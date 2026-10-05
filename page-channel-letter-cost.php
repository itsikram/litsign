<?php
/**
 * Channel Letter Cost guide (/channel-letter-cost/).
 *
 * Answers "how much do channel letters cost" with the store's real prices.
 * Every dollar amount is calculated by wholesale_price_quote(), the same
 * function the cart uses, so the guide always matches checkout.
 *
 * @package litsign
 */

$builder_url = home_url('/channel-letter-builder/');
$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$storefront_signs_url = function_exists('wholesale_seo_storefront_signs_url') ? wholesale_seo_storefront_signs_url() : '';

$styles = array(
	'standard-channel-letter-front-lit' => 'Front lit',
	'standard-channel-letter-back-lit' => 'Back lit',
	'standard-channel-letter-front-back-lit' => 'Front &amp; back lit',
	'hidden-back-halo-lit' => 'Halo lit',
	'halo-reverse-acrylic-lit-channel-letters' => 'Reverse lit',
	'inset-acrylic-face-lit-with-border-no-trimcap' => 'Trimless with border',
	'exposed-acrylic-face-lit-borderless-no-trimcap' => 'Borderless',
);

/**
 * Checkout price for a channel letter sign: $text at $height inches, first
 * option of every other attribute, optionally with the standard power supply.
 * Returns 0 when the style doesn't offer that height.
 */
$cl_price = static function ($slug, $height, $text = 'A', $power_supply = false) {
	$product = wholesale_seo_product($slug);
	if (!$product) {
		return 0.0;
	}

	$request = array('letters' => $text, 'product_quantity' => 1);
	$has_height = false;
	foreach (wholesale_product_price_attrs($product->ID) as $attr) {
		$options = wholesale_attr_options($attr);
		if (empty($attr['name']) || !$options) {
			continue;
		}
		$request[$attr['name']] = $options[0]['value'];
		if ('height' === $attr['name']) {
			foreach ($options as $option) {
				if ((int) $option['label'] === (int) $height) {
					$request['height'] = $option['value'];
					$has_height = true;
				}
			}
		}
		if ('power-supply' === $attr['name'] && $power_supply && isset($options[1])) {
			$request['power-supply'] = $options[1]['value'];
		}
	}

	if (!$has_height) {
		return 0.0;
	}

	$quote = wholesale_price_quote($product->ID, $request);

	return !empty($quote['ok']) ? (float) $quote['total'] : 0.0;
};

$money = static function ($amount) {
	return '$' . number_format((float) $amount, 2);
};

$heights = array(8, 12, 15, 18, 24, 30, 36);
$example_name = 'YOURSHOP'; // 8 letters, a typical short business name.
$example_heights = array(12, 18, 24);

$front_lit = wholesale_seo_product('standard-channel-letter-front-lit');
$front_lit_url = $front_lit ? get_permalink($front_lit) : home_url('/');
$discount = $front_lit ? (float) get_post_meta($front_lit->ID, '_discount_percent', true) : 0;
$discount_text = $discount > 0 ? rtrim(rtrim(number_format($discount, 2), '0'), '.') . '%' : '';
$front_lit_heights = wholesale_seo_letter_height_range('standard-channel-letter-front-lit');

// Option prices straight from the product and checkout settings.
$power_supply_price = 0.0;
$raceway_rate = 0.0;
foreach ($front_lit ? wholesale_product_price_attrs($front_lit->ID) : array() as $attr) {
	$options = wholesale_attr_options($attr);
	if ('power-supply' === ($attr['name'] ?? '') && isset($options[1])) {
		$power_supply_price = wholesale_parse_option_price($options[1]['value'])['amount'];
	}
	if ('raceway' === ($attr['name'] ?? '') && isset($options[1])) {
		$raceway_rate = wholesale_parse_option_price($options[1]['value'])['amount'];
	}
}
$cl_shipping = array_values(array_filter(explode(',', (string) wholesale_get_setting('channel_shipping_options')), 'is_numeric'));
$shipping_price = $cl_shipping ? (float) $cl_shipping[0] : 0.0;

$cheapest_8 = $cl_price('standard-channel-letter-front-lit', 8);
$example_12 = $cl_price('standard-channel-letter-front-lit', 12, $example_name, true);
$example_24 = $cl_price('standard-channel-letter-front-lit', 24, $example_name, true);

wholesale_seo_set_page_faq(array_values(array_filter(array(
	$cheapest_8 && $example_12 && $example_24 ? array(
		'q' => 'How much do channel letters cost?',
		'a' => sprintf('Our channel letters start at %s for one 8 inch front lit letter%s. A typical 8-letter business name in front lit letters costs about %s at 12 inches tall and %s at 24 inches tall, including a standard power supply, before shipping. Halo lit and trimless styles cost more per letter.', $money($cheapest_8), $discount_text ? ' with our ' . $discount_text . ' online discount' : '', $money($example_12), $money($example_24)),
	) : null,
	array(
		'q' => 'Why are channel letters priced per letter?',
		'a' => 'Each letter is built to order as its own lit sign, with its own face, returns and LEDs, so the cost grows with the number of letters and their height. Taller letters use more material and more LEDs, which is why price climbs with height.',
	),
	$power_supply_price ? array(
		'q' => 'Do I need to buy a power supply?',
		'a' => sprintf('Lit channel letters need a power supply to run the LEDs. A standard power supply is %s and is selected automatically when you enter your wording; choose No only if your installer is supplying one.', $money($power_supply_price)),
	) : null,
	$raceway_rate ? array(
		'q' => 'How much does a raceway cost?',
		'a' => sprintf('A raceway is optional on standard front lit, back lit and front and back lit letters and costs %s per foot. It mounts all the letters as one unit, so fewer holes go into your building.', $money($raceway_rate)),
	) : null,
	$shipping_price ? array(
		'q' => 'How much is shipping for channel letters?',
		'a' => sprintf('Standard shipping on a channel letter order is %s anywhere we ship in the USA. Faster 3-day, 2-day and overnight options are shown at checkout.', $money($shipping_price)),
	) : null,
	array(
		'q' => 'Does the price include installation?',
		'a' => 'No. Your sign ships tested and ready to install, with an installation pattern and a wiring diagram, and is installed by your local sign installer. In most areas the electrical connection must be made by a licensed electrician or sign contractor, and most cities require a sign permit, so ask local installers to quote both.',
	),
	array(
		'q' => 'What is the cheapest type of channel letter?',
		'a' => 'Standard front lit letters with a trimcap are the most affordable lit style, and smaller letters cost less. If your budget is tight, keep the wording short and choose the smallest height that can be read from the street.',
	),
))));

get_header();
?>

<main id="primary" class="sf-page">
	<section class="sf-hero" aria-labelledby="clc-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Channel letter pricing</p>
			<h1 id="clc-title" class="sf-hero-title">How Much Do Channel Letters Cost?</h1>
			<p class="sf-hero-lead">Real prices, not estimates. Channel letters are priced per letter by height<?php if ($cheapest_8) : ?>, starting at <strong><?php echo esc_html($money($cheapest_8)); ?></strong> for one 8 inch front lit letter<?php endif; ?>. The tables below come straight from our online pricing, so they match what you pay at checkout.</p>
			<div class="sf-hero-actions">
				<a class="cl-button" href="<?php echo esc_url($front_lit_url); ?>">Price Your Sign <?php echo wholesale_home_icon('arrow'); ?></a>
				<a class="sf-button-outline" href="<?php echo esc_url($contact_url); ?>">Get a Free Quote</a>
			</div>
			<ul class="sf-hero-points">
				<?php if ($discount_text) : ?><li><?php echo esc_html($discount_text); ?> online discount included</li><?php endif; ?>
				<li>Made in the USA</li>
				<li>UL listed for outdoor use</li>
				<li>Shipped ready to install</li>
			</ul>
		</div>
	</section>

	<section class="cl-compare" aria-labelledby="clc-table-title">
		<div class="container">
			<p class="cl-kicker">Price per letter</p>
			<h2 id="clc-table-title" class="cl-section-title">Channel Letter Prices by Style and Height</h2>
			<p class="cl-section-lead">Price for one letter<?php echo $discount_text ? ', after the ' . esc_html($discount_text) . ' online discount' : ''; ?>, before the power supply, raceway and shipping. Multiply by the number of letters in your sign.</p>
			<div class="cl-compare-table-wrap">
				<table class="cl-compare-table sf-price-table">
					<thead>
						<tr>
							<th scope="col">Letter height</th>
							<?php foreach ($styles as $slug => $label) : ?>
								<?php $style_product = wholesale_seo_product($slug); ?>
								<?php if ($style_product) : ?>
									<th scope="col"><a href="<?php echo esc_url(get_permalink($style_product)); ?>"><?php echo wp_kses_post($label); ?></a></th>
								<?php endif; ?>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($heights as $height) : ?>
							<tr>
								<th scope="row"><?php echo esc_html($height); ?>&Prime;</th>
								<?php foreach ($styles as $slug => $label) : ?>
									<?php if (wholesale_seo_product($slug)) : ?>
										<?php $price = $cl_price($slug, $height); ?>
										<td class="cl-compare-price"><?php echo $price ? esc_html($money($price)) : '&mdash;'; ?></td>
									<?php endif; ?>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($front_lit_heights) : ?>
				<p class="cl-section-lead">Front lit letters are available from <?php echo esc_html($front_lit_heights[0]); ?> to <?php echo esc_html($front_lit_heights[1]); ?> inches tall in 1 inch steps. Open any style to price the exact height you need.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ($example_12 && $example_24) : ?>
		<section class="sf-section" aria-labelledby="clc-example-title">
			<div class="container sf-prose">
				<p class="cl-kicker">Example signs</p>
				<h2 id="clc-example-title" class="cl-section-title">What a Typical Storefront Sign Costs</h2>
				<p>Here is what an 8-letter business name costs in front lit channel letters, including a standard power supply and the online discount<?php echo $shipping_price ? ', before ' . esc_html($money($shipping_price)) . ' standard shipping' : ''; ?>:</p>
				<div class="cl-compare-table-wrap">
					<table class="cl-compare-table sf-price-table">
						<thead>
							<tr>
								<th scope="col">Sign</th>
								<th scope="col">Letter height</th>
								<th scope="col">Price</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($example_heights as $height) : ?>
								<?php $price = $cl_price('standard-channel-letter-front-lit', $height, $example_name, true); ?>
								<?php if ($price) : ?>
									<tr>
										<th scope="row">8 front lit letters + power supply</th>
										<td><?php echo esc_html($height); ?> inches</td>
										<td class="cl-compare-price"><?php echo esc_html($money($price)); ?></td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p>Spaces aren&rsquo;t charged, so &ldquo;Sun Cafe&rdquo; counts as 7 letters. Add a logo or shapes with the <a href="<?php echo esc_url($builder_url); ?>">online sign builder</a>, which prices your full design as you go.</p>
			</div>
		</section>
	<?php endif; ?>

	<section class="sf-section sf-section--tint" aria-labelledby="clc-factors-title">
		<div class="container sf-prose">
			<p class="cl-kicker">What affects the price</p>
			<h2 id="clc-factors-title" class="cl-section-title">5 Things That Decide Your Channel Letter Cost</h2>
			<h3>1. Number of letters</h3>
			<p>You pay per letter, so a short name costs less than a long one. Spaces are free. Shortening &ldquo;Smith Family Dental Care&rdquo; to &ldquo;Smith Dental&rdquo; cuts the letter count, and the price, by about half.</p>
			<h3>2. Letter height</h3>
			<p>Taller letters cost more because they use more aluminum, acrylic and LEDs. Size letters for your viewing distance: a common sign industry rule of thumb is about 1 inch of letter height for every 10 feet, so letters read from a 120-foot parking lot work best at around 12 inches or taller.</p>
			<h3>3. Lighting style</h3>
			<p>Standard front lit letters are the most affordable. Back lit and dual lit letters add light aimed at the wall, and halo lit, reverse lit and trimless letters use welded stainless steel faces or returns, which costs more but gives a premium look. Compare them in the table above.</p>
			<h3>4. Power supply and raceway</h3>
			<p>Lit letters need a power supply<?php echo $power_supply_price ? ' (' . esc_html($money($power_supply_price)) . ' for a standard one)' : ''; ?>. A raceway<?php echo $raceway_rate ? ' (' . esc_html($money($raceway_rate)) . ' per foot)' : ''; ?> is optional: it mounts all the letters on one metal box, which means fewer holes in your wall and an easier move later.</p>
			<h3>5. Installation and permits</h3>
			<p>Your sign ships ready to install with a wiring diagram and installation pattern, so the remaining costs are local: a sign installer, an electrician to connect power where required, and your city&rsquo;s sign permit. Ask your landlord for any shopping center sign criteria before you order.</p>
			<?php if ($storefront_signs_url) : ?>
				<p>Still choosing a sign type? Our <a href="<?php echo esc_url($storefront_signs_url); ?>">storefront signs guide</a> compares channel letters with window graphics, A-frames, banners and flags.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="clc-faq-title">
		<div class="container">
			<p class="cl-kicker">Pricing questions</p>
			<h2 id="clc-faq-title" class="cl-section-title">Channel Letter Cost FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(true); ?>
			</div>
		</div>
	</section>

	<section class="cl-help" aria-labelledby="clc-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="clc-help-title">Get an Exact Price for Your Sign</h2>
				<p>Send your logo and storefront photo and a sign specialist will price the letters, height and options that fit your building.</p>
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
