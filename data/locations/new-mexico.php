<?php
/**
 * New Mexico state guide (/locations/new-mexico/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'new-mexico',
	'name' => 'New Mexico',
	'abbr' => 'NM',
	'status' => 'review',
	'neighbors' => array('arizona', 'colorado', 'oklahoma', 'texas'),
	'seo_title' => 'Storefront Signs Shipped to New Mexico | ES-1 Sign License',
	'seo_description' => 'Channel letters shipped to New Mexico, plus who can install them (CID ES-1 electrical sign or EE-98 contractors) and where local sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to New Mexico',
	'intro' => array(
		'New Mexico businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed New Mexico contractor.',
		'New Mexico separates sign structures from sign wiring. The Construction Industries Division (CID) issues a GS-23 classification for non-electrical sign construction, but electrified signs may only be installed by contractors holding the ES-1 electrical signs and outline lighting classification or the full EE-98 electrical classification.',
		'Sign permits come from your city or county. Albuquerque, Las Cruces and Santa Fe each enforce their own sign rules, and Santa Fe&rsquo;s historic districts are especially strict about size, materials and lighting.',
	),
	'facts' => array(
		'Licensing agency' => 'Construction Industries Division (Regulation and Licensing Department)',
		'Electrical signs' => 'ES-1 Electrical signs and outline lighting',
		'Full electrical' => 'EE-98 Electrical contractor',
		'Non-electrical signs' => 'GS-23 Sign construction (non-electrical)',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in New Mexico',
		'body' => array(
			'New Mexico&rsquo;s license classifications rule, 14.6.6 NMAC, defines <strong>ES-1</strong> as electrical signs and outline lighting, requiring two years&rsquo; experience and limited to commercial locations. <strong>EE-98</strong> covers residential, commercial and industrial electrical wiring of 5,000 volts or less and requires four years&rsquo; experience.',
			'The <strong>GS-23</strong> sign construction classification lets a contractor fabricate and install non-electrical signs, but the rule says electrified signs shall only be installed by contractors holding an ES-1 or EE-98 classification. ES-1 licensees may install raceway needed to connect to a sign or outline lighting circuit, up to 10 feet; other raceway on or in a building needs an EE-98 contractor.',
		),
		'table' => array(
			'Licensing agency' => 'Construction Industries Division, NM Regulation and Licensing Department',
			'Electrical signs' => 'ES-1 (2 years&rsquo; experience, commercial locations)',
			'Full electrical' => 'EE-98 (4 years&rsquo; experience)',
			'Sign raceway' => 'ES-1 may run up to 10 ft to connect a sign circuit',
		),
		'sources' => array(
			array('14.6.6 NMAC: Construction industries license classifications and scopes (PDF)', 'https://www.cabq.gov/municipaldevelopment/documents/cid-license-classifications-and-scopes.pdf/@@download/file/CID%20License%20Classifications%20and%20Scopes.pdf'),
			array('NM Regulation and Licensing Department: Construction Industries Division', 'https://www.rld.nm.gov/construction/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in New Mexico',
		'body' => array(
			'New Mexico cities and counties issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit pulled by the ES-1 or EE-98 contractor.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical work' => 'ES-1 or EE-98 contractor',
		),
		'sources' => array(),
	),
	'climate' => array(
		'New Mexico&rsquo;s high-desert sun is intense, nights get cold, and spring winds and monsoon storms are common. Pick colors that hold up to strong UV, and ask your installer about anchoring for wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for New Mexico Storefronts',
		'lead' => 'Lit letters for shopping centers and understated signs for historic districts.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Readable lit letters for Albuquerque shopping centers.',
			'hidden-back-halo-lit' => 'A quiet halo look on stucco facades.',
			'standard-channel-letter-back-lit' => 'Back lit letters for a soft glow on adobe-style walls.',
			'feather-angled-flag-pole' => 'Flags for roadside visibility in New Mexico.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in New Mexico?',
			'a' => 'We ship custom channel letters anywhere in New Mexico from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a CID-licensed ES-1 or EE-98 contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in New Mexico?',
			'a' => 'Electrified signs must be installed by a contractor with the ES-1 (electrical signs and outline lighting) or EE-98 (electrical) classification. GS-23 covers non-electrical signs only.',
		),
		array(
			'q' => 'Can a sign contractor run wiring to my sign in New Mexico?',
			'a' => 'An ES-1 contractor may install up to 10 feet of raceway to connect to a sign circuit. Longer runs and other raceway on the building need an EE-98 contractor.',
		),
		array(
			'q' => 'How long does shipping to New Mexico take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The 14.6.6 NMAC copy is hosted by the City of Albuquerque; confirm it matches the current rule on the state site.',
		'The Santa Fe historic-district line is general knowledge; confirm or remove. Add Albuquerque&rsquo;s sign permit process once confirmed.',
		'Transit days to New Mexico not shown until verified.',
	),
);
