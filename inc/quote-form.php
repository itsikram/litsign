<?php
/**
 * Reusable on-page quote form for Google Ads landing pages, modeled on the
 * channel letters page form (page-channel-letters.php, "clq" form): it saves a
 * contact_submission, emails the team and the customer, keeps the ad click,
 * and redirects with wholesale_quote_lead_args() so wholesale_track_quote_lead()
 * fires the Google Ads quote conversion once.
 *
 * Used by /banners-displays/. Arguments for both functions:
 *  - prefix         Form field prefix, e.g. 'bdq'.
 *  - page_url       The page's own URL (the form posts and redirects back to it).
 *  - interests      Choices for "What do you need?".
 *  - project_label  Saved as the project type, e.g. 'Banners & displays'.
 *
 * @package litsign
 */

const WHOLESALE_QUOTE_FORM_MAX_MB = 10;

/**
 * Process a submission of this form, if there is one. Call before get_header():
 * it redirects and exits.
 */
function wholesale_quote_form_handle(array $args)
{
	$prefix = $args['prefix'];
	if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') || !isset($_POST[$prefix . '_submit'])) {
		return;
	}
	$field = static function ($key) use ($prefix) {
		$key = $prefix . '_' . $key;
		return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
	};

	// Keep the click ID on the URL so the page reload still counts as an ad visit.
	$gclid = preg_replace('/[^A-Za-z0-9_-]/', '', $field('gclid'));
	$return_url = $gclid ? add_query_arg('gclid', $gclid, $args['page_url']) : $args['page_url'];
	$fail = static function ($status = 'error') use ($return_url) {
		wp_safe_redirect(add_query_arg('quote_status', $status, $return_url) . '#quote');
		exit;
	};

	if (!wp_verify_nonce($field('nonce'), $prefix . '_quote')) {
		$fail();
	}

	$name = $field('name');
	$email = isset($_POST[$prefix . '_email']) ? sanitize_email(wp_unslash($_POST[$prefix . '_email'])) : '';
	$phone = $field('phone');
	$business = $field('business');
	$interest = in_array($field('interest'), $args['interests'], true) ? $field('interest') : '';
	$details = isset($_POST[$prefix . '_details']) ? sanitize_textarea_field(wp_unslash($_POST[$prefix . '_details'])) : '';

	// Bots fill the hidden website field; people never see it.
	if ($field('website') || !$name || !$phone || !is_email($email)) {
		$fail();
	}

	$file_path = '';
	$file_url = '';
	$file = $_FILES[$prefix . '_file'] ?? null;
	if ($file && UPLOAD_ERR_NO_FILE !== (int) $file['error']) {
		if (UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] > WHOLESALE_QUOTE_FORM_MAX_MB * MB_IN_BYTES) {
			$fail('file_error');
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload = wp_handle_upload($file, array(
			'test_form' => false,
			'mimes' => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png' => 'image/png',
				'pdf' => 'application/pdf',
			),
		));
		if (!empty($upload['error'])) {
			$fail('file_error');
		}
		$file_path = $upload['file'];
		$file_url = $upload['url'];
	}

	$project_type = $args['project_label'] . ($interest ? ': ' . $interest : '');
	$utm_campaign = $field('utm_campaign');
	$utm_term = $field('utm_term');
	$source = implode(', ', array_filter(array(
		$utm_campaign ? 'Campaign: ' . $utm_campaign : '',
		$utm_term ? 'Keyword: ' . $utm_term : '',
		$gclid ? 'GCLID: ' . $gclid : '',
	)));

	$submission_id = wp_insert_post(array(
		'post_type' => 'contact_submission',
		'post_status' => 'publish',
		'post_title' => sprintf('%s - %s', $name, current_time('Y-m-d H:i')),
		'post_content' => $details,
		'meta_input' => array(
			'_contact_name' => $name,
			'_contact_business' => $business,
			'_contact_phone' => $phone,
			'_contact_email' => $email,
			'_contact_project_type' => $project_type,
			'_contact_message' => $details,
			'_contact_logo' => $file_url,
			'_contact_gclid' => $gclid,
			'_contact_source' => $source,
		),
	), true);
	if (is_wp_error($submission_id)) {
		$fail();
	}

	$body = "Name: {$name}\n"
		. "Business: {$business}\n"
		. "Phone: {$phone}\n"
		. "Email: {$email}\n"
		. 'Interested in: ' . ($interest ? $interest : 'Not sure yet') . "\n"
		. 'Artwork: ' . ($file_url ? $file_url : 'Not uploaded') . "\n"
		. ($source ? "Ad source: {$source}\n" : '')
		. "\nSize, quantity and details:\n{$details}\n";
	list($subject, $body) = wholesale_contact_mail_tag(sprintf('New %s quote request from %s', strtolower($args['project_label']), $name), $body);
	wp_mail(
		wholesale_contact_admin_recipients(),
		$subject,
		$body,
		array('Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>'),
		$file_path ? array($file_path) : array()
	);
	wholesale_send_quote_confirmation($submission_id);

	// The quote is saved even when mail fails, so the visitor still sees success.
	wp_safe_redirect(add_query_arg(wholesale_quote_lead_args($submission_id), $return_url) . '#quote');
	exit;
}

/**
 * Whether this page view is the "quote sent" redirect for a real submission.
 */
function wholesale_quote_form_sent()
{
	$lead_id = isset($_GET['lead']) ? absint($_GET['lead']) : 0;
	$lead_key = isset($_GET['lk']) ? sanitize_text_field(wp_unslash($_GET['lk'])) : '';

	return isset($_GET['quote_status']) && 'sent' === $_GET['quote_status'] && $lead_id
		&& hash_equals(wp_hash('cla_lead_' . $lead_id), $lead_key)
		&& 'contact_submission' === get_post_type($lead_id);
}

/**
 * The form card (or the thank-you message after a submission). Uses the
 * channel letters page's clq-* styles in css/cl-quote.css.
 */
function wholesale_quote_form_render(array $args)
{
	$prefix = $args['prefix'];
	$status = isset($_GET['quote_status']) ? sanitize_key(wp_unslash($_GET['quote_status'])) : '';
	$query = static function ($key) {
		return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';
	};
	$name = static function ($key) use ($prefix) {
		return esc_attr($prefix . '_' . $key);
	};
	?>
	<div class="clq-card">
		<?php if (wholesale_quote_form_sent()) : ?>
			<div class="clq-success" role="status">
				<span class="clq-success-icon"><?php echo wholesale_home_icon('check'); ?></span>
				<h3>Thanks! Your quote request is in.</h3>
				<p>A sign specialist will review your request and reach out with pricing. Need it sooner? Call us during business hours.</p>
				<a class="clq-submit" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> Call 866-436-2101</a>
			</div>
		<?php else : ?>
			<form class="clq-form" action="<?php echo esc_url($args['page_url']); ?>#quote" method="post" enctype="multipart/form-data" data-quote-form>
				<?php if ('error' === $status) : ?>
					<p class="clq-alert" role="alert">We couldn&rsquo;t send your request. Please check your name, email and phone, then try again.</p>
				<?php elseif ('file_error' === $status) : ?>
					<p class="clq-alert" role="alert">Your file couldn&rsquo;t be uploaded. Please use a JPG, PNG or PDF under <?php echo esc_html(WHOLESALE_QUOTE_FORM_MAX_MB); ?>&nbsp;MB, or send the form without it.</p>
				<?php endif; ?>

				<input type="hidden" name="<?php echo $name('submit'); ?>" value="1">
				<input type="hidden" name="<?php echo $name('nonce'); ?>" value="<?php echo esc_attr(wp_create_nonce($prefix . '_quote')); ?>">
				<input type="hidden" name="<?php echo $name('gclid'); ?>" value="<?php echo esc_attr(preg_replace('/[^A-Za-z0-9_-]/', '', $query('gclid'))); ?>">
				<input type="hidden" name="<?php echo $name('utm_campaign'); ?>" value="<?php echo esc_attr($query('utm_campaign')); ?>">
				<input type="hidden" name="<?php echo $name('utm_term'); ?>" value="<?php echo esc_attr($query('utm_term')); ?>">
				<div class="clq-hp" aria-hidden="true"><label>Website <input type="text" name="<?php echo $name('website'); ?>" tabindex="-1" autocomplete="off"></label></div>

				<div class="clq-grid">
					<label class="clq-field">
						<span>Your name <b aria-hidden="true">*</b></span>
						<input type="text" name="<?php echo $name('name'); ?>" autocomplete="name" required>
					</label>
					<label class="clq-field">
						<span>Business name</span>
						<input type="text" name="<?php echo $name('business'); ?>" autocomplete="organization">
					</label>
					<label class="clq-field">
						<span>Phone <b aria-hidden="true">*</b></span>
						<input type="tel" name="<?php echo $name('phone'); ?>" autocomplete="tel" inputmode="tel" required>
					</label>
					<label class="clq-field">
						<span>Email <b aria-hidden="true">*</b></span>
						<input type="email" name="<?php echo $name('email'); ?>" autocomplete="email" required>
					</label>
					<label class="clq-field clq-field--wide">
						<span>What do you need?</span>
						<select name="<?php echo $name('interest'); ?>">
							<option value="">Not sure &ndash; help me choose</option>
							<?php foreach ($args['interests'] as $interest) : ?>
								<option value="<?php echo esc_attr($interest); ?>"><?php echo esc_html($interest); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="clq-field clq-field--wide">
						<span>Sizes, quantities and anything else</span>
						<textarea name="<?php echo $name('details'); ?>" rows="3" placeholder="e.g. two 3 x 8 ft vinyl banners with grommets, and one retractable stand"></textarea>
					</label>
					<label class="clq-upload clq-field--wide">
						<input type="file" name="<?php echo $name('file'); ?>" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" data-quote-file>
						<span class="clq-upload-icon"><?php echo wholesale_home_icon('upload'); ?></span>
						<span class="clq-upload-text"><strong data-quote-file-name>Upload your artwork or logo (optional)</strong><small>JPG, PNG or PDF up to <?php echo esc_html(WHOLESALE_QUOTE_FORM_MAX_MB); ?>&nbsp;MB</small></span>
					</label>
				</div>

				<button class="clq-submit" type="submit">Get My Free Quote <?php echo wholesale_home_icon('arrow'); ?></button>
				<p class="clq-note">Your details stay private. Prefer to talk? Call <a href="tel:+18664362101">866-436-2101</a>.</p>
			</form>
			<script>
				(function () {
					var form = document.querySelector('[data-quote-form]');
					if (!form) {
						return;
					}
					var file = form.querySelector('[data-quote-file]');
					var fileName = form.querySelector('[data-quote-file-name]');
					if (file && fileName) {
						file.addEventListener('change', function () {
							fileName.textContent = file.files.length ? file.files[0].name : 'Upload your artwork or logo (optional)';
						});
					}
					form.addEventListener('submit', function () {
						var button = form.querySelector('.clq-submit');
						if (button) {
							button.disabled = true;
							button.textContent = 'Sending...';
						}
					});
				})();
			</script>
		<?php endif; ?>
	</div>
	<?php
}
