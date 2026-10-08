<?php
/**
 * Front-end loading tweaks that keep third-party widgets from slowing the
 * first view on phones.
 *
 * @package litsign
 */

/**
 * Load the Tidio chat widget on the visitor's first interaction (scroll, tap,
 * key or mouse move), or after 15 seconds, instead of as soon as the page
 * loads. The widget is about 500 KB of JavaScript; loading it later keeps the
 * page responsive for the first view while the chat stays available.
 *
 * Replaces the Tidio plugin's own footer loader; does nothing when the plugin
 * is inactive or not connected.
 */
function wholesale_delay_tidio_widget()
{
	global $wp_filter;

	$public_key = get_option('tidio-one-public-key');
	if (is_admin() || !$public_key || !class_exists('TidioLiveChat\\Widget\\WidgetLoader') || empty($wp_filter['wp_footer'])) {
		return;
	}

	$removed = false;
	foreach ($wp_filter['wp_footer']->callbacks as $priority => $callbacks) {
		foreach ($callbacks as $callback) {
			$function = $callback['function'];
			if (is_array($function) && $function[0] instanceof TidioLiveChat\Widget\WidgetLoader) {
				remove_action('wp_footer', $function, $priority);
				$removed = true;
			}
		}
	}

	if (!$removed) {
		return;
	}

	add_action('wp_footer', static function () use ($public_key) {
		$src = '//code.tidio.co/' . rawurlencode($public_key) . '.js';
		?>
		<script>
			document.tidioChatCode = <?php echo wp_json_encode($public_key); ?>;
			(function () {
				var loaded = false, events = ['scroll', 'pointerdown', 'keydown', 'touchstart', 'mousemove'];
				function load() {
					if (loaded) return;
					loaded = true;
					events.forEach(function (name) { window.removeEventListener(name, load, { passive: true }); });
					var script = document.createElement('script');
					script.async = true;
					script.src = <?php echo wp_json_encode($src); ?>;
					document.body.appendChild(script);
				}
				events.forEach(function (name) { window.addEventListener(name, load, { passive: true }); });
				window.addEventListener('load', function () { setTimeout(load, 15000); });
			})();
		</script>
		<?php
	}, 1000);
}
add_action('wp', 'wholesale_delay_tidio_widget');

/**
 * Let the browser fetch the /landing-page/ hero image while CSS is still
 * loading; it is that page's largest paint.
 */
function wholesale_preload_landing_hero()
{
	if (!is_page_template('landing-page.php')) {
		return;
	}

	$img = get_template_directory_uri() . '/img/';
	printf('<link rel="preload" as="image" href="%s" type="image/webp" media="(max-width: 767px)" fetchpriority="high">' . "\n", esc_url($img . 'landing-hero-960.webp'));
	printf('<link rel="preload" as="image" href="%s" type="image/webp" media="(min-width: 768px)" fetchpriority="high">' . "\n", esc_url($img . 'landing-hero-1440.webp'));
}
add_action('wp_head', 'wholesale_preload_landing_hero', 1);

/**
 * On the home page the guide styles only apply below the fold, so load that
 * stylesheet without blocking the first paint (it stays blocking on pages
 * whose hero uses it).
 */
function wholesale_async_home_guide_styles($tag, $handle)
{
	if ('wholesale-seo-pages' !== $handle || !is_front_page()) {
		return $tag;
	}

	$async = str_replace("media='all'", "media='print' onload=\"this.media='all'\"", $tag);

	return $async . '<noscript>' . $tag . '</noscript>';
}
add_filter('style_loader_tag', 'wholesale_async_home_guide_styles', 10, 2);

/**
 * Pages a page cache must never store, whatever cache plugin the host uses:
 * cart, checkout and account screens, the sign builder, pages with a quote
 * form (their nonce would go stale in the cache), and any request carrying an
 * ad click ID or campaign tags, which must reach PHP so the theme can store
 * the click for conversion tracking. Sets DONOTCACHEPAGE (respected by WP
 * Super Cache, LiteSpeed Cache, W3 Total Cache and WP Rocket) and a private,
 * no-store header for any proxy or host cache.
 */
function wholesale_cache_excluded_request()
{
	if (is_user_logged_in() || is_404() || is_search() || get_query_var('wholesale_thank_you') || is_singular('order')) {
		return true;
	}

	foreach (array('gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'utm_source', 'utm_medium', 'utm_campaign', 'quote_status', 'newsletter') as $param) {
		if (isset($_GET[$param])) {
			return true;
		}
	}

	return is_page(array('cart', 'checkout', 'account', 'my-orders', 'my_orders', 'orders', 'track-order', 'login', 'signup', 'pay', 'payment', 'channel-letter-builder', 'b2-calculator', 'contact', 'banners-displays'))
		|| is_page_template(array('page-channel-letters.php', 'page-channel-letters-ads.php', 'landing-page.php', 'cl_builderr.php', 'b2calc.php', 'cart.php', 'checkout.php', 'account.php', 'my_orders.php', 'login.php', 'signup.php', 'payment.php', 'page-track-order.php'));
}

function wholesale_cache_exclusions()
{
	if (is_admin() || !wholesale_cache_excluded_request()) {
		return;
	}

	if (!defined('DONOTCACHEPAGE')) {
		define('DONOTCACHEPAGE', true);
	}
	if (!headers_sent()) {
		header('Cache-Control: private, no-store, max-age=0');
	}
}
add_action('template_redirect', 'wholesale_cache_exclusions', 5);
