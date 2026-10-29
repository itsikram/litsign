<?php
/**
 * Virginia state guide (/locations/virginia/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$rva_ohd = 'https://www.rva.gov/sites/default/files/2022-04/Old_Historic_District_Guidelines.pdf';

return array(
	'slug' => 'virginia',
	'name' => 'Virginia',
	'abbr' => 'VA',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Virginia',
	'neighbors' => array('maryland', 'west-virginia', 'kentucky', 'tennessee', 'north-carolina'),
	'seo_title' => 'Channel Letters Shipped to Virginia | DPOR License Guide',
	'seo_description' => 'Channel letters shipped to Virginia: DPOR contractor classes, the master electrician behind an ELE license, and Richmond historic rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Virginia',
	'intro' => array(
		'Virginia businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a DPOR-licensed Virginia contractor. Banners and window graphics are printed in Renton, WA.',
		'Virginia&rsquo;s Department of Professional and Occupational Regulation (DPOR) licenses both the company and the people. The Board for Contractors sorts companies into Class A, B or C by contract size, and a company with the Electrical (ELE) specialty must have a master electrician, licensed through the board&rsquo;s tradesman program, as its qualifier.',
		'Sign permits are local. Virginia Beach routes them through its zoning administrator, and in Richmond&rsquo;s Old and Historic Districts a new sign also needs a Certificate of Appropriateness, with internally lit signs discouraged.',
	),
	'facts' => array(
		'Licensing agency' => 'DPOR Board for Contractors',
		'Company license' => 'Class A, B or C with the Electrical (ELE) specialty',
		'ELE qualifier' => 'Master electrician tradesman license',
		'Richmond historic districts' => 'Certificate of Appropriateness for new signs',
	),
	'categories' => array(
		'channel-letters' => 'Richmond&rsquo;s Old and Historic District guidelines say signs should not be internally illuminated, so letters there are usually unlit or lit from outside; ask Commission staff before ordering.',
		'window-graphics' => 'In Richmond&rsquo;s historic districts, permanent window signs should use no more than 20 percent of the glass, and temporary ones up to 30 days no more than 15 percent.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Virginia',
		'body' => array(
			'The <strong>Board for Contractors</strong> licenses companies by class, and the class caps the work a company may take on. Under Va. Code &sect; 54.1-1100, a <strong>Class C</strong> contractor handles single jobs over $1,000 and under $30,000, a <strong>Class B</strong> contractor jobs from $30,000 to under $150,000, and a <strong>Class A</strong> contractor jobs of $150,000 or more, each with an annual total limit as well.',
			'The specialty decides the kind of work. Under 18VAC50-22-61, an <strong>Electrical (ELE)</strong> contractor&rsquo;s designated employee or qualifier must hold a <strong>master electrician tradesman license</strong> issued by the board; there is no separate sign specialty for lit signs. Look up both the company and its master electrician on DPOR&rsquo;s License Lookup before work starts.',
		),
		'table' => array(
			'Licensing agency' => 'Virginia DPOR, Board for Contractors',
			'Class C' => 'Single jobs over $1,000 and under $30,000',
			'Class B' => 'Single jobs from $30,000 to under $150,000',
			'Class A' => 'Single jobs of $150,000 or more',
			'ELE specialty' => 'Qualified by a licensed master electrician',
		),
		'sources' => array(
			array('Va. Code &sect; 54.1-1100: Contractor classes', 'https://law.lis.virginia.gov/vacode/title54.1/chapter11/section54.1-1100/'),
			array('18VAC50-22-61: Specialty services (Electrical)', 'https://law.lis.virginia.gov/admincode/title18/agency50/chapter22/section61/'),
			array('Virginia DPOR: Board for Contractors', 'https://www.dpor.virginia.gov/Boards/Contractors'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Virginia',
		'body' => array(
			'Virginia&rsquo;s independent cities and its counties each adopt their own sign ordinances, while the structure and wiring are checked against the Virginia Uniform Statewide Building Code (USBC).',
			'In <strong>Virginia Beach</strong>, most new or replacement signs need a permit approved by the zoning administrator. You may need to submit plans and diagrams so the sign can be checked against the city&rsquo;s sign ordinance and the USBC. Applications are filed online, fees are set in section 8-31 of the City Code, and the city says decisions are generally made within 30 days of a complete application.',
			'In <strong>Richmond</strong>, sign permits are reviewed by Zoning Administration. If the storefront is in one of the city&rsquo;s Old and Historic Districts, City Code 30-930.6 also requires a Certificate of Appropriateness from the Commission of Architectural Review before new signs go up; staff can approve one administratively when the sign meets the Commission&rsquo;s signage standards.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Building code' => 'Virginia Uniform Statewide Building Code',
			'Virginia Beach' => 'Zoning administrator; decisions generally within 30 days',
			'Richmond' => 'Zoning Administration, plus CAR review in Old and Historic Districts',
		),
		'sources' => array(
			array('City of Virginia Beach: Sign permits', 'https://planning.virginiabeach.gov/permits/sign'),
			array('City of Richmond: Zoning Administration', 'https://www.rva.gov/planning-development-review/zoning-administration'),
		),
	),
	'districts' => array(
		array(
			'name' => 'Richmond Old and Historic Districts',
			'rule' => 'The Commission of Architectural Review&rsquo;s sign standards say signs should not be internally illuminated or use flashing or moving lights, LED and plasma display signs won&rsquo;t be approved, and projecting signs should hang at least 8 feet above the sidewalk and reach no more than 4 feet out. Roof signs are prohibited citywide.',
			'source' => 'https://www.rva.gov/sites/default/files/2020-08/Signage.pdf',
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Richmond&rsquo;s Historic Districts',
		'body' => array(
			'Richmond&rsquo;s design guidelines for Old and Historic Districts limit <strong>permanent window signs to 20 percent of the total glass</strong>, and <strong>temporary window signs, up for 30 days or less, to 15 percent</strong>. Banners there are reviewed for shape, materials and colors too. Outside the historic districts, window graphics follow the regular zoning rules for your address.',
		),
		'sources' => array(
			array('City of Richmond: Old and Historic Districts Handbook and Design Review Guidelines (PDF)', $rva_ohd),
		),
	),
	'climate' => array(
		'Hampton Roads storefronts face salt spray off the Chesapeake Bay and the Atlantic, plus hurricane and nor&rsquo;easter winds, while the Shenandoah Valley and Northern Virginia see more snow and ice. On the coast, ask your installer for stainless or coated mounting hardware.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Virginia Storefronts',
		'lead' => 'Lit letters for shopping centers from Northern Virginia to Hampton Roads.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for suburban shopping centers.',
			'hidden-back-halo-lit' => 'A polished halo look for offices and clinics in Virginia.',
			'feather-angled-flag-pole' => 'Flags for roadside visibility in Virginia.',
			'adhesive-window-perf' => 'Window graphics that keep the view out in Virginia.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Virginia?',
			'a' => 'We ship custom channel letters anywhere in Virginia, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a DPOR-licensed electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign electrician need in Virginia?',
			'a' => 'The company needs a Board for Contractors license (Class A, B or C, depending on the job size) with the Electrical (ELE) specialty, and that specialty must be qualified by an employee holding a master electrician tradesman license from the board.',
		),
		array(
			'q' => 'Can I put lit channel letters in a Richmond historic district?',
			'a' => 'Ask first. Richmond&rsquo;s Old and Historic District standards say signs should not be internally illuminated, and LED or plasma display signs won&rsquo;t be approved. Unlit or externally lit letters fit the guidelines, and every new sign there needs a Certificate of Appropriateness.',
		),
		array(
			'q' => 'How long does a Virginia Beach sign permit take?',
			'a' => 'The city says decisions are generally made within 30 days of a complete application. The zoning administrator may ask for plans and diagrams to check the sign against the sign ordinance and the Uniform Statewide Building Code. Fees are set in section 8-31 of the City Code, and applications are filed online.',
		),
		array(
			'q' => 'How long does shipping to Virginia take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Virginia Beach sign page read directly 2026-10-27; its 30-day decision time: re-check yearly.',
		'Richmond CAR sign standards from the Signage guidelines (adopted July 26, 2011) and the Old and Historic Districts Handbook pp. 73&ndash;75; whether halo-lit (reverse lit) letters count as internally illuminated was not confirmed with CAR staff.',
		'Contractor class dollar limits (Va. Code &sect; 54.1-1100): re-check yearly.',
		'Transit days to Virginia not shown until verified.',
	),
);
