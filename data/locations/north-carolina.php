<?php
/**
 * North Carolina state guide (/locations/north-carolina/). See washington.php
 * for the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'north-carolina',
	'name' => 'North Carolina',
	'abbr' => 'NC',
	'status' => 'review',
	'neighbors' => array('virginia', 'tennessee', 'georgia', 'south-carolina'),
	'seo_title' => 'Storefront Signs Shipped to North Carolina | SP-ES Sign License',
	'seo_description' => 'Channel letters shipped to North Carolina, plus the state Special Restricted Electric Sign (SP-ES) license and how Charlotte sign permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to North Carolina',
	'intro' => array(
		'North Carolina businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed North Carolina sign contractor.',
		'North Carolina has a state electrical license made for sign companies. The State Board of Examiners of Electrical Contractors issues a <strong>Special Restricted Electric Sign (SP-ES)</strong> license, and its qualified individual must be on active duty with a firm primarily engaged in a lawful electric sign business in the state.',
		'In Charlotte, a sign needs a city Zoning Use Permit together with a Mecklenburg County building permit, and the county&rsquo;s system flags when a sign also needs an electrical permit.',
	),
	'facts' => array(
		'Licensing agency' => 'NC State Board of Examiners of Electrical Contractors',
		'Sign license' => 'Special Restricted Electric Sign (SP-ES)',
		'Experience' => '3,000 hours, 2,000 of them primary',
		'Charlotte' => 'City Zoning Use Permit + Mecklenburg County building permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in North Carolina',
		'body' => array(
			'Under the board&rsquo;s rules, an applicant for the <strong>SP-ES</strong> classification needs 3,000 hours of experience, of which 2,000 hours must be primary experience, and may count experience gained in the electric sign field. The application must show the applicant is regularly on active duty in, and will be the listed qualified individual for, a firm primarily engaged in a lawful electric sign business in North Carolina.',
			'Electrical contractors with limited, intermediate or unlimited licenses can also install signs. Look up the contractor&rsquo;s license with the board before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'NC State Board of Examiners of Electrical Contractors',
			'Sign license' => 'SP-ES, Special Restricted Electric Sign',
			'Experience' => '3,000 hours (2,000 primary)',
			'Qualified individual' => 'Active duty with a firm primarily in the electric sign business',
		),
		'sources' => array(
			array('NC Office of Administrative Hearings: Electrical contractor licensing rules (PDF)', 'https://www.oah.nc.gov/media/14252/open'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in North Carolina',
		'body' => array(
			'North Carolina cities and counties issue sign permits under their own ordinances, and county code enforcement offices usually handle the building and electrical permits.',
			'In <strong>Charlotte</strong>, signs are among the projects that need a concurrent Mecklenburg County building permit and a City of Charlotte Zoning Use Permit. The city lists a 3-business-day intake window and a 10-business-day review, with another 10 business days for resubmittals. Contractors can apply online through the county&rsquo;s contractor dashboard, and the system flags when a sign also needs a building or electrical permit. Sign questions go to the municipality where the property is.',
		),
		'table' => array(
			'Sign permit' => 'Your city or county',
			'Charlotte zoning' => 'Zoning Use Permit; 10-business-day review',
			'Mecklenburg County' => 'Building permit, plus electrical permit for lit signs',
		),
		'sources' => array(
			array('Mecklenburg County Code Enforcement: Sign permits', 'https://code.mecknc.gov/permitting/signs'),
			array('City of Charlotte: Zoning permitting', 'https://charlottenc.gov/planning/ZoningAdministration/Pages/Zoning%20Permitting.aspx'),
		),
	),
	'climate' => array(
		'North Carolina summers are hot and humid, and the coast faces hurricanes and salt air while the mountains get snow and ice. Ask your installer about anchoring for wind and corrosion-resistant hardware near the coast.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for North Carolina Storefronts',
		'lead' => 'Lit letters for Charlotte and Triangle shopping centers and small-town main streets.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for growing suburban centers.',
			'hidden-back-halo-lit' => 'A polished halo look for offices and clinics in North Carolina.',
			'standard-channel-letter-back-lit' => 'A soft glow on downtown brick in North Carolina.',
			'feather-angled-flag-pole' => 'Flags for busy roads in North Carolina.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in North Carolina?',
			'a' => 'We ship custom channel letters anywhere in North Carolina from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an SP-ES sign contractor or a licensed electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'Does North Carolina have a sign electrician license?',
			'a' => 'Yes. The Special Restricted Electric Sign (SP-ES) license is issued by the State Board of Examiners of Electrical Contractors to qualified individuals of electric sign businesses.',
		),
		array(
			'q' => 'What permits does a sign need in Charlotte?',
			'a' => 'A City of Charlotte Zoning Use Permit and a Mecklenburg County building permit, plus an electrical permit for a lit sign.',
		),
		array(
			'q' => 'How long does shipping to North Carolina take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'SP-ES experience is from a rulemaking document (hours replaced "years"); confirm the adopted rule in 21 NCAC 18B. The SP-ES scope (sign wiring isolated from building wiring, per a vendor) is not shown.',
		'Transit days to North Carolina not shown until verified.',
	),
);
