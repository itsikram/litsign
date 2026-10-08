<?php
/**
 * Template Name: Shop
 *
 * Shop all products (/shop/) with category, pricing, price, rating and
 * keyword filters. Logic lives in inc/shop-catalog.php; js/shop-catalog.js
 * filters the server-rendered cards in place.
 *
 * @package litsign
 */

$shop_url = get_permalink();
$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$builder_url = home_url('/channel-letter-builder/');

$all_products = wholesale_shop_products();
$state = wholesale_shop_request_state();
$products = wholesale_shop_filter($all_products, $state);
$category_tree = wholesale_shop_category_tree($all_products);
$pricing_labels = wholesale_shop_pricing_labels();
$sort_options = wholesale_shop_sort_options();
$total = count($all_products);

$price_ceiling = 0;
$pricing_counts = array_fill_keys(array_keys($pricing_labels), 0);
$rated = array(4 => 0, 3 => 0);
foreach ($all_products as $product) {
	$price_ceiling = max($price_ceiling, $product['price']);
	$pricing_counts[$product['unit']]++;
	foreach ($rated as $stars => $count) {
		if ($product['rating'] >= $stars) {
			$rated[$stars]++;
		}
	}
}
$price_ceiling = (int) ceil($price_ceiling);
$show_ratings = (function_exists('wholesale_setting_enabled') ? wholesale_setting_enabled('show_product_ratings') : true) && $rated[3] > 0;

// Category tiles: top-level categories, pictured by their first product.
$category_tiles = array();
foreach ($category_tree as $node) {
	// A parent gets its own tile only when it holds products outside its sub-categories.
	$tiles = $node['children'];
	$child_slugs = wp_list_pluck(wp_list_pluck($tiles, 'term'), 'slug');
	foreach ($all_products as $product) {
		if (in_array($node['term']->slug, $product['cats'], true) && !array_intersect($child_slugs, $product['cats'])) {
			array_unshift($tiles, $node);
			break;
		}
	}
	foreach ($tiles as $tile) {
		$tile_image = 0;
		foreach ($all_products as $product) {
			if ($product['thumb'] && in_array($tile['term']->slug, $product['cats'], true)) {
				$tile_image = $product['thumb'];
				break;
			}
		}
		$category_tiles[] = array('term' => $tile['term'], 'count' => $tile['count'], 'image' => $tile_image);
	}
}

// Structured data: the full product list (ItemList), FAQ, share image.
$GLOBALS['wholesale_page_items'] = wp_list_pluck($all_products, 'id');
if (!empty($all_products[0]['thumb'])) {
	$GLOBALS['wholesale_page_image'] = wp_get_attachment_image_url($all_products[0]['thumb'], 'large');
}
wholesale_seo_set_page_faq(array(
	array(
		'q' => 'How is the price of a custom sign worked out?',
		'a' => 'Each product shows where it starts. Printed products such as banners, vinyl and window graphics are priced per square foot of your size; flags, stands, frames and tents are priced per item; channel letters are priced per inch of letter height. Open any product, choose your size and options, and you see your full price before checkout.',
	),
	array(
		'q' => 'Can I upload my own artwork?',
		'a' => 'Yes. Most products let you upload print-ready artwork on the product page. Need a starting point? Download our <a href="' . esc_url(home_url('/design-templates/')) . '">free design templates</a>.',
	),
	array(
		'q' => 'How long does production and shipping take?',
		'a' => 'Every sign is made to order. Your estimated ship date is shown at checkout, and after production you can choose standard (3&ndash;6 business days), 3-day, 2-day or overnight shipping to any of the 50 states.',
	),
	array(
		'q' => 'I can&rsquo;t find the sign I need. Can you make it?',
		'a' => 'Very likely. Call <a href="tel:+18664362101">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PST) or <a href="' . esc_url($contact_url) . '">request a free quote</a> with your size and a photo or logo, and a sign specialist will price it for you.',
	),
));

$active_count = count($state['cat']) + count($state['pricing']) + ($state['rating'] ? 1 : 0)
	+ (null !== $state['price_min'] || null !== $state['price_max'] ? 1 : 0) + ('' !== $state['q'] ? 1 : 0);
$page_size = 24;

get_header();
?>

<main id="primary" class="site-main sc-page">

	<section class="sc-hero" aria-labelledby="sc-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<div class="sc-hero-grid">
				<div class="sc-hero-copy">
					<h1 id="sc-title" class="sc-hero-title"><?php esc_html_e('Shop Custom Signs, Banners & Displays', 'litsign'); ?></h1>
					<p class="sc-hero-lead">
						<?php
						echo esc_html(sprintf(
							/* translators: %d: number of products */
							__('Browse %d made-to-order products — LED channel letters, vinyl banners, window graphics, flags, stands and trade show displays. Every price is shown online, and every order ships to all 50 states.', 'litsign'),
							$total
						));
						?>
					</p>
				</div>
				<ul class="sc-hero-trust" aria-label="<?php esc_attr_e('Why order from us', 'litsign'); ?>">
					<li><?php echo wholesale_home_icon('check'); ?><span><strong><?php esc_html_e('Prices online', 'litsign'); ?></strong> <?php esc_html_e('See your total before checkout', 'litsign'); ?></span></li>
					<li><?php echo wholesale_home_icon('flag'); ?><span><strong><?php esc_html_e('Made to order', 'litsign'); ?></strong> <?php esc_html_e('Built for your business', 'litsign'); ?></span></li>
					<li><?php echo wholesale_home_icon('phone'); ?><span><strong><?php esc_html_e('Real sign experts', 'litsign'); ?></strong> <a href="tel:+18664362101">866-436-2101</a></span></li>
				</ul>
			</div>
		</div>
	</section>

	<nav class="sc-cats" aria-labelledby="sc-cats-title">
		<div class="container">
			<div class="sc-cats-head">
				<h2 id="sc-cats-title" class="sc-cats-title"><?php esc_html_e('Shop by category', 'litsign'); ?></h2>
				<div class="sc-cats-arrows" hidden>
					<button type="button" class="sc-icon-btn" data-scroll-cats="-1" aria-label="<?php esc_attr_e('Scroll categories left', 'litsign'); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></button>
					<button type="button" class="sc-icon-btn" data-scroll-cats="1" aria-label="<?php esc_attr_e('Scroll categories right', 'litsign'); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></button>
				</div>
			</div>
			<ul class="sc-cats-list">
				<?php foreach ($category_tiles as $tile) : ?>
					<li>
						<a class="sc-cat-tile<?php echo in_array($tile['term']->slug, $state['cat'], true) ? ' is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('cat', $tile['term']->slug, $shop_url) . '#sc-results'); ?>" data-cat-tile="<?php echo esc_attr($tile['term']->slug); ?>">
							<span class="sc-cat-tile-img">
								<?php
								if ($tile['image']) {
									echo wp_get_attachment_image($tile['image'], 'thumbnail', false, array('alt' => '', 'loading' => 'lazy', 'decoding' => 'async'));
								}
								?>
							</span>
							<span class="sc-cat-tile-text">
								<strong><?php echo esc_html($tile['term']->name); ?></strong>
								<small><?php echo esc_html(sprintf(_n('%d product', '%d products', $tile['count'], 'litsign'), $tile['count'])); ?></small>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>

	<div class="container sc-layout" id="sc-results">

		<form class="sc-filters" id="sc-filters" action="<?php echo esc_url($shop_url); ?>" method="get" role="search" aria-label="<?php esc_attr_e('Product filters', 'litsign'); ?>">
			<div class="sc-filters-head">
				<h2 class="sc-filters-title"><?php esc_html_e('Filters', 'litsign'); ?></h2>
				<a class="sc-link-btn sc-clear-all" href="<?php echo esc_url($shop_url); ?>#sc-results"<?php echo $active_count ? '' : ' hidden'; ?>><?php esc_html_e('Clear all', 'litsign'); ?></a>
				<button type="button" class="sc-icon-btn sc-filters-close" data-filters-close aria-label="<?php esc_attr_e('Close filters', 'litsign'); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
			</div>

			<div class="sc-filters-body">
				<div class="sc-search">
					<label class="visually-hidden" for="sc-q"><?php esc_html_e('Search products', 'litsign'); ?></label>
					<svg class="sc-search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
					<input type="search" id="sc-q" name="q" value="<?php echo esc_attr($state['q']); ?>" placeholder="<?php esc_attr_e('Search products…', 'litsign'); ?>" autocomplete="off" enterkeyhint="search">
				</div>

				<fieldset class="sc-group" data-group="cat">
					<legend class="sc-group-title"><?php esc_html_e('Category', 'litsign'); ?></legend>
					<?php wholesale_shop_render_category_tree($category_tree, $state['cat']); ?>
				</fieldset>

				<fieldset class="sc-group" data-group="price">
					<legend class="sc-group-title"><?php esc_html_e('Starting price', 'litsign'); ?></legend>
					<div class="sc-range" data-max="<?php echo esc_attr($price_ceiling); ?>">
						<div class="sc-range-track" aria-hidden="true"><span class="sc-range-fill"></span></div>
						<input type="range" class="sc-range-input" data-range="min" min="0" max="1000" step="1" value="0" aria-label="<?php esc_attr_e('Minimum price', 'litsign'); ?>" tabindex="-1" hidden>
						<input type="range" class="sc-range-input" data-range="max" min="0" max="1000" step="1" value="1000" aria-label="<?php esc_attr_e('Maximum price', 'litsign'); ?>" tabindex="-1" hidden>
					</div>
					<div class="sc-price-inputs">
						<label><span><?php esc_html_e('Min', 'litsign'); ?></span><span class="sc-money"><input type="number" inputmode="decimal" min="0" step="1" id="sc-price-min" name="pmin" value="<?php echo null !== $state['price_min'] ? esc_attr($state['price_min']) : ''; ?>" placeholder="0"></span></label>
						<span class="sc-price-sep" aria-hidden="true">–</span>
						<label><span><?php esc_html_e('Max', 'litsign'); ?></span><span class="sc-money"><input type="number" inputmode="decimal" min="0" step="1" id="sc-price-max" name="pmax" value="<?php echo null !== $state['price_max'] ? esc_attr($state['price_max']) : ''; ?>" placeholder="<?php echo esc_attr($price_ceiling); ?>"></span></label>
					</div>
					<div class="sc-presets">
						<?php foreach (array(array(0, 10), array(10, 50), array(50, 200), array(200, 0)) as $preset) : ?>
							<button type="button" class="sc-chip-btn" data-price-preset="<?php echo esc_attr($preset[0] . '-' . ($preset[1] ?: '')); ?>">
								<?php
								echo esc_html($preset[1]
									? ($preset[0] ? sprintf('$%d – $%d', $preset[0], $preset[1]) : sprintf(__('Under $%d', 'litsign'), $preset[1]))
									: sprintf(__('$%d & up', 'litsign'), $preset[0]));
								?>
							</button>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<fieldset class="sc-group" data-group="pricing">
					<legend class="sc-group-title"><?php esc_html_e('How it&rsquo;s priced', 'litsign'); ?></legend>
					<ul class="sc-tree">
						<?php foreach ($pricing_labels as $key => $label) : ?>
							<?php if (!$pricing_counts[$key]) { continue; } ?>
							<li class="sc-tree-item"><div class="sc-tree-row">
								<label class="sc-check" for="sc-pricing-<?php echo esc_attr($key); ?>">
									<input type="checkbox" id="sc-pricing-<?php echo esc_attr($key); ?>" name="pricing[]" value="<?php echo esc_attr($key); ?>"<?php checked(in_array($key, $state['pricing'], true)); ?>>
									<span class="sc-check-box" aria-hidden="true"></span>
									<span class="sc-check-label"><?php echo esc_html($label[0]); ?><small><?php echo esc_html($label[1]); ?></small></span>
									<span class="sc-count" data-count-for="pricing:<?php echo esc_attr($key); ?>"><?php echo (int) $pricing_counts[$key]; ?></span>
								</label>
							</div></li>
						<?php endforeach; ?>
					</ul>
				</fieldset>

				<?php if ($show_ratings) : ?>
					<fieldset class="sc-group" data-group="rating">
						<legend class="sc-group-title"><?php esc_html_e('Customer rating', 'litsign'); ?></legend>
						<ul class="sc-tree">
							<?php foreach ($rated as $stars => $count) : ?>
								<?php if (!$count) { continue; } ?>
								<li class="sc-tree-item"><div class="sc-tree-row">
									<label class="sc-check sc-check--radio" for="sc-rating-<?php echo esc_attr($stars); ?>">
										<input type="radio" id="sc-rating-<?php echo esc_attr($stars); ?>" name="rating" value="<?php echo esc_attr($stars); ?>"<?php checked($state['rating'], $stars); ?>>
										<span class="sc-check-box" aria-hidden="true"></span>
										<span class="sc-check-label"><?php echo wholesale_review_stars($stars, 'sc-stars'); // Escaped in helper. ?> <?php esc_html_e('& up', 'litsign'); ?></span>
										<span class="sc-count" data-count-for="rating:<?php echo esc_attr($stars); ?>"><?php echo (int) $count; ?></span>
									</label>
								</div></li>
							<?php endforeach; ?>
						</ul>
					</fieldset>
				<?php endif; ?>

				<noscript><button type="submit" class="sc-btn sc-btn--primary sc-btn--block"><?php esc_html_e('Apply filters', 'litsign'); ?></button></noscript>
			</div>

			<div class="sc-filters-foot">
				<a class="sc-btn sc-btn--ghost" href="<?php echo esc_url($shop_url); ?>#sc-results" data-clear-all><?php esc_html_e('Clear all', 'litsign'); ?></a>
				<button type="button" class="sc-btn sc-btn--primary" data-filters-close><span><?php esc_html_e('Show', 'litsign'); ?> <span data-result-count><?php echo (int) count($products); ?></span> <span data-result-noun><?php echo esc_html(_n('product', 'products', count($products), 'litsign')); ?></span></span></button>
			</div>
		</form>
		<div class="sc-backdrop" data-filters-close hidden></div>

		<section class="sc-main" aria-labelledby="sc-results-title">
			<div class="sc-toolbar">
				<h2 id="sc-results-title" class="sc-results-count" aria-live="polite">
					<span data-result-count><?php echo (int) count($products); ?></span>
					<span data-result-noun><?php echo esc_html(_n('product', 'products', count($products), 'litsign')); ?></span>
				</h2>
				<div class="sc-toolbar-actions">
					<button type="button" class="sc-btn sc-btn--outline sc-filters-open" aria-controls="sc-filters" aria-expanded="false">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
						<?php esc_html_e('Filters', 'litsign'); ?>
						<span class="sc-badge-count" data-active-count<?php echo $active_count ? '' : ' hidden'; ?>><?php echo (int) $active_count; ?></span>
					</button>
					<label class="sc-sort">
						<span class="sc-sort-label"><?php esc_html_e('Sort by', 'litsign'); ?></span>
						<select id="sc-sort" name="sort" form="sc-filters">
							<?php foreach ($sort_options as $key => $label) : ?>
								<option value="<?php echo esc_attr($key); ?>"<?php selected($state['sort'], $key); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="sc-view" role="group" aria-label="<?php esc_attr_e('Layout', 'litsign'); ?>">
						<button type="button" class="sc-icon-btn is-active" data-view="grid" aria-pressed="true" aria-label="<?php esc_attr_e('Grid view', 'litsign'); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></button>
						<button type="button" class="sc-icon-btn" data-view="list" aria-pressed="false" aria-label="<?php esc_attr_e('List view', 'litsign'); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="6" height="6" rx="1"/><rect x="3" y="14" width="6" height="6" rx="1"/><path d="M13 6h8M13 9h5M13 16h8M13 19h5"/></svg></button>
					</div>
				</div>
			</div>

			<ul class="sc-chips" data-chips aria-label="<?php esc_attr_e('Active filters', 'litsign'); ?>"<?php echo $active_count ? '' : ' hidden'; ?>></ul>

			<script>document.documentElement.classList.add('sc-js');</script>
			<ul class="sc-grid" data-grid data-page-size="<?php echo (int) $page_size; ?>">
				<?php
				// Cards that match the URL filters come first, in order; the rest
				// stay in the page (hidden) so the browser can filter without reloading.
				$shown_ids = wp_list_pluck($products, 'id');
				foreach ($products as $index => $product) {
					wholesale_shop_render_card($product, $index);
				}
				foreach ($all_products as $product) {
					if (!in_array($product['id'], $shown_ids, true)) {
						ob_start();
						wholesale_shop_render_card($product, 99);
						echo str_replace('<li class="sc-card"', '<li class="sc-card" hidden', ob_get_clean()); // Markup built and escaped above.
					}
				}
				?>
			</ul>

			<div class="sc-empty" data-empty<?php echo $products ? ' hidden' : ''; ?>>
				<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5M8.5 11h5"/></svg>
				<h3><?php esc_html_e('No products match those filters', 'litsign'); ?></h3>
				<p><?php esc_html_e('Try removing a filter, or tell us what you need — we make custom sizes and one-off signs every day.', 'litsign'); ?></p>
				<div class="sc-empty-actions">
					<a class="sc-btn sc-btn--primary" href="<?php echo esc_url($shop_url); ?>#sc-results" data-clear-all><?php esc_html_e('Clear all filters', 'litsign'); ?></a>
					<a class="sc-btn sc-btn--outline" href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Request a free quote', 'litsign'); ?></a>
				</div>
			</div>

			<div class="sc-more" data-more hidden>
				<p class="sc-more-status" data-more-status></p>
				<div class="sc-more-bar" aria-hidden="true"><span data-more-bar></span></div>
				<button type="button" class="sc-btn sc-btn--outline" data-more-btn><?php esc_html_e('Show more products', 'litsign'); ?></button>
			</div>
		</section>
	</div>

	<section class="sc-help" aria-labelledby="sc-help-title">
		<div class="container sc-help-inner">
			<div>
				<h2 id="sc-help-title"><?php esc_html_e('Not sure which sign is right?', 'litsign'); ?></h2>
				<p><?php esc_html_e('Send us your logo, a storefront photo or your measurements and a sign specialist will recommend the right product and give you a free quote.', 'litsign'); ?></p>
			</div>
			<div class="sc-help-actions">
				<a class="sc-btn sc-btn--light" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> 866-436-2101</a>
				<a class="sc-btn sc-btn--primary" href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Get a free quote', 'litsign'); ?></a>
			</div>
		</div>
	</section>

	<section class="sc-guide" aria-labelledby="sc-guide-title">
		<div class="container">
			<div class="sc-guide-grid">
				<div class="sc-guide-copy">
					<h2 id="sc-guide-title"><?php esc_html_e('Custom signs and printing for every part of your business', 'litsign'); ?></h2>
					<p><?php echo wp_kses_post(sprintf(
						__('Start with your building: <a href="%1$s">LED channel letters</a> are the most-requested storefront sign, and you can <a href="%2$s">design yours online</a> and see the price as you go. Then add signs at eye level — <a href="%3$s">window graphics</a>, <a href="%4$s">sidewalk A-frames</a> and <a href="%5$s">rigid signs</a> for doors, hours and parking.', 'litsign'),
						esc_url(wholesale_category_url('channel-letters')),
						esc_url($builder_url),
						esc_url(wholesale_category_url('adhesive-products')),
						esc_url(wholesale_category_url('signicade-a-frames')),
						esc_url(wholesale_category_url('rigid-signs-and-magnets'))
					)); ?></p>
					<p><?php echo wp_kses_post(sprintf(
						__('For openings, sales and events, choose from <a href="%1$s">vinyl, mesh and fabric banners</a>, <a href="%2$s">feather and teardrop flags</a>, <a href="%3$s">retractable banner stands</a>, <a href="%4$s">trade show displays</a> and <a href="%5$s">printed event tents</a>.', 'litsign'),
						esc_url(wholesale_category_url('banners')),
						esc_url(wholesale_category_url('advertising-flags')),
						esc_url(wholesale_category_url('banner-stands')),
						esc_url(wholesale_category_url('trade-show-products')),
						esc_url(wholesale_category_url('custom-event-tents'))
					)); ?></p>
				</div>
				<div class="sc-guide-links">
					<h3><?php esc_html_e('All categories', 'litsign'); ?></h3>
					<ul>
						<?php foreach ($category_tiles as $tile) : ?>
							<li><a href="<?php echo esc_url(wholesale_category_url($tile['term']->slug)); ?>"><?php echo esc_html($tile['term']->name); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="sc-faq-title">
		<div class="container">
			<p class="cl-kicker"><?php esc_html_e('Before you order', 'litsign'); ?></p>
			<h2 id="sc-faq-title" class="cl-section-title"><?php esc_html_e('Frequently Asked Questions', 'litsign'); ?></h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
