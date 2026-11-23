<?php
/**
 * Trade site About page. Owner-confirmed facts only (prompt v2, section 3):
 * since 2002, team in Renton WA, letters built in Washington and California,
 * UL listed, 5-year warranty, never installs. No staff names, client logos or
 * volume numbers until the owner provides them.
 *
 * @package litsign
 */

$brand = wholesale_trade_brand();

get_header();
?>
<main id="primary" class="tw-page tw-about">
	<section class="tw-hero">
		<div class="tw-wrap"><?php wholesale_trade_render_crumbs(); ?></div>
		<div class="tw-wrap" style="padding:28px 16px 52px">
			<p class="tw-eyebrow">About us</p>
			<h1 class="tw-hero__title" style="font-size:clamp(30px,4.4vw,48px)">A channel letter fabrication shop <em>for the sign trade</em></h1>
			<p class="tw-hero__text" style="margin-bottom:0"><?php echo esc_html($brand['name']); ?> is the trade side of <?php echo esc_html($brand['legal_name']); ?>. Since <?php echo (int) $brand['since']; ?> we have built lit channel letters that other sign companies sell, permit and install.</p>
		</div>
	</section>

	<div class="tw-wrap"><?php wholesale_trade_render_proof('tw-proof--float'); ?></div>

	<section class="tw-section" aria-labelledby="tw-what">
		<div class="tw-wrap tw-split">
			<div>
				<h2 class="tw-h2" id="tw-what">What we do, and what we don't</h2>
				<p>We fabricate seven types of LED channel letters, from aluminum face lit sets to welded stainless steel halo and trimless letters. Every set is wired, tested and UL labeled before it is crated and shipped from our Washington and California facilities to any of the 50 states.</p>
				<p>We don't install, and we don't sell to your clients on this site. Installation belongs to your crew or a licensed electrician, which is why every set ships with a wiring diagram, a full-size mounting pattern and our installation manual.</p>
				<ul class="tw-checks">
					<li>Trade accounts reviewed by our team, one business at a time</li>
					<li>5-year warranty on all 7 channel letter types</li>
					<li>Trade sales team in Renton, Washington</li>
				</ul>
			</div>
			<div class="tw-value">
				<h2 class="tw-h3">Contact trade sales</h2>
				<p style="margin-bottom:10px"><?php echo esc_html($brand['legal_name']); ?><br>707 S. Grady Way, Suite 600<br>Renton, WA 98057</p>
				<p style="margin-bottom:10px">Phone: <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a><br>Text: <a href="<?php echo esc_url($brand['text_href']); ?>"><?php echo esc_html($brand['text']); ?></a><br>Email: <a href="mailto:<?php echo esc_attr($brand['email']); ?>"><?php echo esc_html($brand['email']); ?></a></p>
				<p><?php echo esc_html($brand['hours']); ?>. Office only: no showroom or pickup.</p>
			</div>
		</div>
	</section>

	<?php wholesale_trade_render_cta_band(); ?>
</main>
<?php
get_footer();
