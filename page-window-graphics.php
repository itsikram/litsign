<?php
/**
 * Window Graphics & Vinyl campaign landing page (/window-graphics/).
 *
 * Landing page for the "SSO - Window Graphics & Vinyl - USA" Google Ads
 * campaign: window perf, frosted, clear and cling films, printed decals, wall
 * and floor graphics. Prices and photos are read from the AV products so they
 * stay current; products that are missing or unpublished are skipped.
 *
 * Each ad group links here with ?type=<key> (see $types) so the hero headline
 * matches the ad. The canonical URL stays /window-graphics/.
 *
 * Reuses the banners & displays layout (css/banners-displays.css) plus
 * css/window-graphics.css for this page's own sections.
 *
 * @package litsign
 */

$category_url = wholesale_category_url('adhesive-products');

// Google Ads landing page: an on-page quote form (#quote) that saves the ad
// click and fires the lead conversion (inc/quote-form.php).
$quote_form = array(
	'prefix' => 'wgq',
	'page_url' => get_permalink(),
	'project_label' => 'Window graphics & vinyl',
	'interests' => array('Window perf (one-way vision)', 'Frosted window film', 'Clear window vinyl', 'Window clings', 'Printed vinyl decals', 'Wall graphics', 'Floor graphics', 'Not sure yet'),
);
wholesale_quote_form_handle($quote_form);
wholesale_track_quote_lead('window_graphics_page');
// Quote links on this page jump to the form instead of leaving for /contact/.
$contact_url = '#quote';

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

// Films: key (anchor id and ?type= value), product slug, name, summary, label.
// Facts come from the product buying guides (inc/product-guides-catalog.php).
$films = array(
	array('perf', 'adhesive-window-perf', 'Window Perf (One-Way Vision)', 'Full color graphic outside, a clear view out from inside. 70/30 perforated film with a black backing for storefront and office windows.', 'See-through from inside'),
	array('frosted', 'frosted-vinyl-etched', 'Frosted Window Film', 'The look of etched glass with your logo printed in. Lets diffuse light through while blocking a clear view, for offices, clinics and conference rooms.', 'Privacy + branding'),
	array('clear', 'adhesive-clear-vinyl', 'Clear Window Vinyl', 'Ultra clear film so only your design shows on the glass. Ideal for logos, store hours and door decals.', 'Logos &amp; store hours'),
	array('cling', 'premium-window-cling', 'Window Clings', 'Low tack 8 mil cling that peels off with no residue, so you can reuse it for sales, seasons and events.', 'Removable &amp; reusable'),
	array('decal', 'adhesive-products', 'Printed Vinyl Decals', 'Our everyday 4 mil white vinyl with a permanent adhesive for decals, door signs and equipment labels.', 'Lowest price per sq ft'),
	array('removable', 'adhesive-vinyl-high-performance', 'Removable Adhesive Vinyl', 'Air release backing for bubble free installs and an adhesive that comes off when the promotion ends.', 'Bubble free install'),
	array('backlit', 'adhesive-translucent-vinyl', 'Translucent Vinyl for Lit Signs', 'Printed vinyl for light box faces, lit sign panels and backlit window displays.', 'Light boxes'),
	array('wall', 'adhesive-wall-fabric', 'Wall Graphics &amp; Murals', 'Repositionable printed wall fabric that is safe for painted walls and doesn&rsquo;t crease while you hang it.', 'Safe on paint'),
	array('floor', 'floor-graphics', 'Floor Graphics', 'Floor vinyl with a textured, non-slip lamination built for heavy foot traffic on indoor floors.', 'Non-slip laminate'),
);

// Ad group headlines (?type=key). Anything else shows the default hero.
$types = array(
	'perf' => array('Custom Window Perf Printing', 'One-way vision window graphics: your full color design faces the street and you still see out from inside.'),
	'frosted' => array('Custom Frosted Window Film', 'Printed frosted vinyl that gives glass the etched look, adds privacy and still lets light through.'),
	'clear' => array('Custom Clear Window Decals', 'Ultra clear vinyl for logos, store hours and door graphics, so only your design shows on the glass.'),
	'cling' => array('Custom Printed Window Clings', 'Removable window graphics for sales and seasons. They peel off clean and go back up again and again.'),
	'decal' => array('Custom Printed Vinyl Decals', 'Full color decals and stickers for doors, walls and equipment, printed to the size you need.'),
	'wall' => array('Custom Wall Graphics &amp; Murals', 'Printed wall fabric that repositions while you hang it and is safe for painted walls.'),
	'floor' => array('Custom Floor Graphics', 'Printed floor decals with a non-slip laminate for wayfinding, queues and in-store promotions.'),
);
$type = isset($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : '';
$type = isset($types[$type]) ? $type : '';

// Which film for which job: product slug, film, view out from inside, removable, best for.
$compare = array(
	array('adhesive-window-perf', 'Window Perf', 'Yes', 'No', 'Full window coverage you can see out of'),
	array('frosted-vinyl-etched', 'Frosted Film', 'Light only', 'No', 'Privacy glass with a logo'),
	array('adhesive-clear-vinyl', 'Clear Vinyl', 'Yes, around the print', 'No', 'Logos, hours and door decals'),
	array('premium-window-cling', 'Window Cling', 'No', 'Yes, reusable', 'Sales and seasonal promotions'),
	array('adhesive-products', 'White Vinyl (AV 1)', 'No', 'No', 'Opaque decals and labels'),
	array('adhesive-vinyl-high-performance', 'Removable Vinyl (AV 2)', 'No', 'Yes', 'Large graphics you will change later'),
);

// Picks by business: id, heading, intro, array(product slug => label).
$businesses = array(
	array('for-retail', 'Retail Storefronts', 'Turn the front window into your biggest ad and change it with every sale.', array(
		'adhesive-window-perf' => 'Full window graphic',
		'premium-window-cling' => 'Sale and season promos',
		'adhesive-clear-vinyl' => 'Logo and store hours',
	)),
	array('for-restaurants', 'Restaurants &amp; Caf&eacute;s', 'Show the menu and specials to people walking past, without blocking the view of the street.', array(
		'adhesive-window-perf' => 'Window menu and photos',
		'premium-window-cling' => 'Daily and seasonal specials',
		'floor-graphics' => 'Line and pickup markers',
	)),
	array('for-offices', 'Offices &amp; Clinics', 'Privacy for glass walls and exam rooms, with your brand where visitors see it first.', array(
		'frosted-vinyl-etched' => 'Frosted privacy glass',
		'adhesive-clear-vinyl' => 'Door and suite decals',
		'adhesive-wall-fabric' => 'Lobby wall mural',
	)),
	array('for-events', 'Events &amp; Trade Shows', 'Branding that goes up fast for a show or pop-up and comes down without damage.', array(
		'floor-graphics' => 'Booth floor graphic',
		'adhesive-vinyl-high-performance' => 'Removable wall graphics',
		'premium-window-cling' => 'Glass and cooler clings',
	)),
);

// One query for every product this page shows (instead of one lookup per slug).
wholesale_seo_prime_products(array_merge(
	array_column($films, 1),
	array_column($compare, 0),
	array_merge(...array_map(static function ($business) {
		return array_keys($business[3]);
	}, $businesses))
));

$perf_price = wholesale_seo_product_starting_text('adhesive-window-perf');
$decal_price = wholesale_seo_product_starting_text('adhesive-products');

$hero_photos = array(
	array('adhesive-window-perf', 'Printed window perf graphic on a storefront window'),
	array('frosted-vinyl-etched', 'Frosted window film with a printed logo on office glass'),
	array('adhesive-clear-vinyl', 'Clear vinyl window decal on a cafe window'),
	array('premium-window-cling', 'Printed window cling menu on a restaurant door'),
);

// The films are this page's item list in structured data.
$GLOBALS['wholesale_page_items'] = array_values(array_filter(array_map(static function ($film) {
	return wholesale_seo_product($film[1]);
}, $films)));
$GLOBALS['wholesale_page_image'] = ($hero = wholesale_seo_product('adhesive-window-perf')) ? get_the_post_thumbnail_url($hero, 'large') : '';

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'How much do custom window graphics cost?',
		'a' => 'Window graphics are priced per square foot, so the price depends on the film and the size' . ($perf_price ? ': window perf starts at ' . $perf_price : '') . ($decal_price ? ' and printed vinyl decals at ' . $decal_price : '') . '. Enter your width and height on any product and your price updates before you add it to the cart.',
	),
	array(
		'q' => 'Can I see out through window graphics?',
		'a' => 'With window perf, yes. It is 70% printed film and 30% open holes with a black backing, so people outside see your full color graphic and you can still see out from inside. Clear vinyl also lets you see through the glass around the printed design.',
	),
	array(
		'q' => 'Which window film is best for privacy?',
		'a' => 'Frosted window film. It gives glass the look of etched glass, lets diffuse light through and blocks a clear view, and it can carry your printed logo or a decorative band.',
	),
	array(
		'q' => 'Are window graphics removable?',
		'a' => 'Window clings peel off with no residue and can be reused, so they suit sales and seasonal promotions. Removable adhesive vinyl (AV 2) also comes off when you are done. Window perf, frosted film, clear vinyl and white vinyl (AV 1) use permanent adhesives for long term graphics.',
	),
	array(
		'q' => 'Do window graphics go on the inside or the outside of the glass?',
		'a' => 'Most films are applied to the outside of the window, with the printed side facing the street. Window clings hold on the back of the print, so apply them to the side of the glass people look from. Frosted, clear and perf films are waterproof and UV safe for outdoor use.',
	),
	array(
		'q' => 'What is the largest size you can print?',
		'a' => 'Window and decal films are printed up to 52 inches high and up to 159 feet long. Wall fabric and floor graphics go up to 52 inches high by 100 feet long. Larger windows and walls are covered in panels that butt together.',
	),
	array(
		'q' => 'Do you install window graphics?',
		'a' => 'No. We print and ship every order. Window graphics are made to be applied with a squeegee and a little patience, so most business owners apply them themselves, and larger jobs can be handed to a local sign installer.',
	),
	array(
		'q' => 'How fast will my window graphics ship?',
		'a' => 'Window graphics and vinyl are printed in Renton, WA. Orders placed with artwork before 4pm PST ship the next business day, and you choose standard, 3-day, 2-day or overnight shipping at checkout.',
	),
	array(
		'q' => 'Can you help with my artwork?',
		'a' => 'Yes. <a href="' . esc_url($contact_url) . '">Request a free quote</a> and upload your logo or a photo of the window, or call <a href="tel:+18664362101">866-436-2101</a>, and a specialist will help with sizing, film choice and artwork before you order.',
	),
));

get_header();

$page_hero_image = get_the_post_thumbnail_url(get_the_ID(), 'large');
$page_hero_image = $page_hero_image ? wholesale_webp_url(esc_url_raw($page_hero_image)) : '';
$default_page_hero_image = wholesale_default_hero_url();
$page_hero_background = wholesale_hero_background_style($page_hero_image, $default_page_hero_image);

// Hero proof point: the Google rating, linked to the reviews page.
$google_reviews = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;
$google_rating = $google_reviews && !empty($google_reviews['count']) ? $google_reviews : null;
$reviews_url = function_exists('wholesale_reviews_page_url') ? wholesale_reviews_page_url() : home_url('/reviews/');
?>

<main id="primary" class="sf-page bd-page wg-page">
	<section class="bd-hero" aria-labelledby="wg-title" style="<?php echo esc_attr($page_hero_background); ?>">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<div class="bd-hero-grid">
				<div class="bd-hero-copy">
					<p class="home-hero-eyebrow">UV printed &middot; Made in Renton, WA &middot; Ships to all 50 states</p>
					<?php if ($type) : ?>
						<h1 id="wg-title" class="bd-hero-title"><?php echo wp_kses_post($types[$type][0]); ?> <span>for Your Business</span></h1>
					<?php else : ?>
						<h1 id="wg-title" class="bd-hero-title">Custom Window Graphics <span>&amp; Printed Vinyl</span></h1>
					<?php endif; ?>
					<?php if ($google_rating) : ?>
						<p class="home-hero-proof">
							<a class="home-hero-rating" href="<?php echo esc_url($reviews_url); ?>"><?php echo wholesale_review_stars($google_rating['rating']); ?> <strong><?php echo esc_html(number_format((float) $google_rating['rating'], 1)); ?></strong> on Google (<?php echo esc_html(number_format_i18n((int) $google_rating['count'])); ?>)</a>
						</p>
					<?php endif; ?>
					<p class="home-hero-lead"><?php echo $type ? wp_kses_post($types[$type][1]) . ' ' : ''; ?>Window perf, frosted and clear films, window clings, decals, wall and floor graphics. Pick a film, enter your size and see your price before you check out.</p>
					<div class="home-hero-actions home-hero-actions--cl">
						<a class="home-hero-shop-button" href="<?php echo esc_url($type ? '#' . $type : '#films'); ?>"><span class="home-hero-button-text">See Films &amp; Prices<?php if ($decal_price) : ?><small>From <?php echo wp_kses_post($decal_price); ?></small><?php endif; ?></span> <span aria-hidden="true">&rarr;</span></a>
						<a class="home-hero-secondary-button" href="<?php echo esc_url($contact_url); ?>"><?php echo wholesale_home_icon('message'); ?> Get a Free Quote</a>
					</div>
					<p class="home-hero-help">
						<?php echo wholesale_home_icon('phone'); ?>
						Talk to a sign specialist: <a href="tel:+18664362101">866-436-2101</a>
						<span class="home-hero-help-hours">Mon&ndash;Fri, 8am&ndash;5pm PT</span>
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
				<span><strong>See Your Price Online</strong><small>Priced per square foot.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('clock'); ?></span>
				<span><strong>Ships Next Business Day</strong><small>Order with artwork by 4pm PST.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('badge'); ?></span>
				<span><strong>Sign Makers Since 2002</strong><small>Help from real specialists.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('pin'); ?></span>
				<span><strong>Made in Renton, WA</strong><small>Shipped to all 50 states.</small></span>
			</div>
		</div>
	</div>

	<section class="sf-section" id="films" aria-labelledby="wg-films-title">
		<div class="container">
			<p class="cl-kicker">Shop by film</p>
			<h2 id="wg-films-title" class="cl-section-title">Window Films, Decals, Wall &amp; Floor Graphics</h2>
			<p class="cl-section-lead">Every film is printed in full color to the exact size you order. Choose by what the graphic needs to do: be seen through, add privacy, come off later or stand up to foot traffic.</p>
			<div class="sf-type-grid">
				<?php foreach ($films as $film) : ?>
					<?php
					$url = $product_url($film[1]);
					if (!$url) {
						continue;
					}
					$price = wholesale_seo_product_starting_text($film[1]);
					$name = wp_strip_all_tags(html_entity_decode($film[2], ENT_QUOTES, 'UTF-8'));
					?>
					<article class="sf-type-card bd-card wg-card<?php echo $type === $film[0] ? ' is-highlighted' : ''; ?>" id="<?php echo esc_attr($film[0]); ?>">
						<a class="sf-type-image" href="<?php echo esc_url($url); ?>" tabindex="-1" aria-hidden="true">
							<?php echo $product_image($film[1], $name, '(max-width: 575px) 112px, (max-width: 991px) 50vw, 25vw'); ?>
						</a>
						<div class="sf-type-body">
							<span class="wg-card-tag"><?php echo wp_kses_post($film[4]); ?></span>
							<h3><a href="<?php echo esc_url($url); ?>"><?php echo wp_kses_post($film[2]); ?></a></h3>
							<p><?php echo wp_kses_post($film[3]); ?></p>
							<?php if ($price) : ?>
								<p class="sf-type-price"><small>Starting at</small> <?php echo wp_kses_post($price); ?></p>
							<?php endif; ?>
							<a class="wg-card-link" href="<?php echo esc_url($url); ?>">Size &amp; price it <span aria-hidden="true">&rarr;</span></a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="cl-compare" aria-labelledby="wg-compare-title">
		<div class="container">
			<p class="cl-kicker">Compare films</p>
			<h2 id="wg-compare-title" class="cl-section-title">Which Window Film Should You Choose?</h2>
			<p class="cl-section-lead">Start with two questions: do you need to see out, and will the graphic come down later?</p>
			<div class="cl-compare-table-wrap">
				<table class="cl-compare-table">
					<thead>
						<tr>
							<th scope="col">Film</th>
							<th scope="col">See out from inside</th>
							<th scope="col">Removable</th>
							<th scope="col">Best for</th>
							<th scope="col">Starting at</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($compare as $row) : ?>
							<?php $url = $product_url($row[0]); ?>
							<?php if ($url) : ?>
								<tr>
									<th scope="row"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($row[1]); ?></a></th>
									<td><?php echo esc_html($row[2]); ?></td>
									<td><?php echo esc_html($row[3]); ?></td>
									<td><?php echo esc_html($row[4]); ?></td>
									<td class="cl-compare-price"><?php echo wp_kses_post(wholesale_seo_product_starting_text($row[0])); ?></td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="wg-business-title">
		<div class="container">
			<p class="cl-kicker">Shop by business</p>
			<h2 id="wg-business-title" class="cl-section-title">Window Graphics for Every Kind of Business</h2>
			<p class="cl-section-lead">The combinations businesses order most, from a single door decal to a full storefront.</p>
			<div class="bd-occasion-grid">
				<?php foreach ($businesses as $business) : ?>
					<article class="bd-occasion" id="<?php echo esc_attr($business[0]); ?>">
						<h3><?php echo wp_kses_post($business[1]); ?></h3>
						<p><?php echo wp_kses_post($business[2]); ?></p>
						<ul>
							<?php foreach ($business[3] as $slug => $label) : ?>
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

	<section class="bd-cta" aria-labelledby="wg-cta-title">
		<div class="container bd-cta-inner">
			<div>
				<h2 id="wg-cta-title">Not sure which film or size?</h2>
				<p>Send a photo of your window or wall with rough measurements and your logo. A specialist will recommend the film, size the artwork and price the whole job.</p>
			</div>
			<div class="bd-cta-actions">
				<a class="home-hero-shop-button" href="<?php echo esc_url($contact_url); ?>">Request a Free Quote <span aria-hidden="true">&rarr;</span></a>
				<a class="home-hero-secondary-button" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> 866-436-2101</a>
			</div>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="wg-guide-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Buying guide</p>
			<h2 id="wg-guide-title" class="cl-section-title">How to Plan Window Graphics That Sell</h2>

			<h3>1. Decide what the window has to do</h3>
			<p>A storefront window works hardest when it does one job. If you want the whole window to advertise and still see out, use <a href="<?php echo esc_url($product_url('adhesive-window-perf', $category_url)); ?>">window perf</a>. If you need privacy, use <a href="<?php echo esc_url($product_url('frosted-vinyl-etched', $category_url)); ?>">frosted film</a>. If you only need your logo, hours and phone number on the door, <a href="<?php echo esc_url($product_url('adhesive-clear-vinyl', $category_url)); ?>">clear vinyl</a> keeps the rest of the glass open.</p>

			<h3>2. Measure the visible glass</h3>
			<p>Measure each pane inside the frame, width first, then height, and note any mullions, door handles or stickers you need to work around. Order to the visible glass size; panes wider than 52 inches in both directions are printed in panels that butt together.</p>

			<h3>3. Keep the message readable from the street</h3>
			<p>People read a window from the sidewalk or a passing car, so lead with one headline, your logo and a single next step such as a phone number or website. On window perf, avoid very small text: the perforation softens fine detail, and lettering reads best at a few inches tall or more.</p>

			<h3>4. Plan for change</h3>
			<p>If your promotions change every month, put the permanent branding on perf or clear vinyl and run the offers on <a href="<?php echo esc_url($product_url('premium-window-cling', $category_url)); ?>">window clings</a> that peel off clean and go back up next season. For temporary wall graphics, <a href="<?php echo esc_url($product_url('adhesive-vinyl-high-performance', $category_url)); ?>">removable vinyl</a> and <a href="<?php echo esc_url($product_url('adhesive-wall-fabric', $category_url)); ?>">wall fabric</a> come down without damage.</p>

			<h3>5. Applying your graphics</h3>
			<p>We print and ship; we don&rsquo;t install. Clean the glass, peel a few inches of backing, and work from the center out with a squeegee. Clings and air release vinyl make bubble free installs easy for one or two people, and large storefronts can be handed to a local sign installer.</p>
			<?php if ($locations_guide_url = wholesale_guide_url('locations', true)) : ?>
				<p>Some cities limit how much of a storefront window may carry advertising. We ship to every state, and our <a href="<?php echo esc_url($locations_guide_url); ?>">state and city sign permit guides</a> link to the official rules for your area.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="cl-steps" aria-labelledby="wg-steps-title">
		<div class="container">
			<p class="cl-kicker">Ordering process</p>
			<h2 id="wg-steps-title" class="cl-section-title">Order in Four Simple Steps</h2>
			<ol class="cl-steps-list">
				<li>
					<span class="cl-step-number" aria-hidden="true">1</span>
					<h3>Pick a film</h3>
					<p>Choose perf, frosted, clear, cling, decal, wall or floor.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">2</span>
					<h3>Size &amp; price it</h3>
					<p>Enter your width and height; your price updates instantly.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">3</span>
					<h3>Upload artwork</h3>
					<p>Send print-ready files, or <a href="<?php echo esc_url($contact_url); ?>">ask us for design help</a>.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">4</span>
					<h3>We print &amp; ship</h3>
					<p>Order by 4pm PST and it ships the next business day.</p>
				</li>
			</ol>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="wg-faq-title">
		<div class="container">
			<p class="cl-kicker">Before you order</p>
			<h2 id="wg-faq-title" class="cl-section-title">Window Graphics FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(true); ?>
			</div>
		</div>
	</section>

	<section class="cl-help cl-help--quote" id="quote" aria-labelledby="wg-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<p class="cl-kicker">Free quote &middot; No obligation</p>
				<h2 id="wg-help-title">Get a Free Window Graphics Quote</h2>
				<p>Tell us about the windows, walls or floors, the sizes and how many, and attach your logo or a photo of the space. A sign specialist will recommend the right film and price your order.</p>
				<ul class="cl-help-details">
					<li><?php echo wholesale_home_icon('clock'); ?> Mon&ndash;Fri, 8:00am&ndash;5:00pm PST</li>
					<li><?php echo wholesale_home_icon('pin'); ?> 707 S. Grady Way, Suite 600, Renton, WA 98057</li>
				</ul>
				<div class="clq-contact">
					<a class="cl-help-action cl-help-action--primary" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?><span><small>Call toll free</small>866-436-2101</span></a>
					<a class="cl-help-action" href="sms:+12066186543"><?php echo wholesale_home_icon('message'); ?><span><small>Text us</small>206-618-6543</span></a>
					<a class="cl-help-action" href="mailto:TR@StorefrontSignOnline.com"><?php echo wholesale_home_icon('mail'); ?><span><small>Email</small>TR@StorefrontSignOnline.com</span></a>
				</div>
			</div>
			<?php wholesale_quote_form_render($quote_form); ?>
		</div>
	</section>

	<nav class="cl-mobile-bar" aria-label="Quick actions">
		<button type="button" class="cl-mobile-bar-products" aria-controls="megaMenu"><?php echo wholesale_home_icon('grid'); ?> All Products</button>
		<a class="cl-mobile-bar-quote" href="#quote">Free Quote</a>
		<a class="cl-mobile-bar-shop" href="#films">Shop Films</a>
	</nav>
</main>

<?php
get_footer();
