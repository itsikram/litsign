<?php
/**
 * Channel letter builder for visitors who can't see prices. The real builder
 * (cl_builderr.php + js/cl.js) carries every cost rule in the page, so it is
 * only rendered for approved trade accounts (inc/trade/gate.php).
 *
 * @package litsign
 */

$logged_in = is_user_logged_in();

get_header();
?>
<main id="primary" class="tw-page tw-builder-gate">
	<div class="tw-wrap">
		<?php wholesale_trade_render_crumbs(); ?>
		<article class="tw-product">
			<div>
				<div class="tw-builder-preview" aria-hidden="true">
					<div>
						<div class="tw-builder-preview__word">YOUR SIGN</div>
						<p class="tw-builder-preview__cap">Live preview, letter by letter, in the trade builder</p>
					</div>
				</div>
			</div>
			<div>
				<p class="tw-product__kicker">Trade tool</p>
				<h1 class="tw-product__title">Channel letter builder for trade accounts</h1>
				<p class="tw-product__lead">Type the sign text, pick the letter type, font, height and colors, and see the set and its trade price update as you go. Save the design, send it to your client, and order it like any web order.</p>
				<?php echo wholesale_trade_price_box('builder'); // Escaped in helper. ?>
				<h2 class="tw-h3" style="margin-top:28px">What approved accounts can do in the builder</h2>
				<ul class="tw-checks">
					<li>Price complete sets on all 7 letter types</li>
					<li>Choose fonts, letter heights, face, return and trim cap colors</li>
					<li>Add a raceway, power supplies and extension cords</li>
					<li>Upload logos and vector art with the order</li>
					<li>Reopen a saved design to change it before you order</li>
				</ul>
				<?php if (!$logged_in) : ?>
					<p style="color:var(--tw-muted)">Just looking? Compare the <a href="<?php echo esc_url(home_url('/channel-letters/')); ?>">7 channel letter types</a> first.</p>
				<?php endif; ?>
			</div>
		</article>
	</div>
	<?php wholesale_trade_render_cta_band('Get builder access with a trade account'); ?>
</main>
<?php
get_footer();
