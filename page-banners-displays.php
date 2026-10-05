<?php
/**
 * Banners & Displays campaign landing page (/banners-displays/).
 *
 * Hub for printed banners, banner stands, flags and trade show displays:
 * product lines, picks by occasion, material and stand comparisons, a buying
 * guide and FAQ. Prices and photos are read from the products so they stay
 * current; products that are missing or unpublished are skipped.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$banners_url = wholesale_category_url('banners');

$product_url = static function ($slug, $fallback = '') {
	$product = wholesale_seo_product($slug);
	return $product ? get_permalink($product) : $fallback;
};

// $eager: 'high' for the page's main image, true for other above-the-fold images.
$product_image = static function ($slug, $alt, $sizes, $eager = false) {
	$product = wholesale_seo_product($slug);
	if (!$product || !has_post_thumbnail($product)) {
		return '';
	}

	return get_the_post_thumbnail($product, 'medium_large', array(
		'loading' => $eager ? 'eager' : 'lazy',
		'fetchpriority' => 'high' === $eager ? 'high' : 'auto',
		'decoding' => 'async',
		'alt' => $alt,
		'sizes' => $sizes,
	));
};

// Product lines: category slug, product whose photo and price represent it, name, summary.
$lines = array(
	array('banners', '13oz-vinyl-banner', 'Vinyl &amp; Fabric Banners', '13oz and 18oz vinyl, mesh, backlit and wrinkle-free fabric banners, hemmed and finished the way you hang them.'),
	array('banner-stands', 'standard-retractable-insert-stand', 'Retractable Banner Stands', 'Roll-up, X-stand, tension fabric and table top stands that set up in minutes and travel in a bag.'),
	array('advertising-flags', 'feather-angled-flag-pole', 'Feather &amp; Teardrop Flags', 'Flags from 9 to 18 feet tall that move in the wind and catch drivers&rsquo; eyes from the road.'),
	array('trade-show-products', 'straight-tension-fabric-displays-graphic-frame', 'Trade Show Displays', 'Straight and curved tension fabric walls and pop up displays for a booth that looks finished.'),
	array('seg-products', '10ft-seg-fabric-display-graphic-frame', 'SEG &amp; Backlit Displays', 'Silicone edge fabric graphics in aluminum frames, with backlit options that glow on a busy show floor.'),
	array('table-throws', '6ft-table-cover', 'Table Covers &amp; Throws', 'Printed 4, 6 and 8 ft covers, stretch covers and runners that turn any table into branded space.'),
	array('custom-event-tents', 'event-tent-full-canopy-graphic-frame', 'Custom Event Tents', 'Full color canopy tents, walls and tent flags for festivals, markets and outdoor events.'),
	array('a-frame-and-sign-holders', 'banner-a-banner-frame', 'A-Frames &amp; Sign Holders', 'Banner A-frames, poster stands and snap hangers that put your message on the sidewalk.'),
);

// Best sellers: product slug, short label.
$best_sellers = array(
	'13oz-vinyl-banner' => 'Outdoor everyday banner',
	'18oz-blockout-banner' => 'Double-sided, heavy duty',
	'mesh-banner' => 'Fences &amp; windy spots',
	'standard-retractable-insert-stand' => 'Most popular stand',
	'x-stand-graphic-stand' => 'Budget indoor stand',
	'step-and-repeat-backdrop-graphic-frame' => 'Photo backdrops',
	'feather-angled-flag-pole' => 'Roadside attention',
	'10ft-seg-fabric-display-graphic-frame' => 'Trade show back wall',
);

// Picks by occasion: id, heading, intro, array(product slug => label).
$occasions = array(
	array('grand-opening', 'Grand Openings &amp; Sales', 'Make sure the whole street knows you&rsquo;re open, then keep the promotion in front of customers at the door.', array(
		'13oz-vinyl-banner' => 'Storefront banner',
		'feather-angled-flag-pole' => 'Feather flag',
		'banner-a-banner-frame' => 'Banner A-frame',
	)),
	array('trade-show', 'Trade Shows &amp; Expos', 'Build a booth that draws people in from the aisle and packs into the trunk afterwards.', array(
		'10ft-seg-fabric-display-graphic-frame' => '10ft back wall',
		'deluxe-retractable-insert-stand' => 'Retractable stand',
		'6ft-table-cover' => 'Printed table cover',
	)),
	array('events', 'Events &amp; Festivals', 'Shade, shelter and branding for markets, races, fairs and outdoor activations.', array(
		'event-tent-full-canopy-graphic-frame' => 'Canopy tent',
		'teardrop-flag-pole' => 'Teardrop flag',
		'mesh-banner' => 'Mesh fence banner',
	)),
	array('in-store', 'In-Store &amp; Lobby', 'Point customers to new products, offers and check-in from the moment they walk in.', array(
		'standard-retractable-insert-stand' => 'Retractable stand',
		'x-stand-graphic-stand' => 'X-stand',
		'table-top-banner-stand' => 'Table top stand',
	)),
);

// Banner materials: product slug, material, best for, outdoors.
$materials = array(
	array('13oz-vinyl-banner', '13oz Vinyl', 'Storefronts, sales and everyday outdoor use', 'Yes'),
	array('18oz-blockout-banner', '18oz Blockout Vinyl', 'Double-sided banners and long outdoor runs', 'Yes'),
	array('mesh-banner', 'Mesh Vinyl', 'Fences, scaffolding and windy locations', 'Yes'),
	array('pole-banner-set-banner-hardware', 'Pole Banner Set', 'Street and parking lot light poles', 'Yes'),
	array('fabric-banner-9oz-wrinkle-free-copy', '9oz Wrinkle-Free Fabric', 'Indoor backdrops, events and photos', 'Indoor'),
	array('backlit-banner', 'Backlit Banner', 'Lightboxes and illuminated displays', 'In a lightbox'),
);

// Display stands: product slug, stand, setup, best for.
$stands = array(
	array('table-top-banner-stand', 'Table Top Stand', 'Seconds', 'Counters, check-in and tables'),
	array('x-stand-graphic-stand', 'X-Stand', 'About a minute', 'Budget indoor promotions'),
	array('standard-retractable-insert-stand', 'Standard Retractable', 'About a minute', 'Lobbies, retail and trade shows'),
	array('deluxe-retractable-insert-stand', 'Deluxe Retractable', 'About a minute', 'Frequent travel and heavy use'),
	array('tension-fabric-stand-graphic-frame', 'Tension Fabric Stand', 'A few minutes', 'Wrinkle-free, premium look'),
	array('step-and-repeat-backdrop-graphic-frame', 'Step &amp; Repeat Backdrop', 'A few minutes', 'Red carpets and photo walls'),
);

$vinyl_price = wholesale_seo_product_starting_text('13oz-vinyl-banner');

$hero_photos = array(
	array('13oz-vinyl-banner', 'Custom printed 13oz vinyl banner'),
	array('standard-retractable-insert-stand', 'Retractable banner stand with printed graphic'),
	array('feather-angled-flag-pole', 'Custom feather flag on a pole'),
	array('10ft-seg-fabric-display-graphic-frame', '10ft SEG fabric trade show display'),
);

// The best sellers are this page's item list in structured data.
$GLOBALS['wholesale_page_items'] = array_values(array_filter(array_map(static function ($slug) {
	return wholesale_seo_product($slug);
}, array_keys($best_sellers))));
$GLOBALS['wholesale_page_image'] = ($hero = wholesale_seo_product('13oz-vinyl-banner')) ? get_the_post_thumbnail_url($hero, 'large') : '';

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'How much does a custom banner cost?',
		'a' => 'Banners are priced per square foot, so the price depends on size and material' . ($vinyl_price ? ': 13oz vinyl starts at ' . $vinyl_price : '') . '. Enter your width and height on any banner and your price, including finishing options, updates before you add it to the cart.',
	),
	array(
		'q' => 'What size banner do I need?',
		'a' => 'Measure the space where it will hang, then think about distance: a banner read from across a parking lot or street needs larger lettering and fewer words than one read at the door. A short headline, your logo and one detail (a sale, a phone number or a website) is easier to read than a full paragraph.',
	),
	array(
		'q' => 'Which banner material is best for outdoor use?',
		'a' => '13oz vinyl is the everyday choice for outdoor banners. Choose 18oz blockout when the banner is seen from both sides or stays up for a long time, and mesh for fences, scaffolding and windy locations because air passes through it.',
	),
	array(
		'q' => 'What finishing options do banners come with?',
		'a' => 'Banners are hemmed on all sides. You can add grommets every 2 feet or in the corners, pole pockets at the top or top and bottom, webbing with D-rings, Velcro, and reinforced corners, and print one or both sides.',
	),
	array(
		'q' => 'Which banner stand should I buy?',
		'a' => 'A retractable (roll-up) stand is the most popular: the graphic rolls into the base, so it sets up in about a minute and stays protected in transit. An X-stand is the lightest budget option, and a tension fabric stand gives a wrinkle-free premium look. Retractable stands come in sizes from 23&Prime; x 66&Prime; up to 47&Prime; x 81&Prime;.',
	),
	array(
		'q' => 'Can I order just a replacement graphic for my stand?',
		'a' => 'Yes. Most stands, flags, A-frames and fabric displays can be ordered as the printed graphic only, so you can refresh a promotion and keep the hardware you already own. Look for the &ldquo;graphic only&rdquo; version of the product.',
	),
	array(
		'q' => 'How fast can I get my banner?',
		'a' => 'Many banners and displays offer next-day turnaround when you order and submit artwork before 4pm PST, and some banners can ship the same day when artwork is in before 12pm PST. Each product shows its turnaround, and you choose standard, 3-day, 2-day or overnight shipping at checkout.',
	),
	array(
		'q' => 'Can you help design my banner?',
		'a' => 'Yes. <a href="' . esc_url($contact_url) . '">Request a free quote</a> and upload your logo, or call <a href="tel:+18664362101">866-436-2101</a>, and a specialist will help with sizing, materials and artwork before you order.',
	),
));

get_header();
?>

<main id="primary" class="sf-page bd-page">
	<section class="bd-hero" aria-labelledby="bd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<div class="bd-hero-grid">
				<div class="bd-hero-copy">
					<p class="home-hero-eyebrow">Full color &middot; Printed to order &middot; Next-day options</p>
					<h1 id="bd-title" class="bd-hero-title">Custom Banners &amp; Displays <span>That Get You Noticed</span></h1>
					<p class="home-hero-lead">Vinyl and fabric banners, retractable banner stands, feather flags and trade show displays. Pick a product, enter your size and see your price before you check out.</p>
					<div class="home-hero-actions home-hero-actions--cl">
						<a class="home-hero-shop-button" href="<?php echo esc_url($banners_url); ?>"><span class="home-hero-button-text">Shop Banners<?php if ($vinyl_price) : ?><small>From <?php echo wp_kses_post($vinyl_price); ?></small><?php endif; ?></span> <span aria-hidden="true">&rarr;</span></a>
						<a class="home-hero-secondary-button" href="<?php echo esc_url($contact_url); ?>"><?php echo wholesale_home_icon('message'); ?> Get a Free Quote</a>
					</div>
					<p class="home-hero-help">
						<?php echo wholesale_home_icon('phone'); ?>
						Talk to a sign specialist: <a href="tel:+18664362101">866-436-2101</a>
						<span class="home-hero-help-hours">Mon&ndash;Fri, 8am&ndash;5pm PST</span>
					</p>
				</div>
				<div class="bd-hero-media" aria-hidden="true">
					<?php foreach ($hero_photos as $index => $photo) : ?>
						<?php $image = $product_image($photo[0], $photo[1], '(max-width: 575px) 45vw, (max-width: 991px) 22vw, 260px', 0 === $index ? 'high' : true); ?>
						<?php if ($image) : ?>
							<div class="bd-hero-tile bd-hero-tile--<?php echo (int) $index + 1; ?>"><?php echo $image; ?></div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<div class="home-hero-features bd-features" aria-label="Why order from us">
		<div class="container home-hero-feature-grid">
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('check'); ?></span>
				<span><strong>See Your Price Online</strong><small>No waiting on a quote.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('clock'); ?></span>
				<span><strong>Next-Day Turnaround</strong><small>Available on many products.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('badge'); ?></span>
				<span><strong>Sign Makers Since 2002</strong><small>Help from real specialists.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('flag'); ?></span>
				<span><strong>Graphic-Only Refills</strong><small>Keep the hardware you own.</small></span>
			</div>
		</div>
	</div>

	<section class="sf-section" aria-labelledby="bd-lines-title">
		<div class="container">
			<p class="cl-kicker">Shop by product</p>
			<h2 id="bd-lines-title" class="cl-section-title">Banners, Stands, Flags &amp; Trade Show Displays</h2>
			<p class="cl-section-lead">Everything you need to promote a storefront, an event or a booth, from a single vinyl banner to a complete trade show display.</p>
			<div class="sf-type-grid">
				<?php foreach ($lines as $line) : ?>
					<?php
					$line_url = wholesale_category_url($line[0]);
					$line_price = wholesale_seo_product_starting_text($line[1]);
					$line_name = wp_strip_all_tags(html_entity_decode($line[2], ENT_QUOTES, 'UTF-8'));
					?>
					<article class="sf-type-card bd-card">
						<a class="sf-type-image" href="<?php echo esc_url($line_url); ?>" tabindex="-1" aria-hidden="true">
							<?php echo $product_image($line[1], $line_name, '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw'); ?>
						</a>
						<div class="sf-type-body">
							<h3><a href="<?php echo esc_url($line_url); ?>"><?php echo wp_kses_post($line[2]); ?></a></h3>
							<p><?php echo wp_kses_post($line[3]); ?></p>
							<?php if ($line_price) : ?>
								<p class="sf-type-price"><small>Starting at</small> <?php echo wp_kses_post($line_price); ?></p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="bd-occasion-title">
		<div class="container">
			<p class="cl-kicker">Shop by occasion</p>
			<h2 id="bd-occasion-title" class="cl-section-title">The Right Display for Every Campaign</h2>
			<p class="cl-section-lead">Not sure where to start? These are the combinations our customers order most for each kind of promotion.</p>
			<div class="bd-occasion-grid">
				<?php foreach ($occasions as $occasion) : ?>
					<article class="bd-occasion" id="<?php echo esc_attr($occasion[0]); ?>">
						<h3><?php echo wp_kses_post($occasion[1]); ?></h3>
						<p><?php echo wp_kses_post($occasion[2]); ?></p>
						<ul>
							<?php foreach ($occasion[3] as $slug => $label) : ?>
								<?php $url = $product_url($slug); ?>
								<?php if ($url) : ?>
									<li>
										<a href="<?php echo esc_url($url); ?>">
											<span><?php echo wp_kses_post($label); ?></span>
											<?php $price = wholesale_seo_product_starting_text($slug); ?>
											<?php if ($price) : ?><small><?php echo wp_kses_post($price); ?></small><?php endif; ?>
										</a>
									</li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="bd-best-title">
		<div class="container">
			<p class="cl-kicker">Best sellers</p>
			<h2 id="bd-best-title" class="cl-section-title">Popular Banners &amp; Displays</h2>
			<div class="bd-product-grid">
				<?php foreach ($best_sellers as $slug => $label) : ?>
					<?php
					$product = wholesale_seo_product($slug);
					if (!$product) {
						continue;
					}
					$name = wp_strip_all_tags(get_the_title($product));
					$price = wholesale_seo_product_starting_text($slug);
					?>
					<a class="bd-product" href="<?php echo esc_url(get_permalink($product)); ?>">
						<span class="bd-product-image"><?php echo $product_image($slug, $name, '(max-width: 575px) 50vw, (max-width: 991px) 33vw, 25vw'); ?></span>
						<span class="bd-product-tag"><?php echo wp_kses_post($label); ?></span>
						<span class="bd-product-name"><?php echo esc_html($name); ?></span>
						<?php if ($price) : ?>
							<span class="bd-product-price"><small>From</small> <?php echo wp_kses_post($price); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="bd-cta" aria-labelledby="bd-cta-title">
		<div class="container bd-cta-inner">
			<div>
				<h2 id="bd-cta-title">Planning a bigger campaign?</h2>
				<p>Multiple locations, an event series or a full trade show booth: send us the details and a specialist will put together one quote for everything.</p>
			</div>
			<div class="bd-cta-actions">
				<a class="home-hero-shop-button" href="<?php echo esc_url($contact_url); ?>">Request a Free Quote <span aria-hidden="true">&rarr;</span></a>
				<a class="home-hero-secondary-button" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> 866-436-2101</a>
			</div>
		</div>
	</section>

	<section class="cl-compare" aria-labelledby="bd-materials-title">
		<div class="container">
			<p class="cl-kicker">Compare materials</p>
			<h2 id="bd-materials-title" class="cl-section-title">Which Banner Material Should You Choose?</h2>
			<p class="cl-section-lead">The right material depends on where the banner hangs and how long it stays up.</p>
			<div class="cl-compare-table-wrap">
				<table class="cl-compare-table">
					<thead>
						<tr>
							<th scope="col">Material</th>
							<th scope="col">Best for</th>
							<th scope="col">Outdoors</th>
							<th scope="col">Starting at</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($materials as $material) : ?>
							<?php $url = $product_url($material[0]); ?>
							<?php if ($url) : ?>
								<tr>
									<th scope="row"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($material[1]); ?></a></th>
									<td><?php echo esc_html($material[2]); ?></td>
									<td><?php echo esc_html($material[3]); ?></td>
									<td class="cl-compare-price"><?php echo wp_kses_post(wholesale_seo_product_starting_text($material[0])); ?></td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="cl-compare bd-stands" aria-labelledby="bd-stands-title">
		<div class="container">
			<p class="cl-kicker">Compare stands</p>
			<h2 id="bd-stands-title" class="cl-section-title">Banner Stands Side by Side</h2>
			<p class="cl-section-lead">Order a complete stand with its printed graphic, or just the graphic for a stand you already own.</p>
			<div class="cl-compare-table-wrap">
				<table class="cl-compare-table">
					<thead>
						<tr>
							<th scope="col">Stand</th>
							<th scope="col">Setup time</th>
							<th scope="col">Best for</th>
							<th scope="col">Starting at</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($stands as $stand) : ?>
							<?php $url = $product_url($stand[0]); ?>
							<?php if ($url) : ?>
								<tr>
									<th scope="row"><a href="<?php echo esc_url($url); ?>"><?php echo wp_kses_post($stand[1]); ?></a></th>
									<td><?php echo esc_html($stand[2]); ?></td>
									<td><?php echo esc_html($stand[3]); ?></td>
									<td class="cl-compare-price"><?php echo wp_kses_post(wholesale_seo_product_starting_text($stand[0])); ?></td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="bd-guide-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Buying guide</p>
			<h2 id="bd-guide-title" class="cl-section-title">How to Plan a Banner That Works</h2>

			<h3>1. Start with where it will be seen</h3>
			<p>A banner on a building is read from the street or the parking lot, so it needs big, bold lettering and very few words. A banner stand in a lobby is read from a few feet away and can carry more detail. Decide on the viewing distance first, then size the banner and the text to suit it.</p>

			<h3>2. Keep the message short</h3>
			<p>Most people glance at a banner for a few seconds. Lead with one headline (&ldquo;Grand Opening&rdquo;, &ldquo;Now Hiring&rdquo;, &ldquo;50% Off&rdquo;), add your logo, and finish with a single next step such as a phone number or website. High-contrast colors, like dark text on a light background, read best from a distance.</p>

			<h3>3. Choose the material for the location</h3>
			<p><a href="<?php echo esc_url($product_url('13oz-vinyl-banner', $banners_url)); ?>">13oz vinyl</a> is the everyday outdoor banner. Go up to <a href="<?php echo esc_url($product_url('18oz-blockout-banner', $banners_url)); ?>">18oz blockout</a> when both sides will be seen, choose <a href="<?php echo esc_url($product_url('mesh-banner', $banners_url)); ?>">mesh</a> for fences and windy spots, and use <a href="<?php echo esc_url($product_url('fabric-banner-9oz-wrinkle-free-copy', $banners_url)); ?>">wrinkle-free fabric</a> for indoor backdrops and photos.</p>

			<h3>4. Finish it for how you&rsquo;ll hang it</h3>
			<p>Every banner is hemmed on all sides. Add grommets every 2 feet to tie it to a fence or wall, pole pockets to slide it onto a pole, webbing with D-rings for large banners under tension, and reinforced corners for windy locations. Choosing finishing on the product page updates your price straight away.</p>

			<h3>5. Plan for reuse</h3>
			<p>If you run promotions often, invest in hardware once and swap the print. <a href="<?php echo esc_url(wholesale_category_url('banner-stands')); ?>">Banner stands</a>, <a href="<?php echo esc_url(wholesale_category_url('advertising-flags')); ?>">flags</a> and <a href="<?php echo esc_url(wholesale_category_url('a-frame-and-sign-holders')); ?>">A-frames</a> are available as graphic-only refills, and you&rsquo;ll find replacement parts in <a href="<?php echo esc_url(wholesale_category_url('hardware-only')); ?>">hardware only</a>.</p>
		</div>
	</section>

	<section class="cl-steps" aria-labelledby="bd-steps-title">
		<div class="container">
			<p class="cl-kicker">Ordering process</p>
			<h2 id="bd-steps-title" class="cl-section-title">Order in Four Simple Steps</h2>
			<ol class="cl-steps-list">
				<li>
					<span class="cl-step-number" aria-hidden="true">1</span>
					<h3>Pick a product</h3>
					<p>Choose a banner, stand, flag or display above.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">2</span>
					<h3>Size &amp; price it</h3>
					<p>Enter your size and finishing; your price updates instantly.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">3</span>
					<h3>Upload artwork</h3>
					<p>Send print-ready files, or <a href="<?php echo esc_url($contact_url); ?>">ask us for design help</a>.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">4</span>
					<h3>We print &amp; ship</h3>
					<p>Choose standard, 3-day, 2-day or overnight shipping at checkout.</p>
				</li>
			</ol>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="bd-faq-title">
		<div class="container">
			<p class="cl-kicker">Before you order</p>
			<h2 id="bd-faq-title" class="cl-section-title">Banners &amp; Displays FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(true); ?>
			</div>
		</div>
	</section>

	<section class="cl-help" aria-labelledby="bd-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="bd-help-title">Talk to a Real Sign Specialist</h2>
				<p>Not sure which size, material or stand fits your space? Send us a photo and your artwork and we&rsquo;ll recommend the right setup.</p>
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
