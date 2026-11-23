<?php
/**
 * Hawaii state guide (/locations/hawaii/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'hawaii',
	'name' => 'Hawaii',
	'abbr' => 'HI',
	'status' => 'review',
	'neighbors' => array(),
	'seo_title' => 'Storefront Signs Shipped to Hawaii | Honolulu Sign Permits',
	'seo_description' => 'Channel letters shipped to Hawaii businesses, plus who can connect them (Board of Electricians and Plumbers licensees) and how Honolulu DPP sign permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Hawaii',
	'intro' => array(
		'Hawaii businesses can price custom LED channel letters online, have them built in Washington and California, and have them shipped to the islands, ready for a licensed Hawaii electrician.',
		'Hawaii licenses electricians through the Board of Electricians and Plumbers in the Department of Commerce and Consumer Affairs (DCCA). Its licenses include Journey Worker and Supervising Electrician, industrial and specialty versions of each, and Maintenance Electrician.',
		'On Oahu, the City and County of Honolulu&rsquo;s Department of Planning and Permitting requires a sign permit to install, construct, alter, relocate or reconstruct any sign.',
	),
	'facts' => array(
		'Licensing agency' => 'Board of Electricians and Plumbers (DCCA)',
		'Electrician licenses' => 'Journey Worker, Supervising, Specialty, Maintenance',
		'Honolulu sign permit' => 'Department of Planning and Permitting (DPP)',
		'Before you apply' => 'Confirm the use of the space is permitted',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Hawaii',
		'body' => array(
			'The <strong>Board of Electricians and Plumbers</strong> licenses Journey Worker Electricians, Supervising Electricians, Journey Worker and Supervising Industrial Electricians, Journey Worker and Supervising Specialty Electricians, and Maintenance Electricians. A Supervising Electrician needs four years of experience as a licensed journey worker electrician (or the equivalent).',
			'Electrical contracting businesses are licensed separately, through the Contractors License Board. Check both the electrician and the contractor on DCCA&rsquo;s license search before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Board of Electricians and Plumbers, DCCA Professional and Vocational Licensing',
			'Individuals' => 'Journey Worker, Supervising, Specialty and Maintenance Electricians',
			'Businesses' => 'Contractors License Board',
		),
		'sources' => array(
			array('Hawaii DCCA: Board of Electricians and Plumbers', 'https://cca.hawaii.gov/pvl/?p=432'),
			array('Hawaii Board of Electricians and Plumbers: FAQs (PDF)', 'https://cca.hawaii.gov/pvl/files/2018/09/EP-FAQs_07-18.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Hawaii',
		'body' => array(
			'Each county issues its own sign permits: Honolulu, Maui, Hawaii County and Kauai. Hawaii also bans billboards statewide, so business signs are limited to your own premises.',
			'In <strong>Honolulu</strong>, DPP requires a sign permit to install, construct, alter, relocate or reconstruct any sign. First confirm the use of the space is permitted; if it differs from the existing or previous use, a building permit for the change in use is needed before the sign permit. Plans must follow DPP&rsquo;s sign permit plan format checklist, because drawings that miss any item are rejected and must be resubmitted. Applications are prescreened, reviewed by the relevant agencies, issued and then inspected.',
		),
		'table' => array(
			'Sign permit' => 'Your county',
			'Honolulu' => 'Department of Planning and Permitting',
			'Honolulu plans' => 'Must meet the sign permit plan format checklist',
			'Billboards' => 'Not allowed statewide',
		),
		'sources' => array(
			array('City and County of Honolulu DPP: Sign permit', 'https://www.honolulu.gov/dpp/permitting/building-permits-home/permit-sign-permit/'),
		),
	),
	'climate' => array(
		'Hawaii&rsquo;s sun, salt air and humidity are tough on metal and paint, and trade winds and storms hit exposed facades. Ask your installer about corrosion-resistant hardware and anchoring for wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Hawaii Storefronts',
		'lead' => 'Lit letters for shopping centers and graphics for storefront glass.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for island shopping centers.',
			'hidden-back-halo-lit' => 'A clean halo look for restaurants and boutiques.',
			'adhesive-window-perf' => 'Window graphics that keep the view and the shade.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for visitor foot traffic.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Hawaii?',
			'a' => 'We ship custom channel letters to Hawaii from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Hawaii-licensed electrician and contractor to mount and connect it.',
		),
		array(
			'q' => 'Who can wire a lit sign in Hawaii?',
			'a' => 'An electrician licensed by the Board of Electricians and Plumbers, working for a contractor licensed by the Contractors License Board.',
		),
		array(
			'q' => 'What do I need before applying for a Honolulu sign permit?',
			'a' => 'Confirm the use of your space is permitted, then prepare plans that meet DPP&rsquo;s sign permit plan format checklist. A change in use needs a building permit first.',
		),
		array(
			'q' => 'How long does shipping to Hawaii take?',
			'a' => 'Hawaii is outside the standard mainland transit times. Checkout shows the shipping options and estimated delivery date for your address, including production.',
		),
	),
	'review_notes' => array(
		'Hawaii&rsquo;s statewide billboard ban is general knowledge; add a statute source (HRS Chapter 445, Part IV) or remove the line.',
		'Contractor classification for electrical work (C-13) came from secondary sources and is not shown.',
		'Shipping FAQ intentionally differs; confirm carrier options and transit to Hawaii before going live.',
	),
);
