<?php
/**
 * Trade site content: templates, trade pages, navigation, footer and assets.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Pages served from code (no editor content to keep in sync between local and
 * live). Each has its own template in template-parts/trade/.
 */
function wholesale_trade_virtual_pages()
{
	return array(
		'trade-account' => array(
			'title' => 'Trade Account for Sign Companies | Storefront Sign Wholesale',
			'description' => 'Apply for a trade account: sign companies, installers and resellers get wholesale channel letter pricing, the online builder and checkout once approved.',
			'crumb' => 'Trade Account',
			'modified' => '2026-11-02T00:00:00+00:00',
		),
		'for-sign-companies' => array(
			'title' => 'Outsource Channel Letter Fabrication | Storefront Sign Wholesale',
			'description' => 'Outsource channel letter fabrication to a trade-only shop that never installs or sells to your clients. UL listed, wired, tested and shipped with patterns.',
			'crumb' => 'For Sign Companies',
			'modified' => '2026-11-02T00:00:00+00:00',
			'template' => 'for-sign-companies.php',
		),
	);
}

add_action('init', static function () {
	add_rewrite_rule('^for-sign-companies/?$', 'index.php?wholesale_trade_page=for-sign-companies', 'top');
}, 20);

add_action('template_redirect', static function () {
	$slug = (string) get_query_var('wholesale_trade_page');
	$pages = wholesale_trade_virtual_pages();
	if (!$slug || 'apply' === $slug || empty($pages[$slug]['template'])) {
		return;
	}
	$GLOBALS['wholesale_trade_virtual_page'] = $slug;
	status_header(200);
	include get_template_directory() . '/template-parts/trade/' . $pages[$slug]['template'];
	exit;
}, 6);

/** Trade templates for the home page, the channel letters hub, About and Terms. */
add_filter('template_include', static function ($template) {
	$dir = get_template_directory() . '/template-parts/trade/';
	if (is_front_page()) {
		return $dir . 'home.php';
	}
	if (wholesale_trade_is_cl_hub()) {
		return $dir . 'channel-letters.php';
	}
	if (is_page('about')) {
		return $dir . 'about.php';
	}
	if (is_page('terms-conditions')) {
		return $dir . 'terms.php';
	}
	return $template;
}, 60);

/** URL of a channel letter type page (product), or the hub when it isn't published. */
function wholesale_trade_type_url($slug)
{
	$id = wholesale_trade_cl_product_id($slug);
	return $id ? get_permalink($id) : home_url('/channel-letters/');
}

function wholesale_trade_builder_url($slug = '')
{
	$id = $slug ? wholesale_trade_cl_product_id($slug) : 0;
	return $id ? add_query_arg('product_id', $id, home_url('/channel-letter-builder/')) : home_url('/channel-letter-builder/');
}

/** Photo for a type: the product's featured image, else the file named in the data. */
function wholesale_trade_type_image($slug, $size = 'large', $attrs = array())
{
	$types = wholesale_trade_cl_types();
	$type = isset($types[$slug]) ? $types[$slug] : array();
	$alt = $type ? 'Type ' . $type['type'] . ' ' . $type['name'] . ' channel letters' : '';
	$id = wholesale_trade_cl_product_id($slug);
	$thumb = $id ? (int) get_post_thumbnail_id($id) : 0;
	$attrs = $attrs + array('alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async');
	if ($thumb) {
		return wp_get_attachment_image($thumb, $size, false, $attrs);
	}
	if (!empty($type['image'])) {
		$url = content_url('uploads/storefront-files/' . $type['image']);
		return '<img src="' . esc_url($url) . '" alt="' . esc_attr($attrs['alt']) . '" loading="' . esc_attr($attrs['loading']) . '" decoding="async">';
	}
	return '';
}

/** Navigation shown in the header row and the phone menu. */
function wholesale_trade_nav_items()
{
	$items = array(
		array('Channel Letters', home_url('/channel-letters/')),
	);
	// Every other non-empty product category. The 7 letter types live in "All Products".
	$categories = array(
		'Banners'       => 'banners',
		'Flags'         => 'advertising-flags',
		'Banner Stands' => 'banner-stands',
		'Trade Show'    => 'trade-show-products',
		'SEG'           => 'seg-products',
		'Tents'         => 'custom-event-tents',
		'Table Throws'  => 'table-throws',
		'A-Frames'      => 'a-frame-and-sign-holders',
		'Signicade'     => 'signicade-a-frames',
		'Real Estate'   => 'real-estate-products',
		'Rigid Signs'   => 'rigid-signs-and-magnets',
		'Adhesive'      => 'adhesive-products',
		'Wall Art'      => 'wall-art',
		'Reflective'    => 'reflective-products',
		'Dry Erase'     => 'dry-erase-products',
		'Hardware'      => 'hardware-only',
	);
	foreach ($categories as $label => $slug) {
		$items[] = array($label, wholesale_category_url($slug));
	}
	$items = array_merge($items, array(
		array('For Sign Companies', home_url('/for-sign-companies/')),
		array('Builder', home_url('/channel-letter-builder/')),
	));
	// The header row scrolls sideways (with an edge fade) when these do not fit.
	return $items;
}

add_filter('pre_wp_nav_menu', static function ($output, $args) {
	if (empty($args->theme_location) || !in_array($args->theme_location, array('header-bottom-menu', 'header-menu'), true)) {
		return $output;
	}
	$brand = wholesale_trade_brand();
	$links = 'header-menu' === $args->theme_location
		? array(array($brand['phone'], $brand['phone_href']), array($brand['email'], 'mailto:' . $brand['email']))
		: wholesale_trade_nav_items();

	$current = isset($_SERVER['REQUEST_URI']) ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
	$items = '';
	foreach ($links as $link) {
		$path = (string) wp_parse_url($link[1], PHP_URL_PATH);
		$is_current = $path && untrailingslashit($path) === untrailingslashit($current);
		$items .= '<li class="menu-item' . ($is_current ? ' current-menu-item' : '') . '"><a href="' . esc_url($link[1]) . '"' . ($is_current ? ' aria-current="page"' : '') . '>' . esc_html($link[0]) . '</a></li>';
	}
	$wrap = isset($args->items_wrap) ? $args->items_wrap : '<ul id="%1$s" class="%2$s">%3$s</ul>';
	$nav = sprintf($wrap, esc_attr('menu-' . $args->theme_location), esc_attr(isset($args->menu_class) ? $args->menu_class : 'menu'), $items);
	if (!empty($args->container)) {
		$tag = in_array($args->container, array('div', 'nav'), true) ? $args->container : 'div';
		$attrs = !empty($args->container_class) ? ' class="' . esc_attr($args->container_class) . '"' : '';
		$attrs .= !empty($args->container_aria_label) ? ' aria-label="' . esc_attr($args->container_aria_label) . '"' : '';
		$nav = '<' . $tag . $attrs . '>' . $nav . '</' . $tag . '>';
	}
	if (!empty($args->echo)) {
		echo $nav; // Escaped above.
		return '';
	}
	return $nav;
}, 10, 2);

/**
 * The "All Products" menu, four columns: channel letters, displays,
 * events + large format print, then trade tools and company links.
 */
function wholesale_trade_mega_columns()
{
	$types = wholesale_trade_cl_types();
	$aluminum = $stainless = array();
	foreach ($types as $slug => $type) {
		$link = array('Type ' . $type['type'] . ' · ' . $type['name'], wholesale_trade_type_url($slug));
		if ('aluminum' === $type['family']) {
			$aluminum[] = $link;
		} else {
			$stainless[] = $link;
		}
	}
	$aluminum[] = array('Compare all 7 letter types →', home_url('/channel-letters/'));

	$cat = static function ($label, $slug) {
		return array($label, wholesale_category_url($slug));
	};

	$can_order = wholesale_trade_can_order();
	$tools = array(
		array('Channel letter builder', home_url('/channel-letter-builder/')),
		array('Outsource fabrication', home_url('/for-sign-companies/')),
		array('Install manual (PDF)', content_url('uploads/storefront-files/channel-letter-installation-manual.pdf')),
	);
	if ($can_order) {
		$tools[] = array('My orders', home_url('/my-orders/'));
	} else {
		$tools[] = array('Apply for a trade account', wholesale_trade_apply_url());
		$tools[] = array('Trade sign in', home_url('/login/'));
	}
	if (wholesale_trade_can_see_prices()) {
		$tools[] = array('Full trade catalog', home_url('/shop/'));
	}

	// Link the reviews page only once it has an approved review to show.
	$company = array(array('About us', home_url('/about/')));
	$review_counts = wp_count_posts('review_submission');
	if (!empty($review_counts->publish)) {
		$company[] = array('Customer reviews', function_exists('wholesale_reviews_page_url') ? wholesale_reviews_page_url() : home_url('/reviews/'));
	}
	$company[] = array('Contact', home_url('/contact/'));

	return array(
		array(
			array('title' => 'Aluminum channel letters', 'links' => $aluminum),
			array('title' => 'Stainless steel channel letters', 'links' => $stainless),
		),
		array(
			array('title' => 'Banners & flags', 'links' => array(
				$cat('Vinyl & fabric banners', 'banners'),
				$cat('Advertising flags', 'advertising-flags'),
				$cat('Banner stands', 'banner-stands'),
				$cat('Banner stand hardware', 'banner-stand-hardware'),
			)),
			array('title' => 'Outdoor displays', 'links' => array(
				$cat('A-frames & sign holders', 'a-frame-and-sign-holders'),
				$cat('Signicade A-frames', 'signicade-a-frames'),
				$cat('Real estate signs', 'real-estate-products'),
				$cat('Hardware only', 'hardware-only'),
				$cat('All indoor / outdoor displays →', 'indoor-outdoor-displays'),
			)),
		),
		array(
			array('title' => 'Trade show & events', 'links' => array(
				$cat('Trade show displays', 'trade-show-products'),
				$cat('SEG fabric frames', 'seg-products'),
				$cat('Event tents', 'custom-event-tents'),
				$cat('Table throws', 'table-throws'),
			)),
			array('title' => 'Large format print', 'links' => array(
				$cat('Adhesive vinyl & decals', 'adhesive-products'),
				$cat('Rigid signs & magnets', 'rigid-signs-and-magnets'),
				$cat('Wall art', 'wall-art'),
				$cat('Reflective', 'reflective-products'),
				$cat('Dry erase', 'dry-erase-products'),
				$cat('All large format →', 'large-format'),
			)),
		),
		array(
			array('title' => 'Trade tools', 'links' => $tools),
			array('title' => 'Company', 'links' => $company),
		),
	);
}
add_filter('wholesale_mega_columns', 'wholesale_trade_mega_columns');

add_action('wholesale_legacy_mega_menu', static function () {
	echo '<div class="container p-3"><div class="row">';
	foreach (wholesale_trade_mega_columns() as $column) {
		echo '<div class="col-md-3">';
		foreach ($column as $group) {
			echo '<div class="mm-col-container mt-3"><h4 class="mm-heading">' . esc_html($group['title']) . '</h4>';
			foreach ($group['links'] as $link) {
				echo '<a href="' . esc_url($link[1]) . '" class="mm-link"><span>' . esc_html($link[0]) . '</span><i class="fa-solid fa-chevron-right"></i></a>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
	echo '<div class="mega-menu-footer"><a class="mega-menu-builder-cta" href="' . esc_url(home_url('/channel-letter-builder/')) . '"><span>Open the builder</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>';
	echo '</div></div>';
});

add_filter('wholesale_search_placeholder', static function () {
	return 'Search channel letter types, specs…';
});

add_filter('wholesale_popular_searches', static function () {
	return array('Halo lit', 'Front lit', 'Trimless', 'Stainless steel', 'Raceway');
});

/** Header button: apply until approved, then the builder. */
add_filter('wholesale_header_cta', static function ($cta) {
	return wholesale_trade_can_order()
		? array(home_url('/channel-letter-builder/'), 'Start Building')
		: array(wholesale_trade_apply_url(), 'Apply for Trade');
});
add_filter('wholesale_header_cta_mobile_label', static function () {
	return wholesale_trade_can_order() ? 'Open the channel letter builder' : 'Apply for a trade account';
});

add_action('wholesale_footer', static function () {
	get_template_part('template-parts/trade/footer');
});

/** Trade styles on every front-end page (one small file). */
add_action('wp_enqueue_scripts', static function () {
	$file = '/css/trade.css';
	wp_enqueue_style('wholesale-trade', get_template_directory_uri() . $file, array('custom-style'), (string) @filemtime(get_template_directory() . $file));

	if (is_front_page()) {
		$dir = get_template_directory();
		$uri = get_template_directory_uri();
		// Home page layout and the lit preview (template-parts/trade/home.php).
		wp_enqueue_style('wholesale-trade-home', $uri . '/css/trade-home.css', array('wholesale-trade'), (string) @filemtime($dir . '/css/trade-home.css'));
		wp_enqueue_script('wholesale-trade-home', $uri . '/js/trade-home.js', array(), (string) @filemtime($dir . '/js/trade-home.js'), array('in_footer' => true, 'strategy' => 'defer'));
		// The retail home page styles (catalog, quote band, SEO blocks) style
		// nothing on the trade home page; skipping them saves ~29 KB of render-blocking CSS.
		foreach (array('wholesale-home-catalog', 'wholesale-banners-displays', 'wholesale-seo-pages', 'cl-quote') as $handle) {
			wp_dequeue_style($handle);
		}
	}
}, 120);

/**
 * Hero photo on the trade home page: the front page's featured image (WebP copy
 * when one exists), or the built-in hero photo when the page has none.
 *
 * @return array{src:string,srcset:string,width:int,height:int,avif:string}
 */
function wholesale_trade_hero_image()
{
	static $hero = null;
	if (null !== $hero) {
		return $hero;
	}

	$thumb_id = (int) get_post_thumbnail_id((int) get_option('page_on_front'));
	$full_url = $thumb_id ? wp_get_attachment_url($thumb_id) : '';
	$meta = $thumb_id ? wp_get_attachment_metadata($thumb_id) : array();
	if ($full_url && !empty($meta['width'])) {
		// One full-size file: the hero is cropped to cover a tall box on phones, so
		// smaller widths would look soft. The WebP copy keeps it light.
		return $hero = array(
			'src'    => wholesale_webp_url($full_url),
			'srcset' => '',
			'width'  => (int) $meta['width'],
			'height' => (int) $meta['height'],
			// Full-size AVIF copy when one is already on disk; '' falls back to the WebP.
			'avif'   => function_exists('wholesale_hero_avif_url') ? wholesale_hero_avif_url($full_url) : '',
		);
	}

	return $hero = array(
		'src'    => get_template_directory_uri() . '/img/hero-1920.webp',
		'srcset' => '',
		'width'  => 1920,
		'height' => 640,
		'avif'   => get_template_directory_uri() . '/img/hero-1920.avif',
	);
}

/** Preload the hero photo so it paints with the headline. */
add_action('wp_head', static function () {
	if (!is_front_page()) {
		return;
	}
	$hero = wholesale_trade_hero_image();
	if ($hero['avif']) {
		printf('<link rel="preload" as="image" href="%s" type="image/avif" fetchpriority="high">' . "\n", esc_url($hero['avif']));
	} elseif ($hero['srcset']) {
		printf('<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="100vw" fetchpriority="high">' . "\n", esc_url($hero['src']), esc_attr($hero['srcset']));
	} else {
		printf('<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url($hero['src']));
	}
}, 2);

/** Trade FAQ shared by the home page and the hub. */
function wholesale_trade_faq()
{
	return array(
		array('q' => 'Who can open a trade account?', 'a' => 'Sign companies, sign installers, print shops, ad and marketing agencies, general contractors, architects and multi-location brands. ' . (wholesale_trade_auto_approve() ? 'Your account opens as soon as you apply. We look over every new account and may ask for a resale certificate or business license.' : 'We review every application by hand and may ask for a resale certificate or business license.')),
		array('q' => 'Do you install signs or sell to my clients?', 'a' => 'No. We never install, and this site is trade only. Every set ships wired, tested and UL labeled with a wiring diagram and a full-size mounting pattern, so your installer or a licensed electrician mounts it.'),
		array('q' => 'Where are the letters built?', 'a' => 'In our Washington and California facilities. We ship to all 50 states.'),
		array('q' => 'Are the letters UL listed?', 'a' => 'Yes. All 7 types are UL listed and carry a sign section label for outdoor installation, which most permit inspectors look for.'),
		array('q' => 'What warranty do the letters carry?', 'a' => 'A 5-year warranty covers all 7 channel letter types.'),
		array('q' => 'How do I see prices?', 'a' => 'Apply for a trade account. ' . (wholesale_trade_auto_approve() ? 'As soon as you send the application' : 'Once approved') . ', you see trade pricing on every type, price full sign sets in the online builder and check out like any web order.'),
		array('q' => 'What artwork do you need?', 'a' => 'A vector file (AI, EPS or PDF) at final size with text outlined. For logos and complex marks, send the file with your request and our team checks stroke widths before production.'),
	);
}

/** Cards for the channel letter types (all 7 by default). */
function wholesale_trade_render_type_cards($slugs = array(), $heading_level = 3)
{
	$types = wholesale_trade_cl_types();
	$slugs = $slugs ? $slugs : array_keys($types);
	$h = 'h' . max(2, min(4, (int) $heading_level));
	echo '<ul class="tw-types">';
	foreach ($slugs as $slug) {
		if (empty($types[$slug])) {
			continue;
		}
		$type = $types[$slug];
		$url = wholesale_trade_type_url($slug);
		?>
		<li class="tw-type tw-type--<?php echo esc_attr($type['family']); ?>">
			<a class="tw-type__media" href="<?php echo esc_url($url); ?>" tabindex="-1" aria-hidden="true"><?php echo wholesale_trade_type_image($slug, 'medium_large'); // Escaped in core. ?></a>
			<div class="tw-type__body">
				<p class="tw-type__kicker">Type <?php echo (int) $type['type']; ?> · <?php echo 'aluminum' === $type['family'] ? 'Aluminum' : 'Stainless steel'; ?></p>
				<<?php echo $h; ?> class="tw-type__name"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($type['name']); ?> channel letters</a></<?php echo $h; ?>>
				<p class="tw-type__short"><?php echo esc_html($type['short']); ?></p>
				<p class="tw-type__best"><strong>Best for:</strong> <?php echo esc_html($type['best_for'][0]); ?></p>
				<a class="tw-type__more" href="<?php echo esc_url($url); ?>">Specs &amp; trade pricing <span aria-hidden="true">→</span></a>
			</div>
		</li>
		<?php
	}
	echo '</ul>';
}

/** A visible FAQ list; also registered for FAQPage markup. */
function wholesale_trade_render_faq($items, $title = 'Trade FAQ')
{
	wholesale_trade_register_faq($items);
	echo '<section class="tw-section tw-faq" aria-labelledby="tw-faq-title"><div class="tw-wrap tw-wrap--narrow">';
	echo '<h2 id="tw-faq-title" class="tw-h2">' . esc_html($title) . '</h2>';
	foreach ($items as $i => $item) {
		echo '<details class="tw-faq__item"' . (0 === $i ? ' open' : '') . '><summary>' . esc_html($item['q']) . '</summary><p>' . esc_html($item['a']) . '</p></details>';
	}
	echo '</div></section>';
}

/** Closing call to action, changes with the visitor's account state. */
function wholesale_trade_render_cta_band($title = 'Ready to send your next channel letter job?')
{
	$brand = wholesale_trade_brand();
	$approved = wholesale_trade_can_order();
	?>
	<section class="tw-cta" aria-label="Get started">
		<div class="tw-wrap tw-cta__in">
			<div>
				<h2 class="tw-cta__title"><?php echo esc_html($title); ?></h2>
				<p class="tw-cta__text"><?php echo $approved ? 'Price a full set in the builder, or call trade sales with your shop drawings.' : 'The application takes about three minutes. Approved accounts see trade pricing on all 7 types and order online.'; ?></p>
			</div>
			<div class="tw-cta__actions">
				<?php if ($approved) : ?>
					<a class="tw-btn tw-btn--glow" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Open the builder</a>
				<?php else : ?>
					<a class="tw-btn tw-btn--glow" href="<?php echo esc_url(wholesale_trade_apply_url()); ?>">Apply for a trade account</a>
				<?php endif; ?>
				<a class="tw-btn tw-btn--ghost-light" href="<?php echo esc_url($brand['phone_href']); ?>">Call <?php echo esc_html($brand['phone']); ?></a>
			</div>
		</div>
	</section>
	<?php
}

/** Proof points every page can repeat (owner-confirmed facts only). */
function wholesale_trade_proof_points()
{
	return array(
		array('Since 2002', 'Building lit letters for more than two decades'),
		array('UL listed', 'Sign section label on every outdoor set'),
		array('WA & CA', 'Built in Washington and California'),
		array('50 states', 'Crated and shipped nationwide'),
		array('5-year warranty', 'On all 7 channel letter types'),
	);
}

function wholesale_trade_render_proof($class = '')
{
	echo '<ul class="tw-proof ' . esc_attr($class) . '">';
	foreach (wholesale_trade_proof_points() as $point) {
		echo '<li><strong>' . esc_html($point[0]) . '</strong><span>' . esc_html($point[1]) . '</span></li>';
	}
	echo '</ul>';
}

/**
 * The shared contact page is written for retail shoppers. In trade mode its
 * headings and helper text are reworded for sign companies; the form and its
 * handler are unchanged.
 */
add_action('template_redirect', static function () {
	if (!is_page('contact')) {
		return;
	}
	ob_start(static function ($html) {
		$brand = wholesale_trade_brand();
		return strtr($html, array(
			'Let’s bring your storefront to life' => 'Trade sales',
			'Request a Custom Channel Letter Sign Quote' => 'Contact Trade Sales',
			'Tell us about your storefront, and our sign specialists will help you plan illuminated channel letters that fit your brand, building, and budget.' => 'Send your shop drawing, artwork or job details and our trade team will help you spec and price the channel letter set for your client.',
			'Share a few details and we’ll follow up with the best next step for your storefront.' => 'Share the job details and we will follow up with the next step.',
			'Tell us about your storefront' => 'Tell us about the job',
			'Storefront photos, your logo, or a sketch.' => 'Shop drawings, vector art or site photos.',
			'Get practical guidance on materials, illumination, installation planning, and the right channel letter style for your storefront.' => 'Get help choosing letter types, lighting, mounting and colors for your client’s job.',
			'Storefront Sign Online LLC, also doing business as Lit Sign Manufacturing.' => esc_html($brand['name'] . ', the trade division of ' . $brand['legal_name'] . '.'),
			'Every order ships to you; we don’t offer walk-ins or pickup.' => 'Every order ships; no walk-ins or pickup. We never install.',
			'Planning a storefront sign?' => 'Quoting a channel letter job?',
			'Storefront photos, approximate dimensions, preferred lighting style, installation location, and your target timeline are all helpful.' => 'Your shop drawing or vector art, letter height, colors, lighting style, mounting method and the delivery state.',
			'based on your storefront and brand.' => 'for your client’s site and brand.',
		));
	});
}, 20);

/**
 * Print and display catalog on the home page, under the channel letter types.
 * Same four groups as the "All Products" menu. Featured tiles name the product
 * whose photo reads best at a large size; directory cards use a picked photo
 * or, failing that, the first published product photo in the category.
 *
 * Counts and photo IDs are cached for 12 hours and cleared when a product or a
 * product category is saved. URLs are built on each request.
 *
 * @return array{featured:array,groups:array,total:int}
 */
function wholesale_trade_home_catalog()
{
	$featured = array(
		'banners'             => array('blurb' => 'Vinyl, mesh, fabric and backlit banners printed full color for storefronts, events and job sites.', 'photo' => 680, 'tint' => '#eef2fb'),
		'custom-event-tents'  => array('blurb' => 'Full color tents, walls and tent flags for markets and outdoor events.', 'photo' => 1115, 'tint' => '#ecf6ee'),
		'advertising-flags'   => array('blurb' => 'Feather, teardrop, rectangle and pole flags that catch the street.', 'photo' => 821, 'tint' => '#fff5e1'),
		'signicade-a-frames'  => array('blurb' => 'Sidewalk A-frames with printed inserts for menus and specials.', 'photo' => 1011, 'tint' => '#fbeff0'),
		'trade-show-products' => array('blurb' => 'Tension fabric and pop up displays that set up fast on the floor.', 'photo' => 1092, 'tint' => '#e9f4fa'),
	);

	$groups = array(
		'banners-flags' => array(
			'title' => 'Banners & flags',
			'icon'  => 'flag',
			'all'   => '',
			'categories' => array(
				'banners'               => array('Vinyl & fabric banners', 680),
				'advertising-flags'     => array('Advertising flags', 0),
				'banner-stands'         => array('Banner stands', 685),
				'banner-stand-hardware' => array('Banner stand hardware', 0),
			),
		),
		'outdoor-displays' => array(
			'title' => 'Outdoor displays',
			'icon'  => 'easel',
			'all'   => 'indoor-outdoor-displays',
			'categories' => array(
				'a-frame-and-sign-holders' => array('A-frames & sign holders', 0),
				'signicade-a-frames'       => array('Signicade A-frames', 1011),
				'real-estate-products'     => array('Real estate signs', 0),
				'hardware-only'            => array('Hardware only', 0),
			),
		),
		'trade-show-events' => array(
			'title' => 'Trade show & events',
			'icon'  => 'tent',
			'all'   => '',
			'categories' => array(
				'trade-show-products' => array('Trade show displays', 1092),
				'seg-products'        => array('SEG fabric frames', 0),
				'custom-event-tents'  => array('Event tents', 1115),
				'table-throws'        => array('Table throws', 0),
			),
		),
		'large-format' => array(
			'title' => 'Large format print',
			'icon'  => 'print',
			'all'   => 'large-format',
			'categories' => array(
				'adhesive-products'       => array('Adhesive vinyl & decals', 0),
				'rigid-signs-and-magnets' => array('Rigid signs & magnets', 920),
				'wall-art'                => array('Wall art', 0),
				'reflective-products'     => array('Reflective', 0),
				'dry-erase-products'      => array('Dry erase', 0),
			),
		),
	);

	$data = get_transient('wholesale_trade_home_catalog');
	if (!is_array($data) || empty($data['v']) || 1 !== $data['v']) {
		$data = array('v' => 1, 'terms' => array(), 'groups' => array(), 'featured' => array());
		$photo = static function ($product_id) {
			$product_id = (int) $product_id;
			return $product_id && 'publish' === get_post_status($product_id) ? (int) get_post_thumbnail_id($product_id) : 0;
		};
		foreach ($groups as $key => $group) {
			$slugs = array();
			foreach ($group['categories'] as $slug => $category) {
				$term = get_term_by('slug', $slug, 'product_category');
				if (!$term || !$term->count) {
					continue;
				}
				$slugs[] = $slug;
				$image = $photo($category[1]);
				if (!$image) {
					$first = get_posts(array(
						'post_type'      => 'product',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
						'meta_key'       => '_thumbnail_id',
						'no_found_rows'  => true,
						'tax_query'      => array(array('taxonomy' => 'product_category', 'field' => 'slug', 'terms' => $slug, 'include_children' => false)),
					));
					$image = $first ? (int) get_post_thumbnail_id($first[0]) : 0;
				}
				$data['terms'][$slug] = array('count' => (int) $term->count, 'image' => $image);
			}
			// Unique products across the group: a stand can sit in two of its categories.
			$data['groups'][$key] = $slugs ? count(get_posts(array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array(array('taxonomy' => 'product_category', 'field' => 'slug', 'terms' => $slugs, 'include_children' => false)),
			))) : 0;
		}
		foreach ($featured as $slug => $item) {
			$data['featured'][$slug] = $photo($item['photo']);
		}
		set_transient('wholesale_trade_home_catalog', $data, 12 * HOUR_IN_SECONDS);
	}

	$out = array('featured' => array(), 'groups' => array(), 'total' => 0);
	foreach ($groups as $key => $group) {
		$items = array();
		foreach ($group['categories'] as $slug => $category) {
			if (empty($data['terms'][$slug])) {
				continue;
			}
			$items[$slug] = array(
				'name'  => $category[0],
				'url'   => wholesale_category_url($slug),
				'count' => $data['terms'][$slug]['count'],
				'image' => $data['terms'][$slug]['image'],
			);
		}
		if (!$items) {
			continue;
		}
		$out['total'] += count($items);
		$out['groups'][$key] = array(
			'title' => $group['title'],
			'icon'  => $group['icon'],
			'count' => isset($data['groups'][$key]) ? (int) $data['groups'][$key] : 0,
			'items' => $items,
			'all'   => $group['all'] ? wholesale_category_url($group['all']) : '',
		);
		foreach ($featured as $slug => $item) {
			if (isset($items[$slug])) {
				$out['featured'][$slug] = array_merge($items[$slug], array(
					'blurb' => $item['blurb'],
					'tint'  => $item['tint'],
					'image' => !empty($data['featured'][$slug]) ? $data['featured'][$slug] : $items[$slug]['image'],
				));
			}
		}
	}
	// Keep the featured order as listed above (the first tile is the large one).
	$out['featured'] = array_merge(array_intersect_key($featured, $out['featured']), $out['featured']);
	return $out;
}

/** A saved product or category can change the home catalog counts and photos. */
foreach (array('save_post_product', 'deleted_post', 'created_product_category', 'edited_product_category', 'delete_product_category') as $wholesale_trade_hook) {
	add_action($wholesale_trade_hook, static function () {
		delete_transient('wholesale_trade_home_catalog');
	});
}
