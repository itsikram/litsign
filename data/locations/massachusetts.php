<?php
/**
 * Massachusetts state guide (/locations/massachusetts/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'massachusetts',
	'name' => 'Massachusetts',
	'abbr' => 'MA',
	'status' => 'review',
	'neighbors' => array('rhode-island', 'connecticut', 'new-york', 'vermont', 'new-hampshire'),
	'seo_title' => 'Storefront Signs Shipped to Massachusetts | Permit Guide',
	'seo_description' => 'Channel letters shipped to Massachusetts, plus who can wire them (licensed master or journeyman electricians) and how Boston ISD sign permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Massachusetts',
	'intro' => array(
		'Massachusetts businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a Massachusetts-licensed electrician.',
		'The Board of State Examiners of Electricians licenses Master (Class A) and Journeyman (Class B) electricians, and only licensed masters or journeymen may legally be hired for electrical work at a home or business. Its other licenses cover fire warning and security systems, so the person who connects your lit sign holds a master or journeyman license.',
		'In Boston, every sign needs a permit from the Inspectional Services Department, and the electrical side of a sign is permitted separately, by a contractor with a Massachusetts trade license.',
	),
	'facts' => array(
		'Licensing agency' => 'Board of State Examiners of Electricians',
		'Licenses for sign wiring' => 'Master (Class A) or Journeyman (Class B) electrician',
		'Boston sign permit' => 'Inspectional Services Department (ISD)',
		'Boston electrical permit' => 'Filed by a Massachusetts-licensed contractor',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Massachusetts',
		'body' => array(
			'The board&rsquo;s consumer fact sheet is plain about it: only licensed masters or journeymen can legally be hired for electrical work at a home or business. A master license requires at least a year as a licensed Massachusetts journeyman plus 150 hours of instruction, under 237 CMR 13.00.',
			'The board also licenses fire warning and security system contractors and technicians, but those licenses don&rsquo;t cover sign wiring. Look up your electrician&rsquo;s license with the Division of Occupational Licensure before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Board of State Examiners of Electricians',
			'Master' => 'Class A master electrician',
			'Journeyman' => 'Class B journeyman electrician',
			'Master prerequisites' => '1 year as a MA journeyman + 150 hours of instruction',
		),
		'sources' => array(
			array('Mass.gov: Electricians consumer fact sheet', 'https://www.mass.gov/info-details/electricians-consumer-fact-sheet'),
			array('Mass.gov: 237 CMR 13.00, eligibility for initial licensure', 'https://www.mass.gov/doc/237-cmr-13-eligibility-criteria-for-initial-licensure/download'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Massachusetts',
		'body' => array(
			'Massachusetts cities and towns regulate signs through local zoning bylaws, and building and electrical permits are issued by the local building department and wiring inspector.',
			'<strong>Boston</strong>&rsquo;s Inspectional Services Department requires a permit for all signage and checks it against the building and zoning codes; sign zoning rules are in Article 11 of the Boston Zoning Code. Use a Short-Form permit for work on an existing sign and a Long-Form permit for a new sign where none existed before, and apply for all of a business&rsquo;s signs on one permit through ISD&rsquo;s online portal. Property owners and contractors can apply. Wiring or electrical service for the sign needs an electrical permit, which only a contractor with a valid Massachusetts trade license can apply for.',
		),
		'table' => array(
			'Sign permit' => 'Your city or town building department',
			'Boston' => 'ISD Short-Form (existing sign) or Long-Form (new sign) permit',
			'Boston zoning' => 'Article 11, Boston Zoning Code',
			'Boston electrical' => 'Electrical permit by a Massachusetts-licensed contractor',
		),
		'sources' => array(
			array('City of Boston: Install or replace a sign for your business', 'https://www.boston.gov/boston-permitting/install-or-replace/install-or-replace-sign-your-business'),
			array('City of Boston ISD: Sign permit application submission requirements (PDF)', 'https://www.boston.gov/sites/default/files/file/2026/09/Sign%20Permit%20Application%20Submission%20Requirements_20260803.pdf'),
		),
	),
	'climate' => array(
		'Massachusetts winters bring nor&rsquo;easters, heavy snow and ice, and coastal towns get salt air. Plan the mounting for wind and snow load on exposed facades, and keep letters clear of where snow piles up.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Massachusetts Storefronts',
		'lead' => 'Lit letters for main streets and city blocks, plus graphics for storefront glass.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Classic lit letters for plazas and main streets.',
			'hidden-back-halo-lit' => 'A refined halo look for Boston-area boutiques and offices.',
			'frosted-vinyl-etched' => 'Frosted privacy graphics for street-level offices.',
			'premium-window-cling' => 'Reusable window promotions for city storefronts.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Massachusetts?',
			'a' => 'We ship custom channel letters anywhere in Massachusetts from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Massachusetts-licensed electrician or sign contractor to mount and connect it.',
		),
		array(
			'q' => 'Who can wire a lit sign in Massachusetts?',
			'a' => 'A licensed master (Class A) or journeyman (Class B) electrician. The state says only licensed masters or journeymen may be hired for electrical work at a business.',
		),
		array(
			'q' => 'Who applies for a sign permit in Boston?',
			'a' => 'Property owners and contractors can apply for ISD&rsquo;s Short-Form or Long-Form sign permit. The electrical permit for the sign must come from a contractor with a Massachusetts trade license.',
		),
		array(
			'q' => 'How long does shipping to Massachusetts take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'No sign-only electrician license was found among the board&rsquo;s license types; confirm with the board that sign wiring needs a Class A or B license.',
		'Boston ISD details are from the city&rsquo;s permitting page and its August 2026 requirements PDF; re-check before going live.',
		'Transit days to Massachusetts not shown until verified.',
	),
);
