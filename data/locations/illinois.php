<?php
/**
 * Illinois state guide (/locations/illinois/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'illinois',
	'name' => 'Illinois',
	'abbr' => 'IL',
	'status' => 'review',
	'neighbors' => array('wisconsin', 'indiana', 'iowa', 'missouri', 'kentucky'),
	'seo_title' => 'Storefront Signs Shipped to Illinois | Chicago Sign Permits',
	'seo_description' => 'Channel letters shipped to Illinois businesses, with how local electrician licensing works and Chicago Department of Buildings sign permit rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Illinois',
	'intro' => array(
		'Illinois businesses can price custom LED channel letters online, then have them built in Renton, Washington and shipped ready for a licensed local installer.',
		'Like New York, Illinois leaves electrician licensing to local governments rather than the state, so the license your installer needs depends on the town where your storefront is. Chicago runs its own licensing and its own sign permits, and in Chicago every electric sign needs a permit.',
		'If your business is in a Chicago suburb, call the village or city building department early: many license electrical contractors themselves and may or may not accept a license from another town.',
	),
	'facts' => array(
		'Electrician licensing' => 'Local (city or village); no statewide license',
		'Chicago sign permit' => 'Department of Buildings; always for electric signs',
		'Large Chicago signs' => 'City Council order over 100 sq ft or 24 ft high',
		'Over the sidewalk' => 'Chicago Public Way Use Permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Illinois',
		'body' => array(
			'Illinois doesn&rsquo;t issue a statewide electrician license. Electrical contractors and electricians are licensed by the city, village or county where the work happens, and requirements differ from town to town. Ask the local building department which license it requires and whether it accepts licenses from other municipalities.',
			'<strong>Chicago</strong> licenses electrical contractors and supervising electricians through its Department of Buildings, under the Chicago Electrical Code. Work in the city needs Chicago credentials even if the contractor is based in the suburbs.',
		),
		'table' => array(
			'Statewide license' => 'None; licensing is local',
			'Chicago' => 'Department of Buildings licenses electrical contractors and supervising electricians',
			'Suburbs' => 'Each city or village sets its own rules',
		),
		'sources' => array(
			array('Chicago Department of Buildings: Sign permits', 'https://www.chicago.gov/city/en/depts/bldgs/provdrs/permits/svcs/sign-permits.html'),
			array('Village of Libertyville: Electrical licensing (example of local licensing)', 'https://www.libertyville.com/departments/community_development/building/electrical_licensing_information.php'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Illinois',
		'body' => array(
			'Sign permits are issued by your city or village under its own sign ordinance. In unincorporated areas, the county handles them.',
			'In <strong>Chicago</strong>, a sign permit from the Department of Buildings is generally required to place a sign on a building or business, and electric or neon signs require a permit in all circumstances. Signs larger than 100 square feet or higher than 24 feet above ground also need a City Council Order, and signs that hang over the public way need a separate Public Way Use Permit.',
		),
		'table' => array(
			'Sign permit' => 'City or village building department (county if unincorporated)',
			'Chicago' => 'Department of Buildings; always required for electric signs',
			'Chicago large signs' => 'City Council Order over 100 sq ft or 24 ft above grade',
			'Chicago projecting signs' => 'Public Way Use Permit',
		),
		'sources' => array(
			array('Chicago Department of Buildings: Sign permits', 'https://www.chicago.gov/city/en/depts/bldgs/provdrs/permits/svcs/sign-permits.html'),
		),
	),
	'climate' => array(
		'Illinois winters bring hard freezes, wind and snow, and Chicago adds lake wind. Seal every wall penetration, plan anchoring for wind on exposed corners, and remember that short winter days make a lit sign do most of its work after 4:30pm for months.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Illinois Storefronts',
		'lead' => '',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright lit letters for long winter evenings.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters that stand out on busy corridors.',
			'hidden-back-halo-lit' => 'Halo letters for brick and boutique storefronts.',
			'adhesive-window-perf' => 'Window graphics for hours, offers and branding.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter sign company near me in Illinois?',
			'a' => 'We ship custom channel letters anywhere in Illinois from Renton, WA. We don&rsquo;t install, so you&rsquo;ll hire an electrical contractor licensed by your city or village to connect it.',
		),
		array(
			'q' => 'Does Illinois license electricians statewide?',
			'a' => 'No. Electrician licensing in Illinois is local. Chicago and many suburbs license electrical contractors themselves, so check with your local building department.',
		),
		array(
			'q' => 'Do I need a permit for a lit sign in Chicago?',
			'a' => 'Yes. Chicago&rsquo;s Department of Buildings requires a permit for electric and neon signs in all circumstances, and large signs over 100 square feet or 24 feet high also need a City Council Order.',
		),
		array(
			'q' => 'How long does shipping to Illinois take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'"No statewide electrician license" is from secondary sources plus local government pages; confirm before going live.',
		'Chicago licensing summary (contractor and supervising electrician) is from secondary sources; confirm on chicago.gov.',
		'Transit days to Illinois not shown until verified.',
	),
);
