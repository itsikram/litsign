<?php

/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package litsign
 */

$logo_id = get_theme_mod('custom_logo');

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">


	<style>
		.footer {}

		.footer-bg-image-1 {
			/*	background-image: url(<?php echo get_template_directory_uri() . '/img/footer-bg-1.jpg'; ?>); */
			min-height: unset;

		}

		.footer-bg-image-1 img {
			width: 100%;
		}


		.footer-bg-image-2 {
			min-height: unset !important;
			position: relative;

		}

		.footer-bg-image-2 .button-container {
			position: absolute;
			top: 40px;
			left: 0;
			width: 100%;
			height: 100%;
			text-align: center;
		}

		.footer-bg-image-2 img {
			width: 100%;
		}

		.dual-color-white {
			background: url("<?php echo get_template_directory_uri() . '/img/dual-color-white.jpeg'; ?>") 0% 0% / 25px padding-box text;
			-webkit-text-fill-color: transparent;
		}

		.dual-color-black {
			background: url("<?php echo get_template_directory_uri() . '/img/dual-color-black.jpeg'; ?>") 0% 0% / 25px padding-box text;
			-webkit-text-fill-color: transparent;
		}
	</style>

	<style>
		@font-face {
			font-family: 'nimbus-sans';
			/*a name to be used later*/
			src: url('<?php echo get_template_directory_uri() . '/fonts/NimbusSanL-Bol.woff2'; ?>') format('woff2');
			font-weight: bold;
			font-display: swap;
			/*URL to font*/
		}

		@font-face {
			font-family: 'type-writer';
			src: url('<?php echo get_template_directory_uri() . '/fonts/TYPEWR_B.woff2'; ?>') format('woff2');
			font-weight: bold;
			font-display: swap;
		}

		@font-face {
			font-family: 'alegreya';
			src: url('<?php echo get_template_directory_uri() . '/fonts/Alegreya-VariableFont_wght.woff2'; ?>') format('woff2');
			font-display: swap;
		}

		@font-face {
			font-family: 'anton';
			src: url('<?php echo get_template_directory_uri() . '/fonts/Anton-Regular.woff2'; ?>') format('woff2');
			font-display: swap;
		}

		@font-face {
			font-family: 'gotham-medium';
			src: url('<?php echo get_template_directory_uri() . '/fonts/gotham-medium.woff2'; ?>') format('woff2');
			font-weight: 900;
			font-display: swap;
		}

		@font-face {
			font-family: 'helvetica-condensed-bold';
			src: url('<?php echo get_template_directory_uri() . '/fonts/helvetica-condensed-bold.woff2'; ?>') format('woff2');
			font-weight: 900;
			font-display: swap;
		}

		@font-face {
			font-family: 'helvetica-rounded-bold';
			src: url('<?php echo get_template_directory_uri() . '/fonts/helvetica-rounded-bold.woff2'; ?>') format('woff2');
			font-weight: 900;
			font-display: swap;
		}



		.load-font-family {
			font-family: 'gotham-medium';
		}

		.load-font-family {
			font-family: 'helvetica-condensed-bold';
		}

		.load-font-family {
			font-family: 'arial-black';
		}

		#loadFontFamily {
			font-family: 'helvetica-rounded-bold';
		}

		body {
			font-family: var(--font-sans, system-ui, sans-serif);
		}
	</style>

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'litsign'); ?></a>

		<?php
		// Shipping offer amounts come from Settings > Checkout defaults.
		$ship_min = retails_free_shipping_amount();
		$ship_cl_min = retails_free_shipping_amount(true);
		$ship_offer = $ship_min
			? 'Free shipping on orders of ' . $ship_min . '+'
			: 'Free shipping on channel letter orders of ' . $ship_cl_min . '+';
		if ($ship_min && $ship_cl_min && $ship_cl_min !== $ship_min) {
			$ship_offer .= ' · Channel letters ' . $ship_cl_min . '+';
		}
		// Account links differ for signed-in customers. The cart count badge is filled in by
		// JavaScript from the sso_cart_count cookie, because pages are served from cache.
		$account_links = is_user_logged_in()
			? array(home_url('/my-orders/') => 'My Orders', home_url('/account/') => 'My Account')
			: array(home_url('/login/') => 'Log in', home_url('/signup/') => 'Register');
		$phone_display = '866-436-2101';
		$phone_href = 'tel:+18664362101';
		$text_display = '206-618-6543';
		$text_href = 'sms:+12066186543';
		// Inline icons: the theme's icon font is a small subset without these glyphs.
		$icon = static function ($name) {
			$paths = array(
				'phone' => '<path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
				'sms' => '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v11H9l-5 4z"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01"/>',
				'cart' => '<path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM1 2v2h2l3.6 7.6-1.35 2.4A2 2 0 0 0 7 17h12v-2H7.4a.25.25 0 0 1-.22-.37l.9-1.63h7.45a2 2 0 0 0 1.75-1.03l3.58-6.5A1 1 0 0 0 20 4H5.2l-.94-2H1zm16 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>',
				'grid' => '<path fill="currentColor" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
				'arrow' => '<path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>',
			);
			return '<svg class="sh-svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
		};
		$ship_url = home_url('/shipping-returns/#free-shipping');
		$ship_has = (bool) ($ship_min || $ship_cl_min);
		// A separate channel-letter minimum makes the offer twice as long; both top bars then drop lower-priority items to stay on one row.
		$ship_two = $ship_min && $ship_cl_min && $ship_cl_min !== $ship_min;

		// "All Products" mega menu, shared by the phone menu (#megaMenu), the desktop
		// menu (#ssxMega) and the phone drawer: three columns of titled groups.
		// Each link is array(label, url, category slug or '', is "shop all" link).
		$mm_cat = static function ($label, $slug, $all = false) {
			return array($label, retails_category_url($slug), $slug, $all);
		};
		$mm_prod = static function ($label, $path) {
			return array($label, home_url($path), '', false);
		};
		$mm_signs_url = function_exists('retails_seo_storefront_signs_url') ? retails_seo_storefront_signs_url() : '';
		$mega_columns = array(
			array(
				array('title' => 'Signs / Letters', 'links' => array(
					$mm_cat('Custom Channel Letter Signs', 'channel-letters'),
					// /signs-letters/ redirects to the Storefront Signs guide when it is published.
					array('Storefront Signs', $mm_signs_url ? $mm_signs_url : retails_category_url('signs-letters'), 'signs-letters', true),
				)),
				array('title' => 'Indoor / Outdoor Displays', 'links' => array(
					$mm_cat('Advertising Flags', 'advertising-flags'),
					$mm_cat('Banner Stands', 'banner-stands'),
					$mm_prod('Step and Repeat Backdrop', '/product/step-and-repeat-backdrop-graphic-frame/'),
					$mm_cat('Real Estate Products', 'real-estate-products'),
					$mm_cat('A Frame and Sign Holders', 'a-frame-and-sign-holders'),
					$mm_cat('Signicade A-Frames', 'signicade-a-frames'),
					$mm_cat('SEG Products', 'seg-products'),
					$mm_cat('Trade Show Products', 'trade-show-products'),
					$mm_cat('Custom Event Tents', 'custom-event-tents'),
					$mm_cat('Table Throws', 'table-throws'),
					$mm_cat('All Indoor / Outdoor Displays', 'indoor-outdoor-displays', true),
				)),
			),
			array(
				array('title' => 'Banners', 'links' => array(
					$mm_prod('13oz Vinyl Banner', '/product/13oz-vinyl-banner/'),
					$mm_prod('18oz Blockout Banner', '/product/18oz-blockout-banner/'),
					$mm_prod('Backlit Banner', '/product/backlit-banner/'),
					$mm_prod('Mesh Banner', '/product/mesh-banner/'),
					$mm_prod('Indoor Banner', '/product/indoor-banner-super-smooth/'),
					$mm_prod('Pole Banner', '/product/pole-banner-set-banner-hardware/'),
					$mm_prod('9oz Fabric Banner', '/product/fabric-banner-9oz-wrinkle-free-copy/'),
					$mm_prod('Blockout Fabric Banner', '/product/blockout-fabric-banner/'),
					$mm_prod('Tension Fabric', '/product/tension-fabric/'),
					$mm_cat('All Banners', 'banners', true),
				)),
				array('title' => 'Display Hardware', 'links' => array(
					$mm_cat('Display Hardware Only', 'hardware-only'),
					$mm_cat('Banner Stand Hardware', 'banner-stand-hardware'),
					$mm_cat('Flag Poles, Bases & Hardware', 'flag-hardware'),
					$mm_cat('A Frame and Sign Holder Hardware', 'a-frame-and-sign-holder-hardware'),
					$mm_cat('Signicade A-Frame Hardware', 'signicade-a-frame-hardware'),
					$mm_cat('Event Tent Hardware', 'event-tent-hardware-only'),
				)),
			),
			array(
				array('title' => 'Large Format', 'links' => array(
					$mm_cat('Adhesive Vinyl & Window Graphics', 'adhesive-products'),
					$mm_cat('Wall Art', 'wall-art'),
					$mm_cat('Rigid Signs and Magnets', 'rigid-signs-and-magnets'),
					$mm_cat('Reflective Products', 'reflective-products'),
					$mm_cat('Dry Erase Products', 'dry-erase-products'),
					$mm_cat('Clear Acrylic Prints', 'clear-acrylic-prints-custom-sizes-storefront-sign-online'),
					$mm_prod('DTF', '/product/dtf/'),
					$mm_prod('Backlit Film', '/product/backlit-film/'),
					$mm_prod('Prem. Window Cling', '/product/premium-window-cling/'),
					$mm_prod('Posters', '/product/posters/'),
					$mm_prod('Styrene', '/product/styrene/'),
					$mm_prod('Popup', '/product/popup/'),
					$mm_prod('Canvas Roll', '/product/canvas-roll/'),
					$mm_cat('All Large Format Printing', 'large-format', true),
				)),
			),
		);
		// Any category with products that the lists above miss (e.g. one added later
		// in Products > Categories) still shows, under "More Categories".
		$mm_listed = array();
		foreach ($mega_columns as $mm_column) {
			foreach ($mm_column as $mm_group) {
				foreach ($mm_group['links'] as $mm_link) {
					if ($mm_link[2]) {
						$mm_listed[$mm_link[2]] = true;
					}
				}
			}
		}
		$mm_terms = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false, 'orderby' => 'name'));
		$mm_more = array();
		foreach (is_wp_error($mm_terms) ? array() : $mm_terms as $mm_term) {
			if (isset($mm_listed[$mm_term->slug])) {
				continue;
			}
			if (function_exists('retails_seo_category_has_products') ? !retails_seo_category_has_products($mm_term) : !$mm_term->count) {
				continue;
			}
			// Names are stored HTML-escaped; drop an SEO suffix such as " | Storefront Sign Online".
			$mm_name = trim(preg_replace('/\s+\|.*$/', '', wp_specialchars_decode($mm_term->name, ENT_QUOTES)));
			$mm_more[] = $mm_cat($mm_name, $mm_term->slug);
		}
		if ($mm_more) {
			$mega_columns[count($mega_columns) - 1][] = array('title' => 'More Categories', 'links' => $mm_more);
		}
		unset($mm_listed, $mm_terms, $mm_more, $mm_term, $mm_name, $mm_column, $mm_group, $mm_link);
		?>
		<?php // Dark contact bar for phones and tablets (below 992px), matching the desktop top bar: call/text on the left, free shipping on the right. It sits above the sticky header, so it scrolls away. ?>
		<style>
			.sso-mbar {
				display: none;
			}

			@media (max-width: 991.98px) {
				.sso-mbar {
					display: block;
					background: #0d2236;
					color: #d3e0ec;
					font-size: 13px;
					line-height: 1.2;
				}

				.sso-mbar .sso-mbar__in {
					display: flex;
					flex-wrap: wrap;
					align-items: center;
					justify-content: space-between;
					gap: 6px 16px;
					min-height: 40px;
					padding-top: 6px;
					padding-bottom: 6px;
				}

				.sso-mbar__contact {
					display: flex;
					align-items: center;
					gap: 18px;
				}

				.sso-mbar--long-offer .sso-mbar__text {
					display: none;
				}

				.sso-mbar a {
					display: inline-flex;
					align-items: center;
					gap: 6px;
					color: #fff;
					text-decoration: none;
					white-space: nowrap;
				}

				.sso-mbar svg {
					flex: none;
					color: #7ccdee;
				}

				.sso-mbar__label {
					color: #9fdcf3;
					font-weight: 600;
				}

				.sso-mbar__num {
					font-weight: 800;
					letter-spacing: .01em;
				}

				.sso-mbar a.sso-mbar__ship {
					gap: 7px;
					height: 28px;
					padding: 0 11px 0 10px;
					border: 1px solid rgba(124, 205, 238, .4);
					border-radius: 999px;
					background: rgba(31, 168, 222, .14);
					color: #e4eef6;
				}

				.sso-mbar__ship strong,
				.sso-mbar__ship b {
					color: #fff;
					font-weight: 800;
				}

				.sso-mbar__ship-sep {
					color: #7ccdee;
				}

				.sso-mbar a:focus-visible {
					outline: 3px solid #7ccdee;
					outline-offset: 2px;
				}
			}

			@media (max-width: 599.98px) {
				/* Phones: free shipping and the toll-free number sit inline on one row. */
				.sso-mbar {
					font-size: 12px;
				}

				.sso-mbar .sso-mbar__in {
					flex-wrap: nowrap;
					justify-content: center;
					gap: 10px;
					min-height: 36px;
					padding-left: 10px;
					padding-right: 10px;
				}

				.sso-mbar__contact {
					flex: none;
				}

				.sso-mbar a {
					gap: 4px;
				}

				.sso-mbar svg {
					width: 13px;
					height: 13px;
				}

				/* Phones: free shipping first, then the toll-free number (labelled like the desktop top bar); the text number moves to the header SMS button and the menu. */
				.sso-mbar a.sso-mbar__ship {
					order: -1;
					flex: 0 1 auto;
					min-width: 0;
					gap: 5px;
					height: 24px;
					padding: 0 8px 0 7px;
				}

				.sso-mbar a.sso-mbar__ship > span {
					overflow: hidden;
					text-overflow: ellipsis;
				}

				.sso-mbar__long,
				.sso-mbar__label:not(.sso-mbar__label--toll),
				.sso-mbar__text {
					display: none !important;
				}
			}

			@media (max-width: 767.98px) {
				.sso-mbar--long-offer .sso-mbar__long {
					display: none;
				}
			}

			@media (max-width: 359.98px) {
				.sso-mbar {
					font-size: 12px;
				}

				.sso-mbar a.sso-mbar__ship svg {
					display: none;
				}
			}
		</style>
		<div class="sso-mbar<?php echo $ship_two ? ' sso-mbar--long-offer' : ''; ?>">
			<div class="container sso-mbar__in">
				<div class="sso-mbar__contact">
					<a class="sso-mbar__call" href="<?php echo esc_url($phone_href); ?>" aria-label="<?php echo esc_attr('Call us toll free at ' . $phone_display); ?>">
						<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg>
						<span class="sso-mbar__label sso-mbar__label--toll">Toll Free:</span>
						<span class="sso-mbar__num"><?php echo esc_html($phone_display); ?></span>
					</a>
					<a class="sso-mbar__text" href="<?php echo esc_url($text_href); ?>" aria-label="<?php echo esc_attr('Text us at ' . $text_display); ?>">
						<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v11H9l-5 4z"/></svg>
						<span class="sso-mbar__label">Text:</span>
						<span class="sso-mbar__num"><?php echo esc_html($text_display); ?></span>
					</a>
				</div>
				<?php if ($ship_has) : ?>
					<a class="sso-mbar__ship" href="<?php echo esc_url($ship_url); ?>" title="<?php esc_attr_e('See free shipping details', 'litsign'); ?>">
						<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2.5 6.5h11v9h-11zM13.5 9.5h4l3 3v3h-7"/><circle cx="6.5" cy="17.5" r="1.8" fill="#0d2236" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="17.5" r="1.8" fill="#0d2236" stroke="currentColor" stroke-width="2"/></svg>
						<span>
							<?php if ($ship_min) : ?>
								<strong>Free shipping</strong><span class="sso-mbar__long"> on orders of</span> <b><?php echo esc_html($ship_min); ?>+</b><?php if ($ship_two) : ?> <span class="sso-mbar__ship-sep" aria-hidden="true">&middot;</span> Channel letters <b><?php echo esc_html($ship_cl_min); ?>+</b><?php endif; ?>
							<?php else : ?>
								<strong>Free shipping</strong> on channel letters <b><?php echo esc_html($ship_cl_min); ?>+</b>
							<?php endif; ?>
						</span>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<header class="main-header site-header ssx-legacy">
			<div class="container">
				<div class="sh-top">
					<div class="logo-container sh-logo">
						<a href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name') . ' home'); ?>">
							<?php
							if ($logo_id) {
								echo wp_get_attachment_image($logo_id, 'full', false, array(
									'alt' => get_bloginfo('name'),
									'class' => 'header-logo',
									'loading' => 'eager',
									'decoding' => 'async',
									'fetchpriority' => 'high',
									// Same sizes as the desktop header logo below, so both pick one file.
									'sizes' => '(max-width: 767px) 240px, (max-width: 991px) 400px, (max-width: 1199px) 250px, 400px',
								));
							} else {
								echo retails_logo_fallback_html('header-logo');
							}
							?>
						</a>
					</div>

					<nav class="sh-contact" aria-label="<?php esc_attr_e('Contact', 'litsign'); ?>">
						<?php
						wp_nav_menu(array(
							'theme_location' => 'header-menu',
							'container_class' => 'header-menu-container',
							'menu_class' => 'header-menu',
						));
						?>
						<div class="sh-phone-options" aria-label="<?php esc_attr_e('Call or text us', 'litsign'); ?>">
							<span class="sh-phone-options__title">Call or text</span>
							<a class="sh-phone-option" href="<?php echo esc_url($phone_href); ?>">
								<span class="sh-phone-option__label">Call:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($phone_display); ?></span>
							</a>
							<a class="sh-phone-option" href="<?php echo esc_url($text_href); ?>">
								<span class="sh-phone-option__label">Text:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($text_display); ?></span>
							</a>
						</div>
						<p class="sh-hours">Mon&ndash;Fri 8am&ndash;5pm PT</p>
					</nav>

					<div class="sh-actions">
						<ul class="sh-account">
							<?php foreach ($account_links as $url => $label) : ?>
								<li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></li>
							<?php endforeach; ?>
						</ul>
						<a class="sh-icon sh-icon--call" href="<?php echo esc_attr($phone_href); ?>" aria-label="<?php echo esc_attr('Call us at ' . $phone_display); ?>">
							<?php echo $icon('phone'); // Static SVG. ?>
							<span class="sh-icon__text"><?php echo esc_html($phone_display); ?></span>
						</a>
						<?php // Phones: Send SMS replaces the call button (the toll-free number is in the dark bar above). ?>
						<a class="sh-icon sh-icon--sms" href="<?php echo esc_attr($text_href); ?>" aria-label="<?php echo esc_attr('Send us an SMS at ' . $text_display); ?>">
							<?php echo $icon('sms'); // Static SVG. ?>
						</a>
						<a class="sh-icon sh-icon--cart" href="<?php echo esc_url(home_url('/cart/')); ?>" data-cart-link aria-label="<?php esc_attr_e('Cart', 'litsign'); ?>">
							<?php echo $icon('cart'); // Static SVG. ?>
							<span class="sh-icon__text">Cart</span>
							<span class="sh-cart-count" data-cart-count hidden></span>
						</a>
						<button type="button" class="mobile-menu-trigger sh-icon sh-menu-toggle" aria-label="<?php esc_attr_e('Open menu', 'litsign'); ?>" aria-expanded="false" aria-controls="siteMobileMenu">
							<span></span>
							<span></span>
							<span></span>
						</button>
					</div>
				</div>

				<div class="mobile-menu-container" id="siteMobileMenu" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Menu', 'litsign'); ?>">
					<div class="mobile-menu-header">
						<span class="mobile-menu-title">Menu</span>
						<button type="button" class="mobile-menu-close" aria-label="<?php esc_attr_e('Close menu', 'litsign'); ?>">&times;</button>
					</div>
					<a class="mobile-menu-cta" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">
						Design your sign online <?php echo $icon('arrow'); // Static SVG. ?>
					</a>
					<nav aria-label="<?php esc_attr_e('Mobile navigation', 'litsign'); ?>">
						<p class="mobile-menu-label">Shop</p>
						<?php
						wp_nav_menu(array(
							'theme_location' => 'header-bottom-menu',
							'container' => false,
							'menu_class' => 'header-menu',
						));
						?>
						<p class="mobile-menu-label">Your account</p>
						<ul class="header-menu mobile-account-menu">
							<?php foreach ($account_links as $url => $label) : ?>
								<li class="menu-item"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></li>
							<?php endforeach; ?>
							<li class="menu-item"><a href="<?php echo esc_url(home_url('/cart/')); ?>">Cart <span class="sh-cart-count" data-cart-count hidden></span></a></li>
							<li class="menu-item"><a href="<?php echo esc_url(home_url('/checkout/')); ?>">Checkout</a></li>
						</ul>
					</nav>
					<div class="mobile-menu-contact">
						<p class="mobile-menu-label">Talk to a sign specialist</p>
						<?php
						wp_nav_menu(array(
							'theme_location' => 'header-menu',
							'container' => false,
							'menu_class' => 'header-menu',
						));
						?>
						<div class="sh-phone-options" aria-label="<?php esc_attr_e('Call or text us', 'litsign'); ?>">
							<span class="sh-phone-options__title">Call or text</span>
							<a class="sh-phone-option" href="<?php echo esc_url($phone_href); ?>">
								<span class="sh-phone-option__label">Call:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($phone_display); ?></span>
							</a>
							<a class="sh-phone-option" href="<?php echo esc_url($text_href); ?>">
								<span class="sh-phone-option__label">Text:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($text_display); ?></span>
							</a>
						</div>
						<p class="sh-hours">Mon&ndash;Fri 8am&ndash;5pm PT</p>
						<?php if ($ship_min || $ship_cl_min) : ?>
							<a class="sh-shipping-offer" href="<?php echo esc_url(home_url('/shipping-returns/#free-shipping')); ?>"><?php echo esc_html($ship_offer); ?></a>
						<?php endif; ?>
					</div>
				</div>

				<div class="row header-bottom">
					<div class="col d-flex justify-content-center align-items-center header-bottom-container">
						<div class="megamenu-container">
							<?php // Hidden: the header no longer shows it; phone bottom bars still open #megaMenu through it (js/main.js). ?>
						<button type="button" id="allProductsBtn" style="display:none" aria-expanded="false" aria-controls="megaMenu"><?php echo $icon('grid'); // Static SVG. ?> All Products <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
							<div id="megaMenu" class="mega-menu">
								<div class="mega-menu-mobile-header">
									<span class="mega-menu-mobile-title">All Products</span>
									<button type="button" class="mega-menu-close" aria-label="<?php esc_attr_e('Close product menu', 'litsign'); ?>">
										<span aria-hidden="true">&times;</span>
									</button>
								</div>
								<div class="container p-3">
									<div class="row">
										<?php foreach ($mega_columns as $column) : ?>
											<div class="col-md-4">
												<?php foreach ($column as $group_i => $group) : ?>
													<div class="mm-col-container<?php echo $group_i ? ' mt-3' : ''; ?>">
														<h4 class="mm-heading"><?php echo esc_html($group['title']); ?></h4>
														<?php foreach ($group['links'] as $link) : ?>
															<a href="<?php echo esc_url($link[1]); ?>" class="mm-link<?php echo !empty($link[3]) ? ' mm-link--all' : ''; ?>">
																<span><?php echo esc_html($link[0]); ?></span>
																<?php if ($link[2]) : ?><i class="fa-solid fa-chevron-right"></i><?php endif; ?>
															</a>
														<?php endforeach; ?>
													</div>
												<?php endforeach; ?>
											</div>
										<?php endforeach; ?>
										<div class="mega-menu-footer">
											<a class="mega-menu-builder-cta" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">
												<span>Start building</span>
												<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
											</a>
										</div>
									</div>
								</div>
							</div>
							<div class="mega-menu-backdrop" aria-hidden="true"></div>
						</div>
						<nav class="sh-categories" aria-label="<?php esc_attr_e('Product categories', 'litsign'); ?>">
							<?php wp_nav_menu(array(
								'theme_location' => 'header-bottom-menu',
								'container_class' => 'header-menu-container',
								'menu_class' => 'header-menu',
							)); ?>
						</nav>
						<?php
						// Main call-to-action (this header and the desktop one below). "Start Building"
						// opens the channel letter builder, so banner, flag, vinyl and display shoppers
						// get a free quote instead; so does the builder itself, rather than a reload.
						$cta_quote = is_page('channel-letter-builder');
						$cta_on_page_quote = is_page(array('banners-displays', 'window-graphics')); // Both have a #quote form.
						if (is_singular('product')) {
							$cta_quote = 'sign' !== retails_product_noun(get_queried_object_id());
						} elseif (get_query_var('category_slug')) {
							$cta_quote = !retails_is_channel_letters_page();
						} elseif (is_front_page()) {
							$cta_quote = true; // The home page leads with banners & displays.
						} elseif ($cta_on_page_quote) {
							$cta_quote = true;
						}
						$cta_url   = $cta_quote ? ($cta_on_page_quote ? '#quote' : (is_singular('product') && function_exists('retails_quote_url') ? retails_quote_url('', get_post_field('post_name', get_queried_object_id())) : home_url('/contact/'))) : home_url('/channel-letter-builder/');
						$cta_label = $cta_quote ? 'Free Quote' : 'Start Building';
						?>

					</div>

				</div>
			</div>
		</header>

		<?php
		/* ===================================================================
		 * NEW DESKTOP HEADER (shown at 992px and up).
		 * Below 992px the original header above is shown, unchanged.
		 * =================================================================== */

		$ssx_email = 'TR@StorefrontSignOnline.com';

		// Search: limit results to products. Set to '' to search the whole site instead.
		$search_post_type = 'product';
		$popular_searches = array('Channel letters', '13oz vinyl banner', 'Flags', 'Adhesive vinyl', 'Trade show');

		// Main call-to-action: $cta_url / $cta_label are set above the phone header's button.

		// Inline icons (the theme's icon font is a small subset without these glyphs).
		$ssx_icon = static function ($name, $size = 20) {
			$s = 'fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"';
			$paths = array(
				'phone'   => '<path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
				'cart'    => '<path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM1 2v2h2l3.6 7.6-1.35 2.4A2 2 0 0 0 7 17h12v-2H7.4a.25.25 0 0 1-.22-.37l.9-1.63h7.45a2 2 0 0 0 1.75-1.03l3.58-6.5A1 1 0 0 0 20 4H5.2l-.94-2H1zm16 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>',
				'grid'    => '<path fill="currentColor" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
				'search'  => '<circle cx="11" cy="11" r="7" ' . $s . ' stroke-width="2.2"/><path ' . $s . ' stroke-width="2.2" d="m20 20-3.5-3.5"/>',
				'chevron' => '<path ' . $s . ' stroke-width="2.4" d="m6 9 6 6 6-6"/>',
				'close'   => '<path ' . $s . ' stroke-width="2.2" d="M6 6l12 12M18 6 6 18"/>',
				'clock'   => '<circle cx="12" cy="12" r="9" ' . $s . ' stroke-width="2"/><path ' . $s . ' stroke-width="2" d="M12 7v5l3 2"/>',
				'sms'     => '<path ' . $s . ' stroke-width="2" d="M4 5h16v11H9l-5 4z"/>',
				'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2" ' . $s . ' stroke-width="2"/><path ' . $s . ' stroke-width="2" d="m3 7 9 6 9-6"/>',
				'next'    => '<path ' . $s . ' stroke-width="2.4" d="m9 6 6 6-6 6"/>',
				// Outline icons for the phone menu (.ssxm), drawn to match 'mail' / 'sms'.
				'phone-o' => '<path ' . $s . ' stroke-width="1.8" d="M21 16.4v2.8a1.9 1.9 0 0 1-2.1 1.9 18.8 18.8 0 0 1-8.2-2.9 18.5 18.5 0 0 1-5.7-5.7A18.8 18.8 0 0 1 2.1 4.2 1.9 1.9 0 0 1 4 2.1h2.8a1.9 1.9 0 0 1 1.9 1.6c.12.9.34 1.8.66 2.65a1.9 1.9 0 0 1-.43 2L7.7 9.5a15.2 15.2 0 0 0 5.7 5.7l1.2-1.2a1.9 1.9 0 0 1 2-.43c.86.32 1.75.54 2.65.66A1.9 1.9 0 0 1 21 16.4z"/>',
				'cart-o'  => '<circle cx="9" cy="20" r="1.3" ' . $s . ' stroke-width="1.8"/><circle cx="18" cy="20" r="1.3" ' . $s . ' stroke-width="1.8"/><path ' . $s . ' stroke-width="1.8" d="M2.5 3h2.4l2.5 11.4a1.8 1.8 0 0 0 1.8 1.4h8.5a1.8 1.8 0 0 0 1.75-1.35L21.5 7H5.8"/>',
				'card'    => '<rect x="2.5" y="5" width="19" height="14" rx="2.5" ' . $s . ' stroke-width="1.8"/><path ' . $s . ' stroke-width="1.8" d="M2.5 10h19M6.5 15h4"/>',
				'arrow'   => '<path ' . $s . ' stroke-width="2" d="M5 12h14M13 6l6 6-6 6"/>',
				'truck'   => '<path ' . $s . ' stroke-width="2" d="M2.5 6.5h11v9h-11zM13.5 9.5h4l3 3v3h-7"/><circle cx="6.5" cy="17.5" r="1.8" fill="#0d2236" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="17.5" r="1.8" fill="#0d2236" stroke="currentColor" stroke-width="2"/>',
			);
			return '<svg class="ssx-svg" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
		};

		// "All Products" mega menu: $mega_columns is set at the top of this file (shared with the phone menu).
		?>

		<style>
			/* ===================================================================
			 * Desktop header (ssx-*). Hidden below 992px, where the original
			 * header (.ssx-legacy) is shown instead.
			 * =================================================================== */
			@media (min-width: 992px) {
				.ssx-legacy {
					display: none !important;
				}
			}

			@media (max-width: 991px) {
				.ssx-header {
					display: none !important;
				}
			}

			.ssx-header {
				--ssx-blue: #1fa8de;
				--ssx-blue-hover: #1790c0;
				/* Buttons with white text: #1fa8de is only 2.7:1 behind white, this is 5.2:1. */
				--ssx-blue-button: #0b74a3;
				--ssx-blue-button-hover: #095f86;
				--ssx-blue-text: #0e7aa6;
				--ssx-blue-tint: #e6f6fc;
				--ssx-navy: #0d2236;
				--ssx-ink: #17222e;
				--ssx-muted: #5c6b7a;
				--ssx-line: #e2e8ee;
				--ssx-soft: #f3f6f9;
				--ssx-topbar-h: 42px;

				position: sticky;
				top: calc(var(--ssx-topbar-h) * -1);
				z-index: 1030;
				background: #fff;
				color: var(--ssx-ink);
				font-family: var(--font-sans, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
				font-size: 15px;
				line-height: 1.4;
				box-shadow: 0 1px 0 var(--ssx-line), 0 6px 18px rgba(13, 34, 54, .05);
			}

			.admin-bar .ssx-header {
				top: calc(32px - var(--ssx-topbar-h));
			}

			.ssx-header *,
			.ssx-header *::before,
			.ssx-header *::after {
				box-sizing: border-box;
			}

			.ssx-header [hidden] {
				display: none !important;
			}

			.ssx-header a {
				color: inherit;
				text-decoration: none;
			}

			.ssx-header ul {
				list-style: none;
				margin: 0;
				padding: 0;
			}

			.ssx-header button {
				font-family: inherit;
				font-size: inherit;
				color: inherit;
				margin: 0;
			}

			.ssx-header :focus-visible {
				outline: 3px solid #7ccdee;
				outline-offset: 2px;
			}

			.ssx-container {
				width: 100%;
				max-width: 1320px;
				margin: 0 auto;
				padding-left: 12px;
				padding-right: 12px;
			}

			.ssx-svg {
				display: block;
				flex: none;
			}

			.ssx-vh {
				position: absolute !important;
				width: 1px;
				height: 1px;
				margin: -1px;
				padding: 0;
				overflow: hidden;
				clip: rect(0 0 0 0);
				white-space: nowrap;
				border: 0;
			}

			/* ---------- Top bar: call / text / email / hours ---------- */
			.ssx-topbar {
				height: var(--ssx-topbar-h);
				background: var(--ssx-navy);
				color: #d3e0ec;
				font-size: 14px;
			}

			.ssx-topbar__in {
				height: 100%;
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 16px;
			}

			.ssx-topbar__left,
			.ssx-topbar__right {
				display: flex;
				align-items: center;
				gap: 26px;
				min-width: 0;
			}

			.ssx-tb {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				color: #fff;
				white-space: nowrap;
				transition: color .15s;
			}

			a.ssx-tb:hover {
				color: #7ccdee;
			}

			.ssx-tb .ssx-svg {
				color: var(--ssx-blue);
			}

			.ssx-tb__label {
				color: #9fdcf3;
				font-weight: 600;
			}

			.ssx-tb strong {
				font-size: 15px;
				font-weight: 800;
				letter-spacing: .01em;
			}

			.ssx-tb--muted {
				color: #cfdbe7;
			}

			/* Free shipping: a quiet pill on the dark bar that links to the details. */
			.ssx-topbar__right {
				gap: 18px;
			}

			.ssx-topbar .ssx-ship {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				height: 28px;
				padding: 0 10px 0 11px;
				border: 1px solid rgba(124, 205, 238, .4);
				border-radius: 999px;
				background: rgba(31, 168, 222, .14);
				color: #e4eef6;
				font-size: 13.5px;
				line-height: 1;
				white-space: nowrap;
				transition: background-color .15s, border-color .15s;
			}

			.ssx-topbar .ssx-ship:hover {
				border-color: rgba(124, 205, 238, .8);
				background: rgba(31, 168, 222, .26);
			}

			.ssx-ship .ssx-svg {
				color: #7ccdee;
			}

			.ssx-ship strong,
			.ssx-ship b {
				color: #fff;
				font-weight: 800;
			}

			.ssx-ship__sep {
				color: #7ccdee;
			}

			.ssx-ship__go {
				display: inline-flex;
				margin-left: -2px;
				transition: transform .15s;
			}

			.ssx-ship:hover .ssx-ship__go {
				transform: translateX(2px);
			}

			/* Hours sit left of the free-shipping pill, with the divider between them. */
			.ssx-tb--hours {
				padding-right: 18px;
				border-right: 1px solid rgba(255, 255, 255, .16);
			}

			.ssx-tb--hours:last-child {
				padding-right: 0;
				border-right: 0;
			}

			/* Narrower desktops keep one row: hours drop first (they're on the Contact page and in the menu). */
			@media (min-width: 992px) and (max-width: 1299px) {
				.ssx-tb--hours {
					display: none;
				}
			}

			/* The longer two-minimum offer: the "Toll Free:" / "Text:" labels go (the icons stay), since the bar is capped at 1320px
			   and needs ~1360px with them; then the hours go below 1500px and the email below 1100px. */
			@media (min-width: 992px) {
				.ssx-topbar--long-offer .ssx-tb__label {
					display: none;
				}
			}

			@media (min-width: 992px) and (max-width: 1499px) {
				.ssx-topbar--long-offer .ssx-tb--hours {
					display: none;
				}
			}

			@media (min-width: 992px) and (max-width: 1099px) {
				.ssx-topbar--long-offer .ssx-tb--email {
					display: none;
				}
			}

			/* ---------- Main row: logo / search / account + cart ---------- */
			.ssx-main {
				background: #fff;
			}

			.ssx-main__in {
				display: grid;
				grid-template-columns: auto minmax(0, 1fr) auto;
				align-items: center;
				column-gap: clamp(16px, 2.6vw, 40px);
				padding-top: 14px;
				padding-bottom: 14px;
			}

			.ssx-logo {
				display: block;
				min-width: 0;
			}

			.ssx-logo img {
				display: block;
				width: 320px;
				max-width: 100%;
				height: auto;
			}

			.ssx-actions {
				display: flex;
				align-items: center;
				gap: 4px;
			}

			.ssx-account {
				display: flex;
				align-items: center;
			}

			.ssx-link {
				position: relative;
				display: inline-flex;
				align-items: center;
				gap: 8px;
				min-height: 44px;
				padding: 0 12px;
				border-radius: 10px;
				color: var(--ssx-ink);
				font-weight: 600;
				white-space: nowrap;
				transition: background-color .15s, color .15s;
			}

			.ssx-link:hover {
				background: var(--ssx-soft);
				color: var(--ssx-blue-text);
			}

			.ssx-badge {
				min-width: 20px;
				height: 20px;
				padding: 0 6px;
				border-radius: 10px;
				background: #f04e23;
				color: #fff;
				font-size: 12px;
				font-weight: 700;
				line-height: 20px;
				text-align: center;
			}

			/* ---------- Search ---------- */
			.ssx-search {
				position: relative;
				width: 100%;
				max-width: 600px;
				justify-self: center;
			}

			.ssx-search__field {
				display: flex;
				align-items: center;
				gap: 8px;
				height: 48px;
				padding: 0 6px 0 16px;
				border: 1.5px solid #cfd9e2;
				border-radius: 999px;
				background: #fff;
				color: var(--ssx-muted);
				transition: border-color .15s, box-shadow .15s;
			}

			.ssx-search__field:hover {
				border-color: #aebccb;
			}

			.ssx-search__field:focus-within {
				border-color: var(--ssx-blue);
				box-shadow: 0 0 0 4px rgba(31, 168, 222, .18);
			}

			.ssx-search__input {
				flex: 1;
				min-width: 0;
				height: 100%;
				padding: 0;
				border: 0;
				outline: 0;
				box-shadow: none;
				background: transparent;
				color: var(--ssx-ink);
				font: inherit;
				font-size: 15px;
				-webkit-appearance: none;
				appearance: none;
			}

			.ssx-search__input::placeholder {
				color: #7d8b99;
				opacity: 1;
			}

			.ssx-search__input::-webkit-search-cancel-button,
			.ssx-search__input::-webkit-search-decoration {
				-webkit-appearance: none;
				display: none;
			}

			.ssx-search__clear {
				flex: none;
				display: inline-grid;
				place-items: center;
				width: 32px;
				height: 32px;
				border: 0;
				border-radius: 50%;
				background: transparent;
				color: var(--ssx-muted);
				cursor: pointer;
			}

			.ssx-search__clear:hover {
				background: var(--ssx-soft);
				color: var(--ssx-ink);
			}

			.ssx-header .ssx-search__submit {
				flex: none;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				gap: 8px;
				height: 36px;
				padding: 0 18px;
				border: 0;
				border-radius: 999px;
				background: var(--ssx-blue-button);
				color: #fff;
				font-weight: 700;
				cursor: pointer;
				transition: background-color .15s;
			}

			.ssx-search__submit:hover {
				background: var(--ssx-blue-button-hover);
			}

			.ssx-suggest {
				position: absolute;
				left: 0;
				right: 0;
				top: calc(100% + 8px);
				z-index: 5;
				padding: 16px 18px 18px;
				border: 1px solid var(--ssx-line);
				border-radius: 16px;
				background: #fff;
				box-shadow: 0 18px 40px rgba(13, 34, 54, .18);
			}

			.ssx-suggest__title {
				margin: 0 0 10px;
				color: var(--ssx-muted);
				font-size: 13px;
				font-weight: 600;
			}

			.ssx-chips {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
			}

			.ssx-chip {
				display: inline-flex;
				align-items: center;
				min-height: 36px;
				padding: 0 14px;
				border: 1px solid var(--ssx-line);
				border-radius: 999px;
				background: var(--ssx-soft);
				color: var(--ssx-ink);
				font-size: 14px;
				font-weight: 600;
				transition: background-color .15s, border-color .15s, color .15s;
			}

			.ssx-chip:hover {
				border-color: var(--ssx-blue);
				background: var(--ssx-blue-tint);
				color: var(--ssx-blue-text);
			}

			/* ---------- Navigation row ---------- */
			.ssx-nav {
				position: relative;
				border-top: 1px solid var(--ssx-line);
				background: #fff;
			}

			.ssx-nav__in {
				display: flex;
				align-items: center;
				gap: 10px;
				min-height: 54px;
			}

			.ssx-allbtn {
				flex: none;
				display: inline-flex;
				align-items: center;
				gap: 10px;
				height: 40px;
				padding: 0 14px 0 16px;
				border: 0;
				border-radius: 10px;
				background: var(--ssx-blue-tint);
				color: var(--ssx-blue-text);
				font-weight: 700;
				cursor: pointer;
				transition: background-color .15s, color .15s;
			}

			.ssx-allbtn:hover,
			.ssx-allbtn[aria-expanded="true"] {
				background: var(--ssx-blue-button);
				color: #fff;
			}

			.ssx-allbtn .ssx-svg:last-child {
				transition: transform .2s;
			}

			.ssx-allbtn[aria-expanded="true"] .ssx-svg:last-child {
				transform: rotate(180deg);
			}

			.ssx-nav__links {
				flex: 1;
				min-width: 0;
				display: flex;
			}

			.ssx-menu {
				flex: 1;
				min-width: 0;
				display: flex;
				align-items: center;
				gap: 2px;
				overflow-x: auto;
				scrollbar-width: none;
			}

			.ssx-menu::-webkit-scrollbar {
				display: none;
			}

			.ssx-menu a {
				position: relative;
				display: block;
				padding: 10px 13px;
				border-radius: 8px;
				color: var(--ssx-ink);
				font-weight: 600;
				white-space: nowrap;
				transition: background-color .15s, color .15s;
			}

			.ssx-menu a:hover {
				background: var(--ssx-soft);
				color: var(--ssx-blue-text);
			}

			.ssx-menu .current-menu-item>a,
			.ssx-menu .current_page_item>a,
			.ssx-menu .current-menu-ancestor>a {
				color: var(--ssx-blue-text);
			}

			.ssx-menu .current-menu-item>a::after,
			.ssx-menu .current_page_item>a::after,
			.ssx-menu .current-menu-ancestor>a::after {
				content: "";
				position: absolute;
				left: 13px;
				right: 13px;
				bottom: 3px;
				height: 2px;
				border-radius: 2px;
				background: var(--ssx-blue);
			}

			.ssx-cta {
				flex: none;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-height: 40px;
				padding: 0 24px;
				border-radius: 999px;
				background: var(--ssx-blue-button);
				color: #fff !important;
				font-weight: 700;
				white-space: nowrap;
				transition: background-color .15s, box-shadow .15s;
			}

			.ssx-cta:hover {
				background: var(--ssx-blue-button-hover);
				box-shadow: 0 6px 16px rgba(31, 168, 222, .38);
			}

			/* ---------- Mega menu ---------- */
			.ssx-mega {
				position: absolute;
				left: 0;
				right: 0;
				top: 100%;
				z-index: 3;
				max-height: calc(100vh - 180px);
				overflow-y: auto;
				border-top: 1px solid var(--ssx-line);
				background: #fff;
				box-shadow: 0 24px 48px rgba(13, 34, 54, .2);
				opacity: 0;
				visibility: hidden;
				transform: translateY(-6px);
				transition: opacity .18s, transform .18s, visibility 0s .18s;
			}

			.ssx-mega.is-open {
				opacity: 1;
				visibility: visible;
				transform: none;
				transition-delay: 0s;
			}

			.ssx-mega__grid {
				display: grid;
				grid-template-columns: repeat(3, minmax(0, 1fr));
				gap: 36px;
				padding-top: 28px;
				padding-bottom: 24px;
			}

			.ssx-mega__group+.ssx-mega__group {
				margin-top: 26px;
			}

			.ssx-mega__title {
				margin: 0 0 8px;
				padding-bottom: 8px;
				border-bottom: 2px solid var(--ssx-line);
				color: var(--ssx-ink);
				font-size: 17px;
				font-weight: 800;
			}

			.ssx-mega__list a {
				display: block;
				margin: 0 -10px;
				padding: 7px 10px;
				border-radius: 8px;
				color: #2c3a49;
				transition: background-color .12s, color .12s, padding-left .12s;
			}

			.ssx-mega__list a:hover {
				padding-left: 14px;
				background: var(--ssx-blue-tint);
				color: var(--ssx-blue-text);
			}

			/* "All Banners" etc.: the group's whole category. */
			.ssx-mega__list a.is-all {
				color: var(--ssx-blue-text);
				font-weight: 700;
			}

			.ssx-mega__foot {
				background: var(--ssx-soft);
				border-top: 1px solid var(--ssx-line);
			}

			.ssx-mega__foot-in {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 16px;
				padding-top: 14px;
				padding-bottom: 14px;
			}

			.ssx-mega__foot p {
				margin: 0;
				color: var(--ssx-muted);
			}

			.ssx-mega__foot p a {
				color: var(--ssx-ink);
				font-weight: 700;
			}

			.ssx-backdrop {
				position: fixed;
				inset: 0;
				z-index: -1;
				background: rgba(10, 24, 40, .42);
				opacity: 0;
				visibility: hidden;
				transition: opacity .18s, visibility 0s .18s;
			}

			.ssx-header.is-mega .ssx-backdrop {
				opacity: 1;
				visibility: visible;
				transition-delay: 0s;
			}

			@media (min-width: 992px) and (max-width: 1199px) {
				.ssx-logo img {
					width: 250px;
				}

				.ssx-link {
					padding: 0 9px;
				}

				.ssx-topbar__left {
					gap: 18px;
				}

				.ssx-topbar__right .ssx-tb {
					font-size: 13px;
				}

				.ssx-ship__long {
					display: none;
				}
			}

			@media (prefers-reduced-motion: reduce) {

				.ssx-header *,
				.ssx-header *::before,
				.ssx-header *::after {
					transition-duration: 0s !important;
					transition-delay: 0s !important;
				}
			}
		</style>

		<?php // Live search results: styles in css/live-search.css, loaded without blocking the first paint (the panel only appears after typing). ?>
		<link rel="stylesheet" id="ssx-live-search-css" href="<?php echo esc_url(get_template_directory_uri() . '/css/live-search.css?ver=' . (int) @filemtime(get_template_directory() . '/css/live-search.css')); ?>" media="print" onload="this.media='all'">

		<header class="ssx-header" id="ssxHeader">

			<!-- Top bar: call, text, email, hours -->
			<div class="ssx-topbar<?php echo $ship_two ? ' ssx-topbar--long-offer' : ''; ?>">
				<div class="ssx-container ssx-topbar__in">
					<div class="ssx-topbar__left">
						<a class="ssx-tb" href="<?php echo esc_url($phone_href); ?>">
							<?php echo $ssx_icon('phone', 17); // Static SVG. ?>
							<span class="ssx-tb__label">Toll Free:</span>
							<strong><?php echo esc_html($phone_display); ?></strong>
						</a>
						<a class="ssx-tb" href="<?php echo esc_url($text_href); ?>">
							<?php echo $ssx_icon('sms', 17); // Static SVG. ?>
							<span class="ssx-tb__label">Text:</span>
							<strong><?php echo esc_html($text_display); ?></strong>
						</a>
						<a class="ssx-tb ssx-tb--email" href="<?php echo esc_url('mailto:' . $ssx_email); ?>">
							<?php echo $ssx_icon('mail', 17); // Static SVG. ?>
							<strong><?php echo esc_html($ssx_email); ?></strong>
						</a>
					</div>
					<div class="ssx-topbar__right">
						<span class="ssx-tb ssx-tb--muted ssx-tb--hours">
							<?php echo $ssx_icon('clock', 16); // Static SVG. ?>
							Mon&ndash;Fri 8am&ndash;5pm PT
						</span>
						<?php if ($ship_has) : ?>
							<a class="ssx-ship" href="<?php echo esc_url($ship_url); ?>" title="<?php esc_attr_e('See free shipping details', 'litsign'); ?>">
								<?php echo $ssx_icon('truck', 18); // Static SVG. ?>
								<span class="ssx-ship__text">
									<?php if ($ship_min) : ?>
										<strong>Free shipping</strong><span class="ssx-ship__long"> on orders of</span> <b><?php echo esc_html($ship_min); ?>+</b><?php if ($ship_two) : ?> <span class="ssx-ship__sep" aria-hidden="true">&middot;</span> Channel letters <b><?php echo esc_html($ship_cl_min); ?>+</b><?php endif; ?>
									<?php else : ?>
										<strong>Free shipping</strong> on channel letters <b><?php echo esc_html($ship_cl_min); ?>+</b>
									<?php endif; ?>
								</span>
								<span class="ssx-ship__go"><?php echo $ssx_icon('next', 13); // Static SVG. ?></span>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Main row: logo, search, account + cart -->
			<div class="ssx-main">
				<div class="ssx-container ssx-main__in">

					<a class="ssx-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name') . ' home'); ?>">
						<?php
						if ($logo_id) {
							echo wp_get_attachment_image($logo_id, 'full', false, array(
								'alt'           => get_bloginfo('name'),
								'class'         => 'header-logo',
								'loading'       => 'eager',
								'decoding'      => 'async',
								'fetchpriority' => 'high',
								'sizes'         => '(max-width: 767px) 240px, (max-width: 991px) 400px, (max-width: 1199px) 250px, 400px',
							));
						} else {
							echo retails_logo_fallback_html('header-logo');
						}
						?>
					</a>

					<form class="ssx-search" id="ssxSearch" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
						<label class="ssx-vh" for="ssxSearchInput"><?php esc_html_e('Search products', 'litsign'); ?></label>
						<div class="ssx-search__field">
							<?php echo $ssx_icon('search', 20); // Static SVG. ?>
							<input class="ssx-search__input" id="ssxSearchInput" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search channel letters, banners, flags…', 'litsign'); ?>" autocomplete="off" enterkeyhint="search">
							<?php if ($search_post_type) : ?>
								<input type="hidden" name="post_type" value="<?php echo esc_attr($search_post_type); ?>">
							<?php endif; ?>
							<button type="button" class="ssx-search__clear" id="ssxSearchClear" aria-label="<?php esc_attr_e('Clear search', 'litsign'); ?>" hidden>
								<?php echo $ssx_icon('close', 16); // Static SVG. ?>
							</button>
							<button type="submit" class="ssx-search__submit">
								<?php echo $ssx_icon('search', 18); // Static SVG. ?>
								<span>Search</span>
							</button>
						</div>

						<div class="ssx-suggest" id="ssxSuggest" hidden>
							<p class="ssx-suggest__title">Popular searches</p>
							<div class="ssx-chips">
								<?php foreach ($popular_searches as $term) :
									$chip_url = add_query_arg(array_filter(array('s' => $term, 'post_type' => $search_post_type)), home_url('/'));
								?>
									<a class="ssx-chip" href="<?php echo esc_url($chip_url); ?>"><?php echo esc_html($term); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					</form>

					<div class="ssx-actions">
						<ul class="ssx-account">
							<?php foreach ($account_links as $url => $label) : ?>
								<li><a class="ssx-link" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></li>
							<?php endforeach; ?>
						</ul>
						<a class="ssx-link" href="<?php echo esc_url(home_url('/cart/')); ?>" data-cart-link aria-label="<?php esc_attr_e('Cart', 'litsign'); ?>">
							<?php echo $ssx_icon('cart', 22); // Static SVG. ?>
							<span>Cart</span>
							<span class="ssx-badge" data-cart-count hidden></span>
						</a>
					</div>
				</div>
			</div>

			<!-- Navigation row -->
			<div class="ssx-nav">
				<div class="ssx-container ssx-nav__in">
					<button type="button" class="ssx-allbtn" id="ssxMegaBtn" aria-expanded="false" aria-controls="ssxMega">
						<?php echo $ssx_icon('grid', 18); // Static SVG. ?>
						All Products
						<?php echo $ssx_icon('chevron', 16); // Static SVG. ?>
					</button>

					<div class="ssx-mega" id="ssxMega">
						<div class="ssx-container ssx-mega__grid">
							<?php foreach ($mega_columns as $column) : ?>
								<div class="ssx-mega__col">
									<?php foreach ($column as $group) : ?>
										<div class="ssx-mega__group">
											<h3 class="ssx-mega__title"><?php echo esc_html($group['title']); ?></h3>
											<ul class="ssx-mega__list">
												<?php foreach ($group['links'] as $link) : ?>
													<li><a href="<?php echo esc_url($link[1]); ?>"<?php echo !empty($link[3]) ? ' class="is-all"' : ''; ?>><?php echo esc_html($link[0]); ?></a></li>
												<?php endforeach; ?>
											</ul>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="ssx-mega__foot">
							<div class="ssx-container ssx-mega__foot-in">
								<p>Not sure what you need? Call <a href="<?php echo esc_url($phone_href); ?>"><?php echo esc_html($phone_display); ?></a> and we&rsquo;ll help.</p>
								<a class="ssx-cta" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_label); ?></a>
							</div>
						</div>
					</div>

					<nav class="ssx-nav__links" aria-label="<?php esc_attr_e('Product categories', 'litsign'); ?>">
						<?php wp_nav_menu(array(
							'theme_location' => 'header-bottom-menu',
							'container'      => false,
							'menu_class'     => 'ssx-menu',
							'fallback_cb'    => false,
							'depth'          => 1,
						)); ?>
					</nav>

					<a class="ssx-cta" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_label); ?></a>
				</div>
			</div>

			<div class="ssx-backdrop" id="ssxBackdrop" aria-hidden="true"></div>
		</header>

		<script>
			(function() {
				var root = document.getElementById('ssxHeader');
				if (!root) return;
				var $ = function(id) {
					return document.getElementById(id);
				};
				var canHover = window.matchMedia('(hover: hover) and (pointer: fine)');

				/* ---------- Mega menu ---------- */
				var megaBtn = $('ssxMegaBtn'),
					mega = $('ssxMega'),
					backdrop = $('ssxBackdrop'),
					megaTimer;

				function setMega(open) {
					clearTimeout(megaTimer);
					mega.classList.toggle('is-open', open);
					root.classList.toggle('is-mega', open);
					megaBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
				}
				var megaOpen = function() {
					return mega.classList.contains('is-open');
				};

				megaBtn.addEventListener('click', function() {
					setMega(!megaOpen());
				});
				backdrop.addEventListener('click', function() {
					setMega(false);
				});
				[megaBtn, mega].forEach(function(el) {
					el.addEventListener('mouseenter', function() {
						if (!canHover.matches) return;
						clearTimeout(megaTimer);
						if (el === megaBtn && !megaOpen()) megaTimer = setTimeout(function() {
							setMega(true);
						}, 90);
					});
					el.addEventListener('mouseleave', function() {
						if (!canHover.matches) return;
						clearTimeout(megaTimer);
						megaTimer = setTimeout(function() {
							setMega(false);
						}, 180);
					});
				});
				mega.addEventListener('click', function(e) {
					if (e.target.closest('a')) setMega(false);
				});
				document.addEventListener('click', function(e) {
					if (megaOpen() && !megaBtn.contains(e.target) && !mega.contains(e.target)) setMega(false);
				});
				root.addEventListener('focusin', function(e) {
					if (megaOpen() && !megaBtn.contains(e.target) && !mega.contains(e.target)) setMega(false);
				});

				/* ---------- Search ---------- */
				var form = $('ssxSearch'),
					input = $('ssxSearchInput'),
					clear = $('ssxSearchClear'),
					panel = $('ssxSuggest');

				function syncClear() {
					clear.hidden = !input.value;
				}

				function showPanel() {
					panel.hidden = !!input.value;
				}

				function hidePanel() {
					panel.hidden = true;
				}
				syncClear();

				input.addEventListener('focus', showPanel);
				input.addEventListener('input', function() {
					syncClear();
					showPanel();
				});
				clear.addEventListener('click', function() {
					input.value = '';
					syncClear();
					input.focus();
					showPanel();
				});
				panel.addEventListener('mousedown', function(e) {
					e.preventDefault(); // keep focus in the field so the click lands (Safari)
				});
				form.addEventListener('focusout', function(e) {
					if (!form.contains(e.relatedTarget)) hidePanel();
				});
				form.addEventListener('submit', function(e) {
					if (!input.value.trim()) {
						e.preventDefault();
						input.focus();
					}
				});
				// Press "/" anywhere to jump to search.
				document.addEventListener('keydown', function(e) {
					if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
					var t = e.target,
						tag = t && t.tagName;
					if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (t && t.isContentEditable)) return;
					if (!input.offsetParent) return; // header hidden (mobile view)
					e.preventDefault();
					input.focus();
				});

				document.addEventListener('keydown', function(e) {
					if (e.key !== 'Escape') return;
					if (megaOpen()) {
						setMega(false);
						megaBtn.focus();
					} else if (!panel.hidden) {
						hidePanel();
					}
				});

				/* ---------- Cart badge (cached pages) ---------- */
				try {
					var m = document.cookie.match(/(?:^|;\s*)sso_cart_count=(\d+)/);
					if (m && parseInt(m[1], 10) > 0) {
						Array.prototype.forEach.call(root.querySelectorAll('[data-cart-count]'), function(el) {
							if (!el.textContent.trim()) el.textContent = m[1];
							el.hidden = false;
						});
					}
				} catch (err) {}
			})();
		</script>

		<?php
		/* ===================================================================
		 * NEW MOBILE MENU (opened by the existing hamburger button).
		 * The original top bar is unchanged; only the slide-out menu is new.
		 * =================================================================== */
		$ssxm_cta_label = $cta_quote ? 'Get a free quote' : 'Design your sign online';
		?>

		<style>
			/* The original mobile menu is replaced by the one below. */
			#siteMobileMenu {
				display: none !important;
			}

			html.ssxm-lock {
				overflow: hidden;
			}

			.ssxm-scrim {
				position: fixed;
				inset: 0;
				z-index: 100000;
				background: rgba(15, 23, 32, .42);
				opacity: 0;
				visibility: hidden;
				transition: opacity .28s ease, visibility 0s .28s;
				-webkit-tap-highlight-color: transparent;
			}

			.ssxm-scrim.is-open {
				opacity: 1;
				visibility: visible;
				transition-delay: 0s;
			}

			/* Phone menu: one type scale (12 / 14 / 15 / 16), one radius, hairlines only. */
			.ssxm {
				--m-accent: #1fa8de;
				--m-accent-strong: #0e7aa6;
				--m-accent-strong-hover: #0b6a91;
				--m-accent-tint: #eef8fc;
				--m-ink: #101828;
				--m-body: #344054;
				--m-muted: #667085;
				--m-faint: #98a2b3;
				--m-line: #eaecf0;
				--m-border: #d0d5dd;
				--m-hover: #f4f6f8;
				--m-radius: 10px;
				--m-pad: 20px;

				position: fixed;
				top: 0;
				right: 0;
				bottom: 0;
				z-index: 100001;
				display: flex;
				flex-direction: column;
				width: min(90vw, 400px);
				background: #fff;
				color: var(--m-ink);
				font-family: var(--font-sans, "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
				font-size: 16px;
				line-height: 1.5;
				letter-spacing: -.011em;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				text-rendering: optimizeLegibility;
				box-shadow: -16px 0 48px rgba(16, 24, 40, .16);
				transform: translateX(100%);
				visibility: hidden;
				transition: transform .34s cubic-bezier(.32, .72, 0, 1), visibility 0s .34s;
			}

			.ssxm.is-open {
				transform: none;
				visibility: visible;
				transition-delay: 0s;
			}

			.ssxm *,
			.ssxm *::before,
			.ssxm *::after {
				box-sizing: border-box;
			}

			.ssxm a {
				color: inherit;
				text-decoration: none;
				-webkit-tap-highlight-color: transparent;
			}

			.ssxm ul {
				list-style: none;
				margin: 0;
				padding: 0;
			}

			.ssxm p,
			.ssxm h2 {
				margin: 0;
			}

			.ssxm button {
				font-family: inherit;
				margin: 0;
				-webkit-tap-highlight-color: transparent;
			}

			.ssxm :focus-visible {
				outline: 2px solid var(--m-accent);
				outline-offset: 2px;
			}

			.ssxm [hidden] {
				display: none !important;
			}

			.ssxm .ssx-svg {
				display: block;
				flex: none;
			}

			/* One line weight for every outline icon in the menu. */
			.ssxm .ssx-svg [stroke-width] {
				stroke-width: 1.8px;
			}

			.ssxm__search button .ssx-svg [stroke-width] {
				stroke-width: 2.2px;
			}

			.ssxm-vh {
				position: absolute !important;
				width: 1px;
				height: 1px;
				margin: -1px;
				overflow: hidden;
				clip: rect(0 0 0 0);
				white-space: nowrap;
				border: 0;
			}

			/* Header */
			.ssxm__head {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 12px;
				min-height: 64px;
				padding: 8px 10px 8px var(--m-pad);
				border-bottom: 1px solid var(--m-line);
			}

			.ssxm__logo {
				display: block;
				min-width: 0;
			}

			.ssxm__logo img {
				display: block;
				width: 170px;
				max-width: 100%;
				height: auto;
			}

			.ssxm__close {
				flex: none;
				display: grid;
				place-items: center;
				width: 44px;
				height: 44px;
				padding: 0;
				border: 0;
				border-radius: var(--m-radius);
				background: transparent;
				color: var(--m-muted);
				cursor: pointer;
				transition: background-color .15s, color .15s;
			}

			.ssxm__close:hover,
			.ssxm__close:active {
				background: var(--m-hover);
				color: var(--m-ink);
			}

			/* Body */
			.ssxm__body {
				flex: 1;
				overflow-y: auto;
				overscroll-behavior: contain;
				-webkit-overflow-scrolling: touch;
				scrollbar-width: thin;
				padding: 16px var(--m-pad) 28px;
			}

			.ssxm__search {
				display: flex;
				align-items: center;
				gap: 10px;
				height: 46px;
				padding: 0 6px 0 14px;
				border: 1px solid transparent;
				border-radius: var(--m-radius);
				background: var(--m-hover);
				color: var(--m-muted);
				transition: border-color .15s, background-color .15s, box-shadow .15s;
			}

			.ssxm__search:focus-within {
				border-color: var(--m-accent);
				background: #fff;
				box-shadow: 0 0 0 3px rgba(31, 168, 222, .16);
			}

			.ssxm__search input[type="search"] {
				flex: 1;
				min-width: 0;
				height: 100%;
				padding: 0;
				border: 0;
				outline: 0;
				box-shadow: none;
				background: transparent;
				color: var(--m-ink);
				font: inherit;
				font-size: 16px;
				letter-spacing: inherit;
				-webkit-appearance: none;
				appearance: none;
			}

			.ssxm__search input::placeholder {
				color: var(--m-muted);
				opacity: 1;
			}

			.ssxm__search input::-webkit-search-cancel-button {
				-webkit-appearance: none;
				display: none;
			}

			/* Submit arrow: only shown once something is typed (Enter / the keyboard's Search key also submit). */
			.ssxm__search button {
				flex: none;
				display: grid;
				place-items: center;
				width: 34px;
				height: 34px;
				padding: 0;
				border: 0;
				border-radius: 8px;
				background: var(--m-ink);
				color: #fff;
				cursor: pointer;
				transition: opacity .15s, transform .15s;
			}

			.ssxm__search input:placeholder-shown~button {
				opacity: 0;
				visibility: hidden;
				transform: scale(.85);
			}

			.ssxm__cta {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 8px;
				min-height: 48px;
				margin-top: 12px;
				padding: 0 16px;
				border-radius: var(--m-radius);
				background: var(--m-accent-strong);
				color: #fff !important;
				font-size: 15px;
				font-weight: 600;
				letter-spacing: -.006em;
				transition: background-color .15s;
			}

			.ssxm__cta:hover,
			.ssxm__cta:active {
				background: var(--m-accent-strong-hover);
			}

			.ssxm__cta .ssx-svg {
				transition: transform .15s;
			}

			.ssxm__cta:hover .ssx-svg {
				transform: translateX(2px);
			}

			.ssxm .ssxm__label {
				margin: 28px 0 4px;
				color: var(--m-muted);
				font-size: 12px;
				font-weight: 600;
				line-height: 1.4;
				letter-spacing: .06em;
				text-transform: uppercase;
			}

			/* Rows: links, accordion summary. Text lines up with the search box; the hover fill bleeds 12px out. */
			.ssxm__row,
			.ssxm__list a,
			.ssxm-acc>summary {
				display: flex;
				align-items: center;
				gap: 14px;
				min-height: 48px;
				margin: 0 -12px;
				padding: 0 12px;
				border-radius: var(--m-radius);
				color: var(--m-ink);
				font-size: 16px;
				font-weight: 500;
				line-height: 1.25;
				transition: background-color .15s, color .15s;
			}

			.ssxm__row:hover,
			.ssxm__row:active,
			.ssxm__list a:hover,
			.ssxm__list a:active,
			.ssxm-acc>summary:hover,
			.ssxm-acc>summary:active {
				background: var(--m-hover);
			}

			.ssxm__row .ssx-svg {
				color: var(--m-muted);
			}

			.ssxm__list .current-menu-item>a,
			.ssxm__list .current_page_item>a {
				background: var(--m-accent-tint);
				color: var(--m-accent-strong);
				font-weight: 600;
			}

			.ssxm__badge {
				min-width: 20px;
				height: 20px;
				margin-left: auto;
				padding: 0 6px;
				border-radius: 10px;
				background: #f04e23;
				color: #fff;
				font-size: 11px;
				font-weight: 600;
				line-height: 20px;
				letter-spacing: 0;
				text-align: center;
				font-variant-numeric: tabular-nums;
			}

			/* All Products accordion */
			.ssxm-acc>summary {
				cursor: pointer;
				list-style: none;
			}

			.ssxm-acc>summary::-webkit-details-marker {
				display: none;
			}

			.ssxm-acc>summary .ssxm__next {
				display: flex;
				margin-left: auto;
				color: var(--m-faint);
				transition: transform .2s ease;
			}

			.ssxm-acc[open]>summary .ssxm__next {
				transform: rotate(180deg);
			}

			.ssxm-acc__body {
				margin: 4px 0 12px;
				padding: 0 0 4px 14px;
				border-left: 1px solid var(--m-line);
			}

			.ssxm .ssxm-group__title {
				margin: 18px 0 2px;
				color: var(--m-ink);
				font-size: 14px;
				font-weight: 600;
				line-height: 1.4;
			}

			.ssxm .ssxm-acc__body>.ssxm-group__title:first-child {
				margin-top: 8px;
			}

			.ssxm-sub a {
				display: flex;
				align-items: center;
				min-height: 40px;
				margin: 0 -10px;
				padding: 8px 10px;
				border-radius: 8px;
				color: var(--m-body);
				font-size: 15px;
				font-weight: 400;
				line-height: 1.35;
				transition: background-color .15s, color .15s;
			}

			.ssxm-sub a:hover,
			.ssxm-sub a:active {
				background: var(--m-hover);
				color: var(--m-ink);
			}

			.ssxm-sub a.is-all {
				color: var(--m-accent-strong);
				font-weight: 500;
			}

			/* Account */
			.ssxm__pair {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 8px;
				margin: 8px 0 6px;
			}

			.ssxm__btn {
				display: flex;
				align-items: center;
				justify-content: center;
				min-height: 44px;
				padding: 0 12px;
				border: 1px solid var(--m-border);
				border-radius: var(--m-radius);
				color: var(--m-ink);
				font-size: 15px;
				font-weight: 500;
				text-align: center;
				transition: border-color .15s, background-color .15s;
			}

			.ssxm__btn:hover,
			.ssxm__btn:active {
				border-color: #c4cad3;
				background: var(--m-hover);
			}

			.ssxm__row--mail {
				color: var(--m-body);
				font-size: 15px;
				font-weight: 400;
				overflow-wrap: anywhere;
			}

			/* Footer: always-visible contact */
			.ssxm__foot {
				padding: 10px var(--m-pad) calc(10px + env(safe-area-inset-bottom, 0px));
				border-top: 1px solid var(--m-line);
				background: #fafbfc;
			}

			.ssxm .ssxm__hours {
				display: flex;
				align-items: baseline;
				justify-content: space-between;
				gap: 12px;
				margin-bottom: 2px;
				color: var(--m-muted);
				font-size: 12px;
				line-height: 1.4;
			}

			.ssxm__hours b {
				font-weight: 600;
				letter-spacing: .06em;
				text-transform: uppercase;
			}

			.ssxm__contact {
				display: flex;
				align-items: center;
				gap: 12px;
				min-height: 44px;
				margin: 0 -12px;
				padding: 0 12px;
				border-radius: var(--m-radius);
				transition: background-color .15s;
			}

			.ssxm__contact:hover,
			.ssxm__contact:active {
				background: var(--m-hover);
			}

			.ssxm__contact .ssx-svg {
				color: var(--m-accent-strong);
			}

			.ssxm__contact span {
				color: var(--m-body);
				font-size: 15px;
				font-weight: 400;
			}

			.ssxm__contact strong {
				margin-left: auto;
				color: var(--m-ink);
				font-size: 15px;
				font-weight: 600;
				white-space: nowrap;
			}

			@media (min-width: 992px) {

				.ssxm,
				.ssxm-scrim {
					display: none !important;
				}
			}

			@media (max-width: 300px) {
				.ssxm__pair {
					grid-template-columns: 1fr;
				}
			}

			@media (prefers-reduced-motion: reduce) {

				.ssxm,
				.ssxm *,
				.ssxm-scrim {
					transition-duration: 0s !important;
					transition-delay: 0s !important;
				}
			}
		</style>

		<div class="ssxm-scrim" id="ssxmScrim" aria-hidden="true"></div>

		<div class="ssxm" id="ssxMobileMenu" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Menu', 'litsign'); ?>">
			<div class="ssxm__head">
				<a class="ssxm__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name') . ' home'); ?>">
					<?php
					if ($logo_id) {
						echo wp_get_attachment_image($logo_id, 'full', false, array(
							'alt'      => get_bloginfo('name'),
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '170px',
						));
					} else {
						echo retails_logo_fallback_html();
					}
					?>
				</a>
				<button type="button" class="ssxm__close" id="ssxmClose" aria-label="<?php esc_attr_e('Close menu', 'litsign'); ?>">
					<?php echo $ssx_icon('close', 20); // Static SVG. ?>
				</button>
			</div>

			<div class="ssxm__body">
				<form class="ssxm__search" id="ssxmSearch" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
					<label class="ssxm-vh" for="ssxmSearchInput"><?php esc_html_e('Search products', 'litsign'); ?></label>
					<?php echo $ssx_icon('search', 18); // Static SVG. ?>
					<input id="ssxmSearchInput" type="search" name="s" placeholder="<?php esc_attr_e('Search products…', 'litsign'); ?>" autocomplete="off" enterkeyhint="search">
					<?php if ($search_post_type) : ?>
						<input type="hidden" name="post_type" value="<?php echo esc_attr($search_post_type); ?>">
					<?php endif; ?>
					<button type="submit" aria-label="<?php esc_attr_e('Search', 'litsign'); ?>"><?php echo $ssx_icon('arrow', 16); // Static SVG. ?></button>
				</form>

				<a class="ssxm__cta" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($ssxm_cta_label); ?><?php echo $ssx_icon('arrow', 18); // Static SVG. ?></a>

				<p class="ssxm__label">Shop</p>
				<details class="ssxm-acc">
					<summary>
						All Products
						<span class="ssxm__next"><?php echo $ssx_icon('chevron', 18); // Static SVG. ?></span>
					</summary>
					<div class="ssxm-acc__body">
						<?php foreach ($mega_columns as $column) : ?>
							<?php foreach ($column as $group) : ?>
								<p class="ssxm-group__title"><?php echo esc_html($group['title']); ?></p>
								<ul class="ssxm-sub">
									<?php foreach ($group['links'] as $link) : ?>
										<li><a href="<?php echo esc_url($link[1]); ?>"<?php echo !empty($link[3]) ? ' class="is-all"' : ''; ?>><?php echo esc_html($link[0]); ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</div>
				</details>
				<?php wp_nav_menu(array(
					'theme_location'  => 'header-bottom-menu',
					'container'       => 'nav',
					'container_class' => 'ssxm__list',
					'container_aria_label' => __('Product categories', 'litsign'),
					'items_wrap'      => '<ul>%3$s</ul>',
					'fallback_cb'     => false,
					'depth'           => 1,
				)); ?>

				<p class="ssxm__label">Your account</p>
				<div class="ssxm__pair">
					<?php foreach ($account_links as $url => $label) : ?>
						<a class="ssxm__btn" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
					<?php endforeach; ?>
				</div>
				<a class="ssxm__row" href="<?php echo esc_url(home_url('/cart/')); ?>">
					<?php echo $ssx_icon('cart-o', 20); // Static SVG. ?>
					Cart
					<span class="ssxm__badge" data-cart-count hidden></span>
				</a>
				<a class="ssxm__row" href="<?php echo esc_url(home_url('/checkout/')); ?>">
					<?php echo $ssx_icon('card', 20); // Static SVG. ?>
					Checkout
				</a>
				<a class="ssxm__row ssxm__row--mail" href="<?php echo esc_url('mailto:' . $ssx_email); ?>">
					<?php echo $ssx_icon('mail', 20); // Static SVG. ?>
					<?php echo esc_html($ssx_email); ?>
				</a>
			</div>

			<div class="ssxm__foot">
				<p class="ssxm__hours"><b>Need help?</b> Mon&ndash;Fri 8am&ndash;5pm PT</p>
				<a class="ssxm__contact" href="<?php echo esc_url($phone_href); ?>">
					<?php echo $ssx_icon('phone-o', 18); // Static SVG. ?>
					<span>Call toll-free</span>
					<strong><?php echo esc_html($phone_display); ?></strong>
				</a>
				<a class="ssxm__contact" href="<?php echo esc_url($text_href); ?>">
					<?php echo $ssx_icon('sms', 18); // Static SVG. ?>
					<span>Text us</span>
					<strong><?php echo esc_html($text_display); ?></strong>
				</a>
			</div>
		</div>

		<script>
			(function() {
				var menu = document.getElementById('ssxMobileMenu'),
					scrim = document.getElementById('ssxmScrim'),
					closeBtn = document.getElementById('ssxmClose');
				if (!menu) return;
				var trigger = null,
					desktop = window.matchMedia('(min-width: 992px)');

				var isOpen = function() {
					return menu.classList.contains('is-open');
				};

				function setTriggers(expanded) {
					Array.prototype.forEach.call(document.querySelectorAll('.mobile-menu-trigger'), function(t) {
						t.setAttribute('aria-controls', 'ssxMobileMenu');
						t.setAttribute('aria-expanded', expanded ? 'true' : 'false');
					});
				}

				function open() {
					menu.classList.add('is-open');
					scrim.classList.add('is-open');
					document.documentElement.classList.add('ssxm-lock');
					setTriggers(true);
					setTimeout(function() {
						closeBtn.focus();
					}, 80);
				}

				function close(restore) {
					if (!isOpen()) return;
					menu.classList.remove('is-open');
					scrim.classList.remove('is-open');
					document.documentElement.classList.remove('ssxm-lock');
					setTriggers(false);
					if (restore !== false && trigger && trigger.focus) trigger.focus();
				}

				// Take over the existing hamburger button (capture phase, so the old menu code never runs).
				window.addEventListener('click', function(e) {
					var t = e.target.closest ? e.target.closest('.mobile-menu-trigger') : null;
					if (!t) return;
					e.preventDefault();
					e.stopImmediatePropagation();
					trigger = t;
					isOpen() ? close() : open();
				}, true);

				closeBtn.addEventListener('click', function() {
					close();
				});
				scrim.addEventListener('click', function() {
					close();
				});
				menu.addEventListener('click', function(e) {
					if (e.target.closest('a[href]')) close(false);
				});
				document.addEventListener('keydown', function(e) {
					if (!isOpen()) return;
					if (e.key === 'Escape') {
						close();
						return;
					}
					if (e.key !== 'Tab') return;
					var items = Array.prototype.filter.call(
						menu.querySelectorAll('a[href], button:not([disabled]), summary, input:not([type="hidden"])'),
						function(el) {
							return el.offsetParent !== null;
						});
					if (!items.length) return;
					var first = items[0],
						last = items[items.length - 1];
					if (e.shiftKey && document.activeElement === first) {
						e.preventDefault();
						last.focus();
					} else if (!e.shiftKey && document.activeElement === last) {
						e.preventDefault();
						first.focus();
					}
				});
				var onBreakpoint = function() {
					if (desktop.matches) close(false);
				};
				if (desktop.addEventListener) desktop.addEventListener('change', onBreakpoint);
				else if (desktop.addListener) desktop.addListener(onBreakpoint);

				// Cart count badge (pages are cached, so it comes from the cookie).
				try {
					var m = document.cookie.match(/(?:^|;\s*)sso_cart_count=(\d+)/);
					if (m && parseInt(m[1], 10) > 0) {
						Array.prototype.forEach.call(menu.querySelectorAll('[data-cart-count]'), function(el) {
							if (!el.textContent.trim()) el.textContent = m[1];
							el.hidden = false;
						});
					}
				} catch (err) {}
			})();
		</script>

		<?php
		/* ===================================================================
		 * LIVE SEARCH (async results for the header search + mobile menu search)
		 * Endpoint: /wp-json/ssx/v1/search  (see ssx-live-search.php).
		 * If that file is not loaded yet, the script falls back to the core
		 * /wp-json/wp/v2/search endpoint (titles + links only, no images/prices).
		 * =================================================================== */
		$ssx_live = array(
			'endpoint'  => esc_url_raw(rest_url('ssx/v1/search')),
			'fallback'  => esc_url_raw(rest_url('wp/v2/search')),
			'searchUrl' => esc_url_raw(home_url('/')),
			'postType'  => $search_post_type,
			'popular'   => array_values($popular_searches),
			'phone'     => $phone_display,
			'phoneHref' => $phone_href,
			'min'       => 2,
			'limit'     => 6,
		);
		?>
		<script>
			window.ssxLiveSearch = <?php echo wp_json_encode($ssx_live, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
		</script>
		<script src="<?php echo esc_url(get_template_directory_uri() . '/js/live-search.js?ver=' . (int) @filemtime(get_template_directory() . '/js/live-search.js')); ?>" defer></script>

		<?php
		// Notices from redirects (?type=success&message=...). Landing pages also use
		// ?type= to match an ad (/banners-displays/?type=stands), so an empty message
		// shows nothing instead of an empty alert bar.
		$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
		if (isset($_GET['type']) && '' !== $message) {
			$type = sanitize_key(wp_unslash($_GET['type']));
			if (!in_array($type, array('success', 'danger', 'warning', 'info'), true)) {
				$type = 'info';
			}
		?>
			<div class="container mt-3 site-alert-container">
				<div class="row">
					<div class="col-md-6 offset-md-3 col-12 text-center">
						<div class="alert alert-<?php echo esc_attr($type); ?>">
							<?php echo esc_html($message); ?>
						</div>
					</div>
				</div>
			</div>


		<?php
		}

		if (false) {
		?>

			<div class="category-selecteor-container container mt-3">
				<div class="row">
					<div class="col-md-6 offset-md-4 px-1">
						<a href="<?php echo home_url() . '#channel-letters'; ?>">
							<div class="category-selector" data-cat="channel-letters">Channel Letter Products</div>
						</a>
						<a href="<?php echo home_url() . '#adhesive-products'; ?>">

							<div class="category-selector" data-cat="adhesive-products">Adhesive Products</div>
						</a>

					</div>
				</div>
			</div>
		<?php
		}
