<?php
/**
 * Review request email: one "How did it turn out?" email per order, a few days after it
 * arrives, with a signed review link for each item (retails_order_review_url(), so guest
 * checkouts can review too) and the Google review link.
 *
 * The shipped email already carries review links, but it arrives before the product does.
 * Genuine product reviews are what give product pages their star rating in Google
 * (aggregateRating in the Product markup), so this asks again once the customer has it.
 *
 * When an order counts as arrived comes from its status history (_status_log):
 * - Delivered (carrier tracking): the time it became Delivered, plus the delay setting.
 * - Shipped without tracking (still Completed): the time it shipped, plus 7 days for
 *   transit, plus the delay.
 * Sign orders (channel letters) wait 7 more days, since they still have to be installed.
 * Orders with a delivery issue, returned, cancelled or refunded get nothing, and neither
 * does an order whose items were all reviewed already.
 *
 * Sends only on the live site (never from a local copy, whose orders are real customers),
 * hourly between 9am and 6pm Pacific, at most 20 per run, once per order.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const RETAILS_REVIEW_REQUEST_TRANSIT_DAYS = 7;
const RETAILS_REVIEW_REQUEST_INSTALL_DAYS = 7;
// Never ask about an order that arrived longer ago than this.
const RETAILS_REVIEW_REQUEST_MAX_AGE_DAYS = 45;
// On first run, orders that arrived up to this long before it still get an email.
const RETAILS_REVIEW_REQUEST_LOOKBACK_DAYS = 14;

/**
 * Only the live site emails customers. A local or staging copy has the same orders and
 * customer addresses, and marking orders there would stop live from sending after a push.
 */
function retails_review_requests_can_send()
{
	$host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
	return (bool) apply_filters('retails_review_requests_can_send', in_array($host, array('storefrontsignonline.com', 'www.storefrontsignonline.com'), true));
}

function retails_review_requests_enabled()
{
	return retails_setting_enabled('review_request_email') && retails_setting_enabled('allow_verified_reviews');
}

/**
 * The earliest arrival time that still gets an email: set once, the first time this runs,
 * so turning the feature on never emails customers about old orders.
 */
function retails_review_requests_since()
{
	$since = (int) get_option('retails_review_request_since', 0);
	if (!$since) {
		$since = time() - RETAILS_REVIEW_REQUEST_LOOKBACK_DAYS * DAY_IN_SECONDS;
		update_option('retails_review_request_since', $since, false);
	}
	return $since;
}

/**
 * When the order arrived (or is assumed to have), as a timestamp, or 0 if unknown.
 */
function retails_review_request_arrival_time($order_id)
{
	$status = (string) get_post_status($order_id);
	$shipped = 0;
	$delivered = 0;
	foreach ((array) get_post_meta($order_id, '_status_log') as $entry) {
		if (!is_array($entry) || empty($entry['time'])) {
			continue;
		}
		$to = (string) ($entry['to'] ?? '');
		$time = (int) $entry['time'];
		if (!$shipped && retails_order_is_shipped($to)) {
			$shipped = $time;
		}
		if ('delivered' === $to) {
			$delivered = max($delivered, $time);
		}
	}

	if ('delivered' === $status) {
		return $delivered ?: ($shipped ? $shipped + RETAILS_REVIEW_REQUEST_TRANSIT_DAYS * DAY_IN_SECONDS : 0);
	}
	if ('completed' === $status && $shipped) {
		return $shipped + RETAILS_REVIEW_REQUEST_TRANSIT_DAYS * DAY_IN_SECONDS;
	}
	return 0;
}

/**
 * When the review request for this order is due, or 0 if it never will be.
 */
function retails_review_request_due_time($order_id)
{
	$arrived = retails_review_request_arrival_time($order_id);
	if (!$arrived) {
		return 0;
	}
	$days = max(1, absint(retails_get_setting('review_request_days')));
	if ('sign' === retails_order_noun($order_id)) {
		$days += RETAILS_REVIEW_REQUEST_INSTALL_DAYS;
	}
	return $arrived + $days * DAY_IN_SECONDS;
}

/**
 * Why an order would not get a review request now ('' when it is due).
 */
function retails_review_request_skip_reason($order_id, $now = null)
{
	$now = null === $now ? time() : (int) $now;
	if (get_post_meta($order_id, '_review_request_sent', true)) {
		return 'sent';
	}
	$status = (string) get_post_status($order_id);
	if (!in_array($status, array('delivered', 'completed'), true)) {
		return 'status';
	}
	$arrived = retails_review_request_arrival_time($order_id);
	if (!$arrived) {
		return 'no-ship-date';
	}
	if ($arrived < retails_review_requests_since() || $arrived < $now - RETAILS_REVIEW_REQUEST_MAX_AGE_DAYS * DAY_IN_SECONDS) {
		return 'too-old';
	}
	if (retails_review_request_due_time($order_id) > $now) {
		return 'not-yet';
	}
	$billing = retails_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
	if (!is_email($billing['billing_email'] ?? '')) {
		return 'no-email';
	}
	if (!retails_order_unreviewed_products($order_id)) {
		return 'reviewed';
	}
	return '';
}

/**
 * The email body (already escaped) for retails_send_order_email().
 */
function retails_review_request_email_body($order_id)
{
	$links = retails_order_unreviewed_products($order_id);
	$google_url = function_exists('retails_order_google_review_url') ? retails_order_google_review_url($order_id) : '';
	$noun = retails_order_noun($order_id);
	$thing = 'order' === $noun ? 'your order' : 'your ' . $noun;
	$choose = 'order' === $noun ? 'what to order' : "the right {$noun}";

	$html = '<p style="margin:0 0 12px;">' . esc_html('sign' === $noun
		? 'Your sign should be up by now. We would love to know how it turned out.'
		: "We hope {$thing} arrived in great shape. How did it turn out?") . '</p>'
		. '<p style="margin:0 0 16px;color:#5b6b7b;">' . esc_html("A short review helps other business owners choose {$choose}, and it takes about a minute. No account needed.") . '</p>';

	foreach ($links as $product_id => $url) {
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 10px;border:1px solid #e3e9ef;border-radius:8px;"><tr>'
			. '<td style="padding:12px 14px;font-weight:700;color:#0d2e4d;">' . esc_html(get_the_title($product_id)) . '</td>'
			. '<td align="right" style="padding:12px 14px;white-space:nowrap;"><a href="' . esc_url($url) . '" style="display:inline-block;padding:9px 16px;border-radius:6px;background:#f5ad27;color:#0d2e4d;font-weight:700;text-decoration:none;">&#9733;&#9733;&#9733;&#9733;&#9733; Write a review</a></td>'
			. '</tr></table>';
	}
	if ($google_url) {
		$html .= '<p style="margin:16px 0 0;">Happy with our service? <a href="' . esc_url($google_url) . '" style="color:#1287b5;font-weight:700;">Review us on Google</a> too.</p>';
	}
	$html .= '<p style="margin:16px 0 0;color:#5b6b7b;font-size:14px;">If something is not right, please reply to this email or call us first so we can fix it.</p>';
	return $html;
}

/**
 * Sends the review request for one order. Returns true when sent.
 */
function retails_send_review_request($order_id)
{
	$number = retails_order_number($order_id);
	$noun = retails_order_noun($order_id);
	$subject = 'sign' === $noun ? sprintf('How does your new sign look? (order #%s)', $number) : sprintf('How did your order #%s turn out?', $number);

	$sent = retails_send_order_email($order_id, $subject, retails_review_request_email_body($order_id));
	// Marked even when the send fails: the email log retries it, so it is never sent twice.
	update_post_meta($order_id, '_review_request_sent', time());
	add_post_meta($order_id, '_status_email_log', current_time('mysql') . ' review-request' . ($sent ? '' : ' (send failed, will retry)'));
	return (bool) $sent;
}

/**
 * Shipped orders that may still need a review request (cheap pre-filter; the skip check decides).
 */
function retails_review_request_candidate_ids()
{
	return get_posts(array(
		'post_type' => 'order',
		'post_status' => array('delivered', 'completed'),
		'posts_per_page' => 200,
		'fields' => 'ids',
		'orderby' => 'modified',
		'order' => 'DESC',
		'date_query' => array(array('after' => gmdate('Y-m-d', time() - (RETAILS_REVIEW_REQUEST_MAX_AGE_DAYS + 120) * DAY_IN_SECONDS), 'column' => 'post_date_gmt')),
		'meta_query' => array(array('key' => '_review_request_sent', 'compare' => 'NOT EXISTS')),
	));
}

function retails_process_review_requests()
{
	if (!retails_review_requests_enabled() || !retails_review_requests_can_send()) {
		return;
	}
	$hour = (int) wp_date('G');
	if ($hour < 9 || $hour >= 18) {
		return;
	}
	$sent = 0;
	foreach (retails_review_request_candidate_ids() as $order_id) {
		if ($sent >= 20) {
			break;
		}
		if ('' === retails_review_request_skip_reason($order_id)) {
			retails_send_review_request($order_id);
			$sent++;
		}
	}
}
add_action('retails_review_requests', 'retails_process_review_requests');

add_action('init', function () {
	if (!wp_next_scheduled('retails_review_requests')) {
		wp_schedule_event(time() + 10 * MINUTE_IN_SECONDS, 'hourly', 'retails_review_requests');
	}
});

/**
 * Settings page summary: what was sent and what is coming up.
 */
function retails_review_request_summary()
{
	$sent_30 = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => array(array('key' => '_review_request_sent', 'value' => time() - 30 * DAY_IN_SECONDS, 'compare' => '>=', 'type' => 'NUMERIC')),
	));
	$due_now = 0;
	$upcoming = 0;
	$next = 0;
	foreach (retails_review_request_candidate_ids() as $order_id) {
		$reason = retails_review_request_skip_reason($order_id);
		if ('' === $reason) {
			$due_now++;
		} elseif ('not-yet' === $reason) {
			$upcoming++;
			$due = retails_review_request_due_time($order_id);
			$next = $next ? min($next, $due) : $due;
		}
	}
	return array('sent_30' => count($sent_30), 'due_now' => $due_now, 'upcoming' => $upcoming, 'next' => $next);
}

/** Days after delivery: 1 to 60. */
function retails_sanitize_review_request_days($value)
{
	return max(1, min(60, absint($value)));
}
