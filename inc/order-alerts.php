<?php
/**
 * New-order alerts that do not depend on email alone.
 *
 * 1. Every new checkout or payment-ticket order is marked "admin email due".
 * 2. A phone/desktop push goes to staff devices subscribed in Live Chat
 *    (the chat plugin's existing web push), independent of email delivery.
 * 3. A five-minute watchdog re-sends the admin email for any order whose email
 *    never went out (request died, or every automatic retry failed), and sends
 *    any missed push.
 * 4. When an automatic email retry delivers a "New Order" email, the order is
 *    marked as emailed.
 *
 * Loaded from the end of inc/email-log.php.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

// How many times the watchdog itself re-sends one order's admin email.
define('WHOLESALE_ORDER_ALERT_MAX_RESENDS', 3);

/**
 * Mark a new customer order as needing the admin email, and push it to staff
 * devices. Runs after the post and all its meta are saved.
 */
function wholesale_order_alert_on_insert($post_id, $post, $update, $post_before)
{
	if ($update || !($post instanceof WP_Post) || 'order' !== $post->post_type) {
		return;
	}
	if (in_array($post->post_status, array('auto-draft', 'draft', 'trash'), true)) {
		return;
	}
	// Checkout and payment tickets always set a payment status; a blank
	// "Add New" order typed in wp-admin does not, and needs no alert.
	if ('' === (string) get_post_meta($post_id, '_payment_status', true)) {
		return;
	}

	update_post_meta($post_id, '_wholesale_admin_email_due', time());
	wholesale_order_alert_push($post_id);
}
add_action('wp_after_insert_post', 'wholesale_order_alert_on_insert', 20, 4);

/**
 * True when pushes may go to the owner's real devices: on the live store, or
 * on a local copy only when WHOLESALE_ORDER_PUSH_LOCAL is defined (local push
 * subscriptions are copies of the live ones).
 */
function wholesale_order_alert_push_allowed()
{
	if (defined('WHOLESALE_ORDER_PUSH_LOCAL') && WHOLESALE_ORDER_PUSH_LOCAL) {
		return true;
	}

	return wholesale_order_alert_is_live_host();
}

/**
 * True only when the site really runs on a live store domain (never a local copy).
 */
function wholesale_order_alert_is_live_host()
{
	$host = preg_replace('/^www\./', '', strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST)));

	return in_array($host, array('storefrontsignonline.com', 'storefrontsignwholesale.com', 'mystorefrontsigns.com'), true);
}

/**
 * Queue a web push about a new order to every staff device subscribed in
 * Live Chat. Sent after the response, so checkout is not slowed down.
 */
function wholesale_order_alert_push($post_id)
{
	if (!class_exists('\SFS\LiveChat\Realtime') || !class_exists('\SFS\LiveChat\Repo\PushSubs') || !\SFS\LiveChat\Settings::push_enabled()) {
		return false;
	}
	if (!wholesale_order_alert_push_allowed() || get_post_meta($post_id, '_wholesale_order_alert_pushed', true)) {
		return false;
	}

	// Claim it now so the watchdog and a second hook call cannot push twice.
	update_post_meta($post_id, '_wholesale_order_alert_pushed', time());

	\SFS\LiveChat\Realtime::defer(static function () use ($post_id) {
		$user_ids = get_users(array('capability' => 'sfs_manage_chats', 'fields' => 'ID'));
		$subs = $user_ids ? \SFS\LiveChat\Repo\PushSubs::for_users(array_map('intval', $user_ids)) : array();
		if (!$subs) {
			// Nobody to push to: let the watchdog try again later.
			delete_post_meta($post_id, '_wholesale_order_alert_pushed');
			update_option('wholesale_order_alert_no_devices', time(), false);
			return;
		}

		$labels = array('paid' => 'Paid by card', 'paid_offline' => 'Paid (offline)', 'needs_review' => 'Card - verify in Converge', 'manual' => 'Not paid - collect payment', 'refunded' => 'Refunded');
		$status = function_exists('wholesale_get_payment_status') ? wholesale_get_payment_status($post_id) : '';
		$billing = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true)) : array();
		$name = trim((isset($billing['billing_fname']) ? $billing['billing_fname'] : '') . ' ' . (isset($billing['billing_lname']) ? $billing['billing_lname'] : ''));
		$total = function_exists('wholesale_order_total') ? (float) wholesale_order_total($post_id) : 0;
		$emailed = get_post_meta($post_id, '_wholesale_admin_order_email_sent', true);
		$local = !wholesale_order_alert_is_live_host();

		$payload = array(
			'title' => ($local ? '[LOCAL] ' : '') . sprintf('New order #%s - $%s', wholesale_order_number($post_id), number_format($total, 2)),
			'body' => implode(' · ', array_filter(array($name, isset($labels[$status]) ? $labels[$status] : '', $emailed ? '' : 'Email not sent yet'))),
			'icon' => \SFS\LiveChat\Settings::logo_url(),
			'url' => admin_url('post.php?post=' . (int) $post_id . '&action=edit'),
			'tag' => 'sfs-order-' . (int) $post_id,
			// The chat service worker only opens admin URLs for the 'admin' audience.
			'audience' => 'admin',
		);

		$jobs = array();
		foreach ($subs as $sub) {
			$jobs[] = array(
				'subscription' => array(
					'endpoint' => $sub->endpoint,
					'keys' => array('p256dh' => $sub->p256dh, 'auth' => $sub->auth),
				),
				'payload' => $payload,
				'ttl' => DAY_IN_SECONDS,
				'urgency' => 'high',
			);
		}
		\SFS\LiveChat\Realtime::push($jobs);
	});

	return true;
}

/**
 * Order post id for an order number like "Z8GXDVXVG4", or 0.
 */
function wholesale_order_alert_find_by_number($number)
{
	global $wpdb;
	$number = strtoupper(trim((string) $number));
	if ('' === $number) {
		return 0;
	}

	return (int) $wpdb->get_var($wpdb->prepare(
		"SELECT pm.post_id FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE p.post_type = 'order' AND pm.meta_key = 'order_id' AND (UPPER(pm.meta_value) = %s OR UPPER(pm.meta_value) = %s)
		 ORDER BY pm.post_id DESC LIMIT 1",
		$number,
		'ORDER_' . $number
	));
}

/**
 * An automatic retry delivered a "New Order" admin email: mark the order emailed.
 */
function wholesale_order_alert_retry_succeeded($row)
{
	if (!is_array($row) || !preg_match('/^New Order #([A-Z0-9_]+) - /i', (string) $row['subject'], $m)) {
		return;
	}
	$post_id = wholesale_order_alert_find_by_number($m[1]);
	if ($post_id && !get_post_meta($post_id, '_wholesale_admin_order_email_sent', true)) {
		update_post_meta($post_id, '_wholesale_admin_order_email_sent', current_time('mysql'));
	}
}
add_action('wholesale_email_retry_succeeded', 'wholesale_order_alert_retry_succeeded');

/**
 * Keep the watchdog scheduled. A site push replaces the cron option, so this
 * re-creates it on the next page load.
 */
function wholesale_order_alert_schedule()
{
	if (!wp_next_scheduled('wholesale_order_alert_check')) {
		wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'wholesale_five_minutes', 'wholesale_order_alert_check');
	}
}
add_action('init', 'wholesale_order_alert_schedule');

/**
 * Watchdog: for orders from the last 3 days that are marked "admin email due"
 * but were never emailed, send the admin email again, unless an automatic
 * retry is already queued for it. Also sends any push that did not go out.
 */
function wholesale_order_alert_check()
{
	if (get_transient('wholesale_order_alert_lock')) {
		return;
	}
	set_transient('wholesale_order_alert_lock', 1, 4 * MINUTE_IN_SECONDS);

	global $wpdb;
	$now = time();
	$ids = $wpdb->get_col($wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wholesale_admin_email_due' AND CAST(meta_value AS UNSIGNED) >= %d ORDER BY post_id DESC LIMIT 50",
		$now - 3 * DAY_IN_SECONDS
	));

	foreach (array_map('intval', $ids) as $post_id) {
		$post = get_post($post_id);
		if (!$post || 'order' !== $post->post_type || 'trash' === $post->post_status) {
			continue;
		}

		if (!get_post_meta($post_id, '_wholesale_order_alert_pushed', true)) {
			wholesale_order_alert_push($post_id);
		}

		if (get_post_meta($post_id, '_wholesale_admin_order_email_sent', true)) {
			continue;
		}
		$due = (int) get_post_meta($post_id, '_wholesale_admin_email_due', true);
		if ($now - $due < 10 * MINUTE_IN_SECONDS) {
			continue; // The checkout request may still be sending it.
		}
		$tries = (int) get_post_meta($post_id, '_wholesale_admin_email_resends', true);
		if ($tries >= WHOLESALE_ORDER_ALERT_MAX_RESENDS) {
			continue;
		}

		// Leave it to the email log's own retry while one is queued or running.
		if (function_exists('wholesale_email_log_table')) {
			$table = wholesale_email_log_table();
			$busy = (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE subject LIKE %s AND ((status = 'failed' AND next_retry IS NOT NULL) OR status = 'pending')",
				'New Order #' . $wpdb->esc_like(wholesale_order_number($post_id)) . ' %'
			));
			if ($busy) {
				continue;
			}
		}

		update_post_meta($post_id, '_wholesale_admin_email_resends', $tries + 1);
		wholesale_send_new_order_admin_email($post_id);
	}

	delete_transient('wholesale_order_alert_lock');
}
add_action('wholesale_order_alert_check', 'wholesale_order_alert_check');

/**
 * Red notice for admins when a recent order still has no admin email after
 * the watchdog gave up.
 */
function wholesale_order_alert_admin_notice()
{
	if (!current_user_can('manage_options')) {
		return;
	}
	global $wpdb;
	$ids = $wpdb->get_col($wpdb->prepare(
		"SELECT d.post_id FROM {$wpdb->postmeta} d
		 JOIN {$wpdb->posts} p ON p.ID = d.post_id AND p.post_type = 'order' AND p.post_status <> 'trash'
		 LEFT JOIN {$wpdb->postmeta} s ON s.post_id = d.post_id AND s.meta_key = '_wholesale_admin_order_email_sent'
		 WHERE d.meta_key = '_wholesale_admin_email_due' AND CAST(d.meta_value AS UNSIGNED) BETWEEN %d AND %d AND s.meta_id IS NULL
		 ORDER BY d.post_id DESC LIMIT 10",
		time() - 7 * DAY_IN_SECONDS,
		time() - 15 * MINUTE_IN_SECONDS
	));
	if (!$ids) {
		return;
	}
	$links = array_map(static function ($id) {
		return '<a href="' . esc_url(admin_url('post.php?post=' . (int) $id . '&action=edit')) . '">#' . esc_html(wholesale_order_number($id)) . '</a>';
	}, $ids);
	echo '<div class="notice notice-error"><p><strong>New-order email could not be sent for:</strong> ' . implode(', ', $links) . '. Check <a href="' . esc_url(admin_url('admin.php?page=wholesale-emails')) . '">Emails → Email Log</a>.</p></div>';
}
add_action('admin_notices', 'wholesale_order_alert_admin_notice');
