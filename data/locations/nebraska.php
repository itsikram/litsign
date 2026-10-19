<?php
/**
 * Nebraska state guide (/locations/nebraska/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'nebraska',
	'name' => 'Nebraska',
	'abbr' => 'NE',
	'status' => 'review',
	'neighbors' => array('south-dakota', 'iowa', 'missouri', 'kansas', 'colorado', 'wyoming'),
	'seo_title' => 'Storefront Signs Shipped to Nebraska | Electrician Guide',
	'seo_description' => 'Channel letters shipped to Nebraska, plus who can connect them (State Electrical Division contractors and special electricians) and local sign rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Nebraska',
	'intro' => array(
		'Nebraska businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Nebraska electrician.',
		'The Nebraska State Electrical Division licenses electrical contractors, master and journeyman electricians, and special electricians. Nebraska&rsquo;s law defines a <strong>special electrician</strong> as someone licensed for special classes of wiring, and sign installation is one of those classes.',
		'Journeyman electricians in Nebraska may only wire or install while employed by a licensed contractor or master electrician, so the company you hire matters as much as the person on the ladder.',
	),
	'facts' => array(
		'Licensing agency' => 'Nebraska State Electrical Division',
		'Sign work' => 'Special electrician (sign installation class)',
		'Contractors' => 'Electrical contractor; Class B contractor (systems up to 400 amps)',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Nebraska',
		'body' => array(
			'Nebraska&rsquo;s Electrical Act licenses <strong>electrical contractors</strong>, who may bid, plan, lay out, install and supervise electrical work anywhere in the state, and Class B contractors limited to systems of up to 400 amps. Journeyman, Class B journeyman and residential journeyman electricians may wire or install only while employed by a licensed contractor or master electrician.',
			'The <strong>special electrician</strong> license covers special classes of work, including irrigation and well pump wiring, air conditioning and refrigeration, and sign installation. Check a license on the State Electrical Division&rsquo;s license search before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Nebraska State Electrical Division',
			'Sign work' => 'Special electrician (sign installation)',
			'Contractor' => 'Electrical contractor (or Class B, up to 400 amps)',
			'Journeymen' => 'Must work for a licensed contractor or master',
		),
		'sources' => array(
			array('Nebraska State Electrical Division: License descriptions', 'https://www.nebraska.gov/sed/lists/descriptions.html'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Nebraska',
		'body' => array(
			'Nebraska cities and counties issue sign permits under their own codes, and Omaha and Lincoln run their own permit offices. Electrical permits come from the city where it has its own program, or from the State Electrical Division.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Nebraska weather swings from hot, stormy summers with hail and strong wind to cold, blowing-snow winters. Ask your installer about anchoring for wind on exposed facades.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Nebraska Storefronts',
		'lead' => 'Lit letters for Omaha and Lincoln shopping centers and small-town main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Omaha and Lincoln shopping centers.',
			'hidden-back-halo-lit' => 'A clean halo look for offices and clinics.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Nebraska.',
			'18oz-blockout-banner' => 'Banners built for prairie wind.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Nebraska?',
			'a' => 'We ship custom channel letters anywhere in Nebraska from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Nebraska electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'Does Nebraska have a sign electrician license?',
			'a' => 'Sign installation is one of the special classes covered by Nebraska&rsquo;s special electrician license. Licensed electrical contractors can also do the work.',
		),
		array(
			'q' => 'Where do I get a sign permit in Nebraska?',
			'a' => 'From your city or county. Omaha and Lincoln run their own permit offices, and electrical permits come from the city or the State Electrical Division.',
		),
		array(
			'q' => 'How long does shipping to Nebraska take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The special electrician definition is from statute text in bill copies; confirm on the Division&rsquo;s descriptions page and Neb. Rev. Stat. 81-2102.',
		'Omaha&rsquo;s sign code (Chapter 55, city limits plus a 3-mile jurisdiction, per a secondary source) is not shown; confirm and add.',
		'Transit days to Nebraska not shown until verified.',
	),
);
