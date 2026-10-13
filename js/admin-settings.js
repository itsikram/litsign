/**
 * Settings > Storefront Sign (inc/settings-page.php): panel tabs, settings
 * search, Media Library image pickers and the unsaved-changes save bar.
 */
jQuery(function ($) {
  var $wrap = $(".wss");
  if (!$wrap.length) {
    return;
  }

  var $form = $("#wss-form");
  var $tabs = $wrap.find("[data-wss-tab]");
  var $panels = $wrap.find("[data-wss-panel]");
  var $search = $("#wss-search");
  var $savebar = $wrap.find(".wss-savebar");
  var $state = $wrap.find("[data-wss-state]");
  var $referer = $form.find('input[name="_wp_http_referer"]');
  var refererBase = ($referer.val() || "").split("#")[0];
  var dirty = false;
  var submitting = false;

  $wrap.addClass("has-js");

  // ---- Tabs -------------------------------------------------------------

  function panelFor(hash) {
    var id = (hash || "").replace(/^#/, "");
    if (!id) {
      return "";
    }
    if ($panels.filter('[data-wss-panel="' + id + '"]').length) {
      return id;
    }
    // Old in-page anchors such as #sale-promotion or #google-reviews.
    var target = document.getElementById(id);
    var $panel = target ? $(target).closest("[data-wss-panel]") : $();
    return $panel.length ? $panel.data("wss-panel") : "";
  }

  function show(slug, scrollTo) {
    if (!$panels.filter('[data-wss-panel="' + slug + '"]').length) {
      slug = $panels.first().data("wss-panel");
    }
    $panels.removeClass("is-active").filter('[data-wss-panel="' + slug + '"]').addClass("is-active");
    var $tab = $tabs.attr({ "aria-selected": "false", tabindex: "-1" })
      .filter('[data-wss-tab="' + slug + '"]').attr({ "aria-selected": "true", tabindex: "0" });

    // On narrow screens the menu scrolls sideways: keep the open tab visible.
    var list = $tab.closest("ul")[0];
    if ($tab.length && list && list.scrollWidth > list.clientWidth) {
      list.scrollLeft = $tab[0].offsetLeft - (list.clientWidth - $tab[0].offsetWidth) / 2;
    }

    if (window.history && history.replaceState) {
      history.replaceState(null, "", "#" + slug);
    }
    // options.php sends us back to the referer: keep the open panel after saving.
    $referer.val(refererBase + "#" + slug);

    if (scrollTo) {
      var el = document.getElementById(scrollTo);
      if (el) {
        el.scrollIntoView({ block: "start" });
      }
    }
  }

  var initial = window.location.hash;
  var initialPanel = panelFor(initial);
  show(initialPanel, initialPanel && initial.replace(/^#/, "") !== initialPanel ? initial.replace(/^#/, "") : "");

  $tabs.on("click", function (e) {
    e.preventDefault();
    clearSearch();
    show($(this).data("wss-tab"));
  });

  $tabs.on("keydown", function (e) {
    var keys = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 };
    if (!keys[e.key]) {
      return;
    }
    e.preventDefault();
    var i = $tabs.index(this) + keys[e.key];
    var $next = $tabs.eq((i + $tabs.length) % $tabs.length);
    $next.trigger("focus").trigger("click");
  });

  $wrap.on("click", "[data-wss-goto]", function (e) {
    e.preventDefault();
    clearSearch();
    show($(this).data("wss-goto"));
    $tabs.filter('[data-wss-tab="' + $(this).data("wss-goto") + '"]').trigger("focus");
  });

  $(window).on("hashchange", function () {
    var slug = panelFor(window.location.hash);
    if (slug) {
      show(slug);
    }
  });

  // ---- Search -----------------------------------------------------------

  var $rows = $panels.find(".form-table tr, .wss-image");

  function clearSearch() {
    if ($search.val()) {
      $search.val("");
      runSearch();
    }
  }

  function runSearch() {
    var q = $.trim($search.val()).toLowerCase();
    $rows.removeClass("is-hit").show();
    $panels.removeClass("has-match");
    $tabs.removeClass("has-match").removeAttr("data-matches");
    $wrap.find(".wss-card").show();
    $wrap.find(".wss-no-results").prop("hidden", true);

    if (!q) {
      $wrap.removeClass("is-searching");
      show(panelFor(window.location.hash));
      return;
    }

    $wrap.addClass("is-searching");
    $panels.removeClass("is-active");
    $tabs.attr("aria-selected", "false");
    var total = 0;

    $panels.each(function () {
      var $panel = $(this);
      var count = 0;
      $panel.find(".wss-card").each(function () {
        var $card = $(this);
        var headHit = $card.find(".wss-card__head").text().toLowerCase().indexOf(q) !== -1;
        var shown = 0;
        $card.find(".form-table tr, .wss-image").each(function () {
          var $row = $(this);
          var hit = headHit || $row.text().toLowerCase().indexOf(q) !== -1;
          $row.toggle(hit).toggleClass("is-hit", hit && !headHit);
          if (hit) {
            shown++;
          }
        });
        if (!$card.find(".form-table tr, .wss-image").length && headHit) {
          shown = 1;
        }
        $card.toggle(shown > 0);
        count += shown;
      });
      if (count) {
        $panel.addClass("has-match");
        $tabs.filter('[data-wss-tab="' + $panel.data("wss-panel") + '"]').addClass("has-match").attr("data-matches", count);
      }
      total += count;
    });

    $wrap.find(".wss-no-results").prop("hidden", total > 0);
  }

  $search.on("input", runSearch);
  $search.on("keydown", function (e) {
    if (e.key === "Escape") {
      clearSearch();
    }
    // Enter would submit the settings form.
    if (e.key === "Enter") {
      e.preventDefault();
    }
  });

  // ---- Image pickers ----------------------------------------------------

  $wrap.find("[data-wss-image]").each(function () {
    var $box = $(this);
    var $input = $box.find('input[type="hidden"]');
    var $img = $box.find(".wss-image__frame img");
    var $empty = $box.find(".wss-image__empty");
    var $choose = $box.find("[data-wss-choose]");
    var $remove = $box.find("[data-wss-remove]");
    var $badge = $box.find("[data-wss-badge]");
    var fallback = $box.data("fallback") || "";
    var frame;

    function setPreview(url) {
      $img.attr("src", url || "").prop("hidden", !url);
      $empty.prop("hidden", !!url);
    }

    function setBadge(text, cls) {
      $badge.text(text).attr("class", "wss-badge " + cls);
    }

    $choose.on("click", function () {
      if (!frame) {
        frame = wp.media({
          title: $box.data("title") || "Choose image",
          button: { text: "Use this image" },
          library: { type: "image" },
          multiple: false
        });
        frame.on("open", function () {
          var id = parseInt($input.val(), 10);
          if (id) {
            var attachment = wp.media.attachment(id);
            attachment.fetch();
            frame.state().get("selection").reset([attachment]);
          }
        });
        frame.on("select", function () {
          var image = frame.state().get("selection").first().toJSON();
          var sizes = image.sizes || {};
          $input.val(image.id).trigger("change");
          setPreview((sizes.medium_large || sizes.large || image).url);
          setBadge("Media Library", "is-ok");
          $choose.text("Replace image");
          $remove.prop("hidden", false);
          $box.addClass("is-changed");
        });
      }
      frame.open();
    });

    $remove.on("click", function () {
      $input.val("0").trigger("change");
      setPreview(fallback);
      if (fallback) {
        setBadge($box.data("fallback-label") || "Built-in", "is-info");
      } else {
        setBadge("Not set", "is-warn");
      }
      $choose.text("Upload or choose");
      $remove.prop("hidden", true);
      $box.addClass("is-changed");
    });
  });

  // ---- Media sync (inc/media-sync.php) -------------------------------------

  var $sync = $wrap.find(".wss-msync");
  if ($sync.length) {
    var syncRunning = false;
    var syncStop = false;
    var $progress = $sync.find(".wss-msync__progress");
    var $bar = $progress.find(".wss-msync__bar span");
    var $status = $progress.find(".wss-msync__status");
    var $report = $progress.find(".wss-msync__report");
    var $buttons = $sync.find("[data-wss-msync]");
    // Pause between requests: rapid bursts got this computer blocked by the live site's firewall.
    var PAUSE = 1500;

    var esc = function (text) {
      return $("<div>").text(String(text)).html();
    };
    var size = function (bytes) {
      return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + " MB" : Math.round(bytes / 1024) + " KB";
    };

    var request = function (data) {
      return $.post(window.ajaxurl, $.extend({ action: "wholesale_media_sync", nonce: $sync.data("nonce") }, data));
    };

    var render = function (s) {
      var pct = 0;
      var text = "";
      var verb = s.direction === "push" ? "Pushed" : "Pulled";
      if (s.phase === "check") {
        pct = s.total ? (s.checked / s.total) * 30 : 30;
        text = "Asking " + s.site + " which files it is missing: " + s.checked + " of " + s.total + " checked, " + s.queue + " missing there so far…";
      } else if (s.phase === "transfer") {
        var base = s.direction === "push" ? 30 : 0;
        var span = s.direction === "push" ? 65 : 100;
        var handled = s.done + s.failed_count + s.not_found_count;
        pct = base + (s.queue ? (handled / s.queue) * span : span);
        text = verb + " " + s.done + " of " + s.queue + " files (" + size(s.bytes) + ")…";
      } else if (s.phase === "finalize") {
        pct = 97;
        text = "Moving the uploaded files into place on " + s.site + "…";
      } else {
        pct = 100;
        if (s.direction === "pull" && !s.queue) {
          text = "Nothing to pull: no Media Library file is missing on this site.";
        } else if (s.direction === "push" && !s.queue) {
          text = s.site + " already has every Media Library file of this site.";
        } else {
          text = "Done. " + verb + " " + s.done + " file" + (s.done === 1 ? "" : "s") + " (" + size(s.bytes) + ").";
        }
      }
      $bar.css("width", Math.min(100, Math.round(pct)) + "%");
      $status.text(text);

      var html = "";
      if (s.not_found_count) {
        html += "<p><strong>" + s.not_found_count + " not found on " + esc(s.site) + " either</strong> (their attachments have no file anywhere; re-upload them):</p><ul><li>" + $.map(s.not_found, esc).join("</li><li>") + "</li></ul>";
      }
      if (s.failed_count) {
        html += "<p><strong>" + s.failed_count + " could not be copied</strong> (run it again to retry):</p><ul><li>" + $.map(s.failed, esc).join("</li><li>") + "</li></ul>";
      }
      $report.html(html);
    };

    var finish = function (message, isError) {
      syncRunning = false;
      $buttons.prop("disabled", false);
      $sync.find("[data-wss-msync-cancel]").prop("hidden", true);
      if (message) {
        $status.html((isError ? '<span class="wss-badge is-bad">Stopped</span> ' : "") + esc(message));
      }
    };

    var step = function () {
      if (syncStop) {
        request({ op: "cancel" });
        finish("Stopped. Files copied so far are kept.", false);
        return;
      }
      request({ op: "step" }).done(function (res) {
        if (!res || !res.success) {
          finish((res && res.data && res.data.message) || "The step failed.", true);
          return;
        }
        render(res.data);
        if (res.data.phase === "done") {
          finish("", false);
          return;
        }
        window.setTimeout(step, PAUSE);
      }).fail(function (xhr) {
        finish("Request failed (" + xhr.status + "). Run it again to continue where it stopped.", true);
      });
    };

    $buttons.on("click", function () {
      if (syncRunning) {
        return;
      }
      var direction = $(this).data("wss-msync");
      var $site = $("#wss-msync-site");
      if (direction === "push" && !window.confirm("Upload this site's Media Library files that " + $site.find("option:selected").text() + " is missing?\n\nExisting files there are not overwritten. This replaces that site's Site Migration undo point.")) {
        return;
      }
      syncRunning = true;
      syncStop = false;
      $buttons.prop("disabled", true);
      $progress.prop("hidden", false);
      $sync.find("[data-wss-msync-cancel]").prop("hidden", false);
      $bar.css("width", "0");
      $report.empty();
      $status.text(direction === "push" ? "Listing this site's media files…" : "Finding missing files…");

      request({ op: "start", site_id: $site.val(), direction: direction }).done(function (res) {
        if (!res || !res.success) {
          finish((res && res.data && res.data.message) || "Could not start.", true);
          return;
        }
        render(res.data);
        if (res.data.phase === "done") {
          finish("", false);
          return;
        }
        window.setTimeout(step, 300);
      }).fail(function (xhr) {
        finish("Request failed (" + xhr.status + ").", true);
      });
    });

    $sync.find("[data-wss-msync-cancel]").on("click", function () {
      syncStop = true;
      $status.text("Stopping after the current step…");
    });

    $(window).on("beforeunload", function () {
      if (syncRunning) {
        return "Media sync is still running.";
      }
    });
  }

  // ---- Unsaved changes ----------------------------------------------------

  function markDirty() {
    if (dirty) {
      return;
    }
    dirty = true;
    $savebar.addClass("is-dirty");
    $state.text("Unsaved changes");
  }

  $form.on("input change", "input, select, textarea", function () {
    if (this.id === "wss-search" || $(this).closest("[data-wss-nosave]").length) {
      return;
    }
    markDirty();
  });

  $form.on("submit", function () {
    submitting = true;
    $state.text("Saving…");
  });

  $(window).on("beforeunload", function () {
    if (dirty && !submitting) {
      return "You have unsaved changes.";
    }
  });

  // Ctrl/Cmd + S saves.
  $(document).on("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === "s" || e.key === "S")) {
      e.preventDefault();
      $form.find('[type="submit"]').first().trigger("click");
    }
  });
});
