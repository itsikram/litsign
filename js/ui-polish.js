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
 *    - Phones: a bar fixed to the bottom of the screen shows the live Grand Total and
 *      an Add To Cart button while the real pricing box is off screen. Its button
 *      clicks the real one, so the form's own checks (sign text, size, required
 *      fields) run.
 *    - Channel letters: before the sign text is typed, the Grand Total shows the
 *      starting price instead of $0.00.
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
    '<button type="button" class="pd-buy-bar__button"></button>';
  const barLabel = bar.querySelector('.pd-buy-bar__label');
  const barTotal = bar.querySelector('.pd-buy-bar__total');
  const barButton = bar.querySelector('.pd-buy-bar__button');
  document.body.appendChild(bar);

  const render = () => {
    const total = parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0;
    const outOfStock = addButton.type !== 'submit';
    // No price yet: channel letters before the sign text is typed, or after it is
    // cleared (js/main.js then shows its "Type your sign text" hint but can leave an
    // old total behind). The Grand Total row shows the starting price instead.
    const noPrice = total <= 0 || Boolean(pricingBox.querySelector('.price-quote-message.is-hint:not([hidden])'));
    if (totalFrom) pricingBox.classList.toggle('pd-total-empty', noPrice);
    if (!noPrice || !fromText) {
      barLabel.textContent = 'Grand Total';
      barTotal.textContent = '$' + totalEl.textContent.trim();
      barButton.textContent = addButton.textContent.trim();
    } else {
      // The bar button takes the visitor to the text box (the real button's own handler).
      barLabel.textContent = 'Starting at';
      barTotal.textContent = fromText;
      barButton.textContent = 'Get your price';
    }
    // The real button runs the mini cart request (js: .is-adding while it runs).
    const adding = addButton.classList.contains('is-adding');
    if (adding) barButton.textContent = 'Adding…';
    barButton.disabled = outOfStock || adding || addButton.disabled;
    bar.classList.toggle('is-updating', pricingBox.classList.contains('is-updating'));
  };
  render();
  new MutationObserver(render).observe(totalEl, { childList: true, characterData: true, subtree: true });
  new MutationObserver(render).observe(addButton, { attributes: true, attributeFilter: ['disabled', 'class'] });
  // The box's own class (.is-updating) and the hint message inside it (hidden / .is-hint).
  new MutationObserver(render).observe(pricingBox, { attributes: true, attributeFilter: ['class', 'hidden'], subtree: true });

  barButton.addEventListener('click', () => addButton.click());

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
