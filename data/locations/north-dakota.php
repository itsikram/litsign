<?php
/**
 * North Dakota state guide (/locations/north-dakota/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$nd_4309 = 'https://ndlegis.gov/cencode/t43c09.pdf';
$nd_cert = 'https://ndlegis.gov/api/acdata/pdf/24.1-05-01.pdf';
$far_code = 'https://download.fargond.gov/0/fargo_sign_code_2012.pdf';
$far_temp = 'https://fargond.gov/city-government/departments/inspections/commercial-building-permits-fees/sign-regulations/temporary-signs';

return array(
	'slug' => 'north-dakota',
	'name' => 'North Dakota',
	'abbr' => 'ND',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/North_Dakota',
	'neighbors' => array('minnesota', 'south-dakota', 'montana'),
	'seo_title' => 'Channel Letters Shipped to North Dakota | Fargo Sign Rules',
	'seo_description' => 'Channel letters shipped to North Dakota: master electrician contracting, the $500 wiring certificate, and Fargo and Bismarck sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to North Dakota',
	'intro' => array(
		'North Dakota businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed North Dakota electrician. Banners and window graphics are printed in Renton, WA.',
		'North Dakota keeps electrical licensing simple and statewide. The State Electrical Board licenses four classes, master, journeyman, class B and power limited, and only a master or power limited electrician may contract for an installation. There is no separate sign license, so a licensed electrician makes the connection.',
		'The paperwork starts before the work does: any job worth $500 or more needs an electrical wiring certificate, with a startup copy sent to the board and the power company before work begins. Fargo and Bismarck then add their own sign installer rules.',
	),
	'facts' => array(
		'Licensing agency' => 'North Dakota State Electrical Board',
		'License classes' => 'Master, journeyman, class B, power limited',
		'Wiring certificate' => 'Jobs of $500 or more, filed before work starts',
		'Fargo window signs' => 'Exempt from the sign code',
	),
	'categories' => array(
		'channel-letters' => 'Fargo writes permits for building-mounted signs to a licensed sign hanger unless the owner does the work, and lit letters need an electrical permit as well.',
		'window-graphics' => 'Fargo exempts window signs, including the glass in doors, from its sign article entirely: no permit and no size limit.',
		'banners' => 'In Fargo a cloth banner needs no permit but can stay up only 14 days before a 60-day break, and it has to lie flat against the facade.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in North Dakota',
		'body' => array(
			'Under N.D.C.C. 43-09-09, no one may plan, lay out, install, alter or repair wiring for light, heat or power without a license from the State Electrical Board. Section 43-09-10 names four classes: master, journeyman, class B and power limited. A journeyman needs 8,000 hours of experience gained over at least three years, a master needs a year as a licensed journeyman, and class B electricians work on farmsteads and homes.',
			'Only a master or power limited electrician may contract for an electrical installation, class B contracting is limited to farmsteads and one- and two-family homes in small cities, and contractors carry liability insurance of at least $500,000 (43-09-20). The exemptions in 43-09-16 cover utilities, telecommunications and plugging appliances into existing receptacles, but nothing for signs, so the circuit and the connection for lit letters are licensed electrical work.',
			'Every job of $500 or more needs an <strong>electrical wiring certificate</strong> submitted by the supervising master, class B or power limited electrician, and a startup copy goes to the board and to the power company before work begins and before the installation is energized (N.D. Admin. Code 24.1-05-01). State inspection fees start at $50 for jobs up to $500.',
		),
		'table' => array(
			'Licensing agency' => 'North Dakota State Electrical Board',
			'Journeyman' => '8,000 hours over at least three years',
			'Who may contract' => 'Master or power limited electrician',
			'Contractor insurance' => 'At least $500,000 liability',
			'Wiring certificate' => 'Jobs of $500 and up; startup copy before work',
		),
		'sources' => array(
			array('N.D.C.C. chapter 43-09: Electricians (PDF)', $nd_4309),
			array('N.D. Admin. Code 24.1-05-01: Wiring certificates and inspection fees (PDF)', $nd_cert),
		),
	),
	'permits' => array(
		'heading' => 'Electrical Inspection and Sign Permits in North Dakota',
		'body' => array(
			'The State Electrical Board has jurisdiction over, and inspects, all electrical installations in North Dakota, and a city may run its own inspection program inside its limits with an inspector registered with the board (43-09-22). Either way, your electrician&rsquo;s wiring certificate is the paper trail for the sign circuit.',
			'In <strong>Fargo</strong>, the largest city, sign permits are filed on the city&rsquo;s permit portal, and the permanent sign fee is $50 or 50 cents per square foot of sign face, whichever is greater. Permits for signs hung on a building are written to a licensed sign hanger unless the property owner does the work, applications include a site plan, an elevation drawing and the sign contractor&rsquo;s business license number, and every electrical sign needs an electrical permit too. All signs on a property together can&rsquo;t exceed 30 percent of the building&rsquo;s front face or 2.5 square feet per foot of lot frontage.',
			'Fargo also bars lit signs from throwing glare onto nearby residential areas between 8 p.m. and 8 a.m., and it doesn&rsquo;t allow exposed bulbs over 40 watts on a sign&rsquo;s exterior.',
			'In <strong>Bismarck</strong>, the capital, no one may erect signs or get a sign permit without a sign installer license from the building official. The license requires liability insurance of $250,000 per person and $500,000 per occurrence, naming the city, and it expires every December 31.',
		),
		'table' => array(
			'Inspection' => 'State Electrical Board, or a city with its own program',
			'Fargo installer' => 'Licensed sign hanger, unless the owner does the work',
			'Fargo fee' => '$50 or $0.50 per sq ft of face, whichever is greater',
			'Fargo total sign area' => '30% of the front face or 2.5 sq ft per foot of frontage',
			'Bismarck installer' => 'City sign installer license, expires each Dec. 31',
		),
		'sources' => array(
			array('City of Fargo: Sign regulations', 'https://fargond.gov/city-government/departments/inspections/commercial-building-permits-fees/sign-regulations'),
			array('City of Fargo: Permanent signs', 'https://fargond.gov/city-government/departments/inspections/commercial-building-permits-fees/sign-regulations/permanent-signs'),
			array('City of Fargo: Sign code, Land Development Code Article 20-13 (PDF)', $far_code),
			array('City of Bismarck: Sign installers license application', 'https://www.bismarcknd.gov/DocumentCenter/View/41797/New-Logo-Sign-Installers-License-Application'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Portable Displays in Fargo and Bismarck',
		'body' => array(
			'Fargo doesn&rsquo;t require a permit for temporary cloth signs or banners on private property. They can stay up for 14 days and then can&rsquo;t go back up for 60 days, no temporary sign may exceed 100 square feet, and a banner on a building must lie flat against the facade, fixed so it doesn&rsquo;t flap in the wind. Portable displays work differently: each placement takes a $15 permit and lasts up to 30 consecutive days, and a business can use no more than 98 days a year.',
			'Bismarck is more relaxed about duration. Temporary banners need no permit, must be on the business&rsquo;s own premises and fastened to a wall or other solid surface, and can be displayed up to 10 months in a calendar year; downtown and in residential districts they&rsquo;re capped at 32 square feet. A-frame sidewalk displays are allowed with restrictions, and portable ones may not be lit or powered.',
		),
		'sources' => array(
			array('City of Fargo: Temporary signs', $far_temp),
			array('City of Fargo: Portable signs', 'https://fargond.gov/city-government/departments/inspections/commercial-building-permits-fees/sign-regulations/portable-signs'),
			array('City of Bismarck: Temporary signs information guide', 'https://www.bismarcknd.gov/DocumentCenter/View/47319/Signs-Temporary'),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Fargo',
		'body' => array(
			'Fargo lists window signs, including signs on the glass of a door, among the signs exempt from every part of its sign article, including permits and limits on size, location and number, and the city&rsquo;s temporary sign page confirms that signs in windows don&rsquo;t need sign permits. That makes window graphics the one storefront message in Fargo with no permit, no clock and no square-foot cap.',
		),
		'sources' => array(
			array('City of Fargo: Sign code &sect; 20-1306, exempt signs (PDF)', $far_code),
			array('City of Fargo: Temporary signs', $far_temp),
		),
	),
	'climate' => array(
		'North Dakota winters are among the coldest in the lower 48, with long subzero spells, brutal wind chills and ground blizzards that can close the interstates. Spring brings Red River flooding around Fargo, and summers turn hot with severe thunderstorms. Choose LED power supplies rated for deep cold, and have your installer caulk each mounting hole and conduit entry so meltwater can&rsquo;t work its way into the wall.',
	),
	'popular' => array(
		'heading' => 'Popular Picks for North Dakota Storefronts',
		'lead' => 'Lit letters for Fargo, Bismarck and Grand Forks retail, plus window graphics that need no Fargo permit.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for long, dark winter evenings.',
			'standard-channel-letter-back-lit' => 'A halo glow for downtown Fargo and Bismarck brick.',
			'adhesive-window-perf' => 'Window graphics, exempt from Fargo&rsquo;s sign permits.',
			'18oz-blockout-banner' => 'Flat-mounted banners for 14-day promotions.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in North Dakota?',
			'a' => 'We ship custom channel letters anywhere in North Dakota, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed North Dakota electrician and, in Fargo or Bismarck, a city-licensed sign installer to mount and connect them.',
		),
		array(
			'q' => 'Does North Dakota have a sign electrician license?',
			'a' => 'No. The State Electrical Board licenses master, journeyman, class B and power limited electricians, and the statute&rsquo;s exemptions don&rsquo;t include signs. The wiring and connection for lit letters is licensed electrical work, and only a master or power limited electrician may contract for it, with at least $500,000 in liability insurance.',
		),
		array(
			'q' => 'What is a wiring certificate in North Dakota?',
			'a' => 'It&rsquo;s the filing for any electrical job worth $500 or more. The supervising master, class B or power limited electrician submits it, and a startup copy goes to the State Electrical Board and to the power company before work begins and before the installation is energized. State inspection fees start at $50.',
		),
		array(
			'q' => 'Do I need a permit for a banner in Fargo?',
			'a' => 'No, temporary cloth banners on private property don&rsquo;t need permits in Fargo. A banner can stay up for 14 days and then can&rsquo;t go back up for 60 days, no temporary sign may exceed 100 square feet, and it must be fixed flat against the facade so it doesn&rsquo;t flap. Window signs are exempt altogether.',
		),
		array(
			'q' => 'Who can put up a sign in Bismarck?',
			'a' => 'A sign installer licensed by the city&rsquo;s building official; nobody else can get a permit to erect a sign. The license requires liability insurance of $250,000 per person and $500,000 per occurrence, naming the city, and it expires on December 31 each year. Temporary banners don&rsquo;t need a permit.',
		),
		array(
			'q' => 'How long does shipping to North Dakota take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'N.D.C.C. chapter 43-09 and N.D. Admin. Code 24.1-05-01 read directly 2026-11-20; 43-09-10 and 43-09-11 confirm the four classes and the 8,000 hours (closes the 2019 bill-draft note). The old 24.1-03-01 source was only the license fee chapter.',
		'Fargo&rsquo;s sign code PDF is a 2012 compilation and its portable sign limits differ from the city&rsquo;s current portable sign page (the page is used). Fargo fees ($50 or $0.50 per sq ft; $15 per portable placement) and state inspection fees (effective July 1, 2024): re-check yearly.',
		'Fargo sign hanger license details (fee, insurance) not read.',
		'Bismarck sign installer form last revised 11/3/2017 (its $10 license fee is not shown); temporary sign guide revised 9/16/2026. The Downtown Signs guide (A-frame details) not read.',
		'Transit days to North Dakota not shown until verified.',
	),
);
