<?php
/**
 * SEO > Dashboard: site health checks from an on-demand scan.
 *
 * The scan is started by hand from this screen and runs in small batches
 * (10 URLs, 10 s timeout each) from the admin's browser through admin-ajax.
 * Each URL is fetched over HTTP like a visitor would see it, with a signed
 * X-Wholesale-SEO-Scan header and a bot user agent, so the 404 log, redirect
 * hit counters and Visitor Insights ignore it (Visitor Insights also only
 * counts browsers that run its script). Results are kept in a transient;
 * nothing here ever runs on visitor traffic.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

const WHOLESALE_SEO_SCAN_BATCH = 10;

/**
 * Every public URL the site owns: published pages, posts and products, and
 * product category routes. Keyed "p{ID}" / "t{term ID}".
 *
 * @return array key => array(url, label, object_type, object_id, kind).
 */
function wholesale_seo_scan_targets()
{
	$targets = array();
	$front_id = (int) get_option('page_on_front');

	$posts = get_posts(array(
		'post_type' => array('page', 'post', 'product'),
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => array('type' => 'ASC', 'title' => 'ASC'),
		'no_found_rows' => true,
	));

	foreach ($posts as $post) {
		$url = get_permalink($post);
		if (!$url) {
			continue;
		}
		$is_front = (int) $post->ID === $front_id;
		$targets['p' . $post->ID] = array(
			'url' => $url,
			'label' => ($is_front ? 'Homepage: ' : '') . wholesale_schema_text(get_the_title($post)),
			'object_type' => 'post',
			'object_id' => (int) $post->ID,
			'kind' => $is_front ? 'home' : $post->post_type,
		);
	}

	$terms = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => true));
	foreach (is_wp_error($terms) ? array() : $terms as $term) {
		$targets['t' . $term->term_id] = array(
			'url' => wholesale_category_url($term->slug),
			'label' => wholesale_schema_text($term->name),
			'object_type' => 'term',
			'object_id' => (int) $term->term_id,
			'kind' => 'product_category',
		);
	}

	if ($front_id && isset($targets['p' . $front_id])) {
		$targets = array('p' . $front_id => $targets['p' . $front_id]) + $targets;
	}

	return $targets;
}

/**
 * A URL reduced for comparison: host and path with a trailing slash, no
 * scheme, query or fragment.
 */
function wholesale_seo_compare_url($url)
{
	$parts = wp_parse_url(html_entity_decode(trim((string) $url), ENT_QUOTES, 'UTF-8'));
	if (!$parts || empty($parts['host'])) {
		return '';
	}

	return strtolower(preg_replace('/^www\./', '', $parts['host'])) . trailingslashit(isset($parts['path']) ? $parts['path'] : '/');
}

/**
 * Fetch one URL as the scanner.
 *
 * @return array code, location, body.
 */
function wholesale_seo_scan_fetch($url)
{
	$response = wp_remote_get($url, array(
		'timeout' => 10,
		'redirection' => 0,
		'user-agent' => 'WholesaleSEOScan/1.0 (site health bot; ' . home_url('/') . ')',
		'headers' => array('X-Wholesale-SEO-Scan' => wholesale_seo_scan_signature()),
		'sslverify' => apply_filters('https_local_ssl_verify', false),
		'limit_response_size' => 3 * MB_IN_BYTES,
	));

	if (is_wp_error($response)) {
		return array('code' => 0, 'location' => '', 'body' => '', 'error' => $response->get_error_message());
	}

	return array(
		'code' => (int) wp_remote_retrieve_response_code($response),
		'location' => (string) wp_remote_retrieve_header($response, 'location'),
		'body' => (string) wp_remote_retrieve_body($response),
		'error' => '',
	);
}

/**
 * Attributes of one HTML tag.
 *
 * @return array
 */
function wholesale_seo_tag_attributes($tag)
{
	$attributes = array();
	if (preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/', $tag, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$value = isset($match[4]) && '' !== $match[4] ? $match[4] : (isset($match[3]) && '' !== $match[3] ? $match[3] : $match[2]);
			$attributes[strtolower($match[1])] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
		}
	}
	return $attributes;
}

/**
 * The SEO facts of a page's HTML.
 *
 * @return array
 */
function wholesale_seo_parse_page($html)
{
	$result = array('title' => '', 'titles' => 0, 'description' => '', 'descriptions' => 0, 'robots' => '', 'canonical' => '', 'canonicals' => 0, 'h1' => 0, 'images' => 0, 'no_alt' => array(), 'empty_alt' => array());

	$head = preg_match('#<head\b.*?</head>#si', $html, $match) ? $match[0] : $html;
	if (preg_match_all('#<title\b[^>]*>(.*?)</title>#si', $head, $titles)) {
		$result['titles'] = count($titles[1]);
		$result['title'] = trim(html_entity_decode(wp_strip_all_tags($titles[1][0]), ENT_QUOTES, 'UTF-8'));
	}

	preg_match_all('#<(meta|link)\b[^>]*>#i', $head, $tags);
	foreach ($tags[0] as $tag) {
		$attributes = wholesale_seo_tag_attributes($tag);
		$name = isset($attributes['name']) ? strtolower($attributes['name']) : '';
		$rel = isset($attributes['rel']) ? strtolower($attributes['rel']) : '';
		if ('description' === $name) {
			$result['descriptions']++;
			$result['description'] = trim(isset($attributes['content']) ? $attributes['content'] : '');
		} elseif ('robots' === $name) {
			$result['robots'] = trim(isset($attributes['content']) ? $attributes['content'] : '');
		} elseif ('canonical' === $rel) {
			$result['canonicals']++;
			$result['canonical'] = isset($attributes['href']) ? $attributes['href'] : '';
		}
	}

	$body = preg_replace('#<(script|style|noscript|template)\b.*?</\1>#si', '', $html);
	$result['h1'] = preg_match_all('#<h1[\s>]#i', $body);

	preg_match_all('#<img\b[^>]*>#i', $body, $images);
	foreach ($images[0] as $tag) {
		$attributes = wholesale_seo_tag_attributes($tag);
		$src = isset($attributes['src']) ? $attributes['src'] : (isset($attributes['data-src']) ? $attributes['data-src'] : '');
		if ('' === $src || false !== strpos($src, 'facebook.com/tr') || (isset($attributes['width']) && '1' === $attributes['width'])) {
			continue;
		}
		$result['images']++;
		// alt="" is right for decorative images; only a missing attribute is an error.
		if (!isset($attributes['alt'])) {
			$result['no_alt'][] = $src;
		} elseif ('' === trim($attributes['alt'])) {
			$result['empty_alt'][] = $src;
		}
	}
	$result['no_alt'] = array_values(array_unique($result['no_alt']));
	$result['empty_alt'] = array_values(array_unique($result['empty_alt']));

	return $result;
}

/**
 * Lines in the theme that still print an old brand name.
 *
 * @return array List of array(file, line, text).
 */
function wholesale_seo_brand_fallbacks()
{
	$found = array();
	$root = get_template_directory();
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

	foreach ($iterator as $file) {
		$path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
		if ('php' !== $file->getExtension() || preg_match('#^(cmb2|stripe-php|utils|inc/seo-admin)/#', $path)) {
			continue;
		}
		foreach ((array) file($file->getPathname()) as $number => $line) {
			if (false !== strpos($line, 'Lit Sign Manufacturing') || false !== strpos($line, 'Store Front Sign Online')) {
				$found[] = array($path, $number + 1, trim(mb_substr(trim($line), 0, 160)));
			}
		}
	}

	return $found;
}

add_action('wp_ajax_wholesale_seo_scan', function () {
	if (!current_user_can('manage_options')) {
		wp_send_json_error('You are not allowed to run the SEO scan.', 403);
	}
	check_ajax_referer('wholesale_seo_scan', 'nonce');

	$step = isset($_POST['step']) ? sanitize_key(wp_unslash($_POST['step'])) : '';

	if ('start' === $step) {
		$targets = wholesale_seo_scan_targets();
		$sitemap = array();
		foreach (array('/sitemap.xml', '/product-sitemap.xml', '/category-sitemap.xml') as $path) {
			$response = wholesale_seo_scan_fetch(home_url($path));
			if (preg_match_all('#<loc>(.*?)</loc>#s', $response['body'], $locs)) {
				foreach ($locs[1] as $loc) {
					$sitemap[wholesale_seo_compare_url($loc)] = true;
				}
			}
		}

		set_transient('wholesale_seo_scan_run', array(
			'targets' => $targets,
			'sitemap' => $sitemap,
			'results' => array(),
			'brand' => wholesale_seo_brand_fallbacks(),
			'started' => time(),
		), HOUR_IN_SECONDS);

		wp_send_json_success(array('total' => count($targets), 'batch' => WHOLESALE_SEO_SCAN_BATCH));
	}

	$run = get_transient('wholesale_seo_scan_run');
	if (!is_array($run)) {
		wp_send_json_error('The scan expired. Start it again.');
	}

	if ('batch' === $step) {
		@set_time_limit(WHOLESALE_SEO_SCAN_BATCH * 15);
		$offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
		foreach (array_slice($run['targets'], $offset, WHOLESALE_SEO_SCAN_BATCH, true) as $key => $target) {
			$response = wholesale_seo_scan_fetch($target['url']);
			$page = 200 === $response['code'] ? wholesale_seo_parse_page($response['body']) : array();
			$run['results'][$key] = array_merge($page, array(
				'code' => $response['code'],
				'location' => $response['location'],
				'error' => $response['error'],
			));
		}
		set_transient('wholesale_seo_scan_run', $run, HOUR_IN_SECONDS);
		wp_send_json_success(array('done' => min(count($run['targets']), $offset + WHOLESALE_SEO_SCAN_BATCH)));
	}

	if ('finish' === $step) {
		$run['finished'] = time();
		// With an expiry the transient is not autoloaded on every request.
		set_transient('wholesale_seo_scan', $run, 30 * DAY_IN_SECONDS);
		delete_transient('wholesale_seo_scan_run');
		wp_send_json_success();
	}

	wp_send_json_error('Unknown step.');
});

/**
 * The checks the Dashboard shows, from the last scan.
 *
 * @return array key => array(label, help, items) with items as
 *               array(target key, note).
 */
function wholesale_seo_scan_report($scan)
{
	$checks = array(
		'missing_title' => array('Missing title', 'Indexable pages with no &lt;title&gt;.', array()),
		'duplicate_title' => array('Duplicate titles', 'Indexable pages sharing a title with another page.', array()),
		'long_title' => array('Titles over 60 characters', 'Google cuts them off around 60 characters.', array()),
		'missing_description' => array('Missing meta description', 'Indexable pages with no description.', array()),
		'duplicate_description' => array('Duplicate meta descriptions', 'Indexable pages sharing a description.', array()),
		'long_description' => array('Descriptions over 160 characters', 'Google cuts them off around 160 characters.', array()),
		'noindex' => array('Noindex pages', 'Kept out of search results, and why.', array()),
		'not_in_sitemap' => array('Indexable pages missing from the sitemap', 'Not in /sitemap.xml, /product-sitemap.xml or /category-sitemap.xml.', array()),
		'h1' => array('No H1, or more than one', 'Each page should have exactly one main heading.', array()),
		'no_alt' => array('Images without an alt attribute', 'Screen readers and Google get no description for these.', array()),
		'empty_alt' => array('Images with empty alt=""', 'Correct for decorative images (backgrounds, icons next to text); check that none of these is a product photo.', array()),
		'canonical_elsewhere' => array('Canonical points to another URL', 'Expected for duplicate products and ad landing pages.', array()),
		'not_200' => array('URLs that did not answer 200', 'Redirects, errors or timeouts.', array()),
		'duplicate_tags' => array('Duplicate head tags', 'More than one title, description or canonical tag.', array()),
	);

	$titles = array();
	$descriptions = array();

	foreach ($scan['targets'] as $key => $target) {
		$result = isset($scan['results'][$key]) ? $scan['results'][$key] : null;
		if (!$result) {
			continue;
		}

		if (200 !== (int) $result['code']) {
			$note = $result['code'] ? 'HTTP ' . $result['code'] . ($result['location'] ? ' → ' . $result['location'] : '') : 'No answer: ' . $result['error'];
			$checks['not_200'][2][] = array($key, $note);
			continue;
		}

		if ($result['titles'] > 1 || $result['descriptions'] > 1 || $result['canonicals'] > 1) {
			$checks['duplicate_tags'][2][] = array($key, sprintf('%d titles, %d descriptions, %d canonicals', $result['titles'], $result['descriptions'], $result['canonicals']));
		}

		$noindex = false !== stripos($result['robots'], 'noindex');
		if ($noindex) {
			$reason = wholesale_seo_stored_value($target['object_type'], $target['object_id'], 'noindex') ? 'Set on the page (SEO box)' : wholesale_seo_theme_noindex_reason($target['object_type'], $target['object_id']);
			$checks['noindex'][2][] = array($key, $reason ? $reason : 'robots: ' . $result['robots']);
			continue;
		}

		$canonical = wholesale_seo_compare_url($result['canonical']);
		if ($canonical && $canonical !== wholesale_seo_compare_url($target['url'])) {
			$checks['canonical_elsewhere'][2][] = array($key, '→ ' . $result['canonical']);
			continue;
		}

		// Indexable from here on.
		if ('' === $result['title']) {
			$checks['missing_title'][2][] = array($key, '');
		} else {
			$titles[mb_strtolower($result['title'])][] = $key;
			if (mb_strlen($result['title']) > 60) {
				$checks['long_title'][2][] = array($key, mb_strlen($result['title']) . ' characters: ' . $result['title']);
			}
		}

		if ('' === $result['description']) {
			$checks['missing_description'][2][] = array($key, '');
		} else {
			$descriptions[mb_strtolower($result['description'])][] = $key;
			if (mb_strlen($result['description']) > 160) {
				$checks['long_description'][2][] = array($key, mb_strlen($result['description']) . ' characters');
			}
		}

		if (empty($scan['sitemap'][wholesale_seo_compare_url($target['url'])])) {
			$checks['not_in_sitemap'][2][] = array($key, '');
		}

		if (1 !== (int) $result['h1']) {
			$checks['h1'][2][] = array($key, (int) $result['h1'] . ' H1 headings');
		}

		if (!empty($result['empty_alt'])) {
			$checks['empty_alt'][2][] = array($key, count($result['empty_alt']) . ' images: ' . implode(', ', array_map('wp_basename', array_slice($result['empty_alt'], 0, 3))));
		}

		if ($result['no_alt']) {
			$checks['no_alt'][2][] = array($key, count($result['no_alt']) . ' of ' . (int) $result['images'] . ' images: ' . implode(', ', array_map('wp_basename', array_slice($result['no_alt'], 0, 3))));
		}
	}

	foreach ($titles as $title => $keys) {
		if (count($keys) > 1) {
			foreach ($keys as $key) {
				$checks['duplicate_title'][2][] = array($key, 'Shared by ' . count($keys) . ' pages: ' . $scan['results'][$key]['title']);
			}
		}
	}
	foreach ($descriptions as $description => $keys) {
		if (count($keys) > 1) {
			foreach ($keys as $key) {
				$checks['duplicate_description'][2][] = array($key, 'Shared by ' . count($keys) . ' pages');
			}
		}
	}

	return $checks;
}

/**
 * Images in the media library with no alt text.
 *
 * @return array count, items (ID, title).
 */
function wholesale_seo_media_without_alt($limit = 100)
{
	global $wpdb;

	$where = "FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_attachment_image_alt'
		WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%' AND (m.meta_value IS NULL OR TRIM(m.meta_value) = '')";

	return array(
		'count' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) {$where}"),
		'items' => $wpdb->get_results($wpdb->prepare("SELECT DISTINCT p.ID, p.post_title {$where} ORDER BY p.ID DESC LIMIT %d", $limit)),
	);
}

/**
 * One list item of a check: page name with edit and view links, and a note.
 */
function wholesale_seo_report_item($target, $note)
{
	$edit = wholesale_seo_edit_link($target['object_type'], $target['object_id']);
	echo '<li><strong>' . esc_html($target['label']) . '</strong> ';
	if ($edit) {
		echo '<a href="' . esc_url($edit) . '">Edit</a> · ';
	}
	echo '<a href="' . esc_url($target['url']) . '" target="_blank" rel="noopener">View</a>';
	if ('' !== $note) {
		echo '<br><span class="description">' . esc_html($note) . '</span>';
	}
	echo '</li>';
}

function wholesale_seo_dashboard_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$scan = get_transient('wholesale_seo_scan');
	wholesale_seo_admin_header('wholesale-seo', 'Health checks from a scan of every page, post, product and product category, fetched the way search engines see them. The scan only runs when you start it.');
	?>
	<div class="wseo-card wseo-scan-panel">
		<p>
			<button type="button" class="button button-primary" id="wseo-scan-start"><?php echo $scan ? 'Run the scan again' : 'Run the first scan'; ?></button>
			<?php if ($scan) : ?>
				<span class="description">Last scan: <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $scan['finished'])); ?>, <?php echo (int) count($scan['targets']); ?> URLs.
				<?php if ((int) get_option('wholesale_seo_changed_at') > (int) $scan['finished']) : ?>
					<strong>SEO settings changed since then.</strong>
				<?php endif; ?>
				</span>
			<?php endif; ?>
		</p>
		<div class="wseo-progress" hidden><div class="wseo-progress-bar"></div></div>
		<p class="wseo-scan-status description" aria-live="polite"></p>
	</div>

	<?php if ($scan) : ?>
		<?php $checks = wholesale_seo_scan_report($scan); ?>
		<div class="wseo-checks-grid">
			<?php foreach ($checks as $key => $check) : ?>
				<?php $count = count($check[2]); ?>
				<details class="wseo-card wseo-check <?php echo $count ? (in_array($key, array('noindex', 'canonical_elsewhere', 'empty_alt'), true) ? 'is-info' : 'is-bad') : 'is-good'; ?>">
					<summary><span class="wseo-count"><?php echo (int) $count; ?></span> <?php echo esc_html($check[0]); ?></summary>
					<p class="description"><?php echo wp_kses_post($check[1]); ?></p>
					<?php if ($count) : ?>
						<ul class="wseo-list">
							<?php foreach ($check[2] as $item) : ?>
								<?php wholesale_seo_report_item($scan['targets'][$item[0]], $item[1]); ?>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="wseo-checks-grid">
		<?php $media = wholesale_seo_media_without_alt(); ?>
		<details class="wseo-card wseo-check <?php echo $media['count'] ? 'is-bad' : 'is-good'; ?>">
			<summary><span class="wseo-count"><?php echo (int) $media['count']; ?></span> Media library images without alt text</summary>
			<p class="description">Live count. The theme adds a fallback alt to product photos it prints, so the page check above is the one visitors and Google see.</p>
			<?php if ($media['items']) : ?>
				<ul class="wseo-list">
					<?php foreach ($media['items'] as $item) : ?>
						<li><a href="<?php echo esc_url(get_edit_post_link($item->ID, 'raw')); ?>"><?php echo esc_html($item->post_title ? $item->post_title : '#' . $item->ID); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php if ($media['count'] > count($media['items'])) : ?>
					<p class="description">Showing the newest <?php echo (int) count($media['items']); ?>.</p>
				<?php endif; ?>
			<?php endif; ?>
		</details>

		<details class="wseo-card wseo-check is-info">
			<?php $not_found = function_exists('wholesale_seo_404_recent') ? wholesale_seo_404_recent(7) : null; ?>
			<summary><span class="wseo-count"><?php echo null === $not_found ? '–' : (int) count($not_found); ?></span> 404s in the last 7 days</summary>
			<?php if (null === $not_found) : ?>
				<p class="description">The 404 log arrives with the Redirects &amp; 404s screen.</p>
			<?php else : ?>
				<ul class="wseo-list">
					<?php foreach ($not_found as $row) : ?>
						<li><code><?php echo esc_html($row['path']); ?></code> <span class="description">× <?php echo (int) $row['hits']; ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</details>

		<?php if ($scan && !empty($scan['brand'])) : ?>
			<details class="wseo-card wseo-check is-info">
				<summary><span class="wseo-count"><?php echo (int) count($scan['brand']); ?></span> Old brand names in theme code</summary>
				<p class="description">Lines that print "Lit Sign Manufacturing" or "Store Front Sign Online". Left as they are for now; the brand cleanup is a separate change. The site title under Settings &gt; General is "<?php echo esc_html(get_bloginfo('name')); ?>".</p>
				<ul class="wseo-list wseo-code-list">
					<?php foreach ($scan['brand'] as $line) : ?>
						<li><code><?php echo esc_html($line[0] . ':' . $line[1]); ?></code><br><span class="description"><?php echo esc_html($line[2]); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>
	</div>
	<?php
	wholesale_seo_admin_footer();
}
