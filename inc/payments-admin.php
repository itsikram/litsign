<?php
/**
 * Payments: one admin page for every payment on the site.
 *
 *  - Overview: collected, refunded, net, awaiting payment, needs attention, daily chart,
 *    payment methods, for any date range.
 *  - All payments / Needs attention / Refunds / Payment requests / Failed attempts tabs,
 *    with search, filters, sorting, bulk actions and CSV export.
 *  - A detail panel per payment (amounts, gateway facts, customer, items, refunds, timeline,
 *    notes) and every action: refund through Converge / Stripe / PayPal (the existing refund
 *    endpoints), check with Converge, change payment status, record an offline payment or a
 *    refund made outside the site, email a receipt, send or copy the payment link, add notes.
 *
 * Reads the same order meta the order screen uses (_payment_*, _refund_log, _pay_link_*,
 * payment tickets). Writes only through the same keys, plus _payment_admin_log and
 * _payment_notes (shown in the order's Activity box too).
 *
 * Files: inc/payments-admin.php, css/payments-admin.css, js/payments-admin.js.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_PA_SLUG = 'wholesale-payments';
const WHOLESALE_PA_MAX_ORDERS = 3000;

function wholesale_pa_capability()
{
	return 'manage_options';
}

// ------------------------------------------------------------------
// Menu
// ------------------------------------------------------------------

add_action('admin_menu', function () {
	$attention = function_exists('wholesale_attention_order_ids') ? count(wholesale_attention_order_ids()) : 0;
	$badge = $attention ? ' <span class="awaiting-mod"><span class="pending-count">' . $attention . '</span></span>' : '';
	$hook = add_menu_page('Payments', 'Payments' . $badge, wholesale_pa_capability(), WHOLESALE_PA_SLUG, 'wholesale_pa_render_page', 'dashicons-money-alt', 26);
	$tabs = array(
		WHOLESALE_PA_SLUG => array('Overview', 'overview'),
		WHOLESALE_PA_SLUG . '-all' => array('All payments', 'all'),
		WHOLESALE_PA_SLUG . '-attention' => array('Needs attention', 'attention'),
		WHOLESALE_PA_SLUG . '-refunds' => array('Refunds', 'refunds'),
		WHOLESALE_PA_SLUG . '-requests' => array('Payment requests', 'requests'),
		WHOLESALE_PA_SLUG . '-failed' => array('Failed attempts', 'failed'),
	);
	$hooks = array($hook);
	foreach ($tabs as $slug => $tab) {
		$hooks[] = add_submenu_page(WHOLESALE_PA_SLUG, $tab[0] . ' ‹ Payments', $tab[0], wholesale_pa_capability(), $slug, 'wholesale_pa_render_page');
	}
	foreach (array_filter($hooks) as $page_hook) {
		add_action('load-' . $page_hook, 'wholesale_pa_load_page');
	}
}, 30);

function wholesale_pa_current_tab()
{
	$page = sanitize_key(wp_unslash($_GET['page'] ?? ''));
	$tab = 0 === strpos($page, WHOLESALE_PA_SLUG . '-') ? substr($page, strlen(WHOLESALE_PA_SLUG) + 1) : 'overview';
	return in_array($tab, array('overview', 'all', 'attention', 'refunds', 'requests', 'failed'), true) ? $tab : 'overview';
}

function wholesale_pa_load_page()
{
	add_action('admin_enqueue_scripts', static function () {
		$dir = get_template_directory();
		$uri = get_template_directory_uri();
		wp_enqueue_style('wholesale-payments-admin', $uri . '/css/payments-admin.css', array(), (string) filemtime($dir . '/css/payments-admin.css'));
		wp_enqueue_script('wholesale-payments-admin', $uri . '/js/payments-admin.js', array(), (string) filemtime($dir . '/js/payments-admin.js'), true);
		wp_localize_script('wholesale-payments-admin', 'WholesalePayments', array(
			'ajax' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('wholesale_pa'),
			'tab' => wholesale_pa_current_tab(),
			'today' => current_time('Y-m-d'),
			'site' => wp_parse_url(home_url(), PHP_URL_HOST),
			'baseUrl' => admin_url('admin.php?page=' . WHOLESALE_PA_SLUG),
			'statuses' => wholesale_pa_status_options(),
			'canRefund' => current_user_can(function_exists('wholesale_refund_capability') ? wholesale_refund_capability() : 'manage_options'),
			'data' => wholesale_pa_dataset(),
		));
	});
	add_filter('admin_body_class', static function ($classes) {
		return $classes . ' wholesale-payments-page';
	});
}

/**
 * key => array(label, tone) including "Not recorded".
 */
function wholesale_pa_status_options()
{
	$out = array();
	foreach (wholesale_payment_statuses() as $key => $item) {
		$out[$key] = array('label' => $item[0], 'tone' => $item[1]);
	}
	$out['none'] = array('label' => 'Not recorded', 'tone' => 'muted');
	return $out;
}

// ------------------------------------------------------------------
// Data
// ------------------------------------------------------------------

function wholesale_pa_address($raw)
{
	$data = wholesale_decode_order_meta_array($raw);
	$out = array();
	foreach ($data as $key => $value) {
		if (is_scalar($value)) {
			$out[preg_replace('/^(billing|shipping)_/', '', (string) $key)] = trim((string) $value);
		}
	}
	return $out;
}

function wholesale_pa_address_lines($address)
{
	$city = trim(implode(', ', array_filter(array($address['city'] ?? '', trim(($address['state'] ?? '') . ' ' . ($address['zip'] ?? ''))))));
	return array_values(array_filter(array(
		trim(($address['fname'] ?? '') . ' ' . ($address['lname'] ?? '')),
		$address['company'] ?? '',
		$address['address'] ?? '',
		$address['address_2'] ?? '',
		$city,
		$address['country'] ?? '',
	), 'strlen'));
}

/**
 * The gateway that took the payment: converge | stripe | paypal | offline | ''.
 */
function wholesale_pa_gateway($id, $status)
{
	$gateway = (string) get_post_meta($id, '_payment_gateway', true);
	if (in_array($gateway, array('stripe', 'paypal'), true)) {
		return $gateway;
	}
	if (get_post_meta($id, '_payment_txn_id', true)) {
		return 'converge';
	}
	if ('paid_offline' === $status || get_post_meta($id, '_payment_offline', true)) {
		return 'offline';
	}
	return '';
}

function wholesale_pa_gateway_label($gateway)
{
	$labels = array('converge' => 'Elavon Converge', 'stripe' => 'Stripe', 'paypal' => 'PayPal', 'offline' => 'Offline');
	return $labels[$gateway] ?? 'None';
}

function wholesale_pa_offline_methods()
{
	return array(
		'phone_card' => 'Card by phone',
		'check' => 'Check',
		'cash' => 'Cash',
		'zelle' => 'Zelle',
		'bank_transfer' => 'Bank transfer / ACH',
		'wire' => 'Wire transfer',
		'other' => 'Other',
	);
}

/**
 * Short method text, e.g. "Visa •••• 4242", "Apple Pay", "PayPal", "Check".
 */
function wholesale_pa_method_label($id, $gateway)
{
	$method = (string) get_post_meta($id, '_payment_method', true);
	$last4 = (string) get_post_meta($id, '_payment_card_last4', true);
	$brand = (string) get_post_meta($id, '_payment_card_brand', true);
	$card = trim(($brand ? ucwords(str_replace('_', ' ', $brand)) : 'Card') . ($last4 ? ' •••• ' . $last4 : ''));
	if ('offline' === $gateway) {
		$offline = get_post_meta($id, '_payment_offline', true);
		$methods = wholesale_pa_offline_methods();
		return is_array($offline) && isset($methods[$offline['method'] ?? '']) ? $methods[$offline['method']] : 'Offline';
	}
	if ('converge' === $gateway) {
		return $card;
	}
	$wallets = array('apple_pay' => 'Apple Pay', 'google_pay' => 'Google Pay', 'link' => 'Stripe Link', 'paypal' => 'PayPal', 'venmo' => 'Venmo');
	if (isset($wallets[$method])) {
		return $wallets[$method] . (in_array($method, array('apple_pay', 'google_pay'), true) && $last4 ? ' •••• ' . $last4 : '');
	}
	return $gateway ? $card : '—';
}

function wholesale_pa_method_group($id, $gateway)
{
	$method = (string) get_post_meta($id, '_payment_method', true);
	if ('offline' === $gateway) {
		return 'Offline';
	}
	$groups = array('apple_pay' => 'Apple Pay', 'google_pay' => 'Google Pay', 'link' => 'Stripe Link', 'paypal' => 'PayPal', 'venmo' => 'Venmo');
	if (isset($groups[$method])) {
		return $groups[$method];
	}
	return $gateway ? 'Card' : 'Marked paid (no transaction)';
}

function wholesale_pa_source($id)
{
	if ($ticket = (int) get_post_meta($id, '_ticket_id', true)) {
		return 'Payment ticket ' . (function_exists('wholesale_ticket_number') ? wholesale_ticket_number($ticket) : '#' . $ticket);
	}
	if (get_post_meta($id, '_pay_link_paid_at', true)) {
		return 'Payment link';
	}
	if (get_post_meta($id, '_payment_gateway', true)) {
		return 'Express checkout';
	}
	if (get_post_meta($id, '_payment_txn_id', true)) {
		return 'Checkout';
	}
	return 'Order';
}

/**
 * Unix time the payment was taken (pay-link time, else the order time).
 */
function wholesale_pa_paid_time($id)
{
	$paid = (string) get_post_meta($id, '_pay_link_paid_at', true);
	if ($paid) {
		return (int) get_gmt_from_date($paid, 'U');
	}
	$offline = get_post_meta($id, '_payment_offline', true);
	if (is_array($offline) && !empty($offline['date'])) {
		return (int) get_gmt_from_date($offline['date'] . ' 12:00:00', 'U');
	}
	return (int) get_post_time('U', true, $id);
}

function wholesale_pa_is_collected($status)
{
	return in_array($status, array('paid', 'paid_offline', 'needs_review', 'partially_refunded', 'refunded'), true);
}

/**
 * One list row for an order.
 */
function wholesale_pa_order_row($post)
{
	$id = (int) $post->ID;
	$status = wholesale_get_payment_status($id);
	$gateway = wholesale_pa_gateway($id, $status);
	$billing = wholesale_pa_address(get_post_meta($id, 'billing_address', true));
	$total = wholesale_order_total($id);
	$totals = wholesale_order_refund_totals($id);
	$collected = wholesale_pa_is_collected($status);
	$charged = $collected ? $totals['charged'] : 0.0;
	$paid_ts = wholesale_pa_paid_time($id);
	$info = wholesale_order_status_info();
	$order_status = $info[$post->post_status] ?? array('label' => ucwords(str_replace(array('-', '_'), ' ', $post->post_status)), 'tone' => 'muted');
	$unknown_refund = false;
	foreach (wholesale_order_refund_log($id) as $entry) {
		if ('unknown' === ($entry['result'] ?? '')) {
			$unknown_refund = true;
		}
	}
	$duplicate = function_exists('wholesale_order_pay_last_event') && wholesale_order_pay_last_event($id, 'duplicate');

	$flags = array();
	if ('needs_review' === $status) {
		$flags[] = 'Verify with gateway';
	}
	if (in_array($status, array('', 'manual'), true) && $total > 0 && !in_array($post->post_status, array('cancelled', 'refunded', 'failed'), true)) {
		$flags[] = 'Payment owed';
	}
	if ($unknown_refund) {
		$flags[] = 'Refund result unknown';
	}
	if ($duplicate) {
		$flags[] = 'Paid twice';
	}
	if ($collected && abs($charged - $total) > 0.009 && $total > 0 && 'paid_offline' !== $status) {
		$flags[] = 'Amount differs from order total';
	}

	return array(
		'id' => $id,
		'number' => wholesale_order_number($id),
		'ts' => $paid_ts,
		'day' => wp_date('Y-m-d', $paid_ts),
		'when' => wp_date('M j, Y · g:i a', $paid_ts),
		'customer' => trim(($billing['fname'] ?? '') . ' ' . ($billing['lname'] ?? '')) ?: ($billing['company'] ?? '') ?: '—',
		'company' => $billing['company'] ?? '',
		'email' => $billing['email'] ?? '',
		'phone' => $billing['tel'] ?? ($billing['phone'] ?? ''),
		'total' => round($total, 2),
		'charged' => round($charged, 2),
		'refunded' => $totals['refunded'],
		'net' => round($charged - $totals['refunded'], 2),
		'owed' => in_array('Payment owed', $flags, true) ? round($total, 2) : 0,
		'status' => $status ?: 'none',
		'gateway' => $gateway,
		'gatewayLabel' => '' === $gateway && $collected ? 'Manual record' : wholesale_pa_gateway_label($gateway),
		'method' => '' === $gateway && $collected ? 'Marked paid' : wholesale_pa_method_label($id, $gateway),
		'methodGroup' => $collected ? wholesale_pa_method_group($id, $gateway) : '',
		'txn' => (string) (get_post_meta($id, '_payment_txn_id', true) ?: get_post_meta($id, '_payment_gateway_txn', true)),
		'ref' => (string) get_post_meta($id, '_payment_ref', true),
		'approval' => (string) get_post_meta($id, '_payment_approval_code', true),
		'last4' => (string) get_post_meta($id, '_payment_card_last4', true),
		'mode' => (string) get_post_meta($id, '_payment_gateway_mode', true),
		'source' => wholesale_pa_source($id),
		'orderStatus' => $order_status['label'],
		'orderTone' => $order_status['tone'],
		'flags' => $flags,
		'edit' => get_edit_post_link($id, 'raw'),
	);
}

/**
 * Everything the page shows, in one payload (the list is filtered and sorted in the browser).
 */
function wholesale_pa_dataset()
{
	$orders = get_posts(array(
		'post_type' => 'order',
		'post_status' => array_diff(array_keys(get_post_stati()), array('trash', 'auto-draft', 'inherit')),
		'posts_per_page' => WHOLESALE_PA_MAX_ORDERS,
		'orderby' => 'date',
		'order' => 'DESC',
	));

	$rows = array();
	$refunds = array();
	$failed = array();
	foreach ($orders as $post) {
		$row = wholesale_pa_order_row($post);
		$rows[] = $row;

		foreach (wholesale_order_refund_log($post->ID) as $index => $entry) {
			$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
			$time = (int) $entry['time'];
			$method = (string) ($entry['method'] ?? '');
			$methods = array('void' => 'Void', 'return' => 'Card return', 'refund' => 'Gateway refund', 'manual' => 'Recorded (outside site)');
			$refunds[] = array(
				'key' => $post->ID . '-' . $index,
				'id' => (int) $post->ID,
				'number' => $row['number'],
				'customer' => $row['customer'],
				'email' => $row['email'],
				'ts' => $time,
				'day' => wp_date('Y-m-d', $time),
				'when' => wp_date('M j, Y · g:i a', $time),
				'amount' => round((float) ($entry['amount'] ?? 0), 2),
				'method' => $methods[$method] ?? ucfirst($method),
				'gateway' => !empty($entry['gateway']) ? wholesale_pa_gateway_label($entry['gateway']) : ('manual' === $method ? (string) ($entry['channel'] ?? 'Outside site') : $row['gatewayLabel']),
				'result' => (string) ($entry['result'] ?? ''),
				'reference' => (string) ($entry['reference'] ?? ''),
				'message' => (string) ($entry['message'] ?? ''),
				'reason' => (string) ($entry['reason'] ?? ''),
				'by' => $user ? $user->display_name : '',
				'emailed' => !empty($entry['emailed']),
			);
		}

		foreach (get_post_meta($post->ID, '_pay_link_log') as $entry) {
			if (is_array($entry) && in_array($entry['event'] ?? '', array('failed', 'duplicate'), true) && !empty($entry['time'])) {
				$time = (int) $entry['time'];
				$failed[] = array(
					'ts' => $time,
					'day' => wp_date('Y-m-d', $time),
					'when' => wp_date('M j, Y · g:i a', $time),
					'source' => 'duplicate' === $entry['event'] ? 'Payment link (paid twice)' : 'Payment link',
					'label' => '#' . $row['number'],
					'customer' => $row['customer'],
					'email' => $row['email'],
					'amount' => $row['total'],
					'message' => (string) ($entry['note'] ?? ''),
					'orderId' => (int) $post->ID,
					'edit' => $row['edit'],
				);
			}
		}
	}

	// Payment tickets that still wait for money, and their failed card attempts.
	$requests = array();
	if (function_exists('wholesale_get_ticket') && post_type_exists('payment_ticket')) {
		$tickets = get_posts(array(
			'post_type' => 'payment_ticket',
			'post_status' => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page' => 1000,
			'orderby' => 'date',
			'order' => 'DESC',
		));
		foreach ($tickets as $ticket_post) {
			$ticket = wholesale_get_ticket($ticket_post->ID);
			foreach ($ticket['log'] as $entry) {
				if ('payment_failed' === ($entry['event'] ?? '') && !empty($entry['time'])) {
					$time = (int) get_gmt_from_date($entry['time'], 'U');
					$failed[] = array(
						'ts' => $time,
						'day' => wp_date('Y-m-d', $time),
						'when' => wp_date('M j, Y · g:i a', $time),
						'source' => 'Payment ticket',
						'label' => $ticket['number'],
						'customer' => $ticket['customer_name'] ?: '—',
						'email' => $ticket['customer_email'],
						'amount' => round((float) $ticket['amount'], 2),
						'message' => (string) ($entry['note'] ?? ''),
						'orderId' => 0,
						'edit' => get_edit_post_link($ticket_post->ID, 'raw'),
					);
				}
			}
			// Paid tickets are orders already; empty drafts aren't a request yet.
			if (in_array($ticket['status'], array('paid', 'cancelled'), true) || ('draft' === $ticket['status'] && $ticket['amount'] <= 0)) {
				continue;
			}
			$key = wholesale_ticket_status_key($ticket);
			$created = (int) get_post_time('U', true, $ticket_post);
			$requests[] = array(
				'id' => $ticket['id'],
				'number' => $ticket['number'],
				'title' => $ticket['title'],
				'customer' => $ticket['customer_name'] ?: '—',
				'company' => $ticket['customer_company'],
				'email' => $ticket['customer_email'],
				'phone' => $ticket['customer_phone'],
				'amount' => round((float) $ticket['amount'], 2),
				'status' => $key,
				'statusLabel' => wholesale_ticket_status_label($ticket),
				'ts' => $created,
				'day' => wp_date('Y-m-d', $created),
				'when' => wp_date('M j, Y', $created),
				'sent' => $ticket['sent_at'] ? wp_date('M j, g:i a', (int) get_gmt_from_date($ticket['sent_at'], 'U')) : '',
				'viewed' => $ticket['viewed_at'] ? wp_date('M j, g:i a', (int) get_gmt_from_date($ticket['viewed_at'], 'U')) : '',
				'due' => $ticket['due'] ? wp_date('M j, Y', strtotime($ticket['due'] . ' 12:00:00')) : '',
				'url' => $ticket['token'] && 'draft' !== $ticket['status'] ? wholesale_ticket_payment_url($ticket) : '',
				'canSend' => in_array($ticket['status'], array('draft', 'sent'), true),
				'edit' => get_edit_post_link($ticket['id'], 'raw'),
			);
		}
	}

	usort($refunds, static function ($a, $b) {
		return $b['ts'] <=> $a['ts'];
	});
	usort($failed, static function ($a, $b) {
		return $b['ts'] <=> $a['ts'];
	});

	return array(
		'rows' => $rows,
		'refunds' => $refunds,
		'requests' => $requests,
		'failed' => $failed,
		'truncated' => count($orders) >= WHOLESALE_PA_MAX_ORDERS,
		'generated' => wp_date('M j, g:i a'),
	);
}

// ------------------------------------------------------------------
// Page
// ------------------------------------------------------------------

function wholesale_pa_render_page()
{
	if (!current_user_can(wholesale_pa_capability())) {
		wp_die('You are not allowed to view payments.');
	}
	$tabs = array(
		'overview' => 'Overview',
		'all' => 'All payments',
		'attention' => 'Needs attention',
		'refunds' => 'Refunds',
		'requests' => 'Payment requests',
		'failed' => 'Failed attempts',
	);
	$current = wholesale_pa_current_tab();
	?>
	<div class="wrap wpa" id="wpa">
		<h1 class="screen-reader-text">Payments</h1>
		<header class="wpa-head">
			<div class="wpa-head__title">
				<span class="wpa-head__icon dashicons dashicons-money-alt" aria-hidden="true"></span>
				<div>
					<h2>Payments</h2>
					<p>Every payment, refund and payment request on <?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST)); ?></p>
				</div>
			</div>
			<div class="wpa-head__actions">
				<div class="wpa-range" role="group" aria-label="Date range">
					<select id="wpa-range" class="wpa-select" aria-label="Date range">
						<option value="7">Last 7 days</option>
						<option value="30" selected>Last 30 days</option>
						<option value="90">Last 90 days</option>
						<option value="mtd">This month</option>
						<option value="lm">Last month</option>
						<option value="ytd">This year</option>
						<option value="all">All time</option>
						<option value="custom">Custom range…</option>
					</select>
					<span class="wpa-range__custom" hidden>
						<input type="date" id="wpa-from" class="wpa-input" aria-label="From">
						<span aria-hidden="true">–</span>
						<input type="date" id="wpa-to" class="wpa-input" aria-label="To">
					</span>
				</div>
				<button type="button" class="wpa-btn wpa-btn--ghost" id="wpa-reload" title="Reload data"><span class="dashicons dashicons-update" aria-hidden="true"></span><span class="wpa-hide-sm">Refresh</span></button>
				<button type="button" class="wpa-btn" id="wpa-export"><span class="dashicons dashicons-download" aria-hidden="true"></span><span class="wpa-hide-sm">Export CSV</span></button>
			</div>
		</header>

		<nav class="wpa-tabs" role="tablist" aria-label="Payment views">
			<?php foreach ($tabs as $key => $label) : ?>
				<a role="tab" href="<?php echo esc_url(admin_url('admin.php?page=' . WHOLESALE_PA_SLUG . ('overview' === $key ? '' : '-' . $key))); ?>" data-tab="<?php echo esc_attr($key); ?>" class="wpa-tab<?php echo $current === $key ? ' is-active' : ''; ?>" aria-selected="<?php echo $current === $key ? 'true' : 'false'; ?>"><?php echo esc_html($label); ?><span class="wpa-tab__count" data-count="<?php echo esc_attr($key); ?>"></span></a>
			<?php endforeach; ?>
		</nav>

		<div id="wpa-notice" class="wpa-notice" role="status" aria-live="polite" hidden></div>

		<section class="wpa-view" data-view="overview" hidden>
			<div class="wpa-kpis" id="wpa-kpis"></div>
			<div class="wpa-grid">
				<div class="wpa-card wpa-card--chart">
					<div class="wpa-card__head"><h3>Net collected per day</h3><span class="wpa-legend"><i class="wpa-dot wpa-dot--in"></i>Collected <i class="wpa-dot wpa-dot--out"></i>Refunded</span></div>
					<div id="wpa-chart" class="wpa-chart"></div>
				</div>
				<div class="wpa-card">
					<div class="wpa-card__head"><h3>Payment methods</h3></div>
					<div id="wpa-methods"></div>
				</div>
			</div>
			<div class="wpa-grid">
				<div class="wpa-card">
					<div class="wpa-card__head"><h3>Needs attention</h3><a href="#" data-goto="attention" class="wpa-link">View all</a></div>
					<div id="wpa-attention-mini"></div>
				</div>
				<div class="wpa-card">
					<div class="wpa-card__head"><h3>Latest payments</h3><a href="#" data-goto="all" class="wpa-link">View all</a></div>
					<div id="wpa-latest"></div>
				</div>
			</div>
		</section>

		<section class="wpa-view" data-view="list" hidden>
			<div class="wpa-toolbar">
				<label class="wpa-search">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<input type="search" id="wpa-q" placeholder="Search order #, name, email, phone, transaction, last 4…" aria-label="Search payments">
				</label>
				<select id="wpa-f-status" class="wpa-select" aria-label="Payment status"><option value="">All statuses</option></select>
				<select id="wpa-f-gateway" class="wpa-select" aria-label="Gateway">
					<option value="">All gateways</option>
					<option value="converge">Elavon Converge</option>
					<option value="stripe">Stripe</option>
					<option value="paypal">PayPal</option>
					<option value="offline">Offline</option>
					<option value="none">No payment</option>
				</select>
				<select id="wpa-f-source" class="wpa-select" aria-label="Source"><option value="">All sources</option></select>
				<span class="wpa-amount-filter">
					<input type="number" id="wpa-f-min" class="wpa-input" placeholder="Min $" min="0" step="0.01" aria-label="Minimum amount">
					<input type="number" id="wpa-f-max" class="wpa-input" placeholder="Max $" min="0" step="0.01" aria-label="Maximum amount">
				</span>
				<button type="button" class="wpa-btn wpa-btn--ghost" id="wpa-clear">Clear</button>
			</div>
			<div class="wpa-bulk" id="wpa-bulk" hidden>
				<strong><span id="wpa-bulk-count">0</span> selected</strong>
				<select id="wpa-bulk-status" class="wpa-select" aria-label="Set payment status to"><option value="">Set payment status…</option></select>
				<button type="button" class="wpa-btn wpa-btn--sm" id="wpa-bulk-apply">Apply</button>
				<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" id="wpa-bulk-export">Export selected</button>
				<button type="button" class="wpa-btn wpa-btn--sm wpa-btn--ghost" id="wpa-bulk-clear">Clear selection</button>
			</div>
			<div class="wpa-summary" id="wpa-summary"></div>
			<div class="wpa-table-wrap"><table class="wpa-table" id="wpa-table"></table></div>
			<div class="wpa-pager" id="wpa-pager"></div>
		</section>

		<p class="wpa-foot">Data loaded <span id="wpa-generated"></span>. Card numbers are never stored, only the last 4 digits.</p>
	</div>

	<aside class="wpa-drawer" id="wpa-drawer" aria-hidden="true" aria-labelledby="wpa-drawer-title" role="dialog" aria-modal="true">
		<div class="wpa-drawer__scrim" data-close></div>
		<div class="wpa-drawer__panel" tabindex="-1">
			<header class="wpa-drawer__head">
				<div>
					<p class="wpa-eyebrow" id="wpa-drawer-eyebrow">Payment</p>
					<h2 id="wpa-drawer-title">Loading…</h2>
				</div>
				<div class="wpa-drawer__tools">
					<button type="button" class="wpa-icon-btn" id="wpa-print" title="Print"><span class="dashicons dashicons-printer" aria-hidden="true"></span><span class="screen-reader-text">Print</span></button>
					<button type="button" class="wpa-icon-btn" data-close title="Close"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span class="screen-reader-text">Close</span></button>
				</div>
			</header>
			<div class="wpa-drawer__actions" id="wpa-drawer-actions"></div>
			<div class="wpa-drawer__body" id="wpa-drawer-body"></div>
		</div>
	</aside>

	<dialog class="wpa-modal" id="wpa-modal">
		<form method="dialog" class="wpa-modal__form" id="wpa-modal-form" novalidate>
			<header class="wpa-modal__head"><h2 id="wpa-modal-title"></h2><button type="button" class="wpa-icon-btn" data-modal-close title="Close"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></header>
			<div class="wpa-modal__body" id="wpa-modal-body"></div>
			<div class="wpa-modal__error" id="wpa-modal-error" role="alert" hidden></div>
			<footer class="wpa-modal__foot">
				<button type="button" class="wpa-btn wpa-btn--ghost" data-modal-close>Cancel</button>
				<button type="submit" class="wpa-btn wpa-btn--primary" id="wpa-modal-submit">Save</button>
			</footer>
		</form>
	</dialog>
	<?php
}

// ------------------------------------------------------------------
// Detail panel (AJAX)
// ------------------------------------------------------------------

function wholesale_pa_money($value)
{
	return '$' . number_format((float) $value, 2);
}

function wholesale_pa_fact($label, $value, $copy = false, $html = false)
{
	if ('' === (string) $value) {
		return '';
	}
	$out = '<div class="wpa-fact"><dt>' . esc_html($label) . '</dt><dd>' . ($html ? $value : esc_html($value));
	if ($copy) {
		$out .= ' <button type="button" class="wpa-copy" data-copy="' . esc_attr(wp_strip_all_tags($value)) . '" title="Copy"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span><span class="screen-reader-text">Copy</span></button>';
	}
	return $out . '</dd></div>';
}

/**
 * Payment and order events, newest first: time, text, tone.
 */
function wholesale_pa_timeline($id)
{
	$post = get_post($id);
	$events = array(array('time' => (int) get_post_time('U', true, $post), 'text' => 'Order placed', 'tone' => 'info'));
	$info = wholesale_order_status_info();
	$label = static function ($status) use ($info) {
		return $info[$status]['label'] ?? ucwords(str_replace(array('-', '_'), ' ', (string) $status));
	};
	foreach (get_post_meta($id, '_status_log') as $entry) {
		if (is_array($entry) && !empty($entry['time'])) {
			$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
			$events[] = array('time' => (int) $entry['time'], 'text' => 'Order status: ' . $label($entry['from'] ?? '') . ' → ' . $label($entry['to'] ?? '') . ($user ? ' by ' . $user->display_name : ''), 'tone' => 'muted');
		}
	}
	foreach (wholesale_order_refund_log($id) as $entry) {
		$tone = 'approved' === ($entry['result'] ?? '') ? 'warn' : 'danger';
		$text = 'manual' === ($entry['method'] ?? '')
			? 'Refund recorded (made outside the site): ' . wholesale_pa_money($entry['amount'] ?? 0) . (!empty($entry['channel']) ? ' via ' . $entry['channel'] : '')
			: wholesale_refund_activity_text($entry);
		$events[] = array('time' => (int) $entry['time'], 'text' => $text . (!empty($entry['reason']) ? ' — "' . $entry['reason'] . '"' : ''), 'tone' => $tone);
	}
	// Pay-link events (sent, viewed, paid, failed …) and anything else plugins add.
	$extra = (array) apply_filters('wholesale_order_activity_events', array(), $post);
	foreach ($extra as $event) {
		if (!empty($event['time']) && !empty($event['text'])) {
			$events[] = array('time' => (int) $event['time'], 'text' => (string) $event['text'], 'tone' => $event['tone'] ?? 'muted');
		}
	}
	if ($ticket_id = (int) get_post_meta($id, '_ticket_id', true)) {
		$labels = function_exists('wholesale_ticket_log_labels') ? wholesale_ticket_log_labels() : array();
		$log = get_post_meta($ticket_id, '_ticket_log', true);
		foreach (is_array($log) ? $log : array() as $entry) {
			if (!empty($entry['time'])) {
				$events[] = array(
					'time' => (int) get_gmt_from_date($entry['time'], 'U'),
					'text' => 'Ticket: ' . ($labels[$entry['event'] ?? ''] ?? ($entry['event'] ?? '')) . (!empty($entry['note']) ? ' — ' . $entry['note'] : '') . (!empty($entry['by']) ? ' (' . $entry['by'] . ')' : ''),
					'tone' => 'payment_failed' === ($entry['event'] ?? '') ? 'danger' : ('paid' === ($entry['event'] ?? '') ? 'success' : 'muted'),
				);
			}
		}
	}
	usort($events, static function ($a, $b) {
		return $b['time'] <=> $a['time'];
	});
	// The same event can arrive twice (admin log is also fed to the activity filter).
	$seen = array();
	return array_values(array_filter($events, static function ($event) use (&$seen) {
		$key = $event['time'] . '|' . $event['text'];
		if (isset($seen[$key])) {
			return false;
		}
		$seen[$key] = true;
		return true;
	}));
}

function wholesale_pa_admin_log($id, $text)
{
	add_post_meta($id, '_payment_admin_log', array('time' => time(), 'user' => get_current_user_id(), 'text' => $text));
}

// "Payment" link on each order in the Orders list opens its payment details.
add_filter('post_row_actions', function ($actions, $post) {
	if ('order' === $post->post_type && current_user_can(wholesale_pa_capability())) {
		$actions['wholesale_payment'] = '<a href="' . esc_url(admin_url('admin.php?page=' . WHOLESALE_PA_SLUG . '-all#pay-' . $post->ID)) . '">Payment</a>';
	}
	return $actions;
}, 20, 2);

// Payments-page actions also show in the order screen's Activity box.
add_filter('wholesale_order_activity_events', function ($events, $post) {
	foreach (get_post_meta($post->ID, '_payment_admin_log') as $entry) {
		if (is_array($entry) && !empty($entry['time'])) {
			$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
			$events[] = array('time' => (int) $entry['time'], 'text' => $entry['text'] . ($user ? ' by ' . $user->display_name : ''), 'tone' => 'info');
		}
	}
	return $events;
}, 20, 2);

function wholesale_pa_detail($id)
{
	$post = get_post($id);
	$row = wholesale_pa_order_row($post);
	$status = 'none' === $row['status'] ? '' : $row['status'];
	$billing = wholesale_pa_address(get_post_meta($id, 'billing_address', true));
	$shipping = wholesale_pa_address(get_post_meta($id, 'shipping_address', true));
	$items = wholesale_decode_order_meta_array(get_post_meta($id, 'product_json', true));
	$cost = wholesale_decode_order_meta_array(get_post_meta($id, 'product_cost', true));
	$totals = wholesale_order_refund_totals($id);
	$gateway = $row['gateway'];
	$mode = (string) get_post_meta($id, '_payment_gateway_mode', true);
	$txn = $row['txn'];
	$verified_by = (int) get_post_meta($id, '_payment_verified_by', true);
	$verified_user = $verified_by ? get_userdata($verified_by) : null;
	$offline = get_post_meta($id, '_payment_offline', true);
	$offline = is_array($offline) ? $offline : array();
	$ticket_id = (int) get_post_meta($id, '_ticket_id', true);
	$statuses = wholesale_pa_status_options();
	$state = $statuses[$status ?: 'none'];

	// Gateway dashboard link.
	$dash = '';
	if ('stripe' === $gateway && $txn) {
		$dash = 'https://dashboard.stripe.com/' . ('test' === $mode ? 'test/' : '') . 'payments/' . rawurlencode($txn);
	} elseif ('paypal' === $gateway) {
		$dash = 'test' === $mode ? 'https://www.sandbox.paypal.com/activity' : 'https://www.paypal.com/activity';
	} elseif ('converge' === $gateway) {
		$dash = 'https://www.convergepay.com/converge-webapp/';
	}

	// Actions available for this payment.
	$refund_capability = function_exists('wholesale_refund_capability') ? wholesale_refund_capability() : 'manage_options';
	$refund = array('available' => false, 'reason' => '', 'gateway' => $gateway, 'remaining' => $totals['remaining']);
	if ('converge' === $gateway) {
		$refund['endpoint'] = 'converge';
		$refund['nonce'] = wp_create_nonce('wholesale_refund_' . $id);
		$refund['reason'] = function_exists('wholesale_refund_blocked_reason') ? wholesale_refund_blocked_reason($id) : 'Install SFS Wallet Payments to refund Converge payments from here.';
	} elseif (in_array($gateway, array('stripe', 'paypal'), true)) {
		$refund['endpoint'] = 'wallet';
		$refund['nonce'] = wp_create_nonce('sfs_wallet_refund_' . $id);
		$refund['reason'] = class_exists('\SFS\WalletPayments\AdminOrder') ? \SFS\WalletPayments\AdminOrder::blocked_reason($id) : 'Activate SFS Wallet Payments to refund this payment from here.';
	} else {
		$refund['reason'] = 'There is no card or wallet transaction to refund. Use "Record refund" for money returned another way.';
	}
	$refund['available'] = '' === $refund['reason'];

	$pay_open = function_exists('wholesale_order_pay_is_open') && wholesale_order_pay_is_open($id);
	$ctx = array(
		'id' => $id,
		'number' => $row['number'],
		'status' => $status ?: 'none',
		'email' => $row['email'],
		'customer' => $row['customer'],
		'total' => $row['total'],
		'charged' => $totals['charged'],
		'refunded' => $totals['refunded'],
		'remaining' => $totals['remaining'],
		'gateway' => $gateway,
		'gatewayLabel' => $row['gatewayLabel'],
		'last4' => $row['last4'],
		'refund' => $refund,
		'canVerify' => 'converge' === $gateway && function_exists('wholesale_converge_query_transaction') && function_exists('wholesale_converge_credentials') && wholesale_converge_credentials(),
		'canRecordPayment' => in_array($status, array('', 'manual', 'needs_review'), true),
		'canRecordRefund' => wholesale_pa_is_collected($status) && $totals['remaining'] >= 0.01 && current_user_can($refund_capability),
		'canReceipt' => wholesale_pa_is_collected($status) && 'refunded' !== $status,
		'payLink' => $pay_open ? array('nonce' => wp_create_nonce('wholesale_order_pay_link_' . $id), 'url' => function_exists('wholesale_order_pay_url') ? wholesale_order_pay_url($id, false) : '') : null,
		'edit' => get_edit_post_link($id, 'raw'),
		'dashboard' => $dash,
		'offlineMethods' => wholesale_pa_offline_methods(),
		'today' => current_time('Y-m-d'),
	);

	ob_start();
	?>
	<div class="wpa-hero wpa-hero--<?php echo esc_attr($state['tone']); ?>">
		<div>
			<p class="wpa-hero__label"><?php echo wholesale_pa_is_collected($status) ? 'Amount charged' : 'Order total'; ?></p>
			<p class="wpa-hero__amount"><?php echo esc_html(wholesale_pa_money(wholesale_pa_is_collected($status) ? $totals['charged'] : $row['total'])); ?></p>
			<p class="wpa-hero__meta"><span class="wpa-badge wpa-badge--<?php echo esc_attr($state['tone']); ?>"><?php echo esc_html($state['label']); ?></span> <span><?php echo esc_html($row['method']); ?></span> · <span><?php echo esc_html($row['when']); ?></span></p>
		</div>
		<div class="wpa-hero__split">
			<div><span>Order total</span><strong><?php echo esc_html(wholesale_pa_money($row['total'])); ?></strong></div>
			<div><span>Refunded</span><strong class="<?php echo $totals['refunded'] > 0 ? 'is-neg' : ''; ?>"><?php echo esc_html(($totals['refunded'] > 0 ? '−' : '') . wholesale_pa_money($totals['refunded'])); ?></strong></div>
			<div><span>Net kept</span><strong><?php echo esc_html(wholesale_pa_money(wholesale_pa_is_collected($status) ? $totals['charged'] - $totals['refunded'] : 0)); ?></strong></div>
		</div>
	</div>

	<?php if ($row['flags']) : ?>
		<div class="wpa-alerts">
			<?php foreach ($row['flags'] as $flag) : ?>
				<p class="wpa-alert"><span class="dashicons dashicons-warning" aria-hidden="true"></span><?php echo esc_html($flag); ?><?php
				if ('Verify with gateway' === $flag) {
					echo ' — the gateway could not be reached to confirm this payment. Check it, then mark it paid.';
				} elseif ('Payment owed' === $flag) {
					echo ' — no payment was taken yet. Send the payment link or record an offline payment.';
				} elseif ('Refund result unknown' === $flag) {
					echo ' — a refund got no answer from the gateway. Check the gateway before trying again.';
				} elseif ('Paid twice' === $flag) {
					echo ' — the payment link was paid a second time. Refund the extra charge in the gateway.';
				}
				?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div id="wpa-verify-result"></div>

	<section class="wpa-sec">
		<h3>Payment</h3>
		<dl class="wpa-facts">
			<?php
			echo wholesale_pa_fact('Gateway', $row['gatewayLabel'] . ($mode ? ' (' . ucfirst($mode) . ' mode)' : ''));
			echo wholesale_pa_fact('Method', $row['method']);
			echo wholesale_pa_fact('Source', $row['source']);
			echo wholesale_pa_fact('Paid on', wholesale_pa_is_collected($status) ? $row['when'] : '');
			echo wholesale_pa_fact('Transaction ID', $txn, true);
			echo wholesale_pa_fact('Approval code', $row['approval'], true);
			echo wholesale_pa_fact('Invoice / reference', $row['ref'], true);
			echo wholesale_pa_fact('Stripe charge', (string) get_post_meta($id, '_payment_gateway_charge', true), true);
			echo wholesale_pa_fact('PayPal order', (string) get_post_meta($id, '_payment_gateway_order', true), true);
			echo wholesale_pa_fact('Payer email', (string) get_post_meta($id, '_payment_payer_email', true), true);
			if ($offline) {
				$methods = wholesale_pa_offline_methods();
				echo wholesale_pa_fact('Offline method', $methods[$offline['method'] ?? ''] ?? '');
				echo wholesale_pa_fact('Received on', !empty($offline['date']) ? wp_date('M j, Y', strtotime($offline['date'] . ' 12:00:00')) : '');
				echo wholesale_pa_fact('Offline reference', (string) ($offline['reference'] ?? ''), true);
			}
			echo wholesale_pa_fact('Refundable now', wholesale_pa_is_collected($status) ? wholesale_pa_money($totals['remaining']) : '');
			echo wholesale_pa_fact('Status set by', $verified_user ? $verified_user->display_name : '');
			if ($ticket_id) {
				echo wholesale_pa_fact('Payment ticket', '<a href="' . esc_url(get_edit_post_link($ticket_id)) . '">' . esc_html(function_exists('wholesale_ticket_number') ? wholesale_ticket_number($ticket_id) : '#' . $ticket_id) . '</a>', false, true);
			}
			if ($dash) {
				echo wholesale_pa_fact('Gateway dashboard', '<a href="' . esc_url($dash) . '" target="_blank" rel="noopener">Open ' . esc_html($row['gatewayLabel']) . ' ↗</a>', false, true);
			}
			?>
		</dl>
	</section>

	<section class="wpa-sec wpa-sec--split">
		<div>
			<h3>Customer</h3>
			<dl class="wpa-facts wpa-facts--stack">
				<?php
				echo wholesale_pa_fact('Name', $row['customer']);
				echo wholesale_pa_fact('Company', $row['company']);
				echo wholesale_pa_fact('Email', $row['email'] ? '<a href="mailto:' . esc_attr($row['email']) . '">' . esc_html($row['email']) . '</a>' : '', false, true);
				echo wholesale_pa_fact('Phone', $row['phone'] ? '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $row['phone'])) . '">' . esc_html($row['phone']) . '</a>' : '', false, true);
				?>
			</dl>
		</div>
		<div>
			<h3>Billing address</h3>
			<address class="wpa-address"><?php echo implode('<br>', array_map('esc_html', wholesale_pa_address_lines($billing))) ?: '—'; ?></address>
			<?php $ship_lines = wholesale_pa_address_lines($shipping); ?>
			<?php if ($ship_lines && $ship_lines !== wholesale_pa_address_lines($billing)) : ?>
				<h3>Ship to</h3>
				<address class="wpa-address"><?php echo implode('<br>', array_map('esc_html', $ship_lines)); ?></address>
			<?php endif; ?>
		</div>
	</section>

	<section class="wpa-sec">
		<h3>Order <a href="<?php echo esc_url(get_edit_post_link($id)); ?>">#<?php echo esc_html($row['number']); ?></a> <span class="wpa-badge wpa-badge--<?php echo esc_attr($row['orderTone']); ?>"><?php echo esc_html($row['orderStatus']); ?></span></h3>
		<table class="wpa-items">
			<tbody>
				<?php foreach ($items as $item) : ?>
					<?php
					if (!is_array($item)) {
						continue;
					}
					$specs = function_exists('wholesale_item_specs') ? wholesale_item_specs($item) : array();
					unset($specs['Design Url'], $specs['My Artwork'], $specs['Payment Ticket']);
					$spec_text = array();
					foreach (array_slice($specs, 0, 6, true) as $label => $value) {
						$spec_text[] = $label . ': ' . wp_strip_all_tags((string) $value);
					}
					?>
					<tr>
						<td><strong><?php echo esc_html(wp_strip_all_tags((string) ($item['product_title'] ?? 'Item'))); ?></strong><small>Qty <?php echo esc_html((string) ($item['product_quantity'] ?? 1)); ?><?php echo $spec_text ? ' · ' . esc_html(implode(' · ', $spec_text)) : ''; ?></small></td>
						<td class="num"><?php echo esc_html(wholesale_pa_money($item['product_subtotal'] ?? 0)); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr><td>Subtotal</td><td class="num"><?php echo esc_html(wholesale_pa_money($cost['sub_total'] ?? 0)); ?></td></tr>
				<tr><td>Shipping<?php echo !empty($cost['shipping_method']) ? ' · ' . esc_html($cost['shipping_method']) : ''; ?></td><td class="num"><?php echo esc_html(wholesale_pa_money($cost['shipping_cost'] ?? 0)); ?></td></tr>
				<tr><td>Tax</td><td class="num"><?php echo esc_html(wholesale_pa_money($cost['tax'] ?? 0)); ?></td></tr>
				<tr class="is-total"><td>Total</td><td class="num"><?php echo esc_html(wholesale_pa_money($row['total'])); ?></td></tr>
			</tfoot>
		</table>
	</section>

	<?php $log = wholesale_order_refund_log($id); ?>
	<?php if ($log) : ?>
		<section class="wpa-sec">
			<h3>Refunds</h3>
			<table class="wpa-mini">
				<thead><tr><th>Date</th><th>Amount</th><th>Type</th><th>Result</th><th>Reference</th><th>By</th></tr></thead>
				<tbody>
					<?php foreach (array_reverse($log) as $entry) : ?>
						<?php $user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null; ?>
						<tr>
							<td><?php echo esc_html(wp_date('M j, Y g:ia', (int) $entry['time'])); ?></td>
							<td class="num"><?php echo esc_html(wholesale_pa_money($entry['amount'] ?? 0)); ?></td>
							<td><?php echo esc_html(array('void' => 'Void', 'return' => 'Card return', 'refund' => 'Gateway refund', 'manual' => 'Outside site')[$entry['method'] ?? ''] ?? (string) ($entry['method'] ?? '')); ?></td>
							<td><span class="wpa-badge wpa-badge--<?php echo esc_attr(array('approved' => 'success', 'declined' => 'danger', 'unknown' => 'warn')[$entry['result'] ?? ''] ?? 'muted'); ?>"><?php echo esc_html(ucfirst((string) ($entry['result'] ?? ''))); ?></span></td>
							<td><code><?php echo esc_html((string) ($entry['reference'] ?? '')); ?></code></td>
							<td><?php echo esc_html($user ? $user->display_name : ''); ?></td>
						</tr>
						<?php if (!empty($entry['reason']) || !empty($entry['message'])) : ?>
							<tr class="wpa-mini__note"><td colspan="6"><?php echo esc_html(trim(($entry['reason'] ?? '') . (!empty($entry['message']) ? ' · ' . $entry['message'] : ''), ' ·')); ?></td></tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>

	<section class="wpa-sec">
		<h3>Internal notes</h3>
		<?php $notes = array_reverse(array_filter(get_post_meta($id, '_payment_notes'), 'is_array')); ?>
		<ul class="wpa-notes">
			<?php foreach ($notes as $note) : ?>
				<?php $user = !empty($note['user']) ? get_userdata((int) $note['user']) : null; ?>
				<li><p><?php echo nl2br(esc_html((string) ($note['text'] ?? ''))); ?></p><small><?php echo esc_html(($user ? $user->display_name . ' · ' : '') . wp_date('M j, Y g:ia', (int) ($note['time'] ?? 0))); ?></small></li>
			<?php endforeach; ?>
			<?php if (!$notes) : ?><li class="wpa-empty-note">No notes yet. Notes are for staff only.</li><?php endif; ?>
		</ul>
		<div class="wpa-note-form">
			<textarea id="wpa-note-text" rows="2" placeholder="Add a note for the team (staff only)…"></textarea>
			<button type="button" class="wpa-btn wpa-btn--sm" data-action="note">Add note</button>
		</div>
	</section>

	<section class="wpa-sec">
		<h3>Timeline</h3>
		<ol class="wpa-timeline">
			<?php foreach (array_slice(wholesale_pa_timeline($id), 0, 60) as $event) : ?>
				<li class="is-<?php echo esc_attr($event['tone']); ?>"><span><?php echo esc_html($event['text']); ?></span><time><?php echo esc_html(wp_date('M j, Y g:ia', $event['time'])); ?></time></li>
			<?php endforeach; ?>
		</ol>
	</section>
	<?php
	return array('html' => ob_get_clean(), 'ctx' => $ctx, 'row' => $row);
}

// ------------------------------------------------------------------
// AJAX: detail + actions
// ------------------------------------------------------------------

function wholesale_pa_request_order()
{
	if (!check_ajax_referer('wholesale_pa', 'nonce', false) || !current_user_can(wholesale_pa_capability())) {
		wp_send_json_error(array('message' => 'Your session expired. Reload the page and try again.'), 403);
	}
	$id = absint($_POST['id'] ?? 0);
	if ($id && ('order' !== get_post_type($id) || !current_user_can('edit_post', $id))) {
		wp_send_json_error(array('message' => 'This order could not be found.'), 404);
	}
	return $id;
}

add_action('wp_ajax_wholesale_pa_detail', function () {
	$id = wholesale_pa_request_order();
	if (!$id) {
		wp_send_json_error(array('message' => 'Missing order.'), 400);
	}
	wp_send_json_success(wholesale_pa_detail($id));
});

add_action('wp_ajax_wholesale_pa_data', function () {
	wholesale_pa_request_order();
	wp_send_json_success(wholesale_pa_dataset());
});

add_action('wp_ajax_wholesale_pa_action', function () {
	$id = wholesale_pa_request_order();
	$do = sanitize_key(wp_unslash($_POST['do'] ?? ''));
	$statuses = wholesale_payment_statuses();

	switch ($do) {
		case 'status':
			$new = sanitize_key(wp_unslash($_POST['status'] ?? ''));
			$note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
			if (!$id || (!isset($statuses[$new]) && 'none' !== $new)) {
				wp_send_json_error(array('message' => 'Choose a payment status.'));
			}
			wholesale_pa_set_status($id, $new, $note);
			wp_send_json_success(array('message' => 'Payment status updated.'));

		case 'bulk_status':
			$new = sanitize_key(wp_unslash($_POST['status'] ?? ''));
			$ids = array_filter(array_map('absint', (array) ($_POST['ids'] ?? array())));
			if (!$ids || (!isset($statuses[$new]) && 'none' !== $new)) {
				wp_send_json_error(array('message' => 'Choose payments and a status.'));
			}
			$done = 0;
			foreach ($ids as $order_id) {
				if ('order' === get_post_type($order_id) && current_user_can('edit_post', $order_id)) {
					wholesale_pa_set_status($order_id, $new, 'Bulk update');
					$done++;
				}
			}
			wp_send_json_success(array('message' => sprintf('%d payment%s updated.', $done, 1 === $done ? '' : 's')));

		case 'record_payment':
			$amount = round((float) preg_replace('/[^0-9.]/', '', (string) wp_unslash($_POST['amount'] ?? '')), 2);
			$method = sanitize_key(wp_unslash($_POST['method'] ?? ''));
			$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['date'] ?? '')) ? (string) $_POST['date'] : current_time('Y-m-d');
			$reference = sanitize_text_field(wp_unslash($_POST['reference'] ?? ''));
			$note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
			$methods = wholesale_pa_offline_methods();
			if (!$id || $amount < 0.01 || !isset($methods[$method])) {
				wp_send_json_error(array('message' => 'Enter the amount received and how it was paid.'));
			}
			if (get_post_meta($id, '_payment_txn_id', true) || get_post_meta($id, '_payment_gateway_txn', true)) {
				wp_send_json_error(array('message' => 'This order already has a card or wallet transaction. Change its payment status instead.'));
			}
			update_post_meta($id, '_payment_offline', array('method' => $method, 'amount' => $amount, 'date' => $date, 'reference' => $reference, 'user' => get_current_user_id(), 'time' => time()));
			update_post_meta($id, '_payment_amount', $amount);
			update_post_meta($id, '_payment_status', 'paid_offline');
			update_post_meta($id, '_payment_verified_by', get_current_user_id());
			wholesale_pa_admin_log($id, sprintf('Offline payment recorded: %s by %s%s', wholesale_pa_money($amount), $methods[$method], $reference ? ' (ref ' . $reference . ')' : ''));
			if ('' !== $note) {
				add_post_meta($id, '_payment_notes', array('time' => time(), 'user' => get_current_user_id(), 'text' => $note));
			}
			wp_send_json_success(array('message' => 'Payment of ' . wholesale_pa_money($amount) . ' recorded. The order is now marked "Paid (offline)".'));

		case 'record_refund':
			if (!$id || !current_user_can(function_exists('wholesale_refund_capability') ? wholesale_refund_capability() : 'manage_options')) {
				wp_send_json_error(array('message' => 'Only administrators can record refunds.'), 403);
			}
			$totals = wholesale_order_refund_totals($id);
			$amount = round((float) preg_replace('/[^0-9.]/', '', (string) wp_unslash($_POST['amount'] ?? '')), 2);
			if ($amount < 0.01 || $amount > $totals['remaining'] + 0.001) {
				wp_send_json_error(array('message' => 'Enter an amount between $0.01 and ' . wholesale_pa_money($totals['remaining']) . '.'));
			}
			$channel = sanitize_text_field(wp_unslash($_POST['channel'] ?? ''));
			$entry = array(
				'time' => time(),
				'user' => get_current_user_id(),
				'amount' => $amount,
				'method' => 'manual',
				'channel' => $channel,
				'reason' => sanitize_textarea_field(wp_unslash($_POST['reason'] ?? '')),
				'reference' => sanitize_text_field(wp_unslash($_POST['reference'] ?? '')),
				'message' => 'Recorded on the Payments page; no money was moved by the site.',
				'result' => 'approved',
				'emailed' => false,
			);
			$remaining = round($totals['remaining'] - $amount, 2);
			update_post_meta($id, '_payment_status', $remaining < 0.01 ? 'refunded' : 'partially_refunded');
			update_post_meta($id, '_payment_verified_by', get_current_user_id());
			if (!empty($_POST['notify']) && function_exists('wholesale_send_order_email')) {
				$body = '<p style="margin:0;">We\'ve issued a ' . ($remaining < 0.01 ? '' : 'partial ') . 'refund for your order.</p>'
					. '<p style="margin:16px 0 0;padding:14px 16px;background:#eef7fc;border-radius:8px;font-size:15px;color:#0d2e4d;"><strong>Refund amount:</strong> ' . esc_html(wholesale_pa_money($amount)) . '</p>'
					. '<p style="margin:16px 0 0;">Depending on your bank, it can take 5–10 business days to appear.</p>';
				$entry['emailed'] = (bool) wholesale_send_order_email($id, sprintf('Your refund for order #%s', wholesale_order_number($id)), $body);
			}
			add_post_meta($id, '_refund_log', $entry);
			wp_send_json_success(array('message' => 'Refund of ' . wholesale_pa_money($amount) . ' recorded.' . ($entry['emailed'] ? ' The customer was emailed.' : '')));

		case 'note':
			$text = sanitize_textarea_field(wp_unslash($_POST['text'] ?? ''));
			if (!$id || '' === trim($text)) {
				wp_send_json_error(array('message' => 'Write a note first.'));
			}
			add_post_meta($id, '_payment_notes', array('time' => time(), 'user' => get_current_user_id(), 'text' => $text));
			wp_send_json_success(array('message' => 'Note added.'));

		case 'verify':
			if (!$id || !function_exists('wholesale_converge_query_transaction')) {
				wp_send_json_error(array('message' => 'Converge lookups need the SFS Wallet Payments plugin.'));
			}
			$txn_id = (string) get_post_meta($id, '_payment_txn_id', true);
			if ('' === $txn_id) {
				wp_send_json_error(array('message' => 'This order has no Converge transaction.'));
			}
			$txn = wholesale_converge_query_transaction($txn_id);
			if (is_wp_error($txn)) {
				wp_send_json_error(array('message' => 'Converge: ' . $txn->get_error_message()));
			}
			$labels = array('STL' => 'Settled', 'PEN' => 'Pending settlement', 'OPN' => 'Open (unsettled)', 'REV' => 'Under review', 'FPR' => 'Failed fraud rule', 'PRE' => 'Failed pre-auth rule', 'PST' => 'Failed post-auth rule');
			$code = strtoupper((string) ($txn['ssl_trans_status'] ?? ''));
			$amount = (float) ($txn['ssl_amount'] ?? 0);
			$expected = (float) get_post_meta($id, '_payment_amount', true) ?: wholesale_order_total($id);
			$approved = isset($txn['ssl_result_message']) && 0 === stripos($txn['ssl_result_message'], 'APPROV') && !in_array($code, array('FPR', 'PRE', 'PST'), true);
			$match = abs($amount - $expected) < 0.006;
			$fields = array_filter(array(
				'Converge status' => ($labels[$code] ?? $code) . ($code ? ' (' . $code . ')' : ''),
				'Result' => (string) ($txn['ssl_result_message'] ?? ''),
				'Amount' => wholesale_pa_money($amount) . ($match ? '' : ' — order expects ' . wholesale_pa_money($expected)),
				'Card' => trim(($txn['ssl_card_short_description'] ?? '') . ' ' . ($txn['ssl_card_number'] ?? '')),
				'Transaction time' => (string) ($txn['ssl_txn_time'] ?? ''),
				'Settled' => (string) ($txn['ssl_settle_time'] ?? ''),
				'Invoice' => (string) ($txn['ssl_invoice_number'] ?? ''),
				'Approval code' => (string) ($txn['ssl_approval_code'] ?? ''),
				'Entry mode' => wholesale_pa_code((string) ($txn['ssl_entry_mode'] ?? ''), array('K' => 'Keyed online', 'S' => 'Swiped', 'C' => 'Chip', 'T' => 'Contactless', 'E' => 'E-commerce')),
				'Address check (AVS)' => wholesale_pa_code(strtoupper((string) ($txn['ssl_avs_response'] ?? '')), array('Y' => 'Address and ZIP match', 'X' => 'Address and ZIP match', 'A' => 'Address matches, ZIP does not', 'Z' => 'ZIP matches, address does not', 'W' => 'ZIP matches, address does not', 'N' => 'No match', 'U' => 'Not available', 'R' => 'Retry', 'S' => 'Not supported', 'G' => 'Not supported (foreign card)')),
				'CVV check' => wholesale_pa_code(strtoupper((string) ($txn['ssl_cvv2_response'] ?? '')), array('M' => 'Match', 'N' => 'No match', 'P' => 'Not processed', 'S' => 'Should be on card', 'U' => 'Not supported by issuer')),
				'Name on card' => trim(($txn['ssl_first_name'] ?? '') . ' ' . ($txn['ssl_last_name'] ?? '')),
			), 'strlen');
			wholesale_pa_admin_log($id, 'Checked with Converge: ' . ($labels[$code] ?? ($code ?: 'unknown')) . ', ' . wholesale_pa_money($amount));
			wp_send_json_success(array(
				'fields' => $fields,
				'ok' => $approved && $match,
				'message' => $approved && $match ? 'Converge confirms this payment.' : 'Converge does not fully confirm this payment. Review the details below.',
				'canMarkPaid' => $approved && $match && 'needs_review' === wholesale_get_payment_status($id),
			));

		case 'receipt':
			$email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
			if (!$id || !is_email($email)) {
				wp_send_json_error(array('message' => 'Enter a valid email address.'));
			}
			$result = wholesale_pa_send_receipt($id, $email);
			if (true !== $result) {
				wp_send_json_error(array('message' => 'The receipt could not be sent: ' . $result));
			}
			wp_send_json_success(array('message' => 'Receipt emailed to ' . $email . '.'));

		case 'ticket_send':
			$ticket_id = absint($_POST['ticket'] ?? 0);
			if (!$ticket_id || 'payment_ticket' !== get_post_type($ticket_id) || !current_user_can('edit_post', $ticket_id) || !function_exists('wholesale_send_ticket')) {
				wp_send_json_error(array('message' => 'This payment request could not be found.'));
			}
			$result = wholesale_send_ticket($ticket_id);
			$messages = array(
				'sent' => 'Payment request emailed to the customer.',
				'missing_customer' => 'Add the customer name and a valid email on the ticket first.',
				'no_items' => 'The ticket has no items.',
				'zero_total' => 'The ticket total is $0.',
				'locked' => 'This ticket can no longer be sent.',
				'send_failed' => 'The email could not be sent right now; it is retried automatically from the Emails log.',
			);
			if ('sent' !== $result) {
				wp_send_json_error(array('message' => $messages[$result] ?? 'The ticket could not be sent.'));
			}
			wp_send_json_success(array('message' => $messages['sent']));
	}

	wp_send_json_error(array('message' => 'Unknown action.'), 400);
});

function wholesale_pa_code($code, $labels)
{
	return '' === $code ? '' : (isset($labels[$code]) ? $labels[$code] . ' (' . $code . ')' : $code);
}

function wholesale_pa_set_status($id, $new, $note = '')
{
	$statuses = wholesale_pa_status_options();
	$old = wholesale_get_payment_status($id) ?: 'none';
	if ($old === $new) {
		return;
	}
	if ('none' === $new) {
		delete_post_meta($id, '_payment_status');
		delete_post_meta($id, '_payment_verified_by');
	} else {
		update_post_meta($id, '_payment_status', $new);
		update_post_meta($id, '_payment_verified_by', get_current_user_id());
	}
	wholesale_pa_admin_log($id, sprintf('Payment status: %s → %s%s', $statuses[$old]['label'] ?? $old, $statuses[$new]['label'], '' !== $note ? ' ("' . $note . '")' : ''));
}

/**
 * Emails a payment receipt for any paid order. Returns true or an error message.
 */
function wholesale_pa_send_receipt($id, $email)
{
	if (!function_exists('wholesale_order_pay_email_shell') || !function_exists('wholesale_send_html_mail')) {
		return 'the email templates are not loaded.';
	}
	$row = wholesale_pa_order_row(get_post($id));
	$billing = wholesale_pa_address(get_post_meta($id, 'billing_address', true));
	$totals = wholesale_order_refund_totals($id);
	$facts = array_filter(array(
		'Order number' => '#' . $row['number'],
		'Amount paid' => wholesale_pa_money($totals['charged']),
		'Refunded' => $totals['refunded'] > 0 ? wholesale_pa_money($totals['refunded']) : '',
		'Paid on' => wp_date('F j, Y g:i a', $row['ts']),
		'Payment method' => $row['method'],
		'Transaction ID' => $row['txn'],
	), 'strlen');
	$html = wholesale_order_pay_email_shell(
		'<h1 style="margin:0 0 8px;font-size:22px;">Your payment receipt</h1>'
		. '<p style="margin:0 0 16px;">Hi' . (!empty($billing['fname']) ? ' ' . esc_html($billing['fname']) : '') . ', here is the receipt for your payment on order <strong>#' . esc_html($row['number']) . '</strong>. Keep this email for your records.</p>'
		. wholesale_order_pay_facts_email_html($facts)
		. wholesale_order_pay_items_email_html($id, 'Total paid')
	);
	$headers = function_exists('wholesale_customer_email_headers') ? wholesale_customer_email_headers() : array('Content-Type: text/html; charset=UTF-8');
	$result = wholesale_send_html_mail($email, sprintf('Payment receipt for order #%s - %s', $row['number'], wholesale_pa_money($totals['charged'])), $html, $headers, 'Storefront Sign Online');
	wholesale_pa_admin_log($id, true === $result ? 'Payment receipt emailed to ' . $email : 'Payment receipt failed for ' . $email);
	return true === $result ? true : (string) $result;
}
