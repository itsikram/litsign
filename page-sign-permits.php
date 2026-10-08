<?php
/**
 * Sign permit help hub (/sign-permits/).
 *
 * Explains how sign permits work in general so the state and city guides
 * only need their own local facts. We manufacture and ship; we never pull
 * permits or install, and the page says so.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$install_url = wholesale_guide_url('how-to-install-channel-letters');
$locations_url = wholesale_guide_url('locations');
$ul_number = wholesale_guide_ul_file_number();

// What ships with every lit sign today (confirmed on the site). Add an item
// here only once it is offered to every customer.
$included = array(
	'A UL listed sign with its UL label(s) on the sign sections',
	'A wiring diagram for your electrician',
	'An installation pattern for marking mounting holes and wire penetrations',
	'A sign that was tested before it shipped',
);

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'Do I need a permit for a channel letter sign?',
		'a' => 'Almost always. Most cities and counties require a sign permit for any permanent exterior business sign, and a lit sign also needs an electrical permit for the power connection. A few small or temporary signs are exempt in some places, but exemptions rarely cover illuminated signs.',
	),
	array(
		'q' => 'Who applies for the sign permit?',
		'a' => 'It depends on the city. Some let the business or property owner apply; many only accept applications from a licensed sign or electrical contractor, and the electrical permit is usually pulled by the licensed electrician who connects the sign. Ask your installer to handle both, and check our <a href="' . esc_url($locations_url ? $locations_url : home_url('/')) . '">state guides</a> for who issues permits where you are.',
	),
	array(
		'q' => 'Should I get the permit before I order my sign?',
		'a' => 'Check the rules first. Before you order, confirm the allowed sign size, lighting and mounting with your landlord&rsquo;s sign criteria and your city&rsquo;s sign code, so the sign you order is one that can be approved. Our signs are made to order and can&rsquo;t be returned if a permit is refused.',
	),
	array(
		'q' => 'What does the city need to see in a sign permit application?',
		'a' => 'Typically a drawing of the sign with its dimensions and letter height, a picture or elevation of the building front showing where it goes, the size of the wall or tenant frontage, how it mounts, and whether it is lit. Lit signs usually need proof the sign is UL listed (the label on the sign) and an electrical permit.',
	),
	array(
		'q' => 'Is a UL listed sign required?',
		'a' => 'The National Electrical Code requires electric signs to be listed and labeled, and inspectors look for the label on the sign. Our outdoor channel letter signs are UL listed and ship with their labels in place.',
	),
	array(
		'q' => 'Does Storefront Sign Online pull permits or install signs?',
		'a' => 'No. We manufacture your sign in Renton, WA, test it and ship it to you. A licensed sign installer or electrician in your area handles the permit and installation. We include the wiring diagram and install pattern they need, and we&rsquo;re happy to answer their questions by phone.',
	),
));

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Before you order</p>
			<h1 id="gd-title" class="sf-hero-title">Sign Permits for Lit Storefront Signs</h1>
			<p class="sf-hero-lead">Nearly every city asks for a permit before a lit business sign goes up. Here&rsquo;s how the process usually works, what the permit office and your electrician will ask for, and what comes in the box with every sign we ship.</p>
			<ul class="gd-facts">
				<li><strong>Usually needed</strong><span>A sign permit, plus an electrical permit for the power connection</span></li>
				<li><strong>Who applies</strong><span>Often your licensed sign installer or electrician</span></li>
				<li><strong>Check first</strong><span>Your lease&rsquo;s sign criteria and your city&rsquo;s sign code</span></li>
				<li><strong>We supply</strong><span>A UL listed, tested sign with wiring diagram and install pattern</span></li>
			</ul>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-how-title">
		<div class="container sf-prose">
			<p class="cl-kicker">The usual process</p>
			<h2 id="gd-how-title" class="cl-section-title">How Sign Permits Work</h2>
			<p>Sign rules in the US are local. Your city (or your county, outside city limits) decides what size, height, lighting and placement is allowed, and its building or planning department issues the permit. States mostly regulate who may do the electrical work. That&rsquo;s why the same sign can be approved in one town and need changes in the next.</p>
			<ol class="gd-steps">
				<li>
					<h3>Read your lease and the landlord&rsquo;s sign criteria</h3>
					<p>Shopping centers and many commercial buildings publish sign criteria: letter height, colors, lighting style, raceway color and mounting. Landlord approval is often needed before the city will even look at your application.</p>
				</li>
				<li>
					<h3>Check the city&rsquo;s sign code for your zone</h3>
					<p>Sign codes usually limit sign area as a share of your building frontage, cap letter height, and may restrict lighting near homes or in historic districts. Your city&rsquo;s permit counter can tell you which zone and sign rules apply to your address.</p>
				</li>
				<li>
					<h3>Size and design the sign to fit</h3>
					<p>Use the limits you found to choose the letter height and layout. Our <a href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">sign builder</a> shows the sign&rsquo;s overall size as you design, or <a href="<?php echo esc_url($contact_url); ?>">send us your logo and the criteria</a> and we&rsquo;ll suggest a size that fits.</p>
				</li>
				<li>
					<h3>Your installer files for the permits</h3>
					<p>The application usually includes a sign drawing with dimensions, a photo or elevation of the building showing placement, mounting details and the electrical details. In many cities only a licensed sign or electrical contractor can apply, and the electrical permit is pulled by the electrician who makes the connection.</p>
				</li>
				<li>
					<h3>Install after approval, then pass inspection</h3>
					<p>Install only after the permit is issued. Lit signs are typically inspected after the electrical connection is made, and the inspector checks the sign&rsquo;s listing label, the disconnect and the wiring.</p>
				</li>
			</ol>
			<div class="gd-note gd-note--warn">
				<p><strong>Order after you know the rules.</strong> Every sign is made to order for your business, so it can&rsquo;t be returned if a permit is refused. A few minutes with your landlord and city before you order prevents that.</p>
			</div>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-electric-title">
		<div class="container sf-prose">
			<p class="cl-kicker">For your electrician</p>
			<h2 id="gd-electric-title" class="cl-section-title">What Your Electrician Needs</h2>
			<p>The electrical side of a sign is covered by Article 600 of the National Electrical Code (NFPA 70), which most states adopt with their own amendments. Your electrician will plan for:</p>
			<ul class="gd-checklist">
				<li><strong>A power source at the sign location.</strong> Commercial storefronts often already have a dedicated sign circuit at the entrance; if not, the electrician runs one.</li>
				<li><strong>A disconnect</strong> for the sign, as the code and local inspector require.</li>
				<li><strong>The sign&rsquo;s listing label.</strong> Inspectors look for it on the sign itself.</li>
				<li><strong>Where the power supplies go.</strong> The wiring diagram shows how the letters connect to their power supplies; your electrician mounts the supplies in a location rated for them.</li>
				<li><strong>Wire penetrations through the wall</strong>, marked on the install pattern, sealed against water.</li>
				<li><strong>The electrical permit</strong>, where the state or city requires one, and the inspection after hookup.</li>
			</ul>
			<p>Licensing for this work differs by state: some states license sign electricians or sign contractors specifically, others leave electrical licensing to each city. Our <a href="<?php echo esc_url($locations_url ? $locations_url : home_url('/')); ?>">state guides</a> list the licensing agency for each state we cover.<?php if ($install_url) : ?> For the mounting steps, see the <a href="<?php echo esc_url($install_url); ?>">channel letter installation guide</a>.<?php endif; ?></p>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-included-title">
		<div class="container sf-prose">
			<p class="cl-kicker">In the box</p>
			<h2 id="gd-included-title" class="cl-section-title">What Comes With Every Lit Sign</h2>
			<ul class="gd-checklist">
				<?php foreach ($included as $item) : ?>
					<li><?php echo esc_html($item); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ($ul_number) : ?>
				<p>Our UL file number is <strong><?php echo esc_html($ul_number); ?></strong>. Inspectors can look it up in UL&rsquo;s Product iQ database.</p>
			<?php endif; ?>
			<p>Need the sign&rsquo;s exact dimensions or letter heights for your application before it ships? <a href="<?php echo esc_url($contact_url); ?>">Contact us</a> with your order or quote number.</p>
			<div class="gd-note">
				<p><strong>We build and ship; your local pro installs.</strong> Storefront Sign Online doesn&rsquo;t pull permits or install signs. Your licensed sign installer or electrician handles the permit, mounting and electrical connection, and can call us at <a href="tel:+18664362101">866-436-2101</a> with any questions about the sign.</p>
			</div>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="gd-faq-title">
		<div class="container">
			<p class="cl-kicker">Questions</p>
			<h2 id="gd-faq-title" class="cl-section-title">Sign Permit FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php wholesale_guide_render_help('Not Sure What Your City Allows?', 'Send us your storefront photo, logo and any sign criteria from your landlord. We&rsquo;ll suggest a size and style that fits before you order.'); ?>
</main>

<?php
get_footer();
