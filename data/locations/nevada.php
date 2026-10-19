<?php
/**
 * Nevada state guide (/locations/nevada/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'nevada',
	'name' => 'Nevada',
	'abbr' => 'NV',
	'status' => 'review',
	'neighbors' => array('california', 'oregon', 'idaho', 'utah', 'arizona'),
	'seo_title' => 'Storefront Signs Shipped to Nevada | C-6 Sign License Guide',
	'seo_description' => 'Channel letters shipped to Nevada, plus who can install them (NSCB C-6 sign contractors, including C-6b electrical signs) and Las Vegas area permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Nevada',
	'intro' => array(
		'Nevada businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Nevada sign contractor.',
		'Nevada licenses sign work as its own contracting trade. The Nevada State Contractors Board issues the C-6 classification for erecting signs, and its C-6b subclassification covers fabricating, installing and erecting electrical signs, including the wiring they need.',
		'In the Las Vegas area, much of the commercial strip is in unincorporated Clark County, where contractors pulling permits need an active Nevada contractor&rsquo;s license and a business license.',
	),
	'facts' => array(
		'Licensing agency' => 'Nevada State Contractors Board (NSCB)',
		'Sign classification' => 'C-6 Erecting signs',
		'Electrical signs' => 'C-6b subclassification (includes sign wiring)',
		'Las Vegas area permits' => 'City of Las Vegas or Clark County, depending on address',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Nevada',
		'body' => array(
			'Under NAC 624.240, a <strong>C-6</strong> license holder may fabricate, install and erect signs and perform the wiring necessary for electrical signs. The classification has subclassifications: <strong>C-6a</strong> outdoor advertising (excluding electrical lighting for billboards), <strong>C-6b</strong> electrical signs, <strong>C-6c</strong> sheet metal work incidental to signs, and <strong>C-6d</strong> painted signs.',
			'An electrical contractor (classification C-2) can also connect a sign. Check the license and its classification on the NSCB license search before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Nevada State Contractors Board',
			'Sign contractor' => 'C-6 (Erecting signs)',
			'Electrical signs' => 'C-6b, including the wiring for the sign',
			'Rule' => 'NAC 624.240',
		),
		'sources' => array(
			array('Nevada Administrative Code, Chapter 624 (NAC 624.240: Classification C-6)', 'https://www.leg.state.nv.us/NAC/NAC-624.html'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Nevada',
		'body' => array(
			'Nevada cities and counties issue sign permits under their own codes. In greater Las Vegas, which office you use depends on your address: the City of Las Vegas, Henderson and North Las Vegas each have their own, and unincorporated areas go to Clark County.',
			'In <strong>Clark County</strong>, contractors pulling building permits need an active Nevada State Contractor&rsquo;s License and an active Multi-Jurisdictional Business License number, with matching business names, and permits are submitted and paid through the county&rsquo;s Citizen Access Portal.',
		),
		'table' => array(
			'Sign permit' => 'Your city, or the county outside city limits',
			'Clark County contractors' => 'Nevada contractor&rsquo;s license + Multi-Jurisdictional Business License',
			'Clark County filing' => 'Citizen Access Portal',
		),
		'sources' => array(
			array('Clark County Building &amp; Fire Prevention: Licensing requirements', 'https://www.clarkcountynv.gov/government/departments/building___fire_prevention/permit_issuance/licensing_requirements.php'),
		),
	),
	'climate' => array(
		'Nevada&rsquo;s desert sun, heat and wide temperature swings are hard on paint and plastics, and spring winds are strong. Choose colors that hold up to intense UV, and ask your installer about anchoring for wind on exposed facades.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Nevada Storefronts',
		'lead' => 'Bright letters for strip centers, plus flags and banners for busy roads.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright face lit letters for Las Vegas and Reno strip centers.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for maximum nighttime presence in Nevada.',
			'feather-angled-flag-pole' => 'Flags that catch drivers on wide arterials.',
			'13oz-vinyl-banner' => 'Grand opening and now hiring banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Nevada?',
			'a' => 'We ship custom channel letters anywhere in Nevada from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an NSCB-licensed sign contractor (C-6) or electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in Nevada?',
			'a' => 'A Nevada State Contractors Board license in classification C-6. The C-6b subclassification covers electrical signs, including the wiring necessary for them.',
		),
		array(
			'q' => 'Who pulls a sign permit in unincorporated Clark County?',
			'a' => 'A contractor with an active Nevada contractor&rsquo;s license and a Multi-Jurisdictional Business License, through the county&rsquo;s Citizen Access Portal.',
		),
		array(
			'q' => 'How long does shipping to Nevada take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'NSCB was revising subclassification rules in 2024; confirm NAC 624.240 is unchanged.',
		'Add the City of Las Vegas sign permit process once confirmed on the city site.',
		'Transit days to Nevada not shown until verified.',
	),
);
