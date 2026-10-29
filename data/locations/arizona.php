<?php
/**
 * Arizona state guide (/locations/arizona/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$phx_705 = 'https://phoenix.municipal.codes/ZO/705';

return array(
	'slug' => 'arizona',
	'name' => 'Arizona',
	'abbr' => 'AZ',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Arizona',
	'neighbors' => array('california', 'nevada', 'utah', 'new-mexico'),
	'seo_title' => 'Channel Letters Shipped to Arizona | ROC C-38 Sign License',
	'seo_description' => 'Channel letters shipped to Arizona: who can install them (ROC C-38 sign and C-11 electrical licenses), Phoenix permits and window rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Arizona',
	'intro' => array(
		'Arizona businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for an ROC-licensed Arizona contractor. Banners and window graphics are printed in Renton, WA.',
		'Arizona licenses the business, not the individual tradesperson. The Registrar of Contractors (ROC) has a dedicated <strong>C-38 Signs</strong> classification whose scope includes the posts, supports and electrical wiring a sign installer needs, alongside the general <strong>C-11 Electrical</strong> classification for commercial wiring under 600 volts.',
		'In Phoenix, every sign needs a permit from Planning and Development before it goes up, electric signs must carry a component tag showing they meet the City Electrical Code, and window signs can cover up to 30 percent of each ground-floor window.',
	),
	'facts' => array(
		'Licensing agency' => 'Arizona Registrar of Contractors (ROC)',
		'Sign license' => 'C-38 Signs (includes sign wiring)',
		'Electrical license' => 'C-11 Electrical (commercial, under 600 volts)',
		'Phoenix sign permit' => 'Planning and Development, Zoning Ordinance &sect; 705',
	),
	'categories' => array(
		'channel-letters' => 'Phoenix waives sealed engineering plans for wall signs made of cut-out letters attached directly to the building, as long as no single letter is over 100 square feet.',
		'window-graphics' => 'Phoenix lets a business cover up to 30 percent of each ground-floor window with window signs, and those graphics aren&rsquo;t counted against its wall sign allowance.',
		'banners' => 'A Phoenix grand-opening banner is limited to one per street front for up to 14 days, and only once a building permit or certificate of occupancy has been applied for.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Arizona',
		'body' => array(
			'The Registrar of Contractors licenses contracting businesses, each through a qualifying party, rather than issuing state journeyman or master cards to individual electricians. Two classifications matter for channel letters. <strong>C-38 Signs</strong> lets the licensee install and repair posts, poles, supports, paint and the electrical wiring needed to install and repair signs, displays and flagpoles. <strong>C-11 Electrical</strong> covers commercial wiring and equipment under 600 volts, including overhead wiring on public rights-of-way for signs; R-11 is the residential version and CR-11 combines both.',
			'In practice, a sign company holding C-38 can mount your letters and connect them, and a C-11 electrical contractor can bring a new circuit to the sign location. Ask for the license number and classification and look it up on the ROC contractor search before you sign a contract.',
		),
		'table' => array(
			'Licensing agency' => 'Arizona Registrar of Contractors',
			'C-38 Signs' => 'Posts, supports and wiring needed to install and repair signs',
			'C-11 Electrical' => 'Commercial wiring under 600 volts, including sign wiring over rights-of-way',
			'License holder' => 'The contracting business, through its qualifying party',
		),
		'sources' => array(
			array('Arizona ROC: License classifications', 'https://roc.az.gov/license-classifications'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Arizona',
		'body' => array(
			'Each Arizona city and county issues sign permits under its own zoning ordinance, so Phoenix, Scottsdale, Tempe and Mesa all apply different limits.',
			'In <strong>Phoenix</strong>, Section 705 of the Zoning Ordinance requires a permit from the Planning and Development Department before any sign is erected, displayed, relocated or altered. The owner, tenant, their agent, an Arizona-licensed contractor, or a registered architect or engineer may apply, and every application includes plans showing how the sign attaches and a plot layout. Plans sealed by an Arizona engineer or architect are required for larger wall signs, but not for cut-out letters fixed straight to the wall with no letter over 100 square feet. The permit lapses if work doesn&rsquo;t start within 180 days or stops for 60 days.',
			'Lit signs have extra rules in Phoenix: they must meet the City Electrical Code and display an electrical component tag or an approved testing-laboratory tag, and an illuminated sign facing single-family homes generally can&rsquo;t sit within 60 feet of them without a use permit.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Phoenix' => 'Planning and Development permit before any sign goes up (&sect; 705)',
			'Phoenix plans' => 'Attachment details and plot layout; sealed plans only for larger signs',
			'Phoenix permit life' => 'Void if work doesn&rsquo;t start in 180 days or stops for 60',
			'Phoenix lit signs' => 'City Electrical Code plus a component or lab tag',
		),
		'sources' => array(
			array('Phoenix Zoning Ordinance &sect; 705: Signs', $phx_705),
			array('City of Phoenix: Sign permit information (PDF)', 'https://www.phoenix.gov/pddsite/Documents/TRT/dsd_trt_pdf_00615.pdf'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Temporary Displays in Phoenix',
		'body' => array(
			'Phoenix&rsquo;s Zoning Administrator issues temporary sign permits for occasions such as a grand opening or identifying a business before its permanent letters are installed. Banners and balloons can qualify; pennants don&rsquo;t, and nothing may be placed in the public right-of-way. Banners must be securely attached, clear public walkways by 8 feet, be vented for wind and stay below the roofline.',
			'A grand-opening or awaiting-permanent-sign banner is limited to one per street front for up to 14 days, and only while a building permit or certificate of occupancy application is on file. Portable ground displays, including A-frames, are prohibited in Phoenix unless another section of the ordinance allows them, so plan on banners or window graphics instead.',
		),
		'sources' => array(
			array('Phoenix Zoning Ordinance &sect; 705.D.10 and 705.B.3', $phx_705),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Phoenix',
		'body' => array(
			'Window signs may cover up to <strong>30 percent of each ground-floor window</strong>, and panes divided by muntins or mullions count as one window. They aren&rsquo;t counted as wall signs, so they don&rsquo;t use up your wall allowance, but they can&rsquo;t go on glass doors, where only small pedestrian signs are allowed.',
		),
		'sources' => array(
			array('Phoenix Zoning Ordinance &sect; 705.D.5', $phx_705),
		),
	),
	'climate' => array(
		'Arizona storefronts take intense sun nearly every day of the year, and Valley summers are some of the hottest in the country. The summer monsoon brings sudden microburst winds and dust storms. Pick face colors rated for strong UV, and ask your installer to anchor letters for gusts rather than average wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Arizona Storefronts',
		'lead' => 'Bright lit letters for strip centers and halo glows for stucco facades.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'The standard lit letter in Valley strip centers.',
			'hidden-back-halo-lit' => 'A halo glow that looks sharp on stucco.',
			'standard-channel-letter-back-lit' => 'Back lit letters for a softer evening look.',
			'feather-angled-flag-pole' => 'Flags for busy arterial roads.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Arizona?',
			'a' => 'We ship custom channel letters anywhere in Arizona, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an ROC-licensed contractor, such as a C-38 sign company, to mount and connect them.',
		),
		array(
			'q' => 'Which ROC license covers sign installation in Arizona?',
			'a' => 'The C-38 Signs classification. Its scope includes the posts, poles, supports and electrical wiring needed to install and repair signs. A C-11 Electrical contractor can handle commercial wiring under 600 volts, such as a new circuit to the sign. Check the license number and classification on the ROC contractor search before you sign.',
		),
		array(
			'q' => 'Do channel letters need engineered plans in Phoenix?',
			'a' => 'Usually not for individual letters. Phoenix requires sealed plans for larger wall signs but exempts cut-out letters attached directly to the building when no single letter exceeds 100 square feet. Every application still shows how the sign attaches, and the permit is void if work doesn&rsquo;t start within 180 days.',
		),
		array(
			'q' => 'How much of a Phoenix shop window can graphics cover?',
			'a' => 'Up to 30 percent of each ground-floor window, counting panes split by muntins as one window. Window signs don&rsquo;t count toward the wall sign area, but they aren&rsquo;t allowed on glass doors. Temporary window signs fall under the same section, so promotions share that 30 percent.',
		),
		array(
			'q' => 'How long does shipping to Arizona take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'ROC classifications read 2026-10-27 from the Internet Archive copy of 2026-08-17 (roc.az.gov / azroc.gov return 403 to the research PC); open the link once from another network.',
		'Phoenix Zoning Ordinance &sect; 705 read from the Internet Archive copy of 2025-12-20 (current through Ordinance G-7396, July 2, 2025); re-check yearly.',
		'Phoenix fees in the city PDF ($75 wall signs, $150 ground signs, $150 electrical sign inspection) left off the page; confirm before adding.',
		'Transit days to Arizona not shown until verified.',
	),
);
