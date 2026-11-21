<?php
/**
 * Delaware state guide (/locations/delaware/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$de_dpr = 'https://dpr.delaware.gov/boards/electrician/newlicense/';
$de_sc03 = 'https://delcode.delaware.gov/title24/c014/sc03/index.html';
$dov_signs = 'https://www.cityofdover.gov/media/Planning%20and%20Inspections/Forms%20and%20Brochures/Brochures/Signs';

return array(
	'slug' => 'delaware',
	'name' => 'Delaware',
	'abbr' => 'DE',
	'status' => 'live',
	'updated' => '2026-11-20',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Delaware',
	'neighbors' => array('maryland', 'pennsylvania', 'new-jersey'),
	'seo_title' => 'Channel Letters Shipped to Delaware | Electric Sign License',
	'seo_description' => 'Channel letters shipped to Delaware: the master electrician special for signs, private inspection agencies and Wilmington sign rules. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Delaware',
	'intro' => array(
		'Delaware businesses can order custom LED channel letters online, built in Washington and California and shipped ready for a Delaware-licensed electrician. Banners and window graphics are printed in Renton, WA.',
		'Delaware&rsquo;s Board of Electrical Examiners offers a <strong>master electrician special</strong> license in a single specialty, and electric signs is one of them. Inspections work differently from most states: every job gets its certificate from a Board-licensed private inspection agency, and the power company won&rsquo;t connect without the agency&rsquo;s cut-in card.',
		'Wilmington, the largest city, requires every new and existing sign to be registered with its Department of Land Use and Planning, and in Dover, the capital, window signs can fill up to half of a window.',
	),
	'facts' => array(
		'Licensing agency' => 'Board of Electrical Examiners (Division of Professional Regulation)',
		'Sign license' => 'Master electrician special, electric signs',
		'Inspections' => 'Board-licensed private inspection agencies',
		'Wilmington' => 'Every sign registered (City Code &sect; 41-42)',
	),
	'categories' => array(
		'channel-letters' => 'Wilmington&rsquo;s sign registration form must be signed by both the sign owner and the property owner, with a photo of each sign, and it goes in with the permit application for a new sign.',
		'window-graphics' => 'Dover&rsquo;s sign guide lets window signs occupy up to 50 percent of the window.',
		'banners' => 'Dover allows no more than two temporary signs at a time, for a maximum of 90 days in a calendar year.',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Delaware',
		'body' => array(
			'The State Board of Electrical Examiners licenses master, master special, limited, limited special, journeyperson, residential and apprentice electricians under 24 Del. C. chapter 14. A <strong>master electrician special</strong> may plan, lay out, perform or supervise electrical work for one specialty, and the statute&rsquo;s list includes electric signs, alongside swimming pools, air conditioning and heating, refrigeration and primary distribution systems (&sect; 1402(15)). Each specialty takes its own application.',
			'The sign specialty requires six years of full-time experience under a licensed master electrician or a master special in the same specialty, or 8,000 hours plus 576 hours of related instruction in a registered apprenticeship, and a passing exam (&sect; 1408). The limited electrician special doesn&rsquo;t include signs; it covers only HVAC and pools in buildings with four or fewer dwelling units.',
			'A contractor doing business in Delaware also needs a contractor license from the Division of Revenue before signing the contract, along with Department of Labor notice and proof of workers&rsquo; compensation (30 Del. C. &sect; 2502).',
		),
		'table' => array(
			'Licensing agency' => 'Board of Electrical Examiners, DPR',
			'Sign license' => 'Master electrician special, electric signs',
			'Experience' => 'Six years, or 8,000 hours plus 576 hours of instruction',
			'Limited electrician special' => 'HVAC and pools only, not signs',
			'Business license' => 'Division of Revenue contractor license',
		),
		'sources' => array(
			array('Delaware DPR: Electrician license types', $de_dpr),
			array('24 Del. C. chapter 14, subchapter I: Definitions', 'https://delcode.delaware.gov/title24/c014/sc01/index.html'),
			array('24 Del. C. chapter 14, subchapter II: Licensure', 'https://delcode.delaware.gov/title24/c014/sc02/index.html'),
			array('30 Del. C. chapter 25: Contractor licenses', 'https://delcode.delaware.gov/title30/c025/index.html'),
		),
	),
	'permits' => array(
		'heading' => 'Inspections and Sign Permits in Delaware',
		'body' => array(
			'All electrical work in Delaware, unless specifically exempt, needs a certificate of inspection from a <strong>Board-licensed inspection agency</strong> (24 Del. C. &sect; 1420). A state-licensed electrician files the application with the agency within 5 working days of starting work, the agency inspects within 5 working days, and violations must be corrected within 15 days. No power company may connect current to a property without the agency&rsquo;s permanent or temporary cut-in card, and DPR publishes the list of licensed agencies.',
			'In <strong>Wilmington</strong>, City Code &sect; 41-42 requires all new and existing signs to be registered with the Department of Land Use and Planning. The registration is signed by both the sign owner and the owner of the premises, includes a photograph of each sign, and accompanies the permit application for a new sign. The city&rsquo;s building permit application asks owners to use only tradespeople licensed in the City of Wilmington, and sidewalk or street encroachments need their own permit from Land Use and Planning.',
			'In <strong>Dover</strong>, a permit is needed to reface, replace or install a sign, with two sets of drawings showing dimensions, square footage and location. The city&rsquo;s sign guide lists roof signs, billboards and flashing or moving signs as not permitted, and electronic message centers may cover no more than 70 percent of a sign and change no more than once every 8 seconds.',
		),
		'table' => array(
			'Electrical inspection' => 'Board-licensed private inspection agency',
			'Filing deadline' => 'Within 5 working days of starting work',
			'Power connection' => 'Only with the agency&rsquo;s cut-in card',
			'Wilmington' => 'Every sign registered, with owner signatures and photos',
			'Dover' => 'Permit to reface, replace or install; two sets of drawings',
		),
		'sources' => array(
			array('24 Del. C. chapter 14, subchapter III: Inspections', $de_sc03),
			array('City of Wilmington: Sign registration form (PDF)', 'https://www.wilmingtonde.gov/home/showpublisheddocument/12906/638858371194230000'),
			array('City of Wilmington: Application for building permit (PDF)', 'https://www.wilmingtonde.gov/home/showpublisheddocument/1262/638817882664400000'),
			array('City of Dover: Signs brochure', $dov_signs),
		),
	),
	'districts' => array(
		array(
			'name' => 'Wilmington historic and conservation districts (Design Review and Preservation Commission)',
			'rule' => 'Exterior alterations in the City Historic Districts and Neighborhood Conservation Districts are reviewed by the Design Review and Preservation Commission, and building permits there are issued only for plans the commission has approved; staff may approve simple items administratively.',
			'source' => 'https://www.wilmingtonde.gov/government/city-departments/land-use-and-planning/land-use/historic-preservation',
		),
	),
	'temporary_signs' => array(
		'heading' => 'Temporary Signs in Dover',
		'body' => array(
			'Dover&rsquo;s sign guide allows no more than two temporary signs at any one time and a maximum of 90 days of display across the calendar year, and each temporary sign carries its own fee. That makes 90 days the yearly budget for banners, so plan openings and seasonal promotions within it.',
			'Wilmington&rsquo;s banner and window limits weren&rsquo;t available from the city&rsquo;s code for this guide, so check with the Department of Land Use and Planning before hanging a banner there.',
		),
		'sources' => array(
			array('City of Dover: Signs brochure', $dov_signs),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics in Dover',
		'body' => array(
			'Dover&rsquo;s sign guide allows window signs to occupy up to <strong>50 percent of the window</strong>, which leaves room for store hours, a logo and a full promotional panel on most storefronts. Because the guide dates from 2016, confirm the current limit with the Department of Planning and Inspections before ordering full-height window graphics.',
		),
		'sources' => array(
			array('City of Dover: Signs brochure', $dov_signs),
		),
	),
	'climate' => array(
		'Delaware&rsquo;s weather is shaped by the Atlantic and the Delaware Bay: nor&rsquo;easters and tropical storm remnants bring wind-driven rain and coastal flooding, beach towns from Lewes to Fenwick Island take salt spray, and summers are hot and humid. Specify stainless or coated hardware near the shore, and ask your installer to seal fastener holes and keep the power supply in a dry, serviceable location.',
	),
	'popular' => array(
		'heading' => 'Popular Choices for Delaware Storefronts',
		'lead' => 'Lit letters for Wilmington, Dover and beach-town storefronts, plus window graphics for busy summer seasons.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for Wilmington and Newark retail.',
			'standard-channel-letter-back-lit' => 'A soft halo on Dover and Lewes main streets.',
			'adhesive-window-perf' => 'Window graphics for beach-season promotions.',
			'13oz-vinyl-banner' => 'Banners planned around Dover&rsquo;s 90-day allowance.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Delaware?',
			'a' => 'We ship custom channel letters anywhere in Delaware, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Delaware master electrician, or a master electrician special in electric signs, to mount and connect them.',
		),
		array(
			'q' => 'Does Delaware have a sign electrician license?',
			'a' => 'Yes. The Board of Electrical Examiners issues a master electrician special license for one specialty, and electric signs is on the list. It requires six years of full-time experience under a master or master special in the specialty, or 8,000 hours plus 576 hours of instruction, and an exam. The limited electrician special doesn&rsquo;t cover signs.',
		),
		array(
			'q' => 'Who inspects electrical work for a sign in Delaware?',
			'a' => 'A private inspection agency licensed by the Board of Electrical Examiners. A state-licensed electrician files the application within 5 working days of starting work, the agency inspects within 5 working days, and the power company can&rsquo;t connect the work without the agency&rsquo;s cut-in card.',
		),
		array(
			'q' => 'Do I have to register my sign in Wilmington?',
			'a' => 'Yes. Wilmington City Code &sect; 41-42 requires every new and existing sign to be registered with the Department of Land Use and Planning. The form is signed by both the sign owner and the property owner, includes a photo of each sign, and goes in with the permit application for a new sign.',
		),
		array(
			'q' => 'How long does shipping to Delaware take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'24 Del. C. chapter 14, 30 Del. C. chapter 25 and the DPR license page read directly 2026-11-20. Closes the Wilmington &sect; 41-42 note (confirmed on the current city form).',
		'Wilmington forms and historic preservation page read from Internet Archive copies of 2025-12-10 to 2026-10-05 (wilmingtonde.gov returns 403 here); the city code on Municode showed a bot check, so Wilmington window, banner and A-frame limits were not verified. Wilmington fees ($12 per $1,000 of cost plus $20 per trade) not shown.',
		'Dover sign brochure is dated January 2016; the city site moved from cityofdover.com (now 404) to cityofdover.gov (403 here), and the archive copy of 2026-07-12 shows the same brochure still posted there. Confirm the 50 percent window, two-sign / 90-day temporary and electronic message center rules are current. Fees ($0.75 per sq ft, $50 minimum; $50 per temporary sign) not shown.',
		'Fees ($75 Division of Revenue contractor license): re-check yearly. Delaware&rsquo;s statewide outdoor advertising law not researched.',
		'Transit days to Delaware not shown until verified.',
	),
);
