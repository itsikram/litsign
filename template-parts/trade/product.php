<?php
/**
 * Trade site product page for visitors who can't see prices (guests,
 * applicants, rejected or suspended accounts). Approved accounts get the shared
 * single-product.php with prices and ordering (inc/trade/gate.php).
 *
 * Channel letter types show the trade copy from data/trade/channel-letters.php;
 * other products show their own copy. All are indexable (inc/trade/seo.php).
 * No price, option value or discount reaches this page: the price meta reads
 * empty for these visitors, and nothing here prints it anyway.
 *
 * @package litsign
 */

$product_id = get_queried_object_id();
$type = wholesale_trade_current_cl_type();
$types = wholesale_trade_cl_types();
$title = $type ? $type['h1'] : wp_strip_all_tags(get_the_title($product_id));
// CMB2 file list: attachment ID => URL. The stored URLs can carry an old host,
// so the images are printed from their attachment IDs.
$gallery = get_post_meta($product_id, '_product_gallery', true);
$gallery = is_array($gallery) ? array_values(array_filter(array_map('absint', array_keys($gallery)), 'wp_attachment_is_image')) : array();
$thumb_id = (int) get_post_thumbnail_id($product_id);
$gallery = array_values(array_diff($gallery, array($thumb_id)));
$short = (string) get_post_meta($product_id, '_product_short_desc', true);

get_header();
?>
<main id="primary" class="tw-page tw-product-page">
	<div class="tw-wrap">
		<?php wholesale_trade_render_crumbs(); ?>
		<article class="tw-product">
			<div>
				<div class="tw-gallery__main">
					<?php
					echo get_the_post_thumbnail($product_id, 'large', array(
						'alt' => $type ? 'Type ' . $type['type'] . ' ' . $type['name'] . ' channel letters' : wp_strip_all_tags(get_the_title($product_id)),
						'loading' => 'eager',
						'fetchpriority' => 'high',
						'decoding' => 'async',
					));
					?>
				</div>
				<?php if ($gallery) : ?>
					<ul class="tw-gallery__thumbs">
						<?php foreach (array_slice($gallery, 0, 5) as $i => $image_id) : ?>
							<li><a href="<?php echo esc_url(wp_get_attachment_image_url($image_id, 'large')); ?>"><?php echo wp_get_attachment_image($image_id, 'thumbnail', false, array('alt' => sprintf('%s, photo %d', $title, $i + 2), 'loading' => 'lazy', 'decoding' => 'async')); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div>
				<?php if ($type) : ?>
					<p class="tw-product__kicker">Type <?php echo (int) $type['type']; ?> · <?php echo 'aluminum' === $type['family'] ? 'Aluminum channel letters' : 'Stainless steel channel letters'; ?></p>
				<?php endif; ?>
				<h1 class="tw-product__title"><?php echo esc_html($title); ?></h1>
				<?php if ($type) : ?>
					<p class="tw-product__lead"><?php echo esc_html($type['lead']); ?></p>
				<?php elseif ($short) : ?>
					<div class="tw-product__lead"><?php echo wp_kses_post(wholesale_trade_strip_unconfirmed_claims($short)); ?></div>
				<?php endif; ?>

				<?php echo wholesale_trade_price_box('product'); // Escaped in helper. ?>

				<?php if ($type) : ?>
					<h2 class="tw-h3" style="margin-top:28px">Best for</h2>
					<ul class="tw-checks">
						<?php foreach ($type['best_for'] as $line) : ?>
							<li><?php echo esc_html($line); ?></li>
						<?php endforeach; ?>
					</ul>
					<p style="padding:14px 16px;border-left:4px solid var(--tw-glow);background:var(--tw-soft);border-radius:0 8px 8px 0;margin:0"><strong>Selling it:</strong> <?php echo esc_html(preg_replace('/^Tell your client:\s*/', '', $type['sell'])); ?></p>
				<?php endif; ?>
			</div>
		</article>
	</div>

	<?php if ($type) : ?>
		<section class="tw-section tw-section--soft" aria-labelledby="tw-specs">
			<div class="tw-wrap tw-split" style="align-items:start">
				<div>
					<h2 class="tw-h2" id="tw-specs">Construction &amp; specs</h2>
					<div class="tw-table-wrap">
						<table class="tw-table tw-table--specs">
							<tbody>
								<?php foreach ($type['specs'] as $label => $value) : ?>
									<tr><th scope="row"><?php echo esc_html($label); ?></th><td><?php echo esc_html($value); ?></td></tr>
								<?php endforeach; ?>
								<tr><th scope="row">Warranty</th><td>5 years</td></tr>
								<tr><th scope="row">Built in</th><td>Washington and California; ships to all 50 states</td></tr>
							</tbody>
						</table>
					</div>
				</div>
				<div>
					<h2 class="tw-h2">Mounting &amp; install</h2>
					<p><?php echo esc_html($type['install']); ?></p>
					<p><a href="<?php echo esc_url(content_url('uploads/storefront-files/channel-letter-installation-manual.pdf')); ?>">Installation manual (PDF)</a></p>
					<?php if ('stainless' === $type['family']) : ?>
						<p>Finish options: <a href="<?php echo esc_url(content_url('uploads/storefront-files/stainless-steel-channel-letter-color-chart.pdf')); ?>">stainless steel color chart (PDF)</a>.</p>
					<?php endif; ?>
					<p><a class="tw-btn tw-btn--primary" href="<?php echo esc_url(wholesale_trade_apply_url()); ?>">Apply to order this type</a></p>
				</div>
			</div>
		</section>

		<?php if (!empty($type['faq'])) {
			wholesale_trade_render_faq($type['faq'], 'Speccing ' . $type['name'] . ' letters');
		} ?>

		<section class="tw-section tw-related" aria-labelledby="tw-related">
			<div class="tw-wrap">
				<h2 class="tw-h2" id="tw-related">Other channel letter types</h2>
				<?php
				// A short list, not full cards: repeated card copy made the 7 type
				// pages read alike (duplicate-content gate, keyword-map.md).
				echo '<ul class="tw-related__list">';
				foreach ($types as $slug => $other) {
					if ($slug !== $type['slug']) {
						echo '<li><a href="' . esc_url(wholesale_trade_type_url($slug)) . '">Type ' . (int) $other['type'] . ' · ' . esc_html($other['name']) . '</a></li>';
					}
				}
				echo '</ul>';
				?>
				<p style="margin:20px 0 0"><a href="<?php echo esc_url(home_url('/channel-letters/')); ?>" style="color:var(--tw-blue);font-weight:700">Compare all 7 types →</a></p>
			</div>
		</section>
	<?php else : ?>
		<section class="tw-section tw-section--soft">
			<div class="tw-wrap tw-wrap--narrow tw-product__content">
				<?php
				// Same cleaner as single-product.php: drops the supplier's facility list,
				// pickup lines and unanswered FAQ questions.
				$content = (string) get_post_field('post_content', $product_id);
				$content = apply_filters('the_content', function_exists('wholesale_seo_clean_product_content') ? wholesale_seo_clean_product_content($content) : $content);
				// Supplier copy can carry price lines; they never print here.
				$content = preg_replace('/\.?\s+or\s+(?:a\s+)?minimum charge of\s*\$\s?\d[\d,]*(?:\.\d+)?/i', '', (string) $content);
				echo preg_replace('/\$\s?\d[\d,]*(\.\d+)?(\s*(per|\/)\s*\w+)?/i', '', (string) $content); // Content filtered by WordPress.
				?>
				<p><a href="<?php echo esc_url(home_url('/channel-letters/')); ?>" style="color:var(--tw-blue);font-weight:700">Looking for channel letters? See all 7 types →</a></p>
			</div>
		</section>
	<?php endif; ?>

	<section class="tw-section" aria-labelledby="tw-product-warranty">
		<div class="tw-wrap tw-wrap--narrow tw-product__content">
			<h2 class="tw-h2" id="tw-product-warranty">Warranty and order issues</h2>
			<p>Every product is covered by our <a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>">Terms and Conditions</a>. Report defects or other order issues within five (5) business days of receiving your order. Any additional product warranty and its coverage are stated in the product details.</p>
		</div>
	</section>

	<?php if (!$type) {
		wholesale_trade_render_cta_band();
	} ?>
</main>
<?php
get_footer();
