<?php
/**
 * Trade accounts: status, roles, history and emails.
 *
 * Status lives in user meta `_trade_status`:
 *   email_unverified -> pending_review -> approved | info_requested | rejected
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

/** Staff see prices and manage accounts. */
function wholesale_trade_is_staff($user_id = null)
{
	$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
	return $user_id && user_can($user_id, 'list_users');
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
	}

	if ($notify) {
		wholesale_trade_send_status_email($user->ID, $status, $message);
	}
	do_action('wholesale_trade_status_changed', $user->ID, $status, $old);
	return true;
}

/** Plain, branded email to the applicant or staff. Goes through wp_mail, so the email log records it. */
function wholesale_trade_mail($to, $subject, $paragraphs, $button = array())
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

	$headers = array('Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $brand['name'] . ' <' . $brand['email'] . '>');
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
