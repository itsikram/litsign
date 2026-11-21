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

const RETAILS_VI_DB_VERSION = '1.0';
const RETAILS_VI_OFF_COOKIE = 'sso_vi_off';
const RETAILS_VI_SESSION_COOKIE = 'sso_sid';
const RETAILS_VI_VISITOR_COOKIE = 'sso_vid';
const RETAILS_VI_MAX_EVENTS_PER_SESSION = 1500;
// Bump to re-run retails_vi_fix_data() once on each site.
const RETAILS_VI_DATA_REV = '2026-11-09';

/* ---------------------------------------------------------------------------
 * Settings and storage
 * ------------------------------------------------------------------------ */

function retails_vi_settings()
{
	$saved = get_option('retails_vi_settings', array());

	return wp_parse_args(is_array($saved) ? $saved : array(), array(
		'enabled' => 1,
		'exclude_admins' => 1,
		'mask_ip' => 0,
		'retention_days' => 120,
		'excluded_ips' => '',
		// Ad links send the campaign ID ({campaignid}); these names replace it in the
		// reports. They add to (and override) retails_vi_known_campaigns().
		'campaign_names' => '',
	));
}

/**
 * Campaigns in the Google Ads account (Nov 9, 2026), so every ad click shows a
 * name even before anyone fills in Settings. Lines in Settings add to these or
 * override them; a campaign made later only needs its line there.
 */
function retails_vi_known_campaigns()
{
	return array(
		'24253204713' => 'SSO - Channel Letters - USA',
		'24352872665' => 'SSO - Channel Letters - WA',
		// Was the Banners & Displays Search campaign until Nov 2, 2026.
		'24321177008' => 'SSO - Business Intent - USA (was Banners & Displays Search)',
		'24347758452' => 'SSO - Window Graphics & Vinyl - USA',
		'24334436525' => 'SSO - Brand - USA',
		'24399941678' => 'SSO - Shopping - Banners & Displays - USA',
		'24449162107' => 'SSO - PMax - Banners & Displays - USA',
		'24438569001' => 'SSO - PMax - Channel Letters - USA',
		'24449655346' => 'SSO - YouTube - Channel Letters Video - USA',
		'24429570132' => 'Sales-Performance Max-1',
		// Early ads sent a name instead of the ID.
		'sso-window-graphics-vinyl-usa' => 'SSO - Window Graphics & Vinyl - USA',
		'sso-channel-letters-wa' => 'SSO - Channel Letters - WA',
	);
}

/**
 * Campaign ID => name: the built-in list plus the Settings list.
 */
function retails_vi_campaign_names()
{
	static $names = null;
	if (null === $names) {
		$names = retails_vi_known_campaigns();
		foreach (preg_split('/\r\n|\r|\n/', (string) retails_vi_setting('campaign_names')) as $line) {
			$parts = array_map('trim', explode('=', $line, 2));
			if (2 === count($parts) && '' !== $parts[0] && '' !== $parts[1]) {
				$names[$parts[0]] = $parts[1];
			}
		}
	}

	return $names;
}

/**
 * A campaign's name. An ID with no name reads "Campaign 24449…" (name it under
 * Settings); a name sent as a slug is made readable.
 */
function retails_vi_campaign_name($campaign)
{
	$campaign = trim((string) $campaign);
	$names = retails_vi_campaign_names();
	if (isset($names[$campaign])) {
		return $names[$campaign];
	}
	if (isset($names[strtolower($campaign)])) {
		return $names[strtolower($campaign)];
	}
	if (preg_match('/^\d{6,}$/', $campaign)) {
		return 'Campaign ' . $campaign;
	}
	if ('' !== $campaign && false === strpos($campaign, ' ') && preg_match('/[-_+]/', $campaign)) {
		$words = explode(' ', ucwords(strtolower(str_replace(array('-', '_', '+'), ' ', $campaign))));
		$upper = array('Sso', 'Usa', 'Us', 'Wa', 'Ca', 'Cl', 'Led', 'Pmax', 'Seo', 'Ai');
		foreach ($words as &$word) {
			if (in_array($word, $upper, true)) {
				$word = 'Pmax' === $word ? 'PMax' : strtoupper($word);
			}
		}
		return implode(' ', $words);
	}

	return $campaign;
}

/**
 * Whether a campaign has a name (built in or from Settings).
 */
function retails_vi_campaign_is_named($campaign)
{
	$names = retails_vi_campaign_names();

	return isset($names[(string) $campaign]) || isset($names[strtolower((string) $campaign)]) || !preg_match('/^\d{6,}$/', (string) $campaign);
}

/**
 * The kind of campaign, read from its name: PMax, Shopping, Video, Search.
 */
function retails_vi_campaign_type($campaign)
{
	$name = retails_vi_campaign_name($campaign);
	if (preg_match('/pmax|performance max/i', $name)) {
		return 'PMax';
	}
	if (preg_match('/shopping/i', $name)) {
		return 'Shopping';
	}
	if (preg_match('/youtube|demand gen|video/i', $name)) {
		return 'Video';
	}
	if (retails_vi_campaign_is_named($campaign) && '' !== trim((string) $campaign)) {
		return 'Search';
	}

	return '';
}

/**
 * Campaign name with its type and, when it differs, the ID as sent.
 */
function retails_vi_campaign_html($campaign, $with_id = true)
{
	$campaign = (string) $campaign;
	if ('' === $campaign || '(not set)' === $campaign) {
		return '<span class="wvi-muted">(not set)</span>';
	}
	$name = retails_vi_campaign_name($campaign);
	$type = retails_vi_campaign_type($campaign);
	$html = esc_html($name);
	if ($type) {
		$html .= ' <span class="wvi-ctype is-' . esc_attr(strtolower($type)) . '">' . esc_html($type) . '</span>';
	}
	if (!retails_vi_campaign_is_named($campaign)) {
		$html .= ' <a class="wvi-muted" href="' . esc_url(retails_vi_url('retails-insights-settings')) . '#wvi-campaigns" title="Add this campaign\'s name under Settings">name it</a>';
	} elseif ($with_id && $name !== $campaign) {
		$html .= ' <span class="wvi-muted">' . esc_html($campaign) . '</span>';
	}

	return $html;
}

function retails_vi_setting($key)
{
	$settings = retails_vi_settings();

	return isset($settings[$key]) ? $settings[$key] : null;
}

function retails_vi_table($name)
{
	global $wpdb;

	return $wpdb->prefix . 'retails_vi_' . $name;
}

function retails_vi_install()
{
	if (RETAILS_VI_DB_VERSION === get_option('retails_vi_db_version')) {
		if (RETAILS_VI_DATA_REV !== get_option('retails_vi_data_rev')) {
			retails_vi_fix_data();
		}
		return;
	}

	global $wpdb;
	$sessions = retails_vi_table('sessions');
	$events = retails_vi_table('events');
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

	update_option('retails_vi_db_version', RETAILS_VI_DB_VERSION);
	retails_vi_fix_data();
}
add_action('init', 'retails_vi_install');

/**
 * Corrections to visits recorded before the tracker knew better: ChatGPT and
 * other AI assistants were counted as plain referrals. Safe to run again; it
 * also runs with the daily clean-up, because copying wp_options between the
 * live and local sites can carry the "done" flag without the visits.
 */
function retails_vi_fix_data()
{
	global $wpdb;
	$sessions = retails_vi_table('sessions');
	if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $sessions)) !== $sessions) {
		return;
	}

	$ai = retails_vi_ai_regex();
	$wpdb->query("UPDATE {$sessions} SET channel = 'ai', medium = IF(medium IN ('', 'referral'), 'ai', medium) WHERE channel = 'referral' AND (source REGEXP '{$ai}' OR referrer REGEXP '{$ai}')"); // phpcs:ignore WordPress.DB.PreparedSQL -- constant pattern.

	update_option('retails_vi_data_rev', RETAILS_VI_DATA_REV);
}

/**
 * Hosts of AI assistants (ChatGPT, Copilot, Perplexity...), as a pattern that
 * works in both PHP (with # delimiters) and MySQL REGEXP.
 */
function retails_vi_ai_regex()
{
	return '(^|[/.])(chatgpt[.]com|chat[.]openai[.]com|perplexity[.]ai|copilot[.]microsoft[.]com|copilot[.]com|gemini[.]google[.]com|bard[.]google[.]com|claude[.]ai|meta[.]ai|you[.]com|phind[.]com|poe[.]com|deepseek[.]com|grok[.]com|mistral[.]ai)([/:?]|$)';
}

/**
 * Delete visits older than the retention period, once a day.
 */
function retails_vi_schedule_purge()
{
	if (!wp_next_scheduled('retails_vi_purge')) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'retails_vi_purge');
	}
}
add_action('init', 'retails_vi_schedule_purge');

function retails_vi_purge_old()
{
	global $wpdb;
	$days = max(7, (int) retails_vi_setting('retention_days'));
	$cutoff = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
	$sessions = retails_vi_table('sessions');
	$events = retails_vi_table('events');

	$wpdb->query($wpdb->prepare("DELETE FROM {$events} WHERE created_at < %s", $cutoff));
	$wpdb->query($wpdb->prepare("DELETE FROM {$sessions} WHERE last_seen < %s", $cutoff));
	retails_vi_fix_data();
}
add_action('retails_vi_purge', 'retails_vi_purge_old');

/* ---------------------------------------------------------------------------
 * Front end: tracker
 * ------------------------------------------------------------------------ */

/**
 * Whether this request's visitor should be left out of the stats.
 */
function retails_vi_is_excluded()
{
	if (!retails_vi_setting('enabled') || !empty($_COOKIE[RETAILS_VI_OFF_COOKIE])) {
		return true;
	}
	if (retails_vi_setting('exclude_admins') && is_user_logged_in() && current_user_can('edit_posts')) {
		return true;
	}

	$excluded = preg_split('/[\s,]+/', (string) retails_vi_setting('excluded_ips'), -1, PREG_SPLIT_NO_EMPTY);

	return $excluded && in_array(retails_vi_client_ip(false), $excluded, true);
}

/**
 * Staff browsing the site after visiting wp-admin get a cookie that keeps
 * their own visits out of the stats, even when logged out.
 */
function retails_vi_mark_staff_browser()
{
	if (wp_doing_ajax() || headers_sent() || !current_user_can('edit_posts')) {
		return;
	}

	$wanted = (bool) retails_vi_setting('exclude_admins');
	$has = !empty($_COOKIE[RETAILS_VI_OFF_COOKIE]);
	if ($wanted === $has) {
		return;
	}

	setcookie(RETAILS_VI_OFF_COOKIE, $wanted ? '1' : '', array(
		'expires' => $wanted ? time() + YEAR_IN_SECONDS : time() - HOUR_IN_SECONDS,
		'path' => '/',
		'secure' => is_ssl(),
		'httponly' => false,
		'samesite' => 'Lax',
	));
}
add_action('admin_init', 'retails_vi_mark_staff_browser');

/**
 * The kind of page being viewed, for the funnel and the reports.
 */
function retails_vi_page_type()
{
	if (get_query_var('retails_thank_you')) {
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

function retails_vi_enqueue_tracker()
{
	if (is_admin() || is_customize_preview() || is_preview() || retails_vi_is_excluded()) {
		return;
	}

	$path = '/js/visitor-tracker.js';
	$file = get_template_directory() . $path;
	wp_enqueue_script('retails-vi', get_template_directory_uri() . $path, array(), file_exists($file) ? (string) filemtime($file) : _S_VERSION, array('in_footer' => true, 'strategy' => 'defer'));

	$ptype = retails_vi_page_type();
	$cart_items = 0;
	if ('cart' === $ptype && function_exists('retails_get_cart')) {
		$cart_items = count((array) retails_get_cart()->get_items());
	}

	$snap_scripts = function_exists('retails_vi_snap_scripts') ? retails_vi_snap_scripts() : null;
	wp_localize_script('retails-vi', 'retailsVI', array_filter(array(
		'endpoint' => rest_url('retails/v1/vi'),
		'ptype' => $ptype,
		'cart' => $cart_items,
		// Admin "Take screenshot" (inc/visitor-snaps.php): loaded only when asked.
		'snap' => $snap_scripts ? $snap_scripts['capture'] : '',
		'snapEndpoint' => $snap_scripts ? rest_url('retails/v1/vi-snap') : '',
		// Automatic screenshots on errors and leaving (inc/screenshots.php).
		'shots' => function_exists('retails_shots_tracker_config') ? retails_shots_tracker_config() : '',
	), static function ($value) {
		return '' !== $value;
	}));
}
add_action('wp_enqueue_scripts', 'retails_vi_enqueue_tracker', 20);

/* ---------------------------------------------------------------------------
 * Collection endpoint
 * ------------------------------------------------------------------------ */

function retails_vi_register_route()
{
	register_rest_route('retails/v1', '/vi', array(
		'methods' => 'POST',
		'callback' => 'retails_vi_rest_collect',
		'permission_callback' => '__return_true',
	));
}
add_action('rest_api_init', 'retails_vi_register_route');

/**
 * The visitor's IP address. Only Cloudflare's header is trusted; any other
 * forwarded-for header can be set by the visitor.
 */
function retails_vi_client_ip($maybe_mask = true)
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

	if ($maybe_mask && retails_vi_setting('mask_ip')) {
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$ip = preg_replace('/\.\d+$/', '.0', $ip);
		} else {
			$ip = implode(':', array_slice(explode(':', $ip), 0, 3)) . '::';
		}
	}

	return $ip;
}

function retails_vi_is_bot($ua)
{
	return '' === $ua || (bool) preg_match('/bot|crawl|spider|slurp|mediapartners|adsbot|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|preview|python|curl|wget|httpclient|okhttp|java\/|go-http|phantom|selenium|puppeteer|playwright|scrapy|facebookexternalhit|bingpreview/i', $ua);
}

/**
 * Whether an IP is in Google's own crawler and fetcher ranges. Google checks ad landing
 * pages with a normal Chrome user agent and a gclid, so these looked like ad clicks:
 * 64.233.172.x opened /, /signup/ and /about/ in the same second (Oct 27 report, flagged
 * as a "repeat ad clicker"). People browse from their ISP, not from these ranges.
 */
function retails_vi_is_google_ip($ip)
{
	if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return false;
	}

	$long = ip2long($ip);
	foreach (retails_vi_google_ranges() as $range) {
		list($subnet, $bits) = explode('/', $range);
		$mask = -1 << (32 - (int) $bits);
		if ((ip2long($subnet) & $mask) === ($long & $mask)) {
			return true;
		}
	}

	return false;
}

function retails_vi_google_ranges()
{
	return array('64.233.160.0/19', '66.102.0.0/20', '66.249.64.0/19', '72.14.192.0/18', '74.125.0.0/16', '209.85.128.0/17', '216.239.32.0/19');
}

/**
 * Screen sizes that only automated browsers report on a Linux desktop: the
 * square 1024x1024 window of Google's page renderer and of crawlers that copy
 * it, and headless Chrome's 800x600. 250+ such "direct" visits (Oct 6 – Nov 1)
 * had 0 seconds engaged, made a third of all direct traffic and inflated the
 * bounce rate.
 */
function retails_vi_robot_screens()
{
	return array('1024x1024', '1366x1024', '1280x1280', '1200x2000', '800x600');
}

/**
 * Whether a new visit is from an automated browser that passes as a person
 * (a normal Chrome user agent, but a robot's screen).
 *
 * @param array  $agent  retails_vi_parse_ua() result.
 * @param string $screen "WIDTHxHEIGHT" of the screen.
 */
function retails_vi_looks_automated($agent, $screen)
{
	if (!preg_match('/^(\d+)x(\d+)$/', (string) $screen, $size)) {
		return false;
	}
	$linux_desktop = 'desktop' === $agent['device'] && in_array($agent['os'], array('Linux', 'Other'), true);

	return ($linux_desktop && in_array($screen, retails_vi_robot_screens(), true))
		// Phones are never square.
		|| ('Android' === $agent['os'] && '1024x1024' === $screen)
		|| ('desktop' === $agent['device'] && 'Other' === $agent['os'] && 'Other' === $agent['browser'])
		// Full-page screenshot services report the whole page as the screen.
		|| (int) $size[2] > 6000 || (int) $size[1] > 8000;
}

/**
 * SQL that is true for a recorded visit that was a robot (see
 * retails_vi_looks_automated()), a Google checker, or a server: hidden from
 * every report. Visits with an order or a lead are always kept.
 */
function retails_vi_automated_sql($alias = 's')
{
	$a = $alias;
	$screens = "'" . implode("', '", retails_vi_robot_screens()) . "'";
	$google = array();
	foreach (retails_vi_google_ranges() as $range) {
		list($subnet, $bits) = explode('/', $range);
		$start = ip2long($subnet);
		// INET_ATON() is NULL for IPv6, and a NULL here used to drop the visit
		// from every report (neither human nor robot). The last part after ':'
		// keeps IPv4-mapped addresses (::ffff:1.2.3.4) checkable.
		$google[] = sprintf("COALESCE(INET_ATON(SUBSTRING_INDEX(%s.ip, ':', -1)), 0) BETWEEN %d AND %d", $a, $start, $start + (1 << (32 - (int) $bits)) - 1);
	}

	// COALESCE: an unknown (NULL) check means "not a robot", so the visit is counted.
	return "COALESCE(({$a}.order_id = 0 AND {$a}.is_lead = 0 AND (
		({$a}.device = 'desktop' AND {$a}.os IN ('Linux', 'Other') AND {$a}.screen IN ({$screens}))
		OR ({$a}.os = 'Android' AND {$a}.screen = '1024x1024')
		OR ({$a}.device = 'desktop' AND {$a}.os = 'Other' AND {$a}.browser = 'Other')
		OR CAST(SUBSTRING_INDEX({$a}.screen, 'x', -1) AS UNSIGNED) > 6000
		OR ({$a}.device = 'desktop' AND {$a}.os = 'Linux' AND {$a}.timezone IN ('UTC', 'Etc/UTC') AND {$a}.engaged_seconds < 5)
		OR " . implode(' OR ', $google) . ')), 0)';
}

/**
 * Device, browser and operating system from the user agent.
 */
function retails_vi_parse_ua($ua, $touch_points = 0)
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

	// In-app browsers first: ads on YouTube, Gmail and Discover open inside those
	// apps, which is why so many phone visits used to read "Other".
	$browser = 'Other';
	foreach (array(
		'/FBAN|FBAV/' => 'Facebook app',
		'/Instagram/' => 'Instagram app',
		'/com\.google\.ios\.youtube|YouTube\//i' => 'YouTube app',
		'/GmailApp|com\.google\.Gmail/i' => 'Gmail app',
		'/GSA\//' => 'Google app',
		'/LinkedInApp/i' => 'LinkedIn app',
		'/Pinterest/i' => 'Pinterest app',
		'/Snapchat/i' => 'Snapchat app',
		'/musical_ly|BytedanceWebview|TikTok/i' => 'TikTok app',
		'/Twitter|TwitterAndroid/i' => 'X (Twitter) app',
		'/WhatsApp/i' => 'WhatsApp',
		'/DuckDuckGo/i' => 'DuckDuckGo',
		'/; wv\)/' => 'In-app browser',
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
	// iPhone apps show pages in a web view whose user agent lacks "Safari/".
	if ('Other' === $browser && 'iOS' === $os && preg_match('/AppleWebKit/', $ua)) {
		$browser = 'In-app browser';
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
function retails_vi_clean_url($url)
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
function retails_vi_attribution($campaign, $referrer)
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
	// Coming back from a payment or sign-in page is the same visitor carrying
	// on, not a referral from that site.
	if (preg_match('/(^|\.)(stripe\.com|paypal\.com|squareup\.com|square\.link|authorize\.net|klarna\.com|affirm\.com|afterpay\.com|accounts\.google\.com|appleid\.apple\.com)$/', $ref_host)) {
		$ref_host = '';
	}
	$is_ai = (bool) preg_match('#' . retails_vi_ai_regex() . '#i', $source) || (bool) preg_match('#' . retails_vi_ai_regex() . '#i', $ref_host);
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
	} elseif ($is_ai) {
		// ChatGPT adds utm_source=chatgpt.com to the links it gives.
		$channel = 'ai';
		$source = $source ?: $ref_host;
		$medium = $medium ?: 'ai';
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
		// Google adds gad_campaignid to ad clicks itself, so it names the
		// campaign of ads that lack the tracking suffix.
		'campaign' => ($campaign['utm_campaign'] ?? '') ?: ($campaign['gad_campaignid'] ?? ''),
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
function retails_vi_request_user_id()
{
	$user_id = get_current_user_id();
	if (!$user_id && defined('LOGGED_IN_COOKIE') && !empty($_COOKIE[LOGGED_IN_COOKIE])) {
		$user_id = (int) wp_validate_auth_cookie(wp_unslash($_COOKIE[LOGGED_IN_COOKIE]), 'logged_in');
	}

	return $user_id;
}

function retails_vi_find_session($session_key)
{
	global $wpdb;
	$table = retails_vi_table('sessions');

	return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE session_key = %s", $session_key));
}

/**
 * The visit for this request, created from its first page view if new.
 */
function retails_vi_open_session($session_key, $visitor_key, $data, $events, $ua)
{
	global $wpdb;
	$session = retails_vi_find_session($session_key);
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
	$attribution = retails_vi_attribution($first['campaign'] ?? array(), $referrer);
	$agent = retails_vi_parse_ua($ua, (int) ($data['tp'] ?? 0));
	// Every request carries the screen size, so a robot is turned away even when
	// its first page view never arrived.
	$screen = substr(preg_replace('/[^0-9x]/', '', (string) ($first['screen'] ?? ($data['sc'] ?? ''))), 0, 20);
	if (retails_vi_looks_automated($agent, $screen)) {
		return null;
	}
	if (!$first && !empty($data['tz'])) {
		$first['tz'] = $data['tz'];
	}
	$country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper(substr(sanitize_key(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])), 0, 2)) : '';
	$ago = min(max(0, (int) ($first['ago'] ?? 0)), HOUR_IN_SECONDS * 1000);
	$now = gmdate('Y-m-d H:i:s', time() - (int) round($ago / 1000));

	// A returning visitor is anyone seen before, even if their cookie says new.
	$table = retails_vi_table('sessions');
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
		retails_vi_clean_url($first['url'] ?? ($data['url'] ?? '/')),
		sanitize_key($first['ptype'] ?? ''),
		retails_vi_clean_url($first['url'] ?? ($data['url'] ?? '/')),
		$agent['device'],
		$agent['browser'],
		$agent['os'],
		$screen,
		preg_match('/^[A-Z]{2}$/', $country) && 'XX' !== $country ? $country : '',
		substr(preg_replace('#[^A-Za-z0-9_/+-]#', '', (string) ($first['tz'] ?? '')), 0, 60),
		substr(preg_replace('/[^A-Za-z-]/', '', (string) ($first['lang'] ?? '')), 0, 20),
		retails_vi_client_ip(),
		retails_vi_request_user_id()
	));

	return retails_vi_find_session($session_key);
}

/**
 * Funnel stage an event proves the visitor reached.
 *
 * 1 landed, 2 viewed a product, 3 configured or priced it, 4 added to cart,
 * 5 reached checkout, 6 tried to pay, 7 purchased.
 */
function retails_vi_event_stage($type, $event)
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

function retails_vi_event_types()
{
	return array(
		'pageview', 'click', 'call_click', 'email_click', 'rage_click', 'configure', 'price_quote', 'price_error',
		'design_upload', 'upload_error', 'add_to_cart', 'cart_error', 'cart_remove', 'form_start', 'form_submit',
		'payment_start', 'checkout_error', 'payment_error', 'js_error', 'purchase', 'quote_submit',
	);
}

function retails_vi_rest_collect(WP_REST_Request $request)
{
	$done = new WP_REST_Response(null, 204);
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr(wp_unslash($_SERVER['HTTP_USER_AGENT']), 0, 500) : '';

	if (retails_vi_is_excluded() || retails_vi_is_bot($ua) || retails_vi_is_google_ip(retails_vi_client_ip(false))) {
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

	$user_id = retails_vi_request_user_id();
	if ($user_id && retails_vi_setting('exclude_admins') && user_can($user_id, 'edit_posts')) {
		return $done;
	}

	// An agent started a chat with this visitor from Live Chat's Live visitors
	// tab: tell the page to open the chat (visitor-tracker.js). Reads an
	// autoloaded option, so no extra query on ordinary pings.
	$reply = array();
	if (apply_filters('sfs_chat_invite_pending', false, $data['v'])) {
		$reply['chat'] = 1;
	}
	// An admin pressed "Take screenshot" on this visit: the page sends a copy of
	// itself (inc/visitor-snaps.php). Also reads an autoloaded option only.
	$snap = function_exists('retails_vi_snap_pending') ? retails_vi_snap_pending($data['s']) : '';
	if ('' !== $snap) {
		$reply['snap'] = $snap;
	}
	if ($reply) {
		$done->set_status(200);
		$done->set_data($reply);
	}

	// Safari keeps a cookie written by a page script for 1 to 7 days, so a
	// customer coming back later would count as new; set by the server, the
	// visitor ID lasts its full year.
	if (!headers_sent()) {
		setcookie(RETAILS_VI_VISITOR_COOKIE, $data['v'], array(
			'expires' => time() + YEAR_IN_SECONDS,
			'path' => '/',
			'secure' => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		));
	}

	$events = array_slice(array_values(array_filter((array) ($data['events'] ?? array()), 'is_array')), 0, 40);
	$session = retails_vi_open_session($data['s'], $data['v'], $data, $events, $ua);
	if (!$session || (int) $session->events >= RETAILS_VI_MAX_EVENTS_PER_SESSION) {
		return $done;
	}

	global $wpdb;
	// Customers who log in partway through a visit.
	if ($user_id && (int) $session->user_id !== $user_id) {
		$wpdb->update(retails_vi_table('sessions'), array('user_id' => $user_id), array('id' => (int) $session->id), array('%d'), array('%d'));
	}
	$events_table = retails_vi_table('events');
	$sessions_table = retails_vi_table('sessions');
	$page_key = preg_match('/^[a-z0-9]{8}$/', (string) ($data['p'] ?? '')) ? $data['p'] : '';
	$page_url = retails_vi_clean_url($data['url'] ?? '/');
	$allowed = retails_vi_event_types();
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
		$url = isset($event['url']) ? retails_vi_clean_url($event['url']) : $page_url;
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
		$stage = max($stage, retails_vi_event_stage($type, $event));
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
function retails_vi_current_session()
{
	$key = isset($_COOKIE[RETAILS_VI_SESSION_COOKIE]) ? sanitize_key(wp_unslash($_COOKIE[RETAILS_VI_SESSION_COOKIE])) : '';
	$session = preg_match('/^[a-f0-9]{32}$/', $key) ? retails_vi_find_session($key) : null;
	if ($session) {
		return $session;
	}

	// The visit cookie lapses after 30 idle minutes, but a page left open (a
	// checkout while the customer finds their card) still belongs to that
	// visit: use the visitor's latest one.
	$visitor = isset($_COOKIE[RETAILS_VI_VISITOR_COOKIE]) ? sanitize_key(wp_unslash($_COOKIE[RETAILS_VI_VISITOR_COOKIE])) : '';
	if (!preg_match('/^[a-f0-9]{32}$/', $visitor)) {
		return null;
	}

	global $wpdb;

	return $wpdb->get_row($wpdb->prepare(
		'SELECT * FROM ' . retails_vi_table('sessions') . ' WHERE visitor_key = %s AND last_seen >= %s ORDER BY last_seen DESC LIMIT 1',
		$visitor,
		gmdate('Y-m-d H:i:s', time() - 12 * HOUR_IN_SECONDS)
	));
}

function retails_vi_log_server_event($session, $type, $label, $value, $updates)
{
	global $wpdb;

	$wpdb->insert(retails_vi_table('events'), array(
		'session_id' => (int) $session->id,
		'created_at' => current_time('mysql', true),
		'type' => $type,
		'url' => isset($_SERVER['HTTP_REFERER']) ? retails_vi_clean_url(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
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

	$wpdb->query($wpdb->prepare('UPDATE ' . retails_vi_table('sessions') . ' SET ' . implode(', ', $set) . ' WHERE id = %d', $params));
}

function retails_vi_record_order($order)
{
	$session = retails_vi_is_excluded() ? null : retails_vi_current_session();
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

	retails_vi_log_server_event($session, 'purchase', 'Order #' . get_post_meta($order, 'order_id', true), (string) $order, $updates);

	// Saved on the order now: the visits behind it are deleted after the
	// retention period.
	retails_vi_order_source($order);
}
add_action('retails_order_created', 'retails_vi_record_order');

function retails_vi_record_quote($post_id, $post, $update)
{
	if ($update || wp_is_post_revision($post_id) || is_admin()) {
		return;
	}

	$session = retails_vi_is_excluded() ? null : retails_vi_current_session();
	if (!$session) {
		return;
	}

	update_post_meta($post_id, '_vi_session_id', (int) $session->id);
	retails_vi_log_server_event($session, 'quote_submit', wp_strip_all_tags($post->post_title), (string) $post_id, array('is_lead = %d' => 1));

	// Saved on the request now, like an order's source: the visits behind it
	// are deleted after the retention period.
	if (function_exists('retails_vi_lead_source')) {
		retails_vi_lead_source($post_id);
	}
}
add_action('save_post_contact_submission', 'retails_vi_record_quote', 10, 3);

/* ---------------------------------------------------------------------------
 * Where each order came from
 * ------------------------------------------------------------------------ */

/**
 * The visit an order was placed in, with the time of the purchase. Found by
 * the purchase event, which names the order number: copying the database
 * between the live and local sites can renumber posts and reuse visit IDs, so
 * the order's _vi_session_id alone may point at someone else's visit.
 */
function retails_vi_order_session($order_id)
{
	global $wpdb;
	$number = (string) get_post_meta($order_id, 'order_id', true);
	if ('' === $number) {
		return null;
	}

	$purchase = $wpdb->get_row($wpdb->prepare(
		'SELECT session_id, created_at FROM ' . retails_vi_table('events') . " WHERE type = 'purchase' AND label = %s ORDER BY session_id = %d DESC, id DESC LIMIT 1",
		substr('Order #' . $number, 0, 255),
		(int) get_post_meta($order_id, '_vi_session_id', true)
	));
	$session = $purchase ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . retails_vi_table('sessions') . ' WHERE id = %d', (int) $purchase->session_id)) : null;
	if ($session) {
		$session->ordered_at = $purchase->created_at;
	}

	return $session;
}

/**
 * Where an order's customer came from, credited the way Google Ads credits a
 * sale: the last ad click in the 90 days up to the purchase, else the last
 * visit that was not direct (a search, a referring site), else the visit the
 * order was placed in. Earlier visits count when they are from the same
 * browser or the customer's account.
 *
 * Saved on the order the first time, so it outlives the visits.
 *
 * @return array|null Null when no visit was recorded for the order.
 */
function retails_vi_order_source($order_id)
{
	$order_id = (int) $order_id;
	$saved = get_post_meta($order_id, '_vi_source', true);
	if (is_array($saved) && !empty($saved['channel'])) {
		return retails_vi_normalize_source($saved);
	}

	$buy = retails_vi_order_session($order_id);
	$source = $buy ? retails_vi_credit_source($buy) : null;

	// A customer who asked for a quote first often pays later from another
	// device or through a payment link, so the order's own visit says "direct"
	// or nothing. Their quote request knows where they came from.
	if ((!$source || 'direct' === $source['channel']) && function_exists('retails_vi_order_lead')) {
		$lead = retails_vi_order_lead($order_id);
		$lead_source = $lead ? retails_vi_lead_source($lead) : null;
		if ($lead_source && 'direct' !== $lead_source['channel']) {
			$source = array_merge($lead_source, array(
				'via_lead' => (int) $lead,
				'lead_at' => get_post_field('post_date_gmt', $lead),
				'ordered_at' => $buy ? $buy->ordered_at : get_post_field('post_date_gmt', $order_id),
				'order_visit_id' => $buy ? (int) $buy->id : (int) $lead_source['order_visit_id'],
				'order_channel' => $buy ? $buy->channel : 'direct',
				'device' => $buy ? $buy->device : ($lead_source['device'] ?? ''),
			));
		}
	}
	if (!$source) {
		return null;
	}

	update_post_meta($order_id, '_vi_source', $source);

	return $source;
}

/**
 * Sources saved before AI assistants had their own channel.
 */
function retails_vi_normalize_source($source)
{
	if ('referral' === ($source['channel'] ?? '') && preg_match('#' . retails_vi_ai_regex() . '#i', (string) ($source['source'] ?? ''))) {
		$source['channel'] = 'ai';
	}

	return $source + array('network' => '', 'placement' => '', 'via_lead' => 0, 'location' => '', 'from_form' => false);
}

/**
 * Who gets the credit for a sale or lead made in the visit $buy (which has
 * ->ordered_at, the time of the purchase or request): the last ad click in the
 * 90 days before, else the last visit that was not direct, else $buy itself.
 * Earlier visits count when they are from the same browser or account.
 */
function retails_vi_credit_source($buy)
{
	global $wpdb;
	$who = 'visitor_key = %s';
	$params = array($buy->visitor_key);
	$user = (int) $buy->user_id ? get_userdata((int) $buy->user_id) : false;
	if ($user) {
		// Only visits since the account was made: a copied users table can
		// give a deleted customer's ID to someone new.
		$who .= ' OR (user_id = %d AND last_seen >= %s)';
		array_push($params, $user->ID, $user->user_registered);
	}
	array_push($params, gmdate('Y-m-d H:i:s', strtotime($buy->started_at . ' UTC') - 90 * DAY_IN_SECONDS), $buy->started_at, (int) $buy->id);
	$visits = $wpdb->get_results($wpdb->prepare(
		'SELECT * FROM ' . retails_vi_table('sessions') . " WHERE (({$who}) AND started_at >= %s AND started_at <= %s) OR id = %d ORDER BY started_at DESC, id DESC LIMIT 200",
		$params
	));

	$paid = null;
	$not_direct = null;
	foreach ($visits as $visit) {
		if (!$paid && in_array($visit->channel, array('google_ads', 'paid_other'), true)) {
			$paid = $visit;
		}
		if (!$not_direct && 'direct' !== $visit->channel) {
			$not_direct = $visit;
		}
	}
	$credit = $paid ?: ($not_direct ?: $buy);

	return array(
		'channel' => $credit->channel,
		'source' => $credit->source,
		'keyword' => $credit->term,
		'campaign' => $credit->campaign,
		'ad_group' => $credit->content,
		'landing' => $credit->landing_url,
		'network' => retails_vi_ad_network($credit),
		'placement' => retails_vi_ad_placement($credit),
		'visit_id' => (int) $credit->id,
		'visit_at' => $credit->started_at,
		'order_visit_id' => (int) $buy->id,
		'order_channel' => $buy->channel,
		'ordered_at' => $buy->ordered_at,
		'device' => $buy->device,
		'location' => retails_vi_location($buy),
		'visits' => count($visits),
		'first_at' => $visits ? end($visits)->started_at : $buy->started_at,
		'via_lead' => 0,
		'from_form' => false,
	);
}

/**
 * The order's source and keyword in a line or two, for the order list and
 * the Visitor Insights orders table.
 */
function retails_vi_order_source_html($order_id)
{
	$source = retails_vi_order_source($order_id);
	if (!$source) {
		return get_post_meta($order_id, '_ads_click_id', true)
			? retails_vi_channel_badge('google_ads') . '<span class="wvi-sub" title="The order has a Google Ads click ID, but no recorded visit">Keyword not recorded</span>'
			: '<span class="wvi-muted" title="Placed before Visitor Insights started, by staff, or from a browser that blocks tracking">Not tracked</span>';
	}

	$paid = in_array($source['channel'], array('google_ads', 'paid_other'), true);
	$html = retails_vi_channel_badge($source['channel']);
	if ('' !== $source['keyword']) {
		$html .= ' <strong>“' . esc_html($source['keyword']) . '”</strong>';
	} elseif ($paid) {
		$html .= ' <span class="wvi-muted" title="Google sends no keyword for Display, Search partner and Performance Max clicks">no keyword</span>';
	} elseif ('direct' !== $source['channel'] && '' !== $source['source']) {
		$html .= ' <span class="wvi-muted">' . esc_html($source['source']) . '</span>';
	}

	$details = array();
	if ('' !== $source['campaign']) {
		$details[] = retails_vi_campaign_name($source['campaign']);
	}
	if ($paid && '' !== $source['network']) {
		$details[] = retails_vi_ad_networks()[$source['network']] ?? $source['network'];
	}
	if ($source['via_lead']) {
		$details[] = 'Asked for a quote ' . retails_vi_date($source['lead_at'] ?? '', 'M j') . ' first';
	} elseif ($source['visit_id'] !== $source['order_visit_id']) {
		$details[] = retails_vi_time_between($source['visit_at'], $source['ordered_at']) . ' before ordering';
	}
	if (!$paid && retails_vi_order_has_unlinked_ad_click($order_id, $source)) {
		$details[] = 'Earlier Google ad click, keyword unknown';
	}

	return $html . ($details ? '<span class="wvi-sub">' . esc_html(implode(' · ', $details)) . '</span>' : '');
}

/**
 * Whether the order has a Google Ads click ID (kept in a 90-day cookie) that
 * no recorded ad visit accounts for: the ad visit was in another browser,
 * before cookies were cleared, or not recorded.
 */
function retails_vi_order_has_unlinked_ad_click($order_id, $source)
{
	return (!$source || !in_array($source['channel'], array('google_ads', 'paid_other'), true)) && '' !== (string) get_post_meta($order_id, '_ads_click_id', true);
}

function retails_vi_time_between($from_gmt, $to_gmt)
{
	return human_time_diff(strtotime($from_gmt . ' UTC'), strtotime($to_gmt . ' UTC'));
}

/**
 * Order screen: where the customer came from and the keyword of their ad.
 */
function retails_vi_order_source_box($post)
{
	$source = retails_vi_order_source($post->ID);
	if (!$source) {
		echo '<p>' . esc_html(get_post_meta($post->ID, '_ads_click_id', true)
			? 'This order came from a Google ad (see the Google Ads box), but no visit was recorded for it, so the keyword is unknown.'
			: 'No visit was recorded for this order. It was placed before Visitor Insights started, by staff, or from a browser that blocks tracking.') . '</p>';
		return;
	}

	$paid = in_array($source['channel'], array('google_ads', 'paid_other'), true);
	$facts = array('Source' => retails_vi_channel_badge($source['channel']) . ('' !== $source['source'] && 'google_ads' !== $source['channel'] ? ' ' . esc_html($source['source']) : ''));
	if ('' !== $source['keyword']) {
		$facts['Keyword'] = '<strong>“' . esc_html($source['keyword']) . '”</strong>';
	} elseif ($paid) {
		$facts['Keyword'] = '<span class="wvi-muted">Not sent. Google sends none for Display, Search partner and Performance Max clicks.</span>';
	} elseif ('organic' === $source['channel']) {
		$facts['Keyword'] = '<span class="wvi-muted">Search engines do not share search words.</span>';
	}
	if ('' !== $source['campaign']) {
		$facts['Campaign'] = retails_vi_campaign_html($source['campaign']);
	}
	if ($paid && '' !== $source['network']) {
		$facts['Ad shown on'] = esc_html(retails_vi_ad_networks()[$source['network']] ?? $source['network']) . ('' !== $source['placement'] ? ' <span class="wvi-muted">' . esc_html($source['placement']) . '</span>' : '');
	}
	if ($paid && '' !== $source['ad_group']) {
		$facts['Ad group'] = '<span class="wvi-muted">' . esc_html($source['ad_group']) . '</span>';
	}
	if ($source['via_lead']) {
		$facts['Quote first'] = '<a href="' . esc_url(get_edit_post_link($source['via_lead'])) . '">Quote request</a> on ' . esc_html(retails_vi_date($source['lead_at'] ?? '', 'M j')) . '<br><span class="wvi-muted">Matched by the customer\'s email. Credited to where that request came from.</span>';
	}
	if (retails_vi_order_has_unlinked_ad_click($post->ID, $source)) {
		$facts['Google Ads'] = '<span class="wvi-muted">This browser came from a Google ad in the 90 days before the order (see the Google Ads box), but that visit was not linked, so its keyword is unknown.</span>';
	}
	$facts['Landed on'] = '<a href="' . esc_url(retails_vi_site_link($source['landing'])) . '" target="_blank" rel="noopener" class="wvi-break">' . esc_html(retails_vi_short_path(strtok($source['landing'], '?'), 60)) . '</a>';
	$facts[$paid ? 'Ad click' : 'Visit'] = esc_html(retails_vi_date($source['visit_at'])) . '<br><span class="wvi-muted">' . esc_html($source['visit_id'] === $source['order_visit_id'] ? 'Ordered in the same visit' : retails_vi_time_between($source['visit_at'], $source['ordered_at']) . ' before ordering, on a later ' . strtolower(retails_vi_channels()[$source['order_channel']] ?? $source['order_channel']) . ' visit') . '</span>';
	if ('' !== $source['location']) {
		$facts['Location'] = esc_html($source['location']) . ' <span class="wvi-muted">(from the browser\'s time zone)</span>';
	}
	$facts['Visits'] = esc_html(sprintf(_n('%d visit', '%d visits', $source['visits']), $source['visits'])) . ($source['visits'] > 1 ? '<br><span class="wvi-muted">First on ' . esc_html(retails_vi_date($source['first_at'], 'M j')) . '</span>' : '');
	?>
	<div class="wvi wvi-order-source">
		<dl class="wvi-facts">
			<?php foreach ($facts as $label => $html) : ?>
				<dt><?php echo esc_html($label); ?></dt>
				<dd><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></dd>
			<?php endforeach; ?>
		</dl>
		<?php if (current_user_can('manage_options')) : ?>
			<p><a href="<?php echo esc_url(retails_vi_url('retails-insights-visitors', array('session' => $source['order_visit_id']))); ?>">See the customer's visits →</a></p>
		<?php endif; ?>
	</div>
	<?php
}

add_action('add_meta_boxes_order', static function () {
	add_meta_box('retails-vi-source', 'Source & keyword', 'retails_vi_order_source_box', 'order', 'side', 'high');
});

/**
 * Order list: a "Source / keyword" column after the total.
 */
function retails_vi_order_columns($columns)
{
	$updated = array();
	foreach ($columns as $key => $label) {
		$updated[$key] = $label;
		if ('order_total' === $key) {
			$updated['vi_source'] = 'Source / keyword';
		}
	}
	if (!isset($updated['vi_source'])) {
		$updated['vi_source'] = 'Source / keyword';
	}

	return $updated;
}
add_filter('manage_order_posts_columns', 'retails_vi_order_columns', 30);

function retails_vi_order_column($column, $post_id)
{
	if ('vi_source' === $column) {
		echo '<div class="wvi">' . retails_vi_order_source_html($post_id) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper.
	}
}
add_action('manage_order_posts_custom_column', 'retails_vi_order_column', 10, 2);

function retails_vi_order_screen_assets($hook)
{
	$screen = get_current_screen();
	if (!in_array($hook, array('edit.php', 'post.php'), true) || !$screen || 'order' !== $screen->post_type) {
		return;
	}

	$file = get_template_directory() . '/css/visitor-insights.css';
	wp_enqueue_style('retails-vi-admin', get_template_directory_uri() . '/css/visitor-insights.css', array(), (string) filemtime($file));
}
add_action('admin_enqueue_scripts', 'retails_vi_order_screen_assets');

/* ---------------------------------------------------------------------------
 * Reporting
 * ------------------------------------------------------------------------ */

function retails_vi_channels()
{
	return array(
		'google_ads' => 'Google Ads',
		'organic' => 'Organic search',
		'ai' => 'AI assistants',
		'direct' => 'Direct',
		'referral' => 'Referral',
		'social' => 'Social',
		'email' => 'Email',
		'paid_other' => 'Other paid',
	);
}

/**
 * Where a Google ad was shown when it was clicked.
 */
function retails_vi_ad_networks()
{
	return array(
		'search' => 'Google Search & Shopping',
		'youtube' => 'YouTube',
		'display' => 'Display network',
		'partners' => 'Search partners',
		'gmail' => 'Gmail',
		'sites' => 'Partner websites',
		'app' => 'Apps (no referrer)',
	);
}

/**
 * Rules for retails_vi_ad_networks(), first match wins: the {network} value
 * when the ad's tracking suffix sends one (utm_network={network}), else the
 * page the click came from. Patterns work in both PHP and MySQL REGEXP.
 */
function retails_vi_network_rules()
{
	return array(
		array('youtube', 'landing_url', '[?&](utm_)?network=ytv'),
		array('partners', 'landing_url', '[?&](utm_)?network=s(&|$)'),
		array('display', 'landing_url', '[?&](utm_)?network=(d|vp)(&|$)'),
		array('youtube', 'referrer', '(^|[/.])(youtube[.]com|youtu[.]be)(/|$)|com[.]google[.]android[.]youtube'),
		array('display', 'referrer', '(^|[/.])(doubleclick[.]net|googlesyndication[.]com|googleadservices[.]com)(/|$)'),
		array('partners', 'referrer', '(^|[/.])syndicatedsearch[.]goog(/|$)'),
		array('gmail', 'referrer', 'mail[.]google[.]com|com[.]google[.]android[.]gm'),
		array('search', 'referrer', '(^|[/.])google[.][a-z.]+(/|$)|googlequicksearchbox'),
		array('search', 'landing_url', '[?&](utm_)?network=g(&|$)'),
	);
}

/**
 * The ad network of a visit (empty for visits that are not ad clicks).
 */
function retails_vi_ad_network($session)
{
	$session = (object) $session;
	if (!in_array($session->channel ?? '', array('google_ads', 'paid_other'), true)) {
		return '';
	}
	foreach (retails_vi_network_rules() as $rule) {
		if (preg_match('#' . $rule[2] . '#i', (string) ($session->{$rule[1]} ?? ''))) {
			return $rule[0];
		}
	}

	return '' === (string) ($session->referrer ?? '') ? 'app' : 'sites';
}

/**
 * SQL version of retails_vi_ad_network().
 */
function retails_vi_network_sql($alias = 's')
{
	$sql = "CASE WHEN {$alias}.channel NOT IN ('google_ads', 'paid_other') THEN ''";
	foreach (retails_vi_network_rules() as $rule) {
		$sql .= " WHEN {$alias}.{$rule[1]} REGEXP '{$rule[2]}' THEN '{$rule[0]}'";
	}

	return $sql . " WHEN {$alias}.referrer = '' THEN 'app' ELSE 'sites' END";
}

/**
 * SQL true for ad clicks that came from a partner website, one that could be
 * excluded in Google Ads. Google's own ad-serving hosts (doubleclick,
 * googlesyndication, syndicatedsearch) hide the real site, so they are left out.
 */
function retails_vi_placement_sql($alias = 's')
{
	// The site itself is never a placement: an ad URL reopened from one of its own pages
	// (reload, back, an in-app browser) showed storefrontsignonline.com as a "partner
	// website" to exclude in Google Ads (Nov 2026).
	$own = array('storefrontsignonline[.]com');
	$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
	if ($host && preg_match('/^[a-z0-9.-]+$/', $host)) {
		$own[] = str_replace('.', '[.]', preg_replace('/^www\./', '', $host));
	}

	return '(' . retails_vi_network_sql($alias) . ") IN ('display', 'partners', 'sites') AND {$alias}.referrer <> '' AND {$alias}.referrer NOT REGEXP '(^|[/.])(doubleclick[.]net|googlesyndication[.]com|googleadservices[.]com|syndicatedsearch[.]goog|google[.][a-z.]+|localhost|" . implode('|', array_unique($own)) . ")(/|:|$)'";
}

/**
 * The site an ad was clicked on, for Display and partner clicks.
 */
function retails_vi_ad_placement($session)
{
	$session = (object) $session;
	$network = retails_vi_ad_network($session);
	if (!in_array($network, array('display', 'partners', 'sites', 'youtube'), true)) {
		return '';
	}

	return preg_replace('/^www\./', '', strtolower((string) wp_parse_url((string) $session->referrer, PHP_URL_HOST)));
}

/**
 * A US region (or the country) from a browser time zone. Visits carry no
 * country unless the site is behind Cloudflare, so this is the best location.
 */
function retails_vi_region($tz)
{
	$tz = (string) $tz;
	if ('' === $tz) {
		return '';
	}
	$us = array(
		'America/Los_Angeles' => 'US Pacific (CA, WA, OR, NV)',
		'America/Denver' => 'US Mountain (CO, UT, NM, MT, WY)',
		'America/Boise' => 'US Mountain (Idaho)',
		'America/Phoenix' => 'US Arizona',
		'America/Chicago' => 'US Central (TX, IL, MN, MO…)',
		'America/New_York' => 'US Eastern (NY, FL, GA, PA…)',
		'America/Detroit' => 'US Eastern (Michigan)',
		'America/Anchorage' => 'US Alaska',
		'America/Juneau' => 'US Alaska',
		'Pacific/Honolulu' => 'US Hawaii',
		'America/Puerto_Rico' => 'Puerto Rico',
	);
	if (isset($us[$tz])) {
		return $us[$tz];
	}
	if (preg_match('#^America/Indiana/#', $tz)) {
		return 'US Indiana';
	}
	if (preg_match('#^America/Kentucky/#', $tz)) {
		return 'US Kentucky';
	}
	if (preg_match('#^America/North_Dakota/#', $tz)) {
		return 'US North Dakota';
	}
	$countries = array(
		'America/Toronto' => 'Canada', 'America/Vancouver' => 'Canada', 'America/Edmonton' => 'Canada', 'America/Winnipeg' => 'Canada', 'America/Halifax' => 'Canada',
		'America/Mexico_City' => 'Mexico', 'America/Tijuana' => 'Mexico', 'Asia/Dhaka' => 'Bangladesh', 'Asia/Kolkata' => 'India', 'Asia/Calcutta' => 'India',
		'Asia/Karachi' => 'Pakistan', 'Asia/Manila' => 'Philippines', 'Asia/Shanghai' => 'China', 'Asia/Hong_Kong' => 'Hong Kong', 'Asia/Singapore' => 'Singapore',
		'Asia/Dubai' => 'UAE', 'Asia/Jakarta' => 'Indonesia', 'Asia/Ho_Chi_Minh' => 'Vietnam', 'Asia/Saigon' => 'Vietnam', 'Asia/Tokyo' => 'Japan',
		'Europe/London' => 'UK', 'Europe/Berlin' => 'Germany', 'Europe/Paris' => 'France', 'Europe/Amsterdam' => 'Netherlands', 'Africa/Lagos' => 'Nigeria',
		'Australia/Sydney' => 'Australia', 'Australia/Melbourne' => 'Australia', 'UTC' => 'Unknown (UTC)', 'Etc/UTC' => 'Unknown (UTC)',
	);
	if (isset($countries[$tz])) {
		return $countries[$tz];
	}

	return str_replace('_', ' ', substr(strrchr($tz, '/') ?: '/' . $tz, 1));
}

function retails_vi_stages()
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
function retails_vi_bounce_sql($alias = 's')
{
	return "({$alias}.pageviews <= 1 AND {$alias}.engaged_seconds < 10 AND {$alias}.stage < 3 AND {$alias}.is_lead = 0 AND {$alias}.order_id = 0)";
}

/**
 * SQL turning a GMT datetime column into site time for a date range. Uses the
 * offset in force at each moment, so days and hours stay right across a
 * daylight-saving change (today's offset alone would shift a 90-day chart).
 */
function retails_vi_local_sql($column, $start_gmt, $end_gmt)
{
	$tz = wp_timezone();
	$from = strtotime($start_gmt . ' UTC');
	$transitions = $tz->getTransitions($from, strtotime($end_gmt . ' UTC'));
	if (!$transitions || count($transitions) < 2) {
		$offset = (int) $tz->getOffset(new DateTime('@' . $from));
		return "DATE_ADD({$column}, INTERVAL {$offset} SECOND)";
	}

	// The first entry is the offset at the start of the range; each later
	// one is a change.
	$case = 'CASE';
	for ($i = 1, $n = count($transitions); $i < $n; $i++) {
		$case .= sprintf(" WHEN %s < '%s' THEN %d", $column, gmdate('Y-m-d H:i:s', $transitions[$i]['ts']), (int) $transitions[$i - 1]['offset']);
	}
	$case .= ' ELSE ' . (int) end($transitions)['offset'] . ' END';

	return "DATE_ADD({$column}, INTERVAL ({$case}) SECOND)";
}

/**
 * The date range picked on the admin page, with the same-length range before
 * it for comparisons.
 */
function retails_vi_period()
{
	$tz = wp_timezone();
	$now = new DateTimeImmutable('now', $tz);
	$today = $now->setTime(0, 0);
	$key = isset($_GET['period']) ? sanitize_key(wp_unslash($_GET['period'])) : '7d';
	$labels = array('today' => 'Today', 'yesterday' => 'Yesterday', '7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', 'custom' => 'Custom');
	$presets = array(
		'today' => array($today, $now),
		'yesterday' => array($today->modify('-1 day'), $today),
		'7d' => array($today->modify('-6 days'), $now),
		'30d' => array($today->modify('-29 days'), $now),
		'90d' => array($today->modify('-89 days'), $now),
	);
	if (!isset($presets[$key]) && 'custom' !== $key) {
		$key = '7d';
	}

	// The toolbar always sends its two dates. They only make a custom range
	// when they differ from the preset being viewed, so changing a filter
	// keeps "Last 30 days" instead of turning it into "Custom".
	$from = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_GET['from'] ?? '')), $tz);
	$to = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_GET['to'] ?? '')), $tz);
	if ($from && $to) {
		if ($from > $to) {
			list($from, $to) = array($to, $from);
		}
		if ('custom' !== $key && ($from != $presets[$key][0] || $to != $presets[$key][1]->modify('-1 second')->setTime(0, 0))) {
			$key = 'custom';
		}
	} elseif ('custom' === $key) {
		$key = '7d';
	}

	if ('custom' === $key) {
		$start = $from;
		// A range that starts in the future is empty; keep it one day long.
		$end = $from > $now ? $from->modify('+1 day') : min($to->modify('+1 day'), $now);
	} else {
		list($start, $end) = $presets[$key];
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
function retails_vi_where($start_gmt, $end_gmt, $filters = array(), $alias = 's')
{
	$where = array("{$alias}.started_at >= %s", "{$alias}.started_at < %s");
	$params = array($start_gmt, $end_gmt);

	// Robots are left out of every number unless asked for.
	if (empty($filters['robots'])) {
		$where[] = 'NOT ' . retails_vi_automated_sql($alias);
	} elseif ('only' === $filters['robots']) {
		$where[] = retails_vi_automated_sql($alias);
	}

	if (!empty($filters['channel']) && isset(retails_vi_channels()[$filters['channel']])) {
		$where[] = "{$alias}.channel = %s";
		$params[] = $filters['channel'];
	}
	if (!empty($filters['device']) && in_array($filters['device'], array('mobile', 'tablet', 'desktop'), true)) {
		$where[] = "{$alias}.device = %s";
		$params[] = $filters['device'];
	}
	if (!empty($filters['network']) && isset(retails_vi_ad_networks()[$filters['network']])) {
		$where[] = '(' . retails_vi_network_sql($alias) . ') = %s';
		$params[] = $filters['network'];
	}
	if (!empty($filters['campaign'])) {
		$where[] = "{$alias}.campaign IN (" . implode(', ', array_fill(0, count((array) $filters['campaign']), '%s')) . ')';
		$params = array_merge($params, (array) $filters['campaign']);
	}

	return array(implode(' AND ', $where), $params);
}

function retails_vi_metrics_sql($alias = 's')
{
	$bounce = retails_vi_bounce_sql($alias);

	return "COUNT(*) AS sessions, COUNT(DISTINCT {$alias}.visitor_key) AS visitors, SUM({$alias}.channel = 'google_ads') AS ads,
		SUM({$bounce}) AS bounced, SUM({$alias}.pageviews <= 1 AND {$alias}.engaged_seconds < 10) AS quick_exits,
		COALESCE(AVG({$alias}.engaged_seconds), 0) AS avg_engaged, COALESCE(AVG({$alias}.pageviews), 0) AS avg_pages,
		SUM({$alias}.stage >= 2) AS s2, SUM({$alias}.stage >= 3) AS s3, SUM({$alias}.stage >= 4) AS s4, SUM({$alias}.stage >= 5) AS s5,
		SUM({$alias}.stage >= 6) AS s6, SUM({$alias}.order_id > 0) AS orders, SUM({$alias}.is_lead) AS leads,
		COALESCE(SUM({$alias}.revenue), 0) AS revenue, SUM({$alias}.is_new = 0) AS returning_visits";
}

function retails_vi_summary($start_gmt, $end_gmt, $filters = array())
{
	global $wpdb;
	list($where, $params) = retails_vi_where($start_gmt, $end_gmt, $filters);
	$row = $wpdb->get_row($wpdb->prepare('SELECT ' . retails_vi_metrics_sql() . ' FROM ' . retails_vi_table('sessions') . " s WHERE {$where}", $params), ARRAY_A);

	return array_map('floatval', $row ?: array());
}

/**
 * Sessions grouped by an SQL expression, with the standard metrics.
 */
function retails_vi_breakdown($group_sql, $start_gmt, $end_gmt, $filters = array(), $limit = 10, $extra_where = '')
{
	global $wpdb;
	list($where, $params) = retails_vi_where($start_gmt, $end_gmt, $filters);
	if ($extra_where) {
		$where .= ' AND ' . $extra_where;
	}
	$params[] = (int) $limit;

	return $wpdb->get_results($wpdb->prepare(
		"SELECT {$group_sql} AS label, " . retails_vi_metrics_sql() . ' FROM ' . retails_vi_table('sessions') . " s WHERE {$where} GROUP BY label ORDER BY sessions DESC LIMIT %d",
		$params
	), ARRAY_A);
}

/**
 * Event rows grouped by type and label (errors, clicks, ...).
 */
function retails_vi_event_breakdown($types, $start_gmt, $end_gmt, $filters = array(), $limit = 10)
{
	global $wpdb;
	list($where, $params) = retails_vi_where($start_gmt, $end_gmt, $filters);
	$types = array_values(array_intersect((array) $types, retails_vi_event_types()));
	if (!$types) {
		return array();
	}
	$params = array_merge($types, array($start_gmt, $end_gmt), $params, array((int) $limit));
	$in = implode(', ', array_fill(0, count($types), '%s'));

	return $wpdb->get_results($wpdb->prepare(
		'SELECT e.type, e.label, COUNT(*) AS hits, COUNT(DISTINCT e.session_id) AS sessions, SUM(s.order_id > 0) AS buyers, MAX(e.created_at) AS last_at
		FROM ' . retails_vi_table('events') . ' e JOIN ' . retails_vi_table('sessions') . " s ON s.id = e.session_id
		WHERE e.type IN ({$in}) AND e.created_at >= %s AND e.created_at < %s AND {$where}
		GROUP BY e.type, e.label ORDER BY sessions DESC, hits DESC LIMIT %d",
		$params
	), ARRAY_A);
}

/**
 * Forms visitors started, how many finished them, and the field most people
 * stopped at.
 */
function retails_vi_form_report($start_gmt, $end_gmt, $filters = array())
{
	global $wpdb;
	list($where, $params) = retails_vi_where($start_gmt, $end_gmt, $filters);
	$events = retails_vi_table('events');
	$base = array_merge(array($start_gmt, $end_gmt), $params);

	// The checkout form counts as finished only when an order was placed. Every
	// checkout visit has added to the cart first, so the add_to_cart rule below
	// marked every checkout "finished" and hid checkout abandonment (0% gave up).
	$checkout = "('checkoutForm', 'Checkout')";
	$finished = "CASE WHEN f.label IN {$checkout} THEN %1\$s.type = 'purchase'
		ELSE %1\$s.type IN ('form_submit', 'quote_submit', 'purchase', 'add_to_cart') AND (%1\$s.label = f.label OR %1\$s.type <> 'form_submit') END";

	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT f.label, COUNT(DISTINCT f.session_id) AS started, COUNT(DISTINCT sub.session_id) AS submitted
		FROM {$events} f JOIN " . retails_vi_table('sessions') . " s ON s.id = f.session_id
		LEFT JOIN {$events} sub ON sub.session_id = f.session_id AND " . sprintf($finished, 'sub') . "
		WHERE f.type = 'form_start' AND f.created_at >= %s AND f.created_at < %s AND {$where}
		GROUP BY f.label ORDER BY started DESC LIMIT 8",
		$base
	), ARRAY_A);

	$fields = $wpdb->get_results($wpdb->prepare(
		"SELECT f.label, f.value, COUNT(*) AS n
		FROM {$events} f JOIN " . retails_vi_table('sessions') . " s ON s.id = f.session_id
		WHERE f.type = 'form_start' AND f.value <> '' AND f.created_at >= %s AND f.created_at < %s AND {$where}
			AND NOT EXISTS (SELECT 1 FROM {$events} x WHERE x.session_id = f.session_id AND " . sprintf($finished, 'x') . ")
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
function retails_vi_series($period, $filters = array())
{
	global $wpdb;
	list($where, $params) = retails_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$local = retails_vi_local_sql('s.started_at', $period['start_gmt'], $period['end_gmt']);
	$bucket = $period['hourly'] ? "DATE_FORMAT({$local}, '%%Y-%%m-%%d %%H:00')" : "DATE({$local})";

	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT {$bucket} AS bucket, COUNT(*) AS sessions, SUM(s.channel = 'google_ads') AS ads, SUM(s.order_id > 0 OR s.is_lead = 1) AS conversions
		FROM " . retails_vi_table('sessions') . " s WHERE {$where} GROUP BY bucket",
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
function retails_vi_repeat_ad_clickers($start_gmt, $end_gmt, $min = 3)
{
	global $wpdb;

	$rows = $wpdb->get_results($wpdb->prepare(
		'SELECT ip, COUNT(*) AS clicks, COUNT(DISTINCT visitor_key) AS devices, SUM(order_id > 0) AS orders, SUM(is_lead) AS leads,
			COALESCE(AVG(engaged_seconds), 0) AS avg_engaged, MAX(last_seen) AS last_seen, MAX(device) AS device, MAX(timezone) AS timezone
		FROM ' . retails_vi_table('sessions') . " s WHERE channel = 'google_ads' AND ip <> '' AND started_at >= %s AND started_at < %s AND NOT " . retails_vi_automated_sql('s') . "
		GROUP BY ip HAVING clicks >= %d ORDER BY clicks DESC LIMIT 15",
		$start_gmt,
		$end_gmt,
		(int) $min
	), ARRAY_A);

	// Google's own landing page checks (recorded before they were filtered out) are not
	// clicks to exclude in Google Ads.
	$rows = array_values(array_filter((array) $rows, static function ($row) {
		return !retails_vi_is_google_ip($row['ip']);
	}));
	foreach ($rows as &$row) {
		$row['carrier'] = retails_vi_is_carrier_ip($row['ip']);
	}
	unset($row);

	return $rows;
}

/**
 * True for a mobile carrier address that many phones share at once (T-Mobile
 * 172.32.0.0/11, Verizon Wireless 174.192.0.0/10, AT&T Mobility 107.64.0.0/10 and
 * 166.128.0.0/9, carrier-grade NAT 100.64.0.0/10). Most repeat ad clickers in Nov
 * 2026 were these: excluding one in Google Ads would hide the ads from real buyers.
 */
function retails_vi_is_carrier_ip($ip)
{
	$long = ip2long((string) $ip);
	if (false === $long) {
		return false;
	}
	foreach (array('172.32.0.0/11', '174.192.0.0/10', '107.64.0.0/10', '166.128.0.0/9', '100.64.0.0/10') as $cidr) {
		list($net, $bits) = explode('/', $cidr);
		$mask = -1 << (32 - (int) $bits);
		if ((ip2long($net) & $mask) === ($long & $mask)) {
			return true;
		}
	}

	return false;
}

/**
 * Plain-language findings about why visitors are not buying, worked out from
 * the numbers. Each is array(level, title, detail).
 */
function retails_vi_findings($m, $context)
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

	// Shared phone-carrier addresses are many people, not one clicker (retails_vi_is_carrier_ip()).
	$own_ips = array_filter((array) ($context['repeat_clickers'] ?? array()), static function ($row) {
		return empty($row['carrier']);
	});
	if ($own_ips) {
		$findings[] = array('critical', sprintf('%d IP address(es) clicked your ads 3 or more times', count($own_ips)),
			'Repeated clicks from the same address with no purchase are often competitors or click fraud. Check them in "Repeat ad clickers" and add them under Google Ads → Settings → IP exclusions. Leave out the ones marked "Phone carrier": many phones share those addresses.');
	}

	if (!empty($context['foreign']) && $pct($context['foreign'], $sessions) >= 10) {
		// Across all traffic these are mostly not ad clicks (e.g. Asia time zones landing on a
		// product page for 0 seconds), so only the Google Ads view points at ad locations.
		$findings[] = array('warning', sprintf('%d%% of %s are in time zones outside the US', $pct($context['foreign'], $sessions), $who),
			!empty($context['ads'])
				? 'Your ads may be shown abroad. In Google Ads → Locations, set "Presence: people in or regularly in your targeted locations" instead of "Presence or interest".'
				: 'If the Google Ads view does not show this warning too, these visits are not from your ads: foreign visits that open one page and leave in 0 seconds are usually bots or link checkers, and they inflate the bounce rate. Judge ad locations from the Google Ads view.');
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

function retails_vi_admin_menu()
{
	add_menu_page('Visitor Insights', 'Visitor Insights', 'manage_options', 'retails-insights', 'retails_vi_admin_page', 'dashicons-chart-area', 3);
	add_submenu_page('retails-insights', 'Overview', 'Overview', 'manage_options', 'retails-insights', 'retails_vi_admin_page');
	add_submenu_page('retails-insights', 'Get More Orders', 'Get more orders', 'manage_options', 'retails-insights-grow', 'retails_vi_admin_page');
	add_submenu_page('retails-insights', 'Leads', 'Leads', 'manage_options', 'retails-insights-leads', 'retails_vi_admin_page');
	add_submenu_page('retails-insights', 'Google Ads Insights', 'Google Ads', 'manage_options', 'retails-insights-ads', 'retails_vi_admin_page');
	add_submenu_page('retails-insights', 'Visitors', 'Visitors', 'manage_options', 'retails-insights-visitors', 'retails_vi_admin_page');
	add_submenu_page('retails-insights', 'Live Visitors', 'Live', 'manage_options', 'retails-insights-live', 'retails_vi_admin_page');
	add_submenu_page('retails-insights', 'Visitor Insights Settings', 'Settings', 'manage_options', 'retails-insights-settings', 'retails_vi_admin_page');
}
add_action('admin_menu', 'retails_vi_admin_menu');

function retails_vi_admin_bar_menu($admin_bar)
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$admin_bar->add_node(array(
		'id' => 'retails-visitor-insights',
		'title' => __('Visitor Insights', 'litsign'),
		'href' => retails_vi_url('retails-insights'),
	));

	$views = array(
		'retails-insights' => __('Overview', 'litsign'),
		'retails-insights-grow' => __('Get more orders', 'litsign'),
		'retails-insights-leads' => __('Leads', 'litsign'),
		'retails-insights-live' => __('Live Visitors', 'litsign'),
		'retails-insights-visitors' => __('Visitors', 'litsign'),
		'retails-insights-ads' => __('Google Ads', 'litsign'),
		'retails-insights-settings' => __('Settings', 'litsign'),
	);

	foreach ($views as $page => $title) {
		$admin_bar->add_node(array(
			'id' => 'retails-visitor-insights-' . $page,
			'parent' => 'retails-visitor-insights',
			'title' => $title,
			'href' => retails_vi_url($page),
		));
	}
}
add_action('admin_bar_menu', 'retails_vi_admin_bar_menu', 100);

function retails_vi_admin_assets($hook)
{
	if (false === strpos($hook, 'retails-insights')) {
		return;
	}

	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('retails-vi-admin', $uri . '/css/visitor-insights.css', array(), (string) filemtime($dir . '/css/visitor-insights.css'));
	wp_enqueue_script('retails-vi-admin', $uri . '/js/visitor-insights-admin.js', array(), (string) filemtime($dir . '/js/visitor-insights-admin.js'), true);
	// The Live tab and a single visit are cheap to redraw and change by the
	// second; the reports run ~35 queries per redraw, so check them less often.
	$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
	$fast = 'retails-insights-live' === $page || ('retails-insights-visitors' === $page && !empty($_GET['session']));
	wp_localize_script('retails-vi-admin', 'retailsVIAdmin', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('retails_vi_live'),
		'interval' => $fast ? 5000 : 20000,
	));
}
add_action('admin_enqueue_scripts', 'retails_vi_admin_assets');

function retails_vi_tabs()
{
	return array(
		'retails-insights' => array('Overview', 'dashboard'),
		'retails-insights-grow' => array('Get more orders', 'lightbulb'),
		'retails-insights-leads' => array('Leads', 'id'),
		'retails-insights-ads' => array('Google Ads', 'megaphone'),
		'retails-insights-visitors' => array('Visitors', 'groups'),
		'retails-insights-live' => array('Live', 'visibility'),
		'retails-insights-settings' => array('Settings', 'admin-generic'),
	);
}

function retails_vi_url($page, $args = array())
{
	// add_query_arg() does not encode values; a search for "a&b" or "c++" would break the link.
	return add_query_arg(urlencode_deep(array_merge(array('page' => $page), $args)), admin_url('admin.php'));
}

/* Formatting helpers --------------------------------------------------- */

function retails_vi_duration($seconds)
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

function retails_vi_pct($part, $whole, $decimals = 0)
{
	return $whole > 0 ? number_format_i18n(100 * $part / $whole, $decimals) . '%' : '—';
}

function retails_vi_money($amount)
{
	return '$' . number_format_i18n((float) $amount, 2);
}

function retails_vi_date($gmt, $format = 'M j, g:i a')
{
	return $gmt ? get_date_from_gmt($gmt, $format) : '';
}

function retails_vi_ago($gmt)
{
	return human_time_diff(strtotime($gmt . ' UTC'), time()) . ' ago';
}

function retails_vi_site_link($path)
{
	$origin = preg_replace('#^(https?://[^/]+).*$#', '$1', home_url());

	return $origin . $path;
}

function retails_vi_short_path($path, $length = 48)
{
	$path = rawurldecode((string) $path);
	$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
	if ($home_path && '/' !== $home_path && 0 === strpos($path, $home_path)) {
		$path = '/' . substr($path, strlen($home_path));
	}

	return mb_strlen($path) > $length ? mb_substr($path, 0, $length - 1) . '…' : $path;
}

function retails_vi_channel_badge($channel)
{
	$labels = retails_vi_channels();

	return sprintf('<span class="wvi-channel is-%s">%s</span>', esc_attr($channel), esc_html($labels[$channel] ?? ucfirst($channel)));
}

function retails_vi_device_icon($device)
{
	$icons = array('mobile' => 'smartphone', 'tablet' => 'tablet', 'desktop' => 'desktop');

	return sprintf('<span class="dashicons dashicons-%s" title="%s"></span>', esc_attr($icons[$device] ?? 'desktop'), esc_attr(ucfirst($device)));
}

function retails_vi_stage_pips($stage)
{
	$html = '<span class="wvi-pips" title="' . esc_attr(retails_vi_stages()[(int) $stage] ?? '') . '">';
	for ($i = 1; $i <= 7; $i++) {
		$html .= '<i class="' . ($i <= $stage ? 'is-on' : '') . ($i === 7 && $stage >= 7 ? ' is-won' : '') . '"></i>';
	}

	return $html . '</span>';
}

function retails_vi_outcome($session)
{
	$session = (object) $session;
	if ((int) $session->order_id > 0) {
		return '<span class="wvi-badge is-green">Purchased ' . esc_html(retails_vi_money($session->revenue)) . '</span>';
	}
	if ((int) $session->is_lead) {
		$types = retails_vi_lead_types_cache()[(int) ($session->id ?? 0)] ?? array();
		$label = in_array('quote_submit', $types, true) ? 'Quote request' : (in_array('call_click', $types, true) ? 'Lead · call tap' : (in_array('email_click', $types, true) ? 'Lead · email' : 'Lead'));
		return '<span class="wvi-badge is-blue">' . esc_html($label) . '</span>';
	}
	if ((int) $session->stage >= 4) {
		return '<span class="wvi-badge is-amber">Abandoned ' . ((int) $session->stage >= 5 ? 'checkout' : 'cart') . '</span>';
	}
	if ((int) $session->pageviews <= 1 && (int) $session->engaged_seconds < 10) {
		return '<span class="wvi-badge is-red">Bounced</span>';
	}

	return '<span class="wvi-badge">Browsed</span>';
}

function retails_vi_location($session)
{
	$session = (object) $session;
	$parts = array();
	if (!empty($session->country)) {
		$parts[] = $session->country;
	}
	if (!empty($session->timezone)) {
		$parts[] = retails_vi_region($session->timezone);
	}
	if (!empty($session->language) && preg_match('/^es/i', $session->language)) {
		$parts[] = 'Spanish speaker';
	}

	return implode(' · ', $parts);
}

/**
 * Whether a browser time zone is outside the United States.
 */
function retails_vi_is_foreign_tz($tz)
{
	if ('' === $tz) {
		return false;
	}

	return !preg_match('#^(America/(New_York|Detroit|Chicago|Denver|Phoenix|Los_Angeles|Anchorage|Juneau|Sitka|Nome|Adak|Boise|Indiana/.+|Kentucky/.+|North_Dakota/.+|Menominee|Metlakatla|Yakutat|Puerto_Rico)|Pacific/Honolulu|US/.+)$#', $tz);
}

/* Page shell ------------------------------------------------------------ */

function retails_vi_admin_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'retails-insights';
	$tabs = retails_vi_tabs();
	if (!isset($tabs[$page])) {
		$page = 'retails-insights';
	}
	$notice = isset($_GET['vi_notice']) ? sanitize_key(wp_unslash($_GET['vi_notice'])) : '';
	?>
	<div class="wrap wvi">
		<header class="wvi-header">
			<div>
				<h1 class="wvi-title"><span class="dashicons dashicons-chart-area"></span> Visitor Insights</h1>
				<p class="wvi-subtitle">Who visits the site, where they come from, what they do, and where they stop before buying.</p>
			</div>
			<?php if (!retails_vi_setting('enabled')) : ?>
				<span class="wvi-status is-off"><span class="wvi-dot"></span> Tracking is paused</span>
			<?php else : ?>
				<span class="wvi-status" data-live-status>
					<span class="wvi-dot"></span>
					<span><strong data-online><?php echo esc_html(retails_vi_live_count()); ?></strong> online now</span>
					<?php if ('retails-insights-settings' !== $page) : ?>
						<span class="wvi-status-sep"></span>
						<span class="wvi-updated" data-updated>Live</span>
						<button type="button" class="wvi-live-toggle" data-live-toggle title="Pause live updates" aria-pressed="false"><span class="dashicons dashicons-controls-pause"></span></button>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</header>

		<nav class="wvi-tabs">
			<?php foreach ($tabs as $slug => $tab) : ?>
				<a href="<?php echo esc_url(retails_vi_url($slug)); ?>" class="<?php echo $slug === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-<?php echo esc_attr($tab[1]); ?>"></span> <?php echo esc_html($tab[0]); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ('saved' === $notice) : ?>
			<div class="wvi-alert is-success">Settings saved.</div>
		<?php elseif ('purged' === $notice) : ?>
			<div class="wvi-alert is-success">All visitor data was deleted.</div>
		<?php endif; ?>

		<?php if ('retails-insights-settings' === $page) : ?>
			<?php retails_vi_render_settings(); ?>
		<?php else : ?>
			<div class="wvi-body" data-live-body data-version="<?php echo esc_attr(retails_vi_data_version($page)); ?>">
				<?php retails_vi_render_body($page); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The report, list or visit for a tab. Also rendered by the live-update
 * request, which swaps it in without reloading the page.
 */
function retails_vi_render_body($page)
{
	switch ($page) {
		case 'retails-insights-ads':
			retails_vi_render_report(true);
			break;
		case 'retails-insights-visitors':
			if (!empty($_GET['session'])) {
				retails_vi_render_session(absint($_GET['session']));
			} else {
				retails_vi_render_visitors();
			}
			break;
		case 'retails-insights-live':
			retails_vi_render_live();
			break;
		case 'retails-insights-leads':
			retails_vi_render_leads();
			break;
		case 'retails-insights-grow':
			retails_vi_render_growth();
			break;
		default:
			retails_vi_render_report(false);
	}
}

/**
 * A cheap fingerprint of the data a tab shows. The live-update request only
 * re-renders the tab when it changes.
 */
function retails_vi_data_version($page)
{
	global $wpdb;
	$sessions = retails_vi_table('sessions');

	if ('retails-insights-visitors' === $page && !empty($_GET['session'])) {
		$row = $wpdb->get_row($wpdb->prepare("SELECT last_seen, events, session_key FROM {$sessions} WHERE id = %d", absint($_GET['session'])));
		$version = $row ? $row->last_seen . ':' . $row->events : 'gone';
		// A screenshot taken or drawn (inc/visitor-snaps.php).
		if ($row && function_exists('retails_vi_snap_signature')) {
			$version .= ':' . retails_vi_snap_signature($row->session_key);
		}
	} else {
		// A range that ended before now never changes.
		$period = retails_vi_period();
		if ('retails-insights-live' !== $page && $period['end']->getTimestamp() < time() - MINUTE_IN_SECONDS) {
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
function retails_vi_ajax_refresh()
{
	check_ajax_referer('retails_vi_live', 'nonce');
	if (!current_user_can('manage_options')) {
		wp_send_json_error(null, 403);
	}

	parse_str((string) wp_unslash($_POST['query'] ?? ''), $query);
	$_GET = is_array($query) ? $query : array();
	$page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
	if (!isset(retails_vi_tabs()[$page]) || 'retails-insights-settings' === $page) {
		wp_send_json_error(null, 400);
	}

	// A fresh nonce each time keeps a tab left open for days updating.
	$response = array('online' => retails_vi_live_count(), 'changed' => false, 'nonce' => wp_create_nonce('retails_vi_live'));
	$version = retails_vi_data_version($page);
	if ($version !== (string) wp_unslash($_POST['version'] ?? '')) {
		ob_start();
		retails_vi_render_body($page);
		$response['html'] = ob_get_clean();
		$response['version'] = $version;
		$response['changed'] = true;
	}

	wp_send_json_success($response);
}
add_action('wp_ajax_retails_vi_refresh', 'retails_vi_ajax_refresh');

/**
 * Period picker and filters, as a GET form that keeps the current page.
 */
function retails_vi_render_toolbar($period, $page, $filters = array(), $show = array('channel', 'device'))
{
	?>
	<form class="wvi-toolbar wvi-card" method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
		<div class="wvi-segments">
			<?php foreach (array('today', 'yesterday', '7d', '30d', '90d') as $key) : ?>
				<a href="<?php echo esc_url(retails_vi_url($page, array_filter(array_merge($filters, array('period' => $key))))); ?>" class="<?php echo $period['key'] === $key ? 'is-active' : ''; ?>"><?php echo esc_html($period['labels'][$key]); ?></a>
			<?php endforeach; ?>
		</div>
		<div class="wvi-toolbar-right">
			<label class="wvi-daterange">
				<input type="date" name="from" value="<?php echo esc_attr($period['start']->format('Y-m-d')); ?>" aria-label="From">
				<span>–</span>
				<input type="date" name="to" value="<?php echo esc_attr($period['end']->modify('-1 second')->format('Y-m-d')); ?>" aria-label="To">
				<input type="hidden" name="period" value="<?php echo esc_attr($period['key']); ?>">
			</label>
			<?php if (in_array('channel', $show, true)) : ?>
				<select name="channel" aria-label="Traffic source">
					<option value="">All sources</option>
					<?php foreach (retails_vi_channels() as $key => $label) : ?>
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
			<?php if (!empty($filters['network']) && isset(retails_vi_ad_networks()[$filters['network']])) : ?>
				<input type="hidden" name="network" value="<?php echo esc_attr($filters['network']); ?>">
				<a class="wvi-chip" href="<?php echo esc_url(retails_vi_url($page, array_filter(array_merge($filters, array('network' => '', 'period' => $period['key']))))); ?>" title="Show all ad networks">Ad network: <?php echo esc_html(retails_vi_ad_networks()[$filters['network']]); ?> ✕</a>
			<?php endif; ?>
			<?php do_action('retails_vi_toolbar_fields', $page, $filters); ?>
			<button class="button button-primary">Apply</button>
		</div>
	</form>
	<?php
}

function retails_vi_request_filters()
{
	return array(
		'channel' => isset($_GET['channel']) ? sanitize_key(wp_unslash($_GET['channel'])) : '',
		'device' => isset($_GET['device']) ? sanitize_key(wp_unslash($_GET['device'])) : '',
		'network' => isset($_GET['network']) ? sanitize_key(wp_unslash($_GET['network'])) : '',
	);
}

function retails_vi_change($now, $before, $invert = false)
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

function retails_vi_stat($label, $value, $icon, $color, $delta = '', $hint = '')
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

function retails_vi_render_funnel($m, $title = 'Purchase funnel')
{
	$stages = retails_vi_stages();
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
					<span class="wvi-funnel-num"><strong><?php echo esc_html(number_format_i18n($counts[$i])); ?></strong> <?php echo esc_html(retails_vi_pct($counts[$i], $counts[1], 1)); ?></span>
					<span class="wvi-funnel-drop"><?php echo $i > 1 && $counts[$i - 1] > 0 ? esc_html($drop >= 0.5 ? '−' . number_format_i18n($drop, 0) . '% lost' : 'none lost') : ''; ?></span>
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
function retails_vi_render_breakdown($title, $rows, $label_cb, $hint = '', $columns = array('sessions', 'bounce', 'time', 'cart', 'conv'))
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
								<?php if (in_array('bounce', $columns, true)) : ?><td class="num <?php echo $bounce >= 0.6 && $row['sessions'] >= 5 ? 'is-bad' : ''; ?>"><?php echo esc_html(retails_vi_pct($row['bounced'], $row['sessions'])); ?></td><?php endif; ?>
								<?php if (in_array('time', $columns, true)) : ?><td class="num"><?php echo esc_html(retails_vi_duration($row['avg_engaged'])); ?></td><?php endif; ?>
								<?php if (in_array('cart', $columns, true)) : ?><td class="num"><?php echo esc_html(number_format_i18n($row['s4'])); ?></td><?php endif; ?>
								<?php if (in_array('conv', $columns, true)) : ?>
									<td class="num"><?php echo esc_html(number_format_i18n($row['leads'])); ?></td>
									<td class="num"><?php echo esc_html(number_format_i18n($row['orders'])); ?></td>
									<td class="num"><strong><?php echo esc_html(retails_vi_pct($row['orders'] + $row['leads'], $row['sessions'], 1)); ?></strong></td>
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

function retails_vi_event_label($type)
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
function retails_vi_report_data($period, $filters, $ads)
{
	global $wpdb;
	$start = $period['start_gmt'];
	$end = $period['end_gmt'];

	$m = retails_vi_summary($start, $end, $filters);
	$prev = retails_vi_summary($period['prev_start_gmt'], $period['prev_end_gmt'], $filters);
	$series = retails_vi_series($period, $filters);
	$devices = retails_vi_breakdown('s.device', $start, $end, $filters, 3);
	$by_device = array();
	foreach ($devices as $row) {
		$by_device[$row['label']] = $row;
	}

	$problem_types = array('checkout_error', 'payment_error', 'cart_error', 'price_error', 'upload_error', 'js_error', 'rage_click');
	$problems = retails_vi_event_breakdown($problem_types, $start, $end, $filters, 12);
	list($where, $params) = retails_vi_where($start, $end, $filters);
	$not_found = $wpdb->get_results($wpdb->prepare(
		'SELECT e.url AS label, COUNT(*) AS hits, COUNT(DISTINCT e.session_id) AS sessions, MAX(e.created_at) AS last_at FROM ' . retails_vi_table('events') . ' e JOIN ' . retails_vi_table('sessions') . " s ON s.id = e.session_id
		WHERE e.type = 'pageview' AND e.value = '404' AND {$where} GROUP BY e.url ORDER BY sessions DESC LIMIT 8",
		$params
	), ARRAY_A);

	$payment_errors = 0;
	foreach ($problems as $row) {
		if (in_array($row['type'], array('checkout_error', 'payment_error', 'cart_error'), true)) {
			$payment_errors += (int) $row['hits'];
		}
	}

	$timezones = retails_vi_breakdown('s.timezone', $start, $end, $filters, 50);
	$foreign = 0;
	foreach ($timezones as $row) {
		if (retails_vi_is_foreign_tz($row['label'])) {
			$foreign += (int) $row['sessions'];
		}
	}

	$repeat = $ads ? retails_vi_repeat_ad_clickers($start, $end) : array();
	$keywords = $ads ? retails_vi_breakdown("IF(s.term = '', '(not set)', s.term)", $start, $end, $filters, 15) : array();
	$campaigns = $ads ? retails_vi_merge_campaigns(retails_vi_breakdown("IF(s.campaign = '', '(not set)', s.campaign)", $start, $end, $filters, 40)) : array();
	$networks = $ads ? retails_vi_breakdown(retails_vi_network_sql('s'), $start, $end, $filters, 10) : array();
	$placements = $ads ? retails_vi_breakdown("SUBSTRING_INDEX(SUBSTRING_INDEX(s.referrer, '/', 3), '/', -1)", $start, $end, $filters, 15, retails_vi_placement_sql()) : array();
	$has_keywords = false;
	foreach (array_merge($keywords, $campaigns) as $row) {
		if ('(not set)' !== $row['label']) {
			$has_keywords = true;
			break;
		}
	}

	$landing_types = retails_vi_breakdown('s.landing_type', $start, $end, $filters, 1);
	$findings = retails_vi_findings($m, array(
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
	foreach (retails_vi_breakdown('HOUR(' . retails_vi_local_sql('s.started_at', $start, $end) . ')', $start, $end, $filters, 24) as $row) {
		$hours[(int) $row['label']] = array('sessions' => (int) $row['sessions'], 'conversions' => (int) ($row['orders'] + $row['leads']));
	}

	$orders = retails_vi_orders_report($period, $filters);
	// Orders each campaign earned, including those placed on a later visit
	// (the per-visit "Orders" column only counts orders made during the click).
	$credited = array();
	foreach ($orders as $order) {
		if ($order['source'] && in_array($order['source']['channel'], array('google_ads', 'paid_other'), true)) {
			$name = '' === (string) $order['source']['campaign'] ? '(not set)' : retails_vi_campaign_name($order['source']['campaign']);
			$credited[$name]['orders'] = ($credited[$name]['orders'] ?? 0) + 1;
			$credited[$name]['revenue'] = ($credited[$name]['revenue'] ?? 0) + $order['total'];
		}
	}
	foreach ($campaigns as &$campaign_row) {
		$name = '(not set)' === $campaign_row['label'] ? '(not set)' : retails_vi_campaign_name($campaign_row['label']);
		$campaign_row['credited_orders'] = $credited[$name]['orders'] ?? 0;
		$campaign_row['credited_revenue'] = $credited[$name]['revenue'] ?? 0;
	}
	unset($campaign_row);

	$robot_filters = array_merge($filters, array('robots' => 'only'));
	$robots = (int) ($ads ? 0 : retails_vi_summary($start, $end, $robot_filters)['sessions'] ?? 0);

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
		'landing' => retails_vi_breakdown("SUBSTRING_INDEX(s.landing_url, '?', 1)", $start, $end, $filters, 10),
		'exits' => retails_vi_breakdown("SUBSTRING_INDEX(s.exit_url, '?', 1)", $start, $end, $filters, 10, 's.order_id = 0'),
		'forms' => retails_vi_form_report($start, $end, $filters),
		'clicks' => retails_vi_event_breakdown(array('click', 'call_click', 'email_click'), $start, $end, $filters, 12),
		'channels' => retails_vi_breakdown('s.channel', $start, $end, $filters, 10),
		'browsers' => retails_vi_breakdown('s.browser', $start, $end, $filters, 8),
		'sources' => retails_vi_breakdown("IF(s.source = '', '(direct)', s.source)", $start, $end, $filters, 10),
		'orders' => $orders,
		'networks' => $networks,
		'placements' => $placements,
		'robots' => $robots,
	);
}

/**
 * Campaign rows merged by name: early ads sent a name, later ones the ID, and
 * both are the same campaign.
 */
function retails_vi_merge_campaigns($rows)
{
	$merged = array();
	$sum = array('sessions', 'visitors', 'ads', 'bounced', 'quick_exits', 's2', 's3', 's4', 's5', 's6', 'orders', 'leads', 'revenue', 'returning_visits');
	foreach ($rows as $row) {
		$key = '(not set)' === $row['label'] ? '(not set)' : retails_vi_campaign_name($row['label']);
		if (!isset($merged[$key])) {
			$merged[$key] = $row;
			// Prefer the ID as the label: it shows next to the name.
			continue;
		}
		$total = $merged[$key]['sessions'] + $row['sessions'];
		foreach (array('avg_engaged', 'avg_pages') as $avg) {
			$merged[$key][$avg] = $total > 0 ? ($merged[$key][$avg] * $merged[$key]['sessions'] + $row[$avg] * $row['sessions']) / $total : 0;
		}
		foreach ($sum as $col) {
			$merged[$key][$col] = ($merged[$key][$col] ?? 0) + ($row[$col] ?? 0);
		}
		if (preg_match('/^\d+$/', $row['label'])) {
			$merged[$key]['label'] = $row['label'];
		}
	}
	$merged = array_values($merged);
	usort($merged, static function ($a, $b) {
		return $b['sessions'] <=> $a['sessions'];
	});

	return array_slice($merged, 0, 15);
}

/**
 * Orders placed in the range, newest first, with where each came from. The
 * source and device filters apply to the credited source and the buying
 * visit's device.
 *
 * @return array[] Each: id, number, customer, total, ordered_gmt, source (array|null), channel.
 */
function retails_vi_orders_report($period, $filters = array(), $limit = 50)
{
	$ids = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'any',
		'posts_per_page' => $limit,
		'fields' => 'ids',
		'no_found_rows' => true,
		'orderby' => 'date',
		'order' => 'DESC',
		'date_query' => array(array('column' => 'post_date_gmt', 'after' => $period['start_gmt'], 'before' => $period['end_gmt'], 'inclusive' => true)),
	));
	update_meta_cache('post', $ids);

	$rows = array();
	foreach ($ids as $id) {
		$source = retails_vi_order_source($id);
		// Google Ads counts an order with its click ID as its own conversion.
		$channel = retails_vi_order_has_unlinked_ad_click($id, $source) ? 'google_ads' : ($source ? $source['channel'] : '');
		if ((!empty($filters['channel']) && $filters['channel'] !== $channel) || (!empty($filters['device']) && (!$source || $filters['device'] !== $source['device']))) {
			continue;
		}

		$billing = function_exists('retails_decode_order_meta_array') ? retails_decode_order_meta_array(get_post_meta($id, 'billing_address', true)) : array();
		$rows[] = array(
			'id' => (int) $id,
			'number' => '#' . strtoupper(preg_replace('/^order_/i', '', (string) get_post_meta($id, 'order_id', true) ?: (string) $id)),
			'customer' => trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? '')),
			'total' => function_exists('retails_order_net_total') ? retails_order_net_total($id) : (function_exists('retails_order_total') ? retails_order_total($id) : 0.0), // After refunds (inc/order-net-total.php).
			'ordered_gmt' => get_post_field('post_date_gmt', $id),
			'source' => $source,
			'channel' => $channel,
		);
	}

	return $rows;
}

function retails_vi_render_orders($orders, $ads)
{
	$revenue = array_sum(array_column($orders, 'total'));
	?>
	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2><?php echo $ads ? 'Orders from Google Ads and their keywords' : 'Orders and where they came from'; ?></h2>
			<span class="wvi-muted"><?php echo esc_html(sprintf(_n('%s order', '%s orders', count($orders)), number_format_i18n(count($orders))) . ' · ' . retails_vi_money($revenue)); ?> · credited to the last ad click in the 90 days before the order, as Google Ads does, else the last search or referral visit.</span>
		</div>
		<?php if (!$orders) : ?>
			<p class="wvi-empty">No orders<?php echo $ads ? ' from Google Ads' : ''; ?> in this range.</p>
		<?php else : ?>
			<div class="wvi-table-wrap">
				<table class="wvi-table wvi-orders">
					<thead><tr><th>Order</th><th>Customer</th><th class="num">Total</th><th>Source &amp; keyword</th><th>Landing page</th><th class="num" title="Visits in the 90 days up to the order">Visits</th><th>Ordered</th><th></th></tr></thead>
					<tbody>
						<?php foreach ($orders as $order) : ?>
							<?php $source = $order['source']; ?>
							<tr>
								<td class="wvi-nowrap"><a href="<?php echo esc_url(get_edit_post_link($order['id'])); ?>"><strong><?php echo esc_html($order['number']); ?></strong></a></td>
								<td><?php echo esc_html($order['customer'] ?: '—'); ?></td>
								<td class="num"><?php echo esc_html(retails_vi_money($order['total'])); ?></td>
								<td><?php echo retails_vi_order_source_html($order['id']); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?></td>
								<td class="wvi-path"><?php echo $source ? '<span title="' . esc_attr($source['landing']) . '">' . esc_html(retails_vi_short_path(strtok($source['landing'], '?'), 40)) . '</span>' : '—'; ?></td>
								<td class="num"><?php echo $source ? '<span title="' . esc_attr('First visit ' . retails_vi_date($source['first_at'])) . '">' . esc_html($source['visits']) . '</span>' : '—'; ?></td>
								<td class="wvi-nowrap"><?php echo esc_html(retails_vi_date($order['ordered_gmt'])); ?></td>
								<td class="wvi-nowrap"><?php if ($source) : ?><a href="<?php echo esc_url(retails_vi_url('retails-insights-visitors', array('session' => $source['order_visit_id']))); ?>">Journey →</a><?php endif; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Google Ads tab: each campaign by name and type, with what its clicks did and
 * the orders it earned (also those placed on a later visit).
 */
function retails_vi_render_campaigns($campaigns, $period, $filters)
{
	?>
	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2>Campaigns</h2>
			<span class="wvi-muted">Names come from the built-in list and Settings. "Orders earned" also counts orders placed days after the click.</span>
		</div>
		<?php if (!$campaigns) : ?>
			<p class="wvi-empty">No ad clicks in this range.</p>
		<?php else : ?>
			<div class="wvi-table-wrap">
				<table class="wvi-table">
					<thead><tr><th>Campaign</th><th class="num">Ad clicks</th><th class="num" title="One page, under 10 seconds, no action">Bounce</th><th class="num">Avg. time</th><th class="num">Got a price</th><th class="num">Cart</th><th class="num">Leads</th><th class="num" title="Orders credited to this campaign, including later visits">Orders earned</th><th class="num">Revenue</th><th class="num" title="Leads + orders earned per click">Conv.</th><th></th></tr></thead>
					<tbody>
						<?php foreach ($campaigns as $row) : ?>
							<?php
							$bounce = $row['sessions'] > 0 ? $row['bounced'] / $row['sessions'] : 0;
							$won = max((int) $row['orders'], (int) $row['credited_orders']);
							$dead = $row['sessions'] >= 30 && !$row['s4'] && !$row['leads'] && !$won;
							?>
							<tr>
								<td class="wvi-cell-label wvi-wrap"><?php echo retails_vi_campaign_html($row['label']); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?><?php echo $dead ? ' <span class="wvi-badge is-red" title="30+ clicks, no cart, lead or order">No results</span>' : ''; ?></td>
								<td class="num"><?php echo esc_html(number_format_i18n($row['sessions'])); ?></td>
								<td class="num <?php echo $bounce >= 0.6 && $row['sessions'] >= 5 ? 'is-bad' : ''; ?>"><?php echo esc_html(retails_vi_pct($row['bounced'], $row['sessions'])); ?></td>
								<td class="num"><?php echo esc_html(retails_vi_duration($row['avg_engaged'])); ?></td>
								<td class="num"><?php echo esc_html(number_format_i18n($row['s3'])); ?></td>
								<td class="num"><?php echo esc_html(number_format_i18n($row['s4'])); ?></td>
								<td class="num"><?php echo esc_html(number_format_i18n($row['leads'])); ?></td>
								<td class="num"><strong><?php echo esc_html(number_format_i18n($won)); ?></strong></td>
								<td class="num"><?php echo esc_html($row['credited_revenue'] > 0 ? retails_vi_money($row['credited_revenue']) : ($row['revenue'] > 0 ? retails_vi_money($row['revenue']) : '—')); ?></td>
								<td class="num"><strong><?php echo esc_html(retails_vi_pct($won + $row['leads'], $row['sessions'], 1)); ?></strong></td>
								<td class="wvi-nowrap"><?php if ('(not set)' !== $row['label']) : ?><a href="<?php echo esc_url(retails_vi_url('retails-insights-visitors', array_filter(array('period' => $period['key'], 'from' => $period['start']->format('Y-m-d'), 'to' => $period['end']->modify('-1 second')->format('Y-m-d'), 'channel' => 'google_ads', 'device' => $filters['device'], 's' => $row['label'])))); ?>">Visits →</a><?php endif; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Overview and Google Ads reports share one layout; the Ads one is filtered
 * to ad clicks and adds keyword, campaign and click-fraud sections.
 */
function retails_vi_render_report($ads)
{
	global $wpdb;
	$page = $ads ? 'retails-insights-ads' : 'retails-insights';
	$period = retails_vi_period();
	$filters = retails_vi_request_filters();
	if ($ads) {
		$filters['channel'] = 'google_ads';
	}
	$start = $period['start_gmt'];
	$end = $period['end_gmt'];

	$report = retails_vi_report_data($period, $filters, $ads);
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

	$shown_filters = $ads ? array('device' => $filters['device'], 'network' => $filters['network']) : $filters;
	retails_vi_render_toolbar($period, $page, $shown_filters, $ads ? array('device') : array('channel', 'device'));
	retails_vi_render_export_bar($period, $shown_filters);
	if (function_exists('retails_vi_render_growth_teaser')) {
		retails_vi_render_growth_teaser($period);
	}

	$conversions = $m['orders'] + $m['leads'];
	$prev_conversions = ($prev['orders'] ?? 0) + ($prev['leads'] ?? 0);
	?>
	<div class="wvi-stats">
		<?php
		retails_vi_stat($ads ? 'Ad clicks (visits)' : 'Visits', number_format_i18n($m['sessions']), $ads ? 'megaphone' : 'chart-line', 'blue', retails_vi_change($m['sessions'], $prev['sessions'] ?? 0));
		retails_vi_stat('Unique visitors', number_format_i18n($m['visitors']), 'groups', 'purple', retails_vi_change($m['visitors'], $prev['visitors'] ?? 0));
		retails_vi_stat('Bounce rate', retails_vi_pct($m['bounced'], $m['sessions']), 'migrate', 'red', retails_vi_change($m['sessions'] ? $m['bounced'] / $m['sessions'] : 0, !empty($prev['sessions']) ? $prev['bounced'] / $prev['sessions'] : 0, true), 'Visits with one page, under 10 seconds and no action');
		retails_vi_stat('Avg. engaged time', retails_vi_duration($m['avg_engaged']), 'clock', 'amber', retails_vi_change($m['avg_engaged'], $prev['avg_engaged'] ?? 0), 'Time with the page open and in use');
		retails_vi_stat('Added to cart', number_format_i18n($m['s4']), 'cart', 'teal', retails_vi_change($m['s4'], $prev['s4'] ?? 0));
		retails_vi_stat('Leads (calls, quotes)', number_format_i18n($m['leads']), 'phone', 'blue', retails_vi_change($m['leads'], $prev['leads'] ?? 0));
		retails_vi_stat('Orders · ' . retails_vi_money($m['revenue']), number_format_i18n($m['orders']), 'yes-alt', 'green', retails_vi_change($m['orders'], $prev['orders'] ?? 0));
		retails_vi_stat('Conversion rate', retails_vi_pct($conversions, $m['sessions'], 1), 'performance', 'green', retails_vi_change($m['sessions'] ? $conversions / $m['sessions'] : 0, !empty($prev['sessions']) ? $prev_conversions / $prev['sessions'] : 0), 'Orders + leads per visit');
		?>
	</div>

	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2><?php echo $ads ? 'Why Google Ads visitors are not buying' : 'What is stopping visitors from buying'; ?></h2>
			<span class="wvi-muted">Worked out from this range's visits.<?php if (!empty($report['robots'])) : ?> <a href="<?php echo esc_url(retails_vi_url('retails-insights-visitors', array('period' => $period['key'], 'from' => $period['start']->format('Y-m-d'), 'to' => $period['end']->modify('-1 second')->format('Y-m-d'), 'outcome' => 'robots'))); ?>" title="Crawlers, screenshot services and Google's page checker that run like a browser"><?php echo esc_html(sprintf(_n('%s robot visit left out', '%s robot visits left out', $report['robots']), number_format_i18n($report['robots']))); ?></a>.<?php endif; ?></span>
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
		<?php retails_vi_render_funnel($m, $ads ? 'Google Ads funnel' : 'Purchase funnel'); ?>
	</div>

	<?php retails_vi_render_orders($report['orders'], $ads); ?>

	<?php if ($ads) : ?>
		<?php retails_vi_render_campaigns($campaigns, $period, $filters); ?>
		<div class="wvi-grid">
			<?php
			$network_names = retails_vi_ad_networks();
			retails_vi_render_breakdown('Where the ads were clicked', $report['networks'], static function ($row) use ($network_names, $page, $period, $filters) {
				$label = $network_names[$row['label']] ?? ($row['label'] ?: 'Unknown');
				$url = retails_vi_url($page, array_filter(array('period' => $period['key'], 'from' => 'custom' === $period['key'] ? $period['start']->format('Y-m-d') : '', 'to' => 'custom' === $period['key'] ? $period['end']->modify('-1 second')->format('Y-m-d') : '', 'device' => $filters['device'], 'network' => $row['label'])));
				return '<a href="' . esc_url($url) . '" title="Show only these clicks">' . esc_html($label) . '</a>';
			}, 'Worked out from the page each click came from, or from {network} when the tracking suffix sends it.', array('sessions', 'bounce', 'time', 'cart', 'conv'));
			retails_vi_render_breakdown('Partner websites that sent ad clicks', $report['placements'], static function ($row) {
				$host = preg_replace('/^www\./', '', (string) $row['label']);
				return esc_html($host ?: '(unknown)') . ($host ? ' <button type="button" class="wvi-copy button-link" data-copy="' . esc_attr($host) . '">Copy</button>' : '');
			}, 'Sites with no carts or leads are candidates for Google Ads → Content → Exclusions (placements).', array('sessions', 'bounce', 'cart', 'conv'));
			?>
		</div>
		<?php
		retails_vi_render_breakdown('Keywords', $keywords, static function ($row) {
			return esc_html($row['label']);
		}, 'From utm_term. Performance Max, Shopping and YouTube clicks carry no keyword, so they show as (not set).');
		?>
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
									<td class="num"><?php echo esc_html(retails_vi_duration($row['avg_engaged'])); ?></td>
									<td><?php echo retails_vi_device_icon($row['device']); // phpcs:ignore ?> <?php echo esc_html(str_replace('_', ' ', $row['timezone'])); ?></td>
									<td><?php echo $row['orders'] > 0 ? '<span class="wvi-badge is-green">Bought</span>' : ($row['leads'] > 0 ? '<span class="wvi-badge is-blue">Lead</span>' : '<span class="wvi-badge is-red">Nothing</span>'); ?><?php if (!empty($row['carrier'])) : ?> <span class="wvi-badge" title="A mobile carrier address many phones share. Excluding it would hide your ads from real buyers.">Phone carrier, don't exclude</span><?php endif; ?></td>
									<td><?php echo esc_html(retails_vi_ago($row['last_seen'])); ?></td>
									<td><a href="<?php echo esc_url(retails_vi_url('retails-insights-visitors', array('period' => $period['key'], 'from' => $period['start']->format('Y-m-d'), 'to' => $period['end']->modify('-1 second')->format('Y-m-d'), 'channel' => 'google_ads', 's' => $row['ip']))); ?>">View visits →</a></td>
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
		retails_vi_render_breakdown('Landing pages', $report['landing'], static function ($row) {
			return sprintf('<a href="%s" target="_blank" rel="noopener" title="%s">%s</a>', esc_url(retails_vi_site_link($row['label'])), esc_attr($row['label']), esc_html(retails_vi_short_path($row['label'])));
		}, 'First page of each visit.');
		retails_vi_render_breakdown('Exit pages of visitors who did not buy', $report['exits'], static function ($row) {
			return sprintf('<a href="%s" target="_blank" rel="noopener" title="%s">%s</a>', esc_url(retails_vi_site_link($row['label'])), esc_attr($row['label']), esc_html(retails_vi_short_path($row['label'])));
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
								<td><span class="wvi-badge <?php echo 'rage_click' === $row['type'] || 'js_error' === $row['type'] ? 'is-amber' : 'is-red'; ?>"><?php echo esc_html(retails_vi_event_label($row['type'])); ?></span></td>
								<td><?php echo esc_html($row['label'] ?: '—'); ?></td>
								<td class="num"><?php echo esc_html($row['hits']); ?></td>
								<td class="num"><?php echo esc_html($row['sessions']); ?></td>
								<td><?php echo esc_html(retails_vi_ago($row['last_at'])); ?></td>
								<td><a href="<?php echo esc_url(retails_vi_url('retails-insights-visitors', array('period' => $period['key'], 'from' => $period['start']->format('Y-m-d'), 'to' => $period['end']->modify('-1 second')->format('Y-m-d'), 'event' => $row['type'], 'channel' => $filters['channel'], 'device' => $filters['device']))); ?>">Visits →</a></td>
							</tr>
						<?php endforeach; ?>
						<?php foreach ($not_found as $row) : ?>
							<tr>
								<td><span class="wvi-badge is-red">Page not found</span></td>
								<td><code><?php echo esc_html(retails_vi_short_path($row['label'], 70)); ?></code></td>
								<td class="num"><?php echo esc_html($row['hits']); ?></td>
								<td class="num"><?php echo esc_html($row['sessions']); ?></td>
								<td><?php echo esc_html(retails_vi_ago($row['last_at'])); ?></td>
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
				<span class="wvi-muted">Forms visitors started typing in, and the field where most of them gave up. Finished = sent, added to the cart or ordered.</span>
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
									<td class="num <?php echo $row['started'] >= 3 && $row['submitted'] < $row['started'] / 2 ? 'is-bad' : ''; ?>"><?php echo esc_html(retails_vi_pct($row['started'] - $row['submitted'], $row['started'])); ?></td>
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
									<td><?php echo 'click' !== $row['type'] ? '<span class="wvi-badge is-blue">' . esc_html(retails_vi_event_label($row['type'])) . '</span> ' : ''; ?><?php echo esc_html($row['label'] ?: '(no text)'); ?></td>
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
			return retails_vi_device_icon($row['label']) . ' ' . esc_html(ucfirst($row['label']));
		};
		retails_vi_render_breakdown('Devices', $devices, $label_device, '', array('sessions', 'bounce', 'conv'));
		if (!$ads) {
			retails_vi_render_breakdown('Traffic sources', $report['channels'], static function ($row) {
				return retails_vi_channel_badge($row['label']);
			}, '', array('sessions', 'bounce', 'conv'));
		} else {
			retails_vi_render_breakdown('Browsers', $report['browsers'], static function ($row) {
				return esc_html($row['label']);
			}, '', array('sessions', 'bounce', 'conv'));
		}
		retails_vi_render_breakdown('Locations (time zone)', array_slice($timezones, 0, 10), static function ($row) {
			$label = $row['label'] ? retails_vi_region($row['label']) : 'Unknown';
			return '<span title="' . esc_attr($row['label']) . '">' . esc_html($label) . '</span>' . (retails_vi_is_foreign_tz($row['label']) ? ' <span class="wvi-badge is-amber">Outside US</span>' : '');
		}, '', array('sessions', 'bounce', 'conv'));
		?>
	</div>

	<?php if (!$ads) : ?>
		<?php
		retails_vi_render_breakdown('Referring sites and search engines', $report['sources'], static function ($row) {
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
function retails_vi_load_people($rows)
{
	global $wpdb;
	$people = &retails_vi_people_cache();
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
		foreach ($wpdb->get_results($wpdb->prepare('SELECT visitor_key, user_id, last_seen FROM ' . retails_vi_table('sessions') . " WHERE user_id > 0 AND visitor_key IN ({$in}) ORDER BY last_seen ASC", $visitor_keys)) as $match) {
			$by_visitor[$match->visitor_key] = $match;
		}
	}

	// The name, email and phone given in a quote request on any visit from the
	// same browser: most buyers never make an account.
	$requests = array();
	if ($visitor_keys) {
		foreach ($wpdb->get_results($wpdb->prepare('SELECT s.visitor_key, e.label, e.value FROM ' . retails_vi_table('events') . ' e JOIN ' . retails_vi_table('sessions') . " s ON s.id = e.session_id WHERE e.type = 'quote_submit' AND s.visitor_key IN ({$in}) ORDER BY e.id ASC", $visitor_keys)) as $match) {
			$requests[$match->visitor_key] = $match;
		}
	}

	// What kind of lead each visit was, for the Outcome badge.
	$lead_rows = array_filter($rows, static function ($row) {
		return (int) $row->is_lead;
	});
	if ($lead_rows) {
		$types = &retails_vi_lead_types_cache();
		$ids = implode(',', array_map(static function ($row) {
			return (int) $row->id;
		}, $lead_rows));
		foreach ($wpdb->get_results('SELECT session_id, GROUP_CONCAT(DISTINCT type) AS types FROM ' . retails_vi_table('events') . " WHERE session_id IN ({$ids}) AND type IN ('quote_submit', 'call_click', 'email_click') GROUP BY session_id") as $match) { // phpcs:ignore WordPress.DB.PreparedSQL -- integers.
			$types[(int) $match->session_id] = explode(',', $match->types);
		}
	}

	$user_ids = array_unique(array_filter(array_merge(array_map(static function ($row) {
		return (int) $row->user_id;
	}, $rows), array_map(static function ($match) {
		return (int) $match->user_id;
	}, array_values($by_visitor)))));
	$users = array();
	$registered = array();
	if ($user_ids) {
		foreach (get_users(array('include' => $user_ids, 'fields' => array('ID', 'display_name', 'user_email', 'user_login', 'user_registered'))) as $user) {
			$name = trim(get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true));
			$users[(int) $user->ID] = array('user_id' => (int) $user->ID, 'name' => $name ?: $user->display_name, 'email' => $user->user_email);
			$registered[(int) $user->ID] = strtotime($user->user_registered . ' UTC');
		}
	}
	// A visit can't belong to an account made after it: copying the users
	// table between the live and local sites can give a deleted customer's ID
	// to someone new.
	$account = static function ($user_id, $seen) use ($users, $registered) {
		return isset($users[$user_id]) && $registered[$user_id] <= strtotime($seen . ' UTC') + MINUTE_IN_SECONDS ? $users[$user_id] : null;
	};

	foreach ($rows as $row) {
		$person = null;
		$member = $account((int) $row->user_id, $row->last_seen);
		$linked = isset($by_visitor[$row->visitor_key]) ? $account((int) $by_visitor[$row->visitor_key]->user_id, $by_visitor[$row->visitor_key]->last_seen) : null;
		if ($member) {
			$person = $member + array('type' => 'member', 'note' => 'Logged in during this visit');
		} elseif ($linked) {
			$person = $linked + array('type' => 'linked', 'note' => 'Not logged in on this visit; same browser logged in on another visit');
		} elseif ((int) $row->order_id && function_exists('retails_decode_order_meta_array')) {
			$billing = retails_decode_order_meta_array(get_post_meta((int) $row->order_id, 'billing_address', true));
			$name = trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? ''));
			if ('' !== $name) {
				$person = array('user_id' => 0, 'name' => $name, 'email' => (string) ($billing['billing_email'] ?? ''), 'type' => 'buyer', 'note' => 'Guest checkout; name from the order');
			}
		}
		if (!$person && isset($requests[$row->visitor_key]) && function_exists('retails_vi_lead_by_title')) {
			$lead = retails_vi_lead_by_title($requests[$row->visitor_key]->label, (int) $requests[$row->visitor_key]->value);
			$name = $lead ? trim((string) get_post_meta($lead, '_contact_name', true)) : '';
			if ('' !== $name) {
				$phone = (string) get_post_meta($lead, '_contact_phone', true);
				$person = array('user_id' => 0, 'name' => $name, 'email' => (string) get_post_meta($lead, '_contact_email', true), 'type' => 'lead', 'lead_id' => $lead, 'note' => 'Name from their quote request' . ($phone ? ' · ' . $phone : ''));
			}
		}
		$people[(int) $row->id] = $person;
	}
}

function &retails_vi_people_cache()
{
	static $people = array();

	return $people;
}

function &retails_vi_lead_types_cache()
{
	static $types = array();

	return $types;
}

function retails_vi_person($row)
{
	$people = &retails_vi_people_cache();
	if (!array_key_exists((int) $row->id, $people)) {
		retails_vi_load_people(array($row));
	}

	return $people[(int) $row->id];
}

function retails_vi_person_html($person)
{
	if (!$person) {
		return '';
	}
	$icons = array('member' => 'admin-users', 'linked' => 'admin-users', 'buyer' => 'cart', 'lead' => 'clipboard');

	return sprintf(
		'<span class="wvi-person is-%s" title="%s"><span class="dashicons dashicons-%s"></span>%s</span>',
		esc_attr($person['type']),
		esc_attr($person['note'] . ($person['email'] ? ' · ' . $person['email'] : '')),
		esc_attr($icons[$person['type']]),
		esc_html($person['name'])
	);
}

/* Visitors list --------------------------------------------------------- */

function retails_vi_render_visitors()
{
	global $wpdb;
	$period = retails_vi_period();
	$filters = retails_vi_request_filters();
	$outcome = isset($_GET['outcome']) ? sanitize_key(wp_unslash($_GET['outcome'])) : '';
	$event = isset($_GET['event']) ? sanitize_key(wp_unslash($_GET['event'])) : '';
	$search = isset($_GET['s']) ? trim(sanitize_text_field(wp_unslash($_GET['s']))) : '';
	$per_page = 30;
	$paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);

	list($where, $params) = retails_vi_where($period['start_gmt'], $period['end_gmt'], array_merge($filters, array('robots' => 'robots' === $outcome ? 'only' : '')));
	$table = retails_vi_table('sessions');
	$outcomes = array(
		'' => 'All visits',
		'members' => 'Logged-in customers',
		'purchased' => 'Purchased',
		'lead' => 'Leads',
		'abandoned' => 'Abandoned cart / checkout',
		'engaged' => 'Engaged, no purchase',
		'bounced' => 'Bounced',
		'robots' => 'Robots (left out of reports)',
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
			$where .= ' AND s.order_id = 0 AND NOT ' . retails_vi_bounce_sql();
			break;
		case 'bounced':
			$where .= ' AND ' . retails_vi_bounce_sql();
			break;
	}
	if ($event && in_array($event, retails_vi_event_types(), true)) {
		$where .= ' AND EXISTS (SELECT 1 FROM ' . retails_vi_table('events') . ' x WHERE x.session_id = s.id AND x.type = %s)';
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
	retails_vi_load_people($rows);

	add_action('retails_vi_toolbar_fields', static function () use ($outcome, $outcomes, $search, $event) {
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
	retails_vi_render_toolbar($period, 'retails-insights-visitors', array_merge($filters, array('outcome' => $outcome, 's' => $search, 'event' => $event)));
	?>
	<section class="wvi-card">
		<div class="wvi-section-head wvi-pad">
			<h2><?php echo esc_html(number_format_i18n($total)); ?> visits</h2>
			<span class="wvi-muted"><?php echo $event ? esc_html('Only visits with: ' . retails_vi_event_label($event) . ' · ') : ''; ?>Click a row to see the visitor's full journey.</span>
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
							<?php retails_vi_visit_row($row); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($pages > 1) : ?>
				<div class="wvi-pagination">
					<?php
					// Built from the filters, not the request URL: live updates render
					// this list inside admin-ajax.php.
					$list_args = array_filter(array_merge($filters, array(
						'period' => $period['key'],
						'from' => 'custom' === $period['key'] ? $period['start']->format('Y-m-d') : '',
						'to' => 'custom' === $period['key'] ? $period['end']->modify('-1 second')->format('Y-m-d') : '',
						'outcome' => $outcome,
						'event' => $event,
						's' => $search,
					)));
					echo paginate_links(array( // phpcs:ignore WordPress.Security.EscapeOutput
						'base' => add_query_arg('paged', '%#%', retails_vi_url('retails-insights-visitors', $list_args)),
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

function retails_vi_visit_row($row, $live = false)
{
	$url = retails_vi_url('retails-insights-visitors', array('session' => $row->id));
	$online = strtotime($row->last_seen . ' UTC') > time() - 5 * MINUTE_IN_SECONDS;
	$network = retails_vi_ad_network($row);
	$source_detail = array_filter(array(
		retails_vi_campaign_name($row->campaign),
		$row->term ? '“' . $row->term . '”' : '',
		$network ? (retails_vi_ad_networks()[$network] ?? '') : '',
		'google_ads' !== $row->channel ? $row->source : '',
	));
	?>
	<tr class="wvi-row-link" data-id="<?php echo esc_attr($row->id); ?>" data-href="<?php echo esc_url($url); ?>">
		<td class="wvi-nowrap">
			<?php if ($online) : ?><span class="wvi-live-dot" title="Online now"></span><?php endif; ?>
			<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($live ? retails_vi_ago($row->started_at) : retails_vi_date($row->started_at)); ?></a>
			<?php if (!(int) $row->is_new) : ?><span class="wvi-badge is-soft" title="Has visited before">Returning</span><?php endif; ?>
			<?php echo retails_vi_person_html(retails_vi_person($row)); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>
			<?php echo function_exists('retails_vi_snap_badge') ? retails_vi_snap_badge($row->session_key) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>
		</td>
		<td><?php echo retails_vi_channel_badge($row->channel); // phpcs:ignore ?><?php if ($source_detail) : ?><span class="wvi-sub"><?php echo esc_html(implode(' · ', $source_detail)); ?></span><?php endif; ?></td>
		<td class="wvi-path" title="<?php echo esc_attr($live ? $row->exit_url : $row->landing_url); ?>">
			<?php echo esc_html(retails_vi_short_path($live ? $row->exit_url : $row->landing_url, 40)); ?>
		</td>
		<td class="wvi-nowrap"><?php echo retails_vi_device_icon($row->device); // phpcs:ignore ?> <span class="wvi-sub-inline"><?php echo esc_html(retails_vi_location($row) ?: $row->browser); ?></span></td>
		<td class="num"><?php echo esc_html($row->pageviews); ?></td>
		<td class="num"><?php echo esc_html(retails_vi_duration($row->engaged_seconds)); ?></td>
		<td><?php echo retails_vi_stage_pips((int) $row->stage); // phpcs:ignore ?></td>
		<td><?php echo retails_vi_outcome($row); // phpcs:ignore ?><?php if ((int) $row->errors) : ?> <span class="wvi-badge is-red" title="Errors shown to this visitor"><?php echo esc_html($row->errors); ?> err</span><?php endif; ?></td>
		<?php if ($live) : ?>
			<td class="wvi-nowrap">
				<?php if ($online && !empty($GLOBALS['retails_vi_chat_map'][$row->visitor_key])) : ?>
					<button type="button" class="button button-small" data-chat-visitor="<?php echo esc_attr($GLOBALS['retails_vi_chat_map'][$row->visitor_key]); ?>"><span class="dashicons dashicons-format-chat" style="font-size:16px;width:16px;height:16px;margin:3px 2px 0 0"></span> Message</button>
				<?php endif; ?>
				<?php echo $online && function_exists('retails_vi_snap_button') ? retails_vi_snap_button($row->id) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping. ?>
			</td>
		<?php endif; ?>
	</tr>
	<?php
}

/* Single visit ---------------------------------------------------------- */

/**
 * The number a call tap dialed, in words. For visitors from an ad, Google's
 * call tracking (retails_deferred_conversion_tracking) swaps the main line
 * for a forwarding number that rings through to 866-436-2101, so a tap on any
 * other number is shown as the main line, with Google's number alongside.
 */
function retails_vi_call_label($label)
{
	$digits = preg_replace('/\D/', '', (string) $label);
	$local = preg_replace('/^1(?=\d{10}$)/', '', $digits);
	if ('8664362101' === $local) {
		return '866-436-2101';
	}
	if (10 !== strlen($local)) {
		return (string) $label;
	}

	return '866-436-2101 (via Google ad number ' . substr($local, 0, 3) . '-' . substr($local, 3, 3) . '-' . substr($local, 6) . ')';
}

function retails_vi_event_text($event)
{
	$label = $event->label;
	switch ($event->type) {
		case 'pageview':
			return array('visibility', '404' === $event->value ? 'Hit a “page not found” page' : 'Viewed ' . ($label ?: 'a page'), '404' === $event->value ? 'bad' : '');
		case 'click':
			return array('admin-links', 'Clicked “' . ($label ?: 'element') . '”', '');
		case 'call_click':
			return array('phone', 'Tapped to call ' . retails_vi_call_label($label), 'good');
		case 'email_click':
			return array('email', 'Clicked to email ' . $label, 'good');
		case 'rage_click':
			return array('warning', 'Rage-clicked (clicked repeatedly in frustration) on “' . $label . '”', 'warn');
		case 'configure':
			return array('admin-settings', 'Started choosing options' . ($label ? ' (' . $label . ')' : ''), '');
		case 'price_quote':
			return array('tag', 'Got a price' . ($event->value ? ': ' . retails_vi_money($event->value) : ''), '');
		case 'price_error':
			return array('dismiss', 'Pricing error: ' . $label . ($event->value ? ' (entered ' . $event->value . ')' : ''), 'bad');
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
		case 'exit_survey':
			return array('feedback', 'Answered the exit survey: “' . $label . '”', 'warn');
		case 'sa_view':
		case 'sa_click':
			// Recorded by the SFS Shopping Assistant plugin.
			if (function_exists('retails_sa_event_text')) {
				return retails_sa_event_text($event);
			}
			break;
	}

	return array('marker', $event->type . ' ' . $label, '');
}

function retails_vi_render_session($id)
{
	global $wpdb;
	$session = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . retails_vi_table('sessions') . ' WHERE id = %d', $id));
	if (!$session) {
		echo '<div class="wvi-alert is-error">That visit was not found. It may have been deleted after the retention period.</div>';
		return;
	}

	$events = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . retails_vi_table('events') . ' WHERE session_id = %d ORDER BY created_at, id LIMIT 2000', $id));
	// Screenshots an admin took (inc/visitor-snaps.php), shown as steps of the journey.
	$snaps = function_exists('retails_vi_snap_timeline_list') ? retails_vi_snap_timeline_list($session->session_key) : array();
	$others =$wpdb->get_results($wpdb->prepare('SELECT * FROM ' . retails_vi_table('sessions') . ' WHERE visitor_key = %s AND id <> %d ORDER BY started_at DESC LIMIT 20', $session->visitor_key, $id));
	$start = strtotime($session->started_at . ' UTC');
	$duration = max(0, strtotime($session->last_seen . ' UTC') - $start);
	$back = wp_get_referer() && false !== strpos(wp_get_referer(), 'retails-insights') ? wp_get_referer() : retails_vi_url('retails-insights-visitors');

	$person = retails_vi_person($session);
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
	$network = retails_vi_ad_network($session);
	$placement = retails_vi_ad_placement($session);
	$facts += array(
		'Source' => retails_vi_channel_badge($session->channel) . ($session->source ? ' ' . esc_html($session->source . ($session->medium ? ' / ' . $session->medium : '')) : ''),
		'Campaign' => $session->campaign ? retails_vi_campaign_html($session->campaign) : '—',
		'Keyword' => esc_html($session->term ?: '—'),
	);
	if ($network) {
		$facts['Ad shown on'] = esc_html(retails_vi_ad_networks()[$network] ?? $network) . ($placement ? ' <span class="wvi-muted">' . esc_html($placement) . '</span>' : '');
	}
	if (retails_vi_looks_automated(array('device' => $session->device, 'os' => $session->os, 'browser' => $session->browser), $session->screen)) {
		$facts['Robot?'] = '<span class="wvi-badge is-amber">Likely automated</span> <span class="wvi-muted">Its screen size is one only crawlers report; left out of the reports.</span>';
	}
	$facts += array(
		'Ad click ID' => esc_html($session->click_type ?: '—'),
		'Referrer' => $session->referrer ? '<span class="wvi-break">' . esc_html($session->referrer) . '</span>' : '—',
		'Landing page' => '<a href="' . esc_url(retails_vi_site_link($session->landing_url)) . '" target="_blank" rel="noopener" class="wvi-break">' . esc_html(retails_vi_short_path($session->landing_url, 80)) . '</a>',
		'Device' => retails_vi_device_icon($session->device) . ' ' . esc_html(ucfirst($session->device) . ' · ' . $session->os . ' · ' . $session->browser . ($session->screen ? ' · ' . $session->screen : '')),
		'Location' => esc_html(retails_vi_location($session) ?: '—') . ($session->timezone || $session->language ? ' <span class="wvi-muted">(' . esc_html(trim($session->timezone . ' ' . $session->language)) . ')</span>' : ''),
		'IP address' => $session->ip ? '<code>' . esc_html($session->ip) . '</code> <a href="' . esc_url(retails_vi_url('retails-insights-visitors', array('period' => 'custom', 'from' => wp_date('Y-m-d', time() - max(7, (int) retails_vi_setting('retention_days')) * DAY_IN_SECONDS), 'to' => wp_date('Y-m-d'), 's' => $session->ip))) . '">all visits from this IP</a>' : '—',
		'Visitor' => ((int) $session->is_new ? 'First visit' : 'Returning visitor') . ' · ' . (count($others) + 1) . ' visit(s) on record',
	);
	// Orders are found by the number in the purchase event: database copies
	// between the live and local sites can renumber the order's post.
	$orders = array();
	foreach ($events as $event) {
		if ('purchase' !== $event->type) {
			continue;
		}
		$number = preg_replace('/^Order #/', '', $event->label);
		$found = get_posts(array('post_type' => 'order', 'post_status' => 'any', 'meta_key' => 'order_id', 'meta_value' => $number, 'fields' => 'ids', 'posts_per_page' => 1, 'no_found_rows' => true));
		$text = '#' . strtoupper(preg_replace('/^order_/i', '', $number));
		$orders[] = $found
			? '<a href="' . esc_url(get_edit_post_link($found[0])) . '">' . esc_html($text) . '</a> · ' . esc_html(retails_vi_money(function_exists('retails_order_net_total') ? retails_order_net_total($found[0]) : (function_exists('retails_order_total') ? retails_order_total($found[0]) : 0)))
			: esc_html($text) . ' <span class="wvi-muted">(deleted)</span>';
	}
	if ($orders) {
		$facts['Order'] = implode('<br>', $orders);
	}
	// Quote requests are found by their title for the same reason.
	$requests = array();
	foreach ($events as $event) {
		if ('quote_submit' === $event->type && function_exists('retails_vi_lead_by_title')) {
			$lead = retails_vi_lead_by_title($event->label, (int) $event->value);
			$requests[] = $lead
				? '<a href="' . esc_url(get_edit_post_link($lead)) . '">' . esc_html(get_post_meta($lead, '_contact_name', true) ?: get_the_title($lead)) . '</a> · ' . esc_html(retails_vi_lead_wants($lead))
					. '<br><span class="wvi-muted">' . esc_html(implode(' · ', array_filter(array(get_post_meta($lead, '_contact_phone', true), get_post_meta($lead, '_contact_email', true))))) . '</span>'
				: esc_html($event->label) . ' <span class="wvi-muted">(deleted)</span>';
		}
	}
	if ($requests) {
		$facts['Quote request'] = implode('<br>', $requests);
	}
	?>
	<p><a href="<?php echo esc_url($back); ?>" class="wvi-back">← Back to visits</a></p>

	<div class="wvi-session-head wvi-card">
		<div>
			<h2><?php echo $person ? esc_html($person['name']) . ' · ' : ''; ?><?php echo esc_html(retails_vi_date($session->started_at, 'l, M j, Y · g:i a')); ?></h2>
			<p class="wvi-muted">
				<?php echo esc_html(sprintf('%d page(s) · %s engaged · %s on site · %d%% max scroll', $session->pageviews, retails_vi_duration($session->engaged_seconds), retails_vi_duration($duration), $session->max_scroll)); ?>
			</p>
		</div>
		<div class="wvi-session-outcome">
			<?php echo retails_vi_outcome($session); // phpcs:ignore ?>
			<div class="wvi-stage-line"><?php echo retails_vi_stage_pips((int) $session->stage); // phpcs:ignore ?> <span><?php echo esc_html(retails_vi_stages()[(int) $session->stage] ?? ''); ?></span></div>
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
						list($icon, $text, $tone) = retails_vi_event_text($event);
						$offset = max(0, strtotime($event->created_at . ' UTC') - $start);
						if ($snaps) {
							retails_vi_snap_timeline_items($snaps, strtotime($event->created_at . ' UTC') - 1, $start, $session->id);
						}
						?>
						<li class="is-<?php echo esc_attr($event->type); ?> <?php echo $tone ? 'tone-' . esc_attr($tone) : ''; ?>">
							<span class="wvi-tl-time" title="<?php echo esc_attr(retails_vi_date($event->created_at, 'g:i:s a')); ?>">+<?php echo esc_html(sprintf('%d:%02d', floor($offset / 60), $offset % 60)); ?></span>
							<span class="wvi-tl-icon dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
							<div class="wvi-tl-body">
								<div class="wvi-tl-text"><?php echo esc_html($text); ?></div>
								<?php if ('pageview' === $event->type) : ?>
									<div class="wvi-tl-meta">
										<a href="<?php echo esc_url(retails_vi_site_link($event->url)); ?>" target="_blank" rel="noopener"><?php echo esc_html(retails_vi_short_path($event->url, 70)); ?></a>
										<span class="wvi-chip"><?php echo esc_html(retails_vi_duration($event->seconds)); ?> on page</span>
										<span class="wvi-chip">scrolled <?php echo esc_html($event->scroll); ?>%</span>
									</div>
								<?php elseif ('click' === $event->type && $event->value) : ?>
									<div class="wvi-tl-meta"><span class="wvi-break"><?php echo esc_html(retails_vi_short_path(preg_replace('#^https?://[^/]+#', '', $event->value), 70)); ?></span></div>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
					<?php if ($snaps) {
						retails_vi_snap_timeline_items($snaps, PHP_INT_MAX, $start, $session->id);
					} ?>
					<?php if (strtotime($session->last_seen . ' UTC') < time() - 5 * MINUTE_IN_SECONDS && !(int) $session->order_id) : ?>
						<li class="tone-end">
							<span class="wvi-tl-time">+<?php echo esc_html(sprintf('%d:%02d', floor($duration / 60), $duration % 60)); ?></span>
							<span class="wvi-tl-icon dashicons dashicons-exit"></span>
							<div class="wvi-tl-body"><div class="wvi-tl-text">Left the site from <?php echo esc_html(retails_vi_short_path($session->exit_url, 60)); ?></div></div>
						</li>
					<?php endif; ?>
				</ol>
			<?php endif; ?>
		</section>

		<div>
			<?php if (function_exists('retails_vi_snap_render_section')) {
				retails_vi_snap_render_section($session);
			} ?>
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
					<?php retails_vi_load_people($others); ?>
					<ul class="wvi-other-visits">
						<?php foreach ($others as $other) : ?>
							<li>
								<a href="<?php echo esc_url(retails_vi_url('retails-insights-visitors', array('session' => $other->id))); ?>"><?php echo esc_html(retails_vi_date($other->started_at)); ?></a>
								<?php echo retails_vi_channel_badge($other->channel); // phpcs:ignore ?>
								<span class="wvi-muted"><?php echo esc_html($other->pageviews . ' pages'); ?></span>
								<?php echo retails_vi_outcome($other); // phpcs:ignore ?>
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

function retails_vi_live_count()
{
	global $wpdb;

	return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . retails_vi_table('sessions') . ' s WHERE last_seen >= %s AND NOT ' . retails_vi_automated_sql('s'), gmdate('Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS)));
}

function retails_vi_live_rows_html()
{
	global $wpdb;
	$rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . retails_vi_table('sessions') . ' s WHERE last_seen >= %s AND NOT ' . retails_vi_automated_sql('s') . ' ORDER BY last_seen DESC LIMIT 100', gmdate('Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS)));

	ob_start();
	if (!$rows) {
		echo '<tr><td colspan="9" class="wvi-empty">Nobody is on the site right now.</td></tr>';
	}
	retails_vi_load_people($rows);
	$GLOBALS['retails_vi_chat_map'] = retails_vi_chat_visitor_map($rows);
	foreach ($rows as $row) {
		retails_vi_visit_row($row, true);
	}
	unset($GLOBALS['retails_vi_chat_map']);

	return array('count' => count($rows), 'html' => ob_get_clean());
}

/**
 * Live Chat visitor ids for these visits (the chat plugin stores the visitor's
 * sso_vid cookie), so the Live tab can message someone through the chat.
 */
function retails_vi_chat_visitor_map($rows)
{
	global $wpdb;
	if (!$rows || !class_exists('\SFS\LiveChat\Repo\Visitors')) {
		return array();
	}
	$keys = array_values(array_unique(array_map(static function ($row) {
		return (string) $row->visitor_key;
	}, $rows)));
	$table = \SFS\LiveChat\Repo\Visitors::table();
	if (!$wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', 'vi_key'))) {
		return array();
	}
	$in = implode(', ', array_fill(0, count($keys), '%s'));
	$map = array();
	foreach ($wpdb->get_results($wpdb->prepare("SELECT id, vi_key FROM {$table} WHERE blocked = 0 AND vi_key IN ({$in}) ORDER BY last_seen ASC", $keys)) as $match) {
		$map[$match->vi_key] = (int) $match->id;
	}

	return $map;
}

/**
 * Live tab "Message" button: a first message to someone on the site, sent
 * through Live Chat (it pops up in their chat widget and opens a conversation).
 */
function retails_vi_ajax_chat_message()
{
	if (!current_user_can('manage_options') || !check_ajax_referer('retails_vi_live', 'nonce', false)) {
		wp_send_json_error('Not allowed.', 403);
	}
	if (!class_exists('\SFS\LiveChat\Rest\AdminController')) {
		wp_send_json_error('Live Chat is not active.', 400);
	}
	$request = new WP_REST_Request('POST', '/');
	$request->set_param('id', isset($_POST['visitor']) ? absint($_POST['visitor']) : 0);
	$request->set_param('body', isset($_POST['body']) ? wp_unslash($_POST['body']) : '');
	$result = (new \SFS\LiveChat\Rest\AdminController())->proactive($request);
	if (is_wp_error($result)) {
		wp_send_json_error($result->get_error_message(), 400);
	}
	wp_send_json_success();
}
add_action('wp_ajax_retails_vi_chat_message', 'retails_vi_ajax_chat_message');

/**
 * Live Chat's "Live visitors" tab lists only people whose chat is connected
 * (the chat bundle loads when they open it, visitor tracking is off for
 * speed). Hand it the rest of our "online now" visits so both screens count
 * the same people. Visits already listed there (matched by chat visitor id)
 * are skipped. No IPs, names or emails leave here.
 */
function retails_vi_chat_live_browsing($browsing, $shown)
{
	global $wpdb;
	$rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . retails_vi_table('sessions') . ' s WHERE last_seen >= %s AND NOT ' . retails_vi_automated_sql('s') . ' ORDER BY last_seen DESC LIMIT 100', gmdate('Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS)));
	if (!$rows) {
		return $browsing;
	}
	$chat_map = retails_vi_chat_visitor_map($rows);
	$shown = array_flip(array_map('intval', (array) $shown));
	$channels = retails_vi_channels();
	$details = current_user_can('manage_options');
	$seen = array();
	foreach ($rows as $row) {
		$chat_id = (int) ($chat_map[$row->visitor_key] ?? 0);
		// One row per person (newest visit), and not the people chat already lists.
		if (isset($seen[$row->visitor_key]) || ($chat_id && isset($shown[$chat_id]))) {
			continue;
		}
		$seen[$row->visitor_key] = true;
		$path = (string) ($row->exit_url ?: $row->landing_url);
		$page = $path;
		if ('' !== $path && '/' === $path[0]) {
			// Stored paths already include any subfolder, so add only scheme + host.
			$page = wp_parse_url(home_url('/'), PHP_URL_SCHEME) . '://' . wp_parse_url(home_url('/'), PHP_URL_HOST) . $path;
		}
		$browsing[] = array(
			'key' => substr(md5((string) $row->visitor_key), 0, 12),
			'page' => $page,
			'title' => retails_vi_short_path($path, 60),
			'since' => strtotime($row->started_at . ' UTC') * 1000,
			'last_seen' => strtotime($row->last_seen . ' UTC') * 1000,
			'device' => (string) $row->device,
			'browser' => (string) $row->browser,
			'location' => retails_vi_location($row),
			'source' => (string) ($channels[$row->channel] ?? ucfirst((string) $row->channel)),
			'pages' => (int) $row->pageviews,
			'chat_visitor_id' => $chat_id ?: null,
			'session_id' => (int) $row->id,
			'details_url' => $details ? retails_vi_url('retails-insights-visitors', array('session' => $row->id)) : '',
		);
	}

	return $browsing;
}
add_filter('sfs_chat_live_browsing', 'retails_vi_chat_live_browsing', 10, 2);

/**
 * Live Chat "Start chat" on someone browsing without the chat open: the
 * visitor key and current page for a visit seen in the last 30 minutes.
 */
function retails_vi_chat_browsing_target($target, $session_id)
{
	global $wpdb;
	$row = $wpdb->get_row($wpdb->prepare('SELECT visitor_key, exit_url, landing_url FROM ' . retails_vi_table('sessions') . ' WHERE id = %d AND last_seen >= %s', absint($session_id), gmdate('Y-m-d H:i:s', time() - 30 * MINUTE_IN_SECONDS)));
	if (!$row || !preg_match('/^[a-f0-9]{32}$/', (string) $row->visitor_key)) {
		return $target;
	}
	$path = (string) ($row->exit_url ?: $row->landing_url);

	return array(
		'key' => (string) $row->visitor_key,
		'page' => ('' !== $path && '/' === $path[0]) ? wp_parse_url(home_url('/'), PHP_URL_SCHEME) . '://' . wp_parse_url(home_url('/'), PHP_URL_HOST) . $path : $path,
	);
}
add_filter('sfs_chat_browsing_target', 'retails_vi_chat_browsing_target', 10, 2);

/**
 * Live Chat inbox: what a chat visitor is doing right now and did before, for
 * the visitor details pane (polled every few seconds while it is open).
 * Matched by the chat visitor's sso_vid cookie (vi_key), else their account.
 * Returns plain data; the inbox prints every value as text.
 */
function retails_vi_chat_visitor_insights($insights, $vi_key, $chat_visitor)
{
	global $wpdb;
	$sessions = retails_vi_table('sessions');
	$vi_key = strtolower((string) $vi_key);
	$user_id = (int) ($chat_visitor->user_id ?? 0);
	if (preg_match('/^[a-f0-9]{32}$/', $vi_key)) {
		$match = $wpdb->prepare('visitor_key = %s', $vi_key);
	} elseif ($user_id > 0) {
		$match = $wpdb->prepare('user_id = %d', $user_id);
	} else {
		return $insights;
	}
	$visits = $wpdb->get_results("SELECT * FROM {$sessions} WHERE {$match} ORDER BY last_seen DESC LIMIT 6"); // phpcs:ignore WordPress.DB.PreparedSQL -- prepared above.
	if (!$visits) {
		return $insights;
	}
	$totals = $wpdb->get_row("SELECT COUNT(*) AS visits, MIN(started_at) AS first_seen, SUM(pageviews) AS pages, SUM(engaged_seconds) AS engaged, SUM(order_id > 0) AS orders, SUM(revenue) AS revenue, SUM(is_lead) AS leads, MAX(stage) AS best_stage FROM {$sessions} WHERE {$match}"); // phpcs:ignore WordPress.DB.PreparedSQL -- prepared above.

	$now = time();
	$ms = static function ($gmt) {
		return $gmt ? strtotime($gmt . ' UTC') * 1000 : null;
	};
	$link = static function ($path) {
		$path = (string) $path;
		return ('' !== $path && '/' === $path[0]) ? retails_vi_site_link($path) : $path;
	};
	$channels = retails_vi_channels();
	$stages = retails_vi_stages();
	$details = current_user_can('manage_options');
	retails_vi_load_people($visits);
	$lead_types = retails_vi_lead_types_cache();

	// The same badge the Visitors report shows, worded for someone who may still be on the site.
	$outcome = static function ($visit, $online) use ($lead_types) {
		if ((int) $visit->order_id > 0) {
			return array('Purchased ' . retails_vi_money($visit->revenue), 'won');
		}
		if ((int) $visit->is_lead) {
			$types = $lead_types[(int) $visit->id] ?? array();
			return array(in_array('quote_submit', $types, true) ? 'Quote request' : (in_array('call_click', $types, true) ? 'Lead · call tap' : (in_array('email_click', $types, true) ? 'Lead · email' : 'Lead')), 'good');
		}
		if ((int) $visit->stage >= 4) {
			$checkout = (int) $visit->stage >= 5;
			return $online ? array($checkout ? 'At checkout' : 'Has items in cart', 'warn') : array('Abandoned ' . ($checkout ? 'checkout' : 'cart'), 'bad');
		}
		if ((int) $visit->pageviews <= 1 && (int) $visit->engaged_seconds < 10) {
			return $online ? array('Just arrived', '') : array('Bounced', 'bad');
		}

		return array($online ? 'Browsing' : 'Browsed', '');
	};
	$source = static function ($visit) use ($channels) {
		$network = retails_vi_ad_network($visit);
		return array(
			'channel' => (string) $visit->channel,
			'label' => (string) ($channels[$visit->channel] ?? ucfirst((string) $visit->channel)),
			'detail' => implode(' / ', array_filter(array((string) $visit->source, (string) $visit->medium))),
			'campaign' => $visit->campaign ? (string) retails_vi_campaign_name($visit->campaign) : '',
			'keyword' => (string) $visit->term,
			'network' => $network ? (string) (retails_vi_ad_networks()[$network] ?? $network) : '',
			'referrer' => (string) $visit->referrer,
		);
	};

	$visit = $visits[0];
	$online = strtotime($visit->last_seen . ' UTC') >= $now - 5 * MINUTE_IN_SECONDS;
	$events = array_reverse($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . retails_vi_table('events') . ' WHERE session_id = %d ORDER BY id DESC LIMIT 40', $visit->id)));
	$timeline = array();
	$page_since = null;
	foreach ($events as $event) {
		list(, $text, $tone) = retails_vi_event_text($event);
		$item = array(
			'type' => (string) $event->type,
			'at' => $ms($event->created_at),
			'text' => (string) $text,
			'tone' => (string) $tone,
		);
		if ('pageview' === $event->type) {
			$item['url'] = $link($event->url);
			$item['path'] = retails_vi_short_path($event->url, 60);
			$item['seconds'] = (int) $event->seconds;
			$item['scroll'] = (int) $event->scroll;
			$page_since = $item['at'];
		}
		$timeline[] = $item;
	}
	$person = retails_vi_person($visit);
	list($outcome_label, $outcome_tone) = $outcome($visit, $online);

	$history = array();
	foreach (array_slice($visits, 1, 5) as $other) {
		list($label, $tone) = $outcome($other, false);
		$history[] = array(
			'started' => $ms($other->started_at),
			'channel' => (string) ($channels[$other->channel] ?? ucfirst((string) $other->channel)),
			'pages' => (int) $other->pageviews,
			'engaged' => (int) $other->engaged_seconds,
			'outcome' => $label,
			'tone' => $tone,
			'details_url' => $details ? retails_vi_url('retails-insights-visitors', array('session' => $other->id)) : '',
		);
	}

	return array(
		'online' => $online,
		'visit' => array(
			'started' => $ms($visit->started_at),
			'last_seen' => $ms($visit->last_seen),
			'page' => $link($visit->exit_url ?: $visit->landing_url),
			'page_path' => retails_vi_short_path($visit->exit_url ?: $visit->landing_url, 60),
			'page_since' => $page_since,
			'landing' => $link($visit->landing_url),
			'landing_path' => retails_vi_short_path($visit->landing_url, 60),
			'pages' => (int) $visit->pageviews,
			'engaged' => (int) $visit->engaged_seconds,
			'scroll' => (int) $visit->max_scroll,
			'stage' => (int) $visit->stage,
			'stage_label' => (string) ($stages[(int) $visit->stage] ?? ''),
			'stages' => count($stages),
			'outcome' => $outcome_label,
			'tone' => $outcome_tone,
			'returning' => !(int) $visit->is_new,
			'errors' => (int) $visit->errors,
			'rage_clicks' => (int) $visit->rage_clicks,
			'source' => $source($visit),
			'device' => implode(' · ', array_filter(array(ucfirst((string) $visit->device), (string) $visit->os, (string) $visit->browser, (string) $visit->screen))),
			'location' => (string) retails_vi_location($visit),
			'details_url' => $details ? retails_vi_url('retails-insights-visitors', array('session' => $visit->id)) : '',
		),
		'person' => $person ? array('name' => (string) $person['name'], 'email' => (string) $person['email'], 'note' => (string) $person['note']) : null,
		'timeline' => array_reverse($timeline),
		'totals' => array(
			'visits' => (int) ($totals->visits ?? 1),
			'first_seen' => $ms($totals->first_seen ?? ''),
			'pages' => (int) ($totals->pages ?? 0),
			'engaged' => (int) ($totals->engaged ?? 0),
			'orders' => (int) ($totals->orders ?? 0),
			'revenue' => round((float) ($totals->revenue ?? 0), 2),
			'leads' => (int) ($totals->leads ?? 0),
		),
		'history' => $history,
	);
}
add_filter('sfs_chat_visitor_insights', 'retails_vi_chat_visitor_insights', 10, 3);

function retails_vi_render_live()
{
	$live = retails_vi_live_rows_html();
	?>
	<section class="wvi-card">
		<div class="wvi-section-head wvi-pad">
			<h2><span class="wvi-live-dot"></span> <span data-live-count><?php echo esc_html($live['count']); ?></span> visitor(s) online now</h2>
			<span class="wvi-muted">Active in the last 5 minutes · updates live · the page column shows where they are now.</span>
		</div>
		<div class="wvi-table-wrap">
			<table class="wvi-table wvi-visits">
				<thead><tr><th>Arrived</th><th>Source</th><th>Current page</th><th>Device · location</th><th class="num">Pages</th><th class="num">Time</th><th>Got to</th><th>Outcome</th><th>Chat · screenshot</th></tr></thead>
				<tbody data-live-rows><?php echo $live['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping. ?></tbody>
			</table>
		</div>
	</section>
	<?php
}


/* Settings -------------------------------------------------------------- */

function retails_vi_render_settings()
{
	global $wpdb;
	$settings = retails_vi_settings();
	$sessions = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . retails_vi_table('sessions'));
	$events = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . retails_vi_table('events'));
	$last = $wpdb->get_var('SELECT MAX(last_seen) FROM ' . retails_vi_table('sessions'));
	$suffix = 'utm_source=google&utm_medium=cpc&utm_campaign={campaignid}&utm_content={adgroupid}&utm_term={keyword}';
	?>
	<div class="wvi-grid">
		<section class="wvi-card wvi-section">
			<div class="wvi-section-head"><h2>Tracking</h2></div>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wvi-form">
				<input type="hidden" name="action" value="retails_vi_save">
				<?php wp_nonce_field('retails_vi_save'); ?>
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
					<span class="wvi-muted">Your current IP: <code><?php echo esc_html(retails_vi_client_ip(false)); ?></code></span>
				</p>
				<p id="wvi-campaigns">
					<label for="wvi-campaigns-names"><strong>Google Ads campaign names</strong></label><br>
					<textarea id="wvi-campaigns-names" name="campaign_names" rows="5" class="large-text code" placeholder="24449162107 = SSO - PMax - Banners & Displays - USA"><?php echo esc_textarea($settings['campaign_names']); ?></textarea>
					<span class="wvi-muted">One per line: campaign ID = name. Only needed for campaigns made after Nov 9, 2026 or to rename one; the campaigns below are already named. The ID is in Google Ads under Campaigns (add the "Campaign ID" column).</span>
				</p>
				<?php retails_vi_render_campaign_list(); ?>
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
				<p><strong>Optional, more exact:</strong> this version also sends where the ad was shown (<code>{network}</code>: Search, Search partners, Display, YouTube) and the match type. Without it the site works this out from the page the click came from, which is right for most clicks but not for app clicks.</p>
				<div class="wvi-code">
					<code><?php echo esc_html($suffix . '&utm_network={network}&utm_matchtype={matchtype}'); ?></code>
					<button type="button" class="button wvi-copy" data-copy="<?php echo esc_attr($suffix . '&utm_network={network}&utm_matchtype={matchtype}'); ?>">Copy</button>
				</div>
			</section>
			<section class="wvi-card wvi-section">
				<div class="wvi-section-head"><h2>Stored data</h2></div>
				<p><?php echo esc_html(sprintf('%s visits and %s activity records.', number_format_i18n($sessions), number_format_i18n($events))); ?> <?php echo $last ? esc_html('Last activity ' . retails_vi_ago($last) . '.') : 'No visits recorded yet.'; ?></p>
				<p class="wvi-muted">Only page addresses, clicks on buttons and links, and which form field a visitor was on are recorded — never what they type. Mention first-party analytics cookies (sso_vid, sso_sid) in your privacy policy.</p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-confirm="Delete all recorded visits and activity? This cannot be undone.">
					<input type="hidden" name="action" value="retails_vi_purge_all">
					<?php wp_nonce_field('retails_vi_purge_all'); ?>
					<button class="button wvi-danger">Delete all visitor data</button>
				</form>
			</section>
		</div>
	</div>
	<?php
}

/**
 * Settings: every campaign seen in ad clicks over the last 90 days, with the
 * name the reports use, so a new campaign without a name stands out.
 */
function retails_vi_render_campaign_list()
{
	global $wpdb;
	$rows = $wpdb->get_results($wpdb->prepare(
		'SELECT campaign, COUNT(*) AS clicks, MAX(started_at) AS last_click FROM ' . retails_vi_table('sessions') . " WHERE channel IN ('google_ads', 'paid_other') AND campaign <> '' AND started_at >= %s GROUP BY campaign ORDER BY last_click DESC LIMIT 40",
		gmdate('Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS)
	));
	$seen = array();
	foreach ((array) $rows as $row) {
		$seen[$row->campaign] = $row;
	}
	$all = array_unique(array_merge(array_keys($seen), array_filter(array_keys(retails_vi_campaign_names()), static function ($id) {
		return (bool) preg_match('/^\d+$/', (string) $id);
	})));
	?>
	<div class="wvi-table-wrap">
		<table class="wvi-table">
			<thead><tr><th>Campaign ID</th><th>Shown as</th><th class="num">Ad clicks (90 days)</th><th>Last click</th></tr></thead>
			<tbody>
				<?php foreach ($all as $id) : ?>
					<?php $row = $seen[$id] ?? null; ?>
					<tr>
						<td><code><?php echo esc_html($id); ?></code></td>
						<td><?php echo retails_vi_campaign_is_named($id) ? retails_vi_campaign_html($id, false) : '<span class="wvi-badge is-amber">No name yet</span> <span class="wvi-muted">add a line above</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?></td>
						<td class="num"><?php echo esc_html($row ? number_format_i18n($row->clicks) : '0'); ?></td>
						<td><?php echo esc_html($row ? retails_vi_ago($row->last_click) : '—'); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function retails_vi_save_settings()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.', 403);
	}
	check_admin_referer('retails_vi_save');

	$ips = preg_split('/[\s,]+/', sanitize_textarea_field(wp_unslash($_POST['excluded_ips'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
	$ips = array_filter($ips, static function ($ip) {
		return (bool) filter_var($ip, FILTER_VALIDATE_IP);
	});

	update_option('retails_vi_settings', array(
		'enabled' => empty($_POST['enabled']) ? 0 : 1,
		'exclude_admins' => empty($_POST['exclude_admins']) ? 0 : 1,
		'mask_ip' => empty($_POST['mask_ip']) ? 0 : 1,
		'retention_days' => min(365, max(30, absint($_POST['retention_days'] ?? 120))),
		'excluded_ips' => implode("\n", array_unique($ips)),
		'campaign_names' => sanitize_textarea_field(wp_unslash($_POST['campaign_names'] ?? '')),
	), false);

	wp_safe_redirect(retails_vi_url('retails-insights-settings', array('vi_notice' => 'saved')));
	exit;
}
add_action('admin_post_retails_vi_save', 'retails_vi_save_settings');

function retails_vi_purge_all()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.', 403);
	}
	check_admin_referer('retails_vi_purge_all');

	global $wpdb;
	$wpdb->query('TRUNCATE TABLE ' . retails_vi_table('events'));
	$wpdb->query('TRUNCATE TABLE ' . retails_vi_table('sessions'));

	wp_safe_redirect(retails_vi_url('retails-insights-settings', array('vi_notice' => 'purged')));
	exit;
}
add_action('admin_post_retails_vi_purge_all', 'retails_vi_purge_all');

/* Export ---------------------------------------------------------------- */

/**
 * Theme files behind each kind of page, for the exported report, so whoever
 * acts on it (a developer or Claude) knows where to make changes. Only files
 * that exist are listed.
 */
function retails_vi_page_type_files()
{
	$map = array(
		'every page' => array('header.php', 'footer.php', 'css/ui-polish.css', 'js/ui-polish.js', 'inc/page-loader.php', 'inc/buying-journey.php'),
		'home' => array('home.php', 'template-parts/home-catalog.php', 'inc/home-catalog-directory.php', 'css/style.scss'),
		'landing' => array('landing-page.php', 'page-channel-letters.php', 'page-window-graphics.php', 'page-banners-displays.php', 'page-storefront-signs.php', 'inc/hero-images.php', 'inc/quote-form.php', 'css/landing-page.css', 'css/cl-quote.css'),
		'product' => array('single-product.php', 'inc/product-hero.php', 'inc/product-buy-box.php', 'inc/size-chips.php', 'inc/product-page-content.php', 'inc/product-group-swap.php', 'inc/product-kit.php', 'js/main.js', 'js/product-group-swap.js', 'inc/pricing.php', 'css/shop.css'),
		'builder' => array('cl_builderr.php', 'b2calc.php', 'js/cl.js', 'css/cl.css'),
		'category' => array('home.php', 'inc/category-page-content.php'),
		'shop' => array('page-shop.php', 'inc/shop-catalog.php'),
		'cart' => array('cart.php', 'utils/Cart.php', 'inc/mini-cart.php', 'js/mini-cart.js'),
		'checkout' => array('checkout.php', 'js/checkout.js', 'inc/payments.php'),
		'thankyou' => array('thank-you.php', 'inc/ads-tracking.php'),
		'contact' => array('page-contact.php', 'inc/quote-form.php'),
		'exit survey' => array('inc/exit-survey.php', 'js/exit-survey.js', 'css/exit-survey.css'),
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

function retails_vi_error_files($type)
{
	$map = array(
		'checkout_error' => 'inc/payments.php, js/checkout.js',
		'payment_error' => 'inc/payments.php, js/checkout.js',
		'price_error' => 'inc/pricing.php, js/main.js',
		'cart_error' => 'utils/Cart.php, inc/mini-cart.php',
		'upload_error' => 'functions.php (retails_upload_design)',
		'rage_click' => 'the page where it happened',
	);

	return $map[$type] ?? '';
}

/**
 * The theme template that renders a site path, e.g. "single-product.php".
 */
function retails_vi_path_template($path)
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

function retails_vi_md_cell($value)
{
	return str_replace(array('|', "\n", "\r"), array('\|', ' ', ''), (string) $value);
}

/**
 * A Markdown table from rows of cells.
 */
function retails_vi_md_table($headers, $rows)
{
	if (!$rows) {
		return "_No data in this range._\n";
	}

	$out = '| ' . implode(' | ', $headers) . " |\n|" . str_repeat(' --- |', count($headers)) . "\n";
	foreach ($rows as $row) {
		$out .= '| ' . implode(' | ', array_map('retails_vi_md_cell', $row)) . " |\n";
	}

	return $out;
}

/**
 * Standard metric cells for a breakdown row.
 */
function retails_vi_md_metrics($row)
{
	return array(
		number_format_i18n($row['sessions']),
		retails_vi_pct($row['bounced'], $row['sessions']),
		retails_vi_duration($row['avg_engaged']),
		number_format_i18n($row['s2']),
		number_format_i18n($row['s4']),
		number_format_i18n($row['leads']),
		number_format_i18n($row['orders']),
		retails_vi_pct($row['orders'] + $row['leads'], $row['sessions'], 1),
	);
}

function retails_vi_md_breakdown($rows, $first_header, $label_cb = null)
{
	$out = array();
	foreach ($rows as $row) {
		$out[] = array_merge(array($label_cb ? $label_cb($row) : ($row['label'] ?: '(none)')), retails_vi_md_metrics($row));
	}

	return retails_vi_md_table(array($first_header, 'Visits', 'Bounce', 'Avg. engaged', 'Viewed product', 'Cart', 'Leads', 'Orders', 'Conv.'), $out);
}

/**
 * Recent visits as short step lists: by default visits that showed interest
 * but did not buy; with $bought, visits that ended in an order (to compare
 * what worked). No names, emails or IP addresses.
 */
function retails_vi_md_journeys($period, $filters, $limit = 15, $bought = false)
{
	global $wpdb;
	list($where, $params) = retails_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$params[] = (int) $limit;
	$sessions = $wpdb->get_results($wpdb->prepare(
		'SELECT * FROM ' . retails_vi_table('sessions') . " s WHERE {$where} AND " . ($bought
			? 's.order_id > 0 ORDER BY s.started_at DESC'
			: 's.order_id = 0 AND (s.stage >= 2 OR s.pageviews >= 2 OR s.errors > 0) ORDER BY s.stage DESC, s.errors DESC, s.started_at DESC') . ' LIMIT %d',
		$params
	));
	if (!$sessions) {
		return $bought ? "\n_No tracked orders in this range._\n" : "\n_No visits that showed interest without buying in this range._\n";
	}

	$stages = retails_vi_stages();
	$channels = retails_vi_channels();
	$out = '';
	foreach ($sessions as $i => $session) {
		$events = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . retails_vi_table('events') . ' WHERE session_id = %d ORDER BY created_at, id LIMIT 80', $session->id));
		$start = strtotime($session->started_at . ' UTC');
		$out .= sprintf(
			"\n**%s %d** — %s · %s, %s %s, %s · %d pages · %s engaged · got to: %s%s%s\n\n",
			$bought ? 'Order visit' : 'Visit',
			$i + 1,
			wp_date('D M j, g:i a', $start),
			$channels[$session->channel] ?? $session->channel,
			ucfirst($session->device),
			$session->browser,
			$session->timezone ?: 'unknown location',
			$session->pageviews,
			retails_vi_duration($session->engaged_seconds),
			$stages[(int) $session->stage] ?? '',
			($session->is_new ? '' : ' · returning visitor') . ('' !== (string) $session->campaign ? ' · campaign: ' . retails_vi_campaign_name($session->campaign) : ''),
			$session->term ? ' · keyword: "' . $session->term . '"' : ''
		);
		$last = '';
		foreach ($events as $event) {
			list(, $text) = retails_vi_event_text($event);
			if ('pageview' === $event->type) {
				$text = sprintf('Viewed %s (%s, %s on page, scrolled %d%%)', retails_vi_short_path($event->url, 90), $event->label ?: 'untitled', retails_vi_duration($event->seconds), $event->scroll);
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
		$out .= sprintf("- Left from %s\n", retails_vi_short_path($session->exit_url, 90));
	}

	return $out;
}

/**
 * Visitor-typed text (survey answers, chat messages) for the export: emails
 * and phone numbers removed, one line, shortened.
 */
function retails_vi_md_scrub($text, $max = 220)
{
	$text = wp_strip_all_tags(html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8'));
	$text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email]', $text);
	$text = preg_replace_callback('/\+?\(?\d[\d\s().\-]{6,}\d/', static function ($match) {
		return strlen(preg_replace('/\D/', '', $match[0])) >= 9 ? '[phone]' : $match[0];
	}, $text);
	$text = trim(preg_replace('/\s+/u', ' ', $text));
	if (mb_strlen($text) > $max) {
		$text = rtrim(mb_substr($text, 0, $max - 1)) . '…';
	}

	return retails_vi_md_cell($text);
}

function retails_vi_md_dollars($amount)
{
	return '$' . number_format_i18n(round((float) $amount));
}

/**
 * Page title without the " | Storefront Sign Online" suffix.
 */
function retails_vi_md_title($title, $length = 48)
{
	$title = trim(preg_replace('/\s*[|–-]\s*Storefront Sign Online.*$/iu', '', html_entity_decode((string) $title, ENT_QUOTES, 'UTF-8')));

	return mb_strlen($title) > $length ? rtrim(mb_substr($title, 0, $length - 1)) . '…' : $title;
}

/**
 * The products in an order, e.g. "13oz Vinyl Banner ×2, Magnets".
 *
 * @return array[] Each: product_id, title, qty, subtotal.
 */
function retails_vi_order_items($order_id)
{
	$raw = get_post_meta($order_id, 'product_json', true);
	$items = is_array($raw) ? $raw : json_decode((string) $raw, true);
	if (!is_array($items) && function_exists('retails_decode_order_meta_array')) {
		$items = retails_decode_order_meta_array($raw);
	}

	$out = array();
	foreach (is_array($items) ? $items : array() as $item) {
		if (!is_array($item) || empty($item['product_title'])) {
			continue;
		}
		$qty = max(1, (int) ($item['product_quantity'] ?? 1));
		$id = (int) ($item['product_id'] ?? 0);
		$title = trim(html_entity_decode(wp_strip_all_tags((string) $item['product_title']), ENT_QUOTES, 'UTF-8'));
		$subtotal = isset($item['product_subtotal']) ? (float) $item['product_subtotal'] : (float) ($item['unit_price'] ?? 0) * $qty;
		// The same product in several sizes is one line here.
		$key = $id . '|' . $title;
		if (isset($out[$key])) {
			$out[$key]['qty'] += $qty;
			$out[$key]['subtotal'] += $subtotal;
			continue;
		}
		$out[$key] = array('product_id' => $id, 'title' => $title, 'qty' => $qty, 'subtotal' => $subtotal);
	}

	return array_values($out);
}

function retails_vi_order_items_text($order_id, $max = 3)
{
	$names = array();
	foreach (retails_vi_order_items($order_id) as $item) {
		$names[] = $item['title'] . ($item['qty'] > 1 ? ' ×' . $item['qty'] : '');
	}

	return $names ? implode(', ', array_slice($names, 0, $max)) . (count($names) > $max ? ' +' . (count($names) - $max) . ' more' : '') : '—';
}

/**
 * Each product page (and the sign builder): how many visits viewed it, got a
 * price, saw a pricing error, added it to the cart and bought, the prices
 * they were shown, and the orders that included it.
 */
function retails_vi_product_funnel($period, $filters, $orders)
{
	global $wpdb;
	list($where, $params) = retails_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$num = "e.value REGEXP '^[0-9]+([.][0-9]+)?$'";
	// "priced" counts add_to_cart too: a visit that put the product in the cart saw its
	// price even with no option changed (fixed-price graphic-only stands read 0 priced,
	// 1 in cart, Nov 2026).
	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT SUBSTRING_INDEX(e.url, '?', 1) AS path,
			MAX(CASE WHEN e.type = 'pageview' THEN e.label END) AS title,
			COUNT(DISTINCT CASE WHEN e.type = 'pageview' THEN e.session_id END) AS views,
			COUNT(DISTINCT CASE WHEN e.type = 'pageview' AND s.channel = 'google_ads' THEN e.session_id END) AS ads,
			COUNT(DISTINCT CASE WHEN e.type = 'pageview' AND s.device = 'mobile' THEN e.session_id END) AS mobile,
			SUM(CASE WHEN e.type = 'pageview' THEN e.seconds ELSE 0 END) AS seconds,
			SUM(CASE WHEN e.type = 'pageview' THEN e.scroll ELSE 0 END) AS scroll,
			SUM(e.type = 'pageview') AS pageviews,
			COUNT(DISTINCT CASE WHEN e.type IN ('configure', 'price_quote', 'add_to_cart') THEN e.session_id END) AS priced,
			COUNT(DISTINCT CASE WHEN e.type = 'price_error' THEN e.session_id END) AS price_errors,
			COUNT(DISTINCT CASE WHEN e.type = 'add_to_cart' THEN e.session_id END) AS carted,
			COUNT(DISTINCT CASE WHEN e.type = 'add_to_cart' AND s.order_id > 0 THEN e.session_id END) AS bought,
			SUM(CASE WHEN e.type = 'price_quote' AND {$num} THEN e.value + 0 ELSE 0 END) AS price_sum,
			SUM(e.type = 'price_quote' AND {$num}) AS price_n,
			MIN(CASE WHEN e.type = 'price_quote' AND {$num} AND e.value + 0 > 0 THEN e.value + 0 END) AS price_min,
			MAX(CASE WHEN e.type = 'price_quote' AND {$num} THEN e.value + 0 END) AS price_max,
			SUM(CASE WHEN e.type = 'add_to_cart' AND {$num} THEN e.value + 0 ELSE 0 END) AS cart_sum,
			SUM(e.type = 'add_to_cart' AND {$num}) AS cart_n
		FROM " . retails_vi_table('events') . ' e JOIN ' . retails_vi_table('sessions') . " s ON s.id = e.session_id
		WHERE e.type IN ('pageview', 'configure', 'price_quote', 'price_error', 'add_to_cart') AND NOT (e.type = 'pageview' AND e.value = '404')
			AND e.created_at >= %s AND e.created_at < %s AND (e.url LIKE %s OR e.url LIKE %s) AND {$where}
		GROUP BY path",
		array_merge(array($period['start_gmt'], $period['end_gmt'], '%/product/%', '%-builder%'), $params)
	), ARRAY_A);

	// The same page can be stored with and without the site's sub-folder.
	$sum = array('views', 'ads', 'mobile', 'seconds', 'scroll', 'pageviews', 'priced', 'price_errors', 'carted', 'bought', 'price_sum', 'price_n', 'cart_sum', 'cart_n');
	$products = array();
	foreach ((array) $rows as $row) {
		$key = retails_vi_short_path($row['path'], 300);
		if (!isset($products[$key])) {
			$products[$key] = array_merge(array_fill_keys($sum, 0), array('path' => $key, 'title' => '', 'price_min' => null, 'price_max' => null, 'orders' => 0, 'units' => 0, 'sales' => 0.0));
		}
		$product = &$products[$key];
		foreach ($sum as $col) {
			$product[$col] += (float) $row[$col];
		}
		$product['title'] = $product['title'] ?: (string) $row['title'];
		if (null !== $row['price_min']) {
			$product['price_min'] = null === $product['price_min'] ? (float) $row['price_min'] : min($product['price_min'], (float) $row['price_min']);
		}
		if (null !== $row['price_max']) {
			$product['price_max'] = null === $product['price_max'] ? (float) $row['price_max'] : max($product['price_max'], (float) $row['price_max']);
		}
		unset($product);
	}

	// Orders placed in the range, by product, from the order itself.
	foreach ($orders as $order) {
		foreach (retails_vi_order_items($order['id']) as $item) {
			$link = $item['product_id'] ? get_permalink($item['product_id']) : '';
			$key = $link ? retails_vi_short_path((string) wp_parse_url($link, PHP_URL_PATH), 300) : 'item:' . $item['title'];
			if (!isset($products[$key])) {
				// Custom items without a product page keep an empty path.
				$products[$key] = array_merge(array_fill_keys($sum, 0), array('path' => $link ? $key : '', 'title' => $item['title'], 'price_min' => null, 'price_max' => null, 'orders' => 0, 'units' => 0, 'sales' => 0.0));
			}
			if (empty($counted[$key][$order['id']])) {
				$counted[$key][$order['id']] = true;
				$products[$key]['orders']++;
			}
			$products[$key]['units'] += $item['qty'];
			$products[$key]['sales'] += $item['subtotal'];
		}
	}

	uasort($products, static function ($a, $b) {
		return array($b['views'], $b['orders']) <=> array($a['views'], $a['orders']);
	});

	return $products;
}

function retails_vi_md_products($products, $limit = 25)
{
	$top = array_slice($products, 0, $limit, true);
	// Products that sold but were not among the most viewed still belong here.
	foreach ($products as $key => $product) {
		if ($product['orders'] && !isset($top[$key])) {
			$top[$key] = $product;
		}
	}

	$rows = array();
	foreach ($top as $product) {
		$views = (int) $product['views'];
		$rows[] = array(
			'' === $product['path'] ? retails_vi_md_title($product['title'], 56) . ' (custom item, no product page)' : ($product['title'] ? retails_vi_md_title($product['title'], 56) . ' — ' : '') . '`' . retails_vi_short_path($product['path'], 70) . '`',
			number_format_i18n($views),
			$views ? retails_vi_pct($product['ads'], $views) : '—',
			$views ? retails_vi_pct($product['mobile'], $views) : '—',
			$product['pageviews'] ? retails_vi_duration($product['seconds'] / $product['pageviews']) : '—',
			$product['pageviews'] ? round($product['scroll'] / $product['pageviews']) . '%' : '—',
			number_format_i18n($product['priced']),
			$product['price_errors'] ? number_format_i18n($product['price_errors']) : '0',
			number_format_i18n($product['carted']),
			number_format_i18n($product['bought']),
			$product['orders'] ? $product['orders'] . ' (' . retails_vi_md_dollars($product['sales']) . ')' : '0',
			$product['price_n'] ? retails_vi_md_dollars($product['price_sum'] / $product['price_n']) . ' avg (' . retails_vi_md_dollars($product['price_min']) . '–' . retails_vi_md_dollars($product['price_max']) . ')' : '—',
			$product['cart_n'] ? retails_vi_md_dollars($product['cart_sum'] / $product['cart_n']) : '—',
		);
	}

	return retails_vi_md_table(array('Product — page', 'Visits that viewed it', 'From ads', 'On phones', 'Avg. time on page', 'Avg. scroll', 'Got a price', 'Saw a pricing error', 'Added to cart', 'Bought in that visit', 'Orders in range (sales)', 'Prices shown', 'Avg. cart value'), $rows);
}

/**
 * Landing pages from the first-screen point of view: how far the visitors
 * who left at once scrolled (0% = they never looked past the first screen)
 * and how long the others stayed.
 */
function retails_vi_md_landing_check($period, $filters, $limit = 10)
{
	global $wpdb;
	list($where, $params) = retails_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$bounce = retails_vi_bounce_sql('s');
	$params[] = (int) $limit;
	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT SUBSTRING_INDEX(s.landing_url, '?', 1) AS label, COUNT(*) AS sessions, SUM(s.device = 'mobile') AS mobile, SUM({$bounce}) AS bounced,
			AVG(CASE WHEN {$bounce} THEN s.max_scroll END) AS bounce_scroll, AVG(CASE WHEN NOT {$bounce} THEN s.engaged_seconds END) AS stayed_seconds,
			AVG(CASE WHEN NOT {$bounce} THEN s.pageviews END) AS stayed_pages, SUM(s.stage >= 3) AS priced, SUM(s.is_lead) AS leads, SUM(s.order_id > 0) AS orders
		FROM " . retails_vi_table('sessions') . " s WHERE {$where} GROUP BY label ORDER BY sessions DESC LIMIT %d",
		$params
	), ARRAY_A);

	$out = array();
	foreach ((array) $rows as $row) {
		$template = retails_vi_path_template($row['label']);
		$out[] = array(
			retails_vi_short_path($row['label'], 70) . ($template ? ' → `' . $template . '`' : ''),
			number_format_i18n($row['sessions']),
			retails_vi_pct($row['mobile'], $row['sessions']),
			retails_vi_pct($row['bounced'], $row['sessions']),
			null === $row['bounce_scroll'] ? '—' : round($row['bounce_scroll']) . '%',
			null === $row['stayed_seconds'] ? '—' : retails_vi_duration($row['stayed_seconds']) . ', ' . number_format_i18n($row['stayed_pages'], 1) . ' pages',
			number_format_i18n($row['priced']),
			number_format_i18n($row['leads'] + $row['orders']),
		);
	}

	return retails_vi_md_table(array('Landing page → template', 'Visits', 'On phones', 'Bounce', 'How far bouncers scrolled', 'Visitors who stayed: time, pages', 'Got a price', 'Leads + orders'), $out);
}

/**
 * The pages a problem happened on, most visitors first: "/product/x/ (12)".
 */
function retails_vi_problem_pages($type, $label, $period, $filters)
{
	global $wpdb;
	list($where, $params) = retails_vi_where($period['start_gmt'], $period['end_gmt'], $filters);
	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT SUBSTRING_INDEX(e.url, '?', 1) AS path, COUNT(DISTINCT e.session_id) AS n FROM " . retails_vi_table('events') . ' e JOIN ' . retails_vi_table('sessions') . " s ON s.id = e.session_id
		WHERE e.type = %s AND e.label = %s AND e.created_at >= %s AND e.created_at < %s AND {$where} GROUP BY path ORDER BY n DESC LIMIT 6",
		array_merge(array($type, (string) $label, $period['start_gmt'], $period['end_gmt']), $params)
	), ARRAY_A);

	$pages = array();
	foreach ((array) $rows as $row) {
		$key = retails_vi_short_path($row['path'], 60);
		$pages[$key] = ($pages[$key] ?? 0) + (int) $row['n'];
	}
	arsort($pages);
	$out = array();
	foreach (array_slice($pages, 0, 3, true) as $path => $n) {
		$out[] = $path . ' (' . $n . ')';
	}

	return $out ? implode(', ', $out) : '—';
}

/**
 * What visitors said in the exit survey ("What stopped you from ordering?").
 * The contact field is never included.
 */
function retails_vi_md_exit_survey($period, $filters)
{
	global $wpdb;
	$table = $wpdb->prefix . 'retails_exit_survey';
	$md = "### Exit survey: \"What stopped you from ordering?\"\n\n";
	if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
		return $md . "_The exit survey is not installed._\n\n";
	}

	$where = 'created_at >= %s AND created_at < %s';
	$params = array($period['start_gmt'], $period['end_gmt']);
	if (!empty($filters['channel'])) {
		$where .= ' AND channel = %s';
		$params[] = $filters['channel'];
	}
	if (!empty($filters['device'])) {
		$where .= ' AND device = %s';
		$params[] = $filters['device'];
	}

	$totals = $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) AS shown, SUM(status = 'answered') AS answered, SUM(status = 'dismissed') AS dismissed FROM {$table} WHERE {$where}", $params), ARRAY_A);
	$settings = (array) get_option('retails_es_settings', array());
	$enabled = !empty($settings['enabled']);
	if (empty($totals['shown'])) {
		return $md . '_Not shown in this range. The survey is ' . ($enabled ? 'on' : 'off') . ' (Settings → Storefront Sign → Store → "Exit survey")._' . "\n\n";
	}

	$md .= sprintf("Shown %s times · answered %s · closed without answering %s. The survey is %s now.\n\n", number_format_i18n($totals['shown']), number_format_i18n($totals['answered']), number_format_i18n($totals['dismissed']), $enabled ? 'on' : 'off');
	if (empty($totals['answered'])) {
		return $md;
	}
	$labels = function_exists('retails_es_reasons') ? retails_es_reasons() : array();
	$label = static function ($reason) use ($labels) {
		return $labels[$reason]['label'] ?? ($reason ?: '(no reason)');
	};

	$rows = array();
	foreach ((array) $wpdb->get_results($wpdb->prepare(
		"SELECT reason, COUNT(*) AS n, SUM(device = 'mobile') AS mobile, SUM(channel = 'google_ads') AS ads, SUM(cart_items > 0) AS with_cart,
			GROUP_CONCAT(DISTINCT NULLIF(page_type, '') ORDER BY page_type SEPARATOR ', ') AS pages
		FROM {$table} WHERE {$where} AND status = 'answered' GROUP BY reason ORDER BY n DESC",
		$params
	), ARRAY_A) as $row) {
		$rows[] = array($label($row['reason']), $row['n'], retails_vi_pct($row['mobile'], $row['n']), retails_vi_pct($row['ads'], $row['n']), $row['with_cart'], $row['pages'] ?: '—');
	}
	$md .= retails_vi_md_table(array('Reason', 'Answers', 'On phones', 'From ads', 'Had items in cart', 'Page types'), $rows);

	$comments = $wpdb->get_results($wpdb->prepare(
		"SELECT reason, details, page_url, device, channel, cart_items FROM {$table} WHERE {$where} AND status = 'answered' AND details <> '' ORDER BY created_at DESC LIMIT 20",
		$params
	));
	if ($comments) {
		$md .= "\nWhat they wrote (newest first; emails and phone numbers removed):\n\n";
		foreach ($comments as $comment) {
			$md .= sprintf("- “%s” — %s · %s · %s, %s%s\n", retails_vi_md_scrub($comment->details, 300), $label($comment->reason), retails_vi_short_path(strtok((string) $comment->page_url, '?'), 60), retails_vi_channels()[$comment->channel] ?? ($comment->channel ?: 'unknown source'), $comment->device ?: 'unknown device', $comment->cart_items ? ' · had ' . (int) $comment->cart_items . ' item(s) in cart' : '');
		}
	}

	return $md . "\n";
}

/**
 * Live chats visitors started in the range, with what they asked first.
 * Emails and phone numbers are removed from the text.
 */
function retails_vi_md_chats($period, $filters = array(), $limit = 25)
{
	global $wpdb;
	$conversations = $wpdb->prefix . 'sfs_chat_conversations';
	$messages = $wpdb->prefix . 'sfs_chat_messages';
	$md = "### Live chat: what visitors asked\n\n" . (!empty($filters['channel']) || !empty($filters['device']) ? "_All chats in the range: the source and device filters do not apply here._\n\n" : '');
	if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $conversations)) !== $conversations || $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $messages)) !== $messages) {
		return $md . "_Live chat is not installed._\n\n";
	}

	// The shared inbox also holds chats from the other sites; 'sso' is this one.
	$site = in_array('site', (array) $wpdb->get_col("SHOW COLUMNS FROM {$conversations}"), true) ? " AND c.site = 'sso'" : '';
	$range = array($period['start_gmt'], $period['end_gmt']);
	$totals = $wpdb->get_row($wpdb->prepare(
		"SELECT COUNT(*) AS chats, SUM(c.last_admin_message_at IS NOT NULL) AS staff,
			SUM(EXISTS (SELECT 1 FROM {$messages} m WHERE m.conversation_id = c.id AND m.sender_type = 'visitor')) AS asked
		FROM {$conversations} c WHERE c.created_at >= %s AND c.created_at < %s{$site}",
		$range
	), ARRAY_A);
	if (empty($totals['chats'])) {
		return $md . "_No chats in this range._\n\n";
	}
	$md .= sprintf("%s chats opened · visitors wrote in %s · staff replied in %s.\n\n", number_format_i18n($totals['chats']), number_format_i18n($totals['asked']), number_format_i18n($totals['staff']));

	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT c.created_at, c.source_page, c.last_admin_message_at IS NOT NULL AS staff,
			(SELECT COUNT(*) FROM {$messages} m WHERE m.conversation_id = c.id AND m.sender_type = 'bot' AND m.type = 'text') AS bot,
			(SELECT COUNT(*) FROM {$messages} m WHERE m.conversation_id = c.id AND m.sender_type = 'visitor') AS visitor,
			(SELECT GROUP_CONCAT(LEFT(m.body, 200) ORDER BY m.id SEPARATOR ' / ') FROM {$messages} m WHERE m.conversation_id = c.id AND m.sender_type = 'visitor' AND m.type = 'text') AS said
		FROM {$conversations} c WHERE c.created_at >= %s AND c.created_at < %s{$site}
		HAVING visitor > 0 ORDER BY c.created_at DESC LIMIT %d",
		array_merge($range, array((int) $limit))
	));
	if ($rows) {
		$md .= "Newest first. \"/\" separates the visitor's messages.\n\n";
		foreach ($rows as $row) {
			$md .= sprintf(
				"- %s · from %s · %d visitor message(s) · answered by %s — “%s”\n",
				retails_vi_date($row->created_at, 'M j, g:i a'),
				retails_vi_short_path(strtok((string) wp_parse_url((string) $row->source_page, PHP_URL_PATH), '?') ?: '/', 60),
				(int) $row->visitor,
				$row->staff ? 'staff' : ($row->bot ? 'the AI assistant only' : 'nobody'),
				'' !== (string) $row->said ? retails_vi_md_scrub($row->said, 320) : '(sent a photo or file)'
			);
		}
	}

	return $md . "\n";
}

/**
 * The business and the owner's standing rules, so whoever reads the report
 * can change the site without breaking ads, claims or pricing.
 */
function retails_vi_md_business()
{
	$name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
	$host = wp_parse_url(home_url(), PHP_URL_HOST);
	$categories = array();
	$terms = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => true, 'parent' => 0));
	foreach (is_array($terms) ? $terms : array() as $term) {
		if ((int) $term->count > 0) {
			$categories[] = trim(preg_replace('/\s*\|.*$/', '', html_entity_decode($term->name, ENT_QUOTES, 'UTF-8')));
		}
	}
	$products = (int) (wp_count_posts('product')->publish ?? 0);
	$aov = function_exists('retails_vi_average_order') ? retails_vi_average_order() : 0;

	$md = "### About the business\n\n";
	$md .= "- **Store:** {$name} ({$host}), an online sign and print shop with {$products} products" . ($categories ? ' in: ' . implode(', ', $categories) : '') . ".\n";
	$md .= "- **How people buy:** each product page shows the price instantly for the chosen size and options, then Add to cart or Buy now → checkout. Channel letters (lighted 3D letters) are designed in the online sign builder or quoted from a photo.\n";
	$md .= "- **Production and delivery:** made in Renton, WA (channel letters in Washington and California) and shipped to all 50 states. The company does **not** install; the customer's own sign installer or electrician does.\n";
	$md .= '- **Help for buyers:** phone (Mon–Fri 8 am–5 pm Pacific), text, email and live chat.' . ($aov > 0 ? ' Average order over the last 180 days: ' . retails_vi_md_dollars($aov) . '.' : '') . "\n";
	$md .= "- **Traffic:** paid traffic is Google Ads (Performance Max, Shopping and Search). Leads (calls, quote requests, chats) matter as well as orders: staff turn many of them into orders.\n\n";

	$md .= "### Rules for any change (set by the owner)\n\n";
	$md .= "- Keep every page URL and slug as it is: Google Ads final URLs and Merchant Center links point at them.\n";
	$md .= "- Never say or imply the company installs signs, or has shops or offices outside Renton, WA. No made-up reviews, ratings, customer counts, addresses or projects.\n";
	$md .= "- Each product uses its own word (banner, display, decal, flag, print); \"sign\" is for channel letters and sign products. `retails_product_noun()` in `inc/pricing.php` picks it.\n";
	$md .= "- Sizes are always written width × height.\n";
	$md .= "- \"Starting at\" prices on product cards are exact, as on the product page, only without \".00\" (`retails_price_no_cents()`, no rounding since 2026-11-12).\n";
	$md .= "- No permanent sale badges or crossed-out prices. Discounts come only from the dated promotion in Settings → Storefront Sign.\n";
	$md .= "- Channel letter price rules live in both `js/cl.js` (what the builder shows) and `inc/pricing.php` (what the cart charges): change both together.\n";
	$md .= "- The theme owns SEO (titles, meta, schema, sitemaps); Rank Math is not used.\n";
	$md .= "- Late CSS and phone fixes go in `css/ui-polish.css` and `js/ui-polish.js`, which load last (not on the sign builder).\n";
	$md .= "- Changes are made on a local copy and the owner uploads files by hand, so end with the list of changed files. Other work may be editing the same theme: re-read a file right before changing it and keep edits small.\n\n";

	return $md;
}

/**
 * A few computed headlines so the reader starts from the biggest levers.
 */
function retails_vi_md_glance($all, $ads, $products, $period)
{
	$m = $all['summary'];
	$prev = $all['previous'];
	$out = array();
	$conv = static function ($s) {
		return !empty($s['sessions']) ? (($s['orders'] ?? 0) + ($s['leads'] ?? 0)) / $s['sessions'] : 0;
	};
	$change = static function ($now, $before) {
		if ($before <= 0) {
			return $now > 0 ? 'new' : 'no change';
		}
		$pct = ($now - $before) / $before * 100;

		return ($pct >= 0 ? '+' : '') . number_format_i18n($pct) . '%';
	};

	$out[] = sprintf(
		'**Traffic:** %s visits (%s vs %s in the period before), %s of them Google Ads clicks.',
		number_format_i18n($m['sessions']),
		$change($m['sessions'], $prev['sessions'] ?? 0),
		number_format_i18n($prev['sessions'] ?? 0),
		retails_vi_pct($m['ads'] ?? 0, $m['sessions'])
	);
	$order_total = array_sum(array_column($all['orders'], 'total'));
	$out[] = sprintf(
		'**Results:** %s placed (%s, average %s) and %s leads; %s of visits ended in an order or lead (previous period %s).',
		sprintf(_n('%s order', '%s orders', count($all['orders'])), number_format_i18n(count($all['orders']))),
		retails_vi_md_dollars($order_total),
		count($all['orders']) ? retails_vi_md_dollars($order_total / count($all['orders'])) : '—',
		number_format_i18n($m['leads']),
		number_format_i18n($conv($m) * 100, 1) . '%',
		number_format_i18n($conv($prev) * 100, 1) . '%'
	);

	// Funnel steps that lose the most visitors.
	$stages = retails_vi_stages();
	$losses = array();
	$before = $m['sessions'];
	foreach ($stages as $i => $label) {
		if ($i < 2) {
			continue;
		}
		$now = $m[7 === $i ? 'orders' : 's' . $i] ?? 0;
		$losses[] = array('lost' => $before - $now, 'text' => sprintf('%s → %s: %s visits lost (%s)', $stages[$i - 1], $label, number_format_i18n($before - $now), retails_vi_pct($before - $now, $before)));
		$before = $now;
	}
	usort($losses, static function ($a, $b) {
		return $b['lost'] <=> $a['lost'];
	});
	$out[] = '**Biggest drop-offs:** ' . implode('; ', array_column(array_slice($losses, 0, 3), 'text')) . '.';

	$best = $worst = null;
	foreach ($all['channels'] as $row) {
		$rate = $row['sessions'] ? ($row['orders'] + $row['leads']) / $row['sessions'] : 0;
		if ($row['sessions'] >= 30 && (!$best || $rate > $best[1])) {
			$best = array($row, $rate);
		}
		if ($row['sessions'] >= 100 && (!$worst || $rate < $worst[1])) {
			$worst = array($row, $rate);
		}
	}
	if ($best && $worst && $best[0]['label'] !== $worst[0]['label']) {
		$channels = retails_vi_channels();
		$out[] = sprintf(
			'**Sources:** best is %s at %s (%s visits); weakest large source is %s at %s (%s visits).',
			$channels[$best[0]['label']] ?? $best[0]['label'],
			number_format_i18n($best[1] * 100, 1) . '%',
			number_format_i18n($best[0]['sessions']),
			$channels[$worst[0]['label']] ?? $worst[0]['label'],
			number_format_i18n($worst[1] * 100, 1) . '%',
			number_format_i18n($worst[0]['sessions'])
		);
	}

	$devices = array();
	foreach ($all['devices'] as $row) {
		$devices[$row['label']] = $row;
	}
	if (!empty($devices['mobile']['sessions']) && !empty($devices['desktop']['sessions'])) {
		$out[] = sprintf(
			'**Phones vs computers:** %s of visits are on phones; phones convert at %s, computers at %s.',
			retails_vi_pct($devices['mobile']['sessions'], $m['sessions']),
			retails_vi_pct($devices['mobile']['orders'] + $devices['mobile']['leads'], $devices['mobile']['sessions'], 1),
			retails_vi_pct($devices['desktop']['orders'] + $devices['desktop']['leads'], $devices['desktop']['sessions'], 1)
		);
	}

	if (!empty($all['problems'][0])) {
		$problem = $all['problems'][0];
		$out[] = sprintf('**Most common problem:** %s “%s” — %s visitors, %s times.', retails_vi_event_label($problem['type']), $problem['label'] ?: '—', number_format_i18n($problem['sessions']), number_format_i18n($problem['hits']));
	}

	$stuck = null;
	foreach ($products as $product) {
		$gap = $product['priced'] - $product['carted'];
		if ($product['priced'] >= 5 && (!$stuck || $gap > $stuck['priced'] - $stuck['carted'])) {
			$stuck = $product;
		}
	}
	if ($stuck) {
		$out[] = sprintf('**Priced most but not bought:** %s (`%s`) — %d visits got a price, %d added it to the cart.', retails_vi_md_title($stuck['title'] ?: $stuck['path'], 50), retails_vi_short_path($stuck['path'], 70), $stuck['priced'], $stuck['carted']);
	}

	$form = null;
	foreach ($all['forms'] as $row) {
		if (!$form || ($row['started'] - $row['submitted']) > ($form['started'] - $form['submitted'])) {
			$form = $row;
		}
	}
	if ($form && $form['started'] - $form['submitted'] > 0) {
		$out[] = sprintf('**Most abandoned form:** “%s” — %d of %d gave up%s.', $form['label'], $form['started'] - $form['submitted'], $form['started'], $form['left_at'] ? ', most at “' . $form['left_at'] . '”' : '');
	}

	return '- ' . implode("\n- ", $out) . "\n\n";
}

/**
 * Things in the data that are easy to misread.
 */
function retails_vi_md_data_notes($all, $ads)
{
	$m = $all['summary'];
	$notes = array();

	foreach ($ads['keywords'] as $row) {
		if ('(not set)' === $row['label'] && !empty($ads['summary']['sessions'])) {
			$notes[] = sprintf('%s of Google Ads clicks have no keyword: Performance Max and Shopping do not send one. Judge those clicks by campaign, network and landing page.', retails_vi_pct($row['sessions'], $ads['summary']['sessions']));
		}
	}

	$untracked = array_filter($all['orders'], static function ($order) {
		return !$order['source'] && 'google_ads' !== $order['channel'];
	});
	if ($untracked) {
		$notes[] = sprintf('%d of %d orders (%s) have no recorded visit, so their source is unknown (for example orders entered by staff, or a browser that blocked tracking). Key numbers count revenue only from tracked visits; the Orders table lists every order.', count($untracked), count($all['orders']), retails_vi_md_dollars(array_sum(array_column($untracked, 'total'))));
	}

	$foreign = $foreign_bounced = 0;
	foreach ($all['timezones'] as $row) {
		if (retails_vi_is_foreign_tz($row['label'])) {
			$foreign += $row['sessions'];
			$foreign_bounced += $row['bounced'];
		}
	}
	if ($foreign && $m['sessions']) {
		$notes[] = sprintf('%s of visits (%s) come from time zones outside the US and %s of them bounce; most are crawlers or link checkers, not buyers. They push the all-traffic bounce rate up; the Google Ads columns are cleaner.', retails_vi_pct($foreign, $m['sessions']), number_format_i18n($foreign), retails_vi_pct($foreign_bounced, $foreign));
	}

	foreach ($all['browsers'] as $row) {
		if ('Other' === $row['label'] && $m['sessions'] && $row['sessions'] / $m['sessions'] >= 0.05) {
			$notes[] = sprintf('%s visits (%s) report an unrecognized browser and %s of them bounce; many are likely automated.', number_format_i18n($row['sessions']), retails_vi_pct($row['sessions'], $m['sessions']), retails_vi_pct($row['bounced'], $row['sessions']));
		}
	}

	$prev = $all['previous']['sessions'] ?? 0;
	if ($prev > 0 && $prev < $m['sessions'] / 2) {
		$notes[] = sprintf('The previous period had far fewer visits (%s vs %s), so percentage changes against it are not like-for-like.', number_format_i18n($prev), number_format_i18n($m['sessions']));
	}

	if (!empty($all['robots'])) {
		$notes[] = number_format_i18n($all['robots']) . ' robot visits were already left out of every number.';
	}
	$notes[] = 'Leads are per visit: one visitor who calls twice in one visit counts once. Calls are taps on a phone link, not answered calls.';

	return '- ' . implode("\n- ", $notes) . "\n\n";
}

/**
 * Visits and results per day (per hour for one-day ranges), to line up
 * changes on the site or in Google Ads with what happened next.
 */
function retails_vi_md_trend($all, $period)
{
	$format = $period['hourly'] ? 'ga' : 'M j';
	$placed = array();
	foreach ($all['orders'] as $order) {
		$key = wp_date($format, strtotime($order['ordered_gmt'] . ' UTC'));
		$placed[$key]['n'] = ($placed[$key]['n'] ?? 0) + 1;
		$placed[$key]['total'] = ($placed[$key]['total'] ?? 0) + $order['total'];
	}

	$rows = array();
	foreach ($all['series'] as $point) {
		$day = $point['label'];
		if (!$period['hourly']) {
			// Labels have no year; a range can run from December into January.
			$when = strtotime($point['label'] . ' ' . $period['start']->format('Y') . ' 12:00 UTC');
			if ($when && $when < $period['start']->getTimestamp() - DAY_IN_SECONDS) {
				$when = strtotime($point['label'] . ' ' . $period['end']->format('Y') . ' 12:00 UTC');
			}
			$day .= $when ? ' ' . gmdate('D', $when) : '';
		}
		$rows[] = array(
			$day,
			$point['sessions'],
			$point['ads'],
			$point['conversions'],
			isset($placed[$point['label']]) ? $placed[$point['label']]['n'] . ' (' . retails_vi_md_dollars($placed[$point['label']]['total']) . ')' : '0',
		);
	}

	return retails_vi_md_table(array($period['hourly'] ? 'Hour' : 'Day', 'Visits', 'Ad clicks', 'Visits with a lead or order', 'Orders placed'), $rows);
}

/**
 * The full insights report as Markdown, written so it can be handed to
 * Claude (or a developer) to find and fix what stops visitors from buying.
 */
function retails_vi_export_markdown($period, $filters)
{
	$all = retails_vi_report_data($period, $filters, false);
	$ads = retails_vi_report_data($period, array_merge($filters, array('channel' => 'google_ads')), true);
	$m = $all['summary'];
	$a = $ads['summary'];
	$prev = $all['previous'];
	$ap = $ads['previous'];
	$site = wp_parse_url(home_url(), PHP_URL_HOST);
	$range = $period['start']->format('M j, Y') . ' – ' . $period['end']->modify('-1 second')->format('M j, Y');
	$filter_text = array_filter(array(
		!empty($filters['channel']) ? 'source: ' . (retails_vi_channels()[$filters['channel']] ?? $filters['channel']) : '',
		!empty($filters['device']) ? 'device: ' . $filters['device'] : '',
	));
	$template_label = static function ($row) {
		$template = retails_vi_path_template($row['label']);
		return retails_vi_short_path($row['label'], 90) . ($template ? ' → `' . $template . '`' : '');
	};

	$products = retails_vi_product_funnel($period, $filters, $all['orders']);

	$md = "# Visitor Insights report — {$site}\n\n";
	$md .= "- **Period:** {$range} ({$period['label']}, site time zone " . wp_timezone_string() . ")\n";
	$md .= '- **Filters:** ' . ($filter_text ? implode(', ', $filter_text) : 'none (all traffic)') . "\n";
	$md .= '- **Generated:** ' . wp_date('M j, Y g:i a') . "\n";
	$md .= "- **Source:** Visitor Insights (first-party tracking in the site's theme), orders, the exit survey and live chat. No names, emails or full IP addresses; text visitors typed has emails and phone numbers removed.\n\n";
	$md .= "**Contents:** Instructions · At a glance · Data notes · Page map · Key numbers · Day by day · Automatic findings · Purchase funnel · Products · Orders · Landing pages · Exit pages · Problems · Form abandonment · Clicks · What visitors told us · Audience · Google Ads detail · Suggestions · Leads · Journeys of buyers · Journeys of visitors who did not buy · Definitions\n\n";

	$md .= "## Instructions for Claude\n\n";
	$md .= "You are helping the owner of {$site} get more orders. This report shows what visitors did on the site in the period above. Use it to find what stops visitors from buying, fix what can be fixed in the website code, and say what to change in Google Ads and in how the team follows up.\n\n";
	$md .= retails_vi_md_business();
	$md .= "### What to do\n\n";
	$md .= "1. Start from \"At a glance\" and \"Data notes\", then read the funnel, products, landing pages, problems, form abandonment, what visitors told us, and the journeys. Compare the buyers' journeys with the non-buyers': what did buyers see or do that the others did not?\n";
	$md .= "2. Work out the most likely reasons visitors do not buy, biggest first. Back each with numbers from this report, and say when a number is too small to trust (under about 30 visits is a hint, not proof).\n";
	$md .= "3. Check each cause against the code before changing anything. All site code is in the custom theme at `wp-content/themes/tr-retails/` (see the page map). Open the page on a phone-sized screen (390 px wide) as well as a computer: most visitors are on phones.\n";
	$md .= "4. Fix what can be fixed in the theme: errors visitors hit, pricing messages that show before anyone typed, landing pages whose first screen does not show the product, price and a clear button, mobile layout problems, form friction, missing trust details (shipping, turnaround, proof before printing), broken links. Make the smallest change that fixes the cause and explain each one.\n";
	$md .= "5. List separately what has to change outside the website: Google Ads (campaigns, asset groups, keywords, negative keywords, placements, networks, locations, ad schedule, bids, IP exclusions), Merchant Center, and follow-up by the team (quote requests, calls, chats, abandoned carts).\n";
	$md .= "6. Do not invent numbers that are not in this report. If something needed is missing, say what data would answer it.\n\n";
	$md .= "### How to answer\n\n";
	$md .= "1. **Diagnosis** — three to five sentences: the main reasons visitors are not buying, with the numbers behind them.\n";
	$md .= "2. **Top actions** — a table ranked by how many extra orders or leads each is likely to bring: # · change · evidence (section and number) · where (file, or Google Ads screen) · effort (small / medium / large) · number to watch in the next report.\n";
	$md .= "3. **Website changes** — make them one at a time, explain each, and finish with the list of changed files for upload.\n";
	$md .= "4. **Google Ads and Merchant Center** — exact steps (screen → setting → value); these cannot be changed in code.\n";
	$md .= "5. **Team follow-up** — who to contact and what to send (from the leads, chats and suggestions sections).\n";
	$md .= "6. **Next report** — what to compare after the changes have been live for 7–14 days.\n\n";

	$md .= "## At a glance\n\n" . retails_vi_md_glance($all, $ads, $products, $period);
	$md .= "## Data notes (read before drawing conclusions)\n\n" . retails_vi_md_data_notes($all, $ads);

	$md .= "## Page map: page types → theme files\n\n";
	$rows = array();
	foreach (retails_vi_page_type_files() as $type => $files) {
		$rows[] = array($type, '`' . implode('`, `', $files) . '`');
	}
	$md .= retails_vi_md_table(array('Page type', 'Files (in wp-content/themes/tr-retails/)'), $rows) . "\n";

	$conv = static function ($s) {
		return retails_vi_pct(($s['orders'] ?? 0) + ($s['leads'] ?? 0), $s['sessions'] ?? 0, 1);
	};
	$num = static function ($s, $key, $decimals = 0) {
		return number_format_i18n($s[$key] ?? 0, $decimals);
	};
	$rate = static function ($s, $key) {
		return retails_vi_pct($s[$key] ?? 0, $s['sessions'] ?? 0);
	};
	$time = static function ($s) {
		return retails_vi_duration($s['avg_engaged'] ?? 0);
	};
	$money = static function ($s) {
		return retails_vi_money($s['revenue'] ?? 0);
	};
	$per_visit = static function ($s) {
		return !empty($s['sessions']) ? retails_vi_money(($s['revenue'] ?? 0) / $s['sessions']) : '—';
	};
	$md .= "## Key numbers\n\n";
	$md .= retails_vi_md_table(array('Metric', 'All traffic', 'Previous period', 'Google Ads', 'Ads previous period'), array(
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
		array('Returning visits (visitor came before)', $rate($m, 'returning_visits'), $rate($prev, 'returning_visits'), $rate($a, 'returning_visits'), $rate($ap, 'returning_visits')),
		array('Got a price (configured a product)', $num($m, 's3'), $num($prev, 's3'), $num($a, 's3'), $num($ap, 's3')),
		array('Reached checkout', $num($m, 's5'), $num($prev, 's5'), $num($a, 's5'), $num($ap, 's5')),
		array('Revenue per visit', $per_visit($m), $per_visit($prev), $per_visit($a), $per_visit($ap)),
	)) . "\n";

	$md .= "## Day by day\n\nUse this to line up changes on the site or in Google Ads with what happened next.\n\n" . retails_vi_md_trend($all, $period) . "\n";

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
	foreach (retails_vi_stages() as $i => $label) {
		$col = 7 === $i ? 'orders' : (1 === $i ? 'sessions' : 's' . $i);
		$rows[] = array(
			$i . '. ' . $label,
			number_format_i18n($m[$col]) . ' (' . retails_vi_pct($m[$col], $m['sessions'], 1) . ')',
			$i > 1 ? retails_vi_pct($prev_all - $m[$col], $prev_all) . ' lost' : '',
			number_format_i18n($a[$col]) . ' (' . retails_vi_pct($a[$col], $a['sessions'], 1) . ')',
			$i > 1 ? retails_vi_pct($prev_ads - $a[$col], $prev_ads) . ' lost' : '',
		);
		$prev_all = $m[$col];
		$prev_ads = $a[$col];
	}
	$md .= retails_vi_md_table(array('Step', 'All traffic', 'Lost from previous step', 'Google Ads', 'Lost from previous step'), $rows);
	$md .= sprintf("\nLeads (calls, emails, quote requests) are counted outside the funnel: all traffic %d, Google Ads %d.\n\n", $m['leads'], $a['leads']);

	$md .= "## Products: viewed → priced → cart → bought\n\n";
	$md .= "Most-viewed product pages (all use `single-product.php`; the channel letter builder is `cl_builderr.php`), plus any product that sold. \"Got a price\" = changed an option or saw a price. \"Saw a pricing error\" = a size or text message such as \"Please enter the height\"; if it is close to the number of views, the message probably shows before the visitor typed anything. \"Prices shown\" are the totals visitors were quoted for the sizes they chose.\n\n";
	$md .= retails_vi_md_products($products) . "\n";

	$md .= "## Orders and where they came from\n\nEach order is credited to the last ad click in the 90 days before it (as Google Ads does), else the last search or referral visit.\n\n";
	$rows = array();
	foreach ($all['orders'] as $order) {
		$source = $order['source'];
		$label = $source ? (retails_vi_channels()[$source['channel']] ?? $source['channel']) : 'Not tracked';
		if (retails_vi_order_has_unlinked_ad_click($order['id'], $source)) {
			$label = $source ? $label . ' (after an earlier Google ad click; keyword unknown)' : 'Google Ads (no visit recorded)';
		}
		$rows[] = array(
			retails_vi_date($order['ordered_gmt'], 'M j, Y'),
			retails_vi_money($order['total']),
			retails_vi_order_items_text($order['id']),
			$source && !empty($source['device']) ? $source['device'] : '—',
			$label,
			$source && '' !== $source['keyword'] ? $source['keyword'] : '—',
			$source && '' !== $source['campaign'] ? retails_vi_campaign_name($source['campaign']) : '—',
			$source ? retails_vi_short_path(strtok($source['landing'], '?'), 60) : '—',
			$source ? $source['visits'] : '—',
			!$source ? '—' : ($source['visit_id'] === $source['order_visit_id'] ? 'same visit' : retails_vi_time_between($source['visit_at'], $source['ordered_at']) . ' later'),
		);
	}
	$md .= retails_vi_md_table(array('Ordered', 'Total', 'Bought', 'Device', 'Source', 'Keyword', 'Campaign', 'Landing page', 'Visits', 'Ordered after the credited visit'), $rows) . "\n";

	$md .= "## Landing pages (first page of each visit)\n\n### All traffic\n\n" . retails_vi_md_breakdown($all['landing'], 'Page → template', $template_label);
	$md .= "\n### Google Ads\n\n" . retails_vi_md_breakdown($ads['landing'], 'Page → template', $template_label);
	$md .= "\n### First-screen check (all traffic)\n\nFor visitors who bounced: how far down the page they scrolled before leaving. Near 0% means they judged the page on the first screen alone, so the first screen (on phones especially) must show the product from the ad, a price and one clear button.\n\n" . retails_vi_md_landing_check($period, $filters) . "\n";

	$md .= "## Exit pages of visitors who did not buy\n\n" . retails_vi_md_breakdown($all['exits'], 'Last page → template', $template_label) . "\n";

	$md .= "## Problems visitors hit\n\n";
	$rows = array();
	foreach ($all['problems'] as $row) {
		$rows[] = array(retails_vi_event_label($row['type']), $row['label'] ?: '—', $row['hits'], $row['sessions'], (int) ($row['buyers'] ?? 0), retails_vi_problem_pages($row['type'], $row['label'], $period, $filters), retails_vi_error_files($row['type']));
	}
	foreach ($all['not_found'] as $row) {
		$rows[] = array('Page not found (404)', retails_vi_short_path($row['label'], 90), $row['hits'], $row['sessions'], '—', '—', 'Fix the link pointing here, or add a redirect');
	}
	$md .= retails_vi_md_table(array('Problem', 'Exact message / element', 'Times', 'Visitors', 'Of them bought anyway', 'Pages (visitors)', 'Where to look'), $rows) . "\n";

	$md .= "## Form abandonment\n\n";
	$rows = array();
	foreach ($all['forms'] as $row) {
		$rows[] = array($row['label'], $row['started'], $row['submitted'], retails_vi_pct($row['started'] - $row['submitted'], $row['started']), $row['left_at'] ? $row['left_at'] . ' (' . $row['left_at_n'] . ')' : '—');
	}
	$md .= retails_vi_md_table(array('Form', 'Started', 'Finished', 'Gave up', 'Field most people stopped at'), $rows) . "\n";

	$md .= "## What visitors click\n\n";
	$rows = array();
	foreach (retails_vi_event_breakdown(array('click', 'call_click', 'email_click'), $period['start_gmt'], $period['end_gmt'], $filters, 25) as $row) {
		$rows[] = array(('click' !== $row['type'] ? retails_vi_event_label($row['type']) . ': ' : '') . ($row['label'] ?: '(no text)'), $row['hits'], $row['sessions'], $row['buyers']);
	}
	$md .= retails_vi_md_table(array('Button / link', 'Clicks', 'Visitors', 'Of them bought'), $rows) . "\n";

	$md .= "## What visitors told us\n\nTheir own words are the best clue to what is missing on the site.\n\n";
	$md .= retails_vi_md_exit_survey($period, $filters);
	$md .= retails_vi_md_chats($period, $filters);

	$md .= "## Audience\n\n### Traffic sources\n\n" . retails_vi_md_breakdown($all['channels'], 'Source', static function ($row) {
		return retails_vi_channels()[$row['label']] ?? $row['label'];
	});
	$md .= "\n### New vs returning visitors\n\n" . retails_vi_md_breakdown(retails_vi_breakdown("IF(s.is_new = 1, 'New visitor', 'Returning visitor')", $period['start_gmt'], $period['end_gmt'], $filters, 2), 'Visitor');
	$md .= "\n### Devices (all traffic)\n\n" . retails_vi_md_breakdown($all['devices'], 'Device');
	$md .= "\n### Devices (Google Ads)\n\n" . retails_vi_md_breakdown($ads['devices'], 'Device');
	$md .= "\n### Browsers\n\n" . retails_vi_md_breakdown($all['browsers'], 'Browser');
	$md .= "\n### Locations (browser time zone)\n\n" . retails_vi_md_breakdown(array_slice($all['timezones'], 0, 12), 'Time zone', static function ($row) {
		return ($row['label'] ?: 'Unknown') . (retails_vi_is_foreign_tz($row['label']) ? ' (outside US)' : '');
	}) . "\n";

	$md .= "## Google Ads detail\n\n### Keywords (utm_term)\n\n" . retails_vi_md_breakdown($ads['keywords'], 'Keyword');
	$rows = array();
	foreach ($ads['campaigns'] as $row) {
		$type = retails_vi_campaign_type($row['label']);
		$rows[] = array_merge(
			array(('(not set)' === $row['label'] ? '(not set)' : retails_vi_campaign_name($row['label'])) . ($type ? ' [' . $type . ']' : '')),
			retails_vi_md_metrics($row),
			array(max((int) $row['orders'], (int) $row['credited_orders']), $row['credited_revenue'] > 0 ? retails_vi_money($row['credited_revenue']) : '—')
		);
	}
	$md .= "\n### Campaigns\n\n" . retails_vi_md_table(array('Campaign', 'Visits', 'Bounce', 'Avg. engaged', 'Viewed product', 'Cart', 'Leads', 'Orders in visit', 'Conv.', 'Orders earned', 'Revenue earned'), $rows);
	$md .= "\n### Where the ads were clicked (from the referring page)\n\n" . retails_vi_md_breakdown($ads['networks'], 'Network', static function ($row) {
		return retails_vi_ad_networks()[$row['label']] ?? ($row['label'] ?: 'Unknown');
	});
	$md .= "\n### Partner websites that sent ad clicks\n\n" . retails_vi_md_breakdown($ads['placements'], 'Site');
	$rows = array();
	foreach ($ads['hours'] as $hour => $row) {
		if ($row['sessions']) {
			$rows[] = array(sprintf('%02d:00–%02d:59', $hour, $hour), $row['sessions'], $row['conversions']);
		}
	}
	$md .= "\n### Ad clicks by hour of day (site time)\n\n" . retails_vi_md_table(array('Hour', 'Ad clicks', 'Leads + orders'), $rows);
	$rows = array();
	foreach ($ads['repeat_clickers'] as $row) {
		$ip = false !== strpos($row['ip'], ':') ? implode(':', array_slice(explode(':', $row['ip']), 0, 2)) . ':…' : preg_replace('/\.\d+$/', '.x', $row['ip']);
		$rows[] = array($ip, $row['clicks'], $row['devices'], retails_vi_duration($row['avg_engaged']), ($row['orders'] > 0 ? 'bought' : ($row['leads'] > 0 ? 'lead' : 'nothing')) . (!empty($row['carrier']) ? ' (phone carrier, shared IP: do not exclude)' : ''));
	}
	$md .= "\n### Repeat ad clickers (3+ ad clicks from one IP; partly masked)\n\n" . retails_vi_md_table(array('IP', 'Ad clicks', 'Devices', 'Avg. engaged', 'Result'), $rows);
	$md .= "\nFull IP addresses for Google Ads IP exclusions are on the Google Ads tab of Visitor Insights.\n\n";

	if (function_exists('retails_vi_md_growth')) {
		$md .= retails_vi_md_growth($period) . "\n";
	}

	$md .= "## Journeys of visitors who bought\n\nThe most recent order visits, to compare with the visits below that did not buy. Times are minutes:seconds from the start of the visit.\n";
	$md .= retails_vi_md_journeys($period, $filters, 6, true) . "\n";

	$md .= "## Sample journeys of visitors who showed interest but did not buy\n\nFurthest-reaching visits first. Times are minutes:seconds from the start of the visit.\n";
	$md .= retails_vi_md_journeys($period, $filters, 20) . "\n";

	$md .= "## Definitions\n\n";
	$md .= "- **Visit:** one browsing session; a new visit starts after 30 idle minutes or a new ad click.\n";
	$md .= "- **Bounce:** a visit with one page, under 10 seconds engaged, no shopping step and no lead.\n";
	$md .= "- **Engaged time:** time the page was open, visible and in use (60 seconds without input stops the clock).\n";
	$md .= "- **Funnel steps:** 2 = opened a product or the sign builder; 3 = changed an option or got a price; 4 = added to cart; 5 = opened checkout; 6 = clicked pay; 7 = order placed.\n";
	$md .= "- **Lead:** clicked a phone or email link, or submitted a quote form.\n";
	$md .= "- **Returning visit:** the browser had visited before (same device and browser; cleared cookies count as new).\n";
	$md .= "- **Location:** the browser's time zone, not an IP lookup; VPNs and travel make it approximate.\n";
	$md .= "- **Robots:** visits from automated browsers (a screen size only crawlers report, such as 1024x1024 on Linux; Google's own checkers) are left out of every number." . (!empty($all['robots']) ? ' ' . $all['robots'] . ' were left out of this range.' : '') . "\n";
	$md .= "- **AI assistants:** visits from links in ChatGPT, Copilot, Perplexity, Gemini and similar (counted as referrals before Nov 9, 2026).\n";
	$md .= "- **Rage click:** three fast clicks on the same spot, usually on something that looks clickable but does nothing.\n";

	return $md;
}

function retails_vi_export_url($args = array())
{
	return wp_nonce_url(add_query_arg(urlencode_deep(array_merge(array('action' => 'retails_vi_export'), $args)), admin_url('admin-post.php')), 'retails_vi_export');
}

function retails_vi_export()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.', 403);
	}
	check_admin_referer('retails_vi_export');

	$md = retails_vi_export_markdown(retails_vi_period(), retails_vi_request_filters());

	nocache_headers();
	header('Content-Type: text/markdown; charset=utf-8');
	if (empty($_GET['inline'])) {
		$file = 'visitor-insights-' . wp_parse_url(home_url(), PHP_URL_HOST) . '-' . wp_date('Y-m-d') . '.md';
		header('Content-Disposition: attachment; filename="' . sanitize_file_name($file) . '"');
	}
	echo $md; // phpcs:ignore WordPress.Security.EscapeOutput -- plain-text download.
	exit;
}
add_action('admin_post_retails_vi_export', 'retails_vi_export');

/**
 * Export buttons on the Overview and Google Ads tabs, for the range and
 * filters being viewed.
 */
function retails_vi_render_export_bar($period, $filters)
{
	$args = array_filter(array(
		'period' => $period['key'],
		'from' => 'custom' === $period['key'] ? $period['start']->format('Y-m-d') : '',
		'to' => 'custom' === $period['key'] ? $period['end']->modify('-1 second')->format('Y-m-d') : '',
		'channel' => $filters['channel'] ?? '',
		'device' => $filters['device'] ?? '',
		'network' => $filters['network'] ?? '',
	));
	?>
	<div class="wvi-export wvi-card">
		<span class="wvi-export-icon dashicons dashicons-media-text"></span>
		<div class="wvi-export-text">
			<strong>Export insights report</strong>
			<span class="wvi-muted"><?php echo esc_html($period['label']); ?> · all traffic and Google Ads · a Markdown brief for Claude: business rules, funnel, every product's views → price → cart → orders, landing pages, errors, survey answers and chat questions, buyer and non-buyer journeys, and the answer format. Claude can then find and fix what stops visitors from buying. No names, emails or full IPs.</span>
		</div>
		<a class="button" href="<?php echo esc_url(retails_vi_export_url($args)); ?>"><span class="dashicons dashicons-download"></span> Download .md</a>
		<button type="button" class="button button-primary wvi-copy-report" data-url="<?php echo esc_url(retails_vi_export_url(array_merge($args, array('inline' => 1)))); ?>"><span class="dashicons dashicons-clipboard"></span> <span>Copy for Claude</span></button>
	</div>
	<?php
}

require_once __DIR__ . '/visitor-insights-leads.php';
require_once __DIR__ . '/visitor-insights-chat-ai.php';
require_once __DIR__ . '/visitor-snaps.php';
require_once __DIR__ . '/screenshots.php';
