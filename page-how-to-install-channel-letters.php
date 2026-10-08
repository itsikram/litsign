<?php
/**
 * Channel letter installation overview (/how-to-install-channel-letters/).
 *
 * Written for the customer's licensed installer: raceway vs. direct mount,
 * the install pattern, power and inspection. We don't install, and the page
 * never suggests we do. It is an overview, not a substitute for the wiring
 * diagram and pattern that ship with each sign.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$permits_url = wholesale_guide_url('sign-permits');
$warranty_url = wholesale_guide_url('warranty');
$locations_url = wholesale_guide_url('locations');
$ul_number = wholesale_guide_ul_file_number();

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'Can I install channel letters myself?',
		'a' => 'Usually not. The electrical connection must be made by a licensed electrician or licensed sign contractor in almost every state, and some states, such as Washington, require an electrical license to install any part of a lit sign, not only to wire it. Lit signs usually also need an electrical permit and inspection. Most owners hire one licensed local sign installer to do both.',
	),
	array(
		'q' => 'Raceway or direct mount: which should I choose?',
		'a' => 'Choose a raceway if your landlord requires one, if the wall is hard to drill or to reach from behind, or if you want fewer holes and an easier move-out. Choose direct (flush) mount for the cleanest look when the wall is accessible from inside and the landlord allows it.',
	),
	array(
		'q' => 'How long does channel letter installation take?',
		'a' => 'For a typical storefront sign, an experienced crew usually needs a few hours to a day on site once the permit is issued and power is at the wall. Large signs, difficult walls or lift access take longer. Ask your installer for their estimate.',
	),
	array(
		'q' => "What if a letter doesn't light after installation?",
		'a' => 'Ask your electrician to check the connections against the wiring diagram first. If a letter or power supply still doesn&rsquo;t work, contact us before any repair work: call <a href="tel:+18664362101">866-436-2101</a> or email TR@StorefrontSignOnline.com, and we&rsquo;ll walk through it under the warranty.',
	),
	array(
		'q' => 'Do you install signs or send an installer?',
		'a' => 'No. We manufacture and test your sign and ship it to you anywhere in the US. Your own licensed sign installer or electrician installs it, using the wiring diagram and install pattern included with the sign.',
	),
));

get_header();
?>

<main id="primary" class="sf-page gd-page">
	<section class="sf-hero" aria-labelledby="gd-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Installation overview</p>
			<h1 id="gd-title" class="sf-hero-title">How to Install Channel Letters</h1>
			<p class="sf-hero-lead">An overview for you and your licensed installer: how raceway and direct-mount letters go up, how the install pattern is used, and how power is connected and inspected. Always follow the wiring diagram and install pattern that ship with your sign.</p>
			<div class="gd-note">
				<p><strong>Who installs it?</strong> We build, test and ship your sign. A licensed sign installer or electrician in your area mounts it and makes the electrical connection. We don&rsquo;t install signs.</p>
			</div>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-before-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Before the sign arrives</p>
			<h2 id="gd-before-title" class="cl-section-title">Get Ready to Install</h2>
			<ul class="gd-checklist">
				<li><strong>Permits issued.</strong> Most cities require a sign permit and an electrical permit before installation.<?php if ($permits_url) : ?> See <a href="<?php echo esc_url($permits_url); ?>">how sign permits work</a>.<?php endif; ?></li>
				<li><strong>Power planned.</strong> Your electrician confirms there is a circuit for the sign at the wall, or plans to run one, and where the disconnect will go.</li>
				<li><strong>Wall checked.</strong> Know what&rsquo;s behind the fascia (wood framing, metal studs, concrete block, EIFS) so the installer brings the right anchors, and whether the inside of the wall can be reached.</li>
				<li><strong>Access arranged.</strong> A ladder, scaffold or lift for the sign height, and any landlord or property manager sign-off on the day.</li>
				<li><strong>Delivery inspected.</strong> When the sign arrives, check the packaging before you sign for it and report any damage right away (see <a href="<?php echo esc_url(wholesale_guide_url('shipping-returns') ? wholesale_guide_url('shipping-returns') : home_url('/terms-conditions/')); ?>">shipping damage</a>).</li>
			</ul>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-mount-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Choose a mounting method</p>
			<h2 id="gd-mount-title" class="cl-section-title">Raceway Mount vs. Direct Mount</h2>
			<table class="gd-table">
				<thead>
					<tr><th scope="col"></th><th scope="col">Raceway mount</th><th scope="col">Direct (flush) mount</th></tr>
				</thead>
				<tbody>
					<tr><th scope="row">How it works</th><td>Letters are fixed to a metal box (the raceway) that holds the wiring; the raceway is fastened to the wall as one unit.</td><td>Each letter is fastened to the wall on its own, with wiring passing through the wall behind each letter.</td></tr>
					<tr><th scope="row">Holes in the building</th><td>Few: the raceway&rsquo;s fasteners and one power entry.</td><td>More: fasteners for every letter plus wire penetrations.</td></tr>
					<tr><th scope="row">Access behind the wall</th><td>Not usually needed.</td><td>Usually needed to connect the wiring.</td></tr>
					<tr><th scope="row">Look</th><td>A visible bar behind the letters, often painted to match the wall.</td><td>Letters appear to float on the wall; cleanest look.</td></tr>
					<tr><th scope="row">Moving out</th><td>Easier: the sign comes down as one unit.</td><td>Each letter comes off, and the holes need patching.</td></tr>
					<tr><th scope="row">Often chosen when</th><td>The landlord requires it, or the wall is hard to drill or reach from inside.</td><td>Brand look matters most and the wall is accessible.</td></tr>
				</tbody>
			</table>
			<p>You choose raceway or no raceway when you design your sign. If your landlord&rsquo;s sign criteria specify a mounting method, follow it.</p>
		</div>
	</section>

	<section class="sf-section" aria-labelledby="gd-steps-title">
		<div class="container sf-prose">
			<p class="cl-kicker">On installation day</p>
			<h2 id="gd-steps-title" class="cl-section-title">Installation Steps (Overview)</h2>
			<h3>Direct mount</h3>
			<ol class="gd-steps">
				<li>
					<h3>Position the install pattern</h3>
					<p>Tape the installation pattern to the wall, level it, and confirm it is centered where the permit drawing shows the sign.</p>
				</li>
				<li>
					<h3>Mark and drill</h3>
					<p>Mark the mounting points and wire holes through the pattern, remove it, and drill using anchors suited to the wall material.</p>
				</li>
				<li>
					<h3>Hang the letters</h3>
					<p>Fasten each letter at its marked position, feeding its wires through the wall, and seal the penetrations against water.</p>
				</li>
				<li>
					<h3>Connect the power supplies</h3>
					<p>The electrician connects the letters to the power supplies and the power supplies to the sign circuit exactly as the wiring diagram shows, with the disconnect in place.</p>
				</li>
				<li>
					<h3>Test and inspect</h3>
					<p>Power the sign on, check every letter lights evenly, then schedule the electrical inspection if your permit requires it.</p>
				</li>
			</ol>
			<h3>Raceway mount</h3>
			<ol class="gd-steps">
				<li>
					<h3>Mark the raceway position</h3>
					<p>Level the raceway&rsquo;s position on the wall and mark its fastening points and the single power entry.</p>
				</li>
				<li>
					<h3>Fasten the raceway</h3>
					<p>Fasten the raceway (with the letters attached) to the wall using anchors suited to the wall material, and seal the power entry.</p>
				</li>
				<li>
					<h3>Connect and test</h3>
					<p>The electrician brings the sign circuit to the raceway and connects it per the wiring diagram, then the sign is tested and inspected.</p>
				</li>
			</ol>
			<div class="gd-note gd-note--warn">
				<p><strong>This is an overview, not a substitute for the paperwork in the box.</strong> Your sign&rsquo;s wiring diagram, install pattern, the National Electrical Code and local rules always take priority. Electrical work must be done by a licensed professional.</p>
			</div>
		</div>
	</section>

	<section class="sf-section sf-section--tint" aria-labelledby="gd-after-title">
		<div class="container sf-prose">
			<p class="cl-kicker">After installation</p>
			<h2 id="gd-after-title" class="cl-section-title">Keep Your Sign Looking New</h2>
			<p>LED channel letters need little care. Clean the faces with mild soap and water and a soft cloth; avoid solvents that can cloud acrylic. If a letter goes dark, have your electrician check its connection first, then contact us before anyone opens or repairs the sign: unapproved repair work can void the warranty.<?php if ($warranty_url) : ?> See <a href="<?php echo esc_url($warranty_url); ?>">what the warranty covers</a>.<?php endif; ?></p>
			<?php if ($ul_number) : ?>
				<p>Inspectors can confirm our UL listing under file number <strong><?php echo esc_html($ul_number); ?></strong>.</p>
			<?php endif; ?>
			<?php if ($locations_url) : ?>
				<p>Looking for licensing rules in your state? Our <a href="<?php echo esc_url($locations_url); ?>">state guides</a> list who licenses sign electricians and where permits are issued.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="gd-faq-title">
		<div class="container">
			<p class="cl-kicker">Questions</p>
			<h2 id="gd-faq-title" class="cl-section-title">Installation FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<?php wholesale_guide_render_help('Questions From Your Installer?', 'Your installer or electrician can call us during business hours with questions about the wiring diagram, install pattern or power supplies. Have your order number ready.'); ?>
</main>

<?php
get_footer();
