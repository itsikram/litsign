/* Motion layer. How it works and why: comments in inc/motion.php. */
(function () {
  'use strict';
  var R = document.documentElement, L = R.getAttribute('data-motion');
  if ((L !== 'full' && L !== 'calm') || !('IntersectionObserver' in window)) return;
  var desk = matchMedia('(min-width: 992px) and (hover: hover)'),
    red = matchMedia('(prefers-reduced-motion: reduce)'),
    sl = window.CSS && CSS.supports && CSS.supports('animation-timeline', 'view()'),
    obs = {}, fns = new WeakMap(), hidden = new Set(),
    ROOTS = 'main,#content,.home,.landing-page,.product-details,.contact-page,.site-main',
    SECTS = 'section,.card,.sf-type-card,.cl-step',
    GRIDS = ['hc-pcard-grid hc-shop-cl-grid hc-feature hc-dir sf-type-grid cl-steps-list cl-journey-steps cll-why-grid gd-facts', 'gd-family-grid gd-link-grid related-products-grid sc-grid product-review-list pkg-grid product-box-container cl-faq-list product-info category-guide-body gd-steps gd-checklist srp-grid'],
    KICK = '.cl-kicker,.cla-kicker,.hc-shop-cl-kicker,.product-section-eyebrow,.contact-eyebrow',
    SKIP = 'header,nav,.modal,.offcanvas,.home-hero,.bd-hero,.sf-hero,.sc-hero,.cla-hero,.contact-hero,.pd-hero,.cla-quote-wrap,.customer-review-section,.footer-cp-text-container,[aria-hidden="true"]',
    FIELD = 'input:not([type=hidden]),select,textarea';

  function each(s, fn) { [].forEach.call(typeof s === 'string' ? document.querySelectorAll(s) : s, fn); }
  function byClass(names, fn) { names.split(' ').forEach(function (n) { each(document.getElementsByClassName(n), fn); }); }

  function observe(el, m, fn) {
    var io = obs[m] || (obs[m] = new IntersectionObserver(function (es) {
      var batch = new Map();
      es.forEach(function (e) { var f = fns.get(e.target); if (f) f(e, io, batch); });
    }, { rootMargin: m }));
    fns.set(el, fn);
    io.observe(el);
  }

  function ok(el) {
    return !el.closest(SKIP) && !el.querySelector(FIELD);
  }
  function show(el, d) {
    var c = el.classList;
    hidden.delete(el);
    if (d < 0) c.add('mo-now'); else { el.style.setProperty('--mo-delay', d + 'ms'); c.add('mo-animating'); }
    c.add('is-in');
    setTimeout(function () {
      c.remove('mo-reveal', 'mo-fade', 'mo-head', 'mo-now', 'mo-animating', 'is-in', 'mo-sl', 'mo-sl-c', 'mo-sl-f');
      el.style.removeProperty('--mo-delay');
      el.style.removeProperty('--mo-lag');
    }, d < 0 ? 60 : d + 800);
  }

  function revealAll() { hidden.forEach(function (el) { show(el, -1); }); }

  function setupReveals() {
    var ph = !desk.matches;
    if (L !== 'full' || red.matches || location.hash || (ph && !sl)) return;
    var list = new Map(), kids = new Set(), anc = new Set(), path = new Set(), sect = new Map(), grid = function (g) {
      if (g.closest(ROOTS) && !g.closest(SKIP)) each(g.children, function (k, i) { if (i < 24 && !k.matches(SKIP) && !k.querySelector(FIELD)) kids.add(k); });
    };
    byClass(GRIDS[0], grid);
    idle(function () { byClass(GRIDS[1], grid); });
    idle(function () {
    kids.forEach(function (k) { for (var p = k.parentElement; p && p !== document.body; p = p.parentElement) anc.add(p); });
    kids.forEach(function (k) {
      for (var p = k.parentElement, n = []; p && p !== document.body; p = p.parentElement) {
        n.push(p);
        if (!sect.has(p)) sect.set(p, p.matches(SECTS) && ok(p));
        if (sect.get(p)) { n.forEach(function (x) { path.add(x); }); break; }
      }
    });
    path.forEach(function (n) {
      each(n.children, function (c) {
        if (anc.has(c) || kids.has(c) || !ok(c)) return;
        list.set(c, c.matches(KICK) ? 'fade' : c.firstElementChild && c.firstElementChild.matches(KICK) ? 'head' : 'up');
      });
    });
    kids.forEach(function (k) { list.set(k, 'up'); });
    }); idle(function () {
    var sec = function (s) { if (!anc.has(s) && s.parentElement && s.parentElement.closest(ROOTS) && ok(s)) list.set(s, 'up'); };
    each(document.getElementsByTagName('section'), sec);
    }); idle(function () {
    byClass('card sf-type-card cl-step', function (s) { if (!anc.has(s) && s.parentElement && s.parentElement.closest(ROOTS) && ok(s)) list.set(s, 'up'); });
    each('.footer > *', function (f) { if (ok(f)) list.set(f, 'fade'); });
    list.forEach(function (kind, el) {
      for (var p = el.parentElement; p; p = p.parentElement) if (list.has(p)) { list.delete(el); return; }
    });
    idle(function () { list.forEach(function (kind, el) {
      var first = true;
      observe(el, '99999px 0px 10% 0px', function (e, io, batch) {
        var b = e.rootBounds;
        if (first) {
          first = false;
          if (!e.isIntersecting && e.boundingClientRect.height) {
            hidden.add(el);
            if (!ph) {
              el.classList.add('mo-reveal');
              if (kind !== 'up') el.classList.add('mo-' + kind);
              return;
            }
            el.classList.add('mo-sl', kind === 'fade' ? 'mo-sl-f' : kids.has(el) ? 'mo-sl-c' : 'mo-sl');
            if (b && b.width) el.style.setProperty('--mo-lag', Math.max(0, (e.boundingClientRect.left - b.left) / b.width).toFixed(2));
            io.unobserve(el);
            observe(el, '0px 0px -40% 0px', function (f, o) { if (f.isIntersecting) { o.unobserve(el); show(el, -1); } });
            return;
          }
        } else if (e.isIntersecting) {
          var p = el.parentElement, n = batch.get(p) || 0;
          batch.set(p, n + 1);
          show(el, !b || e.boundingClientRect.top < b.bottom / 1.1 ? -1 : Math.min(n * 60, 300));
        } else return;
        io.unobserve(el);
      });
    }); idle(setupGlow); }); });
  }
  function setupGlow() {
    each('.hc-shop-cl-media,.product-listing-container--cl .pb-image-top,.cla-style-image', function (f) {
      var first = true, g;
      observe(f, '0px 0px -25% 0px', function (e, io) {
        var b = e.rootBounds, img;
        if (first) {
          first = false;
          if (!e.isIntersecting && b && e.boundingClientRect.top > b.bottom / 0.75) {
            g = document.createElement('span');
            g.className = 'mo-glow';
            g.setAttribute('aria-hidden', 'true');
            f.appendChild(g);
            return;
          }
        } else if (e.isIntersecting) {
          img = f.querySelector('img');
          if (img && img.complete && img.naturalWidth) {
            g.classList.add('is-on');
            g.onanimationend = function () { g.remove(); };
          } else g.remove();
        } else return;
        io.unobserve(f);
      });
    });
  }

  function nudge(el, kf, ms) {
    if (!red.matches && el.animate) el.animate(kf, { duration: ms, easing: 'cubic-bezier(.22,1,.36,1)' });
  }

  function setupFeedback() {
    var tot = document.querySelector('.product-pricing-box .price-total'), q = document.getElementById('productQuantity'), last;
    if (tot && L === 'full') {
      last = tot.textContent;
      new MutationObserver(function () {
        if (tot.textContent !== last) nudge(tot.parentElement, [{ opacity: 0.4, transform: 'translateY(4px)' }, { opacity: 1, transform: 'none' }], 180);
        last = tot.textContent;
      }).observe(tot, { childList: true, characterData: true, subtree: true });
    }
    document.addEventListener('click', function (e) {
      var b = q && e.target.closest && e.target.closest('[data-qty-step]');
      if (b) nudge(q, [{ transform: 'translateY(' + 3 * b.getAttribute('data-qty-step') + 'px)' }, { transform: 'none' }], 140);
    });
    document.addEventListener('wholesale:cart-added', function () {
      each('.add-to-cart-btn:not(.buy-now-btn)', function (el) {
        el.classList.add('mo-ok');
        setTimeout(function () { el.classList.remove('mo-ok'); }, 1300);
      });
    });
    document.addEventListener('invalid', function (e) {
      nudge(e.target, [0, -4, 4, -4, 4, 0].map(function (x) { return { transform: 'translateX(' + x + 'px)' }; }), 300);
    }, true);
  }

  function setupLoops() {
    each('[data-motion="loop"],.hc-shop-cl-dot,.hc-gal-flag', function (el) {
      observe(el, '0px', function (e) { el.classList.toggle('mo-paused', !e.isIntersecting); });
    });
    document.addEventListener('visibilitychange', function () { R.classList.toggle('mo-tab-hidden', document.hidden); });
  }

  function fail(e) {
    R.classList.add('mo-failsafe');
    revealAll();
    if (window.console) console.warn('Motion effects off:', e);
  }
  function idle(fn) {
    var run = function () { try { fn(); } catch (e) { fail(e); } };
    if (window.requestIdleCallback) requestIdleCallback(run, { timeout: 1500 }); else setTimeout(run, 50);
  }

  try {
    idle(setupReveals);
    addEventListener('load', function () { R.classList.add('mo-loaded'); });
    setupLoops();
    setupFeedback();
    addEventListener('beforeprint', revealAll);
    addEventListener('focusin', function (e) { var t = e.target; if (t && t.nodeType) hidden.forEach(function (el) { if (el.contains(t)) show(el, -1); }); });
    red.addEventListener('change', function () { if (red.matches) { revealAll(); for (var k in obs) obs[k].disconnect(); } });
    desk.addEventListener('change', revealAll);
  } catch (e) {
    fail(e);
  }
})();
