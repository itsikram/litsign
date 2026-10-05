<?php
/**
 * Visitor Insights: first-party visitor analytics with an admin dashboard.
 *
 * js/visitor-tracker.js records each visit (source, landing page, pages seen,
 * clicks, form use, errors and every shopping step) and posts it to the REST
 * route below. Orders and quote requests are tied to the visit on the server.
 * The admin page (Visitor Insights) shows traffic, the purchase funnel, where
 * Google Ads visitors drop off, and each visitor's full journey.
 *
 * Nothing visitors type into fields is ever recorded, only which field they
 * were on.
 *
 * @package litsign
 */

const WHOLESALE_VI_DB_VERSION = '1.0';
const WHOLESALE_VI_OFF_COOKIE = 'sso_vi_off';
const WHOLESALE_VI_SESSION_COOKIE = 'sso_sid';
const WHOLESALE_VI_MAX_EVENTS_PER_SESSION = 1500;

/* ---------------------------------------------------------------------------
 * Settings and storage
 * ------------------------------------------------------------------------ */

function wholesale_vi_settings()
{
	$saved = get_option('wholesale_vi_settings', array());

	return wp_parse_args(is_array($saved) ? $saved : array(), array(
		'enabled' => 1,
		'exclude_admins' => 1,
		'mask_ip' => 0,
		'retention_days' => 120,
		'excluded_ips' => '',
	));
}

function wholesale_vi_setting($key)
{
	$settings = wholesale_vi_settings();

	return isset($settings[$key]) ? $settings[$key] : null;
}

function wholesale_vi_table($name)
{
	global $wpdb;

	return $wpdb->prefix . 'wholesale_vi_' . $name;
}

function wholesale_vi_install()
{
	if (WHOLESALE_VI_DB_VERSION === get_option('wholesale_vi_db_version')) {
		return;
	}

	global $wpdb;
	$sessions = wholesale_vi_table('sessions');
	$events = wholesale_vi_table('events');
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta("CREATE TABLE {$sessions} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		session_key CHAR(32) NOT NULL,
		visitor_key CHAR(32) NOT NULL,
		started_at DATETIME NOT NULL,
		last_seen DATETIME NOT NULL,
		is_new TINYINT(1) NOT NULL DEFAULT 1,
		channel VARCHAR(20) NOT NULL DEFAULT 'direct',
		source VARCHAR(100) NOT NULL DEFAULT '',
		medium VARCHAR(100) NOT NULL DEFAULT '',
		campaign VARCHAR(190) NOT NULL DEFAULT '',
		term VARCHAR(190) NOT NULL DEFAULT '',
		content VARCHAR(190) NOT NULL DEFAULT '',
		click_type VARCHAR(10) NOT NULL DEFAULT '',
		referrer VARCHAR(500) NOT NULL DEFAULT '',
		landing_url VARCHAR(500) NOT NULL DEFAULT '',
		landing_type VARCHAR(20) NOT NULL DEFAULT '',
		exit_url VARCHAR(500) NOT NULL DEFAULT '',
		device VARCHAR(10) NOT NULL DEFAULT '',
		browser VARCHAR(40) NOT NULL DEFAULT '',
		os VARCHAR(40) NOT NULL DEFAULT '',
		screen VARCHAR(20) NOT NULL DEFAULT '',
		country CHAR(2) NOT NULL DEFAULT '',
		timezone VARCHAR(60) NOT NULL DEFAULT '',
		language VARCHAR(20) NOT NULL DEFAULT '',
		ip VARCHAR(45) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		pageviews SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		events SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		engaged_seconds INT UNSIGNED NOT NULL DEFAULT 0,
		max_scroll TINYINT UNSIGNED NOT NULL DEFAULT 0,
		stage TINYINT UNSIGNED NOT NULL DEFAULT 1,
		is_lead TINYINT(1) NOT NULL DEFAULT 0,
		order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		revenue DECIMAL(10,2) NOT NULL DEFAULT 0,
		errors SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		rage_clicks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY session_key (session_key),
		KEY started_at (started_at),
		KEY last_seen (last_seen),
		KEY visitor_key (visitor_key),
		KEY channel_started (channel, started_at),
		KEY ip (ip)
	) {$charset_collate};");

	dbDelta("CREATE TABLE {$events} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		session_id BIGINT UNSIGNED NOT NULL,
		created_at DATETIME NOT NULL,
		type VARCHAR(20) NOT NULL,
		page_key CHAR(8) NOT NULL DEFAULT '',
		url VARCHAR(500) NOT NULL DEFAULT '',
		label VARCHAR(255) NOT NULL DEFAULT '',
		value VARCHAR(255) NOT NULL DEFAULT '',
		seconds INT UNSIGNED NOT NULL DEFAULT 0,
		scroll TINYINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY session_id (session_id, id),
		KEY type_created (type, created_at),
		KEY created_at (created_at)
	) {$charset_collate};");

	update_option('wholesale_vi_db_version', WHOLESALE_VI_DB_VERSION);
}
add_action('init', 'wholesale_vi_install');

/**
 * Delete visits older than the retention period, once a day.
 */
function wholesale_vi_schedule_purge()
{
	if (!wp_next_scheduled('wholesale_vi_purge')) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wholesale_vi_purge');
	}
}
add_action('init', 'wholesale_vi_schedule_purge');

function wholesale_vi_purge_old()
{
	global $wpdb;
	$days = max(7, (int) wholesale_vi_setting('retention_days'));
	$cutoff = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
	$sessions = wholesale_vi_table('sessions');
	$events = wholesale_vi_table('events');

	$wpdb->query($wpdb->prepare("DELETE FROM {$events} WHERE created_at < %s", $cutoff));
	$wpdb->query($wpdb->prepare("DELETE FROM {$sessions} WHERE last_seen < %s", $cutoff));
}
add_action('wholesale_vi_purge', 'wholesale_vi_purge_old');

/* ---------------------------------------------------------------------------
 * Front end: tracker
 * ------------------------------------------------------------------------ */

/**
 * Whether this request's visitor should be left out of the stats.
 */
function wholesale_vi_is_excluded()
{
	if (!wholesale_vi_setting('enabled') || !empty($_COOKIE[WHOLESALE_VI_OFF_COOKIE])) {
		return true;
	}
	if (wholesale_vi_setting('exclude_admins') && is_user_logged_in() && current_user_can('edit_posts')) {
		return true;
	}

	$excluded = preg_split('/[\s,]+/', (string) wholesale_vi_setting('excluded_ips'), -1, PREG_SPLIT_NO_EMPTY);

	return $excluded && in_array(wholesale_vi_client_ip(false), $excluded, true);
}

/**
 * Staff browsing the site after visiting wp-admin get a cookie that keeps
 * their own visits out of the stats, even when logged out.
 */
function wholesale_vi_mark_staff_browser()
{
	if (wp_doing_ajax() || headers_sent() || !current_user_can('edit_posts')) {
		return;
	}

	$wanted = (bool) wholesale_vi_setting('exclude_admins');
	$has = !empty($_COOKIE[WHOLESALE_VI_OFF_COOKIE]);
	if ($wanted === $has) {
		return;
	}

	setcookie(WHOLESALE_VI_OFF_COOKIE, $wanted ? '1' : '', array(
		'expires' => $wanted ? time() + YEAR_IN_SECONDS : time() - HOUR_IN_SECONDS,
		'path' => '/',
		'secure' => is_ssl(),
		'httponly' => false,
		'samesite' => 'Lax',
	));
}
add_action('admin_init', 'wholesale_vi_mark_staff_browser');

/**
 * The kind of page being viewed, for the funnel and the reports.
 */
function wholesale_vi_page_type()
{
	if (get_query_var('wholesale_thank_you')) {
		return 'thankyou';
	}
	if (is_front_page()) {
		return 'home';
	}
	if (is_page('checkout')) {
		return 'checkout';
	}
	if (is_page('cart')) {
		return 'cart';
	}
	if (is_singular('product')) {
		return 'product';
	}
	if (is_page_template('cl_builderr.php') || is_page_template('b2calc.php')) {
		return 'builder';
	}
	if (is_page_template('landing-page.php') || is_page_template('page-channel-letters-ads.php')) {
		return 'landing';
	}
	if (get_query_var('category_slug')) {
		return 'category';
	}
	if (is_page('contact')) {
		return 'contact';
	}
	if (is_404()) {
		return '404';
	}
	if (is_search()) {
		return 'search';
	}
	if (is_singular('post') || is_home() || is_archive()) {
		return 'blog';
	}

	return 'page';
}

function wholesale_vi_enqueue_tracker()
{
	if (is_admin() || is_customize_preview() || is_preview() || wholesale_vi_is_excluded()) {
		return;
	}

	$path = '/js/visitor-tracker.js';
	$file = get_template_directory() . $path;
	wp_enqueue_script('wholesale-vi', get_template_directory_uri() . $path, array(), file_exists($file) ? (string) filemtime($file) : _S_VERSION, array('in_footer' => true, 'strategy' => 'defer'));

	$ptype = wholesale_vi_page_type();
	$cart_items = 0;
	if ('cart' === $ptype && function_exists('wholesale_get_cart')) {
		$cart_items = count((array) wholesale_get_cart()->get_items());
	}

	wp_localize_script('wholesale-vi', 'wholesaleVI', array(
		'endpoint' => rest_url('wholesale/v1/vi'),
		'ptype' => $ptype,
		'cart' => $cart_items,
	));
}
add_action('wp_enqueue_scripts', 'wholesale_vi_enqueue_tracker', 20);

/* ---------------------------------------------------------------------------
 * Collection endpoint
 * ------------------------------------------------------------------------ */

function wholesale_vi_register_route()
{
	register_rest_route('wholesale/v1', '/vi', array(
		'methods' => 'POST',
		'callback' => 'wholesale_vi_rest_collect',
		'permission_callback' => '__return_true',
	));
}
add_action('rest_api_init', 'wholesale_vi_register_route');

/**
 * The visitor's IP address. Only Cloudflare's header is trusted; any other
 * forwarded-for header can be set by the visitor.
 */
function wholesale_vi_client_ip($maybe_mask = true)
{
	$ip = '';
	if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_RAY'])) {
		$ip = trim(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
	}
	if (!filter_var($ip, FILTER_VALIDATE_IP)) {
		$ip = isset($_SERVER['REMOTE_ADDR']) ? trim(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
	}
	if (!filter_var($ip, FILTER_VALIDATE_IP)) {
		return '';
	}

	if ($maybe_mask && wholesale_vi_setting('mask_ip')) {
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$ip = preg_replace('/\.\d+$/', '.0', $ip);
		} else {
			$ip = implode(':', array_slice(explode(':', $ip), 0, 3)) . '::';
		}
	}

	return $ip;
}

function wholesale_vi_is_bot($ua)
{
	return '' === $ua || (bool) preg_match('/bot|crawl|spider|slurp|mediapartners|adsbot|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|preview|python|curl|wget|httpclient|okhttp|java\/|go-http|phantom|selenium|puppeteer|playwright|scrapy|facebookexternalhit|bingpreview/i', $ua);
}

/**
 * Device, browser and operating system from the user agent.
 */
function wholesale_vi_parse_ua($ua, $touch_points = 0)
{
	$os = 'Other';
	foreach (array(
		'/Windows NT/' => 'Windows',
		'/iPhone|iPad|iPod/' => 'iOS',
		'/CrOS/' => 'ChromeOS',
		'/Android/' => 'Android',
		'/Mac OS X|Macintosh/' => 'macOS',
		'/Linux/' => 'Linux',
	) as $pattern => $name) {
		if (preg_match($pattern, $ua)) {
			$os = $name;
			break;
		}
	}

	$browser = 'Other';
	foreach (array(
		'/FBAN|FBAV/' => 'Facebook app',
		'/Instagram/' => 'Instagram app',
		'/GSA\//' => 'Google app',
		'/Edg(e|A|iOS)?\//' => 'Edge',
		'/OPR\/|Opera/' => 'Opera',
		'/SamsungBrowser/' => 'Samsung Internet',
		'/CriOS|Chrome\//' => 'Chrome',
		'/FxiOS|Firefox\//' => 'Firefox',
		'/Safari\//' => 'Safari',
	) as $pattern => $name) {
		if (preg_match($pattern, $ua)) {
			$browser = $name;
			break;
		}
	}

	$device = 'desktop';
	if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle/i', $ua) || (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua))) {
		$device = 'tablet';
	} elseif (preg_match('/Mobi|iPhone|iPod|Windows Phone|BlackBerry|Opera Mini/i', $ua)) {
		$device = 'mobile';
	} elseif ('macOS' === $os && $touch_points > 1) {
		// iPadOS asks for the desktop site and reports itself as a Mac.
		$device = 'tablet';
		$os = 'iOS';
	}

	return compact('device', 'browser', 'os');
}

/**
 * Path and query of a URL, without ad click IDs (they are long and unique).
 */
function wholesale_vi_clean_url($url)
{
	$parts = wp_parse_url((string) $url);
	if (!$parts || empty($parts['path'])) {
		$parts['path'] = '/';
	}

	$path = $parts['path'];
	if (!empty($parts['query'])) {
		parse_str($parts['query'], $query);
		$query = array_diff_key($query, array_flip(array('gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'gad_source', 'gad_campaignid', '_gl', 'key')));
		if ($query) {
			$path .= '?' . http_build_query($query);
		}
	}

	return substr(sanitize_text_field($path), 0, 500);
}

/**
 * Traffic channel and source for a new visit, from the landing URL's
 * campaign parameters and the referrer.
 */
function wholesale_vi_attribution($campaign, $referrer)
{
	$campaign = array_map(static function ($value) {
		return substr(sanitize_text_field((string) $value), 0, 190);
	}, is_array($campaign) ? $campaign : array());

	$source = strtolower($campaign['utm_source'] ?? '');
	$medium = strtolower($campaign['utm_medium'] ?? '');
	$click_type = '';
	foreach (array('gclid', 'gbraid', 'wbraid', 'msclkid', 'fbclid') as $type) {
		if (!empty($campaign[$type])) {
			$click_type = $type;
			break;
		}
	}

	$ref_host = strtolower((string) wp_parse_url($referrer, PHP_URL_HOST));
	$own_host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
	$ref_host = preg_replace('/^(www|m|l|lm)\./', '', $ref_host);
	$is_search = (bool) preg_match('/(^|\.)(google|bing|yahoo|duckduckgo|ecosia|baidu|yandex|aol|ask|brave)\./', $ref_host . '.');
	$is_social = (bool) preg_match('/(^|\.)(facebook|instagram|t\.co|twitter|x\.com|linkedin|lnkd\.in|pinterest|youtube|tiktok|reddit|nextdoor|yelp)/', $ref_host);
	$is_internal = '' !== $ref_host && ($ref_host === preg_replace('/^www\./', '', $own_host));

	if (in_array($click_type, array('gclid', 'gbraid', 'wbraid'), true) || ('google' === $source && preg_match('/cpc|ppc|paid|ads/', $medium))) {
		$channel = 'google_ads';
		$source = $source ?: 'google';
		$medium = $medium ?: 'cpc';
	} elseif ('msclkid' === $click_type || preg_match('/^(cpc|ppc|paid|paidsearch|paid_search|cpm|display|banner|retargeting)$/', $medium)) {
		$channel = 'paid_other';
		$source = $source ?: ('msclkid' === $click_type ? 'bing' : ($ref_host ?: 'unknown'));
		$medium = $medium ?: 'cpc';
	} elseif ('email' === $medium || 'newsletter' === $medium) {
		$channel = 'email';
	} elseif ('fbclid' === $click_type || preg_match('/social/', $medium) || ($is_social && !$source)) {
		$channel = 'social';
		$source = $source ?: ($ref_host ?: 'facebook');
	} elseif ($source) {
		$channel = 'referral';
	} elseif ($is_search) {
		$channel = 'organic';
		$source = strtok($ref_host, '.');
		$medium = 'organic';
	} elseif ($ref_host && !$is_internal) {
		$channel = 'referral';
		$source = $ref_host;
		$medium = 'referral';
	} else {
		$channel = 'direct';
	}

	return array(
		'channel' => $channel,
		'source' => substr($source, 0, 100),
		'medium' => substr($medium, 0, 100),
		'campaign' => $campaign['utm_campaign'] ?? '',
		'term' => $campaign['utm_term'] ?? '',
		'content' => $campaign['utm_content'] ?? '',
		'click_type' => $click_type,
	);
}

/**
 * The logged-in customer sending a tracker request. The REST API treats
 * requests without a nonce as logged out, so read the login cookie directly.
 * The cookie is only used to label the visit, never to grant access.
 */
function wholesale_vi_request_user_id()
{
	$user_id = get_current_user_id();
	if (!$user_id && defined('LOGGED_IN_COOKIE') && !empty($_COOKIE[LOGGED_IN_COOKIE])) {
		$user_id = (int) wp_validate_auth_cookie(wp_unslash($_COOKIE[LOGGED_IN_COOKIE]), 'logged_in');
	}

	return $user_id;
}

function wholesale_vi_find_session($session_key)
{
	global $wpdb;
	$table = wholesale_vi_table('sessions');

	return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE session_key = %s", $session_key));
}

/**
 * The visit for this request, created from its first page view if new.
 */
function wholesale_vi_open_session($session_key, $visitor_key, $data, $events, $ua)
{
	global $wpdb;
	$session = wholesale_vi_find_session($session_key);
	if ($session) {
		return $session;
	}

	$first = array();
	foreach ($events as $event) {
		if (is_array($event) && 'pageview' === ($event['t'] ?? '')) {
			$first = $event;
			break;
		}
	}

	$referrer = isset($first['ref']) ? esc_url_raw(substr((string) $first['ref'], 0, 500)) : '';
	$attribution = wholesale_vi_attribution($first['campaign'] ?? array(), $referrer);
	$agent = wholesale_vi_parse_ua($ua, (int) ($data['tp'] ?? 0));
	$country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper(substr(sanitize_key(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])), 0, 2)) : '';
	$ago = min(max(0, (int) ($first['ago'] ?? 0)), HOUR_IN_SECONDS * 1000);
	$now = gmdate('Y-m-d H:i:s', time() - (int) round($ago / 1000));

	// A returning visitor is anyone seen before, even if their cookie says new.
	$table = wholesale_vi_table('sessions');
	$seen = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE visitor_key = %s", $visitor_key));

	$wpdb->query($wpdb->prepare(
		"INSERT IGNORE INTO {$table} (session_key, visitor_key, started_at, last_seen, is_new, channel, source, medium, campaign, term, content, click_type, referrer, landing_url, landing_type, exit_url, device, browser, os, screen, country, timezone, language, ip, user_id)
		VALUES (%s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d)",
		$session_key,
		$visitor_key,
		$now,
		$now,
		$seen ? 0 : 1,
		$attribution['channel'],
		$attribution['source'],
		$attribution['medium'],
		$attribution['campaign'],
		$attribution['term'],
		$attribution['content'],
		$attribution['click_type'],
		$referrer,
		wholesale_vi_clean_url($first['url'] ?? ($data['url'] ?? '/')),
		sanitize_key($first['ptype'] ?? ''),
		wholesale_vi_clean_url($first['url'] ?? ($data['url'] ?? '/')),
		$agent['device'],
		$agent['browser'],
		$agent['os'],
		substr(preg_replace('/[^0-9x]/', '', (string) ($first['screen'] ?? '')), 0, 20),
		preg_match('/^[A-Z]{2}$/', $country) && 'XX' !== $country ? $country : '',
		substr(preg_replace('#[^A-Za-z0-9_/+-]#', '', (string) ($first['tz'] ?? '')), 0, 60),
		substr(preg_replace('/[^A-Za-z-]/', '', (string) ($first['lang'] ?? '')), 0, 20),
		wholesale_vi_client_ip(),
		wholesale_vi_request_user_id()
	));

	return wholesale_vi_find_session($session_key);
}

/**
 * Funnel stage an event proves the visitor reached.
 *
 * 1 landed, 2 viewed a product, 3 configured or priced it, 4 added to cart,
 * 5 reached checkout, 6 tried to pay, 7 purchased.
 */
function wholesale_vi_event_stage($type, $event)
{
	switch ($type) {
		case 'pageview':
			$ptype = $event['ptype'] ?? '';
			if ('checkout' === $ptype) {
				return 5;
			}
			if ('cart' === $ptype && (int) ($event['cart'] ?? 0) > 0) {
				return 4;
			}
			return in_array($ptype, array('product', 'builder'), true) ? 2 : 1;
		case 'configure':
		case 'price_quote':
		case 'design_upload':
			return 3;
		case 'add_to_cart':
			return 4;
		case 'payment_start':
		case 'payment_error':
			return 6;
		case 'purchase':
			return 7;
	}

	return 1;
}

function wholesale_vi_event_types()
{
	return array(
		'pageview', 'click', 'call_click', 'email_click', 'rage_click', 'configure', 'price_quote', 'price_error',
		'design_upload', 'upload_error', 'add_to_cart', 'cart_error', 'cart_remove', 'form_start', 'form_submit',
		'payment_start', 'checkout_error', 'payment_error', 'js_error', 'purchase', 'quote_submit',
	);
}

function wholesale_vi_rest_collect(WP_REST_Request $request)
{
	$done = new WP_REST_Response(null, 204);
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr(wp_unslash($_SERVER['HTTP_USER_AGENT']), 0, 500) : '';

	if (wholesale_vi_is_excluded() || wholesale_vi_is_bot($ua)) {
		return $done;
	}

	$raw = $request->get_body();
	if (strlen($raw) > 65536) {
		return $done;
	}
	$data = json_decode($raw, true);
	if (!is_array($data) || !preg_match('/^[a-f0-9]{32}$/', (string) ($data['s'] ?? '')) || !preg_match('/^[a-f0-9]{32}$/', (string) ($data['v'] ?? ''))) {
		return $done;
	}

	$user_id = wholesale_vi_request_user_id();
	if ($user_id && wholesale_vi_setting('exclude_admins') && user_can($user_id, 'edit_posts')) {
		return $done;
	}

	$events = array_slice(array_values(array_filter((array) ($data['events'] ?? array()), 'is_array')), 0, 40);
	$session = wholesale_vi_open_session($data['s'], $data['v'], $data, $events, $ua);
	if (!$session || (int) $session->events >= WHOLESALE_VI_MAX_EVENTS_PER_SESSION) {
		return $done;
	}

	global $wpdb;
	// Customers who log in partway through a visit.
	if ($user_id && (int) $session->user_id !== $user_id) {
		$wpdb->update(wholesale_vi_table('sessions'), array('user_id' => $user_id), array('id' => (int) $session->id), array('%d'), array('%d'));
	}
	$events_table = wholesale_vi_table('events');
	$sessions_table = wholesale_vi_table('sessions');
	$page_key = preg_match('/^[a-z0-9]{8}$/', (string) ($data['p'] ?? '')) ? $data['p'] : '';
	$page_url = wholesale_vi_clean_url($data['url'] ?? '/');
	$allowed = wholesale_vi_event_types();
	$now = time();

	$stage = (int) $session->stage;
	$added = array('pageviews' => 0, 'events' => 0, 'errors' => 0, 'rage_clicks' => 0);
	$is_lead = (int) $session->is_lead;
	$exit_url = '';

	foreach ($events as $event) {
		$type = (string) ($event['t'] ?? '');
		if (!in_array($type, $allowed, true) || in_array($type, array('purchase', 'quote_submit'), true)) {
			continue;
		}

		$ago = min(max(0, (int) ($event['ago'] ?? 0)), 6 * HOUR_IN_SECONDS * 1000);
		$url = isset($event['url']) ? wholesale_vi_clean_url($event['url']) : $page_url;
		$label = substr(sanitize_text_field((string) ($event['label'] ?? '')), 0, 255);
		$value = substr(sanitize_text_field((string) ($event['value'] ?? '')), 0, 255);

		if ('pageview' === $type) {
			$label = substr(sanitize_text_field(html_entity_decode((string) ($event['title'] ?? ''), ENT_QUOTES, 'UTF-8')), 0, 255);
			$value = sanitize_key($event['ptype'] ?? '');
			$exit_url = $url;
			$added['pageviews']++;
		} elseif ('click' === $type && !empty($event['href'])) {
			$value = substr(esc_url_raw((string) $event['href']), 0, 255);
		}

		$wpdb->insert($events_table, array(
			'session_id' => (int) $session->id,
			'created_at' => gmdate('Y-m-d H:i:s', $now - (int) round($ago / 1000)),
			'type' => $type,
			'page_key' => $page_key,
			'url' => $url,
			'label' => $label,
			'value' => $value,
		), array('%d', '%s', '%s', '%s', '%s', '%s', '%s'));

		$added['events']++;
		$stage = max($stage, wholesale_vi_event_stage($type, $event));
		if (in_array($type, array('price_error', 'upload_error', 'cart_error', 'checkout_error', 'payment_error', 'js_error'), true)) {
			$added['errors']++;
		} elseif ('rage_click' === $type) {
			$added['rage_clicks']++;
		} elseif ('call_click' === $type || 'email_click' === $type) {
			$is_lead = 1;
		}
	}

	// Time on page, scroll depth and the field each unfinished form was left at.
	$ping = isset($data['ping']) && is_array($data['ping']) ? $data['ping'] : array();
	if ($page_key && $ping) {
		$wpdb->query($wpdb->prepare(
			"UPDATE {$events_table} SET seconds = GREATEST(seconds, %d), scroll = GREATEST(scroll, %d) WHERE session_id = %d AND page_key = %s AND type = 'pageview'",
			min(max(0, (int) ($ping['secs'] ?? 0)), 6 * HOUR_IN_SECONDS),
			min(max(0, (int) ($ping['scroll'] ?? 0)), 100),
			(int) $session->id,
			$page_key
		));

		foreach (array_slice((array) ($ping['forms'] ?? array()), 0, 5) as $form) {
			if (!is_array($form) || empty($form['n']) || empty($form['f'])) {
				continue;
			}
			$wpdb->query($wpdb->prepare(
				"UPDATE {$events_table} SET value = %s WHERE session_id = %d AND page_key = %s AND type = 'form_start' AND label = %s",
				substr(sanitize_text_field((string) $form['f']), 0, 255),
				(int) $session->id,
				$page_key,
				substr(sanitize_text_field((string) $form['n']), 0, 255)
			));
		}
	}

	$totals = $wpdb->get_row($wpdb->prepare(
		"SELECT COALESCE(SUM(seconds), 0) AS seconds, COALESCE(MAX(scroll), 0) AS scroll FROM {$events_table} WHERE session_id = %d AND type = 'pageview'",
		(int) $session->id
	));

	$wpdb->query($wpdb->prepare(
		"UPDATE {$sessions_table} SET last_seen = %s, pageviews = pageviews + %d, events = events + %d, errors = errors + %d, rage_clicks = rage_clicks + %d,
			stage = GREATEST(stage, %d), is_lead = GREATEST(is_lead, %d), engaged_seconds = %d, max_scroll = %d, exit_url = IF(%s = '', exit_url, %s)
		WHERE id = %d",
		gmdate('Y-m-d H:i:s', $now),
		$added['pageviews'],
		$added['events'],
		$added['errors'],
		$added['rage_clicks'],
		$stage,
		$is_lead,
		(int) $totals->seconds,
		(int) $totals->scroll,
		$exit_url,
		$exit_url,
		(int) $session->id
	));

	return $done;
}

/* ---------------------------------------------------------------------------
 * Orders and quote requests
 * ------------------------------------------------------------------------ */

/**
 * The visit of the visitor making this request, from the tracker's cookie.
 */
function wholesale_vi_current_session()
{
	$key = isset($_COOKIE[WHOLESALE_VI_SESSION_COOKIE]) ? sanitize_key(wp_unslash($_COOKIE[WHOLESALE_VI_SESSION_COOKIE])) : '';

	return preg_match('/^[a-f0-9]{32}$/', $key) ? wholesale_vi_find_session($key) : null;
}

function wholesale_vi_log_server_event($session, $type, $label, $value, $updates)
{
	global $wpdb;

	$wpdb->insert(wholesale_vi_table('events'), array(
		'session_id' => (int) $session->id,
		'created_at' => current_time('mysql', true),
		'type' => $type,
		'url' => isset($_SERVER['HTTP_REFERER']) ? wholesale_vi_clean_url(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
		'label' => substr($label, 0, 255),
		'value' => substr($value, 0, 255),
	));

	$set = array('last_seen = %s', 'events = events + 1');
	$params = array(current_time('mysql', true));
	foreach ($updates as $sql => $param) {
		$set[] = $sql;
		if (false !== strpos($sql, '%')) {
			$params[] = $param;
		}
	}
	$params[] = (int) $session->id;

	$wpdb->query($wpdb->prepare('UPDATE ' . wholesale_vi_table('sessions') . ' SET ' . implode(', ', $set) . ' WHERE id = %d', $params));
}

function wholesale_vi_record_order($order)
{
	$session = wholesale_vi_current_session();
	if (!$session) {
		return;
	}

	$cost = json_decode((string) get_post_meta($order, 'product_cost', true), true);
	$total = round((float) ($cost['grand_total'] ?? 0), 2);
	update_post_meta($order, '_vi_session_id', (int) $session->id);

	$updates = array(
		'stage = 7' => null,
		'order_id = %d' => (int) $order,
		'revenue = revenue + %f' => $total,
	);
	$user_id = (int) get_post_meta($order, 'user_id', true) ?: get_current_user_id();
	if ($user_id && !(int) $session->user_id) {
		$updates['user_id = %d'] = $user_id;
	}

	wholesale_vi_log_server_event($session, 'purchase', 'Order #' . get_post_meta($order, 'order_id', true), (string) $order, $updates);
}
add_action('wholesale_order_created', 'wholesale_vi_record_order');

function wholesale_vi_record_quote($post_id, $post, $update)
{
	if ($update || wp_is_post_revision($post_id) || is_admin()) {
		return;
	}

	$session = wholesale_vi_current_session();
	if (!$session) {
		return;
	}

	update_post_meta($post_id, '_vi_session_id', (int) $session->id);
	wholesale_vi_log_server_event($session, 'quote_submit', wp_strip_all_tags($post->post_title), (string) $post_id, array('is_lead = %d' => 1));
}
add_action('save_post_contact_submission', 'wholesale_vi_record_quote', 10, 3);

/* ---------------------------------------------------------------------------
 * Reporting
 * ------------------------------------------------------------------------ */

function wholesale_vi_channels()
{
	return array(
		'google_ads' => 'Google Ads',
		'organic' => 'Organic search',
		'direct' => 'Direct',
		'referral' => 'Referral',
		'social' => 'Social',
		'email' => 'Email',
		'paid_other' => 'Other paid',
	);
}

function wholesale_vi_stages()
{
	return array(
		1 => 'Landed on site',
		2 => 'Viewed a product',
		3 => 'Configured / got a price',
		4 => 'Added to cart',
		5 => 'Reached checkout',
		6 => 'Tried to pay',
		7 => 'Purchased',
	);
}

/**
 * SQL that is true for a visit with no real engagement: one page, under ten
 * seconds, and no shopping step or lead.
 */
function wholesale_vi_bounce_sql($alias = 's')
{
	return "({$alias}.pageviews <= 1 AND {$alias}.engaged_seconds < 10 AND {$alias}.stage < 3 AND {$alias}.is_lead = 0 AND {$alias}.order_id = 0)";
}

function wholesale_vi_tz_offset()
{
	return (int) wp_timezone()->getOffset(new DateTime('now', wp_timezone()));
}

/**
 * The date range picked on the admin page, with the same-length range before
 * it for comparisons.
 */
function wholesale_vi_period()
{
	$tz = wp_timezone();
	$now = new DateTimeImmutable('now', $tz);
	$today = $now->setTime(0, 0);
	$key = isset($_GET['period']) ? sanitize_key(wp_unslash($_GET['period'])) : '7d';
	$labels = array('today' => 'Today', 'yesterday' => 'Yesterday', '7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', 'custom' => 'Custom');

	switch ($key) {
		case 'today':
			$start = $today;
			$end = $now;
			break;
		case 'yesterday':
			$start = $today->modify('-1 day');
			$end = $today;
			break;
		case '30d':
			$start = $today->modify('-29 days');
			$end = $now;
			break;
		case '90d':
			$start = $today->modify('-89 days');
			$end = $now;
			break;
		case 'custom':
			$from = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_GET['from'] ?? '')), $tz);
			$to = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_GET['to'] ?? '')), $tz);
			if ($from && $to && $from <= $to) {
				$start = $from;
				$end = min($to->modify('+1 day'), $now);
				break;
			}
			// Fall through to the default range when the dates are missing.
		default:
			$key = '7d';
			$start = $today->modify('-6 days');
			$end = $now;
	}

	$length = max(1, $end->getTimestamp() - $start->getTimestamp());
	$utc = new DateTimeZone('UTC');

	return array(
		'key' => $key,
		'label' => $labels[$key],
		'labels' => $labels,
		'start' => $start,
		'end' => $end,
		'start_gmt' => $start->setTimezone($utc)->format('Y-m-d H:i:s'),
		'end_gmt' => $end->setTimezone($utc)->format('Y-m-d H:i:s'),
		'prev_start_gmt' => gmdate('Y-m-d H:i:s', $start->getTimestamp() - $length),
		'prev_end_gmt' => $start->setTimezone($utc)->format('Y-m-d H:i:s'),
		'hourly' => $length <= 2 * DAY_IN_SECONDS,
	);
}

/**
 * WHERE clause for sessions in a date range plus optional filters.
 *
 * @return array array(sql, params)
 */
function wholesale_vi_where($start_gmt, $end_gmt, $filters = array(), $alias = 's')
{
	$where = array("{$alias}.started_at >= %s", "{$alias}.started_at < %s");
	$params = array($start_gmt, $end_gmt);

	if (!empty($filters['channel']) && isset(wholesale_vi_channels()[$filters['channel']])) {
		$where[] = "{$alias}.channel = %s";
		$params[] = $filters['channel'];
	}
	if (!empty($filters['device']) && in_array($filters['device'], array('mobile', 'tablet', 'desktop'), true)) {
		$where[] = "{$alias}.device = %s";
		$params[] = $filters['device'];
	}

	return array(implode(' AND ', $where), $params);
}

function wholesale_vi_metrics_sql($alias = 's')
{
	$bounce = wholesale_vi_bounce_sql($alias);

	return "COUNT(*) AS sessions, COUNT(DISTINCT {$alias}.visitor_key) AS visitors, SUM({$alias}.channel = 'google_ads') AS ads,
		SUM({$bounce}) AS bounced, SUM({$alias}.pageviews <= 1 AND {$alias}.engaged_seconds < 10) AS quick_exits,
		COALESCE(AVG({$alias}.engaged_seconds), 0) AS avg_engaged, COALESCE(AVG({$alias}.pageviews), 0) AS avg_pages,
		SUM({$alias}.stage >= 2) AS s2, SUM({$alias}.stage >= 3) AS s3, SUM({$alias}.stage >= 4) AS s4, SUM({$alias}.stage >= 5) AS s5,
		SUM({$alias}.stage >= 6) AS s6, SUM({$alias}.order_id > 0) AS orders, SUM({$alias}.is_lead) AS leads,
		COALESCE(SUM({$alias}.revenue), 0) AS revenue, SUM({$alias}.is_new = 0) AS returning_visits";
}

function wholesale_vi_summary($start_gmt, $end_gmt, $filters = array())
{
	global $wpdb;
	list($where, $params) = wholesale_vi_where($start_gmt, $end_gmt, $filters);
	$row = $wpdb->get_row($wpdb->prepare('SELECT ' . wholesale_vi_metrics_sql() . ' FROM ' . wholesale_vi_table('sessions') . " s WHERE {$where}", $params), ARRAY_A);

	return array_map('floatval', $row ?: array());
}

/**
 * Sessions grouped by an SQL expression, with the standard metrics.
 */
function wholesale_vi_breakdown($group_sql, $start_gmt, $end_gmt, $filters = array(), $limit = 10, $extra_where = '')
{
	global $wpdb;
	list($where, $params) = wholesale_vi_where($start_gmt, $end_gmt, $filters);
	if ($extra_where) {
		$where .= ' AND ' . $extra_where;
	}
	$params[] = (int) $limit;

	return $wpdb->get_results($wpdb->prepare(
		"SELECT {$group_sql} AS label, " . wholesale_vi_metrics_sql() . ' FROM ' . wholesale_vi_table('sessions') . " s WHERE {$where} GROUP BY label ORDER BY sessions DESC LIMIT %d",
		$params
	), ARRAY_A);
}

/**
 * Event rows grouped by type and label (errors, clicks, ...).
 */
function wholesale_vi_event_breakdown($types, $start_gmt, $end_gmt, $filters = array(), $limit = 10)
{
	global $wpdb;
	list($where, $params) = wholesale_vi_where($start_gmt, $end_gmt, $filters);
	$types = array_values(array_intersect((array) $types, wholesale_vi_event_types()));
	if (!$types) {
		return array();
	}
	$params = array_merge($types, array($start_gmt, $end_gmt), $params, array((int) $limit));
	$in = implode(', ', array_fill(0, count($types), '%s'));

	return $wpdb->get_results($wpdb->prepare(
		'SELECT e.type, e.label, COUNT(*) AS hits, COUNT(DISTINCT e.session_id) AS sessions, SUM(s.order_id > 0) AS buyers, MAX(e.created_at) AS last_at
		FROM ' . wholesale_vi_table('events') . ' e JOIN ' . wholesale_vi_table('sessions') . " s ON s.id = e.session_id
		WHERE e.type IN ({$in}) AND e.created_at >= %s AND e.created_at < %s AND {$where}
		GROUP BY e.type, e.label ORDER BY sessions DESC, hits DESC LIMIT %d",
		$params
	), ARRAY_A);
}

/**
 * Forms visitors started, how many finished them, and the field most people
 * stopped at.
 */
function wholesale_vi_form_report($start_gmt, $end_gmt, $filters = array())
{
	global $wpdb;
	list($where, $params) = wholesale_vi_where($start_gmt, $end_gmt, $filters);
	$events = wholesale_vi_table('events');
	$base = array_merge(array($start_gmt, $end_gmt), $params);

	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT f.label, COUNT(DISTINCT f.session_id) AS started, COUNT(DISTINCT sub.session_id) AS submitted
		FROM {$events} f JOIN " . wholesale_vi_table('sessions') . " s ON s.id = f.session_id
		LEFT JOIN {$events} sub ON sub.session_id = f.session_id AND sub.type IN ('form_submit', 'quote_submit', 'purchase') AND (sub.label = f.label OR sub.type <> 'form_submit')
		WHERE f.type = 'form_start' AND f.created_at >= %s AND f.created_at < %s AND {$where}
		GROUP BY f.label ORDER BY started DESC LIMIT 8",
		$base
	), ARRAY_A);

	$fields = $wpdb->get_results($wpdb->prepare(
		"SELECT f.label, f.value, COUNT(*) AS n
		FROM {$events} f JOIN " . wholesale_vi_table('sessions') . " s ON s.id = f.session_id
		WHERE f.type = 'form_start' AND f.value <> '' AND f.created_at >= %s AND f.created_at < %s AND {$where}
			AND NOT EXISTS (SELECT 1 FROM {$events} x WHERE x.session_id = f.session_id AND x.type IN ('form_submit', 'quote_submit', 'purchase'))
		GROUP BY f.label, f.value ORDER BY n DESC",
		$base
	), ARRAY_A);

	$top_field = array();
	foreach ($fields as $field) {
		if (!isset($top_field[$field['label']])) {
			$top_field[$field['label']] = $field;
		}
	}
	foreach ($rows as &$row) {
		$row['left_at'] = isset($top_field[$row['label']]) ? $top_field[$row['label']]['value'] : '';
		$row['left_at_n'] = isset($top_field[$row['label']]) ? (int) $top_field[$row['label']]['n'] : 0;
	}

	return $rows;
}

/**
 * Sessions, ad sessions and conversions per day (or per hour for short ranges).
 */
function wholesale_vi_series($period, $filters = array())
{
	global $wpdb;
	list($where, $params) = wholesale_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$offset = wholesale_vi_tz_offset();
	$bucket = $period['hourly'] ? "DATE_FORMAT(DATE_ADD(s.started_at, INTERVAL {$offset} SECOND), '%%Y-%%m-%%d %%H:00')" : "DATE(DATE_ADD(s.started_at, INTERVAL {$offset} SECOND))";

	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT {$bucket} AS bucket, COUNT(*) AS sessions, SUM(s.channel = 'google_ads') AS ads, SUM(s.order_id > 0 OR s.is_lead = 1) AS conversions
		FROM " . wholesale_vi_table('sessions') . " s WHERE {$where} GROUP BY bucket",
		$params
	), OBJECT_K);

	$series = array();
	$step = $period['hourly'] ? '+1 hour' : '+1 day';
	$cursor = $period['hourly'] ? $period['start'] : $period['start']->setTime(0, 0);
	$guard = 0;
	while ($cursor < $period['end'] && $guard++ < 400) {
		$key = $period['hourly'] ? $cursor->format('Y-m-d H:00') : $cursor->format('Y-m-d');
		$row = $rows[$key] ?? null;
		$series[] = array(
			'label' => $period['hourly'] ? $cursor->format('ga') : $cursor->format('M j'),
			'sessions' => $row ? (int) $row->sessions : 0,
			'ads' => $row ? (int) $row->ads : 0,
			'conversions' => $row ? (int) $row->conversions : 0,
		);
		$cursor = $cursor->modify($step);
	}

	return $series;
}

/**
 * IP addresses with several ad clicks in the range: possible click fraud or
 * a competitor clicking ads.
 */
function wholesale_vi_repeat_ad_clickers($start_gmt, $end_gmt, $min = 3)
{
	global $wpdb;

	return $wpdb->get_results($wpdb->prepare(
		'SELECT ip, COUNT(*) AS clicks, COUNT(DISTINCT visitor_key) AS devices, SUM(order_id > 0) AS orders, SUM(is_lead) AS leads,
			COALESCE(AVG(engaged_seconds), 0) AS avg_engaged, MAX(last_seen) AS last_seen, MAX(device) AS device, MAX(timezone) AS timezone
		FROM ' . wholesale_vi_table('sessions') . " WHERE channel = 'google_ads' AND ip <> '' AND started_at >= %s AND started_at < %s
		GROUP BY ip HAVING clicks >= %d ORDER BY clicks DESC LIMIT 15",
		$start_gmt,
		$end_gmt,
		(int) $min
	), ARRAY_A);
}

/**
 * Plain-language findings about why visitors are not buying, worked out from
 * the numbers. Each is array(level, title, detail).
 */
function wholesale_vi_findings($m, $context)
{
	$findings = array();
	$sessions = (int) ($m['sessions'] ?? 0);
	$who = !empty($context['ads']) ? 'Google Ads visitors' : 'visitors';
	if ($sessions < 10) {
		return array(array('info', 'Not enough visits yet', sprintf('Only %d visit(s) in this range. Findings appear once there are at least 10 — check back after a few days of traffic.', $sessions)));
	}

	$pct = static function ($part, $whole) {
		return $whole > 0 ? round(100 * $part / $whole) : 0;
	};

	$quick = $pct($m['quick_exits'], $sessions);
	if ($quick >= 45) {
		$findings[] = array('critical', sprintf('%d%% of %s leave within 10 seconds without clicking anything', $quick, $who),
			'They saw the landing page and decided right away it is not what they searched for. Check that the ad text, keyword and landing page match (same product, same price point), that the page loads fast on phones, and that the offer and phone number are visible without scrolling. Look at "Landing pages" below for the worst pages, and at your Search Terms report in Google Ads for irrelevant searches to add as negative keywords.');
	} elseif ($quick >= 30) {
		$findings[] = array('warning', sprintf('%d%% of %s leave within 10 seconds', $quick, $who), 'A healthy rate for paid search is under 30%. Compare landing pages below and tighten keyword match types.');
	}

	$product_rate = $pct($m['s2'], $sessions);
	if ($product_rate < 35 && empty($context['landing_is_product'])) {
		$findings[] = array('warning', sprintf('Only %d%% of %s ever open a product page', $product_rate, $who),
			'Most visitors never get to see prices or the sign builder. Send ads straight to the matching product page, or make the main button on the landing page lead to it.');
	}

	if ($m['s3'] > 0 && $m['s4'] < $m['s3'] * 0.25) {
		$findings[] = array('warning', sprintf('%d visitors configured a sign or saw a price, but only %d added it to the cart', $m['s3'], $m['s4']),
			'Price is the most likely reason they stop here. Look at the "Clicks" and "Problems visitors hit" sections for pricing errors, and consider showing a starting price or a "Get a quote" option next to Add to cart for high-ticket signs.');
	}

	if ($m['s4'] >= 3 && $m['s5'] < $m['s4'] * 0.5) {
		$findings[] = array('warning', sprintf('%d%% of carts never reach checkout', 100 - $pct($m['s5'], $m['s4'])),
			'Visitors add a sign to the cart and stop. Usual causes: a surprise shipping cost, turnaround time, or wanting to compare prices first. Make shipping and delivery dates clear in the cart.');
	}

	if ($m['s5'] >= 3 && $m['s6'] < $m['s5'] * 0.5) {
		$findings[] = array('critical', sprintf('%d visitors opened checkout but %d never tried to pay', $m['s5'], $m['s5'] - $m['s6']),
			'See "Form abandonment" for the exact field where they stop. Long forms, forced account creation and missing trust signals (secure payment badges, reviews, phone number) are the usual causes.');
	}

	if (!empty($context['payment_errors'])) {
		$findings[] = array('critical', sprintf('%d checkout or payment errors were shown to visitors', $context['payment_errors']),
			'These are visitors who wanted to buy and were stopped. Read the exact messages under "Problems visitors hit" and open the affected visits.');
	}

	if (!empty($context['mobile']) && $context['mobile']['sessions'] >= 10 && !empty($context['desktop']) && $context['desktop']['sessions'] >= 5) {
		$mobile_conv = $pct($context['mobile']['orders'] + $context['mobile']['leads'], $context['mobile']['sessions']);
		$desktop_conv = $pct($context['desktop']['orders'] + $context['desktop']['leads'], $context['desktop']['sessions']);
		$mobile_share = $pct($context['mobile']['sessions'], $sessions);
		if ($mobile_share >= 50 && $mobile_conv < $desktop_conv) {
			$findings[] = array('warning', sprintf('%d%% of %s are on phones, but phones convert at %d%% vs %d%% on desktop', $mobile_share, $who, $mobile_conv, $desktop_conv),
				'Test the landing page and sign builder on a phone. If the builder is hard to use on small screens, lower mobile bids in Google Ads or send mobile clicks to a quote form / click-to-call page.');
		}
	}

	if (!empty($context['repeat_clickers'])) {
		$findings[] = array('critical', sprintf('%d IP address(es) clicked your ads 3 or more times', count($context['repeat_clickers'])),
			'Repeated clicks from the same address with no purchase are often competitors or click fraud. Check them in "Repeat ad clickers" and add them under Google Ads → Settings → IP exclusions.');
	}

	if (!empty($context['foreign']) && $pct($context['foreign'], $sessions) >= 10) {
		$findings[] = array('warning', sprintf('%d%% of %s are in time zones outside the US', $pct($context['foreign'], $sessions), $who),
			'Your ads may be shown abroad. In Google Ads → Locations, set "Presence: people in or regularly in your targeted locations" instead of "Presence or interest".');
	}

	if (!empty($context['not_found'])) {
		$findings[] = array('critical', sprintf('%d visit(s) landed on or hit a "page not found" page', $context['not_found']),
			'Fix or redirect the broken links listed under "Problems visitors hit", and check the final URLs of your ads.');
	}

	if (!empty($context['ads']) && empty($context['has_keywords'])) {
		$findings[] = array('info', 'Keywords and campaigns are not being passed to the site',
			'Add the tracking suffix shown under Settings to your Google Ads account so every visit shows which campaign and keyword it came from. You will then see which keywords waste money.');
	}

	if ($m['leads'] > 0 && $m['orders'] == 0) {
		$findings[] = array('info', sprintf('%d visitor(s) called, emailed or requested a quote, but none bought online', $m['leads']),
			'Many sign buyers prefer to talk before ordering. Import calls and quote requests into Google Ads as conversions so Smart Bidding optimizes for them, not only online sales.');
	}

	if (!$findings) {
		$findings[] = array('success', 'No major problem found in this range', 'Keep an eye on the funnel and the visitor journeys for patterns.');
	}

	return $findings;
}

/* ---------------------------------------------------------------------------
 * Admin page
 * ------------------------------------------------------------------------ */

function wholesale_vi_admin_menu()
{
	add_menu_page('Visitor Insights', 'Visitor Insights', 'manage_options', 'wholesale-insights', 'wholesale_vi_admin_page', 'dashicons-chart-area', 3);
	add_submenu_page('wholesale-insights', 'Overview', 'Overview', 'manage_options', 'wholesale-insights', 'wholesale_vi_admin_page');
	add_submenu_page('wholesale-insights', 'Google Ads Insights', 'Google Ads', 'manage_options', 'wholesale-insights-ads', 'wholesale_vi_admin_page');
	add_submenu_page('wholesale-insights', 'Visitors', 'Visitors', 'manage_options', 'wholesale-insights-visitors', 'wholesale_vi_admin_page');
	add_submenu_page('wholesale-insights', 'Live Visitors', 'Live', 'manage_options', 'wholesale-insights-live', 'wholesale_vi_admin_page');
	add_submenu_page('wholesale-insights', 'Visitor Insights Settings', 'Settings', 'manage_options', 'wholesale-insights-settings', 'wholesale_vi_admin_page');
}
add_action('admin_menu', 'wholesale_vi_admin_menu');

function wholesale_vi_admin_assets($hook)
{
	if (false === strpos($hook, 'wholesale-insights')) {
		return;
	}

	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('wholesale-vi-admin', $uri . '/css/visitor-insights.css', array(), (string) filemtime($dir . '/css/visitor-insights.css'));
	wp_enqueue_script('wholesale-vi-admin', $uri . '/js/visitor-insights-admin.js', array(), (string) filemtime($dir . '/js/visitor-insights-admin.js'), true);
	wp_localize_script('wholesale-vi-admin', 'wholesaleVIAdmin', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('wholesale_vi_live'),
		'interval' => 5000,
	));
}
add_action('admin_enqueue_scripts', 'wholesale_vi_admin_assets');

function wholesale_vi_tabs()
{
	return array(
		'wholesale-insights' => array('Overview', 'dashboard'),
		'wholesale-insights-ads' => array('Google Ads', 'megaphone'),
		'wholesale-insights-visitors' => array('Visitors', 'groups'),
		'wholesale-insights-live' => array('Live', 'visibility'),
		'wholesale-insights-settings' => array('Settings', 'admin-generic'),
	);
}

function wholesale_vi_url($page, $args = array())
{
	return add_query_arg(array_merge(array('page' => $page), $args), admin_url('admin.php'));
}

/* Formatting helpers --------------------------------------------------- */

function wholesale_vi_duration($seconds)
{
	$seconds = (int) round($seconds);
	if ($seconds < 60) {
		return $seconds . 's';
	}
	if ($seconds < 3600) {
		return floor($seconds / 60) . 'm ' . str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT) . 's';
	}

	return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
}

function wholesale_vi_pct($part, $whole, $decimals = 0)
{
	return $whole > 0 ? number_format_i18n(100 * $part / $whole, $decimals) . '%' : '—';
}

function wholesale_vi_money($amount)
{
	return '$' . number_format_i18n((float) $amount, 2);
}

function wholesale_vi_date($gmt, $format = 'M j, g:i a')
{
	return $gmt ? get_date_from_gmt($gmt, $format) : '';
}

function wholesale_vi_ago($gmt)
{
	return human_time_diff(strtotime($gmt . ' UTC'), time()) . ' ago';
}

function wholesale_vi_site_link($path)
{
	$origin = preg_replace('#^(https?://[^/]+).*$#', '$1', home_url());

	return $origin . $path;
}

function wholesale_vi_short_path($path, $length = 48)
{
	$path = rawurldecode((string) $path);
	$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
	if ($home_path && '/' !== $home_path && 0 === strpos($path, $home_path)) {
		$path = '/' . substr($path, strlen($home_path));
	}

	return mb_strlen($path) > $length ? mb_substr($path, 0, $length - 1) . '…' : $path;
}

function wholesale_vi_channel_badge($channel)
{
	$labels = wholesale_vi_channels();

	return sprintf('<span class="wvi-channel is-%s">%s</span>', esc_attr($channel), esc_html($labels[$channel] ?? ucfirst($channel)));
}

function wholesale_vi_device_icon($device)
{
	$icons = array('mobile' => 'smartphone', 'tablet' => 'tablet', 'desktop' => 'desktop');

	return sprintf('<span class="dashicons dashicons-%s" title="%s"></span>', esc_attr($icons[$device] ?? 'desktop'), esc_attr(ucfirst($device)));
}

function wholesale_vi_stage_pips($stage)
{
	$html = '<span class="wvi-pips" title="' . esc_attr(wholesale_vi_stages()[(int) $stage] ?? '') . '">';
	for ($i = 1; $i <= 7; $i++) {
		$html .= '<i class="' . ($i <= $stage ? 'is-on' : '') . ($i === 7 && $stage >= 7 ? ' is-won' : '') . '"></i>';
	}

	return $html . '</span>';
}

function wholesale_vi_outcome($session)
{
	$session = (object) $session;
	if ((int) $session->order_id > 0) {
		return '<span class="wvi-badge is-green">Purchased ' . esc_html(wholesale_vi_money($session->revenue)) . '</span>';
	}
	if ((int) $session->is_lead) {
		return '<span class="wvi-badge is-blue">Lead</span>';
	}
	if ((int) $session->stage >= 4) {
		return '<span class="wvi-badge is-amber">Abandoned ' . ((int) $session->stage >= 5 ? 'checkout' : 'cart') . '</span>';
	}
	if ((int) $session->pageviews <= 1 && (int) $session->engaged_seconds < 10) {
		return '<span class="wvi-badge is-red">Bounced</span>';
	}

	return '<span class="wvi-badge">Browsed</span>';
}

function wholesale_vi_location($session)
{
	$session = (object) $session;
	$parts = array();
	if ($session->country) {
		$parts[] = $session->country;
	}
	if ($session->timezone) {
		$parts[] = str_replace('_', ' ', $session->timezone);
	}

	return implode(' · ', $parts);
}

/**
 * Whether a browser time zone is outside the United States.
 */
function wholesale_vi_is_foreign_tz($tz)
{
	if ('' === $tz) {
		return false;
	}

	return !preg_match('#^(America/(New_York|Detroit|Chicago|Denver|Phoenix|Los_Angeles|Anchorage|Juneau|Sitka|Nome|Adak|Boise|Indiana/.+|Kentucky/.+|North_Dakota/.+|Menominee|Metlakatla|Yakutat|Puerto_Rico)|Pacific/Honolulu|US/.+)$#', $tz);
}

/* Page shell ------------------------------------------------------------ */

function wholesale_vi_admin_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'wholesale-insights';
	$tabs = wholesale_vi_tabs();
	if (!isset($tabs[$page])) {
		$page = 'wholesale-insights';
	}
	$notice = isset($_GET['vi_notice']) ? sanitize_key(wp_unslash($_GET['vi_notice'])) : '';
	?>
	<div class="wrap wvi">
		<header class="wvi-header">
			<div>
				<h1 class="wvi-title"><span class="dashicons dashicons-chart-area"></span> Visitor Insights</h1>
				<p class="wvi-subtitle">Who visits the site, where they come from, what they do, and where they stop before buying.</p>
			</div>
			<?php if (!wholesale_vi_setting('enabled')) : ?>
				<span class="wvi-status is-off"><span class="wvi-dot"></span> Tracking is paused</span>
			<?php else : ?>
				<span class="wvi-status" data-live-status>
					<span class="wvi-dot"></span>
					<span><strong data-online><?php echo esc_html(wholesale_vi_live_count()); ?></strong> online now</span>
					<?php if ('wholesale-insights-settings' !== $page) : ?>
						<span class="wvi-status-sep"></span>
						<span class="wvi-updated" data-updated>Live</span>
						<button type="button" class="wvi-live-toggle" data-live-toggle title="Pause live updates" aria-pressed="false"><span class="dashicons dashicons-controls-pause"></span></button>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</header>

		<nav class="wvi-tabs">
			<?php foreach ($tabs as $slug => $tab) : ?>
				<a href="<?php echo esc_url(wholesale_vi_url($slug)); ?>" class="<?php echo $slug === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-<?php echo esc_attr($tab[1]); ?>"></span> <?php echo esc_html($tab[0]); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ('saved' === $notice) : ?>
			<div class="wvi-alert is-success">Settings saved.</div>
		<?php elseif ('purged' === $notice) : ?>
			<div class="wvi-alert is-success">All visitor data was deleted.</div>
		<?php endif; ?>

		<?php if ('wholesale-insights-settings' === $page) : ?>
			<?php wholesale_vi_render_settings(); ?>
		<?php else : ?>
			<div class="wvi-body" data-live-body data-version="<?php echo esc_attr(wholesale_vi_data_version($page)); ?>">
				<?php wholesale_vi_render_body($page); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The report, list or visit for a tab. Also rendered by the live-update
 * request, which swaps it in without reloading the page.
 */
function wholesale_vi_render_body($page)
{
	switch ($page) {
		case 'wholesale-insights-ads':
			wholesale_vi_render_report(true);
			break;
		case 'wholesale-insights-visitors':
			if (!empty($_GET['session'])) {
				wholesale_vi_render_session(absint($_GET['session']));
			} else {
				wholesale_vi_render_visitors();
			}
			break;
		case 'wholesale-insights-live':
			wholesale_vi_render_live();
			break;
		default:
			wholesale_vi_render_report(false);
	}
}

/**
 * A cheap fingerprint of the data a tab shows. The live-update request only
 * re-renders the tab when it changes.
 */
function wholesale_vi_data_version($page)
{
	global $wpdb;
	$sessions = wholesale_vi_table('sessions');

	if ('wholesale-insights-visitors' === $page && !empty($_GET['session'])) {
		$row = $wpdb->get_row($wpdb->prepare("SELECT last_seen, events FROM {$sessions} WHERE id = %d", absint($_GET['session'])));
		$version = $row ? $row->last_seen . ':' . $row->events : 'gone';
	} else {
		// A range that ended before now never changes.
		$period = wholesale_vi_period();
		if ('wholesale-insights-live' !== $page && $period['end']->getTimestamp() < time() - MINUTE_IN_SECONDS) {
			return 'closed:' . $period['start_gmt'];
		}
		$version = (string) $wpdb->get_var("SELECT CONCAT(COUNT(*), ':', COALESCE(MAX(last_seen), ''), ':', COALESCE(SUM(events), 0)) FROM {$sessions} WHERE last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)");
	}

	// "x minutes ago" labels and who counts as online move with the clock.
	return md5($version . ':' . floor(time() / 60));
}

/**
 * Live updates: the admin page sends its query string and the version it
 * shows; gets back the re-rendered tab when the data changed.
 */
function wholesale_vi_ajax_refresh()
{
	check_ajax_referer('wholesale_vi_live', 'nonce');
	if (!current_user_can('manage_options')) {
		wp_send_json_error(null, 403);
	}

	parse_str((string) wp_unslash($_POST['query'] ?? ''), $query);
	$_GET = is_array($query) ? $query : array();
	$page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
	if (!isset(wholesale_vi_tabs()[$page]) || 'wholesale-insights-settings' === $page) {
		wp_send_json_error(null, 400);
	}

	$response = array('online' => wholesale_vi_live_count(), 'changed' => false);
	$version = wholesale_vi_data_version($page);
	if ($version !== (string) wp_unslash($_POST['version'] ?? '')) {
		ob_start();
		wholesale_vi_render_body($page);
		$response['html'] = ob_get_clean();
		$response['version'] = $version;
		$response['changed'] = true;
	}

	wp_send_json_success($response);
}
add_action('wp_ajax_wholesale_vi_refresh', 'wholesale_vi_ajax_refresh');

/**
 * Period picker and filters, as a GET form that keeps the current page.
 */
function wholesale_vi_render_toolbar($period, $page, $filters = array(), $show = array('channel', 'device'))
{
	?>
	<form class="wvi-toolbar wvi-card" method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
		<div class="wvi-segments">
			<?php foreach (array('today', 'yesterday', '7d', '30d', '90d') as $key) : ?>
				<a href="<?php echo esc_url(wholesale_vi_url($page, array_filter(array_merge($filters, array('period' => $key))))); ?>" class="<?php echo $period['key'] === $key ? 'is-active' : ''; ?>"><?php echo esc_html($period['labels'][$key]); ?></a>
			<?php endforeach; ?>
		</div>
		<div class="wvi-toolbar-right">
			<label class="wvi-daterange">
				<input type="date" name="from" value="<?php echo esc_attr($period['start']->format('Y-m-d')); ?>" aria-label="From">
				<span>–</span>
				<input type="date" name="to" value="<?php echo esc_attr($period['end']->modify('-1 second')->format('Y-m-d')); ?>" aria-label="To">
				<input type="hidden" name="period" value="custom">
			</label>
			<?php if (in_array('channel', $show, true)) : ?>
				<select name="channel" aria-label="Traffic source">
					<option value="">All sources</option>
					<?php foreach (wholesale_vi_channels() as $key => $label) : ?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($filters['channel'] ?? '', $key); ?>><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
			<?php if (in_array('device', $show, true)) : ?>
				<select name="device" aria-label="Device">
					<option value="">All devices</option>
					<?php foreach (array('mobile' => 'Mobile', 'tablet' => 'Tablet', 'desktop' => 'Desktop') as $key => $label) : ?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($filters['device'] ?? '', $key); ?>><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
			<?php do_action('wholesale_vi_toolbar_fields', $page, $filters); ?>
			<button class="button button-primary">Apply</button>
		</div>
	</form>
	<?php
}

function wholesale_vi_request_filters()
{
	return array(
		'channel' => isset($_GET['channel']) ? sanitize_key(wp_unslash($_GET['channel'])) : '',
		'device' => isset($_GET['device']) ? sanitize_key(wp_unslash($_GET['device'])) : '',
	);
}

function wholesale_vi_change($now, $before, $invert = false)
{
	if ($before <= 0) {
		return '';
	}
	$change = ($now - $before) / $before * 100;
	if (abs($change) < 0.5) {
		return '<span class="wvi-delta">no change</span>';
	}
	$good = $invert ? $change < 0 : $change > 0;

	return sprintf('<span class="wvi-delta is-%s">%s%s%% vs previous</span>', $good ? 'up' : 'down', $change > 0 ? '▲ ' : '▼ ', number_format_i18n(abs($change), 0));
}

function wholesale_vi_stat($label, $value, $icon, $color, $delta = '', $hint = '')
{
	?>
	<div class="wvi-stat" <?php echo $hint ? 'title="' . esc_attr($hint) . '"' : ''; ?>>
		<span class="wvi-stat-icon is-<?php echo esc_attr($color); ?>"><span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span></span>
		<span>
			<span class="wvi-stat-value"><?php echo esc_html($value); ?></span>
			<span class="wvi-stat-label"><?php echo esc_html($label); ?></span>
			<?php echo $delta; // phpcs:ignore WordPress.Security.EscapeOutput -- built from numbers. ?>
		</span>
	</div>
	<?php
}

function wholesale_vi_render_funnel($m, $title = 'Purchase funnel')
{
	$stages = wholesale_vi_stages();
	$counts = array(1 => $m['sessions'], 2 => $m['s2'], 3 => $m['s3'], 4 => $m['s4'], 5 => $m['s5'], 6 => $m['s6'], 7 => $m['orders']);
	$max = max(1, $counts[1]);
	$worst = 0;
	$worst_drop = -1;
	for ($i = 2; $i <= 7; $i++) {
		$drop = $counts[$i - 1] > 0 ? ($counts[$i - 1] - $counts[$i]) / $counts[$i - 1] : 0;
		if ($counts[$i - 1] >= 3 && $drop > $worst_drop) {
			$worst_drop = $drop;
			$worst = $i;
		}
	}
	?>
	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2><?php echo esc_html($title); ?></h2>
			<span class="wvi-muted">How far visits got. The red step loses the most people.</span>
		</div>
		<ol class="wvi-funnel">
			<?php foreach ($stages as $i => $label) : ?>
				<?php $drop = $i > 1 && $counts[$i - 1] > 0 ? 100 * ($counts[$i - 1] - $counts[$i]) / $counts[$i - 1] : 0; ?>
				<li class="<?php echo $i === $worst ? 'is-worst' : ''; ?>">
					<span class="wvi-funnel-label"><?php echo esc_html($label); ?></span>
					<span class="wvi-funnel-bar"><i style="width:<?php echo esc_attr(max(0.6, 100 * $counts[$i] / $max)); ?>%"></i></span>
					<span class="wvi-funnel-num"><strong><?php echo esc_html(number_format_i18n($counts[$i])); ?></strong> <?php echo esc_html(wholesale_vi_pct($counts[$i], $counts[1], 1)); ?></span>
					<span class="wvi-funnel-drop"><?php echo $i > 1 && $counts[$i - 1] > 0 ? esc_html('−' . number_format_i18n($drop, 0) . '% lost') : ''; ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
		<p class="wvi-muted wvi-funnel-note">Leads (calls, emails and quote requests) are counted separately: <strong><?php echo esc_html(number_format_i18n($m['leads'])); ?></strong>.</p>
	</section>
	<?php
}

/**
 * Table of grouped sessions with the standard metric columns.
 */
function wholesale_vi_render_breakdown($title, $rows, $label_cb, $hint = '', $columns = array('sessions', 'bounce', 'time', 'cart', 'conv'))
{
	?>
	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2><?php echo esc_html($title); ?></h2>
			<?php if ($hint) : ?><span class="wvi-muted"><?php echo esc_html($hint); ?></span><?php endif; ?>
		</div>
		<?php if (!$rows) : ?>
			<p class="wvi-empty">No data in this range.</p>
		<?php else : ?>
			<div class="wvi-table-wrap">
				<table class="wvi-table">
					<thead>
						<tr>
							<th></th>
							<?php if (in_array('sessions', $columns, true)) : ?><th class="num">Visits</th><?php endif; ?>
							<?php if (in_array('bounce', $columns, true)) : ?><th class="num" title="One page, under 10 seconds, no action">Bounce</th><?php endif; ?>
							<?php if (in_array('time', $columns, true)) : ?><th class="num">Avg. time</th><?php endif; ?>
							<?php if (in_array('cart', $columns, true)) : ?><th class="num">Cart</th><?php endif; ?>
							<?php if (in_array('conv', $columns, true)) : ?><th class="num">Leads</th><th class="num">Orders</th><th class="num" title="Orders + leads per visit">Conv.</th><?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $row) : ?>
							<?php $bounce = $row['sessions'] > 0 ? $row['bounced'] / $row['sessions'] : 0; ?>
							<tr>
								<td class="wvi-cell-label"><?php echo $label_cb($row); // phpcs:ignore WordPress.Security.EscapeOutput -- callbacks escape. ?></td>
								<?php if (in_array('sessions', $columns, true)) : ?><td class="num"><?php echo esc_html(number_format_i18n($row['sessions'])); ?></td><?php endif; ?>
								<?php if (in_array('bounce', $columns, true)) : ?><td class="num <?php echo $bounce >= 0.6 && $row['sessions'] >= 5 ? 'is-bad' : ''; ?>"><?php echo esc_html(wholesale_vi_pct($row['bounced'], $row['sessions'])); ?></td><?php endif; ?>
								<?php if (in_array('time', $columns, true)) : ?><td class="num"><?php echo esc_html(wholesale_vi_duration($row['avg_engaged'])); ?></td><?php endif; ?>
								<?php if (in_array('cart', $columns, true)) : ?><td class="num"><?php echo esc_html(number_format_i18n($row['s4'])); ?></td><?php endif; ?>
								<?php if (in_array('conv', $columns, true)) : ?>
									<td class="num"><?php echo esc_html(number_format_i18n($row['leads'])); ?></td>
									<td class="num"><?php echo esc_html(number_format_i18n($row['orders'])); ?></td>
									<td class="num"><strong><?php echo esc_html(wholesale_vi_pct($row['orders'] + $row['leads'], $row['sessions'], 1)); ?></strong></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

function wholesale_vi_event_label($type)
{
	$labels = array(
		'price_error' => 'Pricing error',
		'upload_error' => 'Upload failed',
		'cart_error' => 'Add to cart failed',
		'checkout_error' => 'Checkout error',
		'payment_error' => 'Payment failed',
		'js_error' => 'Page script error',
		'rage_click' => 'Rage clicks',
		'click' => 'Click',
		'call_click' => 'Call',
		'email_click' => 'Email',
		'pageview' => 'Page not found',
	);

	return $labels[$type] ?? $type;
}

/**
 * Everything the Overview and Google Ads reports show for a range, shared by
 * the admin page and the exported report.
 */
function wholesale_vi_report_data($period, $filters, $ads)
{
	global $wpdb;
	$start = $period['start_gmt'];
	$end = $period['end_gmt'];

	$m = wholesale_vi_summary($start, $end, $filters);
	$prev = wholesale_vi_summary($period['prev_start_gmt'], $period['prev_end_gmt'], $filters);
	$series = wholesale_vi_series($period, $filters);
	$devices = wholesale_vi_breakdown('s.device', $start, $end, $filters, 3);
	$by_device = array();
	foreach ($devices as $row) {
		$by_device[$row['label']] = $row;
	}

	$problem_types = array('checkout_error', 'payment_error', 'cart_error', 'price_error', 'upload_error', 'js_error', 'rage_click');
	$problems = wholesale_vi_event_breakdown($problem_types, $start, $end, $filters, 12);
	list($where, $params) = wholesale_vi_where($start, $end, $filters);
	$not_found = $wpdb->get_results($wpdb->prepare(
		'SELECT e.url AS label, COUNT(*) AS hits, COUNT(DISTINCT e.session_id) AS sessions, MAX(e.created_at) AS last_at FROM ' . wholesale_vi_table('events') . ' e JOIN ' . wholesale_vi_table('sessions') . " s ON s.id = e.session_id
		WHERE e.type = 'pageview' AND e.value = '404' AND {$where} GROUP BY e.url ORDER BY sessions DESC LIMIT 8",
		$params
	), ARRAY_A);

	$payment_errors = 0;
	foreach ($problems as $row) {
		if (in_array($row['type'], array('checkout_error', 'payment_error', 'cart_error'), true)) {
			$payment_errors += (int) $row['hits'];
		}
	}

	$timezones = wholesale_vi_breakdown('s.timezone', $start, $end, $filters, 50);
	$foreign = 0;
	foreach ($timezones as $row) {
		if (wholesale_vi_is_foreign_tz($row['label'])) {
			$foreign += (int) $row['sessions'];
		}
	}

	$repeat = $ads ? wholesale_vi_repeat_ad_clickers($start, $end) : array();
	$keywords = $ads ? wholesale_vi_breakdown("IF(s.term = '', '(not set)', s.term)", $start, $end, $filters, 15) : array();
	$campaigns = $ads ? wholesale_vi_breakdown("IF(s.campaign = '', '(not set)', s.campaign)", $start, $end, $filters, 10) : array();
	$has_keywords = false;
	foreach (array_merge($keywords, $campaigns) as $row) {
		if ('(not set)' !== $row['label']) {
			$has_keywords = true;
			break;
		}
	}

	$landing_types = wholesale_vi_breakdown('s.landing_type', $start, $end, $filters, 1);
	$findings = wholesale_vi_findings($m, array(
		'ads' => $ads,
		'payment_errors' => $payment_errors,
		'mobile' => $by_device['mobile'] ?? null,
		'desktop' => $by_device['desktop'] ?? null,
		'repeat_clickers' => $repeat,
		'foreign' => $foreign,
		'not_found' => array_sum(array_column($not_found, 'sessions')),
		'has_keywords' => $has_keywords,
		'landing_is_product' => $landing_types && in_array($landing_types[0]['label'], array('product', 'builder'), true),
	));

	$hours = array_fill(0, 24, array('sessions' => 0, 'conversions' => 0));
	foreach (wholesale_vi_breakdown('HOUR(DATE_ADD(s.started_at, INTERVAL ' . wholesale_vi_tz_offset() . ' SECOND))', $start, $end, $filters, 24) as $row) {
		$hours[(int) $row['label']] = array('sessions' => (int) $row['sessions'], 'conversions' => (int) ($row['orders'] + $row['leads']));
	}

	return array(
		'summary' => $m,
		'previous' => $prev,
		'series' => $series,
		'devices' => $devices,
		'problems' => $problems,
		'not_found' => $not_found,
		'timezones' => $timezones,
		'repeat_clickers' => $repeat,
		'keywords' => $keywords,
		'campaigns' => $campaigns,
		'findings' => $findings,
		'hours' => $hours,
		'landing' => wholesale_vi_breakdown("SUBSTRING_INDEX(s.landing_url, '?', 1)", $start, $end, $filters, 10),
		'exits' => wholesale_vi_breakdown("SUBSTRING_INDEX(s.exit_url, '?', 1)", $start, $end, $filters, 10, 's.order_id = 0'),
		'forms' => wholesale_vi_form_report($start, $end, $filters),
		'clicks' => wholesale_vi_event_breakdown(array('click', 'call_click', 'email_click'), $start, $end, $filters, 12),
		'channels' => wholesale_vi_breakdown('s.channel', $start, $end, $filters, 10),
		'browsers' => wholesale_vi_breakdown('s.browser', $start, $end, $filters, 8),
		'sources' => wholesale_vi_breakdown("IF(s.source = '', '(direct)', s.source)", $start, $end, $filters, 10),
	);
}

/**
 * Overview and Google Ads reports share one layout; the Ads one is filtered
 * to ad clicks and adds keyword, campaign and click-fraud sections.
 */
function wholesale_vi_render_report($ads)
{
	global $wpdb;
	$page = $ads ? 'wholesale-insights-ads' : 'wholesale-insights';
	$period = wholesale_vi_period();
	$filters = wholesale_vi_request_filters();
	if ($ads) {
		$filters['channel'] = 'google_ads';
	}
	$start = $period['start_gmt'];
	$end = $period['end_gmt'];

	$report = wholesale_vi_report_data($period, $filters, $ads);
	$m = $report['summary'];
	$prev = $report['previous'];
	$series = $report['series'];
	$devices = $report['devices'];
	$problems = $report['problems'];
	$not_found = $report['not_found'];
	$timezones = $report['timezones'];
	$repeat = $report['repeat_clickers'];
	$keywords = $report['keywords'];
	$campaigns = $report['campaigns'];
	$findings = $report['findings'];

	wholesale_vi_render_toolbar($period, $page, $ads ? array('device' => $filters['device']) : $filters, $ads ? array('device') : array('channel', 'device'));
	wholesale_vi_render_export_bar($period, $ads ? array('device' => $filters['device']) : $filters);

	$conversions = $m['orders'] + $m['leads'];
	$prev_conversions = ($prev['orders'] ?? 0) + ($prev['leads'] ?? 0);
	?>
	<div class="wvi-stats">
		<?php
		wholesale_vi_stat($ads ? 'Ad clicks (visits)' : 'Visits', number_format_i18n($m['sessions']), $ads ? 'megaphone' : 'chart-line', 'blue', wholesale_vi_change($m['sessions'], $prev['sessions'] ?? 0));
		wholesale_vi_stat('Unique visitors', number_format_i18n($m['visitors']), 'groups', 'purple', wholesale_vi_change($m['visitors'], $prev['visitors'] ?? 0));
		wholesale_vi_stat('Bounce rate', wholesale_vi_pct($m['bounced'], $m['sessions']), 'migrate', 'red', wholesale_vi_change($m['sessions'] ? $m['bounced'] / $m['sessions'] : 0, !empty($prev['sessions']) ? $prev['bounced'] / $prev['sessions'] : 0, true), 'Visits with one page, under 10 seconds and no action');
		wholesale_vi_stat('Avg. engaged time', wholesale_vi_duration($m['avg_engaged']), 'clock', 'amber', wholesale_vi_change($m['avg_engaged'], $prev['avg_engaged'] ?? 0), 'Time with the page open and in use');
		wholesale_vi_stat('Added to cart', number_format_i18n($m['s4']), 'cart', 'teal', wholesale_vi_change($m['s4'], $prev['s4'] ?? 0));
		wholesale_vi_stat('Leads (calls, quotes)', number_format_i18n($m['leads']), 'phone', 'blue', wholesale_vi_change($m['leads'], $prev['leads'] ?? 0));
		wholesale_vi_stat('Orders · ' . wholesale_vi_money($m['revenue']), number_format_i18n($m['orders']), 'yes-alt', 'green', wholesale_vi_change($m['orders'], $prev['orders'] ?? 0));
		wholesale_vi_stat('Conversion rate', wholesale_vi_pct($conversions, $m['sessions'], 1), 'performance', 'green', wholesale_vi_change($m['sessions'] ? $conversions / $m['sessions'] : 0, !empty($prev['sessions']) ? $prev_conversions / $prev['sessions'] : 0), 'Orders + leads per visit');
		?>
	</div>

	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2><?php echo $ads ? 'Why Google Ads visitors are not buying' : 'What is stopping visitors from buying'; ?></h2>
			<span class="wvi-muted">Worked out from this range's visits.</span>
		</div>
		<ul class="wvi-findings">
			<?php foreach ($findings as $finding) : ?>
				<li class="is-<?php echo esc_attr($finding[0]); ?>">
					<strong><?php echo esc_html($finding[1]); ?></strong>
					<p><?php echo esc_html($finding[2]); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<div class="wvi-grid">
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head">
				<h2>Traffic</h2>
				<span class="wvi-legend"><i class="is-all"></i> Visits <?php if (!$ads) : ?><i class="is-ads"></i> Google Ads<?php endif; ?> <i class="is-conv"></i> Leads + orders</span>
			</div>
			<div class="wvi-chart" data-series="<?php echo esc_attr(wp_json_encode($series)); ?>" data-ads="<?php echo $ads ? '0' : '1'; ?>"></div>
		</section>
		<?php wholesale_vi_render_funnel($m, $ads ? 'Google Ads funnel' : 'Purchase funnel'); ?>
	</div>

	<?php if ($ads) : ?>
		<div class="wvi-grid">
			<?php
			wholesale_vi_render_breakdown('Keywords', $keywords, static function ($row) {
				return esc_html($row['label']);
			}, 'From utm_term. Set up under Settings.');
			wholesale_vi_render_breakdown('Campaigns', $campaigns, static function ($row) {
				return esc_html($row['label']);
			}, 'From utm_campaign.');
			?>
		</div>
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head">
				<h2>Repeat ad clickers</h2>
				<span class="wvi-muted">IP addresses with 3+ ad clicks in this range. Exclude suspicious ones in Google Ads → Settings → IP exclusions.</span>
			</div>
			<?php if (!$repeat) : ?>
				<p class="wvi-empty">No IP address clicked your ads 3 or more times. 👍</p>
			<?php else : ?>
				<div class="wvi-table-wrap">
					<table class="wvi-table">
						<thead><tr><th>IP address</th><th class="num">Ad clicks</th><th class="num">Devices</th><th class="num">Avg. time</th><th>Device · time zone</th><th>Result</th><th>Last click</th><th></th></tr></thead>
						<tbody>
							<?php foreach ($repeat as $row) : ?>
								<tr>
									<td><code><?php echo esc_html($row['ip']); ?></code> <button type="button" class="wvi-copy button-link" data-copy="<?php echo esc_attr($row['ip']); ?>">Copy</button></td>
									<td class="num is-bad"><strong><?php echo esc_html($row['clicks']); ?></strong></td>
									<td class="num"><?php echo esc_html($row['devices']); ?></td>
									<td class="num"><?php echo esc_html(wholesale_vi_duration($row['avg_engaged'])); ?></td>
									<td><?php echo wholesale_vi_device_icon($row['device']); // phpcs:ignore ?> <?php echo esc_html(str_replace('_', ' ', $row['timezone'])); ?></td>
									<td><?php echo $row['orders'] > 0 ? '<span class="wvi-badge is-green">Bought</span>' : ($row['leads'] > 0 ? '<span class="wvi-badge is-blue">Lead</span>' : '<span class="wvi-badge is-red">Nothing</span>'); ?></td>
									<td><?php echo esc_html(wholesale_vi_ago($row['last_seen'])); ?></td>
									<td><a href="<?php echo esc_url(wholesale_vi_url('wholesale-insights-visitors', array('period' => $period['key'], 'from' => $period['start']->format('Y-m-d'), 'to' => $period['end']->modify('-1 second')->format('Y-m-d'), 's' => $row['ip']))); ?>">View visits →</a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
		<?php
		$hour_data = $report['hours'];
		?>
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head">
				<h2>Ad clicks by hour of day</h2>
				<span class="wvi-muted">Use this for ad scheduling: cut bids in hours with many clicks and no leads or orders.</span>
			</div>
			<div class="wvi-hours" data-hours="<?php echo esc_attr(wp_json_encode($hour_data)); ?>"></div>
		</section>
	<?php endif; ?>

	<div class="wvi-grid">
		<?php
		wholesale_vi_render_breakdown('Landing pages', $report['landing'], static function ($row) {
			return sprintf('<a href="%s" target="_blank" rel="noopener" title="%s">%s</a>', esc_url(wholesale_vi_site_link($row['label'])), esc_attr($row['label']), esc_html(wholesale_vi_short_path($row['label'])));
		}, 'First page of each visit.');
		wholesale_vi_render_breakdown('Exit pages of visitors who did not buy', $report['exits'], static function ($row) {
			return sprintf('<a href="%s" target="_blank" rel="noopener" title="%s">%s</a>', esc_url(wholesale_vi_site_link($row['label'])), esc_attr($row['label']), esc_html(wholesale_vi_short_path($row['label'])));
		}, 'Last page seen before leaving.', array('sessions', 'time', 'cart'));
		?>
	</div>

	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2>Problems visitors hit</h2>
			<span class="wvi-muted">Errors shown to visitors, broken pages and frustrated (rage) clicks.</span>
		</div>
		<?php if (!$problems && !$not_found) : ?>
			<p class="wvi-empty">No errors were recorded in this range.</p>
		<?php else : ?>
			<div class="wvi-table-wrap">
				<table class="wvi-table">
					<thead><tr><th>Problem</th><th>Message / element</th><th class="num">Times</th><th class="num">Visitors</th><th>Last seen</th><th></th></tr></thead>
					<tbody>
						<?php foreach ($problems as $row) : ?>
							<tr>
								<td><span class="wvi-badge <?php echo 'rage_click' === $row['type'] || 'js_error' === $row['type'] ? 'is-amber' : 'is-red'; ?>"><?php echo esc_html(wholesale_vi_event_label($row['type'])); ?></span></td>
								<td><?php echo esc_html($row['label'] ?: '—'); ?></td>
								<td class="num"><?php echo esc_html($row['hits']); ?></td>
								<td class="num"><?php echo esc_html($row['sessions']); ?></td>
								<td><?php echo esc_html(wholesale_vi_ago($row['last_at'])); ?></td>
								<td><a href="<?php echo esc_url(wholesale_vi_url('wholesale-insights-visitors', array('period' => $period['key'], 'from' => $period['start']->format('Y-m-d'), 'to' => $period['end']->modify('-1 second')->format('Y-m-d'), 'event' => $row['type'], 'channel' => $filters['channel']))); ?>">Visits →</a></td>
							</tr>
						<?php endforeach; ?>
						<?php foreach ($not_found as $row) : ?>
							<tr>
								<td><span class="wvi-badge is-red">Page not found</span></td>
								<td><code><?php echo esc_html(wholesale_vi_short_path($row['label'], 70)); ?></code></td>
								<td class="num"><?php echo esc_html($row['hits']); ?></td>
								<td class="num"><?php echo esc_html($row['sessions']); ?></td>
								<td><?php echo esc_html(wholesale_vi_ago($row['last_at'])); ?></td>
								<td></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>

	<div class="wvi-grid">
		<?php
		$forms = $report['forms'];
		?>
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head">
				<h2>Form abandonment</h2>
				<span class="wvi-muted">Forms visitors started typing in, and the field where most of them gave up.</span>
			</div>
			<?php if (!$forms) : ?>
				<p class="wvi-empty">No form activity in this range.</p>
			<?php else : ?>
				<div class="wvi-table-wrap">
					<table class="wvi-table">
						<thead><tr><th>Form</th><th class="num">Started</th><th class="num">Finished</th><th class="num">Gave up</th><th>Most left at field</th></tr></thead>
						<tbody>
							<?php foreach ($forms as $row) : ?>
								<tr>
									<td><?php echo esc_html($row['label']); ?></td>
									<td class="num"><?php echo esc_html($row['started']); ?></td>
									<td class="num"><?php echo esc_html($row['submitted']); ?></td>
									<td class="num <?php echo $row['started'] >= 3 && $row['submitted'] < $row['started'] / 2 ? 'is-bad' : ''; ?>"><?php echo esc_html(wholesale_vi_pct($row['started'] - $row['submitted'], $row['started'])); ?></td>
									<td><?php echo $row['left_at'] ? esc_html($row['left_at']) . ' <span class="wvi-muted">(' . esc_html($row['left_at_n']) . ')</span>' : '—'; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
		<?php
		$clicks = $report['clicks'];
		?>
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head">
				<h2>What visitors click</h2>
				<span class="wvi-muted">Most clicked buttons and links.</span>
			</div>
			<?php if (!$clicks) : ?>
				<p class="wvi-empty">No clicks recorded in this range.</p>
			<?php else : ?>
				<div class="wvi-table-wrap">
					<table class="wvi-table">
						<thead><tr><th>Button / link</th><th class="num">Clicks</th><th class="num">Visitors</th><th class="num">Of them bought</th></tr></thead>
						<tbody>
							<?php foreach ($clicks as $row) : ?>
								<tr>
									<td><?php echo 'click' !== $row['type'] ? '<span class="wvi-badge is-blue">' . esc_html(wholesale_vi_event_label($row['type'])) . '</span> ' : ''; ?><?php echo esc_html($row['label'] ?: '(no text)'); ?></td>
									<td class="num"><?php echo esc_html($row['hits']); ?></td>
									<td class="num"><?php echo esc_html($row['sessions']); ?></td>
									<td class="num"><?php echo esc_html($row['buyers']); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
	</div>

	<div class="wvi-grid wvi-grid-3">
		<?php
		$label_device = static function ($row) {
			return wholesale_vi_device_icon($row['label']) . ' ' . esc_html(ucfirst($row['label']));
		};
		wholesale_vi_render_breakdown('Devices', $devices, $label_device, '', array('sessions', 'bounce', 'conv'));
		if (!$ads) {
			wholesale_vi_render_breakdown('Traffic sources', $report['channels'], static function ($row) {
				return wholesale_vi_channel_badge($row['label']);
			}, '', array('sessions', 'bounce', 'conv'));
		} else {
			wholesale_vi_render_breakdown('Browsers', $report['browsers'], static function ($row) {
				return esc_html($row['label']);
			}, '', array('sessions', 'bounce', 'conv'));
		}
		wholesale_vi_render_breakdown('Locations (time zone)', array_slice($timezones, 0, 10), static function ($row) {
			$label = $row['label'] ? str_replace('_', ' ', $row['label']) : 'Unknown';
			return esc_html($label) . (wholesale_vi_is_foreign_tz($row['label']) ? ' <span class="wvi-badge is-amber">Outside US</span>' : '');
		}, '', array('sessions', 'bounce', 'conv'));
		?>
	</div>

	<?php if (!$ads) : ?>
		<?php
		wholesale_vi_render_breakdown('Referring sites and search engines', $report['sources'], static function ($row) {
			return esc_html($row['label']);
		});
		?>
	<?php endif; ?>
	<?php
}

/* People ---------------------------------------------------------------- */

/**
 * Names for a set of visits: the customer account used on the visit, the
 * account this browser logged in with on another visit, or the billing name
 * of an order placed as a guest. Loaded once per list and cached.
 *
 * @param array $rows Session rows.
 */
function wholesale_vi_load_people($rows)
{
	global $wpdb;
	$people = &wholesale_vi_people_cache();
	$rows = array_filter((array) $rows, static function ($row) use ($people) {
		return !array_key_exists((int) $row->id, $people);
	});
	if (!$rows) {
		return;
	}

	// Accounts seen on other visits from the same browser.
	$by_visitor = array();
	$visitor_keys = array_unique(array_map(static function ($row) {
		return $row->visitor_key;
	}, $rows));
	if ($visitor_keys) {
		$in = implode(', ', array_fill(0, count($visitor_keys), '%s'));
		foreach ($wpdb->get_results($wpdb->prepare('SELECT visitor_key, user_id FROM ' . wholesale_vi_table('sessions') . " WHERE user_id > 0 AND visitor_key IN ({$in}) ORDER BY last_seen ASC", $visitor_keys)) as $match) {
			$by_visitor[$match->visitor_key] = (int) $match->user_id;
		}
	}

	$user_ids = array_unique(array_filter(array_merge(array_map(static function ($row) {
		return (int) $row->user_id;
	}, $rows), array_values($by_visitor))));
	$users = array();
	if ($user_ids) {
		foreach (get_users(array('include' => $user_ids, 'fields' => array('ID', 'display_name', 'user_email', 'user_login'))) as $user) {
			$name = trim(get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true));
			$users[(int) $user->ID] = array('user_id' => (int) $user->ID, 'name' => $name ?: $user->display_name, 'email' => $user->user_email);
		}
	}

	foreach ($rows as $row) {
		$person = null;
		if ((int) $row->user_id && isset($users[(int) $row->user_id])) {
			$person = $users[(int) $row->user_id] + array('type' => 'member', 'note' => 'Logged in during this visit');
		} elseif (isset($by_visitor[$row->visitor_key], $users[$by_visitor[$row->visitor_key]])) {
			$person = $users[$by_visitor[$row->visitor_key]] + array('type' => 'linked', 'note' => 'Not logged in on this visit; same browser logged in on another visit');
		} elseif ((int) $row->order_id && function_exists('wholesale_decode_order_meta_array')) {
			$billing = wholesale_decode_order_meta_array(get_post_meta((int) $row->order_id, 'billing_address', true));
			$name = trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? ''));
			if ('' !== $name) {
				$person = array('user_id' => 0, 'name' => $name, 'email' => (string) ($billing['billing_email'] ?? ''), 'type' => 'buyer', 'note' => 'Guest checkout; name from the order');
			}
		}
		$people[(int) $row->id] = $person;
	}
}

function &wholesale_vi_people_cache()
{
	static $people = array();

	return $people;
}

function wholesale_vi_person($row)
{
	$people = &wholesale_vi_people_cache();
	if (!array_key_exists((int) $row->id, $people)) {
		wholesale_vi_load_people(array($row));
	}

	return $people[(int) $row->id];
}

function wholesale_vi_person_html($person)
{
	if (!$person) {
		return '';
	}
	$icons = array('member' => 'admin-users', 'linked' => 'admin-users', 'buyer' => 'cart');

	return sprintf(
		'<span class="wvi-person is-%s" title="%s"><span class="dashicons dashicons-%s"></span>%s</span>',
		esc_attr($person['type']),
		esc_attr($person['note'] . ($person['email'] ? ' · ' . $person['email'] : '')),
		esc_attr($icons[$person['type']]),
		esc_html($person['name'])
	);
}

/* Visitors list --------------------------------------------------------- */

function wholesale_vi_render_visitors()
{
	global $wpdb;
	$period = wholesale_vi_period();
	$filters = wholesale_vi_request_filters();
	$outcome = isset($_GET['outcome']) ? sanitize_key(wp_unslash($_GET['outcome'])) : '';
	$event = isset($_GET['event']) ? sanitize_key(wp_unslash($_GET['event'])) : '';
	$search = isset($_GET['s']) ? trim(sanitize_text_field(wp_unslash($_GET['s']))) : '';
	$per_page = 30;
	$paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);

	list($where, $params) = wholesale_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$table = wholesale_vi_table('sessions');
	$outcomes = array(
		'' => 'All visits',
		'members' => 'Logged-in customers',
		'purchased' => 'Purchased',
		'lead' => 'Leads',
		'abandoned' => 'Abandoned cart / checkout',
		'engaged' => 'Engaged, no purchase',
		'bounced' => 'Bounced',
	);
	switch ($outcome) {
		case 'members':
			$where .= ' AND s.user_id > 0';
			break;
		case 'purchased':
			$where .= ' AND s.order_id > 0';
			break;
		case 'lead':
			$where .= ' AND s.is_lead = 1';
			break;
		case 'abandoned':
			$where .= ' AND s.stage >= 4 AND s.order_id = 0';
			break;
		case 'engaged':
			$where .= ' AND s.order_id = 0 AND NOT ' . wholesale_vi_bounce_sql();
			break;
		case 'bounced':
			$where .= ' AND ' . wholesale_vi_bounce_sql();
			break;
	}
	if ($event && in_array($event, wholesale_vi_event_types(), true)) {
		$where .= ' AND EXISTS (SELECT 1 FROM ' . wholesale_vi_table('events') . ' x WHERE x.session_id = s.id AND x.type = %s)';
		$params[] = $event;
	}
	if ('' !== $search) {
		$like = '%' . $wpdb->esc_like($search) . '%';
		// Customer name or email: visits on their account, and other visits
		// from browsers they logged in with.
		$user_ids = get_users(array('search' => '*' . $search . '*', 'search_columns' => array('user_login', 'user_email', 'display_name'), 'fields' => 'ID', 'number' => 50));
		$user_ids = array_unique(array_merge(array_map('intval', $user_ids), array_map('intval', get_users(array(
			'fields' => 'ID',
			'number' => 50,
			'meta_query' => array('relation' => 'OR', array('key' => 'first_name', 'value' => $search, 'compare' => 'LIKE'), array('key' => 'last_name', 'value' => $search, 'compare' => 'LIKE')),
		)))));
		$people_sql = '';
		if ($user_ids) {
			$ids = implode(',', $user_ids);
			$people_sql = " OR s.user_id IN ({$ids}) OR s.visitor_key IN (SELECT visitor_key FROM (SELECT DISTINCT visitor_key FROM {$table} WHERE user_id IN ({$ids})) known)";
		}
		$where .= ' AND (s.ip = %s OR s.landing_url LIKE %s OR s.exit_url LIKE %s OR s.campaign LIKE %s OR s.term LIKE %s OR s.source LIKE %s OR s.visitor_key = %s' . $people_sql . ')';
		array_push($params, $search, $like, $like, $like, $like, $like, $search);
	}

	$total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} s WHERE {$where}", $params));
	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT s.* FROM {$table} s WHERE {$where} ORDER BY s.started_at DESC LIMIT %d OFFSET %d",
		array_merge($params, array($per_page, ($paged - 1) * $per_page))
	));
	$pages = max(1, (int) ceil($total / $per_page));
	wholesale_vi_load_people($rows);

	add_action('wholesale_vi_toolbar_fields', static function () use ($outcome, $outcomes, $search, $event) {
		?>
		<select name="outcome" aria-label="Outcome">
			<?php foreach ($outcomes as $key => $label) : ?>
				<option value="<?php echo esc_attr($key); ?>" <?php selected($outcome, $key); ?>><?php echo esc_html($label); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ($event) : ?><input type="hidden" name="event" value="<?php echo esc_attr($event); ?>"><?php endif; ?>
		<span class="wvi-search"><span class="dashicons dashicons-search"></span><input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Name, email, IP, page, keyword"></span>
		<?php
	});
	wholesale_vi_render_toolbar($period, 'wholesale-insights-visitors', array_merge($filters, array('outcome' => $outcome, 's' => $search, 'event' => $event)));
	?>
	<section class="wvi-card">
		<div class="wvi-section-head wvi-pad">
			<h2><?php echo esc_html(number_format_i18n($total)); ?> visits</h2>
			<span class="wvi-muted"><?php echo $event ? esc_html('Only visits with: ' . wholesale_vi_event_label($event) . ' · ') : ''; ?>Click a row to see the visitor's full journey.</span>
		</div>
		<?php if (!$rows) : ?>
			<p class="wvi-empty">No visits match these filters.</p>
		<?php else : ?>
			<div class="wvi-table-wrap">
				<table class="wvi-table wvi-visits">
					<thead>
						<tr><th>When</th><th>Source</th><th>Landing page</th><th>Device · location</th><th class="num">Pages</th><th class="num">Time</th><th>Got to</th><th>Outcome</th></tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $row) : ?>
							<?php wholesale_vi_visit_row($row); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($pages > 1) : ?>
				<div class="wvi-pagination">
					<?php
					echo paginate_links(array( // phpcs:ignore WordPress.Security.EscapeOutput
						'base' => add_query_arg('paged', '%#%'),
						'format' => '',
						'current' => $paged,
						'total' => $pages,
						'prev_text' => '‹ Newer',
						'next_text' => 'Older ›',
					));
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</section>
	<?php
}

function wholesale_vi_visit_row($row, $live = false)
{
	$url = wholesale_vi_url('wholesale-insights-visitors', array('session' => $row->id));
	$online = strtotime($row->last_seen . ' UTC') > time() - 5 * MINUTE_IN_SECONDS;
	$source_detail = array_filter(array($row->campaign, $row->term ? '“' . $row->term . '”' : '', 'google_ads' !== $row->channel ? $row->source : ''));
	?>
	<tr class="wvi-row-link" data-id="<?php echo esc_attr($row->id); ?>" data-href="<?php echo esc_url($url); ?>">
		<td class="wvi-nowrap">
			<?php if ($online) : ?><span class="wvi-live-dot" title="Online now"></span><?php endif; ?>
			<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($live ? wholesale_vi_ago($row->started_at) : wholesale_vi_date($row->started_at)); ?></a>
			<?php if (!(int) $row->is_new) : ?><span class="wvi-badge is-soft" title="Has visited before">Returning</span><?php endif; ?>
			<?php echo wholesale_vi_person_html(wholesale_vi_person($row)); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>
		</td>
		<td><?php echo wholesale_vi_channel_badge($row->channel); // phpcs:ignore ?><?php if ($source_detail) : ?><span class="wvi-sub"><?php echo esc_html(implode(' · ', $source_detail)); ?></span><?php endif; ?></td>
		<td class="wvi-path" title="<?php echo esc_attr($live ? $row->exit_url : $row->landing_url); ?>">
			<?php echo esc_html(wholesale_vi_short_path($live ? $row->exit_url : $row->landing_url, 40)); ?>
		</td>
		<td class="wvi-nowrap"><?php echo wholesale_vi_device_icon($row->device); // phpcs:ignore ?> <span class="wvi-sub-inline"><?php echo esc_html(wholesale_vi_location($row) ?: $row->browser); ?></span></td>
		<td class="num"><?php echo esc_html($row->pageviews); ?></td>
		<td class="num"><?php echo esc_html(wholesale_vi_duration($row->engaged_seconds)); ?></td>
		<td><?php echo wholesale_vi_stage_pips((int) $row->stage); // phpcs:ignore ?></td>
		<td><?php echo wholesale_vi_outcome($row); // phpcs:ignore ?><?php if ((int) $row->errors) : ?> <span class="wvi-badge is-red" title="Errors shown to this visitor"><?php echo esc_html($row->errors); ?> err</span><?php endif; ?></td>
	</tr>
	<?php
}

/* Single visit ---------------------------------------------------------- */

function wholesale_vi_event_text($event)
{
	$label = $event->label;
	switch ($event->type) {
		case 'pageview':
			return array('visibility', '404' === $event->value ? 'Hit a “page not found” page' : 'Viewed ' . ($label ?: 'a page'), '404' === $event->value ? 'bad' : '');
		case 'click':
			return array('admin-links', 'Clicked “' . ($label ?: 'element') . '”', '');
		case 'call_click':
			return array('phone', 'Tapped to call ' . $label, 'good');
		case 'email_click':
			return array('email', 'Clicked to email ' . $label, 'good');
		case 'rage_click':
			return array('warning', 'Rage-clicked (clicked repeatedly in frustration) on “' . $label . '”', 'warn');
		case 'configure':
			return array('admin-settings', 'Started choosing options' . ($label ? ' (' . $label . ')' : ''), '');
		case 'price_quote':
			return array('tag', 'Got a price' . ($event->value ? ': ' . wholesale_vi_money($event->value) : ''), '');
		case 'price_error':
			return array('dismiss', 'Pricing error: ' . $label, 'bad');
		case 'design_upload':
			return array('upload', 'Uploaded a design file', '');
		case 'upload_error':
			return array('dismiss', 'Design upload failed: ' . $label, 'bad');
		case 'add_to_cart':
			return array('cart', 'Added to cart', 'good');
		case 'cart_error':
			return array('dismiss', 'Could not add to cart: ' . $label, 'bad');
		case 'cart_remove':
			return array('trash', 'Removed an item from the cart', 'warn');
		case 'form_start':
			return array('edit', 'Started filling in “' . $label . '”' . ($event->value ? ' — last field: ' . $event->value : ''), '');
		case 'form_submit':
			return array('yes', 'Submitted “' . $label . '”', 'good');
		case 'payment_start':
			return array('money-alt', 'Clicked to pay', 'good');
		case 'checkout_error':
			return array('dismiss', 'Checkout error: ' . $label, 'bad');
		case 'payment_error':
			return array('dismiss', 'Payment failed: ' . $label, 'bad');
		case 'js_error':
			return array('warning', 'Page script error: ' . $label, 'warn');
		case 'purchase':
			return array('yes-alt', 'Purchased — ' . $label, 'won');
		case 'quote_submit':
			return array('format-chat', 'Submitted a quote request — ' . $label, 'good');
	}

	return array('marker', $event->type . ' ' . $label, '');
}

function wholesale_vi_render_session($id)
{
	global $wpdb;
	$session = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('sessions') . ' WHERE id = %d', $id));
	if (!$session) {
		echo '<div class="wvi-alert is-error">That visit was not found. It may have been deleted after the retention period.</div>';
		return;
	}

	$events = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('events') . ' WHERE session_id = %d ORDER BY created_at, id LIMIT 2000', $id));
	$others = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('sessions') . ' WHERE visitor_key = %s AND id <> %d ORDER BY started_at DESC LIMIT 20', $session->visitor_key, $id));
	$start = strtotime($session->started_at . ' UTC');
	$duration = max(0, strtotime($session->last_seen . ' UTC') - $start);
	$back = wp_get_referer() && false !== strpos(wp_get_referer(), 'wholesale-insights') ? wp_get_referer() : wholesale_vi_url('wholesale-insights-visitors');

	$person = wholesale_vi_person($session);
	$facts = array();
	if ($person) {
		$who = '<strong>' . esc_html($person['name']) . '</strong>';
		if ($person['user_id']) {
			$who = '<a href="' . esc_url(get_edit_user_link($person['user_id'])) . '">' . $who . '</a>';
		}
		if ($person['email']) {
			$who .= '<br><a href="mailto:' . esc_attr($person['email']) . '">' . esc_html($person['email']) . '</a>';
		}
		$facts['Customer'] = $who . '<br><span class="wvi-muted">' . esc_html($person['note']) . '</span>';
	}
	$facts += array(
		'Source' => wholesale_vi_channel_badge($session->channel) . ($session->source ? ' ' . esc_html($session->source . ($session->medium ? ' / ' . $session->medium : '')) : ''),
		'Campaign' => esc_html($session->campaign ?: '—'),
		'Keyword' => esc_html($session->term ?: '—'),
		'Ad click ID' => esc_html($session->click_type ?: '—'),
		'Referrer' => $session->referrer ? '<span class="wvi-break">' . esc_html($session->referrer) . '</span>' : '—',
		'Landing page' => '<a href="' . esc_url(wholesale_vi_site_link($session->landing_url)) . '" target="_blank" rel="noopener" class="wvi-break">' . esc_html(wholesale_vi_short_path($session->landing_url, 80)) . '</a>',
		'Device' => wholesale_vi_device_icon($session->device) . ' ' . esc_html(ucfirst($session->device) . ' · ' . $session->os . ' · ' . $session->browser . ($session->screen ? ' · ' . $session->screen : '')),
		'Location' => esc_html(wholesale_vi_location($session) ?: '—') . ($session->language ? ' <span class="wvi-muted">(' . esc_html($session->language) . ')</span>' : ''),
		'IP address' => $session->ip ? '<code>' . esc_html($session->ip) . '</code> <a href="' . esc_url(wholesale_vi_url('wholesale-insights-visitors', array('period' => '90d', 's' => $session->ip))) . '">all visits from this IP</a>' : '—',
		'Visitor' => ((int) $session->is_new ? 'First visit' : 'Returning visitor') . ' · ' . (count($others) + 1) . ' visit(s) on record',
	);
	if ((int) $session->order_id) {
		$facts['Order'] = '<a href="' . esc_url(get_edit_post_link((int) $session->order_id)) . '">' . esc_html(get_the_title((int) $session->order_id) ?: '#' . $session->order_id) . '</a> · ' . esc_html(wholesale_vi_money($session->revenue));
	}
	?>
	<p><a href="<?php echo esc_url($back); ?>" class="wvi-back">← Back to visits</a></p>

	<div class="wvi-session-head wvi-card">
		<div>
			<h2><?php echo $person ? esc_html($person['name']) . ' · ' : ''; ?><?php echo esc_html(wholesale_vi_date($session->started_at, 'l, M j, Y · g:i a')); ?></h2>
			<p class="wvi-muted">
				<?php echo esc_html(sprintf('%d page(s) · %s engaged · %s on site · %d%% max scroll', $session->pageviews, wholesale_vi_duration($session->engaged_seconds), wholesale_vi_duration($duration), $session->max_scroll)); ?>
			</p>
		</div>
		<div class="wvi-session-outcome">
			<?php echo wholesale_vi_outcome($session); // phpcs:ignore ?>
			<div class="wvi-stage-line"><?php echo wholesale_vi_stage_pips((int) $session->stage); // phpcs:ignore ?> <span><?php echo esc_html(wholesale_vi_stages()[(int) $session->stage] ?? ''); ?></span></div>
		</div>
	</div>

	<div class="wvi-grid wvi-grid-session">
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head"><h2>Journey</h2><span class="wvi-muted">Everything this visitor did, in order.</span></div>
			<?php if (!$events) : ?>
				<p class="wvi-empty">No activity recorded.</p>
			<?php else : ?>
				<ol class="wvi-timeline">
					<?php foreach ($events as $event) : ?>
						<?php
						list($icon, $text, $tone) = wholesale_vi_event_text($event);
						$offset = max(0, strtotime($event->created_at . ' UTC') - $start);
						?>
						<li class="is-<?php echo esc_attr($event->type); ?> <?php echo $tone ? 'tone-' . esc_attr($tone) : ''; ?>">
							<span class="wvi-tl-time" title="<?php echo esc_attr(wholesale_vi_date($event->created_at, 'g:i:s a')); ?>">+<?php echo esc_html(sprintf('%d:%02d', floor($offset / 60), $offset % 60)); ?></span>
							<span class="wvi-tl-icon dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
							<div class="wvi-tl-body">
								<div class="wvi-tl-text"><?php echo esc_html($text); ?></div>
								<?php if ('pageview' === $event->type) : ?>
									<div class="wvi-tl-meta">
										<a href="<?php echo esc_url(wholesale_vi_site_link($event->url)); ?>" target="_blank" rel="noopener"><?php echo esc_html(wholesale_vi_short_path($event->url, 70)); ?></a>
										<span class="wvi-chip"><?php echo esc_html(wholesale_vi_duration($event->seconds)); ?> on page</span>
										<span class="wvi-chip">scrolled <?php echo esc_html($event->scroll); ?>%</span>
									</div>
								<?php elseif ('click' === $event->type && $event->value) : ?>
									<div class="wvi-tl-meta"><span class="wvi-break"><?php echo esc_html(wholesale_vi_short_path(preg_replace('#^https?://[^/]+#', '', $event->value), 70)); ?></span></div>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
					<?php if (strtotime($session->last_seen . ' UTC') < time() - 5 * MINUTE_IN_SECONDS && !(int) $session->order_id) : ?>
						<li class="tone-end">
							<span class="wvi-tl-time">+<?php echo esc_html(sprintf('%d:%02d', floor($duration / 60), $duration % 60)); ?></span>
							<span class="wvi-tl-icon dashicons dashicons-exit"></span>
							<div class="wvi-tl-body"><div class="wvi-tl-text">Left the site from <?php echo esc_html(wholesale_vi_short_path($session->exit_url, 60)); ?></div></div>
						</li>
					<?php endif; ?>
				</ol>
			<?php endif; ?>
		</section>

		<div>
			<section class="wvi-card wvi-section">
				<div class="wvi-section-head"><h2>Visit details</h2></div>
				<dl class="wvi-facts">
					<?php foreach ($facts as $label => $html) : ?>
						<dt><?php echo esc_html($label); ?></dt>
						<dd><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></dd>
					<?php endforeach; ?>
				</dl>
			</section>
			<?php if ($others) : ?>
				<section class="wvi-card wvi-section">
					<div class="wvi-section-head"><h2>Other visits by this visitor</h2></div>
					<?php wholesale_vi_load_people($others); ?>
					<ul class="wvi-other-visits">
						<?php foreach ($others as $other) : ?>
							<li>
								<a href="<?php echo esc_url(wholesale_vi_url('wholesale-insights-visitors', array('session' => $other->id))); ?>"><?php echo esc_html(wholesale_vi_date($other->started_at)); ?></a>
								<?php echo wholesale_vi_channel_badge($other->channel); // phpcs:ignore ?>
								<span class="wvi-muted"><?php echo esc_html($other->pageviews . ' pages'); ?></span>
								<?php echo wholesale_vi_outcome($other); // phpcs:ignore ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/* Live ------------------------------------------------------------------ */

function wholesale_vi_live_count()
{
	global $wpdb;

	return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . wholesale_vi_table('sessions') . ' WHERE last_seen >= %s', gmdate('Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS)));
}

function wholesale_vi_live_rows_html()
{
	global $wpdb;
	$rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('sessions') . ' WHERE last_seen >= %s ORDER BY last_seen DESC LIMIT 100', gmdate('Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS)));

	ob_start();
	if (!$rows) {
		echo '<tr><td colspan="8" class="wvi-empty">Nobody is on the site right now.</td></tr>';
	}
	wholesale_vi_load_people($rows);
	foreach ($rows as $row) {
		wholesale_vi_visit_row($row, true);
	}

	return array('count' => count($rows), 'html' => ob_get_clean());
}

function wholesale_vi_render_live()
{
	$live = wholesale_vi_live_rows_html();
	?>
	<section class="wvi-card">
		<div class="wvi-section-head wvi-pad">
			<h2><span class="wvi-live-dot"></span> <span data-live-count><?php echo esc_html($live['count']); ?></span> visitor(s) online now</h2>
			<span class="wvi-muted">Active in the last 5 minutes · updates live · the page column shows where they are now.</span>
		</div>
		<div class="wvi-table-wrap">
			<table class="wvi-table wvi-visits">
				<thead><tr><th>Arrived</th><th>Source</th><th>Current page</th><th>Device · location</th><th class="num">Pages</th><th class="num">Time</th><th>Got to</th><th>Outcome</th></tr></thead>
				<tbody data-live-rows><?php echo $live['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping. ?></tbody>
			</table>
		</div>
	</section>
	<?php
}


/* Settings -------------------------------------------------------------- */

function wholesale_vi_render_settings()
{
	global $wpdb;
	$settings = wholesale_vi_settings();
	$sessions = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . wholesale_vi_table('sessions'));
	$events = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . wholesale_vi_table('events'));
	$last = $wpdb->get_var('SELECT MAX(last_seen) FROM ' . wholesale_vi_table('sessions'));
	$suffix = 'utm_source=google&utm_medium=cpc&utm_campaign={campaignid}&utm_content={adgroupid}&utm_term={keyword}';
	?>
	<div class="wvi-grid">
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head"><h2>Tracking</h2></div>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wvi-form">
				<input type="hidden" name="action" value="wholesale_vi_save">
				<?php wp_nonce_field('wholesale_vi_save'); ?>
				<label class="wvi-check"><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> <span><strong>Record visitor activity</strong><br><span class="wvi-muted">Turn off to pause tracking. Existing data is kept.</span></span></label>
				<label class="wvi-check"><input type="checkbox" name="exclude_admins" value="1" <?php checked($settings['exclude_admins']); ?>> <span><strong>Leave out staff visits</strong><br><span class="wvi-muted">Logged-in staff, and any browser that has opened wp-admin, are not counted.</span></span></label>
				<label class="wvi-check"><input type="checkbox" name="mask_ip" value="1" <?php checked($settings['mask_ip']); ?>> <span><strong>Mask IP addresses</strong><br><span class="wvi-muted">Stores 203.0.113.0 instead of 203.0.113.54. More private, but "Repeat ad clickers" becomes less precise.</span></span></label>
				<p>
					<label for="wvi-retention"><strong>Keep data for</strong></label><br>
					<select id="wvi-retention" name="retention_days">
						<?php foreach (array(30, 60, 90, 120, 180, 365) as $days) : ?>
							<option value="<?php echo esc_attr($days); ?>" <?php selected((int) $settings['retention_days'], $days); ?>><?php echo esc_html($days); ?> days</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="wvi-ips"><strong>Ignore these IP addresses</strong></label><br>
					<textarea id="wvi-ips" name="excluded_ips" rows="3" class="large-text code" placeholder="One per line, e.g. your office IP"><?php echo esc_textarea($settings['excluded_ips']); ?></textarea>
					<span class="wvi-muted">Your current IP: <code><?php echo esc_html(wholesale_vi_client_ip(false)); ?></code></span>
				</p>
				<p><button class="button button-primary">Save settings</button></p>
			</form>
		</section>

		<div>
			<section class="wvi-card wvi-section">
				<div class="wvi-section-head"><h2>See keywords and campaigns from Google Ads</h2></div>
				<p>In Google Ads, open <strong>Admin → Account settings → Tracking</strong> (or a campaign's <em>Campaign URL options</em>) and paste this into <strong>Final URL suffix</strong>:</p>
				<div class="wvi-code">
					<code><?php echo esc_html($suffix); ?></code>
					<button type="button" class="button wvi-copy" data-copy="<?php echo esc_attr($suffix); ?>">Copy</button>
				</div>
				<p class="wvi-muted">Every ad click then tells this page which campaign, ad group and keyword it came from, so the Google Ads tab can show which keywords bring visitors who buy and which only cost money. Keep auto-tagging (gclid) on as well; it is what marks a visit as a Google Ads click.</p>
			</section>
			<section class="wvi-card wvi-section">
				<div class="wvi-section-head"><h2>Stored data</h2></div>
				<p><?php echo esc_html(sprintf('%s visits and %s activity records.', number_format_i18n($sessions), number_format_i18n($events))); ?> <?php echo $last ? esc_html('Last activity ' . wholesale_vi_ago($last) . '.') : 'No visits recorded yet.'; ?></p>
				<p class="wvi-muted">Only page addresses, clicks on buttons and links, and which form field a visitor was on are recorded — never what they type. Mention first-party analytics cookies (sso_vid, sso_sid) in your privacy policy.</p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-confirm="Delete all recorded visits and activity? This cannot be undone.">
					<input type="hidden" name="action" value="wholesale_vi_purge_all">
					<?php wp_nonce_field('wholesale_vi_purge_all'); ?>
					<button class="button wvi-danger">Delete all visitor data</button>
				</form>
			</section>
		</div>
	</div>
	<?php
}

function wholesale_vi_save_settings()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.', 403);
	}
	check_admin_referer('wholesale_vi_save');

	$ips = preg_split('/[\s,]+/', sanitize_textarea_field(wp_unslash($_POST['excluded_ips'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
	$ips = array_filter($ips, static function ($ip) {
		return (bool) filter_var($ip, FILTER_VALIDATE_IP);
	});

	update_option('wholesale_vi_settings', array(
		'enabled' => empty($_POST['enabled']) ? 0 : 1,
		'exclude_admins' => empty($_POST['exclude_admins']) ? 0 : 1,
		'mask_ip' => empty($_POST['mask_ip']) ? 0 : 1,
		'retention_days' => min(365, max(30, absint($_POST['retention_days'] ?? 120))),
		'excluded_ips' => implode("\n", array_unique($ips)),
	), false);

	wp_safe_redirect(wholesale_vi_url('wholesale-insights-settings', array('vi_notice' => 'saved')));
	exit;
}
add_action('admin_post_wholesale_vi_save', 'wholesale_vi_save_settings');

function wholesale_vi_purge_all()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.', 403);
	}
	check_admin_referer('wholesale_vi_purge_all');

	global $wpdb;
	$wpdb->query('TRUNCATE TABLE ' . wholesale_vi_table('events'));
	$wpdb->query('TRUNCATE TABLE ' . wholesale_vi_table('sessions'));

	wp_safe_redirect(wholesale_vi_url('wholesale-insights-settings', array('vi_notice' => 'purged')));
	exit;
}
add_action('admin_post_wholesale_vi_purge_all', 'wholesale_vi_purge_all');

/* Export ---------------------------------------------------------------- */

/**
 * Theme files behind each kind of page, for the exported report, so whoever
 * acts on it (a developer or Claude) knows where to make changes. Only files
 * that exist are listed.
 */
function wholesale_vi_page_type_files()
{
	$map = array(
		'home' => array('home.php', 'header.php', 'footer.php', 'css/style.scss'),
		'landing' => array('landing-page.php', 'page-channel-letters.php', 'css/landing-page.css', 'css/cl-quote.css'),
		'product' => array('single-product.php', 'js/main.js', 'inc/pricing.php', 'css/shop.css'),
		'builder' => array('cl_builderr.php', 'b2calc.php', 'js/cl.js', 'css/cl.css'),
		'category' => array('home.php'),
		'cart' => array('cart.php', 'utils/Cart.php', 'inc/mini-cart.php', 'js/mini-cart.js'),
		'checkout' => array('checkout.php', 'js/checkout.js', 'inc/payments.php'),
		'thankyou' => array('thank-you.php', 'inc/ads-tracking.php'),
		'contact' => array('page-contact.php'),
		'404' => array('404.php'),
		'tracking' => array('inc/visitor-insights.php', 'js/visitor-tracker.js', 'inc/ads-tracking.php'),
	);
	$dir = get_template_directory();
	foreach ($map as $type => $files) {
		$map[$type] = array_values(array_filter($files, static function ($file) use ($dir) {
			return file_exists($dir . '/' . $file);
		}));
	}

	return array_filter($map);
}

function wholesale_vi_error_files($type)
{
	$map = array(
		'checkout_error' => 'inc/payments.php, js/checkout.js',
		'payment_error' => 'inc/payments.php, js/checkout.js',
		'price_error' => 'inc/pricing.php, js/main.js',
		'cart_error' => 'utils/Cart.php, inc/mini-cart.php',
		'upload_error' => 'functions.php (wholesale_upload_design)',
		'rage_click' => 'the page where it happened',
	);

	return $map[$type] ?? '';
}

/**
 * The theme template that renders a site path, e.g. "single-product.php".
 */
function wholesale_vi_path_template($path)
{
	static $cache = array();
	if (isset($cache[$path])) {
		return $cache[$path];
	}

	$home_path = untrailingslashit((string) wp_parse_url(home_url('/'), PHP_URL_PATH));
	$relative = '/' . ltrim($home_path && 0 === strpos($path, $home_path) ? substr($path, strlen($home_path)) : $path, '/');
	$relative = strtok($relative, '?');
	$template = '';
	$post_id = 0;

	if ('/' === $relative) {
		$post_id = 'page' === get_option('show_on_front') ? (int) get_option('page_on_front') : 0;
		$template = $post_id ? '' : 'home.php';
	} elseif (preg_match('#^/thank-you/?$#', $relative)) {
		$template = 'thank-you.php';
	} else {
		$post_id = url_to_postid(home_url($relative));
	}

	if (!$template && $post_id) {
		$post = get_post($post_id);
		if ($post && 'page' === $post->post_type) {
			$template = get_page_template_slug($post_id);
			if (!$template) {
				$template = file_exists(get_template_directory() . '/page-' . $post->post_name . '.php') ? 'page-' . $post->post_name . '.php' : 'page.php';
			}
		} elseif ($post) {
			$template = 'product' === $post->post_type ? 'single-product.php' : ('post' === $post->post_type ? 'single.php' : 'single-' . $post->post_type . '.php');
		}
	} elseif (!$template && get_term_by('slug', trim($relative, '/'), 'product_category')) {
		$template = 'home.php (product category)';
	}

	return $cache[$path] = $template;
}

function wholesale_vi_md_cell($value)
{
	return str_replace(array('|', "\n", "\r"), array('\|', ' ', ''), (string) $value);
}

/**
 * A Markdown table from rows of cells.
 */
function wholesale_vi_md_table($headers, $rows)
{
	if (!$rows) {
		return "_No data in this range._\n";
	}

	$out = '| ' . implode(' | ', $headers) . " |\n|" . str_repeat(' --- |', count($headers)) . "\n";
	foreach ($rows as $row) {
		$out .= '| ' . implode(' | ', array_map('wholesale_vi_md_cell', $row)) . " |\n";
	}

	return $out;
}

/**
 * Standard metric cells for a breakdown row.
 */
function wholesale_vi_md_metrics($row)
{
	return array(
		number_format_i18n($row['sessions']),
		wholesale_vi_pct($row['bounced'], $row['sessions']),
		wholesale_vi_duration($row['avg_engaged']),
		number_format_i18n($row['s2']),
		number_format_i18n($row['s4']),
		number_format_i18n($row['leads']),
		number_format_i18n($row['orders']),
		wholesale_vi_pct($row['orders'] + $row['leads'], $row['sessions'], 1),
	);
}

function wholesale_vi_md_breakdown($rows, $first_header, $label_cb = null)
{
	$out = array();
	foreach ($rows as $row) {
		$out[] = array_merge(array($label_cb ? $label_cb($row) : ($row['label'] ?: '(none)')), wholesale_vi_md_metrics($row));
	}

	return wholesale_vi_md_table(array($first_header, 'Visits', 'Bounce', 'Avg. engaged', 'Viewed product', 'Cart', 'Leads', 'Orders', 'Conv.'), $out);
}

/**
 * Recent visits that showed interest but did not buy, as short step lists.
 * No names, emails or IP addresses.
 */
function wholesale_vi_md_journeys($period, $filters, $limit = 15)
{
	global $wpdb;
	list($where, $params) = wholesale_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$params[] = (int) $limit;
	$sessions = $wpdb->get_results($wpdb->prepare(
		'SELECT * FROM ' . wholesale_vi_table('sessions') . " s WHERE {$where} AND s.order_id = 0 AND (s.stage >= 2 OR s.pageviews >= 2 OR s.errors > 0)
		ORDER BY s.stage DESC, s.errors DESC, s.started_at DESC LIMIT %d",
		$params
	));
	if (!$sessions) {
		return "\n_No visits that showed interest without buying in this range._\n";
	}

	$stages = wholesale_vi_stages();
	$channels = wholesale_vi_channels();
	$out = '';
	foreach ($sessions as $i => $session) {
		$events = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('events') . ' WHERE session_id = %d ORDER BY created_at, id LIMIT 80', $session->id));
		$start = strtotime($session->started_at . ' UTC');
		$out .= sprintf(
			"\n**Visit %d** — %s, %s %s, %s · %d pages · %s engaged · got to: %s%s\n\n",
			$i + 1,
			$channels[$session->channel] ?? $session->channel,
			ucfirst($session->device),
			$session->browser,
			$session->timezone ?: 'unknown location',
			$session->pageviews,
			wholesale_vi_duration($session->engaged_seconds),
			$stages[(int) $session->stage] ?? '',
			$session->term ? ' · keyword: "' . $session->term . '"' : ''
		);
		$last = '';
		foreach ($events as $event) {
			list(, $text) = wholesale_vi_event_text($event);
			if ('pageview' === $event->type) {
				$text = sprintf('Viewed %s (%s, %s on page, scrolled %d%%)', wholesale_vi_short_path($event->url, 90), $event->label ?: 'untitled', wholesale_vi_duration($event->seconds), $event->scroll);
			} elseif ('quote_submit' === $event->type) {
				$text = 'Submitted a quote request';
			}
			if ($text === $last) {
				continue;
			}
			$last = $text;
			$offset = max(0, strtotime($event->created_at . ' UTC') - $start);
			$out .= sprintf("- +%d:%02d %s\n", floor($offset / 60), $offset % 60, $text);
		}
		$out .= sprintf("- Left from %s\n", wholesale_vi_short_path($session->exit_url, 90));
	}

	return $out;
}

/**
 * The full insights report as Markdown, written so it can be handed to
 * Claude (or a developer) to find and fix what stops visitors from buying.
 */
function wholesale_vi_export_markdown($period, $filters)
{
	$all = wholesale_vi_report_data($period, $filters, false);
	$ads = wholesale_vi_report_data($period, array_merge($filters, array('channel' => 'google_ads')), true);
	$m = $all['summary'];
	$a = $ads['summary'];
	$prev = $all['previous'];
	$ap = $ads['previous'];
	$site = wp_parse_url(home_url(), PHP_URL_HOST);
	$range = $period['start']->format('M j, Y') . ' – ' . $period['end']->modify('-1 second')->format('M j, Y');
	$filter_text = array_filter(array(
		!empty($filters['channel']) ? 'source: ' . (wholesale_vi_channels()[$filters['channel']] ?? $filters['channel']) : '',
		!empty($filters['device']) ? 'device: ' . $filters['device'] : '',
	));
	$template_label = static function ($row) {
		$template = wholesale_vi_path_template($row['label']);
		return wholesale_vi_short_path($row['label'], 90) . ($template ? ' → `' . $template . '`' : '');
	};

	$md = "# Visitor Insights report — {$site}\n\n";
	$md .= "- **Period:** {$range} ({$period['label']}, site time zone " . wp_timezone_string() . ")\n";
	$md .= '- **Filters:** ' . ($filter_text ? implode(', ', $filter_text) : 'none (all traffic)') . "\n";
	$md .= '- **Generated:** ' . wp_date('M j, Y g:i a') . "\n";
	$md .= "- **Source:** Visitor Insights (first-party tracking in the `wholesale` theme). No names, emails or full IP addresses are included.\n\n";

	$md .= "## Instructions for Claude\n\n";
	$md .= "This report shows how visitors use {$site}, a WordPress store for custom signs (channel letters, storefront signs). Most paid traffic comes from Google Ads, and many ad visitors leave without buying. Please:\n\n";
	$md .= "1. Read the findings, funnel, problems, form abandonment and sample journeys below, and work out the most likely reasons visitors are not buying.\n";
	$md .= "2. Check each likely cause against the site's code before changing anything. All site code lives in the custom theme at `wp-content/themes/wholesale/` (see the page map below). The theme, not plugins, also owns SEO (titles, meta, schema).\n";
	$md .= "3. Fix what can be fixed in the theme: errors visitors hit, confusing or slow landing pages, missing prices or calls to action, mobile layout problems, form friction, broken links. Make the smallest changes that address the cause, and explain each one.\n";
	$md .= "4. List separately what has to be changed outside the website (Google Ads keywords, negative keywords, locations, bids, ad schedule, IP exclusions), since those cannot be changed in code.\n";
	$md .= "5. Say how to confirm each change worked in the next report (which number should move).\n\n";
	$md .= "_Small numbers (fewer than about 30 visits) are not reliable on their own; treat them as hints and look for patterns in the journeys._\n\n";

	$md .= "## Page map: page types → theme files\n\n";
	$rows = array();
	foreach (wholesale_vi_page_type_files() as $type => $files) {
		$rows[] = array($type, '`' . implode('`, `', $files) . '`');
	}
	$md .= wholesale_vi_md_table(array('Page type', 'Files (in wp-content/themes/wholesale/)'), $rows) . "\n";

	$conv = static function ($s) {
		return wholesale_vi_pct(($s['orders'] ?? 0) + ($s['leads'] ?? 0), $s['sessions'] ?? 0, 1);
	};
	$num = static function ($s, $key, $decimals = 0) {
		return number_format_i18n($s[$key] ?? 0, $decimals);
	};
	$rate = static function ($s, $key) {
		return wholesale_vi_pct($s[$key] ?? 0, $s['sessions'] ?? 0);
	};
	$time = static function ($s) {
		return wholesale_vi_duration($s['avg_engaged'] ?? 0);
	};
	$money = static function ($s) {
		return wholesale_vi_money($s['revenue'] ?? 0);
	};
	$md .= "## Key numbers\n\n";
	$md .= wholesale_vi_md_table(array('Metric', 'All traffic', 'Previous period', 'Google Ads', 'Ads previous period'), array(
		array('Visits', $num($m, 'sessions'), $num($prev, 'sessions'), $num($a, 'sessions'), $num($ap, 'sessions')),
		array('Unique visitors', $num($m, 'visitors'), $num($prev, 'visitors'), $num($a, 'visitors'), $num($ap, 'visitors')),
		array('Bounce rate', $rate($m, 'bounced'), $rate($prev, 'bounced'), $rate($a, 'bounced'), $rate($ap, 'bounced')),
		array('Left within 10 seconds', $rate($m, 'quick_exits'), $rate($prev, 'quick_exits'), $rate($a, 'quick_exits'), $rate($ap, 'quick_exits')),
		array('Avg. engaged time', $time($m), $time($prev), $time($a), $time($ap)),
		array('Avg. pages per visit', $num($m, 'avg_pages', 1), $num($prev, 'avg_pages', 1), $num($a, 'avg_pages', 1), $num($ap, 'avg_pages', 1)),
		array('Added to cart', $num($m, 's4'), $num($prev, 's4'), $num($a, 's4'), $num($ap, 's4')),
		array('Leads (calls, emails, quotes)', $num($m, 'leads'), $num($prev, 'leads'), $num($a, 'leads'), $num($ap, 'leads')),
		array('Orders', $num($m, 'orders'), $num($prev, 'orders'), $num($a, 'orders'), $num($ap, 'orders')),
		array('Revenue', $money($m), $money($prev), $money($a), $money($ap)),
		array('Conversion rate (orders + leads)', $conv($m), $conv($prev), $conv($a), $conv($ap)),
	)) . "\n";

	$md .= "## Automatic findings\n\n";
	foreach (array('All traffic' => $all['findings'], 'Google Ads' => $ads['findings']) as $label => $findings) {
		$md .= "### {$label}\n\n";
		foreach ($findings as $finding) {
			$md .= '- **[' . strtoupper($finding[0]) . '] ' . rtrim($finding[1], '.') . '.** ' . $finding[2] . "\n";
		}
		$md .= "\n";
	}

	$md .= "## Purchase funnel\n\n";
	$rows = array();
	$prev_all = $prev_ads = 0;
	foreach (wholesale_vi_stages() as $i => $label) {
		$col = 7 === $i ? 'orders' : (1 === $i ? 'sessions' : 's' . $i);
		$rows[] = array(
			$i . '. ' . $label,
			number_format_i18n($m[$col]) . ' (' . wholesale_vi_pct($m[$col], $m['sessions'], 1) . ')',
			$i > 1 ? wholesale_vi_pct($prev_all - $m[$col], $prev_all) . ' lost' : '',
			number_format_i18n($a[$col]) . ' (' . wholesale_vi_pct($a[$col], $a['sessions'], 1) . ')',
			$i > 1 ? wholesale_vi_pct($prev_ads - $a[$col], $prev_ads) . ' lost' : '',
		);
		$prev_all = $m[$col];
		$prev_ads = $a[$col];
	}
	$md .= wholesale_vi_md_table(array('Step', 'All traffic', 'Lost from previous step', 'Google Ads', 'Lost from previous step'), $rows);
	$md .= sprintf("\nLeads (calls, emails, quote requests) are counted outside the funnel: all traffic %d, Google Ads %d.\n\n", $m['leads'], $a['leads']);

	$md .= "## Landing pages (first page of each visit)\n\n### All traffic\n\n" . wholesale_vi_md_breakdown($all['landing'], 'Page → template', $template_label);
	$md .= "\n### Google Ads\n\n" . wholesale_vi_md_breakdown($ads['landing'], 'Page → template', $template_label) . "\n";

	$md .= "## Exit pages of visitors who did not buy\n\n" . wholesale_vi_md_breakdown($all['exits'], 'Last page → template', $template_label) . "\n";

	$md .= "## Problems visitors hit\n\n";
	$rows = array();
	foreach ($all['problems'] as $row) {
		$rows[] = array(wholesale_vi_event_label($row['type']), $row['label'] ?: '—', $row['hits'], $row['sessions'], wholesale_vi_error_files($row['type']));
	}
	foreach ($all['not_found'] as $row) {
		$rows[] = array('Page not found (404)', wholesale_vi_short_path($row['label'], 90), $row['hits'], $row['sessions'], 'Fix the link pointing here, or add a redirect');
	}
	$md .= wholesale_vi_md_table(array('Problem', 'Exact message / element', 'Times', 'Visitors', 'Where to look'), $rows) . "\n";

	$md .= "## Form abandonment\n\n";
	$rows = array();
	foreach ($all['forms'] as $row) {
		$rows[] = array($row['label'], $row['started'], $row['submitted'], wholesale_vi_pct($row['started'] - $row['submitted'], $row['started']), $row['left_at'] ? $row['left_at'] . ' (' . $row['left_at_n'] . ')' : '—');
	}
	$md .= wholesale_vi_md_table(array('Form', 'Started', 'Finished', 'Gave up', 'Field most people stopped at'), $rows) . "\n";

	$md .= "## What visitors click\n\n";
	$rows = array();
	foreach ($all['clicks'] as $row) {
		$rows[] = array(('click' !== $row['type'] ? wholesale_vi_event_label($row['type']) . ': ' : '') . ($row['label'] ?: '(no text)'), $row['hits'], $row['sessions'], $row['buyers']);
	}
	$md .= wholesale_vi_md_table(array('Button / link', 'Clicks', 'Visitors', 'Of them bought'), $rows) . "\n";

	$md .= "## Audience\n\n### Traffic sources\n\n" . wholesale_vi_md_breakdown($all['channels'], 'Source', static function ($row) {
		return wholesale_vi_channels()[$row['label']] ?? $row['label'];
	});
	$md .= "\n### Devices (all traffic)\n\n" . wholesale_vi_md_breakdown($all['devices'], 'Device');
	$md .= "\n### Devices (Google Ads)\n\n" . wholesale_vi_md_breakdown($ads['devices'], 'Device');
	$md .= "\n### Browsers\n\n" . wholesale_vi_md_breakdown($all['browsers'], 'Browser');
	$md .= "\n### Locations (browser time zone)\n\n" . wholesale_vi_md_breakdown(array_slice($all['timezones'], 0, 12), 'Time zone', static function ($row) {
		return ($row['label'] ?: 'Unknown') . (wholesale_vi_is_foreign_tz($row['label']) ? ' (outside US)' : '');
	}) . "\n";

	$md .= "## Google Ads detail\n\n### Keywords (utm_term)\n\n" . wholesale_vi_md_breakdown($ads['keywords'], 'Keyword');
	$md .= "\n### Campaigns (utm_campaign)\n\n" . wholesale_vi_md_breakdown($ads['campaigns'], 'Campaign');
	$rows = array();
	foreach ($ads['hours'] as $hour => $row) {
		if ($row['sessions']) {
			$rows[] = array(sprintf('%02d:00–%02d:59', $hour, $hour), $row['sessions'], $row['conversions']);
		}
	}
	$md .= "\n### Ad clicks by hour of day (site time)\n\n" . wholesale_vi_md_table(array('Hour', 'Ad clicks', 'Leads + orders'), $rows);
	$rows = array();
	foreach ($ads['repeat_clickers'] as $row) {
		$ip = false !== strpos($row['ip'], ':') ? implode(':', array_slice(explode(':', $row['ip']), 0, 2)) . ':…' : preg_replace('/\.\d+$/', '.x', $row['ip']);
		$rows[] = array($ip, $row['clicks'], $row['devices'], wholesale_vi_duration($row['avg_engaged']), $row['orders'] > 0 ? 'bought' : ($row['leads'] > 0 ? 'lead' : 'nothing'));
	}
	$md .= "\n### Repeat ad clickers (3+ ad clicks from one IP; partly masked)\n\n" . wholesale_vi_md_table(array('IP', 'Ad clicks', 'Devices', 'Avg. engaged', 'Result'), $rows);
	$md .= "\nFull IP addresses for Google Ads IP exclusions are on the Google Ads tab of Visitor Insights.\n\n";

	$md .= "## Sample journeys of visitors who showed interest but did not buy\n\nFurthest-reaching visits first. Times are minutes:seconds from the start of the visit.\n";
	$md .= wholesale_vi_md_journeys($period, $filters) . "\n";

	$md .= "## Definitions\n\n";
	$md .= "- **Visit:** one browsing session; a new visit starts after 30 idle minutes or a new ad click.\n";
	$md .= "- **Bounce:** a visit with one page, under 10 seconds engaged, no shopping step and no lead.\n";
	$md .= "- **Engaged time:** time the page was open, visible and in use (60 seconds without input stops the clock).\n";
	$md .= "- **Funnel steps:** 2 = opened a product or the sign builder; 3 = changed an option or got a price; 4 = added to cart; 5 = opened checkout; 6 = clicked pay; 7 = order placed.\n";
	$md .= "- **Lead:** clicked a phone or email link, or submitted a quote form.\n";
	$md .= "- **Rage click:** three fast clicks on the same spot, usually on something that looks clickable but does nothing.\n";

	return $md;
}

function wholesale_vi_export_url($args = array())
{
	return wp_nonce_url(add_query_arg(array_merge(array('action' => 'wholesale_vi_export'), $args), admin_url('admin-post.php')), 'wholesale_vi_export');
}

function wholesale_vi_export()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.', 403);
	}
	check_admin_referer('wholesale_vi_export');

	$md = wholesale_vi_export_markdown(wholesale_vi_period(), wholesale_vi_request_filters());

	nocache_headers();
	header('Content-Type: text/markdown; charset=utf-8');
	if (empty($_GET['inline'])) {
		$file = 'visitor-insights-' . wp_parse_url(home_url(), PHP_URL_HOST) . '-' . wp_date('Y-m-d') . '.md';
		header('Content-Disposition: attachment; filename="' . sanitize_file_name($file) . '"');
	}
	echo $md; // phpcs:ignore WordPress.Security.EscapeOutput -- plain-text download.
	exit;
}
add_action('admin_post_wholesale_vi_export', 'wholesale_vi_export');

/**
 * Export buttons on the Overview and Google Ads tabs, for the range and
 * filters being viewed.
 */
function wholesale_vi_render_export_bar($period, $filters)
{
	$args = array_filter(array(
		'period' => $period['key'],
		'from' => 'custom' === $period['key'] ? $period['start']->format('Y-m-d') : '',
		'to' => 'custom' === $period['key'] ? $period['end']->modify('-1 second')->format('Y-m-d') : '',
		'channel' => $filters['channel'] ?? '',
		'device' => $filters['device'] ?? '',
	));
	?>
	<div class="wvi-export wvi-card">
		<span class="wvi-export-icon dashicons dashicons-media-text"></span>
		<div class="wvi-export-text">
			<strong>Export insights report</strong>
			<span class="wvi-muted"><?php echo esc_html($period['label']); ?> · all traffic and Google Ads · a Markdown file to give to Claude, which can then find and fix what stops visitors from buying. No names, emails or full IPs.</span>
		</div>
		<a class="button" href="<?php echo esc_url(wholesale_vi_export_url($args)); ?>"><span class="dashicons dashicons-download"></span> Download .md</a>
		<button type="button" class="button button-primary wvi-copy-report" data-url="<?php echo esc_url(wholesale_vi_export_url(array_merge($args, array('inline' => 1)))); ?>"><span class="dashicons dashicons-clipboard"></span> <span>Copy for Claude</span></button>
	</div>
	<?php
}
