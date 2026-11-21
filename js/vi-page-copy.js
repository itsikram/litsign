/*
 * Automatic screenshots (inc/screenshots.php): a copy of the page when a visitor runs into
 * an error or leaves the site. js/visitor-tracker.js loads this file after the visitor's
 * first move, scroll or tap (or right after an error), never during page load, and never
 * for staff, robots or visitors who opted out of Visitor Insights.
 *
 * Nothing is drawn here. Like Live Chat's snap.js, the page is only copied (HTML, typed
 * values, ticked boxes, canvas drawings, scroll spots; password and card fields masked; the
 * chat window left out) and the admin's browser draws the picture later. While the page is
 * on screen the copy runs in short slices, so the visitor never feels a pause.
 *
 * Leaving: a browser gives a closing page almost no time and lets it send only ~64 KB, so
 *  - a copy is kept ready (made again a moment after the visitor stops scrolling, tapping
 *    or typing) and sent the instant the page is hidden or closed;
 *  - on a desktop, a fresh copy is sent as the mouse leaves through the top of the window
 *    (towards the tab's close button, the address bar or another tab);
 *  - the page's large inline <style> blocks are uploaded once (while the page is on
 *    screen) and the copies only name them, which halves their size.
 * The visitor's next page on this site in the same tab, or their return to this page,
 * takes the "left" screenshot back. The server also drops it when the visit carried on.
 */
(() => {
  'use strict';
  if (window.retailsShots) return;

  // ------------------------------------------------------------------
  // Page copier (same rules as sfs-live-chat src/widget/snap.js)
  // ------------------------------------------------------------------

  const PRIVATE_NAME = /(pass(word|wd)?|card.?(num|no|number|code)|cc.?(num|number|csc|cvv|exp)|cvv|cvc|csc|security.?code|expir|ssn|social.?sec|iban|routing|acct|account.?num)/i;
  const DROP_TAGS = new Set(['SCRIPT', 'NOSCRIPT', 'TEMPLATE', 'AUDIO', 'BASE']);
  const KEEP_LINKS = /(^|\s)stylesheet(\s|$)/i;
  const MAX_CANVAS_PIXELS = 8e6;
  // Inline <style> blocks at least this long are stored once on the server, by content hash.
  const SHARED_CSS_MIN = 2048;

  const isPrivateField = (el) => {
    const type = (el.getAttribute('type') || '').toLowerCase();
    if (type === 'password') return true;
    const auto = (el.getAttribute('autocomplete') || '').toLowerCase();
    if (auto.indexOf('cc-') === 0 || auto.indexOf('password') !== -1 || auto === 'one-time-code') return true;
    return el.hasAttribute('data-sfs-private') || PRIVATE_NAME.test((el.getAttribute('name') || '') + ' ' + (el.id || ''));
  };

  const mask = (value) => (value ? '•'.repeat(Math.min(String(value).length, 16)) : '');

  /** Copy the live state of one element onto its copy. Returns true when the copy should go. */
  const carry = (o, c) => {
    const tag = o.tagName;
    if (DROP_TAGS.has(tag) || o.id === 'sfs-chat-root' || o.hasAttribute('data-sfs-snap-ignore')) return true;
    switch (tag) {
      case 'LINK':
        if (!KEEP_LINKS.test(o.rel || '')) return true;
        if (o.href) c.setAttribute('href', o.href);
        break;
      case 'META':
        if (o.hasAttribute('http-equiv')) return true;
        break;
      case 'IMG':
        if (o.currentSrc) {
          c.setAttribute('src', o.currentSrc);
          c.removeAttribute('srcset');
          c.removeAttribute('sizes');
        }
        c.removeAttribute('loading');
        break;
      case 'INPUT': {
        const type = (o.type || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') {
          if (o.checked) c.setAttribute('checked', '');
          else c.removeAttribute('checked');
        } else if (type === 'file') {
          c.removeAttribute('value');
        } else if (type !== 'hidden') {
          c.setAttribute('value', isPrivateField(o) ? mask(o.value) : o.value);
        }
        break;
      }
      case 'TEXTAREA':
        c.textContent = isPrivateField(o) ? mask(o.value) : o.value;
        break;
      case 'SELECT':
        for (let i = 0; i < o.options.length; i++) {
          if (!c.options || !c.options[i]) continue;
          if (o.options[i].selected) c.options[i].setAttribute('selected', '');
          else c.options[i].removeAttribute('selected');
        }
        break;
      case 'CANVAS':
        // Drawings (the channel letter builder preview) live only in the canvas.
        try {
          if (o.width && o.height && o.width * o.height <= MAX_CANVAS_PIXELS) {
            c.style.setProperty('background', 'url("' + o.toDataURL('image/png') + '") center / 100% 100% no-repeat');
          }
        } catch (error) {
          // Tainted by a cross-origin image: leave it blank.
        }
        break;
      case 'VIDEO':
        c.removeAttribute('autoplay');
        c.setAttribute('preload', 'none');
        try {
          if (o.videoWidth && o.readyState >= 2) {
            const canvas = document.createElement('canvas');
            canvas.width = o.videoWidth;
            canvas.height = o.videoHeight;
            canvas.getContext('2d').drawImage(o, 0, 0);
            c.setAttribute('poster', canvas.toDataURL('image/jpeg', 0.8));
          }
        } catch (error) {
          // Cross-origin video: keep its own poster.
        }
        break;
      case 'IFRAME':
        // Maps, video players and payment fields would load again elsewhere: grey box.
        c.removeAttribute('srcdoc');
        c.setAttribute('src', 'about:blank');
        c.style.setProperty('background', '#e5e7eb');
        break;
      case 'STYLE':
        // Rules added from JavaScript are not in the text.
        try {
          if (!o.textContent.trim() && o.sheet && o.sheet.cssRules && o.sheet.cssRules.length) {
            c.textContent = Array.prototype.map.call(o.sheet.cssRules, (rule) => rule.cssText).join('\n');
          }
        } catch (error) {
          // Ignore.
        }
        break;
      default:
    }
    if (o.scrollTop || o.scrollLeft) c.setAttribute('data-sfs-scroll', Math.round(o.scrollLeft) + ',' + Math.round(o.scrollTop));
    return false;
  };

  /** Give the page a turn between slices of work. */
  const nextTask = () => new Promise((resolve) => {
    if (window.scheduler && typeof window.scheduler.yield === 'function') window.scheduler.yield().then(resolve, resolve);
    else setTimeout(resolve, 0);
  });

  const toHex = (buffer) => Array.from(new Uint8Array(buffer), (byte) => byte.toString(16).padStart(2, '0')).join('');

  /** First 20 hex characters of the SHA-256 of the text's UTF-8 bytes ('' when unavailable). */
  const cssKey = async (text) => {
    try {
      if (!window.crypto || !crypto.subtle || typeof TextEncoder !== 'function') return '';
      return toHex(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text))).slice(0, 20);
    } catch (error) {
      return '';
    }
  };

  const pack = async (html) => {
    if (typeof CompressionStream === 'function' && typeof Blob.prototype.stream === 'function') {
      try {
        const stream = new Blob([html]).stream().pipeThrough(new CompressionStream('gzip'));
        const buffer = await new Response(stream).arrayBuffer();
        return { blob: new Blob([buffer], { type: 'application/gzip' }), enc: 'gzip' };
      } catch (error) {
        // Fall back to the plain text.
      }
    }
    return { blob: new Blob([html], { type: 'text/html' }), enc: 'raw' };
  };

  /**
   * Copy the page as the visitor sees it now. `fast`: no pauses (the page is hidden, so
   * nobody can feel one, and a hidden page may get no second turn).
   */
  const capture = async (fast) => {
    const root = document.documentElement;
    const shot = {
      w: root.clientWidth || window.innerWidth,
      h: window.innerHeight || root.clientHeight,
      sx: Math.round(window.scrollX || window.pageXOffset || 0),
      sy: Math.round(window.scrollY || window.pageYOffset || 0),
      ph: Math.round(Math.max(root.scrollHeight, document.body ? document.body.scrollHeight : 0)),
      dpr: Math.round((window.devicePixelRatio || 1) * 100) / 100,
      page: location.href,
      title: document.title,
      at: Date.now(),
    };

    const copy = root.cloneNode(true);
    const originals = Array.from(root.getElementsByTagName('*'));
    const copies = Array.from(copy.getElementsByTagName('*'));
    if (!fast) await nextTask();
    const drop = [];
    let slice = performance.now();
    for (let i = 0; i < originals.length && i < copies.length; i++) {
      if (carry(originals[i], copies[i])) drop.push(copies[i]);
      if (!fast && performance.now() - slice > 12) {
        await nextTask();
        slice = performance.now();
      }
    }
    drop.forEach((el) => el.remove());

    // Large inline styles go by name; their text is uploaded once (see shareCss()).
    const css = {};
    const styles = Array.from(copy.getElementsByTagName('style'));
    for (let i = 0; i < styles.length; i++) {
      const text = styles[i].textContent;
      if (text.length < SHARED_CSS_MIN) continue;
      const key = await cssKey(text);
      if (!key) break;
      css[key] = text;
      styles[i].textContent = '';
      styles[i].setAttribute('data-wsh-css', key);
    }

    const head = copy.querySelector('head') || copy.insertBefore(document.createElement('head'), copy.firstChild);
    const base = document.createElement('base');
    base.setAttribute('href', location.href);
    head.insertBefore(base, head.firstChild);
    if (document.adoptedStyleSheets && document.adoptedStyleSheets.length) {
      try {
        const style = document.createElement('style');
        style.textContent = document.adoptedStyleSheets.map((sheet) => Array.prototype.map.call(sheet.cssRules, (rule) => rule.cssText).join('\n')).join('\n');
        head.appendChild(style);
      } catch (error) {
        // Ignore.
      }
    }
    if (!fast) await nextTask();
    const html = (document.doctype ? '<!DOCTYPE ' + document.doctype.name + '>' : '<!DOCTYPE html>') + copy.outerHTML;
    const packed = await pack(html);
    shot.blob = packed.blob;
    shot.enc = packed.enc;
    shot.css = css;
    return shot;
  };

  // ------------------------------------------------------------------
  // Small helpers
  // ------------------------------------------------------------------

  const now = () => Date.now();
  const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
  const randomKey = (length) => {
    const bytes = new Uint8Array(length / 2);
    window.crypto.getRandomValues(bytes);
    return toHex(bytes.buffer);
  };
  const clean = (value, max) => String(value === null || value === undefined ? '' : value).replace(/\s+/g, ' ').trim().slice(0, max || 200);
  const session = {
    get(key, fallback) {
      try {
        const value = window.sessionStorage.getItem(key);
        return value ? JSON.parse(value) : fallback;
      } catch (error) {
        return fallback;
      }
    },
    set(key, value) {
      try {
        if (value === null) window.sessionStorage.removeItem(key);
        else window.sessionStorage.setItem(key, JSON.stringify(value));
      } catch (error) {
        // Private mode or storage full: only this page remembers.
      }
    },
  };

  let started = false;

  const start = (api) => {
    if (started || !api || !api.config || !api.config.url) return;
    started = true;
    const cfg = api.config;
    const fetchFn = typeof api.fetch === 'function' ? api.fetch : (window.fetch ? window.fetch.bind(window) : null);
    if (!fetchFn || typeof FormData !== 'function' || typeof Blob !== 'function') return;
    // A closing page may send about 64 KB in all; the tracker's own last ping needs a little.
    const LEAVING_MAX = 60000;
    let internalAt = 0; // when the visitor followed a link or form to another page of this site
    let externalAt = 0; // when they followed a link to another site

    // ----------------------------------------------------------------
    // Shared styles: uploaded once while the page is on screen
    // ----------------------------------------------------------------

    let knownCss = new Set();
    try {
      knownCss = new Set(JSON.parse(window.localStorage.getItem('wsh_css') || '[]'));
    } catch (error) {
      // No storage: this page remembers on its own.
    }
    const rememberCss = (keys) => {
      keys.forEach((key) => knownCss.add(key));
      try {
        window.localStorage.setItem('wsh_css', JSON.stringify(Array.from(knownCss).slice(-150)));
      } catch (error) {
        // Ignore.
      }
    };
    const missingCss = (shot) => Object.keys(shot.css || {}).filter((key) => !knownCss.has(key));
    const cssBlob = (shot, keys) => {
      const texts = {};
      keys.forEach((key) => { texts[key] = shot.css[key]; });
      return new Blob([JSON.stringify(texts)], { type: 'application/json' });
    };
    const readJson = (response) => (response && response.ok ? response.json().catch(() => null) : null);

    // Usually another visitor's browser already sent these styles: ask first (a few
    // bytes), then send only what the server still needs.
    let cssBusy = null;
    const shareCss = (shot) => {
      if (cssBusy) return cssBusy;
      const missing = missingCss(shot);
      if (!missing.length || !cfg.css) return Promise.resolve();
      const done = (json) => {
        if (json && Array.isArray(json.have)) rememberCss(json.have.filter((key) => missing.indexOf(key) !== -1));
        return json;
      };
      cssBusy = Promise.resolve(fetchFn(cfg.css, {
        method: 'POST',
        body: JSON.stringify({ s: api.session, v: api.visitor, keys: missing }),
        credentials: 'same-origin',
        headers: { 'Content-Type': 'text/plain' },
      }))
        .then(readJson)
        .then(done)
        .then((json) => {
          const need = json && Array.isArray(json.need) ? json.need.filter((key) => missing.indexOf(key) !== -1) : [];
          if (!need.length) return null;
          const form = new FormData();
          form.append('meta', JSON.stringify({ s: api.session, v: api.visitor }));
          form.append('css', cssBlob(shot, need), 'css.json');
          return Promise.resolve(fetchFn(cfg.css, { method: 'POST', body: form, credentials: 'same-origin' })).then(readJson).then(done);
        })
        .catch(() => null)
        .then(() => { cssBusy = null; });
      return cssBusy;
    };

    // ----------------------------------------------------------------
    // Sending
    // ----------------------------------------------------------------

    /**
     * @param {object}  fields  kind, type, ref, … (saved with the screenshot)
     * @param {object}  shot    From capture().
     * @param {boolean} leaving The page is being hidden or closed: send it the way that survives.
     * @returns {Promise<number>} HTTP status (0 unknown or failed, 202 queued by the browser).
     */
    const post = (fields, shot, leaving) => {
      const meta = Object.assign({
        s: api.session,
        v: api.visitor,
        p: api.page,
        enc: shot.enc,
        w: shot.w,
        h: shot.h,
        sx: shot.sx,
        sy: shot.sy,
        ph: shot.ph,
        dpr: shot.dpr,
        page: shot.page,
        title: clean(shot.title, 200),
        age: Math.max(0, now() - shot.at),
      }, fields);
      const form = new FormData();
      // Styles the server may still need (rare: shareCss() runs first) go along when the
      // whole send still fits a closing page's allowance.
      let missing = missingCss(shot);
      const extra = missing.length ? cssBlob(shot, missing) : null;
      if (extra && (!leaving || extra.size + shot.blob.size + 3000 < LEAVING_MAX)) form.append('css', extra, 'css.json');
      else missing = [];
      form.append('meta', JSON.stringify(meta));
      form.append('copy', shot.blob, shot.enc === 'gzip' ? 'page.html.gz' : 'page.html');
      const small = shot.blob.size + (extra && missing.length ? extra.size : 0) + 3000 < LEAVING_MAX;
      if (leaving && small && navigator.sendBeacon) {
        try {
          if (navigator.sendBeacon(cfg.url, form)) return Promise.resolve(202);
        } catch (error) {
          // Fall back to fetch.
        }
      }
      try {
        return Promise.resolve(fetchFn(cfg.url, { method: 'POST', body: form, credentials: 'same-origin', keepalive: !!(leaving && small) }))
          .then((response) => {
            if (!response) return 0;
            if (response.ok && missing.length) rememberCss(missing);
            return response.status;
          }, () => 0);
      } catch (error) {
        return Promise.resolve(0);
      }
    };

    const takeBack = (ref) => {
      if (!ref || !cfg.back) return;
      const body = JSON.stringify({ s: api.session, v: api.visitor, ref });
      try {
        if (navigator.sendBeacon && navigator.sendBeacon(cfg.back, new Blob([body], { type: 'text/plain' }))) return;
      } catch (error) {
        // Fall back to fetch.
      }
      try {
        Promise.resolve(fetchFn(cfg.back, { method: 'POST', body, keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'text/plain' } })).catch(() => {});
      } catch (error) {
        // Offline: the server's own check removes it.
      }
    };

    // ----------------------------------------------------------------
    // Errors
    // ----------------------------------------------------------------

    const ERRORS_PER_PAGE = 4;
    const ERRORS_PER_VISIT = 12;
    const seenErrors = new Set(session.get('wsh_seen', []));
    let errorsThisPage = 0;
    let errorJob = null;
    let lastErrorShot = 0;

    const takeErrorShot = async () => {
      const job = errorJob;
      if (!job) return;
      let shot = null;
      try {
        shot = await capture(document.hidden);
      } catch (error) {
        shot = null;
      }
      errorJob = null;
      lastErrorShot = now();
      if (!shot) return;
      if (!document.hidden) await shareCss(shot);
      const first = job.list[0];
      const fields = { kind: 'error', type: first.type, label: first.label, where: first.where, more: job.list.slice(1, 6), ref: randomKey(24) };
      let status = await post(fields, shot, document.hidden);
      // 409: the visit's first page view has not reached the server yet.
      if (status === 409) {
        await sleep(5000);
        status = await post(fields, shot, document.hidden);
      }
    };

    const problem = (item) => {
      if (!cfg.err || !item) return;
      const type = clean(item.type, 30) || 'js_error';
      const label = clean(item.label || type, 300);
      // Numbers vary (sizes, prices, HTTP codes) while the problem is the same one.
      const key = clean(label.toLowerCase().replace(/\d+/g, '#'), 140);
      if (!key || seenErrors.has(key)) return;
      seenErrors.add(key);
      session.set('wsh_seen', Array.from(seenErrors).slice(-60));
      const entry = { type, label, where: clean(item.where, 160) };
      // A second error before the screenshot is taken goes on the same screenshot.
      if (errorJob) {
        errorJob.list.push(entry);
        return;
      }
      const visitCount = session.get('wsh_n', 0);
      if (errorsThisPage >= ERRORS_PER_PAGE || visitCount >= ERRORS_PER_VISIT) return;
      errorsThisPage++;
      session.set('wsh_n', visitCount + 1);
      errorJob = { list: [entry] };
      // Let the page show its message first; space screenshots out.
      setTimeout(takeErrorShot, Math.max(900, 4000 - (now() - lastErrorShot)));
    };

    // Problems the tracker saw (script errors, failed price/cart/upload/payment requests).
    const drainProblems = () => {
      const list = Array.isArray(api.problems) ? api.problems.splice(0, api.problems.length) : [];
      list.forEach(problem);
    };
    api.report = drainProblems;
    drainProblems();

    // Messages the page shows when something went wrong.
    const MESSAGE_SELECTOR = [
      '.alert-danger', '.clq-alert', '.cla-form-alert', '.landing-quote-message-error', '.contact-message-error',
      '.contact-upload-error', '.contact-form-errors', '.checkout-alert', '[data-pay-status][data-type="error"]',
      '.wpt-paybox__error', '.track-error', '.pb-dim-error', '.artwork-size-status.is-error', '.async-upload-status.is-error',
      '.product-review-alert-error', '.wqr-error', '.woocommerce-error', '.cl-cart-status',
    ].join(',');
    const shown = (el) => !el.hidden && el.getClientRects().length > 0 && window.getComputedStyle(el).visibility !== 'hidden';
    const scanMessages = () => {
      if (!cfg.err) return;
      try {
        document.querySelectorAll(MESSAGE_SELECTOR).forEach((el) => {
          if (el.closest('#sfs-chat-root, [data-sfs-snap-ignore]') || !shown(el)) return;
          const text = clean(el.innerText || el.textContent, 300);
          if (text.length > 3) problem({ type: 'message', label: text });
        });
      } catch (error) {
        // Never get in the page's way.
      }
    };
    let scanTimers = [];
    const scanSoon = () => {
      scanTimers.forEach(clearTimeout);
      scanTimers = [setTimeout(scanMessages, 1000), setTimeout(scanMessages, 3500)];
    };
    ['click', 'submit', 'change'].forEach((name) => document.addEventListener(name, (event) => { if (event.isTrusted) scanSoon(); }, true));
    setTimeout(scanMessages, 300);

    // The browser's own "Please fill out this field" checks.
    document.addEventListener('invalid', (event) => {
      const field = event.target;
      if (!field || !field.matches || !field.matches('input, select, textarea') || field.closest('#sfs-chat-root, [role="search"], .search-form')) return;
      let name = field.getAttribute('aria-label') || field.placeholder || field.name || field.id || field.type || 'field';
      if (field.id && window.CSS && CSS.escape) {
        const tag = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
        if (tag) name = tag.textContent;
      }
      problem({ type: 'form_error', label: clean(name, 60) + ': ' + clean(field.validationMessage || 'not accepted', 160) });
    }, true);

    // Server errors (HTTP 500 and up) behind the page's own requests.
    const OWN_REQUEST = /sfs-chat|retails\/v1\/vi|admin-ajax\.php\?action=heartbeat/;
    const sameSiteUrl = (url) => {
      try {
        const u = new URL(String(url), location.href);
        return u.origin === location.origin && (u.pathname.indexOf('admin-ajax.php') !== -1 || u.pathname.indexOf('/wp-json/') !== -1 || u.search.indexOf('rest_route=') !== -1) ? u : null;
      } catch (error) {
        return null;
      }
    };
    const serverError = (url, status, action) => {
      const u = sameSiteUrl(url);
      if (!u || OWN_REQUEST.test(u.pathname + u.search)) return;
      const what = action || (u.pathname.indexOf('/wp-json/') !== -1 ? u.pathname.split('/wp-json/')[1] : u.pathname.split('/').pop());
      problem({ type: 'server_error', label: 'The server answered HTTP ' + (status || 'nothing') + ' to ' + clean(what, 80), where: clean(u.pathname, 120) });
    };
    if (window.fetch) {
      const pageFetch = window.fetch;
      window.fetch = function (input, init) {
        const request = pageFetch.apply(this, arguments);
        try {
          const url = typeof input === 'string' ? input : (input && input.url) || '';
          if (sameSiteUrl(url)) {
            const body = init && init.body;
            const action = body && typeof body.get === 'function' ? body.get('action') : '';
            Promise.resolve(request).then((response) => {
              if (response && response.status >= 500) serverError(url, response.status, action);
            }, () => {});
          }
        } catch (error) {
          // Never get in the way of the page's own request.
        }
        return request;
      };
    }
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.on) {
      try {
        window.jQuery(document).on('ajaxError', (event, xhr, settings) => {
          if (!xhr || !settings || xhr.statusText === 'abort') return;
          if (xhr.status >= 500 || (xhr.status === 0 && now() - internalAt > 5000 && !document.hidden)) {
            const data = typeof settings.data === 'string' ? settings.data : '';
            const match = /(?:^|&)action=([^&]+)/.exec(data);
            serverError(settings.url, xhr.status, match ? decodeURIComponent(match[1]) : '');
          }
        });
      } catch (error) {
        // Ignore.
      }
    }

    // ----------------------------------------------------------------
    // Leaving
    // ----------------------------------------------------------------

    const EXIT_SENDS_PER_PAGE = 6;
    let ready = null; // the copy kept for the moment the page goes away
    let readyAt = 0;
    let dirty = true;
    let preparing = false;
    let preparedCount = 0;
    let settleTimer = 0;
    let intentAt = 0;
    let exitSends = 0;
    let outRef = ''; // the "left the site" screenshot currently out (sent and not taken back)
    let sentAt = 0;
    const desktop = !!(window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches);

    const prepare = async () => {
      if (!cfg.exit || preparing || document.hidden || preparedCount >= 25 || (!dirty && ready)) return;
      const wait = 12000 - (now() - readyAt);
      if (ready && wait > 0) {
        clearTimeout(settleTimer);
        settleTimer = setTimeout(prepare, wait + 50);
        return;
      }
      preparing = true;
      dirty = false;
      try {
        const shot = await capture(false);
        ready = shot;
        readyAt = now();
        preparedCount++;
        shareCss(shot);
      } catch (error) {
        // Try again after the next pause.
      }
      preparing = false;
    };

    const settle = () => {
      dirty = true;
      clearTimeout(settleTimer);
      settleTimer = setTimeout(prepare, 1500);
    };
    ['scroll', 'click', 'input', 'change', 'keyup', 'touchend'].forEach((name) => window.addEventListener(name, settle, { passive: true, capture: true }));

    const sendExit = (type, shot, leaving) => {
      if (!shot || exitSends >= EXIT_SENDS_PER_PAGE) return;
      exitSends++;
      const ref = randomKey(24);
      const prev = outRef;
      outRef = ref;
      sentAt = now();
      // The next page of this site in this tab takes it back (visitor-tracker.js).
      session.set('wsh_exit', { ref, s: api.session, v: api.visitor, url: cfg.back, at: sentAt });
      post({ kind: 'exit', type, ref, prev }, shot, leaving);
    };

    const cameBack = () => {
      if (!outRef) return;
      const ref = outRef;
      outRef = '';
      session.set('wsh_exit', null);
      takeBack(ref);
      // A send still on its way could land after the first take-back.
      setTimeout(() => takeBack(ref), 6000);
    };

    const leaving = (how) => {
      if (!cfg.exit || now() - internalAt < 8000) return;
      // The desktop copy taken a moment ago as the mouse left the window covers it.
      if (outRef && now() - sentAt < 20000) return;
      const type = now() - externalAt < 8000 ? 'external' : how;
      if (ready) {
        sendExit(type, ready, true);
        return;
      }
      // No copy kept yet: try one now (the page may not get the time to finish it).
      capture(true).then((shot) => sendExit(type, shot, true), () => {});
    };

    document.addEventListener('click', (event) => {
      if (!event.isTrusted) return;
      const link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
      if (!link || event.defaultPrevented) return;
      if (link.target === '_blank' || link.hasAttribute('download') || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
      const href = link.getAttribute('href') || '';
      if (/^(#|javascript:|tel:|mailto:|sms:)/i.test(href)) return;
      try {
        const url = new URL(link.href, location.href);
        if (url.origin === location.origin) {
          if (url.pathname !== location.pathname || url.search !== location.search) internalAt = now();
        } else if (/^https?:$/.test(url.protocol)) {
          externalAt = now();
        }
      } catch (error) {
        // Ignore.
      }
    }, true);
    document.addEventListener('submit', (event) => {
      const form = event.target;
      try {
        const action = form && form.getAttribute && form.getAttribute('action');
        if (!action || new URL(action, location.href).origin === location.origin) internalAt = now();
      } catch (error) {
        internalAt = now();
      }
    }, true);

    // After a desktop "about to leave" screenshot, using the page again means they stayed.
    const stayed = (event) => {
      if (event.isTrusted && outRef && !document.hidden && now() - sentAt > 1500) cameBack();
    };
    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach((name) => window.addEventListener(name, stayed, { passive: true, capture: true }));

    if (desktop) {
      document.addEventListener('mouseout', (event) => {
        if (event.relatedTarget || event.clientY > 0 || document.hidden || !cfg.exit) return;
        if (now() - intentAt < 15000 || exitSends >= EXIT_SENDS_PER_PAGE) return;
        intentAt = now();
        capture(false).then((shot) => {
          ready = shot;
          readyAt = now();
          dirty = false;
          shareCss(shot);
          // Still on screen, but the tab may close any moment: send it the way that survives.
          sendExit('exit_intent', shot, true);
        }, () => {});
      });
    }

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) leaving('hidden');
      else cameBack();
    });
    window.addEventListener('pagehide', () => leaving('closed'));
    window.addEventListener('pageshow', (event) => { if (event.persisted) cameBack(); });

    if (cfg.exit) setTimeout(prepare, 1500);
  };

  window.retailsShots = { start };
})();
