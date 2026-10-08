/**
 * Shop page filters (page-shop.php).
 *
 * The server renders every product card with data-* attributes and applies the
 * URL filters on first load. This script filters, sorts and pages those cards
 * in place, keeps facet counts and the URL in step, and runs the mobile
 * filter drawer.
 */
(() => {
	const form = document.getElementById('sc-filters');
	const grid = document.querySelector('[data-grid]');
	if (!form || !grid) return;

	const root = document.documentElement;
	const cards = Array.from(grid.querySelectorAll('.sc-card'));
	const pageSize = parseInt(grid.dataset.pageSize, 10) || 24;
	const search = form.querySelector('#sc-q');
	const sortSelect = document.getElementById('sc-sort');
	const minInput = form.querySelector('#sc-price-min');
	const maxInput = form.querySelector('#sc-price-max');
	const range = form.querySelector('.sc-range');
	const rangeMin = range ? range.querySelector('[data-range="min"]') : null;
	const rangeMax = range ? range.querySelector('[data-range="max"]') : null;
	const rangeFill = range ? range.querySelector('.sc-range-fill') : null;
	const priceCeiling = range ? parseFloat(range.dataset.max) || 0 : 0;
	const chips = document.querySelector('[data-chips]');
	const empty = document.querySelector('[data-empty]');
	const more = document.querySelector('[data-more]');
	const moreBtn = document.querySelector('[data-more-btn]');
	const moreStatus = document.querySelector('[data-more-status]');
	const moreBar = document.querySelector('[data-more-bar]');
	const openBtn = document.querySelector('.sc-filters-open');
	const backdrop = document.querySelector('.sc-backdrop');
	const drawerQuery = window.matchMedia('(max-width: 991.98px)');

	const text = (el, selector) => {
		const node = el.querySelector(selector);
		return node ? node.textContent.replace(/\s+/g, ' ').trim().toLowerCase() : '';
	};
	const card = (el) => ({
		el,
		cats: el.dataset.cats.split(' '),
		unit: el.dataset.unit,
		price: parseFloat(el.dataset.price) || 0,
		rating: parseFloat(el.dataset.rating) || 0,
		reviews: parseInt(el.dataset.reviews, 10) || 0,
		order: el.dataset.order,
		title: text(el, '.sc-card-title'),
		search: [text(el, '.sc-card-title'), text(el, '.sc-card-cat'), text(el, '.sc-card-features')].join(' '),
	});
	const items = cards.map(card);

	let visibleLimit = pageSize;

	// The site header is sticky; the filter sidebar and mobile toolbar sit below it.
	const page = document.querySelector('.sc-page');
	const header = document.querySelector('.site-header');
	const headerHeight = () => {
		if (!header || getComputedStyle(header).position !== 'sticky') return 0;
		return Math.round(header.getBoundingClientRect().height);
	};
	const syncHeader = () => page.style.setProperty('--sc-header', `${headerHeight()}px`);
	if (header && 'ResizeObserver' in window) new ResizeObserver(syncHeader).observe(header);
	syncHeader();

	/* ---------- State ---------- */

	const checkedValues = (name) =>
		Array.from(form.querySelectorAll(`input[name="${name}"]:checked`)).map((input) => input.value);

	const readState = () => {
		const min = parseFloat(minInput.value);
		const max = parseFloat(maxInput.value);
		const rating = form.querySelector('input[name="rating"]:checked');
		return {
			q: search.value.trim().toLowerCase(),
			cat: checkedValues('cat[]'),
			pricing: checkedValues('pricing[]'),
			min: min > 0 ? min : null,
			max: max > 0 ? max : null,
			rating: rating ? parseInt(rating.value, 10) : 0,
			sort: sortSelect ? sortSelect.value : 'featured',
		};
	};

	// Each filter group on its own, so facet counts can leave out their own group.
	const tests = {
		q: (item, s) => !s.q || s.q.split(/\s+/).every((word) => item.search.includes(word)),
		cat: (item, s) => !s.cat.length || s.cat.some((slug) => item.cats.includes(slug)),
		pricing: (item, s) => !s.pricing.length || s.pricing.includes(item.unit),
		price: (item, s) => (s.min === null || item.price >= s.min) && (s.max === null || item.price <= s.max),
		rating: (item, s) => !s.rating || item.rating >= s.rating,
	};
	const groups = Object.keys(tests);
	const passes = (item, s, skip) => groups.every((group) => group === skip || tests[group](item, s));

	const sorters = {
		featured: (a, b) => (a.order < b.order ? -1 : a.order > b.order ? 1 : 0),
		'price-asc': (a, b) => a.price - b.price || sorters.featured(a, b),
		'price-desc': (a, b) => b.price - a.price || sorters.featured(a, b),
		rating: (a, b) => b.rating - a.rating || b.reviews - a.reviews || sorters.featured(a, b),
		name: (a, b) => a.title.localeCompare(b.title),
	};

	/* ---------- Render ---------- */

	const setCount = (key, count) => {
		const el = form.querySelector(`[data-count-for="${key}"]`);
		if (!el) return;
		el.textContent = count;
		const row = el.closest('.sc-check');
		const input = row ? row.querySelector('input') : null;
		row.classList.toggle('is-empty', count === 0 && !(input && input.checked));
	};

	const updateCounts = (s) => {
		form.querySelectorAll('input[name="cat[]"]').forEach((input) => {
			setCount(`cat:${input.value}`, items.filter((item) => item.cats.includes(input.value) && passes(item, s, 'cat')).length);
		});
		form.querySelectorAll('input[name="pricing[]"]').forEach((input) => {
			setCount(`pricing:${input.value}`, items.filter((item) => item.unit === input.value && passes(item, s, 'pricing')).length);
		});
		form.querySelectorAll('input[name="rating"]').forEach((input) => {
			const stars = parseInt(input.value, 10);
			setCount(`rating:${input.value}`, items.filter((item) => item.rating >= stars && passes(item, s, 'rating')).length);
		});
	};

	const labelFor = (input) => {
		const label = input.closest('.sc-check').querySelector('.sc-check-label');
		const clone = label.cloneNode(true);
		clone.querySelectorAll('small').forEach((el) => el.remove());
		return clone.textContent.replace(/\s+/g, ' ').trim();
	};

	const money = (value) => `$${Number.isInteger(value) ? value : value.toFixed(2)}`;

	const renderChips = (s) => {
		const list = [];
		if (s.q) list.push({ label: `“${search.value.trim()}”`, clear: () => { search.value = ''; } });
		form.querySelectorAll('input[name="cat[]"]:checked, input[name="pricing[]"]:checked').forEach((input) => {
			list.push({ label: labelFor(input), clear: () => { input.checked = false; } });
		});
		if (s.min !== null || s.max !== null) {
			const label = s.min !== null && s.max !== null ? `${money(s.min)} – ${money(s.max)}`
				: s.min !== null ? `${money(s.min)} & up` : `Under ${money(s.max)}`;
			list.push({ label, clear: () => { minInput.value = ''; maxInput.value = ''; } });
		}
		if (s.rating) {
			list.push({ label: `${s.rating}★ & up`, clear: () => { form.querySelector('input[name="rating"]:checked').checked = false; } });
		}

		chips.innerHTML = '';
		list.forEach((chip) => {
			const li = document.createElement('li');
			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'sc-chip';
			button.setAttribute('aria-label', `Remove filter: ${chip.label}`);
			button.innerHTML = '<span></span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>';
			button.firstChild.textContent = chip.label;
			button.addEventListener('click', () => { chip.clear(); update(); });
			li.appendChild(button);
			chips.appendChild(li);
		});
		if (list.length > 1) {
			const li = document.createElement('li');
			li.innerHTML = '<button type="button" class="sc-link-btn">Clear all</button>';
			li.firstChild.addEventListener('click', clearAll);
			chips.appendChild(li);
		}
		chips.hidden = list.length === 0;

		document.querySelectorAll('[data-active-count]').forEach((el) => {
			el.textContent = list.length;
			el.hidden = list.length === 0;
		});
		form.querySelectorAll('.sc-clear-all').forEach((el) => { el.hidden = list.length === 0; });
		return list.length;
	};

	const writeUrl = (s) => {
		const params = new URLSearchParams();
		if (s.q) params.set('q', search.value.trim());
		if (s.cat.length) params.set('cat', s.cat.join(','));
		if (s.pricing.length) params.set('pricing', s.pricing.join(','));
		if (s.min !== null || s.max !== null) params.set('price', `${s.min || 0}-${s.max || ''}`);
		if (s.rating) params.set('rating', s.rating);
		if (s.sort !== 'featured') params.set('sort', s.sort);
		const query = params.toString().replace(/%2C/g, ',');
		history.replaceState(null, '', `${location.pathname}${query ? `?${query}` : ''}${location.hash}`);
	};

	const syncTiles = (s) => {
		document.querySelectorAll('[data-cat-tile]').forEach((tile) => {
			const active = s.cat.length === 1 && s.cat[0] === tile.dataset.catTile;
			tile.classList.toggle('is-active', active);
			if (active) tile.setAttribute('aria-current', 'true');
			else tile.removeAttribute('aria-current');
		});
	};

	const update = ({ resetPage = true, url = true } = {}) => {
		const s = readState();
		if (resetPage) visibleLimit = pageSize;

		const matches = items.filter((item) => passes(item, s)).sort(sorters[s.sort] || sorters.featured);
		const matchSet = new Set(matches);
		const rest = items.filter((item) => !matchSet.has(item));

		const fragment = document.createDocumentFragment();
		matches.forEach((item, index) => {
			item.el.hidden = false;
			item.el.classList.toggle('is-paged', index >= visibleLimit);
			fragment.appendChild(item.el);
		});
		rest.forEach((item) => {
			item.el.hidden = true;
			item.el.classList.remove('is-paged');
			fragment.appendChild(item.el);
		});
		grid.appendChild(fragment);

		const total = matches.length;
		const shown = Math.min(total, visibleLimit);
		document.querySelectorAll('[data-result-count]').forEach((el) => { el.textContent = total; });
		document.querySelectorAll('[data-result-noun]').forEach((el) => { el.textContent = total === 1 ? 'product' : 'products'; });
		empty.hidden = total > 0;
		grid.hidden = total === 0;
		more.hidden = shown >= total;
		if (!more.hidden) {
			moreStatus.textContent = `Showing ${shown} of ${total} products`;
			moreBar.style.width = `${(shown / total) * 100}%`;
		}

		updateCounts(s);
		renderChips(s);
		syncTiles(s);
		syncRange();
		if (url) writeUrl(s);
	};

	const clearAll = (event) => {
		if (event) event.preventDefault();
		form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => { input.checked = false; });
		search.value = '';
		minInput.value = '';
		maxInput.value = '';
		update();
	};

	/* ---------- Price slider (square-root scale, so cheap items get room) ---------- */

	const toPos = (price) => (priceCeiling ? Math.round(Math.sqrt(Math.min(price, priceCeiling) / priceCeiling) * 1000) : 0);
	const toPrice = (pos) => Math.round(priceCeiling * (pos / 1000) ** 2);

	function syncRange() {
		if (!range) return;
		const min = parseFloat(minInput.value) || 0;
		const max = parseFloat(maxInput.value) || priceCeiling;
		rangeMin.value = toPos(min);
		rangeMax.value = toPos(max);
		rangeFill.style.left = `${rangeMin.value / 10}%`;
		rangeFill.style.right = `${100 - rangeMax.value / 10}%`;
		rangeMin.setAttribute('aria-valuetext', money(min));
		rangeMax.setAttribute('aria-valuetext', money(max));
	}

	if (range) {
		[rangeMin, rangeMax].forEach((input) => {
			input.hidden = false;
			input.removeAttribute('tabindex');
			input.addEventListener('input', () => {
				let lo = parseInt(rangeMin.value, 10);
				let hi = parseInt(rangeMax.value, 10);
				if (lo > hi - 10) {
					if (input === rangeMin) lo = rangeMin.value = hi - 10;
					else hi = rangeMax.value = lo + 10;
				}
				minInput.value = lo > 0 ? toPrice(lo) : '';
				maxInput.value = hi < 1000 ? toPrice(hi) : '';
				update();
			});
		});
		range.classList.add('is-ready');
	}

	form.querySelectorAll('[data-price-preset]').forEach((button) => {
		button.addEventListener('click', () => {
			const [lo, hi] = button.dataset.pricePreset.split('-');
			minInput.value = lo && lo !== '0' ? lo : '';
			maxInput.value = hi || '';
			update();
		});
	});

	/* ---------- Events ---------- */

	let searchTimer;
	search.addEventListener('input', () => {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(update, 180);
	});

	form.addEventListener('change', (event) => {
		if (event.target === search) return;
		update();
	});
	[minInput, maxInput].forEach((input) => input.addEventListener('input', () => {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(update, 350);
	}));

	// Radios toggle off when clicked again.
	form.querySelectorAll('input[name="rating"]').forEach((input) => {
		input.addEventListener('click', () => {
			if (input.dataset.wasChecked === '1') {
				input.checked = false;
				update();
			}
			form.querySelectorAll('input[name="rating"]').forEach((other) => { other.dataset.wasChecked = other.checked ? '1' : ''; });
		});
		input.dataset.wasChecked = input.checked ? '1' : '';
	});

	form.addEventListener('submit', (event) => {
		event.preventDefault();
		update();
		if (drawerQuery.matches) closeDrawer();
	});

	if (sortSelect) sortSelect.addEventListener('change', () => update());

	document.querySelectorAll('[data-clear-all]').forEach((el) => el.addEventListener('click', clearAll));
	form.querySelectorAll('.sc-clear-all').forEach((el) => el.addEventListener('click', clearAll));

	moreBtn.addEventListener('click', () => {
		const firstNew = grid.querySelector('.sc-card.is-paged:not([hidden])');
		visibleLimit += pageSize;
		update({ resetPage: false, url: false });
		if (firstNew) {
			const link = firstNew.querySelector('a');
			if (link) link.focus({ preventScroll: true });
		}
	});

	// Category tree expand / collapse.
	form.querySelectorAll('.sc-tree-toggle').forEach((toggle) => {
		toggle.addEventListener('click', () => {
			const open = toggle.getAttribute('aria-expanded') !== 'true';
			toggle.setAttribute('aria-expanded', open);
			toggle.closest('.sc-tree-item').classList.toggle('is-open', open);
			document.getElementById(toggle.getAttribute('aria-controls')).hidden = !open;
		});
	});

	// Category tiles filter in place; clicking the active tile clears it.
	const results = document.getElementById('sc-results');
	document.querySelectorAll('[data-cat-tile]').forEach((tile) => {
		tile.addEventListener('click', (event) => {
			event.preventDefault();
			const slug = tile.dataset.catTile;
			const wasActive = tile.classList.contains('is-active');
			form.querySelectorAll('input[name="cat[]"]').forEach((input) => {
				input.checked = !wasActive && input.value === slug;
				if (input.checked) {
					const parent = input.closest('.sc-tree-children');
					if (parent && parent.hidden) {
						const toggle = form.querySelector(`[aria-controls="${parent.id}"]`);
						if (toggle) toggle.click();
					}
				}
			});
			update();
			const top = results.getBoundingClientRect().top + window.scrollY - headerHeight() - 8;
			if (window.scrollY > top || results.getBoundingClientRect().top > window.innerHeight * 0.6) {
				window.scrollTo({ top, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
			}
		});
	});

	// Category strip arrows when the tiles overflow.
	const strip = document.querySelector('.sc-cats-list');
	const arrows = document.querySelector('.sc-cats-arrows');
	if (strip && arrows) {
		const syncArrows = () => {
			arrows.hidden = strip.scrollWidth <= strip.clientWidth + 4;
			const [prev, next] = arrows.querySelectorAll('button');
			prev.disabled = strip.scrollLeft <= 4;
			next.disabled = strip.scrollLeft + strip.clientWidth >= strip.scrollWidth - 4;
		};
		arrows.querySelectorAll('[data-scroll-cats]').forEach((button) => {
			button.addEventListener('click', () => {
				strip.scrollBy({ left: parseInt(button.dataset.scrollCats, 10) * strip.clientWidth * 0.8, behavior: 'smooth' });
			});
		});
		strip.addEventListener('scroll', syncArrows, { passive: true });
		window.addEventListener('resize', syncArrows);
		syncArrows();
	}

	/* ---------- Grid / list view ---------- */

	const viewButtons = document.querySelectorAll('[data-view]');
	const setView = (view) => {
		grid.classList.toggle('sc-grid--list', view === 'list');
		viewButtons.forEach((button) => {
			const active = button.dataset.view === view;
			button.classList.toggle('is-active', active);
			button.setAttribute('aria-pressed', active);
		});
	};
	viewButtons.forEach((button) => button.addEventListener('click', () => {
		setView(button.dataset.view);
		try { localStorage.setItem('scView', button.dataset.view); } catch (e) { /* Private mode. */ }
	}));
	try { if (localStorage.getItem('scView') === 'list') setView('list'); } catch (e) { /* Private mode. */ }

	/* ---------- Mobile drawer ---------- */

	let lastFocus = null;
	const openDrawer = () => {
		lastFocus = document.activeElement;
		form.classList.add('is-open');
		backdrop.hidden = false;
		requestAnimationFrame(() => backdrop.classList.add('is-visible'));
		document.body.classList.add('sc-lock');
		openBtn.setAttribute('aria-expanded', 'true');
		form.setAttribute('role', 'dialog');
		form.setAttribute('aria-modal', 'true');
		const close = form.querySelector('.sc-filters-close');
		if (close) close.focus();
	};
	function closeDrawer() {
		if (!form.classList.contains('is-open')) return;
		form.classList.remove('is-open');
		backdrop.classList.remove('is-visible');
		backdrop.hidden = true;
		document.body.classList.remove('sc-lock');
		openBtn.setAttribute('aria-expanded', 'false');
		form.setAttribute('role', 'search');
		form.removeAttribute('aria-modal');
		(lastFocus && lastFocus !== document.body && !form.contains(lastFocus) ? lastFocus : openBtn).focus();
	}

	openBtn.addEventListener('click', openDrawer);
	document.querySelectorAll('[data-filters-close]').forEach((el) => el.addEventListener('click', closeDrawer));
	document.addEventListener('keydown', (event) => {
		if (!form.classList.contains('is-open')) return;
		if (event.key === 'Escape') closeDrawer();
		if (event.key === 'Tab') {
			const focusable = Array.from(form.querySelectorAll('a[href], button, input, select')).filter((el) => !el.disabled && !el.hidden && el.offsetParent !== null);
			const first = focusable[0];
			const last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
			else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
		}
	});
	drawerQuery.addEventListener('change', (event) => { if (!event.matches) closeDrawer(); });

	/* ---------- Init ---------- */

	// Price inputs only feed the script's ?price= parameter.
	minInput.removeAttribute('name');
	maxInput.removeAttribute('name');
	update({ url: false });
	root.classList.add('sc-ready');
})();
