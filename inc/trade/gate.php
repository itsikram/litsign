<?php
/**
 * Trade site price and ordering gate.
 *
 * Visitors who are not approved trade accounts never receive a price, rate,
 * price-encoded option, discount or minimum: not in HTML, hidden inputs,
 * data attributes, JSON, AJAX/REST, feeds or schema. This is enforced on the
 * server in layers:
 *
 * 1. Data: every price meta field reads as empty/zero for them
 *    (get_post_metadata), so no template, card, search result or endpoint
 *    can print a real number.
 * 2. Templates: product pages and the builder render price-free trade
 *    versions; cart, checkout and payment pages redirect to /trade-account/.
 * 3. Endpoints: price, cart, payment and upload AJAX actions answer 403.
 *
 * Googlebot gets exactly what a logged-out visitor gets (no cloaking).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Product meta keys that hold prices, rates, discounts or price-encoded options. */
function wholesale_trade_price_meta_keys()
{
	return array(
		'_price_per_sqft' => '0',
		'_starting_at_text' => '',
		'_standard_ps_cost' => '0',
		'_backlit_cost' => '0',
		'_eight_ft_cable_cost' => '0',
		'_default_color_cost' => '0',
		'_discount_percent' => '0',
		'_promo_percent' => '0',
		'_promo_start' => '',
		'_promo_end' => '',
		'_min_order_price' => '0',
		'_min_order_per_side' => '0',
		'_size_presets' => '',
		// Written by SFS B2Sign Sync.
		'_qty_pricing' => '',
		'_turnaround_spec' => '',
		'_margin_floor' => '',
		'_max_print_price' => '0',
		'_max_print_extra_side' => '0',
		'_min_order_extra_side' => '0',
		'_size_dropdown' => '',
		'_cl_lexan_percent' => '0',
		'_cl_trimcap_1in' => '',
		'_cl_piece_pricing' => '',
		'product_attr' => '[]',
		'product_cl_data' => null, // Structure kept, costs zeroed (wholesale_trade_zero_cl_data()).
	);
}

/** Whether the current request must not see prices. Cron and CLI jobs are not visitors. */
function wholesale_trade_hide_prices()
{
	static $hide = null;
	if (null !== $hide && did_action('set_current_user')) {
		return $hide;
	}
	$value = !wp_doing_cron() && !(defined('WP_CLI') && WP_CLI) && !wholesale_trade_can_see_prices();
	if (did_action('set_current_user')) {
		$hide = $value;
	}
	return $value;
}

/** Channel letter size/cost tables with every cost set to 0 (sizes stay for the preview). */
function wholesale_trade_zero_cl_data($json)
{
	$data = json_decode((string) $json, true);
	if (!is_array($data)) {
		return '[]';
	}
	foreach ($data as &$group) {
		if (!is_array($group)) {
			continue;
		}
		if (isset($group['cost'])) {
			$group['cost'] = '0';
		}
		if (!empty($group['options']) && is_array($group['options'])) {
			foreach ($group['options'] as &$option) {
				if (is_array($option)) {
					$option = array_map(static function () {
						return '0';
					}, $option);
				}
			}
			unset($option);
		}
	}
	unset($group);
	return wp_json_encode($data);
}

add_filter('get_post_metadata', static function ($value, $object_id, $meta_key, $single) {
	static $reading = false;
	if ($reading || !$meta_key || null !== $value) {
		return $value;
	}
	$keys = wholesale_trade_price_meta_keys();
	if (!array_key_exists($meta_key, $keys) || !wholesale_trade_hide_prices() || 'product' !== get_post_type($object_id)) {
		return $value;
	}
	if ('product_cl_data' === $meta_key) {
		// The raw read runs this filter again; $reading lets it through once.
		$reading = true;
		$raw = get_post_meta($object_id, $meta_key, true);
		$reading = false;
		$blank = wholesale_trade_zero_cl_data($raw);
	} else {
		$blank = $keys[$meta_key];
	}
	return $single ? $blank : array($blank);
}, 1, 4);

/** Hide the free-shipping threshold bar from visitors who can't order. */
add_filter('wholesale_show_shipping_bar', static function ($show) {
	return $show && !wholesale_trade_hide_prices();
});

/** Store-wide sale and shipping amounts (Settings > Storefront Sign) are trade prices too. */
foreach (array('promo_percent' => '0', 'free_shipping_min' => '0', 'free_shipping_channel_min' => '0', 'standard_shipping_options' => '', 'channel_shipping_options' => '', 'saturday_delivery_fee' => '0', 'early_am_fee' => '0') as $wholesale_trade_setting => $wholesale_trade_blank) {
	add_filter('option_wholesale_' . $wholesale_trade_setting, static function ($value) use ($wholesale_trade_blank) {
		return wholesale_trade_hide_prices() ? $wholesale_trade_blank : $value;
	});
	add_filter('default_option_wholesale_' . $wholesale_trade_setting, static function ($value) use ($wholesale_trade_blank) {
		return wholesale_trade_hide_prices() ? $wholesale_trade_blank : $value;
	});
}
unset($wholesale_trade_setting, $wholesale_trade_blank);

/**
 * Whether this request carries a valid payment-ticket token (?t= on /pay/).
 * Staff send ticket links to any email address, and the 32-character token is
 * the access check, as on the retail site.
 */
function wholesale_trade_valid_ticket_request()
{
	static $valid = null;
	if (null === $valid) {
		$token = isset($_GET['t']) ? $_GET['t'] : '';
		$valid = is_string($token) && '' !== $token && function_exists('wholesale_find_ticket_by_token')
			&& (bool) wholesale_find_ticket_by_token(wp_unslash($token));
	}
	return $valid;
}

/**
 * Whether this request carries a valid order payment link (?o= on /pay/, inc/order-pay-link.php).
 * Staff email the link to the order's customer, who may not be signed in on that device; the
 * token is the access check, as for payment tickets. A paid order still opens (it says "Paid").
 */
function wholesale_trade_valid_order_pay_request()
{
	static $valid = null;
	if (null === $valid) {
		$token = isset($_GET['o']) && is_string($_GET['o']) && !isset($_GET['t']) ? $_GET['o'] : '';
		$valid = '' !== $token && function_exists('wholesale_find_order_by_pay_token') && function_exists('wholesale_order_pay_clean_token')
			&& (bool) wholesale_find_order_by_pay_token(wholesale_order_pay_clean_token($token));
	}
	return $valid;
}

/** Pages that take orders or money. */
function wholesale_trade_is_order_page()
{
	// A payment-ticket or order payment link opens for whoever holds it; /pay/ without a valid token stays gated.
	if ((is_page('pay') || is_page_template('page-pay.php')) && (wholesale_trade_valid_ticket_request() || wholesale_trade_valid_order_pay_request())) {
		return false;
	}
	// The receipt opens for whoever holds the order's private key link (a payment-ticket payer may not have an account).
	if (get_query_var('wholesale_thank_you') && function_exists('wholesale_thank_you_order') && wholesale_thank_you_order()) {
		return false;
	}
	return is_page(array('cart', 'checkout', 'payment', 'pay', 'my-orders', 'orders'))
		|| is_page_template(array('cart.php', 'checkout.php', 'payment.php', 'page-pay.php', 'my_orders.php'))
		|| (bool) get_query_var('wholesale_thank_you');
}

/**
 * The full catalog (/shop/) is priced browsing: approved accounts only. Others
 * go to the channel letters hub, which is what the trade site sells to them.
 */
add_action('template_redirect', static function () {
	if ((is_page('shop') || is_page_template('page-shop.php')) && !wholesale_trade_can_see_prices()) {
		// Per visitor (approved accounts get the catalog), so never cacheable.
		nocache_headers();
		wp_safe_redirect(home_url('/channel-letters/'), 302);
		exit;
	}
}, 1);

/** Order pages: approved accounts only; everyone else goes to sign-in or the application. */
add_action('template_redirect', static function () {
	if (!wholesale_trade_is_order_page() || wholesale_trade_can_order()) {
		return;
	}
	nocache_headers();
	wp_safe_redirect(is_user_logged_in() ? wholesale_trade_apply_url() : wholesale_trade_login_url());
	exit;
}, 1);

/**
 * Product pages and the builder: price-free trade templates for visitors who
 * can't see prices. Approved accounts get the full shared templates.
 */
add_filter('template_include', static function ($template) {
	if (!wholesale_trade_hide_prices()) {
		return $template;
	}
	if (is_singular('product')) {
		return get_template_directory() . '/template-parts/trade/product.php';
	}
	if (is_page('channel-letter-builder') || is_page_template('cl_builderr.php')) {
		return get_template_directory() . '/template-parts/trade/builder-gate.php';
	}
	return $template;
}, 50);

/** Price, cart, payment and upload endpoints refuse non-approved visitors. */
function wholesale_trade_gated_ajax_actions()
{
	return array(
		'wholesale_price_quote',
		'wholesale_cl_builder_add',
		'wholesale_mini_cart',
		'wholesale_mini_cart_qty',
		'wholesale_mini_cart_remove',
		'wholesale_mini_cart_restore',
		'wholesale_mini_cart_add',
		'wholesale_payment_start',
		'wholesale_payment_complete',
		'wholesale_upload_design',
		// Artwork size check on the product page (inc/artwork-size-check.php): ordering is
		// for approved accounts only, so the check is too.
		'wholesale_artwork_check',
		// SFS Wallet Payments plugin (Apple Pay / Google Pay through Stripe, PayPal / Venmo /
		// Pay Later): product page quote, express and checkout payments. Its Stripe webhook is a
		// REST route checked by Stripe's signature, not an AJAX action, so it stays open.
		'sfs_wallet_product_quote',
		'sfs_wallet_stripe_start',
		'sfs_wallet_stripe_complete',
		'sfs_wallet_paypal_create',
		'sfs_wallet_paypal_capture',
		'sfs_wallet_paypal_shipping',
		// wholesale_async_upload stays open: the contact form uses it for artwork
		// (no prices; inc/async-uploads.php checks type and size).
	);
}

/**
 * Paying a payment ticket or an order payment link: payment_start with kind=ticket and a valid
 * ticket token, or kind=order and a valid order payment-link token, or payment_complete for a
 * pending payment that was started for one of those. Cart payments stay gated. The amount
 * always comes from the ticket or order on the server (inc/payments.php).
 *
 * The same for the wallet plugin: sfs_wallet_stripe_start / sfs_wallet_paypal_create for a
 * ticket or order link (never the cart page or product page express buttons), and
 * sfs_wallet_stripe_complete / sfs_wallet_paypal_capture for a wallet payment started for one.
 */
function wholesale_trade_ticket_payment_request()
{
	$action = preg_replace('/^wp_ajax_(?:nopriv_)?/', '', (string) current_action());
	if (in_array($action, array('sfs_wallet_stripe_start', 'sfs_wallet_paypal_create'), true)) {
		if (!empty($_POST['express'])) {
			return false;
		}
		$action = 'wholesale_payment_start'; // Same kind and token fields.
	}
	if (in_array($action, array('sfs_wallet_stripe_complete', 'sfs_wallet_paypal_capture'), true)) {
		$ref = isset($_POST['ref']) && is_string($_POST['ref']) ? substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(wp_unslash($_POST['ref']))), 0, 40) : '';
		$pending = '' !== $ref ? get_transient('sfs_wallet_p_' . $ref) : false;
		return is_array($pending) && isset($pending['kind']) && in_array($pending['kind'], array('ticket', 'order'), true);
	}
	if ('wholesale_payment_start' === $action) {
		$kind = isset($_POST['kind']) && is_string($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
		if ('order' === $kind) {
			$order_token = isset($_POST['order_token']) && is_string($_POST['order_token']) ? $_POST['order_token'] : '';
			return '' !== $order_token && function_exists('wholesale_find_order_by_pay_token') && function_exists('wholesale_order_pay_clean_token')
				&& (bool) wholesale_find_order_by_pay_token(wholesale_order_pay_clean_token($order_token));
		}
		$token = isset($_POST['ticket_token']) && is_string($_POST['ticket_token']) ? wp_unslash($_POST['ticket_token']) : '';
		return 'ticket' === $kind && '' !== $token && function_exists('wholesale_find_ticket_by_token') && (bool) wholesale_find_ticket_by_token($token);
	}
	if ('wholesale_payment_complete' === $action) {
		$ref = isset($_POST['ref']) && is_string($_POST['ref']) ? preg_replace('/[^A-Z0-9]/', '', wp_unslash($_POST['ref'])) : '';
		$pending = '' !== $ref ? get_transient('wholesale_payment_' . $ref) : false;
		return is_array($pending) && isset($pending['kind']) && in_array($pending['kind'], array('ticket', 'order'), true);
	}
	return false;
}

foreach (wholesale_trade_gated_ajax_actions() as $wholesale_trade_action) {
	foreach (array('wp_ajax_', 'wp_ajax_nopriv_') as $wholesale_trade_prefix) {
		add_action($wholesale_trade_prefix . $wholesale_trade_action, static function () {
			if (wholesale_trade_can_order() || wholesale_trade_ticket_payment_request()) {
				return;
			}
			wp_send_json(array(
				'success' => false,
				'ok' => false,
				'data' => array('message' => 'Trade pricing and ordering are for approved trade accounts.', 'apply' => wholesale_trade_apply_url()),
			), 403);
		}, 0);
	}
}
unset($wholesale_trade_action, $wholesale_trade_prefix);

/**
 * Wallet buttons (SFS Wallet Payments, enqueued at priority 20) only for approved accounts.
 * Everyone else gets the price-free product template, so the plugin's script, its settings
 * and Stripe.js are not loaded there. Payment ticket and order links keep them: the token is
 * the access check, as for card payments.
 */
add_action('wp_enqueue_scripts', static function () {
	if (wholesale_trade_can_order() || wholesale_trade_valid_ticket_request() || wholesale_trade_valid_order_pay_request()) {
		return;
	}
	wp_dequeue_script('sfs-wallet');
	wp_dequeue_script('sfs-stripe-js');
	wp_dequeue_style('sfs-wallet');
}, 21);

/** Live search: strip any price field from results for visitors who can't see prices. */
add_filter('rest_post_dispatch', static function ($response, $server, $request) {
	if (!($response instanceof WP_REST_Response) || 0 !== strpos((string) $request->get_route(), '/ssx/v1/') || !wholesale_trade_hide_prices()) {
		return $response;
	}
	$data = $response->get_data();
	$strip = static function (&$node) use (&$strip) {
		if (!is_array($node)) {
			return;
		}
		foreach (array('price', 'price_html', 'regular_price', 'sale_price', 'starting_at') as $key) {
			unset($node[$key]);
		}
		foreach ($node as &$child) {
			$strip($child);
		}
	};
	$strip($data);
	$response->set_data($data);
	return $response;
}, 10, 3);

/** Supplier copy can carry price lines ("$50", "$7.43 per sq ft"); remove the amounts. */
function wholesale_trade_strip_price_text($html)
{
	if (!is_string($html) || '' === $html) {
		return $html;
	}
	$out = preg_replace('/\$\s?\d[\d,]*(\.\d+)?(\s*(per|\/)\s*\w+)?/i', '', $html);
	return null === $out ? $html : $out;
}

/** REST /wp/v2/product: the same price lines the trade product template strips. */
add_filter('rest_prepare_product', static function ($response) {
	if (!($response instanceof WP_REST_Response) || !wholesale_trade_hide_prices()) {
		return $response;
	}
	$data = $response->get_data();
	foreach (array('content', 'excerpt') as $key) {
		if (isset($data[$key]['rendered']) && is_string($data[$key]['rendered'])) {
			$data[$key]['rendered'] = wholesale_trade_strip_price_text($data[$key]['rendered']);
		}
	}
	$response->set_data($data);
	return $response;
}, 10, 1);

/** The cart count cookie is meaningless without ordering; drop it for non-approved visitors. */
add_action('init', static function () {
	if (!is_admin() && !wholesale_trade_can_order() && isset($_COOKIE['sso_cart_count'])) {
		setcookie('sso_cart_count', '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
	}
}, 3);

/**
 * The box shown wherever a price would be.
 *
 * @param string $context 'product' | 'card' | 'builder'
 */
function wholesale_trade_price_box($context = 'product')
{
	$logged_in = is_user_logged_in();
	$status = $logged_in ? wholesale_trade_status() : '';
	ob_start();
	?>
	<div class="tw-gate tw-gate--<?php echo esc_attr($context); ?>" role="note">
		<p class="tw-gate__title"><span class="tw-gate__lock" aria-hidden="true"></span>Trade pricing for approved accounts</p>
		<?php if ($logged_in) : ?>
			<p class="tw-gate__text">Your account status: <strong><?php echo esc_html(wholesale_trade_status_label($status)); ?></strong>. Prices and ordering open as soon as your account is approved.</p>
			<a class="tw-btn tw-btn--primary" href="<?php echo esc_url(wholesale_trade_apply_url()); ?>">View application status</a>
		<?php else : ?>
			<?php if ('product' !== $context) : // Product pages keep it short: the same paragraph on 7 type pages made them read alike. ?>
				<p class="tw-gate__text">Sign companies, installers and resellers get trade pricing, the online builder and checkout once their business is approved.</p>
			<?php endif; ?>
			<div class="tw-gate__actions">
				<a class="tw-btn tw-btn--primary" href="<?php echo esc_url(wholesale_trade_apply_url()); ?>">Apply for a trade account</a>
				<a class="tw-btn tw-btn--ghost" href="<?php echo esc_url(wholesale_trade_login_url()); ?>">Log in</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
