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
 *  - Quantity multiplies everything; the product discount applies after quantity; same-day
 *    turnaround adds 100% of the undiscounted price.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_RACEWAY_COST_PER_FOOT = 50;
const WHOLESALE_CL_MIN_INCHES = 8;
const WHOLESALE_CL_MAX_INCHES = 45;

function wholesale_product_is_channel_letter($product_id)
{
	$terms = get_the_terms($product_id, 'product_category');
	return is_array($terms) && isset($terms[0]) && 'channel-letters' === $terms[0]->slug;
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
		'type' => in_array($type, array('%', 'sqft', 'lft'), true) ? $type : 'fixed',
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
			$limits = array(
				'height' => array((float) get_post_meta($product_id, '_min_height', true), (float) get_post_meta($product_id, '_max_height', true), $height_ft),
				'width' => array((float) get_post_meta($product_id, '_min_width', true), (float) get_post_meta($product_id, '_max_width', true), $width_ft),
			);
			foreach ($limits as $label => $limit) {
				if ($limit[2] <= 0) {
					return $fail(sprintf('Please enter the %s of your sign.', $label));
				}
				if ($limit[0] > 0 && $limit[2] + 0.0001 < $limit[0]) {
					return $fail(sprintf('The minimum %s is %s ft.', $label, rtrim(rtrim(number_format($limit[0], 2), '0'), '.')));
				}
				if ($limit[1] > 0 && $limit[2] - 0.0001 > $limit[1]) {
					return $fail(sprintf('The maximum %s is %s ft.', $label, rtrim(rtrim(number_format($limit[1], 2), '0'), '.')));
				}
			}
			$sqft = $height_ft * $width_ft;
			if ($min_sqft > 0 && $sqft + 0.0001 < $min_sqft) {
				return $fail(sprintf('The minimum size for this product is %s sq ft.', rtrim(rtrim(number_format($min_sqft, 2), '0'), '.')));
			}
		}

		$running = $sqft * $price_per_sqft;
		$lines[] = array('label' => $has_calculator || null !== $width_attr_value ? sprintf('%s sq ft', number_format($sqft, 2)) : 'Base price', 'amount' => $running);
	}

	foreach ($attrs as $attr) {
		$options = wholesale_attr_options($attr);
		$css = (string) ($attr['cssClass'] ?? '');
		// Single-option attributes are shown as fixed text, and avoid-price ones are handled above.
		if (count($options) < 2 || false !== strpos($css, 'avoid-price') || 'height' === ($attr['name'] ?? '') && $is_cl) {
			continue;
		}

		$submitted = wholesale_request_attr_value($request, $attr['name']);
		$chosen = null;
		foreach ($options as $option) {
			if (null !== $submitted && (trim($option['value']) === trim($submitted) || sanitize_text_field($option['value']) === trim($submitted))) {
				$chosen = $option;
				break;
			}
		}
		if (null === $chosen) {
			return $fail(sprintf('Please choose a valid %s.', str_replace('-', ' ', strtolower($attr['name']))));
		}

		$price = wholesale_parse_option_price($chosen['value']);
		switch ($price['type']) {
			case '%':
				$amount = $running * $price['amount'] / 100;
				break;
			case 'sqft':
				$amount = max($min_sqft, $sqft) * $price['amount'];
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

	if ($running <= 0) {
		return $fail('Please choose your product options so we can calculate a price.');
	}

	$quantity = min(1000, max(1, absint($request['product_quantity'] ?? 1)));
	$subtotal = $running * $quantity;
	$discount_percent = min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)));
	$discount = $subtotal * $discount_percent / 100;

	$turnaround = 0.0;
	if (!$is_cl && isset($request['turnaround_option']) && 'same_day' === $request['turnaround_option']) {
		if (!wholesale_same_day_available()) {
			return $fail('Same-day production is only available for orders placed before 12pm PST. Please choose Next Day.');
		}
		$turnaround = $subtotal;
	}

	return array(
		'ok' => true,
		'error' => '',
		'unit_price' => round($running, 2),
		'quantity' => $quantity,
		'subtotal' => round($subtotal, 2),
		'discount_percent' => $discount_percent,
		'discount' => round($discount, 2),
		'turnaround' => round($turnaround, 2),
		'total' => round($subtotal - $discount + $turnaround, 2),
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
