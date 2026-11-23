/**
 * Live search (async results for the header search + mobile menu search).
 * Moved out of header.php on 2026-11-11 (~15 KB less HTML on every page);
 * header.php prints window.ssxLiveSearch first and loads this file deferred.
 */
(function() {
	var cfg = window.ssxLiveSearch;
	if (!cfg || !window.fetch || !window.Map) return;

	var MIN = cfg.min || 2,
		DELAY = 220,
		cache = new Map(),
		useCore = false,
		uid = 0;

	/* ---------- Tiny DOM helpers (all dynamic text goes through textContent) ---------- */
	function h(tag, cls, text) {
		var el = document.createElement(tag);
		if (cls) el.className = cls;
		if (text != null) el.textContent = text;
		return el;
	}

	var ICONS = {
		arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
		image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-9 9"/>',
		alert: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>',
		search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>'
	};

	function svg(name, size, cls) {
		var d = document.createElement('div');
		d.innerHTML = '<svg class="ssx-r-svg' + (cls ? ' ' + cls : '') + '" viewBox="0 0 24 24" width="' + size + '" height="' + size + '" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + ICONS[name] + '</svg>';
		return d.firstChild;
	}

	function decode(s) {
		var t = document.createElement('textarea');
		t.innerHTML = s || '';
		return t.value;
	}

	function highlight(node, text, q) {
		var words = q.split(/\s+/).filter(Boolean).map(function(w) {
			return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		});
		if (!words.length) {
			node.textContent = text;
			return;
		}
		text.split(new RegExp('(' + words.join('|') + ')', 'gi')).forEach(function(part, i) {
			if (!part) return;
			if (i % 2) node.appendChild(h('mark', null, part));
			else node.appendChild(document.createTextNode(part));
		});
	}

	function withParams(base, params) {
		var sep = base.indexOf('?') > -1 ? '&' : '?';
		return base + sep + Object.keys(params).map(function(k) {
			return k + '=' + encodeURIComponent(params[k]);
		}).join('&');
	}

	function searchHref(term) {
		var p = {
			s: term
		};
		if (cfg.postType) p.post_type = cfg.postType;
		return withParams(cfg.searchUrl, p);
	}

	/* ---------- Data ---------- */
	function getJSON(u, signal) {
		return fetch(u, {
			signal: signal,
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json'
			}
		}).then(function(res) {
			if (!res.ok) {
				var err = new Error('HTTP ' + res.status);
				err.status = res.status;
				throw err;
			}
			return res.json().then(function(json) {
				return {
					json: json,
					res: res
				};
			});
		});
	}

	function viaCustom(q, signal) {
		var p = {
			q: q,
			limit: cfg.limit
		};
		if (cfg.postType) p.post_type = cfg.postType;
		return getJSON(withParams(cfg.endpoint, p), signal).then(function(r) {
			return {
				total: r.json.total || 0,
				items: r.json.items || []
			};
		});
	}

	function viaCore(q, signal) {
		return getJSON(withParams(cfg.fallback, {
			search: q,
			per_page: cfg.limit,
			type: 'post',
			subtype: cfg.postType || 'any'
		}), signal).then(function(r) {
			var items = (r.json || []).map(function(x) {
				return {
					id: x.id,
					title: decode(x.title),
					url: x.url,
					image: '',
					type: '',
					price: ''
				};
			});
			return {
				total: parseInt(r.res.headers.get('X-WP-Total'), 10) || items.length,
				items: items
			};
		});
	}

	/* ---------- Instant search: every product downloaded once, matched in the browser ----------
	 * /wp-json/ssx/v1/search-index (ssx-live-search.php) is fetched on the first sign of a
	 * person (pointer, touch, key) and kept in the HTTP cache; each keystroke is then matched
	 * here in well under a millisecond. Matching follows WordPress search, so the counts agree
	 * with the results page: every word must appear in the title, excerpt or content. */
	var STOP = ' about an are as at be by com for from how in is it of on or that the this to was what when where who will with www ',
		EDGE = /^[.,;:!?()\[\]{}<>"'*|`~\u201C\u201D\u2018\u2019]+|[.,;:!?()\[\]{}<>"'*|`~\u201C\u201D\u2018\u2019]+$/g,
		idx = null,
		idxPromise = null,
		// Product searches only; the index lives next to the /search endpoint.
		indexUrl = cfg.postType === 'product' && cfg.endpoint ? cfg.endpoint.replace(/\/ssx\/v1\/search(?=$|[?&#])/, '/ssx/v1/search-index') : '',
		idxFailed = !indexUrl || indexUrl === cfg.endpoint;

	function norm(s) {
		s = String(s || '').toLowerCase();
		return s.normalize ? s.normalize('NFD').replace(/[\u0300-\u036F]/g, '') : s;
	}

	function loadIndex() {
		if (idxFailed) return null;
		if (idxPromise) return idxPromise;
		idxPromise = getJSON(indexUrl).then(function(r) {
			var j = r.json || {},
				rows = j.items || [],
				abs = function(s, base) {
					return !s || /^(https?:)?\/\//.test(s) || s.charAt(0) === '/' ? s : base + s;
				};
			if (!rows.length) throw new Error('Empty index');
			idx = rows.map(function(x, n) {
				var tl = norm(x[1]);
				return {
					id: x[0],
					title: String(x[1] || ''),
					url: abs(x[2], j.home || ''),
					image: abs(x[3], j.up || ''),
					tl: tl,
					// What each part of the title is ("13oz. Vinyl Banner" is a banner,
					// "Banner Tape - 1in x 164ft" is tape): last word before a dash/bracket.
					heads: tl.split(/\s[\u2013\u2014-]\s|[(),]/).map(function(part) {
						var w = part.trim().split(/\s+/);
						return w[w.length - 1].replace(EDGE, '');
					}).filter(Boolean),
					hay: tl + ' ' + norm(x[4]),
					n: n
				};
			});
			return idx;
		}).catch(function(err) {
			idxFailed = true; // The server search below takes over.
			throw err;
		});
		return idxPromise;
	}

	// Search words the way WordPress splits them: spaces, commas, plus signs and quotes
	// separate words; single letters and common stopwords are skipped.
	function terms(q) {
		var out = [];
		(q.match(/"[^"]*"?|[^\t ",+]+/g) || []).forEach(function(raw) {
			norm(raw).replace(/"/g, ' ').split(/\s+/).forEach(function(t) {
				t = t.replace(EDGE, '');
				if (!t || (t.length === 1 && /^[a-z\-]$/.test(t)) || STOP.indexOf(' ' + t + ' ') > -1) return;
				if (out.indexOf(t) < 0) out.push(t);
			});
		});
		if (!out.length || out.length > 9) out = [norm(q).replace(EDGE, '')];
		return out;
	}

	// 2 = starts a word in the title, 1 = inside a title word, 0 = not in the title.
	function titleHit(tl, t) {
		var i = tl.indexOf(t);
		if (i < 0) return 0;
		while (i > -1) {
			if (i === 0 || /[^a-z0-9]/.test(tl.charAt(i - 1))) return 2;
			i = tl.indexOf(t, i + 1);
		}
		return 1;
	}

	function searchIndex(q) {
		var ts = terms(q),
			phrase = ts.join(' '),
			hits = [];
		for (var i = 0; i < idx.length; i++) {
			var it = idx[i],
				score = 0,
				inTitle = 0,
				ok = true;
			for (var k = 0; k < ts.length; k++) {
				if (it.hay.indexOf(ts[k]) < 0) {
					ok = false;
					break;
				}
				var hit = titleHit(it.tl, ts[k]);
				if (hit) {
					inTitle++;
					score += hit === 2 ? 30 : 12;
					// The product IS what was searched ("banner" -> banners before banner tape).
					for (var h = 0; h < it.heads.length; h++) {
						if (it.heads[h].indexOf(ts[k]) === 0) {
							score += 25;
							break;
						}
					}
				}
			}
			if (!ok) continue;
			if (inTitle === ts.length) score += 40;
			if (it.tl === phrase) score += 200;
			else if (ts.length > 1 && it.tl.indexOf(phrase) > -1) score += 30;
			hits.push({
				it: it,
				s: score
			});
		}
		// Best title match first; ties keep shop order (listed products by category).
		hits.sort(function(a, b) {
			return b.s - a.s || a.it.n - b.it.n;
		});
		return {
			total: hits.length,
			items: hits.slice(0, cfg.limit || 6).map(function(h) {
				return {
					id: h.it.id,
					title: h.it.title,
					url: h.it.url,
					image: h.it.image,
					type: 'Product',
					price: ''
				};
			})
		};
	}

	// Start the download on the first sign of a person (bots and page-speed tests never
	// move a pointer), so the index is ready before the first letter is typed.
	if (!idxFailed) {
		var wake = function() {
			['pointerdown', 'pointermove', 'touchstart', 'keydown', 'focusin'].forEach(function(t) {
				window.removeEventListener(t, wake, true);
			});
			(window.requestIdleCallback || function(fn) {
				setTimeout(fn, 1);
			})(function() {
				var p = loadIndex();
				if (p) p.catch(function() {});
			}, {
				timeout: 1500
			});
		};
		['pointerdown', 'pointermove', 'touchstart', 'keydown', 'focusin'].forEach(function(t) {
			window.addEventListener(t, wake, {
				capture: true,
				passive: true
			});
		});
	}

	function fetchResults(q, signal) {
		var key = q.toLowerCase();
		if (cache.has(key)) return Promise.resolve(cache.get(key));
		var req = useCore ? viaCore(q, signal) : viaCustom(q, signal).catch(function(err) {
			if (err && err.status === 404) {
				useCore = true;
				return viaCore(q, signal);
			}
			throw err;
		});
		return req.then(function(data) {
			if (cache.size > 60) cache.delete(cache.keys().next().value);
			cache.set(key, data);
			return data;
		});
	}

	/* ---------- One controller per search form ---------- */
	function attach(form, input, inline, clearBtn) {
		var id = 'ssxRes' + (++uid),
			panel = h('div', 'ssx-results' + (inline ? ' ssx-results--inline' : '')),
			live = h('div', 'ssx-r-vh'),
			options = [],
			active = -1,
			seq = 0,
			timer = null,
			ctrl = null;

		panel.id = id;
		panel.hidden = true;
		live.setAttribute('role', 'status');
		live.setAttribute('aria-live', 'polite');
		form.appendChild(live);
		if (inline) form.parentNode.insertBefore(panel, form.nextSibling);
		else form.appendChild(panel);

		input.setAttribute('role', 'combobox');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-haspopup', 'listbox');
		input.setAttribute('aria-expanded', 'false');
		input.setAttribute('aria-controls', id);

		function setActive(i) {
			if (active > -1 && options[active]) {
				options[active].classList.remove('is-active');
				options[active].setAttribute('aria-selected', 'false');
			}
			active = i;
			if (i > -1 && options[i]) {
				options[i].classList.add('is-active');
				options[i].setAttribute('aria-selected', 'true');
				input.setAttribute('aria-activedescendant', options[i].id);
				options[i].scrollIntoView({
					block: 'nearest'
				});
			} else {
				input.removeAttribute('aria-activedescendant');
			}
		}

		function open() {
			panel.hidden = false;
			input.setAttribute('aria-expanded', 'true');
		}

		function close() {
			panel.hidden = true;
			input.setAttribute('aria-expanded', 'false');
			setActive(-1);
		}

		function reset() {
			clearTimeout(timer);
			seq++;
			if (ctrl) ctrl.abort();
			panel.classList.remove('is-loading');
			live.textContent = '';
			close();
		}

		function empty() {
			panel.textContent = '';
			options = [];
			active = -1;
			panel.classList.remove('is-loading');
		}

		/* ----- Renderers ----- */
		function renderSkeleton() {
			empty();
			var wrap = h('div', 'ssx-results__skel');
			wrap.setAttribute('aria-hidden', 'true');
			for (var i = 0; i < 3; i++) {
				var row = h('div', 'ssx-sk'),
					txt = h('span', 'ssx-sk__txt');
				row.appendChild(h('span', 'ssx-sk__img'));
				txt.appendChild(h('span', 'ssx-sk__l1'));
				txt.appendChild(h('span', 'ssx-sk__l2'));
				row.appendChild(txt);
				wrap.appendChild(row);
			}
			panel.appendChild(wrap);
			open();
		}

		function renderResults(term, data) {
			empty();
			var head = h('div', 'ssx-results__head');
			head.appendChild(h('span', null, cfg.postType === 'product' ? 'Products' : 'Results'));
			head.appendChild(h('span', 'ssx-results__count', data.total + (data.total === 1 ? ' match' : ' matches')));
			panel.appendChild(head);

			var ul = h('ul', 'ssx-results__list');
			ul.setAttribute('role', 'listbox');
			ul.setAttribute('aria-label', 'Search results');

			data.items.forEach(function(it, i) {
				var li = h('li'),
					a = h('a', 'ssx-res'),
					thumb = h('span', 'ssx-res__thumb'),
					body = h('span', 'ssx-res__body'),
					title = h('span', 'ssx-res__title');
				li.setAttribute('role', 'presentation');
				a.href = it.url;
				a.id = id + '-o' + i;
				a.tabIndex = -1;
				a.setAttribute('role', 'option');
				a.setAttribute('aria-selected', 'false');

				if (it.image) {
					var img = document.createElement('img');
					img.src = it.image;
					img.alt = '';
					img.width = 52;
					img.height = 52;
					// Eager: six small thumbnails, shown at once (lazy waits for a layout pass).
					img.decoding = 'async';
					img.onerror = function() {
						if (img.parentNode) img.parentNode.removeChild(img);
						thumb.appendChild(svg('image', 22));
					};
					thumb.appendChild(img);
				} else {
					thumb.appendChild(svg('image', 22));
				}

				highlight(title, it.title, term);
				body.appendChild(title);
				var showType = it.type && !cfg.postType;
				if (showType || it.price) {
					var meta = h('span', 'ssx-res__meta');
					if (showType) meta.appendChild(h('span', 'ssx-res__type', it.type));
					if (it.price) meta.appendChild(h('span', 'ssx-res__price', it.price));
					body.appendChild(meta);
				}

				a.appendChild(thumb);
				a.appendChild(body);
				a.appendChild(svg('arrow', 18, 'ssx-res__go'));
				li.appendChild(a);
				ul.appendChild(li);
				options.push(a);
			});
			panel.appendChild(ul);

			var all = h('a', 'ssx-results__all'),
				label = h('span');
			all.href = searchHref(term);
			all.id = id + '-all';
			all.tabIndex = -1;
			all.setAttribute('role', 'option');
			all.setAttribute('aria-selected', 'false');
			label.appendChild(document.createTextNode('See all ' + (data.total > data.items.length ? data.total + ' ' : '') + 'results for '));
			label.appendChild(h('strong', null, '\u201C' + term + '\u201D'));
			all.appendChild(label);
			all.appendChild(svg('arrow', 18));
			panel.appendChild(all);
			options.push(all);

			live.textContent = data.total + (data.total === 1 ? ' result' : ' results') + ' available. Use the up and down arrow keys to review.';
		}

		function renderEmpty(term) {
			empty();
			var box = h('div', 'ssx-results__empty'),
				ico = h('span', 'ssx-results__empty-ico');
			ico.appendChild(svg('search', 22));
			box.appendChild(ico);
			box.appendChild(h('p', 'ssx-results__empty-title', 'No results for \u201C' + term + '\u201D'));
			box.appendChild(h('p', 'ssx-results__empty-sub', 'Check the spelling or try a broader keyword.'));
			if (cfg.popular && cfg.popular.length) {
				box.appendChild(h('p', 'ssx-results__empty-label', 'Popular searches'));
				var chips = h('div', 'ssx-results__chips');
				cfg.popular.forEach(function(t) {
					var c = h('a', 'ssx-results__chip', t);
					c.href = searchHref(t);
					chips.appendChild(c);
				});
				box.appendChild(chips);
			}
			if (cfg.phone) {
				var help = h('a', 'ssx-results__help', 'Can\u2019t find it? Call ' + cfg.phone);
				help.href = cfg.phoneHref;
				box.appendChild(help);
			}
			panel.appendChild(box);
			live.textContent = 'No results found.';
		}

		function renderError(term) {
			empty();
			var box = h('div', 'ssx-results__empty'),
				ico = h('span', 'ssx-results__empty-ico'),
				retry = h('button', 'ssx-results__retry', 'Try again');
			ico.appendChild(svg('alert', 22));
			box.appendChild(ico);
			box.appendChild(h('p', 'ssx-results__empty-title', 'We couldn\u2019t load results'));
			box.appendChild(h('p', 'ssx-results__empty-sub', 'Check your connection, or press Enter to search all products.'));
			retry.type = 'button';
			retry.addEventListener('click', function() {
				run(term);
			});
			box.appendChild(retry);
			panel.appendChild(box);
			live.textContent = 'Search failed. Try again.';
		}

		function show(term, data) {
			if (data.items.length) renderResults(term, data);
			else renderEmpty(term);
			open();
		}

		/* ----- Request flow ----- */
		function run(term) {
			var token = ++seq;
			if (ctrl) ctrl.abort();
			// Instant path: the product index is in memory.
			if (idx) {
				show(term, searchIndex(term));
				return;
			}
			var key = term.toLowerCase();
			if (cache.has(key)) {
				show(term, cache.get(key));
				return;
			}
			if (panel.querySelector('.ssx-results__list') && !panel.hidden) panel.classList.add('is-loading');
			else renderSkeleton();

			// Index still downloading: show results the moment it lands (server search if it fails).
			var pending = loadIndex();
			if (pending) {
				pending.then(function() {
					if (token === seq) show(term, searchIndex(term));
				}, function() {
					if (token === seq) runServer(term, token);
				});
				return;
			}
			runServer(term, token);
		}

		function runServer(term, token) {
			ctrl = window.AbortController ? new AbortController() : null;
			fetchResults(term, ctrl ? ctrl.signal : undefined).then(function(data) {
				if (token !== seq) return;
				show(term, data);
			}).catch(function(err) {
				if ((err && err.name === 'AbortError') || token !== seq) return;
				renderError(term);
				open();
			});
		}

		function currentTerm() {
			return input.value.trim().replace(/\s+/g, ' ');
		}

		input.addEventListener('input', function() {
			var term = currentTerm();
			clearTimeout(timer);
			seq++;
			if (ctrl) ctrl.abort();
			if (term.length < MIN) {
				reset();
				return;
			}
			// With the index there is nothing to wait for: no typing delay.
			if (!idxFailed) {
				run(term);
				return;
			}
			timer = setTimeout(function() {
				run(term);
			}, cache.has(term.toLowerCase()) ? 0 : DELAY);
		});

		input.addEventListener('focus', function() {
			if (currentTerm().length >= MIN && panel.firstChild) open();
		});

		input.addEventListener('keydown', function(e) {
			var n = options.length;
			if (e.key === 'ArrowDown') {
				if (panel.hidden) {
					if (!panel.firstChild || currentTerm().length < MIN) return;
					open();
				}
				if (!n) return;
				e.preventDefault();
				setActive(active + 1 >= n ? 0 : active + 1);
			} else if (e.key === 'ArrowUp') {
				if (panel.hidden || !n) return;
				e.preventDefault();
				setActive(active <= 0 ? n - 1 : active - 1);
			} else if (e.key === 'Enter') {
				if (!panel.hidden && active > -1 && options[active]) {
					e.preventDefault();
					window.location.href = options[active].href;
				}
			} else if (e.key === 'Escape') {
				if (!panel.hidden) close();
			}
		});

		// Keep focus in the field when clicking inside the panel (Safari), and close when focus leaves.
		panel.addEventListener('mousedown', function(e) {
			if (!e.target.closest('a, button')) e.preventDefault();
			else if (e.target.closest('.ssx-results__retry')) e.preventDefault();
		});

		function onFocusOut(e) {
			var next = e.relatedTarget;
			if (next && (form.contains(next) || panel.contains(next))) return;
			close();
		}
		form.addEventListener('focusout', onFocusOut);
		panel.addEventListener('focusout', onFocusOut);

		if (clearBtn) clearBtn.addEventListener('click', reset);
	}

	var f1 = document.getElementById('ssxSearch'),
		i1 = document.getElementById('ssxSearchInput');
	if (f1 && i1) attach(f1, i1, false, document.getElementById('ssxSearchClear'));

	var f2 = document.getElementById('ssxmSearch'),
		i2 = document.getElementById('ssxmSearchInput');
	if (f2 && i2) attach(f2, i2, true, null);
})();
