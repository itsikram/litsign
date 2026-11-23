/**
 * "How to order" video section (inc/cl-how-to-video.php).
 * The <video> has no source until the visitor presses play or a step, so the page
 * never downloads the MP4 on its own. Phones / Save-Data get the 720p file.
 */
(function () {
  'use strict';

  function init(section) {
    var video = section.querySelector('.cl-howto__video');
    var poster = section.querySelector('[data-cl-howto-play]');
    var steps = Array.prototype.slice.call(section.querySelectorAll('[data-cl-howto-seek]'));
    if (!video || !poster) return;

    var started = false;

    function pickSource() {
      var conn = navigator.connection || {};
      var small = window.matchMedia('(max-width: 767.98px)').matches || conn.saveData === true || /(^|-)2g$/.test(conn.effectiveType || '');
      return small ? video.getAttribute('data-src-sd') : video.getAttribute('data-src-hd');
    }

    function start(at) {
      if (!started) {
        started = true;
        video.src = pickSource();
        video.hidden = false;
        poster.hidden = true;
        section.classList.add('is-playing');
      }
      if (typeof at === 'number') {
        var seek = function () {
          try { video.currentTime = at; } catch (e) { /* not seekable yet */ }
        };
        if (video.readyState >= 1) seek();
        else video.addEventListener('loadedmetadata', seek, { once: true });
      }
      var p = video.play();
      if (p && typeof p.catch === 'function') p.catch(function () { /* autoplay blocked: controls stay visible */ });
    }

    poster.addEventListener('click', function () {
      start();
      video.focus({ preventScroll: true });
    });

    steps.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var at = parseFloat(btn.getAttribute('data-cl-howto-seek')) || 0;
        start(at);
        // On phones the steps sit under the video: bring the video back into view.
        var r = video.getBoundingClientRect();
        if (r.top < 0 || r.bottom > window.innerHeight) {
          section.querySelector('.cl-howto__frame').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    });

    // Highlight the step that is playing.
    var marks = steps.map(function (b) { return parseFloat(b.getAttribute('data-cl-howto-seek')) || 0; });
    var current = -1;
    video.addEventListener('timeupdate', function () {
      var t = video.currentTime, idx = -1;
      for (var i = 0; i < marks.length; i++) { if (t >= marks[i] - 0.25) idx = i; }
      if (idx === current) return;
      current = idx;
      steps.forEach(function (b, i) {
        b.classList.toggle('is-current', i === idx);
        if (i === idx) b.setAttribute('aria-current', 'step'); else b.removeAttribute('aria-current');
      });
    });

    var top = section.querySelector('[data-cl-howto-top]');
    if (top) {
      top.addEventListener('click', function (e) {
        e.preventDefault();
        if (!video.paused) video.pause();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }

    // Links from Google key moments: ?t=34#how-to-order-video
    if (location.hash === '#how-to-order-video') {
      var t = parseFloat(new URLSearchParams(location.search).get('t'));
      if (!isNaN(t)) {
        // Load at the moment but let the visitor press play (browsers block sound autoplay).
        started = true;
        video.src = pickSource();
        video.hidden = false;
        poster.hidden = true;
        section.classList.add('is-playing');
        video.addEventListener('loadedmetadata', function () { video.currentTime = t; }, { once: true });
        video.preload = 'metadata';
      }
    }
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-cl-howto]'), init);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
