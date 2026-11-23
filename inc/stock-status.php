<?php
/**
 * Product stock status: In stock (the default), Out of stock or Backorder.
 *
 * Set per product in the editor (Visibility and settings tab). An empty value means
 * In stock, so products saved before this field existed keep their meta unchanged.
 * Read by the Merchant feed, the product schema, the product page and
 * Cart::add_item(), which refuses out of stock products.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Stock status choices, stored value => label. '' is In stock.
 */
function wholesale_stock_status_options()
{
	return array(
		'' => 'In stock',
		'outofstock' => 'Out of stock',
		'backorder' => 'Backorder',
	);
}

/**
 * The product's stock status: 'instock', 'outofstock' or 'backorder'.
 */
function wholesale_product_stock_status($product_id)
{
	$status = (string) get_post_meta($product_id, '_stock_status', true);
	return in_array($status, array('outofstock', 'backorder'), true) ? $status : 'instock';
}

/**
 * Date a backordered product is expected to ship, as YYYY-MM-DD (site time). Uses the
 * product's _backorder_date when it is a valid future date, else 14 days from today,
 * because Google Merchant Center rejects a backorder item without a date.
 */
function wholesale_product_backorder_date($product_id)
{
	$today = wp_date('Y-m-d');
	$date = trim((string) get_post_meta($product_id, '_backorder_date', true));
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && $date > $today) {
		return $date;
	}
	return wp_date('Y-m-d', strtotime('+14 days'));
}

/**
 * Products list in wp-admin: a Stock column, so out of stock products stand out.
 */
add_filter('manage_product_posts_columns', function ($columns) {
	$columns['wholesale_stock'] = 'Stock';
	return $columns;
});

add_action('manage_product_posts_custom_column', function ($column, $post_id) {
	if ('wholesale_stock' !== $column) {
		return;
	}
	$status = wholesale_product_stock_status($post_id);
	$labels = array('instock' => 'In stock', 'outofstock' => 'Out of stock', 'backorder' => 'Backorder');
	$colors = array('instock' => '#1a7f37', 'outofstock' => '#b32d2e', 'backorder' => '#9a6700');
	printf('<span style="color:%s;font-weight:600">%s</span>', esc_attr($colors[$status]), esc_html($labels[$status]));
	if ('backorder' === $status) {
		echo '<br><small>' . esc_html((new DateTimeImmutable(wholesale_product_backorder_date($post_id), wp_timezone()))->format('M j, Y')) . '</small>';
	}
}, 10, 2);
