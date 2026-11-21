/*
 * Toolbar (admin bar) on phones and small tablets, on store pages AND in wp-admin
 * (inc/admin-mobile.php + css/admin-bar-mobile.css). Only acts at <= 782px.
 *  1. Items with no phone icon show their text label.
 *  2. When the bar wraps to two rows, its height goes into --wam-bar-h and
 *     html.wam-tall, so the page (and wp-admin's side menu / block editor) sits
 *     below it.
 *  3. A dropdown that would run past the right edge of the screen is moved left.
 */
(function () {
	'use strict';

	var bar = document.getElementById('wpadminbar');
	if (!bar) {
		return;
	}
	var mq = window.matchMedia('(max-width: 782px)');
	var root = document.documentElement;

	// Core items, and store items that get an icon in css/admin-bar-mobile.css.
	var HAS_ICON = /^wp-admin-bar-(menu-toggle|my-account|site-name|my-sites|wp-logo|updates|comments|new-content|edit|customize|site-editor|command-palette|search|wudt-diagnostics|retails-visitor-insights|retails-store-admin|retails-edit-product-category|retails-clear-cache|sfs-live-chat)$/;

	function iconless(li) {
		var a = li.querySelector(':scope > .ab-item');
		if (!a) {
			return false;
		}
		if (a.querySelector('img, svg')) {
			return false;
		}
		var icon = a.querySelector('.ab-icon');
		if (!icon) {
			return true;
		}
		var before = window.getComputedStyle(icon, '::before').content;
		return !before || before === 'none' || before === 'normal' || before === '""';
	}

	/* ---- 1 + 2. Labels and bar height ------------------------------------- */
	function syncBar() {
		var phone = mq.matches;
		var items = bar.querySelectorAll('#wp-toolbar > ul > li');
		for (var i = 0; i < items.length; i++) {
			var li = items[i];
			if (HAS_ICON.test(li.id)) {
				continue;
			}
			li.classList.toggle('wam-text-item', phone && iconless(li));
		}
		var h = bar.offsetHeight;
		if (phone && h > 46) {
			root.style.setProperty('--wam-bar-h', h + 'px');
			root.classList.add('wam-tall');
		} else {
			root.style.removeProperty('--wam-bar-h');
			root.classList.remove('wam-tall');
		}
	}

	/* ---- 3. Keep open dropdowns on screen ---------------------------------- */
	function fitMenus() {
		var subs = bar.querySelectorAll('#wp-toolbar > ul > li.menupop > .ab-sub-wrapper');
		for (var i = 0; i < subs.length; i++) {
			subs[i].style.removeProperty('left');
			subs[i].style.removeProperty('right');
		}
		if (!mq.matches) {
			return;
		}
		var vw = root.clientWidth;
		for (var j = 0; j < subs.length; j++) {
			var sub = subs[j];
			var r = sub.getBoundingClientRect();
			if (!r.width || r.right <= vw) {
				continue;
			}
			sub.style.left = Math.max(0, vw - r.width) + 'px';
			sub.style.right = 'auto';
		}
	}

	var queued = false;
	function fitSoon() {
		if (queued) {
			return;
		}
		queued = true;
		window.requestAnimationFrame(function () {
			queued = false;
			fitMenus();
		});
	}

	/* ---- Run ------------------------------------------------------------------ */
	function run() {
		syncBar();
		fitMenus();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
	window.addEventListener('load', run);
	window.addEventListener('resize', run);
	window.addEventListener('orientationchange', run);
	if (mq.addEventListener) {
		mq.addEventListener('change', run);
	}
	if (window.ResizeObserver) {
		new ResizeObserver(syncBar).observe(bar);
	}
	// WordPress opens a dropdown by adding .hover (tap) or on :hover / :focus-within.
	if (window.MutationObserver) {
		new MutationObserver(fitSoon).observe(bar, { subtree: true, attributes: true, attributeFilter: ['class'] });
	}
	bar.addEventListener('mouseover', fitSoon);
	bar.addEventListener('focusin', fitSoon);
})();
