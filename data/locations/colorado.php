<?php
/**
 * Colorado state guide (/locations/colorado/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'colorado',
	'name' => 'Colorado',
	'abbr' => 'CO',
	'status' => 'review',
	'neighbors' => array('wyoming', 'nebraska', 'kansas', 'oklahoma', 'new-mexico', 'utah'),
	'seo_title' => 'Storefront Signs Shipped to Colorado | Electrical Board Guide',
	'seo_description' => 'Channel letters shipped to Colorado, plus who can wire them (State Electrical Board licensees and registered contractors) and how Denver sign permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Colorado',
	'intro' => array(
		'Colorado businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Colorado electrician.',
		'Colorado&rsquo;s State Electrical Board, in the Department of Regulatory Agencies, licenses master electricians, journeyman electricians and residential wiremen, and electrical contractors register with the board as businesses.',
		'In Denver, most signs need a zoning permit, most also need a building permit with structural plans stamped by a Colorado-registered structural engineer, and a sign with electrical parts needs an electrical permit too.',
	),
	'facts' => array(
		'Licensing agency' => 'Colorado State Electrical Board (DORA)',
		'Individual licenses' => 'Master, Journeyman, Residential Wireman',
		'Business' => 'Electrical contractor registration',
		'Denver sign permits' => 'Zoning + building + electrical permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Colorado',
		'body' => array(
			'The <strong>State Electrical Board</strong> licenses electricians at the <strong>residential wireman</strong>, <strong>journeyman</strong> and <strong>master</strong> levels and registers <strong>electrical contractors</strong>. Licenses are renewed every three years, and apprentices register with the board.',
			'Hire a registered electrical contractor to connect your sign, and check both the contractor and the electrician on DORA&rsquo;s license verification before work starts.',
		),
		'table' => array(
			'Licensing agency' => 'Colorado State Electrical Board, Division of Professions and Occupations',
			'Individuals' => 'Master, Journeyman, Residential Wireman',
			'Business' => 'Registered electrical contractor',
			'Renewal' => 'Every three years',
		),
		'sources' => array(
			array('Colorado State Electrical Board: Applications and forms', 'https://dpo.colorado.gov/Electrical/Applications'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Colorado',
		'body' => array(
			'Colorado cities and counties issue sign permits under their own codes, and many mountain towns have strict design standards for size and lighting.',
			'In <strong>Denver</strong>, Community Planning and Development requires a zoning permit for most signs on private property, and a building permit for most signs unless the Denver Building and Fire Code exempts them, with structural plans prepared, stamped and signed by a Colorado-registered structural engineer. A sign with electrical components or connections also needs an electrical permit. Signs reaching into the public right of way need a DOTI permit, and signs on landmarks or in historic districts go to Landmark Preservation. Apply online through e-permits.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Denver' => 'Zoning permit and, for most signs, a building permit',
			'Denver engineering' => 'Plans stamped by a Colorado-registered structural engineer',
			'Denver lit signs' => 'Electrical permit as well',
		),
		'sources' => array(
			array('City and County of Denver: Apply for a sign permit', 'https://www.denvergov.org/Government/Agencies-Departments-Offices/Agencies-Departments-Offices-Directory/Community-Planning-and-Development/Plan-Review-Permits-and-Inspections/Zoning-Permits/Signs/Apply-for-Sign-Permit'),
			array('City and County of Denver: Signs', 'https://denvergov.org/Government/Agencies-Departments-Offices/Agencies-Departments-Offices-Directory/Community-Planning-and-Development/Plan-Review-Permits-and-Inspections/Zoning-Permits/Signs'),
		),
	),
	'climate' => array(
		'Colorado&rsquo;s high-altitude sun is intense, and the Front Range sees hail, heavy snow and strong chinook winds. Pick colors that hold up to UV, and ask your installer about anchoring for wind and snow load.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Colorado Storefronts',
		'lead' => 'Lit letters for Front Range shopping centers and mountain-town main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Front Range shopping centers.',
			'hidden-back-halo-lit' => 'A refined halo look for Denver offices and boutiques.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for mountain-town foot traffic.',
			'18oz-blockout-banner' => 'Heavy banners for wind and weather in Colorado.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Colorado?',
			'a' => 'We ship custom channel letters anywhere in Colorado from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a registered Colorado electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Colorado?',
			'a' => 'A State Electrical Board license (master, journeyman or residential wireman, as the work requires), working for a registered electrical contractor.',
		),
		array(
			'q' => 'Does Denver require an engineer for sign permits?',
			'a' => 'For signs that need a building permit, structural plans must be stamped and signed by a Colorado-registered structural engineer.',
		),
		array(
			'q' => 'How long does shipping to Colorado take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'License types and renewal cycle are consistent across sources but were not read on a DORA page beyond the applications page; confirm.',
		'Confirm whether some Colorado cities (e.g. Denver) license electrical contractors separately.',
		'Transit days to Colorado not shown until verified.',
	),
);
