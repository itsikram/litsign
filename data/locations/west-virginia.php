<?php
/**
 * West Virginia state guide (/locations/west-virginia/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$wv_rule = 'https://www.law.cornell.edu/regulations/west-virginia/W-Va-C-S-R-SS-103-5-2';
$mgw_code = 'https://morgantownwv.gov/DocumentCenter/View/2705/2019-Planning-and-Zoning-Code';

return array(
	'slug' => 'west-virginia',
	'name' => 'West Virginia',
	'abbr' => 'WV',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/West_Virginia',
	'neighbors' => array('ohio', 'pennsylvania', 'maryland', 'virginia', 'kentucky'),
	'seo_title' => 'Channel Letters Shipped to West Virginia | Sign Electrician',
	'seo_description' => 'Channel letters shipped to West Virginia: the SP-ES sign electrician license, contractor thresholds and Morgantown sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to West Virginia',
	'intro' => array(
		'West Virginia businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a West Virginia-licensed electrician or sign electrician. Banners and window graphics are printed in Renton, WA.',
		'The State Fire Marshal licenses electricians, and its rules include a <strong>specialty electric sign electrician (SP-ES)</strong> license. It covers wiring directly related to electric signs that is electrically isolated from the building&rsquo;s system, starting at a disconnect or junction box right next to the sign, which a master or journeyman installs.',
		'Contractor licensing is separate, under the Contractor Licensing Act: since 2025 a license is required for commercial jobs of $25,000 or more and residential jobs of $5,000 or more.',
	),
	'facts' => array(
		'Electrician licensing' => 'West Virginia State Fire Marshal',
		'Sign license' => 'Specialty electric sign electrician (SP-ES)',
		'Contractor license' => '$25,000+ commercial; $5,000+ residential (W. Va. Code 30-42)',
		'Morgantown lit signs' => 'Electrical permit plus a testing-lab label',
	),
	'categories' => array(
		'channel-letters' => 'Downtown Morgantown (B-4) and B-1 don&rsquo;t allow internally lit signs except neon, while the B-2, B-5 and I-1 districts do, so check your zoning before choosing face-lit letters.',
		'banners' => 'A Morgantown banner needs a temporary sign permit good for 30 days, up to three a year, flat on a wall, cloth or vinyl, and no larger than 32 square feet.',
		'flags-aframes' => 'Morgantown allows one sandwich board per business on the adjacent sidewalk, up to 4 feet high and 2 feet wide, taken in after hours and in bad weather.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in West Virginia',
		'body' => array(
			'The Fire Marshal&rsquo;s electrician licensing rules (W. Va. Code R. 103-5, effective May 1, 2024) define master electricians, with at least two years of electrical experience, and journeyman electricians, with at least one year, who work under a master and may not design any part of an electrical system; supervision doesn&rsquo;t require the master to be physically present with the journeyman. Helpers may not do any electrical work unsupervised, and one master or journeyman may supervise no more than three helpers on a job site.',
			'The <strong>SP-ES</strong> license authorizes electrical work only as part of the holder&rsquo;s electric sign business, on wiring directly related to electric signs and electrically isolated from the building wiring system. For a typical wall sign, that wiring starts at the load-side terminals of a disconnect, or inside a junction box, located immediately next to the sign. Installing that disconnect or junction box, with its line-side connections and circuit conductors, isn&rsquo;t part of the sign license, so a master or journeyman brings the circuit to the sign.',
			'Businesses are licensed under the Contractor Licensing Act (W. Va. Code 30-42). As amended in 2025, a contractor is anyone whose job costs $5,000 or more for residential work or $25,000 or more for commercial work. The act defines an electrical contractor classification but no sign classification, and a licensed contractor posts the license at the job site and puts the license number in its ads and contracts.',
		),
		'table' => array(
			'Licensing agency' => 'West Virginia State Fire Marshal',
			'SP-ES sign electrician' => 'Sign wiring isolated from the building system',
			'Where SP-ES work starts' => 'Load side of a disconnect or junction box next to the sign',
			'Master / journeyman' => 'Two years / one year of electrical experience',
			'Contractor license' => '$25,000+ commercial, $5,000+ residential',
		),
		'sources' => array(
			array('W. Va. Code R. 103-5-2: Electrician license classes (Cornell LII)', $wv_rule),
			array('West Virginia State Fire Marshal: Regulatory and Licensing Division', 'https://firemarshal.wv.gov/regulatory-and-licensing-division'),
			array('W. Va. Code 30-42-3: Contractor Licensing Act definitions', 'https://code.wvlegislature.gov/30-42-3/'),
			array('W. Va. Code 30-42-6: License display', 'https://code.wvlegislature.gov/30-42-6/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Morgantown and Charleston',
		'body' => array(
			'West Virginia cities issue sign permits under their own codes. In <strong>Morgantown</strong>, only the property owner, or an agent, lessee or contract purchaser the owner specifically authorizes, may file a sign permit application with the Planning Department, a building permit is required as well, and the Planning Director decides within 20 working days of a complete application. Downtown B-4 signs over 6 square feet go to the Downtown Design Review Committee.',
			'Lit signs need more: electric signs with internal wiring or lighting, and external lights aimed at signs, can&rsquo;t be installed until Code Enforcement issues an electrical permit, and all of it must carry the label of a nationally recognized testing laboratory. Wall signs are allowed 0.6 square feet per linear foot of tenant frontage in the B-2, B-5 and I-1 districts and 0.4 in B-1 and B-4, where copy is limited to the business name and logo. A sign permit lapses if the business license lapses or the business closes for 90 days or more.',
			'In <strong>Charleston</strong>, the capital and largest city, the East End Historic District is the only area subject to formal design review: exterior changes to historic buildings there need a Certificate of Appropriateness from the Historic Landmarks Commission.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Morgantown applicant' => 'Owner, or an agent or lessee the owner authorizes',
			'Morgantown lit signs' => 'Electrical permit from Code Enforcement; lab-listed',
			'Morgantown wall signs' => '0.6 sq ft per foot (B-2, B-5, I-1); 0.4 (B-1, B-4)',
			'Charleston design review' => 'East End Historic District only',
		),
		'sources' => array(
			array('City of Morgantown: Planning and Zoning Code, Article 1369 Signs (PDF)', $mgw_code),
			array('City of Charleston: Planning boards and commissions', 'https://www.charlestonwv.gov/node/697'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs and Sandwich Boards in Morgantown',
		'body' => array(
			'Temporary signs in Morgantown need a permit, and each permit lasts 30 days and covers one sign. A business can get no more than three in any 12-month period at the same location. The sign must be cloth or vinyl, mounted flat on a wall, and no larger than 32 square feet, so plan a grand opening and two promotions a year around those three windows.',
			'One sandwich board per business may stand on the sidewalk next to it, no higher than 4 feet and no wider than 2 feet, and it must come in outside trading hours and in bad weather. Window lettering that shows only hours, the street address or non-commercial copy and logos is exempt from the sign regulations.',
		),
		'sources' => array(
			array('Morgantown Planning and Zoning Code &sect;&sect; 1369.05 and 1369.07 (PDF)', $mgw_code),
		),
	),
	'climate' => array(
		'West Virginia&rsquo;s mountains shape its weather: storms stall over narrow valleys and turn into flash floods, ridgetop towns get heavy snow and ice, and summers in the Kanawha and Ohio valleys are hot and humid. Mount letters well above any past high-water line, ask your installer to seal fasteners against driving rain, and choose drivers and connections rated for damp locations.',
	),
	'popular' => array(
		'heading' => 'Popular Picks for West Virginia Storefronts',
		'lead' => 'Lit letters for Charleston, Huntington and Morgantown storefronts, plus permit-friendly banners.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Face-lit letters where your district allows internal lighting.',
			'standard-channel-letter-back-lit' => 'A halo glow for brick downtown facades.',
			'13oz-vinyl-banner' => 'Vinyl banners up to Morgantown&rsquo;s 32 square foot limit.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-64 and I-79.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in West Virginia?',
			'a' => 'We ship custom channel letters anywhere in West Virginia, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a West Virginia-licensed master or journeyman electrician, and a sign company with an SP-ES sign electrician if you like, to mount and connect them.',
		),
		array(
			'q' => 'Does West Virginia have a sign electrician license?',
			'a' => 'Yes. The State Fire Marshal issues a specialty electric sign electrician (SP-ES) license under its electrician licensing rules. It covers only wiring directly related to electric signs and electrically isolated from the building wiring system, done as part of the holder&rsquo;s electric sign business, starting at a disconnect or junction box next to the sign.',
		),
		array(
			'q' => 'Who installs the disconnect for my sign in West Virginia?',
			'a' => 'A master or journeyman electrician. The SP-ES license begins at the load-side terminals of a disconnect, or inside a junction box, located immediately next to the sign. Installing that disconnect or junction box, with its line-side connections and circuit conductors, isn&rsquo;t covered by the sign license.',
		),
		array(
			'q' => 'When does a West Virginia sign job need a licensed contractor?',
			'a' => 'When the commercial job costs $25,000 or more, or a residential job $5,000 or more, under the Contractor Licensing Act as amended in 2025. The licensed contractor must post the license at the job site and include its license number in advertising and in signed contracts. Electricians are licensed separately by the Fire Marshal.',
		),
		array(
			'q' => 'How long can a banner stay up in Morgantown?',
			'a' => 'Thirty days per temporary sign permit, with no more than three permits in any 12-month period for the same business and location. Each permit covers one cloth or vinyl sign mounted flat on a wall, up to 32 square feet. A sandwich board, up to 4 by 2 feet, can go out during business hours.',
		),
		array(
			'q' => 'How long does shipping to West Virginia take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'W. Va. Code R. 103-5 (effective May 1, 2024) read directly from Cornell LII 2026-11-20. The old Fire Marshal electrical licensing page now returns 404 and was replaced by the Regulatory and Licensing Division page; the experience hours that only the old page carried (specialty one year / 2,000 hours) were removed. A search summary says exams use the 2023 NEC from Jan. 1, 2026; not confirmed on a page read here, so not shown.',
		'W. Va. Code 30-42-3 and 30-42-6 read from archive copies of Oct. 2025 (code.wvlegislature.gov times out here); thresholds as amended by 2025 SB871 replace the old $2,500 note. Check 2026 session changes and re-check the figures yearly.',
		'Charleston&rsquo;s sign ordinance, window and banner rules were not reachable (the zoning PDF has no archive copy and the handbooks are scanned images); Morgantown (2019 code edition) is used instead. Later Morgantown amendments not checked.',
		'Who performs electrical inspections statewide was not confirmed; only Morgantown&rsquo;s local electrical permit is shown.',
		'Transit days to West Virginia not shown until verified.',
	),
);
