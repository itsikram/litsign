<?php
/**
 * Missouri state guide (/locations/missouri/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'missouri',
	'name' => 'Missouri',
	'abbr' => 'MO',
	'status' => 'review',
	'neighbors' => array('iowa', 'illinois', 'kentucky', 'tennessee', 'arkansas', 'oklahoma', 'kansas', 'nebraska'),
	'seo_title' => 'Storefront Signs Shipped to Missouri | Local License Guide',
	'seo_description' => 'Channel letters shipped to Missouri. Electrical licensing is mostly local, so Kansas City, St. Louis and other cities set their own contractor rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Missouri',
	'intro' => array(
		'Missouri businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed local installer.',
		'Missouri has no mandatory statewide electrician license. The state offers an optional statewide electrical contractor license, but it is recognized only in participating jurisdictions, and larger home rule cities such as Kansas City, St. Louis, Springfield and Columbia license electrical contractors themselves.',
		'Whatever license your installer holds, permits stay local: the sign permit and the electrical permit both come from the city or county where your business is.',
	),
	'facts' => array(
		'State electrical license' => 'No mandatory license; optional statewide contractor license',
		'Big cities' => 'Kansas City, St. Louis, Springfield, Columbia license locally',
		'Kansas City permits' => 'Filed through CompassKC',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Missouri',
		'body' => array(
			'Electrical licensing in Missouri is largely local. In <strong>Kansas City</strong>, a business doing regulated electrical work needs a city contractor license backed by a qualified individual, and permits are filed through the city&rsquo;s CompassKC portal and inspected by the city. St. Louis and other home rule cities run their own programs.',
			'The optional statewide electrical contractor license is accepted only where a jurisdiction participates, so ask your installer which license is valid at your address.',
		),
		'table' => array(
			'State license' => 'Optional; accepted only in participating jurisdictions',
			'Kansas City' => 'City electrical contractor license; permits via CompassKC',
			'Elsewhere' => 'Your city or county',
		),
		'sources' => array(
			array('City of Kansas City, MO: CompassKC permit portal', 'https://compasskc.kcmo.org'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Missouri',
		'body' => array(
			'Missouri cities and counties issue sign permits under their own codes. Billboards along state highways also need a Missouri Department of Transportation permit, but a sign on your own storefront is handled locally.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Billboards on state highways' => 'MoDOT permit',
		),
		'sources' => array(
			array('Kansas City, MO: Outdoor advertising sign information bulletin (PDF)', 'https://kcmo.gov/home/showdocument?id=3310'),
		),
	),
	'climate' => array(
		'Missouri summers are hot and humid with severe storms and hail, and winters bring ice. Ask your installer about anchoring for wind and sealing wall penetrations.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Missouri Storefronts',
		'lead' => 'Lit letters for Kansas City and St. Louis area shopping centers and main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for suburban shopping centers in Missouri.',
			'standard-channel-letter-back-lit' => 'A soft glow for historic brick storefronts.',
			'feather-angled-flag-pole' => 'Flags for busy roads in Missouri.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Missouri.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Missouri?',
			'a' => 'We ship custom channel letters anywhere in Missouri from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a local electrical contractor licensed where your business is.',
		),
		array(
			'q' => 'Does Missouri require a state electrician license?',
			'a' => 'No. Missouri has an optional statewide contractor license, but most larger cities, including Kansas City and St. Louis, license electrical contractors locally.',
		),
		array(
			'q' => 'Where are Kansas City permits filed?',
			'a' => 'Through the city&rsquo;s CompassKC online portal.',
		),
		array(
			'q' => 'How long does shipping to Missouri take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Optional statewide license (Office of Statewide Electrical Contractors) and city list are from secondary sources; confirm with the Missouri Division of Professional Registration.',
		'SB 240 (statewide license acceptance) was pending per one source; check whether it passed and update.',
		'Transit days to Missouri not shown until verified.',
	),
);
