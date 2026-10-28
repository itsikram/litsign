<?php
/**
 * Per-page Google Ads conversion: a "Google Ads conversion" box on the edit
 * screen of pages, posts, products and other public post types. When a label
 * is set, viewing that page sends the conversion (e.g. a thank-you page).
 * Stored as _wholesale_gtag_label meta; empty means no conversion.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

const WHOLESALE_PAGE_CONVERSION_META = '_wholesale_gtag_label';

/**
 * Post types that get the box: every public type with an edit screen.
 *
 * @return string[]
 */
function wholesale_page_conversion_post_types()
{
	$types = get_post_types(array('public' => true, 'show_ui' => true));
	unset($types['attachment']);

	return array_values($types);
}

/**
 * Clean a pasted label. Accepts the bare label or the full
 * "AW-18454059893/label" send_to value from Google Ads.
 */
function wholesale_page_conversion_clean_label($label)
{
	$label = trim((string) $label);
	if (preg_match('#^AW-\d+/(.+)$#i', $label, $match)) {
		$label = $match[1];
	}

	return preg_replace('/[^A-Za-z0-9_-]/', '', $label);
}

add_action('add_meta_boxes', function ($post_type) {
	if (!current_user_can('manage_options') || !in_array($post_type, wholesale_page_conversion_post_types(), true)) {
		return;
	}
	add_meta_box('wholesale-gtag-label', 'Google Ads conversion', 'wholesale_page_conversion_metabox_render', $post_type, 'side', 'default');
});

function wholesale_page_conversion_metabox_render($post)
{
	$label = (string) get_post_meta($post->ID, WHOLESALE_PAGE_CONVERSION_META, true);
	wp_nonce_field('wholesale_gtag_label', 'wholesale_gtag_label_nonce');
	?>
	<p>
		<label for="wholesale-gtag-label"><strong>Conversion label</strong></label>
		<input type="text" id="wholesale-gtag-label" name="wholesale_gtag_label" class="widefat code" value="<?php echo esc_attr($label); ?>" placeholder="e.g. AbC1dEfGhIjK_lMnO">
	</p>
	<p class="description">Sent to Google Ads (AW-18454059893) once each time someone views this page. Paste the label, or the whole <code>AW-&hellip;/label</code> value. Leave empty for no conversion.</p>
	<?php
}

add_action('save_post', function ($post_id, $post) {
	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
		return;
	}
	if (!isset($_POST['wholesale_gtag_label_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['wholesale_gtag_label_nonce'])), 'wholesale_gtag_label')) {
		return;
	}
	if (!in_array($post->post_type, wholesale_page_conversion_post_types(), true) || !current_user_can('manage_options') || !current_user_can('edit_post', $post_id)) {
		return;
	}

	$label = wholesale_page_conversion_clean_label(isset($_POST['wholesale_gtag_label']) ? wp_unslash($_POST['wholesale_gtag_label']) : '');
	// Only write on a change, so an untouched save leaves the meta as it was.
	if ($label === (string) get_post_meta($post_id, WHOLESALE_PAGE_CONVERSION_META, true)) {
		return;
	}
	if ('' === $label) {
		delete_post_meta($post_id, WHOLESALE_PAGE_CONVERSION_META);
	} else {
		update_post_meta($post_id, WHOLESALE_PAGE_CONVERSION_META, $label);
	}
}, 10, 2);

/**
 * The conversion label this page view sends, or '' when it sends none.
 */
function wholesale_page_conversion_label()
{
	if (!is_singular() || is_preview()) {
		return '';
	}

	$label = wholesale_page_conversion_clean_label(get_post_meta(get_queried_object_id(), WHOLESALE_PAGE_CONVERSION_META, true));
	if ('' === $label) {
		return '';
	}
	// The view a quote form returns to already sends the quote conversion.
	if (isset($_GET['quote_status']) && 'sent' === sanitize_key(wp_unslash($_GET['quote_status']))) {
		return '';
	}
	// Purchase, quote, call and click labels are sent by their own triggers.
	if (in_array($label, wholesale_ads_sitewide_labels(), true)) {
		return '';
	}

	return $label;
}

/**
 * Send this page's conversion. The event queues in dataLayer, so it goes out
 * once the deferred Google tag (wholesale_deferred_conversion_tracking) loads;
 * on these pages the tag starts right away (wholesale_ads_is_conversion_page()).
 */
function wholesale_page_conversion_script()
{
	$label = wholesale_page_conversion_label();
	if ('' === $label) {
		return;
	}
	?>
	<script>
		(function () {
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
			window.gtag('event', 'conversion', { send_to: <?php echo wp_json_encode('AW-18454059893/' . $label); ?> });
		})();
	</script>
	<?php
}
add_action('wp_footer', 'wholesale_page_conversion_script', 21);
