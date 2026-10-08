/**
 * Background file uploads (endpoint: inc/async-uploads.php).
 *
 * A file starts uploading as soon as it is picked, so submitting the form only sends a
 * short token. If a background upload fails, the file stays in the input and goes with
 * the normal form post instead.
 *
 *   <input type="file" name="x" data-async-upload="artwork" data-async-field="x_token">
 *
 * Single-file inputs marked like that are handled here. The contact page's multi-file
 * picker uses window.wholesaleAsyncUpload.upload() directly.
 */
(function () {
  "use strict";

  var config = window.wholesaleAsyncUpload || {};
  if (!config.ajaxUrl || !window.FormData || !window.XMLHttpRequest) return;

  // Resolves to the token; rejects with an Error whose message can be shown.
  function upload(file, context, onProgress) {
    var xhr = new XMLHttpRequest();
    var promise = new Promise(function (resolve, reject) {
      var data = new FormData();
      data.append("action", "wholesale_async_upload");
      data.append("context", context);
      data.append("file", file);
      xhr.open("POST", config.ajaxUrl);
      xhr.responseType = "json";
      if (onProgress) {
        xhr.upload.addEventListener("progress", function (e) {
          if (e.lengthComputable) onProgress(e.loaded / e.total);
        });
      }
      xhr.onload = function () {
        var json = xhr.response;
        if (json && json.success && json.data && json.data.token) resolve(json.data.token);
        else reject(new Error((json && json.data && json.data.message) || "upload_failed"));
      };
      xhr.onerror = function () { reject(new Error("upload_failed")); };
      xhr.onabort = function () { reject(new Error("aborted")); };
      xhr.send(data);
    });
    return { promise: promise, abort: function () { xhr.abort(); } };
  }

  var pending = new Set();

  // Hold a form's submit until its uploads finish, then submit it again.
  function holdSubmit(form, waitFor) {
    var key = "asyncUploadHold";
    if (form.dataset[key]) return;
    form.dataset[key] = "1";
    Promise.all(waitFor.map(function (p) { return p.catch(function () {}); })).then(function () {
      delete form.dataset[key];
      // Click the same button again so its click handlers (price, tracking) run as usual.
      var button = form.__asyncSubmitter;
      if (button && button.isConnected) button.click();
      else if (form.requestSubmit) form.requestSubmit();
      else form.submit();
    });
  }

  // Capture phase on document: runs before the forms' own submit handlers.
  document.addEventListener("submit", function (e) {
    var form = e.target;
    var waiting = [];
    pending.forEach(function (job) {
      if (job.form === form) waiting.push(job.promise);
    });
    if (!waiting.length) return;
    if (typeof form.checkValidity === "function" && !form.checkValidity()) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    form.__asyncSubmitter = e.submitter || null;
    holdSubmit(form, waiting);
  }, true);

  function bindInput(input) {
    var context = input.getAttribute("data-async-upload");
    var field = input.getAttribute("data-async-field");
    var form = input.form;
    if (!context || !field || !form) return;

    var name = input.name;
    var status = document.createElement("small");
    status.className = "async-upload-status";
    status.setAttribute("aria-live", "polite");
    input.insertAdjacentElement("afterend", status);

    var hidden = document.createElement("input");
    hidden.type = "hidden";
    var job = null;

    function reset() {
      if (job) {
        job.abort();
        pending.delete(job);
        job = null;
      }
      if (hidden.parentNode) hidden.parentNode.removeChild(hidden);
      input.name = name;
      status.textContent = "";
      status.className = "async-upload-status";
    }

    input.addEventListener("change", function () {
      reset();
      var file = input.files && input.files[0];
      if (!file) return;

      status.textContent = "Uploading… 0%";
      var current = upload(file, context, function (p) {
        status.textContent = "Uploading… " + Math.round(p * 100) + "%";
      });
      current.form = form;
      job = current;
      pending.add(current);

      current.promise.then(function (token) {
        if (job !== current) return;
        // The token goes with the form now; the file itself doesn't need to.
        hidden.name = field;
        hidden.value = token;
        input.insertAdjacentElement("afterend", hidden);
        input.removeAttribute("name");
        status.textContent = "Uploaded ✓";
        status.className = "async-upload-status is-done";
      }, function (err) {
        if (job !== current || err.message === "aborted") return;
        // Leave the file in the input so the normal form post still carries it.
        status.textContent = err.message === "upload_failed" ? "" : err.message;
        status.className = "async-upload-status is-error";
      }).then(function () {
        pending.delete(current);
        if (job === current) job = null;
      });
    });

    // Clearing the input (e.g. after Add To Cart) resets it.
    input.addEventListener("async-upload:reset", reset);
  }

  document.querySelectorAll("input[type=file][data-async-upload]").forEach(bindInput);

  window.wholesaleAsyncUpload = Object.assign(config, {
    upload: upload,
    track: function (job, form) {
      job.form = form;
      pending.add(job);
      job.promise.then(function () { pending.delete(job); }, function () { pending.delete(job); });
    },
  });
})();
