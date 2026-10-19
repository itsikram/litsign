<?php
/**
 * Tennessee state guide (/locations/tennessee/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'tennessee',
	'name' => 'Tennessee',
	'abbr' => 'TN',
	'status' => 'review',
	'neighbors' => array('kentucky', 'virginia', 'north-carolina', 'georgia', 'alabama', 'mississippi', 'arkansas', 'missouri'),
	'seo_title' => 'Storefront Signs Shipped to Tennessee | CE & LLE License Guide',
	'seo_description' => 'Channel letters shipped to Tennessee, plus who can wire them (CE electrical contractors for jobs of $25,000 or more, LLE electricians for smaller jobs).',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Tennessee',
	'intro' => array(
		'Tennessee businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Tennessee electrician.',
		'Tennessee licenses electrical work by job size. Electrical work of $25,000 or more, materials and labor included, needs a state contractor&rsquo;s license with the CE (electrical) classification. Smaller jobs are where the Limited Licensed Electrician (LLE) license applies, which many counties require.',
		'Tennessee has no statewide journeyman or master electrician license, and some local governments license electricians for jobs under $25,000 themselves, so check with your county or city.',
	),
	'facts' => array(
		'Licensing agency' => 'Tennessee Board for Licensing Contractors',
		'Jobs of $25,000+' => 'Contractor&rsquo;s license with CE (electrical) classification',
		'Jobs under $25,000' => 'Limited Licensed Electrician (LLE), required in many counties',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Tennessee',
		'body' => array(
			'If the total cost of electrical work is <strong>$25,000 or more</strong>, the contractor needs a state contractor&rsquo;s license with a <strong>CE</strong> classification. The CE classification is accepted statewide, and additional exams aren&rsquo;t required in each county or municipality, though local permits and fees still apply.',
			'For projects under $25,000, the <strong>Limited Licensed Electrician (LLE)</strong> license is required in many counties, and some local governments have their own electrician licenses for those jobs. Ask your installer which license they hold for the size of your job, and confirm it with the state or your county.',
		),
		'table' => array(
			'Licensing agency' => 'Tennessee Board for Licensing Contractors',
			'$25,000 and up' => 'Contractor&rsquo;s license, CE classification (statewide)',
			'Under $25,000' => 'LLE license (many counties) or local license',
			'Journeyman / master' => 'No statewide license',
		),
		'sources' => array(
			array('Tennessee Department of Commerce: Electrical licensing', 'https://tn.gov/commerce/regboards/lle/electrical-licensing.html'),
			array('Tennessee Department of Commerce: Who needs an LLE license?', 'https://www.tn.gov/commerce/regboards/lle/who-needs-a-limited-licensed-electrician--lle--license-.html'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Tennessee',
		'body' => array(
			'Tennessee cities and counties issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit. Local permits and fees apply even when the contractor holds a statewide CE license.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical permit' => 'Local, even with a statewide CE license',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Tennessee summers are hot and humid with strong thunderstorms, and winters bring occasional ice. Ask your installer about anchoring for wind and sealing wall penetrations.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Tennessee Storefronts',
		'lead' => 'Lit letters for Nashville, Memphis and Knoxville storefronts and small-town squares.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for suburban shopping centers in Tennessee.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for nightlife districts.',
			'standard-channel-letter-back-lit' => 'A warm glow on courthouse-square brick.',
			'13oz-vinyl-banner' => 'Grand opening and event banners in Tennessee.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Tennessee?',
			'a' => 'We ship custom channel letters anywhere in Tennessee from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Tennessee electrician or contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Tennessee?',
			'a' => 'For electrical work of $25,000 or more, a contractor&rsquo;s license with the CE classification. For smaller jobs, an LLE license is required in many counties, or a local license where the city or county issues one.',
		),
		array(
			'q' => 'Is a CE license good everywhere in Tennessee?',
			'a' => 'Yes. The CE classification is accepted statewide without extra local exams, though local permits and fees still apply.',
		),
		array(
			'q' => 'How long does shipping to Tennessee take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Nashville sign permit office is inferred from a Metro Codes commercial permit form; confirm on nashville.gov.',
		'Confirm whether an LLE may install and connect an electric sign.',
		'Transit days to Tennessee not shown until verified.',
	),
);
