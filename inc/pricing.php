<?php
/**
 * Server-side pricing. The product page and channel letter builder show live prices
 * in the browser, but the amount that reaches the cart (and the card) is always
 * calculated here from the product's own settings.
 *
 * Rules mirror js/main.js and js/cl.js:
 *  - Channel letters: letters × price for the selected height, or the builder design total.
 *  - Calculator products: square feet × price per sq ft (minimum sq ft enforced).
 *  - Fixed products: minimum sq ft × price per sq ft (usually 1 × the base price).
 *  - Each priced option is added in page order: "N" or "N/label" adds N, "N/%" adds N% of the
 *    running price, "N/sqft" adds N per sq ft, "N/lft" adds N per linear foot of letters.
 *  - Quantity multiplies everything; the product discount applies after quantity, then any
 *    dated sale (wholesale_product_promotion()); same-day turnaround adds 100% of the
 *    undiscounted price.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_RACEWAY_COST_PER_FOOT = 50;
const WHOLESALE_CL_MIN_INCHES = 8;
const WHOLESALE_CL_MAX_INCHES = 45;
// Default lowest per-item price for products sized with the sq ft dimensions calculator.
// A product can set its own with _min_order_price (see wholesale_product_min_order()).
const WHOLESALE_SQFT_MIN_PRICE = 15;

/**
 * Lowest price one item of a calculator product can cost, after the product's own
 * _discount_percent: its _min_order_price, or WHOLESALE_SQFT_MIN_PRICE when that is
 * empty. 0 means no minimum.
 */
function wholesale_product_min_order($product_id)
{
	$value = trim((string) get_post_meta($product_id, '_min_order_price', true));
	return is_numeric($value) ? max(0.0, (float) $value) : (float) WHOLESALE_SQFT_MIN_PRICE;
}

/**
 * Smallest area (sq ft) the size calculator accepts: the area whose price per sq ft,
 * after the product's own discount, reaches its minimum order price. Rounded up to
 * 0.01 sq ft, the precision it is shown at. 0 when there is no minimum.
 */
function wholesale_product_min_area($product_id)
{
	if (get_post_meta($product_id, '_hide_calculator', true) || wholesale_product_min_sides($product_id)) {
		return 0.0;
	}
	$rate = max(0, (float) get_post_meta($product_id, '_price_per_sqft', true));
	$keep = (100 - min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)))) / 100;
	$min_price = wholesale_product_min_order($product_id);
	if ($rate <= 0 || $keep <= 0 || $min_price <= 0) {
		return 0.0;
	}
	return ceil(round($min_price / ($rate * $keep), 6) * 100) / 100;
}

/**
 * Size calculator products (like B2Sign): a smallest width and height, from the
 * product's _min_width / _min_height in inches (6" when empty, none when "0"). Any size from there up
 * can be ordered; one that costs less than the minimum order price pays that minimum.
 * array(width_in, height_in), or null for products without the size calculator.
 */
function wholesale_product_min_sides($product_id)
{
	if (get_post_meta($product_id, '_hide_calculator', true) || wholesale_product_is_channel_letter($product_id)) {
		return null;
	}
	// "0" means any size (like B2Sign's vinyl, which only bills its minimum charge).
	$side = static function ($key) use ($product_id) {
		$value = trim((string) get_post_meta($product_id, $key, true));
		return '0' === $value || (float) $value > 0 ? (float) $value : 6.0;
	};
	return array($side('_min_width'), $side('_min_height'));
}

/**
 * Largest size a calculator product can be made (like B2Sign's "48" x 1200" or
 * 1200" x 48""): the product's _max_size, "short x long" in inches, which the size may
 * fit either way round. array(short_in, long_in), or null when the product has none
 * (the legacy _max_width / _max_height are not enforced).
 */
function wholesale_product_max_sides($product_id)
{
	if (!wholesale_product_min_sides($product_id)) {
		return null;
	}
	if (!preg_match('/(\d+(?:\.\d+)?)\D+(\d+(?:\.\d+)?)/', (string) get_post_meta($product_id, '_max_size', true), $m)) {
		return null;
	}
	$sides = array((float) $m[1], (float) $m[2]);
	sort($sides);
	return $sides[0] > 0 ? $sides : null;
}

/**
 * Size the calculator opens with: the largest square, in whole inches, that still
 * costs the product's minimum charge (e.g. 15" x 15" at $9.90/ft2 with a $16 minimum),
 * kept within the product's min/max sides. array(width_in, height_in), or null for
 * products without the size calculator.
 */
function wholesale_product_default_size($product_id)
{
	$min_sides = wholesale_product_min_sides($product_id);
	if (!$min_sides) {
		return null;
	}
	$rate = max(0, (float) get_post_meta($product_id, '_price_per_sqft', true));
	$keep = (100 - min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)))) / 100;
	$min_price = wholesale_product_min_order($product_id);
	$side = $rate > 0 && $keep > 0 && $min_price > 0 ? floor(round(sqrt($min_price / ($rate * $keep)) * 12, 6)) : 0;
	$max_sides = wholesale_product_max_sides($product_id);
	if ($max_sides) {
		$side = min($side, $max_sides[0]);
	}
	// Options the page opens on can carry a price of their own (e.g. an included
	// H-stake), so shrink the square until that quote still lands on the minimum charge.
	if ($side > 0 && $min_price > 0) {
		$request = array('product_quantity' => 1);
		foreach (wholesale_product_price_attrs($product_id) as $attr) {
			$options = wholesale_attr_options($attr);
			if ($options && !empty($attr['name'])) {
				$request[$attr['name']] = $options[0]['value'];
			}
		}
		while ($side > max($min_sides[0], $min_sides[1], 1)) {
			$quote = wholesale_price_quote($product_id, array('width-in' => $side, 'height-in' => $side) + $request);
			if (!$quote['ok'] || !empty($quote['minimum_order'])) {
				break;
			}
			$side--;
		}
	}
	return array(max($side, $min_sides[0]), max($side, $min_sides[1]));
}

/**
 * Example size for wholesale_product_min_area(): the smallest square in whole inches
 * that reaches it, e.g. 14" x 14".
 */
function wholesale_min_area_example($area)
{
	$side = (int) ceil(round(sqrt($area) * 12, 6));
	return sprintf('%d" x %d"', $side, $side);
}

function wholesale_product_is_channel_letter($product_id)
{
	$terms = get_the_terms($product_id, 'product_category');
	return is_array($terms) && isset($terms[0]) && 'channel-letters' === $terms[0]->slug;
}

/**
 * What shopper-facing copy calls one of this product ("Bought this banner?",
 * "the width of your flag"). Only channel letters are a "sign"; everything
 * else uses its own word, and hardware or anything unmapped is a "product".
 */
function wholesale_product_noun($product_id)
{
	$nouns = array(
		'channel-letters' => 'sign',
		'signs-letters' => 'sign',
		'banners' => 'banner',
		'advertising-flags' => 'flag',
		'custom-event-tents' => 'tent',
		'table-throws' => 'table cover',
		'banner-stands' => 'display',
		'seg-products' => 'display',
		'trade-show-products' => 'display',
		'indoor-outdoor-displays' => 'display',
		'real-estate-products' => 'display',
		'a-frame-and-sign-holders' => 'display',
		'signicade-a-frames' => 'display',
		'adhesive-products' => 'graphic',
		'wall-art' => 'print',
		'rigid-signs-and-magnets' => 'print',
		'reflective-products' => 'print',
		'dry-erase-products' => 'print',
		'large-format' => 'print',
	);
	$terms = get_the_terms($product_id, 'product_category');
	$slugs = is_array($terms) ? wp_list_pluck($terms, 'slug') : array();
	foreach ($nouns as $slug => $noun) {
		if (in_array($slug, $slugs, true)) {
			return $noun;
		}
	}
	return 'product';
}

/**
 * One word for a whole cart or order ("Ship my banner", "Your sign is on its way").
 * Takes cart item objects or order product_json rows. Items that disagree make it an "order".
 */
function wholesale_items_noun($items)
{
	$nouns = array();
	foreach ((array) $items as $item) {
		$product_id = is_object($item) ? ($item->product_id ?? 0) : ($item['product_id'] ?? 0);
		if ($product_id) {
			$nouns[wholesale_product_noun((int) $product_id)] = true;
		}
	}
	return 1 === count($nouns) ? (string) key($nouns) : 'order';
}

/**
 * The noun for an order post, from its saved items.
 */
function wholesale_order_noun($order_post_id)
{
	return wholesale_items_noun(wholesale_decode_order_meta_array(get_post_meta($order_post_id, 'product_json', true)));
}

function wholesale_product_price_attrs($product_id)
{
	$attrs = json_decode((string) get_post_meta($product_id, 'product_attr', true), true);
	return is_array($attrs) ? array_values(array_filter($attrs, 'is_array')) : array();
}

/**
 * Flattens [{"Label": "value"}, ...] into [['label' => ..., 'value' => ...], ...].
 */
function wholesale_attr_options($attr)
{
	$options = array();
	foreach (isset($attr['options']) && is_array($attr['options']) ? $attr['options'] : array() as $option) {
		if (!is_array($option)) {
			continue;
		}
		foreach ($option as $label => $value) {
			$options[] = array('label' => (string) $label, 'value' => (string) $value);
		}
	}
	return $options;
}

/**
 * "10/%/red" => 10 percent, "3/sqft/..." => 3 per sq ft, "25/Single" or "25" => +25.
 * "73.3/%total/2 Sides" => 73.3 percent of the item after every other option (B2Sign's
 * double-sided upcharge, which also covers its holes).
 * A value that doesn't start with a number (a color name, "3-Inch") carries no price.
 */
function wholesale_parse_option_price($value)
{
	$parts = explode('/', trim((string) $value));
	if (!is_numeric(trim($parts[0]))) {
		return array('amount' => 0.0, 'type' => 'fixed');
	}
	$type = isset($parts[1]) ? strtolower(trim($parts[1])) : '';
	return array(
		'amount' => (float) $parts[0],
		'type' => in_array($type, array('%', '%total', 'sqft', 'lft'), true) ? $type : 'fixed',
	);
}

/**
 * Form fields are named after the attribute, with a few spelling variants between templates.
 */
function wholesale_request_attr_value($request, $attr_name)
{
	foreach (array($attr_name, strtolower($attr_name), str_replace(' ', '-', $attr_name), strtolower(str_replace(' ', '-', $attr_name)), str_replace(' ', '', $attr_name)) as $key) {
		if (isset($request[$key]) && !is_array($request[$key])) {
			return (string) $request[$key];
		}
	}
	return null;
}

/**
 * A preset "size" dropdown (12" x 18", 24" x 36", ...). When the product shows the
 * width/height calculator the typed size sets the price, so this attribute is neither
 * shown nor charged; it only applies while the calculator is hidden.
 */
function wholesale_attr_is_preset_size($attr)
{
	$css = (string) ($attr['cssClass'] ?? '');
	return 'size' === strtolower(trim((string) ($attr['name'] ?? ''))) && false === strpos($css, 'size-width-inch') && false === strpos($css, 'size-height-inch');
}

function wholesale_same_day_available()
{
	$now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
	return (int) $now->format('G') < 12;
}

function wholesale_count_letters($text)
{
	return (int) preg_match_all('/\S/u', (string) $text);
}

/**
 * Face color rate per inch for a builder element: the cost of the color group the
 * chosen color belongs to, or the product's default color cost.
 */
function wholesale_cl_face_rate($product_id, $cl_data, $face_color)
{
	if (empty($face_color['title'])) {
		return (float) get_post_meta($product_id, '_default_color_cost', true);
	}
	foreach ($cl_data as $group) {
		if (!isset($group['id']) || 0 !== strpos((string) $group['id'], 'color-')) {
			continue;
		}
		foreach (wholesale_attr_options($group) as $option) {
			if (trim($option['label']) === trim((string) $face_color['title'])) {
				return (float) ($group['cost'] ?? 0);
			}
		}
	}
	return null;
}

function wholesale_cl_rate_for_size($cost_table, $inches)
{
	$size = (int) floor((float) $inches);
	return isset($cost_table[$size]) ? $cost_table[$size] : null;
}

/**
 * Price of a channel letter builder design, calculated from the product's rate table.
 * Element sizes, text and colors come from the design; every dollar amount comes from
 * the product settings. Returns a WP_Error when the design can't be priced.
 */
function wholesale_cl_design_quote($product_id, $design)
{
	$cl_data = json_decode((string) get_post_meta($product_id, 'product_cl_data', true), true);
	$cl_data = is_array($cl_data) ? $cl_data : array();

	$cost_table = array();
	foreach ($cl_data as $group) {
		if (isset($group['id']) && 'cost-per-inch' === $group['id']) {
			foreach (wholesale_attr_options($group) as $option) {
				$cost_table[(int) $option['label']] = (float) $option['value'];
			}
		}
	}
	if (!$cost_table) {
		return new WP_Error('no_rates', 'This product is not set up for online pricing yet. Please call us for a quote.');
	}

	$elements = isset($design['elements']) && is_array($design['elements']) ? $design['elements'] : array();
	if (!$elements) {
		return new WP_Error('empty_design', 'Your design is empty. Add your letters in the builder first.');
	}

	$letters_cost = 0.0;
	foreach ($elements as $element) {
		if (!is_array($element)) {
			continue;
		}
		$type = isset($element['type']) ? (string) $element['type'] : '';
		$width = (float) ($element['width'] ?? 0);
		$height = (float) ($element['height'] ?? 0);

		if ('Raceway' === $type) {
			$letters_cost += ($width / 12) * WHOLESALE_RACEWAY_COST_PER_FOOT;
			continue;
		}

		$face_rate = wholesale_cl_face_rate($product_id, $cl_data, isset($element['faceColor']) && is_array($element['faceColor']) ? $element['faceColor'] : array());
		if (null === $face_rate) {
			return new WP_Error('bad_color', 'One of the colors in your design is no longer available. Please pick another color in the builder.');
		}

		if ('Text' === $type) {
			$count = wholesale_count_letters($element['text'] ?? '');
			$rate = wholesale_cl_rate_for_size($cost_table, $height);
			if (!$count) {
				continue;
			}
			if (null === $rate) {
				return new WP_Error('bad_size', sprintf('Letter height must be between %d" and %d". Please resize your letters in the builder.', WHOLESALE_CL_MIN_INCHES, WHOLESALE_CL_MAX_INCHES));
			}
			$letters_cost += $rate * $count + $height * $count * $face_rate;
			continue;
		}

		// Shapes are priced on their larger side.
		$size = max($width, $height);
		$rate = wholesale_cl_rate_for_size($cost_table, $size);
		if (null === $rate) {
			return new WP_Error('bad_size', sprintf('Shapes must be between %d" and %d". Please resize them in the builder.', WHOLESALE_CL_MIN_INCHES, WHOLESALE_CL_MAX_INCHES));
		}
		$letters_cost += $rate + $size * $face_rate;
	}

	// Same fallbacks as cl_builderr.php.
	$meta_or = static function ($key, $default) use ($product_id) {
		$value = get_post_meta($product_id, $key, true);
		return $value ? (float) $value : (float) $default;
	};
	$extras = isset($design['extras']) && is_array($design['extras']) ? $design['extras'] : array();
	$extras_cost = 0.0;

	if (isset($extras['powerSupply']['value']) && 'Standard' === $extras['powerSupply']['value'] && !empty($extras['powerSupply']['qty'])) {
		$extras_cost += $meta_or('_standard_ps_cost', 90);
	}
	if (isset($extras['lit']['value']) && 'Back Lit' === $extras['lit']['value'] && !empty($extras['lit']['qty']) && get_post_meta($product_id, '_is_lit_option', true)) {
		$extras_cost += $letters_cost * $meta_or('_backlit_cost', 100) / 100;
	}
	if (isset($extras['cable']['value']) && '8ft Cable' === $extras['cable']['value'] && !empty($extras['cable']['qty'])) {
		$extras_cost += $meta_or('_eight_ft_cable_cost', 70);
	}

	return round($letters_cost + $extras_cost, 2);
}

/**
 * Full quote for one cart line.
 *
 * @param int        $product_id
 * @param array      $request  Submitted product form fields.
 * @param array|null $design   Channel letter builder design, when the customer used the builder.
 * @return array{ok:bool,error:string,unit_price:float,quantity:int,subtotal:float,discount_percent:float,discount:float,turnaround:float,total:float,lines:array}
 */
function wholesale_price_quote($product_id, $request, $design = null)
{
	$fail = static function ($message) {
		return array('ok' => false, 'error' => $message, 'unit_price' => 0, 'quantity' => 1, 'subtotal' => 0, 'discount_percent' => 0, 'discount' => 0, 'turnaround' => 0, 'total' => 0, 'lines' => array());
	};

	$product_id = absint($product_id);
	if (!$product_id || 'product' !== get_post_type($product_id) || 'publish' !== get_post_status($product_id)) {
		return $fail('This product is no longer available.');
	}

	$is_cl = wholesale_product_is_channel_letter($product_id);
	$attrs = wholesale_product_price_attrs($product_id);
	$lines = array();
	$min_sqft = max(0, (float) get_post_meta($product_id, '_min_sqft', true));
	$price_per_sqft = max(0, (float) get_post_meta($product_id, '_price_per_sqft', true));
	$sqft = $min_sqft;
	$letter_count = 0;
	$letter_height = 0;
	$is_sqft_calc = false;
	$size_error = '';

	if ($is_cl && is_array($design)) {
		$design_total = wholesale_cl_design_quote($product_id, $design);
		if (is_wp_error($design_total)) {
			return $fail($design_total->get_error_message());
		}
		$running = $design_total;
		$lines[] = array('label' => 'Your channel letter design', 'amount' => $design_total);
		$attrs = array(); // The builder design already includes every option.
	} elseif ($is_cl) {
		$letter_count = wholesale_count_letters($request['letters'] ?? '');
		if (!$letter_count) {
			return $fail('Enter the text for your sign so we can calculate a price.');
		}
		$height_value = wholesale_request_attr_value($request, 'height');
		$per_letter = null;
		foreach ($attrs as $attr) {
			if ('height' !== ($attr['name'] ?? '')) {
				continue;
			}
			foreach (wholesale_attr_options($attr) as $option) {
				if (null !== $height_value && trim($option['value']) === trim($height_value)) {
					$per_letter = (float) $option['value'];
					$letter_height = (int) $option['label'];
					break;
				}
			}
		}
		if (null === $per_letter) {
			return $fail('Please choose a letter height.');
		}
		$running = $letter_count * $per_letter;
		$lines[] = array('label' => sprintf('%d letters at %d"', $letter_count, $letter_height), 'amount' => $running);
	} else {
		$has_calculator = !get_post_meta($product_id, '_hide_calculator', true);
		$width_attr_value = null;
		$height_attr_value = null;
		foreach ($attrs as $attr) {
			$css = (string) ($attr['cssClass'] ?? '');
			if (false !== strpos($css, 'size-width-inch')) {
				$width_attr_value = wholesale_request_attr_value($request, $attr['name']);
			}
			if (false !== strpos($css, 'size-height-inch')) {
				$height_attr_value = wholesale_request_attr_value($request, $attr['name']);
			}
		}

		if (null !== $width_attr_value && null !== $height_attr_value) {
			// Products sized with width/height dropdowns in inches (e.g. table runners).
			$sqft = ((float) $width_attr_value / 12) * ((float) $height_attr_value / 12);
		} elseif ($has_calculator) {
			$height_ft = (float) ($request['height-ft'] ?? 0) + (float) ($request['height-in'] ?? 0) / 12;
			$width_ft = (float) ($request['width-ft'] ?? 0) + (float) ($request['width-in'] ?? 0) / 12;
			// The smallest size: a width and height (wholesale_product_min_sides()), or else the
			// area that reaches the minimum order price (wholesale_product_min_area()).
			foreach (array('height' => $height_ft, 'width' => $width_ft) as $label => $value) {
				if ($value <= 0) {
					return $fail(sprintf('Please enter the %s of your %s.', $label, wholesale_product_noun($product_id)));
				}
			}
			$is_sqft_calc = true;
			$sqft = $height_ft * $width_ft;
			$min_sides = wholesale_product_min_sides($product_id);
			// Like B2Sign, a size outside the limits is still priced (the page shows it) but
			// the quote is not ok, so it can't reach the cart.
			if ($min_sides && ($width_ft * 12 < $min_sides[0] - 0.000001 || $height_ft * 12 < $min_sides[1] - 0.000001)) {
				$size_error = sprintf('The smallest size for this product is %s" x %s" (width x height). Please enter a larger size.', $min_sides[0] + 0, $min_sides[1] + 0);
			}
			$max_sides = wholesale_product_max_sides($product_id);
			if ($max_sides && (min($width_ft, $height_ft) * 12 > $max_sides[0] + 0.000001 || max($width_ft, $height_ft) * 12 > $max_sides[1] + 0.000001)) {
				$size_error = sprintf('The largest size for this product is %1$s" x %2$s" or %2$s" x %1$s". Please enter a smaller size.', $max_sides[0] + 0, $max_sides[1] + 0);
			}
			$min_area = wholesale_product_min_area($product_id);
			if ($sqft < $min_area - 0.000001) {
				return $fail(sprintf('The smallest size for this product is %s sq ft (for example %s). Please enter a larger size.', number_format($min_area, 2), wholesale_min_area_example($min_area)));
			}
		}

		$running = $sqft * $price_per_sqft;
		$lines[] = array('label' => $has_calculator || null !== $width_attr_value ? sprintf('%s sq ft', number_format($sqft, 2)) : 'Base price', 'amount' => $running);
	}

	$sides = 1;
	$percent_total = array();
	foreach ($attrs as $attr) {
		$options = wholesale_attr_options($attr);
		$css = (string) ($attr['cssClass'] ?? '');
		// Single-option attributes are shown as fixed text (charged only when they carry a
		// price, e.g. an included H-stake), and avoid-price ones are handled above.
		$fixed = 1 === count($options) && 0.0 !== round(wholesale_parse_option_price($options[0]['value'])['amount'], 2);
		if (count($options) < 2 && !$fixed || false !== strpos($css, 'avoid-price') || 'height' === ($attr['name'] ?? '') && $is_cl) {
			continue;
		}
		// The calculator's typed size replaces a preset size dropdown.
		if ($is_sqft_calc && wholesale_attr_is_preset_size($attr)) {
			continue;
		}

		$submitted = wholesale_request_attr_value($request, $attr['name']);
		$chosen = $fixed ? $options[0] : null;
		foreach ($fixed ? array() : $options as $option) {
			if (null !== $submitted && (trim($option['value']) === trim($submitted) || sanitize_text_field($option['value']) === trim($submitted))) {
				$chosen = $option;
				break;
			}
		}
		if (null === $chosen) {
			return $fail(sprintf('Please choose a valid %s.', str_replace('-', ' ', strtolower($attr['name']))));
		}
		if (in_array(strtolower((string) $attr['name']), array('of-sides', 'sides'), true) && (int) $chosen['label'] > 1) {
			$sides = (int) $chosen['label'];
		}

		$price = wholesale_parse_option_price($chosen['value']);
		switch ($price['type']) {
			case '%total':
				// Applied after the loop, to the item with every other option.
				$percent_total[] = array('attr' => $attr, 'chosen' => $chosen, 'percent' => $price['amount']);
				continue 2;
			case '%':
				$amount = $running * $price['amount'] / 100;
				break;
			case 'sqft':
				$amount = ($is_sqft_calc ? $sqft : max($min_sqft, $sqft)) * $price['amount'];
				break;
			case 'lft':
				$amount = $price['amount'] * ($letter_height * $letter_count / 12);
				break;
			default:
				$amount = $price['amount'];
		}

		if (0.0 !== round($amount, 2)) {
			$lines[] = array('label' => ucwords(str_replace('-', ' ', $attr['name'])) . ': ' . $chosen['label'], 'amount' => $amount);
		}
		$running += $amount;
	}
	$base_running = $running;
	foreach ($percent_total as $item) {
		$amount = $base_running * $item['percent'] / 100;
		if (0.0 !== round($amount, 2)) {
			$lines[] = array('label' => ucwords(str_replace('-', ' ', $item['attr']['name'])) . ': ' . $item['chosen']['label'], 'amount' => $amount);
		}
		$running += $amount;
	}

	if ($running <= 0) {
		return $fail('Please choose your product options so we can calculate a price.');
	}

	// The size stays exactly as entered. When one item would cost less than the
	// product's minimum after its own price adjustment, the item price is raised so
	// that it comes to exactly the minimum after the adjustment.
	$discount_percent = min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)));
	$minimum_order = 0;
	$min_price = $is_sqft_calc && $discount_percent < 100 ? wholesale_product_min_order($product_id) : 0;
	// _min_order_per_side: the minimum is per printed side (B2Sign: $8 one side, $16 two).
	if (get_post_meta($product_id, '_min_order_per_side', true)) {
		$min_price *= $sides;
	}
	if ($min_price > 0 && $running * (100 - $discount_percent) / 100 < $min_price) {
		$raised = $min_price * 100 / (100 - $discount_percent);
		$lines[] = array('label' => sprintf('Minimum order ($%s)', number_format($min_price, 2)), 'amount' => $raised - $running);
		$running = $raised;
		$minimum_order = $min_price;
	}

	$quantity = min(1000, max(1, absint($request['product_quantity'] ?? 1)));
	$subtotal = $running * $quantity;
	$discount = $subtotal * $discount_percent / 100;
	// A dated sale (Settings > Storefront Sign) comes off the everyday price, after the
	// product's own price adjustment above. Zero outside the sale's dates.
	$everyday = $subtotal - $discount;
	$promo_percent = wholesale_product_promotion_percent($product_id);
	// Rounded the way the shown sale price is (wholesale_promotion_price()), so a
	// half cent can't make checkout differ from the advertised price.
	$promo_discount = $promo_percent > 0 ? round($everyday, 2) - wholesale_promotion_price($product_id, round($everyday, 2)) : 0.0;
	$discount += $promo_discount;

	$turnaround = 0.0;
	if (!$is_cl && isset($request['turnaround_option']) && 'same_day' === $request['turnaround_option']) {
		if (!wholesale_same_day_available()) {
			return $fail('Same-day production is only available for orders placed before 12pm PST. Please choose Next Day.');
		}
		$turnaround = $subtotal;
	}

	return array(
		'ok' => '' === $size_error,
		'error' => $size_error,
		// True when only the size is outside the product's limits (the prices are still filled in).
		'size_error' => '' !== $size_error,
		'unit_price' => round($running, 2),
		'quantity' => $quantity,
		'subtotal' => round($subtotal, 2),
		'discount_percent' => $discount_percent,
		'discount' => round($discount, 2),
		// The price before any dated sale, and the sale's share of the discount.
		'everyday' => round($everyday, 2),
		'promo_percent' => $promo_percent,
		'promo_discount' => round($promo_discount, 2),
		'turnaround' => round($turnaround, 2),
		'total' => round($subtotal - $discount + $turnaround, 2),
		// The minimum order price when it replaced the calculator price, else 0.
		'minimum_order' => $minimum_order,
		'lines' => array_map(static function ($line) {
			return array('label' => $line['label'], 'amount' => round($line['amount'], 2));
		}, $lines),
	);
}

/**
 * The saved builder design for a product in this visitor's session, if any.
 */
function wholesale_session_cl_design($product_id)
{
	if (empty($_SESSION['design_data_' . $product_id])) {
		return null;
	}
	$design = json_decode(stripslashes((string) $_SESSION['design_data_' . $product_id]), true);
	return is_array($design) ? $design : null;
}

/**
 * Stores a builder design (its JSON plus the preview image this visitor uploaded) as the
 * product's session design, priced on the server. Used by "Save design" on the product
 * page and by the builder's own Add To Cart.
 *
 * @return array|WP_Error The stored design.
 */
function wholesale_store_session_cl_design($product_id, $design_id, $design_json)
{
	$product_id = absint($product_id);
	$design_id = absint($design_id);
	wholesale_get_cart(); // Loads the Cart class, which AJAX requests don't include.
	if (!Cart::is_session_design($design_id)) {
		return new WP_Error('invalid_design', 'Your design image could not be found. Please try again.');
	}
	$design = json_decode(stripslashes((string) $design_json), true);
	if (!is_array($design) || empty($design['elements'])) {
		return new WP_Error('invalid_design', 'Add letters or a shape to your design first.');
	}

	$old_design = wholesale_session_cl_design($product_id);
	$old_design_id = $old_design && isset($old_design['design_id']) ? absint($old_design['design_id']) : 0;
	if ($old_design_id && $old_design_id !== $design_id && Cart::is_session_design($old_design_id)) {
		// Never delete an image a cart line still shows.
		$in_cart = false;
		foreach (wholesale_get_cart()->get_items() as $item) {
			$in_cart = $in_cart || absint($item->design_id ?? 0) === $old_design_id;
		}
		if (!$in_cart) {
			wp_delete_attachment($old_design_id, true);
		}
	}

	update_post_meta($design_id, '_cl_data', stripslashes((string) $design_json));
	$design['design_url'] = wp_get_attachment_url($design_id);
	$design['design_id'] = $design_id;
	$design['total_cost'] = wholesale_cl_design_total($design, $product_id);
	$design['product_id'] = $product_id;
	$_SESSION['design_data_' . $product_id] = stripslashes(wp_json_encode($design));

	return $design;
}

/**
 * AJAX: live price for the product page, so the price shown is exactly what the cart charges.
 */
function wholesale_ajax_price_quote()
{
	$request = map_deep(wp_unslash($_POST), 'sanitize_text_field');
	$product_id = absint($request['product_id'] ?? 0);
	$design = wholesale_product_is_channel_letter($product_id) ? wholesale_session_cl_design($product_id) : null;
	wp_send_json(wholesale_price_quote($product_id, $request, $design));
}
add_action('wp_ajax_wholesale_price_quote', 'wholesale_ajax_price_quote');
add_action('wp_ajax_nopriv_wholesale_price_quote', 'wholesale_ajax_price_quote');

/**
 * Example total for a channel letter product, from wholesale_price_quote(): the product
 * page's default selections (first face color, no raceway, and the standard power supply
 * the calculator adds once text is entered), for $letters letters at $inches tall.
 *
 * @return array|null array('total' => float, 'discount' => float) or null when it can't be priced.
 */
function wholesale_cl_example_price($slug, $letters, $inches)
{
	$product = get_page_by_path($slug, OBJECT, 'product');
	if (!$product) {
		return null;
	}
	$request = array('letters' => str_repeat('A', max(1, (int) $letters)), 'product_quantity' => 1);
	foreach (wholesale_product_price_attrs($product->ID) as $attr) {
		$options = wholesale_attr_options($attr);
		$name = (string) ($attr['name'] ?? '');
		if (!$options || '' === $name) {
			continue;
		}
		$value = $options[0]['value'];
		if ('height' === $name) {
			$value = null;
			foreach ($options as $option) {
				$value = (int) $option['label'] === (int) $inches ? $option['value'] : $value;
			}
		} elseif ('power-supply' === $name && isset($options[1])) {
			$value = $options[1]['value'];
		}
		if (null === $value) {
			return null;
		}
		$request[$name] = $value;
	}
	$quote = wholesale_price_quote($product->ID, $request);

	return $quote['ok'] ? array('total' => $quote['total'], 'discount' => $quote['discount']) : null;
}

/**
 * Grand Total a fixed-price product (one with the size calculator hidden, like a pole
 * banner set) shows on its product page before the buyer changes anything: the first
 * choice of every option, quantity 1, from wholesale_price_quote(), so it's the price
 * the cart charges. Null for calculator products, or when it can't be priced.
 */
function wholesale_product_default_total($slug)
{
	$product = get_page_by_path($slug, OBJECT, 'product');
	if (!$product || !get_post_meta($product->ID, '_hide_calculator', true)) {
		return null;
	}
	$request = array('product_quantity' => 1);
	foreach (wholesale_product_price_attrs($product->ID) as $attr) {
		$options = wholesale_attr_options($attr);
		if ($options && !empty($attr['name'])) {
			$request[$attr['name']] = $options[0]['value'];
		}
	}
	$quote = wholesale_price_quote($product->ID, $request);

	return $quote['ok'] ? (float) $quote['total'] : null;
}

/**
 * The dated sale from Settings > Storefront Sign that applies to a product right now:
 * array('percent' => float, 'ends' => int), 'ends' being the last second of the sale's
 * end date in Pacific time, or null. Products sell at their everyday price (after their
 * own _discount_percent adjustment, which is never shown as a sale); only this sale shows
 * a badge or a struck-through price, and only between its start and end dates.
 */
function wholesale_product_promotion($product_id)
{
	// A product's own dated sale (_promo_percent/_promo_start/_promo_end) replaces the
	// site-wide one while it runs.
	$own = wholesale_dated_sale_window(get_post_meta($product_id, '_promo_percent', true), get_post_meta($product_id, '_promo_start', true), get_post_meta($product_id, '_promo_end', true));
	if ($own) {
		return $own;
	}
	$window = wholesale_promotion_window();
	if (!$window) {
		return null;
	}
	if ('channel-letters' === $window['scope'] || 'other' === $window['scope']) {
		if (('channel-letters' === $window['scope']) !== wholesale_product_is_channel_letter($product_id)) {
			return null;
		}
	}

	return array('percent' => $window['percent'], 'ends' => $window['ends']);
}

/**
 * The sale settings when today is within the sale's dates, whatever products it covers:
 * array('percent', 'ends', 'scope'), or null.
 */
function wholesale_promotion_window()
{
	static $window = false;
	if (false === $window) {
		$window = wholesale_dated_sale_window(wholesale_get_setting('promo_percent'), wholesale_get_setting('promo_start'), wholesale_get_setting('promo_end'));
		if ($window) {
			$window['scope'] = (string) wholesale_get_setting('promo_scope');
		}
	}

	return $window;
}

/**
 * A sale percent with Y-m-d start and end dates (Pacific): array('percent', 'ends')
 * when today is within them, else null.
 */
function wholesale_dated_sale_window($percent, $start, $end)
{
	$percent = min(90, max(0, (float) $percent));
	$zone = new DateTimeZone('America/Los_Angeles');
	$start = DateTime::createFromFormat('!Y-m-d', trim((string) $start), $zone);
	$end = DateTime::createFromFormat('!Y-m-d', trim((string) $end), $zone);
	if ($percent <= 0 || !$start || !$end) {
		return null;
	}
	$ends = $end->modify('+1 day')->getTimestamp() - 1;

	return time() >= $start->getTimestamp() && time() <= $ends ? array('percent' => $percent, 'ends' => $ends) : null;
}

function wholesale_product_promotion_percent($product_id)
{
	$promotion = wholesale_product_promotion($product_id);
	return $promotion ? (float) $promotion['percent'] : 0.0;
}

/**
 * An everyday price after the dated sale, or the price unchanged when no sale is running.
 */
function wholesale_promotion_price($product_id, $everyday_price)
{
	$percent = wholesale_product_promotion_percent($product_id);
	return $percent > 0 ? round($everyday_price * (1 - $percent / 100), 2) : $everyday_price;
}

/**
 * "Oct 31": the last day of a sale, for "ends ..." labels.
 */
function wholesale_promotion_end_text($promotion)
{
	return wp_date('M j', $promotion['ends'], new DateTimeZone('America/Los_Angeles'));
}

/**
 * Lowest price of one channel letter: the cheapest letter height on the product
 * page, with no paid options: before the product discount ('regular'), after it
 * ('everyday') and after any dated sale too ('price'). 'price' is what
 * wholesale_price_quote() charges for that letter, so a "from" price built on it is
 * always one a buyer can actually pay.
 *
 * @return array|null array('regular' => float, 'everyday' => float, 'price' => float, 'height' => int) or null.
 */
function wholesale_cl_from_price($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (array_key_exists($product_id, $cache)) {
		return $cache[$product_id];
	}

	$cache[$product_id] = null;
	foreach (wholesale_product_price_attrs($product_id) as $attr) {
		if ('height' !== ($attr['name'] ?? '')) {
			continue;
		}
		foreach (wholesale_attr_options($attr) as $option) {
			$value = (float) $option['value'];
			if ($value > 0 && (null === $cache[$product_id] || $value < $cache[$product_id]['regular'])) {
				$cache[$product_id] = array('regular' => $value, 'price' => 0.0, 'height' => (int) $option['label']);
			}
		}
	}
	if ($cache[$product_id]) {
		$discount = min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)));
		$everyday = $cache[$product_id]['regular'] * (1 - $discount / 100);
		$promo_percent = wholesale_product_promotion_percent($product_id);
		$cache[$product_id]['everyday'] = round($everyday, 2);
		$cache[$product_id]['price'] = round($promo_percent > 0 ? $everyday * (1 - $promo_percent / 100) : $everyday, 2);
	}

	return $cache[$product_id];
}

/**
 * "Starting at" text for a channel letter, from its real per-letter price:
 * "$133.20 per letter", or "<del>$133.20</del> $119.88 per letter" during a dated
 * sale. Replaces the hand-typed per-inch text, which quoted prices no letter height
 * actually reached.
 */
function wholesale_cl_starting_text($product_id)
{
	$from = wholesale_cl_from_price($product_id);
	if (!$from) {
		return '';
	}
	$money = static function ($amount) {
		return '$' . (floor($amount) == $amount ? number_format($amount) : number_format($amount, 2));
	};
	$now = $money($from['price']) . ' per letter';

	return $from['price'] < $from['everyday'] ? '<del>' . $money($from['everyday']) . ' </del><span style="color: green">' . $now . '</span>' : $now;
}

/**
 * One worked example under the "from" price: 6 front lit letters at 12 inches with
 * the standard power supply (wholesale_cl_example_price()), plus the cheapest
 * channel letter shipping rate the checkout charges. Empty when it can't be priced.
 */
function wholesale_cl_worked_example_text()
{
	$example = wholesale_cl_example_price('standard-channel-letter-front-lit', 6, 12);
	if (!$example) {
		return '';
	}
	$rates = array_values(array_filter(explode(',', (string) wholesale_get_setting('channel_shipping_options')), 'is_numeric'));
	$shipping = $rates ? (float) min($rates) : 0;

	return sprintf('Example: 6 front lit letters, 12&Prime; tall, with power supply: $%s', number_format($example['total'], 2))
		. ($shipping > 0 ? sprintf(' + $%s shipping', floor($shipping) == $shipping ? number_format($shipping) : number_format($shipping, 2)) : '');
}

/**
 * What a channel letter "starting at" price covers, read from the front lit product's
 * real settings: the smallest letter height, whether any face color adds a per-inch
 * charge, and the dated sale behind a struck-through price. Sentences only, no markup.
 */
function wholesale_cl_starting_price_note()
{
	$product = get_page_by_path('standard-channel-letter-front-lit', OBJECT, 'product');
	$from = $product ? wholesale_cl_from_price($product->ID) : null;
	if (!$from) {
		return '';
	}

	$note = sprintf('Starting prices are for one %d&Prime; letter, the smallest height, before options. Channel letters are priced per letter, not per inch: each letter height has its own price, and your total is that price times the number of letters, plus options such as a raceway or power supply.', $from['height']);

	$cl_data = json_decode((string) get_post_meta($product->ID, 'product_cl_data', true), true);
	foreach (is_array($cl_data) ? $cl_data : array() as $group) {
		if (isset($group['id']) && 0 === strpos((string) $group['id'], 'color-') && (float) ($group['cost'] ?? 0) > 0) {
			$note .= ' On some styles, vinyl face colors add a small charge per inch of letter height, included in the builder price.';
			break;
		}
	}

	$promotion = wholesale_product_promotion($product->ID);
	if ($promotion && $from['price'] < $from['everyday']) {
		$note .= sprintf(' Struck-through prices are everyday prices; the lower price is our current sale, which ends %s.', wholesale_promotion_end_text($promotion));
	}

	return $note;
}

/**
 * A stored "starting at" text as shoppers see it. Any struck-through <del> price is
 * dropped, because product prices are everyday prices, not sales. During a dated sale
 * the price is struck through and the sale price follows it.
 */
function wholesale_starting_text_for_sale($product_id, $text)
{
	$text = trim(preg_replace('/<del\b[^>]*>.*?<\/del>\s*/is', '', (string) $text));
	if (wholesale_product_promotion_percent($product_id) <= 0 || !preg_match_all('/\$\s?([\d,]+(?:\.\d+)?)/', $text, $prices, PREG_OFFSET_CAPTURE)) {
		return $text;
	}
	$last = end($prices[0]);
	$sale = wholesale_promotion_price($product_id, (float) str_replace(',', '', end($prices[1])[0]));

	return substr($text, 0, $last[1]) . '<del>' . $last[0] . '</del> $' . number_format($sale, 2) . substr($text, $last[1] + strlen($last[0]));
}

/**
 * On the storefront, every reader of a product's _starting_at_text (cards, category
 * pages, comparison tables, the builder's product switcher) gets the text shoppers
 * should see: the computed per-letter text above for channel letters, the stored text
 * through wholesale_starting_text_for_sale() for everything else. The admin editor
 * still shows and saves the stored value.
 */
add_filter('get_post_metadata', function ($value, $object_id, $meta_key) {
	static $reading = false;
	if ($reading || '_starting_at_text' !== $meta_key || null !== $value || (is_admin() && !wp_doing_ajax()) || 'product' !== get_post_type($object_id)) {
		return $value;
	}
	$text = wholesale_product_is_channel_letter($object_id) ? wholesale_cl_starting_text($object_id) : '';
	if ('' === $text) {
		$reading = true;
		$stored = (string) get_post_meta($object_id, '_starting_at_text', true);
		$reading = false;
		// Size calculator products: always the per sq ft rate the cart charges (after the
		// product's own discount), so the text can't fall behind a rate change.
		$rate = (float) get_post_meta($object_id, '_price_per_sqft', true);
		if ($rate > 0 && !get_post_meta($object_id, '_hide_calculator', true) && ('' === $stored || preg_match('/per\s*(ft|sq)/i', $stored))) {
			$discount = min(100, max(0, (float) get_post_meta($object_id, '_discount_percent', true)));
			$stored = '$' . number_format(round($rate * (100 - $discount) / 100, 2), 2) . ' per ft<sup>2</sup>';
		}
		if ('' === $stored) {
			return $value;
		}
		$text = wholesale_starting_text_for_sale($object_id, $stored);
		// Stored text varies ("$539per item", "Per Item"); show one form.
		$text = preg_replace(array('/(\$[\d,]+(?:\.\d+)?)(?=per\b)/i', '/\bper\s+item\b/i'), array('$1 ', 'per item'), $text);
	}

	return array($text);
}, 10, 3);

/**
 * Whole-number percentage for a product's sale badge: the dated sale running now,
 * rounded down so the badge never overstates it, or 0.
 */
function wholesale_product_sale_percent($product_id)
{
	return (int) floor(min(99, wholesale_product_promotion_percent($product_id)));
}

/**
 * Shopper price for one of a product's option amounts: the everyday price (after the
 * product discount), struck through with the sale price after it during a dated sale.
 */
function wholesale_price_with_sale($product_id, $regular_price)
{
	$regular_price = round((float) $regular_price, 2);
	$discount = min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)));
	$everyday = round($regular_price * (1 - ($discount / 100)), 2);
	$sale_price = wholesale_promotion_price($product_id, $everyday);
	$format = static function ($amount) {
		return '$' . number_format($amount, 2, '.', ',');
	};

	if ($sale_price >= $everyday) {
		return esc_html($format($everyday));
	}

	return '<del class="product-price-regular">' . esc_html($format($everyday)) . '</del> <span class="product-price-sale">' . esc_html($format($sale_price)) . '</span>';
}

/**
 * Product image badge. Out of stock takes priority over any dated sale.
 */
function wholesale_render_sale_badge($product_id, $percent = null)
{
	if ('outofstock' === wholesale_product_stock_status($product_id)) {
		echo '<span class="sale-badge sale-badge--out-of-stock"><span class="sale-badge__label">' . esc_html__('Out of stock', 'litsign') . '</span></span>';
		return;
	}

	$percent = null === $percent ? wholesale_product_sale_percent($product_id) : (int) $percent;
	$promotion = wholesale_product_promotion($product_id);
	if ($percent < 1 || !$promotion) {
		return;
	}
	?>
	<span class="sale-badge" role="img" aria-label="<?php echo esc_attr(sprintf(__('On sale: save %1$d%% through %2$s', 'litsign'), $percent, wholesale_promotion_end_text($promotion))); ?>">
		<span class="sale-badge__label" aria-hidden="true"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg><?php esc_html_e('Sale', 'litsign'); ?></span>
		<span class="sale-badge__value" aria-hidden="true"><?php echo esc_html(sprintf(__('%d%% off', 'litsign'), $percent)); ?></span>
	</span>
	<?php
}
