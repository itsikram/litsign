<?php
/**
 * Minnesota state guide (/locations/minnesota/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$mpls_permit = 'https://www.minneapolismn.gov/business-services/planning-zoning/applications-handouts/land-use-applications/sign-permit/';
$mpls_hanger = 'https://www.minneapolismn.gov/business-services/licenses-permits-inspections/business-licenses/contractors/trades/sign-hanger-painter/';

return array(
	'slug' => 'minnesota',
	'name' => 'Minnesota',
	'abbr' => 'MN',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Minnesota',
	'neighbors' => array('wisconsin', 'iowa', 'south-dakota', 'north-dakota'),
	'seo_title' => 'Channel Letters Shipped to Minnesota | Minneapolis Permits',
	'seo_description' => 'Channel letters shipped to Minnesota: Class A electricians, state inspection, and Minneapolis sign hanger licenses and permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Minnesota',
	'intro' => array(
		'Minnesota businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Minnesota electrician and sign installer. Banners and window graphics are printed in Renton, WA.',
		'Minnesota licenses electrical work statewide through the Department of Labor and Industry (DLI) under the Electrical Act, and every new electrical installation is inspected, either by the state or by a city that runs its own equivalent inspection program.',
		'Minneapolis adds a layer for the sign itself: only city-licensed sign hangers may install a sign or apply for the sign permit, and every sign permit is approved by the city&rsquo;s Zoning Administration.',
	),
	'facts' => array(
		'Licensing agency' => 'Minnesota Department of Labor and Industry (DLI)',
		'Commercial sign wiring' => 'Class A master or Class A journeyworker electrician',
		'Electrical inspection' => 'Required for every new installation (Minn. Stat. &sect; 326B.36)',
		'Minneapolis' => 'Sign hanger license; Zoning approves every sign permit',
	),
	'categories' => array(
		'channel-letters' => 'In Minneapolis the sign permit can only be filed by a city-licensed sign hanger, so choose an installer who holds that license as well as working with a licensed electrician.',
		'banners' => 'Minneapolis reviews temporary signs mounted on a building or roof, including cloth, fabric and plastic banners for special events, through its Zoning Administrator.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Minnesota',
		'body' => array(
			'Minnesota Statutes section 326B.31 defines the license classes. A <strong>Class A master electrician</strong> is qualified to perform and supervise any electrical work, and a <strong>Class A journeyworker electrician</strong> may do any electrical work except planning or laying it out. Class B master and journeyworker licenses are limited to single-phase work in certain farm and small-town settings, and a <strong>power limited technician</strong> works only on technology circuits such as signaling and controls.',
			'Inspection is built into the law. Under section 326B.36, every new electrical installation, whether construction, remodeling, replacement or repair, must be inspected by the commissioner, unless the city or county has adopted its own equivalent inspection ordinance; only minor repair work, as defined by rule, is exempt, and new sign wiring isn&rsquo;t minor repair. So the lit sign&rsquo;s circuit gets a permit and an inspection either from DLI or from your city.',
		),
		'table' => array(
			'Licensing agency' => 'Minnesota Department of Labor and Industry',
			'Class A master' => 'Perform and supervise any electrical work',
			'Class A journeyworker' => 'Any electrical work except planning or layout',
			'Inspection' => 'DLI, or a city with its own equivalent program',
		),
		'sources' => array(
			array('Minnesota Statutes &sect; 326B.31: Electrical Act definitions', 'https://www.revisor.mn.gov/statutes/cite/326B.31'),
			array('Minnesota Statutes &sect; 326B.36: Inspection', 'https://www.revisor.mn.gov/statutes/cite/326B.36'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Minnesota',
		'body' => array(
			'Minnesota cities issue sign permits under their own zoning codes, so the size, height and lighting limits in Minneapolis, St. Paul, Rochester and Duluth all differ.',
			'In <strong>Minneapolis</strong>, a sign permit can only be issued to a City-licensed sign contractor, and every sign permit must be approved by the Zoning Administration office before installation. The installer&rsquo;s license is the <strong>sign hanger</strong> license, the trade license for people who install, fix or remove signs on a building; it requires an $8,000 bond from the city or the state. Applications and the sign permit fee schedule are on the city&rsquo;s sign permit page.',
			'Temporary signs and banners mounted on a building or roof, made of cloth, canvas, fabric, nylon or plastic, are reviewed by the Zoning Administrator too, so contact Zoning before an opening or event banner goes up.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Minneapolis applicant' => 'City-licensed sign hanger (sign contractor)',
			'Minneapolis approval' => 'Zoning Administration, for every sign permit',
			'Sign hanger bond' => '$8,000, from the city or the state',
			'Electrical' => 'Permit and inspection from DLI or your city',
		),
		'sources' => array(
			array('City of Minneapolis: Sign permit', $mpls_permit),
			array('City of Minneapolis: Sign hanger or sign painter license', $mpls_hanger),
			array('City of Minneapolis: Signs, banners and building wrap permits', 'https://www.minneapolismn.gov/business-services/licenses-permits-inspections/special-temporary-events/signs-banners'),
		),
	),
	'climate' => array(
		'Minnesota winters run long, with stretches below zero, wind-driven snow and ice dams, and spring thaws that soak masonry. Mount letters high enough to clear plowed snowbanks, and ask your installer to seal every wall penetration so meltwater can&rsquo;t freeze behind the raceway.',
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
			'a' => 'We ship custom channel letters anywhere in Minnesota, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed Minnesota electrical contractor and, where your city requires one, a licensed sign installer.',
		),
		array(
			'q' => 'What license does a sign electrician need in Minnesota?',
			'a' => 'For commercial work, a Class A master or Class A journeyworker electrician license from DLI, working for a licensed electrical contractor. Class B licenses are limited to single-phase work in certain farm and small-town settings, and power limited technicians only handle technology circuits.',
		),
		array(
			'q' => 'Who can pull a sign permit in Minneapolis?',
			'a' => 'Only a City-licensed sign contractor. Minneapolis issues sign permits only to licensed sign hangers, who carry an $8,000 bond from the city or the state, and every permit is approved by Zoning Administration before the sign goes up. The application and fee schedule are on the city&rsquo;s sign permit page.',
		),
		array(
			'q' => 'Does the electrical work on my sign get inspected?',
			'a' => 'Yes. Minnesota law requires every new electrical installation to be inspected, by DLI or by your city if it has adopted an equivalent inspection ordinance. Only minor repair work, as defined by rule, is exempt, and a new sign circuit doesn&rsquo;t qualify.',
		),
		array(
			'q' => 'How long does shipping to Minnesota take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Minneapolis sign permit and banner pages (updated June 30, 2026) and sign hanger page (updated September 3, 2026) read directly 2026-10-27; the $8,000 bond: re-check yearly.',
		'Minneapolis window-sign and wall-sign size rules were not verified in the current zoning code and are not shown.',
		'No sign-specific state electrical license or exemption found in Minn. Stat. 326B.31&ndash;326B.36 (the 2007 "sign installer" bill did not become law as far as found).',
		'Transit days to Minnesota not shown until verified.',
	),
);
