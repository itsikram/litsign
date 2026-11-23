<?php
/**
 * Iowa state guide (/locations/iowa/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ia_103 = 'https://www.legis.iowa.gov/docs/code/2026/103.html';
$ia_401 = 'https://www.legis.iowa.gov/docs/iac/chapter/481.401.pdf';
$dsm_134 = 'https://www.municode.com/webcontent/13242/Chapter-134-ZONING.pdf';

return array(
	'slug' => 'iowa',
	'name' => 'Iowa',
	'abbr' => 'IA',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Iowa',
	'neighbors' => array('minnesota', 'wisconsin', 'illinois', 'missouri', 'nebraska', 'south-dakota'),
	'seo_title' => 'Channel Letters Shipped to Iowa | Sign Installation License',
	'seo_description' => 'Channel letters shipped to Iowa: the sign installation endorsement and its 220 V / 20 A limit, plus Des Moines window and banner rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Iowa',
	'intro' => array(
		'Iowa businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Iowa electrician or sign installer. Banners and window graphics are printed in Renton, WA.',
		'Iowa writes sign work into its electrician law. Iowa Code chapter 103 defines a <strong>special electrician</strong> as someone licensed for special classes of work, and sign installation is one of them: the endorsement lets the holder connect signs, while a new or upgraded branch circuit stays with a master electrician.',
		'In Des Moines, the capital and largest city, window signs can cover up to 25 percent of a window outside the neighborhood districts, and one temporary sign at a time can stay up for as long as six months a year.',
	),
	'facts' => array(
		'Licensing law' => 'Iowa Code chapter 103; rules in Iowa Admin. Code 481-401',
		'Sign license' => 'Special electrician, Sign Installation endorsement',
		'Endorsement limit' => 'Not over 220 V and 20 A',
		'Des Moines window signs' => 'Up to 25% of the window (most districts)',
	),
	'categories' => array(
		'channel-letters' => 'Des Moines measures channel letters by the smallest rectangle that can be drawn around the letters or elements, which is the number to check against your wall allowance.',
		'window-graphics' => 'Outside its N and NX neighborhood districts, Des Moines lets window signs cover up to 25 percent of a window, counting only the glass.',
		'banners' => 'Des Moines allows one temporary sign at a time for up to six months a year, up to 32 square feet in commercial districts, and since 2025 unlit ones of 15 square feet or less need no permit.',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Iowa',
		'body' => array(
			'Iowa licenses electricians statewide under Iowa Code chapter 103. Its definition of a <strong>special electrician</strong> covers special classes of work, naming irrigation system wiring, disconnecting and reconnecting existing air conditioning and refrigeration, and sign installation. A special electrician needs experience acceptable to the board in each class and doesn&rsquo;t need an electrical contractor license for work inside it (&sect; 103.13). The license costs $75 for three years.',
			'The endorsement rules moved in July 2025 from the Public Safety Department&rsquo;s chapter 502 to Iowa Administrative Code 481-401.2, under the Department of Inspections, Appeals, and Licensing, with the wording unchanged. The <strong>Sign Installation</strong> endorsement &ldquo;does not authorize the holder to connect power to a sign that has a voltage greater than 220V and an ampere rating greater than 20 amps,&rdquo; and the initial installation or upgrade of branch circuits supplying the sign may only be done by a licensed master electrician or by a journeyman under a master&rsquo;s supervision.',
			'The irrigation and air conditioning endorsements each require a board exam or two years of documented experience, but the rule lists no separate exam for the sign endorsement. The usual split is a sign installer with the endorsement hooking up your letters and an electrical contractor&rsquo;s master electrician bringing any new circuit to the wall.',
		),
		'table' => array(
			'Licensing law' => 'Iowa Code chapter 103',
			'Sign license' => 'Special electrician with the Sign Installation endorsement',
			'Endorsement limit' => 'Not over 220 V and 20 A',
			'Branch circuits' => 'Master electrician, or journeyman under a master',
			'License fee' => '$75 for three years',
		),
		'sources' => array(
			array('Iowa Code chapter 103: Electricians and electrical contractors', $ia_103),
			array('Iowa Admin. Code 481-401: Electrician licensing (PDF)', $ia_401),
		),
	),
	'permits' => array(
		'heading' => 'Electrical Inspections and Sign Permits in Iowa',
		'body' => array(
			'State inspection covers all new commercial and industrial electrical installations, inside and outside buildings (Iowa Code &sect; 103.23), except in cities and counties that adopt their own equal or stricter code and inspect it themselves (&sect; 103.24). Where the state inspects, the licensee or owner applies for a state permit before starting work, and the state code is the 2023 National Electrical Code with Iowa amendments.',
			'In <strong>Des Moines</strong>, sign permits come from Zoning Enforcement in Neighborhood Services under Municipal Code section 26-802. Wall signs can&rsquo;t cover windows or other building openings, and in the downtown, mixed-use and commercial districts the total wall sign area is based on building frontage, 1.25 or 2 square feet per linear foot for the first three floors depending on the district, with no more than two wall signs per occupant.',
			'Lighting has its own limits: sign illumination must be steady, except approved electronic displays, and may not exceed 70 foot-candles measured 2 feet from the sign face, shielded so it doesn&rsquo;t glare onto the street.',
		),
		'table' => array(
			'State inspection' => 'New commercial work, unless the city inspects (&sect;&sect; 103.23&ndash;103.24)',
			'Des Moines sign permits' => 'Zoning Enforcement, Neighborhood Services',
			'Des Moines wall signs' => '1.25 or 2 sq ft per foot of frontage, by district',
			'Des Moines lighting' => 'Steady light, max 70 foot-candles at 2 ft',
		),
		'sources' => array(
			array('Iowa Admin. Code 481-404: Electrical inspection program (PDF)', 'https://www.legis.iowa.gov/docs/iac/chapter/481.404.pdf'),
			array('City of Des Moines: Zoning Enforcement Division', 'https://www.dsm.city/departments/neighborhood_services/zoning_enforcement.php'),
			array('Des Moines Municipal Code Chapter 134, Article 5: Signs (PDF)', $dsm_134),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Banners in Des Moines',
		'body' => array(
			'One temporary sign is allowed at a time, for no more than six months in a calendar year. In commercial and mixed-use districts it can be up to 32 square feet and 8 feet tall; in the A, N, NX and NM districts the limit is 4 square feet and 3 feet tall.',
			'Since an ordinance passed in May 2025, non-illuminated temporary signs of 15 square feet or less don&rsquo;t need a permit, so a 3 by 5 foot banner can go up without one, while a larger banner still does.',
		),
		'sources' => array(
			array('Des Moines Municipal Code &sect; 134-5.4.6: Signs used on a temporary basis (PDF)', $dsm_134),
			array('City of Des Moines: Ordinance No. 16,465, May 2025 (PDF)', 'https://councildocs.dsm.city/ordinances/16,465.pdf'),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Des Moines',
		'body' => array(
			'Window signs are allowed in every Des Moines zoning district, including decals and painted lettering, and neon displayed inside the glass. Outside the N and NX neighborhood districts they may cover up to <strong>25 percent of the window</strong>; inside those districts each window opening is limited to a sign of 8.5 by 11 inches. Only the glass counts toward the window area.',
		),
		'sources' => array(
			array('Des Moines Municipal Code &sect; 134-5.4.4: Window signs (PDF)', $dsm_134),
		),
	),
	'climate' => array(
		'Iowa winters bring blizzards and deep cold, and summer storms can turn into derechos, long lines of straight-line wind that cross the state in hours. Hail and spring flooding along the river towns round out the list. Ask your installer to anchor letters for gusts well above the usual wind, seal every penetration, and keep raceways clear of where snow piles against the wall.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Iowa Storefronts',
		'lead' => 'Lit letters for Des Moines, Cedar Rapids and main-street storefronts, plus window graphics within city limits.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for shopping centers along I-35 and I-80.',
			'standard-channel-letter-back-lit' => 'A warm glow on main-street brick.',
			'feather-angled-flag-pole' => 'Flags for highway-facing businesses.',
			'18oz-blockout-banner' => 'Sturdy banners for six-month temporary displays.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Iowa?',
			'a' => 'We ship custom channel letters anywhere in Iowa, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an Iowa-licensed electrician or a special electrician with the sign installation endorsement to mount and connect them.',
		),
		array(
			'q' => 'Does Iowa have a sign installer license?',
			'a' => 'Yes. A special electrician license can carry the Sign Installation endorsement, which lets the holder connect signs to electrical systems. It doesn&rsquo;t cover power to a sign over 220 volts and 20 amps, and it doesn&rsquo;t cover new or upgraded branch circuits. The license costs $75 for three years, and no contractor license is needed for work inside the class.',
		),
		array(
			'q' => 'Who runs the circuit to my sign in Iowa?',
			'a' => 'A licensed master electrician, or a journeyman working under a master&rsquo;s supervision. Iowa Administrative Code 481-401.2 says the initial installation or upgrade of branch circuits supplying a sign may only be done by them, even when a sign installer with the endorsement makes the final connection.',
		),
		array(
			'q' => 'How much of a Des Moines window can graphics cover?',
			'a' => 'Up to 25 percent of the window in most zoning districts, measured on the glass alone. In the N and NX neighborhood districts each window opening is limited to a sign of 8.5 by 11 inches. Decals, painted lettering and neon displayed inside the window all count as window signs.',
		),
		array(
			'q' => 'How long does shipping to Iowa take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Iowa Code chapter 103 (2026) and Iowa Admin. Code 481-401 and 481-404 (IAC 9/30/26) read directly 2026-11-20; the old 661-502.2 citation was replaced (transferred 7/9/25).',
		'Des Moines Chapter 134 read from the compiled copy through Ord. 16,125 (May 2022; archive copy of 2022-12-02). 2025 Ords. 16,422, 16,461, 16,465 and 16,510 checked: none change the window or temporary sign sections. Later amendments (an electronic display hearing in Dec. 2025 was seen only in a search snippet) not checked.',
		'Des Moines zoning enforcement page read from the archive copy of 2026-10-29 (dsm.city returns 403 here). Des Moines A-frame and historic district sign rules not researched.',
		'License fee ($75 for three years): re-check yearly.',
		'Transit days to Iowa not shown until verified.',
	),
);
