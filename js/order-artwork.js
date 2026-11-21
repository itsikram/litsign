/**
 * Artwork box under each order item (inc/order-artwork.php): upload or replace the file
 * with a progress readout, then swap in the box the server sends back.
 *
 * Order page: the box is a real form, so it still posts without JavaScript.
 * wp-admin order screen: the box sits inside the post form, so only this script uploads.
 */
(function () {
  "use strict";

  var config = window.retailsOrderArtwork || {};
  if (!config.ajaxUrl || !window.FormData || !window.XMLHttpRequest) return;

  function sizeText(bytes) {
    return (bytes / 1048576).toFixed(bytes < 10485760 ? 1 : 0).replace(/\.0$/, "") + " MB";
  }

  function parts(box) {
    return {
      input: box.querySelector(".order-artwork__file"),
      button: box.querySelector(".order-artwork__submit"),
      progress: box.querySelector(".order-artwork__progress"),
    };
  }

  function say(box, text, state) {
    var p = parts(box).progress;
    if (!p) return;
    p.hidden = !text;
    p.textContent = text || "";
    p.className = "order-artwork__progress" + (state ? " is-" + state : "");
  }

  function upload(box, force) {
    var el = parts(box);
    var file = el.input && el.input.files && el.input.files[0];
    if (!file) {
      say(box, "Please choose your artwork file.", "error");
      return;
    }
    var max = parseInt(box.getAttribute("data-max"), 10);
    if (max && file.size > max) {
      say(box, "This file is " + sizeText(file.size) + ". Artwork files must be under " + (config.maxText || sizeText(max)) + ". " + (config.largeHelp || ""), "error");
      return;
    }

    var stale = box.querySelector(".order-artwork__status");
    if (stale) stale.remove();
    var data = new FormData();
    data.append("action", "retails_order_artwork");
    data.append("order_id", box.getAttribute("data-order"));
    data.append("line", box.getAttribute("data-line"));
    data.append("cart_id", box.getAttribute("data-cart") || "");
    data.append("context", box.getAttribute("data-context") || "site");
    data.append("nonce", box.getAttribute("data-nonce"));
    if (force) data.append("force", "1");
    data.append("artwork", file);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", config.ajaxUrl);
    xhr.responseType = "json";
    box.classList.add("is-busy");
    if (el.button) el.button.disabled = true;
    if (el.input) el.input.disabled = true;
    say(box, "Uploading… 0%", "busy");
    xhr.upload.addEventListener("progress", function (e) {
      if (e.lengthComputable) say(box, e.loaded < e.total ? "Uploading… " + Math.round((e.loaded / e.total) * 100) + "%" : "Checking your file…", "busy");
    });

    function done() {
      box.classList.remove("is-busy");
      if (el.button) el.button.disabled = false;
      if (el.input) el.input.disabled = false;
    }

    xhr.onload = function () {
      var json = xhr.response;
      done();
      if (json && json.success && json.data && json.data.html) {
        var holder = document.createElement("div");
        holder.innerHTML = json.data.html.trim();
        var fresh = holder.firstElementChild;
        if (fresh) {
          box.replaceWith(fresh);
          var status = fresh.querySelector(".order-artwork__status");
          if (status) {
            status.setAttribute("tabindex", "-1");
            status.focus({ preventScroll: true });
          }
          // The order page's "no artwork yet" note goes once every item has its file.
          var alert = document.querySelector(".order-artwork-alert");
          if (alert && !document.querySelector(".order-artwork.is-missing .order-artwork__form")) alert.remove();
          fresh.dispatchEvent(new CustomEvent("order-artwork:saved", { bubbles: true, detail: json.data }));
        }
        return;
      }
      var message = (json && json.data && json.data.message) || (xhr.status === 413 ? "This file is too large to upload. " + (config.largeHelp || "") : "Your artwork could not be uploaded. Please try again.");
      say(box, message, "error");
      if (json && json.data && json.data.can_force) {
        var again = document.createElement("button");
        again.type = "button";
        again.className = "button-link order-artwork__force";
        again.textContent = "Save it anyway";
        again.addEventListener("click", function () { upload(box, true); });
        parts(box).progress.appendChild(document.createTextNode(" "));
        parts(box).progress.appendChild(again);
      }
    };
    xhr.onerror = function () {
      done();
      say(box, "Your artwork could not be uploaded. Check your connection and try again.", "error");
    };
    xhr.send(data);
  }

  // The button opens the file picker when no file is chosen yet, and uploads right after.
  document.addEventListener("click", function (e) {
    var button = e.target.closest && e.target.closest(".order-artwork__submit");
    var box = button && button.closest("[data-order-artwork]");
    if (!box) return;
    e.preventDefault();
    if (box.classList.contains("is-busy")) return;
    var input = parts(box).input;
    if (input && !(input.files && input.files.length)) {
      box.setAttribute("data-auto", "1");
      input.click();
      return;
    }
    upload(box, false);
  });

  document.addEventListener("change", function (e) {
    var input = e.target;
    if (!input.classList || !input.classList.contains("order-artwork__file")) return;
    var box = input.closest("[data-order-artwork]");
    if (!box) return;
    say(box, "", "");
    if (box.getAttribute("data-auto") === "1" && input.files && input.files.length) {
      box.removeAttribute("data-auto");
      upload(box, false);
    }
  });

  // Enter in the order page form.
  document.addEventListener("submit", function (e) {
    var box = e.target.closest && e.target.closest("[data-order-artwork]");
    if (!box) return;
    e.preventDefault();
    if (!box.classList.contains("is-busy")) upload(box, false);
  });
})();
