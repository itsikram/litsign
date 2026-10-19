<?php
/**
 * South Carolina state guide (/locations/south-carolina/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'south-carolina',
	'name' => 'South Carolina',
	'abbr' => 'SC',
	'status' => 'review',
	'neighbors' => array('north-carolina', 'georgia'),
	'seo_title' => 'Storefront Signs Shipped to South Carolina | EL License Guide',
	'seo_description' => 'Channel letters shipped to South Carolina, plus who can wire them (LLR Contractor&rsquo;s Licensing Board EL contractors) and where sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to South Carolina',
	'intro' => array(
		'South Carolina businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed South Carolina electrical contractor.',
		'South Carolina licenses commercial electrical contractors through the Contractor&rsquo;s Licensing Board in the Department of Labor, Licensing and Regulation (LLR), under the Electrical (EL) classification of a mechanical contractor&rsquo;s license. Residential electricians are licensed by a different board.',
		'Sign permits are local: Charleston, Columbia, Greenville and the coastal towns each set their own sign rules, and historic districts may add architectural review.',
	),
	'facts' => array(
		'Licensing agency' => 'SC LLR Contractor&rsquo;s Licensing Board',
		'Commercial electrical' => 'Mechanical contractor license, Electrical (EL) classification',
		'Residential electricians' => 'Licensed by a separate board',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in South Carolina',
		'body' => array(
			'Commercial electrical work above the state&rsquo;s dollar threshold needs a <strong>mechanical contractor&rsquo;s license</strong> with the <strong>Electrical (EL)</strong> classification from the Contractor&rsquo;s Licensing Board under S.C. Code &sect; 40-11-410, with the total project cost counting toward the threshold.',
			'Many cities and counties also require local business licenses and permits. Look up the contractor on LLR&rsquo;s license lookup before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'SC Department of Labor, Licensing and Regulation, Contractor&rsquo;s Licensing Board',
			'Commercial electrical' => 'Mechanical contractor license, EL classification',
			'Law' => 'S.C. Code &sect; 40-11-410',
		),
		'sources' => array(
			array('Municipal Association of SC: Building officials&rsquo; licensing problem areas (PDF)', 'https://www.masc.sc/sites/default/files/uploads/affiliated-associations/03-23_bloa_problem-areas-2.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in South Carolina',
		'body' => array(
			'South Carolina cities and counties issue sign permits under their own zoning ordinances, and a lit sign also needs an electrical permit. In historic districts, a board of architectural review may need to approve the sign first.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Historic districts' => 'Architectural review may apply',
		),
		'sources' => array(),
	),
	'climate' => array(
		'South Carolina summers are hot and humid, and the coast faces hurricanes and salt air. Ask your installer about corrosion-resistant hardware and anchoring for hurricane winds.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for South Carolina Storefronts',
		'lead' => 'Lit letters for shopping centers and understated signs for historic downtowns.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Greenville and Columbia shopping centers.',
			'hidden-back-halo-lit' => 'A quiet halo look for historic districts.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for beach town foot traffic in South Carolina.',
			'13oz-vinyl-banner' => 'Grand opening and event banners in South Carolina.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in South Carolina?',
			'a' => 'We ship custom channel letters anywhere in South Carolina from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed South Carolina electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in South Carolina?',
			'a' => 'For commercial work above the state threshold, a mechanical contractor&rsquo;s license with the Electrical (EL) classification from the Contractor&rsquo;s Licensing Board.',
		),
		array(
			'q' => 'Do historic districts affect my sign?',
			'a' => 'They can. In historic districts, a board of architectural review may need to approve a sign before a permit is issued.',
		),
		array(
			'q' => 'How long does shipping to South Carolina take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The EL threshold conflicts across sources ($5,000 in LLR/MASC material, $10,000 in newer secondary sources); confirm the current figure before adding it.',
		'Transit days to South Carolina not shown until verified.',
	),
);
