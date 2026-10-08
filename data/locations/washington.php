<?php
/**
 * Washington state guide and city guides (/locations/washington/...).
 *
 * Edit freely. Rules for this file:
 * - Only facts you can source. Put the source link in 'sources'.
 * - 'review_notes' lists what still needs the owner's check; it is never shown.
 * - 'projects' and 'installers' stay empty until you have real ones:
 *   projects: array('image' => URL, 'alt' => '', 'quote' => '', 'caption' => 'Business, City')
 *   installers: array('name' => '', 'url' => '', 'area' => 'Cities served')
 * - 'transit' => array('days' => '1-2', 'verified' => true) shows a transit
 *   estimate; leave 'verified' false until checked with the carrier.
 * - Set 'status' => 'live' (state or individual city) after approval.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$lni_cities = 'https://lni.wa.gov/licensing-permits/electrical/electrical-permits-fees-and-inspections/city-electrical-permits-inspections';
$wac_specialties = 'https://app.leg.wa.gov/wac/default.aspx?cite=296-46B-920';

return array(
	'slug' => 'washington',
	'name' => 'Washington',
	'abbr' => 'WA',
	'status' => 'live',
	'neighbors' => array('oregon', 'idaho'),
	'seo_title' => 'Storefront Signs in Washington | Made in Renton, WA',
	'seo_description' => 'Channel letters and storefront signs made in Renton and shipped across Washington, with L&I licensing and city sign permit guides for your installer.',
	'h1' => 'Storefront Signs and Channel Letters in Washington',
	'local_note' => 'Our team is based in Renton, but we don&rsquo;t send installation crews anywhere, including in Washington.',
	'intro' => array(
		'Washington businesses can order custom LED channel letters, window graphics, banners and sidewalk signs made right here in Renton, see the price online, and have the finished sign shipped to their door, ready for a licensed local installer.',
		'Because we manufacture in the state, Washington orders have the shortest trip of anywhere we ship. What takes the most planning is usually local: your landlord&rsquo;s sign criteria, your city&rsquo;s sign code, and an electrician with the right Washington license to connect a lit sign. This guide covers each, with links to the official sources, plus guides to the rules in several Washington cities.',
		'Talk to us before you order if your sign has a logo, a custom shape or strict landlord criteria. Send a storefront photo and we&rsquo;ll help you pick a letter height and style that fits.',
	),
	'facts' => array(
		'Made in' => 'Renton, WA',
		'Electrical license' => 'L&amp;I electrical contractor; Signs (04) specialty electricians',
		'Electrical permit' => 'From L&amp;I, or from the city in 25 cities that inspect their own',
		'Sign permit' => 'From your city or county',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Washington',
		'body' => array(
			'Washington licenses electrical work statewide through the Department of Labor &amp; Industries (L&amp;I). Businesses doing electrical work must be licensed electrical contractors, and the people doing the work must hold an L&amp;I electrician certificate (or be supervised trainees).',
			'L&amp;I has a specialty certificate just for signs: <strong>Signs (04)</strong>. Under WAC 296-46B-920 it covers placing and connecting signs and outline lighting, their electrical supply, controls and associated circuit extensions, and installing a power service of up to 60 amps at 120/240 volts single phase that supplies only a remote sign. A general (journey level) electrician can also do this work.',
			'Washington is stricter than many states about who may put up a lit sign. Under the same rule, an electrical license or certificate is required to <strong>install, modify or maintain any part of a listed electric sign</strong>, not only to wire it. The only sign tasks that don&rsquo;t need one are cleaning the non-electrical parts, pouring a concrete pole base, operating machinery to help an electrician mount the sign, and assembling billboard structures. So in Washington, hire a sign installer that is an L&amp;I-licensed electrical contractor with Signs (04) or journey level electricians.',
		),
		'table' => array(
			'Licensing agency' => 'Washington State Department of Labor &amp; Industries (L&amp;I), Electrical Program',
			'Sign specialty' => 'Signs (04) specialty electrician certificate',
			'Contractor' => 'Licensed electrical contractor for the business doing the work',
			'Check a license' => 'Use L&amp;I&rsquo;s &ldquo;Verify a contractor, tradesperson or business&rdquo; lookup before you hire',
		),
		'sources' => array(
			array('WAC 296-46B-920: electrical license and certificate types and scope of work', $wac_specialties),
			array('L&I: Electrician certification', 'https://www.lni.wa.gov/Licensing-Permits/Electrical/Electrical-Licensing-Exams-Education/Electrician'),
		),
	),
	'permits' => array(
		'heading' => 'Sign and Electrical Permits in Washington',
		'body' => array(
			'A lit sign in Washington usually needs two permits from two different offices. The <strong>sign permit</strong> comes from your city (or county, outside city limits), which applies its own sign code for size, height and lighting. The <strong>electrical permit</strong> comes from L&amp;I, unless your business is inside one of the cities that issue their own electrical permits and inspections.',
			'L&amp;I lists 25 such cities, including Seattle, Bellevue, Renton, Everett, Spokane, Vancouver, Kirkland, Redmond and Tukwila, and one utility: in parts of Pierce County, Tacoma Power issues electrical permits for the properties it serves. Everywhere else, the electrical permit is bought from L&amp;I. Your electrician will know which applies to your address.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county building / planning department',
			'Electrical permit' => 'L&amp;I, or one of 25 cities that do their own, or Tacoma Power where it is the utility',
			'Before you order' => 'Landlord sign criteria and the city&rsquo;s sign code for your zone',
		),
		'sources' => array(
			array('L&I: Cities that issue their own electrical permits and inspections', $lni_cities),
		),
	),
	'climate' => array(
		'Western Washington&rsquo;s long rainy season makes sealing matter: ask your installer to seal every wall penetration and to plan the power supply location so it stays dry, as the wiring diagram specifies. East of the Cascades, cold winters and snow load on awnings and fascias are worth a look before you choose where the letters go.',
		'Short winter days also mean a lit sign is doing its job by late afternoon from November through February, which is one reason lit channel letters are the default storefront sign for most Washington retailers and restaurants.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Washington Storefronts',
		'lead' => 'Lit letters for the building, plus graphics and banners closer to the door.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'The brightest, most readable lit letters through Washington&rsquo;s gray months.',
			'hidden-back-halo-lit' => 'A soft halo glow that suits salons, clinics and upscale shops.',
			'adhesive-window-perf' => 'See-through window graphics for hours, offers and branding.',
			'13oz-vinyl-banner' => 'A temporary sign for your opening while permits are in review.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a storefront sign maker near me in Washington?',
			'a' => 'Yes. We make channel letters and storefront signs in Renton and ship them anywhere in Washington. We don&rsquo;t install, so you&rsquo;ll also need a local sign installer or an L&amp;I-licensed electrician to mount and connect the sign.',
		),
		array(
			'q' => 'Who issues the electrical permit for a sign in Washington?',
			'a' => 'L&amp;I, unless your business is inside one of the 25 cities that run their own electrical permits and inspections (such as Seattle, Bellevue, Renton or Everett), or Tacoma Power serves the property. Your licensed electrician normally buys the permit.',
		),
		array(
			'q' => 'What license does an electrician need to connect a sign in Washington?',
			'a' => 'An L&amp;I electrician certificate that covers sign work, such as the Signs (04) specialty or a journey level electrician certificate, working for a licensed electrical contractor. In Washington this applies to mounting a listed electric sign as well as wiring it.',
		),
		array(
			'q' => 'Can I pick up my sign in Renton?',
			'a' => 'No. Every order ships to your address, including orders in Renton and the Seattle area.',
		),
	),
	// Open content (not displayed facts): transit days hidden until verified; no Oregon or Idaho guide yet.
	'review_notes' => array(),
	'cities' => array(
		'seattle' => array(
			'name' => 'Seattle',
			'card' => 'SDCI sign permits; city electrical permits',
			'seo_title' => 'Storefront Signs in Seattle: Sign Permits & Lit Letters',
			'seo_description' => 'Ordering channel letters for a Seattle storefront? How SDCI sign permits work, signs over the sidewalk, Seattle electrical permits, and pricing.',
			'h1' => 'Storefront Signs and Channel Letters for Seattle Businesses',
			'intro' => array(
				'Seattle storefronts can order lit channel letters and window graphics from our Renton shop, see the price online, and have the sign shipped ready for a licensed Seattle installer.',
				'Seattle is one of the more involved places in Washington to put up a sign: the city runs its own sign permits through the Seattle Department of Construction and Inspections (SDCI), handles its own electrical permits, and has special review districts and landmarks where signs get an extra design review. Knowing which rules apply to your block before you order saves a redesign later.',
			),
			'facts' => array(
				'Sign permit' => 'SDCI, for signs over 5 sq ft or with power',
				'Electrical permit' => 'Combined with the SDCI sign permit for electric signs',
				'Sign code' => 'Seattle Municipal Code ch. 23.55',
				'Extra review' => 'Special review districts and landmarks',
			),
			'licensing' => array(
				'heading' => 'Who Can Connect a Sign in Seattle',
				'body' => array(
					'Installing a lit sign in Seattle follows Washington&rsquo;s L&amp;I licensing: a licensed electrical contractor with certified electricians, such as Signs (04) specialty or journey level electricians. The difference in Seattle is the permit: the City runs its own electrical permits and inspections, and for electric signs SDCI combines the electrical and sign permit into one.',
				),
				'table' => array(
					'Electrical permits &amp; inspection' => 'City of Seattle (SDCI), 700 5th Ave, Suite 2000 (206-684-8464); combined with the sign permit for electric signs',
					'Electrician license' => 'Washington L&amp;I certificate covering sign work',
				),
				'sources' => array(
					array('L&I: Cities that issue their own electrical permits (Seattle)', $lni_cities),
					array('WAC 296-46B-920: Signs (04) specialty scope', $wac_specialties),
				),
			),
			'permits' => array(
				'heading' => 'Seattle Sign Permits (SDCI)',
				'body' => array(
					'SDCI requires a sign permit for any sign larger than 5 square feet or connected to an electrical power source, which includes every lit channel letter sign, and for electric signs it combines the electrical and sign permit together. SDCI issues the permit after reviewing your application and plans against the Land Use Code (SMC 23.55) and the Building, Electrical and Energy Codes. Applications are made through the Seattle Services Portal.',
					'In special review districts such as Pioneer Square, SMC 23.66.030 requires a certificate of approval from the Department of Neighborhoods before the sign permit can be issued, and the district&rsquo;s board reviews the sign&rsquo;s size, lighting, colors and attachment. District rules restrict some sign and lighting types, so check with the board coordinator before you order a lit sign there.',
					'Signs that project over the sidewalk from private property, such as blade signs, under-canopy signs and awning graphics, follow SMC 15.12 and 23.55 and SDOT Director&rsquo;s Rule 05-2023. Since October 13, 2023 they no longer need a separate long-term Public Space Management permit, but they still need the SDCI sign permit.',
				),
				'table' => array(
					'Permit office' => 'Seattle Department of Construction and Inspections (SDCI)',
					'When needed' => 'Signs over 5 sq ft, or connected to an electrical source',
					'Sign code' => 'SMC Chapter 23.55; special review districts also need a certificate of approval (SMC 23.66.030)',
					'Over the sidewalk' => 'SMC 15.12, 23.55 and SDOT Director&rsquo;s Rule 05-2023',
				),
				'sources' => array(
					array('SDCI Tip 126: Sign, awning and billboard permits', 'https://www.seattle.gov/DPD/Publications/CAM/Tip126.pdf'),
					array('Pioneer Square Preservation Board staff report on a sign (cites SMC 23.66.030 and 23.66.160)', 'https://www.seattle.gov/documents/Departments/Neighborhoods/HistoricPreservation/HistoricDistricts/PioneerSquare/2026/PSB072926CourtyardsignSR.pdf'),
					array('SDOT: Signs, awnings and graphics over the right-of-way', 'https://www.seattle.gov/transportation/permits-and-services/permits/signs-awnings-and-graphics-over-the-right-of-way'),
				),
			),
			'climate' => array(
				'Seattle&rsquo;s steady rain and marine air are easy on LED letters but hard on unsealed wall penetrations, so ask your installer to seal every hole. Many Seattle storefronts sit under deep canopies or on brick facades; a raceway can keep holes in older masonry to a minimum.',
			),
			'popular' => array(
				'heading' => 'Popular Choices for Seattle Storefronts',
				'lead' => 'From street-level retail to restaurants under a canopy.',
				'products' => array(
					'standard-channel-letter-front-lit' => 'Bright, readable letters for busy streets.',
					'hidden-back-halo-lit' => 'Halo letters for brick and dark facades.',
					'frosted-vinyl-etched' => 'Etched-glass look for offices and clinics.',
					'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames, where your block allows them.',
				),
			),
			'projects' => array(),
			'installers' => array(),
			'faq' => array(
				array(
					'q' => 'Do I need a permit for a channel letter sign in Seattle?',
					'a' => 'Yes. SDCI requires a sign permit for any sign connected to electricity and for signs over 5 square feet; for electric signs, the electrical and sign permit are combined into one.',
				),
				array(
					'q' => 'Where can I find a sign installer near me in Seattle?',
					'a' => 'Look for a Seattle sign installer or electrical contractor whose electrician holds an L&amp;I certificate covering sign work, and check the license on L&amp;I&rsquo;s lookup. We ship your finished sign to them or to you, with the wiring diagram and install pattern.',
				),
				array(
					'q' => 'Is my Seattle sign reviewed differently in Pioneer Square or the International District?',
					'a' => 'Yes. Those are special review districts, where SMC 23.66.030 requires a certificate of approval from the Department of Neighborhoods before SDCI issues the sign permit. Check with the district&rsquo;s board coordinator before you order.',
				),
			),
			'review_notes' => array(),
		),
		'tacoma' => array(
			'name' => 'Tacoma',
			'card' => 'City sign permit; Tacoma Power electrical',
			'seo_title' => 'Storefront Signs in Tacoma, WA: Permits & Channel Letters',
			'seo_description' => 'Channel letters for Tacoma businesses, made in Renton. How Tacoma sign permits work, Tacoma Power electrical permits, and what your installer needs.',
			'h1' => 'Storefront Signs and Channel Letters for Tacoma Businesses',
			'intro' => array(
				'Tacoma businesses can order custom channel letters and storefront graphics from our Renton shop, about 25 miles up I-5, and have them shipped ready for a licensed Tacoma installer.',
				'Two things are specific to Tacoma. Sign permits go through the City&rsquo;s Planning &amp; Development Services under the Tacoma Municipal Code, and for properties served by Tacoma Power, the electrical permit and inspection come from Tacoma Power rather than from L&amp;I.',
			),
			'facts' => array(
				'Sign permit' => 'City of Tacoma Planning &amp; Development Services',
				'Electrical permit' => 'Tacoma Power, where it is your utility',
				'Installer license' => 'No separate city sign installer license since 2019',
				'Right-of-way work' => 'Bond required for work over the city right-of-way',
			),
			'licensing' => array(
				'heading' => 'Who Can Install and Connect a Sign in Tacoma',
				'body' => array(
					'Tacoma used to require a separate city regulatory license for sign installers and maintainers. According to the City, that license has not been required since July 15, 2019; sign work still needs a sign permit from Planning &amp; Development Services, and Washington&rsquo;s L&amp;I rules still apply to installing a lit sign.',
					'The electrical connection still needs a Washington L&amp;I-certified electrician working for a licensed electrical contractor. If Tacoma Power serves your property, it issues the electrical permit and performs the inspection.',
				),
				'table' => array(
					'Electrical permits &amp; inspection' => 'Tacoma Power, 3628 S 35th St (253-502-8277), for properties it serves; otherwise L&amp;I',
					'City sign installer license' => 'Not required since July 15, 2019 (sign permit still required)',
				),
				'sources' => array(
					array('City of Tacoma: Sign erectors (license change and permit requirements)', 'https://tacoma.gov/government/departments/finance/taxes-and-licenses/business-licensing/specific-business-activity/sign-erectors/'),
					array('L&I: Cities and utilities that issue their own electrical permits (Tacoma Power)', $lni_cities),
				),
			),
			'permits' => array(
				'heading' => 'Tacoma Sign Permits',
				'body' => array(
					'Installing or maintaining a sign in Tacoma requires a sign permit from Planning &amp; Development Services. The City&rsquo;s requirements include a certificate of insurance naming the City as certificate holder (minimum $100,000 per person and $300,000 per accident for injury, and $50,000 for property damage), and a right-of-way bond when the work is done in, on, over or from the City right-of-way, which can apply to projecting signs or work from the sidewalk.',
				),
				'table' => array(
					'Permit office' => 'City of Tacoma Planning &amp; Development Services',
					'Insurance' => 'Certificate naming the City: $100,000 / $300,000 injury, $50,000 property damage',
					'Right-of-way' => 'Bond for work in, on, over or from the City right-of-way',
				),
				'sources' => array(
					array('City of Tacoma: Sign erectors and sign permits', 'https://tacoma.gov/government/departments/finance/taxes-and-licenses/business-licensing/specific-business-activity/sign-erectors/'),
				),
			),
			'climate' => array(
				'Tacoma&rsquo;s wet winters call for well-sealed wall penetrations. Near the port and waterfront, ask your installer about corrosion-resistant fasteners for the salt air.',
			),
			'popular' => array(
				'heading' => 'Popular Choices for Tacoma Businesses',
				'lead' => '',
				'products' => array(
					'standard-channel-letter-front-lit' => 'Classic lit letters that read clearly from the road.',
					'standard-channel-letter-front-back-lit' => 'Face and halo glow together for a bold night look.',
					'feather-angled-flag-pole' => 'Roadside flags for car lots and strip centers.',
					'13oz-vinyl-banner' => 'Grand opening and sale banners.',
				),
			),
			'projects' => array(),
			'installers' => array(),
			'faq' => array(
				array(
					'q' => 'Who issues the electrical permit for a sign in Tacoma?',
					'a' => 'If Tacoma Power is your electric utility, Tacoma Power issues the electrical permit and inspects the work. Otherwise the permit comes from L&amp;I.',
				),
				array(
					'q' => 'Does a sign installer need a City of Tacoma license?',
					'a' => 'Not anymore. Tacoma stopped requiring its regulatory license for sign installers on July 15, 2019, but the sign still needs a city sign permit, and the electrical connection needs a licensed electrician.',
				),
				array(
					'q' => 'Can I get a channel letter sign near me in Tacoma?',
					'a' => 'Yes. We make channel letters in Renton and ship them to Tacoma addresses, ready for your local installer. We don&rsquo;t install or offer pickup.',
				),
			),
			// Content gap (not a displayed fact): Tacoma's zoning sign standards chapter isn't cited yet.
			'review_notes' => array(),
		),
		'bellevue' => array(
			'status' => 'review', // Unverified: see review_notes.
			'name' => 'Bellevue',
			'card' => 'Sign code 22B.10; design review zones',
			'seo_title' => 'Storefront Signs in Bellevue, WA: Sign Code & Permits',
			'seo_description' => 'Channel letters for Bellevue storefronts. What the Bellevue sign code (BCC 22B.10) requires, design review districts, and city electrical permits.',
			'h1' => 'Storefront Signs and Channel Letters for Bellevue Businesses',
			'intro' => array(
				'Bellevue retailers, restaurants and offices can order channel letters and window graphics from our Renton shop next door and have them shipped ready for a licensed installer.',
				'Bellevue&rsquo;s sign code puts more weight on design than most: in Downtown and several other districts, sign permits go through design review to check that the sign fits the building and its surroundings. Plan your letter style, color and lighting with that review in mind.',
			),
			'facts' => array(
				'Sign permit' => 'City of Bellevue, for most new signs',
				'Sign code' => 'Bellevue City Code ch. 22B.10',
				'Design review' => 'Downtown and several other districts',
				'Electrical permit' => 'City of Bellevue (not L&amp;I)',
			),
			'licensing' => array(
				'heading' => 'Who Can Connect a Sign in Bellevue',
				'body' => array(
					'Bellevue follows Washington&rsquo;s L&amp;I electrician licensing, but issues its own electrical permits and inspections. Your licensed electrical contractor gets the electrical permit from the City of Bellevue rather than from L&amp;I.',
				),
				'table' => array(
					'Electrical permits &amp; inspection' => 'City of Bellevue, 450 110th Ave NE (425-452-6800), per L&amp;I&rsquo;s list',
					'Electrician license' => 'Washington L&amp;I certificate covering sign work',
				),
				'sources' => array(
					array('L&I: Cities that issue their own electrical permits (Bellevue)', $lni_cities),
				),
			),
			'permits' => array(
				'heading' => 'Bellevue Sign Permits and Design Review',
				'body' => array(
					'A sign permit is required for any new sign except temporary and exempt signs. The code exempts signs of 6 square feet or less (other than subdivision directional signs), and repainting or routine maintenance that doesn&rsquo;t change the sign. A permit expires if the work isn&rsquo;t completed within one year of issuance.',
					'Design review applies to sign permits in Downtown, Community Business, Neighborhood Mixed Use, Neighborhood Business, Office Limited Business-Open Space, the BelRed districts (except BR-GC), Eastgate Transit Oriented Development, and transition areas next to residential districts. The City recommends talking to a land use planner before applying.',
					'Bellevue adopted a rewritten sign code in 2026, so confirm the current rules with the City before you order.',
				),
				'table' => array(
					'Sign code' => 'BCC Chapter 22B.10',
					'Exempt' => 'Signs of 6 sq ft or less (not subdivision directional signs)',
					'Permit lasts' => 'One year to complete the work (BCC 22B.10.160)',
					'Design review' => 'BCC 22B.10.025, in the districts listed above',
				),
				'sources' => array(
					array('City of Bellevue: Sign requirements', 'https://bellevuewa.gov/city-government/departments/development/zoning-and-land-use/zoning-requirements/signs'),
					array('Bellevue City Code Chapter 22B.10: Sign code', 'https://www.codepublishing.com/WA/Bellevue/html/Bellevue22B/Bellevue22B10.html'),
				),
			),
			'climate' => array(
				'Bellevue&rsquo;s glass-and-metal storefronts often suit halo lit or trimless letters, and many buildings have landlord criteria that specify the style. Rain sealing still matters on every wall penetration.',
			),
			'popular' => array(
				'heading' => 'Popular Choices for Bellevue Storefronts',
				'lead' => 'Clean, modern lit letters that pass design review more easily.',
				'products' => array(
					'hidden-back-halo-lit' => 'Stainless steel faces with a soft halo.',
					'exposed-acrylic-face-lit-borderless-no-trimcap' => 'Borderless face lit letters with no trimcap.',
					'inset-acrylic-face-lit-with-border-no-trimcap' => 'Trimless letters with a clean metal border.',
					'frosted-vinyl-etched' => 'Frosted glass graphics for offices and clinics.',
				),
			),
			'projects' => array(),
			'installers' => array(),
			'faq' => array(
				array(
					'q' => 'Does my Bellevue sign need design review?',
					'a' => 'It does if your business is in Downtown, Community Business, Neighborhood Mixed Use, Neighborhood Business, Office Limited Business-Open Space, most BelRed districts, Eastgate TOD, or a transition area next to housing. Talk to a City land use planner before applying.',
				),
				array(
					'q' => 'Who issues electrical permits in Bellevue?',
					'a' => 'The City of Bellevue issues its own electrical permits and does its own inspections, so your electrician applies to the City instead of L&amp;I.',
				),
				array(
					'q' => 'Is there a sign shop near me in Bellevue?',
					'a' => 'Our shop is in neighboring Renton. We make your sign and ship it to your Bellevue address; your licensed installer mounts and connects it. We don&rsquo;t offer pickup or installation.',
				),
			),
			'review_notes' => array(
				'Bellevue adopted a rewritten sign code (City Council roundup, June 9, 2026). The exemption, design review and permit-validity details on this page describe the old chapter 22B.10 and must be rewritten from the new code.',
			),
		),
		'renton' => array(
			'name' => 'Renton',
			'card' => 'Our home town; RMC 4-4-100',
			'seo_title' => 'Storefront Signs in Renton, WA: Made Here, Permit Guide',
			'seo_description' => 'Channel letters made in Renton for Renton businesses. How Renton sign permits work (RMC 4-4-100), temporary sign rules and city electrical permits.',
			'h1' => 'Storefront Signs Made in Renton for Renton Businesses',
			'local_note' => 'Renton is our home: our team is based here. We still don&rsquo;t install signs or offer pickup; every order ships to you.',
			'intro' => array(
				'Renton is where our team is based and where we make the channel letters and storefront signs we ship across the country. For Renton businesses that means a sign built a few miles away, priced online, and delivered ready for your licensed installer.',
				'Renton&rsquo;s sign rules are in the Renton Municipal Code, and the city also runs its own electrical permits. If you&rsquo;re opening soon, Renton&rsquo;s temporary sign permit lets you hang a grand opening banner while your permanent sign is in permitting.',
			),
			'facts' => array(
				'Sign code' => 'Renton Municipal Code 4-4-100',
				'Sign permit' => 'City of Renton Permit Center',
				'Electrical permit' => 'City of Renton (not L&amp;I)',
				'Grand opening' => 'Temporary sign permits for banners',
			),
			'licensing' => array(
				'heading' => 'Who Can Connect a Sign in Renton',
				'body' => array(
					'Renton issues its own electrical permits and inspections. Your L&amp;I-licensed electrical contractor applies to the City of Renton for the electrical permit, separately from the sign permit.',
				),
				'table' => array(
					'Electrical permits &amp; inspection' => 'City of Renton, 1055 S Grady Way (425-430-7200), per L&amp;I&rsquo;s list',
					'Electrician license' => 'Washington L&amp;I certificate covering sign work',
				),
				'sources' => array(
					array('L&I: Cities that issue their own electrical permits (Renton)', $lni_cities),
				),
			),
			'permits' => array(
				'heading' => 'Renton Sign Permits',
				'body' => array(
					'Renton regulates signs under RMC 4-4-100, with standards for type, placement, scale and construction that vary by use, zoning district and City Center sign district. A permanent sign permit application needs a signed application, a site plan drawn to scale (1 inch = 20 feet) and a construction plan; for wall signs the elevation shows the sign&rsquo;s dimensions and square footage and the building&rsquo;s height and length. Electrical and mechanical work need their own separate permits, and the City doubles permit fees when work starts before a permit is issued.',
					'For openings and events, Renton issues temporary sign permits, each good for 30 days. Temporary event permits can be issued four times per calendar year per business, with a 15-day break between events when the sign must come down, and each business is allowed one grand opening sign. Ask the Permit Center for the size limits before you order a banner.',
				),
				'table' => array(
					'Sign code' => 'RMC 4-4-100',
					'Permit Center' => '425-430-7215; Renton City Hall, 1055 South Grady Way',
					'Application' => 'Signed application, scaled site plan (1&rdquo; = 20&rsquo;), construction plan',
					'Temporary signs' => '30-day permits; event permits up to 4 a year with a 15-day break; one grand opening sign',
				),
				'sources' => array(
					array('City of Renton: Sign permit (permanent)', 'https://www.rentonwa.gov/City-Services/Permit-Services/Sign-Permit-Permanent'),
					array('City of Renton: Sign permit (temporary)', 'https://www.rentonwa.gov/City-Services/Permit-Services/Building-Fire-and-Signs/Sign-Permit-Temporary'),
					array('Renton Municipal Code 4-4-100: Sign regulations', 'https://codepublishing.com/WA/Renton/html/Renton04/Renton0404/Renton0404100.html'),
				),
			),
			'climate' => array(
				'Renton gets the same long wet season as the rest of the Puget Sound lowlands, so sealed penetrations and a dry location for the power supplies are the priorities for your installer.',
			),
			'popular' => array(
				'heading' => 'Popular Choices for Renton Businesses',
				'lead' => '',
				'products' => array(
					'standard-channel-letter-front-lit' => 'Lit letters for the building, made down the road.',
					'13oz-vinyl-banner' => 'Grand opening banners, with a Renton temporary sign permit.',
					'adhesive-window-perf' => 'Window graphics for hours and branding.',
					'aluminum-sign' => 'Aluminum signs for doors, parking and wayfinding.',
				),
			),
			'projects' => array(),
			'installers' => array(),
			'faq' => array(
				array(
					'q' => 'Can I pick up my sign at your Renton location?',
					'a' => 'No. Even for Renton businesses, every order ships to your address. We don&rsquo;t offer pickup or installation.',
				),
				array(
					'q' => 'Can I put up a grand opening banner in Renton while my sign is being permitted?',
					'a' => 'Renton issues temporary sign permits good for 30 days (event permits up to four times a year) and allows one grand opening sign per business. Check the size limits with the Permit Center before you order a banner.',
				),
				array(
					'q' => 'Who issues electrical permits for signs in Renton?',
					'a' => 'The City of Renton runs its own electrical permits and inspections; your licensed electrician applies to the City.',
				),
			),
			// Owner question (not shown on the page): can customers visit 707 S. Grady Way?
			'review_notes' => array(),
		),
		'kent' => array(
			'name' => 'Kent',
			'card' => 'Sign code KCC 15.06; L&I electrical',
			'seo_title' => 'Storefront Signs in Kent, WA: Sign Permits & Channel Letters',
			'seo_description' => 'Channel letters shipped to Kent businesses from Renton. How Kent sign permits work under KCC 15.06, nonconforming sign rules and L&I electrical permits.',
			'h1' => 'Storefront Signs and Channel Letters for Kent Businesses',
			'intro' => array(
				'Kent&rsquo;s warehouses, strip centers and restaurants are a short drive from our Renton shop. Order channel letters or window graphics online and we&rsquo;ll ship them ready for your licensed installer.',
				'Kent&rsquo;s permit process looks at your whole site, not just the new sign: if the property has an illegal or nonconforming sign, it must be brought into conformance before a new sign permit is issued. Check what&rsquo;s already on your building before you design the new one.',
			),
			'facts' => array(
				'Sign permit' => 'City of Kent, for all signs installed or altered',
				'Sign code' => 'Kent City Code ch. 15.06',
				'Electrical permit' => 'From L&amp;I (Kent doesn&rsquo;t issue its own)',
				'Whole-site review' => 'Nonconforming signs must be fixed first',
			),
			'licensing' => array(
				'heading' => 'Who Can Connect a Sign in Kent',
				'body' => array(
					'Kent is not on L&amp;I&rsquo;s list of cities that issue their own electrical permits, so your L&amp;I-licensed electrical contractor buys the electrical permit from L&amp;I and L&amp;I inspects the connection.',
				),
				'table' => array(
					'Electrical permits &amp; inspection' => 'Washington L&amp;I',
					'Electrician license' => 'Washington L&amp;I certificate covering sign work',
				),
				'sources' => array(
					array('L&I: Cities that issue their own electrical permits (Kent is not listed)', $lni_cities),
				),
			),
			'permits' => array(
				'heading' => 'Kent Sign Permits',
				'body' => array(
					'Kent requires a permit for all signs installed or altered in city limits, except signs exempted by KCC 15.06.080(A)(2). Under KCC 15.08.100(F)(2)(b), no sign permit is issued for a property with an illegal or nonconforming sign until that sign is brought into conformance, so a complete application covers all existing and proposed signs on the site.',
					'Any number of wall signs for one tenant space can go on a single application. Wall signs weighing 300 pounds or more need a final building inspection, and a wall sign that is part of an awning needs a separate building permit for the awning.',
				),
				'table' => array(
					'Sign code' => 'KCC Chapter 15.06',
					'Permit Center' => '253-856-5300',
					'Whole site' => 'Existing nonconforming signs fixed before a new permit',
					'Inspection' => 'Final building inspection for wall signs of 300 lb or more',
				),
				'sources' => array(
					array('City of Kent: Sign permit application instructions', 'https://kentwa.gov/home/showpublisheddocument/22220/638665054597870000'),
				),
			),
			'climate' => array(
				'Kent&rsquo;s Green River valley is wet and windy in winter. On tall warehouse and industrial facades, a raceway keeps the work at height simpler and the wall penetrations to a minimum.',
			),
			'popular' => array(
				'heading' => 'Popular Choices for Kent Businesses',
				'lead' => '',
				'products' => array(
					'standard-channel-letter-front-lit' => 'Lit letters that read from parking lots and arterials.',
					'standard-channel-letter-back-lit' => 'Back lit letters for a softer night glow.',
					'aluminum-sign' => 'Aluminum signs for warehouses, docks and parking.',
					'mesh-banner' => 'Wind-friendly mesh banners for fences and construction sites.',
				),
			),
			'projects' => array(),
			'installers' => array(),
			'faq' => array(
				array(
					'q' => 'Why does Kent want to know about my old signs?',
					'a' => 'Kent won&rsquo;t issue a new sign permit on a property with an illegal or nonconforming sign until it is brought into conformance (KCC 15.08.100), so the application covers every sign on the site.',
				),
				array(
					'q' => 'Who issues electrical permits for signs in Kent?',
					'a' => 'Washington L&amp;I. Kent doesn&rsquo;t run its own electrical permit program.',
				),
				array(
					'q' => 'Is there a storefront sign company near me in Kent?',
					'a' => 'We&rsquo;re in neighboring Renton. We make your sign and ship it to your Kent address; a licensed local installer mounts and connects it.',
				),
			),
			'review_notes' => array(),
		),
		'everett' => array(
			'status' => 'review', // Unverified: see review_notes.
			'name' => 'Everett',
			'card' => 'Sign code EMC 19.36; city electrical',
			'seo_title' => 'Storefront Signs in Everett, WA: Sign Code & Permits',
			'seo_description' => 'Channel letters for Everett businesses, made in Renton. Everett sign permits under EMC 19.36, window sign limits, nonconforming signs and city electrical permits.',
			'h1' => 'Storefront Signs and Channel Letters for Everett Businesses',
			'intro' => array(
				'Everett businesses can order custom channel letters, window graphics and banners from our Renton shop and have them shipped ready for a licensed local installer.',
				'Everett&rsquo;s sign chapter has two rules worth knowing before you design: window signs are capped at 25% of the window area, and each new permitted sign on a property with nonconforming signs requires one nonconforming sign to be removed or brought into conformance.',
			),
			'facts' => array(
				'Sign code' => 'Everett Municipal Code ch. 19.36',
				'Sign permit' => 'Building official, with Planning approval',
				'Window signs' => 'Up to 25% of the window area',
				'Electrical permit' => 'City of Everett (not L&amp;I)',
			),
			'licensing' => array(
				'heading' => 'Who Can Connect a Sign in Everett',
				'body' => array(
					'Everett issues its own electrical permits and inspections, so your L&amp;I-licensed electrical contractor applies to the City of Everett for the sign&rsquo;s electrical permit.',
				),
				'table' => array(
					'Electrical permits &amp; inspection' => 'City of Everett, 3200 Cedar Street (425-257-8810), per L&amp;I&rsquo;s list',
					'Electrician license' => 'Washington L&amp;I certificate covering sign work',
				),
				'sources' => array(
					array('L&I: Cities that issue their own electrical permits (Everett)', $lni_cities),
				),
			),
			'permits' => array(
				'heading' => 'Everett Sign Permits',
				'body' => array(
					'Everett&rsquo;s sign rules are in EMC Chapter 19.36. Sign permit applications go to the building official and need Planning approval, and the city engineer reviews signs for hazards to drivers and pedestrians.',
					'When a new sign that needs a permit goes on a property with nonconforming signs, one nonconforming sign must be removed or brought into conformance for each new sign installed for that business. Permanent and temporary window signs for commercial uses are limited to 25% of the window area.',
				),
				'table' => array(
					'Sign code' => 'EMC Chapter 19.36',
					'Review' => 'Building official, Planning, and the city engineer (traffic safety)',
					'Window signs' => 'Maximum 25% of window area',
					'Planning questions' => '425-257-8810, option 3',
				),
				'sources' => array(
					array('Everett Municipal Code 19.36: Signs', 'https://everett.municipal.codes/EMC/19.36.010'),
				),
			),
			'climate' => array(
				'Everett&rsquo;s waterfront and Port areas bring salt air and wind; ask your installer about corrosion-resistant fasteners and sealing on exposed facades.',
			),
			'popular' => array(
				'heading' => 'Popular Choices for Everett Businesses',
				'lead' => '',
				'products' => array(
					'standard-channel-letter-front-lit' => 'Lit letters that read clearly in the rain.',
					'standard-channel-letter-front-back-lit' => 'Face and halo glow for restaurants and bars.',
					'premium-window-cling' => 'Removable window clings within the 25% limit.',
					'13oz-vinyl-banner' => 'Banners for openings and events.',
				),
			),
			'projects' => array(),
			'installers' => array(),
			'faq' => array(
				array(
					'q' => 'How much of my window can be covered by signs in Everett?',
					'a' => 'Everett limits permanent and temporary window signs for commercial uses to 25% of the window area (EMC 19.36).',
				),
				array(
					'q' => 'Who issues electrical permits for signs in Everett?',
					'a' => 'The City of Everett issues its own electrical permits and inspections; your licensed electrician applies to the City.',
				),
				array(
					'q' => 'Can I get a lit sign made near me in Everett?',
					'a' => 'We make channel letters in Renton and ship them to Everett addresses, ready for a licensed local installer. We don&rsquo;t install or offer pickup.',
				),
			),
			'review_notes' => array(
				'Confirm EMC 19.36 numbering and the 25% window rule against the current code on everett.municipal.codes (sources mixed versions).',
			),
		),
	),
);
