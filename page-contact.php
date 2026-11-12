<?php
/**
 * Contact page template: the product-aware quote form (2026-11-05).
 *
 * Buttons pass context as /contact/?interest=banners&item=13oz-vinyl-banner (see
 * wholesale_quote_url()); both are re-validated here (wholesale_quote_context()).
 * The form asks for contact details first, then shows only the questions for the
 * chosen product (inc/buying-journey.php, js/buying-journey.js). Submissions are saved
 * as contact_submission posts with the lead pipeline stage "new".
 *
 * @package litsign
 */

$contact_url = get_permalink();
$quote_status = isset($_GET['quote_status']) ? sanitize_key(wp_unslash($_GET['quote_status'])) : '';
$quote_interests = wholesale_quote_interests();
$quote_spec_fields = wholesale_quote_spec_fields();

// Photos and artwork customers can attach to a quote request.
$quote_file_mimes = array(
	'jpg|jpeg|jpe' => 'image/jpeg',
	'png' => 'image/png',
	'webp' => 'image/webp',
	'heic' => 'image/heic',
	'pdf' => 'application/pdf',
);
$quote_file_max_count = 5;
$quote_file_max_bytes = min(10 * MB_IN_BYTES, wp_max_upload_size());
$quote_file_max_total = 25 * MB_IN_BYTES;
// Email providers reject large messages, so bigger uploads are sent as links only.
$quote_file_attach_limit = 15 * MB_IN_BYTES;

// The form posts in the background (script at the bottom, header X-Contact-Async) and
// gets JSON back; without JavaScript it posts normally and the redirects below run.
$contact_async = !empty($_SERVER['HTTP_X_CONTACT_ASYNC']);

// Error messages, shown above the form after a redirect or returned to the background post.
$quote_status_messages = array(
	'file_error' => 'One of your files couldn’t be uploaded. Please attach up to ' . esc_html($quote_file_max_count) . ' JPG, PNG, WEBP, HEIC or PDF files, each under ' . esc_html(size_format($quote_file_max_bytes)) . ', and try again. Larger files: email a download link to <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a>.',
	'expired' => 'This page was open too long, so the form expired. Please send it again; nothing was saved.',
	'error' => 'Please fill in your name, email, phone and what you need, then try again. If it still doesn’t send, call <a href="tel:+18664362101">866-436-2101</a>.',
);
$contact_async_fail = static function ($status) use ($quote_status_messages) {
	wp_send_json(array(
		'ok' => false,
		'status' => $status,
		// The page is still open with the old form key, so a reload is needed first.
		'message' => 'expired' === $status ? 'This page was open too long, so the form expired. Please reload the page and send it again; nothing was saved.'
			: (isset($quote_status_messages[$status]) ? $quote_status_messages[$status] : $quote_status_messages['error']),
	));
};

// The thank-you panel: printed on the "quote sent" page view and returned to the background post.
$contact_sent_html = static function ($next_steps) use ($contact_url) {
	ob_start();
	?>
	<div class="contact-sent" role="status" tabindex="-1" data-contact-sent>
		<p class="contact-eyebrow">Request received</p>
		<h2 id="contact-form-title">Thanks, we have your request</h2>
		<p>A sign specialist will reply <?php echo esc_html(wholesale_quote_response_text()); ?>. We sent a copy to your email. Nothing is made or charged until you approve a quote.</p>
		<h3>What happens next</h3>
		<ol class="contact-next-steps">
			<?php foreach ($next_steps as $step) : ?>
				<li><?php echo esc_html($step); ?></li>
			<?php endforeach; ?>
		</ol>
		<p>Need it sooner? Call <a href="tel:+18664362101">866-436-2101</a> or text <a href="sms:+12066186543">206-618-6543</a>.</p>
		<p><a class="contact-sent-link" href="<?php echo esc_url($contact_url); ?>">Send another request</a></p>
	</div>
	<?php
	return ob_get_clean();
};

// Success answer for the background post. The Google Ads lead conversion is sent by the
// page itself (same data as wholesale_track_quote_lead()), once per lead.
$contact_async_sent = static function ($lead_id, $interest) use ($contact_url, $quote_interests, $contact_sent_html) {
	$conversion = null;
	if (!get_post_meta($lead_id, '_conversion_tracked', true)) {
		update_post_meta($lead_id, '_conversion_tracked', current_time('mysql'));
		$digits = preg_replace('/\D/', '', (string) get_post_meta($lead_id, '_contact_phone', true));
		$conversion = array(
			'send_to' => 'AW-18454059893/' . wholesale_ads_lead_label(),
			'transaction_id' => 'lead-' . $lead_id,
			'user_data' => (object) array_filter(array(
				'email' => (string) get_post_meta($lead_id, '_contact_email', true),
				'phone_number' => 10 === strlen($digits) ? '+1' . $digits : (11 === strlen($digits) && '1' === $digits[0] ? '+' . $digits : ''),
			)),
			'source' => 'contact_page',
		);
	}
	$next = isset($quote_interests[$interest]['next']) ? $quote_interests[$interest]['next'] : $quote_interests['not-sure']['next'];
	wp_send_json(array(
		'ok' => true,
		'lead' => (int) $lead_id,
		// Shown in the address bar, so a reload shows the thank-you panel again.
		'url' => add_query_arg(array_merge(wholesale_quote_lead_args($lead_id), array('interest' => $interest)), $contact_url) . '#contact-form',
		'html' => $contact_sent_html($next),
		'conversion' => $conversion,
	));
};

// A request larger than post_max_size arrives with an empty $_POST.
if ('POST' === $_SERVER['REQUEST_METHOD'] && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
	if ($contact_async) {
		$contact_async_fail('file_error');
	}
	wp_safe_redirect(add_query_arg('quote_status', 'file_error', $contact_url ? $contact_url : home_url('/contact/')) . '#contact-form');
	exit;
}

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['contact_quote_submit'])) {
	$redirect_url = $contact_url ? $contact_url : home_url('/contact/');
	$post_text = static function ($key, $textarea = false) {
		if (!isset($_POST[$key]) || is_array($_POST[$key])) {
			return '';
		}
		$value = wp_unslash($_POST[$key]);
		return $textarea ? sanitize_textarea_field($value) : sanitize_text_field($value);
	};

	// Keep the context on error redirects so the visitor sees the same form again.
	$context = wholesale_quote_context($post_text('contact_interest'), $post_text('contact_item'));
	$redirect_url = add_query_arg(array_filter(array(
		'interest' => $context['interest'],
		'item' => $context['item'] ? $context['item']['key'] : '',
	)), $redirect_url);
	$fail = static function ($status = 'error') use ($redirect_url, $contact_async, $contact_async_fail) {
		if ($contact_async) {
			$contact_async_fail($status);
		}
		wp_safe_redirect(add_query_arg('quote_status', $status, $redirect_url) . '#contact-form');
		exit;
	};

	if (!wp_verify_nonce($post_text('contact_quote_nonce'), 'contact_quote')) {
		$fail('expired');
	}

	$name = $post_text('contact_name');
	$business = $post_text('contact_business');
	$phone = $post_text('contact_phone');
	$email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
	$message = $post_text('contact_message', true);
	$website = $post_text('contact_website');
	$interest = $context['interest'];

	// Bots fill the hidden website field; people never see it.
	if ($website || !$name || !$phone || !$interest || !is_email($email)) {
		$fail();
	}

	// Repeat taps / back-and-resend: the same form key, or the same request within 10 minutes,
	// returns the first submission instead of saving a second one.
	$form_key = preg_replace('/[^A-Za-z0-9-]/', '', $post_text('contact_form_key'));
	$fingerprint = md5(strtolower($email) . '|' . $interest . '|' . $message);
	foreach (array_filter(array($form_key ? 'wholesale_qkey_' . md5($form_key) : '', 'wholesale_qfp_' . $fingerprint)) as $dupe_key) {
		$existing = (int) get_transient($dupe_key);
		if ($existing && 'contact_submission' === get_post_type($existing)) {
			if ($contact_async) {
				$contact_async_sent($existing, $interest);
			}
			wp_safe_redirect(add_query_arg(array_merge(wholesale_quote_lead_args($existing), array('interest' => $interest)), $contact_url) . '#contact-form');
			exit;
		}
	}

	// Only the questions for the chosen product are kept.
	$specs = array();
	$posted_specs = isset($_POST['spec']) && is_array($_POST['spec']) ? wp_unslash($_POST['spec']) : array();
	foreach ($quote_interests[$interest]['fields'] as $group) {
		foreach ($quote_spec_fields[$group] as $spec_name => $spec) {
			if (!isset($posted_specs[$spec_name])) {
				continue;
			}
			$raw = $posted_specs[$spec_name];
			if ('checks' === $spec[1]) {
				$value = implode(', ', array_values(array_intersect_key($spec[2], array_flip(array_map('sanitize_key', (array) $raw)))));
			} elseif ('select' === $spec[1]) {
				$raw = sanitize_key(is_array($raw) ? '' : $raw);
				$value = '' !== $raw && isset($spec[2][$raw]) ? $spec[2][$raw] : '';
			} elseif ('number' === $spec[1]) {
				$value = is_array($raw) ? '' : (string) min(100000, absint($raw));
				$value = '0' === $value ? '' : $value;
			} elseif ('date' === $spec[1]) {
				$value = is_array($raw) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $raw) ? '' : (string) $raw;
			} else {
				$value = is_array($raw) ? '' : mb_substr(sanitize_text_field($raw), 0, 200);
			}
			if ('' !== $value) {
				$specs[$spec[0]] = $value;
			}
		}
	}

	// Where the lead came from: first-party values the page already has (URL tags, the
	// referring page). Kept on the lead only; never sent to analytics.
	$attribution = array();
	foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content') as $utm) {
		$value = mb_substr($post_text('attr_' . $utm), 0, 150);
		if ('' !== $value) {
			$attribution[$utm] = $value;
		}
	}
	$gclid = preg_replace('/[^A-Za-z0-9_-]/', '', $post_text('attr_gclid'));
	foreach (array('attr_landing' => 'landing_page', 'attr_from' => 'quote_button_page', 'attr_referrer' => 'referrer') as $field_name => $attr_key) {
		$value = esc_url_raw(mb_substr($post_text($field_name), 0, 300));
		if ($value) {
			$attribution[$attr_key] = strtok($value, '#');
		}
	}
	$source = implode(', ', array_filter(array(
		!empty($attribution['utm_source']) ? 'Source: ' . $attribution['utm_source'] . (!empty($attribution['utm_medium']) ? ' / ' . $attribution['utm_medium'] : '') : '',
		!empty($attribution['utm_campaign']) ? 'Campaign: ' . $attribution['utm_campaign'] : '',
		!empty($attribution['utm_term']) ? 'Keyword: ' . $attribution['utm_term'] : '',
		$gclid ? 'GCLID: ' . $gclid : '',
	)));

	// Normalize the multi-file field into one array per file.
	$incoming_files = array();
	if (!empty($_FILES['contact_files']['name']) && is_array($_FILES['contact_files']['name'])) {
		foreach (array_keys($_FILES['contact_files']['name']) as $index) {
			if (UPLOAD_ERR_NO_FILE === (int) $_FILES['contact_files']['error'][$index]) {
				continue;
			}
			$incoming_files[] = array(
				'name' => $_FILES['contact_files']['name'][$index],
				'type' => $_FILES['contact_files']['type'][$index],
				'tmp_name' => $_FILES['contact_files']['tmp_name'][$index],
				'error' => (int) $_FILES['contact_files']['error'][$index],
				'size' => (int) $_FILES['contact_files']['size'][$index],
			);
		}
	}

	// Files already uploaded in the background (inc/async-uploads.php) arrive as tokens.
	$incoming_files = array_merge(wholesale_async_upload_take_posted('contact_file_tokens', 'contact'), $incoming_files);

	if (count($incoming_files) > $quote_file_max_count || array_sum(wp_list_pluck($incoming_files, 'size')) > $quote_file_max_total) {
		$fail('file_error');
	}
	foreach ($incoming_files as $file) {
		if (UPLOAD_ERR_OK !== $file['error'] || $file['size'] > $quote_file_max_bytes) {
			$fail('file_error');
		}
	}

	$uploaded_files = array();
	if ($incoming_files) {
		// Keep quote files in their own folder under random names so they can't be guessed.
		$quote_upload_dir = function ($dirs) {
			$dirs['subdir'] = '/quote-files' . $dirs['subdir'];
			$dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
			$dirs['url'] = $dirs['baseurl'] . $dirs['subdir'];
			return $dirs;
		};
		add_filter('upload_dir', $quote_upload_dir);

		foreach ($incoming_files as $file) {
			$original_name = sanitize_file_name(wp_basename($file['name']));
			$file['name'] = wp_generate_password(12, false) . '-' . $original_name;
			$upload = wholesale_async_upload_store($file, array(
				'mimes' => $quote_file_mimes,
			));

			if (!empty($upload['error'])) {
				remove_filter('upload_dir', $quote_upload_dir);
				foreach ($uploaded_files as $done) {
					wp_delete_file($done['file']);
				}
				$fail('file_error');
			}

			$uploaded_files[] = array(
				'name' => $original_name,
				'file' => $upload['file'],
				'url' => $upload['url'],
				'size' => $file['size'],
			);
		}
		remove_filter('upload_dir', $quote_upload_dir);
	}

	$interest_label = $quote_interests[$interest]['label'];
	$project_type = $interest_label . ($context['item'] ? ' – ' . $context['item']['title'] : '');
	$spec_text = '';
	foreach ($specs as $spec_label => $spec_value) {
		$spec_text .= "{$spec_label}: {$spec_value}\n";
	}

	$subject = sprintf('New %s quote request from %s', strtolower($interest_label), $name);
	$body = "Name: {$name}\n"
		. 'Business: ' . ($business ? $business : '—') . "\n"
		. "Phone: {$phone}\n"
		. "Email: {$email}\n"
		. "Interested in: {$project_type}\n"
		. ($context['item'] ? 'From: ' . $context['item']['url'] . "\n" : '')
		. ($spec_text ? "\n" . $spec_text : '')
		. ($source ? "\nLead source: {$source}\n" : '')
		. "\nProject details:\n" . ($message ? $message : '—') . "\n";
	if ($uploaded_files) {
		$body .= "\nAttached files:\n";
		foreach ($uploaded_files as $uploaded) {
			$body .= '- ' . $uploaded['name'] . ' (' . size_format($uploaded['size']) . '): ' . $uploaded['url'] . "\n";
		}
	}
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$submission_id = wp_insert_post(array(
		'post_type' => 'contact_submission',
		'post_status' => 'publish',
		'post_title' => sprintf('%s - %s', $name, current_time('Y-m-d H:i')),
		'post_content' => $message,
		'meta_input' => array(
			'_contact_name' => $name,
			'_contact_business' => $business,
			'_contact_phone' => $phone,
			'_contact_email' => $email,
			'_contact_project_type' => $project_type,
			'_contact_message' => $spec_text ? trim($spec_text . "\n" . $message) : $message,
			'_contact_files' => array_map(function ($uploaded) {
				return array('name' => $uploaded['name'], 'url' => $uploaded['url']);
			}, $uploaded_files),
			'_contact_interest' => $interest,
			'_contact_item' => $context['item'] ? $context['item']['key'] : '',
			'_contact_specs' => $specs,
			'_contact_attribution' => $attribution,
			'_contact_source' => $source,
			'_contact_gclid' => $gclid,
			'_lead_stage' => 'new',
		),
	), true);

	if (is_wp_error($submission_id)) {
		$fail();
	}
	if ($form_key) {
		set_transient('wholesale_qkey_' . md5($form_key), $submission_id, DAY_IN_SECONDS);
	}
	set_transient('wholesale_qfp_' . $fingerprint, $submission_id, 10 * MINUTE_IN_SECONDS);

	$attachments = array_sum(wp_list_pluck($uploaded_files, 'size')) <= $quote_file_attach_limit
		? wp_list_pluck($uploaded_files, 'file')
		: array();

	// No From header here: the site's sender (Emails → SMTP Settings) is used, like every other
	// store email. A From of the Gmail admin address, sent from this server, fails Gmail's checks.
	list($subject, $body) = wholesale_contact_mail_tag($subject, $body);
	wholesale_mail_team($subject, $body, $headers, $attachments);
	wholesale_send_quote_confirmation($submission_id);
	// The request is saved and a failed email is retried automatically, so the visitor sees success.
	if ($contact_async) {
		$contact_async_sent($submission_id, $interest);
	}
	wp_safe_redirect(add_query_arg(array_merge(wholesale_quote_lead_args($submission_id), array('interest' => $interest)), $contact_url) . '#contact-form');
	exit;
}

wholesale_track_quote_lead('contact_page');

$quote_sent = 'sent' === $quote_status && wholesale_quote_form_sent();
$quote_context = wholesale_quote_context(
	isset($_GET['interest']) ? wp_unslash($_GET['interest']) : '',
	isset($_GET['item']) ? wp_unslash($_GET['item']) : ''
);
$current_interest = $quote_context['interest'];
$current_item = $quote_context['item'];
$copy = $current_interest ? $quote_interests[$current_interest] : array(
	'heading' => 'Request a Free Quote',
	'intro' => 'Channel letters, banners, flags, trade show displays and window graphics. Tell us what you need and a specialist replies with a price.',
	'next' => $quote_interests['not-sure']['next'],
);

get_header();
?>

<main id="primary" class="site-main contact-page">
	<div class="container">
		<?php wholesale_breadcrumbs(); ?>

		<section class="contact-hero" aria-labelledby="contact-page-title">
			<div class="contact-hero-copy">
				<p class="contact-eyebrow">Free quotes from real sign specialists</p>
				<h1 id="contact-page-title"><?php echo esc_html($copy['heading']); ?></h1>
				<p><?php echo esc_html($copy['intro']); ?></p>
				<?php // Visitors who would rather talk reach the phone without scrolling past the form (css/ui-polish.css 18). ?>
				<p class="contact-hero-call">Prefer to talk? <a href="tel:+18664362101">Call 866-436-2101</a> or <a href="sms:+12066186543">text 206-618-6543</a></p>
				<div class="contact-trust-row">
					<span><strong>Since 2002</strong> making signs</span>
					<span><strong>Ships</strong> to all 50 states</span>
					<span><strong>No obligation</strong> quotes</span>
				</div>
			</div>
			<div class="contact-hero-accent" aria-hidden="true">
				<span class="contact-accent-letter">A</span>
				<span class="contact-accent-glow"></span>
			</div>
		</section>

		<div class="contact-layout">
			<section class="contact-form-card" id="contact-form" aria-labelledby="contact-form-title">
				<?php if ($quote_sent) : ?>
					<?php echo $contact_sent_html($copy['next']); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
				<?php else : ?>
				<div class="contact-card-heading">
					<p class="contact-eyebrow">Start your project</p>
					<h2 id="contact-form-title">Get your free quote</h2>
					<p>Fields marked <span aria-hidden="true">*</span><span class="screen-reader-text">with an asterisk</span> are required. Everything else is optional.</p>
				</div>

				<?php if (isset($quote_status_messages[$quote_status])) : ?>
					<div class="contact-message contact-message-error" role="alert"><?php echo $quote_status_messages[$quote_status]; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed text above. ?></div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url($contact_url); ?>" class="contact-form" enctype="multipart/form-data" data-contact-form>
					<?php wp_nonce_field('contact_quote', 'contact_quote_nonce'); ?>
					<?php // Also sent when the script falls back to form.submit(), which leaves out the button. ?>
					<input type="hidden" name="contact_quote_submit" value="1">
					<input type="hidden" name="contact_form_key" value="" data-form-key>
					<input type="hidden" name="contact_item" value="<?php echo esc_attr($current_item ? $current_item['key'] : ''); ?>">
					<?php foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'landing', 'from', 'referrer') as $attr_field) : ?>
						<input type="hidden" name="attr_<?php echo esc_attr($attr_field); ?>" value="" data-attr="<?php echo esc_attr($attr_field); ?>">
					<?php endforeach; ?>
					<p class="contact-honeypot" aria-hidden="true">
						<label for="contact-website">Website</label>
						<input type="text" id="contact-website" name="contact_website" tabindex="-1" autocomplete="off">
					</p>

					<?php if ($current_item) : ?>
						<p class="contact-context">Quote for: <strong><?php echo esc_html($current_item['title']); ?></strong> &middot; <a href="<?php echo esc_url($current_item['url']); ?>">view</a></p>
					<?php endif; ?>

					<fieldset class="contact-step">
						<legend>1. Your contact details</legend>
						<div class="contact-form-grid">
							<label for="contact-name">Your name <span aria-hidden="true">*</span>
								<input id="contact-name" name="contact_name" type="text" required autocomplete="name" aria-required="true">
							</label>
							<label for="contact-email">Email address <span aria-hidden="true">*</span>
								<input id="contact-email" name="contact_email" type="email" required autocomplete="email" aria-required="true">
							</label>
							<label for="contact-phone">Phone number <span aria-hidden="true">*</span>
								<input id="contact-phone" name="contact_phone" type="tel" required autocomplete="tel" aria-required="true">
							</label>
							<label for="contact-business">Business name <small class="contact-optional">optional</small>
								<input id="contact-business" name="contact_business" type="text" autocomplete="organization">
							</label>
						</div>
					</fieldset>

					<fieldset class="contact-step contact-interest" aria-describedby="contact-interest-hint">
						<legend>2. What do you need? <span aria-hidden="true">*</span></legend>
						<p class="contact-field-hint" id="contact-interest-hint">Pick the closest one. You can list more in the details.</p>
						<div class="contact-interest-grid">
							<?php foreach ($quote_interests as $interest_key => $interest_info) : ?>
								<label class="contact-interest-option">
									<input type="radio" name="contact_interest" value="<?php echo esc_attr($interest_key); ?>" required <?php checked($current_interest, $interest_key); ?>>
									<span><?php echo esc_html($interest_info['label']); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<fieldset class="contact-step contact-specs" data-contact-specs>
						<legend>3. Project details <small class="contact-optional">optional</small></legend>
						<?php
						$rendered_specs = array();
						foreach ($quote_spec_fields as $group => $group_fields) :
							$show_for = array();
							foreach ($quote_interests as $interest_key => $interest_info) {
								if (in_array($group, $interest_info['fields'], true)) {
									$show_for[] = $interest_key;
								}
							}
							?>
							<div class="contact-spec-group" data-show-for="<?php echo esc_attr(implode(' ', $show_for)); ?>"<?php echo $current_interest && !in_array($current_interest, $show_for, true) ? ' hidden' : ''; ?><?php echo !$current_interest ? ' hidden' : ''; ?>>
								<?php foreach ($group_fields as $spec_name => $spec) :
									$spec_id = 'spec-' . $spec_name;
									if ('checks' === $spec[1]) : ?>
										<fieldset class="contact-checks">
											<legend><?php echo esc_html($spec[0]); ?></legend>
											<?php foreach ($spec[2] as $check_value => $check_label) : ?>
												<label><input type="checkbox" name="spec[<?php echo esc_attr($spec_name); ?>][]" value="<?php echo esc_attr($check_value); ?>"> <?php echo esc_html($check_label); ?></label>
											<?php endforeach; ?>
										</fieldset>
									<?php elseif ('select' === $spec[1]) : ?>
										<label for="<?php echo esc_attr($spec_id); ?>"><?php echo esc_html($spec[0]); ?>
											<select id="<?php echo esc_attr($spec_id); ?>" name="spec[<?php echo esc_attr($spec_name); ?>]">
												<?php foreach ($spec[2] as $option_value => $option_label) : ?>
													<option value="<?php echo esc_attr($option_value); ?>"><?php echo esc_html($option_label); ?></option>
												<?php endforeach; ?>
											</select>
										</label>
									<?php else : ?>
										<label for="<?php echo esc_attr($spec_id); ?>"><?php echo esc_html($spec[0]); ?>
											<input id="<?php echo esc_attr($spec_id); ?>" name="spec[<?php echo esc_attr($spec_name); ?>]" type="<?php echo esc_attr('number' === $spec[1] ? 'number' : ('date' === $spec[1] ? 'date' : 'text')); ?>"<?php echo 'number' === $spec[1] ? ' min="1" max="100000" inputmode="numeric"' : ''; ?><?php echo 'date' === $spec[1] ? ' min="' . esc_attr(current_time('Y-m-d')) . '"' : ''; ?><?php echo $spec[2] && 'text' === $spec[1] ? ' placeholder="' . esc_attr($spec[2]) . '"' : ''; ?>>
										</label>
									<?php endif; ?>
								<?php endforeach; ?>
								<?php if ('cl' === $group) : ?>
									<p class="contact-field-hint">Not sure about style or size? Leave them blank and send a storefront photo; we’ll suggest options. Installation isn’t included: your licensed electrician or sign installer mounts the sign using our wiring diagram and mounting pattern.</p>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>

						<label for="contact-message">Anything else we should know?
							<textarea id="contact-message" name="contact_message" rows="4" placeholder="Wording, colors, where it goes, your deadline, or questions."></textarea>
						</label>
					</fieldset>

					<div class="contact-upload" data-contact-upload data-max-count="<?php echo esc_attr($quote_file_max_count); ?>" data-max-bytes="<?php echo esc_attr($quote_file_max_bytes); ?>" data-max-total="<?php echo esc_attr($quote_file_max_total); ?>">
						<div class="contact-upload-label">
							<span id="contact-files-label">Logo, storefront photo or artwork</span>
							<small>Optional</small>
						</div>
						<input class="contact-upload-input" type="file" id="contact-files" name="contact_files[]" multiple accept=".jpg,.jpeg,.png,.webp,.heic,.pdf,image/jpeg,image/png,image/webp,image/heic,application/pdf" aria-labelledby="contact-files-label" aria-describedby="contact-files-hint">
						<label class="contact-upload-drop" for="contact-files" data-contact-drop>
							<span class="contact-upload-icon">
								<svg aria-hidden="true" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2"/></svg>
							</span>
							<span class="contact-upload-title"><strong>Drag &amp; drop files here</strong> or <u>browse</u></span>
							<span class="contact-upload-hint" id="contact-files-hint">A photo of your storefront or window helps us size signs. Up to <?php echo esc_html($quote_file_max_count); ?> files &middot; JPG, PNG, WEBP, HEIC or PDF &middot; <?php echo esc_html(size_format($quote_file_max_bytes)); ?> each</span>
						</label>
						<p class="contact-upload-error" role="alert" data-contact-upload-error hidden></p>
						<ul class="contact-upload-list" data-contact-upload-list aria-live="polite"></ul>
					</div>
					<p class="contact-form-errors" role="alert" data-contact-errors hidden></p>
					<p class="contact-submit-hint" id="contact-submit-hint">All done? Press <strong>Submit</strong> to send your request to our team.</p>
					<button class="contact-submit" type="submit" aria-describedby="contact-submit-hint" name="contact_quote_submit" value="1" data-contact-submit><span class="contact-submit-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg></span>Submit</button>
					<p class="contact-form-note">We reply <?php echo esc_html(wholesale_quote_response_text()); ?>. We use your details only to answer this request.</p>
				</form>
				<?php endif; ?>
			</section>

			<aside class="contact-info-card" aria-labelledby="contact-info-title">
				<p class="contact-eyebrow">We’re here to help</p>
				<h2 id="contact-info-title">Talk with a sign specialist</h2>
				<p>Help with sizes, materials, artwork and choosing the right sign, banner or display for your space.</p>
				<div class="contact-info-list">
					<a href="tel:+18664362101"><span class="contact-info-icon" aria-hidden="true">&#9742;</span><span><small>Call us</small><strong>866-436-2101</strong></span></a>
					<a href="sms:+12066186543"><span class="contact-info-icon" aria-hidden="true">&#9993;</span><span><small>Text a photo or logo</small><strong>206-618-6543</strong></span></a>
					<a href="mailto:TR@StorefrontSignOnline.com"><span class="contact-info-icon" aria-hidden="true">&#64;</span><span><small>Email us</small><strong>TR@StorefrontSignOnline.com</strong></span></a>
					<div><span class="contact-info-icon" aria-hidden="true">&#9673;</span><span><small>Office address</small><strong>707 S. Grady Way, Suite 600<br>Renton, WA 98057</strong></span></div>
				</div>
				<div class="contact-info-footer">Storefront Sign Online LLC, also doing business as Lit Sign Manufacturing. Mon–Fri, 8am–5pm Pacific. Every order ships to you; we don’t offer walk-ins or pickup.</div>
				<p class="contact-info-links">Know what you want? <a href="<?php echo esc_url(home_url('/banners-displays/')); ?>">Banners &amp; displays</a> and <a href="<?php echo esc_url(home_url('/custom-channel-letters/')); ?>">channel letters</a> are priced online. Planning several items? See <a href="<?php echo esc_url(home_url('/sign-packages/')); ?>">project packages</a>.</p>
			</aside>
		</div>

		<section class="contact-faq" aria-labelledby="contact-faq-title">
			<p class="contact-eyebrow">Helpful answers</p>
			<h2 id="contact-faq-title">Before you send a request</h2>
			<div class="contact-faq-grid">
				<details>
					<summary>What should I include?</summary>
					<p>For signs: your logo, a storefront photo and the rough wall or window size. For banners, flags and displays: the size, quantity and the date you need them. If you don’t know yet, just describe the project.</p>
				</details>
				<details>
					<summary>Can you help me choose a lighting style?</summary>
					<p>Yes. Pick “Not sure – help me choose” and we’ll compare front lit, back lit (halo) and front-and-back lit letters for your storefront.</p>
				</details>
				<details>
					<summary>How quickly will someone respond?</summary>
					<p>A sign specialist replies <?php echo esc_html(wholesale_quote_response_text()); ?>.</p>
				</details>
				<details>
					<summary>Do you install?</summary>
					<p>No. We build, test and ship to all 50 states. Your licensed electrician or sign installer mounts channel letters using the included wiring diagram and mounting pattern.</p>
				</details>
			</div>
		</section>
	</div>
</main>

<script>
	(function () {
		var form = document.querySelector('[data-contact-form]');
		var upload = form && form.querySelector('[data-contact-upload]');
		if (!upload || typeof DataTransfer === 'undefined') {
			return;
		}

		var input = upload.querySelector('input[type="file"]');
		var drop = upload.querySelector('[data-contact-drop]');
		var list = upload.querySelector('[data-contact-upload-list]');
		var errorBox = upload.querySelector('[data-contact-upload-error]');
		var submit = form.querySelector('[data-contact-submit]');
		var maxCount = parseInt(upload.getAttribute('data-max-count'), 10);
		var maxBytes = parseInt(upload.getAttribute('data-max-bytes'), 10);
		var maxTotal = parseInt(upload.getAttribute('data-max-total'), 10);
		var allowed = /\.(jpe?g|png|webp|heic|pdf)$/i;
		var files = [];
		var previews = [];
		// Background uploads (js/async-upload.js): file -> { job, token, progress, label }.
		var uploads = new Map();
		var tokenBox = document.createElement('div');
		tokenBox.hidden = true;
		form.appendChild(tokenBox);

		function formatSize(bytes) {
			return bytes < 1048576 ? Math.max(1, Math.round(bytes / 1024)) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';
		}

		function showError(message) {
			errorBox.textContent = message;
			errorBox.hidden = !message;
		}

		// Files uploaded in the background go as tokens; any others go with the form post.
		function sync() {
			var transfer = new DataTransfer();
			tokenBox.innerHTML = '';
			files.forEach(function (file) {
				var state = uploads.get(file);
				if (state && state.token) {
					var hidden = document.createElement('input');
					hidden.type = 'hidden';
					hidden.name = 'contact_file_tokens[]';
					hidden.value = state.token;
					tokenBox.appendChild(hidden);
				} else {
					transfer.items.add(file);
				}
			});
			input.files = transfer.files;
		}

		function statusText(file) {
			var state = uploads.get(file);
			if (!state || state.failed) {
				return formatSize(file.size);
			}
			return state.token ? formatSize(file.size) + ' · Uploaded ✓' : formatSize(file.size) + ' · Uploading ' + Math.round(state.progress * 100) + '%';
		}

		function startUpload(file) {
			// Looked up now: the uploader script loads after this inline script.
			var asyncApi = window.wholesaleAsyncUpload;
			if (!asyncApi || !asyncApi.upload) {
				return;
			}
			var state = { progress: 0, token: '', failed: false, label: null };
			state.job = asyncApi.upload(file, 'contact', function (p) {
				state.progress = p;
				if (state.label) {
					state.label.textContent = statusText(file);
				}
			});
			uploads.set(file, state);
			asyncApi.track(state.job, form);
			state.job.promise.then(function (token) {
				state.token = token;
			}, function () {
				// Sent with the form post instead.
				state.failed = true;
			}).then(function () {
				if (uploads.get(file) === state) {
					if (state.label) {
						state.label.textContent = statusText(file);
					}
					sync();
				}
			});
		}

		function stopUpload(file) {
			var state = uploads.get(file);
			if (state) {
				uploads.delete(file);
				state.job.abort();
			}
		}

		function render() {
			previews.forEach(function (url) {
				URL.revokeObjectURL(url);
			});
			previews = [];
			list.innerHTML = '';

			files.forEach(function (file, index) {
				var item = document.createElement('li');
				item.className = 'contact-upload-item';

				var thumb = document.createElement('span');
				thumb.className = 'contact-upload-thumb';
				if (/^image\/(jpeg|png|webp)$/.test(file.type)) {
					var url = URL.createObjectURL(file);
					previews.push(url);
					var img = document.createElement('img');
					img.src = url;
					img.alt = '';
					thumb.appendChild(img);
				} else {
					thumb.textContent = (file.name.split('.').pop() || 'file').toUpperCase();
					thumb.classList.add('is-doc');
				}

				var meta = document.createElement('span');
				meta.className = 'contact-upload-meta';
				var name = document.createElement('strong');
				name.textContent = file.name;
				var size = document.createElement('small');
				size.textContent = statusText(file);
				if (uploads.get(file)) {
					uploads.get(file).label = size;
				}
				meta.appendChild(name);
				meta.appendChild(size);

				var remove = document.createElement('button');
				remove.type = 'button';
				remove.className = 'contact-upload-remove';
				remove.setAttribute('aria-label', 'Remove ' + file.name);
				remove.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
				remove.addEventListener('click', function () {
					stopUpload(files.splice(index, 1)[0]);
					showError('');
					sync();
					render();
					input.focus();
				});

				item.appendChild(thumb);
				item.appendChild(meta);
				item.appendChild(remove);
				list.appendChild(item);
			});

			upload.classList.toggle('has-files', files.length > 0);
			drop.classList.toggle('is-full', files.length >= maxCount);
		}

		function addFiles(incoming) {
			var problems = [];
			var total = files.reduce(function (sum, file) {
				return sum + file.size;
			}, 0);

			Array.prototype.forEach.call(incoming, function (file) {
				var duplicate = files.some(function (existing) {
					return existing.name === file.name && existing.size === file.size;
				});
				if (duplicate) {
					return;
				}
				if (files.length >= maxCount) {
					problems.push('You can attach up to ' + maxCount + ' files.');
				} else if (!allowed.test(file.name)) {
					problems.push(file.name + ' isn’t a supported file type.');
				} else if (file.size > maxBytes) {
					problems.push(file.name + ' is larger than ' + formatSize(maxBytes) + '.');
				} else if (total + file.size > maxTotal) {
					problems.push('Files can total up to ' + formatSize(maxTotal) + '.');
				} else {
					files.push(file);
					total += file.size;
					startUpload(file);
				}
			});

			showError(problems.filter(function (problem, i) {
				return problems.indexOf(problem) === i;
			}).join(' '));
			sync();
			render();
		}

		input.addEventListener('change', function () {
			// The input now only holds the new picks; merge them into the kept list.
			var picked = Array.prototype.slice.call(input.files);
			sync();
			addFiles(picked);
		});

		['dragenter', 'dragover'].forEach(function (type) {
			drop.addEventListener(type, function (event) {
				event.preventDefault();
				drop.classList.add('is-dragging');
			});
		});
		['dragleave', 'drop'].forEach(function (type) {
			drop.addEventListener(type, function (event) {
				event.preventDefault();
				if ('dragleave' === type && drop.contains(event.relatedTarget)) {
					return;
				}
				drop.classList.remove('is-dragging');
			});
		});
		drop.addEventListener('drop', function (event) {
			if (event.dataTransfer && event.dataTransfer.files.length) {
				addFiles(event.dataTransfer.files);
			}
		});

		// Runs before the sending script below, so the post carries the current files and tokens.
		form.addEventListener('submit', function () {
			sync();
		});
	})();

	// Send the form in the background and show the thank-you panel in place, without a reload.
	// The server answers JSON when it sees X-Contact-Async; anything unexpected falls back to
	// a normal post (the form key stops a second copy if the first one did arrive).
	(function () {
		var form = document.querySelector('[data-contact-form]');
		var submit = form && form.querySelector('[data-contact-submit]');
		if (!submit) {
			return;
		}
		var card = form.closest('.contact-form-card') || form.parentNode;
		var errors = form.querySelector('[data-contact-errors]');
		var canFetch = !!(window.fetch && window.FormData);
		var busy = false;

		function label(text) {
			for (var i = submit.childNodes.length - 1; i >= 0; i--) {
				if (3 === submit.childNodes[i].nodeType && submit.childNodes[i].nodeValue.trim()) {
					submit.childNodes[i].nodeValue = text;
					return;
				}
			}
			submit.appendChild(document.createTextNode(text));
		}

		function setBusy(on, text) {
			busy = on;
			submit.disabled = on;
			submit.classList.toggle('is-loading', on);
			submit.setAttribute('aria-busy', on ? 'true' : 'false');
			label(on ? text : 'Submit');
		}

		function showError(html) {
			if (!errors) {
				return;
			}
			errors.innerHTML = html || '';
			errors.hidden = !html;
			if (html) {
				errors.scrollIntoView({ block: 'center', behavior: 'smooth' });
			}
		}

		// Same events wholesale_track_quote_lead() prints on the "quote sent" page view.
		function sendConversion(conversion) {
			if (!conversion || !conversion.send_to) {
				return;
			}
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
			if (conversion.user_data && Object.keys(conversion.user_data).length) {
				window.gtag('set', 'user_data', conversion.user_data);
			}
			window.gtag('event', 'conversion', { send_to: conversion.send_to, transaction_id: conversion.transaction_id });
			window.gtag('event', 'generate_lead', { lead_source: conversion.source });
		}

		function hasFiles() {
			return Array.prototype.some.call(form.querySelectorAll('input[type="file"]'), function (field) {
				return !field.disabled && field.files && field.files.length > 0;
			});
		}

		form.addEventListener('submit', function (event) {
			if (!canFetch) {
				// Old browsers: normal post, just show progress.
				window.setTimeout(function () {
					setBusy(true, hasFiles() ? 'Uploading files…' : 'Sending…');
				}, 0);
				return;
			}
			event.preventDefault();
			if (busy) {
				return;
			}
			showError('');
			setBusy(true, hasFiles() ? 'Uploading files…' : 'Sending…');

			fetch(form.action, {
				method: 'POST',
				body: new FormData(form),
				credentials: 'same-origin',
				headers: { 'X-Contact-Async': '1', 'Accept': 'application/json' }
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				if (!result || 'boolean' !== typeof result.ok) {
					throw new Error('Unexpected answer');
				}
				if (!result.ok) {
					setBusy(false);
					showError(result.message);
					return;
				}
				sendConversion(result.conversion);
				if (result.url && window.history && window.history.replaceState) {
					window.history.replaceState(null, '', result.url);
				}
				card.innerHTML = result.html;
				card.classList.add('is-sent');
				var sent = card.querySelector('[data-contact-sent]');
				// Below the sticky site header, so the "Thanks" heading isn't hidden behind it.
				var header = document.getElementById('ssxHeader');
				var offset = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
				window.scrollTo({ top: Math.max(0, card.getBoundingClientRect().top + window.pageYOffset - offset - 16), behavior: 'smooth' });
				if (sent) {
					sent.focus({ preventScroll: true });
				}
				document.dispatchEvent(new CustomEvent('sfs:contact-sent', { detail: { lead: result.lead } }));
			}).catch(function () {
				// Network trouble or a non-JSON answer: send it the normal way instead.
				label('Sending…');
				HTMLFormElement.prototype.submit.call(form);
			});
		});
	})();
</script>

<?php get_footer(); ?>
