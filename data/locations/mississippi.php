<?php
/**
 * Mississippi state guide (/locations/mississippi/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'mississippi',
	'name' => 'Mississippi',
	'abbr' => 'MS',
	'status' => 'review',
	'neighbors' => array('tennessee', 'alabama', 'louisiana', 'arkansas'),
	'seo_title' => 'Storefront Signs Shipped to Mississippi | License Guide',
	'seo_description' => 'Channel letters shipped to Mississippi, plus how electrical licensing works (state contractor licenses, local electrician licenses) and local sign permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Mississippi',
	'intro' => array(
		'Mississippi businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed local installer.',
		'Mississippi splits electrical licensing between the state and local governments. The Mississippi State Board of Contractors licenses electrical contracting businesses for larger projects, while individual electricians are licensed by cities and counties such as Jackson.',
		'Because local rules vary, start with the building department where your business is: it will tell you which license your installer needs and what the sign permit requires.',
	),
	'facts' => array(
		'State licensing' => 'Mississippi State Board of Contractors (businesses)',
		'Individual electricians' => 'Licensed by cities and counties',
		'Sign permit' => 'Your city or county',
		'First step' => 'Call your local building department',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Mississippi',
		'body' => array(
			'The <strong>Mississippi State Board of Contractors</strong> licenses contracting businesses, including electrical contractors, for projects above its dollar thresholds. Individual journeyman and master electricians aren&rsquo;t licensed by the state; cities and counties, including Jackson, run their own exams and licenses.',
			'Ask your installer which state and local licenses they hold, and confirm them with the Board of Contractors and your city.',
		),
		'table' => array(
			'State agency' => 'Mississippi State Board of Contractors',
			'Individuals' => 'Local electrician licenses',
		),
		'sources' => array(
			array('Mississippi State Board of Contractors', 'https://www.msboc.us/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Mississippi',
		'body' => array(
			'Mississippi cities and counties issue sign permits under their own zoning ordinances, and a lit sign also needs an electrical permit from the city or county.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical permit' => 'Your city or county',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Mississippi heat and humidity are constant, spring storms bring high winds, and the Gulf Coast faces hurricanes and salt air. Ask your installer about anchoring and corrosion-resistant hardware.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Mississippi Storefronts',
		'lead' => 'Lit letters for shopping centers and downtown squares.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for shopping centers along busy roads in Mississippi.',
			'standard-channel-letter-back-lit' => 'A soft glow on downtown square storefronts.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Mississippi.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Mississippi.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Mississippi?',
			'a' => 'We ship custom channel letters anywhere in Mississippi from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a locally licensed electrician or contractor to mount and connect it.',
		),
		array(
			'q' => 'Does Mississippi license electricians?',
			'a' => 'The state licenses electrical contracting businesses through the Board of Contractors; individual electricians are licensed by cities and counties.',
		),
		array(
			'q' => 'Where do I get a sign permit in Mississippi?',
			'a' => 'From your city or county building or planning department.',
		),
		array(
			'q' => 'How long does shipping to Mississippi take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Board of Contractors thresholds conflict across sources ($50,000 commercial; $5,000 or $10,000 for some work); confirm before adding numbers.',
		'"No state individual license" is from secondary sources; confirm with MSBOC.',
		'Transit days to Mississippi not shown until verified.',
	),
);
