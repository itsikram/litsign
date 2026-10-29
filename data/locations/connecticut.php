<?php
/**
 * Connecticut state guide (/locations/connecticut/). See washington.php for
 * the rules of these files.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$bpt_permit = 'https://www.bridgeportct.gov/government/departments/building-department/sign-permit';
$bpt_license = 'https://www.bridgeportct.gov/government/departments/building-department/sign-license';

return array(
	'slug' => 'connecticut',
	'name' => 'Connecticut',
	'abbr' => 'CT',
	'status' => 'live',
	'updated' => '2026-10-27',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Connecticut',
	'neighbors' => array('new-york', 'massachusetts', 'rhode-island'),
	'seo_title' => 'Channel Letters Shipped to Connecticut | C-7 Sign License',
	'seo_description' => 'Channel letters shipped to Connecticut: C-7 sign contractor and C-8 journeyperson licenses, plus Bridgeport sign licenses and permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Connecticut',
	'intro' => array(
		'Connecticut businesses can price custom LED channel letters online, have them built in Washington and California, and receive them ready for a licensed Connecticut sign contractor to mount and connect. Banners and window graphics are printed in Renton, WA.',
		'Connecticut is one of the few states with an electrical license made just for signs. The Department of Consumer Protection issues a C-7 Limited Electrical Sign Contractor license and a C-8 Limited Electrical Sign Journeyperson license, alongside the unlimited E-1 and E-2 electrical licenses. Some cities add a layer of their own: Bridgeport also requires a city sign license before it issues a sign permit.',
		'Town centers and plazas across the state often have sign rules of their own, so ask your landlord and your town&rsquo;s zoning office about size, color and lighting limits before you pick a letter height.',
	),
	'facts' => array(
		'Licensing agency' => 'Connecticut Department of Consumer Protection (DCP)',
		'Sign contractor' => 'C-7 Limited Electrical Sign Contractor',
		'Sign journeyperson' => 'C-8 Limited Electrical Sign Journeyperson',
		'Bridgeport' => 'City sign license plus a sign permit and zoning plan',
	),
	'categories' => array(
		'channel-letters' => 'A Connecticut C-7 sign contractor&rsquo;s work starts at a dedicated outlet or connection beside the sign, so if no circuit reaches the sign location yet, an E-1 or E-2 electrician runs it first.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Connecticut',
		'body' => array(
			'The Department of Consumer Protection licenses electrical work in Connecticut. A <strong>C-7 Limited Electrical Sign Contractor</strong> may install, service, maintain and test electric signs, where the work starts at a dedicated outlet or a connection directly next to the sign. A <strong>C-8 Limited Electrical Sign Journeyperson</strong> may do the same work, but only while employed by a licensed contractor.',
			'Because the sign licenses start at an outlet or connection beside the sign, running a new circuit to the sign location is work for an unlimited electrical contractor (E-1) or journeyperson (E-2). DCP&rsquo;s sign fact sheet also notes that local permits must be taken out before a lit sign is installed.',
		),
		'table' => array(
			'Licensing agency' => 'Connecticut Department of Consumer Protection, Occupational and Professional Licensing',
			'Sign contractor' => 'C-7 Limited Electrical Sign Contractor',
			'Sign journeyperson' => 'C-8 Limited Electrical Sign Journeyperson (works for a licensed contractor)',
			'New circuits' => 'E-1 Unlimited Electrical Contractor / E-2 Unlimited Electrical Journeyperson',
		),
		'sources' => array(
			array('Connecticut DCP: Electrical license types and scope of work', 'https://portal.ct.gov/dcp/occupational-and-professional-division/occupational--profess/electrical-license-types-and-scope-of-work'),
			array('Connecticut DCP: Electrical signs fact sheet (PDF)', 'https://portal.ct.gov/-/media/dcp/pdf/factsheets/electricalsigns2011pdf.pdf'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Connecticut',
		'body' => array(
			'Each Connecticut city and town writes its own sign rules into its zoning regulations and issues its own sign permits, so the same sign can be allowed in one town and too large in the next.',
			'<strong>Bridgeport</strong> requires a sign permit to erect, alter, repair, relocate, maintain components of or replace a ground, pole, wall, projecting or roof sign. Only a holder of a valid City of Bridgeport Sign License, or that holder&rsquo;s authorized agent with a signed letter on company letterhead and photo ID, may apply, and a state C-7 or C-8 license is required where it applies. Applications go through the city&rsquo;s Park City Portal as PDFs, with a separate zoning plan application, a site plan showing the sign locations, a detailed written scope of work, and construction drawings bearing an architect&rsquo;s or engineer&rsquo;s seal and signature.',
			'Bridgeport also asks for copies of any federal, state or Historic Commission pre-approvals, requires special inspections and testing under sections 1704 and 1705 of the State Building Code, and won&rsquo;t issue any permit while the property has delinquent property taxes or sewer user fees.',
			'The <strong>city sign license</strong> is needed by any company or individual putting up, altering, repairing, relocating or replacing an outdoor sign in Bridgeport. It is applied for through the Park City Portal with an insurance certificate naming the City of Bridgeport as certificate holder and covering liability, automobile and workers&rsquo; compensation; sole proprietors also upload a signed, notarized affidavit.',
		),
		'table' => array(
			'Sign permit' => 'Your city or town, under its zoning regulations',
			'Bridgeport' => 'Building Department sign permit through the Park City Portal, plus a zoning plan',
			'Bridgeport license' => 'City of Bridgeport Sign License, with a C-7 or C-8 where required',
			'Bridgeport drawings' => 'Architect&rsquo;s or engineer&rsquo;s seal and signature',
			'Bridgeport inspections' => 'Special inspections under State Building Code &sect;&sect; 1704&ndash;1705',
		),
		'sources' => array(
			array('City of Bridgeport: Sign permit', $bpt_permit),
			array('City of Bridgeport: Sign license', $bpt_license),
		),
	),
	'climate' => array(
		'Connecticut winters bring nor&rsquo;easters, snow and freeze-thaw cycles that work water into older brick, and Long Island Sound towns get salt air and coastal storm surge. Ask your installer to seal every wall penetration and to plan the mounting for wind on exposed shoreline facades.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Connecticut Storefronts',
		'lead' => 'Lit letters for plazas and main streets, plus graphics for storefront glass.',
		'products' => array(
			'standard-channel-letter-front-lit' => 'The everyday lit letter for Connecticut retail plazas.',
			'hidden-back-halo-lit' => 'An understated halo look for historic town centers.',
			'adhesive-window-perf' => 'Full window graphics that still let you see out.',
			'18oz-blockout-banner' => 'Heavy banners that hold up to New England weather.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter company near me in Connecticut?',
			'a' => 'We ship custom channel letters anywhere in Connecticut, built in Washington and California and tested before they ship. We don&rsquo;t install, so you&rsquo;ll hire a Connecticut sign contractor with a C-7 license, or an electrical contractor, to mount and connect them.',
		),
		array(
			'q' => 'What license does a sign installer need in Connecticut?',
			'a' => 'A C-7 Limited Electrical Sign Contractor license covers installing, servicing and testing electric signs from an outlet or connection next to the sign. C-8 sign journeypersons do the same work for a licensed contractor. New circuits need an E-1 or E-2 electrician.',
		),
		array(
			'q' => 'What do I need for a sign permit in Bridgeport?',
			'a' => 'A City of Bridgeport Sign License holder (with a C-7 or C-8 where required) applies through the Park City Portal with a zoning plan, a site plan, a written scope of work and sealed construction drawings, plus any Historic Commission pre-approval.',
		),
		array(
			'q' => 'What insurance does a Bridgeport sign license need?',
			'a' => 'An insurance certificate naming the City of Bridgeport as certificate holder and covering liability, automobile and workers&rsquo; compensation. Sole proprietors also upload a signed, notarized affidavit, and every document goes in as a PDF through the Park City Portal. The city doesn&rsquo;t list required amounts online.',
		),
		array(
			'q' => 'How long does shipping to Connecticut take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production.',
		),
	),
	'review_notes' => array(
		'C-7/C-8 scope is from the DCP license types page; the sign fact sheet is from 2011, so confirm it is still current.',
		'Bridgeport sign permit and sign license pages read directly 2026-10-27 (no fees or insurance amounts listed). Re-check yearly.',
		'Hartford and New Haven sign rules not researched.',
		'Transit days to Connecticut not shown until verified.',
	),
);
