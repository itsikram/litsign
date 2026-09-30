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
		<header class="main-header site-header">
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
						<p class="sh-hours">Mon&ndash;Fri 8am&ndash;5pm PST</p>
					</div>
				</div>

				<div class="row header-bottom">
					<div class="col d-flex justify-content-center align-items-center header-bottom-container">
						<div class="megamenu-container">
							<button type="button" id="allProductsBtn" aria-expanded="false" aria-controls="megaMenu"><?php echo $icon('grid'); // Static SVG. ?> All Products <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
							<div id="megaMenu" class="mega-menu">
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
							<a class="header-builder-cta" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Start Building</a>
						</div>

					</div>

				</div>
			</div>
		</header>

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
