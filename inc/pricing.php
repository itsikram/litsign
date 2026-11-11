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

// Builder raceway price per foot; matches the product page raceway option ("55/lft").
const WHOLESALE_RACEWAY_COST_PER_FOOT = 55;
const WHOLESALE_CL_MIN_INCHES = 8;
const WHOLESALE_CL_MAX_INCHES = 45;
// Channel letters can carry B2Sign's per-letter option prices: Lexan face (_cl_lexan_percent),
// 1" trimcap (_cl_trimcap_1in) and per-inch faces ("N/clin/..."). SFS B2Sign Sync only writes
// them to a theme that defines this.
const WHOLESALE_CL_OPTION_PRICING = 1;
// Channel letters can carry B2Sign's piece rules (_cl_piece_pricing, see wholesale_cl_option_prices()):
// large letters by sq ft and small lit letters. SFS B2Sign Sync only writes them to a theme that defines this.
const WHOLESALE_CL_PIECE_PRICING = 1;
// Default lowest per-item price for products sized with the sq ft dimensions calculator.
// A product can set its own with _min_order_price (see wholesale_product_min_order()).
const WHOLESALE_SQFT_MIN_PRICE = 16.5;

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
 * Per linear foot of the typed size, for B2Sign-style finishing (SFS B2Sign Sync mirrors these):
 * "2.5/perim/..." all four edges, "1.2/wlft/..." the width, "1.2/hlft/..." the height.
 * Channel letters: "0.88/clin/..." per inch of letter height, per letter (B2Sign's vinyl faces).
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
		'type' => in_array($type, array('%', '%total', 'sqft', 'lft', 'perim', 'wlft', 'hlft', 'clin'), true) ? $type : 'fixed',
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

/**
 * B2Sign's price for dropdown sizes (meta _size_dropdown, written by SFS B2Sign Sync 1.7.0), or null.
 * {"w":{"18":[add-on, minimum],...},"label":"Hardware","extra":{"attr":name,"value":option value,"min":amount}}
 * Per width: a fixed add-on (stored, like option prices: pole banner hardware $38.50 / $44 / $46.20 at B2Sign
 * cost for 18" / 24" / 30") and the lowest price of the item (after the product's discount, like
 * _min_order_price; 0 = none). "extra" raises the minimum when that option is chosen (two-sided).
 */
function wholesale_size_dropdown_pricing($product_id)
{
	$data = json_decode((string) get_post_meta($product_id, '_size_dropdown', true), true);
	if (!is_array($data) || !isset($data['w']) || !is_array($data['w']) || !$data['w']) {
		return null;
	}
	$widths = array();
	foreach ($data['w'] as $width => $entry) {
		if (!is_numeric($width) || !is_array($entry) || !isset($entry[0], $entry[1]) || !is_numeric($entry[0]) || !is_numeric($entry[1]) || $entry[1] < 0) {
			return null;
		}
		$widths[(string) (0 + (float) $width)] = array((float) $entry[0], (float) $entry[1]);
	}
	$extra = null;
	if (isset($data['extra']['attr'], $data['extra']['value'], $data['extra']['min']) && is_numeric($data['extra']['min'])) {
		$extra = array('attr' => (string) $data['extra']['attr'], 'value' => (string) $data['extra']['value'], 'min' => max(0, (float) $data['extra']['min']));
	}
	$label = isset($data['label']) && is_string($data['label']) && '' !== trim($data['label']) ? sanitize_text_field($data['label']) : 'Hardware';
	return array('w' => $widths, 'label' => $label, 'extra' => $extra);
}

/**
 * B2Sign quantity pricing for a product (meta _qty_pricing, written by SFS B2Sign Sync), or null.
 * {"min":[[from qty, lowest shown price per item], ...], "sheet":{"w":48,"h":96,"price":stored price per sheet}}
 */
function wholesale_product_qty_pricing($product_id)
{
	$data = json_decode((string) get_post_meta($product_id, '_qty_pricing', true), true);
	if (!is_array($data) || ((!isset($data['min']) || !is_array($data['min'])) && (!isset($data['unit']) || !is_array($data['unit'])))) {
		return null;
	}
	$data += array('min' => array());
	$min = array();
	foreach ($data['min'] as $tier) {
		if (is_array($tier) && isset($tier[0], $tier[1]) && is_numeric($tier[0]) && is_numeric($tier[1])) {
			$min[] = array(max(1, (int) $tier[0]), max(0, (float) $tier[1]));
		}
	}
	usort($min, static function ($a, $b) { return $a[0] <=> $b[0]; });
	// Fixed-price products: "unit" = [[from qty, shown price per item with the default options]] (B2Sign's own
	// table, e.g. a yard sign $9.04, $7.02 each for 2+). "sheet.extra_side" = stored price added to the sheet for
	// each extra printed side (B2Sign coroplast: $33.99 two-sided against $29.99).
	$unit = array();
	foreach (isset($data['unit']) && is_array($data['unit']) ? $data['unit'] : array() as $tier) {
		if (is_array($tier) && isset($tier[0], $tier[1]) && is_numeric($tier[0]) && is_numeric($tier[1])) {
			$unit[] = array(max(1, (int) $tier[0]), max(0, (float) $tier[1]));
		}
	}
	usort($unit, static function ($a, $b) { return $a[0] <=> $b[0]; });
	$sheet = isset($data['sheet']['w'], $data['sheet']['h'], $data['sheet']['price']) && $data['sheet']['price'] > 0 ? array('w' => (float) $data['sheet']['w'], 'h' => (float) $data['sheet']['h'], 'price' => (float) $data['sheet']['price'], 'extra_side' => max(0, (float) ($data['sheet']['extra_side'] ?? 0))) : null;
	// "opt" (SFS B2Sign Sync 1.7.0): [[attribute name, option value, [[from qty, shown change per item]]]], how much
	// an option's own difference moves with the quantity (yard sign Single Sided: -$1.00 at 1, -$0.42 each for 2-5).
	$opt = array();
	foreach (isset($data['opt']) && is_array($data['opt']) ? $data['opt'] : array() as $o) {
		if (!is_array($o) || !isset($o[0], $o[1], $o[2]) || !is_string($o[0]) || !is_string($o[1]) || !is_array($o[2])) {
			continue;
		}
		$tiers = array();
		foreach ($o[2] as $tier) {
			if (is_array($tier) && isset($tier[0], $tier[1]) && is_numeric($tier[0]) && is_numeric($tier[1])) {
				$tiers[] = array(max(1, (int) $tier[0]), (float) $tier[1]);
			}
		}
		usort($tiers, static function ($a, $b) { return $a[0] <=> $b[0]; });
		if ($tiers) {
			$opt[] = array($o[0], $o[1], $tiers);
		}
	}
	return $min || $sheet || $unit ? array('min' => $min, 'sheet' => $sheet, 'unit' => $unit, 'opt' => $opt) : null;
}

/** Pieces of w x h inches that fit on a W x H inch sheet, either way round. */
function wholesale_pieces_per_sheet($w, $h, $sheet_w, $sheet_h)
{
	if ($w <= 0 || $h <= 0) {
		return 0;
	}
	return (int) max(floor($sheet_w / $w) * floor($sheet_h / $h), floor($sheet_w / $h) * floor($sheet_h / $w));
}

/**
 * A product's own production-time choices (meta _turnaround_spec, written by SFS B2Sign Sync), or array().
 * Each: value, label, min_qty, max_qty (null = no limit), cutoff ("16:00" Pacific), days, percent, automatic.
 */
function wholesale_product_turnaround_spec($product_id)
{
	$data = json_decode((string) get_post_meta($product_id, '_turnaround_spec', true), true);
	$out = array();
	$clock = static function ($v) {
		return preg_match('/^\d\d:\d\d$/', (string) $v) ? (string) $v : '';
	};
	foreach (is_array($data) ? $data : array() as $o) {
		if (!is_array($o) || empty($o['value']) || !preg_match('/^[a-z0-9_]+$/', (string) $o['value'])) {
			continue;
		}
		$days = max(0, (int) ($o['days'] ?? 1));
		$cutoff = $clock($o['cutoff'] ?? '');
		// B2Sign Sync 1.9.1 adds B2Sign's exact rules (choice, section, cut-off shown, time gate, size limit, default).
		// Older data: Same Day closes at its cut-off on weekdays, and the automatic Free Same Day is no choice.
		$exact = array_key_exists('choice', $o);
		$out[] = array(
			'value' => (string) $o['value'],
			'label' => sanitize_text_field((string) ($o['label'] ?? $o['value'])),
			'min_qty' => max(1, (int) ($o['min_qty'] ?? 1)),
			'max_qty' => isset($o['max_qty']) && null !== $o['max_qty'] ? (int) $o['max_qty'] : null,
			'cutoff' => $cutoff,
			'days' => $days,
			'percent' => max(0, (float) ($o['percent'] ?? 0)),
			'automatic' => !empty($o['automatic']),
			'choice' => $exact ? !empty($o['choice']) : empty($o['automatic']),
			'section' => sanitize_text_field((string) ($o['section'] ?? (0 === $days ? 'Same Day' : ('next_day' === $o['value'] ? 'Next Day' : ($o['label'] ?? ''))))),
			'cutoff_label' => $clock($o['cutoff_label'] ?? '') ?: $cutoff,
			'time_gate' => $exact ? $clock($o['time_gate'] ?? '') : (0 === $days ? $cutoff : ''),
			'open_off_days' => !empty($o['open_off_days']),
			'reopen_at' => $clock($o['reopen_at'] ?? ''),
			'max_both_sides' => isset($o['max_both_sides']) && is_numeric($o['max_both_sides']) ? (float) $o['max_both_sides'] : null,
			'default' => !empty($o['default']),
		);
	}
	return $out;
}

/**
 * True when B2Sign Sync mirrored that B2Sign offers no production-time choice for this product (meta "[]",
 * hardware): the page then shows none, like B2Sign, instead of the site's Next Day / Same Day.
 */
function wholesale_product_turnaround_none($product_id)
{
	return '[]' === trim((string) get_post_meta($product_id, '_turnaround_spec', true));
}

/**
 * Is a production-time choice open now (Pacific)? Like B2Sign: a choice with a time gate is open on business days
 * before it (and again from reopen_at), and all day on days off when B2Sign says so. Holidays are not known here.
 */
function wholesale_turnaround_open_now($o)
{
	if ('' === $o['time_gate']) {
		return true;
	}
	$now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
	if ((int) $now->format('N') >= 6) {
		return $o['open_off_days'];
	}
	$t = $now->format('H:i');
	return $t < $o['time_gate'] || ('' !== $o['reopen_at'] && $t >= $o['reopen_at']);
}

/** True on a weekday before the Pacific time "HH:MM". */
function wholesale_before_pacific_time($cutoff)
{
	$now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
	return (int) $now->format('N') < 6 && $now->format('H:i') < $cutoff;
}

/**
 * The chosen production time for a quantity, or an error message. No choice sent: B2Sign's default
 * (Next Day) when open, else the first choice open now.
 * $size: array(width_in, height_in) when the product has a size box. B2Sign closes Same Day when both
 * sides are over 120" (max_both_sides).
 */
function wholesale_turnaround_choice($spec, $value, $quantity, $size = null)
{
	$open = array();
	foreach ($spec as $o) {
		if (!$o['choice'] || $quantity < $o['min_qty'] || (null !== $o['max_qty'] && $quantity > $o['max_qty'])) {
			continue;
		}
		if (null !== $o['max_both_sides'] && is_array($size) && min($size) > 0 && min($size) > $o['max_both_sides']) {
			continue;
		}
		$open[$o['value']] = $o;
	}
	if (!$open) {
		return 'No production time is available for this quantity. Please contact us.';
	}
	// Suggest only choices open right now (a greyed-out Free Same Day is no help).
	$now_open = array_filter($open, 'wholesale_turnaround_open_now');
	$labels = implode(' or ', wp_list_pluck(array_values($now_open ?: $open), 'label'));
	if ('' === $value) {
		// B2Sign's own default (Next Day) when it is open, else the first choice open now.
		foreach ($open as $o) {
			if ($o['default'] && wholesale_turnaround_open_now($o)) {
				return $o;
			}
		}
		foreach ($open as $o) {
			if (wholesale_turnaround_open_now($o)) {
				return $o;
			}
		}
		return reset($open);
	}
	if (!isset($open[$value])) {
		return sprintf('That production time is not available for this order. Please choose %s.', $labels);
	}
	$o = $open[$value];
	if (!wholesale_turnaround_open_now($o)) {
		return sprintf('%s is only available on business days before %s PST. Please choose another production time.', $o['label'], date('g:ia', strtotime($o['time_gate'])));
	}
	return $o;
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
	// Several groups share a name (acrylic "Yellow" and 3M vinyl "Yellow"), so the color code
	// picks the group; the name alone is the fallback for designs saved without a code.
	$code = strtolower(preg_replace('/\s+/', '', (string) ($face_color['code'] ?? '')));
	$by_title = null;
	foreach ($cl_data as $group) {
		if (!isset($group['id']) || 0 !== strpos((string) $group['id'], 'color-')) {
			continue;
		}
		foreach (wholesale_attr_options($group) as $option) {
			if (trim($option['label']) !== trim((string) $face_color['title'])) {
				continue;
			}
			if ('' !== $code && strtolower(preg_replace('/\s+/', '', (string) $option['value'])) === $code) {
				return (float) ($group['cost'] ?? 0);
			}
			if (null === $by_title) {
				$by_title = (float) ($group['cost'] ?? 0);
			}
		}
	}
	return $by_title;
}

/**
 * A channel letter product's per-letter option prices and piece rules (written by SFS B2Sign Sync,
 * B2Sign's rules at our markup, stored before the product discount like the rate table):
 * - lexan_percent: Lexan face as a percent of the letter price;
 * - trimcap_1in: the 1" trimcap add-on per piece by size ([[up to inches, dollars], ...]; nothing
 *   above the last size, as on B2Sign);
 * - from meta _cl_piece_pricing (0 when the product has no such rule): a piece over large_sqft sq ft
 *   costs per_sqft per sq ft of its width x height (Lexan White lexan_large percent more) when it is
 *   larger than a letter width_share as wide as it is tall (the rate table already holds that one's
 *   price), a piece longer than the rate table at least per_inch per inch, and a piece under under_in
 *   inches under_price (B2Sign's lit letters).
 * The builder reads the same array (cl_builderr.php).
 */
function wholesale_cl_option_prices($product_id)
{
	$trimcap = json_decode((string) get_post_meta($product_id, '_cl_trimcap_1in', true), true);
	$piece = json_decode((string) get_post_meta($product_id, '_cl_piece_pricing', true), true);
	$prices = array(
		'lexan_percent' => max(0.0, (float) get_post_meta($product_id, '_cl_lexan_percent', true)),
		'trimcap_1in' => is_array($trimcap) ? $trimcap : array(),
	);
	foreach (array('large_sqft', 'per_sqft', 'width_share', 'lexan_large', 'per_inch', 'under_in', 'under_price') as $key) {
		$prices[$key] = is_array($piece) && isset($piece[$key]) && is_numeric($piece[$key]) ? max(0.0, (float) $piece[$key]) : 0.0;
	}
	return $prices;
}

/**
 * One letter of a builder design (a text character, or a whole shape) priced as B2Sign prices its
 * pieces, before the product discount: array('letter' => all its pieces, 'lexan' => what Lexan White
 * adds, 'trimcap' => what the 1" trimcap adds). $letter: char ('' for a shape), side (the side the
 * rate table prices), raw (its measured longer side), w and h (its measured size), in inches.
 * Same as getLetterCosts() in js/cl.js.
 */
function wholesale_cl_letter_cost($cost_table, $options, $letter, $element)
{
	$pieces = '' === (string) $letter['char'] ? 1 : wholesale_cl_char_pieces($letter['char']);
	$rate = (float) wholesale_cl_rate_for_side($cost_table, $letter['side']);
	// Longer than the rate table: at least B2Sign's price per inch.
	$inches = (int) ceil(round((float) $letter['side'], 1) - 0.000001);
	if ($options['per_inch'] > 0 && $cost_table && $inches > max(array_keys($cost_table))) {
		$rate = max($rate, $options['per_inch'] * $inches);
	}
	// B2Sign's lit letters: a piece under 6" costs more than its 6"-9" prices. The pieces of a
	// two-piece character (the dot of an i, an accent) are small, so they all count as under it.
	if ($options['under_price'] > 0 && ($pieces > 1 || round((float) $letter['raw'], 1) < $options['under_in'])) {
		$rate = max($rate, $options['under_price']);
	}
	// B2Sign's standard letter over 10 sq ft: a price per sq ft of its real width x height, when it is
	// larger than the letter the rate table holds at that height (width_share as wide as it is tall).
	$large = 0.0;
	if ($options['per_sqft'] > 0 && $cost_table) {
		$sqft = round(round((float) $letter['w'], 1) * round((float) $letter['h'], 1) / 144, 1);
		$table_inches = max(min(array_keys($cost_table)), min(max(array_keys($cost_table)), $inches));
		$table_sqft = round(round($options['width_share'] * $table_inches, 1) * $table_inches / 144, 1);
		if ($sqft > $options['large_sqft'] && $sqft > $table_sqft) {
			$large = $options['per_sqft'] * $sqft;
		}
	}
	$main = max($rate, $large);
	$cost = array('letter' => $main + $rate * ($pieces - 1), 'lexan' => 0.0, 'trimcap' => 0.0);
	$face = isset($element['faceColor']['title']) ? (string) $element['faceColor']['title'] : '';
	if ($options['lexan_percent'] > 0 && false !== stripos($face, 'lexan')) {
		$cost['lexan'] = max($rate * (1 + $options['lexan_percent'] / 100), $large * (1 + $options['lexan_large'] / 100)) - $main
			+ $rate * ($pieces - 1) * $options['lexan_percent'] / 100;
	}
	$trimcap = isset($element['trimcapSize']['title']) ? trim((string) $element['trimcapSize']['title']) : '';
	if ($options['trimcap_1in'] && preg_match('/^1\s*inch/i', $trimcap)) {
		// On every piece by its own size, as B2Sign; the pieces of a two-piece character are small,
		// so they take the smallest size's price.
		$side = $pieces > 1 ? 0.0 : round((float) $letter['raw'], 1);
		foreach ($options['trimcap_1in'] as $tier) {
			if (is_array($tier) && count($tier) >= 2 && $side <= (float) $tier[0]) {
				$cost['trimcap'] = (float) $tier[1] * $pieces;
				break;
			}
		}
	}
	return $cost;
}

function wholesale_cl_rate_for_size($cost_table, $inches)
{
	$size = (int) floor((float) $inches);
	return isset($cost_table[$size]) ? $cost_table[$size] : null;
}

/**
 * The rate for one letter by its longer side, as B2Sign prices it: rounded up to the next inch,
 * within the product's table (a small lowercase letter pays the smallest height's price).
 */
function wholesale_cl_rate_for_side($cost_table, $side)
{
	if (!$cost_table) {
		return null;
	}
	$keys = array_keys($cost_table);
	$inches = (int) ceil(round((float) $side, 1) - 0.000001);
	$inches = max(min($keys), min(max($keys), $inches));
	return isset($cost_table[$inches]) ? $cost_table[$inches] : null;
}

/** Separate pieces in one character, which B2Sign prices one by one: the dot of an i, an accent. */
function wholesale_cl_char_pieces($char)
{
	if (false !== strpos('ij!?:;="%', $char)) {
		return 2;
	}
	// Accented letters (É, ñ, ü): the accent is its own piece. Same list as js/cl.js.
	return preg_match('/^[\x{C0}-\x{C5}\x{C7}-\x{CF}\x{D1}-\x{D6}\x{D9}-\x{DD}\x{E0}-\x{E5}\x{E7}-\x{EF}\x{F1}-\x{F6}\x{F9}-\x{FD}\x{FF}]$/u', $char) ? 2 : 1;
}

/**
 * Each letter of a builder text element in inches, from the sizes the builder measured (element
 * "glyphs": [[width, height], ...] per non-space character), or null when they are missing or don't
 * fit the text: its width w and height h, its longer side raw, and the side the rate table prices.
 * Sizes are kept up to 2.5 times the text height, and the priced side at least half of it, so a
 * tampered design can't price letters far below their real size. Same as getTextLetters() in js/cl.js.
 */
function wholesale_cl_text_letters($element, $height)
{
	$chars = preg_split('//u', preg_replace('/\s+/u', '', (string) ($element['text'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
	$glyphs = isset($element['glyphs']) && is_array($element['glyphs']) ? array_values($element['glyphs']) : array();
	if (!$chars || count($glyphs) !== count($chars) || $height <= 0) {
		return null;
	}
	$letters = array();
	foreach ($chars as $i => $char) {
		$g = $glyphs[$i];
		if (!is_array($g) || !isset($g[0], $g[1]) || !is_numeric($g[0]) || !is_numeric($g[1]) || !is_finite((float) $g[0]) || !is_finite((float) $g[1])) {
			return null;
		}
		$w = min(max(0.0, (float) $g[0]), 2.5 * $height);
		$h = min(max(0.0, (float) $g[1]), 2.5 * $height);
		$letters[] = array('char' => $char, 'side' => max(max($w, $h), 0.5 * $height), 'raw' => max($w, $h), 'w' => $w, 'h' => $h);
	}
	return $letters;
}

/**
 * The letters of a builder text element without measured sizes (no canvas, or a design saved before
 * the builder measured letters): each real character at the text height, 80% as wide as it is tall
 * (the letter the rate table is priced for). Same as getTextLetters() in js/cl.js.
 */
function wholesale_cl_text_letters_unmeasured($element, $height)
{
	$letters = array();
	foreach (preg_split('//u', (string) preg_replace('/\s+/u', '', (string) ($element['text'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: array() as $char) {
		$letters[] = array('char' => $char, 'side' => $height, 'raw' => $height, 'w' => 0.8 * $height, 'h' => $height);
	}
	return $letters;
}

/** Pieces in a channel letter text, as the builder and B2Sign count them (wholesale_cl_char_pieces()). */
function wholesale_cl_count_pieces($text)
{
	$pieces = 0;
	foreach (preg_split('//u', (string) preg_replace('/\s+/u', '', (string) $text), -1, PREG_SPLIT_NO_EMPTY) ?: array() as $char) {
		$pieces += wholesale_cl_char_pieces($char);
	}
	return $pieces;
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

	$option_prices = wholesale_cl_option_prices($product_id);
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
				return new WP_Error('bad_size', sprintf('Letter height must be between %d" and %d". Please resize your letters in the builder.', min(array_keys($cost_table)), max(array_keys($cost_table))));
			}
			// Like B2Sign: every letter by its own size (wholesale_cl_letter_cost()), and every separate
			// piece (the dot of an i) priced on its own. Without measured letters, all take the text height.
			$letters = wholesale_cl_text_letters($element, $height);
			if (!$letters) {
				$letters = wholesale_cl_text_letters_unmeasured($element, $height);
			}
		} else {
			// A shape is one piece, priced on its larger side like a letter (rounded up) and by its width x height.
			$size = max($width, $height);
			if (null === wholesale_cl_rate_for_size($cost_table, $size)) {
				return new WP_Error('bad_size', sprintf('Shapes must be between %d" and %d". Please resize them in the builder.', min(array_keys($cost_table)), max(array_keys($cost_table))));
			}
			$letters = array(array('char' => '', 'side' => $size, 'raw' => $size, 'w' => $width, 'h' => $height));
		}
		foreach ($letters as $letter) {
			$cost = wholesale_cl_letter_cost($cost_table, $option_prices, $letter, $element);
			$letters_cost += $cost['letter'] + $letter['side'] * $face_rate + $cost['lexan'] + $cost['trimcap'];
		}
	}

	// Same fallbacks as cl_builderr.php.
	$meta_or = static function ($key, $default) use ($product_id) {
		$value = get_post_meta($product_id, $key, true);
		return $value ? (float) $value : (float) $default;
	};
	$extras = isset($design['extras']) && is_array($design['extras']) ? $design['extras'] : array();
	$extras_cost = 0.0;

	if (isset($extras['powerSupply']['value']) && 'Standard' === $extras['powerSupply']['value'] && !empty($extras['powerSupply']['qty'])) {
		$extras_cost += $meta_or('_standard_ps_cost', 99);
	}
	if (isset($extras['lit']['value']) && 'Back Lit' === $extras['lit']['value'] && !empty($extras['lit']['qty']) && get_post_meta($product_id, '_is_lit_option', true)) {
		$extras_cost += $letters_cost * $meta_or('_backlit_cost', 100) / 100;
	}
	if (isset($extras['cable']['value']) && '8ft Cable' === $extras['cable']['value'] && !empty($extras['cable']['qty'])) {
		$extras_cost += $meta_or('_eight_ft_cable_cost', 77);
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
/**
 * Whether flat-priced options (grommets, holes, H-stake) are charged on top of the
 * minimum charge and the full sheet price, as on B2Sign's rigid signs (checked against
 * B2Sign coroplast quotes 2026-11-05). Other products keep them inside the minimum.
 */
function wholesale_product_flat_options_on_top($product_id)
{
	return has_term('rigid-signs-and-magnets', 'product_category', $product_id);
}

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
	// The typed size in feet, for the per linear foot option types (perim / wlft / hlft).
	$width_ft = 0.0;
	$height_ft = 0.0;

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
		// Every separate piece (the dot of an i, an accent) costs a letter, as in the builder and on B2Sign;
		// per-inch faces and the raceway still go by the letters ($letter_count).
		$letter_pieces = max($letter_count, wholesale_cl_count_pieces($request['letters'] ?? ''));
		$running = $letter_pieces * $per_letter;
		$lines[] = array('label' => $letter_pieces > $letter_count ? sprintf('%d letters (%d pieces) at %d"', $letter_count, $letter_pieces, $letter_height) : sprintf('%d letters at %d"', $letter_count, $letter_height), 'amount' => $running);
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
			$width_ft = (float) $width_attr_value / 12;
			$height_ft = (float) $height_attr_value / 12;
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
		// Dropdown sizes priced like B2Sign (meta _size_dropdown): the width's add-on, and its minimum below.
		$size_dropdown = null !== $width_attr_value && null !== $height_attr_value ? wholesale_size_dropdown_pricing($product_id) : null;
		if ($size_dropdown) {
			$width_key = (string) (0 + (float) $width_attr_value);
			if (!isset($size_dropdown['w'][$width_key])) {
				return $fail('Please choose a valid width.');
			}
			[$width_add, $dropdown_min] = $size_dropdown['w'][$width_key];
			if (0.0 !== round($width_add, 2)) {
				$lines[] = array('label' => sprintf('%s (%s" wide)', $size_dropdown['label'], $width_key), 'amount' => $width_add);
				$running += $width_add;
			}
			$extra = $size_dropdown['extra'];
			if ($extra && null !== ($chosen_extra = wholesale_request_attr_value($request, $extra['attr'])) && trim($chosen_extra) === trim($extra['value'])) {
				$dropdown_min += $extra['min'];
			}
		}
	}

	$sides = 1;
	$percent_total = array();
	// Flat-priced options (grommets, H-stake) on a rigid size calculator product: added on
	// top of the minimum charge and the full sheet price below, as B2Sign does.
	$flat_total = 0.0;
	$flat_on_top = $is_sqft_calc && wholesale_product_flat_options_on_top($product_id);
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
			return $fail(sprintf('Please choose a valid %s.', strtolower(wholesale_attr_label($attr['name']))));
		}
		if (in_array(strtolower((string) $attr['name']), array('of-sides', 'sides'), true) && (int) $chosen['label'] > 1) {
			$sides = (int) $chosen['label'];
		} elseif (in_array(strtolower((string) $attr['name']), array('of-sides', 'sides'), true) && preg_match('/^\s*double[\s-]*sided\b/i', (string) $chosen['label'])) {
			// B2Sign's "Double Sided" (pole flags) is 2 sides too, for the two-sided minimum below.
			$sides = 2;
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
			case 'clin':
				$amount = $price['amount'] * $letter_height * $letter_count;
				break;
			case 'perim':
				$amount = $price['amount'] * 2 * ($width_ft + $height_ft);
				break;
			case 'wlft':
				$amount = $price['amount'] * $width_ft;
				break;
			case 'hlft':
				$amount = $price['amount'] * $height_ft;
				break;
			default:
				$amount = $price['amount'];
				if ($flat_on_top) {
					$flat_total += $amount;
				}
		}

		if (0.0 !== round($amount, 2)) {
			$lines[] = array('label' => wholesale_attr_label($attr['name']) . ': ' . $chosen['label'], 'amount' => $amount);
		}
		$running += $amount;
	}
	$base_running = $running;
	foreach ($percent_total as $item) {
		$amount = $base_running * $item['percent'] / 100;
		if (0.0 !== round($amount, 2)) {
			$lines[] = array('label' => wholesale_attr_label($item['attr']['name']) . ': ' . $item['chosen']['label'], 'amount' => $amount);
		}
		$running += $amount;
	}

	if ($running <= 0) {
		return $fail('Please choose your product options so we can calculate a price.');
	}

	// The size stays exactly as entered. Like B2Sign, the printed size (size x rate plus
	// per sq ft and % options such as 2 Sides) costs at least the minimum charge and at
	// most the full sheet price, both after the product's own price adjustment; flat
	// options (grommets, H-stake) are then added on top, so they always change the price.
	$discount_percent = min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)));
	$keep = (100 - $discount_percent) / 100;
	$minimum_order = 0;
	$extra_sides = max(0, $sides - 1);
	$printed = $running - $flat_total;
	$min_price = $is_sqft_calc && $keep > 0 ? wholesale_product_min_order($product_id) : 0;
	$min_one_side = $min_price;
	// _min_order_per_side: the minimum is per printed side (B2Sign banners: $8 one side, $16 two).
	if (get_post_meta($product_id, '_min_order_per_side', true)) {
		$min_price *= $sides;
	}
	// _min_order_extra_side: added to the minimum for each extra side (B2Sign coroplast: $6.99 one side, $7.99 two).
	if ($min_price > 0) {
		$min_price += $extra_sides * max(0, (float) get_post_meta($product_id, '_min_order_extra_side', true));
	}
	// What the extra printed sides add to the minimum (the quantity pricing below adds it to its first tier).
	$sides_min_add = max(0, $min_price - $min_one_side);
	// Dropdown sizes priced like B2Sign: the chosen width's own minimum (meta _size_dropdown).
	if (isset($dropdown_min)) {
		$min_price = $keep > 0 ? max(0, (float) $dropdown_min) : 0;
	}
	$max_price = $is_sqft_calc && $keep > 0 ? max(0, (float) get_post_meta($product_id, '_max_print_price', true)) : 0;
	if ($max_price > 0) {
		$max_price += $extra_sides * max(0, (float) get_post_meta($product_id, '_max_print_extra_side', true));
	}
	if ($min_price > 0 && $printed * $keep < $min_price) {
		$raised = $min_price / $keep;
		$lines[] = array('label' => sprintf('Minimum order ($%s)', number_format($min_price, 2)), 'amount' => $raised - $printed);
		$running += $raised - $printed;
		$minimum_order = $min_price;
	} elseif ($max_price > 0 && $max_price >= $min_price && $printed * $keep > $max_price) {
		// A full sheet costs the same however much of it the size uses (B2Sign: 4 x 8 ft coroplast $29.99).
		$capped = $max_price / $keep;
		$lines[] = array('label' => sprintf('Full sheet price ($%s)', number_format($max_price, 2)), 'amount' => $capped - $printed);
		$running += $capped - $printed;
	}

	$quantity = min(1000, max(1, absint($request['product_quantity'] ?? 1)));
	// B2Sign's quantity pricing, mirrored by SFS B2Sign Sync (meta _qty_pricing): the lowest price per
	// item depends on the quantity, and rigid boards never cost more than the full sheets they need.
	$qty_pricing = $is_cl ? null : wholesale_product_qty_pricing($product_id);
	if ($qty_pricing && $keep > 0) {
		$printed_now = $running - $flat_total;
		$tier_min = 0.0;
		$tier_index = -1;
		foreach ($qty_pricing['min'] as $index => $tier) {
			if ($quantity >= $tier[0]) {
				$tier_min = (float) $tier[1];
				$tier_index = $index;
			}
		}
		// The two-sided minimum raises the first tier (B2Sign coroplast: one two-sided piece $7.99 against $6.99);
		// B2Sign's two-sided minimums for 2 pieces and more are the one-sided ones.
		if (0 === $tier_index && $sides_min_add > 0) {
			$tier_min += $sides_min_add;
		}
		// Like B2Sign: the sheet share caps the price first, then the quantity's minimum per piece applies
		// (128 Coroplast pieces of 6" x 6" fill one sheet but still cost the minimum per piece).
		$unit_printed = $printed;
		if ($max_price > 0) {
			$unit_printed = min($unit_printed, $max_price / $keep);
		}
		if (!empty($qty_pricing['sheet']) && $is_sqft_calc) {
			$per_sheet = wholesale_pieces_per_sheet($width_ft * 12, $height_ft * 12, $qty_pricing['sheet']['w'], $qty_pricing['sheet']['h']);
			if ($per_sheet > 0) {
				// A two-sided sheet costs more (B2Sign coroplast $33.99 against $29.99).
				$sheet_price = $qty_pricing['sheet']['price'] + $extra_sides * $qty_pricing['sheet']['extra_side'];
				$unit_printed = min($unit_printed, ceil($quantity / $per_sheet) * $sheet_price / $quantity);
			}
		}
		// Fixed-price products (a yard sign with its stake): B2Sign's price per item for the quantity, with
		// the chosen options' difference from the one-piece price on top.
		if (!empty($qty_pricing['unit']) && !$is_sqft_calc) {
			$tier_unit = $first_unit = (float) $qty_pricing['unit'][0][1];
			foreach ($qty_pricing['unit'] as $tier) {
				if ($quantity >= $tier[0]) {
					$tier_unit = (float) $tier[1];
				}
			}
			$unit_printed = $printed_now + ($tier_unit - $first_unit) / $keep;
			// A chosen option whose own difference changes with the quantity (meta _qty_pricing "opt").
			foreach ($qty_pricing['opt'] as $o) {
				$chosen_opt = wholesale_request_attr_value($request, $o[0]);
				if (null === $chosen_opt || trim($chosen_opt) !== trim($o[1])) {
					continue;
				}
				$opt_change = 0.0;
				foreach ($o[2] as $tier) {
					if ($quantity >= $tier[0]) {
						$opt_change = (float) $tier[1];
					}
				}
				$unit_printed += $opt_change / $keep;
			}
		}
		$minimum_order = $tier_min > 0 && $unit_printed * $keep < $tier_min ? $tier_min : 0;
		$unit_printed = max($unit_printed, $tier_min / $keep);
		if (abs($unit_printed - $printed_now) > 0.000001) {
			$lines[] = array('label' => sprintf('Price for %d', $quantity), 'amount' => $unit_printed - $printed_now);
			$running += $unit_printed - $printed_now;
		}
	}
	// Minimum dollar margin per order line (SFS B2Sign Sync markup rule, meta _margin_floor): the line costs at
	// least B2Sign's cost (the price ÷ the markup factor) plus the margin, after the product's own discount.
	$margin_floor = $is_cl ? null : json_decode((string) get_post_meta($product_id, '_margin_floor', true), true);
	if (is_array($margin_floor) && isset($margin_floor['factor'], $margin_floor['dollars']) && $margin_floor['factor'] > 0 && $keep > 0) {
		$line_shown = $running * $quantity * $keep;
		$floor_total = $line_shown / (float) $margin_floor['factor'] + (float) $margin_floor['dollars'];
		if ($floor_total > $line_shown + 0.000001) {
			$raise = ($floor_total - $line_shown) / $keep / $quantity;
			$lines[] = array('label' => 'Minimum order', 'amount' => $raise);
			$running += $raise;
		}
	}
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
	$turnaround_label = '';
	// Per-product production times mirrored from B2Sign (meta _turnaround_spec): which choices a quantity
	// allows, their Pacific cut-off and their surcharge. Products without it keep Next Day / Same Day +100%.
	$turnaround_spec = $is_cl ? array() : wholesale_product_turnaround_spec($product_id);
	// What each choice would add at this price, for the "+$8.00" / "Free" B2Sign shows next to each one.
	$turnaround_options = array();
	if ($turnaround_spec) {
		$typed = $width_ft > 0 && $height_ft > 0 ? array($width_ft * 12, $height_ft * 12) : null;
		$chosen = wholesale_turnaround_choice($turnaround_spec, (string) ($request['turnaround_option'] ?? ''), $quantity, $typed);
		if (is_string($chosen)) {
			return $fail($chosen);
		}
		$turnaround = $subtotal * $chosen['percent'] / 100;
		$turnaround_label = $chosen['label'];
		foreach ($turnaround_spec as $o) {
			$turnaround_options[$o['value']] = round($subtotal * $o['percent'] / 100, 2);
		}
	} elseif (!$is_cl && wholesale_product_turnaround_none($product_id)) {
		// B2Sign offers no production-time choice for this product (hardware): nothing to choose or charge.
		$turnaround = 0.0;
	} elseif (!$is_cl && isset($request['turnaround_option']) && 'same_day' === $request['turnaround_option']) {
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
		// The production time's name when the product has its own choices (cart and order show it).
		'turnaround_label' => $turnaround_label,
		// Each choice's charge at this price (value => dollars), for the amounts next to the choices.
		'turnaround_options' => $turnaround_options,
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

	// update_post_meta() unslashes, so slash the JSON once more to store it exactly.
	update_post_meta($design_id, '_cl_data', wp_slash(stripslashes((string) $design_json)));
	$design['design_url'] = wp_get_attachment_url($design_id);
	$design['design_id'] = $design_id;
	$design['total_cost'] = wholesale_cl_design_total($design, $product_id);
	$design['product_id'] = $product_id;
	// Every reader runs stripslashes() before json_decode(), so store it slashed. Unescaped
	// unicode keeps "CAFÉ" from being read back as "CAFu00c9" (extra letters charged).
	$_SESSION['design_data_' . $product_id] = addslashes(wp_json_encode($design, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

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
 * the standard power supply (wholesale_cl_example_price()), plus shipping as the
 * checkout charges it (free at or above the channel letter minimum). Empty when it can't be priced.
 */
function wholesale_cl_worked_example_text()
{
	$example = wholesale_cl_example_price('standard-channel-letter-front-lit', 6, 12);
	if (!$example) {
		return '';
	}
	// Shipping follows the checkout rule: free at or above the channel letter minimum
	// (wholesale_cart_qualifies_free_shipping()), else the cheapest channel rate.
	return sprintf('Example: 6 front lit letters, 12&Prime; tall, with power supply: $%s', number_format($example['total'], 2))
		. wholesale_cl_example_shipping_text($example['total']);
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
 * The cheapest way to buy one of a product: the smallest-size quote Google gets
 * (wholesale_seo_product_lowest_price, default choices), then every choice of each
 * option on its own, then the cheapest choice of every option together. 0 when the
 * product has no online price.
 */
function wholesale_product_cheapest_price($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}
	$lowest = function_exists('wholesale_seo_product_lowest_price') ? (float) wholesale_seo_product_lowest_price($product_id) : 0.0;
	$request = $GLOBALS['wholesale_seo_lowest_quote'][$product_id]['request'] ?? null;
	if ($lowest <= 0 || !is_array($request)) {
		return $cache[$product_id] = $lowest;
	}
	$pick = array();
	foreach (wholesale_product_price_attrs($product_id) as $attr) {
		$options = wholesale_attr_options($attr);
		if (empty($attr['name']) || count($options) < 2) {
			continue;
		}
		$low = null;
		foreach ($options as $option) {
			$quote = wholesale_price_quote($product_id, array_merge($request, array($attr['name'] => $option['value'])));
			if (!empty($quote['ok']) && $quote['total'] > 0 && (null === $low || $quote['total'] < $low[0])) {
				$low = array((float) $quote['total'], $option['value']);
			}
		}
		if ($low) {
			$lowest = min($lowest, $low[0]);
			$pick[$attr['name']] = $low[1];
		}
	}
	if (count($pick) > 1) {
		$quote = wholesale_price_quote($product_id, array_merge($request, $pick));
		if (!empty($quote['ok']) && $quote['total'] > 0) {
			$lowest = min($lowest, (float) $quote['total']);
		}
	}
	return $cache[$product_id] = round($lowest, 2);
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
		} elseif (get_post_meta($object_id, '_hide_calculator', true) && preg_match('/per\s*(ft|sq)/i', $stored) && function_exists('wholesale_seo_product_lowest_price')) {
			// Preset sizes (Table Runner, DTF): no one can buy at the per sq ft rate, so show the
			// cheapest real price (owner 2026-11-08: the cheapest choice, not the default one).
			$reading = true;
			$lowest = wholesale_product_cheapest_price($object_id);
			$reading = false;
			if ($lowest > 0) {
				$stored = '$' . number_format($lowest, 2) . ' per item';
			}
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
