<?php
/**
 * Trade site: the 7 channel letter types, written for sign companies.
 *
 * Keyed by product slug (the 7 channel letter products). This copy is the
 * trade site's own: it must not reuse retail wording (data/product-pages/,
 * functions.php wholesale_seo_channel_letter_products()). Spec rows are facts
 * from each product's component tabs. No turnaround days, volumes or prices
 * here: those need the owner's confirmation and prices are login-only.
 *
 * Keys: type (catalog number), name, short (card line), title / description
 * (SEO), h1, lead, best_for, sell (a line a sign company can use with its
 * client), faq (2 type-specific trade questions), install, family ('aluminum' | 'stainless'), specs, image (file in
 * uploads/storefront-files/, optional).
 *
 * @package litsign
 */

$aluminum_specs = array(
	'Returns' => '.040" high-reflective aluminum, 3" or 5" deep',
	'Trim cap' => 'Jewelite, 3/4" or 1"',
	'Face' => '3/16" acrylic; translucent, day/night or printed 3M vinyl for brand colors',
	'LEDs' => '0.7W / 1.2W IP67 modules, 160° beam, 9,000–10,500K',
	'Listing' => 'UL listed, sign section label for outdoor use',
	'Ships with' => '3 ft power lead, wiring diagram, full-size mounting pattern',
);
$stainless_specs = array(
	'Returns' => 'Welded stainless steel, 0.8 or 1 mm, 2" or 3" deep; sanded and painted',
	'LEDs' => '0.7W / 1.2W IP67 modules, 160° beam, 10,000–12,000K',
	'Colors' => '35+ standard finishes; custom Pantone C on request',
	'Listing' => 'UL listed, sign section label for outdoor use',
	'Ships with' => '3 ft power lead, wiring diagram, full-size mounting pattern',
);

return array(
	'standard-channel-letter-front-lit' => array(
		'faq' => array(
			array('q' => "How do I match a client's brand color on a front lit face?", 'a' => 'Pick a colored acrylic face when one is close, or a white face with 3M 3630 translucent film for exact brand colors. Choose 3M 3635 day/night film when the face must read dark by day and light up at night.'),
			array('q' => 'Should I spec 3 inch or 5 inch returns?', 'a' => 'Both are .040 aluminum. 3" returns suit smaller letters and tight landlord depth limits; 5" returns give taller letters more room for even LED spread and a bolder side profile from the street.'),
		),
		'type' => 1,
		'name' => 'Front Lit',
		'short' => 'The everyday face lit letter: acrylic face, trim cap, aluminum returns.',
		'title' => 'Wholesale Front Lit Channel Letters | Storefront Sign Wholesale',
		'description' => 'Front lit channel letters built to your shop drawings: .040 aluminum returns, 3/16" acrylic faces, Jewelite trim cap, UL labels. Trade accounts only.',
		'h1' => 'Wholesale Front Lit Channel Letters',
		'lead' => 'The letter most of your tenant and storefront jobs call for. LEDs light a 3/16" acrylic face, so the brand reads at night exactly as it does in daylight. We fabricate it, wire it, test it and label it; your crew mounts it.',
		'best_for' => array('Strip centers and tenant signage with landlord criteria', 'Raceway-mounted sets where the face color must match a brand book', 'High-volume repeat orders for franchise locations'),
		'sell' => 'Tell your client: the most legible lit sign per dollar, with the face color matched to their logo by acrylic or 3M vinyl.',
		'install' => 'Flush mount through the wall or onto a raceway. Each set arrives with a full-size pattern marking every stud and the wire penetration.',
		'family' => 'aluminum',
		'specs' => array('Lighting' => 'Face lit through the acrylic') + $aluminum_specs + array('Back' => 'Aluminum'),
		'image' => 'front-lit-led-channel-letter-on-brick-wall.png',
	),
	'standard-channel-letter-back-lit' => array(
		'faq' => array(
			array('q' => 'What wall finish works best with back lit letters?', 'a' => 'Smooth, light-colored walls show the glow best. Very dark or rough surfaces absorb the light, so on those facades we suggest Type 5 reverse lit letters instead.'),
			array('q' => 'Do back lit letters read in daylight?', 'a' => 'By day they read as solid dimensional aluminum letters in your chosen return color. The lit effect only shows after dusk, so pick a return color that carries the brand in daylight.'),
		),
		'type' => 2,
		'name' => 'Back Lit',
		'short' => 'Aluminum letters that wash the wall behind them with light.',
		'title' => 'Wholesale Back Lit Channel Letters | Storefront Sign Wholesale',
		'description' => 'Back lit channel letters for sign companies: aluminum returns, LEDs aimed at the wall for a soft glow, UL listed, mounting pattern and wiring diagram included.',
		'h1' => 'Wholesale Back Lit Channel Letters',
		'lead' => 'Opaque letters with the LEDs aimed backward, so the light spreads across the wall around each character. A good fit when the client wants a softer night look than a face lit sign but keeps an aluminum budget.',
		'best_for' => array('Light-colored or smooth wall finishes that show the glow', 'Restaurants, salons and offices that want an evening look', 'Clients upgrading from non-lit dimensional letters'),
		'sell' => 'Tell your client: the wall becomes part of the sign at night, and by day it reads as clean dimensional letters.',
		'install' => 'Mount on standoffs so the light has room to spread; the pattern shows standoff and wire locations for each letter.',
		'family' => 'aluminum',
		'specs' => array('Lighting' => 'LEDs aimed out the back to light the wall') + $aluminum_specs,
		'image' => 'back-lit-halo-channel-letter-on-brick-wall.png',
	),
	'standard-channel-letter-front-back-lit' => array(
		'faq' => array(
			array('q' => 'What makes the halo on a dual lit letter?', 'a' => 'A 1/8" clear Lexan back replaces the solid back, so the same LEDs that light the acrylic face also spill light onto the wall behind the letter.'),
			array('q' => 'How should I price dual lit against front lit?', 'a' => 'Quote it as the upgrade tier. The construction matches Type 1 apart from the clear back, so your install time stays the same while the client sees a clearly different sign at night.'),
		),
		'type' => 3,
		'name' => 'Front & Back Lit',
		'short' => 'Dual lit: a bright face plus a glow on the wall through a clear back.',
		'title' => 'Wholesale Dual Lit Channel Letters | Storefront Sign Wholesale',
		'description' => 'Front and back lit (dual lit) channel letters: acrylic face, clear 1/8" Lexan back, aluminum returns. Fabricated and UL listed for sign shops. Apply for trade pricing.',
		'h1' => 'Wholesale Front & Back Lit (Dual Lit) Channel Letters',
		'lead' => 'One letter, two light paths. The acrylic face lights like a standard front lit letter, while a clear Lexan back lets light out onto the wall. It is the upsell that wins the bid when two shops quote the same front lit set.',
		'best_for' => array('Flagship storefronts and corner locations', 'Brands that want a halo without moving to stainless', 'Bids where a visible step up helps you win the job'),
		'sell' => 'Tell your client: face lit legibility plus a halo, so the sign stands out from every face lit neighbor on the strip.',
		'install' => 'Mount on standoffs to show the halo. Wiring and the mounting pattern are the same as a front lit set, so your crew needs no new process.',
		'family' => 'aluminum',
		'specs' => array('Lighting' => 'Face lit and halo lit') + $aluminum_specs + array('Back' => '1/8" clear Lexan'),
		'image' => '',
	),
	'hidden-back-halo-lit' => array(
		'faq' => array(
			array('q' => 'What stroke widths can a stainless halo letter take?', 'a' => 'Strokes need to be at least 1/2" wide with 2" returns, or 1" wide with 3" returns. Very fine serifs may need thickening before production; we confirm before we build.'),
			array('q' => 'Which finishes are available?', 'a' => '35+ standard painted finishes over welded 0.8 or 1 mm stainless steel, and a custom Pantone C color on request. The color chart PDF lists the standard set.'),
		),
		'type' => 4,
		'name' => 'Halo Lit (Stainless)',
		'short' => 'Solid stainless face and returns; light shows only as a halo.',
		'title' => 'Wholesale Halo Lit Channel Letters | Storefront Sign Wholesale',
		'description' => 'Halo lit stainless steel channel letters for sign companies: welded face and returns, hidden clear Lexan back, 35+ finishes, UL listed. Trade accounts only.',
		'h1' => 'Wholesale Halo Lit Stainless Steel Channel Letters',
		'lead' => 'Welded stainless steel faces and returns with the light hidden behind them. By day it reads as a premium metal letter; at night the halo outlines every character. The letter architects specify for lobbies, clinics and hospitality.',
		'best_for' => array('Corporate lobbies, medical and professional offices', 'Hotels, restaurants and upscale retail', 'Architect-specified projects with a finish schedule'),
		'sell' => 'Tell your client: a metal sign by day and a lit sign by night, in a finish chosen from 35+ colors.',
		'install' => 'Always on standoffs; the halo needs space behind the letter. The pattern marks every standoff and the power feed.',
		'family' => 'stainless',
		'specs' => array('Lighting' => 'Halo only (light exits the back)', 'Face' => 'Solid stainless steel, 0.8 or 1 mm') + $stainless_specs + array('Back' => '1/8" clear Lexan, hidden'),
		'image' => 'halo-lit-channel-letter-front-view.png',
	),
	'halo-reverse-acrylic-lit-channel-letters' => array(
		'faq' => array(
			array('q' => 'How is reverse lit different from Type 4 halo lit?', 'a' => 'Type 4 hides a clear Lexan back. Type 5 uses a 0.4" translucent polycarbonate back that stays visible and glows itself, so the letter edge reads crisply even before the halo forms on the wall.'),
			array('q' => 'Can reverse lit letters go indoors?', 'a' => 'Yes. Reception walls and interior feature signs are a common use, and the glowing back edge helps logos with fine detail stay sharp at close range.'),
		),
		'type' => 5,
		'name' => 'Reverse Lit (Acrylic Back)',
		'short' => 'Stainless letters on a glowing translucent acrylic back.',
		'title' => 'Wholesale Reverse Lit Channel Letters | Storefront Sign Wholesale',
		'description' => 'Reverse lit channel letters: stainless steel face and returns on an exposed translucent acrylic back that glows and throws a halo. Built for sign shops, UL listed.',
		'h1' => 'Wholesale Reverse Lit Channel Letters',
		'lead' => 'A stainless face and return on an exposed translucent acrylic back. The back edge glows and casts the halo, which gives a crisper outline than a hidden back. A strong pick for darker walls and logos with fine detail.',
		'best_for' => array('Darker wall finishes where a hidden halo fades', 'Logos and marks that need a defined edge at night', 'Interior feature walls and reception signs'),
		'sell' => 'Tell your client: the outline of every letter glows, so the logo stays sharp even on a dark facade.',
		'install' => 'Standoff mount; the pattern sets the spacing for an even halo.',
		'family' => 'stainless',
		'specs' => array('Lighting' => 'Reverse / halo through an exposed acrylic back', 'Face' => 'Stainless steel, 0.8 or 1 mm') + $stainless_specs + array('Back' => '0.4" translucent polycarbonate acrylic, exposed'),
		'image' => 'reverse-lit-channel-letter-acrylic-back-diagram.png',
	),
	'inset-acrylic-face-lit-with-border-no-trimcap' => array(
		'faq' => array(
			array('q' => 'What does the stainless border replace?', 'a' => 'The plastic Jewelite trim cap on a standard letter. A thin welded stainless border holds the 9mm white acrylic face, so the edge looks like metal rather than plastic.'),
			array('q' => 'Can the face be a brand color?', 'a' => 'The face is white 9mm polycarbonate acrylic; brand colors are applied with translucent vinyl, while the border and returns take any of the stainless finishes.'),
		),
		'type' => 6,
		'name' => 'Trimless with Border',
		'short' => 'Face lit acrylic set inside a thin stainless border; no trim cap.',
		'title' => 'Wholesale Trimless Channel Letters | Storefront Sign Wholesale',
		'description' => 'Trimless face lit channel letters: 9mm acrylic face inset in a thin stainless steel border, no plastic trim cap, welded returns. Wholesale for sign companies.',
		'h1' => 'Wholesale Trimless Channel Letters with Border',
		'lead' => 'The face lit look without a plastic trim cap. A 9mm acrylic face sits inside a thin stainless steel border, so the edge of each letter stays clean and metal. It answers the "can we lose the trim cap?" question from design-led clients.',
		'best_for' => array('Design-led brands that dislike visible trim cap', 'Boutique retail, cafés and fitness studios', 'Close viewing distances where edge detail shows'),
		'sell' => 'Tell your client: all the brightness of a face lit sign with a clean metal edge instead of plastic trim.',
		'install' => 'Flush or standoff mount from the supplied pattern.',
		'family' => 'stainless',
		'specs' => array('Lighting' => 'Face lit', 'Face' => '9mm white polycarbonate acrylic, inset in a stainless border') + $stainless_specs + array('Back' => 'Stainless steel, 0.8 or 1 mm'),
		'image' => 'inset-acrylic-face-lit-channel-letter-front-view.jpg',
	),
	'exposed-acrylic-face-lit-borderless-no-trimcap' => array(
		'faq' => array(
			array('q' => 'Why is the borderless face 15mm thick?', 'a' => 'With no trim cap or border holding it, the thicker 15mm white acrylic face stays rigid and lets the whole exposed edge glow evenly around every character.'),
			array('q' => 'Which logos suit borderless letters best?', 'a' => 'Rounded, script and modern sans-serif marks, where an outline of light around each shape is the look the brand wants and no trim line should interrupt it.'),
		),
		'type' => 7,
		'name' => 'Borderless Face Lit',
		'short' => 'A thick exposed acrylic face with no trim cap and no border.',
		'title' => 'Wholesale Borderless Channel Letters | Storefront Sign Wholesale',
		'description' => 'Borderless face lit channel letters: 15mm exposed acrylic face with no trim cap or border, on stainless returns. For sign companies; apply for a trade account.',
		'h1' => 'Wholesale Borderless Face Lit Channel Letters',
		'lead' => 'A 15mm acrylic face with no trim cap and no border, so the whole face edge glows. The most modern face lit option in the range, popular for tech, beauty and lifestyle brands.',
		'best_for' => array('Modern brands with rounded or script logos', 'Interior and exterior feature signs', 'Clients comparing against trimless imports'),
		'sell' => 'Tell your client: nothing but light at the edge of every letter; no trim, no border.',
		'install' => 'Flush or standoff mount from the supplied pattern.',
		'family' => 'stainless',
		'specs' => array('Lighting' => 'Face lit; the exposed face edge glows', 'Face' => '15mm white polycarbonate acrylic, exposed') + $stainless_specs + array('Back' => 'Stainless steel, 0.8 or 1 mm'),
		'image' => 'borderless-face-lit-channel-letter-front-view.jpg',
	),
);
