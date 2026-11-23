<?php
/**
 * Alaska state guide (/locations/alaska/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ak_faq = 'https://dol.alaska.gov/lss/mi-faq.html';
$ak_trades = 'https://www.commerce.alaska.gov/web/Portals/5/pub/con4818_specialty%20contractor%20trades%20list.pdf';
$anc_ch12 = 'https://library.municode.com/ak/anchorage/codes/code_of_ordinances';
$anc_signs = 'https://www.muni.org/Departments/OCPD/Planning/zoning/Pages/Signs.aspx';

return array(
	'slug' => 'alaska',
	'name' => 'Alaska',
	'abbr' => 'AK',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Alaska',
	'neighbors' => array(),
	'seo_title' => 'Channel Letters Shipped to Alaska | Anchorage Sign Rules',
	'seo_description' => 'Channel letters shipped to Alaska: electrical administrators, sign contractor limits, and Anchorage window, banner and A-frame rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Alaska',
	'intro' => array(
		'Alaska businesses can order custom LED channel letters online, built in Washington and California and shipped north ready for an Alaska electrical contractor to connect. Banners and window graphics are printed in Renton, WA.',
		'Alaska splits the job between two agencies. The Department of Labor and Workforce Development issues electricians&rsquo; certificates of fitness, while the Department of Commerce licenses electrical administrators and registers contractors, including a separate <strong>sign contractor</strong> trade that can install electric signs but not the wiring that feeds them.',
		'In Anchorage, window posters can cover up to half a window, banners can go up for 30 days at a time without a sign permit, and portable displays, A-frames included, are banned everywhere in the Municipality.',
	),
	'facts' => array(
		'Electricians' => 'Certificate of fitness from the Department of Labor (DOLWD)',
		'Contractors' => 'Registered with Commerce, with an electrical administrator',
		'Sign contractor' => 'Installs electric signs; no NEC wiring',
		'Anchorage A-frames' => 'Prohibited (AMC 21.12.080)',
	),
	'categories' => array(
		'channel-letters' => 'Anchorage requires electric signs to be installed, repaired and serviced only by a contractor licensed for that work, so line up the installer before the letters arrive.',
		'window-graphics' => 'Anchorage needs no sign permit for window posters and applications, but together they can&rsquo;t cover more than 50 percent of the window area.',
		'banners' => 'An Anchorage banner can go up without a sign permit for 30 consecutive days, up to 120 days a year, with notice filed with land use enforcement each time.',
		'flags-aframes' => 'Anchorage prohibits portable signs everywhere in the Municipality, including sidewalk A-frames, so plan on window graphics or a banner there instead.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install and Wire a Lit Sign in Alaska',
		'body' => array(
			'The DOLWD Mechanical Inspection Section issues electrician certificates of fitness in five categories: journeyman, lineman, residential wireman, trainee and provisional. Alaska has no master electrician license; the journeyman certificate covers work under the code. Out-of-state electricians can get a one-year provisional certificate, and since August 8, 2025 the state accepts applications to reciprocate journeyman certificates with other states.',
			'The business side sits with the Department of Commerce. Any contractor doing work covered by the National Electrical Code must have at least one employee who is a licensed <strong>electrical administrator</strong>, a supervisory license for sign-off that doesn&rsquo;t itself allow the holder to do the work, and one administrator can&rsquo;t serve more than one registered contractor (AS 08.40.130). Administrator categories cover commercial wiring, residential wiring, line work, communications and controls; none is specific to signs.',
			'Contractor registration under AS 08.18 lists a separate <strong>sign contractor</strong> trade (12 AAC 21.490). A sign contractor fabricates and installs signs, &ldquo;including those electrically operated,&rdquo; but doesn&rsquo;t perform any work covered by the National Electrical Code. So a sign company can hang your letters, and a registered electrical contractor, with an administrator and certificate-holding electricians, makes the electrical connection.',
			'One exception matters off the road system: electrical work of $5,000 or less at homes or small commercial establishments in communities under 500 people, or more than 50 miles by air or water from an administrator&rsquo;s place of business, falls outside the administrator law (AS 08.40.190).',
		),
		'table' => array(
			'Electricians' => 'Journeyman, lineman, residential wireman, trainee or provisional certificate',
			'Contractor' => 'Registered under AS 08.18, with an electrical administrator',
			'Sign contractor (12 AAC 21.490)' => 'Fabricates and installs electric signs; no NEC work',
			'Small remote jobs' => '$5,000 or less in small or remote communities (AS 08.40.190)',
		),
		'sources' => array(
			array('Alaska DOLWD: Mechanical Inspection FAQ', $ak_faq),
			array('Alaska Department of Commerce: Electrical administrators', 'https://www.commerce.alaska.gov/web/cbpl/ProfessionalLicensing/ElectricalAdministrators'),
			array('Alaska Department of Commerce: Electrical administrator statutes and regulations (PDF)', 'https://www.commerce.alaska.gov/web/portals/5/pub/ElectricalStatutes.pdf'),
			array('Alaska Department of Commerce: Specialty contractor trades list (PDF)', $ak_trades),
		),
	),
	'permits' => array(
		'heading' => 'Inspections and Sign Permits in Alaska',
		'body' => array(
			'State electrical inspectors inspect new and altered electrical systems on all commercial structures and on dwellings of three units or more, so a new sign circuit at a storefront is subject to inspection.',
			'In <strong>Anchorage</strong>, the largest city, a permit from the Municipality is generally required to erect a sign, and signs are governed by chapter 21.12 of the municipal code. Electric signs, and any permanent sign involving structural requirements of the building code, may be installed, repaired, altered and serviced only by a contractor licensed for that work. In most commercial and industrial districts, wall signs are allowed up to 2 square feet per lineal foot of primary frontage (1.2 square feet downtown), and projecting signs need 8 feet of clearance underneath.',
			'Anchorage lighting rules are specific: an internally lit sign needs an opaque background with translucent letters, or a darker translucent background with lighter letters, though registered trademarks and logos are exempt. LED signs are limited to 5,000 candelas per square meter in daylight and 800 at other times, and flashing or animated signs, roof signs and off-premise billboards are prohibited.',
			'Statewide, Alaska law prohibits outdoor advertising except listed types (AS 19.25.090), and one of the exceptions is signs advertising activities conducted on the property where they stand, so on-premise business signs are allowed.',
		),
		'table' => array(
			'Electrical inspection' => 'State inspectors, all commercial structures',
			'Anchorage sign rules' => 'AMC chapter 21.12; permit generally required',
			'Anchorage installer' => 'Contractor licensed for electric signs',
			'Anchorage wall signs' => '2 sq ft per foot of primary frontage (1.2 downtown)',
			'Anchorage LED signs' => '5,000 cd/m&sup2; by day, 800 at other times',
		),
		'sources' => array(
			array('Alaska DOLWD: Plumbing and electrical inspection', 'https://dol.alaska.gov/lss/plumbing_electrical.htm'),
			array('Municipality of Anchorage: Signs', $anc_signs),
			array('Anchorage Municipal Code, Title 21, chapter 21.12: Signs', $anc_ch12),
			array('Alaska Statutes 19.25.075&ndash;19.25.180: Outdoor advertising', 'https://www.akleg.gov/basis/statutes.asp?media=print&secStart=19.25.075&secEnd=19.25.180'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Temporary Displays in Anchorage',
		'body' => array(
			'A banner isn&rsquo;t treated as a sign for permit purposes in Anchorage if it&rsquo;s kept in as-new condition, stays out of the public right-of-way, and notice is filed with the land use enforcement division at the start of each display period. Each display can run 30 consecutive days, up to 120 days a year.',
			'Other temporary signs at nonresidential uses can be up to 100 square feet (32 square feet if rigid), two per principal use, for up to 120 display days a year, and they can&rsquo;t be lit. Temporary signs of 6 square feet or less are exempt. Portable signs, including sidewalk A-frames, are prohibited everywhere in the Municipality.',
		),
		'sources' => array(
			array('Anchorage Municipal Code &sect; 21.12.070J: Temporary signs', $anc_ch12),
			array('Municipality of Anchorage: Signs', $anc_signs),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Anchorage',
		'body' => array(
			'Posters and other window applications on a window or door, or placed just inside to be read from outside, don&rsquo;t need a sign permit, but their combined area can&rsquo;t exceed <strong>50 percent of the window area</strong>. The only exception is a building under construction and not yet occupied, where windows may be fully covered. Half a window leaves room for hours, a logo and a seasonal promotion, but not a full wrap.',
		),
		'sources' => array(
			array('Anchorage Municipal Code &sect; 21.12.070J.3: Window applications', $anc_ch12),
		),
	),
	'climate' => array(
		'Alaska&rsquo;s climate depends on the region: Anchorage and Southcentral get long, dark winters, heavy snow and freeze-thaw cycles, Fairbanks and the Interior see some of the coldest temperatures in the country, and Southeast towns like Juneau and Ketchikan take near-constant rain. Lit letters run many more hours in the dark months, so choose power supplies rated for extreme cold, and mount letters clear of snow shedding off roofs.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Alaska Storefronts',
		'lead' => 'Lit letters for long winter evenings, plus window graphics and banners that fit Anchorage rules.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters that stay readable through dark winter afternoons.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for the darkest months.',
			'adhesive-window-perf' => 'Window graphics within Anchorage&rsquo;s 50 percent limit.',
			'13oz-vinyl-banner' => 'Seasonal banners for 30-day display periods.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Alaska?',
			'a' => 'We ship custom channel letters anywhere in Alaska, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a registered Alaska contractor to mount them and an electrical contractor, with an electrical administrator, to connect them.',
		),
		array(
			'q' => 'Can a sign contractor wire my sign in Alaska?',
			'a' => 'No. A registered sign contractor fabricates and installs signs, including electrically operated ones, but doesn&rsquo;t perform work covered by the National Electrical Code. The connection goes to a registered electrical contractor that employs a licensed electrical administrator and electricians holding Department of Labor certificates of fitness, such as a journeyman certificate.',
		),
		array(
			'q' => 'Does Alaska have a master electrician license?',
			'a' => 'No. The Department of Labor issues certificates of fitness for journeymen, linemen, residential wiremen, trainees and provisional electricians, and the journeyman certificate covers code work. Supervision and sign-off come from an electrical administrator license issued by the Department of Commerce, and every contractor doing code work needs at least one administrator on staff.',
		),
		array(
			'q' => 'Can I put an A-frame outside my Anchorage store?',
			'a' => 'No. Anchorage prohibits portable signs everywhere in the Municipality, including A-frames on streets and sidewalks. Window posters and applications are allowed up to 50 percent of the window area without a sign permit, and a banner can go up for 30 consecutive days, 120 days a year, once you file notice with land use enforcement.',
		),
		array(
			'q' => 'How long does shipping to Alaska take?',
			'a' => 'Alaska is outside the standard lower-48 transit times. Checkout shows the shipping options and estimated delivery date for your address, including production.',
		),
	),
	'review_notes' => array(
		'Shipping FAQ intentionally differs from other states; confirm carrier options and transit to Alaska before going live.',
		'DOLWD FAQ and electrical page, the electrical administrator statutes PDF (rev. 04/16/2023) and the specialty contractor trades list (rev. 09/03/2025) read directly 2026-11-20; the Commerce electrical administrator page was read from the Internet Archive copy of 2026-11-12 (403 directly). Closes the old AS 08.18 note.',
		'Anchorage chapter 21.12 facts were read from the archive copy (2025-11-08) of the muni.org Chapter 12 PDF (text dated Feb. 15, 2022). That PDF now returns 404 although the city&rsquo;s Signs page (archive 2026-07-13) still links it, so the page cites AMC 21.12 on Municode instead; Municode blocks this PC. ONE CHECK: open AMC 21.12 on Municode from another network and confirm the 50% window, banner (30/120 days), temporary sign, wall sign and LED numbers. Anchorage permit application steps (AMC 21.03.170, Title 23) not read.',
		'Whether the state or the Municipality inspects electrical work inside Anchorage was not confirmed.',
		'Transit days to Alaska not shown until verified.',
	),
);
