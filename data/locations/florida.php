<?php
/**
 * Florida state guide (/locations/florida/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'florida',
	'name' => 'Florida',
	'abbr' => 'FL',
	'status' => 'review',
	'neighbors' => array('georgia', 'alabama'),
	'seo_title' => 'Storefront Signs Shipped to Florida | Sign License & Permits',
	'seo_description' => 'Channel letters shipped to Florida businesses: who can install them (Sign Specialty electrical contractors), Florida Building Code permits and Miami-Dade rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Florida',
	'intro' => array(
		'Florida businesses can design and price custom LED channel letters online, then have them made in Renton, Washington and shipped ready for a licensed Florida sign contractor to install.',
		'Two things set Florida apart. The state licenses a <strong>Sign Specialty</strong> electrical contractor specifically for electric signs, and every sign permit is reviewed against the Florida Building Code, including its wind design rules, which are strictest in the High-Velocity Hurricane Zone of Miami-Dade and Broward counties.',
		'Because wind and structural review can add time, start the permit conversation with your installer before you order, and leave room in your opening schedule.',
	),
	'facts' => array(
		'Licensing board' => 'Electrical Contractors&rsquo; Licensing Board (DBPR)',
		'Sign license' => 'Sign Specialty electrical contractor (ES)',
		'Building code' => 'Florida Building Code, including wind design',
		'Sign permit' => 'Your city or county building department',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Florida',
		'body' => array(
			'Florida&rsquo;s Electrical Contractors&rsquo; Licensing Board, part of the Department of Business and Professional Regulation (DBPR), certifies specialty electrical contractors, including the <strong>Sign Specialty</strong> category. Under Florida Administrative Code rule 61G6-7.001, the sign specialty covers fabricating, installing, repairing and wiring electrical signs and outline lighting, up to the last disconnect or terminal points. A certified or registered electrical contractor can also do the work.',
			'Check your installer&rsquo;s license on DBPR&rsquo;s license search before you hire them.',
		),
		'table' => array(
			'Licensing board' => 'Electrical Contractors&rsquo; Licensing Board, DBPR',
			'Sign license' => 'Sign Specialty electrical contractor (rule 61G6-7.001)',
			'Also allowed' => 'Certified or registered electrical contractors',
			'Check a license' => 'DBPR license search (myfloridalicense.com)',
		),
		'sources' => array(
			array("Florida DBPR: Electrical Contractors' Licensing Board", 'https://www2.myfloridalicense.com/electrical-contractors/'),
			array('Florida Administrative Code 61G6-7.001: Specialty electrical contractors', 'https://www.flrules.org/gateway/ruleNo.asp?id=61G6-7.001'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Florida',
		'body' => array(
			'Sign permits are issued by your city, or by your county for unincorporated areas, and sign drawings must comply with the Florida Building Code. Expect the review to look at the sign&rsquo;s structure and attachment as well as its electrical details.',
			'In <strong>unincorporated Miami-Dade County</strong>, signs are permitted through the Department of Regulatory and Economic Resources. The County&rsquo;s procedure lists illuminated exterior signs under a category reviewed by Zoning, Building, Structural and Electrical; applications are signed by the property owner and the contractor&rsquo;s qualifier, with both signatures notarized, and two sets of plans that comply with the Florida Building Code. Cities inside the county, such as Miami Beach or Doral, run their own sign permits.',
		),
		'table' => array(
			'Sign permit' => 'City or county building department',
			'Code' => 'Florida Building Code (structure, wind, electrical)',
			'Miami-Dade (unincorporated)' => 'RER; illuminated exterior signs reviewed by Zoning, Building, Structural and Electrical',
		),
		'sources' => array(
			array('Miami-Dade County: Procedure for sign permits', 'https://www.miamidade.gov/zoning/library/forms/sign-permit-procedures.pdf'),
			array('Miami-Dade County: Sign permits', 'https://www.miamidade.gov/building/standards/signs-permits.asp'),
		),
	),
	'climate' => array(
		'Hurricane season runs June through November, so wind is the first thing your installer and permit reviewer will think about: plan anchoring for your wall type and the local wind requirements. Salt air near the coast calls for corrosion-resistant fasteners, and strong sun favors colors that hold up to UV.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Florida Storefronts',
		'lead' => 'Bright lit letters plus window graphics that handle the sun.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright face lit letters for shopping plazas.',
			'standard-channel-letter-front-back-lit' => 'Face and halo glow for restaurants and bars.',
			'adhesive-window-perf' => 'Window perf that adds privacy and cuts glare.',
			'feather-angled-flag-pole' => 'Flags for busy commercial roads.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a storefront sign company near me in Florida?',
			'a' => 'We ship custom storefront signs anywhere in Florida from Renton, WA. We don&rsquo;t install, so you&rsquo;ll hire a licensed Florida sign specialty or electrical contractor near you to permit and install it.',
		),
		array(
			'q' => 'What license does a sign installer need in Florida?',
			'a' => 'A Sign Specialty electrical contractor certification from the Electrical Contractors&rsquo; Licensing Board covers electrical signs, or the work can be done by a certified or registered electrical contractor.',
		),
		array(
			'q' => 'Do hurricane rules affect my sign in Florida?',
			'a' => 'Yes. Sign permits are reviewed under the Florida Building Code, which includes wind design requirements, strictest in Miami-Dade and Broward&rsquo;s High-Velocity Hurricane Zone. Your installer accounts for this in the permit drawings and anchoring.',
		),
		array(
			'q' => 'How long does shipping to Florida take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Miami-Dade procedure sheet may be dated; confirm current categories with RER before going live.',
		'Transit days to Florida not shown until verified.',
	),
);
