<?php
/**
 * Industry page (/industries/{slug}/), fed by data/industries.php.
 *
 * National page: which of our products fit the industry, sizing and
 * visibility tips, FAQ and a quote CTA. Real examples render only when the
 * data has them.
 *
 * @package litsign
 */

$entry = wholesale_guide_current();
$industries = wholesale_guide_industries();
$industry = $industries[$entry['industry']];

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$industries_url = wholesale_guide_url('industries');
$locations_url = wholesale_guide_url('locations');
$permits_url = wholesale_guide_url('sign-permits');
$is_events = 'event-trade-show-signs' === $industry['slug'];

wholesale_seo_set_page_faq($industry['faq']);
wholesale_guide_add_service_schema(
	sprintf('Signs for %s', $industry['name']),
	wholesale_schema_text($industry['seo_description'])
);
$GLOBALS['wholesale_page_items'] = array_values(array_filter(array_map('wholesale_seo_product', array_keys($industry['products']))));

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker"><?php echo esc_html($industry['name']); ?></p>
			<h1 id="gd-title" class="sf-hero-title"><?php echo esc_html($industry['h1']); ?></h1>
			<?php foreach ($industry['intro'] as $paragraph) : ?>
				<p class="sf-hero-lead"><?php echo wp_kses_post($paragraph); ?></p>
			<?php endforeach; ?>
			<div class="sf-hero-actions">
				<?php if ($is_events) : ?>
					<a class="cl-button" href="<?php echo esc_url(home_url('/banners-displays/')); ?>">Shop Banners &amp; Displays <?php echo wholesale_home_icon('arrow'); ?></a>
				<?php else : ?>
					<a class="cl-button" href="<?php echo esc_url(home_url('/custom-channel-letters/')); ?>">See Channel Letter Prices <?php echo wholesale_home_icon('arrow'); ?></a>
				<?php endif; ?>
				<a class="sf-button-outline" href="<?php echo esc_url($contact_url); ?>">Send Your Logo for a Quote</a>
			</div>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-needs-title">
		<div class="container sf-prose">
			<p class="cl-kicker">What matters</p>
			<h2 id="gd-needs-title" class="cl-section-title"><?php echo esc_html(sprintf('What %s Need From Their Signs', $industry['name'])); ?></h2>
			<ul class="gd-checklist">
				<?php foreach ($industry['needs'] as $need) : ?>
					<li><strong><?php echo esc_html($need[0]); ?>.</strong> <?php echo wp_kses_post($need[1]); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-products-title">
		<div class="container">
			<p class="cl-kicker">Recommended products</p>
			<h2 id="gd-products-title" class="cl-section-title"><?php echo esc_html(sprintf('Signs for %s', $industry['name'])); ?></h2>
			<p class="cl-section-lead">Every product is made to order and priced online. Prices shown are starting prices.</p>
			<?php wholesale_guide_render_products($industry['products']); ?>
			<?php if (!empty($industry['categories'])) : ?>
				<p class="gd-all-link">
					Browse:
					<?php
					$category_links = array();
					foreach ($industry['categories'] as $slug => $label) {
						$category_links[] = '<a href="' . esc_url(wholesale_category_url($slug)) . '">' . esc_html($label) . '</a>';
					}
					echo implode(' &middot; ', $category_links); // Escaped above.
					?>
				</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-tips-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Planning tips</p>
			<h2 id="gd-tips-title" class="cl-section-title">Sizing and Visibility Tips</h2>
			<?php foreach ($industry['tips'] as $tip) : ?>
				<p><?php echo wp_kses_post($tip); ?></p>
			<?php endforeach; ?>
			<?php if (!$is_events) : ?>
				<div class="gd-note">
					<p><strong>Who installs it?</strong> We build, test and ship your sign. A licensed sign installer or electrician near you mounts and connects it with the included wiring diagram and install pattern.<?php if ($permits_url) : ?> See <a href="<?php echo esc_url($permits_url); ?>">how sign permits work</a><?php if ($locations_url) : ?> and our <a href="<?php echo esc_url($locations_url); ?>">state guides</a><?php endif; ?>.<?php endif; ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if (!empty($industry['examples'])) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-examples-title">
			<div class="container">
				<p class="cl-kicker">Real examples</p>
				<h2 id="gd-examples-title" class="cl-section-title"><?php echo esc_html(sprintf('Signs We Made for %s', $industry['name'])); ?></h2>
				<div class="gd-quotes">
					<?php foreach ($industry['examples'] as $example) : ?>
						<figure class="gd-quote">
							<img src="<?php echo esc_url($example['image']); ?>" alt="<?php echo esc_attr($example['alt']); ?>" loading="lazy" decoding="async" width="800" height="600">
							<figcaption><?php echo esc_html($example['caption']); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="cl-faq" aria-labelledby="gd-faq-title">
		<div class="container">
			<p class="cl-kicker">Questions</p>
			<h2 id="gd-faq-title" class="cl-section-title"><?php echo esc_html(sprintf('%s Sign FAQ', $industry['name'])); ?></h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php
	$others = array();
	foreach ($industries as $slug => $other) {
		$url = $slug !== $industry['slug'] ? wholesale_guide_url('industries/' . $slug) : '';
		if ($url) {
			$others[$url] = $other['name'];
		}
	}
	?>
	<?php if ($others) : ?>
		<section class="sf-section" aria-labelledby="gd-others-title">
			<div class="container">
				<p class="cl-kicker">More industries</p>
				<h2 id="gd-others-title" class="cl-section-title">Signs for Other Businesses</h2>
				<ul class="gd-link-grid">
					<?php foreach ($others as $url => $name) : ?>
						<li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($name); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php if ($industries_url) : ?>
					<p class="gd-all-link"><a href="<?php echo esc_url($industries_url); ?>">All industries <?php echo wholesale_home_icon('arrow'); ?></a></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php wholesale_guide_render_help(sprintf('Signs for Your %s Business', rtrim(preg_replace('/s$/', '', strtok($industry['name'], ' &')))), 'Send your logo, a storefront or booth photo and any landlord or venue rules. We&rsquo;ll recommend the right signs and sizes and price them for you.'); ?>
</main>

<?php
get_footer();
