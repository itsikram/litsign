<?php
/**
 * Ohio state guide (/locations/ohio/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'ohio',
	'name' => 'Ohio',
	'abbr' => 'OH',
	'status' => 'review',
	'neighbors' => array('michigan', 'indiana', 'kentucky', 'west-virginia', 'pennsylvania'),
	'seo_title' => 'Storefront Signs Shipped to Ohio | OCILB & Columbus Permits',
	'seo_description' => 'Channel letters shipped to Ohio, plus who can wire them (OCILB-licensed commercial electrical contractors) and how Columbus graphics permits work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Ohio',
	'intro' => array(
		'Ohio businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Ohio electrical contractor.',
		'Ohio licenses electrical contracting at the state level for commercial work. The Ohio Construction Industry Licensing Board (OCILB) issues state licenses to electrical contractors who perform commercial work, and a storefront sign is commercial work. Individual electrician licensing, where it exists, is set by cities.',
		'Columbus calls signs &ldquo;graphics&rdquo; and requires a graphics permit for each one, with a site plan, a dimensioned elevation and, for most contractors, drawings certified by an Ohio-licensed architect or engineer.',
	),
	'facts' => array(
		'Licensing agency' => 'Ohio Construction Industry Licensing Board (OCILB)',
		'License' => 'Electrical contractor (commercial work)',
		'Experience' => 'At least 5 years in the trade',
		'Columbus' => 'Graphics permit, one per sign',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in Ohio',
		'body' => array(
			'OCILB issues state licenses to <strong>electrical</strong>, HVAC, refrigeration, plumbing and hydronics contractors who perform commercial work. Its testing application asks for at least five years of experience in the trade, documented through permits, a journeyman card, an apprenticeship certificate or approved code education, or a registered professional engineer route.',
			'The state doesn&rsquo;t license individual journeyman electricians, but some cities do, so check local rules too. Look up the contractor on Ohio&rsquo;s eLicense site before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'Ohio Construction Industry Licensing Board, Department of Commerce',
			'Commercial work' => 'OCILB electrical contractor license',
			'Individual electricians' => 'Licensed by some cities, not the state',
		),
		'sources' => array(
			array('Ohio Department of Commerce: OCILB testing application (PDF)', 'https://dam.assets.ohio.gov/image/upload/com.ohio.gov/DICO/OCILB/Testing_Application_-_OCILB.pdf'),
			array('Ohio Legislative Service Commission: OCILB overview (PDF)', 'https://lsc.ohio.gov/assets/organizations/legislative-service-commission/files/ga-135-com-ohio-construction-industry-licensing-board-10021455.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Ohio',
		'body' => array(
			'Ohio cities issue sign permits under their own zoning codes, and building departments handle the structural and electrical side.',
			'In <strong>Columbus</strong>, Building and Zoning Services issues a graphics permit for each sign, one graphic per application. The application needs a site plan showing the tenant and sign locations, property or right-of-way lines and setbacks, a dimensioned elevation rendering, a view of the existing sign and, where applicable, a certificate of appropriateness. Contractors registered with the city may cite the city&rsquo;s structural standards; everyone else submits working drawings certified by an Ohio-licensed architect or engineer. Apply on paper or through Citizen Access.',
		),
		'table' => array(
			'Sign permit' => 'Your city',
			'Columbus' => 'Graphics permit from Building and Zoning Services',
			'Columbus drawings' => 'Ohio-licensed architect or engineer, unless a registered contractor',
		),
		'sources' => array(
			array('City of Columbus: Graphics permit application 2026 (PDF)', 'https://www.columbus.gov/files/sharedassets/city/v/3/building-and-zoning/document-library/graphics-permit-application-2026.pdf'),
			array('City of Columbus: Graphic and sign installation permit form (PDF)', 'https://new.columbus.gov/files/sharedassets/city/building-and-zoning/document-library/graphic-and-sign-installation-permit-form.pdf'),
		),
	),
	'climate' => array(
		'Ohio winters bring snow, ice and freeze-thaw cycles, and spring storms bring high winds. Ask your installer to seal wall penetrations so water can&rsquo;t freeze behind the letters.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Ohio Storefronts',
		'lead' => 'Lit letters for shopping centers and downtown blocks across the state.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters for suburban shopping centers in Ohio.',
			'standard-channel-letter-back-lit' => 'A soft glow on brick downtown storefronts in Ohio.',
			'adhesive-window-perf' => 'Window graphics for city storefronts.',
			'13oz-vinyl-banner' => 'Coming soon and grand opening banners in Ohio.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Ohio?',
			'a' => 'We ship custom channel letters anywhere in Ohio from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire an OCILB-licensed electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign electrician need in Ohio?',
			'a' => 'For commercial work, the contractor needs an OCILB electrical contractor license. Some cities also license individual electricians.',
		),
		array(
			'q' => 'What does Columbus need for a sign permit?',
			'a' => 'A graphics permit application for each sign, with a site plan, dimensioned elevation, a view of the existing sign and, unless your contractor is registered with the city, certified drawings.',
		),
		array(
			'q' => 'How long does shipping to Ohio take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Confirm the OCILB insurance minimum ($500,000 per secondary sources) before adding it.',
		'Transit days to Ohio not shown until verified.',
	),
);
