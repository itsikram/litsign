<?php
/**
 * Trade site identity and the retail features switched off in trade mode.
 *
 * Loaded only when wholesale_is_trade_site() is true (inc/trade/bootstrap.php).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Business details the trade site prints. Brand facts are the owner-confirmed
 * ones (wholesale-plan/wholesale-conversion-prompt-v2.md, section 3); the
 * contact email and staff notification address are settings
 * (Trade Accounts > Settings).
 *
 * @return array
 */
function wholesale_trade_brand()
{
	static $brand = null;
	if (null === $brand) {
		$email = sanitize_email((string) get_option('wholesale_trade_email', ''));
		$brand = array(
			'name' => 'Storefront Sign Wholesale',
			'legal_name' => 'Lit Sign Manufacturing LLC',
			'email' => $email ? $email : 'Info@StorefrontSignWholesale.com',
			'phone' => '866-436-2101',
			'phone_href' => 'tel:+18664362101',
			'text' => '206-618-6543',
			'text_href' => 'sms:+12066186543',
			'hours' => 'Mon–Fri 8am–5pm PST',
			'since' => 2002,
			'retail_url' => 'https://storefrontsignonline.com/',
			'retail_name' => 'Storefront Sign Online',
		);
	}
	return $brand;
}

/**
 * Where staff notifications for new applications go: the address set in Trade
 * Accounts > Settings, otherwise the same inboxes as new orders and reviews
 * (Emails > SMTP Settings > Store notifications).
 *
 * @return string[]
 */
function wholesale_trade_notify_email()
{
	$email = sanitize_email((string) get_option('wholesale_trade_notify_email', ''));
	if ($email) {
		return array($email);
	}
	$store = function_exists('wholesale_contact_admin_recipients') ? wholesale_contact_admin_recipients() : array();
	return $store ? $store : array(get_option('admin_email'));
}

/** Site name and tagline, whatever the options table says after a pull. */
add_filter('option_blogname', static function () {
	return wholesale_trade_brand()['name'];
});
add_filter('option_blogdescription', static function () {
	return 'Wholesale channel letters for sign companies. Trade only, since 2002.';
});

/**
 * Retail features that don't belong on the trade site. Removed here, after
 * every shared include has registered its hooks (functions.php calls
 * wholesale_trade_boot() last).
 */
function wholesale_trade_remove_retail_hooks()
{
	// Content writers: retail pages, guides, locations and migrations must never
	// be created in the trade DB (a DB push carries them to live).
	remove_action('admin_init', 'wholesale_seo_run_migrations');
	remove_action('admin_init', 'wholesale_guide_sync_pages', 20);
	remove_action('admin_init', 'wholesale_developer_create_page', 20);
	remove_action('init', 'wholesale_shop_ensure_page', 30);
	// The retail /sign-packages/ page (inc/buying-journey.php) is consumer bundles, not trade.
	remove_action('admin_init', 'wholesale_packages_sync_page', 21);

	// The retail theme 301s its other domains to the retail site and forces
	// https only for the retail host. Neither applies here; https is replaced below.
	remove_action('init', 'wholesale_seo_redirect_extra_domains', 1);
	remove_action('init', 'wholesale_seo_redirect_to_https', 1);

	// Retail Google Ads account (AW-18454059893) and its conversion labels. The
	// trade site gets its own IDs from the owner; until then, no Ads tag at all.
	remove_action('wp_footer', 'wholesale_deferred_conversion_tracking', 20);
	remove_action('wp_footer', 'wholesale_ads_begin_checkout_event', 21);
	remove_action('wp_footer', 'wholesale_ads_click_tracking_script', 21);
	remove_action('wp_footer', 'wholesale_page_conversion_script', 21);
	remove_action('wp_footer', 'wholesale_purchase_conversion', 21);
	// Google Customer Reviews opt-in (inc/google-customer-reviews.php) belongs to the retail
	// Merchant Center account (5877365740) and the retail domain.
	remove_action('wp_footer', 'wholesale_gcr_optin_script', 50);

	// Merchant Center and OpenAI product feeds and location pages are retail-only SEO
	// (the feeds carry prices, which are login-only here).
	remove_action('template_redirect', 'wholesale_render_merchant_feed', 0);
	remove_action('template_redirect', 'wholesale_render_openai_feed', 0);
	remove_action('template_redirect', 'wholesale_render_location_sitemap', 0);
}
// functions.php keeps registering hooks after wholesale_trade_boot(), so remove them once it's done.
add_action('after_setup_theme', 'wholesale_trade_remove_retail_hooks', 99);

/**
 * GA4 only (Settings > Storefront Sign > Marketing), never the retail Ads ID.
 * Nothing is printed while the ID is empty. Deferred like the retail loader
 * (functions.php wholesale_deferred_conversion_tracking): first scroll, tap,
 * key or mouse move, or 40s after load.
 */
add_action('wp_footer', static function () {
	$id = function_exists('wholesale_ga4_measurement_id') ? wholesale_ga4_measurement_id() : '';
	if (!$id) {
		return;
	}
	?>
	<script>
		(function () {
			var id = <?php echo wp_json_encode($id); ?>, started = false, events = ['scroll', 'pointerdown', 'keydown', 'touchstart', 'mousemove'];
			var start = function () {
				if (started) return;
				started = true;
				events.forEach(function (name) { window.removeEventListener(name, start, { passive: true }); });
				var script = document.createElement('script');
				script.async = true;
				script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
				document.head.appendChild(script);
				window.dataLayer = window.dataLayer || [];
				window.gtag = window.gtag || function () {
					window.dataLayer.push(arguments);
				};
				window.gtag('js', new Date());
				window.gtag('config', id);
			};
			events.forEach(function (name) { window.addEventListener(name, start, { passive: true }); });
			window.addEventListener('load', function () { setTimeout(start, 40000); });
		})();
	</script>
	<?php
}, 20);

/**
 * Plain-http requests for the live trade domain go to https (the retail
 * version of this only knows the retail host). GET/HEAD only.
 */
add_action('init', static function () {
	if (is_ssl() || is_admin() || wp_doing_cron() || empty($_SERVER['HTTP_HOST'])) {
		return;
	}
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		return;
	}
	$host = strtolower(preg_replace('/^www\.|:\d+$/', '', (string) wp_unslash($_SERVER['HTTP_HOST'])));
	$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
	if ('storefrontsignwholesale.com' !== $host || !in_array($method, array('GET', 'HEAD'), true)) {
		return;
	}
	$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
	if (preg_match('#/\.well-known/#', (string) wp_parse_url($uri, PHP_URL_PATH))) {
		return;
	}
	wp_redirect('https://storefrontsignwholesale.com' . ('/' === substr($uri, 0, 1) ? $uri : '/'), 301);
	exit;
}, 1);

/** Retail-only URLs on the trade site: the feed and location pages are gone (410). */
add_action('template_redirect', static function () {
	$gone = get_query_var('wholesale_merchant_feed') || get_query_var('wholesale_openai_feed') || get_query_var('wholesale_location_sitemap');
	if (!$gone && is_page()) {
		$retired = array('page-location.php', 'page-locations.php', 'page-industry.php', 'page-industries.php', 'page-sign-packages.php');
		$template = get_page_template_slug(get_queried_object_id());
		$gone = in_array($template, $retired, true);
		// Location and industry pages have no template meta: inc/guide-pages.php picks
		// their template from its registry (template_include), so check that too.
		if (!$gone && function_exists('wholesale_guide_current')) {
			$entry = wholesale_guide_current();
			$gone = !empty($entry['template']) && in_array($entry['template'], $retired, true);
		}
		// The /locations/ hub is still linked from shared guide pages (sign permits,
		// install guide): send those visitors to the trade hub instead of a 410.
		if ($gone && function_exists('wholesale_guide_current_path') && 'locations' === wholesale_guide_current_path()) {
			wp_safe_redirect(home_url('/channel-letters/'), 301);
			exit;
		}
	}
	if ($gone) {
		status_header(410);
		nocache_headers();
		header('Content-Type: text/plain; charset=utf-8');
		echo 'Gone';
		exit;
	}
}, -1);

/**
 * Retail landing pages (consumer copy, retail price tables, the unconfirmed
 * "Made in USA" claim) go to the trade channel letters hub. 301, not 410:
 * trade pages and menus still link to some of them.
 */
add_action('template_redirect', static function () {
	if (!is_page()) {
		return;
	}
	if (is_page(array('custom-channel-letters', 'chennel-letter-ads', 'landing-page', 'channel-letter-cost', 'storefront-signs'))
		|| is_page_template(array('page-channel-letters.php', 'page-channel-letters-ads.php', 'landing-page.php', 'page-channel-letter-cost.php', 'page-storefront-signs.php'))) {
		wp_safe_redirect(home_url('/channel-letters/'), 301);
		exit;
	}
}, -1);

/** Header contact details and account links. */
add_filter('wholesale_contact_email', static function () {
	return wholesale_trade_brand()['email'];
});

/** "Made in USA" is not confirmed for the trade site (FTC standard); production is in WA and CA. */
function wholesale_trade_strip_unconfirmed_claims($html)
{
	if (!is_string($html) || '' === $html) {
		return $html;
	}
	$html = preg_replace('#<li[^>]*>(?:(?!</li>).)*Made\s+in\s+U\.?S\.?A\.?(?:(?!</li>).)*</li>#isu', '', $html);
	return preg_replace('#Made\s+in\s+(?:the\s+)?U\.?S\.?A\.?#iu', 'Built in Washington and California', $html);
}
add_filter('the_content', 'wholesale_trade_strip_unconfirmed_claims', 99);

/** Trade settings screen (Trade Accounts > Settings). */
add_action('admin_init', static function () {
	register_setting('wholesale_trade_settings', 'wholesale_trade_email', array('type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => ''));
	register_setting('wholesale_trade_settings', 'wholesale_trade_notify_email', array('type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => ''));
	register_setting('wholesale_trade_settings', 'wholesale_trade_require_resale', array('type' => 'boolean', 'sanitize_callback' => static function ($v) {
		return $v ? 1 : 0;
	}, 'default' => 0));
	register_setting('wholesale_trade_settings', 'wholesale_trade_auto_approve', array('type' => 'boolean', 'sanitize_callback' => static function ($v) {
		return $v ? 1 : 0;
	}, 'default' => 1));
});
