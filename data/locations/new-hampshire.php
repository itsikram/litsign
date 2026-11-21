<?php
/**
 * New Hampshire state guide (/locations/new-hampshire/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$nh_319c = 'https://gc.nh.gov/rsa/html/XXX/319-C/319-C-mrg.htm';
$mht_zo = 'https://www.manchesternh.gov/Portals/2/Departments/pcd/MANCHESTER_ZONING_ORDINANCE-IN_EFFECT_MARCH_1_2026.PDF';

return array(
	'slug' => 'new-hampshire',
	'name' => 'New Hampshire',
	'abbr' => 'NH',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/New_Hampshire',
	'neighbors' => array('maine', 'massachusetts', 'vermont'),
	'seo_title' => 'Channel Letters Shipped to New Hampshire | Manchester Rules',
	'seo_description' => 'Channel letters shipped to New Hampshire: master and journeyman licensing, local inspections and Manchester&rsquo;s new 2026 sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to New Hampshire',
	'intro' => array(
		'New Hampshire businesses can order custom LED channel letters online, built in Washington and California and shipped ready for a New Hampshire-licensed electrician. Banners and window graphics are printed in Renton, WA.',
		'New Hampshire has no sign or limited electrician license. The Electricians&rsquo; Board licenses apprentices, journeymen, masters and high- and medium-voltage electricians under RSA 319-C, and a license is needed for electrical work at 30 volts or more, so the connection for lit letters goes to a journeyman or master.',
		'Inspections are mostly local, through city and town building officials. Manchester, the largest city, adopted a new zoning ordinance that took effect on March 1, 2026, with fresh limits on wall signs, window signs and lighting.',
	),
	'facts' => array(
		'Licensing agency' => 'Electricians&rsquo; Board (OPLC)',
		'Sign license' => 'None; journeyman or master electrician',
		'License needed' => 'Electrical work at 30 volts or more',
		'Manchester window signs' => '25% of the window or 12 sq ft, whichever is less',
	),
	'categories' => array(
		'channel-letters' => 'Manchester requires a permit even to illuminate an existing sign, and internally lit signs must have adjustable brightness.',
		'window-graphics' => 'Manchester window signs can cover 25 percent of the window or 12 square feet, whichever is less, and they may be internally lit.',
		'banners' => 'Manchester temporary signs are permit-exempt but limited to four per lot, 4 square feet each, unlit, and removed within 10 days after the event.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in New Hampshire',
		'body' => array(
			'The Electricians&rsquo; Board, administered by the Office of Professional Licensure and Certification (OPLC), regulates apprentices, journeymen, masters, high- and medium-voltage electricians and licensed business entities. RSA 319-C:7 issues master and journeyman licenses to applicants who meet the experience, education and exam requirements, and there is no limited or sign category. OPLC says a license is required to work with 30 volts or higher, and the statute covers installations for heating, lighting or power &ldquo;regardless of the voltage.&rdquo;',
			'A journeyman must be employed by a New Hampshire-licensed master electrician or a licensed entity, while a master may work on their own account; apprentices work under the direct supervision of a master or journeyman (RSA 319-C:2). The statute doesn&rsquo;t require the master to be physically on site with a journeyman. A narrow exemption lets a property owner&rsquo;s regular employees do incidental electrical work, a job of minor consequence needing no calculations, but it isn&rsquo;t a route for a sign company to wire lit letters.',
		),
		'table' => array(
			'Licensing agency' => 'Electricians&rsquo; Board, OPLC',
			'License classes' => 'Apprentice, journeyman, master, high/medium voltage',
			'Sign license' => 'None',
			'Journeyman' => 'Employed by a licensed master or entity',
			'License threshold' => '30 volts or higher (OPLC)',
		),
		'sources' => array(
			array('NH OPLC: Electricians&rsquo; Board', 'https://www.oplc.nh.gov/electricians-board'),
			array('RSA 319-C: Electricians', $nh_319c),
		),
	),
	'permits' => array(
		'heading' => 'Inspections and Sign Permits in New Hampshire',
		'body' => array(
			'Electrical inspections are generally done by city and town building officials enforcing the state building code, and those officials can check an electrician&rsquo;s license on the job (RSA 319-C:5). In municipalities that haven&rsquo;t adopted local enforcement of the state building code, the State Fire Marshal&rsquo;s state building permit system applies to all buildings except one- and two-family homes, with separate permits for electrical work (Saf-C 8101.02).',
			'In <strong>Manchester</strong>, sign permits go to the Building Regulations Division of Planning and Community Development. Under Article 9 of the zoning ordinance in effect since March 1, 2026, no sign may be erected, placed, replaced, moved, enlarged, illuminated or substantially altered without a permit. The application includes scaled plans and elevations showing the sign&rsquo;s location, size, method of attachment, structural support and illumination, plus a plan or photo inventory of all existing signs, and large signs need certification by a New Hampshire registered engineer.',
			'Wall sign area is capped per facade at a percentage or a fixed size, whichever is less: 10 percent or 500 square feet downtown, in the Millyard and in the BC-2 and industrial districts; 5 percent or 100 square feet in BC-1; and 3 percent or 32 square feet in residential and MX-1 districts. Internally lit signs must be able to adjust their brightness and can&rsquo;t be substantially brighter than other light in the area.',
		),
		'table' => array(
			'Inspections' => 'City or town building official, or the state system',
			'Manchester sign permit' => 'Required to erect, move, enlarge or illuminate a sign',
			'Manchester plans' => 'Scaled elevations, attachment, illumination, existing-sign inventory',
			'Downtown wall signs' => '10% of the facade or 500 sq ft, whichever is less',
			'Manchester lighting' => 'Adjustable brightness for internal lighting',
		),
		'sources' => array(
			array('N.H. Admin. Rules Saf-C 8100: State building permit system', 'https://www.gencourt.state.nh.us/rules/state_agencies/saf-c8100.html'),
			array('City of Manchester: Application for sign permit (PDF)', 'https://www.manchesternh.gov/pcd/Forms/SignPermit.pdf'),
			array('City of Manchester: Land Use Code', 'https://www.manchesternh.gov/Departments/Planning-and-Comm-Dev/Land-Use-Code'),
			array('City of Manchester: Zoning Ordinance in effect March 1, 2026 (PDF)', $mht_zo),
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs in Manchester',
		'body' => array(
			'Manchester&rsquo;s new ordinance exempts temporary signs from permits but still sets limits: no more than four per lot, each up to 4 square feet, none of them lit, and each must come down within 10 days after the event or situation it announces ends.',
			'Those limits are tight for a typical vinyl banner, so check with the Building Regulations Division before ordering a larger grand-opening banner, and lean on window graphics, which get their own allowance, for promotions.',
		),
		'sources' => array(
			array('Manchester Zoning Ordinance &sect; 9.2.9 (PDF)', $mht_zo),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics Rules in Manchester',
		'body' => array(
			'Window signs may cover no more than <strong>25 percent of the window area or 12 square feet, whichever is less</strong>, and a window made of several panes counts as one combined area. Unlike many cities, Manchester allows window signs to be internally lit. On a large storefront window the 12-square-foot cap usually controls, so plan hours, a logo and one promotion rather than full coverage.',
		),
		'sources' => array(
			array('Manchester Zoning Ordinance &sect; 9.8.6 (PDF)', $mht_zo),
		),
	),
	'climate' => array(
		'New Hampshire storefronts face nor&rsquo;easters, heavy snow and ice, and a spring thaw that loads walls with meltwater, while Portsmouth and the Seacoast add salt air. Choose letters with drainage and sealed seams, ask your installer to keep wall penetrations watertight, and specify stainless hardware near the coast.',
	),
	'popular' => array(
		'heading' => 'Popular Picks for New Hampshire Storefronts',
		'lead' => 'Lit letters for Manchester, Nashua and Seacoast storefronts, plus window graphics sized to Manchester&rsquo;s cap.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Manchester and Nashua retail.',
			'standard-channel-letter-back-lit' => 'A soft halo on Millyard and Elm Street brick.',
			'premium-window-cling' => 'Window clings within the 12 square foot cap.',
			'feather-angled-flag-pole' => 'Flags for frontage along the Everett Turnpike and I-93.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in New Hampshire?',
			'a' => 'We ship custom channel letters anywhere in New Hampshire, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a New Hampshire-licensed master electrician, or a journeyman working for one, to mount and connect them.',
		),
		array(
			'q' => 'Does New Hampshire have a sign electrician license?',
			'a' => 'No. RSA 319-C provides apprentice, journeyman, master and high- and medium-voltage licenses, plus licenses for business entities, but no limited or sign category. OPLC requires a license for work at 30 volts or more, so the circuit and connection for lit letters is journeyman or master work.',
		),
		array(
			'q' => 'Does a master electrician have to be on site in New Hampshire?',
			'a' => 'The statute doesn&rsquo;t say so for journeymen. A journeyman must be employed by a New Hampshire-licensed master electrician or a licensed entity, while apprentices need the direct supervision of a master or journeyman. Your local building official inspects the work and can check licenses on the job.',
		),
		array(
			'q' => 'What changed for signs in Manchester in 2026?',
			'a' => 'A new zoning ordinance took effect on March 1, 2026. Its Article 9 requires a permit to erect, move, enlarge or even illuminate a sign, caps window signs at 25 percent of the window or 12 square feet, and limits downtown wall signs to 10 percent of the facade or 500 square feet, whichever is less.',
		),
		array(
			'q' => 'How long does shipping to New Hampshire take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'RSA 319-C read from the Internet Archive copy of 2025-06-17, the OPLC board page from 2025-09-12 and Saf-C 8101.02 from 2024-09-16 (all three hosts return 403 here); later amendments not reflected. Closes the old note: there is no limited sign license, and the statute doesn&rsquo;t require a master on site with a journeyman.',
		'An OPLC notice (2025) about state electrical inspectors moving to the Department of Safety could not be read; who employs state inspectors now is not shown.',
		'Manchester ordinance (in effect March 1, 2026) read from the archive copy of 2025-12-22 (the direct download stalls here); the sign permit form is dated 09/02/14. No banner or A-frame rule was found in Article 9; Manchester historic district sign review and Portsmouth HDC rules not verified.',
		'Transit days to New Hampshire not shown until verified.',
	),
);
