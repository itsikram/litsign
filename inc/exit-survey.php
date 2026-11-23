<?php
/**
 * Exit survey: asks visitors who are about to leave without ordering what
 * stopped them, and reports the answers under Visitor Insights → Exit Survey.
 *
 * js/exit-survey.js opens the survey when a desktop visitor moves the mouse
 * out toward the tab bar or address bar, or when a phone visitor scrolls back
 * up quickly. It is never shown on order, account or thank-you pages, to
 * visitors who have ordered, or more than once per visit. Each answer is tied
 * to the visitor's Visitor Insights visit, so the admin can see the journey
 * that led to it.
 *
 * Browsers do not let a site show its own message when a tab is closed, only
 * a generic "Leave site?" box, so leaving intent is the closest signal there
 * is.
 *
 * @package litsign
 */

const WHOLESALE_ES_DB_VERSION = '1.0';

/* ---------------------------------------------------------------------------
 * Settings and storage
 * ------------------------------------------------------------------------ */

function wholesale_es_settings()
{
	$saved = get_option('wholesale_es_settings', array());

	return wp_parse_args(is_array($saved) ? $saved : array(), array(
		'enabled' => 1,
		'mobile' => 1,
		'min_seconds' => 8,
		'snooze_days' => 7,
		'notify' => 1,
	));
}

function wholesale_es_setting($key)
{
	$settings = wholesale_es_settings();

	return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * The on/off switch on Settings > Storefront Sign posts
 * wholesale_exit_survey_enabled. It is saved into wholesale_es_settings, the
 * same value the Exit Survey page edits, and never stored as its own option.
 */
function wholesale_es_register_theme_setting()
{
	register_setting('wholesale_settings', 'wholesale_exit_survey_enabled', array(
		'type' => 'integer',
		'sanitize_callback' => static function ($value) {
			return is_scalar($value) && '1' === (string) $value ? 1 : 0;
		},
	));
}
add_action('admin_init', 'wholesale_es_register_theme_setting');

function wholesale_es_save_theme_setting($value, $old_value)
{
	$saved = get_option('wholesale_es_settings', array());
	$saved = is_array($saved) ? $saved : array();
	$saved['enabled'] = $value ? 1 : 0;
	update_option('wholesale_es_settings', $saved);

	return $old_value;
}
add_filter('pre_update_option_wholesale_exit_survey_enabled', 'wholesale_es_save_theme_setting', 10, 2);

function wholesale_es_table()
{
	global $wpdb;

	return $wpdb->prefix . 'wholesale_exit_survey';
}

function wholesale_es_install()
{
	if (WHOLESALE_ES_DB_VERSION === get_option('wholesale_es_db_version')) {
		return;
	}

	global $wpdb;
	$table = wholesale_es_table();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta("CREATE TABLE {$table} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		created_at DATETIME NOT NULL,
		answered_at DATETIME NULL,
		status VARCHAR(10) NOT NULL DEFAULT 'shown',
		reason VARCHAR(20) NOT NULL DEFAULT '',
		details TEXT NULL,
		contact VARCHAR(190) NOT NULL DEFAULT '',
		trigger_type VARCHAR(10) NOT NULL DEFAULT '',
		page_url VARCHAR(500) NOT NULL DEFAULT '',
		page_type VARCHAR(20) NOT NULL DEFAULT '',
		device VARCHAR(10) NOT NULL DEFAULT '',
		channel VARCHAR(20) NOT NULL DEFAULT '',
		cart_items SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		session_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY created_at (created_at),
		KEY status_created (status, created_at),
		KEY session_id (session_id)
	) {$charset_collate};");

	update_option('wholesale_es_db_version', WHOLESALE_ES_DB_VERSION);
}
add_action('init', 'wholesale_es_install');

/**
 * The answers a visitor can pick, with the reply shown after they submit.
 * Replies must stay true to how the business works: signs are made and
 * shipped, never installed.
 */
function wholesale_es_reasons()
{
	return array(
		'browsing' => array(
			'label' => 'Just looking or comparing for now',
			'icon' => 'search',
			'placeholder' => 'What are you comparing, or what would help you decide?',
			'reply' => 'No rush. Prices show instantly on every product page whenever you are ready, and our team is happy to answer questions.',
		),
		'price' => array(
			'label' => 'The price was higher than I expected',
			'icon' => 'tag',
			'placeholder' => 'What price were you hoping for, and for which sign?',
			'reply' => 'Thanks for telling us. A sign specialist can often suggest a size, material or lighting option that fits your budget.',
		),
		'unsure' => array(
			'label' => 'Not sure which sign, size or option I need',
			'icon' => 'help',
			'placeholder' => 'What sign are you planning? Any size or wall details help.',
			'reply' => 'We can help you choose. Send us a photo of your storefront and the wall size, and we will recommend the right sign.',
		),
		'talk' => array(
			'label' => 'I want to talk to someone or get a quote first',
			'icon' => 'chat',
			'placeholder' => 'What would you like to ask us?',
			'reply' => 'Our sign specialists are happy to help. Call or text us, Monday to Friday, 8 am to 5 pm Pacific.',
		),
		'timing' => array(
			'label' => 'Production or delivery time does not work for me',
			'icon' => 'clock',
			'placeholder' => 'When do you need the sign by?',
			'reply' => 'We ship to all 50 states, with 3-day, 2-day and overnight shipping available. Tell us your deadline and we will tell you what is possible.',
		),
		'install' => array(
			'label' => 'I need someone to install it',
			'icon' => 'tool',
			'placeholder' => 'Where is the sign going? Any questions about installing it?',
			'reply' => 'We make and ship signs ready to install, but we do not install them. Any licensed electrician or sign installer can put it up; every sign ships tested, with a wiring diagram and install pattern.',
		),
		'missing' => array(
			'label' => 'I could not find what I was looking for',
			'icon' => 'box',
			'placeholder' => 'What were you looking for?',
			'reply' => 'Thanks. Tell us what you need and we will let you know if we can make it.',
		),
		'problem' => array(
			'label' => 'Something on the site did not work',
			'icon' => 'alert',
			'placeholder' => 'What went wrong, and on which page?',
			'reply' => 'Sorry about that, and thank you for letting us know. We will look into it right away.',
		),
		'other' => array(
			'label' => 'Something else',
			'icon' => 'dots',
			'placeholder' => 'Tell us what stopped you today.',
			'reply' => 'Thank you. Every answer helps us improve the site.',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Front end
 * ------------------------------------------------------------------------ */

/**
 * Pages where the survey would get in the way or makes no sense: after an
 * order, account pages, and paying an invoice.
 */
function wholesale_es_is_blocked_page()
{
	return get_query_var('wholesale_thank_you')
		|| is_page(array('account', 'my-orders', 'track-order', 'login', 'signup', 'pay', 'payment'))
		|| is_page_template(array('page-pay.php', 'payment.php', 'account.php', 'login.php', 'signup.php', 'my_orders.php', 'page-track-order.php'))
		|| is_singular('order')
		|| is_404();
}

function wholesale_es_preview_requested()
{
	return isset($_GET['exit_survey_preview']) && current_user_can('manage_options');
}

function wholesale_es_enqueue()
{
	if (is_admin() || is_customize_preview() || is_preview()) {
		return;
	}

	$preview = wholesale_es_preview_requested();
	$thank_you = (bool) get_query_var('wholesale_thank_you');

	// The thank-you page only loads the script to remember the order, so the
	// survey never asks a customer why they did not buy.
	if (!$preview && !$thank_you) {
		if (!wholesale_es_setting('enabled') || wholesale_es_is_blocked_page()) {
			return;
		}
		if (function_exists('wholesale_vi_is_excluded') && wholesale_vi_is_excluded()) {
			return;
		}
		$session = function_exists('wholesale_vi_current_session') ? wholesale_vi_current_session() : null;
		if ($session && (int) $session->order_id > 0) {
			return;
		}
	}

	$ptype = function_exists('wholesale_vi_page_type') ? wholesale_vi_page_type() : '';
	$cart_items = 0;
	if (in_array($ptype, array('cart', 'checkout'), true) && function_exists('wholesale_get_cart')) {
		$cart_items = count((array) wholesale_get_cart()->get_items());
	}

	$reasons = array();
	foreach (wholesale_es_reasons() as $key => $reason) {
		$reasons[] = array('key' => $key) + $reason;
	}

	$path = '/js/exit-survey.js';
	$file = get_template_directory() . $path;
	wp_enqueue_script('wholesale-exit-survey', get_template_directory_uri() . $path, array(), file_exists($file) ? (string) filemtime($file) : _S_VERSION, array('in_footer' => true, 'strategy' => 'defer'));

	$css = '/css/exit-survey.css';
	// Inline JSON rather than wp_localize_script(), which turns 0 into "0" (truthy in JS).
	$config = array(
		'endpoint' => rest_url('wholesale/v1/exit-survey'),
		'css' => get_template_directory_uri() . $css . '?ver=' . (file_exists(get_template_directory() . $css) ? filemtime(get_template_directory() . $css) : _S_VERSION),
		'ptype' => $ptype,
		'cart' => $cart_items,
		'ordered' => $thank_you ? 1 : 0,
		'preview' => $preview ? 1 : 0,
		'mobile' => (int) wholesale_es_setting('mobile'),
		'minSeconds' => max(0, (int) wholesale_es_setting('min_seconds')),
		'snoozeDays' => max(1, (int) wholesale_es_setting('snooze_days')),
		'reasons' => $reasons,
		'phone' => '866-436-2101',
		'text' => '206-618-6543',
		'privacy' => get_privacy_policy_url(),
	);
	wp_add_inline_script('wholesale-exit-survey', 'window.wholesaleES = ' . wp_json_encode($config) . ';', 'before');
}
add_action('wp_enqueue_scripts', 'wholesale_es_enqueue', 25);

/* ---------------------------------------------------------------------------
 * Collecting answers
 * ------------------------------------------------------------------------ */

function wholesale_es_register_route()
{
	register_rest_route('wholesale/v1', '/exit-survey', array(
		'methods' => 'POST',
		'callback' => 'wholesale_es_rest_collect',
		'permission_callback' => '__return_true',
	));
}
add_action('rest_api_init', 'wholesale_es_register_route');

function wholesale_es_token($id)
{
	return substr(wp_hash('exit-survey|' . (int) $id), 0, 16);
}

/**
 * Allow a handful of requests per visitor per hour so the table can not be
 * flooded.
 */
function wholesale_es_rate_ok($kind, $limit)
{
	$ip = function_exists('wholesale_vi_client_ip') ? wholesale_vi_client_ip(false) : (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '');
	$key = 'wholesale_es_' . $kind . '_' . md5($ip);
	$count = (int) get_transient($key);
	if ($count >= $limit) {
		return false;
	}
	set_transient($key, $count + 1, HOUR_IN_SECONDS);

	return true;
}

function wholesale_es_rest_collect(WP_REST_Request $request)
{
	global $wpdb;

	if (!wholesale_es_setting('enabled') && !current_user_can('manage_options')) {
		return new WP_REST_Response(array('ok' => false), 200);
	}
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
	if (function_exists('wholesale_vi_is_bot') && wholesale_vi_is_bot($ua)) {
		return new WP_REST_Response(array('ok' => false), 200);
	}

	$params = (array) $request->get_json_params();
	$action = isset($params['action']) ? sanitize_key($params['action']) : '';
	$table = wholesale_es_table();
	$now = current_time('mysql', true);

	if ('shown' === $action) {
		if (!wholesale_es_rate_ok('shown', 20)) {
			return new WP_REST_Response(array('ok' => false), 429);
		}

		$session = function_exists('wholesale_vi_current_session') ? wholesale_vi_current_session() : null;
		$device = $session ? (string) $session->device : (!empty($params['mobile']) ? 'mobile' : 'desktop');
		$url = isset($params['url']) ? (string) $params['url'] : '';

		$wpdb->insert($table, array(
			'created_at' => $now,
			'status' => 'shown',
			'trigger_type' => substr(sanitize_key($params['trigger'] ?? ''), 0, 10),
			'page_url' => function_exists('wholesale_vi_clean_url') ? wholesale_vi_clean_url($url) : esc_url_raw(substr($url, 0, 500)),
			'page_type' => substr(sanitize_key($params['ptype'] ?? ''), 0, 20),
			'device' => substr(sanitize_key($device), 0, 10),
			'channel' => $session ? (string) $session->channel : '',
			'cart_items' => min(999, absint($params['cart'] ?? 0)),
			'session_id' => $session ? (int) $session->id : 0,
		));
		$id = (int) $wpdb->insert_id;

		return new WP_REST_Response(array('ok' => (bool) $id, 'id' => $id, 'token' => $id ? wholesale_es_token($id) : ''), 200);
	}

	$id = absint($params['id'] ?? 0);
	$token = isset($params['token']) ? (string) $params['token'] : '';
	if (!$id || !hash_equals(wholesale_es_token($id), $token)) {
		return new WP_REST_Response(array('ok' => false), 400);
	}
	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
	if (!$row || 'answered' === $row->status) {
		return new WP_REST_Response(array('ok' => false), 400);
	}

	if ('dismiss' === $action) {
		$wpdb->update($table, array('status' => 'dismissed'), array('id' => $id));

		return new WP_REST_Response(array('ok' => true), 200);
	}

	if ('answer' !== $action || !wholesale_es_rate_ok('answer', 8)) {
		return new WP_REST_Response(array('ok' => false), 400);
	}
	// Bots fill every field, including the one people never see.
	if (!empty($params['website'])) {
		return new WP_REST_Response(array('ok' => true), 200);
	}

	$reasons = wholesale_es_reasons();
	$reason = isset($params['reason']) ? sanitize_key($params['reason']) : '';
	if (!isset($reasons[$reason])) {
		return new WP_REST_Response(array('ok' => false), 400);
	}
	$details = trim(sanitize_textarea_field((string) ($params['details'] ?? '')));
	$details = function_exists('mb_substr') ? mb_substr($details, 0, 1000) : substr($details, 0, 1000);
	$contact = substr(trim(sanitize_text_field((string) ($params['contact'] ?? ''))), 0, 190);

	$wpdb->update($table, array(
		'status' => 'answered',
		'answered_at' => $now,
		'reason' => $reason,
		'details' => $details,
		'contact' => $contact,
	), array('id' => $id));

	if ((int) $row->session_id && function_exists('wholesale_vi_log_server_event')) {
		$session = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . wholesale_vi_table('sessions') . ' WHERE id = %d', (int) $row->session_id));
		if ($session) {
			wholesale_vi_log_server_event($session, 'exit_survey', $reasons[$reason]['label'], $reason, array());
		}
	}

	if ('' !== $contact && wholesale_es_setting('notify')) {
		wholesale_es_notify($id);
	}

	return new WP_REST_Response(array('ok' => true), 200);
}

/**
 * Email the store when a visitor leaves contact details, so someone can
 * follow up while they are still deciding.
 */
function wholesale_es_notify($id)
{
	global $wpdb;

	$row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . wholesale_es_table() . ' WHERE id = %d', $id));
	$recipients = function_exists('wholesale_contact_admin_recipients') ? wholesale_contact_admin_recipients() : array(get_option('admin_email'));
	if (!$row || !$recipients) {
		return;
	}

	$reasons = wholesale_es_reasons();
	$lines = array(
		'A visitor left the site without ordering and asked for a reply.',
		'',
		'Reason: ' . ($reasons[$row->reason]['label'] ?? $row->reason),
		'Contact: ' . $row->contact,
	);
	if ('' !== (string) $row->details) {
		$lines[] = 'Comment: ' . $row->details;
	}
	$lines[] = 'Page: ' . wholesale_es_site_link($row->page_url);
	if ((int) $row->cart_items) {
		$lines[] = 'Items in cart: ' . (int) $row->cart_items;
	}
	$lines[] = '';
	$lines[] = 'All exit survey answers: ' . admin_url('admin.php?page=wholesale-es');
	if ((int) $row->session_id) {
		$lines[] = 'This visitor\'s journey: ' . admin_url('admin.php?page=wholesale-insights-visitors&session=' . (int) $row->session_id);
	}

	$headers = array();
	if (is_email($row->contact)) {
		$headers[] = 'Reply-To: ' . $row->contact;
	}

	wp_mail($recipients, 'Exit survey: a visitor wants a reply', implode("\n", $lines), $headers);
}

/* ---------------------------------------------------------------------------
 * Admin report
 * ------------------------------------------------------------------------ */

function wholesale_es_admin_menu()
{
	add_submenu_page('wholesale-insights', 'Exit Survey', 'Exit Survey', 'manage_options', 'wholesale-es', 'wholesale_es_admin_page');
}
add_action('admin_menu', 'wholesale_es_admin_menu', 20);

/**
 * The page lives at its own slug ("wholesale-es") so the Visitor Insights
 * live-refresh script, which loads on every "wholesale-insights" page, leaves
 * it alone. Only the shared stylesheet is reused.
 */
function wholesale_es_admin_assets($hook)
{
	if (false === strpos($hook, 'wholesale-es')) {
		return;
	}

	$dir = get_template_directory();
	wp_enqueue_style('wholesale-vi-admin', get_template_directory_uri() . '/css/visitor-insights.css', array(), (string) filemtime($dir . '/css/visitor-insights.css'));
}
add_action('admin_enqueue_scripts', 'wholesale_es_admin_assets');

function wholesale_es_period()
{
	$days = isset($_GET['days']) ? absint($_GET['days']) : 30;
	if (!in_array($days, array(7, 30, 90, 365), true)) {
		$days = 30;
	}

	return $days;
}

function wholesale_es_admin_page()
{
	global $wpdb;

	if (!current_user_can('manage_options')) {
		return;
	}

	$table = wholesale_es_table();
	$days = wholesale_es_period();
	$since = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
	$reasons = wholesale_es_reasons();
	$reason_filter = isset($_GET['reason']) ? sanitize_key(wp_unslash($_GET['reason'])) : '';
	if (!isset($reasons[$reason_filter])) {
		$reason_filter = '';
	}

	$totals = $wpdb->get_row($wpdb->prepare(
		"SELECT COUNT(*) AS shown,
			SUM(status = 'answered') AS answered,
			SUM(status = 'dismissed') AS dismissed,
			SUM(status = 'answered' AND contact <> '') AS contacts
		FROM {$table} WHERE created_at >= %s",
		$since
	));
	$by_reason = $wpdb->get_results($wpdb->prepare(
		"SELECT reason, COUNT(*) AS total,
			SUM(device = 'mobile') AS mobile,
			SUM(cart_items > 0) AS with_cart
		FROM {$table} WHERE status = 'answered' AND created_at >= %s
		GROUP BY reason ORDER BY total DESC",
		$since
	), OBJECT_K);
	$by_page = $wpdb->get_results($wpdb->prepare(
		"SELECT page_type, COUNT(*) AS shown, SUM(status = 'answered') AS answered
		FROM {$table} WHERE created_at >= %s
		GROUP BY page_type ORDER BY shown DESC LIMIT 8",
		$since
	));

	$where = $wpdb->prepare("status = 'answered' AND created_at >= %s", $since);
	if ($reason_filter) {
		$where .= $wpdb->prepare(' AND reason = %s', $reason_filter);
	}
	$recent = $wpdb->get_results("SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT 100");

	$shown = (int) $totals->shown;
	$answered = (int) $totals->answered;
	$rate = $shown ? round($answered / $shown * 100) : 0;
	$top = $by_reason ? reset($by_reason) : null;
	$base = admin_url('admin.php?page=wholesale-es');
	$settings = wholesale_es_settings();
	$notice = isset($_GET['es_notice']) ? sanitize_key(wp_unslash($_GET['es_notice'])) : '';
	?>
	<div class="wrap wvi">
		<header class="wvi-header">
			<div>
				<h1 class="wvi-title"><span class="dashicons dashicons-feedback"></span> Exit Survey</h1>
				<p class="wvi-subtitle">Why visitors leave without ordering, in their own words.</p>
			</div>
			<span class="wvi-status<?php echo $settings['enabled'] ? '' : ' is-off'; ?>">
				<span class="wvi-dot"></span>
				<?php echo $settings['enabled'] ? 'Survey is live' : 'Survey is paused'; ?>
				<span class="wvi-status-sep"></span>
				<a href="<?php echo esc_url(add_query_arg('exit_survey_preview', '1', home_url('/'))); ?>" target="_blank" rel="noopener">Preview on the site</a>
			</span>
		</header>

		<?php if ('saved' === $notice) : ?>
			<div class="wvi-alert is-success">Settings saved.</div>
		<?php endif; ?>

		<nav class="wvi-tabs">
			<?php foreach (array(7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months') as $value => $label) : ?>
				<a href="<?php echo esc_url(add_query_arg('days', $value, $base)); ?>" class="<?php echo $value === $days ? 'is-active' : ''; ?>"><?php echo esc_html($label); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="wvi-stats">
			<?php
			wholesale_es_stat('Times shown', number_format_i18n($shown), 'visibility', 'blue');
			wholesale_es_stat('Answers', number_format_i18n($answered), 'yes-alt', 'green');
			wholesale_es_stat('Answer rate', $rate . '%', 'chart-pie', 'purple');
			wholesale_es_stat('Asked for a reply', number_format_i18n((int) $totals->contacts), 'email-alt', 'amber');
			?>
		</div>

		<div class="wvi-grid-session wvi-grid">
			<section class="wvi-card wvi-section">
				<div class="wvi-section-head">
					<h2><span class="dashicons dashicons-chart-bar"></span> Why visitors left</h2>
					<?php if ($top && $answered) : ?>
						<span class="wvi-subtitle">Top reason: <strong><?php echo esc_html($reasons[$top->reason]['label'] ?? $top->reason); ?></strong></span>
					<?php endif; ?>
				</div>
				<?php if (!$answered) : ?>
					<p class="wvi-empty">No answers yet for this period. Answers appear here as soon as visitors respond.</p>
				<?php else : ?>
					<div class="wes-bars">
						<?php foreach ($reasons as $key => $reason) :
							$row = $by_reason[$key] ?? null;
							$count = $row ? (int) $row->total : 0;
							$pct = round($count / $answered * 100);
							?>
							<a class="wes-bar<?php echo $reason_filter === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(array('days' => $days, 'reason' => $reason_filter === $key ? false : $key), $base)); ?>#wes-answers">
								<span class="wes-bar-label"><?php echo esc_html($reason['label']); ?></span>
								<span class="wes-bar-track"><span style="width: <?php echo (int) $pct; ?>%"></span></span>
								<span class="wes-bar-num"><?php echo (int) $count; ?> <small><?php echo (int) $pct; ?>%</small></span>
								<?php if ($row && (int) $row->with_cart) : ?>
									<span class="wes-bar-note"><?php echo (int) $row->with_cart; ?> had items in their cart</span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<section class="wvi-card wvi-section">
				<div class="wvi-section-head">
					<h2><span class="dashicons dashicons-admin-page"></span> Where they were</h2>
				</div>
				<?php if (!$by_page) : ?>
					<p class="wvi-empty">The survey has not been shown yet.</p>
				<?php else : ?>
					<div class="wvi-table-wrap">
						<table class="wvi-table">
							<thead><tr><th>Page type</th><th class="num">Shown</th><th class="num">Answered</th></tr></thead>
							<tbody>
								<?php foreach ($by_page as $row) : ?>
									<tr>
										<td><?php echo esc_html(ucfirst($row->page_type ?: 'page')); ?></td>
										<td class="num"><?php echo (int) $row->shown; ?></td>
										<td class="num"><?php echo (int) $row->answered; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</section>
		</div>

		<section class="wvi-card wvi-section" id="wes-answers">
			<div class="wvi-section-head">
				<h2><span class="dashicons dashicons-format-chat"></span> Answers<?php echo $reason_filter ? ': ' . esc_html($reasons[$reason_filter]['label']) : ''; ?></h2>
				<?php if ($reason_filter) : ?>
					<a href="<?php echo esc_url(add_query_arg('days', $days, $base)); ?>#wes-answers">Show all reasons</a>
				<?php endif; ?>
			</div>
			<?php if (!$recent) : ?>
				<p class="wvi-empty">No answers to show.</p>
			<?php else : ?>
				<div class="wvi-table-wrap">
					<table class="wvi-table wes-table">
						<thead><tr><th>When</th><th>Reason</th><th>Comment</th><th>Contact</th><th>Page</th><th>Visit</th></tr></thead>
						<tbody>
							<?php foreach ($recent as $row) : ?>
								<tr>
									<td class="wes-nowrap"><?php echo esc_html(get_date_from_gmt($row->answered_at ?: $row->created_at, 'M j, g:i a')); ?><br><small class="wes-muted"><?php echo esc_html(ucfirst($row->device)); ?><?php echo $row->channel ? ' · ' . esc_html(ucfirst($row->channel)) : ''; ?></small></td>
									<td><?php echo esc_html($reasons[$row->reason]['label'] ?? $row->reason); ?><?php echo (int) $row->cart_items ? '<br><small class="wes-muted">' . (int) $row->cart_items . ' in cart</small>' : ''; ?></td>
									<td class="wes-comment"><?php echo '' !== (string) $row->details ? nl2br(esc_html($row->details)) : '<span class="wes-muted">—</span>'; ?></td>
									<td><?php
										if (is_email($row->contact)) {
											echo '<a href="mailto:' . esc_attr($row->contact) . '">' . esc_html($row->contact) . '</a>';
										} elseif ('' !== $row->contact) {
											echo '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $row->contact)) . '">' . esc_html($row->contact) . '</a>';
										} else {
											echo '<span class="wes-muted">—</span>';
										}
									?></td>
									<td><a href="<?php echo esc_url(wholesale_es_site_link($row->page_url)); ?>" target="_blank" rel="noopener"><?php echo esc_html(wp_html_excerpt($row->page_url ?: '/', 40, '…')); ?></a></td>
									<td><?php echo (int) $row->session_id ? '<a href="' . esc_url(admin_url('admin.php?page=wholesale-insights-visitors&session=' . (int) $row->session_id)) . '">Journey</a>' : '<span class="wes-muted">—</span>'; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>

		<section class="wvi-card wvi-section">
			<div class="wvi-section-head"><h2><span class="dashicons dashicons-admin-generic"></span> Settings</h2></div>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wes-settings">
				<input type="hidden" name="action" value="wholesale_es_save">
				<?php wp_nonce_field('wholesale_es_save'); ?>
				<label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> Show the exit survey on the site</label>
				<label><input type="checkbox" name="mobile" value="1" <?php checked($settings['mobile']); ?>> Also show it on phones and tablets (when a visitor scrolls back up quickly)</label>
				<label><input type="checkbox" name="notify" value="1" <?php checked($settings['notify']); ?>> Email the store notification inbox when a visitor leaves contact details</label>
				<label>Wait at least <input type="number" name="min_seconds" min="0" max="120" value="<?php echo (int) $settings['min_seconds']; ?>" class="small-text"> seconds on a page before the survey can appear</label>
				<label>After a visitor closes it, wait <input type="number" name="snooze_days" min="1" max="365" value="<?php echo (int) $settings['snooze_days']; ?>" class="small-text"> days before asking again (visitors who answer are not asked again for 90 days)</label>
				<p class="wes-muted">Never shown on the thank-you, account, order or payment pages, to visitors who have ordered, to staff browsing the site, or more than once per visit.</p>
				<?php submit_button('Save settings', 'primary', 'submit', false); ?>
			</form>
		</section>
	</div>
	<style>
		.wes-bars { display: grid; gap: 4px; }
		.wes-bar { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) 70px; align-items: center; gap: 4px 14px; padding: 8px 10px; border-radius: 8px; text-decoration: none; color: var(--wvi-text); }
		.wes-bar:hover, .wes-bar.is-active { background: var(--wvi-soft); }
		.wes-bar:focus { box-shadow: none; outline: 2px solid var(--wvi-blue); }
		.wes-bar-track { height: 10px; border-radius: 99px; background: #eef1f4; overflow: hidden; }
		.wes-bar-track span { display: block; height: 100%; border-radius: inherit; background: var(--wvi-blue); min-width: 2px; }
		.wes-bar-num { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
		.wes-bar-num small { color: var(--wvi-muted); font-weight: 400; margin-left: 4px; }
		.wes-bar-note { grid-column: 1 / -1; font-size: 12px; color: var(--wvi-amber); margin-top: -2px; }
		.wes-muted { color: var(--wvi-muted); }
		.wes-nowrap { white-space: nowrap; }
		.wes-comment { max-width: 420px; }
		.wes-settings { display: grid; gap: 12px; max-width: 760px; }
		.wes-settings .small-text { width: 64px; margin: 0 4px; }
		@media (max-width: 782px) { .wes-bar { grid-template-columns: minmax(0, 1fr) 70px; } .wes-bar-track { grid-column: 1 / -1; grid-row: 2; } }
	</style>
	<?php
}

/**
 * Page paths are stored as the browser saw them, so join them to the site's
 * origin rather than home_url(), which may already include a subfolder.
 */
function wholesale_es_site_link($path)
{
	return preg_replace('#^(https?://[^/]+).*$#', '$1', home_url()) . '/' . ltrim((string) $path, '/');
}

function wholesale_es_stat($label, $value, $icon, $color)
{
	?>
	<div class="wvi-stat">
		<span class="wvi-stat-icon is-<?php echo esc_attr($color); ?>"><span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span></span>
		<span><span class="wvi-stat-value"><?php echo esc_html($value); ?></span><span class="wvi-stat-label"><?php echo esc_html($label); ?></span></span>
	</div>
	<?php
}

function wholesale_es_save_settings()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.');
	}
	check_admin_referer('wholesale_es_save');

	update_option('wholesale_es_settings', array(
		'enabled' => empty($_POST['enabled']) ? 0 : 1,
		'mobile' => empty($_POST['mobile']) ? 0 : 1,
		'notify' => empty($_POST['notify']) ? 0 : 1,
		'min_seconds' => min(120, absint($_POST['min_seconds'] ?? 8)),
		'snooze_days' => max(1, min(365, absint($_POST['snooze_days'] ?? 7))),
	));

	wp_safe_redirect(admin_url('admin.php?page=wholesale-es&es_notice=saved'));
	exit;
}
add_action('admin_post_wholesale_es_save', 'wholesale_es_save_settings');
