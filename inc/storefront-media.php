<?php
/**
 * Product images, design templates and installation guides in the media library.
 *
 * Every file product pages used to load from the supplier now lives in
 * uploads/storefront-files under a descriptive file name and is registered as
 * a media library attachment with a title, alt text and parent product. The
 * list of files ships in inc/storefront-media.json:
 *
 * - files: file name, title, alt text, parent product slug, the supplier URL
 *   paths the file replaces and the earlier (random) file names of the same copy.
 * - template_links: per product, the template download links that pointed at
 *   the contact page because the template had not been copied yet.
 * - text_links: per product name, link text that pointed at the supplier's
 *   product pages and the product on this site it now links to.
 * - component_links: channel letter products whose color chart links get the
 *   stainless steel color chart.
 *
 * The migration (see inc/seo.php) runs in steps so a single admin request
 * never has to create every attachment and thumbnail at once.
 *
 * @package litsign
 */

/**
 * The manifest in inc/storefront-media.json.
 *
 * @return array
 */
function wholesale_storefront_media_manifest()
{
	static $manifest = null;
	if (null === $manifest) {
		$manifest = json_decode((string) @file_get_contents(get_template_directory() . '/inc/storefront-media.json'), true);
		$manifest = is_array($manifest) ? $manifest : array();
		$manifest += array('files' => array(), 'template_links' => array(), 'text_links' => array(), 'component_links' => array());
	}

	return $manifest;
}

/**
 * Attachment ID for a file in uploads/storefront-files, or 0.
 */
function wholesale_storefront_media_id($file)
{
	global $wpdb;

	// Look up by the file name recorded at registration: _wp_attached_file
	// changes when WordPress scales a large image down (name-scaled.jpg).
	return (int) $wpdb->get_var($wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = '_wholesale_storefront_file' AND meta_value = %s) OR (meta_key = '_wp_attached_file' AND meta_value = %s) ORDER BY meta_key = '_wholesale_storefront_file' DESC, post_id ASC LIMIT 1",
		$file,
		'storefront-files/' . $file
	));
}

/**
 * Public URL of a storefront file: its media library URL, or the uploads URL
 * while the file is not registered yet.
 */
function wholesale_storefront_media_url($file)
{
	$attachment_id = wholesale_storefront_media_id($file);
	$url = $attachment_id ? wp_get_attachment_url($attachment_id) : '';
	if (!$url) {
		list(, $base_url) = wholesale_supplier_files_dir();
		$url = trailingslashit($base_url) . rawurlencode($file);
	}

	return $url;
}

/**
 * Media type for files WordPress does not know (CorelDRAW, Photoshop).
 */
function wholesale_storefront_media_mime($file)
{
	$type = wp_check_filetype($file);
	$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	$fallback = array('cdr' => 'application/vnd.corel-draw', 'psd' => 'image/vnd.adobe.photoshop', 'pdf' => 'application/pdf');

	return isset($fallback[$ext]) && (!$type['type'] || 'application/octet-stream' === $type['type']) ? $fallback[$ext] : (string) $type['type'];
}

/**
 * Register one manifest file in the media library (or update its text).
 *
 * @return int Attachment ID, or 0 when the file is not on disk.
 */
function wholesale_storefront_media_register($entry)
{
	list($dir) = wholesale_supplier_files_dir();
	$path = trailingslashit($dir) . $entry['file'];
	if (!is_file($path)) {
		return 0;
	}

	$parent = $entry['parent'] ? wholesale_seo_product($entry['parent']) : null;
	$parent_id = $parent ? (int) $parent->ID : 0;
	$mime = wholesale_storefront_media_mime($entry['file']);
	$is_image = 0 === strpos($mime, 'image/') && 'image/vnd.adobe.photoshop' !== $mime;
	$description = $is_image ? $entry['alt'] : sprintf('Free %s download for %s.', $entry['title'], $parent ? html_entity_decode(get_the_title($parent), ENT_QUOTES, 'UTF-8') : 'Storefront Sign Online products');

	$attachment = array(
		'post_title' => $entry['title'],
		'post_name' => sanitize_title(pathinfo($entry['file'], PATHINFO_FILENAME)),
		'post_content' => $description,
		'post_excerpt' => '',
		'post_mime_type' => $mime,
		'post_status' => 'inherit',
		'post_parent' => $parent_id,
	);

	$attachment_id = wholesale_storefront_media_id($entry['file']);
	if ($attachment_id) {
		$attachment['ID'] = $attachment_id;
		wp_update_post($attachment);
	} else {
		$attachment_id = wp_insert_attachment($attachment, $path, $parent_id, true);
		if (is_wp_error($attachment_id)) {
			return 0;
		}
	}
	// Recorded before the slow part, so an interrupted request never
	// registers the file twice.
	update_post_meta($attachment_id, '_wholesale_storefront_file', $entry['file']);

	if (!wp_get_attachment_metadata($attachment_id)) {
		if ($is_image) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			// Keep the descriptive file name as the main URL (no "-scaled"
			// copy); content images get responsive sizes through srcset.
			add_filter('big_image_size_threshold', '__return_false');
			$metadata = wp_generate_attachment_metadata($attachment_id, $path);
			remove_filter('big_image_size_threshold', '__return_false');
		} else {
			// No PDF preview images: the download is the point, and large
			// templates would be slow to render.
			$metadata = array('filesize' => filesize($path));
		}
		wp_update_attachment_metadata($attachment_id, $metadata);
	}

	if ($is_image) {
		update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($entry['alt']));
	}
	if ($entry['parent']) {
		update_post_meta($attachment_id, '_wholesale_storefront_product', $entry['parent']);
	}

	return (int) $attachment_id;
}

/**
 * URL replacements for content: supplier URLs and earlier file names => the
 * media library URL. Old copies are matched on any host, since the site has
 * been served from more than one address.
 *
 * @return array array(plain replacements, regex replacements)
 */
function wholesale_storefront_media_replacements()
{
	$plain = array();
	$regex = array();
	foreach (wholesale_storefront_media_manifest()['files'] as $entry) {
		$url = wholesale_storefront_media_url($entry['file']);
		foreach ($entry['sources'] as $source) {
			$source = WHOLESALE_SUPPLIER_URL . $source;
			foreach (array($source, str_replace('&', '&amp;', $source), set_url_scheme($source, 'http')) as $variant) {
				$plain[$variant] = $url;
			}
		}
		foreach ($entry['old'] as $old) {
			$regex['#(?:https?:)?//[^"\'\s<>()]*?/wp-content/uploads/storefront-files/' . preg_quote($old, '#') . '#'] = $url;
		}
		// The same file at its new name on another host.
		$regex['#(?:https?:)?//[^"\'\s<>()]*?/wp-content/uploads/storefront-files/' . preg_quote($entry['file'], '#') . '(?=["\'\s<>)])#'] = $url;
	}

	return array($plain, $regex);
}

/**
 * Point every supplier URL and earlier copy in a piece of HTML at the media
 * library, then remove what is left of the supplier (names, page links).
 */
function wholesale_storefront_media_rewrite_urls($html)
{
	static $replacements = null;
	if (null === $replacements) {
		$replacements = wholesale_storefront_media_replacements();
	}
	list($plain, $regex) = $replacements;

	$html = strtr((string) $html, $plain);
	$html = preg_replace(array_keys($regex), array_values($regex), $html);

	return wholesale_clean_supplier_html($html, array());
}

/**
 * Give media library images in HTML their alt text, size, class and lazy
 * loading. Alt text that says nothing ("Description", "step1", file types)
 * is replaced; other alt text is kept.
 */
function wholesale_storefront_media_image_tags($html)
{
	static $by_url = null;
	if (null === $by_url) {
		$by_url = array();
		foreach (wholesale_storefront_media_manifest()['files'] as $entry) {
			$attachment_id = wholesale_storefront_media_id($entry['file']);
			if ($attachment_id) {
				$by_url[wholesale_storefront_media_url($entry['file'])] = array($attachment_id, $entry['alt']);
			}
		}
	}

	return preg_replace_callback('/<img\b[^>]*>/i', static function ($match) use ($by_url) {
		$tag = $match[0];
		if (!preg_match('/\ssrc=(["\'])(.*?)\1/i', $tag, $src) || !isset($by_url[$src[2]])) {
			return $tag;
		}
		list($attachment_id, $alt) = $by_url[$src[2]];

		$current = preg_match('/\salt=(["\'])(.*?)\1/i', $tag, $a) ? trim(html_entity_decode($a[2], ENT_QUOTES)) : '';
		$generic = '' === $current || !preg_match('/\s/', $current) || preg_match('/^(description|warning|option|image|step ?\d+)$/i', $current);
		if ($generic && '' !== $alt) {
			$tag = preg_replace('/\salt=(["\']).*?\1/i', '', $tag);
			$tag = preg_replace('/^<img\b/i', '<img alt="' . esc_attr($alt) . '"', $tag);
		}

		if (!preg_match('/\swidth=/i', $tag) && !preg_match('/\sheight=/i', $tag)) {
			$meta = wp_get_attachment_metadata($attachment_id);
			if (!empty($meta['width']) && !empty($meta['height'])) {
				$tag = preg_replace('/^<img\b/i', sprintf('<img width="%d" height="%d"', $meta['width'], $meta['height']), $tag);
			}
		}

		$class = 'wp-image-' . $attachment_id;
		if (preg_match('/\sclass=(["\'])(.*?)\1/i', $tag, $c)) {
			if (false === strpos($c[2], $class)) {
				$tag = str_replace($c[0], ' class=' . $c[1] . trim($c[2] . ' ' . $class) . $c[1], $tag);
			}
		} else {
			$tag = preg_replace('/^<img\b/i', '<img class="' . $class . '"', $tag);
		}

		foreach (array('loading' => 'lazy', 'decoding' => 'async') as $name => $value) {
			if (!preg_match('/\s' . $name . '=/i', $tag)) {
				$tag = preg_replace('#\s*/?>$#', ' ' . $name . '="' . $value . '" />', $tag);
			}
		}

		return $tag;
	}, $html);
}

/**
 * Template download links that still point at the contact page get the
 * template file. Only applied when the links match the manifest exactly, so
 * a product edited since is left alone. Formats that were never offered for
 * a product read "N/A".
 */
function wholesale_storefront_media_template_links($html, $slug)
{
	$links = wholesale_storefront_media_manifest()['template_links'];
	if (empty($links[$slug])) {
		return $html;
	}

	$pattern = '#<a\b[^>]*href=(["\'])[^"\']*/contact/?\1[^>]*>(.*?)</a>#is';
	if (!preg_match_all($pattern, $html, $found) || count($found[0]) !== count($links[$slug])) {
		return $html;
	}
	foreach ($found[2] as $index => $text) {
		$text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'))));
		if ($text !== $links[$slug][$index][0]) {
			return $html;
		}
	}

	$index = 0;

	return preg_replace_callback($pattern, static function ($match) use ($links, $slug, &$index) {
		list($text, $file) = $links[$slug][$index++];
		if (!$file) {
			return '<span class="template-unavailable">N/A</span>';
		}

		return sprintf(
			'<a href="%s" download aria-label="%s">%s</a>',
			esc_url(wholesale_storefront_media_url($file)),
			esc_attr(sprintf('Download %s', wholesale_storefront_media_title($file))),
			esc_html($text)
		);
	}, $html);
}

/**
 * Manifest title for a file.
 */
function wholesale_storefront_media_title($file)
{
	foreach (wholesale_storefront_media_manifest()['files'] as $entry) {
		if ($entry['file'] === $file) {
			return $entry['title'];
		}
	}

	return $file;
}

/**
 * Link the first unlinked occurrence of a piece of text.
 */
function wholesale_storefront_media_link_text($html, $text, $url)
{
	$variants = array_unique(array($text, esc_html($text), str_replace('"', '&quot;', $text), str_replace('"', '&#8221;', $text), str_replace('"', '&rdquo;', $text)));
	$parts = preg_split('#(<a\b.*?</a>|<[^>]+>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
	foreach ($parts as $i => $part) {
		if ('' === $part || '<' === $part[0]) {
			continue;
		}
		foreach ($variants as $variant) {
			$pos = strpos($part, $variant);
			if (false !== $pos) {
				$parts[$i] = substr($part, 0, $pos) . '<a href="' . esc_url($url) . '">' . $variant . '</a>' . substr($part, $pos + strlen($variant));

				return implode('', $parts);
			}
		}
	}

	return $html;
}

/**
 * Restore links that pointed at the supplier's product pages, now to the
 * matching product here, and link "Template User Guide" to the guide page.
 */
function wholesale_storefront_media_text_links($html, $product)
{
	$title = html_entity_decode(get_the_title($product), ENT_QUOTES, 'UTF-8');
	$links = wholesale_storefront_media_manifest()['text_links'];
	if (!empty($links[$title])) {
		foreach ($links[$title] as $link) {
			$target = wholesale_seo_product($link[1]);
			if ($target && false === strpos($html, get_permalink($target))) {
				$html = wholesale_storefront_media_link_text($html, $link[0], get_permalink($target));
			}
		}
	}

	$guide = home_url('/design-templates/#template-guide');
	if (false === strpos($html, '/design-templates/')) {
		$html = wholesale_storefront_media_link_text($html, 'Template User Guide', $guide);
	}

	return $html;
}

/**
 * Color chart links in channel letter component tabs: stainless steel
 * letters get the stainless steel chart; other charts were never copied, so
 * those links say what they do (ask us for the chart).
 */
function wholesale_storefront_media_component_links($html, $slug)
{
	$charts = wholesale_storefront_media_manifest()['component_links'];

	return preg_replace_callback('#<a\b[^>]*href=(["\'])[^"\']*/contact/?\1[^>]*>\s*((?:Download|Same as) [^<]*Color Chart)\s*</a>#i', static function ($match) use ($charts, $slug) {
		if (!empty($charts[$slug])) {
			return sprintf('<a href="%s" download>%s</a>', esc_url(wholesale_storefront_media_url($charts[$slug])), esc_html(preg_replace('/^Same as Return/i', 'Download', $match[2])));
		}

		return str_replace($match[2], preg_replace('/^Download /i', 'Request ', $match[2]), $match[0]);
	}, $html);
}

/**
 * All content changes for one product field: 'content' (the description),
 * 'component' (channel letter component tabs) or 'meta' (any other field,
 * which only gets its URLs and images updated).
 */
function wholesale_storefront_media_product_html($html, $product, $field = 'content')
{
	$html = wholesale_storefront_media_rewrite_urls($html);
	if ('component' === $field) {
		$html = wholesale_storefront_media_component_links($html, $product->post_name);
	} elseif ('content' === $field) {
		$html = wholesale_storefront_media_template_links($html, $product->post_name);
		$html = wholesale_storefront_media_text_links($html, $product);
	}

	return wholesale_storefront_media_image_tags($html);
}

/**
 * The migration step. Registers up to a time budget of files per request and
 * returns false until all are in the media library; then rewrites products,
 * product meta, pages and revisions and removes leftover supplier records.
 */
function wholesale_seo_migrate_storefront_media()
{
	$manifest = wholesale_storefront_media_manifest();
	if (!$manifest['files']) {
		return false;
	}
	list($dir) = wholesale_supplier_files_dir();
	if (!is_file(trailingslashit($dir) . $manifest['files'][0]['file'])) {
		return false; // Files not uploaded yet.
	}

	$started = microtime(true);
	foreach ($manifest['files'] as $entry) {
		$attachment_id = wholesale_storefront_media_id($entry['file']);
		if ($attachment_id && wp_get_attachment_metadata($attachment_id)) {
			continue;
		}
		wholesale_storefront_media_register($entry);
		if (microtime(true) - $started > 15) {
			return false;
		}
	}

	global $wpdb;
	$like = "(post_content LIKE '%storefront-files%' OR post_content LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%' OR post_content LIKE '%/contact/%' OR post_content LIKE '%Template User Guide%')";

	// Products and pages.
	$posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type IN ('product', 'page', 'post') AND post_status NOT IN ('inherit', 'auto-draft') AND {$like}");
	foreach ($posts as $row) {
		$post = get_post($row->ID);
		$clean = 'product' === $post->post_type
			? wholesale_storefront_media_product_html($row->post_content, $post)
			: wholesale_storefront_media_image_tags(wholesale_storefront_media_rewrite_urls($row->post_content));
		if ($clean !== $row->post_content) {
			$wpdb->update($wpdb->posts, array('post_content' => $clean), array('ID' => $row->ID));
			clean_post_cache($row->ID);
		}
	}

	// Product meta (channel letter component tabs and any other HTML fields).
	$metas = $wpdb->get_results("SELECT pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND (pm.meta_value LIKE '%storefront-files%' OR pm.meta_value LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%' OR pm.meta_value LIKE '%/contact/%')");
	foreach ($metas as $meta) {
		if (is_serialized($meta->meta_value)) {
			continue;
		}
		$clean = wholesale_storefront_media_product_html($meta->meta_value, get_post($meta->post_id), '_product_component' === $meta->meta_key ? 'component' : 'meta');
		if ($clean !== $meta->meta_value) {
			$wpdb->update($wpdb->postmeta, array('meta_value' => $clean), array('meta_id' => $meta->meta_id));
			wp_cache_delete($meta->post_id, 'post_meta');
		}
	}

	// Revisions and autosaves keep their own copy of old content.
	$revisions = $wpdb->get_results("SELECT ID, post_content, post_title FROM {$wpdb->posts} WHERE post_type = 'revision' AND (post_content LIKE '%storefront-files%' OR post_content LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%' OR post_content LIKE '%b2 sign%' OR post_title LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%')");
	foreach ($revisions as $row) {
		$clean = wholesale_storefront_media_image_tags(wholesale_storefront_media_rewrite_urls($row->post_content));
		if ($clean !== $row->post_content) {
			$wpdb->update($wpdb->posts, array('post_content' => $clean), array('ID' => $row->ID));
			clean_post_cache($row->ID);
		}
	}

	// Leftovers: the earlier copy list, and an inactive SEO plugin's link
	// index that still lists the supplier's pages.
	delete_option('wholesale_supplier_files');
	$links_table = $wpdb->prefix . 'rank_math_internal_links';
	if ($links_table === $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $links_table))) {
		$wpdb->query("DELETE FROM {$links_table} WHERE url LIKE '%" . WHOLESALE_SUPPLIER_NAME . "%'");
	}

	return true;
}
