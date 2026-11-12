<?php
/**
 * Bulk status actions on wp-admin > Products.
 *
 * Adds two groups to the Bulk actions dropdown:
 *  - Set status: Published, Draft, Pending review, Private (the WordPress post status).
 *  - Set stock: In stock, Out of stock, Backorder (_stock_status, see inc/stock-status.php).
 *
 * Only the status is changed: post status through wp_update_post() with just the ID and
 * post_status, stock through the _stock_status meta (deleted for In stock, as the editor
 * stores it). Other meta, prices and options are not touched.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Post statuses offered, bulk action key => array(post status, label).
 */
function wholesale_product_bulk_post_statuses()
{
	return array(
		'sfs_status_publish' => array('publish', 'Published'),
		'sfs_status_draft' => array('draft', 'Draft'),
		'sfs_status_pending' => array('pending', 'Pending review'),
		'sfs_status_private' => array('private', 'Private'),
	);
}

/**
 * Stock statuses offered, bulk action key => array(stored _stock_status value, label).
 */
function wholesale_product_bulk_stock_statuses()
{
	return array(
		'sfs_stock_instock' => array('', 'In stock'),
		'sfs_stock_outofstock' => array('outofstock', 'Out of stock'),
		'sfs_stock_backorder' => array('backorder', 'Backorder'),
	);
}

add_filter('bulk_actions-edit-product', function ($actions) {
	// Nothing to set on trashed products; their view offers only Restore / Delete.
	if (isset($_REQUEST['post_status']) && 'trash' === $_REQUEST['post_status']) {
		return $actions;
	}
	$status_group = array();
	foreach (wholesale_product_bulk_post_statuses() as $key => $info) {
		if ('publish' === $info[0] && !current_user_can(get_post_type_object('product')->cap->publish_posts)) {
			continue;
		}
		$status_group[$key] = 'Status: ' . $info[1];
	}
	$stock_group = array();
	foreach (wholesale_product_bulk_stock_statuses() as $key => $info) {
		$stock_group[$key] = 'Stock: ' . $info[1];
	}
	// Arrays become <optgroup>s in the dropdown (WordPress 5.6+).
	$actions['Set status'] = $status_group;
	$actions['Set stock'] = $stock_group;
	return $actions;
});

add_filter('handle_bulk_actions-edit-product', function ($redirect_to, $doaction, $post_ids) {
	$post_statuses = wholesale_product_bulk_post_statuses();
	$stock_statuses = wholesale_product_bulk_stock_statuses();
	if (!isset($post_statuses[$doaction]) && !isset($stock_statuses[$doaction])) {
		return $redirect_to;
	}

	$is_stock = isset($stock_statuses[$doaction]);
	$changed = 0;
	$skipped = 0;
	foreach ((array) $post_ids as $post_id) {
		$post = get_post((int) $post_id);
		if (!$post || 'product' !== $post->post_type || 'trash' === $post->post_status || !current_user_can('edit_post', $post->ID)) {
			$skipped++;
			continue;
		}

		if ($is_stock) {
			$value = $stock_statuses[$doaction][0];
			$current = (string) get_post_meta($post->ID, '_stock_status', true);
			if ($current === $value) {
				continue;
			}
			if ('' === $value) {
				delete_post_meta($post->ID, '_stock_status');
			} else {
				update_post_meta($post->ID, '_stock_status', $value);
			}
			// The page cache and the OpenAI feed already purge on these meta hooks.
			$changed++;
			continue;
		}

		$status = $post_statuses[$doaction][0];
		if ($status === $post->post_status) {
			continue;
		}
		if ('publish' === $status && !current_user_can('publish_post', $post->ID)) {
			$skipped++;
			continue;
		}
		$update = array('ID' => $post->ID, 'post_status' => $status);
		// WordPress resets an unpublished product's date to "now" on every update; keep it
		// unless the product is being published (then today is the right publish date).
		if ('publish' !== $status) {
			$update['edit_date'] = true;
		}
		$result = wp_update_post($update, true);
		if (is_wp_error($result) || !$result) {
			$skipped++;
			continue;
		}
		$changed++;
	}

	$label = $is_stock ? $stock_statuses[$doaction][1] : $post_statuses[$doaction][1];
	return add_query_arg(array(
		'sfs_bulk_status_changed' => $changed,
		'sfs_bulk_status_skipped' => $skipped,
		'sfs_bulk_status_kind' => $is_stock ? 'stock' : 'status',
		'sfs_bulk_status_to' => rawurlencode($label),
	), remove_query_arg(array('sfs_bulk_status_changed', 'sfs_bulk_status_skipped', 'sfs_bulk_status_kind', 'sfs_bulk_status_to'), $redirect_to));
}, 10, 3);

add_action('admin_notices', function () {
	global $pagenow, $typenow;
	if ('edit.php' !== $pagenow || 'product' !== $typenow || !isset($_GET['sfs_bulk_status_changed'])) {
		return;
	}
	$changed = absint($_GET['sfs_bulk_status_changed']);
	$skipped = absint($_GET['sfs_bulk_status_skipped'] ?? 0);
	$kind = isset($_GET['sfs_bulk_status_kind']) && 'stock' === $_GET['sfs_bulk_status_kind'] ? 'stock' : 'status';
	$to = sanitize_text_field(rawurldecode(wp_unslash($_GET['sfs_bulk_status_to'] ?? '')));

	$message = sprintf(
		'%d product%s set to %s%s.',
		$changed,
		1 === $changed ? '' : 's',
		$to,
		'stock' === $kind ? '' : ' status'
	);
	if ($skipped) {
		$message .= sprintf(' %d skipped (not allowed or could not be saved).', $skipped);
	}
	printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $skipped ? 'warning' : 'success', esc_html($message));
});

add_filter('removable_query_args', function ($args) {
	return array_merge($args, array('sfs_bulk_status_changed', 'sfs_bulk_status_skipped', 'sfs_bulk_status_kind', 'sfs_bulk_status_to'));
});
