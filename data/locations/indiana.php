<?php
/**
 * Indiana state guide (/locations/indiana/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'indiana',
	'name' => 'Indiana',
	'abbr' => 'IN',
	'status' => 'review',
	'neighbors' => array('michigan', 'ohio', 'kentucky', 'illinois'),
	'seo_title' => 'Storefront Signs Shipped to Indiana | Local License Guide',
	'seo_description' => 'Channel letters shipped to Indiana. Electricians are licensed by cities and counties, not the state, so check local rules before your sign goes up.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Indiana',
	'intro' => array(
		'Indiana businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a qualified local installer.',
		'Indiana doesn&rsquo;t license electricians at the state level. Cities and counties set their own requirements, so the rules in Indianapolis, Fort Wayne and smaller towns can all differ, and some places license electrical contractors while others don&rsquo;t.',
		'Start by calling the building department for your address: it can tell you which contractor license it requires, and whether your sign needs a separate sign permit and electrical permit.',
	),
	'facts' => array(
		'State electrical license' => 'None; set by cities and counties',
		'Indianapolis' => 'Department of Business and Neighborhood Services (BNS)',
		'Sign permit' => 'Your city or county',
		'First step' => 'Call your local building department',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Indiana',
		'body' => array(
			'Electrical licensing in Indiana is local. In <strong>Indianapolis</strong> and Marion County, the Department of Business and Neighborhood Services (BNS) licenses electrical contractors, and other cities and counties, such as Fort Wayne and Allen County, run their own programs.',
			'Ask your installer which local license they hold where your business is, and confirm it with that city or county before work starts.',
		),
		'table' => array(
			'State license' => 'None statewide',
			'Indianapolis' => 'BNS electrical contractor license',
			'Elsewhere' => 'Your city or county building department',
		),
		'sources' => array(
			array('City of Indianapolis: Department of Business and Neighborhood Services', 'https://www.indy.gov/agency/department-of-business-and-neighborhood-services'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Indiana',
		'body' => array(
			'Sign permits in Indiana come from your city or county under its zoning ordinance. Off-premise billboards along state highways also need state approval from INDOT, but a sign on your own building is handled locally.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Billboards on state highways' => 'INDOT outdoor advertising permit',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Indiana gets cold, snowy winters and stormy springs with strong winds. Ask your installer about sealing wall penetrations and anchoring for wind.',
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
			'a' => 'We ship custom channel letters anywhere in Indiana from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a local electrician or sign contractor who meets your city&rsquo;s or county&rsquo;s rules.',
		),
		array(
			'q' => 'Does Indiana require a state electrician license?',
			'a' => 'No. Indiana leaves electrical licensing to cities and counties. Indianapolis, for example, licenses electrical contractors through BNS.',
		),
		array(
			'q' => 'Where do I get a sign permit in Indiana?',
			'a' => 'From your city or county building or planning department, under its zoning ordinance.',
		),
		array(
			'q' => 'How long does shipping to Indiana take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'"No statewide electrical license" is from secondary sources; confirm with the Indiana Professional Licensing Agency.',
		'Indianapolis sign rules (Chapter 744 per a vendor page) were not confirmed on indy.gov.',
		'The INDOT billboard line is from the state outdoor advertising form; confirm wording.',
		'Transit days to Indiana not shown until verified.',
	),
);
