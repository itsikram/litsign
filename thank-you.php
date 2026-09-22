<?php
/**
 * Order confirmation page.
 *
 * @package litsign
 */

get_header();

$session_id = isset($_GET['session_id']) ? sanitize_text_field(wp_unslash($_GET['session_id'])) : '';
?>

<main id="primary" class="site-main thank-you-page">
	<div class="container">
		<section class="thank-you-card" aria-labelledby="thank-you-title">
			<div class="thank-you-icon-wrap">
				<div class="thank-you-icon" aria-hidden="true">
					<span class="fa fa-check"></span>
				</div>
			</div>

			<p class="thank-you-eyebrow">Order received</p>
			<h1 id="thank-you-title">Thank you for your order!</h1>
			<p class="thank-you-intro">
				We appreciate your business. Your order has been placed successfully and our team will begin reviewing it shortly.
			</p>

			<div class="thank-you-next-steps">
				<div class="thank-you-step">
					<span class="fa fa-envelope" aria-hidden="true"></span>
					<div>
						<strong>Check your inbox</strong>
						<p>A confirmation email with your order details is on its way.</p>
					</div>
				</div>
				<div class="thank-you-step">
					<span class="fa fa-phone" aria-hidden="true"></span>
					<div>
						<strong>We will be in touch</strong>
						<p>Our team will contact you if we need any additional information.</p>
					</div>
				</div>
			</div>

			<?php if ($session_id) : ?>
				<p class="thank-you-reference">
					<span>Payment reference</span>
					<strong><?php echo esc_html($session_id); ?></strong>
				</p>
			<?php endif; ?>

			<div class="thank-you-actions">
				<a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Continue shopping</a>
				<?php if (is_user_logged_in()) : ?>
					<a href="<?php echo esc_url(home_url('/my-orders/')); ?>" class="btn btn-outline-primary">View my orders</a>
				<?php endif; ?>
			</div>
		</section>
	</div>
</main>

<?php get_footer(); ?>
