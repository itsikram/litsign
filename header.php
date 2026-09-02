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

$logo_url = get_theme_mod('custom_logo');

if ($logo_url) {
	$logo_url = wp_get_attachment_image_url($logo_url, 'full');
} else {
	$logo_url = get_template_directory_uri() . '/img/logo.png';
}

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
			src: url('<?php echo get_template_directory_uri() . '/fonts/NimbusSanL-Bol.otf'; ?>');
			font-weight: bold;
			/*URL to font*/
		}

		@font-face {
			font-family: 'type-writer';
			src: url('<?php echo get_template_directory_uri() . '/fonts/TYPEWR_B.TTF'; ?>');
			font-weight: bold;
		}

		@font-face {
			font-family: 'alegreya';
			src: url('<?php echo get_template_directory_uri() . '/fonts/Alegreya-VariableFont_wght.ttf'; ?>');
		}

		@font-face {
			font-family: 'anton';
			src: url('<?php echo get_template_directory_uri() . '/fonts/Anton-Regular.ttf'; ?>');
		}

		@font-face {
			font-family: 'gotham-medium';
			src: url('<?php echo get_template_directory_uri() . '/fonts/gotham-medium.otf'; ?>');
			font-weight: 900;
		}

		@font-face {
			font-family: 'helvetica-condensed-bold';
			src: url('<?php echo get_template_directory_uri() . '/fonts/helvetica-condensed-bold.otf'; ?>');
			font-weight: 900;
		}

		@font-face {
			font-family: 'helvetica-rounded-bold';
			src: url('<?php echo get_template_directory_uri() . '/fonts/helvetica-rounded-bold.otf'; ?>');
			font-weight: 900;
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


		<header class="main-header">
			<div class="container">
				<div class="row d-flex align-items-center justify-content-center">
					<div class="col-md-3 col-10">
						<div class="logo-container">
							<a href="<?php echo home_url() . '/'; ?>">
								<img src="<?php echo $logo_url; ?>" alt="<?php echo bloginfo('title'); ?>" class="header-logo">
							</a>
						</div>
					</div>
					<div class="col-md-6 col-2">

						<?php
						wp_nav_menu(array(
							'theme_location' => 'header-menu',
							'container_class' => 'header-menu-container hide-on-mobile',
							'menu_class' => 'header-menu text-center'
						));
						?>
						<div class="mobile-menu-container hide-on-desktop">
							<div class="text-end">
								<span class="mobile-menu-close text-danger fs-2 p-2">
									&times;
								</span>
							</div>
							<?php
							wp_nav_menu(array(
								'theme_location' => 'header-menu',
								'menu_class' => 'header-menu text-center'
							));
							?>
							<?php wp_nav_menu(array(
								'theme_location' => 'header-bottom-menu',
								//'container_class' => 'header-menu-container',
								'menu_class' => 'header-menu text-center'
							));
							?>

							<?php if (!is_user_logged_in()) { ?>
								<!-- <form class="header-account-form" method="POST" action="/login';">
									<div class="row align-items-center d-flex">

										<div class="col-md-12 mb-sm-1 col-sm-12 text-center">
											<button type="submit" class="btn btn-primary account-action-button p-0">login</button>
											<a href="<?php echo site_url() . '/signup'; ?>" class="btn btn-danger account-action-button p-0">Register</a>
										</div>

									</div>
								</form> -->
								<div class="account-menu header-menu-container">
									<ul class="header-menu text-end">
										<li class="menu-item"><a href="<?php echo home_url() . '/cart'; ?>">Cart</a></li>
										<li class="menu-item"><a href="<?php echo home_url() . '/checkout'; ?>">Checkout</a></li>
									</ul>
								</div>
							<?php } else {
							?>
								<div class="account-menu">
									<ul class="header-menu text-end">
										<li class="menu-item"><a href="<?php echo home_url() . '/cart'; ?>">Cart</a></li>
										<li class="menu-item"><a href="<?php echo site_url() . '/my-orders'; ?>">My Orders</a></li>
										<li class="menu-item"><a href="<?php echo site_url() . '/account'; ?>"></i>My Account</a></li>
									</ul>
								</div>

							<?php
							} ?>
						</div>
						<div class="div-hamberger-container">
							<img class="mobile-menu-trigger" src="<?php echo get_template_directory_uri() . '/img/harberger-icon.png'; ?>">
						</div>
					</div>
					<div class="col-md-3 hide-on-mobile">

						<?php if (!is_user_logged_in()) { ?>

							<!-- <form class="header-account-form" method="POST" action="/login">
								<div class="row align-items-center d-flex">

									<div class="col-md-12 mb-sm-1 col-sm-12 text-center">
										<button type="submit" class="btn btn-primary account-action-button p-0">login</button>
										<a href="<?php echo site_url() . '/signup'; ?>" class="btn btn-danger account-action-button p-0">Register</a>
									</div>

								</div>
							</form> -->
							<div class="account-menu header-menu-container">
								<ul class="header-menu text-end">
									<li class="menu-item"><a href="<?php echo home_url() . '/cart'; ?>">Cart</a></li>
									<li class="menu-item"><a href="<?php echo home_url() . '/checkout'; ?>">Checkout</a></li>
								</ul>
							</div>
						<?php } else {
						?>
							<div class="account-menu header-menu-container">
								<ul class="header-menu text-end">
									<li class="menu-item"><a href="<?php echo home_url() . '/cart'; ?>">Cart</a></li>
									<li class="menu-item"><a href="<?php echo site_url() . '/my-orders'; ?>">My Orders</a></li>
									<li class="menu-item"><a href="<?php echo site_url() . '/account'; ?>">My Account</a></li>
								</ul>
							</div>

						<?php
						} ?>
					</div>


				</div>
				<div class="row header-bottom">
					<div class="col d-flex justify-content-center align-items-center header-bottom-container">
						<div class="megamenu-container">
							<button id="allProductsBtn">All Products <i class="fa-solid fa-chevron-down"> </i></button>
							<div id="megaMenu" class="mega-menu">
								<div class="container p-3">
									<div class="row">
										<div class="col-md-4">
											<div class="mm-col-container">
												<h4 class="mm-heading">
													Signs / Letters
												</h4>
												<a href="/?category_slug=channel-letters" class="mm-link">
													<span>
														Channel Letters
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
											</div>
											<div class="mm-col-container mt-3">
												<h4 class="mm-heading">
													Indoor / Outdoor Displays
												</h4>
												<a href="/?category_slug=advertising-flags" class="mm-link">
													<span>
														Advertising Flags
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=banner-stands" class="mm-link">
													<span>
														Banner Stands
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/product/step-and-repeat-backdrop-graphic-frame" class="mm-link">
													<span>
														Step and Repeat Backdrop
													</span>
												</a>
												<a href="/?category_slug=real-estate-products" class="mm-link">
													<span>
														Real Estate Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=a-frame-and-sign-holders" class="mm-link">
													<span>
														A Frame and Sign Holders
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=signicade-a-frames" class="mm-link">
													<span>
														Signicade A-Frames
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=seg-products" class="mm-link">
													<span>
														SEG Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=trade-show-products" class="mm-link">
													<span>
														Trade Show Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=custom-event-tents" class="mm-link">
													<span>
														Custom Event Tents
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=table-throws" class="mm-link">
													<span>
														Table Throws
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=hardware-only" class="mm-link">
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
												<a href="/product/13oz-vinyl-banner" class="mm-link">
													<span>
														13oz Vinyl Banner
													</span>
												</a>
												<a href="/product/18oz-blockout-banner" class="mm-link">
													<span>
														18oz Blockout Banner
													</span>
												</a>
												<a href="/product/backlit-banner" class="mm-link">
													<span>
														Backlit Banner
													</span>
												</a>
												<a href="/product/mesh-banner/" class="mm-link">
													<span>
														Mesh Banner
													</span>
												</a>
												<a href="/product/indoor-banner-super-smooth" class="mm-link">
													<span>
														Indoor Banner
													</span>
												</a>
												<a href="/product/pole-banner-set/" class="mm-link">
													<span>
														Pole Banner
													</span>
												</a>
												<a href="/product/fabric-banner-9oz-wrinkle-free" class="mm-link">
													<span>
														9oz Fabric Banner
													</span>
												</a>
												<a href="/product/blockout-fabric-banner" class="mm-link">
													<span>
														Blockout Fabric Banner
													</span>
												</a>
												<a href="/product/tension-fabric" class="mm-link">
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
												<a href="?category_slug=wall-art" class="mm-link">
													<span>
														Wall Art
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=rigid-signs-and-magnets" class="mm-link">
													<span>
														Rigid Signs and Magnets
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=reflective-products" class="mm-link">
													<span>
														Reflective Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/?category_slug=dry-erase-products" class="mm-link">
													<span>
														Dry Erase Products
													</span>
													<i class="fa-solid fa-chevron-right"></i>
												</a>
												<a href="/product/dtf/" class="mm-link">
													<span>
														DTF
													</span>
												</a>
												<a href="/product/backlit-film/" class="mm-link">
													<span>
														Backlit Film
													</span>
												</a>
												<a href="/product/premium-window-cling/" class="mm-link">
													<span>
														Prem. Window Cling
													</span>
												</a>
												<a href="/product/posters/" class="mm-link">
													<span>
														Posters
													</span>
												</a>
												<a href="/product/styrene/" class="mm-link">
													<span>
														Styrene
													</span>
												</a>
												<a href="/product/popup/" class="mm-link">
													<span>
														Popup
													</span>
												</a>
												<a href="/product/canvas-roll/" class="mm-link">
													<span>
														Canvas Roll
													</span>
												</a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<?php wp_nav_menu(array(
							'theme_location' => 'header-bottom-menu',
							'container_class' => 'header-menu-container hide-on-mobile',
							'menu_class' => 'header-menu text-center'
						)); 					?>

					</div>

				</div>
			</div>
		</header>

		<?php if (isset($_GET['type'])) {
			$type = $_GET['type'];

			$message = isset($_GET['message']) ? $_GET['message'] : '';
		?>
			<div class="container mt-3">
				<div class="row">
					<div class="col-md-6 offset-md-3 col-12 text-center">
						<div class="alert alert-<?php echo $type; ?>">
							<?php echo $message; ?>
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
