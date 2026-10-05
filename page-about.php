<?php
/**
 * About page (/about/).
 *
 * Uses only facts published elsewhere on the site. The company's own story
 * (founding year, founder, team) can be added in the page editor; any content
 * saved there is shown under "Our story".
 *
 * @package litsign
 */

$business = get_bloginfo('name');
$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$storefront_signs_url = function_exists('wholesale_seo_storefront_signs_url') ? wholesale_seo_storefront_signs_url() : '';
$story = trim((string) get_post_field('post_content', get_queried_object_id()));

get_header();
?>

<main id="primary" class="sf-page">
	<section class="sf-hero" aria-labelledby="about-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">About us</p>
			<h1 id="about-title" class="sf-hero-title"><?php echo esc_html(sprintf('About %s', $business)); ?></h1>
			<p class="sf-hero-lead"><?php echo esc_html($business); ?> is an online sign company in Renton, Washington, operated by Storefront Sign Online LLC, which has also done business as Lit Sign Manufacturing since 2002. We make custom LED channel letters and storefront signs, plus banners, flags, trade show displays, adhesive vinyl and other large format prints, and ship them directly to businesses across the United States.</p>
			<div class="sf-hero-actions">
				<a class="cl-button" href="<?php echo esc_url(home_url('/')); ?>">Shop Channel Letters <?php echo wholesale_home_icon('arrow'); ?></a>
				<a class="sf-button-outline" href="<?php echo esc_url($contact_url); ?>">Contact Us</a>
			</div>
		</div>
	</section>

	<?php if ($story) : ?>
		<section class="sf-section" aria-labelledby="about-story-title">
			<div class="container sf-prose">
				<h2 id="about-story-title" class="cl-section-title">Our Story</h2>
				<?php echo apply_filters('the_content', $story); // Page content saved by the site owner. ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="sf-section<?php echo $story ? ' sf-section--tint' : ''; ?>" aria-labelledby="about-make-title">
		<div class="container sf-prose">
			<p class="cl-kicker">What we make</p>
			<h2 id="about-make-title" class="cl-section-title">Signs for Storefronts, Events and Displays</h2>
			<ul class="sf-facts">
				<li><strong><a href="<?php echo esc_url(home_url('/')); ?>">Channel letters</a></strong>Seven LED lit styles, from front lit to halo lit and trimless, made in the USA and UL listed for outdoor use.</li>
				<li><strong><a href="<?php echo esc_url(wholesale_category_url('large-format')); ?>">Large format prints</a></strong>Printed adhesive vinyl, window graphics, banners, posters, backlit film and wall art, priced per square foot.</li>
				<li><strong><a href="<?php echo esc_url(wholesale_category_url('indoor-outdoor-displays')); ?>">Displays</a></strong>Advertising flags, banner stands, A-frames, SEG and trade show displays, event tents and table throws.</li>
			</ul>
			<?php if ($storefront_signs_url) : ?>
				<p>Not sure which sign you need? Our <a href="<?php echo esc_url($storefront_signs_url); ?>">storefront signs guide</a> compares every type, size and price.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section<?php echo $story ? '' : ' sf-section--tint'; ?>" aria-labelledby="about-how-title">
		<div class="container sf-prose">
			<p class="cl-kicker">How we work</p>
			<h2 id="about-how-title" class="cl-section-title">Priced Online, Made to Order</h2>
			<h3>See your price before you order</h3>
			<p>Every product shows its price online. Choose a channel letter style, enter your wording, letter height and colors, or use the <a href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">online sign builder</a>, and your total updates as you go. For logos and larger projects, <a href="<?php echo esc_url($contact_url); ?>">request a free quote</a>.</p>
			<h3>Built to order and tested</h3>
			<p>Every sign is made to order. Channel letters are tested before they ship and arrive ready to install, with a wiring diagram and installation pattern for your installer. Listed LED modules, power supplies and qualifying letters carry a five-year warranty.</p>
			<h3>Real people to help</h3>
			<p>Our sign specialists answer calls, texts and email Monday to Friday, 8am to 5pm Pacific, and can help you pick a style, size your letters or check your artwork before you order.</p>
		</div>
	</section>

	<section class="sf-section<?php echo $story ? ' sf-section--tint' : ''; ?>" aria-labelledby="about-facts-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Company facts</p>
			<h2 id="about-facts-title" class="cl-section-title">Store Front Sign Online at a Glance</h2>
			<?php // Matches the Organization and LocalBusiness structured data in the page head. ?>
			<ul class="sf-facts">
				<li><strong>Company</strong>Storefront Sign Online LLC, doing business as Store Front Sign Online and Lit Sign Manufacturing</li>
				<li><strong>In business since</strong>2002, founded by Tri Nguyen</li>
				<li><strong>Location</strong>707 S. Grady Way, Suite 600, Renton, WA 98057</li>
				<li><strong>Service area</strong>Washington State and businesses across the United States, shipped directly to you</li>
				<li><strong>Products</strong>LED channel letters, storefront signs, banners, flags, banner stands, trade show displays and large format prints</li>
				<li><strong>Policies</strong><a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>">Terms &amp; Conditions</a><?php if (get_privacy_policy_url()) : ?> and <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">Privacy Policy</a><?php endif; ?></li>
			</ul>
		</div>
	</section>

	<section class="cl-help" aria-labelledby="about-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="about-help-title">Contact <?php echo esc_html($business); ?></h2>
				<p>Orders ship directly to you; pickup isn&rsquo;t available.</p>
				<ul class="cl-help-details">
					<li><?php echo wholesale_home_icon('clock'); ?> Mon&ndash;Fri, 8:00am&ndash;5:00pm PST</li>
					<li><?php echo wholesale_home_icon('pin'); ?> 707 S. Grady Way, Suite 600, Renton, WA 98057</li>
				</ul>
			</div>
			<div class="cl-help-actions">
				<a class="cl-help-action cl-help-action--primary" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?><span><small>Call toll free</small>866-436-2101</span></a>
				<a class="cl-help-action" href="sms:+12066186543"><?php echo wholesale_home_icon('message'); ?><span><small>Text us</small>206-618-6543</span></a>
				<a class="cl-help-action" href="mailto:TR@StorefrontSignOnline.com"><?php echo wholesale_home_icon('mail'); ?><span><small>Email</small>TR@StorefrontSignOnline.com</span></a>
				<a class="cl-help-quote" href="<?php echo esc_url($contact_url); ?>">Request a free quote <?php echo wholesale_home_icon('arrow'); ?></a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
