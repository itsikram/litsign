/**
 * Header cart drawer. Markup and endpoints: inc/mini-cart.php.
 *
 * The header cart icon opens the drawer instead of going to /cart/ (modifier-clicks still
 * open the cart page). The product page's Add To Cart posts over AJAX and opens the drawer
 * on success; if the request can't be made at all it falls back to the normal form post.
 */
(function () {
  "use strict";

  const config = window.wholesaleMiniCart || {};
  const root = document.querySelector("[data-mini-cart]");
  if (!root || !config.ajaxUrl || !window.fetch) return;

  const panel = root.querySelector(".mc-panel");
  const body = root.querySelector("[data-mc-body]");
  const foot = root.querySelector("[data-mc-foot]");
  const alertBox = root.querySelector("[data-mc-alert]");
  const toast = root.querySelector("[data-mc-toast]");
  const live = root.querySelector("[data-mc-live]");
  const titleCount = root.querySelector("[data-mc-count]");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  const icons = {
    success: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/><path fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="M7.5 12.5l3 3 6-6.5"/></svg>',
    warning: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 2 1 21h22L12 2zm1 15h-2v-2h2v2zm0-4h-2V9h2v4z"/></svg>',
    danger: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/><path stroke="#fff" stroke-width="2.4" stroke-linecap="round" d="M12 7v6M12 16.5v.5"/></svg>',
  };

  let nonce = "";
  let loaded = false;
  let lastFocus = null;
  let closeTimer = 0;
  let toastTimer = 0;
  let queue = Promise.resolve();

  /* ---------- Requests ---------- */

  // One request at a time, so quantity changes and removals land in order.
  const enqueue = (task) => {
    const run = queue.then(task, task);
    queue = run.catch(() => {});
    return run;
  };

  const post = async (action, fields, retried) => {
    const data = fields instanceof FormData ? fields : new FormData();
    if (!(fields instanceof FormData)) {
      Object.keys(fields || {}).forEach((key) => data.append(key, fields[key]));
    }
    data.set("action", action);
    if (nonce) data.set("nonce", nonce);

    const response = await fetch(config.ajaxUrl, { method: "POST", body: data, credentials: "same-origin" });
    let json;
    try {
      json = await response.json();
    } catch (e) {
      throw new Error("bad_response");
    }

    if (json && json.data && json.data.nonce) nonce = json.data.nonce;
    if (!json.success && json.data && json.data.code === "bad_nonce" && !retried) {
      return post(action, data, true);
    }
    return json;
  };

  /* ---------- Rendering ---------- */

  const announce = (text) => {
    live.textContent = "";
    window.setTimeout(() => { live.textContent = text; }, 60);
  };

  const showAlert = (type, text) => {
    if (!text) {
      alertBox.hidden = true;
      return;
    }
    alertBox.className = "mc-alert mc-alert--" + type;
    alertBox.innerHTML = icons[type] || "";
    const span = document.createElement("span");
    span.textContent = text;
    alertBox.appendChild(span);
    alertBox.hidden = false;
  };

  const updateBadges = (count) => {
    document.querySelectorAll("[data-cart-count]").forEach((el) => {
      const text = count > 99 ? "99+" : String(count);
      if (el.textContent !== text && count > 0 && !reduceMotion.matches) {
        el.classList.remove("is-bump");
        void el.offsetWidth;
        el.classList.add("is-bump");
      }
      el.textContent = text;
      el.hidden = count < 1;
    });
    document.querySelectorAll("[data-cart-link]").forEach((el) => {
      el.setAttribute("aria-label", count > 0 ? `Cart, ${count} ${count === 1 ? "item" : "items"}` : "Cart");
    });
    titleCount.textContent = count > 0 ? String(count) : "";
  };

  const render = (data) => {
    body.innerHTML = data.body;
    foot.innerHTML = data.footer;
    body.removeAttribute("aria-busy");
    updateBadges(parseInt(data.count, 10) || 0);
    loaded = true;
  };

  const lineOf = (el) => el.closest("[data-mc-line]");

  // Re-rendering replaces the line; put focus back on the same control.
  const refocus = (cartId, selector) => {
    if (!cartId) return;
    const line = body.querySelector(`[data-mc-line="${CSS.escape(cartId)}"]`);
    const target = line && line.querySelector(selector);
    if (target && !target.disabled) target.focus();
    else if (line) (line.querySelector("[data-mc-qty]") || panel).focus();
    else panel.focus();
  };

  const fail = (message) => {
    showAlert("danger", message || "Something went wrong. Please try again, or call 866-436-2101.");
    body.querySelectorAll(".is-busy, .is-leaving").forEach((el) => el.classList.remove("is-busy", "is-leaving"));
  };

  /* ---------- Open / close ---------- */

  const focusables = () =>
    Array.from(panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'))
      .filter((el) => el.offsetParent !== null || el === document.activeElement);

  const refresh = () =>
    enqueue(async () => {
      try {
        const json = await post("wholesale_mini_cart");
        if (json.success) render(json.data);
        else fail();
      } catch (e) {
        fail();
      }
    });

  const open = (options = {}) => {
    window.clearTimeout(closeTimer);
    if (!root.classList.contains("is-open")) {
      lastFocus = document.activeElement;
      root.hidden = false;
      document.documentElement.classList.add("mc-locked");
      void root.offsetWidth;
      root.classList.add("is-open");
      document.dispatchEvent(new CustomEvent("wholesale:minicart-open"));
    }
    panel.focus({ preventScroll: true });
    if (!options.skipRefresh) refresh();
  };

  const close = () => {
    if (!root.classList.contains("is-open")) return;
    root.classList.remove("is-open");
    document.documentElement.classList.remove("mc-locked");
    hideToast();
    closeTimer = window.setTimeout(() => {
      root.hidden = true;
      showAlert("", "");
    }, reduceMotion.matches ? 0 : 330);
    if (lastFocus && typeof lastFocus.focus === "function") lastFocus.focus({ preventScroll: true });
  };

  /* ---------- Undo toast ---------- */

  function hideToast() {
    window.clearTimeout(toastTimer);
    toast.hidden = true;
    toast.innerHTML = "";
  }

  const showUndo = (title) => {
    hideToast();
    const text = document.createElement("span");
    text.textContent = `Removed ${title}`;
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = "Undo";
    button.addEventListener("click", () => {
      hideToast();
      enqueue(async () => {
        try {
          const json = await post("wholesale_mini_cart_restore");
          if (!json.success) return fail();
          render(json.data);
          announce(`${title} is back in your cart.`);
          panel.focus();
        } catch (e) {
          fail();
        }
      });
    });
    toast.append(text, button);
    toast.hidden = false;
    toastTimer = window.setTimeout(hideToast, 7000);
  };

  /* ---------- Cart actions ---------- */

  const setQuantity = (line, quantity, focusSelector) => {
    const cartId = line.getAttribute("data-mc-line");
    line.classList.add("is-busy");
    showAlert("", "");
    enqueue(async () => {
      try {
        const json = await post("wholesale_mini_cart_qty", { cart_id: cartId, quantity });
        if (!json.success) return fail(json.data && json.data.message);
        render(json.data);
        announce(`Quantity updated to ${quantity}. Subtotal ${json.data.subtotal}.`);
        refocus(cartId, focusSelector);
      } catch (e) {
        fail();
      }
    });
  };

  const removeLine = (line) => {
    const cartId = line.getAttribute("data-mc-line");
    line.classList.add(reduceMotion.matches ? "is-busy" : "is-leaving");
    showAlert("", "");
    enqueue(async () => {
      try {
        const json = await post("wholesale_mini_cart_remove", { cart_id: cartId });
        if (!json.success) return fail(json.data && json.data.message);
        render(json.data);
        panel.focus();
        if (json.data.removed) {
          showUndo(json.data.removed);
          announce(`${json.data.removed} removed from your cart.`);
        }
      } catch (e) {
        fail();
      }
    });
  };

  const clampQty = (value) => Math.min(1000, Math.max(1, parseInt(value, 10) || 1));

  // Steppers update the number straight away and send once the clicking stops.
  const stepTimers = new WeakMap();
  const stepQuantity = (button) => {
    const line = lineOf(button);
    const input = line.querySelector("[data-mc-qty]");
    const next = clampQty((parseInt(input.value, 10) || 1) + parseInt(button.getAttribute("data-mc-step"), 10));
    input.value = next;
    line.querySelectorAll("[data-mc-step]").forEach((b) => {
      const step = parseInt(b.getAttribute("data-mc-step"), 10);
      b.disabled = (step < 0 && next <= 1) || (step > 0 && next >= 1000);
    });
    window.clearTimeout(stepTimers.get(line));
    stepTimers.set(line, window.setTimeout(() => {
      if (String(next) !== input.getAttribute("data-mc-current")) {
        setQuantity(line, next, `[data-mc-step="${button.getAttribute("data-mc-step")}"]`);
      }
    }, 450));
  };

  const commitInput = (input) => {
    const line = lineOf(input);
    const quantity = clampQty(input.value);
    input.value = quantity;
    if (String(quantity) !== input.getAttribute("data-mc-current")) setQuantity(line, quantity, "[data-mc-qty]");
  };

  /* ---------- Events ---------- */

  document.addEventListener("click", (e) => {
    const link = e.target.closest("[data-cart-link]");
    if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    open();
  });

  root.addEventListener("click", (e) => {
    if (e.target.closest("[data-mc-close]")) {
      close();
      return;
    }
    if (e.target.closest("[data-mc-close-link]")) {
      close();
      return;
    }
    const step = e.target.closest("[data-mc-step]");
    if (step && !step.disabled) {
      stepQuantity(step);
      return;
    }
    const remove = e.target.closest("[data-mc-remove]");
    if (remove) removeLine(lineOf(remove));
  });

  root.addEventListener("change", (e) => {
    if (e.target.matches("[data-mc-qty]")) commitInput(e.target);
  });

  root.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      e.preventDefault();
      close();
      return;
    }
    if (e.key === "Enter" && e.target.matches("[data-mc-qty]")) {
      e.preventDefault();
      commitInput(e.target);
      return;
    }
    if (e.key !== "Tab") return;
    const items = focusables();
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });

  // Close the slide-in menu if it is open when the drawer opens.
  document.addEventListener("wholesale:minicart-open", () => {
    const menuClose = document.querySelector(".main-header.mobile-menu-open .mobile-menu-close");
    if (menuClose) menuClose.click();
  });

  // Warm the drawer while the pointer heads for the icon, so it opens with content.
  document.querySelectorAll("[data-cart-link]").forEach((link) => {
    const warm = () => {
      if (!loaded) refresh();
    };
    link.addEventListener("pointerenter", warm, { once: true });
    link.addEventListener("focus", warm, { once: true });
  });

  /* ---------- Product page: Add To Cart over AJAX ---------- */

  const isCartForm = (form) =>
    form && form.querySelector('input[name="product_id"]') && /\/cart\/?$/.test(new URL(form.action, location.href).pathname);

  // main.js applies the volume discount to #totalCost when Add To Cart is clicked. Remember
  // the price first so it can be put back and a second add isn't discounted twice.
  let savedTotal = null;
  let submitted = false;
  document.addEventListener("click", (e) => {
    const button = e.target.closest(".add-to-cart-btn");
    if (!button || !isCartForm(button.form)) return;
    const total = document.getElementById("totalCost");
    savedTotal = total ? total.value : null;
    submitted = false;
    window.setTimeout(() => {
      if (!submitted) restoreTotal();
    }, 0);
  }, true);

  const restoreTotal = () => {
    const total = document.getElementById("totalCost");
    if (total && savedTotal !== null) total.value = savedTotal;
    savedTotal = null;
  };

  // Listen on window so every other submit handler (validation etc.) has run first.
  window.addEventListener("submit", (e) => {
    const form = e.target;
    if (e.defaultPrevented || !isCartForm(form)) return;
    if (typeof form.checkValidity === "function" && !form.checkValidity()) return;
    submitted = true;
    e.preventDefault();

    const buttons = form.querySelectorAll(".add-to-cart-btn");
    buttons.forEach((b) => {
      b.classList.add("is-adding");
      b.setAttribute("aria-busy", "true");
    });
    const done = () => {
      buttons.forEach((b) => {
        b.classList.remove("is-adding");
        b.removeAttribute("aria-busy");
      });
      restoreTotal();
    };

    const data = new FormData(form);
    enqueue(async () => {
      let json;
      try {
        json = await post("wholesale_mini_cart_add", data);
      } catch (err) {
        // Couldn't talk to the server the AJAX way: use the regular form post.
        done();
        HTMLFormElement.prototype.submit.call(form);
        return;
      }
      done();
      showAddResult(json);

      const fileInput = form.querySelector('input[type="file"][name="custom-artwork"]');
      if (fileInput && json.success) fileInput.value = "";
    });
  });

  // The channel letter builder posts its own add (wholesale_cl_builder_add) and hands the
  // response here: document.dispatchEvent(new CustomEvent("wholesale:minicart-added", { detail: json })).
  document.addEventListener("wholesale:minicart-added", (e) => {
    if (e.detail) showAddResult(e.detail);
  });

  function showAddResult(json) {
    if (!json.success) {
      const message = (json.data && json.data.message) || "This item could not be added. Please check your options and try again.";
      open({ skipRefresh: loaded });
      showAlert("danger", message);
      announce(message);
      return;
    }

    render(json.data);
    open({ skipRefresh: true });
    const added = json.data.added;
    if (json.data.notice) showAlert("warning", json.data.notice);
    else showAlert("success", added ? `${added.title} was added to your cart.` : "Added to your cart.");
    announce(json.data.notice || (added ? `${added.title} added to your cart.` : "Added to your cart."));

    if (added) {
      const line = body.querySelector(`[data-mc-line="${CSS.escape(added.cart_id)}"]`);
      if (line) {
        line.classList.add("is-new");
        line.scrollIntoView({ block: "nearest", behavior: reduceMotion.matches ? "auto" : "smooth" });
      }
    }
  }
})();
