<?php
/**
 * /for-sign-companies/: outsource channel letter fabrication.
 *
 * Primary keyword: "outsource channel letter fabrication"; secondary
 * "channel letter fabricator for sign companies", "trade only sign
 * manufacturer". Written for an owner or estimator deciding whether to
 * build letters in-house or buy them in.
 *
 * @package litsign
 */

$brand = wholesale_trade_brand();

get_header();
?>
<main id="primary" class="tw-page tw-fsc">
	<section class="tw-hero">
		<div class="tw-wrap"><?php wholesale_trade_render_crumbs(); ?></div>
		<div class="tw-wrap tw-hero__in tw-hero__in--crumbs">
			<div>
				<p class="tw-eyebrow">For sign companies</p>
				<h1 class="tw-hero__title">Outsource channel letter fabrication <em>without losing the client</em></h1>
				<p class="tw-hero__text">Keep the sale, the survey, the permit and the install. Send the fabrication to a trade-only shop that has built lit letters since 2002, never installs, and never sells to your customers on this site.</p>
				<div class="tw-hero__actions">
					<a class="tw-btn tw-btn--glow" href="<?php echo esc_url(wholesale_trade_apply_url()); ?>">Apply for a trade account</a>
					<a class="tw-btn tw-btn--ghost-light" href="<?php echo esc_url($brand['phone_href']); ?>">Talk to trade sales</a>
				</div>
			</div>
			<div class="tw-hero__media">
				<img src="<?php echo esc_url(content_url('uploads/storefront-files/channel-letter-raceway-assembly.jpg')); ?>" alt="Cutaway drawing of an aluminum channel letter raceway with mounting tabs" loading="eager" fetchpriority="high" decoding="async">
			</div>
		</div>
	</section>

	<section class="tw-section" aria-labelledby="tw-inhouse">
		<div class="tw-wrap">
			<p class="tw-eyebrow">Build or buy</p>
			<h2 class="tw-h2" id="tw-inhouse">What changes when you stop building letters in-house</h2>
			<p class="tw-lead">Fabricating letters ties up floor space, a letter bender, LED stock and skilled hours. Buying them in turns that fixed cost into a per-job cost you can quote exactly.</p>
			<div class="tw-table-wrap">
				<table class="tw-table">
					<thead><tr><th scope="col"></th><th scope="col">Building in-house</th><th scope="col">Outsourcing to us</th></tr></thead>
					<tbody>
						<tr><th scope="row">Equipment</th><td>Letter bender, router, welding for stainless</td><td>None; you order online or send drawings</td></tr>
						<tr><th scope="row">Letter types you can sell</th><td>What your equipment and team can make</td><td>All 7, aluminum and stainless</td></tr>
						<tr><th scope="row">UL listing</th><td>Your own shop listing and label program</td><td>Every set UL listed and labeled</td></tr>
						<tr><th scope="row">Quoting</th><td>Material takeoffs and labor estimates</td><td>Trade price from the builder in minutes</td></tr>
						<tr><th scope="row">Your crew's time</th><td>Split between the shop and the field</td><td>Spent on surveys, permits and installs that bill</td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="tw-section tw-section--soft" aria-labelledby="tw-protect">
		<div class="tw-wrap">
			<p class="tw-eyebrow">Channel protection</p>
			<h2 class="tw-h2" id="tw-protect">Your client stays your client</h2>
			<ul class="tw-values tw-values--3">
				<li class="tw-value"><h3 class="tw-h3">Trade only</h3><p>This site sells only to approved trade accounts. Prices, the builder and checkout are behind a business login.</p></li>
				<li class="tw-value"><h3 class="tw-h3">No installation service</h3><p>We have no install crews, so we never compete for the install, the service call or the next sign.</p></li>
				<li class="tw-value"><h3 class="tw-h3">Your drawings stay yours</h3><p>Your shop drawings and your client relationship stay with you. We build to the drawing you approve.</p></li>
			</ul>
		</div>
	</section>

	<section class="tw-section" aria-labelledby="tw-flow">
		<div class="tw-wrap">
			<p class="tw-eyebrow">Workflow</p>
			<h2 class="tw-h2" id="tw-flow">How a job runs</h2>
			<ol class="tw-steps">
				<li class="tw-step"><h3 class="tw-h3">You sell &amp; survey</h3><p>Measure the fascia, confirm letter height, colors, mounting and power location with your client.</p></li>
				<li class="tw-step"><h3 class="tw-h3">You price &amp; order</h3><p>Build the set in the trade builder, or send vector art and your shop drawing to trade sales.</p></li>
				<li class="tw-step"><h3 class="tw-h3">We fabricate</h3><p>Letters are built in Washington or California, wired, tested and UL labeled.</p></li>
				<li class="tw-step"><h3 class="tw-h3">You install</h3><p>The set arrives crated with a full-size pattern and wiring diagram, ready for your crew and electrician.</p></li>
			</ol>
		</div>
	</section>

	<section class="tw-section tw-section--soft" aria-labelledby="tw-quote">
		<div class="tw-wrap tw-split">
			<div>
				<p class="tw-eyebrow">Quoting your client</p>
				<h2 class="tw-h2" id="tw-quote">Build every quote on three numbers</h2>
				<ol class="tw-checks" style="list-style:none">
					<li><strong>Letters:</strong> the trade price of the set from the builder, including raceway and power supplies.</li>
					<li><strong>Freight:</strong> shown at checkout for the delivery address.</li>
					<li><strong>Your work:</strong> survey, permit, install labor, lift and your margin.</li>
				</ol>
				<p>Offering a good, better and best option (for example front lit, dual lit and halo lit stainless) gives the client a choice instead of a yes or no.</p>
			</div>
			<div>
				<h2 class="tw-h3">Letter types to quote side by side</h2>
				<?php wholesale_trade_render_type_cards(array('standard-channel-letter-front-lit', 'standard-channel-letter-front-back-lit', 'hidden-back-halo-lit'), 3); ?>
			</div>
		</div>
	</section>

	<?php
	wholesale_trade_render_faq(array(
		array('q' => 'Do you sell to end customers?', 'a' => 'Not on this site. Storefront Sign Wholesale is trade only: prices and ordering open only to approved sign companies, installers and resellers.'),
		array('q' => 'Who installs the letters?', 'a' => 'Your crew or a licensed electrician. We never install; every set ships with a full-size mounting pattern, a wiring diagram and the installation manual.'),
		array('q' => 'Can you build to my shop drawings?', 'a' => 'Yes. Send your approved drawing and vector artwork with the order, and the letters are built to it. We contact you before production if a stroke is too thin to fabricate.'),
		array('q' => 'What do I need to apply?', 'a' => 'Your business details and website. A resale certificate is needed only if you buy tax-exempt, and we may ask for a business or contractor license during review.'),
	), 'Outsourcing questions');
	wholesale_trade_render_cta_band('Add a fabrication partner to your shop');
	?>
</main>
<?php
get_footer();
