<?php
/**
 * Maryland state guide (/locations/maryland/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'maryland',
	'name' => 'Maryland',
	'abbr' => 'MD',
	'status' => 'review',
	'neighbors' => array('pennsylvania', 'delaware', 'virginia', 'west-virginia'),
	'seo_title' => 'Storefront Signs Shipped to Maryland | Electrician Guide',
	'seo_description' => 'Channel letters shipped to Maryland, plus who can connect them (state-licensed master electricians, with local registration) and Baltimore sign rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Maryland',
	'intro' => array(
		'Maryland businesses can price LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Maryland electrician to mount and connect.',
		'Maryland requires a state-issued license to provide electrical services. The State Board of Master Electricians issues the master license, and counties and cities may also require a licensed electrician to register locally before working there, so your installer may need both.',
		'Baltimore sets out its sign rules in Title 17 of its Zoning Code, with limits on projecting and freestanding signs and extra review in designated sign control areas.',
	),
	'facts' => array(
		'Licensing agency' => 'Maryland State Board of Master Electricians (Maryland Department of Labor)',
		'Who needs a license' => 'Every contractor providing electrical services in Maryland',
		'Local rules' => 'Counties and cities may require local registration',
		'Baltimore sign rules' => 'Zoning Code, Title 17',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Maryland',
		'body' => array(
			'According to the Maryland Department of Labor, a state-issued license is required to provide electrical services, and each contractor who offers or provides electrical services in Maryland must hold an electrical license. The <strong>State Board of Master Electricians</strong> issues the master license, and every active master electrician must keep a certificate of insurance on file with the board.',
			'Local jurisdictions may also require registration, so a board-licensed electrician may still need to register with your county or city. Ask your installer whether they are registered where your business is, and check their license on the Department of Labor&rsquo;s license search.',
		),
		'table' => array(
			'Licensing agency' => 'State Board of Master Electricians, Maryland Department of Labor',
			'State license' => 'Master electrician (required to provide electrical services)',
			'Local' => 'County or city registration may also be required',
			'Insurance' => 'Certificate of insurance on file for active masters',
		),
		'sources' => array(
			array('Maryland Department of Labor: Master electrician licensing FAQs', 'https://labor.maryland.gov/license/elec/eleclicfaqs.shtml'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Maryland',
		'body' => array(
			'Maryland counties and cities issue sign permits under their own zoning codes, and large counties such as Montgomery, Prince George&rsquo;s and Baltimore County each run their own permitting offices.',
			'In <strong>Baltimore City</strong>, a sign may be erected, relocated or structurally altered only as the Zoning Code allows, and a sign type is permitted in a zoning district only if Table 17-201 lists it there. Projecting signs may extend no more than 4 feet from the building, with the bottom at least 8 feet above the sidewalk. A monument sign needs at least 50 feet of frontage on a public right of way and a pole or pylon sign at least 100 feet, and in Areas of Special Sign Control the Planning Commission must approve a signage plan before any signs go up.',
		),
		'table' => array(
			'Sign permit' => 'Your county or city',
			'Baltimore projecting signs' => 'Up to 4 ft from the building, bottom at least 8 ft above the sidewalk',
			'Baltimore freestanding signs' => 'Monument: 50 ft of frontage; pole or pylon: 100 ft',
			'Special sign control areas' => 'Planning Commission signage plan first',
		),
		'sources' => array(
			array('Baltimore City Zoning Code &sect; 17-201: Sign regulations', 'https://codes.baltimorecity.gov/us/md/cities/baltimore/code/32/17-201'),
			array('Baltimore City Zoning Code &sect; 17-414: Projecting signs', 'https://codes.baltimorecity.gov/us/md/cities/baltimore/code/32/17-414'),
			array('Baltimore City Zoning Code &sect; 17-408: Freestanding signs', 'https://codes.baltimorecity.gov/us/md/cities/baltimore/code/32/17-408'),
			array('Baltimore City Zoning Code &sect; 17-505: Areas of Special Sign Control', 'https://codes.baltimorecity.gov/us/md/cities/baltimore/code/32/17-505'),
		),
	),
	'climate' => array(
		'Maryland summers are hot and humid, winters bring snow and ice, and Chesapeake Bay towns see salt air and storm winds. On waterfront facades, ask your installer about corrosion-resistant hardware and anchoring for wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Maryland Storefronts',
		'lead' => 'Lit letters for rowhouse storefronts and suburban centers alike.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Readable lit letters for suburban shopping centers.',
			'standard-channel-letter-back-lit' => 'A softer glow for brick rowhouse storefronts.',
			'adhesive-window-perf' => 'Window graphics for narrow city storefronts.',
			'13oz-vinyl-banner' => 'Grand opening and coming soon banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Maryland?',
			'a' => 'We ship custom channel letters anywhere in Maryland from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Maryland-licensed electrician to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Maryland?',
			'a' => 'A state license from the State Board of Master Electricians to provide electrical services, plus any registration your county or city requires.',
		),
		array(
			'q' => 'How big can a projecting sign be in Baltimore?',
			'a' => 'Baltimore&rsquo;s Zoning Code limits projecting signs to 4 feet out from the building, with the bottom edge at least 8 feet above the sidewalk.',
		),
		array(
			'q' => 'How long does shipping to Maryland take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Baltimore City: which office issues sign permits (DHCD per secondary sources) and whether signs over a sidewalk need a DOT minor privilege permit were not confirmed on city sites.',
		'Baltimore City Code 17-10 shows a city electrician license (master, master restricted, maintenance); confirm how it relates to state licensing today.',
		'Transit days to Maryland not shown until verified.',
	),
);
