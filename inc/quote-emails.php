<?php
/**
 * Customer emails for quote requests (contact_submission posts): an automatic
 * "we received your request" confirmation, and replies written by the team from
 * the quote request screen in wp-admin. Every email sent to the customer is
 * recorded on the request in `_contact_emails`.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * The store's branded email layout, shared by quote emails and ticket receipts.
 *
 * @param string $eyebrow  Small uppercase line above the title.
 * @param string $title    Heading.
 * @param string $content  Body HTML (already escaped).
 * @param array  $button   Optional array('url' => ..., 'label' => ...).
 */
function wholesale_branded_email_html($eyebrow, $title, $content, $button = array())
{
	$business = wholesale_ticket_business();
	$color = $business['color'] ? $business['color'] : '#1fa8de';

	return '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#f3f5f8;font-family:Arial,Helvetica,sans-serif;color:#1d2733;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f8;padding:24px 12px;"><tr><td align="center">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e3e8ee;">'
		. '<tr><td style="padding:24px 28px;border-bottom:4px solid ' . esc_attr($color) . ';">'
		. ($business['logo'] ? '<img src="' . esc_url($business['logo']) . '" alt="' . esc_attr($business['name']) . '" style="max-height:44px;max-width:220px;">' : '<strong style="font-size:18px;">' . esc_html($business['name']) . '</strong>')
		. '</td></tr>'
		. '<tr><td style="padding:28px 28px 8px;">'
		. ('' !== $eyebrow ? '<p style="margin:0 0 4px;color:#5b6573;font-size:13px;letter-spacing:1px;text-transform:uppercase;">' . esc_html($eyebrow) . '</p>' : '')
		. '<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#0b1f33;">' . esc_html($title) . '</h1>'
		. $content
		. (!empty($button['url']) ? '<p style="margin:8px 0 20px;"><a href="' . esc_url($button['url']) . '" style="display:inline-block;background:' . esc_attr($color) . ';color:#ffffff;padding:13px 26px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">' . esc_html($button['label']) . '</a></p>' : '')
		. '</td></tr>'
		. '<tr><td style="padding:18px 28px;background:#f8fafc;color:#5b6573;font-size:12px;line-height:1.6;border-top:1px solid #e3e8ee;">'
		. esc_html($business['name']) . ' · ' . esc_html($business['address']) . '<br>'
		. 'Questions? Reply to this email or call ' . esc_html($business['phone']) . '.'
		. '</td></tr>'
		. '</table></td></tr></table></body></html>';
}

/**
 * Headers for emails to customers: HTML, replies go to the business inbox.
 */
function wholesale_customer_email_headers($extra = array())
{
	$business = wholesale_ticket_business();
	// wp_mail() splits Reply-To on commas, so a name like "Lit Sign, LLC" must lose them.
	$reply_name = trim(str_replace(array(',', '"', '<', '>'), '', $business['name']));

	return array_merge(array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . (is_email($business['email']) ? $reply_name . ' <' . $business['email'] . '>' : get_option('admin_email')),
	), $extra);
}

function wholesale_email_paragraphs($text)
{
	$html = '';
	foreach (preg_split("/\n\s*\n/", trim((string) $text)) as $paragraph) {
		if ('' !== trim($paragraph)) {
			$html .= '<p style="margin:0 0 16px;line-height:1.6;">' . nl2br(esc_html(trim($paragraph))) . '</p>';
		}
	}
	return $html;
}

/* ---------------------------------------------------------------------------
 * Quote request data
 * ------------------------------------------------------------------------ */

function wholesale_quote_request($post_id)
{
	$meta = static function ($key) use ($post_id) {
		return (string) get_post_meta($post_id, $key, true);
	};
	$files = array();
	foreach ((array) get_post_meta($post_id, '_contact_files', true) as $file) {
		if (!empty($file['name'])) {
			$files[] = $file['name'];
		}
	}
	if ($meta('_contact_logo')) {
		$files[] = wp_basename($meta('_contact_logo'));
	}
	$name = $meta('_contact_name');

	return array(
		'id' => (int) $post_id,
		'name' => $name,
		'first_name' => $name ? strtok($name, ' ') : '',
		'email' => $meta('_contact_email'),
		'business' => $meta('_contact_business'),
		'phone' => $meta('_contact_phone'),
		'project_type' => $meta('_contact_project_type'),
		'zip' => $meta('_contact_zip'),
		'message' => $meta('_contact_message'),
		'files' => $files,
		'date' => get_the_date('F j, Y', $post_id),
	);
}

/**
 * A copy of what the customer sent, for the bottom of confirmations and replies.
 */
function wholesale_quote_request_summary_html($quote, $heading)
{
	$rows = array(
		'Project' => $quote['project_type'],
		'Business' => $quote['business'],
		'Phone' => $quote['phone'],
		'ZIP code' => $quote['zip'],
		'Files' => implode(', ', $quote['files']),
	);
	$html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 20px;background:#f8fafc;border:1px solid #e3e8ee;border-radius:8px;font-size:14px;">'
		. '<tr><td colspan="2" style="padding:14px 16px 6px;font-weight:bold;color:#0b1f33;">' . esc_html($heading) . '</td></tr>';
	foreach ($rows as $label => $value) {
		if ('' !== trim($value)) {
			$html .= '<tr><td style="padding:4px 16px;color:#5b6573;width:110px;vertical-align:top;">' . esc_html($label) . '</td><td style="padding:4px 16px 4px 0;">' . esc_html($value) . '</td></tr>';
		}
	}
	if ('' !== trim($quote['message'])) {
		$html .= '<tr><td colspan="2" style="padding:8px 16px 14px;line-height:1.6;color:#1d2733;">' . nl2br(esc_html($quote['message'])) . '</td></tr>';
	}

	return $html . '</table>';
}

/**
 * @param array $entry type (confirmation|reply), subject, message, ok, error.
 */
function wholesale_quote_record_email($post_id, $entry)
{
	$emails = get_post_meta($post_id, '_contact_emails', true);
	$emails = is_array($emails) ? $emails : array();
	$user = wp_get_current_user();
	$emails[] = array_merge(array(
		'time' => current_time('mysql'),
		'by' => $user->exists() ? $user->display_name : 'Automatic',
	), $entry);
	update_post_meta($post_id, '_contact_emails', $emails);
}

/* ---------------------------------------------------------------------------
 * Sending
 * ------------------------------------------------------------------------ */

function wholesale_quote_placeholders($text, $quote)
{
	return strtr((string) $text, array(
		'{name}' => $quote['first_name'],
		'{store}' => wholesale_ticket_business()['name'],
		'{project}' => $quote['project_type'],
	));
}

/**
 * Email the customer that their quote request arrived. Called by every quote form.
 *
 * @return true|string|null True when sent, the error when it failed, null when turned off.
 */
function wholesale_send_quote_confirmation($post_id)
{
	$quote = wholesale_quote_request($post_id);
	if (!wholesale_setting_enabled('quote_autoreply') || !is_email($quote['email']) || get_post_meta($post_id, '_contact_confirmation_sent', true)) {
		return null;
	}

	$defaults = wholesale_setting_defaults();
	$subject = trim((string) wholesale_get_setting('quote_autoreply_subject'));
	$message = trim((string) wholesale_get_setting('quote_autoreply_message'));
	$subject = wholesale_quote_placeholders('' !== $subject ? $subject : $defaults['quote_autoreply_subject'], $quote);
	$message = wholesale_quote_placeholders('' !== $message ? $message : $defaults['quote_autoreply_message'], $quote);

	$html = wholesale_branded_email_html(
		'Quote request received',
		'Thanks' . ($quote['first_name'] ? ', ' . $quote['first_name'] : '') . '! We have your request.',
		wholesale_email_paragraphs($message) . wholesale_quote_request_summary_html($quote, 'What you sent us (' . $quote['date'] . ')')
	);

	$result = wholesale_send_html_mail($quote['email'], $subject, $html, wholesale_customer_email_headers(array('X-Wholesale-Quote: ' . (int) $post_id)), wholesale_ticket_business()['name']);
	if (true === $result) {
		update_post_meta($post_id, '_contact_confirmation_sent', current_time('mysql'));
	}
	wholesale_quote_record_email($post_id, array(
		'type' => 'confirmation',
		'subject' => $subject,
		'message' => $message,
		'ok' => true === $result,
		'error' => true === $result ? '' : (string) $result,
		'by' => 'Automatic',
	));

	return $result;
}

/**
 * Send a reply written by the team.
 *
 * @return true|string True when sent, otherwise the reason it failed.
 */
function wholesale_send_quote_reply($post_id, $subject, $message, $include_request)
{
	$quote = wholesale_quote_request($post_id);
	if (!is_email($quote['email'])) {
		return 'This quote request has no valid customer email address.';
	}

	$user = wp_get_current_user();
	$business = wholesale_ticket_business();
	$content = '<p style="margin:0 0 16px;line-height:1.6;">Hi' . ($quote['first_name'] ? ' ' . esc_html($quote['first_name']) : '') . ',</p>'
		. wholesale_email_paragraphs($message)
		. '<p style="margin:0 0 20px;line-height:1.6;">' . esc_html($user->display_name) . '<br><span style="color:#5b6573;">' . esc_html($business['name']) . ' · ' . esc_html($business['phone']) . '</span></p>'
		. ($include_request ? wholesale_quote_request_summary_html($quote, 'Your request from ' . $quote['date']) : '');

	$html = wholesale_branded_email_html('Your quote request', $subject, $content);
	$result = wholesale_send_html_mail($quote['email'], $subject, $html, wholesale_customer_email_headers(array('X-Wholesale-Quote: ' . (int) $post_id)), $business['name']);

	wholesale_quote_record_email($post_id, array(
		'type' => 'reply',
		'subject' => $subject,
		'message' => $message,
		'ok' => true === $result,
		'error' => true === $result ? '' : (string) $result,
	));
	if (true === $result) {
		update_post_meta($post_id, '_contact_last_reply', current_time('mysql'));
	}

	return $result;
}

/**
 * A failed confirmation or reply delivered later by the automatic retry
 * (inc/email-log.php) is marked as sent on the quote request.
 */
add_action('wholesale_email_retry_succeeded', function ($row) {
	if (!preg_match('/^X-Wholesale-Quote:\s*(\d+)/mi', (string) $row['headers'], $m)) {
		return;
	}
	$post_id = (int) $m[1];
	$emails = get_post_meta($post_id, '_contact_emails', true);
	if (!is_array($emails)) {
		return;
	}
	foreach ($emails as $i => $email) {
		if (empty($email['ok']) && $email['subject'] === $row['subject']) {
			$emails[$i]['ok'] = true;
			$emails[$i]['error'] = '';
			$emails[$i]['note'] = 'Delivered by automatic retry';
			if ('confirmation' === $email['type']) {
				update_post_meta($post_id, '_contact_confirmation_sent', current_time('mysql'));
			} else {
				update_post_meta($post_id, '_contact_last_reply', current_time('mysql'));
			}
			break;
		}
	}
	update_post_meta($post_id, '_contact_emails', $emails);
});

/* ---------------------------------------------------------------------------
 * Admin: reply box on the quote request screen
 * ------------------------------------------------------------------------ */

add_action('add_meta_boxes_contact_submission', function () {
	add_meta_box('contact-submission-reply', 'Reply to customer', 'wholesale_quote_reply_meta_box', 'contact_submission', 'normal', 'high');
}, 20);

function wholesale_quote_reply_meta_box($post)
{
	$quote = wholesale_quote_request($post->ID);
	$emails = get_post_meta($post->ID, '_contact_emails', true);
	$emails = is_array($emails) ? $emails : array();
	$default_subject = 'Re: Your ' . ($quote['project_type'] ? $quote['project_type'] . ' ' : '') . 'quote request';
	wp_nonce_field('wholesale_quote_reply', 'wholesale_quote_reply_nonce');
	?>
	<style>
		.wqr-thread{margin:0 0 16px;padding:0;list-style:none}
		.wqr-thread li{border:1px solid #e2e4e7;border-radius:8px;padding:12px 14px;margin-bottom:10px;background:#fff}
		.wqr-thread li.is-failed{border-color:#f0b8b9;background:#fcf6f6}
		.wqr-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:6px}
		.wqr-head strong{font-size:13px}
		.wqr-meta{color:#646970;font-size:12px}
		.wqr-tag{display:inline-block;font-size:11px;font-weight:600;padding:1px 8px;border-radius:999px;margin-right:6px;background:#f0f6fc;color:#2271b1}
		.wqr-tag.is-ok{background:#e7f6ea;color:#008a20}.wqr-tag.is-bad{background:#fbeaea;color:#d63638}
		.wqr-body{white-space:pre-wrap;color:#1d2327;font-size:13px;line-height:1.55;max-height:160px;overflow:auto}
		.wqr-error{color:#d63638;font-size:12px;margin-top:6px}
		.wqr-form label{display:block;font-weight:600;margin:0 0 4px}
		.wqr-form input[type=text],.wqr-form textarea{width:100%}
		.wqr-form textarea{min-height:170px}
		.wqr-row{margin-bottom:12px}
		.wqr-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
		.wqr-empty{color:#646970;margin:0 0 14px}
	</style>

	<?php if ($emails) : ?>
		<ul class="wqr-thread">
			<?php foreach (array_reverse($emails) as $email) : ?>
				<li class="<?php echo empty($email['ok']) ? 'is-failed' : ''; ?>">
					<div class="wqr-head">
						<div>
							<span class="wqr-tag"><?php echo 'confirmation' === $email['type'] ? 'Automatic confirmation' : 'Reply'; ?></span>
							<span class="wqr-tag <?php echo empty($email['ok']) ? 'is-bad' : 'is-ok'; ?>"><?php echo empty($email['ok']) ? 'Failed · retrying' : 'Sent'; ?></span>
							<strong><?php echo esc_html($email['subject']); ?></strong>
						</div>
						<span class="wqr-meta"><?php echo esc_html($email['by'] . ' · ' . date_i18n('M j, Y g:i a', strtotime($email['time']))); ?></span>
					</div>
					<div class="wqr-body"><?php echo esc_html($email['message']); ?></div>
					<?php if (!empty($email['error'])) : ?><div class="wqr-error"><?php echo esc_html($email['error']); ?></div><?php endif; ?>
					<?php if (!empty($email['note'])) : ?><div class="wqr-meta"><?php echo esc_html($email['note']); ?></div><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="wqr-empty">No emails have been sent to this customer yet.</p>
	<?php endif; ?>

	<?php if (!is_email($quote['email'])) : ?>
		<p class="wqr-empty">This request has no valid email address, so you cannot reply by email.</p>
		<?php return; ?>
	<?php endif; ?>

	<div class="wqr-form">
		<div class="wqr-row">
			<label>To</label>
			<code><?php echo esc_html(($quote['name'] ? $quote['name'] . ' ' : '') . '<' . $quote['email'] . '>'); ?></code>
		</div>
		<div class="wqr-row">
			<label for="wqr-subject">Subject</label>
			<input type="text" id="wqr-subject" name="wholesale_quote_reply_subject" value="<?php echo esc_attr($default_subject); ?>">
		</div>
		<div class="wqr-row">
			<label for="wqr-message">Message</label>
			<textarea id="wqr-message" name="wholesale_quote_reply_message" placeholder="<?php echo esc_attr('Write your reply. It starts with "Hi ' . ($quote['first_name'] ? $quote['first_name'] : 'there') . '," and ends with your name automatically.'); ?>"></textarea>
		</div>
		<div class="wqr-actions">
			<label style="font-weight:normal;"><input type="checkbox" name="wholesale_quote_reply_include" value="1" checked> Include a copy of their original request</label>
			<button type="submit" class="button button-primary button-large" name="wholesale_quote_reply_send" value="1" data-wqr-send><span class="dashicons dashicons-email-alt" style="margin-top:4px;"></span> Send reply</button>
		</div>
		<p class="wqr-meta">Replies come from <?php echo esc_html(wholesale_mail_from_email()); ?>; the customer's answer goes to <?php echo esc_html(wholesale_ticket_business()['email']); ?>.</p>
	</div>
	<script>
	(function () {
		var btn = document.querySelector('[data-wqr-send]');
		if (!btn) { return; }
		btn.addEventListener('click', function (e) {
			var msg = document.getElementById('wqr-message');
			if (!msg.value.trim()) { e.preventDefault(); msg.focus(); window.alert('Write a message first.'); return; }
			if (!window.confirm('Send this reply to <?php echo esc_js($quote['email']); ?>?')) { e.preventDefault(); }
		});
	})();
	</script>
	<?php
}

function wholesale_quote_handle_reply_save($post_id)
{
	if (empty($_POST['wholesale_quote_reply_send']) || empty($_POST['wholesale_quote_reply_nonce'])) {
		return;
	}
	if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wholesale_quote_reply_nonce'])), 'wholesale_quote_reply') || !current_user_can('edit_post', $post_id)) {
		return;
	}
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
		return;
	}
	// save_post can fire more than once per request; send the reply only once.
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	$subject = isset($_POST['wholesale_quote_reply_subject']) ? sanitize_text_field(wp_unslash($_POST['wholesale_quote_reply_subject'])) : '';
	$message = isset($_POST['wholesale_quote_reply_message']) ? sanitize_textarea_field(wp_unslash($_POST['wholesale_quote_reply_message'])) : '';
	if ('' === trim($message)) {
		set_transient('wholesale_quote_reply_notice_' . get_current_user_id(), array('error', 'The reply was not sent: the message was empty.'), MINUTE_IN_SECONDS);
		return;
	}

	$result = wholesale_send_quote_reply($post_id, '' !== $subject ? $subject : 'Re: Your quote request', $message, !empty($_POST['wholesale_quote_reply_include']));
	set_transient('wholesale_quote_reply_notice_' . get_current_user_id(), true === $result
		? array('success', 'Reply sent to ' . get_post_meta($post_id, '_contact_email', true) . '.')
		: array('error', 'The reply could not be sent: ' . $result . ' It will be retried automatically; see Emails → Email Log.'), MINUTE_IN_SECONDS);
}
add_action('save_post_contact_submission', 'wholesale_quote_handle_reply_save');

add_action('admin_notices', function () {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || 'contact_submission' !== $screen->post_type) {
		return;
	}
	$key = 'wholesale_quote_reply_notice_' . get_current_user_id();
	$notice = get_transient($key);
	if (!is_array($notice)) {
		return;
	}
	delete_transient($key);
	printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($notice[0]), esc_html($notice[1]));
});

// Hide WordPress's "Post updated." message after sending a reply; the notice above says what happened.
add_filter('post_updated_messages', function ($messages) {
	$messages['contact_submission'] = array_fill(0, 11, '');
	return $messages;
});

/* ---------------------------------------------------------------------------
 * Admin: reply status in the quote request list
 * ------------------------------------------------------------------------ */

add_filter('manage_contact_submission_posts_columns', function ($columns) {
	$date = isset($columns['date']) ? $columns['date'] : 'Received';
	unset($columns['date']);
	$columns['contact_reply'] = 'Customer emails';
	$columns['date'] = $date;
	return $columns;
}, 20);

add_action('manage_contact_submission_posts_custom_column', function ($column, $post_id) {
	if ('contact_reply' !== $column) {
		return;
	}
	$replied = get_post_meta($post_id, '_contact_last_reply', true);
	$confirmed = get_post_meta($post_id, '_contact_confirmation_sent', true);
	if ($replied) {
		echo '<span style="color:#008a20;font-weight:600;">Replied</span><br><span style="color:#646970;">' . esc_html(sprintf('%s ago', human_time_diff(strtotime($replied), current_time('timestamp')))) . '</span>';
	} else {
		echo '<span style="color:#b26200;font-weight:600;">Awaiting reply</span>';
	}
	if ($confirmed) {
		echo '<br><span style="color:#646970;">Confirmation sent</span>';
	}
}, 10, 2);
