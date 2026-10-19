<?php
/**
 * Utah state guide (/locations/utah/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'utah',
	'name' => 'Utah',
	'abbr' => 'UT',
	'status' => 'review',
	'neighbors' => array('idaho', 'wyoming', 'colorado', 'arizona', 'nevada'),
	'seo_title' => 'Storefront Signs Shipped to Utah | DOPL Sign License Guide',
	'seo_description' => 'Channel letters shipped to Utah, plus who can install them (DOPL S440 sign installers and licensed electrical contractors) and Salt Lake City sign permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Utah',
	'intro' => array(
		'Utah businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Utah contractor.',
		'Utah&rsquo;s Division of Professional Licensing (DOPL) has a contractor classification for sign work, S440 Sign Installation Contractor, but its scope does not include electrical trade work. So in Utah, the sign structure and the sign&rsquo;s wiring are often handled under different licenses.',
		'In Salt Lake City, the sign permit covers the sign itself, and the feeder or branch circuit wiring that serves it needs a separate electrical permit pulled by a licensed electrical contractor.',
	),
	'facts' => array(
		'Licensing agency' => 'Utah Division of Professional Licensing (DOPL)',
		'Sign contractor' => 'S440 Sign Installation Contractor (no electrical work)',
		'Sign wiring' => 'Licensed electrical contractor',
		'Salt Lake City' => 'Sign permit + separate electrical permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Utah',
		'body' => array(
			'DOPL licenses contractors by classification. The <strong>S440 Sign Installation Contractor</strong> classification covers installing signs, but a 2017 rule filing states its scope does not include installation of any electrical trade work. A separate <strong>S441</strong> classification covers non-electrical outdoor advertising signs.',
			'The electrical side of a lit sign therefore needs a licensed electrical contractor with licensed electricians. In 2025 the Legislature passed HB 28, On Premise Sign Installation Amendments, which changed some on-premise sign rules, so ask DOPL or your installer how it applies to your project.',
		),
		'table' => array(
			'Licensing agency' => 'Utah Division of Professional Licensing',
			'Sign installation' => 'S440 (excludes electrical work)',
			'Outdoor advertising (non-electrical)' => 'S441',
			'Sign wiring' => 'Licensed electrical contractor',
		),
		'sources' => array(
			array('Utah DAR File No. 42086 (2017): S440 scope excludes electrical work', 'https://rules.utah.gov/publicat/bulletin/2017/20171001/42086.htm'),
			array('Utah Legislature: HB 28 (2025) On Premise Sign Installation Amendments (PDF)', 'https://le.utah.gov/Session/2025/bills/enrolled/HB0028.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Utah',
		'body' => array(
			'Utah cities and counties issue sign permits under their own codes, and many Wasatch Front cities require engineered plans for freestanding signs.',
			'<strong>Salt Lake City</strong>&rsquo;s sign permit form calls for a separate electrical permit for the feeder or branch circuit wiring that serves sign equipment, which must be pulled by a licensed electrical contractor, and a separate permit for work on public property. Approval includes zoning, plans examiner and, where it applies, historic review, and inspections are scheduled through the city&rsquo;s permits site.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Salt Lake City' => 'Sign permit with zoning, plans examiner and historic approvals',
			'Salt Lake City electrical' => 'Separate permit by a licensed electrical contractor',
		),
		'sources' => array(
			array('Salt Lake City: Sign permit form (PDF)', 'https://www.slcdocs.com/building/f-sign-permit.pdf'),
		),
	),
	'climate' => array(
		'Utah&rsquo;s high-desert sun is strong, winters bring snow and inversions, and canyon winds can be fierce. Choose colors that hold up to UV, and ask your installer about anchoring for wind and snow.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Utah Storefronts',
		'lead' => 'Lit letters for Wasatch Front shopping centers and downtown blocks.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Wasatch Front shopping centers.',
			'hidden-back-halo-lit' => 'A polished halo look for offices and clinics in Utah.',
			'feather-angled-flag-pole' => 'Flags for busy suburban roads in Utah.',
			'13oz-vinyl-banner' => 'Coming soon and grand opening banners in Utah.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Utah?',
			'a' => 'We ship custom channel letters anywhere in Utah from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Utah contractor to mount the sign and a licensed electrical contractor to connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in Utah?',
			'a' => 'DOPL&rsquo;s S440 Sign Installation Contractor classification covers installing signs but not electrical work, which needs a licensed electrical contractor.',
		),
		array(
			'q' => 'Who pulls the electrical permit for a sign in Salt Lake City?',
			'a' => 'A licensed electrical contractor, for the feeder or branch circuit wiring that serves the sign.',
		),
		array(
			'q' => 'How long does shipping to Utah take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'HB 28 (2025) may have changed S440 scope or created an on-premise sign license; read the enacted bill and update the licensing section.',
		'"Many Wasatch Front cities require engineered plans for freestanding signs" is general; confirm or remove.',
		'Transit days to Utah not shown until verified.',
	),
);
