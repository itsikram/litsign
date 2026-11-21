<?php
/**
 * New Mexico state guide (/locations/new-mexico/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$nm_class = 'https://www.srca.nm.gov/parts/title14/14.006.0006.html';
$abq_ido = 'https://documents.cabq.gov/planning/IDO/2025_IDO_Update/IDO_2026_Effective-2026-05-06.pdf';
$abq_signs = 'https://www.cabq.gov/planning/code-enforcement-zoning/general-sign-regulations';

return array(
	'slug' => 'new-mexico',
	'name' => 'New Mexico',
	'abbr' => 'NM',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/New_Mexico',
	'neighbors' => array('arizona', 'colorado', 'oklahoma', 'texas'),
	'seo_title' => 'Channel Letters Shipped to New Mexico | ES-1 Sign License',
	'seo_description' => 'Channel letters shipped to New Mexico: the ES-1 sign license, EE-98 contractors, Albuquerque sign permits and Santa Fe historic rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to New Mexico',
	'intro' => array(
		'New Mexico businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a contractor licensed by the Construction Industries Division (CID). Banners and window graphics are printed in Renton, WA.',
		'New Mexico has a contractor classification written for this work. <strong>ES-1, electrical signs and outline lighting</strong>, lets a sign company install lit signs at commercial locations once a sign circuit has been brought within 10 feet of the supporting wall or roof; the circuit inside the building is work for an <strong>EE-98</strong> electrical contractor.',
		'Albuquerque issues its own trade permits, requires a sign permit for every illuminated sign, and limits window signs to 15 percent of window and door area. In Santa Fe&rsquo;s historic districts, every sign is reviewed before installation and sandwich boards are prohibited.',
	),
	'facts' => array(
		'Licensing agency' => 'Construction Industries Division (CID)',
		'Sign license' => 'ES-1 electrical signs and outline lighting',
		'Building wiring' => 'EE-98 electrical contractor',
		'Albuquerque window signs' => 'Up to 15% of window and door area',
	),
	'categories' => array(
		'channel-letters' => 'Albuquerque requires a sign permit for every illuminated sign, and only an ES-1 or EE-98 license holder may obtain one for an electrical sign.',
		'window-graphics' => 'Albuquerque caps window signs at 15 percent of window and door area, and at 25 percent of the ground-floor band between 4 and 8 feet up.',
		'banners' => 'Albuquerque temporary signs need no permit but are limited to four per premises, 16 square feet in total, no lighting and 15 calendar days.',
		'flags-aframes' => 'Albuquerque allows one A-frame per food, beverage, entertainment or retail business, up to 6 square feet a side and 3 feet tall, with a sign permit.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in New Mexico',
		'body' => array(
			'CID&rsquo;s license classifications are in 14.6.6 NMAC. <strong>ES-1</strong> requires two years of experience and is limited to commercial locations; it covers completing a sign or outline lighting installation &ldquo;where a sign circuit&rdquo; has been provided within 10 feet of the roof or wall supporting the sign, along with the sign&rsquo;s foundations and structure. <strong>EE-98</strong> covers residential, commercial and industrial wiring at 5,000 volts or less, includes the ES-1 scope, and takes four years of experience.',
			'The rule draws a firm line at the building. All raceway installed in or on a building must be done by an EE-98 contractor, and specialty license holders, ES-1 included, may not install it, with one exception: ES-1 licensees and journeymen may run the raceway needed to connect to the sign circuit, up to 10 feet. So an EE-98 electrician brings the circuit to the wall and an ES-1 sign company takes it from there.',
			'Every individual doing electrical wiring needs a journeyman certificate in the matching classification, working only for a licensed contractor. An <strong>ES-1J</strong> sign journeyman (two years) can work under an ES-1 or an EE-98 license. A <strong>GS-23</strong> sign construction license covers non-electrical signs only, and the rule states that electrified signs may only be installed by ES-1 or EE-98 contractors.',
		),
		'table' => array(
			'Licensing agency' => 'Construction Industries Division',
			'ES-1' => 'Electrical signs and outline lighting; commercial only; two years',
			'EE-98' => 'All wiring 5,000 V or less, including the ES-1 scope; four years',
			'Raceway in or on the building' => 'EE-98, except up to 10 ft to reach the sign circuit',
			'Sign journeyman' => 'ES-1J, under an ES-1 or EE-98 contractor',
		),
		'sources' => array(
			array('14.6.6 NMAC: Construction industries licensing classifications', $nm_class),
			array('NM Regulation and Licensing Department: Construction Industries Division', 'https://www.rld.nm.gov/construction/'),
		),
	),
	'permits' => array(
		'heading' => 'Permits in Albuquerque and Across New Mexico',
		'body' => array(
			'Under CID&rsquo;s permit rule (14.5.2 NMAC), a permit expires if the authorized work doesn&rsquo;t start within 180 days of issuance. Some cities run their own building and trade permits: <strong>Albuquerque&rsquo;s</strong> Building Safety Division issues building and trade permits and performs inspections, and the 2020 New Mexico Electrical Code took effect there on September 28, 2023.',
			'Albuquerque&rsquo;s Integrated Development Ordinance requires a sign permit before any illuminated sign goes up, as well as for signs over 24 square feet or 8 feet tall. Only certain licenses can obtain one: GB-98 and GS-23 contractors may erect a sign&rsquo;s structure and foundation but may not connect electrical signs, while ES-1 and EE-98 holders may erect electrical signs. A wall sign application includes three copies of the elevation, the sign design and construction, and the method of fastening.',
			'Wall sign area is a percentage of the facade, doors and windows included: 5 percent in MX-T, 10 percent in MX-L, 15 percent in MX-M, MX-H and NR-C, and 25 percent in the NR-LM and NR-GM industrial districts.',
		),
		'table' => array(
			'Permit life (CID rule)' => 'Void if work doesn&rsquo;t start within 180 days',
			'Albuquerque trade permits' => 'Building Safety Division',
			'Albuquerque sign permit' => 'Required for every illuminated sign',
			'Who may apply (lit signs)' => 'ES-1 or EE-98 license holder',
			'Wall sign area' => '5% to 25% of the facade, by zone',
		),
		'sources' => array(
			array('14.5.2 NMAC: Permits', 'https://www.srca.nm.gov/parts/title14/14.005.0002.html'),
			array('City of Albuquerque: Building Safety Division', 'https://www.cabq.gov/planning/building-safety-division'),
			array('City of Albuquerque: Sign regulations', $abq_signs),
			array('Albuquerque Integrated Development Ordinance, effective May 2026 (PDF)', $abq_ido),
		),
	),
	'districts' => array(
		array(
			'name' => 'Santa Fe historic districts (Historic Districts Handbook, 2021)',
			'rule' => 'All signage in a historic district must be reviewed and permitted before installation, sandwich boards are prohibited, and further limits apply to the number, location, illumination, color and size of signs, awnings and banners. Under a 2009 ordinance, sign lighting citywide must be indirect or shine through translucent diffusing material with no exposed conduit, and neon and electronic message signs are allowed only outside the historic districts.',
			'source' => 'https://santafenm.gov/media/files/land_use/1b_-_Historic_Districts_Handbook_2021_%281%29.pdf',
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs and A-Frames in Albuquerque',
		'body' => array(
			'Temporary signs, displays installed for a set period in a way that is easy to remove, can go up in Albuquerque without a sign permit. Each premises may have up to four, totaling no more than 16 square feet, none of them lit, and each can stay up no longer than 15 calendar days.',
			'A-frames are portable signs, so they need a sign permit, and they&rsquo;re allowed only for food and beverage, indoor entertainment or retail uses: one per establishment, up to 6 square feet per side, 2.5 feet wide and 3 feet tall, in front of your own premises and not within 2 feet of the curb. Leave a clear sidewalk path, take the A-frame inside after business hours, and skip it entirely on the Coors Boulevard and Alameda Boulevard corridors where portable signs are banned.',
		),
		'sources' => array(
			array('Albuquerque IDO 14-16-5-12(F) and (I), Tables 5-12-4 and 5-12-7 (PDF)', $abq_ido),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Albuquerque',
		'body' => array(
			'No more than <strong>15 percent of window and door areas</strong> may be covered with signs. On the ground floor, the band between 4 and 8 feet above the ground has its own limit of 25 percent, which is the zone shoppers see at eye level. Plan hours, logos and promotions to fit both numbers, and keep large perforated window graphics within the overall 15 percent.',
		),
		'sources' => array(
			array('Albuquerque IDO Table 5-12-3: Wall and window signs (PDF)', $abq_ido),
		),
	),
	'climate' => array(
		'New Mexico sits high: Albuquerque is around a mile up and Santa Fe higher still, so UV exposure is intense, spring brings strong wind and blowing dust, and the July-to-September monsoon adds sudden thunderstorms. Daily temperature swings are wide. Choose UV-stable faces, ask your installer to use anchors suited to stucco or adobe-style walls, and keep drain holes clear of dust.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for New Mexico Storefronts',
		'lead' => 'Lit letters for Albuquerque and Las Cruces centers, halo glows for stucco facades, and window graphics within city limits.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Albuquerque and Las Cruces retail.',
			'hidden-back-halo-lit' => 'A halo glow on stucco and adobe-style walls.',
			'adhesive-window-perf' => 'Window graphics within Albuquerque&rsquo;s 15 percent limit.',
			'13oz-vinyl-banner' => 'Banners sized for 15-day temporary displays.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in New Mexico?',
			'a' => 'We ship custom channel letters anywhere in New Mexico, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a CID-licensed ES-1 sign contractor or EE-98 electrical contractor to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign installer need in New Mexico?',
			'a' => 'An ES-1 electrical signs and outline lighting classification from CID, which takes two years of experience and covers commercial locations only, or an EE-98 electrical license, which includes the ES-1 scope. The installers on site need matching journeyman certificates, such as ES-1J. A GS-23 sign construction license covers non-electrical signs only.',
		),
		array(
			'q' => 'Who runs the circuit to my sign in New Mexico?',
			'a' => 'An EE-98 electrical contractor. ES-1 work assumes a sign circuit has already been provided within 10 feet of the wall or roof supporting the sign, and all raceway in or on the building is EE-98 work, except up to 10 feet that an ES-1 licensee may run to connect to that sign circuit.',
		),
		array(
			'q' => 'How much of an Albuquerque window can graphics cover?',
			'a' => 'No more than 15 percent of window and door areas overall. On the ground floor, the band between 4 and 8 feet above the ground is limited to 25 percent. Temporary signs are separate: up to four per premises, 16 square feet in total, unlit, for no more than 15 calendar days.',
		),
		array(
			'q' => 'How long does shipping to New Mexico take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'14.6.6 NMAC and 14.5.2 NMAC (both 3/10/2022) read directly on srca.nm.gov 2026-11-20; closes the old note about the Albuquerque-hosted copy (that link was replaced by the official rule).',
		'Albuquerque IDO (effective May 2026) read from the identical file served by the city&rsquo;s IDO viewer; documents.cabq.gov timed out here, so confirm the cited link opens. Albuquerque sign permit fees and IDO permit expiration (6-5(C)) not read.',
		'Santa Fe: the 2021 Historic Districts Handbook and Ordinance 2009-29 read directly; later amendments to SFCC 14-8.10 not checked, and the current 14-8.10(H) text (window and lighting limits) not read. No claim that internal lighting is banned.',
		'Whether Las Cruces and Santa Fe run their own electrical permit programs was not confirmed.',
		'Transit days to New Mexico not shown until verified.',
	),
);
