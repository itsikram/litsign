<?php
/**
 * Arkansas state guide (/locations/arkansas/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'arkansas',
	'name' => 'Arkansas',
	'abbr' => 'AR',
	'status' => 'review',
	'neighbors' => array('missouri', 'tennessee', 'mississippi', 'louisiana', 'texas', 'oklahoma'),
	'seo_title' => 'Storefront Signs Shipped to Arkansas | Electrician Guide',
	'seo_description' => 'Channel letters shipped to Arkansas, plus who can connect them (Board of Electrical Examiners master and journeyman electricians) and local sign permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Arkansas',
	'intro' => array(
		'Arkansas businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Arkansas electrician.',
		'The Board of Electrical Examiners, in the Department of Labor and Licensing, licenses master, journeyman, residential master and residential journeyman electricians, plus air conditioning and industrial maintenance electricians. Residential licenses cover one and two family dwellings only, so a storefront sign is work for a master or journeyman electrician.',
		'The board also registers apprentices, who work under licensed electricians, so ask who will be on site and which license they hold.',
	),
	'facts' => array(
		'Licensing agency' => 'Arkansas Board of Electrical Examiners (Department of Labor and Licensing)',
		'For commercial sign wiring' => 'Master or journeyman electrician',
		'Residential licenses' => 'One and two family dwellings only',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Arkansas',
		'body' => array(
			'The board&rsquo;s license classes are <strong>master electrician</strong>, <strong>journeyman electrician</strong>, residential master electrician, residential journeyman electrician, air conditioning electrician and industrial maintenance electrician, and it registers apprentices. Residential licenses are limited to one and two family dwellings, and air conditioning electricians to supplying heating, air conditioning and refrigeration units.',
			'The Department of Labor and Licensing also lists a sign specialist license among its renewals, so ask your installer whether they hold it or a master or journeyman license.',
		),
		'table' => array(
			'Licensing agency' => 'Board of Electrical Examiners, Arkansas Department of Labor and Licensing',
			'Commercial work' => 'Master or journeyman electrician',
			'Residential' => 'Residential master / journeyman (1 and 2 family dwellings)',
			'Also listed' => 'Sign specialist license',
		),
		'sources' => array(
			array('Arkansas Department of Labor and Licensing: Electrical inspection and licensing', 'https://labor.arkansas.gov/labor/code-enforcement/electrical-inspection-and-licensing/'),
			array('Arkansas.gov: Board of Electrical Examiners', 'https://portal.arkansas.gov/agency/department-of-labor-and-licensing/board-of-electrical-examiners-of-the-state-of-arkansas/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Arkansas',
		'body' => array(
			'Arkansas cities and counties issue sign permits under their own zoning codes. Little Rock, Fayetteville and the other larger cities run their own planning and permit offices.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical work' => 'Licensed master or journeyman electrician',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Arkansas summers are hot and humid, and spring brings severe storms and high winds. Ask your installer about anchoring for wind and sealing wall penetrations.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Arkansas Storefronts',
		'lead' => 'Lit letters for Little Rock and Northwest Arkansas shopping centers and main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for growing Northwest Arkansas centers.',
			'standard-channel-letter-back-lit' => 'A soft glow on downtown storefronts.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Arkansas.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Arkansas.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Arkansas?',
			'a' => 'We ship custom channel letters anywhere in Arkansas from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an Arkansas-licensed electrician to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Arkansas?',
			'a' => 'For a storefront, a master or journeyman electrician license from the Board of Electrical Examiners. The state also lists a sign specialist license.',
		),
		array(
			'q' => 'Can a residential electrician wire my store sign?',
			'a' => 'No. Arkansas residential licenses cover one and two family dwellings only.',
		),
		array(
			'q' => 'How long does shipping to Arkansas take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Sign specialist license appears only in the renewal list; confirm its scope with the board and add it if it covers electric signs.',
		'Transit days to Arkansas not shown until verified.',
	),
);
