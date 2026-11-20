<?php
/**
 * Artwork size check (owner request 2026-11-15, after order NODHJFDR31: an 18" W x 12" H
 * magnet ordered with a 17" x 17" file, then canceled).
 *
 * Like B2Sign, an uploaded print file must have the shape of the size ordered, or it is
 * turned away:
 * - PDF: each page's trim box (else crop box, else media box), in inches.
 * - JPG / PNG: the pixel width and height (shape only; pixels carry no reliable inches).
 *
 * The file may be the exact size or the same shape at another scale (36" x 24" for an
 * 18" x 12" order), with up to WHOLESALE_ARTWORK_BLEED_IN of bleed per side and a
 * WHOLESALE_ARTWORK_SHAPE_TOLERANCE margin. A file turned on its side (12" x 18" for
 * 18" x 12") is refused. Products whose ordered size can't be read (channel letters,
 * flags, tents, table covers, hardware) are not checked.
 *
 * Used by utils/Cart.php (the final say, before a held upload is claimed) and the
 * wholesale_artwork_check AJAX action, which js/artwork-size-check.js calls as soon as
 * a file is uploaded and again when the size changes.
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_ARTWORK_SHAPE_TOLERANCE = 0.02; // 2% either way.
const WHOLESALE_ARTWORK_BLEED_IN = 0.25;        // Per side.

/**
 * The print file types the product page takes.
 */
function wholesale_artwork_mimes()
{
	return array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png' => 'image/png',
		'pdf' => 'application/pdf',
	);
}

function wholesale_artwork_types_text()
{
	return 'PDF, JPG or PNG';
}

/* ---------------------------------------------------------------------------------------
 * Ordered size
 * ------------------------------------------------------------------------------------- */

/**
 * Lowercase, straight quotes, single spaces: for matching option labels.
 */
function wholesale_artwork_norm($text)
{
	$text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = str_replace(array('”', '“', '″', '’', '‘', '′', '×'), array('"', '"', '"', "'", "'", "'", 'x'), $text);
	return trim(preg_replace('/\s+/', ' ', strtolower($text)));
}

/**
 * "29.5 x 139.5 or 59 x 139.5" => [[29.5, 139.5], [59, 139.5]], or null when a part isn't a size.
 */
function wholesale_artwork_parse_size_list($text)
{
	$sizes = array();
	foreach (preg_split('/\s+or\s+/i', trim((string) $text)) as $part) {
		$size = wholesale_artwork_parse_size_label($part);
		if (!$size) {
			return null;
		}
		$sizes[] = $size;
	}
	return $sizes ?: null;
}

/**
 * The product's "Artwork size" field (meta _artwork_size), read as
 * array('off' => bool, 'size' => [[w, h], ...] or null, 'options' => [[option, [[w, h], ...], written], ...], 'invalid' => [lines]).
 *
 *   24 x 36                      one print size for every order
 *   8ft = 94 x 33                the print size when the option "8ft" is chosen
 *   Large = 29.5 x 139.5 or 59 x 139.5   either size is accepted (e.g. one side, or
 *                                both sides in one file as in the double-sided template)
 *   off                          no check for this product
 */
function wholesale_artwork_size_rules($product_id, $raw = null)
{
	$raw = null === $raw ? (string) get_post_meta($product_id, '_artwork_size', true) : (string) $raw;
	$rules = array('off' => false, 'size' => null, 'options' => array(), 'invalid' => array());
	foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
		$line = trim($line);
		if ('' === $line || '#' === $line[0]) {
			continue;
		}
		if (preg_match('/^(off|none|no|skip)$/i', $line)) {
			$rules['off'] = true;
			continue;
		}
		$equals = strrpos($line, '=');
		if (false !== $equals) {
			$option = wholesale_artwork_norm(substr($line, 0, $equals));
			$sizes = wholesale_artwork_parse_size_list(substr($line, $equals + 1));
			if ('' !== $option && $sizes) {
				$rules['options'][] = array($option, $sizes, trim(substr($line, 0, $equals)));
			} else {
				$rules['invalid'][] = $line;
			}
			continue;
		}
		$sizes = wholesale_artwork_parse_size_list($line);
		if ($sizes) {
			$rules['size'] = $sizes;
		} else {
			$rules['invalid'][] = $line;
		}
	}
	return $rules;
}

/**
 * The labels of the options chosen on the form (a fixed single choice counts as chosen).
 */
function wholesale_artwork_chosen_labels(array $attrs, array $request)
{
	$labels = array();
	foreach ($attrs as $attr) {
		$options = wholesale_attr_options($attr);
		$chosen = wholesale_request_attr_value($request, $attr['name'] ?? '');
		if (null === $chosen && 1 === count($options)) {
			$labels[] = $options[0]['label'];
			continue;
		}
		$found = false;
		foreach ($options as $option) {
			// The option's value, or its label: product group tabs (e.g. Tent Walls'
			// "Half Wall") post the label text instead of the stored value.
			if (null !== $chosen && (trim($option['value']) === trim($chosen) || sanitize_text_field($option['value']) === trim($chosen)
				|| wholesale_artwork_norm($option['label']) === wholesale_artwork_norm($chosen))) {
				$labels[] = $option['label'];
				$found = true;
				break;
			}
		}
		// A single fixed choice is the choice, whatever the form sent for it.
		if (!$found && 1 === count($options)) {
			$labels[] = $options[0]['label'];
		}
	}
	return $labels;
}

/**
 * The accepted print sizes from the "Artwork size" field for these choices: [[w, h], ...],
 * false for "off", or null when the field doesn't say.
 */
function wholesale_artwork_field_size($product_id, array $attrs, array $request, $has_calculator)
{
	$rules = wholesale_artwork_size_rules($product_id);
	if ($rules['off']) {
		return false;
	}
	if ($rules['options']) {
		$labels = array_map('wholesale_artwork_norm', wholesale_artwork_chosen_labels($attrs, $request));
		foreach ($rules['options'] as [$option, $size]) {
			foreach ($labels as $label) {
				if ($label === $option || preg_match('/(^|[^a-z0-9])' . preg_quote($option, '/') . '($|[^a-z0-9])/', $label)) {
					return $size;
				}
			}
		}
	}
	// One size for every order; the typed size wins on size-calculator products.
	return $rules['size'] && !$has_calculator ? $rules['size'] : null;
}

/**
 * The print size for this order, in inches: array(width, height, source[, accepted sizes]),
 * or null when it can't be told (then the file isn't checked). Source: 'typed' (the width
 * and height boxes), 'option' (a size option), 'field' (the product's "Artwork size"
 * field, which can accept more than one size: [[w, h], ...], the first being width, height).
 */
function wholesale_artwork_ordered_size($product_id, array $request)
{
	$product_id = absint($product_id);
	if (!$product_id || !apply_filters('wholesale_artwork_size_check_enabled', true, $product_id)) {
		return null;
	}
	if (wholesale_product_is_channel_letter($product_id)) {
		return null;
	}
	$attrs = wholesale_product_price_attrs($product_id);
	$has_calculator = !get_post_meta($product_id, '_hide_calculator', true);

	$field_size = wholesale_artwork_field_size($product_id, $attrs, $request, $has_calculator);
	if (false === $field_size) {
		return null;
	}
	if ($field_size) {
		return array($field_size[0][0], $field_size[0][1], 'field', $field_size);
	}

	// 1. Width and height dropdowns in inches (table runner, poster hangers, pole banners).
	$width = null;
	$height = null;
	foreach ($attrs as $attr) {
		$css = (string) ($attr['cssClass'] ?? '');
		if (false !== strpos($css, 'size-width-inch')) {
			$width = wholesale_request_attr_value($request, $attr['name']);
		}
		if (false !== strpos($css, 'size-height-inch')) {
			$height = wholesale_request_attr_value($request, $attr['name']);
		}
	}
	if (null !== $width && null !== $height) {
		return (float) $width > 0 && (float) $height > 0 ? array((float) $width, (float) $height, 'option') : null;
	}

	// 2. The typed Width x Height box (also filled by the size presets).
	if ($has_calculator) {
		$width = (float) ($request['width-ft'] ?? 0) * 12 + (float) ($request['width-in'] ?? 0);
		$height = (float) ($request['height-ft'] ?? 0) * 12 + (float) ($request['height-in'] ?? 0);
		return $width > 0 && $height > 0 ? array($width, $height, 'typed') : null;
	}

	// 3. A size dropdown whose choice reads like 24" x 36" or 10' W x 8' H.
	$candidates = array();
	foreach ($attrs as $attr) {
		$name = strtolower(trim((string) ($attr['name'] ?? '')));
		if ('size' === $name) {
			array_unshift($candidates, $attr);
		} elseif (preg_match('/(size|graphic|art)/', $name) && !preg_match('/(hardware|pole|post|display|table|tent|carry|frame|bag|wall|flag|shape|stand)/', $name)) {
			$candidates[] = $attr;
		}
	}
	foreach ($candidates as $attr) {
		$options = wholesale_attr_options($attr);
		$chosen = wholesale_request_attr_value($request, $attr['name']);
		$label = null;
		if (null === $chosen && 1 === count($options)) {
			$label = $options[0]['label']; // A fixed single choice isn't posted by the form.
		} else {
			foreach ($options as $option) {
				if (null !== $chosen && (trim($option['value']) === trim($chosen) || sanitize_text_field($option['value']) === trim($chosen))) {
					$label = $option['label'];
					break;
				}
			}
		}
		$size = null !== $label ? wholesale_artwork_parse_size_label($label) : null;
		if ($size) {
			return array($size[0], $size[1], 'option');
		}
	}
	return null;
}

/**
 * "24\" x 36\"", "17.5\"W x 24\"H", "10' W x 8' H", "12\" x 18\" (Portrait)" => array(w, h) in inches.
 */
function wholesale_artwork_parse_size_label($label)
{
	$text = html_entity_decode((string) $label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = str_replace('×', 'x', $text);
	$text = str_replace(array('”', '“', '″', '&quot;'), '"', $text);
	$text = str_replace(array('’', '‘', '′'), "'", $text);
	$unit = '(\"|\'|in(?:ch(?:es)?)?\.?|ft\.?|feet|foot)?';
	if (!preg_match('/(\d+(?:\.\d+)?)\s*' . $unit . '\s*(?:W\b)?\s*x\s*(\d+(?:\.\d+)?)\s*' . $unit . '\s*(?:H\b)?/i', $text, $m)) {
		return null;
	}
	$is_feet = static function ($u) {
		$u = strtolower((string) $u);
		return '' !== $u && ("'" === $u || 0 === strpos($u, 'f'));
	};
	$first_unit = $m[2] ?? '';
	$second_unit = $m[4] ?? '';
	// "10 x 8'" means feet on both sides; a bare "24 x 36" is inches.
	if ('' === $first_unit) {
		$first_unit = $second_unit;
	}
	if ('' === $second_unit) {
		$second_unit = $first_unit;
	}
	$width = (float) $m[1] * ($is_feet($first_unit) ? 12 : 1);
	$height = (float) $m[3] * ($is_feet($second_unit) ? 12 : 1);
	return $width > 0 && $height > 0 ? array($width, $height) : null;
}

/* ---------------------------------------------------------------------------------------
 * Measuring the file
 * ------------------------------------------------------------------------------------- */

/**
 * array('kind' => 'pdf', 'pages' => [[w_in, h_in], ...]) or array('kind' => 'image', 'w' => px, 'h' => px),
 * or null when the file can't be read.
 */
function wholesale_artwork_measure($path, $mime)
{
	if (!is_string($path) || !is_file($path)) {
		return null;
	}
	if ('application/pdf' === $mime) {
		$pages = wholesale_artwork_pdf_pages($path);
		return $pages ? array('kind' => 'pdf', 'pages' => $pages) : null;
	}
	$info = @getimagesize($path);
	if (!$info || $info[0] < 1 || $info[1] < 1) {
		return null;
	}
	$width = (int) $info[0];
	$height = (int) $info[1];
	// A phone photo saved sideways with an "turn it" flag shows (and is seen by the customer) turned.
	if (IMAGETYPE_JPEG === $info[2] && function_exists('exif_read_data')) {
		$exif = @exif_read_data($path, 'IFD0');
		$orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
		if ($orientation >= 5 && $orientation <= 8) {
			[$width, $height] = array($height, $width);
		}
	}
	return array('kind' => 'image', 'w' => $width, 'h' => $height);
}

/**
 * Page sizes of a PDF in inches, in page order (at most $max_pages), or null.
 *
 * A small reader that needs no PDF library: it indexes every object (including those
 * packed in compressed object streams), walks the page tree from the catalog and reads
 * each page's TrimBox / CropBox / MediaBox, following /Parent for inherited boxes,
 * /Rotate and /UserUnit (used by large-format PDFs over 200 inches).
 */
function wholesale_artwork_pdf_pages($path, $max_pages = 50)
{
	$bytes = @filesize($path);
	if (!$bytes || $bytes > 64 * MB_IN_BYTES) {
		return null;
	}
	$data = @file_get_contents($path);
	if (!is_string($data) || false === strpos(substr($data, 0, 1024), '%PDF')) {
		return null;
	}

	$objects = wholesale_artwork_pdf_objects($data);
	if (!$objects) {
		return null;
	}

	// The page tree, from the newest trailer's /Root.
	$page_ids = array();
	if (preg_match_all('/\/Root\s+(\d+)\s+\d+\s+R/', $data, $roots) && isset($objects[(int) end($roots[1])])) {
		$catalog = $objects[(int) end($roots[1])];
		if (preg_match('/\/Pages\s+(\d+)\s+\d+\s+R/', $catalog, $pages_ref)) {
			$queue = array((int) $pages_ref[1]);
			$seen = array();
			while ($queue && count($page_ids) < $max_pages && count($seen) < 20000) {
				$id = array_shift($queue);
				if (isset($seen[$id]) || !isset($objects[$id])) {
					continue;
				}
				$seen[$id] = true;
				$dict = $objects[$id];
				$kids = wholesale_artwork_pdf_kids($objects, $dict);
				if (null !== $kids) {
					$queue = array_merge($kids, $queue); // Depth first keeps the page order.
				} elseif (wholesale_artwork_pdf_is_page($dict)) {
					$page_ids[] = $id;
				}
			}
		}
	}
	// No usable tree (damaged file): every page object in the file.
	if (!$page_ids) {
		foreach ($objects as $id => $dict) {
			if (wholesale_artwork_pdf_is_page($dict) && null === wholesale_artwork_pdf_kids($objects, $dict)) {
				$page_ids[] = $id;
				if (count($page_ids) >= $max_pages) {
					break;
				}
			}
		}
	}

	$pages = array();
	foreach ($page_ids as $id) {
		$size = wholesale_artwork_pdf_page_size($objects, $id);
		if ($size) {
			$pages[] = $size;
		}
	}
	return $pages ?: null;
}

/**
 * Object number => dictionary text (stream data left out), later definitions winning.
 */
function wholesale_artwork_pdf_objects($data)
{
	if (!preg_match_all('/(?<![\d.])(\d{1,7})\s+\d{1,5}\s+obj\b/', $data, $found, PREG_OFFSET_CAPTURE)) {
		return array();
	}
	$objects = array();
	$length = strlen($data);
	$count = count($found[0]);
	$skip_until = 0;
	for ($i = 0; $i < $count; $i++) {
		$offset = $found[0][$i][1];
		if ($offset < $skip_until) {
			continue; // Bytes inside a stream that happen to read like "12 0 obj".
		}
		$num = (int) $found[1][$i][0];
		$start = $offset + strlen($found[0][$i][0]);
		$end = $i + 1 < $count ? $found[0][$i + 1][1] : $length;
		$endobj = strpos($data, 'endobj', $start);
		if (false !== $endobj && $endobj < $end) {
			$end = $endobj;
		}
		$stream = strpos($data, 'stream', $start);
		$has_stream = false !== $stream && $stream < $end && 'end' !== substr($data, $stream - 3, 3);
		$dict = substr($data, $start, min(($has_stream ? $stream : $end) - $start, 65536));
		$objects[$num] = $dict;
		if (!$has_stream) {
			continue;
		}

		// Where the stream's bytes start and stop.
		$data_start = $stream + 6;
		if ("\r" === ($data[$data_start] ?? '')) {
			$data_start++;
		}
		if ("\n" === ($data[$data_start] ?? '')) {
			$data_start++;
		}
		$data_end = false;
		if (preg_match('/\/Length\s+(\d++)(?!\s+\d+\s+R)/', $dict, $len) && $data_start + (int) $len[1] <= $length
			&& false !== ($check = strpos($data, 'endstream', $data_start + (int) $len[1])) && $check - ($data_start + (int) $len[1]) < 16) {
			$data_end = $data_start + (int) $len[1];
		}
		if (false === $data_end) {
			$data_end = strpos($data, 'endstream', $data_start);
			if (false === $data_end) {
				break;
			}
		}
		$skip_until = $data_end;

		if (preg_match('/\/Type\s*\/ObjStm\b/', $dict)) {
			foreach (wholesale_artwork_pdf_object_stream($dict, substr($data, $data_start, $data_end - $data_start)) as $inner_num => $inner) {
				$objects[$inner_num] = $inner;
			}
		}
	}
	return $objects;
}

/**
 * The objects packed in a compressed object stream (PDF 1.5+).
 */
function wholesale_artwork_pdf_object_stream($dict, $raw)
{
	if (preg_match('/\/DecodeParms/', $dict) && preg_match('/\/Predictor\s+(\d+)/', $dict, $predictor) && (int) $predictor[1] > 1) {
		return array();
	}
	if (preg_match('/\/Filter\s*\[?\s*\/FlateDecode/', $dict)) {
		$decoded = false;
		foreach (array_unique(array($raw, rtrim($raw, "\r\n"))) as $candidate) {
			$decoded = @gzuncompress($candidate, 32 * MB_IN_BYTES);
			if (false === $decoded && strlen($candidate) > 2) {
				$decoded = @gzinflate(substr($candidate, 2), 32 * MB_IN_BYTES); // zlib header, raw deflate.
			}
			if (false !== $decoded) {
				break;
			}
		}
		if (false === $decoded) {
			return array();
		}
	} elseif (preg_match('/\/Filter/', $dict)) {
		return array();
	} else {
		$decoded = $raw;
	}
	if (!preg_match('/\/N\s+(\d+)/', $dict, $n) || !preg_match('/\/First\s+(\d+)/', $dict, $first)) {
		return array();
	}
	$first = (int) $first[1];
	preg_match_all('/(\d+)\s+(\d+)/', substr($decoded, 0, $first), $pairs);
	$total = min(count($pairs[1]), (int) $n[1]);
	$objects = array();
	for ($k = 0; $k < $total; $k++) {
		$from = $first + (int) $pairs[2][$k];
		$to = $k + 1 < $total ? $first + (int) $pairs[2][$k + 1] : strlen($decoded);
		if ($to > $from) {
			$objects[(int) $pairs[1][$k]] = substr($decoded, $from, min($to - $from, 65536));
		}
	}
	return $objects;
}

function wholesale_artwork_pdf_is_page($dict)
{
	return (bool) preg_match('/\/Type\s*\/Page(?![A-Za-z0-9])/', $dict);
}

/**
 * A Pages node's kids as object numbers, or null when the object has no /Kids.
 */
function wholesale_artwork_pdf_kids(array $objects, $dict)
{
	if (preg_match('/\/Kids\s*\[([^\]]*)\]/', $dict, $kids)) {
		$list = $kids[1];
	} elseif (preg_match('/\/Kids\s+(\d+)\s+\d+\s+R/', $dict, $ref) && isset($objects[(int) $ref[1]])) {
		$list = $objects[(int) $ref[1]];
	} else {
		return null;
	}
	preg_match_all('/(\d+)\s+\d+\s+R/', $list, $refs);
	return array_map('intval', $refs[1]);
}

/**
 * A page key's value, looked up on the page and then (when $inherit) its parents.
 */
function wholesale_artwork_pdf_page_value(array $objects, $id, $key, $inherit)
{
	$pattern_array = '/\/' . $key . '\s*\[([^\]]*)\]/';
	$pattern_number = '/\/' . $key . '\s+(-?\d++(?:\.\d++)?)(?!\s+\d+\s+R)/';
	$pattern_ref = '/\/' . $key . '\s+(\d+)\s+\d+\s+R/';
	$seen = array();
	while (isset($objects[$id]) && !isset($seen[$id]) && count($seen) < 32) {
		$seen[$id] = true;
		$dict = $objects[$id];
		if (preg_match($pattern_array, $dict, $m)) {
			return $m[1];
		}
		if (preg_match($pattern_ref, $dict, $m) && isset($objects[(int) $m[1]])) {
			$target = trim($objects[(int) $m[1]]);
			return preg_match('/^\[([^\]]*)\]/', $target, $a) ? $a[1] : $target;
		}
		if (preg_match($pattern_number, $dict, $m)) {
			return $m[1];
		}
		if (!$inherit || !preg_match('/\/Parent\s+(\d+)\s+\d+\s+R/', $dict, $parent)) {
			break;
		}
		$id = (int) $parent[1];
	}
	return null;
}

/**
 * array(width_in, height_in) of one page as it is viewed, or null.
 */
function wholesale_artwork_pdf_page_size(array $objects, $id)
{
	$box = null;
	foreach (array('TrimBox' => false, 'CropBox' => true, 'MediaBox' => true) as $key => $inherit) {
		$value = wholesale_artwork_pdf_page_value($objects, $id, $key, $inherit);
		if (null === $value || !preg_match_all('/-?\d+(?:\.\d+)?|-?\.\d+/', $value, $numbers) || count($numbers[0]) < 4) {
			continue;
		}
		[$x1, $y1, $x2, $y2] = array_map('floatval', array_slice($numbers[0], 0, 4));
		$width = abs($x2 - $x1);
		$height = abs($y2 - $y1);
		if ($width >= 1 && $height >= 1) {
			$box = array($width, $height);
			break;
		}
	}
	if (!$box) {
		return null;
	}
	$unit = (float) (wholesale_artwork_pdf_page_value($objects, $id, 'UserUnit', false) ?? 1);
	$unit = $unit > 0 ? $unit : 1;
	$rotate = ((int) (wholesale_artwork_pdf_page_value($objects, $id, 'Rotate', true) ?? 0) % 360 + 360) % 360;
	$width = $box[0] * $unit / 72;
	$height = $box[1] * $unit / 72;
	if (90 === $rotate || 270 === $rotate) {
		[$width, $height] = array($height, $width);
	}
	return array(round($width, 3), round($height, 3));
}

/* ---------------------------------------------------------------------------------------
 * Comparing
 * ------------------------------------------------------------------------------------- */

/**
 * True when a file of $file_w x $file_h has the shape of an $order_w x $order_h order
 * (any scale, up to the bleed allowance per side, within the tolerance).
 */
function wholesale_artwork_shape_matches($file_w, $file_h, $order_w, $order_h, $bleed_per_side = WHOLESALE_ARTWORK_BLEED_IN)
{
	if ($file_w <= 0 || $file_h <= 0 || $order_w <= 0 || $order_h <= 0) {
		return false;
	}
	$tolerance = (float) apply_filters('wholesale_artwork_shape_tolerance', WHOLESALE_ARTWORK_SHAPE_TOLERANCE);
	$bleed = 2 * max(0, (float) $bleed_per_side);
	$ratio = $file_w / $file_h;
	$exact = $order_w / $order_h;
	$with_bleed = ($order_w + $bleed) / ($order_h + $bleed);
	return $ratio >= min($exact, $with_bleed) * (1 - $tolerance) && $ratio <= max($exact, $with_bleed) * (1 + $tolerance);
}

/**
 * Extra artwork allowed on each side, in inches. Canvas is wrapped around its frame, so
 * the file may carry about 2" more per side than the face size ordered.
 */
function wholesale_artwork_bleed_for($product_id)
{
	$bleed = WHOLESALE_ARTWORK_BLEED_IN;
	if (false !== stripos((string) get_the_title($product_id), 'canvas')) {
		$bleed = 2.0;
	}
	foreach (wholesale_product_price_attrs($product_id) as $attr) {
		if ('edge-option' === strtolower(trim((string) ($attr['name'] ?? '')))) {
			$bleed = 2.0;
		}
	}
	return (float) apply_filters('wholesale_artwork_bleed_in', $bleed, $product_id);
}

function wholesale_artwork_inches_text($inches)
{
	$text = number_format((float) $inches, 2, '.', '');
	return rtrim(rtrim($text, '0'), '.') . '"';
}

function wholesale_artwork_shape_word($width, $height)
{
	$ratio = $width / $height;
	if (abs($ratio - 1) <= WHOLESALE_ARTWORK_SHAPE_TOLERANCE) {
		return 'square';
	}
	return $ratio > 1 ? 'wide' : 'tall';
}

/**
 * '' when the measured file fits the ordered size, else the message for the customer.
 */
function wholesale_artwork_size_problem($measure, array $ordered, $bleed_per_side = WHOLESALE_ARTWORK_BLEED_IN)
{
	[$order_w, $order_h] = $ordered;
	// Every size this order accepts (the "Artwork size" field may list more than one).
	$accepted = !empty($ordered[3]) && is_array($ordered[3]) ? $ordered[3] : array(array($order_w, $order_h));
	$ordered_text = wholesale_artwork_inches_text($order_w) . ' W × ' . wholesale_artwork_inches_text($order_h) . ' H';
	$size_text = implode(' or ', array_map(static function ($size) {
		return wholesale_artwork_inches_text($size[0]) . ' × ' . wholesale_artwork_inches_text($size[1]);
	}, $accepted));

	if (!is_array($measure)) {
		return sprintf('We couldn’t read the size of this file. Please save your artwork again as a standard PDF, JPG or PNG at %s and upload it again.', $size_text);
	}

	$files = 'pdf' === $measure['kind'] ? $measure['pages'] : array(array($measure['w'], $measure['h']));
	foreach ($files as $index => $file) {
		[$file_w, $file_h] = $file;
		foreach ($accepted as $size) {
			if (wholesale_artwork_shape_matches($file_w, $file_h, $size[0], $size[1], $bleed_per_side)) {
				continue 2;
			}
		}
		if ('pdf' === $measure['kind']) {
			$file_text = wholesale_artwork_inches_text($file_w) . ' W × ' . wholesale_artwork_inches_text($file_h) . ' H';
			$scale = max($order_w, $order_h) > 48 ? 0.5 : 2;
			$example = wholesale_artwork_inches_text($order_w * $scale) . ' × ' . wholesale_artwork_inches_text($order_h * $scale);
		} else {
			$file_text = number_format($file_w) . ' × ' . number_format($file_h) . ' pixels';
			$per_inch = min(150, 6000 / max($order_w, $order_h));
			$example = number_format(round($order_w * $per_inch)) . ' × ' . number_format(round($order_h * $per_inch)) . ' pixels';
		}
		$order_shape = wholesale_artwork_shape_word($order_w, $order_h);
		$file_shape = wholesale_artwork_shape_word($file_w, $file_h);
		$which = count($files) > 1 ? sprintf('page %d of your file', $index + 1) : 'your file';
		// Worded for where the size came from (wholesale_artwork_ordered_size()).
		$source = $ordered[2] ?? 'typed';
		$intro = array(
			'typed' => 'You ordered %1$s (%2$s)',
			'option' => 'The size you chose is %1$s (%2$s)',
			'field' => 'The print size for this item is %1$s (%2$s)',
		);
		$outro = array(
			'typed' => ', or change the size to match your file.',
			'option' => ', or choose the size that matches your file.',
			'field' => '.',
		);
		return sprintf(
			'This artwork doesn’t match the size you ordered. ' . ($intro[$source] ?? $intro['typed']) . ', but %3$s is %4$s (%5$s), so it would print stretched or cut off. Please upload artwork made at %6$s (or the same shape, like %7$s)' . ($outro[$source] ?? $outro['typed']),
			$ordered_text,
			$order_shape,
			$which,
			$file_text,
			$file_shape,
			$size_text,
			$example
		);
	}
	return '';
}

/**
 * Product editor: under the "Artwork size" field, what the saved value means for uploads.
 */
function wholesale_artwork_size_field_summary($field_args, $field)
{
	$product_id = is_object($field) ? (int) $field->object_id : 0;
	if (!$product_id || 'product' !== get_post_type($product_id)) {
		return '';
	}
	// One size ([w, h]) or the accepted sizes of a field line ([[w, h], ...]).
	$size_text = static function ($sizes) {
		$sizes = is_array($sizes[0] ?? null) ? $sizes : array($sizes);
		return implode(' or ', array_map(static function ($size) {
			return wholesale_artwork_inches_text($size[0]) . ' W × ' . wholesale_artwork_inches_text($size[1]) . ' H';
		}, $sizes));
	};
	$rules = wholesale_artwork_size_rules($product_id);
	$has_calculator = !get_post_meta($product_id, '_hide_calculator', true);
	$lines = array();
	if ($rules['invalid']) {
		$lines[] = '<strong style="color:#b42318">' . esc_html('Not understood, ignored: ' . implode(' · ', $rules['invalid'])) . '</strong>';
	}
	if (wholesale_product_is_channel_letter($product_id)) {
		$lines[] = esc_html('Channel letters: uploads are not checked.');
	} elseif ($rules['off']) {
		$lines[] = esc_html('Off: uploads for this product are not checked.');
	} else {
		foreach ($rules['options'] as [, $size, $written]) {
			$lines[] = esc_html(sprintf('When "%s" is chosen: %s', $written, $size_text($size)));
		}
		if ($rules['size'] && !$has_calculator) {
			$lines[] = esc_html(($rules['options'] ? 'Any other choice: ' : 'Every order: ') . $size_text($rules['size']));
		} elseif ($has_calculator) {
			$lines[] = esc_html(($rules['options'] ? 'Any other choice: c' : 'C') . 'hecked against the width × height the customer types.' . ($rules['size'] ? ' (A single size is ignored on size-calculator products.)' : ''));
		} else {
			// No size in the field for other choices: the size option, if there is one.
			$attrs = wholesale_product_price_attrs($product_id);
			$request = array();
			foreach ($attrs as $attr) {
				$options = wholesale_attr_options($attr);
				if ($options) {
					$request[$attr['name']] = $options[0]['value'];
				}
			}
			$auto = wholesale_artwork_ordered_size($product_id, $request);
			if ($auto && 'field' !== $auto[2]) {
				$lines[] = esc_html(($rules['options'] ? 'Any other choice: c' : 'C') . 'hecked against the size option the customer picks (the first choice reads as ' . $size_text($auto) . ').');
			} else {
				$lines[] = esc_html($rules['options'] ? 'Any other choice: not checked.' : 'Not checked: this product has no width × height to compare. Enter the print size above to check uploads.');
			}
		}
	}
	return '<p class="cmb2-metabox-description wholesale-artwork-size-summary" style="margin-top:6px"><strong>Upload check:</strong> ' . implode('<br>', $lines) . '</p>';
}

/* ---------------------------------------------------------------------------------------
 * Held uploads, the cart and the AJAX check
 * ------------------------------------------------------------------------------------- */

/**
 * A background upload (inc/async-uploads.php) without claiming it:
 * array('path', 'type', 'measure') or null. The measurement is kept with the token.
 */
function wholesale_artwork_held_file($token)
{
	$token = is_string($token) ? $token : '';
	if (!preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
		return null;
	}
	$dir = wholesale_async_upload_dir();
	$meta_path = $dir . '/' . $token . '.json';
	$path = $dir . '/' . $token . '.bin';
	if (!is_file($meta_path) || !is_file($path)) {
		return null;
	}
	$meta = json_decode((string) file_get_contents($meta_path), true);
	if (!is_array($meta) || 'artwork' !== ($meta['context'] ?? '')) {
		return null;
	}
	if (!array_key_exists('measure', $meta)) {
		$meta['measure'] = wholesale_artwork_measure($path, (string) ($meta['type'] ?? ''));
		@file_put_contents($meta_path, wp_json_encode($meta));
	}
	return array('path' => $path, 'type' => (string) ($meta['type'] ?? ''), 'measure' => $meta['measure']);
}

/**
 * The artwork posted with an Add To Cart (held token or plain file), measured, or null.
 * 'measure' is null when the file can't be read.
 */
function wholesale_artwork_posted_file()
{
	$held = wholesale_artwork_held_file(isset($_POST['custom-artwork-token']) ? wp_unslash($_POST['custom-artwork-token']) : '');
	if ($held) {
		return $held;
	}
	$file = $_FILES['custom-artwork'] ?? null;
	if (!is_array($file) || UPLOAD_ERR_OK !== (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) || !is_uploaded_file($file['tmp_name'])) {
		return null;
	}
	$checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], wholesale_artwork_mimes());
	if (empty($checked['type'])) {
		return null; // utils/Cart.php turns the type down with its own message.
	}
	return array('path' => $file['tmp_name'], 'type' => $checked['type'], 'measure' => wholesale_artwork_measure($file['tmp_name'], $checked['type']));
}

/**
 * For utils/Cart.php: '' or the message that stops this Add To Cart.
 */
function wholesale_artwork_request_problem($product_id, array $request)
{
	$file = wholesale_artwork_posted_file();
	if (!$file) {
		return '';
	}
	$ordered = wholesale_artwork_ordered_size($product_id, $request);
	return $ordered ? wholesale_artwork_size_problem($file['measure'], $ordered, wholesale_artwork_bleed_for($product_id)) : '';
}

/**
 * POST product_id, token and the product form's fields:
 * {checked, ok, message, ordered}. checked is false when there is nothing to compare.
 */
function wholesale_ajax_artwork_check()
{
	nocache_headers();
	$request = map_deep(wp_unslash($_POST), 'sanitize_text_field');
	$product_id = absint($request['product_id'] ?? 0);
	$held = wholesale_artwork_held_file($request['token'] ?? '');
	$ordered = $product_id && $held && 'product' === get_post_type($product_id) ? wholesale_artwork_ordered_size($product_id, $request) : null;
	if (!$held || !$ordered) {
		wp_send_json_success(array('checked' => false, 'ok' => true, 'message' => ''));
	}
	$problem = wholesale_artwork_size_problem($held['measure'], $ordered, wholesale_artwork_bleed_for($product_id));
	wp_send_json_success(array(
		'checked' => true,
		'ok' => '' === $problem,
		'message' => $problem,
		'ordered' => wholesale_artwork_inches_text($ordered[0]) . ' × ' . wholesale_artwork_inches_text($ordered[1]),
	));
}
add_action('wp_ajax_wholesale_artwork_check', 'wholesale_ajax_artwork_check');
add_action('wp_ajax_nopriv_wholesale_artwork_check', 'wholesale_ajax_artwork_check');

function wholesale_artwork_size_check_script()
{
	if (is_admin() || !is_singular('product')) {
		return;
	}
	$file = get_template_directory() . '/js/artwork-size-check.js';
	wp_enqueue_script('wholesale-artwork-size-check', get_template_directory_uri() . '/js/artwork-size-check.js', array('wholesale-async-upload'), (string) filemtime($file), true);
	wp_script_add_data('wholesale-artwork-size-check', 'strategy', 'defer');
}
add_action('wp_enqueue_scripts', 'wholesale_artwork_size_check_script', 20);
