<?php
/**
 * Colorado state guide (/locations/colorado/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$crs_110 = 'https://colorado.public.law/statutes/crs_12-115-110';
$den_cpd = 'https://www.denvergov.org/Government/Agencies-Departments-Offices/Agencies-Departments-Offices-Directory/Community-Planning-and-Development/Plan-Review-Permits-and-Inspections/Zoning-Permits/Signs';
$den_apply = $den_cpd . '/Apply-for-Sign-Permit';

return array(
	'slug' => 'colorado',
	'name' => 'Colorado',
	'abbr' => 'CO',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Colorado',
	'neighbors' => array('wyoming', 'nebraska', 'kansas', 'oklahoma', 'new-mexico', 'utah'),
	'seo_title' => 'Channel Letters Shipped to Colorado | Denver Sign Permits',
	'seo_description' => 'Channel letters shipped to Colorado: State Electrical Board licenses, contractors run by a master electrician, and Denver sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Colorado',
	'intro' => array(
		'Colorado businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a registered Colorado electrical contractor. Banners and window graphics are printed in Renton, WA.',
		'Colorado licenses electricians statewide through the State Electrical Board, at the master, journeyman and residential wireman levels, and every electrical contracting business must register with the board and have a licensed master electrician supervising all of its electrical work.',
		'In Denver, most signs need a zoning permit and a building permit with structural plans stamped by a Colorado-registered structural engineer, plus an electrical permit for anything lit. The building permit must follow within 180 days of the zoning permit.',
	),
	'facts' => array(
		'Licensing agency' => 'Colorado State Electrical Board',
		'Individual licenses' => 'Master, journeyman, residential wireman',
		'Contractors' => 'Registered with the board; supervised by a master electrician',
		'Denver lit sign' => 'Zoning + building + electrical permit',
	),
	'categories' => array(
		'channel-letters' => 'Denver&rsquo;s building permit for a sign needs structural plans stamped by a Colorado-registered professional structural engineer, covering the supports and how the letters anchor to the wall.',
		'window-graphics' => 'Denver lets a business put up unlit window signs and window graphics promoting a sale or event for up to 45 days without a permit, as long as zoning rules are followed.',
		'banners' => 'In Denver, a banner with commercial copy for a temporary event or sale can stay up to 45 days without a permit, while inflatables, balloons and pennants need one.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Colorado',
		'body' => array(
			'Under C.R.S. &sect; 12-115-110, the <strong>State Electrical Board</strong> licenses three levels of electrician. A <strong>journeyman</strong> shows four years of apprenticeship or equivalent experience, at least two of them in commercial, industrial or similar work, plus 288 hours of training in safety and the National Electrical Code. A <strong>master</strong> adds supervisory and layout experience, or qualifies through an engineering degree or a trade-school route, and a <strong>residential wireman</strong> is limited to one- to four-family dwellings, so a storefront calls for a journeyman or master.',
			'Any business acting as an <strong>electrical contractor</strong> must register with the board and show workers&rsquo; and unemployment compensation compliance. If an owner holds a master license and supervises the work, that person can be the registered master; otherwise the contractor must employ at least one licensed master electrician to supervise all electrical work, and a master can qualify only one contractor at a time.',
		),
		'table' => array(
			'Licensing agency' => 'Colorado State Electrical Board, Division of Professions and Occupations',
			'Journeyman' => '4 years&rsquo; experience (2 commercial or industrial) + 288 training hours',
			'Residential wireman' => 'One- to four-family dwellings',
			'Contractor' => 'Board registration; a licensed master supervises all electrical work',
		),
		'sources' => array(
			array('C.R.S. &sect; 12-115-110: License requirements (Colorado Revised Statutes)', $crs_110),
			array('Colorado State Electrical Board: Applications and forms', 'https://dpo.colorado.gov/Electrical/Applications'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Colorado',
		'body' => array(
			'Colorado cities and counties issue sign permits under their own codes, and Front Range suburbs and mountain towns each set their own size and lighting limits.',
			'In <strong>Denver</strong>, Community Planning and Development requires a zoning permit for most signs on private property. The application includes a site plan with dimensions from the sign to the zone lot lines and a fully dimensioned elevation showing the sign on the facade and its height above grade. A building permit is also required for most signs unless the Denver Building and Fire Code exempts them, with structural plans for the framing, supports, connections and anchorage that are prepared, stamped and signed by a Colorado-registered professional structural engineer.',
			'Applications go through Denver&rsquo;s e-permits system; choosing a building log gets the zoning and building reviews done together. If the building permit isn&rsquo;t obtained within 180 days of the zoning permit, the zoning permit has to be applied for again. A sign with electrical components needs an electrical permit, one that crosses into the public right-of-way needs a Department of Transportation and Infrastructure permit, and a sign on a landmark or in a historic district goes to Landmark Preservation for a Certificate of Appropriateness.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Denver zoning' => 'Zoning permit with site plan and dimensioned elevation',
			'Denver building' => 'Structural plans by a Colorado-registered structural engineer',
			'Denver timing' => 'Building permit within 180 days of the zoning permit',
			'Denver lit signs' => 'Electrical permit as well',
		),
		'sources' => array(
			array('City and County of Denver: Apply for a sign permit', $den_apply),
			array('City and County of Denver: Signs', $den_cpd),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs and Banners in Denver',
		'body' => array(
			'Denver allows a temporary commercial sign promoting an event, sale or service of the business on the same lot for up to <strong>45 days without a permit</strong>. That covers an unlit window sign, a wall sign or poster, window graphics, and banners with commercial copy; if a temporary window sign will be lit, it needs an electrical permit. Inflatables, balloons, streamers and pennants for a special event do need a permit, and every temporary sign still has to follow Section 10.10.3 of the Denver Zoning Code.',
		),
		'sources' => array(
			array('City and County of Denver: Signs (temporary signs)', $den_cpd),
		),
	),
	'climate' => array(
		'Colorado&rsquo;s high-altitude sun is intense, Front Range summers bring hailstorms, and downslope chinook winds can gust hard along the foothills. Choose UV-stable face colors, and ask your installer to anchor letters for wind and snow load.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Colorado Storefronts',
		'lead' => 'Lit letters for Front Range shopping centers and mountain-town main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Front Range shopping centers.',
			'hidden-back-halo-lit' => 'A refined halo look for Denver offices and boutiques.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for mountain-town foot traffic.',
			'18oz-blockout-banner' => 'Heavy banners for wind and weather in Colorado.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Colorado?',
			'a' => 'We ship custom channel letters anywhere in Colorado, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a registered Colorado electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign electrician need in Colorado?',
			'a' => 'A State Electrical Board license, journeyman or master for commercial work, since a residential wireman is limited to one- to four-family homes. The company must be a registered electrical contractor with a licensed master electrician supervising all of its electrical work.',
		),
		array(
			'q' => 'Does Denver require an engineer for a sign permit?',
			'a' => 'For signs that need a building permit, which is most of them, yes: the structural plans for the framing, supports and anchorage must be prepared, stamped and signed by a Colorado-registered professional structural engineer. Include any drawings or specifications from the sign manufacturer with them.',
		),
		array(
			'q' => 'Can I put up a grand opening banner in Denver without a permit?',
			'a' => 'Yes, for up to 45 days, if it promotes a temporary event, sale or service of the business on the same lot and follows Section 10.10.3 of the Denver Zoning Code. Inflatables, balloons and pennants for the event need a permit, and a lit window sign needs an electrical permit.',
		),
		array(
			'q' => 'How long does shipping to Colorado take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Electrical license levels and contractor registration read 2026-10-27 from C.R.S. &sect; 12-115-110 on colorado.public.law (an unofficial copy of the statutes); dpo.colorado.gov returns 403 to the research PC and its archive copy failed to load. Confirm against the official statute or the board before approval.',
		'Denver sign permit pages read from Internet Archive copies of 2026-06-08 (apply page) and 2026-04-18 (signs page); denvergov.org blocks the research PC. Re-check yearly.',
		'Whether Denver licenses or registers electrical contractors itself, beyond the state registration, was not confirmed.',
		'Transit days to Colorado not shown until verified.',
	),
);
