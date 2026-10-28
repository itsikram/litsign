<?php
/**
 * Search visibility helpers: removed-content status codes and one-time
 * database updates that ship with the theme.
 *
 * @package litsign
 */

/**
 * Whether a request path looks like one of the spam posts injected during the
 * September 2026 compromise (casino, betting and dating articles). Only used
 * for requests that already 404, so a false match changes nothing but the code.
 */
function wholesale_is_removed_spam_path($path)
{
	$slug = trim((string) $path, '/');
	if ('' === $slug) {
		return false;
	}

	// category/bez-rubriki is the spam posts' "Uncategorized" (Russian) archive.
	$known = array('article-jule-24000', 'hello-world', 'hello-world-2', 'category/bez-rubriki');
	if (in_array($slug, $known, true)) {
		return true;
	}

	return (bool) preg_match(
		'/(?:^|[-\/])(?:1win|1vin|casino\w*|kazino\w*|kasino|bahis|bukmeker\w*|pin-?up|slots?|aviator|olympus|poker\w*|jackpot|bett(?:ing|ors?)|\w*bet|bonus\w*|spins?|apuestas|kumar|oyun\w*|stavki|zerkalo|igrov\w*|onlajn|onlayn|kripto\w*|billionairespin|dating|singles|marry|sigara\w*|jeux|jogos|gioco|giocatore|igaming|gambling|roulette|blackjack|bookmaker|vulkan|mostbet|melbet|fortune|reload|dealer|auszahlung\w*)(?:[-\/]|$)/i',
		$slug
	);
}

/**
 * Answer removed spam URLs with 410 Gone so search engines drop them faster
 * than a plain 404. The normal 404 template still renders.
 */
function wholesale_removed_spam_status()
{
	if (!is_404()) {
		return;
	}

	$path = wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH);
	$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
	if ($home_path && 0 === strpos((string) $path, $home_path)) {
		$path = substr((string) $path, strlen($home_path));
	}

	if (wholesale_is_removed_spam_path($path)) {
		status_header(410);
	}
}
add_action('template_redirect', 'wholesale_removed_spam_status', 2);

/**
 * The owner's other domains. They are meant to 301 at the server
 * (seo-plan/domain-redirects.md); if one still reaches WordPress, for example
 * as a cPanel alias of this site, it is sent to the same path on the main
 * site here instead of serving a duplicate copy with its own canonical URLs.
 *
 * @return string[] Hosts without www.
 */
function wholesale_seo_extra_domains()
{
	return array(
		'storefrontsignwholesale.com',
		'storefrontsignwholesales.com',
		'onlinestorefrontsigns.com',
		'mystorefrontsigns.com',
		'mystorefrontsign.com',
	);
}

function wholesale_seo_redirect_extra_domains()
{
	if (is_admin() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI) || empty($_SERVER['HTTP_HOST'])) {
		return;
	}

	// Only the exact extra domains, with or without www. The main domain, IPs
	// and localhost never match.
	$host = strtolower(preg_replace('/^www\.|:\d+$/', '', (string) wp_unslash($_SERVER['HTTP_HOST'])));
	if (!in_array($host, wholesale_seo_extra_domains(), true)) {
		return;
	}

	// Never wp-admin, cron, the REST API or certificate validation, whatever the host.
	$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
	$path = (string) wp_parse_url($uri, PHP_URL_PATH);
	if (preg_match('#/(?:wp-admin/|wp-cron\.php|wp-json(?:/|$)|\.well-known/)#', $path) || isset($_GET['rest_route'])) {
		return;
	}

	// Hard-coded target: home_url() may follow the requested host on the live server.
	wp_redirect('https://storefrontsignonline.com' . ('/' === substr($uri, 0, 1) ? $uri : '/'), 301);
	exit;
}
add_action('init', 'wholesale_seo_redirect_extra_domains', 1);

/**
 * Plain-http requests for the main domain go to https. WordPress only does this
 * for the home page: every other page (e.g. http://storefrontsignonline.com/product/magnets/)
 * answered 200 over http, a second copy of the site. Like the extra domains, this
 * belongs at the server (root .htaccess); it runs here until it is there.
 */
function wholesale_seo_redirect_to_https()
{
	if (is_ssl() || is_admin() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI) || empty($_SERVER['HTTP_HOST'])) {
		return;
	}
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		return;
	}

	// Only the live domain (localhost and staging copies never match), and only
	// GET/HEAD: a redirected POST loses its body.
	$host = strtolower(preg_replace('/^www\.|:\d+$/', '', (string) wp_unslash($_SERVER['HTTP_HOST'])));
	$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
	if ('storefrontsignonline.com' !== $host || !in_array($method, array('GET', 'HEAD'), true)) {
		return;
	}

	// Certificate validation must keep working over http.
	$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
	if (preg_match('#/(?:wp-cron\.php|\.well-known/)#', (string) wp_parse_url($uri, PHP_URL_PATH))) {
		return;
	}

	wp_redirect('https://storefrontsignonline.com' . ('/' === substr($uri, 0, 1) ? $uri : '/'), 301);
	exit;
}
add_action('init', 'wholesale_seo_redirect_to_https', 1);

/**
 * WordPress's old-slug redirect (a renamed product, e.g. /product/pole-banner-set/,
 * a Google Ads sitelink) rebuilds the URL without the query string, which drops
 * ?gclid and UTM tags and breaks ad attribution. Carry them over.
 */
function wholesale_seo_old_slug_keep_query($link)
{
	$query = isset($_SERVER['QUERY_STRING']) ? (string) wp_unslash($_SERVER['QUERY_STRING']) : '';
	if ('' === $query || false !== strpos((string) $link, '?')) {
		return $link;
	}

	parse_str($query, $args);
	return $args ? add_query_arg(array_map('rawurlencode', array_filter($args, 'is_scalar')), $link) : $link;
}
add_filter('old_slug_redirect_url', 'wholesale_seo_old_slug_keep_query');

/**
 * Permanently redirect leftover placeholder pages to the page that replaces them.
 *
 * @return array Page slug => target URL.
 */
function wholesale_seo_page_redirects()
{
	return array(
		'sample-page' => home_url('/'),
		'sample-page-2' => home_url('/'),
	);
}

function wholesale_seo_redirect_pages()
{
	if (!is_page()) {
		return;
	}

	$slug = get_post_field('post_name', get_queried_object_id());
	$redirects = wholesale_seo_page_redirects();

	if ($slug && isset($redirects[$slug])) {
		wp_safe_redirect($redirects[$slug], 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_pages', 1);

/**
 * The site has no blog authors: /author/{name}/ pages are thin duplicates
 * that also reveal admin user names. Send them home and keep them out of
 * the sitemap.
 */
function wholesale_seo_redirect_author_archives()
{
	if (is_author()) {
		wp_safe_redirect(home_url('/'), 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_author_archives', 1);

/**
 * WordPress answers a 404 by guessing a post whose slug starts with the
 * request, so /Banners/, /product_category/banners/ and /banners/page/2/ went
 * to /banners-displays/. When the path names a product category (any case,
 * under a taxonomy base, or with a page number) send it to that category.
 */
add_filter('pre_redirect_guess_404_permalink', function ($pre) {
	$path = trim((string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH), '/');
	$home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
	if ('' !== $home_path && 0 === strpos($path, $home_path . '/')) {
		$path = substr($path, strlen($home_path) + 1);
	}
	$path = preg_replace('#/page/\d+$#i', '', strtolower($path));
	$path = preg_replace('#^(?:product[_-]category|category)/#', '', $path);
	if ('' === $path || false !== strpos($path, '/')) {
		return $pre;
	}

	$term = get_term_by('slug', sanitize_title($path), 'product_category');
	if ($term && !is_wp_error($term) && wholesale_seo_category_has_products($term)) {
		return wholesale_category_url($term->slug);
	}

	return $pre;
});

add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
	return 'users' === $name ? false : $provider;
}, 10, 2);

/**
 * Product category links point at the clean /{category}/ routes the theme
 * serves, instead of /category/{slug}/ URLs that only redirect there.
 */
function wholesale_seo_product_category_link($url, $term, $taxonomy)
{
	return 'product_category' === $taxonomy ? wholesale_category_url($term->slug) : $url;
}
add_filter('term_link', 'wholesale_seo_product_category_link', 10, 3);

/**
 * Categories whose route is canonicalized or redirected elsewhere stay out of
 * the core sitemap.
 */
function wholesale_seo_sitemap_excluded_categories()
{
	$excluded = array('channel-letters');

	// /signs-letters/ redirects to the Storefront Signs guide once it is published.
	if (wholesale_seo_storefront_signs_url()) {
		$excluded[] = 'signs-letters';
	}

	// Plus categories set to noindex or "exclude from sitemap" under SEO (inc/seo-admin/).
	return (array) apply_filters('wholesale_seo_sitemap_excluded_categories', $excluded);
}

/**
 * Whether a product category lists any products, counting its subcategories.
 * Hubs such as /indoor-outdoor-displays/ hold no products directly, so core's
 * hide_empty dropped them from every sitemap.
 */
function wholesale_seo_category_has_products($term)
{
	if ((int) $term->count > 0) {
		return true;
	}

	foreach (get_term_children($term->term_id, $term->taxonomy) as $child_id) {
		$child = get_term($child_id, $term->taxonomy);
		if ($child && !is_wp_error($child) && (int) $child->count > 0) {
			return true;
		}
	}

	return false;
}

/**
 * Product categories for the sitemaps: every category with products of its
 * own or in a subcategory.
 *
 * @return WP_Term[]
 */
function wholesale_seo_sitemap_categories()
{
	$terms = get_terms(array(
		'taxonomy' => 'product_category',
		'hide_empty' => false,
		'orderby' => 'name',
	));

	return is_wp_error($terms) ? array() : array_values(array_filter($terms, 'wholesale_seo_category_has_products'));
}

/**
 * A category page with nothing to list is thin content: keep it out of search.
 */
function wholesale_seo_is_empty_category_page()
{
	$slug = get_query_var('category_slug');
	if (!$slug) {
		return false;
	}

	$term = get_term_by('slug', sanitize_title($slug), 'product_category');

	return $term && !is_wp_error($term) && !wholesale_seo_category_has_products($term);
}

/**
 * URL of the published Storefront Signs guide, or ''.
 */
function wholesale_seo_storefront_signs_url()
{
	static $url = null;

	if (null === $url) {
		$page = get_page_by_path('storefront-signs');
		$url = $page && 'publish' === $page->post_status ? get_permalink($page) : '';
	}

	return $url;
}

/**
 * /signs-letters/ listed the same seven channel letter products as the home
 * page; send it to the Storefront Signs guide instead.
 */
function wholesale_seo_redirect_signs_letters()
{
	if ('signs-letters' === get_query_var('category_slug') && ($url = wholesale_seo_storefront_signs_url())) {
		wp_safe_redirect($url, 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_signs_letters', 1);

/**
 * /channel-letters/ rendered the same letters as the channel letters landing
 * page; send it (and its link equity) to /custom-channel-letters/.
 */
function wholesale_seo_redirect_channel_letters()
{
	if ('channel-letters' === get_query_var('category_slug') && wholesale_channel_letters_landing_id()) {
		// Keep ad tags (?gclid, UTM) but drop category_slug: carrying it over
		// makes the landing page match again and redirect to itself forever.
		$url = get_permalink(wholesale_channel_letters_landing_id());
		$args = array_filter($_GET, 'is_scalar');
		unset($args['category_slug']);
		if ($args) {
			$url = add_query_arg(array_map('rawurlencode', wp_unslash($args)), $url);
		}
		wp_safe_redirect($url, 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_channel_letters', 1);

/**
 * The site has no blog, so RSS feeds (/feed/, /comments/feed/, ...) are empty
 * pages Google keeps crawling; send them to the home page.
 */
function wholesale_seo_redirect_feeds()
{
	if (is_feed()) {
		wp_safe_redirect(home_url('/'), 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_feeds', 1);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

/**
 * The reviews page used to filter by ?product=, which is also WordPress's
 * product query var, so /reviews/?product=115 answers 404. The filter is
 * ?review_product= now; send links Google still has to the working URL.
 */
function wholesale_seo_redirect_old_review_filter()
{
	if (!is_404() || !isset($_GET['product']) || !is_scalar($_GET['product']) || !absint($_GET['product'])) {
		return;
	}

	$path = untrailingslashit((string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH));
	if ($path !== untrailingslashit((string) wp_parse_url(wholesale_reviews_page_url(), PHP_URL_PATH))) {
		return;
	}

	wp_safe_redirect(wholesale_reviews_page_url(array('review_product' => absint($_GET['product']))), 301);
	exit;
}
add_action('template_redirect', 'wholesale_seo_redirect_old_review_filter', 1);

function wholesale_seo_sitemap_taxonomy_args($args, $taxonomy)
{
	if ('product_category' !== $taxonomy) {
		return $args;
	}

	$excluded = array();
	foreach (wholesale_seo_sitemap_excluded_categories() as $slug) {
		$term = get_term_by('slug', $slug, 'product_category');
		if ($term && !is_wp_error($term)) {
			$excluded[] = (int) $term->term_id;
		}
	}

	// Keep hub categories whose products sit in subcategories; drop the
	// truly empty ones instead.
	$args['hide_empty'] = false;
	foreach (get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false)) as $term) {
		if (!wholesale_seo_category_has_products($term)) {
			$excluded[] = (int) $term->term_id;
		}
	}

	if ($excluded) {
		$args['exclude'] = array_merge(isset($args['exclude']) ? (array) $args['exclude'] : array(), $excluded);
	}

	return $args;
}
add_filter('wp_sitemaps_taxonomies_query_args', 'wholesale_seo_sitemap_taxonomy_args', 10, 2);

/**
 * Menu links that point at the wrong or a retired URL, keyed by path.
 *
 * @return array Path without trailing slash => site path to use instead.
 */
function wholesale_seo_link_fixes()
{
	return array(
		// The old slug now redirects to Tension Fabric, a different product.
		'/product/fabric-banner-9oz-wrinkle-free' => '/product/fabric-banner-9oz-wrinkle-free-copy/',
		// The channel letter route redirects to the channel letters landing page.
		'/channel-letters' => wholesale_channel_letters_landing_id()
			? '/' . get_page_uri(wholesale_channel_letters_landing_id()) . '/'
			: '/',
	);
}

/**
 * Normalize an internal link to its final URL: absolute, on this site's base
 * path, clean category routes, and a trailing slash, so a click never costs a
 * redirect. External, anchor, tel:, mailto: and sms: links are returned as-is.
 */
function wholesale_seo_normalize_internal_url($url)
{
	$url = trim((string) $url);
	if ('' === $url || '#' === $url[0] || preg_match('/^(?:tel|mailto|sms|javascript):/i', $url)) {
		return $url;
	}

	$parts = wp_parse_url($url);
	if (false === $parts) {
		return $url;
	}

	$home = wp_parse_url(home_url('/'));
	if (!empty($parts['host']) && strcasecmp($parts['host'], $home['host']) !== 0) {
		return $url;
	}

	$path = isset($parts['path']) ? $parts['path'] : '/';
	$home_path = isset($home['path']) ? rtrim($home['path'], '/') : '';
	if ($home_path && 0 === strpos($path, $home_path . '/')) {
		$path = substr($path, strlen($home_path));
	}
	$path = '/' . ltrim($path, '/');

	if (!empty($parts['query'])) {
		parse_str($parts['query'], $query);
		if (!empty($query['category_slug']) && 1 === count($query) && '/' === $path) {
			$term = get_term_by('slug', sanitize_title($query['category_slug']), 'product_category');
			if ($term && !is_wp_error($term)) {
				$path = '/' . $term->slug;
				unset($parts['query']);
			}
		}
	}

	$fixes = wholesale_seo_link_fixes();
	$key = untrailingslashit($path);
	if (isset($fixes[$key])) {
		$path = $fixes[$key];
	} elseif (!pathinfo($path, PATHINFO_EXTENSION)) {
		$path = trailingslashit($path);
	}

	$normalized = home_url($path);
	if (!empty($parts['query'])) {
		$normalized .= '?' . $parts['query'];
	}
	if (!empty($parts['fragment'])) {
		$normalized .= '#' . $parts['fragment'];
	}

	return $normalized;
}

function wholesale_seo_normalize_menu_urls($items)
{
	foreach ($items as $item) {
		if (!empty($item->url)) {
			$item->url = wholesale_seo_normalize_internal_url($item->url);
		}
	}

	return $items;
}
add_filter('wp_nav_menu_objects', 'wholesale_seo_normalize_menu_urls', 20);

/**
 * Rewrite this site's http:// URLs to https:// in a page served over HTTPS.
 *
 * Product gallery meta and some stored content still hold http:// image URLs
 * (the site address was http when they were saved), which browsers flag as
 * mixed content. Setting both site addresses to https in Settings > General
 * and running a search-replace fixes the data; this keeps pages clean either way.
 */
function wholesale_seo_force_https_urls($html)
{
	$host = wp_parse_url(home_url('/'), PHP_URL_HOST);
	if (!$host) {
		return $html;
	}

	return str_replace(
		array('http://' . $host, 'http:\/\/' . $host, 'http://www.' . $host, 'http:\/\/www.' . $host),
		array('https://' . $host, 'https:\/\/' . $host, 'https://www.' . $host, 'https:\/\/www.' . $host),
		$html
	);
}

function wholesale_seo_start_https_buffer()
{
	if (!is_ssl() || is_admin() || wp_doing_ajax() || is_feed()) {
		return;
	}

	ob_start('wholesale_seo_force_https_urls');
}
add_action('template_redirect', 'wholesale_seo_start_https_buffer', 0);

/**
 * One-time database updates that travel with the theme. Each step runs once,
 * for an administrator, and is recorded so it never repeats.
 */
function wholesale_seo_migrations()
{
	return array(
		'2026-10-remove-spam-categories' => 'wholesale_seo_migrate_remove_spam_categories',
		'2026-10-storefront-signs-page' => 'wholesale_seo_migrate_storefront_signs_page',
		'2026-10-image-alt-text' => 'wholesale_seo_migrate_image_alt_text',
		'2026-10-product-copy-titles' => 'wholesale_seo_migrate_product_copy_titles',
		'2026-10-policy-drafts' => 'wholesale_seo_migrate_policy_drafts',
		'2026-10-remove-supplier-references' => 'wholesale_seo_migrate_remove_supplier_references',
		'2026-10-banners-displays-page' => 'wholesale_seo_migrate_banners_displays_page',
		'2026-10-coroplast-description' => 'wholesale_seo_migrate_coroplast_description',
		'2026-10-channel-letter-cost-page' => 'wholesale_seo_migrate_channel_letter_cost_page',
		'2026-10-design-templates-page' => 'wholesale_seo_migrate_design_templates_page',
		'2026-10-storefront-media' => 'wholesale_seo_migrate_storefront_media',
		'2026-10-supplier-text' => 'wholesale_seo_migrate_supplier_text',
		'2026-10-site-title' => 'wholesale_seo_migrate_site_title',
		'2026-10-brand-chat-alt' => 'wholesale_seo_migrate_brand_chat_alt',
		'2026-10-product-typos' => 'wholesale_seo_migrate_product_typos',
		'2026-10-short-descriptions' => 'wholesale_seo_migrate_short_descriptions',
		'2026-10-13oz-banner-description' => 'wholesale_seo_migrate_13oz_banner_description',
		'2026-10-window-graphics-page' => 'wholesale_seo_migrate_window_graphics_page',
		'2026-10-old-dev-gallery-urls' => 'wholesale_seo_migrate_old_dev_gallery_urls',
		'2026-10-seo-phase-copy-overrides' => 'wholesale_seo_migrate_phase_copy_overrides',
		// Not SEO, but it rides the same once-per-site runner (functions.php).
		'2026-10-places-key-cleanup' => 'wholesale_migrate_places_key_cleanup',
		'2026-10-terms-business-identity' => 'wholesale_seo_migrate_terms_business_identity',
	);
}

/**
 * Terms & Conditions (Merchant Center misrepresentation fix, 2026-10-27): name
 * the business behind the site and state the remedy for defective orders
 * (free reprint, or a full refund of the item when it can't be reprinted), the
 * same as page-shipping-returns.php. Only text still as originally written is
 * replaced.
 */
function wholesale_seo_migrate_terms_business_identity()
{
	$page = get_page_by_path('terms-conditions');
	if (!$page) {
		return true;
	}

	$content = str_replace(
		array(
			'Welcome to StorefrontSignOnline.com (SSO for short).',
			'There will be no refunds of any kind after a job has been approved.',
			'In some cases, customer will be required to ship defective product(s) back to SSO at their own expense and in an agreed upon reasonable time frame.',
			'SSO may reimburse the customer for shipping if a defect is determined to exist.',
			'unless FedEx delivers a defective product or fails to deliver an order.',
		),
		array(
			'Welcome to StorefrontSignOnline.com (SSO for short), operated by Storefront Sign Online LLC, also doing business as Lit Sign Manufacturing, 707 S. Grady Way, Suite 600, Renton, WA 98057. Phone 866-436-2101, email TR@StorefrontSignOnline.com.',
			'An approved job cannot be cancelled for a refund. This does not limit the reprint or refund described below when an order arrives defective or incorrect.',
			'If an order is defective, damaged in transit, or different from the proof the customer approved, SSO will reprint or replace it at no charge. If SSO cannot reprint it, SSO will refund the full price paid for that item. In some cases, customer will be required to ship defective product(s) back to SSO in an agreed upon reasonable time frame.',
			'SSO reimburses the customer for that shipping once a defect is confirmed.',
			'unless the carrier delivers a damaged order or fails to deliver it.',
		),
		$page->post_content
	);

	if ($content !== $page->post_content) {
		wp_update_post(array(
			'ID' => $page->ID,
			'post_content' => wp_slash($content),
		));
	}

	return true;
}

/**
 * Category and product titles and descriptions saved on the SEO screen
 * (inc/seo-admin/) were copies of the theme's defaults at the time, so they
 * hid the rewritten ones in wholesale_seo_category_meta() and
 * data/product-pages/ (Oct 2026 SEO pass). Clear only the values still
 * exactly as they were saved; anything edited since is kept.
 */
function wholesale_seo_migrate_phase_copy_overrides()
{
	$saved = array(
		'term' => array(
			'large-format' => array(
				'title' => 'Large Format Printing | Adhesive Vinyl, Posters & Film',
				'description' => 'Large format prints: adhesive vinyl, window perf and clings, floor graphics, vehicle wrap vinyl, backlit film, posters, canvas and acrylic prints.',
			),
			'adhesive-products' => array(
				'title' => 'Printed Adhesive Vinyl | Window, Wall & Floor Graphics',
				'description' => 'Custom printed adhesive vinyl: standard and high performance vinyl, window perf, clear and frosted film, clings, floor graphics and vehicle wrap.',
			),
			'wall-art' => array(
				'title' => 'Custom Wall Art | Canvas, Acrylic & Framed Prints',
				'description' => 'Turn photos and artwork into wall art: canvas wraps and rolls, framed canvas, acrylic prints, framed posters and prints, and magnetic wood hangers.',
			),
			'indoor-outdoor-displays' => array(
				'title' => 'Indoor & Outdoor Displays | Flags, Stands & Booths',
				'description' => 'Event and retail displays: advertising flags, banner stands, A-frames, SEG and trade show displays, event tents, table throws and real estate signs.',
			),
			'advertising-flags' => array(
				'title' => 'Custom Feather Flags & Advertising Flags | Printed Flags',
				'description' => 'Custom printed feather, teardrop, rectangle and pole flags for storefronts and events. Order the flag only or with a pole set and see your price online.',
			),
			'banner-stands' => array(
				'title' => 'Retractable Banner Stands | Custom Printed Banner Stands',
				'description' => 'Retractable banner stands, X-stands, tension fabric and table top stands, and step and repeat backdrops in full color. Graphic only or with hardware.',
			),
			'banners' => array(
				'title' => 'Custom Vinyl Banners | 13oz, 18oz, Mesh & Fabric Banners',
				'description' => 'Custom printed banners: 13oz and 18oz blockout vinyl, mesh, backlit, indoor and wrinkle-free fabric banners, plus pole banner sets. Priced per square foot.',
			),
			'real-estate-products' => array(
				'title' => 'Real Estate Signs | Yard Signs, Frames & Post Signs',
				'description' => 'Custom real estate signs: yard signs with H-stakes, real estate frames, post signs and A-frames. Reusable outdoor signage printed in full color.',
			),
			'a-frame-and-sign-holders' => array(
				'title' => 'A-Frame Signs & Sign Holders | Poster Stands & Hangers',
				'description' => 'Banner A-frames, poster stands, snap poster hangers and magnetic wood frame hangers with custom prints. Display systems for banners, signs and posters.',
			),
			'signicade-a-frames' => array(
				'title' => 'Signicade A-Frame Signs | Custom Sidewalk Signs',
				'description' => 'Plasticade Signicade, Deluxe Signicade and Simposign II sidewalk A-frames with full color UV printed graphics. Order the graphic only or with the frame.',
			),
			'seg-products' => array(
				'title' => 'SEG Fabric Displays | Silicone Edge Graphic Frames',
				'description' => 'SEG fabric displays and backlit SEG popups from 3ft to 20ft. Silicone edged fabric graphics slot into aluminum frames. Order graphic only or with frame.',
			),
			'trade-show-products' => array(
				'title' => 'Trade Show Displays | Fabric Pop Up & Tension Displays',
				'description' => 'Straight and curved tension fabric displays and Velcro fabric pop up displays for trade show booths and backdrops. Printed graphics, with or without frame.',
			),
			'custom-event-tents' => array(
				'title' => 'Custom Event Tents | Printed 10x10 Canopy Tents & Walls',
				'description' => 'Full color 10x10 custom event tents with heavy duty aluminum hex frames, plus printed full and half tent walls and tent flags for outdoor events.',
			),
			'table-throws' => array(
				'title' => 'Custom Table Throws | Printed Table Covers & Runners',
				'description' => 'Full color dye sublimated table covers for 4ft, 6ft and 8ft and round tables, plus stretch covers, solid color throws and table runners for events.',
			),
			'hardware-only' => array(
				'title' => 'Banner Stand & Display Hardware | Replacement Stands',
				'description' => 'Hardware only for retractable banner stands, X-stands, tension fabric stands and step and repeat backdrops. Graphics are not included.',
			),
			'rigid-signs-and-magnets' => array(
				'title' => 'Rigid Signs & Magnets | Aluminum, Coroplast & PVC Signs',
				'description' => 'Custom printed rigid signs: aluminum, Coroplast, foam board, GatorFoam, PVC, styrene and aluminum sandwich boards, plus custom magnets.',
			),
			'reflective-products' => array(
				'title' => 'Reflective Signs | Reflective Vinyl, Aluminum & Magnets',
				'description' => 'Reflective signs that stand out at night: reflective aluminum signs, Coroplast, PVC, sandwich boards, A-frames, adhesive vinyl and car magnets.',
			),
			'dry-erase-products' => array(
				'title' => 'Dry Erase Signs | Printed Whiteboard Signs & Vinyl',
				'description' => 'Printed dry erase signs: dry erase adhesive vinyl, Coroplast, foamcore, PVC, magnets, aluminum sandwich boards and Signicade A-frames.',
			),
			'banner-stand-hardware' => array(
				'title' => 'Banner Stand Hardware | Retractable & Fabric Stands',
				'description' => 'Replacement hardware for standard retractable banner stands and tension fabric stands. Graphic not included.',
			),
			'flag-hardware' => array(
				'title' => 'Flag Poles & Bases | Feather Flag Hardware',
				'description' => 'Feather and rectangle flag pole sets, cross and square bases, ground stakes, spikes, water bags and carrying bags. Graphic not included.',
			),
			'event-tent-hardware-only' => array(
				'title' => 'Event Tent Frames & Hardware | Tent Accessories',
				'description' => 'Event tent frames, half wall hardware, flag holders, sandbag sets and wheeled carrying bags for custom event tents. Graphics not included.',
			),
		),
		'post' => array(
			'acrylic-prints' => array(
				'description' => 'Custom acrylic prints: full color graphics back-mounted on 1/4" acrylic sheet for a glossy gallery look. Choose 16x24, 20x30 or 24x36 inches.',
			),
			'adhesive-window-perf' => array(
				'description' => 'Perforated window vinyl (70/30) shows your graphic outside while you still see out from inside. Black adhesive backing for one-way vision.',
			),
			'canvas-wrap' => array(
				'description' => 'Canvas wrap prints: your photo or artwork printed on photo quality artist canvas and stretched, so it arrives ready to hang. Priced online.',
			),
			'mesh-banner' => array(
				'description' => 'Custom mesh banners: 10oz vinyl with 70/30 perforation lets wind pass through on fences and buildings. Full color UV printed, indoor or outdoor.',
			),
			'tension-fabric' => array(
				'description' => 'Tension fabric banners: 8.8oz 2-way stretch polyester, dye sublimated for vivid color, for trade show displays and fabric wraps. Priced online.',
			),
			'round-table-cover' => array(
				'description' => 'Custom round table covers for bar and standard height tables, full color dye sublimated on 8.8oz tension fabric. See your price online.',
			),
			'tent-flag' => array(
				'description' => 'Tent flag: add a printed flag to your custom event tent. A clamp connector attaches it to the hex frame leg, so no base is needed.',
			),
			'custom-seg-fabric' => array(
				'description' => 'Custom SEG fabric graphics up to 40\' x 10\': 6oz UV printed polyester with a 3mm x 11mm silicone edge that fits SEG frames. Priced online.',
			),
			'spike-econo-flag' => array(
				'description' => 'Econo flag ground spike: powder coated steel, 28.5" tall, for setting an Econo flag in grass, sand or soil. Fits the Econo pole set. Spike only.',
			),
		),
	);

	foreach ($saved as $type => $objects) {
		foreach ($objects as $slug => $fields) {
			$object = 'term' === $type ? get_term_by('slug', $slug, 'product_category') : get_page_by_path($slug, OBJECT, 'product');
			if (!$object) {
				continue;
			}
			$id = 'term' === $type ? (int) $object->term_id : (int) $object->ID;
			$clear = array();
			foreach ($fields as $key => $value) {
				$stored = 'term' === $type ? get_term_meta($id, '_wholesale_seo_' . $key, true) : get_post_meta($id, '_wholesale_seo_' . $key, true);
				if ('' !== (string) $stored && $value === html_entity_decode(trim((string) $stored), ENT_QUOTES, 'UTF-8')) {
					$clear[$key] = '';
				}
			}
			if (!$clear) {
				continue;
			}
			if (function_exists('wholesale_seo_save_object_values')) {
				wholesale_seo_save_object_values($type, $id, $clear);
			} else {
				foreach (array_keys($clear) as $key) {
					'term' === $type ? delete_term_meta($id, '_wholesale_seo_' . $key) : delete_post_meta($id, '_wholesale_seo_' . $key);
				}
			}
		}
	}

	return true;
}

/**
 * Product galleries saved on the old dev copy kept its URLs
 * (http://localhost/wholesale/wp-content/uploads/...), which the site
 * migration's search-replace never matched: the Acrylic Prints photos were
 * broken on live. Point them at this site's uploads when the file is there.
 */
function wholesale_seo_migrate_old_dev_gallery_urls()
{
	global $wpdb;

	$uploads = wp_get_upload_dir();
	$ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_product_gallery' AND meta_value LIKE '%localhost/wholesale/wp-content/uploads/%'");
	foreach ($ids as $id) {
		$gallery = get_post_meta($id, '_product_gallery', true);
		if (!is_array($gallery)) {
			continue;
		}
		$fixed = array();
		foreach ($gallery as $key => $url) {
			if (is_string($url) && preg_match('#^https?://localhost/wholesale/wp-content/uploads/(.+)$#', $url, $match)
				&& file_exists(trailingslashit($uploads['basedir']) . $match[1])) {
				$url = trailingslashit($uploads['baseurl']) . $match[1];
			}
			$fixed[$key] = $url;
		}
		if ($fixed !== $gallery) {
			update_post_meta($id, '_product_gallery', $fixed);
		}
	}

	return true;
}

/**
 * The 13oz banner's description and spec were the 18oz blockout banner's
 * supplier copy ("Our 18oz. blockout matte vinyl banner material...", "UV
 * Printed 18 oz. Matte Vinyl Banner"), and its FAQ listed grommets as free,
 * which they are not on 13oz banners. The Merchant Center feed sends this
 * description to Google Shopping. Only those sentences change.
 */
function wholesale_seo_migrate_13oz_banner_description()
{
	$product = get_page_by_path('13oz-vinyl-banner', OBJECT, 'product');
	if (!$product) {
		return true;
	}

	$content = str_replace(
		array(
			'Our 18oz. blockout matte vinyl banner material is our premium heavy weight vinyl, it is often used for street banners and light pole banners. This material has pvc vinyl faces front and back with a blockout layer in between, scrim fibers reinforce this material so it will hold up in the toughest conditions.',
			'<li>UV Printed 18 oz. Matte Vinyl Banner</li>',
			'<li>Grommet (free)</li>',
		),
		array(
			'Our 13oz. matte scrim vinyl banner is the everyday choice for grand openings, sales, events and storefront promotions. Scrim fibers inside the vinyl make it strong and tear resistant, it works indoors and outdoors, and the matte finish keeps glare down so your message is easy to read. When both sides of a banner will be seen, choose our <a href="' . esc_url(home_url('/product/18oz-blockout-banner/')) . '">18oz. blockout banner</a>, which stops light showing through.',
			'<li>UV Printed 13 oz. Matte Scrim Vinyl Banner</li>',
			'<li>Grommets</li>',
		),
		$product->post_content
	);

	if ($content !== $product->post_content) {
		wp_update_post(array(
			'ID' => $product->ID,
			'post_content' => wp_slash($content),
		));
	}

	return true;
}

/**
 * Meta descriptions for the pages whose generated snippet was under 110
 * characters. Saved as the SEO screen's description, and only where that field
 * is still empty, so the owner can edit or clear them there.
 */
function wholesale_seo_migrate_short_descriptions()
{
	if (!function_exists('wholesale_seo_save_object_values')) {
		return false;
	}

	$products = array(
		'acrylic-prints' => 'Custom acrylic prints: full color graphics back-mounted on 1/4" acrylic sheet for a glossy gallery look. Choose 16x24, 20x30 or 24x36 inches.',
		'adhesive-window-perf' => 'Perforated window vinyl (70/30) shows your graphic outside while you still see out from inside. Black adhesive backing for one-way vision.',
		'canvas-wrap' => 'Canvas wrap prints: your photo or artwork printed on photo quality artist canvas and stretched, so it arrives ready to hang. Priced online.',
		'custom-seg-fabric' => 'Custom SEG fabric graphics up to 40\' x 10\': 6oz UV printed polyester with a 3mm x 11mm silicone edge that fits SEG frames. Priced online.',
		'mesh-banner' => 'Custom mesh banners: 10oz vinyl with 70/30 perforation lets wind pass through on fences and buildings. Full color UV printed, indoor or outdoor.',
		'round-table-cover' => 'Custom round table covers for bar and standard height tables, full color dye sublimated on 8.8oz tension fabric. See your price online.',
		'spike-econo-flag' => 'Econo flag ground spike: powder coated steel, 28.5" tall, for setting an Econo flag in grass, sand or soil. Fits the Econo pole set. Spike only.',
		'tension-fabric' => 'Tension fabric banners: 8.8oz 2-way stretch polyester, dye sublimated for vivid color, for trade show displays and fabric wraps. Priced online.',
		'tent-flag' => 'Tent flag: add a printed flag to your Storefront Sign Online event tent. A clamp connector attaches it to the hex frame leg, so no base is needed.',
	);
	foreach ($products as $slug => $description) {
		$product = get_page_by_path($slug, OBJECT, 'product');
		if ($product && '' === (string) get_post_meta($product->ID, '_wholesale_seo_description', true)) {
			wholesale_seo_save_object_values('post', $product->ID, array('description' => $description));
		}
	}

	$term = get_term_by('slug', 'banner-stand-hardware', 'product_category');
	if ($term && !is_wp_error($term) && '' === (string) get_term_meta($term->term_id, '_wholesale_seo_description', true)) {
		wholesale_seo_save_object_values('term', $term->term_id, array('description' => 'Replacement hardware for retractable banner stands and tension fabric stands: bases, poles and frames that fit our graphics. Graphic not included.'));
	}

	return true;
}

/**
 * Spelling mistakes from the supplier copy that show in product pages and in
 * Google snippets ("Permanant adhesive backing").
 */
function wholesale_seo_migrate_product_typos()
{
	global $wpdb;

	$fixes = array(
		'Permanant' => 'Permanent',
		'permanant' => 'permanent',
		'stablility' => 'stability',
		'stablity' => 'stability',
	);
	$fix = static function ($text) use ($fixes) {
		return str_replace(array_keys($fixes), array_values($fixes), $text);
	};

	$posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'product' AND (post_content LIKE '%ermanant%' OR post_content LIKE '%stabli%ity%' OR post_content LIKE '%stablity%')");
	foreach ($posts as $post) {
		$clean = $fix($post->post_content);
		if ($clean !== $post->post_content) {
			$wpdb->update($wpdb->posts, array('post_content' => $clean), array('ID' => $post->ID));
			clean_post_cache($post->ID);
		}
	}

	$metas = $wpdb->get_results("SELECT pm.meta_id, pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND pm.meta_key IN ('_product_short_desc', '_product_list_desc', '_product_description', '_product_component', '_product_faq', '_seo_description') AND (pm.meta_value LIKE '%ermanant%' OR pm.meta_value LIKE '%stablity%' OR pm.meta_value LIKE '%stablility%')");
	foreach ($metas as $meta) {
		if (is_serialized($meta->meta_value)) {
			continue;
		}
		$clean = $fix($meta->meta_value);
		if ($clean !== $meta->meta_value) {
			$wpdb->update($wpdb->postmeta, array('meta_value' => $clean), array('meta_id' => $meta->meta_id));
			wp_cache_delete($meta->post_id, 'post_meta');
		}
	}

	return true;
}

/**
 * The site title was "Store Front Sign Online"; the brand is "Storefront Sign
 * Online" (ads, schema, footer). Only that exact old value is changed, so a
 * title the owner set by hand is left alone.
 */
function wholesale_seo_migrate_site_title()
{
	if ('Store Front Sign Online' === get_option('blogname')) {
		update_option('blogname', 'Storefront Sign Online');
	}

	return true;
}

/**
 * The live chat widget's business name and two logo alt texts still said
 * "Store Front Sign Online", which printed on every page.
 */
function wholesale_seo_migrate_brand_chat_alt()
{
	global $wpdb;

	$chat = get_option('sfs_chat_settings');
	if (is_array($chat) && isset($chat['business_name']) && 'Store Front Sign Online' === $chat['business_name']) {
		$chat['business_name'] = 'Storefront Sign Online';
		update_option('sfs_chat_settings', $chat);
	}

	$ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_image_alt' AND meta_value LIKE '%Store Front Sign Online%'");
	foreach ($ids as $id) {
		$alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
		update_post_meta($id, '_wp_attachment_image_alt', str_replace('Store Front Sign Online', 'Storefront Sign Online', $alt));
	}

	return true;
}

function wholesale_seo_run_migrations()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$done = get_option('wholesale_seo_migrations_done', array());
	$done = is_array($done) ? $done : array();

	foreach (wholesale_seo_migrations() as $key => $callback) {
		if (in_array($key, $done, true) || !is_callable($callback)) {
			continue;
		}

		if (false !== call_user_func($callback)) {
			$done[] = $key;
			update_option('wholesale_seo_migrations_done', $done, false);
		}
	}
}
add_action('admin_init', 'wholesale_seo_run_migrations');

/**
 * Delete the empty post categories the spam injection created.
 */
function wholesale_seo_migrate_remove_spam_categories()
{
	$slugs = array('bez-rubriki', 'pinup', 'pu', 'bh-top', 'bt', 'btprod', 'casinom-hub', 'gatesofolympus-link', 'marsbet', 'pb-top', 'sahabet');

	foreach ($slugs as $slug) {
		$term = get_term_by('slug', $slug, 'category');
		if ($term && !is_wp_error($term) && 0 === (int) $term->count) {
			wp_delete_term($term->term_id, 'category');
		}
	}

	return true;
}

/**
 * Create the Storefront Signs guide page. Its content comes from
 * page-storefront-signs.php, so the page itself stays empty.
 */
function wholesale_seo_migrate_storefront_signs_page()
{
	if (get_page_by_path('storefront-signs', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Storefront Signs',
		'post_name' => 'storefront-signs',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Create the Banners & Displays landing page. Its content comes from
 * page-banners-displays.php, so the page itself stays empty.
 */
function wholesale_seo_migrate_banners_displays_page()
{
	if (get_page_by_path('banners-displays', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Banners & Displays',
		'post_name' => 'banners-displays',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Fill empty alt text on media library images from the product each image
 * belongs to. Existing alt text is never changed.
 */
function wholesale_seo_migrate_image_alt_text()
{
	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
	));

	foreach ($attachments as $attachment_id) {
		if ('' !== trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true))) {
			continue;
		}

		$alt = wholesale_seo_attachment_alt($attachment_id);
		if ('' !== $alt) {
			update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt));
		}
	}

	return true;
}

/**
 * Two products were published with " Copy" left in their names from being
 * duplicated. Only the displayed name changes; the URLs stay the same.
 */
function wholesale_seo_migrate_product_copy_titles()
{
	$titles = array(
		'fabric-banner-9oz-wrinkle-free-copy' => 'Fabric Banner (9oz. Wrinkle Free)',
		'sd-retractable-sd-retractable-insert-only' => 'SD Retractable',
	);

	foreach ($titles as $slug => $title) {
		$product = get_page_by_path($slug, OBJECT, 'product');
		if ($product && preg_match('/\sCopy$/', $product->post_title)) {
			wp_update_post(array('ID' => $product->ID, 'post_title' => $title));
		}
	}

	return true;
}

/**
 * Save draft Privacy Policy and Shipping & Returns text for the owner to
 * review and publish. Never publishes anything, and only replaces the
 * privacy page's content while it still holds WordPress's sample text.
 */
function wholesale_seo_migrate_policy_drafts()
{
	require_once get_template_directory() . '/inc/policy-drafts.php';

	$privacy_id = (int) get_option('wp_page_for_privacy_policy');
	$privacy = $privacy_id ? get_post($privacy_id) : null;
	if ($privacy && 'publish' !== $privacy->post_status && false !== strpos($privacy->post_content, 'privacy-policy-tutorial')) {
		wp_update_post(array(
			'ID' => $privacy_id,
			'post_content' => wholesale_policy_privacy_draft(),
		));
	}

	if (!get_page_by_path('shipping-returns', OBJECT, 'page')) {
		wp_insert_post(array(
			'post_type' => 'page',
			'post_status' => 'draft',
			'post_title' => 'Shipping & Returns',
			'post_name' => 'shipping-returns',
			'post_content' => str_replace('href="/terms-conditions/"', 'href="' . esc_url(home_url('/terms-conditions/')) . '"', wholesale_policy_shipping_draft()),
			'comment_status' => 'closed',
			'ping_status' => 'closed',
		));
	}

	return true;
}

/**
 * The Coroplast product was saved with the Magnets product's copy (description,
 * specs, FAQ, bullets and card text all describe a 30 mil magnet). Replace it
 * with copy built from the product's own options: 4mm white Coroplast, UV
 * printed matte, one or two sides, free grommets, optional H-stake. Only runs
 * while the magnet text is still there, so owner edits are never overwritten.
 */
function wholesale_seo_migrate_coroplast_description()
{
	$product = get_page_by_path('coroplast', OBJECT, 'product');
	if (!$product || false === strpos($product->post_content, 'Our magnet sheet')) {
		return true;
	}

	$intro = strstr($product->post_content, '<h4 id="description">', true);
	$body = '<h4 id="description">Description</h4>
Custom Coroplast signs are printed on 4mm white corrugated plastic: lightweight, waterproof and rigid enough to stand up outdoors. They are the go-to material for yard signs, real estate and open house signs, political and event signs, construction site and directional signs, and temporary storefront promotions. Graphics are UV printed for a long lasting matte finish.

Choose a standard size or enter your own, print one or both sides, and add an H-stake to put your sign straight into the ground.

<hr />

<h4 id="spec">Spec</h4>
<strong>Material:</strong>
<ul>
 	<li>4mm White Coroplast (corrugated plastic)</li>
</ul>
<strong>Print</strong>
<ul>
 	<li>UV Ink - Matte Finish</li>
 	<li>1 side or 2 sides</li>
</ul>
<strong>Product Attributes:</strong>
<ul>
 	<li>Standard sizes from 18" x 12" to 24" x 36", or custom sizes up to 4 ft x 8 ft</li>
 	<li>Grommets in all four corners or the top two corners, free of charge</li>
 	<li>Optional H-stake for yard and lawn installs</li>
 	<li>Indoor or outdoor; waterproof</li>
</ul>
<strong>See Also:</strong>
<ul>
 	<li><a href="/product/reflective-coroplast-sign-hstake/">Reflective Coroplast</a></li>
 	<li><a href="/product/dry-erase-coroplast-sign-hstake/">Dry Erase Coroplast</a></li>
 	<li><a href="/product/yard-sign-and-h-stake/">Yard Sign and H-Stake</a></li>
</ul>

<hr />

<h4 id="file-setup">File Setup</h4>
<ul>
 	<li>Accepted File Formats: JPEG or PDF (single page only)</li>
 	<li>Color Space: CMYK</li>
 	<li>Resolution: 150dpi for raster images (More than enough for large format)</li>
 	<li>Max File Upload Size: 300MB</li>
 	<li>Submit artwork built to ordered size - Scaled artwork is automatically detected and fit to order</li>
 	<li>Do not include crop marks or bleeds</li>
</ul>
<strong>Additional Tips</strong>
<ul>
 	<li>Do not submit with Pantones/Spot Colors - Convert to CMYK</li>
 	<li>Convert live fonts to outlines</li>
 	<li>Use provided design templates when available</li>
</ul>

<hr />

<h4 id="frequently-asked-questions">Frequently asked questions</h4>
<ul>
 	<li>Q: Can I print both sides of my Coroplast sign?</li>
 	<li>A: Yes. Choose 2 Sides when you order and both faces are printed.</li>
 	<li>Q: Do Coroplast signs come with stakes?</li>
 	<li>A: H-stakes are an optional add-on. Choose Yes for H-Stake when you order.</li>
 	<li>Q: Is there a charge for grommets?</li>
 	<li>A: No. Grommets in all four corners or the top two corners are free.</li>
</ul>';

	$body = str_replace('href="/product/', 'href="' . esc_url(home_url('/product/')), $body);

	wp_update_post(array(
		'ID' => $product->ID,
		'post_content' => (false !== $intro ? $intro : '') . $body,
	));

	update_post_meta($product->ID, '_product_short_desc', '<ul>
 	<li>4mm white Coroplast (corrugated plastic)</li>
 	<li>UV printed matte finish - indoor and outdoor ready</li>
 	<li>Print 1 or 2 sides, free grommets</li>
 	<li>Optional H-stake for yard signs</li>
</ul>');
	update_post_meta($product->ID, '_product_list_desc', '<ul>
 	<li>4mm corrugated plastic yard signs</li>
 	<li>Waterproof, optional H-stake</li>
</ul>');

	return true;
}

/**
 * Create the Channel Letter Cost guide page. Its content comes from
 * page-channel-letter-cost.php, so the page itself stays empty.
 */
function wholesale_seo_migrate_channel_letter_cost_page()
{
	if (get_page_by_path('channel-letter-cost', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Channel Letter Cost',
		'post_name' => 'channel-letter-cost',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Create the Design Templates page (artwork guide and every template
 * download). Its content comes from page-design-templates.php, so the page
 * itself stays empty.
 */
function wholesale_seo_migrate_design_templates_page()
{
	if (get_page_by_path('design-templates', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Design Templates',
		'post_name' => 'design-templates',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Create the Window Graphics & Vinyl landing page (Google Ads campaign
 * "SSO - Window Graphics & Vinyl - USA"). Its content comes from
 * page-window-graphics.php, so the page itself stays empty.
 */
function wholesale_seo_migrate_window_graphics_page()
{
	if (get_page_by_path('window-graphics', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Window Graphics & Vinyl',
		'post_name' => 'window-graphics',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Keep crawlers out of internal site search. Every sort and filter link on the
 * results page makes a new ?s= URL; they are all noindexed, but Google still
 * spent crawls on dozens of them, so block the crawl itself. Cart and checkout
 * are private and noindexed too.
 */
add_filter('robots_txt', function ($output, $public) {
	if (!$public) {
		return $output;
	}

	$path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
	$rules = array('/?s=', '/*?s=', '/*&s=', '/search/', '/cart/', '/checkout/');
	$lines = '';
	foreach ($rules as $rule) {
		$rule = rtrim($path, '/') . $rule;
		if (false === strpos($output, 'Disallow: ' . $rule . "\n")) {
			$lines .= 'Disallow: ' . $rule . "\n";
		}
	}

	// Add to the "User-agent: *" group core prints, ahead of the Sitemap lines.
	return $lines ? preg_replace('/(User-agent: \*\n(?:(?:Dis)?[Aa]llow: [^\n]*\n)*)/', '$1' . $lines, $output, 1) : $output;
}, 15, 2);
