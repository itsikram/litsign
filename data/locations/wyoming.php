<?php
/**
 * Wyoming state guide (/locations/wyoming/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'wyoming',
	'name' => 'Wyoming',
	'abbr' => 'WY',
	'status' => 'review',
	'neighbors' => array('montana', 'south-dakota', 'nebraska', 'colorado', 'utah', 'idaho'),
	'seo_title' => 'Storefront Signs Shipped to Wyoming | Electrician Guide',
	'seo_description' => 'Channel letters shipped to Wyoming, plus who can connect them under the Department of Fire Prevention and Electrical Safety and where sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Wyoming',
	'intro' => array(
		'Wyoming businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Wyoming electrician.',
		'Electrical licensing in Wyoming runs through the Department of Fire Prevention and Electrical Safety in Cheyenne, which licenses master and journeyman electricians, electrical contractors, and low voltage and limited licensees.',
		'Wyoming&rsquo;s towns are spread out and many sit on highways, so check both your city&rsquo;s sign rules and, near a state highway, any WYDOT requirements before you order.',
	),
	'facts' => array(
		'Licensing agency' => 'Wyoming Department of Fire Prevention and Electrical Safety',
		'Licenses' => 'Master, Journeyman, Electrical Contractor, Low Voltage/Limited',
		'Office' => 'Cheyenne, 320 West 25th St.',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Wyoming',
		'body' => array(
			'The <strong>Department of Fire Prevention and Electrical Safety</strong> licenses <strong>Master</strong> and <strong>Journeyman</strong> electricians, <strong>Electrical Contractors</strong>, and <strong>Low Voltage/Limited</strong> licensees, and apprentices must start the licensing process within 10 days of starting work.',
			'Hire a licensed electrical contractor to connect your sign, and check the license with the department before work begins.',
		),
		'table' => array(
			'Licensing agency' => 'Department of Fire Prevention and Electrical Safety',
			'Individuals' => 'Master and Journeyman electricians',
			'Businesses' => 'Electrical Contractor; Low Voltage/Limited contractors',
		),
		'sources' => array(
			array('Wyoming Department of Fire Prevention and Electrical Safety: Electrical licensing', 'https://wsfm.wyo.gov/electrical-safety/licensing'),
			array('Wyoming Department of Fire Prevention and Electrical Safety: Personnel and inspectors', 'https://wsfm.wyo.gov/electrical-safety/personnel-and-inspectors'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Wyoming',
		'body' => array(
			'Wyoming cities and counties issue sign permits under their own codes. In Cheyenne, permits go through the Building Department&rsquo;s online license and permit portal, which needs an account. A lit sign also needs an electrical permit and inspection from the city or the state.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Cheyenne' => 'Building Department online portal',
			'Near state highways' => 'Check WYDOT requirements',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Wyoming is known for its wind, and winters bring deep cold and blowing snow. Ask your installer to plan anchoring for high wind and to seal every wall penetration.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Wyoming Storefronts',
		'lead' => 'Lit letters for highway towns and main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters that read from the highway in Wyoming.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for long winter nights.',
			'18oz-blockout-banner' => 'Heavy banners built for wind in Wyoming.',
			'deluxe-signicade-graphic-frame' => 'Weighted sidewalk A-frames for main streets.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Wyoming?',
			'a' => 'We ship custom channel letters anywhere in Wyoming from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Wyoming electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'Who can wire a lit sign in Wyoming?',
			'a' => 'A licensed electrical contractor with master or journeyman electricians licensed by the Department of Fire Prevention and Electrical Safety.',
		),
		array(
			'q' => 'Where do I get a sign permit in Wyoming?',
			'a' => 'From your city or county. In Cheyenne, permits go through the Building Department&rsquo;s online portal.',
		),
		array(
			'q' => 'How long does shipping to Wyoming take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Confirm that Cheyenne sign permits go through the Building Department portal (the portal page covers permits generally).',
		'The WYDOT line is general; confirm or remove.',
		'Transit days to Wyoming not shown until verified.',
	),
);
