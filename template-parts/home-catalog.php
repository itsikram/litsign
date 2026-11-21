<?php
/**
 * Front page: the catalog hub, led by banners & displays.
 *
 * Loaded by home.php on the front page only; home.php's own listing still
 * serves the category pages. Channel letters and the banner and display lines
 * come first, then the full category directory, the ordering steps and the
 * company FAQ. Each category gets one priced card and one directory row, so
 * add new blocks with that in mind. Prices and photos are read from the
 * products, so they stay current; missing or unpublished products are skipped.
 *
 * The in-depth pages own their searches: /banners-displays/ (materials and
 * stand comparisons, buying guide) and /custom-channel-letters/ (styles,
 * heights, warranty). This page summarizes and links to them instead of
 * repeating their tables and FAQs.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$builder_url = home_url('/channel-letter-builder/');
$cl_landing_id = function_exists('retails_channel_letters_landing_id') ? retails_channel_letters_landing_id() : 0;
$cl_landing_url = $cl_landing_id ? get_permalink($cl_landing_id) : home_url('/custom-channel-letters/');
$bd_page = get_page_by_path('banners-displays');
$bd_url = $bd_page && 'publish' === $bd_page->post_status ? get_permalink($bd_page) : '';
$page_url = static function ($slug) {
	$page = get_page_by_path($slug);
	return $page && 'publish' === $page->post_status ? get_permalink($page) : '';
};
// The footer's next-day shipping note applies here (it stays off channel letter pages).
$GLOBALS['retails_channel_letter_landing'] = false;

// Card prices are whole dollars, except per-ft² prices (as on /banners-displays/):
// rounding $1.98 up to $2 would overstate the banner prices the ads quote.
$card_price = static function ($html) {
	return preg_match('/per ft/i', wp_strip_all_tags((string) $html)) ? $html : retails_price_no_cents($html);
};
// Without a leading "From" (sale prices read "From <del>$47.67</del> $42.90"):
// every price on this page already sits after "From" or "Starting at".
$price_of = static function ($slug) use ($card_price) {
	return $card_price(preg_replace('/^\s*from\s+/i', '', retails_seo_product_starting_text($slug)));
};
// A category card's price: the cheapest Starting at among the products its category
// page lists (retails_seo_category_from()), the same on every page that shows it.
$category_price = static function ($category) use ($card_price) {
	return $card_price(retails_seo_category_starting_text($category));
};

// Banners & displays product lines: category slug, product whose photo and price
// represent it, name, summary.
$bd_lines = array(
	array('banners', '13oz-vinyl-banner', 'Vinyl &amp; Fabric Banners', 'Any size in 13oz or 18oz vinyl, mesh or fabric, finished with grommets, pole pockets or hems.'),
	array('banner-stands', 'standard-retractable-insert-stand', 'Banner Stands', 'Retractable, X-stand and table top stands, shipped with your printed graphic.'),
	array('advertising-flags', 'feather-angled-flag-pole', 'Feather &amp; Teardrop Flags', 'Printed flags with poles and bases for roadsides, parking lots and events.'),
	array('trade-show-products', 'straight-tension-fabric-displays-graphic-frame', 'Trade Show Displays', 'Straight and curved fabric backwalls that pack down for the trip home.'),
	array('seg-products', '10ft-seg-fabric-display-graphic-frame', 'SEG Fabric Displays', 'Silicone edge graphics in aluminum frames, including backlit walls.'),
	array('custom-event-tents', 'event-tent-full-canopy-graphic-frame', 'Event Tents', 'Full color canopies with matching walls and tent flags.'),
	array('table-throws', '6ft-table-cover', 'Table Covers', 'Fitted, stretch and round covers plus runners for 4, 6 and 8 ft tables.'),
	array('a-frame-and-sign-holders', 'banner-a-banner-frame', 'A-Frames &amp; Poster Stands', 'Banner A-frames, poster stands and snap hangers for sidewalks and lobbies.'),
);

// Everyday banners & displays: product slug => short label. Products the hero and
// the line cards above already show are left out, so this grid adds new choices.
$everyday = array(
	'18oz-blockout-banner' => 'Seen from both sides',
	'mesh-banner' => 'Fences &amp; windy spots',
	'fabric-banner-9oz-wrinkle-free-copy' => 'Wrinkle-free indoor fabric',
	'x-stand-graphic-stand' => 'Lightest indoor stand',
	'table-top-banner-stand' => 'Counters &amp; check-in desks',
	'teardrop-flag-pole' => 'Curved roadside flag',
	'step-and-repeat-backdrop-graphic-frame' => 'Photo &amp; press backdrop',
	'stretch-table-cover' => 'Fitted booth table',
);
// Products whose only photos carry sample sale artwork (9oz fabric, X-stand) sit out
// until they have new photos; these fill their places (inc/product-photo-overrides.php).
if (function_exists('retails_card_picks')) {
	$everyday = retails_card_picks($everyday, array(
		'indoor-banner-super-smooth' => 'Crisp photos up close',
		'deluxe-retractable-insert-stand' => 'Frequent travel &amp; heavy use',
	));
}

// Channel letter styles, shown after the banners & displays grids: product slug => short name,
// lighting tag, how it lights, floating photo tags (label => color). Cards open the
// product page, where the customer enters the wording, height and colors and
// orders. Wording matches the style comparison on /custom-channel-letters/.
// "Best seller": front lit is the style customers order most (owner, 2026-11-05).
// The cheapest style also gets a "Lowest price" tag, worked out from the prices.
$cl_styles = array(
	'standard-channel-letter-front-lit' => array('Front Lit', 'Face lit', 'Bright, colored acrylic face. The easiest to read at night.', array('Best seller' => 'best')),
	'standard-channel-letter-back-lit' => array('Back Lit', 'Wall glow', 'Light glows onto the wall behind each letter.', array('Soft glow' => 'glow')),
	'standard-channel-letter-front-back-lit' => array('Front &amp; Back Lit', 'Dual lit', 'A lit face plus a halo on the wall for maximum presence.', array('Most visible' => 'blue')),
	'hidden-back-halo-lit' => array('Halo Lit', 'Halo', 'Solid metal face with a soft halo. Upscale and modern.', array('Premium look' => 'premium')),
	'halo-reverse-acrylic-lit-channel-letters' => array('Reverse Lit', 'Halo', 'Painted metal face with an exposed acrylic back that glows.', array('Glowing back' => 'glow')),
	'inset-acrylic-face-lit-with-border-no-trimcap' => array('Trimless with Border', 'Face lit', 'Inset acrylic face in a metal border, no plastic trimcap.', array('No trimcap' => 'dark')),
	'exposed-acrylic-face-lit-borderless-no-trimcap' => array('Borderless', 'Face lit', 'Exposed acrylic face with no trimcap or border. The sleekest look.', array('Sleekest' => 'premium', 'No trimcap' => 'dark')),
);

// Card photo galleries. Galleries often repeat the main photo as an upload copy
// ("photo-1.jpg") or an edited copy ("photo-e1733640963626.jpg"), so photos are
// compared by file name without size, edit or copy suffixes.
$photo_key = static function ($url) {
	$name = strtolower(pathinfo((string) wp_parse_url((string) $url, PHP_URL_PATH), PATHINFO_FILENAME));
	$name = preg_replace('/\.(png|jpe?g)$/', '', $name);
	$name = preg_replace('/-\d+x\d+$/', '', $name);
	$name = preg_replace('/-e\d{10,}$/', '', $name);
	return preg_replace('/-\d$/', '', $name);
};

// Whether two photos (array('id' => attachment ID or 0, 'url' => file URL)) are
// the same picture. retails_card_same_photo() (inc/shop-catalog.php) keeps
// "banner-2.jpg" apart from "banner-1.jpg" unless the files are byte copies; the
// file-name key above is only the fallback, as it merges every "-1", "-2", "-3".
$same_photo = static function (array $a, array $b) use ($photo_key) {
	if (function_exists('retails_card_same_photo')) {
		return retails_card_same_photo($a, $b);
	}
	return ($a['id'] && $a['id'] === $b['id']) || $photo_key($a['url']) === $photo_key($b['url']);
};

// <img> tags for a list of photos: array('id' => attachment ID or 0, 'url' => file
// URL when there is no ID, 'alt' => fallback alt text). Library files get srcset.
$photo_tags = static function (array $photos, $sizes) {
	$html = array();
	foreach ($photos as $position => $photo) {
		$src = $photo['id'] ? (string) wp_get_attachment_image_url($photo['id'], 'full') : $photo['url'];
		$alt = function_exists('retails_product_photo_alt') ? retails_product_photo_alt($src, '') : '';
		$alt = $alt ? $alt : $photo['alt'];
		if ($photo['id']) {
			$img = wp_get_attachment_image($photo['id'], 'medium_large', false, array(
				'class' => 'hc-gal-photo',
				'loading' => 'lazy',
				'decoding' => 'async',
				'sizes' => $sizes,
				'alt' => $alt,
			));
		} else {
			$img = sprintf('<img class="hc-gal-photo" src="%s" alt="%s" loading="lazy" decoding="async">', esc_url(retails_webp_url($photo['url'])), esc_attr($alt));
		}
		// Only the first photo loads with the page. Browsers fetch lazy images in a
		// side-scrolling card as if they were on screen, so the others wait in data
		// attributes until the visitor reaches the card (warm() in the script below).
		// The first photo waits too, but only until the page has loaded, the visitor
		// scrolls or touches, or it is on screen (release() in the script below), so
		// photos below the first screen do not slow down the hero photo.
		if ($img) {
			$img = preg_replace('/\s(srcset|src)="/', ' data-hc-$1="', $img);
			$img = str_replace('<img ', '<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" ' . (0 === $position ? 'data-hc-first ' : ''), $img);
		}
		if ($img) {
			$html[] = $img;
		}
	}
	return $html;
};

// A product's photo, then its gallery (as on the product page), at most $max.
$gallery_ids = array();
$card_photos = static function ($product, $name, $sizes, $max = 6) use ($same_photo, $photo_tags, &$gallery_ids) {
	if (!$product) {
		return array();
	}
	$featured = has_post_thumbnail($product) ? (int) get_post_thumbnail_id($product) : 0;
	$gallery = get_post_meta($product->ID, '_product_gallery', true);
	$gallery = is_array($gallery) ? array_values(array_filter($gallery, 'is_string')) : array();
	$missing = array_diff($gallery, array_keys($gallery_ids));
	if ($missing && function_exists('retails_product_image_alt_attachment_ids_by_url')) {
		$gallery_ids += retails_product_image_alt_attachment_ids_by_url($missing);
	}
	$ids = $gallery_ids;

	$photos = array();
	if ($featured) {
		$photos[] = array('id' => $featured, 'url' => (string) wp_get_attachment_url($featured), 'alt' => $name);
	}
	foreach ($gallery as $url) {
		if (count($photos) >= $max) {
			break;
		}
		$photo = array('id' => !empty($ids[$url]) ? (int) $ids[$url] : 0, 'url' => $url, 'alt' => sprintf('%s, photo %d', $name, count($photos) + 1));
		foreach ($photos as $shown) {
			if ($same_photo($shown, $photo)) {
				continue 2;
			}
		}
		$photos[] = $photo;
	}

	return $photo_tags($photos, $sizes);
};

// Published products of each line above, in display order: category slug => array of
// product ID => thumbnail ID. A category includes its child categories. One query
// for all lines, instead of three per line card.
$line_products = array();
$line_terms = get_terms(array(
	'taxonomy' => 'product_category',
	'slug' => array_values(array_unique(array_column($bd_lines, 0))),
	'hide_empty' => false,
));
$term_lines = array();
foreach (is_array($line_terms) ? $line_terms : array() as $line_term) {
	foreach (array_merge(array($line_term->term_id), (array) get_term_children($line_term->term_id, 'product_category')) as $term_id) {
		$term_lines[(int) $term_id][] = $line_term->slug;
	}
}
if ($term_lines) {
	global $wpdb;
	$line_rows = $wpdb->get_results(
		"SELECT p.ID, tt.term_id, m.meta_value AS thumbnail
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id'
		WHERE p.post_type = 'product' AND p.post_status = 'publish'
			AND tt.term_id IN (" . implode(',', array_map('intval', array_keys($term_lines))) . ')
		ORDER BY p.menu_order ASC, p.post_date DESC'
	);
	foreach ($line_rows as $row) {
		foreach ($term_lines[(int) $row->term_id] as $line_slug) {
			$line_products[$line_slug][(int) $row->ID] = (int) $row->thumbnail;
		}
	}
}

// A product line: the representative product's photo, then the main photos of the
// other products in that category, at most $max, so the card previews the range.
$category_photos = static function ($category, $product, $sizes, $max = 5) use ($same_photo, $photo_tags, &$line_products) {
	$photos = array();
	$add = static function ($item) use (&$photos, $same_photo) {
		// Products whose only photos carry sample sale artwork add none (inc/product-photo-overrides.php).
		if ($item && function_exists('retails_product_card_hidden') && retails_product_card_hidden($item)) {
			return;
		}
		$id = $item && has_post_thumbnail($item) ? (int) get_post_thumbnail_id($item) : 0;
		if (!$id) {
			return;
		}
		$photo = array('id' => $id, 'url' => (string) wp_get_attachment_url($id), 'alt' => wp_strip_all_tags(get_the_title($item)));
		foreach ($photos as $shown) {
			if ($same_photo($shown, $photo)) {
				return;
			}
		}
		$photos[] = $photo;
	};
	$add($product);
	// The next 12 products in the line that have a photo (see $line_products).
	$line = isset($line_products[$category]) ? $line_products[$category] : array();
	$others = array();
	foreach ($line as $id => $thumbnail) {
		if ($thumbnail && (!$product || $id !== $product->ID)) {
			$others[] = get_post($id);
			if (12 === count($others)) {
				break;
			}
		}
	}
	foreach ($others as $other) {
		if (count($photos) >= $max) {
			break;
		}
		$add($other);
	}

	// Published products in the line (the stored term count can be stale).
	$count = count($line);

	return array('photos' => $photo_tags($photos, $sizes), 'count' => $count);
};

// Times each product was ordered (orders not in the trash), cached for 12 hours.
// Drives the "Best seller" and "Popular" tags, so they follow real orders.
$sales = get_transient('retails_home_product_sales');
if (!is_array($sales)) {
	global $wpdb;
	$sales = array();
	$order_items = $wpdb->get_col("SELECT m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = 'product_json' AND p.post_type = 'order' AND p.post_status NOT IN ('trash', 'auto-draft', 'draft')");
	foreach ($order_items as $json) {
		$items = json_decode((string) $json, true);
		foreach (is_array($items) ? $items : array() as $item) {
			$pid = isset($item['product_id']) ? (int) $item['product_id'] : 0;
			if ($pid > 0) {
				$sales[$pid] = (isset($sales[$pid]) ? $sales[$pid] : 0) + 1;
			}
		}
	}
	set_transient('retails_home_product_sales', $sales, 12 * HOUR_IN_SECONDS);
}
// Orders across a product line. A product listed in two lines on this page counts
// only toward the first card, so one product's orders don't earn two cards a tag.
$counted_products = array();
$category_sales = static function ($category) use ($sales, &$counted_products, &$line_products) {
	if (!$sales) {
		return 0;
	}
	$ids = array_keys(array_intersect_key(isset($line_products[$category]) ? $line_products[$category] : array(), $sales));
	$ids = array_diff($ids, $counted_products);
	$counted_products = array_merge($counted_products, $ids);
	return array_sum(array_intersect_key($sales, array_flip($ids)));
};

// Lowest effective per-inch rate for each channel letter style.
$cl_card_price = static function ($slug) {
	$product = retails_seo_product($slug);
	$from = $product && function_exists('retails_cl_from_inch_rate') ? retails_cl_from_inch_rate($product->ID) : null;
	if (!$from || $from['price'] <= 0) {
		return null;
	}
	$now = '$' . number_format($from['price'], 2);
	$was = '$' . number_format($from['everyday'], 2);

	return array(
		'now' => $now,
		'was' => $was !== $now && $from['everyday'] > $from['price'] ? $was : '',
		'height' => (int) $from['height'],
		'amount' => (float) $from['price'],
	);
};

// Product card tags, from real data, at most two: a dated sale ("10% off"), then
// "Best seller" / "Popular" from order counts, then "Under $50" for low per-item
// prices or "Any size" for per-ft² products.
$card_tags = static function ($product, $price_html, $orders) {
	$tags = array();
	$promo = $product && function_exists('retails_product_promotion_percent') ? (float) retails_product_promotion_percent($product->ID) : 0;
	if ($promo > 0) {
		$tags[round($promo) . '% off'] = 'sale';
	}
	if ($orders >= 5) {
		$tags['Best seller'] = 'best';
	} elseif ($orders >= 2) {
		$tags['Popular'] = 'blue';
	}
	$plain = wp_strip_all_tags(preg_replace('#<del\b.*?</del>#is', '', (string) $price_html));
	if (preg_match('/per ft/i', $plain)) {
		$tags['Any size'] = 'dark';
	} elseif (preg_match('/\$\s?([\d,]+(?:\.\d+)?)/', $plain, $amount) && (float) str_replace(',', '', $amount[1]) < 50) {
		$tags['Under $50'] = 'price';
	}
	return array_slice($tags, 0, 2, true);
};

// Turnaround line from the product's supplier notes ("Same-day turnaround available").
$card_turnaround = static function ($product) {
	if (!$product || !function_exists('retails_product_turnaround')) {
		return '';
	}
	// The product's fastest real production-time choice (inc/card-trust.php, 2026-11-21):
	// retails_product_turnaround() alone said "next day" for 7-day-only products.
	if (function_exists('retails_card_trust_turnaround')) {
		$fast = retails_card_trust_turnaround($product->ID);
		$map = array('Ships same day' => 'In stock, ships same day', 'Same-day available' => 'Same-day turnaround available');
		return isset($map[$fast]) ? $map[$fast] : $fast;
	}
	$t = retails_product_turnaround($product->ID);
	if ($t['stock'] && $t['same_day']) {
		return 'In stock, ships same day';
	}
	if ($t['same_day']) {
		return 'Same-day turnaround available';
	}
	return 1 === (int) $t['next_days'] ? 'Next-day turnaround' : sprintf('Ships in %d business days', (int) $t['next_days']);
};

// One product card for the light grids: photo gallery, tags, name, details, price
// and a button. $card: url, name (HTML), photos (<img> list), tags, chip (top right),
// kicker, desc, meta, price (HTML), button, button_short (optional label for narrow cards).
$render_card = static function (array $card) {
	$photos = $card['photos'];
	$count = count($photos);
	$name = wp_strip_all_tags(html_entity_decode($card['name'], ENT_QUOTES, 'UTF-8'));
	?>
	<article class="hc-pcard">
		<div class="hc-pcard-media hc-gal"<?php echo $count > 1 ? ' data-hc-gallery' : ''; ?>>
			<a class="hc-gal-track" href="<?php echo esc_url($card['url']); ?>" tabindex="-1" aria-hidden="true">
				<?php foreach ($photos as $photo) : ?>
					<span class="hc-gal-slide"><?php echo $photo; ?></span>
				<?php endforeach; ?>
			</a>
			<?php if (!empty($card['tags'])) : ?>
				<span class="hc-gal-flags hc-gal-flags--left">
					<?php foreach ($card['tags'] as $label => $color) : ?>
						<span class="hc-gal-flag hc-gal-flag--<?php echo esc_attr($color); ?>"><?php if ('best' === $color) : ?><svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 20.9l1.6-7L2 9.2l7.1-.6z"/></svg><?php endif; ?><?php echo esc_html($label); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
			<?php if (!empty($card['chip'])) : ?><span class="hc-pcard-chip"><?php echo esc_html($card['chip']); ?></span><?php endif; ?>
			<?php if ($count > 1) : ?>
				<button type="button" class="hc-gal-arrow hc-gal-arrow--prev" data-hc-prev aria-label="Previous <?php echo esc_attr($name); ?> photo"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
				<button type="button" class="hc-gal-arrow hc-gal-arrow--next" data-hc-next aria-label="Next <?php echo esc_attr($name); ?> photo"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
				<span class="hc-gal-dots" aria-hidden="true"><?php for ($dot = 0; $dot < $count; $dot++) : ?><span<?php echo 0 === $dot ? ' class="is-active"' : ''; ?>></span><?php endfor; ?></span>
				<span class="hc-gal-count" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg><span data-hc-index>1</span>/<?php echo (int) $count; ?></span>
			<?php endif; ?>
		</div>
		<a class="hc-pcard-body" href="<?php echo esc_url($card['url']); ?>">
			<?php if (!empty($card['kicker'])) : ?><span class="hc-pcard-kicker"><?php echo wp_kses_post($card['kicker']); ?></span><?php endif; ?>
			<h3 class="hc-pcard-name"><?php echo wp_kses_post($card['name']); ?></h3>
			<?php if (!empty($card['desc'])) : ?><span class="hc-pcard-desc"><?php echo wp_kses_post($card['desc']); ?></span><?php endif; ?>
			<?php if (!empty($card['meta'])) : ?><span class="hc-pcard-meta"><?php echo retails_home_icon('clock'); ?> <?php
					// Phones show a shorter wording so the line never wraps (css/home-catalog.css).
					$meta_short = str_ireplace(array('Same-day turnaround available', 'In stock, ships same day', ' business days', ' business day'), array('Same-day available', 'Ships same day', ' days', ' day'), (string) $card['meta']);
					if ($meta_short !== (string) $card['meta']) :
						?><span class="hc-meta-long"><?php echo esc_html($card['meta']); ?></span><span class="hc-meta-short" aria-hidden="true"><?php echo esc_html($meta_short); ?></span><?php
					else :
						echo esc_html($card['meta']);
					endif; ?></span><?php endif; ?>
			<?php // Shipping pill only: the clock line above already gives the production time. ?>
			<?php echo !empty($card['trust']) ? $card['trust'] : ''; // Built by retails_card_trust(), escaped there. ?>
			<span class="hc-pcard-foot">
				<?php if (!empty($card['price'])) : ?>
					<span class="hc-pcard-price"><small>From</small> <?php echo wp_kses_post(function_exists('retails_emphasize_price') ? retails_emphasize_price($card['price']) : $card['price']); ?></span>
				<?php endif; ?>
				<?php // Narrow cards show button_short (when given) so the label never runs past the button; see css/home-catalog.css. ?>
				<span class="hc-pcard-button"><span class="hc-pcard-button-text<?php echo !empty($card['button_short']) ? ' hc-pcard-button-long' : ''; ?>"><?php echo esc_html($card['button']); ?></span><?php if (!empty($card['button_short'])) : ?><span class="hc-pcard-button-text hc-pcard-button-short" aria-hidden="true"><?php echo esc_html($card['button_short']); ?></span><?php endif; ?> <?php echo retails_home_icon('arrow'); ?></span>
			</span>
		</a>
	</article>
	<?php
};

// A product line card (category): photos from across the line, product count.
$line_card = static function ($line) use ($category_price, $category_photos, $card_tags, $card_turnaround, $category_sales) {
	$product = retails_seo_product($line[1]);
	$price = $category_price($line[0]);
	// The sale tag follows the product whose price the card shows.
	$from = retails_seo_category_from($line[0]);
	$range = $category_photos($line[0], $product, '(max-width: 575px) 46vw, (max-width: 991px) 46vw, 280px');
	return array(
		'url' => retails_category_url($line[0]),
		'name' => $line[2],
		'photos' => $range['photos'],
		'tags' => $card_tags($from ? get_post($from['product_id']) : $product, $price, $category_sales($line[0])),
		'chip' => $range['count'] > 1 ? sprintf('%d products', $range['count']) : '',
		'desc' => $line[3],
		'meta' => $card_turnaround($product),
		'trust' => function_exists('retails_card_trust') ? retails_card_trust($product ? $product->ID : 0, 'ship') : '',
		'price' => $price,
		'button' => 'Explore Options',
		'button_short' => 'Shop Now',
	);
};

// One query for every product this page shows (instead of one lookup per slug).
$directory = retails_home_catalog_directory();
retails_seo_prime_products(array_merge(
	array_column($bd_lines, 1),
	array_keys($everyday),
	array_keys($cl_styles),
	array_column($directory['featured'], 'product')
));

// Load everything the cards read in one go: the products and photos in each line
// card (at most 13 per line), and the gallery photos of the single-product cards.
$prime_posts = array();
$prime_photos = array();
foreach ($line_products as $line) {
	foreach (array_slice($line, 0, 13, true) as $product_id => $thumbnail) {
		$prime_posts[] = $product_id;
		$prime_photos[] = $thumbnail;
	}
}
$gallery_urls = array();
foreach (array_merge(array_keys($everyday), array_keys($cl_styles)) as $slug) {
	$card_product = retails_seo_product($slug);
	$card_gallery = $card_product ? get_post_meta($card_product->ID, '_product_gallery', true) : array();
	foreach (is_array($card_gallery) ? $card_gallery : array() as $url) {
		if (is_string($url)) {
			$gallery_urls[] = $url;
		}
	}
}
if ($gallery_urls && function_exists('retails_product_image_alt_attachment_ids_by_url')) {
	$gallery_ids = retails_product_image_alt_attachment_ids_by_url(array_values(array_unique($gallery_urls)));
	$prime_photos = array_merge($prime_photos, array_values($gallery_ids));
}
_prime_post_caches(array_values(array_unique($prime_posts)), false, true);
_prime_post_caches(array_values(array_unique(array_filter(array_map('intval', $prime_photos)))), false, true);

$free_shipping = function_exists('retails_free_shipping_amount') ? retails_free_shipping_amount() : '';
$free_shipping_cl = function_exists('retails_free_shipping_amount') ? retails_free_shipping_amount(true) : '';

// The everyday products are this page's item list in structured data.
$GLOBALS['retails_page_items'] = array_values(array_filter(array_map(static function ($slug) {
	return retails_seo_product($slug);
}, array_keys($everyday))));

$home_link = static function ($url, $text) {
	return $url ? '<a href="' . esc_url($url) . '">' . $text . '</a>' : $text;
};
$industries_url = $page_url('industries');
$locations_url = $page_url('locations');
$install_url = $page_url('how-to-install-channel-letters');

// Company and catalog questions. Banner and stand details are answered on
// /banners-displays/, channel letter details on /custom-channel-letters/.
retails_seo_set_page_faq(array_values(array_filter(array(
	array(
		'q' => 'What does Storefront Sign Online make?',
		'a' => 'Printed ' . $home_link(retails_category_url('banners'), 'banners') . ', ' . $home_link(retails_category_url('banner-stands'), 'banner stands') . ', ' . $home_link(retails_category_url('advertising-flags'), 'flags') . ', ' . $home_link(retails_category_url('trade-show-products'), 'trade show displays') . ', event tents and table covers, plus ' . $home_link(retails_category_url('adhesive-products'), 'window graphics') . ', sidewalk A-frames, rigid and reflective signs, and LED ' . $home_link($cl_landing_url, 'channel letters') . '. ' . $home_link(retails_shop_url(), 'Browse every product') . '.',
	),
	array(
		'q' => 'How do I see what my order will cost?',
		'a' => 'Open any product and choose a size and options; your price updates before you add it to the cart. Banners and vinyl are priced by the square foot, stands, flags and displays come as a set with the printed graphic, and channel letter starting rates are shown per inch of letter height. For a large or mixed order, ' . $home_link($contact_url, 'request a free quote') . '.',
	),
	array(
		'q' => 'How quickly will my order arrive?',
		'a' => 'Many banners, displays and printed products have next-day turnaround when you order and send artwork by 4pm PST, and some can ship the same day when artwork is in by 12pm PST. Each product shows its turnaround, and you choose standard (3&ndash;6 business days), 3-day, 2-day or overnight shipping at checkout. Channel letters are built to order, with the estimated ship date shown at checkout.',
	),
	$free_shipping ? array(
		'q' => 'Do you offer free shipping?',
		'a' => 'Yes. Orders of ' . esc_html($free_shipping) . ' or more can choose free shipping (3&ndash;6 business days) at checkout' . ($free_shipping_cl ? '; orders that include channel letters qualify at ' . esc_html($free_shipping_cl) : '') . '.',
	) : null,
	array(
		'q' => 'Where are your products made, and where do you ship?',
		'a' => 'Banners, displays, window graphics and our other printed products are made in Renton, WA, and channel letters are built in Washington and California. Everything ships to all 50 states. Pickup isn&rsquo;t available.',
	),
	array(
		'q' => 'Can I order just the graphic for a stand or flag I already own?',
		'a' => 'Yes. Most banner stands, flags, A-frames and fabric displays are also sold as the printed graphic only, and poles, bases, frames and stands without graphics are in ' . $home_link(retails_category_url('hardware-only'), 'display hardware') . '.',
	),
	array(
		'q' => 'Can you help with my artwork or logo?',
		'a' => 'Yes. Upload your logo with a ' . $home_link($contact_url, 'quote request') . ' or text it to <a href="sms:+12066186543">206-618-6543</a>, and a specialist will help with sizing, materials and artwork, usually within one business day.',
	),
	array(
		'q' => 'Do you install signs?',
		'a' => 'No. We make and ship. Banners and displays are ready to hang or set up, and every lit sign ships tested with a wiring diagram and install pattern for your licensed electrician or sign installer.' . ($install_url ? ' Our ' . $home_link($install_url, 'channel letter installation guide') . ' explains what they will do.' : ''),
	),
	($industries_url || $locations_url) ? array(
		'q' => 'Do you make products for my type of business?',
		'a' => 'We supply retail stores, restaurants, salons, medical offices, real estate agents, event planners and trade show exhibitors across the country.' . ($industries_url ? ' Our ' . $home_link($industries_url, 'industry guides') . ' suggest what to order for your business' : '') . ($locations_url ? ($industries_url ? ', and our ' : ' Our ') . $home_link($locations_url, 'location guides') . ' cover local sign rules' : '') . '.',
	) : null,
))));

// Set both URLs before get_header() so the head can preload the matching image.
$hero_image = get_template_directory_uri() . '/img/home-hero-desktop.webp';
$hero_mobile_image = get_template_directory_uri() . '/img/home-hero-mobile.webp';
$hero_mobile_avif = file_exists(get_template_directory() . '/img/home-hero-mobile.avif') ? get_template_directory_uri() . '/img/home-hero-mobile.avif' : '';
$hero_mobile_position = '';

// Phones: the photo chosen under Storefront Sign > Images > "Home page hero on phones"
// (Media Library) replaces the built-in one (owner, Nov 21 2026). Its WebP copy is made on
// first use and its AVIF copy by a background job; until that exists phones get the WebP.
$hero_mobile_id = function_exists('retails_image_slot_id') ? retails_image_slot_id('home_hero_mobile') : 0;
$hero_mobile_src = $hero_mobile_id ? (string) wp_get_attachment_image_url($hero_mobile_id, '1536x1536') : '';
if ($hero_mobile_src) {
	$hero_mobile_image = function_exists('retails_webp_url') ? retails_webp_url($hero_mobile_src) : $hero_mobile_src;
	$hero_mobile_avif = function_exists('retails_hero_avif_url') ? retails_hero_avif_url($hero_mobile_src) : '';
	// The built-in photo's subject sits low and right; a library photo is framed on its centre.
	$hero_mobile_position = '50% 50%';
}

$GLOBALS['retails_hero_image'] = $hero_image;
$GLOBALS['retails_hero_mobile_image'] = $hero_mobile_image;
$hero_background = "--hc-hero-desktop: url('" . esc_url($hero_image) . "'); --hc-hero-mobile: url('" . esc_url($hero_mobile_image) . "');";
if ($hero_mobile_position) {
	$hero_background .= ' --hc-hero-mobile-pos: ' . $hero_mobile_position . ';';
}

// AVIF copies are about a third of the size. css/home-catalog.css uses the sets only in
// browsers that understand them; the head preloads the AVIF, which other browsers skip.
if (file_exists(get_template_directory() . '/img/home-hero-desktop.avif')) {
	$hero_image_avif = get_template_directory_uri() . '/img/home-hero-desktop.avif';
	$GLOBALS['retails_hero_image_avif'] = $hero_image_avif;
	$hero_background .= " --hc-hero-desktop-set: image-set(url('" . esc_url($hero_image_avif) . "') type('image/avif'), url('" . esc_url($hero_image) . "') type('image/webp'));";
}
if ($hero_mobile_avif) {
	$GLOBALS['retails_hero_mobile_avif'] = $hero_mobile_avif;
	$hero_background .= " --hc-hero-mobile-set: image-set(url('" . esc_url($hero_mobile_avif) . "') type('image/avif'), url('" . esc_url($hero_mobile_image) . "') type('image/webp'));";
}

// Keep the banners & displays photo for share previews and structured data.
$share_image = $bd_page ? get_the_post_thumbnail_url($bd_page, 'large') : '';
if ($share_image) {
	$GLOBALS['retails_page_image'] = $share_image;
}
add_filter('retails_seo_webpage_schema', static function ($page) use ($share_image) {
	$organization_id = trailingslashit(home_url('/')) . '#organization';
	$page['about'] = array('@id' => $organization_id);
	if (defined('RETAILS_HOME_PAGE_REVISED')) {
		$page['dateModified'] = RETAILS_HOME_PAGE_REVISED;
	}
	if ($share_image) {
		$page['primaryImageOfPage'] = array('@type' => 'ImageObject', 'url' => $share_image);
	}
	return $page;
});

get_header();

$google_reviews = function_exists('retails_google_reviews') ? retails_google_reviews() : null;
$google_rating = $google_reviews && !empty($google_reviews['count']) ? $google_reviews : null;

// First-screen product shortcuts (Visitor Insights, Nov 8 2026: 426 of 451 ad visits
// landed here on phones, 63% left within 10 s and only 4% opened a product; the hero
// was text only). Photo, name and starting price, each opening its category.
$hero_picks = array();
$pick_slugs = array_column($bd_lines, 1, 0); // category => product whose photo stands for it
// Each card shows two photos side by side: the category's main product, then a
// second product from the same line (owner, Nov 9 2026: two photos per card).
// No photos with sale artwork (18oz blockout "BIG SALE", X-stand "50%" pizza, poster
// stand "Summer Discount"), and products on the card-hidden list
// (inc/product-photo-overrides.php) are skipped.
$pick_seconds = array(
	'banners' => 'mesh-banner',
	'banner-stands' => 'table-top-banner-stand',
	'a-frame-and-sign-holders' => 'snap-poster-graphic-hanger',
	'channel-letters' => 'hidden-back-halo-lit',
);
$pick_photos = static function ($products) {
	$urls = array();
	foreach ($products as $product) {
		if ($product && function_exists('retails_product_card_hidden') && retails_product_card_hidden($product)) {
			continue;
		}
		$id = $product ? (int) get_post_thumbnail_id($product) : 0;
		$medium = $id ? wp_get_attachment_image_src($id, 'medium') : false;
		if ($medium) {
			$thumb = wp_get_attachment_image_src($id, 'thumbnail');
			$urls[] = array(
				'src' => $medium[0],
				'width' => (int) $medium[1],
				'thumb' => $thumb && $thumb[0] !== $medium[0] ? $thumb[0] : '',
			);
		}
	}
	return $urls;
};
foreach (array('banners' => 'Vinyl Banners', 'banner-stands' => 'Banner Stands', 'a-frame-and-sign-holders' => 'A-Frames & Holders') as $pick_category => $pick_name) {
	$pick_product = isset($pick_slugs[$pick_category]) ? retails_seo_product($pick_slugs[$pick_category]) : null;
	// Sale price only (no struck-through price); "$42" for "$42 per item", "$1.38/ft²" for per ft².
	$pick_price = $category_price($pick_category);
	$pick_price = strip_tags(preg_replace('#<del\b.*?</del>#is', '', (string) $pick_price), '<sup>');
	$pick_price = trim(preg_replace(array('/\s*per item\s*$/i', '/\s*per ft/i'), array('', '/ft'), $pick_price));
	if ($pick_product && '' !== $pick_price) {
		$hero_picks[] = array(
			'url' => retails_category_url($pick_category),
			'name' => $pick_name,
			'price' => $pick_price,
			'photos' => $pick_photos(array($pick_product, retails_seo_product($pick_seconds[$pick_category]))),
		);
	}
}
// Channel letters: use the same lowest in-stock per-inch price as the category card.
$pick_cl_from = retails_category_card_from('channel-letters');
$pick_cl_product = retails_seo_product(array_key_first($cl_styles));
if ($pick_cl_from && $pick_cl_product) {
	$hero_picks[] = array(
		'url' => '#channel-letters',
		'name' => 'Channel Letters',
		'price' => implode('', $pick_cl_from),
		'photos' => $pick_photos(array($pick_cl_product, retails_seo_product($pick_seconds['channel-letters']))),
	);
}
?>

<main id="primary" class="site-main hc-page">
	<script>
	/* Sections far below the first screen skip layout until they come near
	   (content-visibility in css/home-catalog.css). Lay them out 1,250px early, so
	   their photos load as before, and all at once before an in-page jump, so
	   #channel-letters and the other jumps land on the exact spot. A reload or Back
	   restores the scroll position, so those load with every section laid out. */
	(function (root) {
		var off = function () { root.classList.add('hc-cv-off'); };
		var nav = window.performance && performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
		if (location.hash || !('IntersectionObserver' in window) || (nav && (nav.type === 'reload' || nav.type === 'back_forward'))) {
			off();
			return;
		}
		document.addEventListener('click', function (e) {
			var a = e.target.closest ? e.target.closest('a[href*="#"]') : null;
			if (a && a.hash && a.pathname === location.pathname && a.hostname === location.hostname) {
				off();
			}
		}, true);
		window.addEventListener('hashchange', off);
		document.addEventListener('DOMContentLoaded', function () {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('hc-cv-near');
						io.unobserve(entry.target);
					}
				});
			}, { rootMargin: '1250px 0px' });
			var sections = [].slice.call(document.querySelectorAll('.hc-page > section:not(.hc-hero)'));
			sections.forEach(function (section) {
				io.observe(section);
			});
			// Once the page has loaded, lay out the rest one section per idle moment
			// (no long task), so no section keeps its 1,500px placeholder height.
			window.addEventListener('load', function () {
				var idle = window.requestIdleCallback || function (fn) { return setTimeout(fn, 50); };
				var next = function () {
					var section = sections.shift();
					while (section && section.classList.contains('hc-cv-near')) {
						section = sections.shift();
					}
					if (section) {
						section.classList.add('hc-cv-near');
						io.unobserve(section);
						idle(next, { timeout: 1000 });
					}
				};
				idle(next, { timeout: 1000 });
			});
		});
	})(document.documentElement);
	</script>
	<section class="bd-hero hc-hero" aria-labelledby="home-hero-title" style="<?php echo esc_attr($hero_background); ?>">
		<div class="container">
			<div class="hc-hero-copy">
				<p class="hc-eyebrow">Made to order &middot; Since 2002</p>
				<h1 id="home-hero-title" class="bd-hero-title">Custom Banners, Displays &amp; Signs <span>for Your Business</span></h1>
				<p class="home-hero-lead">From trade show displays to LED storefront signs, choose your size and options to see your price online.</p>
				<?php if (count($hero_picks) >= 2) : ?>
					<nav class="hc-hero-picks" aria-label="Popular products">
						<?php foreach ($hero_picks as $pick) : ?>
							<a class="hc-hero-pick" href="<?php echo esc_url($pick['url']); ?>">
								<?php if ($pick['photos']) : ?>
									<span class="hc-hero-pick-photos" data-count="<?php echo esc_attr(count($pick['photos'])); ?>" aria-hidden="true">
										<?php
										// Shown at 72-93px. The 150px copy is enough up to about 1.75x screens
										// (PageSpeed's phone, desktops); sharper screens still pick the 300px one.
										foreach ($pick['photos'] as $pick_photo) :
											$pick_src = retails_webp_url($pick_photo['src']);
											$pick_srcset = $pick_photo['thumb'] ? retails_webp_url($pick_photo['thumb']) . ' 150w, ' . $pick_src . ' ' . $pick_photo['width'] . 'w' : '';
											?><img src="<?php echo esc_url($pick_src); ?>"<?php if ($pick_srcset) : ?> srcset="<?php echo esc_attr($pick_srcset); ?>" sizes="(max-width: 991px) 85px, 72px"<?php endif; ?> alt="" width="150" height="150" decoding="async" fetchpriority="low"><?php
										endforeach;
										?>
									</span>
								<?php endif; ?>
								<span class="hc-hero-pick-text"><strong><?php echo esc_html($pick['name']); ?></strong><small>From <?php echo wp_kses($pick['price'], array('sup' => array())); ?></small><?php echo retails_home_icon('arrow'); ?></span>
							</a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
				<div class="home-hero-actions">
					<a class="home-hero-shop-button" href="<?php echo esc_url(retails_shop_url()); ?>">Shop All Products <?php echo retails_home_icon('arrow'); ?></a>
					<a class="home-hero-secondary-button" href="<?php echo esc_url($contact_url); ?>">Get a Free Quote</a>
				</div>
				<div class="hc-hero-meta">
					<?php if ($google_rating) : ?>
						<a class="home-hero-rating" href="#customer-reviews"><?php echo retails_review_stars($google_rating['rating']); ?> <strong><?php echo esc_html(number_format((float) $google_rating['rating'], 1)); ?></strong> on Google (<?php echo esc_html(number_format_i18n((int) $google_rating['count'])); ?>)</a>
					<?php endif; ?>
					<a class="hc-hero-phone" href="tel:+18664362101"><?php echo retails_home_icon('phone'); ?> Talk to a specialist</a>
				</div>
			</div>
		</div>
	</section>

	<div class="home-hero-features bd-features hc-benefits" aria-label="Why order from us">
		<div class="container home-hero-feature-grid">
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo retails_home_icon('check'); ?></span>
				<span><strong>Online Pricing</strong><small>Enter a size for your price.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo retails_home_icon('flag'); ?></span>
				<?php if ($free_shipping) : ?>
					<span><strong>Free Shipping</strong><small>Orders over <?php echo esc_html($free_shipping); ?>.</small></span>
				<?php else : ?>
					<span><strong>Ships to All 50 States</strong><small>Standard to overnight.</small></span>
				<?php endif; ?>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo retails_home_icon('clock'); ?></span>
				<span><strong>Fast Turnaround</strong><small>Next-day options.</small></span>
			</div>
			<div class="home-hero-feature">
				<span class="home-hero-feature-icon" aria-hidden="true"><?php echo retails_home_icon('badge'); ?></span>
				<span><strong>Expert Support</strong><small>Sizing and artwork help.</small></span>
			</div>
		</div>
	</div>

	<section class="sf-section hc-product-section" id="banners-displays" aria-labelledby="hc-bd-title">
		<div class="container">
			<p class="cl-kicker">Product lines</p>
			<h2 id="hc-bd-title" class="cl-section-title">Shop Banners &amp; Displays</h2>
			<p class="cl-section-lead">Promote a storefront, an event or a booth: printed in full color and finished the way you&rsquo;ll hang or set it up.</p>
			<div class="hc-pcard-grid">
				<?php foreach ($bd_lines as $line) : ?>
					<?php $render_card($line_card($line)); ?>
				<?php endforeach; ?>
			</div>
			<?php if ($bd_url) : ?>
				<p class="hc-more-link"><a href="<?php echo esc_url($bd_url); ?>">Compare materials and stands in our banners &amp; displays guide <?php echo retails_home_icon('arrow'); ?></a></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section sf-section--tint hc-product-section hc-order-picks" id="everyday-displays" aria-labelledby="hc-everyday-title">
		<div class="container">
			<p class="cl-kicker">Quick picks</p>
			<h2 id="hc-everyday-title" class="cl-section-title">Everyday Banners &amp; Displays</h2>
			<p class="cl-section-lead">Single products for a specific job, from fence banners to photo backdrops.</p>
			<div class="hc-pcard-grid">
				<?php foreach ($everyday as $slug => $label) : ?>
					<?php
					$product = retails_seo_product($slug);
					if (!$product) {
						continue;
					}
					$name = wp_strip_all_tags(get_the_title($product));
					$price = $price_of($slug);
					$render_card(array(
						'url' => get_permalink($product),
						'name' => esc_html($name),
						'photos' => $card_photos($product, $name, '(max-width: 575px) 46vw, (max-width: 991px) 46vw, 280px'),
						'tags' => $card_tags($product, $price, isset($sales[$product->ID]) ? $sales[$product->ID] : 0),
						'kicker' => $label,
						'meta' => $card_turnaround($product),
						'trust' => function_exists('retails_card_trust') ? retails_card_trust($product->ID, 'ship') : '',
						'price' => $price,
						'button' => 'Customize & Order',
						// Phones and narrow columns: the same short label as the channel letter cards.
						'button_short' => 'Order Now',
					));
					?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	// Channel letter styles with their prices; the cheapest one gets the "Lowest price" flag.
	$cl_cards = array();
	foreach ($cl_styles as $slug => $style) {
		$product = retails_seo_product($slug);
		if (!$product) {
			continue;
		}
		$cl_cards[] = array(
			'slug' => $slug,
			'type' => preg_match('/^Type\s*(\d+)/i', get_the_title($product), $type_match) ? (int) $type_match[1] : 0,
			'style' => $style,
			'url' => get_permalink($product),
			'price' => $cl_card_price($slug),
		);
	}
	$cl_priced = array_filter(array_column($cl_cards, 'price'));
	$cl_lowest = $cl_priced ? min(array_column($cl_priced, 'amount')) : 0;
	?>
	<?php if ($cl_cards) : ?>
	<section class="hc-shop-cl" id="channel-letters" aria-labelledby="hc-shop-cl-title">
		<div class="container">
			<div class="hc-shop-cl-head">
				<div class="hc-shop-cl-intro">
					<p class="hc-shop-cl-kicker"><span class="hc-shop-cl-dot" aria-hidden="true"></span> LED storefront signs</p>
					<h2 id="hc-shop-cl-title" class="hc-shop-cl-title">Shop Channel Letters <span>That Light Up Your Storefront</span></h2>
					<p class="hc-shop-cl-lead">Seven lighting styles, made to order in Washington and California. Pick a style, type your business name, choose a height and colors, and see your exact price before checkout.</p>
				</div>
				<ul class="hc-shop-cl-trust">
					<li><?php echo retails_home_icon('shield'); ?> UL listed for outdoor use</li>
					<li><?php echo retails_home_icon('badge'); ?> 5-year warranty</li>
					<li><?php echo retails_home_icon('plug'); ?> Ships tested with wiring diagram</li>
					<li><?php echo retails_home_icon('flag'); ?> Ships to all 50 states</li>
				</ul>
			</div>

			<div class="hc-shop-cl-grid">
				<?php foreach ($cl_cards as $card) : ?>
					<?php
					$style_name = wp_strip_all_tags(html_entity_decode($card['style'][0], ENT_QUOTES, 'UTF-8'));
					$is_lowest = $card['price'] && $cl_lowest && $card['price']['amount'] <= $cl_lowest;
					$flags = isset($card['style'][3]) ? (array) $card['style'][3] : array();
					if ($is_lowest) {
						$flags['Lowest price'] = 'price';
					}
					$photos = $card_photos(retails_seo_product($card['slug']), $style_name . ' channel letters', '(max-width: 575px) 46vw, (max-width: 991px) 31vw, 280px');
					$photo_count = count($photos);
					?>
					<article class="hc-shop-cl-card">
						<div class="hc-shop-cl-media hc-gal"<?php echo $photo_count > 1 ? ' data-hc-gallery' : ''; ?>>
							<a class="hc-gal-track" href="<?php echo esc_url($card['url']); ?>" tabindex="-1" aria-hidden="true">
								<?php foreach ($photos as $photo) : ?>
									<span class="hc-gal-slide"><?php echo $photo; ?></span>
								<?php endforeach; ?>
							</a>
							<?php if ($card['type']) : ?><span class="hc-shop-cl-type">Type <?php echo (int) $card['type']; ?></span><?php endif; ?>
							<?php if ($flags) : ?>
								<span class="hc-gal-flags">
									<?php foreach ($flags as $flag_label => $flag_color) : ?>
										<span class="hc-gal-flag hc-gal-flag--<?php echo esc_attr($flag_color); ?>"><?php if ('best' === $flag_color) : ?><svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 20.9l1.6-7L2 9.2l7.1-.6z"/></svg><?php endif; ?><?php echo esc_html($flag_label); ?></span>
									<?php endforeach; ?>
								</span>
							<?php endif; ?>
							<?php if ($photo_count > 1) : ?>
								<button type="button" class="hc-gal-arrow hc-gal-arrow--prev" data-hc-prev aria-label="Previous <?php echo esc_attr($style_name); ?> photo"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
								<button type="button" class="hc-gal-arrow hc-gal-arrow--next" data-hc-next aria-label="Next <?php echo esc_attr($style_name); ?> photo"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
								<span class="hc-gal-dots" aria-hidden="true">
									<?php for ($dot = 0; $dot < $photo_count; $dot++) : ?><span<?php echo 0 === $dot ? ' class="is-active"' : ''; ?>></span><?php endfor; ?>
								</span>
								<span class="hc-gal-count" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg><span data-hc-index>1</span>/<?php echo (int) $photo_count; ?></span>
							<?php endif; ?>
						</div>
						<a class="hc-shop-cl-body" href="<?php echo esc_url($card['url']); ?>">
							<span class="hc-shop-cl-tag hc-shop-cl-tag--<?php echo esc_attr(sanitize_title($card['style'][1])); ?>"><?php echo esc_html($card['style'][1]); ?></span>
							<span class="hc-shop-cl-name"><?php echo wp_kses_post($card['style'][0]); ?> <span class="screen-reader-text">channel letters</span></span>
							<span class="hc-shop-cl-desc"><?php echo esc_html($card['style'][2]); ?></span>
							<?php if ($card['price']) : ?>
								<span class="hc-shop-cl-price">
									<small>From</small>
									<?php if ($card['price']['was']) : ?><del><?php echo esc_html($card['price']['was']); ?></del><?php endif; ?>
									<strong><?php echo esc_html($card['price']['now']); ?></strong>
									<small>/ inch</small>
								</span>
							<?php endif; ?>
							<span class="hc-shop-cl-button"><span class="hc-shop-cl-button-long">Customize &amp; Order</span><span class="hc-shop-cl-button-short" aria-hidden="true">Order Now</span> <?php echo retails_home_icon('arrow'); ?></span>
						</a>
					</article>
				<?php endforeach; ?>

				<div class="hc-shop-cl-help">
					<span class="hc-shop-cl-help-icon" aria-hidden="true"><?php echo retails_home_icon('upload'); ?></span>
					<h3>Have a logo or custom shape?</h3>
					<p>Send it with your size and a sign specialist will quote it, usually within one business day.</p>
					<a class="hc-shop-cl-help-primary" href="<?php echo esc_url(retails_quote_url('channel-letters')); ?>">Get a Free Logo Quote <?php echo retails_home_icon('arrow'); ?></a>
					<a class="hc-shop-cl-help-link" href="<?php echo esc_url($builder_url); ?>"><?php echo retails_home_icon('pen'); ?> Design your sign online</a>
					<a class="hc-shop-cl-help-link" href="tel:+18664362101"><?php echo retails_home_icon('phone'); ?> 866-436-2101</a>
				</div>
			</div>

			<p class="hc-shop-cl-note">
				Rates are per inch of letter height and vary with height and style. Enter your design to see the full price before checkout.
				<a href="<?php echo esc_url($cl_landing_url); ?>">Compare all 7 styles, heights &amp; warranty <?php echo retails_home_icon('arrow'); ?></a>
			</p>
		</div>
	</section>
	<?php endif; ?>

	<?php
	// The full catalog (inc/home-catalog-directory.php): five photo tiles for lines
	// without a card above, then every category by group. Ported from the trade
	// site's home page, with the copy written for business owners instead of
	// resellers.
	if ($directory['groups']) :
		$dir_img = static function ($id, $size, $sizes) {
			return $id ? wp_get_attachment_image($id, $size, false, array('alt' => '', 'sizes' => $sizes, 'loading' => 'lazy', 'decoding' => 'async')) : '';
		};
		?>
	<section class="sf-section sf-section--tint hc-cats" id="shop-by-category" aria-labelledby="hc-cats-title">
		<div class="container">
			<div class="hc-cats-head">
				<div>
					<p class="cl-kicker">Full catalog</p>
					<h2 id="hc-cats-title" class="cl-section-title">Print, Display &amp; Event Products for Your Business</h2>
					<p class="cl-section-lead">Window graphics, rigid signs, wall art, hardware and every other category, grouped by use.</p>
				</div>
				<a class="hc-cats-link" href="#hc-cats-all"><?php echo retails_home_icon('grid'); ?>Browse all <?php echo (int) $directory['total']; ?> categories</a>
			</div>

			<?php if ($directory['featured']) : ?>
				<ul class="hc-feature" aria-label="Popular product categories">
					<?php
					$n = 0;
					foreach ($directory['featured'] as $slug => $item) :
						$n++;
						$is_lead = 1 === $n;
						?>
						<li class="hc-feature-item<?php echo $is_lead ? ' hc-feature-item--lead' : ''; ?>" style="--tint:<?php echo esc_attr($item['tint']); ?>">
							<a class="hc-feature-link" href="<?php echo esc_url($item['url']); ?>">
								<span class="hc-feature-media">
									<?php echo $dir_img($item['image'], $is_lead ? 'large' : 'medium_large', $is_lead ? '(min-width: 992px) 560px, 100vw' : '(min-width: 992px) 280px, 50vw'); ?>
								</span>
								<span class="hc-feature-body">
									<span class="hc-feature-count"><?php echo esc_html(sprintf(_n('%d product', '%d products', $item['count'], 'litsign'), $item['count'])); ?></span>
									<h3 class="hc-feature-name"><?php echo esc_html($item['name']); ?></h3>
									<span class="hc-feature-blurb"><?php echo esc_html($item['blurb']); ?></span>
									<?php $tile_price = $category_price($slug); ?>
									<?php if ($tile_price) : ?><span class="hc-feature-price"><small>From</small> <?php echo wp_kses_post($tile_price); ?></span><?php endif; ?>
									<span class="hc-feature-cta">Shop now <?php echo retails_home_icon('arrow'); ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="hc-dir" id="hc-cats-all">
				<?php foreach ($directory['groups'] as $key => $group) : ?>
					<section class="hc-dir-group" aria-labelledby="hc-dir-<?php echo esc_attr($key); ?>">
						<div class="hc-dir-head">
							<span class="hc-dir-icon"><?php echo retails_home_icon($group['icon']); ?></span>
							<div>
								<h3 class="hc-dir-title" id="hc-dir-<?php echo esc_attr($key); ?>"><?php echo esc_html($group['title']); ?></h3>
								<p class="hc-dir-meta"><?php echo esc_html(sprintf('%d categories · %d products', count($group['items']), $group['count'])); ?></p>
							</div>
						</div>
						<ul class="hc-dir-list">
							<?php foreach ($group['items'] as $item) : ?>
								<li>
									<a class="hc-dir-item" href="<?php echo esc_url($item['url']); ?>">
										<span class="hc-dir-thumb"><?php echo $dir_img($item['image'], 'thumbnail', '52px'); ?></span>
										<span class="hc-dir-text">
											<span class="hc-dir-name"><?php echo esc_html($item['name']); ?></span>
											<span class="hc-dir-count"><?php echo esc_html(sprintf(_n('%d product', '%d products', $item['count'], 'litsign'), $item['count'])); ?></span>
										</span>
										<?php echo retails_home_icon('arrow'); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php if ($group['all']) : ?>
							<a class="hc-dir-all" href="<?php echo esc_url($group['all']); ?>">View all <?php echo esc_html(strtolower($group['title'])); ?> <?php echo retails_home_icon('arrow'); ?></a>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</div>

			<p class="hc-more-link"><a href="<?php echo esc_url(retails_shop_url()); ?>">Browse every product <?php echo retails_home_icon('arrow'); ?></a></p>

			<div class="hc-cats-band">
				<span class="hc-cats-band-icon" aria-hidden="true"><?php echo retails_home_icon('check'); ?></span>
				<p class="hc-cats-band-text">
					<strong>Every product is priced online.</strong> Choose a size and options, see your total and check out. Need a size or material you don&rsquo;t see? Ask us for a quote.
				</p>
				<div class="hc-cats-band-actions">
					<a class="hc-cats-band-primary" href="<?php echo esc_url($contact_url); ?>">Request a Quote <?php echo retails_home_icon('arrow'); ?></a>
					<a class="hc-cats-band-ghost" href="tel:+18664362101"><?php echo retails_home_icon('phone'); ?> 866-436-2101</a>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section class="cl-steps" aria-labelledby="hc-steps-title">
		<div class="container">
			<p class="cl-kicker">How it works</p>
			<h2 id="hc-steps-title" class="cl-section-title">How to Order Custom Banners &amp; Signs</h2>
			<ol class="cl-steps-list">
				<li>
					<span class="cl-step-number" aria-hidden="true">1</span>
					<h3>Choose a product</h3>
					<p>Banners, stands, flags, displays, window graphics or signs.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">2</span>
					<h3>Enter your size</h3>
					<p>Pick your size and options; your price updates as you go.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">3</span>
					<h3>Add your artwork</h3>
					<p>Upload print-ready files, or <a href="<?php echo esc_url($contact_url); ?>">ask us for design help</a>.</p>
				</li>
				<li>
					<span class="cl-step-number" aria-hidden="true">4</span>
					<h3>We make &amp; ship it</h3>
					<p>Standard, 3-day, 2-day or overnight shipping to all 50 states.</p>
				</li>
			</ol>
		</div>
	</section>

	<?php
	// Reviews after the products and "How to order", next to About us, instead of
	// above the footer; the footer's call then renders nothing (the slider prints
	// once per page). Order: products > how to order > reviews > about > FAQ > help.
	retails_render_review_slider();
	?>

	<?php
	// Who makes it: the company facts in a few lines, linked to the pages that
	// cover them in full (owner-confirmed facts only). Hours, shipping and installing
	// are left to the FAQ and help band below.
	$about_url = $page_url('about');
	$reviews_url = function_exists('retails_reviews_page_url') ? retails_reviews_page_url() : $page_url('reviews');
	?>
	<section class="sf-section hc-about" aria-labelledby="hc-about-title">
		<div class="container sf-prose">
			<p class="cl-kicker">About us</p>
			<h2 id="hc-about-title" class="cl-section-title">Custom Banners &amp; Signs from Renton, WA, Since 2002</h2>
			<p>Storefront Sign Online has been making signs for businesses since 2002. Banners, flags, displays, window graphics and our other printed products are made in Renton, Washington, and our LED channel letters are built in Washington and California. Every product page shows its price as you choose a size and options, so you can order a single banner or a complete trade show booth without waiting on a quote.<?php if ($about_url || $reviews_url) : ?> <?php echo $about_url ? $home_link($about_url, 'Learn more about us') : ''; ?><?php echo $about_url && $reviews_url ? ' or ' : ''; ?><?php echo $reviews_url ? $home_link($reviews_url, ($about_url ? 'read' : 'Read') . ' customer reviews') : ''; ?>.<?php endif; ?></p>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="hc-faq-title">
		<div class="container">
			<div class="hc-faq-intro">
				<p class="cl-kicker">Before you order</p>
				<h2 id="hc-faq-title" class="cl-section-title">Questions About Storefront Sign Online</h2>
				<p class="cl-section-lead cl-faq-note">
					<?php if ($bd_url) : ?>Find sizing and materials in our <a href="<?php echo esc_url($bd_url); ?>#bd-faq-title">banners &amp; displays FAQ</a>. <?php endif; ?>Compare lighting styles and heights in our <a href="<?php echo esc_url($cl_landing_url); ?>#cl-faq-title">channel letter FAQ</a>.
				</p>
				<?php
				// Product photo under the FAQ intro: 480/960/1440 wide copies in AVIF and WebP.
				$faq_img_base = get_template_directory_uri() . '/img/home-faq-kit-';
				$faq_img_set = static function ($ext) use ($faq_img_base) {
					return implode(', ', array_map(static function ($w) use ($faq_img_base, $ext) {
						return esc_url($faq_img_base . $w . '.' . $ext) . ' ' . $w . 'w';
					}, array(480, 960, 1440)));
				};
				$faq_img_sizes = '(max-width: 767px) calc(100vw - 32px), (max-width: 1399px) 30vw, 460px';
				?>
				<div class="hc-faq-media">
					<picture>
						<source type="image/avif" srcset="<?php echo $faq_img_set('avif'); ?>" sizes="<?php echo esc_attr($faq_img_sizes); ?>">
						<img src="<?php echo esc_url($faq_img_base . '960.webp'); ?>" srcset="<?php echo $faq_img_set('webp'); ?>" sizes="<?php echo esc_attr($faq_img_sizes); ?>" width="1440" height="1080" loading="lazy" decoding="async" alt="A lit channel letter, a vinyl banner, a feather flag kit and sign material samples packed for shipping">
					</picture>
				</div>
			</div>
			<div class="cl-faq-list">
				<?php retails_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<section class="cl-help" aria-labelledby="hc-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="hc-help-title">Talk to a Real Specialist</h2>
				<p>Not sure which size, material or stand you need? Send a photo of the space or your logo and we&rsquo;ll help you choose before you order. Outfitting a whole store, event or booth? Send us the list and we&rsquo;ll put it all in one quote.</p>
				<ul class="cl-help-details">
					<li><?php echo retails_home_icon('clock'); ?> Mon&ndash;Fri, 8:00am&ndash;5:00pm PST</li>
					<li><?php echo retails_home_icon('pin'); ?> 707 S. Grady Way, Suite 600, Renton, WA 98057</li>
				</ul>
			</div>
			<div class="cl-help-actions">
				<a class="cl-help-action cl-help-action--primary" href="tel:+18664362101"><?php echo retails_home_icon('phone'); ?><span><small>Call toll free</small>866-436-2101</span></a>
				<a class="cl-help-action" href="sms:+12066186543"><?php echo retails_home_icon('message'); ?><span><small>Text us</small>206-618-6543</span></a>
				<a class="cl-help-action" href="mailto:TR@StorefrontSignOnline.com"><?php echo retails_home_icon('mail'); ?><span><small>Email</small>TR@StorefrontSignOnline.com</span></a>
				<a class="cl-help-quote" href="<?php echo esc_url(retails_quote_url()); ?>">Request a free quote <?php echo retails_home_icon('arrow'); ?></a>
			</div>
		</div>
	</section>

	<script>
	/* The first photo of each card waits in data attributes ($photo_tags) until the
	   page has loaded, the visitor scrolls, touches or types, or the photo is on
	   screen. Then it loads lazily, as before, so the hero photo comes first. */
	(function () {
		var firsts = [].slice.call(document.querySelectorAll('img[data-hc-first]'));
		var events = ['scroll', 'wheel', 'touchstart', 'pointerdown', 'keydown'];
		var io = null;
		var release = function (img) {
			if (!img.hasAttribute('data-hc-first')) {
				return;
			}
			img.removeAttribute('data-hc-first');
			if (img.dataset.hcSrcset) {
				img.srcset = img.dataset.hcSrcset;
			}
			img.src = img.dataset.hcSrc;
			// Back to a plain lazy image, so warm() below leaves it alone.
			img.removeAttribute('data-hc-srcset');
			img.removeAttribute('data-hc-src');
		};
		var all = function () {
			events.forEach(function (type) {
				window.removeEventListener(type, all, 'scroll' !== type);
			});
			if (io) {
				io.disconnect();
			}
			firsts.forEach(release);
		};
		if (!firsts.length) {
			return;
		}
		if (!('IntersectionObserver' in window)) {
			all();
			return;
		}
		io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					release(entry.target);
					io.unobserve(entry.target);
				}
			});
		});
		firsts.forEach(function (img) {
			io.observe(img);
		});
		// Page scrolls only: the reviews carousel scrolling itself is not the visitor.
		events.forEach(function (type) {
			window.addEventListener(type, all, { capture: 'scroll' !== type, passive: true });
		});
		var later = function () {
			if (window.requestIdleCallback) {
				window.requestIdleCallback(all, { timeout: 1000 });
			} else {
				setTimeout(all, 50);
			}
		};
		if ('complete' === document.readyState) {
			later();
		} else {
			window.addEventListener('load', later);
		}
	})();

	/* Card photo galleries (channel letters and the product grids): swipe on touch screens, arrows on hover, dots and a
	   1/N counter follow the visible photo. Later photos start loading once the
	   visitor reaches for the gallery (hover, touch or keyboard focus). */
	(function () {
		document.querySelectorAll('[data-hc-gallery]').forEach(function (media) {
			var track = media.querySelector('.hc-gal-track');
			var dots = media.querySelectorAll('.hc-gal-dots span');
			var counter = media.querySelector('[data-hc-index]');
			var total = dots.length;
			var current = 0;
			var warmed = false;
			if (!track || total < 2) {
				return;
			}
			var warm = function () {
				if (warmed) {
					return;
				}
				warmed = true;
				media.querySelectorAll('img[data-hc-src]:not([data-hc-first])').forEach(function (img) {
					img.loading = 'eager';
					// "auto" only works on lazy images; left in, browsers size for the full window.
					img.sizes = img.sizes.replace(/^auto,\s*/, '');
					if (img.dataset.hcSrcset) {
						img.srcset = img.dataset.hcSrcset;
					}
					img.src = img.dataset.hcSrc;
				});
			};
			var show = function (index) {
				current = index;
				dots.forEach(function (dot, i) {
					dot.classList.toggle('is-active', i === index);
				});
				if (counter) {
					counter.textContent = String(index + 1);
				}
			};
			var go = function (step) {
				warm();
				var next = (current + step + total) % total;
				track.scrollTo({ left: next * track.clientWidth, behavior: 'smooth' });
			};
			var ticking = false;
			track.addEventListener('scroll', function () {
				if (ticking) {
					return;
				}
				ticking = true;
				window.requestAnimationFrame(function () {
					ticking = false;
					var index = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
					if (index !== current && index >= 0 && index < total) {
						show(index);
					}
				});
			}, { passive: true });
			media.addEventListener('pointerenter', warm);
			media.addEventListener('touchstart', warm, { passive: true });
			media.addEventListener('focusin', warm);
			media.querySelector('[data-hc-prev]').addEventListener('click', function () { go(-1); });
			media.querySelector('[data-hc-next]').addEventListener('click', function () { go(1); });
		});
	})();
	</script>

	<nav class="cl-mobile-bar" aria-label="Quick actions">
		<button type="button" class="cl-mobile-bar-products" aria-controls="megaMenu"><?php echo retails_home_icon('grid'); ?> Categories</button>
		<a class="cl-mobile-bar-quote" href="<?php echo esc_url($contact_url); ?>">Free Quote</a>
		<a class="cl-mobile-bar-shop" href="#banners-displays">Shop Banners</a>
	</nav>
</main>

<?php
get_footer();
