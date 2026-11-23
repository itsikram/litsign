<?php
/**
 * Front page: the catalog hub, led by banners & displays.
 *
 * Loaded by home.php on the front page only; home.php's own listing still
 * serves the category pages. Banners, stands, flags, trade show displays and
 * tents come first, then every other product line, the channel letters, the
 * ordering steps and the company FAQ. Prices and photos are read from the
 * products, so they stay current; missing or unpublished products are skipped.
 *
 * The in-depth pages own their searches: /banners-displays/ (materials and
 * stand comparisons, buying guide) and /custom-channel-letters/ (styles,
 * heights, warranty). This page summarizes and links to them instead of
 * repeating their tables and FAQs.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$builder_url = home_url('/channel-letter-builder/');
$cl_landing_id = function_exists('wholesale_channel_letters_landing_id') ? wholesale_channel_letters_landing_id() : 0;
$cl_landing_url = $cl_landing_id ? get_permalink($cl_landing_id) : home_url('/custom-channel-letters/');
$bd_page = get_page_by_path('banners-displays');
$bd_url = $bd_page && 'publish' === $bd_page->post_status ? get_permalink($bd_page) : '';
$page_url = static function ($slug) {
	$page = get_page_by_path($slug);
	return $page && 'publish' === $page->post_status ? get_permalink($page) : '';
};
// The footer's next-day shipping note applies here (it stays off channel letter pages).
$GLOBALS['wholesale_channel_letter_landing'] = false;

$product_url = static function ($slug, $fallback = '') {
	$product = wholesale_seo_product($slug);
	return $product ? get_permalink($product) : $fallback;
};

// $eager: 'high' for the page's main image, true for other above-the-fold images.
$product_image = static function ($slug, $alt, $sizes, $eager = false, $size = 'medium_large') {
	$product = wholesale_seo_product($slug);
	if (!$product || !has_post_thumbnail($product)) {
		return '';
	}

	return get_the_post_thumbnail($product, $size, array(
		'loading' => $eager ? 'eager' : 'lazy',
		'fetchpriority' => 'high' === $eager ? 'high' : 'auto',
		'decoding' => 'async',
		'alt' => $alt,
		'sizes' => $sizes,
	));
};

// Card prices are whole dollars, except per-ft² prices (as on /banners-displays/):
// rounding $1.98 up to $2 would overstate the banner prices the ads quote.
$card_price = static function ($html) {
	return preg_match('/per ft/i', wp_strip_all_tags((string) $html)) ? $html : wholesale_price_no_cents($html);
};
// Without a leading "From" (sale prices read "From <del>$47.67</del> $42.90"):
// every price on this page already sits after "From" or "Starting at".
$price_of = static function ($slug) use ($card_price) {
	return $card_price(preg_replace('/^\s*from\s+/i', '', wholesale_seo_product_starting_text($slug)));
};

// Hero tiles: product slug, label. Each opens its product with the price.
$hero_photos = array(
	array('13oz-vinyl-banner', 'Vinyl Banners'),
	array('standard-retractable-insert-stand', 'Banner Stands'),
	array('feather-angled-flag-pole', 'Feather Flags'),
	array('10ft-seg-fabric-display-graphic-frame', 'Trade Show Displays'),
);

// "Shop all products" rail under the hero: every product line, banners & displays
// first. Category slug, product whose photo and price represent it, label, and an
// optional URL (channel letters open their landing page).
$rail = array(
	array('banners', '13oz-vinyl-banner', 'Banners'),
	array('banner-stands', 'standard-retractable-insert-stand', 'Banner Stands'),
	array('advertising-flags', 'feather-angled-flag-pole', 'Flags'),
	array('trade-show-products', 'straight-tension-fabric-displays-graphic-frame', 'Trade Show Displays'),
	array('seg-products', '10ft-seg-fabric-display-graphic-frame', 'SEG Displays'),
	array('custom-event-tents', 'event-tent-full-canopy-graphic-frame', 'Event Tents'),
	array('table-throws', '6ft-table-cover', 'Table Covers'),
	array('a-frame-and-sign-holders', 'banner-a-banner-frame', 'A-Frames &amp; Holders'),
	array('adhesive-products', 'adhesive-window-perf', 'Window Graphics'),
	array('channel-letters', 'standard-channel-letter-front-lit', 'Channel Letters', $cl_landing_url),
	array('signicade-a-frames', 'deluxe-signicade-graphic-frame', 'Sidewalk A-Frames'),
	array('rigid-signs-and-magnets', 'coroplast', 'Rigid Signs &amp; Magnets'),
	array('reflective-products', 'reflective-aluminum-sign', 'Reflective'),
	array('real-estate-products', 'yard-sign-and-h-stake', 'Real Estate'),
	array('dry-erase-products', 'dry-erase-signicade-a-frame', 'Dry Erase'),
	array('wall-art', 'canvas-wrap', 'Wall Art'),
	array('large-format', 'posters', 'Posters &amp; Prints'),
	array('hardware-only', 'sd-retractable-hardware-only', 'Hardware'),
);

// Banners & displays product lines: category slug, product whose photo and price
// represent it, name, summary.
$bd_lines = array(
	array('banners', '13oz-vinyl-banner', 'Vinyl &amp; Fabric Banners', 'Any size in 13oz or 18oz vinyl, mesh or fabric, finished with grommets, pole pockets or hems.'),
	array('banner-stands', 'standard-retractable-insert-stand', 'Banner Stands', 'Retractable, X-stand and table top stands, shipped with your printed graphic.'),
	array('advertising-flags', 'feather-angled-flag-pole', 'Feather &amp; Teardrop Flags', 'Printed flags with poles and bases for roadsides, parking lots and events.'),
	array('trade-show-products', 'straight-tension-fabric-displays-graphic-frame', 'Trade Show Displays', 'Straight and curved fabric backwalls that pack down for the trip home.'),
	array('seg-products', '10ft-seg-fabric-display-graphic-frame', 'SEG Fabric Displays', 'Silicone edge graphics in aluminum frames, including backlit walls.'),
	array('custom-event-tents', 'event-tent-full-canopy-graphic-frame', 'Event Tents', 'Full color canopies with matching walls and tent flags.'),
	array('table-throws', '6ft-table-cover', 'Table Covers', 'Fitted, stretch and round covers plus runners for 4, 6 and 8 ft tables.'),
	array('a-frame-and-sign-holders', 'banner-a-banner-frame', 'A-Frames &amp; Poster Stands', 'Banner A-frames, poster stands and snap hangers for sidewalks and lobbies.'),
);

// Everyday banners & displays: product slug => short label. Products the hero and
// the line cards above already show are left out, so this grid adds new choices.
$everyday = array(
	'18oz-blockout-banner' => 'Seen from both sides',
	'mesh-banner' => 'Fences &amp; windy spots',
	'fabric-banner-9oz-wrinkle-free-copy' => 'Wrinkle-free indoor fabric',
	'x-stand-graphic-stand' => 'Lightest indoor stand',
	'table-top-banner-stand' => 'Counters &amp; check-in desks',
	'teardrop-flag-pole' => 'Curved roadside flag',
	'step-and-repeat-backdrop-graphic-frame' => 'Photo &amp; press backdrop',
	'stretch-table-cover' => 'Fitted booth table',
);

// Shop by project, across the whole catalog: id, heading, intro, array(product slug => label).
$projects = array(
	array('project-storefront', 'Storefront &amp; Windows', 'Make the building itself do the selling, day and night.', array(
		'standard-channel-letter-front-lit' => 'Lit channel letter sign',
		'adhesive-window-perf' => 'See-through window graphic',
		'adhesive-products' => 'Store hours &amp; logo decal',
		'deluxe-signicade-graphic-frame' => 'Sidewalk A-frame',
	)),
	array('project-opening', 'Grand Openings &amp; Sales', 'Tell the whole street you&rsquo;re open, then point them to the door.', array(
		'13oz-vinyl-banner' => 'Grand opening banner',
		'feather-angled-flag-pole' => 'Roadside feather flag',
		'banner-a-banner-frame' => 'Banner A-frame',
		'x-stand-graphic-stand' => 'In-store sale stand',
	)),
	array('project-events', 'Trade Shows &amp; Events', 'A booth or a market stand that looks finished and travels well.', array(
		'10ft-seg-fabric-display-graphic-frame' => 'Booth backwall',
		'standard-retractable-insert-stand' => 'Retractable stand',
		'event-tent-full-canopy-graphic-frame' => 'Canopy tent',
		'6ft-table-cover' => 'Printed table cover',
	)),
	array('project-property', 'Property &amp; Job Sites', 'For listings, construction fences, parking lots and building entrances.', array(
		'real-estate-post-sign' => 'Real estate post',
		'yard-sign-and-h-stake' => 'Yard sign &amp; H-stake',
		'mesh-banner' => 'Fence mesh banner',
		'aluminum-sign' => 'Aluminum panel',
	)),
);

// Every other product line: category slug, product whose photo and price represent it,
// name, summary.
$more_lines = array(
	array('adhesive-products', 'adhesive-window-perf', 'Window Graphics &amp; Vinyl', 'Window perf, frosted film, clings, wall and floor graphics, printed to size.'),
	array('signicade-a-frames', 'deluxe-signicade-graphic-frame', 'Sidewalk A-Frames', 'Signicade frames with double-sided prints you can swap for each promotion.'),
	array('rigid-signs-and-magnets', 'coroplast', 'Rigid Signs &amp; Magnets', 'Aluminum, PVC, Coroplast and foam board panels, plus vehicle magnets.'),
	array('reflective-products', 'reflective-aluminum-sign', 'Reflective Signs', 'Reflective aluminum, Coroplast and vinyl that shine back in headlights.'),
	array('real-estate-products', 'yard-sign-and-h-stake', 'Real Estate Signs', 'Post and frame signs, A-frames and yard signs with stakes.'),
	array('dry-erase-products', 'dry-erase-signicade-a-frame', 'Dry Erase Signs', 'Write-on A-frames, boards and vinyl for menus and daily specials.'),
	array('wall-art', 'canvas-wrap', 'Wall Art Prints', 'Canvas wraps and acrylic prints for offices, lobbies and dining rooms.'),
	array('large-format', 'posters', 'Posters &amp; Large Format', 'Posters, backlit film and pop up graphics, printed to the size you need.'),
);

// Hardware without graphics: category slug, label.
$hardware = array(
	array('hardware-only', 'Display hardware'),
	array('banner-stand-hardware', 'Banner stand hardware'),
	array('flag-hardware', 'Flag poles &amp; bases'),
	array('event-tent-hardware-only', 'Event tent hardware'),
);

// One query for every product this page shows (instead of one lookup per slug).
wholesale_seo_prime_products(array_merge(
	array_column($hero_photos, 0),
	array_column($rail, 1),
	array_column($bd_lines, 1),
	array_keys($everyday),
	array_merge(...array_map(static function ($project) {
		return array_keys($project[3]);
	}, $projects)),
	array_column($more_lines, 1)
));

$cl_price = wholesale_price_no_cents(wholesale_seo_product_starting_text('standard-channel-letter-front-lit'));
$banner_price = $price_of('13oz-vinyl-banner');
$free_shipping = function_exists('wholesale_free_shipping_amount') ? wholesale_free_shipping_amount() : '';
$free_shipping_cl = function_exists('wholesale_free_shipping_amount') ? wholesale_free_shipping_amount(true) : '';

// The everyday products are this page's item list in structured data.
$GLOBALS['wholesale_page_items'] = array_values(array_filter(array_map(static function ($slug) {
	return wholesale_seo_product($slug);
}, array_keys($everyday))));

$home_link = static function ($url, $text) {
	return $url ? '<a href="' . esc_url($url) . '">' . $text . '</a>' : $text;
};
$industries_url = $page_url('industries');
$locations_url = $page_url('locations');
$install_url = $page_url('how-to-install-channel-letters');

// Company and catalog questions. Banner and stand details are answered on
// /banners-displays/, channel letter details on /custom-channel-letters/.
wholesale_seo_set_page_faq(array_values(array_filter(array(
	array(
		'q' => 'What does Storefront Sign Online make?',
		'a' => 'Printed ' . $home_link(wholesale_category_url('banners'), 'banners') . ', ' . $home_link(wholesale_category_url('banner-stands'), 'banner stands') . ', ' . $home_link(wholesale_category_url('advertising-flags'), 'flags') . ', ' . $home_link(wholesale_category_url('trade-show-products'), 'trade show displays') . ', event tents and table covers, plus ' . $home_link(wholesale_category_url('adhesive-products'), 'window graphics') . ', sidewalk A-frames, rigid and reflective signs, and LED ' . $home_link($cl_landing_url, 'channel letters') . '. ' . $home_link(wholesale_shop_url(), 'Browse every product') . '.',
	),
	array(
		'q' => 'How do I see what my order will cost?',
		'a' => 'Open any product and choose a size and options; your price updates before you add it to the cart. Banners and vinyl are priced by the square foot, stands, flags and displays come as a set with the printed graphic, and channel letters are priced per letter by height. For a large or mixed order, ' . $home_link($contact_url, 'request a free quote') . '.',
	),
	array(
		'q' => 'How quickly will my order arrive?',
		'a' => 'Many banners, displays and printed products have next-day turnaround when you order and send artwork by 4pm PST, and some can ship the same day when artwork is in by 12pm PST. Each product shows its turnaround, and you choose standard (3&ndash;6 business days), 3-day, 2-day or overnight shipping at checkout. Channel letters are built to order, with the estimated ship date shown at checkout.',
	),
	$free_shipping ? array(
		'q' => 'Do you offer free shipping?',
		'a' => 'Yes. Orders of ' . esc_html($free_shipping) . ' or more can choose free shipping (3&ndash;6 business days) at checkout' . ($free_shipping_cl ? '; orders that include channel letters qualify at ' . esc_html($free_shipping_cl) : '') . '.',
	) : null,
	array(
		'q' => 'Where are your products made, and where do you ship?',
		'a' => 'Banners, displays, window graphics and our other printed products are made in Renton, WA, and channel letters are built in Washington and California. Everything ships to all 50 states. Pickup isn&rsquo;t available.',
	),
	array(
		'q' => 'Can I order just the graphic for a stand or flag I already own?',
		'a' => 'Yes. Most banner stands, flags, A-frames and fabric displays are also sold as the printed graphic only, and poles, bases, frames and stands without graphics are in ' . $home_link(wholesale_category_url('hardware-only'), 'display hardware') . '.',
	),
	array(
		'q' => 'Can you help with my artwork or logo?',
		'a' => 'Yes. Upload your logo with a ' . $home_link($contact_url, 'quote request') . ' or text it to <a href="sms:+12066186543">206-618-6543</a>, and a specialist will help with sizing, materials and artwork, usually within one business day.',
	),
	array(
		'q' => 'Do you install signs?',
		'a' => 'No. We make and ship. Banners and displays are ready to hang or set up, and every lit sign ships tested with a wiring diagram and install pattern for your licensed electrician or sign installer.' . ($install_url ? ' Our ' . $home_link($install_url, 'channel letter installation guide') . ' explains what they will do.' : ''),
	),
	($industries_url || $locations_url) ? array(
		'q' => 'Do you make products for my type of business?',
		'a' => 'We supply retail stores, restaurants, salons, medical offices, real estate agents, event planners and trade show exhibitors across the country.' . ($industries_url ? ' Our ' . $home_link($industries_url, 'industry guides') . ' suggest what to order for your business' : '') . ($locations_url ? ($industries_url ? ', and our ' : ' Our ') . $home_link($locations_url, 'location guides') . ' cover local sign rules' : '') . '.',
	) : null,
))));

// The banners & displays photo, with the home page's own image as the fallback.
// Set before get_header() so the head preloads this file (wholesale_preload_front_page_hero()).
$hero_image = $bd_page ? get_the_post_thumbnail_url($bd_page, 'large') : '';
$hero_image = $hero_image ? $hero_image : get_the_post_thumbnail_url((int) get_option('page_on_front'), 'large');
$hero_image = $hero_image ? wholesale_webp_url(esc_url_raw($hero_image)) : '';
$hero_background = wholesale_hero_background_style($hero_image, wholesale_default_hero_url());

// Share previews and the page's structured data use the same banners & displays
// photo (the original file, not the WebP copy).
$share_image = $bd_page ? get_the_post_thumbnail_url($bd_page, 'large') : '';
if ($share_image) {
	$GLOBALS['wholesale_page_image'] = $share_image;
}
add_filter('wholesale_seo_webpage_schema', static function ($page) use ($share_image) {
	$organization_id = trailingslashit(home_url('/')) . '#organization';
	$page['about'] = array('@id' => $organization_id);
	if (defined('WHOLESALE_HOME_PAGE_REVISED')) {
		$page['dateModified'] = WHOLESALE_HOME_PAGE_REVISED;
	}
	if ($share_image) {
		$page['primaryImageOfPage'] = array('@type' => 'ImageObject', 'url' => $share_image);
	}
	return $page;
});

get_header();

$google_reviews = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;
$google_rating = $google_reviews && !empty($google_reviews['count']) ? $google_reviews : null;
?>

<main id="primary" class="site-main hc-page">
	<section class="bd-hero hc-hero" aria-labelledby="home-hero-title" style="<?php echo esc_attr($hero_background); ?>">
		<div class="container">
			<div class="bd-hero-grid">
				<div class="bd-hero-copy">
					<p class="home-hero-eyebrow">Printed to order &middot; Ships to all 50 states &middot; Since 2002</p>
					<h1 id="home-hero-title" class="bd-hero-title">Custom Banners, Displays &amp; Signs <span>for Your Business</span></h1>
					<?php if ($google_rating) : ?>
						<p class="home-hero-proof">
							<a class="home-hero-rating" href="#customer-reviews"><?php echo wholesale_review_stars($google_rating['rating']); ?> <strong><?php echo esc_html(number_format((float) $google_rating['rating'], 1)); ?></strong> on Google (<?php echo esc_html(number_format_i18n((int) $google_rating['count'])); ?>)</a>
						</p>
					<?php endif; ?>
					<p class="home-hero-lead">Vinyl banners, banner stands, feather flags, trade show displays and event tents, plus window graphics, A-frames and LED channel letters. Choose a product, enter your size and see your price before you check out.</p>
					<div class="home-hero-actions home-hero-actions--cl">
						<a class="home-hero-shop-button" href="#banners-displays"><span class="home-hero-button-text">Shop Banners &amp; Displays<?php if ($banner_price) : ?><small>Banners from <?php echo wp_kses_post($banner_price); ?></small><?php endif; ?></span> <span aria-hidden="true">&rarr;</span></a>
						<a class="home-hero-secondary-button" href="<?php echo esc_url($contact_url); ?>"><?php echo wholesale_home_icon('message'); ?> Get a Free Quote</a>
					</div>
					<p class="home-hero-help">
						<?php echo wholesale_home_icon('phone'); ?>
						Talk to a specialist: <a href="tel:+18664362101">866-436-2101</a>
						<span class="home-hero-help-hours">Mon&ndash;Fri, 8am&ndash;5pm PT &middot; or <a href="sms:+12066186543">text your logo</a></span>
					</p>
				</div>
				<div class="bd-hero-media">
					<?php foreach ($hero_photos as $index => $photo) : ?>
						<?php
						// The hero background is the page's largest paint, so no tile asks for
						// high priority. On phones the first row only peeks above the fold and
						// the second row is below it, so those load lazily (on wider screens
						// they are in view and still load as soon as the page lays out).
						$image = $product_image($photo[0], '', '(max-width: 575px) 45vw, (max-width: 991px) 22vw, 260px', $index < 2);
						$url = $product_url($photo[0]);
						if (!$image || !$url) {
							continue;
						}
						$price = $price_of($photo[0]);
						?>
						<a class="bd-hero-tile bd-hero-tile--<?php echo (int) $index + 1; ?>" href="<?php echo esc_url($url); ?>">
							<span class="bd-hero-tile-image"><?php echo $image; ?></span>
							<span class="bd-hero-tile-label"><?php echo esc_html($photo[1]); ?><?php if ($price) : ?><small>From <?php echo wp_kses_post($price); ?></small><?php endif; ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<div class="home-hero-features bd-features" aria-label="Why order from us">
		<div class="container home-hero-feature-grid">
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('check'); ?></span>
				<span><strong>See Your Price Online</strong><small>Enter a size, get your total.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('flag'); ?></span>
				<?php if ($free_shipping) : ?>
					<span><strong>Free Shipping Over <?php echo esc_html($free_shipping); ?></strong><small>To all 50 states.</small></span>
				<?php else : ?>
					<span><strong>Ships to All 50 States</strong><small>Standard to overnight.</small></span>
				<?php endif; ?>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('clock'); ?></span>
				<span><strong>Fast Turnaround</strong><small>Next-day on many products.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('badge'); ?></span>
				<span><strong>Sign Makers Since 2002</strong><small>Real people on the phone.</small></span>
			</div>
		</div>
	</div>

	<nav class="home-types hc-types" aria-label="Shop all products">
		<div class="container">
			<div class="home-types-head">
				<p class="home-types-title">Shop all products</p>
				<a class="home-types-all" href="<?php echo esc_url(wholesale_shop_url()); ?>">View all products <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
			</div>
			<div class="home-types-rail" data-types-rail>
				<button type="button" class="home-types-arrow home-types-arrow--prev" data-types-prev aria-label="Scroll products left" hidden><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
				<ul class="home-types-list" data-types-list>
					<?php foreach ($rail as $item) : ?>
						<?php
						$item_product = wholesale_seo_product($item[1]);
						$item_url = isset($item[3]) ? $item[3] : wholesale_category_url($item[0]);
						$item_price = 'channel-letters' === $item[0] ? $cl_price : $price_of($item[1]);
						?>
						<li>
							<a class="home-types-item" href="<?php echo esc_url($item_url); ?>">
								<span class="home-types-image">
									<?php
									if ($item_product && has_post_thumbnail($item_product)) {
										echo get_the_post_thumbnail($item_product, 'product-card', array('sizes' => '(max-width: 575px) 104px, 140px', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => ''));
									}
									?>
								</span>
								<span class="home-types-text">
									<strong><?php echo wp_kses_post($item[2]); ?></strong>
									<?php if ($item_price) : ?><small><span>From</span> <?php echo wp_kses_post($item_price); ?></small><?php endif; ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<button type="button" class="home-types-arrow home-types-arrow--next" data-types-next aria-label="Scroll products right" hidden><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
			</div>
		</div>
	</nav>

	<section class="sf-section" id="banners-displays" aria-labelledby="hc-bd-title">
		<div class="container">
			<p class="cl-kicker">Banners &amp; displays</p>
			<h2 id="hc-bd-title" class="cl-section-title">Shop Banners &amp; Displays</h2>
			<p class="cl-section-lead">Promote a storefront, an event or a booth: printed in full color, finished the way you&rsquo;ll hang or set it up, and priced as you choose your size.</p>
			<div class="sf-type-grid">
				<?php foreach ($bd_lines as $line) : ?>
					<?php
					$line_url = wholesale_category_url($line[0]);
					$line_price = $price_of($line[1]);
					$line_name = wp_strip_all_tags(html_entity_decode($line[2], ENT_QUOTES, 'UTF-8'));
					?>
					<article class="sf-type-card bd-card">
						<a class="sf-type-image" href="<?php echo esc_url($line_url); ?>" tabindex="-1" aria-hidden="true">
							<?php echo $product_image($line[1], $line_name, '(max-width: 575px) 50vw, (max-width: 991px) 50vw, 25vw'); ?>
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
			<?php if ($bd_url) : ?>
				<p class="hc-more-link"><a href="<?php echo esc_url($bd_url); ?>">Compare materials and stands in our banners &amp; displays guide <?php echo wholesale_home_icon('arrow'); ?></a></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="hc-everyday-title">
		<div class="container">
			<p class="cl-kicker">Quick picks</p>
			<h2 id="hc-everyday-title" class="cl-section-title">Everyday Banners &amp; Displays</h2>
			<p class="cl-section-lead">Double-sided, mesh and fabric banners, compact stands, backdrops and flags. Open one to choose a size and see your price.</p>
			<div class="bd-product-grid">
				<?php foreach ($everyday as $slug => $label) : ?>
					<?php
					$product = wholesale_seo_product($slug);
					if (!$product) {
						continue;
					}
					$name = wp_strip_all_tags(get_the_title($product));
					$price = $price_of($slug);
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

	<?php
	// Reviews right after the products instead of above the footer; the footer's
	// call then renders nothing (the slider prints once per page).
	wholesale_render_review_slider();
	?>

	<section class="sf-section sf-section--tint" aria-labelledby="hc-projects-title">
		<div class="container">
			<p class="cl-kicker">Shop by project</p>
			<h2 id="hc-projects-title" class="cl-section-title">Signs &amp; Displays for Every Project</h2>
			<p class="cl-section-lead">What are you promoting? These products work well together for the most common jobs, from a new storefront to a property listing.</p>
			<div class="bd-occasion-grid">
				<?php foreach ($projects as $project) : ?>
					<article class="bd-occasion" id="<?php echo esc_attr($project[0]); ?>">
						<h3><?php echo wp_kses_post($project[1]); ?></h3>
						<p><?php echo wp_kses_post($project[2]); ?></p>
						<ul>
							<?php foreach ($project[3] as $slug => $label) : ?>
								<?php $url = $product_url($slug); ?>
								<?php if ($url) : ?>
									<?php $price = 'standard-channel-letter-front-lit' === $slug ? $cl_price : $price_of($slug); ?>
									<li>
										<a href="<?php echo esc_url($url); ?>">
											<span><?php echo wp_kses_post($label); ?></span>
											<?php if ($price) : ?><small>From <?php echo wp_kses_post($price); ?></small><?php endif; ?>
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

	<section class="sf-section" aria-labelledby="hc-more-title">
		<div class="container">
			<p class="cl-kicker">More products</p>
			<h2 id="hc-more-title" class="cl-section-title">Window Graphics, Signs &amp; Prints</h2>
			<p class="cl-section-lead">The rest of the catalog, each with its price online.</p>
			<div class="sf-type-grid">
				<?php foreach ($more_lines as $line) : ?>
					<?php
					$line_url = wholesale_category_url($line[0]);
					$line_price = $price_of($line[1]);
					$line_name = wp_strip_all_tags(html_entity_decode($line[2], ENT_QUOTES, 'UTF-8'));
					?>
					<article class="sf-type-card bd-card">
						<a class="sf-type-image" href="<?php echo esc_url($line_url); ?>" tabindex="-1" aria-hidden="true">
							<?php echo $product_image($line[1], $line_name, '(max-width: 575px) 50vw, (max-width: 991px) 50vw, 25vw'); ?>
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
			<div class="hc-hardware">
				<p class="hc-hardware-title">Hardware only:</p>
				<ul>
					<?php foreach ($hardware as $item) : ?>
						<li><a href="<?php echo esc_url(wholesale_category_url($item[0])); ?>"><?php echo wp_kses_post($item[1]); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<a class="hc-hardware-all" href="<?php echo esc_url(wholesale_shop_url()); ?>">Browse every product <?php echo wholesale_home_icon('arrow'); ?></a>
			</div>
		</div>
	</section>

	<section class="hc-cl sf-section--tint" aria-labelledby="hc-cl-title">
		<div class="container hc-cl-inner">
			<a class="hc-cl-image" href="<?php echo esc_url($cl_landing_url); ?>" tabindex="-1" aria-hidden="true">
				<?php echo $product_image('standard-channel-letter-front-lit', '', '(max-width: 767px) 100vw, 40vw'); ?>
			</a>
			<div class="hc-cl-copy">
				<p class="cl-kicker">Lit storefront signs</p>
				<h2 id="hc-cl-title" class="cl-section-title">LED Channel Letters, Built in Washington &amp; California</h2>
				<p>Put your name above the door in UL listed LED letters, made to order in seven lighting styles<?php if ($cl_price) : ?> from <?php echo wp_kses_post($cl_price); ?><?php endif; ?>. Every style carries a five-year warranty and ships tested, with a wiring diagram and install pattern for your licensed electrician or sign installer.</p>
				<div class="hc-cl-actions">
					<a class="cl-button" href="<?php echo esc_url($cl_landing_url); ?>">Compare the 7 Styles <?php echo wholesale_home_icon('arrow'); ?></a>
					<a class="hc-cl-secondary" href="<?php echo esc_url($builder_url); ?>"><?php echo wholesale_home_icon('pen'); ?> Design your sign online</a>
				</div>
			</div>
		</div>
	</section>

	<section class="bd-cta" aria-labelledby="hc-cta-title">
		<div class="container bd-cta-inner">
			<div>
				<h2 id="hc-cta-title">Outfitting a store, an event or a booth?</h2>
				<p>Send us the list (banners, stands, flags, window graphics, signs) and a specialist will put it all in one quote.</p>
			</div>
			<div class="bd-cta-actions">
				<a class="home-hero-shop-button" href="<?php echo esc_url($contact_url); ?>">Request a Free Quote <span aria-hidden="true">&rarr;</span></a>
				<a class="home-hero-secondary-button" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> 866-436-2101</a>
			</div>
		</div>
	</section>

	<section class="cl-steps" aria-labelledby="hc-steps-title">
		<div class="container">
			<p class="cl-kicker">How it works</p>
			<h2 id="hc-steps-title" class="cl-section-title">How to Order Custom Banners &amp; Signs</h2>
			<ol class="cl-steps-list">
				<li>
					<span class="cl-step-number" aria-hidden="true">1</span>
					<h3>Choose a product</h3>
					<p>Banners, stands, flags, displays, window graphics or signs.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">2</span>
					<h3>Enter your size</h3>
					<p>Pick your size and options; your price updates as you go.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">3</span>
					<h3>Add your artwork</h3>
					<p>Upload print-ready files, or <a href="<?php echo esc_url($contact_url); ?>">ask us for design help</a>.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">4</span>
					<h3>We make &amp; ship it</h3>
					<p>Standard, 3-day, 2-day or overnight shipping to all 50 states.</p>
				</li>
			</ol>
		</div>
	</section>

	<?php
	// Who makes it: the company facts in a few lines, linked to the pages that
	// cover them in full (owner-confirmed facts only; see the FAQ above for the same).
	$about_url = $page_url('about');
	$reviews_url = function_exists('wholesale_reviews_page_url') ? wholesale_reviews_page_url() : $page_url('reviews');
	?>
	<section class="sf-section hc-about" aria-labelledby="hc-about-title">
		<div class="container sf-prose">
			<p class="cl-kicker">About us</p>
			<h2 id="hc-about-title" class="cl-section-title">Custom Banners &amp; Signs from Renton, WA, Since 2002</h2>
			<p>Storefront Sign Online has been making signs for businesses since 2002. Banners, flags, displays, window graphics and our other printed products are made in Renton, Washington, and our LED channel letters are built in Washington and California. Every product page shows its price as you choose a size and options, so you can order a single banner or a complete trade show booth without waiting on a quote.</p>
			<p>Prefer to talk it through? A sign specialist answers the phone Monday to Friday, 8am to 5pm Pacific, and can help with sizing, materials and artwork. We ship to all 50 states but don&rsquo;t install, so lit signs arrive tested with a wiring diagram for your electrician.<?php if ($about_url || $reviews_url) : ?> <?php echo $about_url ? $home_link($about_url, 'Learn more about us') : ''; ?><?php echo $about_url && $reviews_url ? ' or ' : ''; ?><?php echo $reviews_url ? $home_link($reviews_url, ($about_url ? 'read' : 'Read') . ' customer reviews') : ''; ?>.<?php endif; ?></p>
			<?php // Owner-confirmed facts only (since 2002, 50 states, 5-year warranty on every channel letter style). ?>
			<ul class="hc-facts">
				<li><strong>2002</strong><span>Making signs since</span></li>
				<li><strong>50</strong><span>States we ship to</span></li>
				<?php if ($google_rating) : ?>
					<li><strong><?php echo esc_html(number_format((float) $google_rating['rating'], 1)); ?>&#9733;</strong><span>Google rating</span></li>
				<?php endif; ?>
				<li><strong>5-year</strong><span>Channel letter warranty</span></li>
			</ul>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="hc-faq-title">
		<div class="container">
			<p class="cl-kicker">Before you order</p>
			<h2 id="hc-faq-title" class="cl-section-title">Questions About Storefront Sign Online</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
			<p class="cl-section-lead cl-faq-note">
				<?php if ($bd_url) : ?>Banner sizes, materials and stands: see the <a href="<?php echo esc_url($bd_url); ?>#bd-faq-title">banners &amp; displays FAQ</a>. <?php endif; ?>Channel letter styles and heights: see the <a href="<?php echo esc_url($cl_landing_url); ?>#cl-faq-title">channel letter FAQ</a>.
			</p>
		</div>
	</section>

	<section class="cl-help" aria-labelledby="hc-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="hc-help-title">Talk to a Real Specialist</h2>
				<p>Not sure which size, material or stand you need? Send a photo of the space or your logo and we&rsquo;ll help you choose before you order.</p>
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

	<nav class="cl-mobile-bar" aria-label="Quick actions">
		<button type="button" class="cl-mobile-bar-products" aria-controls="megaMenu"><?php echo wholesale_home_icon('grid'); ?> All Products</button>
		<a class="cl-mobile-bar-quote" href="<?php echo esc_url($contact_url); ?>">Free Quote</a>
		<a class="cl-mobile-bar-shop" href="#banners-displays">See Prices</a>
	</nav>
</main>

<?php
get_footer();
