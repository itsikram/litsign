<?php
/**
 * Google Ads attribution helpers: keep the ad click ID with each order and
 * quote, and send customer data with the purchase conversion.
 *
 * @package litsign
 */

/**
 * Name of the first-party cookie that remembers the last ad click for 90 days.
 */
const WHOLESALE_ADS_CLICK_COOKIE = 'sso_ads_click';

/**
 * The ad click ID on this request's URL, or an empty array.
 *
 * @return array array('type' => gclid|gbraid|wbraid, 'id' => click ID)
 */
function wholesale_ads_click_from_request()
{
	foreach (array('gclid', 'gbraid', 'wbraid') as $type) {
		if (!empty($_GET[$type])) {
			$id = preg_replace('/[^A-Za-z0-9_-]/', '', wp_unslash($_GET[$type]));
			if ('' !== $id && strlen($id) <= 200) {
				return array('type' => $type, 'id' => $id);
			}
		}
	}

	return array();
}

/**
 * The remembered ad click: this request's, else the cookie's.
 *
 * @return array Same shape as wholesale_ads_click_from_request().
 */
function wholesale_ads_click()
{
	$click = wholesale_ads_click_from_request();
	if ($click) {
		return $click;
	}

	if (empty($_COOKIE[WHOLESALE_ADS_CLICK_COOKIE])) {
		return array();
	}

	$parts = explode(':', sanitize_text_field(wp_unslash($_COOKIE[WHOLESALE_ADS_CLICK_COOKIE])), 2);
	if (2 !== count($parts) || !in_array($parts[0], array('gclid', 'gbraid', 'wbraid'), true)) {
		return array();
	}

	$id = preg_replace('/[^A-Za-z0-9_-]/', '', $parts[1]);

	return '' !== $id ? array('type' => $parts[0], 'id' => $id) : array();
}

/**
 * Remember an ad click for 90 days, so an order placed later (after browsing
 * the builder or coming back another day) keeps its click ID.
 */
function wholesale_ads_remember_click()
{
	if (is_admin() || wp_doing_ajax() || headers_sent()) {
		return;
	}

	$click = wholesale_ads_click_from_request();
	if (!$click) {
		return;
	}

	setcookie(WHOLESALE_ADS_CLICK_COOKIE, $click['type'] . ':' . $click['id'], array(
		'expires' => time() + 90 * DAY_IN_SECONDS,
		'path' => COOKIEPATH ? COOKIEPATH : '/',
		'domain' => COOKIE_DOMAIN,
		'secure' => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	));
	$_COOKIE[WHOLESALE_ADS_CLICK_COOKIE] = $click['type'] . ':' . $click['id'];
}
add_action('init', 'wholesale_ads_remember_click');

/**
 * Save the ad click on a new order, for offline conversion uploads and for
 * seeing which orders came from ads.
 */
function wholesale_ads_attach_click_to_order($order_id)
{
	$click = wholesale_ads_click();
	if ($click && !get_post_meta($order_id, '_ads_click_id', true)) {
		update_post_meta($order_id, '_ads_click_type', $click['type']);
		update_post_meta($order_id, '_ads_click_id', $click['id']);
	}
}
add_action('wholesale_order_created', 'wholesale_ads_attach_click_to_order');

/**
 * Quote forms outside the ads landing page don't read the click ID from the
 * URL; fill it in from the remembered click when a submission is created.
 */
function wholesale_ads_attach_click_to_quote($post_id, $post, $update)
{
	if ($update || wp_is_post_revision($post_id) || is_admin()) {
		return;
	}

	$click = wholesale_ads_click();
	if ($click && !get_post_meta($post_id, '_contact_gclid', true)) {
		update_post_meta($post_id, '_contact_gclid', $click['id']);
		update_post_meta($post_id, '_contact_click_type', $click['type']);
	}
}
add_action('save_post_contact_submission', 'wholesale_ads_attach_click_to_quote', 10, 3);

/**
 * Whether this page view came straight from an ad click. The Google tag then
 * loads right away instead of after the page finishes loading, so the click
 * is recorded even if the visitor moves on quickly.
 */
function wholesale_ads_is_ad_landing()
{
	return (bool) wholesale_ads_click_from_request();
}

/**
 * Customer data for enhanced conversions, from an order's billing details.
 *
 * @return array Google tag user_data (unhashed; the tag hashes it).
 */
function wholesale_ads_order_user_data($order_id)
{
	$billing = wholesale_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
	$digits = preg_replace('/\D/', '', (string) ($billing['billing_tel'] ?? ''));
	$phone = 10 === strlen($digits) ? '+1' . $digits : (11 === strlen($digits) && '1' === $digits[0] ? '+' . $digits : '');

	$address = array_filter(array(
		'first_name' => (string) ($billing['billing_fname'] ?? ''),
		'last_name' => (string) ($billing['billing_lname'] ?? ''),
		'street' => (string) ($billing['billing_address'] ?? ''),
		'city' => (string) ($billing['billing_city'] ?? ''),
		'region' => (string) ($billing['billing_state'] ?? ''),
		'postal_code' => (string) ($billing['billing_zip'] ?? ''),
	));
	if ($address) {
		$address['country'] = 'US';
	}

	return array_filter(array(
		'email' => is_email($billing['billing_email'] ?? '') ? (string) $billing['billing_email'] : '',
		'phone_number' => $phone,
		'address' => $address,
	));
}

/**
 * Items of an order in the GA4 ecommerce format.
 *
 * @return array
 */
function wholesale_ads_order_items($order_id)
{
	$items = array();

	foreach (wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_json', true)) as $item) {
		$quantity = max(1, (int) ($item['product_quantity'] ?? 1));
		$subtotal = (float) ($item['product_subtotal'] ?? 0);
		$items[] = array_filter(array(
			'item_id' => isset($item['product_id']) ? (string) $item['product_id'] : '',
			'item_name' => html_entity_decode(wp_strip_all_tags((string) ($item['product_title'] ?? '')), ENT_QUOTES, 'UTF-8'),
			'quantity' => $quantity,
			'price' => round($subtotal / $quantity, 2),
		), static function ($value) {
			return '' !== $value;
		});
	}

	return $items;
}

/**
 * GA4 begin_checkout when the checkout page opens, so the funnel from cart to
 * purchase shows where buyers drop off.
 */
function wholesale_ads_begin_checkout_event()
{
	if (!is_page('checkout') || !function_exists('wholesale_get_cart')) {
		return;
	}

	$cart = wholesale_get_cart();
	$items = array();
	foreach ((array) $cart->get_items() as $item) {
		$item = (array) $item;
		$quantity = max(1, (int) ($item['product_quantity'] ?? 1));
		$items[] = array(
			'item_id' => (string) ($item['product_id'] ?? ''),
			'item_name' => html_entity_decode(wp_strip_all_tags((string) ($item['product_title'] ?? '')), ENT_QUOTES, 'UTF-8'),
			'quantity' => $quantity,
			'price' => round((float) ($item['product_subtotal'] ?? 0) / $quantity, 2),
		);
	}
	if (!$items) {
		return;
	}
	?>
	<script>
		window.addEventListener('load', function () {
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
			window.gtag('event', 'begin_checkout', {
				currency: 'USD',
				value: <?php echo wp_json_encode(round((float) $cart->sub_total, 2)); ?>,
				items: <?php echo wp_json_encode($items); ?>
			});
		});
	</script>
	<?php
}
add_action('wp_footer', 'wholesale_ads_begin_checkout_event', 21);

/**
 * Secondary Google Ads conversions sent from the browser, keyed by their theme
 * setting (Settings > Storefront Sign > Google Ads). An empty label turns one off.
 *
 * @return array setting key => array('event' => script key, 'label' => admin label, 'help' => admin help)
 */
function wholesale_ads_click_conversions()
{
	return array(
		'google_ads_phone_tap_label' => array('event' => 'phone_tap', 'label' => 'Phone tap conversion label', 'help' => 'Sent when someone taps or clicks a phone number link.'),
		'google_ads_sms_tap_label' => array('event' => 'sms_tap', 'label' => 'Text tap conversion label', 'help' => 'Sent when someone taps a "text us" link.'),
		'google_ads_email_tap_label' => array('event' => 'email_tap', 'label' => 'Email tap conversion label', 'help' => 'Sent when someone clicks an email address link.'),
		'google_ads_builder_start_label' => array('event' => 'builder_start', 'label' => 'Builder start conversion label', 'help' => 'Sent once per page view, the first time someone types sign text in the channel letter builder or a product price calculator.'),
		'google_ads_add_to_cart_label' => array('event' => 'add_to_cart', 'label' => 'Add to cart conversion label', 'help' => 'Sent with the item value when a product or builder design is added to the cart.'),
	);
}

/**
 * The GA4 measurement ID from the theme settings, or '' when unset or malformed.
 */
function wholesale_ga4_measurement_id()
{
	$id = strtoupper(trim((string) wholesale_get_setting('ga4_measurement_id')));

	return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) ? $id : '';
}

/**
 * Phone, text and email taps, builder starts and cart adds. Events queue in
 * dataLayer, so ones that happen before the deferred Google tag loads
 * (wholesale_deferred_conversion_tracking) are sent once it does.
 */
function wholesale_ads_click_tracking_script()
{
	$labels = array();
	foreach (wholesale_ads_click_conversions() as $key => $conversion) {
		$label = trim((string) wholesale_get_setting($key));
		if ('' !== $label) {
			$labels[$conversion['event']] = $label;
		}
	}
	$ga4 = '' !== wholesale_ga4_measurement_id();
	if (!$labels && !$ga4) {
		return;
	}
	?>
	<script>
		(function () {
			var labels = <?php echo wp_json_encode((object) $labels); ?>;
			var ga4 = <?php echo $ga4 ? 'true' : 'false'; ?>;
			window.dataLayer = window.dataLayer || [];
			function gtag() { window.dataLayer.push(arguments); }
			function convert(key, params) {
				if (!labels[key]) return;
				params = params || {};
				params.send_to = 'AW-18454059893/' + labels[key];
				gtag('event', 'conversion', params);
			}

			document.addEventListener('click', function (e) {
				var link = e.target.closest ? e.target.closest('a[href]') : null;
				if (!link) return;
				var href = link.getAttribute('href').trim().toLowerCase();
				if (0 === href.indexOf('tel:')) convert('phone_tap');
				else if (0 === href.indexOf('sms:')) convert('sms_tap');
				else if (0 === href.indexOf('mailto:')) convert('email_tap');
			}, true);

			var started = false;
			document.addEventListener('input', function (e) {
				var field = e.target;
				if (started || !field.matches || !field.matches('#textInput, input[name="letters"]') || !field.value) return;
				started = true;
				convert('builder_start');
			}, true);

			// js/mini-cart.js dispatches this after the cart confirms the add.
			document.addEventListener('wholesale:cart-added', function (e) {
				var item = e.detail || {};
				var value = Number(item.value) || 0;
				var quantity = Number(item.quantity) || 1;
				convert('add_to_cart', { value: value, currency: 'USD' });
				if (ga4) {
					gtag('event', 'add_to_cart', {
						currency: 'USD',
						value: value,
						items: [{ item_id: String(item.product_id || ''), item_name: item.title || '', quantity: quantity, price: Math.round(value / quantity * 100) / 100 }]
					});
				}
			});
		})();
	</script>
	<?php
}
add_action('wp_footer', 'wholesale_ads_click_tracking_script', 21);
