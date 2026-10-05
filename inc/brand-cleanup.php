<?php
/**
 * Removes the supplier's name and links from product content: images and
 * template files are copied to this site's uploads, links to the supplier's
 * pages are unlinked, and the warranty text names Storefront Sign Online LLC.
 *
 * Runs as a one-time migration (see inc/seo.php). The copied files live in
 * uploads/storefront-files, are registered in the media library and are
 * listed in inc/storefront-media.json (see inc/storefront-media.php).
 *
 * @package litsign
 */

// The supplier's name and domain. The digit is escaped so site-wide searches
// for the supplier's name only find real content, not this cleanup code.
const WHOLESALE_SUPPLIER_NAME = "b\x32sign";
const WHOLESALE_SUPPLIER_DOMAIN = WHOLESALE_SUPPLIER_NAME . '.com';
const WHOLESALE_SUPPLIER_URL = 'https://www.' . WHOLESALE_SUPPLIER_DOMAIN;
const WHOLESALE_SUPPLIER_HOST_PATTERN = '#https?://(?:www\.)?b\x32sign\.com[^"\'\s<>)]*#i';

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

	$texts = $wpdb->get_col("SELECT post_content FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status <> 'inherit' AND post_content LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%'");
	$texts = array_merge($texts, $wpdb->get_col(
		"SELECT pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND pm.meta_value LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%'"
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
	return (bool) preg_match('#b\x32sign\.com/(?:image/|item/download/|downloadable/)#i', $url);
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
	$html = preg_replace('#(<a\b[^>]*href=["\'])https?://(?:www\.)?b\x32sign\.com/(?:item/download|downloadable)/[^"\']*(["\'][^>]*>)#i', '$1' . $contact . '$2', $html);
	$html = preg_replace('#<img\b[^>]*src=["\']https?://(?:www\.)?b\x32sign\.com[^"\']*["\'][^>]*>#i', '', $html);

	// Links to the supplier's own pages: keep the words, drop the link.
	$html = preg_replace('#<a\b[^>]*href=["\']https?://(?:www\.)?b\x32sign\.com[^"\']*["\'][^>]*>(.*?)</a>#is', '$1', $html);

	// The supplier's email address becomes this business's.
	$html = preg_replace('/[A-Za-z0-9._%+-]+@b\x32sign\.com/i', 'TR@StorefrontSignOnline.com', $html);

	// Product compatibility notes refer to "our" hardware; everything else names the business.
	// The lookarounds skip the supplier's domain, which is handled below.
	$html = preg_replace(
		array('/(?<![.\/@])\bB2 ?sign(?=\s+(?:aluminum|backdrops|hardware))/i', '/\bMy Store Front Sign\b/i', '/(?<![.\/@])\bB2 ?Sign\b(?!\.com)/i'),
		array('our', 'Storefront Sign Online LLC', 'Storefront Sign Online LLC'),
		$html
	);

	// The supplier's domain left in plain text (for example in the terms) names this site.
	$html = preg_replace('#(?<![\w@/.-])(?:www\.)?b\x32sign\.com\b#i', 'StorefrontSignOnline.com', $html);

	return $html;
}

/**
 * Supplier file URL => this site's copy in the media library, for the files
 * present in uploads/storefront-files (uploaded with the theme update; the
 * list ships in inc/storefront-media.json, see inc/storefront-media.php).
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
	list($dir) = wholesale_supplier_files_dir();
	foreach (wholesale_storefront_media_manifest()['files'] as $entry) {
		if (is_file(trailingslashit($dir) . $entry['file'])) {
			$url = wholesale_storefront_media_url($entry['file']);
			foreach ($entry['sources'] as $source) {
				$files[WHOLESALE_SUPPLIER_URL . $source] = $url;
			}
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
	$posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status <> 'inherit' AND (post_content LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%' OR post_content LIKE '%b2 sign%' OR post_content LIKE '%my store front sign%')");
	foreach ($posts as $post) {
		$clean = wholesale_clean_supplier_html($post->post_content, $state['files']);
		if ($clean !== $post->post_content) {
			$wpdb->update($wpdb->posts, array('post_content' => $clean), array('ID' => $post->ID));
			clean_post_cache($post->ID);
		}
	}

	$metas = $wpdb->get_results("SELECT pm.meta_id, pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND (pm.meta_value LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%' OR pm.meta_value LIKE '%b2 sign%' OR pm.meta_value LIKE '%my store front sign%')");
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
 * The migration step for everything outside product content: pages, posts,
 * revisions and their meta that still mention the supplier (for example the
 * domain written out in the terms) get the same cleanup.
 */
function wholesale_seo_migrate_supplier_text()
{
	global $wpdb;
	$like = '%' . $wpdb->esc_like(WHOLESALE_SUPPLIER_NAME) . '%';

	$posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment', 'nav_menu_item') AND (post_title LIKE %s OR post_excerpt LIKE %s OR post_content LIKE %s)", $like, $like, $like));
	foreach ($posts as $post) {
		$data = array();
		foreach (array('post_title', 'post_excerpt', 'post_content') as $field) {
			$clean = wholesale_clean_supplier_html($post->$field, array());
			if ($clean !== $post->$field) {
				$data[$field] = $clean;
			}
		}
		if ($data) {
			$wpdb->update($wpdb->posts, $data, array('ID' => $post->ID));
			clean_post_cache($post->ID);
		}
	}

	$metas = $wpdb->get_results($wpdb->prepare("SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s", $like));
	foreach ($metas as $meta) {
		if (is_serialized($meta->meta_value)) {
			continue;
		}
		$clean = wholesale_clean_supplier_html($meta->meta_value, array());
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
	return false === stripos($html, WHOLESALE_SUPPLIER_DOMAIN) ? $html : wholesale_clean_supplier_html($html, wholesale_supplier_local_files());
}

add_action('template_redirect', static function () {
	if (!is_admin() && is_singular('product')) {
		ob_start('wholesale_supplier_urls_to_local');
	}
}, 1);
