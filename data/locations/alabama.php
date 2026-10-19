<?php
/**
 * Alabama state guide (/locations/alabama/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'alabama',
	'name' => 'Alabama',
	'abbr' => 'AL',
	'status' => 'review',
	'neighbors' => array('tennessee', 'georgia', 'florida', 'mississippi'),
	'seo_title' => 'Storefront Signs Shipped to Alabama | AECB License Guide',
	'seo_description' => 'Channel letters shipped to Alabama, plus who can wire them (Alabama Electrical Contractors Board contractors and journeymen) and where sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Alabama',
	'intro' => array(
		'Alabama businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Alabama electrical contractor.',
		'Alabama issues two electrical licenses through the Alabama Electrical Contractors Board (AECB): the electrical contractor license and the journeyman electrician license. Anyone in the business of soliciting and installing electrical power or control systems needs a state license, with no dollar threshold.',
		'Both licenses renew every year, so ask to see a current license before work starts.',
	),
	'facts' => array(
		'Licensing agency' => 'Alabama Electrical Contractors Board (AECB)',
		'Licenses' => 'Electrical contractor; journeyman electrician',
		'Threshold' => 'None; any electrical contracting business needs a license',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Alabama',
		'body' => array(
			'The <strong>AECB</strong> licenses <strong>electrical contractors</strong>, who may take any job, employ electricians and run their own business, and <strong>journeyman electricians</strong>, who work under a licensed contractor. Alabama doesn&rsquo;t issue a separate master electrician license, and apprentices and helpers don&rsquo;t need one.',
			'Under Title 34, Chapter 36 of the Code of Alabama, anyone engaged in the business of soliciting and installing electrical power or control systems must hold a state electrical license. Check your installer on the AECB license search.',
		),
		'table' => array(
			'Licensing agency' => 'Alabama Electrical Contractors Board',
			'Business' => 'Electrical contractor license',
			'Individuals' => 'Journeyman electrician (works under a contractor)',
			'Renewal' => 'Annual',
		),
		'sources' => array(
			array('Alabama Electrical Contractors Board: Licensee forms', 'https://aecb.alabama.gov/licensees/forms/'),
			array('Alabama Administrative Code ch. 303-X-1', 'https://admincode.legislature.state.al.us/api/chapter/303-X-1'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Alabama',
		'body' => array(
			'Alabama cities issue sign permits under their own zoning ordinances; Birmingham, Huntsville, Mobile and Montgomery each run their own permit offices, and counties cover some unincorporated areas.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical work' => 'AECB-licensed contractor',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Alabama summers are hot and humid, spring brings severe storms, and the Gulf Coast faces hurricanes and salt air. Ask your installer about anchoring for wind and corrosion-resistant hardware near the coast.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Alabama Storefronts',
		'lead' => 'Lit letters for shopping centers and downtown squares.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Birmingham and Huntsville centers.',
			'standard-channel-letter-back-lit' => 'A soft glow on downtown brick in Alabama.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Alabama.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Alabama.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Alabama?',
			'a' => 'We ship custom channel letters anywhere in Alabama from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an AECB-licensed electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Alabama?',
			'a' => 'The company needs an AECB electrical contractor license, and its electricians need journeyman licenses.',
		),
		array(
			'q' => 'Does Alabama have a master electrician license?',
			'a' => 'No. Alabama issues electrical contractor and journeyman electrician licenses.',
		),
		array(
			'q' => 'How long does shipping to Alabama take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Add Birmingham sign permit steps once confirmed on the city site.',
		'Transit days to Alabama not shown until verified.',
	),
);
