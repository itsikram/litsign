<?php
/**
 * Screenshots: a picture of what a visitor saw when they ran into an error or left the site.
 *
 * Each screenshot is a post of the private type `sfs_screenshot` (wp-admin → Screenshots),
 * saved with the visitor's details from Visitor Insights.
 *
 * 1. js/visitor-tracker.js hands problems it sees (script errors, failed price, cart,
 *    upload and payment requests) to js/vi-page-copy.js, which also notices error messages
 *    on the page, the browser's form checks, 404 pages and server errors, and the moment the
 *    visitor leaves. It sends a copy of the page (HTML, typed values, scroll spots; password
 *    and card fields masked) to retails/v1/vi-copy. Nothing is drawn on the visitor's
 *    page, so it never pauses or flashes.
 * 2. The copy is saved here. The admin's browser draws it into a JPEG the first time the
 *    Screenshots list or the screenshot is opened (Live Chat's snap-render.js), in a
 *    sandboxed frame that runs no scripts.
 * 3. "Left the site" screenshots are taken back when the visitor turns out to be still
 *    here: their next page in the same tab, their return to the tab, or (checked hourly
 *    and whenever the list opens) any later activity in the same visit.
 *
 * Only visits Visitor Insights records can send one: staff, robots and visitors who opted
 * out never load the script, and the server checks the visit again. Uploads are limited per
 * visit, per IP address and per hour. Old screenshots are deleted after the days set in
 * Screenshots → Settings, and the oldest go when there are more than the maximum.
 *
 * Files: uploads/sfs-screenshots/<yyyymm>/<ref>.html.gz (copy) and <ref>.jpg (picture);
 * large inline styles, the same for every visitor, are stored once in css/<hash>.css.
 * The folder denies all web access; files are served to admins only.
 *
 * Deleting a screenshot never goes through WordPress's delete hooks: those empty the whole
 * page cache (sfs-page-cache) for any deleted post.
 */

defined('ABSPATH') || exit;

const RETAILS_SHOT_TYPE = 'sfs_screenshot';
const RETAILS_SHOT_OPTION = 'sfs_shots_settings'; // not "retails_*": those clear the OpenAI feed cache
const RETAILS_SHOT_MAX_GZ = 6291456;
const RETAILS_SHOT_MAX_RAW = 20971520;
const RETAILS_SHOT_MAX_IMAGE = 4194304;
const RETAILS_SHOT_MAX_CSS = 1572864;
// Per visit: error screenshots, "left the site" sends (each replaces the last), style uploads.
const RETAILS_SHOT_VISIT_ERRORS = 12;
const RETAILS_SHOT_VISIT_EXITS = 24;

/* ---------------------------------------------------------------------------
 * Settings and the post type
 * ------------------------------------------------------------------------ */

function retails_shots_settings()
{
	$saved = get_option(RETAILS_SHOT_OPTION, array());
	$saved = is_array($saved) ? $saved : array();

	return array(
		'errors' => array_key_exists('errors', $saved) ? (bool) $saved['errors'] : true,
		'exits' => array_key_exists('exits', $saved) ? (bool) $saved['exits'] : true,
		'keep_days' => max(1, min(180, (int) ($saved['keep_days'] ?? 14))),
		'max' => max(100, min(10000, (int) ($saved['max'] ?? 1500))),
	);
}

function retails_shots_register_type()
{
	register_post_type(RETAILS_SHOT_TYPE, array(
		'labels' => array(
			'name' => 'Screenshots',
			'singular_name' => 'Screenshot',
			'menu_name' => 'Screenshots',
			'all_items' => 'All screenshots',
			'edit_item' => 'Screenshot',
			'view_item' => 'View screenshot',
			'search_items' => 'Search screenshots',
			'not_found' => 'No screenshots yet. They appear here when a visitor runs into an error or leaves the site.',
			'not_found_in_trash' => 'No screenshots.',
		),
		'description' => 'What a visitor saw when they ran into an error or left the site.',
		'public' => false,
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => false,
		'show_in_admin_bar' => false,
		'show_in_rest' => false,
		'menu_position' => 4,
		'menu_icon' => 'dashicons-camera',
		'hierarchical' => false,
		'supports' => false,
		'has_archive' => false,
		'rewrite' => false,
		'query_var' => false,
		'can_export' => false,
		'delete_with_user' => false,
		'map_meta_cap' => true,
		'capability_type' => array('sfs_screenshot', 'sfs_screenshots'),
		'capabilities' => array(
			'create_posts' => 'do_not_allow',
			'edit_posts' => 'manage_options',
			'edit_others_posts' => 'manage_options',
			'edit_published_posts' => 'manage_options',
			'edit_private_posts' => 'manage_options',
			'delete_posts' => 'manage_options',
			'delete_others_posts' => 'manage_options',
			'delete_published_posts' => 'manage_options',
			'delete_private_posts' => 'manage_options',
			'publish_posts' => 'manage_options',
			'read_private_posts' => 'manage_options',
			'read' => 'read',
		),
	));
}
add_action('init', 'retails_shots_register_type');

/** What happened, in words. */
function retails_shots_types()
{
	return array(
		'js_error' => 'Script error',
		'price_error' => 'Price or size error',
		'upload_error' => 'Upload failed',
		'cart_error' => 'Add to cart failed',
		'checkout_error' => 'Checkout error',
		'payment_error' => 'Payment error',
		'form_error' => 'Form check',
		'message' => 'Error message shown',
		'server_error' => 'Server error',
		'page_404' => 'Page not found (404)',
		'exit_intent' => 'Moved the mouse to close or leave the page',
		// Closing a tab hides it first, so "hidden" covers closing too.
		'hidden' => 'Closed the tab, or switched to another tab or app',
		'closed' => 'Left the page (closed it or went to another address)',
		'external' => 'Followed a link to another site',
	);
}

/* ---------------------------------------------------------------------------
 * Storage
 * ------------------------------------------------------------------------ */

function retails_shots_root()
{
	$uploads = wp_upload_dir(null, false);

	return trailingslashit($uploads['basedir']) . 'sfs-screenshots';
}

function retails_shots_prepare_dir($dir)
{
	$root = retails_shots_root();
	if (!wp_mkdir_p($root)) {
		return false;
	}
	if (!file_exists($root . '/.htaccess')) {
		// Apache 2.4 + 2.2 and IIS; nginx ignores these, so every file name is also random.
		file_put_contents($root . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents($root . '/index.php', "<?php\n// Silence is golden.\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents($root . '/web.config', '<?xml version="1.0"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>'); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	return wp_mkdir_p($dir);
}

/**
 * Path of one of a screenshot's files ('' when its details are damaged).
 *
 * @param array  $shot The `_shot` meta.
 * @param string $ext  'html.gz' or the picture's extension.
 */
function retails_shots_path(array $shot, $ext)
{
	$dir = (string) ($shot['dir'] ?? '');
	$ref = (string) ($shot['ref'] ?? '');
	if (!preg_match('/^\d{6}$/', $dir) || !preg_match('/^[a-f0-9]{24}$/', $ref) || !preg_match('/^(html\.gz|jpg|png|webp)$/', (string) $ext)) {
		return '';
	}

	return retails_shots_root() . '/' . $dir . '/' . $ref . '.' . $ext;
}

function retails_shots_css_path($key)
{
	return preg_match('/^[a-f0-9]{20}$/', (string) $key) ? retails_shots_root() . '/css/' . $key . '.css' : '';
}

function retails_shots_meta($post_id)
{
	$shot = get_post_meta($post_id, '_shot', true);

	return is_array($shot) ? $shot : array();
}

function retails_shots_find($ref)
{
	global $wpdb;
	if (!preg_match('/^[a-f0-9]{24}$/', (string) $ref)) {
		return 0;
	}

	return (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s LIMIT 1", RETAILS_SHOT_TYPE, $ref));
}

function retails_shots_delete_files(array $shot)
{
	foreach (array('html.gz', 'jpg', 'png', 'webp') as $ext) {
		$path = retails_shots_path($shot, $ext);
		if ($path && file_exists($path)) {
			wp_delete_file($path);
		}
	}
}

/**
 * Delete screenshots with their files, straight from the database.
 *
 * @param int[] $ids
 * @return int How many were deleted.
 */
function retails_shots_delete(array $ids)
{
	global $wpdb;
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	$deleted = 0;
	foreach (array_chunk($ids, 100) as $chunk) {
		$in = implode(',', $chunk);
		// Only screenshots, whatever IDs were passed.
		$chunk = array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID IN ({$in})", RETAILS_SHOT_TYPE))); // phpcs:ignore WordPress.DB.PreparedSQL
		if (!$chunk) {
			continue;
		}
		$in = implode(',', $chunk);
		foreach ((array) $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_shot' AND post_id IN ({$in})") as $value) { // phpcs:ignore WordPress.DB.PreparedSQL
			$shot = maybe_unserialize($value);
			if (is_array($shot)) {
				retails_shots_delete_files($shot);
			}
		}
		$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$in})"); // phpcs:ignore WordPress.DB.PreparedSQL
		$deleted += (int) $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ({$in})"); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ($chunk as $id) {
			wp_cache_delete($id, 'posts');
			wp_cache_delete($id, 'post_meta');
		}
	}
	if ($deleted) {
		wp_cache_set_posts_last_changed();
		retails_shots_counts_changed();
	}

	return $deleted;
}

/** Deleting or trashing a screenshot anywhere in wp-admin: delete it here instead. */
function retails_shots_pre_delete($check, $post)
{
	if ($post instanceof WP_Post && RETAILS_SHOT_TYPE === $post->post_type) {
		retails_shots_delete(array($post->ID));

		return $post;
	}

	return $check;
}
add_filter('pre_delete_post', 'retails_shots_pre_delete', 10, 2);
add_filter('pre_trash_post', 'retails_shots_pre_delete', 10, 2);

function retails_shots_counts_changed()
{
	delete_transient('sfs_shots_new_count');
}

/* ---------------------------------------------------------------------------
 * Tracker settings (read by inc/visitor-insights.php)
 * ------------------------------------------------------------------------ */

/**
 * What js/visitor-tracker.js needs, or '' when screenshots are off.
 */
function retails_shots_tracker_config()
{
	$settings = retails_shots_settings();
	$file = get_template_directory() . '/js/vi-page-copy.js';
	if ((!$settings['errors'] && !$settings['exits']) || !is_readable($file)) {
		return '';
	}

	return array(
		'js' => get_template_directory_uri() . '/js/vi-page-copy.js?ver=' . filemtime($file),
		'url' => rest_url('retails/v1/vi-copy'),
		'back' => rest_url('retails/v1/vi-copy-back'),
		'css' => rest_url('retails/v1/vi-copy-css'),
		'err' => $settings['errors'] ? 1 : 0,
		'exit' => $settings['exits'] ? 1 : 0,
	);
}

/* ---------------------------------------------------------------------------
 * Receiving copies (public endpoints, checked against Visitor Insights)
 * ------------------------------------------------------------------------ */

function retails_shots_register_routes()
{
	foreach (array('vi-copy' => 'retails_shots_rest_receive', 'vi-copy-back' => 'retails_shots_rest_back', 'vi-copy-css' => 'retails_shots_rest_css') as $route => $callback) {
		register_rest_route('retails/v1', '/' . $route, array(
			'methods' => 'POST',
			'callback' => $callback,
			'permission_callback' => '__return_true',
		));
	}
}
add_action('rest_api_init', 'retails_shots_register_routes');

/**
 * At most $limit hits per window for a bucket (IP address, visit, whole site).
 */
function retails_shots_allow($bucket, $limit, $window = HOUR_IN_SECONDS)
{
	$key = 'sfs_shot_rl_' . md5($bucket);
	$now = time();
	$data = get_transient($key);
	if (!is_array($data) || (int) ($data['until'] ?? 0) <= $now) {
		$data = array('n' => 0, 'until' => $now + $window);
	}
	if ((int) $data['n'] >= $limit) {
		return false;
	}
	$data['n'] = (int) $data['n'] + 1;
	set_transient($key, $data, max(1, (int) $data['until'] - $now));

	return true;
}

/**
 * The Visitor Insights visit a request belongs to, or why it may not send.
 *
 * @return object|string Visit row, or 'skip' (staff, robot, opted out) / 'unknown'.
 */
function retails_shots_visit($session_key, $visitor_key)
{
	global $wpdb;
	if (!function_exists('retails_vi_table') || !preg_match('/^[a-f0-9]{32}$/', (string) $session_key) || !preg_match('/^[a-f0-9]{32}$/', (string) $visitor_key)) {
		return 'skip';
	}
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr(wp_unslash($_SERVER['HTTP_USER_AGENT']), 0, 500) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if (retails_vi_is_excluded() || retails_vi_is_bot($ua) || retails_vi_is_google_ip(retails_vi_client_ip(false))) {
		return 'skip';
	}
	$user_id = retails_vi_request_user_id();
	if ($user_id && retails_vi_setting('exclude_admins') && user_can($user_id, 'edit_posts')) {
		return 'skip';
	}
	$table = retails_vi_table('sessions');
	$robot = function_exists('retails_vi_automated_sql') ? retails_vi_automated_sql('s') : '0';
	$visit = $wpdb->get_row("SELECT s.*, {$robot} AS sfs_robot FROM {$table} s " . $wpdb->prepare('WHERE s.session_key = %s AND s.visitor_key = %s', $session_key, $visitor_key)); // phpcs:ignore WordPress.DB.PreparedSQL
	if (!$visit) {
		return 'unknown';
	}

	return (int) $visit->sfs_robot ? 'skip' : $visit;
}

/**
 * A screenshot's copy of the page.
 *
 * Multipart form: `meta` (JSON) and `copy` (the page, gzip or plain), and `css` (JSON of
 * shared styles) when the visitor's browser thinks the server may not have them yet.
 */
function retails_shots_rest_receive(WP_REST_Request $request)
{
	ignore_user_abort(true);
	$skip = new WP_REST_Response(null, 204);
	try {
		$settings = retails_shots_settings();
		$params = $request->get_body_params();
		$meta = json_decode((string) ($params['meta'] ?? ''), true);
		$files = $request->get_file_params();
		$file = $files['copy'] ?? null;
		if (!is_array($meta) || !is_array($file)) {
			return $skip;
		}
		$kind = (string) ($meta['kind'] ?? '');
		$type = sanitize_key((string) ($meta['type'] ?? ''));
		$ref = (string) ($meta['ref'] ?? '');
		$exit_types = array('exit_intent', 'hidden', 'closed', 'external');
		if (!preg_match('/^[a-f0-9]{24}$/', $ref) || !in_array($kind, array('error', 'exit'), true) || ('exit' === $kind) !== in_array($type, $exit_types, true) || !isset(retails_shots_types()[$type])) {
			return $skip;
		}
		if (('error' === $kind && !$settings['errors']) || ('exit' === $kind && !$settings['exits'])) {
			return $skip;
		}
		// Taken back before it arrived, or already saved (a retry).
		if (get_transient('sfs_shot_x_' . $ref) || retails_shots_find($ref)) {
			return new WP_REST_Response(array('ok' => 1), 200);
		}
		if (!retails_shots_allow('all', 600) || !retails_shots_allow('ip:' . retails_vi_client_ip(false), 60)) {
			return new WP_REST_Response(null, 429);
		}

		$enc = 'gzip' === ($meta['enc'] ?? '') ? 'gzip' : 'raw';
		$size = (int) ($file['size'] ?? 0);
		$tmp = (string) ($file['tmp_name'] ?? '');
		if (UPLOAD_ERR_OK !== (int) ($file['error'] ?? -1) || !$tmp || !is_uploaded_file($tmp) || $size < 100 || $size > ('gzip' === $enc ? RETAILS_SHOT_MAX_GZ : RETAILS_SHOT_MAX_RAW)) {
			return new WP_REST_Response(null, 413);
		}

		$visit = retails_shots_visit((string) ($meta['s'] ?? ''), (string) ($meta['v'] ?? ''));
		if ('unknown' === $visit) {
			// The visit's first page view is still on its way: the browser tries once more.
			return new WP_REST_Response(array('retry' => 1), 409);
		}
		if (!is_object($visit)) {
			return $skip;
		}

		$label = substr(sanitize_text_field((string) ($meta['label'] ?? '')), 0, 300);
		if ('error' === $kind) {
			if (!retails_shots_allow('err:' . $visit->session_key, RETAILS_SHOT_VISIT_ERRORS, 2 * HOUR_IN_SECONDS)) {
				return $skip;
			}
			// The same problem twice in one visit is one screenshot.
			$seen = get_transient('sfs_shot_d_' . $visit->session_key);
			$seen = is_array($seen) ? $seen : array();
			$hash = md5($type . '|' . strtolower(preg_replace('/\d+/', '#', $label)));
			if (in_array($hash, $seen, true)) {
				return new WP_REST_Response(array('ok' => 1), 200);
			}
			$seen[] = $hash;
			set_transient('sfs_shot_d_' . $visit->session_key, array_slice($seen, -40), 2 * HOUR_IN_SECONDS);
		} elseif (!retails_shots_allow('exit:' . $visit->session_key, RETAILS_SHOT_VISIT_EXITS, 2 * HOUR_IN_SECONDS)) {
			return $skip;
		}

		if (!empty($files['css']) && is_array($files['css'])) {
			retails_shots_store_css_upload($files['css']);
		}

		$post_id = retails_shots_store($visit, $meta, $kind, $type, $label, $ref, $tmp, $enc);
		if (is_wp_error($post_id)) {
			return new WP_REST_Response(null, 'wsh_bad' === $post_id->get_error_code() ? 400 : 503);
		}

		return new WP_REST_Response(array('ok' => 1), 201);
	} catch (Throwable $error) {
		return $skip;
	}
}

/**
 * Check and save a copy as a new screenshot.
 *
 * @return int|WP_Error Post ID.
 */
function retails_shots_store($visit, array $meta, $kind, $type, $label, $ref, $tmp, $enc)
{
	$data = file_get_contents($tmp); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ('gzip' === $enc) {
		$gz = (string) $data;
		$html = '' !== $gz ? @gzdecode($gz, RETAILS_SHOT_MAX_RAW) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
	} else {
		$html = (string) $data;
		$gz = gzencode($html, 6);
	}
	unset($data);
	if (false === $html || false === $gz || strlen($html) < 200 || !preg_match('/<(html|body)[\s>]/i', substr($html, 0, 8192))) {
		return new WP_Error('wsh_bad', 'The page copy was empty or damaged.');
	}
	if (function_exists('disk_free_space')) {
		$free = @disk_free_space(WP_CONTENT_DIR); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if (false !== $free && $free < 300 * MB_IN_BYTES) {
			return new WP_Error('wsh_disk', 'The disk is almost full.');
		}
	}

	$at = time() - max(0, min(600, (int) (($meta['age'] ?? 0) / 1000)));
	preg_match_all('/data-wsh-css="([a-f0-9]{20})"/', $html, $css_keys);
	$css = array_values(array_unique($css_keys[1]));
	unset($html);

	$page = esc_url_raw((string) ($meta['page'] ?? ''));
	$more = array();
	foreach (array_slice(is_array($meta['more'] ?? null) ? $meta['more'] : array(), 0, 5) as $item) {
		if (is_array($item) && !empty($item['label'])) {
			$more[] = array(
				'type' => isset(retails_shots_types()[sanitize_key((string) ($item['type'] ?? ''))]) ? sanitize_key((string) $item['type']) : 'js_error',
				'label' => substr(sanitize_text_field((string) $item['label']), 0, 300),
				'where' => substr(sanitize_text_field((string) ($item['where'] ?? '')), 0, 160),
			);
		}
	}
	$shot = array(
		'ref' => $ref,
		'dir' => gmdate('Ym', $at),
		'ext' => '',
		'kind' => $kind,
		'type' => $type,
		'label' => $label,
		'where' => substr(sanitize_text_field((string) ($meta['where'] ?? '')), 0, 160),
		'more' => $more,
		'page' => preg_match('#^https?://#i', $page) ? substr($page, 0, 2000) : '',
		'title' => substr(sanitize_text_field(html_entity_decode((string) ($meta['title'] ?? ''), ENT_QUOTES, 'UTF-8')), 0, 200),
		'w' => max(200, min(4000, (int) ($meta['w'] ?? 0))),
		'h' => max(200, min(4000, (int) ($meta['h'] ?? 0))),
		'sx' => max(0, min(100000, (int) ($meta['sx'] ?? 0))),
		'sy' => max(0, min(1000000, (int) ($meta['sy'] ?? 0))),
		'ph' => max(0, min(1000000, (int) ($meta['ph'] ?? 0))),
		'dpr' => max(1, min(4, (float) ($meta['dpr'] ?? 1))),
		'bytes' => strlen($gz),
		'css' => $css,
		'at' => $at,
		'session' => (string) $visit->session_key,
		'visitor' => (string) $visit->visitor_key,
		'info' => function_exists('retails_vi_snap_visitor_info') ? retails_vi_snap_visitor_info($visit) : array(),
	);

	$path = retails_shots_path($shot, 'html.gz');
	if (!$path || !retails_shots_prepare_dir(dirname($path)) || false === file_put_contents($path, $gz)) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error('wsh_disk', 'Could not save the page copy.');
	}
	// Styles still in use are kept by the clean-up.
	foreach ($css as $key) {
		$css_path = retails_shots_css_path($key);
		if ($css_path && file_exists($css_path)) {
			@touch($css_path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
	}

	$where = $shot['title'] ?: (string) wp_parse_url($shot['page'], PHP_URL_PATH);
	$title = 'error' === $kind ? 'Error: ' . ($label ?: retails_shots_types()[$type]) : 'Left the site: ' . ($where ?: 'page');
	$post_id = wp_insert_post(wp_slash(array(
		'post_type' => RETAILS_SHOT_TYPE,
		'post_status' => 'publish',
		'post_title' => wp_html_excerpt($title, 160, '…'),
		'post_name' => $ref,
		'post_content' => '',
		'post_author' => 0,
		'comment_status' => 'closed',
		'ping_status' => 'closed',
		'post_date' => wp_date('Y-m-d H:i:s', $at),
		'post_date_gmt' => gmdate('Y-m-d H:i:s', $at),
		'meta_input' => array_filter(array(
			'_shot' => $shot,
			'_shot_kind' => $kind,
			'_shot_session' => $shot['session'],
			'_shot_new' => 1,
			'_shot_open' => 'exit' === $kind ? 1 : 0,
		)),
	)), true);
	if (is_wp_error($post_id) || !$post_id) {
		wp_delete_file($path);

		return is_wp_error($post_id) ? $post_id : new WP_Error('wsh_disk', 'Could not save the screenshot.');
	}

	// A newer "left the site" copy of the same page replaces the one sent before it.
	if ('exit' === $kind && preg_match('/^[a-f0-9]{24}$/', (string) ($meta['prev'] ?? ''))) {
		$prev = retails_shots_find($meta['prev']);
		$prev_shot = $prev ? retails_shots_meta($prev) : array();
		if ($prev && 'exit' === ($prev_shot['kind'] ?? '') && ($prev_shot['session'] ?? '') === $shot['session']) {
			retails_shots_delete(array($prev));
		}
	}
	retails_shots_counts_changed();
	retails_shots_trim(50);

	return (int) $post_id;
}

/**
 * Shared styles: `{key: css}` JSON, each key being the first 20 hex characters of the
 * SHA-256 of its text (checked, so nobody can swap another page's styles).
 *
 * @return string[] Keys stored or already there.
 */
function retails_shots_store_css_upload(array $file)
{
	$tmp = (string) ($file['tmp_name'] ?? '');
	$size = (int) ($file['size'] ?? 0);
	if (UPLOAD_ERR_OK !== (int) ($file['error'] ?? -1) || !$tmp || !is_uploaded_file($tmp) || $size < 2 || $size > 4 * RETAILS_SHOT_MAX_CSS) {
		return array();
	}
	$texts = json_decode((string) file_get_contents($tmp), true); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if (!is_array($texts) || !retails_shots_prepare_dir(retails_shots_root() . '/css')) {
		return array();
	}
	$have = array();
	foreach (array_slice($texts, 0, 30, true) as $key => $css) {
		$path = retails_shots_css_path($key);
		if (!$path || !is_string($css) || strlen($css) > RETAILS_SHOT_MAX_CSS) {
			continue;
		}
		if (file_exists($path)) {
			$have[] = $key;
			continue;
		}
		if (substr(hash('sha256', $css), 0, 20) === $key && false !== file_put_contents($path, $css)) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			$have[] = $key;
		}
	}

	return $have;
}

/**
 * Shared styles: which of these does the server have (JSON body {s, v, keys}), or a
 * multipart upload of the ones it needs (meta + css).
 */
function retails_shots_rest_css(WP_REST_Request $request)
{
	ignore_user_abort(true);
	try {
		$files = $request->get_file_params();
		$upload = !empty($files['css']) && is_array($files['css']);
		$body = $upload ? json_decode((string) ($request->get_body_params()['meta'] ?? ''), true) : json_decode((string) $request->get_body(), true);
		if (!is_array($body) || !is_object(retails_shots_visit((string) ($body['s'] ?? ''), (string) ($body['v'] ?? '')))) {
			return new WP_REST_Response(null, 204);
		}
		if (!retails_shots_allow('css:' . retails_vi_client_ip(false), $upload ? 20 : 120)) {
			return new WP_REST_Response(null, 429);
		}
		if ($upload) {
			return new WP_REST_Response(array('have' => retails_shots_store_css_upload($files['css']), 'need' => array()), 200);
		}
		$have = array();
		$need = array();
		foreach (array_slice(is_array($body['keys'] ?? null) ? $body['keys'] : array(), 0, 30) as $key) {
			$path = retails_shots_css_path($key);
			if ($path) {
				if (file_exists($path)) {
					$have[] = $key;
				} else {
					$need[] = $key;
				}
			}
		}

		return new WP_REST_Response(array('have' => $have, 'need' => $need), 200);
	} catch (Throwable $error) {
		return new WP_REST_Response(null, 204);
	}
}

/**
 * The visitor did not leave after all: take the "left the site" screenshot back.
 * Body (text/plain JSON): {s, v, ref}.
 */
function retails_shots_rest_back(WP_REST_Request $request)
{
	ignore_user_abort(true);
	$done = new WP_REST_Response(null, 204);
	try {
		$body = json_decode((string) $request->get_body(), true);
		$ref = (string) ($body['ref'] ?? '');
		$session_key = (string) ($body['s'] ?? '');
		$visitor_key = (string) ($body['v'] ?? '');
		if (!preg_match('/^[a-f0-9]{24}$/', $ref) || !preg_match('/^[a-f0-9]{32}$/', $session_key) || !preg_match('/^[a-f0-9]{32}$/', $visitor_key)) {
			return $done;
		}
		if (!retails_shots_allow('back:' . retails_vi_client_ip(false), 200)) {
			return $done;
		}
		// A copy still on its way must not arrive after this.
		set_transient('sfs_shot_x_' . $ref, 1, 10 * MINUTE_IN_SECONDS);
		$post_id = retails_shots_find($ref);
		$shot = $post_id ? retails_shots_meta($post_id) : array();
		if ($post_id && 'exit' === ($shot['kind'] ?? '') && hash_equals((string) ($shot['session'] ?? ''), $session_key) && hash_equals((string) ($shot['visitor'] ?? ''), $visitor_key)) {
			retails_shots_delete(array($post_id));
		}
	} catch (Throwable $error) {
		// Ignore: the hourly check removes it if the visit carried on.
	}

	return $done;
}

/* ---------------------------------------------------------------------------
 * Clean-up
 * ------------------------------------------------------------------------ */

function retails_shots_schedule()
{
	if (!wp_next_scheduled('sfs_shots_hourly')) {
		wp_schedule_event(time() + 10 * MINUTE_IN_SECONDS, 'hourly', 'sfs_shots_hourly');
	}
	if (!wp_next_scheduled('sfs_shots_daily')) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'sfs_shots_daily');
	}
}
add_action('init', 'retails_shots_schedule');

/**
 * "Left the site" screenshots of visits that carried on (the visitor came back, or was
 * still browsing in another tab) are deleted; the rest are confirmed once the visit ends.
 */
function retails_shots_settle_exits()
{
	global $wpdb;
	if (!function_exists('retails_vi_table')) {
		return;
	}
	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT p.ID, p.post_date_gmt, s.meta_value AS session_key FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} o ON o.post_id = p.ID AND o.meta_key = '_shot_open'
		INNER JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_shot_session'
		WHERE p.post_type = %s AND p.post_date_gmt < %s ORDER BY p.ID LIMIT 400",
		RETAILS_SHOT_TYPE,
		gmdate('Y-m-d H:i:s', time() - 3 * MINUTE_IN_SECONDS)
	));
	if (!$rows) {
		return;
	}
	$keys = array_values(array_unique(array_filter(wp_list_pluck($rows, 'session_key'), static function ($key) {
		return (bool) preg_match('/^[a-f0-9]{32}$/', (string) $key);
	})));
	$last_seen = array();
	if ($keys) {
		$in = implode(', ', array_fill(0, count($keys), '%s'));
		foreach ((array) $wpdb->get_results($wpdb->prepare('SELECT session_key, last_seen FROM ' . retails_vi_table('sessions') . " WHERE session_key IN ({$in})", $keys)) as $visit) { // phpcs:ignore WordPress.DB.PreparedSQL
			$last_seen[$visit->session_key] = strtotime($visit->last_seen . ' UTC');
		}
	}
	$stayed = array();
	$final = array();
	foreach ($rows as $row) {
		$at = strtotime($row->post_date_gmt . ' UTC');
		$seen = $last_seen[$row->session_key] ?? 0;
		if ($seen && $seen > $at + 2 * MINUTE_IN_SECONDS) {
			$stayed[] = (int) $row->ID;
		} elseif (!$seen || $seen < time() - 30 * MINUTE_IN_SECONDS) {
			$final[] = (int) $row->ID;
		}
	}
	retails_shots_delete($stayed);
	if ($final) {
		$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_shot_open' AND post_id IN (" . implode(',', array_map('intval', $final)) . ')'); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ($final as $id) {
			wp_cache_delete($id, 'post_meta');
		}
	}
}
add_action('sfs_shots_hourly', 'retails_shots_settle_exits');

/**
 * Keep at most the maximum number of screenshots (oldest go first).
 */
function retails_shots_trim($slack = 0)
{
	global $wpdb;
	$max = retails_shots_settings()['max'];
	$count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", RETAILS_SHOT_TYPE));
	if ($count <= $max + $slack) {
		return;
	}
	retails_shots_delete(array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s ORDER BY post_date_gmt ASC, ID ASC LIMIT %d", RETAILS_SHOT_TYPE, min(1000, $count - $max)))));
}

function retails_shots_daily()
{
	global $wpdb;
	$settings = retails_shots_settings();
	retails_shots_settle_exits();
	$cutoff = gmdate('Y-m-d H:i:s', time() - $settings['keep_days'] * DAY_IN_SECONDS);
	for ($round = 0; $round < 20; $round++) {
		$ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_date_gmt < %s LIMIT 200", RETAILS_SHOT_TYPE, $cutoff)));
		if (!$ids || !retails_shots_delete($ids)) {
			break;
		}
	}
	retails_shots_trim();

	// Files no screenshot points to (a failed save), empty month folders, unused styles.
	$root = retails_shots_root();
	foreach ((array) glob($root . '/[0-9][0-9][0-9][0-9][0-9][0-9]', GLOB_ONLYDIR) as $dir) {
		$refs = array();
		foreach ((array) glob($dir . '/*.*') as $file) {
			if (filemtime($file) < time() - HOUR_IN_SECONDS && preg_match('/^([a-f0-9]{24})\./', basename($file), $match)) {
				$refs[$match[1]][] = $file;
			}
		}
		foreach (array_chunk(array_keys($refs), 200) as $chunk) {
			$in = implode(', ', array_fill(0, count($chunk), '%s'));
			$known = array_flip((array) $wpdb->get_col($wpdb->prepare("SELECT post_name FROM {$wpdb->posts} WHERE post_type = %s AND post_name IN ({$in})", array_merge(array(RETAILS_SHOT_TYPE), $chunk)))); // phpcs:ignore WordPress.DB.PreparedSQL
			foreach ($chunk as $ref) {
				if (!isset($known[$ref])) {
					array_map('wp_delete_file', $refs[$ref]);
				}
			}
		}
		if (!glob($dir . '/*')) {
			@rmdir($dir); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
		}
	}
	foreach ((array) glob($root . '/css/*.css') as $file) {
		if (filemtime($file) < time() - ($settings['keep_days'] + 7) * DAY_IN_SECONDS) {
			wp_delete_file($file);
		}
	}
}
add_action('sfs_shots_daily', 'retails_shots_daily');

/**
 * "Delete all visitor data" (Visitor Insights → Settings) deletes every screenshot too.
 * Runs before that handler, with the same checks.
 */
function retails_shots_purge_all()
{
	global $wpdb;
	if (!current_user_can('manage_options') || !wp_verify_nonce(isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '', 'retails_vi_purge_all')) {
		return;
	}
	for ($round = 0; $round < 100; $round++) {
		$ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 500", RETAILS_SHOT_TYPE)));
		if (!$ids || !retails_shots_delete($ids)) {
			break;
		}
	}
	foreach ((array) glob(retails_shots_root() . '/css/*.css') as $file) {
		wp_delete_file($file);
	}
}
add_action('admin_post_retails_vi_purge_all', 'retails_shots_purge_all', 5);

/* ---------------------------------------------------------------------------
 * Admin: list
 * ------------------------------------------------------------------------ */

function retails_shots_file_url($post_id, array $shot, $download = false)
{
	return add_query_arg(array_filter(array(
		'action' => 'sfs_shot_file',
		'id' => (int) $post_id,
		'v' => substr(md5((string) ($shot['ext'] ?? '') . ($shot['iw'] ?? '')), 0, 8),
		'dl' => $download ? 1 : 0,
	)), admin_url('admin-ajax.php'));
}

/**
 * What the admin script needs to draw or show a screenshot.
 */
function retails_shots_js_data($post_id, array $shot)
{
	return array(
		'id' => (int) $post_id,
		'w' => (int) ($shot['w'] ?? 0),
		'h' => (int) ($shot['h'] ?? 0),
		'sx' => (int) ($shot['sx'] ?? 0),
		'sy' => (int) ($shot['sy'] ?? 0),
		'ph' => (int) ($shot['ph'] ?? 0),
		'dpr' => (float) ($shot['dpr'] ?? 1),
		'page' => (string) ($shot['page'] ?? ''),
		'url' => !empty($shot['ext']) ? retails_shots_file_url($post_id, $shot) : '',
	);
}

function retails_shots_columns($columns)
{
	return array(
		'cb' => $columns['cb'] ?? '<input type="checkbox" />',
		'shot' => 'Screenshot',
		'title' => 'What happened',
		'shot_page' => 'Page',
		'shot_visitor' => 'Visitor',
		'shot_device' => 'Device',
		'date' => 'Date',
	);
}
add_filter('manage_' . RETAILS_SHOT_TYPE . '_posts_columns', 'retails_shots_columns');

function retails_shots_column($column, $post_id)
{
	$shot = retails_shots_meta($post_id);
	$info = is_array($shot['info'] ?? null) ? $shot['info'] : array();
	switch ($column) {
		case 'shot':
			$data = retails_shots_js_data($post_id, $shot);
			echo '<a class="sfs-shot-thumb" href="' . esc_url(get_edit_post_link($post_id)) . '">';
			if ($data['url']) {
				echo '<img src="' . esc_url($data['url']) . '" alt="" loading="lazy">';
			} else {
				echo '<span class="sfs-shot-ph" data-shot-draw="' . esc_attr(wp_json_encode($data)) . '"><span class="dashicons dashicons-format-image"></span><span>Drawing…</span></span>';
			}
			echo '</a>';
			break;
		case 'shot_page':
			$path = (string) wp_parse_url((string) ($shot['page'] ?? ''), PHP_URL_PATH);
			$query = (string) wp_parse_url((string) ($shot['page'] ?? ''), PHP_URL_QUERY);
			if (!empty($shot['page'])) {
				printf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($shot['page']), esc_html(retails_shots_short($path . ($query ? '?' . $query : ''), 46)));
			}
			if (!empty($shot['title'])) {
				echo '<br><span class="sfs-shot-muted">' . esc_html(retails_shots_short($shot['title'], 60)) . '</span>';
			}
			break;
		case 'shot_visitor':
			$who = array_filter(array($info['name'] ?? '', $info['email'] ?? ''));
			echo $who ? '<strong>' . esc_html(implode(' · ', $who)) . '</strong><br>' : '';
			echo esc_html(implode(' · ', array_filter(array($info['location'] ?? '', $info['ip'] ?? ''))));
			if (!empty($info['source'])) {
				echo '<br><span class="sfs-shot-muted">' . esc_html(retails_shots_short($info['source'], 50)) . '</span>';
			}
			break;
		case 'shot_device':
			echo esc_html((string) ($info['device'] ?? ''));
			if (!empty($shot['w'])) {
				echo '<br><span class="sfs-shot-muted">' . esc_html($shot['w'] . ' × ' . $shot['h']) . '</span>';
			}
			break;
	}
}
add_action('manage_' . RETAILS_SHOT_TYPE . '_posts_custom_column', 'retails_shots_column', 10, 2);

function retails_shots_short($text, $length)
{
	$text = (string) $text;

	return function_exists('mb_strimwidth') ? mb_strimwidth($text, 0, $length, '…', 'UTF-8') : (strlen($text) > $length ? substr($text, 0, $length - 1) . '…' : $text);
}

/** "New" and "may still be on the site" next to the title. */
function retails_shots_states($states, $post)
{
	if (RETAILS_SHOT_TYPE !== $post->post_type) {
		return $states;
	}
	$states = array();
	if (get_post_meta($post->ID, '_shot_new', true)) {
		$states['sfs_new'] = 'New';
	}
	if (get_post_meta($post->ID, '_shot_open', true)) {
		$states['sfs_open'] = 'Visit still going: removed if they carry on';
	}

	return $states;
}
add_filter('display_post_states', 'retails_shots_states', 10, 2);

function retails_shots_row_actions($actions, $post)
{
	if (RETAILS_SHOT_TYPE !== $post->post_type) {
		return $actions;
	}

	return array(
		'view' => '<a href="' . esc_url(get_edit_post_link($post->ID)) . '">View</a>',
		'delete' => '<a href="' . esc_url(get_delete_post_link($post->ID, '', true)) . '" class="submitdelete" onclick="return confirm(\'Delete this screenshot?\')">Delete</a>',
	);
}
add_filter('post_row_actions', 'retails_shots_row_actions', 10, 2);

function retails_shots_bulk_actions()
{
	return array('delete' => 'Delete permanently');
}
add_filter('bulk_actions-edit-' . RETAILS_SHOT_TYPE, 'retails_shots_bulk_actions');

function retails_shots_bulk_messages($messages, $counts)
{
	$messages[RETAILS_SHOT_TYPE] = array(
		'deleted' => _n('%s screenshot deleted.', '%s screenshots deleted.', (int) $counts['deleted']),
		'trashed' => _n('%s screenshot deleted.', '%s screenshots deleted.', (int) $counts['trashed']),
		'updated' => _n('%s screenshot updated.', '%s screenshots updated.', (int) $counts['updated']),
		'locked' => _n('%s screenshot not updated, somebody is editing it.', '%s screenshots not updated, somebody is editing them.', (int) $counts['locked']),
		'untrashed' => '',
	);

	return $messages;
}
add_filter('bulk_post_updated_messages', 'retails_shots_bulk_messages', 10, 2);

/** "Errors / Left the site" filter above the list. */
function retails_shots_filters($post_type)
{
	if (RETAILS_SHOT_TYPE !== $post_type) {
		return;
	}
	$current = isset($_GET['shot_kind']) ? sanitize_key(wp_unslash($_GET['shot_kind'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<label class="screen-reader-text" for="sfs-shot-kind">Filter by what happened</label><select name="shot_kind" id="sfs-shot-kind">';
	foreach (array('' => 'Errors and leaving', 'error' => 'Errors only', 'exit' => 'Left the site only') as $value => $label) {
		printf('<option value="%s"%s>%s</option>', esc_attr($value), selected($current, $value, false), esc_html($label));
	}
	echo '</select>';
	if (!empty($_GET['shot_session'])) { // phpcs:ignore WordPress.Security.NonceVerification
		printf('<input type="hidden" name="shot_session" value="%s">', esc_attr(sanitize_key(wp_unslash($_GET['shot_session'])))); // phpcs:ignore WordPress.Security.NonceVerification
	}
}
add_action('restrict_manage_posts', 'retails_shots_filters');

function retails_shots_list_query($query)
{
	if (!is_admin() || !$query->is_main_query() || RETAILS_SHOT_TYPE !== $query->get('post_type')) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification
	$meta = array();
	$kind = isset($_GET['shot_kind']) ? sanitize_key(wp_unslash($_GET['shot_kind'])) : '';
	if (in_array($kind, array('error', 'exit'), true)) {
		$meta[] = array('key' => '_shot_kind', 'value' => $kind);
	}
	$session = isset($_GET['shot_session']) ? sanitize_key(wp_unslash($_GET['shot_session'])) : '';
	if (preg_match('/^[a-f0-9]{32}$/', $session)) {
		$meta[] = array('key' => '_shot_session', 'value' => $session);
	}
	// phpcs:enable
	if ($meta) {
		$query->set('meta_query', $meta);
	}
}
add_action('pre_get_posts', 'retails_shots_list_query');

/** The list is up to date when it opens: settle "left the site" screenshots first. */
function retails_shots_load_list()
{
	if (RETAILS_SHOT_TYPE === (get_current_screen()->post_type ?? '')) {
		retails_shots_settle_exits();
	}
}
add_action('load-edit.php', 'retails_shots_load_list');

/** How many screenshots nobody opened yet, on the menu. */
function retails_shots_menu_count()
{
	global $menu, $wpdb;
	if (!current_user_can('manage_options') || !is_array($menu)) {
		return;
	}
	$count = get_transient('sfs_shots_new_count');
	if (false === $count) {
		$count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_shot_new' WHERE p.post_type = %s", RETAILS_SHOT_TYPE));
		set_transient('sfs_shots_new_count', $count, 5 * MINUTE_IN_SECONDS);
	}
	if ((int) $count < 1) {
		return;
	}
	foreach ($menu as $index => $item) {
		if (isset($item[2]) && 'edit.php?post_type=' . RETAILS_SHOT_TYPE === $item[2]) {
			$menu[$index][0] .= sprintf(' <span class="awaiting-mod count-%1$d"><span class="pending-count">%2$s</span></span>', (int) $count, esc_html(number_format_i18n(min(999, (int) $count))));
			break;
		}
	}
}
add_action('admin_menu', 'retails_shots_menu_count', 999);

/** Screenshots sit right under Visitor Insights in the menu. */
function retails_shots_menu_order($order)
{
	$ours = 'edit.php?post_type=' . RETAILS_SHOT_TYPE;
	if (!is_array($order) || !in_array($ours, $order, true) || !in_array('retails-insights', $order, true)) {
		return $order;
	}
	$order = array_values(array_diff($order, array($ours)));
	array_splice($order, array_search('retails-insights', $order, true) + 1, 0, array($ours));

	return $order;
}
add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', 'retails_shots_menu_order', 20);

/* ---------------------------------------------------------------------------
 * Admin: one screenshot
 * ------------------------------------------------------------------------ */

function retails_shots_meta_boxes($post)
{
	remove_meta_box('submitdiv', RETAILS_SHOT_TYPE, 'side');
	remove_meta_box('slugdiv', RETAILS_SHOT_TYPE, 'normal');
	add_meta_box('sfs-shot-view', esc_html(get_the_title($post)), 'retails_shots_box_view', RETAILS_SHOT_TYPE, 'normal', 'high');
	add_meta_box('sfs-shot-actions', 'Screenshot', 'retails_shots_box_actions', RETAILS_SHOT_TYPE, 'side', 'high');
	add_meta_box('sfs-shot-visitor', 'Visitor', 'retails_shots_box_visitor', RETAILS_SHOT_TYPE, 'side', 'default');
}
add_action('add_meta_boxes_' . RETAILS_SHOT_TYPE, 'retails_shots_meta_boxes');

/** Opening a screenshot marks it seen. */
function retails_shots_load_post()
{
	$post_id = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ($post_id && RETAILS_SHOT_TYPE === get_post_type($post_id) && get_post_meta($post_id, '_shot_new', true)) {
		delete_post_meta($post_id, '_shot_new');
		retails_shots_counts_changed();
	}
}
add_action('load-post.php', 'retails_shots_load_post');

function retails_shots_box_view($post)
{
	$shot = retails_shots_meta($post->ID);
	$types = retails_shots_types();
	$data = retails_shots_js_data($post->ID, $shot);
	$problems = array_merge(array(array('type' => $shot['type'] ?? '', 'label' => $shot['label'] ?? '', 'where' => $shot['where'] ?? '')), is_array($shot['more'] ?? null) ? $shot['more'] : array());
	?>
	<div class="sfs-shot-what">
		<?php if ('exit' === ($shot['kind'] ?? '')) : ?>
			<p><span class="sfs-shot-tag is-exit">Left the site</span> <?php echo esc_html($types[$shot['type'] ?? ''] ?? ''); ?><?php echo get_post_meta($post->ID, '_shot_open', true) ? ' <span class="sfs-shot-muted">(their visit is still going; this is removed if they carry on)</span>' : ''; ?></p>
		<?php else : ?>
			<?php foreach ($problems as $problem) : ?>
				<p>
					<span class="sfs-shot-tag is-error"><?php echo esc_html($types[$problem['type']] ?? 'Error'); ?></span>
					<?php echo esc_html((string) $problem['label']); ?>
					<?php echo !empty($problem['where']) ? '<code>' . esc_html((string) $problem['where']) . '</code>' : ''; ?>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<div class="sfs-shot-stage" data-shot-view="<?php echo esc_attr(wp_json_encode($data)); ?>">
		<?php if ($data['url']) : ?>
			<a href="<?php echo esc_url($data['url']); ?>" target="_blank" rel="noopener" class="sfs-shot-picture"><img src="<?php echo esc_url($data['url']); ?>" alt="Screenshot of <?php echo esc_attr($shot['title'] ?? ''); ?>"></a>
		<?php else : ?>
			<p class="sfs-shot-status"><span class="spinner is-active"></span> Drawing the visitor's screen…</p>
		<?php endif; ?>
	</div>
	<p class="sfs-shot-tools">
		<button type="button" class="button" data-shot-whole>Scroll the whole page</button>
		<button type="button" class="button-link" data-shot-redraw>Draw again</button>
		<span class="sfs-shot-muted">The picture shows the visitor's window (<?php echo esc_html(($shot['w'] ?? '?') . ' × ' . ($shot['h'] ?? '?')); ?>). Password and card fields are hidden; payment and map boxes show grey.</span>
	</p>
	<?php
}

function retails_shots_box_actions($post)
{
	$shot = retails_shots_meta($post->ID);
	$at = (int) ($shot['at'] ?? strtotime($post->post_date_gmt . ' UTC'));
	?>
	<p><strong><?php echo esc_html(wp_date('M j, Y · g:i:s a', $at)); ?></strong></p>
	<?php if (!empty($shot['page'])) : ?>
		<p class="sfs-shot-break"><a href="<?php echo esc_url($shot['page']); ?>" target="_blank" rel="noopener"><?php echo esc_html($shot['title'] ?: $shot['page']); ?></a></p>
	<?php endif; ?>
	<p class="sfs-shot-muted">Window <?php echo esc_html(($shot['w'] ?? '?') . ' × ' . ($shot['h'] ?? '?')); ?><?php echo !empty($shot['ph']) && $shot['ph'] > ($shot['h'] ?? 0) ? esc_html(sprintf(', scrolled %d%% down', min(100, round(100 * ($shot['sy'] ?? 0) / max(1, $shot['ph'] - $shot['h']))))) : ''; ?></p>
	<p class="sfs-shot-actions">
		<?php if (!empty($shot['ext'])) : ?>
			<a class="button" href="<?php echo esc_url(retails_shots_file_url($post->ID, $shot, true)); ?>"><span class="dashicons dashicons-download"></span> Download</a>
		<?php endif; ?>
		<a class="button-link submitdelete" href="<?php echo esc_url(get_delete_post_link($post->ID, '', true)); ?>" onclick="return confirm('Delete this screenshot?')">Delete</a>
	</p>
	<p><a href="<?php echo esc_url(admin_url('edit.php?post_type=' . RETAILS_SHOT_TYPE)); ?>">← All screenshots</a></p>
	<?php
}

function retails_shots_box_visitor($post)
{
	$shot = retails_shots_meta($post->ID);
	$info = is_array($shot['info'] ?? null) ? $shot['info'] : array();
	$labels = array(
		'name' => 'Name', 'email' => 'Email', 'customer' => 'Customer', 'ip' => 'IP address', 'location' => 'Location',
		'timezone' => 'Time zone', 'language' => 'Language', 'device' => 'Device', 'screen' => 'Screen', 'source' => 'Source',
		'campaign' => 'Campaign', 'keyword' => 'Keyword', 'ad_network' => 'Ad shown on', 'referrer' => 'Referrer',
		'landing' => 'Landing page', 'visit_started' => 'Visit started', 'pages' => 'Pages viewed', 'returning' => 'Visitor',
	);
	echo '<dl class="sfs-shot-details">';
	foreach ($labels as $key => $label) {
		if (!array_key_exists($key, $info) || '' === $info[$key]) {
			continue;
		}
		$value = $info[$key];
		if ('returning' === $key) {
			$value = $value ? 'Returning visitor' : 'First visit';
		} elseif ('visit_started' === $key) {
			$value = wp_date('M j, Y g:i a', (int) $value);
		} elseif ('landing' === $key && is_string($value) && '' !== $value && '/' === $value[0]) {
			$value = home_url($value);
		}
		$value = (string) $value;
		echo '<dt>' . esc_html($label) . '</dt><dd>' . (preg_match('#^https?://#', $value) ? '<a href="' . esc_url($value) . '" target="_blank" rel="noopener">' . esc_html(retails_shots_short($value, 60)) . '</a>' : esc_html($value)) . '</dd>';
	}
	echo '</dl>';
	$visit = (!empty($shot['session']) && function_exists('retails_vi_find_session')) ? retails_vi_find_session($shot['session']) : null;
	echo '<p>';
	if ($visit && function_exists('retails_vi_url')) {
		echo '<a class="button" href="' . esc_url(retails_vi_url('retails-insights-visitors', array('session' => (int) $visit->id))) . '">Visit journey →</a> ';
	}
	if (!empty($shot['session'])) {
		echo '<a href="' . esc_url(add_query_arg(array('post_type' => RETAILS_SHOT_TYPE, 'shot_session' => $shot['session']), admin_url('edit.php'))) . '">All screenshots of this visit</a>';
	}
	echo '</p>';
}

/* ---------------------------------------------------------------------------
 * Admin: drawing, files, assets
 * ------------------------------------------------------------------------ */

function retails_shots_ajax_post()
{
	if (!current_user_can('manage_options') || !check_ajax_referer('sfs_shots', 'nonce', false)) {
		wp_send_json_error('Not allowed. Reload the page and try again.', 403);
	}
	$post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
	$shot = (RETAILS_SHOT_TYPE === get_post_type($post_id)) ? retails_shots_meta($post_id) : array();
	if (!$shot) {
		wp_send_json_error('That screenshot was not found.', 404);
	}

	return array($post_id, $shot);
}

/** The page copy (with its shared styles put back), for the admin's browser to draw. */
function retails_shots_ajax_copy()
{
	list(, $shot) = retails_shots_ajax_post();
	$path = retails_shots_path($shot, 'html.gz');
	$html = ($path && is_readable($path)) ? @gzdecode((string) file_get_contents($path)) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
	if (false === $html) {
		wp_send_json_error('The page copy is gone.', 404);
	}
	$html = preg_replace_callback('/<style([^>]*?)\sdata-wsh-css="([a-f0-9]{20})"([^>]*)><\/style>/i', static function ($match) {
		$path = retails_shots_css_path($match[2]);
		$css = ($path && is_readable($path)) ? (string) file_get_contents($path) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions

		return '<style' . $match[1] . $match[3] . '>' . str_ireplace('</style', '<\/style', $css) . '</style>';
	}, $html);
	wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_sfs_shot_copy', 'retails_shots_ajax_copy');

/** The picture the admin's browser drew. */
function retails_shots_ajax_save()
{
	list($post_id, $shot) = retails_shots_ajax_post();
	$image = isset($_POST['image']) ? (string) wp_unslash($_POST['image']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if (!preg_match('#^data:image/(jpeg|png|webp);base64,#', $image, $match)) {
		wp_send_json_error('No picture was sent.', 400);
	}
	$bytes = base64_decode(substr($image, strlen($match[0])), true); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	if (false === $bytes || strlen($bytes) < 100 || strlen($bytes) > RETAILS_SHOT_MAX_IMAGE) {
		wp_send_json_error('The picture was empty or too large.', 400);
	}
	$info = @getimagesizefromstring($bytes); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$ext = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp')[is_array($info) ? (string) $info['mime'] : ''] ?? '';
	$path = $ext ? retails_shots_path($shot, $ext) : '';
	if (!$path) {
		wp_send_json_error('The picture could not be read.', 400);
	}
	if (!empty($shot['ext']) && $shot['ext'] !== $ext) {
		$old = retails_shots_path($shot, $shot['ext']);
		if ($old) {
			wp_delete_file($old);
		}
	}
	if (!retails_shots_prepare_dir(dirname($path)) || false === file_put_contents($path, $bytes)) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		wp_send_json_error('Could not save the picture (disk full?).', 500);
	}
	$shot['ext'] = $ext;
	$shot['iw'] = (int) $info[0];
	$shot['ih'] = (int) $info[1];
	update_post_meta($post_id, '_shot', $shot);
	wp_send_json_success(array('url' => retails_shots_file_url($post_id, $shot), 'download' => retails_shots_file_url($post_id, $shot, true)));
}
add_action('wp_ajax_sfs_shot_save', 'retails_shots_ajax_save');

/**
 * Stream a picture (admins only). Reading needs no nonce: it changes nothing, and the
 * page shows it in <img>.
 */
function retails_shots_ajax_file()
{
	$post_id = isset($_GET['id']) ? absint($_GET['id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$shot = (current_user_can('manage_options') && RETAILS_SHOT_TYPE === get_post_type($post_id)) ? retails_shots_meta($post_id) : array();
	$types = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
	$ext = isset($types[$shot['ext'] ?? '']) ? $shot['ext'] : '';
	$path = $ext ? retails_shots_path($shot, $ext) : '';
	if (!$path || !is_readable($path)) {
		status_header(current_user_can('manage_options') ? 404 : 403);
		exit;
	}
	$name = sanitize_file_name('screenshot-' . wp_date('Y-m-d-His', (int) ($shot['at'] ?? time())) . '-' . ($shot['kind'] ?? 'page') . '.' . $ext);
	header('Content-Type: ' . $types[$ext]);
	header('Content-Length: ' . filesize($path));
	header('Content-Disposition: ' . (empty($_GET['dl']) ? 'inline' : 'attachment') . '; filename="' . $name . '"'); // phpcs:ignore WordPress.Security.NonceVerification
	header('Cache-Control: private, max-age=86400');
	header('X-Content-Type-Options: nosniff');
	header('X-Robots-Tag: noindex');
	readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action('wp_ajax_sfs_shot_file', 'retails_shots_ajax_file');

function retails_shots_admin_assets($hook)
{
	$screen = get_current_screen();
	if (!$screen || RETAILS_SHOT_TYPE !== $screen->post_type || !in_array($screen->base, array('edit', 'post'), true)) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('sfs-shots-admin', $uri . '/css/screenshots-admin.css', array(), (string) filemtime($dir . '/css/screenshots-admin.css'));
	wp_enqueue_script('sfs-shots-admin', $uri . '/js/screenshots-admin.js', array(), (string) filemtime($dir . '/js/screenshots-admin.js'), true);
	$scripts = function_exists('retails_vi_snap_scripts') ? retails_vi_snap_scripts() : null;
	wp_localize_script('sfs-shots-admin', 'sfsShots', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('sfs_shots'),
		'render' => $scripts ? $scripts['render'] : '',
		'home' => home_url('/'),
	));
}
add_action('admin_enqueue_scripts', 'retails_shots_admin_assets');

/* ---------------------------------------------------------------------------
 * Admin: settings
 * ------------------------------------------------------------------------ */

function retails_shots_settings_menu()
{
	add_submenu_page('edit.php?post_type=' . RETAILS_SHOT_TYPE, 'Screenshot settings', 'Settings', 'manage_options', 'sfs-shots-settings', 'retails_shots_settings_page');
}
add_action('admin_menu', 'retails_shots_settings_menu');

function retails_shots_settings_save()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.', 403);
	}
	check_admin_referer('sfs_shots_settings');
	update_option(RETAILS_SHOT_OPTION, array(
		'errors' => !empty($_POST['errors']),
		'exits' => !empty($_POST['exits']),
		'keep_days' => max(1, min(180, isset($_POST['keep_days']) ? absint($_POST['keep_days']) : 14)),
		'max' => max(100, min(10000, isset($_POST['max']) ? absint($_POST['max']) : 1500)),
	), true);
	retails_shots_trim();
	wp_safe_redirect(add_query_arg(array('post_type' => RETAILS_SHOT_TYPE, 'page' => 'sfs-shots-settings', 'saved' => 1), admin_url('edit.php')));
	exit;
}
add_action('admin_post_sfs_shots_settings', 'retails_shots_settings_save');

function retails_shots_settings_page()
{
	global $wpdb;
	$settings = retails_shots_settings();
	$count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", RETAILS_SHOT_TYPE));
	$bytes = 0;
	foreach (array_merge((array) glob(retails_shots_root() . '/*/*.*'), (array) glob(retails_shots_root() . '/css/*.css')) as $file) {
		$bytes += (int) @filesize($file); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	$can_draw = function_exists('retails_vi_snap_scripts') && retails_vi_snap_scripts();
	$tracking = function_exists('retails_vi_setting') && retails_vi_setting('enabled');
	?>
	<div class="wrap">
		<h1>Screenshot settings</h1>
		<?php if (!empty($_GET['saved'])) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Saved. Clear the page cache so every page picks up the change.</p></div>
		<?php endif; ?>
		<?php if (!$tracking) : ?>
			<div class="notice notice-warning"><p>Visitor Insights tracking is off, so no screenshots are taken. Turn it on in Visitor Insights → Settings.</p></div>
		<?php endif; ?>
		<?php if (!$can_draw) : ?>
			<div class="notice notice-warning"><p>The SFS Live Chat plugin (a version with page screenshots) is not active, so screenshots can't be drawn into pictures. They can still be viewed with "Scroll the whole page".</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="sfs_shots_settings">
			<?php wp_nonce_field('sfs_shots_settings'); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Take a screenshot when</th>
					<td>
						<label><input type="checkbox" name="errors" value="1" <?php checked($settings['errors']); ?>> A visitor runs into an error</label>
						<p class="description">Script errors, failed price, cart, upload and payment requests, error messages on the page, form checks, server errors and "page not found".</p>
						<br>
						<label><input type="checkbox" name="exits" value="1" <?php checked($settings['exits']); ?>> A visitor leaves the site</label>
						<p class="description">Closing the tab, going to another site, or switching away. Taken back when the visitor turns out to be still on the site.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sfs-shots-keep">Keep screenshots for</label></th>
					<td><input type="number" min="1" max="180" id="sfs-shots-keep" name="keep_days" value="<?php echo esc_attr($settings['keep_days']); ?>" class="small-text"> days</td>
				</tr>
				<tr>
					<th scope="row"><label for="sfs-shots-max">Keep at most</label></th>
					<td><input type="number" min="100" max="10000" step="50" id="sfs-shots-max" name="max" value="<?php echo esc_attr($settings['max']); ?>" class="small-text"> screenshots <p class="description">The oldest are deleted first. Now: <?php echo esc_html(number_format_i18n($count)); ?> screenshots, <?php echo esc_html(size_format($bytes, 1) ?: '0 B'); ?> on disk.</p></td>
				</tr>
			</table>
			<p class="description">Only visits that Visitor Insights records get screenshots: staff browsers, robots and visitors who opted out never do. Password and card fields are hidden in every copy.</p>
			<?php submit_button('Save settings'); ?>
		</form>
	</div>
	<?php
}
