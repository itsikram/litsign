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
