<?php
/**
 * Emails admin menu: a log of every email the site sends (with its delivery
 * result), and the SMTP settings used to send them.
 *
 * SMTP settings are saved in the `wholesale_mail_settings` option; until they are
 * saved once, the WHOLESALE_SMTP_* constants in wp-config.php are used.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

define('WHOLESALE_EMAIL_LOG_DB_VERSION', '2');

// A failed email is tried again after each of these delays, then given up on.
define('WHOLESALE_EMAIL_RETRY_DELAYS', array(5 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS, 2 * HOUR_IN_SECONDS));

/* ---------------------------------------------------------------------------
 * SMTP settings
 * ------------------------------------------------------------------------ */

function wholesale_smtp_providers()
{
	return array(
		'gmail' => array('label' => 'Gmail / Google Workspace', 'host' => 'smtp.gmail.com', 'port' => 465, 'encryption' => 'ssl', 'hint' => 'Use a Google app password, not the account password (Google Account → Security → App passwords).'),
		'outlook' => array('label' => 'Microsoft 365 / Outlook', 'host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'SMTP AUTH must be enabled for the mailbox in the Microsoft 365 admin center.'),
		'sendgrid' => array('label' => 'SendGrid', 'host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Username is literally "apikey"; the password is your SendGrid API key.'),
		'mailgun' => array('label' => 'Mailgun', 'host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Use the SMTP credentials from your Mailgun domain settings.'),
		'ses' => array('label' => 'Amazon SES', 'host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Change the region in the host to match your SES region.'),
		'brevo' => array('label' => 'Brevo', 'host' => 'smtp-relay.brevo.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Use the SMTP key from Brevo → SMTP & API.'),
		'zoho' => array('label' => 'Zoho Mail', 'host' => 'smtp.zoho.com', 'port' => 465, 'encryption' => 'ssl', 'hint' => 'Use an application-specific password if 2FA is on.'),
		'custom' => array('label' => 'Other SMTP server', 'host' => '', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Get the host, port and encryption from your email provider.'),
	);
}

function wholesale_mail_settings_defaults()
{
	return array(
		'enabled' => true,
		'provider' => 'gmail',
		'host' => '',
		'port' => 465,
		'encryption' => 'ssl',
		'auth' => true,
		'username' => '',
		'password' => '',
		'from_email' => '',
		'from_name' => '',
		'log_enabled' => true,
		'log_retention' => 90,
		'auto_retry' => true,
		// Where store notifications (new orders, quotes, contact forms, reviews) go.
		'notify_recipients' => '',
	);
}

/**
 * The key used to encrypt the saved SMTP password, derived from the site's
 * security keys so a database dump alone does not reveal it.
 */
function wholesale_smtp_crypto_key()
{
	return hash('sha256', wp_salt('secure_auth') . '|wholesale-smtp', true);
}

function wholesale_smtp_encrypt($plain)
{
	if ('' === (string) $plain) {
		return '';
	}
	if (!function_exists('openssl_encrypt')) {
		return 'plain:' . base64_encode($plain);
	}
	$iv = random_bytes(12);
	$tag = '';
	$cipher = openssl_encrypt($plain, 'aes-256-gcm', wholesale_smtp_crypto_key(), OPENSSL_RAW_DATA, $iv, $tag);

	return 'v1:' . base64_encode($iv . $tag . $cipher);
}

/**
 * @return string|false The password, or false when it can no longer be decrypted
 *                      (for example after the wp-config.php security keys changed).
 */
function wholesale_smtp_decrypt($stored)
{
	$stored = (string) $stored;
	if ('' === $stored) {
		return '';
	}
	if (0 === strpos($stored, 'plain:')) {
		return (string) base64_decode(substr($stored, 6));
	}
	if (0 !== strpos($stored, 'v1:') || !function_exists('openssl_decrypt')) {
		return false;
	}
	$raw = base64_decode(substr($stored, 3));
	if (false === $raw || strlen($raw) < 29) {
		return false;
	}

	return openssl_decrypt(substr($raw, 28), 'aes-256-gcm', wholesale_smtp_crypto_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
}

/**
 * The settings in effect: saved from the Emails → SMTP Settings page, or the
 * wp-config.php constants when nothing has been saved yet.
 *
 * `password` is decrypted; `password_unreadable` is true when a saved one could not be.
 */
function wholesale_mail_settings()
{
	static $settings = null;
	if (null !== $settings) {
		return $settings;
	}

	$defaults = wholesale_mail_settings_defaults();
	$saved = get_option('wholesale_mail_settings', null);

	if (is_array($saved)) {
		$settings = array_merge($defaults, $saved);
		$password = wholesale_smtp_decrypt($settings['password']);
		$settings['password_unreadable'] = false === $password;
		$settings['password'] = false === $password ? '' : $password;
		$settings['source'] = 'admin';
		return $settings;
	}

	$settings = array_merge($defaults, array(
		'enabled' => defined('WHOLESALE_SMTP_HOST') && WHOLESALE_SMTP_HOST,
		'provider' => defined('WHOLESALE_SMTP_HOST') && 'smtp.gmail.com' === WHOLESALE_SMTP_HOST ? 'gmail' : 'custom',
		'host' => defined('WHOLESALE_SMTP_HOST') ? (string) WHOLESALE_SMTP_HOST : '',
		'port' => defined('WHOLESALE_SMTP_PORT') ? (int) WHOLESALE_SMTP_PORT : 465,
		'encryption' => defined('WHOLESALE_SMTP_SECURE') && WHOLESALE_SMTP_SECURE ? (string) WHOLESALE_SMTP_SECURE : 'ssl',
		'username' => defined('WHOLESALE_SMTP_USERNAME') ? (string) WHOLESALE_SMTP_USERNAME : '',
		'password' => defined('WHOLESALE_SMTP_PASSWORD') && 'your-google-workspace-app-password' !== WHOLESALE_SMTP_PASSWORD ? (string) WHOLESALE_SMTP_PASSWORD : '',
		'from_email' => defined('WHOLESALE_SMTP_FROM') ? (string) WHOLESALE_SMTP_FROM : '',
	));
	$settings['password_unreadable'] = false;
	$settings['source'] = 'wp-config';

	return $settings;
}

function wholesale_mail_from_email()
{
	$settings = wholesale_mail_settings();

	return is_email($settings['from_email']) ? $settings['from_email'] : get_option('admin_email');
}

function wholesale_mail_from_name()
{
	$settings = wholesale_mail_settings();

	return '' !== trim($settings['from_name']) ? $settings['from_name'] : get_bloginfo('name');
}

/**
 * How this site sends email, for the settings page and failure messages.
 *
 * @return array mode ('smtp'; 'default' when the admin chose the default WordPress
 *               mailer; 'php_mail' when SMTP is chosen but incomplete), host, username,
 *               from, source, and a problem string explaining a 'php_mail' fallback.
 */
function wholesale_mail_status()
{
	$settings = wholesale_mail_settings();
	$problem = '';

	if (!$settings['enabled']) {
		return array(
			'mode' => 'default',
			'host' => '',
			'username' => '',
			'from' => wholesale_mail_from_email(),
			'source' => $settings['source'],
			'problem' => '',
		);
	}

	if ('' === $settings['host']) {
		$problem = 'No SMTP server is set in Emails → SMTP Settings.';
	} elseif ($settings['auth'] && $settings['password_unreadable']) {
		$problem = 'The saved SMTP password can no longer be read (the site security keys changed). Enter it again in Emails → SMTP Settings.';
	} elseif ($settings['auth'] && ('' === $settings['username'] || '' === $settings['password'])) {
		$problem = 'The SMTP username or password is missing in Emails → SMTP Settings.';
	}

	return array(
		'mode' => $problem ? 'php_mail' : 'smtp',
		'host' => $settings['host'],
		'username' => $settings['username'],
		'from' => wholesale_mail_from_email(),
		'source' => $settings['source'],
		'problem' => $problem,
	);
}

/**
 * Point a PHPMailer instance at the configured SMTP server.
 */
function wholesale_apply_smtp($phpmailer, $settings)
{
	$phpmailer->isSMTP();
	$phpmailer->Host = $settings['host'];
	$phpmailer->Port = (int) $settings['port'];
	$phpmailer->SMTPSecure = in_array($settings['encryption'], array('ssl', 'tls'), true) ? $settings['encryption'] : '';
	$phpmailer->SMTPAutoTLS = false;
	$phpmailer->SMTPAuth = (bool) $settings['auth'];
	if ($settings['auth']) {
		$phpmailer->Username = $settings['username'];
		$phpmailer->Password = $settings['password'];
	}
}

function wholesale_configure_smtp_mailer($phpmailer)
{
	if ('smtp' !== wholesale_mail_status()['mode']) {
		return;
	}

	wholesale_apply_smtp($phpmailer, wholesale_mail_settings());
	$phpmailer->From = wholesale_mail_from_email();
	$phpmailer->FromName = wholesale_mail_from_name();
	$phpmailer->Sender = $phpmailer->From;
}
add_action('phpmailer_init', 'wholesale_configure_smtp_mailer');

add_filter('wp_mail_from', function ($from_email) {
	$settings = wholesale_mail_settings();

	return is_email($settings['from_email']) ? $settings['from_email'] : $from_email;
});

add_filter('wp_mail_from_name', function () {
	return wholesale_mail_from_name();
});

/* ---------------------------------------------------------------------------
 * Log storage
 * ------------------------------------------------------------------------ */

function wholesale_email_log_table()
{
	global $wpdb;

	return $wpdb->prefix . 'wholesale_email_log';
}

function wholesale_email_log_install()
{
	if (WHOLESALE_EMAIL_LOG_DB_VERSION === get_option('wholesale_email_log_db_version')) {
		return;
	}

	global $wpdb;
	$table = wholesale_email_log_table();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta("CREATE TABLE {$table} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		created_at DATETIME NOT NULL,
		to_email TEXT NOT NULL,
		subject VARCHAR(255) NOT NULL DEFAULT '',
		message LONGTEXT NULL,
		headers TEXT NULL,
		attachments TEXT NULL,
		content_type VARCHAR(50) NOT NULL DEFAULT '',
		from_email VARCHAR(190) NOT NULL DEFAULT '',
		mailer VARCHAR(190) NOT NULL DEFAULT '',
		source VARCHAR(190) NOT NULL DEFAULT '',
		status VARCHAR(20) NOT NULL DEFAULT 'pending',
		error TEXT NULL,
		attempts SMALLINT UNSIGNED NOT NULL DEFAULT 1,
		next_retry DATETIME NULL,
		PRIMARY KEY  (id),
		KEY status_created (status, created_at),
		KEY created_at (created_at),
		KEY next_retry (next_retry)
	) {$charset_collate};");

	update_option('wholesale_email_log_db_version', WHOLESALE_EMAIL_LOG_DB_VERSION);
}
add_action('init', 'wholesale_email_log_install');

/**
 * The theme file and function that called wp_mail(), so the log says which
 * feature sent each email.
 */
function wholesale_email_log_source()
{
	$skip = array('wp_mail', 'wholesale_send_html_mail');
	$frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
	$file = '';
	$line = 0;
	$caller = '';
	foreach ($frames as $i => $frame) {
		if (!isset($frame['function']) || !in_array($frame['function'], $skip, true)) {
			continue;
		}
		$file = isset($frame['file']) ? $frame['file'] : '';
		$line = isset($frame['line']) ? (int) $frame['line'] : 0;
		$caller = isset($frames[$i + 1]['function']) ? $frames[$i + 1]['function'] : '';
	}
	if ('' === $file) {
		return '';
	}

	$label = basename($file) . ':' . $line;
	if ('' !== $caller &&!in_array($caller, array('include', 'require', 'include_once', 'require_once', '{closure}', 'do_action', 'apply_filters'), true)) {
		$label .= ' · ' . $caller . '()';
	}

	return substr($label, 0, 190);
}

/**
 * Record each email before it is sent; the result is filled in by the hooks below.
 */
function wholesale_email_log_start($atts)
{
	global $wpdb;
	$GLOBALS['wholesale_email_log_current'] = 0;

	// An automatic retry updates its original row instead of adding a new one.
	if (!empty($GLOBALS['wholesale_email_log_retry_id'])) {
		$id = (int) $GLOBALS['wholesale_email_log_retry_id'];
		$GLOBALS['wholesale_email_log_retry_id'] = 0;
		$table = wholesale_email_log_table();
		$wpdb->query($wpdb->prepare("UPDATE {$table} SET status = 'pending', attempts = attempts + 1, next_retry = NULL WHERE id = %d", $id));
		$GLOBALS['wholesale_email_log_current'] = $id;
		return $atts;
	}

	if (!wholesale_mail_settings()['log_enabled']) {
		return $atts;
	}

	$to = isset($atts['to']) ? $atts['to'] : '';
	$headers = isset($atts['headers']) ? $atts['headers'] : '';
	$attachments = isset($atts['attachments']) ? (array) $atts['attachments'] : array();
	$message = isset($atts['message']) ? (string) $atts['message'] : '';

	$inserted = $wpdb->insert(wholesale_email_log_table(), array(
		'created_at' => current_time('mysql', true),
		'to_email' => is_array($to) ? implode(', ', $to) : (string) $to,
		'subject' => mb_substr(isset($atts['subject']) ? (string) $atts['subject'] : '', 0, 255),
		// Cap huge bodies so the log cannot bloat the database.
		'message' => strlen($message) > 512000 ? substr($message, 0, 512000) : $message,
		'headers' => is_array($headers) ? implode("\n", $headers) : (string) $headers,
		// Full paths, so retries can attach the files again.
		'attachments' => implode("\n", array_filter(array_map('strval', $attachments))),
		'source' => wholesale_email_log_source(),
		'status' => 'pending',
	));
	if ($inserted) {
		$GLOBALS['wholesale_email_log_current'] = (int) $wpdb->insert_id;
	}

	return $atts;
}
add_filter('wp_mail', 'wholesale_email_log_start', 9999);

function wholesale_email_log_update($data)
{
	$id = isset($GLOBALS['wholesale_email_log_current']) ? (int) $GLOBALS['wholesale_email_log_current'] : 0;
	if ($id) {
		global $wpdb;
		$wpdb->update(wholesale_email_log_table(), $data, array('id' => $id));
	}
}

// Last on phpmailer_init, so it sees the final sender and transport.
add_action('phpmailer_init', function ($phpmailer) {
	wholesale_email_log_update(array(
		'content_type' => substr((string) $phpmailer->ContentType, 0, 50),
		'from_email' => substr((string) $phpmailer->From, 0, 190),
		'mailer' => 'smtp' === $phpmailer->Mailer ? substr('SMTP · ' . $phpmailer->Host, 0, 190) : 'PHP mail()',
	));
}, 9999);

add_action('wp_mail_succeeded', function () {
	wholesale_email_log_update(array('status' => 'sent', 'error' => null, 'next_retry' => null));
	$GLOBALS['wholesale_email_log_current'] = 0;
});

add_action('wp_mail_failed', function ($error) {
	$id = isset($GLOBALS['wholesale_email_log_current']) ? (int) $GLOBALS['wholesale_email_log_current'] : 0;
	wholesale_email_log_update(array('status' => 'failed', 'error' => $error->get_error_message()));
	$GLOBALS['wholesale_email_log_current'] = 0;
	if ($id) {
		wholesale_email_schedule_retry($id);
	}
});

/**
 * Queue a failed email to be tried again, unless retries are off, it has used
 * them all, or it has no valid recipient (trying again cannot fix that).
 */
function wholesale_email_schedule_retry($id)
{
	if (!wholesale_mail_settings()['auto_retry']) {
		return;
	}
	$row = wholesale_email_log_get($id);
	$delays = WHOLESALE_EMAIL_RETRY_DELAYS;
	if (!$row || (int) $row['attempts'] > count($delays)) {
		return;
	}
	$valid = array_filter(array_map('trim', explode(',', $row['to_email'])), static function ($address) {
		return is_email(preg_match('/<([^>]+)>/', $address, $m) ? $m[1] : $address);
	});
	if (!$valid) {
		return;
	}

	global $wpdb;
	$wpdb->update(wholesale_email_log_table(), array('next_retry' => gmdate('Y-m-d H:i:s', time() + $delays[(int) $row['attempts'] - 1])), array('id' => $id));
}

/**
 * Send a logged email again. With $in_place, the attempt updates the same log
 * row (automatic retries); otherwise it is logged as a new email.
 */
function wholesale_email_log_send_row($row, $in_place)
{
	$headers = array_filter(array_map('trim', explode("\n", (string) $row['headers'])));
	$has_type = (bool) preg_grep('/^content-type:/i', $headers);
	if (!$has_type && wholesale_email_log_is_html($row)) {
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
	}

	// Only re-attach files that still exist in the uploads folder.
	$uploads = wp_normalize_path(trailingslashit(wp_upload_dir()['basedir']));
	$attachments = array_filter(explode("\n", (string) $row['attachments']), static function ($path) use ($uploads) {
		$real = '' !== trim($path) ? realpath($path) : false;
		return $real && 0 === strpos(wp_normalize_path($real), $uploads) && is_file($real);
	});

	$GLOBALS['wholesale_email_log_retry_id'] = $in_place ? (int) $row['id'] : 0;
	$sent = wp_mail($row['to_email'], $row['subject'], $row['message'], $headers, array_values($attachments));
	$GLOBALS['wholesale_email_log_retry_id'] = 0;

	return $sent;
}

/**
 * Try again every failed email whose retry time has come.
 */
function wholesale_email_process_retries()
{
	global $wpdb;
	$table = wholesale_email_log_table();
	$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE status = 'failed' AND next_retry IS NOT NULL AND next_retry <= %s ORDER BY next_retry ASC LIMIT 20", current_time('mysql', true)));

	foreach ($ids as $id) {
		// Claim the row first so an overlapping cron run cannot send it twice.
		$claimed = $wpdb->query($wpdb->prepare("UPDATE {$table} SET next_retry = NULL WHERE id = %d AND next_retry IS NOT NULL", $id));
		$row = $claimed ? wholesale_email_log_get($id) : null;
		if ($row) {
			wholesale_email_log_send_row($row, true);
		}
	}
}
add_action('wholesale_email_process_retries', 'wholesale_email_process_retries');

add_filter('cron_schedules', function ($schedules) {
	$schedules['wholesale_five_minutes'] = array('interval' => 5 * MINUTE_IN_SECONDS, 'display' => 'Every five minutes');
	return $schedules;
});

function wholesale_email_log_prune()
{
	$days = (int) wholesale_mail_settings()['log_retention'];
	if ($days < 1) {
		return;
	}

	global $wpdb;
	$table = wholesale_email_log_table();
	$wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS)));
}
add_action('wholesale_email_log_prune', 'wholesale_email_log_prune');

add_action('init', function () {
	if (!wp_next_scheduled('wholesale_email_log_prune')) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wholesale_email_log_prune');
	}
	if (!wp_next_scheduled('wholesale_email_process_retries')) {
		wp_schedule_event(time() + MINUTE_IN_SECONDS, 'wholesale_five_minutes', 'wholesale_email_process_retries');
	}
});

function wholesale_email_log_get($id)
{
	global $wpdb;
	$table = wholesale_email_log_table();

	return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
}

function wholesale_email_log_is_html($row)
{
	return false !== stripos((string) $row['content_type'], 'html')
		|| preg_match('/content-type:\s*text\/html/i', (string) $row['headers'])
		|| preg_match('/<(html|body|p|div|table|br)\b/i', (string) $row['message']);
}

/**
 * Manual "Resend": logged as a new email, and any automatic retry of the original
 * is cancelled so the recipient does not get it twice.
 */
function wholesale_email_log_resend($id)
{
	$row = wholesale_email_log_get($id);
	if (!$row) {
		return false;
	}

	global $wpdb;
	$wpdb->update(wholesale_email_log_table(), array('next_retry' => null), array('id' => (int) $id));

	return wholesale_email_log_send_row($row, false);
}

/* ---------------------------------------------------------------------------
 * Admin menu
 * ------------------------------------------------------------------------ */

function wholesale_email_admin_url($tab = 'log', $args = array())
{
	return add_query_arg($args, admin_url('admin.php?page=' . ('smtp' === $tab ? 'wholesale-emails-smtp' : 'wholesale-emails')));
}

function wholesale_email_failed_recent_count()
{
	global $wpdb;
	$table = wholesale_email_log_table();

	return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = 'failed' AND created_at >= %s", gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)));
}

function wholesale_email_add_admin_menu()
{
	$failed = wholesale_email_failed_recent_count();
	$badge = $failed ? ' <span class="awaiting-mod count-' . $failed . '"><span class="pending-count">' . number_format_i18n($failed) . '</span></span>' : '';

	add_menu_page('Email Log', 'Emails' . $badge, 'manage_options', 'wholesale-emails', 'wholesale_email_admin_page', 'dashicons-email', 27);
	add_submenu_page('wholesale-emails', 'Email Log', 'Email Log', 'manage_options', 'wholesale-emails', 'wholesale_email_admin_page');
	add_submenu_page('wholesale-emails', 'SMTP Settings', 'SMTP Settings', 'manage_options', 'wholesale-emails-smtp', 'wholesale_email_admin_page');
}
add_action('admin_menu', 'wholesale_email_add_admin_menu');

/* ---------------------------------------------------------------------------
 * Admin actions
 * ------------------------------------------------------------------------ */

function wholesale_email_require_admin($nonce_action)
{
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You are not allowed to do this.', 'litsign'), '', array('response' => 403));
	}
	check_admin_referer($nonce_action);
}

function wholesale_email_redirect($tab, $notice, $type = 'success', $extra = array())
{
	set_transient('wholesale_email_notice_' . get_current_user_id(), array('type' => $type, 'message' => $notice), MINUTE_IN_SECONDS);
	$back = wp_get_referer();
	$url = $back && false !== strpos($back, 'page=wholesale-emails') ? remove_query_arg(array('_wpnonce'), $back) : wholesale_email_admin_url($tab);
	wp_safe_redirect(add_query_arg($extra, $url));
	exit;
}

function wholesale_handle_save_smtp_settings()
{
	wholesale_email_require_admin('wholesale_save_smtp');

	$post = wp_unslash($_POST);
	$current = wholesale_mail_settings();
	$providers = wholesale_smtp_providers();

	$host = trim(sanitize_text_field(isset($post['host']) ? $post['host'] : ''));
	$host = preg_replace('#^[a-z]+://#i', '', $host);
	$port = isset($post['port']) ? absint($post['port']) : 0;
	$encryption = isset($post['encryption']) && in_array($post['encryption'], array('none', 'ssl', 'tls'), true) ? $post['encryption'] : 'tls';

	$password = isset($post['password']) ? (string) $post['password'] : '';
	// Blank means "keep the saved one" (it is never printed back into the form).
	if ('' === $password) {
		$password = $current['password'];
	}

	$from_email = sanitize_email(isset($post['from_email']) ? $post['from_email'] : '');
	$recipient_input = preg_split('/[\s,;]+/', isset($post['notify_recipients']) ? (string) $post['notify_recipients'] : '', -1, PREG_SPLIT_NO_EMPTY);
	$recipients = array_values(array_unique(array_filter(array_map('sanitize_email', $recipient_input), 'is_email')));

	$settings = array(
		'enabled' => isset($post['mailer']) && 'smtp' === $post['mailer'],
		'provider' => isset($post['provider'], $providers[$post['provider']]) ? $post['provider'] : 'custom',
		'host' => $host,
		'port' => $port >= 1 && $port <= 65535 ? $port : ('ssl' === $encryption ? 465 : 587),
		'encryption' => $encryption,
		'auth' => !empty($post['auth']),
		'username' => trim(sanitize_text_field(isset($post['username']) ? $post['username'] : '')),
		'password' => wholesale_smtp_encrypt($password),
		'from_email' => $from_email,
		'from_name' => sanitize_text_field(isset($post['from_name']) ? $post['from_name'] : ''),
		'log_enabled' => !empty($post['log_enabled']),
		'log_retention' => min(3650, isset($post['log_retention']) ? absint($post['log_retention']) : 90),
		'auto_retry' => !empty($post['auto_retry']),
		'notify_recipients' => implode(', ', $recipients),
	);

	update_option('wholesale_mail_settings', $settings, false);

	$message = 'SMTP settings saved.';
	if ($settings['enabled'] && '' === $host) {
		wholesale_email_redirect('smtp', 'Settings saved, but SMTP stays off until you enter an SMTP host.', 'warning');
	}
	if (count($recipients) < count(array_unique($recipient_input))) {
		wholesale_email_redirect('smtp', 'Settings saved, but some notification recipients were not valid email addresses and were left out.', 'warning');
	}
	if (isset($post['from_email']) && '' !== trim($post['from_email']) && '' === $from_email) {
		wholesale_email_redirect('smtp', 'Settings saved, but the From email address was not valid, so the site admin email is used.', 'warning');
	}
	wholesale_email_redirect('smtp', $message . ' Send a test email to confirm delivery.');
}
add_action('admin_post_wholesale_save_smtp', 'wholesale_handle_save_smtp_settings');

function wholesale_handle_test_email()
{
	wholesale_email_require_admin('wholesale_send_test_email');

	$to = isset($_POST['wholesale_test_email_to']) ? sanitize_email(wp_unslash($_POST['wholesale_test_email_to'])) : '';
	if (!is_email($to)) {
		wholesale_email_redirect('smtp', 'Enter a valid email address to send the test to.', 'error');
	}

	$status = wholesale_mail_status();
	$html = '<div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;color:#1d2327;">'
		. '<h2 style="margin:0 0 12px;font-size:20px;">Email delivery is working</h2>'
		. '<p>This is a test email from <strong>' . esc_html(get_bloginfo('name')) . '</strong>. If you are reading this, payment requests and order emails can reach this inbox.</p>'
		. '<table style="border-collapse:collapse;font-size:13px;color:#50575e;margin-top:16px;">'
		. '<tr><td style="padding:4px 12px 4px 0;">Sent through</td><td>' . esc_html('smtp' === $status['mode'] ? 'SMTP (' . $status['host'] . ')' : 'PHP mail()') . '</td></tr>'
		. '<tr><td style="padding:4px 12px 4px 0;">From</td><td>' . esc_html($status['from']) . '</td></tr>'
		. '<tr><td style="padding:4px 12px 4px 0;">Time</td><td>' . esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'))) . '</td></tr>'
		. '</table></div>';
	$result = wholesale_send_html_mail($to, 'Test email from ' . get_bloginfo('name'), $html, array('Content-Type: text/html; charset=UTF-8'));

	if (true === $result) {
		wholesale_email_redirect('smtp', sprintf('Test email accepted for delivery to %s. If it does not arrive within a few minutes, check the spam folder.', $to), 'success', array('test_to' => rawurlencode($to)));
	}
	wholesale_email_redirect('smtp', 'Test email failed: ' . $result, 'error', array('test_to' => rawurlencode($to)));
}
add_action('admin_post_wholesale_send_test_email', 'wholesale_handle_test_email');

/**
 * Connect and log in to the SMTP server without sending anything, and keep the
 * conversation (with credentials hidden) to show on the settings page.
 */
function wholesale_handle_test_smtp_connection()
{
	wholesale_email_require_admin('wholesale_test_smtp');

	$settings = wholesale_mail_settings();
	if (!$settings['enabled'] || '' === $settings['host']) {
		wholesale_email_redirect('smtp', 'Choose Custom SMTP and save an SMTP host first.', 'error');
	}

	require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
	require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
	require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

	$transcript = array();
	$mail = new PHPMailer\PHPMailer\PHPMailer(true);
	wholesale_apply_smtp($mail, $settings);
	$mail->Timeout = 15;
	$mail->SMTPDebug = 2;
	$mail->Debugoutput = static function ($line) use (&$transcript) {
		$line = trim($line);
		if (preg_match('/^CLIENT -> SERVER:\s*(\S*)/', $line, $m)) {
			$command = strtoupper($m[1]);
			if ('AUTH' === $command) {
				$line = 'CLIENT -> SERVER: AUTH [credentials hidden]';
			} elseif (!in_array($command, array('EHLO', 'HELO', 'STARTTLS', 'QUIT', 'RSET', 'NOOP'), true)) {
				$line = 'CLIENT -> SERVER: [hidden]';
			}
		}
		$transcript[] = $line;
	};

	$ok = false;
	$error = '';
	try {
		$ok = $mail->smtpConnect();
		if (!$ok) {
			$error = $mail->ErrorInfo;
		}
	} catch (Exception $e) {
		$error = $e->getMessage();
	}
	$mail->smtpClose();

	set_transient('wholesale_smtp_transcript_' . get_current_user_id(), array_slice($transcript, -60), 10 * MINUTE_IN_SECONDS);
	if ($ok) {
		wholesale_email_redirect('smtp', sprintf('Connected and logged in to %s:%d successfully.', $settings['host'], $settings['port']));
	}
	wholesale_email_redirect('smtp', 'Could not connect to the SMTP server: ' . ($error ? $error : 'unknown error') . ' See the connection log below.', 'error');
}
add_action('admin_post_wholesale_test_smtp', 'wholesale_handle_test_smtp_connection');

function wholesale_handle_email_log_action()
{
	wholesale_email_require_admin('wholesale_email_log');

	global $wpdb;
	$table = wholesale_email_log_table();
	$request = wp_unslash($_REQUEST);
	$op = isset($request['op']) ? sanitize_key($request['op']) : '';
	if ('' === $op && isset($request['bulk_op'])) {
		$op = sanitize_key($request['bulk_op']);
	}
	$ids = array_filter(array_map('absint', (array) (isset($request['ids']) ? $request['ids'] : (isset($request['id']) ? $request['id'] : array()))));

	if ('clear' === $op) {
		$wpdb->query("TRUNCATE TABLE {$table}");
		wholesale_email_redirect('log', 'The email log was cleared.');
	}
	if (!$ids) {
		wholesale_email_redirect('log', 'Select at least one email first.', 'warning');
	}

	if ('delete' === $op) {
		$wpdb->query("DELETE FROM {$table} WHERE id IN (" . implode(',', $ids) . ')');
		wholesale_email_redirect('log', sprintf(_n('%d email deleted from the log.', '%d emails deleted from the log.', count($ids), 'litsign'), count($ids)));
	}

	if ('resend' === $op) {
		$sent = 0;
		foreach (array_slice($ids, 0, 50) as $id) {
			$sent += wholesale_email_log_resend($id) ? 1 : 0;
		}
		$failed = min(50, count($ids)) - $sent;
		if ($failed) {
			wholesale_email_redirect('log', sprintf('%d resent, %d failed. The new attempts are at the top of the log.', $sent, $failed), $sent ? 'warning' : 'error');
		}
		wholesale_email_redirect('log', sprintf(_n('%d email resent. The new attempt is at the top of the log.', '%d emails resent. The new attempts are at the top of the log.', $sent, 'litsign'), $sent));
	}

	wholesale_email_redirect('log', 'Choose an action.', 'warning');
}
add_action('admin_post_wholesale_email_log', 'wholesale_handle_email_log_action');

function wholesale_ajax_email_log_view()
{
	if (!current_user_can('manage_options') || !check_ajax_referer('wholesale_email_log', 'nonce', false)) {
		wp_send_json_error('Not allowed.', 403);
	}
	$row = wholesale_email_log_get(isset($_POST['id']) ? absint($_POST['id']) : 0);
	if (!$row) {
		wp_send_json_error('This email is no longer in the log.', 404);
	}

	$format = get_option('date_format') . ' ' . get_option('time_format');
	wp_send_json_success(array(
		'id' => (int) $row['id'],
		'date' => get_date_from_gmt($row['created_at'], $format),
		'to' => $row['to_email'],
		'subject' => $row['subject'],
		'from' => $row['from_email'],
		'mailer' => $row['mailer'],
		'source' => $row['source'],
		'status' => wholesale_email_status_meta($row['status'], $row)['key'],
		'error' => (string) $row['error'] . (!empty($row['next_retry']) ? ' Will try again ' . get_date_from_gmt($row['next_retry'], 'M j, g:i a') . '.' : ''),
		'headers' => (string) $row['headers'],
		'attachments' => implode(', ', array_map('basename', array_filter(explode("\n", (string) $row['attachments'])))),
		'is_html' => (bool) wholesale_email_log_is_html($row),
		'message' => (string) $row['message'],
		'resend_url' => wp_nonce_url(admin_url('admin-post.php?action=wholesale_email_log&op=resend&id=' . (int) $row['id']), 'wholesale_email_log'),
	));
}
add_action('wp_ajax_wholesale_email_log_view', 'wholesale_ajax_email_log_view');

/* ---------------------------------------------------------------------------
 * Admin page
 * ------------------------------------------------------------------------ */

function wholesale_email_status_meta($status, $row = array())
{
	if ('failed' === $status && !empty($row['next_retry'])) {
		return array('key' => 'retrying', 'label' => 'Retrying', 'title' => sprintf('Attempt %d failed. Trying again %s.', (int) $row['attempts'], get_date_from_gmt($row['next_retry'], 'M j, g:i a')));
	}

	$map = array(
		'sent' => array('key' => 'sent', 'label' => 'Sent', 'title' => 'Accepted by the mail server for delivery'),
		'failed' => array('key' => 'failed', 'label' => 'Failed', 'title' => 'The mail server refused it or could not be reached'),
		'pending' => array('key' => 'pending', 'label' => 'No result', 'title' => 'wp_mail() started but never reported success or failure'),
	);
	$meta = isset($map[$status]) ? $map[$status] : array('key' => $status, 'label' => ucfirst($status), 'title' => '');
	if (!empty($row['attempts']) && $row['attempts'] > 1) {
		$meta['title'] .= sprintf(' (after %d attempts)', (int) $row['attempts']);
	}

	return $meta;
}

function wholesale_email_admin_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$tab = isset($_GET['page']) && 'wholesale-emails-smtp' === $_GET['page'] ? 'smtp' : 'log';
	$notice_key = 'wholesale_email_notice_' . get_current_user_id();
	$notice = get_transient($notice_key);
	delete_transient($notice_key);
	$status = wholesale_mail_status();
	?>
	<div class="wrap wsm">
		<?php wholesale_email_admin_styles(); ?>
		<header class="wsm-header">
			<div>
				<h1 class="wsm-title"><span class="dashicons dashicons-email"></span> Emails</h1>
				<p class="wsm-subtitle">Every email the site sends, its delivery result, and how it is sent.</p>
			</div>
			<div class="wsm-method is-<?php echo esc_attr($status['mode']); ?>">
				<span class="wsm-dot"></span>
				<?php if ('smtp' === $status['mode']) : ?>
					Sending via SMTP · <strong><?php echo esc_html($status['host']); ?></strong>
				<?php elseif ('default' === $status['mode']) : ?>
					Using the default WordPress mailer
				<?php else : ?>
					SMTP incomplete · using PHP mail() fallback
				<?php endif; ?>
			</div>
		</header>

		<nav class="wsm-tabs">
			<a href="<?php echo esc_url(wholesale_email_admin_url('log')); ?>" class="<?php echo 'log' === $tab ? 'is-active' : ''; ?>"><span class="dashicons dashicons-list-view"></span> Email Log</a>
			<a href="<?php echo esc_url(wholesale_email_admin_url('smtp')); ?>" class="<?php echo 'smtp' === $tab ? 'is-active' : ''; ?>"><span class="dashicons dashicons-admin-generic"></span> SMTP Settings</a>
		</nav>

		<?php if (is_array($notice)) : ?>
			<div class="wsm-alert is-<?php echo esc_attr($notice['type']); ?>"><?php echo esc_html($notice['message']); ?></div>
		<?php endif; ?>

		<?php if ('php_mail' === $status['mode']) : ?>
			<div class="wsm-alert is-warning">
				<strong>Emails are not going through SMTP.</strong> <?php echo esc_html($status['problem']); ?>
				Without SMTP, Gmail and Outlook often reject these emails or put them in spam.
				<?php if ('log' === $tab) : ?><a href="<?php echo esc_url(wholesale_email_admin_url('smtp')); ?>">Set up SMTP →</a><?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		if ('smtp' === $tab) {
			wholesale_email_smtp_tab($status);
		} else {
			wholesale_email_log_tab();
		}
		?>
	</div>
	<?php
}

function wholesale_email_log_tab()
{
	global $wpdb;
	$table = wholesale_email_log_table();

	$filter_status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
	$search = isset($_GET['s']) ? trim(sanitize_text_field(wp_unslash($_GET['s']))) : '';
	$period = isset($_GET['period']) ? sanitize_key(wp_unslash($_GET['period'])) : '';
	$periods = array('' => 'All time', '24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days');
	$period_seconds = array('24h' => DAY_IN_SECONDS, '7d' => 7 * DAY_IN_SECONDS, '30d' => 30 * DAY_IN_SECONDS);
	$per_page = 25;
	$paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);

	$where = array('1=1');
	$params = array();
	if (in_array($filter_status, array('sent', 'failed', 'pending'), true)) {
		$where[] = 'status = %s';
		$params[] = $filter_status;
	}
	if ('' !== $search) {
		$like = '%' . $wpdb->esc_like($search) . '%';
		$where[] = '(to_email LIKE %s OR subject LIKE %s OR source LIKE %s)';
		array_push($params, $like, $like, $like);
	}
	if (isset($period_seconds[$period])) {
		$where[] = 'created_at >= %s';
		$params[] = gmdate('Y-m-d H:i:s', time() - $period_seconds[$period]);
	}
	$where_sql = implode(' AND ', $where);
	$prepare = static function ($sql) use ($wpdb, $params) {
		return $params ? $wpdb->prepare($sql, $params) : $sql;
	};

	$total = (int) $wpdb->get_var($prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"));
	$rows = $wpdb->get_results($prepare("SELECT id, created_at, to_email, subject, from_email, mailer, source, status, error, attachments, attempts, next_retry FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT {$per_page} OFFSET " . (($paged - 1) * $per_page)), ARRAY_A);
	$pages = (int) ceil($total / $per_page);

	$counts = array('sent' => 0, 'failed' => 0, 'pending' => 0);
	foreach ((array) $wpdb->get_results("SELECT status, COUNT(*) AS c FROM {$table} GROUP BY status", ARRAY_A) as $row) {
		$counts[$row['status']] = (int) $row['c'];
	}
	$all = array_sum($counts);
	$last_day = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)));
	$finished = $counts['sent'] + $counts['failed'];
	$rate = $finished ? round($counts['sent'] / $finished * 100, 1) : null;
	$format = get_option('date_format') . ' ' . get_option('time_format');
	$nonce = wp_create_nonce('wholesale_email_log');
	$base = wholesale_email_admin_url('log');
	$filter_url = static function ($args) use ($base, $search, $period) {
		return add_query_arg(array_filter(array_merge(array('s' => $search, 'period' => $period), $args), 'strlen'), $base);
	};
	?>
	<section class="wsm-stats">
		<a class="wsm-stat" href="<?php echo esc_url($filter_url(array('status' => ''))); ?>">
			<span class="wsm-stat-icon is-blue"><span class="dashicons dashicons-email-alt"></span></span>
			<span><span class="wsm-stat-value"><?php echo esc_html(number_format_i18n($all)); ?></span><span class="wsm-stat-label">Total logged</span></span>
		</a>
		<a class="wsm-stat" href="<?php echo esc_url($filter_url(array('status' => 'sent'))); ?>">
			<span class="wsm-stat-icon is-green"><span class="dashicons dashicons-yes-alt"></span></span>
			<span><span class="wsm-stat-value"><?php echo esc_html(number_format_i18n($counts['sent'])); ?></span><span class="wsm-stat-label">Sent</span></span>
		</a>
		<a class="wsm-stat" href="<?php echo esc_url($filter_url(array('status' => 'failed'))); ?>">
			<span class="wsm-stat-icon is-red"><span class="dashicons dashicons-dismiss"></span></span>
			<span><span class="wsm-stat-value"><?php echo esc_html(number_format_i18n($counts['failed'])); ?></span><span class="wsm-stat-label">Failed</span></span>
		</a>
		<div class="wsm-stat">
			<span class="wsm-stat-icon is-purple"><span class="dashicons dashicons-chart-line"></span></span>
			<span><span class="wsm-stat-value"><?php echo null === $rate ? '—' : esc_html($rate . '%'); ?></span><span class="wsm-stat-label">Success rate · <?php echo esc_html(number_format_i18n($last_day)); ?> in last 24h</span></span>
		</div>
	</section>

	<section class="wsm-card">
		<form class="wsm-toolbar" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
			<input type="hidden" name="page" value="wholesale-emails">
			<div class="wsm-segments" role="tablist">
				<?php foreach (array('' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'pending' => 'No result') as $key => $label) : ?>
					<a href="<?php echo esc_url($filter_url(array('status' => $key))); ?>" class="<?php echo $key === $filter_status ? 'is-active' : ''; ?>">
						<?php echo esc_html($label); ?>
						<span class="wsm-count"><?php echo esc_html(number_format_i18n('' === $key ? $all : $counts[$key])); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
			<div class="wsm-toolbar-right">
				<?php if ($filter_status) : ?><input type="hidden" name="status" value="<?php echo esc_attr($filter_status); ?>"><?php endif; ?>
				<select name="period" onchange="this.form.submit()">
					<?php foreach ($periods as $key => $label) : ?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($period, $key); ?>><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="wsm-search">
					<span class="dashicons dashicons-search"></span>
					<input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search recipient, subject or source">
				</div>
				<button class="button">Filter</button>
			</div>
		</form>

		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wsm-bulk-form">
			<input type="hidden" name="action" value="wholesale_email_log">
			<?php wp_nonce_field('wholesale_email_log'); ?>
			<div class="wsm-bulkbar">
				<select name="bulk_op">
					<option value="">Bulk actions</option>
					<option value="resend">Resend</option>
					<option value="delete">Delete</option>
				</select>
				<button class="button" type="submit" data-confirm-bulk>Apply</button>
				<span class="wsm-muted"><?php echo esc_html(sprintf(_n('%s email', '%s emails', $total, 'litsign'), number_format_i18n($total))); ?></span>
				<?php if ($all) : ?>
					<button class="button-link wsm-danger-link" type="submit" name="op" value="clear" data-confirm="Delete every email in the log? This cannot be undone.">Clear entire log</button>
				<?php endif; ?>
			</div>

			<div class="wsm-table-wrap">
				<table class="wsm-table">
					<thead>
						<tr>
							<th class="wsm-col-check"><input type="checkbox" data-check-all aria-label="Select all"></th>
							<th class="wsm-col-status">Status</th>
							<th>Recipient</th>
							<th>Subject</th>
							<th class="wsm-col-mailer">Sent via</th>
							<th class="wsm-col-date">Date</th>
							<th class="wsm-col-actions"><span class="screen-reader-text">Actions</span></th>
						</tr>
					</thead>
					<tbody>
						<?php if (!$rows) : ?>
							<tr><td colspan="7">
								<div class="wsm-empty">
									<span class="dashicons dashicons-email-alt2"></span>
									<strong><?php echo $all ? 'No emails match these filters' : 'No emails logged yet'; ?></strong>
									<p><?php echo $all ? 'Try a different status, period or search.' : 'Emails sent from now on will appear here with their delivery status.'; ?></p>
								</div>
							</td></tr>
						<?php endif; ?>
						<?php foreach ($rows as $row) :
							$meta = wholesale_email_status_meta($row['status'], $row);
							$time = strtotime($row['created_at'] . ' UTC');
							?>
							<tr class="is-<?php echo esc_attr($row['status']); ?>">
								<td class="wsm-col-check"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['id']; ?>" aria-label="Select email"></td>
								<td class="wsm-col-status"><span class="wsm-pill is-<?php echo esc_attr($meta['key']); ?>" title="<?php echo esc_attr($meta['title']); ?>"><?php echo esc_html($meta['label']); ?></span><?php if ($row['attempts'] > 1) : ?><span class="wsm-attempts"><?php echo esc_html(sprintf('%d attempts', $row['attempts'])); ?></span><?php endif; ?></td>
								<td class="wsm-col-to">
									<span class="wsm-to" title="<?php echo esc_attr($row['to_email']); ?>"><?php echo esc_html($row['to_email']); ?></span>
								</td>
								<td class="wsm-col-subject">
									<button type="button" class="wsm-subject" data-view="<?php echo (int) $row['id']; ?>"><?php echo esc_html('' !== $row['subject'] ? $row['subject'] : '(no subject)'); ?></button>
									<?php if ($row['attachments']) : ?><span class="dashicons dashicons-paperclip wsm-clip" title="<?php echo esc_attr(implode(', ', array_map('basename', explode("\n", $row['attachments'])))); ?>"></span><?php endif; ?>
									<?php if ('failed' === $row['status'] && $row['error']) : ?>
										<span class="wsm-error-line"><?php echo esc_html(wp_trim_words($row['error'], 18)); ?></span>
									<?php elseif ($row['source']) : ?>
										<span class="wsm-source"><?php echo esc_html($row['source']); ?></span>
									<?php endif; ?>
								</td>
								<td class="wsm-col-mailer"><span class="wsm-mailer"><?php echo esc_html($row['mailer'] ? $row['mailer'] : '—'); ?></span></td>
								<td class="wsm-col-date">
									<span title="<?php echo esc_attr(get_date_from_gmt($row['created_at'], $format)); ?>"><?php echo esc_html(sprintf('%s ago', human_time_diff($time))); ?></span>
									<span class="wsm-muted"><?php echo esc_html(get_date_from_gmt($row['created_at'], 'M j, g:i a')); ?></span>
								</td>
								<td class="wsm-col-actions">
									<div class="wsm-actions">
										<button type="button" class="wsm-icon-btn" data-view="<?php echo (int) $row['id']; ?>" title="View email"><span class="dashicons dashicons-visibility"></span></button>
										<a class="wsm-icon-btn" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wholesale_email_log&op=resend&id=' . (int) $row['id']), 'wholesale_email_log')); ?>" data-confirm="Send this email again to <?php echo esc_attr($row['to_email']); ?>?" title="Resend"><span class="dashicons dashicons-controls-repeat"></span></a>
										<a class="wsm-icon-btn is-danger" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wholesale_email_log&op=delete&id=' . (int) $row['id']), 'wholesale_email_log')); ?>" data-confirm="Delete this email from the log?" title="Delete"><span class="dashicons dashicons-trash"></span></a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</form>

		<?php if ($pages > 1) : ?>
			<div class="wsm-pagination">
				<span class="wsm-muted">Page <?php echo esc_html(number_format_i18n($paged)); ?> of <?php echo esc_html(number_format_i18n($pages)); ?></span>
				<?php echo wp_kses_post(paginate_links(array(
					'base' => add_query_arg('paged', '%#%'),
					'format' => '',
					'current' => $paged,
					'total' => $pages,
					'prev_text' => '‹',
					'next_text' => '›',
				))); ?>
			</div>
		<?php endif; ?>
	</section>

	<div class="wsm-modal" id="wsm-modal" hidden>
		<div class="wsm-modal-backdrop" data-close></div>
		<div class="wsm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="wsm-modal-title">
			<header class="wsm-modal-head">
				<div>
					<span class="wsm-pill" data-field="status"></span>
					<h2 id="wsm-modal-title" data-field="subject"></h2>
				</div>
				<button type="button" class="wsm-icon-btn" data-close title="Close"><span class="dashicons dashicons-no-alt"></span></button>
			</header>
			<div class="wsm-modal-error" data-field="error" hidden></div>
			<dl class="wsm-meta">
				<div><dt>To</dt><dd data-field="to"></dd></div>
				<div><dt>From</dt><dd data-field="from"></dd></div>
				<div><dt>Date</dt><dd data-field="date"></dd></div>
				<div><dt>Sent via</dt><dd data-field="mailer"></dd></div>
				<div><dt>Source</dt><dd data-field="source"></dd></div>
				<div data-row="attachments"><dt>Attachments</dt><dd data-field="attachments"></dd></div>
			</dl>
			<div class="wsm-modal-tabs">
				<button type="button" class="is-active" data-pane="preview">Preview</button>
				<button type="button" data-pane="source">Source</button>
				<button type="button" data-pane="headers">Headers</button>
			</div>
			<div class="wsm-modal-body">
				<div data-pane-body="preview"><iframe title="Email preview" sandbox="allow-popups allow-popups-to-escape-sandbox" data-field="frame"></iframe><pre data-field="text" hidden></pre></div>
				<div data-pane-body="source" hidden><pre data-field="source-code"></pre></div>
				<div data-pane-body="headers" hidden><pre data-field="headers"></pre></div>
			</div>
			<footer class="wsm-modal-foot">
				<span class="wsm-muted">Resending re-attaches files that are still in the uploads folder.</span>
				<a class="button button-primary" data-field="resend" href="#">Resend email</a>
			</footer>
		</div>
	</div>

	<script>
	(function () {
		var nonce = <?php echo wp_json_encode($nonce); ?>;
		var modal = document.getElementById('wsm-modal');
		var labels = {sent: 'Sent', failed: 'Failed', pending: 'No result', retrying: 'Retrying'};
		var field = function (name) { return modal.querySelector('[data-field="' + name + '"]'); };

		document.querySelectorAll('[data-confirm]').forEach(function (el) {
			el.addEventListener('click', function (e) {
				if (!window.confirm(el.getAttribute('data-confirm'))) { e.preventDefault(); }
			});
		});
		var bulkForm = document.getElementById('wsm-bulk-form');
		var bulkBtn = bulkForm.querySelector('[data-confirm-bulk]');
		bulkBtn.addEventListener('click', function (e) {
			var op = bulkForm.querySelector('[name="bulk_op"]').value;
			var n = bulkForm.querySelectorAll('input[name="ids[]"]:checked').length;
			if (!op || !n) { e.preventDefault(); window.alert('Choose an action and select at least one email.'); return; }
			var verb = op === 'delete' ? 'Delete ' : 'Resend ';
			if (!window.confirm(verb + n + ' email' + (n === 1 ? '' : 's') + '?')) { e.preventDefault(); }
		});
		var checkAll = document.querySelector('[data-check-all]');
		if (checkAll) {
			checkAll.addEventListener('change', function () {
				document.querySelectorAll('input[name="ids[]"]').forEach(function (box) { box.checked = checkAll.checked; });
			});
		}

		function showPane(name) {
			modal.querySelectorAll('[data-pane]').forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-pane') === name); });
			modal.querySelectorAll('[data-pane-body]').forEach(function (p) { p.hidden = p.getAttribute('data-pane-body') !== name; });
		}
		modal.querySelectorAll('[data-pane]').forEach(function (b) {
			b.addEventListener('click', function () { showPane(b.getAttribute('data-pane')); });
		});
		function close() { modal.hidden = true; field('frame').srcdoc = ''; document.body.classList.remove('wsm-lock'); }
		modal.querySelectorAll('[data-close]').forEach(function (el) { el.addEventListener('click', close); });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { close(); } });

		function open(id) {
			var body = new FormData();
			body.append('action', 'wholesale_email_log_view');
			body.append('nonce', nonce);
			body.append('id', id);
			modal.hidden = false;
			modal.classList.add('is-loading');
			document.body.classList.add('wsm-lock');
			fetch(window.ajaxurl, {method: 'POST', credentials: 'same-origin', body: body})
				.then(function (r) { return r.json(); })
				.then(function (res) {
					modal.classList.remove('is-loading');
					if (!res.success) { close(); window.alert(res.data || 'Could not load this email.'); return; }
					var d = res.data;
					var pill = field('status');
					pill.className = 'wsm-pill is-' + d.status;
					pill.textContent = labels[d.status] || d.status;
					field('subject').textContent = d.subject || '(no subject)';
					['to', 'from', 'date', 'mailer', 'source', 'attachments'].forEach(function (k) { field(k).textContent = d[k] || '—'; });
					modal.querySelector('[data-row="attachments"]').hidden = !d.attachments;
					var err = field('error');
					err.hidden = !d.error;
					err.textContent = d.error ? 'Delivery error: ' + d.error : '';
					field('headers').textContent = d.headers || '(no custom headers)';
					field('source-code').textContent = d.message;
					field('resend').href = d.resend_url;
					var frame = field('frame');
					var text = field('text');
					if (d.is_html) {
						frame.hidden = false; text.hidden = true;
						frame.srcdoc = '<base target="_blank"><style>body{margin:16px;font-family:-apple-system,Segoe UI,Roboto,sans-serif}</style>' + d.message;
					} else {
						frame.hidden = true; text.hidden = false;
						text.textContent = d.message;
					}
					showPane('preview');
				})
				.catch(function () { close(); window.alert('Could not load this email.'); });
		}
		document.querySelectorAll('[data-view]').forEach(function (el) {
			el.addEventListener('click', function () { open(el.getAttribute('data-view')); });
		});
		field('resend').addEventListener('click', function (e) {
			if (!window.confirm('Send this email again to ' + field('to').textContent + '?')) { e.preventDefault(); }
		});
	})();
	</script>
	<?php
}

function wholesale_email_smtp_tab($status)
{
	$settings = wholesale_mail_settings();
	$providers = wholesale_smtp_providers();
	$provider = isset($providers[$settings['provider']]) ? $settings['provider'] : 'custom';
	$transcript_key = 'wholesale_smtp_transcript_' . get_current_user_id();
	$transcript = get_transient($transcript_key);
	delete_transient($transcript_key);
	$test_to = isset($_GET['test_to']) ? sanitize_email(rawurldecode(wp_unslash($_GET['test_to']))) : '';
	if (!$test_to) {
		$test_to = wp_get_current_user()->user_email;
	}
	$has_password = '' !== $settings['password'];
	?>
	<div class="wsm-grid">
		<form class="wsm-card wsm-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" autocomplete="off">
			<input type="hidden" name="action" value="wholesale_save_smtp">
			<?php wp_nonce_field('wholesale_save_smtp'); ?>

			<?php if ('wp-config' === $settings['source']) : ?>
				<div class="wsm-alert is-info">These values come from <code>wp-config.php</code>. Once you save here, this page's settings are used instead.</div>
			<?php endif; ?>

			<div class="wsm-section">
				<div class="wsm-section-head">
					<div>
						<h2>How emails are sent</h2>
						<p>Choose the mailer used for every email the site sends.</p>
					</div>
				</div>
				<div class="wsm-mailers">
					<label class="wsm-mailer-option">
						<input type="radio" name="mailer" value="default" <?php checked(!$settings['enabled']); ?>>
						<span>
							<span class="dashicons dashicons-wordpress"></span>
							<strong>Default mailer</strong>
							<small>WordPress's built-in sending through the web server (PHP mail()). No setup, but Gmail and Outlook often mark it as spam.</small>
						</span>
					</label>
					<label class="wsm-mailer-option">
						<input type="radio" name="mailer" value="smtp" <?php checked($settings['enabled']); ?>>
						<span>
							<span class="dashicons dashicons-shield"></span>
							<strong>Custom SMTP <em class="wsm-tag is-green">Recommended</em></strong>
							<small>Send through your email provider's server (Google Workspace, Microsoft 365, SendGrid…) for reliable inbox delivery.</small>
						</span>
					</label>
				</div>

				<div id="wsm-smtp-fields" <?php echo $settings['enabled'] ? '' : 'hidden'; ?>>
					<label class="wsm-label">Email provider</label>
					<div class="wsm-providers">
						<?php foreach ($providers as $key => $p) : ?>
							<label class="wsm-provider">
								<input type="radio" name="provider" value="<?php echo esc_attr($key); ?>" <?php checked($provider, $key); ?>
									data-host="<?php echo esc_attr($p['host']); ?>" data-port="<?php echo (int) $p['port']; ?>" data-encryption="<?php echo esc_attr($p['encryption']); ?>" data-hint="<?php echo esc_attr($p['hint']); ?>">
								<span><?php echo esc_html($p['label']); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="wsm-hint" id="wsm-provider-hint"><span class="dashicons dashicons-info-outline"></span> <span><?php echo esc_html($providers[$provider]['hint']); ?></span></p>

					<div class="wsm-row">
						<div class="wsm-field wsm-grow">
							<label class="wsm-label" for="wsm-host">SMTP host</label>
							<input type="text" id="wsm-host" name="host" value="<?php echo esc_attr($settings['host']); ?>" placeholder="smtp.example.com" spellcheck="false">
						</div>
						<div class="wsm-field wsm-port">
							<label class="wsm-label" for="wsm-port">Port</label>
							<input type="number" id="wsm-port" name="port" min="1" max="65535" value="<?php echo (int) $settings['port']; ?>">
						</div>
					</div>

					<label class="wsm-label">Encryption</label>
					<div class="wsm-radio-group">
						<?php foreach (array('ssl' => array('SSL', 'Port 465'), 'tls' => array('TLS / STARTTLS', 'Port 587'), 'none' => array('None', 'Not recommended')) as $key => $label) : ?>
							<label>
								<input type="radio" name="encryption" value="<?php echo esc_attr($key); ?>" <?php checked($settings['encryption'], $key); ?>>
								<span><strong><?php echo esc_html($label[0]); ?></strong><small><?php echo esc_html($label[1]); ?></small></span>
							</label>
						<?php endforeach; ?>
					</div>

					<div class="wsm-inline-toggle">
						<label class="wsm-switch is-small">
							<input type="checkbox" name="auth" value="1" <?php checked($settings['auth']); ?> data-toggle-target="#wsm-auth-fields">
							<span class="wsm-switch-ui"></span>
						</label>
						<span><strong>Authentication</strong> — log in with a username and password (almost always required)</span>
					</div>

					<div class="wsm-row" id="wsm-auth-fields">
						<div class="wsm-field wsm-grow">
							<label class="wsm-label" for="wsm-username">Username</label>
							<input type="text" id="wsm-username" name="username" value="<?php echo esc_attr($settings['username']); ?>" autocomplete="off" spellcheck="false">
						</div>
						<div class="wsm-field wsm-grow">
							<label class="wsm-label" for="wsm-password">Password
								<?php if ($has_password) : ?><span class="wsm-tag is-green">Saved</span><?php elseif ($settings['password_unreadable']) : ?><span class="wsm-tag is-red">Re-enter</span><?php endif; ?>
							</label>
							<div class="wsm-password">
								<input type="password" id="wsm-password" name="password" value="" autocomplete="new-password" placeholder="<?php echo $has_password ? esc_attr('•••••••• leave blank to keep') : 'App password or API key'; ?>">
								<button type="button" class="wsm-icon-btn" data-reveal="#wsm-password" title="Show password"><span class="dashicons dashicons-visibility"></span></button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="wsm-section">
				<div class="wsm-section-head">
					<div>
						<h2>Sender</h2>
						<p>Who customers see the email from. Most providers require the From email to be the account you log in with (or a verified alias).</p>
					</div>
				</div>
				<div class="wsm-row">
					<div class="wsm-field wsm-grow">
						<label class="wsm-label" for="wsm-from-email">From email</label>
						<input type="email" id="wsm-from-email" name="from_email" value="<?php echo esc_attr($settings['from_email']); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
					</div>
					<div class="wsm-field wsm-grow">
						<label class="wsm-label" for="wsm-from-name">From name</label>
						<input type="text" id="wsm-from-name" name="from_name" value="<?php echo esc_attr($settings['from_name']); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
					</div>
				</div>
			</div>

			<div class="wsm-section">
				<div class="wsm-section-head">
					<div>
						<h2>Email log</h2>
						<p>Keep a copy of every email sent, with its delivery result.</p>
					</div>
					<label class="wsm-switch">
						<input type="checkbox" name="log_enabled" value="1" <?php checked($settings['log_enabled']); ?> data-toggle-target="#wsm-log-fields">
						<span class="wsm-switch-ui"></span>
						<span class="screen-reader-text">Log emails</span>
					</label>
				</div>
				<div class="wsm-row" id="wsm-log-fields">
					<div class="wsm-field">
						<label class="wsm-label" for="wsm-retention">Delete log entries older than</label>
						<div class="wsm-suffix"><input type="number" id="wsm-retention" name="log_retention" min="0" max="3650" value="<?php echo (int) $settings['log_retention']; ?>"><span>days</span></div>
						<p class="wsm-muted">0 keeps them forever.</p>
					</div>
				</div>
				<div class="wsm-inline-toggle">
					<label class="wsm-switch is-small">
						<input type="checkbox" name="auto_retry" value="1" <?php checked($settings['auto_retry']); ?>>
						<span class="wsm-switch-ui"></span>
					</label>
					<span><strong>Retry failed emails automatically</strong> after 5 minutes, 30 minutes and 2 hours (needs the email log on)</span>
				</div>
			</div>

			<div class="wsm-section">
				<div class="wsm-section-head">
					<div>
						<h2>Store notifications</h2>
						<p>New orders, quote requests, contact forms and reviews are emailed to these addresses. Order confirmations and status updates still go to the customer.</p>
					</div>
				</div>
				<label class="wsm-label" for="wsm-recipients">Notification recipients</label>
				<textarea id="wsm-recipients" name="notify_recipients" rows="3" class="wsm-textarea" spellcheck="false"><?php echo esc_textarea(implode("\n", wholesale_contact_admin_recipients())); ?></textarea>
				<p class="wsm-muted">One email address per line (or separated by commas).</p>
			</div>

			<div class="wsm-form-foot">
				<button type="submit" class="button button-primary button-large">Save settings</button>
			</div>
		</form>

		<aside class="wsm-side">
			<div class="wsm-card">
				<h3>Current status</h3>
				<?php
				$state = array(
					'smtp' => array('is-ok', 'dashicons-yes-alt', 'SMTP is active', $status['host'] . ' as ' . $status['username']),
					'default' => array('is-neutral', 'dashicons-wordpress', 'Default mailer', 'Emails are sent by the web server with PHP mail().'),
					'php_mail' => array('is-bad', 'dashicons-warning', 'SMTP is not working', $status['problem'] . ' Falling back to PHP mail().'),
				);
				$state = $state[$status['mode']];
				?>
				<div class="wsm-status-row <?php echo esc_attr($state[0]); ?>">
					<span class="dashicons <?php echo esc_attr($state[1]); ?>"></span>
					<div>
						<strong><?php echo esc_html($state[2]); ?></strong>
						<span><?php echo esc_html($state[3]); ?></span>
					</div>
				</div>
				<dl class="wsm-kv">
					<div><dt>From</dt><dd><?php echo esc_html(wholesale_mail_from_name() . ' <' . $status['from'] . '>'); ?></dd></div>
					<div><dt>Settings from</dt><dd><?php echo 'admin' === $status['source'] ? 'This page' : 'wp-config.php'; ?></dd></div>
				</dl>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="wholesale_test_smtp">
					<?php wp_nonce_field('wholesale_test_smtp'); ?>
					<button type="submit" class="button wsm-block" <?php disabled(!$settings['enabled'] || '' === $settings['host']); ?>><span class="dashicons dashicons-admin-plugins"></span> Test connection</button>
				</form>
				<p class="wsm-muted">Logs in to the saved SMTP server without sending an email (Custom SMTP only).</p>
			</div>

			<div class="wsm-card">
				<h3>Send a test email</h3>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="wholesale_send_test_email">
					<?php wp_nonce_field('wholesale_send_test_email'); ?>
					<label class="wsm-label" for="wholesale_test_email_to">Recipient</label>
					<input type="email" id="wholesale_test_email_to" name="wholesale_test_email_to" value="<?php echo esc_attr($test_to); ?>" required>
					<button type="submit" class="button button-primary wsm-block"><span class="dashicons dashicons-email-alt"></span> Send test email</button>
				</form>
				<p class="wsm-muted">Use an address outside your own domain (for example a personal Gmail) to see what customers receive. Save your settings first.</p>
			</div>

			<?php if (is_array($transcript) && $transcript) : ?>
				<div class="wsm-card">
					<h3>Connection log</h3>
					<pre class="wsm-transcript"><?php echo esc_html(implode("\n", $transcript)); ?></pre>
				</div>
			<?php endif; ?>
		</aside>
	</div>

	<script>
	(function () {
		var host = document.getElementById('wsm-host');
		var port = document.getElementById('wsm-port');
		var hint = document.querySelector('#wsm-provider-hint span:last-child');
		document.querySelectorAll('input[name="provider"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				hint.textContent = radio.getAttribute('data-hint');
				if (radio.value === 'custom') { host.focus(); return; }
				host.value = radio.getAttribute('data-host');
				port.value = radio.getAttribute('data-port');
				var enc = document.querySelector('input[name="encryption"][value="' + radio.getAttribute('data-encryption') + '"]');
				if (enc) { enc.checked = true; }
			});
		});
		var smtpFields = document.getElementById('wsm-smtp-fields');
		document.querySelectorAll('input[name="mailer"]').forEach(function (radio) {
			radio.addEventListener('change', function () { smtpFields.hidden = radio.value !== 'smtp'; });
		});
		var defaultPorts = {ssl: '465', tls: '587', none: '25'};
		document.querySelectorAll('input[name="encryption"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				var known = Object.keys(defaultPorts).map(function (k) { return defaultPorts[k]; });
				if (!port.value || known.indexOf(port.value) !== -1) { port.value = defaultPorts[radio.value]; }
			});
		});
		document.querySelectorAll('[data-toggle-target]').forEach(function (box) {
			var target = document.querySelector(box.getAttribute('data-toggle-target'));
			var sync = function () { target.classList.toggle('is-disabled', !box.checked); };
			box.addEventListener('change', sync);
			sync();
		});
		document.querySelectorAll('[data-reveal]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var input = document.querySelector(btn.getAttribute('data-reveal'));
				input.type = input.type === 'password' ? 'text' : 'password';
				btn.querySelector('.dashicons').className = 'dashicons ' + (input.type === 'password' ? 'dashicons-visibility' : 'dashicons-hidden');
			});
		});
	})();
	</script>
	<?php
}

function wholesale_email_admin_styles()
{
	?>
	<style>
		.wsm{--wsm-bg:#fff;--wsm-border:#e2e4e7;--wsm-muted:#646970;--wsm-text:#1d2327;--wsm-soft:#f6f7f7;--wsm-blue:#2271b1;--wsm-green:#008a20;--wsm-red:#d63638;--wsm-amber:#dba617;--wsm-purple:#7c3aed;--wsm-radius:10px;max-width:1400px;color:var(--wsm-text)}
		.wsm *{box-sizing:border-box}
		.wsm [hidden]{display:none!important}
		.wsm-header{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin:10px 0 18px}
		.wsm-title{display:flex;align-items:center;gap:10px;font-size:24px;font-weight:600;margin:0;padding:0}
		.wsm-title .dashicons{width:36px;height:36px;font-size:22px;line-height:36px;border-radius:8px;background:var(--wsm-blue);color:#fff;text-align:center}
		.wsm-subtitle{margin:6px 0 0;color:var(--wsm-muted)}
		.wsm-method{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:var(--wsm-bg);border:1px solid var(--wsm-border);font-size:13px}
		.wsm-dot{width:8px;height:8px;border-radius:50%;background:var(--wsm-green);box-shadow:0 0 0 3px rgba(0,138,32,.15)}
		.wsm-method.is-php_mail .wsm-dot{background:var(--wsm-red);box-shadow:0 0 0 3px rgba(214,54,56,.15)}
		.wsm-method.is-default .wsm-dot{background:#8c8f94;box-shadow:0 0 0 3px rgba(140,143,148,.15)}
		.wsm-tabs{display:flex;gap:4px;border-bottom:1px solid var(--wsm-border);margin-bottom:20px}
		.wsm-tabs a{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;text-decoration:none;color:var(--wsm-muted);font-weight:500;border-bottom:2px solid transparent;margin-bottom:-1px}
		.wsm-tabs a:hover{color:var(--wsm-text)}
		.wsm-tabs a.is-active{color:var(--wsm-blue);border-bottom-color:var(--wsm-blue)}
		.wsm-tabs a:focus{box-shadow:none;outline:2px solid var(--wsm-blue);outline-offset:-2px}
		.wsm-alert{padding:12px 16px;border-radius:8px;margin:0 0 16px;border:1px solid;font-size:13px;line-height:1.5}
		.wsm-alert.is-success{background:#edfaef;border-color:#b8e6bf;color:#00450c}
		.wsm-alert.is-error{background:#fcf0f1;border-color:#f0b8b9;color:#8a1f1f}
		.wsm-alert.is-warning{background:#fcf9e8;border-color:#f0e0a0;color:#614a00}
		.wsm-alert.is-info{background:#f0f6fc;border-color:#c5d9ed;color:#0a4b78}
		.wsm-card{background:var(--wsm-bg);border:1px solid var(--wsm-border);border-radius:var(--wsm-radius);box-shadow:0 1px 2px rgba(0,0,0,.04)}
		.wsm-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}
		.wsm-stat{display:flex;align-items:center;gap:14px;padding:18px;background:var(--wsm-bg);border:1px solid var(--wsm-border);border-radius:var(--wsm-radius);text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
		a.wsm-stat:hover{border-color:#c3c4c7;box-shadow:0 2px 8px rgba(0,0,0,.06);color:inherit}
		.wsm-stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex:none}
		.wsm-stat-icon.is-blue{background:#e8f1fa;color:var(--wsm-blue)}.wsm-stat-icon.is-green{background:#e7f6ea;color:var(--wsm-green)}.wsm-stat-icon.is-red{background:#fbeaea;color:var(--wsm-red)}.wsm-stat-icon.is-purple{background:#f1ebfe;color:var(--wsm-purple)}
		.wsm-stat-value{display:block;font-size:24px;font-weight:600;line-height:1.1}
		.wsm-stat-label{display:block;color:var(--wsm-muted);font-size:12px;margin-top:4px}
		.wsm-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid var(--wsm-border)}
		.wsm-toolbar-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
		.wsm-segments{display:inline-flex;background:var(--wsm-soft);border-radius:8px;padding:3px;gap:2px}
		.wsm-segments a{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;text-decoration:none;color:var(--wsm-muted);font-weight:500}
		.wsm-segments a.is-active{background:#fff;color:var(--wsm-text);box-shadow:0 1px 2px rgba(0,0,0,.08)}
		.wsm-count{font-size:11px;background:rgba(0,0,0,.06);border-radius:999px;padding:1px 7px}
		.wsm-search{position:relative}
		.wsm-search .dashicons{position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--wsm-muted)}
		.wsm-search input{padding-left:32px!important;min-width:260px}
		.wsm-bulkbar{display:flex;align-items:center;gap:8px;padding:10px 16px;background:#fbfbfc;border-bottom:1px solid var(--wsm-border)}
		.wsm-bulkbar .wsm-danger-link{margin-left:auto}
		.wsm-danger-link{color:var(--wsm-red)!important;text-decoration:none!important}
		.wsm-danger-link:hover{text-decoration:underline!important}
		.wsm-muted{color:var(--wsm-muted);font-size:12px}
		.wsm-table-wrap{overflow-x:auto}
		.wsm-table{width:100%;border-collapse:collapse}
		.wsm-table th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--wsm-muted);font-weight:600;padding:10px 12px;border-bottom:1px solid var(--wsm-border);white-space:nowrap}
		.wsm-table td{padding:12px;border-bottom:1px solid #f0f0f1;vertical-align:top}
		.wsm-table tbody tr:hover{background:#fafbfc}
		.wsm-table tbody tr.is-failed{box-shadow:inset 3px 0 0 var(--wsm-red)}
		.wsm-col-check{width:28px;padding-left:16px!important}
		.wsm-col-status{width:90px}.wsm-col-mailer{width:180px}.wsm-col-date{width:130px}.wsm-col-actions{width:110px}
		.wsm-col-to{max-width:240px}
		.wsm-to{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500}
		.wsm-subject{all:unset;cursor:pointer;color:var(--wsm-blue);font-weight:500}
		.wsm-subject:hover{text-decoration:underline}
		.wsm-subject:focus-visible{outline:2px solid var(--wsm-blue);outline-offset:2px}
		.wsm-clip{font-size:14px;width:14px;height:14px;color:var(--wsm-muted);vertical-align:middle}
		.wsm-source,.wsm-error-line{display:block;font-size:12px;margin-top:3px;color:var(--wsm-muted);font-family:Consolas,Monaco,monospace}
		.wsm-error-line{color:var(--wsm-red);font-family:inherit}
		.wsm-mailer{font-size:12px;color:var(--wsm-muted)}
		.wsm-col-date span{display:block}
		.wsm-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
		.wsm-pill::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
		.wsm-pill.is-sent{background:#e7f6ea;color:var(--wsm-green)}
		.wsm-pill.is-failed{background:#fbeaea;color:var(--wsm-red)}
		.wsm-pill.is-pending,.wsm-pill.is-retrying{background:#fcf6e0;color:#8a6d00}
		.wsm-attempts{display:block;font-size:11px;color:var(--wsm-muted);margin-top:4px}
		.wsm-actions{display:flex;gap:4px;justify-content:flex-end}
		.wsm-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;border:1px solid transparent;background:transparent;color:var(--wsm-muted);cursor:pointer;text-decoration:none}
		.wsm-icon-btn:hover{background:var(--wsm-soft);border-color:var(--wsm-border);color:var(--wsm-text)}
		.wsm-icon-btn.is-danger:hover{color:var(--wsm-red);background:#fcf0f1;border-color:#f0b8b9}
		.wsm-empty{text-align:center;padding:48px 16px;color:var(--wsm-muted)}
		.wsm-empty .dashicons{font-size:40px;width:40px;height:40px;color:#c3c4c7}
		.wsm-empty strong{display:block;color:var(--wsm-text);font-size:15px;margin:10px 0 4px}
		.wsm-empty p{margin:0}
		.wsm-pagination{display:flex;justify-content:space-between;align-items:center;padding:12px 16px}
		.wsm-pagination .page-numbers{display:inline-flex;min-width:32px;height:32px;align-items:center;justify-content:center;border:1px solid var(--wsm-border);border-radius:6px;margin-left:4px;text-decoration:none;padding:0 8px}
		.wsm-pagination .page-numbers.current{background:var(--wsm-blue);border-color:var(--wsm-blue);color:#fff}
		.wsm-modal{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:24px}
		.wsm-modal[hidden]{display:none}
		.wsm-modal-backdrop{position:absolute;inset:0;background:rgba(17,24,39,.55)}
		.wsm-modal-dialog{position:relative;background:#fff;border-radius:12px;width:min(920px,100%);max-height:calc(100vh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 50px rgba(0,0,0,.25);overflow:hidden}
		.wsm-modal.is-loading .wsm-modal-dialog>*{opacity:.35;pointer-events:none}
		.wsm-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:18px 20px 12px}
		.wsm-modal-head h2{margin:8px 0 0;font-size:18px;line-height:1.35}
		.wsm-modal-error{margin:0 20px 12px;padding:10px 12px;border-radius:8px;background:#fcf0f1;color:#8a1f1f;border:1px solid #f0b8b9;font-size:13px}
		.wsm-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 24px;margin:0;padding:0 20px 14px;border-bottom:1px solid var(--wsm-border)}
		.wsm-meta div{display:flex;gap:10px;min-width:0}
		.wsm-meta dt{width:84px;flex:none;color:var(--wsm-muted);font-size:12px}
		.wsm-meta dd{margin:0;font-size:13px;overflow-wrap:anywhere}
		.wsm-meta [hidden]{display:none}
		.wsm-modal-tabs{display:flex;gap:4px;padding:10px 20px 0;border-bottom:1px solid var(--wsm-border)}
		.wsm-modal-tabs button{all:unset;cursor:pointer;padding:8px 12px;font-weight:500;color:var(--wsm-muted);border-bottom:2px solid transparent;margin-bottom:-1px}
		.wsm-modal-tabs button.is-active{color:var(--wsm-blue);border-bottom-color:var(--wsm-blue)}
		.wsm-modal-body{flex:1;overflow:auto;background:var(--wsm-soft);min-height:300px}
		.wsm-modal-body iframe{display:block;width:100%;height:58vh;border:0;background:#fff}
		.wsm-modal-body pre{margin:0;padding:16px 20px;white-space:pre-wrap;word-break:break-word;font-size:12px;line-height:1.6}
		.wsm-modal-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 20px;border-top:1px solid var(--wsm-border)}
		body.wsm-lock{overflow:hidden}
		.wsm-grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start}
		.wsm-form{padding:0}
		.wsm-form>.wsm-alert{margin:20px 24px 0}
		.wsm-section{padding:22px 24px;border-bottom:1px solid var(--wsm-border)}
		.wsm-section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}
		.wsm-section-head h2{margin:0;font-size:16px}
		.wsm-section-head p{margin:4px 0 0;color:var(--wsm-muted)}
		.wsm-label{display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;margin:0 0 6px}
		.wsm-form input[type=text],.wsm-form input[type=email],.wsm-form input[type=number],.wsm-form input[type=password],.wsm-side input[type=email]{width:100%;min-height:38px;border-radius:6px;border-color:#c3c4c7;padding:0 12px}
		.wsm-textarea{width:100%;border-radius:6px;border-color:#c3c4c7;padding:8px 12px;font-family:Consolas,Monaco,monospace}
		.wsm-row{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px}
		.wsm-field{min-width:0}.wsm-grow{flex:1 1 220px}.wsm-port{flex:0 0 120px}
		.wsm-providers{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:10px}
		.wsm-provider{position:relative;cursor:pointer}
		.wsm-provider input{position:absolute;opacity:0}
		.wsm-provider span{display:flex;align-items:center;justify-content:center;text-align:center;min-height:44px;padding:8px;border:1px solid var(--wsm-border);border-radius:8px;font-weight:500;font-size:12px;transition:all .15s}
		.wsm-provider:hover span{border-color:#8c8f94}
		.wsm-provider input:checked+span{border-color:var(--wsm-blue);background:#f0f6fc;color:var(--wsm-blue);box-shadow:0 0 0 1px var(--wsm-blue)}
		.wsm-provider input:focus-visible+span{outline:2px solid var(--wsm-blue);outline-offset:2px}
		.wsm-hint{display:flex;gap:6px;align-items:flex-start;color:var(--wsm-muted);font-size:12px;margin:0 0 18px}
		.wsm-hint .dashicons{font-size:16px;width:16px;height:16px}
		.wsm-radio-group{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-bottom:18px}
		.wsm-radio-group label{display:flex;gap:8px;align-items:flex-start;padding:10px 12px;border:1px solid var(--wsm-border);border-radius:8px;cursor:pointer}
		.wsm-radio-group label:has(input:checked){border-color:var(--wsm-blue);background:#f0f6fc}
		.wsm-radio-group input{margin-top:2px}
		.wsm-radio-group small{display:block;color:var(--wsm-muted);font-size:11px}
		.wsm-inline-toggle{display:flex;align-items:center;gap:10px;margin-bottom:14px}
		.wsm-password{display:flex;gap:4px;align-items:center}
		.wsm-suffix{display:flex;align-items:center;gap:8px}
		.wsm-suffix input{width:110px!important}
		.wsm-tag{font-size:10px;text-transform:uppercase;letter-spacing:.04em;padding:2px 6px;border-radius:4px;font-weight:600}
		.wsm-tag.is-green{background:#e7f6ea;color:var(--wsm-green)}.wsm-tag.is-red{background:#fbeaea;color:var(--wsm-red)}
		.wsm-switch{position:relative;display:inline-flex;cursor:pointer;flex:none}
		.wsm-switch input{position:absolute;opacity:0;width:1px;height:1px}
		.wsm-switch-ui{width:44px;height:24px;border-radius:999px;background:#c3c4c7;position:relative;transition:background .15s}
		.wsm-switch-ui::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:transform .15s}
		.wsm-switch input:checked+.wsm-switch-ui{background:var(--wsm-blue)}
		.wsm-switch input:checked+.wsm-switch-ui::after{transform:translateX(20px)}
		.wsm-switch input:focus-visible+.wsm-switch-ui{outline:2px solid var(--wsm-blue);outline-offset:2px}
		.wsm-switch.is-small .wsm-switch-ui{width:36px;height:20px}
		.wsm-switch.is-small .wsm-switch-ui::after{width:14px;height:14px}
		.wsm-switch.is-small input:checked+.wsm-switch-ui::after{transform:translateX(16px)}
		.is-disabled{opacity:.45;pointer-events:none}
		.wsm-form-foot{padding:16px 24px;background:#fbfbfc;border-radius:0 0 var(--wsm-radius) var(--wsm-radius);position:sticky;bottom:0}
		.wsm-side{display:flex;flex-direction:column;gap:16px}
		.wsm-side .wsm-card{padding:18px}
		.wsm-side h3{margin:0 0 12px;font-size:14px}
		.wsm-side .wsm-muted{margin:10px 0 0}
		.wsm-block{width:100%;justify-content:center;display:inline-flex!important;align-items:center;gap:6px;margin-top:10px!important;min-height:36px}
		.wsm-status-row{display:flex;gap:10px;padding:12px;border-radius:8px;margin-bottom:12px}
		.wsm-status-row.is-ok{background:#edfaef;color:#00450c}.wsm-status-row.is-bad{background:#fcf0f1;color:#8a1f1f}.wsm-status-row.is-neutral{background:var(--wsm-soft);color:var(--wsm-text)}
		.wsm-mailers{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:20px}
		.wsm-mailer-option{position:relative;cursor:pointer}
		.wsm-mailer-option input{position:absolute;opacity:0}
		.wsm-mailer-option>span{display:block;height:100%;padding:14px 16px;border:1px solid var(--wsm-border);border-radius:10px;transition:all .15s}
		.wsm-mailer-option:hover>span{border-color:#8c8f94}
		.wsm-mailer-option input:checked+span{border-color:var(--wsm-blue);background:#f0f6fc;box-shadow:0 0 0 1px var(--wsm-blue)}
		.wsm-mailer-option input:focus-visible+span{outline:2px solid var(--wsm-blue);outline-offset:2px}
		.wsm-mailer-option .dashicons{color:var(--wsm-blue);margin-bottom:6px}
		.wsm-mailer-option strong{display:flex;align-items:center;gap:8px;font-size:14px}
		.wsm-mailer-option em{font-style:normal}
		.wsm-mailer-option small{display:block;color:var(--wsm-muted);margin-top:4px;line-height:1.5}
		.wsm-status-row strong,.wsm-status-row span{display:block}
		.wsm-status-row div span{font-size:12px;margin-top:2px;overflow-wrap:anywhere}
		.wsm-kv{margin:0}
		.wsm-kv div{display:flex;justify-content:space-between;gap:12px;padding:6px 0;border-top:1px solid #f0f0f1;font-size:12px}
		.wsm-kv dt{color:var(--wsm-muted)}.wsm-kv dd{margin:0;text-align:right;overflow-wrap:anywhere}
		.wsm-transcript{margin:0;max-height:320px;overflow:auto;background:#1d2327;color:#c3e88d;padding:12px;border-radius:8px;font-size:11px;line-height:1.5;white-space:pre-wrap;word-break:break-all}
		@media (max-width:1100px){.wsm-grid{grid-template-columns:1fr}.wsm-providers{grid-template-columns:repeat(2,minmax(0,1fr))}}
		@media (max-width:782px){.wsm-mailers{grid-template-columns:1fr}.wsm-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.wsm-search input{min-width:0;width:100%}.wsm-toolbar-right,.wsm-search{width:100%}.wsm-radio-group,.wsm-meta{grid-template-columns:1fr}.wsm-col-mailer{display:none}}
	</style>
	<?php
}
