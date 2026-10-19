<?php
/**
 * North Dakota state guide (/locations/north-dakota/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'north-dakota',
	'name' => 'North Dakota',
	'abbr' => 'ND',
	'status' => 'review',
	'neighbors' => array('minnesota', 'south-dakota', 'montana'),
	'seo_title' => 'Storefront Signs Shipped to North Dakota | Electrician Guide',
	'seo_description' => 'Channel letters shipped to North Dakota, plus who can wire them (State Electrical Board master and journeyman electricians) and where sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to North Dakota',
	'intro' => array(
		'North Dakota businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed North Dakota electrician.',
		'The North Dakota State Electrical Board licenses master, journeyman, Class B and power limited electricians. Class B licenses are limited to farmstead and residential wiring, so a commercial storefront sign is work for master and journeyman electricians.',
		'A person may contract for electrical work without a license only by contracting through a licensed electrical contractor, so check that your sign installer works with one.',
	),
	'facts' => array(
		'Licensing agency' => 'North Dakota State Electrical Board',
		'Licenses' => 'Master, Journeyman, Class B, Power Limited',
		'Class B' => 'Farmstead and residential wiring only',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in North Dakota',
		'body' => array(
			'North Dakota law lists four classes of electricians: <strong>master</strong>, <strong>journeyman</strong>, <strong>Class B</strong> and <strong>power limited</strong>. A journeyman needs 8,000 hours of experience installing and repairing electrical wiring, gained over at least three years, and a master needs at least 2,000 hours as a licensed journeyman under the board&rsquo;s rules. Class B electricians are limited to farmstead and residential wiring.',
			'Hire a licensed electrical contractor for a storefront sign, and look up licenses with the State Electrical Board.',
		),
		'table' => array(
			'Licensing agency' => 'North Dakota State Electrical Board',
			'Journeyman' => '8,000 hours over at least 3 years',
			'Master' => 'At least 2,000 hours as a licensed journeyman',
			'Class B' => 'Farmstead and residential only',
		),
		'sources' => array(
			array('N.D. Admin. Code ch. 24.1-03-01: Electrician licensing (PDF)', 'https://ndlegis.gov/api/acdata/pdf/24.1-03-01.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in North Dakota',
		'body' => array(
			'North Dakota cities issue sign permits under their own zoning codes. In Fargo, Bismarck and other larger cities, the planning or inspections office reviews signs, and the electrical side of a lit sign is reported and inspected through the state electrical program or the city.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical' => 'State Electrical Board program or city inspection',
		),
		'sources' => array(),
	),
	'climate' => array(
		'North Dakota winters are severe, with extreme cold, blowing snow and strong winds across open land. Ask your installer about anchoring for high wind and mounting letters clear of drifting snow.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for North Dakota Storefronts',
		'lead' => 'Lit letters for Fargo and Bismarck centers and small-town main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for long, dark winter evenings.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for maximum visibility.',
			'18oz-blockout-banner' => 'Heavy banners built for wind in North Dakota.',
			'13oz-vinyl-banner' => 'Grand opening and event banners in North Dakota.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in North Dakota?',
			'a' => 'We ship custom channel letters anywhere in North Dakota from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed North Dakota electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'Who can wire a lit sign in North Dakota?',
			'a' => 'Licensed master or journeyman electricians working through a licensed electrical contractor. Class B licenses cover only farmstead and residential wiring.',
		),
		array(
			'q' => 'Where do I get a sign permit in North Dakota?',
			'a' => 'From your city&rsquo;s planning or inspections office, under its zoning code.',
		),
		array(
			'q' => 'How long does shipping to North Dakota take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'License classes and the 8,000-hour figure are from a 2019 bill draft of NDCC 43-09-10; confirm against current law.',
		'The permits paragraph (planning/inspections office, state or city electrical inspection) is general; confirm for Fargo.',
		'Transit days to North Dakota not shown until verified.',
	),
);
