<?php
/**
 * Product photo overrides (2026-11-09, ads landing alignment). Code only: nothing in
 * the database changes, and wp-admin still shows and saves the stored photos.
 *
 * Some supplier mockups used as product photos carry sample sale artwork ("BLACK
 * FRIDAY 50% OFF", "DISCOUNT VALUE 500$", "BEST DEAL 50% OFF" with a phone number)
 * or another company's brand. In product cards, the Merchant Center feed and
 * related-product strips they read as sales and brands we don't have. Until the
 * owner replaces those photos in wp-admin, public pages use these three lists:
 *
 * 1. retails_product_photo_overrides(): product slug => attachment ID of a clean
 *    photo of the same product, taken from that product's own gallery. It is the
 *    main photo on public pages (cards, the product page, structured data, the
 *    Merchant Center feed and the image sitemap) while the stored main photo is one
 *    of the product's hidden photos below and the clean photo is still in its gallery.
 * 2. retails_product_photo_hidden(): product slug => gallery attachment IDs that
 *    public pages leave out (product page gallery, feed additional images, image
 *    sitemap, structured data). A replaced main photo is listed here too.
 * 3. retails_card_hidden_products(): products with no clean photo at all. They stay
 *    on sale, on their own page, in their category listing and in the feed, but
 *    related-product strips, home picks and category tiles skip them instead of
 *    showing the artwork. The owner needs to add new photos for these.
 *
 * Once a product has a clean featured image and the bad gallery photo is removed in
 * wp-admin, delete its entries here (an override also stops by itself as soon as the
 * stored main photo is no longer one of the hidden ones).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Product slug => attachment ID shown as the main photo instead of the stored one.
 *
 * @return array<string, int>
 */
function retails_product_photo_overrides()
{
	return array(
		// Stored main photo 6mNPA0Wf-s1000.jpg reads BLACK FRIDAY 50% OFF.
		'adhesive-window-perf'   => 1377, // 2024/05/TuTeIjRw-s1000-1.jpg, window perf close-up.
		// Stored main photo HboixjKV-s1000.jpg is a DISCOUNT VOUCHER VALUE 500$ mockup with placeholder text.
		'mesh-banner'            => 2591, // 2026/10/storefront-sign-online-banner-mesh-square.jpg, mesh banner on a fence.
		// Stored main photo DTJcaVRJ-s1000.jpg reads DISCOUNT VALUE 500$.
		'blockout-fabric-banner' => 676,  // 2024/10/wl71oZdo_s1000.jpg, the fabric banner hanging in a shop window.
		// Stored main photo PL4aoNCf-s1000.jpg reads "Summer Discount" (both products share it).
		'poster-graphic-stand'   => 879,  // 2024/10/59PivBq8-s1000.jpg, clean 22" x 28" / 60" tall size diagram.
		'poster-graphic-only'    => 879,  // Same diagram.
	);
}

/**
 * Product slug => attachment IDs public pages never show for that product.
 *
 * @return array<string, int[]>
 */
function retails_product_photo_hidden()
{
	return array(
		'adhesive-window-perf'       => array(162, 1378), // 6mNPA0Wf main photo and its gallery copy: BLACK FRIDAY 50% OFF.
		'mesh-banner'                => array(646),       // HboixjKV: DISCOUNT VOUCHER VALUE 500$.
		'blockout-fabric-banner'     => array(677),       // DTJcaVRJ: DISCOUNT VALUE 500$.
		'13oz-vinyl-banner'          => array(662),       // Pp0sFitp: ORDER NOW 012 345 6788, BEST DEAL 50% OFF.
		'adhesive-translucent-vinyl' => array(1372, 1373), // "Includes: Free Lamination" overlay; garbled AI lettering on both.
		'premium-window-cling'       => array(1379),      // glhJNv9n: BLACK FRIDAY SALE UP TO 50%.
		'floor-graphics'             => array(1386),      // B4M412yD: UP TO 50% OFF, CLEARANCE 33-50%.
		'aluminum-sandwich-board'    => array(908),       // HRCg1olp: a Keller Williams kw ELITE yard sign.
		'pvc-board'                  => array(911),       // TgnF2AeN: "Summer Discount".
		'poster-graphic-stand'       => array(881, 880),  // PL4aoNCf and 3zir4Q9f: "Summer Discount" (the second also misspells "Printsd").
		'poster-graphic-only'        => array(881, 880),  // Same two photos.
	);
}

/**
 * Products whose photos all carry sale artwork (no clean photo in their gallery).
 *
 * @return string[] Product slugs.
 */
function retails_card_hidden_products()
{
	return array(
		'fabric-banner-9oz-wrinkle-free-copy', // BLACK FRIDAY 50% OFF; the gallery has only a finishing chart.
		'tension-fabric',                      // GRAND OPENING 50% OFF; the gallery has only a finishing chart.
		'tension-fabric-stand-graphic-frame',  // BLACK FRIDAY 50% OFF SALE with phone 323-517-4946; other photos are parts or the same art.
		'tension-fabric-stand-graphic-only',   // Same photo.
		'x-stand-graphic-stand',               // Pizza "50%" art with phone 609-791-3583; other photos are hardware or the same art.
		'x-stand-graphic-only',                // Same photo.
	);
}

/**
 * Whether public pages use the lists above: front-end pages, feeds and sitemaps, and
 * front-end AJAX (cart drawer). Not wp-admin, its AJAX, the REST API (the block
 * editor reads and saves the featured image there) or cron.
 */
function retails_product_photo_overrides_active()
{
	if (wp_doing_cron()) {
		return false;
	}
	// REST: on for public reads such as the header live search (/wp-json/ssx/v1/search),
	// off for editors; the block editor sends a nonce, so its user is set and it keeps
	// reading and saving the stored photos.
	if (defined('REST_REQUEST') && REST_REQUEST) {
		return !current_user_can('edit_posts');
	}
	if (!is_admin()) {
		return true;
	}
	if (wp_doing_ajax()) {
		return false === strpos((string) wp_get_raw_referer(), '/wp-admin/');
	}
	return false;
}

/**
 * Product IDs for the slugs in the lists above, in one query per request.
 *
 * @return array<string, int> Slug => product ID (published products only).
 */
function retails_product_photo_product_ids()
{
	static $ids = null;
	if (null !== $ids) {
		return $ids;
	}
	$slugs = array_values(array_unique(array_merge(
		array_keys(retails_product_photo_overrides()),
		array_keys(retails_product_photo_hidden()),
		retails_card_hidden_products()
	)));
	global $wpdb;
	$ids = array();
	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' AND post_name IN (" . implode(',', array_fill(0, count($slugs), '%s')) . ')',
		$slugs
	));
	foreach ((array) $rows as $row) {
		$ids[$row->post_name] = (int) $row->ID;
	}
	return $ids;
}

/**
 * A product's stored meta value, read from the meta cache (bypasses the filters below).
 */
function retails_product_photo_stored_meta($product_id, $key)
{
	$cache = wp_cache_get($product_id, 'post_meta');
	if (false === $cache) {
		$cache = update_meta_cache('post', array($product_id));
		$cache = isset($cache[$product_id]) ? $cache[$product_id] : array();
	}
	return isset($cache[$key][0]) ? maybe_unserialize($cache[$key][0]) : null;
}

/**
 * Attachment IDs public pages don't show for a product.
 *
 * @return int[]
 */
function retails_product_photo_hidden_ids($product_id)
{
	$slug = array_search((int) $product_id, retails_product_photo_product_ids(), true);
	$hidden = retails_product_photo_hidden();
	return false !== $slug && isset($hidden[$slug]) ? array_map('intval', $hidden[$slug]) : array();
}

/**
 * The clean main photo for a product, or 0 when its stored main photo is fine.
 */
function retails_product_photo_override_id($product_id)
{
	$product_id = (int) $product_id;
	$slug = array_search($product_id, retails_product_photo_product_ids(), true);
	$overrides = retails_product_photo_overrides();
	if (false === $slug || empty($overrides[$slug])) {
		return 0;
	}
	$stored = (int) retails_product_photo_stored_meta($product_id, '_thumbnail_id');
	$gallery = retails_product_photo_stored_meta($product_id, '_product_gallery');
	$clean = (int) $overrides[$slug];
	$active = in_array($stored, retails_product_photo_hidden_ids($product_id), true)
		&& is_array($gallery) && isset($gallery[$clean]) && wp_attachment_is_image($clean);
	return $active ? $clean : 0;
}

add_filter('post_thumbnail_id', static function ($thumbnail_id, $post) {
	if (!$thumbnail_id || !retails_product_photo_overrides_active()) {
		return $thumbnail_id;
	}
	$post = get_post($post);
	if (!$post || 'product' !== $post->post_type) {
		return $thumbnail_id;
	}
	$clean = retails_product_photo_override_id($post->ID);
	return $clean ? $clean : $thumbnail_id;
}, 10, 2);

add_filter('get_post_metadata', static function ($value, $object_id, $meta_key, $single) {
	if ('_product_gallery' !== $meta_key || null !== $value || !retails_product_photo_overrides_active()) {
		return $value;
	}
	$hidden = retails_product_photo_hidden_ids($object_id);
	if (!$hidden) {
		return $value;
	}
	$gallery = retails_product_photo_stored_meta((int) $object_id, '_product_gallery');
	if (!is_array($gallery)) {
		return $value;
	}
	foreach ($hidden as $attachment_id) {
		unset($gallery[$attachment_id]);
	}
	// The clean photo that replaced the main photo leads the gallery, as the main photo did.
	$clean = retails_product_photo_override_id($object_id);
	if ($clean && isset($gallery[$clean])) {
		$gallery = array($clean => $gallery[$clean]) + $gallery;
	}
	return array($gallery);
}, 10, 4);

/**
 * Whether cards and tiles should skip a product (see retails_card_hidden_products()).
 *
 * @param int|WP_Post|string $product Product ID, post or slug.
 */
function retails_product_card_hidden($product)
{
	if (is_string($product) && !is_numeric($product)) {
		return in_array($product, retails_card_hidden_products(), true);
	}
	$product_id = $product instanceof WP_Post ? (int) $product->ID : (int) $product;
	return in_array($product_id, retails_card_hidden_product_ids(), true);
}

/**
 * IDs of the card-hidden products, for post__not_in.
 *
 * @return int[]
 */
function retails_card_hidden_product_ids()
{
	return array_values(array_intersect_key(retails_product_photo_product_ids(), array_flip(retails_card_hidden_products())));
}

/**
 * A card list (product slug => label) without the card-hidden products, topped up
 * from $fallbacks (slug => label) so the grid keeps its length.
 *
 * @return array<string, string>
 */
function retails_card_picks(array $picks, array $fallbacks = array())
{
	$out = array();
	foreach ($picks as $slug => $label) {
		if (!retails_product_card_hidden($slug)) {
			$out[$slug] = $label;
		}
	}
	foreach ($fallbacks as $slug => $label) {
		if (count($out) >= count($picks)) {
			break;
		}
		if (!isset($out[$slug]) && !isset($picks[$slug]) && !retails_product_card_hidden($slug)) {
			$out[$slug] = $label;
		}
	}
	return $out;
}
