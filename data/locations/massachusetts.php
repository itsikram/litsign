<?php
/**
 * Massachusetts state guide (/locations/massachusetts/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$bos_sign = 'https://www.boston.gov/boston-permitting/install-or-replace/install-or-replace-sign-your-business';
$bos_long = 'https://www.boston.gov/boston-permitting/permits/long-form-permits';

return array(
	'slug' => 'massachusetts',
	'name' => 'Massachusetts',
	'abbr' => 'MA',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Massachusetts',
	'neighbors' => array('rhode-island', 'connecticut', 'new-york', 'vermont', 'new-hampshire'),
	'seo_title' => 'Channel Letters Shipped to Massachusetts | Boston Permits',
	'seo_description' => 'Channel letters shipped to Massachusetts: who may wire them (masters or journeymen), Boston Long-Form permits and design review. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Massachusetts',
	'intro' => array(
		'Massachusetts businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a Massachusetts-licensed electrician. Banners and window graphics are printed in Renton, WA.',
		'The state is clear about who may connect them: the Board of State Examiners of Electricians says only a licensed master or journeyman electrician can legally be hired for electrical work at a home or business. There is no separate sign-only electrical license.',
		'In Boston, a brand-new sign needs a Long-Form permit from Inspectional Services, the electrical permit is pulled separately by a contractor with a Massachusetts trade license, and many storefronts in commercial districts also go through design review.',
	),
	'facts' => array(
		'Licensing agency' => 'Board of State Examiners of Electricians',
		'Who may wire a sign' => 'Licensed master (A) or journeyman (B) electrician',
		'Boston new sign' => 'ISD Long-Form permit',
		'Boston electrical' => 'Separate permit, Massachusetts-licensed contractor only',
	),
	'categories' => array(
		'channel-letters' => 'A new Boston sign goes on a Long-Form permit, while changes to an existing sign use the Short-Form; either way the lit sign&rsquo;s electrical permit is separate.',
		'window-graphics' => 'Boston Planning says a new or replacement storefront sign in a commercial area may trigger design review, so plan graphics alongside the sign rather than after it.',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Massachusetts',
		'body' => array(
			'The board licenses <strong>Master (Class A)</strong> and <strong>Journeyman (Class B)</strong> electricians, plus fire warning and security system licenses (Classes C and D) that don&rsquo;t cover sign wiring. Its consumer fact sheet says only licensed masters or journeymen may legally be hired for electrical work at a home or business. A master can work as an individual, firm or corporation and must keep a regular place of business, and a license type and number must appear on any sign, listing or advertisement for electrical work.',
			'A master license requires at least a year as a licensed Massachusetts journeyman plus 150 hours of instruction, under 237 CMR 13.00. Before work starts, ask to see the electrical permit, and look the license up with the Division of Occupational Licensure.',
		),
		'table' => array(
			'Licensing agency' => 'Board of State Examiners of Electricians',
			'Master' => 'Class A master electrician',
			'Journeyman' => 'Class B journeyman electrician',
			'Master prerequisites' => '1 year as a Massachusetts journeyman + 150 hours of instruction',
			'Advertising' => 'License type and number on any ad or listing',
		),
		'sources' => array(
			array('Mass.gov: Electricians consumer fact sheet', 'https://www.mass.gov/info-details/electricians-consumer-fact-sheet'),
			array('Mass.gov: 237 CMR 13.00, eligibility for initial licensure', 'https://www.mass.gov/doc/237-cmr-13-eligibility-criteria-for-initial-licensure/download'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Massachusetts',
		'body' => array(
			'Massachusetts cities and towns regulate signs through their own zoning bylaws, and the local building department and wiring inspector issue the building and electrical permits.',
			'<strong>Boston</strong>&rsquo;s Inspectional Services Department (ISD) permits all signage against the building and zoning codes; sign zoning sits in Article 11 of the Boston Zoning Code. Work on an existing sign uses a <strong>Short-Form</strong> permit, and a new sign where none existed before uses a <strong>Long-Form</strong> permit. Long-Form applications pay a $50 application fee plus $10 for every $1,000 of estimated cost, and the applicant, not ISD, gathers outside approvals such as Landmarks Commission sign-off in a historic district. Starting work before the permit is issued can mean paying double the permit cost or a code violation.',
			'Wiring for the sign needs its own electrical permit, which only a contractor holding a valid Massachusetts trade license can apply for.',
		),
		'table' => array(
			'Sign permit' => 'Your city or town building department',
			'Boston existing sign' => 'ISD Short-Form permit',
			'Boston new sign' => 'ISD Long-Form permit ($50 + $10 per $1,000 of cost)',
			'Boston zoning' => 'Article 11, Boston Zoning Code',
			'Boston electrical' => 'Separate permit by a Massachusetts-licensed contractor',
		),
		'sources' => array(
			array('City of Boston: Install or replace a sign for your business', $bos_sign),
			array('City of Boston: Long-Form permits', $bos_long),
			array('City of Boston ISD: Sign permit application submission requirements (PDF)', 'https://www.boston.gov/sites/default/files/file/2026/09/Sign%20Permit%20Application%20Submission%20Requirements_20260803.pdf'),
		),
	),
	'districts' => array(
		array(
			'name' => 'Boston design review',
			'rule' => 'Boston Planning reviews many signs in the city&rsquo;s commercial areas, and a new or replacement sign may trigger design review. The agency&rsquo;s Sign Code Modernization initiative plans a zoning text amendment and new sign design guidelines, so check the current rules before you finalize letter sizes.',
			'source' => 'http://www.bostonplans.org/urban-design/storefront-signage',
		),
		array(
			'name' => 'Boston historic districts',
			'rule' => 'Projects in a historic district need Landmarks Commission approval, which the applicant obtains before ISD issues the Long-Form permit.',
			'source' => $bos_long,
		),
	),
	'climate' => array(
		'Massachusetts winters bring nor&rsquo;easters, wet heavy snow and ice dams, and Cape Cod and North Shore storefronts face salt air. Mount letters above where plows and shovels pile snow, and keep raceways and drain holes clear so meltwater doesn&rsquo;t refreeze inside them.',
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
			'a' => 'We ship custom channel letters anywhere in Massachusetts, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Massachusetts-licensed electrician or sign contractor to mount and connect them.',
		),
		array(
			'q' => 'Who can wire a lit sign in Massachusetts?',
			'a' => 'A licensed master (Class A) or journeyman (Class B) electrician. The state says only licensed masters or journeymen may legally be hired for electrical work at a business, and there is no sign-only electrical license. The board also requires the license type and number on any advertisement for electrical work.',
		),
		array(
			'q' => 'Which Boston permit does a new storefront sign need?',
			'a' => 'A Long-Form permit from Inspectional Services, since it&rsquo;s a new sign where none existed. Changes to an existing sign use the Short-Form. The electrical permit is separate and filed by a Massachusetts-licensed contractor. A Long-Form application costs $50 plus $10 for every $1,000 of estimated cost.',
		),
		array(
			'q' => 'What happens if a Boston sign goes up before the permit?',
			'a' => 'The city warns that starting work before the permit is issued can mean paying double the permit cost or getting a code violation, so wait for the Long-Form permit, and any Landmarks or design review approval, before installation day. The applicant, not ISD, collects those outside approvals.',
		),
		array(
			'q' => 'How long does shipping to Massachusetts take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Electricians consumer fact sheet read from the Internet Archive copy of 2026-02-14.',
		'Boston Long-Form fee ($50 + $10 per $1,000) and the double-fee warning from the page updated 9/11/26: re-check yearly.',
		'Boston Planning storefront page read from the Internet Archive copy of 2026-04-18; the Sign Code Modernization initiative is time-sensitive, so re-check its status before each update.',
		'Boston window-sign and banner rules (Zoning Code Article 11) not found; nothing about them is shown.',
		'Transit days to Massachusetts not shown until verified.',
	),
);
