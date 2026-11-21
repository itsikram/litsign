<?php
/**
 * Extra tabs in the admin media popup (every wp.media window: hero photos, theme
 * images, page editor "Add Media", ...):
 *
 *  - Channel letter designs : the PNG previews saved by the channel letter builder.
 *  - Order artwork          : files customers attached to an order, each labelled
 *                             with the order number, customer, product and size.
 *  - Customer uploads       : files sent through the quote / contact forms, labelled
 *                             with who sent them and what they asked for.
 *
 * The tabs reuse the normal media grid (so selecting, searching and "Use this
 * image" work like the Media Library tab). Only administrators see them because the
 * labels carry customer names and emails. Files that are not in the Media Library yet
 * (form uploads) get an attachment record the first time the Customer uploads tab is
 * opened, so they can be picked like any other image.
 *
 * The script is js/media-source-tabs.js.
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Tab id => label shown in the popup. */
function retails_media_source_tabs()
{
	return array(
		'designs' => 'Channel letter designs',
		'artwork' => 'Order artwork',
		'uploads' => 'Customer uploads',
	);
}

function retails_media_source_allowed()
{
	return is_admin() && current_user_can('manage_options');
}

function retails_media_source_enqueue()
{
	if (!retails_media_source_allowed()) {
		return;
	}
	$file = get_template_directory() . '/js/media-source-tabs.js';
	wp_enqueue_script('retails-media-source-tabs', get_template_directory_uri() . '/js/media-source-tabs.js', array('media-views'), file_exists($file) ? (string) filemtime($file) : '1', true);
	$tabs = array();
	foreach (retails_media_source_tabs() as $source => $label) {
		$tabs[] = array('source' => $source, 'mode' => 'sfs_' . $source, 'label' => $label);
	}
	wp_add_inline_style('media-views', '.sfs-source-buttons{display:inline-flex;flex-wrap:wrap;gap:4px;margin:0 8px 0 0;vertical-align:middle}.sfs-source-buttons .button{min-height:28px;line-height:26px;padding:0 10px}.media-toolbar.sfs-has-sources{height:auto;min-height:50px;overflow:visible;padding-bottom:6px}.media-toolbar.sfs-has-sources .media-toolbar-secondary{float:none;max-width:none;display:flex;flex-wrap:wrap;align-items:center;gap:4px 0}.media-toolbar.sfs-has-sources .media-toolbar-primary{float:none;position:absolute;right:10px;top:10px}');
	wp_localize_script('retails-media-source-tabs', 'sfsMediaSources', array(
		'tabs' => $tabs,
		'empty' => array(
			'designs' => 'No channel letter designs saved yet.',
			'artwork' => 'No order has artwork attached yet.',
			'uploads' => 'No customer has uploaded a file through the quote or contact form yet.',
		),
	));
}
add_action('wp_enqueue_media', 'retails_media_source_enqueue');

/**
 * Orders that carry attachments: attachment id => list of array(order post id, item).
 */
function retails_media_source_order_links()
{
	static $links = null;
	if (null !== $links) {
		return $links;
	}
	$links = array('artwork' => array(), 'design' => array());
	if (!function_exists('retails_decode_order_meta_array')) {
		return $links;
	}
	$order_ids = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'orderby' => 'date',
		'order' => 'DESC',
		'no_found_rows' => true,
		'update_post_term_cache' => false,
	));
	foreach ($order_ids as $order_id) {
		$items = retails_decode_order_meta_array(get_post_meta($order_id, 'product_json', true));
		if (!is_array($items)) {
			continue;
		}
		if (isset($items['product_id'])) {
			$items = array($items);
		}
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			foreach (array('artwork' => 'artwork_id', 'design' => 'design_id') as $kind => $key) {
				$attachment_id = isset($item[$key]) ? absint($item[$key]) : 0;
				if ($attachment_id) {
					$links[$kind][$attachment_id][] = array((int) $order_id, $item);
				}
			}
		}
	}
	return $links;
}

/** Label lines for one order item. */
function retails_media_source_order_info($order_id, $item)
{
	$billing = function_exists('retails_decode_order_meta_array') ? retails_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true)) : array();
	$billing = is_array($billing) ? $billing : array();
	$name = trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? ''));
	$company = trim((string) ($billing['billing_company'] ?? ''));
	$number = function_exists('retails_order_number') ? retails_order_number($order_id) : (string) $order_id;
	$status = get_post_status_object(get_post_status($order_id));
	$details = isset($item['product_details']) && is_array($item['product_details']) ? $item['product_details'] : array();

	$size = '';
	if (!empty($details['Width']) && !empty($details['Height'])) {
		$size = trim($details['Width']) . ' × ' . trim($details['Height']);
	} elseif (!empty($details['Size'])) {
		$size = (string) $details['Size'];
	}
	$product = html_entity_decode(wp_strip_all_tags((string) ($item['product_title'] ?? '')), ENT_QUOTES, 'UTF-8');
	if (!empty($item['product_quantity'])) {
		$product .= ' × ' . (int) $item['product_quantity'];
	}

	$lines = array(array('Order', '#' . $number . ' · ' . get_the_date('M j, Y', $order_id) . ($status ? ' · ' . $status->label : '')));
	$who = trim($name . ($company ? ' (' . $company . ')' : ''));
	if ($who) {
		$lines[] = array('Customer', $who);
	}
	if (!empty($billing['billing_email'])) {
		$lines[] = array('Email', $billing['billing_email']);
	}
	if (!empty($billing['billing_tel'])) {
		$lines[] = array('Phone', $billing['billing_tel']);
	}
	if ($product) {
		$lines[] = array('Product', $product);
	}
	if (!empty($item['job_name']) && '' !== trim((string) $item['job_name'])) {
		$lines[] = array('Job name', trim((string) $item['job_name']));
	}
	if ($size) {
		$lines[] = array('Size', $size);
	}
	if (!empty($details['Turnaround Option'])) {
		$lines[] = array('Turnaround', (string) $details['Turnaround Option']);
	}
	if (isset($item['product_subtotal']) && '' !== $item['product_subtotal']) {
		$lines[] = array('Item total', '$' . number_format((float) $item['product_subtotal'], 2));
	}

	return array(
		'title' => '#' . $number . ($name ? ' · ' . $name : ''),
		'lines' => $lines,
		'link' => get_edit_post_link($order_id, 'raw'),
		'hay' => strtolower(implode(' ', array_map(static function ($line) {
			return $line[1];
		}, $lines))),
	);
}

/**
 * Make sure every image/PDF sent through the quote and contact forms is in the
 * Media Library. Returns attachment id => info.
 */
function retails_media_source_customer_uploads()
{
	$out = array();
	$submissions = get_posts(array(
		'post_type' => 'contact_submission',
		'post_status' => 'any',
		'posts_per_page' => 200,
		'orderby' => 'date',
		'order' => 'DESC',
		'meta_query' => array(array('key' => '_contact_logo', 'value' => '', 'compare' => '!=')),
		'no_found_rows' => true,
	));
	if (!$submissions) {
		return $out;
	}
	$uploads = wp_get_upload_dir();
	foreach ($submissions as $submission) {
		$url = (string) get_post_meta($submission->ID, '_contact_logo', true);
		if ('' === $url) {
			continue;
		}
		$attachment_id = (int) attachment_url_to_postid($url);
		if (!$attachment_id) {
			if (0 !== strpos($url, $uploads['baseurl'] . '/')) {
				continue;
			}
			$file = $uploads['basedir'] . '/' . ltrim(substr($url, strlen($uploads['baseurl'])), '/');
			if (!is_file($file)) {
				continue;
			}
			$type = wp_check_filetype($file);
			if (empty($type['type'])) {
				continue;
			}
			$name = (string) get_post_meta($submission->ID, '_contact_name', true);
			$attachment_id = wp_insert_attachment(array(
				'guid' => $url,
				'post_mime_type' => $type['type'],
				'post_title' => 'Customer upload' . ($name ? ' - ' . $name : ''),
				'post_content' => '',
				'post_status' => 'inherit',
				'post_date' => $submission->post_date,
				'post_date_gmt' => $submission->post_date_gmt,
			), $file, 0);
			if (!$attachment_id || is_wp_error($attachment_id)) {
				continue;
			}
			if (0 === strpos($type['type'], 'image/')) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$meta = wp_generate_attachment_metadata($attachment_id, $file);
				if ($meta && !is_wp_error($meta)) {
					wp_update_attachment_metadata($attachment_id, $meta);
				}
			}
		}

		$name = (string) get_post_meta($submission->ID, '_contact_name', true);
		$lines = array(array('Sent', get_the_date('M j, Y', $submission->ID)));
		foreach (array('Name' => '_contact_name', 'Business' => '_contact_business', 'Email' => '_contact_email', 'Phone' => '_contact_phone', 'Project' => '_contact_project_type') as $label => $key) {
			$value = trim((string) get_post_meta($submission->ID, $key, true));
			if ('' !== $value) {
				$lines[] = array($label, $value);
			}
		}
		$message = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) get_post_meta($submission->ID, '_contact_message', true))));
		if ('' !== $message) {
			$lines[] = array('Message', mb_strlen($message) > 220 ? mb_substr($message, 0, 220) . '…' : $message);
		}
		$out[$attachment_id] = array(
			'title' => $name ? $name . ' · ' . get_the_date('M j', $submission->ID) : 'Customer upload',
			'lines' => $lines,
			'link' => get_edit_post_link($submission->ID, 'raw'),
			'hay' => strtolower(implode(' ', array_map(static function ($line) {
				return $line[1];
			}, $lines))),
		);
	}
	return $out;
}

/**
 * Ids of the files that live only in the extra tabs: builder design previews, files
 * attached to orders/carts, and imported form uploads. Cheap title/URL match.
 */
function retails_media_source_hidden_ids()
{
	static $ids = null;
	if (null === $ids) {
		global $wpdb;
		$ids = array_map('intval', $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND (post_title LIKE 'clDesign%' OR post_title LIKE 'custom-artwork%' OR post_title LIKE 'Customer upload%' OR guid LIKE '%/guest-design-%' OR guid LIKE '%/custom-artwork-%')"));
	}
	return $ids;
}

/** Info for the tab being queried, keyed by attachment id (shared with the JS response filter). */
function retails_media_source_info($set = null)
{
	static $info = null;
	if (null !== $set) {
		$info = $set;
	}
	return $info;
}

/**
 * Narrow the media query to the files of the tab that sent it.
 */
function retails_media_source_query_args($args)
{
	$query = isset($_REQUEST['query']) && is_array($_REQUEST['query']) ? wp_unslash($_REQUEST['query']) : array();
	$source = isset($query['sfs_source']) ? sanitize_key($query['sfs_source']) : '';
	if ('' === $source || !isset(retails_media_source_tabs()[$source]) || !retails_media_source_allowed()) {
		// Media Library / "All files": keep customer designs and artwork out of it.
		if (empty($args['post__in'])) {
			$args['post__not_in'] = array_merge((array) ($args['post__not_in'] ?? array()), retails_media_source_hidden_ids());
		}
		return $args;
	}

	$search = isset($query['s']) ? (string) $query['s'] : '';
	$resolved = retails_media_source_resolve($source, $search);
	if ('' !== trim($search)) {
		unset($args['s']);
	}
	retails_media_source_info($resolved['info']);
	$args['post__in'] = $resolved['ids'] ? $resolved['ids'] : array(0);
	return $args;
}
add_filter('ajax_query_attachments_args', 'retails_media_source_query_args');

/**
 * Attachment ids (and their label info) for one tab, optionally narrowed by a search text.
 *
 * @return array{ids:int[],info:array}
 */
function retails_media_source_resolve($source, $search = '')
{
	global $wpdb;
	$info = array();
	$ids = array();

	if ('designs' === $source) {
		$ids = array_map('intval', $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND (post_title LIKE 'clDesign%' OR guid LIKE '%/guest-design-%')"));
		$links = retails_media_source_order_links();
		foreach ($ids as $id) {
			if (!empty($links['design'][$id])) {
				list($order_id, $item) = $links['design'][$id][0];
				$info[$id] = retails_media_source_order_info($order_id, $item);
			} else {
				$info[$id] = array(
					'title' => 'Design · ' . get_the_date('M j, Y', $id),
					'lines' => array(array('Saved', get_the_date('M j, Y g:i a', $id)), array('Order', 'Not ordered (saved in the builder only)')),
					'link' => '',
					'hay' => '',
				);
			}
		}
	} elseif ('artwork' === $source) {
		$links = retails_media_source_order_links();
		foreach ($links['artwork'] as $id => $orders) {
			list($order_id, $item) = $orders[0];
			$info[$id] = retails_media_source_order_info($order_id, $item);
		}
		$ids = $info ? array_map('intval', $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND ID IN (" . implode(',', array_map('intval', array_keys($info))) . ')')) : array();
		// Artwork uploaded on a product page but never ordered (abandoned cart) is hidden from
		// the Media Library too, so it is listed here, marked as not ordered.
		$unordered = array_map('intval', $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND (post_title LIKE 'custom-artwork%' OR guid LIKE '%/custom-artwork-%')"));
		foreach (array_diff($unordered, $ids) as $id) {
			$ids[] = $id;
			$info[$id] = array(
				'title' => 'Not ordered · ' . get_the_date('M j', $id),
				'lines' => array(array('Uploaded', get_the_date('M j, Y g:i a', $id)), array('Order', 'Not ordered (uploaded on a product page, cart not checked out)')),
				'link' => '',
				'hay' => 'not ordered',
			);
		}
	} else {
		$info = retails_media_source_customer_uploads();
		$ids = array_keys($info);
	}

	// Search also covers the order number, customer, product and message text.
	$search = strtolower(trim((string) $search));
	if ('' !== $search) {
		$ids = array_values(array_filter($ids, static function ($id) use ($info, $search) {
			$text = isset($info[$id]) ? $info[$id]['hay'] . ' ' . strtolower($info[$id]['title']) : '';
			return false !== strpos($text, $search);
		}));
	}

	return array('ids' => $ids, 'info' => array_intersect_key($info, array_flip($ids)));
}

/**
 * List view of Media > Library (upload.php?mode=list): a "Source" drop-down next to
 * the type and date filters. Its value (?sfs_source=) limits the table.
 */
function retails_media_source_list_filter($post_type, $which = 'top')
{
	global $pagenow;
	if ('upload.php' !== $pagenow || 'bar' !== $which || !retails_media_source_allowed()) {
		return;
	}
	$current = isset($_GET['sfs_source']) ? sanitize_key(wp_unslash($_GET['sfs_source'])) : '';
	echo '<label for="sfs-source-filter" class="screen-reader-text">Filter by source</label><select name="sfs_source" id="sfs-source-filter"><option value="">All files</option>';
	foreach (retails_media_source_tabs() as $source => $label) {
		printf('<option value="%s"%s>%s</option>', esc_attr($source), selected($current, $source, false), esc_html($label));
	}
	echo '</select>';
}
add_action('restrict_manage_posts', 'retails_media_source_list_filter', 10, 2);

function retails_media_source_list_query($query)
{
	global $pagenow;
	if (!is_admin() || 'upload.php' !== $pagenow || !$query->is_main_query()) {
		return;
	}
	$source = isset($_GET['sfs_source']) ? sanitize_key(wp_unslash($_GET['sfs_source'])) : '';
	if ('' === $source || !isset(retails_media_source_tabs()[$source]) || !retails_media_source_allowed()) {
		// List view "All files": keep customer designs and artwork out of it.
		$query->set('post__not_in', array_merge((array) $query->get('post__not_in'), retails_media_source_hidden_ids()));
		return;
	}
	$search = (string) $query->get('s');
	$resolved = retails_media_source_resolve($source, $search);
	if ('' !== trim($search)) {
		$query->set('s', '');
	}
	$query->set('post__in', $resolved['ids'] ? $resolved['ids'] : array(0));
}
add_action('pre_get_posts', 'retails_media_source_list_query');

/**
 * Show the order / sender details on the grid tile and in the details sidebar.
 */
function retails_media_source_prepare($response, $attachment)
{
	$info = retails_media_source_info();
	if (!$info || !isset($info[$attachment->ID])) {
		return $response;
	}
	$entry = $info[$attachment->ID];
	$response['title'] = $entry['title'];
	$response['filename'] = $entry['title'];
	$response['sfsInfo'] = $entry['lines'];
	$response['sfsLink'] = $entry['link'];
	return $response;
}
add_filter('wp_prepare_attachment_for_js', 'retails_media_source_prepare', 10, 2);
