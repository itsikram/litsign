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

	$known = array('article-jule-24000', 'hello-world', 'hello-world-2');
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
	return array('channel-letters');
}

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
		// The channel letter route canonicalizes to the home page.
		'/channel-letters' => '/',
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
	);
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
