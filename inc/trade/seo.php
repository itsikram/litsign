<?php
/**
 * Trade site SEO: its own titles, descriptions, schema, robots and sitemap.
 *
 * Deliberately different from the retail site (wholesale-plan/keyword-map.md):
 * - targets trade intent (wholesale, manufacturer, fabrication, for sign
 *   companies), never retail terms (custom/near me/cost/{place}, storefront
 *   signs, print products);
 * - title formula "{Primary keyword} | Storefront Sign Wholesale";
 * - public Product details, but no Offer/price markup (prices are login-only), no Merchant feed,
 *   no LocalBusiness, no location pages;
 * - every public page is indexed and in the sitemap: channel letter and trade
 *   pages, the rest of the catalog (price-free product and category pages) and
 *   the public content pages (owner, 2026-11-20). Private, order, redirected
 *   and retired pages stay noindex and out of the sitemap.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Retail head output and sitemaps off; the trade versions below replace them. */
add_action('after_setup_theme', static function () {
	remove_action('wp_head', 'wholesale_seo_head', 1);
	remove_action('wp_head', 'wholesale_organization_schema', 2);
	remove_action('wp_head', 'wholesale_preload_front_page_hero', 1);
	remove_filter('wp_robots', 'wholesale_seo_robots');
	remove_filter('wp_robots', 'wholesale_guide_robots', 20);
	remove_filter('robots_txt', 'wholesale_robots_txt', 10);
	remove_action('template_redirect', 'wholesale_render_sitemap', 0);
	remove_action('template_redirect', 'wholesale_render_product_sitemap', 0);
	remove_action('template_redirect', 'wholesale_render_category_sitemap', 0);
	remove_action('wp_head', 'rel_canonical');
	// /channel-letters/ is the trade hub (template-parts/trade/channel-letters.php). The retail
	// 301 to /custom-channel-letters/ (a noindexed retail page) ran first and hid it.
	remove_action('template_redirect', 'wholesale_seo_redirect_channel_letters', 1);
}, 99);

/** The 7 channel letter types, keyed by product slug (data/trade/channel-letters.php). */
function wholesale_trade_cl_types()
{
	static $types = null;
	if (null === $types) {
		$types = include get_template_directory() . '/data/trade/channel-letters.php';
		$types = is_array($types) ? $types : array();
	}
	return $types;
}

/** Published product ID for a channel letter type slug. */
function wholesale_trade_cl_product_id($slug)
{
	static $ids = array();
	if (!isset($ids[$slug])) {
		$post = get_page_by_path($slug, OBJECT, 'product');
		$ids[$slug] = ($post && 'publish' === $post->post_status) ? (int) $post->ID : 0;
	}
	return $ids[$slug];
}

/** The type entry for the current product page, or an empty array. */
function wholesale_trade_current_cl_type()
{
	if (!is_singular('product')) {
		return array();
	}
	$types = wholesale_trade_cl_types();
	$slug = get_post_field('post_name', get_queried_object_id());
	return isset($types[$slug]) ? $types[$slug] + array('slug' => $slug) : array();
}

function wholesale_trade_is_cl_hub()
{
	return 'channel-letters' === sanitize_title((string) get_query_var('category_slug'));
}

/** Trade pages served from code (inc/trade/content.php). */
function wholesale_trade_virtual_page()
{
	return isset($GLOBALS['wholesale_trade_virtual_page']) ? (string) $GLOBALS['wholesale_trade_virtual_page'] : '';
}

/** A product or category name without the retail brand ("Clear Acrylic Prints | Storefront Sign Online"). */
function wholesale_trade_clean_name($name)
{
	$name = html_entity_decode(wp_strip_all_tags((string) $name), ENT_QUOTES, 'UTF-8');
	$name = (string) preg_replace('/\s*[|:\x{2013}\x{2014}-]?\s*Storefront\s+Signs?\s+Online\b/iu', '', $name);
	return trim((string) preg_replace('/\s+/u', ' ', $name), " \t,|:-");
}

/**
 * Title for a catalog product or category that fits the ~60 characters Google
 * shows, leading with the trade search term: "Wholesale {name} | brand", then
 * "Wholesale {name} for Sign Shops", then "Wholesale {name}".
 */
function wholesale_trade_catalog_title($name)
{
	$name = wholesale_trade_clean_name($name);
	$core = preg_match('/\bwholesale\b/i', $name) ? $name : 'Wholesale ' . $name;
	foreach (array($core . ' | Storefront Sign Wholesale', $core . ' for Sign Shops') as $title) {
		if (mb_strlen($title) <= 60) {
			return $title;
		}
	}
	return $core;
}

/** A meta description cut at a word to the ~160 characters Google shows. */
function wholesale_trade_fit_description($text, $max = 160)
{
	$text = trim((string) preg_replace('/\s+/u', ' ', (string) $text));
	if (mb_strlen($text) <= $max) {
		return $text;
	}
	$cut = mb_substr($text, 0, $max - 1);
	$space = mb_strrpos($cut, ' ');
	if (false !== $space && $space > 80) {
		$cut = mb_substr($cut, 0, $space);
	}
	return rtrim($cut, " ,;:-\x{2013}\x{2014}.…") . '…';
}

/**
 * The page a copy points search engines at: "reviews-2" with the same title as
 * a published "reviews" page. 0 when the page is not a copy.
 */
function wholesale_trade_page_duplicate_of($page_id)
{
	$slug = (string) get_post_field('post_name', $page_id);
	if (!preg_match('/^(.+)-\d+$/', $slug, $match)) {
		return 0;
	}
	$original = get_page_by_path($match[1]);
	if (!$original || 'publish' !== $original->post_status || (int) $original->ID === (int) $page_id) {
		return 0;
	}
	$name = static function ($id) {
		return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8')));
	};
	return $name($original->ID) === $name($page_id) ? (int) $original->ID : 0;
}

/** Plain text for a meta description: no tags, shortcodes or price amounts, one line. */
function wholesale_trade_meta_text($text, $words)
{
	// List items, paragraphs and line breaks become sentences, not one run-on line.
	$text = (string) preg_replace('#</(?:li|p|h[1-6]|div|td)>|<br\s*/?>|\r?\n#i', '. ', strip_shortcodes((string) $text));
	$text = wp_strip_all_tags($text);
	$text = (string) preg_replace('/([.!?;:,])(\s*\.)+/u', '$1', (string) preg_replace('/^[\s.]+|(\s*\.){2,}/u', '.', $text));
	$text = ltrim($text, ' .');
	if (function_exists('wholesale_trade_strip_price_text')) {
		$text = wholesale_trade_strip_price_text($text);
	}
	$text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8')));
	return '' === $text ? '' : wp_trim_words($text, $words, '…');
}

/** Description for a catalog product page: its name, then its own short copy. */
function wholesale_trade_product_description($product_id)
{
	$text = '';
	foreach (array((string) get_post_field('post_excerpt', $product_id), (string) get_post_meta($product_id, '_product_short_desc', true), (string) get_post_field('post_content', $product_id)) as $source) {
		$text = wholesale_trade_meta_text($source, 16);
		if ('' !== $text) {
			break;
		}
	}
	$lead = 'Wholesale ' . wholesale_trade_clean_name(get_the_title($product_id)) . ' for sign companies and resellers.';
	return wholesale_trade_fit_description('' === $text ? $lead . ' Trade pricing and online ordering once your account is approved.' : $lead . ' ' . $text);
}

/** Description for a product category page. */
function wholesale_trade_category_description($term)
{
	if (!$term || is_wp_error($term)) {
		return 'Trade-only products for approved accounts of Storefront Sign Wholesale.';
	}
	$text = wholesale_trade_meta_text(term_description($term), 18);
	$lead = 'Wholesale ' . wholesale_trade_clean_name($term->name) . ' for sign companies, installers and resellers, with trade pricing for approved accounts.';
	return wholesale_trade_fit_description('' === $text ? $lead : $lead . ' ' . $text);
}

/** Description for a public content page (trade copy, not the retail guide text). */
function wholesale_trade_page_description($page_id)
{
	$known = array(
		'contact' => 'Contact Storefront Sign Wholesale trade sales for channel letter quotes, artwork questions and order help. For sign companies, installers and resellers.',
		'reviews' => 'Customer reviews of Storefront Sign Wholesale channel letters and signs: build quality, packaging, shipping and support.',
		'warranty' => 'Warranty and claims for Storefront Sign Wholesale products: how to report a defect or order issue, and the five-year channel letter warranty.',
		'shipping-returns' => 'How Storefront Sign Wholesale ships trade orders: UPS and FedEx options, free blind drop ship and pickup, plus cancellations, reprints and damage claims.',
		'design-templates' => 'Print templates and artwork guides for banners, flags, displays and signs, so trade orders print at the right size with the right bleed.',
		'how-to-install-channel-letters' => 'Channel letter installation for sign crews, step by step: raceway or direct mount, the install pattern, power and inspection.',
		'sign-permits' => 'How sign permits work for lit channel letters: what cities ask for, sign drawings, UL labels and the electrician checklist your crew needs.',
		'banners-displays' => 'Wholesale banners and displays for sign companies: vinyl and fabric banners, banner stands, flags and trade show displays, with trade pricing.',
		'window-graphics' => 'Wholesale window graphics and adhesive vinyl for sign shops: perforated window film, frosted and clear vinyl, window clings and wall graphics.',
	);
	$slug = (string) get_post_field('post_name', $page_id);
	if (isset($known[$slug])) {
		return $known[$slug];
	}
	$text = wholesale_trade_meta_text(get_post_field('post_excerpt', $page_id), 22);
	return '' !== $text ? $text : wp_strip_all_tags(get_the_title($page_id)) . ' from Storefront Sign Wholesale, the trade-only channel letter and sign fabricator for sign companies, installers and resellers.';
}

/** Pages that must stay out of search: account, order, payment and placeholder pages. */
function wholesale_trade_is_private_page()
{
	return in_array((int) get_queried_object_id(), array_map('intval', wholesale_seo_noindex_page_ids()), true)
		|| is_page('shop') || is_page_template('page-shop.php') // Priced catalog: approved accounts only.
		|| (function_exists('wholesale_trade_is_order_page') && wholesale_trade_is_order_page())
		|| (bool) get_query_var('wholesale_thank_you')
		|| post_password_required();
}

/**
 * Whether a published page belongs in the sitemap: public, indexable and
 * answering 200 on the trade site (not redirected by inc/trade/settings.php or
 * gate.php, not a retired 410 location or industry page).
 */
function wholesale_trade_sitemap_page_ok($page)
{
	$page = get_post($page);
	if (!$page || 'page' !== $page->post_type || 'publish' !== $page->post_status || '' !== $page->post_password) {
		return false;
	}
	if ((int) get_option('page_on_front') === (int) $page->ID || in_array((int) $page->ID, array_map('intval', wholesale_seo_noindex_page_ids()), true)
		|| wholesale_trade_page_duplicate_of($page->ID)) {
		return false;
	}
	$redirected = array('custom-channel-letters', 'chennel-letter-ads', 'landing-page', 'channel-letter-cost', 'storefront-signs', 'shop', 'locations', 'cart', 'checkout', 'payment', 'pay', 'my-orders', 'orders');
	$templates = array('page-channel-letters.php', 'page-channel-letters-ads.php', 'landing-page.php', 'page-channel-letter-cost.php', 'page-storefront-signs.php', 'page-shop.php',
		'cart.php', 'checkout.php', 'payment.php', 'page-pay.php', 'my_orders.php',
		'page-location.php', 'page-locations.php', 'page-industry.php', 'page-industries.php', 'page-sign-packages.php');
	if (in_array($page->post_name, $redirected, true) || in_array(get_page_template_slug($page), $templates, true)) {
		return false;
	}
	if (function_exists('wholesale_guide_entry')) {
		$entry = wholesale_guide_entry(get_page_uri($page));
		if ($entry && (in_array($entry['template'], $templates, true) || 'live' !== $entry['status'])) {
			return false;
		}
	}
	return true;
}

/**
 * Title, description and index rule for the current request.
 *
 * @return array{title:string,description:string,index:bool,canonical:string}
 */
function wholesale_trade_seo()
{
	static $seo = null;
	if (null !== $seo) {
		return $seo;
	}
	$brand = ' | Storefront Sign Wholesale';
	$seo = array('title' => '', 'description' => '', 'index' => false, 'canonical' => '');
	$virtual = wholesale_trade_virtual_page();
	$virtual_pages = wholesale_trade_virtual_pages();

	if (is_front_page()) {
		$seo = array(
			'title' => 'Wholesale Channel Letter Manufacturer | Trade Only Since 2002',
			'description' => 'Wholesale channel letters for sign shops: 7 UL listed types built in Washington and California, shipped to all 50 states with wiring diagrams and patterns.',
			'index' => true,
			'canonical' => home_url('/'),
		);
	} elseif ($virtual && isset($virtual_pages[$virtual])) {
		$page = $virtual_pages[$virtual];
		$seo = array('title' => $page['title'], 'description' => $page['description'], 'index' => true, 'canonical' => home_url('/' . $virtual . '/'));
	} elseif (wholesale_trade_is_cl_hub()) {
		$seo = array(
			'title' => 'Wholesale Channel Letters, All 7 Types' . $brand,
			'description' => 'Compare 7 wholesale channel letter types for sign jobs: aluminum face, back and dual lit, or stainless halo, reverse, trimless and borderless. Trade only.',
			'index' => true,
			'canonical' => home_url('/channel-letters/'),
		);
	} elseif ($type = wholesale_trade_current_cl_type()) {
		$seo = array('title' => $type['title'], 'description' => $type['description'], 'index' => true, 'canonical' => get_permalink(get_queried_object_id()));
	} elseif (is_singular('product')) {
		// The rest of the catalog: price-free trade pages, indexed. A product that
		// duplicates another ("graphic only" next to "graphic + frame") points its
		// canonical at that one and stays out of the sitemap.
		$product_id = get_queried_object_id();
		$canonical_of = function_exists('wholesale_seo_product_canonical_of') ? (int) wholesale_seo_product_canonical_of($product_id) : 0;
		$seo = array(
			'title' => wholesale_trade_catalog_title(get_the_title()),
			'description' => wholesale_trade_product_description($product_id),
			'index' => true,
			'canonical' => $canonical_of ? get_permalink($canonical_of) : get_permalink($product_id),
		);
	} elseif (is_page('about')) {
		$seo = array(
			'title' => 'Channel Letter Fabrication Shop' . $brand,
			'description' => 'Storefront Sign Wholesale builds UL listed channel letters for sign companies in Washington and California, since 2002. We fabricate; your crews install.',
			'index' => true,
			'canonical' => get_permalink(),
		);
	} elseif (is_page('terms-conditions')) {
		$seo = array('title' => 'Trade Terms and Conditions' . $brand, 'description' => 'Terms for all Storefront Sign Wholesale products, including orders, proofs, cancellations, defect claims and reprints.', 'index' => true, 'canonical' => get_permalink());
	} elseif (is_page('channel-letter-builder')) {
		$seo = array('title' => 'Channel Letter Builder for Trade Accounts' . $brand, 'description' => 'Design and price channel letter sets online with a trade account: pick the type, font, height and colors, then order with shop drawings and a mounting pattern.', 'index' => true, 'canonical' => get_permalink());
	} elseif (is_page()) {
		$page_id = get_queried_object_id();
		$copy_of = wholesale_trade_page_duplicate_of($page_id);
		$seo = array('title' => wp_strip_all_tags(get_the_title()) . $brand, 'description' => wholesale_trade_page_description($copy_of ? $copy_of : $page_id), 'index' => !wholesale_trade_is_private_page(), 'canonical' => get_permalink($copy_of ? $copy_of : $page_id));
	} elseif (get_query_var('category_slug')) {
		$slug = sanitize_title((string) get_query_var('category_slug'));
		$term = get_term_by('slug', $slug, 'product_category');
		$name = $term ? $term->name : 'Products';
		// Empty categories are thin; redirected ones (signs-letters) never render.
		$excluded = function_exists('wholesale_seo_sitemap_excluded_categories') ? wholesale_seo_sitemap_excluded_categories() : array();
		$seo = array(
			'title' => wholesale_trade_catalog_title($name),
			'description' => wholesale_trade_category_description($term),
			'index' => $term && !in_array($slug, $excluded, true) && !(function_exists('wholesale_seo_is_empty_category_page') && wholesale_seo_is_empty_category_page()),
			'canonical' => wholesale_category_url($slug),
		);
	} elseif (is_search()) {
		$seo = array('title' => 'Search' . $brand, 'description' => '', 'index' => false, 'canonical' => '');
	} elseif (is_404()) {
		$seo = array('title' => 'Page not found' . $brand, 'description' => '', 'index' => false, 'canonical' => '');
	} else {
		$seo['title'] = 'Storefront Sign Wholesale';
	}

	$seo['description'] = wholesale_trade_fit_description($seo['description']);

	// Filtered or parameterized views never index (?s=, ?orderby=, utm tags…).
	if ($seo['index'] && !empty($_GET)) {
		$allowed = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid');
		if (array_diff(array_keys($_GET), $allowed)) {
			$seo['index'] = false;
		}
	}
	return $seo;
}

/** One title for the whole document (short-circuits the retail title filters). */
add_filter('pre_get_document_title', static function ($title) {
	$seo = wholesale_trade_seo();
	return $seo['title'] ? $seo['title'] : $title;
}, 99);

add_filter('wp_robots', static function ($robots) {
	$seo = wholesale_trade_seo();
	if (!$seo['index']) {
		return array('noindex' => true, 'follow' => true);
	}
	// Keep restrictions added earlier: core (Discourage search engines, /embed/
	// copies) and the SEO screen's per-page noindex/nofollow.
	if (!empty($robots['noindex']) || !empty($robots['nofollow'])) {
		return $robots;
	}
	return $robots + array('max-image-preview' => 'large');
}, 99);

/** Breadcrumb trail for the current page (also printed as BreadcrumbList). */
function wholesale_trade_crumbs()
{
	$crumbs = array(array('name' => 'Home', 'url' => home_url('/')));
	if (is_front_page()) {
		return $crumbs; // One item: no "Home > Home" trail or BreadcrumbList.
	}
	$virtual = wholesale_trade_virtual_page();
	$pages = wholesale_trade_virtual_pages();
	if ($virtual && isset($pages[$virtual])) {
		$crumbs[] = array('name' => $pages[$virtual]['crumb'], 'url' => home_url('/' . $virtual . '/'));
	} elseif (wholesale_trade_is_cl_hub()) {
		$crumbs[] = array('name' => 'Channel Letters', 'url' => home_url('/channel-letters/'));
	} elseif ($type = wholesale_trade_current_cl_type()) {
		$crumbs[] = array('name' => 'Channel Letters', 'url' => home_url('/channel-letters/'));
		$crumbs[] = array('name' => 'Type ' . $type['type'] . ': ' . $type['name'], 'url' => get_permalink(get_queried_object_id()));
	} elseif (is_singular('product')) {
		$term = wholesale_trade_product_category(get_queried_object_id());
		if ($term) {
			$crumbs[] = array('name' => $term->name, 'url' => wholesale_category_url($term->slug));
		} else {
			$crumbs[] = array('name' => 'Other trade products', 'url' => (function_exists('wholesale_trade_can_see_prices') && wholesale_trade_can_see_prices()) ? home_url('/shop/') : home_url('/channel-letters/'));
		}
		$crumbs[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
	} elseif (get_query_var('category_slug') && ($term = get_term_by('slug', sanitize_title((string) get_query_var('category_slug')), 'product_category'))) {
		$crumbs[] = array('name' => $term->name, 'url' => wholesale_category_url($term->slug));
	} elseif (is_page()) {
		$crumbs[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
	}
	return $crumbs;
}

/**
 * The category a catalog product's breadcrumb links to: its first category
 * with a public, indexable page (the most specific one when it has several).
 */
function wholesale_trade_product_category($product_id)
{
	$terms = get_the_terms($product_id, 'product_category');
	if (!$terms || is_wp_error($terms)) {
		return null;
	}
	$excluded = function_exists('wholesale_seo_sitemap_excluded_categories') ? wholesale_seo_sitemap_excluded_categories() : array('channel-letters');
	$terms = array_values(array_filter($terms, static function ($term) use ($excluded) {
		return !in_array($term->slug, $excluded, true);
	}));
	if (!$terms) {
		return null;
	}
	// Children before parents: "Flag Hardware" over "Hardware Only".
	usort($terms, static function ($a, $b) {
		return ((int) (0 === (int) $a->parent)) - ((int) (0 === (int) $b->parent)) ?: strcmp($a->name, $b->name);
	});
	return $terms[0];
}

function wholesale_trade_render_crumbs()
{
	$crumbs = wholesale_trade_crumbs();
	if (count($crumbs) < 2) {
		return;
	}
	echo '<nav class="tw-crumbs" aria-label="Breadcrumbs"><ol>';
	foreach ($crumbs as $i => $crumb) {
		echo '<li>' . (count($crumbs) - 1 === $i ? '<span aria-current="page">' . esc_html($crumb['name']) . '</span>' : '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['name']) . '</a>') . '</li>';
	}
	echo '</ol></nav>';
}

/** FAQ items a template printed on the page (FAQPage markup only for visible FAQs). */
function wholesale_trade_register_faq($items)
{
	$GLOBALS['wholesale_trade_faq'] = array_merge(isset($GLOBALS['wholesale_trade_faq']) ? $GLOBALS['wholesale_trade_faq'] : array(), (array) $items);
}

function wholesale_trade_logo_url()
{
	$logo = get_theme_mod('custom_logo');
	return $logo ? (string) wp_get_attachment_image_url($logo, 'full') : '';
}

/** Canonical, description, social tags and JSON-LD. */
add_action('wp_head', static function () {
	$seo = wholesale_trade_seo();
	$brand = wholesale_trade_brand();
	$home = trailingslashit(home_url('/'));
	$url = $seo['canonical'];

	if ($url && $seo['index']) {
		echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
	}
	if ($seo['description']) {
		echo '<meta name="description" content="' . esc_attr($seo['description']) . '">' . "\n";
	}

	// Share image by real size (full file, not a resized copy): a product's own
	// photo when it is at least 600px wide, else the front page hero (the
	// large storefront photo), else the logo. [url, width, height].
	$image_src = array();
	if (is_singular('product')) {
		$thumb = wp_get_attachment_image_src((int) get_post_thumbnail_id(get_queried_object_id()), 'full');
		$image_src = ($thumb && (int) $thumb[1] >= 600) ? $thumb : array();
	}
	if (!$image_src) {
		$hero_id = (int) get_post_thumbnail_id((int) get_option('page_on_front'));
		$hero = $hero_id ? wp_get_attachment_image_src($hero_id, 'full') : false;
		$logo_id = (int) get_theme_mod('custom_logo');
		$logo = $logo_id ? wp_get_attachment_image_src($logo_id, 'full') : false;
		$image_src = $hero ? $hero : ($logo ? $logo : array());
	}
	$image = $image_src ? (string) $image_src[0] : '';
	$image_w = $image_src ? (int) $image_src[1] : 0;
	$image_h = $image_src ? (int) $image_src[2] : 0;

	$og = array(
		'og:type' => 'website',
		'og:site_name' => $brand['name'],
		'og:title' => $seo['title'],
		'og:description' => $seo['description'],
		'og:url' => $url,
		'og:image' => $image,
		'og:image:width' => $image && $image_w ? (string) $image_w : '',
		'og:image:height' => $image && $image_h ? (string) $image_h : '',
	);
	foreach (array_filter($og) as $property => $content) {
		echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ($image && $image_w >= 300 ? 'summary_large_image' : 'summary') . '">' . "\n";

	if (!$seo['index']) {
		return;
	}

	// Sitewide entities. No LocalBusiness: there is no visitable trade counter.
	$org = array(
		'@type' => 'Organization',
		'@id' => $home . '#organization',
		'name' => $brand['name'],
		'legalName' => $brand['legal_name'],
		'url' => $home,
		'foundingDate' => (string) $brand['since'],
		'description' => 'Trade-only manufacturer of UL listed LED channel letters for sign companies, installers and resellers.',
		'knowsAbout' => array('Channel letter fabrication', 'LED sign lighting', 'Halo lit stainless steel letters', 'Sign raceways', 'UL 48 electric signs'),
		'contactPoint' => array(array(
			'@type' => 'ContactPoint',
			'contactType' => 'sales',
			'name' => 'Trade sales',
			'telephone' => '+1-' . $brand['phone'],
			'email' => $brand['email'],
			'areaServed' => 'US',
			'availableLanguage' => 'en',
		)),
	);
	if ($logo = wholesale_trade_logo_url()) {
		$org['logo'] = $logo;
	}
	$graph = array(
		$org,
		array('@type' => 'WebSite', '@id' => $home . '#website', 'url' => $home, 'name' => $brand['name'], 'publisher' => array('@id' => $home . '#organization'), 'inLanguage' => 'en-US'),
	);

	$crumbs = wholesale_trade_crumbs();
	$page = array(
		'@type' => (is_front_page() || wholesale_trade_is_cl_hub()) ? 'CollectionPage' : (is_page('about') ? 'AboutPage' : 'WebPage'),
		'@id' => trailingslashit($url) . '#webpage',
		'url' => $url,
		'name' => $seo['title'],
		'description' => $seo['description'],
		'isPartOf' => array('@id' => $home . '#website'),
		'about' => array('@id' => $home . '#organization'),
		'inLanguage' => 'en-US',
	);
	if ($image) {
		$page['primaryImageOfPage'] = array('@type' => 'ImageObject', 'url' => $image);
		if ($image_w && $image_h) {
			$page['primaryImageOfPage'] += array('width' => $image_w, 'height' => $image_h);
		}
	}
	// Same date as the sitemap lastmod (content built from code counts).
	$modified_key = is_front_page() ? 'home' : (wholesale_trade_is_cl_hub() ? 'hub' : (wholesale_trade_current_cl_type() ? 'type' : (is_page('about') ? 'about' : (is_page('terms-conditions') ? 'terms-conditions' : ''))));
	if ($modified_key) {
		$page['dateModified'] = wholesale_trade_content_modified(is_singular() ? get_queried_object_id() : 0, $modified_key);
	} elseif (is_singular()) {
		$page['dateModified'] = get_post_modified_time('c', true, get_queried_object_id());
	}
	if (count($crumbs) > 1) {
		$page['breadcrumb'] = array('@id' => trailingslashit($url) . '#breadcrumb');
		$list = array();
		foreach ($crumbs as $i => $crumb) {
			$list[] = array('@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'], 'item' => $crumb['url']);
		}
		$graph[] = array('@type' => 'BreadcrumbList', '@id' => trailingslashit($url) . '#breadcrumb', 'itemListElement' => $list);
	}
	// Hubs list the 7 types; only their own pages describe them as Products.
	if (is_front_page() || wholesale_trade_is_cl_hub()) {
		$items = array();
		foreach (wholesale_trade_cl_types() as $slug => $type) {
			$id = wholesale_trade_cl_product_id($slug);
			if ($id) {
				$items[] = array('@type' => 'ListItem', 'position' => count($items) + 1, 'name' => 'Type ' . $type['type'] . ': ' . $type['name'] . ' channel letters', 'url' => get_permalink($id));
			}
		}
		if ($items) {
			$page['mainEntity'] = array('@type' => 'ItemList', 'name' => 'Wholesale channel letter types', 'numberOfItems' => count($items), 'itemListElement' => $items);
		}
	}
	if ($type = wholesale_trade_current_cl_type()) {
		$product_id = get_queried_object_id();
		$product = array(
			'@type' => 'Product',
			'@id' => trailingslashit($url) . '#product',
			'name' => $type['h1'],
			'description' => $type['lead'],
			'url' => $url,
			'category' => 'Wholesale channel letters',
			'material' => 'aluminum' === $type['family'] ? 'Aluminum' : 'Stainless steel',
			'manufacturer' => array('@id' => $home . '#organization'),
			'additionalProperty' => array(),
		);
		$photo = get_the_post_thumbnail_url($product_id, 'full');
		if (!$photo && !empty($type['image'])) {
			$filename = basename($type['image']);
			if (is_file(WP_CONTENT_DIR . '/uploads/storefront-files/' . $filename)) {
				$photo = content_url('uploads/storefront-files/' . $filename);
			}
		}
		if ($photo) {
			$product['image'] = array($photo);
		}
		foreach ($type['specs'] as $label => $value) {
			$product['additionalProperty'][] = array('@type' => 'PropertyValue', 'name' => $label, 'value' => $value);
		}
		$product['additionalProperty'][] = array('@type' => 'PropertyValue', 'name' => 'Warranty', 'value' => '5 years');
		$product['additionalProperty'][] = array('@type' => 'PropertyValue', 'name' => 'Built in', 'value' => 'Washington and California; ships to all 50 states');
		$page['mainEntity'] = array('@id' => $product['@id']);
		$graph[] = $product;
	}
	$graph[] = $page;

	echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}, 1);

/** FAQPage for FAQs the template printed (wp_footer, after the page body ran). */
add_action('wp_footer', static function () {
	if (empty($GLOBALS['wholesale_trade_faq']) || !wholesale_trade_seo()['index']) {
		return;
	}
	$entities = array();
	foreach ($GLOBALS['wholesale_trade_faq'] as $item) {
		$entities[] = array('@type' => 'Question', 'name' => wp_strip_all_tags($item['q']), 'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags($item['a'])));
	}
	echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}, 5);

/**
 * Last change to a page's visible content: the later of the post's modified
 * date and the newest template/data file that renders it (these pages are
 * built from code, so post_modified alone is stale). Never the current time.
 *
 * @param int    $post_id Post behind the page, or 0.
 * @param string $key     'home', 'hub', 'type', 'about' or 'terms-conditions'.
 */
function wholesale_trade_content_modified($post_id, $key)
{
	$data = 'data/trade/channel-letters.php';
	$content = 'inc/trade/content.php';
	$files = array(
		'home' => array('template-parts/trade/home.php', $data, $content),
		'hub' => array('template-parts/trade/channel-letters.php', $data, $content),
		'type' => array('template-parts/trade/product.php', $data),
		'about' => array('template-parts/trade/about.php', $content),
		'terms-conditions' => array('template-parts/trade/terms.php'),
	);
	$time = $post_id ? (int) get_post_modified_time('U', true, $post_id) : 0;
	foreach (isset($files[$key]) ? $files[$key] : array() as $file) {
		$path = get_template_directory() . '/' . $file;
		if (is_file($path)) {
			$time = max($time, (int) filemtime($path));
		}
	}
	return $time ? gmdate('c', $time) : '';
}

/** Every indexable trade URL (the sitemap lists exactly these): trade pages first, then content pages, categories and products. */
function wholesale_trade_sitemap_urls()
{
	$urls = array(
		home_url('/') => wholesale_trade_content_modified((int) get_option('page_on_front'), 'home'),
		home_url('/channel-letters/') => wholesale_trade_content_modified(0, 'hub'),
	);
	foreach (array_keys(wholesale_trade_cl_types()) as $slug) {
		if ($id = wholesale_trade_cl_product_id($slug)) {
			$urls[get_permalink($id)] = wholesale_trade_content_modified($id, 'type');
		}
	}
	foreach (wholesale_trade_virtual_pages() as $path => $page) {
		$urls[home_url('/' . $path . '/')] = $page['modified'];
	}
	foreach (array('about', 'terms-conditions') as $slug) {
		$page = get_page_by_path($slug);
		if ($page && 'publish' === $page->post_status) {
			$urls[get_permalink($page)] = wholesale_trade_content_modified((int) $page->ID, $slug);
		}
	}

	// The public content pages (contact, warranty, guides...).
	$pages = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true));
	foreach ($pages as $page) {
		$url = get_permalink($page);
		if ($url && !isset($urls[$url]) && wholesale_trade_sitemap_page_ok($page)) {
			$urls[$url] = get_post_modified_time('c', true, $page);
		}
	}

	// Category pages with products, dated by their newest product.
	$excluded_terms = function_exists('wholesale_seo_sitemap_excluded_categories') ? wholesale_seo_sitemap_excluded_categories() : array('channel-letters');
	foreach (function_exists('wholesale_seo_sitemap_categories') ? wholesale_seo_sitemap_categories() : array() as $term) {
		$url = wholesale_category_url($term->slug);
		if (in_array($term->slug, $excluded_terms, true) || isset($urls[$url])) {
			continue;
		}
		$newest = get_posts(array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'orderby' => 'modified',
			'order' => 'DESC',
			'no_found_rows' => true,
			'tax_query' => array(array('taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => (int) $term->term_id, 'include_children' => true)),
		));
		$urls[$url] = $newest ? get_post_modified_time('c', true, $newest[0]) : '';
	}

	// The rest of the catalog, minus products whose canonical is another product.
	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post__not_in' => function_exists('wholesale_seo_sitemap_excluded_product_ids') ? wholesale_seo_sitemap_excluded_product_ids() : array(),
		'orderby' => 'title',
		'order' => 'ASC',
		'no_found_rows' => true,
	));
	foreach ($products as $product) {
		$url = get_permalink($product);
		if ($url && !isset($urls[$url]) && '' === $product->post_password) {
			$urls[$url] = function_exists('wholesale_product_content_modified') ? wholesale_product_content_modified($product) : get_post_modified_time('c', true, $product);
		}
	}
	return $urls;
}

/** /sitemap.xml: one urlset. The retail sub-sitemaps answer with a redirect to it. */
add_action('template_redirect', static function () {
	$sitemap = get_query_var('wholesale_sitemap');
	$others = get_query_var('wholesale_product_sitemap') || get_query_var('wholesale_category_sitemap');
	if (!$sitemap && !$others) {
		return;
	}
	if ($others || '1' !== (string) $sitemap) {
		wp_safe_redirect(home_url('/sitemap.xml'), 301);
		exit;
	}
	status_header(200);
	header('Content-Type: application/xml; charset=UTF-8');
	header('X-Robots-Tag: noindex, follow');
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	foreach (wholesale_trade_sitemap_urls() as $loc => $lastmod) {
		echo "\t<url><loc>" . esc_url($loc) . '</loc>' . ($lastmod ? '<lastmod>' . esc_html($lastmod) . '</lastmod>' : '') . "</url>\n";
	}
	echo '</urlset>';
	exit;
}, 0);

/**
 * /signs-letters/ went to the retail Storefront Signs guide, which redirects to
 * the trade hub: one hop straight to the hub instead of a chain.
 */
add_action('template_redirect', static function () {
	if ('signs-letters' === sanitize_title((string) get_query_var('category_slug'))) {
		wp_safe_redirect(home_url('/channel-letters/'), 301);
		exit;
	}
}, 0);

/** The home page has no pages: /page/N/ (and ?page=N) were 200 duplicates of it. */
add_action('template_redirect', static function () {
	if (is_front_page() && ((int) get_query_var('paged') > 1 || (int) get_query_var('page') > 1)) {
		wp_safe_redirect(home_url('/'), 301);
		exit;
	}
}, 1);

/** robots.txt for the trade site. */
add_filter('robots_txt', static function ($output, $public) {
	if (!$public) {
		return $output;
	}
	$path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
	$path = $path ? trailingslashit($path) : '/';
	$lines = array('User-agent: *');
	foreach (array('wp-admin/', 'cart/', 'checkout/', 'payment/', 'pay/', 'account/', 'my-orders/', 'login/', 'thank-you/', '*?s=', '*?category_slug=', '*?orderby=') as $rule) {
		$lines[] = 'Disallow: ' . (0 === strpos($rule, '*') ? $rule : $path . $rule);
	}
	$lines[] = 'Allow: ' . $path . 'wp-admin/admin-ajax.php';
	$lines[] = '';
	$lines[] = 'Sitemap: ' . home_url('/sitemap.xml');
	return implode("\n", $lines) . "\n";
}, 99, 2);
