<?php
/**
 * "Edit product" pencil on product cards and the product page.
 *
 * Shown only to users who can edit that product. Logged-in visitors bypass
 * the page cache (wordpress_logged_in_ cookie), so cached HTML never holds it.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Pencil link to the product's wp-admin edit screen, or '' for everyone else.
 *
 * @param int    $product_id Product post ID.
 * @param string $modifier   Extra class suffix, e.g. 'page' for the product page.
 */
function wholesale_admin_edit_product_link($product_id, $modifier = '')
{
	$product_id = (int) $product_id;
	if (!$product_id || !is_user_logged_in() || !current_user_can('edit_post', $product_id)) {
		return '';
	}
	$url = get_edit_post_link($product_id, 'raw');
	if (!$url) {
		return '';
	}
	$label = sprintf('Edit product: %s', wp_strip_all_tags(get_the_title($product_id)));

	return sprintf(
		'<a class="sfs-edit-pencil%s" href="%s" title="Edit product" aria-label="%s"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></a>',
		$modifier ? ' sfs-edit-pencil--' . sanitize_html_class($modifier) : '',
		esc_url($url),
		esc_attr($label)
	);
}

/**
 * Pencil styles, printed only for users who can edit products.
 */
function wholesale_admin_edit_pencil_styles()
{
	if (is_admin() || !is_user_logged_in() || !current_user_can('edit_posts')) {
		return;
	}
	?>
	<style id="sfs-edit-pencil-css">
		.product-box, .sc-card-inner, .srp-card, .related-product { position: relative; }
		.sfs-edit-pencil {
			position: absolute; top: 10px; right: 10px; z-index: 6;
			display: inline-flex; align-items: center; justify-content: center;
			width: 34px; height: 34px; border-radius: 50%;
			background: #fff; color: #1d2327; border: 1px solid rgba(0, 0, 0, .15);
			box-shadow: 0 2px 6px rgba(0, 0, 0, .18); text-decoration: none;
			transition: background-color .15s, color .15s, transform .15s;
		}
		.sfs-edit-pencil:hover, .sfs-edit-pencil:focus-visible { background: #2271b1; color: #fff; transform: scale(1.06); }
		.sfs-edit-pencil:focus-visible { outline: 2px solid #2271b1; outline-offset: 2px; }
		.sfs-edit-pencil--page { position: static; vertical-align: middle; margin-left: 10px; width: 32px; height: 32px; }
		.related-product .sfs-edit-pencil { top: 6px; right: 6px; width: 30px; height: 30px; }
		@media (max-width: 575px) { .sfs-edit-pencil { top: 6px; right: 6px; width: 30px; height: 30px; } }
	</style>
	<?php
}
add_action('wp_head', 'wholesale_admin_edit_pencil_styles', 99);
