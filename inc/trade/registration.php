<?php
/**
 * Trade account application (/trade-account/).
 *
 * Replaces the old /signup/ form, which let in 400+ spam accounts and asked for
 * no business details. Flow with instant access on (the default, Trade
 * Accounts > Settings): application -> approved and logged in at once -> staff
 * are emailed and can suspend. With it off: application -> email verification
 * link -> staff review (inc/trade/admin.php) -> approved / info requested / rejected.
 *
 * Anti-spam: honeypot, signed minimum fill time, per-IP rate limit, disposable
 * email domains blocked, verification before review. A business email whose
 * domain doesn't match the website is flagged for staff, not blocked.
 *
 * Documents are stored in uploads/trade-docs/ behind a deny rule, under
 * random names with a fixed safe extension, and are only readable by staff
 * through a nonce-checked download handler.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

define('WHOLESALE_TRADE_REWRITE_VERSION', '1');
define('WHOLESALE_TRADE_MAX_DOC_BYTES', 10 * MB_IN_BYTES);

add_action('init', static function () {
	add_rewrite_rule('^trade-account/?$', 'index.php?wholesale_trade_page=apply', 'top');
}, 20);
add_action('init', static function () {
	if (WHOLESALE_TRADE_REWRITE_VERSION !== get_option('wholesale_trade_rewrite_version')) {
		flush_rewrite_rules(false);
		update_option('wholesale_trade_rewrite_version', WHOLESALE_TRADE_REWRITE_VERSION, false);
	}
}, 99);
add_filter('query_vars', static function ($vars) {
	$vars[] = 'wholesale_trade_page';
	return $vars;
});

function wholesale_trade_apply_url()
{
	return home_url('/trade-account/');
}

/** Same-site login link that returns to $return (the current page by default). */
function wholesale_trade_login_url($return = '')
{
	if (!$return && !empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI'])) {
		$return = (is_ssl() ? 'https://' : 'http://') . wp_unslash($_SERVER['HTTP_HOST']) . wp_unslash($_SERVER['REQUEST_URI']);
	}
	// wp_validate_redirect() drops anything off this site (no open redirect).
	return add_query_arg('redirect_ulr', rawurlencode(wp_validate_redirect(esc_url_raw((string) $return), home_url('/'))), home_url('/login/'));
}

/** Old signup URL -> the application page. Account page shows trade status for non-approved users. */
add_action('template_redirect', static function () {
	if (is_page('signup')) {
		wp_safe_redirect(wholesale_trade_apply_url(), 301);
		exit;
	}
	if (is_page('account') && is_user_logged_in() && !wholesale_trade_can_see_prices()) {
		wp_safe_redirect(wholesale_trade_apply_url());
		exit;
	}
}, 2);

/** Header links: apply instead of "Register". */
add_filter('wholesale_header_account_links', static function ($links) {
	if (!is_user_logged_in()) {
		return array(wholesale_trade_login_url() => 'Log in', wholesale_trade_apply_url() => 'Apply for trade');
	}
	if (!wholesale_trade_can_see_prices()) {
		return array(wholesale_trade_apply_url() => 'Application status', wp_logout_url(home_url('/')) => 'Log out');
	}
	return $links;
});

/** Domains of throwaway inboxes seen in the old signup spam, plus the common services. */
function wholesale_trade_disposable_domains()
{
	return array(
		'anonmails.de', 'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com', '10minutemail.com',
		'temp-mail.org', 'tempmail.com', 'tempmailo.com', 'yopmail.com', 'trashmail.com', 'getnada.com', 'dispostable.com',
		'maildrop.cc', 'throwawaymail.com', 'fakeinbox.com', 'mohmal.com', 'emailondeck.com', 'mintemail.com', 'spamgourmet.com',
		'mailnesia.com', 'tempr.email', 'discard.email', 'moakt.com', 'tmail.ws', 'mail.tm', 'emailfake.com', 'luxusmail.org',
	);
}

function wholesale_trade_email_domain($email)
{
	$at = strrpos((string) $email, '@');
	return false === $at ? '' : strtolower(substr($email, $at + 1));
}

function wholesale_trade_site_domain($url)
{
	$host = strtolower((string) wp_parse_url(esc_url_raw($url), PHP_URL_HOST));
	return preg_replace('/^www\./', '', $host);
}

/** Signed form-start time: the form must take at least 4 seconds and no more than a day to fill in. */
function wholesale_trade_form_token()
{
	$time = time();
	return $time . '.' . hash_hmac('sha256', 'trade-form|' . $time, wp_salt('nonce'));
}

function wholesale_trade_form_token_ok($token)
{
	return '' === wholesale_trade_form_token_problem($token);
}

/** Why a form token fails: '' (fine), 'token_bad', 'token_fast' or 'token_old'. */
function wholesale_trade_form_token_problem($token)
{
	$parts = explode('.', (string) $token);
	if (2 !== count($parts) || '' === $parts[0] || !preg_match('/^\d+$/', $parts[0])
		|| !hash_equals(hash_hmac('sha256', 'trade-form|' . $parts[0], wp_salt('nonce')), $parts[1])) {
		return 'token_bad';
	}
	$age = time() - (int) $parts[0];
	if ($age < 4) {
		return 'token_fast';
	}
	return $age > DAY_IN_SECONDS ? 'token_old' : '';
}

/**
 * Keep a short, anonymous log of blocked submissions (time and reason only), so
 * staff can see on Trade Accounts whether real applicants are being turned away.
 */
function wholesale_trade_note_blocked($reason)
{
	$log = get_option('wholesale_trade_blocked_log', array());
	$log = is_array($log) ? $log : array();
	$log[] = array(time(), (string) $reason);
	update_option('wholesale_trade_blocked_log', array_slice($log, -200), false);
	if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
		error_log('Trade application blocked: ' . $reason); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}

/** Blocked submissions in the last $days days, counted by reason. */
function wholesale_trade_blocked_counts($days = 7)
{
	$since = time() - $days * DAY_IN_SECONDS;
	$counts = array();
	foreach ((array) get_option('wholesale_trade_blocked_log', array()) as $row) {
		if (is_array($row) && isset($row[0], $row[1]) && (int) $row[0] >= $since) {
			$counts[$row[1]] = ($counts[$row[1]] ?? 0) + 1;
		}
	}
	return $counts;
}

function wholesale_trade_client_ip_key()
{
	$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';
	return 'wholesale_trade_rl_' . substr(hash_hmac('sha256', $ip, wp_salt('auth')), 0, 20);
}

/** Folder for applicant documents, with deny rules so the web server never serves them. */
function wholesale_trade_docs_dir()
{
	$uploads = wp_upload_dir(null, false);
	$dir = trailingslashit($uploads['basedir']) . 'trade-docs';
	if (!is_dir($dir)) {
		wp_mkdir_p($dir);
	}
	if (!file_exists($dir . '/.htaccess')) {
		file_put_contents($dir . '/.htaccess', "# Trade applicant documents: staff download them through wp-admin only.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\nOptions -Indexes\n");
	}
	if (!file_exists($dir . '/index.php')) {
		file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n");
	}
	if (!file_exists($dir . '/web.config')) {
		file_put_contents($dir . '/web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>');
	}
	return $dir;
}

/** Document fields on the form. */
function wholesale_trade_doc_fields()
{
	return array(
		'resale_certificate' => 'Resale certificate or seller’s permit',
		'business_license' => 'Business license',
	);
}

/**
 * Validate and store one uploaded document. Returns the stored record, null
 * when no file was sent, or a WP_Error.
 */
function wholesale_trade_store_doc($field)
{
	if (empty($_FILES[$field]) || !isset($_FILES[$field]['error']) || UPLOAD_ERR_NO_FILE === (int) $_FILES[$field]['error']) {
		return null;
	}
	$file = $_FILES[$field];
	if (UPLOAD_ERR_OK !== (int) $file['error'] || !is_uploaded_file($file['tmp_name'])) {
		return new WP_Error($field, 'The file could not be uploaded. Please try again.');
	}
	if ((int) $file['size'] <= 0 || (int) $file['size'] > WHOLESALE_TRADE_MAX_DOC_BYTES) {
		return new WP_Error($field, 'Files must be a PDF, JPG or PNG up to 10 MB.');
	}

	// Trust the content, never the name or the browser's type.
	$types = array('application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png');
	$mime = '';
	if (function_exists('finfo_open')) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = (string) finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);
	}
	if (!isset($types[$mime])) {
		return new WP_Error($field, 'Files must be a PDF, JPG or PNG up to 10 MB.');
	}
	$head = (string) file_get_contents($file['tmp_name'], false, null, 0, 8);
	$magic_ok = ('pdf' === $types[$mime] && 0 === strpos($head, '%PDF'))
		|| ('jpg' === $types[$mime] && 0 === strpos($head, "\xFF\xD8\xFF"))
		|| ('png' === $types[$mime] && 0 === strpos($head, "\x89PNG"));
	if (!$magic_ok || ('pdf' !== $types[$mime] && false === @getimagesize($file['tmp_name']))) {
		return new WP_Error($field, 'Files must be a PDF, JPG or PNG up to 10 MB.');
	}

	$dir = wholesale_trade_docs_dir();
	$name = bin2hex(random_bytes(16)) . '.' . $types[$mime];
	if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
		return new WP_Error($field, 'The file could not be saved. Please try again.');
	}
	@chmod($dir . '/' . $name, 0640);

	return array(
		'file' => $name,
		'original' => sanitize_file_name(wp_basename((string) $file['name'])),
		'mime' => $mime,
		'size' => (int) $file['size'],
		'uploaded' => time(),
	);
}

/** Text fields on the form: key => [label, required]. */
function wholesale_trade_form_fields()
{
	return array(
		'first_name' => array('First name', true),
		'last_name' => array('Last name', true),
		'job_title' => array('Job title', true),
		'email' => array('Work email', true),
		'mobile' => array('Mobile phone', true),
		'legal_name' => array('Legal business name', true),
		'dba' => array('DBA (if different)', false),
		'business_type' => array('Business type', true),
		'website' => array('Company website', true),
		'business_phone' => array('Business phone', true),
		'address_1' => array('Street address', true),
		'address_2' => array('Suite / unit', false),
		'city' => array('City', true),
		'state' => array('State', true),
		'zip' => array('ZIP code', true),
		'years_in_business' => array('Years in business', true),
		'heard_about' => array('How did you hear about us?', false),
		'notes' => array('Anything we should know about your shop or upcoming projects?', false),
	);
}

/** Fields no longer on the form, kept so staff still see them on older applications. */
function wholesale_trade_retired_fields()
{
	return array(
		'incorporation_state' => array('State of incorporation', false),
		'monthly_volume' => array('Estimated channel letter sets per month', false),
		'contractor_license' => array('Sign or electrical contractor license #', false),
		'contractor_license_state' => array('License state', false),
	);
}

function wholesale_trade_volume_options()
{
	return array('1-2' => '1–2 sets a month', '3-5' => '3–5 sets a month', '6-15' => '6–15 sets a month', '16+' => '16+ sets a month', 'project' => 'One upcoming project / program');
}

function wholesale_trade_us_states()
{
	return array('AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY');
}

/**
 * Handle a posted application. Fills $GLOBALS['wholesale_trade_form'] with
 * errors and values for the template, or redirects on success (PRG).
 */
function wholesale_trade_handle_application()
{
	$state = array('errors' => array(), 'values' => array());
	$GLOBALS['wholesale_trade_form'] = &$state;

	if (!isset($_POST['wholesale_trade_apply'])) {
		return;
	}

	$fail = static function ($message) use (&$state) {
		$state['errors']['_form'] = $message;
	};

	// The typed details are read first, so a form turned away below comes back filled
	// in (only to the person who sent it) and they don't have to type it all again.
	$values = array();
	foreach (wholesale_trade_form_fields() as $key => $field) {
		$raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
		$raw = is_string($raw) ? $raw : '';
		$values[$key] = 'notes' === $key ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
		$values[$key] = mb_substr($values[$key], 0, 'notes' === $key ? 2000 : 200);
	}
	$state['values'] = $values;

	// Spam checks. The trap field has a meaningless name and is display:none, so
	// browser autofill and password managers leave it empty; only bots fill it.
	$honeypot = isset($_POST['tw_hp_check']) ? trim((string) wp_unslash($_POST['tw_hp_check'])) : '';
	$token = isset($_POST['trade_token']) ? (string) wp_unslash($_POST['trade_token']) : '';
	$token_problem = wholesale_trade_form_token_problem($token);
	$nonce_ok = isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'wholesale_trade_apply');
	if ('' !== $honeypot) {
		wholesale_trade_note_blocked('honeypot');
		$fail('We could not accept the form. Please check your details and send it again, or call us at ' . wholesale_trade_brand()['phone'] . '.');
		return;
	}
	if ('token_fast' === $token_problem) {
		wholesale_trade_note_blocked('too_fast');
		$fail('That was sent a little too fast. Please wait a few seconds and send it again.');
		return;
	}
	if (!$nonce_ok || '' !== $token_problem) {
		wholesale_trade_note_blocked($nonce_ok ? $token_problem : 'nonce');
		$fail('This page was open for a long time, so we need you to send it once more. Your details are still filled in below: re-enter your password, re-attach any documents and send the form again.');
		return;
	}
	// The form start time carries over to a re-shown form, so fixing one field and
	// sending it again straight away is not mistaken for a bot.
	$state['token'] = $token;

	// Two hourly limits per IP: 5 created accounts, and 20 rejected tries (typos must not lock a real applicant out).
	$rl_key = wholesale_trade_client_ip_key();
	$attempts = (int) get_transient($rl_key);
	$fail_key = $rl_key . '_f';
	$failures = (int) get_transient($fail_key);
	if ($attempts >= 5 || $failures >= 20) {
		$fail('Too many applications from this connection. Please call us at ' . wholesale_trade_brand()['phone'] . '.');
		return;
	}
	$fail_counted = static function ($message) use ($fail, $fail_key, $failures) {
		set_transient($fail_key, $failures + 1, HOUR_IN_SECONDS);
		$fail($message);
	};

	foreach (wholesale_trade_form_fields() as $key => $field) {
		if ($field[1] && '' === $values[$key]) {
			$state['errors'][$key] = $field[0] . ' is required.';
		}
	}
	$values['email'] = sanitize_email($values['email']);
	$values['website'] = esc_url_raw(preg_match('#^https?://#i', $values['website']) ? $values['website'] : ('' !== $values['website'] ? 'https://' . $values['website'] : ''));
	$state['values'] = $values;

	if ($values['email'] && !is_email($values['email'])) {
		$state['errors']['email'] = 'Enter a valid email address.';
	}
	$domain = wholesale_trade_email_domain($values['email']);
	if ($domain && in_array($domain, wholesale_trade_disposable_domains(), true)) {
		$state['errors']['email'] = 'Please use your business email address.';
	}
	if ($values['email'] && email_exists($values['email'])) {
		$state['errors']['email'] = 'This email already has an account. Log in to see your application status.';
	}
	if ('' !== $values['website'] && !wholesale_trade_site_domain($values['website'])) {
		$state['errors']['website'] = 'Enter your company website, for example yoursignco.com.';
	}
	if (!isset(wholesale_trade_business_types()[$values['business_type']])) {
		$state['errors']['business_type'] = 'Choose your business type.';
	}
	if (!in_array($values['state'], wholesale_trade_us_states(), true)) {
		$state['errors']['state'] = 'Choose a state.';
	}
	if (!preg_match('/^\d{5}(-\d{4})?$/', $values['zip'])) {
		$state['errors']['zip'] = 'Enter a 5-digit ZIP code.';
	}
	if ('' !== $values['years_in_business'] && !preg_match('/^\d{1,3}$/', $values['years_in_business'])) {
		$state['errors']['years_in_business'] = 'Enter a number of years.';
	}
	foreach (array('mobile', 'business_phone') as $phone) {
		if ('' !== $values[$phone] && strlen(preg_replace('/\D/', '', $values[$phone])) < 10) {
			$state['errors'][$phone] = 'Enter a 10-digit phone number.';
		}
	}
	$password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
	if (strlen($password) < 10) {
		$state['errors']['password'] = 'Use at least 10 characters.';
	}
	if (empty($_POST['agree_terms'])) {
		$state['errors']['agree_terms'] = 'Please accept the trade terms.';
	}

	if ($state['errors']) {
		$fail_counted('Please fix the highlighted fields.');
		return;
	}

	// Documents last, so a form with errors doesn't leave files behind.
	$docs = array();
	foreach (array_keys(wholesale_trade_doc_fields()) as $doc_field) {
		$doc = wholesale_trade_store_doc($doc_field);
		if (is_wp_error($doc)) {
			$state['errors'][$doc_field] = $doc->get_error_message();
		} elseif ($doc) {
			$docs[$doc_field] = $doc;
		}
	}
	if (get_option('wholesale_trade_require_resale') && empty($docs['resale_certificate'])) {
		$state['errors']['resale_certificate'] = 'Please attach your resale certificate or seller’s permit.';
	}
	if ($state['errors']) {
		foreach ($docs as $doc) {
			@unlink(wholesale_trade_docs_dir() . '/' . $doc['file']);
		}
		$fail_counted('Please fix the highlighted fields.');
		return;
	}

	// A unique username from the email; applicants never choose a role.
	$base = sanitize_user(strstr($values['email'], '@', true), true);
	$base = $base ? substr($base, 0, 40) : 'trade';
	$login = $base;
	for ($i = 2; username_exists($login); $i++) {
		$login = $base . $i;
	}

	$user_id = wp_insert_user(array(
		'user_login' => $login,
		'user_email' => $values['email'],
		'user_pass' => $password,
		'first_name' => $values['first_name'],
		'last_name' => $values['last_name'],
		'display_name' => trim($values['first_name'] . ' ' . $values['last_name']),
		'user_url' => $values['website'],
		'role' => 'trade_pending',
	));
	if (is_wp_error($user_id)) {
		$fail_counted('We could not create the account. Please call us at ' . wholesale_trade_brand()['phone'] . '.');
		return;
	}
	set_transient($rl_key, $attempts + 1, HOUR_IN_SECONDS);

	$flags = array();
	$site_domain = wholesale_trade_site_domain($values['website']);
	if ($site_domain && $domain && $domain !== $site_domain && substr($domain, -strlen('.' . $site_domain)) !== '.' . $site_domain) {
		$flags[] = 'Email domain (' . $domain . ') does not match the website (' . $site_domain . ').';
	}
	if (in_array($domain, array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com', 'live.com', 'msn.com'), true)) {
		$flags[] = 'Free email provider (' . $domain . ').';
	}

	unset($values['email']);
	update_user_meta($user_id, '_trade_profile', $values);
	update_user_meta($user_id, '_trade_docs', $docs);
	update_user_meta($user_id, '_trade_flags', $flags);
	update_user_meta($user_id, '_trade_applied_at', time());
	update_user_meta($user_id, 'billing_phone', $values['business_phone']);
	wholesale_trade_fill_account_details($user_id);
	update_user_meta($user_id, '_trade_status', 'email_unverified');
	wholesale_trade_log($user_id, array('status' => 'email_unverified', 'from' => '', 'note' => 'Application submitted'));

	// Instant access: approve, log them in and open trade pricing now. Staff are
	// told and check the business afterwards (they can suspend from wp-admin).
	if (wholesale_trade_auto_approve()) {
		update_user_meta($user_id, '_trade_auto_approved', time());
		wholesale_trade_set_status($user_id, 'approved', 'Approved automatically when the application was sent', false);
		wholesale_trade_send_status_email($user_id, 'welcome');
		wholesale_trade_notify_staff($user_id, 'auto_approved');
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id, true);
		wp_safe_redirect(add_query_arg('welcome', '1', wholesale_trade_apply_url()));
		exit;
	}

	wholesale_trade_send_verification($user_id);
	// Staff hear about every new registration now, not only once the applicant confirms:
	// a confirmation email lost to spam would otherwise leave the account unseen.
	wholesale_trade_notify_staff($user_id, 'registered');
	wp_safe_redirect(add_query_arg('submitted', '1', wholesale_trade_apply_url()));
	exit;
}

function wholesale_trade_send_verification($user_id)
{
	$user = get_userdata((int) $user_id);
	if (!$user) {
		return;
	}
	$token = bin2hex(random_bytes(20));
	update_user_meta($user->ID, '_trade_verify_hash', wp_hash_password($token));
	update_user_meta($user->ID, '_trade_verify_expires', time() + 3 * DAY_IN_SECONDS);
	$url = add_query_arg(array('verify' => $token, 'u' => $user->ID), wholesale_trade_apply_url());

	wholesale_trade_mail($user->user_email, 'Confirm your email to finish your trade application', array(
		'Hi ' . ($user->first_name ? $user->first_name : $user->display_name) . ',',
		'Please confirm your email address. Your trade account application goes to our team for review as soon as you do.',
		'The link works for 3 days. If you did not apply, you can ignore this email.',
	), array('url' => $url, 'label' => 'Confirm my email'));
}

/** "Send a new confirmation link" for a logged-in applicant who has not confirmed yet (one every 10 minutes). */
function wholesale_trade_handle_resend()
{
	$user_id = get_current_user_id();
	if (!$user_id || !isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'wholesale_trade_resend')
		|| 'email_unverified' !== wholesale_trade_status($user_id)) {
		return;
	}
	$key = 'wholesale_trade_resend_' . $user_id;
	if (get_transient($key)) {
		wp_safe_redirect(add_query_arg('resent', 'wait', wholesale_trade_apply_url()));
		exit;
	}
	set_transient($key, 1, 10 * MINUTE_IN_SECONDS);
	wholesale_trade_send_verification($user_id);
	wp_safe_redirect(add_query_arg('resent', '1', wholesale_trade_apply_url()));
	exit;
}

/** The verification link: moves the application into staff review. */
function wholesale_trade_handle_verification()
{
	if (!isset($_GET['verify'], $_GET['u'])) {
		return '';
	}
	$user_id = absint($_GET['u']);
	$token = sanitize_text_field(wp_unslash($_GET['verify']));
	$hash = (string) get_user_meta($user_id, '_trade_verify_hash', true);
	$expires = (int) get_user_meta($user_id, '_trade_verify_expires', true);

	if ('email_unverified' !== wholesale_trade_status($user_id)) {
		return 'pending_review' === wholesale_trade_status($user_id) ? 'already' : 'invalid';
	}
	if (!$hash || $expires < time() || !wp_check_password($token, $hash)) {
		return 'invalid';
	}

	delete_user_meta($user_id, '_trade_verify_hash');
	delete_user_meta($user_id, '_trade_verify_expires');
	// Applied before instant access was switched on: confirming now opens the account too.
	if (wholesale_trade_auto_approve()) {
		update_user_meta($user_id, '_trade_auto_approved', time());
		wholesale_trade_set_status($user_id, 'approved', 'Email verified, approved automatically');
		wholesale_trade_notify_staff($user_id, 'auto_approved');
		return 'approved';
	}
	wholesale_trade_set_status($user_id, 'pending_review', 'Email verified');
	wholesale_trade_notify_staff($user_id);
	return 'verified';
}

/**
 * Email staff about an application. $stage 'registered' is sent when the form is
 * submitted (email not confirmed yet); 'confirmed' when it is ready for review.
 */
function wholesale_trade_notify_staff($user_id, $stage = 'confirmed')
{
	$user = get_userdata((int) $user_id);
	if (!$user) {
		return false;
	}
	$profile = wholesale_trade_profile($user_id);
	$types = wholesale_trade_business_types();
	$flags = array_filter((array) get_user_meta($user_id, '_trade_flags', true));
	$business = $profile['legal_name'] ?? $user->user_email;
	if ('auto_approved' === $stage) {
		$subject = 'New trade account (approved automatically): ' . $business;
		$intro = array(
			'A business just applied for a trade account. It was approved automatically, so they already see trade pricing and can order.',
			'Please look over the details below. If it is not a real trade business, open the account and click Suspend: pricing and ordering stop at once.',
		);
	} elseif ('registered' === $stage) {
		$subject = 'New trade account registration: ' . $business . ' (email not confirmed yet)';
		$intro = array(
			'A business just registered for a trade account.',
			'We emailed them a confirmation link. You will get a second email when they confirm and the application is ready for review. If they have not confirmed within a day, the email may be in their spam folder: you can call them, or approve the account by hand.',
		);
	} else {
		$subject = 'New trade application: ' . $business;
		$intro = array('A new trade account application is waiting for review. The applicant has confirmed their email.');
	}
	return wholesale_trade_mail(wholesale_trade_notify_email(), $subject, array_merge($intro, array(
		'Business: ' . ($profile['legal_name'] ?? '') . (empty($profile['dba']) ? '' : ' (DBA ' . $profile['dba'] . ')'),
		'Type: ' . ($types[$profile['business_type'] ?? ''] ?? '') . ' · ' . ($profile['city'] ?? '') . ', ' . ($profile['state'] ?? ''),
		'Contact: ' . $user->display_name . ', ' . ($profile['job_title'] ?? '') . ' · ' . $user->user_email . ' · ' . ($profile['mobile'] ?? ''),
		'Business phone: ' . ($profile['business_phone'] ?? ''),
		'Website: ' . ($profile['website'] ?? ''),
	), $flags ? array('Flags: ' . implode(' ', $flags)) : array()), array('url' => admin_url('admin.php?page=wholesale-trade&user=' . (int) $user_id), 'label' => 'Review application'), $user->user_email, $user->display_name);
}

/** Route /trade-account/ to its template and run the handlers first. */
add_action('template_redirect', static function () {
	if ('apply' !== get_query_var('wholesale_trade_page')) {
		return;
	}
	nocache_headers();
	$GLOBALS['wholesale_trade_verify_result'] = wholesale_trade_handle_verification();
	if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && !is_user_logged_in()) {
		wholesale_trade_handle_application();
	} elseif ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['wholesale_trade_resend'])) {
		wholesale_trade_handle_resend();
	}
	if (!isset($GLOBALS['wholesale_trade_form'])) {
		$GLOBALS['wholesale_trade_form'] = array('errors' => array(), 'values' => array());
	}
	$GLOBALS['wholesale_trade_virtual_page'] = 'trade-account';
	status_header(200);
	include get_template_directory() . '/template-parts/trade/trade-account.php';
	exit;
}, 5);

/** Pending accounts that log in land on their status page. */
add_filter('login_redirect', static function ($redirect, $requested, $user) {
	return ($user instanceof WP_User && !wholesale_trade_can_see_prices($user->ID)) ? wholesale_trade_apply_url() : $redirect;
}, 10, 3);
