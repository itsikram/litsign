<?php
/**
 * Minnesota state guide (/locations/minnesota/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'minnesota',
	'name' => 'Minnesota',
	'abbr' => 'MN',
	'status' => 'review',
	'neighbors' => array('wisconsin', 'iowa', 'south-dakota', 'north-dakota'),
	'seo_title' => 'Storefront Signs Shipped to Minnesota | Electrician Guide',
	'seo_description' => 'Channel letters shipped to Minnesota, plus who can wire them under the state Electrical Act (Class A master and journeyworker electricians) and local permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Minnesota',
	'intro' => array(
		'Minnesota businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Minnesota electrician.',
		'Minnesota&rsquo;s Department of Labor and Industry (DLI) licenses electrical work statewide under the Electrical Act. A Class A master electrician may perform and supervise any electrical work, and a Class A journeyworker may do any electrical work except planning or laying it out, so a commercial lit sign is work for Class A electricians at a licensed contractor.',
		'Minnesota cities set their own sign rules, and a lit sign also needs an electrical permit and inspection, from DLI or from cities that run their own electrical inspection.',
	),
	'facts' => array(
		'Licensing agency' => 'Minnesota Department of Labor and Industry (DLI)',
		'For commercial sign wiring' => 'Class A master or Class A journeyworker electrician',
		'Class B licenses' => 'Limited single-phase farm and small-town work',
		'Sign permit' => 'Your city',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Minnesota',
		'body' => array(
			'Minnesota Statutes section 326B.31 defines the license classes. A <strong>Class A master electrician</strong> is qualified to perform and supervise any electrical work; a <strong>Class A journeyworker electrician</strong> may do any electrical work except planning or laying it out; and Class B master and journeyworker licenses are limited to single-phase work in certain farm and small-town settings. A <strong>power limited technician</strong> works only on technology circuits such as signaling and controls.',
			'Hire a licensed electrical contractor with Class A electricians to connect a storefront sign, and look up the license on DLI&rsquo;s license lookup.',
		),
		'table' => array(
			'Licensing agency' => 'Minnesota Department of Labor and Industry',
			'Class A master' => 'Perform and supervise any electrical work',
			'Class A journeyworker' => 'Any electrical work except planning or layout',
			'Power limited technician' => 'Technology circuits only',
		),
		'sources' => array(
			array('Minnesota Statutes &sect; 326B.31: Electrical Act definitions', 'https://www.revisor.mn.gov/statutes/cite/326B.31'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Minnesota',
		'body' => array(
			'Minnesota cities issue sign permits under their own zoning codes, and electrical permits and inspections for a lit sign come from DLI or from cities that run their own electrical inspection.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Electrical inspection' => 'DLI, or your city where it inspects',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Minnesota winters bring deep cold, heavy snow and ice, and summers bring storms. Mount letters above the snow line and ask your installer to seal every wall penetration.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Minnesota Storefronts',
		'lead' => 'Lit letters for Twin Cities centers and main streets across the state.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for long winter evenings.',
			'hidden-back-halo-lit' => 'A polished halo look for Twin Cities offices.',
			'adhesive-window-perf' => 'Window graphics for skyway-level and street storefronts.',
			'13oz-vinyl-banner' => 'Seasonal and grand opening banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Minnesota?',
			'a' => 'We ship custom channel letters anywhere in Minnesota from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed Minnesota electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Minnesota?',
			'a' => 'For commercial work, a Class A master or Class A journeyworker electrician license from DLI, working for a licensed contractor.',
		),
		array(
			'q' => 'Who inspects the electrical work on my sign?',
			'a' => 'DLI, or your city if it runs its own electrical inspection program.',
		),
		array(
			'q' => 'How long does shipping to Minnesota take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Minnesota once proposed a "sign installer" license (2007 bill); confirm with DLI whether any sign-specific license or exemption exists today.',
		'Minneapolis appears to license sign contractors (city contractor pages); confirm and add the city detail.',
		'Transit days to Minnesota not shown until verified.',
	),
);
