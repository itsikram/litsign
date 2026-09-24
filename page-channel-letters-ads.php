<?php
/**
 * Template Name: Channel Letters Ads Landing
 * Template Post Type: page
 *
 * Distraction-free channel letter landing page for Google Ads traffic: no site
 * navigation, a short quote form with logo upload, and a lead conversion once
 * the quote is saved. The page is noindexed so it never competes with the
 * organic channel letter pages.
 *
 * Add ?style=front-lit, back-lit, halo or dual to an ad's final URL so the
 * headline matches the ad group.
 *
 * @package litsign
 */

$page_url = get_permalink();
$builder_url = home_url('/channel-letter-builder/');
$phone_display = '866-436-2101';
$phone_link = 'tel:+18664362101';
$text_display = '206-618-6543';
$text_link = 'sms:+12066186543';
$email_address = 'TR@StorefrontSignOnline.com';
$logo_max_mb = 10;

$variants = array(
	'default' => array(
		'title' => 'Custom LED Channel Letters',
		'accent' => 'for Your Storefront',
		'lead' => 'UL listed channel letter signs, made in the USA, tested before shipping and ready to install. Get a free quote from a sign specialist or price your sign online in minutes.',
	),
	'front-lit' => array(
		'title' => 'Front Lit Channel Letters',
		'accent' => 'Built to Get Noticed',
		'lead' => 'Bold, direct LED light that keeps your name bright day and night. UL listed, made in the USA and tested before shipping.',
	),
	'back-lit' => array(
		'title' => 'Back Lit Channel Letters',
		'accent' => 'with a Glowing Halo',
		'lead' => 'Letters that wash the wall behind them in soft light for a clean, upscale look. UL listed, made in the USA and tested before shipping.',
	),
	'halo' => array(
		'title' => 'Halo Lit Channel Letters',
		'accent' => 'for a Premium Storefront',
		'lead' => 'Welded stainless steel letters with a halo glow that sets your brand apart. UL listed, made in the USA and tested before shipping.',
	),
	'dual' => array(
		'title' => 'Front &amp; Back Lit Letters',
		'accent' => 'Twice the Glow',
		'lead' => 'Letters that light up the face and the wall behind them for maximum visibility after dark. UL listed, made in the USA and tested before shipping.',
	),
);

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['cla_submit'])) {
	$field = static function ($key) {
		return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
	};

	// Keep the ad group variant and click ID when the page reloads after submit.
	$variant_key = sanitize_key($field('cla_variant'));
	$gclid = preg_replace('/[^A-Za-z0-9_-]/', '', $field('cla_gclid'));
	$return_url = add_query_arg(array_filter(array(
		'style' => isset($variants[$variant_key]) && 'default' !== $variant_key ? $variant_key : '',
		'gclid' => $gclid,
	)), $page_url ? $page_url : home_url('/'));
	$fail = static function ($status = 'error') use ($return_url) {
		wp_safe_redirect(add_query_arg('quote_status', $status, $return_url) . '#quote');
		exit;
	};

	if (!wp_verify_nonce($field('cla_nonce'), 'cla_quote')) {
		$fail();
	}

	$name = $field('cla_name');
	$email = isset($_POST['cla_email']) ? sanitize_email(wp_unslash($_POST['cla_email'])) : '';
	$phone = $field('cla_phone');
	$business = $field('cla_business');
	$style = $field('cla_style');
	$zip = $field('cla_zip');
	$details = isset($_POST['cla_details']) ? sanitize_textarea_field(wp_unslash($_POST['cla_details'])) : '';

	// Bots fill the hidden website field; people never see it.
	if ($field('cla_website') || !$name || !$phone || !is_email($email)) {
		$fail();
	}

	$logo_path = '';
	$logo_url = '';
	if (!empty($_FILES['cla_logo']) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['cla_logo']['error']) {
		if (UPLOAD_ERR_OK !== (int) $_FILES['cla_logo']['error'] || (int) $_FILES['cla_logo']['size'] > $logo_max_mb * MB_IN_BYTES) {
			$fail('file_error');
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload = wp_handle_upload($_FILES['cla_logo'], array(
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

		$logo_path = $upload['file'];
		$logo_url = $upload['url'];
	}

	$project_type = 'Channel letters (Google Ads)' . ($style ? ': ' . $style : '');
	$source = implode(', ', array_filter(array(
		$field('cla_utm_campaign') ? 'Campaign: ' . $field('cla_utm_campaign') : '',
		$field('cla_utm_term') ? 'Keyword: ' . $field('cla_utm_term') : '',
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
			'_contact_zip' => $zip,
			'_contact_logo' => $logo_url,
			'_contact_gclid' => $gclid,
			'_contact_source' => $source,
		),
	), true);

	if (is_wp_error($submission_id)) {
		$fail();
	}

	$subject = sprintf('New channel letter quote request from %s', $name);
	$body = "Name: {$name}\n"
		. "Business: {$business}\n"
		. "Phone: {$phone}\n"
		. "Email: {$email}\n"
		. "Letter style: " . ($style ? $style : 'Not sure yet') . "\n"
		. "ZIP code: {$zip}\n"
		. 'Logo: ' . ($logo_url ? $logo_url : 'Not uploaded') . "\n"
		. ($source ? "Ad source: {$source}\n" : '')
		. "\nProject details:\n{$details}\n";
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
		'From: ' . get_option('admin_email'),
	);

	wp_mail(wholesale_contact_admin_recipients(), $subject, $body, $headers, $logo_path ? array($logo_path) : array());

	// The quote is saved even when mail fails, so the visitor still sees success.
	wp_safe_redirect(add_query_arg(array(
		'quote_status' => 'sent',
		'lead' => $submission_id,
		'lk' => wp_hash('cla_lead_' . $submission_id),
	), $return_url) . '#quote');
	exit;
}

$variant_key = isset($_GET['style']) ? sanitize_key(wp_unslash($_GET['style'])) : 'default';
$variant_key = isset($variants[$variant_key]) ? $variant_key : 'default';
$variant = $variants[$variant_key];
$quote_status = isset($_GET['quote_status']) ? sanitize_key(wp_unslash($_GET['quote_status'])) : '';
$gclid = isset($_GET['gclid']) ? preg_replace('/[^A-Za-z0-9_-]/', '', wp_unslash($_GET['gclid'])) : '';

// Fire the lead conversion once, only for the visitor who just sent the quote.
$lead_id = isset($_GET['lead']) ? absint($_GET['lead']) : 0;
$lead_key = isset($_GET['lk']) ? sanitize_text_field(wp_unslash($_GET['lk'])) : '';
$lead_valid = 'sent' === $quote_status && $lead_id
	&& hash_equals(wp_hash('cla_lead_' . $lead_id), $lead_key)
	&& 'contact_submission' === get_post_type($lead_id);

if ($lead_valid && !get_post_meta($lead_id, '_conversion_tracked', true)) {
	update_post_meta($lead_id, '_conversion_tracked', current_time('mysql'));
	add_action('wp_footer', function () use ($lead_id) {
		$label = trim((string) wholesale_get_setting('google_ads_lead_label'));
		$email = (string) get_post_meta($lead_id, '_contact_email', true);
		$digits = preg_replace('/\D/', '', (string) get_post_meta($lead_id, '_contact_phone', true));
		$phone = 10 === strlen($digits) ? '+1' . $digits : (11 === strlen($digits) && '1' === $digits[0] ? '+' . $digits : '');
		?>
		<script>
			window.addEventListener('load', function () {
				window.dataLayer = window.dataLayer || [];
				window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
				window.gtag('set', 'user_data', <?php echo wp_json_encode(array_filter(array('email' => $email, 'phone_number' => $phone))); ?>);
				<?php if ($label) : ?>
				window.gtag('event', 'conversion', {
					send_to: <?php echo wp_json_encode('AW-18454059893/' . $label); ?>,
					transaction_id: <?php echo wp_json_encode('lead-' . $lead_id); ?>
				});
				<?php endif; ?>
				window.gtag('event', 'generate_lead', { lead_source: 'channel_letters_ads' });
			});
		</script>
		<?php
	}, 21);
}

$styles = new WP_Query(array(
	'post_type' => 'product',
	'post_status' => 'publish',
	'posts_per_page' => 8,
	'meta_key' => '_order_by_index',
	'orderby' => 'meta_value_num',
	'order' => 'ASC',
	'tax_query' => array(
		array(
			'taxonomy' => 'product_category',
			'field' => 'slug',
			'terms' => 'channel-letters',
		),
	),
	'meta_query' => array(
		array(
			'key' => '_show_in_list',
			'value' => 'on',
			'compare' => '=',
		),
	),
));
$style_names = wp_list_pluck($styles->posts, 'post_title');

$reviews = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;
$rating = $reviews && !empty($reviews['rating']) ? (float) $reviews['rating'] : 0;
$rating_count = $reviews && !empty($reviews['count']) ? (int) $reviews['count'] : 0;

$logo_id = get_theme_mod('custom_logo');
$privacy_url = get_privacy_policy_url();

if (!function_exists('wholesale_cla_icon')) {
	function wholesale_cla_icon($name)
	{
		$paths = array(
			'flag' => '<path d="M4 22V4"/><path d="M4 4h13l-2 4 2 4H4"/>',
			'badge' => '<path d="M12 2l2.4 1.8 3-.2 1 2.8 2.6 1.6-.8 2.9.8 2.9-2.6 1.6-1 2.8-3-.2L12 22l-2.4-1.8-3 .2-1-2.8L3 16l.8-2.9L3 10.2l2.6-1.6 1-2.8 3 .2z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
			'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
			'plug' => '<path d="M9 2v6M15 2v6"/><path d="M6 8h12v3a6 6 0 0 1-12 0z"/><path d="M12 17v5"/>',
			'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
			'pen' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
			'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
			'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 6L2 7"/>',
			'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
			'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
			'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'check' => '<path d="M20 6L9 17l-5-5"/>',
			'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/>',
			'truck' => '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
			'star' => '<path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
		);

		if (!isset($paths[$name])) {
			return '';
		}

		return '<svg class="cla-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
	}
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preload" as="image" href="<?php echo esc_url(get_template_directory_uri() . '/img/hero-1920.webp'); ?>" type="image/webp" fetchpriority="high">
	<?php wp_head(); ?>
</head>

<body <?php body_class('cla-body'); ?>>
	<?php wp_body_open(); ?>

	<header class="cla-topbar">
		<div class="cla-shell cla-topbar-inner">
			<a class="cla-brand" href="<?php echo esc_url(home_url('/')); ?>">
				<?php
				if ($logo_id) {
					echo wp_get_attachment_image($logo_id, 'full', false, array('alt' => get_bloginfo('name'), 'class' => 'cla-logo', 'loading' => 'eager', 'decoding' => 'async', 'sizes' => '240px'));
				} else {
					echo '<img src="' . esc_url(get_template_directory_uri() . '/img/logo.png') . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="cla-logo" width="2417" height="261" loading="eager" decoding="async">';
				}
				?>
			</a>
			<div class="cla-topbar-contact">
				<span class="cla-topbar-hours"><?php echo wholesale_cla_icon('clock'); ?> Mon&ndash;Fri 8am&ndash;5pm PST</span>
				<a class="cla-topbar-phone" href="<?php echo esc_attr($phone_link); ?>"><?php echo wholesale_cla_icon('phone'); ?><span><small>Talk to a sign specialist</small><?php echo esc_html($phone_display); ?></span></a>
			</div>
		</div>
	</header>

	<main id="primary" class="cla-page">
		<section class="cla-hero" aria-labelledby="cla-title">
			<div class="cla-shell cla-hero-inner">
				<div class="cla-hero-copy">
					<p class="cla-eyebrow">Made in USA &middot; UL listed &middot; 5-year LED warranty</p>
					<h1 id="cla-title"><?php echo wp_kses_post($variant['title']); ?> <span><?php echo wp_kses_post($variant['accent']); ?></span></h1>
					<p class="cla-lead"><?php echo esc_html($variant['lead']); ?></p>
					<ul class="cla-hero-points">
						<li><?php echo wholesale_cla_icon('check'); ?> Free quote &amp; design help from a real person</li>
						<li><?php echo wholesale_cla_icon('check'); ?> See your exact price online before you order</li>
						<li><?php echo wholesale_cla_icon('check'); ?> Ships ready to install, with wiring diagram &amp; pattern</li>
					</ul>
					<div class="cla-hero-actions">
						<a class="cla-button cla-button--primary" href="#quote">Get my free quote <?php echo wholesale_cla_icon('arrow'); ?></a>
						<a class="cla-button cla-button--ghost" href="<?php echo esc_url($builder_url); ?>"><?php echo wholesale_cla_icon('pen'); ?> Design &amp; price online</a>
					</div>
					<?php if ($rating > 0) : ?>
						<p class="cla-hero-rating">
							<span class="cla-stars" aria-hidden="true"><?php echo str_repeat(wholesale_cla_icon('star'), 5); ?></span>
							<strong><?php echo esc_html(number_format($rating, 1)); ?></strong> on Google
							<?php if ($rating_count) : ?>&middot; <?php echo esc_html(sprintf(_n('%s review', '%s reviews', $rating_count, 'litsign'), number_format_i18n($rating_count))); ?><?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section class="cla-quote-wrap" id="quote" aria-labelledby="cla-quote-title">
			<div class="cla-shell">
				<div class="cla-quote-card">
					<?php if ($lead_valid) : ?>
						<div class="cla-quote-success" role="status">
							<span class="cla-success-icon"><?php echo wholesale_cla_icon('check'); ?></span>
							<h2 id="cla-quote-title">Thanks! Your quote request is in.</h2>
							<p>A sign specialist will review your project and reach out with pricing and design options. Need it sooner? Call us during business hours.</p>
							<div class="cla-success-actions">
								<a class="cla-button cla-button--primary" href="<?php echo esc_attr($phone_link); ?>"><?php echo wholesale_cla_icon('phone'); ?> Call <?php echo esc_html($phone_display); ?></a>
								<a class="cla-button cla-button--outline" href="<?php echo esc_url($builder_url); ?>"><?php echo wholesale_cla_icon('pen'); ?> Design your sign online</a>
							</div>
						</div>
					<?php else : ?>
						<div class="cla-quote-intro">
							<p class="cla-kicker">Free, no obligation</p>
							<h2 id="cla-quote-title">Get your channel letter quote</h2>
							<p>Tell us what your sign should say. Add your logo if you have one and we&rsquo;ll price the exact letters you need.</p>
							<ul class="cla-quote-promise">
								<li><?php echo wholesale_cla_icon('check'); ?> No hidden charges</li>
								<li><?php echo wholesale_cla_icon('check'); ?> Free design help</li>
								<li><?php echo wholesale_cla_icon('check'); ?> Your details stay private</li>
							</ul>
						</div>

						<form class="cla-form" action="<?php echo esc_url($page_url); ?>" method="post" enctype="multipart/form-data" data-cla-form>
							<?php if ('error' === $quote_status) : ?>
								<p class="cla-form-alert" role="alert">We couldn&rsquo;t send your request. Please check your name, email and phone, then try again.</p>
							<?php elseif ('file_error' === $quote_status) : ?>
								<p class="cla-form-alert" role="alert">Your logo couldn&rsquo;t be uploaded. Please use a JPG, PNG or PDF under <?php echo esc_html($logo_max_mb); ?>&nbsp;MB, or send the form without it.</p>
							<?php endif; ?>

							<input type="hidden" name="cla_submit" value="1">
							<input type="hidden" name="cla_nonce" value="<?php echo esc_attr(wp_create_nonce('cla_quote')); ?>">
							<input type="hidden" name="cla_variant" value="<?php echo esc_attr($variant_key); ?>">
							<input type="hidden" name="cla_gclid" value="<?php echo esc_attr($gclid); ?>">
							<input type="hidden" name="cla_utm_campaign" value="<?php echo esc_attr(isset($_GET['utm_campaign']) ? sanitize_text_field(wp_unslash($_GET['utm_campaign'])) : ''); ?>">
							<input type="hidden" name="cla_utm_term" value="<?php echo esc_attr(isset($_GET['utm_term']) ? sanitize_text_field(wp_unslash($_GET['utm_term'])) : ''); ?>">
							<div class="cla-hp" aria-hidden="true"><label>Website <input type="text" name="cla_website" tabindex="-1" autocomplete="off"></label></div>

							<div class="cla-form-grid">
								<label class="cla-field">
									<span>Your name <b aria-hidden="true">*</b></span>
									<input type="text" name="cla_name" autocomplete="name" required>
								</label>
								<label class="cla-field">
									<span>Business name</span>
									<input type="text" name="cla_business" autocomplete="organization">
								</label>
								<label class="cla-field">
									<span>Phone <b aria-hidden="true">*</b></span>
									<input type="tel" name="cla_phone" autocomplete="tel" inputmode="tel" required>
								</label>
								<label class="cla-field">
									<span>Email <b aria-hidden="true">*</b></span>
									<input type="email" name="cla_email" autocomplete="email" required>
								</label>
								<label class="cla-field">
									<span>Letter style</span>
									<select name="cla_style">
										<option value="">Not sure &ndash; help me choose</option>
										<?php foreach ($style_names as $style_name) : ?>
											<option><?php echo esc_html($style_name); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label class="cla-field">
									<span>Install ZIP code</span>
									<input type="text" name="cla_zip" autocomplete="postal-code" inputmode="numeric" maxlength="10">
								</label>
								<label class="cla-field cla-field--wide">
									<span>What should your sign say? Size, colors, anything else</span>
									<textarea name="cla_details" rows="3" placeholder="e.g. &ldquo;BELLA NAILS&rdquo;, about 12 ft wide, red letters, mounted on a raceway"></textarea>
								</label>
								<label class="cla-upload cla-field--wide">
									<input type="file" name="cla_logo" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" data-cla-file>
									<span class="cla-upload-icon"><?php echo wholesale_cla_icon('upload'); ?></span>
									<span class="cla-upload-text"><strong data-cla-file-name>Upload your logo (optional)</strong><small>JPG, PNG or PDF up to <?php echo esc_html($logo_max_mb); ?>&nbsp;MB</small></span>
								</label>
							</div>

							<button class="cla-button cla-button--primary cla-submit" type="submit">Get my free quote <?php echo wholesale_cla_icon('arrow'); ?></button>
							<p class="cla-form-note">Prefer to talk? Call <a href="<?php echo esc_attr($phone_link); ?>"><?php echo esc_html($phone_display); ?></a> or text <a href="<?php echo esc_attr($text_link); ?>"><?php echo esc_html($text_display); ?></a>.</p>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section class="cla-trust" aria-label="Why customers choose us">
			<div class="cla-shell cla-trust-grid">
				<div><?php echo wholesale_cla_icon('flag'); ?><span><strong>Made in USA</strong><small>Built to order for your business</small></span></div>
				<div><?php echo wholesale_cla_icon('badge'); ?><span><strong>UL Listed</strong><small>Outdoor signs ship with UL labels</small></span></div>
				<div><?php echo wholesale_cla_icon('shield'); ?><span><strong>5-Year LED Warranty</strong><small>On listed LED modules &amp; power supplies</small></span></div>
				<div><?php echo wholesale_cla_icon('plug'); ?><span><strong>Tested Before Shipping</strong><small>Arrives ready to install</small></span></div>
			</div>
		</section>

		<?php if ($styles->have_posts()) : ?>
			<section class="cla-section" id="styles" aria-labelledby="cla-styles-title">
				<div class="cla-shell">
					<header class="cla-heading">
						<p class="cla-kicker">Choose your look</p>
						<h2 id="cla-styles-title">Channel Letter Styles &amp; Starting Prices</h2>
						<p>Every style is custom made to your wording, font, colors and size. Pick one to see your exact price, or ask us to recommend one.</p>
					</header>
					<div class="cla-style-grid">
						<?php while ($styles->have_posts()) : $styles->the_post(); ?>
							<?php $start_price = get_post_meta(get_the_ID(), '_starting_at_text', true); ?>
							<article class="cla-style-card">
								<a class="cla-style-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
									<?php if (has_post_thumbnail()) : ?>
										<?php the_post_thumbnail('medium_large', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => '')); ?>
									<?php endif; ?>
								</a>
								<div class="cla-style-body">
									<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
									<?php if ($start_price) : ?>
										<p class="cla-style-price"><small>Starting at</small> <?php echo wp_kses_post($start_price); ?></p>
									<?php endif; ?>
									<a class="cla-style-link" href="<?php echo esc_url(add_query_arg('product_id', get_the_ID(), $builder_url)); ?>">Design &amp; price <?php echo wholesale_cla_icon('arrow'); ?></a>
								</div>
							</article>
						<?php endwhile; ?>
						<?php wp_reset_postdata(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="cla-section cla-section--tint" aria-labelledby="cla-steps-title">
			<div class="cla-shell">
				<header class="cla-heading">
					<p class="cla-kicker">Simple from start to finish</p>
					<h2 id="cla-steps-title">How It Works</h2>
				</header>
				<ol class="cla-steps">
					<li><b>1</b><h3>Tell us about your sign</h3><p>Send the quote form with your logo, or build your letters online and see the price instantly.</p></li>
					<li><b>2</b><h3>Approve your design</h3><p>A sign specialist confirms sizes, colors and lighting with you before anything is made.</p></li>
					<li><b>3</b><h3>We build &amp; test it</h3><p>Your letters are made to order in the USA and lit up and tested before they leave.</p></li>
					<li><b>4</b><h3>Install &amp; shine</h3><p>Your sign arrives ready to install, with a wiring diagram and installation pattern.</p></li>
				</ol>
			</div>
		</section>

		<section class="cla-section cla-dark" aria-labelledby="cla-why-title">
			<div class="cla-shell cla-why">
				<div class="cla-why-copy">
					<p class="cla-kicker">Built to last</p>
					<h2 id="cla-why-title">Commercial-Grade Signs Your Customers Will Notice</h2>
					<p>Your sign works for you 24 hours a day. We build channel letters with bright, energy-efficient LEDs and durable materials so your storefront looks sharp for years.</p>
					<a class="cla-button cla-button--primary" href="#quote">Get my free quote <?php echo wholesale_cla_icon('arrow'); ?></a>
				</div>
				<ul class="cla-why-list">
					<li><?php echo wholesale_cla_icon('badge'); ?><span><strong>UL listed</strong> outdoor channel letter signs with sign section labels.</span></li>
					<li><?php echo wholesale_cla_icon('shield'); ?><span><strong>5-year warranty</strong> on listed LED modules, power supplies and qualifying letters.</span></li>
					<li><?php echo wholesale_cla_icon('flag'); ?><span><strong>Made in USA</strong> with .040 aluminum or welded stainless steel returns and acrylic faces.</span></li>
					<li><?php echo wholesale_cla_icon('truck'); ?><span><strong>Fast shipping options</strong>: standard, 3-day, 2-day or overnight after manufacturing.</span></li>
				</ul>
			</div>
		</section>

		<?php if (function_exists('wholesale_render_review_slider')) : ?>
			<div class="cla-reviews"><?php wholesale_render_review_slider(); ?></div>
		<?php endif; ?>

		<section class="cla-section" aria-labelledby="cla-faq-title">
			<div class="cla-shell cla-faq-layout">
				<header class="cla-heading cla-heading--left">
					<p class="cla-kicker">Before you order</p>
					<h2 id="cla-faq-title">Frequently Asked Questions</h2>
					<p>Still have a question? Call <a href="<?php echo esc_attr($phone_link); ?>"><?php echo esc_html($phone_display); ?></a>, Mon&ndash;Fri 8am&ndash;5pm PST.</p>
				</header>
				<div class="cla-faq">
					<details open>
						<summary>How much do channel letters cost?</summary>
						<p>Price depends on the style, letter height, number of letters and lighting. Each style above shows its starting price, and our online builder shows your full price as you design. For logos or large projects, send the quote form and we&rsquo;ll price it for you.</p>
					</details>
					<details>
						<summary>Which channel letter style is right for my storefront?</summary>
						<p>Front lit letters give bold, direct light. Back lit and halo lit letters glow onto the wall behind them for a softer, upscale look. Front &amp; back lit letters combine both. Not sure? Leave &ldquo;help me choose&rdquo; selected and we&rsquo;ll recommend one.</p>
					</details>
					<details>
						<summary>How long will it take to get my sign?</summary>
						<p>Every sign is made to order. Your estimated ship date is shown at checkout, and after manufacturing you can choose standard (3&ndash;6 business days), 3-day, 2-day or overnight shipping.</p>
					</details>
					<details>
						<summary>Is my sign ready to install when it arrives?</summary>
						<p>Yes. Every sign is tested before shipping and includes a wiring diagram and an installation pattern for your installer.</p>
					</details>
					<details>
						<summary>What warranty do I get?</summary>
						<p>Outdoor channel letter signs are UL listed with sign section labels. Listed LED modules, power supplies and qualifying letters carry a five-year warranty.</p>
					</details>
					<details>
						<summary>Can I pick up my order?</summary>
						<p>Pickup isn&rsquo;t available. Every order ships directly to you.</p>
					</details>
				</div>
			</div>
		</section>

		<section class="cla-final" aria-labelledby="cla-final-title">
			<div class="cla-shell cla-final-inner">
				<div>
					<h2 id="cla-final-title">Ready to light up your storefront?</h2>
					<p>Get a free quote today, or talk to a sign specialist now.</p>
				</div>
				<div class="cla-final-actions">
					<a class="cla-button cla-button--primary" href="#quote">Get my free quote <?php echo wholesale_cla_icon('arrow'); ?></a>
					<a class="cla-button cla-button--ghost" href="<?php echo esc_attr($phone_link); ?>"><?php echo wholesale_cla_icon('phone'); ?> <?php echo esc_html($phone_display); ?></a>
				</div>
			</div>
		</section>
	</main>

	<footer class="cla-footer">
		<div class="cla-shell cla-footer-inner">
			<div>
				<strong><?php echo esc_html(wholesale_get_setting('ticket_business_name')); ?></strong>
				<p><?php echo wholesale_cla_icon('pin'); ?> 707 S. Grady Way, Suite 600, Renton, WA 98057</p>
			</div>
			<div>
				<p><?php echo wholesale_cla_icon('phone'); ?> <a href="<?php echo esc_attr($phone_link); ?>"><?php echo esc_html($phone_display); ?></a></p>
				<p><?php echo wholesale_cla_icon('mail'); ?> <a href="mailto:<?php echo esc_attr($email_address); ?>"><?php echo esc_html($email_address); ?></a></p>
			</div>
			<nav class="cla-footer-links" aria-label="Legal">
				<a href="<?php echo esc_url(home_url('/')); ?>">Shop all signs</a>
				<a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>">Terms &amp; Conditions</a>
				<?php if ($privacy_url) : ?>
					<a href="<?php echo esc_url($privacy_url); ?>">Privacy Policy</a>
				<?php endif; ?>
			</nav>
		</div>
		<p class="cla-copy">&copy; <?php echo esc_html(wp_date('Y')); ?> Storefrontsignonline, Inc. All rights reserved.</p>
	</footer>

	<nav class="cla-mobile-bar" aria-label="Quick actions">
		<a class="cla-mobile-call" href="<?php echo esc_attr($phone_link); ?>"><?php echo wholesale_cla_icon('phone'); ?> Call us</a>
		<a class="cla-mobile-quote" href="#quote">Free quote</a>
	</nav>

	<script>
		(function () {
			var form = document.querySelector('[data-cla-form]');
			if (!form) {
				return;
			}
			var file = form.querySelector('[data-cla-file]');
			var fileName = form.querySelector('[data-cla-file-name]');
			if (file && fileName) {
				file.addEventListener('change', function () {
					fileName.textContent = file.files.length ? file.files[0].name : 'Upload your logo (optional)';
				});
			}
			form.addEventListener('submit', function () {
				var button = form.querySelector('.cla-submit');
				if (button) {
					button.disabled = true;
					button.textContent = 'Sending...';
				}
			});
		})();
	</script>

	<?php wp_footer(); ?>
</body>

</html>
