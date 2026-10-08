<?php
/**
 * Shipping & Returns (/shipping-returns/).
 *
 * Shipping options and prices come from the checkout settings (Settings >
 * Storefront Sign > Checkout defaults), so this page always matches what
 * checkout charges. Cancellation, reprint and claim terms restate the
 * Terms & Conditions page; change both together.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$terms_page = get_page_by_path('terms-conditions');
$terms_url = $terms_page ? get_permalink($terms_page) : home_url('/terms-conditions/');
$warranty_url = wholesale_guide_url('warranty');
$locations_url = wholesale_guide_url('locations');

$rate_list = static function ($setting) {
	return array_values(array_map('floatval', array_filter(explode(',', (string) wholesale_get_setting($setting)), 'is_numeric')));
};
$standard_rates = $rate_list('standard_shipping_options');
$channel_rates = $rate_list('channel_shipping_options');
$money = static function ($rates, $index) {
	return isset($rates[$index]) ? '$' . number_format($rates[$index], 2) : '&ndash;';
};

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'Do you ship to all 50 states?',
		'a' => 'Yes. Every order is made in Renton, Washington and shipped to your address anywhere in the United States. Pickup isn&rsquo;t available.',
	),
	array(
		'q' => 'When will my sign ship?',
		'a' => 'Every sign is made to order. Checkout shows the estimated date for each shipping speed, and those dates include production time. Production starts after your final written proof approval.',
	),
	array(
		'q' => 'Can I cancel my order?',
		'a' => 'Orders can&rsquo;t be stopped or cancelled once they are approved for production, and approved orders are not refundable, because each sign is made for your business.',
	),
	array(
		'q' => 'What if something is wrong with my order?',
		'a' => 'Report it within five business days of receiving your order by email or phone. We&rsquo;ll open a claim, ask for photos, and work out a reprint or repair.',
	),
	array(
		'q' => 'My sign arrived damaged. What do I do?',
		'a' => 'If the box is damaged, inspect the contents before signing for the delivery. If the sign is damaged, file a claim with the carrier and email or call us right away.',
	),
));

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Shipping &amp; returns</p>
			<h1 id="gd-title" class="sf-hero-title">Shipping &amp; Returns</h1>
			<p class="sf-hero-lead">Every sign is made to order in Renton, Washington and shipped to all 50 states. Choose standard, 3-day, 2-day or overnight shipping at checkout; the dates you see there include production time.</p>
			<ul class="gd-facts">
				<li><strong>Ships to</strong><span>All 50 states, to your address (no pickup)</span></li>
				<li><strong>Speeds</strong><span>Standard (3&ndash;6 business days), 3-day, 2-day, overnight</span></li>
				<li><strong>Adhesive products</strong><span>Ordered by 4pm PST ship the next business day</span></li>
				<li><strong>Problems</strong><span>Report within 5 business days of delivery</span></li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-options-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Shipping options</p>
			<h2 id="gd-options-title" class="cl-section-title">Shipping Speeds and Prices</h2>
			<p>Shipping is one flat price per order, chosen at checkout. Channel letters have their own rates.</p>
			<table class="gd-table">
				<thead>
					<tr><th scope="col">Speed (after production)</th><th scope="col">Banners, displays, vinyl &amp; prints</th><th scope="col">Channel letters</th></tr>
				</thead>
				<tbody>
					<?php foreach (array('Standard (3&ndash;6 business days)', '3-Day', '2-Day', 'Overnight') as $index => $label) : ?>
						<tr>
							<th scope="row"><?php echo wp_kses_post($label); ?></th>
							<td><?php echo wp_kses_post($money($standard_rates, $index)); ?></td>
							<td><?php echo wp_kses_post($money($channel_rates, $index)); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<h3>Production time</h3>
			<p>Production starts once you approve your final proof in writing (we don&rsquo;t accept verbal approvals). Checkout shows the estimated ship date for each shipping speed, including production.</p>
			<ul class="gd-checklist">
				<li><strong>Adhesive products</strong> ordered by 4pm PST ship the next business day. Same-day service is available if you order by 12pm PST.</li>
				<li><strong>Channel letters</strong> are built, wired and tested for your order and ship ready to install, with a wiring diagram and installation pattern.</li>
			</ul>
			<?php if ($locations_url) : ?>
				<p>Planning around a deadline in your state? Our <a href="<?php echo esc_url($locations_url); ?>">state guides</a> also cover permits and finding a licensed installer.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-delivery-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Delivery</p>
			<h2 id="gd-delivery-title" class="cl-section-title">When Your Order Arrives</h2>
			<ul class="gd-checklist">
				<li>Inspect the packaging before you sign for delivery.</li>
				<li>If the packaging is damaged, inspect the contents before signing.</li>
				<li>If anything is damaged in transit, file a claim with the carrier and email <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a> or call <a href="tel:+18664362101">866-436-2101</a> right away. Damage between our plant and your door is the carrier&rsquo;s responsibility.</li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-returns-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Returns, reprints &amp; cancellations</p>
			<h2 id="gd-returns-title" class="cl-section-title">Made-to-Order Returns Policy</h2>
			<p>Because every sign is made for your business, we can&rsquo;t take returns of correctly made products. If something is wrong, we&rsquo;ll make it right.</p>
			<h3>Cancellations</h3>
			<p>Orders can&rsquo;t be stopped or cancelled once they&rsquo;re in Approved status, and there are no refunds after an order is approved.</p>
			<h3>Problems with your order</h3>
			<ul class="gd-checklist">
				<li>Report any problem within <strong>five business days</strong> of receiving your order: email <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a> or call <a href="tel:+18664362101">866-436-2101</a>.</li>
				<li>We document your claim and may ask for photos of the defect.</li>
				<li>In some cases we&rsquo;ll ask you to ship the product back; if a defect is confirmed, we may reimburse that shipping.</li>
				<li>Rush production and expedited shipping charges aren&rsquo;t refundable for defective products, unless the carrier delivers a damaged order or fails to deliver it.</li>
				<li>Turnaround and shipping for reprints depend on production capacity.</li>
			</ul>
			<p>Lit signs are also covered by our <?php if ($warranty_url) : ?><a href="<?php echo esc_url($warranty_url); ?>">five-year warranty</a><?php else : ?>five-year warranty<?php endif; ?>. Full terms are in our <a href="<?php echo esc_url($terms_url); ?>">Terms &amp; Conditions</a>.</p>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="gd-faq-title">
		<div class="container">
			<p class="cl-kicker">Questions</p>
			<h2 id="gd-faq-title" class="cl-section-title">Shipping FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php wholesale_guide_render_help('Questions About Your Delivery?', 'Call or email with your order number and we&rsquo;ll check its production and shipping status.'); ?>
</main>

<?php
get_footer();
