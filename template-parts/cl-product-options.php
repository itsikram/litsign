<?php
/**
 * Channel letter product page: the builder's options as an order form.
 *
 * Every option comes from the settings the builder reads (wholesale_cl_page_options():
 * product_cl_data plus the lighting, power supply and cable meta), and the price is
 * the builder's own formula (wholesale_cl_page_design() -> wholesale_cl_design_quote()),
 * so the product page and the builder always offer and charge the same thing.
 * js/cl-product-options.js runs the preview, the width estimate and the raceway length.
 *
 * Expects from single-product.php: $cl_options, $product_min_price, $product_price,
 * $product_trimcap_color, $is_same_return_color.
 *
 * @package litsign
 */

$cl_row = static function ($label, $for, $control, $info = '') {
	?>
	<div class="row align-items-center mt-2 cl-opt-row">
		<div class="col-md-3 col-4">
			<?php if ($for) : ?>
				<label for="<?php echo esc_attr($for); ?>"><?php echo esc_html($label); ?></label>
			<?php else : ?>
				<span class="cl-opt-label"><?php echo esc_html($label); ?></span>
			<?php endif; ?>
			<?php if ($info) : ?>
				<span class="option-info-container">
					<span class="option-info-button" role="button" tabindex="0" aria-label="<?php echo esc_attr('About ' . strtolower($label)); ?>">i</span>
					<span class="option-info-content"><span class="option-info-text"><?php echo esc_html($info); ?></span></span>
				</span>
			<?php endif; ?>
		</div>
		<div class="col-8 col-md-9"><?php echo $control; // Built below from escaped parts. ?></div>
	</div>
	<?php
};

/** A select of swatches: value = title, data-color = the color code for the preview. */
$cl_color_select = static function ($name, $id, $list) {
	$html = '<span class="cl-opt-color"><span class="cl-opt-swatch" aria-hidden="true"></span><select name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="form-select cl-opt" required>';
	foreach ($list as $item) {
		$html .= '<option value="' . esc_attr($item['title']) . '" data-color="' . esc_attr($item['code']) . '">' . esc_html($item['title']) . '</option>';
	}
	return $html . '</select></span>';
};

$cl_plain_select = static function ($name, $id, $choices) {
	$html = '<select name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="form-select cl-opt">';
	foreach ($choices as $value => $label) {
		$html .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
	}
	return $html . '</select>';
};

$cl_fixed = static function ($text) {
	return '<span class="cl-opt-fixed">' . esc_html($text) . '</span>';
};
?>
<div class="product-attibute-box mb-3 cl-product-options" data-cl-options>
	<input type="hidden" value="<?php echo esc_attr($product_trimcap_color); ?>" name="default_trimcap_color" id="trimcapColor">
	<input type="hidden" value="<?php echo esc_attr($is_same_return_color); ?>" name="is_same_return_color" id="returnColorSame">
	<input type="hidden" name="total_cost" value="<?php echo esc_attr($product_min_price > 0 ? $product_min_price : $product_price); ?>" id="totalCost">

	<div class="row">
		<div class="col mobile-s-0">
			<div id="letter-output" class="text-center fs-1 py-3" aria-hidden="true">Enter Your Text</div>
		</div>
	</div>
	<div class="row my-3">
		<div class="col">
			<label for="letterInput" class="visually-hidden">Sign text</label>
			<input type="text" id="letterInput" name="letters" required maxlength="120" placeholder="Enter Your Text Here" class="form-control fw-bold" autocomplete="off">
			<p class="form-text m-0">For an instant quote - under 30 seconds. Need several lines, a logo or shapes? Use <a href="<?php echo esc_url(add_query_arg('product_id', get_the_ID(), home_url('/channel-letter-builder/'))); ?>">the builder</a>.</p>
		</div>
	</div>

	<?php
	// Font: the builder's full font list.
	$font_html = '<select id="select-font" name="font" class="form-select cl-opt avoid-price" data-default="Arial">';
	foreach (wholesale_cl_fonts() as $group => $fonts) {
		$font_html .= '<optgroup label="' . esc_attr($group) . '">';
		foreach ($fonts as $title => $code) {
			$font_html .= '<option value="' . esc_attr($code) . '" style="font-family:' . esc_attr($code) . '">' . esc_html($title) . '</option>';
		}
		$font_html .= '</optgroup>';
	}
	$font_html .= '</select><div class="form-text">Cursive and complex logos: use the builder or send your file for a quote.</div>';
	$cl_row('Font', 'select-font', $font_html);

	// Letter height: the sizes in the product's cost table.
	$default_height = in_array(12, $cl_options['heights'], true) ? 12 : $cl_options['heights'][0];
	$height_html = '<select name="cl_height" id="clHeight" class="form-select cl-opt" required data-default="' . esc_attr($default_height) . '">';
	foreach ($cl_options['heights'] as $inches) {
		$height_html .= '<option value="' . esc_attr($inches) . '"' . selected($default_height, $inches, false) . '>' . esc_html($inches . ' Inch') . '</option>';
	}
	$height_html .= '</select><span id="clApproxWidth" class="form-text d-block" aria-live="polite">Approximate width: <b>0 Inches</b></span>';
	$cl_row('Letter height', 'clHeight', $height_html, 'The height of a capital letter. Lowercase letters are sized in proportion.');

	// Face color: every color group the builder offers (acrylic, 3M vinyl...).
	$face_count = array_sum(array_map(static function ($group) {
		return count($group['options']);
	}, $cl_options['face_groups']));
	if (1 === $face_count) {
		$only = $cl_options['face_groups'][0];
		$face_html = '<input type="hidden" name="cl_face" id="clFace" value="' . esc_attr($only['id'] . '|' . $only['options'][0]['title']) . '" data-color="' . esc_attr($only['options'][0]['code']) . '">' . $cl_fixed($only['options'][0]['title']);
	} else {
		$face_html = '<span class="cl-opt-color"><span class="cl-opt-swatch" aria-hidden="true"></span><select name="cl_face" id="clFace" class="form-select cl-opt" required>';
		foreach ($cl_options['face_groups'] as $group) {
			$face_html .= '<optgroup label="' . esc_attr($group['heading']) . '">';
			foreach ($group['options'] as $item) {
				$face_html .= '<option value="' . esc_attr($group['id'] . '|' . $item['title']) . '" data-color="' . esc_attr($item['code']) . '">' . esc_html($item['title']) . '</option>';
			}
			$face_html .= '</optgroup>';
		}
		$face_html .= '</select></span>';
	}
	$cl_row('Face color', 'clFace', $face_html, 'The face is the colored front of each letter that lights up.');

	// Return (the side wall of each letter).
	if ($cl_options['return_same_as_face']) {
		$cl_row('Return color', '', $cl_fixed('Same as face color'), 'The return is the metal side wall of each channel letter.');
	} elseif ($cl_options['return_colors']) {
		$cl_row('Return color', 'clReturn', count($cl_options['return_colors']) > 1 ? $cl_color_select('cl_return', 'clReturn', $cl_options['return_colors']) : '<input type="hidden" name="cl_return" id="clReturn" value="' . esc_attr($cl_options['return_colors'][0]['title']) . '" data-color="' . esc_attr($cl_options['return_colors'][0]['code']) . '">' . $cl_fixed($cl_options['return_colors'][0]['title']), 'The return is the metal side wall of each channel letter.');
	}
	if ($cl_options['return_sizes']) {
		$cl_row('Return depth', 'clReturnSize', $cl_plain_select('cl_return_size', 'clReturnSize', array_column($cl_options['return_sizes'], 'title', 'title')));
	}

	// Trimcap (aluminum letters only).
	if ($cl_options['trimcap_colors']) {
		$cl_row('Trimcap color', 'clTrimcap', $cl_color_select('cl_trimcap', 'clTrimcap', $cl_options['trimcap_colors']), 'Trimcap is the plastic molding that holds the acrylic face to the return.');
	}
	if ($cl_options['trimcap_sizes']) {
		$cl_row('Trimcap size', 'clTrimcapSize', $cl_plain_select('cl_trimcap_size', 'clTrimcapSize', array_column($cl_options['trimcap_sizes'], 'title', 'title')));
	}

	// Lighting, power and mounting: the builder's bottom bar.
	if ($cl_options['lit']) {
		$cl_row('Lighting', 'clLit', $cl_plain_select('cl_lit', 'clLit', array('front' => 'Front Lit', 'both' => 'Front and Back Lit')));
	}
	if ($cl_options['power_supply']) {
		$cl_row('Power supply', 'clPowerSupply', $cl_plain_select('cl_power_supply', 'clPowerSupply', array('standard' => 'Standard', 'none' => 'None')), 'Standard: 12V / 5A 60W, Class 2, IP67, 5-year warranty. Transformer box for dry locations only.');
	}
	if ($cl_options['cable']) {
		$cl_row('LED lights', 'clCable', $cl_plain_select('cl_cable', 'clCable', array('3' => '3ft Cable', '8' => '8ft Cable', '0' => 'No LED (no UL label)')));
	}
	$raceway_html = $cl_plain_select('cl_raceway', 'clRaceway', array('no' => 'No raceway', 'yes' => 'Add raceway'))
		. '<label class="cl-opt-raceway-length mt-2" for="clRacewayLength" hidden>Raceway length'
		. '<span class="cl-opt-unit"><input type="number" name="cl_raceway_length" id="clRacewayLength" class="form-control" min="12" max="1200" step="1" inputmode="numeric" disabled><em>in</em></span></label>';
	$cl_row('Raceway', 'clRaceway', $raceway_html, 'A raceway is a metal box the letters mount on, so the wall needs only one power penetration. Priced per foot of length.');
	?>
</div>
