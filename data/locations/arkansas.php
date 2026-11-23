<?php
/**
 * Arkansas state guide (/locations/arkansas/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ar_rules = 'https://labor.arkansas.gov/wp-content/uploads/17CARpt-210-Final.pdf';
$ar_aclb = 'https://labor.arkansas.gov/wp-content/uploads/Commercial-Contractors-Law-revised-04.2026.pdf';
$lr_sign = 'https://www.littlerock.gov/media/17738/on-premises-sign-appl.pdf';
$lr_banner = 'https://www.littlerock.gov/media/17739/special-event-sign-permit-banner.pdf';

return array(
	'slug' => 'arkansas',
	'name' => 'Arkansas',
	'abbr' => 'AR',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Arkansas',
	'neighbors' => array('missouri', 'tennessee', 'mississippi', 'louisiana', 'texas', 'oklahoma'),
	'seo_title' => 'Channel Letters Shipped to Arkansas | Sign Electrician Rules',
	'seo_description' => 'Channel letters shipped to Arkansas: the specialist sign electrician license, the $50,000 contractor rule and Little Rock sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Arkansas',
	'intro' => array(
		'Arkansas storefronts can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Arkansas sign electrician or master electrician to connect. Banners and window graphics are printed in Renton, WA.',
		'Arkansas is one of the few states with a license written for exactly this job. The Board of Electrical Examiners issues a <strong>specialist sign electrician</strong> license covering the installation, maintenance and repair of lit signs and neon, including the final hookup to an approved outlet within 25 feet of the sign.',
		'Larger projects bring in a second board. Any commercial job of $50,000 or more, labor and materials included, needs a contractor licensed by the Contractors Licensing Board, whose electrical classification lists electrical signs as a specialty.',
	),
	'facts' => array(
		'Electrical licensing' => 'Board of Electrical Examiners (Department of Labor and Licensing)',
		'Sign license' => 'Specialist sign electrician, outlet within 25 ft',
		'Contractor license' => 'Commercial jobs of $50,000 or more',
		'Little Rock sign permit' => 'Planning and Development, $50 per sign',
	),
	'categories' => array(
		'channel-letters' => 'Little Rock&rsquo;s sign permit form asks whether your letters need a new circuit, an existing circuit or no power at all, so settle the power plan with your electrician before you apply.',
		'banners' => 'Little Rock allows event banners up to 5 by 20 feet under a permit that runs in six-week periods, at most four periods a year, and never on trees, fences or screen walls.',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Arkansas',
		'body' => array(
			'The board&rsquo;s rules, now published as Part 210 of Title 17 of the Code of Arkansas Rules, define six license classes: master, journeyman, residential master, residential journeyman, industrial maintenance and <strong>specialist sign electrician</strong>. The residential classes reach from single-family homes up to fourplexes, so a storefront goes to a master or journeyman electrician or to a sign electrician.',
			'The sign electrician&rsquo;s scope is narrow and specific: installing, maintaining and repairing electrically lit or operated signs and gaseous tubing, &ldquo;making all connection to an approved outlet of sufficient capacity within twenty-five feet&rdquo; of the sign. In other words, the sign electrician connects your letters to an outlet that already exists. The rule says nothing about running a new branch circuit, so plan on a master or journeyman electrician when the wall has no power. Repairing parking lot lights is an add-on that takes a separate board exam.',
			'To qualify, an applicant needs one year of experience with an electrical sign company or an electrical licensee, and applicants who qualify by experience are exempt from the exam. Helpers work under the direct supervision of a licensed sign electrician. The rules adopt the 2026 National Electrical Code, and where no city or county issues a permit, the contractor must post a job-site placard at least 12 by 12 inches with the company name, the master electrician&rsquo;s license number and a phone number.',
			'The Contractors Licensing Board adds a business license on bigger jobs. Under Ark. Code &sect; 17-25-101, a commercial project of $50,000 or more needs a licensed commercial contractor, and splitting the work into smaller contracts to stay under that line isn&rsquo;t allowed. The board&rsquo;s Electrical classification includes electrical signs and requires at least one full-time master electrician on staff.',
		),
		'table' => array(
			'Licensing agency' => 'Arkansas Board of Electrical Examiners',
			'Specialist sign electrician' => 'Lit signs and neon, connected to an outlet within 25 ft',
			'Qualifying experience' => 'One year with a sign company or electrical licensee',
			'New branch circuit' => 'Master or journeyman electrician',
			'Contractor license' => 'Commercial jobs of $50,000 or more (Ark. Code &sect; 17-25-101)',
		),
		'sources' => array(
			array('17 CAR Part 210: Rules of the Arkansas Board of Electrical Examiners (PDF)', $ar_rules),
			array('Arkansas Department of Labor and Licensing: Electrical inspection and licensing', 'https://labor.arkansas.gov/labor/code-enforcement/electrical-inspection-and-licensing/'),
			array('Arkansas Commercial Contractors Licensing Law, revised April 2026 (PDF)', $ar_aclb),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Little Rock and Across Arkansas',
		'body' => array(
			'Arkansas leaves sign permits to cities and counties, so Fayetteville, Fort Smith, Jonesboro and Bentonville each apply their own zoning limits on size, height and lighting.',
			'In <strong>Little Rock</strong>, the capital and largest city, on-premises sign permits go through the Planning and Development Department&rsquo;s zoning and sign inspector. The application has you mark whether the sign needs a new circuit, uses an existing circuit or needs no power, and it asks for a rendering or photo with dimensions, a scaled site plan backed by a certified boundary survey, and a foundation drawing, sealed by an engineer where appropriate. The fee is $50 for each sign.',
			'Pole and projecting signs must leave 13 feet of clearance over areas used by vehicles and 9 feet over walkways, and freestanding signs sit at least 5 feet back from the property line with the street number shown in 6-inch characters. Letters mounted flat on the facade avoid the clearance rules, but the circuit question still applies.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Little Rock office' => 'Planning and Development, zoning and sign inspector',
			'Little Rock attachments' => 'Rendering with dimensions, scaled site plan, foundation drawing',
			'Little Rock fee' => '$50 per sign',
			'Projecting sign clearance' => '13 ft over vehicles, 9 ft over walkways',
		),
		'sources' => array(
			array('City of Little Rock: On-premises sign permit application (PDF)', $lr_sign),
		),
	),
	'districts' => array(
		array(
			'name' => 'Little Rock historic districts (Historic District Commission design guidelines)',
			'rule' => 'Sign materials should be traditional, not plastic, neon or other internally lit materials, and lighting should come from remote sources aimed at the sign rather than from inside it, which favors non-lit or externally lit letters. Signs may be painted on windows and doors, letters on a masonry wall should be anchored in the mortar joints rather than the brick, and portable displays shouldn&rsquo;t be used.',
			'source' => 'https://littlerock.gov/!userfiles/editor/docs/planning/hdc/HDC%20070913%20Guidelines%20Section%20V-VIII%20red.pdf',
		),
	),
	'temporary_signs' => array(
		'heading' => 'Event Banners in Little Rock',
		'body' => array(
			'Little Rock handles banners with a special event sign permit. Each banner can be up to 5 feet high and 20 feet wide, and the permit runs in six-week periods at $20 per banner per period, with no more than four periods in a calendar year, or 24 weeks in total.',
			'Banners can&rsquo;t be fastened to trees, landscaping, screen walls or fences, so plan to mount them on the building itself or on a frame. A 13 oz vinyl banner hemmed and grommeted at 5 by 20 feet fits the maximum size exactly.',
		),
		'sources' => array(
			array('City of Little Rock: Special event sign (banner) permit application (PDF)', $lr_banner),
		),
	),
	'climate' => array(
		'Arkansas sits where Gulf moisture meets spring storm systems, so March through May brings severe thunderstorms, hail and tornado warnings across the Arkansas River Valley, while humid summers push the heat index into triple digits. Winter ice storms can glaze letters and raceways in the north and center of the state. Ask your installer to anchor letters for gusts, seal fastener holes against wind-driven rain, and keep the weep holes in each letter return clear.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Arkansas Storefronts',
		'lead' => 'Lit letters for Little Rock, Fort Smith and Northwest Arkansas retail, plus banners sized to city rules.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Face-lit letters for Northwest Arkansas retail centers.',
			'standard-channel-letter-back-lit' => 'A soft wall glow for downtown brick storefronts.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-40 and I-49.',
			'13oz-vinyl-banner' => 'Event banners up to Little Rock&rsquo;s 5 by 20 foot limit.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Arkansas?',
			'a' => 'We ship custom channel letters anywhere in Arkansas, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an Arkansas-licensed sign electrician or master electrician to mount and connect them.',
		),
		array(
			'q' => 'Does Arkansas have a sign electrician license?',
			'a' => 'Yes. The Board of Electrical Examiners issues a specialist sign electrician license for installing, maintaining and repairing lit signs and neon, with the final connection made to an approved outlet within 25 feet of the sign. It takes one year of experience with a sign company or an electrical licensee, and applicants who qualify by experience don&rsquo;t sit the exam.',
		),
		array(
			'q' => 'Who runs a new circuit to my sign in Arkansas?',
			'a' => 'A master or journeyman electrician. The sign electrician license covers connecting the sign to an approved outlet of sufficient capacity within 25 feet, but the rule doesn&rsquo;t extend to running a new branch circuit. On Little Rock&rsquo;s permit application you mark whether the sign needs a new circuit, an existing one or no power.',
		),
		array(
			'q' => 'How big can a banner be in Little Rock?',
			'a' => 'Up to 5 feet high and 20 feet wide, under a special event sign permit. Each permit covers a six-week period at $20 per banner, and a business can use at most four periods in a calendar year. Banners can&rsquo;t be attached to trees, landscaping, screen walls or fences.',
		),
		array(
			'q' => 'When does an Arkansas sign job need a licensed contractor?',
			'a' => 'When a commercial job costs $50,000 or more, counting labor and materials. The Contractors Licensing Board then requires a licensed commercial contractor, and splitting the work into smaller contracts to get under the threshold isn&rsquo;t allowed. Its Electrical classification, which covers electrical signs, requires at least one full-time master electrician.',
		),
		array(
			'q' => 'How long does shipping to Arkansas take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'17 CAR Part 210 (board rules, NEC 2026) and the Commercial Contractors Licensing Law (revised April 2026) read directly 2026-11-20; the labor.arkansas.gov web page returns 403 here and was read from the Internet Archive copy of 2026-11-03.',
		'Little Rock fees ($50 per sign, $20 per banner per six-week period): re-check yearly.',
		'Little Rock window-sign, A-frame and wall-sign size rules (Code Chapter 36, Article X, on Municode) not verified: Municode blocks the research PC. Not shown.',
		'Little Rock historic district guidelines file is dated 07/09/13; confirm there is no newer edition.',
		'Transit days to Arkansas not shown until verified.',
	),
);
