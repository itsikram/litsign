<?php
/**
 * Storefront Signs guide and hub (/storefront-signs/).
 *
 * Covers the sign types sold on the site, how to choose, materials, sizes,
 * installation, ordering and pricing, and links into each product line.
 * Prices and letter heights are read from the products so they stay current.
 *
 * @package litsign
 */

$builder_url = home_url('/channel-letter-builder/');
$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$channel_letters_url = home_url('/');

$product_url = static function ($slug, $fallback) {
	$product = wholesale_seo_product($slug);
	return $product ? get_permalink($product) : $fallback;
};

$front_lit_heights = wholesale_seo_letter_height_range('standard-channel-letter-front-lit');
$halo_heights = wholesale_seo_letter_height_range('hidden-back-halo-lit');
$min_height = $front_lit_heights ? $front_lit_heights[0] : 8;
$max_height = $front_lit_heights ? $front_lit_heights[1] : 0;

// Sign types: product slug (image and starting price), link, name, summary, lit?, typical use.
$sign_types = array(
	array('standard-channel-letter-front-lit', $channel_letters_url, 'LED Channel Letters', 'Individually built, LED lit letters mounted on your building or on a raceway. The most popular lit storefront sign for retail stores, restaurants and offices.', true, 'Primary building sign'),
	array('hidden-back-halo-lit', $product_url('hidden-back-halo-lit', $channel_letters_url), 'Halo &amp; Reverse Lit Letters', 'Letters with solid metal faces that glow onto the wall behind them, for an upscale look at salons, boutiques, clinics and professional offices.', true, 'Premium building sign'),
	array('exposed-acrylic-face-lit-borderless-no-trimcap', $product_url('exposed-acrylic-face-lit-borderless-no-trimcap', $channel_letters_url), 'Trimless &amp; Borderless Letters', 'Face lit letters without a plastic trimcap for a clean, modern edge that suits contemporary brands.', true, 'Modern building sign'),
	array('adhesive-window-perf', wholesale_category_url('adhesive-products'), 'Window Graphics', 'Printed vinyl, see-through window perf, frosted and clear films and removable clings that turn your glass into advertising for hours, offers and branding.', false, 'Windows and doors'),
	array('deluxe-signicade-graphic-frame', wholesale_category_url('signicade-a-frames'), 'Sidewalk A-Frame Signs', 'Double-sided sidewalk signs that pull foot traffic in from the street, with printed graphics you can swap as promotions change.', false, 'Sidewalk and entrance'),
	array('13oz-vinyl-banner', wholesale_category_url('banners'), 'Banners', 'Fast, affordable signs for grand openings and sales, or a temporary sign while your permanent storefront sign is being made.', false, 'Openings and promotions'),
	array('feather-angled-flag-pole', wholesale_category_url('advertising-flags'), 'Advertising Flags', 'Feather and teardrop flags that move in the wind and catch drivers&rsquo; attention from the road.', false, 'Roadside visibility'),
	array('aluminum-sign', wholesale_category_url('rigid-signs-and-magnets'), 'Rigid Signs', 'Aluminum, PVC and Coroplast signs for doors, store hours, parking and wayfinding around your building.', false, 'Doors, hours and parking'),
);

// Channel letter styles and where each starts, for the pricing section.
$cl_styles = array(
	'standard-channel-letter-front-lit' => 'Front lit',
	'standard-channel-letter-back-lit' => 'Back lit',
	'standard-channel-letter-front-back-lit' => 'Front &amp; back lit',
	'hidden-back-halo-lit' => 'Halo lit',
	'halo-reverse-acrylic-lit-channel-letters' => 'Reverse lit',
	'inset-acrylic-face-lit-with-border-no-trimcap' => 'Trimless with border',
	'exposed-acrylic-face-lit-borderless-no-trimcap' => 'Borderless',
);

$height_text = $max_height
	? sprintf('%d to %d inches tall', $min_height, $max_height)
	: sprintf('from %d inches tall', $min_height);

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'What is the best type of storefront sign?',
		'a' => 'For most businesses the main building sign should be LED channel letters: they read clearly by day, light up at night and fit almost any brand. Halo lit letters suit upscale brands, while window graphics, A-frames and banners add messages closer to the door. Many storefronts combine a lit sign with window graphics.',
	),
	array(
		'q' => 'How much does a storefront sign cost?',
		'a' => 'Channel letters are priced per letter by letter height, so the total depends on how many letters you need, how tall they are and the style. Each style shows its starting price, and the <a href="' . esc_url($builder_url) . '">online sign builder</a> shows your full price as you design. Window graphics and banners are priced per square foot.',
	),
	array(
		'q' => 'What size should my storefront sign letters be?',
		'a' => 'Start with how far away your customers are. A common sign industry rule of thumb is about 1 inch of letter height for every 10 feet of viewing distance, so a sign seen from across a 120-foot parking lot works best with letters about 12 inches or taller. Then check the space on your fascia and any size limits in your lease or local sign code. Our channel letters are available ' . $height_text . '.',
	),
	array(
		'q' => 'Do I need a permit for a storefront sign?',
		'a' => 'In most cities, yes: exterior signs, and especially lit signs, usually need a sign permit, and many shopping centers have their own sign criteria for size, color and lighting. Check with your landlord and your city before you order, then size your sign to fit those rules.',
	),
	array(
		'q' => 'Are lit storefront signs worth it?',
		'a' => 'If customers pass your business after dark, or you share a street with other lit signs, a lit sign keeps your name visible for more hours of the day. LED channel letters use energy-efficient LEDs, and non-lit signs such as window graphics and A-frames work well alongside them.',
	),
	array(
		'q' => 'How are channel letters installed?',
		'a' => 'Letters mount either directly to the wall, using the included installation pattern, or on a raceway, a metal box that holds the wiring and mounts the letters as one unit so fewer holes go into your building. Every sign ships with a wiring diagram for your installer. In most areas the electrical hookup must be done by a licensed electrician or sign contractor.',
	),
	array(
		'q' => 'How long does it take to get my sign?',
		'a' => 'Every sign is made to order. Your estimated ship date is shown at checkout, and after manufacturing you can choose standard, 3-day, 2-day or overnight shipping.',
	),
	array(
		'q' => 'Can I send my logo instead of designing the sign myself?',
		'a' => 'Yes. <a href="' . esc_url($contact_url) . '">Request a free quote</a> and upload your logo, or call <a href="tel:+18664362101">866-436-2101</a>, and a sign specialist will price the letters and options you need.',
	),
));

get_header();
?>

<main id="primary" class="sf-page">
	<section class="sf-hero" aria-labelledby="sf-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Storefront signs</p>
			<h1 id="sf-title" class="sf-hero-title">Custom Storefront Signs That Bring Customers In</h1>
			<p class="sf-hero-lead">Your storefront sign is the first thing customers see. Choose lit channel letters for your building, graphics for your windows and signs for the sidewalk, and see prices online before you order.</p>
			<div class="sf-hero-actions">
				<a class="cl-button" href="<?php echo esc_url($channel_letters_url); ?>">Shop Channel Letters <?php echo wholesale_home_icon('arrow'); ?></a>
				<a class="sf-button-outline" href="<?php echo esc_url($contact_url); ?>">Get a Free Quote</a>
			</div>
			<ul class="sf-hero-points">
				<li>Prices shown online</li>
				<li>Channel letters made in the USA</li>
				<li>UL listed outdoor lit signs</li>
				<li>Shipped ready to install</li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="sf-types-title">
		<div class="container">
			<p class="cl-kicker">Types of storefront signs</p>
			<h2 id="sf-types-title" class="cl-section-title">Storefront Signs for Every Part of Your Business</h2>
			<p class="cl-section-lead">Most storefronts use more than one sign: a main building sign that works day and night, plus signs at eye level that tell passing customers what&rsquo;s inside.</p>
			<div class="sf-type-grid">
				<?php foreach ($sign_types as $type) : ?>
					<?php
					$type_product = wholesale_seo_product($type[0]);
					$type_price = wholesale_seo_product_starting_text($type[0]);
					?>
					<article class="sf-type-card">
						<a class="sf-type-image" href="<?php echo esc_url($type[1]); ?>" tabindex="-1" aria-hidden="true">
							<?php
							if ($type_product && has_post_thumbnail($type_product)) {
								echo get_the_post_thumbnail($type_product, 'medium_large', array(
									'loading' => 'lazy',
									'decoding' => 'async',
									'alt' => wp_strip_all_tags(html_entity_decode($type[2], ENT_QUOTES, 'UTF-8')),
									'sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw',
								));
							}
							?>
						</a>
						<div class="sf-type-body">
							<h3><a href="<?php echo esc_url($type[1]); ?>"><?php echo wp_kses_post($type[2]); ?></a></h3>
							<p><?php echo wp_kses_post($type[3]); ?></p>
							<?php if ($type_price) : ?>
								<p class="sf-type-price"><small>Starting at</small> <?php echo wp_kses_post($type_price); ?></p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="cl-compare" aria-labelledby="sf-compare-title">
		<div class="container">
			<p class="cl-kicker">Compare at a glance</p>
			<h2 id="sf-compare-title" class="cl-section-title">Lit vs. Non-Lit Storefront Signs</h2>
			<p class="cl-section-lead">A lit sign keeps working after dark; non-lit signs are quick, affordable ways to add messages at eye level.</p>
			<div class="cl-compare-table-wrap">
				<table class="cl-compare-table">
					<thead>
						<tr>
							<th scope="col">Sign type</th>
							<th scope="col">Lit at night</th>
							<th scope="col">Best for</th>
							<th scope="col">Starting at</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($sign_types as $type) : ?>
							<tr>
								<th scope="row"><a href="<?php echo esc_url($type[1]); ?>"><?php echo wp_kses_post($type[2]); ?></a></th>
								<td><?php echo $type[4] ? 'Yes, LED' : 'No'; ?></td>
								<td><?php echo esc_html($type[5]); ?></td>
								<td class="cl-compare-price"><?php echo wp_kses_post(wholesale_seo_product_starting_text($type[0])); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="sf-choose-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Buying guide</p>
			<h2 id="sf-choose-title" class="cl-section-title">How to Choose the Right Storefront Sign</h2>

			<h3>1. Size it for how far away your customers are</h3>
			<p>Readability comes first. A common sign industry rule of thumb is about 1 inch of letter height for every 10 feet of viewing distance: letters seen from a 120-foot parking lot or across a busy street work best at around 12 inches or taller. Measure the width and height of the space on your fascia and leave some margin around the letters so the sign doesn&rsquo;t look crowded. Our channel letters are available <?php echo esc_html($height_text); ?>.</p>

			<h3>2. Decide whether the sign needs to work at night</h3>
			<p>If you&rsquo;re open in the evening, or customers drive past after dark, choose an illuminated sign. <a href="<?php echo esc_url($channel_letters_url); ?>">LED channel letters</a> are the standard for lit storefront signs because each letter is lit from inside and reads clearly day and night. Non-lit options such as <a href="<?php echo esc_url(wholesale_category_url('adhesive-products')); ?>">window graphics</a> and <a href="<?php echo esc_url(wholesale_category_url('reflective-products')); ?>">reflective signs</a> cost less and are ideal for secondary messages.</p>

			<h3>3. Match the lighting style to your brand</h3>
			<p>Front lit letters shine light through a colored acrylic face and are the brightest, easiest-to-read option. Halo lit and reverse lit letters glow onto the wall behind them for a softer, upscale look. Front and back lit letters do both. Trimless and borderless letters drop the plastic trimcap for a sleek, modern edge. The <a href="<?php echo esc_url($channel_letters_url . '#cl-compare-title'); ?>">channel letter comparison</a> shows how each style lights and where it starts.</p>

			<h3>4. Check your lease and local sign rules</h3>
			<p>Most cities require a permit for exterior signs, and many shopping centers publish sign criteria that set letter height, colors, lighting and mounting. Ask your landlord for the criteria before you order, then size your sign to fit. If you&rsquo;re not sure what will work, <a href="<?php echo esc_url($contact_url); ?>">send us your storefront photo and logo</a>.</p>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="sf-materials-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Materials &amp; lighting</p>
			<h2 id="sf-materials-title" class="cl-section-title">What Storefront Signs Are Made Of</h2>
			<p>Standard channel letters are built with .040 aluminum returns (the sides of each letter), a colored acrylic face and a trimcap that holds the face in place, with LED modules and a power supply inside. Halo lit letters use welded stainless steel faces and returns, sanded and painted, so the light shines out of the back. Outdoor channel letter signs are UL listed and ship with sign section labels.</p>
			<p>Window graphics are printed on adhesive vinyl: opaque vinyl for solid graphics, perforated window film that lets people see out while showing your design from outside, clear and frosted films for glass, and removable clings for short promotions. Sidewalk signs and rigid signs use weather-resistant plastics and aluminum.</p>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="sf-install-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Sizes &amp; installation</p>
			<h2 id="sf-install-title" class="cl-section-title">Sizing and Installing Your Sign</h2>
			<h3>Letter heights</h3>
			<p>Channel letters start at <?php echo esc_html($min_height); ?> inches<?php if ($max_height) : ?> and go up to <?php echo esc_html($max_height); ?> inches for front lit letters<?php endif; ?><?php if ($halo_heights && $halo_heights[1] !== $max_height) : ?> (<?php echo esc_html($halo_heights[1]); ?> inches for halo lit)<?php endif; ?>. Choose a height on any style to see the options and price.</p>
			<h3>Raceway or direct mount</h3>
			<p>A raceway is a metal box behind the letters that holds the wiring and mounts the whole sign as one unit, so fewer holes go into your building and the sign is easier to remove when you move. Direct (flush) mounting attaches each letter to the wall for the cleanest look. Letters ship with an installation pattern to mark the holes and a wiring diagram for your installer.</p>
			<h3>Who installs it</h3>
			<p>Your sign arrives tested and ready to install. Mounting is straightforward for a local sign installer, and in most areas the electrical connection must be made by a licensed electrician or sign contractor.</p>
		</div>
	</section>

	<section class="cl-steps" aria-labelledby="sf-steps-title">
		<div class="container">
			<p class="cl-kicker">Ordering process</p>
			<h2 id="sf-steps-title" class="cl-section-title">How to Order Your Storefront Sign</h2>
			<ol class="cl-steps-list">
				<li>
					<span class="cl-step-number" aria-hidden="true">1</span>
					<h3>Choose your sign</h3>
					<p>Pick a channel letter style or another sign type above.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">2</span>
					<h3>Design &amp; see your price</h3>
					<p>Enter your wording, size and colors, or <a href="<?php echo esc_url($builder_url); ?>">use the online sign builder</a>. Prefer help? <a href="<?php echo esc_url($contact_url); ?>">Send your logo for a quote</a>.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">3</span>
					<h3>We build &amp; test it</h3>
					<p>Every sign is made to order, and lit signs are tested before they ship.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">4</span>
					<h3>Install &amp; open</h3>
					<p>Choose standard, 3-day, 2-day or overnight shipping at checkout.</p>
				</li>
			</ol>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="sf-pricing-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Pricing guidance</p>
			<h2 id="sf-pricing-title" class="cl-section-title">How Much Does a Storefront Sign Cost?</h2>
			<p>Channel letters are priced per letter by letter height. Your total is the number of letters times the price for the height you choose, plus options such as a raceway, so a short name in smaller letters costs far less than a long name in tall letters. You see the full price before checkout.</p>
			<div class="cl-compare-table-wrap">
				<table class="cl-compare-table sf-price-table" aria-label="Channel letter starting prices by style">
					<thead>
						<tr>
							<th scope="col">Channel letter style</th>
							<th scope="col">Starting at</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($cl_styles as $slug => $label) : ?>
							<?php $style_product = wholesale_seo_product($slug); ?>
							<?php if ($style_product) : ?>
								<tr>
									<th scope="row"><a href="<?php echo esc_url(get_permalink($style_product)); ?>"><?php echo wp_kses_post($label); ?></a></th>
									<td class="cl-compare-price"><?php echo wp_kses_post(wholesale_seo_product_starting_text($slug)); ?></td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p>Window graphics and banners are priced per square foot<?php $vinyl_price = wholesale_seo_product_starting_text('adhesive-window-perf'); $banner_price = wholesale_seo_product_starting_text('13oz-vinyl-banner'); if ($vinyl_price && $banner_price) : ?> (window perf starts at <?php echo wp_kses_post($vinyl_price); ?> and 13oz vinyl banners at <?php echo wp_kses_post($banner_price); ?>)<?php endif; ?>. For a logo, a large project or several signs, <a href="<?php echo esc_url($contact_url); ?>">request a free quote</a>.</p>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="sf-faq-title">
		<div class="container">
			<p class="cl-kicker">Before you order</p>
			<h2 id="sf-faq-title" class="cl-section-title">Storefront Sign FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<section class="cl-help" aria-labelledby="sf-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="sf-help-title">Talk to a Real Sign Specialist</h2>
				<p>Send a photo of your storefront and your logo, and we&rsquo;ll help you choose the right sign and size before you order.</p>
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
