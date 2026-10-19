<?php
/**
 * Louisiana state guide (/locations/louisiana/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'louisiana',
	'name' => 'Louisiana',
	'abbr' => 'LA',
	'status' => 'review',
	'neighbors' => array('arkansas', 'mississippi', 'texas'),
	'seo_title' => 'Storefront Signs Shipped to Louisiana | LSLBC License Guide',
	'seo_description' => 'Channel letters shipped to Louisiana, plus who can wire them (LSLBC-licensed electrical contractors) and how local sign permits and parish rules work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Louisiana',
	'intro' => array(
		'Louisiana businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Louisiana contractor.',
		'Louisiana licenses contractors through the State Licensing Board for Contractors (LSLBC), which issues commercial licenses by major classification, subclassification and specialty classification. Electrical work is one of the board&rsquo;s classifications.',
		'Sign permits come from your city or parish. New Orleans, Baton Rouge, Shreveport and Lafayette each have their own sign rules, and historic districts in New Orleans add their own review.',
	),
	'facts' => array(
		'Licensing agency' => 'Louisiana State Licensing Board for Contractors (LSLBC)',
		'License' => 'Commercial contractor license with an electrical classification',
		'Structure' => 'Major classifications, subclassifications, specialties',
		'Sign permit' => 'Your city or parish',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Louisiana',
		'body' => array(
			'The <strong>LSLBC</strong> licenses commercial contractors by classification: major classifications such as building construction and electrical work, subclassifications within them, and specialty classifications. Hire a contractor whose LSLBC license includes electrical work to connect your sign.',
			'Many Louisiana cities and parishes also license or register electricians locally, so check with your local permit office as well, and look up the contractor on the LSLBC website before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Louisiana State Licensing Board for Contractors',
			'For sign wiring' => 'LSLBC license with an electrical classification',
			'Local' => 'City or parish rules may also apply',
		),
		'sources' => array(
			array('LSLBC: Exams and classifications', 'https://lslbc.louisiana.gov/exams-classifications/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Louisiana',
		'body' => array(
			'Louisiana cities and parishes issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit. In historic districts, a sign may need design review before a permit is issued.',
		),
		'table' => array(
			'Sign permit' => 'Your city or parish',
			'Historic districts' => 'Design review may apply',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Louisiana heat, humidity and hurricanes are hard on signs, and salt air reaches far inland. Ask your installer about corrosion-resistant hardware and anchoring for hurricane winds.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Louisiana Storefronts',
		'lead' => 'Lit letters for shopping centers and signs for busy restaurant districts.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Baton Rouge and Lafayette shopping centers.',
			'halo-reverse-acrylic-lit-channel-letters' => 'A halo glow for restaurants and bars.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for daily specials.',
			'13oz-vinyl-banner' => 'Festival and event banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Louisiana?',
			'a' => 'We ship custom channel letters anywhere in Louisiana from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Louisiana electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in Louisiana?',
			'a' => 'A Louisiana State Licensing Board for Contractors license that includes electrical work, plus any license your city or parish requires.',
		),
		array(
			'q' => 'Do historic districts affect my sign?',
			'a' => 'They can. Signs in historic districts, such as those in New Orleans, may need design review before a permit is issued.',
		),
		array(
			'q' => 'How long does shipping to Louisiana take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Confirm the LSLBC dollar threshold for commercial electrical work and whether a sign subclassification exists.',
		'"Many cities and parishes license electricians locally" and the historic-district lines are general; confirm for New Orleans.',
		'Transit days to Louisiana not shown until verified.',
	),
);
