<?php
/**
 * Reviews from customers whose orders are complete.
 *
 * Every item in a completed order gets a signed review link (in the shipped email and on
 * the order pages), so guest checkouts can review without an account. Signed-in customers
 * can also review from the product page. One review per product per order; reviews stay
 * pending until approved under Review Submissions, then show on the product page, in the
 * footer slider and on the /reviews/ page.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Product IDs in an order, in order of appearance.
 */
function wholesale_order_product_ids($order_id)
{
	$ids = array();
	foreach (wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_json', true)) as $item) {
		$details = isset($item['product_details']) && is_array($item['product_details']) ? $item['product_details'] : array();
		$product_id = absint($item['product_id'] ?? ($details['Product Id'] ?? 0));
		if ($product_id) {
			$ids[] = $product_id;
		}
	}
	return array_values(array_unique($ids));
}

function wholesale_order_review_token($order_id, $product_id)
{
	return substr(hash_hmac('sha256', 'order-review|' . absint($order_id) . '|' . absint($product_id), wp_salt('auth')), 0, 24);
}

function wholesale_order_review_url($order_id, $product_id)
{
	return add_query_arg(array(
		'review_order' => absint($order_id),
		'review_token' => wholesale_order_review_token($order_id, $product_id),
	), get_permalink($product_id)) . '#product-reviews';
}

/**
 * Completed order that contains a product that is still on the site.
 */
function wholesale_order_can_be_reviewed($order_id, $product_id)
{
	return 'order' === get_post_type($order_id)
		&& 'completed' === get_post_status($order_id)
		&& 'publish' === get_post_status($product_id)
		&& in_array(absint($product_id), wholesale_order_product_ids($order_id), true);
}

/**
 * The review already written for this order item (any status but trash), or 0.
 */
function wholesale_order_review_id($order_id, $product_id)
{
	$ids = get_posts(array(
		'post_type' => 'review_submission',
		'post_status' => array('publish', 'pending', 'draft', 'private'),
		'posts_per_page' => 1,
		'fields' => 'ids',
		'meta_query' => array(
			array('key' => '_review_order_id', 'value' => absint($order_id)),
			array('key' => '_review_product_id', 'value' => absint($product_id)),
		),
	));
	return $ids ? (int) $ids[0] : 0;
}

/**
 * A customer's completed orders: placed while signed in, or checked out as a guest with
 * the account's email address.
 */
function wholesale_user_completed_order_ids($user_id)
{
	$user = get_userdata($user_id);
	if (!$user) {
		return array();
	}

	$ids = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'completed',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_key' => 'user_id',
		'meta_value' => absint($user_id),
	));

	$email = strtolower((string) $user->user_email);
	if (is_email($email)) {
		$guest_ids = get_posts(array(
			'post_type' => 'order',
			'post_status' => 'completed',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(array('key' => 'billing_address', 'value' => $email, 'compare' => 'LIKE')),
		));
		foreach ($guest_ids as $order_id) {
			$billing = wholesale_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
			if (strtolower((string) ($billing['billing_email'] ?? '')) === $email) {
				$ids[] = $order_id;
			}
		}
	}

	return array_values(array_unique(array_map('intval', $ids)));
}

/**
 * Whether the visitor may review a product, and against which order.
 *
 * A review link (order + token) decides on its own. Otherwise a signed-in customer's first
 * completed order with this product that has no review yet is used.
 *
 * @return array{order:int, reason:string} reason is 'ok', 'reviewed' or 'none'.
 */
function wholesale_review_eligibility($product_id, $order_id = 0, $token = '')
{
	$product_id = absint($product_id);
	$order_id = absint($order_id);
	$token = (string) $token;

	if ($order_id && '' !== $token) {
		if (!hash_equals(wholesale_order_review_token($order_id, $product_id), $token) || !wholesale_order_can_be_reviewed($order_id, $product_id)) {
			return array('order' => 0, 'reason' => 'none');
		}
		return wholesale_order_review_id($order_id, $product_id)
			? array('order' => 0, 'reason' => 'reviewed')
			: array('order' => $order_id, 'reason' => 'ok');
	}

	if (!is_user_logged_in()) {
		return array('order' => 0, 'reason' => 'none');
	}

	$reason = 'none';
	foreach (wholesale_user_completed_order_ids(get_current_user_id()) as $candidate) {
		if (!wholesale_order_can_be_reviewed($candidate, $product_id)) {
			continue;
		}
		if (!wholesale_order_review_id($candidate, $product_id)) {
			return array('order' => $candidate, 'reason' => 'ok');
		}
		$reason = 'reviewed';
	}

	return array('order' => 0, 'reason' => $reason);
}

/**
 * Eligibility for the current product page request (review link in the query string, if any).
 */
function wholesale_review_eligibility_from_request($product_id)
{
	$order_id = isset($_GET['review_order']) ? absint($_GET['review_order']) : 0;
	$token = isset($_GET['review_token']) ? sanitize_text_field(wp_unslash($_GET['review_token'])) : '';
	return wholesale_review_eligibility($product_id, $order_id, $token);
}

/**
 * Items in a completed order that can still be reviewed, as product_id => review URL.
 */
function wholesale_order_unreviewed_products($order_id)
{
	if ('completed' !== get_post_status($order_id) || !wholesale_setting_enabled('allow_verified_reviews')) {
		return array();
	}
	$links = array();
	foreach (wholesale_order_product_ids($order_id) as $product_id) {
		if (wholesale_order_can_be_reviewed($order_id, $product_id) && !wholesale_order_review_id($order_id, $product_id)) {
			$links[$product_id] = wholesale_order_review_url($order_id, $product_id);
		}
	}
	return $links;
}

/**
 * "How did it turn out?" block for the shipped email. Already escaped.
 */
function wholesale_order_review_email_html($order_id)
{
	$links = wholesale_order_unreviewed_products($order_id);
	if (!$links) {
		return '';
	}

	$html = '<div style="margin:20px 0 0;padding:16px 18px;border:1px solid #e3e9ef;border-radius:8px;background:#fbfdff;">'
		. '<p style="margin:0 0 8px;font-weight:700;color:#0d2e4d;">Once your sign is up, tell us how it turned out</p>'
		. '<p style="margin:0 0 10px;color:#5b6b7b;font-size:14px;">Your review helps other business owners choose the right sign. It only takes a minute.</p>';
	foreach ($links as $product_id => $url) {
		$html .= '<p style="margin:6px 0 0;"><a href="' . esc_url($url) . '" style="color:#1287b5;font-weight:700;">&#9733; Review ' . esc_html(get_the_title($product_id)) . '</a></p>';
	}
	return $html . '</div>';
}

// ------------------------------------------------------------------
// Public "Customer Reviews" page (/reviews/, template page-reviews.php)
// ------------------------------------------------------------------

// Creates the page once so it exists wherever the theme is deployed.
add_action('init', function () {
	$page_id = (int) get_option('wholesale_reviews_page_id');
	if ($page_id && get_post($page_id)) {
		return;
	}
	$page = get_page_by_path('reviews');
	if (!$page) {
		$page_id = wp_insert_post(array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Customer Reviews',
			'post_name' => 'reviews',
			'post_content' => '',
		));
		if (is_wp_error($page_id) || !$page_id) {
			return;
		}
	} else {
		$page_id = $page->ID;
	}
	update_option('wholesale_reviews_page_id', (int) $page_id);
}, 20);

function wholesale_reviews_page_url($args = array())
{
	$page_id = (int) get_option('wholesale_reviews_page_id');
	$url = $page_id && 'publish' === get_post_status($page_id) ? get_permalink($page_id) : home_url('/reviews/');
	return $args ? add_query_arg($args, $url) : $url;
}

/**
 * Approved reviews for the reviews page, optionally for one product and/or star rating.
 *
 * @return array{reviews:WP_Post[], total:int, pages:int}
 */
function wholesale_reviews_page_query($product_id = 0, $rating = 0, $page = 1, $per_page = 12)
{
	$meta_query = array();
	if ($product_id) {
		$meta_query[] = array('key' => '_review_product_id', 'value' => absint($product_id));
	}
	if ($rating) {
		$meta_query[] = array('key' => '_review_rating', 'value' => absint($rating));
	}

	$query = new WP_Query(array(
		'post_type' => 'review_submission',
		'post_status' => 'publish',
		'posts_per_page' => $per_page,
		'paged' => max(1, absint($page)),
		'orderby' => 'date',
		'order' => 'DESC',
		'meta_query' => $meta_query,
		'no_found_rows' => false,
	));

	return array(
		'reviews' => $query->posts,
		'total' => (int) $query->found_posts,
		'pages' => (int) $query->max_num_pages,
	);
}

/**
 * Totals across all approved reviews: average, count, count per star, and products reviewed.
 */
function wholesale_reviews_page_summary()
{
	$ids = get_posts(array(
		'post_type' => 'review_submission',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'fields' => 'ids',
	));

	$stars = array_fill_keys(array(5, 4, 3, 2, 1), 0);
	$products = array();
	$sum = 0;
	foreach ($ids as $id) {
		$rating = min(5, max(1, absint(get_post_meta($id, '_review_rating', true))));
		$stars[$rating]++;
		$sum += $rating;
		$product_id = absint(get_post_meta($id, '_review_product_id', true));
		if ($product_id && 'publish' === get_post_status($product_id)) {
			$products[$product_id] = isset($products[$product_id]) ? $products[$product_id] + 1 : 1;
		}
	}

	$titles = array();
	foreach (array_keys($products) as $product_id) {
		$titles[$product_id] = get_the_title($product_id);
	}
	asort($titles, SORT_NATURAL | SORT_FLAG_CASE);

	return array(
		'count' => count($ids),
		'average' => $ids ? round($sum / count($ids), 1) : 0,
		'stars' => $stars,
		'products' => $titles,
		'product_counts' => $products,
	);
}

add_action('wp_enqueue_scripts', function () {
	if (!is_page('reviews') && !is_singular(array('product', 'order')) && !is_page('my-orders')) {
		return;
	}
	$path = '/css/reviews.css';
	wp_enqueue_style('wholesale-reviews', get_template_directory_uri() . $path, array('custom-style'), (string) filemtime(get_template_directory() . $path));
}, 20);
