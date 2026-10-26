<?php
/**
 * Google Merchant Center product feed at /merchant-feed.xml.
 *
 * Add the URL in Merchant Center (Products > Feeds > Scheduled fetch) to list
 * products in free Shopping listings and use them in Shopping and Performance
 * Max ads. Prices and shipping come from the same code as the product page
 * structured data and the cart, so the feed never disagrees with checkout.
 *
 * @package litsign
 */

function wholesale_merchant_feed_rewrite()
{
	add_rewrite_rule('^merchant-feed\.xml$', 'index.php?wholesale_merchant_feed=1', 'top');
}
add_action('init', 'wholesale_merchant_feed_rewrite', 20);

add_action('init', function () {
	if ('1' !== get_option('wholesale_merchant_feed_rewrite_version')) {
		flush_rewrite_rules(false);
		update_option('wholesale_merchant_feed_rewrite_version', '1');
	}
}, 99);

add_filter('query_vars', function ($vars) {
	$vars[] = 'wholesale_merchant_feed';
	return $vars;
});

/**
 * Google product category ID for a product_category slug.
 * https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt
 */
function wholesale_merchant_google_category($slug)
{
	$map = array(
		'channel-letters' => 4131, // Signage > Electric Signs > LED Signs
		'signicade-a-frames' => 5899, // Signage > Sidewalk & Yard Signs
		'a-frame-and-sign-holders' => 5899,
		'real-estate-products' => 5899,
		'advertising-flags' => 5898, // Signage > Retail & Sale Signs
		'flag-hardware' => 7421, // Decor > Flag & Windsock Poles
		'trade-show-products' => 5865, // Advertising & Marketing > Trade Show Displays
		'seg-products' => 5865,
		'banner-stands' => 5865,
		'banner-stand-hardware' => 5865,
		'hardware-only' => 5865,
		'table-throws' => 5865,
		'custom-event-tents' => 5865,
		'event-tent-hardware-only' => 5865,
		'wall-art' => 500044, // Decor > Artwork > Posters, Prints, & Visual Artwork
	);

	return isset($map[$slug]) ? $map[$slug] : 976; // Business & Industrial > Signage
}

/**
 * Plain-text product description for the feed: the "Description" section of
 * the product content (not the shared turnaround notes), else the bullets.
 */
function wholesale_merchant_description($product_id)
{
	$channel_letter = wholesale_seo_channel_letter_product($product_id);
	if ($channel_letter) {
		return $channel_letter['intro'];
	}

	$content = (string) get_post_field('post_content', $product_id);
	if (preg_match('#<h4[^>]*id="description"[^>]*>.*?</h4>(.*?)(?:<hr|<h4|$)#is', $content, $match)) {
		$text = $match[1];
	} else {
		$text = (string) get_post_meta($product_id, '_product_short_desc', true);
	}

	$text = preg_replace('#</(li|p|h[1-6])>#i', '. ', $text);
	$text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = trim(preg_replace('/\s+/', ' ', preg_replace('/\s*\.(\s*\.)+/', '.', $text)));

	return mb_substr($text, 0, 4900);
}

/**
 * Feed item ID: the product slug, which Merchant Center limits to 50
 * characters. Longer slugs are cut and end in the post ID so they stay unique.
 */
function wholesale_merchant_item_id($product)
{
	$id = (string) $product->post_name;
	if (strlen($id) <= 50) {
		return $id;
	}

	$suffix = '-' . $product->ID;

	return rtrim(substr($id, 0, 50 - strlen($suffix)), '-') . $suffix;
}

function wholesale_render_merchant_feed()
{
	if ('1' !== get_query_var('wholesale_merchant_feed')) {
		return;
	}

	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post__not_in' => wholesale_seo_duplicate_product_ids(),
		'orderby' => 'title',
		'order' => 'ASC',
	));

	nocache_headers();
	header('Content-Type: application/xml; charset=UTF-8');
	header('X-Robots-Tag: noindex, follow');
	status_header(200);

	$x = static function ($value) {
		return esc_xml(wp_strip_all_tags((string) $value));
	};

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n<channel>\n";
	echo '<title>' . $x(get_bloginfo('name')) . "</title>\n";
	echo '<link>' . esc_url(home_url('/')) . "</link>\n";
	echo '<description>' . $x(get_bloginfo('name') . ' products') . "</description>\n";

	foreach ($products as $product) {
		$offer = wholesale_seo_product_lowest_offer($product->ID);
		$price = $offer ? $offer['price'] : 0;
		$images = wholesale_product_sitemap_images($product->ID);
		$image = $images ? $images[0] : '';
		$description = wholesale_merchant_description($product->ID);
		if ($price <= 0 || !$image || '' === $description) {
			continue; // Merchant Center rejects items without a price, image or description.
		}

		$channel_letter = wholesale_seo_channel_letter_product($product->ID);
		$variant = wholesale_seo_product_variant_label($product->ID);
		$title = $channel_letter
			? $channel_letter['heading']
			: wholesale_seo_product_search_name($product->ID) . ($variant ? ' (' . $variant . ')' : '');
		// Channel letters are priced per letter: name the configuration the price buys,
		// as the product page's "One N inch letter" note does.
		$cl_from = $channel_letter && function_exists('wholesale_cl_from_price') ? wholesale_cl_from_price($product->ID) : null;
		if (!empty($cl_from['height'])) {
			$title .= sprintf(' - Single %d" Letter', $cl_from['height']);
		}

		$terms = get_the_terms($product->ID, 'product_category');
		$terms = $terms && !is_wp_error($terms) ? $terms : array();
		$term = $terms ? $terms[0] : null;
		foreach ($terms as $candidate) {
			if ($candidate->parent) {
				$term = $candidate;
			}
		}
		$product_type = '';
		if ($term) {
			$parent = $term->parent ? get_term($term->parent, 'product_category') : null;
			$product_type = ($parent && !is_wp_error($parent) ? $parent->name . ' > ' : '') . $term->name;
		}

		$shipping = wholesale_seo_offer_shipping_details($product->ID, $price);

		echo "<item>\n";
		echo '<g:id>' . $x(wholesale_merchant_item_id($product)) . "</g:id>\n";
		echo '<g:title>' . $x(mb_substr($title, 0, 150)) . "</g:title>\n";
		echo '<g:description>' . $x($description) . "</g:description>\n";
		echo '<g:link>' . esc_url(get_permalink($product)) . "</g:link>\n";
		echo '<g:image_link>' . esc_url($image) . "</g:image_link>\n";
		foreach (array_slice($images, 1) as $gallery_url) {
			echo '<g:additional_image_link>' . esc_url($gallery_url) . "</g:additional_image_link>\n";
		}
		$stock = wholesale_product_stock_status($product->ID);
		echo '<g:availability>' . ('instock' === $stock ? 'in_stock' : ('backorder' === $stock ? 'backorder' : 'out_of_stock')) . "</g:availability>\n";
		if ('backorder' === $stock) {
			// Merchant Center requires the date a backordered item ships.
			echo '<g:availability_date>' . (new DateTimeImmutable(wholesale_product_backorder_date($product->ID), wp_timezone()))->format('c') . "</g:availability_date>\n";
		}
		// The everyday price, plus the dated sale price checkout charges while a sale runs.
		echo '<g:price>' . number_format($offer['regular'], 2, '.', '') . " USD</g:price>\n";
		if ($offer['regular'] > $price && $offer['sale_ends'] && $offer['sale_starts']) {
			$zone = new DateTimeZone('America/Los_Angeles');
			$starts = DateTimeImmutable::createFromFormat('!Y-m-d', $offer['sale_starts'], $zone);
			$ends = (new DateTimeImmutable('@' . $offer['sale_ends']))->setTimezone($zone);
			echo '<g:sale_price>' . number_format($price, 2, '.', '') . " USD</g:sale_price>\n";
			if ($starts) {
				echo '<g:sale_price_effective_date>' . $starts->format('Y-m-d\TH:iO') . '/' . $ends->format('Y-m-d\TH:iO') . "</g:sale_price_effective_date>\n";
			}
		}
		echo "<g:condition>new</g:condition>\n";
		echo '<g:brand>' . $x(get_bloginfo('name')) . "</g:brand>\n";
		// Made to order: no GTIN or manufacturer part number exists.
		echo "<g:identifier_exists>no</g:identifier_exists>\n";
		echo '<g:google_product_category>' . (int) wholesale_merchant_google_category($term ? $term->slug : '') . "</g:google_product_category>\n";
		if ($product_type) {
			echo '<g:product_type>' . $x($product_type) . "</g:product_type>\n";
			// Lets ads campaigns bid and report by product line.
			echo '<g:custom_label_0>' . $x($term->name) . "</g:custom_label_0>\n";
		}
		echo "<g:shipping>\n<g:country>US</g:country>\n<g:service>Standard</g:service>\n";
		echo '<g:price>' . $x($shipping['shippingRate']['value']) . " USD</g:price>\n";
		echo '<g:min_handling_time>' . (int) $shipping['deliveryTime']['handlingTime']['minValue'] . "</g:min_handling_time>\n";
		echo '<g:max_handling_time>' . (int) $shipping['deliveryTime']['handlingTime']['maxValue'] . "</g:max_handling_time>\n";
		echo '<g:min_transit_time>' . (int) $shipping['deliveryTime']['transitTime']['minValue'] . "</g:min_transit_time>\n";
		echo '<g:max_transit_time>' . (int) $shipping['deliveryTime']['transitTime']['maxValue'] . "</g:max_transit_time>\n";
		echo "</g:shipping>\n";
		echo "</item>\n";
	}

	echo "</channel>\n</rss>\n";
	exit;
}
add_action('template_redirect', 'wholesale_render_merchant_feed', 0);
