<?php
/**
 * New Jersey state guide (/locations/new-jersey/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'new-jersey',
	'name' => 'New Jersey',
	'abbr' => 'NJ',
	'status' => 'review',
	'neighbors' => array('new-york', 'pennsylvania', 'delaware'),
	'seo_title' => 'Storefront Signs Shipped to New Jersey | License Guide',
	'seo_description' => 'Channel letters shipped to New Jersey, plus who can wire them (licensed electrical contractors with a state business permit) and local sign permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to New Jersey',
	'intro' => array(
		'New Jersey businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed New Jersey electrical contractor.',
		'New Jersey&rsquo;s Board of Examiners of Electrical Contractors requires every electrical contractor to be licensed and to hold a valid business permit, renewed every three years. The board has said New Jersey has no low-voltage exemption from those requirements, so the company that wires your sign should hold both.',
		'Sign approvals are local. Each of New Jersey&rsquo;s municipalities applies its own zoning rules on sign size, placement and lighting, and many shopping centers add sign criteria in the lease.',
	),
	'facts' => array(
		'Licensing agency' => 'NJ Board of Examiners of Electrical Contractors (Division of Consumer Affairs)',
		'Requirement' => 'Electrical contractor license plus a business permit',
		'Renewal' => 'Every three years',
		'Sign permit' => 'Your municipality',
	),
	'licensing' => array(
		'heading' => 'Who Can Wire a Lit Sign in New Jersey',
		'body' => array(
			'The <strong>Board of Examiners of Electrical Contractors</strong> requires all electrical contractors to be licensed and to hold a valid <strong>business permit</strong>, which is renewed every three years. In a 2015 rule adoption the board stated that New Jersey has no &ldquo;low-voltage&rdquo; exemption from the business permit requirement.',
			'To sit for the license exam, an applicant shows a four-year approved apprenticeship followed by an additional year as a journeyman, then passes a three-part exam. Ask your sign installer for both their license and business permit numbers.',
		),
		'table' => array(
			'Licensing agency' => 'Board of Examiners of Electrical Contractors, NJ Division of Consumer Affairs',
			'Business' => 'Electrical contractor license and business permit',
			'Renewal' => 'Triennial',
			'Exam eligibility' => '4-year apprenticeship plus 1 journeyman year',
		),
		'sources' => array(
			array('NJ Board of Examiners of Electrical Contractors', 'https://njconsumeraffairs.gov/elec'),
			array('NJ Board of Examiners of Electrical Contractors: FAQ', 'https://njconsumeraffairs.gov/elec/Pages/FAQ.aspx'),
			array('NJ Division of Consumer Affairs: 2015 rule adoption (no low-voltage exemption)', 'https://www.njconsumeraffairs.gov/Adoptions/elecado_031615.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in New Jersey',
		'body' => array(
			'In New Jersey, the municipality approves the sign under its zoning ordinance, usually through the zoning officer and sometimes the planning or zoning board, while the construction office issues the building and electrical permits under the state Uniform Construction Code.',
		),
		'table' => array(
			'Sign approval' => 'Your municipality&rsquo;s zoning office',
			'Building and electrical permits' => 'Your municipal construction office',
		),
		'sources' => array(),
	),
	'climate' => array(
		'New Jersey gets humid summers, snowy winters and nor&rsquo;easters, and shore towns face salt air and hurricane season winds. Along the coast, ask your installer about corrosion-resistant hardware and anchoring for wind.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for New Jersey Storefronts',
		'lead' => 'Lit letters for strip malls and downtowns, plus graphics for storefront glass.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for strip malls and highway frontage.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for busy downtown blocks.',
			'adhesive-window-perf' => 'Window graphics that keep the view out in New Jersey.',
			'18oz-blockout-banner' => 'Durable banners for grand openings and sales.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in New Jersey?',
			'a' => 'We ship custom channel letters anywhere in New Jersey from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a licensed New Jersey electrical contractor to mount and connect it.',
		),
		array(
			'q' => 'What license does a sign installer need in New Jersey?',
			'a' => 'An electrical contractor license and a business permit from the Board of Examiners of Electrical Contractors. The board says there is no low-voltage exemption.',
		),
		array(
			'q' => 'Who approves a business sign in New Jersey?',
			'a' => 'Your municipality. The zoning office reviews the sign under the local ordinance, and the construction office issues the building and electrical permits.',
		),
		array(
			'q' => 'How long does shipping to New Jersey take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Add Newark&rsquo;s sign process once confirmed on the city site (a new sign chapter was adopted in November 2023 per a code-hosting site).',
		'The zoning office / construction office split is general New Jersey practice; confirm wording before going live.',
		'Transit days to New Jersey not shown until verified.',
	),
);
