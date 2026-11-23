<?php
/**
 * Vermont state guide (/locations/vermont/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'vermont',
	'name' => 'Vermont',
	'abbr' => 'VT',
	'status' => 'review',
	'neighbors' => array('new-hampshire', 'massachusetts', 'new-york'),
	'seo_title' => 'Storefront Signs Shipped to Vermont | License Guide',
	'seo_description' => 'Channel letters shipped to Vermont businesses, plus who can connect them under Division of Fire Safety electrician licensing and how local sign rules work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Vermont',
	'intro' => array(
		'Vermont businesses can order custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Vermont electrician.',
		'Vermont licenses electricians through the Division of Fire Safety in the Department of Public Safety, with Master (EM), Journeyman (EJ) and Specialist (ES) licenses. Specialist licenses are limited to particular fields and don&rsquo;t allow general electrical work, so a lit sign is connected by a licensed master or journeyman electrician.',
		'Vermont is also one of the few states that bans billboards outright. Business signs on your own premises are allowed, but state law and your town&rsquo;s zoning both limit their size and placement, so check with your town zoning administrator early.',
	),
	'facts' => array(
		'Licensing agency' => 'Vermont Division of Fire Safety (Department of Public Safety)',
		'Licenses' => 'Master (EM), Journeyman (EJ), Specialist (ES)',
		'Billboards' => 'Banned statewide; on-premise signs allowed within limits',
		'Sign permit' => 'Your town zoning administrator',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Vermont',
		'body' => array(
			'Vermont&rsquo;s electrical licenses are issued through the Division of Fire Safety. A <strong>Master Electrician</strong> must have worked as a licensed journeyman for at least two years (or show comparable experience) and pass an exam. <strong>Journeyman</strong> electricians do general electrical work, and <strong>Specialist</strong> electricians are limited to defined fields such as fire alarms or lightning rods and can&rsquo;t do general electrical work.',
			'Electrical installations in commercial buildings in Vermont are generally permitted and inspected through the Division of Fire Safety, so your electrician will handle the electrical side of a lit sign with the state.',
		),
		'table' => array(
			'Licensing agency' => 'Vermont Division of Fire Safety, Electrical Licensing',
			'For sign wiring' => 'Master (EM) or Journeyman (EJ) electrician',
			'Specialist (ES)' => 'Limited fields only; not general electrical work',
			'Rules' => 'Vermont Electrical Safety Rules',
		),
		'sources' => array(
			array('Vermont Division of Fire Safety', 'https://firesafety.vermont.gov/'),
			array('Vermont Electrical Safety Rules (24-040-001)', 'https://www.law.cornell.edu/regulations/vermont/24-001-Code-Vt-R-24-040-001-X'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Vermont',
		'body' => array(
			'Vermont law bans off-premise billboards and limits on-premise signs, and each town adds its own zoning rules on top. Many town plans favor modest sign sizes and shielded lighting, so ask the zoning administrator what your district allows before you choose a letter height.',
		),
		'table' => array(
			'Sign permit' => 'Your town zoning administrator',
			'State sign law' => '10 V.S.A. Chapter 21 (billboard ban, on-premise sign limits)',
		),
		'sources' => array(
			array('Vermont Statutes, Title 10, Chapter 21: Tourist Information Services (signs)', 'https://legislature.vermont.gov/statutes/chapter/10/021'),
		),
	),
	'climate' => array(
		'Vermont winters are long and snowy, with deep cold and freeze-thaw cycles. Mount letters above the snow line, and ask your installer to seal every wall penetration on wood and brick storefronts.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Vermont Storefronts',
		'lead' => 'Understated lit signs for village centers, plus seasonal signs for visitors.',
		'products' => array(
			'hidden-back-halo-lit' => 'A soft halo glow that suits Vermont village centers.',
			'standard-channel-letter-back-lit' => 'Back lit letters for a gentle evening glow.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for ski and leaf-peeping season.',
			'13oz-vinyl-banner' => 'Seasonal and event banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Vermont?',
			'a' => 'We ship custom channel letters anywhere in Vermont from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Vermont-licensed electrician to mount and connect it.',
		),
		array(
			'q' => 'Who can wire a lit sign in Vermont?',
			'a' => 'A licensed master or journeyman electrician. Vermont&rsquo;s specialist licenses are limited to specific fields and don&rsquo;t cover general electrical work.',
		),
		array(
			'q' => 'Are business signs allowed in Vermont?',
			'a' => 'Yes. Vermont bans billboards, but signs on your own business premises are allowed within state limits and your town&rsquo;s zoning rules.',
		),
		array(
			'q' => 'How long does shipping to Vermont take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Confirm 10 V.S.A. Chapter 21 is the current billboard / on-premise sign chapter and its on-premise size limits.',
		'Confirm that commercial electrical permits and inspections run through the Division of Fire Safety.',
		'Add Burlington&rsquo;s sign permit process once confirmed on the city site.',
		'Transit days to Vermont not shown until verified.',
	),
);
