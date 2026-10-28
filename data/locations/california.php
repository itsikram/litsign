<?php
/**
 * California state guide (/locations/california/). See washington.php for
 * the rules of these files.
 *
 * LADBS (dbs.lacity.gov) blocks the research PC; its sign bulletin was read
 * from an Internet Archive copy (2023-09-27) of the same file.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$la_bulletin = 'https://dbs.lacity.gov/sites/default/files/efs/forms/pc17/sign-manual-%28excluding-off-site-signs%29.pdf';
$sd_bulletin = 'https://www.sandiego.gov/development-services/forms-publications/information-bulletins/111';
$cec_signs = 'https://www.energy.ca.gov/sites/default/files/2025-07/07_SignLighting_ada.pdf';

return array(
	'slug' => 'california',
	'name' => 'California',
	'abbr' => 'CA',
	'status' => 'live',
	'updated' => '2026-10-22',
	'wikipedia' => 'https://en.wikipedia.org/wiki/California',
	'neighbors' => array('oregon', 'nevada', 'arizona'),
	'seo_title' => 'Channel Letters Shipped to California | C-45 & Permit Guide',
	'seo_description' => 'Channel letters shipped to California: who can install them (CSLB C-45 and C-10 contractors), how city sign permits work and LA rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to California',
	'intro' => array(
		'California businesses can design and price custom LED channel letters online, have them built in Washington and California, and get them shipped to the storefront, ready for a licensed California installer. Our banners, window graphics and flags are printed in Renton, WA and travel the same way.',
		'California is the one state where the license for sign work is easy to name: the Contractors State License Board has a dedicated C-45 Sign Contractor classification that covers building, installing and wiring electric signs. What varies is everything local. Each city or county runs its own sign code and permit counter, and the rules for banners and window graphics differ as much as the rules for lit letters.',
		'Before you order, collect three things: your landlord&rsquo;s sign criteria, your city&rsquo;s size and lighting limits, and a quote from a C-45 or C-10 contractor to install. Then size your sign to fit.',
	),
	'facts' => array(
		'Licensing board' => 'Contractors State License Board (CSLB)',
		'Sign license' => 'C-45 Sign Contractor, or C-10 Electrical',
		'Sign permit' => 'Your city or county building department',
		'Sign lighting' => 'Energy Code &sect;130.3 controls on outdoor signs',
	),
	'categories' => array(
		'channel-letters' => 'California&rsquo;s Energy Code requires controls on outdoor sign lighting: a photocontrol plus a time switch, or an astronomical time switch, and dimming of at least 65 percent at night for a sign lit day and night.',
		'banners' => 'Banner rules are local. Los Angeles limits temporary signs to 30 days at a time and 90 days a year, while San Diego exempts temporary onsite banners from its sign permit.',
		'window-graphics' => 'Window limits are local too: Los Angeles caps window signs at 10 percent of the window area, and San Diego lists window signs among its permit exemptions.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in California',
		'body' => array(
			'California licenses construction contractors through the Contractors State License Board (CSLB). Its <strong>C-45 Sign Contractor</strong> classification covers fabricating, installing and erecting electrical signs, including their wiring, as well as non-electrical signs such as signs attached to buildings. A <strong>C-10 Electrical Contractor</strong> can also do the electrical work.',
			'Hire a contractor whose license is active for the classification you need, and look it up on CSLB&rsquo;s license check before you sign a contract. In Los Angeles the license also decides who can pull the permit: the city issues illuminated sign permits only to C-10 or C-45 contractors.',
		),
		'table' => array(
			'Licensing agency' => 'Contractors State License Board (CSLB)',
			'Sign classification' => 'C-45 Sign Contractor (electrical and non-electrical signs, including wiring)',
			'Electrical classification' => 'C-10 Electrical Contractor',
			'Check a license' => 'CSLB online license check',
		),
		'sources' => array(
			array('CSLB: C-45 Sign Contractor classification', 'https://www.cslb.ca.gov/About_Us/Library/Licensing_Classifications/Licensing_Classifications_Detail.aspx?Class=C45'),
			array('CSLB: Check a license', 'https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/CheckLicense.aspx'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in California',
		'body' => array(
			'There is no statewide sign permit for a storefront sign. Your city, or your county for unincorporated areas, reviews the sign under its zoning and building codes and issues the permit; specific plans, historic districts and sign districts can add their own limits. Shopping centers usually add landlord sign criteria on top.',
			'<strong>Los Angeles</strong> sets out its on-site sign rules in LADBS Information Bulletin P/BC 2017-078 (Municipal Code Article 4.4 and Building Code Chapter 62). Permits for illuminated signs go only to a licensed C-10 electrical contractor or C-45 electrical sign contractor. Engineered plans aren&rsquo;t needed for a wall sign under 100 square feet unless it attaches to an unreinforced masonry building, and zoning, specific plans and Planning entitlements can each change what is allowed, so check your parcel on ZIMAS first.',
			'<strong>San Diego</strong> requires a separate sign permit to install or alter a sign, filed through the City&rsquo;s online permitting account under Information Bulletin 111 (updated May 2026). Wall signs over 70 square feet or 600 pounds need structural calculations from a registered design professional. Copy changes without structural or electrical work, window signs and temporary onsite banners are exempt from the sign permit.',
			'Statewide, the Energy Code (Title 24, Part 6, &sect;130.3) applies to the lighting itself. Outdoor sign lighting needs a photocontrol plus an automatic time switch, or an astronomical time switch, and a sign that is on day and night needs a dimmer able to cut its power by at least 65 percent at night. Tell your installer how long the sign will be lit so the right control is wired in.',
		),
		'table' => array(
			'Sign permit' => 'City or county building / planning department',
			'Los Angeles' => 'LADBS; lit-sign permits to C-10 or C-45 contractors only',
			'San Diego' => 'Development Services; online sign permit (IB 111)',
			'Energy code' => 'Title 24, Part 6, &sect;130.3 sign lighting controls',
		),
		'sources' => array(
			array('LADBS Information Bulletin P/BC 2017-078: Signs (excluding off-site signs)', $la_bulletin),
			array('City of San Diego Information Bulletin 111: How to obtain a permit for signs', $sd_bulletin),
			array('California Energy Commission: 2025 Nonresidential Compliance Manual, ch. 7 Sign Lighting', $cec_signs),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Grand Opening Banners in California',
		'body' => array(
			'California has no statewide banner rule, and its two largest cities show the range. In <strong>Los Angeles</strong>, a temporary sign such as a banner may stay up 30 days, must then come down for 30 days, and may be displayed no more than 90 days in a calendar year; all temporary signs together are limited to 2 square feet per foot of street frontage, and cloth signs must be hung with stranded cable at least 1/16 inch thick. <strong>San Diego</strong> exempts temporary onsite banners, streamers and pennants from its sign permit.',
		),
		'sources' => array(
			array('LADBS Information Bulletin P/BC 2017-078: temporary signs', $la_bulletin),
			array('City of San Diego Information Bulletin 111: exemptions', $sd_bulletin),
		),
	),
	'climate' => array(
		'Coastal California&rsquo;s salt air calls for corrosion-resistant fasteners, and inland and desert sun is hard on finishes over time; choose painted returns and colors with the full day&rsquo;s sun in mind. LED letters stay cool and efficient in the heat compared with neon.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for California Storefronts',
		'lead' => 'Lit letters for the building and graphics that work in strong sun.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'The most common lit storefront sign in shopping centers.',
			'hidden-back-halo-lit' => 'Halo letters for boutiques, salons and restaurants.',
			'standard-channel-letter-front-back-lit' => 'Face and halo glow for a bold night look.',
			'adhesive-window-perf' => 'See-through window graphics that cut glare.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Can I get a channel letter sign near me in California?',
			'a' => 'You can order online and have it shipped to your storefront anywhere in California. We build channel letters in Washington and California and don&rsquo;t install, so you&rsquo;ll hire a licensed C-45 sign contractor or C-10 electrical contractor near you to install it.',
		),
		array(
			'q' => 'What license does a sign installer need in California?',
			'a' => 'A CSLB C-45 Sign Contractor license covers fabricating, installing and wiring electric signs, and a C-10 Electrical Contractor can also make the electrical connection. Check that the license is active for that classification on CSLB&rsquo;s website before you sign a contract.',
		),
		array(
			'q' => 'Who can pull a lit sign permit in Los Angeles?',
			'a' => 'LADBS issues illuminated sign permits only to a licensed C-10 electrical contractor or C-45 electrical sign contractor. A wall sign under 100 square feet doesn&rsquo;t need engineered plans unless it attaches to an unreinforced masonry building, but zoning and specific plans can still limit it.',
		),
		array(
			'q' => 'Does a lit sign in California need special controls?',
			'a' => 'Yes. Under the Energy Code (&sect;130.3), outdoor sign lighting needs a photocontrol plus an automatic time switch, or an astronomical time switch. A sign lit both day and night also needs a dimmer that can cut its power by at least 65 percent at night.',
		),
		array(
			'q' => 'How long does shipping to California take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, and 3-day, 2-day and overnight shipping are available. Checkout shows the estimated date for each, including production, so you can line up your installer and permit.',
		),
	),
	'review_notes' => array(
		'dbs.lacity.gov blocks the research PC; LA Bulletin P/BC 2017-078 (effective 2017-01-01, still the file LADBS hosts) was read from an Internet Archive copy. Open the link once before the next upload.',
		'San Diego IB 111 (May 2026) structural threshold: re-check yearly.',
		'Transit days to California not shown until verified.',
	),
);
