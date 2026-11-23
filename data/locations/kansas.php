<?php
/**
 * Kansas state guide (/locations/kansas/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'kansas',
	'name' => 'Kansas',
	'abbr' => 'KS',
	'status' => 'review',
	'neighbors' => array('nebraska', 'missouri', 'oklahoma', 'colorado'),
	'seo_title' => 'Storefront Signs Shipped to Kansas | Local License Guide',
	'seo_description' => 'Channel letters shipped to Kansas. Electrical licenses are issued locally, such as by MABCD in Wichita and Sedgwick County, so check your local rules.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Kansas',
	'intro' => array(
		'Kansas businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed local electrician.',
		'In Kansas, electrical licenses are issued by local governments rather than a state board. In Wichita and the rest of Sedgwick County, the Metropolitan Area Building and Construction Department (MABCD) issues master and journeyman trade certificates and trade contractor business licenses.',
		'Because licensing is local, a contractor licensed in one Kansas city may need a separate license in the next, so confirm your installer is licensed where your business is.',
	),
	'facts' => array(
		'State electrical license' => 'None; issued locally',
		'Wichita / Sedgwick County' => 'MABCD (Metropolitan Area Building and Construction Department)',
		'MABCD credentials' => 'Master or journeyman trade certificate; trade contractor license',
		'Sign permit' => 'Your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Kansas',
		'body' => array(
			'In <strong>Wichita and Sedgwick County</strong>, MABCD&rsquo;s electrical division handles master and journeyman trade certificates, the trade contractor business license and electrical permits, and enforces the National Electrical Code as adopted with local amendments. Certificates expire December 31 of each odd-numbered year.',
			'Other Kansas cities and counties run their own licensing, so call the building department for your address to confirm what your installer needs.',
		),
		'table' => array(
			'State license' => 'None statewide',
			'Wichita / Sedgwick County' => 'MABCD trade certificate and trade contractor license',
			'MABCD renewal' => 'Certificates expire Dec. 31 of odd-numbered years',
		),
		'sources' => array(
			array('MABCD: Electrical and alarm division', 'https://www.sedgwick.gov/mabcd/inspection-divisions/electrical-alarm-division/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Kansas',
		'body' => array(
			'Kansas cities and counties issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit pulled by a contractor licensed in that jurisdiction.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Electrical permit' => 'Locally licensed contractor',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Kansas weather brings strong winds, hail and severe spring storms, plus hot summers and icy winters. Ask your installer about anchoring for high wind on exposed facades.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Kansas Storefronts',
		'lead' => 'Lit letters for Wichita, Kansas City-area and small-town storefronts.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters that read from busy roads.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for maximum nighttime presence in Kansas.',
			'feather-angled-flag-pole' => 'Flags for highway frontage in Kansas.',
			'18oz-blockout-banner' => 'Heavy banners built for prairie wind.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Kansas?',
			'a' => 'We ship custom channel letters anywhere in Kansas from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a locally licensed electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'Does Kansas have a state electrician license?',
			'a' => 'No. Electrical licenses are issued locally. In Wichita and Sedgwick County, MABCD issues trade certificates and contractor licenses.',
		),
		array(
			'q' => 'Who issues electrical permits in Wichita?',
			'a' => 'MABCD&rsquo;s electrical division, for Wichita and the rest of Sedgwick County.',
		),
		array(
			'q' => 'How long does shipping to Kansas take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'State law sets minimum journeyman requirements (K.S.A. 12-1526 per a secondary source); confirm and mention if accurate.',
		'Add Wichita sign permit steps once confirmed with MABCD / city planning.',
		'Transit days to Kansas not shown until verified.',
	),
);
