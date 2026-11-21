<?php
/**
 * Channel letter style cards for /custom-channel-letters/.
 *
 * The same dark "Shop Channel Letters" band the home page shows
 * (template-parts/home-catalog.php, section #channel-letters): seven style cards
 * with a photo gallery, floating tags, a "From $X / letter" price and a
 * Customize & Order button, plus a logo-quote tile. Photos and prices are read from
 * the products. Styles are in css/cl-style-grid.css (the home page's copy lives in
 * css/home-catalog.css; keep the two in step).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Style slug => array(name, lighting tag, how it lights, floating tags, page anchor).
 * Copy matches the style comparison table on /custom-channel-letters/.
 */
function retails_cl_style_grid_styles()
{
	return array(
		'standard-channel-letter-front-lit' => array('Front Lit', 'Face lit', 'Bright, colored acrylic face. The easiest to read at night.', array('Best seller' => 'best'), 'front-lit'),
		'standard-channel-letter-back-lit' => array('Back Lit', 'Wall glow', 'Light glows onto the wall behind each letter.', array('Soft glow' => 'glow'), 'back-lit'),
		'standard-channel-letter-front-back-lit' => array('Front &amp; Back Lit', 'Dual lit', 'A lit face plus a halo on the wall for maximum presence.', array('Most visible' => 'blue'), 'dual-lit'),
		'hidden-back-halo-lit' => array('Halo Lit', 'Halo', 'Solid metal face with a soft halo. Upscale and modern.', array('Premium look' => 'premium'), 'halo-lit'),
		'halo-reverse-acrylic-lit-channel-letters' => array('Reverse Lit', 'Halo', 'Painted metal face with an exposed acrylic back that glows.', array('Glowing back' => 'glow'), 'reverse-lit'),
		'inset-acrylic-face-lit-with-border-no-trimcap' => array('Trimless with Border', 'Face lit', 'Inset acrylic face in a metal border, no plastic trimcap.', array('No trimcap' => 'dark'), 'trimless'),
		'exposed-acrylic-face-lit-borderless-no-trimcap' => array('Borderless', 'Face lit', 'Exposed acrylic face with no trimcap or border. The sleekest look.', array('Sleekest' => 'premium', 'No trimcap' => 'dark'), 'borderless'),
	);
}

/**
 * Photos for one style card: the featured photo, then the product gallery (at most
 * $max). Upload copies ("photo-1.jpg") and edited copies ("photo-e1733640963626.jpg")
 * of the same file are dropped. Returns <img> tags; only the first loads with the
 * page, the rest wait in data attributes until the visitor reaches for the card.
 */
function retails_cl_style_grid_photos($product, $name, $sizes, $max = 6)
{
	if (!$product) {
		return array();
	}
	$photo_key = static function ($url) {
		$file = strtolower(pathinfo((string) wp_parse_url((string) $url, PHP_URL_PATH), PATHINFO_FILENAME));
		$file = preg_replace('/\.(png|jpe?g)$/', '', $file);
		$file = preg_replace('/-\d+x\d+$/', '', $file);
		$file = preg_replace('/-e\d{10,}$/', '', $file);
		return preg_replace('/-\d$/', '', $file);
	};

	$featured = has_post_thumbnail($product) ? (int) get_post_thumbnail_id($product) : 0;
	$gallery = get_post_meta($product->ID, '_product_gallery', true);
	$gallery = is_array($gallery) ? array_values(array_filter($gallery, 'is_string')) : array();
	$ids = $gallery && function_exists('retails_product_image_alt_attachment_ids_by_url') ? retails_product_image_alt_attachment_ids_by_url($gallery) : array();

	$seen = array();
	$photos = array();
	if ($featured) {
		$photos[] = array('id' => $featured, 'url' => '', 'alt' => $name);
		$seen[$photo_key(wp_get_attachment_url($featured))] = true;
	}
	foreach ($gallery as $url) {
		if (count($photos) >= $max) {
			break;
		}
		$id = !empty($ids[$url]) ? (int) $ids[$url] : 0;
		$key = $photo_key($url);
		if (($id && $id === $featured) || isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$photos[] = array('id' => $id, 'url' => $url, 'alt' => sprintf('%s, photo %d', $name, count($photos) + 1));
	}

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
		if ($img && $position > 0) {
			$img = preg_replace('/\s(srcset|src)="/', ' data-hc-$1="', $img);
			$img = str_replace('<img ', '<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" ', $img);
		}
		if ($img) {
			$html[] = $img;
		}
	}

	return $html;
}

/**
 * Print the style grid.
 *
 * $args: 'slugs' (limit to these product slugs; empty = all seven), 'section_id'
 * (default "product-box-container": the anchor the page's buttons point at),
 * 'quote_url', 'compare_url' (the comparison table below), 'kicker', 'title',
 * 'title_accent', 'lead' (all plain text except the two title parts, which are
 * escaped here), 'notice_html' (printed under the lead, already escaped).
 */
function retails_render_cl_style_grid(array $args = array())
{
	$args = wp_parse_args($args, array(
		'slugs' => array(),
		'section_id' => 'product-box-container',
		'quote_url' => function_exists('retails_quote_url') ? retails_quote_url('channel-letters') : home_url('/contact/'),
		'compare_url' => '',
		'kicker' => 'LED storefront signs',
		'title' => 'Channel Letter Styles',
		'title_accent' => '& Starting Prices',
		'lead' => 'Seven lighting styles, made to order in Washington and California. Pick a style, type your business name, choose a height and colors, and see your exact price before checkout.',
		'notice_html' => '',
	));
	$builder_url = home_url('/channel-letter-builder/');

	$cards = array();
	foreach (retails_cl_style_grid_styles() as $slug => $style) {
		if ($args['slugs'] && !in_array($slug, (array) $args['slugs'], true)) {
			continue;
		}
		$product = function_exists('retails_seo_product') ? retails_seo_product($slug) : null;
		if (!$product) {
			continue;
		}
		$price = null;
		$from = function_exists('retails_cl_from_inch_rate') ? retails_cl_from_inch_rate($product->ID) : null;
		if ($from && $from['price'] > 0) {
			$now = '$' . number_format($from['price'], 2);
			$was = '$' . number_format($from['everyday'], 2);
			$price = array(
				'now' => $now,
				'was' => $was !== $now && $from['everyday'] > $from['price'] ? $was : '',
				'height' => (int) $from['height'],
				'amount' => (float) $from['price'],
			);
		}
		$cards[] = array(
			'slug' => $slug,
			'product' => $product,
			'type' => preg_match('/^Type\s*(\d+)/i', get_the_title($product), $type_match) ? (int) $type_match[1] : 0,
			'style' => $style,
			'url' => get_permalink($product),
			'price' => $price,
		);
	}
	if (!$cards) {
		return;
	}
	$priced = array_filter(array_column($cards, 'price'));
	$lowest = $priced ? min(array_column($priced, 'amount')) : 0;
	$title_id = 'cl-style-grid-title';
	?>
	<section class="hc-shop-cl cl-style-grid" id="<?php echo esc_attr($args['section_id']); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>">
		<div class="container">
			<div class="hc-shop-cl-head">
				<div class="hc-shop-cl-intro">
					<p class="hc-shop-cl-kicker"><span class="hc-shop-cl-dot" aria-hidden="true"></span> <?php echo esc_html($args['kicker']); ?></p>
					<h2 id="<?php echo esc_attr($title_id); ?>" class="hc-shop-cl-title"><?php echo esc_html($args['title']); ?> <span><?php echo esc_html($args['title_accent']); ?></span></h2>
					<p class="hc-shop-cl-lead"><?php echo esc_html($args['lead']); ?></p>
					<?php echo $args['notice_html']; // Escaped by the caller. ?>
				</div>
				<ul class="hc-shop-cl-trust">
					<li><?php echo retails_home_icon('shield'); ?> UL listed for outdoor use</li>
					<li><?php echo retails_home_icon('badge'); ?> 5-year warranty</li>
					<li><?php echo retails_home_icon('plug'); ?> Ships tested with wiring diagram</li>
					<li><?php echo retails_home_icon('flag'); ?> Ships to all 50 states</li>
				</ul>
			</div>

			<div class="hc-shop-cl-grid">
				<?php foreach ($cards as $card) : ?>
					<?php
					$style_name = wp_strip_all_tags(html_entity_decode($card['style'][0], ENT_QUOTES, 'UTF-8'));
					$is_lowest = $card['price'] && $lowest && $card['price']['amount'] <= $lowest;
					$flags = (array) $card['style'][3];
					if ($is_lowest) {
						$flags['Lowest price'] = 'price';
					}
					$photos = retails_cl_style_grid_photos($card['product'], $style_name . ' channel letters', '(max-width: 575px) 46vw, (max-width: 991px) 31vw, 280px');
					$photo_count = count($photos);
					?>
					<article class="hc-shop-cl-card" id="<?php echo esc_attr($card['style'][4]); ?>">
						<div class="hc-shop-cl-media hc-gal"<?php echo $photo_count > 1 ? ' data-hc-gallery' : ''; ?>>
							<a class="hc-gal-track" href="<?php echo esc_url($card['url']); ?>" tabindex="-1" aria-hidden="true">
								<?php foreach ($photos as $photo) : ?>
									<span class="hc-gal-slide"><?php echo $photo; // Built by wp_get_attachment_image() or escaped above. ?></span>
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
					<a class="hc-shop-cl-help-primary" href="<?php echo esc_url($args['quote_url']); ?>">Get a Free Logo Quote <?php echo retails_home_icon('arrow'); ?></a>
					<a class="hc-shop-cl-help-link" href="<?php echo esc_url($builder_url); ?>"><?php echo retails_home_icon('pen'); ?> Design your sign online</a>
					<a class="hc-shop-cl-help-link" href="tel:+18664362101"><?php echo retails_home_icon('phone'); ?> 866-436-2101</a>
				</div>
			</div>

			<p class="hc-shop-cl-note">
				Rates are per inch of letter height and vary with height and style. Enter your design to see the full price before checkout.
				<?php if ($args['compare_url']) : ?><a href="<?php echo esc_url($args['compare_url']); ?>">Compare all 7 styles, heights &amp; warranty <?php echo retails_home_icon('arrow'); ?></a><?php endif; ?>
			</p>
		</div>
	</section>

	<script>
	/* Style card photo galleries: swipe on touch screens, arrows on hover, dots and a
	   1/N counter follow the visible photo. Later photos start loading once the
	   visitor reaches for the gallery (hover, touch or keyboard focus). */
	(function () {
		document.querySelectorAll('.cl-style-grid [data-hc-gallery]').forEach(function (media) {
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
				media.querySelectorAll('img[data-hc-src]').forEach(function (img) {
					img.loading = 'eager';
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
	<?php
}
