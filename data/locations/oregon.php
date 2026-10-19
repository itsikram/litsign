<?php
/**
 * Oregon state guide (/locations/oregon/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'oregon',
	'name' => 'Oregon',
	'abbr' => 'OR',
	'status' => 'review',
	'neighbors' => array('washington', 'idaho', 'nevada', 'california'),
	'seo_title' => 'Storefront Signs Shipped to Oregon | Limited Sign License Guide',
	'seo_description' => 'Channel letters shipped to Oregon, plus who can install them (limited sign contractors and limited journeyman sign electricians) and Portland sign permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Oregon',
	'intro' => array(
		'Oregon businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Oregon sign installer.',
		'Oregon has a full set of sign-specific electrical licenses. The Building Codes Division licenses Limited Journeyman Sign Electricians, who must work for a Limited Sign Contractor, and the sign contractor license is limited to the work those sign electricians may do: installing and servicing electrical signs and outline lighting.',
		'Portland Permitting &amp; Development issues permits for most signs on private property, and an internally lit sign needs both a sign permit and a separate electrical permit.',
	),
	'facts' => array(
		'Licensing agency' => 'Oregon Building Codes Division (individuals); Construction Contractors Board (businesses)',
		'Sign contractor' => 'Limited Sign Contractor (CLS)',
		'Sign electrician' => 'Limited Journeyman Sign Electrician (SIG)',
		'Sign permit' => 'Your city (Portland: Permitting &amp; Development)',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Oregon',
		'body' => array(
			'A <strong>Limited Sign Contractor</strong> may install and service electrical signs and outline lighting, and may extend a sign branch circuit up to 15 feet if a dedicated branch circuit already exists at the location. Under Oregon&rsquo;s rules, a limited sign contractor must employ at least one full-time Limited Journeyman Sign Electrician.',
			'A <strong>Limited Journeyman Sign Electrician</strong> works for a limited sign contractor and may not install a branch circuit from an electrical panel, install control equipment that isn&rsquo;t on the same wall or post as the sign, or work on service equipment. That work, including a new circuit to the sign, needs a general electrical contractor and journeyman. Check any license in the Oregon license directory before you hire.',
		),
		'table' => array(
			'Business' => 'Limited Sign Contractor (CLS)',
			'Individual' => 'Limited Journeyman Sign Electrician (SIG), employed by a sign contractor',
			'Branch circuits' => 'Extend an existing dedicated sign circuit up to 15 ft only',
			'New circuits and service work' => 'General electrical contractor and journeyman',
		),
		'sources' => array(
			array('Oregon License Directory: Limited Sign Contractor', 'https://apps.oregon.gov/SOS/LicenseDirectory/LicenseDetail/1376'),
			array('Oregon License Directory: Limited Journeyman Sign Electrician', 'https://apps.oregon.gov/SOS/LicenseDirectory/LicenseDetail/484'),
			array('OAR 918-282-0220: Limited Journeyman Sign Electrician license', 'https://www.law.cornell.edu/regulations/oregon/Or-Admin-Code-SS-918-282-0220'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Oregon',
		'body' => array(
			'Oregon cities issue sign permits under their own sign codes, and electrical permits are issued by the city or county where it runs its own electrical program, or otherwise by the state.',
			'In <strong>Portland</strong>, a sign permit is required for most signs on private property, including awnings, freestanding signs and changes between lit and unlit signs. For an internally illuminated sign, the application must be signed by the electrician doing the work or the property owner, and a separate electrical permit is required. Apply through DevHub PDX with one application per sign, including a site plan, elevations, attachment details and a sign-to-wall ratio calculation; freestanding signs need structural calculations from an Oregon-licensed engineer. Inspections are required, including at completion.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Portland' => 'Portland Permitting &amp; Development, through DevHub PDX',
			'Portland lit signs' => 'Application signed by the electrician or owner, plus an electrical permit',
			'Portland freestanding signs' => 'Oregon-licensed engineer&rsquo;s structural calculations',
		),
		'sources' => array(
			array('City of Portland: Sign permits', 'https://www.portland.gov/ppd/sign-permits'),
		),
	),
	'climate' => array(
		'Western Oregon is wet for months at a time, and the coast adds salt air and winter storms. Ask your installer to seal every wall penetration and keep letter drain holes clear so rain can&rsquo;t collect inside.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Oregon Storefronts',
		'lead' => 'Lit letters for Portland blocks and suburban centers, plus signs for rainy-season sidewalks.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters that cut through gray, rainy evenings.',
			'halo-reverse-acrylic-lit-channel-letters' => 'A halo glow popular with Portland restaurants and shops.',
			'adhesive-window-perf' => 'Window graphics for street-level storefronts.',
			'deluxe-signicade-graphic-frame' => 'Weighted sidewalk A-frames for foot traffic.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Oregon?',
			'a' => 'We ship custom channel letters anywhere in Oregon from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an Oregon limited sign contractor or electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in Oregon?',
			'a' => 'The business needs a Limited Sign Contractor license (or a general electrical contractor license), and the person doing the work a Limited Journeyman Sign Electrician license or a general journeyman license.',
		),
		array(
			'q' => 'Who signs the permit for a lit sign in Portland?',
			'a' => 'For an internally illuminated sign, the sign permit application must be signed by the electrician performing the work or the property owner, and a separate electrical permit is required.',
		),
		array(
			'q' => 'How long does shipping to Oregon take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'OAR text was current through September 2024; re-check the license directory before going live.',
		'Transit days to Oregon not shown until verified.',
	),
);
