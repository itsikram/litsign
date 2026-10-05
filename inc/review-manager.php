<?php
/**
 * wp-admin "Reviews by Product": review_submission posts grouped by product slug,
 * with inline editing, moderation and JSON import. Product cards read their rating
 * live from the approved reviews.
 *
 * Reviews marked "is_sample" in an import file are placeholder data. They are stored
 * as drafts, can never be published, and are only visible to editors on a product
 * page opened with ?review_preview=1 (for layout testing).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_REVIEW_MANAGER_PAGE = 'wholesale-reviews-by-product';
const WHOLESALE_REVIEW_MANAGER_CAP = 'edit_others_posts';

function wholesale_review_manager_url($args = array())
{
	return add_query_arg($args, admin_url('edit.php?post_type=review_submission&page=' . WHOLESALE_REVIEW_MANAGER_PAGE));
}

function wholesale_review_is_sample($review_id)
{
	return (bool) get_post_meta($review_id, '_review_is_sample', true);
}

function wholesale_product_by_slug($slug)
{
	$slug = sanitize_title($slug);
	$product = $slug ? get_page_by_path($slug, OBJECT, 'product') : null;
	return ($product && 'trash' !== $product->post_status) ? $product : null;
}

/**
 * Review counts and the average of approved, non-sample reviews, keyed by product ID.
 */
function wholesale_review_stats_by_product()
{
	global $wholesale_review_stats_cache;
	if (is_array($wholesale_review_stats_cache)) {
		return $wholesale_review_stats_cache;
	}

	$reviews = get_posts(array(
		'post_type' => 'review_submission',
		'post_status' => array('publish', 'pending', 'draft'),
		'posts_per_page' => -1,
	));

	$stats = array();
	foreach ($reviews as $review) {
		$product_id = absint(get_post_meta($review->ID, '_review_product_id', true));
		if (!isset($stats[$product_id])) {
			$stats[$product_id] = array('published' => 0, 'pending' => 0, 'samples' => 0, 'sum' => 0, 'average' => 0.0);
		}
		if (wholesale_review_is_sample($review->ID)) {
			$stats[$product_id]['samples']++;
		} elseif ('publish' === $review->post_status) {
			$stats[$product_id]['published']++;
			$stats[$product_id]['sum'] += min(5, max(1, absint(get_post_meta($review->ID, '_review_rating', true))));
		} else {
			$stats[$product_id]['pending']++;
		}
	}

	foreach ($stats as $product_id => $row) {
		$stats[$product_id]['average'] = $row['published'] ? round($row['sum'] / $row['published'], 1) : 0.0;
	}

	return $wholesale_review_stats_cache = $stats;
}

function wholesale_product_review_stats($product_id)
{
	$stats = wholesale_review_stats_by_product();
	return isset($stats[$product_id]) ? $stats[$product_id] : array('published' => 0, 'pending' => 0, 'samples' => 0, 'sum' => 0, 'average' => 0.0);
}

// Any review change clears the cached stats, so product cards update right away.
function wholesale_review_stats_flush()
{
	global $wholesale_review_stats_cache;
	$wholesale_review_stats_cache = null;
}

add_action('transition_post_status', function ($new_status, $old_status, $post) {
	if ('review_submission' === $post->post_type) {
		wholesale_review_stats_flush();
	}
}, 10, 3);

add_action('updated_post_meta', function ($meta_id, $object_id, $meta_key) {
	if (in_array($meta_key, array('_review_rating', '_review_product_id', '_review_is_sample'), true)) {
		wholesale_review_stats_flush();
	}
}, 10, 3);

// Placeholder reviews can never go live, whichever screen tries to publish them.
add_filter('wp_insert_post_data', function ($data, $postarr) {
	if ('review_submission' === $data['post_type'] && 'publish' === $data['post_status'] && !empty($postarr['ID']) && wholesale_review_is_sample($postarr['ID'])) {
		$data['post_status'] = 'draft';
	}
	return $data;
}, 10, 2);

add_filter('post_row_actions', function ($actions, $post) {
	if ('review_submission' === $post->post_type && wholesale_review_is_sample($post->ID)) {
		unset($actions['wholesale_review']);
	}
	return $actions;
}, 20, 2);

function wholesale_review_preview_mode()
{
	return isset($_GET['review_preview']) && current_user_can(WHOLESALE_REVIEW_MANAGER_CAP);
}

/**
 * Approved reviews for a product page. In preview mode, sample reviews are included too.
 */
function wholesale_product_reviews($product_id, $limit = 6)
{
	$preview = wholesale_review_preview_mode();
	$reviews = get_posts(array(
		'post_type' => 'review_submission',
		'post_status' => $preview ? array('publish', 'draft') : 'publish',
		'posts_per_page' => $limit,
		'meta_key' => '_review_product_id',
		'meta_value' => absint($product_id),
		'orderby' => 'date',
		'order' => 'DESC',
	));

	if ($preview) {
		$reviews = array_values(array_filter($reviews, function ($review) {
			return 'publish' === $review->post_status || wholesale_review_is_sample($review->ID);
		}));
	}

	return $reviews;
}

// ------------------------------------------------------------------
// Admin page
// ------------------------------------------------------------------

add_action('admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=review_submission',
		'Reviews by Product',
		'Reviews by Product',
		WHOLESALE_REVIEW_MANAGER_CAP,
		WHOLESALE_REVIEW_MANAGER_PAGE,
		'wholesale_review_manager_page'
	);
});

function wholesale_review_manager_flash($type, $message)
{
	set_transient('wholesale_review_flash_' . get_current_user_id(), array('type' => $type, 'message' => $message), MINUTE_IN_SECONDS);
}

function wholesale_review_manager_notice()
{
	$key = 'wholesale_review_flash_' . get_current_user_id();
	$flash = get_transient($key);
	if (!is_array($flash)) {
		return;
	}
	delete_transient($key);
	printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($flash['type']), wp_kses($flash['message'], array('code' => array(), 'strong' => array(), 'br' => array())));
}

/**
 * Reads the shared review fields from a manager form. Returns WP_Error on bad input.
 */
function wholesale_review_manager_fields($source)
{
	$slug = sanitize_title(wp_unslash($source['product_slug'] ?? ''));
	$product = wholesale_product_by_slug($slug);
	if (!$product) {
		return new WP_Error('product', sprintf('No product has the slug <code>%s</code>.', esc_html($slug)));
	}

	$rating = absint($source['rating'] ?? 0);
	$text = sanitize_textarea_field(wp_unslash($source['text'] ?? ''));
	$name = sanitize_text_field(wp_unslash($source['name'] ?? ''));
	if ($rating < 1 || $rating > 5 || '' === trim($text) || '' === $name) {
		return new WP_Error('fields', 'A review needs a name, a 1–5 rating and review text.');
	}

	$date = DateTime::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($source['date'] ?? '')));

	return array(
		'product_id' => $product->ID,
		'rating' => $rating,
		'text' => $text,
		'name' => $name,
		'title' => sanitize_text_field(wp_unslash($source['title'] ?? '')),
		'company' => sanitize_text_field(wp_unslash($source['company'] ?? '')),
		'location' => sanitize_text_field(wp_unslash($source['location'] ?? '')),
		'date' => $date ? $date->format('Y-m-d 12:00:00') : '',
	);
}

function wholesale_review_manager_post_args($fields)
{
	// wp_insert_post() unslashes its input.
	$args = array(
		'post_title' => wp_slash(sprintf('%d-star review from %s', $fields['rating'], $fields['name'])),
		'post_content' => wp_slash($fields['text']),
	);
	if ($fields['date']) {
		$args['post_date'] = $fields['date'];
		$args['post_date_gmt'] = get_gmt_from_date($fields['date']);
	}
	return $args;
}

function wholesale_review_manager_save_meta($review_id, $fields)
{
	update_post_meta($review_id, '_review_product_id', $fields['product_id']);
	update_post_meta($review_id, '_review_rating', $fields['rating']);
	update_post_meta($review_id, '_review_name', wp_slash($fields['name']));
	update_post_meta($review_id, '_review_title', wp_slash($fields['title']));
	update_post_meta($review_id, '_review_company', wp_slash($fields['company']));
	update_post_meta($review_id, '_review_location', wp_slash($fields['location']));
}

add_action('admin_post_wholesale_review_manager', 'wholesale_review_manager_handle');
function wholesale_review_manager_handle()
{
	if (!current_user_can(WHOLESALE_REVIEW_MANAGER_CAP)) {
		wp_die('Not allowed.');
	}
	check_admin_referer('wholesale_review_manager');

	$task = sanitize_key(wp_unslash($_POST['task'] ?? ''));
	$product_slug = sanitize_title(wp_unslash($_POST['product'] ?? ''));
	$redirect_args = array('product' => $product_slug, 'view' => sanitize_key(wp_unslash($_POST['view'] ?? '')));

	switch ($task) {
		case 'save':
		case 'approve':
		case 'unpublish':
			$review_id = absint($_POST['review_id'] ?? 0);
			if ('review_submission' !== get_post_type($review_id)) {
				wholesale_review_manager_flash('error', 'That review no longer exists.');
				break;
			}
			$fields = wholesale_review_manager_fields($_POST);
			if (is_wp_error($fields)) {
				wholesale_review_manager_flash('error', $fields->get_error_message());
				break;
			}
			$old_product_id = absint(get_post_meta($review_id, '_review_product_id', true));
			$args = wholesale_review_manager_post_args($fields) + array('ID' => $review_id, 'edit_date' => true);
			if ('approve' === $task) {
				if (wholesale_review_is_sample($review_id)) {
					wholesale_review_manager_flash('error', 'Sample reviews are placeholder text and can’t be published.');
					break;
				}
				$args['post_status'] = 'publish';
			} elseif ('unpublish' === $task) {
				$args['post_status'] = 'pending';
			}
			wholesale_review_manager_save_meta($review_id, $fields);
			wp_update_post($args);
			wholesale_review_manager_flash('success', 'approve' === $task ? 'Review approved and shown on the site.' : ('unpublish' === $task ? 'Review hidden from the site.' : 'Review saved.'));
			break;

		case 'trash':
			$review_id = absint($_POST['review_id'] ?? 0);
			if ('review_submission' === get_post_type($review_id) && current_user_can('delete_post', $review_id)) {
				wp_trash_post($review_id);
				wholesale_review_manager_flash('success', 'Review moved to the trash.');
			}
			break;

		case 'add':
			$fields = wholesale_review_manager_fields($_POST);
			if (is_wp_error($fields)) {
				wholesale_review_manager_flash('error', $fields->get_error_message());
				break;
			}
			$sources = wholesale_review_manager_sources();
			$source = sanitize_key(wp_unslash($_POST['source'] ?? ''));
			$review_id = wp_insert_post(wholesale_review_manager_post_args($fields) + array(
				'post_type' => 'review_submission',
				'post_status' => 'publish' === ($_POST['status'] ?? '') ? 'publish' : 'pending',
				'meta_input' => array(
					'_review_email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
					'_review_source' => isset($sources[$source]) ? $source : 'other',
					'_review_source_note' => sanitize_text_field(wp_unslash($_POST['source_note'] ?? '')),
				),
			), true);
			if (is_wp_error($review_id)) {
				wholesale_review_manager_flash('error', $review_id->get_error_message());
				break;
			}
			wholesale_review_manager_save_meta($review_id, $fields);
			$redirect_args['product'] = get_post_field('post_name', $fields['product_id']);
			wholesale_review_manager_flash('success', 'Review added.');
			break;

		case 'import':
			wholesale_review_manager_import();
			break;

		case 'bulk_approve':
			$product = $product_slug ? wholesale_product_by_slug($product_slug) : null;
			$ratings = array_values(array_intersect(array(1, 2, 3, 4, 5), array_map('absint', (array) wp_unslash($_POST['ratings'] ?? array()))));
			if (!$ratings) {
				wholesale_review_manager_flash('error', 'Tick at least one star rating to approve.');
				break;
			}
			$approved = wholesale_review_bulk_approve($ratings, $product ? $product->ID : 0);
			wholesale_review_manager_flash('success', sprintf('Approved %d reviews (%s).', $approved, implode(', ', array_map(function ($r) {
				return $r . '★';
			}, $ratings))));
			break;

		case 'featured_text':
			$product = wholesale_product_by_slug($product_slug);
			if ($product) {
				update_post_meta($product->ID, '_product_review_text', sanitize_textarea_field(wp_unslash($_POST['featured_text'] ?? '')));
				wholesale_review_manager_flash('success', 'Featured review text saved.');
			}
			break;

		case 'delete_samples':
			$samples = get_posts(array(
				'post_type' => 'review_submission',
				'post_status' => 'any',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'meta_key' => '_review_is_sample',
				'meta_value' => '1',
			));
			foreach ($samples as $sample_id) {
				wp_delete_post($sample_id, true);
			}
			wholesale_review_manager_flash('success', sprintf('Deleted %d sample reviews.', count($samples)));
			break;
	}

	wp_safe_redirect(wholesale_review_manager_url(array_filter($redirect_args)));
	exit;
}

function wholesale_review_manager_sources()
{
	return array(
		'email' => 'Sent to us by email',
		'phone' => 'Given by phone',
		'google' => 'Copied from Google',
		'other' => 'Other',
	);
}

/**
 * Imports a JSON file shaped like {"reviews": [{product_slug, rating, title, body,
 * author, company_name, location, publish_date, is_sample}]}. Reviews are matched to
 * products by slug. Real reviews wait for approval; sample reviews stay drafts.
 */
function wholesale_review_manager_import()
{
	$file = $_FILES['reviews_json'] ?? null;
	if (!$file || UPLOAD_ERR_OK !== $file['error'] || !is_uploaded_file($file['tmp_name'])) {
		wholesale_review_manager_flash('error', 'Choose a JSON file to import.');
		return;
	}
	if ($file['size'] > 2 * MB_IN_BYTES) {
		wholesale_review_manager_flash('error', 'The file is larger than 2 MB.');
		return;
	}

	$data = json_decode((string) file_get_contents($file['tmp_name']), true);
	$rows = isset($data['reviews']) && is_array($data['reviews']) ? $data['reviews'] : (is_array($data) && isset($data[0]) ? $data : null);
	if (null === $rows) {
		wholesale_review_manager_flash('error', 'The file isn’t valid JSON or has no <code>reviews</code> list.');
		return;
	}

	$result = wholesale_review_import_rows($rows);
	$lines = array(sprintf('<strong>Import finished.</strong> %d reviews added for approval, %d sample reviews added (hidden), %d already imported, %d invalid.', $result['imported'], $result['samples'], $result['duplicates'], $result['invalid']));
	if ($result['unknown_slugs']) {
		$lines[] = 'No product matched these slugs: <code>' . implode('</code>, <code>', array_map('esc_html', $result['unknown_slugs'])) . '</code>';
	}
	wholesale_review_manager_flash($result['unknown_slugs'] || $result['invalid'] ? 'warning' : 'success', implode('<br>', $lines));
}

/**
 * Creates review_submission posts from decoded import rows and returns the counts.
 */
function wholesale_review_import_rows($rows)
{
	$imported = $samples = $duplicates = $invalid = 0;
	$unknown_slugs = array();

	foreach ($rows as $row) {
		if (!is_array($row)) {
			$invalid++;
			continue;
		}
		$fields = wholesale_review_manager_fields(array(
			'product_slug' => $row['product_slug'] ?? '',
			'rating' => $row['rating'] ?? 0,
			'text' => $row['body'] ?? '',
			'name' => $row['author'] ?? '',
			'title' => $row['title'] ?? '',
			'company' => $row['company_name'] ?? '',
			'location' => $row['location'] ?? '',
			'date' => $row['publish_date'] ?? '',
		));
		if (is_wp_error($fields)) {
			if ('product' === $fields->get_error_code()) {
				$unknown_slugs[] = sanitize_title((string) ($row['product_slug'] ?? ''));
			} else {
				$invalid++;
			}
			continue;
		}

		$import_key = md5(implode('|', array($fields['product_id'], $fields['name'], $fields['title'], $fields['text'])));
		$existing = get_posts(array(
			'post_type' => 'review_submission',
			'post_status' => 'any',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'meta_key' => '_review_import_key',
			'meta_value' => $import_key,
		));
		if ($existing) {
			$duplicates++;
			continue;
		}

		$is_sample = !empty($row['is_sample']);
		$review_id = wp_insert_post(wholesale_review_manager_post_args($fields) + array(
			'post_type' => 'review_submission',
			'post_status' => $is_sample ? 'draft' : 'pending',
			'meta_input' => array(
				'_review_source' => 'import',
				'_review_import_key' => $import_key,
				'_review_is_sample' => $is_sample ? 1 : 0,
			),
		), true);
		if (is_wp_error($review_id)) {
			$invalid++;
			continue;
		}
		wholesale_review_manager_save_meta($review_id, $fields);
		$is_sample ? $samples++ : $imported++;
	}


	return array(
		'imported' => $imported,
		'samples' => $samples,
		'duplicates' => $duplicates,
		'invalid' => $invalid,
		'unknown_slugs' => array_values(array_unique($unknown_slugs)),
	);
}

/**
 * Pending reviews that bulk approval may publish, as rating => review IDs. Samples are
 * never included, and neither are JSON imports: those carry no proof a customer wrote
 * them, so each one is approved individually after checking it.
 */
function wholesale_review_bulk_candidates($product_id = 0)
{
	$query = array(
		'post_type' => 'review_submission',
		'post_status' => 'pending',
		'posts_per_page' => -1,
		'fields' => 'ids',
	);
	if ($product_id) {
		$query['meta_key'] = '_review_product_id';
		$query['meta_value'] = absint($product_id);
	}

	$by_rating = array_fill_keys(array(5, 4, 3, 2, 1), array());
	foreach (get_posts($query) as $review_id) {
		if (wholesale_review_is_sample($review_id) || 'import' === get_post_meta($review_id, '_review_source', true)) {
			continue;
		}
		$rating = absint(get_post_meta($review_id, '_review_rating', true));
		if (isset($by_rating[$rating])) {
			$by_rating[$rating][] = $review_id;
		}
	}
	return $by_rating;
}

function wholesale_review_bulk_approve($ratings, $product_id = 0)
{
	$candidates = wholesale_review_bulk_candidates($product_id);
	$approved = 0;

	foreach ($ratings as $rating) {
		foreach ($candidates[$rating] ?? array() as $review_id) {
			if (!is_wp_error(wp_update_post(array('ID' => $review_id, 'post_status' => 'publish'), true))) {
				$approved++;
			}
		}
	}
	return $approved;
}

/**
 * Card with one checkbox per star rating for bulk approval, scoped to a product or all.
 */
function wholesale_review_bulk_card($product = null)
{
	$candidates = wholesale_review_bulk_candidates($product ? $product->ID : 0);
	$total = array_sum(array_map('count', $candidates));
	?>
	<div class="wholesale-rm-card">
		<h2>Bulk approve<?php echo $product ? '' : ' (all products)'; ?></h2>
		<?php if (!$total) : ?>
			<p class="description">No reviews from customers or added by you are awaiting approval.</p>
		<?php else : ?>
			<?php wholesale_review_manager_form_open('bulk_approve', $product ? $product->post_name : '', 'onsubmit="return confirm(\'Publish the selected reviews on the site?\');"'); ?>
				<div class="wholesale-rm-ratings">
					<?php foreach ($candidates as $rating => $ids) : ?>
						<label><input type="checkbox" name="ratings[]" value="<?php echo esc_attr($rating); ?>" <?php disabled(!$ids); ?> <?php checked((bool) $ids); ?>>
							<?php echo esc_html(str_repeat('★', $rating) . ' ' . $rating . ' star (' . count($ids) . ')'); ?></label>
					<?php endforeach; ?>
				</div>
				<div class="wholesale-rm-actions">
					<button class="button button-primary">Approve selected</button>
				</div>
			</form>
			<p class="description">Covers reviews left through the site and reviews you added by hand. JSON imports and samples are left out; approve imports one at a time. Leaving low ratings pending while approving high ones hides honest feedback, so review those too.</p>
		<?php endif; ?>
	</div>
	<?php
}

// ------------------------------------------------------------------
// Bulk actions and rating filter on the "Customer Reviews" list screen
// ------------------------------------------------------------------

add_filter('bulk_actions-edit-review_submission', function ($actions) {
	return array(
		'wholesale_approve' => 'Approve (show on site)',
		'wholesale_unapprove' => 'Move to awaiting approval',
	) + $actions;
});

add_filter('handle_bulk_actions-edit-review_submission', function ($redirect, $action, $post_ids) {
	if (!in_array($action, array('wholesale_approve', 'wholesale_unapprove'), true)) {
		return $redirect;
	}

	$status = 'wholesale_approve' === $action ? 'publish' : 'pending';
	$done = $skipped = 0;

	foreach (array_map('absint', $post_ids) as $review_id) {
		$post = get_post($review_id);
		if (!$post || 'review_submission' !== $post->post_type || $status === $post->post_status || !current_user_can('edit_post', $review_id)) {
			continue;
		}
		// Same rule as the bulk card: samples and JSON imports are approved one at a time.
		if ('publish' === $status && (wholesale_review_is_sample($review_id) || 'import' === get_post_meta($review_id, '_review_source', true))) {
			$skipped++;
			continue;
		}
		if (!is_wp_error(wp_update_post(array('ID' => $review_id, 'post_status' => $status), true))) {
			$done++;
		}
	}

	return add_query_arg(array('wholesale_bulk' => $action, 'wholesale_done' => $done, 'wholesale_skipped' => $skipped), remove_query_arg(array('wholesale_bulk', 'wholesale_done', 'wholesale_skipped'), $redirect));
}, 10, 3);

add_action('admin_notices', function () {
	$screen = get_current_screen();
	if (!$screen || 'edit-review_submission' !== $screen->id || empty($_GET['wholesale_bulk'])) {
		return;
	}
	$done = absint($_GET['wholesale_done'] ?? 0);
	$skipped = absint($_GET['wholesale_skipped'] ?? 0);
	$message = 'wholesale_approve' === $_GET['wholesale_bulk']
		? sprintf('%d reviews approved and shown on the site.', $done)
		: sprintf('%d reviews moved to awaiting approval.', $done);
	if ($skipped) {
		$message .= ' ' . sprintf('%d skipped: sample and JSON-imported reviews must be approved one at a time from Reviews by Product.', $skipped);
	}
	printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $skipped ? 'warning' : 'success', esc_html($message));
});

add_filter('removable_query_args', function ($args) {
	return array_merge($args, array('wholesale_bulk', 'wholesale_done', 'wholesale_skipped'));
});

add_action('restrict_manage_posts', function ($post_type) {
	if ('review_submission' !== $post_type) {
		return;
	}
	$current = absint($_GET['review_rating'] ?? 0);
	echo '<label for="wholesale-filter-rating" class="screen-reader-text">Filter by rating</label>';
	echo '<select name="review_rating" id="wholesale-filter-rating"><option value="">All ratings</option>';
	for ($i = 5; $i >= 1; $i--) {
		printf('<option value="%1$d"%2$s>%3$s %1$d star</option>', $i, selected($current, $i, false), esc_html(str_repeat('★', $i)));
	}
	echo '</select>';
});

add_action('pre_get_posts', function ($query) {
	if (!is_admin() || !$query->is_main_query() || 'review_submission' !== $query->get('post_type')) {
		return;
	}
	$rating = absint($_GET['review_rating'] ?? 0);
	if ($rating >= 1 && $rating <= 5) {
		$meta_query = (array) $query->get('meta_query');
		$meta_query[] = array('key' => '_review_rating', 'value' => $rating, 'compare' => '=');
		$query->set('meta_query', $meta_query);
	}
});

// ------------------------------------------------------------------
// Rating in Quick Edit on the "Review Submissions" list screen
// ------------------------------------------------------------------

// Hidden value the Quick Edit script reads to preselect the current rating.
add_action('manage_review_submission_posts_custom_column', function ($column, $post_id) {
	if ('review_rating' === $column) {
		printf('<span class="hidden wholesale-qe-rating">%d</span>', absint(get_post_meta($post_id, '_review_rating', true)));
	}
}, 20, 2);

add_action('quick_edit_custom_box', function ($column_name, $post_type) {
	if ('review_submission' !== $post_type || 'review_rating' !== $column_name) {
		return;
	}
	?>
	<fieldset class="inline-edit-col-right">
		<div class="inline-edit-col">
			<label>
				<span class="title">Rating</span>
				<?php echo wholesale_review_manager_rating_select('_review_rating', 5); // Escaped in the helper. ?>
			</label>
		</div>
	</fieldset>
	<?php
}, 10, 2);

add_action('admin_footer-edit.php', function () {
	if ('review_submission' !== get_current_screen()->post_type) {
		return;
	}
	?>
	<script>
	jQuery(function ($) {
		if (typeof inlineEditPost === 'undefined') {
			return;
		}
		var edit = inlineEditPost.edit;
		inlineEditPost.edit = function (id) {
			edit.apply(this, arguments);
			var postId = typeof id === 'object' ? this.getId(id) : id;
			var rating = parseInt($('#post-' + postId + ' .wholesale-qe-rating').text(), 10);
			if (rating >= 1 && rating <= 5) {
				$('#edit-' + postId + ' select[name="_review_rating"]').val(String(rating));
			}
		};
	});
	</script>
	<?php
});

add_action('save_post_review_submission', function ($post_id) {
	if (!wp_doing_ajax() || 'inline-save' !== ($_POST['action'] ?? '') || !isset($_POST['_review_rating'])) {
		return;
	}
	check_ajax_referer('inlineeditnonce', '_inline_edit');
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	$rating = absint($_POST['_review_rating']);
	if ($rating >= 1 && $rating <= 5) {
		update_post_meta($post_id, '_review_rating', $rating);
	}
});

function wholesale_review_manager_rating_select($name, $selected, $required = true)
{
	$html = '<select name="' . esc_attr($name) . '"' . ($required ? ' required' : '') . '>';
	if (!$selected) {
		$html .= '<option value="">Rating</option>';
	}
	for ($i = 5; $i >= 1; $i--) {
		$html .= '<option value="' . $i . '"' . selected($selected, $i, false) . '>' . str_repeat('★', $i) . ' ' . $i . '</option>';
	}
	return $html . '</select>';
}

function wholesale_review_manager_products()
{
	return get_posts(array(
		'post_type' => 'product',
		'post_status' => array('publish', 'draft', 'private'),
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
	));
}

function wholesale_review_manager_page()
{
	$products = wholesale_review_manager_products();
	$product = isset($_GET['product']) ? wholesale_product_by_slug(wp_unslash($_GET['product'])) : null;
	?>
	<div class="wrap wholesale-rm">
		<?php wholesale_review_manager_styles(); ?>
		<datalist id="wholesale-rm-slugs">
			<?php foreach ($products as $item) : ?>
				<option value="<?php echo esc_attr($item->post_name); ?>"><?php echo esc_html($item->post_title); ?></option>
			<?php endforeach; ?>
		</datalist>
		<?php
		if ($product) {
			wholesale_review_manager_product_view($product);
		} else {
			wholesale_review_manager_overview($products);
		}
		?>
	</div>
	<?php
}

function wholesale_review_manager_form_open($task, $product_slug = '', $extra = '')
{
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" ' . $extra . '>';
	echo '<input type="hidden" name="action" value="wholesale_review_manager">';
	if ($task) {
		echo '<input type="hidden" name="task" value="' . esc_attr($task) . '">';
	}
	echo '<input type="hidden" name="product" value="' . esc_attr($product_slug) . '">';
	wp_nonce_field('wholesale_review_manager');
}

function wholesale_review_manager_overview($products)
{
	$stats = wholesale_review_stats_by_product();
	$show_all = isset($_GET['show']) && 'all' === $_GET['show'];
	$total_samples = array_sum(wp_list_pluck($stats, 'samples'));
	$empty = array('published' => 0, 'pending' => 0, 'samples' => 0, 'sum' => 0, 'average' => 0.0);
	?>
	<h1 class="wp-heading-inline">Reviews by Product</h1>
	<hr class="wp-header-end">
	<?php wholesale_review_manager_notice(); ?>
	<p class="description">Every product review, grouped by product slug. Open a product to edit, approve or move its reviews. The star rating on product cards is the average of each product’s approved reviews and updates on its own.</p>

	<div class="wholesale-rm-toolbar">
		<form method="get" action="<?php echo esc_url(admin_url('edit.php')); ?>" class="wholesale-rm-open">
			<input type="hidden" name="post_type" value="review_submission">
			<input type="hidden" name="page" value="<?php echo esc_attr(WHOLESALE_REVIEW_MANAGER_PAGE); ?>">
			<label for="wholesale-rm-open-slug" class="screen-reader-text">Product slug</label>
			<input id="wholesale-rm-open-slug" type="text" name="product" list="wholesale-rm-slugs" placeholder="Type a product slug…" class="regular-text" required>
			<button class="button button-primary">Open product</button>
		</form>
		<a class="button" href="<?php echo esc_url(wholesale_review_manager_url($show_all ? array() : array('show' => 'all'))); ?>"><?php echo $show_all ? 'Only products with reviews' : 'Show all products'; ?></a>
	</div>

	<table class="widefat striped wholesale-rm-table">
		<thead>
			<tr>
				<th>Product</th>
				<th>Approved</th>
				<th>Awaiting approval</th>
				<th>Samples</th>
				<th>Shown on product cards</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$rows = 0;
			foreach ($products as $item) :
				$row = isset($stats[$item->ID]) ? $stats[$item->ID] : $empty;
				if (!$show_all && !isset($stats[$item->ID])) {
					continue;
				}
				$rows++;
				$manage_url = wholesale_review_manager_url(array('product' => $item->post_name));
				?>
				<tr>
					<td>
						<a class="row-title" href="<?php echo esc_url($manage_url); ?>"><?php echo esc_html($item->post_title); ?></a>
						<br><code><?php echo esc_html($item->post_name); ?></code>
					</td>
					<td><?php echo esc_html($row['published']); ?></td>
					<td><?php echo $row['pending'] ? '<a href="' . esc_url(add_query_arg('view', 'pending', $manage_url)) . '"><strong>' . esc_html($row['pending']) . '</strong></a>' : '0'; ?></td>
					<td><?php echo esc_html($row['samples']); ?></td>
					<td><?php echo $row['published'] ? wholesale_review_stars($row['average'], 'wholesale-rm-stars') . ' ' . esc_html(number_format($row['average'], 1)) : '&mdash;'; ?></td>
					<td><a class="button" href="<?php echo esc_url($manage_url); ?>">Manage</a></td>
				</tr>
			<?php endforeach; ?>
			<?php if (!$rows) : ?>
				<tr><td colspan="6">No product has reviews yet. Import a file below, add a review from a product’s page, or choose <em>Show all products</em>.</td></tr>
			<?php endif; ?>
			<?php if (!empty($stats[0])) : ?>
				<tr><td colspan="6"><?php echo esc_html(sprintf('%d general reviews (sent through the site-wide “Leave a review” form) are not tied to a product.', $stats[0]['published'] + $stats[0]['pending'])); ?> <a href="<?php echo esc_url(admin_url('edit.php?post_type=review_submission')); ?>">See all review submissions</a></td></tr>
			<?php endif; ?>
		</tbody>
	</table>

	<div class="wholesale-rm-cards">
		<?php wholesale_review_bulk_card(); ?>
		<div class="wholesale-rm-card">
			<h2>Import reviews from JSON</h2>
			<p>Each review is matched to a product by its <code>product_slug</code>. Imported reviews wait under <em>Awaiting approval</em> until you approve them. Reviews marked <code>"is_sample": true</code> are placeholder text: they stay hidden, can’t be approved, and only appear on a product page when you open it with <code>?review_preview=1</code>.</p>
			<?php wholesale_review_manager_form_open('import', '', 'enctype="multipart/form-data"'); ?>
				<input type="file" name="reviews_json" accept=".json,application/json" required>
				<button class="button button-primary">Import</button>
			</form>
		</div>
		<div class="wholesale-rm-card">
			<h2>Product card ratings</h2>
			<p>Cards show the average and count of each product’s approved reviews. Approving, hiding or trashing a review changes the card right away. Products with no approved reviews show no stars.</p>
			<?php if ($total_samples) : ?>
				<hr>
				<p><?php echo esc_html(sprintf('%d sample reviews are stored for layout testing.', $total_samples)); ?></p>
				<?php wholesale_review_manager_form_open('delete_samples', '', 'onsubmit="return confirm(\'Permanently delete all sample reviews?\');"'); ?>
					<button class="button button-link-delete">Delete all sample reviews</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

function wholesale_review_manager_product_view($product)
{
	$stats = wholesale_product_review_stats($product->ID);
	$shown = wholesale_product_review_data($product->ID);
	$view = sanitize_key(wp_unslash($_GET['view'] ?? ''));
	$views = array(
		'' => array('All', $stats['published'] + $stats['pending'] + $stats['samples']),
		'publish' => array('Approved', $stats['published']),
		'pending' => array('Awaiting approval', $stats['pending']),
		'sample' => array('Samples', $stats['samples']),
	);
	if (!isset($views[$view])) {
		$view = '';
	}

	$reviews = get_posts(array(
		'post_type' => 'review_submission',
		'post_status' => array('publish', 'pending', 'draft'),
		'posts_per_page' => -1,
		'meta_key' => '_review_product_id',
		'meta_value' => $product->ID,
		'orderby' => 'date',
		'order' => 'DESC',
	));
	$reviews = array_filter($reviews, function ($review) use ($view) {
		$is_sample = wholesale_review_is_sample($review->ID);
		if ('sample' === $view) {
			return $is_sample;
		}
		if ('publish' === $view) {
			return !$is_sample && 'publish' === $review->post_status;
		}
		if ('pending' === $view) {
			return !$is_sample && 'publish' !== $review->post_status;
		}
		return true;
	});
	?>
	<p><a href="<?php echo esc_url(wholesale_review_manager_url()); ?>">&larr; All products</a></p>
	<h1 class="wp-heading-inline"><?php echo esc_html($product->post_title); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url(get_permalink($product)); ?>#product-reviews" target="_blank" rel="noopener">View on site</a>
	<?php if ($stats['samples']) : ?>
		<a class="page-title-action" href="<?php echo esc_url(add_query_arg('review_preview', '1', get_permalink($product))); ?>#product-reviews" target="_blank" rel="noopener">Preview with samples</a>
	<?php endif; ?>
	<a class="page-title-action" href="<?php echo esc_url(get_edit_post_link($product->ID)); ?>">Edit product</a>
	<hr class="wp-header-end">
	<p><code><?php echo esc_html($product->post_name); ?></code></p>
	<?php wholesale_review_manager_notice(); ?>

	<div class="wholesale-rm-cards">
		<div class="wholesale-rm-card">
			<h2>Star rating on product cards</h2>
			<p><?php echo $stats['published'] ? wholesale_review_stars($stats['average'], 'wholesale-rm-stars') . ' ' . esc_html(number_format($stats['average'], 1) . ' from ' . $stats['published'] . ' approved reviews') : 'No approved reviews yet, so the card shows no stars.'; ?></p>
			<p class="description">Worked out live from the approved reviews below.</p>
		</div>
		<div class="wholesale-rm-card">
			<h2>Featured review text</h2>
			<?php wholesale_review_manager_form_open('featured_text', $product->post_name); ?>
				<textarea name="featured_text" rows="3" class="large-text" placeholder="Short quote shown above the reviews"><?php echo esc_textarea($shown['text']); ?></textarea>
				<p class="description">Use a quote from an approved review. Leave empty to hide it.</p>
				<button class="button">Save text</button>
			</form>
		</div>
		<?php wholesale_review_bulk_card($product); ?>
	</div>

	<ul class="subsubsub">
		<?php
		$links = array();
		foreach ($views as $key => $info) {
			$links[] = sprintf(
				'<li><a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url(wholesale_review_manager_url(array_filter(array('product' => $product->post_name, 'view' => $key)))),
				$key === $view ? ' class="current" aria-current="page"' : '',
				esc_html($info[0]),
				$info[1]
			);
		}
		echo implode(' |</li>', $links) . '</li>'; // Escaped above.
		?>
	</ul>
	<div class="clear"></div>

	<?php if (!$reviews) : ?>
		<p class="wholesale-rm-empty">No reviews here yet.</p>
	<?php endif; ?>

	<?php foreach ($reviews as $review) :
		$is_sample = wholesale_review_is_sample($review->ID);
		$status = $review->post_status;
		$source = get_post_meta($review->ID, '_review_source', true);
		$email = get_post_meta($review->ID, '_review_email', true);
		$field_id = 'wholesale-rm-' . $review->ID;
		?>
		<div class="wholesale-rm-review<?php echo $is_sample ? ' is-sample' : ''; ?>">
			<?php wholesale_review_manager_form_open('', $product->post_name); ?>
				<input type="hidden" name="review_id" value="<?php echo esc_attr($review->ID); ?>">
				<input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
				<div class="wholesale-rm-review-head">
					<?php if ($is_sample) : ?>
						<span class="order-badge order-badge--muted">Sample · never shown to customers</span>
					<?php elseif ('publish' === $status) : ?>
						<span class="order-badge order-badge--success">Shown on site</span>
					<?php else : ?>
						<span class="order-badge order-badge--warn">Awaiting approval</span>
					<?php endif; ?>
					<span class="wholesale-rm-meta">
						<?php
						$meta = array('#' . $review->ID);
						$meta[] = '' === $source ? 'Customer form' : ('import' === $source ? 'Imported' : 'Added by admin');
						if ($email) {
							$meta[] = $email;
						}
						echo esc_html(implode(' · ', $meta));
						?>
					</span>
				</div>
				<div class="wholesale-rm-grid">
					<label>Rating<?php echo wholesale_review_manager_rating_select('rating', absint(get_post_meta($review->ID, '_review_rating', true))); // Escaped in the helper. ?></label>
					<label>Date<input type="date" name="date" value="<?php echo esc_attr(get_the_date('Y-m-d', $review)); ?>"></label>
					<label>Product slug<input type="text" name="product_slug" list="wholesale-rm-slugs" value="<?php echo esc_attr($product->post_name); ?>" required></label>
					<label>Name<input type="text" name="name" value="<?php echo esc_attr(get_post_meta($review->ID, '_review_name', true)); ?>" required></label>
					<label>Company<input type="text" name="company" value="<?php echo esc_attr(get_post_meta($review->ID, '_review_company', true)); ?>"></label>
					<label>Location<input type="text" name="location" value="<?php echo esc_attr(get_post_meta($review->ID, '_review_location', true)); ?>" placeholder="City, ST"></label>
				</div>
				<label class="wholesale-rm-wide">Headline<input type="text" name="title" value="<?php echo esc_attr(get_post_meta($review->ID, '_review_title', true)); ?>"></label>
				<label class="wholesale-rm-wide" for="<?php echo esc_attr($field_id); ?>">Review</label>
				<textarea id="<?php echo esc_attr($field_id); ?>" name="text" rows="3" class="large-text" required><?php echo esc_textarea($review->post_content); ?></textarea>
				<div class="wholesale-rm-actions">
					<button class="button" name="task" value="save">Save</button>
					<?php if (!$is_sample && 'publish' !== $status) : ?>
						<button class="button button-primary" name="task" value="approve">Save &amp; approve</button>
					<?php elseif ('publish' === $status) : ?>
						<button class="button" name="task" value="unpublish">Hide from site</button>
					<?php endif; ?>
					<button class="button button-link-delete" name="task" value="trash" formnovalidate onclick="return confirm('Move this review to the trash?');">Trash</button>
				</div>
			</form>
		</div>
	<?php endforeach; ?>

	<details class="wholesale-rm-card wholesale-rm-add">
		<summary><strong>Add a review you received</strong> <span class="description">(email, phone, Google…)</span></summary>
		<p class="description">For genuine feedback a customer gave you outside the website. Reviews added here are labelled “Customer review”, not “Verified buyer”.</p>
		<?php wholesale_review_manager_form_open('add', $product->post_name); ?>
			<div class="wholesale-rm-grid">
				<label>Rating<?php echo wholesale_review_manager_rating_select('rating', 0); // Escaped in the helper. ?></label>
				<label>Date<input type="date" name="date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>"></label>
				<label>Product slug<input type="text" name="product_slug" list="wholesale-rm-slugs" value="<?php echo esc_attr($product->post_name); ?>" required></label>
				<label>Name<input type="text" name="name" required></label>
				<label>Company<input type="text" name="company"></label>
				<label>Location<input type="text" name="location" placeholder="City, ST"></label>
				<label>Customer email<input type="email" name="email"></label>
				<label>Where it came from<select name="source">
					<?php foreach (wholesale_review_manager_sources() as $key => $label) : ?>
						<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select></label>
				<label>Reference (order #, email date…)<input type="text" name="source_note"></label>
			</div>
			<label class="wholesale-rm-wide">Headline<input type="text" name="title"></label>
			<label class="wholesale-rm-wide" for="wholesale-rm-new-text">Review</label>
			<textarea id="wholesale-rm-new-text" name="text" rows="3" class="large-text" required></textarea>
			<div class="wholesale-rm-actions">
				<label><input type="checkbox" name="status" value="publish"> Show on site right away</label>
				<button class="button button-primary">Add review</button>
			</div>
		</form>
	</details>
	<?php
}

function wholesale_review_manager_styles()
{
	?>
	<style>
		.wholesale-rm .wholesale-rm-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; margin: 16px 0 12px; }
		.wholesale-rm .wholesale-rm-open { display: flex; gap: 6px; }
		.wholesale-rm .wholesale-rm-table td { vertical-align: middle; }
		.wholesale-rm .wholesale-rm-stars .review-star { color: #c3c4c7; }
		.wholesale-rm .wholesale-rm-stars .review-star.is-full, .wholesale-rm .wholesale-rm-stars .review-star.is-half { color: #f5ad27; }
		.wholesale-rm .wholesale-rm-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin: 20px 0; }
		.wholesale-rm .wholesale-rm-card { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 14px 18px; }
		.wholesale-rm .wholesale-rm-card h2 { margin-top: 4px; font-size: 15px; }
		.wholesale-rm .wholesale-rm-summary th { text-align: left; padding: 4px 16px 4px 0; font-weight: 500; color: #50575e; }
		.wholesale-rm .wholesale-rm-review { background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #72aee6; border-radius: 6px; padding: 12px 16px; margin: 12px 0; }
		.wholesale-rm .wholesale-rm-review.is-sample { border-left-color: #a7aaad; background: #f6f7f7; }
		.wholesale-rm .wholesale-rm-review-head { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 10px; }
		.wholesale-rm .wholesale-rm-meta { color: #646970; font-size: 12px; }
		.wholesale-rm .wholesale-rm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 10px; }
		.wholesale-rm label { display: grid; gap: 3px; font-weight: 500; font-size: 12px; color: #3c434a; }
		.wholesale-rm .wholesale-rm-actions label { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; }
		.wholesale-rm .wholesale-rm-wide { margin-bottom: 6px; }
		.wholesale-rm .wholesale-rm-grid input, .wholesale-rm .wholesale-rm-grid select, .wholesale-rm .wholesale-rm-wide input { width: 100%; max-width: none; }
		.wholesale-rm .wholesale-rm-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 8px; }
		.wholesale-rm .wholesale-rm-add { margin-top: 20px; }
		.wholesale-rm .wholesale-rm-add summary { cursor: pointer; padding: 4px 0; }
		.wholesale-rm .wholesale-rm-empty { color: #646970; }
		.wholesale-rm .wholesale-rm-ratings { display: grid; gap: 4px; }
		.wholesale-rm .wholesale-rm-ratings label { display: flex; align-items: center; gap: 6px; font-size: 13px; }
	</style>
	<?php
}
