<?php
/**
 * Partial refunds on the order edit screen (wp-admin → Orders → Edit).
 *
 * Adds, on top of the existing refund tools (no money logic changes here):
 *  - Payment box: "Full refund" and "Partial refund" buttons instead of one "Refund payment"
 *    button. Both open the existing Converge refund dialog (sfs-wallet-payments,
 *    converge/refunds.php); "Partial refund" opens it with Partial chosen.
 *  - "Pick what to refund": tick order items (and how many of each), shipping; the item's share
 *    of the tax is added. Fills the partial amount in the Converge dialog and the Wallet payment
 *    box (Stripe / PayPal). Typing an amount by hand still works.
 *  - "Record a refund made outside the site": for money returned in Converge's own website, by
 *    check, Zelle, ... Saves it on the order like the Payments page does (no money is moved).
 *    It is the main button on paid orders that have no card / wallet transaction.
 *
 *  - Scheduled partial refund: while a Converge payment hasn't settled, Converge can only void
 *    the whole charge. Staff can schedule the partial refund instead; an hourly check sends it
 *    (ccreturn) once Converge reports the payment settled. See the safety rules further down.
 *
 * Money moves through the existing endpoints: wholesale_refund_issue (Converge), sfs_wallet_refund
 * (Stripe / PayPal), plus wholesale_opr_process_schedule() here for scheduled Converge returns.
 * Recording uses wholesale_pa_action / record_refund.
 * Script: js/order-partial-refund.js. Built 2026-11-17.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Whether the current screen is the edit screen of an order.
 */
function wholesale_opr_is_order_screen()
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	return $screen && 'post' === $screen->base && 'order' === $screen->post_type;
}

/**
 * Everything the script needs about the order: items, totals, which refund tools apply.
 */
function wholesale_opr_config($post)
{
	$id = (int) $post->ID;
	$cost = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($id, 'product_cost', true)) : array();
	$lines = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($id, 'product_json', true)) : array();
	$items = array();
	foreach ((array) $lines as $line) {
		if (!is_array($line)) {
			continue;
		}
		$amount = round((float) ($line['product_subtotal'] ?? 0), 2);
		if ($amount <= 0) {
			continue;
		}
		$items[] = array(
			'title' => wp_specialchars_decode((string) ($line['product_title'] ?? 'Item'), ENT_QUOTES),
			'job' => (string) ($line['job_name'] ?? ''),
			'qty' => max(1, (int) ($line['product_quantity'] ?? 1)),
			'amount' => $amount,
		);
	}

	$totals = function_exists('wholesale_order_refund_totals') ? wholesale_order_refund_totals($id) : array('charged' => 0, 'refunded' => 0, 'remaining' => 0);
	$status = function_exists('wholesale_get_payment_status') ? wholesale_get_payment_status($id) : (string) get_post_meta($id, '_payment_status', true);
	$billing = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($id, 'billing_address', true)) : array();
	$gateway = (string) get_post_meta($id, '_payment_gateway', true);
	$refund_cap = function_exists('wholesale_refund_capability') ? wholesale_refund_capability() : 'manage_options';

	$can_record = current_user_can($refund_cap)
		&& function_exists('wholesale_pa_capability') && current_user_can(wholesale_pa_capability())
		&& in_array($status, array('paid', 'paid_offline', 'needs_review', 'partially_refunded'), true)
		&& $totals['remaining'] >= 0.01;

	if (get_post_meta($id, '_payment_txn_id', true)) {
		$kind = 'converge';
	} elseif (in_array($gateway, array('stripe', 'paypal'), true) && get_post_meta($id, '_payment_gateway_txn', true)) {
		$kind = 'wallet';
	} else {
		$kind = 'none';
	}

	// Approved refunds, newest first (the order the Converge panel lists them in).
	$history = array();
	$log = function_exists('wholesale_order_refund_log') ? wholesale_order_refund_log($id) : array();
	foreach (array_reverse($log) as $entry) {
		if ('approved' !== ($entry['result'] ?? '')) {
			continue;
		}
		$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
		$history[] = array(
			'amount' => round((float) ($entry['amount'] ?? 0), 2),
			'when' => wp_date('M j, Y g:ia', (int) $entry['time']) . ($user ? ' · ' . $user->display_name : ''),
			'method' => (string) ($entry['method'] ?? ''),
			'channel' => (string) ($entry['channel'] ?? ''),
			'reference' => (string) ($entry['reference'] ?? ''),
			'reason' => (string) ($entry['reason'] ?? ''),
		);
	}

	// Scheduled partial refund (only states staff still need to see).
	$can_schedule = 'converge' === $kind && current_user_can($refund_cap) && function_exists('wholesale_opr_can_schedule') && wholesale_opr_can_schedule();
	$schedule = function_exists('wholesale_opr_get_schedule') ? wholesale_opr_get_schedule($id) : null;
	$schedule_info = null;
	if ($schedule && in_array($schedule['state'], array('waiting', 'sending', 'failed', 'unknown'), true)) {
		$by = !empty($schedule['user']) ? get_userdata((int) $schedule['user']) : null;
		$schedule_info = array(
			'state' => $schedule['state'],
			'amount' => round((float) $schedule['amount'], 2),
			'notify' => !empty($schedule['notify']),
			'reason' => (string) ($schedule['reason'] ?? ''),
			'when' => wp_date('M j, g:ia', (int) $schedule['time']) . ($by ? ' by ' . $by->display_name : ''),
			'lastCheck' => !empty($schedule['last_check']) ? wp_date('M j, g:ia', (int) $schedule['last_check']) : '',
			'message' => (string) ($schedule['message'] ?? ''),
			'stale' => 'sending' === $schedule['state'] && time() - (int) ($schedule['sending_since'] ?? 0) > 10 * MINUTE_IN_SECONDS,
			'otherSite' => function_exists('wholesale_opr_site_key') && ($schedule['site'] ?? '') !== wholesale_opr_site_key() ? (string) ($schedule['site_label'] ?? 'unknown') : '',
		);
	}

	return array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'postId' => $id,
		'card' => ($last4 = (string) get_post_meta($id, '_payment_card_last4', true)) ? '•••• ' . $last4 : 'the card',
		'canSchedule' => $can_schedule,
		'schedule' => $schedule_info,
		'scheduleNonce' => ($can_schedule || $schedule_info) && current_user_can($refund_cap) ? wp_create_nonce('wholesale_opr_' . $id) : '',
		'number' => function_exists('wholesale_order_number') ? (string) wholesale_order_number($id) : (string) $id,
		'kind' => $kind,
		'gateway' => $gateway,
		'items' => $items,
		'subtotal' => round((float) ($cost['sub_total'] ?? 0), 2),
		'shipping' => round((float) ($cost['shipping_cost'] ?? 0), 2),
		'shippingMethod' => (string) ($cost['shipping_method'] ?? ''),
		'tax' => round((float) ($cost['tax'] ?? 0), 2),
		'charged' => (float) $totals['charged'],
		'refunded' => (float) $totals['refunded'],
		'remaining' => (float) $totals['remaining'],
		'email' => is_email($billing['billing_email'] ?? '') ? (string) $billing['billing_email'] : '',
		'history' => $history,
		'record' => $can_record ? array('nonce' => wp_create_nonce('wholesale_pa')) : null,
	);
}

// ------------------------------------------------------------------
// Scheduled partial refunds (Converge payments that haven't settled yet)
// ------------------------------------------------------------------
//
// Converge can only void the whole charge until the payment settles (usually overnight).
// Staff can schedule a partial refund instead: it is saved on the order (_refund_scheduled)
// and an hourly check sends it as a "return" (ccreturn) once Converge reports the payment
// settled. Safety rules:
//  - It is only ever sent by the copy of the site it was scheduled on (home URL saved with it),
//    so a local copy of the live database can never send a live refund, and the other way round.
//  - It is not sent if any other refund was made on the order after it was scheduled, if the
//    payment was refunded in full, or if less than the amount is left.
//  - The state is saved as "sending" before the request goes out and never retried after that:
//    a request that got no answer is flagged for staff to check in Converge, never sent twice.
//  - One refund at a time per order (the same lock option the Converge refund dialog uses).
//  - Gives up (and emails the team) if the payment still hasn't settled after 7 days.

define('WHOLESALE_OPR_CRON', 'wholesale_opr_run_scheduled_refunds');

/**
 * Identity of this installation: its folder on the server + database name + table prefix.
 * Unlike the site address (the local wp-config builds it from the browser's host name), this is
 * the same in every request and in cron, and it never travels with a copy of the database.
 */
function wholesale_opr_site_key()
{
	global $table_prefix;
	$path = realpath(ABSPATH);
	return substr(md5(wp_normalize_path($path ? $path : ABSPATH) . '|' . (defined('DB_NAME') ? DB_NAME : '') . '|' . $table_prefix), 0, 16);
}

/**
 * Readable name of this copy of the site, for messages, e.g. "storefrontsignonline.com".
 */
function wholesale_opr_site_label()
{
	$host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
	return preg_replace('/^www\./', '', $host) . untrailingslashit((string) wp_parse_url(home_url(), PHP_URL_PATH));
}

function wholesale_opr_get_schedule($post_id)
{
	$schedule = get_post_meta($post_id, '_refund_scheduled', true);
	return is_array($schedule) && !empty($schedule['state']) ? $schedule : null;
}

function wholesale_opr_save_schedule($post_id, array $schedule)
{
	update_post_meta($post_id, '_refund_scheduled', $schedule);
	update_post_meta($post_id, '_refund_scheduled_state', $schedule['state']);
}

/**
 * Whether this site can schedule card refunds at all (the Converge code lives in the plugin).
 */
function wholesale_opr_can_schedule()
{
	return function_exists('wholesale_refund_blocked_reason') && function_exists('wholesale_refund_method')
		&& function_exists('wholesale_converge_xml_request') && function_exists('wholesale_converge_xml_fields')
		&& function_exists('wholesale_order_refund_totals');
}

/**
 * A line in the order's payment history (Activity box). $user 0 = done automatically.
 */
function wholesale_opr_log($post_id, $text, $user = null)
{
	add_post_meta($post_id, '_payment_admin_log', array('time' => time(), 'user' => null === $user ? get_current_user_id() : (int) $user, 'text' => $text));
}

function wholesale_opr_mail_team($post_id, $subject, array $lines)
{
	$number = function_exists('wholesale_order_number') ? wholesale_order_number($post_id) : $post_id;
	$lines[] = admin_url('post.php?post=' . $post_id . '&action=edit');
	$subject = sprintf($subject, $number);
	$body = implode("\n\n", $lines);
	if (function_exists('wholesale_mail_team')) {
		return wholesale_mail_team($subject, $body);
	}
	return wp_mail(get_option('admin_email'), $subject, $body);
}

/**
 * Ends a schedule that won't be sent: saves why, logs it, and emails the team.
 */
function wholesale_opr_stop_schedule($post_id, array $schedule, $state, $message)
{
	$schedule['state'] = $state;
	$schedule['message'] = $message;
	$schedule['ended'] = time();
	wholesale_opr_save_schedule($post_id, $schedule);
	wholesale_opr_log($post_id, sprintf('Scheduled refund of %s not sent: %s', wholesale_refund_money($schedule['amount']), $message), 0);
	wholesale_opr_mail_team($post_id, 'Scheduled refund NOT sent for order #%s', array(
		sprintf('The partial refund of %s scheduled for this order was not sent.', wholesale_refund_money($schedule['amount'])),
		'Why: ' . $message,
	));
}

/**
 * Checks one order's scheduled refund and sends it if the payment has settled.
 *
 * @return array{state:string,message:string}
 */
function wholesale_opr_process_schedule($post_id)
{
	$schedule = wholesale_opr_get_schedule($post_id);
	if (!$schedule || 'waiting' !== $schedule['state']) {
		return array('state' => $schedule['state'] ?? '', 'message' => 'No refund is waiting on this order.');
	}
	if (($schedule['site'] ?? '') !== wholesale_opr_site_key()) {
		return array('state' => 'waiting', 'message' => 'This refund was scheduled on another copy of the site (' . ($schedule['site_label'] ?? '?') . '), so only that site sends it.');
	}
	if (!wholesale_opr_can_schedule()) {
		return array('state' => 'waiting', 'message' => 'Card refunds need the SFS Wallet Payments plugin, so it will be sent once the plugin is active.');
	}

	// One refund at a time per order: the same lock the Converge refund dialog uses.
	$lock = 'wholesale_refund_lock_' . $post_id;
	if ((int) get_option($lock) < time() - 120) {
		delete_option($lock);
	}
	if (!add_option($lock, time(), '', 'no')) {
		return array('state' => 'waiting', 'message' => 'Another refund for this order is being processed. Try again in a minute.');
	}

	try {
		wp_cache_delete($post_id, 'post_meta');
		$schedule = wholesale_opr_get_schedule($post_id);
		if (!$schedule || 'waiting' !== $schedule['state']) {
			return array('state' => $schedule['state'] ?? '', 'message' => 'No refund is waiting on this order.');
		}
		$amount = round((float) $schedule['amount'], 2);
		$txn_id = (string) get_post_meta($post_id, '_payment_txn_id', true);

		if ('order' !== get_post_type($post_id) || 'trash' === get_post_status($post_id) || '' === $txn_id) {
			wholesale_opr_stop_schedule($post_id, $schedule, 'cancelled', 'the order no longer has a Converge card payment.');
			return array('state' => 'cancelled', 'message' => 'Not sent: the order no longer has a Converge card payment.');
		}
		foreach (wholesale_order_refund_log($post_id) as $entry) {
			// Approved, or sent with no answer (it may have gone through): either way, don't add another.
			if (in_array($entry['result'] ?? '', array('approved', 'unknown'), true) && (int) $entry['time'] >= (int) $schedule['time']) {
				$message = sprintf('another refund (%s) was made or tried on this order after it was scheduled. Schedule it again if it is still needed.', wholesale_refund_money($entry['amount'] ?? 0));
				wholesale_opr_stop_schedule($post_id, $schedule, 'cancelled', $message);
				return array('state' => 'cancelled', 'message' => 'Not sent: ' . $message);
			}
		}
		$totals = wholesale_order_refund_totals($post_id);
		if ('refunded' === wholesale_get_payment_status($post_id) || $amount > $totals['remaining'] + 0.005) {
			$message = sprintf('only %s of the payment is left to refund.', wholesale_refund_money($totals['remaining']));
			wholesale_opr_stop_schedule($post_id, $schedule, 'cancelled', $message);
			return array('state' => 'cancelled', 'message' => 'Not sent: ' . $message);
		}

		$schedule['checks'] = (int) ($schedule['checks'] ?? 0) + 1;
		$schedule['last_check'] = time();
		$too_old = time() - (int) $schedule['time'] > 7 * DAY_IN_SECONDS;
		$method = wholesale_refund_method($txn_id);
		if (is_wp_error($method)) {
			if ('status' === $method->get_error_code() || $too_old) {
				wholesale_opr_stop_schedule($post_id, $schedule, 'failed', $method->get_error_message());
				return array('state' => 'failed', 'message' => 'Not sent: ' . $method->get_error_message());
			}
			$schedule['message'] = $method->get_error_message();
			wholesale_opr_save_schedule($post_id, $schedule);
			return array('state' => 'waiting', 'message' => $schedule['message'] . ' It will be checked again within the hour.');
		}
		list($method, $txn) = $method;
		if ('void' === $method) {
			if ($too_old) {
				$message = 'the payment still hasn\'t settled in Converge after 7 days. Please check it in Converge.';
				wholesale_opr_stop_schedule($post_id, $schedule, 'failed', $message);
				return array('state' => 'failed', 'message' => 'Not sent: ' . $message);
			}
			$schedule['message'] = 'Not settled yet.';
			wholesale_opr_save_schedule($post_id, $schedule);
			return array('state' => 'waiting', 'message' => 'The payment hasn\'t settled in Converge yet. It will be checked again within the hour.');
		}
		if (isset($txn['ssl_amount']) && $amount > (float) $txn['ssl_amount'] + 0.005) {
			$message = 'the refund is larger than the amount Converge charged (' . wholesale_refund_money($txn['ssl_amount']) . ').';
			wholesale_opr_stop_schedule($post_id, $schedule, 'failed', $message);
			return array('state' => 'failed', 'message' => 'Not sent: ' . $message);
		}

		// Settled: send it. Saved as "sending" first, so it can never be sent twice.
		$schedule['state'] = 'sending';
		$schedule['sending_since'] = time();
		wholesale_opr_save_schedule($post_id, $schedule);

		$doc = wholesale_converge_xml_request(array(
			'ssl_transaction_type' => 'ccreturn',
			'ssl_txn_id' => $txn_id,
			'ssl_amount' => number_format($amount, 2, '.', ''),
		), 45);
		$entry = array(
			'time' => time(),
			'user' => (int) $schedule['user'],
			'amount' => $amount,
			'method' => 'return',
			'reason' => (string) $schedule['reason'],
			'reference' => '',
			'message' => 'Scheduled refund',
			'emailed' => false,
			'scheduled' => true,
		);

		if (is_wp_error($doc) && in_array($doc->get_error_code(), array('unreachable', 'bad_response'), true)) {
			// The request may have reached Converge, so the refund may have gone through.
			$entry['result'] = 'unknown';
			$entry['message'] = $doc->get_error_message();
			add_post_meta($post_id, '_refund_log', $entry);
			$schedule['state'] = 'unknown';
			$schedule['message'] = 'Converge didn\'t answer, so it is not known whether the refund went through. Check the transaction in Converge.';
			$schedule['ended'] = time();
			wholesale_opr_save_schedule($post_id, $schedule);
			wholesale_opr_log($post_id, sprintf('Scheduled refund of %s got no answer from Converge', wholesale_refund_money($amount)), 0);
			wholesale_opr_mail_team($post_id, 'CHECK refund for order #%s', array(
				sprintf('The scheduled partial refund of %s on Converge transaction %s got no answer from Converge, so it may or may not have gone through.', wholesale_refund_money($amount), $txn_id),
				'Please check the transaction in Converge. The site will not try again.',
			));
			return array('state' => 'unknown', 'message' => $schedule['message']);
		}

		$result = is_wp_error($doc) ? array() : wholesale_converge_xml_fields($doc);
		if (is_wp_error($doc) || '0' !== ($result['ssl_result'] ?? '')) {
			$entry['result'] = 'declined';
			$entry['message'] = is_wp_error($doc) ? $doc->get_error_message() : sanitize_text_field($result['ssl_result_message'] ?? 'Declined');
			add_post_meta($post_id, '_refund_log', $entry);
			wholesale_opr_stop_schedule($post_id, $schedule, 'failed', 'Converge declined it (' . $entry['message'] . '). No money was moved.');
			return array('state' => 'failed', 'message' => 'Converge declined the refund: ' . $entry['message'] . '. No money was moved.');
		}

		$entry['result'] = 'approved';
		$entry['reference'] = sanitize_text_field($result['ssl_txn_id'] ?? '');
		$remaining = round($totals['remaining'] - $amount, 2);
		update_post_meta($post_id, '_payment_status', $remaining < 0.01 ? 'refunded' : 'partially_refunded');
		update_post_meta($post_id, '_payment_verified_by', (int) $schedule['user']);
		if (!empty($schedule['notify']) && function_exists('wholesale_send_refund_email')) {
			$entry['emailed'] = (bool) wholesale_send_refund_email($post_id, $amount, 'return', $remaining < 0.01);
		}
		add_post_meta($post_id, '_refund_log', $entry);

		$schedule['state'] = 'done';
		$schedule['reference'] = $entry['reference'];
		$schedule['message'] = 'Sent.';
		$schedule['ended'] = time();
		wholesale_opr_save_schedule($post_id, $schedule);
		wholesale_opr_log($post_id, sprintf('Scheduled refund of %s sent to Converge (ref %s)%s', wholesale_refund_money($amount), $entry['reference'] ?: '?', $entry['emailed'] ? ', customer emailed' : ''), 0);
		wholesale_opr_mail_team($post_id, 'Scheduled refund sent for order #%s', array(
			sprintf('The scheduled partial refund of %s was returned to the card. Converge reference: %s.', wholesale_refund_money($amount), $entry['reference'] ?: 'none given'),
			$entry['emailed'] ? 'The customer was emailed a refund confirmation.' : 'The customer was not emailed.',
		));
		return array('state' => 'done', 'message' => sprintf('Refund of %s sent to the card (Converge ref %s).', wholesale_refund_money($amount), $entry['reference'] ?: '?'));
	} finally {
		delete_option($lock);
	}
}

/**
 * Hourly: every waiting scheduled refund. The event removes itself when none are left.
 */
function wholesale_opr_run_all()
{
	global $wpdb;
	$ids = $wpdb->get_col($wpdb->prepare(
		"SELECT m.post_id FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = %s AND m.meta_value = %s AND p.post_type = %s ORDER BY m.post_id ASC LIMIT 25",
		'_refund_scheduled_state',
		'waiting',
		'order'
	));
	if (!$ids) {
		wp_clear_scheduled_hook(WHOLESALE_OPR_CRON);
		return;
	}
	foreach ($ids as $id) {
		wholesale_opr_process_schedule((int) $id);
	}
}
add_action(WHOLESALE_OPR_CRON, 'wholesale_opr_run_all');

function wholesale_opr_ensure_cron()
{
	if (!wp_next_scheduled(WHOLESALE_OPR_CRON)) {
		wp_schedule_event(time() + 10 * MINUTE_IN_SECONDS, 'hourly', WHOLESALE_OPR_CRON);
	}
}

/**
 * Self-healing: WordPress can lose a just-added cron event when two requests save the cron
 * list at the same time. Any admin page (at most every 10 minutes) puts it back while a
 * scheduled refund is waiting.
 */
add_action('admin_init', function () {
	if (wp_doing_ajax() || wp_next_scheduled(WHOLESALE_OPR_CRON) || get_transient('wholesale_opr_cron_checked')) {
		return;
	}
	set_transient('wholesale_opr_cron_checked', 1, 10 * MINUTE_IN_SECONDS);
	global $wpdb;
	$waiting = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_refund_scheduled_state', 'waiting'));
	if ($waiting) {
		wholesale_opr_ensure_cron();
	}
});

/**
 * Shared checks for the schedule AJAX calls. Returns the order ID or sends a JSON error.
 */
function wholesale_opr_request_order()
{
	$post_id = absint($_POST['post_id'] ?? 0);
	if (!$post_id || !check_ajax_referer('wholesale_opr_' . $post_id, 'nonce', false)) {
		wp_send_json(array('ok' => false, 'error' => 'Your session expired. Please reload the page and try again.'), 403);
	}
	$cap = function_exists('wholesale_refund_capability') ? wholesale_refund_capability() : 'manage_options';
	if ('order' !== get_post_type($post_id) || !current_user_can('edit_post', $post_id) || !current_user_can($cap)) {
		wp_send_json(array('ok' => false, 'error' => 'Only administrators can schedule refunds.'), 403);
	}
	if (!wholesale_opr_can_schedule()) {
		wp_send_json(array('ok' => false, 'error' => 'Card refunds need the SFS Wallet Payments plugin.'), 400);
	}
	return $post_id;
}

add_action('wp_ajax_wholesale_refund_schedule', function () {
	$post_id = wholesale_opr_request_order();
	if ($reason = wholesale_refund_blocked_reason($post_id)) {
		wp_send_json(array('ok' => false, 'error' => $reason), 400);
	}
	$current = wholesale_opr_get_schedule($post_id);
	if ($current && in_array($current['state'], array('waiting', 'sending'), true)) {
		wp_send_json(array('ok' => false, 'error' => 'A refund of ' . wholesale_refund_money($current['amount']) . ' is already scheduled for this order. Cancel it first to change it.'), 409);
	}
	$totals = wholesale_order_refund_totals($post_id);
	$raw = trim((string) wp_unslash($_POST['amount'] ?? ''));
	$amount = preg_match('/^\d+(\.\d{1,2})?$/', $raw) ? round((float) $raw, 2) : 0.0;
	if ($amount < 0.01 || $amount > $totals['remaining'] - 0.005) {
		wp_send_json(array('ok' => false, 'error' => 'Enter an amount between $0.01 and ' . wholesale_refund_money(max(0, $totals['remaining'] - 0.01)) . '. To refund everything, void the full charge now.'), 400);
	}

	$txn_id = (string) get_post_meta($post_id, '_payment_txn_id', true);
	$method = wholesale_refund_method($txn_id);
	if (is_wp_error($method)) {
		wp_send_json(array('ok' => false, 'error' => $method->get_error_message()), 502);
	}
	if ('void' !== $method[0]) {
		wp_send_json(array('ok' => false, 'recheck' => true, 'error' => 'This payment has just settled, so the partial refund can be sent right away. Close this window and click Partial refund again.'), 409);
	}
	if (isset($method[1]['ssl_amount']) && $amount > (float) $method[1]['ssl_amount'] + 0.005) {
		wp_send_json(array('ok' => false, 'error' => 'The refund is larger than the amount Converge charged (' . wholesale_refund_money($method[1]['ssl_amount']) . ').'), 400);
	}

	$schedule = array(
		'amount' => $amount,
		'reason' => sanitize_textarea_field(wp_unslash($_POST['reason'] ?? '')),
		'notify' => !empty($_POST['notify']),
		'user' => get_current_user_id(),
		'time' => time(),
		'site' => wholesale_opr_site_key(),
		'site_label' => wholesale_opr_site_label(),
		'state' => 'waiting',
		'checks' => 0,
		'last_check' => time(),
		'message' => 'Not settled yet.',
	);
	wholesale_opr_save_schedule($post_id, $schedule);
	wholesale_opr_log($post_id, sprintf('Partial refund of %s scheduled; it is sent once Converge settles the payment', wholesale_refund_money($amount)));
	wholesale_opr_ensure_cron();
	wp_send_json(array('ok' => true, 'message' => 'Refund of ' . wholesale_refund_money($amount) . ' scheduled. It will be returned to the card automatically once Converge settles the payment, usually by the next business day. You\'ll get an email when it\'s sent.'));
});

add_action('wp_ajax_wholesale_refund_schedule_cancel', function () {
	$post_id = wholesale_opr_request_order();
	$lock = 'wholesale_refund_lock_' . $post_id;
	if ((int) get_option($lock) < time() - 120) {
		delete_option($lock);
	}
	if (!add_option($lock, time(), '', 'no')) {
		wp_send_json(array('ok' => false, 'error' => 'The refund is being processed right now. Reload the page in a minute.'), 409);
	}
	wp_cache_delete($post_id, 'post_meta');
	$schedule = wholesale_opr_get_schedule($post_id);
	if (!$schedule || !in_array($schedule['state'], array('waiting', 'failed', 'unknown', 'cancelled', 'sending'), true)) {
		delete_option($lock);
		wp_send_json(array('ok' => false, 'error' => 'There is no scheduled refund to cancel.'), 400);
	}
	if ('sending' === $schedule['state'] && time() - (int) ($schedule['sending_since'] ?? 0) < 10 * MINUTE_IN_SECONDS) {
		delete_option($lock);
		wp_send_json(array('ok' => false, 'error' => 'The refund is being sent right now. Reload the page in a minute.'), 409);
	}
	$was = $schedule['state'];
	$schedule['state'] = 'waiting' === $was ? 'cancelled' : 'dismissed';
	$schedule['ended'] = time();
	$schedule['message'] = 'waiting' === $was ? 'Cancelled by staff.' : $schedule['message'];
	wholesale_opr_save_schedule($post_id, $schedule);
	if ('waiting' === $was) {
		wholesale_opr_log($post_id, sprintf('Scheduled refund of %s cancelled', wholesale_refund_money($schedule['amount'])));
	}
	delete_option($lock);
	wp_send_json(array('ok' => true, 'message' => 'waiting' === $was ? 'Scheduled refund cancelled. No money was moved.' : 'Dismissed.'));
});

add_action('wp_ajax_wholesale_refund_schedule_run', function () {
	$post_id = wholesale_opr_request_order();
	$result = wholesale_opr_process_schedule($post_id);
	wp_send_json(array('ok' => true, 'state' => $result['state'], 'message' => $result['message']));
});

add_action('admin_enqueue_scripts', function ($hook) {
	if ('post.php' !== $hook || !wholesale_opr_is_order_screen()) {
		return;
	}
	$post = get_post(absint($_GET['post'] ?? 0));
	if (!$post || 'order' !== $post->post_type || !current_user_can('edit_post', $post->ID)) {
		return;
	}
	$path = get_template_directory() . '/js/order-partial-refund.js';
	wp_enqueue_script('wholesale-order-partial-refund', get_template_directory_uri() . '/js/order-partial-refund.js', array(), file_exists($path) ? (string) filemtime($path) : '1', true);
	wp_add_inline_script('wholesale-order-partial-refund', 'window.wholesaleOrderRefund = ' . wp_json_encode(wholesale_opr_config($post)) . ';', 'before');
});

add_action('admin_head-post.php', function () {
	if (!wholesale_opr_is_order_screen()) {
		return;
	}
	?>
	<style id="wholesale-order-partial-refund-css">
		.opr-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; }
		.opr-actions .wr-open, .opr-actions .opr-partial { display: flex !important; align-items: center; justify-content: center; gap: 4px; width: 100%; min-height: 32px; margin: 0 !important; padding: 0 6px !important; white-space: nowrap; color: #b32d2e !important; border-color: #e0a3a3 !important; background: #fff; }
		.opr-actions .wr-open:hover, .opr-actions .wr-open:focus, .opr-actions .opr-partial:hover, .opr-actions .opr-partial:focus { background: #fcf0f1 !important; border-color: #b32d2e !important; }
		.opr-actions .dashicons { font-size: 16px; width: 16px; height: 16px; }
		.opr-record-link { display: block; margin: 0 0 12px; padding: 0; border: 0; background: none; color: #2271b1; text-align: left; text-decoration: underline; cursor: pointer; font-size: 12px; }
		.opr-record-link:hover { color: #135e96; }
		.opr-record-main { display: flex !important; align-items: center; justify-content: center; gap: 4px; width: 100%; margin: 4px 0 12px !important; }
		.opr-record-main .dashicons { font-size: 16px; width: 16px; height: 16px; }

		.opr-history-wrap { margin: 12px -12px 12px; padding: 12px 12px 0; border-top: 1px solid #f0f0f1; }
		.opr-history__title { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #646970; }
		.opr-history { margin: 0 0 8px; }
		.opr-history li { display: grid; gap: 1px; margin: 0; padding: 8px 0; border-bottom: 1px solid #f0f0f1; }
		.opr-history li:first-child { padding-top: 0; }
		.opr-history__top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
		.opr-history__top strong { font-size: 14px; color: #b32d2e; }
		.opr-history small { color: #646970; overflow-wrap: anywhere; }
		.opr-history__reason { font-style: italic; }
		.opr-net { display: flex; justify-content: space-between; margin: 0 0 12px; font-size: 13px; }

		.opr-pick { margin: -2px 0 10px; padding: 10px 12px; border: 1px dashed #c3c4c7; border-radius: 8px; background: #fbfbfc; }
		.opr-pick[hidden] { display: none !important; }
		.opr-pick__head { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; margin: 0 0 6px; font-weight: 600; }
		.opr-pick__head small { font-weight: 400; color: #787c82; }
		.opr-pick__row { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 4px 8px; padding: 6px 0; border-top: 1px solid #f0f0f1; cursor: pointer; }
		.opr-pick__row:first-of-type { border-top: 0; }
		.opr-pick__row input[type="checkbox"] { margin: 0; }
		.opr-pick__name { min-width: 0; overflow-wrap: anywhere; line-height: 1.35; }
		.opr-pick__name small { display: block; color: #787c82; }
		.opr-pick__qty { display: inline-flex; align-items: center; gap: 4px; margin-top: 3px; color: #50575e; font-size: 12px; }
		.opr-pick__qty select { min-height: 26px; padding: 0 22px 0 6px; font-size: 12px; line-height: 1; }
		.opr-pick__amt { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
		.opr-pick__amt small { display: block; font-weight: 400; color: #787c82; font-size: 11px; }
		.opr-pick__foot { display: flex; justify-content: space-between; gap: 8px; margin-top: 6px; padding-top: 8px; border-top: 1px solid #dcdcde; font-variant-numeric: tabular-nums; }
		.opr-pick__note { margin: 6px 0 0; color: #8a4b00; font-size: 12px; }
		.opr-void-note { margin: -2px 0 10px; padding: 8px 10px; border-left: 4px solid #dba617; border-radius: 4px; background: #fcf9e8; line-height: 1.45; }
		.opr-void-note[hidden] { display: none !important; }

		.opr-void .opr-void__text { margin: 0; }
		.opr-sched-open { margin-top: 8px !important; }
		.opr-sched-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid #f0e2b0; }
		.opr-sched-form[hidden], .opr-sched-confirm[hidden], .opr-sched-result[hidden] { display: none !important; }
		.opr-sched-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
		.opr-sched-label { font-weight: 600; }
		.opr-sched-label small { font-weight: 400; color: #787c82; }
		.opr-sched-label--block { display: block; margin-bottom: 4px; }
		.opr-sched-form .opr-pick { margin: 0 0 10px; background: #fff; }
		.opr-sched-form textarea { width: 100%; margin: 0 0 8px; }
		.opr-sched-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
		.opr-sched-confirm { margin-top: 10px; padding: 10px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
		.opr-sched-confirm__text { margin: 0; }
		.opr-sched-result { margin: 10px 0 0; padding: 8px 10px; border-radius: 6px; background: #edfaef; color: #0e4a2c; }

		.opr-sched { margin: 0 0 12px; padding: 10px 12px; border: 1px solid #c5e3f3; border-left: 4px solid #2271b1; border-radius: 6px; background: #f0f6fc; }
		.opr-sched[data-state="failed"] { border-color: #f0c5c6; border-left-color: #d63638; background: #fcf0f1; }
		.opr-sched[data-state="unknown"] { border-color: #f0e2b0; border-left-color: #dba617; background: #fcf9e8; }
		.opr-sched p { margin: 0 0 6px; line-height: 1.45; }
		.opr-sched__head { display: flex; align-items: center; gap: 6px; }
		.opr-sched__head .dashicons { font-size: 16px; width: 16px; height: 16px; color: #2271b1; }
		.opr-sched[data-state="failed"] .opr-sched__head .dashicons { color: #d63638; }
		.opr-sched[data-state="unknown"] .opr-sched__head .dashicons { color: #996800; }
		.opr-sched__amount { margin-left: auto; font-variant-numeric: tabular-nums; }
		.opr-sched small { display: block; color: #50575e; overflow-wrap: anywhere; }
		.opr-sched__actions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
		.opr-sched__cancel { color: #b32d2e !important; border-color: #e0a3a3 !important; }
		.opr-sched__cancel[data-armed="1"] { background: #d63638 !important; border-color: #d63638 !important; color: #fff !important; }
		.opr-sched__msg { margin: 8px 0 0 !important; font-size: 12px; }
		.opr-sched__msg[hidden] { display: none; }

		.opr-seg { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin: 0 0 8px; border: 1px solid #8c8f94; border-radius: 6px; overflow: hidden; }
		.opr-seg button { min-height: 30px; padding: 0 6px; border: 0; background: #fff; color: #1d2327; cursor: pointer; font-size: 13px; }
		.opr-seg button + button { border-left: 1px solid #8c8f94; }
		.opr-seg button[aria-pressed="true"] { background: #2271b1; color: #fff; }

		.opr-dialog { width: min(480px, calc(100vw - 32px)); max-height: calc(100vh - 48px); padding: 0; border: 0; border-radius: 12px; box-shadow: 0 24px 64px rgba(0, 0, 0, .28); color: #1d2327; overflow: hidden; }
		.opr-dialog[open] { display: flex; flex-direction: column; }
		.opr-dialog::backdrop { background: rgba(16, 24, 32, .55); }
		.opr-dialog [hidden] { display: none !important; }
		.opr-dialog__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 18px 20px 14px; border-bottom: 1px solid #f0f0f1; }
		.opr-dialog__head h2 { margin: 0; font-size: 18px; line-height: 1.3; }
		.opr-dialog__head p { margin: 2px 0 0; color: #646970; }
		.opr-x { display: grid; place-items: center; width: 32px; height: 32px; margin: -4px -6px 0 0; padding: 0; border: 0; border-radius: 6px; background: none; color: #646970; cursor: pointer; }
		.opr-x:hover { background: #f0f0f1; color: #1d2327; }
		.opr-dialog__body { padding: 18px 20px; overflow-y: auto; }
		.opr-dialog__foot { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 20px; background: #f6f7f7; border-top: 1px solid #dcdcde; }
		.opr-dialog__foot .button { min-height: 36px; padding: 0 16px; }
		.opr-info { margin: 0 0 14px; padding: 10px 12px; border-radius: 8px; background: #eef7fc; color: #0d3b56; line-height: 1.5; }
		.opr-alert { margin: 0 0 14px; padding: 10px 12px; border-left: 4px solid #d63638; border-radius: 4px; background: #fcf0f1; }
		.opr-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
		.opr-stats div { padding: 10px 12px; border: 1px solid #e3e9ef; border-radius: 8px; background: #f8fafc; }
		.opr-stats span { display: block; font-size: 12px; color: #646970; }
		.opr-stats strong { display: block; font-size: 16px; font-variant-numeric: tabular-nums; }
		.opr-stats .is-key { border-color: #c5e3f3; background: #eef7fc; }
		.opr-legend { margin: 0 0 6px; font-weight: 600; }
		.opr-option { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; padding: 10px 12px; border: 1px solid #dcdcde; border-radius: 8px; cursor: pointer; }
		.opr-option:has(input[type="radio"]:checked) { border-color: #2271b1; background: #f0f6fc; box-shadow: 0 0 0 1px #2271b1; }
		.opr-option input[type="radio"] { margin: 0; }
		.opr-option__label { flex: 1; font-weight: 500; }
		.opr-option__value { font-weight: 600; font-variant-numeric: tabular-nums; }
		.opr-money { display: flex; align-items: center; width: 120px; border: 1px solid #8c8f94; border-radius: 4px; background: #fff; }
		.opr-money > span { padding: 0 2px 0 8px; color: #646970; }
		.opr-money input { width: 100%; min-height: 30px; border: 0 !important; box-shadow: none !important; text-align: right; font-variant-numeric: tabular-nums; }
		.opr-money:focus-within { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
		.opr-error { margin: -2px 0 10px; color: #b32d2e; }
		.opr-field { margin-bottom: 14px; }
		.opr-field label { display: block; margin-bottom: 6px; font-weight: 600; }
		.opr-field label span { margin-left: 4px; color: #787c82; font-weight: 400; font-size: 12px; }
		.opr-field select, .opr-field input[type="text"], .opr-field textarea { width: 100%; max-width: none; }
		.opr-check { display: flex; align-items: flex-start; gap: 8px; }
		.opr-check input { margin-top: 2px !important; }
		.opr-check small { display: block; color: #646970; }
		.opr-confirm { margin-bottom: 16px; padding: 18px; border-radius: 10px; background: #f6f7f7; text-align: center; }
		.opr-confirm__eyebrow { margin: 0; color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
		.opr-confirm__amount { margin: 2px 0; font-size: 32px; font-weight: 600; line-height: 1.2; font-variant-numeric: tabular-nums; }
		.opr-confirm__lead { margin: 0; color: #50575e; }
		.opr-summary { margin: 0 0 6px; }
		.opr-summary div { display: grid; grid-template-columns: 120px 1fr; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f0f0f1; }
		.opr-summary dt { color: #646970; }
		.opr-summary dd { margin: 0; overflow-wrap: anywhere; }
		.opr-state { padding: 20px 0 8px; text-align: center; }
		.opr-state__title { margin: 12px 0 4px; font-size: 16px; font-weight: 600; }
		.opr-state__text { margin: 0; color: #646970; }
		@media (max-width: 480px) {
			.opr-stats strong { font-size: 14px; }
			.opr-summary div { grid-template-columns: 1fr; gap: 2px; }
		}
	</style>
	<?php
});
