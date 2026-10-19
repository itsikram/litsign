<?php
/**
 * Idaho state guide (/locations/idaho/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'idaho',
	'name' => 'Idaho',
	'abbr' => 'ID',
	'status' => 'review',
	'neighbors' => array('washington', 'oregon', 'nevada', 'utah', 'wyoming', 'montana'),
	'seo_title' => 'Storefront Signs Shipped to Idaho | Sign Electrician Guide',
	'seo_description' => 'Channel letters shipped to Idaho, plus who can install them (Idaho sign specialty electricians and contractors) and what Boise asks for in a sign permit.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Idaho',
	'intro' => array(
		'Idaho businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Idaho electrician.',
		'Idaho treats sign work as an electrical specialty. The Idaho Electrical Board, part of the Division of Occupational and Professional Licenses (DOPL), issues specialty licenses for particular kinds of electrical work, and signs are one of them. A licensed journeyman electrician can also make sign installations without an extra license.',
		'In Boise, an on-premise sign application includes a site plan, scaled color elevations and photos, and freestanding signs need engineered plans stamped by an Idaho-licensed engineer.',
	),
	'facts' => array(
		'Licensing agency' => 'Idaho Electrical Board (DOPL)',
		'Sign license' => 'Sign electrical specialty (works for a sign electrical contractor)',
		'Also qualified' => 'Licensed journeyman electrician',
		'Sign permit' => 'Your city or county (Boise: Planning and Development Services)',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Idaho',
		'body' => array(
			'Idaho&rsquo;s specialty license rules list <strong>sign</strong> electrical work among the specialties that need their own license. A sign specialty licensee may install, maintain, repair and replace equipment, controls and wiring on the secondary side of a sign&rsquo;s disconnecting means, as long as the disconnect is on the sign or within sight of it, and must work for a licensed sign electrical contractor.',
			'Wiring on the supply side of the disconnect, such as a new circuit to the sign, is work for a journeyman electrician, who may make all installations without an additional license. Verify any license on DOPL&rsquo;s license search before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Idaho Electrical Board, Division of Occupational and Professional Licenses',
			'Sign specialty' => 'Secondary side of the sign disconnect only',
			'Employer' => 'Licensed sign electrical contractor',
			'Supply wiring' => 'Licensed journeyman electrician',
		),
		'sources' => array(
			array('Idaho DOPL: Idaho Electrical Board', 'https://dopl.idaho.gov/?p=19306'),
			array('Idaho specialty electrical license rules (archived IDAPA 07.01.04, PDF)', 'https://adminrules.idaho.gov/rules/2010/07/0104.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Idaho',
		'body' => array(
			'Idaho cities and counties issue sign permits under their own zoning codes, and electrical permits come from the city or from DOPL where the state inspects.',
			'<strong>Boise</strong>&rsquo;s on-premise sign application asks for a completed application, a letter explaining the project, a Statement of Legal Interest signed by the property owner, a detailed site plan showing all existing and proposed signs, scaled color elevations and labeled color photos. Freestanding signs need anchorage and engineered plans, details and calculations stamped and signed by an Idaho-licensed engineer; wall signs mounted flush with clear attachment details may be exempt from engineering.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Boise' => 'On-premise sign and building permit application',
			'Boise engineering' => 'Idaho-licensed engineer&rsquo;s stamp for freestanding signs',
		),
		'sources' => array(
			array('City of Boise: On-premise sign and building permit application (PDF)', 'https://www.cityofboise.org/media/17974/on-premise-sign.pdf'),
		),
	),
	'climate' => array(
		'Idaho summers are hot and dry and winters bring snow and freezing nights, with big daily temperature swings. Ask your installer to seal wall penetrations and mount letters clear of snow buildup.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Idaho Storefronts',
		'lead' => 'Lit letters for growing Treasure Valley shopping centers and main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for new Treasure Valley shopping centers.',
			'standard-channel-letter-back-lit' => 'A soft glow for downtown storefronts.',
			'feather-angled-flag-pole' => 'Flags for highway-facing businesses in Idaho.',
			'13oz-vinyl-banner' => 'Coming soon and grand opening banners in Idaho.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Idaho?',
			'a' => 'We ship custom channel letters anywhere in Idaho from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an Idaho-licensed sign electrical contractor or electrician to mount and connect it.',
		),
		array(
			'q' => 'Does Idaho have a sign electrician license?',
			'a' => 'Yes. Sign work is an electrical specialty. A sign specialty licensee works on the sign side of its disconnect and must work for a licensed sign electrical contractor.',
		),
		array(
			'q' => 'Does Boise need an engineer for my sign?',
			'a' => 'Freestanding signs need plans stamped by an Idaho-licensed engineer. Flush-mounted wall signs with clear attachment details may be exempt.',
		),
		array(
			'q' => 'How long does shipping to Idaho take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Sign specialty scope is from the archived IDAPA 07.01.04 (2010); confirm it against the current IDAPA 24.39 rules.',
		'Boise form is dated 8/2020; confirm it is current.',
		'Transit days to Idaho not shown until verified.',
	),
);
