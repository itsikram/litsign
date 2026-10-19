<?php
/**
 * South Dakota state guide (/locations/south-dakota/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'south-dakota',
	'name' => 'South Dakota',
	'abbr' => 'SD',
	'status' => 'review',
	'neighbors' => array('north-dakota', 'minnesota', 'iowa', 'nebraska', 'wyoming', 'montana'),
	'seo_title' => 'Storefront Signs Shipped to South Dakota | Electrician Guide',
	'seo_description' => 'Channel letters shipped to South Dakota, plus who can wire them (State Electrical Commission contractors and journeymen) and where sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to South Dakota',
	'intro' => array(
		'South Dakota businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed South Dakota electrician.',
		'The South Dakota Electrical Commission, in the Department of Labor and Regulation, licenses electrical contractors, journeyman electricians, Class B electricians and maintenance electricians under SDCL chapter 36-16.',
		'To become an electrical contractor, a journeyman needs two more years under a contractor, including at least a year of commercial wiring, which is the experience a storefront sign calls for.',
	),
	'facts' => array(
		'Licensing agency' => 'South Dakota Electrical Commission (Department of Labor and Regulation)',
		'Licenses' => 'Electrical contractor, Journeyman, Class B, Maintenance',
		'Law' => 'SDCL chapter 36-16',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in South Dakota',
		'body' => array(
			'SDCL chapter 36-16 provides for <strong>electrical contractor</strong>, <strong>journeyman electrician</strong>, <strong>Class B electrician</strong>, electrical inspector and maintenance electrician licenses. A journeyman has at least four years of experience wiring and repairing electrical equipment.',
			'The Commission&rsquo;s application sets the path to contractor: a journeyman needs two years under a licensed contractor, with at least one year of commercial wiring, and a Class B electrician one additional year of commercial wiring. Hire a licensed electrical contractor for a storefront sign.',
		),
		'table' => array(
			'Licensing agency' => 'South Dakota Electrical Commission',
			'Journeyman' => 'At least 4 years of experience',
			'Journeyman to contractor' => '2 years under a contractor, 1 year commercial',
			'Law' => 'SDCL 36-16',
		),
		'sources' => array(
			array('South Dakota Codified Laws 36-16-2', 'https://sdlegislature.gov/Statutes/36-16-2'),
			array('South Dakota Electrical Commission: License application (PDF)', 'https://dlr.sd.gov/electrical/forms/license_app_general_fillable.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in South Dakota',
		'body' => array(
			'South Dakota cities issue sign permits under their own zoning codes, and Sioux Falls and Rapid City run their own permit offices. The electrical work on a lit sign is permitted and inspected through the state electrical program or the city.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical' => 'State electrical program or city inspection',
		),
		'sources' => array(),
	),
	'climate' => array(
		'South Dakota winters bring extreme cold, blowing snow and strong winds, and summers bring hail. Ask your installer about anchoring for wind and mounting letters clear of drifts.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for South Dakota Storefronts',
		'lead' => 'Lit letters for Sioux Falls and Rapid City storefronts and tourist towns.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Sioux Falls shopping centers.',
			'standard-channel-letter-back-lit' => 'A warm glow on downtown brick in South Dakota.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for tourist season.',
			'18oz-blockout-banner' => 'Heavy banners built for wind in South Dakota.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in South Dakota?',
			'a' => 'We ship custom channel letters anywhere in South Dakota from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed South Dakota electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in South Dakota?',
			'a' => 'A South Dakota Electrical Commission license; for a storefront sign, hire a licensed electrical contractor with commercial wiring experience.',
		),
		array(
			'q' => 'Where do I get a sign permit in South Dakota?',
			'a' => 'From your city or county. Sioux Falls and Rapid City run their own permit offices.',
		),
		array(
			'q' => 'How long does shipping to South Dakota take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Confirm current license classes on dlr.sd.gov (secondary sites used different names).',
		'Add Sioux Falls sign permit steps once confirmed on the city site.',
		'Transit days to South Dakota not shown until verified.',
	),
);
