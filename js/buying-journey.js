/**
 * Buying journey helpers (inc/buying-journey.php), 2026-11-05.
 *  1. /contact/: show only the questions for the chosen product, fill the
 *     duplicate-submission key and the lead attribution fields.
 *  2. Calculator products: group less common finishing options under
 *     "Advanced finishing options" and link each option to its help text.
 * Attribution values stay on the lead record; nothing here sends data to analytics.
 */
(function () {
  'use strict';

  // ---- 1. Product-aware quote form ----
  var form = document.querySelector('[data-contact-form]');
  if (form) {
    var groups = form.querySelectorAll('[data-show-for]');
    var syncGroups = function () {
      var picked = form.querySelector('input[name="contact_interest"]:checked');
      var value = picked ? picked.value : '';
      groups.forEach(function (group) {
        var show = value && (' ' + group.getAttribute('data-show-for') + ' ').indexOf(' ' + value + ' ') !== -1;
        group.hidden = !show;
        // Hidden questions are not sent, so a changed mind leaves no stray answers.
        group.querySelectorAll('input, select, textarea').forEach(function (field) {
          field.disabled = !show;
        });
      });
    };
    form.addEventListener('change', function (e) {
      if (e.target && e.target.name === 'contact_interest') syncGroups();
    });
    syncGroups();

    // One key per page view: a double tap or a resend after "back" returns the first request.
    var key = form.querySelector('[data-form-key]');
    if (key && !key.value) {
      key.value = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID()
        : Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
    }

    var stored = {};
    try { stored = JSON.parse(window.sessionStorage.getItem('sfsAttribution') || '{}') || {}; } catch (err) { stored = {}; }
    var params = new URLSearchParams(window.location.search);
    form.querySelectorAll('[data-attr]').forEach(function (input) {
      var name = input.getAttribute('data-attr');
      var value = params.get(name) || stored[name] || '';
      if (name === 'from' && !value && document.referrer.indexOf(window.location.origin) === 0) value = document.referrer;
      if (name === 'referrer' && !value && document.referrer && document.referrer.indexOf(window.location.origin) !== 0) value = document.referrer;
      if (name === 'landing' && !value) value = stored.landing || '';
      input.value = String(value).slice(0, 300);
    });
  }

  var sent = document.querySelector('[data-contact-sent]');
  if (sent) sent.focus({ preventScroll: false });

  // ---- 2. Calculator products: advanced finishing options ----
  // Again after a product group swap (js/product-group-swap.js) brings in another product's options.
  var finishing = function () {
    var advanced = ['webbing', 'velcro', 'rope', 'corners', 'reinforced-strip', 'windslit', 'wind-slit'];
    var rows = [];
    advanced.forEach(function (name) {
      var select = document.querySelector('select.dynamic-select[name="' + name + '"]');
      var row = select && select.closest('.select-row');
      if (row && !row.closest('.pb-advanced-finishing')) rows.push({ row: row, select: select });
    });
    if (rows.length >= 2) {
      // Keep page order.
      rows.sort(function (a, b) {
        return a.row.compareDocumentPosition(b.row) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
      });
      var box = document.createElement('details');
      box.className = 'pb-advanced-finishing';
      var summary = document.createElement('summary');
      summary.textContent = 'Advanced finishing options';
      var note = document.createElement('small');
      note.className = 'pb-advanced-finishing__note';
      note.textContent = ' (optional: most orders don’t need these)';
      summary.appendChild(note);
      box.appendChild(summary);
      rows[0].row.parentNode.insertBefore(box, rows[0].row);
      rows.forEach(function (item) {
        box.appendChild(item.row);
        if (item.select.selectedIndex > 0) box.open = true;
      });
    }

    document.querySelectorAll('.pb-attr-help[id^="help-"]').forEach(function (help) {
      var select = document.getElementById('select-' + help.id.slice(5));
      if (select) select.setAttribute('aria-describedby', help.id);
    });
  };
  finishing();
  document.addEventListener('retails:product-swapped', finishing);
})();
