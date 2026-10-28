<?php
/**
 * Indiana state guide (/locations/indiana/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$indy_lic = 'https://www.indy.gov/activity/contractor-licenses';
$indy_sign = 'https://www.indy.gov/activity/signs-and-advertising-permits';

return array(
	'slug' => 'indiana',
	'name' => 'Indiana',
	'abbr' => 'IN',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Indiana',
	'neighbors' => array('michigan', 'ohio', 'kentucky', 'illinois'),
	'seo_title' => 'Channel Letters Shipped to Indiana | Indianapolis Permits',
	'seo_description' => 'Channel letters shipped to Indiana: no state electrician license, so cities like Indianapolis license contractors, plus Indy sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Indiana',
	'intro' => array(
		'Indiana businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a locally licensed installer. Banners and window graphics are printed in Renton, WA.',
		'Indiana leaves electrical licensing to local government. The state&rsquo;s own business guide says the only construction contractors licensed by the State of Indiana are plumbers, so each city or county decides whether, and how, it licenses electrical contractors.',
		'Indianapolis does: its Department of Business and Neighborhood Services (BNS) licenses electrical contractors and issues sign permits through an online portal, and it treats a banner fastened at all four corners as a wall sign.',
	),
	'facts' => array(
		'State electrical license' => 'None; licensing is a local option',
		'Indianapolis licensing' => 'BNS electrical contractor license (Code &sect; 875-201)',
		'Indianapolis sign permits' => 'BNS, online through the Citizen&rsquo;s Access Portal',
		'Indianapolis wall signs' => 'Within 18 in of the wall; up to 50% (max 4 ft) above it',
	),
	'categories' => array(
		'channel-letters' => 'Indianapolis counts a sign as a wall sign when it sits parallel to and within 18 inches of the wall, with no more than half of it, and never more than 4 feet, above the wall.',
		'banners' => 'In Indianapolis, a banner attached securely at all four corners is regulated as a wall sign, so it needs the same wall sign permit as permanent letters.',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Indiana',
		'body' => array(
			'There is no statewide electrician or electrical contractor license in Indiana. The state&rsquo;s business guide lists plumbers as the only construction contractors it licenses and calls other contractor licensing a local option, so the rules in Fort Wayne, Evansville or a small county seat can each differ.',
			'In <strong>Indianapolis</strong>, BNS licenses contractors working in the Consolidated City, except in Lawrence, Beech Grove, Speedway and Southport, which handle their own. City Code &sect; 875-201 requires an electrical contractor license, and getting one means passing an exam and first being approved by the Board of Electrical Examiners. A licensed company names the authorized agents who may pull permits in its name, so check that your installer&rsquo;s license and agent list are current before the permit is filed.',
		),
		'table' => array(
			'State license' => 'None; the state licenses only plumbing contractors',
			'Indianapolis' => 'BNS electrical contractor license; exam and board approval',
			'Not covered by BNS' => 'Lawrence, Beech Grove, Speedway, Southport',
			'Permits' => 'Pulled by the company&rsquo;s named authorized agents',
		),
		'sources' => array(
			array('IN.gov: Business owner&rsquo;s guide, licenses and permits', 'http://www.in.gov/core/bg_licenses.html'),
			array('City of Indianapolis: Contractor licenses', $indy_lic),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Indiana',
		'body' => array(
			'Sign permits in Indiana come from your city, town or county under its zoning ordinance.',
			'In <strong>Indianapolis</strong>, every sign application goes through BNS, online in the Citizen&rsquo;s Access Portal or dropped off at the BNS lobby. A wall sign application needs a scaled elevation of the building showing the sign, plus the property owner&rsquo;s signature or a letter of permission. Routine maintenance and copy changes that don&rsquo;t change the sign&rsquo;s size don&rsquo;t need a permit. BNS lists fees by sign type: a wall or incidental sign is $32 plus $142, an awning or canopy sign $32 plus $170, and a pole, pylon, roof or ground sign $32 plus $422.',
		),
		'table' => array(
			'Sign permit' => 'Your city, town or county',
			'Indianapolis' => 'BNS, online or at the BNS lobby',
			'Wall sign application' => 'Scaled elevation + property owner&rsquo;s signature or letter',
			'Indianapolis wall sign fee' => '$32 + $142',
			'No permit needed' => 'Maintenance and same-size copy changes',
		),
		'sources' => array(
			array('City of Indianapolis: Signs and advertising permits', $indy_sign),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners in Indianapolis',
		'body' => array(
			'Indianapolis doesn&rsquo;t treat every banner as temporary. A banner that is <strong>securely attached at all four corners</strong> is regulated as a <strong>wall sign</strong>, which means it goes through the same BNS wall sign permit, with the scaled elevation and the owner&rsquo;s permission, as permanent letters. If you plan to leave a vinyl banner up while your channel letters are being made, permit it like a wall sign from the start.',
		),
		'sources' => array(
			array('City of Indianapolis: Signs and advertising permits', $indy_sign),
		),
	),
	'climate' => array(
		'Indiana storefronts see lake-effect snow in the north, spring thunderstorms with damaging winds statewide, and humid summers in the south. Ask your installer to seal every wall penetration behind the letters and use hardware sized for gusts on open frontage roads.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Indiana Storefronts',
		'lead' => 'Lit letters for shopping centers and courthouse-square downtowns.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for shopping centers along busy roads in Indiana.',
			'standard-channel-letter-back-lit' => 'A soft glow for downtown square storefronts.',
			'feather-angled-flag-pole' => 'Flags for roadside visibility in Indiana.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Indiana.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Indiana?',
			'a' => 'We ship custom channel letters anywhere in Indiana, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a local electrician or sign contractor who meets your city&rsquo;s or county&rsquo;s rules.',
		),
		array(
			'q' => 'Does Indiana require a state electrician license?',
			'a' => 'No. The state&rsquo;s business guide says plumbers are the only construction contractors Indiana licenses, and leaves electrical licensing to local government. Indianapolis, for example, requires an electrical contractor license from BNS under City Code &sect; 875-201. Elsewhere, ask the local building department which license it requires.',
		),
		array(
			'q' => 'Do I need a permit to hang a banner in Indianapolis?',
			'a' => 'If it&rsquo;s attached at all four corners, yes: Indianapolis regulates a securely attached banner as a wall sign, so it goes through the BNS wall sign permit with a scaled elevation and the property owner&rsquo;s permission. Routine maintenance and same-size copy changes don&rsquo;t need a permit.',
		),
		array(
			'q' => 'What does an Indianapolis wall sign permit cost?',
			'a' => 'BNS lists a wall or incidental sign at $32 plus $142. Awning and canopy signs are $32 plus $170, and pole, pylon, roof or ground signs $32 plus $422. Fees change, so confirm the current amount when you apply online in the Citizen&rsquo;s Access Portal or at the BNS lobby.',
		),
		array(
			'q' => 'How long does shipping to Indiana take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'in.gov business guide read directly 2026-10-27.',
		'indy.gov contractor license and sign permit pages read in the browser pane 2026-10-27 (JavaScript site). Sign permit fees: re-check yearly.',
		'Old INDOT billboard line removed (not verified).',
		'Transit days to Indiana not shown until verified.',
	),
);
