/**
 * Artwork size check on product pages (server: inc/artwork-size-check.php).
 *
 * As soon as an artwork file finishes uploading (js/async-upload.js), and again when the
 * size changes, the server compares the file's shape with the ordered size. A file that
 * doesn't match says why, and Add To Cart / Buy Now stay blocked until the size or the
 * file is fixed. utils/Cart.php checks again, so this is the early warning, not the guard.
 */
(function () {
  "use strict";

  var config = window.wholesaleAsyncUpload || {};
  if (!config.ajaxUrl || !window.FormData || !window.fetch || !window.WeakMap) return;

  var states = new WeakMap(); // artwork input => { token, key, result, pending, box }
  var SKIP = { total_cost: 1, total_sqft: 1, turnaround_cost: 1, job_name: 1 };

  function artworkInput(form) {
    return form && form.querySelector ? form.querySelector('input[type="file"][data-async-upload="artwork"]') : null;
  }

  // The form's text fields, which carry the size the customer is ordering.
  function formFields(form) {
    var fields = [];
    new FormData(form).forEach(function (value, key) {
      if (typeof value === "string" && !SKIP[key]) fields.push([key, value]);
    });
    return fields;
  }

  function messageBox(input, state) {
    if (state.box && state.box.isConnected) return state.box;
    var box = document.createElement("div");
    box.className = "artwork-size-status";
    box.setAttribute("aria-live", "polite");
    var anchor = input.parentNode.querySelector(".async-upload-status") || input;
    anchor.insertAdjacentElement("afterend", box);
    state.box = box;
    return box;
  }

  function show(input, state, result) {
    var box = messageBox(input, state);
    var bad = Boolean(result && result.checked && !result.ok);
    input.classList.toggle("is-invalid", bad);
    // js/async-upload.js's "Uploaded ✓" would contradict the size message.
    var uploaded = input.parentNode.querySelector(".async-upload-status");
    if (uploaded && (bad || uploaded.textContent === "")) {
      uploaded.textContent = bad ? "" : "Uploaded ✓";
      uploaded.className = bad ? "async-upload-status" : "async-upload-status is-done";
    }
    box.textContent = "";
    box.className = "artwork-size-status";
    if (!result || !result.checked) return;
    if (!bad) {
      box.className = "artwork-size-status is-done";
      box.textContent = "✓ Artwork matches your size (" + result.ordered + ").";
      return;
    }
    box.className = "artwork-size-status is-error";
    box.setAttribute("role", "alert");
    var text = document.createElement("span");
    text.textContent = result.message;
    box.appendChild(text);
    var remove = document.createElement("button");
    remove.type = "button";
    remove.className = "artwork-size-remove";
    remove.textContent = "Remove this file";
    remove.addEventListener("click", function () {
      input.value = "";
      // js/async-upload.js drops the token; "async-upload:cleared" then clears this box.
      input.dispatchEvent(new Event("async-upload:reset"));
      input.focus();
    });
    box.appendChild(remove);
  }

  function clear(input) {
    var state = states.get(input);
    if (!state) return;
    state.token = "";
    state.key = "";
    state.result = null;
    state.pending = null;
    input.classList.remove("is-invalid");
    if (state.box) {
      state.box.textContent = "";
      state.box.className = "artwork-size-status";
      state.box.removeAttribute("role");
    }
  }

  // Resolves to true when the artwork may go with the form (or there is nothing to check).
  function check(input) {
    var state = states.get(input);
    var form = input.form;
    if (!state || !state.token || !form) return Promise.resolve(true);
    var fields = formFields(form);
    var key = state.token + "|" + JSON.stringify(fields);
    if (state.result && state.key === key) return Promise.resolve(state.result.ok !== false);
    if (state.pending && state.pendingKey === key) return state.pending;

    var data = new FormData();
    fields.forEach(function (pair) { data.append(pair[0], pair[1]); });
    data.set("action", "wholesale_artwork_check");
    data.set("token", state.token);

    var request = fetch(config.ajaxUrl, { method: "POST", body: data, credentials: "same-origin" })
      .then(function (response) { return response.json(); })
      .then(function (json) {
        var result = json && json.success && json.data ? json.data : { checked: false, ok: true };
        if (state.pending === request) {
          state.key = key;
          state.result = result;
          show(input, state, result);
        }
        return result.ok !== false;
      })
      .catch(function () {
        return true; // Couldn't ask: utils/Cart.php still checks.
      })
      .then(function (ok) {
        if (state.pending === request) state.pending = null;
        return ok;
      });
    state.pending = request;
    state.pendingKey = key;
    return request;
  }

  document.addEventListener("async-upload:done", function (e) {
    var input = e.target;
    if (!input || input.getAttribute("data-async-upload") !== "artwork") return;
    var state = states.get(input) || {};
    states.set(input, state);
    state.token = (e.detail && e.detail.token) || "";
    state.key = "";
    state.result = null;
    check(input);
  });

  document.addEventListener("async-upload:cleared", function (e) {
    if (e.target && e.target.getAttribute && e.target.getAttribute("data-async-upload") === "artwork") clear(e.target);
  });

  // A new size (typed, a preset, a size dropdown): ask again shortly after.
  var timer = null;
  function onFormEdit(e) {
    var target = e.target;
    if (!target || !target.form || target.type === "file") return;
    var input = artworkInput(target.form);
    var state = input && states.get(input);
    if (!state || !state.token) return;
    clearTimeout(timer);
    timer = setTimeout(function () { check(input); }, 400);
  }
  document.addEventListener("change", onFormEdit, true);
  document.addEventListener("input", onFormEdit, true);

  // Add To Cart / Buy Now: hold the submit until the answer for the current size is in,
  // and stop it when the artwork doesn't match. Capture phase, before js/mini-cart.js.
  document.addEventListener("submit", function (e) {
    var form = e.target;
    var input = artworkInput(form);
    var state = input && states.get(input);
    if (!state || !state.token) return;
    if (form.dataset.artworkChecked === "1") {
      delete form.dataset.artworkChecked;
      return;
    }
    var fields = formFields(form);
    var key = state.token + "|" + JSON.stringify(fields);
    if (state.result && state.key === key) {
      if (state.result.ok !== false) return;
      e.preventDefault();
      e.stopImmediatePropagation();
      show(input, state, state.result);
      if (state.box && state.box.scrollIntoView) state.box.scrollIntoView({ behavior: "smooth", block: "center" });
      return;
    }
    // Not checked for this size yet: wait for the answer, then send the form again.
    e.preventDefault();
    e.stopImmediatePropagation();
    var submitter = e.submitter || null;
    check(input).then(function (ok) {
      if (!ok) {
        if (state.box && state.box.scrollIntoView) state.box.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }
      form.dataset.artworkChecked = "1";
      if (submitter && submitter.isConnected) submitter.click();
      else if (form.requestSubmit) form.requestSubmit();
      else form.submit();
      // If another check stopped that submit, the next one is checked again.
      setTimeout(function () { delete form.dataset.artworkChecked; }, 0);
    });
  }, true);
})();
