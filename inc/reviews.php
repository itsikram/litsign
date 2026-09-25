<?php
/**
 * Customer review slider: real Google Business reviews (Places API) combined with
 * reviews customers submit on the site and an admin approves.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_GOOGLE_REVIEWS_TRANSIENT = 'wholesale_google_reviews';
const WHOLESALE_GOOGLE_REVIEWS_BACKUP = 'wholesale_google_reviews_last_good';

/**
 * Fetches the business rating and up to five reviews from the Google Places API (New).
 *
 * Results are cached so page views never wait on Google. When a refresh fails, the
 * last good response keeps the slider populated and the error is shown in wp-admin.
 */
function wholesale_google_reviews($force_refresh = false)
{
	$api_key = trim((string) wholesale_get_setting('google_places_api_key'));
	$place_id = trim((string) wholesale_get_setting('google_place_id'));

	if (!$api_key || !$place_id) {
		return null;
	}

	if (!$force_refresh) {
		$cached = get_transient(WHOLESALE_GOOGLE_REVIEWS_TRANSIENT);
		if (is_array($cached)) {
			return $cached;
		}
	}

	$response = wp_remote_get(
		'https://places.googleapis.com/v1/places/' . rawurlencode($place_id) . '?languageCode=en',
		array(
			'timeout' => 8,
			'headers' => array(
				'X-Goog-Api-Key' => $api_key,
				'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,googleMapsUri,reviews',
			),
		)
	);

	$code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
	$body = is_wp_error($response) ? null : json_decode(wp_remote_retrieve_body($response), true);

	if (200 !== $code || !is_array($body)) {
		if (is_wp_error($response)) {
			$error = $response->get_error_message();
		} else {
			$error = isset($body['error']['message']) ? $body['error']['message'] : 'Google returned HTTP ' . $code . '.';
		}

		$fallback = get_option(WHOLESALE_GOOGLE_REVIEWS_BACKUP);
		$fallback = is_array($fallback) ? $fallback : array('rating' => 0, 'count' => 0, 'url' => '', 'reviews' => array());
		$fallback['error'] = $error;
		$fallback['checked'] = time();
		// Retry in 30 minutes rather than on every page view.
		set_transient(WHOLESALE_GOOGLE_REVIEWS_TRANSIENT, $fallback, 30 * MINUTE_IN_SECONDS);
		return $fallback;
	}

	$reviews = array();
	foreach (isset($body['reviews']) && is_array($body['reviews']) ? $body['reviews'] : array() as $review) {
		$text = isset($review['text']['text']) ? $review['text']['text'] : (isset($review['originalText']['text']) ? $review['originalText']['text'] : '');
		if ('' === trim($text)) {
			continue;
		}
		$reviews[] = array(
			'source' => 'google',
			'rating' => isset($review['rating']) ? (int) $review['rating'] : 0,
			'text' => $text,
			'author' => isset($review['authorAttribution']['displayName']) ? $review['authorAttribution']['displayName'] : 'Google user',
			'author_url' => isset($review['authorAttribution']['uri']) ? $review['authorAttribution']['uri'] : '',
			'photo' => isset($review['authorAttribution']['photoUri']) ? $review['authorAttribution']['photoUri'] : '',
			'when' => isset($review['relativePublishTimeDescription']) ? $review['relativePublishTimeDescription'] : '',
			'url' => isset($review['googleMapsUri']) ? $review['googleMapsUri'] : '',
		);
	}

	$data = array(
		'rating' => isset($body['rating']) ? (float) $body['rating'] : 0,
		'count' => isset($body['userRatingCount']) ? (int) $body['userRatingCount'] : 0,
		'url' => isset($body['googleMapsUri']) ? $body['googleMapsUri'] : '',
		'reviews' => $reviews,
		'error' => '',
		'checked' => time(),
	);

	set_transient(WHOLESALE_GOOGLE_REVIEWS_TRANSIENT, $data, 12 * HOUR_IN_SECONDS);
	update_option(WHOLESALE_GOOGLE_REVIEWS_BACKUP, $data, false);

	return $data;
}

/**
 * Approved (published) reviews customers sent through the site.
 */
function wholesale_site_reviews($limit = 12)
{
	$posts = get_posts(array(
		'post_type' => 'review_submission',
		'post_status' => 'publish',
		'posts_per_page' => $limit,
		'orderby' => 'date',
		'order' => 'DESC',
	));

	$reviews = array();
	foreach ($posts as $post) {
		$text = trim(wp_strip_all_tags($post->post_content));
		if ('' === $text) {
			continue;
		}
		$product_id = absint(get_post_meta($post->ID, '_review_product_id', true));
		$reviews[] = array(
			'source' => 'site',
			'rating' => min(5, max(1, absint(get_post_meta($post->ID, '_review_rating', true)))),
			'text' => $text,
			'author' => wholesale_review_display_name(get_post_meta($post->ID, '_review_name', true)),
			'author_url' => '',
			'photo' => '',
			'when' => sprintf('%s ago', human_time_diff(get_post_time('U', true, $post), time())),
			'url' => '',
			// Product reviews sent through the site are only accepted from customers with a
			// completed order. Reviews an admin added or imported have a _review_source.
			'verified' => $product_id > 0 && '' === get_post_meta($post->ID, '_review_source', true),
			'product' => $product_id ? get_the_title($product_id) : '',
		);
	}

	return $reviews;
}

/**
 * "Michael Roberts" -> "Michael R." so full customer names aren't published.
 */
function wholesale_review_display_name($name)
{
	$parts = preg_split('/\s+/', trim((string) $name));
	if (empty($parts[0])) {
		return 'Customer';
	}
	$display = ucfirst($parts[0]);
	if (count($parts) > 1) {
		$display .= ' ' . strtoupper(mb_substr(end($parts), 0, 1)) . '.';
	}
	return $display;
}

/**
 * Everything the footer slider needs, Google and site reviews interleaved.
 */
function wholesale_review_slider_data()
{
	$google = wholesale_google_reviews();
	$google_reviews = $google ? $google['reviews'] : array();
	$site_reviews = wholesale_site_reviews();

	$reviews = array();
	$max = max(count($google_reviews), count($site_reviews));
	for ($i = 0; $i < $max; $i++) {
		if (isset($google_reviews[$i])) {
			$reviews[] = $google_reviews[$i];
		}
		if (isset($site_reviews[$i])) {
			$reviews[] = $site_reviews[$i];
		}
	}

	return array(
		'reviews' => $reviews,
		'google_rating' => $google ? $google['rating'] : 0,
		'google_count' => $google ? $google['count'] : 0,
		'google_url' => $google ? $google['url'] : '',
		'google_write_url' => $google ? 'https://search.google.com/local/writereview?placeid=' . rawurlencode(wholesale_get_setting('google_place_id')) : '',
	);
}

function wholesale_google_logo_svg($size = 20)
{
	return '<svg class="review-google-logo" width="' . absint($size) . '" height="' . absint($size) . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">'
		. '<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>'
		. '<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>'
		. '<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>'
		. '<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>'
		. '</svg>';
}

function wholesale_review_stars($rating, $class = 'review-stars')
{
	$rating = max(0, min(5, (float) $rating));
	$html = '<span class="' . esc_attr($class) . '" role="img" aria-label="' . esc_attr(sprintf('%s out of 5 stars', rtrim(rtrim(number_format($rating, 1), '0'), '.'))) . '">';
	for ($star = 1; $star <= 5; $star++) {
		$state = $star <= floor($rating) ? 'is-full' : (($star - 0.5) <= $rating ? 'is-half' : '');
		$html .= '<span class="review-star ' . $state . '" aria-hidden="true">&#9733;</span>';
	}
	return $html . '</span>';
}

/**
 * Footer slider. Renders nothing when there are no reviews to show yet.
 */
function wholesale_render_review_slider()
{
	$data = wholesale_review_slider_data();
	if (empty($data['reviews'])) {
		return;
	}
	?>
	<section class="customer-review-section" aria-labelledby="customer-review-title" data-review-slider>
		<div class="container">
			<div class="customer-review-header">
				<h2 id="customer-review-title">What Our Customers Say</h2>
				<?php if ($data['google_rating'] > 0) : ?>
					<a class="review-summary" href="<?php echo esc_url($data['google_url']); ?>" target="_blank" rel="noopener">
						<?php echo wholesale_google_logo_svg(22); ?>
						<strong><?php echo esc_html(number_format($data['google_rating'], 1)); ?></strong>
						<?php echo wholesale_review_stars($data['google_rating']); ?>
						<span><?php echo esc_html(sprintf(_n('%s Google review', '%s Google reviews', $data['google_count'], 'litsign'), number_format_i18n($data['google_count']))); ?></span>
					</a>
				<?php else : ?>
					<p>Real reviews from businesses we&rsquo;ve built signs for.</p>
				<?php endif; ?>
			</div>

			<div class="review-slider">
				<button class="review-nav review-nav--prev" type="button" aria-label="Previous reviews" data-review-prev>
					<span aria-hidden="true">&#8249;</span>
				</button>
				<ul class="review-track" data-review-track tabindex="0" aria-label="Customer reviews">
					<?php foreach ($data['reviews'] as $index => $review) : ?>
						<li class="review-card review-card--<?php echo esc_attr($review['source']); ?>" aria-label="<?php echo esc_attr(sprintf('Review %d of %d', $index + 1, count($data['reviews']))); ?>">
							<div class="review-card-top">
								<?php echo wholesale_review_stars($review['rating']); ?>
								<?php if ('google' === $review['source']) : ?>
									<span class="review-source"><?php echo wholesale_google_logo_svg(16); ?> Google</span>
								<?php elseif (!empty($review['verified'])) : ?>
									<span class="review-source review-source--verified">Verified buyer</span>
								<?php else : ?>
									<span class="review-source review-source--site">Customer review</span>
								<?php endif; ?>
							</div>
							<blockquote class="review-text"><?php echo esc_html($review['text']); ?></blockquote>
							<div class="review-author">
								<?php if ($review['photo']) : ?>
									<img class="review-avatar" src="<?php echo esc_url($review['photo']); ?>" alt="" width="36" height="36" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="review-avatar review-avatar--initial" aria-hidden="true"><?php echo esc_html(mb_strtoupper(mb_substr($review['author'], 0, 1))); ?></span>
								<?php endif; ?>
								<span>
									<?php if ($review['author_url']) : ?>
										<a class="review-author-name" href="<?php echo esc_url($review['author_url']); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html($review['author']); ?></a>
									<?php else : ?>
										<span class="review-author-name"><?php echo esc_html($review['author']); ?></span>
									<?php endif; ?>
									<small>
										<?php echo esc_html($review['when']); ?>
										<?php if (!empty($review['product'])) : ?>
											&middot; <?php echo esc_html($review['product']); ?>
										<?php endif; ?>
									</small>
								</span>
								<?php if ($review['url']) : ?>
									<a class="review-read-more" href="<?php echo esc_url($review['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr(sprintf("Read %s's review on Google", $review['author'])); ?>">Read on Google</a>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<button class="review-nav review-nav--next" type="button" aria-label="Next reviews" data-review-next>
					<span aria-hidden="true">&#8250;</span>
				</button>
			</div>

			<div class="review-dots" data-review-dots aria-hidden="true"></div>

			<div class="review-actions">
				<?php if ($data['google_url']) : ?>
					<a class="review-action" href="<?php echo esc_url($data['google_url']); ?>" target="_blank" rel="noopener">See all reviews on Google</a>
				<?php endif; ?>
				<?php if ($data['google_write_url']) : ?>
					<a class="review-action review-action--primary" href="<?php echo esc_url($data['google_write_url']); ?>" target="_blank" rel="noopener"><?php echo wholesale_google_logo_svg(16); ?> Review us on Google</a>
				<?php else : ?>
					<button type="button" class="review-action review-action--primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">Leave a review</button>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * wp-admin: refresh Google reviews on demand from the settings page.
 */
function wholesale_handle_refresh_google_reviews()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.');
	}
	check_admin_referer('wholesale_refresh_google_reviews');
	wholesale_google_reviews(true);
	wp_safe_redirect(add_query_arg('google_reviews_refreshed', '1', admin_url('options-general.php?page=wholesale-settings')) . '#google-reviews');
	exit;
}
add_action('admin_post_wholesale_refresh_google_reviews', 'wholesale_handle_refresh_google_reviews');

// Settings changes should take effect immediately rather than after the cache expires.
function wholesale_clear_google_reviews_cache()
{
	delete_transient(WHOLESALE_GOOGLE_REVIEWS_TRANSIENT);
}
add_action('update_option_wholesale_google_places_api_key', 'wholesale_clear_google_reviews_cache');
add_action('update_option_wholesale_google_place_id', 'wholesale_clear_google_reviews_cache');

