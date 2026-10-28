<?php
/**
 * Illinois state guide (/locations/illinois/). See washington.php for the
 * rules of these files.
 *
 * chicago.gov blocks the research PC; the Department of Buildings sign
 * permit page was read from an Internet Archive copy dated 2025-07-18.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

$chicago_signs = 'https://www.chicago.gov/city/en/depts/bldgs/provdrs/permits/svcs/sign-permits.html';

return array(
	'slug' => 'illinois',
	'name' => 'Illinois',
	'abbr' => 'IL',
	'status' => 'live',
	'updated' => '2026-10-22',
	'wikipedia' => 'https://en.wikipedia.org/wiki/Illinois',
	'neighbors' => array('wisconsin', 'indiana', 'iowa', 'missouri', 'kentucky'),
	'seo_title' => 'Channel Letters Shipped to Illinois | Chicago Sign Permits',
	'seo_description' => 'Channel letters shipped to Illinois businesses: how local electrician licensing works and Chicago Department of Buildings sign permits. See prices online.',
	'h1' => 'Channel Letters and Storefront Signs Shipped to Illinois',
	'intro' => array(
		'Illinois businesses can price custom LED channel letters online, then have them built in Washington and California and shipped ready for a licensed local installer. Banners and window graphics are printed in Renton, WA and ship alongside.',
		'Like New York, Illinois leaves electrician licensing to local governments rather than the state, so the license your installer needs depends on the town where your storefront is. Chicago runs its own contractor licensing and its own sign permits, and it treats new channel letters as engineered work.',
		'If your business is in a Chicago suburb, call the village or city building department early: many license electrical contractors themselves and may or may not accept a license from another town.',
	),
	'facts' => array(
		'Electrician licensing' => 'Local (city or village); no statewide license',
		'Chicago sign permit' => 'Department of Buildings, one permit per sign',
		'Large Chicago signs' => 'City Council order over 100 sq ft or 24 ft high',
		'Over the sidewalk' => 'Chicago Public Way Use Permit and Council ordinance',
	),
	'categories' => array(
		'channel-letters' => 'In Chicago, installing new channel letters requires drawings sealed by an Illinois-licensed architect or structural engineer, so a licensed general contractor goes on the permit with a registered electrical contractor for the wiring.',
		'window-graphics' => 'Chicago lets window film, clings and painted lettering go up without a sign permit as long as all signage in a window covers no more than 25 percent of its glass.',
		'banners' => 'A paper, fabric or vinyl display attached to a Chicago window needs no permit for up to 60 days, inside the same 25 percent limit. Larger banners on the building follow the regular sign permit.',
	),
	'licensing' => array(
		'heading' => 'Who Can Install a Lit Sign in Illinois',
		'body' => array(
			'Illinois doesn&rsquo;t issue a statewide electrician license. Electrical contractors and electricians are licensed or registered by the city, village or county where the work happens, and requirements differ from town to town. Ask the local building department which license it requires and whether it accepts licenses from other municipalities.',
			'<strong>Chicago</strong> keeps its own lists of licensed general contractors and licensed electrical contractors. Any electrical work for a sign, its structure or its lighting must be done by a Chicago-registered electrical contractor named on the sign permit, and a sign that needs sealed drawings, which includes new channel letters, also needs a licensed general contractor on the application. Check both lists before you pay a deposit.',
		),
		'table' => array(
			'Statewide license' => 'None; licensing is local',
			'Chicago electrical work' => 'Chicago-registered electrical contractor, listed on the sign permit',
			'Chicago sealed-drawing signs' => 'Licensed general contractor listed (includes new channel letters)',
			'Suburbs' => 'Each city or village sets its own rules',
		),
		'sources' => array(
			array('Chicago Department of Buildings: Sign permits (contractor requirements)', $chicago_signs),
			array('Village of Libertyville: Electrical licensing (no state electrical license)', 'https://www.libertyville.com/departments/community_development/building/electrical_licensing_information.php'),
		),
	),
	'permits' => array(
		'heading' => 'Sign Permits in Illinois',
		'body' => array(
			'Sign permits are issued by your city or village under its own sign ordinance. In unincorporated areas, the county handles them.',
			'In <strong>Chicago</strong>, the Department of Buildings requires a permit for nearly every business sign, and each sign needs its own application. You start it online, pay the zoning review fee, then send the printed application, a supplemental sign form, a drawing of the sign, a location plan and any engineering documents to the Department&rsquo;s sign permit desk at 121 N. LaSalle St. A business can apply for its own on-premise sign, but the sign can&rsquo;t go up before the permit is issued. Signs larger than 100 square feet or higher than 24 feet above ground need a City Council Order, and anything that extends over the sidewalk, street or alley needs a Public Way Use Permit and a City Council ordinance.',
			'Chicago&rsquo;s sign rules are spread across several chapters of the Municipal Code: 10-24 (signs over the public way), 13-20 Article XIII, 13-96, the electrical code&rsquo;s section 14E-6-600 and zoning chapter 17-12. Check your parcel&rsquo;s zoning district on the City&rsquo;s zoning map before you choose a size.',
		),
		'table' => array(
			'Sign permit' => 'City or village building department (county if unincorporated)',
			'Chicago' => 'Department of Buildings; one application per sign; online plus paper supplement',
			'Chicago large signs' => 'City Council Order over 100 sq ft or 24 ft above grade',
			'Chicago projecting signs' => 'Public Way Use Permit and City Council ordinance',
		),
		'sources' => array(
			array('Chicago Department of Buildings: Sign permits', $chicago_signs),
		),
	),
	'window_signs' => array(
		'heading' => 'Window Graphics and Temporary Window Displays in Chicago',
		'body' => array(
			'Chicago exempts two kinds of window displays from its sign permit, both limited to 25 percent of the glass in each window: graphics painted on the glass or made of film fully adhered by adhesive or static cling, for any length of time, and temporary paper, fabric or vinyl displays attached to the window for up to 60 days. Painted lettering under 2 inches tall doesn&rsquo;t count toward the 25 percent. Hours, open/closed and address signs without a business name or logo need no permit either.',
		),
		'sources' => array(
			array('Chicago Department of Buildings: Sign permits (Sec. 13-20-550(d))', $chicago_signs),
		),
	),
	'climate' => array(
		'Illinois winters bring hard freezes, wind and snow, and Chicago adds lake wind. Seal every wall penetration, plan anchoring for wind on exposed corners, and remember that short winter days make a lit sign do most of its work after 4:30pm for months.',
	),
	'popular' => array(
		'heading' => 'Popular Signs for Illinois Storefronts',
		'lead' => '',
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright lit letters for long winter evenings.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters that stand out on busy corridors.',
			'hidden-back-halo-lit' => 'Halo letters for brick and boutique storefronts.',
			'adhesive-window-perf' => 'Window graphics for hours, offers and branding, within Chicago&rsquo;s 25 percent limit.',
		),
	),
	'projects' => array(),
	'installers' => array(),
	'faq' => array(
		array(
			'q' => 'Is there a channel letter sign company near me in Illinois?',
			'a' => 'We ship custom channel letters anywhere in Illinois, built in Washington and California and tested before they leave. We don&rsquo;t install, so you&rsquo;ll hire an electrical contractor licensed or registered by your city or village to mount and connect the letters.',
		),
		array(
			'q' => 'Does Illinois license electricians statewide?',
			'a' => 'No. Electrician licensing in Illinois is local. Chicago and many suburbs license or register electrical contractors themselves, and some accept other towns&rsquo; licenses while others don&rsquo;t, so check with your local building department before you hire.',
		),
		array(
			'q' => 'Do I need a permit for channel letters in Chicago?',
			'a' => 'Yes. Chicago requires a sign permit for each sign, and new channel letters need drawings sealed by an Illinois-licensed architect or structural engineer, a licensed general contractor and a registered electrical contractor on the application. Signs over 100 square feet or 24 feet high also need a City Council Order.',
		),
		array(
			'q' => 'Can I put graphics in my Chicago storefront window without a permit?',
			'a' => 'Usually. Window film, clings and painted lettering are exempt as long as all signage in a window covers no more than 25 percent of its glass, and temporary paper, fabric or vinyl window displays are exempt for up to 60 days within the same limit.',
		),
		array(
			'q' => 'How long does shipping to Illinois take?',
			'a' => 'Standard shipping takes 3&ndash;6 business days in transit, with 3-day, 2-day and overnight options. Checkout shows the estimated date for each, including production, so you can file the Chicago permit while your letters are made.',
		),
	),
	'review_notes' => array(
		'chicago.gov blocks the research PC; the sign permit page was read from an Internet Archive copy (2025-07-18). Open the link once before the next upload.',
		'Transit days to Illinois not shown until verified.',
	),
);
