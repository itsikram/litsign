<?php
/**
 * Sign packages (/sign-packages/): quote-based project bundles from inc/buying-journey.php
 * (retails_packages()). Each item links to its real product page; the quote button
 * passes the package to /contact/ (?interest=mixed&item=<package>). No bundle discount
 * and no package price are shown: every item is quoted at its normal price.
 *
 * @package litsign
 */

$packages = retails_packages();
get_header();
?>

<main id="primary" class="site-main pkg-page">
	<div class="container">
		<?php retails_breadcrumbs(); ?>

		<header class="pkg-hero">
			<h1>Sign Packages for Openings, Events &amp; New Storefronts</h1>
			<p>Three common projects, quoted in one request. Every item is a product you can also order on its own, priced at its normal price. Tell us your sizes and dates and you get one combined quote; nothing is made until you approve it.</p>
		</header>

		<div class="pkg-grid">
			<?php foreach ($packages as $key => $package) : ?>
				<section class="pkg-card" id="<?php echo esc_attr($key); ?>" aria-labelledby="pkg-<?php echo esc_attr($key); ?>-title">
					<h2 id="pkg-<?php echo esc_attr($key); ?>-title"><?php echo esc_html($package['title']); ?> package</h2>
					<p><?php echo esc_html($package['lead']); ?></p>
					<h3>What’s in it</h3>
					<ul class="pkg-items">
						<?php foreach ($package['contents'] as $item) :
							$product = retails_package_product($item[0]);
							$image = $product ? get_the_post_thumbnail_url($product, 'thumbnail') : '';
							?>
							<li class="pkg-item">
								<?php if ($image) : ?>
									<img src="<?php echo esc_url($image); ?>" alt="" width="72" height="72" loading="lazy" decoding="async">
								<?php else : ?>
									<span aria-hidden="true"></span>
								<?php endif; ?>
								<span>
									<strong><?php echo esc_html($item[1]); ?></strong>
									<small><?php echo esc_html($item[2]); ?></small>
									<?php if ($product) : ?>
										<a href="<?php echo esc_url(get_permalink($product)); ?>">See <?php echo esc_html(html_entity_decode(get_the_title($product), ENT_QUOTES)); ?> options and prices</a>
									<?php endif; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
					<h3>You choose</h3>
					<p><?php echo esc_html($package['options']); ?></p>
					<h3>Not included</h3>
					<ul class="pkg-excludes">
						<?php foreach ($package['excludes'] as $exclude) : ?>
							<li><?php echo esc_html($exclude); ?></li>
						<?php endforeach; ?>
					</ul>
					<a class="pkg-cta" href="<?php echo esc_url(retails_quote_url('mixed', $key)); ?>">Get a quote for this package</a>
				</section>
			<?php endforeach; ?>
		</div>

		<p class="pkg-note">Need only some of the items, or something else? <a href="<?php echo esc_url(retails_quote_url('mixed')); ?>">Describe your project</a> or call <a href="tel:+18664362101">866-436-2101</a>. Shipping is quoted with your items and sales tax is added at checkout. We build and ship; we don’t install.</p>
	</div>
</main>

<?php
get_footer();
