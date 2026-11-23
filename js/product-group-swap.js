/**
 * Product group buttons ("Graphic + Frame" / "Graphic Only", "Flag + Pole" / "Flag Only"...),
 * B2Sign style: a button swaps the other product into this page without reloading it.
 *
 * The products stay separate (own price, options, cart line, feed item, URL). The button's
 * link is the group's first product with ?graphic_only=true etc. (inc/product-group-swap.php);
 * this script puts that product's block in place of this one, and the page scripts set
 * themselves up again on "wholesale:product-swapped" (js/main.js, js/ui-polish.js,
 * js/buying-journey.js, js/async-upload.js). The address bar shows the link, so a refresh,
 * a shared link or Back shows the same product. Anything unexpected opens the link instead.
 *
 * Every other option of the group is loaded, parsed and its main photos fetched as soon as
 * the page is ready (low priority, so the page's own photo and price come first), so a tap
 * swaps at once. Save-Data visitors load an option on hover / touch instead.
 */
(function () {
  'use strict';

  var SELECTOR = '[data-product-page]';
  var LINK = '.product-group-item-link';
  var page = function () { return document.querySelector(SELECTOR); };
  if (!page() || !window.fetch || !window.DOMParser || !window.history || !window.history.pushState) return;

  var bare = function (url) { return String(url).split('#')[0]; };
  var shown = bare(window.location.href);
  // The page to fetch for a link: the product's own URL when it shows the same group (a
  // cached static page on the live site), else the link itself.
  var source = function (link) {
    return bare(link.getAttribute('data-fetch-url') ? new URL(link.getAttribute('data-fetch-url'), window.location.href).href : link.href);
  };
  var linkFor = function (url) {
    var block = page();
    return block ? Array.prototype.find.call(block.querySelectorAll(LINK), function (link) { return bare(link.href) === url; }) : null;
  };

  // The option's main photo and first thumbnails, fetched ahead with the page.
  var warmed = {};
  var warmPhotos = function (doc) {
    var next = doc.querySelector(SELECTOR);
    var photos = Array.prototype.slice.call(next.querySelectorAll('.product-image-zoom img, .thumbnail-container img'), 0, 2)
      .concat(Array.prototype.slice.call(next.querySelectorAll('.gallery-image-item img'), 0, 5));
    photos.forEach(function (photo) {
      var src = photo.getAttribute('src') || photo.getAttribute('data-src') || '';
      var srcset = photo.getAttribute('srcset') || '';
      if ((!src && !srcset) || warmed[src + srcset]) return;
      warmed[src + srcset] = true;
      var img = new Image();
      img.decoding = 'async';
      if ('fetchPriority' in img) img.fetchPriority = 'low';
      if (photo.getAttribute('sizes')) img.sizes = photo.getAttribute('sizes');
      if (srcset) img.srcset = srcset;
      if (src) img.src = src;
    });
  };

  // One request per page, parsed once and kept; low priority when nobody is waiting for it.
  var pages = {};
  var load = function (url, background) {
    if (!pages[url]) {
      var init = { credentials: 'same-origin' };
      if (background) init.priority = 'low';
      pages[url] = fetch(url, init).then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.text();
      }).then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        if (!doc.querySelector(SELECTOR)) throw new Error('No product block');
        warmPhotos(doc);
        return doc;
      });
      pages[url].catch(function () { delete pages[url]; });
    }
    return pages[url];
  };

  var saveData = Boolean(navigator.connection && navigator.connection.saveData);
  var idle = function (fn) {
    if (window.requestIdleCallback) window.requestIdleCallback(fn, { timeout: 1500 });
    else window.setTimeout(fn, 200);
  };
  var loadOthers = function () {
    if (saveData) return;
    idle(function () {
      var block = page();
      if (!block) return;
      block.querySelectorAll('.product-group-item:not(.current) ' + LINK).forEach(function (link) {
        load(source(link), true).catch(function () {});
      });
    });
  };

  // Scripts in the new block run like the page's own (DOMParser leaves them inert).
  var runScripts = function (root) {
    root.querySelectorAll('script').forEach(function (old) {
      var type = (old.getAttribute('type') || '').toLowerCase();
      if (type && type !== 'text/javascript' && type !== 'module') return;
      var script = document.createElement('script');
      Array.prototype.forEach.call(old.attributes, function (attr) { script.setAttribute(attr.name, attr.value); });
      script.text = old.textContent;
      old.replaceWith(script);
    });
  };

  // Title, canonical, description and share tags, structured data, the body's postid class
  // and the admin bar's Edit link follow the product shown.
  var updatePage = function (doc) {
    if (doc.title) document.title = doc.title;
    var canonical = doc.querySelector('link[rel="canonical"]');
    var ownCanonical = document.querySelector('link[rel="canonical"]');
    if (canonical && ownCanonical) ownCanonical.setAttribute('href', canonical.getAttribute('href'));
    doc.querySelectorAll('head meta[name], head meta[property]').forEach(function (meta) {
      var attr = meta.hasAttribute('name') ? 'name' : 'property';
      var own = document.head.querySelector('meta[' + attr + '="' + CSS.escape(meta.getAttribute(attr)) + '"]');
      if (own && meta.hasAttribute('content')) own.setAttribute('content', meta.getAttribute('content'));
    });

    var outside = function (root) {
      return Array.prototype.filter.call(root.querySelectorAll('script[type="application/ld+json"]'), function (el) {
        return !el.closest(SELECTOR);
      });
    };
    var oldData = outside(document);
    var spot = oldData.length ? oldData[0] : null;
    outside(doc).forEach(function (el) {
      var script = document.createElement('script');
      script.type = 'application/ld+json';
      script.text = el.textContent;
      if (spot) spot.before(script); else document.body.appendChild(script);
    });
    oldData.forEach(function (el) { el.remove(); });

    var postClass = function (body) {
      return Array.prototype.filter.call(body.classList, function (name) { return /^postid-\d+$/.test(name); });
    };
    if (doc.body) {
      postClass(document.body).forEach(function (name) { document.body.classList.remove(name); });
      postClass(doc.body).forEach(function (name) { document.body.classList.add(name); });
    }

    var edit = doc.querySelector('#wp-admin-bar-edit > a');
    var ownEdit = document.querySelector('#wp-admin-bar-edit > a');
    if (edit && ownEdit) ownEdit.setAttribute('href', edit.getAttribute('href'));
  };

  // The screen-centre spinner (styles: inc/product-group-swap.php). Fixed to the viewport, so
  // it shows wherever the visitor has scrolled; it never takes clicks.
  var SPIN_MIN = 350;
  var spinnerEl = null;
  var spinner = function (on) {
    if (on && !spinnerEl) {
      spinnerEl = document.createElement('div');
      spinnerEl.className = 'pgs-spinner';
      spinnerEl.setAttribute('role', 'status');
      spinnerEl.innerHTML = '<span class="pgs-spinner__ring" aria-hidden="true"></span><span class="pgs-spinner__text">Loading…</span>';
      document.body.appendChild(spinnerEl);
    }
    if (spinnerEl) spinnerEl.classList.toggle('is-on', Boolean(on));
  };

  var latest = 0;
  // url: the group link the address bar shows; from: the page that has its product block.
  var swap = function (url, from, push, link) {
    var current = page();
    if (!current) {
      window.location.assign(url);
      return;
    }
    var ticket = ++latest;
    var hadFocus = document.activeElement && document.activeElement.closest && document.activeElement.closest('.product-group-container');
    if (link) {
      // The chosen button reads as chosen at once; the block follows (at once when loaded ahead).
      current.querySelectorAll('.product-group-item').forEach(function (item) {
        var on = item.contains(link);
        item.classList.toggle('current', on);
        item.classList.toggle('is-loading', on);
      });
    }
    // Loading spinner: in the chosen button and in the middle of the screen, over the dimmed
    // options. It stays at least SPIN_MIN ms so a swap that is ready at once still reads as
    // "changing" instead of a jump.
    var started = Date.now();
    current.classList.add('is-swapping');
    spinner(true);

    load(from, false).then(function (doc) {
      if (ticket !== latest) return;
      var wait = SPIN_MIN - (Date.now() - started);
      return wait > 0 ? new Promise(function (done) { window.setTimeout(function () { done(doc); }, wait); }) : doc;
    }).then(function (doc) {
      if (!doc || ticket !== latest) return;
      spinner(false);
      var next = doc.querySelector(SELECTOR);

      var apply = function () {
        var block = page();
        var anchor = block.querySelector('.product-group-container');
        var before = anchor ? anchor.getBoundingClientRect().top : null;
        var fresh = document.importNode(next, true);
        block.replaceWith(fresh);
        runScripts(fresh);
        if (push) window.history.pushState({ productGroupSwap: true }, '', url);
        shown = bare(url);
        updatePage(doc);
        // Keep the buttons where they were on screen.
        var after = fresh.querySelector('.product-group-container');
        if (before !== null && after) window.scrollBy(0, after.getBoundingClientRect().top - before);
        // detail: id / productId (the product shown), key (its group key, '' for the group's
        // first product), url (the address bar). B2Sign Sync's admin panel listens too.
        var shownId = parseInt(fresh.getAttribute('data-product-page'), 10) || 0;
        var shownLink = fresh.querySelector('.product-group-item.current ' + LINK);
        document.dispatchEvent(new CustomEvent('wholesale:product-swapped', {
          detail: { id: shownId, productId: shownId, key: shownLink ? shownLink.getAttribute('data-group-key') || '' : '', url: url },
        }));
        if (hadFocus && shownLink) shownLink.focus({ preventScroll: true });
        loadOthers();
      };

      // Straight swap, like B2Sign: a crossfade's snapshot alone cost ~150 ms per tap.
      apply();
    }).catch(function () {
      // The spinner stays up while the browser opens the link instead.
      if (ticket === latest) window.location.assign(url);
    });
  };

  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest(LINK);
    if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var block = page();
    if (!block || !block.contains(link)) return;
    e.preventDefault();
    if (link.closest('.product-group-item.current') || bare(link.href) === shown) return;
    swap(bare(link.href), source(link), true, link);
  });

  // A hover, touch or keyboard focus loads that option now (it may not be loaded yet on Save-Data).
  var warm = function (e) {
    var link = e.target.closest && e.target.closest(LINK);
    if (link && !link.closest('.product-group-item.current')) load(source(link), false).catch(function () {});
  };
  document.addEventListener('pointerover', warm, { passive: true });
  document.addEventListener('touchstart', warm, { passive: true });
  document.addEventListener('focusin', warm);

  // Load the other options as soon as the page is ready.
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', loadOthers);
  else loadOthers();

  // Back / Forward between products of the group.
  window.history.replaceState(Object.assign({}, window.history.state || {}, { productGroupSwap: true }), '');
  window.addEventListener('popstate', function (e) {
    var url = bare(window.location.href);
    if (!e.state || !e.state.productGroupSwap || url === shown) return;
    var link = linkFor(url);
    swap(url, link ? source(link) : url, false, link);
  });
})();
