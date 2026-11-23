<?php
/**
 * Product page hero (2026-11-05): the lines under the product title that give a
 * shopper reasons to order before they reach the options.
 *
 * - wholesale_render_product_proof(): the product's own star rating, or, while it
 *   has no reviews yet, the store's Google rating (labelled as such), plus short
 *   true perks: ship time from the product's production days, the free shipping
 *   threshold from Settings, "since 2002", and the 5-year warranty on channel letters.
 * - wholesale_render_product_uses(): "Great for" chips from the product guide's
 *   Popular uses list (inc/product-guides.php).
 *
 * Layout: single-product.php puts these in `.pd-hero__top` / `.pd-hero__info`, and
 * css/ui-polish.css section 15 places the photo column beside them on 768px and up.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Small inline icon for the perk chips.
 */
function wholesale_product_hero_icon($name)
{
	$paths = array(
		'truck' => 'M1 4h13v11H1zM14 8h4l3 3.5V15h-7M5.5 18.5a1.5 1.5 0 1 0 0-.01M17.5 18.5a1.5 1.5 0 1 0 0-.01',
		'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
		'award' => 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12zM8.2 13.9 7 22l5-3 5 3-1.2-8.1',
		'shield' => 'M12 22s8-3.6 8-10V5l-8-3-8 3v7c0 6.4 8 10 8 10zM8.5 12l2.5 2.5 4.5-5',
	);
	if (!isset($paths[$name])) {
		return '';
	}

	return '<svg class="pd-perk__icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" d="' . esc_attr($paths[$name]) . '"/></svg>';
}

/**
 * Five stars for a rating, filled to the nearest half star.
 */
function wholesale_product_hero_stars($rating)
{
	$html = '<span class="pd-proof__stars" aria-hidden="true">';
	for ($star = 1; $star <= 5; $star++) {
		$state = $star <= floor($rating) ? 'is-full' : (($star - 0.5) <= $rating ? 'is-half' : '');
		$html .= '<span class="pd-proof__star ' . esc_attr($state) . '">&#9733;</span>';
	}

	return $html . '</span>';
}

/**
 * Rating + perks row under the product title.
 */
function wholesale_render_product_proof($product_id)
{
	$rating_html = '';

	// The product's own reviews first; they are on this page (#product-reviews).
	$own = wholesale_setting_enabled('show_product_ratings') ? wholesale_product_review_data($product_id) : array('rating' => 0, 'count' => 0);
	if ($own['count'] > 0 && $own['rating'] > 0) {
		$rating_html = sprintf(
			'<a class="pd-proof__rating" href="#product-reviews" aria-label="%1$s">%2$s<strong>%3$s</strong><span class="pd-proof__count">%4$s</span></a>',
			esc_attr(sprintf('%s out of 5 stars from %s reviews of this product', number_format($own['rating'], 1), number_format_i18n($own['count']))),
			wholesale_product_hero_stars($own['rating']),
			esc_html(number_format($own['rating'], 1)),
			esc_html(sprintf(_n('%s review', '%s reviews', $own['count']), number_format_i18n($own['count'])))
		);
	} elseif (function_exists('wholesale_google_reviews')) {
		// No product reviews yet: the store's overall Google rating, said to be that.
		$google = wholesale_google_reviews();
		if ($google && !empty($google['count']) && !empty($google['rating']) && $google['rating'] >= 4) {
			$rating_html = sprintf(
				'<a class="pd-proof__rating" href="%1$s" target="_blank" rel="noopener" aria-label="%2$s">%3$s<strong>%4$s</strong><span class="pd-proof__count">%5$s</span></a>',
				esc_url(!empty($google['url']) ? $google['url'] : 'https://www.google.com/maps'),
				esc_attr(sprintf('Storefront Sign Online is rated %s out of 5 on Google from %s reviews (opens Google)', number_format((float) $google['rating'], 1), number_format_i18n($google['count']))),
				wholesale_product_hero_stars((float) $google['rating']),
				esc_html(number_format((float) $google['rating'], 1)),
				esc_html(sprintf('Google rating · %s reviews', number_format_i18n($google['count'])))
			);
		}
	}

	$is_cl = wholesale_product_is_channel_letter($product_id);
	$stock = wholesale_product_stock_status($product_id);
	$perks = array();

	if (!in_array($stock, array('outofstock', 'backorder'), true)) {
		$days = wholesale_product_production_days($product_id);
		$perks[] = array('clock', 1 === $days ? 'Ships next business day' : sprintf('Ships in %d business days', $days));
	}
	$free_min = (float) wholesale_get_setting($is_cl ? 'free_shipping_channel_min' : 'free_shipping_min');
	if ($free_min > 0) {
		$perks[] = array('truck', sprintf('Free shipping $%s+', number_format($free_min, 0)));
	}
	$perks[] = $is_cl ? array('shield', '5-year warranty') : array('award', 'Since 2002');

	if (!$rating_html && !$perks) {
		return;
	}
	?>
	<div class="pd-proof">
		<?php echo $rating_html; // Escaped above. ?>
		<?php if ($perks) : ?>
			<ul class="pd-perks" aria-label="Why order here">
				<?php foreach ($perks as $perk) : ?>
					<li class="pd-perk"><?php echo wholesale_product_hero_icon($perk[0]); // Static SVG. ?><span><?php echo esc_html($perk[1]); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * "Great for" chips from the product guide's Popular uses.
 */
function wholesale_render_product_uses($product_id, $limit = 3)
{
	if (!function_exists('wholesale_product_guide')) {
		return;
	}
	$guide = wholesale_product_guide($product_id);
	$uses = !empty($guide['uses']) ? array_slice(array_filter(array_map('trim', (array) $guide['uses'])), 0, $limit) : array();
	if (!$uses) {
		return;
	}
	?>
	<div class="pd-uses">
		<span class="pd-uses__label">Great for</span>
		<ul class="pd-uses__list">
			<?php foreach ($uses as $use) : ?>
				<li><?php echo esc_html($use); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
