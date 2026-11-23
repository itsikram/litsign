/**
 * Storefront Sign settings > Design (inc/design-settings.php): live color
 * family previews, the All colors table and the typography preview.
 *
 * role() and shift() mirror wholesale_design_color_role() and
 * wholesale_design_shift_color() in PHP; keep the two the same.
 */
jQuery(function ($) {
  var $roles = $("[data-wsd-roles]");
  if (!$roles.length) {
    return;
  }

  var defaults = $roles.data("wsd-roles") || {};
  var scales = $roles.data("wsd-scales") || {};
  var $tidy = $("[data-wsd-tidy]");
  var $overrides = $("[data-wsd-overrides]");
  var overrides = {};
  try {
    overrides = JSON.parse($overrides.val() || "{}") || {};
  } catch (e) {
    overrides = {};
  }

  // ---- Color math ---------------------------------------------------------

  function hex6(value) {
    var v = String(value || "").trim().replace(/^#/, "").toLowerCase();
    if (/^[0-9a-f]{3}$/.test(v)) {
      return v[0] + v[0] + v[1] + v[1] + v[2] + v[2];
    }
    return /^[0-9a-f]{6}$/.test(v) ? v : "";
  }

  function hsl(hex) {
    var r = parseInt(hex.substr(0, 2), 16) / 255;
    var g = parseInt(hex.substr(2, 2), 16) / 255;
    var b = parseInt(hex.substr(4, 2), 16) / 255;
    var max = Math.max(r, g, b);
    var min = Math.min(r, g, b);
    var l = (max + min) / 2;
    if (max === min) {
      return [0, 0, l];
    }
    var d = max - min;
    var s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    var h;
    if (max === r) {
      h = (g - b) / d + (g < b ? 6 : 0);
    } else if (max === g) {
      h = (b - r) / d + 2;
    } else {
      h = (r - g) / d + 4;
    }
    return [h * 60, s, l];
  }

  function toHex(h, s, l) {
    h = (((h % 360) + 360) % 360) / 360;
    s = Math.max(0, Math.min(1, s));
    l = Math.max(0, Math.min(1, l));
    var r, g, b;
    if (s === 0) {
      r = g = b = l;
    } else {
      var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      var p = 2 * l - q;
      var ch = function (t) {
        t = t < 0 ? t + 1 : t > 1 ? t - 1 : t;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
      };
      r = ch(h + 1 / 3);
      g = ch(h);
      b = ch(h - 1 / 3);
    }
    return [r, g, b].map(function (c) {
      var x = Math.round(c * 255).toString(16);
      return x.length < 2 ? "0" + x : x;
    }).join("");
  }

  function role(hex) {
    var c = hsl(hex), h = c[0], s = c[1], l = c[2];
    if (l >= 0.995) return "white";
    if (l <= 0.005) return "";
    if (s < 0.3 || (l >= 0.85 && s < 0.48) || (l < 0.2 && s < 0.55) || (l < 0.35 && s < 0.4)) {
      if (l < 0.22) return "text";
      if (l < 0.4) return "body";
      if (l < 0.7) return "muted";
      return l < 0.94 ? "border" : "soft";
    }
    if (h >= 185 && h < 206) return "brand";
    if (h >= 206 && h < 235 && l < 0.35) return "navy";
    if (h >= 10 && h < 35) return "orange";
    if (h >= 35 && h < 52) return "star";
    if (h >= 90 && h < 175) return "green";
    if (h >= 340 || h < 10) return "red";
    return "";
  }

  function rescale(x, base, target) {
    if (x <= base) {
      return base > 0 ? (x * target) / base : target;
    }
    return base < 1 ? 1 - ((1 - x) * (1 - target)) / (1 - base) : target;
  }

  function shift(hex, base, target) {
    if (base === target) return hex;
    if (hex === base) return target;
    var c = hsl(hex), b = hsl(base), t = hsl(target);
    var hue = b[1] < 0.02 ? t[0] : c[0] + (t[0] - b[0]);
    return toHex(hue, rescale(c[1], b[1], t[1]), rescale(c[2], b[2], t[2]));
  }

  // ---- Families -----------------------------------------------------------

  var current = {};

  function readRoles() {
    $roles.find("[data-wsd-role]").each(function () {
      var key = $(this).data("wsd-role");
      current[key] = hex6($(this).find("[data-wsd-color]").val()) || defaults[key];
    });
  }

  /** Nearest step of the family's palette scale (by lightness). */
  function snap(hex, r) {
    var scale = scales[r] || [];
    if (!scale.length) return hex;
    var l = hsl(hex)[2], best = scale[0], gap = 2;
    scale.forEach(function (step) {
      var d = Math.abs(hsl(step)[2] - l);
      if (d < gap) { gap = d; best = step; }
    });
    return best;
  }

  /** What a theme color becomes from its family alone (no All colors change). */
  function familyColor(hex) {
    var r = role(hex);
    if (!r) return hex;
    var c = $tidy.is(":checked") ? snap(hex, r) : hex;
    if (current[r] && defaults[r] && current[r] !== defaults[r]) {
      return shift(c, defaults[r], current[r]);
    }
    return c;
  }

  function effective(hex) {
    return overrides[hex] || familyColor(hex);
  }

  function paint() {
    readRoles();
    $roles.find("[data-wsd-role]").each(function () {
      var $role = $(this);
      var key = $role.data("wsd-role");
      var changed = current[key] !== defaults[key];
      $role.toggleClass("is-changed", changed);
      $role.find("[data-wsd-reset]").prop("hidden", !changed);
      $role.find("[data-wsd-scale] span").each(function (i) {
        var step = (scales[key] || [])[i];
        if (step) {
          var now = changed ? shift(step, defaults[key], current[key]) : step;
          $(this).css("background", "#" + now);
        }
      });
      $role.find("[data-wsd-chip]").each(function () {
        var hex = String($(this).data("wsd-chip"));
        var now = effective(hex);
        $(this).toggleClass("is-moved", now !== hex).find(".wsd-chip__new").css("background", "#" + now);
        $(this).attr("title", now !== hex ? "#" + hex + " → #" + now : "#" + hex);
      });
    });
    $("[data-wsd-row]").each(function () {
      var hex = String($(this).data("wsd-row"));
      var $input = $(this).find("[data-wsd-row-color]");
      if (!$input.is(":focus")) {
        $input.val("#" + effective(hex));
      }
      $(this).toggleClass("is-overridden", !!overrides[hex]).toggleClass("is-moved", effective(hex) !== hex);
      $(this).find("[data-wsd-row-reset]").prop("hidden", !overrides[hex]);
    });
    var n = Object.keys(overrides).length;
    $("[data-wsd-override-count]").text(n ? n + " changed" : "None changed").attr("class", "wss-badge " + (n ? "is-ok" : "is-info"));
  }

  function saveOverrides() {
    var keys = Object.keys(overrides).sort();
    var clean = {};
    keys.forEach(function (k) {
      clean["#" + k] = "#" + overrides[k];
    });
    $overrides.val(keys.length ? JSON.stringify(clean) : "").trigger("change");
  }

  // Normalize what the page loaded with ("#aabbcc" keys).
  (function () {
    var clean = {};
    Object.keys(overrides).forEach(function (k) {
      var from = hex6(k), to = hex6(overrides[k]);
      if (from && to && from !== to) clean[from] = to;
    });
    overrides = clean;
  })();

  $roles.on("input change", "[data-wsd-color]", function () {
    var $role = $(this).closest("[data-wsd-role]");
    $role.find("[data-wsd-hex]").val(this.value);
    paint();
  });

  $roles.on("input", "[data-wsd-hex]", function () {
    var hex = hex6(this.value);
    if (hex) {
      $(this).closest("[data-wsd-role]").find("[data-wsd-color]").val("#" + hex).trigger("change");
    }
  });

  $roles.on("click", "[data-wsd-reset]", function () {
    var $role = $(this).closest("[data-wsd-role]");
    var def = $role.find("[data-wsd-color]").data("default");
    $role.find("[data-wsd-color]").val(def).trigger("change");
  });

  $("[data-wsd-reset-all]").on("click", function () {
    if (!window.confirm("Set every color back to the theme's own? Nothing changes on the site until you save.")) {
      return;
    }
    $roles.find("[data-wsd-color]").each(function () {
      $(this).val($(this).data("default"));
      $(this).closest("[data-wsd-role]").find("[data-wsd-hex]").val($(this).data("default"));
    });
    var $sale = $("#wholesale_sale_price_color");
    $sale.val($sale.data("default")).trigger("change");
    overrides = {};
    saveOverrides();
    paint();
  });

  // ---- All colors table ---------------------------------------------------

  $(document).on("change", "[data-wsd-row-color]", function () {
    var hex = String($(this).closest("[data-wsd-row]").data("wsd-row"));
    var value = hex6(this.value);
    if (!value || value === familyColor(hex)) {
      delete overrides[hex];
    } else {
      overrides[hex] = value;
    }
    saveOverrides();
    paint();
  });

  $(document).on("click", "[data-wsd-row-reset]", function () {
    var hex = String($(this).closest("[data-wsd-row]").data("wsd-row"));
    delete overrides[hex];
    saveOverrides();
    paint();
  });

  var $find = $("#wsd-all-search");
  var $onlyChanged = $("[data-wsd-only-changed]");

  function filterRows() {
    var q = $.trim($find.val()).toLowerCase().replace(/^#/, "");
    var only = $onlyChanged.is(":checked");
    $("[data-wsd-group]").each(function () {
      var $group = $(this);
      var shown = 0;
      $group.find("[data-wsd-row]").each(function () {
        var $row = $(this);
        var hit = (!q || $row.text().toLowerCase().indexOf(q) !== -1) && (!only || $row.hasClass("is-moved"));
        $row.toggle(hit);
        shown += hit ? 1 : 0;
      });
      $group.toggle(shown > 0);
      if (q || only) {
        $group.prop("open", shown > 0);
      }
    });
  }

  $find.on("input", filterRows).on("keydown", function (e) {
    if (e.key === "Enter") e.preventDefault();
  });
  $onlyChanged.on("change", filterRows);

  // ---- Typography preview -------------------------------------------------

  var $preview = $("[data-wsd-preview]");
  var loaded = {};

  function fontStack(which) {
    var $opt = $('[data-wsd-font="' + which + '"] option:selected');
    var google = $opt.data("google");
    if (google && !loaded[google]) {
      loaded[google] = true;
      $("<link>", { rel: "stylesheet", href: "https://fonts.googleapis.com/css2?family=" + google + "&display=swap" }).appendTo("head");
    }
    return $opt.data("stack") || "";
  }

  function typePreview() {
    if (!$preview.length) return;
    var body = fontStack("body");
    var heading = fontStack("heading");
    $preview.find(".wsd-type-preview__p").css({
      fontFamily: body,
      fontSize: ($("#wholesale_font_size_base").val() || 16) + "px",
      lineHeight: $("#wholesale_line_height").val() || 1.6
    });
    $preview.find(".wsd-type-preview__h1").css({ fontFamily: heading, fontWeight: $("#wholesale_h1_weight").val() || 800 });
    $preview.find(".wsd-type-preview__h2").css({ fontFamily: heading, fontWeight: $("#wholesale_heading_weight").val() || 700 });
  }

  $("[data-wsd-font], [data-wsd-type]").on("input change", typePreview);
  $tidy.on("change", paint);

  paint();
  typePreview();
});
