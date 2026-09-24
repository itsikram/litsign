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
	$exp_year = 2000 + (int) $card['year'];
	$expired = $exp_year < (int) gmdate('Y') || ($exp_year === (int) gmdate('Y') && (int) $card['month'] < (int) gmdate('n'));

	if (strlen($card['number']) < 12 || strlen($card['number']) > 19) {
		return new WP_Error('card', 'Please check your card number.');
	}
	if (strlen($card['cvv']) < 3 || strlen($card['cvv']) > 4) {
		return new WP_Error('card', 'Please check your card security code (CVV).');
	}
	if (2 !== strlen($card['month']) || (int) $card['month'] < 1 || (int) $card['month'] > 12 || 2 !== strlen($card['year']) || $expired) {
		return new WP_Error('card', 'Please check your card expiration date.');
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
	$result = array('ok' => false, 'message' => 'Payment failed. No charge was made.', 'txn_id' => '', 'approval_code' => '', 'card_last4' => substr($card['number'], -4));
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
		$result['message'] = 'Payment could not be processed: ' . sanitize_text_field($fields['errorMessage'] ?? $fields['errorName'] ?? 'unknown error') . '. No charge was made.';
	} elseif (!empty($fields['ssl_result_message'])) {
		$result['message'] = 'Your card was declined (' . sanitize_text_field($fields['ssl_result_message']) . '). No charge was made. Please try another card.';
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
 * Looks a transaction up with Converge. Returns the transaction fields, or WP_Error.
 */
function wholesale_converge_query_transaction($txn_id)
{
	$credentials = wholesale_converge_credentials();
	if (!$credentials) {
		return new WP_Error('not_configured', 'Payment gateway is not configured.');
	}

	$xml = '<txn>';
	foreach (array_merge($credentials, array('ssl_transaction_type' => 'txnquery', 'ssl_txn_id' => $txn_id)) as $key => $value) {
		$xml .= '<' . $key . '>' . esc_html($value) . '</' . $key . '>';
	}
	$xml .= '</txn>';

	$path = wholesale_setting_enabled('payment_test_mode') ? '/VirtualMerchantDemo/processxml.do' : '/VirtualMerchant/processxml.do';
	$response = wp_remote_post(wholesale_converge_host() . $path, array(
		'timeout' => 30,
		'body' => array('xmldata' => $xml),
	));
	if (is_wp_error($response)) {
		return $response;
	}

	$previous = libxml_use_internal_errors(true);
	$doc = simplexml_load_string(wp_remote_retrieve_body($response));
	libxml_use_internal_errors($previous);
	if (!$doc) {
		return new WP_Error('bad_response', 'Converge returned an unreadable response.');
	}
	if (isset($doc->errorCode)) {
		return new WP_Error('converge_error', trim((string) $doc->errorCode . ' ' . (string) $doc->errorMessage));
	}

	// Responses are either <txnlist><txn>…</txn></txnlist> or a single <txn>.
	$candidates = isset($doc->txn) ? $doc->txn : array($doc);
	foreach ($candidates as $txn) {
		$fields = array();
		foreach ($txn->children() as $child) {
			$fields[$child->getName()] = trim((string) $child);
		}
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
		return in_array($txn->get_error_code(), array('not_found'), true)
			? $txn
			: new WP_Error('unverified', $txn->get_error_message());
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
			return new WP_Error('billing', sprintf('Please enter your billing %s.', $label));
		}
	}
	if (!is_email($billing['billing_email'])) {
		return new WP_Error('billing', 'Please enter a valid email address.');
	}

	if ('on' === $field('same_shipping_address')) {
		$shipping = $billing;
	} else {
		$shipping = array();
		foreach (array('fname', 'lname', 'company', 'address', 'address_2', 'city', 'state', 'zip', 'country', 'tel') as $key) {
			$shipping['shipping_' . $key] = $field('shipping_' . $key);
		}
		foreach (array('shipping_fname' => 'first name', 'shipping_lname' => 'last name', 'shipping_address' => 'address', 'shipping_city' => 'city', 'shipping_state' => 'state', 'shipping_zip' => 'ZIP code') as $key => $label) {
			if ('' === $shipping[$key]) {
				return new WP_Error('shipping', sprintf('Please enter the shipping %s.', $label));
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

	$shipping_options = wholesale_cart_shipping_options($cart);
	$requested_shipping = isset($post['shipping_method']) ? (float) wp_unslash($post['shipping_method']) : -1;
	if (!in_array($requested_shipping, $shipping_options, true)) {
		return new WP_Error('shipping_method', 'Please choose a shipping method.');
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
		'shipping_label' => wholesale_shipping_label(array_search($requested_shipping, $shipping_options, true)),
		'tax' => $tax,
		'grand_total' => $grand_total,
		'comment' => isset($post['comment']) ? sanitize_textarea_field(wp_unslash($post['comment'])) : '',
		'estimate_delivery_time' => wholesale_estimated_ship_date($cart, array_search($requested_shipping, $shipping_options, true)),
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

function wholesale_shipping_label($index)
{
	$labels = array('Standard (3–6 business days)', '3-Day', '2-Day', 'Overnight');
	return isset($labels[$index]) ? $labels[$index] : 'Shipping option ' . ((int) $index + 1);
}

/**
 * Longest production time in the cart plus transit for the chosen shipping speed.
 */
function wholesale_estimated_ship_date($cart, $shipping_index = 0)
{
	$turnaround = 1;
	foreach ($cart->get_items() as $item) {
		$turnaround = max($turnaround, (int) get_post_meta(absint($item->product_id), '_product_turnaround', true));
	}
	$transit = array(5, 3, 2, 0);
	$days = $turnaround + ($transit[(int) $shipping_index] ?? 5);
	$date = new DateTime('now', wp_timezone());
	$date->modify('+' . $days . ' days');
	return $date->format('D M. j');
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
		'tax' => $checkout['tax'],
	));

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

function wholesale_payment_json_error($message, $status = 400)
{
	wp_send_json(array('ok' => false, 'error' => $message), $status);
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
			wholesale_payment_json_error($checkout->get_error_message());
		}
		if ($account_error = wholesale_checkout_account_error($checkout, $password)) {
			wholesale_payment_json_error($account_error);
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
	} else {
		wholesale_payment_json_error('Unknown payment type.');
	}

	$ref = 'SSO' . strtoupper(wp_generate_password(12, false, false));

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

/**
 * Direct method: charge the card fields from the form, then create the order. Always exits.
 */
function wholesale_direct_payment($kind, $payload, $amount, $billing, $ref, $password)
{
	$card = wholesale_direct_card_from_request($_POST);
	if (is_wp_error($card)) {
		wholesale_payment_json_error($card->get_error_message());
	}

	// One charge at a time per visitor: a double click can never charge twice.
	$lock = 'wholesale_direct_lock_' . md5(session_id() . ('ticket' === $kind ? $payload['ticket_id'] : ''));
	if ((int) get_option($lock) < time() - 120) {
		delete_option($lock);
	}
	if (!add_option($lock, time(), '', 'no')) {
		wholesale_payment_json_error('Your payment is already being processed. Please wait a moment.');
	}

	$sale = wholesale_converge_direct_sale($amount, $card, $billing, $ref);
	unset($card);
	if (!$sale['ok']) {
		delete_option($lock);
		if ('ticket' === $kind) {
			wholesale_ticket_log($payload['ticket_id'], 'payment_failed', $sale['message']);
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
		'cardEnabled' => !wholesale_setting_enabled('payment_disabled'),
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
	?>
	<script>
		window.addEventListener('load', function () {
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
			window.gtag('event', 'conversion', {
				send_to: 'AW-18454059893/UkFKCJGGp_kcEPW2yt9E',
				value: <?php echo wp_json_encode(round($value, 2)); ?>,
				currency: 'USD',
				transaction_id: <?php echo wp_json_encode((string) get_post_meta($order, 'order_id', true)); ?>
			});
		});
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
