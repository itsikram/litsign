<?php
/**
 * Trade site footer (replaces the retail footer in trade mode, footer.php).
 *
 * @package litsign
 */

$brand = wholesale_trade_brand();
$approved = wholesale_trade_can_order();
$types = wholesale_trade_cl_types();
$show_sticky = !$approved && !is_page(array('cart', 'checkout', 'login')) && 'trade-account' !== wholesale_trade_virtual_page();
?>
<footer class="tw-footer">
	<div class="tw-wrap tw-footer__grid">
		<div class="tw-footer__brand">
			<h2><?php echo esc_html($brand['name']); ?></h2>
			<p>Trade-only channel letter manufacturer for sign companies, installers and resellers. Built in Washington and California since <?php echo (int) $brand['since']; ?>.</p>
			<p><strong>UL listed</strong> · <strong>5-year warranty</strong> · <strong>We never install</strong></p>
		</div>
		<nav aria-label="Channel letter types">
			<h2>Channel letters</h2>
			<ul>
				<?php foreach ($types as $slug => $type) : ?>
					<li><a href="<?php echo esc_url(wholesale_trade_type_url($slug)); ?>">Type <?php echo (int) $type['type']; ?> · <?php echo esc_html($type['name']); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<nav aria-label="Trade">
			<h2>Trade</h2>
			<ul>
				<li><a href="<?php echo esc_url(home_url('/channel-letters/')); ?>">Compare all 7 types</a></li>
				<li><a href="<?php echo esc_url(home_url('/for-sign-companies/')); ?>">Outsource fabrication</a></li>
				<li><a href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Channel letter builder</a></li>
				<li><a href="<?php echo esc_url($approved ? home_url('/my-orders/') : wholesale_trade_apply_url()); ?>"><?php echo $approved ? 'My orders' : 'Apply for a trade account'; ?></a></li>
				<li><a href="<?php echo esc_url(content_url('uploads/storefront-files/channel-letter-installation-manual.pdf')); ?>">Installation manual (PDF)</a></li>
				<?php if ($approved) : ?>
					<li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Other trade products</a></li>
				<?php endif; ?>
			</ul>
		</nav>
		<div>
			<h2>Trade sales</h2>
			<ul>
				<li><a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a></li>
				<li>Text <a href="<?php echo esc_url($brand['text_href']); ?>"><?php echo esc_html($brand['text']); ?></a></li>
				<li><a href="mailto:<?php echo esc_attr($brand['email']); ?>"><?php echo esc_html($brand['email']); ?></a></li>
				<li><?php echo esc_html($brand['hours']); ?></li>
				<li><a href="<?php echo esc_url(home_url('/about/')); ?>">About us</a> · <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></li>
			</ul>
		</div>
	</div>
	<div class="tw-wrap tw-footer__bottom">
		<span>&copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html($brand['legal_name']); ?>. Trade accounts only.</span>
		<span><a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>">Trade terms</a></span>
	</div>
	<?php if ($show_sticky) : ?>
		<div class="tw-sticky" role="region" aria-label="Quick actions">
			<a class="tw-btn tw-btn--glow" href="<?php echo esc_url(wholesale_trade_apply_url()); ?>">Apply for trade</a>
			<a class="tw-btn tw-btn--ghost-light" href="<?php echo esc_url($brand['phone_href']); ?>">Call</a>
		</div>
	<?php endif; ?>
</footer>
