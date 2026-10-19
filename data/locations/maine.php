<?php
/**
 * Maine state guide (/locations/maine/). See washington.php for the rules of
 * these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	'slug' => 'maine',
	'name' => 'Maine',
	'abbr' => 'ME',
	'status' => 'review',
	'neighbors' => array('new-hampshire'),
	'seo_title' => 'Storefront Signs Shipped to Maine | Sign License Guide',
	'seo_description' => 'Channel letters shipped to Maine businesses, plus the limited electrician license for outdoor signs and how Portland sign permits are filed online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Maine',
	'intro' => array(
		'Maine businesses can order custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Maine electrician to install.',
		'The Maine Electricians&rsquo; Examining Board licenses master and journeyman electricians, and it also issues limited licenses for particular trades. One of those limited categories is <strong>outdoor signs</strong>, including sign lighting, though it doesn&rsquo;t cover branch circuit wiring.',
		'Sign permits are local. In Portland, the state&rsquo;s largest city, sign and awning applications are accepted only online through the city&rsquo;s Citizen Self Service portal.',
	),
	'facts' => array(
		'Licensing agency' => 'Maine Electricians&rsquo; Examining Board (Office of Professional and Occupational Regulation)',
		'Sign license' => 'Limited electrician: outdoor signs, including sign lighting',
		'Full licenses' => 'Master and Journeyman electrician',
		'Sign permit' => 'Your city or town (Portland: online CSS portal)',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Maine',
		'body' => array(
			'Maine&rsquo;s electrical licenses run from helper and apprentice to journeyman and master, plus limited licenses for water pumps, outdoor signs, gasoline dispensing, traffic signals, house wiring, refrigeration, low energy and crane work. The <strong>outdoor signs</strong> limited license, including sign lighting, calls for 135 hours of approved electrical education and 2,000 hours of experience, and its privileges do not include branch circuit wiring.',
			'In practice, a limited sign electrician can install and connect the sign, while a new branch circuit to the sign location is work for a master or journeyman electrician.',
		),
		'table' => array(
			'Licensing agency' => 'Maine Electricians&rsquo; Examining Board',
			'Sign license' => 'Limited electrician, outdoor signs',
			'Requirements' => '135 hours of electrical education and 2,000 hours of experience',
			'Not included' => 'Branch circuit wiring (needs a master or journeyman)',
		),
		'sources' => array(
			array('Maine Office of Professional and Occupational Regulation: Electrician license types (PDF)', 'https://www.maine.gov/pfr/professionallicensing/sites/maine.gov.pfr.professionallicensing/files/inline-files/joint%20electrical.pdf'),
			array('Maine Legislature: limited license requirements (Public Law, 124th Legislature)', 'https://www.legislature.maine.gov/legis/bills/bills_124th/chapters/PUBLIC112.asp'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Maine',
		'body' => array(
			'Maine cities and towns issue sign permits under their own land use ordinances, and sign size and lighting limits vary a great deal between a downtown and a shoreline village.',
			'<strong>Portland</strong>&rsquo;s Permitting and Inspections Department accepts sign and awning applications only online through the Citizen Self Service (CSS) portal. A complete application includes the permit form, a plan showing all existing and proposed signs, a signed lease or the owner&rsquo;s permission if you are a tenant, and a certificate of liability naming the City as additional insured when any part of the sign could extend over the public right of way. Sidewalk signs have their own checklist, including at least $400,000 of liability coverage, a limit of one per establishment per street frontage, and a size of no more than 24 inches wide by 40 inches tall.',
		),
		'table' => array(
			'Sign permit' => 'Your city or town',
			'Portland' => 'Permitting and Inspections Department, online through CSS',
			'Portland sidewalk signs' => 'Up to 24" wide x 40" tall; $400,000 liability certificate',
		),
		'sources' => array(
			array('City of Portland, ME: Sign and awning permit checklist', 'https://content.civicplus.com/api/assets/53efdf42-8610-46fa-952d-b0110e892d9e'),
			array('City of Portland, ME: Sidewalk sign checklist', 'https://content.civicplus.com/api/assets/fe227193-cb9f-43d4-8e55-573dfede0659'),
		),
	),
	'climate' => array(
		'Maine winters are long, with heavy snow, ice and coastal storms, and salt air along the shore. Mount letters high enough to clear snowbanks and plow spray, and ask your installer to seal every wall penetration.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Maine Storefronts',
		'lead' => 'Signs for main streets, harbors and the summer season.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Lit letters that stay readable through long winter evenings.',
			'hidden-back-halo-lit' => 'A quiet halo look for historic downtowns.',
			'deluxe-signicade-graphic-frame' => 'Sidewalk A-frames for summer foot traffic in Maine.',
			'13oz-vinyl-banner' => 'Seasonal opening and event banners in Maine.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Maine?',
			'a' => 'We ship custom channel letters anywhere in Maine from our shops in Washington and California. We don&rsquo;t install, so you&rsquo;ll hire a Maine-licensed electrician to mount and connect it.',
		),
		array(
			'q' => 'Does Maine have a license for sign electricians?',
			'a' => 'Yes. Maine issues a limited electrician license for outdoor signs, including sign lighting. It doesn&rsquo;t cover branch circuit wiring, which needs a master or journeyman electrician.',
		),
		array(
			'q' => 'How do I apply for a sign permit in Portland, Maine?',
			'a' => 'Online only, through the city&rsquo;s Citizen Self Service portal, with the permit form, a sign plan, your lease or owner permission, and a liability certificate if the sign could reach over the public right of way.',
		),
		array(
			'q' => 'How long does shipping to Maine take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'Outdoor signs limited license hours are from a 124th Legislature law; confirm they match the current statute and board rules.',
		'Portland checklists are hosted on CivicPlus; confirm the links still point at the current Portland, ME versions.',
		'Transit days to Maine not shown until verified.',
	),
);
