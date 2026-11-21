<?php
/**
 * Locations hub (/locations/): every state guide, plus how shipping,
 * licensing and permits work wherever you are. States are added by
 * dropping a file into data/locations/.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$permits_url = retails_guide_url('sign-permits');
$install_url = retails_guide_url('how-to-install-channel-letters');
$shipping_url = retails_guide_url('shipping-returns');

// Keep the directory, its ItemList schema and the sitemap in sync.
$guides = retails_location_public_guides();
$states = retails_guide_states();
$state_links = array();
$city_groups = array();
$state_pages = array();
$city_pages = array();
foreach ($guides as $guide) {
	$slug = $guide['state'];
	$state = $states[$slug];
	if ('state' === $guide['type']) {
		$state_pages[] = $guide['page'];
		$state_links[$slug] = array($guide['loc'], $state['name'], $state['facts']['Sign license'] ?? $state['facts']['Electrician licensing'] ?? '');
	} else {
		$city_pages[] = $guide['page'];
		$city_groups[$slug][$guide['loc']] = $guide['title'] . ', ' . $state['abbr'];
	}
}
$GLOBALS['retails_page_items'] = array_merge($state_pages, $city_pages);

add_filter('retails_seo_webpage_schema', static function ($page) use ($guides) {
	$page['dateModified'] = retails_location_directory_modified($guides);
	return $page;
});

retails_seo_set_page_faq(array(
	array(
		'q' => 'Do you ship storefront signs to every state?',
		'a' => 'Yes. We make each sign to order and ship it from Washington or California to all 50 states. Enter your ZIP code at checkout to see the services and delivery estimates available for your address.',
	),
	array(
		'q' => 'Do you install signs in my state?',
		'a' => 'No. We manufacture, test and ship your sign. A licensed sign installer or electrician in your area mounts and connects it, using the wiring diagram and install pattern we include.',
	),
	array(
		'q' => "My state isn't listed. Can I still order?",
		'a' => 'Yes. We ship everywhere in the US; we&rsquo;re adding state guides over time. Our <a href="' . esc_url($permits_url ? $permits_url : $contact_url) . '">sign permit guide</a> covers the steps that apply in every state.',
	),
	array(
		'q' => 'How do I find a sign installer near me?',
		'a' => 'Search for licensed sign contractors or electrical contractors in your city, check the license with your state or city licensing agency (our state guides link to them), and ask for proof of insurance. Send them our wiring diagram and install pattern with your quote request.',
	),
));

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php retails_breadcrumbs(); ?>
			<p class="cl-kicker">Shipped to all 50 states</p>
			<h1 id="gd-title" class="sf-hero-title">Storefront Signs Shipped to Your State</h1>
			<p class="sf-hero-lead">Find the sign permit office, installer licensing rules and official sources for your state or city. We build custom LED channel letters in Washington and California and ship them to all 50 states for your licensed installer.</p>
			<nav class="gd-jump-links" aria-label="Location guide directory">
				<?php if ($state_links) : ?><a href="#gd-states-title">Browse state guides</a><?php endif; ?>
				<?php if ($city_groups) : ?><a href="#gd-major-cities-title">Browse city guides</a><?php endif; ?>
				<a href="#gd-works-title">How shipping and installation work</a>
			</nav>
			<ul class="gd-facts">
				<li><strong>Made in</strong><span>Washington &amp; California, USA</span></li>
				<li><strong>Ships to</strong><span>All 50 states</span></li>
				<li><strong>Installed by</strong><span>Your licensed local sign installer or electrician</span></li>
				<li><strong>In the box</strong><span>Tested sign, wiring diagram and install pattern</span></li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-states-title">
		<div class="container">
			<p class="cl-kicker">State guides</p>
			<h2 id="gd-states-title" class="cl-section-title">State Sign Permit Guides</h2>
			<p class="cl-section-lead">Each guide covers the state&rsquo;s sign and electrical licensing, how permits work in its largest cities, shipping, and weather to plan for.</p>
			<?php if ($state_links) : ?>
				<ul class="gd-link-grid">
					<?php foreach ($state_links as $link) : ?>
						<li><a href="<?php echo esc_url($link[0]); ?>"><?php echo esc_html($link[1]); ?><?php if ($link[2]) : ?><small><?php echo wp_kses_post($link[2]); ?></small><?php endif; ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p>Don&rsquo;t see your state yet? We still ship there. Read the <a href="<?php echo esc_url($permits_url ? $permits_url : $contact_url); ?>">sign permit guide</a> or <a href="<?php echo esc_url($contact_url); ?>">ask us</a> about your project.</p>
		</div>
	</section>

	<?php if ($city_groups) : ?>
		<section class="sf-section sf-section--tint" aria-labelledby="gd-major-cities-title">
			<div class="container">
				<p class="cl-kicker">City guides</p>
				<h2 id="gd-major-cities-title" class="cl-section-title">Sign Permit Guides for Major Cities</h2>
				<p class="cl-section-lead">Large cities run their own sign codes and permit offices. Each city guide covers who issues the permit, the local limits on banners and window graphics, and who may install a lit sign there.</p>
				<div class="gd-city-groups">
				<?php foreach ($city_groups as $slug => $cities) : ?>
						<div class="gd-city-group">
						<h3>
							<?php if (isset($state_links[$slug])) : ?>
								<a href="<?php echo esc_url($state_links[$slug][0]); ?>"><?php echo esc_html($states[$slug]['name']); ?></a>
							<?php else : ?>
								<?php echo esc_html($states[$slug]['name']); ?>
							<?php endif; ?>
						</h3>
							<ul>
							<?php foreach ($cities as $city_url => $city_name) : ?>
									<li><a href="<?php echo esc_url($city_url); ?>"><?php echo esc_html($city_name); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="sf-section<?php echo $city_groups ? '' : ' sf-section--tint'; ?>" aria-labelledby="gd-works-title">
		<div class="container sf-prose">
			<p class="cl-kicker">How it works anywhere</p>
			<h2 id="gd-works-title" class="cl-section-title">Ordering a Sign From Out of State</h2>
			<ol class="gd-steps">
				<li>
					<h3>Check your local rules</h3>
					<p>Get your landlord&rsquo;s sign criteria and your city&rsquo;s sign limits, then line up a licensed installer. Your state guide shows who licenses sign work.</p>
				</li>
				<li>
					<h3>Design and price your sign</h3>
					<p>Use the <a href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">sign builder</a> or see <a href="<?php echo esc_url(home_url('/channel-letter-cost/')); ?>">channel letter prices</a>, or <a href="<?php echo esc_url($contact_url); ?>">send your logo for a quote</a>.</p>
				</li>
				<li>
					<h3>We build, test and ship</h3>
					<p>Your sign is made to order and tested before it ships from Washington or California. Checkout shows the delivery options for your address<?php if ($shipping_url) : ?> (<a href="<?php echo esc_url($shipping_url); ?>">see shipping details</a>)<?php endif; ?>.</p>
				</li>
				<li>
					<h3>Your installer puts it up</h3>
					<p>With the permit issued, your installer mounts the sign using the install pattern and connects it per the wiring diagram<?php if ($install_url) : ?> (<a href="<?php echo esc_url($install_url); ?>">installation overview</a>)<?php endif; ?>.</p>
				</li>
			</ol>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="gd-faq-title">
		<div class="container">
			<p class="cl-kicker">Questions</p>
			<h2 id="gd-faq-title" class="cl-section-title">Shipping and Installation FAQ</h2>
			<div class="cl-faq-list">
				<?php retails_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php retails_guide_render_help(); ?>
</main>

<?php
get_footer();
