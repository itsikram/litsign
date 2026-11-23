<?php
/**
 * Trade accounts: status, roles, history and emails.
 *
 * Status lives in user meta `_trade_status`:
 *   email_unverified -> pending_review -> approved | info_requested | rejected
 *   (with instant access on, a new application goes straight to approved)
 *   approved -> suspended (and back with Re-open)
 * Users with no status (everyone registered before the trade site) count as
 * `legacy_unreviewed`: nobody is approved automatically, staff decide.
 *
 * Only `approved` sees prices or orders. Applicants get the `trade_pending`
 * role and approved accounts `trade_customer`; both have `read` only.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

define('WHOLESALE_TRADE_ROLES_VERSION', '1');

/** Status keys and their labels. */
function wholesale_trade_statuses()
{
	return array(
		'email_unverified' => 'Email not verified',
		'pending_review' => 'Pending review',
		'info_requested' => 'More info requested',
		'approved' => 'Approved',
		'rejected' => 'Not approved',
		'suspended' => 'Suspended',
		'legacy_unreviewed' => 'Legacy (unreviewed)',
	);
}

/** Business types offered on the application form. */
function wholesale_trade_business_types()
{
	return array(
		'sign_company' => 'Sign company / sign shop',
		'installer' => 'Sign installer',
		'print_shop' => 'Print shop',
		'agency' => 'Ad or marketing agency',
		'contractor' => 'General contractor or architect',
		'franchise' => 'Franchise or brand (multi-location)',
		'other' => 'Other trade business',
	);
}

/** Accounts created by the 2024–2026 attack (never approve these). */
function wholesale_trade_attacker_user_ids()
{
	return array(1, 2, 3, 8, 477);
}

add_action('init', static function () {
	if (WHOLESALE_TRADE_ROLES_VERSION === get_option('wholesale_trade_roles_version')) {
		return;
	}
	if (!get_role('trade_pending')) {
		add_role('trade_pending', 'Trade applicant', array('read' => true));
	}
	if (!get_role('trade_customer')) {
		add_role('trade_customer', 'Trade customer', array('read' => true));
	}
	update_option('wholesale_trade_roles_version', WHOLESALE_TRADE_ROLES_VERSION, false);
}, 5);

/**
 * Staff see prices and manage accounts. Anyone who can edit products (Editors,
 * Authors) counts too, so the product editor never loads zeroed price meta and
 * saves it back. Account management itself still checks list_users (admin.php).
 */
function wholesale_trade_is_staff($user_id = null)
{
	$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
	$product = get_post_type_object('product');
	return $user_id && (user_can($user_id, 'list_users') || user_can($user_id, $product ? $product->cap->edit_posts : 'edit_posts'));
}

/**
 * New applications are approved (and the applicant logged in) the moment the
 * form is sent; staff check them afterwards and can suspend. On by default;
 * Trade Accounts > Settings turns it off to go back to email confirmation and
 * review before access.
 */
function wholesale_trade_auto_approve()
{
	return (bool) get_option('wholesale_trade_auto_approve', 1);
}

/** A user's trade status key. */
function wholesale_trade_status($user_id = null)
{
	$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
	if (!$user_id) {
		return '';
	}
	$status = (string) get_user_meta($user_id, '_trade_status', true);
	return isset(wholesale_trade_statuses()[$status]) ? $status : 'legacy_unreviewed';
}

function wholesale_trade_status_label($status)
{
	$labels = wholesale_trade_statuses();
	return isset($labels[$status]) ? $labels[$status] : $status;
}

/** Approved trade account, or staff. Called through wholesale_trade_can_see_prices(). */
function wholesale_trade_user_is_approved($user_id = null)
{
	$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
	if (!$user_id) {
		return false;
	}
	if (wholesale_trade_is_staff($user_id)) {
		return true;
	}
	if (in_array($user_id, wholesale_trade_attacker_user_ids(), true)) {
		return false;
	}
	return 'approved' === wholesale_trade_status($user_id);
}

/** The application details saved at signup. */
function wholesale_trade_profile($user_id)
{
	$profile = get_user_meta((int) $user_id, '_trade_profile', true);
	return is_array($profile) ? $profile : array();
}

/**
 * Copy the application's contact details into the user meta keys My Account and
 * checkout read. Only empty keys are filled, so edits made in My Account stay.
 */
function wholesale_trade_fill_account_details($user_id)
{
	$profile = wholesale_trade_profile($user_id);
	$map = array('telephone' => 'business_phone', 'website' => 'website', 'street_one' => 'address_1', 'street_two' => 'address_2', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'billing_company' => 'legal_name');
	foreach ($map as $meta_key => $field) {
		if (!empty($profile[$field]) && '' === (string) get_user_meta((int) $user_id, $meta_key, true)) {
			update_user_meta((int) $user_id, $meta_key, $profile[$field]);
		}
	}
}

/** Status history, newest first. */
function wholesale_trade_history($user_id)
{
	$history = get_user_meta((int) $user_id, '_trade_history', true);
	return is_array($history) ? array_reverse($history) : array();
}

function wholesale_trade_log($user_id, $entry)
{
	$history = get_user_meta((int) $user_id, '_trade_history', true);
	$history = is_array($history) ? $history : array();
	$history[] = $entry + array('time' => time(), 'by' => get_current_user_id());
	update_user_meta((int) $user_id, '_trade_history', array_slice($history, -100));
}

/**
 * Change a trade account's status, its role, the history log, and email the
 * applicant. $message goes into the email (reason or what info is needed).
 */
function wholesale_trade_set_status($user_id, $status, $message = '', $notify = true)
{
	$user = get_userdata((int) $user_id);
	if (!$user || !isset(wholesale_trade_statuses()[$status]) || 'legacy_unreviewed' === $status) {
		return false;
	}
	if ('approved' === $status && in_array($user->ID, wholesale_trade_attacker_user_ids(), true)) {
		return false;
	}

	$old = wholesale_trade_status($user->ID);
	update_user_meta($user->ID, '_trade_status', $status);
	wholesale_trade_log($user->ID, array('status' => $status, 'from' => $old, 'note' => (string) $message));

	// Never touch staff roles; everyone else gets one of the two trade roles.
	if (!wholesale_trade_is_staff($user->ID)) {
		$user->set_role('approved' === $status ? 'trade_customer' : 'trade_pending');
	}
	if ('approved' === $status) {
		update_user_meta($user->ID, '_trade_approved_at', time());
		wholesale_trade_fill_account_details($user->ID);
	}

	if ($notify) {
		wholesale_trade_send_status_email($user->ID, $status, $message);
	}
	do_action('wholesale_trade_status_changed', $user->ID, $status, $old);
	return true;
}

/**
 * Plain, branded email to the applicant or staff. Goes through wp_mail, so the email log records it.
 * Replies go to the trade inbox, or to $reply_email (staff alerts reply to the applicant).
 */
function wholesale_trade_mail($to, $subject, $paragraphs, $button = array(), $reply_email = '', $reply_name = '')
{
	$brand = wholesale_trade_brand();
	$body = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#17222e;max-width:560px">';
	$body .= '<p style="font-size:18px;font-weight:700;color:#0d2236;margin:0 0 16px">' . esc_html($brand['name']) . '</p>';
	foreach (array_filter((array) $paragraphs, 'strlen') as $p) {
		$body .= '<p style="margin:0 0 14px">' . nl2br(esc_html($p)) . '</p>';
	}
	if (!empty($button['url'])) {
		$body .= '<p style="margin:22px 0"><a href="' . esc_url($button['url']) . '" style="background:#0b74a3;color:#fff;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:700;display:inline-block">' . esc_html($button['label']) . '</a></p>';
	}
	$body .= '<p style="margin:24px 0 0;color:#5c6b7a;font-size:13px">' . esc_html($brand['name']) . ' · ' . esc_html($brand['phone']) . ' · ' . esc_html($brand['email']) . '<br>' . esc_html($brand['legal_name']) . ', trade accounts only.</p></div>';

	// wp_mail() splits Reply-To on commas, so the name loses them.
	$reply = is_email($reply_email)
		? trim(str_replace(array(',', '"', '<', '>'), '', (string) $reply_name) . ' <' . $reply_email . '>')
		: $brand['name'] . ' <' . $brand['email'] . '>';
	$headers = array('Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $reply);
	return wp_mail($to, $subject, $body, $headers);
}

function wholesale_trade_send_status_email($user_id, $status, $message = '')
{
	$user = get_userdata((int) $user_id);
	if (!$user) {
		return;
	}
	$name = $user->first_name ? $user->first_name : $user->display_name;
	$login = add_query_arg('redirect_ulr', rawurlencode(home_url('/channel-letters/')), home_url('/login/'));
	$status_url = home_url('/trade-account/');

	switch ($status) {
		case 'pending_review':
			wholesale_trade_mail($user->user_email, 'We received your trade account application', array(
				"Hi {$name},",
				'Thanks for confirming your email. Your trade account application is now with our team for review. We check every business by hand, usually within one business day, and email you as soon as it is decided.',
				'Approved accounts see trade pricing on every channel letter type and can order online.',
			), array('url' => $status_url, 'label' => 'Check your application'));
			break;
		case 'welcome':
			wholesale_trade_mail($user->user_email, 'Your trade account is open', array(
				"Hi {$name},",
				'Thanks for applying. Your trade account is open now: log in any time to see trade pricing on all 7 channel letter types, price designs in the channel letter builder and order online.',
				'Your login is this email address and the password you chose. Our team looks over every new account and may contact you for a resale certificate or business license.',
			), array('url' => $login, 'label' => 'See trade pricing'));
			break;
		case 'approved':
			wholesale_trade_mail($user->user_email, 'Your trade account is approved', array(
				"Hi {$name},",
				'Your trade account is approved. Log in to see trade pricing on all 7 channel letter types, price designs in the channel letter builder and order online.',
				$message ? 'Note from our team: ' . $message : '',
			), array('url' => $login, 'label' => 'Log in and see trade pricing'));
			break;
		case 'info_requested':
			wholesale_trade_mail($user->user_email, 'We need a little more information', array(
				"Hi {$name},",
				'Before we can approve your trade account, we need a bit more information:',
				$message ? $message : 'Please reply to this email with your business details.',
				'Reply to this email or call us, and we will finish the review.',
			), array('url' => $status_url, 'label' => 'View your application'));
			break;
		case 'rejected':
			wholesale_trade_mail($user->user_email, 'Your trade account application', array(
				"Hi {$name},",
				'Thank you for applying. We are not able to approve a trade account for this business at this time.',
				$message ? 'Reason: ' . $message : '',
				'Buying a sign for your own business? Our retail site, ' . wholesale_trade_brand()['retail_name'] . ', sells to everyone: ' . wholesale_trade_brand()['retail_url'],
			));
			break;
		case 'suspended':
			wholesale_trade_mail($user->user_email, 'Your trade account is on hold', array(
				"Hi {$name},",
				'Your trade account is on hold, so trade pricing and ordering are paused.',
				$message ? 'Reason: ' . $message : '',
				'Please contact us to sort it out.',
			));
			break;
	}
}

/** Count of applications waiting on staff (menu bubble). */
function wholesale_trade_pending_count()
{
	$count = get_transient('wholesale_trade_pending_count');
	if (false === $count) {
		$count = count(get_users(array(
			'meta_key' => '_trade_status',
			'meta_value' => 'pending_review',
			'fields' => 'ID',
			'number' => 500,
		)));
		set_transient('wholesale_trade_pending_count', $count, 5 * MINUTE_IN_SECONDS);
	}
	return (int) $count;
}
add_action('wholesale_trade_status_changed', static function () {
	delete_transient('wholesale_trade_pending_count');
});

/** Logging out empties the PHP session (cart, designs, pending payment), so the next person on this browser starts clean. */
add_action('wp_logout', static function () {
	if (!wholesale_is_trade_site() || PHP_SESSION_ACTIVE !== session_status()) {
		return;
	}
	$_SESSION = array();
	if (!headers_sent()) {
		session_regenerate_id(true);
	}
});

/*
 * Login throttle for wp-login.php and the theme's /login/ page (both go through
 * wp_signon()). 6 failed logins within 15 minutes from one IP block that IP for
 * 15 minutes. 20 failures for one account block that account for 15 minutes, but
 * only for IPs that have failed themselves, so an attack on an account from other
 * IPs cannot lock its owner out.
 * Blocked tries are not counted, so a block always ends 15 minutes after the
 * last real failure: nothing is ever blocked for good.
 */
define('WHOLESALE_TRADE_LOGIN_MAX_FAILS', 6);
define('WHOLESALE_TRADE_LOGIN_WINDOW', 15 * MINUTE_IN_SECONDS);
define('WHOLESALE_TRADE_LOGIN_MAX_ACCOUNT_FAILS', 20);

/** Whether an IP is one of Cloudflare's published edge ranges. */
function wholesale_trade_ip_is_cloudflare($ip)
{
	$bin = @inet_pton((string) $ip);
	if (false === $bin) {
		return false;
	}
	$ranges = array(
		'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
		'197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
		'2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
	);
	foreach ($ranges as $range) {
		list($net, $bits) = explode('/', $range);
		$net = inet_pton($net);
		if (strlen($net) !== strlen($bin)) {
			continue;
		}
		$bytes = intdiv((int) $bits, 8);
		$rest = (int) $bits % 8;
		if (substr($bin, 0, $bytes) !== substr($net, 0, $bytes)) {
			continue;
		}
		$mask = chr((0xFF << (8 - $rest)) & 0xFF);
		if (0 === $rest || (substr($bin, $bytes, 1) & $mask) === (substr($net, $bytes, 1) & $mask)) {
			return true;
		}
	}
	return false;
}

/** The visitor's IP for the throttle. Cloudflare's client-IP header counts only on requests that really come from Cloudflare. */
function wholesale_trade_login_ip()
{
	$ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
	$cf = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? trim((string) wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP'])) : '';
	return ($cf && filter_var($cf, FILTER_VALIDATE_IP) && wholesale_trade_ip_is_cloudflare($ip)) ? $cf : $ip;
}

/** Transient keys for a login try: the IP, and the account (an email counts as its username). */
function wholesale_trade_login_throttle_keys($username)
{
	$salt = wp_salt('auth');
	$keys = array('ip' => 'wholesale_lf_ip_' . substr(hash_hmac('sha256', wholesale_trade_login_ip(), $salt), 0, 20));
	$name = strtolower(trim((string) $username));
	if (is_email($name)) {
		$account = get_user_by('email', $name);
		$name = $account ? strtolower($account->user_login) : $name;
	}
	if ('' !== $name) {
		$keys['user'] = 'wholesale_lf_u_' . substr(hash_hmac('sha256', $name, $salt), 0, 20);
	}
	return $keys;
}

/** Whether a stored failure record has reached the limit. */
function wholesale_trade_login_record_locked($record)
{
	return is_array($record) && (int) ($record['n'] ?? 0) >= WHOLESALE_TRADE_LOGIN_MAX_FAILS;
}

/** Add one failure to a stored record (a record older than the window starts over). */
function wholesale_trade_login_record_add($record, $now)
{
	if (!is_array($record) || (int) ($record['t'] ?? 0) < $now - WHOLESALE_TRADE_LOGIN_WINDOW) {
		$record = array('n' => 0, 't' => $now);
	}
	$record['n'] = (int) ($record['n'] ?? 0) + 1;
	return $record;
}

add_filter('authenticate', static function ($user, $username = '') {
	$keys = wholesale_trade_login_throttle_keys($username);
	$ip_record = get_transient($keys['ip']);
	$user_record = isset($keys['user']) ? get_transient($keys['user']) : false;
	$ip_failed = is_array($ip_record) && (int) ($ip_record['n'] ?? 0) > 0;
	if (wholesale_trade_login_record_locked($ip_record) || ($ip_failed && is_array($user_record) && (int) ($user_record['n'] ?? 0) >= WHOLESALE_TRADE_LOGIN_MAX_ACCOUNT_FAILS)) {
		return new WP_Error('wholesale_login_locked', '<strong>Error:</strong> Too many failed login attempts. Please wait 15 minutes and try again.');
	}
	// One message for an unknown account and a wrong password, so the form never reveals which accounts exist.
	if (is_wp_error($user) && array_intersect($user->get_error_codes(), array('invalid_username', 'invalid_email', 'incorrect_password'))) {
		return new WP_Error('invalid_credentials', '<strong>Error:</strong> The username, email address or password is incorrect. <a href="' . esc_url(wp_lostpassword_url()) . '">Lost your password?</a>');
	}
	return $user;
}, 30, 2);

add_action('wp_login_failed', static function ($username, $error = null) {
	if (is_wp_error($error) && in_array('wholesale_login_locked', $error->get_error_codes(), true)) {
		return;
	}
	foreach (wholesale_trade_login_throttle_keys($username) as $key) {
		set_transient($key, wholesale_trade_login_record_add(get_transient($key), time()), WHOLESALE_TRADE_LOGIN_WINDOW);
	}
}, 10, 2);

/** A successful login clears that account's earlier failures (the IP count stays). */
add_action('wp_login', static function ($user_login) {
	$keys = wholesale_trade_login_throttle_keys($user_login);
	if (isset($keys['user'])) {
		delete_transient($keys['user']);
	}
});

add_filter('shake_error_codes', static function ($codes) {
	return array_merge((array) $codes, array('invalid_credentials', 'wholesale_login_locked'));
});
