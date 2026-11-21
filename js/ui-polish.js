/**
 * Layout helpers for css/ui-polish.css (sections 8 and 9). Loaded on every page
 * except the channel letter builder.
 *
 * 1. Phones and tablets: the sticky header slides away while the visitor scrolls
 *    down and comes back as soon as they scroll up.
 * 2. Phones: the long channel letter guide on the home page opens with a button.
 * 3. Product pages:
 *    - Desktop: the photo column (photo, thumbnails, price box) sticks below the
 *      sticky header while the options scroll, only when the whole column fits in
 *      the window, so its price box is never cut off.
 *    - Every width: a bar fixed to the bottom of the screen shows the live Grand
 *      Total, a cart button and Buy Now above and below the Grand Total section
 *      (hidden only while the Grand Total and the real buttons are on screen).
 *      Tablets and desktops also show the photo, name and a one-line summary of the
 *      chosen options (a tap goes back to the options). A price change counts up to
 *      the new total and shows the difference. Its buttons click the real ones, so
 *      the form's own checks (sign text, size, required fields) run.
 *    - Channel letters: before the sign text is typed, the Grand Total shows the
 *      starting price instead of $0.00.
 * 4. Delivered price. 5. One-tap size chips.
 * 6. "Order within ... and it ships ..." delivery estimate, and the Same Day option
 *    shown only while it can still be ordered (inc/product-buy-box.php).
 * 7. Quantity - / + buttons.
 * 8. The whole "Starting at ... Get your price" box scrolls to the options.
 * 9. Contact page: a tap on the form heading puts the cursor in the name box.
 */

// Product group buttons (js/product-group-swap.js) swap another product into the page and
// fire "retails:product-swapped"; the product sections (3-8) then run again on the new
// markup. The signal ends the last run's window/document listeners, timers and observers,
// and its cleanup removes what that run added outside the product (the phone buy bar).
const retailsProductSection = (setup) => {
  let scope = null;
  const run = () => {
    if (scope) scope.abort();
    scope = new AbortController();
    setup(scope.signal);
  };
  run();
  document.addEventListener('retails:product-swapped', run);
};
// A MutationObserver that stops with the section's run.
const retailsWatch = (signal, target, options, callback) => {
  const observer = new MutationObserver(callback);
  observer.observe(target, options);
  signal.addEventListener('abort', () => observer.disconnect());
};

// ---- 1. Header hides on scroll down (< 992px, the .ssx-legacy header) ----
(function () {
  const header = document.querySelector('.main-header.ssx-legacy');
  if (!header) return;
  const small = window.matchMedia('(max-width: 991.98px)');
  const root = document.documentElement;
  let lastY = window.scrollY;
  let tucked = false;

  const set = (on) => {
    if (on === tucked) return;
    tucked = on;
    header.classList.toggle('is-tucked', on);
    root.classList.toggle('sh-tucked', on);
  };
  // Never while a menu or the mini cart is open.
  const busy = () =>
    header.matches('.mobile-menu-open, .mega-menu-open') ||
    Boolean(document.querySelector('#ssxMobileMenu.is-open, .mc-root.is-open'));

  const onScroll = () => {
    const y = window.scrollY;
    // iOS rubber-band scrolling past either end.
    if (y < 0 || y > document.documentElement.scrollHeight - window.innerHeight) return;
    if (!small.matches || busy() || y < header.offsetHeight * 2) {
      set(false);
      lastY = y;
      return;
    }
    // Small moves add up until they pass 8px, so slow scrolling still counts.
    if (Math.abs(y - lastY) < 8) return;
    set(y > lastY);
    lastY = y;
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  small.addEventListener('change', onScroll);
  // A menu opening while the header is away brings it back.
  new MutationObserver(() => {
    if (tucked && busy()) set(false);
  }).observe(header, { attributes: true, attributeFilter: ['class'] });
})();

// ---- 2. Channel letter guide: "Read the full guide" (phones) ----
(function () {
  const prose = document.querySelector('.cl-guide .sf-prose');
  if (!prose) return;
  const phone = window.matchMedia('(max-width: 767.98px)');
  const limit = 560;
  let button = null;

  const setup = () => {
    if (button || !phone.matches || prose.scrollHeight < limit + 240) return;
    prose.classList.add('is-collapsible');
    prose.id = prose.id || 'cl-guide-text';
    button = document.createElement('button');
    button.type = 'button';
    button.className = 'cl-guide-toggle';
    button.setAttribute('aria-controls', prose.id);
    button.setAttribute('aria-expanded', 'false');
    button.textContent = 'Read the full guide';
    prose.after(button);
    button.addEventListener('click', () => {
      const open = !prose.classList.contains('is-expanded');
      prose.classList.toggle('is-expanded', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      button.textContent = open ? 'Show less' : 'Read the full guide';
      if (!open) prose.closest('.cl-guide').scrollIntoView({ block: 'start' });
    });
  };
  setup();
  phone.addEventListener('change', setup);
})();

// ---- 3. Product pages ----
retailsProductSection((signal) => {
  const form = document.querySelector('.product-details form[data-noun]');
  const addButton = document.getElementById('addToCartBtn');
  // Buy Now (inc/product-buy-box.php); missing when the product is out of stock.
  const buyButton = document.getElementById('buyNowBtn');
  if (!form || !addButton) return;

  // Sticky photo column (768px and up).
  const column = form.querySelector(':scope > .row > .col-md-4');
  const desktop = window.matchMedia('(min-width: 768px)');
  // The desktop header (#ssxHeader) from 992px, the .ssx-legacy header below that.
  const visibleHeader = () =>
    [document.getElementById('ssxHeader'), document.querySelector('.main-header.ssx-legacy')].find((el) => el && el.offsetHeight > 0);
  // Space the sticky header covers at the top of the window.
  const headerSpace = () => {
    const header = visibleHeader();
    if (!header || getComputedStyle(header).position !== 'sticky') return 0;
    return Math.max(0, (parseFloat(getComputedStyle(header).top) || 0) + header.offsetHeight);
  };
  // Height of the price bar at the bottom (css/ui-polish.css 8d), so the column never sits under it.
  const BAR_SPACE = 84;
  const fitColumn = () => {
    if (!column) return;
    const top = 16 + headerSpace();
    column.style.setProperty('--pd-sticky-top', top + 'px');
    column.classList.toggle('pd-col-sticky', desktop.matches && column.offsetHeight + top + 16 + BAR_SPACE <= window.innerHeight);
  };
  if (column) {
    fitColumn();
    window.addEventListener('resize', fitColumn, { signal });
    if ('ResizeObserver' in window) {
      const resize = new ResizeObserver(fitColumn);
      resize.observe(column);
      signal.addEventListener('abort', () => resize.disconnect());
    }
  }

  const pricingBox = form.querySelector('.product-pricing-box');
  const totalEl = form.querySelector('.price-total');
  if (!pricingBox || !totalEl || !('IntersectionObserver' in window)) return;

  // Buy bar / sticky price preview (every width; the item block shows from 768px).
  const pill = document.querySelector('.product-details .product-mobile-price');
  // "Starting at $133.20 per letter" from the price line under the title. During a sale
  // that line also has the everyday price struck through (<del>), kept apart so the bar
  // can strike it too instead of running the two prices together.
  const fromCopy = pill && pill.firstElementChild ? pill.firstElementChild.cloneNode(true) : null;
  const fromDel = fromCopy ? fromCopy.querySelector('del') : null;
  const fromWas = fromDel ? fromDel.textContent.replace(/\s+/g, ' ').trim() : '';
  if (fromDel) fromDel.remove();
  const fromText = fromCopy
    ? fromCopy.textContent.replace(/\s+/g, ' ').replace(/^\s*Starting at\s*/i, '').trim()
    : '';
  // Channel letters: the starting price printed in the Grand Total row (single-product.php).
  const totalFrom = pricingBox.querySelector('.price-total-from');

  const title = document.querySelector('.product-details h1');
  const photo = column ? column.querySelector('img') : null;
  const optionsEl = form.querySelector('#product-options');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  const bar = document.createElement('div');
  bar.className = 'pd-buy-bar';
  bar.hidden = true;
  bar.setAttribute('role', 'region');
  bar.setAttribute('aria-label', 'Order total');
  bar.innerHTML =
    '<button type="button" class="pd-buy-bar__item">' +
    '<img class="pd-buy-bar__thumb" alt="" width="52" height="52" decoding="async" hidden>' +
    '<span class="pd-buy-bar__text"><span class="pd-buy-bar__title"><span class="pd-buy-bar__name"></span>' +
    '<span class="pd-buy-bar__edit">Change options</span></span><span class="pd-buy-bar__summary"></span></span></button>' +
    '<p class="pd-buy-bar__price"><span class="pd-buy-bar__label"></span>' +
    '<span class="pd-buy-bar__was" hidden><s class="pd-buy-bar__regular"></s><span class="pd-buy-bar__save"></span></span>' +
    '<span class="pd-buy-bar__amount"><strong class="pd-buy-bar__total"></strong><span class="pd-buy-bar__delta" aria-hidden="true" hidden></span></span></p>' +
    '<div class="pd-buy-bar__actions">' +
    (buyButton
      ? '<button type="button" class="pd-buy-bar__cart" aria-label="Add to cart"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.76L20 8H6.2M9.5 19.5a1 1 0 1 0 0-.01M17 19.5a1 1 0 1 0 0-.01M15 4v4M13 6h4"/></svg><span class="pd-buy-bar__cart-text" aria-hidden="true">Add to Cart</span></button>'
      : '') +
    '<button type="button" class="pd-buy-bar__button"></button></div>';
  const barItem = bar.querySelector('.pd-buy-bar__item');
  const barThumb = bar.querySelector('.pd-buy-bar__thumb');
  const barName = bar.querySelector('.pd-buy-bar__name');
  const barSummary = bar.querySelector('.pd-buy-bar__summary');
  const barLabel = bar.querySelector('.pd-buy-bar__label');
  const barTotal = bar.querySelector('.pd-buy-bar__total');
  const barDelta = bar.querySelector('.pd-buy-bar__delta');
  const barButton = bar.querySelector('.pd-buy-bar__button');
  const barCart = bar.querySelector('.pd-buy-bar__cart');
  const barWas = bar.querySelector('.pd-buy-bar__was');
  const barRegular = bar.querySelector('.pd-buy-bar__regular');
  const barSave = bar.querySelector('.pd-buy-bar__save');
  // A dated sale (single-product.php data-sale-* on the pricing box): a strip across the
  // top of the phone bar with its name, percent and last day, and the everyday price
  // struck through with the saving above the total (2026-11-21).
  const salePercent = pricingBox.dataset.salePercent || '';
  if (salePercent) {
    bar.classList.add('pd-buy-bar--sale');
    const strip = document.createElement('p');
    strip.className = 'pd-buy-bar__sale';
    strip.innerHTML =
      '<span class="pd-buy-bar__sale-name"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg><span></span></span>' +
      '<span class="pd-buy-bar__sale-pct"></span><span class="pd-buy-bar__sale-ends"></span>';
    strip.querySelector('.pd-buy-bar__sale-name > span').textContent = pricingBox.dataset.saleName || 'Sale';
    strip.querySelector('.pd-buy-bar__sale-pct').textContent = salePercent + '% off';
    strip.querySelector('.pd-buy-bar__sale-ends').textContent = pricingBox.dataset.saleEnds ? 'Ends ' + pricingBox.dataset.saleEnds : '';
    bar.prepend(strip);
  }
  // The bar's main button is Buy Now when there is one, else Add To Cart.
  const mainButton = buyButton || addButton;
  if (buyButton) bar.classList.add('pd-buy-bar--buy');
  // The product name without the admin edit link inside the H1.
  if (title) {
    const copy = title.cloneNode(true);
    copy.querySelectorAll('a, button, svg, .screen-reader-text, .sr-only, .visually-hidden').forEach((el) => el.remove());
    barName.textContent = copy.textContent.replace(/\s+/g, ' ').trim();
  }
  document.body.appendChild(bar);

  // One line of what is chosen: size (W x H), quantity, each option that is on,
  // production time. "No pole pockets"-style choices are left out to keep it short.
  const clean = (text) => (text || '').replace(/\s+/g, ' ').trim();
  // Some labels are lower case and capitalized by CSS (text-capitalize).
  const labelOf = (el) => {
    const label = el.id ? form.querySelector('label[for="' + CSS.escape(el.id) + '"]') : null;
    const text = label ? clean(label.textContent).replace(/^Size\s*-\s*/i, '') : '';
    return text.charAt(0).toUpperCase() + text.slice(1);
  };
  const inches = (n) => String(Math.round(n * 100) / 100);
  const summaryText = () => {
    const parts = [];
    const options = [];
    let size = '';
    // Channel letters: the sign text first.
    const letters = form.querySelector('input[name="letters"]');
    if (letters && clean(letters.value)) parts.push('“' + clean(letters.value).slice(0, 40) + '”');
    form.querySelectorAll('select').forEach((select) => {
      if (!select.offsetParent || select.selectedIndex < 0 || select.value === '') return;
      const text = clean(select.options[select.selectedIndex].text);
      if (!text || /^(no|none|select|choose)\b/i.test(text)) return;
      const label = labelOf(select);
      if (/^size\b/i.test(label) && !size) size = text;
      else options.push(label ? label + ': ' + text : text);
    });
    const width = parseFloat((form.querySelector('input[name="width-in"]') || {}).value) || 0;
    const height = parseFloat((form.querySelector('input[name="height-in"]') || {}).value) || 0;
    if (!size && width > 0 && height > 0) {
      size = width % 12 || height % 12 ? inches(width) + ' × ' + inches(height) + ' in' : width / 12 + ' × ' + height / 12 + ' ft';
    }
    if (size) parts.push(size);
    const qty = form.querySelector('input[name="product_quantity"]');
    if (qty && parseInt(qty.value, 10) > 0) parts.push('Qty ' + parseInt(qty.value, 10));
    parts.push(...options);
    const turn = form.querySelector('input[name="turnaround_option"]:checked');
    if (turn && turn.offsetParent) {
      // B2Sign-style choices (inc/product-buy-box.php) name the speed in their section
      // title; the default Next Day / Same Day labels start with it.
      const section = turn.closest('[data-turnaround-section]');
      const sectionTitle = section ? section.querySelector('.pb-turn-title') : null;
      const label = turn.id ? form.querySelector('label[for="' + CSS.escape(turn.id) + '"]') : null;
      const name = sectionTitle ? clean(sectionTitle.textContent) : label && label.firstChild ? clean(label.firstChild.textContent) : '';
      if (name) parts.push(name + ' production');
    }
    return parts.join(' · ');
  };
  let summaryTimer = 0;
  const queueSummary = () => {
    if (summaryTimer) return;
    summaryTimer = setTimeout(() => {
      summaryTimer = 0;
      const text = summaryText();
      barSummary.textContent = text;
      barItem.title = text ? 'Change options: ' + text : 'Change options';
    }, 30);
  };
  form.addEventListener('change', queueSummary, { signal });
  form.addEventListener('input', queueSummary, { signal });

  // A price change counts up to the new total and shows the difference beside it.
  const money = (n) => '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  let shownValue = null; // the total the bar shows; null while it shows "Starting at"
  let shownText = '';
  let tween = 0;
  let tweenEnd = 0;
  let deltaTimer = 0;
  const showDelta = (diff) => {
    clearTimeout(deltaTimer);
    barDelta.hidden = true;
    void barDelta.offsetWidth; // restarts its animation
    barDelta.textContent = (diff > 0 ? '+' : '−') + money(Math.abs(diff));
    barDelta.classList.toggle('is-down', diff < 0);
    barDelta.hidden = false;
    deltaTimer = setTimeout(() => {
      barDelta.hidden = true;
    }, 2400);
  };
  const setTotal = (value, text) => {
    if (text === shownText) return;
    cancelAnimationFrame(tween);
    clearTimeout(tweenEnd);
    const from = shownValue;
    shownValue = value;
    shownText = text;
    const changed = !bar.hidden && from > 0 && value > 0 && Math.abs(value - from) >= 0.005;
    if (changed) showDelta(value - from);
    if (!changed || reduceMotion.matches) {
      barTotal.textContent = text;
      return;
    }
    // Timed from the first frame: a frame's timestamp can be older than the change.
    let start = 0;
    const step = (now) => {
      if (!start) start = now;
      const t = Math.max(0, Math.min(1, (now - start) / 420));
      barTotal.textContent = t < 1 ? money(from + (value - from) * (1 - Math.pow(1 - t, 3))) : text;
      if (t < 1) tween = requestAnimationFrame(step);
    };
    tween = requestAnimationFrame(step);
    // Frames can pause (a background window); the exact total always lands.
    tweenEnd = setTimeout(() => {
      cancelAnimationFrame(tween);
      barTotal.textContent = text;
    }, 600);
  };
  // js/main.js can show a quick estimate and correct it ~0.2 s later (e.g. before the
  // minimum charge applies); the bar waits for the settled price and animates once.
  let pendingText = '';
  let settleTimer = 0;
  const queueTotal = (value, text) => {
    if (text === pendingText) return;
    pendingText = text;
    clearTimeout(settleTimer);
    if (bar.hidden || shownValue === null || text === shownText) {
      setTotal(value, text);
      return;
    }
    settleTimer = setTimeout(() => setTotal(value, text), 220);
  };
  const clearTotal = (text) => {
    pendingText = '';
    clearTimeout(settleTimer);
    cancelAnimationFrame(tween);
    clearTimeout(tweenEnd);
    shownValue = null;
    shownText = '';
    barDelta.hidden = true;
    barTotal.textContent = text;
  };

  const render = () => {
    const total = parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0;
    const outOfStock = addButton.type !== 'submit';
    // No price yet: channel letters before the sign text is typed, or after it is
    // cleared (js/main.js then shows its "Type your sign text" hint but can leave an
    // old total behind). The Grand Total row shows the starting price instead.
    const noPrice = total <= 0 || Boolean(pricingBox.querySelector('.price-quote-message.is-hint:not([hidden])'));
    if (totalFrom) pricingBox.classList.toggle('pd-total-empty', noPrice);
    const priced = !noPrice || !fromText;
    if (priced) {
      barLabel.textContent = 'Grand Total';
      queueTotal(total, '$' + totalEl.textContent.trim());
      barButton.textContent = mainButton.textContent.trim();
    } else {
      // The bar button takes the visitor to the text box (the real button's own handler).
      barLabel.textContent = 'Starting at';
      if (fromWas) {
        const was = document.createElement('s');
        was.className = 'pd-buy-bar__from-was';
        was.textContent = fromWas;
        barLabel.append(' ', was);
      }
      clearTotal(fromText);
      barButton.textContent = 'Get your price';
    }
    // Sale: "$13.98 (struck) Save $2.10" from the pricing box rows js/main.js fills.
    const amountOf = (selector) => {
      const el = pricingBox.querySelector(selector);
      return el ? parseFloat(el.textContent.replace(/[^0-9.]/g, '')) || 0 : 0;
    };
    const regular = salePercent ? amountOf('.price-subtotal') : 0;
    const saving = salePercent ? amountOf('.price-saving') : 0;
    const showWas = priced && saving >= 0.01 && regular > total;
    if (showWas) {
      barRegular.textContent = money(regular);
      barSave.textContent = 'Save ' + money(saving);
    }
    barWas.hidden = !showWas;
    bar.classList.toggle('has-was', showWas);
    // Option resets made in code (js/main.js) fire no change event; the price follows them.
    queueSummary();
    // The real buttons run the mini cart request (js: .is-adding while it runs).
    const buying = Boolean(buyButton && buyButton.classList.contains('is-adding'));
    const adding = buying || addButton.classList.contains('is-adding');
    if (adding) barButton.textContent = buying ? 'One moment…' : 'Adding…';
    barButton.disabled = outOfStock || adding || mainButton.disabled;
    if (barCart) {
      barCart.hidden = !priced;
      barCart.disabled = outOfStock || adding || addButton.disabled;
    }
    bar.classList.toggle('is-updating', pricingBox.classList.contains('is-updating'));
  };
  render();
  retailsWatch(signal, totalEl, { childList: true, characterData: true, subtree: true }, render);
  retailsWatch(signal, addButton, { attributes: true, attributeFilter: ['disabled', 'class'] }, render);
  if (buyButton) retailsWatch(signal, buyButton, { attributes: true, attributeFilter: ['disabled', 'class'] }, render);
  // The box's own class (.is-updating) and the hint message inside it (hidden / .is-hint).
  retailsWatch(signal, pricingBox, { attributes: true, attributeFilter: ['class', 'hidden'], subtree: true }, render);
  // The sale saving can be written after the total.
  const savingEl = salePercent ? pricingBox.querySelector('.price-saving') : null;
  if (savingEl) retailsWatch(signal, savingEl, { childList: true, characterData: true, subtree: true }, render);

  barButton.addEventListener('click', () => mainButton.click());
  if (barCart) barCart.addEventListener('click', () => addButton.click());
  // Photo, name and summary (768px+): back to the top of the options.
  barItem.addEventListener('click', () => {
    const target = optionsEl || form;
    window.scrollTo({
      top: target.getBoundingClientRect().top + window.scrollY - headerSpace() - 16,
      behavior: reduceMotion.matches ? 'auto' : 'smooth',
    });
  });

  // The chat launcher (sfs-live-chat, Shadow DOM) is placed by its --y offset;
  // lift it above the bar while the bar shows.
  // The chat launcher can load after the bar shows (the bar can show on page load), so
  // this runs on every check and only writes when the offset differs.
  const liftChat = (on) => {
    const chat = document.getElementById('sfs-chat-root');
    if (!chat) return;
    if (on) {
      const base = window.SFSChatConfig && window.SFSChatConfig.brand ? Number(window.SFSChatConfig.brand.offsetY) || 0 : 20;
      const y = (base + bar.offsetHeight) + 'px';
      if (chat.style.getPropertyValue('--y') !== y) chat.style.setProperty('--y', y);
    } else if (chat.style.getPropertyValue('--y')) {
      chat.style.removeProperty('--y');
    }
  };

  // Hidden only while the Grand Total section is on screen (owner, 2026-11-20): shown
  // from the top of the page until the Grand Total row comes up above the bar, and
  // again once the visitor scrolls down past the real Add To Cart / Buy Now buttons
  // (under the sticky header counts as past; a tucked phone header covers nothing).
  // Checked on scroll and resize, and whenever the form changes height (choices that
  // open move the Grand Total without any scrolling).
  const totalRow = pricingBox.querySelector('.total-container') || pricingBox;
  const update = () => {
    if (totalRow.offsetParent === null) {
      if (!bar.hidden) {
        bar.hidden = true;
        document.body.classList.remove('has-pd-buy-bar');
      }
      liftChat(false);
      return;
    }
    const header = visibleHeader();
    const covered = header && header.classList.contains('is-tucked') ? 0 : headerSpace();
    const ends = [addButton, buyButton]
      .filter((el) => el && el.offsetParent !== null)
      .map((el) => el.getBoundingClientRect().bottom);
    const sectionEnd = ends.length ? Math.max(...ends) : totalRow.getBoundingClientRect().bottom;
    const above = totalRow.getBoundingClientRect().top > window.innerHeight - 80;
    const below = sectionEnd < covered;
    const show = above || below;
    if (show !== !bar.hidden) {
      if (show && photo && !barThumb.getAttribute('src') && (photo.currentSrc || photo.src)) {
        barThumb.src = photo.currentSrc || photo.src;
        barThumb.hidden = false;
      }
      bar.hidden = !show;
      document.body.classList.toggle('has-pd-buy-bar', show);
    }
    liftChat(show);
  };
  update();
  window.addEventListener('scroll', update, { passive: true, signal });
  window.addEventListener('resize', update, { signal });
  window.addEventListener('load', update, { signal, once: true });
  let formSize = null;
  if ('ResizeObserver' in window) {
    formSize = new ResizeObserver(update);
    formSize.observe(form);
  }
  // Lift the chat launcher as soon as it is added, if the bar is already up.
  if (!document.getElementById('sfs-chat-root')) {
    retailsWatch(signal, document.body, { childList: true }, (records, watcher) => {
      if (!document.getElementById('sfs-chat-root')) return;
      watcher.disconnect();
      update();
    });
  }
  // A product swap builds a new bar for the new product.
  signal.addEventListener('abort', () => {
    if (formSize) formSize.disconnect();
    cancelAnimationFrame(tween);
    clearTimeout(tweenEnd);
    clearTimeout(settleTimer);
    clearTimeout(summaryTimer);
    clearTimeout(deltaTimer);
    bar.remove();
    document.body.classList.remove('has-pd-buy-bar');
    liftChat(false);
  });
});

// ---- 4. Delivered price under the Grand Total and in the phone buy bar ----
// Price plus standard shipping below the free shipping minimum, so the price an
// ad visitor sees is the price they pay before tax (single-product.php .pd-delivered).
retailsProductSection((signal) => {
  const line = document.querySelector('.product-pricing-box .pd-delivered');
  const totalEl = document.querySelector('.product-pricing-box .price-total');
  if (!line || !totalEl) return;
  const freeMin = parseFloat(line.dataset.freeMin) || 0;
  const ship = parseFloat(line.dataset.ship) || 0;
  const money = (n) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  const render = () => {
    const box = totalEl.closest('.product-pricing-box');
    const total = parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0;
    const noPrice = total <= 0 || (box && box.classList.contains('pd-total-empty'));
    let text = '';
    if (!noPrice && freeMin > 0 && total >= freeMin) {
      text = 'Free shipping. Delivered: $' + money(total) + ', plus sales tax';
    } else if (!noPrice && ship > 0) {
      text = 'Delivered: $' + money(total + ship) + ' (includes $' + (ship % 1 ? money(ship) : ship) + ' shipping), plus sales tax';
    }
    line.textContent = text;
    line.hidden = !text;

    const bar = document.querySelector('.pd-buy-bar .pd-buy-bar__price');
    if (bar) {
      let small = bar.querySelector('.pd-buy-bar__delivered');
      if (!small) {
        small = document.createElement('small');
        small.className = 'pd-buy-bar__delivered';
        bar.appendChild(small);
      }
      small.textContent = text;
      small.hidden = !text;
    }
  };
  render();
  retailsWatch(signal, totalEl, { childList: true, characterData: true, subtree: true }, render);
  const box = totalEl.closest('.product-pricing-box');
  if (box) retailsWatch(signal, box, { attributes: true, attributeFilter: ['class'] }, render);
  // The buy bar is built by section 3 after this script's first run on some pages.
  window.addEventListener('load', render, { signal });
});

// ---- 5. One-tap size chips (inc/size-chips.php) ----
// A size chip fills width and height and prices them (js/main.js prices on the boxes'
// input/change events); typing a size selects the matching chip, or none. Banner
// stands: phones tap a size chip instead of opening the size dropdown, which stays
// in the form. Visitor Insights logs chip taps as clicks with the chip's text.
retailsProductSection(() => {
  const mark = (group, chip) => {
    group.querySelectorAll('.pb-size-chip').forEach((el) => {
      const on = el === chip;
      el.classList.toggle('is-selected', on);
      el.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  };

  document.querySelectorAll('.pb-size-chips').forEach((group) => {
    const form = group.closest('form');
    const box = (dim) => form && form.querySelector('input[name="' + dim + '-in"]');
    if (!box('width') || !box('height')) return;
    const fill = (dim, value) => {
      const input = box(dim);
      input.value = value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    group.addEventListener('click', (e) => {
      const chip = e.target.closest('.pb-size-chip');
      if (!chip) return;
      if (chip.dataset.custom) {
        mark(group, null);
        box('width').focus();
        box('width').select();
        return;
      }
      mark(group, chip);
      fill('width', chip.dataset.width);
      fill('height', chip.dataset.height);
    });
    const sync = () => {
      const width = parseFloat(box('width').value) || 0;
      const height = parseFloat(box('height').value) || 0;
      const match = [...group.querySelectorAll('.pb-size-chip[data-width]')].find(
        (el) => parseFloat(el.dataset.width) === width && parseFloat(el.dataset.height) === height
      );
      mark(group, match || null);
    };
    form.addEventListener('input', (e) => { if (e.target === box('width') || e.target === box('height')) sync(); });
  });

  document.querySelectorAll('.pb-option-chips').forEach((group) => {
    const select = document.getElementById(group.dataset.for);
    if (!select) return;
    group.addEventListener('click', (e) => {
      const chip = e.target.closest('.pb-size-chip');
      if (!chip) return;
      mark(group, chip);
      select.value = chip.dataset.value;
      // js/main.js prices options on the select's change (jQuery handlers).
      if (window.jQuery) window.jQuery(select).trigger('change');
      else select.dispatchEvent(new Event('change', { bubbles: true }));
    });
    const sync = () => mark(group, group.querySelector('.pb-size-chip[data-value="' + CSS.escape(select.value) + '"]'));
    select.addEventListener('change', sync);
    sync();
  });
});

// ---- 6. Delivery estimate and production-time choices (inc/product-buy-box.php) ----
// The same rules as checkout (inc/payments.php retails_estimated_ship_datetime()):
// Pacific time, weekdays only, orders after the 4pm cut-off (or on a weekend) count from
// the next business day, then the product's production days. Worked out here because the
// page may come from the page cache, hours old. Holidays aren't known to checkout either.
// Choices mirrored from B2Sign (data-time-gate etc., B2Sign Sync 1.9.1) follow B2Sign's own rules:
// a Same Day choice is open on business days before its time (some again from 4pm) and all day on
// days off; it is closed when both sides of the size are over 120". Closed choices are greyed out,
// as on B2Sign; choices the quantity rules out are hidden. The cart checks all of it again.
retailsProductSection((signal) => {
  const box = document.querySelector('.product-pricing-box .pd-eta');
  const choices = Array.from(document.querySelectorAll('[data-turnaround-option]'));
  // Products without mirrored choices: the site's own Next Day / Same Day (12pm) markup.
  const legacySameDay = choices.length ? null : document.querySelector('[data-same-day-option]');
  if (!box && !choices.length && !legacySameDay) return;

  let clock;
  try {
    clock = new Intl.DateTimeFormat('en-US', { timeZone: 'America/Los_Angeles', year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', hourCycle: 'h23' });
  } catch (e) {
    return; // No time zone support: show nothing rather than a wrong date.
  }
  const DAY = 86400000;
  const short = new Intl.DateTimeFormat('en-US', { timeZone: 'UTC', weekday: 'short', month: 'short', day: 'numeric' });
  const monthDay = new Intl.DateTimeFormat('en-US', { timeZone: 'UTC', month: 'short', day: 'numeric' });
  const dayOnly = new Intl.DateTimeFormat('en-US', { timeZone: 'UTC', day: 'numeric' });

  // Pacific wall clock; dates are UTC midnights so day math ignores the visitor's zone.
  const now = () => {
    const p = {};
    clock.formatToParts(new Date()).forEach((part) => { p[part.type] = part.value; });
    const date = Date.UTC(+p.year, +p.month - 1, +p.day);
    return { date, minutes: (+p.hour % 24) * 60 + +p.minute, weekday: new Date(date).getUTCDay() };
  };
  const isWeekend = (date) => [0, 6].includes(new Date(date).getUTCDay());
  const addBusinessDays = (date, days) => {
    let d = date;
    while (days > 0) {
      d += DAY;
      if (!isWeekend(d)) days--;
    }
    return d;
  };
  const left = (minutes) => {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h ? h + ' hr' + (m ? ' ' + m + ' min' : '') : m + ' min';
  };
  const strong = (text) => {
    const el = document.createElement('strong');
    el.textContent = text;
    return el;
  };
  const range = (from, to) => {
    if (from === to) return short.format(from);
    const sameMonth = new Date(from).getUTCMonth() === new Date(to).getUTCMonth();
    return monthDay.format(from) + ' – ' + (sameMonth ? dayOnly.format(to) : monthDay.format(to));
  };
  const hm = (text) => (/^\d\d:\d\d$/.test(text || '') ? +text.slice(0, 2) * 60 + +text.slice(3) : null);

  const form = (box || choices[0] || legacySameDay).closest('form');
  const noun = form && form.dataset.noun && form.dataset.noun !== 'product' ? 'your ' + form.dataset.noun : 'it';
  const qty = document.getElementById('productQuantity');
  const radio = (el) => el.querySelector('input[type="radio"]');
  const inches = (name) => {
    const input = form ? form.querySelector('input[name="' + name + '"]') : null;
    return input ? parseFloat(input.value) || 0 : 0;
  };
  const typedSize = () => {
    const w = inches('width-in');
    const h = inches('height-in');
    return w > 0 && h > 0 ? [w, h] : null;
  };
  const choose = (el) => {
    const r = el && radio(el);
    if (r && !r.checked) {
      r.checked = true;
      // js/main.js reacts to this change (jQuery handler) and asks for a new price.
      r.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };

  // Which mirrored choices are open (clock, size) and allowed (quantity) right now.
  const refreshChoices = (t) => {
    const weekday = t.weekday > 0 && t.weekday < 6;
    const n = qty ? Math.max(1, parseInt(qty.value, 10) || 1) : 1;
    const size = typedSize();
    choices.forEach((el) => {
      const min = parseInt(el.dataset.minQty, 10) || 1;
      const max = el.dataset.maxQty ? parseInt(el.dataset.maxQty, 10) : Infinity;
      el.hidden = n < min || n > max;
      const gate = hm(el.dataset.timeGate);
      const reopen = hm(el.dataset.reopenAt);
      let open = true;
      if (gate !== null) open = weekday ? (t.minutes < gate || (reopen !== null && t.minutes >= reopen)) : el.dataset.openOffDays === '1';
      const maxBoth = parseFloat(el.dataset.maxBoth);
      if (open && maxBoth > 0 && size && Math.min(size[0], size[1]) > maxBoth) open = false;
      el.classList.toggle('is-closed', !open);
      const r = radio(el);
      if (r) r.disabled = !open;
    });
    document.querySelectorAll('[data-turnaround-section]').forEach((section) => {
      section.hidden = !Array.from(section.querySelectorAll('[data-turnaround-option]')).some((el) => !el.hidden);
    });
    const usable = (el) => !el.hidden && !el.classList.contains('is-closed');
    const checked = choices.find((el) => radio(el) && radio(el).checked);
    if (!checked || !usable(checked)) {
      choose(choices.find((el) => el.hasAttribute('data-default') && usable(el)) || choices.find(usable));
    }
  };

  const update = () => {
    const t = now();
    const weekday = t.weekday > 0 && t.weekday < 6;

    let rush = false;
    let rushCut = 12 * 60;
    if (choices.length) {
      refreshChoices(t);
      const checked = choices.find((el) => radio(el) && radio(el).checked);
      const gate = checked ? hm(checked.dataset.timeGate) : null;
      if (checked && checked.hasAttribute('data-same-day-option') && !checked.classList.contains('is-closed') && weekday && gate !== null && t.minutes < gate) {
        rush = true;
        rushCut = gate;
      }
    } else if (legacySameDay) {
      // The site's own Same Day: only until 12pm on a weekday (the cart refuses it after noon too).
      const nextDayRadio = document.getElementById('turnaroundNextDay');
      const sameDayRadio = legacySameDay.querySelector('input[type="radio"]');
      const sameDayOpen = weekday && t.minutes < 12 * 60;
      legacySameDay.hidden = !sameDayOpen;
      if (!sameDayOpen && sameDayRadio && sameDayRadio.checked && nextDayRadio) {
        nextDayRadio.checked = true;
        nextDayRadio.dispatchEvent(new Event('change', { bubbles: true }));
      }
      rush = Boolean(sameDayRadio && sameDayRadio.checked && sameDayOpen);
      rushCut = (parseInt(box && box.dataset.sameDayCutoff, 10) || 12) * 60;
    }
    if (!box) return;

    const cutoff = (parseInt(box.dataset.cutoff, 10) || 16) * 60;
    const production = Math.max(1, parseInt(box.dataset.productionDays, 10) || 1);
    const fastestDays = parseInt(box.dataset.fastestDays, 10) || 0;

    let ship;
    let minutesLeft = 0;
    if (rush) {
      ship = t.date;
      minutesLeft = rushCut - t.minutes;
    } else {
      let orderDay = t.date;
      if (!weekday || t.minutes >= cutoff) orderDay = addBusinessDays(orderDay, 1);
      else minutesLeft = cutoff - t.minutes;
      ship = addBusinessDays(orderDay, production);
    }
    const shipText = ship === t.date ? 'today' : ship === t.date + DAY ? 'tomorrow, ' + short.format(ship) : short.format(ship);

    const shipLine = box.querySelector('.pd-eta__ship');
    shipLine.textContent = '';
    if (minutesLeft > 0) {
      shipLine.append('Order within ', strong(left(minutesLeft)), ' and ' + noun + ' ships ', strong(shipText));
    } else {
      shipLine.append('Order now and ' + noun + ' ships ', strong(shipText));
    }

    // Standard shipping is 3-6 business days (checkout quotes the slow end).
    const arriveLine = box.querySelector('.pd-eta__arrive');
    arriveLine.textContent = '';
    arriveLine.append('Arrives ', strong(range(addBusinessDays(ship, 3), addBusinessDays(ship, 6))), ' with standard shipping');
    if (fastestDays > 0 && fastestDays < 3) {
      arriveLine.append(', or ', strong(short.format(addBusinessDays(ship, fastestDays))), ' with ' + (box.dataset.fastestName || 'faster') + ' shipping');
    }
    arriveLine.append('.');
    box.hidden = false;
  };

  update();
  const tick = window.setInterval(update, 30000);
  signal.addEventListener('abort', () => window.clearInterval(tick));
  document.addEventListener('visibilitychange', () => { if (!document.hidden) update(); }, { signal });
  document.addEventListener('change', (e) => { if (e.target && e.target.name === 'turnaround_option') update(); }, { signal });
  if (qty) {
    qty.addEventListener('input', update);
    qty.addEventListener('change', update);
  }
  // The size boxes fill hidden width-in / height-in inputs (js/main.js): check after they are updated.
  if (form && choices.some((el) => el.dataset.maxBoth)) {
    form.addEventListener('input', () => setTimeout(update, 0));
    form.addEventListener('change', () => setTimeout(update, 0));
  }
});

// ---- 7. Quantity - / + buttons (inc/product-buy-box.php) ----
// They change the number box and send it a change event, which js/main.js prices
// (its jQuery handler and the server quote both listen for it).
retailsProductSection(() => {
  const input = document.getElementById('productQuantity');
  const box = input && input.closest('.pd-qty__box');
  if (!box) return;
  const steps = box.querySelectorAll('[data-qty-step]');
  const clamp = (value) => Math.min(1000, Math.max(1, parseInt(value, 10) || 1));
  const sync = () => {
    const value = clamp(input.value);
    steps.forEach((button) => {
      const step = parseInt(button.dataset.qtyStep, 10);
      button.disabled = (step < 0 && value <= 1) || (step > 0 && value >= 1000);
    });
  };
  box.addEventListener('click', (e) => {
    const button = e.target.closest('[data-qty-step]');
    if (!button || button.disabled) return;
    input.value = clamp(clamp(input.value) + parseInt(button.dataset.qtyStep, 10));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
  input.addEventListener('input', sync);
  input.addEventListener('change', sync);
  sync();
});

// ---- 8. The whole "Starting at ... Get your price" box is a tap target (single-product.php) ----
// Visitors tapped the price, not the small "Get your price" link beside it, and nothing
// happened (Visitor Insights, Nov 8 2026: rage clicks on "STARTING AT $176.09 per letter
// Get your price"). A tap anywhere on the box now scrolls to the options; on channel letters
// the cursor goes into the sign text box, the one thing to fill in before a price shows.
retailsProductSection(() => {
  document.querySelectorAll('.product-mobile-price').forEach((box) => {
    const link = box.querySelector('a[href^="#"]');
    const target = link && document.getElementById(link.getAttribute('href').slice(1));
    if (!target) return;
    box.classList.add('is-tappable');
    box.addEventListener('click', (e) => {
      const clicked = e.target.closest('a');
      if (clicked && clicked !== link) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      const text = target.querySelector('#letterInput');
      if (text && !text.value) window.setTimeout(() => text.focus({ preventScroll: true }), 500);
    });
  });
});

// ---- 9. Contact page: a tap on the form heading starts the form (page-contact.php) ----
// One visitor tapped "Get your free quote" three times, as if it were a button (Nov 8 2026).
(function () {
  const heading = document.querySelector('.contact-card-heading');
  const name = document.getElementById('contact-name');
  if (!heading || !name) return;
  heading.classList.add('is-tappable');
  heading.addEventListener('click', () => {
    if (!name.value) name.focus();
  });
})();

// ---- 10. Home: a tap on the big headline goes to the products (template-parts/home-catalog.php) ----
// Three visitors tapped "Custom Banners, Displays & Signs for Your Business" again and
// again, as if it were a link (Visitor Insights, Nov 8-14 2026). It now scrolls to the
// first product section below the hero.
(function () {
  const title = document.getElementById('home-hero-title');
  const target = document.getElementById('banners-displays') || document.getElementById('shop-by-category');
  if (!title || !target) return;
  title.classList.add('is-tappable');
  title.addEventListener('click', () => {
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
})();

// ---- 11. Cards under the product photos (inc/product-hero.php retails_render_product_aside) ----
// 768px and up. The cards fill the space between the photos and the bottom of the options,
// most useful first (data-aside-priority), skipping any that would run past the options, so
// the hero never grows and nothing below it moves. The aside stays collapsed until this runs.
retailsProductSection((signal) => {
  const aside = document.querySelector('.product-details .pd-aside');
  const column = aside && aside.parentElement;
  const options = document.getElementById('product-options');
  const heroTop = document.querySelector('.product-details .pd-hero__top');
  if (!aside || !column || !options || !heroTop) return;
  const cards = Array.from(aside.querySelectorAll('[data-aside-card]')).sort(
    (a, b) => (Number(a.dataset.asidePriority) || 99) - (Number(b.dataset.asidePriority) || 99)
  );
  const wide = window.matchMedia('(min-width: 768px)');
  let frame = 0;
  const fit = () => {
    frame = 0;
    if (!wide.matches) return;
    cards.forEach((card) => card.classList.remove('is-cut'));
    aside.classList.remove('is-empty');
    // The column can be sticky (section 3), so the aside is measured from the column's own
    // top; the column and the title block both start at the top of the hero grid.
    const room =
      options.getBoundingClientRect().bottom -
      heroTop.getBoundingClientRect().top -
      (aside.getBoundingClientRect().top - column.getBoundingClientRect().top);
    const gap = parseFloat(getComputedStyle(aside).rowGap) || 0;
    let used = 0;
    let shown = 0;
    const kept = new Set();
    cards.forEach((card) => {
      const need = card.offsetHeight + (shown ? gap : 0);
      // More reviews only under the first one, which carries the Google rating.
      const requires = card.dataset.asideRequires;
      if (used + need <= room && (!requires || kept.has(requires))) {
        used += need;
        shown += 1;
        kept.add(card.dataset.asidePriority);
      } else {
        card.classList.add('is-cut');
      }
    });
    aside.classList.toggle('is-empty', !shown);
    aside.classList.add('is-fit');
  };
  const schedule = () => {
    if (!frame) frame = window.requestAnimationFrame(fit);
  };
  schedule();
  window.addEventListener('resize', schedule, { signal });
  window.addEventListener('load', schedule, { signal, once: true });
  if ('ResizeObserver' in window) {
    // The options grow as choices open and the wallet buttons load; the photos, title and
    // highlights move the top of the aside or the options; fonts change the cards' heights.
    const watch = new ResizeObserver(schedule);
    [options, heroTop, document.querySelector('.product-details .pd-hero__info')]
      .concat(Array.from(column.children).filter((el) => el !== aside), cards)
      .forEach((el) => el && watch.observe(el));
    signal.addEventListener('abort', () => {
      watch.disconnect();
      if (frame) window.cancelAnimationFrame(frame);
    });
  }
});
