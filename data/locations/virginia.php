<?php
/**
 * Virginia state guide (/locations/virginia/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'virginia',
	'name' => 'Virginia',
	'abbr' => 'VA',
	'status' => 'review',
	'neighbors' => array('maryland', 'west-virginia', 'kentucky', 'tennessee', 'north-carolina'),
	'seo_title' => 'Storefront Signs Shipped to Virginia | DPOR License Guide',
	'seo_description' => 'Channel letters shipped to Virginia, plus who can wire them (DPOR-licensed electrical contractors and electricians) and how Virginia Beach sign permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Virginia',
	'intro' => array(
		'Virginia businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Virginia installer.',
		'Virginia&rsquo;s Department of Professional and Occupational Regulation (DPOR) licenses both sides of the job. The Board for Contractors licenses businesses by class (A, B or C, which caps the contract size) and by specialty, such as Electrical (ELE), and its Tradesman program licenses individual electricians.',
		'Sign permits come from your city or county. In Virginia Beach, the state&rsquo;s largest city, most new or replacement signs need a permit approved by the zoning administrator.',
	),
	'facts' => array(
		'Licensing agency' => 'DPOR Board for Contractors',
		'Business license' => 'Class A, B or C contractor with the Electrical (ELE) specialty',
		'Individuals' => 'Electricians licensed through the Tradesman program',
		'Virginia Beach' => 'Zoning administrator sign permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Virginia',
		'body' => array(
			'The <strong>Board for Contractors</strong> licenses businesses engaged in construction, removal, repair or improvement of facilities on property owned by others. The license class determines the dollar value of the contracts a business may take, and the classification or specialty, such as <strong>Electrical (ELE)</strong>, determines what work it may do. All applicants complete eight hours of pre-license education.',
			'The board also licenses tradesmen, including electricians. Check both the company and the electrician on DPOR&rsquo;s License Lookup before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Virginia DPOR, Board for Contractors',
			'Business' => 'Class A, B or C license with the ELE specialty',
			'Individuals' => 'Licensed electricians (Tradesman program)',
			'Check a license' => 'DPOR License Lookup',
		),
		'sources' => array(
			array('Virginia DPOR: Board for Contractors', 'https://www.dpor.virginia.gov/Boards/Contractors'),
			array('Virginia DPOR: Adding a specialty to a contractor license (PDF)', 'https://www.dpor.virginia.gov/sites/default/files/boards/Contractors/A501-27ADDSP.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Virginia',
		'body' => array(
			'Virginia&rsquo;s independent cities and its counties each adopt their own sign ordinances, while building and electrical work follows the Virginia Uniform Statewide Building Code.',
			'In <strong>Virginia Beach</strong>, most new or replacement signs need a permit approved by the zoning administrator under the City Code. You may need to submit plans and diagrams so the administrator can check the sign against the sign ordinance and the Virginia Uniform Statewide Building Code. Fees are set in section 8-31 of the City Code, and decisions are generally made within 30 days of a complete application.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Building code' => 'Virginia Uniform Statewide Building Code',
			'Virginia Beach' => 'Zoning administrator; decisions generally within 30 days',
			'Virginia Beach fees' => 'City Code section 8-31',
		),
		'sources' => array(
			array('City of Virginia Beach: Sign permits', 'https://planning.virginiabeach.gov/permits/sign'),
		),
	),
	'climate' => array(
		'Virginia summers are hot and humid, and coastal cities like Virginia Beach and Norfolk face salt air and hurricane season winds. Ask your installer about corrosion-resistant hardware and anchoring for wind on exposed facades.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Virginia Storefronts',
		'lead' => 'Lit letters for shopping centers from Northern Virginia to Hampton Roads.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for suburban shopping centers.',
			'hidden-back-halo-lit' => 'A polished halo look for offices and clinics in Virginia.',
			'feather-angled-flag-pole' => 'Flags for roadside visibility in Virginia.',
			'adhesive-window-perf' => 'Window graphics that keep the view out in Virginia.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Virginia?',
			'a' => 'We ship custom channel letters anywhere in Virginia from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a DPOR-licensed electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in Virginia?',
			'a' => 'The business needs a Board for Contractors license (Class A, B or C) with the Electrical (ELE) specialty, and the electricians are licensed through DPOR&rsquo;s Tradesman program.',
		),
		array(
			'q' => 'How long does a Virginia Beach sign permit take?',
			'a' => 'The city says decisions are generally made within 30 days of a complete application, which may need plans and diagrams.',
		),
		array(
			'q' => 'How long does shipping to Virginia take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Confirm the exact tradesman credential (Master or Journeyman Electrician) DPOR requires to qualify an ELE license, and the class contract limits.',
		'Virginia Beach details are from the city planning page; re-check before going live.',
		'Transit days to Virginia not shown until verified.',
	),
);
