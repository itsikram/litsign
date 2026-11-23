<?php
/**
 * Visitor Insights: screenshots of what a visitor is looking at, saved with their visit.
 *
 * 1. "Take screenshot" (Live tab or a visit) asks that visit's browser for its page. The
 *    request sits in an autoloaded option, so ordinary tracker pings cost no extra query.
 * 2. The visitor's next tracker ping (js/visitor-tracker.js, every 15 s while they move,
 *    scroll or click) answers {snap: <request>}. The page loads Live Chat's snap.js and
 *    posts a copy of itself: HTML, typed values, canvas drawings, scroll spots. Password and
 *    card fields are masked and the chat window is left out. Nothing is drawn on the
 *    visitor's page, so it never pauses, flashes or scrolls, and nothing is shown to them.
 * 3. The admin's browser rebuilds the copy in a sandboxed frame at the visitor's window
 *    size, draws it into a JPEG (Live Chat's snap-render.js) and saves it here
 *    (js/visitor-snaps-admin.js).
 *
 * Each screenshot is saved with the visitor's details at that moment (name or email when
 * known, IP, location, device, source, campaign, keyword, page). The visit shows them next
 * to the picture, and "Download with details" stamps them under it.
 *
 * Files: uploads/wholesale-vi-snaps/<visit key>/<name>.html.gz (copy), .jpg (picture) and
 * .json (details), behind a deny-all .htaccess and served only to admins. They go with the
 * visit: the daily clean-up removes them once the visit passes the retention period, and
 * "Delete all visitor data" removes them all. Folders use the visit's random key, not its
 * ID, because database copies between the live and local sites renumber visits.
 *
 * Visitors who opted out of Visitor Insights (sso_vi_off), staff browsers and robots are
 * never tracked, so they are never asked.
 */

defined('ABSPATH') || exit;

const WHOLESALE_VI_SNAP_OPTION = 'wholesale_vi_snap_requests';
// How long a request waits for the visitor's browser (they may be away from the screen).
const WHOLESALE_VI_SNAP_TTL = 600;
// A page that was asked but never answered (closed, navigated away): ask the next one.
const WHOLESALE_VI_SNAP_RESEND = 45;
const WHOLESALE_VI_SNAP_KEEP = 30;
const WHOLESALE_VI_SNAP_MAX_GZ = 6291456;
const WHOLESALE_VI_SNAP_MAX_RAW = 20971520;
const WHOLESALE_VI_SNAP_MAX_IMAGE = 4194304;

/* ---------------------------------------------------------------------------
 * Scripts and storage
 * ------------------------------------------------------------------------ */

/**
 * Live Chat's page copier (runs on the visitor's page) and drawer (runs in the admin), or
 * null when Live Chat is not active or is a version without page screenshots.
 */
function wholesale_vi_snap_scripts()
{
	static $scripts = null;
	if (null === $scripts) {
		$scripts = array();
		if (defined('SFS_CHAT_DIR') && defined('SFS_CHAT_URL')) {
			foreach (array('capture' => 'snap.js', 'render' => 'snap-render.js') as $key => $file) {
				$path = SFS_CHAT_DIR . 'assets/js/' . $file;
				if (!is_readable($path)) {
					$scripts = array();
					break;
				}
				$scripts[$key] = SFS_CHAT_URL . 'assets/js/' . $file . '?ver=' . filemtime($path);
			}
		}
	}

	return $scripts ?: null;
}

function wholesale_vi_snap_root()
{
	$uploads = wp_upload_dir(null, false);

	return trailingslashit($uploads['basedir']) . 'wholesale-vi-snaps';
}

/**
 * Folder of one visit's screenshots ('' for a malformed key).
 */
function wholesale_vi_snap_dir($session_key)
{
	return preg_match('/^[a-f0-9]{32}$/', (string) $session_key) ? wholesale_vi_snap_root() . '/' . $session_key : '';
}

function wholesale_vi_snap_prepare_root()
{
	$root = wholesale_vi_snap_root();
	if (!wp_mkdir_p($root)) {
		return false;
	}
	if (!file_exists($root . '/.htaccess')) {
		// Apache 2.4 + 2.2; nginx ignores it, so every name is also random.
		file_put_contents($root . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents($root . '/index.php', "<?php\n// Silence is golden.\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents($root . '/web.config', '<?xml version="1.0"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>'); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	return true;
}

function wholesale_vi_snap_clean_name($name)
{
	return preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));
}

function wholesale_vi_snap_read_meta($session_key, $name)
{
	$name = wholesale_vi_snap_clean_name($name);
	$dir = wholesale_vi_snap_dir($session_key);
	$file = $dir . '/' . $name . '.json';
	$meta = ('' !== $name && $dir && is_readable($file)) ? json_decode((string) file_get_contents($file), true) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions

	return is_array($meta) && ($meta['name'] ?? '') === $name ? $meta : null;
}

function wholesale_vi_snap_write_meta($session_key, array $meta)
{
	return false !== file_put_contents(wholesale_vi_snap_dir($session_key) . '/' . $meta['name'] . '.json', wp_json_encode($meta)); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * A visit's screenshots (details only), newest first.
 */
function wholesale_vi_snap_metas($session_key)
{
	$dir = wholesale_vi_snap_dir($session_key);
	if (!$dir || !is_dir($dir)) {
		return array();
	}
	$out = array();
	foreach ((array) glob($dir . '/*.json') as $file) {
		$meta = json_decode((string) file_get_contents($file), true); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if (is_array($meta) && !empty($meta['name'])) {
			$out[] = $meta;
		}
	}
	usort($out, static function ($a, $b) {
		return strcmp((string) $b['name'], (string) $a['name']);
	});

	return $out;
}

function wholesale_vi_snap_count($session_key)
{
	$dir = wholesale_vi_snap_dir($session_key);

	return ($dir && is_dir($dir)) ? count((array) glob($dir . '/*.json')) : 0;
}

/**
 * Changes when a visit gains, draws or loses a screenshot (for the live redraw).
 */
function wholesale_vi_snap_signature($session_key)
{
	$dir = wholesale_vi_snap_dir($session_key);
	if (!$dir || !is_dir($dir)) {
		return '';
	}
	$files = (array) glob($dir . '/*');
	$latest = 0;
	foreach ($files as $file) {
		$latest = max($latest, (int) @filemtime($file)); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	return count($files) . '@' . $latest;
}

function wholesale_vi_snap_delete_files($session_key, $name)
{
	$dir = wholesale_vi_snap_dir($session_key);
	$name = wholesale_vi_snap_clean_name($name);
	if (!$dir || '' === $name) {
		return;
	}
	foreach ((array) glob($dir . '/' . $name . '.*') as $file) {
		wp_delete_file($file);
	}
}

function wholesale_vi_snap_delete_dir($dir)
{
	if (!is_dir($dir)) {
		return;
	}
	foreach ((array) glob($dir . '/*') as $file) {
		wp_delete_file($file);
	}
	@rmdir($dir); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
}

/**
 * Keep a visit's newest screenshots.
 */
function wholesale_vi_snap_prune($session_key)
{
	$metas = (array) glob(wholesale_vi_snap_dir($session_key) . '/*.json');
	if (count($metas) <= WHOLESALE_VI_SNAP_KEEP) {
		return;
	}
	sort($metas);
	foreach (array_slice($metas, 0, count($metas) - WHOLESALE_VI_SNAP_KEEP) as $file) {
		wholesale_vi_snap_delete_files($session_key, basename($file, '.json'));
	}
}

/* ---------------------------------------------------------------------------
 * Requests
 * ------------------------------------------------------------------------ */

/**
 * Open requests, keyed by visit key: { req, at, by, id, sent }.
 */
function wholesale_vi_snap_requests()
{
	$all = get_option(WHOLESALE_VI_SNAP_OPTION, array());
	$now = time();

	return array_filter(is_array($all) ? $all : array(), static function ($request) use ($now) {
		return is_array($request) && !empty($request['req']) && $now - (int) ($request['at'] ?? 0) < WHOLESALE_VI_SNAP_TTL;
	});
}

function wholesale_vi_snap_save_requests(array $all)
{
	// Kept (empty) rather than deleted: a missing option would cost every tracker ping a query.
	update_option(WHOLESALE_VI_SNAP_OPTION, $all, true);
}

function wholesale_vi_snap_set_result($req, array $data)
{
	set_transient('wholesale_vi_snap_r_' . $req, $data, 20 * MINUTE_IN_SECONDS);
}

function wholesale_vi_snap_get_result($req)
{
	$req = preg_replace('/[^a-f0-9]/', '', (string) $req);
	$data = $req ? get_transient('wholesale_vi_snap_r_' . $req) : false;

	return is_array($data) ? $data : null;
}

/**
 * Ask a visit's browser for its page.
 *
 * @return string Request id.
 */
function wholesale_vi_snap_ask($session, $admin_id)
{
	$req = bin2hex(random_bytes(12));
	$all = wholesale_vi_snap_requests();
	$all[(string) $session->session_key] = array(
		'req' => $req,
		'at' => time(),
		'by' => (int) $admin_id,
		'id' => (int) $session->id,
		'sent' => 0,
	);
	wholesale_vi_snap_save_requests($all);
	wholesale_vi_snap_set_result($req, array('state' => 'waiting', 'session' => (int) $session->id));

	return $req;
}

/**
 * Called by the tracker's collect endpoint on every ping: the request id this visit's
 * page should answer, or ''. Reads only already-loaded options unless a request is open.
 */
function wholesale_vi_snap_pending($session_key)
{
	$loaded = wp_load_alloptions();
	if (empty($loaded[WHOLESALE_VI_SNAP_OPTION]) || 'a:0:{}' === $loaded[WHOLESALE_VI_SNAP_OPTION] || !preg_match('/^[a-f0-9]{32}$/', (string) $session_key)) {
		return '';
	}
	$all = wholesale_vi_snap_requests();
	$request = $all[$session_key] ?? null;
	// One page answers; another is asked only if that one never did.
	if (!$request || (!empty($request['sent']) && time() - (int) $request['sent'] < WHOLESALE_VI_SNAP_RESEND)) {
		return '';
	}
	$all[$session_key]['sent'] = time();
	wholesale_vi_snap_save_requests($all);

	return (string) $request['req'];
}

/* ---------------------------------------------------------------------------
 * Receiving the page copy (public endpoint, answered only for an open request)
 * ------------------------------------------------------------------------ */

function wholesale_vi_snap_register_route()
{
	register_rest_route('wholesale/v1', '/vi-snap', array(
		'methods' => 'POST',
		'callback' => 'wholesale_vi_snap_rest_receive',
		'permission_callback' => '__return_true',
	));
}
add_action('rest_api_init', 'wholesale_vi_snap_register_route');

function wholesale_vi_snap_rest_receive(WP_REST_Request $request)
{
	$done = new WP_REST_Response(null, 204);
	$raw = $request->get_body();
	if (strlen($raw) > WHOLESALE_VI_SNAP_MAX_GZ * 4 / 3 + 65536) {
		return $done;
	}
	$body = json_decode($raw, true);
	if (!is_array($body)) {
		return $done;
	}
	$session_key = (string) ($body['s'] ?? '');
	$visitor_key = (string) ($body['v'] ?? '');
	$req = (string) ($body['req'] ?? '');
	if (!preg_match('/^[a-f0-9]{32}$/', $session_key) || !preg_match('/^[a-f0-9]{32}$/', $visitor_key) || !preg_match('/^[a-f0-9]{24}$/', $req)) {
		return $done;
	}
	$all = wholesale_vi_snap_requests();
	$pending = $all[$session_key] ?? null;
	if (!$pending || !hash_equals((string) $pending['req'], $req)) {
		return $done;
	}

	global $wpdb;
	$session = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('sessions') . ' WHERE session_key = %s AND visitor_key = %s', $session_key, $visitor_key));
	if (!$session) {
		return $done;
	}
	unset($all[$session_key]);
	wholesale_vi_snap_save_requests($all);

	$error = isset($body['error']) ? sanitize_text_field((string) $body['error']) : '';
	$meta = '' === $error ? wholesale_vi_snap_store_copy($session, $body, (int) $pending['by']) : new WP_Error('wvi_snap_failed', 'The visitor\'s browser could not copy the page: ' . $error);
	wholesale_vi_snap_set_result($req, is_wp_error($meta)
		? array('state' => 'failed', 'session' => (int) $session->id, 'error' => substr($meta->get_error_message(), 0, 240))
		: array('state' => 'captured', 'session' => (int) $session->id, 'name' => (string) $meta['name']));

	return $done;
}

/**
 * Check and save a page copy with the visitor's details.
 *
 * @return array|WP_Error Details saved.
 */
function wholesale_vi_snap_store_copy($session, array $body, $admin_id)
{
	$data = (string) ($body['html'] ?? '');
	if ('gzip' === ($body['enc'] ?? '')) {
		$gz = base64_decode($data, true); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$html = (false !== $gz && strlen($gz) <= WHOLESALE_VI_SNAP_MAX_GZ) ? @gzdecode($gz, WHOLESALE_VI_SNAP_MAX_RAW) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
	} else {
		$html = strlen($data) <= WHOLESALE_VI_SNAP_MAX_RAW ? $data : false;
		$gz = false !== $html ? gzencode($html, 6) : false;
	}
	if (false === $html || false === $gz || strlen($html) < 200 || !preg_match('/<(html|body)[\s>]/i', substr($html, 0, 4096))) {
		return new WP_Error('wvi_snap_bad', 'The page copy was empty, damaged or too large.');
	}

	$dir = wholesale_vi_snap_dir($session->session_key);
	if (!$dir || !wholesale_vi_snap_prepare_root() || !wp_mkdir_p($dir)) {
		return new WP_Error('wvi_snap_disk', 'Could not save the page copy.');
	}
	$name = gmdate('ymdHis') . bin2hex(random_bytes(4));
	if (false === file_put_contents($dir . '/' . $name . '.html.gz', $gz)) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error('wvi_snap_disk', 'Could not save the page copy (disk full?).');
	}
	$page = esc_url_raw((string) ($body['page'] ?? ''));
	$meta = array(
		'name' => $name,
		'ext' => '',
		'page' => preg_match('#^https?://#i', $page) ? substr($page, 0, 2000) : '',
		'title' => substr(sanitize_text_field(html_entity_decode((string) ($body['title'] ?? ''), ENT_QUOTES, 'UTF-8')), 0, 200),
		'vw' => max(200, min(4000, (int) ($body['w'] ?? 0))),
		'vh' => max(200, min(4000, (int) ($body['h'] ?? 0))),
		'sx' => max(0, min(100000, (int) ($body['sx'] ?? 0))),
		'sy' => max(0, min(1000000, (int) ($body['sy'] ?? 0))),
		'ph' => max(0, min(1000000, (int) ($body['ph'] ?? 0))),
		'dpr' => max(1, min(4, (float) ($body['dpr'] ?? 1))),
		'by' => (int) $admin_id,
		'at' => time(),
		'info' => wholesale_vi_snap_visitor_info($session),
	);
	wholesale_vi_snap_write_meta($session->session_key, $meta);
	wholesale_vi_snap_prune($session->session_key);

	return $meta;
}

/**
 * Who the visitor is, as Visitor Insights knows them at the moment of the screenshot.
 */
function wholesale_vi_snap_visitor_info($session)
{
	$person = function_exists('wholesale_vi_person') ? wholesale_vi_person($session) : null;
	$channels = wholesale_vi_channels();
	$network = wholesale_vi_ad_network($session);
	$source = (string) ($channels[$session->channel] ?? ucfirst((string) $session->channel));
	if ($session->source) {
		$source .= ' · ' . $session->source . ($session->medium ? ' / ' . $session->medium : '');
	}

	return array_filter(array(
		'name' => $person ? (string) $person['name'] : '',
		'email' => $person ? (string) $person['email'] : '',
		'customer' => $person ? (string) $person['note'] : '',
		'ip' => (string) $session->ip,
		'location' => (string) wholesale_vi_location($session),
		'timezone' => (string) $session->timezone,
		'language' => (string) $session->language,
		'device' => implode(' · ', array_filter(array(ucfirst((string) $session->device), (string) $session->os, (string) $session->browser))),
		'screen' => (string) $session->screen,
		'source' => $source,
		'campaign' => $session->campaign ? (string) wholesale_vi_campaign_name($session->campaign) : '',
		'keyword' => (string) $session->term,
		'ad_network' => $network ? (string) (wholesale_vi_ad_networks()[$network] ?? $network) : '',
		'referrer' => (string) $session->referrer,
		'landing' => (string) $session->landing_url,
		'visit_started' => strtotime($session->started_at . ' UTC'),
		'pages' => (int) $session->pageviews,
		'returning' => !(int) $session->is_new,
		'visitor_key' => (string) $session->visitor_key,
	), static function ($value) {
		return '' !== $value && null !== $value;
	});
}

/* ---------------------------------------------------------------------------
 * Admin: shape, list and endpoints
 * ------------------------------------------------------------------------ */

function wholesale_vi_snap_file_url($session_id, $name, $download = false)
{
	return add_query_arg(array_filter(array(
		'action' => 'wholesale_vi_snap_file',
		'session' => (int) $session_id,
		'name' => $name,
		'dl' => $download ? 1 : 0,
	)), admin_url('admin-ajax.php'));
}

/**
 * Shape for the admin page and its script. `url` stays empty until an admin's browser has
 * drawn the picture.
 */
function wholesale_vi_snap_format($session_id, array $meta)
{
	$by = (int) ($meta['by'] ?? 0);
	$user = $by ? get_userdata($by) : null;
	$at = (int) ($meta['at'] ?? time());
	$info = is_array($meta['info'] ?? null) ? $meta['info'] : array();
	if (!empty($info['visit_started'])) {
		$info['visit_started'] = wp_date('M j, Y g:i a', (int) $info['visit_started']);
	}
	if (!empty($info['landing']) && '/' === $info['landing'][0]) {
		$info['landing'] = wholesale_vi_site_link($info['landing']);
	}

	return array(
		'id' => (string) $meta['name'],
		'session' => (int) $session_id,
		'url' => !empty($meta['ext']) ? wholesale_vi_snap_file_url($session_id, $meta['name']) : '',
		'download' => !empty($meta['ext']) ? wholesale_vi_snap_file_url($session_id, $meta['name'], true) : '',
		'page' => (string) ($meta['page'] ?? ''),
		'title' => (string) ($meta['title'] ?? ''),
		'w' => (int) ($meta['vw'] ?? 0),
		'h' => (int) ($meta['vh'] ?? 0),
		'sx' => (int) ($meta['sx'] ?? 0),
		'sy' => (int) ($meta['sy'] ?? 0),
		'ph' => (int) ($meta['ph'] ?? 0),
		'dpr' => (float) ($meta['dpr'] ?? 1),
		'at' => $at,
		'at_text' => wp_date('M j, Y · g:i:s a', $at),
		'stamp' => wp_date('Y-m-d-His', $at),
		'by' => $user ? (string) $user->display_name : '',
		'info' => $info,
		'site' => (string) wp_parse_url(home_url('/'), PHP_URL_HOST),
	);
}

function wholesale_vi_snap_session($id)
{
	global $wpdb;

	return $id > 0 ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('sessions') . ' WHERE id = %d', $id)) : null;
}

function wholesale_vi_snap_guard()
{
	if (!current_user_can('manage_options') || !check_ajax_referer('wholesale_vi_live', 'nonce', false)) {
		wp_send_json_error('Not allowed. Reload the page and try again.', 403);
	}
}

function wholesale_vi_snap_ajax_session()
{
	$session = wholesale_vi_snap_session(isset($_POST['session']) ? absint($_POST['session']) : 0);
	if (!$session) {
		wp_send_json_error('That visit was not found.', 404);
	}

	return $session;
}

/** "Take screenshot". */
function wholesale_vi_ajax_snap_request()
{
	wholesale_vi_snap_guard();
	if (!wholesale_vi_snap_scripts()) {
		wp_send_json_error('Screenshots need the SFS Live Chat plugin (a version with page screenshots) to be active.', 400);
	}
	$session = wholesale_vi_snap_ajax_session();
	$last = strtotime($session->last_seen . ' UTC');
	// A visit ends after 30 idle minutes; after that no page of it is open.
	if ($last < time() - 30 * MINUTE_IN_SECONDS) {
		wp_send_json_error('This visitor has left the site, so there is no screen to capture. Screenshots work while they are online.', 400);
	}
	wp_send_json_success(array(
		'req' => wholesale_vi_snap_ask($session, get_current_user_id()),
		'online' => $last >= time() - 5 * MINUTE_IN_SECONDS,
		'ttl' => WHOLESALE_VI_SNAP_TTL,
	));
}
add_action('wp_ajax_wholesale_vi_snap_request', 'wholesale_vi_ajax_snap_request');

/** Where a request stands: waiting | sent | captured (+ snap) | failed | expired. */
function wholesale_vi_ajax_snap_status()
{
	wholesale_vi_snap_guard();
	$req = isset($_POST['req']) ? preg_replace('/[^a-f0-9]/', '', (string) wp_unslash($_POST['req'])) : '';
	$result = wholesale_vi_snap_get_result($req);
	if (!$result) {
		wp_send_json_success(array('state' => 'expired'));
	}
	if ('captured' === $result['state']) {
		$session = wholesale_vi_snap_session((int) $result['session']);
		$meta = $session ? wholesale_vi_snap_read_meta($session->session_key, $result['name']) : null;
		if (!$meta) {
			wp_send_json_success(array('state' => 'failed', 'error' => 'The screenshot was deleted.'));
		}
		wp_send_json_success(array('state' => 'captured', 'snap' => wholesale_vi_snap_format((int) $session->id, $meta)));
	}
	if ('failed' === $result['state']) {
		wp_send_json_success(array('state' => 'failed', 'error' => (string) ($result['error'] ?? '')));
	}
	// Still open: has the visitor's page been asked yet?
	$open = null;
	foreach (wholesale_vi_snap_requests() as $request) {
		if (hash_equals((string) $request['req'], $req)) {
			$open = $request;
			break;
		}
	}
	if (!$open) {
		wp_send_json_success(array('state' => 'expired'));
	}
	wp_send_json_success(array('state' => !empty($open['sent']) ? 'sent' : 'waiting', 'left' => max(0, WHOLESALE_VI_SNAP_TTL - (time() - (int) $open['at']))));
}
add_action('wp_ajax_wholesale_vi_snap_status', 'wholesale_vi_ajax_snap_status');

/** The page copy, for the admin's browser to draw. */
function wholesale_vi_ajax_snap_copy()
{
	wholesale_vi_snap_guard();
	$session = wholesale_vi_snap_ajax_session();
	$meta = wholesale_vi_snap_read_meta($session->session_key, isset($_POST['name']) ? wp_unslash($_POST['name']) : '');
	$file = $meta ? wholesale_vi_snap_dir($session->session_key) . '/' . $meta['name'] . '.html.gz' : '';
	$html = ($file && is_readable($file)) ? gzdecode((string) file_get_contents($file)) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions
	if (false === $html) {
		wp_send_json_error('The page copy is gone.', 404);
	}
	wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_wholesale_vi_snap_copy', 'wholesale_vi_ajax_snap_copy');

/** The picture the admin's browser drew from a copy. */
function wholesale_vi_ajax_snap_save()
{
	wholesale_vi_snap_guard();
	$session = wholesale_vi_snap_ajax_session();
	$meta = wholesale_vi_snap_read_meta($session->session_key, isset($_POST['name']) ? wp_unslash($_POST['name']) : '');
	if (!$meta) {
		wp_send_json_error('That screenshot was not found.', 404);
	}
	$image = isset($_POST['image']) ? (string) wp_unslash($_POST['image']) : '';
	if (!preg_match('#^data:image/(jpeg|png|webp);base64,#', $image, $match)) {
		wp_send_json_error('No picture was sent.', 400);
	}
	$bytes = base64_decode(substr($image, strlen($match[0])), true); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	if (false === $bytes || strlen($bytes) < 100 || strlen($bytes) > WHOLESALE_VI_SNAP_MAX_IMAGE) {
		wp_send_json_error('The picture was empty or too large.', 400);
	}
	$info = @getimagesizefromstring($bytes); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$ext = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp')[is_array($info) ? (string) $info['mime'] : ''] ?? '';
	if ('' === $ext) {
		wp_send_json_error('The picture could not be read.', 400);
	}
	$dir = wholesale_vi_snap_dir($session->session_key);
	if (!empty($meta['ext']) && $meta['ext'] !== $ext) {
		wp_delete_file($dir . '/' . $meta['name'] . '.' . $meta['ext']);
	}
	if (false === file_put_contents($dir . '/' . $meta['name'] . '.' . $ext, $bytes)) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		wp_send_json_error('Could not save the picture (disk full?).', 500);
	}
	$meta['ext'] = $ext;
	$meta['iw'] = (int) $info[0];
	$meta['ih'] = (int) $info[1];
	wholesale_vi_snap_write_meta($session->session_key, $meta);
	wp_send_json_success(wholesale_vi_snap_format((int) $session->id, $meta));
}
add_action('wp_ajax_wholesale_vi_snap_save', 'wholesale_vi_ajax_snap_save');

function wholesale_vi_ajax_snap_delete()
{
	wholesale_vi_snap_guard();
	$session = wholesale_vi_snap_ajax_session();
	wholesale_vi_snap_delete_files($session->session_key, isset($_POST['name']) ? wp_unslash($_POST['name']) : '');
	wp_send_json_success();
}
add_action('wp_ajax_wholesale_vi_snap_delete', 'wholesale_vi_ajax_snap_delete');

/**
 * Stream a screenshot (admins only). Reading needs no nonce: it changes nothing, and the
 * page shows it in <img>.
 */
function wholesale_vi_ajax_snap_file()
{
	if (!current_user_can('manage_options')) {
		status_header(403);
		exit;
	}
	$session = wholesale_vi_snap_session(isset($_GET['session']) ? absint($_GET['session']) : 0);
	$meta = $session ? wholesale_vi_snap_read_meta($session->session_key, isset($_GET['name']) ? wp_unslash($_GET['name']) : '') : null;
	$types = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
	$ext = ($meta && isset($types[$meta['ext'] ?? ''])) ? $meta['ext'] : '';
	$path = $ext ? wholesale_vi_snap_dir($session->session_key) . '/' . $meta['name'] . '.' . $ext : '';
	if (!$path || !is_readable($path)) {
		status_header(404);
		exit;
	}
	$filename = 'visitor-screenshot-' . wp_date('Y-m-d-His', (int) ($meta['at'] ?? time())) . '.' . $ext;
	header('Content-Type: ' . $types[$ext]);
	header('Content-Length: ' . filesize($path));
	header('Content-Disposition: ' . (empty($_GET['dl']) ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
	header('Cache-Control: private, max-age=86400');
	header('X-Content-Type-Options: nosniff');
	header('X-Robots-Tag: noindex');
	readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action('wp_ajax_wholesale_vi_snap_file', 'wholesale_vi_ajax_snap_file');

function wholesale_vi_snap_admin_assets($hook)
{
	if (false === strpos((string) $hook, 'wholesale-insights')) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('wholesale-vi-snaps', $uri . '/css/visitor-snaps.css', array(), (string) filemtime($dir . '/css/visitor-snaps.css'));
	$scripts = wholesale_vi_snap_scripts();
	if (!$scripts) {
		return;
	}
	wp_enqueue_script('wholesale-vi-snaps', $uri . '/js/visitor-snaps-admin.js', array('wholesale-vi-admin'), (string) filemtime($dir . '/js/visitor-snaps-admin.js'), true);
	wp_localize_script('wholesale-vi-snaps', 'wholesaleVISnaps', array(
		'render' => $scripts['render'],
		'home' => home_url('/'),
	));
}
add_action('admin_enqueue_scripts', 'wholesale_vi_snap_admin_assets', 20);

/* ---------------------------------------------------------------------------
 * Admin: what the visit page shows
 * ------------------------------------------------------------------------ */

/**
 * Camera button for a visit that is (or may still be) on the site.
 */
function wholesale_vi_snap_button($session_id, $small = true)
{
	if (!wholesale_vi_snap_scripts()) {
		return '';
	}

	return sprintf(
		'<button type="button" class="button%s wvi-snap-take" data-snap-take="%d" title="%s"><span class="dashicons dashicons-camera"></span>%s</button>',
		$small ? ' button-small' : ' button-primary',
		(int) $session_id,
		esc_attr__('Take a screenshot of what this visitor sees right now. They are not told.', 'litsign'),
		$small ? '' : ' ' . esc_html__('Take screenshot', 'litsign')
	);
}

/**
 * Small "camera · n" badge for visit rows that have screenshots.
 */
function wholesale_vi_snap_badge($session_key)
{
	$count = wholesale_vi_snap_count($session_key);

	return $count ? '<span class="wvi-badge is-soft wvi-snap-badge" title="' . esc_attr(sprintf(_n('%d screenshot saved', '%d screenshots saved', $count, 'litsign'), $count)) . '"><span class="dashicons dashicons-camera"></span>' . (int) $count . '</span>' : '';
}

/**
 * The "Screenshots" card on a visit.
 */
function wholesale_vi_snap_render_section($session)
{
	$metas = wholesale_vi_snap_metas($session->session_key);
	$last = strtotime($session->last_seen . ' UTC');
	$open = $last >= time() - 30 * MINUTE_IN_SECONDS;
	$online = $last >= time() - 5 * MINUTE_IN_SECONDS;
	$ready = (bool) wholesale_vi_snap_scripts();
	if (!$metas && !$open) {
		return;
	}
	?>
	<section class="wvi-card wvi-section wvi-snaps">
		<div class="wvi-section-head">
			<h2><span class="dashicons dashicons-camera"></span> Screenshots</h2>
			<?php if ($open && $ready) : ?>
				<?php echo wholesale_vi_snap_button($session->id, false); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping. ?>
			<?php endif; ?>
		</div>
		<?php if ($open && !$ready) : ?>
			<p class="wvi-muted">Screenshots need the SFS Live Chat plugin (a version with page screenshots) to be active.</p>
		<?php elseif ($open) : ?>
			<p class="wvi-muted"><?php echo $online ? 'Captures what this visitor sees right now, without telling them, and saves it here with their details.' : 'This visitor has been idle for a few minutes. A request waits up to 10 minutes for them to come back to the page.'; ?> Password and card fields are hidden.</p>
		<?php endif; ?>
		<?php if ($metas) : ?>
			<div class="wvi-snap-grid">
				<?php foreach ($metas as $meta) : ?>
					<?php $snap = wholesale_vi_snap_format((int) $session->id, $meta); ?>
					<figure class="wvi-snap" data-snap-id="<?php echo esc_attr($snap['id']); ?>">
						<button type="button" class="wvi-snap-thumb" data-snap="<?php echo esc_attr(wp_json_encode($snap)); ?>" title="Open screenshot">
							<?php if ($snap['url']) : ?>
								<img src="<?php echo esc_url($snap['url']); ?>" alt="Screenshot of <?php echo esc_attr($snap['title'] ?: $snap['page']); ?>" loading="lazy">
							<?php else : ?>
								<span class="wvi-snap-ph" <?php echo $ready ? 'data-snap-undrawn' : ''; ?>><span class="dashicons dashicons-format-image"></span><?php echo $ready ? 'Drawing…' : 'Not drawn yet'; ?></span>
							<?php endif; ?>
						</button>
						<figcaption>
							<strong><?php echo esc_html(wp_date('g:i:s a', $snap['at'])); ?></strong>
							<span class="wvi-break" title="<?php echo esc_attr($snap['page']); ?>"><?php echo esc_html(wholesale_vi_short_path((string) wp_parse_url($snap['page'], PHP_URL_PATH) . ((string) wp_parse_url($snap['page'], PHP_URL_QUERY) ? '?' . wp_parse_url($snap['page'], PHP_URL_QUERY) : ''), 44)); ?></span>
							<span class="wvi-muted"><?php echo esc_html($snap['w'] . ' × ' . $snap['h']); ?></span>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php elseif ($open) : ?>
			<p class="wvi-empty">No screenshots yet.</p>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Screenshots as steps of the visit's journey, printed before the first event that came
 * after them (call with PHP_INT_MAX at the end for the rest).
 *
 * @param array $metas Remaining screenshots, oldest first; printed ones are removed.
 */
function wholesale_vi_snap_timeline_items(&$metas, $until, $start, $session_id)
{
	while ($metas && (int) $metas[0]['at'] <= $until) {
		$snap = wholesale_vi_snap_format((int) $session_id, array_shift($metas));
		$offset = max(0, $snap['at'] - $start);
		?>
		<li class="is-snap tone-snap">
			<span class="wvi-tl-time" title="<?php echo esc_attr(wp_date('g:i:s a', $snap['at'])); ?>">+<?php echo esc_html(sprintf('%d:%02d', floor($offset / 60), $offset % 60)); ?></span>
			<span class="wvi-tl-icon dashicons dashicons-camera"></span>
			<div class="wvi-tl-body">
				<div class="wvi-tl-text">Screenshot taken<?php echo $snap['by'] ? ' by ' . esc_html($snap['by']) : ''; ?></div>
				<div class="wvi-tl-meta">
					<button type="button" class="button-link" data-snap="<?php echo esc_attr(wp_json_encode($snap)); ?>">View screenshot</button>
					<span class="wvi-chip"><?php echo esc_html($snap['w'] . ' × ' . $snap['h']); ?></span>
				</div>
			</div>
		</li>
		<?php
	}
}

function wholesale_vi_snap_timeline_list($session_key)
{
	return array_reverse(wholesale_vi_snap_metas($session_key));
}

/* ---------------------------------------------------------------------------
 * Clean-up: screenshots go with their visits
 * ------------------------------------------------------------------------ */

/**
 * After the daily purge (priority 10) removed old visits: remove their screenshots.
 */
function wholesale_vi_snap_purge_old()
{
	global $wpdb;
	$root = wholesale_vi_snap_root();
	$dirs = is_dir($root) ? (array) glob($root . '/*', GLOB_ONLYDIR) : array();
	$sessions = wholesale_vi_table('sessions');
	// Never judge against a missing or empty table (e.g. mid-copy between sites).
	if (!$dirs || $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $sessions)) !== $sessions || !(int) $wpdb->get_var("SELECT COUNT(*) FROM {$sessions}")) { // phpcs:ignore WordPress.DB.PreparedSQL
		return;
	}
	foreach (array_chunk($dirs, 200) as $chunk) {
		$keys = array_values(array_filter(array_map('basename', $chunk), static function ($key) {
			return (bool) preg_match('/^[a-f0-9]{32}$/', $key);
		}));
		if (!$keys) {
			continue;
		}
		$in = implode(', ', array_fill(0, count($keys), '%s'));
		$known = array_flip((array) $wpdb->get_col($wpdb->prepare("SELECT session_key FROM {$sessions} WHERE session_key IN ({$in})", $keys))); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ($keys as $key) {
			if (!isset($known[$key])) {
				wholesale_vi_snap_delete_dir($root . '/' . $key);
			}
		}
	}
	// Requests nobody answered.
	wholesale_vi_snap_save_requests(wholesale_vi_snap_requests());
}
add_action('wholesale_vi_purge', 'wholesale_vi_snap_purge_old', 20);

/**
 * "Delete all visitor data" (Settings) also deletes every screenshot. Runs before the
 * handler in visitor-insights.php, with the same checks.
 */
function wholesale_vi_snap_purge_all()
{
	if (!current_user_can('manage_options') || !wp_verify_nonce(isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '', 'wholesale_vi_purge_all')) {
		return;
	}
	$root = wholesale_vi_snap_root();
	foreach ((array) glob($root . '/*', GLOB_ONLYDIR) as $dir) {
		wholesale_vi_snap_delete_dir($dir);
	}
	wholesale_vi_snap_save_requests(array());
}
add_action('admin_post_wholesale_vi_purge_all', 'wholesale_vi_snap_purge_all', 5);
