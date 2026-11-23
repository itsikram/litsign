<?php
/**
 * Rhode Island state guide (/locations/rhode-island/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'rhode-island',
	'name' => 'Rhode Island',
	'abbr' => 'RI',
	'status' => 'review',
	'neighbors' => array('connecticut', 'massachusetts'),
	'seo_title' => 'Storefront Signs Shipped to Rhode Island | Sign License Guide',
	'seo_description' => 'Channel letters shipped to Rhode Island, plus the state electrical sign contractor license (Certificate SCF) and where local sign permits come from.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Rhode Island',
	'intro' => array(
		'Rhode Island businesses can price LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Rhode Island sign installer.',
		'Rhode Island has a license of its own for sign companies. The state Board of Examiners of Electricians, in the Department of Labor and Training&rsquo;s Division of Professional Regulation, issues a <strong>Certificate SCF</strong>, the electrical sign contractor&rsquo;s license, to businesses engaged in electrical sign installation.',
		'Sign permits come from each of the state&rsquo;s cities and towns. In Providence, building and electrical permits are handled by the Department of Inspection and Standards.',
	),
	'facts' => array(
		'Licensing agency' => 'Board of Examiners of Electricians (RI Department of Labor and Training)',
		'Sign license' => 'Certificate SCF: electrical sign contractor',
		'Law' => 'R.I. Gen. Laws Chapter 5-6',
		'Sign permit' => 'Your city or town (Providence: Department of Inspection and Standards)',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Rhode Island',
		'body' => array(
			'Rhode Island&rsquo;s electrician licensing law, Chapter 5-6 of the General Laws, sets out a <strong>Certificate SCF</strong>, the electrical sign contractor&rsquo;s license, issued to any person, firm or corporation qualified under the chapter and engaged in the business of electrical sign installations. Licenses are issued by the state Board of Examiners of Electricians.',
			'A licensed electrical contractor can also install and connect a sign. Before you hire, ask whether the installer holds an SCF or an electrical contractor license, and verify it with the Department of Labor and Training.',
		),
		'table' => array(
			'Licensing agency' => 'Board of Examiners of Electricians, Division of Professional Regulation (DLT)',
			'Sign contractor' => 'Certificate SCF, electrical sign contractor&rsquo;s license',
			'Also qualified' => 'Licensed electrical contractor',
			'Law' => 'R.I. Gen. Laws Chapter 5-6',
		),
		'sources' => array(
			array('Rhode Island General Laws, Chapter 5-6: Electricians', 'https://webserver.rilegislature.gov/Statutes/TITLE5/5-6/INDEX.htm'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Rhode Island',
		'body' => array(
			'Each Rhode Island city and town regulates signs through its zoning ordinance, and historic districts in places like Providence and Newport can add a review step. A lit sign also needs an electrical permit from the local building official.',
			'In <strong>Providence</strong>, the Department of Inspection and Standards handles building and electrical permits, and its commercial checklist calls for a complete permit application, an owner authorization and a written scope of work.',
		),
		'table' => array(
			'Sign permit' => 'Your city or town',
			'Providence' => 'Department of Inspection and Standards',
			'Lit signs' => 'Electrical permit as well',
		),
		'sources' => array(
			array('City of Providence: Department of Inspection and Standards', 'https://www.providenceri.gov/inspection-standards/'),
			array('City of Providence: DIS commercial permit checklist 2026 (PDF)', 'https://www.providenceri.gov/wp-content/uploads/2026/05/Department-of-Inspection-and-Standards-Commercial-Checklist-2026.pdf'),
		),
	),
	'climate' => array(
		'Rhode Island is the Ocean State: salt air, coastal storms and hurricane season winds reach most storefronts. Ask your installer about corrosion-resistant fasteners and anchoring for wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Rhode Island Storefronts',
		'lead' => 'Lit letters for downtowns and plazas, plus signs for the summer season.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for plazas and main streets.',
			'halo-reverse-acrylic-lit-channel-letters' => 'A halo glow for restaurants and shops downtown.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for summer foot traffic in Rhode Island.',
			'premium-window-cling' => 'Reusable window promotions for seasonal sales.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Rhode Island?',
			'a' => 'We ship custom channel letters anywhere in Rhode Island from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Rhode Island sign contractor or electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'Does Rhode Island have a sign contractor license?',
			'a' => 'Yes. The Board of Examiners of Electricians issues a Certificate SCF, the electrical sign contractor&rsquo;s license, for businesses that install electrical signs.',
		),
		array(
			'q' => 'Who handles permits in Providence?',
			'a' => 'The Department of Inspection and Standards handles building and electrical permits. Zoning or historic district review may also apply to signs.',
		),
		array(
			'q' => 'How long does shipping to Rhode Island take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The statute page could not be loaded during research; the SCF wording comes from bill texts quoting &sect; 5-6. Confirm against the current statute.',
		'Providence sign-specific permit steps were not found on the city site; confirm with DIS (401-680-5375 per the city site).',
		'Transit days to Rhode Island not shown until verified.',
	),
);
