<?php
/**
 * Maryland state guide (/locations/maryland/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$bmore_zc = 'https://codes.baltimorecity.gov/us/md/cities/baltimore/code/32/';
$bmore_ref = 'https://www.baltimorecity.gov/dhcd/our-work/permits-and-inspections/permits-special-referrals';
$annap_hp = 'https://annapolis.gov/2491/Historic-Preservation-Sign-Guidelines';

return array(
	'slug' => 'maryland',
	'name' => 'Maryland',
	'abbr' => 'MD',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Maryland',
	'neighbors' => array('pennsylvania', 'delaware', 'virginia', 'west-virginia'),
	'seo_title' => 'Channel Letters Shipped to Maryland | Baltimore Sign Rules',
	'seo_description' => 'Channel letters shipped to Maryland: the state electrical license, Baltimore design review, and Annapolis historic sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Maryland',
	'intro' => array(
		'Maryland businesses can price LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Maryland electrician to mount and connect. Banners and window graphics are printed in Renton, WA.',
		'Maryland requires a state-issued license to provide electrical services, issued through the Maryland Department of Labor&rsquo;s electrical board, and counties and cities may also require a licensed electrician to register locally, so your installer may need both.',
		'Baltimore City sends larger or higher signs to Planning for design review before a permit, and in Annapolis&rsquo;s historic district a sign needs a Certificate of Approval and can&rsquo;t be internally illuminated.',
	),
	'facts' => array(
		'Licensing agency' => 'Maryland Department of Labor, electrical licensing board',
		'Who needs a license' => 'Anyone providing electrical services in Maryland',
		'Baltimore design review' => 'Signs over 100 sq ft or above the ground floor',
		'Annapolis historic district' => 'Certificate of Approval; no internal lighting',
	),
	'categories' => array(
		'channel-letters' => 'In Baltimore City, a wall sign installed above the ground floor, or any sign over 100 square feet, gets Planning design review before the permit is issued.',
		'banners' => 'Baltimore sends banners used as permanent wall signs to Planning design review, and Annapolis allows banners for a new business or promotion twice a year, up to 30 days each, with written approval.',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Maryland',
		'body' => array(
			'According to the Maryland Department of Labor, a state-issued license is required to provide electrical services, and each contractor who offers or provides electrical services in the state must hold one. The board issues the <strong>master electrician</strong> license, every active master keeps a certificate of insurance on file, and the board has announced it will begin accepting applications for apprentice and journeyperson licenses as well.',
			'Local jurisdictions may also require registration, so a state-licensed electrician may still need to register with your county or city before pulling a permit there. Ask your installer whether they are registered where your business is, and check the license on the Department of Labor&rsquo;s license search.',
		),
		'table' => array(
			'Licensing agency' => 'Maryland Department of Labor',
			'State license' => 'Master electrician; apprentice and journeyperson licenses being added',
			'Local' => 'County or city registration may also be required',
			'Insurance' => 'Certificate of insurance on file for active masters',
		),
		'sources' => array(
			array('Maryland Department of Labor: Electrical licensing FAQs', 'https://labor.maryland.gov/license/elec/eleclicfaqs.shtml'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Maryland',
		'body' => array(
			'Maryland counties and cities issue sign permits under their own zoning codes, and large counties such as Montgomery, Prince George&rsquo;s and Baltimore County each run their own permitting offices.',
			'In <strong>Baltimore City</strong>, a sign type is allowed in a zoning district only if Table 17-201 lists it there. Projecting signs may extend no more than 4 feet from the building with the bottom at least 8 feet above the sidewalk, a monument sign needs at least 50 feet of street frontage and a pole or pylon sign 100 feet, and in Areas of Special Sign Control the Planning Commission approves a signage plan before any signs go up.',
			'The housing department&rsquo;s permit office refers certain signs to the Department of Planning for design review before issuing the permit: wall signs installed above the ground floor, any sign over 100 square feet, any electronic message sign, and banners used as permanent wall signs. Exterior changes in a City Historic District need Commission for Historical and Architectural Preservation (CHAP) approval and can&rsquo;t be permitted over the counter, and anything in the public right-of-way goes to the Department of Transportation.',
		),
		'table' => array(
			'Sign permit' => 'Your county or city',
			'Baltimore projecting signs' => 'Up to 4 ft out; bottom at least 8 ft above the sidewalk',
			'Baltimore design review' => 'Signs above the ground floor, over 100 sq ft, electronic, or permanent banners',
			'Baltimore historic districts' => 'CHAP approval; no over-the-counter permit',
		),
		'sources' => array(
			array('Baltimore City DHCD: Permits requiring special referrals', $bmore_ref),
			array('Baltimore City Zoning Code &sect; 17-201: Sign regulations', $bmore_zc . '17-201'),
			array('Baltimore City Zoning Code &sect; 17-414: Projecting signs', $bmore_zc . '17-414'),
			array('Baltimore City Zoning Code &sect; 17-408: Freestanding signs', $bmore_zc . '17-408'),
			array('Baltimore City Zoning Code &sect; 17-505: Areas of Special Sign Control', $bmore_zc . '17-505'),
		),
	),
	'districts' => array(
		array(
			'name' => 'Annapolis Historic District',
			'rule' => 'Any new sign, or one changed in size, shape, content or location, needs a Certificate of Approval from the Historic Preservation Commission, filed separately from the sign permit. The city&rsquo;s sign guidelines don&rsquo;t permit internally illuminated signs; lighting should be small fixtures aimed at the sign rather than the facade, so plan non-illuminated or externally lit letters there.',
			'source' => $annap_hp,
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners in Annapolis and Baltimore',
		'body' => array(
			'In <strong>Annapolis</strong>, banners and flags announcing a new business or a promotion need written approval from the planning and zoning director and are limited to <strong>two events a year, up to 30 days each</strong>. The city&rsquo;s sign permit itself is filed through its online portal by a contractor holding a current Maryland license, with a site plan, a measured facade drawing and a drawing that shows the sign&rsquo;s wording.',
			'In <strong>Baltimore City</strong>, a banner meant to stay up as a permanent wall sign is referred to Planning for design review before it can be permitted.',
		),
		'sources' => array(
			array('City of Annapolis: Historic Preservation sign guidelines', $annap_hp),
			array('City of Annapolis: Guide for sign permits', 'https://www.annapolis.gov/2415/Guide-for-Sign-Permits'),
			array('Baltimore City DHCD: Permits requiring special referrals', $bmore_ref),
		),
	),
	'climate' => array(
		'Maryland storefronts range from Chesapeake Bay waterfronts, with salt air and tropical-storm winds, to Western Maryland mountain towns that get far more snow. On waterfront facades, ask your installer for corrosion-resistant hardware; inland, seal penetrations against freeze-thaw.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Maryland Storefronts',
		'lead' => 'Lit letters for rowhouse storefronts and suburban centers alike.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Readable lit letters for suburban shopping centers.',
			'standard-channel-letter-back-lit' => 'A softer glow for brick rowhouse storefronts.',
			'adhesive-window-perf' => 'Window graphics for narrow city storefronts.',
			'13oz-vinyl-banner' => 'Grand opening and coming soon banners.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Maryland?',
			'a' => 'We ship custom channel letters anywhere in Maryland, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Maryland-licensed electrician to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign electrician need in Maryland?',
			'a' => 'A state-issued electrical license from the Maryland Department of Labor, since one is required to provide electrical services anywhere in the state, plus any local registration your county or city requires before it issues the electrical permit. Every active master electrician also keeps a certificate of insurance on file with the board.',
		),
		array(
			'q' => 'Which Baltimore signs need design review?',
			'a' => 'Wall signs installed above the ground floor, any sign over 100 square feet, electronic message signs and banners used as permanent wall signs are referred to the Department of Planning before the permit is issued. Historic districts also need CHAP approval.',
		),
		array(
			'q' => 'Can I have lit letters in the Annapolis Historic District?',
			'a' => 'Not internally lit ones. The city&rsquo;s historic sign guidelines don&rsquo;t permit internally illuminated signs, and every new or changed sign needs a Certificate of Approval from the Historic Preservation Commission. Non-illuminated letters with small, aimed fixtures fit the guidelines.',
		),
		array(
			'q' => 'How long does shipping to Maryland take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'MD Labor licensing FAQ read directly 2026-10-27; the board&rsquo;s planned apprentice and journeyperson licenses: re-check whether they have started.',
		'Annapolis sign guidelines adopted March 27, 2008; the $30 Annapolis application fee was left off the page (re-check yearly before adding).',
		'Baltimore DOT minor-privilege details (projection permits, clearances) not verified: the 2021 guidelines PDF moved. Only the referral to DOT is shown.',
		'How Baltimore City Code 17-10 (city electrician license) relates to the state license today is still open.',
		'Transit days to Maryland not shown until verified.',
	),
);
