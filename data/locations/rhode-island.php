<?php
/**
 * Rhode Island state guide (/locations/rhode-island/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ri_56 = 'https://webserver.rilegislature.gov/Statutes/TITLE5/5-6/';
$pvd_zo = 'https://www.providenceri.gov/wp-content/uploads/2026/10/26.07.22-Official-Zoning-Ordinance.pdf';

return array(
	'slug' => 'rhode-island',
	'name' => 'Rhode Island',
	'abbr' => 'RI',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Rhode_Island',
	'neighbors' => array('connecticut', 'massachusetts'),
	'seo_title' => 'Channel Letters Shipped to Rhode Island | SCF Sign License',
	'seo_description' => 'Channel letters shipped to Rhode Island: SCF sign contractor and CF installer licenses, the 15-foot wire limit and Providence rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Rhode Island',
	'intro' => array(
		'Rhode Island businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a Rhode Island-licensed sign installer or electrician. Banners and window graphics are printed in Renton, WA.',
		'Rhode Island licenses sign work directly. The Board of Examiners of Electricians issues a <strong>Certificate SCF</strong> to electrical sign contractors and a <strong>Certificate CF</strong> to the individual sign installers, and the statute limits their wiring to no more than three wires, up to 15 feet long, ending in an existing electrical box beside or at the base of the sign.',
		'Providence requires a separate electrical permit for every sign with an electrical component, keeps window signs to 25 percent of each window, and bans most internally lit signs downtown except halo, reverse-channel and channel-cut letters.',
	),
	'facts' => array(
		'Licensing agency' => 'Board of Examiners of Electricians (Department of Labor and Training)',
		'Sign licenses' => 'SCF sign contractor; CF sign installer',
		'Sign wiring limit' => 'Three wires, 15 ft, to an existing box at the sign',
		'Providence window signs' => 'Up to 25% of each window',
	),
	'categories' => array(
		'channel-letters' => 'Downtown Providence (D-1) bans cabinet box signs and most internal lighting but still allows halo-lit, reverse-channel and channel-cut letters.',
		'window-graphics' => 'Providence limits temporary and permanent window signs together to 25 percent of each window, and lit window signs need a permit.',
		'banners' => 'A Providence banner needs no permit: one per structure, up to 32 square feet, for 10 days at a time and five display periods a year.',
		'flags-aframes' => 'Providence allows one A-frame per business, up to 6 square feet a side and 4 feet tall, during business hours; on the sidewalk it needs an encroachment permit.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Rhode Island',
		'body' => array(
			'Chapter 5-6 of the Rhode Island General Laws, administered by the Department of Labor and Training&rsquo;s Board of Examiners of Electricians, has its own sign licenses. A <strong>Certificate SCF</strong> goes to a person, firm or corporation in the business of electrical sign installations (&sect; 5-6-8(d)), and a <strong>Certificate CF</strong> names the individual authorized to install, maintain, work on and repair electrical signs after passing an exam (&sect; 5-6-11(c)). CF applicants need at least 4,000 hours over two years or more as a registered limited apprentice, and SCF applicants must be at least 21.',
			'The key limit is in the definitions (&sect; 5-6-1): sign work may include removing or installing &ldquo;no more than three (3) wires not to exceed fifteen feet (15&prime;) in length and terminating in an existing electrical enclosure directly adjacent to, or at the base of,&rdquo; the sign. Anything beyond that, such as a new circuit from the panel, is work for a regular journeyperson; a Certificate B covers all electrical work, electrical signs included (&sect; 5-6-12). A separate sign renovation license covers renovations needing no more than three wires.',
			'General contractors who aren&rsquo;t licensed electrical contractors may not bid on or advertise electrical work, and every licensee must follow the rules of the city or town where the work is done (&sect; 5-6-25).',
		),
		'table' => array(
			'Licensing agency' => 'Board of Examiners of Electricians, DLT',
			'Certificate SCF' => 'Electrical sign contractor (the business)',
			'Certificate CF' => 'Electrical sign installer; 4,000 hours as a limited apprentice',
			'Sign wiring limit' => 'Three wires, 15 ft, to an existing box at the sign',
			'New circuits' => 'Licensed journeyperson (Certificate B)',
		),
		'sources' => array(
			array('R.I. Gen. Laws chapter 5-6: Electricians', $ri_56),
			array('R.I. Gen. Laws &sect; 5-6-1: Definitions', 'https://webserver.rilegislature.gov/Statutes/TITLE5/5-6/5-6-1.HTM'),
			array('R.I. Gen. Laws &sect; 5-6-8: Contractor licenses', 'https://webserver.rilegislature.gov/Statutes/TITLE5/5-6/5-6-8.HTM'),
			array('260-RICR-30-15-7: Electrician licensing rules', 'https://rules.sos.ri.gov/Regulations/part/260-30-15-7'),
		),
	),
	'permits' => array(
		'heading' => 'Electrical and Sign Permits in Rhode Island',
		'body' => array(
			'Electrical permits come from the local building official under the State Building Code. Before a permit is approved, the licensed electrician must show the building official state license identification and sign the permit (R.I. Gen. Laws &sect; 23-27.3-113.3.1). The Rhode Island Electrical Code adopts the 2023 National Electrical Code, in effect since December 1, 2025.',
			'In <strong>Providence</strong>, signs are governed by Article 16 of the zoning ordinance. Electrical permits are required for signs with an electrical component, and the wiring must meet the building and electrical codes and be hidden where feasible. The sign permit application, filed with the Department of Inspection and Standards, asks who will make the electrical connections and who will hang the sign, and any sign in the public right-of-way also needs a right-of-way encroachment permit. Repainting, copy changes and repairs, even switching from fluorescent to LED lighting, are exempt.',
			'Lighting rules apply citywide: neon, LED and exposed bulbs are allowed only as part of a sign in non-residential districts, nothing may flash, and outlining doors or windows with neon or LED is prohibited.',
		),
		'table' => array(
			'Electrical permit' => 'Local building official; electrician shows state license and signs',
			'Code edition' => 'NEC 2023, effective Dec. 1, 2025',
			'Providence sign rules' => 'Zoning Ordinance Article 16',
			'Providence lit signs' => 'Separate electrical permit',
			'Right-of-way signs' => 'Encroachment permit',
		),
		'sources' => array(
			array('R.I. Gen. Laws &sect; 23-27.3-113.3.1: Licensed persons and permits', 'https://webserver.rilegislature.gov/Statutes/TITLE23/23-27.3/23-2/23-27.3-113.3.1.htm'),
			array('510-RICR-00-00-5: Rhode Island Electrical Code', 'https://rules.sos.ri.gov/regulations/part/510-00-00-5'),
			array('City of Providence: Zoning Ordinance, amended through July 22, 2026 (PDF)', $pvd_zo),
			array('City of Providence: Department of Inspection and Standards', 'https://www.providenceri.gov/inspection-standards/'),
		),
	),
	'districts' => array(
		array(
			'name' => 'Providence historic districts and Downtown D-1 (Zoning Ordinance Article 16)',
			'rule' => 'All signs in a historic district, window signs included, need Historic District Commission approval, and downtown signs need Downtown Design Review Committee or I-195 Commission approval. In D-1, cabinet box wall and projecting signs are prohibited, and internally lit signs are banned there and in the ES Overlay except reverse-channel (halo), stencil or channel-cut letters and halo lighting.',
			'source' => $pvd_zo,
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and A-Frames in Providence',
		'body' => array(
			'Banners don&rsquo;t need a permit at non-residential uses: one per structure, up to 32 square feet. A banner not tied to a specific event can stay up for 10 days; one promoting an event can go up 7 days before and must come down 2 days after. A business can use no more than five display periods a calendar year, at least 30 days apart.',
			'A-frames also need no sign permit: one per business, at least 20 feet from the next, within 15 feet of the main entrance, up to 6 square feet per side and 4 feet tall, and out only during business hours. On a public sidewalk, keep 5 feet of clearance and get a right-of-way encroachment permit.',
		),
		'sources' => array(
			array('Providence Zoning Ordinance &sect; 1606(B) and (D) (PDF)', $pvd_zo),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Providence',
		'body' => array(
			'All window signs, temporary and permanent together, are limited to <strong>25 percent of each window area</strong>, and mullions don&rsquo;t split a window into smaller ones. Lit window signs, inside or outside, need a permit, and neon or LED window signs are allowed only in commercial, waterfront and downtown districts. In historic districts and downtown, even window signs go through design review.',
		),
		'sources' => array(
			array('Providence Zoning Ordinance &sect; 1606(Q) (PDF)', $pvd_zo),
		),
	),
	'climate' => array(
		'Rhode Island&rsquo;s long coastline means salt air in most commercial districts, nor&rsquo;easters that drive rain sideways into facades, and occasional hurricanes that push storm surge up Narragansett Bay. Winters bring wet snow and freeze-thaw cycles. Specify stainless or coated hardware, and ask your installer to seal every penetration and keep the power supply in a protected, accessible spot.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Rhode Island Storefronts',
		'lead' => 'Halo and channel letters that work downtown, plus window graphics within Providence&rsquo;s 25 percent limit.',
		'products' => array(
			'hidden-back-halo-lit' => 'Halo-lit letters, allowed even in downtown Providence.',
			'standard-channel-letter-front-lit' => 'Face-lit letters for Warwick and Cranston retail centers.',
			'adhesive-window-perf' => 'Window graphics within the 25 percent limit.',
			'13oz-vinyl-banner' => 'Banners up to 32 square feet for 10-day displays.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Rhode Island?',
			'a' => 'We ship custom channel letters anywhere in Rhode Island, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an SCF-licensed electrical sign contractor with CF installers, plus a licensed electrician if a new circuit is needed, to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign installer need in Rhode Island?',
			'a' => 'A Certificate CF, the electrical sign installer&rsquo;s license, which requires an exam and at least 4,000 hours over two years as a registered limited apprentice. The business needs a Certificate SCF, the electrical sign contractor&rsquo;s license. A journeyperson with Certificate B can also do sign work, since that license covers all electrical work.',
		),
		array(
			'q' => 'Can a sign installer run a new circuit in Rhode Island?',
			'a' => 'No. Sign work is limited to removing or installing no more than three wires, up to 15 feet long, ending in an existing electrical enclosure directly next to or at the base of the sign. A new circuit from the panel is work for a licensed journeyperson employed by an electrical contractor.',
		),
		array(
			'q' => 'Can I have internally lit letters in downtown Providence?',
			'a' => 'Only certain types. In the D-1 district and the ES Overlay, internally lit signs are prohibited except reverse-channel (halo), stencil or channel-cut letters and halo lighting, and cabinet box signs are banned in D-1. Downtown signs also need Downtown Design Review Committee or I-195 Commission approval.',
		),
		array(
			'q' => 'How long does shipping to Rhode Island take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'R.I. Gen. Laws &sect;&sect; 5-6-1, 5-6-8, 5-6-11, 5-6-12, 5-6-25 and 23-27.3-113.3.1 read from Internet Archive copies dated 2025-11 to 2026-09 (webserver.rilegislature.gov times out here); 260-RICR-30-15-7 and 510-RICR-00-00-5 read directly. Closes the old SCF note.',
		'The DLT rule table lists the sign renovation license under a different code (LPR) than the statute (SRL); the page names neither code.',
		'Providence Zoning Ordinance (amendments through July 22, 2026) read directly. The sign permit application in the 2017 Guide to Permitting may have changed. License fees (&sect; 5-6-16) not checked.',
		'Transit days to Rhode Island not shown until verified.',
	),
);
