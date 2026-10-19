<?php
/**
 * Iowa state guide (/locations/iowa/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'iowa',
	'name' => 'Iowa',
	'abbr' => 'IA',
	'status' => 'review',
	'neighbors' => array('minnesota', 'wisconsin', 'illinois', 'missouri', 'nebraska', 'south-dakota'),
	'seo_title' => 'Storefront Signs Shipped to Iowa | Sign Installation License',
	'seo_description' => 'Channel letters shipped to Iowa, plus the special electrician sign installation endorsement, its limits, and who must run new circuits to your sign.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Iowa',
	'intro' => array(
		'Iowa businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Iowa installer.',
		'Iowa writes sign installation into its electrician law. Iowa Code chapter 103 defines a <strong>special electrician</strong> as someone licensed for special classes of electrical work, and sign installation is one of them, along with irrigation wiring and reconnecting air conditioning and refrigeration.',
		'The sign endorsement covers connecting the sign, not the circuit that feeds it: installing or upgrading the branch circuit that supplies the sign is work for a licensed master electrician, or a journeyman under one.',
	),
	'facts' => array(
		'Licensing agency' => 'Iowa Electrical Examining Board',
		'Sign license' => 'Special electrician, sign installation endorsement',
		'Endorsement limit' => 'Signs up to 220 volts and 20 amps',
		'Branch circuits' => 'Licensed master (or supervised journeyman) electrician',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Iowa',
		'body' => array(
			'Under Iowa&rsquo;s rules, a special electrician license can carry one or more endorsements, and the <strong>sign installation</strong> endorsement allows the holder to connect signs to electrical systems. The board&rsquo;s form says it does not authorize connecting power to a sign rated over 220 volts and 20 amps, and that the initial installation or upgrade of branch circuits supplying the sign must be done by a licensed electrician.',
			'The administrative rule adds that the branch-circuit work may only be done by a licensed master electrician, or a licensed journeyman electrician supervised by one. The board&rsquo;s form lists no exam or experience requirement for the sign endorsement.',
		),
		'table' => array(
			'Licensing agency' => 'Iowa Electrical Examining Board',
			'Sign license' => 'Special electrician with sign installation endorsement',
			'Limit' => 'Not over 220 volts and 20 amps',
			'Branch circuits' => 'Master electrician, or journeyman under a master',
		),
		'sources' => array(
			array('Iowa Code chapter 103: Electricians and electrical contractors', 'https://www.legis.iowa.gov/docs/code/2026/103.html'),
			array('Iowa Admin. Code r. 661-502.2: License requirements', 'https://www.law.cornell.edu/regulations/iowa/Iowa-Admin-Code-r-661-502-2'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Iowa',
		'body' => array(
			'Iowa cities and counties issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit from the city or from the state where the state inspects. Des Moines, Cedar Rapids and the other larger cities run their own permit offices.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical permit' => 'City, or the state where it inspects',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Iowa winters are cold and snowy, and summer storms bring hail and high winds across open country. Ask your installer about anchoring for wind and sealing every wall penetration.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Iowa Storefronts',
		'lead' => 'Lit letters for main streets and shopping centers.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Readable lit letters for shopping centers.',
			'standard-channel-letter-back-lit' => 'A warm glow on main street brick.',
			'feather-angled-flag-pole' => 'Flags for highway-facing businesses in Iowa.',
			'18oz-blockout-banner' => 'Heavy banners for wind and weather in Iowa.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Iowa?',
			'a' => 'We ship custom channel letters anywhere in Iowa from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an Iowa-licensed electrician or sign installer to mount and connect it.',
		),
		array(
			'q' => 'Does Iowa have a sign installer license?',
			'a' => 'Yes. A special electrician license with the sign installation endorsement lets the holder connect signs, up to 220 volts and 20 amps.',
		),
		array(
			'q' => 'Who runs the circuit to my sign in Iowa?',
			'a' => 'A licensed master electrician, or a journeyman supervised by one. The sign endorsement doesn&rsquo;t cover installing or upgrading the branch circuit.',
		),
		array(
			'q' => 'How long does shipping to Iowa take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The 220 V / 20 A limit and "no exam" note are from the board&rsquo;s affidavit form on dial.iowa.gov; confirm they are current.',
		'Add Des Moines sign permit steps once confirmed on the city site.',
		'Transit days to Iowa not shown until verified.',
	),
);
