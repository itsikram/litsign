<?php
/**
 * Mississippi state guide (/locations/mississippi/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$msboc_faq = 'https://www.msboc.us/general-info/frequently-asked-questions/';
$tup_app = 'https://tupeloms.gov/wp-content/uploads/2025/03/SIGN-APPLICATION-FINAL-RD.pdf';

return array(
	'slug' => 'mississippi',
	'name' => 'Mississippi',
	'abbr' => 'MS',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Mississippi',
	'neighbors' => array('tennessee', 'alabama', 'louisiana', 'arkansas'),
	'seo_title' => 'Channel Letters Shipped to Mississippi | MSBOC Sign Rules',
	'seo_description' => 'Channel letters shipped to Mississippi: the $50,000 MSBOC license rule, the sign erection specialty and Jackson and Tupelo sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Mississippi',
	'intro' => array(
		'Mississippi businesses can order custom LED channel letters online, built in Washington and California and shipped ready for a Mississippi installer and electrician. Banners and window graphics are printed in Renton, WA.',
		'Mississippi licenses contracting businesses rather than individual tradespeople. The State Board of Contractors (MSBOC) requires a commercial license for every contractor and subcontractor on a commercial job over $50,000, and it has a <strong>Sign Erection</strong> specialty, with its own exam, that covers the electrical hookup of neon and digital signs.',
		'Cities handle the rest. Jackson&rsquo;s Building Permit Office requires a permit for electrical work and for signs and enforces the 2017 National Electrical Code, while Tupelo limits banners to 21 days at a time, three times a year.',
	),
	'facts' => array(
		'Contractor licensing' => 'Mississippi State Board of Contractors (MSBOC)',
		'Commercial license' => 'Every contractor and subcontractor on jobs over $50,000',
		'Sign classification' => 'Sign Erection specialty (test required)',
		'Jackson electrical code' => '2017 National Electrical Code',
	),
	'categories' => array(
		'channel-letters' => 'Tupelo voids a sign permit if the sign isn&rsquo;t installed within six months of issuance, so line up production and your installer before you apply.',
		'banners' => 'Tupelo limits banners, streamers and inflatables to a 21-day period, three times a year, and its permit application asks for the date of your last banner.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Mississippi',
		'body' => array(
			'The Mississippi State Board of Contractors licenses contracting businesses through a qualifying party. Its rule for commercial work is direct: all contractors and subcontractors performing work on commercial jobs over $50,000, including equipment installation, need an MSBOC commercial license (Miss. Code &sect; 31-3-1). The lower $5,000 and $10,000 figures in the same statute apply only to fire protection systems, not to electrical or sign work.',
			'The board&rsquo;s classifications include a <strong>Sign Erection</strong> specialty (test required) whose scope covers installing business signs and &ldquo;the related electrical hook-up to install neon and digital signs such as located at banks and stores,&rdquo; along with maintenance, repair and painting. Electrical Work is a separate major classification, and anyone working outside the major classifications must qualify as a specialty contractor. On a job above the threshold, the sign company should hold Sign Erection or a classification that covers the work.',
			'MSBOC licenses the business, so the local side matters too. In <strong>Jackson</strong>, the Building Permit Office issues construction, demolition and sign permits, and a permit is required whenever an owner or the owner&rsquo;s authorized agent, usually a contractor, proposes electrical work or a sign; the city enforces the 2017 National Electrical Code without amendments. Other Mississippi cities set their own electrician and permit requirements, so ask the building department where your business is located.',
		),
		'table' => array(
			'State agency' => 'Mississippi State Board of Contractors',
			'Commercial license' => 'Jobs over $50,000, contractors and subcontractors alike',
			'Sign Erection specialty' => 'Signs plus the electrical hookup of neon and digital signs',
			'Jackson permits' => 'Building Permit Office; 2017 NEC',
		),
		'sources' => array(
			array('MSBOC: Frequently asked questions', $msboc_faq),
			array('MSBOC: Classifications', 'https://www.msboc.us/classifications/'),
			array('MSBOC: Commercial laws and rules, 2022 (PDF)', 'https://www.msboc.us/wp-content/uploads/2022/02/COMMERCIAL-LAWS-AND-RULES-REVISED-2022.pdf'),
			array('City of Jackson: Building permits', 'https://jacksonms.gov/government/city-departments/planning-and-development/office-of-code-services/building-permits/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Jackson and Tupelo',
		'body' => array(
			'Mississippi cities issue sign permits under their own ordinances. In <strong>Jackson</strong>, the capital and largest city, anyone placing a sign on or off their premises needs a sign permit from the Signs and License Division of the Department of Planning and Development, on the first floor of the Warren Hood Building at 200 South President Street, and every sign must meet height and setback requirements. The zoning ordinance, last amended in December 2024, sends sign details to a separate City of Jackson Sign Ordinance.',
			'In <strong>Tupelo</strong>, every sign falls under Chapter 10 and Section 12.13 of the Development Code and needs a permit unless Chapter 10 exempts it. Development Services asks for the recorded deed, or the lease and the owner&rsquo;s permission, a site plan with a scaled elevation of the sign, an issued certificate of occupancy or building permit, and contact details for both the sign contractor and the electrical contractor. Contracts over $10,000 also need a Material Purchase Certificate, and a permit is void if the sign isn&rsquo;t installed within six months.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Jackson' => 'Signs and License Division, Planning and Development',
			'Tupelo' => 'Development Services, Development Code Chapter 10',
			'Tupelo attachments' => 'Deed or lease, scaled elevation, CO or building permit',
			'Tupelo permit life' => 'Void if the sign isn&rsquo;t installed within six months',
		),
		'sources' => array(
			array('City of Jackson: Signs and licenses', 'https://jacksonms.gov/business/jxn-resource-hub/business-licensing/signs-and-licenses/'),
			array('City of Jackson: Zoning Ordinance, amended December 2024 (PDF)', 'https://jacksonms.gov/wp-content/uploads/2026/06/COJ-Zoning-Ordinance-Amended-December-2024.pdf'),
			array('City of Tupelo: Sign permit application (PDF)', $tup_app),
			array('City of Tupelo: Sign application, Development Services (PDF)', 'https://tupeloms.gov/wp-content/uploads/2023/12/47.-SIGNS_PLN_Application.2023.pdf'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Inflatables in Tupelo',
		'body' => array(
			'Tupelo groups banners, streamers and inflatables as temporary signs and limits them to a 21-day period, three times a year. The application asks for the banner&rsquo;s square footage, when it will be displayed and the date of your last banner, so the city can track how many periods you have used.',
			'Three 21-day periods add up to about nine weeks of banner time a year, so save them for a grand opening, a seasonal sale and one more event, and let permanent window graphics and lit letters carry the message the rest of the year.',
		),
		'sources' => array(
			array('City of Tupelo: Sign permit application (PDF)', $tup_app),
		),
	),
	'climate' => array(
		'Mississippi heat and humidity stretch from spring well into fall, storm systems in spring and late fall spawn tornadoes from the Delta to the Pine Belt, and the Gulf Coast takes hurricanes and salt-laden air. Near the coast, specify stainless or coated hardware and ask your installer to anchor letters for hurricane gusts; inland, focus on sealing every penetration against driving rain.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Mississippi Storefronts',
		'lead' => 'Lit letters for Jackson-area and Tupelo shopping centers, courthouse squares and the Coast.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Face-lit letters for metro Jackson and Tupelo retail centers.',
			'standard-channel-letter-back-lit' => 'A soft glow for courthouse-square storefronts.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-55 and US 45.',
			'13oz-vinyl-banner' => 'Banners for Tupelo&rsquo;s 21-day temporary sign periods.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Mississippi?',
			'a' => 'We ship custom channel letters anywhere in Mississippi, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a locally licensed electrician and, on commercial jobs over $50,000, an MSBOC-licensed contractor to mount and connect them.',
		),
		array(
			'q' => 'When does a Mississippi sign job need an MSBOC license?',
			'a' => 'When the commercial job is over $50,000. MSBOC requires a commercial license for all contractors and subcontractors on commercial jobs over that amount, including equipment installation. The $5,000 and $10,000 thresholds in the statute apply only to fire protection systems, so they don&rsquo;t lower the limit for electrical or sign work.',
		),
		array(
			'q' => 'Is there a sign contractor license in Mississippi?',
			'a' => 'Yes, as a board classification. MSBOC&rsquo;s Sign Erection specialty requires a test, and its scope covers installing business signs plus the related electrical hookup of neon and digital signs, along with maintenance, repair and painting. It matters on commercial jobs over $50,000; below that, your city&rsquo;s license and permit rules apply.',
		),
		array(
			'q' => 'Who issues sign and electrical permits in Jackson?',
			'a' => 'The Building Permit Office in the Department of Planning and Development. A permit is required whenever an owner or the owner&rsquo;s authorized agent, usually a contractor, proposes electrical work or a sign, and sign permits are processed by the Signs and License Division. Jackson enforces the 2017 National Electrical Code without amendments.',
		),
		array(
			'q' => 'How long can a banner stay up in Tupelo?',
			'a' => 'Up to 21 days at a time, three times a year. Tupelo treats banners, streamers and inflatables as temporary signs, and its permit application asks for the banner&rsquo;s square footage, the display dates and the date of your last banner. Permanent signs need their own permit, which is void if the sign isn&rsquo;t installed within six months.',
		),
		array(
			'q' => 'How long does shipping to Mississippi take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'MSBOC FAQ, classifications and the 2022 Commercial Laws and Rules PDF read directly 2026-11-20; Miss. Code 31-3-1 shows the $5,000 / $10,000 figures are for fire protection systems only (closes the old threshold note).',
		'Jackson&rsquo;s homeowner/journeyman FAQ now returns 404, so the facts only it carried (city journeyman licenses; journeymen can&rsquo;t pull permits) were removed; the live Building Permits page (read directly 2026-11-20) is used instead. Jackson&rsquo;s current electrician license requirements were not found online.',
		'Jackson Sign Ordinance text (window, banner and permit-life rules) not found online, and Municode blocks the research PC. Not shown.',
		'Tupelo sign permit application (2025) and 2023 planning form read directly; the 2023 fee table ($10 up to $10,000; banners $20 plus $0.30 per sq ft) was replaced by &ldquo;determined by project cost&rdquo; and is not shown.',
		'Gulf Coast (Biloxi, Gulfport) sign rules and wind-load requirements not verified; Biloxi&rsquo;s posted sign section is an unadopted draft.',
		'Transit days to Mississippi not shown until verified.',
	),
);
