<?php
/**
 * New Jersey state guide (/locations/new-jersey/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$nj_2015 = 'https://www.njconsumeraffairs.gov/Adoptions/elecado_031615.pdf';
$jc_hpc = 'https://www.jerseycitynj.gov/CityHall/HousingAndDevelopment/cityplanning/historicpreservation';

return array(
	'slug' => 'new-jersey',
	'name' => 'New Jersey',
	'abbr' => 'NJ',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/New_Jersey',
	'neighbors' => array('new-york', 'pennsylvania', 'delaware'),
	'seo_title' => 'Channel Letters Shipped to New Jersey | Licensing Guide',
	'seo_description' => 'Channel letters shipped to New Jersey: why LED wiring needs a licensed electrical contractor, UCC permits, Jersey City historic rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to New Jersey',
	'intro' => array(
		'New Jersey businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed New Jersey electrical contractor. Banners and window graphics are printed in Renton, WA.',
		'New Jersey is strict about LED wiring. Its Board of Examiners of Electrical Contractors exempts only work under 10 volts, has said an LED lighting system runs above that, and requires a licensed electrical contractor even to replace an LED driver, the power supply inside a lit channel letter sign.',
		'Permits follow the statewide Uniform Construction Code: your town&rsquo;s zoning officer approves the sign, and its construction office issues the building and electrical permits, with the electrical contractor&rsquo;s seal on the electrical application.',
	),
	'facts' => array(
		'Licensing agency' => 'NJ Board of Examiners of Electrical Contractors',
		'Contractor needs' => 'License plus business permit, renewed every three years',
		'LED drivers' => 'Replacement is licensed electrical work',
		'Construction code' => 'Uniform Construction Code (N.J.A.C. 5:23)',
	),
	'categories' => array(
		'channel-letters' => 'In New Jersey, connecting LED letters or swapping a failed LED driver later is licensed electrical work, so keep your electrical contractor&rsquo;s details for future service calls.',
		'window-graphics' => 'Jersey City requires a Historic Preservation certificate before any work on any sign on a landmark or in a historic district, even with no construction permit, so check before adding lettered window graphics there.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in New Jersey',
		'body' => array(
			'The <strong>Board of Examiners of Electrical Contractors</strong> requires every electrical contractor to be licensed and to hold a valid <strong>business permit</strong>, both renewed every three years. To sit for the license exam, an applicant shows a four-year approved apprenticeship followed by a year working as a journeyman, then passes a three-part exam.',
			'In its 2015 rule adoption the board explained why lit signs aren&rsquo;t a do-it-yourself job here. New Jersey has no general &ldquo;low-voltage&rdquo; exemption: the statute exempts only work under 10 volts, and because an LED lighting system exceeds 10 volts, it doesn&rsquo;t qualify. The board added that replacing LED drivers must be done by a licensed electrical contractor. Ask your installer for both the license number and the business permit number.',
		),
		'table' => array(
			'Licensing agency' => 'Board of Examiners of Electrical Contractors, NJ Division of Consumer Affairs',
			'Business' => 'Electrical contractor license and business permit',
			'Renewal' => 'Every three years',
			'Exempt work' => 'Only under 10 volts; LED systems don&rsquo;t qualify',
		),
		'sources' => array(
			array('NJ Board of Examiners of Electrical Contractors', 'https://njconsumeraffairs.gov/elec'),
			array('NJ Board of Examiners of Electrical Contractors: FAQ', 'https://njconsumeraffairs.gov/elec/Pages/FAQ.aspx'),
			array('NJ Division of Consumer Affairs: 2015 rule adoption (LED drivers, 10-volt exemption) (PDF)', $nj_2015),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in New Jersey',
		'body' => array(
			'Every New Jersey municipality approves signs under its own zoning ordinance, usually through the zoning officer and sometimes the planning or zoning board. The building and electrical permits come from the municipal construction office under the state Uniform Construction Code.',
			'Under N.J.A.C. 5:23-2.15, the property owner or an agent applies for the construction permit, though a licensed engineer, architect or electrical contractor working on the job may file it. The electrical subcode application carries the licensed electrical contractor&rsquo;s seal and signature, and plans that need professional review carry the seal of a registered architect or licensed engineer. The state&rsquo;s current subcodes are the 2024 International Building Code, New Jersey edition, and the 2023 National Electrical Code.',
		),
		'table' => array(
			'Sign approval' => 'Your municipality&rsquo;s zoning officer or board',
			'Construction permits' => 'Municipal construction office (building and electrical subcodes)',
			'Electrical application' => 'Sealed and signed by the licensed electrical contractor',
			'Codes in force' => 'IBC 2024 NJ edition; 2023 NEC',
		),
		'sources' => array(
			array('N.J.A.C. 5:23-2.15: Construction permits, application', 'https://www.law.cornell.edu/regulations/new-jersey/N-J-A-C-5-23-2-15'),
			array('NJ Department of Community Affairs: Current codes', 'https://www.nj.gov/dca/codes/codreg/current.shtml'),
		),
	),
	'districts' => array(
		array(
			'name' => 'Jersey City historic districts and landmarks',
			'rule' => 'Any work on any sign needs a Certificate of No Effect or a Certificate of Appropriateness from the Historic Preservation Commission before work starts, whether or not a construction permit is required. Most requests are reviewed by commission staff and are filed through the city&rsquo;s online licensing and permitting site.',
			'source' => $jc_hpc,
		),
		array(
			'name' => 'Trenton landmarks',
			'rule' => 'The Landmarks Commission reviews all improvements to the exterior of designated historic properties, which include local landmarks and eight historic districts, through a Landmarks Restoration Application.',
			'source' => 'https://www.trentonnj.org/282',
		),
	),
	'climate' => array(
		'Shore towns from Sandy Hook to Cape May take salt air and nor&rsquo;easter winds, while North Jersey storefronts deal with snow, road salt and freeze-thaw cycles. Ask your installer for corrosion-resistant fasteners near the coast and sealed wall penetrations everywhere.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for New Jersey Storefronts',
		'lead' => 'Lit letters for strip malls and downtowns, plus graphics for storefront glass.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for strip malls and highway frontage.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for busy downtown blocks.',
			'adhesive-window-perf' => 'Window graphics that keep the view out in New Jersey.',
			'18oz-blockout-banner' => 'Durable banners for grand openings and sales.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in New Jersey?',
			'a' => 'We ship custom channel letters anywhere in New Jersey, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed New Jersey electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'Can a handyman connect LED channel letters in New Jersey?',
			'a' => 'No. The state exempts only electrical work under 10 volts, and the electrical board has said LED lighting systems exceed that, so wiring the letters, and even replacing an LED driver later, takes a licensed electrical contractor with a current business permit.',
		),
		array(
			'q' => 'Who signs the electrical permit for a sign in New Jersey?',
			'a' => 'The licensed electrical contractor. Under the Uniform Construction Code, the electrical subcode application carries the contractor&rsquo;s seal and signature, while the owner or an agent applies for the overall construction permit at the municipal construction office. Plans that need professional review also carry a registered architect&rsquo;s or licensed engineer&rsquo;s seal.',
		),
		array(
			'q' => 'Do I need approval for a sign in a Jersey City historic district?',
			'a' => 'Yes. Any sign work on a landmark or in a historic district needs a Certificate of No Effect or a Certificate of Appropriateness from the Historic Preservation Commission before work begins, even if no construction permit is needed. Most requests are reviewed by commission staff rather than at a full commission meeting.',
		),
		array(
			'q' => 'How long does shipping to New Jersey take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'2015 rule adoption read from the Internet Archive copy of 2025-03-05.',
		'Newark not added: its sign chapter (41:9) is behind a Cloudflare check at ecode360.com/36711653 and the only archive copy (2024-03-05) is the 2015 text; the chapter was rewritten in November 2023. Open it from another network if you want Newark on the page.',
		'NJ DCA lists the current subcodes with an adoption date of Aug 17, 2026; the page names the editions only. Re-check yearly.',
		'Trenton&rsquo;s count of 58 local landmarks was left off; Jersey City&rsquo;s page gives two different district counts, so no number is shown.',
		'Transit days to New Jersey not shown until verified.',
	),
);
