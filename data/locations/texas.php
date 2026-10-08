<?php
/**
 * Texas state guide (/locations/texas/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'texas',
	'name' => 'Texas',
	'abbr' => 'TX',
	'status' => 'review',
	'neighbors' => array('new-mexico', 'oklahoma', 'arkansas', 'louisiana'),
	'seo_title' => 'Storefront Signs Shipped to Texas | TDLR Sign License Guide',
	'seo_description' => 'Channel letters shipped to Texas businesses, plus who can install them (TDLR electrical sign contractors) and how city sign permits work, including Houston.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Texas',
	'intro' => array(
		'Texas businesses can price custom LED channel letters online, then have them built in Renton, Washington and shipped to their storefront, ready for a licensed Texas sign installer.',
		'Texas is one of the few states that licenses sign electricians as their own trade. The Texas Department of Licensing and Regulation (TDLR) issues Electrical Sign Contractor licenses for businesses and Master Sign Electrician, Journeyman Sign Electrician and Electrical Sign Apprentice licenses for individuals. Sign permits themselves come from each city, and big cities like Houston run their own sign departments.',
		'Strip centers across Texas often spell out the sign in the lease: letter height, colors and mounting. Get the criteria from your landlord before you design.',
	),
	'facts' => array(
		'Licensing agency' => 'Texas Department of Licensing and Regulation (TDLR)',
		'Business license' => 'Electrical Sign Contractor',
		'Individual licenses' => 'Master / Journeyman Sign Electrician, Sign Apprentice',
		'Sign permit' => 'Your city (e.g. Houston Sign Administration)',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Texas',
		'body' => array(
			'TDLR licenses electrical sign work statewide. A business that installs electric signs holds an <strong>Electrical Sign Contractor</strong> license, which requires general liability insurance on file with TDLR. Its electricians hold sign licenses of their own: <strong>Master Sign Electrician</strong>, <strong>Journeyman Sign Electrician</strong> or <strong>Electrical Sign Apprentice</strong>. A general electrical contractor can also connect a sign.',
			'TDLR electrician licenses are renewed every year, so check that your installer&rsquo;s license is current on TDLR&rsquo;s license search.',
		),
		'table' => array(
			'Licensing agency' => 'Texas Department of Licensing and Regulation (TDLR)',
			'Business' => 'Electrical Sign Contractor (or Electrical Contractor)',
			'Individuals' => 'Master Sign Electrician, Journeyman Sign Electrician, Electrical Sign Apprentice',
			'Check a license' => 'TDLR license search',
		),
		'sources' => array(
			array('TDLR: Electrical Sign Contractor license', 'https://www.tdlr.texas.gov/electricians/apply/businesses/contractor-sign.htm'),
			array('TDLR: Master Sign Electrician license', 'https://www.tdlr.texas.gov/electricians/apply/individuals/master-sign.htm'),
			array('TDLR: License search', 'https://www.tdlr.texas.gov/LicenseSearch/'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Texas',
		'body' => array(
			'Texas cities issue their own sign permits under their own sign ordinances, and requirements vary by city, zoning district and property type. Outside city limits, county rules and highway advertising rules may apply instead.',
			'<strong>Houston</strong> runs sign permits through Sign Administration at the Houston Permitting Center. Permits must be obtained before a sign is erected, altered or repaired, and are issued only to licensed sign contractors, who apply online. New signs taller than eight feet or larger than sixty square feet need a design drawing certified by a Texas-registered professional engineer. The City lists processing at 4 to 11 business days.',
		),
		'table' => array(
			'Sign permit' => 'Your city&rsquo;s sign or building department',
			'Houston' => 'Sign Administration, Houston Permitting Center; licensed sign contractors apply',
			'Houston engineering' => 'PE-certified drawing for new signs over 8 ft tall or 60 sq ft',
		),
		'sources' => array(
			array('City of Houston: Sign Administration', 'https://www.houstonpermittingcenter.org/building-code-enforcement/sign-administration'),
			array('City of Houston: Commercial advertising sign (on-premise) permit', 'https://www.houstonpermittingcenter.org/hpwcode1112'),
		),
	),
	'climate' => array(
		'Texas sun and summer heat are tough on finishes, and spring hail and Gulf Coast storms are worth planning for: ask your installer about anchoring for wind on exposed facades, and choose colors that will still look right after years of strong UV. LED letters handle heat far better than neon.',
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
			'a' => 'We ship custom channel letters anywhere in Texas from our shop in Renton, WA. We don&rsquo;t install, so you&rsquo;ll hire a TDLR-licensed electrical sign contractor near you to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in Texas?',
			'a' => 'The business needs a TDLR Electrical Sign Contractor (or Electrical Contractor) license, and the electricians need TDLR sign electrician licenses such as Master or Journeyman Sign Electrician.',
		),
		array(
			'q' => 'Who applies for a sign permit in Houston?',
			'a' => 'A licensed sign contractor. Houston Sign Administration issues sign permits only to licensed sign contractors, and only contractors can apply online.',
		),
		array(
			'q' => 'How long does shipping to Texas take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Houston fees and the 4-11 day processing time are from the City page; re-check before going live.',
		'Transit days to Texas not shown until verified.',
	),
);
