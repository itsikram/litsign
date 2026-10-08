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
