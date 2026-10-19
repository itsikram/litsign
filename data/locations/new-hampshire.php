<?php
/**
 * New Hampshire state guide (/locations/new-hampshire/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'new-hampshire',
	'name' => 'New Hampshire',
	'abbr' => 'NH',
	'status' => 'review',
	'neighbors' => array('maine', 'massachusetts', 'vermont'),
	'seo_title' => 'Storefront Signs Shipped to New Hampshire | Electrician Guide',
	'seo_description' => 'Channel letters shipped to New Hampshire businesses, plus who can connect them under the Electricians&rsquo; Board rules and where to get a sign permit.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to New Hampshire',
	'intro' => array(
		'New Hampshire businesses can order custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed New Hampshire electrician.',
		'The New Hampshire Electricians&rsquo; Board, part of the Office of Professional Licensure and Certification (OPLC), licenses master, journeyman and high/medium voltage electricians. The board licenses people who make electrical installations for heat, light and power, and it says the type of circuit, not its voltage, decides whether a license is needed.',
		'New Hampshire has no statewide sign permit. Your city or town zoning ordinance sets what size and kind of sign you may put up, and the local building office permits the work.',
	),
	'facts' => array(
		'Licensing agency' => 'NH Electricians&rsquo; Board (Office of Professional Licensure and Certification)',
		'Licenses' => 'Master, Journeyman, High/Medium Voltage',
		'What needs a license' => 'Electrical installations for heat, light and power',
		'Sign permit' => 'Your city or town',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in New Hampshire',
		'body' => array(
			'New Hampshire&rsquo;s Electricians&rsquo; Board licenses or registers the people who perform electrical installations for heat, light and power. It licenses three categories: <strong>Master</strong>, <strong>Journeyman</strong> and <strong>High/Medium Voltage</strong> electricians. Because it is the type of circuit, not the voltage, that decides licensing, connecting a lit sign to building power is licensed electrical work.',
			'Licensees take 15 hours of instruction every year on National Electrical Code changes and the state rules. Ask your installer for their license number and confirm it with OPLC before work starts.',
		),
		'table' => array(
			'Licensing agency' => 'Electricians&rsquo; Board, Office of Professional Licensure and Certification',
			'Licenses' => 'Master, Journeyman, High/Medium Voltage electrician',
			'Rule of thumb' => 'Circuit type, not voltage, decides licensing',
			'Continuing education' => '15 hours a year',
		),
		'sources' => array(
			array('NH OPLC: Electricians&rsquo; Board', 'https://oplc.nh.gov/electricians-board'),
			array('NH OPLC: Electricians&rsquo; Board license requirements', 'https://oplc.nh.gov/electricians-board-license-requirements'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in New Hampshire',
		'body' => array(
			'Each New Hampshire city and town adopts its own zoning ordinance, and the sign section usually caps sign area by building frontage and sets lighting limits. Some towns send signs to the planning board or a historic district commission as well, so start with your town&rsquo;s land use or building office.',
		),
		'table' => array(
			'Sign permit' => 'Your city or town building or land use office',
			'Electrical work' => 'Licensed New Hampshire electrician',
		),
		'sources' => array(),
	),
	'climate' => array(
		'New Hampshire winters are cold and snowy, with ice and freeze-thaw cycles. Mount letters above the snow line, and ask your installer to seal wall penetrations and route wiring so meltwater can&rsquo;t collect.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for New Hampshire Storefronts',
		'lead' => 'Lit letters for plazas and main streets, plus seasonal signs for tourist towns.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for shopping plazas along busy routes.',
			'hidden-back-halo-lit' => 'A subtle halo look for historic downtowns.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for main street shops.',
			'feather-angled-flag-pole' => 'Flags for seasonal businesses near the highway.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in New Hampshire?',
			'a' => 'We ship custom channel letters anywhere in New Hampshire from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a New Hampshire-licensed electrician to mount and connect it.',
		),
		array(
			'q' => 'Does New Hampshire have a sign electrician license?',
			'a' => 'No separate sign license is listed. The Electricians&rsquo; Board licenses master, journeyman and high/medium voltage electricians, and connecting a lit sign is licensed work.',
		),
		array(
			'q' => 'Where do I get a sign permit in New Hampshire?',
			'a' => 'From your city or town. Its zoning ordinance sets sign size and lighting limits, and some towns also send signs to the planning board or a historic district commission.',
		),
		array(
			'q' => 'How long does shipping to New Hampshire take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Add Manchester&rsquo;s sign permit process once confirmed on the city site.',
		'Confirm with OPLC that no limited sign license exists and that journeymen may connect a sign without a master on site.',
		'Transit days to New Hampshire not shown until verified.',
	),
);
