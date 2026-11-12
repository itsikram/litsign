<?php
/**
 * Card payments (Elavon Converge) run in the SFS Wallet Payments plugin since 2026-11-12
 * (wp-content/plugins/sfs-wallet-payments/converge/). This file only loads when that plugin
 * is missing or inactive (inc/payments.php and inc/refunds.php check on after_setup_theme),
 * so the site never breaks without it:
 *
 *  - checkout, the pay pages and Settings render as usual;
 *  - a card payment answers "please call us", charges nothing, and staff get one email an hour;
 *  - "Disable card payment" checkouts still create the order for manual payment;
 *  - the order screen says refunds need the plugin;
 *  - administrators see a notice to activate the plugin.
 *
 * Each function is only defined when nothing else defined it.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('wholesale_payment_method')) {
	/**
	 * 'lightbox' (Converge secure window) or 'direct' (card fields on our page).
	 */
	function wholesale_payment_method()
	{
		return 'direct' === wholesale_get_setting('payment_method') ? 'direct' : 'lightbox';
	}
}

if (!function_exists('wholesale_converge_credentials')) {
	/**
	 * No gateway without the plugin, so pages that check this offer the phone instead.
	 */
	function wholesale_converge_credentials()
	{
		return null;
	}
}

if (!function_exists('wholesale_payment_json_error')) {
	function wholesale_payment_json_error($message, $status = 400, $field = '')
	{
		wp_send_json(array_filter(array('ok' => false, 'error' => $message, 'field' => $field), static function ($value) {
			return '' !== $value;
		}), $status);
	}
}

if (!function_exists('wholesale_payment_gateway_missing_alert')) {
	/**
	 * Tells staff that card payments are off. "start": at most one email an hour.
	 * "complete": a customer may have paid in the Converge window, so always (20 an hour at most).
	 */
	function wholesale_payment_gateway_missing_alert($step = 'start')
	{
		$hour_key = 'wholesale_gateway_missing_' . $step . '_' . gmdate('YmdH');
		$sent = (int) get_transient($hour_key);
		if ($sent >= ('complete' === $step ? 20 : 1)) {
			return;
		}
		set_transient($hour_key, $sent + 1, HOUR_IN_SECONDS);

		$lines = array();
		if ('complete' === $step) {
			$ref = isset($_POST['ref']) ? preg_replace('/[^A-Z0-9]/', '', (string) wp_unslash($_POST['ref'])) : '';
			$txn = isset($_POST['txn_id']) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string) wp_unslash($_POST['txn_id'])) : '';
			$lines[] = 'A customer finished paying in the Converge window, but no order could be created because card payments are off on this site. The card MAY HAVE BEEN CHARGED.';
			$lines[] = 'Look up invoice ' . ('' !== $ref ? $ref : '(unknown)') . ('' !== $txn ? ' / transaction ' . $txn : '') . ' in Converge and contact the customer.';
		} else {
			$lines[] = 'A customer just tried to pay by card, but card payments are off on this site. No card was charged and no order was placed; the customer was asked to call 866-436-2101.';
		}
		$lines[] = '';
		$lines[] = 'Card payments (Elavon Converge) now run in the SFS Wallet Payments plugin, which is not active. Activate it to turn card payments back on:';
		$lines[] = admin_url('plugins.php');
		$lines[] = '';
		$lines[] = 'Time: ' . wp_date('M j, Y g:i a T');

		$subject = 'URGENT: card payments are off (SFS Wallet Payments plugin is not active)';
		if (function_exists('wholesale_mail_team')) {
			wholesale_mail_team($subject, implode("\n", $lines));
		} else {
			wp_mail(get_option('admin_email'), $subject, implode("\n", $lines));
		}
	}
}

if (!function_exists('wholesale_ajax_payment_start')) {
	/**
	 * Checkout / pay pages without the plugin. "Disable card payment" checkouts still create the
	 * order for manual payment (no card involved); a card payment is answered with the phone number.
	 */
	function wholesale_ajax_payment_start()
	{
		if (!check_ajax_referer('wholesale_payment', 'nonce', false)) {
			wholesale_payment_json_error('Your session expired. Please refresh the page and try again.', 403);
		}

		$kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
		if ('cart' === $kind && wholesale_setting_enabled('payment_disabled')) {
			$password = isset($_POST['account_password']) ? (string) wp_unslash($_POST['account_password']) : '';
			$cart = wholesale_get_cart();
			$checkout = wholesale_checkout_build($_POST, $cart);
			if (is_wp_error($checkout)) {
				$data = $checkout->get_error_data();
				wholesale_payment_json_error($checkout->get_error_message(), 400, is_array($data) ? ($data['field'] ?? '') : '');
			}
			if ($account_error = wholesale_checkout_account_error($checkout, $password)) {
				wholesale_payment_json_error($account_error, 400, false !== stripos($account_error, 'password must') ? 'account_password' : 'billing_email');
			}
			$order = wholesale_checkout_create_order($checkout, $cart, array('_payment_status' => 'manual'));
			if (!$order) {
				wholesale_payment_json_error('We could not create your order. Please try again or call 866-436-2101.', 500);
			}
			wholesale_checkout_attach_account($order, $checkout, $password);
			wp_send_json(array('ok' => true, 'redirect' => wholesale_thank_you_url($order)));
		}

		wholesale_payment_gateway_missing_alert('start');
		wholesale_payment_json_error('Online card payment is not available right now, so no charge was made. Please call us at 866-436-2101 to place your order.', 503);
	}
	add_action('wp_ajax_wholesale_payment_start', 'wholesale_ajax_payment_start');
	add_action('wp_ajax_nopriv_wholesale_payment_start', 'wholesale_ajax_payment_start');
}

if (!function_exists('wholesale_ajax_payment_complete')) {
	function wholesale_ajax_payment_complete()
	{
		wholesale_payment_gateway_missing_alert('complete');
		wholesale_payment_json_error('We could not finish your order. If you were charged, please call us at 866-436-2101 and we will confirm it.', 503);
	}
	add_action('wp_ajax_wholesale_payment_complete', 'wholesale_ajax_payment_complete');
	add_action('wp_ajax_nopriv_wholesale_payment_complete', 'wholesale_ajax_payment_complete');
}

if (!function_exists('wholesale_render_refund_panel')) {
	/**
	 * Order screen, Payment box: Converge refunds need the plugin.
	 */
	function wholesale_render_refund_panel($post)
	{
		if (!get_post_meta($post->ID, '_payment_txn_id', true)) {
			return;
		}
		echo '<p class="description">Card refunds run in the SFS Wallet Payments plugin, which is not active. Activate it to refund from here, or refund in Converge.</p>';
	}
}

if (!function_exists('wholesale_payment_gateway_missing_notice')) {
	function wholesale_payment_gateway_missing_notice()
	{
		if (!current_user_can('activate_plugins')) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p><strong>Card payments are off.</strong> Elavon Converge card payments run in the <strong>SFS Wallet Payments</strong> plugin, which is not active, so customers are asked to call. <a href="%s">Activate it under Plugins</a>.</p></div>',
			esc_url(admin_url('plugins.php'))
		);
	}
	add_action('admin_notices', 'wholesale_payment_gateway_missing_notice');
}
