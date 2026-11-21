<?php
/**
 * Kansas state guide (/locations/kansas/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ks_1526 = 'https://ksrevisor.gov/statutes/chapters/ch12/012_015_0026.html';
$ict_signs = 'https://www.sedgwickcounty.org/media/40123/50-439-sign-code.pdf';

return array(
	'slug' => 'kansas',
	'name' => 'Kansas',
	'abbr' => 'KS',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Kansas',
	'neighbors' => array('nebraska', 'missouri', 'oklahoma', 'colorado'),
	'seo_title' => 'Channel Letters Shipped to Kansas | Wichita Sign Permits',
	'seo_description' => 'Channel letters shipped to Kansas: city-issued electrician licenses under K.S.A. 12-1526, MABCD permits and Wichita banner limits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Kansas',
	'intro' => array(
		'Kansas businesses can order custom LED channel letters online, built in Washington and California and shipped ready for an electrician licensed by their city or county. Banners and window graphics are printed in Renton, WA.',
		'Kansas has no statewide electrician license. Instead, K.S.A. 12-1525 through 12-1528 set the ground rules for the counties and cities that choose to license electricians: standard national exams, a 75 percent passing score, continuing education and minimum field experience.',
		'The benefit for a business owner is portability. A certificate earned on one of the designated exams, with the required experience, is valid in any other Kansas county or city that licenses electricians, without a new test. In Wichita and the rest of Sedgwick County, the Metropolitan Area Building and Construction Department (MABCD) issues those certificates along with electrical permits.',
	),
	'facts' => array(
		'State electrical license' => 'None; counties and cities license (K.S.A. 12-1525 to 12-1528)',
		'Standard exams' => 'ICC, IAPMO or Prometric, 75% to pass',
		'Wichita / Sedgwick County' => 'MABCD permits, inspections and trade certificates',
		'Wichita window graphics' => 'Exempt from the sign code',
	),
	'categories' => array(
		'channel-letters' => 'Wichita voids a permanent sign permit if work hasn&rsquo;t started within 180 days or stops for 120, so time your order to the permit rather than the other way around.',
		'window-graphics' => 'Wichita&rsquo;s sign code exempts window displays and on-site signs painted on or attached to windows, so storefront window graphics need no sign permit there.',
		'banners' => 'A Wichita banner is limited to one per business, 64 square feet in total and 30 days, and only during a special event on the property.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Kansas',
		'body' => array(
			'Kansas doesn&rsquo;t license electricians at the state level. K.S.A. 12-1525 designates the standard examinations, from the International Code Council, IAPMO or Prometric, for electrical contractors and for master, journeyman and residential electricians. K.S.A. 12-1526 then tells any county or city that licenses electricians how to use them: no added questions, a 75 percent passing score, and 12 hours of continuing education every two years, at least half of it on the electrical code.',
			'Experience minimums are in the same section. A journeyman or residential certificate takes two years of field experience, of which trade school can count for up to one year, and a master certificate takes two years as a certified journeyman. A certificate earned this way is valid proof of competency in every other Kansas county or city that licenses electricians, with no new exam. A city may still give a different test, but a license earned that way is good only in that city or county (K.S.A. 12-1527).',
			'Cities can&rsquo;t stack extra licensing requirements on top: the statute annotations cite a 1999 Attorney General opinion holding that home rule doesn&rsquo;t allow it. What cities and counties do control is the electrical code they adopt, their inspections and their fees, so the permit for your sign circuit comes from the local building department.',
			'In <strong>Wichita and Sedgwick County</strong>, MABCD&rsquo;s Electrical and Alarm Division issues electrical permits, performs inspections, and issues master and journeyman trade certificates and trade contractor licenses. Certificates run on a two-year cycle, all expiring December 31 of odd-numbered years, and renewal takes 12 hours of continuing education, 6 of them on the code.',
		),
		'table' => array(
			'State license' => 'None statewide',
			'Designated exams' => 'ICC, IAPMO or Prometric (K.S.A. 12-1525)',
			'Journeyman or residential' => 'Two years of field experience',
			'Master' => 'Two years as a certified journeyman',
			'Portability' => 'Valid in every Kansas county or city that licenses, no re-test',
		),
		'sources' => array(
			array('K.S.A. 12-1525: Designated examinations', 'https://ksrevisor.gov/statutes/chapters/ch12/012_015_0025.html'),
			array('K.S.A. 12-1526: Licensure standards for counties and cities', $ks_1526),
			array('K.S.A. 12-1527: Other examinations, local codes and inspections', 'https://ksrevisor.gov/statutes/chapters/ch12/012_015_0027.html'),
			array('MABCD: Electrical and alarm division', 'https://www.sedgwickcounty.org/mabcd/inspection-divisions/electrical-alarm-division/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Wichita and Other Kansas Cities',
		'body' => array(
			'Kansas cities and counties write their own sign codes, so Wichita, Overland Park, Kansas City, Kansas, Topeka and Olathe each set different limits on size, height and lighting.',
			'Wichita&rsquo;s sign rules are in Title 24 of the City Code. As amended by Ordinance 50-439 in 2017, the code makes it unlawful to put up, relocate, alter or paint a sign without a permit unless the sign is exempt. The application includes drawings of the sign&rsquo;s location, dimensions, height, construction and structural design, and the director may ask for an engineer. A permanent sign permit is void if work doesn&rsquo;t begin within 180 days or is suspended for 120 days.',
			'The same ordinance limits who may do the work: only licensed and bonded sign hangers may install a sign that needs a permit, and sign hanging businesses hold an annual city license. MABCD&rsquo;s sign permit application asks for the contractor&rsquo;s license number and a scaled site plan, while wiring for the letters is permitted and inspected through MABCD&rsquo;s electrical division.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Wichita sign rules' => 'City Code Title 24 (Ordinance 50-439, 2017)',
			'Wichita installer' => 'Licensed and bonded sign hanger',
			'Wichita permit life' => 'Void if not started in 180 days or stopped for 120',
			'Electrical' => 'MABCD permit and inspection',
		),
		'sources' => array(
			array('City of Wichita: Ordinance No. 50-439, sign code amendments (PDF)', $ict_signs),
			array('MABCD: Sign permit application (PDF)', 'https://www.sedgwickcounty.org/media/6810/sign-permit-application.pdf'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Event Displays in Wichita and Overland Park',
		'body' => array(
			'Wichita allows one temporary banner per business or location, no larger than 64 square feet in total, displayed for no more than 30 days and only during a special event on that property, and it requires a temporary sign permit. Other temporary signs may stay up for no more than 90 days.',
			'In <strong>Overland Park</strong>, banners bigger or longer-lasting than the temporary sign rules allow need a Temporary Event Signage permit. Each permit lasts no more than ten days, an organization can get at most four in a calendar year, pennants and strings of triangle flags are banned, and everything stays on private property, out of the right-of-way.',
		),
		'sources' => array(
			array('City of Wichita: Ordinance No. 50-439 (temporary signs and banners)', $ict_signs),
			array('City of Overland Park: Special event permits', 'https://www.opkansas.org/city-services/permits-licenses-inspections-directory/event-permits-inspections/'),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Wichita',
		'body' => array(
			'Wichita&rsquo;s sign code doesn&rsquo;t apply to window displays or to on-site signs placed in or on a building&rsquo;s windows, whether painted or attached. That makes perforated window film, cut vinyl store hours and promotional window graphics the simplest way to add storefront messaging in Wichita without a sign permit. Your lease or the center&rsquo;s sign criteria can still limit how much glass you cover.',
		),
		'sources' => array(
			array('City of Wichita: Ordinance No. 50-439, exemptions (&sect; 24.04.040)', $ict_signs),
		),
	),
	'climate' => array(
		'Kansas weather is built around wind. Gusty south winds sweep the plains for much of the year, spring and early summer bring hail and tornado outbreaks, and long summer heat gives way to sharp winter cold snaps and ice. Ask your installer to use every mounting stud on each letter, fasten raceways into solid backing rather than siding alone, and check the letters after a major hailstorm.',
	),
	'popular' => array(
		'heading' => 'Popular Picks for Kansas Storefronts',
		'lead' => 'Lit letters for Wichita, Johnson County and main-street storefronts, plus heavy banners for prairie wind.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters that read from Wichita&rsquo;s arterial roads.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for a bold nighttime look.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-35 and I-70.',
			'18oz-blockout-banner' => 'Heavy banners sized under Wichita&rsquo;s 64 square foot limit.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Kansas?',
			'a' => 'We ship custom channel letters anywhere in Kansas, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an electrician licensed by your city or county, and in Wichita a licensed sign hanger, to mount and connect them.',
		),
		array(
			'q' => 'Does Kansas have a state electrician license?',
			'a' => 'No. Kansas leaves electrician licensing to counties and cities. State law (K.S.A. 12-1525 to 12-1528) sets the rules those that license must follow: designated ICC, IAPMO or Prometric exams, a 75 percent passing score, 12 hours of continuing education every two years and minimum field experience. Your installer needs the license your city or county requires.',
		),
		array(
			'q' => 'Is a Kansas electrician license valid in another city?',
			'a' => 'Usually, yes. A certificate earned on one of the designated exams, with the required experience, is valid proof of competency in any other Kansas county or city that licenses electricians, without another exam. A license earned on a city&rsquo;s own different test is good only there, and every city still issues its own permits.',
		),
		array(
			'q' => 'Do window graphics need a permit in Wichita?',
			'a' => 'Not under the sign code. Wichita exempts window displays and on-site signs placed in or on windows, whether painted or attached. Banners are handled differently: one per business, up to 64 square feet in total and 30 days, only during a special event and with a temporary sign permit.',
		),
		array(
			'q' => 'Who installs a permitted sign in Wichita?',
			'a' => 'A licensed and bonded sign hanger. Wichita&rsquo;s sign code, as amended in 2017, allows only licensed and bonded sign hangers to install signs that need a permit, and sign hanging businesses renew a city license every year. Wiring for lit letters is permitted and inspected separately through MABCD.',
		),
		array(
			'q' => 'How long does shipping to Kansas take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'K.S.A. 12-1525 to 12-1528 read directly on ksrevisor.gov 2026-11-20 (12-1529 to 12-1539 are reserved). MABCD pages read from Internet Archive copies (sedgwick.gov times out here).',
		'Wichita sign rules come from Ordinance 50-439 (April 25, 2017; archive copy of 2025-07-16). Wichita changed its sign rules again in 2023 (political signs and billboards, per news reports), and current Chapter 24.04 on Municode can&rsquo;t be read from this PC: confirm the banner, permit-life, window and sign hanger rules are unchanged before going live.',
		'MABCD&rsquo;s contractor license form (Rev 4/22/2024) no longer lists a sign contractor license, so none is claimed.',
		'Overland Park page read from the archive copy of 2026-01-17 (the live site now shows a bot check); its base temporary sign limits were not verified.',
		'Transit days to Kansas not shown until verified.',
	),
);
