<?php
/**
 * Wisconsin state guide (/locations/wisconsin/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$sps305 = 'https://docs.legis.wisconsin.gov/code/admin_code/sps/safety_and_buildings_and_environment/301_319/305.pdf';
$mke_faq = 'https://city.milwaukee.gov/DNS/planning/StorefrontSignage.pdf';

return array(
	'slug' => 'wisconsin',
	'name' => 'Wisconsin',
	'abbr' => 'WI',
	'status' => 'review',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Wisconsin',
	'neighbors' => array('michigan', 'minnesota', 'iowa', 'illinois'),
	'seo_title' => 'Channel Letters Shipped to Wisconsin | Milwaukee Permits',
	'seo_description' => 'Channel letters shipped to Wisconsin: the electric sign exemption in state law, DSPS contractors, and Milwaukee window and sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Wisconsin',
	'intro' => array(
		'Wisconsin businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a Wisconsin installer and a DSPS-licensed electrician. Banners and window graphics are printed in Renton, WA.',
		'Wisconsin has licensed both electrical contractors and electricians statewide since April 2014, through the Department of Safety and Professional Services (DSPS). State law carves out one exception that matters for signs: installing manufactured equipment, electric signs included, is exempt when the work only involves the conductors beyond the last box feeding it.',
		'In Milwaukee, every permanent sign needs a permit from the Permit &amp; Development Center, permanent window signs may cover up to 25 percent of the window, and banners aren&rsquo;t allowed as permanent signs.',
	),
	'facts' => array(
		'Licensing agency' => 'Wisconsin Department of Safety and Professional Services (DSPS)',
		'Business' => 'Electrical contractor license',
		'Electric sign exemption' => 'Wis. Stat. &sect; 101.862(4)(g), beyond the last box only',
		'Milwaukee sign permit' => 'Permit &amp; Development Center',
	),
	'categories' => array(
		'channel-letters' => 'Milwaukee lists individual letters mounted on the building as an allowed type of wall sign; in its most restrictive districts all wall signs together are capped at 18 square feet.',
		'window-graphics' => 'Milwaukee lets permanent window signs cover up to 25 percent of the window area, and temporary window promotions need no permit for up to 30 days within the same 25 percent.',
		'banners' => 'Milwaukee doesn&rsquo;t allow banners, or exterior signs of paper, cardboard or flexible plastic, as permanent signs, so a banner can only bridge a short opening period.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Wisconsin',
		'body' => array(
			'Under Wis. Stat. &sect; 101.862 and SPS 305, a business needs an <strong>electrical contractor</strong> license from DSPS before taking on electrical wiring work, and it must use appropriately licensed or registered people: <strong>master</strong>, residential master, <strong>journeyman</strong> and industrial journeyman electricians, with registered electricians and apprentices working under them. Where a permit is required, wiring can&rsquo;t begin until it is issued.',
			'The statute also lists who doesn&rsquo;t need a license. Under &sect; 101.862(4)(g), a person installing, repairing or maintaining manufactured equipment, <strong>including electric signs</strong>, ballasts and luminaires, is exempt when the work only modifies or installs conductors beyond the disconnecting point or the last junction, pull or device box nearest the work. In practice a sign crew can hang and connect your letters to a prepared box, but the branch circuit and that box have to be installed by licensed electricians.',
		),
		'table' => array(
			'Licensing agency' => 'Wisconsin DSPS',
			'Business' => 'Electrical contractor license',
			'Individuals' => 'Master and journeyman electricians; registered electricians and apprentices',
			'Sign exemption' => 'Conductors beyond the last box nearest the sign (&sect; 101.862(4)(g))',
			'Circuit and box' => 'Licensed electricians',
		),
		'sources' => array(
			array('Wisconsin DSPS: Electrical contractor', 'https://dsps.wi.gov/Pages/Professions/ElectricalContractor/Default.aspx'),
			array('Wis. Admin. Code ch. SPS 305 (note to SPS 305.41 quoting Wis. Stat. &sect; 101.862(4)) (PDF)', $sps305),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Wisconsin',
		'body' => array(
			'Wisconsin cities, villages and towns issue sign permits under their own zoning codes, and a lit sign also needs an electrical permit before the circuit is wired.',
			'In <strong>Milwaukee</strong>, all permanent signs need a permit from the Permit &amp; Development Center at 809 N. Broadway, and in a locally designated historic district the Historic Preservation Commission must approve the sign as well. The city&rsquo;s storefront sign guide lists individual letters among the allowed wall sign types. In the most restrictive districts a business gets 18 square feet of wall signs in total, plus one projecting sign of up to 12 square feet; an awning sign is limited to 10 square feet unlit or 5 square feet if internally lit. Graphics painted on a wall count as signs too.',
		),
		'table' => array(
			'Sign permit' => 'Your city, village or town',
			'Milwaukee' => 'Permit &amp; Development Center; HPC approval in historic districts',
			'Most restrictive districts' => '18 sq ft of wall signs in total; one projecting sign up to 12 sq ft',
			'Milwaukee awnings' => '10 sq ft unlit, 5 sq ft internally lit',
		),
		'sources' => array(
			array('City of Milwaukee DNS: Storefront signs FAQ (PDF)', $mke_faq),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Milwaukee',
		'body' => array(
			'Permanent window signs may cover up to <strong>25 percent of the window area</strong>. Temporary promotions placed inside the window don&rsquo;t need a permit as long as they stay up <strong>no more than 30 days</strong> and, together, cover no more than 25 percent of the store&rsquo;s windows. Anything advertising products, services or specials that stays up longer than 30 days is treated as a sign and needs a permit.',
		),
		'sources' => array(
			array('City of Milwaukee DNS: Storefront signs FAQ (PDF)', $mke_faq),
		),
	),
	'climate' => array(
		'Wisconsin winters are long, with sub-zero cold, lake-effect snow along Lake Michigan and Lake Superior, and plenty of road salt in the slush. Mount letters above snow-pile height, and ask your installer to seal penetrations and keep raceway drain holes clear.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Wisconsin Storefronts',
		'lead' => 'Lit letters for main streets and shopping centers across the state.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters that read through winter evenings.',
			'standard-channel-letter-back-lit' => 'A soft glow on brick downtown storefronts in Wisconsin.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for supper clubs and cafes.',
			'18oz-blockout-banner' => 'Heavy banners for wind and weather in Wisconsin.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Wisconsin?',
			'a' => 'We ship custom channel letters anywhere in Wisconsin, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a local sign installer and a DSPS-licensed electrician to mount and connect them.',
		),
		array(
			'q' => 'Does a sign installer need an electrical license in Wisconsin?',
			'a' => 'Not for connecting the sign itself, if the work stays beyond the last junction or device box nearest the sign; state law exempts installing manufactured equipment, including electric signs, in that case. The circuit and the box feeding it need licensed electricians.',
		),
		array(
			'q' => 'Can I hang a banner on my Milwaukee storefront?',
			'a' => 'Not as a permanent sign. Milwaukee doesn&rsquo;t allow banners, or exterior signs of paper, cardboard or flexible plastic, as permanent signage. Temporary promotions inside the window are fine without a permit for up to 30 days within 25 percent of the glass.',
		),
		array(
			'q' => 'How much of a Milwaukee shop window can graphics cover?',
			'a' => 'Permanent window signs can cover up to 25 percent of the window area. Temporary ones also share a 25 percent limit and can stay up 30 days without a permit; after that, they count as signs that need one. Permanent signs get their permit from the Permit &amp; Development Center.',
		),
		array(
			'q' => 'How long does shipping to Wisconsin take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Wis. Stat. &sect; 101.862(4)(g) read from the note to SPS 305.41 in the SPS 305 PDF, Internet Archive copy of 2026-05-09 (docs.legis.wisconsin.gov refuses connections from the research PC). The &sect; 101.862(4)(d) low-voltage exemption was not confirmed in that text and is left off.',
		'Milwaukee storefront signs FAQ (DNS, 02/22/2021) read from the Internet Archive copy of 2025-03-16; Milwaukee Code Chapter 244 itself was not read. Re-check yearly.',
		'Madison sign permits (Chapter 31, Street Graphics Control) not researched.',
		'Transit days to Wisconsin not shown until verified.',
	),
);
