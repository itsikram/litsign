/*
 * Exit survey (inc/exit-survey.php): when a visitor is about to leave without
 * ordering, ask what stopped them. Desktop: the mouse leaves through the top
 * of the window (toward the tabs or address bar). Phones: a quick scroll back
 * up after reading down the page. Shown at most once per visit; closing it
 * snoozes it for a few days, answering for 90 days, and ordering turns it off.
 * The stylesheet loads only once the survey is armed, so it costs nothing on
 * first paint.
 */
(() => {
  const config = window.wholesaleES;
  if (!config || !config.endpoint || !window.fetch || navigator.webdriver) return;

  const readCookie = (name) => {
    const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  };
  const writeCookie = (name, value, days) => {
    document.cookie = name + '=' + encodeURIComponent(value) + '; max-age=' + Math.round(days * 86400) +
      '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
  };
  const store = (key, value) => {
    try {
      if (value === undefined) return window.sessionStorage.getItem(key);
      window.sessionStorage.setItem(key, value);
    } catch (e) { /* private mode */ }
    return null;
  };

  // A finished order means this visitor is never asked why they did not buy.
  if (config.ordered) {
    writeCookie('sso_es_done', '1', 90);
    return;
  }

  const preview = !!config.preview;
  if (!preview && (readCookie('sso_vi_off') === '1' || readCookie('sso_es_done') || readCookie('sso_es_snooze') || store('sso_es_shown'))) return;

  const reasons = Array.isArray(config.reasons) ? config.reasons : [];
  if (!reasons.length) return;

  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  if (!finePointer && !config.mobile && !preview) return;

  // ------------------------------------------------------------------
  // Icons (inline so nothing extra is downloaded)
  // ------------------------------------------------------------------

  const ICONS = {
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    tag: '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    help: '<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4.5"/><path d="M12 17.5h.01"/>',
    chat: '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-5A8 8 0 1 1 21 12z"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    tool: '<path d="M14.7 6.3a4 4 0 0 0 5 5L21 13l-8 8-3-3 1.7-1.3a4 4 0 0 0-5-5L3 8l5-5 3 3a4 4 0 0 0 3.7.3z"/>',
    box: '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
    alert: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    dots: '<circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/>',
    close: '<path d="M18 6 6 18M6 6l12 12"/>',
    check: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    phone: '<path d="M6.6 10.8a15 15 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
    message: '<path d="M4 4h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H8l-4 4V5a1 1 0 0 1 1-1z"/>',
  };
  const icon = (name) => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' + (ICONS[name] || ICONS.dots) + '</svg>';
  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

  // ------------------------------------------------------------------
  // Talking to the server
  // ------------------------------------------------------------------

  const send = (payload) => window.fetch(config.endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    keepalive: true,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  }).then((response) => response.json()).catch(() => ({}));

  // ------------------------------------------------------------------
  // Stylesheet, loaded once the survey may be needed
  // ------------------------------------------------------------------

  let cssReady = null;
  const loadCss = () => {
    if (cssReady) return cssReady;
    cssReady = new Promise((resolve) => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = config.css;
      link.onload = resolve;
      link.onerror = resolve;
      document.head.appendChild(link);
      setTimeout(resolve, 2500);
    });
    return cssReady;
  };

  // ------------------------------------------------------------------
  // The dialog
  // ------------------------------------------------------------------

  let opened = false;
  let root = null;
  let record = Promise.resolve({});
  let answered = false;
  let lastFocus = null;

  const cart = parseInt(config.cart, 10) || 0;
  const title = cart > 0
    ? 'Before you go, what is holding you back from ordering?'
    : 'Before you go, what stopped you from ordering today?';
  const eyebrow = cart > 0
    ? 'You have ' + cart + ' item' + (cart === 1 ? '' : 's') + ' in your cart'
    : 'Quick question · takes 10 seconds';

  const build = () => {
    const options = reasons.map((reason, index) => (
      '<label class="sso-es__option">' +
        '<input type="radio" name="sso-es-reason" value="' + escapeHtml(reason.key) + '"' + (index === 0 ? ' required' : '') + '>' +
        '<span class="sso-es__option-icon">' + icon(reason.icon) + '</span>' +
        '<span class="sso-es__option-text">' + escapeHtml(reason.label) + '</span>' +
      '</label>'
    )).join('');

    const phoneHref = 'tel:+1' + String(config.phone || '').replace(/\D/g, '');
    const textHref = 'sms:+1' + String(config.text || '').replace(/\D/g, '');

    const el = document.createElement('div');
    el.className = 'sso-es';
    el.innerHTML =
      '<div class="sso-es__backdrop" data-es-close></div>' +
      '<div class="sso-es__dialog" role="dialog" aria-modal="true" aria-labelledby="sso-es-title" aria-describedby="sso-es-lead">' +
        '<button type="button" class="sso-es__close" data-es-close aria-label="Close survey">' + icon('close') + '</button>' +
        '<div class="sso-es__step" data-es-step="ask">' +
          '<p class="sso-es__eyebrow">' + escapeHtml(eyebrow) + '</p>' +
          '<h2 class="sso-es__title" id="sso-es-title">' + escapeHtml(title) + '</h2>' +
          '<p class="sso-es__lead" id="sso-es-lead">Your answer helps us make ordering a sign easier. It stays anonymous unless you leave your contact details.</p>' +
          '<form class="sso-es__form" novalidate>' +
            '<fieldset class="sso-es__fieldset"><legend class="sso-es__sr">What stopped you from ordering?</legend>' +
              '<div class="sso-es__options">' + options + '</div>' +
            '</fieldset>' +
            '<div class="sso-es__more" hidden>' +
              '<label class="sso-es__label" for="sso-es-details">Anything you would like to add? <span>Optional</span></label>' +
              '<textarea class="sso-es__input" id="sso-es-details" rows="3" maxlength="1000"></textarea>' +
              '<label class="sso-es__label" for="sso-es-contact">Want a reply? Email or phone <span>Optional</span></label>' +
              '<input class="sso-es__input" id="sso-es-contact" type="text" maxlength="190" autocomplete="email" placeholder="you@company.com or (555) 555-5555">' +
              '<input class="sso-es__trap" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">' +
            '</div>' +
            '<div class="sso-es__actions">' +
              '<button type="submit" class="sso-es__submit" disabled>Send answer</button>' +
              '<button type="button" class="sso-es__skip" data-es-close>No thanks</button>' +
            '</div>' +
          '</form>' +
        '</div>' +
        '<div class="sso-es__step sso-es__done" data-es-step="done" hidden>' +
          '<span class="sso-es__done-icon">' + icon('check') + '</span>' +
          '<h2 class="sso-es__title" tabindex="-1">Thank you for your feedback</h2>' +
          '<p class="sso-es__lead" data-es-reply></p>' +
          '<div class="sso-es__contact">' +
            '<a class="sso-es__contact-link" href="' + escapeHtml(phoneHref) + '">' + icon('phone') + '<span><strong>Call ' + escapeHtml(config.phone) + '</strong><small>Mon–Fri, 8 am–5 pm PST</small></span></a>' +
            '<a class="sso-es__contact-link" href="' + escapeHtml(textHref) + '">' + icon('message') + '<span><strong>Text ' + escapeHtml(config.text) + '</strong><small>Send a photo of your storefront</small></span></a>' +
          '</div>' +
          '<button type="button" class="sso-es__submit sso-es__continue" data-es-close>Continue browsing</button>' +
        '</div>' +
      '</div>';
    return el;
  };

  const focusables = () => Array.from(root.querySelectorAll('button, a[href], input:not([type="hidden"]):not(.sso-es__trap), textarea'))
    .filter((node) => !node.disabled && node.offsetParent !== null);

  const onKey = (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }
    if (event.key !== 'Tab') return;
    const nodes = focusables();
    if (!nodes.length) return;
    const first = nodes[0];
    const last = nodes[nodes.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  };

  const close = () => {
    if (!root) return;
    if (!answered) {
      if (!preview) writeCookie('sso_es_snooze', '1', parseInt(config.snoozeDays, 10) || 7);
      record.then((saved) => {
        if (saved && saved.id) send({ action: 'dismiss', id: saved.id, token: saved.token });
      });
    }
    document.removeEventListener('keydown', onKey, true);
    root.classList.remove('is-open');
    document.documentElement.classList.remove('sso-es-lock');
    const node = root;
    root = null;
    setTimeout(() => node.remove(), 250);
    if (lastFocus && document.contains(lastFocus)) lastFocus.focus({ preventScroll: true });
  };

  const showThanks = (reasonKey, hasContact) => {
    const reason = reasons.find((item) => item.key === reasonKey) || {};
    const step = root.querySelector('[data-es-step="done"]');
    step.querySelector('[data-es-reply]').textContent = (reason.reply || 'Thank you.') +
      (hasContact ? ' We will get back to you within one business day.' : '');
    root.querySelector('[data-es-step="ask"]').hidden = true;
    step.hidden = false;
    step.querySelector('.sso-es__title').focus({ preventScroll: true });
  };

  const wire = () => {
    const form = root.querySelector('form');
    const more = root.querySelector('.sso-es__more');
    const details = root.querySelector('#sso-es-details');
    const submit = root.querySelector('.sso-es__submit');

    root.addEventListener('click', (event) => {
      if (event.target.closest('[data-es-close]')) close();
    });

    form.addEventListener('change', (event) => {
      if (event.target.name !== 'sso-es-reason') return;
      form.querySelectorAll('.sso-es__option').forEach((label) => {
        label.classList.toggle('is-checked', label.contains(event.target));
      });
      const reason = reasons.find((item) => item.key === event.target.value) || {};
      details.placeholder = reason.placeholder || '';
      submit.disabled = false;
      if (more.hidden) {
        more.hidden = false;
        root.querySelector('.sso-es__dialog').classList.add('is-expanded');
      }
    });

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const picked = form.querySelector('input[name="sso-es-reason"]:checked');
      if (!picked) return;
      const contact = root.querySelector('#sso-es-contact').value.trim();
      const payload = {
        action: 'answer',
        reason: picked.value,
        details: details.value.trim(),
        contact: contact,
        website: root.querySelector('.sso-es__trap').value,
      };

      answered = true;
      if (!preview) writeCookie('sso_es_done', '1', 90);
      record.then((saved) => {
        if (saved && saved.id) send(Object.assign(payload, { id: saved.id, token: saved.token }));
      });
      showThanks(picked.value, contact !== '');
    });
  };

  const somethingElseOpen = () => {
    const dialogs = document.querySelectorAll('.modal.show, .modal.in, [aria-modal="true"]:not([hidden])');
    if (Array.from(dialogs).some((node) => node.getClientRects().length)) return true;
    const active = document.activeElement;
    // Someone typing into a form is not leaving.
    return !!(active && /^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName) && active.type !== 'submit');
  };

  const open = (trigger) => {
    if (opened || (!preview && somethingElseOpen())) return;
    opened = true;
    store('sso_es_shown', '1');
    detach();

    loadCss().then(() => {
      lastFocus = document.activeElement;
      root = build();
      document.body.appendChild(root);
      wire();
      document.documentElement.classList.add('sso-es-lock');
      document.addEventListener('keydown', onKey, true);
      // Read layout once so the opening transition runs from the hidden state.
      void root.offsetWidth;
      root.classList.add('is-open');
      const first = root.querySelector('.sso-es__option input');
      if (first && finePointer) first.focus({ preventScroll: true });
      else root.querySelector('.sso-es__close').focus({ preventScroll: true });

      // Previews by staff are not counted.
      if (preview) return;
      record = send({
        action: 'shown',
        trigger: trigger,
        ptype: config.ptype || '',
        cart: cart,
        mobile: finePointer ? 0 : 1,
        url: location.pathname + location.search,
      });
    });
  };

  // ------------------------------------------------------------------
  // When to ask
  // ------------------------------------------------------------------

  const onMouseOut = (event) => {
    if (event.relatedTarget || event.toElement) return;
    if (event.clientY > 6) return;
    open('exit');
  };

  // Phones have no mouse to leave the window with. A fast flick back toward
  // the top after reading down the page is the usual sign someone is heading
  // for the address bar or the back button.
  let lastY = window.scrollY;
  let lastAt = performance.now();
  let deepest = 0;
  const onScroll = () => {
    const y = window.scrollY;
    const now = performance.now();
    const height = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
    deepest = Math.max(deepest, y / height);
    const dy = y - lastY;
    const dt = Math.max(1, now - lastAt);
    if (dy < -120 && dy / dt < -1.8 && deepest > 0.35 && y < height * 0.5) open('scroll');
    lastY = y;
    lastAt = now;
  };

  const detach = () => {
    document.removeEventListener('mouseout', onMouseOut);
    window.removeEventListener('scroll', onScroll);
  };

  const arm = () => {
    loadCss();
    if (finePointer) {
      document.addEventListener('mouseout', onMouseOut);
    } else {
      lastY = window.scrollY;
      lastAt = performance.now();
      window.addEventListener('scroll', onScroll, { passive: true });
    }
  };

  if (preview) {
    setTimeout(() => open('preview'), 600);
    return;
  }

  // Count only time the page is actually in front of the visitor. Phones
  // wait a little longer, since scrolling starts sooner than real reading.
  const wait = (parseInt(config.minSeconds, 10) || 0) * 1000 * (finePointer ? 1 : 1.5);
  let visibleFor = 0;
  let since = document.visibilityState === 'visible' ? performance.now() : 0;
  const tick = () => {
    if (since) visibleFor += performance.now() - since;
    since = document.visibilityState === 'visible' ? performance.now() : 0;
    if (visibleFor >= wait) {
      clearInterval(timer);
      document.removeEventListener('visibilitychange', tick);
      arm();
    }
  };
  const timer = setInterval(tick, 1000);
  document.addEventListener('visibilitychange', tick);
  tick();
})();
