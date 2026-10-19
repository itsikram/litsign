<?php
/**
 * West Virginia state guide (/locations/west-virginia/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'west-virginia',
	'name' => 'West Virginia',
	'abbr' => 'WV',
	'status' => 'review',
	'neighbors' => array('ohio', 'pennsylvania', 'maryland', 'virginia', 'kentucky'),
	'seo_title' => 'Storefront Signs Shipped to West Virginia | Electrician Guide',
	'seo_description' => 'Channel letters shipped to West Virginia, plus who can wire them (State Fire Marshal electricians and licensed electrical contractors) and local permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to West Virginia',
	'intro' => array(
		'West Virginia businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed West Virginia electrician.',
		'West Virginia licenses both the person and the business. The State Fire Marshal licenses master and journeyman electricians, and the West Virginia Contractor Licensing Board licenses electrical contractors, with an electrical specialty classification for electrical contracting projects above its dollar threshold.',
		'Helpers and apprentices don&rsquo;t need a license, but a licensed master or journeyman must supervise them at all times.',
	),
	'facts' => array(
		'Electrician licenses' => 'State Fire Marshal: Master, Journeyman',
		'Contractor license' => 'WV Contractor Licensing Board, electrical specialty',
		'Helpers' => 'Supervised by a master or journeyman',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in West Virginia',
		'body' => array(
			'The <strong>State Fire Marshal</strong> licenses <strong>journeyman</strong> and <strong>master</strong> electricians under W. Va. Code &sect; 29-3B, and may issue additional specialty classes. A journeyman needs at least 2,000 hours of hands-on electrical experience and a master at least 4,000 hours. Exams are based on the 2023 National Electrical Code as of January 1, 2026.',
			'The business doing the work needs a <strong>West Virginia contractor license</strong> with the electrical specialty classification for projects above the board&rsquo;s threshold. Check both licenses before you hire.',
		),
		'table' => array(
			'Electricians' => 'State Fire Marshal (Master, Journeyman)',
			'Contractors' => 'WV Contractor Licensing Board, electrical specialty',
			'Experience' => 'Journeyman 2,000 hours; master 4,000 hours',
			'Helpers' => 'No license; supervised by a master or journeyman',
		),
		'sources' => array(
			array('West Virginia State Fire Marshal: Electrical licensing', 'https://firemarshal.wv.gov/Divisions/Fire%20Services/Pages/Electrical-Licensing.aspx'),
			array('West Virginia State Fire Marshal: Regulatory and licensing division', 'https://firemarshal.wv.gov/fm-divisions/regulatory-licensing'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in West Virginia',
		'body' => array(
			'West Virginia cities issue sign permits under their own zoning codes, and many smaller towns and rural counties have few sign rules, so check with the local planning or code office at your address.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical work' => 'Licensed electrician and contractor',
		),
		'sources' => array(),
	),
	'climate' => array(
		'West Virginia winters bring snow and ice in the mountains, and summers are humid with strong storms. Ask your installer to seal wall penetrations and plan for wind on exposed hillside facades.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for West Virginia Storefronts',
		'lead' => 'Lit letters for main streets and highway towns.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters that read from the highway in West Virginia.',
			'standard-channel-letter-back-lit' => 'A warm glow on downtown brick in West Virginia.',
			'feather-angled-flag-pole' => 'Flags for roadside visibility in West Virginia.',
			'13oz-vinyl-banner' => 'Grand opening and event banners in West Virginia.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in West Virginia?',
			'a' => 'We ship custom channel letters anywhere in West Virginia from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed West Virginia electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What licenses does a sign installer need in West Virginia?',
			'a' => 'The electricians need State Fire Marshal licenses (master or journeyman), and the business needs a contractor license with the electrical specialty.',
		),
		array(
			'q' => 'Can helpers do electrical work in West Virginia?',
			'a' => 'Helpers don&rsquo;t need a license, but they must be supervised at all times by a licensed master or journeyman electrician.',
		),
		array(
			'q' => 'How long does shipping to West Virginia take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The $2,500 contractor threshold is from a secondary summary; confirm with the WV Contractor Licensing Board.',
		'Add Charleston sign permit steps once confirmed.',
		'Transit days to West Virginia not shown until verified.',
	),
);
