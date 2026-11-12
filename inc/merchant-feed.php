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

// Merchant Center account the feed is in; Google Ads purchase conversions name
// it so sales can be matched to the products in the feed (cart data).
define('WHOLESALE_MERCHANT_CENTER_ID', 5877365740);

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
 * Shopping title for a printed product: "Custom" in front and "Full Color
 * Printed" after, the words shoppers search with ("custom vinyl banner"), which
 * the plain product names ("13oz Vinyl Banner") lack. Feed only; the product
 * pages keep their names. Hardware, blank items and channel letters (whose
 * titles already name the letter) are left as they are.
 *
 * @param string $title         Product name with its variant label.
 * @param string $category_name The product's category (custom_label_0).
 */
function wholesale_merchant_title($title, $category_name)
{
	$printed = array(
		'Custom Printed Banners',
		'Custom Banner Stands',
		'Custom Advertising Flags',
		'Trade Show Displays',
		'SEG Fabric Displays',
		'Custom Table Throws & Covers',
		'Custom Event Tents',
		'A-Frame Signs & Sign Holders',
		'Signicade A-Frame Sidewalk Signs',
		'Real Estate Signs',
		'Rigid Signs & Magnets',
		'Reflective Signs',
		'Dry Erase Signs',
		'Printed Adhesive Vinyl & Window Graphics',
		'Custom Wall Art Prints',
		'Large Format Printing',
	);
	// Term names store "&" as "&amp;".
	$category_name = html_entity_decode($category_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	if (!in_array($category_name, $printed, true) || preg_match('/hardware only|solid color|wood frame hanger/i', $title)) {
		return $title;
	}

	if (0 !== stripos($title, 'custom ')) {
		$title = 'Custom ' . $title;
	}
	if (!preg_match('/full color|print/i', $title)) {
		$title .= ' - Full Color Printed';
	}

	return $title;
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

/**
 * Feed item ID for a product post ID. Tracking events (GA4 items, Google Ads
 * cart data) must name products the way the feed does, or Google can't tie a
 * sale to the Shopping product that brought it.
 */
function wholesale_merchant_feed_id($product_id)
{
	$product = get_post((int) $product_id);

	return $product && 'product' === $product->post_type ? wholesale_merchant_item_id($product) : (string) $product_id;
}

/**
 * Feed items for a product's popular sizes (inc/size-chips.php): one per size,
 * ID "<base id>-<w>x<h>", the size in the title's first 40 characters, the price
 * wholesale_price_quote() gives that size with the page's default options (the
 * price /product/<slug>/?size=<w>x<h> shows in its HTML), linking to that page.
 * Products with preset sizes only get chips on the page, not feed items.
 *
 * @return array list of variant arrays for wholesale_render_merchant_feed().
 */
function wholesale_merchant_size_items($product, $title)
{
	$map = wholesale_size_chip_map();
	if (!isset($map[$product->post_name])) {
		return array();
	}
	$items = array();
	// "Custom Magnets 12" x 18" – Full Color Printed": no second "Custom" when the name has one.
	$search_name = wholesale_seo_product_search_name($product->ID);
	$suffix = 0 === stripos($search_name, 'custom ') ? 'Full Color Printed' : 'Custom Full Color Printed';
	foreach (wholesale_size_chips($product->ID) as $chip) {
		$quote = wholesale_price_quote($product->ID, wholesale_size_chip_request($product->ID, $chip['width'], $chip['height']));
		if (empty($quote['ok']) || $quote['total'] <= 0) {
			continue;
		}
		$price = (float) $quote['total'];
		$promo = !empty($quote['promo_discount']) && $quote['promo_discount'] > 0 ? wholesale_product_promotion($product->ID) : null;
		$base_offer = wholesale_seo_product_lowest_offer($product->ID);
		$size = $chip['width'] . 'x' . $chip['height'];
		$items[] = array(
			'id' => wholesale_merchant_item_id($product) . '-' . $size,
			'title' => sprintf('%s %s – %s', $search_name, str_replace('×', 'x', $chip['label']), $suffix),
			'link' => add_query_arg('size', $size, get_permalink($product)),
			'offer' => array(
				'price' => $price,
				'regular' => $promo ? round($price + (float) $quote['promo_discount'], 2) : $price,
				'sale_ends' => $promo && !empty($promo['ends']) ? (int) $promo['ends'] : null,
				'sale_starts' => $promo && !empty($base_offer['sale_starts']) ? $base_offer['sale_starts'] : '',
			),
			'price' => $price,
			'shipping' => wholesale_seo_offer_shipping_details($product->ID, $price),
			'size' => sprintf('%s x %s in', $chip['width'], $chip['height']),
		);
	}

	return $items;
}

/**
 * Every feed item (a product and its popular-size items) as plain data, shared
 * by the Google feed (/merchant-feed.xml) and the OpenAI feed
 * (inc/openai-feed.php) so both always list the same IDs, prices and links.
 *
 * @return array[]
 */
function wholesale_merchant_feed_rows()
{
	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post__not_in' => wholesale_seo_duplicate_product_ids(),
		'orderby' => 'title',
		'order' => 'ASC',
	));

	$rows = array();
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

		if (!$channel_letter && $term) {
			$title = wholesale_merchant_title($title, $term->name);
		}
		// Graphic-only, flag-only and hardware-only items say so in their first 40 characters.
		$kit_info = $channel_letter ? null : wholesale_product_kit_info($product->ID);
		if ($kit_info) {
			$title = $kit_info['feed_title'];
		}

		$shipping = wholesale_seo_offer_shipping_details($product->ID, $price);
		$stock = wholesale_product_stock_status($product->ID);
		$availability_date = 'backorder' === $stock
			? (new DateTimeImmutable(wholesale_product_backorder_date($product->ID), wp_timezone()))->format('c')
			: '';

		// The product, then its popular sizes as their own items (inc/size-chips.php):
		// "13oz Vinyl Banner 3 x 6 ft" at that size's price, linking to the page opened
		// on that size. The base item is unchanged.
		$variants = array(array(
			'id' => wholesale_merchant_item_id($product),
			'title' => $title,
			'link' => get_permalink($product),
			'offer' => $offer,
			'price' => $price,
			'shipping' => $shipping,
			'size' => '',
		));
		foreach (wholesale_merchant_size_items($product, $title) as $sized) {
			$variants[] = $sized;
		}

		foreach ($variants as $variant_item) {
			$rows[] = array(
				'product_id' => $product->ID,
				'id' => $variant_item['id'],
				'group_id' => $variant_item['size'] ? $variants[0]['id'] : '',
				'title' => $variant_item['title'],
				'description' => $description,
				'link' => $variant_item['link'],
				'image' => $image,
				'gallery' => array_slice($images, 1),
				'stock' => $stock,
				'availability_date' => $availability_date,
				'offer' => $variant_item['offer'],
				'price' => $variant_item['price'],
				'shipping' => $variant_item['shipping'],
				'size' => $variant_item['size'],
				'term_slug' => $term ? $term->slug : '',
				'term_name' => $term ? $term->name : '',
				'product_type' => $product_type,
			);
		}
	}

	return $rows;
}

function wholesale_render_merchant_feed()
{
	if ('1' !== get_query_var('wholesale_merchant_feed')) {
		return;
	}

	$rows = wholesale_merchant_feed_rows();

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

	foreach ($rows as $row) {
		$offer = $row['offer'];
		$price = $row['price'];
		$shipping = $row['shipping'];
		echo "<item>\n";
		echo '<g:id>' . $x($row['id']) . "</g:id>\n";
		if ($row['size']) {
			echo '<g:item_group_id>' . $x($row['group_id']) . "</g:item_group_id>\n";
		}
		echo '<g:title>' . $x(mb_substr($row['title'], 0, 150)) . "</g:title>\n";
		echo '<g:description>' . $x($row['description']) . "</g:description>\n";
		echo '<g:link>' . esc_url($row['link']) . "</g:link>\n";
		echo '<g:image_link>' . esc_url($row['image']) . "</g:image_link>\n";
		foreach ($row['gallery'] as $gallery_url) {
			echo '<g:additional_image_link>' . esc_url($gallery_url) . "</g:additional_image_link>\n";
		}
		$stock = $row['stock'];
		echo '<g:availability>' . ('instock' === $stock ? 'in_stock' : ('backorder' === $stock ? 'backorder' : 'out_of_stock')) . "</g:availability>\n";
		if ('backorder' === $stock) {
			// Merchant Center requires the date a backordered item ships.
			echo '<g:availability_date>' . $row['availability_date'] . "</g:availability_date>\n";
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
		if ($row['size']) {
			echo '<g:size>' . $x($row['size']) . "</g:size>\n";
		}
		echo "<g:condition>new</g:condition>\n";
		echo '<g:brand>' . $x(get_bloginfo('name')) . "</g:brand>\n";
		// Made to order: no GTIN or manufacturer part number exists.
		echo "<g:identifier_exists>no</g:identifier_exists>\n";
		echo '<g:google_product_category>' . (int) wholesale_merchant_google_category($row['term_slug']) . "</g:google_product_category>\n";
		if ($row['product_type']) {
			echo '<g:product_type>' . $x($row['product_type']) . "</g:product_type>\n";
			// Lets ads campaigns bid and report by product line.
			echo '<g:custom_label_0>' . $x($row['term_name']) . "</g:custom_label_0>\n";
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
