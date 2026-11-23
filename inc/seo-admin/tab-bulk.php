<?php
/**
 * SEO > Per-page SEO: every public URL with its title and description, quick
 * edit, filters, search and pagination.
 *
 * "Now" values come from the last Dashboard scan (what the page really
 * prints); rows the scan hasn't seen are worked out on the spot.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

const WHOLESALE_SEO_BULK_PER_PAGE = 25;

/**
 * Rows for the bulk editor with stored and current values.
 *
 * @return array
 */
function wholesale_seo_bulk_rows()
{
	$targets = wholesale_seo_scan_targets();
	$scan = get_transient('wholesale_seo_scan');
	$results = is_array($scan) && isset($scan['results']) ? $scan['results'] : array();

	$post_ids = array();
	$term_ids = array();
	foreach ($targets as $target) {
		if ('post' === $target['object_type']) {
			$post_ids[] = $target['object_id'];
		} else {
			$term_ids[] = $target['object_id'];
		}
	}
	// One query each for every row's meta.
	update_meta_cache('post', $post_ids);
	update_meta_cache('term', $term_ids);

	$rows = array();
	foreach ($targets as $key => $target) {
		$stored = wholesale_seo_object_values($target['object_type'], $target['object_id']);
		$result = isset($results[$key]) && 200 === (int) $results[$key]['code'] ? $results[$key] : null;
		$theme_noindex = wholesale_seo_theme_noindex_reason($target['object_type'], $target['object_id']);

		$rows[$key] = $target + array(
			'stored' => $stored,
			'scanned' => (bool) $result,
			'now_title' => $result ? $result['title'] : null,
			'now_description' => $result ? $result['description'] : null,
			'robots' => $result ? $result['robots'] : '',
			'theme_noindex' => $theme_noindex,
			'noindex' => '1' === $stored['noindex'] || '' !== $theme_noindex || ($result && false !== stripos($result['robots'], 'noindex')),
		);
	}

	return $rows;
}

/**
 * Fill in "now" values for rows the scan hasn't seen (visible page only).
 */
function wholesale_seo_bulk_fill_now(array &$rows)
{
	foreach ($rows as &$row) {
		if (null !== $row['now_title']) {
			continue;
		}
		$now = wholesale_seo_admin_effective($row['object_type'], $row['object_id']);
		$row['now_title'] = isset($now['title']) ? $now['title'] : '';
		$row['now_description'] = isset($now['description']) ? $now['description'] : '';
		$row['robots'] = isset($now['robots']) ? $now['robots'] : '';
	}
	unset($row);
}

function wholesale_seo_bulk_effective($row, $field)
{
	$stored = $row['stored'][$field];
	if ('' !== $stored) {
		return $stored;
	}
	$now = $row['now_' . $field];
	return null === $now ? '' : $now;
}

function wholesale_seo_bulk_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$filter = isset($_GET['filter']) ? sanitize_key(wp_unslash($_GET['filter'])) : '';
	$kind = isset($_GET['kind']) ? sanitize_key(wp_unslash($_GET['kind'])) : '';
	$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
	$paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;

	$rows = wholesale_seo_bulk_rows();
	$has_scan = (bool) get_transient('wholesale_seo_scan');

	// Duplicates among indexable rows, by effective value.
	$seen = array('title' => array(), 'description' => array());
	foreach ($rows as $key => $row) {
		if ($row['noindex']) {
			continue;
		}
		foreach (array('title', 'description') as $field) {
			$value = mb_strtolower(wholesale_seo_bulk_effective($row, $field));
			if ('' !== $value) {
				$seen[$field][$value][] = $key;
			}
		}
	}
	$duplicates = array();
	foreach ($seen as $field => $values) {
		foreach ($values as $keys) {
			if (count($keys) > 1) {
				foreach ($keys as $key) {
					$duplicates[$key][$field] = count($keys);
				}
			}
		}
	}

	$counts = array('' => count($rows), 'missing' => 0, 'long' => 0, 'duplicate' => 0, 'noindex' => 0, 'custom' => 0);
	$matches = array();
	foreach ($rows as $key => $row) {
		$title = wholesale_seo_bulk_effective($row, 'title');
		$description = wholesale_seo_bulk_effective($row, 'description');
		$known = $row['scanned'] || '' !== $row['stored']['title'];
		$flags = array(
			'missing' => !$row['noindex'] && $known && ('' === $title || '' === $description),
			'long' => mb_strlen($title) > 60 || mb_strlen($description) > 160,
			'duplicate' => isset($duplicates[$key]),
			'noindex' => $row['noindex'],
			'custom' => (bool) array_filter($row['stored']),
		);
		foreach ($flags as $flag => $on) {
			$counts[$flag] += $on ? 1 : 0;
		}

		if ($filter && empty($flags[$filter])) {
			continue;
		}
		if ($kind && $row['kind'] !== $kind) {
			continue;
		}
		if ('' !== $search && false === mb_stripos($row['label'] . ' ' . $row['url'] . ' ' . $title . ' ' . $description, $search)) {
			continue;
		}
		$matches[$key] = $row;
	}

	$total = count($matches);
	$pages = max(1, (int) ceil($total / WHOLESALE_SEO_BULK_PER_PAGE));
	$paged = min($paged, $pages);
	$visible = array_slice($matches, ($paged - 1) * WHOLESALE_SEO_BULK_PER_PAGE, WHOLESALE_SEO_BULK_PER_PAGE, true);
	wholesale_seo_bulk_fill_now($visible);

	$base = admin_url('admin.php?page=wholesale-seo-bulk');
	$filters = array('' => 'All', 'missing' => 'Missing', 'long' => 'Too long', 'duplicate' => 'Duplicate', 'noindex' => 'Noindex', 'custom' => 'Set here');
	$kinds = array('' => 'All types', 'home' => 'Homepage', 'page' => 'Pages', 'post' => 'Posts', 'product' => 'Products', 'product_category' => 'Product categories');

	wholesale_seo_admin_header('wholesale-seo-bulk', 'Every public URL. Quick edit sets the page&rsquo;s own SEO title and description (the same fields as the SEO box on its edit screen); leave a field empty to keep what the page prints now.');
	if (!$has_scan) {
		echo '<div class="notice notice-info inline"><p>Run a scan on the <a href="' . esc_url(admin_url('admin.php?page=wholesale-seo')) . '">Dashboard</a> so the filters can use what every page prints now. Until then, only the rows on screen are checked.</p></div>';
	}
	?>
	<ul class="subsubsub">
		<?php
		$links = array();
		foreach ($filters as $slug => $label) {
			$url = add_query_arg(array_filter(array('filter' => $slug, 'kind' => $kind, 's' => $search)), $base);
			$links[] = '<li><a href="' . esc_url($url) . '"' . ($slug === $filter ? ' class="current" aria-current="page"' : '') . '>' . esc_html($label) . ' <span class="count">(' . (int) $counts[$slug] . ')</span></a>';
		}
		echo implode(' |</li>', $links) . '</li>'; // Escaped above.
		?>
	</ul>

	<form method="get" class="wseo-bulk-search">
		<input type="hidden" name="page" value="wholesale-seo-bulk">
		<input type="hidden" name="filter" value="<?php echo esc_attr($filter); ?>">
		<label class="screen-reader-text" for="wseo-kind">Type</label>
		<select name="kind" id="wseo-kind">
			<?php foreach ($kinds as $slug => $label) : ?>
				<option value="<?php echo esc_attr($slug); ?>" <?php selected($kind, $slug); ?>><?php echo esc_html($label); ?></option>
			<?php endforeach; ?>
		</select>
		<label class="screen-reader-text" for="wseo-search">Search</label>
		<input type="search" id="wseo-search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search pages, URLs, titles">
		<button class="button">Filter</button>
	</form>

	<table class="wp-list-table widefat fixed striped wseo-bulk">
		<thead>
			<tr>
				<th scope="col" class="column-primary">Page</th>
				<th scope="col">SEO title</th>
				<th scope="col">Meta description</th>
				<th scope="col" class="wseo-col-status">Search</th>
			</tr>
		</thead>
		<tbody>
			<?php if (!$visible) : ?>
				<tr><td colspan="4">Nothing matches.</td></tr>
			<?php endif; ?>
			<?php foreach ($visible as $key => $row) : ?>
				<?php
				$title = wholesale_seo_bulk_effective($row, 'title');
				$description = wholesale_seo_bulk_effective($row, 'description');
				$edit = wholesale_seo_edit_link($row['object_type'], $row['object_id']);
				?>
				<tr data-wseo-row="<?php echo esc_attr($key); ?>" data-object-type="<?php echo esc_attr($row['object_type']); ?>" data-object-id="<?php echo (int) $row['object_id']; ?>">
					<td class="column-primary" data-colname="Page">
						<strong><?php echo esc_html($row['label']); ?></strong>
						<span class="wseo-kind"><?php echo esc_html(isset($kinds[$row['kind']]) ? rtrim($kinds[$row['kind']], 's') : $row['kind']); ?></span><br>
						<a class="wseo-url" href="<?php echo esc_url($row['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html(wp_make_link_relative($row['url'])); ?></a>
						<div class="row-actions">
							<span><button type="button" class="button-link wseo-quick-edit">Quick edit</button> | </span>
							<?php if ($edit) : ?><span><a href="<?php echo esc_url($edit); ?>">Edit page</a></span><?php endif; ?>
						</div>
					</td>
					<td data-colname="SEO title">
						<span class="wseo-cell" data-wseo-cell="title"><?php echo esc_html($title); ?></span>
						<?php echo wholesale_seo_length_badge($title, 60); // Escaped in the helper. ?>
						<?php echo '' !== $row['stored']['title'] ? '<span class="wseo-tag">set here</span>' : ''; ?>
						<?php echo isset($duplicates[$key]['title']) ? '<span class="wseo-tag is-warn">duplicate</span>' : ''; ?>
					</td>
					<td data-colname="Meta description">
						<span class="wseo-cell" data-wseo-cell="description"><?php echo esc_html($description); ?></span>
						<?php echo wholesale_seo_length_badge($description, 160); // Escaped in the helper. ?>
						<?php echo '' !== $row['stored']['description'] ? '<span class="wseo-tag">set here</span>' : ''; ?>
						<?php echo isset($duplicates[$key]['description']) ? '<span class="wseo-tag is-warn">duplicate</span>' : ''; ?>
					</td>
					<td data-colname="Search" class="wseo-col-status">
						<?php if ($row['theme_noindex']) : ?>
							<span class="wseo-tag is-muted" title="<?php echo esc_attr($row['theme_noindex']); ?>">noindex by theme</span>
							<span class="description"><?php echo esc_html($row['theme_noindex']); ?></span>
						<?php elseif ('1' === $row['stored']['noindex']) : ?>
							<span class="wseo-tag is-warn">noindex (set here)</span>
						<?php elseif ($row['noindex']) : ?>
							<span class="wseo-tag is-muted">noindex</span> <code><?php echo esc_html($row['robots']); ?></code>
						<?php else : ?>
							<span class="wseo-tag is-ok">index</span>
						<?php endif; ?>
						<?php if ('' !== $row['stored']['canonical']) : ?>
							<br><span class="description">Canonical → <?php echo esc_html($row['stored']['canonical']); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr class="wseo-quick-row" hidden>
					<td colspan="4">
						<div class="wseo-quick">
							<label>SEO title <span class="wseo-counter" data-limit="60"></span>
								<input type="text" class="widefat" data-field="title" value="<?php echo esc_attr($row['stored']['title']); ?>" placeholder="<?php echo esc_attr((string) $row['now_title']); ?>">
							</label>
							<label>Meta description <span class="wseo-counter" data-limit="160"></span>
								<textarea class="widefat" rows="2" data-field="description" placeholder="<?php echo esc_attr((string) $row['now_description']); ?>"><?php echo esc_textarea($row['stored']['description']); ?></textarea>
							</label>
							<p>
								<button type="button" class="button button-primary wseo-quick-save">Save</button>
								<button type="button" class="button wseo-quick-cancel">Cancel</button>
								<span class="wseo-quick-status" aria-live="polite"></span>
							</p>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<span class="displaying-num"><?php echo (int) $total; ?> items</span>
			<?php
			echo paginate_links(array(
				'base' => add_query_arg('paged', '%#%', add_query_arg(array_filter(array('filter' => $filter, 'kind' => $kind, 's' => $search)), $base)),
				'format' => '',
				'current' => $paged,
				'total' => $pages,
			)); // paginate_links() escapes its output.
			?>
		</div>
	</div>
	<?php
	wholesale_seo_admin_footer();
}

add_action('wp_ajax_wholesale_seo_bulk_save', function () {
	if (!current_user_can('manage_options')) {
		wp_send_json_error('You are not allowed to change SEO settings.', 403);
	}
	check_ajax_referer('wholesale_seo_bulk', 'nonce');

	$object_type = isset($_POST['object_type']) && 'term' === $_POST['object_type'] ? 'term' : 'post';
	$object_id = isset($_POST['object_id']) ? (int) $_POST['object_id'] : 0;
	$exists = 'term' === $object_type ? get_term($object_id, 'product_category') : get_post($object_id);
	if (!$exists || is_wp_error($exists) || ('post' === $object_type && !current_user_can('edit_post', $object_id))) {
		wp_send_json_error('That page no longer exists.');
	}

	wholesale_seo_save_object_values($object_type, $object_id, array(
		'title' => isset($_POST['title']) ? wp_unslash($_POST['title']) : '',
		'description' => isset($_POST['description']) ? wp_unslash($_POST['description']) : '',
	));

	$now = wholesale_seo_admin_effective($object_type, $object_id);
	wp_send_json_success(array(
		'title' => isset($now['title']) ? $now['title'] : '',
		'description' => isset($now['description']) ? $now['description'] : '',
		'storedTitle' => wholesale_seo_stored_value($object_type, $object_id, 'title'),
		'storedDescription' => wholesale_seo_stored_value($object_type, $object_id, 'description'),
	));
});
