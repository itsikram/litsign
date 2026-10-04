<?php
/**
 * Removes the supplier's name and links from product content: images and
 * template files are copied to this site's uploads, links to the supplier's
 * pages are unlinked, and the warranty text names Storefront Sign Online LLC.
 *
 * Runs as a one-time migration (see inc/seo.php). The copied files live in
 * uploads/storefront-files and are listed in inc/storefront-files.json.
 *
 * @package litsign
 */

const WHOLESALE_SUPPLIER_HOST_PATTERN = '#https?://(?:www\.)?b2sign\.com[^"\'\s<>)]*#i';

/**
 * Folder in uploads that holds the copied files.
 *
 * @return array array(path, url)
 */
function wholesale_supplier_files_dir()
{
	$uploads = wp_upload_dir(null, false);

	return array(trailingslashit($uploads['basedir']) . 'storefront-files', trailingslashit($uploads['baseurl']) . 'storefront-files');
}

/**
 * Supplier URLs still present in product content and product meta.
 *
 * @return string[]
 */
function wholesale_supplier_urls_in_content()
{
	global $wpdb;

	$texts = $wpdb->get_col("SELECT post_content FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status <> 'inherit' AND post_content LIKE '%b2sign.com%'");
	$texts = array_merge($texts, $wpdb->get_col(
		"SELECT pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND pm.meta_value LIKE '%b2sign.com%'"
	));

	$urls = array();
	foreach ($texts as $text) {
		if (preg_match_all(WHOLESALE_SUPPLIER_HOST_PATTERN, (string) $text, $matches)) {
			foreach ($matches[0] as $url) {
				$urls[html_entity_decode($url, ENT_QUOTES)] = true;
			}
		}
	}

	return array_keys($urls);
}

/**
 * Whether a supplier URL is a file to copy (image or downloadable template)
 * rather than a link to one of the supplier's pages.
 */
function wholesale_supplier_url_is_file($url)
{
	return (bool) preg_match('#b2sign\.com/(?:image/|item/download/|downloadable/)#i', $url);
}

/**
 * Rewrite supplier references in one piece of HTML.
 *
 * @param array $files Supplier file URL => local URL.
 */
function wholesale_clean_supplier_html($html, $files)
{
	$html = (string) $html;
	if (false === stripos($html, 'b2') && false === stripos($html, 'my store front sign')) {
		return $html;
	}

	// Copied files: point at this site's copy.
	foreach ($files as $remote => $local) {
		$html = str_replace(array($remote, esc_attr($remote), str_replace('&', '&amp;', $remote)), $local, $html);
	}

	// Files that could not be copied (large design templates): link to the
	// contact page, where customers can ask for them.
	$contact = esc_url(home_url('/contact/'));
	$html = preg_replace('#(<a\b[^>]*href=["\'])https?://(?:www\.)?b2sign\.com/(?:item/download|downloadable)/[^"\']*(["\'][^>]*>)#i', '$1' . $contact . '$2', $html);
	$html = preg_replace('#<img\b[^>]*src=["\']https?://(?:www\.)?b2sign\.com[^"\']*["\'][^>]*>#i', '', $html);

	// Links to the supplier's own pages: keep the words, drop the link.
	$html = preg_replace('#<a\b[^>]*href=["\']https?://(?:www\.)?b2sign\.com[^"\']*["\'][^>]*>(.*?)</a>#is', '$1', $html);

	// The supplier's email address becomes this business's.
	$html = preg_replace('/[A-Za-z0-9._%+-]+@b2sign\.com/i', 'TR@StorefrontSignOnline.com', $html);

	// Product compatibility notes refer to "our" hardware; everything else names the business.
	// The lookarounds leave any remaining b2sign.com address alone.
	$html = preg_replace(
		array('/(?<![.\/@])\bB2 ?sign(?=\s+(?:aluminum|backdrops|hardware))/i', '/\bMy Store Front Sign\b/i', '/(?<![.\/@])\bB2 ?Sign\b(?!\.com)/i'),
		array('our', 'Storefront Sign Online LLC', 'Storefront Sign Online LLC'),
		$html
	);

	return $html;
}

/**
 * Supplier file URL => this site's copy, for the copies present in
 * uploads/storefront-files (uploaded with the theme update; the list ships
 * in inc/storefront-files.json).
 *
 * @return array
 */
function wholesale_supplier_local_files()
{
	static $files = null;
	if (null !== $files) {
		return $files;
	}

	$files = array();
	$map = json_decode((string) @file_get_contents(get_template_directory() . '/inc/storefront-files.json'), true);
	list($dir, $base_url) = wholesale_supplier_files_dir();
	foreach (is_array($map) ? $map : array() as $url => $name) {
		if (is_file(trailingslashit($dir) . $name)) {
			$files[$url] = trailingslashit($base_url) . rawurlencode($name);
		}
	}

	return $files;
}

/**
 * The migration step: rewrite product content to use this site's copies and
 * drop every supplier name and link. Waits (returns false) until the copied
 * files folder has been uploaded, so images are never removed by mistake.
 */
function wholesale_seo_migrate_remove_supplier_references()
{
	$files = wholesale_supplier_local_files();
	if (!$files) {
		return false;
	}
	$state = array('files' => $files);

	global $wpdb;
	$posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status <> 'inherit' AND (post_content LIKE '%b2sign%' OR post_content LIKE '%b2 sign%' OR post_content LIKE '%my store front sign%')");
	foreach ($posts as $post) {
		$clean = wholesale_clean_supplier_html($post->post_content, $state['files']);
		if ($clean !== $post->post_content) {
			$wpdb->update($wpdb->posts, array('post_content' => $clean), array('ID' => $post->ID));
			clean_post_cache($post->ID);
		}
	}

	$metas = $wpdb->get_results("SELECT pm.meta_id, pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND (pm.meta_value LIKE '%b2sign%' OR pm.meta_value LIKE '%b2 sign%' OR pm.meta_value LIKE '%my store front sign%')");
	foreach ($metas as $meta) {
		if (is_serialized($meta->meta_value)) {
			continue;
		}
		$clean = wholesale_clean_supplier_html($meta->meta_value, $state['files']);
		if ($clean !== $meta->meta_value) {
			$wpdb->update($wpdb->postmeta, array('meta_value' => $clean), array('meta_id' => $meta->meta_id));
			wp_cache_delete($meta->post_id, 'post_meta');
		}
	}

	return true;
}

/**
 * Safety net on the front end: any supplier file URL still printed (for
 * example from a product edited later) is swapped for this site's copy.
 */
function wholesale_supplier_urls_to_local($html)
{
	return false === stripos($html, 'b2sign.com') ? $html : wholesale_clean_supplier_html($html, wholesale_supplier_local_files());
}

add_action('template_redirect', static function () {
	if (!is_admin() && is_singular('product')) {
		ob_start('wholesale_supplier_urls_to_local');
	}
}, 1);
