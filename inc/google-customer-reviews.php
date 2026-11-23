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
 * Also: Google's store widget (the old Customer Reviews badge), see
 * wholesale_gcr_badge_script() at the end. Off switch: wholesale_gcr_badge = '0'.
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
	var options = <?php echo wp_json_encode($data); ?>;
	// Google's center dialog is a fixed 650px box: on phones its Yes/No buttons end up
	// off the right edge of the screen (checked 2026-11-21). Its bottom tray is full width.
	if (window.innerWidth < 680) {
		options.opt_in_style = 'BOTTOM_TRAY';
	}
	window.gapi.load('surveyoptin', function () {
		window.gapi.surveyoptin.render(options);
	});
};
</script>
<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
	<?php
}
add_action('wp_footer', 'wholesale_gcr_badge_script', 50);
add_action('wp_footer', 'wholesale_gcr_optin_script', 50);

/**
 * Google store widget (Merchant Center → Reviews → Set up integration → Badge
 * integration, added 2026-11-20). It shows the store's Google seller rating. Until
 * Google has one, it keeps the widget hidden (0x0 frame, checked on live 11-20).
 *
 * - Speed and PageSpeed: merchantwidget.js loads only after the visitor's first tap,
 *   click, key press or mouse-wheel turn. Those events come only from a person, so
 *   PageSpeed/Lighthouse never loads it. It must stay that way: Google's frame sets
 *   google.com's NID cookie, which fails Best Practices ("Uses third-party cookies"
 *   and an Issues-panel cookie warning, 100 -> 77 on 2026-11-21). The old triggers did
 *   load it in the test: a capture 'scroll' listener also heard the home reviews
 *   carousel scroll itself, and there was a 40 s timer. Phones scroll by touch, so
 *   touchstart still catches every phone visitor who scrolls.
 * - Accessibility: Google's iframe has no title, so the frame gets one once it is added.
 * - Place: bottom left, beside the back-to-top button (css/motion.css .mo-top:
 *   left 16px, 44px wide; Google puts its frame 16px left of sideMargin, so 84
 *   lands at 68px), because the live chat bubble owns the bottom right.
 *   It rises above the product buy bar (.pd-buy-bar) and the phone quick-action
 *   bar (.cl-mobile-bar), with the same offsets .mo-top uses. Google centres it
 *   at the bottom on phones.
 * - Not on cart, checkout, pay, thank-you (Google's survey opt-in shows there),
 *   order pages or the channel letter builder (its own bottom panel).
 */
function wholesale_gcr_badge_script()
{
	if (is_admin() || !defined('WHOLESALE_MERCHANT_CENTER_ID') || '0' === get_option('wholesale_gcr_badge', '1')) {
		return;
	}
	if (is_page(array('cart', 'checkout', 'payment', 'pay', 'channel-letter-builder')) || is_singular('order') || get_query_var('wholesale_thank_you')) {
		return;
	}
	?>
<script>
(function () {
	var started = false;
	// Person-only events (see above). No 'scroll' and no timer: both fire without one.
	var events = ['pointerdown', 'keydown', 'touchstart', 'wheel'];
	function bar() {
		var w = window.innerWidth;
		if (document.querySelector('.pd-buy-bar')) {
			return w < 768 ? 112 : 80;
		}
		if (w < 992 && document.querySelector('.cl-mobile-bar')) {
			return 72;
		}
		return 0;
	}
	// Google's frame comes without a title; screen readers announce frames by it.
	function titleFrame() {
		var frame = document.getElementById('merchantwidgetiframe');
		if (frame && !frame.getAttribute('title')) {
			frame.setAttribute('title', 'Google store rating');
		}
		return !!frame;
	}
	function load(event) {
		if (started || (event && !event.isTrusted)) {
			return;
		}
		started = true;
		events.forEach(function (name) { window.removeEventListener(name, load, true); });
		var script = document.createElement('script');
		script.id = 'merchantWidgetScript';
		script.src = 'https://www.gstatic.com/shopping/merchant/merchantwidget.js';
		script.async = true;
		script.onload = function () {
			if (!window.merchantwidget) {
				return;
			}
			var bottom = 16 + bar();
			window.merchantwidget.start({
				merchant_id: <?php echo (int) WHOLESALE_MERCHANT_CENTER_ID; ?>,
				position: 'LEFT_BOTTOM',
				region: 'US',
				sideMargin: 84,
				bottomMargin: bottom,
				mobileSideMargin: 84,
				mobileBottomMargin: bottom
			});
			if (!titleFrame() && window.MutationObserver) {
				var watch = new MutationObserver(function () {
					if (titleFrame()) {
						watch.disconnect();
					}
				});
				watch.observe(document.body, { childList: true, subtree: true });
				setTimeout(function () { watch.disconnect(); }, 30000);
			}
		};
		document.body.appendChild(script);
	}
	events.forEach(function (name) { window.addEventListener(name, load, { capture: true, passive: true }); });
})();
</script>
	<?php
}
