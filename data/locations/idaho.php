<?php
/**
 * Idaho state guide (/locations/idaho/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$id_200 = 'https://www.law.cornell.edu/regulations/idaho/IDAPA-24.39.10.200';
$boi_code = 'https://codelibrary.amlegal.com/codes/boise_id/latest/boise/0-0-0-68585';
$boi_signs = 'https://www.cityofboise.org/departments/planning-and-development-services/planning/zoning/design-and-development/signs/';

return array(
	'slug' => 'idaho',
	'name' => 'Idaho',
	'abbr' => 'ID',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Idaho',
	'neighbors' => array('washington', 'oregon', 'nevada', 'utah', 'wyoming', 'montana'),
	'seo_title' => 'Channel Letters Shipped to Idaho | Sign Electrical License',
	'seo_description' => 'Channel letters shipped to Idaho: the sign electrical limited license, state permits, and Boise window, banner and design review limits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Idaho',
	'intro' => array(
		'Idaho businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for an Idaho-licensed electrical contractor or sign installer. Banners and window graphics are printed in Renton, WA.',
		'The Idaho Electrical Board, part of the Division of Occupational and Professional Licenses (DOPL), offers a <strong>sign electrical limited license</strong>. Its holders install, maintain and repair sign equipment, controls and wiring on the secondary side of the sign&rsquo;s disconnecting means, as long as that disconnect is on the sign or within sight of it.',
		'Boise runs its own electrical permits and its own sign code, rewritten in 2023. Window signs covering 25 percent or less of the window need no permit, and banners are limited to one promotion or grand-opening display per business, up to 32 square feet.',
	),
	'facts' => array(
		'Licensing agency' => 'Idaho Electrical Board (DOPL)',
		'Sign license' => 'Sign electrical limited license (IDAPA 24.39.10)',
		'Scope' => 'Secondary side of the sign disconnect',
		'Boise window signs' => '25% or less of the window: no permit',
	),
	'categories' => array(
		'channel-letters' => 'Boise may waive engineering for wall signs mounted flush against the wall when the attachment details are clear and easy to verify, which describes most channel letter installs.',
		'window-graphics' => 'Boise exempts window signs from permits when they cover 25 percent or less of the window area in aggregate.',
		'banners' => 'Boise prohibits most banners but allows one promotion, event or grand-opening sign per establishment, up to 32 square feet, removed when the event ends.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Idaho',
		'body' => array(
			'Idaho&rsquo;s Electrical Board rules are IDAPA 24.39.10, and section 200.05 creates limited electrical licenses for restricted categories of work. The <strong>sign electrical</strong> category is &ldquo;only authorized to install, maintain, repair, and replace equipment, controls, and wiring on the secondary side of sign disconnecting means,&rdquo; provided the disconnect is on the sign or within sight of it. It replaces the older sign specialty wording of the archived IDAPA 07.01.04.',
			'A limited electrical installer must be employed by an electrical contractor or limited electrical contractor in the same restricted category, and limited work still requires a contractor license and supervision by a journeyman, a master electrician or a limited installer. Idaho Code 54-1016 exempts property owners doing noncommercial work at their own home, but nothing covers a commercial sign, so the circuit up to the disconnect is work for a journeyman or master employed by an electrical contractor.',
			'DOPL issues apprentice, journeyman, master and contractor licenses alongside the limited contractor, limited installer and limited trainee categories, and in October 2026 it began moving the Electrical Board to two-year licenses.',
		),
		'table' => array(
			'Licensing agency' => 'Idaho Electrical Board, DOPL',
			'Sign electrical limited license' => 'Secondary side of the sign disconnect',
			'Disconnect location' => 'On the sign or within sight of it',
			'Employer' => 'Contractor licensed in the same category',
			'Circuit to the disconnect' => 'Journeyman or master electrician',
		),
		'sources' => array(
			array('IDAPA 24.39.10.200: Electrical licenses (Cornell LII)', $id_200),
			array('Idaho DOPL: Electrical Board', 'https://dopl.idaho.gov/ele/'),
			array('Idaho Code 54-1016: Exemptions', 'https://legislature.idaho.gov/statutesrules/idstat/Title54/T54CH10/SECT54-1016/'),
		),
	),
	'permits' => array(
		'heading' => 'Electrical Permits and Sign Permits in Idaho',
		'body' => array(
			'Where DOPL is the permitting authority, electrical permits must be purchased before work starts, work can&rsquo;t be concealed before it&rsquo;s inspected, and a permit expires 365 days after purchase (IDAPA 24.39.10.500). Small commercial jobs costing $500 or less that don&rsquo;t change the service connection carry a $10 permit fee.',
			'<strong>Boise</strong>, the capital and largest city, issues its own electrical permits through Planning and Development Services, and a hired contractor pulls the permit for the work it performs. Any sign that is installed needs city approval unless the sign standards exempt it; changing copy, repainting and normal repairs need no permit as long as the sign structure or surface doesn&rsquo;t grow.',
			'A Boise on-premise sign submittal includes the application, a letter of explanation, a statement of legal interest signed by the owner of record, a site plan, colored scaled elevations and photos. Freestanding signs need anchorage plans stamped by an Idaho engineer, while flush wall signs with clear attachment details may skip engineering. Roof signs, animated signs and strobe or flashing lights are prohibited citywide.',
		),
		'table' => array(
			'State permits' => 'Bought before work starts; expire after 365 days',
			'Small commercial jobs' => '$10 permit for work of $500 or less',
			'Boise electrical permits' => 'Planning and Development Services',
			'Boise sign submittal' => 'Owner&rsquo;s legal-interest statement, site plan, colored elevations, photos',
			'Boise prohibited' => 'Roof signs, animated signs, flashing lights',
		),
		'sources' => array(
			array('IDAPA 24.39.10.500: Electrical permits and fees (Cornell LII)', 'https://www.law.cornell.edu/regulations/idaho/IDAPA-24.39.10.500'),
			array('City of Boise: Electrical permit application (PDF)', 'https://www.cityofboise.org/media/3811/ele-app.pdf'),
			array('City of Boise: Signs', $boi_signs),
			array('City of Boise: On-premise sign submittal requirements (PDF)', 'https://www.cityofboise.org/media/17974/on-premise-sign.pdf'),
		),
	),
	'districts' => array(
		array(
			'name' => 'Capitol Boulevard Special Design District (Boise City Code 11-04-012)',
			'rule' => 'Signs need Design Review approval as an overall sign plan for the site, wall signs are limited to 15 percent of the wall area or 65 square feet, whichever is less, and colors, materials and lighting must be restrained and harmonious with the building and site.',
			'source' => $boi_code,
		),
	),
	'temporary_signs' => array(
		'heading' => 'Promotions and Portable Displays in Boise',
		'body' => array(
			'Boise&rsquo;s code lists banners, pennants, streamers, balloons and similar attention-getting devices as prohibited unless another provision allows them. The main exception for a business is one special promotion, event or grand-opening sign per establishment, which can be a banner above the door in a multi-tenant building. It may be up to 32 square feet, or 8 feet tall if freestanding, and must come down when the promotion or opening ends.',
			'Portable signs are allowed only in the MX-5 zoning district, so a sidewalk A-frame generally isn&rsquo;t an option elsewhere in Boise. Sandwich boards for community events in The Grove are limited to 4 feet tall and 3 feet wide and can&rsquo;t stay out overnight.',
		),
		'sources' => array(
			array('Boise City Code 11-04-012: Sign standards', $boi_code),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Boise',
		'body' => array(
			'Window signs that keep to <strong>25 percent or less of the window area</strong> in aggregate are exempt from Boise&rsquo;s sign permits. Past that, the graphics need city approval like any other sign, so either keep store hours, logos and promotions under a quarter of the glass or plan full-coverage perforated film together with a permit.',
		),
		'sources' => array(
			array('Boise City Code 11-04-012.4: Exempt signs', $boi_code),
			array('City of Boise: Signs', $boi_signs),
		),
	),
	'climate' => array(
		'Boise sits in high desert, with hot, dry summers, cold winter inversions and wide swings between day and night, while North Idaho and the mountain towns get heavy snow. Summer wildfire smoke and strong sun are hard on faces and finishes. Ask for UV-stable faces, leave expansion room in the mounting, and keep raceways and drain holes clear of snow sliding off the roof above.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Idaho Storefronts',
		'lead' => 'Lit letters for Treasure Valley centers and downtown Boise, plus graphics that fit Boise&rsquo;s exemptions.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Boise, Meridian and Nampa retail centers.',
			'hidden-back-halo-lit' => 'A soft halo glow for downtown Boise facades.',
			'adhesive-window-perf' => 'Window graphics within Boise&rsquo;s 25 percent exemption.',
			'13oz-vinyl-banner' => 'A grand-opening banner up to 32 square feet.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Idaho?',
			'a' => 'We ship custom channel letters anywhere in Idaho, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire an Idaho-licensed electrical contractor, or a sign electrical limited licensee working for one, to mount and connect them.',
		),
		array(
			'q' => 'Does Idaho have a sign electrician license?',
			'a' => 'Yes, the sign electrical limited license under IDAPA 24.39.10. It covers installing, maintaining, repairing and replacing sign equipment, controls and wiring on the secondary side of the sign&rsquo;s disconnecting means, as long as the disconnect is on the sign or within sight of it. Holders must work for a contractor licensed in the same category.',
		),
		array(
			'q' => 'Who runs the power to my sign in Idaho?',
			'a' => 'A journeyman or master electrician working for a licensed electrical contractor. The sign electrical limited license starts at the sign&rsquo;s disconnect, so the branch circuit and the disconnect itself, which must be on the sign or within sight of it, belong to a full electrical license. Where the state issues permits, the permit is bought before work starts.',
		),
		array(
			'q' => 'Can I put graphics on my Boise windows without a permit?',
			'a' => 'Yes, if the window signs cover 25 percent or less of the window area in aggregate; Boise exempts those from sign permits. Larger window graphics need city approval like any other sign. Banners are stricter: one promotion, event or grand-opening sign per establishment, up to 32 square feet, removed when it ends.',
		),
		array(
			'q' => 'Do channel letters need engineering in Boise?',
			'a' => 'Often not. Boise asks for anchorage plans stamped by an Idaho engineer on freestanding signs, but wall signs mounted flush against the wall with clear attachment details that can be easily verified may be exempt from engineering. In the Capitol Boulevard district, wall signs are also capped at 15 percent of the wall or 65 square feet.',
		),
		array(
			'q' => 'How long does shipping to Idaho take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'IDAPA 24.39.10.200 and .500 read 2026-11-20 from the Cornell LII copy (effective April 19, 2024, marked temporary); adminrules.idaho.gov, legislature.idaho.gov and dopl.idaho.gov time out here, so Idaho Code 54-1016 and the DOPL page were read from Internet Archive copies (2026-02-14, 2026-11-12). Closes the old IDAPA 07.01.04 note.',
		'DOPL&rsquo;s 2026 proposed electrical rules (Aug. 13, 2026) not read: check they don&rsquo;t change the sign electrical category before going live.',
		'Boise City Code 11-04-012 (Ord. 27-23, eff. 12-1-2023; amended 6-24-2025) read from the archive copy of 2026-02-11 (amlegal returns 403 here). The on-premise submittal list is dated 8/2020, before the rewrite: confirm it&rsquo;s current.',
		'Fees ($10 small-work state permit): re-check yearly.',
		'Transit days to Idaho not shown until verified.',
	),
);
