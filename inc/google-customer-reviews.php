<?php
/**
 * Google Customer Reviews survey opt-in on the order confirmation page.
 *
 * Merchant Center 5877365740 joined the program on 2026-11-12. After an order, Google
 * shows its opt-in dialog on /thank-you/. Customers who agree get one email survey after
 * the estimated delivery date. Their ratings build the store rating that Merchant
 * Center's Store quality page and Shopping ads use.
 *
 * Shown only when the thank-you link matches a real order (wholesale_thank_you_order())
 * that has a customer email. Never shown to logged-in staff, so test views add no
 * surveys. GTINs are left out: the products are made to order and have none.
 *
 * Turn it off with the option wholesale_gcr_optin = '0'.
 *
 * @package litsign
 */

/**
 * Estimated delivery as Y-m-d, from the order's saved ship date ("Tue Nov. 10", no
 * year) plus the transit business days of the shipping speed the customer chose.
 * Standard and free shipping use the slow end of 3-6 days, the same as
 * wholesale_estimated_delivery_date().
 */
function wholesale_gcr_delivery_date($order)
{
	$zone = new DateTimeZone('America/Los_Angeles');
	$placed = new DateTime(get_post_field('post_date', $order) ?: 'now', $zone);
	$placed->setTime(0, 0);

	$ship = null;
	$saved = (string) get_post_meta($order, 'estimate_delivery_time', true);
	if (preg_match('/\b([A-Z][a-z]{2})\.?\s+(\d{1,2})\b/', $saved, $match)) {
		$ship = DateTime::createFromFormat('!M j Y', $match[1] . ' ' . $match[2] . ' ' . $placed->format('Y'), $zone);
		// A December order shipping in January.
		if ($ship && $ship < (clone $placed)->modify('-7 days')) {
			$ship->modify('+1 year');
		}
	}
	if (!$ship) {
		$ship = function_exists('wholesale_add_business_days') ? wholesale_add_business_days($placed, 5) : (clone $placed)->modify('+7 days');
	}

	$cost = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($order, 'product_cost', true)) : array();
	$method = strtolower((string) ($cost['shipping_method'] ?? ''));
	$transit = 6;
	if (false !== strpos($method, 'overnight')) {
		$transit = 1;
	} elseif (false !== strpos($method, '2-day')) {
		$transit = 2;
	} elseif (false !== strpos($method, '3-day')) {
		$transit = 3;
	}

	$delivery = function_exists('wholesale_add_business_days') ? wholesale_add_business_days($ship, $transit) : (clone $ship)->modify('+' . ($transit + 2) . ' days');
	return $delivery->format('Y-m-d');
}

/**
 * The render() options for an order, or null when the opt-in must not show.
 */
function wholesale_gcr_optin_data($order)
{
	if (!$order || !defined('WHOLESALE_MERCHANT_CENTER_ID')) {
		return null;
	}
	$billing = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($order, 'billing_address', true)) : array();
	$email = sanitize_email((string) ($billing['billing_email'] ?? ''));
	if (!is_email($email)) {
		return null;
	}

	return array(
		'merchant_id' => (int) WHOLESALE_MERCHANT_CENTER_ID,
		'order_id' => (string) (function_exists('wholesale_order_number') ? wholesale_order_number($order) : get_post_meta($order, 'order_id', true)),
		'email' => $email,
		'delivery_country' => 'US', // Ships to the 50 states only.
		'estimated_delivery_date' => wholesale_gcr_delivery_date($order),
		'opt_in_style' => 'CENTER_DIALOG',
	);
}

function wholesale_gcr_optin_script()
{
	if (!get_query_var('wholesale_thank_you') || '0' === get_option('wholesale_gcr_optin', '1')) {
		return;
	}
	if (is_user_logged_in() && current_user_can('edit_posts')) {
		return;
	}
	$data = wholesale_gcr_optin_data(function_exists('wholesale_thank_you_order') ? wholesale_thank_you_order() : 0);
	if (!$data) {
		return;
	}
	?>
<script>
window.renderOptIn = function () {
	window.gapi.load('surveyoptin', function () {
		window.gapi.surveyoptin.render(<?php echo wp_json_encode($data); ?>);
	});
};
</script>
<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
	<?php
}
add_action('wp_footer', 'wholesale_gcr_optin_script', 50);
