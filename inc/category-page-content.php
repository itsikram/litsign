<?php
/**
 * Category page copy (data/category-pages.php) and card images for the
 * /{category}/ routes rendered by home.php.
 *
 * Each category prints its own intro (Products > Categories) in the hero,
 * a short lead above the products and a buying guide below them, so the
 * page explains the choice instead of only listing products.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * When data/category-pages.php last changed. Bump it by hand with the copy.
 */
define('WHOLESALE_CATEGORY_PAGES_REVISED', '2026-10-26T00:00:00+00:00');

/**
 * The lead and guide for a category, or an empty array.
 *
 * @param string $slug product_category slug.
 * @return array
 */
function wholesale_category_page($slug)
{
	static $pages = null;
	if (null === $pages) {
		$file = get_template_directory() . '/data/category-pages.php';
		$pages = is_file($file) ? (array) include $file : array();
	}

	return isset($pages[$slug]) ? $pages[$slug] : array();
}

/**
 * Heading and lead paragraph above the product grid.
 *
 * @param string $slug    product_category slug.
 * @param string $heading Category heading (the page H1).
 */
function wholesale_render_category_lead($slug, $heading)
{
	$page = wholesale_category_page($slug);
	if (empty($page['lead'])) {
		return;
	}
	?>
	<header class="category-listing-header">
		<h2 id="category-listing-title" class="category-listing-title"><?php echo esc_html(sprintf(__('Shop %s', 'litsign'), $heading)); ?></h2>
		<p class="category-listing-lead"><?php echo wp_kses_post($page['lead']); ?></p>
	</header>
	<?php
}

/**
 * Buying guide section under the product grid.
 *
 * @param string $slug product_category slug.
 */
function wholesale_render_category_guide($slug)
{
	$page = wholesale_category_page($slug);
	if (empty($page['guide']['sections'])) {
		return;
	}
	$guide = $page['guide'];
	?>
	<section class="sf-section category-guide" aria-labelledby="category-guide-title">
		<div class="container">
			<p class="cl-kicker"><?php esc_html_e('Buying guide', 'litsign'); ?></p>
			<h2 id="category-guide-title" class="cl-section-title"><?php echo esc_html($guide['title']); ?></h2>
			<div class="category-guide-body">
				<?php foreach ($guide['sections'] as $section) : ?>
					<div class="category-guide-block">
						<h3><?php echo esc_html($section['h']); ?></h3>
						<?php foreach ((array) (isset($section['p']) ? $section['p'] : array()) as $paragraph) : ?>
							<p><?php echo wp_kses_post($paragraph); ?></p>
						<?php endforeach; ?>
						<?php if (!empty($section['list'])) : ?>
							<ul>
								<?php foreach ($section['list'] as $item) : ?>
									<li><?php echo wp_kses_post($item); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if (!empty($section['table'])) : ?>
							<?php $rows = $section['table']; $head = array_shift($rows); ?>
							<div class="category-guide-table">
								<table>
									<thead>
										<tr>
											<?php foreach ($head as $cell) : ?>
												<th scope="col"><?php echo wp_kses_post($cell); ?></th>
											<?php endforeach; ?>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($rows as $row) : ?>
											<tr>
												<?php foreach (array_values($row) as $i => $cell) : ?>
													<?php echo 0 === $i ? '<th scope="row">' : '<td>'; ?><?php echo wp_kses_post($cell); ?><?php echo 0 === $i ? '</th>' : '</td>'; ?>
												<?php endforeach; ?>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Card-sized copies of a product photo for the listing grid.
 *
 * Most product photos predate the 290px 'product-card' size, so cards were
 * loading the 1000px original. This picks the 290px or 300px copy for 'src'
 * and offers the 768px copy for wide and high density screens.
 *
 * @param string[] $urls Photo URLs (full size), in display order.
 * @return array URL => array('src' => URL, 'srcset' => string, 'large' => URL).
 */
function wholesale_card_images($urls)
{
	$images = array();
	$ids = function_exists('wholesale_product_image_alt_attachment_ids_by_url')
		? wholesale_product_image_alt_attachment_ids_by_url(array_values(array_unique($urls)))
		: array();

	foreach ($urls as $url) {
		$images[$url] = array('src' => $url, 'srcset' => '', 'large' => $url);
		$attachment_id = isset($ids[$url]) ? (int) $ids[$url] : 0;
		$meta = $attachment_id ? wp_get_attachment_metadata($attachment_id) : array();
		$sizes = is_array($meta) && !empty($meta['sizes']) ? $meta['sizes'] : array();
		$small = isset($sizes['product-card']) ? 'product-card' : (isset($sizes['medium']) ? 'medium' : '');
		// A wide or tall 300px copy would look soft once cropped to the square card.
		if ('medium' === $small && abs((int) $sizes['medium']['width'] - (int) $sizes['medium']['height']) > 2) {
			$small = '';
		}
		if (!$small) {
			if (isset($sizes['medium_large'])) {
				$large = wp_get_attachment_image_url($attachment_id, 'medium_large');
				$images[$url] = array('src' => $large, 'srcset' => '', 'large' => $large);
			}
			continue;
		}

		$src = wp_get_attachment_image_url($attachment_id, $small);
		$srcset = array(wholesale_webp_url($src) . ' ' . (int) $sizes[$small]['width'] . 'w');
		$large = $url;
		if (isset($sizes['medium_large'])) {
			$large = wp_get_attachment_image_url($attachment_id, 'medium_large');
			$srcset[] = wholesale_webp_url($large) . ' ' . (int) $sizes['medium_large']['width'] . 'w';
		}
		$images[$url] = array('src' => $src, 'srcset' => implode(', ', $srcset), 'large' => $large);
	}

	return $images;
}

/**
 * Load the photos of a whole category grid (home.php's product query) in a
 * few queries when its loop starts. Each card otherwise loads its featured
 * photo, looks up its gallery URLs and loads every photo's post and meta one
 * at a time (about 550 queries on /indoor-outdoor-displays/). This only fills
 * the caches; the cards print the same markup.
 *
 * @param WP_Query $query Query whose loop is starting.
 */
function wholesale_prime_card_images($query)
{
	if (is_admin() || !($query instanceof WP_Query) || 'product' !== $query->get('post_type') || '_order_by_index' !== $query->get('meta_key')
		|| empty($query->posts) || count($query->posts) < 2 || !function_exists('wholesale_product_image_alt_attachment_ids_by_url')) {
		return;
	}

	update_post_thumbnail_cache($query);

	// The same URLs wholesale_render_home_product_gallery() passes to wholesale_card_images().
	$site_url_parts = wp_parse_url(home_url('/'));
	$site_origin = ($site_url_parts['scheme'] ?? 'http') . '://' . ($site_url_parts['host'] ?? '');
	if (!empty($site_url_parts['port'])) {
		$site_origin .= ':' . $site_url_parts['port'];
	}
	$urls = array();
	foreach ($query->posts as $post) {
		$post_id = is_object($post) ? (int) $post->ID : (int) $post;
		$thumbnail_id = get_post_thumbnail_id($post_id);
		$featured = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'full') : '';
		if ($featured) {
			$urls[] = $featured;
		}
		$gallery = get_post_meta($post_id, '_product_gallery', true);
		foreach (is_array($gallery) ? array_filter($gallery, 'is_string') : array() as $url) {
			$url = esc_url_raw($url);
			$host = wp_parse_url($url, PHP_URL_HOST);
			if ('localhost' === $host || '127.0.0.1' === $host) {
				$url = $site_origin . wp_parse_url($url, PHP_URL_PATH);
			}
			if ($url) {
				$urls[] = $url;
			}
		}
	}

	$ids = wholesale_product_image_alt_attachment_ids_by_url(array_values(array_unique($urls)));
	_prime_post_caches(array_values(array_unique(array_filter(array_map('intval', $ids)))), false, true);
}
add_action('loop_start', 'wholesale_prime_card_images');
