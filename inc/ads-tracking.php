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
const RETAILS_ADS_CLICK_COOKIE = 'sso_ads_click';

/**
 * The ad click ID on this request's URL, or an empty array.
 *
 * @return array array('type' => gclid|gbraid|wbraid, 'id' => click ID)
 */
function retails_ads_click_from_request()
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
 * @return array Same shape as retails_ads_click_from_request().
 */
function retails_ads_click()
{
	$click = retails_ads_click_from_request();
	if ($click) {
		return $click;
	}

	if (empty($_COOKIE[RETAILS_ADS_CLICK_COOKIE])) {
		return array();
	}

	$parts = explode(':', sanitize_text_field(wp_unslash($_COOKIE[RETAILS_ADS_CLICK_COOKIE])), 2);
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
function retails_ads_remember_click()
{
	if (is_admin() || wp_doing_ajax() || headers_sent()) {
		return;
	}

	$click = retails_ads_click_from_request();
	if (!$click) {
		return;
	}

	setcookie(RETAILS_ADS_CLICK_COOKIE, $click['type'] . ':' . $click['id'], array(
		'expires' => time() + 90 * DAY_IN_SECONDS,
		'path' => COOKIEPATH ? COOKIEPATH : '/',
		'domain' => COOKIE_DOMAIN,
		'secure' => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	));
	$_COOKIE[RETAILS_ADS_CLICK_COOKIE] = $click['type'] . ':' . $click['id'];
}
add_action('init', 'retails_ads_remember_click');

/**
 * Save the ad click on a new order, for offline conversion uploads and for
 * seeing which orders came from ads.
 */
function retails_ads_attach_click_to_order($order_id)
{
	$click = retails_ads_click();
	if ($click && !get_post_meta($order_id, '_ads_click_id', true)) {
		update_post_meta($order_id, '_ads_click_type', $click['type']);
		update_post_meta($order_id, '_ads_click_id', $click['id']);
	}
}
add_action('retails_order_created', 'retails_ads_attach_click_to_order');

/**
 * Quote forms outside the ads landing page don't read the click ID from the
 * URL; fill it in from the remembered click when a submission is created.
 */
function retails_ads_attach_click_to_quote($post_id, $post, $update)
{
	if ($update || wp_is_post_revision($post_id) || is_admin()) {
		return;
	}

	$click = retails_ads_click();
	if ($click && !get_post_meta($post_id, '_contact_gclid', true)) {
		update_post_meta($post_id, '_contact_gclid', $click['id']);
		update_post_meta($post_id, '_contact_click_type', $click['type']);
	}
}
add_action('save_post_contact_submission', 'retails_ads_attach_click_to_quote', 10, 3);

/**
 * Whether this page view came straight from an ad click. The Google tag then
 * loads right away instead of after the page finishes loading, so the click
 * is recorded even if the visitor moves on quickly.
 */
function retails_ads_is_ad_landing()
{
	return (bool) retails_ads_click_from_request();
}

/**
 * Customer data for enhanced conversions, from an order's billing details.
 *
 * @return array Google tag user_data (unhashed; the tag hashes it).
 */
function retails_ads_order_user_data($order_id)
{
	$billing = retails_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
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
 * Product ID for GA4 items: the Merchant Center feed item ID
 * (inc/merchant-feed.php), so sales match the Shopping products.
 */
function retails_ads_item_id($product_id)
{
	return function_exists('retails_merchant_feed_id') ? retails_merchant_feed_id($product_id) : (string) $product_id;
}

/**
 * Items of an order in the GA4 ecommerce format.
 *
 * @return array
 */
function retails_ads_order_items($order_id)
{
	$items = array();

	foreach (retails_decode_order_meta_array(get_post_meta($order_id, 'product_json', true)) as $item) {
		$quantity = max(1, (int) ($item['product_quantity'] ?? 1));
		$subtotal = (float) ($item['product_subtotal'] ?? 0);
		$items[] = array_filter(array(
			'item_id' => isset($item['product_id']) ? retails_ads_item_id($item['product_id']) : '',
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
function retails_ads_begin_checkout_event()
{
	if (!is_page('checkout') || !function_exists('retails_get_cart')) {
		return;
	}

	$cart = retails_get_cart();
	$items = array();
	foreach ((array) $cart->get_items() as $item) {
		$item = (array) $item;
		$quantity = max(1, (int) ($item['product_quantity'] ?? 1));
		$items[] = array(
			'item_id' => isset($item['product_id']) ? retails_ads_item_id($item['product_id']) : '',
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
add_action('wp_footer', 'retails_ads_begin_checkout_event', 21);

/**
 * Secondary Google Ads conversions sent from the browser, keyed by their theme
 * setting (Settings > Storefront Sign > Google Ads). An empty label turns one off.
 *
 * @return array setting key => array('event' => script key, 'label' => admin label, 'help' => admin help)
 */
function retails_ads_click_conversions()
{
	return array(
		'google_ads_phone_tap_label' => array('event' => 'phone_tap', 'label' => 'Phone tap conversion label', 'help' => 'Sent when someone taps or clicks a phone number link.'),
		'google_ads_sms_tap_label' => array('event' => 'sms_tap', 'label' => 'Text tap conversion label', 'help' => 'Sent when someone taps a "text us" link.'),
		'google_ads_email_tap_label' => array('event' => 'email_tap', 'label' => 'Email tap conversion label', 'help' => 'Sent when someone clicks an email address link.'),
		'google_ads_builder_start_label' => array('event' => 'builder_start', 'label' => 'Builder start conversion label', 'help' => 'Sent once per page view, the first time someone types sign text in the channel letter builder or a product price calculator.'),
		'google_ads_add_to_cart_label' => array('event' => 'add_to_cart', 'label' => 'Add to cart conversion label', 'help' => 'Sent with the item value when a product or builder design is added to the cart.'),
		'google_ads_chat_start_label' => array('event' => 'chat_start', 'label' => 'Live chat conversation label', 'help' => 'Sent once per conversation when a visitor starts a live chat (first message or file). Chats opened to ask about an existing order are not counted.'),
	);
}

/**
 * Conversion labels fixed in the theme (purchase, website calls).
 */
const RETAILS_ADS_PURCHASE_LABEL = 'UkFKCJGGp_kcEPW2yt9E';
const RETAILS_ADS_CALL_LABEL = 'e7GrCJqb85sdEPW2yt9E';

/**
 * The quote request label: the theme setting, else the built-in default.
 */
function retails_ads_lead_label()
{
	$label = trim((string) retails_get_setting('google_ads_lead_label'));

	return '' !== $label ? $label : retails_setting_defaults()['google_ads_lead_label'];
}

/**
 * Conversions the theme always sends, for the settings page.
 *
 * @return array[] Each: name, label, when.
 */
function retails_ads_builtin_conversions()
{
	return array(
		array('name' => 'Purchase', 'label' => RETAILS_ADS_PURCHASE_LABEL, 'when' => 'Once per order on the order confirmation page, with the order total, order number and customer details (enhanced conversions).'),
		array('name' => 'Quote request', 'label' => retails_ads_lead_label(), 'when' => 'Once per quote, on the page the quote form returns to (contact page, /custom-channel-letters/, /banners-displays/, landing pages).'),
		array('name' => 'Calls from website', 'label' => RETAILS_ADS_CALL_LABEL, 'when' => 'Google shows ad visitors a forwarding number in place of 866-436-2101 and counts their calls. Not a click event.'),
	);
}

/**
 * Every label the theme sends from its own triggers, so a page-view
 * conversion box can't send the same action a second time.
 *
 * @return string[]
 */
function retails_ads_sitewide_labels()
{
	$labels = wp_list_pluck(retails_ads_builtin_conversions(), 'label');
	foreach (array_keys(retails_ads_click_conversions()) as $key) {
		$labels[] = trim((string) retails_get_setting($key));
	}

	return array_values(array_filter($labels));
}

/**
 * The GA4 measurement ID from the theme settings, or '' when unset or malformed.
 */
function retails_ga4_measurement_id()
{
	$id = strtoupper(trim((string) retails_get_setting('ga4_measurement_id')));

	return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) ? $id : '';
}

/**
 * Phone, text and email taps, builder starts and cart adds. Events queue in
 * dataLayer, so ones that happen before the deferred Google tag loads
 * (retails_deferred_conversion_tracking) are sent once it does.
 */
function retails_ads_click_tracking_script()
{
	$labels = array();
	foreach (retails_ads_click_conversions() as $key => $conversion) {
		$label = trim((string) retails_get_setting($key));
		if ('' !== $label) {
			$labels[$conversion['event']] = $label;
		}
	}
	$ga4 = '' !== retails_ga4_measurement_id();
	if (!$labels && !$ga4) {
		return;
	}
	// This product page's feed item IDs, so add_to_cart names the item as the feed does. Group
	// buttons swap the other products of the group into the page (inc/product-group-swap.php).
	$feed_ids = array();
	if (is_singular('product')) {
		$feed_product_ids = array((int) get_queried_object_id());
		if (function_exists('retails_product_group_for_page')) {
			foreach (retails_product_group_for_page() as $group_member) {
				$feed_product_ids[] = (int) $group_member['id'];
			}
		}
		foreach (array_unique($feed_product_ids) as $feed_product_id) {
			$feed_ids[(string) $feed_product_id] = retails_ads_item_id($feed_product_id);
		}
	}
	?>
	<script>
		(function () {
			var labels = <?php echo wp_json_encode((object) $labels); ?>;
			var ga4 = <?php echo $ga4 ? 'true' : 'false'; ?>;
			// This product page's feed item IDs (with its group's, which a group button can swap in).
			var feedIds = <?php echo wp_json_encode($feed_ids ? $feed_ids : (object) array()); ?>;
			window.dataLayer = window.dataLayer || [];
			function gtag() { window.dataLayer.push(arguments); }
			function convert(key, params) {
				if (!labels[key]) return;
				params = params || {};
				params.send_to = 'AW-18454059893/' + labels[key];
				gtag('event', 'conversion', params);
			}

			// A double tap on the same link counts once.
			var lastTap = {};
			function tap(key) {
				var now = Date.now();
				if (lastTap[key] && now - lastTap[key] < 3000) return;
				lastTap[key] = now;
				convert(key);
			}
			document.addEventListener('click', function (e) {
				// composedPath reaches links inside the chat widget's shadow root.
				var target = e.composedPath ? e.composedPath()[0] : e.target;
				var link = target && target.closest ? target.closest('a[href]') : null;
				if (!link) return;
				var href = link.getAttribute('href').trim().toLowerCase();
				if (0 === href.indexOf('tel:')) tap('phone_tap');
				else if (0 === href.indexOf('sms:')) tap('sms_tap');
				else if (0 === href.indexOf('mailto:')) tap('email_tap');
			}, true);

			var started = false;
			document.addEventListener('input', function (e) {
				var field = e.target;
				if (started || !field.matches || !field.matches('#textInput, input[name="letters"]') || !field.value) return;
				started = true;
				convert('builder_start');
			}, true);

			// The sfs-live-chat widget dispatches this when the server creates a
			// conversation. Each conversation counts once, even across page loads.
			document.addEventListener('sfs-chat:conversation-started', function (e) {
				var chat = e.detail || {};
				if (!chat.conversation_id || 'order' === chat.source) return;
				var seen = [];
				try { seen = JSON.parse(localStorage.getItem('retails_chat_conversions') || '[]'); } catch (err) {}
				if (seen.indexOf(chat.conversation_id) > -1) return;
				seen.push(chat.conversation_id);
				try { localStorage.setItem('retails_chat_conversions', JSON.stringify(seen.slice(-20))); } catch (err) {}
				convert('chat_start', { transaction_id: 'chat-' + chat.conversation_id });
				if (ga4) gtag('event', 'generate_lead', { method: 'live_chat' });
			});

			// js/mini-cart.js dispatches this after the cart confirms the add.
			document.addEventListener('retails:cart-added', function (e) {
				var item = e.detail || {};
				var value = Number(item.value) || 0;
				var quantity = Number(item.quantity) || 1;
				convert('add_to_cart', { value: value, currency: 'USD' });
				if (ga4) {
					gtag('event', 'add_to_cart', {
						currency: 'USD',
						value: value,
						items: [{ item_id: feedIds[String(item.product_id)] || String(item.product_id || ''), item_name: item.title || '', quantity: quantity, price: Math.round(value / quantity * 100) / 100 }]
					});
				}
			});
		})();
	</script>
	<?php
}
add_action('wp_footer', 'retails_ads_click_tracking_script', 21);
