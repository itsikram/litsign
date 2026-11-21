<?php
/**
 * Location sitemap at /location-sitemap.xml.
 *
 * Lists the /locations/ hub and every approved ('live') state and city guide
 * (inc/guide-pages.php) whose WordPress page is published, indexable and
 * canonical to itself. <lastmod> reflects significant content updates;
 * the hub takes the newest of its own revision and its guides. Guides
 * still in review never appear.
 * Submit it in Search Console next to /sitemap.xml.
 *
 * @package litsign
 */

function retails_location_sitemap_rewrite()
{
	add_rewrite_rule('^location-sitemap\.xml$', 'index.php?retails_location_sitemap=1', 'top');
}
add_action('init', 'retails_location_sitemap_rewrite', 20);

add_action('init', function () {
	if ('1' !== get_option('retails_location_sitemap_rewrite_version')) {
		flush_rewrite_rules(false);
		update_option('retails_location_sitemap_rewrite_version', '1');
	}
}, 99);

add_filter('query_vars', function ($vars) {
	$vars[] = 'retails_location_sitemap';
	return $vars;
});

/**
 * Whether a registry page may be listed: published, not noindexed or
 * excluded from sitemaps, and without a canonical pointing elsewhere.
 *
 * @param WP_Post|null $page
 */
function retails_location_sitemap_listable($page)
{
	if (!$page || 'publish' !== $page->post_status || '' !== $page->post_password) {
		return false;
	}

	static $excluded = null;
	if (null === $excluded) {
		$excluded = array_fill_keys(array_map('intval', array_merge(
			retails_seo_noindex_page_ids(),
			(array) apply_filters('retails_seo_sitemap_excluded_post_ids', array())
		)), true);
	}
	if (isset($excluded[$page->ID])) {
		return false;
	}

	// A canonical set on the page under SEO (inc/seo-admin/) that isn't the page itself.
	if (function_exists('retails_seo_stored_value')) {
		$canonical = retails_seo_stored_value('post', $page->ID, 'canonical');
		if ('' !== $canonical && $canonical !== get_permalink($page)) {
			return false;
		}
	}

	return true;
}

/**
 * The same published, indexable guides appear in the directory and sitemap.
 *
 * @return array Registry entries with the WordPress page, URL and lastmod.
 */
function retails_location_public_guides()
{
	$states = retails_guide_states();
	$entries = array();

	foreach (retails_guide_registry() as $path => $entry) {
		if (empty($entry['type']) || !in_array($entry['type'], array('state', 'city'), true) || 'live' !== $entry['status']) {
			continue;
		}

		$page = get_page_by_path($path);
		if (!retails_location_sitemap_listable($page)) {
			continue;
		}

		$state = $states[$entry['state']];
		$place = 'city' === $entry['type'] ? $state['cities'][$entry['city']] : $state;

		// Sort key: state name, then city name, so each state is followed by its cities A-Z.
		$sort = $state['name'] . "\0" . ('city' === $entry['type'] ? $state['cities'][$entry['city']]['name'] : '');
		$entries[] = $entry + array(
			'page' => $page,
			'loc' => get_permalink($page),
			'lastmod' => retails_location_modified($place),
			'sort' => $sort,
		);
	}

	usort($entries, static function ($a, $b) {
		return strcasecmp($a['sort'], $b['sort']);
	});
	return $entries;
}

function retails_location_directory_modified($guides)
{
	return $guides ? max(array_column($guides, 'lastmod')) : retails_location_modified();
}

/** @return array Sitemap URLs and dates, with the location hub first. */
function retails_location_sitemap_entries()
{
	$entries = retails_location_public_guides();
	$hub = get_page_by_path('locations');
	if (retails_guide_is_live('locations') && retails_location_sitemap_listable($hub)) {
		array_unshift($entries, array('loc' => get_permalink($hub), 'lastmod' => retails_location_directory_modified($entries)));
	}

	return $entries;
}

function retails_render_location_sitemap()
{
	if ('1' !== get_query_var('retails_location_sitemap')) {
		return;
	}

	$entries = retails_location_sitemap_entries();

	nocache_headers();
	header('Content-Type: application/xml; charset=UTF-8');
	header('X-Robots-Tag: noindex, follow');
	status_header(200);

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	foreach ($entries as $entry) {
		echo "\t<url>\n";
		echo "\t\t<loc>" . esc_xml(esc_url_raw($entry['loc'])) . "</loc>\n";
		if ($entry['lastmod']) {
			echo "\t\t<lastmod>" . esc_xml($entry['lastmod']) . "</lastmod>\n";
		}
		echo "\t</url>\n";
	}
	echo '</urlset>';
	exit;
}
add_action('template_redirect', 'retails_render_location_sitemap', 0);
