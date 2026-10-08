<?php
/**
 * Warranty page (/warranty/).
 *
 * Restates the five-year warranty already published on channel letter
 * product pages (template-parts/cl-content-1.php, Warranty tab) in plain
 * language. Change the terms there and here together; add nothing the owner
 * hasn't confirmed.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$install_url = wholesale_guide_url('how-to-install-channel-letters');
$shipping_url = wholesale_guide_url('shipping-returns');
$terms_page = get_page_by_path('terms-conditions');
$terms_url = $terms_page ? get_permalink($terms_page) : home_url('/terms-conditions/');
$ul_number = wholesale_guide_ul_file_number();

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'How long is the channel letter warranty?',
		'a' => 'Five years from the date your sign ships, against defects in materials and workmanship.',
	),
	array(
		'q' => 'What do you do if a covered part fails?',
		'a' => 'At our option we repair the product at no charge for parts or phone and email support, or replace it with an equivalent product, which may be new or refurbished.',
	),
	array(
		'q' => 'What voids the warranty?',
		'a' => 'Repairing or working on the sign before you notify us, and damage from improper use, installation or care, accidents, negligence, vandalism, misuse or acts of nature.',
	),
	array(
		'q' => 'Is shipping damage covered by the warranty?',
		'a' => 'Shipping damage is handled through the carrier. Inspect the packaging before you sign for delivery; if it&rsquo;s damaged, inspect the contents, file a claim with the carrier and let us know right away by email or phone.',
	),
	array(
		'q' => 'How do I make a warranty claim?',
		'a' => 'Email <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a> or call <a href="tel:+18664362101">866-436-2101</a> with your order number, a description of the problem and photos or a short video, before anyone works on the sign.',
	),
));

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Warranty</p>
			<h1 id="gd-title" class="sf-hero-title">Our 5-Year Channel Letter Warranty</h1>
			<p class="sf-hero-lead">Every lit sign is tested before it ships, and our channel letters are backed for five years against defects in materials and workmanship. Here&rsquo;s what that means, in plain language.</p>
			<ul class="gd-facts">
				<li><strong>Length</strong><span>5 years from the date your sign ships</span></li>
				<li><strong>Covers</strong><span>Defects in materials and workmanship</span></li>
				<li><strong>Remedy</strong><span>Repair or replacement, at our option</span></li>
				<li><strong>First step</strong><span>Contact us before any repair work</span></li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-covered-title">
		<div class="container sf-prose">
			<p class="cl-kicker">The terms</p>
			<h2 id="gd-covered-title" class="cl-section-title">What&rsquo;s Covered</h2>
			<p>Storefront Sign Online LLC warrants its front lit and front &amp; back lit channel letters and lit logo shapes to be free from defects in materials and workmanship for five years from the date of shipment. The LED modules and standard power supplies used in our letters each carry their own five-year warranty, as listed in the sign builder.</p>
			<p>If a covered product proves defective during the warranty period, we will, at our option:</p>
			<ul class="gd-checklist">
				<li>repair the product, at no charge for parts or for phone and email support, or</li>
				<li>replace it with an equivalent product, which may be new or refurbished.</li>
			</ul>
			<p>Repair or replacement is the sole remedy under this warranty, and it is given in place of any other warranty, express or implied. The full wording is in the Warranty tab on each channel letter product page and in our <a href="<?php echo esc_url($terms_url); ?>">Terms &amp; Conditions</a>.</p>

			<h2 class="cl-section-title">What&rsquo;s Not Covered</h2>
			<ul class="gd-checklist">
				<li>Any work or repair on the sign made before notifying us. This voids the warranty.</li>
				<li>Damage caused by improper use, installation or care.</li>
				<li>Accidental damage, negligence, vandalism or misuse.</li>
				<li>Damage from acts of nature (acts of God).</li>
				<li>Damage in transit: this is the carrier&rsquo;s responsibility (see below).</li>
			</ul>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-claim-title">
		<div class="container sf-prose">
			<p class="cl-kicker">If something goes wrong</p>
			<h2 id="gd-claim-title" class="cl-section-title">How to Make a Warranty Claim</h2>
			<ol class="gd-steps">
				<li>
					<h3>Stop and contact us first</h3>
					<p>Don&rsquo;t open, rewire or repair the sign yet. Call <a href="tel:+18664362101">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PST) or email <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a>.</p>
				</li>
				<li>
					<h3>Send your order number and photos</h3>
					<p>Tell us which letters or parts are affected and send photos or a short video, day and night if the problem is with the lighting.</p>
				</li>
				<li>
					<h3>We diagnose it with you or your electrician</h3>
					<p>Many problems are a loose connection we can solve by phone with your electrician, using the wiring diagram.</p>
				</li>
				<li>
					<h3>Repair or replacement</h3>
					<p>If the problem is a covered defect, we repair or replace the part or product as described above.</p>
				</li>
			</ol>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-ship-title">
		<div class="container sf-prose">
			<p class="cl-kicker">On delivery</p>
			<h2 id="gd-ship-title" class="cl-section-title">Shipping Damage</h2>
			<p>We test and inspect every sign before shipment and pack it for common-carrier shipping. Damage in transit is the carrier&rsquo;s responsibility, so:</p>
			<ul class="gd-checklist">
				<li>inspect the packaging before you sign for the delivery;</li>
				<li>if the packaging is damaged, inspect the contents before signing;</li>
				<li>if anything is damaged, file a claim with the carrier and notify us by email or phone right away.</li>
			</ul>
			<?php if ($shipping_url) : ?>
				<p>More on delivery, cancellations and reprints: <a href="<?php echo esc_url($shipping_url); ?>">Shipping &amp; Returns</a>.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-quality-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Built to last</p>
			<h2 id="gd-quality-title" class="cl-section-title">How We Help Your Sign Last</h2>
			<p>Outdoor channel letter signs are UL listed and ship with their labels, and every lit sign is tested before it ships.<?php if ($ul_number) : ?> Our UL file number is <strong><?php echo esc_html($ul_number); ?></strong>.<?php endif; ?> A correct installation matters just as much: have a licensed professional install your sign using the included wiring diagram and install pattern<?php if ($install_url) : ?>, and see our <a href="<?php echo esc_url($install_url); ?>">installation overview</a><?php endif; ?>.</p>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="gd-faq-title">
		<div class="container">
			<p class="cl-kicker">Questions</p>
			<h2 id="gd-faq-title" class="cl-section-title">Warranty FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php wholesale_guide_render_help('Need Help With Your Sign?', 'Call or email with your order number and photos. We&rsquo;ll work through it with you or your electrician.'); ?>
</main>

<?php
get_footer();
