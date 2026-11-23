<?php
/**
 * Centralized alt text generation for product images.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Is this alt text already written by a person, or only an auto-generated value?
 *
 * @param string $alt Raw alt text.
 * @return bool
 */
function wholesale_product_image_alt_is_auto_generated($alt)
{
	$alt = trim((string) $alt);
	if ('' === $alt) {
		return true;
	}

	$lower = strtolower($alt);
	$lower = preg_replace('/\s+/', ' ', $lower);
	if ('' === $lower) {
		return true;
	}

	if (preg_match('/^(?:img[_-]?\d+|dsc[_-]?\d+|image[-_ ]?\d+|photo[-_ ]?\d+|picture[-_ ]?\d+|screenshot|image|photo|picture)$/i', $lower)) {
		return true;
	}

	if (preg_match('/^[0-9a-f]{8,}$/i', $lower)) {
		return true;
	}

	if (preg_match('/\.(?:jpe?g|png|webp|gif|bmp)$/i', $alt)) {
		return true;
	}

	if (preg_match('/\b(?:img|dsc|image|photo|picture|screenshot)\b/i', $alt) && preg_match('/\d/', $alt)) {
		return true;
	}

	return false;
}

/**
 * Product title text for product alt generation.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function wholesale_product_image_alt_product_title($product_id)
{
	$product_id = (int) $product_id;
	if (!$product_id) {
		return '';
	}

	$heading = '';
	if (function_exists('wholesale_seo_channel_letter_product')) {
		$seo = wholesale_seo_channel_letter_product($product_id);
		if (!empty($seo['heading'])) {
			$heading = trim((string) $seo['heading']);
		}
	}

	if ('' === $heading && function_exists('wholesale_seo_product_name')) {
		$heading = wholesale_seo_product_name($product_id);
	}

	if ('' === $heading) {
		$heading = trim(wp_strip_all_tags(get_the_title($product_id)));
	}

	return trim(preg_replace('/\s+/', ' ', $heading));
}

/**
 * Best product category label for a product image alt.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function wholesale_product_image_alt_category_name($product_id)
{
	$product_id = (int) $product_id;
	if (!$product_id) {
		return '';
	}

	$terms = get_the_terms($product_id, 'product_category');
	if (!$terms || is_wp_error($terms) || empty($terms)) {
		return '';
	}

	$term = $terms[0];
	foreach ($terms as $candidate) {
		if ($candidate->parent) {
			$term = $candidate;
		}
	}

	return trim((string) $term->name);
}

/**
 * Thumbnail or gallery attachment ID for a product image.
 *
 * @param int $product_id Product ID.
 * @param int $attachment_id Attachment ID override.
 * @param string $url Optional image URL.
 * @return int
 */
function wholesale_product_image_alt_resolve_attachment_id($product_id, $attachment_id = 0, $url = '')
{
	$product_id = (int) $product_id;
	$attachment_id = (int) $attachment_id;
	if ($attachment_id) {
		return $attachment_id;
	}

	if ($product_id) {
		$featured = get_post_thumbnail_id($product_id);
		if ($featured) {
			return (int) $featured;
		}
	}

	if ($url) {
		static $cache = array();
		$key = md5($url);
		if (!isset($cache[$key])) {
			$cache[$key] = attachment_url_to_postid($url);
		}
		return (int) $cache[$key];
	}

	return 0;
}

/**
 * Select a useful descriptive view label from attachment metadata.
 *
 * @param int $attachment_id Attachment ID.
 * @param int $index Gallery index.
 * @return string
 */
function wholesale_product_image_alt_view_label($attachment_id, $index)
{
	$attachment_id = (int) $attachment_id;
	$attachment_title = $attachment_id ? (string) get_the_title($attachment_id) : '';
	$attachment_caption = $attachment_id ? (string) wp_get_attachment_caption($attachment_id) : '';
	$source = trim($attachment_title . ' ' . $attachment_caption);
	$source = preg_replace('/\s+/', ' ', $source);
	$source = strtolower($source);

	if (preg_match('/\b(?:night|after dark|lit at night|night shot|evening)\b/i', $source)) {
		return 'lit at night';
	}
	if (preg_match('/\b(?:side|profile|side view|left side|right side)\b/i', $source)) {
		return 'side view';
	}
	if (preg_match('/\b(?:raceway|flush|mount|mounted|installed|wall mount|channel letter)\b/i', $source)) {
		return 'on raceway';
	}
	if (preg_match('/\b(?:close[- ]?up|detail|macro|zoom|trim cap|return|face)\b/i', $source)) {
		return 'close-up of the face';
	}
	if (preg_match('/\b(?:installed|mockup|front|back|halo|trimless|detail|return)\b/i', $source)) {
		return 'detail view';
	}
	if (0 === $index) {
		return 'featured view';
	}

	return 'view ' . max(1, (int) $index);
}

/**
 * Extract useful product detail text from known product meta keys.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function wholesale_product_image_alt_product_detail($product_id)
{
	$product_id = (int) $product_id;
	if (!$product_id) {
		return '';
	}

	$keys = array(
		'_illumination',
		'_light_type',
		'_lighting',
		'_mounting',
		'_mount',
		'_material',
		'_face_material',
		'_return_material',
		'_trimcap_material',
		'_indoor_outdoor',
		'_outdoor',
		'_indoor',
	);

	foreach ($keys as $key) {
		$value = trim((string) get_post_meta($product_id, $key, true));
		if ('' !== $value) {
			$value = preg_replace('/\s+/', ' ', $value);
			if ('' !== $value) {
				return $value;
			}
		}
	}

	$product_attr = get_post_meta($product_id, 'product_attr', true);
	if (is_string($product_attr) && '' !== trim($product_attr)) {
		$attrs = json_decode($product_attr, true);
		if (is_array($attrs)) {
			foreach ($attrs as $attr) {
				if (!isset($attr['name']) || !is_array($attr['options'] ?? null)) {
					continue;
				}
				$name = strtolower((string) $attr['name']);
				if (false !== strpos($name, 'illum') || false !== strpos($name, 'light') || false !== strpos($name, 'mount') || false !== strpos($name, 'material') || false !== strpos($name, 'indoor') || false !== strpos($name, 'outdoor')) {
					foreach ($attr['options'] as $option) {
						if (is_array($option)) {
							foreach ($option as $label => $value) {
								$label = trim((string) $label);
								if ('' !== $label) {
									return $label;
								}
							}
						}
					}
				}
			}
		}
	}

	return '';
}

/**
 * Build a product image alt from product SEO title, category and useful details.
 *
 * @param int $product_id Product ID.
 * @param int $attachment_id Attachment ID.
 * @param int $index Gallery index.
 * @return string
 */
function wholesale_product_image_alt($product_id, $attachment_id = 0, $index = 0)
{
	$product_id = (int) $product_id;
	$attachment_id = (int) $attachment_id;
	$index = max(0, (int) $index);

	if (!$product_id) {
		return '';
	}

	if ($attachment_id) {
		$existing = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
		if ('' !== $existing && !wholesale_product_image_alt_is_auto_generated($existing)) {
			return $existing;
		}
	}

	$product_name = wholesale_product_image_alt_product_title($product_id);
	$category_name = wholesale_product_image_alt_category_name($product_id);
	$detail = wholesale_product_image_alt_product_detail($product_id);
	$detail = preg_replace('/\s+/', ' ', (string) $detail);
	$detail = trim($detail, " .");

	if (0 === $index) {
		$base = $product_name ? $product_name : get_the_title($product_id);
		$category = $category_name ? $category_name : 'sign';
		$text = sprintf('%s – %s by Storefront Sign Online', $base, $category);
	} else {
		$view = wholesale_product_image_alt_view_label($attachment_id, $index);
		$prefix = $detail ? $detail : $category_name;
		$prefix = $prefix ? $prefix : 'product';
		$text = sprintf('%s %s', $product_name ? $product_name : 'Product', $view);
		if ('' !== $detail && !preg_match('/\b' . preg_quote(strtolower($product_name), '/') . '\b/i', strtolower($detail))) {
			$text = sprintf('%s %s', $product_name ? $product_name : 'Product', $detail);
		}
		if (0 === $index || !preg_match('/\b(?:view|detail|close|night|side|raceway|mounted|installed|mockup)\b/i', strtolower($text))) {
			$text = sprintf('%s %s', $product_name ? $product_name : 'Product', $view);
		}
	}

	$text = preg_replace('/\s+/', ' ', $text);
	$text = trim(strip_tags($text), " \t\n\r");
	$text = preg_replace('/\s+[–-]\s+/', ' – ', $text);
	$text = preg_replace('/\s+([,.;:])/u', '$1', $text);
	$text = preg_replace('/\.+$/', '', $text);
	$text = trim($text, " \t\n\r");
	$text = preg_replace('/\s+/', ' ', $text);

	if (mb_strlen($text) > 125) {
		$text = mb_substr($text, 0, 125);
		$text = preg_replace('/\s+\S*$/u', '', $text);
		$text = trim($text, " \t\n\r.-");
	}

	if (str_word_count(wp_strip_all_tags($text), 0, 'A-Za-z0-9') < 5) {
		$text = $product_name ? $product_name . ' view ' . ($index ? $index : 1) : 'Product view ' . ($index ? $index : 1);
	}

	return $text;
}

/**
 * Use the product generator whenever a product image has an empty alt.
 *
 * @param array $attr Image attributes.
 * @param int|WP_Post $attachment Attachment ID or object.
 * @return array
 */
function wholesale_product_image_alt_attachment_attributes($attr, $attachment)
{
	if (is_admin()) {
		return $attr;
	}

	if (!is_object($attachment) && !is_numeric($attachment)) {
		return $attr;
	}

	$attachment_id = is_object($attachment) ? (int) $attachment->ID : (int) $attachment;
	if (!$attachment_id) {
		return $attr;
	}

	$decorative = (bool) get_post_meta($attachment_id, '_wp_attachment_is_decorative', true);
	if ($decorative) {
		return $attr;
	}

	// Product photos described one by one in data/image-alts.php win over the
	// product name the templates pass in.
	$file = (string) get_post_meta($attachment_id, '_wp_attached_file', true);
	$photo_alt = $file && function_exists('wholesale_product_photo_alt') ? wholesale_product_photo_alt($file) : '';
	if ('' !== $photo_alt) {
		$attr['alt'] = $photo_alt;
		return $attr;
	}

	$alt = isset($attr['alt']) ? trim((string) $attr['alt']) : '';
	if ('' !== $alt && !wholesale_product_image_alt_is_auto_generated($alt)) {
		return $attr;
	}

	$product_id = 0;
	$parent = wp_get_post_parent_id($attachment_id);
	if ($parent && 'product' === get_post_type($parent)) {
		$product_id = (int) $parent;
	}

	if (!$product_id) {
		$product_id = wholesale_product_image_alt_find_product_id_by_attachment($attachment_id);
	}

	if ($product_id) {
		$attr['alt'] = wholesale_product_image_alt($product_id, $attachment_id, !empty($attr['data-gallery-index']) ? (int) $attr['data-gallery-index'] : 0);
	}

	return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'wholesale_product_image_alt_attachment_attributes', 20, 2);

/**
 * Find the product that uses an attachment, whether featured or gallery.
 *
 * @param int $attachment_id Attachment ID.
 * @return int
 */
function wholesale_product_image_alt_find_product_id_by_attachment($attachment_id)
{
	$attachment_id = (int) $attachment_id;
	if (!$attachment_id) {
		return 0;
	}

	$map = wholesale_product_image_alt_attachment_product_map();

	return isset($map[$attachment_id]) ? $map[$attachment_id] : 0;
}

/**
 * Attachment ID => product ID for every product's featured and gallery
 * images, built once per request. The first product in query order that
 * uses an image wins, as a product-by-product scan would find it.
 *
 * @return array
 */
function wholesale_product_image_alt_attachment_product_map()
{
	static $map = null;
	if (null !== $map) {
		return $map;
	}
	$map = array();

	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
	));
	if (!$products) {
		return $map;
	}

	// Load every product row and its meta in one go instead of per product.
	_prime_post_caches($products, false, true);

	$galleries = array();
	$urls = array();
	foreach ($products as $product_id) {
		$gallery = get_post_meta($product_id, '_product_gallery', true);
		$galleries[$product_id] = is_array($gallery) ? $gallery : array();
		foreach ($galleries[$product_id] as $value) {
			if (is_string($value)) {
				$urls[$value] = true;
			}
		}
	}
	$url_ids = wholesale_product_image_alt_attachment_ids_by_url(array_keys($urls));

	foreach ($products as $product_id) {
		$used = array((int) get_post_thumbnail_id($product_id));
		foreach ($galleries[$product_id] as $key => $value) {
			if (is_numeric($key)) {
				$used[] = (int) $key;
			}
			if (is_string($value) && !empty($url_ids[$value])) {
				$used[] = $url_ids[$value];
			}
		}
		foreach ($used as $id) {
			if ($id && !isset($map[$id])) {
				$map[$id] = (int) $product_id;
			}
		}
	}

	return $map;
}

/**
 * attachment_url_to_postid() for many URLs with one query per 200 URLs.
 *
 * @param string[] $urls Image URLs.
 * @return array URL => attachment ID (0 when not found).
 */
function wholesale_product_image_alt_attachment_ids_by_url($urls)
{
	global $wpdb;

	$ids = array();
	if (!$urls) {
		return $ids;
	}

	// Plugins that filter the lookup get the core function, one URL at a time.
	if (has_filter('pre_attachment_url_to_postid') || has_filter('attachment_url_to_postid')) {
		foreach ($urls as $url) {
			$ids[$url] = (int) attachment_url_to_postid($url);
		}
		return $ids;
	}

	// Same path rules as attachment_url_to_postid().
	$dir = wp_get_upload_dir();
	$site_url = parse_url($dir['url']);
	$paths = array();
	foreach ($urls as $url) {
		$path = $url;
		$image_path = parse_url($path);
		if (isset($image_path['scheme']) && $image_path['scheme'] !== $site_url['scheme']) {
			$path = str_replace($image_path['scheme'], $site_url['scheme'], $path);
		}
		if (str_starts_with($path, $dir['baseurl'] . '/')) {
			$path = substr($path, strlen($dir['baseurl'] . '/'));
		}
		$paths[$url] = $path;
	}

	// The meta_value comparison ignores case, so group rows the same way.
	$rows = array();
	foreach (array_chunk(array_values(array_unique($paths)), 200) as $chunk) {
		$placeholders = implode(',', array_fill(0, count($chunk), '%s'));
		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value IN ($placeholders) ORDER BY meta_id",
			$chunk
		));
		foreach ($results as $row) {
			$rows[strtolower($row->meta_value)][] = $row;
		}
	}

	foreach ($paths as $url => $path) {
		$matches = isset($rows[strtolower($path)]) ? $rows[strtolower($path)] : array();
		$post_id = 0;
		if ($matches) {
			// First result, but prefer a case-sensitive match, as core does.
			$post_id = (int) reset($matches)->post_id;
			foreach ($matches as $row) {
				if ($path === $row->meta_value) {
					$post_id = (int) $row->post_id;
					break;
				}
			}
		}
		$ids[$url] = $post_id;
	}

	return $ids;
}

/**
 * Save product image alt text for featured and gallery images when missing.
 *
 * @param int $post_id Product ID.
 */
function wholesale_product_image_alt_backfill_product($post_id)
{
	$post_id = (int) $post_id;
	if (!$post_id || 'product' !== get_post_type($post_id)) {
		return;
	}

	$featured_id = (int) get_post_thumbnail_id($post_id);
	if ($featured_id) {
		$alt = trim((string) get_post_meta($featured_id, '_wp_attachment_image_alt', true));
		if ('' === $alt || wholesale_product_image_alt_is_auto_generated($alt)) {
			$generated = wholesale_product_image_alt($post_id, $featured_id, 0);
			if ('' !== $generated) {
				update_post_meta($featured_id, '_wp_attachment_image_alt', wp_slash($generated));
			}
		}
	}

	$gallery = get_post_meta($post_id, '_product_gallery', true);
	if (!is_array($gallery)) {
		return;
	}

	foreach ($gallery as $key => $value) {
		$attachment_id = is_numeric($key) ? (int) $key : attachment_url_to_postid((string) $value);
		if (!$attachment_id) {
			continue;
		}
		$alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
		if ('' === $alt || wholesale_product_image_alt_is_auto_generated($alt)) {
			$generated = wholesale_product_image_alt($post_id, $attachment_id, 1);
			if ('' !== $generated) {
				update_post_meta($attachment_id, '_wp_attachment_image_alt', wp_slash($generated));
			}
		}
	}
}
add_action('save_post_product', 'wholesale_product_image_alt_backfill_product', 20);

/**
 * WP-CLI command for previewing and writing product image alt text.
 */
if (defined('WP_CLI') && WP_CLI) {
	class WP_CLI_Wholesale_Product_Image_Alt extends WP_CLI_Command
	{
		/**
		 * Preview or generate product image alt text.
		 *
		 * ## OPTIONS
		 *
		 * [--dry-run]
		 * : Print the planned changes without updating the database.
		 *
		 * [--force]
		 * : Only update alt values that look auto-generated.
		 *
		 * [--product=<id>]
		 * : Limit the run to one product ID.
		 *
		 * @when after_wp_load
		 */
		public function __invoke($args, $assoc_args)
		{
			$dry_run = !empty($assoc_args['dry-run']);
			$force = !empty($assoc_args['force']);
			$product_id = !empty($assoc_args['product']) ? (int) $assoc_args['product'] : 0;
			$products = array();

			if ($product_id) {
				$products[] = $product_id;
			} else {
				$products = get_posts(array(
					'post_type' => 'product',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'fields' => 'ids',
					'no_found_rows' => true,
				));
			}

			$rows = array();
			foreach ($products as $id) {
				$featured = get_post_thumbnail_id($id);
				if ($featured) {
					$alt = trim((string) get_post_meta($featured, '_wp_attachment_image_alt', true));
					$would = wholesale_product_image_alt($id, $featured, 0);
					if ('' !== $would && (!$force || '' === $alt || wholesale_product_image_alt_is_auto_generated($alt))) {
						$rows[] = array($id, $featured, $alt, $would, 'featured');
					}
				}

				$gallery = get_post_meta($id, '_product_gallery', true);
				if (is_array($gallery)) {
					$i = 1;
					foreach ($gallery as $key => $value) {
						$attachment_id = is_numeric($key) ? (int) $key : attachment_url_to_postid((string) $value);
						if (!$attachment_id) {
							continue;
						}
						$alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
						$would = wholesale_product_image_alt($id, $attachment_id, $i);
						if ('' !== $would && (!$force || '' === $alt || wholesale_product_image_alt_is_auto_generated($alt))) {
							$rows[] = array($id, $attachment_id, $alt, $would, 'gallery');
						}
						$i++;
					}
				}
			}

			if ($dry_run) {
				if (!$rows) {
					WP_CLI::success('No missing product alt text found.');
					return;
				}
				WP_CLI::line('product_id	attachment_id	old_alt	new_alt	role');
				foreach ($rows as $row) {
					list($product_id, $attachment_id, $old_alt, $new_alt, $role) = $row;
					WP_CLI::line(sprintf('%d	%d	%s	%s	%s', $product_id, $attachment_id, $old_alt, $new_alt, $role));
				}
				return;
			}

			foreach ($rows as $row) {
				list($product_id, $attachment_id, $old_alt, $new_alt, $role) = $row;
				update_post_meta($attachment_id, '_wp_attachment_image_alt', wp_slash($new_alt));
				WP_CLI::line(sprintf('Updated product %d %s image %d: %s', $product_id, $role, $attachment_id, $new_alt));
			}
			WP_CLI::success(sprintf('Updated %d product image alt values.', count($rows)));
		}
	}

	WP_CLI::add_command('wholesale product-alt', 'WP_CLI_Wholesale_Product_Image_Alt');
}
