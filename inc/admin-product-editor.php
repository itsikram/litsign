<?php
/**
 * Product edit screen (wp-admin → Products → Edit / Add New).
 *
 * A friendlier layout on top of the existing fields: section tabs, plain-language
 * labels and help, a visual editor for options / channel letter data / product
 * group links, a live price example and a check before Update.
 *
 * Saved data does not change: the same meta keys, input names and JSON shape are
 * used, and a JSON field that wasn't edited is sent back exactly as it was stored.
 * The JS (js/admin-product-editor.js) only rewrites a hidden JSON field once
 * something in that editor is changed. To roll back, remove the require line for
 * this file in functions.php.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Whether the current admin screen is the product edit / new product screen.
 */
function wholesale_pe_is_product_screen()
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	return $screen && 'post' === $screen->base && 'product' === $screen->post_type;
}

/**
 * Plain-language labels and help text for the CMB2 fields. Only the name, help
 * text and input hints change; ids, types, defaults and saving stay the same.
 */
function wholesale_pe_field_copy()
{
	return array(
		'product_details' => array(
			'_order_by_index' => array('Sort position', 'Lower numbers show first in product lists. Example: 10. Products left at 999999 go last.', '999999', 'numeric'),
			'_min_height' => array('Minimum height (inches)', 'Smallest height a customer can order with the size calculator, in inches. Empty means 6. Example: 6', '1', 'decimal'),
			'_max_height' => array('Maximum height (inches)', 'Largest height a customer can type into the size calculator. Example: 120', '100', 'decimal'),
			'_min_width' => array('Minimum width (inches)', 'Smallest width a customer can order with the size calculator, in inches. Empty means 6. Example: 6', '1', 'decimal'),
			'_max_width' => array('Maximum width (inches)', 'Largest width a customer can type into the size calculator. Example: 120', '100', 'decimal'),
			'_min_sqft' => array('Minimum square feet', 'Used for the "starting at" price and for per-sq-ft options. When the calculator is hidden, the base price is minimum sq ft × price per sq ft. Example: 2', '1', 'decimal'),
			'_price_per_sqft' => array('Price per square foot ($)', 'Base price for one square foot, before options and discount. When the calculator is hidden this works as the item price (with minimum sq ft 1). Example: 2.25', '2.25', 'decimal'),
			'_discount_percent' => array('Everyday price adjustment (%)', 'Taken off every price this product shows and the whole cart line, after quantity. Shoppers only see the result, never a sale. 0 to 100. Example: 10. Run sales from Settings &gt; Storefront Sign &gt; Sale promotion.', '0', 'decimal'),
			'_min_order_price' => array('Minimum order price ($)', 'Size calculator products only. The lowest price one item can cost, after the everyday price adjustment. Smaller sizes (down to the minimum width and height) can still be ordered and pay this price. Leave empty for the standard $15. Enter 0 for no minimum. Example: 25', '15', 'decimal'),
			'_starting_at_text' => array('"Starting at" price text', 'Shown on product cards and at the top of the product page, as the everyday price. Example: $1.98 per ft². A crossed-out &lt;del&gt; price is not shown; during a sale the price is crossed out automatically.'),
			'_starting_at_options' => array('"Starting at" option name', 'Optional. The option (attribute id) whose choices are listed on channel letter cards. Example: height'),
			'_product_list_desc' => array('List description', 'Short text shown on the product card in shop and category lists.'),
			'_product_short_desc' => array('Short description', 'Shown near the top of the product page, beside the price.'),
			'_product_review_text' => array('Featured review text', 'Optional short review quote shown on the product page.'),
			'_product_gallery' => array('Product gallery images', 'Extra photos shown in the product page gallery. Drag to reorder.'),
			'_product_group_data' => array('Product group links (raw)', 'Buttons that switch between related versions of this product. Edited with the list above; this raw box is kept for reference.'),
			'_hide_calculator' => array('Hide the size calculator', 'Ticked: customers pick from the options only (fixed-size product). Not ticked: customers type a width and height and pay per square foot.'),
			'_size_presets' => array('Show preset size dropdown', 'Products with the size calculator: ticked shows a size dropdown (from its size options, plus Custom size) that fills in the width and height. Price is still per square foot.'),
			'_show_in_list' => array('Show in product lists', 'Ticked: the product appears in shop and category lists.'),
			'_has_upload_artwork' => array('Allow artwork upload', 'Ticked: customers can upload their artwork file on the product page.'),
			'_product_turnaround' => array('Production time (business days)', 'Shown as the turnaround on the product page. Example: 2'),
			'_trimcap_color' => array('Default trimcap color', 'Channel letters only. Leave empty if the product has a trimcap color option. Example: Black'),
			'_return_color' => array('Return color matches face color', 'Channel letters only. Ticked: the return uses the face color instead of its own choice.'),
			'_info_content_return_text' => array('Return info text', 'Channel letters only. Help text shown next to the return choice.'),
			'_info_content_return_image' => array('Return info image', 'Channel letters only. Picture shown next to the return choice.'),
			'_info_content_trimcap_text' => array('Trimcap info text', 'Channel letters only. Help text shown next to the trimcap choice.'),
			'_info_content_trimcap_image' => array('Trimcap info image', 'Channel letters only. Picture shown next to the trimcap choice.'),
			'_info_content_face_text' => array('Face info text', 'Channel letters only. Help text shown next to the face choice.'),
			'_info_content_face_image' => array('Face info image', 'Channel letters only. Picture shown next to the face choice.'),
		),
		'product_additional_info' => array(
			'_product_description' => array('Full description', 'The "Description" tab on the product page.'),
			'_product_component' => array('Components', 'The "Components" tab: what is included.'),
			'_product_warrenty' => array('Warranty', 'The "Warranty" tab on the product page.'),
			'_product_faq' => array('FAQ', 'The "FAQ" tab on the product page.'),
			'_product_manual' => array('Manual / instructions', 'The "Manual" tab on the product page.'),
		),
		'product_extra_options' => array(
			'_is_ps_option' => array('Offer a power supply', 'Channel letter builder: shows the power supply choice.'),
			'_is_lit_option' => array('Offer back lighting', 'Channel letter builder: shows the back-lit choice.'),
			'_is_cable_option' => array('Offer an 8 ft cable', 'Channel letter builder: shows the cable choice.'),
			'_standard_ps_cost' => array('Power supply price ($)', 'Flat amount added once when the customer picks a standard power supply. Example: 90', '90', 'decimal'),
			'_backlit_cost' => array('Back-lit upcharge (% of letter price)', 'A percentage, not dollars: 20 adds 20% of the letters price when back lighting is picked. Example: 20', '20', 'decimal'),
			'_eight_ft_cable_cost' => array('8 ft cable price ($)', 'Flat amount added once when the customer picks the cable. Example: 70', '70', 'decimal'),
			'_has_trimcap' => array('Has a trimcap choice', 'Channel letters: shows the trimcap section on the product page.'),
			'_has_return' => array('Has a return choice', 'Channel letters: shows the return section on the product page.'),
			'_has_face' => array('Has a face choice', 'Channel letters: shows the face section on the product page.'),
			'_default_face' => array('Default face color', 'Color picked when the builder opens. Example: White'),
			'_default_color_cost' => array('Default face color cost ($ per inch of height)', 'Used when no face color is chosen: added per letter for each inch of letter height. Example: 0', '0', 'decimal'),
			'_default_return' => array('Default return color', 'Return color picked when the builder opens. Example: Gloss Black'),
			'_default_trimcap' => array('Default trimcap color', 'Trimcap color picked when the builder opens. Example: Black'),
		),
	);
}

add_action('cmb2_admin_init', function () {
	$titles = array(
		'product_details' => 'Product details',
		'product_additional_info' => 'Descriptions and FAQ',
		'product_extra_options' => 'Channel letter extras and defaults',
	);
	foreach (wholesale_pe_field_copy() as $box_id => $fields) {
		$box = cmb2_get_metabox($box_id);
		if (!$box) {
			continue;
		}
		$box->set_prop('title', $titles[$box_id]);
		foreach ($fields as $field_id => $copy) {
			$box->update_field_property($field_id, 'name', $copy[0]);
			$box->update_field_property($field_id, 'desc', $copy[1]);
			if (isset($copy[2])) {
				$box->update_field_property($field_id, 'attributes', array(
					'placeholder' => $copy[2],
					'inputmode' => $copy[3],
					'autocomplete' => 'off',
				));
			}
		}
	}
}, 20);

/**
 * CMB2 deletes a meta row when the field is saved empty. A row that is already
 * stored as '' is left in place, so an untouched Update doesn't remove it
 * (get_post_meta() reads '' either way).
 */
add_filter('cmb2_override_meta_remove', function ($override, $args) {
	if (null !== $override || 'post' !== ($args['type'] ?? '') || 'product' !== get_post_type($args['id'] ?? 0)) {
		return $override;
	}
	$key = $args['field_id'] ?? '';
	if ('' !== $key && metadata_exists('post', $args['id'], $key) && array('') === get_post_meta($args['id'], $key, false)) {
		return true;
	}
	return $override;
}, 10, 2);

/**
 * A text or rich text field sent back exactly as stored keeps its stored bytes.
 * CMB2 would otherwise run it through its cleaning again on every Update (for
 * rich text, wp_kses_post() drops attributes such as decoding="async"). Changed
 * values are cleaned as before. CMB2 works with slashed values here.
 */
function wholesale_pe_keep_unchanged_value($override, $value, $object_id, $field_args)
{
	if (null !== $override || !is_string($value) || empty($field_args['id']) || 'product' !== get_post_type($object_id)) {
		return $override;
	}
	$stored = get_post_meta($object_id, $field_args['id'], false);
	if (array(wp_unslash($value)) === $stored && is_string($stored[0])) {
		return $value;
	}
	return $override;
}
foreach (array('text', 'text_small', 'text_medium', 'textarea', 'textarea_small', 'wysiwyg') as $wholesale_pe_type) {
	add_filter("cmb2_sanitize_{$wholesale_pe_type}", 'wholesale_pe_keep_unchanged_value', 10, 4);
}
unset($wholesale_pe_type);

/**
 * Swap the render callbacks of the options and channel letter boxes, keeping
 * their ids and positions. (remove_meta_box() + add_meta_box() with the same
 * id would not re-add a removed box.)
 */
add_action('add_meta_boxes_product', function () {
	global $wp_meta_boxes;
	$swap = array(
		'product_attr' => array('Product options (attributes)', 'wholesale_pe_render_attr_box'),
		'product_cl_data' => array('Channel letter price table and colors', 'wholesale_pe_render_cl_box'),
	);
	foreach ($swap as $id => $box) {
		foreach (array('normal', 'advanced', 'side') as $context) {
			foreach (array('high', 'core', 'default', 'low') as $priority) {
				if (!empty($wp_meta_boxes['product'][$context][$priority][$id])) {
					$wp_meta_boxes['product'][$context][$priority][$id]['title'] = $box[0];
					$wp_meta_boxes['product'][$context][$priority][$id]['callback'] = $box[1];
				}
			}
		}
	}
}, 20);

/**
 * Options box. The hidden #productAttrJson holds the stored value untouched;
 * the editor UI is built by JS from the JSON in the data attribute.
 */
function wholesale_pe_render_attr_box($post)
{
	$product_attr = (string) get_post_meta($post->ID, 'product_attr', true);
	$has_upload_artwork = get_post_meta($post->ID, '_has_upload_artwork', true);
	$hide_calculator = get_post_meta($post->ID, '_hide_calculator', true);
	$show_in_list = get_post_meta($post->ID, '_show_in_list', true);
	$decoded = json_decode($product_attr);
	$readable = is_array($decoded);
	$unreadable = '' !== trim($product_attr) && '[{' !== $product_attr && !$readable;
	?>
	<input type="hidden" name="has_custom_artwork" value="<?php echo esc_attr($has_upload_artwork); ?>" id="hasCustomArtwork">
	<input type="hidden" name="hide_calculator" value="<?php echo esc_attr($hide_calculator); ?>" id="hideCalculator">
	<input type="hidden" name="show_in_list" value="<?php echo esc_attr($show_in_list); ?>" id="showInList">
	<input type="hidden" name="product_attr" id="productAttrJson" value="<?php echo esc_attr($readable ? $product_attr : ''); ?>">
	<?php if ($unreadable) : ?>
		<div class="notice notice-error inline"><p><strong>This product's options are damaged and can't be shown.</strong> The product page shows no option selectors. The stored options are kept as they are until you add options below and update.</p></div>
	<?php endif; ?>
	<div id="sso-attr-editor" class="sso-pe-editor" data-kind="attr"><p class="sso-pe-loading">Loading the options editor…</p></div>
	<noscript><p>The options editor needs JavaScript.</p></noscript>
	<?php
}

/**
 * Channel letter data box. The stored JSON is escaped into the field so it
 * comes back byte for byte (the old box printed it unescaped, which cut it at
 * the first quote and gave the legacy '[{' value).
 */
function wholesale_pe_render_cl_box($post)
{
	$product_cl_data = (string) get_post_meta($post->ID, 'product_cl_data', true);
	$value = '[{' === $product_cl_data ? '[]' : $product_cl_data;
	$readable = '' === trim($value) || is_array(json_decode($value));
	?>
	<input type="hidden" name="product_cl_data" id="productClJson" value="<?php echo esc_attr($value); ?>">
	<?php if (!$readable) : ?>
		<div class="notice notice-error inline"><p><strong>This product's channel letter data can't be read.</strong> It is kept as it is unless you rebuild it below and update.</p></div>
	<?php endif; ?>
	<div id="sso-cl-editor" class="sso-pe-editor" data-kind="cl"><p class="sso-pe-loading">Loading the channel letter editor…</p></div>
	<?php
}

/**
 * Save wrappers. The original handlers in functions.php still do the saving;
 * these only skip a value that wasn't changed (so an untouched Update leaves
 * the stored bytes alone) and refuse JSON that can't be read.
 */
add_action('init', function () {
	if (false !== has_action('save_post', 'save_product_attr_meta')) {
		remove_action('save_post', 'save_product_attr_meta');
		add_action('save_post', 'wholesale_pe_save_product_attr');
	}
	if (false !== has_action('save_post', 'save_product_cl_meta')) {
		remove_action('save_post', 'save_product_cl_meta');
		add_action('save_post', 'wholesale_pe_save_product_cl');
	}
});

/**
 * Same checks and cleaning as save_product_attr_meta(), except that an option
 * group sent back exactly as stored is kept as stored. Otherwise the cleaning
 * (which appends the name to options sharing a bare price) would also rewrite
 * older groups nobody edited.
 */
function wholesale_pe_save_product_attr($post_id)
{
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id) || !isset($_POST['product_attr'])) {
		return;
	}
	$raw = trim(wp_unslash((string) $_POST['product_attr']));
	$stored = (string) get_post_meta($post_id, 'product_attr', true);
	if ('' === $raw || '[{' === $raw || $raw === $stored) {
		return;
	}
	$attrs = json_decode($raw, true);
	if (!is_array($attrs)) {
		set_transient('wholesale_attr_save_error_' . get_current_user_id(), $post_id, 60);
		return;
	}
	$clean = wholesale_normalize_product_attrs($attrs);

	$old_by_name = array();
	$old = json_decode($stored, true);
	foreach (is_array($old) ? $old : array() as $old_attr) {
		if (is_array($old_attr) && isset($old_attr['name']) && !isset($old_by_name[(string) $old_attr['name']])) {
			$old_by_name[(string) $old_attr['name']] = $old_attr;
		}
	}
	$sent_by_name = array();
	foreach ($attrs as $attr) {
		if (is_array($attr) && isset($attr['name']) && !isset($sent_by_name[(string) $attr['name']])) {
			$sent_by_name[(string) $attr['name']] = $attr;
		}
	}
	foreach ($clean as $i => $attr) {
		$name = $attr['name'];
		if (isset($old_by_name[$name], $sent_by_name[$name]) && $sent_by_name[$name] === $old_by_name[$name]) {
			$clean[$i] = $old_by_name[$name];
		}
	}
	update_post_meta($post_id, 'product_attr', wp_slash(wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
}

function wholesale_pe_save_product_cl($post_id)
{
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id) || !isset($_POST['product_cl_data'])) {
		return;
	}
	$raw = wp_unslash((string) $_POST['product_cl_data']);
	$stored = (string) get_post_meta($post_id, 'product_cl_data', true);
	if ('[{' === $raw || $raw === $stored || ('[{' === $stored && '[]' === $raw)) {
		return;
	}
	// Same cleaning as save_product_cl_meta(); keep the previous value if the result can't be read.
	$clean = sanitize_text_field($_POST['product_cl_data']);
	if (!is_array(json_decode(wp_unslash($clean), true))) {
		set_transient('wholesale_cl_save_error_' . get_current_user_id(), $post_id, 60);
		return;
	}
	update_post_meta($post_id, 'product_cl_data', $clean);
}

add_action('admin_notices', function () {
	$key = 'wholesale_cl_save_error_' . get_current_user_id();
	if ($post_id = get_transient($key)) {
		delete_transient($key);
		echo '<div class="notice notice-error"><p>The channel letter data for &ldquo;' . esc_html(get_the_title($post_id)) . '&rdquo; was not saved because it could not be read. The previous data was kept.</p></div>';
	}
});

add_action('admin_enqueue_scripts', function ($hook) {
	if (!in_array($hook, array('post.php', 'post-new.php'), true) || !wholesale_pe_is_product_screen()) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('wholesale-product-editor', $uri . '/css/admin-product-editor.css', array(), (string) filemtime($dir . '/css/admin-product-editor.css'));
	wp_enqueue_script('wholesale-product-editor', $uri . '/js/admin-product-editor.js', array('jquery', 'jquery-ui-sortable'), (string) filemtime($dir . '/js/admin-product-editor.js'), true);

	$post = get_post();
	$is_new = !$post || 'auto-draft' === $post->post_status;

	// The values actually stored, so the screen shows them instead of CMB2 defaults
	// (CMB2 shows a field's default when the stored value is empty, and saving
	// would then write that default in).
	$stored = array();
	if (!$is_new) {
		foreach (wholesale_pe_field_copy() as $fields) {
			foreach (array_keys($fields) as $key) {
				$stored[$key] = metadata_exists('post', $post->ID, $key) ? get_post_meta($post->ID, $key, true) : null;
			}
		}
		$stored = array_filter($stored, static function ($value) {
			return null === $value || is_scalar($value);
		});
	}

	$terms = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false));
	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => array('publish', 'draft', 'private', 'pending'),
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
		'no_found_rows' => true,
	));

	wp_localize_script('wholesale-product-editor', 'ssoProductEditor', array(
		'postId' => $post ? (int) $post->ID : 0,
		'isNew' => $is_new,
		'stored' => $is_new ? new stdClass() : $stored,
		'postTerms' => $post ? wp_get_object_terms($post->ID, 'product_category', array('fields' => 'ids')) : array(),
		'terms' => is_array($terms) ? array_map(static function ($term) {
			return array('id' => (int) $term->term_id, 'slug' => $term->slug, 'name' => $term->name);
		}, $terms) : array(),
		'products' => array_map(static function ($product) {
			return array('slug' => $product->post_name, 'title' => html_entity_decode(get_the_title($product), ENT_QUOTES, 'UTF-8'), 'status' => $product->post_status);
		}, $products),
		'sqftMinPrice' => defined('WHOLESALE_SQFT_MIN_PRICE') ? WHOLESALE_SQFT_MIN_PRICE : 15,
		'racewayPerFoot' => defined('WHOLESALE_RACEWAY_COST_PER_FOOT') ? WHOLESALE_RACEWAY_COST_PER_FOOT : 50,
	));
});
