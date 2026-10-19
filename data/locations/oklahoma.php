<?php
/**
 * Oklahoma state guide (/locations/oklahoma/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'oklahoma',
	'name' => 'Oklahoma',
	'abbr' => 'OK',
	'status' => 'review',
	'neighbors' => array('kansas', 'missouri', 'arkansas', 'texas', 'new-mexico', 'colorado'),
	'seo_title' => 'Storefront Signs Shipped to Oklahoma | CIB License Guide',
	'seo_description' => 'Channel letters shipped to Oklahoma, plus who can wire them (Construction Industries Board electrical contractors and electricians) and local sign permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Oklahoma',
	'intro' => array(
		'Oklahoma businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Oklahoma electrician.',
		'Oklahoma licenses electrical work statewide through the Construction Industries Board (CIB) in Oklahoma City, which issues electrical contractor and electrician licenses.',
		'Sign permits come from your city. Oklahoma City, Tulsa and Norman each enforce their own sign codes.',
	),
	'facts' => array(
		'Licensing agency' => 'Oklahoma Construction Industries Board (CIB)',
		'Licenses' => 'Unlimited and limited electrical contractor; journeyman and other electricians',
		'Continuing education' => '6 hours every 36 months',
		'Sign permit' => 'Your city',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Oklahoma',
		'body' => array(
			'The <strong>Construction Industries Board</strong> licenses electrical contractors and electricians statewide. Electrical licensees complete six hours of continuing education every 36 months.',
			'Hire a CIB-licensed electrical contractor whose licensed electricians will do the wiring, and check both on the CIB license search.',
		),
		'table' => array(
			'Licensing agency' => 'Oklahoma Construction Industries Board, Oklahoma City',
			'Business' => 'Electrical contractor (unlimited or limited)',
			'Individuals' => 'Licensed electricians',
		),
		'sources' => array(
			array('Oklahoma CIB: 2026 proposed rules (PDF)', 'https://aem-prod.oklahoma.gov/content/dam/ok/en/cib/documents/rules/CIB%202026%20Proposed%20Rules%20with%20Gov%20Approval.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Oklahoma',
		'body' => array(
			'Oklahoma cities issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit pulled by a licensed contractor.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Electrical permit' => 'CIB-licensed contractor',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Oklahoma weather brings strong winds, hail and severe spring storms, plus hot summers and winter ice. Ask your installer about anchoring for high wind on exposed facades.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Oklahoma Storefronts',
		'lead' => 'Lit letters for Oklahoma City and Tulsa shopping centers.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for strip centers along busy roads.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for maximum nighttime presence in Oklahoma.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Oklahoma.',
			'18oz-blockout-banner' => 'Heavy banners built for wind in Oklahoma.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Oklahoma?',
			'a' => 'We ship custom channel letters anywhere in Oklahoma from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a CIB-licensed electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Oklahoma?',
			'a' => 'A Construction Industries Board electrician license, working for a CIB-licensed electrical contractor.',
		),
		array(
			'q' => 'Where do I get a sign permit in Oklahoma?',
			'a' => 'From your city. Oklahoma City, Tulsa and Norman each enforce their own sign codes.',
		),
		array(
			'q' => 'How long does shipping to Oklahoma take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The limited contractor "management only, no hands-on work" description is from an exam-prep site; confirm with CIB.',
		'Confirm whether CIB has a sign-specific electrical license category.',
		'Transit days to Oklahoma not shown until verified.',
	),
);
