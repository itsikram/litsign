<?php
/**
 * Contact page template.
 *
 * @package litsign
 */

$contact_url = get_permalink();
$quote_status = isset($_GET['quote_status']) ? sanitize_key(wp_unslash($_GET['quote_status'])) : '';

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

// A request larger than post_max_size arrives with an empty $_POST.
if ('POST' === $_SERVER['REQUEST_METHOD'] && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
	wp_safe_redirect(add_query_arg('quote_status', 'file_error', $contact_url ? $contact_url : home_url('/contact/')) . '#contact-form');
	exit;
}

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['contact_quote_submit'])) {
	$redirect_url = $contact_url ? $contact_url : home_url('/contact/');
	$nonce = isset($_POST['contact_quote_nonce']) ? sanitize_text_field(wp_unslash($_POST['contact_quote_nonce'])) : '';

	if (!wp_verify_nonce($nonce, 'contact_quote')) {
		wp_safe_redirect(add_query_arg('quote_status', 'error', $redirect_url) . '#contact-form');
		exit;
	}

	$name = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
	$business = isset($_POST['contact_business']) ? sanitize_text_field(wp_unslash($_POST['contact_business'])) : '';
	$phone = isset($_POST['contact_phone']) ? sanitize_text_field(wp_unslash($_POST['contact_phone'])) : '';
	$email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
	$project_type = isset($_POST['contact_project_type']) ? sanitize_text_field(wp_unslash($_POST['contact_project_type'])) : '';
	$message = isset($_POST['contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['contact_message'])) : '';
	$website = isset($_POST['contact_website']) ? sanitize_text_field(wp_unslash($_POST['contact_website'])) : '';

	if ($website || !$name || !$business || !$phone || !$project_type || !$message || !is_email($email)) {
		wp_safe_redirect(add_query_arg('quote_status', 'error', $redirect_url) . '#contact-form');
		exit;
	}

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

	$file_fail = function () use ($redirect_url) {
		wp_safe_redirect(add_query_arg('quote_status', 'file_error', $redirect_url) . '#contact-form');
		exit;
	};

	if (count($incoming_files) > $quote_file_max_count || array_sum(wp_list_pluck($incoming_files, 'size')) > $quote_file_max_total) {
		$file_fail();
	}
	foreach ($incoming_files as $file) {
		if (UPLOAD_ERR_OK !== $file['error'] || $file['size'] > $quote_file_max_bytes) {
			$file_fail();
		}
	}

	$uploaded_files = array();
	if ($incoming_files) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

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
			$upload = wp_handle_upload($file, array(
				'test_form' => false,
				'mimes' => $quote_file_mimes,
			));

			if (!empty($upload['error'])) {
				remove_filter('upload_dir', $quote_upload_dir);
				foreach ($uploaded_files as $done) {
					wp_delete_file($done['file']);
				}
				$file_fail();
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

	$subject = sprintf('New storefront sign quote request from %s', $name);
	$body = "Name: {$name}\n"
		. "Business: {$business}\n"
		. "Phone: {$phone}\n"
		. "Email: {$email}\n"
		. "Project type: {$project_type}\n\n"
		. "Project details:\n{$message}\n";
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
			'_contact_message' => $message,
			'_contact_files' => array_map(function ($uploaded) {
				return array('name' => $uploaded['name'], 'url' => $uploaded['url']);
			}, $uploaded_files),
		),
	), true);

	if (is_wp_error($submission_id)) {
		wp_safe_redirect(add_query_arg('quote_status', 'error', $redirect_url) . '#contact-form');
		exit;
	}

	$attachments = array_sum(wp_list_pluck($uploaded_files, 'size')) <= $quote_file_attach_limit
		? wp_list_pluck($uploaded_files, 'file')
		: array();

	$headers[] = 'From: ' . get_option('admin_email');
	wp_mail(wholesale_contact_admin_recipients(), $subject, $body, $headers, $attachments);
	wholesale_send_quote_confirmation($submission_id);
	// The request is saved and a failed email is retried automatically, so the visitor sees success.
	wp_safe_redirect(add_query_arg(wholesale_quote_lead_args($submission_id), $redirect_url) . '#contact-form');
	exit;
}

wholesale_track_quote_lead('contact_page');

get_header();
?>

<main id="primary" class="site-main contact-page">
	<div class="container">
		<?php wholesale_breadcrumbs(); ?>

		<section class="contact-hero" aria-labelledby="contact-page-title">
			<div class="contact-hero-copy">
				<p class="contact-eyebrow">Let’s bring your storefront to life</p>
				<h1 id="contact-page-title">Request a Custom Channel Letter Sign Quote</h1>
				<p>Tell us about your storefront, and our sign specialists will help you plan illuminated channel letters that fit your brand, building, and budget.</p>
				<div class="contact-trust-row">
					<span><strong>5-year</strong> warranty</span>
					<span><strong>UL listed</strong> options</span>
					<span><strong>Retail-focused</strong> service</span>
				</div>
			</div>
			<div class="contact-hero-accent" aria-hidden="true">
				<span class="contact-accent-letter">A</span>
				<span class="contact-accent-glow"></span>
			</div>
		</section>

		<div class="contact-layout">
			<section class="contact-form-card" id="contact-form" aria-labelledby="contact-form-title">
				<div class="contact-card-heading">
					<p class="contact-eyebrow">Start your project</p>
					<h2 id="contact-form-title">Get your free sign quote</h2>
					<p>Share a few details and we’ll follow up with the best next step for your storefront.</p>
				</div>

				<?php if ('sent' === $quote_status) : ?>
					<div class="contact-message contact-message-success" role="status">Thanks! Your request has been sent. Our team will be in touch soon.</div>
				<?php elseif ('file_error' === $quote_status) : ?>
					<div class="contact-message contact-message-error" role="alert">One of your files couldn’t be uploaded. Please attach up to <?php echo esc_html($quote_file_max_count); ?> JPG, PNG, WEBP, HEIC or PDF files, each under <?php echo esc_html(size_format($quote_file_max_bytes)); ?>, and try again.</div>
				<?php elseif ('error' === $quote_status) : ?>
					<div class="contact-message contact-message-error" role="alert">Please complete all required fields and try again. If the problem continues, call us directly.</div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url($contact_url); ?>" class="contact-form" enctype="multipart/form-data" data-contact-form>
					<?php wp_nonce_field('contact_quote', 'contact_quote_nonce'); ?>
					<p class="contact-honeypot" aria-hidden="true">
						<label for="contact-website">Website</label>
						<input type="text" id="contact-website" name="contact_website" tabindex="-1" autocomplete="off">
					</p>
					<div class="contact-form-grid">
						<label for="contact-name">Your name <span aria-hidden="true">*</span>
							<input id="contact-name" name="contact_name" type="text" required autocomplete="name">
						</label>
						<label for="contact-business">Business name <span aria-hidden="true">*</span>
							<input id="contact-business" name="contact_business" type="text" required autocomplete="organization">
						</label>
						<label for="contact-phone">Phone number <span aria-hidden="true">*</span>
							<input id="contact-phone" name="contact_phone" type="tel" required autocomplete="tel">
						</label>
						<label for="contact-email">Email address <span aria-hidden="true">*</span>
							<input id="contact-email" name="contact_email" type="email" required autocomplete="email">
						</label>
					</div>
					<label for="contact-project-type">What type of sign are you interested in? <span aria-hidden="true">*</span>
						<select id="contact-project-type" name="contact_project_type" required>
							<option value="">Select an option</option>
							<optgroup label="Channel letters">
								<option value="Front-lit channel letters">Front-lit channel letters</option>
								<option value="Reverse-lit channel letters">Back-lit channel letters</option>
								<option value="Front-and-back lit channel letters">Front-and-back lit channel letters</option>
								<option value="Halo-lit channel letters">Halo-lit (hidden back) channel letters</option>
								<option value="Exposed acrylic halo-lit letters">Exposed acrylic back / halo-lit letters</option>
								<option value="Inset acrylic face-lit letters">Inset acrylic face-lit letters</option>
								<option value="Borderless acrylic face-lit letters">Borderless acrylic face-lit letters</option>
								<option value="Non-lit dimensional letters">Non-lit dimensional letters</option>
							</optgroup>
							<optgroup label="Storefront &amp; building signs">
								<option value="Lightbox / cabinet sign">Lightbox / cabinet sign</option>
								<option value="Blade / projecting sign">Blade / projecting sign</option>
								<option value="Monument or pylon sign">Monument or pylon sign</option>
								<option value="Neon / LED neon sign">Neon / LED neon sign</option>
								<option value="Window graphics / clings">Window graphics / clings</option>
								<option value="Rigid signs &amp; magnets">Rigid signs &amp; magnets</option>
							</optgroup>
							<optgroup label="Banners &amp; large format prints">
								<option value="Vinyl banners">Vinyl banners</option>
								<option value="Mesh banners">Mesh banners</option>
								<option value="Fabric banners">Fabric banners</option>
								<option value="Backlit banners / film">Backlit banners / film</option>
								<option value="Pole banners">Pole banners</option>
								<option value="Posters / wall art">Posters / wall art</option>
							</optgroup>
							<optgroup label="Displays, flags &amp; events">
								<option value="Advertising flags">Advertising flags</option>
								<option value="Banner stands">Banner stands</option>
								<option value="A-frames &amp; sidewalk signs">A-frames &amp; sidewalk signs</option>
								<option value="Trade show displays">Trade show displays</option>
								<option value="Step and repeat backdrops">Step and repeat backdrops</option>
								<option value="Table throws">Table throws</option>
								<option value="Event tents">Event tents</option>
							</optgroup>
							<optgroup label="Other">
								<option value="Multiple sign types">Multiple sign types</option>
								<option value="Something else">Something else</option>
								<option value="Not sure yet">I’m not sure yet</option>
							</optgroup>
						</select>
					</label>
					<label for="contact-message">Tell us about your storefront <span aria-hidden="true">*</span>
						<textarea id="contact-message" name="contact_message" rows="6" required placeholder="Share your location, approximate sign size, timeline, or any other helpful details."></textarea>
					</label>
					<div class="contact-upload" data-contact-upload data-max-count="<?php echo esc_attr($quote_file_max_count); ?>" data-max-bytes="<?php echo esc_attr($quote_file_max_bytes); ?>" data-max-total="<?php echo esc_attr($quote_file_max_total); ?>">
						<div class="contact-upload-label">
							<span id="contact-files-label">Photos, logo or artwork</span>
							<small>Optional</small>
						</div>
						<input class="contact-upload-input" type="file" id="contact-files" name="contact_files[]" multiple accept=".jpg,.jpeg,.png,.webp,.heic,.pdf,image/jpeg,image/png,image/webp,image/heic,application/pdf" aria-labelledby="contact-files-label" aria-describedby="contact-files-hint">
						<label class="contact-upload-drop" for="contact-files" data-contact-drop>
							<span class="contact-upload-icon">
								<svg aria-hidden="true" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2"/></svg>
							</span>
							<span class="contact-upload-title"><strong>Drag &amp; drop files here</strong> or <u>browse</u></span>
							<span class="contact-upload-hint" id="contact-files-hint">Storefront photos, your logo, or a sketch. Up to <?php echo esc_html($quote_file_max_count); ?> files &middot; JPG, PNG, WEBP, HEIC or PDF &middot; <?php echo esc_html(size_format($quote_file_max_bytes)); ?> each</span>
						</label>
						<p class="contact-upload-error" role="alert" data-contact-upload-error hidden></p>
						<ul class="contact-upload-list" data-contact-upload-list aria-live="polite"></ul>
					</div>
					<button class="contact-submit" type="submit" name="contact_quote_submit" value="1" data-contact-submit>Request my quote <span aria-hidden="true">&rarr;</span></button>
					<p class="contact-form-note">By submitting this form, you’re requesting a conversation about your sign project. We’ll only use your details to respond to your inquiry.</p>
				</form>
			</section>

			<aside class="contact-info-card" aria-labelledby="contact-info-title">
				<p class="contact-eyebrow">We’re here to help</p>
				<h2 id="contact-info-title">Talk with a sign specialist</h2>
				<p>Get practical guidance on materials, illumination, installation planning, and the right channel letter style for your storefront.</p>
				<div class="contact-info-list">
					<a href="tel:+18664362101"><span class="contact-info-icon" aria-hidden="true">&#9742;</span><span><small>Call us</small><strong>866-436-2101</strong></span></a>
					<a href="mailto:TR@StorefrontSignOnline.com"><span class="contact-info-icon" aria-hidden="true">&#9993;</span><span><small>Email us</small><strong>TR@StorefrontSignOnline.com</strong></span></a>
					<div><span class="contact-info-icon" aria-hidden="true">&#9673;</span><span><small>Visit our office</small><strong>707 S. Grady Way, Suite 600<br>Renton, WA 98057</strong></span></div>
				</div>
				<div class="contact-info-footer">Serving retail storefront businesses with custom sign solutions.</div>
			</aside>
		</div>

		<section class="contact-faq" aria-labelledby="contact-faq-title">
			<p class="contact-eyebrow">Helpful answers</p>
			<h2 id="contact-faq-title">Planning a storefront sign?</h2>
			<div class="contact-faq-grid">
				<details>
					<summary>What should I include in my quote request?</summary>
					<p>Storefront photos, approximate dimensions, preferred lighting style, installation location, and your target timeline are all helpful.</p>
				</details>
				<details>
					<summary>Can you help me choose a lighting style?</summary>
					<p>Yes. We can compare front-lit, reverse-lit halo, and front-and-back lit channel letters based on your storefront and brand.</p>
				</details>
				<details>
					<summary>How quickly will someone respond?</summary>
					<p>We review each request and follow up using the contact information you provide.</p>
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

		function formatSize(bytes) {
			return bytes < 1048576 ? Math.max(1, Math.round(bytes / 1024)) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';
		}

		function showError(message) {
			errorBox.textContent = message;
			errorBox.hidden = !message;
		}

		function sync() {
			var transfer = new DataTransfer();
			files.forEach(function (file) {
				transfer.items.add(file);
			});
			input.files = transfer.files;
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
				size.textContent = formatSize(file.size);
				meta.appendChild(name);
				meta.appendChild(size);

				var remove = document.createElement('button');
				remove.type = 'button';
				remove.className = 'contact-upload-remove';
				remove.setAttribute('aria-label', 'Remove ' + file.name);
				remove.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
				remove.addEventListener('click', function () {
					files.splice(index, 1);
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

		form.addEventListener('submit', function () {
			if (!submit || !form.checkValidity()) {
				return;
			}
			// Leave the button's value in the request, then show progress.
			window.setTimeout(function () {
				submit.disabled = true;
				submit.classList.add('is-loading');
				submit.firstChild.textContent = files.length ? 'Uploading files… ' : 'Sending… ';
			}, 0);
		});
	})();
</script>

<?php get_footer(); ?>
