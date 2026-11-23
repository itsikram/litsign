<?php
/**
 * Shipping & Returns (/shipping-returns/).
 *
 * Shipping options and prices come from the checkout settings (Settings >
 * Storefront Sign > Checkout defaults), so this page always matches what
 * checkout charges. Cancellation, reprint and claim terms restate the
 * Terms & Conditions page; change both together. The trade site's policy is no
 * refunds on approved jobs; the retail wording stays as it is (Merchant Center
 * fix, inc/seo.php).
 *
 * @package litsign
 */

$no_refunds = function_exists('wholesale_is_trade_site') && wholesale_is_trade_site();

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$terms_page = get_page_by_path('terms-conditions');
$terms_url = $terms_page ? get_permalink($terms_page) : home_url('/terms-conditions/');
$warranty_url = wholesale_guide_url('warranty');
// The trade site has no state guides (inc/trade/settings.php answers them with 410).
$locations_url = (function_exists('wholesale_is_trade_site') && wholesale_is_trade_site()) ? '' : wholesale_guide_url('locations');

$rate_list = static function ($setting) {
	return array_values(array_map('floatval', array_filter(explode(',', (string) wholesale_get_setting($setting)), 'is_numeric')));
};
$standard_rates = $rate_list('standard_shipping_options');
$channel_rates = $rate_list('channel_shipping_options');
$free_min = wholesale_free_shipping_amount();
$free_channel_min = wholesale_free_shipping_amount(true);
// One line for the offers that are switched on, e.g. "... $100 or more (orders with channel letters: $1,000 or more)".
$free_note = '';
if ($free_min && $free_channel_min && $free_min === $free_channel_min) {
	$free_note = sprintf('Free Ground shipping on orders of %s or more', $free_min);
} elseif ($free_min && $free_channel_min) {
	$free_note = sprintf('Free Ground shipping on orders of %s or more (orders with channel letters: %s or more)', $free_min, $free_channel_min);
} elseif ($free_min) {
	$free_note = sprintf('Free Ground shipping on orders of %s or more (channel letters excluded)', $free_min);
} elseif ($free_channel_min) {
	$free_note = sprintf('Free Ground shipping on channel letter orders of %s or more', $free_channel_min);
}
$money = static function ($rates, $index) {
	return isset($rates[$index]) ? '$' . number_format($rates[$index], 2) : '&ndash;';
};
// Delivery options (inc/shipping.php).
$carrier_names = implode(' or ', wholesale_enabled_shipping_carriers());
$pickup = wholesale_pickup_location();
$prices_hidden = function_exists('wholesale_trade_hide_prices') && wholesale_trade_hide_prices();
$surcharges = array();
foreach (array('saturday_delivery_fee' => 'Saturday Delivery (2 Day from a Thursday ship date, Next Day from a Friday)', 'early_am_fee' => 'Next Day Early AM') as $fee_key => $fee_label) {
	$fee = (float) wholesale_get_setting($fee_key);
	if ($fee > 0 || $prices_hidden) {
		$surcharges[$fee_label] = $fee > 0 ? '+$' . number_format($fee, 2) : '&ndash;';
	}
}

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'Do you ship to all 50 states?',
		'a' => 'Yes. Every order is made to order and shipped by ' . $carrier_names . ' from Washington or California to any address in the United States.' . ($pickup ? ' You can also pick up for free at ' . $pickup['address'] . '.' : ''),
	),
	array(
		'q' => 'Can you blind ship to my client?',
		'a' => 'Yes, for free. Choose Blind drop ship at checkout and enter your business as the return address. The label shows your business, and nothing in the box names us or shows prices.',
	),
	array(
		'q' => 'Do you offer free shipping?',
		'a' => $free_note ? $free_note . '. Free shipping uses ' . $carrier_names . ' Ground (3&ndash;6 business days after production) and is applied at checkout once your order qualifies.' : 'Not at the moment. Shipping is one flat price per order, chosen at checkout.',
	),
	array(
		'q' => 'When will my sign ship?',
		'a' => 'Every sign is made to order. Checkout shows the estimated date for each shipping speed, and those dates include production time. Production starts after your final written proof approval.',
	),
	array(
		'q' => 'Can I cancel my order?',
		'a' => $no_refunds
			? 'Orders can&rsquo;t be stopped or cancelled once they are approved for production, because each sign is made for your business, and approved orders are not refundable. If an approved order arrives defective or incorrect, report it within five business days so we can investigate a claim.'
			: 'Orders can&rsquo;t be stopped or cancelled once they are approved for production, because each sign is made for your business. If an approved order arrives defective or incorrect, we still reprint it or refund it.',
	),
	array(
		'q' => 'What if something is wrong with my order?',
		'a' => $no_refunds
			? 'Report it within five business days of receiving your order by email or phone. We&rsquo;ll document the claim and request photos to investigate the problem. Approved orders are not refundable.'
			: 'Report it within five business days of receiving your order by email or phone. We&rsquo;ll open a claim and ask for photos, then reprint or replace the item at no charge. If we can&rsquo;t reprint it, we refund the full price you paid for that item.',
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
			<p class="sf-hero-lead">Every sign is made to order and shipped from Washington or California to all 50 states. Choose <?php echo esc_html($carrier_names); ?> Ground, 3 Day, 2 Day or Next Day at checkout, blind drop ship to your client<?php echo $pickup ? ', or pick up for free' : ''; ?>. The dates you see at checkout include production time.</p>
			<ul class="gd-facts">
				<li><strong>Ships to</strong><span>All 50 states by <?php echo esc_html($carrier_names); ?></span></li>
				<li><strong>Speeds</strong><span>Ground (3&ndash;6 business days), 3 Day, 2 Day, Next Day, Saturday and Early AM</span></li>
				<li><strong>Blind drop ship</strong><span>Free, under your business name</span></li>
				<?php if ($pickup) : ?>
					<li><strong>Store pickup</strong><span>Free in <?php echo esc_html($pickup['city']); ?></span></li>
				<?php endif; ?>
				<?php if ($free_note) : ?>
					<li><strong>Free shipping</strong><span><?php echo esc_html($free_note); ?></span></li>
				<?php endif; ?>
				<li><strong>Printed products</strong><span>Ordered by 4 pm PT ship the next business day</span></li>
				<li><strong>Problems</strong><span>Report within 5 business days of delivery</span></li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-options-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Shipping options</p>
			<h2 id="gd-options-title" class="cl-section-title">Shipping Speeds and Prices</h2>
			<p>Shipping is one flat price per order, chosen at checkout. <?php echo count(wholesale_enabled_shipping_carriers()) > 1 ? esc_html(implode(' and ', wholesale_enabled_shipping_carriers())) . ' charge the same for each speed, so pick the carrier you prefer.' : ''; ?> Channel letters have their own rates.</p>
			<?php if ($free_note) : ?>
				<p id="free-shipping"><strong><?php echo esc_html($free_note); ?>.</strong> Ground shipping becomes free at checkout once your subtotal qualifies and delivers on the same 3&ndash;6 business day schedule. Faster speeds keep the prices below.</p>
			<?php endif; ?>
			<table class="gd-table">
				<thead>
					<tr><th scope="col">Speed (after production)</th><th scope="col">Banners, displays, vinyl &amp; prints</th><th scope="col">Channel letters</th></tr>
				</thead>
				<tbody>
					<?php foreach (array('Ground (3&ndash;6 business days)', '3 Day', '2 Day', 'Next Day') as $index => $label) : ?>
						<tr>
							<th scope="row"><?php echo wp_kses_post($label); ?></th>
							<td><?php echo wp_kses_post($money($standard_rates, $index)); ?></td>
							<td><?php echo wp_kses_post($money($channel_rates, $index)); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ($surcharges as $label => $amount) : ?>
						<tr>
							<th scope="row"><?php echo esc_html($label); ?></th>
							<td colspan="2"><?php echo wp_kses_post($amount); ?> on top of the speed&rsquo;s rate</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<h3>Blind drop ship</h3>
			<p>Ship straight to your client under your own name, at no charge. Tick <strong>Blind drop ship</strong> at checkout and enter your business as the return address: it goes on the shipping label, and nothing in the box names Storefront Sign or shows prices.</p>
			<?php if ($pickup) : ?>
				<h3 id="pickup">Store pickup</h3>
				<p>Choose <strong>Store pickup</strong> at checkout to collect your order for free at <?php echo esc_html($pickup['address']); ?><?php echo $pickup['hours'] ? ' (' . esc_html($pickup['hours']) . ')' : ''; ?>. We email you when it&rsquo;s ready; bring your order number.</p>
			<?php endif; ?>
			<h3>Production time</h3>
			<p>Production starts once you approve your final proof in writing (we don&rsquo;t accept verbal approvals). Checkout shows the estimated ship date for each shipping speed, including production.</p>
			<ul class="gd-checklist">
				<li><strong>Printed products</strong> (banners, window graphics and vinyl, flags and displays) ordered and approved by 4 pm PT ship the next business day. Same-day service is available on many of them if you order by 12 pm PT; each product page shows its production times.</li>
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
				<li>If anything is damaged in transit, file a claim with the carrier and email <a href="mailto:Info@StorefrontSignWholesale.com">Info@StorefrontSignWholesale.com</a> or call <a href="tel:+18664362101">866-436-2101</a> right away. Damage between our plant and your door is the carrier&rsquo;s responsibility.</li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-returns-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Returns, reprints &amp; cancellations</p>
			<h2 id="gd-returns-title" class="cl-section-title">Made-to-Order Returns Policy</h2>
			<?php if ($no_refunds) : ?>
				<p><strong>We do not give refunds on approved orders.</strong> Because every sign is made for your business, we also can&rsquo;t take returns of correctly made products. If your order arrives defective, damaged, or different from the proof you approved, report it within five business days so we can investigate the claim and determine the remedy.</p>
			<?php else : ?>
				<p>Because every sign is made for your business, we can&rsquo;t take returns of correctly made products. If your order arrives defective, damaged, or different from the proof you approved, <strong>we reprint or replace it at no charge</strong>. If we can&rsquo;t reprint it, <strong>we refund the full price you paid for that item</strong>.</p>
			<?php endif; ?>
			<h3>Cancellations</h3>
			<?php if ($no_refunds) : ?>
				<p>Orders can&rsquo;t be stopped or cancelled once they&rsquo;re in Approved status, and approved orders are not refundable. If an order arrives defective or incorrect, contact us within five business days to open a claim.</p>
			<?php else : ?>
				<p>Orders can&rsquo;t be stopped or cancelled once they&rsquo;re in Approved status, and an approved order can&rsquo;t be cancelled for a refund. This doesn&rsquo;t limit the reprint or refund you get when an order arrives defective or incorrect.</p>
			<?php endif; ?>
			<h3>Problems with your order</h3>
			<ul class="gd-checklist">
				<li>Report any problem within <strong>five business days</strong> of receiving your order: email <a href="mailto:Info@StorefrontSignWholesale.com">Info@StorefrontSignWholesale.com</a> or call <a href="tel:+18664362101">866-436-2101</a>.</li>
				<li>We document your claim and may ask for photos of the defect.</li>
				<li>In some cases we&rsquo;ll ask you to ship the product back; if a defect is confirmed, we may reimburse that shipping.</li>
				<?php if ($no_refunds) : ?>
					<li>Rush production and expedited shipping charges aren&rsquo;t refundable for defective products unless FedEx delivers a defective product or fails to deliver an order.</li>
				<?php else : ?>
					<li>Rush production and expedited shipping charges aren&rsquo;t refundable for defective products, unless the carrier delivers a damaged order or fails to deliver it.</li>
				<?php endif; ?>
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
