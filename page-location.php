<?php
/**
 * State and city guide pages (/locations/{state}/ and /locations/{state}/{city}/).
 *
 * Content comes from data/locations/{state}.php. We ship from Renton, WA and
 * never install: the page covers shipping to the area, who may install and
 * connect a sign there, and how permits work, with official sources. Projects,
 * reviews and installer referrals render only when real ones are in the data.
 *
 * @package litsign
 */

$entry = wholesale_guide_current();
$states = wholesale_guide_states();
$state = $states[$entry['state']];
$is_city = 'city' === $entry['type'];
$place = $is_city ? $state['cities'][$entry['city']] : $state;
$place_name = $is_city ? $place['name'] . ', ' . $state['abbr'] : $state['name'];

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$permits_url = wholesale_guide_url('sign-permits');
$install_url = wholesale_guide_url('how-to-install-channel-letters');
$shipping_url = wholesale_guide_url('shipping-returns');
$state_url = wholesale_guide_url('locations/' . $state['slug']);
$locations_url = wholesale_guide_url('locations');

// Ground transit from Renton is shown once verified in the data file;
// until then the page states the checkout's own standard range.
$transit = isset($place['transit']) ? $place['transit'] : (isset($state['transit']) ? $state['transit'] : array());
$transit_text = !empty($transit['verified']) && !empty($transit['days'])
	? sprintf('Standard shipping to %s usually takes %s business days in transit from Renton, WA.', $place_name, $transit['days'])
	: 'Standard shipping takes 3&ndash;6 business days in transit; 3-day, 2-day and overnight shipping are available at checkout.';

$faq = isset($place['faq']) ? $place['faq'] : array();
wholesale_seo_set_page_faq($faq);

// Service entity: what we do for this area (manufacture and ship), never a local office.
$area = $is_city
	? array('@type' => 'City', 'name' => $place['name'], 'containedInPlace' => array('@type' => 'State', 'name' => $state['name']))
	: array('@type' => 'State', 'name' => $state['name'], 'containedInPlace' => array('@type' => 'Country', 'name' => 'United States'));
wholesale_guide_add_service_schema(
	sprintf('Storefront signs and channel letters shipped to %s', $place_name),
	sprintf('Custom LED channel letters and storefront signs made in Renton, WA and shipped to %s, ready for a licensed local installer.', $place_name),
	$area
);

$paragraphs = static function ($items) {
	foreach ((array) $items as $paragraph) {
		echo '<p>' . wp_kses_post($paragraph) . '</p>';
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
				<a class="cl-button" href="<?php echo esc_url(home_url('/custom-channel-letters/')); ?>">See Channel Letter Prices <?php echo wholesale_home_icon('arrow'); ?></a>
				<a class="sf-button-outline" href="<?php echo esc_url($contact_url); ?>">Send Your Logo for a Quote</a>
			</div>
			<?php if (!empty($place['facts'])) : ?>
				<ul class="gd-facts">
					<?php foreach ($place['facts'] as $label => $value) : ?>
						<li><strong><?php echo esc_html($label); ?></strong><span><?php echo wp_kses_post($value); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-about-title">
		<div class="container sf-prose">
			<p class="cl-kicker">How it works</p>
			<h2 id="gd-about-title" class="cl-section-title"><?php echo esc_html(sprintf('Ordering a Storefront Sign for %s', $place_name)); ?></h2>
			<?php $paragraphs(array_slice((array) $place['intro'], 1)); ?>
			<div class="gd-note">
				<p><strong>We build and ship; a local pro installs.</strong> Your sign is made and tested in Renton, Washington and shipped to your door with a wiring diagram and install pattern. A licensed sign installer or electrician in <?php echo esc_html($place_name); ?> mounts and connects it. <?php echo wp_kses_post(!empty($place['local_note']) ? $place['local_note'] : sprintf('We don&rsquo;t have an office or installation crew in %s.', esc_html($is_city ? $place['name'] : $state['name']))); ?></p>
			</div>
		</div>
	</section>

	<?php if (!empty($place['licensing'])) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-license-title">
			<div class="container sf-prose">
				<p class="cl-kicker">Who can install it</p>
				<h2 id="gd-license-title" class="cl-section-title"><?php echo esc_html($place['licensing']['heading']); ?></h2>
				<?php $paragraphs($place['licensing']['body']); ?>
				<?php if (!empty($place['licensing']['table'])) : ?>
					<table class="gd-table">
						<tbody>
							<?php foreach ($place['licensing']['table'] as $label => $value) : ?>
								<tr><th scope="row"><?php echo esc_html($label); ?></th><td><?php echo wp_kses_post($value); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<?php wholesale_guide_render_sources(isset($place['licensing']['sources']) ? $place['licensing']['sources'] : array()); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if (!empty($place['permits'])) : ?>
		<section class="sf-section" aria-labelledby="gd-permit-title">
			<div class="container sf-prose">
				<p class="cl-kicker">Permits</p>
				<h2 id="gd-permit-title" class="cl-section-title"><?php echo esc_html($place['permits']['heading']); ?></h2>
				<?php $paragraphs($place['permits']['body']); ?>
				<?php if (!empty($place['permits']['table'])) : ?>
					<table class="gd-table">
						<tbody>
							<?php foreach ($place['permits']['table'] as $label => $value) : ?>
								<tr><th scope="row"><?php echo esc_html($label); ?></th><td><?php echo wp_kses_post($value); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<?php if ($permits_url) : ?>
					<p>New to sign permits? Read <a href="<?php echo esc_url($permits_url); ?>">how sign permits work</a> and what your electrician needs.</p>
				<?php endif; ?>
				<?php wholesale_guide_render_sources(isset($place['permits']['sources']) ? $place['permits']['sources'] : array()); ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-ship-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Delivery</p>
			<h2 id="gd-ship-title" class="cl-section-title"><?php echo esc_html(sprintf('Shipping to %s', $place_name)); ?></h2>
			<p><?php echo wp_kses_post($transit_text); ?> Every sign is made to order, and the estimated ship date for each shipping speed, including production, is shown at checkout.<?php if ($shipping_url) : ?> See <a href="<?php echo esc_url($shipping_url); ?>">shipping options and prices</a>.<?php endif; ?></p>
			<?php if (!empty($place['climate'])) : ?>
				<h3><?php echo esc_html(sprintf('Planning for %s weather', $is_city ? $place['name'] : $state['name'])); ?></h3>
				<?php $paragraphs($place['climate']); ?>
			<?php endif; ?>
		</div>
	</section>

	<?php if (!empty($place['popular'])) : ?>
		<section class="sf-section" aria-labelledby="gd-popular-title">
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

	<?php if (!$is_city && !empty($state['cities'])) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-cities-title">
			<div class="container">
				<p class="cl-kicker">City guides</p>
				<h2 id="gd-cities-title" class="cl-section-title"><?php echo esc_html(sprintf('Sign Permits in %s Cities', $state['name'])); ?></h2>
				<p class="cl-section-lead">Each city has its own sign code and permit office. These guides cover the local rules.</p>
				<ul class="gd-link-grid">
					<?php foreach ($state['cities'] as $city_slug => $city) : ?>
						<?php $city_url = wholesale_guide_url('locations/' . $state['slug'] . '/' . $city_slug); ?>
						<?php if ($city_url) : ?>
							<li><a href="<?php echo esc_url($city_url); ?>"><?php echo esc_html($city['name']); ?><small><?php echo esc_html($city['card']); ?></small></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ($faq) : ?>
		<section class="cl-faq" aria-labelledby="gd-faq-title">
			<div class="container">
				<p class="cl-kicker">Questions</p>
				<h2 id="gd-faq-title" class="cl-section-title"><?php echo esc_html(sprintf('%s Storefront Sign FAQ', $is_city ? $place['name'] : $state['name'])); ?></h2>
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
				<li><a href="<?php echo esc_url(home_url('/custom-channel-letters/')); ?>">Custom Channel Letters<small>Every lit style, priced online</small></a></li>
				<li><a href="<?php echo esc_url(home_url('/channel-letter-cost/')); ?>">Channel Letter Cost<small>Real prices by style and height</small></a></li>
				<li><a href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Sign Builder<small>Design and price your sign</small></a></li>
				<?php if ($permits_url) : ?><li><a href="<?php echo esc_url($permits_url); ?>">Sign Permit Help<small>What the city and your electrician need</small></a></li><?php endif; ?>
				<?php if ($install_url) : ?><li><a href="<?php echo esc_url($install_url); ?>">Installation Guide<small>Raceway vs. direct mount</small></a></li><?php endif; ?>
				<li><a href="<?php echo esc_url(home_url('/banners-displays/')); ?>">Banners &amp; Displays<small>Grand opening banners, flags, stands</small></a></li>
				<?php if ($is_city && $state_url) : ?><li><a href="<?php echo esc_url($state_url); ?>"><?php echo esc_html($state['name']); ?> State Guide<small>Licensing and statewide rules</small></a></li><?php endif; ?>
			</ul>

			<?php
			// Nearby places: sibling cities on a city page, neighbouring states
			// (or every other state guide until neighbours exist) on a state page.
			$nearby = array();
			if ($is_city) {
				foreach ($state['cities'] as $city_slug => $city) {
					$city_url = $city_slug !== $entry['city'] ? wholesale_guide_url('locations/' . $state['slug'] . '/' . $city_slug) : '';
					if ($city_url) {
						$nearby[$city_url] = $city['name'];
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
				<h3 class="gd-subhead"><?php echo esc_html($is_city ? sprintf('Other %s city guides', $state['name']) : 'Other state guides'); ?></h3>
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

	<?php wholesale_guide_render_help(sprintf('Planning a Sign in %s?', $place_name), sprintf('Send your storefront photo, logo and your landlord&rsquo;s sign criteria. We&rsquo;ll suggest a size and style that fits %s rules and give you a price before you order.', esc_html($is_city ? $place['name'] : $state['name']))); ?>
</main>

<?php
get_footer();
