<?php
/**
 * California state guide (/locations/california/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ladbs_manual = 'https://ladbs.org/docs/default-source/publications/information-bulletins/building-code/sign-manual-(excluding-off-site-signs).pdf?sfvrsn=5';

return array(
	'slug' => 'california',
	'name' => 'California',
	'abbr' => 'CA',
	'status' => 'review',
	'neighbors' => array('oregon', 'nevada', 'arizona'),
	'seo_title' => 'Storefront Signs Shipped to California | C-45 & Permit Guide',
	'seo_description' => 'Channel letters shipped to California businesses, with who can install them (CSLB C-45 and C-10 contractors), how city sign permits work, and LA rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to California',
	'intro' => array(
		'California businesses can design and price custom LED channel letters online, then have them built in Renton, Washington and shipped to their storefront, ready for a licensed California installer.',
		'California is the one state where the license for sign work is easy to name: the Contractors State License Board has a dedicated C-45 Sign Contractor classification that covers building, installing and wiring electric signs. What varies is everything local. Each city or county runs its own sign code and permit counter, and in Los Angeles an illuminated sign permit is only issued to a licensed sign or electrical contractor.',
		'Before you order, collect three things: your landlord&rsquo;s sign criteria, your city&rsquo;s size and lighting limits, and a quote from a C-45 or C-10 contractor to install. Then size your sign to fit.',
	),
	'facts' => array(
		'Licensing board' => 'Contractors State License Board (CSLB)',
		'Sign license' => 'C-45 Sign Contractor, or C-10 Electrical',
		'Sign permit' => 'Your city or county building department',
		'In Los Angeles' => 'LADBS issues lit-sign permits only to C-10 or C-45 contractors',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in California',
		'body' => array(
			'California licenses construction contractors through the Contractors State License Board (CSLB). Its <strong>C-45 Sign Contractor</strong> classification covers fabricating, installing and erecting electrical signs, including their wiring, as well as non-electrical signs such as signs attached to buildings. A <strong>C-10 Electrical Contractor</strong> can also do the electrical work.',
			'Hire a contractor whose license is active for the classification you need, and look it up on CSLB&rsquo;s license check before you sign a contract.',
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
			'<strong>Los Angeles</strong> is a good example of how detailed local rules can be. According to the LADBS sign manual, permits for illuminated signs are only issued to a licensed C-10 electrical contractor or C-45 electrical sign contractor, and a wall sign under 100 square feet doesn&rsquo;t need plans from a licensed engineer or architect unless it attaches to an unreinforced masonry building. Specific plan areas, historic preservation overlay zones and sign districts can each change what&rsquo;s allowed.',
			'California&rsquo;s Energy Code (Title 24, Part 6) also includes requirements for sign lighting controls. Ask your electrician which controls, such as a time switch or photocontrol, your sign needs.',
		),
		'table' => array(
			'Sign permit' => 'City or county building / planning department',
			'Los Angeles' => 'LADBS; lit-sign permits to C-10 or C-45 contractors only',
			'Energy code' => 'Title 24, Part 6 sign lighting controls',
		),
		'sources' => array(
			array('LADBS: Sign manual (excluding off-site signs)', $ladbs_manual),
			array('California Energy Commission: Building Energy Efficiency Standards (Title 24, Part 6)', 'https://www.energy.ca.gov/programs-and-topics/programs/building-energy-efficiency-standards'),
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
			'a' => 'You can order online and have it shipped to your storefront anywhere in California. We manufacture in Renton, WA and don&rsquo;t install, so you&rsquo;ll hire a licensed C-45 sign contractor or C-10 electrical contractor near you to install it.',
		),
		array(
			'q' => 'What license does a sign installer need in California?',
			'a' => 'A CSLB C-45 Sign Contractor license covers fabricating, installing and wiring electric signs. A C-10 Electrical Contractor can also make the electrical connection. Check the license on CSLB&rsquo;s website.',
		),
		array(
			'q' => 'Who can pull a lit sign permit in Los Angeles?',
			'a' => 'According to the LADBS sign manual, illuminated sign permits are only issued to a licensed C-10 electrical contractor or C-45 electrical sign contractor.',
		),
		array(
			'q' => 'How long does shipping to California take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, and 3-day, 2-day and overnight shipping are available. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'LADBS sign manual cited is the 2017 bulletin; confirm it is still current.',
		'Title 24 sign lighting controls: kept general on purpose; confirm wording with an electrician if you want specifics.',
		'Transit days to California not shown until verified.',
	),
);
