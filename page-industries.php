<?php
/**
 * Industries hub (/industries/): links to every industry page in
 * data/industries.php.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');

$industry_links = array();
foreach (wholesale_guide_industries() as $slug => $industry) {
	$url = wholesale_guide_url('industries/' . $slug);
	if ($url) {
		$industry_links[] = array($url, $industry['name'], $industry['needs'][0][0]);
	}
}

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'Which sign should my business start with?',
		'a' => 'Start with the main building sign customers see from the road, usually lit channel letters, then add signs at eye level: window graphics, a sidewalk sign and a banner for openings and sales.',
	),
	array(
		'q' => 'Can you help me choose signs for my type of business?',
		'a' => 'Yes. <a href="' . esc_url($contact_url) . '">Send us your logo and a photo of your storefront</a>, and a sign specialist will recommend signs and sizes and price them.',
	),
	array(
		'q' => 'Do you install the signs?',
		'a' => 'No. We make, test and ship your sign to any US state. Lit signs are mounted and connected by your licensed local sign installer or electrician, using the wiring diagram and install pattern we include. Banners, flags and portable displays need no installer.',
	),
	array(
		'q' => 'Do I need a permit for a business sign?',
		'a' => 'Most cities require a permit for permanent exterior signs, and lit signs usually need an electrical permit too. Our <a href="' . esc_url(home_url('/sign-permits/')) . '">sign permit guide</a> explains what the city asks for.',
	),
));

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Signs by industry</p>
			<h1 id="gd-title" class="sf-hero-title">Business Signs for Your Industry</h1>
			<p class="sf-hero-lead">Restaurants, salons, clinics, shops, franchises and event teams need different things from their signs. Pick your industry to see the signs that fit, how to size them, and prices.</p>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-list-title">
		<div class="container">
			<h2 id="gd-list-title" class="cl-section-title">Choose Your Industry</h2>
			<ul class="gd-link-grid">
				<?php foreach ($industry_links as $link) : ?>
					<li><a href="<?php echo esc_url($link[0]); ?>"><?php echo esc_html($link[1]); ?><small><?php echo esc_html($link[2]); ?></small></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-types-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Every industry</p>
			<h2 id="gd-types-title" class="cl-section-title">Signs Most Businesses Need</h2>
			<p class="cl-section-lead">Whatever you sell, customers find you in the same order: from the road, at the door, then inside. These are the signs that cover each step.</p>
			<ol class="gd-steps">
				<li>
					<h3>A building sign people see from the road</h3>
					<p>Lit <a href="<?php echo esc_url(wholesale_category_url('channel-letters')); ?>">channel letters</a> are the main sign for most storefronts because they read clearly day and night. We build them in Washington and California and ship them ready for your licensed local installer. See <a href="<?php echo esc_url(home_url('/channel-letter-cost/')); ?>">what channel letters cost</a>.</p>
				</li>
				<li>
					<h3>Window and door graphics</h3>
					<p><a href="<?php echo esc_url(wholesale_category_url('adhesive-products')); ?>">Window graphics</a> show your hours, logo and services at eye level. Perforated window vinyl keeps the view out, and frosted film adds privacy for clinics and offices.</p>
				</li>
				<li>
					<h3>A sidewalk sign at the entrance</h3>
					<p><a href="<?php echo esc_url(wholesale_category_url('signicade-a-frames')); ?>">A-frame sidewalk signs</a> catch foot traffic with daily specials, menus and directions, and you can swap the graphics as offers change.</p>
				</li>
				<li>
					<h3>Banners and flags for openings and sales</h3>
					<p><a href="<?php echo esc_url(wholesale_category_url('banners')); ?>">Vinyl banners</a> and <a href="<?php echo esc_url(wholesale_category_url('advertising-flags')); ?>">feather flags</a> announce grand openings, sales and events while your permanent sign is being made or permitted.</p>
				</li>
				<li>
					<h3>Displays for events and trade shows</h3>
					<p><a href="<?php echo esc_url(wholesale_category_url('banner-stands')); ?>">Retractable banner stands</a>, <a href="<?php echo esc_url(wholesale_category_url('trade-show-products')); ?>">trade show displays</a> and <a href="<?php echo esc_url(wholesale_category_url('custom-event-tents')); ?>">event tents</a> take your brand off-site to markets, fairs and conferences.</p>
				</li>
			</ol>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="gd-faq-title">
		<div class="container">
			<p class="cl-kicker">Questions</p>
			<h2 id="gd-faq-title" class="cl-section-title">Choosing Signs FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php wholesale_guide_render_help(); ?>
</main>

<?php
get_footer();
