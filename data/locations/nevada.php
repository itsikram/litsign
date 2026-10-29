<?php
/**
 * Nevada state guide (/locations/nevada/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$lv_1914 = 'https://files.lasvegasnevada.gov/planning/Title-19-Sign-Standards.pdf';
$lv_temp = 'https://files.lasvegasnevada.gov/planning/Temporary-Sign-Permit.pdf';

return array(
	'slug' => 'nevada',
	'name' => 'Nevada',
	'abbr' => 'NV',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Nevada',
	'neighbors' => array('california', 'oregon', 'idaho', 'utah', 'arizona'),
	'seo_title' => 'Channel Letters Shipped to Nevada | C-6 Sign License Guide',
	'seo_description' => 'Channel letters shipped to Nevada: NSCB C-6b electrical sign licenses, Las Vegas sign certificates and temporary sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Nevada',
	'intro' => array(
		'Nevada businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Nevada sign contractor. Banners and window graphics are printed in Renton, WA.',
		'Nevada licenses sign work as its own contracting trade. The Nevada State Contractors Board issues the C-6 classification for erecting signs, and its C-6b subclassification covers fabricating, installing and erecting electrical signs, including the wiring they need.',
		'Which permit office you use in greater Las Vegas depends on your address. Inside the City of Las Vegas, an on-premise sign needs a sign certificate and a city- and state-licensed contractor; much of the commercial strip is in unincorporated Clark County, with its own permit portal.',
	),
	'facts' => array(
		'Licensing agency' => 'Nevada State Contractors Board (NSCB)',
		'Electrical signs' => 'C-6b subclassification (includes sign wiring)',
		'City of Las Vegas' => 'Sign certificate; contractor licensed by city and state',
		'Las Vegas temporary signs' => 'Temporary Sign Permit ($100), filed online',
	),
	'categories' => array(
		'channel-letters' => 'In the City of Las Vegas, a wall sign may not rise more than 12 inches above the top of the wall, marquee or parapet, and the district standards generally keep lit signs off walls facing single-family homes within 200 feet.',
		'banners' => 'Las Vegas handles banners, balloons, pennants and other temporary displays through a Temporary Sign Permit, filed online with a letter giving the dates, types and quantities.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Nevada',
		'body' => array(
			'Under NAC 624.240, a <strong>C-6</strong> license holder may fabricate, install and erect signs and perform the wiring necessary for electrical signs. The classification has subclassifications: <strong>C-6a</strong> outdoor advertising (excluding electrical lighting for billboards), <strong>C-6b</strong> electrical signs, <strong>C-6c</strong> sheet metal work incidental to signs, and <strong>C-6d</strong> painted signs.',
			'An electrical contractor (classification C-2) can also connect a sign. Local permit offices add their own requirements on top of the state license, such as the City of Las Vegas rule that signs needing a construction permit be installed by a contractor licensed by both the city and the state. Check the license and its classification on the NSCB license search before you hire.',
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
			'Nevada cities and counties issue sign permits under their own codes. In greater Las Vegas, the City of Las Vegas, Henderson and North Las Vegas each have their own offices, and unincorporated areas go to Clark County.',
			'In the <strong>City of Las Vegas</strong>, the sign standards in Chapter 19.14 of the zoning code require the lot owner or an authorized agent to secure a <strong>sign certificate</strong> before any on-premise sign that needs one is built, placed or modified, and no one may install such a sign without it. Signs that need a construction permit must be installed by a contractor licensed by the City of Las Vegas and the State of Nevada. Wall signs may not extend more than 12 inches above the top of the wall, marquee or parapet, and the district standards generally don&rsquo;t allow lit signs, internal or external, on a building elevation that faces single-family residential property within 200 feet.',
			'In <strong>Clark County</strong>, contractors pulling building permits need an active Nevada State Contractor&rsquo;s License and an active Multi-Jurisdictional Business License number, with matching business names, and permits are submitted and paid through the county&rsquo;s Citizen Access Portal.',
		),
		'table' => array(
			'Sign permit' => 'Your city, or the county outside city limits',
			'City of Las Vegas' => 'Sign certificate before building or changing the sign',
			'Las Vegas installer' => 'Licensed by the City of Las Vegas and the State',
			'Clark County contractors' => 'Nevada contractor&rsquo;s license + Multi-Jurisdictional Business License',
		),
		'sources' => array(
			array('City of Las Vegas: Zoning Code Chapter 19.14, Sign Standards (PDF)', $lv_1914),
			array('Clark County Building &amp; Fire Prevention: Licensing requirements', 'https://www.clarkcountynv.gov/government/departments/building___fire_prevention/permit_issuance/licensing_requirements.php'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs and Windows in Las Vegas',
		'body' => array(
			'The City of Las Vegas handles banners, balloons, inflatables, searchlights, pennants, streamers and similar devices through a <strong>Temporary Sign Permit</strong>, filed online only. The application is signed, notarized and acknowledged by the property owner, or by a lessee with the owner&rsquo;s written consent, and includes a letter giving the proposed dates, types and quantities, a scaled site plan and sign elevations or photos. The fee is $100, and display periods follow the limits in the zoning code.',
			'Some of Chapter 19.14&rsquo;s district standards cap window signs at <strong>25 percent of the windows on a building elevation</strong> and count them, together with wall signs, against the wall-sign allowance, so ask Planning which table applies to your zoning before ordering window graphics.',
		),
		'sources' => array(
			array('City of Las Vegas: Temporary Sign Permit submittal requirements (PDF)', $lv_temp),
			array('City of Las Vegas: Zoning Code Chapter 19.14, Sign Standards (PDF)', $lv_1914),
		),
	),
	'climate' => array(
		'Nevada&rsquo;s desert sun and summer heat are hard on paint and plastics, nights cool off sharply, and spring winds and summer monsoon storms can kick up dust. Choose colors that hold up to intense UV, and ask your installer about anchoring for wind on exposed facades.',
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
			'a' => 'We ship custom channel letters anywhere in Nevada, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an NSCB-licensed sign contractor (C-6) or electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign installer need in Nevada?',
			'a' => 'A Nevada State Contractors Board license in classification C-6. The C-6b subclassification covers electrical signs, including the wiring necessary for them, and an electrical contractor (C-2) can also connect a sign. Cities may add their own license requirements. Check the classification on the NSCB license search.',
		),
		array(
			'q' => 'What does the City of Las Vegas require before a sign goes up?',
			'a' => 'A sign certificate, secured by the lot owner or an authorized agent before the sign is built, placed or modified. Signs needing a construction permit must be installed by a contractor licensed by both the city and the State of Nevada.',
		),
		array(
			'q' => 'Do I need a permit for a grand opening banner in Las Vegas?',
			'a' => 'Yes, inside city limits: banners, balloons, pennants and similar displays go through the city&rsquo;s online Temporary Sign Permit, with the owner&rsquo;s notarized application, a letter giving dates and quantities, a site plan and elevations or photos. The fee is $100.',
		),
		array(
			'q' => 'How long does shipping to Nevada take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'NSCB was revising subclassification rules in 2024; confirm NAC 624.240 is unchanged.',
		'City of Las Vegas Chapter 19.14 read from the city-hosted PDF 2026-10-27; the compilation shows ordinance notes up to 2008, so confirm it is the current text. The 25 percent window cap appears in some district tables only.',
		'Las Vegas Temporary Sign Permit requirements (revised 01/06/21) read 2026-10-27; the $100 fee: re-check yearly.',
		'Transit days to Nevada not shown until verified.',
	),
);
