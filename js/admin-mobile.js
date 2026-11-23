/*
 * Phone-friendly wp-admin (inc/admin-mobile.php + css/admin-mobile.css).
 * Only acts at phone width (<= 782px); desktop is left alone.
 *  1 + 2. Toolbar: moved to js/admin-bar-mobile.js (loads on store pages too).
 *  3. List screens get a "Sort by" box (WordPress hides the sortable columns on phones).
 *  4. Anything wider than the phone is fixed: tables scroll inside their own box,
 *     flex rows wrap, other boxes shrink to fit.
 */
(function () {
	'use strict';

	var mq = window.matchMedia('(max-width: 782px)');
	var root = document.documentElement;

	function isPhone() {
		return mq.matches;
	}

	/* ---- 1 + 2. Toolbar: js/admin-bar-mobile.js (store pages use it too) ---- */

	/* ---- 3. Sort by box ----------------------------------------------------- */
	function buildSort() {
		var tables = document.querySelectorAll('#wpbody-content table');
		for (var t = 0; t < tables.length; t++) {
			var table = tables[t];
			if (table.getAttribute('data-wam-sort')) {
				continue;
			}
			var heads = table.querySelectorAll('thead th.sortable, thead th.sorted');
			if (!heads.length) {
				continue;
			}
			table.setAttribute('data-wam-sort', '1');

			var select = document.createElement('select');
			select.id = 'wam-sort-' + t;
			var current = '';
			var blank = document.createElement('option');
			blank.value = '';
			blank.textContent = 'Default order';
			select.appendChild(blank);

			for (var i = 0; i < heads.length; i++) {
				var link = heads[i].querySelector('a[href]');
				if (!link) {
					continue;
				}
				var label = (heads[i].querySelector('span') || link).textContent.replace(/\s+/g, ' ').trim();
				if (!label) {
					continue;
				}
				var url;
				try {
					url = new URL(link.href, window.location.href);
				} catch (e) {
					continue;
				}
				var dirs = [['desc', '↓ high / newest / Z–A'], ['asc', '↑ low / oldest / A–Z']];
				for (var d = 0; d < dirs.length; d++) {
					url.searchParams.set('order', dirs[d][0]);
					url.searchParams.delete('paged');
					var opt = document.createElement('option');
					opt.value = url.toString();
					opt.textContent = label + ' ' + dirs[d][1];
					if (heads[i].classList.contains('sorted') && heads[i].classList.contains(dirs[d][0])) {
						current = opt.value;
					}
					select.appendChild(opt);
				}
			}
			if (select.options.length < 2) {
				continue;
			}
			select.value = current;
			select.addEventListener('change', function () {
				if (this.value) {
					window.location.href = this.value;
				}
			});

			var box = document.createElement('div');
			box.className = 'wam-sort';
			var lab = document.createElement('label');
			lab.setAttribute('for', select.id);
			lab.textContent = 'Sort by';
			box.appendChild(lab);
			box.appendChild(select);

			// Inside the form's top toolbar when there is one, otherwise just above the table.
			var form = table.closest('form');
			var nav = form ? form.querySelector('.tablenav.top') : null;
			if (nav) {
				nav.appendChild(box);
			} else {
				box.style.marginBottom = '8px';
				var anchor = table.parentNode.classList.contains('wam-scroll') ? table.parentNode : table;
				anchor.parentNode.insertBefore(box, anchor);
			}
		}
	}

	/* ---- 4. Overflow fixer ---------------------------------------------------- */
	function clipsX(el) {
		var s = window.getComputedStyle(el);
		return /(auto|scroll|hidden|clip)/.test(s.overflowX);
	}

	function insideClipper(el, stop) {
		var p = el.parentElement;
		while (p && p !== stop) {
			if (clipsX(p)) {
				return true;
			}
			p = p.parentElement;
		}
		return false;
	}

	function fixOverflow() {
		var content = document.getElementById('wpbody-content');
		if (!content) {
			return;
		}
		var limit = Math.min(window.innerWidth, (window.screen && window.screen.width) || window.innerWidth, root.clientWidth) + 1;
		for (var pass = 0; pass < 4; pass++) {
			var changed = false;
			var all = content.querySelectorAll('*');
			for (var i = 0; i < all.length; i++) {
				var el = all[i];
				var r = el.getBoundingClientRect();
				if (!r.width || r.right <= limit) {
					continue;
				}
				var parent = el.parentElement;
				if (!parent || parent.getBoundingClientRect().right > limit) {
					continue; // fix the outermost box only
				}
				if (window.getComputedStyle(el).position === 'fixed' || insideClipper(el, document.body)) {
					continue;
				}
				if (el.tagName === 'TABLE') {
					var wrap = document.createElement('div');
					wrap.className = 'wam-scroll';
					parent.insertBefore(wrap, el);
					wrap.appendChild(el);
				} else if (/flex/.test(window.getComputedStyle(el).display)) {
					el.classList.add('wam-wrap', 'wam-fit');
				} else if (el.classList.contains('wam-fit')) {
					// Already shrunk and still too wide (fixed-width content): let it scroll.
					el.style.overflowX = 'auto';
				} else {
					el.classList.add('wam-fit');
				}
				changed = true;
			}
			if (!changed) {
				break;
			}
		}
	}

	/* ---- Run ------------------------------------------------------------------ */
	var timer = null;
	function run() {
		if (!isPhone()) {
			return;
		}
		buildSort();
		fixOverflow();
	}
	function later() {
		clearTimeout(timer);
		timer = setTimeout(run, 250);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
	window.addEventListener('load', later);
	window.addEventListener('resize', later);
	window.addEventListener('orientationchange', later);
	if (mq.addEventListener) {
		mq.addEventListener('change', later);
	}

	// Screens that load content after the page (charts, AJAX tables) get re-checked.
	var body = document.getElementById('wpbody-content');
	if (body && window.MutationObserver) {
		var busy = false;
		new MutationObserver(function () {
			if (busy || !isPhone()) {
				return;
			}
			busy = true;
			setTimeout(function () {
				busy = false;
				run();
			}, 600);
		}).observe(body, { childList: true, subtree: true });
	}
})();
