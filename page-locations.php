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
$permits_url = wholesale_guide_url('sign-permits');
$install_url = wholesale_guide_url('how-to-install-channel-letters');
$shipping_url = wholesale_guide_url('shipping-returns');

$state_links = array();
foreach (wholesale_guide_states() as $slug => $state) {
	$url = wholesale_guide_url('locations/' . $slug);
	if ($url) {
		$state_links[] = array($url, $state['name'], isset($state['facts']['Sign license']) ? $state['facts']['Sign license'] : (isset($state['facts']['Electrician licensing']) ? $state['facts']['Electrician licensing'] : ''));
	}
}

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'Do you ship storefront signs to every state?',
		'a' => 'Yes. Every sign is made to order in Renton, Washington and shipped to your address in any of the 50 states, with standard, 3-day, 2-day and overnight options.',
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
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Shipped to all 50 states</p>
			<h1 id="gd-title" class="sf-hero-title">Storefront Signs Shipped to Your State</h1>
			<p class="sf-hero-lead">We make custom channel letters and storefront signs in Renton, Washington and ship them anywhere in the US, ready for a licensed local installer. Our state guides cover who may install a lit sign where you are and how permits work, with links to the official sources.</p>
			<ul class="gd-facts">
				<li><strong>Made in</strong><span>Renton, WA, USA</span></li>
				<li><strong>Ships to</strong><span>All 50 states</span></li>
				<li><strong>Installed by</strong><span>Your licensed local sign installer or electrician</span></li>
				<li><strong>In the box</strong><span>Tested sign, wiring diagram and install pattern</span></li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-states-title">
		<div class="container">
			<p class="cl-kicker">State guides</p>
			<h2 id="gd-states-title" class="cl-section-title">Choose Your State</h2>
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

	<section class="sf-section sf-section--tint" aria-labelledby="gd-works-title">
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
					<p>Your sign is made to order in Renton and tested before it ships. Choose standard, 3-day, 2-day or overnight shipping<?php if ($shipping_url) : ?> (<a href="<?php echo esc_url($shipping_url); ?>">options and prices</a>)<?php endif; ?>.</p>
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
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php wholesale_guide_render_help(); ?>
</main>

<?php
get_footer();
