<?php
/**
 * Utah state guide (/locations/utah/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ut_hb28 = 'https://le.utah.gov/Session/2025/bills/enrolled/HB0028.pdf';
$slc_form = 'https://www.slcdocs.com/building/f-sign-permit.pdf';

return array(
	'slug' => 'utah',
	'name' => 'Utah',
	'abbr' => 'UT',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Utah',
	'neighbors' => array('idaho', 'wyoming', 'colorado', 'arizona', 'nevada'),
	'seo_title' => 'Channel Letters Shipped to Utah | DOPL Sign License Guide',
	'seo_description' => 'Channel letters shipped to Utah: the S440 sign license, the HB 28 wiring exemption, and Salt Lake City sign and electrical permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Utah',
	'intro' => array(
		'Utah businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Utah contractor. Banners and window graphics are printed in Renton, WA.',
		'Utah&rsquo;s Division of Professional Licensing (DOPL) has a contractor classification for sign work, S440 Sign Installation Contractor, whose scope excludes electrical trade work. A 2025 law, HB 28, then carved out on-premise signs: minor electrical work incidental to installing, maintaining or repairing them is exempt from licensing, within tight limits on distance, voltage and amperage.',
		'In Salt Lake City, the sign permit covers the sign itself, and the feeder or branch circuit wiring that serves it needs a separate electrical permit pulled by a licensed electrical contractor.',
	),
	'facts' => array(
		'Licensing agency' => 'Utah Division of Professional Licensing (DOPL)',
		'Sign contractor' => 'S440 Sign Installation Contractor (no electrical trade work)',
		'HB 28 (2025)' => 'Minor sign wiring: within 10 ft of an existing outlet, 120 V / 20 A',
		'Salt Lake City' => 'Sign permit + separate electrical permit for the circuit',
	),
	'categories' => array(
		'channel-letters' => 'Under Utah&rsquo;s 2025 HB 28, connecting an on-premise sign can be exempt minor electrical work when wiring runs no more than 10 feet from an existing outlet or disconnect at 120 volts and 20 amps or less.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Utah',
		'body' => array(
			'DOPL licenses contractors by classification. The <strong>S440 Sign Installation Contractor</strong> classification covers installing signs, but a 2017 rule filing states its scope does not include installation of any electrical trade work. A separate <strong>S441</strong> classification covers non-electrical outdoor advertising signs, and electricians and electrical contractors are licensed under their own classifications.',
			'<strong>HB 28, On Premise Sign Installation Amendments (2025)</strong>, changed two sections of the Construction Trades Licensing Act. It added to the exemptions from licensure a person doing minor electrical work incidental to a mechanical or service installation, including the installation, maintenance or repair of on-premise signs. And it took out of the &ldquo;electrical trade&rdquo; minor work incidental to a mechanical or service installation where wiring is extended <strong>no more than 10 feet from an existing outlet or disconnect</strong> and doesn&rsquo;t exceed <strong>120 volts and 20 amps</strong>. Anything beyond that, such as a new circuit from the panel, still needs a licensed electrical contractor.',
		),
		'table' => array(
			'Licensing agency' => 'Utah Division of Professional Licensing',
			'Sign installation' => 'S440 (excludes electrical trade work)',
			'Outdoor advertising (non-electrical)' => 'S441',
			'Exempt sign wiring (HB 28)' => 'Up to 10 ft from an existing outlet or disconnect; 120 V, 20 A max',
			'New circuits' => 'Licensed electrical contractor',
		),
		'sources' => array(
			array('Utah DAR File No. 42086 (2017): S440 scope excludes electrical work', 'https://rules.utah.gov/publicat/bulletin/2017/20171001/42086.htm'),
			array('Utah Legislature: HB 28 (2025) On Premise Sign Installation Amendments (PDF)', $ut_hb28),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Utah',
		'body' => array(
			'Utah cities and counties issue sign permits under their own codes, and the sign allowance is often tied to the length of your storefront.',
			'<strong>Salt Lake City</strong>&rsquo;s Building Services sign permit application is filed with the sign contractor&rsquo;s license number and three sets of plans, including site plans, details, elevations and engineering. Its worksheets compute the allowed sign area from the zone&rsquo;s size ratio and your lot and store frontage. Approval includes zoning, the plans examiner and, where it applies, historic review, and footing inspections are required before installation, with a final inspection for every sign.',
			'The form also states that a separate electrical permit is needed for the feeder or branch circuit wiring to sign equipment, obtained by a licensed electrical contractor, and that any work on public property needs its own permit. A permit or license issued for a sign that conflicts with city ordinances is void, whoever issued it.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Salt Lake City plans' => 'Three sets: site plan, details, elevations, engineering',
			'Salt Lake City approvals' => 'Zoning, plans examiner and historic review where it applies',
			'Salt Lake City inspections' => 'Footing inspection before installation; final inspection',
			'Salt Lake City electrical' => 'Separate permit by a licensed electrical contractor',
		),
		'sources' => array(
			array('Salt Lake City: Sign permit application (PDF)', $slc_form),
		),
	),
	'climate' => array(
		'Utah&rsquo;s high-desert sun is strong, Wasatch Front winters bring lake-effect snow and long inversions, and canyon winds can gust hard along the benches. Choose colors that hold up to UV, and ask your installer about anchoring for wind and snow.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Utah Storefronts',
		'lead' => 'Lit letters for Wasatch Front shopping centers and downtown blocks.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Wasatch Front shopping centers.',
			'hidden-back-halo-lit' => 'A polished halo look for offices and clinics in Utah.',
			'feather-angled-flag-pole' => 'Flags for busy suburban roads in Utah.',
			'13oz-vinyl-banner' => 'Coming soon and grand opening banners in Utah.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Utah?',
			'a' => 'We ship custom channel letters anywhere in Utah, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed Utah contractor to mount the sign and, where needed, a licensed electrical contractor to connect it.',
		),
		array(
			'q' => 'Does a Utah sign installer need an electrician to connect the sign?',
			'a' => 'Not always since HB 28 (2025). Minor electrical work for installing an on-premise sign is exempt when wiring runs no more than 10 feet from an existing outlet or disconnect at up to 120 volts and 20 amps. A new circuit still needs a licensed electrical contractor.',
		),
		array(
			'q' => 'What does the S440 sign license cover?',
			'a' => 'Installing signs. DOPL&rsquo;s S440 Sign Installation Contractor classification excludes electrical trade work, and S441 covers non-electrical outdoor advertising signs, so the structure and the wiring can fall under different licenses unless the HB 28 exemption applies. Check the classification on DOPL&rsquo;s license lookup.',
		),
		array(
			'q' => 'Who pulls the electrical permit for a sign in Salt Lake City?',
			'a' => 'A licensed electrical contractor, for the feeder or branch circuit wiring that serves the sign. The sign permit itself is filed by the sign contractor with three sets of plans, and footing and final inspections are required. Work on public property needs its own permit.',
		),
		array(
			'q' => 'How long does shipping to Utah take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'HB 28 (2025) read 2026-10-27 from the Internet Archive copy of 2026-05-26 (le.utah.gov refuses connections from the research PC). The page combines its two changes (the 58-55-305 exemption for on-premise sign work and the 10 ft / 120 V / 20 A limit in 58-55-102); confirm with DOPL that both apply together.',
		'The Salt Lake City sign permit form is marked "Updated 08/2012"; confirm the current form before approval.',
		'Transit days to Utah not shown until verified.',
	),
);
