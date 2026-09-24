<?php
/**
 * Contact page template.
 *
 * @package litsign
 */

$contact_url = get_permalink();
$quote_status = isset($_GET['quote_status']) ? sanitize_key(wp_unslash($_GET['quote_status'])) : '';

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

	$subject = sprintf('New storefront sign quote request from %s', $name);
	$body = "Name: {$name}\n"
		. "Business: {$business}\n"
		. "Phone: {$phone}\n"
		. "Email: {$email}\n"
		. "Project type: {$project_type}\n\n"
		. "Project details:\n{$message}\n";
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
		),
	), true);

	if (is_wp_error($submission_id)) {
		wp_safe_redirect(add_query_arg('quote_status', 'error', $redirect_url) . '#contact-form');
		exit;
	}

	$headers[] = 'From: ' . get_option('admin_email');
	$sent = wp_mail(wholesale_contact_admin_recipients(), $subject, $body, $headers);
	wp_safe_redirect(add_query_arg('quote_status', $sent ? 'sent' : 'error', $redirect_url) . '#contact-form');
	exit;
}

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
				<?php elseif ('error' === $quote_status) : ?>
					<div class="contact-message contact-message-error" role="alert">Please complete all required fields and try again. If the problem continues, call us directly.</div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url($contact_url); ?>" class="contact-form">
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
					<button class="contact-submit" type="submit" name="contact_quote_submit" value="1">Request my quote <span aria-hidden="true">&rarr;</span></button>
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

<?php get_footer(); ?>
