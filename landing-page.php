<?php
/**
 * Template Name: Sign Company Landing Page
 * Template Post Type: page
 *
 * A conversion-focused landing page for custom sign services.
 */

$theme_uri = get_template_directory_uri();
$contact_url = get_page_by_path('contact') ? get_permalink(get_page_by_path('contact')) : home_url('/contact/');
$landing_url = get_permalink();
$logo_id = get_theme_mod('custom_logo');
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : $theme_uri . '/img/logo.png';

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['landing_quote_submit'])) {
	$redirect_url = $landing_url ? $landing_url : home_url('/');

	if (!isset($_POST['landing_quote_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['landing_quote_nonce'])), 'landing_quote')) {
		wp_safe_redirect(add_query_arg('quote_status', 'error', $redirect_url) . '#quote');
		exit;
	}

	$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
	$business = isset($_POST['business']) ? sanitize_text_field(wp_unslash($_POST['business'])) : '';
	$phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
	$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
	$sign_type = isset($_POST['sign_type']) ? sanitize_text_field(wp_unslash($_POST['sign_type'])) : '';

	if (!$name || !$business || !$phone || !$sign_type || !is_email($email)) {
		wp_safe_redirect(add_query_arg('quote_status', 'error', $redirect_url) . '#quote');
		exit;
	}

	$subject = sprintf('New sign quote request from %s', $name);
	$message = "Name: {$name}\n"
		. "Business: {$business}\n"
		. "Phone: {$phone}\n"
		. "Email: {$email}\n"
		. "Sign type: {$sign_type}\n";
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	// Saved like the other quote forms, so the team can reply from Contact Submissions.
	$submission_id = wp_insert_post(array(
		'post_type' => 'contact_submission',
		'post_status' => 'publish',
		'post_title' => sprintf('%s - %s', $name, current_time('Y-m-d H:i')),
		'post_content' => '',
		'meta_input' => array(
			'_contact_name' => $name,
			'_contact_business' => $business,
			'_contact_phone' => $phone,
			'_contact_email' => $email,
			'_contact_project_type' => $sign_type,
		),
	), true);
	if (is_wp_error($submission_id)) {
		wp_safe_redirect(add_query_arg('quote_status', 'error', $redirect_url) . '#quote');
		exit;
	}

	list($subject, $message) = wholesale_contact_mail_tag($subject, $message);
	wp_mail(wholesale_contact_admin_recipients(), $subject, $message, $headers);
	wholesale_send_quote_confirmation($submission_id);
	wp_safe_redirect(add_query_arg(wholesale_quote_lead_args($submission_id), $redirect_url) . '#quote');
	exit;
}

$solutions = array(
	array('Channel Letters', 'Illuminated channel letters for maximum visibility day and night.'),
	array('Light Box Signs', 'Bright, energy-efficient signs that grab attention.'),
	array('LED Signs', 'LED sign builds to last with vibrant illumination.'),
	array('Dimensional Letters', '3D letters that add a professional look to your business.'),
	array('Monument Signs', 'Make a strong first impression with monument signs.'),
	array('Window Graphics', 'Custom window graphics for promotions and branding.'),
	array('Banners', 'High quality banners for any event or promotion.'),
	array('Vehicle Graphics', 'Turn your vehicle into a powerful marketing tool.'),
);

$landing_products = new WP_Query(array(
	'post_type' => 'product',
	'post_status' => 'publish',
	'posts_per_page' => 8,
	'meta_key' => '_order_by_index',
	'orderby' => 'meta_value_num',
	'order' => 'ASC',
	'meta_query' => array(
		array(
			'key' => '_show_in_list',
			'value' => 'on',
			'compare' => '=',
		),
	),
));

wholesale_track_quote_lead('landing_page');

get_header();
?>

<main class="landing-page">
	<section class="landing-hero">
		<div class="landing-shell container landing-hero-inner">
			<div class="landing-hero-copy">
				<p class="landing-eyebrow">Professional signage. Delivered nationwide.</p>
				<h1>Custom signs<br>built for your <span>business success</span></h1>
				<p class="landing-lead">High quality custom signs, fast turnaround and nationwide shipping.</p>
				<div class="landing-actions">
					<a class="landing-button" href="#quote">Get a free quote <span aria-hidden="true">&rarr;</span></a>
					<a class="landing-button landing-button-outline" href="#solutions">View our work</a>
				</div>
				<div class="landing-benefits">
					<span><b>+</b> Premium quality materials</span>
					<span><b>+</b> Fast production &amp; delivery</span>
					<span><b>+</b> Wiring diagram &amp; install pattern included</span>
				</div>
			</div>
		</div>
		<form class="landing-quote-card" id="quote" action="<?php echo esc_url($landing_url ? $landing_url : home_url('/')); ?>" method="post">
			<h2>Get your <span>free</span> custom sign quote</h2>
			<?php wp_nonce_field('landing_quote', 'landing_quote_nonce'); ?>
			<?php if (isset($_GET['quote_status']) && 'sent' === sanitize_key(wp_unslash($_GET['quote_status']))) : ?>
				<p class="landing-quote-message landing-quote-message-success" role="status">Thanks! Your quote request has been sent. We will be in touch shortly.</p>
			<?php elseif (isset($_GET['quote_status']) && 'error' === sanitize_key(wp_unslash($_GET['quote_status']))) : ?>
				<p class="landing-quote-message landing-quote-message-error" role="alert">We could not send your request. Please check your details and try again.</p>
			<?php endif; ?>
			<div class="landing-quote-fields">
				<label><span class="screen-reader-text">Your name</span><input type="text" name="name" placeholder="Your Name*" required></label>
				<label><span class="screen-reader-text">Business name</span><input type="text" name="business" placeholder="Business Name*" required></label>
				<label><span class="screen-reader-text">Phone number</span><input type="tel" name="phone" placeholder="Phone Number*" required></label>
				<label><span class="screen-reader-text">Email address</span><input type="email" name="email" placeholder="Email Address*" required></label>
				<label><span class="screen-reader-text">Sign type</span><select name="sign_type" required><option value="">Select Sign Type*</option><?php if ($landing_products->have_posts()) : while ($landing_products->have_posts()) : $landing_products->the_post(); ?><option><?php echo esc_html(get_the_title()); ?></option><?php endwhile; wp_reset_postdata(); else : foreach ($solutions as $solution) : ?><option><?php echo esc_html($solution[0]); ?></option><?php endforeach; endif; ?></select></label>
				<button class="landing-button" type="submit" name="landing_quote_submit" value="1">Get free quote</button>
			</div>
			<div class="landing-quote-points"><span>Free design support</span><span>No hidden charges</span><span>Quick response</span><span>100% satisfaction guarantee</span></div>
		</form>
	</section>

	<section class="landing-section" id="solutions">
		<div class="landing-shell container">
			<header class="landing-section-heading"><p class="landing-eyebrow">Stand out from the crowd</p><h2>Our <span>premium</span> sign solutions</h2><p>High quality custom signs that help your business stand out and grow.</p></header>
			<p class="text-center"><a href="<?php echo esc_url(wholesale_category_url('channel-letters')); ?>">Explore custom channel letter signs for your storefront</a></p>
			<div class="landing-solution-grid product-box-container">
				<?php if ($landing_products->have_posts()) : $index = 0; while ($landing_products->have_posts()) : $landing_products->the_post(); ?>
					<article class="product-box landing-solution-card">
						<a href="<?php the_permalink(); ?>">
							<div class="pb-image-top">
								<?php if (has_post_thumbnail()) : ?>
									<?php the_post_thumbnail('medium_large', array('loading' => $index === 0 ? 'eager' : 'lazy', 'fetchpriority' => $index === 0 ? 'high' : 'auto', 'decoding' => 'async')); ?>
								<?php endif; ?>
							</div>
							<div class="pb-details">
								<h4 class="pb-title text-truncate" title="<?php echo esc_attr(get_the_title()); ?>"><?php the_title(); ?></h4>
								<div class="pb-description-list"><?php echo wp_kses_post(get_post_meta(get_the_ID(), '_product_list_desc', true) ?: wp_trim_words(get_the_excerpt(), 15)); ?></div>
								<hr>
								<div class="start-at-pricing">
									<span class="pb-title-short">Starting at</span>
									<span class="pb-price"><?php echo wp_kses_post(get_post_meta(get_the_ID(), '_starting_at_text', true)); ?></span>
								</div>
							</div>
						</a>
					</article>
				<?php $index++; endwhile; wp_reset_postdata(); else : ?>
					<p class="landing-products-empty">No sign products are currently available.</p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="landing-dark-section">
		<div class="landing-shell container landing-stats-layout">
			<div><p class="landing-eyebrow">Why choose StorefrontSignOnline?</p><h2>Signs that work as hard as you do.</h2><p>We are a sign company committed to providing high-quality signage solutions with exceptional customer service.</p><ul><li>Over 15 years of industry experience</li><li>Advanced technology &amp; premium materials</li><li>Fast turnaround &amp; on-time delivery</li><li>Nationwide shipping</li><li>Unlimited design support</li></ul><a class="landing-button" href="<?php echo esc_url($contact_url); ?>">About our company <span aria-hidden="true">&rarr;</span></a></div>
			<div class="landing-stat-grid"><strong>15+<small>Years experience</small></strong><strong>10,000+<small>Signs completed</small></strong><strong>50<small>States served</small></strong><strong>100%<small>Satisfaction</small></strong></div>
		</div>
	</section>

	<section class="landing-section landing-process">
		<div class="landing-shell container"><header class="landing-section-heading"><p class="landing-eyebrow">Simple from start to finish</p><h2>Our <span>simple</span> process</h2><p>From concept to delivery, we make the process easy.</p></header><div class="landing-process-grid"><div><b>01</b><h3>Consultation</h3><p>Tell us about your project and requirements.</p></div><div><b>02</b><h3>Design &amp; approval</h3><p>We create the design and get your approval.</p></div><div><b>03</b><h3>Production</h3><p>We build your sign with quality materials.</p></div><div><b>04</b><h3>Testing &amp; delivery</h3><p>We test your sign and ship it with a wiring diagram and install pattern for your licensed installer.</p></div></div></div>
	</section>

	<section class="landing-cta"><div class="landing-shell container"><div><p class="landing-eyebrow">Ready to grow your business?</p><h2>Let your sign do the talking.</h2><p>Let's create a sign that gets you noticed and brings more customers to your business.</p></div><a class="landing-button" href="#quote">Get a free quote now <span aria-hidden="true">&rarr;</span></a></div></section>
</main>

<?php get_footer(); ?>
