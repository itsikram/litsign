<?php
/**
 * Trade site channel letters hub (/channel-letters/).
 *
 * Primary keyword: "wholesale channel letters". Helps a sign company pick the
 * right type for a job, compare construction, and plan mounting, then sends
 * them to the type page or the trade application.
 *
 * @package litsign
 */

$types = wholesale_trade_cl_types();
$approved = wholesale_trade_can_order();
$raceway_img = content_url('uploads/storefront-files/channel-letter-raceway-installation-parts.jpg');
$manual = content_url('uploads/storefront-files/channel-letter-installation-manual.pdf');
$color_chart = content_url('uploads/storefront-files/stainless-steel-channel-letter-color-chart.pdf');

get_header();
?>
<main id="primary" class="tw-page tw-hub">

	<section class="tw-hero tw-hub-hero" aria-labelledby="tw-hub-hero-title">
		<div class="tw-wrap">
			<?php wholesale_trade_render_crumbs(); ?>
		</div>
		<div class="tw-wrap tw-hero__in tw-hero__in--crumbs">
			<div class="tw-hub-hero__copy">
				<p class="tw-eyebrow">Made in the USA <span aria-hidden="true">·</span> UL listed <span aria-hidden="true">·</span> Built for sign shops</p>
				<h1 class="tw-hero__title" id="tw-hub-hero-title">Wholesale channel letters, <em>built for sign shops.</em></h1>
				<p class="tw-hub-hero__proof">7 channel letter styles <span aria-hidden="true">·</span> Built in Washington &amp; California <span aria-hidden="true">·</span> Ships nationwide</p>
				<p class="tw-hero__text">From bright front lit letters to refined halo and trimless styles, find the right construction for your client's storefront. We fabricate, wire and test every set, ready for your crew to install.</p>
				<div class="tw-hero__actions">
					<a class="tw-btn tw-btn--glow" href="#tw-compare">Compare all 7 types <span aria-hidden="true">→</span></a>
					<?php if ($approved) : ?>
						<a class="tw-btn tw-btn--ghost-light" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Open the builder</a>
					<?php else : ?>
						<a class="tw-btn tw-btn--ghost-light" href="<?php echo esc_url(wholesale_trade_apply_url()); ?>">Apply for trade pricing</a>
					<?php endif; ?>
				</div>
				<p class="tw-hub-hero__detail">Every set includes a wiring diagram and full-size mounting pattern for your installer.</p>
			</div>
		</div>
		<div class="tw-hub-hero__benefits">
			<div class="tw-wrap tw-hub-hero__benefits-in">
				<p><strong>UL listed</strong><span>Sign section labels on every set</span></p>
				<p><strong>5-year warranty</strong><span>Coverage across all 7 styles</span></p>
				<p><strong>Tested before shipping</strong><span>Ready for your installation crew</span></p>
			</div>
		</div>
	</section>

	<section class="tw-section" aria-labelledby="tw-pick">
		<div class="tw-wrap">
			<p class="tw-eyebrow">Choose by job</p>
			<h2 class="tw-h2" id="tw-pick">Which channel letter fits the job?</h2>
			<p class="tw-lead">Start from what the site and the client need. The table points to the type most shops quote first; every type page has the full construction and mounting notes.</p>
			<div class="tw-table-wrap">
				<table class="tw-table">
					<thead><tr><th scope="col">The job</th><th scope="col">Quote first</th><th scope="col">Why</th></tr></thead>
					<tbody>
						<tr><th scope="row">Strip center tenant with landlord sign criteria</th><td><a href="<?php echo esc_url(wholesale_trade_type_url('standard-channel-letter-front-lit')); ?>">Type 1 · Front lit</a></td><td>Face lit acrylic in matched colors is what most criteria ask for; raceway mount keeps wall penetrations to one</td></tr>
						<tr><th scope="row">Restaurant or salon that wants an evening look</th><td><a href="<?php echo esc_url(wholesale_trade_type_url('standard-channel-letter-back-lit')); ?>">Type 2 · Back lit</a></td><td>Light washes the wall; works on smooth, lighter finishes</td></tr>
						<tr><th scope="row">Flagship or corner storefront</th><td><a href="<?php echo esc_url(wholesale_trade_type_url('standard-channel-letter-front-back-lit')); ?>">Type 3 · Front &amp; back lit</a></td><td>A bright face plus a halo: a visible step up without stainless</td></tr>
						<tr><th scope="row">Office lobby, clinic or hotel</th><td><a href="<?php echo esc_url(wholesale_trade_type_url('hidden-back-halo-lit')); ?>">Type 4 · Halo lit stainless</a></td><td>Metal letter by day, halo by night, 35+ finishes for the designer</td></tr>
						<tr><th scope="row">Dark facade or detailed logo</th><td><a href="<?php echo esc_url(wholesale_trade_type_url('halo-reverse-acrylic-lit-channel-letters')); ?>">Type 5 · Reverse lit</a></td><td>The glowing acrylic back keeps a crisp outline on dark walls</td></tr>
						<tr><th scope="row">Design-led brand that dislikes trim cap</th><td><a href="<?php echo esc_url(wholesale_trade_type_url('inset-acrylic-face-lit-with-border-no-trimcap')); ?>">Type 6 · Trimless</a></td><td>Face lit with a thin metal border instead of plastic trim</td></tr>
						<tr><th scope="row">Modern brand with a script or rounded logo</th><td><a href="<?php echo esc_url(wholesale_trade_type_url('exposed-acrylic-face-lit-borderless-no-trimcap')); ?>">Type 7 · Borderless</a></td><td>The whole face edge glows; no trim and no border</td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="tw-section tw-section--soft" aria-labelledby="tw-all">
		<div class="tw-wrap">
			<h2 class="tw-h2" id="tw-all">All 7 wholesale channel letter types</h2>
			<p class="tw-lead">Types 1–3 are aluminum with acrylic faces. Types 4–7 are welded stainless steel, sanded and painted.</p>
			<?php wholesale_trade_render_type_cards(array(), 3); ?>
		</div>
	</section>

	<section class="tw-section" id="tw-compare" aria-labelledby="tw-compare-title">
		<div class="tw-wrap">
			<p class="tw-eyebrow">Construction</p>
			<h2 class="tw-h2" id="tw-compare-title">Compare construction at a glance</h2>
			<div class="tw-table-wrap">
				<table class="tw-table">
					<thead><tr><th scope="col">Type</th><th scope="col">Lighting</th><th scope="col">Face</th><th scope="col">Returns</th><th scope="col">Back</th></tr></thead>
					<tbody>
						<?php foreach ($types as $slug => $type) : ?>
							<tr>
								<th scope="row"><a href="<?php echo esc_url(wholesale_trade_type_url($slug)); ?>"><?php echo esc_html($type['type'] . ' · ' . $type['name']); ?></a></th>
								<td><?php echo esc_html($type['specs']['Lighting'] ?? ''); ?></td>
								<td><?php echo esc_html($type['specs']['Face'] ?? ''); ?></td>
								<td><?php echo esc_html($type['specs']['Returns'] ?? ''); ?></td>
								<td><?php echo esc_html($type['specs']['Back'] ?? ''); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p style="margin:16px 0 0;color:var(--tw-muted);font-size:15px">LED modules on every type: 0.7W / 1.2W, IP67, 160° beam. All 7 types are UL listed with a sign section label and carry a 5-year warranty.</p>
		</div>
	</section>

	<section class="tw-section tw-section--soft" aria-labelledby="tw-mount">
		<div class="tw-wrap tw-split">
			<div>
				<p class="tw-eyebrow">For your installer</p>
				<h2 class="tw-h2" id="tw-mount">Raceway, flush or standoff mounting</h2>
				<p>Tell us how the set will mount when you order. Face lit aluminum letters are often mounted on a raceway, so the crew makes one wall penetration and the landlord gets an easy removal later. Halo and reverse lit letters always go on standoffs, so the light has room to spread behind them.</p>
				<ul class="tw-checks">
					<li>Full-size pattern with each stud and wire hole marked</li>
					<li>Wiring diagram for the electrician who makes the final connection</li>
					<li>3 ft power lead; longer leads and an 8 ft extension cord can be added</li>
				</ul>
				<p><a class="tw-btn tw-btn--ghost" href="<?php echo esc_url($manual); ?>">Download the installation manual (PDF)</a></p>
			</div>
			<div>
				<img src="<?php echo esc_url($raceway_img); ?>" alt="Channel letter raceway with mounting hardware and wiring parts" loading="lazy" decoding="async">
			</div>
		</div>
	</section>

	<section class="tw-section" aria-labelledby="tw-files">
		<div class="tw-wrap tw-wrap--narrow">
			<p class="tw-eyebrow">Artwork</p>
			<h2 class="tw-h2" id="tw-files">Sending art for a channel letter order</h2>
			<ul class="tw-checks">
				<li>Vector file (AI, EPS or PDF) at final size, with all text converted to outlines</li>
				<li>Letter height and the overall sign width your client approved</li>
				<li>Face, return and trim cap colors (acrylic, 3M vinyl or the stainless finish name)</li>
				<li>Mounting method: raceway, flush or standoff</li>
				<li>Very thin strokes may need to be thickened before production; we tell you before we build</li>
			</ul>
			<p>Stainless finishes: <a href="<?php echo esc_url($color_chart); ?>">stainless steel color chart (PDF)</a>.</p>
		</div>
	</section>

	<?php
	wholesale_trade_render_faq(array(
		array('q' => 'What is the difference between halo lit and reverse lit letters?', 'a' => 'Both put the light behind the letter. Type 4 halo lit letters hide a clear Lexan back, so only the halo on the wall shows. Type 5 reverse lit letters have an exposed translucent acrylic back that glows itself and gives a crisper outline, which helps on dark walls.'),
		array('q' => 'Aluminum or stainless steel?', 'a' => 'Aluminum types 1–3 use acrylic faces with a trim cap and are the usual choice for tenant and storefront signs. Stainless types 4–7 are welded and painted metal for halo, reverse, trimless and borderless looks.'),
		array('q' => 'Can you match brand colors?', 'a' => 'Aluminum faces use colored acrylic or 3M translucent, day/night or printed vinyl. Stainless letters come in 35+ standard finishes, and a custom Pantone C color can be requested.'),
		array('q' => 'Do the letters come with mounting hardware?', 'a' => 'They come with a full-size mounting pattern, a wiring diagram and the installation manual. Fasteners are not included, so your crew can choose what suits the wall.'),
		array('q' => 'How do I get pricing?', 'a' => 'Apply for a trade account. Approved accounts see trade pricing on every type and can price complete sets in the online builder.'),
	), 'Channel letter questions from sign shops');
	wholesale_trade_render_cta_band('Price your next channel letter set');
	?>
</main>
<?php
get_footer();
