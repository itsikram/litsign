<?php
/**
 * Live refresh for the admin Orders list: a Refresh button plus automatic updates when an order is
 * added or changes (status, payment, tracking). The list is swapped in place, so filters, search and
 * the page number stay, and nothing is replaced while staff are typing, quick editing or have rows ticked.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * A short fingerprint of everything the order list shows. It changes when an order is added, removed,
 * moves status, or its payment, tracking or shipment data changes.
 */
function retails_orders_live_signature()
{
	global $wpdb;
	$posts = $wpdb->get_row(
		"SELECT COUNT(*) AS n, COALESCE(SUM(CRC32(CONCAT_WS('|', ID, post_status, post_date, post_modified_gmt))), 0) AS h
		FROM {$wpdb->posts} WHERE post_type = 'order' AND post_status <> 'auto-draft'",
		ARRAY_A
	);
	$keys = array('_payment_status', '_tracking_number', '_tracking_carrier', '_sfs_shipments', '_sfs_auto_stage', '_staff_note', 'estimate_delivery_time');
	$meta = $wpdb->get_row(
		"SELECT COUNT(*) AS n, COALESCE(SUM(CRC32(CONCAT_WS('|', m.post_id, m.meta_key, m.meta_value))), 0) AS h
		FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
		WHERE p.post_type = 'order' AND p.post_status <> 'auto-draft' AND m.meta_key IN ('" . implode("','", array_map('esc_sql', $keys)) . "')",
		ARRAY_A
	);
	return md5(wp_json_encode(array($posts, $meta)));
}

add_action('wp_ajax_retails_orders_signature', function () {
	check_ajax_referer('retails_orders_live', 'nonce');
	if (!current_user_can('edit_posts')) {
		wp_send_json_error(null, 403);
	}
	wp_send_json_success(array('signature' => retails_orders_live_signature()));
});

/**
 * True on the admin Orders list screen.
 */
function retails_orders_live_screen()
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	return $screen && 'edit-order' === $screen->id;
}

add_action('admin_head-edit.php', function () {
	if (!retails_orders_live_screen()) {
		return;
	}
	?>
	<style>
		.sfs-live { display: inline-flex; align-items: center; gap: 8px; margin-left: 6px; vertical-align: middle; font-size: 13px; color: #50575e; }
		.sfs-live__refresh.page-title-action { display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
		.sfs-live__refresh .dashicons { width: 16px; height: 16px; font-size: 16px; }
		.sfs-live__refresh.is-loading .dashicons { animation: sfs-live-spin .8s linear infinite; }
		.sfs-live__dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #00a32a; }
		.sfs-live.is-off .sfs-live__dot { background: #a7aaad; }
		.sfs-live.is-error .sfs-live__dot { background: #d63638; }
		.sfs-live__auto { display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
		.sfs-live__auto input { margin: 0; }
		#the-list tr.sfs-row-new > * { animation: sfs-live-flash 4s ease-out; }
		@keyframes sfs-live-flash { 0% { background-color: #fff3bf; } 100% { background-color: transparent; } }
		@keyframes sfs-live-spin { to { transform: rotate(360deg); } }
		@media (prefers-reduced-motion: reduce) { .sfs-live__refresh.is-loading .dashicons, #the-list tr.sfs-row-new > * { animation: none; } }
	</style>
	<?php
});

add_action('admin_footer-edit.php', function () {
	if (!retails_orders_live_screen()) {
		return;
	}
	$config = array(
		'ajax' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('retails_orders_live'),
		'signature' => retails_orders_live_signature(),
		'interval' => (int) apply_filters('retails_orders_live_interval', 20) * 1000,
	);
	?>
	<script>
		(function () {
			var config = <?php echo wp_json_encode($config); ?>;
			var heading = document.querySelector('.wrap .page-title-action');
			if (!heading || !document.getElementById('the-list')) {
				return;
			}
			var signature = config.signature;
			var busy = false;
			var timer = null;
			var autoOn = true;
			try { autoOn = localStorage.getItem('sfsOrdersAuto') !== '0'; } catch (e) {}

			var wrap = document.createElement('span');
			wrap.className = 'sfs-live';
			wrap.innerHTML =
				'<button type="button" class="page-title-action sfs-live__refresh"><span class="dashicons dashicons-update" aria-hidden="true"></span> Refresh</button>' +
				'<label class="sfs-live__auto"><input type="checkbox"> Auto-refresh</label>' +
				'<span class="sfs-live__dot" aria-hidden="true"></span><span class="sfs-live__text" role="status" aria-live="polite"></span>';
			heading.insertAdjacentElement('afterend', wrap);
			var button = wrap.querySelector('.sfs-live__refresh');
			var toggle = wrap.querySelector('input');
			var text = wrap.querySelector('.sfs-live__text');
			toggle.checked = autoOn;

			function clock() {
				return new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', second: '2-digit' });
			}

			function setState(message, state) {
				text.textContent = message;
				wrap.classList.toggle('is-off', state === 'off');
				wrap.classList.toggle('is-error', state === 'error');
			}

			function idle(message) {
				setState(message || (autoOn ? 'Live · updated ' : 'Updated ') + clock(), autoOn ? '' : 'off');
			}

			// True while staff are working in the list, so an automatic swap would lose their input.
			function editing() {
				var list = document.getElementById('the-list');
				if (list.querySelector('.inline-edit-row, tr.inline-editor')) { return true; }
				if (list.querySelector('input[type="checkbox"]:checked')) { return true; }
				var active = document.activeElement;
				return !!(active && active !== document.body && /^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName) && active.closest('#posts-filter') && !active.closest('.tablenav-pages'));
			}

			function rowIds(root) {
				return Array.prototype.map.call(root.querySelectorAll('#the-list > tr[id^="post-"]'), function (row) { return row.id; });
			}

			function swap(next, selector, index) {
				var from = next.querySelectorAll(selector)[index || 0];
				var to = document.querySelectorAll(selector)[index || 0];
				if (from && to) { to.replaceWith(from); }
			}

			function reload(manual) {
				if (busy) { return Promise.resolve(false); }
				if (editing()) {
					if (!manual) { return Promise.resolve(false); }
					if (!window.confirm('Refreshing will discard the quick edit or selection in progress. Refresh anyway?')) { return Promise.resolve(false); }
				}
				busy = true;
				button.classList.add('is-loading');
				button.disabled = true;
				var before = rowIds(document);
				return fetch(window.location.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'sfs-orders-live' } })
					.then(function (response) {
						if (!response.ok) { throw new Error('http ' + response.status); }
						return response.text();
					})
					.then(function (html) {
						var next = new DOMParser().parseFromString(html, 'text/html');
						if (!next.getElementById('the-list')) { throw new Error('login'); }
						swap(next, '#the-list');
						swap(next, '.subsubsub');
						swap(next, '.tablenav.top .tablenav-pages');
						swap(next, '.tablenav.bottom .tablenav-pages');
						swap(next, '#menu-posts-order .wp-menu-name');
						document.querySelectorAll('#cb-select-all-1, #cb-select-all-2').forEach(function (box) { box.checked = false; });
						document.querySelectorAll('.column-order_number strong').forEach(function (el) {
							el.textContent = el.textContent.replace(/^#order_/i, '#').toUpperCase();
						});
						var added = rowIds(document).filter(function (id) { return before.indexOf(id) === -1; });
						if (before.length) {
							added.forEach(function (id) {
								var row = document.getElementById(id);
								if (row) { row.classList.add('sfs-row-new'); }
							});
						}
						document.dispatchEvent(new CustomEvent('retails-orders-refreshed'));
						idle(added.length && before.length ? added.length + (added.length === 1 ? ' new order · ' : ' new orders · ') + clock() : '');
						return true;
					})
					.catch(function (error) {
						setState(error.message === 'login' ? 'Session expired — reload the page' : 'Could not refresh — will retry', 'error');
						return false;
					})
					.then(function (done) {
						busy = false;
						button.classList.remove('is-loading');
						button.disabled = false;
						return done;
					});
			}

			function poll() {
				if (!autoOn || document.hidden || busy) { return; }
				var body = new URLSearchParams({ action: 'retails_orders_signature', nonce: config.nonce });
				fetch(config.ajax, { method: 'POST', credentials: 'same-origin', body: body })
					.then(function (response) { return response.json(); })
					.then(function (payload) {
						if (!payload || !payload.success) { throw new Error('bad'); }
						if (payload.data.signature === signature) { idle(); return; }
						return reload(false).then(function (done) {
							// Only remember the new fingerprint once the list was really updated.
							if (done) { signature = payload.data.signature; }
						});
					})
					.catch(function () { setState('Could not check for new orders — will retry', 'error'); });
			}

			function schedule() {
				window.clearInterval(timer);
				timer = autoOn ? window.setInterval(poll, config.interval) : null;
			}

			button.addEventListener('click', function () {
				var body = new URLSearchParams({ action: 'retails_orders_signature', nonce: config.nonce });
				reload(true);
				fetch(config.ajax, { method: 'POST', credentials: 'same-origin', body: body })
					.then(function (response) { return response.json(); })
					.then(function (payload) { if (payload && payload.success) { signature = payload.data.signature; } })
					.catch(function () {});
			});

			toggle.addEventListener('change', function () {
				autoOn = toggle.checked;
				try { localStorage.setItem('sfsOrdersAuto', autoOn ? '1' : '0'); } catch (e) {}
				schedule();
				idle();
			});

			document.addEventListener('visibilitychange', function () {
				if (!document.hidden) { poll(); }
			});

			idle();
			schedule();
		})();
	</script>
	<?php
});
