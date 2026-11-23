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

/**
 * Line icons for the cards under the product photos.
 */
function wholesale_product_aside_icon($name, $size = 18)
{
	$paths = array(
		'sliders' => 'M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4',
		'upload' => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12',
		'lock' => 'M6 11h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1zM8 11V7a4 4 0 0 1 8 0v4',
		'printer' => 'M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z',
		'package' => 'M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7zM3.3 7 12 12l8.7-5M12 22V12',
		'truck' => 'M1 4h13v11H1zM14 8h4l3 3.5V15h-7M5.5 18.5a1.5 1.5 0 1 0 0-.01M17.5 18.5a1.5 1.5 0 1 0 0-.01',
		'type' => 'M4 7V4h16v3M9 20h6M12 4v16',
		'droplet' => 'M12 2.7s-6 6.5-6 11a6 6 0 0 0 12 0c0-4.5-6-11-6-11z',
		'shield' => 'M12 22s8-3.6 8-10V5l-8-3-8 3v7c0 6.4 8 10 8 10zM8.5 12l2.5 2.5 4.5-5',
		'wrench' => 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z',
		'award' => 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12zM8.2 13.9 7 22l5-3 5 3-1.2-8.1',
		'pin' => 'M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0zM12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
		'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
		'phone' => 'M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z',
		'message' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z',
		'arrow' => 'M5 12h14M13 6l6 6-6 6',
	);
	if (!isset($paths[$name])) {
		return '';
	}

	return '<svg class="pd-aside__icon" viewBox="0 0 24 24" width="' . absint($size) . '" height="' . absint($size) . '" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" d="' . esc_attr($paths[$name]) . '"/></svg>';
}

/**
 * Cards under the product photos, 768px and up (2026-11-20). The photo column ended far
 * above the options (about 1,150px on Custom Pole Flag), leaving a blank area beside the
 * order form. Every line comes from the store's own settings and facts: production days,
 * cut-off time, free shipping minimum, the Google rating and a real Google review.
 *
 * The cards start collapsed; js/ui-polish.js section 11 shows the ones that fit beside the
 * options (in data-aside-priority order), so the column never runs past the options and
 * nothing below the hero moves. Phones never show them: there the photo column sits above
 * the options, and the same facts appear in the price box and the sections below.
 */
function wholesale_render_product_aside($product_id)
{
	$is_cl = wholesale_product_is_channel_letter($product_id);
	$in_stock = !in_array(wholesale_product_stock_status($product_id), array('outofstock', 'backorder'), true);
	$printed = function_exists('wholesale_product_is_printed') ? wholesale_product_is_printed($product_id) : true;
	$days = wholesale_product_production_days($product_id);
	$cutoff = wholesale_product_cutoff_hours($product_id);
	$cut = wholesale_turnaround_clock(sprintf('%02d:00', $cutoff['next']));
	$free = wholesale_free_shipping_amount($is_cl);
	$ships = 1 === $days ? 'it ships the next business day' : sprintf('it ships in %d business days', $days);

	// 1. How ordering works.
	$steps = array();
	if ($is_cl) {
		$steps[] = array('type', 'Type your sign text', 'See your price in under 30 seconds.');
		$steps[] = array('droplet', 'Choose colors & options', 'Pick the colors and options for your letters.');
	} else {
		// The Width/Height box shows unless the calculator is hidden (single-product.php).
		$steps[] = array('sliders', 'on' !== get_post_meta($product_id, '_hide_calculator', true) ? 'Choose size & options' : 'Choose your options', 'Your price updates as you choose.');
		// Stock hardware (carrying bags, stakes) can have the upload field, but has nothing to print.
		if ($printed && 'on' === get_post_meta($product_id, '_has_upload_artwork', true)) {
			$steps[] = array('upload', 'Upload your artwork', sprintf('%s up to %s, made at the size you order.', wholesale_artwork_types_text(), wholesale_artwork_max_text()));
		}
	}
	$steps[] = array('lock', 'Check out securely', 'Guest checkout, no account needed.');
	if ($is_cl) {
		$steps[] = array('shield', 'We build & test it', $in_stock ? sprintf('UL listed, built in WA & CA. %s.', ucfirst($ships)) : 'UL listed, built in Washington and California.');
		$steps[] = array('wrench', 'Your installer mounts it', 'Ships with a wiring diagram and install pattern.');
	} else {
		if ($printed) {
			$steps[] = array('printer', 'We print it', $in_stock ? sprintf('Printed in Renton, WA. Order by %s PT and %s.', $cut, $ships) : 'Printed to order in Renton, WA.');
		} elseif ($in_stock) {
			$steps[] = array('package', 'We pack it', sprintf('Order by %s PT and %s.', $cut, $ships));
		}
		$steps[] = array('truck', 'Delivered to your door', $free ? sprintf('Ships to all 50 states, free on orders of %s or more.', $free) : 'Ships to all 50 states. Choose the speed at checkout.');
	}

	// 2. Google reviews: those linked to this product or its category first, then the store's
	// own. The first card carries the Google rating; the other two only show where the options
	// leave a lot of room (tablets, long option lists).
	$google = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;
	$reviews = array();
	$pool = array_merge(
		function_exists('wholesale_product_google_reviews') ? wholesale_product_google_reviews($product_id, 3) : array(),
		$google && !empty($google['reviews']) ? $google['reviews'] : array()
	);
	foreach ($pool as $candidate) {
		$key = md5(trim((string) $candidate['author']) . '|' . trim((string) $candidate['text']));
		if (!isset($reviews[$key]) && '' !== trim((string) $candidate['text'])) {
			$reviews[$key] = $candidate;
		}
		if (3 === count($reviews)) {
			break;
		}
	}
	$reviews = array_values($reviews);
	$rating = $google && !empty($google['count']) && (float) $google['rating'] >= 4 ? (float) $google['rating'] : 0;

	// 3. Help: call, text or a free quote.
	$quote_url = function_exists('wholesale_quote_url') ? wholesale_quote_url($is_cl ? 'channel-letters' : '', (string) get_post_field('post_name', $product_id)) : home_url('/contact/');

	// 4. Store facts.
	$trust = $is_cl
		? array(array('pin', 'Made in WA & CA', 'Built to order'), array('shield', 'UL listed', '5-year LED warranty'))
		: array(array('award', 'Since 2002', 'Over 20 years in business'), $printed ? array('pin', 'Printed in Renton, WA', 'Made to order') : array('clock', 'Order online 24/7', 'Price shown up front'));
	$trust[] = array('truck', 'Ships to all 50 states', $free ? sprintf('Free on %s+ orders', $free) : 'Standard to overnight');
	$trust[] = array('lock', 'Secure checkout', 'No account needed');

	static $styled = false;
	if (!$styled) {
		$styled = true;
		wholesale_product_aside_styles();
	}
	?>
	<aside class="pd-aside" aria-label="How ordering works and how to reach us">
		<div class="pd-aside__card pd-aside__trust" data-aside-card data-aside-priority="4">
			<ul class="pd-trust">
				<?php foreach ($trust as $item) : ?>
					<li>
						<span class="pd-trust__icon"><?php echo wholesale_product_aside_icon($item[0], 18); // Static SVG. ?></span>
						<span><strong><?php echo esc_html($item[1]); ?></strong><small><?php echo esc_html($item[2]); ?></small></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="pd-aside__card" data-aside-card data-aside-priority="1">
			<p class="pd-aside__eyebrow">How it works</p>
			<p class="pd-aside__title"><?php echo esc_html($is_cl ? 'From your design to your door' : 'From order to your door'); ?></p>
			<ol class="pd-steps">
				<?php foreach ($steps as $step) : ?>
					<li>
						<span class="pd-steps__icon"><?php echo wholesale_product_aside_icon($step[0], 18); // Static SVG. ?></span>
						<span><strong><?php echo esc_html($step[1]); ?></strong><span class="pd-steps__text"><?php echo esc_html($step[2]); ?></span></span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<?php foreach ($reviews as $review_index => $review) :
			$review_rating = function_exists('wholesale_sanitize_star_rating') ? wholesale_sanitize_star_rating($review['rating']) : (int) $review['rating'];
			$review_time = !empty($review['date']) ? strtotime($review['date']) : 0;
			$author = trim((string) $review['author']);
			?>
			<div class="pd-aside__card pd-aside__review" data-aside-card data-aside-priority="<?php echo 0 === $review_index ? 2 : 4 + $review_index; ?>"<?php echo $review_index ? ' data-aside-requires="2"' : ''; ?>>
				<?php if (0 === $review_index) : ?>
				<div class="pd-aside__review-head">
					<?php echo function_exists('wholesale_google_logo_svg') ? wholesale_google_logo_svg(26) : ''; // Static SVG. ?>
					<?php if ($rating) : ?>
						<span class="pd-aside__score"><?php echo esc_html(number_format($rating, 1)); ?></span>
						<span class="pd-aside__review-meta">
							<?php echo wholesale_product_hero_stars($rating); // Escaped in helper. ?>
							<span class="visually-hidden"><?php echo esc_html(sprintf('%s out of 5 stars on Google.', number_format($rating, 1))); ?></span>
							<?php if (!empty($google['url'])) : ?>
								<a href="<?php echo esc_url($google['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html(sprintf(_n('%s Google review', '%s Google reviews', $google['count']), number_format_i18n($google['count']))); ?></a>
							<?php else : ?>
								<span><?php echo esc_html(sprintf(_n('%s Google review', '%s Google reviews', $google['count']), number_format_i18n($google['count']))); ?></span>
							<?php endif; ?>
						</span>
					<?php else : ?>
						<span class="pd-aside__review-meta"><strong>From our Google reviews</strong></span>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				<figure class="pd-aside__quote">
					<blockquote>
						<p class="pd-aside__stars" role="img" aria-label="<?php echo esc_attr($review_rating . ' out of 5 stars'); ?>"><?php echo esc_html(str_repeat('★', $review_rating)); ?></p>
						<p class="pd-aside__quote-text"><?php echo esc_html($review['text']); ?></p>
					</blockquote>
					<figcaption class="pd-aside__author">
						<span class="pd-aside__avatar" aria-hidden="true"><?php echo esc_html(function_exists('mb_substr') ? mb_strtoupper(mb_substr($author, 0, 1)) : strtoupper(substr($author, 0, 1))); ?></span>
						<span class="pd-aside__who">
							<strong><?php echo esc_html($author); ?></strong>
							<span>
								<?php echo $review_time ? esc_html(wp_date('M Y', $review_time)) . ' &middot; ' : ''; ?>
								<?php if (!empty($review['url'])) : ?>
									<a href="<?php echo esc_url($review['url']); ?>" target="_blank" rel="noopener nofollow">Read on Google</a>
								<?php else : ?>
									Google review
								<?php endif; ?>
							</span>
						</span>
					</figcaption>
				</figure>
			</div>
		<?php endforeach; ?>

		<div class="pd-aside__card pd-aside__help" data-aside-card data-aside-priority="3">
			<p class="pd-aside__eyebrow">Need a hand?</p>
			<p class="pd-aside__title"><?php echo esc_html($is_cl ? 'Talk to a sign specialist' : 'Talk to a real person'); ?></p>
			<p class="pd-aside__lead"><?php echo esc_html($is_cl ? 'Help with letter sizes, styles and logo files.' : 'Help with sizes, materials and artwork files.'); ?> Mon&ndash;Fri, 8am&ndash;5pm PT.</p>
			<div class="pd-aside__actions">
				<a class="pd-aside__btn pd-aside__btn--call" href="tel:+18664362101"><?php echo wholesale_product_aside_icon('phone', 17); // Static SVG. ?>866-436-2101</a>
				<a class="pd-aside__btn pd-aside__btn--text" href="sms:+12066186543"><?php echo wholesale_product_aside_icon('message', 17); // Static SVG. ?>Text us</a>
			</div>
			<a class="pd-aside__quote-link" href="<?php echo esc_url($quote_url); ?>"><?php echo esc_html($is_cl ? 'Have a logo? Send it for a free quote' : 'Large or custom order? Get a free quote'); ?><?php echo wholesale_product_aside_icon('arrow', 16); // Static SVG. ?></a>
		</div>
	</aside>
	<?php
}

/**
 * Styles for wholesale_render_product_aside(), printed once with it. Inline on purpose:
 * an edit to css/ui-polish.css turns off the home page's inlined subset of that file
 * (inc/performance.php md5 check), and a separate file would be one more request on
 * product pages, where most ad clicks land. Phones use only the first rule.
 */
function wholesale_product_aside_styles()
{
	?>
	<style id="wholesale-product-aside-css">
	.pd-aside{display:none}
	@media (min-width:768px){
	.product-details .pd-aside{display:flex;flex-direction:column;gap:16px;margin-top:20px}
	.product-details .pd-aside:not(.is-fit){height:0;overflow:hidden;visibility:hidden}
	.product-details .pd-aside.is-empty,.product-details .pd-aside__card.is-cut{display:none}
	.pd-aside__card{padding:20px 22px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.06);color:#334155;font-size:14.5px;line-height:1.5}
	.pd-aside__eyebrow{margin:0 0 4px;color:#0b74a3;font-size:12px;font-weight:800;letter-spacing:.06em;line-height:1.3;text-transform:uppercase}
	.pd-aside__title{margin:0 0 16px;color:#172027;font-family:"Plus Jakarta Sans",Inter,system-ui,sans-serif;font-size:19px;font-weight:750;line-height:1.25;letter-spacing:-.01em}
	.pd-aside__icon{flex:none;display:block}
	.pd-steps{margin:0;padding:0;list-style:none}
	.pd-steps li{position:relative;display:grid;grid-template-columns:36px minmax(0,1fr);column-gap:12px;padding-bottom:16px}
	.pd-steps li:last-child{padding-bottom:0}
	.pd-steps li:not(:last-child)::after{content:"";position:absolute;top:40px;bottom:4px;left:17px;width:2px;border-radius:2px;background:#d3ecf7}
	.pd-steps__icon{display:grid;place-items:center;width:36px;height:36px;border-radius:50%;background:#e8f6fc;color:#0b74a3;box-shadow:inset 0 0 0 1px #cde9f6}
	.pd-steps strong{display:block;margin-top:1px;color:#172027;font-size:15px;font-weight:700;line-height:1.3}
	.pd-steps__text{display:block;margin-top:2px;color:#475569;font-size:13.5px;line-height:1.45}
	.pd-aside__review-head{display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #eef2f6}
	.pd-aside__review-head .review-google-logo{flex:none}
	.pd-aside__score{color:#172027;font-family:"Plus Jakarta Sans",Inter,system-ui,sans-serif;font-size:26px;font-weight:800;line-height:1}
	.pd-aside__review-meta{display:flex;flex-direction:column;gap:3px;font-size:13px;line-height:1.2}
	.pd-aside__review-meta a{color:#0b74a3;font-weight:600;text-decoration:none}
	.pd-aside__review-meta a:hover,.pd-aside__review-meta a:focus-visible{text-decoration:underline}
	.pd-aside__quote{margin:0}
	.pd-aside__quote blockquote{position:relative;margin:0;padding-right:28px}
	.pd-aside__quote blockquote::after{content:"\201D";position:absolute;top:-14px;right:-4px;color:#d3ecf7;font-family:Georgia,serif;font-size:64px;line-height:1}
	.pd-aside__stars{margin:0 0 6px;color:#f5ad27;font-size:15px;letter-spacing:2px;line-height:1}
	.pd-aside__quote-text{display:-webkit-box;margin:0;overflow:hidden;color:#1e293b;font-size:15px;line-height:1.6;-webkit-box-orient:vertical;-webkit-line-clamp:6}
	.pd-aside__author{display:flex;align-items:center;gap:10px;margin-top:14px}
	.pd-aside__avatar{flex:none;display:grid;place-items:center;width:34px;height:34px;border-radius:50%;background:#0b74a3;color:#fff;font-size:14px;font-weight:700}
	.pd-aside__who{display:flex;flex-direction:column;min-width:0;color:#64748b;font-size:12.5px;line-height:1.35}
	.pd-aside__who strong{overflow:hidden;color:#172027;font-size:14px;text-overflow:ellipsis;white-space:nowrap}
	.pd-aside__who a{color:#0b74a3;font-weight:600;text-decoration:none}
	.pd-aside__who a:hover,.pd-aside__who a:focus-visible{text-decoration:underline}
	.pd-aside__help{border:0;background:linear-gradient(150deg,#0b2a45 0%,#0f3d63 100%);box-shadow:0 12px 28px rgba(11,42,69,.22);color:#d6e6f2}
	.pd-aside__help .pd-aside__eyebrow{color:#7fd3f2}
	.pd-aside__help .pd-aside__title{margin-bottom:6px;color:#fff}
	.pd-aside__lead{margin:0 0 16px;color:#cfe0ec;font-size:14px}
	.pd-aside__actions{display:flex;flex-wrap:wrap;gap:8px}
	.pd-aside__btn{flex:1 1 140px;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:9px 14px;border-radius:10px;font-size:14.5px;font-weight:700;line-height:1.2;text-decoration:none;white-space:nowrap;transition:background-color .15s ease,border-color .15s ease}
	.pd-aside__btn--call,.pd-aside__btn--call:hover,.pd-aside__btn--call:focus-visible{color:#0b2a45}
	.pd-aside__btn--call{background:#fff}
	.pd-aside__btn--call:hover,.pd-aside__btn--call:focus-visible{background:#e8f6fc}
	.pd-aside__btn--text,.pd-aside__btn--text:hover,.pd-aside__btn--text:focus-visible{color:#fff}
	.pd-aside__btn--text{border:1px solid rgba(255,255,255,.38);background:rgba(255,255,255,.06)}
	.pd-aside__btn--text:hover,.pd-aside__btn--text:focus-visible{border-color:rgba(255,255,255,.6);background:rgba(255,255,255,.14)}
	.pd-aside__quote-link{display:inline-flex;align-items:center;gap:6px;margin-top:14px;color:#7fd3f2;font-size:14px;font-weight:700;text-decoration:none}
	.pd-aside__quote-link:hover,.pd-aside__quote-link:focus-visible{color:#fff;text-decoration:underline}
	.pd-aside__trust{padding:14px}
	.pd-trust{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;margin:0;padding:0;list-style:none}
	.pd-trust li{display:flex;align-items:center;gap:10px;min-width:0;padding:10px;border-radius:10px;background:#f6f9fc}
	.pd-trust__icon{flex:none;display:grid;place-items:center;width:36px;height:36px;border-radius:10px;background:#fff;color:#0b74a3;box-shadow:inset 0 0 0 1px #e2e8f0}
	.pd-trust strong{display:block;color:#172027;font-size:13.5px;font-weight:700;line-height:1.25}
	.pd-trust small{display:block;margin-top:1px;color:#64748b;font-size:12px;line-height:1.3}
	}
	</style>
	<?php
}
