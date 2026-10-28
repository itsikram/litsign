<?php
/**
 * Texas state guide (/locations/texas/). See washington.php for the rules of
 * these files. City guides live in data/locations/texas/{city}.php.
 *
 * Houston Permitting Center pages were read from Internet Archive copies (the
 * site blocks the research PC); see texas/houston.php.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$houston_signs = 'https://www.houstonpermittingcenter.org/building-code-enforcement/sign-administration';
$san_antonio_signs = 'https://www.sa.gov/Directory/Departments/DSD/Constructing/Business-Commercial/Permit-Process/Sign-Billboard';
$austin_signs = 'https://www.austintexas.gov/page/sign-permits';

return array(
	'slug' => 'texas',
	'name' => 'Texas',
	'abbr' => 'TX',
	'status' => 'live',
	'updated' => '2026-10-22',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Texas',
	'neighbors' => array('new-mexico', 'oklahoma', 'arkansas', 'louisiana'),
	'seo_title' => 'Channel Letters Shipped to Texas | TDLR Sign License Guide',
	'seo_description' => 'Channel letters, banners and window graphics shipped to Texas from WA and CA, with TDLR sign electrician licensing and city permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Texas',
	'intro' => array(
		'Texas businesses can price custom LED channel letters online, have them built in Washington and California, and get them shipped to the storefront, ready for a licensed Texas sign installer. Banners, window graphics and flags are made in Renton, WA and ship the same way.',
		'Texas is one of the few states that licenses sign electricians as a trade of their own. The Texas Department of Licensing and Regulation (TDLR) licenses Electrical Sign Contractors and Master, Journeyman and Apprentice sign electricians. Sign permits come from each city, and the largest cities run their own systems on top of the state license: Houston licenses sign contractors itself, and Austin has applicants register before they can file.',
		'Strip centers across Texas often spell out the sign in the lease: letter height, colors and mounting. Get the criteria from your landlord before you design.',
	),
	'facts' => array(
		'Licensing agency' => 'Texas Department of Licensing and Regulation (TDLR)',
		'Business license' => 'Electrical Sign Contractor',
		'Individual licenses' => 'Master / Journeyman Sign Electrician, Sign Apprentice',
		'Sign permit' => 'Your city (e.g. Houston Sign Administration)',
	),
	'categories' => array(
		'channel-letters' => 'Wiring a lit sign is TDLR-licensed work in Texas, so line up the installer before the letters ship: in Houston and San Antonio, the licensed sign contractor is also the one who files the city permit.',
		'banners' => 'Banner rules are set city by city. Houston allows one permitted limited-use banner of up to 40 square feet for seven days at a time, and San Antonio lists banners as their own sign permit type.',
		'flags-aframes' => 'Check before anything goes on the sidewalk: San Antonio doesn&rsquo;t allow portable signs, and Houston issues no new permits for them.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Texas',
		'body' => array(
			'TDLR licenses electrical sign work statewide. A business that designs, installs, connects or services electric signs, including neon and outline tubing, holds an <strong>Electrical Sign Contractor</strong> license. It must employ a licensed Master Electrician or Master Sign Electrician and keep liability insurance of at least $300,000 per occurrence, $600,000 in aggregate and $300,000 for products and completed operations. A general Electrical Contractor can also connect a sign.',
			'The people doing the work hold individual licenses: <strong>Master Sign Electrician</strong> (12,000 hours of on-the-job training under a Texas master, plus an exam), <strong>Journeyman Sign Electrician</strong> or <strong>Electrical Sign Apprentice</strong>. TDLR electrician licenses are renewed every year, so check that your installer&rsquo;s license is current on TDLR&rsquo;s license search.',
			'Big cities add their own layer. Houston requires a City sign contractor license with insurance and a removal bond, and Austin requires an Outdoor Advertising Registration and has electrical sign work done by a registered Electrical Sign Contractor.',
		),
		'table' => array(
			'Licensing agency' => 'Texas Department of Licensing and Regulation (TDLR)',
			'Business' => 'Electrical Sign Contractor (or Electrical Contractor), with a master on staff',
			'Insurance' => '$300,000 per occurrence, $600,000 aggregate, $300,000 products and completed operations',
			'Individuals' => 'Master Sign Electrician, Journeyman Sign Electrician, Electrical Sign Apprentice',
			'Renewal' => 'Every year',
			'Check a license' => 'TDLR license search',
		),
		'sources' => array(
			array('TDLR: Electrical Sign Contractor license', 'https://www.tdlr.texas.gov/electricians/apply/businesses/contractor-sign.htm'),
			array('TDLR: Master Sign Electrician license', 'https://www.tdlr.texas.gov/electricians/apply/individuals/master-sign.htm'),
			array('TDLR: License search', 'https://www.tdlr.texas.gov/LicenseSearch/'),
			array('City of Austin: Sign permits (registration and electrical sign work)', $austin_signs),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Texas',
		'body' => array(
			'Texas has no statewide permit for business signs. Each city issues its own under its own sign ordinance, and what it allows changes with the zoning district, the street and the property. The three largest permit systems show how much they differ:',
			'<strong>Houston</strong> permits every business sign through Sign Administration, and only to licensed sign contractors filing online. New signs more than 8 feet above the ground or over 60 square feet need a drawing certified by a Texas-registered professional engineer. Houston&rsquo;s sign code also reaches past the city limits into its extraterritorial jurisdiction, so a business just outside Houston may still need a Houston permit.',
			'<strong>San Antonio</strong> requires a sign plan review and permit from Development Services for freestanding signs, electric signs and any sign over 32 square feet, and only licensed sign contractors can request one. Monument signs over 8 feet and pole signs over 12 feet need sealed engineering. <strong>Austin</strong> requires a sign permit for outdoor signs under Chapter 25-10 of its Land Development Code; lit signs get an electrical permit and inspection before the sign permit is finaled.',
		),
		'table' => array(
			'Sign permit' => 'Your city&rsquo;s sign or development services office',
			'Houston' => 'Sign Administration; licensed sign contractors apply online',
			'San Antonio' => 'Development Services; licensed sign contractors only; Chapter 28 sign code',
			'Austin' => 'Development Services; LDC Chapter 25-10; registered applicants',
			'Engineering' => 'Common for tall or large signs; thresholds differ by city',
		),
		'sources' => array(
			array('City of Houston: Sign Administration', $houston_signs),
			array('City of Houston: Commercial advertising sign (on premises) permit', 'https://www.houstonpermittingcenter.org/hpwcode1112'),
			array('City of San Antonio: Sign and billboard permits', $san_antonio_signs),
			array('City of Austin: Sign permits', $austin_signs),
		),
	),
	'climate' => array(
		'Texas sun and summer heat are tough on finishes, and spring hail and Gulf Coast storms are worth planning for: ask your installer about anchoring for wind on exposed facades, and choose colors that will still look right after years of strong UV. LED letters handle heat far better than neon.',
		'On the coast from Brownsville to Beaumont, the Atlantic hurricane season runs from June 1 to November 30. Plan installs and banner displays around storm warnings, and take temporary banners down before a storm arrives.',
	),
	'climate_sources' => array(
		array('NOAA National Hurricane Center: Atlantic hurricane season climatology', 'https://www.nhc.noaa.gov/climo/'),
	),
	'popular' => array(
		'heading' => 'Popular Signs for Texas Storefronts',
		'lead' => 'Lit letters for strip centers, plus flags and banners for the road.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'The standard lit letter in Texas shopping centers.',
			'standard-channel-letter-back-lit' => 'Back lit letters for a softer glow on stucco.',
			'feather-angled-flag-pole' => 'Feather flags that catch drivers on the frontage road.',
			'13oz-vinyl-banner' => 'Coming soon and grand opening banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Texas?',
			'a' => 'We ship custom channel letters anywhere in Texas. They are built in Washington and California, tested, and delivered to your door with a wiring diagram and install pattern. We don&rsquo;t install, so you&rsquo;ll hire a TDLR-licensed electrical sign contractor near you to mount and connect the letters.',
		),
		array(
			'q' => 'What license does a sign installer need in Texas?',
			'a' => 'The business needs a TDLR Electrical Sign Contractor (or Electrical Contractor) license, with a Master Electrician or Master Sign Electrician on staff and liability insurance on file. The people doing the work need TDLR licenses such as Master or Journeyman Sign Electrician. Houston also requires its own sign contractor license.',
		),
		array(
			'q' => 'Is there a statewide sign permit in Texas?',
			'a' => 'No. Business sign permits come from the city where the sign goes up, under that city&rsquo;s sign code. Some cities&rsquo; rules reach beyond the city limits: Houston&rsquo;s sign code also applies in its extraterritorial jurisdiction. Ask the city that serves your address before you order.',
		),
		array(
			'q' => 'Who can apply for a sign permit in Houston, San Antonio or Austin?',
			'a' => 'In Houston and San Antonio, only a licensed sign contractor can apply (Houston lets owners permit a few small non-electric signs themselves). Austin asks applicants to register for an Outdoor Advertising Registration first, and lit signs need an electrical permit and inspection before the sign permit is finaled.',
		),
		array(
			'q' => 'How long does shipping to Texas take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Houston processing (4-11 business days) and engineering thresholds re-checked 2026-10-22 from Internet Archive copies; re-check yearly (next by 2027-10).',
		'Transit days to Texas not shown until verified.',
	),
);
