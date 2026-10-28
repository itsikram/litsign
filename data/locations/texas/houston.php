<?php
/**
 * Houston city guide (/locations/texas/houston/). See washington.php for the
 * rules of these files; city files return one city array.
 *
 * The Houston Permitting Center (houstonpermittingcenter.org) blocks requests
 * from the research PC, so its pages and PDFs were read from Internet Archive
 * copies (sign administration page 2026-05-15, permit page 2026-06-10, Sign
 * Code effective July 29, 2020 and informational letters 2024-12-12). The
 * links below are the City's own URLs.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$hpc = 'https://www.houstonpermittingcenter.org';
$sign_admin = $hpc . '/building-code-enforcement/sign-administration';
$sign_code = $hpc . '/media/5591/download?inline=';

return array(
	'name' => 'Houston',
	'status' => 'live',
	'updated' => '2026-10-27',
	'county' => 'Harris County',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Houston',
	'card' => 'Sign Administration permits; licensed sign contractors install',
	'seo_title' => 'Channel Letters in Houston, TX: Sign Permits & Prices',
	'seo_description' => 'Channel letters and window graphics for Houston, shipped from WA & CA: Houston sign permit steps, banner limits and who installs. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs for Houston Businesses',
	'intro' => array(
		'Houston businesses can price custom LED channel letters online, have them built in Washington or California, and get them shipped to the storefront, ready for a licensed Houston sign contractor to permit and install. Banners, window graphics and flags are made in Renton, WA and ship the same way.',
		'Houston runs signs differently from most Texas cities. One office, Sign Administration at the Houston Permitting Center, permits every business sign under the Houston Sign Code, and a sign&rsquo;s allowed height, size and lighting depend on the street it faces.',
		'The City licenses sign installers, not sign makers: apart from small non-electric signs, a licensed sign contractor pulls the permit and does the work.',
	),
	'facts' => array(
		'Sign permit' => 'Sign Administration, Houston Permitting Center',
		'Who applies' => 'A City-licensed sign contractor, online',
		'Sign code' => 'Houston Sign Code (Building Code ch. 46)',
		'Signs per business' => 'Up to five permanent on-premise signs',
	),
	'categories' => array(
		'channel-letters' => 'Storefront letters count as wall signs: four per business at most, covering under half the wall and staying below the roofline.',
		'banners' => 'Grand opening banners need a limited-use permit: one at a time, up to 40 square feet, flat on the wall, for seven days in any 30.',
		'window-graphics' => 'Advertising may cover only 20 percent of a glass storefront, and a lit or framed window display over 6 square feet is permitted as a wall sign.',
		'flags-aframes' => 'Logo flags need a permit, and Houston issues no new permits for portable displays, so ask before an A-frame goes outside.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Houston',
		'body' => array(
			'Two licenses meet on a Houston sign job. Anyone erecting signs for someone else needs a City sign contractor license, with insurance on file and a $25,000 removal bond, and the electrical work falls under a TDLR Electrical Sign Contractor (or Electrical Contractor) license and the Houston Electrical Code.',
			'Sign Administration publishes a list of licensed sign contractors to start your search; check each one&rsquo;s TDLR license online too.',
		),
		'table' => array(
			'City license' => 'Sign contractor license from the Sign Administrator (Sign Code Sec. 4606)',
			'Removal bond' => '$25,000 bond or deposit, plus insurance on file',
			'Electrical work' => 'TDLR Electrical Sign Contractor or Electrical Contractor',
		),
		'sources' => array(
			array('Houston Sign Code (Building Code ch. 46), Sec. 4606: sign companies', $sign_code),
			array('Houston Permitting Center: List of licensed sign contractors', $hpc . '/media/6956/download?inline='),
			array('TDLR: Electrical Sign Contractor license', 'https://www.tdlr.texas.gov/electricians/apply/businesses/contractor-sign.htm'),
		),
	),
	'permits' => array(
		'heading' => 'Houston Sign Permits, Step by Step',
		'body' => array(
			'Every business sign needs a Sign Administration permit before it goes up or is altered, and only whoever does the work can hold it: a licensed sign contractor, or an owner mounting a small non-electric sign. Contractors apply online; the City lists processing at 4 to 11 business days.',
			'New signs more than 8 feet above the ground or over 60 square feet need a design drawing, and later an as-built drawing, both certified by a Texas-registered professional engineer. That covers most wall-mounted letters, so ask for it in your installer&rsquo;s quote.',
			'Lit signs get a shop inspection before installation unless UL (or other OSHA-recognized lab) labels are declared at plan review, one on each letter and power supply, visible from the ground. After the final electrical and sign inspections, the operating permit goes on the sign; it renews yearly and never transfers to another location.',
		),
		'table' => array(
			'Permit office' => 'Sign Administration, 1002 Washington Ave., 4th Floor (832-394-8890)',
			'Engineering' => 'Texas PE drawing for new signs over 8 ft above ground or over 60 sq ft',
			'Sign limits' => 'Five per business: up to 4 wall or roof, 1 ground or projecting per frontage (2 max), 3 marquee',
		),
		'sources' => array(
			array('Houston Permitting Center: Sign Administration', $sign_admin),
			array('Houston Permitting Center: Commercial advertising sign (on premises) permit', $hpc . '/hpwcode1112'),
			array('Houston Sign Code, effective July 29, 2020 (Secs. 4605, 4611)', $sign_code),
			array('Sign Administration Informational Letter 0015-2008: electrically listed signs', $hpc . '/media/5676/download?inline='),
		),
	),
	'districts' => array(
		array(
			'name' => 'Scenic districts and scenic streets',
			'rule' => 'Downtown, Gateway/Midtown, Heights Boulevard, Lindale Park, Tanglewood/Post Oak and F.M. 1093, plus scenic streets such as Allen Parkway and Memorial Drive, have tighter limits, including indirect-only lighting for lit signs (Sec. 4611(c)). Ask Sign Administration how that applies to LED letters before you order.',
			'source' => $sign_admin,
		),
		array(
			'name' => 'Historic districts and landmarks',
			'rule' => 'In a historic district such as the Heights, a sign on a landmark or contributing building first needs a Certificate of Appropriateness from the Houston Archaeological and Historical Commission (monthly meetings; apply 22 days ahead). Heights guidelines allow neon and awning lettering.',
			'source' => 'https://www.houstontx.gov/planning/HistoricPres/certofapp.html',
		),
	),
	'temporary_signs' => array(
		'heading' => 'Grand Opening Banners in Houston',
		'body' => array(
			'Besides your permanent signs, Houston permits one limited-use banner at a time: up to 40 square feet, fastened flat against an exterior wall with no rope or wire, for up to seven consecutive days in any 30-day period. Each display needs its own permit, and the banner doesn&rsquo;t count toward the five-sign limit. Owners may apply themselves for a banner hung no higher than 16 feet.',
			'Waiting for your letters? The licensed contractor holding their permits may put up lightweight provisional signs until they&rsquo;re installed. Nothing may go on a public sidewalk without City Council consent.',
		),
		'sources' => array(
			array('City of Houston: Limited use banner application and guidelines', $hpc . '/media/5611/download?inline='),
			array('Houston Sign Code Secs. 4605(b)(12), 4608(c) and 4608(p)', $sign_code),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Houston',
		'body' => array(
			'Houston exempts signs painted on window and door glass from permits, but advertising may cover no more than 20 percent of a glass storefront, and a lit or framed display over 6 square feet that is visible from the street is permitted as a wall sign. Ask Sign Administration how that applies to vinyl before planning a large window graphic.',
		),
		'sources' => array(
			array('Houston Sign Code Sec. 4605(b)(1)', $sign_code),
		),
	),
	'climate' => array(
		'Houston&rsquo;s hurricane season runs from June through November, and every sign must be designed for the building code&rsquo;s wind loads. On masonry, concrete or stone, wall signs need metal anchors at least 3/8 inch in diameter set 5 inches deep, never on an unbraced parapet.',
	),
	'climate_sources' => array(
		array('Houston Sign Code Sec. 4609(a) and (j): wind loads and wall sign anchorage', $sign_code),
		array('NOAA National Hurricane Center: Atlantic hurricane season', 'https://www.nhc.noaa.gov/climo/'),
	),
	'popular' => array(
		'heading' => 'Popular Choices for Houston Storefronts',
		'lead' => '',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright face-lit letters that read from a major thoroughfare.',
			'hidden-back-halo-lit' => 'A soft halo glow on brick, stucco and stone.',
			'adhesive-window-perf' => 'See-through window film for hours and offers, within the 20 percent limit.',
			'13oz-vinyl-banner' => 'Grand opening banners up to 40 sq ft for a limited-use permit.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Do I need a permit for channel letters in Houston?',
			'a' => 'Yes. Every lit business sign needs a Sign Administration permit before installation, filed online by a licensed sign contractor. Letters more than 8 feet above the ground or over 60 square feet also need a drawing certified by a Texas-registered engineer. The City lists processing at 4 to 11 business days.',
		),
		array(
			'q' => 'Can I hang a grand opening banner in Houston without a permit?',
			'a' => 'No. You need a limited-use banner permit: one banner of up to 40 square feet, flat against an exterior wall, for up to seven consecutive days in any 30-day period, with a new permit for each display. It doesn&rsquo;t count toward your five permanent signs.',
		),
		array(
			'q' => 'How much of a Houston storefront window can graphics cover?',
			'a' => 'Advertising may cover no more than 20 percent of a glass storefront. A lit or framed window display over 6 square feet that is visible from the street needs a permit as a wall sign. Ask Sign Administration how it treats applied vinyl.',
		),
		array(
			'q' => 'Who installs my sign in Houston?',
			'a' => 'A Houston sign contractor you hire: we build, test and ship, and never install. Look for a City of Houston sign contractor license and, for lit signs, a TDLR electrical license; Sign Administration publishes the list of licensed contractors.',
		),
		array(
			'q' => 'How long does shipping to Houston take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with faster options at checkout. Houston lists permit processing at 4 to 11 business days, so have your contractor file the permit while your letters are being built.',
		),
	),
	'review_notes' => array(
		'houstonpermittingcenter.org blocks the research PC; all Houston Permitting Center facts were read from Internet Archive copies (2024-12 to 2026-06). Open each source link once from another network before going live.',
		'Processing time (4-11 business days) re-checked 2026-10-27 on the City permit page (Internet Archive copy of 2026-06-10). The $25,000 bond is from the 2020 Sign Code, Sec. 4606. Re-check both every year.',
		'The page states Houston&rsquo;s rule (shop inspection unless UL labels are on each letter and power supply); it makes no claim about our labels. Owner: confirm per-letter UL labels before saying so anywhere.',
		'Owner: confirm every order gets a proof to approve before production ("How ordering works", step 2, shared by all location pages).',
		'Transit days to Houston not shown until verified.',
	),
);
