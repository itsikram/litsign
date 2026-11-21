<?php
/**
 * Checkout: the form check against the cart (retails_checkout_build), shipping options and
 * dates, order creation, checkout accounts, the thank-you page and the purchase conversion tag.
 * Also used by the SFS Wallet Payments plugin (Apple Pay, Google Pay, PayPal and, since
 * 2026-11-12, the Converge card payments that used to be in this file).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/*
 * Card payments through Elavon Converge (Lightbox or card fields, the two AJAX steps,
 * decline alerts and the card-testing pause) live in the SFS Wallet Payments plugin:
 * wp-content/plugins/sfs-wallet-payments/converge/gateway.php. The plugin loads them on
 * after_setup_theme (priority 5), and only when the theme doesn't define them itself.
 * Without the plugin, inc/payment-gateway-missing.php keeps checkout, the pay pages and the
 * order screen working, answers card payments with "please call us" and tells staff.
 */
add_action('after_setup_theme', static function () {
	if (!function_exists('retails_converge_credentials')) {
		require_once __DIR__ . '/payment-gateway-missing.php';
	}
}, 20);

/**
 * The session cart. utils/Cart.php creates it on include; AJAX requests don't load a
 * template, so load it here.
 */
function retails_get_cart()
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

/**
 * Validates the checkout form against the server-side cart.
 *
 * @return array|WP_Error Everything needed to create the order.
 */
function retails_checkout_build($post, $cart)
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

	if ('on' === $field('same_shipping_address')) {
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

	$shipping_options = retails_cart_shipping_options($cart);
	$shipping_choice = isset($post['shipping_method']) ? sanitize_text_field(wp_unslash($post['shipping_method'])) : '';
	if ('free' === $shipping_choice) {
		// Free shipping travels like Standard: same transit, delivery and ship date.
		if (!retails_cart_qualifies_free_shipping($cart)) {
			return new WP_Error('shipping_method', 'Your order no longer qualifies for free shipping. Please choose a shipping method.');
		}
		$requested_shipping = 0.0;
		$shipping_index = 0;
		$shipping_label = retails_free_shipping_label($cart);
	} else {
		$requested_shipping = is_numeric($shipping_choice) ? (float) $shipping_choice : -1;
		$shipping_index = array_search($requested_shipping, $shipping_options, true);
		// Standard isn't offered once Free Shipping applies.
		if (false === $shipping_index || (0 === $shipping_index && retails_cart_qualifies_free_shipping($cart))) {
			return new WP_Error('shipping_method', 'Please choose a shipping method.');
		}
		$shipping_label = retails_shipping_label($shipping_index);
	}

	$sub_total = round((float) $cart->sub_total, 2);
	$tax = round($sub_total * (float) retails_get_setting('tax_rate') / 100, 2);
	$grand_total = round($sub_total + $requested_shipping + $tax, 2);

	$shipping['shipping_cost'] = $requested_shipping;

	return array(
		'billing' => $billing,
		'shipping' => $shipping,
		'items' => $items,
		'sub_total' => $sub_total,
		'shipping_cost' => $requested_shipping,
		'shipping_label' => $shipping_label,
		'tax' => $tax,
		'grand_total' => $grand_total,
		'comment' => isset($post['comment']) ? sanitize_textarea_field(wp_unslash($post['comment'])) : '',
		'estimate_delivery_time' => retails_estimated_ship_date($cart, $shipping_index),
		'create_account' => !is_user_logged_in() && '1' === $field('create_account'),
	);
}

function retails_cart_has_channel_letters($cart)
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
function retails_cart_shipping_options($cart)
{
	$setting = retails_cart_has_channel_letters($cart) ? 'channel_shipping_options' : 'standard_shipping_options';
	return array_values(array_map('floatval', array_filter(explode(',', (string) retails_get_setting($setting)), 'is_numeric')));
}

/**
 * Subtotal at which the Free Shipping option appears for this cart, or 0 when
 * the offer is off. Carts with channel letters have their own, higher minimum.
 */
function retails_free_shipping_threshold($cart)
{
	$setting = retails_cart_has_channel_letters($cart) ? 'free_shipping_channel_min' : 'free_shipping_min';
	return max(0.0, (float) retails_get_setting($setting));
}

/**
 * Whether checkout offers Free Shipping, listed above the paid options and
 * delivered on Standard's schedule.
 */
function retails_cart_qualifies_free_shipping($cart)
{
	$threshold = retails_free_shipping_threshold($cart);
	return ($threshold > 0 && round((float) $cart->sub_total, 2) >= $threshold) || '' !== retails_applied_ship_code();
}

/**
 * The free shipping promo code (Settings > Storefront Sign > Sales) when today is within
 * its Pacific dates, else ''. An empty start means it already runs; an empty end, that it
 * has no last day.
 */
function retails_ship_code_window()
{
	$code = retails_sanitize_ship_code(retails_get_setting('ship_code'));
	if ('' === $code) {
		return '';
	}
	$zone = new DateTimeZone('America/Los_Angeles');
	$start = DateTime::createFromFormat('!Y-m-d', (string) retails_get_setting('ship_code_start'), $zone);
	$end = DateTime::createFromFormat('!Y-m-d', (string) retails_get_setting('ship_code_end'), $zone);
	if ($start && time() < $start->getTimestamp()) {
		return '';
	}
	if ($end && time() > $end->modify('+1 day')->getTimestamp() - 1) {
		return '';
	}
	return $code;
}

/**
 * The promo code this visitor applied at checkout (cookie sfs_promo), while it runs, else ''.
 * With it, every cart gets Free Shipping (standard speed): checkout, the cart page, the mini
 * cart and the wallet window all ask retails_cart_qualifies_free_shipping().
 */
function retails_applied_ship_code()
{
	$code = retails_ship_code_window();
	$typed = isset($_COOKIE['sfs_promo']) ? retails_sanitize_ship_code(wp_unslash($_COOKIE['sfs_promo'])) : '';
	return '' !== $code && '' !== $typed && hash_equals($code, $typed) ? $code : '';
}

/**
 * The applied code when it is what makes this cart ship free (below the minimum), else ''.
 */
function retails_cart_ship_code($cart)
{
	$threshold = retails_free_shipping_threshold($cart);
	if ($threshold > 0 && round((float) $cart->sub_total, 2) >= $threshold) {
		return '';
	}
	return retails_applied_ship_code();
}

/**
 * Merchant Center promotion id for the code ("freeship_blackfriday") from the day it is set
 * until its last day (the promotion is reviewed before it starts), else ''. The feed puts it
 * on items that don't already ship free (inc/merchant-feed.php).
 */
function retails_ship_code_promotion_id()
{
	$code = retails_sanitize_ship_code(retails_get_setting('ship_code'));
	if ('' === $code) {
		return '';
	}
	$end = DateTime::createFromFormat('!Y-m-d', (string) retails_get_setting('ship_code_end'), new DateTimeZone('America/Los_Angeles'));
	if ($end && time() > $end->modify('+1 day')->getTimestamp() - 1) {
		return '';
	}
	return 'freeship_' . strtolower($code);
}

/**
 * ?promo_code=BLACKFRIDAY on the checkout or cart page (the promo code box) stores a valid
 * code in the sfs_promo cookie; an empty one removes it. Redirects back without the code,
 * with promo=applied|invalid|removed for the message.
 */
function retails_handle_promo_code()
{
	if (!isset($_GET['promo_code']) || is_admin()) {
		return;
	}
	$typed = retails_sanitize_ship_code(wp_unslash($_GET['promo_code']));
	$code = retails_ship_code_window();
	$path = COOKIEPATH ? COOKIEPATH : '/';
	if ('' === $typed) {
		setcookie('sfs_promo', '', time() - 3600, $path, COOKIE_DOMAIN, is_ssl(), true);
		$result = 'removed';
	} elseif ('' !== $code && hash_equals($code, $typed)) {
		setcookie('sfs_promo', $code, time() + 30 * DAY_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true);
		$result = 'applied';
	} else {
		$result = 'invalid';
	}
	wp_safe_redirect(add_query_arg('promo', $result, remove_query_arg(array('promo_code', 'promo'))));
	exit;
}
add_action('template_redirect', 'retails_handle_promo_code', 1);

/**
 * A free shipping minimum for display ("$100", "$1,000"; cents only when
 * set), or '' when that offer is off.
 */
function retails_free_shipping_amount($channel_letters = false)
{
	$amount = max(0.0, (float) retails_get_setting($channel_letters ? 'free_shipping_channel_min' : 'free_shipping_min'));
	return $amount > 0 ? '$' . number_format($amount, floor($amount) == $amount ? 0 : 2) : '';
}

/**
 * The cheapest paid shipping rate for display ("$18", "$50"), or '' when none
 * is set, so pages can say what shipping costs below the free shipping minimum.
 */
function retails_shipping_from_amount($channel_letters = false)
{
	$rates = array_filter(array_map('floatval', explode(',', (string) retails_get_setting($channel_letters ? 'channel_shipping_options' : 'standard_shipping_options'))));
	if (!$rates) {
		return '';
	}
	$amount = min($rates);
	return '$' . number_format($amount, floor($amount) == $amount ? 0 : 2);
}

function retails_free_shipping_label($cart = null)
{
	// "Free Shipping, code BLACKFRIDAY": the order and its emails show which code made it free.
	$code = $cart ? retails_cart_ship_code($cart) : '';
	return '' !== $code ? 'Free Shipping, code ' . $code . ' (3–6 business days)' : 'Free Shipping (3–6 business days)';
}

function retails_shipping_label($index)
{
	$labels = array('Standard (3–6 business days)', '3-Day', '2-Day', 'Overnight');
	return isset($labels[$index]) ? $labels[$index] : 'Shipping option ' . ((int) $index + 1);
}

/**
 * $date plus $days business days (Saturdays and Sundays skipped).
 */
function retails_add_business_days(DateTime $date, $days)
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
function retails_cart_production_days($cart)
{
	$turnaround = 1;
	foreach ($cart->get_items() as $item) {
		$turnaround = max($turnaround, (int) get_post_meta(absint($item->product_id), '_product_turnaround', true));
	}
	return retails_cart_has_channel_letters($cart) ? max($turnaround, 4) : $turnaround;
}

/**
 * When the order should ship: its production business days counted from the
 * order day in Pacific time, the shop's time zone (the site's WordPress time
 * zone may be UTC). Orders placed after 4pm or on a weekend count from the
 * next business day. The old version added calendar days plus transit, so
 * "Ships by" dates landed on weekends and included delivery time.
 */
function retails_estimated_ship_datetime($cart, ?DateTime $now = null)
{
	$now = $now ? clone $now : new DateTime('now', new DateTimeZone('America/Los_Angeles'));
	$order_day = clone $now;
	$order_day->setTime(0, 0);
	if ($now->format('N') >= 6 || (int) $now->format('G') >= 16) {
		$order_day = retails_add_business_days($order_day, 1);
	}
	return retails_add_business_days($order_day, retails_cart_production_days($cart));
}

/**
 * Ship date as "Tue Oct. 13", saved with the order and shown as "Estimated to
 * ship by" on the thank-you page, order emails and order tracking. The ship
 * date doesn't depend on the shipping speed; the argument is kept for callers.
 */
function retails_estimated_ship_date($cart, $shipping_index = 0)
{
	return retails_estimated_ship_datetime($cart)->format('D M. j');
}

/**
 * Estimated delivery for a shipping speed (standard, 3-day, 2-day, overnight):
 * the ship date plus that speed's transit business days, using the slow end
 * of standard shipping's 3-6 days.
 */
function retails_estimated_delivery_date($cart, $shipping_index = 0)
{
	$transit = array(6, 3, 2, 1);
	return retails_add_business_days(retails_estimated_ship_datetime($cart), $transit[(int) $shipping_index] ?? 6)->format('D M. j');
}

/**
 * Creates the order for a validated checkout and empties the cart.
 */
function retails_checkout_create_order($checkout, $cart, $extra_meta = array())
{
	$order_cost = wp_json_encode(array(
		'grand_total' => $checkout['grand_total'],
		'sub_total' => $checkout['sub_total'],
		'shipping_cost' => $checkout['shipping_cost'],
		'shipping_method' => $checkout['shipping_label'],
		'tax' => $checkout['tax'],
	));

	$order = retails_insert_order(
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
		retails_send_new_order_admin_email($order);
		do_action('retails_order_created', $order);
	}

	return $order;
}

function retails_create_checkout_account($email, $password, $first_name, $last_name, $telephone)
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
function retails_checkout_account_error($checkout, $password)
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

function retails_checkout_attach_account($order, $checkout, $password)
{
	if (!$order || !$checkout['create_account']) {
		return;
	}
	$billing = $checkout['billing'];
	$user_id = retails_create_checkout_account($billing['billing_email'], $password, $billing['billing_fname'], $billing['billing_lname'], $billing['billing_tel']);
	if (!is_wp_error($user_id)) {
		update_post_meta($order, 'user_id', $user_id);
	}
}

/**
 * Thank-you page link that can show this order's summary to the visitor who placed it.
 */
function retails_thank_you_url($order)
{
	$key = wp_generate_password(20, false, false);
	update_post_meta($order, '_order_view_key', $key);
	if (isset($_SESSION)) {
		$_SESSION['retails_last_order'] = (int) $order;
	}
	return add_query_arg(array('order' => get_post_meta($order, 'order_id', true), 'key' => $key), home_url('/thank-you/'));
}

/**
 * Checkout / pay pages: load the checkout script with its settings.
 */
function retails_enqueue_payment_script()
{
	if (!is_page(array('checkout', 'pay'))) {
		return;
	}
	$path = '/js/checkout.js';
	wp_enqueue_script('retails-checkout', get_template_directory_uri() . $path, array(), (string) filemtime(get_template_directory() . $path), true);
	wp_localize_script('retails-checkout', 'retailsPayment', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('retails_payment'),
		// Order payment links (/pay/?o=) always take cards; the setting only covers checkout.
		'cardEnabled' => !retails_setting_enabled('payment_disabled') || (is_page('pay') && isset($_GET['o'])),
		'method' => retails_payment_method(),
	));
}
add_action('wp_enqueue_scripts', 'retails_enqueue_payment_script');

/**
 * The order the visitor just placed, when the thank-you link matches it.
 */
function retails_thank_you_order()
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
 * Runs after the deferred gtag config in retails_deferred_conversion_tracking().
 */
function retails_purchase_conversion()
{
	if (!get_query_var('retails_thank_you')) {
		return;
	}
	$order = retails_thank_you_order();
	if (!$order || get_post_meta($order, '_conversion_tracked', true)) {
		return;
	}
	update_post_meta($order, '_conversion_tracked', current_time('mysql'));

	$cost = retails_decode_order_meta_array(get_post_meta($order, 'product_cost', true));
	$value = isset($cost['grand_total']) ? (float) $cost['grand_total'] : 0;
	$transaction_id = (string) get_post_meta($order, 'order_id', true);
	$user_data = retails_ads_order_user_data($order);
	$items = retails_ads_order_items($order);
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
				send_to: <?php echo wp_json_encode('AW-18454059893/' . RETAILS_ADS_PURCHASE_LABEL); ?>,
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
add_action('wp_footer', 'retails_purchase_conversion', 21);

// The confirmation page is private to the customer who placed the order.
add_filter('pre_get_document_title', function ($title) {
	return get_query_var('retails_thank_you') ? 'Order confirmed | ' . get_bloginfo('name') : $title;
}, 99);
add_filter('wp_robots', function ($robots) {
	if (get_query_var('retails_thank_you')) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}
	return $robots;
});
