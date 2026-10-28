<?php
/**
 * South Carolina state guide (/locations/south-carolina/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$chs_bar = 'https://charleston-sc.gov/DocumentCenter/View/32867/BAR-Policy-Statement-for-Signage-Posted-02122026';

return array(
	'slug' => 'south-carolina',
	'name' => 'South Carolina',
	'abbr' => 'SC',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/South_Carolina',
	'neighbors' => array('north-carolina', 'georgia'),
	'seo_title' => 'Channel Letters Shipped to South Carolina | Charleston BAR',
	'seo_description' => 'Channel letters shipped to South Carolina: the $10,000 EL license rule, and Charleston BAR limits on lit signs, windows and banners. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to South Carolina',
	'intro' => array(
		'South Carolina businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed South Carolina electrical contractor. Banners and window graphics are printed in Renton, WA.',
		'South Carolina licenses commercial contractors by job size: work with a total construction cost over $10,000 needs a license from the Contractor&rsquo;s Licensing Board, and electrical work falls under its Electrical (EL) classification. Residential electricians are licensed by a different board.',
		'In historic Charleston, the Board of Architectural Review&rsquo;s 2026 sign policy controls the look of a storefront: pin-mounted and channel letters are welcome, but lighting needs approval, window graphics are capped and banners aren&rsquo;t permitted.',
	),
	'facts' => array(
		'Licensing agency' => 'SC LLR Contractor&rsquo;s Licensing Board',
		'License threshold' => 'Total construction cost over $10,000 (S.C. Code &sect; 40-11-30)',
		'Electrical classification' => 'EL, under a general or mechanical license',
		'Charleston historic areas' => 'Board of Architectural Review sign policy',
	),
	'categories' => array(
		'channel-letters' => 'Charleston&rsquo;s BAR encourages individually pin-mounted letters and allows channel and reverse-channel letters, but any sign that is or could be illuminated needs approval from the City Architect or the Board.',
		'window-graphics' => 'Within Charleston&rsquo;s BAR jurisdiction, window and door graphics, including lettering, decals and posters, may cover no more than 20 percent of any single pane.',
		'banners' => 'Charleston&rsquo;s BAR policy lists banners, advertisement flags and sandwich boards among the sign types that are not permitted in its jurisdiction.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in South Carolina',
		'body' => array(
			'Under S.C. Code &sect; 40-11-30, no one may perform or offer contracting work where the <strong>total cost of construction is greater than $10,000</strong> without a license from the Contractor&rsquo;s Licensing Board in the Department of Labor, Licensing and Regulation (LLR). Section 40-11-410 places <strong>Electrical (EL)</strong> among the license classifications, and the classification number caps the size of job a contractor may bid.',
			'Residential electrical work is handled separately by the Residential Builders Commission. For a storefront, ask your installer for the Contractor&rsquo;s Licensing Board license number and EL classification, and look both up on LLR&rsquo;s license search; cities also require their own business licenses and permits.',
		),
		'table' => array(
			'Licensing agency' => 'SC Department of Labor, Licensing and Regulation, Contractor&rsquo;s Licensing Board',
			'License needed' => 'Work over $10,000 total construction cost',
			'Electrical' => 'EL classification (general or mechanical license)',
			'Residential' => 'Residential Builders Commission',
		),
		'sources' => array(
			array('S.C. Code Title 40, Chapter 11: Contractors', 'https://www.scstatehouse.gov/code/t40c011.php'),
			array('SC LLR: Contractor&rsquo;s Licensing Board', 'https://llr.sc.gov/clb/clb_general.aspx'),
			array('SC LLR: License classification abbreviations and limits (PDF)', 'https://llr.sc.gov/clb/PDFFiles/CLBClassificationAbbreviations.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in South Carolina',
		'body' => array(
			'South Carolina cities and counties issue sign permits under their own zoning ordinances, and a lit sign also needs an electrical permit. Columbia, Greenville and the coastal towns each set their own limits.',
			'In <strong>Charleston</strong>, signs within the Board of Architectural Review&rsquo;s jurisdiction are reviewed case by case, and the BAR&rsquo;s policy statement takes precedence over the sign article of the zoning ordinance. A business may use up to two of five sign types (facade, right-angle, window/door, menu board, awning or canopy), signs go only on first-floor facades that front a public street, and every application includes a cross-section showing how the sign attaches to the building.',
			'A facade sign is limited to one per business and 10 percent of the ground-floor facade, and individually pin-mounted letters are encouraged because they don&rsquo;t detract from the architecture. Illumination is allowed only for businesses that operate after dark, at a muted tone and brightness, and must be approved by the City Architect or the Board before the sign permit. Cabinet or box signs, exposed or unfinished returns, and clear or tinted acrylic backing panels aren&rsquo;t permitted.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Charleston historic areas' => 'BAR policy statement (posted Feb. 12, 2026)',
			'Facade sign' => 'One per business, up to 10% of the ground-floor facade',
			'Lit signs' => 'Only for businesses open after dark; City Architect or BAR approval',
		),
		'sources' => array(
			array('City of Charleston BAR: Policy statement for signage (PDF)', $chs_bar),
			array('City of Charleston: Board of Architectural Review', 'https://www.charleston-sc.gov/293/Board-of-Architectural-Review-BAR-L-BAR-'),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics and Banners in Historic Charleston',
		'body' => array(
			'Within the BAR&rsquo;s jurisdiction, window and door signs, including lettering, graphics, decals, posters and icons, may cover no more than <strong>20 percent of any glass area</strong> of a single window or door, and they count as one of the business&rsquo;s two sign types. Hours, phone numbers and websites are allowed on the glass under the same 20 percent limit, preferably on doors. Banners, advertisement flags and sandwich boards are on the list of sign types that aren&rsquo;t permitted.',
		),
		'sources' => array(
			array('City of Charleston BAR: Policy statement for signage (PDF)', $chs_bar),
		),
	),
	'climate' => array(
		'South Carolina&rsquo;s Lowcountry storefronts sit in salt air, humidity and the path of Atlantic hurricanes, while the Upstate gets summer thunderstorms and the occasional ice storm. Near the coast, ask your installer for stainless or coated fasteners and anchoring rated for hurricane winds.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for South Carolina Storefronts',
		'lead' => 'Lit letters for shopping centers and understated letters for historic downtowns.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Greenville and Columbia shopping centers.',
			'hidden-back-halo-lit' => 'A quiet halo look for historic districts.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for beach town foot traffic, where allowed.',
			'13oz-vinyl-banner' => 'Grand opening and event banners in South Carolina.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in South Carolina?',
			'a' => 'We ship custom channel letters anywhere in South Carolina, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed South Carolina electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'When does a sign installer need a state license in South Carolina?',
			'a' => 'When the total cost of construction is over $10,000. Above that, S.C. Code &sect; 40-11-30 requires a Contractor&rsquo;s Licensing Board license, and electrical work falls under the EL classification, whose number caps the job size the contractor may bid.',
		),
		array(
			'q' => 'Can I have lit channel letters in historic Charleston?',
			'a' => 'Often, with approval. The BAR allows channel and reverse-channel letters and encourages pin-mounted letters, but any sign that is or could be illuminated must be approved by the City Architect or the Board, and lighting is only for businesses open after dark.',
		),
		array(
			'q' => 'Are banners allowed in Charleston&rsquo;s historic district?',
			'a' => 'No. The BAR&rsquo;s signage policy lists banners, advertisement flags and sandwich boards as sign types that aren&rsquo;t permitted within its jurisdiction. Window graphics are allowed, up to 20 percent of any single window or door, and hours, phone numbers and websites share that limit.',
		),
		array(
			'q' => 'How long does shipping to South Carolina take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The old $5,000 / $10,000 conflict is resolved: S.C. Code &sect; 40-11-30 (read 2026-10-27) sets $10,000; a 2021&ndash;2022 bill (H. 4762) to raise it does not appear to have passed, since the code text still shows $10,000. Re-check yearly.',
		'Charleston BAR policy statement (posted Feb. 12, 2026) read 2026-10-27. Outside BAR jurisdiction, Charleston&rsquo;s Design Review Board and Article 4 of the zoning ordinance apply; not summarized.',
		'An LLR sheet reportedly says internal electrical work needs no license but the final connection to power needs the EL classification; the sheet refused connections, so it is not shown.',
		'Transit days to South Carolina not shown until verified.',
	),
);
