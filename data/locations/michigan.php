<?php
/**
 * Michigan state guide (/locations/michigan/). See washington.php for the
 * rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'michigan',
	'name' => 'Michigan',
	'abbr' => 'MI',
	'status' => 'review',
	'neighbors' => array('ohio', 'indiana', 'wisconsin'),
	'seo_title' => 'Storefront Signs Shipped to Michigan | Sign Specialist Guide',
	'seo_description' => 'Channel letters shipped to Michigan, plus who can install them (LARA sign specialists and electricians) and how Detroit sign permits and sign licenses work.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Michigan',
	'intro' => array(
		'Michigan businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Michigan sign installer.',
		'Michigan has a state license just for sign electrical work. Under the Skilled Trades Regulation Act, the Department of Licensing and Regulatory Affairs (LARA) issues a <strong>sign specialist</strong> license to people with at least 4,000 hours, over at least two years, of experience making, installing, maintaining, connecting or repairing electric signs and their wiring. Licensed master, journeyman and apprentice electricians can do the same work without it.',
		'Some cities add their own layer. In Detroit, only sign professionals holding a current City of Detroit Sign License can apply for a sign permit.',
	),
	'facts' => array(
		'Licensing agency' => 'LARA Bureau of Construction Codes',
		'Sign license' => 'Sign specialist (MCL 339.5729)',
		'Also qualified' => 'Licensed master, journeyman or apprentice electrician',
		'Detroit' => 'City of Detroit Sign License required to apply for a permit',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Michigan',
		'body' => array(
			'MCL 339.5729 directs LARA to issue a <strong>sign specialist&rsquo;s license</strong> to an individual who is at least 18 and has at least 4,000 hours of experience, over at least two years, related to the manufacture, installation, maintenance, connection or repair of electric signs and related wiring, verified by a licensed contractor employer. The same section says a licensed master, journeyman or apprentice electrician doesn&rsquo;t need a sign specialist license to do that work.',
			'Sign specialists renew with a board-approved code update course in years when the state construction code changes. Check any license on LARA&rsquo;s license lookup before you hire.',
		),
		'table' => array(
			'Licensing agency' => 'LARA Bureau of Construction Codes, Licensing Section',
			'Sign license' => 'Sign specialist',
			'Experience' => '4,000 hours over at least 2 years in electric sign work',
			'Exempt' => 'Licensed master, journeyman and apprentice electricians',
		),
		'sources' => array(
			array('MCL 339.5729: Sign specialist license (Skilled Trades Regulation Act)', 'https://renderpdf.legislature.mi.gov/documents/mcl/pdf/MCL-339-5729.pdf'),
			array('LARA Bureau of Construction Codes: Licensing section', 'https://www.michigan.gov/lara/bureau-list/bcc/sections/licensing-section'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Michigan',
		'body' => array(
			'Michigan cities and townships issue sign permits under their own zoning ordinances, and some, like Detroit, also license sign companies themselves.',
			'In <strong>Detroit</strong>, the Buildings, Safety Engineering and Environmental Department (BSEED) accepts sign permit applications only from sign professionals with a current City of Detroit Sign License, through the city&rsquo;s online Accela portal, with a separate application for each sign. Electric sign companies licensed by the city must be located in Detroit and hold, or employ full time, a valid sign specialist&rsquo;s license.',
		),
		'table' => array(
			'Sign permit' => 'Your city or township',
			'Detroit' => 'BSEED, online through Accela; one application per sign',
			'Detroit applicants' => 'Holders of a City of Detroit Sign License',
		),
		'sources' => array(
			array('City of Detroit: Advertising and business sign permits', 'https://detroitmi.gov/departments/buildings-safety-engineering-and-environmental-department/bseed-divisions/plan-review/sign-permits'),
			array('City of Detroit BSEED: Sign specialist (electric sign company licensing)', 'https://detroitmi.gov/departments/buildings-safety-engineering-and-environmental-department-bseed/bseed-divisions/construction-inspection/electrical-inspection-team/sign-specialist'),
		),
	),
	'climate' => array(
		'Michigan winters bring lake-effect snow, ice and freeze-thaw cycles. Ask your installer to seal every wall penetration and mount letters clear of where snow piles up.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Michigan Storefronts',
		'lead' => 'Lit letters for strip centers and downtowns, plus window graphics for winter promotions.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright letters for strip centers along busy Michigan roads.',
			'standard-channel-letter-back-lit' => 'A soft glow for downtown brick storefronts.',
			'adhesive-window-perf' => 'Window graphics that keep the view out in Michigan.',
			'13oz-vinyl-banner' => 'Grand opening and sale banners in Michigan.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Michigan?',
			'a' => 'We ship custom channel letters anywhere in Michigan from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Michigan sign specialist or electrician to mount and connect it.',
		),
		array(
			'q' => 'Does Michigan have a sign electrician license?',
			'a' => 'Yes. LARA issues a sign specialist license for electric sign work. Licensed master, journeyman and apprentice electricians can also do the work.',
		),
		array(
			'q' => 'Who can apply for a sign permit in Detroit?',
			'a' => 'Only sign professionals holding a current City of Detroit Sign License, through the city&rsquo;s Accela portal.',
		),
		array(
			'q' => 'How long does shipping to Michigan take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Detroit details come partly from 2019-2021 city forms; re-check the current BSEED pages.',
		'Transit days to Michigan not shown until verified.',
	),
);
