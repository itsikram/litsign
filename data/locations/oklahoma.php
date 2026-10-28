<?php
/**
 * Oklahoma state guide (/locations/oklahoma/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$okc_check = 'https://www.okc.gov/files/assets/city/v/2/development-services/documents/application-checklists/commercial-signs.pdf';
$okc_row = 'https://www.okc.gov/News-articles/OKC-to-allow-temporary-signs-in-the-City%E2%80%99s-right-of-way-with-a-permit';
$cib_158_40 = 'https://oklahoma.gov/content/dam/ok/en/cib/documents/your-industry/electrical-industry/title_158_chapter_40.pdf';

return array(
	'slug' => 'oklahoma',
	'name' => 'Oklahoma',
	'abbr' => 'OK',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Oklahoma',
	'neighbors' => array('kansas', 'missouri', 'arkansas', 'texas', 'new-mexico', 'colorado'),
	'seo_title' => 'Channel Letters Shipped to Oklahoma | OKC Sign Permits',
	'seo_description' => 'Channel letters shipped to Oklahoma: CIB electrical license categories, OKC sign contractor rules, and temporary sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Oklahoma',
	'intro' => array(
		'Oklahoma businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Oklahoma electrician. Banners and window graphics are printed in Renton, WA.',
		'The Construction Industries Board (CIB) licenses electrical contractors and journeymen statewide, and each license is tied to a category of work. Residential licenses are limited to residential construction, so a storefront sign needs a contractor and journeyman licensed for commercial work.',
		'In Oklahoma City, every sign application is filed by a registered sign contractor with a site plan drawn by a state-licensed engineer or surveyor, and temporary signs placed in the city right-of-way need a monthly permit and sticker.',
	),
	'facts' => array(
		'Licensing agency' => 'Oklahoma Construction Industries Board (CIB)',
		'Licenses' => 'Electrical contractor and journeyman, by category of work',
		'OKC sign applications' => 'Filed by a registered sign contractor',
		'OKC right-of-way signs' => '$25 permit; up to 30 days, three times a year',
	),
	'categories' => array(
		'channel-letters' => 'An Oklahoma City wall sign application includes elevations of the building face with overall dimensions, and structural details showing the connection to the wall where they apply.',
		'flags-aframes' => 'Oklahoma City lets temporary signs go in many city rights-of-way with a $25 permit and a monthly sticker, up to 30 days three times a year; on private property no permit is needed.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Oklahoma',
		'body' => array(
			'Oklahoma&rsquo;s Electrical License Act and CIB rules (OAC 158:40) license <strong>electrical contractors</strong> and <strong>journeyman electricians</strong> by category, the classification that limits what kind of electrical work a license covers. A journeyman installs, repairs and maintains electrical facilities only in the category the license names, and <strong>residential contractor and residential journeyman licenses are limited to residential electrical construction</strong>, so they don&rsquo;t cover a storefront.',
			'Supervision is licensed too: no one may act as a foreman, supervisor or superintendent over electrical work for a contractor without a CIB contractor or journeyman license in the right category for the work. Electrical licensees complete six hours of continuing education every 36 months. Check the contractor and the electrician on the CIB license search before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Oklahoma Construction Industries Board',
			'Business' => 'Electrical contractor, in a category of work',
			'Individuals' => 'Journeyman electrician, in the same kind of category',
			'Residential licenses' => 'Limited to residential electrical construction',
			'Continuing education' => '6 hours every 36 months',
		),
		'sources' => array(
			array('Oklahoma CIB: OAC Title 158, Chapter 40 (Electrical) (PDF)', $cib_158_40),
			array('Oklahoma CIB: 2026 proposed rules (PDF)', 'https://aem-prod.oklahoma.gov/content/dam/ok/en/cib/documents/rules/CIB%202026%20Proposed%20Rules%20with%20Gov%20Approval.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Oklahoma',
		'body' => array(
			'Oklahoma cities issue sign permits under their own zoning codes; Oklahoma City, Tulsa and Norman each enforce their own sign rules, and a lit sign also needs an electrical permit pulled by a licensed contractor.',
			'In <strong>Oklahoma City</strong>, all signs must be submitted by a <strong>registered sign contractor</strong>. The application lists the property, owner, legal description, sign type, height, square footage and estimated cost, and it includes a site plan drawn to scale by a <strong>state-licensed engineer or surveyor</strong> showing property lines, easements, right-of-way and the sign&rsquo;s location, plus its distance to any highway within 500 feet. Wall and roof signs add elevations of the building face with overall dimensions, material and color details, and structural details of the wall or roof connection where needed. Permits are filed online through the city&rsquo;s permitting portal or at the Development Center.',
			'Signs in a designated Design District may need extra review through the Planning Department&rsquo;s Certificate of Appropriateness program before the permit is issued.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Oklahoma City applicant' => 'Registered sign contractor',
			'OKC site plan' => 'Drawn to scale by a state-licensed engineer or surveyor',
			'OKC Design Districts' => 'Certificate of Appropriateness may be required',
		),
		'sources' => array(
			array('City of Oklahoma City: Sign permit requirements (PDF)', $okc_check),
			array('City of Oklahoma City: Sign permits', 'https://www.okc.gov/Services/Permits/Sign-Permits'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs in Oklahoma City',
		'body' => array(
			'Since March 2024, Oklahoma City allows temporary signs in many city rights-of-way, the strip between the curb and the sidewalk, medians and alleys, with a permit. The permit costs <strong>$25</strong>, each sign needs a sticker at 25 cents that is good for the month it&rsquo;s bought, and signs may stay up <strong>30 days, three times a year</strong>. They&rsquo;re never allowed in intersection sight triangles or on utility poles, lamp posts, street signs or parking meters. Temporary signs on your own private property, outside the right-of-way, don&rsquo;t need this permit.',
		),
		'sources' => array(
			array('City of Oklahoma City: Temporary signs in the right-of-way (Feb. 13, 2024)', $okc_row),
		),
	),
	'climate' => array(
		'Oklahoma storefronts face strong spring winds and hailstorms on the open plains, plus summer heat and winter ice storms. Ask your installer to anchor letters for high gusts and to use sealed, gasketed raceways on exposed frontage.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Oklahoma Storefronts',
		'lead' => 'Lit letters for Oklahoma City and Tulsa shopping centers.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for strip centers along busy roads.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for maximum nighttime presence in Oklahoma.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Oklahoma.',
			'18oz-blockout-banner' => 'Heavy banners built for wind in Oklahoma.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Oklahoma?',
			'a' => 'We ship custom channel letters anywhere in Oklahoma, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a CIB-licensed electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign electrician need in Oklahoma?',
			'a' => 'A Construction Industries Board journeyman license in a category that covers commercial work, working for a CIB-licensed electrical contractor. Residential contractor and residential journeyman licenses are limited to residential construction, so they don&rsquo;t cover a storefront. Foremen and supervisors on the job need a license in the right category too.',
		),
		array(
			'q' => 'Who files a sign permit in Oklahoma City?',
			'a' => 'A registered sign contractor. The application includes a site plan drawn to scale by a state-licensed engineer or surveyor and, for wall signs, elevations of the building face plus structural connection details where needed. Design Districts may add a Certificate of Appropriateness.',
		),
		array(
			'q' => 'Can I put temporary signs near the street in Oklahoma City?',
			'a' => 'In many city rights-of-way, yes, with a $25 permit and a 25-cent monthly sticker per sign, for up to 30 days three times a year. Signs on your own property outside the right-of-way don&rsquo;t need that permit, and each sticker is good for the month it&rsquo;s bought.',
		),
		array(
			'q' => 'How long does shipping to Oklahoma take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'No sign-specific CIB electrical category was found; the category and supervision rules come from OAC 158:40 (via search excerpts of the CIB PDF, 2026-10-27). Confirm with CIB.',
		'OKC sign checklist PDF read 2026-10-27 (undated); the right-of-way temporary sign program is from the city news release of Feb. 13, 2024. Fees: re-check yearly.',
		'Tulsa sign rules not researched.',
		'Transit days to Oklahoma not shown until verified.',
	),
);
