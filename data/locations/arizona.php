<?php
/**
 * Arizona state guide (/locations/arizona/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'arizona',
	'name' => 'Arizona',
	'abbr' => 'AZ',
	'status' => 'review',
	'neighbors' => array('california', 'nevada', 'utah', 'new-mexico'),
	'seo_title' => 'Storefront Signs Shipped to Arizona | ROC License Guide',
	'seo_description' => 'Channel letters shipped to Arizona, plus who can wire them (ROC-licensed electrical contractors) and how Phoenix sign permits and inspections work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Arizona',
	'intro' => array(
		'Arizona businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Arizona contractor.',
		'Arizona licenses electrical work at the business level. The Registrar of Contractors (ROC) licenses contracting companies, each with a qualifying party who has the required experience and passes the exams, rather than issuing state journeyman or master licenses to individual electricians.',
		'In Phoenix, a sign permit from the Planning and Development Department is needed before a sign goes up, and the application lists the licensed electrical contractor doing the electrical work.',
	),
	'facts' => array(
		'Licensing agency' => 'Arizona Registrar of Contractors (ROC)',
		'Who is licensed' => 'Contracting businesses, through a qualifying party',
		'Phoenix sign permit' => 'Planning and Development Department',
		'Phoenix electrical' => 'Licensed electrical contractor listed on the application',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Arizona',
		'body' => array(
			'The Registrar of Contractors licenses sole proprietors, partnerships, LLCs and corporations that have a regularly employed qualifying party with the necessary experience, knowledge and skills. Electrical work falls under the ROC&rsquo;s electrical classifications, and the City of Phoenix notes that state law requires electrical work to be done by a licensed electrical contractor.',
			'Ask your installer for their ROC license number and classification, and look it up on the ROC&rsquo;s contractor search before you sign a contract.',
		),
		'table' => array(
			'Licensing agency' => 'Arizona Registrar of Contractors',
			'License holder' => 'The contracting business, through its qualifying party',
			'Electrical work' => 'ROC-licensed electrical contractor',
		),
		'sources' => array(
			array('Arizona ROC: License classifications', 'https://azroc.gov/license-classifications'),
			array('Arizona ROC: License classification requirements (PDF)', 'https://www.azroc.gov/sites/default/files/2023-11/License%20Classification%20Requirements_6.8.20_0.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Arizona',
		'body' => array(
			'Arizona cities and counties issue sign permits under their own zoning ordinances, so the rules in Phoenix, Scottsdale, Tempe and Mesa all differ.',
			'In <strong>Phoenix</strong>, Section 705 of the Zoning Ordinance requires a permit from the Planning and Development Department before a sign is erected, displayed, relocated or altered. The property owner, tenant or lessee, their authorized agent, a state-licensed contractor, or a registered architect or engineer may apply. The city&rsquo;s sign permit document lists the licensed electrical contractor&rsquo;s license number and contact details on the application, and an electrical sign inspection is part of the process.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Phoenix' => 'Planning and Development Department (Zoning Ordinance &sect; 705)',
			'Phoenix applicants' => 'Owner, tenant, agent, licensed contractor, architect or engineer',
		),
		'sources' => array(
			array('Phoenix Zoning Ordinance &sect; 705: Signs', 'https://phoenix.municipal.codes/ZO/705'),
			array('City of Phoenix: Sign permit information (PDF)', 'https://www.phoenix.gov/pddsite/Documents/TRT/dsd_trt_pdf_00615.pdf'),
		),
	),
	'climate' => array(
		'Arizona sun and summer heat are extreme, and monsoon storms bring sudden wind and dust. Choose colors that hold up to intense UV, and ask your installer about anchoring for monsoon winds.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Arizona Storefronts',
		'lead' => 'Bright lit letters for strip centers and halo signs for stucco facades.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'The standard lit letter in Valley strip centers.',
			'hidden-back-halo-lit' => 'A halo glow that looks sharp on stucco.',
			'standard-channel-letter-back-lit' => 'Back lit letters for a softer evening look.',
			'feather-angled-flag-pole' => 'Flags for busy arterial roads.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Arizona?',
			'a' => 'We ship custom channel letters anywhere in Arizona from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an ROC-licensed contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in Arizona?',
			'a' => 'The company needs an Arizona Registrar of Contractors license in the right classification, and electrical work must be done by a licensed electrical contractor.',
		),
		array(
			'q' => 'Who can apply for a sign permit in Phoenix?',
			'a' => 'The property owner, tenant or lessee, their authorized agent, a state-licensed contractor, or a registered architect or engineer.',
		),
		array(
			'q' => 'How long does shipping to Arizona take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Which ROC classification covers sign installation (and its electrical wiring) was not confirmed; ROC pages returned 403 during research.',
		'Phoenix fees in the city PDF ($75 wall signs, $150 ground signs, $150 electrical sign inspection) were left off the page; confirm before adding.',
		'Transit days to Arizona not shown until verified.',
	),
);
