<?php
/**
 * Kentucky state guide (/locations/kentucky/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'kentucky',
	'name' => 'Kentucky',
	'abbr' => 'KY',
	'status' => 'review',
	'neighbors' => array('indiana', 'ohio', 'west-virginia', 'virginia', 'tennessee', 'missouri', 'illinois'),
	'seo_title' => 'Storefront Signs Shipped to Kentucky | Electrician Guide',
	'seo_description' => 'Channel letters shipped to Kentucky, plus who can wire them (state-licensed electrical contractors and master electricians) and how local permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Kentucky',
	'intro' => array(
		'Kentucky businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Kentucky electrician.',
		'Kentucky licenses electrical work statewide through the Department of Housing, Buildings and Construction (DHBC). Its license classes are electrical contractor, master electrician and electrician, and a master electrician supervises or takes responsible charge of electricians doing the work.',
		'In Lexington, electrical contractors may pull permits if they hold a master electrician license or employ a master electrician.',
	),
	'facts' => array(
		'Licensing agency' => 'Kentucky Department of Housing, Buildings and Construction (DHBC)',
		'Licenses' => 'Electrical contractor, Master electrician, Electrician',
		'Lexington permits' => 'Contractor holding or employing a master electrician',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Kentucky',
		'body' => array(
			'Kentucky&rsquo;s DHBC issues <strong>electrical contractor</strong>, <strong>master electrician</strong> and <strong>electrician</strong> licenses. A master electrician supervises, directs or takes responsible charge of electricians doing electrical construction, alteration or repair, and may also do the work.',
			'In <strong>Lexington</strong>, an electrical contractor may pull permits if it holds a master electrician license or employs a master electrician. Check your installer&rsquo;s license with DHBC before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Department of Housing, Buildings and Construction',
			'Business' => 'Electrical contractor',
			'Individuals' => 'Master electrician; electrician',
			'Lexington permits' => 'Contractor with a master electrician',
		),
		'sources' => array(
			array('Kentucky DHBC: Electrical licensing regulation material (815 KAR 35.060, PDF)', 'https://dhbc.ky.gov/documents/815%20KAR%2035.060%20Incorporated%20Material.pdf'),
			array('Lexington-Fayette Urban County Government: Electrical permits, licensing, inspections', 'https://lexingtonky.gov/node/861'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Kentucky',
		'body' => array(
			'Kentucky cities and counties issue sign permits under their own zoning rules. Louisville Metro and Lexington-Fayette each run their own planning and permit offices, and a lit sign also needs an electrical permit.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical permit' => 'Licensed electrical contractor',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Kentucky summers are hot and humid with strong storms, and winters bring ice and freeze-thaw cycles. Ask your installer about sealing wall penetrations and anchoring for wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Kentucky Storefronts',
		'lead' => 'Lit letters for Louisville and Lexington centers and small-town main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for suburban shopping centers in Kentucky.',
			'standard-channel-letter-back-lit' => 'A soft glow on historic brick storefronts.',
			'hidden-back-halo-lit' => 'A refined halo look for offices and boutiques.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Kentucky.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Kentucky?',
			'a' => 'We ship custom channel letters anywhere in Kentucky from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Kentucky electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Kentucky?',
			'a' => 'A Kentucky electrician or master electrician license, working for a licensed electrical contractor. DHBC issues all three.',
		),
		array(
			'q' => 'Who can pull an electrical permit in Lexington?',
			'a' => 'An electrical contractor that holds a master electrician license or employs a master electrician.',
		),
		array(
			'q' => 'How long does shipping to Kentucky take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Add Louisville Metro sign permit steps once confirmed.',
		'Transit days to Kentucky not shown until verified.',
	),
);
