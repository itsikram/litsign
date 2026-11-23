<?php
/**
 * Montana state guide (/locations/montana/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$mt_102 = 'https://mca.legmt.gov/bills/mca/title_0370/chapter_0680/part_0010/section_0020/0370-0680-0010-0020.html';
$mt_103 = 'https://mca.legmt.gov/bills/mca/title_0370/chapter_0680/part_0010/section_0030/0370-0680-0010-0030.html';
$bil_wall = 'https://www.billingsmt.gov/DocumentCenter/View/49851/Wall-Sign-Area-and-How-Max-Area-is-applied-June-2022';
$boz_signs = 'https://www.bozemanmt.gov/departments/community-development/development-center/planning-checklists-guides-and-brochures/-folder-2608';

return array(
	'slug' => 'montana',
	'name' => 'Montana',
	'abbr' => 'MT',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Montana',
	'neighbors' => array('idaho', 'wyoming', 'south-dakota', 'north-dakota'),
	'seo_title' => 'Channel Letters Shipped to Montana | Billings Sign Rules',
	'seo_description' => 'Channel letters shipped to Montana: state electrical permits, unlimited vs. limited contractors, and Billings and Bozeman sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Montana',
	'intro' => array(
		'Montana businesses can order custom LED channel letters online, built in Washington and California and shipped ready for a Montana-licensed electrical contractor. Banners and window graphics are printed in Renton, WA.',
		'Montana licenses master, journeyman and residential electricians through the State Electrical Board, and only an unlimited electrical contractor, one with a master electrician, can take on commercial work. There is no sign license or sign exemption: the 90-volt exemption covers low-voltage work, not the line-voltage feed that powers lit letters.',
		'Permits come from the state&rsquo;s Building Codes Program unless your city, county or town is certified to run its own. Billings and Bozeman both let window graphics cover a quarter of the glass before they start counting against your sign allowance.',
	),
	'facts' => array(
		'Licensing agency' => 'Montana State Electrical Board',
		'Commercial work' => 'Unlimited electrical contractor (master electrician)',
		'Electrical permits' => 'State Building Codes Program, or a certified city or county',
		'Window signs' => 'Billings and Bozeman: 25% before they count',
	),
	'categories' => array(
		'channel-letters' => 'In Billings the wall sign maximum covers the whole building frontage, not each tenant, so check how much wall sign area your neighbors already use before sizing your letters.',
		'window-graphics' => 'Bozeman exempts window signs from permits up to 25 percent of each window, and Billings counts only the part above 25 percent toward the wall sign total.',
		'banners' => 'Bozeman issues $10 temporary sign permits: 30 days a year for sales and events, 60 days once for a grand opening and 180 days a year for seasonal displays.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Montana',
		'body' => array(
			'Montana law (MCA 37-68-102) defines three electrician licenses: master, journeyman and residential electrician, the last limited to residential buildings with fewer than five living units. Journeyman-level experience means 8,000 hours of practical work, or recognition as a journeyman by another state or legally authorized jurisdiction.',
			'Contractors come in two tiers. An <strong>unlimited electrical contractor</strong> must have a master electrician and may do residential and commercial work; a <strong>limited electrical contractor</strong> needs a journeyman and is restricted to residential buildings with fewer than five units. A storefront is commercial work, so it goes to an unlimited contractor, which also names a full-time Montana journeyman or master as its responsible electrician.',
			'The exemptions in MCA 37-68-103 include work at 90 volts or less and plugging an appliance into an outlet that is already installed, but nothing for signs. LED channel letters run on low-voltage power supplies, yet the line-voltage circuit feeding those supplies is well above 90 volts, so a licensed electrician still makes that connection.',
		),
		'table' => array(
			'Licensing agency' => 'Montana State Electrical Board',
			'Unlimited contractor' => 'Master electrician; residential and commercial work',
			'Limited contractor' => 'Journeyman; residential under five units only',
			'Journeyman-level experience' => '8,000 hours',
			'Exemption' => '90 volts or less, not the sign&rsquo;s supply circuit',
		),
		'sources' => array(
			array('MCA 37-68-102: Electricians, definitions', $mt_102),
			array('MCA 37-68-103: Exemptions', $mt_103),
			array('Montana State Electrical Board: Electrical contractor application (PDF)', 'https://boards.bsd.dli.mt.gov/_docs/ele/ec-app.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Electrical and Sign Permits in Montana',
		'body' => array(
			'State electrical permits are required on all electrical work in Montana except in cities, counties and towns certified to issue their own permits and conduct inspections. The installer or owner applies to the Building Codes Bureau before starting, permits for anything but a homeowner&rsquo;s own work are pulled by Montana-licensed electrical contractors, and the permit holder calls for rough-in and final inspections.',
			'In <strong>Billings</strong>, the largest city, the City/County Planning Division&rsquo;s Zoning Coordinator has issued an official interpretation of the wall sign rules. In most commercial districts the maximum wall sign area is 20 percent of the wall or 250 square feet, and it applies to the entire building frontage facing a street, not tenant by tenant. Applications for new wall signs must show how much wall sign area already exists on that frontage and whether a sign cabinet is being removed or replaced; without that, the application is incomplete.',
			'Older buildings get squeezed: where existing wall signs exceed the maximum by more than 20 percent, no wall signs may be replaced or added, only their faces swapped, while buildings over by 20 percent or less may replace signs like for like in area. Projecting, awning and other attached signs don&rsquo;t count toward the wall total.',
			'<strong>Bozeman</strong> requires a building permit, filed through ProjectDox, for new signs and for every revision to an existing one, including a change in copy.',
		),
		'table' => array(
			'Electrical permit' => 'State Building Codes Bureau, unless the city or county is certified',
			'Inspections' => 'Rough-in and final',
			'Billings wall signs' => '20% of the wall or 250 sq ft in most commercial districts',
			'Billings wall total' => 'Shared by the whole building frontage',
			'Bozeman' => 'Permit for new signs and any copy change',
		),
		'sources' => array(
			array('Montana DLI Building Codes Program: Electrical permits', 'https://bsd.dli.mt.gov/building-codes-permits/permit-applications/electrical-permits/index.html'),
			array('City of Billings: Wall sign area code interpretation, June 2022', $bil_wall),
			array('City of Bozeman: Planning checklists and guides (signs)', $boz_signs),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs and Banners in Bozeman',
		'body' => array(
			'Bozeman issues temporary sign permits for $10, and the time allowed depends on the purpose: sales and special events get up to 30 days per calendar year, a new business grand opening up to 60 days once in the life of the business, and seasonal displays up to 180 days per calendar year. Temporary freestanding signs can&rsquo;t be lit.',
			'The grand-opening allowance comes only once, so plan the banner, flags and window graphics for that launch together and keep the 30 sales days for later promotions.',
		),
		'sources' => array(
			array('City of Bozeman: Planning checklists and guides (signs)', $boz_signs),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Billings and Bozeman',
		'body' => array(
			'In <strong>Bozeman</strong>, window signs painted on the glass or attached to the inside of a window don&rsquo;t need a permit as long as they cover no more than 25 percent of that window; past that point they count as a wall sign. <strong>Billings</strong> draws the same line differently: a window sign larger than 25 percent of an architecturally distinct window counts toward the wall sign maximum, but only for the area above 25 percent. In both cities a quarter of each window is free space for graphics.',
		),
		'sources' => array(
			array('City of Bozeman: Planning checklists and guides (signs)', $boz_signs),
			array('City of Billings: Wall sign area code interpretation, June 2022', $bil_wall),
		),
	),
	'climate' => array(
		'Montana weather changes fast. East of the Rockies, chinook winds can swing temperatures dramatically within hours and push strong gusts through Billings and Great Falls, while the western valleys collect heavy snow and summer wildfire smoke. Choose letters with sealed seams and clear drain holes, and have your installer leave room in the mounting for expansion so quick temperature swings don&rsquo;t loosen fasteners.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Montana Storefronts',
		'lead' => 'Lit letters for Billings, Missoula and Bozeman storefronts, plus window graphics inside the 25 percent allowance.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Billings and Missoula retail strips.',
			'hidden-back-halo-lit' => 'A halo glow for historic Main Street brick.',
			'adhesive-window-perf' => 'Window graphics sized within the 25 percent allowance.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-90 and I-15.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Montana?',
			'a' => 'We ship custom channel letters anywhere in Montana, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Montana-licensed unlimited electrical contractor, along with any sign installer your city requires, to mount and connect them.',
		),
		array(
			'q' => 'Does Montana have a sign electrician license?',
			'a' => 'No. Montana licenses master, journeyman and residential electricians, and the exemptions in MCA 37-68-103 don&rsquo;t include signs. Work at 90 volts or less is exempt, but the line-voltage circuit that powers lit letters isn&rsquo;t, so a licensed electrician working for an unlimited electrical contractor makes the connection on a storefront.',
		),
		array(
			'q' => 'Who issues electrical permits in Montana?',
			'a' => 'The state Building Codes Program, except in cities, counties and towns certified to issue their own permits and inspect. The installer or owner applies before starting work, permits for commercial jobs are pulled by Montana-licensed electrical contractors, and the permit holder calls for rough-in and final inspections.',
		),
		array(
			'q' => 'How big can wall signs be in Billings?',
			'a' => 'In most commercial districts, wall signs on a building frontage can total 20 percent of the wall area or 250 square feet. That maximum covers the entire frontage, not each tenant, so new applications must list the wall sign area already there. Window signs count toward it only for the part above 25 percent of the window.',
		),
		array(
			'q' => 'How much of a Bozeman window can graphics cover?',
			'a' => 'Up to 25 percent of each window without a permit, whether the graphics are painted on or attached to the inside of the glass. Window signs over that count as wall signs. Billings uses the same 25 percent line but counts only the area above it toward the wall sign maximum.',
		),
		array(
			'q' => 'How long does shipping to Montana take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'MCA 37-68 (2025), the Building Codes Program electrical permit page and the contractor application (revised 07/22/2024) read directly 2026-11-20; closes the old contractor-tier note.',
		'The Billings sign permit information page (billingsmt.gov/2503) now returns 404 (last archived 2026-01-06), so the facts only it carried (Citizen Access filing, separate Building Division electrical permit, private-property rule) were removed; the June 2022 wall sign code interpretation (read directly) is used. Billings temporary and banner rules (BMCC 27-1408, Municode only) not verified.',
		'Bozeman planning page read from the archive copy of 2026-04-28 (403 directly); the $10 temporary sign fee: re-check yearly. The city&rsquo;s summary lists &ldquo;LED signs&rdquo; among prohibited signs; the code text (BMC 38.560.030) wasn&rsquo;t read, so it isn&rsquo;t shown. Check what it covers before going live.',
		'Helena sign code not verified (amlegal and helenamt.gov block the research PC).',
		'Transit days to Montana not shown until verified.',
	),
);
