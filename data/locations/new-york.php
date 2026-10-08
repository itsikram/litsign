<?php
/**
 * New York state guide (/locations/new-york/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'new-york',
	'name' => 'New York',
	'abbr' => 'NY',
	'status' => 'review',
	'neighbors' => array('new-jersey', 'pennsylvania', 'connecticut', 'massachusetts', 'vermont'),
	'seo_title' => 'Storefront Signs Shipped to New York | NYC Sign Permit Guide',
	'seo_description' => 'Channel letters shipped to New York businesses, with how electrician licensing works locally and NYC DOB sign permits, sign hangers and lit-sign rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to New York',
	'intro' => array(
		'New York businesses can price custom LED channel letters online and have them built in Renton, Washington and shipped ready for a licensed local installer, whether the storefront is in Manhattan or upstate.',
		'New York has no statewide electrician license. Licensing is handled locally, by cities, counties and towns, so the rules for who can connect your sign depend on where your business is. New York City has the most detailed system: the Department of Buildings licenses electricians and sign hangers, issues sign permits, and requires an annual permit for some lit signs.',
		'If you&rsquo;re in the city, plan for a licensed sign hanger and a licensed electrician. Upstate, call your city or county building department to ask who licenses electricians there.',
	),
	'facts' => array(
		'Electrician licensing' => 'Local (city, county or town); no statewide license',
		'New York City' => 'Department of Buildings (DOB)',
		'NYC sign work' => 'Licensed Master or Special Sign Hanger',
		'NYC lit signs' => 'Separate electrical permit by a licensed electrician',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in New York',
		'body' => array(
			'New York State doesn&rsquo;t issue electrician licenses; cities, counties and towns set their own requirements, and some don&rsquo;t license electricians at all. Several counties, such as Westchester and Ulster, run their own licensing boards, and some towns accept licenses from other municipalities only under conditions.',
			'In <strong>New York City</strong>, the Department of Buildings licenses electricians and sign hangers. A <strong>Master Sign Hanger</strong> license covers all sign hanging regardless of size or weight; a <strong>Special Sign Hanger</strong> license covers exterior signs up to 150 square feet of face area or 1,200 pounds. A sign that needs an electrical connection needs its own work permit, filed by a licensed electrician through DOB NOW.',
		),
		'table' => array(
			'Outside NYC' => 'Your city, county or town building department sets electrician licensing',
			'NYC electricians' => 'Licensed by the NYC Department of Buildings',
			'NYC sign hangers' => 'Master Sign Hanger (any sign); Special Sign Hanger (up to 150 sq ft or 1,200 lb)',
		),
		'sources' => array(
			array('NYC Business: Sign hanger license', 'https://nyc-business.nyc.gov/nycbusiness/description/sign-hanger-license'),
			array('NYC Department of Buildings: Sign permit', 'https://www.nyc.gov/site/buildings/property-or-business-owner/sign-permit.page'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in New York',
		'body' => array(
			'Outside New York City, your city, town or village building department issues sign permits under its own zoning and the state building code. Ask them which permits a lit sign needs and who may apply.',
			'In <strong>New York City</strong>, sign permits come from the Department of Buildings. Licensed sign hangers can file sign applications with construction documents for DOB approval, or a licensed engineer or architect can file. The electrical connection needs a separate permit filed by a licensed electrician. If a sign is illuminated and projects past the building line, DOB may also require an annual illuminated sign permit, billed every year. Zoning still decides whether a sign is allowed at all: where it goes, how large it is and whether it may be lit.',
		),
		'table' => array(
			'Outside NYC' => 'City, town or village building department',
			'NYC sign permit' => 'Department of Buildings, filed by a licensed sign hanger or design professional',
			'NYC electrical' => 'Separate work permit by a licensed electrician (DOB NOW: Build)',
			'NYC projecting lit signs' => 'Annual illuminated sign permit may apply',
		),
		'sources' => array(
			array('NYC Department of Buildings: Sign permit', 'https://www.nyc.gov/site/buildings/property-or-business-owner/sign-permit.page'),
			array('NYC DOB: Sign project requirements for registrants', 'https://www.nyc.gov/site/buildings/industry/project-requirements-registrant-signs-allowable-without-rdp.page'),
		),
	),
	'climate' => array(
		'Freeze-thaw cycles, snow and road salt are the main threats to a New York sign installation: every wall penetration should be sealed, and fasteners should be suited to wet winters. Many city storefronts are on older masonry, where a raceway keeps holes to a minimum.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for New York Storefronts',
		'lead' => 'Compact, bright signs for narrow frontages and busy sidewalks.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Readable lit letters for narrow storefronts.',
			'hidden-back-halo-lit' => 'Halo letters for brick and upscale frontages.',
			'premium-window-cling' => 'Removable window graphics for hours and offers.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk signs, where your block allows them.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a sign company near me in New York that ships channel letters?',
			'a' => 'We ship custom channel letters anywhere in New York State from Renton, WA. We don&rsquo;t install; in New York City you&rsquo;ll need a DOB-licensed sign hanger and electrician, and elsewhere a locally licensed electrician.',
		),
		array(
			'q' => 'Does New York State license electricians?',
			'a' => 'No. Electrician licensing in New York is handled by cities, counties and towns, and requirements differ from place to place. New York City&rsquo;s Department of Buildings licenses electricians in the city.',
		),
		array(
			'q' => 'Do I need a sign hanger license in NYC?',
			'a' => 'Hanging an exterior sign in New York City requires a DOB-licensed Master Sign Hanger, or a Special Sign Hanger for signs up to 150 square feet or 1,200 pounds.',
		),
		array(
			'q' => 'How long does shipping to New York take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'"No statewide electrician license" is supported by local law documents and secondary sources; no single NYS page states it. Confirm before going live.',
		'NYC annual illuminated sign permit: confirm current DOB rule (the 1997 guideline was rescinded by Buildings Bulletin 2016-015).',
		'Transit days to New York not shown until verified.',
	),
);
