<?php
/**
 * Nebraska state guide (/locations/nebraska/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$ne_act = 'https://electrical.nebraska.gov/sites/default/files/doc/STATE%20ACT%20REVISED%20VERSION%2006%2026%202026-2.pdf';
$ne_rules = 'https://electrical.nebraska.gov/sites/default/files/doc/STATE%20ELECTRICAL%20BOARD%20RULES%204-23-24.pdf';
$lnk_signs = 'https://www.lincoln.ne.gov/City/Departments/PDS/Building-Safety/Permit-Applications/Permanent-Mobile-or-Inflatable-Signs';

return array(
	'slug' => 'nebraska',
	'name' => 'Nebraska',
	'abbr' => 'NE',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Nebraska',
	'neighbors' => array('south-dakota', 'iowa', 'missouri', 'kansas', 'colorado', 'wyoming'),
	'seo_title' => 'Channel Letters Shipped to Nebraska | Sign Installer License',
	'seo_description' => 'Channel letters shipped to Nebraska: the special electrician sign installer license, local inspection programs and Lincoln sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Nebraska',
	'intro' => array(
		'Nebraska storefronts can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a Nebraska-licensed electrician or sign installer to hook up. Banners and window graphics are printed in Renton, WA.',
		'Nebraska licenses electrical work statewide through the State Electrical Board, and its law names sign installation as one of the classes a <strong>special electrician</strong> license can cover. The board&rsquo;s Sign Installer license reaches further than most sign credentials: it includes the branch circuit conductors as well as the sign apparatus, though nothing on the line side of the disconnect.',
		'Inspections depend on where you are. Omaha, Lincoln and a list of other cities and counties run their own board-approved programs; everywhere else, the installer or owner files a request for state inspection at or before the start of work.',
	),
	'facts' => array(
		'Licensing agency' => 'Nebraska State Electrical Board',
		'Sign license' => 'Special electrician, Sign Installer class',
		'Sign Installer scope' => 'Branch circuit conductors and sign apparatus; no line-side work',
		'Inspection' => 'Local program (Omaha, Lincoln and others) or the state',
	),
	'categories' => array(
		'channel-letters' => 'In Lincoln only sign companies licensed with the city can apply for an illuminated sign permit, so confirm your installer&rsquo;s city registration before you book the job.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Nebraska',
		'body' => array(
			'Under the State Electrical Act, no one may wire for another person unless the board has licensed them as an electrical contractor, a Class B electrical contractor, a Class A master electrician or a fire alarm installer, and journeymen work for a licensed contractor (Neb. Rev. Stat. 81-2108). Section 81-2112 adds <strong>special electrician</strong> licenses for limited classes of work, and the definition in 81-2102 lists sign installation alongside irrigation system wiring, well pump wiring and air conditioning and refrigeration.',
			'The board&rsquo;s Rule 1 sets the scope: the Sign Installer license is &ldquo;limited to branch circuit conductors and sign apparatus installation only,&rdquo; and special electricians &ldquo;cannot do any wiring on the line side of the disconnect.&rdquo; So the license covers the conductors feeding the sign and the sign itself, while anything on the line side of the disconnect stays with a licensed contractor. Each licensee needs experience acceptable to the board in the class, and renewal takes 12 hours of continuing education.',
			'The state code is the 2023 National Electrical Code, except that the 2017 edition still governs a handful of sections, including 210.8(A) and 230.85 (81-2104). Cities with their own programs adopt standards at least as strict.',
		),
		'table' => array(
			'Licensing agency' => 'Nebraska State Electrical Board',
			'Special electrician classes' => 'Include sign installation (81-2102)',
			'Sign Installer scope' => 'Branch circuit conductors and sign apparatus only',
			'Outside the scope' => 'Wiring on the line side of the disconnect',
			'Renewal' => '12 hours of continuing education',
		),
		'sources' => array(
			array('Neb. Rev. Stat. 81-2102: State Electrical Act definitions', 'https://nebraskalegislature.gov/laws/statutes.php?statute=81-2102'),
			array('Nebraska State Electrical Board: Board rules, April 2024 (PDF)', $ne_rules),
			array('Nebraska State Electrical Division: State Electrical Act, July 2026 (PDF)', $ne_act),
		),
	),
	'permits' => array(
		'heading' => 'Electrical Inspections and Sign Permits in Nebraska',
		'body' => array(
			'State inspection doesn&rsquo;t apply inside a county, city or village that adopts wiring standards at least equal to the board&rsquo;s and inspects with its own certified inspector (81-2125). The board&rsquo;s list of those programs includes Omaha, Lincoln, Bellevue, Papillion, Grand Island, Kearney, Fremont, Norfolk, Hastings and York, plus Douglas, Lancaster, Hall and Sarpy counties, and some small towns are covered by Omaha, Lincoln or their county.',
			'Everywhere else, the licensee or owner must send the board a request for inspection at or before the start of the installation, and a late request brings a $250 delinquent fee (81-2126). Ask your installer who files it before the letters go up.',
			'In <strong>Lincoln</strong>, the capital, sign permits are filed with Building and Safety through the city&rsquo;s Citizen Access portal. Only sign companies licensed with the City of Lincoln can apply for illuminated signs and most other signs; sign contractors pay a $50 annual registration and keep insurance and a bond on file. A building sign application includes an elevation showing the sign&rsquo;s height, width, projection and location on the building, plus the anchoring method for a projecting sign.',
			'<strong>Omaha</strong> runs its own electrical inspections, and state law extends its zoning authority into the extraterritorial zoning jurisdiction beyond the city limits (Neb. Rev. Stat. 14-418), so check with Omaha planning even when your address sits just outside the city.',
		),
		'table' => array(
			'State inspection' => 'Request filed at or before the start of work',
			'Late request' => '$250 delinquent fee',
			'Local programs' => 'Omaha, Lincoln, Bellevue, Papillion, Grand Island, Kearney and others',
			'Lincoln sign permits' => 'City-licensed sign companies apply for illuminated signs',
			'Lincoln registration' => '$50 a year, plus insurance and a bond',
		),
		'sources' => array(
			array('Nebraska State Electrical Division: Municipal and county electrical inspection programs', 'https://electrical.nebraska.gov/municipal-county-electrical-inspection-programs'),
			array('City of Lincoln: Permanent, mobile or inflatable signs', $lnk_signs),
			array('Neb. Rev. Stat. 14-418: Zoning in metropolitan-class cities', 'https://nebraskalegislature.gov/laws/statutes.php?statute=14-418'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Mobile and Inflatable Displays in Lincoln',
		'body' => array(
			'Lincoln&rsquo;s sign rules sit in two chapters of the municipal code, 27.69 for zoning and 22.05 for construction. Mobile signs are allowed only in the B, H and I zoning districts, for no more than 60 days in a calendar year, and must sit back at least half of the required front yard setback.',
			'Inflatables are limited to 10 days a year in the same districts, and every day one is up counts as six days against the mobile sign allowance, so a short inflatable promotion uses up the year quickly. A business that owns its mobile display and places it on its own property can file the permit itself.',
		),
		'sources' => array(
			array('City of Lincoln: Permanent, mobile or inflatable signs', $lnk_signs),
		),
	),
	'climate' => array(
		'Nebraska sits in one of the most hail-prone stretches of the country, and the open plains add blizzards, damaging straight-line winds and summer tornadoes to the mix. Temperatures can swing from well below zero in January to triple-digit heat in July. Ask your installer to seal every wall penetration, mount letters to solid backing rather than siding, and leave room behind raceways for snow and ice to drain.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Nebraska Storefronts',
		'lead' => 'Lit letters for Omaha and Lincoln centers and main streets from Kearney to Norfolk.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Face-lit letters for Omaha and Lincoln shopping centers.',
			'standard-channel-letter-back-lit' => 'A soft halo on brick downtown storefronts.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-80.',
			'18oz-blockout-banner' => 'Heavy banners for wind on the open plains.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Nebraska?',
			'a' => 'We ship custom channel letters anywhere in Nebraska, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Nebraska-licensed electrical contractor or special electrician with the Sign Installer class to mount and connect them.',
		),
		array(
			'q' => 'Does Nebraska have a sign installer license?',
			'a' => 'Yes. The State Electrical Board issues special electrician licenses for limited classes of work, and Sign Installer is one of them. It is limited to branch circuit conductors and sign apparatus installation, with no wiring on the line side of the disconnect. Applicants need experience acceptable to the board, and renewal takes 12 hours of continuing education.',
		),
		array(
			'q' => 'Who inspects the wiring for my sign in Nebraska?',
			'a' => 'Your city or county if it runs a board-approved program, as Omaha, Lincoln, Bellevue, Grand Island, Kearney and Douglas, Lancaster, Hall and Sarpy counties do. Elsewhere the State Electrical Division inspects, and the licensee or owner must request the inspection at or before the start of work or pay a $250 delinquent fee.',
		),
		array(
			'q' => 'Who can apply for a sign permit in Lincoln?',
			'a' => 'For illuminated signs and most others, only a sign company licensed with the City of Lincoln. Sign contractors pay a $50 annual registration and keep insurance and a bond on file. The application, filed through Citizen Access, includes an elevation with the sign&rsquo;s dimensions, projection and location on the building.',
		),
		array(
			'q' => 'How long does shipping to Nebraska take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'State Electrical Act PDF (July 2026), board rules (April 2024) and the inspection program list read directly 2026-11-20; nebraskalegislature.gov times out here, so 81-2102, 81-2104 and 14-418 were read from Internet Archive copies (2025-03-02, 2026-07-12, 2026-01-14).',
		'The Division&rsquo;s home page mentions changes to the Electrical Act adopted July 18, 2026; not read. Confirm they don&rsquo;t change the Sign Installer scope.',
		'Lincoln sign page read from the archive copy of 2026-02-07 (403 directly); the $50 registration: re-check yearly. Lincoln window and banner limits in Chapter 27.69 not verified.',
		'Omaha sign code (Chapter 55) not verified: Municode and planning.omaha.gov block the research PC. Whether Omaha or Lincoln also license electricians locally was not confirmed.',
		'Transit days to Nebraska not shown until verified.',
	),
);
