<?php
/**
 * Google reviews on product pages (2026-11-12).
 *
 * The business's Google reviews are kept in one list (option wholesale_google_review_list).
 * Google's Places API returns only five of them, so those five are added on their own
 * whenever the review slider refreshes them (wholesale_fetch_google_reviews()), and the
 * owner adds the others by hand in wp-admin > Reviews > Google reviews. Each review is
 * linked to products and/or whole categories; a product page shows the ones linked to it
 * in its reviews section.
 *
 * These are Google reviews of the business, not reviews of the product collected on this
 * site, so they never go into the Product structured data (aggregateRating / review):
 * Google's review snippet rules do not allow ratings taken from another site. Reviews that
 * say we installed the sign stay hidden (wholesale_review_text_hidden()), and the minimum
 * star rating in Settings applies, as in the slider.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_GOOGLE_REVIEW_LIST = 'wholesale_google_review_list';
const WHOLESALE_GOOGLE_REVIEW_PAGE = 'wholesale-google-reviews';
const WHOLESALE_GOOGLE_REVIEW_CAP = 'edit_others_posts';

/**
 * Every Google review on the site, links included.
 *
 * Entry: id, key, source ('google' = came from the Places API, 'manual' = typed in),
 * google_id, author, author_url, photo, rating, text, date (Y-m-d), url,
 * products (int[]), categories (product_category slugs), added.
 *
 * @return array[]
 */
function wholesale_google_review_list()
{
	$list = get_option(WHOLESALE_GOOGLE_REVIEW_LIST, null);
	if (is_array($list)) {
		return $list;
	}

	// First use: start from the reviews the slider already has.
	$list = array();
	$backup = defined('WHOLESALE_GOOGLE_REVIEWS_BACKUP') ? get_option(WHOLESALE_GOOGLE_REVIEWS_BACKUP) : null;
	if (is_array($backup) && !empty($backup['reviews'])) {
		$list = wholesale_google_review_list_merge($list, $backup['reviews']);
	}
	update_option(WHOLESALE_GOOGLE_REVIEW_LIST, $list, false);

	return $list;
}

function wholesale_google_review_list_save($list)
{
	update_option(WHOLESALE_GOOGLE_REVIEW_LIST, array_values($list), false);
	// Product pages are served from the static page cache.
	if (function_exists('sfs_page_cache_purge')) {
		sfs_page_cache_purge();
	}
}

/**
 * Matches the same review across API refreshes and hand-typed copies.
 */
function wholesale_google_review_key($author, $text)
{
	$text = mb_strtolower(mb_substr(preg_replace('/\s+/u', ' ', trim((string) $text)), 0, 80));
	return 'g' . substr(md5(mb_strtolower(trim((string) $author)) . '|' . $text), 0, 12);
}

/**
 * "2 weeks ago" / "a year ago" (the Places API's relative time) -> approximate Y-m-d.
 */
function wholesale_google_review_date_from_relative($when)
{
	$when = preg_replace('/^(a|an)\s+/i', '1 ', trim((string) $when));
	$time = '' !== $when ? strtotime($when) : false;
	return $time ? wp_date('Y-m-d', $time) : '';
}

/**
 * Adds reviews from the Places API to the list, or refreshes ones already in it.
 * Google's copy wins for text and rating (the reviewer may have edited it); links stay.
 */
function wholesale_google_review_list_merge($list, $reviews)
{
	foreach ((array) $reviews as $review) {
		$text = isset($review['text']) ? trim((string) $review['text']) : '';
		if ('' === $text) {
			continue;
		}
		$author = isset($review['author']) ? (string) $review['author'] : 'Google user';
		$key = wholesale_google_review_key($author, $text);
		$google_id = isset($review['id']) ? (string) $review['id'] : '';

		$found = null;
		foreach ($list as $index => $entry) {
			if (($google_id && $google_id === $entry['google_id']) || $key === $entry['key']) {
				$found = $index;
				break;
			}
		}

		$fields = array(
			'source' => 'google',
			'author' => $author,
			'author_url' => isset($review['author_url']) ? (string) $review['author_url'] : '',
			'photo' => isset($review['photo']) ? (string) $review['photo'] : '',
			'rating' => wholesale_sanitize_star_rating(isset($review['rating']) ? $review['rating'] : 5),
			'text' => $text,
			'url' => isset($review['url']) ? (string) $review['url'] : '',
		);
		if ($google_id) {
			$fields['google_id'] = $google_id;
		}
		$date = !empty($review['date']) ? (string) $review['date'] : '';

		if (null === $found) {
			$list[] = $fields + array(
				'id' => $key,
				'key' => $key,
				'google_id' => $google_id,
				'date' => $date ? $date : wholesale_google_review_date_from_relative(isset($review['when']) ? $review['when'] : ''),
				'products' => array(),
				'categories' => array(),
				'added' => time(),
			);
		} else {
			$list[$found] = array_merge($list[$found], $fields);
			if ($date) {
				$list[$found]['date'] = $date;
			}
		}
	}

	return $list;
}

// Each successful Places API refresh adds new reviews to the list.
add_action('wholesale_google_reviews_fetched', function ($data) {
	$list = wholesale_google_review_list();
	$merged = wholesale_google_review_list_merge($list, isset($data['reviews']) ? $data['reviews'] : array());
	if ($merged !== $list) {
		wholesale_google_review_list_save($merged);
	}
});

/**
 * Whether a review is shown on public pages at all.
 */
function wholesale_google_review_is_public($entry)
{
	$min_rating = wholesale_sanitize_star_rating(wholesale_get_setting('google_reviews_min_rating'));
	return (int) $entry['rating'] >= $min_rating && !wholesale_review_text_hidden($entry['text']);
}

/**
 * Google reviews linked to a product, directly or through one of its categories.
 * Direct links come first, newest first within each group.
 */
function wholesale_product_google_reviews($product_id, $limit = 6)
{
	$product_id = absint($product_id);
	$terms = get_the_terms($product_id, 'product_category');
	$slugs = $terms && !is_wp_error($terms) ? wp_list_pluck($terms, 'slug') : array();

	$direct = array();
	$by_category = array();
	foreach (wholesale_google_review_list() as $entry) {
		if (!wholesale_google_review_is_public($entry)) {
			continue;
		}
		if (in_array($product_id, array_map('absint', (array) $entry['products']), true)) {
			$direct[] = $entry;
		} elseif ($slugs && array_intersect($slugs, (array) $entry['categories'])) {
			$by_category[] = $entry;
		}
	}

	$newest_first = static function ($a, $b) {
		return strcmp((string) $b['date'], (string) $a['date']);
	};
	usort($direct, $newest_first);
	usort($by_category, $newest_first);

	return array_slice(array_merge($direct, $by_category), 0, max(1, (int) $limit));
}

/**
 * The product page block (single-product.php), or '' when no Google review is linked.
 */
function wholesale_product_google_reviews_html($product_id)
{
	$reviews = wholesale_product_google_reviews($product_id);
	if (!$reviews) {
		return '';
	}
	$google = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;

	ob_start();
	?>
	<div class="product-google-reviews">
		<div class="product-google-reviews-head">
			<h3><?php echo wholesale_google_logo_svg(20); ?> From our Google reviews</h3>
			<?php if ($google && $google['rating'] > 0 && $google['url']) : ?>
				<a class="product-google-summary" href="<?php echo esc_url($google['url']); ?>" target="_blank" rel="noopener">
					<strong><?php echo esc_html(number_format($google['rating'], 1)); ?></strong>
					<?php echo wholesale_review_stars($google['rating']); ?>
					<span><?php echo esc_html(sprintf(_n('%s Google review', '%s Google reviews', $google['count'], 'litsign'), number_format_i18n($google['count']))); ?></span>
				</a>
			<?php endif; ?>
		</div>
		<div class="product-review-list">
			<?php foreach ($reviews as $review) :
				$rating = wholesale_sanitize_star_rating($review['rating']);
				$time = $review['date'] ? strtotime($review['date']) : 0;
			?>
				<article class="product-review-card product-review-card--google">
					<div class="product-review-card-header">
						<span class="product-review-stars" aria-label="<?php echo esc_attr($rating . ' out of 5 stars'); ?>"><?php echo esc_html(str_repeat('★', $rating) . str_repeat('☆', 5 - $rating)); ?></span>
						<span class="product-review-google-badge"><?php echo wholesale_google_logo_svg(14); ?> Google</span>
					</div>
					<p><?php echo esc_html($review['text']); ?></p>
					<div class="product-review-author">
						<?php if ($review['author_url']) : ?>
							<strong><a href="<?php echo esc_url($review['author_url']); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html($review['author']); ?></a></strong>
						<?php else : ?>
							<strong><?php echo esc_html($review['author']); ?></strong>
						<?php endif; ?>
						<?php if ($time) : ?>
							<time datetime="<?php echo esc_attr(wp_date('Y-m', $time)); ?>"><?php echo esc_html(wp_date('M Y', $time)); ?></time>
						<?php endif; ?>
						<?php if ($review['url']) : ?>
							<a class="product-review-google-link" href="<?php echo esc_url($review['url']); ?>" target="_blank" rel="noopener">Read on Google</a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

// ------------------------------------------------------------------
// wp-admin: Reviews > Google reviews
// ------------------------------------------------------------------

add_action('admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=review_submission',
		'Google reviews',
		'Google reviews',
		WHOLESALE_GOOGLE_REVIEW_CAP,
		WHOLESALE_GOOGLE_REVIEW_PAGE,
		'wholesale_google_review_admin_page'
	);
});

function wholesale_google_review_admin_url($args = array())
{
	return add_query_arg($args, admin_url('edit.php?post_type=review_submission&page=' . WHOLESALE_GOOGLE_REVIEW_PAGE));
}

/**
 * Published products grouped by their first category, for the "Show on" picker.
 *
 * @return array{categories: WP_Term[], groups: array<string, array{name: string, products: array<int, string>}>}
 */
function wholesale_google_review_product_choices()
{
	$categories = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => true, 'orderby' => 'name'));
	$categories = is_wp_error($categories) ? array() : $categories;

	$groups = array();
	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
		'no_found_rows' => true,
	));
	foreach ($products as $product) {
		$terms = get_the_terms($product->ID, 'product_category');
		$term = $terms && !is_wp_error($terms) ? $terms[0] : null;
		$slug = $term ? $term->slug : '';
		if (!isset($groups[$slug])) {
			$groups[$slug] = array('name' => $term ? wholesale_schema_text($term->name) : 'No category', 'products' => array());
		}
		$groups[$slug]['products'][$product->ID] = wholesale_schema_text(get_the_title($product));
	}
	uasort($groups, static function ($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	});

	return array('categories' => $categories, 'groups' => $groups);
}

/**
 * "Channel Letters (all), Backlit Film Prints, +3 more" for the list table.
 */
function wholesale_google_review_links_label($entry)
{
	$names = array();
	foreach ((array) $entry['categories'] as $slug) {
		$term = get_term_by('slug', $slug, 'product_category');
		$names[] = ($term ? wholesale_schema_text($term->name) : $slug) . ' (all)';
	}
	foreach ((array) $entry['products'] as $product_id) {
		$title = get_the_title(absint($product_id));
		if ('' !== $title) {
			$names[] = wholesale_schema_text($title);
		}
	}
	if (!$names) {
		return '';
	}
	$shown = array_slice($names, 0, 3);
	$more = count($names) - count($shown);
	return implode(', ', $shown) . ($more > 0 ? sprintf(', +%d more', $more) : '');
}

function wholesale_google_review_admin_page()
{
	if (!current_user_can(WHOLESALE_GOOGLE_REVIEW_CAP)) {
		wp_die('Not allowed.');
	}

	$list = wholesale_google_review_list();
	$google = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;
	$editing = isset($_GET['edit']) ? sanitize_key(wp_unslash($_GET['edit'])) : '';
	$adding = isset($_GET['add']);
	$entry = null;
	foreach ($list as $item) {
		if ($editing && $item['id'] === $editing) {
			$entry = $item;
		}
	}
	$min_rating = wholesale_sanitize_star_rating(wholesale_get_setting('google_reviews_min_rating'));
	$notice = isset($_GET['gr_notice']) ? sanitize_text_field(wp_unslash($_GET['gr_notice'])) : '';
	?>
	<div class="wrap wholesale-google-reviews-admin">
		<h1 class="wp-heading-inline">Google reviews on product pages</h1>
		<a class="page-title-action" href="<?php echo esc_url(wholesale_google_review_admin_url(array('add' => 1))); ?>#gr-form">Add a Google review by hand</a>
		<hr class="wp-header-end">

		<?php if ($notice) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
		<?php endif; ?>

		<p style="max-width:860px">
			Link each Google review to the products or categories it is about; it then shows in the reviews section of those product pages.
			<?php if ($google && $google['count']) : ?>
				Your Google listing has <strong><?php echo esc_html(number_format_i18n($google['count'])); ?> reviews</strong> (<?php echo esc_html(number_format($google['rating'], 1)); ?> stars).
			<?php endif; ?>
			Google only shares 5 of them with the website, and those 5 are added here on their own. To use the others, copy them from your Google listing with <em>Add a Google review by hand</em>.
			They are never added to the product star ratings or to Google's product data, and reviews that mention installing, or rated below <?php echo esc_html($min_rating); ?> stars (Settings), are not shown.
		</p>

		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:12px 0 18px">
			<input type="hidden" name="action" value="wholesale_google_review_sync">
			<?php wp_nonce_field('wholesale_google_review_sync'); ?>
			<button type="submit" class="button">Check Google for new reviews</button>
		</form>

		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:90px">Rating</th>
					<th style="width:160px">Reviewer</th>
					<th style="width:90px">Date</th>
					<th>Review</th>
					<th style="width:24%">Shows on</th>
					<th style="width:120px"></th>
				</tr>
			</thead>
			<tbody>
				<?php if (!$list) : ?>
					<tr><td colspan="6">No Google reviews yet. Click <em>Check Google for new reviews</em>, or add one by hand.</td></tr>
				<?php endif; ?>
				<?php foreach ($list as $item) :
					$links = wholesale_google_review_links_label($item);
					$public = wholesale_google_review_is_public($item);
				?>
					<tr>
						<td style="color:#f5ad27;white-space:nowrap"><?php echo esc_html(str_repeat('★', (int) $item['rating']) . str_repeat('☆', 5 - (int) $item['rating'])); ?></td>
						<td>
							<strong><?php echo esc_html($item['author']); ?></strong><br>
							<small><?php echo 'google' === $item['source'] ? 'From Google' : 'Added by hand'; ?></small>
						</td>
						<td><?php echo $item['date'] ? esc_html(wp_date('M Y', strtotime($item['date']))) : '&mdash;'; ?></td>
						<td>
							<?php echo esc_html(wp_trim_words($item['text'], 30)); ?>
							<?php if (!$public) : ?>
								<br><span style="color:#b32d2e"><?php echo wholesale_review_text_hidden($item['text']) ? 'Not shown: mentions installing.' : esc_html(sprintf('Not shown: below %d stars.', $min_rating)); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo $links ? esc_html($links) : '<em>Not linked yet</em>'; ?></td>
						<td>
							<a class="button button-small" href="<?php echo esc_url(wholesale_google_review_admin_url(array('edit' => $item['id']))); ?>#gr-form"><?php echo $links ? 'Edit links' : 'Link to products'; ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ($entry || $adding) :
			wholesale_google_review_admin_form($entry);
		endif; ?>
	</div>
	<?php
}

function wholesale_google_review_admin_form($entry)
{
	$is_new = null === $entry;
	$is_google = !$is_new && 'google' === $entry['source'];
	$entry = $entry ? $entry : array('id' => '', 'author' => '', 'rating' => 5, 'date' => '', 'text' => '', 'url' => '', 'products' => array(), 'categories' => array());
	$choices = wholesale_google_review_product_choices();
	$linked_products = array_map('absint', (array) $entry['products']);
	?>
	<div id="gr-form" class="card" style="max-width:980px;margin-top:22px;padding:16px 20px">
		<h2 style="margin-top:0"><?php echo $is_new ? 'Add a Google review by hand' : esc_html('Review by ' . $entry['author']); ?></h2>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="wholesale_google_review_save">
			<input type="hidden" name="review_id" value="<?php echo esc_attr($entry['id']); ?>">
			<?php wp_nonce_field('wholesale_google_review_save'); ?>

			<?php if ($is_google) : ?>
				<p><span style="color:#f5ad27"><?php echo esc_html(str_repeat('★', (int) $entry['rating'])); ?></span>
				<?php echo $entry['date'] ? esc_html(' · ' . wp_date('M j, Y', strtotime($entry['date']))) : ''; ?></p>
				<blockquote style="margin:0 0 6px;padding:10px 14px;background:#f6f7f7;border-left:3px solid #1fa8de"><?php echo esc_html($entry['text']); ?></blockquote>
				<p class="description">This review comes straight from Google, so its words and stars can only change on Google.</p>
			<?php else : ?>
				<p class="description">Copy the review exactly as it appears on your Google listing.</p>
				<table class="form-table" role="presentation">
					<tr><th><label for="gr-author">Reviewer name</label></th><td><input id="gr-author" class="regular-text" name="author" required value="<?php echo esc_attr($entry['author']); ?>"></td></tr>
					<tr><th><label for="gr-rating">Stars</label></th><td><select id="gr-rating" name="rating"><?php for ($stars = 5; $stars >= 1; $stars--) : ?><option value="<?php echo esc_attr($stars); ?>" <?php selected((int) $entry['rating'], $stars); ?>><?php echo esc_html($stars); ?></option><?php endfor; ?></select></td></tr>
					<tr><th><label for="gr-date">Date posted</label></th><td><input id="gr-date" type="date" name="date" value="<?php echo esc_attr($entry['date']); ?>"></td></tr>
					<tr><th><label for="gr-text">Review</label></th><td><textarea id="gr-text" name="text" rows="5" class="large-text" required><?php echo esc_textarea($entry['text']); ?></textarea></td></tr>
					<tr><th><label for="gr-url">Link to the review</label></th><td><input id="gr-url" type="url" class="large-text" name="url" placeholder="https://maps.app.goo.gl/..." value="<?php echo esc_attr($entry['url']); ?>"><p class="description">Optional. Shown as "Read on Google".</p></td></tr>
				</table>
			<?php endif; ?>

			<h3>Show this review on</h3>
			<p class="description">Pick whole categories (every product in them) and/or single products.</p>
			<fieldset style="columns:3 220px;margin:8px 0 14px">
				<?php foreach ($choices['categories'] as $term) : ?>
					<label style="display:block;break-inside:avoid;margin:2px 0"><input type="checkbox" name="categories[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked(in_array($term->slug, (array) $entry['categories'], true)); ?>> <?php echo esc_html(wholesale_schema_text($term->name)); ?> <span style="color:#787c82">(all <?php echo esc_html($term->count); ?>)</span></label>
				<?php endforeach; ?>
			</fieldset>

			<p><input type="search" class="regular-text" placeholder="Filter products..." data-gr-filter aria-label="Filter products"> <span class="description" data-gr-count></span></p>
			<div data-gr-products style="max-height:360px;overflow:auto;border:1px solid #dcdcde;border-radius:4px;padding:8px 12px;background:#fff">
				<?php foreach ($choices['groups'] as $group) : ?>
					<fieldset data-gr-group style="margin:0 0 10px">
						<legend style="font-weight:600;padding:4px 0"><?php echo esc_html($group['name']); ?></legend>
						<?php foreach ($group['products'] as $product_id => $title) : ?>
							<label data-gr-item style="display:block;margin:2px 0"><input type="checkbox" name="products[]" value="<?php echo esc_attr($product_id); ?>" <?php checked(in_array((int) $product_id, $linked_products, true)); ?>> <?php echo esc_html($title); ?></label>
						<?php endforeach; ?>
					</fieldset>
				<?php endforeach; ?>
			</div>

			<p class="submit" style="display:flex;gap:10px;align-items:center">
				<button type="submit" class="button button-primary"><?php echo $is_new ? 'Add review' : 'Save'; ?></button>
				<a class="button" href="<?php echo esc_url(wholesale_google_review_admin_url()); ?>">Cancel</a>
			</p>
		</form>

		<?php if (!$is_new && !$is_google) : ?>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:-6px">
				<input type="hidden" name="action" value="wholesale_google_review_delete">
				<input type="hidden" name="review_id" value="<?php echo esc_attr($entry['id']); ?>">
				<?php wp_nonce_field('wholesale_google_review_delete'); ?>
				<button type="submit" class="button-link button-link-delete">Remove this review from the site</button>
			</form>
		<?php endif; ?>
	</div>
	<script>
	(function () {
		var input = document.querySelector('[data-gr-filter]');
		var box = document.querySelector('[data-gr-products]');
		var count = document.querySelector('[data-gr-count]');
		if (!input || !box) return;
		var update = function () {
			var q = input.value.trim().toLowerCase();
			box.querySelectorAll('[data-gr-group]').forEach(function (group) {
				var any = false;
				group.querySelectorAll('[data-gr-item]').forEach(function (item) {
					var show = !q || item.textContent.toLowerCase().indexOf(q) !== -1;
					item.style.display = show ? 'block' : 'none';
					any = any || show;
				});
				group.style.display = any ? '' : 'none';
			});
			count.textContent = box.querySelectorAll('input:checked').length + ' products picked';
		};
		input.addEventListener('input', update);
		box.addEventListener('change', update);
		update();
	})();
	</script>
	<?php
}

add_action('admin_post_wholesale_google_review_save', function () {
	if (!current_user_can(WHOLESALE_GOOGLE_REVIEW_CAP)) {
		wp_die('Not allowed.');
	}
	check_admin_referer('wholesale_google_review_save');

	$list = wholesale_google_review_list();
	$id = isset($_POST['review_id']) ? sanitize_key(wp_unslash($_POST['review_id'])) : '';
	$index = null;
	foreach ($list as $i => $item) {
		if ($id && $item['id'] === $id) {
			$index = $i;
		}
	}

	$valid_slugs = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false, 'fields' => 'slugs'));
	$valid_slugs = is_wp_error($valid_slugs) ? array() : $valid_slugs;
	$categories = array_values(array_intersect(array_map('sanitize_title', (array) wp_unslash(isset($_POST['categories']) ? $_POST['categories'] : array())), $valid_slugs));
	$products = array_values(array_filter(array_unique(array_map('absint', (array) (isset($_POST['products']) ? $_POST['products'] : array()))), static function ($product_id) {
		return $product_id && 'product' === get_post_type($product_id);
	}));

	$is_google = null !== $index && 'google' === $list[$index]['source'];
	if (!$is_google) {
		$author = sanitize_text_field(wp_unslash(isset($_POST['author']) ? $_POST['author'] : ''));
		$text = sanitize_textarea_field(wp_unslash(isset($_POST['text']) ? $_POST['text'] : ''));
		if ('' === $author || '' === $text) {
			wp_safe_redirect(wholesale_google_review_admin_url(array('gr_notice' => 'Please fill in the reviewer name and the review.')));
			exit;
		}
		$date = sanitize_text_field(wp_unslash(isset($_POST['date']) ? $_POST['date'] : ''));
		$fields = array(
			'author' => $author,
			'rating' => wholesale_sanitize_star_rating(isset($_POST['rating']) ? $_POST['rating'] : 5),
			'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '',
			'text' => $text,
			'url' => esc_url_raw(wp_unslash(isset($_POST['url']) ? $_POST['url'] : '')),
		);
	} else {
		$fields = array();
	}
	$fields['products'] = $products;
	$fields['categories'] = $categories;

	if (null === $index) {
		$key = wholesale_google_review_key($fields['author'], $fields['text']);
		$list[] = $fields + array(
			'id' => 'm' . substr(md5($key . microtime()), 0, 12),
			'key' => $key,
			'source' => 'manual',
			'google_id' => '',
			'author_url' => '',
			'photo' => '',
			'added' => time(),
		);
		$message = 'Review added.';
	} else {
		$list[$index] = array_merge($list[$index], $fields);
		if (!$is_google) {
			$list[$index]['key'] = wholesale_google_review_key($fields['author'], $fields['text']);
		}
		$message = 'Saved.';
	}

	wholesale_google_review_list_save($list);
	wp_safe_redirect(wholesale_google_review_admin_url(array('gr_notice' => $message)));
	exit;
});

add_action('admin_post_wholesale_google_review_delete', function () {
	if (!current_user_can(WHOLESALE_GOOGLE_REVIEW_CAP)) {
		wp_die('Not allowed.');
	}
	check_admin_referer('wholesale_google_review_delete');
	$id = isset($_POST['review_id']) ? sanitize_key(wp_unslash($_POST['review_id'])) : '';
	// Only hand-typed reviews can be removed; Google's own come back on the next refresh.
	$list = array_filter(wholesale_google_review_list(), static function ($item) use ($id) {
		return !($item['id'] === $id && 'manual' === $item['source']);
	});
	wholesale_google_review_list_save($list);
	wp_safe_redirect(wholesale_google_review_admin_url(array('gr_notice' => 'Review removed.')));
	exit;
});

add_action('admin_post_wholesale_google_review_sync', function () {
	if (!current_user_can(WHOLESALE_GOOGLE_REVIEW_CAP)) {
		wp_die('Not allowed.');
	}
	check_admin_referer('wholesale_google_review_sync');
	$before = count(wholesale_google_review_list());
	$data = function_exists('wholesale_fetch_google_reviews') ? wholesale_fetch_google_reviews(true) : null;
	$added = count(wholesale_google_review_list()) - $before;
	if (!$data || !empty($data['error'])) {
		$message = 'Could not reach Google: ' . ($data && !empty($data['error']) ? $data['error'] : 'check the Place ID and API key in Settings.');
	} else {
		$message = sprintf('Google returned %d reviews; %d new.', count($data['reviews']), max(0, $added));
	}
	wp_safe_redirect(wholesale_google_review_admin_url(array('gr_notice' => $message)));
	exit;
});
