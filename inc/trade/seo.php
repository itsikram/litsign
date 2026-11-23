<?php
/**
 * Trade site SEO: its own titles, descriptions, schema, robots and sitemap.
 *
 * Deliberately different from the retail site (wholesale-plan/keyword-map.md):
 * - targets trade intent (wholesale, manufacturer, fabrication, for sign
 *   companies), never retail terms (custom/near me/cost/{place}, storefront
 *   signs, print products);
 * - title formula "{Primary keyword} | Storefront Sign Wholesale";
 * - no Product/Offer/price markup (prices are login-only), no Merchant feed,
 *   no LocalBusiness, no location pages;
 * - only channel letter pages and trade pages are indexed; the rest of the
 *   catalog is noindex,follow and out of the sitemap.
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
			'description' => 'Wholesale channel letters for sign companies: 7 UL listed types, built in Washington and California, shipped to all 50 states with wiring diagrams and patterns.',
			'index' => true,
			'canonical' => home_url('/'),
		);
	} elseif ($virtual && isset($virtual_pages[$virtual])) {
		$page = $virtual_pages[$virtual];
		$seo = array('title' => $page['title'], 'description' => $page['description'], 'index' => true, 'canonical' => home_url('/' . $virtual . '/'));
	} elseif (wholesale_trade_is_cl_hub()) {
		$seo = array(
			'title' => 'Wholesale Channel Letters, All 7 Types' . $brand,
			'description' => 'Compare 7 wholesale channel letter types for your sign jobs: aluminum face, back and dual lit, plus stainless halo, reverse, trimless and borderless. Trade only.',
			'index' => true,
			'canonical' => home_url('/channel-letters/'),
		);
	} elseif ($type = wholesale_trade_current_cl_type()) {
		$seo = array('title' => $type['title'], 'description' => $type['description'], 'index' => true, 'canonical' => get_permalink(get_queried_object_id()));
	} elseif (is_singular('product')) {
		// The rest of the catalog: orderable by approved accounts, never indexed
		// (retail owns those searches and the copy is supplier text).
		$seo = array(
			'title' => wp_strip_all_tags(get_the_title()) . ' for Trade Accounts' . $brand,
			'description' => 'Trade-only ordering for approved sign companies. ' . wp_trim_words(wp_strip_all_tags((string) get_post_field('post_excerpt', get_queried_object_id())), 18, '…'),
			'index' => false,
			'canonical' => get_permalink(get_queried_object_id()),
		);
	} elseif (is_page('about')) {
		$seo = array(
			'title' => 'About Our Channel Letter Fabrication Shop' . $brand,
			'description' => 'Storefront Sign Wholesale builds UL listed channel letters for sign companies in Washington and California, since 2002. We fabricate; your installers install.',
			'index' => true,
			'canonical' => get_permalink(),
		);
	} elseif (is_page('terms-conditions')) {
		$seo = array('title' => 'Trade Terms and Conditions' . $brand, 'description' => 'Terms for trade accounts ordering channel letters from Storefront Sign Wholesale: ordering, artwork approval, shipping, damage claims and warranty.', 'index' => true, 'canonical' => get_permalink());
	} elseif (is_page('channel-letter-builder')) {
		$seo = array('title' => 'Channel Letter Builder for Trade Accounts' . $brand, 'description' => 'Design and price channel letter sets online with a trade account: pick the type, font, height and colors, then order with shop drawings and a mounting pattern.', 'index' => false, 'canonical' => get_permalink());
	} elseif (is_page()) {
		$seo = array('title' => wp_strip_all_tags(get_the_title()) . $brand, 'description' => 'Storefront Sign Wholesale: trade-only channel letter fabrication for sign companies, installers and resellers.', 'index' => false, 'canonical' => get_permalink());
	} elseif (get_query_var('category_slug')) {
		$term = get_term_by('slug', sanitize_title((string) get_query_var('category_slug')), 'product_category');
		$seo = array('title' => ($term ? $term->name : 'Products') . ' for Trade Accounts' . $brand, 'description' => 'Trade-only products for approved accounts of Storefront Sign Wholesale.', 'index' => false, 'canonical' => wholesale_category_url(sanitize_title((string) get_query_var('category_slug'))));
	} elseif (is_search()) {
		$seo = array('title' => 'Search' . $brand, 'description' => '', 'index' => false, 'canonical' => '');
	} elseif (is_404()) {
		$seo = array('title' => 'Page not found' . $brand, 'description' => '', 'index' => false, 'canonical' => '');
	} else {
		$seo['title'] = 'Storefront Sign Wholesale';
	}

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
	unset($robots['noindex'], $robots['nofollow']);
	return $robots + array('max-image-preview' => 'large');
}, 99);

/** Breadcrumb trail for the current page (also printed as BreadcrumbList). */
function wholesale_trade_crumbs()
{
	$crumbs = array(array('name' => 'Home', 'url' => home_url('/')));
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
		$crumbs[] = array('name' => 'Other trade products', 'url' => home_url('/shop/'));
		$crumbs[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
	} elseif (is_page()) {
		$crumbs[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
	}
	return $crumbs;
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

	$image = '';
	if ($type = wholesale_trade_current_cl_type()) {
		$image = get_the_post_thumbnail_url(get_queried_object_id(), 'large');
	} elseif (is_front_page() || wholesale_trade_is_cl_hub()) {
		$front = wholesale_trade_cl_product_id('standard-channel-letter-front-lit');
		$image = $front ? get_the_post_thumbnail_url($front, 'large') : '';
	}
	$image = $image ? $image : wholesale_trade_logo_url();

	$og = array(
		'og:type' => 'website',
		'og:site_name' => $brand['name'],
		'og:title' => $seo['title'],
		'og:description' => $seo['description'],
		'og:url' => $url,
		'og:image' => $image,
	);
	foreach (array_filter($og) as $property => $content) {
		echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";

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
	}
	if (is_singular()) {
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
	// Hubs list the 7 types (no Product markup: prices are login-only).
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

/** Every indexable trade URL (the sitemap lists exactly these). */
function wholesale_trade_sitemap_urls()
{
	$urls = array(home_url('/') => current_time('c'), home_url('/channel-letters/') => current_time('c'));
	foreach (array_keys(wholesale_trade_cl_types()) as $slug) {
		if ($id = wholesale_trade_cl_product_id($slug)) {
			$urls[get_permalink($id)] = get_post_modified_time('c', true, $id);
		}
	}
	foreach (wholesale_trade_virtual_pages() as $path => $page) {
		$urls[home_url('/' . $path . '/')] = $page['modified'];
	}
	foreach (array('about', 'terms-conditions') as $slug) {
		$page = get_page_by_path($slug);
		if ($page && 'publish' === $page->post_status) {
			$urls[get_permalink($page)] = get_post_modified_time('c', true, $page);
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
