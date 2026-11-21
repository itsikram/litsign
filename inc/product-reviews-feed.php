<?php
/**
 * Google product reviews feed at /product-reviews-feed.xml.
 *
 * Gives Merchant Center the genuine product reviews customers wrote after their order
 * shipped, so Google can show star ratings on Shopping and free listings
 * (Merchant Center > Growth > Manage programs > Product ratings, "Reviews feed").
 * The Google Customer Reviews survey (inc/google-customer-reviews.php) only builds the
 * store rating; products need GTINs for it to rate them, and made-to-order signs have none.
 * This feed matches reviews to products by SKU (the feed item ID in merchant-feed.php) and
 * brand instead.
 *
 * Only verified purchases go in (Google rejects the whole program for fake or imported
 * ratings, see the 2026-10-27 misrepresentation suspension):
 *  - a published review sent through the customer review form: no _review_source (imported
 *    and hand-added reviews have one), not a sample, not forced to "not verified";
 *  - tied to an order (_review_order_id) that is still shipped or delivered, for a product
 *    that is still published, with text;
 *  - not hidden on the site (inc/reviews.php retails_hidden_review_ids()).
 * Google's own reviews, imported reviews and reviews with no order never appear.
 * Nothing is sent when Settings > Reviews has "Show reviews on product pages" off, since
 * Google wants every reviewed product's page to show its reviews.
 *
 * Reviewer names are the ones already public on the site ("Michael R."); no email,
 * phone or order number is exposed.
 *
 * Turn it off with the option retails_reviews_feed = '0' (the URL then returns 404).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', function () {
	add_rewrite_rule('^product-reviews-feed\.xml$', 'index.php?retails_product_reviews_feed=1', 'top');
}, 20);

add_action('init', function () {
	if ('1' !== get_option('retails_product_reviews_feed_rewrite_version')) {
		flush_rewrite_rules(false);
		update_option('retails_product_reviews_feed_rewrite_version', '1');
	}
}, 99);

add_filter('query_vars', function ($vars) {
	$vars[] = 'retails_product_reviews_feed';
	return $vars;
});

function retails_product_reviews_feed_enabled()
{
	return '0' !== (string) get_option('retails_reviews_feed', '1');
}

function retails_product_reviews_feed_url()
{
	return home_url('/product-reviews-feed.xml');
}

/**
 * Feed item IDs a product has in merchant-feed.php: the base item plus one per popular
 * size ("<id>-<w>x<h>", inc/size-chips.php), so the reviews rate every size of it.
 *
 * @return string[]
 */
function retails_product_reviews_feed_skus($product)
{
	$skus = array(retails_merchant_item_id($product));
	$map = function_exists('retails_size_chip_map') ? retails_size_chip_map() : array();
	if (isset($map[$product->post_name])) {
		foreach ($map[$product->post_name] as $size) {
			$skus[] = $skus[0] . '-' . $size[0] . 'x' . $size[1];
		}
	}

	return $skus;
}

/**
 * Whether a published review is a genuine post-purchase product review.
 */
function retails_product_reviews_feed_review_ok($review)
{
	if ('review_submission' !== $review->post_type || 'publish' !== $review->post_status) {
		return false;
	}
	$id = $review->ID;
	if (function_exists('retails_review_is_sample') && retails_review_is_sample($id)) {
		return false;
	}
	// Imported and hand-added reviews carry a source; only the customer form leaves it empty.
	if ('' !== (string) get_post_meta($id, '_review_source', true) || '0' === (string) get_post_meta($id, '_review_verified', true)) {
		return false;
	}
	$order_id = absint(get_post_meta($id, '_review_order_id', true));
	if (!$order_id || 'order' !== get_post_type($order_id) || !retails_order_is_shipped(get_post_status($order_id))) {
		return false;
	}
	$rating = absint(get_post_meta($id, '_review_rating', true));
	$text = trim(wp_strip_all_tags($review->post_content));

	return $rating >= 1 && $rating <= 5 && '' !== $text && !retails_review_text_hidden($text);
}

/**
 * The reviews for the feed, oldest first.
 *
 * @return array[] each: post, product (WP_Post), rating, text, name, anonymous, time (UTC timestamp)
 */
function retails_product_reviews_feed_reviews()
{
	if (!retails_setting_enabled('show_product_reviews')) {
		return array();
	}

	$posts = get_posts(array(
		'post_type' => 'review_submission',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post__not_in' => retails_hidden_review_ids(),
		'orderby' => 'date',
		'order' => 'ASC',
		'meta_query' => array(
			array('key' => '_review_product_id', 'value' => 0, 'type' => 'NUMERIC', 'compare' => '>'),
			array('key' => '_review_order_id', 'value' => 0, 'type' => 'NUMERIC', 'compare' => '>'),
		),
	));

	$reviews = array();
	foreach ($posts as $post) {
		if (!retails_product_reviews_feed_review_ok($post)) {
			continue;
		}
		$product = get_post(absint(get_post_meta($post->ID, '_review_product_id', true)));
		if (!$product || 'product' !== $product->post_type || 'publish' !== $product->post_status) {
			continue;
		}
		$time = (int) get_post_time('U', true, $post);
		if ($time <= 0) {
			$time = (int) strtotime($post->post_date);
		}
		$raw_name = trim((string) get_post_meta($post->ID, '_review_name', true));
		$reviews[] = array(
			'post' => $post,
			'product' => $product,
			'rating' => min(5, max(1, absint(get_post_meta($post->ID, '_review_rating', true)))),
			'text' => trim(wp_strip_all_tags($post->post_content)),
			'name' => '' === $raw_name ? 'Customer' : retails_review_display_name($raw_name),
			'anonymous' => '' === $raw_name,
			'time' => $time > 0 ? $time : time(),
		);
	}

	return $reviews;
}

/**
 * Feed XML. Follows Google's product reviews schema 2.3
 * (http://www.google.com/shopping/reviews/schema/product/2.3/product_reviews.xsd).
 */
function retails_product_reviews_feed_xml($reviews = null)
{
	$reviews = null === $reviews ? retails_product_reviews_feed_reviews() : $reviews;

	// Text for XML: no tags, no control characters Google's parser rejects.
	$x = static function ($value) {
		$value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', wp_strip_all_tags((string) $value));
		return esc_xml(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	};

	$out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$out .= '<feed xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.google.com/shopping/reviews/schema/product/2.3/product_reviews.xsd">' . "\n";
	$out .= "<version>2.3</version>\n<publisher>\n";
	$out .= '<name>' . $x(get_bloginfo('name')) . "</name>\n";
	$favicon = get_site_icon_url(96);
	if ($favicon) {
		$out .= '<favicon>' . esc_url($favicon) . "</favicon>\n";
	}
	$out .= "</publisher>\n<reviews>\n";

	foreach ($reviews as $review) {
		$product = $review['product'];
		$page_url = get_permalink($product) . '#product-reviews';

		$out .= "<review>\n<reviewer>\n";
		$out .= '<name' . ($review['anonymous'] ? ' is_anonymous="true"' : '') . '>' . $x($review['name']) . "</name>\n";
		$out .= "</reviewer>\n";
		$out .= '<review_timestamp>' . gmdate('Y-m-d\TH:i:s\Z', $review['time']) . "</review_timestamp>\n";
		$title = trim((string) get_post_meta($review['post']->ID, '_review_title', true));
		if ('' !== $title) {
			$out .= '<title>' . $x(mb_substr($title, 0, 200)) . "</title>\n";
		}
		$out .= '<content>' . $x($review['text']) . "</content>\n";
		// Shown among the other reviews on the product page.
		$out .= '<review_url type="group">' . esc_url($page_url) . "</review_url>\n";
		$out .= '<ratings><overall min="1" max="5">' . (int) $review['rating'] . "</overall></ratings>\n";
		$out .= "<products>\n<product>\n<product_ids>\n<skus>\n";
		foreach (retails_product_reviews_feed_skus($product) as $sku) {
			$out .= '<sku>' . $x($sku) . "</sku>\n";
		}
		$out .= "</skus>\n<brands>\n<brand>" . $x(get_bloginfo('name')) . "</brand>\n</brands>\n</product_ids>\n";
		$out .= '<product_name>' . $x(get_the_title($product)) . "</product_name>\n";
		$out .= '<product_url>' . esc_url(get_permalink($product)) . "</product_url>\n";
		$out .= "</product>\n</products>\n";
		// Asked for after the order shipped (inc/review-requests.php, the shipped email).
		$out .= "<collection_method>post_fulfillment</collection_method>\n";
		$out .= "</review>\n";
	}

	return $out . "</reviews>\n</feed>\n";
}

function retails_render_product_reviews_feed()
{
	if ('1' !== get_query_var('retails_product_reviews_feed')) {
		return;
	}
	if (!retails_product_reviews_feed_enabled()) {
		status_header(404);
		nocache_headers();
		exit;
	}

	$reviews = retails_product_reviews_feed_reviews();

	nocache_headers();
	header('X-Robots-Tag: noindex, follow');
	if (!$reviews) {
		// Google's schema needs at least one review, so an empty feed would be rejected as
		// invalid. A plain 404 lets Merchant Center's scheduled fetch simply retry later.
		status_header(404);
		header('Content-Type: text/plain; charset=UTF-8');
		echo "No verified product reviews qualify for the Google reviews feed yet.\n";
		exit;
	}
	header('Content-Type: application/xml; charset=UTF-8');
	status_header(200);
	echo retails_product_reviews_feed_xml($reviews);
	exit;
}
add_action('template_redirect', 'retails_render_product_reviews_feed', 0);

/**
 * A note above the Review Submissions list with the feed URL and how many reviews qualify.
 */
add_action('admin_notices', function () {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || 'review_submission' !== $screen->post_type || 'edit' !== $screen->base || !current_user_can('edit_others_posts')) {
		return;
	}
	if (!retails_product_reviews_feed_enabled()) {
		return;
	}
	$count = count(retails_product_reviews_feed_reviews());
	printf(
		'<div class="notice notice-info"><p><strong>Google product reviews feed:</strong> <a href="%1$s" target="_blank" rel="noopener">%1$s</a> &mdash; %2$s. Only verified purchases (reviews sent through the customer form for a shipped order) are included; imported, Google and hand-added reviews never are. Add the URL in Merchant Center under Growth &gt; Manage programs &gt; Product ratings.</p></div>',
		esc_url(retails_product_reviews_feed_url()),
		esc_html(sprintf(_n('%d review qualifies today', '%d reviews qualify today', $count, 'litsign'), $count))
	);
});
