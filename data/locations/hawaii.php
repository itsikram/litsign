<?php
/**
 * Hawaii state guide (/locations/hawaii/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$hi_faq = 'https://cca.hawaii.gov/pvl/files/2018/09/EP-FAQs_07-18.pdf';
$hnl_luo = 'https://www.honolulu.gov/dpp/wp-content/uploads/sites/56/2026/11/November-2026-LUO.pdf';

return array(
	'slug' => 'hawaii',
	'name' => 'Hawaii',
	'abbr' => 'HI',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Hawaii',
	'neighbors' => array(),
	'seo_title' => 'Channel Letters Shipped to Hawaii | Honolulu Sign Rules',
	'seo_description' => 'Channel letters shipped to Hawaii: C-13 and C-14 contractors, Honolulu sign permits and lighting limits, and the state billboard ban. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Hawaii',
	'intro' => array(
		'Hawaii businesses can order custom LED channel letters online, built in Washington and California and shipped to the islands ready for a licensed Hawaii contractor to install. Banners and window graphics are printed in Renton, WA.',
		'Hawaii licenses individual electricians through the Board of Electricians and Plumbers and contracting businesses through the Contractors License Board, which has two classifications that matter here: <strong>C-13 Electrical</strong>, whose scope names electrical signs, and <strong>C-14 Sign</strong> for fabricating and installing electrical or nonelectrical signs.',
		'Lighting is the big local question. In Honolulu&rsquo;s B-1 neighborhood business, resort and Waikiki districts, signs may not be directly illuminated, and the code counts interior lighting and backlighting as direct. A state law also bans billboards, while allowing signs for businesses on the premises.',
	),
	'facts' => array(
		'Electricians' => 'Board of Electricians and Plumbers (DCCA)',
		'Contractor classes' => 'C-13 Electrical (includes electrical signs); C-14 Sign',
		'Electrical permits' => 'County building departments',
		'Honolulu lit signs' => 'Not allowed in B-1, resort and Waikiki districts',
	),
	'categories' => array(
		'channel-letters' => 'Check your Honolulu zoning first: in the B-1, resort and Waikiki districts no sign may be directly lit, a term the code defines to include interior lighting and backlighting.',
		'window-graphics' => 'Honolulu doesn&rsquo;t treat ground-floor window displays as signs, but lettering must stay on the inside of the glass and only on the first floor.',
		'banners' => 'Honolulu allows banners, wind displays and portable signs only as a special event display, once every six months for up to seven consecutive days.',
		'flags-aframes' => 'Honolulu&rsquo;s code names A-frames as portable signs, which are prohibited except during a seven-day special event display.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install and Wire a Lit Sign in Hawaii',
		'body' => array(
			'The Board of Electricians and Plumbers licenses journey worker and supervising electricians, plus industrial, specialty and maintenance versions. A journey worker electrician needs at least five years and 10,000 hours of residential or commercial wiring, and a supervising electrician four years as a licensed journey worker. The specialty licenses don&rsquo;t fit a lit sign: HRS 448E-1 limits them to low-voltage systems such as sound and communications, &ldquo;but not the attachment of lighting and power circuits.&rdquo;',
			'A licensed electrician can&rsquo;t contract for electrical work alone; an electrical contractor&rsquo;s license is required, except on projects of $1,000 or less that don&rsquo;t need an electrical permit, and Hawaii has no reciprocal agreements with other states. The general $1,500 exemption from contractor licensing doesn&rsquo;t apply whenever a building permit is required (HRS 444-2), and sign permits are required in Honolulu.',
			'Under the contractor classifications in HAR chapter 16-77, a <strong>C-13 Electrical contractor</strong> may place, install, erect or connect electrical wiring and fixtures, explicitly including electrical signs, under 600 volts, and a <strong>C-14 Sign contractor</strong> may fabricate or install electrical or nonelectrical signs with their supports and accessories. A common split is a C-14 sign company mounting the letters and a C-13 contractor&rsquo;s electricians making the connection.',
		),
		'table' => array(
			'Electrician licensing' => 'Board of Electricians and Plumbers, DCCA',
			'Journey worker' => 'Five years and 10,000 hours of wiring experience',
			'Specialty licenses' => 'Low voltage only, no lighting or power circuits',
			'C-13 Electrical contractor' => 'Wiring and fixtures, including electrical signs',
			'C-14 Sign contractor' => 'Fabricates and installs electrical or nonelectrical signs',
		),
		'sources' => array(
			array('Hawaii Board of Electricians and Plumbers: FAQs (PDF)', $hi_faq),
			array('HRS 448E-1: Electricians and plumbers, definitions', 'https://www.capitol.hawaii.gov/hrscurrent/Vol10_Ch0436-0474/HRS0448E/HRS_0448E-0001.htm'),
			array('HAR 16-77, Exhibit A: Contractor classifications (Cornell LII)', 'https://www.law.cornell.edu/regulations/hawaii/Haw-Code-R-tit-16-ch-77-subch-19-exh-A'),
			array('HRS 444-2: Contractor license exemptions', 'https://data.capitol.hawaii.gov/hrscurrent/Vol10_Ch0436-0474/HRS0444/HRS_0444-0002.htm'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Honolulu and Across Hawaii',
		'body' => array(
			'Electrical permits and codes are handled by the county building departments of Honolulu, Hawaii, Maui and Kauai. In the <strong>City and County of Honolulu</strong>, the Department of Planning and Permitting (DPP) requires a sign permit to install, construct, alter, relocate or reconstruct any sign. Verify the use of your space first, since a change of use needs a building permit before the sign permit, and check drawings against DPP&rsquo;s 2025 sign permit plan format checklist, because plans that miss any item are rejected.',
			'Under the Land Use Ordinance (ROH 21-7.60), the sign owner or agent signs the application, and the plans show the sign&rsquo;s weight, dimensions, lighting equipment, materials, attachment and position, along with any required electrical design. Work started without a permit costs $100 on top of the fee, and a permit is void if the sign isn&rsquo;t finished within six months.',
			'Size rules depend on the district. In the B-2 and BMX-3 community business districts, each ground-floor business can have two signs, which may be lit, at 1.5 square feet per lineal foot of frontage, up to 250 square feet and 15 percent of the wall. In the B-1 neighborhood business district, one wall sign of 1 square foot per lineal foot, up to 100 square feet, is allowed, it can&rsquo;t be directly illuminated, and no lit sign may be visible from an adjoining residential lot after 10 p.m. Flashing signs are prohibited everywhere.',
			'Statewide, HRS 445-112 bans billboards and outdoor advertising devices except listed exceptions, which include signs advertising goods or services traded on the premises where they are displayed.',
		),
		'table' => array(
			'Electrical permits' => 'County building departments',
			'Honolulu sign permit' => 'DPP, required for any sign',
			'Honolulu plans' => 'Weight, dimensions, lighting, attachment and electrical design',
			'Honolulu permit life' => 'Void if the sign isn&rsquo;t completed within six months',
			'B-1 district' => 'One wall sign, not directly illuminated',
		),
		'sources' => array(
			array('Honolulu DPP: Sign permits', 'https://www.honolulu.gov/dpp/permitting/building-permits-home/permit-sign-permit/'),
			array('Honolulu Land Use Ordinance, revised November 2026 (PDF)', $hnl_luo),
			array('HRS 445-112: Billboards prohibited; exceptions', 'https://data.capitol.hawaii.gov/hrscurrent/Vol10_Ch0436-0474/HRS0445/HRS_0445-0112.htm'),
		),
	),
	'districts' => array(
		array(
			'name' => 'Waikiki Special District (Honolulu LUO 21-7.40)',
			'rule' => 'Signs may not be directly illuminated. In the Resort Mixed Use Precinct each business gets one sign per frontage, at 1 square foot per linear foot or 36 square feet, whichever is less, and English or Hawaiian must be the dominant language on every sign.',
			'source' => $hnl_luo,
		),
		array(
			'name' => 'Chinatown Special District (Honolulu LUO 21-9.60)',
			'rule' => 'Up to four signs per ground-floor business at 2 square feet per lineal foot, but no more than two may be lit, directly lit signs must use neon or bulbs mounted on the outside of the sign, box fluorescents are prohibited, and lettering should suit the turn-of-the-century to 1940s character.',
			'source' => $hnl_luo,
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Special Event Displays in Honolulu',
		'body' => array(
			'Honolulu prohibits wind signs, such as flags and banners, and portable signs, a definition that names A-frames, except where the ordinance allows them. The main allowance is a grand opening or special event display, which can use banners, wind signs and portable signs once in any six-month period, for no more than seven consecutive days.',
			'Temporary signs don&rsquo;t need a sign permit, but that seven-day window is short, so most Honolulu storefronts carry their everyday message with permanent letters and window displays and save banners for an opening or a major sale.',
		),
		'sources' => array(
			array('Honolulu Land Use Ordinance 21-7.20 and 21-7.30 (PDF)', $hnl_luo),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Displays in Honolulu',
		'body' => array(
			'Ground-floor window displays and merchandise displays aren&rsquo;t considered signs under Honolulu&rsquo;s Land Use Ordinance. Lettering and graphics in a window display are limited to the inside of the glass surface and to the first floor of the building, so plan clings or vinyl applied from inside and keep upper-floor windows clear. No percentage limit on window coverage was found in the ordinance.',
		),
		'sources' => array(
			array('Honolulu Land Use Ordinance 21-7.20, definitions (PDF)', $hnl_luo),
		),
	),
	'climate' => array(
		'Hawaii storefronts deal with salt spray near every coastline, steady trade winds, intense year-round sun and heavy rain on windward shores, with hurricane threats in summer and fall. Specify stainless or coated fasteners, UV-stable faces and sealed raceways, and ask your installer to anchor letters for storm gusts rather than everyday trade winds.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Hawaii Storefronts',
		'lead' => 'Lit letters where your district allows them, plus window graphics and event banners that fit Honolulu rules.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for business districts that allow lighting.',
			'hidden-back-halo-lit' => 'A halo glow, where your district permits lit signs.',
			'premium-window-cling' => 'Window clings applied to the inside of the glass.',
			'13oz-vinyl-banner' => 'Banners for seven-day special event displays.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Hawaii?',
			'a' => 'We ship custom channel letters anywhere in Hawaii, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Hawaii-licensed C-14 sign contractor and a C-13 electrical contractor, or one company holding both, to mount and connect them.',
		),
		array(
			'q' => 'Which Hawaii license covers sign installation?',
			'a' => 'At the contractor level, C-14 Sign covers fabricating and installing electrical or nonelectrical signs, and C-13 Electrical covers wiring and fixtures, including electrical signs, under 600 volts. The electricians themselves need a journey worker or supervising license; the specialty licenses are limited to low-voltage systems and exclude lighting and power circuits.',
		),
		array(
			'q' => 'Can I have lit channel letters in Honolulu?',
			'a' => 'It depends on your district. Community business districts such as B-2 and BMX-3 allow lit signs, but the B-1 neighborhood business, resort and Waikiki districts don&rsquo;t allow directly illuminated signs, and the code defines that term to include interior lighting and backlighting. In Chinatown, lit signs must use exterior neon or bulbs.',
		),
		array(
			'q' => 'Can I use an A-frame or banner in Honolulu?',
			'a' => 'Only as part of a grand opening or special event display. Honolulu prohibits portable signs, which include A-frames, and wind signs such as banners and flags, except for a special event display allowed once every six months for up to seven consecutive days. Window displays on the inside of first-floor glass aren&rsquo;t treated as signs.',
		),
		array(
			'q' => 'How long does shipping to Hawaii take?',
			'a' => 'Hawaii is outside the standard mainland transit times. Checkout shows the shipping options and estimated delivery date for your address, including production.',
		),
	),
	'review_notes' => array(
		'Shipping FAQ intentionally differs; confirm carrier options and transit to Hawaii before going live.',
		'Board FAQ (updated July 2018), HAR 16-77 Exhibit A (Cornell LII), HRS 444-2 and 445-112 and the Honolulu LUO (revised November 2026) read directly 2026-11-20; HRS 448E-1 read from the Internet Archive copy of 2026-04-20 (www.capitol.hawaii.gov returns 403 here). Closes the old C-13 and billboard-source notes.',
		'Whether halo-lit (reverse) letters count as &ldquo;backlighting&rdquo; is DPP&rsquo;s call; the page quotes the definition as written. Confirm with DPP for a specific Waikiki, resort or B-1 address.',
		'Honolulu sign permit fees and the $100 work-without-permit charge: re-check yearly. Maui, Hawaii County and Kauai sign rules not researched.',
		'Transit days to Hawaii not shown until verified.',
	),
);
