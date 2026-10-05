<?php
/**
 * Product sitemap at /product-sitemap.xml and category sitemap at
 * /category-sitemap.xml.
 *
 * Lists every indexable product page with its last-modified date and its
 * images (Google image sitemap extension), so new and updated products are
 * crawled sooner and product photos can rank in Google Images. The category
 * sitemap lists every indexable category page with the photos of the products
 * it shows. Submit both in Search Console next to /sitemap.xml.
 *
 * @package litsign
 */

function wholesale_product_sitemap_rewrite()
{
	add_rewrite_rule('^product-sitemap\.xml$', 'index.php?wholesale_product_sitemap=1', 'top');
	add_rewrite_rule('^category-sitemap\.xml$', 'index.php?wholesale_category_sitemap=1', 'top');
}
add_action('init', 'wholesale_product_sitemap_rewrite', 20);

add_action('init', function () {
	if ('2' !== get_option('wholesale_product_sitemap_rewrite_version')) {
		flush_rewrite_rules(false);
		update_option('wholesale_product_sitemap_rewrite_version', '2');
	}
}, 99);

add_filter('query_vars', function ($vars) {
	$vars[] = 'wholesale_product_sitemap';
	$vars[] = 'wholesale_category_sitemap';
	return $vars;
});

/**
 * Up to 10 absolute image URLs for a product: the featured image first, then
 * the gallery.
 *
 * @return string[]
 */
function wholesale_product_sitemap_images($product_id)
{
	$images = array();
	$featured = get_the_post_thumbnail_url($product_id, 'full');
	if ($featured) {
		$images[] = $featured;
	}

	$gallery = get_post_meta($product_id, '_product_gallery', true);
	if (is_array($gallery)) {
		foreach ($gallery as $url) {
			if (is_string($url) && '' !== $url) {
				$images[] = $url;
			}
		}
	}

	// Gallery meta stores absolute URLs from whichever host saved them (often
	// http://), so point anything under /wp-content/uploads/ at this site's
	// uploads URL to keep one canonical address per image.
	$uploads = wp_get_upload_dir();
	foreach ($images as $index => $url) {
		$position = strpos($url, '/wp-content/uploads/');
		if (false !== $position) {
			$relative = substr($url, $position + strlen('/wp-content/uploads'));
			// Skip gallery entries whose file was deleted; Google reports them as errors.
			$images[$index] = file_exists(untrailingslashit($uploads['basedir']) . rawurldecode($relative))
				? untrailingslashit($uploads['baseurl']) . $relative
				: '';
		}
	}

	$images = array_values(array_unique(array_filter(array_map('esc_url_raw', $images))));
	return array_slice($images, 0, 10);
}

function wholesale_render_product_sitemap()
{
	if ('1' !== get_query_var('wholesale_product_sitemap')) {
		return;
	}

	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post__not_in' => wholesale_seo_duplicate_product_ids(),
		'orderby' => 'modified',
		'order' => 'DESC',
		'no_found_rows' => true,
	));

	nocache_headers();
	header('Content-Type: application/xml; charset=UTF-8');
	header('X-Robots-Tag: noindex, follow');
	status_header(200);

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

	foreach ($products as $product) {
		$url = get_permalink($product);
		if (!$url) {
			continue;
		}

		echo "\t<url>\n";
		echo "\t\t<loc>" . esc_xml(esc_url_raw($url)) . "</loc>\n";
		echo "\t\t<lastmod>" . esc_xml(get_post_modified_time('c', true, $product)) . "</lastmod>\n";
		foreach (wholesale_product_sitemap_images($product->ID) as $image) {
			echo "\t\t<image:image>\n\t\t\t<image:loc>" . esc_xml($image) . "</image:loc>\n\t\t</image:image>\n";
		}
		echo "\t</url>\n";
	}

	echo '</urlset>';
	exit;
}
add_action('template_redirect', 'wholesale_render_product_sitemap', 0);

function wholesale_render_category_sitemap()
{
	if ('1' !== get_query_var('wholesale_category_sitemap')) {
		return;
	}

	$terms = get_terms(array(
		'taxonomy' => 'product_category',
		'hide_empty' => true,
		'orderby' => 'name',
	));
	$terms = is_wp_error($terms) ? array() : $terms;

	nocache_headers();
	header('Content-Type: application/xml; charset=UTF-8');
	header('X-Robots-Tag: noindex, follow');
	status_header(200);

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

	$seen = array();
	foreach ($terms as $term) {
		// Same exclusions as /sitemap.xml: redirected or canonicalized routes.
		if (in_array($term->slug, wholesale_seo_sitemap_excluded_categories(), true)) {
			continue;
		}

		$url = esc_url_raw(wholesale_category_url($term->slug));
		if (!$url || isset($seen[$url])) {
			continue;
		}
		$seen[$url] = true;

		// The products the category page lists, newest change first.
		$products = get_posts(array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'post__not_in' => wholesale_seo_duplicate_product_ids(),
			'orderby' => 'modified',
			'order' => 'DESC',
			'no_found_rows' => true,
			'tax_query' => array(
				array('taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $term->term_id),
			),
			'meta_query' => array(
				array('key' => '_show_in_list', 'value' => 'on'),
			),
		));

		$images = array();
		foreach ($products as $product) {
			$product_images = wholesale_product_sitemap_images($product->ID);
			if ($product_images && !in_array($product_images[0], $images, true)) {
				$images[] = $product_images[0];
			}
			if (count($images) >= 10) {
				break;
			}
		}

		echo "\t<url>\n";
		echo "\t\t<loc>" . esc_xml($url) . "</loc>\n";
		if ($products) {
			echo "\t\t<lastmod>" . esc_xml(get_post_modified_time('c', true, $products[0])) . "</lastmod>\n";
		}
		foreach ($images as $image) {
			echo "\t\t<image:image>\n\t\t\t<image:loc>" . esc_xml($image) . "</image:loc>\n\t\t</image:image>\n";
		}
		echo "\t</url>\n";
	}

	echo '</urlset>';
	exit;
}
add_action('template_redirect', 'wholesale_render_category_sitemap', 0);

/**
 * Announce the theme sitemaps in robots.txt.
 */
add_filter('robots_txt', function ($output, $public) {
	if (!$public) {
		return $output;
	}

	// Core adds its own wp-sitemap.xml line first, which stops
	// wholesale_robots_txt() from listing /sitemap.xml; list all three here.
	foreach (array('/sitemap.xml', '/product-sitemap.xml', '/category-sitemap.xml') as $path) {
		$url = esc_url(home_url($path));
		if (false === strpos($output, $url)) {
			$output = rtrim($output) . "\nSitemap: " . $url . "\n";
		}
	}

	return $output;
}, 20, 2);
