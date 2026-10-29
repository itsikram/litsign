<?php
/**
 * Alabama state guide (/locations/alabama/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$mgm_elec = 'https://montgomeryal.gov/how-do-i/apply-for/electrical-inspections';
$hsv_signs = 'https://www.huntsvilleal.gov/development/building-construction/planning/zoning/signs/';

return array(
	'slug' => 'alabama',
	'name' => 'Alabama',
	'abbr' => 'AL',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Alabama',
	'neighbors' => array('tennessee', 'georgia', 'florida', 'mississippi'),
	'seo_title' => 'Channel Letters Shipped to Alabama | AECB License Guide',
	'seo_description' => 'Channel letters shipped to Alabama: AECB contractor and journeyman licenses, Montgomery sign wiring permits and Huntsville approvals. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Alabama',
	'intro' => array(
		'Alabama businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Alabama electrical contractor. Banners and window graphics are printed in Renton, WA.',
		'Alabama issues two electrical licenses through the Alabama Electrical Contractors Board (AECB): the electrical contractor license and the journeyman electrician license. Anyone in the business of soliciting and installing electrical power or control systems needs a state license, with no dollar threshold, and both licenses renew every year.',
		'City permits follow from that license. Montgomery issues electrical permits for the wiring on or inside an illuminated sign only to contractors holding a current state electrical contractor license, and in Huntsville every sign permit is approved by Zoning Administration.',
	),
	'facts' => array(
		'Licensing agency' => 'Alabama Electrical Contractors Board (AECB)',
		'Licenses' => 'Electrical contractor; journeyman electrician',
		'Montgomery sign wiring' => 'Electrical permit, state-licensed electrical contractor only',
		'Huntsville sign permits' => 'Approved by Zoning Administration (Article 72)',
	),
	'categories' => array(
		'channel-letters' => 'In Montgomery, the electrical permit for wiring on or within an illuminated sign is issued only to an electrical contractor with a current State of Alabama license, so the sign company needs one on the job.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Alabama',
		'body' => array(
			'The <strong>AECB</strong> licenses <strong>electrical contractors</strong>, who may take any job, employ electricians and run their own business, and <strong>journeyman electricians</strong>, who work under a licensed contractor. Alabama doesn&rsquo;t issue a separate master electrician license, and apprentices and helpers don&rsquo;t need one.',
			'Under Title 34, Chapter 36 of the Code of Alabama, anyone engaged in the business of soliciting and installing electrical power or control systems must hold a state electrical license, whatever the job size. Both licenses are renewed annually, so ask to see a current card and check the installer on the AECB license search before work starts.',
		),
		'table' => array(
			'Licensing agency' => 'Alabama Electrical Contractors Board',
			'Business' => 'Electrical contractor license',
			'Individuals' => 'Journeyman electrician (works under a contractor)',
			'Threshold' => 'None; any electrical contracting business',
			'Renewal' => 'Annual',
		),
		'sources' => array(
			array('Alabama Electrical Contractors Board: Licensee forms', 'https://aecb.alabama.gov/licensees/forms/'),
			array('Alabama Administrative Code ch. 303-X-1', 'https://admincode.legislature.state.al.us/api/chapter/303-X-1'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Alabama',
		'body' => array(
			'Alabama cities issue sign permits under their own zoning ordinances; Huntsville, Birmingham, Mobile and Montgomery each run their own permit offices, and counties cover some unincorporated areas. A lit sign usually needs two permits from two offices: the sign permit from zoning, and an electrical permit for the wiring.',
			'In <strong>Montgomery</strong>, electrical permits, including those for wiring illuminated and neon signs, are issued to electrical contractors licensed by the Alabama Electrical Contractors Board. Anyone in the electrical business in the city should also be a qualified electrical contractor with a current City of Montgomery business license, so a sign company without its own AECB license brings in a licensed electrical contractor to pull the permit and connect the sign.',
			'In <strong>Huntsville</strong>, signs are governed by Article 72 of the Zoning Ordinance, the Sign Control Regulations, which cover sign licenses, required permits and the signs allowed in each district. All sign permits must be approved by Zoning Administration, and the city amended Article 72&rsquo;s definitions and several district rules in 2025, so ask the Zoning Office which version applies when you apply.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county zoning office',
			'Montgomery sign wiring' => 'Electrical permit issued to an AECB-licensed contractor',
			'Montgomery business license' => 'Required for anyone in the electrical business',
			'Huntsville' => 'Zoning Administration approves every sign permit (Article 72)',
		),
		'sources' => array(
			array('City of Montgomery: Electrical permits', $mgm_elec),
			array('City of Huntsville: Signs', $hsv_signs),
			array('City of Huntsville: Zoning Ordinance (Article 72, Sign Control Regulations) (PDF)', 'https://www.huntsvilleal.gov/wp-content/uploads/2015/08/ZonOrd-1.pdf'),
		),
	),
	'climate' => array(
		'In Alabama, spring and late-fall storms bring tornado warnings and damaging straight-line winds, summers are hot and humid, and Mobile Bay storefronts face hurricanes and salt air. Ask your installer to anchor letters for gusts and use corrosion-resistant hardware near the Gulf.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Alabama Storefronts',
		'lead' => 'Lit letters for shopping centers and downtown squares.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Birmingham and Huntsville centers.',
			'standard-channel-letter-back-lit' => 'A soft glow on downtown brick in Alabama.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Alabama.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Alabama.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Alabama?',
			'a' => 'We ship custom channel letters anywhere in Alabama, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an AECB-licensed electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign electrician need in Alabama?',
			'a' => 'The company needs an AECB electrical contractor license, and the electricians doing the work need journeyman licenses. Alabama sets no dollar threshold, so even a single storefront sign counts, and both licenses renew every year. Check the license on the AECB search before work starts.',
		),
		array(
			'q' => 'Does Alabama have a master electrician license?',
			'a' => 'No. Alabama issues only two electrical licenses: the electrical contractor license, which lets a business take jobs and employ electricians, and the journeyman electrician license for people working under a contractor. Apprentices and helpers don&rsquo;t need a license, and both licenses renew every year.',
		),
		array(
			'q' => 'Who can get the electrical permit for a sign in Montgomery?',
			'a' => 'Only an electrical contractor holding a current State of Alabama Electrical Contractors License. Montgomery issues electrical permits for wiring illuminated or neon signs to contractors licensed by the AECB, and anyone in the electrical business there also needs a current City of Montgomery business license.',
		),
		array(
			'q' => 'How long does shipping to Alabama take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Montgomery: the old 311 electrical permit article now returns 404; facts now come from the city&rsquo;s electrical inspections page, read from its Internet Archive copy 2026-10-27 (montgomeryal.gov returns 403 to the research PC).',
		'Huntsville signs page read directly 2026-10-27 (Zoning Administration approves all sign permits; the online application was temporarily disabled). Article 72 is named from the zoning ordinance PDF (updated to June 3, 2016) and 2025 legal notices of amendments; its text was not read, so no size limits are shown.',
		'Birmingham sign permit steps not found on an official city page; not shown.',
		'AECB proposed changes to Rule 303-X-2-.08 (license exemptions) in 2023&ndash;2024; final text not read.',
		'Transit days to Alabama not shown until verified.',
	),
);
