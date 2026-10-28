<?php
/**
 * Florida state guide (/locations/florida/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$jax_signs = 'https://www.jacksonville.gov/departments/public-works/building-inspection-division/sign-laws';
$orlando_permit = 'https://www.orlando.gov/Building-Development/Permits-Inspections/Other/Get-a-Permit-for-Your-Business/Apply-for-a-Sign-Permit';
$orlando_minor = 'https://www.orlando.gov/Our-Government/Departments-Offices/Economic-Development/Code-Enforcement/Minor-Signs';
$md_signs = 'https://www.miamidade.gov/building/standards/signs-permits.asp';

return array(
	'slug' => 'florida',
	'name' => 'Florida',
	'abbr' => 'FL',
	'status' => 'live',
	'updated' => '2026-10-22',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Florida',
	'neighbors' => array('georgia', 'alabama'),
	'seo_title' => 'Channel Letters Shipped to Florida | Sign License & Permits',
	'seo_description' => 'Channel letters shipped to Florida: who installs them (Sign Specialty contractors), Florida Building Code permits and Miami-Dade rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Florida',
	'intro' => array(
		'Florida businesses can design and price custom LED channel letters online, then have them made in Washington and California and shipped ready for a licensed Florida sign contractor to install. Banners, window graphics and flags come from our Renton, WA shop on the same delivery terms.',
		'Two things set Florida apart. The state licenses a <strong>Sign Specialty</strong> electrical contractor specifically for electric signs, and every sign permit is reviewed against the Florida Building Code, including its wind design rules, which are strictest in the High-Velocity Hurricane Zone of Miami-Dade and Broward counties.',
		'Because wind and structural review can add time, start the permit conversation with your installer before you order, and leave room in your opening schedule.',
	),
	'facts' => array(
		'Licensing board' => 'Electrical Contractors&rsquo; Licensing Board (DBPR)',
		'Sign license' => 'Sign Specialty electrical contractor (ES)',
		'Building code' => 'Florida Building Code, including wind design',
		'Sign permit' => 'Your city or county building department',
	),
	'categories' => array(
		'channel-letters' => 'Lit signs get two reviews in most Florida cities. Orlando, for one, wants sealed drawings for wind-exposed signs and a testing-lab listing number for every electrical sign.',
		'banners' => 'Banner rules differ sharply: Jacksonville permits banners up to 100 square feet for 30 days, and unincorporated Miami-Dade allows them only to announce a grand opening.',
		'window-graphics' => 'Orlando caps window signs at 25 percent of the window area. Jacksonville needs no permit for window advertising but counts it toward your allowed sign area.',
		'flags-aframes' => 'Portable displays such as A-frames must stand behind the sidewalk and off the public right-of-way in Orlando, and come down within 180 days.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Florida',
		'body' => array(
			'Florida&rsquo;s Electrical Contractors&rsquo; Licensing Board, part of the Department of Business and Professional Regulation (DBPR), certifies specialty electrical contractors, including the <strong>Sign Specialty</strong> category. Under Florida Administrative Code rule 61G6-7.001, the sign specialty covers fabricating, installing, repairing and wiring electrical signs and outline lighting, up to the last disconnect or terminal points. A certified or registered electrical contractor can also do the work.',
			'Cities add their own paperwork. Orlando asks sign contractors for a Florida license, a City of Orlando business tax receipt and workers&rsquo; compensation coverage, and Jacksonville says a licensed sign contractor is required in most cases. Check your installer&rsquo;s license on DBPR&rsquo;s license search before you hire them.',
		),
		'table' => array(
			'Licensing board' => 'Electrical Contractors&rsquo; Licensing Board, DBPR',
			'Sign license' => 'Sign Specialty electrical contractor (rule 61G6-7.001)',
			'Also allowed' => 'Certified or registered electrical contractors',
			'Check a license' => 'DBPR license search (myfloridalicense.com)',
		),
		'sources' => array(
			array("Florida DBPR: Electrical Contractors' Licensing Board", 'https://www2.myfloridalicense.com/electrical-contractors/'),
			array('Florida Administrative Code 61G6-7.001: Specialty electrical contractors', 'https://www.flrules.org/gateway/ruleNo.asp?id=61G6-7.001'),
			array('City of Orlando: Apply for a sign permit (contractor requirements)', $orlando_permit),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Florida',
		'body' => array(
			'Sign permits are issued by your city, or by your county for unincorporated areas, and sign drawings must comply with the Florida Building Code. Expect the review to look at the sign&rsquo;s structure and attachment as well as its electrical details. Three of the state&rsquo;s busiest permit offices show how the details vary:',
			'<strong>Jacksonville</strong>&rsquo;s Building Inspection Division reviews and issues sign permits for wall, ground, awning, projecting and roof signs, with licensed contractors filing electronically through JAXEPICS. Every illuminated sign also needs an electrical permit, and most applicants file a $5,000 sign bond; the City waives it only when the property owner applies for a non-illuminated sign of 32 square feet or less and no taller than 8 feet. Sign rules sit in Ordinance Code Chapter 326 and Zoning Code Part 13.',
			'<strong>Orlando</strong> takes sign applications as building permits, submitted digitally. Signs must meet the Florida Building Code&rsquo;s minimum wind speed, with drawings for wind-exposed signs signed and sealed by a Florida-registered architect or engineer, and every electrical sign must be listed by an authorized testing laboratory, with the manufacturer and listing number on the application plus a separate electrical permit. In multi-tenant centers, the owner or manager writes a letter allocating the square footage for your sign.',
			'In <strong>unincorporated Miami-Dade County</strong>, a sign needs a Zoning Improvement Permit and possibly a building permit, and every sign must meet building and electrical standards. Cities inside the county, such as Miami, Miami Beach or Doral, run their own sign permits.',
		),
		'table' => array(
			'Sign permit' => 'City or county building department',
			'Code' => 'Florida Building Code (structure, wind, electrical)',
			'Jacksonville' => 'Building Inspection Division; $5,000 sign bond; electrical permit for lit signs',
			'Orlando' => 'Building permit + electrical permit; lab-listed electrical signs; sealed drawings for wind',
			'Miami-Dade (unincorporated)' => 'Zoning Improvement Permit, plus a building permit where required',
		),
		'sources' => array(
			array('City of Jacksonville: Sign laws and sign permits', $jax_signs),
			array('City of Orlando: Apply for a sign permit', $orlando_permit),
			array('Miami-Dade County: Signs and sign permits', $md_signs),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Temporary Displays in Florida',
		'body' => array(
			'Florida leaves banners to local codes, so check before you hang one. <strong>Jacksonville</strong> permits banners of up to 100 square feet for up to 30 days, securely attached to a building, canopy, pole or other structure. <strong>Orlando</strong> treats banners, sidewalk A-frames and window displays as minor signs: they must sit behind the sidewalk and outside the public right-of-way, come down within 180 days, and on a commercial lot are typically limited to two, each up to 12 square feet and 6 feet tall (32 square feet and 8 feet in some zones). <strong>Unincorporated Miami-Dade</strong> prohibits banners except temporary grand opening announcements.',
		),
		'sources' => array(
			array('City of Jacksonville: Sign laws (banners)', $jax_signs),
			array('City of Orlando: Minor signs (Sec. 64.323)', $orlando_minor),
			array('Miami-Dade County: Signs (Sec. 33-95)', $md_signs),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Florida Cities',
		'body' => array(
			'In <strong>Orlando</strong>, window signs don&rsquo;t count toward a lot&rsquo;s minor-sign limit, but they may cover no more than 25 percent of the window area. <strong>Jacksonville</strong> doesn&rsquo;t require a permit for window advertising, yet anything on the glass that could be read as advertising counts toward the sign area your business is allowed, so plan window graphics and wall letters together.',
		),
		'sources' => array(
			array('City of Orlando: Minor signs', $orlando_minor),
			array('City of Jacksonville: Sign laws', $jax_signs),
		),
	),
	'climate' => array(
		'Hurricane season runs June through November, so wind is the first thing your installer and permit reviewer will think about: plan anchoring for your wall type and the local wind requirements, and take banners down ahead of a storm. Salt air near the coast calls for corrosion-resistant fasteners, and strong sun favors colors that hold up to UV.',
	),
	'climate_sources' => array(
		array('NOAA National Hurricane Center: Atlantic hurricane season', 'https://www.nhc.noaa.gov/climo/'),
	),
	'popular' => array(
		'heading' => 'Popular Signs for Florida Storefronts',
		'lead' => 'Bright lit letters plus window graphics that handle the sun.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright face lit letters for shopping plazas.',
			'standard-channel-letter-front-back-lit' => 'Face and halo glow for restaurants and bars.',
			'adhesive-window-perf' => 'Window perf that adds privacy and cuts glare.',
			'feather-angled-flag-pole' => 'Flags for busy commercial roads, where local rules allow them.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a storefront sign company near me in Florida?',
			'a' => 'We ship custom storefront signs anywhere in Florida: channel letters from Washington and California, other products from Renton, WA. We don&rsquo;t install, so you&rsquo;ll hire a licensed Florida sign specialty or electrical contractor near you to permit and install it.',
		),
		array(
			'q' => 'What license does a sign installer need in Florida?',
			'a' => 'A Sign Specialty electrical contractor certification from the Electrical Contractors&rsquo; Licensing Board covers electrical signs, or the work can be done by a certified or registered electrical contractor. Some cities, such as Orlando, also ask for a local business tax receipt and workers&rsquo; compensation coverage.',
		),
		array(
			'q' => 'Do hurricane rules affect my sign in Florida?',
			'a' => 'Yes. Sign permits are reviewed under the Florida Building Code, which includes wind design requirements, strictest in Miami-Dade and Broward&rsquo;s High-Velocity Hurricane Zone. Orlando, for example, requires sealed drawings for wind-exposed signs. Your installer accounts for this in the permit drawings and anchoring.',
		),
		array(
			'q' => 'Can I hang a grand opening banner in Florida?',
			'a' => 'It depends on the city. Jacksonville permits banners of up to 100 square feet for up to 30 days, Orlando lets temporary banners stay up to 180 days behind the sidewalk, and unincorporated Miami-Dade allows banners only to announce a grand opening. Ask your city before you print.',
		),
		array(
			'q' => 'How long does shipping to Florida take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production, so you can book your installer and permit around it.',
		),
	),
	'review_notes' => array(
		'Jacksonville $5,000 bond and Orlando minor-sign sizes: re-check yearly.',
		'Transit days to Florida not shown until verified.',
	),
);
