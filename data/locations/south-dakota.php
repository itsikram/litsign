<?php
/**
 * South Dakota state guide (/locations/south-dakota/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$sd_3616 = 'https://sdlegislature.gov/Statutes/36-16';
$fsd_code = 'https://www.siouxfalls.gov/files/assets/public/v/2/business-and-permits/building-services/signs/1e25143.pdf';
$fsd_signs = 'https://www.siouxfalls.gov/business-permits/permits-licenses-inspections/permits/signs';

return array(
	'slug' => 'south-dakota',
	'name' => 'South Dakota',
	'abbr' => 'SD',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/South_Dakota',
	'neighbors' => array('north-dakota', 'minnesota', 'iowa', 'nebraska', 'wyoming', 'montana'),
	'seo_title' => 'Channel Letters Shipped to South Dakota | Sioux Falls Rules',
	'seo_description' => 'Channel letters shipped to South Dakota: state wiring permits, state inspections, contractor rules and Sioux Falls sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to South Dakota',
	'intro' => array(
		'South Dakota storefronts can order custom LED channel letters online, built in Washington and California and shipped ready for a South Dakota electrical contractor to install. Banners and window graphics are printed in Renton, WA.',
		'Electrical work in South Dakota runs through the State Electrical Commission, which licenses electrical contractors, journeymen, Class B electricians and a few other categories. Only a licensed electrical contractor may contract to install wiring, and state inspectors check every installation except inside Rapid City and Sioux Falls.',
		'Sioux Falls, the largest city, adds a license of its own: only a city-licensed sign contractor can pull any sign permit there. In exchange, the city is generous with temporary displays, allowing up to 100 square feet of banners and 25 square feet of window signs per business without a permit.',
	),
	'facts' => array(
		'Licensing agency' => 'South Dakota State Electrical Commission',
		'Who may contract' => 'Licensed electrical contractor only',
		'Inspection' => 'State inspectors, except in Rapid City and Sioux Falls',
		'Sioux Falls' => 'Sign permits only for city-licensed sign contractors',
	),
	'categories' => array(
		'channel-letters' => 'In Sioux Falls only a city-licensed sign contractor can obtain any sign permit, so hire one who holds the city&rsquo;s sign and outdoor advertising license.',
		'window-graphics' => 'Sioux Falls needs no permit for up to 25 square feet of window signs per business; past that, a permit is required and the area counts toward your building sign allowance.',
		'banners' => 'Sioux Falls lets a business show banners totaling up to 100 square feet without a permit; past that, each banner needs one.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in South Dakota',
		'body' => array(
			'SDCL 36-16-1 requires a license from the State Electrical Commission before anyone contracts for or performs electrical work, and working without one is a Class 2 misdemeanor. The license types defined in 36-16-2 include electrical contractor, journeyman (at least four years of experience), Class B electrician (36 months), electrical inspector, maintenance electrician and 501(d) electrician. The commission&rsquo;s current application has no sign category, and the exemptions in 36-16-16 don&rsquo;t cover sign work.',
			'Only an electrical contractor may enter a contract to install wiring or the electrical parts of other apparatus, and Class B electricians may contract only for farm and residential wiring (36-16-14). Contractors post a $10,000 undertaking and carry liability insurance of at least $100,000 per occurrence and $300,000 in aggregate (36-16-20). The usual path to journeyman is four years, or 8,000 hours, as a licensed apprentice working for an electrical contractor.',
		),
		'table' => array(
			'Licensing agency' => 'South Dakota State Electrical Commission',
			'Journeyman' => 'At least four years of experience',
			'Who may contract' => 'Electrical contractor (Class B: farm and residential only)',
			'Contractor security' => '$10,000 undertaking plus liability insurance',
		),
		'sources' => array(
			array('SDCL chapter 36-16: Electricians', $sd_3616),
			array('South Dakota Electrical Commission: Licensing and renewals', 'https://dlr.sd.gov/electrical/licensing.aspx'),
			array('South Dakota Electrical Commission: General license application (PDF)', 'https://dlr.sd.gov/electrical/forms/Electrical-License-Application-General.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Wiring Permits, Inspections and Sign Permits in South Dakota',
		'body' => array(
			'A new electrical service can&rsquo;t be connected until a wiring permit has been furnished to the utility supplying the power (36-16-27), and every installation gets a rough-in and a final inspection from a State Electrical Inspector. Rapid City and Sioux Falls have their own inspection departments for work inside their city limits. The minimum state inspection fee is $150, permits received since Nov. 12, 2024 are inspected to the 2023 National Electrical Code, and cities and utilities may enforce stricter rules (36-16-35).',
			'In <strong>Sioux Falls</strong>, you must be a licensed sign contractor with the city to obtain any sign permit. The Sign and Outdoor Advertising Contractor license requires an exam, a $10,000 compliance bond and at least $300,000 in general liability insurance, and it&rsquo;s renewed at the end of every year. Applications go through the city&rsquo;s Customer Self Service portal as one PDF combining the application, a site plan with the square footage of all signs, and sketches of the signs. Wall and freestanding sign permits cost $35 under 100 square feet and $50 over.',
			'Light from an illuminated sign must be shielded, shaded or directed so it doesn&rsquo;t adversely affect surrounding or facing properties, and electronic message centers can&rsquo;t blink or flash and must dim automatically to no more than 0.3 foot-candles above ambient light.',
		),
		'table' => array(
			'Wiring permit' => 'Required before the utility connects a new service',
			'Inspections' => 'State rough-in and final, except Rapid City and Sioux Falls',
			'Minimum inspection fee' => '$150',
			'Sioux Falls applicant' => 'City-licensed sign contractor only',
			'Sioux Falls license' => '$10,000 bond, $300,000 liability insurance, yearly renewal',
		),
		'sources' => array(
			array('South Dakota Electrical Commission: Inspections', 'https://dlr.sd.gov/electrical/inspections.aspx'),
			array('City of Sioux Falls: Sign application requirements', 'https://www.siouxfalls.gov/business-permits/permits-licenses-inspections/permits/signs/sign-app-reqs'),
			array('City of Sioux Falls: Sign and outdoor advertising contractor license', 'https://www.siouxfalls.gov/business-permits/permits-licenses-inspections/licensing/contractor-licensing/sign-contractor'),
			array('City of Sioux Falls: Signs (permit fees)', $fsd_signs),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Portable Displays in Sioux Falls',
		'body' => array(
			'Banners on a building or on a freestanding sign don&rsquo;t need permits as long as all the banners at the business location total 100 square feet or less. Above that, each banner needs a permit and counts toward the business&rsquo;s sign area.',
			'Portable displays are capped at 32 square feet, a business location can use up to 60 permit days in a calendar year, and each portable display needs its own permit. Budget those days for an opening or a seasonal push, and let banners under the 100-square-foot allowance do the everyday work.',
		),
		'sources' => array(
			array('Sioux Falls sign code &sect;&sect; 160.573&ndash;160.574 (PDF)', $fsd_code),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Sioux Falls',
		'body' => array(
			'Permits aren&rsquo;t required for window signs up to <strong>25 square feet per business location</strong>. Above 25 square feet, a sign permit is required, the permit fee is $15, and the window area counts toward the building sign area the business is allowed. Window signs over 25 square feet without a permit can bring citations and fines, so measure full-coverage perforated film before it goes up.',
		),
		'sources' => array(
			array('Sioux Falls sign code &sect; 160.574(e) (PDF)', $fsd_code),
			array('City of Sioux Falls: Signs (permit fees)', $fsd_signs),
		),
	),
	'climate' => array(
		'South Dakota weather swings hard between seasons: Sioux Falls and the eastern prairie get blizzards and bitter wind chills, summer storms across the state drop large hail, and West River and the Black Hills see fierce downslope winds. Ask your installer to bolt letters and raceways into solid structure, use sealant rated for freeze-thaw cycles, and confirm the power supplies are rated for cold starts.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for South Dakota Storefronts',
		'lead' => 'Lit letters for Sioux Falls and Rapid City centers, plus banners that stay under the permit-free limits.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Sioux Falls and Rapid City retail.',
			'standard-channel-letter-back-lit' => 'A soft wall glow for Black Hills main streets.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-29 and I-90.',
			'13oz-vinyl-banner' => 'Banners under the 100 square foot permit-free total.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in South Dakota?',
			'a' => 'We ship custom channel letters anywhere in South Dakota, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed South Dakota electrical contractor and, in Sioux Falls, a city-licensed sign contractor to mount and connect them.',
		),
		array(
			'q' => 'Does South Dakota have a sign electrician license?',
			'a' => 'No. The State Electrical Commission licenses electrical contractors, journeymen, Class B, maintenance and 501(d) electricians and inspectors, with no sign category and no sign exemption. Only a licensed electrical contractor may contract for the wiring, and contractors post a $10,000 undertaking plus liability insurance of $100,000 per occurrence and $300,000 in aggregate.',
		),
		array(
			'q' => 'Who inspects sign wiring in South Dakota?',
			'a' => 'A State Electrical Inspector, with a rough-in and a final inspection, everywhere except inside Rapid City and Sioux Falls, which run their own inspection departments. A new service can&rsquo;t be connected until the utility has the wiring permit, and the minimum state inspection fee is $150.',
		),
		array(
			'q' => 'Who can get a sign permit in Sioux Falls?',
			'a' => 'Only a sign contractor licensed by the City of Sioux Falls. The license takes an exam, a $10,000 compliance bond and at least $300,000 of general liability insurance, and it&rsquo;s renewed every year. The application, site plan with all sign areas and sign sketches go in as one PDF through the city&rsquo;s Customer Self Service portal.',
		),
		array(
			'q' => 'How long does shipping to South Dakota take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'SDCL 36-16 read directly 2026-11-20 through the legislature&rsquo;s API copy (the statute pages render with JavaScript); DLR licensing and inspection pages read directly. The old license application link (license_app_general_fillable.pdf) is 404 and was replaced.',
		'Sioux Falls pages and the sign code (Revised 11/23) read from Internet Archive copies of 2025-03-09 to 2026-02-15 (siouxfalls.gov returns 403 here): open once. Fees ($35/$50 sign permits, $15 window permits, $150 state minimum inspection fee): re-check yearly.',
		'Whether Sioux Falls requires a separate electrical permit for lit signs was not confirmed on a sign page. Pierre and Rapid City sign rules not researched.',
		'Transit days to South Dakota not shown until verified.',
	),
);
