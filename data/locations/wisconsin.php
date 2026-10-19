<?php
/**
 * Wisconsin state guide (/locations/wisconsin/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'wisconsin',
	'name' => 'Wisconsin',
	'abbr' => 'WI',
	'status' => 'review',
	'neighbors' => array('michigan', 'minnesota', 'iowa', 'illinois'),
	'seo_title' => 'Storefront Signs Shipped to Wisconsin | DSPS License Guide',
	'seo_description' => 'Channel letters shipped to Wisconsin, plus who can wire them (DSPS-licensed electrical contractors and electricians) and where local sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Wisconsin',
	'intro' => array(
		'Wisconsin businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Wisconsin electrical contractor.',
		'Since April 2014, Wisconsin has required both the business and the electrician to be licensed. The Department of Safety and Professional Services (DSPS) licenses electrical contractors, and no one may install, repair or maintain electrical wiring unless licensed as an electrician or registered.',
		'A licensed contractor must use appropriately licensed or registered people for the work, and where a permit is needed, wiring can&rsquo;t start until the permit is issued.',
	),
	'facts' => array(
		'Licensing agency' => 'Wisconsin Department of Safety and Professional Services (DSPS)',
		'Business' => 'Electrical contractor license',
		'Individuals' => 'Master, journeyman and registered electricians',
		'Law' => 'Wis. Stat. &sect; 101.862; SPS 305',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Wisconsin',
		'body' => array(
			'Under Wis. Stat. &sect; 101.862 and SPS 305.41, a business needs an <strong>electrical contractor</strong> license from DSPS before taking on the installation, repair or maintenance of electrical wiring, and the contractor must use appropriately licensed or registered individuals to do the work. Electricians are licensed as <strong>master</strong>, residential master, <strong>journeyman</strong> and industrial journeyman electricians, and apprentices and registered electricians work under them.',
			'Check the contractor and electrician on DSPS&rsquo;s license lookup before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Wisconsin DSPS',
			'Business' => 'Electrical contractor license',
			'Individuals' => 'Master and journeyman electricians; registered electricians and apprentices',
			'Permits' => 'Wiring may not start before a required permit is issued',
		),
		'sources' => array(
			array('Wisconsin DSPS: Electrical contractor', 'https://dsps.wi.gov/Pages/Professions/ElectricalContractor/Default.aspx'),
			array('Wis. Admin. Code SPS 305.41', 'https://docs.legis.wisconsin.gov/document/administrativecode/SPS%20305.41(3)'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Wisconsin',
		'body' => array(
			'Wisconsin cities, villages and towns issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit before wiring starts.',
		),
		'table' => array(
			'Sign permit' => 'Your city, village or town',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Wisconsin winters bring heavy snow, ice and lake-effect storms near Lake Michigan. Ask your installer to seal wall penetrations and mount letters clear of snow buildup.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Wisconsin Storefronts',
		'lead' => 'Lit letters for main streets and shopping centers across the state.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters that read through winter evenings.',
			'standard-channel-letter-back-lit' => 'A soft glow on brick downtown storefronts in Wisconsin.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for supper clubs and cafes.',
			'18oz-blockout-banner' => 'Heavy banners for wind and weather in Wisconsin.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Wisconsin?',
			'a' => 'We ship custom channel letters anywhere in Wisconsin from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a DSPS-licensed electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Wisconsin?',
			'a' => 'The business needs a DSPS electrical contractor license, and the people doing the wiring need electrician licenses or registrations.',
		),
		array(
			'q' => 'Can wiring start before the permit is issued?',
			'a' => 'No. Where a permit is required, a licensed contractor may not begin installing wiring until the permit is issued.',
		),
		array(
			'q' => 'How long does shipping to Wisconsin take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'A DSPS FAQ (2016) reportedly says sign installers working on signs or skeleton tubing generally don&rsquo;t need an electrical license; the FAQ couldn&rsquo;t be loaded. Confirm and add, since it changes who can install signs.',
		'Milwaukee sign permits (Department of Neighborhood Services, Chapter 244 per secondary sources) are not shown; confirm and add.',
		'Transit days to Wisconsin not shown until verified.',
	),
);
