<?php
/**
 * Visitor Insights: the Leads tab and the "Get more orders" tab.
 *
 * Leads are quote and contact requests (contact_submission posts, with the
 * name, phone, email and product asked about), taps on the phone number or
 * email address, and live chats where the visitor left contact details. Each
 * is credited to the campaign and keyword that brought the person, the same
 * way orders are, and shows whether it later became an order.
 *
 * "Get more orders" turns the numbers into a short ranked to-do list: people
 * to follow up, ad clicks that bring nothing, pages and steps where buyers
 * stop.
 *
 * Loaded by inc/visitor-insights.php.
 *
 * @package litsign
 */

/* ---------------------------------------------------------------------------
 * Finding a lead's visit, source and order
 * ------------------------------------------------------------------------ */

/**
 * The quote request with this title (its quote_submit event's label). Titles
 * are "Name - Y-m-d H:i", so they survive the database copies between the live
 * and local sites that renumber posts; $hint is the post ID the event saved.
 */
function wholesale_vi_lead_by_title($title, $hint = 0)
{
	static $cache = array();
	$title = (string) $title;
	$key = $title . '|' . (int) $hint;
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}

	$id = 0;
	if ($hint && 'contact_submission' === get_post_type($hint) && wp_strip_all_tags(get_post_field('post_title', $hint)) === $title) {
		$id = (int) $hint;
	} elseif ('' !== $title) {
		global $wpdb;
		$id = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'contact_submission' AND post_status NOT IN ('trash', 'auto-draft') AND post_title = %s ORDER BY ID DESC LIMIT 1",
			$title
		));
	}

	return $cache[$key] = $id;
}

/**
 * What a quote request asked for, e.g. "Channel letters: Front lit".
 */
function wholesale_vi_lead_wants($lead_id)
{
	$project = trim((string) get_post_meta($lead_id, '_contact_project_type', true));
	if ('' !== $project) {
		return $project;
	}
	$interest = (string) get_post_meta($lead_id, '_contact_interest', true);
	$interests = function_exists('wholesale_quote_interests') ? wholesale_quote_interests() : array();

	return $interests[$interest]['label'] ?? 'Not said';
}

/**
 * The visit a quote request was sent from, with ->ordered_at (the time it was
 * sent) and ->lead_url (the page the form was on).
 */
function wholesale_vi_lead_session($lead_id)
{
	global $wpdb;
	$title = wp_strip_all_tags((string) get_post_field('post_title', $lead_id));
	if ('' === $title) {
		return null;
	}

	$event = $wpdb->get_row($wpdb->prepare(
		'SELECT session_id, created_at, url FROM ' . wholesale_vi_table('events') . " WHERE type = 'quote_submit' AND label = %s ORDER BY session_id = %d DESC, id DESC LIMIT 1",
		substr($title, 0, 255),
		(int) get_post_meta($lead_id, '_vi_session_id', true)
	));
	$session = $event ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('sessions') . ' WHERE id = %d', (int) $event->session_id)) : null;
	if ($session) {
		$session->ordered_at = $event->created_at;
		$session->lead_url = $event->url;
	}

	return $session;
}

/**
 * Where a quote request came from, credited like an order. Saved on the
 * request the first time, so it outlives the visits. When no visit was
 * recorded (tracking blocked, or sent before Visitor Insights), the campaign,
 * keyword and click ID the form itself kept are used.
 *
 * @return array|null Same keys as wholesale_vi_credit_source().
 */
function wholesale_vi_lead_source($lead_id)
{
	static $cache = array();
	$lead_id = (int) $lead_id;
	if (array_key_exists($lead_id, $cache)) {
		return $cache[$lead_id];
	}

	$saved = get_post_meta($lead_id, '_vi_source', true);
	if (is_array($saved) && !empty($saved['channel'])) {
		return $cache[$lead_id] = wholesale_vi_normalize_source($saved);
	}

	$session = wholesale_vi_lead_session($lead_id);
	if (!$session) {
		return $cache[$lead_id] = wholesale_vi_lead_form_source($lead_id);
	}

	$source = wholesale_vi_credit_source($session);
	// The form keeps the ad click on its hidden fields; trust it over a visit
	// that lost it (a page reload without ?gclid=).
	$form = wholesale_vi_lead_form_source($lead_id);
	if ('direct' === $source['channel'] && $form && 'direct' !== $form['channel']) {
		$source = array_merge($source, array_intersect_key($form, array_flip(array('channel', 'source', 'keyword', 'campaign', 'ad_group'))), array('from_form' => true));
	}
	update_post_meta($lead_id, '_vi_source', $source);

	return $cache[$lead_id] = wholesale_vi_normalize_source($source);
}

/**
 * A lead's source from what the form saved: _contact_attribution (utm values,
 * landing page, referrer), _contact_source ("Campaign: …, Keyword: …") and
 * _contact_gclid.
 */
function wholesale_vi_lead_form_source($lead_id)
{
	$attribution = get_post_meta($lead_id, '_contact_attribution', true);
	$attribution = is_array($attribution) ? $attribution : array();
	$text = (string) get_post_meta($lead_id, '_contact_source', true);
	$params = array();
	foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content') as $key) {
		if (!empty($attribution[$key])) {
			$params[$key] = (string) $attribution[$key];
		}
	}
	foreach (array('Campaign' => 'utm_campaign', 'Keyword' => 'utm_term') as $label => $key) {
		if (empty($params[$key]) && preg_match('/' . $label . ': ([^,]+)/', $text, $match)) {
			$params[$key] = trim($match[1]);
		}
	}
	$gclid = (string) get_post_meta($lead_id, '_contact_gclid', true);
	if ('' === $gclid && preg_match('/GCLID: ([A-Za-z0-9_-]+)/', $text, $match)) {
		$gclid = $match[1];
	}
	if ('' !== $gclid) {
		$params['gclid'] = $gclid;
	}
	$referrer = (string) ($attribution['referrer'] ?? '');
	$landing = (string) ($attribution['landing_page'] ?? '');
	if (!$params && '' === $referrer && '' === $landing) {
		return null;
	}

	$found = wholesale_vi_attribution($params, $referrer);
	$at = get_post_field('post_date_gmt', $lead_id);

	return wholesale_vi_normalize_source(array(
		'channel' => $found['channel'],
		'source' => $found['source'],
		'keyword' => $found['term'],
		'campaign' => $found['campaign'],
		'ad_group' => $found['content'],
		'landing' => '' !== $landing ? wholesale_vi_clean_url($landing) : '',
		'visit_id' => 0,
		'visit_at' => $at,
		'order_visit_id' => 0,
		'order_channel' => '',
		'ordered_at' => $at,
		'device' => '',
		'visits' => 0,
		'first_at' => $at,
		'from_form' => true,
	));
}

function wholesale_vi_order_number($order_id)
{
	return '#' . strtoupper(preg_replace('/^order_/i', '', (string) get_post_meta($order_id, 'order_id', true) ?: (string) $order_id));
}

/**
 * Orders of the last 400 days by the customer's email (lowercase).
 *
 * @return array[] email => list of array(id, number, total, at), oldest first.
 */
function wholesale_vi_orders_by_email()
{
	static $map = null;
	if (null !== $map) {
		return $map;
	}

	$map = array();
	$ids = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'any',
		'posts_per_page' => 2000,
		'fields' => 'ids',
		'no_found_rows' => true,
		'orderby' => 'date',
		'order' => 'ASC',
		'date_query' => array(array('column' => 'post_date_gmt', 'after' => gmdate('Y-m-d H:i:s', time() - 400 * DAY_IN_SECONDS))),
	));
	update_meta_cache('post', $ids);
	foreach ($ids as $id) {
		$billing = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($id, 'billing_address', true)) : array();
		$email = strtolower(trim((string) ($billing['billing_email'] ?? '')));
		if (!is_email($email)) {
			$user = get_userdata((int) get_post_meta($id, 'user_id', true));
			$email = $user ? strtolower($user->user_email) : '';
		}
		if ('' === $email) {
			continue;
		}
		$map[$email][] = array(
			'id' => (int) $id,
			'number' => wholesale_vi_order_number($id),
			'total' => function_exists('wholesale_order_total') ? (float) wholesale_order_total($id) : 0.0,
			'at' => get_post_field('post_date_gmt', $id),
		);
	}

	return $map;
}

/**
 * The order a lead turned into: one placed with the same email from a day
 * before the request on, else the "Won order #" typed on the request.
 *
 * @return array|null array(id, number, total, at, how).
 */
function wholesale_vi_lead_order($lead_id, $email, $at_gmt)
{
	$email = strtolower(trim((string) $email));
	foreach (wholesale_vi_orders_by_email()[$email] ?? array() as $order) {
		if (strtotime($order['at'] . ' UTC') >= strtotime($at_gmt . ' UTC') - DAY_IN_SECONDS) {
			return $order + array('how' => 'Same email');
		}
	}

	$manual = trim((string) get_post_meta($lead_id, '_lead_order', true));
	if ('' === $manual) {
		return null;
	}
	$number = ltrim($manual, '#');
	$found = get_posts(array('post_type' => 'order', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1, 'no_found_rows' => true, 'meta_query' => array(array('key' => 'order_id', 'value' => array($number, strtolower($number), 'order_' . $number, 'order_' . strtolower($number)), 'compare' => 'IN'))));

	return $found
		? array('id' => (int) $found[0], 'number' => wholesale_vi_order_number($found[0]), 'total' => function_exists('wholesale_order_total') ? (float) wholesale_order_total($found[0]) : 0.0, 'at' => get_post_field('post_date_gmt', $found[0]), 'how' => 'Marked won')
		: array('id' => 0, 'number' => '#' . strtoupper($number), 'total' => (float) get_post_meta($lead_id, '_lead_quote_amount', true), 'at' => '', 'how' => 'Marked won');
}

/**
 * The latest quote request sent with an order's email in the 90 days before
 * it, for crediting orders paid on another device or through a payment link.
 */
function wholesale_vi_order_lead($order_id)
{
	$billing = function_exists('wholesale_decode_order_meta_array') ? wholesale_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true)) : array();
	$email = strtolower(trim((string) ($billing['billing_email'] ?? '')));
	if (!is_email($email)) {
		return 0;
	}
	$at = strtotime(get_post_field('post_date_gmt', $order_id) . ' UTC');
	if (!$at) {
		return 0;
	}

	global $wpdb;

	return (int) $wpdb->get_var($wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_contact_email'
		WHERE p.post_type = 'contact_submission' AND p.post_status NOT IN ('trash', 'auto-draft') AND LOWER(m.meta_value) = %s AND p.post_date_gmt <= %s AND p.post_date_gmt >= %s
		ORDER BY p.post_date_gmt DESC LIMIT 1",
		$email,
		gmdate('Y-m-d H:i:s', $at + HOUR_IN_SECONDS),
		gmdate('Y-m-d H:i:s', $at - 90 * DAY_IN_SECONDS)
	));
}

/**
 * Whether a request looks like the team testing a form: sent with a staff
 * account's email, or "test" in the name.
 */
function wholesale_vi_lead_is_test($name, $email, $business = '')
{
	static $staff = null;
	if (null === $staff) {
		$staff = array_map('strtolower', get_users(array('capability' => 'edit_posts', 'fields' => 'user_email', 'number' => 100)));
		$staff[] = strtolower((string) get_option('admin_email'));
	}

	return in_array(strtolower(trim((string) $email)), $staff, true)
		|| (bool) preg_match('/\b(test|tester|testing|trial|audit)\b/i', $name . ' ' . $business . ' ' . strtok((string) $email, '@'));
}

/* ---------------------------------------------------------------------------
 * The leads of a date range
 * ------------------------------------------------------------------------ */

function wholesale_vi_lead_types()
{
	return array(
		'quote' => array('Quote request', 'is-green', 'clipboard'),
		'call' => array('Call tap', 'is-blue', 'phone'),
		'email' => array('Email click', 'is-blue', 'email'),
		'chat' => array('Live chat', 'is-soft', 'format-chat'),
	);
}

/**
 * Every lead in a range, newest first.
 *
 * @param array $period Needs start_gmt and end_gmt.
 * @return array[] Each: type, key, at, name, business, email, phone, zip, wants,
 *                 specs, message, files, detail, page, stage, stage_label,
 *                 overdue, quote_amount, source, visit_id, device, location,
 *                 order, test, edit_url.
 */
function wholesale_vi_leads($period)
{
	static $cache = array();
	$cache_key = $period['start_gmt'] . '|' . $period['end_gmt'];
	if (isset($cache[$cache_key])) {
		return $cache[$cache_key];
	}

	global $wpdb;
	$start = $period['start_gmt'];
	$end = $period['end_gmt'];
	$events = wholesale_vi_table('events');
	$sessions = wholesale_vi_table('sessions');
	$leads = array();
	$stages = function_exists('wholesale_lead_stages') ? wholesale_lead_stages() : array();

	// Quote and contact requests.
	$ids = get_posts(array(
		'post_type' => 'contact_submission',
		'post_status' => 'any',
		'posts_per_page' => 300,
		'fields' => 'ids',
		'no_found_rows' => true,
		'orderby' => 'date',
		'order' => 'DESC',
		'date_query' => array(array('column' => 'post_date_gmt', 'after' => $start, 'before' => $end, 'inclusive' => true)),
	));
	update_meta_cache('post', $ids);
	foreach ($ids as $id) {
		$at = get_post_field('post_date_gmt', $id);
		$name = trim((string) get_post_meta($id, '_contact_name', true));
		$email = trim((string) get_post_meta($id, '_contact_email', true));
		$business = trim((string) get_post_meta($id, '_contact_business', true));
		$source = wholesale_vi_lead_source($id);
		$stage = function_exists('wholesale_lead_stage') ? wholesale_lead_stage($id) : '';
		$specs = get_post_meta($id, '_contact_specs', true);
		$attribution = get_post_meta($id, '_contact_attribution', true);
		$files = get_post_meta($id, '_contact_files', true);
		$page = $source && $source['order_visit_id'] ? (string) $wpdb->get_var($wpdb->prepare("SELECT url FROM {$events} WHERE session_id = %d AND type = 'quote_submit' ORDER BY id DESC LIMIT 1", $source['order_visit_id'])) : '';
		if ('' === $page && is_array($attribution) && !empty($attribution['quote_button_page'])) {
			$page = wholesale_vi_clean_url($attribution['quote_button_page']);
		}
		$leads[] = array(
			'type' => 'quote',
			'key' => 'q' . $id,
			'at' => $at,
			'name' => $name ?: get_the_title($id),
			'business' => $business,
			'email' => $email,
			'phone' => trim((string) get_post_meta($id, '_contact_phone', true)),
			'zip' => trim((string) get_post_meta($id, '_contact_zip', true)),
			'wants' => wholesale_vi_lead_wants($id),
			'specs' => is_array($specs) ? array_filter(array_map('strval', $specs), 'strlen') : array(),
			'message' => wp_trim_words(wp_strip_all_tags((string) (get_post_meta($id, '_contact_message', true) ?: get_post_field('post_content', $id))), 40),
			'files' => (is_array($files) ? count($files) : 0) + (get_post_meta($id, '_contact_logo', true) ? 1 : 0),
			'detail' => '',
			'page' => $page,
			'stage' => $stage,
			'stage_label' => $stages[$stage] ?? '',
			'overdue' => function_exists('wholesale_lead_is_overdue') && wholesale_lead_is_overdue($id),
			'replied' => (string) get_post_meta($id, '_contact_last_reply', true),
			'quote_amount' => (float) get_post_meta($id, '_lead_quote_amount', true),
			'source' => $source,
			'visit_id' => $source ? (int) $source['order_visit_id'] : 0,
			'device' => $source ? (string) $source['device'] : '',
			'location' => $source ? (string) $source['location'] : '',
			'order' => wholesale_vi_lead_order($id, $email, $at),
			'test' => wholesale_vi_lead_is_test($name, $email, $business),
			'edit_url' => get_edit_post_link($id, 'raw'),
		);
	}

	// Taps on the phone number and email links, one per visit and number.
	list($where, $params) = wholesale_vi_where($start, $end, array());
	$taps = $wpdb->get_results($wpdb->prepare(
		"SELECT e.session_id, e.type, e.label, MIN(e.created_at) AS at, MIN(e.url) AS url FROM {$events} e JOIN {$sessions} s ON s.id = e.session_id
		WHERE e.type IN ('call_click', 'email_click') AND {$where} GROUP BY e.session_id, e.type, e.label ORDER BY at DESC LIMIT 200",
		$params
	));
	$tap_sessions = array();
	if ($taps) {
		$ids = implode(',', array_unique(array_map(static function ($tap) {
			return (int) $tap->session_id;
		}, $taps)));
		foreach ($wpdb->get_results("SELECT * FROM {$sessions} WHERE id IN ({$ids})") as $row) { // phpcs:ignore WordPress.DB.PreparedSQL -- integers.
			$tap_sessions[(int) $row->id] = $row;
		}
		wholesale_vi_load_people(array_values($tap_sessions));
	}
	foreach ($taps as $tap) {
		$session = $tap_sessions[(int) $tap->session_id] ?? null;
		if (!$session) {
			continue;
		}
		$credit = clone $session;
		$credit->ordered_at = $tap->at;
		$source = wholesale_vi_credit_source($credit);
		$person = wholesale_vi_person($session);
		$is_call = 'call_click' === $tap->type;
		$title = (string) $wpdb->get_var($wpdb->prepare("SELECT label FROM {$events} WHERE session_id = %d AND type = 'pageview' AND url = %s ORDER BY id DESC LIMIT 1", $session->id, $tap->url));
		$later = $wpdb->get_var($wpdb->prepare(
			"SELECT e.label FROM {$events} e JOIN {$sessions} s ON s.id = e.session_id WHERE s.visitor_key = %s AND e.type = 'purchase' AND e.created_at >= %s ORDER BY e.id LIMIT 1",
			$session->visitor_key,
			$tap->at
		));
		$order = null;
		if ($later) {
			$number = preg_replace('/^Order #/', '', $later);
			$found = get_posts(array('post_type' => 'order', 'post_status' => 'any', 'meta_key' => 'order_id', 'meta_value' => $number, 'fields' => 'ids', 'posts_per_page' => 1, 'no_found_rows' => true));
			$order = array('id' => $found ? (int) $found[0] : 0, 'number' => '#' . strtoupper(preg_replace('/^order_/i', '', $number)), 'total' => $found && function_exists('wholesale_order_total') ? (float) wholesale_order_total($found[0]) : 0.0, 'at' => '', 'how' => 'Same browser');
		}
		$leads[] = array(
			'type' => $is_call ? 'call' : 'email',
			'key' => $tap->type . $tap->session_id . md5($tap->label),
			'at' => $tap->at,
			'name' => $person['name'] ?? '',
			'business' => '',
			'email' => $is_call ? ($person['email'] ?? '') : $tap->label,
			'phone' => '',
			'zip' => '',
			'wants' => preg_replace('/\s*[|–-]\s*Storefront Sign Online.*$/i', '', $title),
			'specs' => array(),
			'message' => '',
			'files' => 0,
			'detail' => $is_call
				? 'Tapped ' . wholesale_vi_call_label($tap->label) . ('desktop' === $session->device ? ' on a computer (may not have called)' : '')
				: 'Clicked to email ' . $tap->label,
			'page' => $tap->url,
			'stage' => '',
			'stage_label' => '',
			'overdue' => false,
			'replied' => '',
			'quote_amount' => 0,
			'source' => $source,
			'visit_id' => (int) $session->id,
			'device' => $session->device,
			'location' => wholesale_vi_location($session),
			'order' => $order,
			'test' => false,
			'edit_url' => '',
		);
	}

	// Live chats where the visitor gave a name, email or phone.
	$chats = $wpdb->prefix . 'sfs_chat_conversations';
	$chat_visitors = $wpdb->prefix . 'sfs_chat_visitors';
	if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $chats)) === $chats) {
		$messages = $wpdb->prefix . 'sfs_chat_messages';
		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT c.id, c.created_at, c.status, c.source_page, v.name, v.email, v.phone, v.ip,
				(SELECT m.body FROM {$messages} m WHERE m.conversation_id = c.id AND m.sender_type = 'visitor' ORDER BY m.id LIMIT 1) AS first_message
			FROM {$chats} c JOIN {$chat_visitors} v ON v.id = c.visitor_id
			WHERE c.created_at >= %s AND c.created_at < %s AND (v.name <> '' OR v.email <> '' OR v.phone <> '') ORDER BY c.created_at DESC LIMIT 100",
			$start,
			$end
		));
		foreach ((array) $rows as $chat) {
			// The chat and the visit share the visitor's IP address and time.
			$session = $chat->ip ? $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM {$sessions} WHERE ip = %s AND started_at <= %s AND last_seen >= %s ORDER BY last_seen DESC LIMIT 1",
				$chat->ip,
				gmdate('Y-m-d H:i:s', strtotime($chat->created_at . ' UTC') + 5 * MINUTE_IN_SECONDS),
				gmdate('Y-m-d H:i:s', strtotime($chat->created_at . ' UTC') - 30 * MINUTE_IN_SECONDS)
			)) : null;
			$source = null;
			if ($session) {
				$session->ordered_at = $chat->created_at;
				$source = wholesale_vi_credit_source($session);
			}
			$email = trim((string) $chat->email);
			$leads[] = array(
				'type' => 'chat',
				'key' => 'c' . $chat->id,
				'at' => $chat->created_at,
				'name' => trim((string) $chat->name),
				'business' => '',
				'email' => $email,
				'phone' => trim((string) $chat->phone),
				'zip' => '',
				'wants' => '',
				'specs' => array(),
				'message' => wp_trim_words(wp_strip_all_tags((string) $chat->first_message), 40),
				'files' => 0,
				'detail' => 'Chat ' . ('solved' === $chat->status ? '(solved)' : '(' . $chat->status . ')'),
				'page' => $chat->source_page ? wholesale_vi_clean_url($chat->source_page) : '',
				'stage' => '',
				'stage_label' => '',
				'overdue' => false,
				'replied' => '',
				'quote_amount' => 0,
				'source' => $source,
				'visit_id' => $session ? (int) $session->id : 0,
				'device' => $session ? $session->device : '',
				'location' => $session ? wholesale_vi_location($session) : '',
				'order' => $email ? wholesale_vi_lead_order(0, $email, $chat->created_at) : null,
				'test' => wholesale_vi_lead_is_test((string) $chat->name, $email),
				'edit_url' => admin_url('admin.php?page=sfs-live-chat'),
			);
		}
	}

	usort($leads, static function ($a, $b) {
		return strcmp($b['at'], $a['at']);
	});

	// The browser of each visit, so a call tap and a quote sent from the same
	// phone are seen as the same person.
	$visitors = array();
	$visit_ids = array_filter(array_unique(array_map('intval', array_column($leads, 'visit_id'))));
	if ($visit_ids) {
		foreach ($wpdb->get_results('SELECT id, visitor_key FROM ' . $sessions . ' WHERE id IN (' . implode(',', $visit_ids) . ')') as $row) { // phpcs:ignore WordPress.DB.PreparedSQL -- integers.
			$visitors[(int) $row->id] = (string) $row->visitor_key;
		}
	}

	return $cache[$cache_key] = wholesale_vi_merge_leads($leads, $visitors);
}

/**
 * One lead per person. A quote request, chat, call tap and email click from
 * the same person (same email, phone number or browser) within 3 days of each
 * other are one lead: the strongest (quote, then chat, call, email; the newest
 * on a tie) counts and lists the others under "Also", which are marked repeats
 * and hidden with the tests. This also folds a form sent twice into one.
 *
 * @param array[] $leads    Newest first.
 * @param array   $visitors Visit id => browser key.
 * @return array[] The same leads, each with repeat, repeat_of and also.
 */
function wholesale_vi_merge_leads($leads, $visitors = array())
{
	$rank = array('quote' => 4, 'chat' => 3, 'call' => 2, 'email' => 1);
	$leads = array_values($leads);
	$parent = array_keys($leads);
	$find = static function ($i) use (&$parent) {
		while ($parent[$i] !== $i) {
			$parent[$i] = $parent[$parent[$i]];
			$i = $parent[$i];
		}
		return $i;
	};
	$owner = array();
	foreach ($leads as $i => &$lead) {
		$lead['repeat'] = false;
		$lead['repeat_of'] = null;
		$lead['also'] = array();
		$keys = array();
		// An email click's address is the store's own, not the visitor's.
		$email = 'email' === $lead['type'] ? '' : strtolower(trim((string) $lead['email']));
		if ('' !== $email && is_email($email)) {
			$keys[] = 'e' . $email;
		}
		$phone = substr(preg_replace('/\D/', '', (string) $lead['phone']), -10);
		if (10 === strlen($phone)) {
			$keys[] = 'p' . $phone;
		}
		$visit = (int) $lead['visit_id'];
		if ($visit && !empty($visitors[$visit])) {
			$keys[] = 'v' . $visitors[$visit];
		}
		foreach ($keys as $key) {
			if (isset($owner[$key])) {
				$parent[$find($i)] = $find($owner[$key]);
			} else {
				$owner[$key] = $i;
			}
		}
	}
	unset($lead);

	// Each person's leads, newest first, cut where 3 days pass between two.
	$people = array();
	foreach (array_keys($leads) as $i) {
		$people[$find($i)][] = $i;
	}
	foreach ($people as $members) {
		$clusters = array(array());
		$previous = null;
		foreach ($members as $i) {
			$time = strtotime($leads[$i]['at'] . ' UTC');
			if (null !== $previous && $previous - $time >= 3 * DAY_IN_SECONDS) {
				$clusters[] = array();
			}
			$clusters[count($clusters) - 1][] = $i;
			$previous = $time;
		}
		foreach ($clusters as $cluster) {
			if (count($cluster) < 2) {
				continue;
			}
			$main = $cluster[0];
			foreach ($cluster as $i) {
				if (($rank[$leads[$i]['type']] ?? 0) > ($rank[$leads[$main]['type']] ?? 0)) {
					$main = $i;
				}
			}
			foreach (array_reverse($cluster) as $i) {
				if ($i === $main) {
					continue;
				}
				$other = $leads[$i];
				$leads[$i]['repeat'] = true;
				$leads[$i]['repeat_of'] = array('type' => $leads[$main]['type'], 'at' => $leads[$main]['at']);
				// Fill what the counted lead is missing from the folded one.
				foreach (array('name', 'business', 'phone', 'zip', 'wants', 'page', 'device', 'location') as $field) {
					if ('' === (string) $leads[$main][$field] && '' !== (string) $other[$field]) {
						$leads[$main][$field] = $other[$field];
					}
				}
				if ('' === (string) $leads[$main]['email'] && 'email' !== $other['type'] && '' !== (string) $other['email']) {
					$leads[$main]['email'] = $other['email'];
				}
				foreach (array('source', 'order') as $field) {
					if (empty($leads[$main][$field]) && !empty($other[$field])) {
						$leads[$main][$field] = $other[$field];
					}
				}
				if (!$leads[$main]['visit_id'] && $other['visit_id']) {
					$leads[$main]['visit_id'] = $other['visit_id'];
				}
				$what = 'quote' === $other['type'] ? ('quote' === $leads[$main]['type'] ? 'Sent the request again' : 'Sent a quote request') : $other['detail'];
				$when = $other['at'] === $leads[$main]['at'] ? 'same time' : wholesale_vi_time_between($other['at'], $leads[$main]['at']) . ($other['at'] < $leads[$main]['at'] ? ' before' : ' after');
				$leads[$main]['also'][] = $what . ' · ' . $when;
			}
		}
	}

	return $leads;
}

/**
 * Leads matching the Leads tab's filters.
 */
function wholesale_vi_filter_leads($leads, $args)
{
	return array_values(array_filter($leads, static function ($lead) use ($args) {
		if (empty($args['tests']) && ($lead['test'] || $lead['repeat'])) {
			return false;
		}
		if (!empty($args['type']) && $args['type'] !== $lead['type']) {
			return false;
		}
		$channel = $lead['source'] ? $lead['source']['channel'] : '';
		if (!empty($args['channel']) && $args['channel'] !== $channel) {
			return false;
		}
		if ('open' === ($args['status'] ?? '') && ($lead['order'] || in_array($lead['stage'], array('won', 'lost'), true))) {
			return false;
		}
		if ('overdue' === ($args['status'] ?? '') && !$lead['overdue']) {
			return false;
		}
		if ('won' === ($args['status'] ?? '') && !$lead['order'] && 'won' !== $lead['stage']) {
			return false;
		}
		if (!empty($args['s'])) {
			$haystack = implode(' ', array($lead['name'], $lead['business'], $lead['email'], $lead['phone'], $lead['wants'], $lead['detail'], $lead['message'], $lead['source'] ? wholesale_vi_campaign_name($lead['source']['campaign']) . ' ' . $lead['source']['keyword'] : ''));
			if (false === stripos($haystack, $args['s'])) {
				return false;
			}
		}
		return true;
	}));
}

/* ---------------------------------------------------------------------------
 * Leads tab
 * ------------------------------------------------------------------------ */

/**
 * Where a lead came from, in a line or two.
 */
function wholesale_vi_lead_source_html($source)
{
	if (!$source) {
		return '<span class="wvi-muted" title="No visit was recorded: tracking was blocked, the person used another device, or the request came before Visitor Insights">Not tracked</span>';
	}
	$paid = in_array($source['channel'], array('google_ads', 'paid_other'), true);
	$html = wholesale_vi_channel_badge($source['channel']);
	if ('' !== $source['keyword']) {
		$html .= ' <strong>“' . esc_html($source['keyword']) . '”</strong>';
	} elseif (!$paid && 'direct' !== $source['channel'] && '' !== $source['source']) {
		$html .= ' <span class="wvi-muted">' . esc_html($source['source']) . '</span>';
	}
	$lines = array();
	if ('' !== $source['campaign']) {
		$lines[] = wholesale_vi_campaign_html($source['campaign'], false);
	}
	$details = array();
	if ($paid && '' !== $source['network']) {
		$details[] = wholesale_vi_ad_networks()[$source['network']] ?? $source['network'];
	}
	if ($source['visit_id'] && $source['visit_id'] !== $source['order_visit_id']) {
		$details[] = 'clicked ' . wholesale_vi_time_between($source['visit_at'], $source['ordered_at']) . ' before';
	}
	if ($source['visits'] > 1) {
		$details[] = $source['visits'] . ' visits';
	}
	if ($source['from_form']) {
		$details[] = 'from the form\'s ad fields';
	}
	if ($details) {
		$lines[] = '<span class="wvi-muted">' . esc_html(implode(' · ', $details)) . '</span>';
	}

	return $html . ($lines ? '<span class="wvi-sub wvi-wrap">' . implode('<br>', $lines) . '</span>' : '');
}

function wholesale_vi_render_leads()
{
	// Leads are few a week; a month is the useful default.
	if (empty($_GET['period']) && empty($_GET['from'])) {
		$_GET['period'] = '30d';
	}
	$period = wholesale_vi_period();
	$args = array(
		'type' => isset($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : '',
		'channel' => isset($_GET['channel']) ? sanitize_key(wp_unslash($_GET['channel'])) : '',
		'status' => isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '',
		's' => isset($_GET['s']) ? trim(sanitize_text_field(wp_unslash($_GET['s']))) : '',
		'tests' => !empty($_GET['tests']),
	);
	$all = wholesale_vi_leads($period);
	$real = array_values(array_filter($all, static function ($lead) {
		return !$lead['test'] && !$lead['repeat'];
	}));
	$leads = wholesale_vi_filter_leads($all, $args);
	$types = wholesale_vi_lead_types();

	add_action('wholesale_vi_toolbar_fields', static function () use ($args, $types) {
		?>
		<select name="type" aria-label="Kind of lead">
			<option value="">All leads</option>
			<?php foreach ($types as $key => $type) : ?>
				<option value="<?php echo esc_attr($key); ?>" <?php selected($args['type'], $key); ?>><?php echo esc_html($type[0]); ?>s</option>
			<?php endforeach; ?>
		</select>
		<select name="status" aria-label="Status">
			<?php foreach (array('' => 'Any status', 'open' => 'Not ordered yet', 'overdue' => 'Follow-up overdue', 'won' => 'Became an order') as $key => $label) : ?>
				<option value="<?php echo esc_attr($key); ?>" <?php selected($args['status'], $key); ?>><?php echo esc_html($label); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="wvi-search"><span class="dashicons dashicons-search"></span><input type="search" name="s" value="<?php echo esc_attr($args['s']); ?>" placeholder="Name, phone, email, product, campaign"></span>
		<label class="wvi-inline-check"><input type="checkbox" name="tests" value="1" <?php checked($args['tests']); ?>> Show tests &amp; repeats</label>
		<?php
	});
	wholesale_vi_render_toolbar($period, 'wholesale-insights-leads', array('channel' => $args['channel'], 'type' => $args['type'], 'status' => $args['status'], 's' => $args['s'], 'tests' => $args['tests'] ? 1 : ''), array('channel'));

	$count = static function ($list, $callback) {
		return count(array_filter($list, $callback));
	};
	$won = array_filter($real, static function ($lead) {
		return (bool) $lead['order'];
	});
	$won_value = array_sum(array_map(static function ($lead) {
		return (float) $lead['order']['total'];
	}, $won));
	$tests = count($all) - count($real);
	?>
	<div class="wvi-stats">
		<?php
		wholesale_vi_stat('Leads', number_format_i18n(count($real)), 'id', 'blue', '', 'Quote requests, call taps, email clicks and chats with contact details. One per person: a call tap and a quote from the same person within 3 days count once.');
		wholesale_vi_stat('Quote requests', number_format_i18n($count($real, static function ($lead) {
			return 'quote' === $lead['type'];
		})), 'clipboard', 'green');
		wholesale_vi_stat('Call taps · emails', number_format_i18n($count($real, static function ($lead) {
			return 'call' === $lead['type'];
		})) . ' · ' . number_format_i18n($count($real, static function ($lead) {
			return 'email' === $lead['type'];
		})), 'phone', 'teal', '', 'A tap on the phone number is a call started on a phone; on a computer it often goes nowhere.');
		wholesale_vi_stat('Live chats', number_format_i18n($count($real, static function ($lead) {
			return 'chat' === $lead['type'];
		})), 'format-chat', 'purple', '', 'Chats where the visitor left a name, email or phone');
		wholesale_vi_stat('From Google Ads', number_format_i18n($count($real, static function ($lead) {
			return $lead['source'] && 'google_ads' === $lead['source']['channel'];
		})), 'megaphone', 'blue');
		wholesale_vi_stat('Became orders · ' . wholesale_vi_money($won_value), number_format_i18n(count($won)), 'yes-alt', 'green', '', 'Matched by the same email, the same browser, or "Won order #" on the request');
		wholesale_vi_stat('Follow-up overdue', number_format_i18n($count($real, static function ($lead) {
			return $lead['overdue'];
		})), 'warning', 'red', '', 'Open quote requests with no reply after a day, or past their next follow-up date');
		wholesale_vi_stat('Lead → order rate', wholesale_vi_pct(count($won), count($real), 0), 'performance', 'amber');
		?>
	</div>

	<?php wholesale_vi_render_lead_sources($real); ?>

	<section class="wvi-card">
		<div class="wvi-section-head wvi-pad">
			<h2><?php echo esc_html(sprintf(_n('%s lead', '%s leads', count($leads)), number_format_i18n(count($leads)))); ?></h2>
			<span class="wvi-muted">Newest first. Credited like orders: the last ad click in the 90 days before, else the last search or referral visit.<?php echo $tests && !$args['tests'] ? esc_html(sprintf(' %d test or repeated request(s) hidden.', $tests)) : ''; ?></span>
		</div>
		<?php if (!$leads) : ?>
			<p class="wvi-empty">No leads match these filters in this range.</p>
		<?php else : ?>
			<div class="wvi-table-wrap">
				<table class="wvi-table wvi-leads">
					<thead><tr><th>When</th><th>Lead</th><th>Wants</th><th>Came from</th><th>Page</th><th>Device · location</th><th>Status</th><th></th></tr></thead>
					<tbody>
						<?php foreach ($leads as $lead) : ?>
							<?php wholesale_vi_lead_row($lead, $types); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

function wholesale_vi_lead_row($lead, $types)
{
	$type = $types[$lead['type']];
	$phone = preg_replace('/[^0-9+]/', '', $lead['phone']);
	?>
	<tr>
		<td class="wvi-nowrap"><?php echo esc_html(wholesale_vi_date($lead['at'])); ?><span class="wvi-sub"><?php echo esc_html(wholesale_vi_ago($lead['at'])); ?></span></td>
		<td class="wvi-lead-who">
			<span class="wvi-badge <?php echo esc_attr($type[1]); ?>"><span class="dashicons dashicons-<?php echo esc_attr($type[2]); ?>"></span> <?php echo esc_html($type[0]); ?></span>
			<?php if ($lead['test']) : ?><span class="wvi-badge is-amber">Test</span><?php endif; ?>
			<?php if ($lead['repeat'] && $lead['repeat_of'] && $lead['repeat_of']['type'] !== $lead['type']) : ?>
				<span class="wvi-badge is-amber" title="<?php echo esc_attr('The same person also sent a ' . strtolower($types[$lead['repeat_of']['type']][0]) . ' (' . wholesale_vi_date($lead['repeat_of']['at']) . '); they count once, as that lead'); ?>">Same person</span>
			<?php elseif ($lead['repeat']) : ?><span class="wvi-badge is-amber" title="The same person did it again within 3 days; they count once"><?php echo esc_html(array('call' => 'Tapped again', 'email' => 'Clicked again', 'chat' => 'Chatted again')[$lead['type']] ?? 'Sent again'); ?></span><?php endif; ?>
			<strong class="wvi-lead-name"><?php echo esc_html($lead['name'] ?: ('quote' === $lead['type'] ? '(no name)' : 'Unknown visitor')); ?></strong>
			<?php if ($lead['business']) : ?><span class="wvi-muted"><?php echo esc_html($lead['business']); ?></span><?php endif; ?>
			<span class="wvi-lead-contact">
				<?php if ($phone) : ?><a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($lead['phone']); ?></a><?php endif; ?>
				<?php if ($lead['email'] && is_email($lead['email'])) : ?><a href="mailto:<?php echo esc_attr($lead['email']); ?>"><?php echo esc_html($lead['email']); ?></a><?php endif; ?>
				<?php if ($lead['zip']) : ?><span class="wvi-muted">ZIP <?php echo esc_html($lead['zip']); ?></span><?php endif; ?>
			</span>
			<?php if ($lead['detail']) : ?><span class="wvi-sub wvi-wrap"><?php echo esc_html($lead['detail']); ?></span><?php endif; ?>
			<?php foreach ($lead['also'] ?? array() as $also) : ?><span class="wvi-sub wvi-wrap" title="Same person, counted once"><span class="dashicons dashicons-plus-alt2" style="font-size:12px;width:12px;height:12px;vertical-align:-2px"></span> Also: <?php echo esc_html($also); ?></span><?php endforeach; ?>
		</td>
		<td class="wvi-lead-wants">
			<?php echo esc_html($lead['wants'] ?: '—'); ?>
			<?php if ($lead['specs']) : ?>
				<span class="wvi-lead-specs">
					<?php foreach (array_slice($lead['specs'], 0, 6, true) as $label => $value) : ?>
						<span class="wvi-chip"><?php echo esc_html((is_string($label) ? $label . ': ' : '') . $value); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
			<?php if ($lead['message'] || $lead['files']) : ?>
				<details class="wvi-details"><summary><?php echo esc_html($lead['message'] ? 'Message' : ''); ?><?php echo $lead['files'] ? esc_html(($lead['message'] ? ' · ' : '') . sprintf(_n('%d file', '%d files', $lead['files']), $lead['files'])) : ''; ?></summary><?php echo $lead['message'] ? '<p>' . esc_html($lead['message']) . '</p>' : ''; ?></details>
			<?php endif; ?>
		</td>
		<td><?php echo wholesale_vi_lead_source_html($lead['source']); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?></td>
		<td class="wvi-path">
			<?php if ($lead['source'] && '' !== $lead['source']['landing']) : ?><span title="Landed on <?php echo esc_attr($lead['source']['landing']); ?>"><?php echo esc_html(wholesale_vi_short_path(strtok($lead['source']['landing'], '?'), 34)); ?></span><?php endif; ?>
			<?php if ($lead['page'] && (!$lead['source'] || strtok($lead['page'], '?') !== strtok($lead['source']['landing'], '?'))) : ?><span class="wvi-sub" title="<?php echo esc_attr($lead['page']); ?>">→ <?php echo esc_html(wholesale_vi_short_path(strtok($lead['page'], '?'), 34)); ?></span><?php endif; ?>
		</td>
		<td class="wvi-nowrap"><?php echo $lead['device'] ? wholesale_vi_device_icon($lead['device']) : ''; // phpcs:ignore ?> <span class="wvi-sub-inline"><?php echo esc_html($lead['location'] ?: '—'); ?></span></td>
		<td>
			<?php if ($lead['order']) : ?>
				<?php if ($lead['order']['id']) : ?><a class="wvi-badge is-green" href="<?php echo esc_url(get_edit_post_link($lead['order']['id'])); ?>" title="<?php echo esc_attr($lead['order']['how']); ?>">Ordered <?php echo esc_html($lead['order']['number'] . ($lead['order']['total'] > 0 ? ' · ' . wholesale_vi_money($lead['order']['total']) : '')); ?></a>
				<?php else : ?><span class="wvi-badge is-green" title="<?php echo esc_attr($lead['order']['how']); ?>">Won <?php echo esc_html($lead['order']['number']); ?></span><?php endif; ?>
			<?php elseif ($lead['stage_label']) : ?>
				<span class="wvi-badge <?php echo 'lost' === $lead['stage'] ? 'is-red' : ('won' === $lead['stage'] ? 'is-green' : ''); ?>"><?php echo esc_html($lead['stage_label']); ?></span>
				<?php if ($lead['quote_amount'] > 0) : ?><span class="wvi-sub">Quoted <?php echo esc_html(wholesale_vi_money($lead['quote_amount'])); ?></span><?php endif; ?>
			<?php else : ?>
				<span class="wvi-muted">No order yet</span>
			<?php endif; ?>
			<?php if ($lead['overdue']) : ?><span class="wvi-badge is-red">Follow up</span><?php endif; ?>
		</td>
		<td class="wvi-nowrap">
			<?php if ($lead['edit_url']) : ?><a href="<?php echo esc_url($lead['edit_url']); ?>"><?php echo 'chat' === $lead['type'] ? 'Chat inbox' : 'Open'; ?></a><?php endif; ?>
			<?php if ($lead['visit_id']) : ?><?php echo $lead['edit_url'] ? ' · ' : ''; ?><a href="<?php echo esc_url(wholesale_vi_url('wholesale-insights-visitors', array('session' => $lead['visit_id']))); ?>">Journey →</a><?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Leads grouped by where they came from (source, then campaign).
 */
function wholesale_vi_render_lead_sources($leads)
{
	$groups = array();
	foreach ($leads as $lead) {
		$source = $lead['source'];
		$channel = $source ? $source['channel'] : '';
		$campaign = $source && '' !== $source['campaign'] ? $source['campaign'] : '';
		$key = $channel . '|' . ($campaign ? wholesale_vi_campaign_name($campaign) : '');
		if (!isset($groups[$key])) {
			$groups[$key] = array('channel' => $channel, 'campaign' => $campaign, 'quote' => 0, 'call' => 0, 'email' => 0, 'chat' => 0, 'total' => 0, 'won' => 0, 'value' => 0.0);
		}
		$groups[$key][$lead['type']]++;
		$groups[$key]['total']++;
		if ($lead['order']) {
			$groups[$key]['won']++;
			$groups[$key]['value'] += (float) $lead['order']['total'];
		}
	}
	uasort($groups, static function ($a, $b) {
		return $b['total'] <=> $a['total'];
	});
	?>
	<section class="wvi-card wvi-section">
		<div class="wvi-section-head">
			<h2>Where the leads came from</h2>
			<span class="wvi-muted">By source and campaign. Use it to see which campaigns bring people who ask for a price, not only clicks.</span>
		</div>
		<?php if (!$groups) : ?>
			<p class="wvi-empty">No leads in this range.</p>
		<?php else : ?>
			<div class="wvi-table-wrap">
				<table class="wvi-table">
					<thead><tr><th>Source</th><th>Campaign</th><th class="num">Leads</th><th class="num">Quotes</th><th class="num">Calls</th><th class="num">Emails</th><th class="num">Chats</th><th class="num">Became orders</th><th class="num">Order value</th></tr></thead>
					<tbody>
						<?php foreach ($groups as $group) : ?>
							<tr>
								<td><?php echo $group['channel'] ? wholesale_vi_channel_badge($group['channel']) : '<span class="wvi-muted">Not tracked</span>'; // phpcs:ignore ?></td>
								<td class="wvi-wrap"><?php echo $group['campaign'] ? wholesale_vi_campaign_html($group['campaign'], false) : '<span class="wvi-muted">—</span>'; // phpcs:ignore ?></td>
								<td class="num"><strong><?php echo esc_html($group['total']); ?></strong></td>
								<td class="num"><?php echo esc_html($group['quote']); ?></td>
								<td class="num"><?php echo esc_html($group['call']); ?></td>
								<td class="num"><?php echo esc_html($group['email']); ?></td>
								<td class="num"><?php echo esc_html($group['chat']); ?></td>
								<td class="num"><?php echo esc_html($group['won']); ?></td>
								<td class="num"><?php echo esc_html($group['value'] > 0 ? wholesale_vi_money($group['value']) : '—'); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/* ---------------------------------------------------------------------------
 * Get more orders: suggestions worked out from the data
 * ------------------------------------------------------------------------ */

/**
 * Average order value over the last 180 days, for sizing what a follow-up is
 * worth.
 */
function wholesale_vi_average_order()
{
	$totals = array();
	$since = time() - 180 * DAY_IN_SECONDS;
	foreach (wholesale_vi_orders_by_email() as $orders) {
		foreach ($orders as $order) {
			if ($order['total'] > 0 && strtotime($order['at'] . ' UTC') >= $since) {
				$totals[] = $order['total'];
			}
		}
	}

	return $totals ? array_sum($totals) / count($totals) : 0.0;
}

/**
 * Known people who added to the cart in the last 14 days and have not ordered
 * since, plus how many such carts belong to people who left no name.
 *
 * @return array array(people => array[], anonymous => int, anonymous_checkout => int).
 */
function wholesale_vi_abandoned_carts()
{
	global $wpdb;
	$sessions = wholesale_vi_table('sessions');
	$events = wholesale_vi_table('events');
	list($where, $params) = wholesale_vi_where(gmdate('Y-m-d H:i:s', time() - 14 * DAY_IN_SECONDS), gmdate('Y-m-d H:i:s', time() + 60), array());
	$rows = $wpdb->get_results($wpdb->prepare("SELECT s.* FROM {$sessions} s WHERE {$where} AND s.stage >= 4 AND s.order_id = 0 ORDER BY s.last_seen DESC LIMIT 100", $params));
	wholesale_vi_load_people($rows);
	$orders = wholesale_vi_orders_by_email();

	$people = array();
	$seen = array();
	$anonymous = array();
	$anonymous_checkout = array();
	foreach ($rows as $row) {
		// The visitor may have bought on a later visit.
		$bought = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sessions} WHERE (visitor_key = %s OR (%d > 0 AND user_id = %d)) AND order_id > 0 AND last_seen >= %s", $row->visitor_key, (int) $row->user_id, (int) $row->user_id, $row->started_at));
		if ($bought) {
			continue;
		}
		$person = wholesale_vi_person($row);
		if (!$person) {
			$anonymous[$row->visitor_key] = true;
			if ((int) $row->stage >= 5) {
				$anonymous_checkout[$row->visitor_key] = true;
			}
			continue;
		}
		$key = strtolower($person['email'] ?: $person['name']);
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		foreach ($orders[strtolower((string) $person['email'])] ?? array() as $order) {
			if ($order['at'] >= $row->started_at) {
				continue 2;
			}
		}
		$cart = $wpdb->get_row($wpdb->prepare(
			"SELECT a.value, a.url, (SELECT p.label FROM {$events} p WHERE p.session_id = a.session_id AND p.type = 'pageview' AND p.url = a.url ORDER BY p.id DESC LIMIT 1) AS title
			FROM {$events} a WHERE a.session_id = %d AND a.type = 'add_to_cart' ORDER BY a.id DESC LIMIT 1",
			$row->id
		));
		$phone = '';
		if (!empty($person['lead_id'])) {
			$phone = (string) get_post_meta($person['lead_id'], '_contact_phone', true);
		}
		$people[] = array(
			'name' => $person['name'],
			'email' => $person['email'],
			'phone' => $phone,
			'what' => $cart && $cart->title ? preg_replace('/\s*[|–-]\s*Storefront Sign Online.*$/i', '', $cart->title) : wholesale_vi_short_path(strtok((string) $row->exit_url, '?'), 40),
			'value' => $cart && is_numeric($cart->value) ? (float) $cart->value : 0.0,
			'when' => $row->last_seen,
			'stage' => (int) $row->stage >= 5 ? 'Left at checkout' : 'Left in cart',
			'link' => wholesale_vi_url('wholesale-insights-visitors', array('session' => $row->id)),
			'link_label' => 'Journey →',
		);
	}

	return array('people' => $people, 'anonymous' => count($anonymous), 'anonymous_checkout' => count($anonymous_checkout));
}

/**
 * The ranked suggestions for a range, cached for five minutes.
 *
 * @return array array(items => array[], generated => timestamp, aov => float).
 */
function wholesale_vi_growth($period)
{
	// "Last 30 days" ends now, so the key uses the end rounded to 5 minutes.
	$cache_key = 'wvi_growth_' . md5($period['start_gmt'] . '|' . floor(strtotime($period['end_gmt'] . ' UTC') / 300) . '|' . WHOLESALE_VI_DATA_REV . '|2');
	$cached = get_transient($cache_key);
	if (is_array($cached) && isset($cached['items'])) {
		return $cached;
	}

	global $wpdb;
	$start = $period['start_gmt'];
	$end = $period['end_gmt'];
	$ads_filter = array('channel' => 'google_ads');
	$items = array();
	$aov = wholesale_vi_average_order();
	$money = static function ($amount) {
		return '$' . number_format_i18n(round($amount));
	};

	$all = wholesale_vi_summary($start, $end);
	$ads = wholesale_vi_summary($start, $end, $ads_filter);

	// 1. Quote requests still waiting for an order (last 30 days).
	$recent = wholesale_vi_leads(array('start_gmt' => gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS), 'end_gmt' => gmdate('Y-m-d H:i:s', time() + 60)));
	$open = array_values(array_filter($recent, static function ($lead) {
		return 'quote' === $lead['type'] && !$lead['test'] && !$lead['repeat'] && !$lead['order'] && !in_array($lead['stage'], array('won', 'lost'), true);
	}));
	if ($open) {
		$overdue = count(array_filter($open, static function ($lead) {
			return $lead['overdue'];
		}));
		$fresh = count(array_filter($open, static function ($lead) {
			return strtotime($lead['at'] . ' UTC') > time() - 3 * DAY_IN_SECONDS;
		}));
		$items[] = array(
			'priority' => $overdue || $fresh ? 1 : 2,
			'area' => 'Follow up',
			'icon' => 'clipboard',
			'title' => sprintf(_n('Follow up %d open quote request', 'Follow up %d open quote requests', count($open)), count($open)),
			'why' => sprintf('They asked for a price in the last 30 days and have not ordered.%s%s Quote requests are the warmest buyers you have.', $overdue ? sprintf(' %d have had no reply for over a day.', $overdue) : '', $aov > 0 ? sprintf(' At your average order of %s, these are worth about %s.', $money($aov), $money($aov * count($open))) : ''),
			'steps' => array(
				'Reply the same day, by phone first if they gave a number: the first shop to answer usually wins the job.',
				'Send the price with a payment link so they can order in one step, and attach a mockup when you have one.',
				'Set Stage and Next follow-up on each request so none slips; the follow-up templates are on the request screen.',
			),
			'people' => array_map(static function ($lead) {
				return array(
					'name' => $lead['name'],
					'email' => $lead['email'],
					'phone' => $lead['phone'],
					'what' => $lead['wants'],
					'when' => $lead['at'],
					'stage' => ($lead['overdue'] ? 'Overdue · ' : '') . ($lead['stage_label'] ?: 'New'),
					'source' => $lead['source'] ? (wholesale_vi_channels()[$lead['source']['channel']] ?? '') . ('' !== $lead['source']['campaign'] ? ' · ' . wholesale_vi_campaign_name($lead['source']['campaign']) : '') : '',
					'link' => $lead['edit_url'],
					'link_label' => 'Open →',
				);
			}, array_slice($open, 0, 12)),
			'watch' => 'Leads tab → "Became orders" and the lead → order rate.',
			'link' => array(wholesale_vi_url('wholesale-insights-leads', array('period' => '30d', 'status' => 'open', 'type' => 'quote')), 'All open quote requests'),
		);
	}

	// 2. Known people who left a sign in the cart.
	$carts = wholesale_vi_abandoned_carts();
	if ($carts['people']) {
		$value = array_sum(array_column($carts['people'], 'value'));
		$items[] = array(
			'priority' => 1,
			'area' => 'Follow up',
			'icon' => 'cart',
			'title' => sprintf(_n('%d known customer left a sign in the cart', '%d known customers left signs in the cart', count($carts['people'])), count($carts['people'])),
			'why' => sprintf('In the last 14 days they added to the cart and have not ordered since%s. You have their name from an account, an earlier order or a quote request.', $value > 0 ? sprintf(' (carts worth about %s)', $money($value)) : ''),
			'steps' => array(
				'Send a short personal email or call: ask whether size, artwork or the delivery date is holding them up.',
				'Offer to check their artwork for free or to send a payment link for the exact cart.',
				'If you want to offer a discount, set it as a dated promotion in Settings (no permanent sale badges).',
			),
			'people' => $carts['people'],
			'watch' => 'Orders from these names in the next 7 days.',
		);
	}

	// 3. Carts from people who left no contact details.
	if ($carts['anonymous'] >= 3) {
		$items[] = array(
			'priority' => 2,
			'area' => 'Website',
			'icon' => 'cart',
			'title' => sprintf('%d shoppers left items in the cart without leaving any contact details', $carts['anonymous']),
			'why' => sprintf('Last 14 days%s. With no email or phone there is no way to follow up.', $carts['anonymous_checkout'] ? sprintf('; %d of them reached checkout', $carts['anonymous_checkout']) : ''),
			'steps' => array(
				'Keep the phone number and live chat visible on the cart and checkout pages so a question does not end the order.',
				'Offer "Email me this quote" on product pages, so people comparing prices leave an email you can follow up.',
				'In Google Ads, use a "visited cart, no purchase" audience as an audience signal for Performance Max.',
			),
			'watch' => 'Overview funnel: "Added to cart" → "Reached checkout".',
		);
	}

	// 4. Visitors who keep coming back and price signs.
	list($where30, $params30) = wholesale_vi_where(gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS), gmdate('Y-m-d H:i:s', time() + 60), array());
	$returning = (int) $wpdb->get_var($wpdb->prepare(
		'SELECT COUNT(*) FROM (SELECT s.visitor_key FROM ' . wholesale_vi_table('sessions') . " s WHERE {$where30} GROUP BY s.visitor_key HAVING COUNT(*) >= 2 AND MAX(s.stage) >= 3 AND SUM(s.order_id > 0) = 0 AND MAX(s.is_lead) = 0) x",
		$params30
	));
	if ($returning >= 5) {
		$items[] = array(
			'priority' => 2,
			'area' => 'Google Ads',
			'icon' => 'update',
			'title' => sprintf('%d people came back 2+ times and priced a sign, but never asked or bought', $returning),
			'why' => 'Last 30 days. They are interested and comparing. A reminder at the right moment often wins the order.',
			'steps' => array(
				'Make sure the remarketing audience of site visitors who priced a sign is attached to your Performance Max campaigns as an audience signal.',
				'On product pages, show what they look for when they come back: reviews, real customer photos, turnaround time and the free-shipping threshold.',
				'Add a visible "Questions? Call us" next to the price so the second visit turns into a conversation.',
			),
			'watch' => 'Returning visitors who reach the cart (Visitors tab, outcome "Abandoned cart").',
		);
	}

	// 5. Ad clicks from networks that bring nothing.
	$networks = wholesale_vi_breakdown(wholesale_vi_network_sql('s'), $start, $end, $ads_filter, 10);
	$names = wholesale_vi_ad_networks();
	$waste = array();
	$waste_networks = array();
	$waste_clicks = 0;
	$search_row = null;
	foreach ($networks as $row) {
		if ('search' === $row['label']) {
			$search_row = $row;
		} elseif ($row['label'] && $row['sessions'] >= 15 && !$row['s4'] && !$row['leads'] && !$row['orders']) {
			$waste[] = sprintf('%s: %d clicks, %s bounce, %s avg. time, no cart, lead or order', $names[$row['label']] ?? $row['label'], $row['sessions'], wholesale_vi_pct($row['bounced'], $row['sessions']), wholesale_vi_duration($row['avg_engaged']));
			$waste_networks[] = $row['label'];
			$waste_clicks += (int) $row['sessions'];
		}
	}
	if ($waste && $ads['sessions'] > 0) {
		$share = round(100 * $waste_clicks / $ads['sessions']);
		$placements = wholesale_vi_breakdown("SUBSTRING_INDEX(SUBSTRING_INDEX(s.referrer, '/', 3), '/', -1)", $start, $end, $ads_filter, 25, wholesale_vi_placement_sql());
		$hosts = array();
		foreach ($placements as $row) {
			$host = preg_replace('/^www\./', '', (string) $row['label']);
			if ($host && $row['sessions'] >= 2 && !$row['s4'] && !$row['leads'] && !$row['orders'] && !preg_match('/(^|\.)(google\.[a-z.]+|doubleclick\.net|googlesyndication\.com|syndicatedsearch\.goog|localhost)$/', $host)) {
				$hosts[] = $host;
			}
		}
		$steps = array();
		if (in_array('youtube', $waste_networks, true)) {
			$steps[] = 'YouTube clicks in Performance Max come from its video and image assets: a feed-only asset group (no videos, images or text) keeps that budget on Shopping.';
		}
		if (array_intersect(array('display', 'partners', 'sites', 'gmail'), $waste_networks)) {
			$steps[] = 'Partner and Display websites: add the sites listed below to an account-level placement exclusion list (Tools → Content suitability → Excluded placements). For Search campaigns, also turn off "Search partners" in the campaign\'s Networks setting.';
		}
		if (in_array('app', $waste_networks, true)) {
			$steps[] = '"Apps (no referrer)" clicks come from ads inside phone apps: exclude mobile app categories under Content suitability if they keep bouncing.';
		}
		$items[] = array(
			'priority' => $share >= 25 ? 1 : 2,
			'area' => 'Google Ads',
			'icon' => 'megaphone',
			'title' => sprintf('%d%% of ad clicks came from places that brought no cart, lead or order', $share),
			'why' => implode('. ', $waste) . '.' . ($search_row ? sprintf(' For comparison, Google Search & Shopping: %d clicks, %d carts, %d leads and orders.', $search_row['sessions'], $search_row['s4'], $search_row['leads'] + $search_row['orders']) : ''),
			'steps' => $steps,
			'items' => array_slice($hosts, 0, 20),
			'copy' => implode("\n", array_slice($hosts, 0, 20)),
			'watch' => 'Google Ads tab → "Where the ads were clicked": the Search & Shopping share should rise.',
			'link' => array(wholesale_vi_url('wholesale-insights-ads', array('period' => $period['key'] ?? '30d')), 'Google Ads tab'),
		);
	}

	// 6. Campaigns with clicks and nothing to show for them.
	$campaigns = wholesale_vi_merge_campaigns(wholesale_vi_breakdown("IF(s.campaign = '', '(not set)', s.campaign)", $start, $end, $ads_filter, 40));
	$credited = array();
	foreach (wholesale_vi_orders_report($period, $ads_filter, 200) as $order) {
		if ($order['source'] && '' !== $order['source']['campaign']) {
			$name = wholesale_vi_campaign_name($order['source']['campaign']);
			$credited[$name] = ($credited[$name] ?? 0) + 1;
		}
	}
	$dead = array();
	$best = null;
	foreach ($campaigns as $row) {
		if ('(not set)' === $row['label']) {
			continue;
		}
		$name = wholesale_vi_campaign_name($row['label']);
		$wins = max((int) $row['orders'], $credited[$name] ?? 0) + (int) $row['leads'];
		if ($row['sessions'] >= 40 && !$wins && !$row['s4']) {
			$dead[] = sprintf('%s: %d clicks, %s bounce, %d got a price, 0 carts, leads or orders', $name, $row['sessions'], wholesale_vi_pct($row['bounced'], $row['sessions']), $row['s3']);
		}
		if ($row['sessions'] >= 20 && $wins && (!$best || $wins / $row['sessions'] > $best['rate'])) {
			$best = array('name' => $name, 'rate' => $wins / $row['sessions'], 'wins' => $wins, 'clicks' => (int) $row['sessions']);
		}
	}
	if ($dead) {
		$items[] = array(
			'priority' => 2,
			'area' => 'Google Ads',
			'icon' => 'chart-bar',
			'title' => sprintf(_n('%d campaign got clicks but no cart, lead or order', '%d campaigns got clicks but no cart, lead or order', count($dead)), count($dead)),
			'why' => implode('. ', $dead) . '.' . ($best ? sprintf(' By contrast, %s turned %d of %d clicks into leads or orders.', $best['name'], $best['wins'], $best['clicks']) : ''),
			'steps' => array(
				'Open the campaign\'s search terms (Search) or placements and channel report (Performance Max) and see what people clicked from.',
				$best ? sprintf('Give the budget first to the campaign that converts (%s), and lower or pause the ones above after their learning period.', $best['name']) : 'Tighten the products, audiences or keywords, or pause the campaign after its learning period.',
				'Check the landing page of each one in "Ad landing pages with high bounce" below.',
			),
			'watch' => 'Google Ads tab → Campaigns: "Orders earned" and "Conv." per campaign.',
		);
	}

	// 7. Ad landing pages people leave at once.
	$pages = array();
	foreach (wholesale_vi_breakdown("SUBSTRING_INDEX(s.landing_url, '?', 1)", $start, $end, $ads_filter, 15) as $row) {
		if ($row['sessions'] >= 20 && $row['bounced'] / max(1, $row['sessions']) >= 0.6) {
			$pages[] = sprintf('%s: %d ad clicks, %s bounce', wholesale_vi_short_path($row['label'], 60), $row['sessions'], wholesale_vi_pct($row['bounced'], $row['sessions']));
		}
	}
	if ($pages) {
		$items[] = array(
			'priority' => 2,
			'area' => 'Website',
			'icon' => 'welcome-view-site',
			'title' => sprintf(_n('%d ad landing page loses most visitors in seconds', '%d ad landing pages lose most visitors in seconds', count($pages)), count($pages)),
			'why' => 'Bounce = one page, under 10 seconds, no action.',
			'items' => $pages,
			'steps' => array(
				'The first screen on a phone must show the product from the ad, its starting price and one clear button (Start building, See prices, Add to cart).',
				'Make sure the ad\'s product and price match the page; a mismatch looks like a bait-and-switch.',
				'Check the page speed on a phone; slow pages lose ad clicks before they load.',
			),
			'watch' => 'Google Ads tab → Landing pages: bounce under 50%.',
		);
	}

	// 8. Products people price but do not add to the cart.
	list($where, $params) = wholesale_vi_where($start, $end, array());
	$events = wholesale_vi_table('events');
	$priced = $wpdb->get_results($wpdb->prepare(
		"SELECT SUBSTRING_INDEX(e.url, '?', 1) AS page, MAX(e.label) AS label, COUNT(DISTINCT e.session_id) AS priced, COUNT(DISTINCT a.session_id) AS added
		FROM {$events} e JOIN " . wholesale_vi_table('sessions') . " s ON s.id = e.session_id
		LEFT JOIN {$events} a ON a.session_id = e.session_id AND a.type = 'add_to_cart'
		WHERE e.type = 'price_quote' AND {$where} GROUP BY page HAVING priced >= 5 ORDER BY priced DESC LIMIT 10",
		$params
	), ARRAY_A);
	$stuck = array();
	foreach ((array) $priced as $row) {
		if ($row['added'] / max(1, $row['priced']) < 0.15) {
			$stuck[] = sprintf('%s: %d priced it, %d added to cart', wholesale_vi_short_path($row['page'], 60), $row['priced'], $row['added']);
		}
	}
	if ($stuck) {
		$items[] = array(
			'priority' => 2,
			'area' => 'Website',
			'icon' => 'tag',
			'title' => sprintf(_n('%d product is priced often but rarely added to the cart', '%d products are priced often but rarely added to the cart', count($stuck)), count($stuck)),
			'why' => 'People got a price and stopped there. The price, or what it includes, did not convince them.',
			'items' => $stuck,
			'steps' => array(
				'Compare the price shown with what the ad, Google Shopping and competitors show for the same size.',
				'Next to the price, say what is included: shipping threshold, turnaround, proof before printing, warranty.',
				'For large or custom sizes, offer "Get a quote" beside Add to cart so high-ticket buyers can talk first.',
			),
			'watch' => 'Overview funnel: "Configured / got a price" → "Added to cart".',
		);
	}

	// 9. Errors shown to people who wanted to buy.
	$problems = wholesale_vi_event_breakdown(array('checkout_error', 'payment_error', 'cart_error', 'upload_error', 'price_error'), $start, $end, array(), 8);
	$blocking = array_filter($problems, static function ($row) {
		return in_array($row['type'], array('checkout_error', 'payment_error', 'cart_error', 'upload_error'), true);
	});
	if ($blocking) {
		$items[] = array(
			'priority' => 1,
			'area' => 'Checkout',
			'icon' => 'warning',
			'title' => sprintf('Fix the errors that stopped %d visitor(s) from buying', array_sum(array_column($blocking, 'sessions'))),
			'why' => 'These people tried to add to cart, upload artwork or pay, and saw an error.',
			'items' => array_map(static function ($row) {
				return wholesale_vi_event_label($row['type']) . ': ' . ($row['label'] ?: '—') . sprintf(' (%d×, %d visitor(s))', $row['hits'], $row['sessions']);
			}, array_values($blocking)),
			'steps' => array(
				'Open the affected visits from Overview → "Problems visitors hit" and repeat the steps.',
				'Contact anyone who left a name or email: they wanted to buy.',
			),
			'watch' => 'Overview → "Problems visitors hit" should list none.',
			'link' => array(wholesale_vi_url('wholesale-insights', array('period' => $period['key'] ?? '30d')), 'Overview'),
		);
	}

	// 10. Checkout opened, never paid.
	if ($all['s5'] >= 3 && $all['s6'] < $all['s5'] * 0.5) {
		$field = '';
		foreach (wholesale_vi_form_report($start, $end) as $form) {
			if ('Checkout' === $form['label'] && $form['left_at']) {
				$field = $form['left_at'];
			}
		}
		$items[] = array(
			'priority' => 1,
			'area' => 'Checkout',
			'icon' => 'money-alt',
			'title' => sprintf('%d people opened checkout but only %d clicked to pay', $all['s5'], $all['s6']),
			'why' => $field ? sprintf('Most of those who gave up stopped at the "%s" field.', $field) : 'They saw the checkout form and stopped before paying.',
			'steps' => array(
				'Show the full total (with shipping and tax) above the form, so nothing changes at the last step.',
				'Put the phone number and live chat on checkout for people who want to ask before paying.',
				'Shorten the form: ask only what the order needs.',
			),
			'watch' => 'Overview funnel: "Reached checkout" → "Tried to pay".',
		);
	}

	// 11. Forms people start and give up.
	foreach (wholesale_vi_form_report($start, $end) as $form) {
		// A product's option form ends in Add to cart, which the product
		// suggestions above cover.
		if ('Checkout' !== $form['label'] && false === strpos($form['label'], ': order options') && $form['started'] >= 5 && $form['submitted'] < $form['started'] * 0.5) {
			$items[] = array(
				'priority' => 2,
				'area' => 'Website',
				'icon' => 'feedback',
				'title' => sprintf('"%s": %d started, %d sent', $form['label'], $form['started'], $form['submitted']),
				'why' => $form['left_at'] ? sprintf('Most who gave up stopped at "%s" (%d).', $form['left_at'], $form['left_at_n']) : 'Most who started never sent it.',
				'steps' => array(
					'Make that field optional or explain why you need it.',
					'Keep only name, phone or email, and what they need; ask for details later.',
				),
				'watch' => 'Overview → "Form abandonment".',
			);
			break;
		}
	}

	// 12. Phones convert worse than computers.
	$devices = array();
	foreach (wholesale_vi_breakdown('s.device', $start, $end, array(), 3) as $row) {
		$devices[$row['label']] = $row;
	}
	if (!empty($devices['mobile']) && !empty($devices['desktop']) && $devices['mobile']['sessions'] >= 30 && $devices['desktop']['sessions'] >= 10) {
		$mobile = ($devices['mobile']['orders'] + $devices['mobile']['leads']) / $devices['mobile']['sessions'];
		$desktop = ($devices['desktop']['orders'] + $devices['desktop']['leads']) / $devices['desktop']['sessions'];
		if ($desktop > 0 && $mobile < $desktop * 0.6) {
			$items[] = array(
				'priority' => 2,
				'area' => 'Website',
				'icon' => 'smartphone',
				'title' => sprintf('Phones convert at %s, computers at %s', wholesale_vi_pct($mobile * 100, 100, 1), wholesale_vi_pct($desktop * 100, 100, 1)),
				'why' => sprintf('%d phone visits vs %d computer visits in this range.', $devices['mobile']['sessions'], $devices['desktop']['sessions']),
				'steps' => array(
					'Order a sign on a phone yourself, start to finish, and note every step that is hard.',
					'Make "Call" and "Get a quote" one tap away on phones; many sign buyers prefer to talk.',
				),
				'watch' => 'Overview → Devices: the phone "Conv." column.',
			);
		}
	}

	// 13. The traffic that already buys best.
	$best_channel = null;
	$ads_rate = $ads['sessions'] > 0 ? ($ads['orders'] + $ads['leads']) / $ads['sessions'] : 0;
	foreach (wholesale_vi_breakdown('s.channel', $start, $end, array(), 10) as $row) {
		if (in_array($row['label'], array('google_ads', 'direct', 'paid_other'), true) || $row['sessions'] < 10) {
			continue;
		}
		$rate = ($row['orders'] + $row['leads']) / $row['sessions'];
		if ($rate > max(0.01, $ads_rate * 1.5) && (!$best_channel || $rate > $best_channel['rate'])) {
			$best_channel = array('channel' => $row['label'], 'rate' => $rate, 'sessions' => (int) $row['sessions'], 'wins' => (int) ($row['orders'] + $row['leads']));
		}
	}
	if ($best_channel) {
		$label = wholesale_vi_channels()[$best_channel['channel']] ?? $best_channel['channel'];
		$steps = 'ai' === $best_channel['channel']
			? array(
				'ChatGPT and other assistants quote what product pages say: keep sizes, materials, "from" prices, shipping and turnaround as plain text on each page.',
				'Answer the common buyer questions (how long, how big, what file to send, is it outdoor-rated) in FAQs on product and category pages.',
				'Ask happy customers for reviews; assistants favor shops with clear reviews.',
			)
			: array(
				'Keep growing this source: it already turns visits into leads and orders better than ads.',
				'Look at the pages these visitors land on and give similar pages the same care.',
			);
		$items[] = array(
			'priority' => 3,
			'area' => 'Growth',
			'icon' => 'star-filled',
			'title' => sprintf('Visitors from %s convert %s better than ad clicks', 'ai' === $best_channel['channel'] ? 'AI assistants (ChatGPT, Copilot…)' : strtolower($label), $ads_rate > 0 ? round($best_channel['rate'] / $ads_rate, 1) . '×' : 'far'),
			'why' => sprintf('%d visits → %d leads and orders (%s), vs %s for Google Ads.', $best_channel['sessions'], $best_channel['wins'], wholesale_vi_pct($best_channel['rate'] * 100, 100, 1), wholesale_vi_pct($ads_rate * 100, 100, 1)),
			'steps' => $steps,
			'watch' => 'Overview → Traffic sources.',
		);
	}

	// 14. Quote requests rarely become orders.
	$quarter = wholesale_vi_leads(array('start_gmt' => gmdate('Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS), 'end_gmt' => gmdate('Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS)));
	$quotes = array_filter($quarter, static function ($lead) {
		return 'quote' === $lead['type'] && !$lead['test'] && !$lead['repeat'];
	});
	$quotes_won = array_filter($quotes, static function ($lead) {
		return $lead['order'] || 'won' === $lead['stage'];
	});
	if (count($quotes) >= 5 && count($quotes_won) < count($quotes) * 0.2) {
		$items[] = array(
			'priority' => 2,
			'area' => 'Follow up',
			'icon' => 'groups',
			'title' => sprintf('Only %d of %d quote requests became orders', count($quotes_won), count($quotes)),
			'why' => 'Quote requests from 7 to 90 days ago. Mark won and lost requests (with the reason) so this number is exact.',
			'steps' => array(
				'Quote within hours, not days, and follow up twice: 2 days and 7 days after the quote.',
				'Send a mockup with the price: buyers say yes to what they can see.',
				'When a request is lost, set the Lost reason (price, timing, installation, no response) to see what to fix.',
			),
			'watch' => 'Leads tab → "Lead → order rate".',
		);
	}

	// 15. Ads seen outside the US.
	$foreign = 0;
	foreach (wholesale_vi_breakdown('s.timezone', $start, $end, $ads_filter, 50) as $row) {
		if (wholesale_vi_is_foreign_tz($row['label'])) {
			$foreign += (int) $row['sessions'];
		}
	}
	if ($ads['sessions'] >= 30 && $foreign / $ads['sessions'] >= 0.08) {
		$items[] = array(
			'priority' => 2,
			'area' => 'Google Ads',
			'icon' => 'location',
			'title' => sprintf('%d%% of ad clicks came from outside the US', round(100 * $foreign / $ads['sessions'])),
			'why' => sprintf('%d of %d ad clicks had a non-US browser time zone. The shop only ships in the US.', $foreign, $ads['sessions']),
			'steps' => array(
				'In each campaign\'s Locations, choose "Presence: people in or regularly in your included locations".',
			),
			'watch' => 'Google Ads tab → Locations: "Outside US" rows should disappear.',
		);
	}

	if (!$items) {
		$items[] = array(
			'priority' => 3,
			'area' => 'Growth',
			'icon' => 'yes-alt',
			'title' => 'No urgent problem found',
			'why' => sprintf('%d visits in this range. Suggestions appear as soon as the data shows a clear opportunity.', $all['sessions']),
			'steps' => array('Keep replying to quote requests quickly and check this page weekly.'),
		);
	}

	usort($items, static function ($a, $b) {
		return $a['priority'] <=> $b['priority'];
	});
	$result = array('items' => $items, 'generated' => time(), 'aov' => $aov);
	set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

	return $result;
}

function wholesale_vi_render_growth()
{
	if (empty($_GET['period']) && empty($_GET['from'])) {
		$_GET['period'] = '30d';
	}
	$period = wholesale_vi_period();
	wholesale_vi_render_toolbar($period, 'wholesale-insights-grow', array(), array());
	$growth = wholesale_vi_growth($period);
	$priorities = array(1 => array('Do today', 'is-red'), 2 => array('This week', 'is-amber'), 3 => array('When you can', 'is-blue'));
	?>
	<section class="wvi-card wvi-section wvi-grow-intro">
		<div class="wvi-section-head">
			<h2><span class="dashicons dashicons-lightbulb"></span> How to get more orders</h2>
			<span class="wvi-muted">Worked out from <?php echo esc_html(strtolower($period['label'])); ?> of visits, leads and orders (follow-up lists use the last 14–30 days) · updated <?php echo esc_html(human_time_diff($growth['generated'], time())); ?> ago<?php echo $growth['aov'] > 0 ? esc_html(' · average order ' . wholesale_vi_money($growth['aov'])) : ''; ?></span>
		</div>
		<p class="wvi-muted">People to contact come first: they already showed they want to buy. Then the ad spend and website steps that lose the most buyers. Nothing here changes the site or Google Ads by itself.</p>
	</section>
	<div class="wvi-grow">
		<?php foreach ($growth['items'] as $i => $item) : ?>
			<?php $priority = $priorities[$item['priority']] ?? $priorities[3]; ?>
			<section class="wvi-card wvi-section wvi-grow-item is-p<?php echo esc_attr($item['priority']); ?>">
				<div class="wvi-grow-head">
					<span class="wvi-grow-icon dashicons dashicons-<?php echo esc_attr($item['icon']); ?>"></span>
					<div>
						<span class="wvi-badge <?php echo esc_attr($priority[1]); ?>"><?php echo esc_html($priority[0]); ?></span>
						<span class="wvi-chip"><?php echo esc_html($item['area']); ?></span>
						<h2><?php echo esc_html(($i + 1) . '. ' . $item['title']); ?></h2>
						<p><?php echo esc_html($item['why']); ?></p>
					</div>
				</div>
				<?php if (!empty($item['people'])) : ?>
					<div class="wvi-table-wrap">
						<table class="wvi-table wvi-people">
							<thead><tr><th>Who</th><th>Contact</th><th>Wanted</th><th>When</th><th>Status</th><th></th></tr></thead>
							<tbody>
								<?php foreach ($item['people'] as $person) : ?>
									<?php $phone = preg_replace('/[^0-9+]/', '', (string) ($person['phone'] ?? '')); ?>
									<tr>
										<td><strong><?php echo esc_html($person['name'] ?: '(no name)'); ?></strong><?php if (!empty($person['source'])) : ?><span class="wvi-sub"><?php echo esc_html($person['source']); ?></span><?php endif; ?></td>
										<td class="wvi-lead-contact">
											<?php if ($phone) : ?><a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($person['phone']); ?></a><?php endif; ?>
											<?php if (!empty($person['email']) && is_email($person['email'])) : ?><a href="mailto:<?php echo esc_attr($person['email']); ?>"><?php echo esc_html($person['email']); ?></a><?php endif; ?>
										</td>
										<td><?php echo esc_html($person['what'] ?: '—'); ?><?php echo !empty($person['value']) ? ' <span class="wvi-muted">' . esc_html(wholesale_vi_money($person['value'])) . '</span>' : ''; ?></td>
										<td class="wvi-nowrap"><?php echo esc_html(wholesale_vi_ago($person['when'])); ?></td>
										<td><span class="wvi-badge <?php echo false !== strpos((string) $person['stage'], 'Overdue') ? 'is-red' : ''; ?>"><?php echo esc_html($person['stage']); ?></span></td>
										<td class="wvi-nowrap"><?php if (!empty($person['link'])) : ?><a href="<?php echo esc_url($person['link']); ?>"><?php echo esc_html($person['link_label']); ?></a><?php endif; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
				<?php if (!empty($item['items'])) : ?>
					<ul class="wvi-grow-list">
						<?php foreach ($item['items'] as $line) : ?>
							<li><?php echo esc_html($line); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if (!empty($item['copy'])) : ?>
						<p><button type="button" class="button wvi-copy" data-copy="<?php echo esc_attr($item['copy']); ?>">Copy the site list</button></p>
					<?php endif; ?>
				<?php endif; ?>
				<div class="wvi-grow-steps">
					<strong>Do this</strong>
					<ol>
						<?php foreach ($item['steps'] as $step) : ?>
							<li><?php echo esc_html($step); ?></li>
						<?php endforeach; ?>
					</ol>
				</div>
				<?php if (!empty($item['watch']) || !empty($item['link'])) : ?>
					<p class="wvi-muted wvi-grow-watch">
						<?php if (!empty($item['watch'])) : ?><span class="dashicons dashicons-visibility"></span> Check it worked: <?php echo esc_html($item['watch']); ?><?php endif; ?>
						<?php if (!empty($item['link'])) : ?> <a href="<?php echo esc_url($item['link'][0]); ?>"><?php echo esc_html($item['link'][1]); ?> →</a><?php endif; ?>
					</p>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * The top suggestions, small, on the Overview and Google Ads tabs.
 */
function wholesale_vi_render_growth_teaser($period)
{
	$growth = wholesale_vi_growth($period);
	$top = array_slice(array_filter($growth['items'], static function ($item) {
		return $item['priority'] <= 2;
	}), 0, 3);
	if (!$top) {
		return;
	}
	?>
	<section class="wvi-card wvi-grow-teaser">
		<span class="wvi-grow-icon dashicons dashicons-lightbulb"></span>
		<div>
			<strong>To get more orders</strong>
			<ul>
				<?php foreach ($top as $item) : ?>
					<li><span class="wvi-badge <?php echo 1 === $item['priority'] ? 'is-red' : 'is-amber'; ?>"><?php echo 1 === $item['priority'] ? 'Today' : 'This week'; ?></span> <?php echo esc_html($item['title']); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<a class="button" href="<?php echo esc_url(wholesale_vi_url('wholesale-insights-grow', array_filter(array('period' => $period['key'], 'from' => 'custom' === $period['key'] ? $period['start']->format('Y-m-d') : '', 'to' => 'custom' === $period['key'] ? $period['end']->modify('-1 second')->format('Y-m-d') : '')))); ?>">See all <?php echo esc_html(count($growth['items'])); ?> suggestions →</a>
	</section>
	<?php
}

/**
 * The suggestions and a lead summary for the exported report: no names,
 * emails or phone numbers.
 */
function wholesale_vi_md_growth($period)
{
	$md = "## Suggestions to get more orders\n\nWorked out by Visitor Insights from the same data (people lists are counts only here).\n\n";
	foreach (wholesale_vi_growth($period)['items'] as $i => $item) {
		$md .= sprintf("%d. **%s** (%s, %s). %s\n", $i + 1, $item['title'], $item['area'], array(1 => 'do today', 2 => 'this week', 3 => 'when possible')[$item['priority']] ?? '', $item['why']);
		if (!empty($item['people'])) {
			$md .= sprintf("   - %d people listed in the admin (names hidden here).\n", count($item['people']));
		}
		foreach (array_slice($item['items'] ?? array(), 0, 10) as $line) {
			$md .= '   - ' . wholesale_vi_md_cell($line) . "\n";
		}
		foreach ($item['steps'] as $step) {
			$md .= '   - Do: ' . $step . "\n";
		}
	}

	$leads = array_filter(wholesale_vi_leads($period), static function ($lead) {
		return !$lead['test'] && !$lead['repeat'];
	});
	$rows = array();
	$groups = array();
	foreach ($leads as $lead) {
		$source = $lead['source'];
		$key = ($source ? (wholesale_vi_channels()[$source['channel']] ?? $source['channel']) : 'Not tracked') . ' | ' . ($source && '' !== $source['campaign'] ? wholesale_vi_campaign_name($source['campaign']) : '—');
		$groups[$key][$lead['type']] = ($groups[$key][$lead['type']] ?? 0) + 1;
		$groups[$key]['won'] = ($groups[$key]['won'] ?? 0) + ($lead['order'] ? 1 : 0);
	}
	foreach ($groups as $key => $counts) {
		list($channel, $campaign) = explode(' | ', $key);
		$rows[] = array($channel, $campaign, $counts['quote'] ?? 0, $counts['call'] ?? 0, $counts['email'] ?? 0, $counts['chat'] ?? 0, $counts['won']);
	}
	$md .= "\n## Leads by source\n\n" . wholesale_vi_md_table(array('Source', 'Campaign', 'Quote requests', 'Call taps', 'Email clicks', 'Chats', 'Became orders'), $rows) . "\n";

	return $md;
}
