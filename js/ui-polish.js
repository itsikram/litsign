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
 *    - Phones: a bar fixed to the bottom of the screen shows the live Grand Total, a
 *      cart button and Buy Now while the real pricing box is off screen. Its buttons
 *      click the real ones, so the form's own checks (sign text, size, required
 *      fields) run.
 *    - Channel letters: before the sign text is typed, the Grand Total shows the
 *      starting price instead of $0.00.
 * 4. Delivered price. 5. One-tap size chips.
 * 6. "Order within ... and it ships ..." delivery estimate, and the Same Day option
 *    shown only while it can still be ordered (inc/product-buy-box.php).
 * 7. Quantity - / + buttons.
 * 8. The whole "Starting at ... Get your price" box scrolls to the options.
 * 9. Contact page: a tap on the form heading puts the cursor in the name box.
 */

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
(function () {
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
  const fitColumn = () => {
    if (!column) return;
    const header = visibleHeader();
    let top = 16;
    if (header && getComputedStyle(header).position === 'sticky') {
      top += Math.max(0, (parseFloat(getComputedStyle(header).top) || 0) + header.offsetHeight);
    }
    column.style.setProperty('--pd-sticky-top', top + 'px');
    column.classList.toggle('pd-col-sticky', desktop.matches && column.offsetHeight + top + 16 <= window.innerHeight);
  };
  if (column) {
    fitColumn();
    window.addEventListener('resize', fitColumn);
    if ('ResizeObserver' in window) new ResizeObserver(fitColumn).observe(column);
  }

  const pricingBox = form.querySelector('.product-pricing-box');
  const totalEl = form.querySelector('.price-total');
  if (!pricingBox || !totalEl || !('IntersectionObserver' in window)) return;

  // Buy bar (phones).
  const phone = window.matchMedia('(max-width: 767.98px)');
  const pill = document.querySelector('.product-details .product-mobile-price');
  // "Starting at $133.20 per letter" from the price line under the title.
  const fromText = pill && pill.firstElementChild
    ? pill.firstElementChild.textContent.replace(/\s+/g, ' ').replace(/^\s*Starting at\s*/i, '').trim()
    : '';
  // Channel letters: the starting price printed in the Grand Total row (single-product.php).
  const totalFrom = pricingBox.querySelector('.price-total-from');

  const bar = document.createElement('div');
  bar.className = 'pd-buy-bar';
  bar.hidden = true;
  bar.setAttribute('role', 'region');
  bar.setAttribute('aria-label', 'Order total');
  bar.innerHTML =
    '<p class="pd-buy-bar__price"><span class="pd-buy-bar__label"></span><strong class="pd-buy-bar__total"></strong></p>' +
    '<div class="pd-buy-bar__actions">' +
    (buyButton
      ? '<button type="button" class="pd-buy-bar__cart" aria-label="Add to cart"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.76L20 8H6.2M9.5 19.5a1 1 0 1 0 0-.01M17 19.5a1 1 0 1 0 0-.01M15 4v4M13 6h4"/></svg></button>'
      : '') +
    '<button type="button" class="pd-buy-bar__button"></button></div>';
  const barLabel = bar.querySelector('.pd-buy-bar__label');
  const barTotal = bar.querySelector('.pd-buy-bar__total');
  const barButton = bar.querySelector('.pd-buy-bar__button');
  const barCart = bar.querySelector('.pd-buy-bar__cart');
  // The bar's main button is Buy Now when there is one, else Add To Cart.
  const mainButton = buyButton || addButton;
  if (buyButton) bar.classList.add('pd-buy-bar--buy');
  document.body.appendChild(bar);

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
      barTotal.textContent = '$' + totalEl.textContent.trim();
      barButton.textContent = mainButton.textContent.trim();
    } else {
      // The bar button takes the visitor to the text box (the real button's own handler).
      barLabel.textContent = 'Starting at';
      barTotal.textContent = fromText;
      barButton.textContent = 'Get your price';
    }
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
  new MutationObserver(render).observe(totalEl, { childList: true, characterData: true, subtree: true });
  new MutationObserver(render).observe(addButton, { attributes: true, attributeFilter: ['disabled', 'class'] });
  if (buyButton) new MutationObserver(render).observe(buyButton, { attributes: true, attributeFilter: ['disabled', 'class'] });
  // The box's own class (.is-updating) and the hint message inside it (hidden / .is-hint).
  new MutationObserver(render).observe(pricingBox, { attributes: true, attributeFilter: ['class', 'hidden'], subtree: true });

  barButton.addEventListener('click', () => mainButton.click());
  if (barCart) barCart.addEventListener('click', () => addButton.click());

  // The chat launcher (sfs-live-chat, Shadow DOM) is placed by its --y offset;
  // lift it above the bar while the bar shows.
  const liftChat = (on) => {
    const chat = document.getElementById('sfs-chat-root');
    if (!chat) return;
    if (on) {
      const base = window.SFSChatConfig && window.SFSChatConfig.brand ? Number(window.SFSChatConfig.brand.offsetY) || 0 : 20;
      chat.style.setProperty('--y', (base + bar.offsetHeight) + 'px');
    } else {
      chat.style.removeProperty('--y');
    }
  };

  // Shown while neither the price line under the title nor the pricing box is on screen.
  const inView = new Map();
  const update = () => {
    const show = phone.matches && !inView.get(pricingBox) && !(pill && inView.get(pill)) && window.scrollY > 120;
    if (show === !bar.hidden) return;
    bar.hidden = !show;
    document.body.classList.toggle('has-pd-buy-bar', show);
    liftChat(show);
  };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => inView.set(entry.target, entry.isIntersecting));
    update();
  });
  observer.observe(pricingBox);
  if (pill) observer.observe(pill);
  window.addEventListener('scroll', update, { passive: true });
  phone.addEventListener('change', update);
})();

// ---- 4. Delivered price under the Grand Total and in the phone buy bar ----
// Price plus standard shipping below the free shipping minimum, so the price an
// ad visitor sees is the price they pay before tax (single-product.php .pd-delivered).
(function () {
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
  new MutationObserver(render).observe(totalEl, { childList: true, characterData: true, subtree: true });
  const box = totalEl.closest('.product-pricing-box');
  if (box) new MutationObserver(render).observe(box, { attributes: true, attributeFilter: ['class'] });
  // The buy bar is built by section 3 after this script's first run on some pages.
  window.addEventListener('load', render);
})();

// ---- 5. One-tap size chips (inc/size-chips.php) ----
// A size chip fills width and height and prices them (js/main.js prices on the boxes'
// input/change events); typing a size selects the matching chip, or none. Banner
// stands: phones tap a size chip instead of opening the size dropdown, which stays
// in the form. Visitor Insights logs chip taps as clicks with the chip's text.
(function () {
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
})();

// ---- 6. Delivery estimate and production-time choices (inc/product-buy-box.php) ----
// The same rules as checkout (inc/payments.php wholesale_estimated_ship_datetime()):
// Pacific time, weekdays only, orders after the 4pm cut-off (or on a weekend) count from
// the next business day, then the product's production days. Worked out here because the
// page may come from the page cache, hours old. Holidays aren't known to checkout either.
// Choices mirrored from B2Sign (data-time-gate etc., B2Sign Sync 1.9.1) follow B2Sign's own rules:
// a Same Day choice is open on business days before its time (some again from 4pm) and all day on
// days off; it is closed when both sides of the size are over 120". Closed choices are greyed out,
// as on B2Sign; choices the quantity rules out are hidden. The cart checks all of it again.
(function () {
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
  window.setInterval(update, 30000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) update(); });
  document.addEventListener('change', (e) => { if (e.target && e.target.name === 'turnaround_option') update(); });
  if (qty) {
    qty.addEventListener('input', update);
    qty.addEventListener('change', update);
  }
  // The size boxes fill hidden width-in / height-in inputs (js/main.js): check after they are updated.
  if (form && choices.some((el) => el.dataset.maxBoth)) {
    form.addEventListener('input', () => setTimeout(update, 0));
    form.addEventListener('change', () => setTimeout(update, 0));
  }
})();

// ---- 7. Quantity - / + buttons (inc/product-buy-box.php) ----
// They change the number box and send it a change event, which js/main.js prices
// (its jQuery handler and the server quote both listen for it).
(function () {
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
})();

// ---- 8. The whole "Starting at ... Get your price" box is a tap target (single-product.php) ----
// Visitors tapped the price, not the small "Get your price" link beside it, and nothing
// happened (Visitor Insights, Nov 8 2026: rage clicks on "STARTING AT $176.09 per letter
// Get your price"). A tap anywhere on the box now scrolls to the options; on channel letters
// the cursor goes into the sign text box, the one thing to fill in before a price shows.
(function () {
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
})();

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
