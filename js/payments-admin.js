/* Payments admin page (inc/payments-admin.php). No dependencies. */
(function () {
	'use strict';

	var C = window.WholesalePayments;
	var root = document.getElementById('wpa');
	if (!C || !root) {
		return;
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
	var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
	var esc = function (value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	};
	var usd = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
	var usd0 = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
	var money = function (v) { return usd.format(Number(v) || 0); };
	var moneyShort = function (v) {
		v = Number(v) || 0;
		return Math.abs(v) >= 10000 ? '$' + (v / 1000).toFixed(v >= 100000 ? 0 : 1) + 'k' : usd0.format(v);
	};
	var store = {
		get: function (k) { try { return window.localStorage.getItem('wpa_' + k); } catch (e) { return null; } },
		set: function (k, v) { try { window.localStorage.setItem('wpa_' + k, v); } catch (e) { /* private mode */ } }
	};
	var dayMs = 86400000;
	var parseDay = function (s) { var p = String(s).split('-'); return Date.UTC(+p[0], +p[1] - 1, +p[2]); };
	var fmtDay = function (ms) { return new Date(ms).toISOString().slice(0, 10); };
	var addDays = function (s, n) { return fmtDay(parseDay(s) + n * dayMs); };
	var shortDate = function (s) {
		var d = new Date(parseDay(s));
		return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'UTC' });
	};
	var initials = function (name) {
		var parts = String(name || '').replace(/[^A-Za-z\s]/g, '').trim().split(/\s+/);
		return ((parts[0] || '?').charAt(0) + (parts.length > 1 ? parts[parts.length - 1].charAt(0) : '')).toUpperCase();
	};
	var collected = function (status) { return ['paid', 'paid_offline', 'needs_review', 'partially_refunded', 'refunded'].indexOf(status) !== -1; };

	var post = function (action, data) {
		var body = new FormData();
		body.append('action', action);
		Object.keys(data || {}).forEach(function (key) {
			var value = data[key];
			if (Array.isArray(value)) {
				value.forEach(function (v) { body.append(key + '[]', v); });
			} else if (value !== undefined && value !== null) {
				body.append(key, value);
			}
		});
		return fetch(C.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Unexpected server response (' + r.status + ').' }; }); })
			.then(function (json) {
				var ok = json.success === true || json.ok === true;
				var payload = json.data || json;
				return {
					ok: ok,
					data: payload,
					raw: json,
					message: (payload && (payload.message || payload.error)) || json.message || json.error || (ok ? 'Done.' : 'Something went wrong.')
				};
			})
			.catch(function () { return { ok: false, data: {}, raw: {}, message: 'Network error. Check your connection and try again.' }; });
	};
	var pa = function (doAction, data) {
		return post('wholesale_pa_action', Object.assign({ nonce: C.nonce, do: doAction }, data || {}));
	};

	var toastEl;
	var toastTimer;
	var toast = function (message, isError) {
		if (!toastEl) {
			toastEl = document.createElement('div');
			toastEl.className = 'wpa-toast';
			toastEl.setAttribute('role', 'status');
			document.body.appendChild(toastEl);
		}
		toastEl.textContent = message;
		toastEl.classList.toggle('is-error', !!isError);
		toastEl.classList.add('is-on');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { toastEl.classList.remove('is-on'); }, isError ? 6000 : 3200);
	};
	var copyText = function (text, btn) {
		var done = function () {
			toast('Copied');
			if (btn) {
				btn.classList.add('is-done');
				setTimeout(function () { btn.classList.remove('is-done'); }, 1200);
			}
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
		} else {
			fallbackCopy(text);
			done();
		}
	};
	var fallbackCopy = function (text) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		try { document.execCommand('copy'); } catch (e) { /* ignore */ }
		document.body.removeChild(ta);
	};
	var badge = function (statusKey) {
		var s = C.statuses[statusKey] || C.statuses.none;
		return '<span class="wpa-badge wpa-badge--' + esc(s.tone) + '">' + esc(s.label) + '</span>';
	};
	var gw = function (row) {
		var key = row.gateway || 'none';
		return '<span class="wpa-gw wpa-gw--' + esc(key) + '"><i></i>' + esc(row.method && row.method !== '—' ? row.method : 'No payment') + (row.mode === 'test' ? '<span class="wpa-test">Test</span>' : '') + '</span>';
	};

	// ------------------------------------------------------------------
	// State
	// ------------------------------------------------------------------

	var data = C.data;
	var state = {
		tab: C.tab,
		range: store.get('range') || '30',
		from: store.get('from') || '',
		to: store.get('to') || '',
		q: '',
		status: '',
		gateway: '',
		source: '',
		min: '',
		max: '',
		sort: { key: 'ts', dir: 'desc' },
		page: 1,
		per: +(store.get('per') || 25),
		selected: {}
	};

	var rangeBounds = function () {
		var today = C.today;
		var t = parseDay(today);
		var d = new Date(t);
		switch (state.range) {
			case '7': return { from: addDays(today, -6), to: today, days: 7 };
			case '90': return { from: addDays(today, -89), to: today, days: 90 };
			case 'mtd': return { from: today.slice(0, 8) + '01', to: today };
			case 'lm': {
				var first = Date.UTC(d.getUTCFullYear(), d.getUTCMonth() - 1, 1);
				var last = Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), 0);
				return { from: fmtDay(first), to: fmtDay(last) };
			}
			case 'ytd': return { from: today.slice(0, 4) + '-01-01', to: today };
			case 'all': {
				var min = today;
				data.rows.forEach(function (r) { if (r.day < min) { min = r.day; } });
				return { from: min, to: today, all: true };
			}
			case 'custom': {
				var f = state.from || addDays(today, -29);
				var to = state.to || today;
				return f <= to ? { from: f, to: to } : { from: to, to: f };
			}
			default: return { from: addDays(today, -29), to: today };
		}
	};
	var inRange = function (day, b) { return day >= b.from && day <= b.to; };

	// ------------------------------------------------------------------
	// Tabs
	// ------------------------------------------------------------------

	var views = { overview: $('[data-view="overview"]'), list: $('[data-view="list"]') };
	var setTab = function (tab, push) {
		state.tab = tab;
		state.page = 1;
		state.selected = {};
		state.sort = { key: tab === 'requests' ? 'ts' : 'ts', dir: 'desc' };
		$$('.wpa-tab').forEach(function (a) {
			var on = a.getAttribute('data-tab') === tab;
			a.classList.toggle('is-active', on);
			a.setAttribute('aria-selected', on ? 'true' : 'false');
		});
		views.overview.hidden = tab !== 'overview';
		views.list.hidden = tab === 'overview';
		var paymentFilters = tab === 'all' || tab === 'attention';
		['#wpa-f-status', '#wpa-f-gateway', '#wpa-f-source', '.wpa-amount-filter'].forEach(function (sel) {
			$(sel).hidden = !paymentFilters;
			$(sel).style.display = paymentFilters ? '' : 'none';
		});
		if (push) {
			var url = new URL(window.location.href);
			url.searchParams.set('page', 'wholesale-payments' + (tab === 'overview' ? '' : '-' + tab));
			url.hash = '';
			window.history.replaceState({}, '', url.toString());
			// Keep the WP submenu highlight in step.
			var page = url.searchParams.get('page');
			$$('#toplevel_page_wholesale-payments .wp-submenu li').forEach(function (li) {
				var a = li.querySelector('a');
				var on = !!a && (a.getAttribute('href') || '').split('page=')[1] === page;
				li.classList.toggle('current', on);
				if (a) { a.classList.toggle('current', on); }
			});
		}
		render();
	};
	$$('.wpa-tab').forEach(function (a) {
		a.addEventListener('click', function (e) {
			e.preventDefault();
			setTab(a.getAttribute('data-tab'), true);
		});
	});
	document.addEventListener('click', function (e) {
		var go = e.target.closest('[data-goto]');
		if (go && root.contains(go)) {
			e.preventDefault();
			setTab(go.getAttribute('data-goto'), true);
		}
	});

	// ------------------------------------------------------------------
	// Overview
	// ------------------------------------------------------------------

	var periodTotals = function (b) {
		var gross = 0;
		var count = 0;
		var refunded = 0;
		data.rows.forEach(function (r) {
			if (collected(r.status) && inRange(r.day, b)) {
				gross += r.charged;
				count++;
			}
		});
		data.refunds.forEach(function (f) {
			if (f.result === 'approved' && inRange(f.day, b)) {
				refunded += f.amount;
			}
		});
		return { gross: gross, count: count, refunded: refunded, net: gross - refunded };
	};

	var delta = function (now, before) {
		if (!before) {
			return '';
		}
		var pct = Math.round(((now - before) / Math.abs(before)) * 100);
		return '<span class="wpa-delta ' + (pct >= 0 ? 'is-up' : 'is-down') + '">' + (pct >= 0 ? '▲ ' : '▼ ') + Math.abs(pct) + '%</span>';
	};

	var renderOverview = function () {
		var b = rangeBounds();
		var t = periodTotals(b);
		var prev = null;
		if (!b.all) {
			var len = Math.round((parseDay(b.to) - parseDay(b.from)) / dayMs) + 1;
			prev = periodTotals({ from: addDays(b.from, -len), to: addDays(b.from, -1) });
		}
		var owed = 0;
		var owedCount = 0;
		var attention = 0;
		var unverified = 0;
		data.rows.forEach(function (r) {
			if (r.owed) { owed += r.owed; owedCount++; }
			if (r.flags.length) { attention++; }
			if (r.status === 'needs_review') { unverified += r.charged; }
		});
		var openRequests = data.requests.filter(function (q) { return q.status !== 'draft'; });
		openRequests.forEach(function (q) { owed += q.amount; owedCount++; });

		var kpi = function (label, icon, color, value, sub, go) {
			return '<div class="wpa-kpi" style="--wpa-kpi:' + color + '"' + (go ? ' data-goto="' + go + '" role="link" tabindex="0"' : '') + '>'
				+ '<p class="wpa-kpi__label"><span class="dashicons dashicons-' + icon + '" aria-hidden="true"></span>' + esc(label) + '</p>'
				+ '<p class="wpa-kpi__value">' + value + '</p><p class="wpa-kpi__sub">' + sub + '</p></div>';
		};
		$('#wpa-kpis').innerHTML = [
			kpi('Collected', 'money-alt', '#13795b', esc(money(t.gross)), '<b>' + t.count + '</b> payment' + (t.count === 1 ? '' : 's') + (prev ? ' ' + delta(t.gross, prev.gross) : ''), 'all'),
			kpi('Refunded', 'undo', '#c0362c', esc(money(t.refunded)), (t.gross ? '<b>' + (t.refunded / t.gross * 100).toFixed(1) + '%</b> of collected' : 'No payments in range'), 'refunds'),
			kpi('Net revenue', 'chart-line', '#1287b5', esc(money(t.net)), prev ? 'vs ' + esc(money(prev.net)) + ' before ' + delta(t.net, prev.net) : 'All time'),
			kpi('Average payment', 'cart', '#0d2e4d', esc(money(t.count ? t.gross / t.count : 0)), 'Per paid order'),
			kpi('Awaiting payment', 'clock', '#a15c07', esc(money(owed)), '<b>' + owedCount + '</b> unpaid order' + (owedCount === 1 ? '' : 's') + ' & requests', openRequests.length ? 'requests' : 'attention'),
			kpi('Needs attention', 'warning', attention ? '#c0362c' : '#13795b', String(attention), unverified ? '<b>' + esc(money(unverified)) + '</b> unverified' : (attention ? 'Open to review' : 'All clear'), 'attention')
		].join('');

		renderChart(b);
		renderMethods(b);

		var flagged = data.rows.filter(function (r) { return r.flags.length; }).slice(0, 6);
		$('#wpa-attention-mini').innerHTML = flagged.length ? '<ul class="wpa-list">' + flagged.map(function (r) {
			return '<li data-open="' + r.id + '"><span class="wpa-avatar">' + esc(initials(r.customer)) + '</span><div class="wpa-list__main"><strong>#' + esc(r.number) + ' · ' + esc(r.customer) + '</strong><small>' + r.flags.map(esc).join(' · ') + '</small></div><div class="wpa-list__amt">' + esc(money(r.charged || r.total)) + '<br>' + badge(r.status) + '</div></li>';
		}).join('') + '</ul>' : '<div class="wpa-empty"><span class="dashicons dashicons-yes-alt"></span>Nothing needs attention. Every payment is verified.</div>';

		var latest = data.rows.filter(function (r) { return collected(r.status); }).sort(function (a, z) { return z.ts - a.ts; }).slice(0, 6);
		$('#wpa-latest').innerHTML = latest.length ? '<ul class="wpa-list">' + latest.map(function (r) {
			return '<li data-open="' + r.id + '"><span class="wpa-avatar">' + esc(initials(r.customer)) + '</span><div class="wpa-list__main"><strong>' + esc(r.customer) + '</strong><small>#' + esc(r.number) + ' · ' + esc(r.method) + ' · ' + esc(r.when) + '</small></div><div class="wpa-list__amt">' + esc(money(r.charged)) + '<br>' + badge(r.status) + '</div></li>';
		}).join('') + '</ul>' : '<div class="wpa-empty"><span class="dashicons dashicons-money-alt"></span>No payments yet.</div>';
	};

	var renderChart = function (b) {
		var el = $('#wpa-chart');
		var days = Math.round((parseDay(b.to) - parseDay(b.from)) / dayMs) + 1;
		var unit = days <= 92 ? 1 : (days <= 400 ? 7 : 30);
		var buckets = [];
		var index = {};
		for (var day = b.from; day <= b.to; day = addDays(day, unit)) {
			index[day] = buckets.length;
			buckets.push({ day: day, end: unit === 1 ? day : (addDays(day, unit - 1) > b.to ? b.to : addDays(day, unit - 1)), in: 0, out: 0, n: 0 });
		}
		var find = function (d) {
			var i = Math.floor((parseDay(d) - parseDay(b.from)) / (unit * dayMs));
			return buckets[i];
		};
		data.rows.forEach(function (r) {
			if (collected(r.status) && inRange(r.day, b)) {
				var k = find(r.day);
				if (k) { k.in += r.charged; k.n++; }
			}
		});
		data.refunds.forEach(function (f) {
			if (f.result === 'approved' && inRange(f.day, b)) {
				var k = find(f.day);
				if (k) { k.out += f.amount; }
			}
		});
		var maxIn = Math.max.apply(null, buckets.map(function (k) { return k.in; }).concat([1]));
		var maxOut = Math.max.apply(null, buckets.map(function (k) { return k.out; }).concat([0]));
		var W = Math.max(el.clientWidth, 280);
		var H = el.clientHeight || 240;
		var padL = 46;
		var padR = 6;
		var padT = 10;
		var padB = 24;
		var plotH = H - padT - padB;
		var outShare = maxOut ? Math.min(0.35, maxOut / (maxIn + maxOut)) : 0;
		var zeroY = padT + plotH * (1 - outShare);
		var scaleIn = (zeroY - padT) / maxIn;
		var scaleOut = maxOut ? (padT + plotH - zeroY) / maxOut : 0;
		var bw = (W - padL - padR) / buckets.length;
		var barW = Math.max(2, Math.min(28, bw * 0.68));
		var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="Net collected per ' + (unit === 1 ? 'day' : unit === 7 ? 'week' : 'month') + '"><g class="grid">';
		for (var g = 0; g <= 3; g++) {
			var v = maxIn * g / 3;
			var y = zeroY - v * scaleIn;
			svg += '<line x1="' + padL + '" x2="' + (W - padR) + '" y1="' + y + '" y2="' + y + '"/><text x="' + (padL - 8) + '" y="' + (y + 3) + '" text-anchor="end">' + esc(moneyShort(v)) + '</text>';
		}
		svg += '</g>';
		var labelEvery = Math.ceil(buckets.length / Math.max(2, Math.floor((W - padL) / 64)));
		buckets.forEach(function (k, i) {
			var x = padL + i * bw + (bw - barW) / 2;
			var hIn = k.in * scaleIn;
			var hOut = k.out * scaleOut;
			svg += '<g class="col" data-i="' + i + '"><rect class="hit" x="' + (padL + i * bw) + '" y="' + padT + '" width="' + bw + '" height="' + plotH + '"/>';
			if (hIn > 0) { svg += '<rect class="bar-in" x="' + x + '" y="' + (zeroY - hIn) + '" width="' + barW + '" height="' + hIn + '" rx="' + Math.min(4, barW / 3) + '"/>'; }
			if (hOut > 0) { svg += '<rect class="bar-out" x="' + x + '" y="' + zeroY + '" width="' + barW + '" height="' + hOut + '" rx="' + Math.min(4, barW / 3) + '"/>'; }
			svg += '</g>';
			if (i % labelEvery === 0) {
				svg += '<g class="axis"><text x="' + (padL + i * bw + bw / 2) + '" y="' + (H - 6) + '" text-anchor="middle">' + esc(shortDate(k.day)) + '</text></g>';
			}
		});
		svg += '<line x1="' + padL + '" x2="' + (W - padR) + '" y1="' + zeroY + '" y2="' + zeroY + '" stroke="#c3ccd7"/></svg><div class="wpa-tip" hidden></div>';
		el.innerHTML = svg;
		var tip = $('.wpa-tip', el);
		$$('.col', el).forEach(function (g) {
			g.addEventListener('mouseenter', function () {
				var k = buckets[+g.getAttribute('data-i')];
				var rect = g.querySelector('.hit');
				tip.innerHTML = '<b>' + esc(shortDate(k.day) + (k.end !== k.day ? ' – ' + shortDate(k.end) : '')) + '</b>Collected ' + esc(money(k.in)) + ' (' + k.n + ')' + (k.out ? '<br>Refunded −' + esc(money(k.out)) : '') + '<br>Net ' + esc(money(k.in - k.out));
				tip.hidden = false;
				var cx = (+rect.getAttribute('x') + +rect.getAttribute('width') / 2) / W * el.clientWidth;
				tip.style.left = Math.min(Math.max(cx, 70), el.clientWidth - 70) + 'px';
				tip.style.top = Math.max(0, (zeroY - k.in * scaleIn) / H * el.clientHeight - 8) + 'px';
			});
			g.addEventListener('mouseleave', function () { tip.hidden = true; });
		});
	};

	var renderMethods = function (b) {
		var groups = {};
		var total = 0;
		data.rows.forEach(function (r) {
			if (collected(r.status) && inRange(r.day, b)) {
				var key = r.methodGroup || 'Other';
				groups[key] = groups[key] || { sum: 0, n: 0 };
				groups[key].sum += r.charged;
				groups[key].n++;
				total += r.charged;
			}
		});
		var list = Object.keys(groups).map(function (k) { return { name: k, sum: groups[k].sum, n: groups[k].n }; }).sort(function (a, z) { return z.sum - a.sum; });
		$('#wpa-methods').innerHTML = list.length ? '<div class="wpa-methods">' + list.map(function (m) {
			var pct = total ? m.sum / total * 100 : 0;
			return '<div class="wpa-method"><div class="wpa-method__top"><strong>' + esc(m.name) + '</strong><span>' + esc(money(m.sum)) + ' · ' + m.n + ' · ' + pct.toFixed(0) + '%</span></div><div class="wpa-method__bar"><i style="width:' + pct.toFixed(1) + '%"></i></div></div>';
		}).join('') + '</div>' : '<div class="wpa-empty"><span class="dashicons dashicons-chart-pie"></span>No payments in this range.</div>';
	};

	// ------------------------------------------------------------------
	// Lists
	// ------------------------------------------------------------------

	var columns = {
		payments: [
			{ key: 'chk', label: '', cls: 'chk' },
			{ key: 'number', label: 'Order', sort: 'ts' },
			{ key: 'customer', label: 'Customer', sort: 'customer' },
			{ key: 'charged', label: 'Amount', sort: 'amount', cls: 'num' },
			{ key: 'status', label: 'Payment', sort: 'status' },
			{ key: 'method', label: 'Method', sort: 'method', cls: 'hide-sm' },
			{ key: 'source', label: 'Source', sort: 'source', cls: 'hide-sm' },
			{ key: 'orderStatus', label: 'Order status', sort: 'orderStatus', cls: 'hide-sm' },
			{ key: 'act', label: '', cls: 't-actions' }
		],
		refunds: [
			{ key: 'when', label: 'Date', sort: 'ts' },
			{ key: 'number', label: 'Order', sort: 'number' },
			{ key: 'customer', label: 'Customer', sort: 'customer' },
			{ key: 'amount', label: 'Amount', sort: 'amount', cls: 'num' },
			{ key: 'method', label: 'Type', sort: 'method', cls: 'hide-sm' },
			{ key: 'result', label: 'Result', sort: 'result' },
			{ key: 'reference', label: 'Reference', cls: 'hide-sm' },
			{ key: 'by', label: 'By', sort: 'by', cls: 'hide-sm' }
		],
		requests: [
			{ key: 'number', label: 'Request', sort: 'ts' },
			{ key: 'customer', label: 'Customer', sort: 'customer' },
			{ key: 'amount', label: 'Amount', sort: 'amount', cls: 'num' },
			{ key: 'status', label: 'Status', sort: 'status' },
			{ key: 'sent', label: 'Sent / viewed', cls: 'hide-sm' },
			{ key: 'due', label: 'Due', cls: 'hide-sm' },
			{ key: 'act', label: '', cls: 't-actions' }
		],
		failed: [
			{ key: 'when', label: 'Date', sort: 'ts' },
			{ key: 'source', label: 'Where', sort: 'source' },
			{ key: 'customer', label: 'Customer', sort: 'customer' },
			{ key: 'amount', label: 'Amount', sort: 'amount', cls: 'num' },
			{ key: 'message', label: 'Reason', cls: 'hide-sm' },
			{ key: 'act', label: '', cls: 't-actions' }
		]
	};
	var listKind = function () {
		return state.tab === 'refunds' ? 'refunds' : state.tab === 'requests' ? 'requests' : state.tab === 'failed' ? 'failed' : 'payments';
	};

	var haystack = function (r) {
		return [r.number, r.customer, r.company, r.email, r.phone, r.txn, r.ref, r.approval, r.last4, r.method, r.source, r.title, r.reference, r.reason, r.message, r.label, r.total, r.charged, r.amount].join(' ').toLowerCase();
	};

	var filtered = function () {
		var kind = listKind();
		var b = rangeBounds();
		var q = state.q.trim().toLowerCase().replace(/^#/, '');
		var items;
		if (kind === 'refunds') {
			items = data.refunds.filter(function (f) { return inRange(f.day, b); });
		} else if (kind === 'requests') {
			items = data.requests.slice();
		} else if (kind === 'failed') {
			items = data.failed.filter(function (f) { return inRange(f.day, b); });
		} else if (state.tab === 'attention') {
			items = data.rows.filter(function (r) { return r.flags.length; });
		} else {
			items = data.rows.filter(function (r) { return inRange(r.day, b); });
		}
		if (kind === 'payments') {
			var min = parseFloat(state.min);
			var max = parseFloat(state.max);
			items = items.filter(function (r) {
				var amt = r.charged || r.total;
				return (!state.status || r.status === state.status)
					&& (!state.gateway || (r.gateway || 'none') === state.gateway)
					&& (!state.source || r.source.indexOf(state.source) === 0)
					&& (isNaN(min) || amt >= min)
					&& (isNaN(max) || amt <= max);
			});
		}
		if (q) {
			var terms = q.split(/\s+/);
			items = items.filter(function (r) {
				var h = haystack(r);
				return terms.every(function (t) { return h.indexOf(t) !== -1; });
			});
		}
		var key = state.sort.key;
		var dir = state.sort.dir === 'asc' ? 1 : -1;
		var val = function (r) {
			if (key === 'amount') { return r.amount != null ? r.amount : (r.charged || r.total); }
			return r[key];
		};
		items.sort(function (a, z) {
			var x = val(a);
			var y = val(z);
			if (typeof x === 'number' && typeof y === 'number') { return (x - y) * dir; }
			return String(x || '').localeCompare(String(y || '')) * dir;
		});
		return items;
	};

	var cell = function (kind, col, r) {
		switch (kind + ':' + col.key) {
			case 'payments:chk':
				return '<input type="checkbox" data-sel="' + r.id + '"' + (state.selected[r.id] ? ' checked' : '') + ' aria-label="Select order ' + esc(r.number) + '">';
			case 'payments:number':
				return '<span class="t-order"><strong>#' + esc(r.number) + '</strong><span class="t-sub">' + esc(r.when) + '</span></span>';
			case 'payments:customer':
			case 'refunds:customer':
			case 'requests:customer':
			case 'failed:customer':
				return '<div class="t-cust"><span class="wpa-avatar">' + esc(initials(r.customer)) + '</span><div><strong>' + esc(r.customer) + '</strong><span class="t-sub">' + esc(r.email || r.company || '') + '</span></div></div>';
			case 'payments:charged':
				var amt = collected(r.status) ? r.charged : r.total;
				return '<span class="t-amt">' + esc(money(amt)) + '</span>' + (r.refunded ? '<span class="t-sub t-neg">−' + esc(money(r.refunded)) + ' refunded</span>' : (!collected(r.status) && r.total ? '<span class="t-sub">unpaid</span>' : ''));
			case 'payments:status':
				return badge(r.status) + (r.flags.length ? '<div>' + r.flags.map(function (f) { return '<span class="wpa-flag">' + esc(f) + '</span>'; }).join('') + '</div>' : '');
			case 'payments:method':
				return gw(r) + (r.txn ? '<span class="t-sub"><code>' + esc(r.txn.length > 22 ? r.txn.slice(0, 10) + '…' + r.txn.slice(-8) : r.txn) + '</code></span>' : '');
			case 'payments:source':
				return esc(r.source);
			case 'payments:orderStatus':
				return '<span class="wpa-badge wpa-badge--' + esc(r.orderTone) + '">' + esc(r.orderStatus) + '</span>';
			case 'payments:act':
				return '<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" data-open="' + r.id + '">Details</button>';

			case 'refunds:when':
				return esc(r.when);
			case 'refunds:number':
				return '<span class="t-order"><strong>#' + esc(r.number) + '</strong></span>';
			case 'refunds:amount':
				return '<span class="t-amt t-neg">−' + esc(money(r.amount)) + '</span>';
			case 'refunds:method':
				return esc(r.method) + '<span class="t-sub">' + esc(r.gateway) + '</span>';
			case 'refunds:result':
				var tone = { approved: 'success', declined: 'danger', unknown: 'warn' }[r.result] || 'muted';
				return '<span class="wpa-badge wpa-badge--' + tone + '">' + esc(r.result ? r.result.charAt(0).toUpperCase() + r.result.slice(1) : '—') + '</span>' + (r.reason || r.message ? '<span class="t-sub" title="' + esc(r.reason || r.message) + '">' + esc((r.reason || r.message).slice(0, 48)) + '</span>' : '');
			case 'refunds:reference':
				return r.reference ? '<code>' + esc(r.reference) + '</code>' : '—';
			case 'refunds:by':
				return esc(r.by || '—') + (r.emailed ? '<span class="t-sub">Customer emailed</span>' : '');

			case 'requests:number':
				return '<span class="t-order"><strong>' + esc(r.number) + '</strong><span class="t-sub">' + esc(r.title || '') + '</span></span>';
			case 'requests:amount':
			case 'failed:amount':
				return '<span class="t-amt">' + esc(money(r.amount)) + '</span>';
			case 'requests:status':
				var rt = { sent: 'info', accepted: 'warn', expired: 'danger', draft: 'muted' }[r.status] || 'muted';
				return '<span class="wpa-badge wpa-badge--' + rt + '">' + esc(r.statusLabel) + '</span>';
			case 'requests:sent':
				return (r.sent ? 'Sent ' + esc(r.sent) : 'Not sent') + '<span class="t-sub">' + (r.viewed ? 'Opened ' + esc(r.viewed) : 'Not opened yet') + '</span>';
			case 'requests:due':
				return esc(r.due || '—');
			case 'requests:act':
				return (r.url ? '<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" data-copy-link="' + esc(r.url) + '">Copy link</button> ' : '')
					+ (r.canSend ? '<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" data-ticket-send="' + r.id + '">' + (r.sent ? 'Resend' : 'Send') + '</button> ' : '')
					+ '<a class="wpa-btn wpa-btn--sm" href="' + esc(r.edit) + '">Open</a>';

			case 'failed:when':
				return esc(r.when);
			case 'failed:source':
				return esc(r.source) + '<span class="t-sub">' + esc(r.label) + '</span>';
			case 'failed:message':
				return '<div class="t-msg">' + esc(r.message || '—') + '</div>';
			case 'failed:act':
				return r.orderId ? '<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" data-open="' + r.orderId + '">Details</button>' : '<a class="wpa-btn wpa-btn--sm wpa-btn--ghost" href="' + esc(r.edit) + '">Open</a>';
		}
		return esc(r[col.key]);
	};

	var renderList = function () {
		var kind = listKind();
		var cols = columns[kind];
		var items = filtered();
		var pages = Math.max(1, Math.ceil(items.length / state.per));
		state.page = Math.min(state.page, pages);
		var slice = items.slice((state.page - 1) * state.per, state.page * state.per);

		var head = '<thead><tr>' + cols.map(function (c) {
			if (c.key === 'chk') {
				var all = slice.length && slice.every(function (r) { return state.selected[r.id]; });
				return '<th class="chk"><input type="checkbox" id="wpa-sel-all"' + (all ? ' checked' : '') + ' aria-label="Select all on this page"></th>';
			}
			var sorted = c.sort && state.sort.key === c.sort ? (state.sort.dir === 'asc' ? ' is-asc' : ' is-desc') : '';
			return '<th class="' + (c.cls || '') + sorted + '"' + (c.sort ? ' data-sort="' + c.sort + '" tabindex="0" aria-sort="' + (sorted ? (state.sort.dir === 'asc' ? 'ascending' : 'descending') : 'none') + '"' : '') + '>' + esc(c.label) + '</th>';
		}).join('') + '</tr></thead>';

		var emptyText = {
			payments: state.tab === 'attention' ? 'Nothing needs attention right now.' : 'No payments match these filters.',
			refunds: 'No refunds in this date range.',
			requests: 'No open payment requests. Create one under Payment Tickets.',
			failed: 'No failed payment attempts in this date range.'
		}[kind];
		var body = slice.length ? slice.map(function (r) {
			var rowId = r.id || r.orderId || '';
			var clickable = kind === 'payments' || kind === 'refunds' || (kind === 'failed' && r.orderId);
			return '<tr' + (clickable ? ' data-row="' + rowId + '"' : '') + (kind === 'payments' && state.selected[r.id] ? ' class="is-selected"' : '') + '>' + cols.map(function (c) {
				return '<td class="' + (c.cls || '') + '">' + cell(kind, c, r) + '</td>';
			}).join('') + '</tr>';
		}).join('') : '<tr><td colspan="' + cols.length + '"><div class="wpa-empty"><span class="dashicons dashicons-search"></span>' + esc(emptyText) + '</div></td></tr>';
		$('#wpa-table').innerHTML = head + '<tbody>' + body + '</tbody>';

		// Summary line.
		var sum = 0;
		var refunded = 0;
		items.forEach(function (r) {
			if (kind === 'payments') {
				sum += collected(r.status) ? r.charged : 0;
				refunded += r.refunded || 0;
			} else {
				sum += r.amount || 0;
			}
		});
		var b = rangeBounds();
		var rangeText = (kind === 'requests' || state.tab === 'attention') ? 'all dates' : (b.all ? 'all time' : shortDate(b.from) + ' – ' + shortDate(b.to));
		var summary = '<span><b>' + items.length + '</b> ' + (kind === 'requests' ? 'request' : kind === 'refunds' ? 'refund' : kind === 'failed' ? 'attempt' : 'payment') + (items.length === 1 ? '' : 's') + ' · ' + esc(rangeText) + '</span>';
		if (kind === 'payments') {
			summary += '<span>Collected <b>' + esc(money(sum)) + '</b></span><span>Refunded <b>' + esc(money(refunded)) + '</b></span><span>Net <b>' + esc(money(sum - refunded)) + '</b></span>';
		} else {
			summary += '<span>Total <b>' + esc(money(sum)) + '</b></span>';
		}
		$('#wpa-summary').innerHTML = summary;

		// Pager.
		var from = items.length ? (state.page - 1) * state.per + 1 : 0;
		$('#wpa-pager').innerHTML = '<span>Showing ' + from + '–' + Math.min(state.page * state.per, items.length) + ' of ' + items.length + '</span>'
			+ '<span class="wpa-pager__btns"><select class="wpa-select" id="wpa-per" aria-label="Rows per page">' + [10, 25, 50, 100, 250].map(function (n) {
				return '<option value="' + n + '"' + (n === state.per ? ' selected' : '') + '>' + n + ' / page</option>';
			}).join('') + '</select>'
			+ '<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" data-page="prev"' + (state.page <= 1 ? ' disabled' : '') + '>‹ Prev</button>'
			+ '<span>Page ' + state.page + ' / ' + pages + '</span>'
			+ '<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" data-page="next"' + (state.page >= pages ? ' disabled' : '') + '>Next ›</button></span>';

		renderBulk();
	};

	var renderBulk = function () {
		var ids = Object.keys(state.selected);
		$('#wpa-bulk').hidden = !ids.length || listKind() !== 'payments';
		$('#wpa-bulk-count').textContent = ids.length;
	};

	var renderCounts = function () {
		var attention = data.rows.filter(function (r) { return r.flags.length; }).length;
		var set = function (tab, n, alert) {
			var el = $('[data-count="' + tab + '"]');
			if (el) {
				el.textContent = n ? String(n) : '';
				el.classList.toggle('is-alert', !!alert && n > 0);
			}
		};
		set('attention', attention, true);
		set('requests', data.requests.filter(function (q) { return q.status !== 'draft'; }).length);
		set('failed', data.failed.length);
		$('#wpa-generated').textContent = data.generated + (data.truncated ? ' (newest orders only)' : '');
	};

	var render = function () {
		renderCounts();
		if (state.tab === 'overview') {
			renderOverview();
		} else {
			renderList();
		}
	};

	// ------------------------------------------------------------------
	// Toolbar + table events
	// ------------------------------------------------------------------

	var fill = function (select, options) {
		select.insertAdjacentHTML('beforeend', options.map(function (o) { return '<option value="' + esc(o[0]) + '">' + esc(o[1]) + '</option>'; }).join(''));
	};
	var statusOptions = Object.keys(C.statuses).map(function (k) { return [k, C.statuses[k].label]; });
	fill($('#wpa-f-status'), statusOptions);
	fill($('#wpa-bulk-status'), statusOptions);
	fill($('#wpa-f-source'), [['Checkout', 'Checkout'], ['Express checkout', 'Express checkout'], ['Payment link', 'Payment link'], ['Payment ticket', 'Payment ticket'], ['Order', 'Manual / no payment']]);

	var rangeSelect = $('#wpa-range');
	var customBox = $('.wpa-range__custom');
	rangeSelect.value = state.range;
	if (rangeSelect.value !== state.range) {
		state.range = '30';
		rangeSelect.value = '30';
	}
	var syncCustom = function () {
		customBox.hidden = state.range !== 'custom';
		if (state.range === 'custom') {
			var b = rangeBounds();
			$('#wpa-from').value = b.from;
			$('#wpa-to').value = b.to;
		}
	};
	syncCustom();
	rangeSelect.addEventListener('change', function () {
		state.range = rangeSelect.value;
		state.page = 1;
		store.set('range', state.range);
		syncCustom();
		render();
	});
	['#wpa-from', '#wpa-to'].forEach(function (sel) {
		$(sel).addEventListener('change', function () {
			state.from = $('#wpa-from').value;
			state.to = $('#wpa-to').value;
			store.set('from', state.from);
			store.set('to', state.to);
			state.page = 1;
			render();
		});
	});

	var debounce;
	$('#wpa-q').addEventListener('input', function (e) {
		clearTimeout(debounce);
		debounce = setTimeout(function () {
			state.q = e.target.value;
			state.page = 1;
			render();
		}, 120);
	});
	[['#wpa-f-status', 'status'], ['#wpa-f-gateway', 'gateway'], ['#wpa-f-source', 'source'], ['#wpa-f-min', 'min'], ['#wpa-f-max', 'max']].forEach(function (pair) {
		$(pair[0]).addEventListener(pair[0].indexOf('min') !== -1 || pair[0].indexOf('max') !== -1 ? 'input' : 'change', function (e) {
			state[pair[1]] = e.target.value;
			state.page = 1;
			render();
		});
	});
	$('#wpa-clear').addEventListener('click', function () {
		state.q = state.status = state.gateway = state.source = state.min = state.max = '';
		['#wpa-q', '#wpa-f-status', '#wpa-f-gateway', '#wpa-f-source', '#wpa-f-min', '#wpa-f-max'].forEach(function (sel) { $(sel).value = ''; });
		state.page = 1;
		render();
	});

	var table = $('#wpa-table');
	table.addEventListener('click', function (e) {
		var th = e.target.closest('th[data-sort]');
		if (th) {
			var key = th.getAttribute('data-sort');
			state.sort = { key: key, dir: state.sort.key === key && state.sort.dir === 'desc' ? 'asc' : 'desc' };
			renderList();
			return;
		}
		if (e.target.id === 'wpa-sel-all') {
			var items = filtered().slice((state.page - 1) * state.per, state.page * state.per);
			items.forEach(function (r) {
				if (e.target.checked) { state.selected[r.id] = true; } else { delete state.selected[r.id]; }
			});
			renderList();
			return;
		}
		var sel = e.target.closest('[data-sel]');
		if (sel) {
			var id = sel.getAttribute('data-sel');
			if (sel.checked) { state.selected[id] = true; } else { delete state.selected[id]; }
			sel.closest('tr').classList.toggle('is-selected', sel.checked);
			renderBulk();
			return;
		}
		var link = e.target.closest('[data-copy-link]');
		if (link) {
			copyText(link.getAttribute('data-copy-link'));
			return;
		}
		var send = e.target.closest('[data-ticket-send]');
		if (send) {
			sendTicket(send);
			return;
		}
		if (e.target.closest('a, button, input, select, label')) {
			if (!e.target.closest('[data-open]')) {
				return;
			}
		}
		var row = e.target.closest('[data-open], tr[data-row]');
		if (row) {
			openDrawer(+(row.getAttribute('data-open') || row.getAttribute('data-row')));
		}
	});
	table.addEventListener('keydown', function (e) {
		var th = e.target.closest('th[data-sort]');
		if (th && (e.key === 'Enter' || e.key === ' ')) {
			e.preventDefault();
			th.click();
		}
	});
	root.addEventListener('click', function (e) {
		var open = e.target.closest('.wpa-list [data-open]');
		if (open) {
			openDrawer(+open.getAttribute('data-open'));
		}
		var pg = e.target.closest('[data-page]');
		if (pg) {
			state.page += pg.getAttribute('data-page') === 'next' ? 1 : -1;
			renderList();
			$('.wpa-table-wrap').scrollIntoView({ block: 'nearest' });
		}
	});
	root.addEventListener('keydown', function (e) {
		if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('.wpa-kpi[data-goto]')) {
			e.preventDefault();
			setTab(e.target.getAttribute('data-goto'), true);
		}
	});
	root.addEventListener('change', function (e) {
		if (e.target.id === 'wpa-per') {
			state.per = +e.target.value;
			store.set('per', state.per);
			state.page = 1;
			renderList();
		}
	});

	var sendTicket = function (btn) {
		if (!window.confirm('Email this payment request to the customer now?')) {
			return;
		}
		btn.classList.add('is-busy');
		pa('ticket_send', { ticket: btn.getAttribute('data-ticket-send') }).then(function (res) {
			btn.classList.remove('is-busy');
			toast(res.message, !res.ok);
			if (res.ok) {
				reloadData();
			}
		});
	};

	// Bulk.
	$('#wpa-bulk-apply').addEventListener('click', function () {
		var status = $('#wpa-bulk-status').value;
		var ids = Object.keys(state.selected);
		if (!status || !ids.length) {
			toast('Choose a payment status first.', true);
			return;
		}
		if (!window.confirm('Set the payment status of ' + ids.length + ' order' + (ids.length === 1 ? '' : 's') + ' to "' + C.statuses[status].label + '"? This only changes the record; no money is moved.')) {
			return;
		}
		var btn = this;
		btn.classList.add('is-busy');
		pa('bulk_status', { status: status, ids: ids }).then(function (res) {
			btn.classList.remove('is-busy');
			toast(res.message, !res.ok);
			if (res.ok) {
				state.selected = {};
				reloadData();
			}
		});
	});
	$('#wpa-bulk-clear').addEventListener('click', function () {
		state.selected = {};
		renderList();
	});

	// ------------------------------------------------------------------
	// CSV export
	// ------------------------------------------------------------------

	var csv = function (rows, cols) {
		var cellText = function (v) {
			v = v == null ? '' : String(v);
			if (/^[=+\-@]/.test(v)) { v = "'" + v; } // Spreadsheet formula injection.
			return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
		};
		return [cols.map(function (c) { return cellText(c[0]); }).join(',')].concat(rows.map(function (r) {
			return cols.map(function (c) { return cellText(typeof c[1] === 'function' ? c[1](r) : r[c[1]]); }).join(',');
		})).join('\r\n');
	};
	var exportCsv = function (onlySelected) {
		var kind = state.tab === 'overview' ? 'payments' : listKind();
		var items;
		if (state.tab === 'overview') {
			var b = rangeBounds();
			items = data.rows.filter(function (r) { return inRange(r.day, b); });
		} else {
			items = filtered();
		}
		if (onlySelected) {
			items = items.filter(function (r) { return state.selected[r.id]; });
		}
		var label = function (r) { return (C.statuses[r.status] || C.statuses.none).label; };
		var cols = {
			payments: [['Order', 'number'], ['Date', 'when'], ['Customer', 'customer'], ['Company', 'company'], ['Email', 'email'], ['Phone', 'phone'], ['Order total', 'total'], ['Charged', 'charged'], ['Refunded', 'refunded'], ['Net', 'net'], ['Payment status', label], ['Gateway', 'gatewayLabel'], ['Method', 'method'], ['Card last 4', 'last4'], ['Transaction ID', 'txn'], ['Approval code', 'approval'], ['Reference', 'ref'], ['Mode', 'mode'], ['Source', 'source'], ['Order status', 'orderStatus'], ['Flags', function (r) { return r.flags.join('; '); }]],
			refunds: [['Date', 'when'], ['Order', 'number'], ['Customer', 'customer'], ['Email', 'email'], ['Amount', 'amount'], ['Type', 'method'], ['Gateway', 'gateway'], ['Result', 'result'], ['Reference', 'reference'], ['Reason', 'reason'], ['Message', 'message'], ['By', 'by'], ['Customer emailed', function (r) { return r.emailed ? 'yes' : 'no'; }]],
			requests: [['Request', 'number'], ['Title', 'title'], ['Customer', 'customer'], ['Company', 'company'], ['Email', 'email'], ['Phone', 'phone'], ['Amount', 'amount'], ['Status', 'statusLabel'], ['Created', 'when'], ['Sent', 'sent'], ['Viewed', 'viewed'], ['Due', 'due'], ['Payment link', 'url']],
			failed: [['Date', 'when'], ['Where', 'source'], ['Reference', 'label'], ['Customer', 'customer'], ['Email', 'email'], ['Amount', 'amount'], ['Reason', 'message']]
		}[kind];
		if (!items.length) {
			toast('Nothing to export.', true);
			return;
		}
		var blob = new Blob(['﻿' + csv(items, cols)], { type: 'text/csv;charset=utf-8' });
		var a = document.createElement('a');
		a.href = URL.createObjectURL(blob);
		a.download = 'payments-' + kind + '-' + C.today + '.csv';
		document.body.appendChild(a);
		a.click();
		setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
		toast('Exported ' + items.length + ' row' + (items.length === 1 ? '' : 's') + '.');
	};
	$('#wpa-export').addEventListener('click', function () { exportCsv(false); });
	$('#wpa-bulk-export').addEventListener('click', function () { exportCsv(true); });

	// ------------------------------------------------------------------
	// Data reload
	// ------------------------------------------------------------------

	var reloadData = function () {
		var btn = $('#wpa-reload');
		btn.classList.add('is-busy');
		return post('wholesale_pa_data', { nonce: C.nonce }).then(function (res) {
			btn.classList.remove('is-busy');
			if (res.ok) {
				data = res.data;
				render();
			} else {
				toast(res.message, true);
			}
		});
	};
	$('#wpa-reload').addEventListener('click', reloadData);

	// ------------------------------------------------------------------
	// Detail drawer
	// ------------------------------------------------------------------

	var drawer = $('#wpa-drawer');
	var drawerBody = $('#wpa-drawer-body');
	var drawerActions = $('#wpa-drawer-actions');
	var current = null;
	var lastFocus = null;

	var openDrawer = function (id, keepScroll) {
		if (!id) {
			return;
		}
		if (!drawer.classList.contains('is-open')) {
			lastFocus = document.activeElement;
		}
		drawer.classList.add('is-open');
		drawer.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
		var scroll = keepScroll ? drawerBody.scrollTop : 0;
		if (!keepScroll) {
			$('#wpa-drawer-title').textContent = 'Loading…';
			$('#wpa-drawer-eyebrow').textContent = 'Payment';
			drawerActions.innerHTML = '';
			drawerBody.innerHTML = '<div class="wpa-skeleton is-big"></div><div class="wpa-skeleton"></div><div class="wpa-skeleton" style="width:70%"></div><div class="wpa-skeleton is-big"></div>';
			$('.wpa-drawer__panel').focus();
		}
		if (history.replaceState) {
			history.replaceState({}, '', '#pay-' + id);
		}
		return post('wholesale_pa_detail', { nonce: C.nonce, id: id }).then(function (res) {
			if (!res.ok) {
				drawerBody.innerHTML = '<div class="wpa-empty"><span class="dashicons dashicons-warning"></span>' + esc(res.message) + '</div>';
				return;
			}
			current = res.data.ctx;
			$('#wpa-drawer-eyebrow').textContent = res.data.row.source + ' · ' + (res.data.row.gateway || collected(current.status) ? res.data.row.gatewayLabel : 'Awaiting payment');
			$('#wpa-drawer-title').textContent = 'Order #' + current.number + ' · ' + current.customer;
			drawerBody.innerHTML = res.data.html;
			drawerBody.scrollTop = scroll;
			renderActions();
		});
	};
	var closeDrawer = function () {
		drawer.classList.remove('is-open');
		drawer.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
		current = null;
		if (history.replaceState) {
			history.replaceState({}, '', window.location.pathname + window.location.search);
		}
		if (lastFocus && lastFocus.focus) {
			lastFocus.focus();
		}
	};
	$$('[data-close]', drawer).forEach(function (el) { el.addEventListener('click', closeDrawer); });
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && drawer.classList.contains('is-open') && !modal.open) {
			closeDrawer();
		}
	});
	$('#wpa-print').addEventListener('click', function () {
		document.body.classList.add('wpa-printing');
		window.print();
		setTimeout(function () { document.body.classList.remove('wpa-printing'); }, 500);
	});

	var actionBtn = function (key, label, icon, cls, title) {
		return '<button type="button" class="wpa-btn wpa-btn--sm ' + (cls || 'wpa-btn--ghost') + '" data-act="' + key + '"' + (title ? ' title="' + esc(title) + '"' : '') + '><span class="dashicons dashicons-' + icon + '" aria-hidden="true"></span>' + esc(label) + '</button>';
	};
	var renderActions = function () {
		var c = current;
		var html = '';
		if (c.status === 'needs_review') {
			html += actionBtn('markpaid', 'Mark as paid', 'yes', 'wpa-btn--primary');
		}
		if (c.canVerify) {
			html += actionBtn('verify', 'Check with Converge', 'shield');
		}
		if (c.refund.available && C.canRefund) {
			html += actionBtn('refund', 'Refund', 'undo', 'wpa-btn--danger');
		} else if (c.refund.endpoint && c.charged > 0 && c.remaining >= 0.01) {
			html += '<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" disabled title="' + esc(c.refund.reason) + '"><span class="dashicons dashicons-undo" aria-hidden="true"></span>Refund</button>';
		}
		if (c.payLink) {
			html += actionBtn('paylink', 'Send payment link', 'email-alt', 'wpa-btn--primary');
			html += actionBtn('copylink', 'Copy payment link', 'admin-links');
		}
		if (c.canRecordPayment) {
			html += actionBtn('recordpay', 'Record payment', 'plus-alt2');
		}
		if (c.canRecordRefund) {
			html += actionBtn('recordrefund', 'Record refund', 'backup', '', 'For money returned outside the site (in the gateway, by check, etc.)');
		}
		if (c.canReceipt) {
			html += actionBtn('receipt', 'Email receipt', 'media-text');
		}
		html += actionBtn('status', 'Change status', 'edit');
		html += '<a class="wpa-btn wpa-btn--sm wpa-btn--ghost" href="' + esc(c.edit) + '"><span class="dashicons dashicons-external" aria-hidden="true"></span>Open order</a>';
		drawerActions.innerHTML = html;
	};

	var afterChange = function (res) {
		toast(res.message, !res.ok);
		if (res.ok && current) {
			openDrawer(current.id, true);
			reloadData();
		}
	};

	drawerActions.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-act]');
		if (!btn || !current) {
			return;
		}
		var act = btn.getAttribute('data-act');
		var fn = actions[act];
		if (fn) {
			fn(btn);
		}
	});
	drawerBody.addEventListener('click', function (e) {
		var copy = e.target.closest('[data-copy]');
		if (copy) {
			copyText(copy.getAttribute('data-copy'), copy);
			return;
		}
		var note = e.target.closest('[data-action="note"]');
		if (note && current) {
			var text = $('#wpa-note-text').value.trim();
			if (!text) {
				$('#wpa-note-text').focus();
				return;
			}
			note.classList.add('is-busy');
			pa('note', { id: current.id, text: text }).then(function (res) {
				note.classList.remove('is-busy');
				afterChange(res);
			});
			return;
		}
		var mark = e.target.closest('[data-act="markpaid"]');
		if (mark) {
			actions.markpaid(mark);
		}
	});

	// ------------------------------------------------------------------
	// Modal
	// ------------------------------------------------------------------

	var modal = $('#wpa-modal');
	var modalForm = $('#wpa-modal-form');
	var modalBody = $('#wpa-modal-body');
	var modalError = $('#wpa-modal-error');
	var modalSubmit = $('#wpa-modal-submit');
	var modalHandler = null;

	var showModal = function (opts) {
		$('#wpa-modal-title').textContent = opts.title;
		modalBody.innerHTML = opts.body;
		modalError.hidden = true;
		modalSubmit.textContent = opts.submit || 'Save';
		modalSubmit.className = 'wpa-btn ' + (opts.danger ? 'wpa-btn--danger' : 'wpa-btn--primary');
		modalSubmit.hidden = !!opts.noSubmit;
		modalSubmit.disabled = false;
		modalSubmit.classList.remove('is-busy');
		modalHandler = opts.onSubmit || null;
		if (!modal.open) {
			modal.showModal();
		}
		var first = modalBody.querySelector('input:not([type=hidden]):not([type=radio]):not([type=checkbox]), select, textarea');
		if (first && !opts.noFocus) {
			first.focus();
		}
		if (opts.onOpen) {
			opts.onOpen();
		}
	};
	var modalFail = function (message) {
		modalError.textContent = message;
		modalError.hidden = false;
		modalSubmit.classList.remove('is-busy');
		modalSubmit.disabled = false;
	};
	var closeModal = function () {
		if (modal.open) {
			modal.close();
		}
	};
	$$('[data-modal-close]', modal).forEach(function (b) { b.addEventListener('click', closeModal); });
	modal.addEventListener('click', function (e) {
		if (e.target === modal) {
			closeModal();
		}
	});
	modalForm.addEventListener('submit', function (e) {
		e.preventDefault();
		if (!modalHandler || modalSubmit.disabled) {
			return;
		}
		modalError.hidden = true;
		modalSubmit.disabled = true;
		modalSubmit.classList.add('is-busy');
		var fields = {};
		new FormData(modalForm).forEach(function (v, k) { fields[k] = v; });
		modalHandler(fields);
	});

	var field = function (label, control, hint) {
		return '<label class="wpa-field"><span>' + esc(label) + '</span>' + control + (hint ? '<small>' + esc(hint) + '</small>' : '') + '</label>';
	};
	var moneyInput = function (name, value, max) {
		return '<span class="wpa-money-input"><input type="number" name="' + name + '" step="0.01" min="0.01"' + (max ? ' max="' + max + '"' : '') + ' value="' + (value != null ? Number(value).toFixed(2) : '') + '" inputmode="decimal" required></span>';
	};
	var statusSelect = function (name, selected) {
		return '<select name="' + name + '">' + Object.keys(C.statuses).map(function (k) {
			return '<option value="' + k + '"' + (k === selected ? ' selected' : '') + '>' + esc(C.statuses[k].label) + '</option>';
		}).join('') + '</select>';
	};

	// ------------------------------------------------------------------
	// Actions
	// ------------------------------------------------------------------

	var actions = {};

	actions.markpaid = function () {
		showModal({
			title: 'Mark order #' + current.number + ' as paid',
			body: '<p>Only do this after confirming the payment in ' + esc(current.gatewayLabel) + '. The status changes to <strong>Paid</strong>; no money is moved.</p>' + field('Note (optional)', '<textarea name="note" rows="2" placeholder="e.g. Confirmed in Converge, settled batch 11/12"></textarea>'),
			submit: 'Mark as paid',
			onSubmit: function (f) {
				pa('status', { id: current.id, status: 'paid', note: f.note }).then(function (res) {
					if (!res.ok) { return modalFail(res.message); }
					closeModal();
					afterChange(res);
				});
			}
		});
	};

	actions.status = function () {
		showModal({
			title: 'Change payment status',
			body: '<p>This only changes the record for order #' + esc(current.number) + '. To move money, use Refund or the payment link.</p>' + field('Payment status', statusSelect('status', current.status)) + field('Reason (optional)', '<textarea name="note" rows="2"></textarea>', 'Saved in the order timeline.'),
			submit: 'Update status',
			onSubmit: function (f) {
				if (f.status === current.status) { return modalFail('Pick a different status.'); }
				pa('status', { id: current.id, status: f.status, note: f.note }).then(function (res) {
					if (!res.ok) { return modalFail(res.message); }
					closeModal();
					afterChange(res);
				});
			}
		});
	};

	actions.verify = function (btn) {
		btn.classList.add('is-busy');
		pa('verify', { id: current.id }).then(function (res) {
			btn.classList.remove('is-busy');
			var box = $('#wpa-verify-result');
			if (!res.ok) {
				toast(res.message, true);
				if (box) {
					box.innerHTML = '<div class="wpa-verify is-bad"><h4><span class="dashicons dashicons-warning"></span>' + esc(res.message) + '</h4></div>';
				}
				return;
			}
			var d = res.data;
			box.innerHTML = '<div class="wpa-verify' + (d.ok ? '' : ' is-bad') + '"><h4><span class="dashicons dashicons-' + (d.ok ? 'yes-alt' : 'warning') + '"></span>' + esc(d.message) + '</h4><dl>'
				+ Object.keys(d.fields).map(function (k) { return '<div><dt>' + esc(k) + '</dt><dd>' + esc(d.fields[k]) + '</dd></div>'; }).join('')
				+ '</dl>' + (d.canMarkPaid ? '<p style="margin:10px 0 0"><button type="button" class="wpa-btn wpa-btn--sm wpa-btn--primary" data-act="markpaid">Mark as paid</button></p>' : '') + '</div>';
			box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		});
	};

	actions.recordpay = function () {
		var methods = current.offlineMethods;
		showModal({
			title: 'Record a payment for #' + current.number,
			body: '<p>For money received outside the website: check, cash, Zelle, bank transfer or a card taken by phone. The order becomes <strong>Paid (offline)</strong>.</p>'
				+ '<div class="wpa-row">' + field('Amount received', moneyInput('amount', current.total)) + field('Date received', '<input type="date" name="date" value="' + esc(current.today) + '" max="' + esc(current.today) + '">') + '</div>'
				+ field('Payment method', '<select name="method">' + Object.keys(methods).map(function (k) { return '<option value="' + k + '">' + esc(methods[k]) + '</option>'; }).join('') + '</select>')
				+ field('Reference (optional)', '<input type="text" name="reference" placeholder="Check #, Zelle confirmation, last 4 of card…">')
				+ field('Note (optional)', '<textarea name="note" rows="2"></textarea>'),
			submit: 'Record payment',
			onSubmit: function (f) {
				if (!(parseFloat(f.amount) > 0)) { return modalFail('Enter the amount received.'); }
				pa('record_payment', { id: current.id, amount: f.amount, date: f.date, method: f.method, reference: f.reference, note: f.note }).then(function (res) {
					if (!res.ok) { return modalFail(res.message); }
					closeModal();
					afterChange(res);
				});
			}
		});
	};

	actions.recordrefund = function () {
		showModal({
			title: 'Record a refund for #' + current.number,
			body: '<p>Use this when money was returned <strong>outside the site</strong> (directly in ' + esc(current.gatewayLabel === 'None' ? 'the gateway' : current.gatewayLabel) + ', by check, etc.). The site does <strong>not</strong> move any money.</p>'
				+ '<div class="wpa-row">' + field('Amount refunded', moneyInput('amount', current.remaining, current.remaining), 'Up to ' + money(current.remaining)) + field('Refunded via', '<input type="text" name="channel" placeholder="e.g. Converge, check, Zelle">') + '</div>'
				+ field('Reference (optional)', '<input type="text" name="reference">')
				+ field('Reason', '<textarea name="reason" rows="2"></textarea>')
				+ '<label class="wpa-check"><input type="checkbox" name="notify" value="1"> Email the customer a refund confirmation</label>',
			submit: 'Record refund',
			onSubmit: function (f) {
				var amt = parseFloat(f.amount);
				if (!(amt > 0) || amt > current.remaining + 0.001) { return modalFail('Enter an amount between $0.01 and ' + money(current.remaining) + '.'); }
				pa('record_refund', { id: current.id, amount: f.amount, channel: f.channel, reference: f.reference, reason: f.reason, notify: f.notify ? 1 : '' }).then(function (res) {
					if (!res.ok) { return modalFail(res.message); }
					closeModal();
					afterChange(res);
				});
			}
		});
	};

	actions.receipt = function () {
		showModal({
			title: 'Email a payment receipt',
			body: '<p>Sends a branded receipt for order #' + esc(current.number) + ' with the amount, method, transaction ID and items.</p>' + field('Send to', '<input type="email" name="email" value="' + esc(current.email) + '" required>'),
			submit: 'Send receipt',
			onSubmit: function (f) {
				pa('receipt', { id: current.id, email: f.email }).then(function (res) {
					if (!res.ok) { return modalFail(res.message); }
					closeModal();
					afterChange(res);
				});
			}
		});
	};

	var payLinkCall = function (mode, extra) {
		return post('wholesale_order_pay_link', Object.assign({ order: current.id, nonce: current.payLink.nonce, mode: mode }, extra || {}));
	};
	actions.copylink = function (btn) {
		btn.classList.add('is-busy');
		payLinkCall('copy').then(function (res) {
			btn.classList.remove('is-busy');
			if (res.ok && res.data.url) {
				copyText(res.data.url);
				current.payLink.url = res.data.url;
			} else {
				toast(res.message, true);
			}
		});
	};
	actions.paylink = function () {
		showModal({
			title: 'Send payment link',
			body: '<p>Emails a secure link where the customer pays ' + esc(money(current.total)) + ' for order #' + esc(current.number) + ' by card. The order is marked paid automatically.</p>'
				+ field('Customer email', '<input type="email" name="email" value="' + esc(current.email) + '" required>')
				+ field('Message (optional)', '<textarea name="note" rows="3" placeholder="Shown above the order summary in the email"></textarea>')
				+ (current.payLink.url ? field('Link', '<span class="wpa-linkbox"><input type="text" readonly value="' + esc(current.payLink.url) + '"><button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" data-copy-modal="' + esc(current.payLink.url) + '">Copy</button></span>') : ''),
			submit: 'Send link',
			onSubmit: function (f) {
				payLinkCall('send', { email: f.email, note: f.note }).then(function (res) {
					if (!res.ok) { return modalFail(res.message); }
					closeModal();
					afterChange(res);
				});
			}
		});
	};
	modalBody.addEventListener('click', function (e) {
		var c = e.target.closest('[data-copy-modal]');
		if (c) {
			copyText(c.getAttribute('data-copy-modal'));
		}
	});

	// Refund: Converge asks first whether the charge settled (void vs return); wallets refund directly.
	actions.refund = function () {
		var c = current;
		var r = c.refund;
		var card = c.last4 ? 'card ending ' + c.last4 : 'the original payment method';
		var form = function (method) {
			var voidOnly = method === 'void';
			var intro = r.endpoint === 'converge'
				? (voidOnly
					? '<p class="wpa-alert"><span class="dashicons dashicons-info"></span><span>This payment has not settled yet, so it will be <strong>voided</strong>: the whole charge is cancelled and never reaches the customer\'s statement. Partial refunds are possible after it settles (usually next business day).</span></p>'
					: '<p class="wpa-alert is-ok"><span class="dashicons dashicons-yes-alt"></span><span>The payment has settled, so the money is <strong>returned</strong> to the ' + esc(card) + ' (full or partial).</span></p>')
				: '<p>The refund goes back through ' + esc(c.gatewayLabel) + ' to ' + esc(card) + '.</p>';
			return intro
				+ '<div class="wpa-choice">'
				+ '<label><input type="radio" name="kind" value="full" checked><span><strong>Full refund · ' + esc(money(r.remaining)) + '</strong><small>Everything that is still refundable.</small></span></label>'
				+ (voidOnly ? '' : '<label><input type="radio" name="kind" value="partial"><span><strong>Partial refund</strong><small>Enter an amount up to ' + esc(money(r.remaining)) + '.</small></span></label>')
				+ '</div>'
				+ (voidOnly ? '' : '<div class="wpa-partial" hidden>' + field('Refund amount', moneyInput('amount', '', r.remaining)) + '</div>')
				+ field('Reason (staff only)', '<textarea name="reason" rows="2" placeholder="e.g. Customer cancelled before production"></textarea>')
				+ field('After a full refund, set the order to', '<select name="status"><option value="">Keep current order status</option><option value="refunded">Refunded</option><option value="cancelled">Cancelled</option></select>')
				+ '<label class="wpa-check"><input type="checkbox" name="notify" value="1" checked> Email the customer that the refund was issued</label>'
				+ '<input type="hidden" name="method" value="' + esc(method || 'refund') + '">';
		};
		var bindPartial = function () {
			$$('[name="kind"]', modalBody).forEach(function (radio) {
				radio.addEventListener('change', function () {
					var box = $('.wpa-partial', modalBody);
					if (box) {
						box.hidden = radio.value !== 'partial' || !radio.checked;
						if (!box.hidden) { $('[name="amount"]', box).focus(); }
					}
				});
			});
		};
		var confirmStep = function (f) {
			var amount = f.kind === 'full' ? r.remaining : parseFloat(f.amount);
			if (!(amount >= 0.01) || amount > r.remaining + 0.001) {
				return modalFail('Enter an amount between $0.01 and ' + money(r.remaining) + '.');
			}
			var verb = f.method === 'void' ? 'Void' : 'Refund';
			showModal({
				title: 'Confirm ' + verb.toLowerCase(),
				body: '<div class="wpa-confirm">' + verb + ' <strong>' + esc(money(amount)) + '</strong><br>to ' + esc(card) + ' via ' + esc(c.gatewayLabel) + '<br>Order #' + esc(c.number) + ' · ' + esc(c.customer)
					+ (f.reason ? '<br><em>"' + esc(f.reason) + '"</em>' : '') + (f.notify ? '<br>The customer will be emailed.' : '') + '</div>'
					+ '<p><strong>This moves real money and cannot be undone.</strong></p>',
				submit: verb + ' ' + money(amount),
				danger: true,
				noFocus: true,
				onSubmit: function () {
					var payload = { post_id: c.id, nonce: r.nonce, kind: f.kind, amount: f.kind === 'full' ? '' : amount.toFixed(2), reason: f.reason || '', notify: f.notify ? 1 : '', status: f.status || '' };
					var call = r.endpoint === 'converge'
						? post('wholesale_refund_issue', Object.assign(payload, { method: f.method }))
						: post('sfs_wallet_refund', payload);
					call.then(function (res) {
						if (!res.ok) {
							if (res.raw && res.raw.recheck) {
								return start();
							}
							return modalFail(res.message);
						}
						closeModal();
						var msg = r.endpoint === 'converge'
							? (f.method === 'void' ? 'Payment voided: ' : 'Refunded ') + money(res.raw.amount || amount) + (res.raw.reference ? ' (ref ' + res.raw.reference + ')' : '') + (res.raw.emailed ? '. Customer emailed.' : '.')
							: res.message;
						afterChange({ ok: true, message: msg });
					});
				}
			});
		};
		var start = function () {
			if (r.endpoint !== 'converge') {
				showModal({ title: 'Refund order #' + c.number, body: form('refund'), submit: 'Review refund', danger: true, onOpen: bindPartial, onSubmit: confirmStep });
				return;
			}
			showModal({ title: 'Refund order #' + c.number, body: '<div class="wpa-skeleton"></div><p>Checking the payment with Converge…</p>', noSubmit: true });
			post('wholesale_refund_check', { post_id: c.id, nonce: r.nonce }).then(function (res) {
				if (!res.ok) {
					showModal({ title: 'Refund order #' + c.number, body: '<p class="wpa-alert"><span class="dashicons dashicons-warning"></span><span>' + esc(res.message) + '</span></p>', noSubmit: true });
					return;
				}
				r.remaining = res.raw.remaining;
				showModal({ title: 'Refund order #' + c.number, body: form(res.raw.method), submit: 'Review refund', danger: true, onOpen: bindPartial, onSubmit: confirmStep });
			});
		};
		start();
	};

	// ------------------------------------------------------------------
	// Boot
	// ------------------------------------------------------------------

	var resizeTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function () {
			if (state.tab === 'overview') {
				renderChart(rangeBounds());
			}
		}, 150);
	});

	setTab(state.tab, false);
	var hash = window.location.hash.match(/^#pay-(\d+)$/);
	if (hash) {
		openDrawer(+hash[1]);
	}
})();
