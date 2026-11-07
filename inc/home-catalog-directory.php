<?php
/**
 * Home page category directory: "Print, display & event products" (template-parts/home-catalog.php).
 *
 * Ported from the trade site's home page (storefrontsignwholesale.com). Five featured
 * categories as photo tiles, then every print, display and event category grouped
 * by use. Counts and photos come from the products, cached for 12 hours; saving a
 * product or category clears the cache. Channel letters and the banner and display
 * lines stay out of the tiles: their own priced grids sit above the section.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Featured tiles, groups and the category total for the home directory.
 *
 * @return array{featured: array, groups: array, total: int}
 */
function wholesale_home_catalog_directory()
{
	// Featured tiles in display order (the first is the large tile): category slug =>
	// blurb, product whose photo and price front the tile, tile tint.
	$featured = array(
		'adhesive-products'       => array('blurb' => 'Window perf, frosted film, clings, wall and floor graphics, printed to size.', 'product' => 'adhesive-window-perf', 'tint' => '#eef2fb'),
		'signicade-a-frames'      => array('blurb' => 'Signicade frames with double-sided prints you can swap for each promotion.', 'product' => 'deluxe-signicade-graphic-frame', 'tint' => '#fbeff0'),
		'rigid-signs-and-magnets' => array('blurb' => 'Aluminum, PVC, Coroplast and foam board panels, plus vehicle magnets.', 'product' => 'coroplast', 'tint' => '#e9f4fa'),
		'real-estate-products'    => array('blurb' => 'Post and frame signs, A-frames and yard signs with stakes.', 'product' => 'yard-sign-and-h-stake', 'tint' => '#ecf6ee'),
		'wall-art'                => array('blurb' => 'Canvas wraps and acrylic prints for offices, lobbies and dining rooms.', 'product' => 'canvas-wrap', 'tint' => '#fff5e1'),
	);

	// Directory groups: category slug => short name, product whose photo is the
	// thumbnail (0 = first product in the category with a photo). 'all' is the parent
	// category page for the group's "View all" link.
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
				'flag-hardware'         => array('Flag poles & bases', 0),
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
				'event-tent-hardware-only' => array('Event tent hardware', 0),
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

	$data = get_transient('wholesale_home_catalog_directory');
	if (!is_array($data) || empty($data['v']) || 2 !== $data['v']) {
		$data = array('v' => 2, 'terms' => array(), 'groups' => array(), 'featured' => array());
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
			$product = wholesale_seo_product($item['product']);
			$data['featured'][$slug] = $product ? (int) get_post_thumbnail_id($product) : 0;
		}
		set_transient('wholesale_home_catalog_directory', $data, 12 * HOUR_IN_SECONDS);
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
					'product' => $item['product'],
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

/** A saved product or category can change the directory's counts and photos. */
foreach (array('save_post_product', 'deleted_post', 'created_product_category', 'edited_product_category', 'delete_product_category') as $wholesale_home_directory_hook) {
	add_action($wholesale_home_directory_hook, static function () {
		delete_transient('wholesale_home_catalog_directory');
	});
}
unset($wholesale_home_directory_hook);
