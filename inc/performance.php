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

	// Set in Settings > Storefront Sign > Images.
	$hero = wholesale_landing_hero_urls();
	if ($hero['phone']) {
		printf('<link rel="preload" as="image" href="%s" media="(max-width: 767px)" fetchpriority="high">' . "\n", esc_url($hero['phone']));
	}
	if ($hero['wide']) {
		printf('<link rel="preload" as="image" href="%s" media="(min-width: 768px)" fetchpriority="high">' . "\n", esc_url($hero['wide']));
	}
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

	// The fallback copy drops the id, which the async tag already uses.
	return $async . '<noscript>' . preg_replace("/ id='[^']*'/", '', $tag, 1) . '</noscript>';
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

	// Quote-form pages are fine in a cache that drops pages well before their
	// nonce expires (logged-out nonces last at least 12 hours), such as the
	// SFS Page Cache plugin's 8 hours.
	$short_cache = defined('SFS_PAGE_CACHE_MAX_AGE') && SFS_PAGE_CACHE_MAX_AGE <= 10 * HOUR_IN_SECONDS;
	if (!$short_cache && (is_page(array('contact', 'banners-displays', 'window-graphics')) || is_page_template('page-channel-letters.php'))) {
		return true;
	}

	return is_page(array('cart', 'checkout', 'account', 'my-orders', 'my_orders', 'orders', 'track-order', 'login', 'signup', 'pay', 'payment', 'channel-letter-builder', 'b2-calculator'))
		|| is_page_template(array('page-channel-letters-ads.php', 'landing-page.php', 'cl_builderr.php', 'b2calc.php', 'cart.php', 'checkout.php', 'account.php', 'my_orders.php', 'login.php', 'signup.php', 'payment.php', 'page-track-order.php'));
}

function wholesale_cache_exclusions()
{
	if (is_admin()) {
		return;
	}

	if (!wholesale_cache_excluded_request()) {
		wholesale_send_revalidate_headers();
		return;
	}

	if (!defined('DONOTCACHEPAGE')) {
		define('DONOTCACHEPAGE', true);
	}
	if (!headers_sent()) {
		header('Cache-Control: private, no-store, max-age=0');
		wholesale_send_revalidate_headers();
	}
}
add_action('template_redirect', 'wholesale_cache_exclusions', 5);

/**
 * Whether PHP has already queued a response header with this name.
 */
function wholesale_response_has_header($name)
{
	foreach (headers_list() as $header) {
		if (0 === stripos($header, $name . ':')) {
			return true;
		}
	}

	return false;
}

/**
 * Make browsers check back before reusing a page. The live host's root
 * .htaccess has "ExpiresDefault 1 year", and mod_expires adds it to any
 * response that has no Expires header of its own, so product pages and the
 * home page were kept in visitors' browsers for a year: returning visitors
 * saw old prices and sale dates, and forms with day-old nonces failed.
 * Sending an Expires header (and a Cache-Control when none is set yet, such
 * as the session's no-store) keeps mod_expires out of HTML responses.
 */
function wholesale_send_revalidate_headers()
{
	if (headers_sent()) {
		return;
	}
	if (!wholesale_response_has_header('Cache-Control')) {
		header('Cache-Control: no-cache, max-age=0, must-revalidate');
	}
	if (!wholesale_response_has_header('Expires')) {
		header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
	}
}

/**
 * REST responses to anonymous visitors have no cache headers of their own
 * (WordPress only adds them for logged-in users), so mod_expires gave them a
 * year as well. Give each one an Expires header that matches its
 * Cache-Control, so a route that allows caching (the live search's 60
 * seconds) keeps it and everything else is checked again.
 */
function wholesale_rest_cache_headers($response)
{
	if (!$response instanceof WP_HTTP_Response) {
		return $response;
	}

	$cache = '';
	foreach ($response->get_headers() as $key => $value) {
		if (0 === strcasecmp($key, 'Expires')) {
			return $response;
		}
		if (0 === strcasecmp($key, 'Cache-Control')) {
			$cache = (string) $value;
		}
	}

	if ('' === $cache) {
		$response->header('Cache-Control', 'no-cache, max-age=0, must-revalidate');
	}
	$max_age = preg_match('/\bmax-age=(\d+)/', $cache, $match) ? (int) $match[1] : 0;
	$response->header('Expires', $max_age ? gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT' : 'Wed, 11 Jan 1984 05:00:00 GMT');

	return $response;
}
add_filter('rest_post_dispatch', 'wholesale_rest_cache_headers', 99);

/**
 * Stylesheets that never style anything in the first view, loaded without
 * blocking the first paint: the cart drawer (hidden until opened; its other
 * rules are click states) and, on the long home page, the footer.
 */
function wholesale_async_offscreen_styles($tag, $handle)
{
	if (is_admin() || false === strpos($tag, "media='all'")
		|| !('wholesale-mini-cart' === $handle || ('wholesale-footer' === $handle && is_front_page()))) {
		return $tag;
	}

	$async = str_replace("media='all'", "media='print' onload=\"this.media='all'\"", $tag);

	// The fallback copy drops the id, which the async tag already uses.
	return $async . '<noscript>' . preg_replace("/ id='[^']*'/", '', $tag, 1) . '</noscript>';
}
add_filter('style_loader_tag', 'wholesale_async_offscreen_styles', 10, 2);

/**
 * The social icons plugin enqueues jQuery UI core on every page, but nothing
 * on the public site uses it. Scripts that depend on it still pull it in.
 */
function wholesale_dequeue_unused_jquery_ui()
{
	if (!is_admin()) {
		wp_dequeue_script('jquery-ui-core');
	}
}
add_action('wp_enqueue_scripts', 'wholesale_dequeue_unused_jquery_ui', 100);

/**
 * Add a 480px-wide copy of the header logo to its srcset. The logo shows at
 * about 240px on phones, so on 2x screens the browser otherwise picks the
 * 768px file. The copy is made once, next to the uploaded original.
 */
function wholesale_logo_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
{
	$width = 480;
	if (is_admin() || !is_array($sources) || isset($sources[$width]) || (int) $attachment_id !== (int) get_theme_mod('custom_logo')
		|| empty($image_meta['width']) || empty($image_meta['height']) || $image_meta['width'] <= $width) {
		return $sources;
	}

	$file = get_attached_file($attachment_id);
	if (!$file || !file_exists($file)) {
		return $sources;
	}

	$height = (int) round($width * $image_meta['height'] / $image_meta['width']);
	$info = pathinfo($file);
	$name = $info['filename'] . '-' . $width . 'x' . $height . '.' . $info['extension'];
	$target = $info['dirname'] . '/' . $name;

	if (!file_exists($target)) {
		$editor = wp_get_image_editor($file);
		if (is_wp_error($editor) || is_wp_error($editor->resize($width, $height, false)) || is_wp_error($editor->save($target))) {
			return $sources;
		}
	}

	$sources[$width] = array(
		'url' => trailingslashit(dirname($image_src)) . $name,
		'descriptor' => 'w',
		'value' => $width,
	);
	ksort($sources);

	return $sources;
}
add_filter('wp_calculate_image_srcset', 'wholesale_logo_srcset', 10, 5);

/**
 * Pages that use css/bootstrap-critical.css instead of the full Bootstrap
 * stylesheet for the first paint: public content and landing pages for
 * logged-out visitors. Product, cart, checkout, account and builder pages,
 * whose forms use much more of Bootstrap, keep the full file.
 */
function wholesale_use_critical_bootstrap()
{
	static $use = null;
	if (null !== $use) {
		return $use;
	}

	$dir = get_template_directory() . '/css/';
	$use = !is_admin() && !is_customize_preview() && !is_user_logged_in()
		&& file_exists($dir . 'bootstrap-critical.css')
		// A newer bootstrap.min.css means the subset is stale.
		&& filemtime($dir . 'bootstrap-critical.css') >= filemtime($dir . 'bootstrap.min.css')
		&& !is_singular(array('product', 'order'))
		&& !get_query_var('wholesale_thank_you')
		&& !is_page(array('cart', 'checkout', 'account', 'my-orders', 'track-order', 'login', 'signup', 'payment', 'pay', 'channel-letter-builder'))
		&& !is_page_template(array('cart.php', 'checkout.php', 'account.php', 'my_orders.php', 'login.php', 'signup.php', 'payment.php', 'page-pay.php', 'page-track-order.php', 'cl_builderr.php', 'b2calc.php', 'landing-page.php', 'page-channel-letters-ads.php'));

	return $use;
}

/**
 * Bootstrap is 31 KB (gzipped) of render-blocking CSS, of which these pages
 * use about 8 KB. Block the first paint only on that subset
 * (css/bootstrap-critical.css: every Bootstrap rule that matches an element
 * on the site's public pages, plus the collapse/fade/show states), and add the
 * full file right after it, in the same cascade position, once the page has
 * loaded, for markup that scripts add later.
 *
 * Regenerate the subset with utils/critical-css/ after changing Bootstrap
 * classes in templates; until then any missing rule applies after load.
 */
function wholesale_critical_bootstrap_tag($tag, $handle, $href)
{
	if ('bootsrap' !== $handle || !wholesale_use_critical_bootstrap()) {
		return $tag;
	}

	$file = get_template_directory() . '/css/bootstrap-critical.css';
	$critical = add_query_arg('ver', filemtime($file), get_template_directory_uri() . '/css/bootstrap-critical.css');
	if (function_exists('wholesale_cache_bust_asset_src')) {
		$critical = wholesale_cache_bust_asset_src($critical);
	}

	ob_start();
	?>
<link rel="stylesheet" id="bootsrap-critical-css" href="<?php echo esc_url($critical); ?>" media="all">
<script>
	window.addEventListener('load', function () {
		var critical = document.getElementById('bootsrap-critical-css');
		var full = document.createElement('link');
		full.rel = 'stylesheet';
		full.id = 'bootsrap-css';
		full.href = <?php echo wp_json_encode($href); ?>;
		// media="print" until it has loaded: a stylesheet added before the first
		// paint otherwise holds the paint back (the page stayed blank until it
		// arrived, PageSpeed Speed Index 5.7s).
		full.media = 'print';
		full.onload = function () { this.media = 'all'; };
		critical.parentNode.insertBefore(full, critical.nextSibling);
	});
</script>
<noscript><?php echo trim($tag); ?></noscript>
	<?php
	return ob_get_clean();
}
add_filter('style_loader_tag', 'wholesale_critical_bootstrap_tag', 10, 3);

/**
 * The same split for css/style.css on the home page: it is 27 KB (gzipped) of
 * render-blocking CSS, of which the home page uses about 7 KB. Block the first
 * paint only on css/style-home-critical.css (every style.css rule that matches
 * an element on the home page, plus their open/active states) and add the
 * full file in the same cascade position once the page has loaded.
 *
 * Built with utils/critical-css (keep-style-home.txt). Any later edit to
 * style.css makes the subset stale, and the home page then loads the full
 * file as before until the subset is rebuilt.
 */
function wholesale_critical_home_style_tag($tag, $handle, $href)
{
	if ('custom-style' !== $handle || !is_front_page() || !wholesale_use_critical_bootstrap()) {
		return $tag;
	}

	$dir = get_template_directory() . '/css/';
	if (!file_exists($dir . 'style-home-critical.css') || filemtime($dir . 'style-home-critical.css') < filemtime($dir . 'style.css')) {
		return $tag;
	}

	$critical = add_query_arg('ver', filemtime($dir . 'style-home-critical.css'), get_template_directory_uri() . '/css/style-home-critical.css');
	if (function_exists('wholesale_cache_bust_asset_src')) {
		$critical = wholesale_cache_bust_asset_src($critical);
	}

	ob_start();
	?>
<link rel="stylesheet" id="custom-style-critical-css" href="<?php echo esc_url($critical); ?>" media="all">
<script>
	window.addEventListener('load', function () {
		var critical = document.getElementById('custom-style-critical-css');
		var full = document.createElement('link');
		full.rel = 'stylesheet';
		full.id = 'custom-style-css';
		full.href = <?php echo wp_json_encode($href); ?>;
		// media="print" until it has loaded, so it never holds a paint back.
		full.media = 'print';
		full.onload = function () { this.media = 'all'; };
		critical.parentNode.insertBefore(full, critical.nextSibling);
	});
</script>
<noscript><?php echo trim($tag); ?></noscript>
	<?php
	return ob_get_clean();
}
add_filter('style_loader_tag', 'wholesale_critical_home_style_tag', 10, 3);

/**
 * Offer AVIF copies of the header logo (about 40% smaller than its WebP; the
 * transparency is what keeps the WebP large). The copies come from the same
 * background job as the hero photos (wholesale_hero_avif_url()); until they
 * exist the plain <img> is printed. display:contents keeps the <picture>
 * wrapper out of the layout, so the logo CSS still sizes the <img> as before.
 */
function wholesale_logo_avif_picture($html, $attachment_id)
{
	if (is_admin() || (int) $attachment_id !== (int) get_theme_mod('custom_logo') || !function_exists('wholesale_hero_avif_url')
		|| !preg_match('/class="[^"]*(?<![\w-])header-logo(?![\w-])/', $html) || !preg_match('/srcset="([^"]+)"/', $html, $srcset)) {
		return $html;
	}

	$candidates = explode(',', $srcset[1]);
	$sources = array();
	foreach ($candidates as $candidate) {
		$parts = preg_split('/\s+/', trim($candidate));
		$avif = 2 === count($parts) ? wholesale_hero_avif_url($parts[0]) : '';
		if ($avif) {
			$sources[] = esc_url($avif) . ' ' . $parts[1];
		}
	}
	// Only when every width has a copy, so the browser never jumps to a larger file.
	if (count($sources) !== count($candidates)) {
		return $html;
	}

	$sizes = preg_match('/sizes="([^"]+)"/', $html, $m) ? ' sizes="' . $m[1] . '"' : '';

	return '<picture style="display:contents"><source type="image/avif" srcset="' . implode(', ', $sources) . '"' . $sizes . '>' . $html . '</picture>';
}
add_filter('wp_get_attachment_image', 'wholesale_logo_avif_picture', 10, 2);
