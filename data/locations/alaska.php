<?php
/**
 * Alaska state guide (/locations/alaska/). See washington.php for the rules
 * of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'alaska',
	'name' => 'Alaska',
	'abbr' => 'AK',
	'status' => 'review',
	'neighbors' => array(),
	'seo_title' => 'Storefront Signs Shipped to Alaska | Electrician Guide',
	'seo_description' => 'Channel letters shipped to Alaska businesses, plus who can connect them (certified electricians and licensed electrical administrators) and local permits.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Alaska',
	'intro' => array(
		'Alaska businesses can price custom LED channel letters online, have them built in Washington and California, and have them shipped north, ready for a certified Alaska electrician.',
		'Alaska splits electrical credentials between two departments. The Department of Labor and Workforce Development&rsquo;s Mechanical Inspection Section issues certificates of fitness to individual electricians, while the Department of Commerce&rsquo;s Division of Corporations, Business and Professional Licensing licenses electrical administrators, the people who take responsibility for a contractor&rsquo;s electrical work.',
		'Shipping to Alaska takes longer than to the lower 48, so plan your sign order around your opening date and check the estimated delivery at checkout.',
	),
	'facts' => array(
		'Individual electricians' => 'Certificate of fitness (Department of Labor, Mechanical Inspection)',
		'Contractor responsibility' => 'Electrical administrator license (Department of Commerce)',
		'Certificate levels' => 'Journeyman, Residential Wireman, Trainee, Provisional',
		'Sign permit' => 'Your borough or city',
	),
	'licensing' => array(
		'heading' => 'Who Can Connect a Lit Sign in Alaska',
		'body' => array(
			'The <strong>Mechanical Inspection Section</strong> verifies training, experience and registration, runs testing and issues electrical certificates of fitness, including Journeyman Electrician, Power Lineman Journeyman, Residential Wireman, Trainee and Provisional certificates. It does not issue business, contractor or administrator licenses.',
			'Those come from the Department of Commerce, whose Division of Corporations, Business and Professional Licensing runs the <strong>electrical administrator</strong> program. Hire a contractor whose work is overseen by a licensed electrical administrator and done by certified electricians.',
		),
		'table' => array(
			'Individuals' => 'Certificate of fitness, DOLWD Mechanical Inspection Section',
			'Administrator' => 'Electrical administrator license, Department of Commerce (CBPL)',
			'Certificates' => 'Journeyman, Power Lineman, Residential Wireman, Trainee, Provisional',
		),
		'sources' => array(
			array('Alaska DOLWD: Mechanical Inspection Section, plumbing and electrical', 'https://dol.alaska.gov/lss/plumbing_electrical.htm'),
			array('Alaska DOLWD: Mechanical Inspection FAQ', 'https://dol.alaska.gov/lss/mi-faq.html'),
			array('Alaska Department of Commerce: Electrical administrators', 'https://www.commerce.alaska.gov/web/cbpl/ProfessionalLicensing/ElectricalAdministrators'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Alaska',
		'body' => array(
			'Sign permits in Alaska come from the borough or city. In Anchorage, signs are regulated under Title 21 of the municipal code, and many smaller communities have few sign rules, so ask the local planning office what applies at your address.',
		),
		'table' => array(
			'Sign permit' => 'Your borough or city',
			'Anchorage' => 'Municipal Code Title 21',
		),
		'sources' => array(),
	),
	'climate' => array(
		'Alaska winters bring deep cold, heavy snow and long hours of darkness, which is exactly when a lit sign earns its keep. Ask your installer about mounting above snow and ice buildup and sealing every wall penetration.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Alaska Storefronts',
		'lead' => 'Lit letters for long winter evenings, plus signs for the summer season.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters that stay readable through dark winter afternoons.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for maximum visibility in the dark months.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for summer visitors.',
			'13oz-vinyl-banner' => 'Seasonal opening and event banners in Alaska.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Alaska?',
			'a' => 'We ship custom channel letters anywhere in Alaska from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a local contractor with certified Alaska electricians to mount and connect it.',
		),
		array(
			'q' => 'Who can wire a lit sign in Alaska?',
			'a' => 'An electrician holding an Alaska certificate of fitness, working for a contractor whose electrical work is overseen by a licensed electrical administrator.',
		),
		array(
			'q' => 'Where do I get a sign permit in Alaska?',
			'a' => 'From your borough or city. Anchorage regulates signs under Title 21 of its municipal code.',
		),
		array(
			'q' => 'How long does shipping to Alaska take?',
			'a' => 'Alaska is outside the standard lower-48 transit times. Checkout shows the shipping options and estimated delivery date for your address, including production.',
		),
	),
	'review_notes' => array(
		'Shipping FAQ intentionally differs from other states; confirm carrier options and transit to Alaska before going live.',
		'Add Anchorage&rsquo;s sign permit steps once confirmed on muni.org (Title 21, section 21.03.140 in the 2006 draft).',
		'Confirm a contractor also needs a specialty contractor registration with the electrical endorsement (AS 08.18).',
	),
);
