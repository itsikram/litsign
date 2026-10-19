<?php
/**
 * Delaware state guide (/locations/delaware/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'delaware',
	'name' => 'Delaware',
	'abbr' => 'DE',
	'status' => 'review',
	'neighbors' => array('maryland', 'pennsylvania', 'new-jersey'),
	'seo_title' => 'Storefront Signs Shipped to Delaware | Sign Electrician Guide',
	'seo_description' => 'Channel letters shipped to Delaware businesses, plus the state Master Electrician Special license for electric signs and where local sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Delaware',
	'intro' => array(
		'Delaware businesses can see the price of custom LED channel letters online, have them built in Washington and California, and ship them straight to the storefront for a licensed Delaware installer.',
		'Delaware licenses electricians statewide through the Board of Electrical Examiners in the Division of Professional Regulation. Besides the full Master Electrician license, the board offers a Master Electrician Special license for people who specialize in one field, and Electric Signs is one of the listed specialties.',
		'Delaware is small, but sign rules still change at the town line: Wilmington, Dover, Newark and the three counties each set their own sign codes, so check with the office for your address before you order.',
	),
	'facts' => array(
		'Licensing agency' => 'Delaware Board of Electrical Examiners (Division of Professional Regulation)',
		'Sign specialty' => 'Master Electrician Special: Electric Signs',
		'Other licenses' => 'Master, Limited, Journeyperson, Residential, Apprentice',
		'Sign permit' => 'Your city, town or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Delaware',
		'body' => array(
			'The Board of Electrical Examiners licenses Master, Master Special, Limited, Limited Special, Journeyperson, Residential and Apprentice electricians. The <strong>Master Electrician Special</strong> license is for people who don&rsquo;t qualify as a Master Electrician but have knowledge of a specialty, and <strong>Electric Signs</strong> is one of those specialties, alongside elevators, HVAC, primary distribution systems, pools and refrigeration. Each specialty needs its own application.',
			'Under the Delaware Code, a Master Special applicant needs six years of full-time experience under a licensed master electrician (or master electrician special) in that specialty, or an apprenticeship route of 8,000 supervised hours plus 576 hours of related instruction. A full Master Electrician can also install and connect a sign.',
		),
		'table' => array(
			'Licensing agency' => 'Delaware Board of Electrical Examiners, Division of Professional Regulation',
			'Sign specialist' => 'Master Electrician Special, Electric Signs specialty',
			'General license' => 'Master Electrician',
			'Experience (Master Special)' => '6 years in the specialty, or 8,000 hours plus 576 hours of instruction',
		),
		'sources' => array(
			array('Delaware Division of Professional Regulation: Electrician license types', 'https://dpr.delaware.gov/boards/electrician/newlicense'),
			array('Delaware Code, Title 24, Chapter 14: Electricians', 'https://delcode.delaware.gov/title24/c014/sc02/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Delaware',
		'body' => array(
			'Delaware has no statewide sign permit for business signs. Cities and towns issue permits under their own codes, and county offices cover addresses outside city limits. A lit sign usually needs both the sign approval and an electrical permit pulled by the licensed electrician.',
		),
		'table' => array(
			'Sign permit' => 'Your city or town; the county outside city limits',
			'Electrical work' => 'Licensed Delaware electrician',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Delaware summers are hot and humid, and beach towns get salt air and strong storm winds. On the coast, ask your installer about corrosion-resistant fasteners and anchoring for wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Delaware Storefronts',
		'lead' => 'Lit letters for shopping centers, and outdoor signs for beach season.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright, readable letters for Delaware strip centers.',
			'halo-reverse-acrylic-lit-channel-letters' => 'A halo glow for restaurants and boutiques downtown.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for beach town foot traffic in Delaware.',
			'feather-angled-flag-pole' => 'Flags that catch drivers on busy highways.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Delaware?',
			'a' => 'We ship custom channel letters anywhere in Delaware from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Delaware-licensed electrician to mount and connect it.',
		),
		array(
			'q' => 'Does Delaware have a sign electrician license?',
			'a' => 'Yes. The Board of Electrical Examiners issues a Master Electrician Special license with an Electric Signs specialty. A full Master Electrician can also do the work.',
		),
		array(
			'q' => 'Where do I get a sign permit in Delaware?',
			'a' => 'From your city or town, or from the county if your business is outside city limits. Each sets its own sign code.',
		),
		array(
			'q' => 'How long does shipping to Delaware take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Wilmington: an older city form says City Code Section 41-42 requires every new and existing sign to be registered, with photos; L&I is at 800 N. French Street, (302) 576-3030. Confirm on the city site before adding it.',
		'Transit days to Delaware not shown until verified.',
	),
);
