<?php

/**
 * Live (async) search endpoint used by the header search.
 *
 * Add this line to functions.php:
 *     require_once get_template_directory() . '/inc/ssx-live-search.php';
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
				'title' => html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES, 'UTF-8'),
				'url'   => get_permalink($post),
				'image' => (string) get_the_post_thumbnail_url($post, 'thumbnail'),
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
