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
			src: url('<?php echo get_template_directory_uri() . '/fonts/NimbusSanL-Bol.woff2'; ?>') format('woff2'),
				url('<?php echo get_template_directory_uri() . '/fonts/NimbusSanL-Bol.otf'; ?>') format('opentype');
			font-weight: bold;
			font-display: swap;
			/*URL to font*/
		}

		@font-face {
			font-family: 'type-writer';
			src: url('<?php echo get_template_directory_uri() . '/fonts/TYPEWR_B.TTF'; ?>');
			font-weight: bold;
			font-display: swap;
		}

		@font-face {
			font-family: 'alegreya';
			src: url('<?php echo get_template_directory_uri() . '/fonts/Alegreya-VariableFont_wght.ttf'; ?>');
			font-display: swap;
		}

		@font-face {
			font-family: 'anton';
			src: url('<?php echo get_template_directory_uri() . '/fonts/Anton-Regular.ttf'; ?>');
			font-display: swap;
		}

		@font-face {
			font-family: 'gotham-medium';
			src: url('<?php echo get_template_directory_uri() . '/fonts/gotham-medium.woff2'; ?>') format('woff2'),
				url('<?php echo get_template_directory_uri() . '/fonts/gotham-medium.otf'; ?>') format('opentype');
			font-weight: 900;
			font-display: swap;
		}

		@font-face {
			font-family: 'helvetica-condensed-bold';
			src: url('<?php echo get_template_directory_uri() . '/fonts/helvetica-condensed-bold.otf'; ?>');
			font-weight: 900;
			font-display: swap;
		}

		@font-face {
			font-family: 'helvetica-rounded-bold';
			src: url('<?php echo get_template_directory_uri() . '/fonts/helvetica-rounded-bold.otf'; ?>');
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
			font-family: 'havetica-rounded-bold', 'helvetica-condensed-bold';
		}
	</style>

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'litsign'); ?></a>


		<?php
		// Account links differ for signed-in customers. The cart count badge is filled in by
		// JavaScript from the sso_cart_count cookie, because pages are served from cache.
		$account_links = is_user_logged_in()
			? array(home_url('/my-orders/') => 'My Orders', home_url('/account/') => 'My Account')
			: array(home_url('/login/') => 'Log in', home_url('/signup/') => 'Register');
		$phone_display = '866-436-2101';
		$phone_href = 'tel:+18664362101';
		// Inline icons: the theme's icon font is a small subset without these glyphs.
		$icon = static function ($name) {
			$paths = array(
				'phone' => '<path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
				'cart' => '<path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM1 2v2h2l3.6 7.6-1.35 2.4A2 2 0 0 0 7 17h12v-2H7.4a.25.25 0 0 1-.22-.37l.9-1.63h7.45a2 2 0 0 0 1.75-1.03l3.58-6.5A1 1 0 0 0 20 4H5.2l-.94-2H1zm16 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>',
				'grid' => '<path fill="currentColor" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
				'arrow' => '<path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>',
			);
			return '<svg class="sh-svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
		};
		?>
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
									'sizes' => '(max-width: 767px) 240px, 400px',
								));
							} else {
								echo '<img src="' . esc_url(get_template_directory_uri() . '/img/logo.png') . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="header-logo" width="2417" height="261" loading="eager" decoding="async">';
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
								<span class="sh-phone-option__label">Cell:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($phone_display); ?></span>
							</a>
							<a class="sh-phone-option" href="<?php echo esc_url('sms:+18664362101'); ?>">
								<span class="sh-phone-option__label">Text:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($phone_display); ?></span>
							</a>
						</div>
						<p class="sh-hours">Mon&ndash;Fri 8am&ndash;5pm PST</p>
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
								<span class="sh-phone-option__label">Cell:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($phone_display); ?></span>
							</a>
							<a class="sh-phone-option" href="<?php echo esc_url('sms:+18664362101'); ?>">
								<span class="sh-phone-option__label">Text:</span>
								<span class="sh-phone-option__number"><?php echo esc_html($phone_display); ?></span>
							</a>
						</div>
						<p class="sh-hours">Mon&ndash;Fri 8am&ndash;5pm PST</p>
					</div>
				</div>

				<div class="row header-bottom">
					<div class="col d-flex justify-content-center align-items-center header-bottom-container">
						<div class="megamenu-container">
							<button type="button" id="allProductsBtn" aria-expanded="false" aria-controls="megaMenu"><?php echo $icon('grid'); // Static SVG. ?> All Products <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
							<div id="megaMenu" class="mega-menu">
								<div class="mega-menu-mobile-header">
									<span class="mega-menu-mobile-title">All Products</span>
									<button type="button" class="mega-menu-close" aria-label="<?php esc_attr_e('Close product menu', 'litsign'); ?>">
										<span aria-hidden="true">&times;</span>
									</button>
								</div>
								<div class="container p-3">
									<div class="row">
										<div class="col-md-4">
											<div class="mm-col-container">
												<h4 class="mm-heading">
													Signs / Letters
												</h4>
												<a href="<?php echo esc_url(wholesale_category_url('channel-letters')); ?>" class="mm-link">
													<span>
														Custom Channel Letter Signs
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
											</div>
											<div class="mm-col-container mt-3">
												<h4 class="mm-heading">
													Indoor / Outdoor Displays
												</h4>
												<a href="<?php echo esc_url(wholesale_category_url('advertising-flags')); ?>" class="mm-link">
													<span>
														Advertising Flags
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('banner-stands')); ?>" class="mm-link">
													<span>
														Banner Stands
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(home_url('/product/step-and-repeat-backdrop-graphic-frame/')); ?>" class="mm-link">
													<span>
														Step and Repeat Backdrop
													</span>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('real-estate-products')); ?>" class="mm-link">
													<span>
														Real Estate Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('a-frame-and-sign-holders')); ?>" class="mm-link">
													<span>
														A Frame and Sign Holders
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('signicade-a-frames')); ?>" class="mm-link">
													<span>
														Signicade A-Frames
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('seg-products')); ?>" class="mm-link">
													<span>
														SEG Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('trade-show-products')); ?>" class="mm-link">
													<span>
														Trade Show Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('custom-event-tents')); ?>" class="mm-link">
													<span>
														Custom Event Tents
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('table-throws')); ?>" class="mm-link">
													<span>
														Table Throws
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('hardware-only')); ?>" class="mm-link">
													<span>
														Hardware Only
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
											</div>


										</div>
										<div class="col-md-4">
											<div class="mm-col-container">
												<h4 class="mm-heading">
													Banners
												</h4>
												<a href="<?php echo esc_url(home_url('/product/13oz-vinyl-banner/')); ?>" class="mm-link">
													<span>
														13oz Vinyl Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/18oz-blockout-banner/')); ?>" class="mm-link">
													<span>
														18oz Blockout Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/backlit-banner/')); ?>" class="mm-link">
													<span>
														Backlit Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/mesh-banner/')); ?>" class="mm-link">
													<span>
														Mesh Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/indoor-banner-super-smooth/')); ?>" class="mm-link">
													<span>
														Indoor Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/pole-banner-set/')); ?>" class="mm-link">
													<span>
														Pole Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/fabric-banner-9oz-wrinkle-free-copy/')); ?>" class="mm-link">
													<span>
														9oz Fabric Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/blockout-fabric-banner/')); ?>" class="mm-link">
													<span>
														Blockout Fabric Banner
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/tension-fabric/')); ?>" class="mm-link">
													<span>
														Tension Fabric
													</span>
												</a>
											</div>
										</div>
										<div class="col-md-4">
											<div class="mm-col-container">
												<h4 class="mm-heading">
													Large Format
												</h4>
												<a href="<?php echo esc_url(wholesale_category_url('wall-art')); ?>" class="mm-link">
													<span>
														Wall Art
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('rigid-signs-and-magnets')); ?>" class="mm-link">
													<span>
														Rigid Signs and Magnets
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('reflective-products')); ?>" class="mm-link">
													<span>
														Reflective Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(wholesale_category_url('dry-erase-products')); ?>" class="mm-link">
													<span>
														Dry Erase Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="<?php echo esc_url(home_url('/product/dtf/')); ?>" class="mm-link">
													<span>
														DTF
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/backlit-film/')); ?>" class="mm-link">
													<span>
														Backlit Film
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/premium-window-cling/')); ?>" class="mm-link">
													<span>
														Prem. Window Cling
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/posters/')); ?>" class="mm-link">
													<span>
														Posters
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/styrene/')); ?>" class="mm-link">
													<span>
														Styrene
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/popup/')); ?>" class="mm-link">
													<span>
														Popup
													</span>
												</a>
												<a href="<?php echo esc_url(home_url('/product/canvas-roll/')); ?>" class="mm-link">
													<span>
														Canvas Roll
													</span>
												</a>
											</div>
										</div>
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
						<div class="header-builder-menu">
							<?php if (is_page('channel-letter-builder')) : ?>
								<?php // Already in the builder: offer help instead of reloading the page. ?>
								<a class="header-builder-cta" href="<?php echo esc_url(home_url('/contact/')); ?>">Free Quote</a>
							<?php else : ?>
								<a class="header-builder-cta" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Start Building</a>
							<?php endif; ?>
						</div>

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
		$sms_href  = 'sms:+18664362101';

		// Search: limit results to products. Set to '' to search the whole site instead.
		$search_post_type = 'product';
		$popular_searches = array('Channel letters', '13oz vinyl banner', 'Flags', 'Adhesive vinyl', 'Trade show');

		// Main call-to-action. Already in the builder: offer help instead of reloading the page.
		$on_builder = is_page('channel-letter-builder');
		$cta_url    = $on_builder ? home_url('/contact/') : home_url('/channel-letter-builder/');
		$cta_label  = $on_builder ? 'Free Quote' : 'Start Building';

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
			);
			return '<svg class="ssx-svg" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
		};

		// "All Products" mega menu: three columns, each with one or more titled groups.
		$mega_columns = array(
			array(
				array('title' => 'Signs / Letters', 'links' => array(
					array('Custom Channel Letter Signs', wholesale_category_url('channel-letters')),
				)),
				array('title' => 'Indoor / Outdoor Displays', 'links' => array(
					array('Advertising Flags', wholesale_category_url('advertising-flags')),
					array('Banner Stands', wholesale_category_url('banner-stands')),
					array('Step and Repeat Backdrop', home_url('/product/step-and-repeat-backdrop-graphic-frame/')),
					array('Real Estate Products', wholesale_category_url('real-estate-products')),
					array('A Frame and Sign Holders', wholesale_category_url('a-frame-and-sign-holders')),
					array('Signicade A-Frames', wholesale_category_url('signicade-a-frames')),
					array('SEG Products', wholesale_category_url('seg-products')),
					array('Trade Show Products', wholesale_category_url('trade-show-products')),
					array('Custom Event Tents', wholesale_category_url('custom-event-tents')),
					array('Table Throws', wholesale_category_url('table-throws')),
					array('Hardware Only', wholesale_category_url('hardware-only')),
				)),
			),
			array(
				array('title' => 'Banners', 'links' => array(
					array('13oz Vinyl Banner', home_url('/product/13oz-vinyl-banner/')),
					array('18oz Blockout Banner', home_url('/product/18oz-blockout-banner/')),
					array('Backlit Banner', home_url('/product/backlit-banner/')),
					array('Mesh Banner', home_url('/product/mesh-banner/')),
					array('Indoor Banner', home_url('/product/indoor-banner-super-smooth/')),
					array('Pole Banner', home_url('/product/pole-banner-set/')),
					array('9oz Fabric Banner', home_url('/product/fabric-banner-9oz-wrinkle-free-copy/')),
					array('Blockout Fabric Banner', home_url('/product/blockout-fabric-banner/')),
					array('Tension Fabric', home_url('/product/tension-fabric/')),
				)),
			),
			array(
				array('title' => 'Large Format', 'links' => array(
					array('Wall Art', wholesale_category_url('wall-art')),
					array('Rigid Signs and Magnets', wholesale_category_url('rigid-signs-and-magnets')),
					array('Reflective Products', wholesale_category_url('reflective-products')),
					array('Dry Erase Products', wholesale_category_url('dry-erase-products')),
					array('DTF', home_url('/product/dtf/')),
					array('Backlit Film', home_url('/product/backlit-film/')),
					array('Prem. Window Cling', home_url('/product/premium-window-cling/')),
					array('Posters', home_url('/product/posters/')),
					array('Styrene', home_url('/product/styrene/')),
					array('Popup', home_url('/product/popup/')),
					array('Canvas Roll', home_url('/product/canvas-roll/')),
				)),
			),
		);
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
				font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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
				background: var(--ssx-blue);
				color: #fff;
				font-weight: 700;
				cursor: pointer;
				transition: background-color .15s;
			}

			.ssx-search__submit:hover {
				background: var(--ssx-blue-hover);
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
				background: var(--ssx-blue);
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
				background: var(--ssx-blue);
				color: #fff !important;
				font-weight: 700;
				white-space: nowrap;
				transition: background-color .15s, box-shadow .15s;
			}

			.ssx-cta:hover {
				background: var(--ssx-blue-hover);
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

		<header class="ssx-header" id="ssxHeader">

			<!-- Top bar: call, text, email, hours -->
			<div class="ssx-topbar">
				<div class="ssx-container ssx-topbar__in">
					<div class="ssx-topbar__left">
						<a class="ssx-tb" href="<?php echo esc_url($phone_href); ?>">
							<?php echo $ssx_icon('phone', 17); // Static SVG. ?>
							<span class="ssx-tb__label">Cell:</span>
							<strong><?php echo esc_html($phone_display); ?></strong>
						</a>
						<a class="ssx-tb" href="<?php echo esc_url($sms_href); ?>">
							<?php echo $ssx_icon('sms', 17); // Static SVG. ?>
							<span class="ssx-tb__label">Text:</span>
							<strong><?php echo esc_html($phone_display); ?></strong>
						</a>
						<a class="ssx-tb" href="<?php echo esc_url('mailto:' . $ssx_email); ?>">
							<?php echo $ssx_icon('mail', 17); // Static SVG. ?>
							<strong><?php echo esc_html($ssx_email); ?></strong>
						</a>
					</div>
					<div class="ssx-topbar__right">
						<span class="ssx-tb ssx-tb--muted">
							<?php echo $ssx_icon('clock', 16); // Static SVG. ?>
							Mon&ndash;Fri 8am&ndash;5pm PST
						</span>
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
								'sizes'         => '(max-width: 1199px) 250px, 400px',
							));
						} else {
							echo '<img src="' . esc_url(get_template_directory_uri() . '/img/logo.png') . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="header-logo" width="2417" height="261" loading="eager" decoding="async">';
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
													<li><a href="<?php echo esc_url($link[1]); ?>"><?php echo esc_html($link[0]); ?></a></li>
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

		<?php if (isset($_GET['type'])) {
			$type = sanitize_key(wp_unslash($_GET['type']));
			if (!in_array($type, array('success', 'danger', 'warning', 'info'), true)) {
				$type = 'info';
			}

			$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
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