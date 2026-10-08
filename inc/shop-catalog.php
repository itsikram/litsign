<?php
/**
 * Shop page (/shop/): every listed product with category, pricing, price
 * range, rating and keyword filters.
 *
 * The page renders the full filtered list on the server, so crawlers and
 * shared links (?cat=banners&sort=price-asc) see real products. js/shop-catalog.js
 * then filters, sorts and pages the same cards in the browser without reloads.
 *
 * @package litsign
 */

/**
 * Create the Shop page once, so the template deploys with the theme.
 */
function wholesale_shop_ensure_page()
{
	if ('1' === get_option('wholesale_shop_page_version')) {
		return;
	}

	$page = get_page_by_path('shop');
	if (!$page) {
		$page_id = wp_insert_post(array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Shop',
			'post_name' => 'shop',
			'meta_input' => array('_wp_page_template' => 'page-shop.php'),
		));
		if (is_wp_error($page_id) || !$page_id) {
			return;
		}
	} elseif ('page-shop.php' !== get_page_template_slug($page)) {
		update_post_meta($page->ID, '_wp_page_template', 'page-shop.php');
	}

	update_option('wholesale_shop_page_version', '1');
}
add_action('init', 'wholesale_shop_ensure_page', 30);

function wholesale_is_shop_page()
{
	return is_page() && is_page_template('page-shop.php');
}

function wholesale_shop_url()
{
	$page = get_page_by_path('shop');
	return $page ? get_permalink($page) : home_url('/shop/');
}

add_action('wp_enqueue_scripts', function () {
	if (!wholesale_is_shop_page()) {
		return;
	}

	$theme_uri = get_template_directory_uri();
	$theme_path = get_template_directory();
	wp_enqueue_style('wholesale-shop-catalog', $theme_uri . '/css/shop-catalog.css', array('custom-style'), (string) filemtime($theme_path . '/css/shop-catalog.css'));
	wp_enqueue_script('wholesale-shop-catalog', $theme_uri . '/js/shop-catalog.js', array(), (string) filemtime($theme_path . '/js/shop-catalog.js'), true);
}, 20);

/**
 * Filtered views (?cat=, ?sort=, ...) canonicalize to /shop/ and stay out of
 * the index; their product links are still followed.
 */
add_filter('wp_robots', function ($robots) {
	if (wholesale_is_shop_page() && wholesale_shop_has_filters()) {
		$robots['noindex'] = true;
		$robots['follow'] = true;
	}
	return $robots;
}, 20);

/**
 * URL filter state, sanitized. Keys match the ones js/shop-catalog.js writes.
 */
function wholesale_shop_request_state()
{
	$get = static function ($key) {
		if (!isset($_GET[$key])) {
			return '';
		}
		// The no-script form sends checkbox groups as cat[]=a&cat[]=b.
		$value = wp_unslash($_GET[$key]);
		return trim(is_array($value) ? implode(',', array_map('strval', $value)) : (string) $value);
	};

	$list = static function ($value) {
		return array_values(array_filter(array_map('sanitize_title', explode(',', $value))));
	};

	// ?price=10-50 from the script, or ?pmin=&pmax= from the form without it.
	$price_param = $get('price');
	if ('' === $price_param && ('' !== $get('pmin') || '' !== $get('pmax'))) {
		$price_param = (float) $get('pmin') . '-' . $get('pmax');
	}
	$price = array_map('floatval', array_pad(explode('-', $price_param, 2), 2, ''));
	$sort = sanitize_key($get('sort'));

	return array(
		'q' => sanitize_text_field($get('q')),
		'cat' => $list($get('cat')),
		'pricing' => array_values(array_intersect($list($get('pricing')), array('sqft', 'item', 'inch'))),
		'price_min' => '' !== $price_param && $price[0] > 0 ? $price[0] : null,
		'price_max' => '' !== $price_param && $price[1] > 0 ? $price[1] : null,
		'rating' => min(5, absint($get('rating'))),
		'sort' => in_array($sort, array_keys(wholesale_shop_sort_options()), true) ? $sort : 'featured',
	);
}

function wholesale_shop_has_filters()
{
	foreach (array('q', 'cat', 'pricing', 'price', 'pmin', 'pmax', 'rating', 'sort') as $key) {
		if (isset($_GET[$key]) && '' !== $_GET[$key]) {
			return true;
		}
	}
	return false;
}

function wholesale_shop_sort_options()
{
	return array(
		'featured' => __('Featured', 'litsign'),
		'price-asc' => __('Price: low to high', 'litsign'),
		'price-desc' => __('Price: high to low', 'litsign'),
		'rating' => __('Top rated', 'litsign'),
		'name' => __('Name: A to Z', 'litsign'),
	);
}

function wholesale_shop_pricing_labels()
{
	return array(
		'sqft' => array(__('Priced per sq ft', 'litsign'), __('Printed to your size', 'litsign'), '/sq ft'),
		'item' => array(__('Priced per item', 'litsign'), __('Set sizes & kits', 'litsign'), '/item'),
		'inch' => array(__('Priced per letter inch', 'litsign'), __('Channel letters', 'litsign'), '/inch'),
	);
}

/**
 * Top-level category order for the Featured sort: storefront signs first.
 */
function wholesale_shop_category_priority()
{
	return array('signs-letters', 'large-format', 'banners', 'indoor-outdoor-displays', 'banner-stand-hardware', 'event-tent-hardware-only');
}

/**
 * Category tree limited to categories that hold listed products.
 *
 * @param array $products Catalog rows from wholesale_shop_products().
 * @return array List of array('term' => WP_Term, 'count' => int, 'children' => array(...)).
 */
function wholesale_shop_category_tree($products)
{
	$counts = array();
	foreach ($products as $product) {
		foreach ($product['cats'] as $slug) {
			$counts[$slug] = isset($counts[$slug]) ? $counts[$slug] + 1 : 1;
		}
	}

	$terms = get_terms(array(
		'taxonomy' => 'product_category',
		'hide_empty' => false,
		'orderby' => 'name',
	));
	if (is_wp_error($terms)) {
		return array();
	}

	$by_parent = array();
	foreach ($terms as $term) {
		if (!empty($counts[$term->slug])) {
			$by_parent[$term->parent][] = $term;
		}
	}

	$build = static function ($parent_id) use (&$build, $by_parent, $counts) {
		$nodes = array();
		foreach (isset($by_parent[$parent_id]) ? $by_parent[$parent_id] : array() as $term) {
			$nodes[] = array(
				'term' => $term,
				'count' => $counts[$term->slug],
				'children' => $build($term->term_id),
			);
		}
		return $nodes;
	};

	$tree = $build(0);
	$priority = array_flip(wholesale_shop_category_priority());
	usort($tree, static function ($a, $b) use ($priority) {
		$pa = isset($priority[$a['term']->slug]) ? $priority[$a['term']->slug] : 99;
		$pb = isset($priority[$b['term']->slug]) ? $priority[$b['term']->slug] : 99;
		return $pa === $pb ? strcmp($a['term']->name, $b['term']->name) : $pa - $pb;
	});

	return $tree;
}

/**
 * Every product shown in listings, with what the cards and filters need.
 *
 * @return array[]
 */
function wholesale_shop_products()
{
	static $products = null;
	if (null !== $products) {
		return $products;
	}

	$query = new WP_Query(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'no_found_rows' => true,
		'meta_query' => array(
			array('key' => '_show_in_list', 'value' => 'on'),
		),
	));

	$priority = array_flip(wholesale_shop_category_priority());
	$products = array();

	foreach ($query->posts as $post) {
		$id = $post->ID;
		$terms = get_the_terms($id, 'product_category');
		$terms = $terms && !is_wp_error($terms) ? $terms : array();

		// The deepest assigned category labels the card; ancestors make the
		// parent filters ("Large Format") include their sub-categories.
		$primary = null;
		$cats = array();
		foreach ($terms as $term) {
			$cats[] = $term->slug;
			foreach (get_ancestors($term->term_id, 'product_category', 'taxonomy') as $ancestor_id) {
				$ancestor = get_term($ancestor_id, 'product_category');
				if ($ancestor && !is_wp_error($ancestor)) {
					$cats[] = $ancestor->slug;
				}
			}
			if (!$primary || $term->parent) {
				$primary = $term;
			}
		}
		$cats = array_values(array_unique($cats));
		if (!$cats) {
			continue;
		}

		$top = $primary;
		while ($top && $top->parent) {
			$top = get_term($top->parent, 'product_category');
		}

		// "Starting at" text: the last dollar amount is the current price; a
		// struck-through <del> amount before it is the regular price.
		$starting_text = (string) get_post_meta($id, '_starting_at_text', true);
		$plain = strtolower(wp_strip_all_tags($starting_text));
		$price = 0.0;
		$was = 0.0;
		if (preg_match_all('/\$\s?([\d,]+(?:\.\d+)?)/', wp_strip_all_tags($starting_text), $matches)) {
			$price = (float) str_replace(',', '', end($matches[1]));
		}
		if (preg_match('/<del>[^$]*\$\s?([\d,]+(?:\.\d+)?)/i', $starting_text, $del)) {
			$was = (float) str_replace(',', '', $del[1]);
		}
		$unit = false !== strpos($plain, 'inch') ? 'inch' : (preg_match('/ft|sq/', $plain) ? 'sqft' : 'item');

		// Up to three feature bullets from the listing (or short) description.
		$features = array();
		$desc = get_post_meta($id, '_product_list_desc', true);
		$desc = $desc ? $desc : get_post_meta($id, '_product_short_desc', true);
		if ($desc && preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $desc, $items)) {
			foreach ($items[1] as $item) {
				$item = trim(preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', html_entity_decode(wp_strip_all_tags($item), ENT_QUOTES, 'UTF-8'))));
				if ('' !== $item) {
					$features[] = $item;
				}
			}
		}

		$reviews = function_exists('wholesale_product_review_data') ? wholesale_product_review_data($id) : array('rating' => 0, 'count' => 0);

		$products[] = array(
			'id' => $id,
			'title' => html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8'),
			'url' => get_permalink($id),
			'thumb' => (int) get_post_thumbnail_id($id),
			'cats' => $cats,
			'cat_name' => $primary ? $primary->name : '',
			'price' => $price,
			'was' => $was > $price ? $was : 0.0,
			'unit' => $unit,
			'rating' => (float) $reviews['rating'],
			'reviews' => (int) $reviews['count'],
			'features' => array_slice($features, 0, 3),
			'order' => sprintf(
				'%02d%04d%s',
				$top && isset($priority[$top->slug]) ? $priority[$top->slug] : 99,
				(int) get_post_meta($id, '_order_by_index', true),
				sanitize_title($post->post_title)
			),
		);
	}

	usort($products, static function ($a, $b) {
		return strcmp($a['order'], $b['order']);
	});

	return $products;
}

/**
 * Apply URL filter state on the server (same rules as js/shop-catalog.js).
 */
function wholesale_shop_filter($products, $state)
{
	$needle = strtolower($state['q']);

	$products = array_values(array_filter($products, static function ($product) use ($state, $needle) {
		if ($state['cat'] && !array_intersect($state['cat'], $product['cats'])) {
			return false;
		}
		if ($state['pricing'] && !in_array($product['unit'], $state['pricing'], true)) {
			return false;
		}
		if (null !== $state['price_min'] && $product['price'] < $state['price_min']) {
			return false;
		}
		if (null !== $state['price_max'] && $product['price'] > $state['price_max']) {
			return false;
		}
		if ($state['rating'] && $product['rating'] < $state['rating']) {
			return false;
		}
		if ('' !== $needle) {
			// Same text the script searches: what the card shows.
			$haystack = strtolower($product['title'] . ' ' . $product['cat_name'] . ' ' . implode(' ', $product['features']));
			foreach (preg_split('/\s+/', $needle) as $word) {
				if (false === strpos($haystack, $word)) {
					return false;
				}
			}
		}
		return true;
	}));

	$compare = array(
		'price-asc' => static function ($a, $b) { return $a['price'] <=> $b['price']; },
		'price-desc' => static function ($a, $b) { return $b['price'] <=> $a['price']; },
		'rating' => static function ($a, $b) { return array($b['rating'], $b['reviews']) <=> array($a['rating'], $a['reviews']); },
		'name' => static function ($a, $b) { return strcasecmp($a['title'], $b['title']); },
	);
	if (isset($compare[$state['sort']])) {
		usort($products, $compare[$state['sort']]);
	}

	return $products;
}

/**
 * Format a price the way the product pages show it: $11.70, $194.98, $2.2 → $2.20.
 */
function wholesale_shop_money($amount)
{
	return '$' . number_format((float) $amount, 2);
}

/**
 * One product card. Data attributes feed the client-side filters.
 */
function wholesale_shop_render_card($product, $index)
{
	$units = wholesale_shop_pricing_labels();
	$show_rating = function_exists('wholesale_setting_enabled') ? wholesale_setting_enabled('show_product_ratings') : true;
	?>
	<li class="sc-card"
		data-cats="<?php echo esc_attr(implode(' ', $product['cats'])); ?>"
		data-unit="<?php echo esc_attr($product['unit']); ?>"
		data-price="<?php echo esc_attr($product['price']); ?>"
		data-rating="<?php echo esc_attr($product['rating']); ?>"
		data-reviews="<?php echo esc_attr($product['reviews']); ?>"
		data-order="<?php echo esc_attr($product['order']); ?>"
>
		<article class="sc-card-inner">
			<div class="sc-card-media">
				<?php
				if ($product['thumb']) {
					echo wp_get_attachment_image($product['thumb'], 'product-card', false, array(
						'alt' => $product['title'],
						'loading' => $index < 4 ? 'eager' : 'lazy',
						'fetchpriority' => $index < 2 ? 'high' : 'auto',
						'decoding' => 'async',
						'sizes' => '(max-width: 575px) 50vw, (max-width: 991px) 33vw, 290px',
					));
				} else {
					echo '<span class="sc-card-placeholder" aria-hidden="true"></span>';
				}
				?>
				<?php if ($product['was']) : ?>
					<span class="sc-badge sc-badge--sale"><?php echo esc_html(sprintf(__('Save %d%%', 'litsign'), round(100 - $product['price'] / $product['was'] * 100))); ?></span>
				<?php endif; ?>
			</div>
			<div class="sc-card-body">
				<?php if ($product['cat_name']) : ?>
					<p class="sc-card-cat"><?php echo esc_html($product['cat_name']); ?></p>
				<?php endif; ?>
				<h3 class="sc-card-title"><a href="<?php echo esc_url($product['url']); ?>"><?php echo esc_html($product['title']); ?></a></h3>
				<?php if ($show_rating && $product['reviews']) : ?>
					<p class="sc-card-rating">
						<?php echo wholesale_review_stars($product['rating'], 'sc-stars'); // Escaped in helper. ?>
						<span><?php echo esc_html(number_format($product['rating'], 1)); ?> <span class="sc-muted">(<?php echo esc_html(number_format_i18n($product['reviews'])); ?>)</span></span>
					</p>
				<?php endif; ?>
				<?php if ($product['features']) : ?>
					<ul class="sc-card-features">
						<?php foreach ($product['features'] as $feature) : ?>
							<li><?php echo esc_html($feature); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="sc-card-foot">
					<?php if ($product['price'] > 0) : ?>
						<p class="sc-card-price">
							<span class="sc-card-price-label"><?php esc_html_e('Starting at', 'litsign'); ?></span>
							<span class="sc-card-price-value">
								<?php if ($product['was']) : ?><del><?php echo esc_html(wholesale_shop_money($product['was'])); ?></del><?php endif; ?>
								<strong><?php echo esc_html(wholesale_shop_money($product['price'])); ?></strong><small><?php echo esc_html($units[$product['unit']][2]); ?></small>
							</span>
						</p>
					<?php else : ?>
						<p class="sc-card-price"><span class="sc-card-price-label"><?php esc_html_e('Price', 'litsign'); ?></span><strong><?php esc_html_e('Get a quote', 'litsign'); ?></strong></p>
					<?php endif; ?>
					<span class="sc-card-cta" aria-hidden="true"><?php esc_html_e('Customize', 'litsign'); ?> <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
				</div>
			</div>
		</article>
	</li>
	<?php
}

/**
 * Category checkbox tree for the filter panel.
 */
function wholesale_shop_render_category_tree($nodes, $selected, $depth = 0)
{
	echo '<ul class="sc-tree' . ($depth ? ' sc-tree--child' : '') . '">';
	foreach ($nodes as $node) {
		$term = $node['term'];
		$id = 'sc-cat-' . $term->slug;
		$has_children = !empty($node['children']);
		$open = $has_children && wholesale_shop_tree_has_selected($node, $selected);
		echo '<li class="sc-tree-item' . ($has_children ? ' has-children' : '') . ($open ? ' is-open' : '') . '">';
		echo '<div class="sc-tree-row">';
		printf(
			'<label class="sc-check" for="%1$s"><input type="checkbox" id="%1$s" name="cat[]" value="%2$s"%3$s><span class="sc-check-box" aria-hidden="true"></span><span class="sc-check-label">%4$s</span><span class="sc-count" data-count-for="cat:%2$s">%5$d</span></label>',
			esc_attr($id),
			esc_attr($term->slug),
			checked(in_array($term->slug, $selected, true), true, false),
			esc_html($term->name),
			(int) $node['count']
		);
		if ($has_children) {
			printf(
				'<button type="button" class="sc-tree-toggle" aria-expanded="%1$s" aria-controls="%2$s-children" aria-label="%3$s"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></button>',
				$open ? 'true' : 'false',
				esc_attr($id),
				esc_attr(sprintf(__('Show %s sub-categories', 'litsign'), $term->name))
			);
		}
		echo '</div>';
		if ($has_children) {
			echo '<div id="' . esc_attr($id) . '-children" class="sc-tree-children"' . ($open ? '' : ' hidden') . '>';
			wholesale_shop_render_category_tree($node['children'], $selected, $depth + 1);
			echo '</div>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

function wholesale_shop_tree_has_selected($node, $selected)
{
	foreach ($node['children'] as $child) {
		if (in_array($child['term']->slug, $selected, true) || wholesale_shop_tree_has_selected($child, $selected)) {
			return true;
		}
	}
	return false;
}
