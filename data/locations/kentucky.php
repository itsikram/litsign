<?php
/**
 * Kentucky state guide (/locations/kentucky/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$lou_signs = 'https://louisvilleky.gov/government/office-planning/sign-regulations';

return array(
	'slug' => 'kentucky',
	'name' => 'Kentucky',
	'abbr' => 'KY',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Kentucky',
	'neighbors' => array('indiana', 'ohio', 'west-virginia', 'virginia', 'tennessee', 'missouri', 'illinois'),
	'seo_title' => 'Channel Letters Shipped to Kentucky | Louisville Sign Rules',
	'seo_description' => 'Channel letters shipped to Kentucky: DHBC electrical licenses, Lexington electrical permits, and Louisville sign and banner permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Kentucky',
	'intro' => array(
		'Kentucky businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Kentucky electrician. Banners and window graphics are printed in Renton, WA.',
		'Kentucky licenses electrical work statewide through the Department of Housing, Buildings and Construction (DHBC): electrical contractors, master electricians and electricians. In Lexington, an electrical contractor can pull permits if it holds a master electrician license or employs one.',
		'Louisville Metro requires a sign permit for permanent signs and even for temporary banners, and a storefront on a landmark or in a preservation district or design overlay gets an extra review folded into the same application.',
	),
	'facts' => array(
		'Licensing agency' => 'Kentucky Department of Housing, Buildings and Construction (DHBC)',
		'Licenses' => 'Electrical contractor, master electrician, electrician',
		'Louisville permanent signs' => 'Sign permit (Land Development Code ch. 1 and 8)',
		'Louisville banners' => 'Permit; up to 30 consecutive days, 90 days a year',
	),
	'categories' => array(
		'channel-letters' => 'In Louisville Metro, permanent signs need a sign permit unless Section 8.1.3 of the Land Development Code exempts them, and the limits depend on the lot&rsquo;s zoning and form district.',
		'banners' => 'Louisville requires a permit even for a temporary banner, which can stay up no more than 30 consecutive days and 90 days in total per calendar year.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Kentucky',
		'body' => array(
			'Kentucky&rsquo;s DHBC issues <strong>electrical contractor</strong>, <strong>master electrician</strong> and <strong>electrician</strong> licenses. A master electrician supervises, directs or takes responsible charge of electricians doing electrical construction, alteration or repair, and may also do the work; an electrician works under that supervision.',
			'Permits follow the license. In <strong>Lexington</strong>, electrical permits are required for all electrical work in Fayette County, and a contractor may pull them only if it holds a master electrician license or employs a master electrician and has an LFUCG business license. All electrical work must be inspected, through the Commonwealth Inspection Bureau rather than the city. Check your installer&rsquo;s license with DHBC before you hire, and confirm who will pull the electrical permit for the sign.',
		),
		'table' => array(
			'Licensing agency' => 'Department of Housing, Buildings and Construction',
			'Business' => 'Electrical contractor',
			'Individuals' => 'Master electrician; electrician',
			'Lexington permits' => 'Contractor holding or employing a master electrician',
		),
		'sources' => array(
			array('815 KAR 35:060: Licensing of electrical contractors, master electricians and electricians', 'https://apps.legislature.ky.gov/law/kar/titles/815/035/060/'),
			array('Lexington-Fayette Urban County Government: Electrical permits, licensing and inspections', 'https://www.lexingtonky.gov/electrical-permits-licensing-inspections'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Kentucky',
		'body' => array(
			'Kentucky cities and counties issue sign permits under their own zoning rules, and a lit sign also needs an electrical permit.',
			'In <strong>Louisville Metro</strong>, signs are regulated by the Metro Code of Ordinances and the Land Development Code, and permanent on-premises signs follow Chapters 1 and 8 of the code, with limits that depend on the lot&rsquo;s zoning and form district. Permanent signs need a sign permit unless they are one of the types exempted in Section 8.1.3, and applications are filed online. A sign in the public right-of-way may also need an encroachment permit from Public Works.',
			'These rules cover Louisville Metro&rsquo;s zoning area, which includes unincorporated Jefferson County and the small cities that haven&rsquo;t adopted their own version of the code; a few cities in the county run their own zoning, so check which office covers your address.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Louisville permanent signs' => 'Sign permit unless exempt under LDC &sect; 8.1.3',
			'Louisville right-of-way' => 'Encroachment permit from Public Works',
			'Electrical permit' => 'Licensed electrical contractor',
		),
		'sources' => array(
			array('Louisville Metro: Sign regulations', $lou_signs),
		),
	),
	'districts' => array(
		array(
			'name' => 'Louisville landmarks and preservation districts',
			'rule' => 'A sign on an individual landmark or in a local preservation district needs a Certificate of Appropriateness if it will be in place more than 6 months. When no committee review is required, it is handled as part of the sign permit review.',
			'source' => $lou_signs,
		),
		array(
			'name' => 'Louisville design overlay districts',
			'rule' => 'A sign in a design overlay district needs an Overlay Permit if it will stay up more than 2 months, reviewed with the sign permit unless a committee must approve it.',
			'source' => $lou_signs,
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Temporary Signs in Louisville',
		'body' => array(
			'Louisville treats temporary signs as their own category, covering small freestanding signs, banners, window signs and rent, sale and construction signs, under the Metro Code. A <strong>temporary banner needs a sign permit</strong> and may stay up no more than <strong>30 consecutive days</strong>, and no more than <strong>90 days in total</strong> in a calendar year. Temporary freestanding signs also need a permit and are limited to 120 days a year.',
		),
		'sources' => array(
			array('Louisville Metro: Sign regulations', $lou_signs),
		),
	),
	'climate' => array(
		'Kentucky storefronts see humid summers with strong thunderstorms, spring flooding along the Ohio River valley, and winter ice storms that can load signs and raceways. Ask your installer to seal wall penetrations and anchor letters for gusty storm winds.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Kentucky Storefronts',
		'lead' => 'Lit letters for Louisville and Lexington centers and small-town main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for suburban shopping centers in Kentucky.',
			'standard-channel-letter-back-lit' => 'A soft glow on historic brick storefronts.',
			'hidden-back-halo-lit' => 'A refined halo look for offices and boutiques.',
			'13oz-vinyl-banner' => 'Permitted grand opening and sale banners in Kentucky.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Kentucky?',
			'a' => 'We ship custom channel letters anywhere in Kentucky, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed Kentucky electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign electrician need in Kentucky?',
			'a' => 'A DHBC electrician or master electrician license, working for a licensed electrical contractor. A master electrician supervises or takes responsible charge of the work, and in Lexington the contractor needs a master electrician on staff to pull the electrical permit.',
		),
		array(
			'q' => 'Do I need a permit for a banner in Louisville?',
			'a' => 'Yes. Louisville Metro requires a sign permit for temporary banners, which may stay up no more than 30 consecutive days and 90 days in total per calendar year. Temporary freestanding signs also need a permit and are limited to 120 days a year.',
		),
		array(
			'q' => 'Does a Louisville historic district change my sign approval?',
			'a' => 'Yes, for signs staying more than 6 months: a landmark or local preservation district needs a Certificate of Appropriateness, and a design overlay needs an Overlay Permit after 2 months. Both are usually reviewed with the sign permit itself, and a sign in the right-of-way may also need a Public Works encroachment permit.',
		),
		array(
			'q' => 'How long does shipping to Kentucky take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Louisville sign regulations page read from the Internet Archive copy of 2025-05-30 (louisvilleky.gov returns 403 to the research PC); open it once from another network. The July 2025 sign permit application (fees reported as $200 for one sign, $350 for two or more) was not read, so fees are not shown.',
		'Louisville LDC Chapter 8 size limits not read; none shown.',
		'Transit days to Kentucky not shown until verified.',
	),
);
