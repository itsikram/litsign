<?php
/**
 * Visitor Insights → Live Chat AI.
 *
 * Three filters the chat plugin (sfs-live-chat 1.8.0+) calls:
 * - sfs_chat_ai_activity: what a chat visitor did on the site (this visit and
 *   before), compact and without personal data, so the AI can tailor its help.
 * - sfs_chat_ai_outcome:  how far a chat visitor got after the chat started
 *   (product, price, cart, checkout, order), for the AI sales results and coach.
 * - sfs_chat_ai_baseline: the same funnel for all human visits, to compare.
 *
 * Matched by the chat visitor's sso_vid cookie (vi_key), else their account,
 * exactly like the inbox's visitor details (wholesale_vi_chat_visitor_insights).
 * No IP, location, name, email, campaign id or form contents ever leave here.
 *
 * @package tr-retails
 */

defined('ABSPATH') || exit;

/** Prepared WHERE clause for a chat visitor's visits, or '' when there is nothing to match on. */
function wholesale_vi_ai_match_sql($vi_key, $user_id)
{
	global $wpdb;
	$vi_key = strtolower((string) $vi_key);
	if (preg_match('/^[a-f0-9]{32}$/', $vi_key)) {
		return $wpdb->prepare('visitor_key = %s', $vi_key);
	}
	if ((int) $user_id > 0) {
		return $wpdb->prepare('user_id = %d', (int) $user_id);
	}

	return '';
}

/** Site path of a recorded URL: no query, fragment or local sub-folder ("/product/13oz-vinyl-banner/"). */
function wholesale_vi_ai_path($url)
{
	$path = (string) wp_parse_url((string) $url, PHP_URL_PATH);
	$base = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
	if ('' !== $base && 0 === strpos($path, $base . '/')) {
		$path = substr($path, strlen($base));
	}

	return '' === $path ? '/' : mb_substr($path, 0, 160);
}

/** A page title without the site name ("13oz Vinyl Banners – Custom Printed"). */
function wholesale_vi_ai_title($label)
{
	$title = trim((string) preg_split('/\s+[|]\s+/u', (string) $label)[0]);

	return mb_substr(trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($title))), 0, 90);
}

/** Short plain line from a recorded label (error text, survey answer), or ''. */
function wholesale_vi_ai_line($text, $length = 140)
{
	$text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $text)));

	return '' === $text ? '' : mb_substr($text, 0, $length);
}

/** Funnel step a recorded event proves (events keep no page type, so pages are told apart by path). */
function wholesale_vi_ai_event_step($type, $path)
{
	switch ($type) {
		case 'purchase':
			return 7;
		case 'payment_start':
		case 'payment_error':
			return 6;
		case 'add_to_cart':
			return 4;
		case 'configure':
		case 'price_quote':
		case 'design_upload':
			return 3;
		case 'pageview':
			if (false !== strpos($path, '/checkout')) {
				return 5;
			}
			return (0 === strpos($path, '/product/') || false !== strpos($path, 'channel-letter-builder')) ? 2 : 1;
	}

	return 1;
}

/**
 * sfs_chat_ai_activity: what this chat visitor did, for the AI's prompt.
 *
 * @param mixed  $activity     Earlier filter value (null).
 * @param string $vi_key       The visitor's sso_vid cookie.
 * @param object $chat_visitor Chat visitor row (user_id is used when there is no cookie).
 * @return array|mixed
 */
function wholesale_vi_ai_activity($activity, $vi_key, $chat_visitor)
{
	global $wpdb;
	$match = wholesale_vi_ai_match_sql($vi_key, (int) ($chat_visitor->user_id ?? 0));
	if ('' === $match) {
		return $activity;
	}
	$sessions = wholesale_vi_table('sessions');
	$events_table = wholesale_vi_table('events');
	$visits = $wpdb->get_results("SELECT * FROM {$sessions} WHERE {$match} ORDER BY last_seen DESC LIMIT 8"); // phpcs:ignore WordPress.DB.PreparedSQL -- prepared in wholesale_vi_ai_match_sql().
	if (!$visits) {
		return $activity;
	}
	$visit = $visits[0];
	$now = time();
	$stages = wholesale_vi_stages();
	$channels = wholesale_vi_channels();
	$events = array_reverse($wpdb->get_results($wpdb->prepare("SELECT type, url, label, value, seconds FROM {$events_table} WHERE session_id = %d ORDER BY id DESC LIMIT 200", $visit->id)));

	$products = array();
	$priced = array();
	$price_problems = array();
	$cart_problems = array();
	$pay_problems = array();
	$frustrated = array();
	$counts = array('add_to_cart' => 0, 'cart_remove' => 0);
	$flags = array('checkout' => false, 'pay' => false, 'design' => false, 'quote' => false, 'contact' => false, 'not_found' => false);
	$survey = '';
	$options_started = array();
	foreach ($events as $event) {
		$path = wholesale_vi_ai_path($event->url);
		switch ($event->type) {
			case 'pageview':
				if ('404' === (string) $event->value) {
					$flags['not_found'] = true;
				}
				if (false !== strpos($path, '/checkout')) {
					$flags['checkout'] = true;
				}
				if (0 === strpos($path, '/product/')) {
					if (!isset($products[$path])) {
						$products[$path] = array('title' => wholesale_vi_ai_title($event->label), 'path' => $path, 'views' => 0, 'seconds' => 0);
					}
					$products[$path]['views']++;
					$products[$path]['seconds'] += (int) $event->seconds;
					// Most recent last.
					$move = $products[$path];
					unset($products[$path]);
					$products[$path] = $move;
				}
				break;
			case 'price_quote':
				$priced[$path] = ($priced[$path] ?? 0) + 1;
				break;
			case 'configure':
				if ('' !== ($line = wholesale_vi_ai_line($event->label, 60))) {
					$options_started[$line] = true;
				}
				break;
			case 'price_error':
				if ('' !== ($line = wholesale_vi_ai_line($event->label))) {
					$price_problems[$line] = true;
				}
				break;
			case 'add_to_cart':
			case 'cart_remove':
				$counts[$event->type]++;
				break;
			case 'cart_error':
				if ('' !== ($line = wholesale_vi_ai_line($event->label))) {
					$cart_problems[$line] = true;
				}
				break;
			case 'payment_start':
				$flags['pay'] = true;
				break;
			case 'checkout_error':
			case 'payment_error':
				if ('' !== ($line = wholesale_vi_ai_line($event->label))) {
					$pay_problems[$line] = true;
				}
				break;
			case 'design_upload':
				$flags['design'] = true;
				break;
			case 'quote_submit':
				$flags['quote'] = true;
				break;
			case 'call_click':
			case 'email_click':
				$flags['contact'] = true;
				break;
			case 'rage_click':
				if ('' !== ($line = wholesale_vi_ai_line($event->label, 40))) {
					$frustrated[$line] = true;
				}
				break;
			case 'exit_survey':
				$survey = wholesale_vi_ai_line($event->label);
				break;
		}
	}

	$viewed = array();
	foreach (array_slice($products, -6, null, true) as $path => $p) {
		$row = array('title' => $p['title'] ?: $path, 'path' => $path, 'views' => $p['views'], 'seconds_on_page' => $p['seconds']);
		if (!empty($priced[$path])) {
			$row['times_priced'] = $priced[$path];
		}
		$viewed[] = $row;
	}

	$started = strtotime($visit->started_at . ' UTC');
	$last_seen = strtotime($visit->last_seen . ' UTC');
	// Over 2 hours quiet: today's visit was not recorded (tracker blocked), so this is their last visit, not this one.
	$stale = $now - $last_seen > 2 * HOUR_IN_SECONDS;
	$this_visit = array(
		'arrived_from' => (string) ($channels[$visit->channel] ?? ucfirst((string) $visit->channel)),
		'landed_on' => wholesale_vi_ai_path($visit->landing_url),
		'minutes_on_site' => max(0, (int) round((($stale ? $last_seen : $now) - $started) / 60)),
		'active_minutes' => (int) round((int) $visit->engaged_seconds / 60),
		'pages_viewed' => (int) $visit->pageviews,
		'device' => 'mobile' === $visit->device ? 'phone' : (string) $visit->device,
		'furthest_step' => (string) ($stages[(int) $visit->stage] ?? ''),
	);
	// The words they searched on Google before an ad click: their own description of what they need.
	if ('google_ads' === $visit->channel && '' !== trim((string) $visit->term) && !preg_match('/^\{.*\}$/', (string) $visit->term)) {
		$this_visit['searched_for'] = wholesale_vi_ai_line($visit->term, 80);
	}
	if ($viewed) {
		$this_visit['products_viewed'] = $viewed;
	}
	if ($options_started) {
		$this_visit['options_they_changed'] = array_slice(array_keys($options_started), -5);
	}
	if ($price_problems) {
		$this_visit['price_calculator_messages'] = array_slice(array_keys($price_problems), -3);
	}
	if ($counts['add_to_cart']) {
		$this_visit['cart_adds'] = $counts['add_to_cart'];
	}
	if ($counts['cart_remove']) {
		$this_visit['cart_removes'] = $counts['cart_remove'];
	}
	if ($cart_problems) {
		$this_visit['add_to_cart_messages'] = array_slice(array_keys($cart_problems), -2);
	}
	if ($flags['checkout']) {
		$this_visit['reached_checkout'] = true;
	}
	if ($flags['pay']) {
		$this_visit['tapped_pay'] = true;
	}
	if ($pay_problems) {
		$this_visit['checkout_messages'] = array_slice(array_keys($pay_problems), -2);
	}
	if ($flags['design']) {
		$this_visit['uploaded_a_design'] = true;
	}
	if ($flags['quote']) {
		$this_visit['sent_a_quote_request'] = true;
	}
	if ($flags['contact']) {
		$this_visit['tapped_call_or_email'] = true;
	}
	if ($flags['not_found']) {
		$this_visit['hit_a_missing_page'] = true;
	}
	if ($frustrated) {
		$this_visit['clicked_repeatedly_on'] = array_slice(array_keys($frustrated), -2);
	}
	if ('' !== $survey) {
		$this_visit['exit_survey_answer'] = $survey;
	}
	if ((int) $visit->order_id > 0) {
		$this_visit['ordered_this_visit'] = true;
	}

	if ($stale) {
		$this_visit = array('days_ago' => (int) floor(($now - $last_seen) / DAY_IN_SECONDS)) + $this_visit;
	}
	$out = array($stale ? 'last_visit' : 'this_visit' => $this_visit);

	$totals = $wpdb->get_row("SELECT COUNT(*) AS visits, MIN(started_at) AS first_seen, SUM(order_id > 0) AS orders FROM {$sessions} WHERE {$match}"); // phpcs:ignore WordPress.DB.PreparedSQL -- prepared in wholesale_vi_ai_match_sql().
	$earlier = array_slice($visits, 1);
	if ($earlier) {
		$best = 0;
		foreach ($earlier as $other) {
			$best = max($best, (int) $other->stage);
		}
		$ids = array_map('intval', wp_list_pluck($earlier, 'id'));
		$rows = $wpdb->get_results("SELECT url, label FROM {$events_table} WHERE session_id IN (" . implode(',', $ids) . ") AND type = 'pageview' AND url LIKE '%/product/%' ORDER BY id DESC LIMIT 60"); // phpcs:ignore WordPress.DB.PreparedSQL -- integer ids.
		$seen = array();
		foreach ($rows as $row) {
			$title = wholesale_vi_ai_title($row->label);
			if ('' !== $title && !isset($seen[$title]) && count($seen) < 5) {
				$seen[$title] = true;
			}
		}
		$before = array(
			'earlier_visits' => max(0, (int) $totals->visits - 1),
			'first_visit_days_ago' => (int) floor(($now - strtotime($totals->first_seen . ' UTC')) / DAY_IN_SECONDS),
			'last_visit_days_ago' => (int) floor(($now - strtotime($earlier[0]->last_seen . ' UTC')) / DAY_IN_SECONDS),
			'furthest_step_before' => (string) ($stages[$best] ?? ''),
		);
		if ($seen) {
			$before['products_viewed_before'] = array_keys($seen);
		}
		$orders_before = (int) $totals->orders - ((int) $visit->order_id > 0 ? 1 : 0);
		if ($orders_before > 0) {
			$before['orders_before'] = $orders_before;
		}
		$out['before'] = $before;
	}

	return $out;
}
add_filter('sfs_chat_ai_activity', 'wholesale_vi_ai_activity', 10, 3);

/**
 * sfs_chat_ai_outcome: how far a chat visitor got between two times (the chat
 * start and a few days later): the furthest funnel step, cart adds, leads and
 * orders placed in that window.
 *
 * @param mixed  $outcome   Earlier filter value (null).
 * @param string $vi_key    The visitor's sso_vid cookie.
 * @param int    $user_id   Their account, when there is no cookie.
 * @param string $since_gmt Window start, Y-m-d H:i:s UTC.
 * @param string $until_gmt Window end, Y-m-d H:i:s UTC.
 * @return array|mixed
 */
function wholesale_vi_ai_outcome($outcome, $vi_key, $user_id, $since_gmt, $until_gmt)
{
	global $wpdb;
	$match = wholesale_vi_ai_match_sql($vi_key, (int) $user_id);
	if ('' === $match) {
		return $outcome;
	}
	$sessions = wholesale_vi_table('sessions');
	$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$sessions} WHERE {$match} AND last_seen >= %s AND started_at <= %s ORDER BY id DESC LIMIT 30", $since_gmt, $until_gmt)); // phpcs:ignore WordPress.DB.PreparedSQL -- $match prepared in wholesale_vi_ai_match_sql().
	if (!$ids) {
		return is_array($outcome) ? $outcome : array('matched' => false);
	}
	$in = implode(',', array_map('intval', $ids));
	$events = $wpdb->get_results($wpdb->prepare('SELECT type, url, value FROM ' . wholesale_vi_table('events') . " WHERE session_id IN ({$in}) AND created_at >= %s AND created_at <= %s AND type IN ('pageview','configure','price_quote','design_upload','add_to_cart','payment_start','payment_error','purchase','quote_submit','call_click','email_click') ORDER BY id ASC LIMIT 2000", $since_gmt, $until_gmt)); // phpcs:ignore WordPress.DB.PreparedSQL -- integer ids.
	$step = 1;
	$cart_adds = 0;
	$lead = false;
	$orders = array();
	$products = array();
	foreach ($events as $event) {
		$path = wholesale_vi_ai_path($event->url);
		$step = max($step, wholesale_vi_ai_event_step($event->type, $path));
		if ('add_to_cart' === $event->type) {
			$cart_adds++;
		} elseif (in_array($event->type, array('quote_submit', 'call_click', 'email_click'), true)) {
			$lead = true;
		} elseif ('purchase' === $event->type && (int) $event->value > 0) {
			$orders[(int) $event->value] = true;
		} elseif ('pageview' === $event->type && 0 === strpos($path, '/product/')) {
			$products[$path] = true;
		}
	}
	$revenue = 0.0;
	foreach (array_keys($orders) as $order_id) {
		$cost = json_decode((string) get_post_meta($order_id, 'product_cost', true), true);
		$revenue += round((float) ($cost['grand_total'] ?? 0), 2);
	}
	$stages = wholesale_vi_stages();

	return array(
		'matched' => true,
		'stage' => $step,
		'stage_label' => (string) ($stages[$step] ?? ''),
		'cart_adds' => $cart_adds,
		'lead' => $lead,
		'orders' => array_keys($orders),
		'revenue' => round($revenue, 2),
		'products_viewed' => count($products),
	);
}
add_filter('sfs_chat_ai_outcome', 'wholesale_vi_ai_outcome', 10, 5);

/**
 * sfs_chat_ai_baseline: the funnel for every human visit in a period (robots
 * left out), so chat results can be compared with the site as a whole.
 *
 * @return array|mixed
 */
function wholesale_vi_ai_baseline($baseline, $since_gmt, $until_gmt)
{
	$m = wholesale_vi_summary($since_gmt, $until_gmt);
	if (!$m || empty($m['sessions'])) {
		return $baseline;
	}

	return array(
		'visits' => (int) $m['sessions'],
		'engaged_visits' => max(0, (int) $m['sessions'] - (int) $m['bounced']),
		'viewed_product' => (int) $m['s2'],
		'priced' => (int) $m['s3'],
		'cart' => (int) $m['s4'],
		'checkout' => (int) $m['s5'],
		'orders' => (int) $m['orders'],
		'revenue' => round((float) $m['revenue'], 2),
	);
}
add_filter('sfs_chat_ai_baseline', 'wholesale_vi_ai_baseline', 10, 3);
