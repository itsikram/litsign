<?php
/**
 * Missouri state guide (/locations/missouri/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$kc_lic = 'https://www.kcmo.gov/city-hall/departments/city-planning-development/contractor-licensing';
$kc_exempt = 'https://www.kcmo.gov/city-hall/departments/city-planning-development/building-permit-exempt-work';
$jc_sign = 'https://www.jeffersoncitymo.gov/government/building_regulations/advertising_and_signage.php';

return array(
	'slug' => 'missouri',
	'name' => 'Missouri',
	'abbr' => 'MO',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Missouri',
	'neighbors' => array('iowa', 'illinois', 'kentucky', 'tennessee', 'arkansas', 'oklahoma', 'kansas', 'nebraska'),
	'seo_title' => 'Channel Letters Shipped to Missouri | Kansas City Sign Rules',
	'seo_description' => 'Channel letters shipped to Missouri: how the optional statewide license works, Kansas City sign contractors and Jefferson City limits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Missouri',
	'intro' => array(
		'Missouri businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed local installer. Banners and window graphics are printed in Renton, WA.',
		'Missouri&rsquo;s statewide electrical contractor license is optional, but it carries weight: under &sect; 324.925 RSMo, every city and county must accept it in place of its own contractor license, without a local exam. Cities still license contractors who don&rsquo;t hold it, and they still issue permits and inspect.',
		'Kansas City requires sign contractors to have a Sign Supervisor Certificate of Qualification, and Jefferson City counts window advertising toward a business&rsquo;s sign allowance and prohibits banners outright.',
	),
	'facts' => array(
		'State license' => 'Optional statewide electrical contractor license',
		'Local acceptance' => 'Every city and county must accept it (&sect; 324.925 RSMo)',
		'Kansas City sign contractors' => 'Sign Supervisor Certificate of Qualification',
		'Jefferson City' => 'Banners, advertising flags and inflatables prohibited',
	),
	'categories' => array(
		'channel-letters' => 'Kansas City exempts copy changes on painted or printed signs from permits, but not on electric signs, so changing lit letters or faces there needs a permit.',
		'window-graphics' => 'Kansas City needs no permit for lettering applied directly to windows or doors that names the business or its products, while Jefferson City counts window advertising toward the sign-area limit.',
		'banners' => 'Jefferson City&rsquo;s sign rules prohibit portable and temporary signs, banners included, as well as advertising flags and inflatables.',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Missouri',
		'body' => array(
			'The Office of Statewide Electrical Contractors has accepted applications for the optional statewide license since July 1, 2019. The license covers electrical contracting regardless of voltage, and under &sect; 324.925.1 RSMo every political subdivision must recognize it <strong>in lieu of a local license</strong> for contracting and permits; a city or county can&rsquo;t make a statewide license holder pass a local exam, and one that doesn&rsquo;t comply can have local sales tax withheld. Cities may still license contractors who don&rsquo;t hold the statewide license, require a business license, issue permits, inspect and adopt an NEC-based code.',
			'<strong>Kansas City</strong> licenses contractors under Article XII of its Building and Rehabilitation Code in three steps: a business license from the Finance Department, a Certificate of Qualification for the company&rsquo;s qualified individual through CompassKC, and then the contractor license itself. A sign contractor needs a <strong>Sign Supervisor</strong> certificate, and electrical work is qualified by Electrical Supervisor, Master or Master (Limited) Electrician certificates.',
		),
		'table' => array(
			'State license' => 'Optional; Office of Statewide Electrical Contractors',
			'Local acceptance' => 'Required statewide in lieu of a local license',
			'Kansas City sign contractor' => 'Sign Supervisor Certificate of Qualification',
			'Kansas City electrical' => 'Electrical Supervisor, Master or Master (Limited) Electrician',
		),
		'sources' => array(
			array('Missouri Office of Statewide Electrical Contractors', 'https://pr.mo.gov/electricalcontractors.asp'),
			array('Missouri OSEC: FAQ for political subdivisions (PDF)', 'https://pr.mo.gov/boards/electrical/FAQ%27s-for-Political-Subdivisions.pdf'),
			array('City of Kansas City, MO: Contractor licensing', $kc_lic),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Missouri',
		'body' => array(
			'Missouri cities and counties issue sign permits under their own codes, whichever electrical license your installer holds.',
			'In <strong>Kansas City</strong>, permits are filed through CompassKC. The city&rsquo;s list of permit-exempt work leaves out signs inside a building and copy changes on painted or printed signs, but electric signs aren&rsquo;t included in that exemption, except theater marquees. Lettering painted or applied directly on a wall, window or door that names the occupant or its products doesn&rsquo;t need a sign permit.',
			'In <strong>Jefferson City</strong>, the capital, a permit is required before any new sign goes up, under Chapter 3 of the City Code. Building signs may cover up to 10 percent of the front facade in the C-1 and C-2 districts, 7.5 percent in C-O and 5 percent in M-1 and M-2, measuring the facade as its length times its height up to 20 feet; side and rear walls get 5 percent. Wall signs can&rsquo;t sit more than 32 feet above grade, and flashing signs and roof signs outside the building&rsquo;s silhouette are prohibited.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Kansas City' => 'Permits through CompassKC; electric signs aren&rsquo;t permit-exempt',
			'Jefferson City wall signs' => '10% of the front facade (C-1/C-2), 7.5% (C-O), 5% (M-1/M-2)',
			'Jefferson City height' => 'No wall sign more than 32 ft above grade',
		),
		'sources' => array(
			array('City of Kansas City, MO: Building permit exempt work', $kc_exempt),
			array('City of Jefferson, MO: Advertising and signage', $jc_sign),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Promotions in Jefferson City',
		'body' => array(
			'Jefferson City&rsquo;s sign rules are stricter than most about promotions. They prohibit <strong>portable or temporary signs, including banners</strong>, along with advertising flags, inflatables, and any sign on the right-of-way or a sidewalk. Window advertising isn&rsquo;t a way around the limit either: it counts toward the building&rsquo;s total sign area. For a Jefferson City opening, put the budget into permanent letters sized within the facade allowance.',
		),
		'sources' => array(
			array('City of Jefferson, MO: Advertising and signage', $jc_sign),
		),
	),
	'climate' => array(
		'Missouri storefronts get a little of everything: humid summers, spring hail and tornado-season winds, and winter ice storms that can coat letters and raceways. Ask your installer to anchor for gusts and seal wall penetrations so ice melt doesn&rsquo;t get behind the letters.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Missouri Storefronts',
		'lead' => 'Lit letters for Kansas City and St. Louis area shopping centers and main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for suburban shopping centers in Missouri.',
			'standard-channel-letter-back-lit' => 'A soft glow for historic brick storefronts.',
			'feather-angled-flag-pole' => 'Flags for busy roads, where your city allows them.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners where your city allows them.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Missouri?',
			'a' => 'We ship custom channel letters anywhere in Missouri, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an electrical or sign contractor licensed for your city or county to mount and connect them.',
		),
		array(
			'q' => 'Is a Missouri statewide electrical license good everywhere?',
			'a' => 'Yes. The license is optional, but &sect; 324.925 RSMo makes every city and county accept it in place of a local contractor license, without a local exam. Local permits, inspections and business licenses still apply, and cities can still license contractors who don&rsquo;t hold it.',
		),
		array(
			'q' => 'What license does a Kansas City sign contractor need?',
			'a' => 'A Kansas City contractor license backed by a Sign Supervisor Certificate of Qualification, after a city business license. Electrical work is qualified by an Electrical Supervisor, Master or Master (Limited) Electrician certificate. Certificates and permits are handled through the city&rsquo;s CompassKC portal.',
		),
		array(
			'q' => 'Can I hang a banner in Jefferson City?',
			'a' => 'No. Jefferson City prohibits portable and temporary signs, banners included, plus advertising flags and inflatables. Window advertising counts toward your total sign area, so plan permanent letters within the facade percentage for your district. Signs on the right-of-way or a sidewalk are prohibited too.',
		),
		array(
			'q' => 'How long does shipping to Missouri take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Corrected: the old page said the statewide license was recognized only in participating jurisdictions; &sect; 324.925.1 RSMo requires every city and county to recognize it. OSEC FAQ ("As of July 1, 2019") read from the Internet Archive copy of 2025-05-26.',
		'Kansas City pages read in the browser pane 2026-10-27. The $1M sign-contractor insurance and the Class I/II electrical rule for sign wiring came only from search snippets of Municode Article XII and are left off.',
		'Jefferson City signage page read from the Internet Archive copy of 2026-01-07 (the site returns 403 to the research PC); open the link once from another network.',
		'Transit days to Missouri not shown until verified.',
	),
);
