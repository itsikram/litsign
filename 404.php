<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package litsign
 */

get_header();
?>

<main id="primary" class="site-main">
	<section class="error-404 not-found">
		<div class="container">
			<div class="error-404-layout">
				<div class="error-404-content">
					<p class="error-404-eyebrow"><?php esc_html_e( 'Error 404', 'litsign' ); ?></p>
					<h1 class="error-404-title"><?php esc_html_e( 'Looks like this sign is still in production.', 'litsign' ); ?></h1>
					<p class="error-404-description">
						<?php esc_html_e( 'The page you are looking for has moved, been removed, or never existed. Let us help you find the right direction.', 'litsign' ); ?>
					</p>

					<div class="error-404-actions">
						<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
							<?php esc_html_e( 'Back to homepage', 'litsign' ); ?>
						</a>
						<a class="error-404-text-link" href="<?php echo esc_url( home_url( '/channel-letters' ) ); ?>">
							<?php esc_html_e( 'Browse our signs', 'litsign' ); ?>
							<span aria-hidden="true">&rarr;</span>
						</a>
					</div>

					<form class="error-404-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label class="screen-reader-text" for="error-404-search-field"><?php esc_html_e( 'Search the site', 'litsign' ); ?></label>
						<input id="error-404-search-field" type="search" name="s" placeholder="<?php esc_attr_e( 'What are you looking for?', 'litsign' ); ?>">
						<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'litsign' ); ?>">
							<span aria-hidden="true">&#8594;</span>
						</button>
					</form>
				</div>

				<div class="error-404-visual" aria-hidden="true">
					<div class="error-404-glow"></div>
					<div class="error-404-sign">
						<span class="error-404-sign-number">404</span>
						<span class="error-404-sign-copy"><?php esc_html_e( 'Page not found', 'litsign' ); ?></span>
						<span class="error-404-sign-line"></span>
					</div>
					<span class="error-404-dot error-404-dot-one"></span>
					<span class="error-404-dot error-404-dot-two"></span>
					<span class="error-404-cross error-404-cross-one">+</span>
					<span class="error-404-cross error-404-cross-two">+</span>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
