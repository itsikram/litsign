<?php
/**
 * Maine state guide (/locations/maine/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$me_1202b = 'https://legislature.maine.gov/statutes/32/title32sec1202-B.html';
$me_1914 = 'https://legislature.maine.gov/statutes/23/title23sec1914.html';
$pwm_luc = 'https://content.civicplus.com/api/assets/a5dcd1dc-4117-40b3-b3da-74a56919e14b';

return array(
	'slug' => 'maine',
	'name' => 'Maine',
	'abbr' => 'ME',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Maine',
	'neighbors' => array('new-hampshire'),
	'seo_title' => 'Channel Letters Shipped to Maine | Outdoor Sign License',
	'seo_description' => 'Channel letters shipped to Maine: the outdoor signs limited license, the state billboard ban, and Portland sign and window rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Maine',
	'intro' => array(
		'Maine businesses can order custom LED channel letters online, built in Washington and California and shipped ready for a licensed Maine electrician. Banners and window graphics are printed in Renton, WA.',
		'Maine&rsquo;s Electricians&rsquo; Examining Board issues a <strong>limited electrician license in outdoor signs</strong>, including sign lighting, but the statute draws a clear boundary: that license may not perform electrical work on branch circuit wiring, so a master or journeyman runs the circuit.',
		'Maine also bans billboards statewide and sets its own limits on on-premises signs, while Portland, the largest city, requires a permit for every permanent sign and each change of copy and caps window signs at half of each window.',
	),
	'facts' => array(
		'Licensing agency' => 'Electricians&rsquo; Examining Board',
		'Sign license' => 'Limited electrician, outdoor signs (no branch circuits)',
		'Electrical permit' => 'From the board, or the town where it inspects',
		'Billboards' => 'Banned statewide (23 M.R.S. &sect; 1908)',
	),
	'categories' => array(
		'channel-letters' => 'Portland wants wiring to building signs concealed and allows raceway cabinets only when the wall behind the sign can&rsquo;t be reached, so ask your installer about direct-mount letters with remote power supplies.',
		'window-graphics' => 'In Portland, temporary and permanent window signs together, including perforated vinyl, can cover no more than 50 percent of each window, on the first and second stories only.',
		'banners' => 'A Portland wall banner needs a temporary sign permit good for 60 days, up to 180 days in a calendar year, and it can&rsquo;t be lit.',
		'flags-aframes' => 'Portland A-frames can be up to 4 feet tall, 2 feet wide and 8 square feet, with a permit when placed in the right-of-way and a 4-foot walkway kept clear.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Maine',
		'body' => array(
			'Maine licenses apprentices, helpers, journeymen-in-training, journeymen, master electricians and limited electricians under 32 M.R.S. &sect; 1202-B, which replaced the older licensing sections in 2023. One of the limited categories is <strong>outdoor signs, including sign lighting</strong>. A limited electrician may make installations within the category without limitation, but the statute says plainly that a limited electrician in outdoor signs &ldquo;may not perform electrical work on branch circuit wiring.&rdquo;',
			'The outdoor sign license requires at least 135 hours of electrical education and 2,000 hours of board-approved work experience, plus a 45-hour National Electrical Code course within the two years before applying, and the board may require an exam. In practice, a sign company holding the license mounts and connects your letters, and a master or journeyman electrician brings the branch circuit to them.',
		),
		'table' => array(
			'Licensing agency' => 'Maine Electricians&rsquo; Examining Board',
			'Outdoor sign license' => 'Signs and sign lighting, no branch circuit wiring',
			'Education' => '135 hours, plus a 45-hour NEC course',
			'Work experience' => '2,000 hours',
			'Branch circuit' => 'Master or journeyman electrician',
		),
		'sources' => array(
			array('32 M.R.S. &sect; 1202-B: Issuance and scope of licenses', $me_1202b),
		),
	),
	'permits' => array(
		'heading' => 'Electrical Permits and Sign Rules in Maine',
		'body' => array(
			'Unless an exception applies, a permit must be obtained from the Electricians&rsquo; Examining Board before an electrical installation, and only a licensed master or limited electrician may apply (32 M.R.S. &sect; 1102-C). The work is inspected by a state electrical inspector, or by the local municipal inspector where the town has one, and the utility must see proof of the permit before connecting power. Towns that require their own permit and inspection by ordinance are exempt from the state permit.',
			'State law also governs on-premises signs. No state license or permit is needed for them except close to the road, such as within 33 feet of the center line of a public way, where a MaineDOT license applies. A business may have no more than 10 on-premises signs, building signs may rise no more than 10 feet above the roof, and signs may not use flashing, intermittent or moving light. In urban compact areas the town administers these rules (23 M.R.S. &sect; 1914).',
			'In <strong>Portland</strong>, Article 19 of the Land Use Code requires a separate sign permit for each permanent sign and each change of copy; window signs and building-mounted directional and directory signs are exempt from the permit but still must meet the standards. A permit lapses if work doesn&rsquo;t start within 180 days or stops for 180 days, and an electrical permit must be issued before any sign needing power is installed. Internally lit cabinet signs need an opaque background with lit letters or a colored background, never white, off-white, light gray, cream or yellow, and neon and directly lit signs go dark at closing or 10 p.m., whichever is later.',
		),
		'table' => array(
			'Electrical permit' => 'Electricians&rsquo; Examining Board, or the town&rsquo;s own program',
			'Applicant' => 'Licensed master or limited electrician',
			'State on-premises limits' => '10 signs; 10 ft above the roof; no flashing light',
			'Portland sign permit' => 'Each permanent sign and each change of copy',
			'Portland permit life' => '180 days to start; lapses if stopped 180 days',
		),
		'sources' => array(
			array('32 M.R.S. &sect; 1102-C: Electrical permits and inspections', 'https://legislature.maine.gov/statutes/32/title32sec1102-C.html'),
			array('23 M.R.S. &sect; 1914: On-premises signs', $me_1914),
			array('23 M.R.S. &sect; 1908: Signs visible from a public way', 'https://legislature.maine.gov/statutes/23/title23sec1908.html'),
			array('City of Portland Land Use Code, Article 19: Signs (PDF)', $pwm_luc),
		),
	),
	'districts' => array(
		array(
			'name' => 'Portland historic districts (Land Use Code 19.3.4)',
			'rule' => 'All permanent signs in a historic district must be approved by the Planning Authority under the sign standards in Subsection 16.6.7 and the Historic Resources Design Manual, in addition to the sign permit, and the stricter rule applies. Temporary signs there are capped at 12 square feet per tenant.',
			'source' => $pwm_luc,
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and A-Frames in Portland',
		'body' => array(
			'Portland requires a temporary sign permit only for a wall banner and for an A-frame placed in the public right-of-way. A wall banner permit is valid for 60 days, a business can use up to 180 days a calendar year, banners can be up to 32 square feet, and a multi-tenant building gets one per tenant. No temporary sign may be lit, and each must show the name and address of whoever placed it and the date it went up.',
			'A-frames can be 30 inches to 4 feet tall, up to 2 feet wide and 8 square feet, and a business may use one only during its regular hours. There is no time limit for A-frames in the right-of-way, but a pedestrian walkway at least 4 feet wide must stay clear at all times.',
		),
		'sources' => array(
			array('City of Portland Land Use Code &sect; 19.8 and Tables 19-W and 19-X (PDF)', $pwm_luc),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Portland',
		'body' => array(
			'Temporary and permanent window signs combined, including perforated vinyl and signs painted on the glass, can&rsquo;t exceed <strong>50 percent of the area of the window</strong> they&rsquo;re on, and they&rsquo;re allowed only on first- and second-story windows. Window signs don&rsquo;t need a sign permit, so the 50 percent cap is the main limit to plan around.',
		),
		'sources' => array(
			array('City of Portland Land Use Code, Table 19-Q (PDF)', $pwm_luc),
		),
	),
	'climate' => array(
		'Maine winters bring nor&rsquo;easters with heavy, wet snow, long stretches of freezing weather and ice that loads every ledge, and coastal towns add salt spray from the Gulf of Maine. Specify stainless or coated fasteners near the water, keep letters above the snow line along the sidewalk, and ask your installer to seal penetrations so meltwater can&rsquo;t freeze and expand inside the wall.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Maine Storefronts',
		'lead' => 'Lit letters for Portland, Bangor and coastal main streets, plus window graphics within Portland&rsquo;s 50 percent limit.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Portland and Bangor retail.',
			'standard-channel-letter-back-lit' => 'A soft halo for brick Old Port storefronts.',
			'adhesive-window-perf' => 'Perforated window graphics within the 50 percent cap.',
			'13oz-vinyl-banner' => 'Wall banners for 60-day permit periods.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Maine?',
			'a' => 'We ship custom channel letters anywhere in Maine, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Maine-licensed master electrician, or a limited electrician in outdoor signs along with a master or journeyman for the circuit, to mount and connect them.',
		),
		array(
			'q' => 'Does Maine have a sign electrician license?',
			'a' => 'Yes, a limited electrician license in outdoor signs, including sign lighting. It takes at least 135 hours of electrical education, 2,000 hours of approved work experience and a 45-hour National Electrical Code course. The license covers sign work without limitation, but it may not perform electrical work on branch circuit wiring.',
		),
		array(
			'q' => 'Who pulls the electrical permit for my sign in Maine?',
			'a' => 'A licensed master or limited electrician, who applies to the Electricians&rsquo; Examining Board before the work unless your town runs its own permit and inspection program. A state or local inspector checks the installation, and the utility must see proof of the permit before it connects power.',
		),
		array(
			'q' => 'How much of a Portland window can graphics cover?',
			'a' => 'Up to 50 percent of each window, counting temporary and permanent window signs together, including perforated vinyl and painted signs. Window signs are allowed on first- and second-story windows only, and they don&rsquo;t need a sign permit. A wall banner does need one, valid 60 days, up to 180 days a year.',
		),
		array(
			'q' => 'How long does shipping to Maine take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'32 M.R.S. &sect;&sect; 1102-C and 1202-B and 23 M.R.S. &sect;&sect; 1908 and 1914 read directly 2026-11-20. The old source (PL 2009, c. 112) amended the repealed &sect; 1202 and was replaced; the undated state fact sheet&rsquo;s &ldquo;1,000 hours in outdoor signs&rdquo; is not in the current statute and isn&rsquo;t shown.',
		'Portland Land Use Code (effective 8/24/2026) read directly from the PDF the city links on its zoning page; portlandmaine.gov itself fails from this PC (SSL error), so the old Portland checklists were dropped. Sign permit fees not verified.',
		'The state electrical permit fee cap ($100 per scheduled service fee, 32 M.R.S. &sect; 1102-C) is not shown: re-check if added.',
		'Transit days to Maine not shown until verified.',
	),
);
