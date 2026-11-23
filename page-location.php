<?php
/**
 * State and city guide pages (/locations/{state}/ and /locations/{state}/{city}/).
 *
 * Content comes from data/locations/{state}.php and data/locations/{state}/{city}.php.
 * We make and ship, and never install: the page covers what we ship to the
 * area, who may install and connect a sign there, how permits work (with
 * official sources), and how ordering works. Sections render only when their
 * data exists. Projects, reviews and installer referrals render only when real
 * ones are in the data.
 *
 * @package litsign
 */

$entry = wholesale_guide_current();
$states = wholesale_guide_states();
$state = $states[$entry['state']];
$is_city = 'city' === $entry['type'];
$place = $is_city ? $state['cities'][$entry['city']] : $state;
$place_name = $is_city ? $place['name'] . ', ' . $state['abbr'] : $state['name'];
$short_name = $is_city ? $place['name'] : $state['name'];

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$builder_url = home_url('/channel-letter-builder/');
$permits_url = wholesale_guide_url('sign-permits');
$install_url = wholesale_guide_url('how-to-install-channel-letters');
$shipping_url = wholesale_guide_url('shipping-returns');
$warranty_url = wholesale_guide_url('warranty');
$state_url = wholesale_guide_url('locations/' . $state['slug']);
$locations_url = wholesale_guide_url('locations');

// Ground transit is shown once verified in the data file;
// until then the page states the checkout's own standard range.
$transit = isset($place['transit']) ? $place['transit'] : (isset($state['transit']) ? $state['transit'] : array());
$transit_text = !empty($transit['verified']) && !empty($transit['days'])
	? sprintf('Standard shipping to %s usually takes %s business days in transit.', $place_name, $transit['days'])
	: 'Standard shipping takes 3&ndash;6 business days in transit; 3-day, 2-day and overnight shipping are available at checkout.';

$faq = isset($place['faq']) ? $place['faq'] : array();
wholesale_seo_set_page_faq($faq);

$facts = isset($place['facts']) ? (array) $place['facts'] : array();
if ($is_city && !empty($place['county']) && !isset($facts['County'])) {
	$facts['County'] = $place['county'];
}

$updated = wholesale_guide_updated($place, true);

// Structured data: the place (State or City, with Wikipedia sameAs), the
// Service we offer there (manufacture and ship, never a local office) and
// the product families it covers. No LocalBusiness, ratings or reviews.
$page_url = trailingslashit(get_permalink());
$place_entity = wholesale_guide_place_entity($state, $is_city ? $place : null);
$place_entity['@id'] = $page_url . '#place';
$GLOBALS['wholesale_page_entities'][] = $place_entity;

wholesale_guide_add_service_schema(
	sprintf('Channel letters and storefront signs shipped to %s', $place_name),
	sprintf('Custom LED channel letters built in Washington and California, plus banners, window graphics, flags and displays made in Renton, WA, shipped to %s for a licensed local installer.', $place_name),
	array('@id' => $place_entity['@id']),
	array('hasOfferCatalog' => wholesale_guide_offer_catalog($place_name))
);

add_filter('wholesale_seo_webpage_schema', static function ($page) use ($place_entity, $is_city, $state, $updated) {
	$page['about'] = array('@id' => $place_entity['@id']);
	if ($is_city) {
		$page['mentions'] = array(wholesale_guide_place_entity($state));
	}
	if ($updated) {
		$page['dateModified'] = $updated;
	}
	return $page;
});

$popular = !empty($place['popular']['products']) ? array_keys((array) $place['popular']['products']) : array();
wholesale_seo_prime_products($popular);

$paragraphs = static function ($items) {
	foreach ((array) $items as $paragraph) {
		echo '<p>' . wp_kses_post($paragraph) . '</p>';
	}
};

// One permits-style block: heading, paragraphs, a fact table and its sources.
$render_block = static function ($block, $heading_tag = 'h2') use ($paragraphs) {
	if (!empty($block['heading'])) {
		printf('<%1$s class="%2$s">%3$s</%1$s>', $heading_tag, 'h2' === $heading_tag ? 'cl-section-title' : 'gd-block-title', esc_html($block['heading']));
	}
	$paragraphs(isset($block['body']) ? $block['body'] : array());
	if (!empty($block['table'])) {
		echo '<table class="gd-table"><tbody>';
		foreach ($block['table'] as $label => $value) {
			echo '<tr><th scope="row">' . wp_kses_post($label) . '</th><td>' . wp_kses_post($value) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
};

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker"><?php echo esc_html($is_city ? $state['name'] . ' city guide' : 'State guide'); ?></p>
			<h1 id="gd-title" class="sf-hero-title"><?php echo esc_html($place['h1']); ?></h1>
			<?php $paragraphs(array_slice((array) $place['intro'], 0, 1)); ?>
			<div class="sf-hero-actions">
				<a class="cl-button" href="<?php echo esc_url($builder_url); ?>">Price Your Channel Letters <?php echo wholesale_home_icon('arrow'); ?></a>
				<a class="sf-button-outline" href="<?php echo esc_url($contact_url); ?>">Get a Free Quote</a>
				<a class="gd-hero-call" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> 866-436-2101</a>
			</div>
			<?php if ($facts) : ?>
				<ul class="gd-facts">
					<?php foreach ($facts as $label => $value) : ?>
						<li><strong><?php echo esc_html($label); ?></strong><span><?php echo wp_kses_post($value); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ($updated) : ?>
				<p class="gd-reviewed">Last reviewed <time datetime="<?php echo esc_attr($updated); ?>"><?php echo esc_html(wp_date('F Y', strtotime($updated . ' 12:00:00'))); ?></time> against the official sources linked on this page.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-ship-title">
		<div class="container">
			<p class="cl-kicker">What we ship</p>
			<h2 id="gd-ship-title" class="cl-section-title"><?php echo esc_html(sprintf('Channel Letters, Banners and Graphics Shipped to %s', $short_name)); ?></h2>
			<?php if (count((array) $place['intro']) > 1) : ?>
				<div class="sf-prose gd-lead">
					<?php $paragraphs(array_slice((array) $place['intro'], 1)); ?>
				</div>
			<?php endif; ?>
			<?php wholesale_guide_render_families($place); ?>
		</div>
	</section>

	<?php if (!empty($place['popular'])) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-popular-title">
			<div class="container">
				<p class="cl-kicker">Popular choices</p>
				<h2 id="gd-popular-title" class="cl-section-title"><?php echo esc_html($place['popular']['heading']); ?></h2>
				<?php if (!empty($place['popular']['lead'])) : ?>
					<p class="cl-section-lead"><?php echo wp_kses_post($place['popular']['lead']); ?></p>
				<?php endif; ?>
				<?php wholesale_guide_render_products($place['popular']['products']); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if (!empty($place['licensing'])) : ?>
		<section class="sf-section" aria-labelledby="gd-license-title">
			<div class="container sf-prose">
				<p class="cl-kicker">Who can install it</p>
				<h2 id="gd-license-title" class="cl-section-title"><?php echo esc_html($place['licensing']['heading']); ?></h2>
				<?php $render_block(array_diff_key($place['licensing'], array('heading' => 1))); ?>
				<?php wholesale_guide_render_sources(isset($place['licensing']['sources']) ? $place['licensing']['sources'] : array()); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if (!empty($place['permits'])) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-permit-title">
			<div class="container sf-prose">
				<p class="cl-kicker">Permits</p>
				<h2 id="gd-permit-title" class="cl-section-title"><?php echo esc_html($place['permits']['heading']); ?></h2>
				<?php $render_block(array_diff_key($place['permits'], array('heading' => 1))); ?>
				<?php if (!empty($place['districts'])) : ?>
					<h3 class="gd-block-title"><?php echo esc_html(sprintf('Design review and special districts in %s', $short_name)); ?></h3>
					<ul class="gd-districts">
						<?php foreach ($place['districts'] as $district) : ?>
							<li><strong><?php echo esc_html(wholesale_schema_text($district['name'])); ?></strong> <?php echo wp_kses_post($district['rule']); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ($permits_url) : ?>
					<p>New to sign permits? Read <a href="<?php echo esc_url($permits_url); ?>">how sign permits work</a> and what your electrician needs.</p>
				<?php endif; ?>
				<?php
				$permit_sources = isset($place['permits']['sources']) ? (array) $place['permits']['sources'] : array();
				foreach (isset($place['districts']) ? (array) $place['districts'] : array() as $district) {
					if (!empty($district['source'])) {
						$permit_sources[] = array($district['name'], $district['source']);
					}
				}
				wholesale_guide_render_sources($permit_sources);
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php foreach (array('temporary_signs' => 'Banners and temporary displays', 'window_signs' => 'Windows') as $key => $kicker) : ?>
		<?php if (!empty($place[$key])) : ?>
			<section class="sf-section" aria-labelledby="gd-<?php echo esc_attr($key); ?>-title">
				<div class="container sf-prose">
					<p class="cl-kicker"><?php echo esc_html($kicker); ?></p>
					<h2 id="gd-<?php echo esc_attr($key); ?>-title" class="cl-section-title"><?php echo esc_html($place[$key]['heading']); ?></h2>
					<?php $render_block(array_diff_key($place[$key], array('heading' => 1))); ?>
					<?php wholesale_guide_render_sources(isset($place[$key]['sources']) ? $place[$key]['sources'] : array(), 'Sources', false); ?>
				</div>
			</section>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php if (!empty($place['climate'])) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-climate-title">
			<div class="container sf-prose">
				<p class="cl-kicker">Weather</p>
				<h2 id="gd-climate-title" class="cl-section-title"><?php echo esc_html(sprintf('Planning for %s Weather', $short_name)); ?></h2>
				<?php $paragraphs($place['climate']); ?>
				<?php wholesale_guide_render_sources(isset($place['climate_sources']) ? $place['climate_sources'] : array(), 'Sources', false); ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="sf-section" aria-labelledby="gd-order-title">
		<div class="container sf-prose">
			<p class="cl-kicker">How it works</p>
			<h2 id="gd-order-title" class="cl-section-title"><?php echo esc_html(sprintf('How Ordering Works From %s', $short_name)); ?></h2>
			<ol class="gd-steps">
				<li>
					<h3>Price it online</h3>
					<p>Use the <a href="<?php echo esc_url($builder_url); ?>">channel letter builder</a> or <a href="<?php echo esc_url($contact_url); ?>">send your logo</a>.</p>
				</li>
				<li>
					<h3>Approve your proof</h3>
					<p>Check it with your landlord and installer.</p>
				</li>
				<li>
					<h3>We build and test it</h3>
					<p>Letters in WA or CA; the rest in Renton, WA.</p>
				</li>
				<li>
					<h3>It ships to you</h3>
					<p><?php echo wp_kses_post($transit_text); ?><?php if ($shipping_url) : ?> <a href="<?php echo esc_url($shipping_url); ?>">Shipping options</a>.<?php endif; ?></p>
				</li>
				<li>
					<h3>Your installer mounts it</h3>
					<p>With our wiring diagram and install pattern<?php if ($install_url) : ?> (<a href="<?php echo esc_url($install_url); ?>">overview</a>)<?php endif; ?>.<?php if ($permits_url) : ?> <a href="<?php echo esc_url($permits_url); ?>">Permit checklist</a>.<?php endif; ?></p>
				</li>
				<li>
					<h3>Five-year warranty</h3>
					<p>On every channel letter style<?php if ($warranty_url) : ?> (<a href="<?php echo esc_url($warranty_url); ?>">details</a>)<?php endif; ?>.</p>
				</li>
			</ol>
			<div class="gd-note">
				<p><strong>We build and ship; a local pro installs.</strong> A licensed sign installer or electrician in <?php echo esc_html($place_name); ?> mounts and connects your sign. <?php echo wp_kses_post(!empty($place['local_note']) ? $place['local_note'] : sprintf('We don&rsquo;t have an office or installation crew in %s, and orders can&rsquo;t be picked up.', esc_html($short_name))); ?></p>
			</div>
		</div>
	</section>

	<?php if (!empty($place['projects'])) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-projects-title">
			<div class="container">
				<p class="cl-kicker">Real projects</p>
				<h2 id="gd-projects-title" class="cl-section-title"><?php echo esc_html(sprintf("Signs We've Shipped to %s", $place_name)); ?></h2>
				<div class="gd-quotes">
					<?php foreach ($place['projects'] as $project) : ?>
						<figure class="gd-quote">
							<?php if (!empty($project['image'])) : ?>
								<img src="<?php echo esc_url($project['image']); ?>" alt="<?php echo esc_attr($project['alt']); ?>" loading="lazy" decoding="async" width="800" height="600">
							<?php endif; ?>
							<?php if (!empty($project['quote'])) : ?>
								<blockquote><p><?php echo esc_html($project['quote']); ?></p></blockquote>
							<?php endif; ?>
							<figcaption><?php echo esc_html($project['caption']); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if (!empty($place['installers'])) : ?>
		<section class="sf-section" aria-labelledby="gd-installers-title">
			<div class="container sf-prose">
				<p class="cl-kicker">Installer referrals</p>
				<h2 id="gd-installers-title" class="cl-section-title"><?php echo esc_html(sprintf('Licensed Installers in %s', $place_name)); ?></h2>
				<p>These independent, licensed businesses have installed our signs. They are not part of Storefront Sign Online; contact them directly for a quote.</p>
				<ul class="gd-link-grid">
					<?php foreach ($place['installers'] as $installer) : ?>
						<li><a href="<?php echo esc_url($installer['url']); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html($installer['name']); ?><small><?php echo esc_html($installer['area']); ?></small></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$city_links = array();
	if (!$is_city) {
		foreach ($state['cities'] as $city_slug => $city) {
			$city_url = wholesale_guide_url('locations/' . $state['slug'] . '/' . $city_slug);
			if ($city_url) {
				$city_links[$city_url] = $city;
			}
		}
	}
	?>
	<?php if ($city_links) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-cities-title">
			<div class="container">
				<p class="cl-kicker">City guides</p>
				<h2 id="gd-cities-title" class="cl-section-title"><?php echo esc_html(sprintf('Sign Permits in %s Cities', $state['name'])); ?></h2>
				<p class="cl-section-lead">Each city has its own sign code and permit office. These guides cover the local rules.</p>
				<ul class="gd-link-grid">
					<?php foreach ($city_links as $city_url => $city) : ?>
						<li><a href="<?php echo esc_url($city_url); ?>"><?php echo esc_html($city['name']); ?><small><?php echo esc_html($city['card']); ?></small></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ($faq) : ?>
		<section class="cl-faq" aria-labelledby="gd-faq-title">
			<div class="container">
				<p class="cl-kicker">Questions</p>
				<h2 id="gd-faq-title" class="cl-section-title"><?php echo esc_html(sprintf('%s Storefront Sign FAQ', $short_name)); ?></h2>
				<div class="cl-faq-list">
					<?php wholesale_seo_render_faq(); ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="sf-section" aria-labelledby="gd-more-title">
		<div class="container">
			<p class="cl-kicker">Keep reading</p>
			<h2 id="gd-more-title" class="cl-section-title">Guides and Products</h2>
			<ul class="gd-link-grid">
				<?php if ($is_city && $state_url) : ?><li><a href="<?php echo esc_url($state_url); ?>"><?php echo esc_html(sprintf('%s sign license and permit guide', $state['name'])); ?></a></li><?php endif; ?>
				<li><a href="<?php echo esc_url(home_url('/custom-channel-letters/')); ?>">Custom Channel Letters</a></li>
				<li><a href="<?php echo esc_url(home_url('/channel-letter-cost/')); ?>">Channel Letter Cost</a></li>
				<li><a href="<?php echo esc_url(home_url('/banners-displays/')); ?>">Banners &amp; Displays</a></li>
				<li><a href="<?php echo esc_url(home_url('/window-graphics/')); ?>">Window Graphics</a></li>
				<?php if ($install_url) : ?><li><a href="<?php echo esc_url($install_url); ?>">Installation Guide</a></li><?php endif; ?>
			</ul>

			<?php
			// Nearby places: on a city page the cities named in 'nearby' (or every
			// other city guide in the state), on a state page the neighbouring
			// states (or every other state guide until neighbours exist).
			$nearby = array();
			if ($is_city) {
				$candidates = !empty($place['nearby']) ? (array) $place['nearby'] : array_keys($state['cities']);
				foreach ($candidates as $city_slug) {
					if ($city_slug === $entry['city'] || !isset($state['cities'][$city_slug])) {
						continue;
					}
					$city_url = wholesale_guide_url('locations/' . $state['slug'] . '/' . $city_slug);
					if ($city_url) {
						$nearby[$city_url] = sprintf('%s, %s', $state['cities'][$city_slug]['name'], $state['abbr']);
					}
					if (count($nearby) >= 6) {
						break;
					}
				}
			} else {
				$candidates = !empty($state['neighbors']) ? $state['neighbors'] : array();
				foreach ($candidates as $slug) {
					if (isset($states[$slug]) && ($url = wholesale_guide_url('locations/' . $slug))) {
						$nearby[$url] = $states[$slug]['name'];
					}
				}
				if (!$nearby) {
					foreach ($states as $slug => $other) {
						if ($slug !== $state['slug'] && ($url = wholesale_guide_url('locations/' . $slug))) {
							$nearby[$url] = $other['name'];
						}
					}
				}
			}
			?>
			<?php if ($nearby) : ?>
				<h3 class="gd-subhead"><?php echo esc_html($is_city ? sprintf('More %s city guides', $state['name']) : 'Neighboring state guides'); ?></h3>
				<ul class="gd-link-grid">
					<?php foreach ($nearby as $url => $name) : ?>
						<li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($name); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ($locations_url) : ?>
				<p class="gd-all-link"><a href="<?php echo esc_url($locations_url); ?>">All state guides <?php echo wholesale_home_icon('arrow'); ?></a></p>
			<?php endif; ?>
		</div>
	</section>

	<?php wholesale_guide_render_help(sprintf('Planning a Sign in %s?', $place_name), sprintf('Send your storefront photo, logo and your landlord&rsquo;s sign criteria. We&rsquo;ll suggest a size and style that fits %s rules and give you a price before you order.', esc_html($short_name))); ?>
</main>

<?php
get_footer();
