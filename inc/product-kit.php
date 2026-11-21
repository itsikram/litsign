<?php
/**
 * Partial products ("Graphic Only", "Flag Only", "Hardware Only", tent walls)
 * and the complete kits they belong to.
 *
 * Google Ads Shopping clicks went mostly to the $26.39 Standard Retractable
 * Banner Stand (Graphic Only), whose page read like the $82.49 kit: same H1,
 * bullets about aluminum hardware and a travel bag. These helpers make a
 * partial item say what it is, link to its kit, and let kits list what the
 * site already states they include. Slugs, URLs, prices, canonicals and
 * structured data are unchanged.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Partial products the slug-ending rules can't pair with a kit by name.
 *
 * @return array slug => array(kind, kit slug)
 */
function retails_product_kit_special()
{
	return array(
		'tension-fabric-stand-hardware-only' => array('hardware', 'tension-fabric-stand-graphic-frame'),
		'tent-walls-full-color-full-wall' => array('walls', 'event-tent-full-canopy-graphic-frame'),
		'tent-walls-full-color-half-wall' => array('walls', 'event-tent-full-canopy-graphic-frame'),
	);
}

/**
 * What a partial product is missing, and its kit.
 *
 * @return array|null kind (graphic, flag, hardware, walls), kit_id, kit_name,
 *                    kit_price, heading, line, kit_link, kit_need, kit_short,
 *                    feed_title, toggle_label.
 *                    Null for complete products.
 */
function retails_product_kit_info($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (array_key_exists($product_id, $cache)) {
		return $cache[$product_id];
	}
	$cache[$product_id] = null;

	$slug = (string) get_post_field('post_name', $product_id);
	$special = retails_product_kit_special();
	if (isset($special[$slug])) {
		$kind = $special[$slug][0];
		$kit = get_page_by_path($special[$slug][1], OBJECT, 'product');
		$kit_id = $kit && 'publish' === $kit->post_status ? (int) $kit->ID : 0;
	} else {
		$kinds = array('Graphic Only' => 'graphic', 'Banner Only' => 'graphic', 'Flag Only' => 'flag');
		$label = retails_seo_product_variant_label($product_id);
		$kind = isset($kinds[$label]) ? $kinds[$label] : '';
		$kit_id = $kind ? (int) retails_seo_product_variant_primary_of($product_id) : 0;
	}
	if (!$kit_id) {
		return null;
	}

	$kit_name = retails_seo_product_search_name($kit_id);
	$name = 'hardware' === $kind ? $kit_name : retails_seo_product_search_name($product_id);
	// What the kit adds, from the kit's own variant label.
	$nouns = array(
		'Graphic & Stand' => 'stand',
		'Graphic & Frame' => 'frame',
		'Banner & Frame' => 'frame',
		'Graphic & Hanger' => 'hanger',
		'Banner & Hardware' => 'hardware',
		'Flag & Pole Set' => 'pole',
	);
	$kit_label = retails_seo_product_variant_label($kit_id);
	$noun = isset($nouns[$kit_label]) ? $nouns[$kit_label] : 'stand';
	$offer = retails_seo_product_lowest_offer($kit_id);
	$kit_price = !empty($offer['price']) ? (float) $offer['price'] : 0.0;

	switch ($kind) {
		case 'flag':
			$heading = sprintf('Replacement Flag for the %s Pole Set', $kit_name);
			$line = 'Flag only. Pole and base not included.';
			$need = 'Need the pole too?';
			$with = 'with pole set';
			$bundle = 'Flag + pole set';
			$feed = sprintf('Flag Only (No Pole) – %s - Full Color Printed', $name);
			$toggle = 'Flag only';
			break;
		case 'hardware':
			$heading = sprintf('%s: Hardware Only', $kit_name);
			$line = 'Graphic not included.';
			$need = 'Need the graphic too?';
			$with = 'with printed graphic';
			$bundle = 'Hardware + printed graphic';
			$feed = sprintf('Hardware Only (No Graphic) – %s', $kit_name);
			$toggle = 'Hardware only';
			break;
		case 'walls':
			$heading = '';
			$line = 'Tent walls only. Tent frame and canopy not included.';
			$need = 'Need the tent too?';
			$with = 'with frame and printed canopy';
			$bundle = 'Complete tent';
			$feed = sprintf('Tent Walls Only (No Tent) – %s (%s) - Full Color Printed', $name, retails_seo_product_variant_label($product_id));
			$toggle = '';
			break;
		default:
			$heading = sprintf('Replacement Graphic for the %s', $kit_name);
			$print_label = preg_match('/-insert-only$/', $slug) ? 'Insert only' : 'Graphic only';
			$line = sprintf('%s. %s not included.', $print_label, ucfirst($noun));
			$need = sprintf('Need the %s too?', $noun);
			$with = 'with printed graphic';
			$bundle = sprintf('%s + graphic', ucfirst($noun));
			$feed = sprintf('Replacement Graphic Only (No %s) – %s - Full Color Printed', ucfirst($noun), $name);
			$toggle = 'Graphic only';
	}

	$cache[$product_id] = array(
		'kind' => $kind,
		'kit_id' => $kit_id,
		'kit_name' => $kit_name,
		'kit_price' => $kit_price,
		'heading' => $heading,
		'line' => $line,
		'kit_link' => trim(sprintf('%s %s %s%s', $need, $kit_name, $with, $kit_price > 0 ? ', from $' . number_format($kit_price, 2) : '')),
		// Short form for the price box under the title: ad clicks for a "$35 retractable
		// banner stand" landed on the graphic-only page, saw "Stand not included" and left
		// (Visitor Insights Nov 8-14 2026: ~370 visits to graphic-only stand pages, 0 orders),
		// while the full-name link above sat below the first screen.
		'kit_need' => $need,
		'kit_short' => trim(sprintf('%s%s', $bundle, $kit_price > 0 ? ' from $' . number_format($kit_price, 2) : '')),
		'feed_title' => $feed,
		'toggle_label' => $toggle,
	);

	return $cache[$product_id];
}

/**
 * Intro bullets for a partial product: only the ones about the print. Bullets
 * about hardware, feet, poles, bases, frames and bags describe the kit.
 */
function retails_product_kit_partial_intro($html, $product_id)
{
	$info = retails_product_kit_info($product_id);
	if (!$info || !preg_match_all('/<li\b[^>]*>.*?<\/li>/is', (string) $html, $items)) {
		return $html;
	}
	$kit_words = '/hardware|feet|stabiliz|\bpoles?\b|\bbase|frame|\bbag\b|\bcase\b|stand\b|setup|set up|assembl|tools|endcaps|chrome|adjustable height|spike|stake|hanging tabs|rails?\b|carry/i';
	if ('hardware' === $info['kind'] || 'walls' === $info['kind']) {
		return $html; // Their own bullets already describe what they are.
	}
	foreach ($items[0] as $item) {
		if (preg_match($kit_words, wp_strip_all_tags($item))) {
			$html = str_replace($item, '', $html);
		}
	}

	return $html;
}

/**
 * What a complete kit includes, taken from the site's own feature bullets for
 * that product. Kits not listed show nothing.
 *
 * @return string[]
 */
function retails_product_kit_included($product_id)
{
	$lists = array(
		'standard-retractable-insert-stand' => array('UV printed fabric graphic', 'Aluminum retractable stand with two stabilizing feet', 'Travel bag'),
		'deluxe-retractable-insert-stand' => array('UV printed fabric graphic', 'Aluminum retractable stand with chrome endcaps and adjustable height pole', 'Travel bag'),
		'sd-retractable-sd-retractable-insert-stand' => array('UV printed fabric graphic', 'Premium retractable stand, adjustable height', 'Carrying case'),
		'x-stand-graphic-stand' => array('Printed graphic with grommets', 'X-stand hardware', 'Carry bag for the hardware and print'),
		'table-top-banner-stand' => array('UV printed vinyl graphic', 'Table top stand the graphic retracts into'),
		'step-and-repeat-backdrop-graphic-frame' => array('Dye sublimated fabric graphic', 'Large tube frame, adjustable height and width', 'Carrying bag'),
		'tension-fabric-stand-graphic-frame' => array('Dye sub pillowcase graphic', 'Tube frame that clicks together, no tools', 'Carrying case'),
		'banner-a-banner-frame' => array('Full color vinyl banner graphic', 'Aluminum A-frame'),
		'poster-graphic-stand' => array('Full color 1/8" PVC graphic (22" x 28")', '60" tall matte black frame'),
		'snap-poster-graphic-hanger' => array('Printed poster graphic', '2-piece aluminum rail set with hanging tabs'),
		'custom-pole-flag-pole' => array('Dye sublimated polyester flag', 'Aluminum pole set'),
		'rectangle-flag-pole' => array('Dye sublimated polyester flag', 'Aluminum pole set'),
		'feather-angled-flag-pole' => array('Dye sublimated polyester flag', 'Tool-free interlocking pole set'),
		'feather-convex-flag-pole' => array('Dye sublimated polyester flag', 'Tool-free interlocking pole set'),
		'teardrop-flag-pole' => array('Dye sublimated polyester flag', 'Tool-free interlocking pole set'),
		'econo-feather-flag-pole' => array('Dye sub polyester flag', 'Aluminum pole set with ground stake'),
		'econo-stock-flag-pole' => array('Dye sub polyester flag', 'Aluminum pole set with ground stake'),
		'event-tent-full-canopy-graphic-frame' => array('Dye sublimated 10\' x 10\' canopy', 'Heavy duty aluminum hex frame', 'Carry bag'),
		'10ft-seg-fabric-display-graphic-frame' => array('Silicone edged UV printed fabric graphic', 'Aluminum frame'),
		'20ft-seg-fabric-display-graphic-frame' => array('Silicone edged UV printed fabric graphic', 'Aluminum frame'),
		'3ft-seg-fabric-stand-graphic-frame' => array('Silicone edged UV printed fabric graphic', 'Aluminum frame', 'Travel bag'),
		'10ft-seg-backlit-fabric-display-graphic-frame' => array('Silicone edged fabric graphic', 'Slim aluminum frame', 'LED lighting'),
		'10ft-seg-backlit-popup-display-graphic-frame' => array('Silicone edged fabric graphic', 'Accordion style frame', 'LED lighting'),
		'8ft-seg-backlit-popup-display-graphic-frame' => array('Silicone edged fabric graphic', 'Accordion style frame', 'LED lighting'),
		'curved-tension-fabric-display-graphic-frame' => array('Dye-sub tension fabric print', 'Curved tube frame, tool free', 'Carry bag'),
		'straight-tension-fabric-displays-graphic-frame' => array('Dye-sub tension fabric print', 'Straight tube frame with stabilizing feet', 'Carry bag'),
		'curved-velcro-fabric-pop-up-display-graphic-frame' => array('Dye-sub fabric print with printed end caps', 'Accordion style frame', 'Carry bag'),
		'straight-velcro-fabric-pop-up-display-graphic-frame' => array('Dye-sub fabric print with printed end caps', 'Accordion style frame', 'Carry bag'),
		'standard-signicade-white-frame-graphic' => array('Signicade A-frame (white)', 'UV printed 24" x 36" vinyl graphics'),
		'simposign-ii-white-graphic-frame' => array('SimpoSign II A-frame (white)', 'UV printed 22" x 28" coroplast graphics'),
		'deluxe-signicade-graphic-frame' => array('Signicade Deluxe A-frame', 'UV printed 24" x 36" coroplast graphics'),
	);
	$slug = (string) get_post_field('post_name', $product_id);

	return isset($lists[$slug]) ? $lists[$slug] : array();
}

/**
 * Label and starting price for one option of a product's kit / partial toggle.
 *
 * @return array label, price (0 when unknown), is_kit
 */
function retails_product_kit_toggle_option($product_id, $title)
{
	$info = retails_product_kit_info($product_id);
	$offer = retails_seo_product_lowest_offer($product_id);
	$partial = array('Graphic Only', 'Flag Only', 'Banner Only', 'Hardware Only', 'Sign Only', 'Full Wall', 'Half Wall');

	return array(
		'label' => wp_strip_all_tags((string) $title),
		'price' => !empty($offer['price']) ? (float) $offer['price'] : 0.0,
		'is_kit' => !$info && !in_array(retails_seo_product_variant_label($product_id), $partial, true),
	);
}

/**
 * The delivered-price line under the total: the price plus standard shipping
 * below the free shipping minimum. Tax is not included.
 */
function retails_product_delivered_text($total, $channel_letters = false)
{
	$min = (float) retails_get_setting($channel_letters ? 'free_shipping_channel_min' : 'free_shipping_min');
	$rates = array_filter(array_map('floatval', explode(',', (string) retails_get_setting($channel_letters ? 'channel_shipping_options' : 'standard_shipping_options'))));
	$ship = $rates ? min($rates) : 0.0;
	$total = (float) $total;
	if ($total <= 0) {
		return '';
	}
	if ($min > 0 && $total >= $min) {
		return sprintf('Free shipping. Delivered: $%s, plus sales tax', number_format($total, 2));
	}

	return $ship > 0
		? sprintf('Delivered: $%s (includes $%s shipping), plus sales tax', number_format($total + $ship, 2), number_format($ship, floor($ship) == $ship ? 0 : 2))
		: '';
}
