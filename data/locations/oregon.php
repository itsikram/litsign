<?php
/**
 * Oregon state guide (/locations/oregon/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$pdx_signs = 'https://www.portland.gov/ppd/sign-permits';
$pdx_aboards = 'https://www.portland.gov/ppd/sign-permits/portable-signs-boards';

return array(
	'slug' => 'oregon',
	'name' => 'Oregon',
	'abbr' => 'OR',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Oregon',
	'neighbors' => array('washington', 'idaho', 'nevada', 'california'),
	'seo_title' => 'Channel Letters Shipped to Oregon | Limited Sign License',
	'seo_description' => 'Channel letters shipped to Oregon: limited sign contractor and sign electrician licenses, Portland sign permits and new A-frame rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Oregon',
	'intro' => array(
		'Oregon businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Oregon sign installer. Banners, window graphics and A-frames are made in Renton, WA.',
		'Oregon has a full set of sign-specific electrical licenses. The Building Codes Division licenses Limited Journeyman Sign Electricians, who must work for a Limited Sign Contractor, and the sign contractor license covers installing and servicing electrical signs and outline lighting, including extending an existing sign circuit a short distance.',
		'Portland Permitting &amp; Development issues permits for most signs on private property. A lit sign needs a sign permit signed by the electrician or the owner plus a separate electrical permit, and letters mounted flat against the wall don&rsquo;t need engineered structural calculations.',
	),
	'facts' => array(
		'Licensing agency' => 'Oregon Building Codes Division (individuals); Construction Contractors Board (businesses)',
		'Sign contractor' => 'Limited Sign Contractor (CLS)',
		'Sign electrician' => 'Limited Journeyman Sign Electrician (SIG)',
		'Portland' => 'Sign permit + separate electrical permit for lit signs',
	),
	'categories' => array(
		'channel-letters' => 'Portland requires engineered structural plans only for signs that aren&rsquo;t flush-mounted or flat against a wall, so wall-mounted channel letters usually skip that step.',
		'banners' => 'Portland requires temporary banners on private property to be registered with Permitting &amp; Development, so register the opening banner before it goes up.',
		'flags-aframes' => 'Since July 25, 2026, Portland no longer requires A-frames to be registered, but on the sidewalk they must sit within 6 inches of the curb and leave a 6-foot clear path.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Oregon',
		'body' => array(
			'A <strong>Limited Sign Contractor</strong> may install and service electrical signs and outline lighting, and may extend a sign branch circuit up to 15 feet if a dedicated branch circuit already exists at the location. Under Oregon&rsquo;s rules, a limited sign contractor must employ at least one full-time Limited Journeyman Sign Electrician.',
			'A <strong>Limited Journeyman Sign Electrician</strong> works for a limited sign contractor and may not install a branch circuit from an electrical panel, install control equipment that isn&rsquo;t on the same wall or post as the sign, or work on service equipment. That work, including a new circuit to the sign, needs a general electrical contractor and journeyman. Check any license in the Oregon license directory before you hire.',
		),
		'table' => array(
			'Business' => 'Limited Sign Contractor (CLS)',
			'Individual' => 'Limited Journeyman Sign Electrician (SIG), employed by a sign contractor',
			'Branch circuits' => 'Extend an existing dedicated sign circuit up to 15 ft only',
			'New circuits and service work' => 'General electrical contractor and journeyman',
		),
		'sources' => array(
			array('Oregon License Directory: Limited Sign Contractor', 'https://apps.oregon.gov/SOS/LicenseDirectory/LicenseDetail/1376'),
			array('Oregon License Directory: Limited Journeyman Sign Electrician', 'https://apps.oregon.gov/SOS/LicenseDirectory/LicenseDetail/484'),
			array('OAR 918-282-0220: Limited Journeyman Sign Electrician license', 'https://www.law.cornell.edu/regulations/oregon/Or-Admin-Code-SS-918-282-0220'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Oregon',
		'body' => array(
			'Oregon cities issue sign permits under their own sign codes, and electrical permits are issued by the city or county where it runs its own electrical program, or otherwise by the state.',
			'In <strong>Portland</strong>, a sign permit is required when materials are placed or constructed to convey a message, which covers painted, flush-mounted, projecting, freestanding and directional signs and fabric awnings. For an internally illuminated sign, the application must be signed by the electrician doing the work or the property owner, and a separate electrical permit application is required. Apply through DevHub PDX with one application per sign, including a site plan, elevations, attachment details and a sign-to-wall ratio calculation, and expect inspections, including one at completion.',
			'Structural plans and calculations prepared by an Oregon-registered engineer are required for every sign that isn&rsquo;t flush-mounted or flat against a wall, such as projecting and freestanding signs.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Portland' => 'Portland Permitting &amp; Development, through DevHub PDX',
			'Portland lit signs' => 'Application signed by the electrician or owner, plus an electrical permit',
			'Portland structural' => 'Engineer&rsquo;s calculations for signs not flat against a wall',
		),
		'sources' => array(
			array('City of Portland: Sign and awning permits', $pdx_signs),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and A-frames in Portland',
		'body' => array(
			'Temporary banners on private property must be <strong>registered</strong> with Portland Permitting &amp; Development.',
			'A-frames, which Portland calls portable signs or A-boards, no longer need registration as of <strong>July 25, 2026</strong>, but the placement rules still apply. On the sidewalk, one may stand within 6 inches of the curb, leaving a clear pedestrian path at least 6 feet wide, and none are allowed on sidewalks narrower than 8 feet. In the right-of-way an A-frame is limited to 8 square feet, 48 inches tall and 30 inches wide; on private property it may be up to 12 square feet. A business gets one per public entrance, and tenants sharing an entrance share one.',
		),
		'sources' => array(
			array('City of Portland: Sign and awning permits', $pdx_signs),
			array('City of Portland: Portable signs (A-boards)', $pdx_aboards),
		),
	),
	'climate' => array(
		'Western Oregon is wet for months at a time, the coast adds salt air and winter storms, and the Columbia Gorge funnels strong east winds toward Portland. Ask your installer to seal every wall penetration and keep letter drain holes clear so rain can&rsquo;t collect inside.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Oregon Storefronts',
		'lead' => 'Lit letters for Portland blocks and suburban centers, plus A-frames for rainy-season sidewalks.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters that cut through gray, rainy evenings.',
			'halo-reverse-acrylic-lit-channel-letters' => 'A halo glow popular with Portland restaurants and shops.',
			'adhesive-window-perf' => 'Window graphics for street-level storefronts.',
			'deluxe-signicade-graphic-frame' => 'Weighted sidewalk A-frames for foot traffic.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Oregon?',
			'a' => 'We ship custom channel letters anywhere in Oregon, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an Oregon limited sign contractor or electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign installer need in Oregon?',
			'a' => 'The business needs a Limited Sign Contractor license, or a general electrical contractor license, and the person doing the work a Limited Journeyman Sign Electrician license or a general journeyman license. A sign electrician can extend an existing sign circuit up to 15 feet but can&rsquo;t run a new one from the panel.',
		),
		array(
			'q' => 'Who signs the permit for a lit sign in Portland?',
			'a' => 'For an internally illuminated sign, the sign permit application must be signed by the electrician performing the work or the property owner, and a separate electrical permit is required. Signs that aren&rsquo;t flat against a wall also need an Oregon engineer&rsquo;s calculations.',
		),
		array(
			'q' => 'Do I need to register my A-frame in Portland?',
			'a' => 'Not anymore: registration ended on July 25, 2026. The placement rules still apply: within 6 inches of the curb, a 6-foot clear path, no sidewalks under 8 feet wide, and one per public entrance, up to 8 square feet in the right-of-way. On private property the limit is 12 square feet.',
		),
		array(
			'q' => 'How long does shipping to Oregon take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'OAR text was current through September 2024; re-check the license directory before going live.',
		'Portland sign permit and A-board pages read directly 2026-10-27 (no page dates shown). The Title 32 banner exemption limits (three per lot, 32 sq ft) came only from search snippets of the code and are left off.',
		'Transit days to Oregon not shown until verified.',
	),
);
