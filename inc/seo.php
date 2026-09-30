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
