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

/** Trade templates for the home page, the channel letters hub and About. */
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
		array('Halo Lit', wholesale_trade_type_url('hidden-back-halo-lit')),
		array('Trimless', wholesale_trade_type_url('inset-acrylic-face-lit-with-border-no-trimcap')),
		array('For Sign Companies', home_url('/for-sign-companies/')),
		array('Builder', home_url('/channel-letter-builder/')),
	);
	// The header button already says "Apply for Trade", so the row stays short enough for 992px.
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

/** The product menu: channel letters first, the rest behind one link. */
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
	return array(
		array(
			array('title' => 'Aluminum channel letters', 'links' => $aluminum),
			array('title' => 'Compare', 'links' => array(array('All 7 letter types', home_url('/channel-letters/')))),
		),
		array(
			array('title' => 'Stainless steel channel letters', 'links' => $stainless),
		),
		array(
			array('title' => 'Trade tools', 'links' => array(
				array('Channel letter builder', home_url('/channel-letter-builder/')),
				array('Outsource fabrication', home_url('/for-sign-companies/')),
				array('Install manual (PDF)', content_url('uploads/storefront-files/channel-letter-installation-manual.pdf')),
				array(wholesale_trade_can_order() ? 'My orders' : 'Apply for a trade account', wholesale_trade_can_order() ? home_url('/my-orders/') : wholesale_trade_apply_url()),
			)),
		) + (wholesale_trade_can_see_prices() ? array(1 => array('title' => 'More', 'links' => array(array('Other trade products', home_url('/shop/'))))) : array()),
	);
}
add_filter('wholesale_mega_columns', 'wholesale_trade_mega_columns');

add_action('wholesale_legacy_mega_menu', static function () {
	echo '<div class="container p-3"><div class="row">';
	foreach (wholesale_trade_mega_columns() as $column) {
		echo '<div class="col-md-4">';
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

/** Hero photo on the trade home page: preload it so it paints with the headline. */
add_action('wp_head', static function () {
	if (is_front_page()) {
		printf('<link rel="preload" as="image" href="%s" type="image/avif" fetchpriority="high">' . "\n", esc_url(get_template_directory_uri() . '/img/hero-1920.avif'));
	}
}, 2);

/** Trade FAQ shared by the home page and the hub. */
function wholesale_trade_faq()
{
	return array(
		array('q' => 'Who can open a trade account?', 'a' => 'Sign companies, sign installers, print shops, ad and marketing agencies, general contractors, architects and multi-location brands. We review every application by hand and may ask for a resale certificate or business license.'),
		array('q' => 'Do you install signs or sell to my clients?', 'a' => 'No. We never install, and this site is trade only. Every set ships wired, tested and UL labeled with a wiring diagram and a full-size mounting pattern, so your installer or a licensed electrician mounts it.'),
		array('q' => 'Where are the letters built?', 'a' => 'In our Washington and California facilities. We ship to all 50 states.'),
		array('q' => 'Are the letters UL listed?', 'a' => 'Yes. All 7 types are UL listed and carry a sign section label for outdoor installation, which most permit inspectors look for.'),
		array('q' => 'What warranty do the letters carry?', 'a' => 'A 5-year warranty covers all 7 channel letter types.'),
		array('q' => 'How do I see prices?', 'a' => 'Apply for a trade account. Once approved, you see trade pricing on every type, price full sign sets in the online builder and check out like any web order.'),
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
