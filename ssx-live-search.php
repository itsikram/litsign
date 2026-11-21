<?php

/**
 * Live (async) search endpoint used by the header search.
 *
 * Loaded from functions.php:
 *     require_once get_template_directory() . '/ssx-live-search.php';
 *
 * Endpoint: GET /wp-json/ssx/v1/search?q=banner&post_type=product&limit=6
 * Response: { "total": 12, "items": [ { id, title, url, image, type, price } ] }
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('rest_api_init', function () {
	register_rest_route('ssx/v1', '/search', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'ssx_live_search_callback',
		'permission_callback' => '__return_true', // Public, read-only: published content only.
		'args'                => array(
			'q'         => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'post_type' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			),
			'limit'     => array(
				'type'              => 'integer',
				'default'           => 6,
				'sanitize_callback' => 'absint',
			),
		),
	));
});

function ssx_live_search_callback(WP_REST_Request $request)
{
	$q     = trim(mb_substr((string) $request['q'], 0, 80));
	$limit = max(1, min(10, (int) $request['limit']));

	if (mb_strlen($q) < 2) {
		return new WP_REST_Response(array('total' => 0, 'items' => array()), 200);
	}

	// Only ever search public, searchable post types.
	$public = get_post_types(array('public' => true, 'exclude_from_search' => false));
	unset($public['attachment']);
	$requested  = (string) $request['post_type'];
	$post_types = ($requested !== '' && isset($public[$requested])) ? array($requested) : array_values($public);

	// Short server-side cache so repeated keystrokes / popular terms stay cheap.
	$cache_key = 'ssx_ls_' . md5(wp_json_encode(array($q, $post_types, $limit)));
	$data      = get_transient($cache_key);

	if (false === $data) {
		$query = new WP_Query(array(
			's'                   => $q,
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'has_password'        => false,
		));

		$items = array();
		foreach ($query->posts as $post) {
			$price = '';
			// Show the price when WooCommerce is active (plain text, no markup).
			if ('product' === $post->post_type && function_exists('wc_get_product')) {
				$product = wc_get_product($post->ID);
				if ($product) {
					$price = trim(wp_strip_all_tags(html_entity_decode($product->get_price_html(), ENT_QUOTES, 'UTF-8')));
				}
			}

			$type_object = get_post_type_object($post->post_type);

			$items[] = array(
				'id'    => (int) $post->ID,
				'title' => ssx_live_search_title($post),
				'url'   => get_permalink($post),
				// Card-hidden products only have sale-artwork photos (inc/product-photo-overrides.php).
				'image' => function_exists('retails_product_card_hidden') && retails_product_card_hidden($post) ? '' : (string) get_the_post_thumbnail_url($post, 'thumbnail'),
				'type'  => $type_object ? $type_object->labels->singular_name : '',
				'price' => $price,
			);
		}

		$data = array('total' => (int) $query->found_posts, 'items' => $items);
		set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);
	}

	$response = new WP_REST_Response($data, 200);
	$response->header('Cache-Control', 'public, max-age=60');
	return $response;
}

/* =====================================================================
 * INSTANT SEARCH INDEX (2026-11-20)
 *
 * The /search endpoint above takes 1-3 s per keystroke (a full WordPress
 * load plus a query), so the header box felt slow. Now the box downloads
 * every published product once, when the shopper first points at or taps
 * the search field, and matches in the browser on each keystroke with no
 * server round trip. /search stays as the fallback if this index fails.
 *
 * Endpoint: GET /wp-json/ssx/v1/search-index
 * Response: { v, home, up, items: [ [id, title, path, image, words], ... ] }
 *   path  = permalink without home_url('/'), image = thumbnail without the
 *   uploads base URL (both stay absolute when they don't start with it),
 *   words = the lowercase words of the title, excerpt and content.
 * Matching follows WordPress search (js/live-search.js): every searched
 * word must appear in the title, excerpt or content, so the count matches
 * the "See all results" page.
 * Items come in shop order (listed products first, as on /shop/).
 * ===================================================================== */

define('SSX_LS_INDEX_FORMAT', 3); // 3: grouped products show their own title (2026-11-20).

/**
 * A result's name: a grouped product's own title ("Real Estate A-Frame (Sign Only)",
 * inc/product-group-swap.php), so products of one group don't all read the same; else the
 * post title. Plain text.
 */
function ssx_live_search_title($post)
{
	$title = html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES, 'UTF-8');
	if ('product' === get_post_type($post) && function_exists('retails_product_group_title')) {
		$title = retails_product_group_title($post->ID, $title);
	}

	return $title;
}

/**
 * Exact copies of another published product with the same shown title, punctuation aside ("-old", "-2" and
 * "-frame-only" leftovers, inc/seo-content.php retails_seo_product_duplicate_of()), e.g.
 * two "X-Stand (Hardware Only)". They stay out of search so every result has its own
 * title; their pages and links keep working.
 *
 * @return int[]
 */
function ssx_live_search_same_title_copies()
{
	static $ids = null;
	if (null !== $ids) {
		return $ids;
	}
	$ids = array();
	if (!function_exists('retails_seo_product_duplicate_of')) {
		return $ids;
	}

	$products = get_posts(array(
		'post_type'            => 'product',
		'post_status'          => 'publish',
		'posts_per_page'       => -1,
		'no_found_rows'        => true,
		'retails_copy_check' => true,
	));
	// Same words, ignoring case and punctuation: "Tension Fabric Stand Hardware Only" = "... (Hardware Only)".
	$plain = static function ($post) {
		return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(ssx_live_search_title($post), 'UTF-8')));
	};
	foreach ($products as $post) {
		$original = retails_seo_product_duplicate_of($post->ID);
		if ($original && $plain(get_post($original)) === $plain($post)) {
			$ids[] = (int) $post->ID;
		}
	}

	return $ids;
}

/** The results page and the /search fallback leave those copies out too. */
add_action('pre_get_posts', static function ($query) {
	if (is_admin() || !$query->is_search() || $query->get('retails_copy_check')) {
		return;
	}
	$copies = ssx_live_search_same_title_copies();
	if ($copies) {
		$query->set('post__not_in', array_values(array_unique(array_merge(array_map('intval', (array) $query->get('post__not_in')), $copies))));
	}
});

add_action('rest_api_init', function () {
	register_rest_route('ssx/v1', '/search-index', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'ssx_live_search_index_callback',
		'permission_callback' => '__return_true', // Public, read-only: published products only.
	));
});

/**
 * Cheap fingerprint of the published products (one indexed query): it changes
 * when a product is added, removed or saved, or the index is flagged stale.
 */
function ssx_live_search_index_fingerprint()
{
	global $wpdb;
	$row = $wpdb->get_row($wpdb->prepare(
		"SELECT COUNT(*) AS n, MAX(post_modified_gmt) AS m FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
		'product'
	));

	return md5(implode('|', array(
		SSX_LS_INDEX_FORMAT,
		home_url('/'),
		$row ? $row->n . '@' . $row->m : '',
		(int) get_option('ssx_ls_index_ver', 0),
	)));
}

/**
 * The lowercase words of a product's title, excerpt and content, each once.
 * A word inside another kept word (or the title) is dropped, because the
 * browser matches substrings and would find it there anyway.
 */
function ssx_live_search_index_words($post)
{
	// The shown title; post_title words it lacks ("insert" in "SD Retractable - Insert Only") stay in the words.
	$title = mb_strtolower(ssx_live_search_title($post), 'UTF-8');
	$text  = $post->post_title . ' ' . $post->post_excerpt . ' ' . $post->post_content;
	// Photo descriptions count as words; other markup (class names such as "_midbanner_" in
	// pasted ad code) doesn't, so the box skips products that only match inside HTML.
	if (preg_match_all('/\salt\s*=\s*(["\'])(.*?)\1/is', $text, $alts)) {
		$text .= ' ' . implode(' ', $alts[2]);
	}
	$text  = preg_replace('/<[^>]*>/', ' ', $text); // Tags become spaces so words never run together.
	$text  = mb_strtolower(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'UTF-8');

	$text  = wp_check_invalid_utf8($text, true);

	$words = array();
	foreach (preg_split('/[\s\x{00A0}\x{2000}-\x{200B}\x{3000}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) as $word) {
		// Edge punctuation off (multibyte-safe: trim() works on bytes and would split UTF-8 characters).
		$word = (string) preg_replace('/^[.,;:!?()\[\]{}<>"\'*|`~\x{201C}\x{201D}\x{2018}\x{2019}]+|[.,;:!?()\[\]{}<>"\'*|`~\x{201C}\x{201D}\x{2018}\x{2019}]+$/u', '', $word);
		if ('' !== $word && mb_strlen($word, 'UTF-8') <= 40) {
			$words[$word] = mb_strlen($word, 'UTF-8');
		}
	}
	arsort($words); // Longest first, so shorter words inside them are dropped.

	$kept = ' ' . $title . ' ';
	$out  = array();
	foreach ($words as $word => $len) {
		$word = (string) $word;
		if (false === strpos($kept, $word)) {
			$out[]  = $word;
			$kept  .= $word . ' ';
		}
	}

	return implode(' ', $out);
}

/**
 * Every published product the search can find, in shop order.
 *
 * @return array[] Rows of array(id, title, path, image, words).
 */
function ssx_live_search_index_build()
{
	$query = new WP_Query(array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => -1,
		'has_password'        => false,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	));
	update_post_thumbnail_cache($query);

	$home     = home_url('/');
	$uploads  = wp_get_upload_dir();
	$up       = trailingslashit($uploads['baseurl']);
	$priority = function_exists('retails_shop_category_priority') ? array_flip(retails_shop_category_priority()) : array();
	$copies   = function_exists('retails_seo_product_copy_redirect_target');
	$hidden   = function_exists('retails_product_card_hidden');

	$rows = array();
	foreach ($query->posts as $n => $post) {
		// Hidden copies that redirect to their listed original stay out, as on the results page.
		if ($copies && preg_match('/-\d+$/', (string) $post->post_name) && retails_seo_product_copy_redirect_target($post->ID)) {
			continue;
		}
		// Copies with the same title as their original: one result per title.
		if (in_array((int) $post->ID, ssx_live_search_same_title_copies(), true)) {
			continue;
		}

		$url   = (string) get_permalink($post);
		$image = $hidden && retails_product_card_hidden($post) ? '' : (string) get_the_post_thumbnail_url($post, 'thumbnail');

		// Shop order: listed products by top category, then their order index, then title.
		// Unlisted products follow, newest first.
		$sort = '1' . sprintf('%05d', $n);
		if ('on' === get_post_meta($post->ID, '_show_in_list', true)) {
			$top   = null;
			$terms = get_the_terms($post->ID, 'product_category');
			foreach ($terms && !is_wp_error($terms) ? $terms : array() as $term) {
				if (!$top || $term->parent) {
					$top = $term;
				}
			}
			while ($top && $top->parent) {
				$parent = get_term($top->parent, 'product_category');
				$top    = $parent && !is_wp_error($parent) ? $parent : null;
			}
			$sort = sprintf('0%02d%04d%s', $top && isset($priority[$top->slug]) ? $priority[$top->slug] : 99, (int) get_post_meta($post->ID, '_order_by_index', true), sanitize_title($post->post_title));
		}

		$rows[$sort . '#' . $post->ID] = array(
			(int) $post->ID,
			ssx_live_search_title($post),
			0 === strpos($url, $home) ? substr($url, strlen($home)) : $url,
			'' !== $image && 0 === strpos($image, $up) ? substr($image, strlen($up)) : $image,
			ssx_live_search_index_words($post),
		);
	}
	ksort($rows, SORT_STRING);

	return array_values($rows);
}

/**
 * The index, cached until a product changes (6 h at most, which also covers
 * database pushes that fire no hooks).
 */
function ssx_live_search_index_data($fingerprint)
{
	$key  = 'ssx_ls_idx_' . substr($fingerprint, 0, 20);
	$data = get_transient($key);
	if (!is_array($data) || empty($data['items'])) {
		$uploads = wp_get_upload_dir();
		$data    = array(
			'v'     => substr($fingerprint, 0, 12),
			'home'  => home_url('/'),
			'up'    => trailingslashit($uploads['baseurl']),
			'items' => ssx_live_search_index_build(),
		);
		set_transient($key, $data, 6 * HOUR_IN_SECONDS);

		// Keep one copy (about 200 KB) instead of one per product save.
		$previous = (string) get_option('ssx_ls_index_key', '');
		if ('' !== $previous && $previous !== $key) {
			delete_transient($previous);
		}
		update_option('ssx_ls_index_key', $key, false);
	}
	return $data;
}

/** Rebuild in the background after a change, so the next shopper gets it from cache. */
add_action('ssx_live_search_index_warm', static function () {
	ssx_live_search_index_data(ssx_live_search_index_fingerprint());
});

function ssx_live_search_index_callback(WP_REST_Request $request)
{
	$fingerprint = ssx_live_search_index_fingerprint();
	$etag        = '"ssx-' . substr($fingerprint, 0, 16) . '"';
	// Browsers reuse the copy for 10 minutes and keep answering from it (refreshing in the
	// background) for a day; an unchanged index then costs a 304 with no body.
	$headers = array(
		'Cache-Control' => 'public, max-age=600, stale-while-revalidate=86400',
		'Expires'       => gmdate('D, d M Y H:i:s', time() + 600) . ' GMT',
		'ETag'          => $etag,
		'Vary'          => 'Accept-Encoding',
	);

	$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? (string) wp_unslash($_SERVER['HTTP_IF_NONE_MATCH']) : '';
	if ('' !== $if_none_match && false !== strpos($if_none_match, $etag)) {
		return new WP_REST_Response(null, 304, $headers);
	}

	return new WP_REST_Response(ssx_live_search_index_data($fingerprint), 200, $headers);
}

/**
 * Send the index gzipped (about a quarter of the size): live hosting compresses
 * HTML only. Skipped when PHP or another handler already compresses output.
 */
add_filter('rest_pre_serve_request', function ($served, $result, $request) {
	if ($served || !($request instanceof WP_REST_Request) || '/ssx/v1/search-index' !== $request->get_route()
		|| !($result instanceof WP_HTTP_Response) || 'GET' !== $request->get_method()) {
		return $served;
	}
	if (304 === $result->get_status()) {
		return true; // Not modified: no body.
	}
	if (200 !== $result->get_status()) {
		return $served;
	}

	$accept = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? (string) $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
	if (headers_sent() || !function_exists('gzencode') || false === stripos($accept, 'gzip')
		|| filter_var(ini_get('zlib.output_compression'), FILTER_VALIDATE_BOOLEAN) || array_intersect(ob_list_handlers(), array('ob_gzhandler', 'zlib output compression'))) {
		return $served;
	}

	$json = wp_json_encode($result->get_data());
	$gz   = false !== $json ? gzencode($json, 6) : false;
	if (false === $gz) {
		return $served;
	}

	header('Content-Encoding: gzip');
	echo $gz; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gzipped JSON.
	return true;
}, 10, 3);

/**
 * Flag the index stale (once per request) when staff change product meta, such
 * as the photo or "Show in list", which doesn't touch the post's modified date.
 */
function ssx_live_search_index_stale()
{
	static $queued = false;
	if ($queued) {
		return;
	}
	$queued = true;
	add_action('shutdown', static function () {
		update_option('ssx_ls_index_ver', (int) get_option('ssx_ls_index_ver', 0) + 1, true);
		if (!wp_next_scheduled('ssx_live_search_index_warm')) {
			wp_schedule_single_event(time() + 60, 'ssx_live_search_index_warm');
		}
	}, 0);
}

// Product saves (post edits) change the fingerprint by themselves; warm those too.
add_action('save_post_product', static function ($post_id, $post) {
	if (!wp_is_post_revision($post_id) && !wp_next_scheduled('ssx_live_search_index_warm')) {
		wp_schedule_single_event(time() + 60, 'ssx_live_search_index_warm');
	}
}, 10, 2);

foreach (array('added_post_meta', 'updated_post_meta', 'deleted_post_meta') as $ssx_ls_hook) {
	add_action($ssx_ls_hook, static function ($meta_ids, $post_id, $meta_key) {
		if (0 !== strpos((string) $meta_key, '_edit_') && 'product' === get_post_type($post_id) && current_user_can('edit_posts')) {
			ssx_live_search_index_stale();
		}
	}, 10, 3);
}
unset($ssx_ls_hook);
// Category changes reorder the index (shop order uses the top category).
add_action('set_object_terms', static function ($object_id, $terms, $tt_ids, $taxonomy) {
	if ('product_category' === $taxonomy) {
		ssx_live_search_index_stale();
	}
}, 10, 4);
foreach (array('edited_term', 'delete_term') as $ssx_ls_hook) {
	add_action($ssx_ls_hook, static function ($term_id, $tt_id, $taxonomy) {
		if ('product_category' === $taxonomy) {
			ssx_live_search_index_stale();
		}
	}, 10, 3);
}
unset($ssx_ls_hook);
add_action('retails_cache_cleared', 'ssx_live_search_index_stale');
