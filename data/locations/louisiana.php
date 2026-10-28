<?php
/**
 * Louisiana state guide (/locations/louisiana/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$nola_czo = 'https://czo.nola.gov/article-24/';

return array(
	'slug' => 'louisiana',
	'name' => 'Louisiana',
	'abbr' => 'LA',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Louisiana',
	'neighbors' => array('arkansas', 'mississippi', 'texas'),
	'seo_title' => 'Channel Letters Shipped to Louisiana | New Orleans Rules',
	'seo_description' => 'Channel letters shipped to Louisiana: the $10,000 LSLBC rule, New Orleans permits and window limits, and French Quarter review. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Louisiana',
	'intro' => array(
		'Louisiana businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Louisiana contractor. Banners and window graphics are printed in Renton, WA.',
		'Louisiana licenses contractors through the State Licensing Board for Contractors (LSLBC), and electrical work valued above $10,000, counting labor and materials, needs a commercial license with an electrical classification.',
		'New Orleans requires a sign permit for any new or altered sign, and anything that needs an electrical permit is never exempt. Window signs are capped at 10 percent of the glass, and in the French Quarter the Vieux Carr&eacute; Commission approves every sign&rsquo;s size and location.',
	),
	'facts' => array(
		'Licensing agency' => 'Louisiana State Licensing Board for Contractors (LSLBC)',
		'Electrical threshold' => 'Commercial license for work over $10,000',
		'New Orleans sign permit' => 'Department of Safety and Permits (CZO Article 24)',
		'French Quarter' => 'Vieux Carr&eacute; Commission approves size and location',
	),
	'categories' => array(
		'channel-letters' => 'New Orleans wall signs must sit at least 7 feet above the ground, flat against the wall and projecting no more than 18 inches, and every sign carries its permit number and voltage.',
		'window-graphics' => 'Outside the historic core, New Orleans limits all window signs, temporary and permanent, neon included, to 10 percent of the total window area.',
		'banners' => 'New Orleans allows one temporary banner per lot for non-residential uses, up to 32 square feet, never above the roofline or over the public right-of-way.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Louisiana',
		'body' => array(
			'The <strong>LSLBC</strong> licenses commercial contractors by major classification, subclassification and specialty, and electrical work is one of its classifications. Its rulebook sets the trigger: a commercial license is required for commercial construction of $50,000 or more, and for <strong>electrical, plumbing or mechanical work over $10,000</strong>, with the value including labor, materials, rentals and direct and indirect expenses.',
			'Cities and parishes add their own permits on top of the state license. Ask your installer for the LSLBC license number and its electrical classification, and look it up on the board&rsquo;s website before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Louisiana State Licensing Board for Contractors',
			'Electrical work' => 'Commercial license over $10,000 (labor and materials included)',
			'General commercial' => '$50,000 or more',
			'Local' => 'City or parish permits also apply',
		),
		'sources' => array(
			array('LSLBC: Exams and classifications', 'https://lslbc.louisiana.gov/exams-classifications/'),
			array('LSLBC rules and regulations (Blue Book, updated Aug. 1, 2024) (PDF)', 'https://wwwcfprd.doa.louisiana.gov/boardsandcommissions/RulesAndRegulations/10_Blue%20Book%208%201%202024%20FINAL.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Louisiana',
		'body' => array(
			'Louisiana cities and parishes issue sign permits under their own zoning codes; Baton Rouge, Shreveport and Lafayette each run their own offices.',
			'In <strong>New Orleans</strong>, Article 24 of the Comprehensive Zoning Ordinance makes it unlawful to erect, relocate or structurally alter any sign without a sign permit from the Department of Safety and Permits. Repainting and copy changes are exempt only if they don&rsquo;t change the sign&rsquo;s structure or electrical components, and nothing that requires an electrical permit is ever exempt from the sign permit. Every sign must show its date of erection, its permit number and the voltage of its electrical apparatus, painted on or on a metallic sticker.',
			'Wall signs go at least 7 feet above the ground, flat against the building, projecting no more than 18 inches and never past the top or ends of the wall. Projecting signs may extend up to 4 feet from the building, with the bottom at least 7 feet above the sidewalk.',
		),
		'table' => array(
			'Sign permit' => 'Your city or parish',
			'New Orleans' => 'Department of Safety and Permits; electrical work never exempt',
			'Every New Orleans sign' => 'Shows erection date, permit number and voltage',
			'New Orleans wall signs' => '7 ft above ground minimum; 18 in projection maximum',
		),
		'sources' => array(
			array('New Orleans Comprehensive Zoning Ordinance, Article 24: Signs', $nola_czo),
		),
	),
	'districts' => array(
		array(
			'name' => 'French Quarter (Vieux Carr&eacute;) and historic core',
			'rule' => 'The location and size of every sign are subject to prior approval by the Vieux Carr&eacute; Commission. In most of the historic core&rsquo;s commercial districts, wall and window signs get 30 square inches per foot of street frontage, with no single sign over 8 square feet. Internally lit signs are limited to 18 inches between faces, with steady light concealed behind glass or hoods and wattage caps.',
			'source' => $nola_czo,
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics and Banners in New Orleans',
		'body' => array(
			'Outside the historic core, <strong>all window signs, temporary or permanent, including neon, are limited to 10 percent of the total window area</strong>, and mullions don&rsquo;t split a window into separate areas. Temporary signs inside the glass or within 12 inches of it count toward the same 10 percent.',
			'A temporary banner is allowed for any non-residential use: one per lot, up to 32 square feet, no higher than the roofline, and never extending over the public right-of-way.',
		),
		'sources' => array(
			array('New Orleans CZO &sect; 24.9 and &sect; 24.11.G', $nola_czo),
		),
	),
	'climate' => array(
		'Louisiana&rsquo;s heat and humidity are constant, Gulf hurricanes and tropical storms bring wind and driving rain, and salt air reaches well inland around the coastal parishes. Ask your installer for corrosion-resistant hardware, sealed raceways and anchoring rated for hurricane winds.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Louisiana Storefronts',
		'lead' => 'Lit letters for shopping centers and signs for busy restaurant districts.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Baton Rouge and Lafayette shopping centers.',
			'halo-reverse-acrylic-lit-channel-letters' => 'A halo glow for restaurants and bars.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for daily specials.',
			'13oz-vinyl-banner' => 'Festival and event banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Louisiana?',
			'a' => 'We ship custom channel letters anywhere in Louisiana, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed Louisiana electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'When does a sign installer need an LSLBC license?',
			'a' => 'When the electrical work is worth more than $10,000, counting labor, materials, rentals and other expenses, the contractor needs an LSLBC commercial license with an electrical classification. Cities and parishes still require their own permits. Check the license on the board&rsquo;s website before you hire.',
		),
		array(
			'q' => 'How much of a New Orleans shop window can graphics cover?',
			'a' => 'Ten percent of the total window area, counting every window sign together, temporary and permanent, neon included. Mullions don&rsquo;t divide the window, and temporary signs within 12 inches of the glass count too. The French Quarter has its own smaller limits.',
		),
		array(
			'q' => 'Can I have lit channel letters in the French Quarter?',
			'a' => 'Only with Vieux Carr&eacute; Commission approval of the sign&rsquo;s size and location. Internally lit signs there are limited to 18 inches between faces, with concealed steady light and wattage caps, and no single sign may exceed 8 square feet in most of its commercial districts.',
		),
		array(
			'q' => 'How long does shipping to Louisiana take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'LSLBC thresholds from the Blue Book updated Aug. 1, 2024 (found via search 2026-10-27; La. R.S. 37:2156.1 text not read). Re-check yearly.',
		'New Orleans CZO Article 24 read from the Internet Archive copy of 2026-04-02 (czo.nola.gov refuses connections from the research PC); open it once from another network. Re-check yearly.',
		'Vieux Carr&eacute; Commission Design Guidelines Chapter 12 (signs) not read; only the zoning ordinance limits are shown.',
		'Transit days to Louisiana not shown until verified.',
	),
);
