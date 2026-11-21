<?php
/**
 * Refund history on orders: who may refund, the _refund_log entries and the totals.
 * Shared by the order screen and the SFS Wallet Payments plugin.
 *
 * Converge card refunds (void / return, the Refund dialog on the order screen) moved to the
 * SFS Wallet Payments plugin on 2026-11-12: converge/refunds.php. Without the plugin,
 * inc/payment-gateway-missing.php shows a note in the Payment box instead.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

function retails_refund_capability()
{
	return apply_filters('retails_refund_capability', 'manage_options');
}

/**
 * Every refund attempt on the order, oldest first. Each entry: time, user, amount,
 * method (void|return), result (approved|declined|unknown), reference, message, reason, emailed.
 */
function retails_order_refund_log($post_id)
{
	$log = array_values(array_filter(get_post_meta($post_id, '_refund_log'), static function ($entry) {
		return is_array($entry) && !empty($entry['time']);
	}));
	usort($log, static function ($a, $b) {
		return (int) $a['time'] <=> (int) $b['time'];
	});
	return $log;
}

/**
 * @return array{charged:float,refunded:float,remaining:float}
 */
function retails_order_refund_totals($post_id)
{
	$charged = (float) get_post_meta($post_id, '_payment_amount', true);
	if ($charged <= 0) {
		$charged = retails_order_total($post_id);
	}
	$refunded = 0.0;
	foreach (retails_order_refund_log($post_id) as $entry) {
		if ('approved' === ($entry['result'] ?? '')) {
			$refunded += (float) $entry['amount'];
		}
	}
	return array(
		'charged' => round($charged, 2),
		'refunded' => round($refunded, 2),
		'remaining' => max(0.0, round($charged - $refunded, 2)),
	);
}

function retails_refund_money($amount)
{
	return '$' . number_format((float) $amount, 2);
}

function retails_refund_activity_text($entry)
{
	$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
	$by = $user ? ' by ' . $user->display_name : '';
	$amount = retails_refund_money($entry['amount'] ?? 0);
	switch ($entry['result'] ?? '') {
		case 'approved':
			return ('void' === ($entry['method'] ?? '') ? 'Payment voided: ' : 'Refunded: ') . $amount . $by . (!empty($entry['emailed']) ? ' (customer emailed)' : '');
		case 'unknown':
			return 'Refund of ' . $amount . ' got no answer from Converge' . $by;
		default:
			return 'Refund of ' . $amount . ' declined' . $by . (!empty($entry['message']) ? ': ' . $entry['message'] : '');
	}
}

add_action('after_setup_theme', static function () {
	if (!function_exists('retails_render_refund_panel')) {
		require_once __DIR__ . '/payment-gateway-missing.php';
	}
}, 20);
