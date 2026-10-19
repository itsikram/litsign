<?php
/**
 * Georgia state guide (/locations/georgia/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'georgia',
	'name' => 'Georgia',
	'abbr' => 'GA',
	'status' => 'review',
	'neighbors' => array('tennessee', 'north-carolina', 'south-carolina', 'florida', 'alabama'),
	'seo_title' => 'Storefront Signs Shipped to Georgia | Class II License Guide',
	'seo_description' => 'Channel letters shipped to Georgia, plus who can wire them (Class I and Class II electrical contractors) and how Atlanta sign permits and lighting rules work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Georgia',
	'intro' => array(
		'Georgia businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Georgia electrical contractor.',
		'Georgia licenses electrical contractors statewide in two classes. A Class I license is restricted to single-phase installations not over 200 amps at the service, and a Class II license is unrestricted. Apprentices and journeymen don&rsquo;t need their own license but must work under a licensed electrical contractor.',
		'In Atlanta, sign permits go through the Office of Buildings, and the applicant agrees to measure the installed sign&rsquo;s light level at the nearest residential district boundary within ten days.',
	),
	'facts' => array(
		'Licensing agency' => 'Georgia State Construction Industry Licensing Board, Division of Electrical Contractors',
		'Class I' => 'Single-phase, up to 200 amps at the service',
		'Class II' => 'Unrestricted',
		'Atlanta' => 'Office of Buildings sign permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Georgia',
		'body' => array(
			'Under Georgia&rsquo;s rules, a <strong>Class I</strong> electrical contractor license is restricted to single-phase electrical installations that don&rsquo;t exceed 200 amperes at the service drop or lateral, and a <strong>Class II</strong> license is unrestricted. Applicants must be at least 21, document at least four years of primary experience, pass the exam with a 70, and submit three references, one from a licensed electrical contractor.',
			'Journeymen and apprentices work under the licensed contractor&rsquo;s supervision. For a commercial building with three-phase service, hire a Class II contractor.',
		),
		'table' => array(
			'Licensing agency' => 'Division of Electrical Contractors, State Construction Industry Licensing Board',
			'Class I' => 'Single-phase, not over 200 A at the service',
			'Class II' => 'Unrestricted',
			'Journeymen / apprentices' => 'No license; work under a licensed contractor',
		),
		'sources' => array(
			array('Ga. Comp. R. &amp; Regs. 121-3-.01: Statewide electrical contractor license', 'https://www.law.cornell.edu/regulations/georgia/Ga-Comp-R-Regs-R-121-3-.01'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Georgia',
		'body' => array(
			'Georgia cities and counties issue sign permits under their own sign ordinances, and metro Atlanta cities and counties each have their own rules.',
			'In the <strong>City of Atlanta</strong>, the sign permit application, handled through the Office of Buildings at 55 Trinity Avenue, asks for scaled street-front elevations, sign renderings, a site plan for freestanding signs, the business owner&rsquo;s and sign erector&rsquo;s details and a business license number. The applicant agrees to measure the installed sign&rsquo;s foot-candle level within ten days at the nearest residential district boundary, and the sign must meet the code in effect when the permit is issued.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Atlanta' => 'Office of Buildings, 55 Trinity Avenue',
			'Atlanta lighting' => 'Foot-candle reading within 10 days of installation',
		),
		'sources' => array(
			array('City of Atlanta: Sign permit application (PDF)', 'https://atlantaga.gov/Home/ShowDocument?id=16756'),
		),
	),
	'climate' => array(
		'Georgia summers are hot and humid with strong thunderstorms, and the coast sees hurricanes and salt air. Ask your installer about anchoring for wind and keeping drain holes clear.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Georgia Storefronts',
		'lead' => 'Lit letters for metro Atlanta centers and Georgia main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for metro Atlanta shopping centers.',
			'halo-reverse-acrylic-lit-channel-letters' => 'A halo glow for restaurants and boutiques.',
			'standard-channel-letter-back-lit' => 'A soft glow on brick storefronts.',
			'feather-angled-flag-pole' => 'Flags for busy suburban roads in Georgia.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Georgia?',
			'a' => 'We ship custom channel letters anywhere in Georgia from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Georgia electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'Class I or Class II electrical contractor?',
			'a' => 'Class I covers single-phase work up to 200 amps at the service; Class II is unrestricted. Most commercial buildings call for a Class II contractor.',
		),
		array(
			'q' => 'What does Atlanta require after a sign is installed?',
			'a' => 'The applicant measures the sign&rsquo;s light level (foot-candles) within ten days at the nearest residential district boundary.',
		),
		array(
			'q' => 'How long does shipping to Georgia take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The Atlanta sign permit form is dated 01/15; confirm it is current with the Office of Buildings.',
		'Transit days to Georgia not shown until verified.',
	),
);
