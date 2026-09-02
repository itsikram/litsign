<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package litsign
 */

get_header();
?>

	<main id="primary" class="site-main">
		<div class="container">
		<h1><?php echo get_the_title(); ?></h1>

		<?php the_content(); ?>

		</div>

	</main><!-- #main -->

<?php
get_footer();
