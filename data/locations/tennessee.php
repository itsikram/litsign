<?php
/**
 * Tennessee state guide (/locations/tennessee/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$nash_sign = 'https://www.nashville.gov/departments/codes/construction-and-permits/sign-permit-process';
$nash_viol = 'https://www.nashville.gov/departments/codes/property-standards/code-enforcement/codes-violation-types';

return array(
	'slug' => 'tennessee',
	'name' => 'Tennessee',
	'abbr' => 'TN',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Tennessee',
	'neighbors' => array('kentucky', 'virginia', 'north-carolina', 'georgia', 'alabama', 'mississippi', 'arkansas', 'missouri'),
	'seo_title' => 'Channel Letters Shipped to Tennessee | CE & LLE Licenses',
	'seo_description' => 'Channel letters shipped to Tennessee: CE contractors for $25,000+ jobs, LLE electricians for smaller ones, and Nashville sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Tennessee',
	'intro' => array(
		'Tennessee businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Tennessee electrician. Banners and window graphics are printed in Renton, WA.',
		'Tennessee licenses electrical work by the size of the job. Work of $25,000 or more, materials and labor included, needs a state contractor with the CE (electrical) classification, which the state calls comparable to a master electrician. Below that, the Limited Licensed Electrician (LLE) license applies, unless your city or county requires its own journeyman or master license instead.',
		'In Nashville, a sign needs a zoning permit and a building permit before it is built or installed, the installer certifies the finished sign to Codes, and every approved sign carries a permanent compliance tag.',
	),
	'facts' => array(
		'Licensing agency' => 'Tennessee Department of Commerce &amp; Insurance',
		'Jobs of $25,000+' => 'Contractor with the CE (electrical) classification',
		'Jobs under $25,000' => 'Limited Licensed Electrician (LLE) or a local license',
		'Nashville' => 'Zoning + building permit; permanent compliance tag',
	),
	'categories' => array(
		'channel-letters' => 'Nashville lets individually mounted letters rise above a wall or parapet by up to half their height, while a solid panel sign can only go 25 percent above, to a maximum of 18 inches.',
		'banners' => 'In Davidson County, banners may only announce a grand opening or a closing, and they must be permitted; portable signs aren&rsquo;t allowed at all.',
		'flags-aframes' => 'Davidson County doesn&rsquo;t permit portable signs, and nothing may be posted on public property such as utility poles or bus benches, so keep displays on your own lot and check the zoning code first.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Tennessee',
		'body' => array(
			'The dividing line is <strong>$25,000</strong>, counting materials and labor unless the owner supplies them (Rule 0680-1-.13). For electrical work of $25,000 or more, the job needs a state contractor holding the <strong>CE</strong> classification, which the Department of Commerce &amp; Insurance describes as comparable to a master electrician.',
			'For smaller jobs, the <strong>Limited Licensed Electrician (LLE)</strong> license covers work under $25,000 per project, and it is required where the municipality uses the state Division of Fire Prevention for permits or inspections. Acceptance is local: the state tells you to check first whether your city or county takes the LLE or has its own licensing requirement for permits and inspections. The LLE is issued to individuals who pass the LLE trade exam, which isn&rsquo;t reciprocal with other states.',
		),
		'table' => array(
			'Licensing agency' => 'Tennessee Department of Commerce &amp; Insurance',
			'$25,000 and up' => 'State contractor with CE classification',
			'Under $25,000' => 'LLE, where your local codes office accepts it',
			'Local licenses' => 'Some cities and counties require their own journeyman or master license',
		),
		'sources' => array(
			array('Tennessee Department of Commerce &amp; Insurance: Limited Licensed Electrician (LLE)', 'https://www.tn.gov/commerce/regboards/contractors/license/get/lle.html'),
			array('Tennessee Department of Commerce &amp; Insurance: Who needs an LLE license?', 'https://www.tn.gov/commerce/regboards/lle/who-needs-a-limited-licensed-electrician--lle--license-.html'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Tennessee',
		'body' => array(
			'Tennessee cities and counties issue sign permits under their own zoning codes, and a lit sign also needs a local electrical permit.',
			'In <strong>Nashville</strong> and Davidson County, no sign may be built, installed or altered without a zoning permit, a building permit and any other applicable permits. The property owner or the owner&rsquo;s legal representative applies by emailing the Commercial Permit Application to Metro Codes with a site plan showing distances to property lines and dimensioned renderings, and the file is assigned to a zoning examiner. Once the sign is up, the person who built it certifies to Codes that it was built legally, and every sign displays a permanent compliance tag issued at final approval.',
			'Two situations change the rules. If your new business is a different activity type from the last one, such as food service to retail, a Use and Occupancy permit comes before the sign permit. And an older sign permitted before February 8, 1993 that doesn&rsquo;t meet today&rsquo;s ordinance must be brought into compliance when an alteration exceeds 50 percent of its replacement cost. Roof signs are prohibited.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Nashville permits' => 'Zoning permit + building permit before installation',
			'Nashville application' => 'By email: application, site plan, dimensioned renderings',
			'After installation' => 'Builder certifies to Codes; permanent compliance tag',
			'Change of activity type' => 'Use and Occupancy permit first',
		),
		'sources' => array(
			array('Metro Nashville Codes: Sign permit process', $nash_sign),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Portable Displays in Nashville',
		'body' => array(
			'Metro Codes lists the common violations: <strong>portable signs are not permitted in Davidson County</strong>, nothing may be placed on public property such as utility poles or bus benches, and <strong>banners can only announce a grand opening or a closing, and must be permitted</strong>. Plan your opening banner into the permit schedule rather than hanging it the week you open.',
		),
		'sources' => array(
			array('Metro Nashville Codes: Codes violation types', $nash_viol),
		),
	),
	'climate' => array(
		'Tennessee sits in a busy severe-weather corridor: spring brings strong thunderstorms and tornado warnings across Middle and West Tennessee, summers are hot and humid, and East Tennessee valleys see occasional ice. Ask your installer to anchor letters for straight-line winds.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Tennessee Storefronts',
		'lead' => 'Lit letters for Nashville, Memphis and Knoxville storefronts and small-town squares.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for suburban shopping centers in Tennessee.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for nightlife districts.',
			'standard-channel-letter-back-lit' => 'A warm glow on courthouse-square brick.',
			'13oz-vinyl-banner' => 'Permitted grand opening banners in Tennessee.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Tennessee?',
			'a' => 'We ship custom channel letters anywhere in Tennessee, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a licensed Tennessee electrician or contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign electrician need in Tennessee?',
			'a' => 'It depends on the job total. At $25,000 or more, including materials and labor, a state contractor with the CE classification is required. Below that, an LLE license applies where your local codes office accepts it; some cities require their own journeyman or master license.',
		),
		array(
			'q' => 'How far can channel letters stick up above a Nashville roofline?',
			'a' => 'Roof signs are prohibited, but individually mounted letters may extend above the wall or parapet by no more than 50 percent of their height. A solid panel sign is limited to 25 percent of its height, up to 18 inches. Metro Codes checks this when it reviews the sign&rsquo;s zoning and building permits.',
		),
		array(
			'q' => 'Can I hang a banner at my Nashville business?',
			'a' => 'Only to announce a grand opening or a closing, and it must be permitted. Metro Codes also says portable signs aren&rsquo;t permitted anywhere in Davidson County, and nothing may be posted on public property such as utility poles or bus benches, so keep the banner on your own building.',
		),
		array(
			'q' => 'How long does shipping to Tennessee take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'The old TN electrical licensing page (regboards/lle/electrical-licensing.html) now returns 404. The materials-and-labor rule (Rule 0680-1-.13), the CE "comparable to a Master Electrician" wording and LLE non-reciprocity were read from its Internet Archive copy of 2025-09-02. The LLE scope (Division of Fire Prevention areas, under $25,000) comes from the new LLE page, archive copy of 2026-08-15 (tn.gov returns 403 here). Re-check the threshold yearly.',
		'A 2025 Board application guide reportedly says the LLE isn&rsquo;t accepted in Nashville, Knoxville, Chattanooga or Memphis; not read, so not shown.',
		'Nashville sign permit page (last updated March 27, 2023) read from the archive copy of 2026-07-31, and the violation types page from 2026-02-11.',
		'A Feb. 12, 2026 Planning Commission staff report on rewriting Metro Code Chapter 17.32 (window signs, temporary signs up to 120 days a year) could not be read (403). Check whether it passed before the next update; window and temporary sign rules may change.',
		'Whether an LLE may install and connect an electric sign was not confirmed with the state.',
		'Transit days to Tennessee not shown until verified.',
	),
);
