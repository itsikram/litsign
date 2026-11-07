<?php
/**
 * One-tap size chips on size calculator products, and size links (?size=WxH).
 *
 * 80% of Google Ads visitors use a phone; ad visitors who started an order form
 * gave up at the size boxes (92% on the Standard Retractable, 91% at "Height in
 * inches" on the 13oz banner). A chip fills width and height in one tap, with
 * its price. The same sizes are Shopping items (inc/merchant-feed.php), whose
 * links open the product on that size with that size's price in the HTML.
 * Prices always come from wholesale_price_quote() with the page's default options.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Chip sizes by product slug: width x height in inches.
 *
 * @return array slug => list of array(width, height)
 */
function wholesale_size_chip_map()
{
	$banners = array(array(24, 48), array(36, 72), array(48, 96));

	return array(
		'13oz-vinyl-banner' => $banners,
		'18oz-blockout-banner' => $banners,
		'mesh-banner' => $banners,
		'blockout-fabric-banner' => $banners,
		'fabric-banner-9oz-wrinkle-free-copy' => $banners,
		'indoor-banner-super-smooth' => $banners,
		'backlit-banner' => $banners,
		'adhesive-window-perf' => array(array(24, 36), array(36, 72), array(48, 96)),
		'adhesive-products' => array(array(12, 12), array(24, 24), array(24, 36)),
		'magnets' => array(array(12, 18), array(12, 24), array(18, 24)),
	);
}

/**
 * The quote request for a size with the options the page opens on.
 */
function wholesale_size_chip_request($product_id, $width, $height)
{
	$request = array('product_quantity' => 1, 'width-in' => $width, 'height-in' => $height);
	foreach (wholesale_product_price_attrs($product_id) as $attr) {
		$options = wholesale_attr_options($attr);
		if ($options && !empty($attr['name'])) {
			$request[$attr['name']] = $options[0]['value'];
		}
	}

	return $request;
}

/**
 * Display label for a size: feet when both sides are whole feet ("3 x 6 ft"),
 * otherwise inches ('24" x 36"').
 */
function wholesale_size_chip_label($width, $height)
{
	if ($width >= 24 && $height >= 24 && 0 == fmod($width, 12) && 0 == fmod($height, 12)) {
		return sprintf('%s × %s ft', $width / 12, $height / 12);
	}

	return sprintf('%s" × %s"', $width + 0, $height + 0);
}

/**
 * The chips for a product: the listed sizes, else its preset sizes
 * (_size_presets). Sizes outside the product's limits are left out.
 *
 * @return array list of array(width, height, label, price)
 */
function wholesale_size_chips($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}
	$cache[$product_id] = array();

	$min = wholesale_product_min_sides($product_id);
	if (!$min) {
		return array();
	}
	$map = wholesale_size_chip_map();
	$slug = (string) get_post_field('post_name', $product_id);
	// Preset sizes: the first six as chips (one phone screen); the dropdown keeps them all.
	$sizes = isset($map[$slug]) ? $map[$slug] : array_slice(wholesale_size_chip_presets($product_id), 0, 6);

	foreach ($sizes as $size) {
		list($width, $height) = $size;
		if (!wholesale_size_fits_product($product_id, $width, $height)) {
			continue;
		}
		$quote = wholesale_price_quote($product_id, wholesale_size_chip_request($product_id, $width, $height));
		if (empty($quote['ok']) || $quote['total'] <= 0) {
			continue;
		}
		$cache[$product_id][] = array(
			'width' => $width + 0,
			'height' => $height + 0,
			'label' => wholesale_size_chip_label($width, $height),
			'price' => (float) $quote['total'],
		);
	}

	return $cache[$product_id];
}

/**
 * Whether the product's size calculator takes this size (its minimum width and
 * height, and its maximum size either way round).
 */
function wholesale_size_fits_product($product_id, $width, $height)
{
	$min = wholesale_product_min_sides($product_id);
	if (!$min || $width < $min[0] || $height < $min[1]) {
		return false;
	}
	$max = wholesale_product_max_sides($product_id);
	$sides = array($width, $height);
	sort($sides);

	return !($max && ($sides[0] > $max[0] || $sides[1] > $max[1]));
}

/**
 * A product's preset sizes (_size_presets), as the B2Sign-style Size selector lists
 * them (single-product.php). From "Preset sizes" (_size_preset_list), one size per
 * line, width x height in inches, then "|" and an optional grey note, a leading "*"
 * opening the page on that size:
 *     *24 x 18 | Vertical Flutes
 * Left empty: the sizes in the labels of the product's size option, without notes.
 * Sizes the calculator does not take (too small or too big) are left out.
 *
 * @return array list of array('width', 'height', 'note', 'default')
 */
function wholesale_size_presets($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}
	$cache[$product_id] = array();
	if (!get_post_meta($product_id, '_size_presets', true)) {
		return array();
	}

	$presets = array();
	$add = function ($width, $height, $note = '', $default = false) use (&$presets, $product_id) {
		$width = (float) $width;
		$height = (float) $height;
		$key = $width . 'x' . $height;
		if (isset($presets[$key]) || !wholesale_size_fits_product($product_id, $width, $height)) {
			return;
		}
		$presets[$key] = array('width' => $width + 0, 'height' => $height + 0, 'note' => $note, 'default' => $default);
	};

	foreach (preg_split('/\r\n|\r|\n/', (string) get_post_meta($product_id, '_size_preset_list', true)) as $line) {
		$line = trim(wp_strip_all_tags($line));
		$default = '' !== $line && '*' === $line[0];
		$parts = array_map('trim', explode('|', ltrim($line, '* '), 2));
		if (preg_match('/^(\d+(?:\.\d+)?)\s*(?:"|”|″|in)?\s*[x×X]\s*(\d+(?:\.\d+)?)/u', $parts[0], $m)) {
			$add($m[1], $m[2], isset($parts[1]) ? $parts[1] : '', $default);
		}
	}

	if (!$presets) {
		foreach ((array) json_decode((string) get_post_meta($product_id, 'product_attr', true)) as $attr) {
			if (!wholesale_attr_is_preset_size((array) $attr) || empty($attr->options)) {
				continue;
			}
			foreach ($attr->options as $option) {
				foreach ((array) $option as $name => $price) {
					if (preg_match('/(\d+(?:\.\d+)?)\D+?(\d+(?:\.\d+)?)/', $name, $m)) {
						$add($m[1], $m[2]);
					}
				}
			}
		}
	}

	$cache[$product_id] = array_values($presets);

	return $cache[$product_id];
}

/**
 * The preset the Size selector opens on: the size the boxes open on when it is a
 * preset (a ?size= link), else the one marked "*", else the smallest.
 *
 * @param array $presets wholesale_size_presets()
 * @param array $size    array('width', 'height') the boxes would open on
 * @return int|null index into $presets
 */
function wholesale_size_preset_default($presets, $size, $from_link = false)
{
	if (!$presets) {
		return null;
	}
	foreach ($presets as $i => $preset) {
		if ($from_link && $preset['width'] == $size['width'] && $preset['height'] == $size['height']) {
			return $i;
		}
	}
	if ($from_link) {
		return null;
	}
	foreach ($presets as $i => $preset) {
		if ($preset['default']) {
			return $i;
		}
	}
	$smallest = 0;
	foreach ($presets as $i => $preset) {
		if ($preset['width'] * $preset['height'] < $presets[$smallest]['width'] * $presets[$smallest]['height']) {
			$smallest = $i;
		}
	}

	return $smallest;
}

/**
 * A product's preset sizes as width/height pairs, for the size chips.
 *
 * @return array list of array(width, height)
 */
function wholesale_size_chip_presets($product_id)
{
	return array_map(function ($preset) {
		return array($preset['width'], $preset['height']);
	}, wholesale_size_presets($product_id));
}

/**
 * The chip a size link (?size=36x72) asks for on the current product page, or null.
 * Only listed chip sizes count, so the price shown is one the feed also lists.
 */
function wholesale_size_link($product_id)
{
	if (empty($_GET['size']) || !is_singular('product') || (int) get_queried_object_id() !== (int) $product_id) {
		return null;
	}
	if (!preg_match('/^(\d{1,4}(?:\.\d+)?)x(\d{1,4}(?:\.\d+)?)$/', sanitize_text_field(wp_unslash($_GET['size'])), $m)) {
		return null;
	}
	foreach (wholesale_size_chips($product_id) as $chip) {
		if ((float) $chip['width'] === (float) $m[1] && (float) $chip['height'] === (float) $m[2]) {
			return $chip;
		}
	}

	return null;
}

/**
 * Size options of a fixed-size product (retractable stands, X-stands) with the
 * full price of each, for the phone tap chips that stand in for the dropdown.
 *
 * @return array list of array(value, label, price); empty when not a stand size.
 */
function wholesale_size_option_chips($product_id, $attr_name)
{
	if ('size' !== $attr_name || !has_term('banner-stands', 'product_category', $product_id)) {
		return array();
	}
	$base = wholesale_size_chip_request($product_id, 0, 0);
	unset($base['width-in'], $base['height-in']);
	$chips = array();
	foreach (wholesale_product_price_attrs($product_id) as $attr) {
		if ('size' !== ($attr['name'] ?? '')) {
			continue;
		}
		foreach (wholesale_attr_options($attr) as $option) {
			$quote = wholesale_price_quote($product_id, array_merge($base, array('size' => $option['value'])));
			if (!empty($quote['ok']) && $quote['total'] > 0) {
				$chips[] = array('value' => $option['value'], 'label' => wp_strip_all_tags((string) $option['label']), 'price' => (float) $quote['total']);
			}
		}
	}

	return count($chips) > 1 ? $chips : array();
}
