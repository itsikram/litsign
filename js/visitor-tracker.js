/*
 * Visitor Insights tracker: records page views, time on page, scroll depth,
 * clicks, form use, errors and shopping steps, and sends them to the site's
 * own REST route (inc/visitor-insights.php). First-party only. It records which
 * field a visitor was on, never what they typed.
 */
(() => {
  const config = window.wholesaleVI;
  if (!config || !config.endpoint || navigator.webdriver || !window.JSON) return;
  if (/(?:^|;\s*)sso_vi_off=1/.test(document.cookie)) return;

  // ------------------------------------------------------------------
  // Visitor and visit IDs
  // ------------------------------------------------------------------

  const readCookie = (name) => {
    const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  };
  const writeCookie = (name, value, seconds) => {
    document.cookie = name + '=' + encodeURIComponent(value) + (seconds !== undefined ? '; max-age=' + seconds : '') +
      '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
  };
  const randomKey = (length) => {
    const bytes = new Uint8Array(length / 2);
    window.crypto.getRandomValues(bytes);
    return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
  };
  const hash = (text) => {
    let value = 5381;
    for (let i = 0; i < text.length; i++) value = ((value << 5) + value + text.charCodeAt(i)) | 0;
    return (value >>> 0).toString(36);
  };

  const params = new URLSearchParams(location.search);
  const campaignKeys = ['gclid', 'gbraid', 'wbraid', 'msclkid', 'fbclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
  const campaign = {};
  campaignKeys.forEach((key) => {
    const value = params.get(key);
    if (value) campaign[key] = value.slice(0, 190);
  });
  const campaignSig = Object.keys(campaign).length ? hash(campaignKeys.map((key) => campaign[key] || '').join('|')) : '';

  let visitor = readCookie('sso_vid');
  if (!/^[a-f0-9]{32}$/.test(visitor)) visitor = randomKey(32);
  writeCookie('sso_vid', visitor, 31536000);

  // A visit ends after 30 idle minutes, or when the visitor comes back through
  // a different ad or campaign link.
  let session = readCookie('sso_sid');
  if (!/^[a-f0-9]{32}$/.test(session) || (campaignSig && campaignSig !== readCookie('sso_ssrc'))) {
    session = randomKey(32);
    writeCookie('sso_ssrc', campaignSig, campaignSig ? undefined : 0);
  }
  const touch = () => writeCookie('sso_sid', session, 1800);
  touch();

  // ------------------------------------------------------------------
  // Queue and sending
  // ------------------------------------------------------------------

  const pageKey = randomKey(8);
  const queue = [];
  const originalFetch = window.fetch ? window.fetch.bind(window) : null;
  let lastSent = '';

  const push = (type, data) => {
    queue.push(Object.assign({ t: type, at: Date.now() }, data || {}));
  };

  const text = (value, max) => String(value || '').replace(/\s+/g, ' ').trim().slice(0, max || 80);

  // ------------------------------------------------------------------
  // Engaged time and scroll depth
  // ------------------------------------------------------------------

  let activeSeconds = 0;
  let lastActivity = Date.now();
  let maxScroll = 0;
  const markActive = () => { lastActivity = Date.now(); };

  setInterval(() => {
    if (!document.hidden && Date.now() - lastActivity < 60000) activeSeconds++;
  }, 1000);

  const measureScroll = () => {
    const doc = document.documentElement;
    const height = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0);
    const seen = height > 0 ? Math.round(((window.scrollY || doc.scrollTop) + window.innerHeight) / height * 100) : 100;
    maxScroll = Math.max(maxScroll, Math.min(100, seen));
  };
  let scrollQueued = false;
  window.addEventListener('scroll', () => {
    markActive();
    if (scrollQueued) return;
    scrollQueued = true;
    requestAnimationFrame(() => { scrollQueued = false; measureScroll(); });
  }, { passive: true });
  ['pointerdown', 'pointermove', 'keydown', 'touchstart'].forEach((name) => window.addEventListener(name, markActive, { passive: true }));

  // ------------------------------------------------------------------
  // Forms
  // ------------------------------------------------------------------

  const forms = new Map();

  const fieldName = (field) => {
    let label = '';
    if (field.id && window.CSS && CSS.escape) {
      const tag = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
      if (tag) label = tag.textContent;
    }
    if (!label && field.closest('label')) label = field.closest('label').textContent;
    return text(label || field.getAttribute('aria-label') || field.placeholder || field.name || field.id || field.type, 60);
  };

  const formName = (form) => {
    const heading = form.querySelector('h1, h2, h3, h4, legend');
    const name = form.getAttribute('aria-label') || form.id || form.getAttribute('name') || (heading && heading.textContent) || '';
    if (name) return text(name, 60);
    if (config.ptype === 'checkout') return 'Checkout';
    return text((form.className || 'form').split(' ')[0], 60) + ' on ' + location.pathname.slice(0, 60);
  };

  const isTrackedField = (field) => field && field.matches && field.matches('input, select, textarea') &&
    !/^(hidden|submit|button|image|reset|search)$/.test(field.type) && !field.closest('[role="search"], .search-form');

  document.addEventListener('focusin', (event) => {
    const field = event.target;
    if (!isTrackedField(field)) return;
    const form = field.form || field.closest('form');
    if (!form) return;
    let state = forms.get(form);
    if (!state) {
      state = { name: formName(form), field: '', submitted: false };
      forms.set(form, state);
      push('form_start', { label: state.name });
    }
    state.field = fieldName(field);
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target;
    const state = forms.get(form);
    if (form.closest && form.closest('[role="search"], .search-form')) return;
    push('form_submit', { label: state ? state.name : formName(form) });
    if (state) state.submitted = true;
    send(true);
  }, true);

  // Choosing sign options on a product or the builder.
  let configured = false;
  const markConfigured = (label) => {
    if (configured || (config.ptype !== 'product' && config.ptype !== 'builder')) return;
    configured = true;
    push('configure', { label: text(label, 60) });
  };
  document.addEventListener('change', (event) => {
    if (event.isTrusted && event.target && event.target.matches && event.target.matches('input, select, textarea')) markConfigured(fieldName(event.target));
  }, true);

  // ------------------------------------------------------------------
  // Clicks and rage clicks
  // ------------------------------------------------------------------

  let clickCount = 0;
  let lastClick = { label: '', at: 0 };
  let recentClicks = [];

  const elementLabel = (el) => text(
    el.getAttribute('aria-label') || el.innerText || el.value || el.getAttribute('title') || el.getAttribute('alt') ||
    (el.querySelector && el.querySelector('img[alt]') && el.querySelector('img[alt]').alt) || el.id || el.getAttribute('name') || el.tagName.toLowerCase()
  );

  document.addEventListener('click', (event) => {
    const now = Date.now();

    recentClicks = recentClicks.filter((click) => now - click.at < 800 && Math.abs(click.x - event.clientX) < 40 && Math.abs(click.y - event.clientY) < 40);
    recentClicks.push({ at: now, x: event.clientX, y: event.clientY });
    if (recentClicks.length === 3) {
      const target = event.target.closest('a, button, input, select, label, img, [role="button"]') || event.target;
      push('rage_click', { label: elementLabel(target) });
    }

    const el = event.target.closest('a, button, input[type="submit"], input[type="button"], [role="button"], .btn');
    if (!el) {
      if (config.ptype === 'builder' && event.isTrusted) markConfigured('builder');
      return;
    }

    const href = el.tagName === 'A' ? (el.getAttribute('href') || '') : '';
    const label = elementLabel(el);

    if (/^tel:/i.test(href)) {
      push('call_click', { label: href.slice(4).trim() });
      send(true);
      return;
    }
    if (/^mailto:/i.test(href)) {
      push('email_click', { label: href.slice(7).split('?')[0] });
      send(true);
      return;
    }
    if (config.ptype === 'builder' && !href && event.isTrusted) markConfigured(label);

    if (clickCount >= 60 || (label === lastClick.label && now - lastClick.at < 1000)) return;
    clickCount++;
    lastClick = { label, at: now };
    push('click', { label, href: href && !/^(#|javascript:)/i.test(href) ? el.href : '' });
  }, true);

  // ------------------------------------------------------------------
  // Script errors
  // ------------------------------------------------------------------

  // Errors from scripts that in-app browsers inject into every page (the
  // Facebook and Instagram apps on iPhone call window.webkit.messageHandlers),
  // not from the site itself.
  const injectedError = /webkit\.messageHandlers|__gCrWeb|instantSearchSDKJSBridgeClearHighlight/;

  let errorCount = 0;
  const logError = (message, where) => {
    message = text(message, 200);
    if (!message || message === 'Script error.' || injectedError.test(message) || errorCount++ >= 5) return;
    push('js_error', { label: message, value: text(where, 120) });
  };
  window.addEventListener('error', (event) => {
    if (event.message) logError(event.message, (event.filename || '').split('/').pop() + ':' + (event.lineno || 0));
  });
  window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason;
    logError(reason && reason.message ? reason.message : reason, 'promise');
  });

  // ------------------------------------------------------------------
  // Shopping steps, read from the theme's own AJAX calls
  // ------------------------------------------------------------------

  // action => [event on success, event on failure]
  const ajaxEvents = {
    wholesale_price_quote: ['price_quote', 'price_error'],
    wholesale_upload_design: ['design_upload', 'upload_error'],
    wholesale_mini_cart_add: ['add_to_cart', 'cart_error'],
    wholesale_mini_cart_remove: ['cart_remove', ''],
    wholesale_payment_start: ['payment_start', 'checkout_error'],
    wholesale_payment_complete: ['', 'payment_error'],
  };
  const seenOnce = new Set();

  const recordAjax = (action, json) => {
    const events = ajaxEvents[action];
    const ok = !!json && (json.ok === true || json.success === true || (json.ok === undefined && json.success === undefined && !json.error));
    if (action === 'wholesale_payment_start') push('payment_start');

    if (ok) {
      const type = events[0];
      if (!type || type === 'payment_start') return send();
      if (type === 'price_quote') {
        // Product pages price the default options on load; only count a
        // price the visitor asked for by changing an option.
        if (!configured || seenOnce.has('price')) return;
        seenOnce.add('price');
      }
      push(type, { value: json && json.total ? String(json.total) : '' });
      return send();
    }

    if (!events[1]) return;
    const data = json && json.data;
    const message = (json && (json.error || json.message)) || (data && (typeof data === 'string' ? data : data.message || data.error)) || 'The request failed';
    const key = events[1] + message;
    if (seenOnce.has(key) && events[1] === 'price_error') return;
    seenOnce.add(key);
    push(events[1], { label: text(message, 200) });
    send();
  };

  if (originalFetch) {
    window.fetch = function (input, init) {
      const request = originalFetch.apply(null, arguments);
      try {
        const body = init && init.body;
        const action = body && typeof body.get === 'function' ? body.get('action') : '';
        if (action && ajaxEvents[action]) {
          request
            .then((response) => response.clone().json())
            .then((json) => recordAjax(action, json))
            .catch(() => recordAjax(action, { ok: false, error: 'No valid response from the server' }));
        }
      } catch (error) {
        // Never get in the way of the page's own request.
      }
      return request;
    };
  }

  // ------------------------------------------------------------------
  // Sending
  // ------------------------------------------------------------------

  const payload = () => {
    measureScroll();
    const openForms = [];
    forms.forEach((state) => {
      if (!state.submitted && state.field) openForms.push({ n: state.name, f: state.field });
    });
    const now = Date.now();
    return JSON.stringify({
      v: visitor,
      s: session,
      p: pageKey,
      url: location.href,
      tp: navigator.maxTouchPoints || 0,
      ping: { secs: activeSeconds, scroll: maxScroll, forms: openForms },
      events: queue.splice(0, 40).map((event) => {
        event.ago = now - event.at;
        delete event.at;
        return event;
      }),
    });
  };

  function send(urgent) {
    const changed = activeSeconds + ':' + maxScroll;
    if (!queue.length && changed === lastSent) return;
    lastSent = changed;
    touch();
    const body = payload();

    if (urgent && navigator.sendBeacon && navigator.sendBeacon(config.endpoint, new Blob([body], { type: 'text/plain' }))) return;
    fetchSafe(body);
  }

  const fetchSafe = (body) => {
    if (!originalFetch) return;
    try {
      Promise.resolve(originalFetch(config.endpoint, {
        method: 'POST',
        body,
        keepalive: body.length < 60000,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'text/plain' },
      })).catch(() => {});
    } catch (error) {
      // Offline or blocked: drop it.
    }
  };

  push('pageview', {
    title: document.title,
    ptype: config.ptype || 'page',
    cart: config.cart || 0,
    ref: document.referrer.slice(0, 500),
    campaign,
    screen: window.screen ? window.screen.width + 'x' + window.screen.height : '',
    tz: (Intl.DateTimeFormat().resolvedOptions().timeZone || '').slice(0, 60),
    lang: (navigator.language || '').slice(0, 20),
  });
  send();

  setInterval(() => { if (!document.hidden) send(); }, 15000);
  document.addEventListener('visibilitychange', () => { if (document.hidden) send(true); });
  window.addEventListener('pagehide', () => send(true));
})();
