<?php
/**
 * Card payments through Converge Hosted Payments (Lightbox).
 *
 * Card details are typed into Converge's own secure window and never reach this server:
 *  1. wholesale_payment_start: the server validates the order, fixes the amount, and asks
 *     Converge for a one-time session token for exactly that amount.
 *  2. The browser opens PayWithConverge.open() with the token.
 *  3. wholesale_payment_complete: on approval the server looks the transaction up with
 *     Converge (txnquery), checks amount + invoice, and only then creates the order.
 *
 * Settings → Storefront Sign → "Card payment method" can instead use the older direct
 * method: card fields on our own page, charged server-to-server (wholesale_converge_direct_sale).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/*
 * The Converge card gateway stays in this theme on the trade site. The retail site moved the
 * same code to the SFS Wallet Payments plugin on 2026-11-12; that plugin only loads its copy
 * when the theme doesn't define these functions, so installing it here later changes nothing.
 */

/**
 * 'lightbox' (Converge secure window) or 'direct' (card fields on our page).
 */
function wholesale_payment_method()
{
	return 'direct' === wholesale_get_setting('payment_method') ? 'direct' : 'lightbox';
}

/**
 * Direct method: validate the card fields from the form. Returns the card data or WP_Error.
 * Card data is only held in memory for the one gateway request and is never stored or logged.
 */
function wholesale_direct_card_from_request($post)
{
	$digits = static function ($key) use ($post) {
		return isset($post[$key]) ? preg_replace('/\D+/', '', (string) wp_unslash($post[$key])) : '';
	};
	$card = array(
		'number' => $digits('card_number'),
		'cvv' => $digits('card_cvv'),
		'month' => $digits('card_exp_month'),
		'year' => $digits('card_exp_year'),
	);
	// Checkout sends one "MM / YY" field; the payment-ticket page sends month and year.
	if ('' === $card['month'] && preg_match('/^(\d{1,2})\D*(\d{2}|\d{4})$/', trim((string) wp_unslash($post['card_exp'] ?? '')), $exp)) {
		$card['month'] = sprintf('%02d', (int) $exp[1]);
		$card['year'] = substr($exp[2], -2);
	}
	$exp_year = 2000 + (int) $card['year'];
	$expired = $exp_year < (int) gmdate('Y') || ($exp_year === (int) gmdate('Y') && (int) $card['month'] < (int) gmdate('n'));

	if (strlen($card['number']) < 12 || strlen($card['number']) > 19) {
		return new WP_Error('card', 'Please check your card number.', array('field' => 'card_number'));
	}
	if (2 !== strlen($card['month']) || (int) $card['month'] < 1 || (int) $card['month'] > 12 || 2 !== strlen($card['year']) || $expired) {
		return new WP_Error('card', 'Please check your card expiration date.', array('field' => 'card_exp'));
	}
	if (strlen($card['cvv']) < 3 || strlen($card['cvv']) > 4) {
		return new WP_Error('card', 'Please check your card security code (CVV).', array('field' => 'card_cvv'));
	}
	return $card;
}

/**
 * Direct method: charge the card server-to-server (Converge process.do, ccsale).
 *
 * @return array{ok:bool,message:string,txn_id:string,approval_code:string,card_last4:string}
 */
function wholesale_converge_direct_sale($amount, $card, $billing, $invoice)
{
	// 'declined': the processor answered no (counts toward the card-testing limit).
	// 'unknown': no answer came back, so the card may or may not have been charged.
	$result = array('ok' => false, 'message' => 'Payment failed. No charge was made.', 'txn_id' => '', 'approval_code' => '', 'card_last4' => substr($card['number'], -4), 'declined' => false, 'unknown' => false);
	$credentials = wholesale_converge_credentials();
	if (!$credentials) {
		$result['message'] = 'Online card payment is not available right now. Please call us at 866-436-2101 to place your order.';
		return $result;
	}

	$path = wholesale_setting_enabled('payment_test_mode') ? '/VirtualMerchantDemo/process.do' : '/VirtualMerchant/process.do';
	$response = wp_remote_post(wholesale_converge_host() . $path, array(
		'timeout' => 45,
		'body' => array_merge($credentials, array(
			'ssl_show_form' => 'false',
			'ssl_result_format' => 'ASCII',
			'ssl_transaction_type' => 'ccsale',
			'ssl_amount' => number_format((float) $amount, 2, '.', ''),
			'ssl_card_number' => $card['number'],
			'ssl_exp_date' => $card['month'] . $card['year'],
			'ssl_cvv2cvc2_indicator' => '1',
			'ssl_cvv2cvc2' => $card['cvv'],
			'ssl_invoice_number' => $invoice,
			'ssl_first_name' => mb_substr($billing['billing_fname'] ?? '', 0, 20),
			'ssl_last_name' => mb_substr($billing['billing_lname'] ?? '', 0, 30),
			'ssl_avs_address' => mb_substr($billing['billing_address'] ?? '', 0, 30),
			'ssl_avs_zip' => mb_substr($billing['billing_zip'] ?? '', 0, 9),
			'ssl_email' => $billing['billing_email'] ?? '',
		)),
	));

	if (is_wp_error($response)) {
		// A timeout can happen after Converge approved the charge, so staff must check.
		error_log('Wholesale: direct Converge sale request failed for invoice ' . $invoice . ': ' . $response->get_error_message());
		$result['message'] = 'We could not reach our payment processor. Please call us at 866-436-2101 before trying again, so you are not charged twice.';
		$result['unknown'] = true;
		return $result;
	}

	$fields = array();
	foreach (preg_split('/\r\n|\n|\s+(?=\w+=)/', trim(wp_remote_retrieve_body($response))) as $line) {
		$pair = explode('=', $line, 2);
		if (2 === count($pair)) {
			$fields[trim($pair[0])] = trim($pair[1]);
		}
	}

	if (isset($fields['ssl_result']) && '0' === $fields['ssl_result']) {
		$result['ok'] = true;
		$result['message'] = 'Approved';
		$result['txn_id'] = $fields['ssl_txn_id'] ?? '';
		$result['approval_code'] = $fields['ssl_approval_code'] ?? '';
		return $result;
	}

	if (!empty($fields['errorCode'])) {
		error_log('Wholesale: direct Converge sale error ' . $fields['errorCode'] . ' (' . ($fields['errorName'] ?? '') . ') for invoice ' . $invoice);
		$result['message'] = 'Payment could not be processed: ' . sanitize_text_field($fields['errorMessage'] ?? $fields['errorName'] ?? 'unknown error') . '. No charge was made. Please check your card details or call us at 866-436-2101.';
		$result['declined'] = true;
	} elseif (isset($fields['ssl_result']) || !empty($fields['ssl_result_message'])) {
		$reason = sanitize_text_field($fields['ssl_result_message'] ?? '');
		$result['message'] = 'Your card was declined' . ('' !== $reason ? ' (' . $reason . ')' : '') . '. No charge was made. Please try another card or call us at 866-436-2101.';
		$result['declined'] = true;
	} else {
		// An empty or unreadable answer says nothing about the charge either.
		error_log('Wholesale: direct Converge sale for invoice ' . $invoice . ' returned no result (HTTP ' . (int) wp_remote_retrieve_response_code($response) . ').');
		$result['message'] = 'We could not confirm your payment with our processor. Please call us at 866-436-2101 before trying again, so you are not charged twice.';
		$result['unknown'] = true;
	}
	return $result;
}

/**
 * The session cart. utils/Cart.php creates it on include; AJAX requests don't load a
 * template, so load it here.
 */
function wholesale_get_cart()
{
	global $cart;
	if (!class_exists('Cart', false)) {
		require_once get_template_directory() . '/utils/Cart.php';
	}
	if (!($cart instanceof Cart)) {
		$cart = new Cart();
	}
	return $cart;
}

function wholesale_converge_host()
{
	return wholesale_setting_enabled('payment_test_mode') ? 'https://api.demo.convergepay.com' : 'https://api.convergepay.com';
}

function wholesale_converge_script_url()
{
	return wholesale_converge_host() . '/hosted-payments/PayWithConverge.js';
}

function wholesale_converge_credentials()
{
	$credentials = array(
		'ssl_merchant_id' => (string) wholesale_get_setting('merchant_id'),
		'ssl_user_id' => (string) wholesale_get_setting('gateway_user_id'),
		'ssl_pin' => (string) wholesale_get_setting('gateway_pin'),
	);
	return in_array('', $credentials, true) ? null : $credentials;
}

/**
 * Server-to-server request for a Lightbox session token (valid 15 minutes, single use).
 */
function wholesale_converge_session_token($amount, $invoice, $billing)
{
	$credentials = wholesale_converge_credentials();
	if (!$credentials) {
		return new WP_Error('not_configured', 'Online card payment is not available right now. Please call us at 866-436-2101 to place your order.');
	}

	$response = wp_remote_post(wholesale_converge_host() . '/hosted-payments/transaction_token', array(
		'timeout' => 20,
		'body' => array_merge($credentials, array(
			'ssl_transaction_type' => 'ccsale',
			'ssl_amount' => number_format((float) $amount, 2, '.', ''),
			'ssl_invoice_number' => $invoice,
			'ssl_first_name' => mb_substr($billing['billing_fname'] ?? '', 0, 20),
			'ssl_last_name' => mb_substr($billing['billing_lname'] ?? '', 0, 30),
			'ssl_company' => mb_substr($billing['billing_company'] ?? '', 0, 50),
			'ssl_avs_address' => mb_substr($billing['billing_address'] ?? '', 0, 30),
			'ssl_city' => mb_substr($billing['billing_city'] ?? '', 0, 30),
			'ssl_state' => mb_substr($billing['billing_state'] ?? '', 0, 30),
			'ssl_avs_zip' => mb_substr($billing['billing_zip'] ?? '', 0, 9),
			'ssl_email' => $billing['billing_email'] ?? '',
		)),
	));

	$body = is_wp_error($response) ? '' : trim(wp_remote_retrieve_body($response));
	// A token is a single opaque string; anything with spaces or tags is an error page/message.
	if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response) || '' === $body || preg_match('/[\s<]/', $body)) {
		$code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
		error_log(sprintf(
			'Wholesale: Converge session token request failed (HTTP %d, %s endpoint): %s%s',
			$code,
			wholesale_setting_enabled('payment_test_mode') ? 'demo' : 'live',
			is_wp_error($response) ? $response->get_error_message() : substr(wp_strip_all_tags($body), 0, 300),
			403 === $code ? ' — the Converge user is not enabled for Hosted Payments (or demo/live credentials are mixed up).' : ''
		));
		return new WP_Error('token_failed', 'We could not connect to our secure payment processor. Please try again in a minute, or call us at 866-436-2101.');
	}

	return $body;
}

/**
 * Server-to-server XML request to Converge (processxml.do).
 *
 * @return SimpleXMLElement|WP_Error  WP_Error code 'unreachable' means no answer came back,
 *                                    so a money-moving request may or may not have run.
 */
function wholesale_converge_xml_request($fields, $timeout = 30)
{
	$credentials = wholesale_converge_credentials();
	if (!$credentials) {
		return new WP_Error('not_configured', 'Payment gateway is not configured.');
	}

	$xml = '<txn>';
	foreach (array_merge($credentials, $fields) as $key => $value) {
		$xml .= '<' . $key . '>' . esc_html($value) . '</' . $key . '>';
	}
	$xml .= '</txn>';

	$path = wholesale_setting_enabled('payment_test_mode') ? '/VirtualMerchantDemo/processxml.do' : '/VirtualMerchant/processxml.do';
	$response = wp_remote_post(wholesale_converge_host() . $path, array(
		'timeout' => $timeout,
		'body' => array('xmldata' => $xml),
	));
	if (is_wp_error($response)) {
		return new WP_Error('unreachable', $response->get_error_message());
	}

	$previous = libxml_use_internal_errors(true);
	$doc = simplexml_load_string(wp_remote_retrieve_body($response));
	libxml_use_internal_errors($previous);
	// SimpleXML treats an element without children as false. For a lookup that is still an
	// answer: an empty <txnlist/> means Converge has no such transaction (not_found below).
	if (false === $doc || (!$doc && 'txnquery' !== ($fields['ssl_transaction_type'] ?? ''))) {
		return new WP_Error('bad_response', 'Converge returned an unreadable response.');
	}
	if (isset($doc->errorCode)) {
		return new WP_Error('converge_error', trim((string) $doc->errorCode . ' ' . (string) $doc->errorMessage));
	}
	return $doc;
}

function wholesale_converge_xml_fields($txn)
{
	$fields = array();
	foreach ($txn->children() as $child) {
		$fields[$child->getName()] = trim((string) $child);
	}
	return $fields;
}

/**
 * Looks a transaction up with Converge. Returns the transaction fields, or WP_Error.
 */
function wholesale_converge_query_transaction($txn_id)
{
	$doc = wholesale_converge_xml_request(array('ssl_transaction_type' => 'txnquery', 'ssl_txn_id' => $txn_id));
	if (is_wp_error($doc)) {
		return $doc;
	}

	// Responses are either <txnlist><txn>…</txn></txnlist> or a single <txn>.
	$candidates = isset($doc->txn) ? $doc->txn : array($doc);
	foreach ($candidates as $txn) {
		$fields = wholesale_converge_xml_fields($txn);
		if (isset($fields['ssl_txn_id']) && $fields['ssl_txn_id'] === $txn_id) {
			return $fields;
		}
	}

	return new WP_Error('not_found', 'Converge has no record of this transaction.');
}

/**
 * Confirms an approved Lightbox payment really happened for this amount and invoice.
 *
 * @return true|WP_Error  WP_Error code 'unverified' means Converge couldn't be reached
 *                        (the payment may be fine); any other code means it must be rejected.
 */
function wholesale_converge_verify($txn_id, $amount, $invoice)
{
	$txn = wholesale_converge_query_transaction($txn_id);
	if (is_wp_error($txn)) {
		// Only "no usable answer" (no reply, an unreadable reply, no gateway settings) leaves the
		// payment unknown. A definite answer from Converge, such as an error code for an unknown
		// transaction ID ('converge_error') or no record of it ('not_found'), is a rejection.
		return in_array($txn->get_error_code(), array('unreachable', 'bad_response', 'not_configured'), true)
			? new WP_Error('unverified', $txn->get_error_message())
			: $txn;
	}

	if (abs((float) ($txn['ssl_amount'] ?? 0) - (float) $amount) > 0.005) {
		return new WP_Error('amount_mismatch', sprintf('Charged amount %s does not match the order total %s.', $txn['ssl_amount'] ?? '?', number_format($amount, 2, '.', '')));
	}
	if (!empty($txn['ssl_invoice_number']) && $txn['ssl_invoice_number'] !== $invoice) {
		return new WP_Error('invoice_mismatch', 'Transaction belongs to a different checkout.');
	}
	if (isset($txn['ssl_result_message']) && 0 !== stripos($txn['ssl_result_message'], 'APPROV')) {
		return new WP_Error('not_approved', 'Transaction was not approved: ' . $txn['ssl_result_message']);
	}
	// Failed-by-rule statuses (fraud prevention, pre/post-auth rules).
	if (isset($txn['ssl_trans_status']) && in_array(strtoupper($txn['ssl_trans_status']), array('FPR', 'PRE', 'PST'), true)) {
		return new WP_Error('not_approved', 'Transaction failed a fraud or authorization rule (' . $txn['ssl_trans_status'] . ').');
	}

	return true;
}

/**
 * Validates the checkout form against the server-side cart.
 *
 * @return array|WP_Error Everything needed to create the order.
 */
function wholesale_checkout_build($post, $cart)
{
	$field = static function ($key) use ($post) {
		return isset($post[$key]) && !is_array($post[$key]) ? sanitize_text_field(wp_unslash($post[$key])) : '';
	};

	$billing = array(
		'billing_email' => isset($post['billing_email']) ? sanitize_email(wp_unslash($post['billing_email'])) : '',
		'billing_fname' => $field('billing_fname'),
		'billing_lname' => $field('billing_lname'),
		'billing_company' => $field('billing_company'),
		'billing_address' => $field('billing_address'),
		'billing_address_2' => $field('billing_address_2'),
		'billing_city' => $field('billing_city'),
		'billing_state' => $field('billing_state'),
		'billing_zip' => $field('billing_zip'),
		'billing_country' => $field('billing_country') ?: 'United States',
		'billing_tel' => $field('billing_tel'),
	);
	foreach (array('billing_fname' => 'first name', 'billing_lname' => 'last name', 'billing_address' => 'address', 'billing_city' => 'city', 'billing_state' => 'state', 'billing_zip' => 'ZIP code') as $key => $label) {
		if ('' === $billing[$key]) {
			return new WP_Error('billing', sprintf('Please enter your billing %s.', $label), array('field' => $key));
		}
	}
	if (!is_email($billing['billing_email'])) {
		return new WP_Error('billing', 'Please enter a valid email address.', array('field' => 'billing_email'));
	}

	$pickup = 'pickup' === $field('delivery_method') ? wholesale_pickup_location() : null;
	if ('pickup' === $field('delivery_method') && !$pickup) {
		return new WP_Error('shipping_method', 'Store pickup isn’t available right now. Please choose a shipping method.');
	}

	if ($pickup || 'on' === $field('same_shipping_address')) {
		// Pickup orders keep the customer's details as the pickup contact.
		$shipping = $billing;
	} else {
		$shipping = array();
		foreach (array('fname', 'lname', 'company', 'address', 'address_2', 'city', 'state', 'zip', 'country', 'tel') as $key) {
			$shipping['shipping_' . $key] = $field('shipping_' . $key);
		}
		foreach (array('shipping_fname' => 'first name', 'shipping_lname' => 'last name', 'shipping_address' => 'address', 'shipping_city' => 'city', 'shipping_state' => 'state', 'shipping_zip' => 'ZIP code') as $key => $label) {
			if ('' === $shipping[$key]) {
				return new WP_Error('shipping', sprintf('Please enter the shipping %s.', $label), array('field' => $key));
			}
		}
	}

	$items = $cart->get_items();
	if (empty($items)) {
		return new WP_Error('empty', 'Your cart is empty.');
	}
	foreach ($items as $item) {
		$product_id = absint($item->product_id ?? 0);
		if (!$product_id || 'product' !== get_post_type($product_id) || 'publish' !== get_post_status($product_id) || absint($item->product_quantity ?? 0) < 1 || (float) ($item->product_subtotal ?? 0) <= 0) {
			return new WP_Error('cart', 'Your cart contains an item that is no longer available. Please review your cart.');
		}
	}

	// Shipping method (inc/shipping.php). The amount always comes from the
	// server; the page's quote is only compared so a total that changed since
	// the page loaded (cart edited in another tab, free shipping lost) is
	// confirmed by the customer instead of charged silently.
	$carrier = '';
	$blind_ship = false;
	if ($pickup) {
		$requested_shipping = 0.0;
		$shipping_label = 'Store Pickup (' . $pickup['city'] . ')';
		$shipping['delivery_method'] = 'pickup';
		$shipping['pickup_location'] = $pickup['address'];
	} else {
		$methods = wholesale_cart_shipping_methods($cart);
		$choice = $field('shipping_method');
		if (!isset($methods[$choice])) {
			return new WP_Error('shipping_method', 'Please choose a shipping method.');
		}
		$method = $methods[$choice];
		$requested_shipping = (float) $method['amount'];
		$quote = $field('shipping_quote');
		if ('' !== $quote && is_numeric($quote) && abs((float) $quote - $requested_shipping) > 0.005) {
			return new WP_Error('shipping_method', sprintf('%s now costs %s for this order. Please review your shipping choice and try again.', $method['label'], $requested_shipping > 0 ? '$' . number_format($requested_shipping, 2) : 'nothing'));
		}
		$carrier = $method['carrier'];
		$shipping_label = $method['label'] . ($method['free'] ? ' (Free Shipping)' : '');
		$shipping['delivery_method'] = 'ship';
		$shipping['shipping_carrier'] = $carrier;
		$shipping['shipping_service'] = $method['label'];

		// Blind Drop Ship: the customer's business is the return address.
		if ('1' === $field('blind_ship')) {
			$blind_ship = true;
			$shipping['blind_ship'] = 'yes';
			foreach (wholesale_blind_ship_fields() as $key => $info) {
				$value = $field('blind_from_' . $key);
				if ($info['required'] && '' === $value) {
					return new WP_Error('blind_ship', sprintf('Please enter the %s for your blind ship return address.', str_replace('zip', 'ZIP', strtolower($info['label']))), array('field' => 'blind_from_' . $key));
				}
				$shipping['blind_from_' . $key] = $value;
			}
		}
	}

	$sub_total = round((float) $cart->sub_total, 2);
	$tax = round($sub_total * (float) wholesale_get_setting('tax_rate') / 100, 2);
	$grand_total = round($sub_total + $requested_shipping + $tax, 2);

	$shipping['shipping_cost'] = $requested_shipping;

	return array(
		'billing' => $billing,
		'shipping' => $shipping,
		'items' => $items,
		'sub_total' => $sub_total,
		'shipping_cost' => $requested_shipping,
		'shipping_label' => $shipping_label,
		'delivery_method' => $pickup ? 'pickup' : 'ship',
		'shipping_carrier' => $carrier,
		'blind_ship' => $blind_ship,
		'tax' => $tax,
		'grand_total' => $grand_total,
		'comment' => isset($post['comment']) ? sanitize_textarea_field(wp_unslash($post['comment'])) : '',
		'estimate_delivery_time' => wholesale_estimated_ship_date($cart),
		'create_account' => !is_user_logged_in() && '1' === $field('create_account'),
	);
}

function wholesale_cart_has_channel_letters($cart)
{
	foreach ($cart->get_items() as $item) {
		if (has_term('channel-letters', 'product_category', absint($item->product_id))) {
			return true;
		}
	}
	return false;
}

/**
 * Shipping rates for the cart, fastest-last (standard, 3-day, 2-day, overnight).
 */
function wholesale_cart_shipping_options($cart)
{
	$setting = wholesale_cart_has_channel_letters($cart) ? 'channel_shipping_options' : 'standard_shipping_options';
	return array_values(array_map('floatval', array_filter(explode(',', (string) wholesale_get_setting($setting)), 'is_numeric')));
}

/**
 * Subtotal at which the Free Shipping option appears for this cart, or 0 when
 * the offer is off. Carts with channel letters have their own, higher minimum.
 */
function wholesale_free_shipping_threshold($cart)
{
	$setting = wholesale_cart_has_channel_letters($cart) ? 'free_shipping_channel_min' : 'free_shipping_min';
	return max(0.0, (float) wholesale_get_setting($setting));
}

/**
 * Whether checkout offers Free Shipping, listed above the paid options and
 * delivered on Standard's schedule.
 */
function wholesale_cart_qualifies_free_shipping($cart)
{
	$threshold = wholesale_free_shipping_threshold($cart);
	return $threshold > 0 && round((float) $cart->sub_total, 2) >= $threshold;
}

/**
 * A free shipping minimum for display ("$100", "$1,000"; cents only when
 * set), or '' when that offer is off.
 */
function wholesale_free_shipping_amount($channel_letters = false)
{
	$amount = max(0.0, (float) wholesale_get_setting($channel_letters ? 'free_shipping_channel_min' : 'free_shipping_min'));
	return $amount > 0 ? '$' . number_format($amount, floor($amount) == $amount ? 0 : 2) : '';
}

/**
 * The cheapest paid shipping rate for display ("$18", "$50"), or '' when none
 * is set, so pages can say what shipping costs below the free shipping minimum.
 */
function wholesale_shipping_from_amount($channel_letters = false)
{
	$rates = array_filter(array_map('floatval', explode(',', (string) wholesale_get_setting($channel_letters ? 'channel_shipping_options' : 'standard_shipping_options'))));
	if (!$rates) {
		return '';
	}
	$amount = min($rates);
	return '$' . number_format($amount, floor($amount) == $amount ? 0 : 2);
}

function wholesale_free_shipping_label()
{
	return 'Free Shipping (3–6 business days)';
}

function wholesale_shipping_label($index)
{
	$labels = array('Standard (3–6 business days)', '3-Day', '2-Day', 'Overnight');
	return isset($labels[$index]) ? $labels[$index] : 'Shipping option ' . ((int) $index + 1);
}

/**
 * $date plus $days business days (Saturdays and Sundays skipped).
 */
function wholesale_add_business_days(DateTime $date, $days)
{
	$date = clone $date;
	while ($days > 0) {
		$date->modify('+1 day');
		if ($date->format('N') < 6) {
			$days--;
		}
	}
	return $date;
}

/**
 * Production business days for the cart: the longest product turnaround, and
 * at least 4 for channel letters, whose product pages say 2-4 business days.
 */
function wholesale_cart_production_days($cart)
{
	$turnaround = 1;
	// Trade site: a line keeps the days of the production time chosen for its quantity
	// (Cart::add_item(), from the product's _turnaround_spec), e.g. 5 for "5 Business Days".
	$is_trade = function_exists('wholesale_is_trade_site') && wholesale_is_trade_site();
	foreach ($cart->get_items() as $item) {
		$days = $is_trade && isset($item->turnaround_days) ? (int) $item->turnaround_days : (int) get_post_meta(absint($item->product_id), '_product_turnaround', true);
		$turnaround = max($turnaround, $days);
	}
	return wholesale_cart_has_channel_letters($cart) ? max($turnaround, 4) : $turnaround;
}

/**
 * When the order should ship: its production business days counted from the
 * order day in Pacific time, the shop's time zone (the site's WordPress time
 * zone may be UTC). Orders placed after 4pm or on a weekend count from the
 * next business day. The old version added calendar days plus transit, so
 * "Ships by" dates landed on weekends and included delivery time.
 */
function wholesale_estimated_ship_datetime($cart, ?DateTime $now = null)
{
	$now = $now ? clone $now : new DateTime('now');
	$now->setTimezone(new DateTimeZone('America/Los_Angeles'));
	$order_day = clone $now;
	$order_day->setTime(0, 0);
	if ($now->format('N') >= 6 || (int) $now->format('G') >= 16) {
		$order_day = wholesale_add_business_days($order_day, 1);
	}
	return wholesale_add_business_days($order_day, wholesale_cart_production_days($cart));
}

/**
 * Ship date as "Tue Oct. 13", saved with the order and shown as "Estimated to
 * ship by" on the thank-you page, order emails and order tracking. The ship
 * date doesn't depend on the shipping speed; the argument is kept for callers.
 */
function wholesale_estimated_ship_date($cart, $shipping_index = 0)
{
	return wholesale_estimated_ship_datetime($cart)->format('D M. j');
}

/**
 * Estimated delivery for a shipping speed (standard, 3-day, 2-day, overnight):
 * the ship date plus that speed's transit business days, using the slow end
 * of standard shipping's 3-6 days.
 */
function wholesale_estimated_delivery_date($cart, $shipping_index = 0)
{
	$transit = array(6, 3, 2, 1);
	return wholesale_add_business_days(wholesale_estimated_ship_datetime($cart), $transit[(int) $shipping_index] ?? 6)->format('D M. j');
}

/**
 * Creates the order for a validated checkout and empties the cart.
 */
function wholesale_checkout_create_order($checkout, $cart, $extra_meta = array())
{
	$order_cost = wp_json_encode(array(
		'grand_total' => $checkout['grand_total'],
		'sub_total' => $checkout['sub_total'],
		'shipping_cost' => $checkout['shipping_cost'],
		'shipping_method' => $checkout['shipping_label'],
		'delivery_method' => $checkout['delivery_method'] ?? 'ship',
		'tax' => $checkout['tax'],
	));

	// Staff see the carrier preselected when they add tracking, and can find
	// pickup and blind ship orders by meta.
	$extra_meta = array_merge(array_filter(array(
		'_tracking_carrier' => $checkout['shipping_carrier'] ?? '',
		'_delivery_method' => $checkout['delivery_method'] ?? 'ship',
		'_blind_ship' => !empty($checkout['blind_ship']) ? 1 : 0,
	)), $extra_meta);

	$order = wholesale_insert_order(
		wp_slash(wp_json_encode($checkout['items'])),
		wp_slash($order_cost),
		wp_slash(wp_json_encode($checkout['billing'])),
		wp_slash(wp_json_encode($checkout['shipping'])),
		$checkout['comment'],
		$checkout['estimate_delivery_time'],
		$extra_meta
	);

	if ($order) {
		$cart->empty();
		// The ordered builder designs are production artwork now: mark them, and take them out of
		// this visitor's session so designing again can't delete or overwrite them
		// (wholesale_store_session_cl_design()).
		foreach ($checkout['items'] as $item) {
			$design_id = absint($item->design_id ?? 0);
			if (!$design_id) {
				continue;
			}
			update_post_meta($design_id, '_wholesale_order', $order);
			if (isset($_SESSION['wholesale_design_uploads']) && is_array($_SESSION['wholesale_design_uploads'])) {
				$_SESSION['wholesale_design_uploads'] = array_values(array_diff(array_map('absint', $_SESSION['wholesale_design_uploads']), array($design_id)));
			}
			$session_design = wholesale_session_cl_design(absint($item->product_id ?? 0));
			if ($session_design && absint($session_design['design_id'] ?? 0) === $design_id) {
				unset($_SESSION['design_data_' . absint($item->product_id)]);
			}
		}
		// Next checkout starts with the same blind ship return address.
		if (!empty($checkout['blind_ship']) && is_user_logged_in()) {
			$from = array();
			foreach (array_keys(wholesale_blind_ship_fields()) as $key) {
				$from[$key] = (string) ($checkout['shipping']['blind_from_' . $key] ?? '');
			}
			update_user_meta(get_current_user_id(), '_blind_ship_from', $from);
		}
		wholesale_send_new_order_admin_email($order);
		do_action('wholesale_order_created', $order);
	}

	return $order;
}

function wholesale_create_checkout_account($email, $password, $first_name, $last_name, $telephone)
{
	$username = sanitize_user(strstr($email, '@', true), true) ?: 'customer';
	while (username_exists($username)) {
		$username = sanitize_user($username . wp_rand(100, 999), true);
	}

	$user_id = wp_insert_user(array(
		'user_login' => $username,
		'user_email' => $email,
		'user_pass' => $password,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'role' => 'subscriber',
		'meta_input' => array('telephone' => $telephone),
	));
	if (is_wp_error($user_id)) {
		return $user_id;
	}

	wp_set_current_user($user_id);
	wp_set_auth_cookie($user_id, true);
	return $user_id;
}

/**
 * Checks the optional "create an account" fields before anything is charged.
 */
function wholesale_checkout_account_error($checkout, $password)
{
	if (!$checkout['create_account']) {
		return '';
	}
	if (email_exists($checkout['billing']['billing_email'])) {
		return 'An account already exists for this email address. Please log in first, or uncheck "Create an account".';
	}
	if (strlen((string) $password) < 8) {
		return 'Your account password must be at least 8 characters.';
	}
	return '';
}

function wholesale_checkout_attach_account($order, $checkout, $password)
{
	if (!$order || !$checkout['create_account']) {
		return;
	}
	$billing = $checkout['billing'];
	$user_id = wholesale_create_checkout_account($billing['billing_email'], $password, $billing['billing_fname'], $billing['billing_lname'], $billing['billing_tel']);
	if (!is_wp_error($user_id)) {
		update_post_meta($order, 'user_id', $user_id);
	}
}

// ------------------------------------------------------------------
// Pending payments
// ------------------------------------------------------------------

function wholesale_payment_store($ref, $data = null)
{
	$key = 'wholesale_payment_' . $ref;
	if (null === $data) {
		return get_transient($key);
	}
	set_transient($key, $data, 30 * MINUTE_IN_SECONDS);
	return $data;
}

/**
 * @param string $field Optional form field name the error belongs to, so the page can highlight it.
 */
function wholesale_payment_json_error($message, $status = 400, $field = '')
{
	wp_send_json(array_filter(array('ok' => false, 'error' => $message, 'field' => $field), static function ($value) {
		return '' !== $value;
	}), $status);
}

/**
 * AJAX step 1: validate, fix the amount, return a Converge session token.
 * With card payment disabled it creates the order right away instead.
 */
function wholesale_ajax_payment_start()
{
	if (!check_ajax_referer('wholesale_payment', 'nonce', false)) {
		wholesale_payment_json_error('Your session expired. Please refresh the page and try again.', 403);
	}

	$kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
	$password = isset($_POST['account_password']) ? (string) wp_unslash($_POST['account_password']) : '';

	if ('cart' === $kind) {
		$cart = wholesale_get_cart();
		$checkout = wholesale_checkout_build($_POST, $cart);
		if (is_wp_error($checkout)) {
			$data = $checkout->get_error_data();
			wholesale_payment_json_error($checkout->get_error_message(), 400, is_array($data) ? ($data['field'] ?? '') : '');
		}
		if ($account_error = wholesale_checkout_account_error($checkout, $password)) {
			wholesale_payment_json_error($account_error, 400, false !== stripos($account_error, 'password must') ? 'account_password' : 'billing_email');
		}

		if (wholesale_setting_enabled('payment_disabled')) {
			$order = wholesale_checkout_create_order($checkout, $cart, array('_payment_status' => 'manual'));
			if (!$order) {
				wholesale_payment_json_error('We could not create your order. Please try again or call 866-436-2101.', 500);
			}
			wholesale_checkout_attach_account($order, $checkout, $password);
			wp_send_json(array('ok' => true, 'redirect' => wholesale_thank_you_url($order)));
		}

		$amount = $checkout['grand_total'];
		$billing = $checkout['billing'];
		$payload = array('checkout' => $checkout);
	} elseif ('ticket' === $kind) {
		$token = isset($_POST['ticket_token']) ? preg_replace('/[^A-Za-z0-9]/', '', (string) wp_unslash($_POST['ticket_token'])) : '';
		$ticket_id = wholesale_find_ticket_by_token($token);
		$ticket = $ticket_id ? wholesale_get_ticket($ticket_id) : null;
		if (!$ticket || 'sent' !== $ticket['status'] || wholesale_ticket_is_expired($ticket) || $ticket['amount'] <= 0 || empty($ticket['items'])) {
			wholesale_payment_json_error('This payment request is no longer open. Please contact us if you need help.');
		}
		$billing = wholesale_ticket_billing_from_request($_POST);
		if (is_wp_error($billing)) {
			wholesale_payment_json_error($billing->get_error_message());
		}
		if (wholesale_setting_enabled('payment_disabled')) {
			wholesale_payment_json_error('Online card payment is not available right now. Please call us at 866-436-2101.');
		}
		$amount = $ticket['amount'];
		$payload = array('ticket_id' => $ticket_id, 'billing' => $billing);
	} elseif ('order' === $kind) {
		// Payment link for an existing unpaid order (inc/order-pay-link.php). Takes cards even
		// when "Disable card payment" is on: that setting only controls checkout.
		$order_id = wholesale_find_order_by_pay_token(wholesale_order_pay_clean_token($_POST['order_token'] ?? ''));
		if (!$order_id || !wholesale_order_pay_is_open($order_id)) {
			wholesale_payment_json_error('This order is already paid or no longer open. Please contact us if you need help.');
		}
		if (current_user_can('edit_post', $order_id)) {
			wholesale_payment_json_error('You are logged in as staff. Open the link in a private window to pay as the customer.');
		}
		$billing = wholesale_ticket_billing_from_request($_POST);
		if (is_wp_error($billing)) {
			wholesale_payment_json_error($billing->get_error_message());
		}
		$amount = wholesale_order_total($order_id);
		$payload = array('order_post_id' => $order_id, 'billing' => $billing);
	} else {
		wholesale_payment_json_error('Unknown payment type.');
	}

	// The ref is the Converge invoice number. A payment link puts the order number in it
	// (e.g. ORDVPRSMLLZNM7K2Q), so the transaction can be matched to the order in Converge.
	$ref = 'order' === $kind
		? 'ORD' . substr(preg_replace('/[^A-Z0-9]/', '', wholesale_order_number($payload['order_post_id'])), 0, 16) . strtoupper(wp_generate_password(4, false, false))
		: 'SSO' . strtoupper(wp_generate_password(12, false, false));

	if ('direct' === wholesale_payment_method()) {
		wholesale_direct_payment($kind, $payload, $amount, $billing, $ref, $password);
	}

	$session_token = wholesale_converge_session_token($amount, $ref, $billing);
	if (is_wp_error($session_token)) {
		wholesale_payment_json_error($session_token->get_error_message(), 502);
	}

	wholesale_payment_store($ref, array_merge($payload, array(
		'kind' => $kind,
		'amount' => $amount,
		'session' => session_id(),
		'created' => time(),
	)));

	wp_send_json(array(
		'ok' => true,
		'ref' => $ref,
		'token' => $session_token,
		'script' => wholesale_converge_script_url(),
		'amount' => number_format($amount, 2),
	));
}
add_action('wp_ajax_wholesale_payment_start', 'wholesale_ajax_payment_start');

// ------------------------------------------------------------------
// Failed card payments: tell staff, slow down card testing
// ------------------------------------------------------------------

/**
 * Transient key counting declined cards from this visitor's IP address, or ''
 * when the address is unknown (then nothing is counted or blocked, so visitors
 * with no readable address can never share one counter).
 */
function wholesale_payment_decline_key()
{
	$ip = function_exists('wholesale_vi_client_ip')
		? wholesale_vi_client_ip(false)
		: (isset($_SERVER['REMOTE_ADDR']) ? (string) filter_var(wp_unslash($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP) : '');

	return '' !== (string) $ip ? 'wholesale_pay_declines_' . md5((string) $ip) : '';
}

/**
 * Declined or rejected cards allowed from one IP address within an hour of the
 * last one. A real customer rarely needs more than two or three tries; bots
 * testing stolen card numbers need hundreds, and each try costs a gateway fee.
 */
function wholesale_payment_decline_limit()
{
	return 5;
}

function wholesale_payment_declines_blocked()
{
	$key = wholesale_payment_decline_key();

	return '' !== $key && (int) get_transient($key) >= wholesale_payment_decline_limit();
}

/**
 * @return int Declines from this IP address in the last hour, this one included.
 */
function wholesale_payment_count_decline()
{
	$key = wholesale_payment_decline_key();
	if ('' === $key) {
		return 0;
	}
	$count = (int) get_transient($key) + 1;
	set_transient($key, $count, HOUR_IN_SECONDS);

	return $count;
}

/**
 * Emails the team when a card payment fails. A declined checkout creates no
 * order, so without this the customer's name, phone and cart are lost the
 * moment they close the page. When the processor never answered, the card may
 * have been charged, so the email says to check Converge first.
 *
 * One email per visitor per 30 minutes for declines, and at most 20 an hour in
 * all, so a card-testing bot can't flood the inbox.
 */
function wholesale_payment_failed_alert($kind, $payload, $amount, $billing, $ref, $sale, $attempts)
{
	$unknown = !empty($sale['unknown']);
	$visitor_key = 'wholesale_pay_alert_' . md5(session_id() . '|' . $kind . '|' . ($billing['billing_email'] ?? ''));
	if (!$unknown && get_transient($visitor_key)) {
		return;
	}
	$hour_key = 'wholesale_pay_alerts_' . gmdate('YmdH');
	$sent_this_hour = (int) get_transient($hour_key);
	if ($sent_this_hour >= 20) {
		return;
	}
	set_transient($hour_key, $sent_this_hour + 1, HOUR_IN_SECONDS);
	set_transient($visitor_key, 1, 30 * MINUTE_IN_SECONDS);

	$name = trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? ''));
	$money = static function ($value) {
		return '$' . number_format((float) $value, 2);
	};

	$lines = array();
	if ($unknown) {
		$lines[] = 'Converge gave no clear answer after the card was sent, so the card MAY HAVE BEEN CHARGED. No order was saved.';
		$lines[] = 'Look up invoice ' . $ref . ' in Converge before contacting the customer, so they are not charged twice.';
	} elseif ('cart' === $kind) {
		$lines[] = 'A customer tried to pay at checkout, but the card did not go through, so no order was created.';
		$lines[] = 'They were ready to buy: call or email them today. They can try another card, or you can take the payment by phone.';
	}
	$lines[] = '';
	$lines[] = 'What the customer was told: ' . wp_strip_all_tags((string) $sale['message']);
	if ($attempts > 1) {
		$lines[] = sprintf('Declined attempts from this device in the last hour: %d (online payment pauses at %d).', $attempts, wholesale_payment_decline_limit());
	}
	$lines[] = '';
	$lines[] = 'Name: ' . ('' !== $name ? $name : '(not given)');
	if (!empty($billing['billing_company'])) {
		$lines[] = 'Business: ' . $billing['billing_company'];
	}
	$lines[] = 'Email: ' . ($billing['billing_email'] ?? '');
	$lines[] = 'Phone: ' . (!empty($billing['billing_tel']) ? $billing['billing_tel'] : '(not given)');
	$lines[] = 'Billing address: ' . trim(implode(', ', array_filter(array($billing['billing_address'] ?? '', $billing['billing_city'] ?? '', trim(($billing['billing_state'] ?? '') . ' ' . ($billing['billing_zip'] ?? ''))))));
	$lines[] = 'Amount: ' . $money($amount);

	if ('cart' === $kind && !empty($payload['checkout'])) {
		$checkout = $payload['checkout'];
		$lines[] = sprintf('  Subtotal %s + %s %s + tax %s', $money($checkout['sub_total']), $checkout['shipping_label'], $money($checkout['shipping_cost']), $money($checkout['tax']));
		$lines[] = '';
		$lines[] = 'Cart:';
		foreach ((array) $checkout['items'] as $item) {
			$item = (object) $item;
			$lines[] = sprintf('- %d x %s: %s', max(1, (int) ($item->product_quantity ?? 1)), html_entity_decode(wp_strip_all_tags((string) ($item->product_title ?? 'Item')), ENT_QUOTES, 'UTF-8'), $money($item->product_subtotal ?? 0));
		}
		if (!empty($checkout['comment'])) {
			$lines[] = '';
			$lines[] = 'Order note: ' . $checkout['comment'];
		}
	} elseif ('order' === $kind && !empty($payload['order_post_id'])) {
		$lines[] = 'Order: #' . wholesale_order_number($payload['order_post_id']) . ' ' . admin_url('post.php?post=' . (int) $payload['order_post_id'] . '&action=edit');
	} elseif ('ticket' === $kind && !empty($payload['ticket_id'])) {
		$lines[] = 'Payment request (ticket) ID: ' . (int) $payload['ticket_id'];
	}
	$lines[] = '';
	$lines[] = 'Converge invoice: ' . $ref;
	$lines[] = 'Time: ' . wp_date('M j, Y g:i a T');

	$subject = $unknown
		? sprintf('CHECK CONVERGE: payment result unknown for %s (%s)', '' !== $name ? $name : ($billing['billing_email'] ?? ''), $money($amount))
		: sprintf('Checkout payment failed: %s (%s), no order placed', '' !== $name ? $name : ($billing['billing_email'] ?? ''), $money($amount));
	$headers = array();
	if (!empty($billing['billing_email']) && is_email($billing['billing_email'])) {
		$headers[] = 'Reply-To: ' . ('' !== $name ? str_replace(array('"', "\r", "\n"), '', $name) . ' ' : '') . '<' . $billing['billing_email'] . '>';
	}

	wholesale_mail_team($subject, implode("\n", $lines), $headers);
}

/**
 * Direct method: charge the card fields from the form, then create the order. Always exits.
 */
function wholesale_direct_payment($kind, $payload, $amount, $billing, $ref, $password)
{
	$card = wholesale_direct_card_from_request($_POST);
	if (is_wp_error($card)) {
		wholesale_payment_json_error($card->get_error_message(), 400, $card->get_error_data()['field'] ?? '');
	}
	if (wholesale_payment_declines_blocked()) {
		wholesale_payment_json_error('For your security, online payment is paused after several declined cards. Please call us at 866-436-2101 and we will finish your order by phone.', 429);
	}

	// One charge at a time per visitor: a double click can never charge twice.
	// An order payment link locks the order itself, so two browsers can't both pay it.
	$lock = 'order' === $kind
		? 'wholesale_order_pay_lock_' . (int) $payload['order_post_id']
		: 'wholesale_direct_lock_' . md5(session_id() . ('ticket' === $kind ? $payload['ticket_id'] : ''));
	if ((int) get_option($lock) < time() - 120) {
		delete_option($lock);
	}
	if (!add_option($lock, time(), '', 'no')) {
		wholesale_payment_json_error('Your payment is already being processed. Please wait a moment.');
	}
	if ('order' === $kind && !wholesale_order_pay_is_open($payload['order_post_id'])) {
		delete_option($lock);
		wholesale_payment_json_error('This order has already been paid. No charge was made.');
	}

	$sale = wholesale_converge_direct_sale($amount, $card, $billing, $ref);
	unset($card);
	if (!$sale['ok']) {
		delete_option($lock);
		if ('ticket' === $kind) {
			wholesale_ticket_log($payload['ticket_id'], 'payment_failed', $sale['message']);
		} elseif ('order' === $kind) {
			wholesale_order_pay_log($payload['order_post_id'], 'failed', $sale['message']);
		}
		$attempts = !empty($sale['declined']) ? wholesale_payment_count_decline() : 0;
		// A failed checkout leaves no order behind, so staff hear about it here. Ticket and
		// order-link payments already have their own record; they only alert when the
		// charge may have gone through.
		if ('cart' === $kind || !empty($sale['unknown'])) {
			wholesale_payment_failed_alert($kind, $payload, $amount, $billing, $ref, $sale, $attempts);
		}
		wholesale_payment_json_error($sale['message'], 402);
	}

	$pending = array_merge($payload, array('kind' => $kind, 'amount' => $amount));
	$order = wholesale_payment_finalize($pending, $ref, array(
		'_payment_status' => 'paid',
		'_payment_method' => 'direct',
		'_payment_txn_id' => $sale['txn_id'],
		'_payment_ref' => $ref,
		'_payment_card_last4' => $sale['card_last4'],
		'_payment_amount' => $amount,
		'_payment_approval_code' => $sale['approval_code'],
	), $password);
	delete_option($lock);

	wp_send_json(array('ok' => true, 'redirect' => wholesale_thank_you_url($order)));
}
add_action('wp_ajax_nopriv_wholesale_payment_start', 'wholesale_ajax_payment_start');

/**
 * Creates the order for a paid checkout or ticket. Sends a JSON error (and alerts staff)
 * if the order can't be saved, because the customer has already been charged.
 *
 * @return int Order ID.
 */
function wholesale_payment_finalize($pending, $ref, $payment_meta, $password = '')
{
	if ('cart' === $pending['kind']) {
		$cart = wholesale_get_cart();
		$order = wholesale_checkout_create_order($pending['checkout'], $cart, $payment_meta);
		if ($order) {
			wholesale_checkout_attach_account($order, $pending['checkout'], $password);
		}
		$customer = $pending['checkout']['billing'];
	} elseif ('order' === $pending['kind']) {
		$order = wholesale_order_pay_complete($pending['order_post_id'], $pending['billing'], $payment_meta);
		$customer = $pending['billing'];
	} else {
		$order = wholesale_ticket_finalize($pending['ticket_id'], $pending['billing'], true, $payment_meta);
		$customer = $pending['billing'];
	}

	delete_transient('wholesale_payment_' . $ref);

	if (!$order) {
		$txn_id = $payment_meta['_payment_txn_id'] ?? '';
		error_log(sprintf('Wholesale: Converge payment %s ($%s) approved for %s but the order could not be saved.', $txn_id, number_format($pending['amount'], 2), $customer['billing_email']));
		unset($pending['checkout']['items']);
		wp_mail(wholesale_contact_admin_recipients(), 'URGENT: payment approved but order was not saved', sprintf("Converge transaction %s for $%s was approved for %s %s (%s), but the order could not be saved.\n\nDetails: %s", $txn_id, number_format($pending['amount'], 2), $customer['billing_fname'], $customer['billing_lname'], $customer['billing_email'], wp_json_encode($pending)));
		wholesale_payment_json_error('Your payment was received, but we could not finish saving your order. Please do not pay again. Our team has been notified and will contact you shortly.', 500);
	}

	return $order;
}

/**
 * AJAX step 2: after Converge approves, verify the transaction and create the order.
 */
function wholesale_ajax_payment_complete()
{
	if (!check_ajax_referer('wholesale_payment', 'nonce', false)) {
		wholesale_payment_json_error('Your session expired. If you were charged, please call us at 866-436-2101 and we will confirm your order.', 403);
	}

	$ref = isset($_POST['ref']) ? preg_replace('/[^A-Z0-9]/', '', (string) wp_unslash($_POST['ref'])) : '';
	$txn_id = isset($_POST['txn_id']) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string) wp_unslash($_POST['txn_id'])) : '';
	$pending = $ref ? wholesale_payment_store($ref) : false;

	if (!$pending || $pending['session'] !== session_id() || '' === $txn_id) {
		wholesale_payment_json_error('We could not match this payment to your checkout. If you were charged, please call us at 866-436-2101.');
	}

	// Each Converge transaction can only ever create one order.
	if (!add_option('wholesale_txn_' . $txn_id, $ref, '', 'no')) {
		wholesale_payment_json_error('This payment has already been used for an order.');
	}

	$verified = wholesale_converge_verify($txn_id, $pending['amount'], $ref);
	if (is_wp_error($verified) && 'unverified' !== $verified->get_error_code()) {
		error_log(sprintf('Wholesale: rejected Converge transaction %s for checkout %s: %s', $txn_id, $ref, $verified->get_error_message()));
		wp_mail(wholesale_contact_admin_recipients(), 'Payment rejected: transaction did not match the order', sprintf("Converge transaction %s was reported approved for checkout %s ($%s), but the server check failed:\n\n%s\n\nPlease review it in Converge.", $txn_id, $ref, number_format($pending['amount'], 2), $verified->get_error_message()));
		wholesale_payment_json_error('We could not confirm this payment. Please call us at 866-436-2101 before trying again.');
	}

	$card = isset($_POST['card']) ? preg_replace('/[^0-9*Xx]/', '', (string) wp_unslash($_POST['card'])) : '';
	$password = isset($_POST['account_password']) ? (string) wp_unslash($_POST['account_password']) : '';
	$order = wholesale_payment_finalize($pending, $ref, array(
		'_payment_status' => true === $verified ? 'paid' : 'needs_review',
		'_payment_method' => 'lightbox',
		'_payment_txn_id' => $txn_id,
		'_payment_ref' => $ref,
		'_payment_card_last4' => substr($card, -4),
		'_payment_amount' => $pending['amount'],
		'_payment_approval_code' => isset($_POST['approval_code']) ? sanitize_text_field(wp_unslash($_POST['approval_code'])) : '',
	), $password);

	if (true !== $verified) {
		wp_mail(wholesale_contact_admin_recipients(), 'Check payment for order #' . wholesale_order_number($order), sprintf("The order was created, but Converge could not be reached to double-check transaction %s ($%s): %s\n\nPlease confirm the payment in Converge.\n\n%s", $txn_id, number_format($pending['amount'], 2), $verified->get_error_message(), admin_url('post.php?post=' . $order . '&action=edit')));
	}

	wp_send_json(array('ok' => true, 'redirect' => wholesale_thank_you_url($order)));
}
add_action('wp_ajax_wholesale_payment_complete', 'wholesale_ajax_payment_complete');
add_action('wp_ajax_nopriv_wholesale_payment_complete', 'wholesale_ajax_payment_complete');

/**
 * Thank-you page link that can show this order's summary to the visitor who placed it.
 */
function wholesale_thank_you_url($order)
{
	$key = wp_generate_password(20, false, false);
	update_post_meta($order, '_order_view_key', $key);
	if (isset($_SESSION)) {
		$_SESSION['wholesale_last_order'] = (int) $order;
	}
	return add_query_arg(array('order' => get_post_meta($order, 'order_id', true), 'key' => $key), home_url('/thank-you/'));
}

/**
 * Checkout / pay pages: load the checkout script with its settings.
 */
function wholesale_enqueue_payment_script()
{
	if (!is_page(array('checkout', 'pay'))) {
		return;
	}
	$path = '/js/checkout.js';
	wp_enqueue_script('wholesale-checkout', get_template_directory_uri() . $path, array(), (string) filemtime(get_template_directory() . $path), true);
	wp_localize_script('wholesale-checkout', 'wholesalePayment', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('wholesale_payment'),
		// Order payment links (/pay/?o=) always take cards; the setting only covers checkout.
		'cardEnabled' => !wholesale_setting_enabled('payment_disabled') || (is_page('pay') && isset($_GET['o'])),
		'method' => wholesale_payment_method(),
	));
}
add_action('wp_enqueue_scripts', 'wholesale_enqueue_payment_script');

/**
 * The order the visitor just placed, when the thank-you link matches it.
 */
function wholesale_thank_you_order()
{
	$order_number = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : '';
	$key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
	if (!$order_number || !$key) {
		return 0;
	}
	$orders = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'any',
		'meta_key' => 'order_id',
		'meta_value' => $order_number,
		'posts_per_page' => 1,
		'fields' => 'ids',
	));
	$order = $orders ? (int) $orders[0] : 0;
	return $order && hash_equals((string) get_post_meta($order, '_order_view_key', true), $key) ? $order : 0;
}

/**
 * Google Ads purchase conversion with the real order value, sent once per order.
 * Runs after the deferred gtag config in wholesale_deferred_conversion_tracking().
 */
function wholesale_purchase_conversion()
{
	if (!get_query_var('wholesale_thank_you')) {
		return;
	}
	$order = wholesale_thank_you_order();
	if (!$order || get_post_meta($order, '_conversion_tracked', true)) {
		return;
	}
	update_post_meta($order, '_conversion_tracked', current_time('mysql'));

	$cost = wholesale_decode_order_meta_array(get_post_meta($order, 'product_cost', true));
	$value = isset($cost['grand_total']) ? (float) $cost['grand_total'] : 0;
	$transaction_id = (string) get_post_meta($order, 'order_id', true);
	$user_data = wholesale_ads_order_user_data($order);
	$items = wholesale_ads_order_items($order);
	// Google Ads cart data: the products sold, by feed item ID.
	$cart_items = array_map(static function ($item) {
		return array(
			'id' => $item['item_id'] ?? '',
			'quantity' => $item['quantity'],
			'price' => $item['price'],
		);
	}, $items);
	?>
	<script>
		// Sent right away (the tag config above is already queued), so a customer
		// who closes the page early is still counted.
		(function () {
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
			<?php if ($user_data) : ?>
			// Enhanced conversions: the Google tag hashes this before sending it.
			window.gtag('set', 'user_data', <?php echo wp_json_encode($user_data); ?>);
			<?php endif; ?>
			window.gtag('event', 'conversion', {
				send_to: <?php echo wp_json_encode('AW-18454059893/' . WHOLESALE_ADS_PURCHASE_LABEL); ?>,
				value: <?php echo wp_json_encode(round($value, 2)); ?>,
				currency: 'USD',
				transaction_id: <?php echo wp_json_encode($transaction_id); ?><?php if (defined('WHOLESALE_MERCHANT_CENTER_ID')) : ?>,
				aw_merchant_id: <?php echo (int) WHOLESALE_MERCHANT_CENTER_ID; ?>,
				aw_feed_country: 'US',
				aw_feed_language: 'EN',
				items: <?php echo wp_json_encode($cart_items); ?>
				<?php endif; ?>
			});
			// GA4 ecommerce purchase (Site Kit's Google tag picks this up).
			window.gtag('event', 'purchase', {
				transaction_id: <?php echo wp_json_encode($transaction_id); ?>,
				value: <?php echo wp_json_encode(round($value, 2)); ?>,
				tax: <?php echo wp_json_encode(round((float) ($cost['tax'] ?? 0), 2)); ?>,
				shipping: <?php echo wp_json_encode(round((float) ($cost['shipping_cost'] ?? 0), 2)); ?>,
				currency: 'USD',
				items: <?php echo wp_json_encode($items); ?>
			});
		})();
	</script>
	<?php
}
add_action('wp_footer', 'wholesale_purchase_conversion', 21);

// The confirmation page is private to the customer who placed the order.
add_filter('pre_get_document_title', function ($title) {
	return get_query_var('wholesale_thank_you') ? 'Order confirmed | ' . get_bloginfo('name') : $title;
}, 99);
add_filter('wp_robots', function ($robots) {
	if (get_query_var('wholesale_thank_you')) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}
	return $robots;
});
