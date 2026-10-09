<?php
/**
 * One-time price updates for products in selected product categories.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_REVIEW_PRICING_SNAPSHOT_META = '_wholesale_review_pricing_snapshot';

function wholesale_review_pricing_target_products($category_ids = array())
{
	$query = array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'orderby' => 'title',
		'order' => 'ASC',
	);
	if ($category_ids) {
		$query['tax_query'] = array(
			array(
				'taxonomy' => 'product_category',
				'field' => 'term_id',
				'terms' => array_map('absint', $category_ids),
				'include_children' => true,
			),
		);
	}

	return get_posts($query);
}

function wholesale_review_pricing_category_ids()
{
	$raw_ids = isset($_POST['category_ids']) ? (array) wp_unslash($_POST['category_ids']) : array();
	$raw_ids = array_filter($raw_ids, static function ($id) {
		return is_scalar($id) && preg_match('/^\d+$/', (string) $id);
	});
	$ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));
	$valid_ids = array();
	foreach ($ids as $term_id) {
		$term = get_term($term_id, 'product_category');
		if ($term && !is_wp_error($term)) {
			$valid_ids[] = $term_id;
		}
	}
	return $valid_ids;
}

function wholesale_review_pricing_scale_option_value($value, $multiplier)
{
	if (!is_string($value) && !is_numeric($value)) {
		return $value;
	}

	$value = (string) $value;
	if (!preg_match('/^(\s*)(-?\d+(?:\.\d+)?)(.*)$/', $value, $matches)) {
		return $value;
	}

	$suffix = $matches[3];
	if (preg_match('~^/\s*%($|/)~', $suffix)) {
		return $value;
	}

	$scaled = round((float) $matches[2] * $multiplier, 4);
	return $matches[1] . rtrim(rtrim(number_format($scaled, 4, '.', ''), '0'), '.') . $suffix;
}

function wholesale_review_pricing_scale_attributes($attributes, $multiplier)
{
	foreach ($attributes as &$attribute) {
		if (
			empty($attribute['options'])
			|| !is_array($attribute['options'])
			|| false !== strpos((string) ($attribute['cssClass'] ?? ''), 'avoid-price')
		) {
			continue;
		}
		foreach ($attribute['options'] as &$option) {
			if (is_array($option)) {
				foreach ($option as &$value) {
					$value = wholesale_review_pricing_scale_option_value($value, $multiplier);
				}
				unset($value);
			} else {
				$option = wholesale_review_pricing_scale_option_value($option, $multiplier);
			}
		}
		unset($option);
	}
	unset($attribute);
	return $attributes;
}

function wholesale_review_pricing_sale_text($text, $multiplier, $old_discount)
{
	$text = (string) $text;
	$source_regular_price = null;
	if (preg_match('/<del\b[^>]*>.*?\$\s?([\d,]+(?:\.\d+)?).*?<\/del>/is', $text, $previous_regular)) {
		$source_regular_price = (float) str_replace(',', '', $previous_regular[1]);
	}
	$without_previous_sale = preg_replace('/<del\b[^>]*>.*?<\/del>/is', '', $text);
	if (!preg_match_all('/\$\s?([\d,]+(?:\.\d+)?)/', $without_previous_sale, $prices, PREG_OFFSET_CAPTURE) || !$prices[0]) {
		return $text;
	}

	$last_price = end($prices[0]);
	$listed_price = (float) str_replace(',', '', end($prices[1])[0]);
	$regular_price = round(($source_regular_price ?: $listed_price) * $multiplier, 2);
	$current_price = null === $source_regular_price
		? round($listed_price * (1 - ($old_discount / 100)), 2)
		: $listed_price;
	$replacement = '<del>$' . number_format($regular_price, 2, '.', ',') . '</del> $' . number_format($current_price, 2, '.', ',');
	$offset = $last_price[1];
	return substr($without_previous_sale, 0, $offset) . $replacement . substr($without_previous_sale, $offset + strlen($last_price[0]));
}

function wholesale_review_pricing_apply_product($product_id, $increase_percent)
{
	if (metadata_exists('post', $product_id, WHOLESALE_REVIEW_PRICING_SNAPSHOT_META)) {
		return 'already_applied';
	}

	$raw_attributes = (string) get_post_meta($product_id, 'product_attr', true);
	$attributes = '' === trim($raw_attributes) ? array() : json_decode($raw_attributes, true);
	if (!is_array($attributes)) {
		return 'invalid_attributes';
	}

	$multiplier = 1 + ($increase_percent / 100);
	$old_discount = min(100, max(0, (float) get_post_meta($product_id, '_discount_percent', true)));
	$new_discount = 100 * (1 - ((1 - ($old_discount / 100)) / $multiplier));
	$price_per_sqft = get_post_meta($product_id, '_price_per_sqft', true);
	$scaled_attributes = wholesale_review_pricing_scale_attributes($attributes, $multiplier);

	$has_price = false;
	$scaled_price_per_sqft = $price_per_sqft;
	if ('' !== (string) $price_per_sqft && is_numeric($price_per_sqft) && (float) $price_per_sqft > 0) {
		$scaled_price_per_sqft = number_format(round((float) $price_per_sqft * $multiplier, 4), 4, '.', '');
		$has_price = true;
	}
	if ($scaled_attributes !== $attributes) {
		$has_price = true;
	}
	if (!$has_price) {
		return 'no_pricing';
	}

	$keys = array('_price_per_sqft', 'product_attr', '_discount_percent', '_starting_at_text');
	$snapshot = array();
	foreach ($keys as $key) {
		$snapshot[$key] = array(
			'exists' => metadata_exists('post', $product_id, $key),
			'value' => get_post_meta($product_id, $key, true),
		);
	}
	update_post_meta($product_id, WHOLESALE_REVIEW_PRICING_SNAPSHOT_META, $snapshot);

	if ($has_price && '' !== (string) $price_per_sqft && is_numeric($price_per_sqft)) {
		update_post_meta($product_id, '_price_per_sqft', $scaled_price_per_sqft);
	}
	if ($scaled_attributes !== $attributes) {
		update_post_meta($product_id, 'product_attr', wp_slash(wp_json_encode($scaled_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
	}
	update_post_meta($product_id, '_discount_percent', number_format($new_discount, 4, '.', ''));

	$starting_text = get_post_meta($product_id, '_starting_at_text', true);
	if (is_string($starting_text) && '' !== $starting_text) {
		update_post_meta($product_id, '_starting_at_text', wp_slash(wholesale_review_pricing_sale_text($starting_text, $multiplier, $old_discount)));
	}

	return 'applied';
}

function wholesale_review_pricing_restore_products()
{
	$product_ids = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_key' => WHOLESALE_REVIEW_PRICING_SNAPSHOT_META,
	));
	$restored = 0;
	foreach ($product_ids as $product_id) {
		$snapshot = get_post_meta($product_id, WHOLESALE_REVIEW_PRICING_SNAPSHOT_META, true);
		if (!is_array($snapshot)) {
			continue;
		}
		foreach ($snapshot as $key => $original) {
			if (!is_array($original) || !array_key_exists('exists', $original)) {
				continue;
			}
			if ($original['exists']) {
				update_post_meta($product_id, $key, $original['value']);
			} else {
				delete_post_meta($product_id, $key);
			}
		}
		delete_post_meta($product_id, WHOLESALE_REVIEW_PRICING_SNAPSHOT_META);
		$restored++;
	}
	return $restored;
}

add_action('admin_menu', static function () {
	add_submenu_page(
		'edit.php?post_type=review_submission',
		'Review Product Sale Pricing',
		'Review Product Sale Pricing',
		WHOLESALE_REVIEW_MANAGER_CAP,
		'wholesale-review-pricing',
		'wholesale_review_pricing_admin_page'
	);
});

add_action('admin_post_wholesale_review_pricing', 'wholesale_review_pricing_handle');
function wholesale_review_pricing_handle()
{
	if (!current_user_can(WHOLESALE_REVIEW_MANAGER_CAP)) {
		wp_die('Not allowed.');
	}
	check_admin_referer('wholesale_review_pricing');

	$task = sanitize_key(wp_unslash($_POST['task'] ?? ''));
	if ('restore' === $task) {
		$result = array('restored' => wholesale_review_pricing_restore_products());
	} elseif ('apply' === $task) {
		$min_increase_raw = isset($_POST['min_increase_percent']) ? wp_unslash($_POST['min_increase_percent']) : '';
		$max_increase_raw = isset($_POST['max_increase_percent']) ? wp_unslash($_POST['max_increase_percent']) : '';
		if (!is_scalar($min_increase_raw) || !is_scalar($max_increase_raw) || !preg_match('/^\d+$/', (string) $min_increase_raw) || !preg_match('/^\d+$/', (string) $max_increase_raw)) {
			wp_die('Enter whole-number minimum and maximum price increases.');
		}
		$min_increase_percent = (int) $min_increase_raw;
		$max_increase_percent = (int) $max_increase_raw;
		if ($min_increase_percent < 1 || $max_increase_percent > 100 || $min_increase_percent > $max_increase_percent) {
			wp_die('Enter a minimum price increase from 1 to 100 and a maximum that is at least the minimum and no more than 100.');
		}

		$selection_mode_raw = wp_unslash($_POST['selection_mode'] ?? '');
		$selection_mode = is_scalar($selection_mode_raw) ? sanitize_key((string) $selection_mode_raw) : '';
		$all_products = wholesale_review_pricing_target_products();
		if ('category' === $selection_mode) {
			$category_ids = wholesale_review_pricing_category_ids();
			if (!$category_ids) {
				wp_die('Select at least one product category.');
			}
			$selected_products = wholesale_review_pricing_target_products($category_ids);
			if (!$selected_products) {
				wp_die('No published products were found in the selected categories.');
			}
		} elseif ('manual' === $selection_mode) {
			$raw_product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();
			$raw_product_ids = array_filter($raw_product_ids, static function ($product_id) {
				return is_scalar($product_id) && preg_match('/^\d+$/', (string) $product_id);
			});
			$requested_ids = array_values(array_unique(array_filter(array_map('absint', $raw_product_ids))));
			if (!$requested_ids) {
				wp_die('Select at least one product.');
			}
			$selected_products = array_values(array_intersect($requested_ids, $all_products));
			if (!$selected_products) {
				wp_die('None of the selected products are published products.');
			}
		} else {
			wp_die('Choose whether to select products by category or manually.');
		}

		$result = array('applied' => 0, 'already_applied' => 0, 'invalid_attributes' => 0, 'no_pricing' => 0, 'increase_percentages' => array());
		foreach ($selected_products as $product_id) {
			$increase_percent = wp_rand($min_increase_percent, $max_increase_percent);
			$status = wholesale_review_pricing_apply_product($product_id, $increase_percent);
			$result[$status]++;
			if ('applied' === $status) {
				$result['increase_percentages'][$increase_percent] = isset($result['increase_percentages'][$increase_percent]) ? $result['increase_percentages'][$increase_percent] + 1 : 1;
			}
		}
	} else {
		wp_die('Invalid price update action.');
	}

	set_transient('wholesale_review_pricing_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS);
	wp_safe_redirect(admin_url('edit.php?post_type=review_submission&page=wholesale-review-pricing'));
	exit;
}

function wholesale_review_pricing_admin_page()
{
	$products = wholesale_review_pricing_target_products();
	$categories = get_terms(array(
		'taxonomy' => 'product_category',
		'hide_empty' => true,
		'orderby' => 'name',
		'order' => 'ASC',
	));
	$categories = is_wp_error($categories) ? array() : $categories;
	$default_categories = array();
	foreach (array('banners', 'adhesive-products') as $slug) {
		$term = get_term_by('slug', $slug, 'product_category');
		if ($term && !is_wp_error($term)) {
			$default_categories[] = (int) $term->term_id;
		}
	}
	$pending_count = count(array_filter($products, static function ($product_id) {
		return !metadata_exists('post', $product_id, WHOLESALE_REVIEW_PRICING_SNAPSHOT_META);
	}));
	$result_key = 'wholesale_review_pricing_result_' . get_current_user_id();
	$result = get_transient($result_key);
	if ($result) {
		delete_transient($result_key);
	}
	$applied_ids = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'any',
		'posts_per_page' => 1,
		'fields' => 'ids',
		'meta_key' => WHOLESALE_REVIEW_PRICING_SNAPSHOT_META,
	));
	?>
	<div class="wrap">
		<h1>Review Product Sale Pricing</h1>
		<p>Choose any published product, whether or not it has reviews. Category selection includes products assigned to the selected category or any of its subcategories. Manual selection offers all published products across all categories.</p>
		<p>Each selected product gets a random regular-price increase in the chosen range. Its sale discount is recalculated to keep the current product price unchanged. Calculator minimums and expedite add-ons keep their existing behavior. Original product pricing is backed up and can be restored below.</p>
		<?php if ($result) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				if (isset($result['restored'])) {
					echo esc_html(sprintf('Restored original pricing for %d products.', $result['restored']));
				} else {
					echo esc_html(sprintf('Updated %d products; skipped %d already updated, %d with unreadable options, and %d without configured pricing.', $result['applied'], $result['already_applied'], $result['invalid_attributes'], $result['no_pricing']));
					if (!empty($result['increase_percentages'])) {
						$distribution = array();
						ksort($result['increase_percentages'], SORT_NUMERIC);
						foreach ($result['increase_percentages'] as $percent => $count) {
							$distribution[] = sprintf('%d%% (%d products)', $percent, $count);
						}
						echo ' ' . esc_html('Regular-price increase distribution: ' . implode(', ', $distribution) . '.');
					}
				}
				?>
			</p></div>
		<?php endif; ?>
		<p><strong><?php echo esc_html(count($products)); ?></strong> published product(s) found; <strong><?php echo esc_html($pending_count); ?></strong> have not been updated by this tool.</p>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Apply the price change to the selected products?');">
			<input type="hidden" name="action" value="wholesale_review_pricing">
			<input type="hidden" name="task" value="apply">
			<?php wp_nonce_field('wholesale_review_pricing'); ?>
			<fieldset>
				<legend><strong>Select products by</strong></legend>
				<label><input type="radio" name="selection_mode" value="category" checked> Category</label>
				<label><input type="radio" name="selection_mode" value="manual"> Manually</label>
			</fieldset>
			<p class="wholesale-review-pricing-categories">
				<label for="wholesale-review-pricing-category"><strong>Categories</strong></label><br>
				<select id="wholesale-review-pricing-category" name="category_ids[]" multiple size="8">
					<?php foreach ($categories as $category) : ?>
						<option value="<?php echo esc_attr($category->term_id); ?>" <?php selected(in_array((int) $category->term_id, $default_categories, true)); ?>>
							<?php echo esc_html(str_repeat('- ', count(get_ancestors($category->term_id, 'product_category', 'taxonomy'))) . $category->name); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<br><span class="description">Select one or more categories; subcategories are included.</span>
			</p>
			<p class="wholesale-review-pricing-products" hidden>
				<label for="wholesale-review-pricing-products"><strong>Products</strong></label><br>
				<select id="wholesale-review-pricing-products" name="product_ids[]" multiple size="12">
					<?php foreach ($products as $product_id) : ?>
						<option value="<?php echo esc_attr($product_id); ?>">
							<?php echo esc_html(get_the_title($product_id) . ' (' . get_post_field('post_name', $product_id) . ')'); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="wholesale-review-min-increase"><strong>Increase regular price by a random percentage</strong></label><br>
				<input id="wholesale-review-min-increase" type="number" name="min_increase_percent" value="10" min="1" max="100" step="1" required>
				% min &ndash;
				<input id="wholesale-review-max-increase" type="number" name="max_increase_percent" value="20" min="1" max="100" step="1" required> % max
				<br><span class="description">Each product gets a random whole-number regular-price increase within this inclusive range; the current price stays as the sale price.</span>
			</p>
			<button type="submit" class="button button-primary">Apply price changes</button>
		</form>
		<script>
		(function () {
			var modes = document.querySelectorAll('input[name="selection_mode"]');
			var categoryFields = document.querySelector('.wholesale-review-pricing-categories');
			var productFields = document.querySelector('.wholesale-review-pricing-products');
			function updateSelectionFields() {
				var selectedMode = document.querySelector('input[name="selection_mode"]:checked');
				var manual = selectedMode && selectedMode.value === 'manual';
				categoryFields.hidden = manual;
				productFields.hidden = !manual;
				document.getElementById('wholesale-review-pricing-category').required = !manual;
				document.getElementById('wholesale-review-pricing-products').required = manual;
			}
			modes.forEach(function (mode) { mode.addEventListener('change', updateSelectionFields); });
			updateSelectionFields();
		}());
		</script>
		<?php if ($applied_ids) : ?>
			<hr>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Restore the original prices for all products updated by this tool?');">
				<input type="hidden" name="action" value="wholesale_review_pricing">
				<input type="hidden" name="task" value="restore">
				<?php wp_nonce_field('wholesale_review_pricing'); ?>
				<button type="submit" class="button">Restore original prices</button>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
