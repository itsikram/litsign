<?php
/**
 * Vermont state guide (/locations/vermont/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$vt_rules = 'https://firesafety.vermont.gov/sites/firesafety/files/Rules/2025%20Vermont%20Electrical%20Safety%20Rules.pdf';
$vt_893 = 'https://legislature.vermont.gov/statutes/section/26/015/00893';
$btv_art7 = 'https://www.burlingtonvt.gov/DocumentCenter/View/2490';

return array(
	'slug' => 'vermont',
	'name' => 'Vermont',
	'abbr' => 'VT',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Vermont',
	'neighbors' => array('new-hampshire', 'massachusetts', 'new-york'),
	'seo_title' => 'Channel Letters Shipped to Vermont | Type-S Sign License',
	'seo_description' => 'Channel letters shipped to Vermont: the type-S sign electrician license, state work notices, the billboard ban and Burlington rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Vermont',
	'intro' => array(
		'Vermont businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a Vermont-licensed electrician. Banners and window graphics are printed in Renton, WA.',
		'Vermont&rsquo;s sign credential is the <strong>type-S journeyman</strong> license in outdoor advertising. A type-S electrician works only in that specialty and may only install branch circuits from the existing electrical service, and only a master or type-S journeyman may file the state&rsquo;s Electrical Work Notice, which serves as the permit.',
		'Vermont has banned billboards for decades and limits on-premises signs by state law, though signs attached to your building fall outside its 150-square-foot cap. Burlington adds its own rules, from a 250-nit brightness limit after dark to window signs capped at 20 percent of any window.',
	),
	'facts' => array(
		'Licensing' => 'Electricians&rsquo; Licensing Board, Division of Fire Safety',
		'Sign license' => 'Type-S journeyman, outdoor advertising',
		'Electrical permit' => 'Validated Electrical Work Notice',
		'Billboards' => 'Banned statewide (10 V.S.A. &sect; 488)',
	),
	'categories' => array(
		'channel-letters' => 'Burlington limits every sign to 250 nits between dusk and dawn, and internally lit signs need an opaque background darker than the lettering.',
		'window-graphics' => 'Burlington window signs can cover 20 percent of any window between 4 and 7 feet up and 30 percent of all windows on a facade, unlit except for neon.',
		'banners' => 'A Burlington wall banner needs no permit: one per primary street frontage, up to 50 square feet, for 30 days at a time and two periods a year.',
		'flags-aframes' => 'Burlington allows one sandwich board per ground-floor tenant on private property, up to 8 square feet and 4 feet tall, with 5 feet of sidewalk kept clear.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Vermont',
		'body' => array(
			'The Electricians&rsquo; Licensing Board, within the Department of Public Safety&rsquo;s Division of Fire Safety, licenses master electricians, journeymen and <strong>type-S journeymen</strong> under 26 V.S.A. chapter 15. Type-S licenses cover a specialty field, and outdoor advertising is one of them (&sect; 904). Under the 2025 Vermont Electrical Safety Rules, effective November 5, 2025, a type-S electrician works only in connection with the specialty on the license and &ldquo;may only install branch circuits from the existing electrical service.&rdquo;',
			'That means a type-S sign electrician can run a new circuit for your letters from the panel you already have, but a new or upgraded service is master electrician work. Licenses aren&rsquo;t required for work in buildings with no more than two dwelling units (26 V.S.A. &sect; 910), but a storefront doesn&rsquo;t qualify for that exemption.',
		),
		'table' => array(
			'Licensing agency' => 'Electricians&rsquo; Licensing Board, Division of Fire Safety',
			'Sign license' => 'Type-S journeyman, outdoor advertising (&sect; 904)',
			'Type-S circuits' => 'Branch circuits from the existing service only',
			'Code edition' => 'NFPA 70, 2023, under the 2025 rules',
		),
		'sources' => array(
			array('26 V.S.A. chapter 15: Electricians', 'https://legislature.vermont.gov/statutes/chapter/26/015'),
			array('26 V.S.A. &sect; 904: Type-S journeyman electrician', 'https://legislature.vermont.gov/statutes/section/26/015/00904'),
			array('Vermont Electrical Safety Rules, effective November 5, 2025 (PDF)', $vt_rules),
		),
	),
	'permits' => array(
		'heading' => 'Work Notices, Sign Law and Burlington Permits',
		'body' => array(
			'State electrical rules apply to complex structures, which include commercial buildings, and work can&rsquo;t begin until the Division of Fire Safety validates an <strong>Electrical Work Notice</strong> with its fee. Only a master or type-S journeyman may file it, and the validated notice is the electrical permit. The base fee is $40, plus item fees that include $5 per sign. Wiring may not be covered until a state inspector has seen it, and a notice expires if work doesn&rsquo;t start, or stops, for 12 months. Towns assigned their own inspection program by the Commissioner handle this locally.',
			'Statewide, no outdoor advertising may be visible to the traveling public except as the law allows (10 V.S.A. &sect; 488). On-premises signs are limited to 150 square feet in total, but that cap doesn&rsquo;t apply to signs attached to or part of the building where the business operates; a building sign may rise no more than 10 feet above the roof, and signs visible from a highway can&rsquo;t flash, move or use unshielded bright lighting.',
			'In <strong>Burlington</strong>, the largest city, every sign needs a zoning permit unless Article 7 of the Comprehensive Development Ordinance exempts it; re-facing or changing the lettering in an existing permitted frame is exempt. The application, filed with the Department of Permitting and Inspections, is signed by the property owner and includes a scaled rendering, building elevations, illumination and fixture details, photos and the area of existing signs. The fee is $15 plus $118 per sign, approval takes at least three weeks, and Burlington also runs its own electrical permits under its Electricity Ordinance.',
		),
		'table' => array(
			'Electrical permit' => 'Validated work notice from a master or type-S journeyman',
			'Work notice fee' => '$40 base plus $5 per sign',
			'State on-premises cap' => '150 sq ft, not counting building-mounted signs',
			'Burlington sign permit' => 'Zoning permit signed by the property owner',
			'Burlington timing' => 'At least three weeks',
		),
		'sources' => array(
			array('26 V.S.A. &sect; 893: Work notices and inspections', $vt_893),
			array('10 V.S.A. &sect; 488: Outdoor advertising prohibited', 'https://legislature.vermont.gov/statutes/section/10/021/00488'),
			array('10 V.S.A. &sect; 493: On-premises signs', 'https://legislature.vermont.gov/statutes/section/10/021/00493'),
			array('City of Burlington: Sign application checklist (PDF)', 'https://burlingtonvt.gov/DocumentCenter/View/2471/Sign-Checklist-PDF'),
			array('City of Burlington: Permitting and Inspections', 'https://www.burlingtonvt.gov/DPI'),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Banners and Sandwich Boards in Burlington',
		'body' => array(
			'A temporary wall banner needs no permit in Burlington: one on a primary street frontage, up to 50 square feet, no higher than the second floor and unlit. It can stay up for 30 consecutive days, with no more than two display periods a calendar year and at least 30 days between them.',
			'Sandwich boards on private property are limited to one per ground-floor tenant, up to 8 square feet, 4 feet tall and 3 feet wide, within 15 feet of the entrance and only during business hours. A sidewalk path of at least 5 feet must stay clear, and a sandwich board in the public right-of-way needs approval from Public Works or the Church Street Marketplace Commission.',
		),
		'sources' => array(
			array('Burlington Comprehensive Development Ordinance, Article 7: Signs (PDF)', $btv_art7),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Burlington',
		'body' => array(
			'Each first- or second-floor window or entry may carry one window sign. It may cover no more than <strong>20 percent of any window</strong> between 4 and 7 feet above the sidewalk, and all window signs on a facade together are limited to 30 percent of the total window area. Lettering can be up to 8 inches tall, and window signs can&rsquo;t be lit except for neon. Displays set at least 12 inches behind the glass are exempt if they cover no more than 40 percent of that 4-to-7-foot band.',
		),
		'sources' => array(
			array('Burlington Comprehensive Development Ordinance, Article 7: Signs (PDF)', $btv_art7),
		),
	),
	'climate' => array(
		'Vermont winters are long and snowy, with deep cold in the Northeast Kingdom and repeated freeze-thaw cycles that work water into masonry, while mud season and summer downpours can flood river towns. Ask your installer to seal fastener holes in brick and clapboard, keep power supplies in a dry, accessible spot, and mount letters above where plowed snow piles up.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Vermont Storefronts',
		'lead' => 'Lit letters for Burlington and main-street storefronts, plus banners and window graphics that fit city limits.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Burlington and Rutland retail.',
			'standard-channel-letter-back-lit' => 'A soft halo for village main streets.',
			'premium-window-cling' => 'Window clings sized to Burlington&rsquo;s 20 percent limit.',
			'13oz-vinyl-banner' => 'Wall banners up to 50 square feet, permit-free.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Vermont?',
			'a' => 'We ship custom channel letters anywhere in Vermont, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Vermont master electrician or a type-S journeyman in outdoor advertising to mount and connect them.',
		),
		array(
			'q' => 'Does Vermont have a sign electrician license?',
			'a' => 'Yes, the type-S journeyman license in outdoor advertising. A type-S electrician works only in the specialty on the license and may only install branch circuits from the existing electrical service. A new or upgraded service needs a master electrician, and both a master and a type-S journeyman can file the state work notice.',
		),
		array(
			'q' => 'How do electrical permits work for a sign in Vermont?',
			'a' => 'A master or type-S journeyman files an Electrical Work Notice with the Division of Fire Safety, and the validated notice is the permit. The fee is $40 plus $5 per sign, wiring can&rsquo;t be covered before inspection, and the notice expires if work doesn&rsquo;t start, or stops, for 12 months.',
		),
		array(
			'q' => 'Does Vermont&rsquo;s billboard ban affect my storefront sign?',
			'a' => 'Not directly. Vermont prohibits outdoor advertising except as the law allows, and on-premises signs are allowed. They&rsquo;re capped at 150 square feet in total, but that cap doesn&rsquo;t apply to signs attached to your building. Building signs may rise no more than 10 feet above the roof, and flashing or moving lights aren&rsquo;t allowed.',
		),
		array(
			'q' => 'How much of a Burlington window can graphics cover?',
			'a' => 'No more than 20 percent of any window between 4 and 7 feet above the sidewalk, and no more than 30 percent of all windows on a facade combined, with one sign per window. Window signs can&rsquo;t be lit except for neon, and lettering is limited to 8 inches tall.',
		),
		array(
			'q' => 'How long does shipping to Vermont take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'26 V.S.A. chapter 15, 10 V.S.A. &sect;&sect; 488&ndash;495 and the 2025 Vermont Electrical Safety Rules (effective 11/5/2025) read directly 2026-11-20; closes the old notes on chapter 21 and on Division of Fire Safety permits. &sect; 494 is the exempt-sign list, not an on-premises size rule.',
		'Burlington CDO Article 7 (last updated Feb. 10, 2026) and the DPI page read directly; the sign checklist is dated July 2020 and its fees ($15 plus $118 per sign) and the state work notice fees ($40 plus $5 per sign): re-check yearly.',
		'Burlington runs its own Electricity Ordinance; the formal assignment under 26 V.S.A. &sect; 898 was not confirmed on a state page. Montpelier not researched.',
		'Transit days to Vermont not shown until verified.',
	),
);
