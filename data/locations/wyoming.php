<?php
/**
 * Wyoming state guide (/locations/wyoming/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$wy_t35 = 'https://wyoleg.gov/statutes/compress/title35.pdf';
$wy_lic = 'https://wsfm.wyo.gov/electrical-safety/licensing';
$cas_signs = 'https://www.casperwy.gov/government/departments/community_development/code_enforcement/signage';

return array(
	'slug' => 'wyoming',
	'name' => 'Wyoming',
	'abbr' => 'WY',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Wyoming',
	'neighbors' => array('montana', 'south-dakota', 'nebraska', 'colorado', 'utah', 'idaho'),
	'seo_title' => 'Channel Letters Shipped to Wyoming | LM-S Sign License',
	'seo_description' => 'Channel letters shipped to Wyoming: the LM-S sign technician license, state wiring permits, WYDOT on-premise rules and Casper banners. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Wyoming',
	'intro' => array(
		'Wyoming businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a Wyoming-licensed sign technician or electrician. Banners and window graphics are printed in Renton, WA.',
		'Wyoming is one of the states with a license made for sign work. Alongside master and journeyman electricians, the Department of Fire Prevention and Electrical Safety licenses <strong>LM-S sign technicians</strong>, who work for a limited electrical contractor and handle wiring on the load side of the sign&rsquo;s equipment disconnect.',
		'Before most new wiring goes in, someone pulls a $50 state wiring permit online, unless the city or county has its own electrical enforcement. Along interstate and primary highways, on-premise signs advertising the business on the property are allowed and don&rsquo;t need a WYDOT permit.',
	),
	'facts' => array(
		'Licensing agency' => 'Department of Fire Prevention and Electrical Safety',
		'Sign license' => 'LM-S sign technician, load side of the disconnect',
		'Wiring permit' => '$50, online, unless the city or county enforces',
		'Highway on-premise signs' => 'Allowed; no WYDOT permit',
	),
	'categories' => array(
		'channel-letters' => 'Casper requires a sign permit for any commercial sign, and freestanding or monument structures need stamped engineered drawings for wind and snow loads.',
		'banners' => 'In Casper, banners go flush on the building front (front and one side on a corner lot) or on a permanent sign structure, and their area counts against the total sign allowance.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Wyoming',
		'body' => array(
			'W.S. 35-9-123 says licensed electrical contractors employing licensed master or journeyman electricians, or registered apprentices supervised by them, shall install all electrical equipment. The statute then allows limited licenses, and one of them is for signs: the installation, repair or remodel of sign systems, &ldquo;limited to wiring on the load side of the equipment disconnect.&rdquo;',
			'The Department of Fire Prevention and Electrical Safety issues that as the <strong>LM-S</strong> license, covering electrical work for electric signs on the load side of the equipment disconnect, after 4,000 hours of experience. LM-S technicians work for a licensed limited electrical contractor, and the license runs for three years. The disconnect and the circuit that feeds it stay with an electrical contractor&rsquo;s master or journeyman.',
			'For comparison, the journeyman exam requires four years (8,000 hours) of experience plus 576 classroom hours, and a master needs eight years (16,000 hours), four of them as a licensed journeyman. Contractor licenses, limited ones included, are renewed each year by July 1, and each contractor needs a full-time master electrician or technician of record.',
		),
		'table' => array(
			'Licensing agency' => 'Department of Fire Prevention and Electrical Safety',
			'Sign technician (LM-S)' => 'Sign wiring on the load side of the disconnect; 4,000 hours',
			'Employer' => 'Licensed limited electrical contractor',
			'Journeyman' => '8,000 hours plus 576 classroom hours',
			'Master' => '16,000 hours, 8,000 of them as a journeyman',
		),
		'sources' => array(
			array('Wyoming Statutes Title 35, chapter 9: Fire prevention and electrical safety (PDF)', $wy_t35),
			array('Wyoming Department of Fire Prevention and Electrical Safety: Licensing', $wy_lic),
		),
	),
	'permits' => array(
		'heading' => 'Wiring Permits and Sign Permits in Wyoming',
		'body' => array(
			'No one may install electrical equipment in new construction, or in a remodel that requires the utility to disconnect and restore power, without an electrical wiring permit (W.S. 35-9-120). The state issues permits only through its online portal, at $50 each. The rule doesn&rsquo;t apply in cities and counties the state fire marshal has delegated local enforcement to under W.S. 35-9-121, whose inspectors must be certified and hold a Wyoming journeyman or master license. The state&rsquo;s adopted codes include the 2026 National Electrical Code.',
			'In <strong>Cheyenne</strong>, the capital and largest city, both new signs and remodels of existing signs require a building permit, and the sign rules are in Section 6.5 of the Unified Development Code. Cheyenne also licenses electrical contractors itself, and every city electrical application must come with a state electrical license first.',
			'<strong>Casper</strong> requires a sign permit for any commercial sign except small on-site directional signs. Changing the copy on a painted or printed sign needs no permit unless the structure changes, and any freestanding or monument structure needs a stamped engineered drawing showing wind and snow loads. The sign code is Chapter 17.96 of the municipal code.',
			'Along interstate and primary highways, Wyoming law bans most outdoor advertising, but signs advertising activities conducted on the property where they stand are allowed and exempt from WYDOT&rsquo;s permit and standards (W.S. 24-10-104). Only off-premise advertising needs a WYDOT permit.',
		),
		'table' => array(
			'State wiring permit' => '$50, online only, before the work',
			'Local enforcement' => 'Cities and counties delegated under W.S. 35-9-121',
			'Cheyenne' => 'Building permit for new or remodeled signs; UDC 6.5',
			'Casper' => 'Sign permit for any commercial sign',
			'Highway frontage' => 'On-premise signs exempt from WYDOT permits',
		),
		'sources' => array(
			array('Wyoming DFPES: Wiring permits', 'https://wsfm.wyo.gov/electrical-safety/wiring-permits-1'),
			array('City of Cheyenne: Building, planning and development FAQ', 'https://www.cheyennecity.org/Home/Tabs/Featured-Links/FAQ-Building-Planning-Development-and-Engineering'),
			array('City of Cheyenne: Contractor license applications', 'https://www.cheyennecity.org/Your-Government/Departments/Compliance-Division/Building-Permitting-Licensing/Contractor-License-Applications'),
			array('City of Casper: Code enforcement, signage', $cas_signs),
			array('Wyoming Statutes Title 24: Highways, including 24-10-104 (PDF)', 'https://wyoleg.gov/statutes/compress/title24.pdf'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs and Banners in Casper',
		'body' => array(
			'Casper allows temporary signs only in commercial and industrial zoning districts. Banners go on the front of the building, or the front and one side on a corner lot, mounted flush to the wall or on a permanently installed sign structure, and the banner&rsquo;s area counts as part of the maximum total signage allowed. If your permanent letters already use most of the allowance, size the banner to what&rsquo;s left.',
			'A business may also use two temporary freestanding signs made of rigid material, up to 12 square feet per side, and one of them must be moved every day. Neither may be mounted on delineator or T-posts.',
		),
		'sources' => array(
			array('City of Casper: Code enforcement, signage', $cas_signs),
		),
	),
	'climate' => array(
		'Wyoming is one of the windiest states, and the Cheyenne and Laramie corridor sees sustained wind and gusts that punish anything loosely mounted, along with ground blizzards and sharp temperature swings at high elevation. Strong sun at altitude fades cheap faces fast. Ask your installer to use every mounting point, seal the raceway against blowing snow, and choose UV-stable acrylic faces.',
	),
	'popular' => array(
		'heading' => 'Popular Picks for Wyoming Storefronts',
		'lead' => 'Lit letters for Cheyenne, Casper and Gillette storefronts, plus banners built for wind.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Cheyenne and Casper retail.',
			'standard-channel-letter-back-lit' => 'A softer evening glow for downtown storefronts.',
			'18oz-blockout-banner' => 'Heavy banners for flush mounting, as Casper requires.',
			'feather-angled-flag-pole' => 'Flags for frontage along I-25 and I-80.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Wyoming?',
			'a' => 'We ship custom channel letters anywhere in Wyoming, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Wyoming-licensed electrical contractor or a limited contractor with an LM-S sign technician to mount and connect them.',
		),
		array(
			'q' => 'Does Wyoming have a sign electrician license?',
			'a' => 'Yes. Wyoming licenses LM-S sign technicians for electrical work on electric signs, limited to the load side of the equipment disconnect, after 4,000 hours of experience. They work for a licensed limited electrical contractor. The disconnect and the circuit supplying it are work for a master or journeyman employed by an electrical contractor.',
		),
		array(
			'q' => 'Do I need a state wiring permit for my sign in Wyoming?',
			'a' => 'If the work is new construction or a remodel that requires the utility to disconnect and restore power, yes, unless your city or county has local electrical enforcement. State permits are issued only through the online portal and cost $50. Ask your installer whether your address falls under state or local enforcement.',
		),
		array(
			'q' => 'Does WYDOT regulate my storefront sign?',
			'a' => 'Not if it advertises the business on the property. Wyoming law allows signs advertising activities conducted on the property along interstate and primary highways and exempts them from WYDOT&rsquo;s permit and standards. Off-premise advertising needs a WYDOT permit. Your city&rsquo;s sign or building permit still applies.',
		),
		array(
			'q' => 'Where can banners go in Casper?',
			'a' => 'Only in commercial and industrial zoning districts, on the front of the building (or the front and one side on a corner lot), flush to the wall or on a permanent sign structure. The banner&rsquo;s area counts toward your maximum total signage, so it shares the allowance with your permanent letters.',
		),
		array(
			'q' => 'How long does shipping to Wyoming take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'W.S. title 35 (35-9-120 to 35-9-125) and title 24 (24-10-104, 24-10-107) read directly on wyoleg.gov 2026-11-20; DFPES licensing and wiring permit pages read directly. Replaces the old general WYDOT line.',
		'Cheyenne FAQ read from the Internet Archive copy of 2023-03-14 and the contractor license page from 2022-09-30 (cheyennecity.org returns 403 here): re-check both. Cheyenne window and banner limits in UDC 6.5 not verified (the archived UDC copies are truncated).',
		'No list of Wyoming cities and counties with delegated electrical enforcement was found.',
		'Casper signage page read from the archive copy of 2025-04-12 (403 directly). Fees ($50 wiring permit; $100 initial technician license, not shown): re-check yearly.',
		'Transit days to Wyoming not shown until verified.',
	),
);
