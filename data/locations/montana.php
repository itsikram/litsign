<?php
/**
 * Montana state guide (/locations/montana/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'montana',
	'name' => 'Montana',
	'abbr' => 'MT',
	'status' => 'review',
	'neighbors' => array('idaho', 'wyoming', 'south-dakota', 'north-dakota'),
	'seo_title' => 'Storefront Signs Shipped to Montana | Electrical Board Guide',
	'seo_description' => 'Channel letters shipped to Montana, plus who can connect them (State Electrical Board contractors and electricians) and how Billings sign permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Montana',
	'intro' => array(
		'Montana businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Montana electrician.',
		'The Montana State Electrical Board licenses electrical contractors by the license of the electrician responsible for their work. An unlimited electrical contractor needs a master electrician and may do residential and commercial work; a limited contractor needs a journeyman and is limited to residential construction of fewer than five living units in one structure, so a storefront sign calls for an unlimited contractor.',
		'In Billings, the Planning Division processes sign permits, and the electrical hookup needs a separate permit from the Building Division.',
	),
	'facts' => array(
		'Licensing agency' => 'Montana State Electrical Board',
		'Commercial work' => 'Unlimited electrical contractor (master electrician responsible)',
		'Limited contractor' => 'Residential only, fewer than 5 units per structure',
		'Billings' => 'Planning Division sign permit + Building Division electrical permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Montana',
		'body' => array(
			'According to the State Electrical Board&rsquo;s contractor application, an <strong>unlimited electrical contractor</strong> requires a master electrician and may do residential and commercial work, while a <strong>limited electrical contractor</strong> requires a journeyman and is restricted to residential construction of fewer than five living units in one structure. The Montana-licensed master serves as the responsible electrician, working full time and overseeing all licensed electrical work the contractor performs.',
			'Contractors must also show Montana workers&rsquo; compensation and unemployment insurance coverage, or an exemption. Check a contractor&rsquo;s license on the Department of Labor and Industry&rsquo;s license lookup before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Montana State Electrical Board, Department of Labor and Industry',
			'For a storefront sign' => 'Unlimited electrical contractor',
			'Responsible electrician' => 'Montana-licensed master, full time',
		),
		'sources' => array(
			array('Montana State Electrical Board: Electrical contractor application (PDF)', 'https://boards.bsd.dli.mt.gov/_docs/ele/ec-app.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Montana',
		'body' => array(
			'Montana cities and counties issue sign permits under their own zoning rules, and electrical permits come from the city building department or from the state where the state inspects.',
			'In <strong>Billings</strong>, the Planning Division receives and processes sign permit applications, which are required within city limits and in the zoned areas around them. Since December 2023, all signs on one property, or for one tenant in a multi-tenant building, go on a single application, filed through the city&rsquo;s online Citizen Access portal. Attach a site plan, a foundation and construction drawing for freestanding signs, and a structural attachment detail for attached signs. Electrical installations and hookups need separate permits from the Building Division.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Billings' => 'Planning Division, one application per property or tenant',
			'Billings electrical' => 'Separate Building Division permit',
		),
		'sources' => array(
			array('City of Billings: Sign permit information', 'https://billingsmt.gov/2503/Sign-Permit-Information'),
		),
	),
	'climate' => array(
		'Montana winters are long and cold, with heavy snow, ice and strong winds, especially east of the mountains. Ask your installer to plan anchoring for wind and to mount letters clear of snow buildup.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Montana Storefronts',
		'lead' => 'Lit letters for main streets and highway frontage.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for highway-facing businesses.',
			'standard-channel-letter-back-lit' => 'A warm glow on downtown brick storefronts.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for tourist-season foot traffic.',
			'18oz-blockout-banner' => 'Heavy banners that stand up to wind.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Montana?',
			'a' => 'We ship custom channel letters anywhere in Montana from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Montana electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Montana?',
			'a' => 'For commercial work, an unlimited electrical contractor license, with a Montana-licensed master electrician responsible for the work.',
		),
		array(
			'q' => 'How do I apply for a sign permit in Billings?',
			'a' => 'Through the city&rsquo;s Citizen Access portal, with all signs for the property or tenant on one application. The electrical hookup needs a separate Building Division permit.',
		),
		array(
			'q' => 'How long does shipping to Montana take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Contractor tiers are from the board&rsquo;s July 2024 application form; confirm before going live.',
		'Transit days to Montana not shown until verified.',
	),
);
