<?php
/**
 * Category page copy printed by home.php around the product grid:
 *
 * - 'lead'  One paragraph above the products, after the category's own intro
 *           (Products > Categories), so the two read as 100-150 words together.
 * - 'guide' Buying guide below the products: 'title' and 'sections', each with
 *           'h' (heading) plus 'p' (paragraphs), 'list' or 'table' (rows; the
 *           first row is the header).
 *
 * Facts come from the products' own spec sheets (see data/product-pages/).
 * Keys are product_category slugs. HTML is allowed in text (wp_kses_post).
 *
 * @package litsign
 */

$cat = static function ($slug, $text) {
	return '<a href="' . esc_url(wholesale_category_url($slug)) . '">' . esc_html($text) . '</a>';
};
$prod = static function ($slug, $text) {
	return '<a href="' . esc_url(home_url('/product/' . $slug . '/')) . '">' . esc_html($text) . '</a>';
};
$page = static function ($path, $text) {
	return '<a href="' . esc_url(home_url('/' . trim($path, '/') . '/')) . '">' . esc_html($text) . '</a>';
};
$artwork = 'Build your file at the size you order, in CMYK, as a JPEG or single-page PDF; free templates are in our ' . $page('design-templates', 'design templates and artwork guide') . '.';

return array(
	'banners' => array(
		'lead' => 'Every banner is printed to the size you enter and priced per square foot, so you pay for exactly what you hang. Vinyl banners are UV printed and fabric banners are dye sublimated; all are made to order and shipped nationwide.',
		'guide' => array(
			'title' => 'How to Choose the Right Banner',
			'sections' => array(
				array('h' => 'Match the material to the job', 'p' => array(
					'Start with where the banner will hang and who will see it. ' . $prod('13oz-vinyl-banner', '13oz vinyl') . ' is the everyday choice for storefronts, sales and events, indoors or out. When both sides will be seen, or a light sits behind the banner, ' . $prod('18oz-blockout-banner', '18oz blockout vinyl') . ' stops show-through so each side reads cleanly. On fences, scaffolding and building sides, a ' . $prod('mesh-banner', 'mesh banner') . ' lets wind pass through its 70/30 perforation instead of catching it like a sail.',
					'For light boxes, the ' . $prod('backlit-banner', 'backlit banner') . ' lets light glow through the print. For close-up indoor viewing, the ' . $prod('indoor-banner-super-smooth', 'super smooth banner') . ' keeps photos and small text sharp, and fabric banners give photo backdrops a soft, glare-free finish.',
				)),
				array('h' => 'Compare banner materials', 'table' => array(
					array('Material', 'Best for', 'Sides', 'Largest single piece'),
					array('13oz matte vinyl', 'Everyday indoor and outdoor banners', 'Single or double', '10\' x 145\' (9.5\' x 145\' double sided)'),
					array('18oz blockout vinyl', 'Double sided, street and pole banners', 'Single or double', '10\' x 145\' (9.5\' x 145\' double sided)'),
					array('10oz mesh vinyl', 'Fences, scaffolding, windy sites', 'Single or double', '10\' x 145\' (9.5\' x 145\' double sided)'),
					array('15oz backlit vinyl', 'Light boxes', 'Single', '120" x 150\''),
					array('Super smooth PVC', 'Close-up indoor displays', 'Single', '52" x 100\''),
					array('Blockout fabric', 'Photo backdrops, no glare', 'Single', '8\' x 150\''),
					array('9oz wrinkle free fabric', 'Backdrops and tapestries', 'Single', '8\' x 100\''),
					array('Tension fabric', 'Frames and wraps you own', 'Single', '90" x 100\''),
				)),
				array('h' => 'Finishing and hanging', 'p' => array(
					'Vinyl banners include free hems, and you can add grommets, pole pockets, Velcro, webbing with D-rings, sewn-in rope, wind slits or reinforced corners. For street, long-term or very large banners, add webbing and reinforced corners and use every grommet when you hang it. Larger vinyl banners are welded from panels. Fabric banners are hemmed in white or black thread or cut flush. ' . $prod('pole-banner-set-banner-hardware', 'Light pole banners') . ' come as a set: double sided 18oz banners with 2" pole pockets and an optional fiberglass bracket kit.',
				)),
				array('h' => 'Artwork, production and shipping', 'p' => array(
					$artwork . ' Many banners ship the same business day when you order and approve artwork by 12 pm Pacific time; the rest ship the next business day when ordered by 4 pm, and each product page shows its own cut-off. 13oz and 18oz banners ship rolled up to 88" on the short side and folded from 89". Need a stand? See ' . $cat('banner-stands', 'banner stands') . '.',
				)),
			),
		),
	),

	'advertising-flags' => array(
		'lead' => 'Every flag is dye sublimated on 4 oz polyester and made to order. Buy the complete kit with a pole set, or the flag only if you already own the hardware; all flags ship nationwide.',
		'guide' => array(
			'title' => 'How to Choose an Advertising Flag',
			'sections' => array(
				array('h' => 'Pick a shape', 'p' => array(
					'Feather flags are tall and narrow, so they are easy to spot from the road. ' . $prod('feather-angled-flag-pole', 'Angled feather flags') . ' keep the whole graphic visible, ' . $prod('feather-convex-flag-pole', 'convex feather flags') . ' have a curved edge, and ' . $prod('teardrop-flag-pole', 'teardrop flags') . ' give a rounded, logo-friendly shape that looks clean from any angle. ' . $prod('rectangle-flag-pole', 'Rectangle flags') . ' offer the simplest design area for text and photos. On a budget, the 16 ft ' . $prod('econo-feather-flag-pole', 'Econo feather flag') . ' or a ready-made ' . $prod('econo-stock-flag-pole', 'stock design') . ' gets a flag outside fast.',
				)),
				array('h' => 'Compare flag sizes', 'table' => array(
					array('Flag', 'Sizes (assembled height)', 'Double sided'),
					array('Angled feather', '9 ft, 10.5 ft, 14 ft, 18 ft', 'Yes'),
					array('Convex feather', '9 ft, 10.5 ft, 14 ft, 18 ft', 'Yes'),
					array('Teardrop', '7 ft, 9 ft, 11.2 ft, 13.5 ft', 'Yes'),
					array('Rectangle', '8.5 ft, 11.8 ft, 15 ft', 'Yes'),
					array('Econo feather and stock', '16 ft', 'No, single sided'),
				)),
				array('h' => 'Single or double sided?', 'p' => array(
					'A single sided flag is printed through: full color on the front and a lighter, mirrored but readable image on the back. Choose the reverse version to put the pole sleeve on the right. A double sided flag is two prints sewn back to back with a block-out layer, so both sides read correctly; it is worth it when people pass from both directions.',
				)),
				array('h' => 'Choose a base', 'p' => array(
					'Use the ground stake for grass, sand or soil; its bearing lets the flag turn with the wind. For concrete, asphalt or indoor floors, use the cross base, add a water bag over it outdoors, or choose the heavy square base. Replacement poles, bases and carry bags are in ' . $cat('flag-hardware', 'flag hardware') . '.',
				)),
				array('h' => 'Production and shipping', 'p' => array(
					'Flags ship the next business day when you order and approve artwork by 4 pm Pacific time. Use the size template on each flag page so your design fits its shape.',
				)),
			),
		),
	),

	'flag-hardware' => array(
		'lead' => 'Replacement poles, bases and bags for the flags we print. These are stock items: order by 12 pm Pacific time and they ship the same business day, to all 50 states.',
		'guide' => array(
			'title' => 'Which Flag Hardware Do You Need?',
			'sections' => array(
				array('h' => 'Bases by surface', 'table' => array(
					array('Base', 'Surface', 'Weight'),
					array('Ground stake', 'Grass, sand and soil', '3.4 lb, with a spinning bearing'),
					array('Cross base', 'Concrete, asphalt and indoor floors', '6.73 lb, folding legs'),
					array('Cross base with water bag', 'Hard surfaces in wind', 'About 23 lb of water added'),
					array('Square base', 'Sidewalks and indoor floors', '22 lb, low and flat'),
					array('Econo ground spike', 'Soft ground, Econo flags only', '3.4 lb'),
				)),
				array('h' => 'Poles that match your flag', 'p' => array(
					'Feather flag pole sets come in four sizes (110", 134", 177" and 220") with flexible fiberglass tops on rigid aluminum bottoms. Rectangle pole sets are 24.5" wide in three heights, with a horizontal top pole that holds the flag straight. The Econo pole set fits only the 16 ft Econo flag. For a house or storefront wall flag, the 74.5" ' . $prod('flag-pole-and-bracket', 'flag pole and bracket') . ' has a tangle-free spinning top.',
				)),
				array('h' => 'Carry and store', 'p' => array(
					'The flag carrying bag comes in S/M and L/XL and holds the flag, poles, a ground stake and a folded cross base; the square base does not fit. Printed flags are in ' . $cat('advertising-flags', 'advertising flags') . '.',
				)),
			),
		),
	),

	'banner-stands' => array(
		'lead' => 'Choose a printed graphic with the stand, or the graphic only to refresh a stand you own. Graphics are printed to order; stands and hardware ship to all 50 states.',
		'guide' => array(
			'title' => 'How to Choose a Banner Stand',
			'sections' => array(
				array('h' => 'Compare stand types', 'table' => array(
					array('Stand', 'How it works', 'Best for'),
					array('Standard retractable', 'Graphic rolls into a light base with fold-out feet', 'Budget trade shows and traveling teams'),
					array('Deluxe retractable', 'Wide chrome-capped base, adjustable pole', 'Lobbies and frequent events'),
					array('SD retractable', 'Low, very stable base, hard travel case', 'Premium displays, wider graphics'),
					array('X-stand', 'Grommeted banner hooks onto an X frame', 'The lowest price and quick graphic swaps'),
					array('Tension fabric stand', 'Zip-on fabric over a tool-free frame', 'A seamless, double sided look'),
					array('Table top stand', 'Mini retractable, 11.5" x 17.5" graphic', 'Counters and trade show tables'),
					array('Step and repeat backdrop', 'Fabric on a telescoping frame, 6\' x 4\' to 10\' x 8\'', 'Photo walls and press backdrops'),
				)),
				array('h' => 'Retractable or X-stand?', 'p' => array(
					'A retractable stand protects the graphic inside its base and sets up in seconds, which matters if you travel often. An X-stand costs less and makes swapping banners easy, since the graphic simply hooks on by its grommets. If you want fabric that stays wrinkle free and can be printed on both sides at the same price, choose a ' . $prod('tension-fabric-stand-graphic-frame', 'tension fabric stand') . '.',
				)),
				array('h' => 'Design tips', 'p' => array(
					'Keep text and logos at least 1" from the top and 3" from the bottom of a retractable graphic, because the bottom sits inside the base. Use the template for your stand size. Optional LED lights clip to the top of the Standard, Deluxe and SD stands for dim halls.',
				)),
				array('h' => 'Production and shipping', 'p' => array(
					'Most retractable graphics ship the same business day when you order and approve artwork by 12 pm Pacific time; fabric stands and backdrops ship the next business day when ordered by 4 pm. Stand frames without a graphic are in ' . $cat('hardware-only', 'display hardware') . '.',
				)),
			),
		),
	),

	'hardware-only' => array(
		'lead' => 'Frames and stands without a printed graphic, for replacing worn hardware or adding stands for graphics you already have. In-stock items ship the same business day when ordered by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'Choosing Replacement Display Hardware',
			'sections' => array(
				array('h' => 'Match the hardware to your graphic', 'table' => array(
					array('Hardware', 'Takes', 'Includes'),
					array('Standard retractable', 'Standard retractable graphics', 'Base, pole, top rail, carry bag'),
					array('Deluxe retractable', '33" x 81" retractable graphics', 'Base, pole, top rail, carry bag'),
					array('SD retractable', '36", 48" or 60" wide graphics', 'Base, pole, top rail, carry case'),
					array('X-stand', '24" x 63" or 32" x 71" grommeted banners', 'Frame and carry bag'),
					array('Tension fabric stand', '36" x 90" or 48" x 90" pillowcase graphics', 'Tubes, base plate, carry bag'),
					array('Step and repeat frame', 'Graphics from 6\' x 4\' to 10\' x 8\'', 'Telescoping poles, feet, carry bag'),
				)),
				array('h' => 'Before you order', 'p' => array(
					'Measure the graphic you plan to reuse and match it to the hardware size above. Retractable graphics must fit the stand model they were made for. The banner stand LED light fits the Standard, Deluxe and SD stands. To order a new graphic with a stand, see ' . $cat('banner-stands', 'banner stands') . '.',
				)),
			),
		),
	),

	'banner-stand-hardware' => array(
		'lead' => 'Replacement hardware for our two most popular stands: the Standard retractable and the tension fabric stand. Order by 12 pm Pacific time and it ships the same business day.',
		'guide' => array(
			'title' => 'Which Replacement Stand Fits?',
			'sections' => array(
				array('h' => 'Standard retractable or tension fabric?', 'p' => array(
					'The ' . $prod('standard-retractable-hardware-only', 'Standard retractable hardware') . ' is an aluminum base with fold-out feet, a support pole and a top rail, packed in a carry bag. It takes the roll-up graphic from a Standard retractable stand.',
					'The ' . $prod('tension-fabric-stand-hardware-only', 'tension fabric stand frame') . ' is a set of tool-free aluminum tubes on a heavy base plate, 36" or 48" wide by 90" tall, for zip-on pillowcase graphics. Match the width to your graphic.',
				)),
				array('h' => 'Need more hardware or a new graphic?', 'p' => array(
					'Deluxe, SD and X-stand frames and the step and repeat frame are in ' . $cat('hardware-only', 'display hardware') . '. Printed graphics, with or without a stand, are in ' . $cat('banner-stands', 'banner stands') . '.',
				)),
			),
		),
	),

	'a-frame-and-sign-holders' => array(
		'lead' => 'Frames and holders that make changing a promotion fast, with the print included. Each is printed to order and ships nationwide; most ship the same business day when ordered by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'Choosing a Sign Holder',
			'sections' => array(
				array('h' => 'Compare the options', 'table' => array(
					array('Holder', 'Print', 'Where it goes'),
					array('Banner A-frame', '13oz vinyl, 45" x 33" or 94" x 33"', 'Sidewalks, events, sports fields'),
					array('Floor poster stand', 'Double sided 22" x 28" PVC', 'Store entrances and lobbies'),
					array('Snap poster hanger', '14 mil double white popup', 'Windows and ceilings'),
					array('Magnetic wood hanger', '17.5" wide popup poster', 'Walls, cafes and shops'),
				)),
				array('h' => 'Indoors or out?', 'p' => array(
					'The ' . $prod('banner-a-banner-frame', 'banner A-frame') . ' is the outdoor option: a tool-free aluminum frame holds a vinyl banner on one or both sides with hooked bungee cords. The ' . $prod('poster-graphic-stand', 'floor poster stand') . ' is a 60" matte black steel frame for indoor aisles and entrances. ' . $prod('snap-poster-graphic-hanger', 'Snap poster hangers') . ' grip a poster top and bottom and hang in windows or from ceilings, and ' . $prod('magnetic-wood-frame-hanger', 'magnetic wood hangers') . ' bring a natural look to walls.',
				)),
				array('h' => 'Changing prints', 'p' => array(
					'Every holder is sold with its print, and replacement prints are sold on their own, so you can change seasonal offers without buying new hardware. For heavy plastic sidewalk A-frames, see ' . $cat('signicade-a-frames', 'Signicade A-frames') . '.',
				)),
			),
		),
	),

	'signicade-a-frames' => array(
		'lead' => 'Genuine Plasticade sidewalk A-frames with your graphics printed in full color. Most ship the same business day when you order and approve artwork by 12 pm Pacific time, to all 50 states.',
		'guide' => array(
			'title' => 'Which Signicade Is Right for You?',
			'sections' => array(
				array('h' => 'Compare the models', 'table' => array(
					array('Model', 'Graphic size', 'Frame size and weight', 'How graphics change'),
					array('Standard Signicade', '24" x 36"', '46" x 25" x 3", 19 lb', 'Vinyl graphics mounted before shipping'),
					array('Deluxe Signicade', '24" x 36"', '46" x 27" x 3", 19 lb', 'Slide-in coroplast inserts'),
					array('SimpoSign II', '22" x 28"', '37" x 24" x 2", 10 lb', 'Slide-in coroplast inserts'),
				)),
				array('h' => 'Fixed graphics or quick changes?', 'p' => array(
					'Choose the ' . $prod('standard-signicade-white-frame-graphic', 'Standard Signicade') . ' when your message stays the same: the vinyl graphics are applied before it ships. Choose the ' . $prod('deluxe-signicade-graphic-frame', 'Deluxe Signicade') . ' or ' . $prod('simposign-ii-white-graphic-frame', 'SimpoSign II') . ' when you run changing promotions, since their rigid inserts slide in and out in seconds. Order one insert for the front only or two for both sides.',
				)),
				array('h' => 'Keeping it in place', 'p' => array(
					'Fill the hollow body with sand or water for weight, and bring the A-frame inside in very high wind. Need to write on it? See the ' . $prod('dry-erase-signicade-a-frame', 'dry erase Signicade') . '. For night visibility, see the ' . $prod('reflective-signicade-a-frame', 'reflective Signicade') . '.',
				)),
			),
		),
	),

	'real-estate-products' => array(
		'lead' => 'Reusable frames and posts with double sided listing panels, so you can swap a property, rider or status in seconds. Most ship the same business day when ordered by 12 pm Pacific time, to all 50 states.',
		'guide' => array(
			'title' => 'Choosing Real Estate Hardware',
			'sections' => array(
				array('h' => 'Compare the options', 'table' => array(
					array('Option', 'Panel', 'Ground'),
					array('Yard sign and H-stake', '24" x 18" double sided coroplast', 'Soft ground'),
					array('Real estate frame', '24" x 18" coroplast plus a 6" rider', 'Soft ground; legs act as stakes'),
					array('Real estate A-frame', '24" x 18" coroplast plus an optional rider', 'Lawns, sidewalks and driveways'),
					array('Real estate post', 'Double sided aluminum, up to 36" or 48"', 'Soft ground; hammer-in stake'),
				)),
				array('h' => 'Pick by how long it stays up', 'p' => array(
					'For open houses and short listings, a ' . $prod('yard-sign-and-h-stake', 'yard sign with an H-stake') . ' is the lowest cost option. The ' . $prod('real-estate-frame-sign', 'real estate frame') . ' and ' . $prod('real-estate-a-frame-sign', 'A-frame') . ' hold changeable panels and riders, and the A-frame stands on pavement as well as grass. For a long listing, the ' . $prod('real-estate-post-sign', 'PVC real estate post') . ' with aluminum sandwich board panels looks the most finished and lasts the longest.',
				)),
				array('h' => 'Design tips', 'p' => array(
					'Each side of a panel can carry different artwork. Keep text at least .75" from the edges on framed panels, because the frame covers the outer edge. For panels that show up at night, see ' . $cat('reflective-products', 'reflective products') . '.',
				)),
			),
		),
	),

	'rigid-signs-and-magnets' => array(
		'lead' => 'Flat printed boards for indoors and out, cut to size and printed in full color, plus magnets for vehicles. Most ship the same business day when you order and approve artwork by 12 pm Pacific time, to all 50 states.',
		'guide' => array(
			'title' => 'How to Choose a Rigid Board',
			'sections' => array(
				array('h' => 'Compare the materials', 'table' => array(
					array('Material', 'Thickness', 'Indoor or outdoor', 'Best for'),
					array('Aluminum', '.040"', 'Outdoor, long term', 'Parking, property and wall panels'),
					array('Aluminum sandwich board', '1/8" (3mm)', 'Outdoor, long term', 'Custom-size panels up to 48" x 96"'),
					array('Coroplast', '4mm', 'Outdoor, short to medium term', 'Yard, event and job site panels'),
					array('PVC board', '1/8"', 'Indoor, short term outdoor', 'Wall panels and frame inserts'),
					array('Styrene', '.020"', 'Indoor or outdoor', 'Drop-in inserts and posters'),
					array('Foam board', '3/16"', 'Indoor only', 'Presentations and easels'),
					array('GatorFoam', '3/16" or 1/2"', 'Indoor only', 'Large, dent-resistant displays'),
					array('Magnet', '30 mil', 'Outdoor on steel', 'Vehicle doors and trailers'),
				)),
				array('h' => 'Pick by how long it will last', 'p' => array(
					'For years outside, choose ' . $prod('aluminum-sign', 'aluminum') . ' or ' . $prod('aluminum-sandwich-board', 'aluminum sandwich board') . ': they will not rust or warp. For weeks or months outside, ' . $prod('coroplast', 'coroplast') . ' costs far less and takes an H-stake. Indoors, ' . $prod('foam-board', 'foam board') . ' is the lightest and ' . $prod('gatorfoam', 'GatorFoam') . ' resists dents on large prints.',
				)),
				array('h' => 'Size and shipping', 'p' => array(
					'Custom panels go up to 48" x 96", but panels larger than 46" x 38" (or 48" x 30") are too big to ship by parcel, so call 866-436-2101 before ordering one. ' . $artwork,
				)),
			),
		),
	),

	'reflective-products' => array(
		'lead' => 'Every product here is faced with engineer grade prismatic reflective vinyl, UV printed in full color, so it lights up in headlights with no power. Most ship the same business day when ordered by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'Choosing a Reflective Product',
			'sections' => array(
				array('h' => 'Compare reflective options', 'table' => array(
					array('Product', 'Base material', 'Lasts outdoors'),
					array('Reflective aluminum sign', '.040 aluminum, 3 sizes', 'Years'),
					array('Reflective aluminum sandwich board', '1/8" aluminum composite, custom size', 'Years'),
					array('Reflective PVC board', '1/8" PVC', 'Short term outdoors'),
					array('Reflective coroplast', '4mm corrugated plastic', 'Weeks to months'),
					array('Reflective car magnet', '30 mil magnet', 'Moves between vehicles'),
					array('Reflective adhesive vinyl', 'Film you apply yourself', 'Years on a flat surface'),
					array('Reflective Signicade', 'Plasticade sidewalk A-frame', 'Daily outdoor use'),
				)),
				array('h' => 'How reflective vinyl works', 'p' => array(
					'Prismatic reflective film bounces light from headlights or flashlights straight back toward its source, so drivers see your message glow after dark. That makes it the right choice for parking, driveway, address, safety and directional graphics that must be read at night.',
				)),
				array('h' => 'Pick the right base', 'p' => array(
					'For permanent outdoor jobs, choose the ' . $prod('reflective-aluminum-sign', 'reflective aluminum sign') . ' or ' . $prod('reflective-aluminum-sandwich-board', 'sandwich board') . '. For temporary jobs, ' . $prod('reflective-coroplast-sign-hstake', 'reflective coroplast') . ' costs less and takes an H-stake. To apply reflective graphics to your own surfaces, use ' . $prod('reflective-adhesive-vinyl', 'reflective adhesive vinyl') . '. For lit signs on your building, see our ' . $page('storefront-signs', 'storefront signs guide') . '.',
				)),
			),
		),
	),

	'dry-erase-products' => array(
		'lead' => 'Your logo and layout printed in full color, sealed with a gloss dry erase coat, so you can write and wipe around them every day. Most ship the same business day when ordered by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'Choosing a Dry Erase Product',
			'sections' => array(
				array('h' => 'Compare dry erase options', 'table' => array(
					array('Product', 'Indoor or outdoor', 'Best for'),
					array('Dry erase adhesive vinyl', 'Both', 'Turning walls, doors and boards into whiteboards'),
					array('Dry erase aluminum board', 'Both, long term', 'Daily specials and schedules'),
					array('Dry erase PVC board', 'Indoor, short term outdoor', 'Wall boards and frame inserts'),
					array('Dry erase coroplast', 'Both', 'Open house times and prices'),
					array('Dry erase foamcore', 'Indoor only', 'Planning and presentation boards'),
					array('Dry erase magnet', 'Both, on steel', 'Fridges, cabinets and vehicles'),
					array('Dry erase Signicade', 'Outdoor sidewalk', 'Changing specials every day'),
				)),
				array('h' => 'Plan your layout', 'p' => array(
					'Print the parts that never change, such as your logo, headings, lines or a grid, and leave open space for what you will write in. Restaurants print a menu frame and write the day&rsquo;s specials; offices print a weekly calendar; job sites print a status board.',
				)),
				array('h' => 'Writing and cleaning', 'p' => array(
					'Use standard dry erase markers and wipe them off with a dry eraser or soft cloth. Avoid permanent markers, abrasive pads and solvents, which can damage the coating. For a sidewalk board you can write on, the ' . $prod('dry-erase-signicade-a-frame', 'dry erase Signicade') . ' arrives with its faces already installed.',
				)),
			),
		),
	),

	'seg-products' => array(
		'lead' => 'SEG displays use a fabric print with a sewn silicone edge that presses into an aluminum frame for a tight, seamless face. Graphics ship the same business day when you order and approve artwork by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'How to Choose an SEG Display',
			'sections' => array(
				array('h' => 'Compare SEG displays', 'table' => array(
					array('Display', 'Size (W x H)', 'Lit'),
					array('3ft SEG fabric stand', '33" x 79"', 'No'),
					array('8ft SEG backlit popup', '88" x 88"', 'Yes, LED'),
					array('10ft SEG fabric display', '118" x 89"', 'No'),
					array('10ft SEG backlit popup', '117" x 88"', 'Yes, LED'),
					array('10ft SEG backlit fabric display', '118" x 88"', 'Yes, LED'),
					array('20ft SEG fabric display', '236" x 89"', 'No'),
					array('Custom SEG fabric', 'Up to 40\' x 10\'', 'For your own frame'),
				)),
				array('h' => 'Lit or unlit?', 'p' => array(
					'Backlit SEG displays have LED lights inside the frame that shine through the fabric, so colors glow even in a dark exhibit hall. Single sided backlit displays include blackout fabric for the back. Unlit SEG displays cost less and suit well lit venues, lobbies and photo walls.',
				)),
				array('h' => 'Graphic only or with frame', 'p' => array(
					'Order the frame and graphic together the first time, then order the graphic only for each new show and reuse the hardware. ' . $prod('custom-seg-fabric', 'Custom SEG fabric') . ' fits frames you already own; its silicone edge measures 0.44" x 0.135" with the fabric. For tube-frame and pop up booths, see ' . $cat('trade-show-products', 'trade show displays') . '.',
				)),
			),
		),
	),

	'trade-show-products' => array(
		'lead' => 'Fabric backwalls for booths, events and photo backdrops, with or without the frame. Graphics ship the next business day when you order and approve artwork by 4 pm Pacific time.',
		'guide' => array(
			'title' => 'Tension Fabric or Pop Up Display?',
			'sections' => array(
				array('h' => 'Compare the two systems', 'table' => array(
					array('', 'Tension fabric display', 'Velcro pop up display'),
					array('Frame', 'Numbered tool-free tubes', 'Accordion frame that opens in one motion'),
					array('Graphic', 'Zip-on pillowcase, single or double sided', 'Sewn-on Velcro panels that wrap the ends'),
					array('Shapes', 'Straight (8, 10, 20 ft) or curved (6, 8, 10 ft)', 'Straight or curved (8 or 10 ft)'),
					array('Carrying', 'Soft bag included; hard case optional', 'Soft bag included; hard case optional'),
				)),
				array('h' => 'Straight or curved?', 'p' => array(
					'A straight wall gives you the most flat design area for a 10 x 10 or 10 x 20 booth. A curved wall frames the people standing in front of it and looks more finished from the aisle. The 6 ft curved tension display is a tabletop backdrop for small booths.',
				)),
				array('h' => 'Finish the booth', 'p' => array(
					'The optional hard case doubles as a podium (37" W x 37" H x 20" D) with a printed wrap. Add ' . $cat('table-throws', 'custom table throws') . ' and ' . $cat('banner-stands', 'banner stands') . ' for a complete booth, or choose ' . $cat('seg-products', 'SEG displays') . ' for a frameless look.',
				)),
			),
		),
	),

	'custom-event-tents' => array(
		'lead' => 'A 10x10 tent printed seam to seam on waterproof coated polyester, on a heavy duty 40 mm hex aluminum frame. Graphics ship the next business day when you order and approve artwork by 4 pm Pacific time.',
		'guide' => array(
			'title' => 'Building Your Event Tent',
			'sections' => array(
				array('h' => 'What comes with the tent', 'list' => array(
					'A dye sublimated canopy with artwork on all four peaks and valances; each side can be different.',
					'A 40 mm hex aluminum frame with telescopic legs from 124.5" to 137" tall.',
					'Four ropes, four stakes and a standard carry bag. The full package weighs 51 lb.',
				)),
				array('h' => 'Add walls and flags', 'table' => array(
					array('Add-on', 'Size', 'Notes'),
					array('Full wall', '113" x 78"', 'Single sided; needs Velcro on that side of the canopy'),
					array('Half wall', '113" x 39"', 'Single or double sided; uses half wall hardware'),
					array('Tent flag', 'Small or medium feather or teardrop', 'Clamps to a tent leg'),
				)),
				array('h' => 'Keep it safe in wind', 'p' => array(
					'Stake every leg on grass, or use the 4 piece sandbag set on pavement: each bag holds up to 19 lb of sand. Take the tent down in high wind, and never pack the canopy wet. Frames, sandbags and the wheeled bag are in ' . $cat('event-tent-hardware-only', 'event tent hardware') . '.',
				)),
			),
		),
	),

	'event-tent-hardware-only' => array(
		'lead' => 'Frames and accessories for our 10x10 event tents. These are stock items: order by 12 pm Pacific time and they ship the same business day, to all 50 states.',
		'guide' => array(
			'title' => 'Event Tent Hardware Guide',
			'sections' => array(
				array('h' => 'What each part does', 'table' => array(
					array('Part', 'What it does'),
					array('Event tent frame', '10x10 hex aluminum frame, 124.5" to 137" tall, with ropes, stakes and a bag'),
					array('Sandbag set', 'Four bags, one per leg, up to 19 lb of sand each'),
					array('Half wall hardware', 'Telescopic pole and two hex clamps that hold a half wall straight'),
					array('Flag holder', 'Adapter and two hex clamps for a small or medium feather or teardrop flag'),
					array('Wheeled carry bag', 'Rolls the frame on casters, with a compartment for graphics'),
				)),
				array('h' => 'Compatibility', 'p' => array(
					'The clamps and accessories fit our 40 mm hex aluminum tent frame. The wheeled bag&rsquo;s graphics compartment holds one canopy top, or one full wall and two half walls. Printed canopies, walls and tent flags are in ' . $cat('custom-event-tents', 'custom event tents') . '.',
				)),
			),
		),
	),

	'table-throws' => array(
		'lead' => 'Washable polyester covers that turn a folding table into part of your booth, printed edge to edge by dye sublimation. Printed covers ship the next business day when ordered by 4 pm Pacific time.',
		'guide' => array(
			'title' => 'How to Choose a Table Cover',
			'sections' => array(
				array('h' => 'Match the cover to your table', 'table' => array(
					array('Cover', 'Fits', 'Style'),
					array('4ft table cover', '48" x 30" x 30" table', 'Draped, open or closed back'),
					array('6ft table cover', '72" x 30" x 30" table', 'Draped, open or closed back'),
					array('8ft table cover', '96" x 30" x 30" table', 'Draped, open or closed back'),
					array('Stretch table cover', '6 ft or 8 ft folding table', 'Fitted, with leg loops'),
					array('Round table cover', '31.5" to 60" round, plus bar height', 'Draped square'),
					array('Table runner', '24" to 30" wide, 72" to 90" long', 'Lays over a plain throw'),
				)),
				array('h' => 'Open back or closed back?', 'p' => array(
					'A 3 sided open back cover leaves room for your legs when you sit behind the table. A 4 sided closed back cover hides everything under the table when it is seen from all sides. Draped covers hang about 2" above the floor.',
				)),
				array('h' => 'Budget options', 'p' => array(
					'A ' . $prod('table-runner', 'printed table runner') . ' over a ' . $prod('solid-color-table-throws', 'solid color throw') . ' is the most economical way to brand a table. For a modern, fitted look, choose a ' . $prod('stretch-table-cover', 'stretch table cover') . '. Pair any cover with ' . $cat('trade-show-products', 'trade show displays') . ' for a complete booth.',
				)),
			),
		),
	),

	'wall-art' => array(
		'lead' => 'Photos, logos and artwork printed as finished wall pieces, most arriving ready to hang. Most ship the same business day when you order and approve artwork by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'Choosing Wall Art',
			'sections' => array(
				array('h' => 'Compare the formats', 'table' => array(
					array('Format', 'Sizes', 'Look'),
					array('Canvas wrap', 'Custom', 'Gallery canvas wrapped on MDF bars, ready to hang'),
					array('Framed canvas', '16" x 16" to 24" x 36"', 'Floating frame in black, white or pecan'),
					array('Acrylic print', '16" x 24", 20" x 30", 24" x 36"', 'Glossy, floats on chrome standoffs'),
					array('Framed poster', '16" x 24" print, 23" x 31" framed', 'Black frame with mat and glazing'),
					array('Framed print', '16" x 20" to 24" x 32"', 'Light wood-look frame'),
					array('Canvas roll', 'Up to 58" x 100\'', 'Unstretched, for your own framing'),
				)),
				array('h' => 'Pick by the room', 'p' => array(
					'For a modern office or lobby, ' . $prod('acrylic-prints', 'acrylic prints') . ' give images a deep, glossy look. ' . $prod('canvas-wrap', 'Canvas wraps') . ' and ' . $prod('framed-canvas', 'framed canvas') . ' suit restaurants, hotels and homes. Framed posters and prints are the classic choice for certificates, menus and art.',
				)),
				array('h' => 'Artwork tips', 'p' => array(
					'Use the highest resolution photo you have. A 1.5" gallery wrap with a folded edge needs 1.75" of extra image on each side, and framed prints need text kept 1" from every edge. ' . $artwork,
				)),
			),
		),
	),

	'adhesive-products' => array(
		'lead' => 'Every adhesive vinyl here is printed to the size you enter and priced per square foot, up to 52" high. They ship the same business day when you order and approve artwork by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'How to Choose Adhesive Vinyl',
			'sections' => array(
				array('h' => 'Compare adhesive vinyl', 'table' => array(
					array('Vinyl', 'Use it for', 'Removable'),
					array('Matte adhesive vinyl (AV 1)', 'Decals, doors, walls, equipment', 'No, permanent'),
					array('High performance vinyl (AV 2)', 'Bubble free wall and panel graphics', 'Yes'),
					array('Translucent vinyl (AV 3)', 'Light boxes and lit faces', 'No, permanent'),
					array('Frosted vinyl (AV 4)', 'Privacy glass, etched look', 'No'),
					array('Clear vinyl (AV 5)', 'Window decals that let light through', 'No, permanent'),
					array('Window perf (AV 6)', 'One-way vision storefront and vehicle windows', 'No'),
					array('Window cling (AV 7)', 'Seasonal promotions on glass', 'Yes, reusable'),
					array('Wall fabric (AV 8)', 'Wall murals on painted walls', 'Yes, repositionable'),
					array('Floor graphics (AV 10)', 'Indoor floors, non-slip', 'No'),
					array('3M vehicle wrap (AV 11)', 'Vehicle wraps and curved surfaces', 'Yes, long term'),
				)),
				array('h' => 'Windows: perf, clear, frosted or cling?', 'p' => array(
					'Window perf covers the whole glass while you still see out from inside. Clear vinyl shows only your design, frosted vinyl adds privacy with an etched look, and a window cling peels off clean for short promotions. Our ' . $page('window-graphics', 'window graphics guide') . ' compares them in detail.',
				)),
				array('h' => 'Installation tips', 'p' => array(
					'Apply vinyl to a clean, dry, smooth surface. Graphics wider than one panel are printed in panels with an overlap. We print and ship; your installer or your team applies them. Reflective and dry erase vinyl are also available.',
				)),
			),
		),
	),

	'large-format' => array(
		'lead' => 'Wide format prints for windows, walls, floors, light boxes and vehicles, priced per square foot so you can size a print and see its cost before you order. Most ship the same business day when ordered by 12 pm Pacific time.',
		'guide' => array(
			'title' => 'Which Large Format Print Do You Need?',
			'sections' => array(
				array('h' => 'Start with where it goes', 'table' => array(
					array('Where', 'Print', 'Category'),
					array('Windows and glass', 'Window perf, clear, frosted vinyl or clings', 'Printed adhesive vinyl'),
					array('Walls and doors', 'Adhesive vinyl, wall fabric murals', 'Printed adhesive vinyl'),
					array('Floors', 'Non-slip floor graphics', 'Printed adhesive vinyl'),
					array('Light boxes', 'Backlit film or translucent vinyl', 'Large format'),
					array('Frames and holders', 'Posters, popup and styrene inserts', 'Large format'),
					array('Decor', 'Canvas, acrylic and framed prints', 'Wall art'),
				)),
				array('h' => 'Posters and inserts', 'p' => array(
					$prod('posters', 'Posters') . ' and ' . $prod('popup', 'popup prints') . ' are UV printed on 14 mil double white PVC that won&rsquo;t tear like paper and lays flat after shipping rolled. ' . $prod('backlit-film', 'Backlit film') . ' is a heavy 10 mil translucent film for menu boards and light boxes. For apparel, ' . $prod('dtf', 'DTF transfers') . ' heat press onto cotton, polyester, hats and bags.',
				)),
				array('h' => 'Browse by type', 'p' => array(
					'See ' . $cat('adhesive-products', 'printed adhesive vinyl') . ' for decals, windows, walls and floors, ' . $cat('wall-art', 'wall art') . ' for canvas and framed prints, ' . $cat('rigid-signs-and-magnets', 'rigid boards and magnets') . ' for mounted prints, and ' . $cat('reflective-products', 'reflective') . ' and ' . $cat('dry-erase-products', 'dry erase') . ' products for special finishes.',
				)),
			),
		),
	),

	'indoor-outdoor-displays' => array(
		'lead' => 'Everything for events, trade shows and retail floors in one place, from flags and tents to booth backwalls and table covers. All displays are made to order and ship nationwide.',
		'guide' => array(
			'title' => 'Plan Your Display',
			'sections' => array(
				array('h' => 'Pick by where it goes', 'table' => array(
					array('Setting', 'Good choices'),
					array('Roadside and storefront', $cat('advertising-flags', 'Feather and teardrop flags') . ', ' . $cat('signicade-a-frames', 'sidewalk A-frames')),
					array('Trade show booth', $cat('trade-show-products', 'Fabric backwalls') . ', ' . $cat('seg-products', 'SEG displays') . ', ' . $cat('table-throws', 'table covers')),
					array('Lobby and retail floor', $cat('banner-stands', 'Banner stands') . ', ' . $cat('a-frame-and-sign-holders', 'poster stands and holders')),
					array('Outdoor events and markets', $cat('custom-event-tents', 'Event tents') . ', flags, banner A-frames'),
					array('Real estate', $cat('real-estate-products', 'Frames, posts and yard signs')),
				)),
				array('h' => 'Build a booth that works together', 'p' => array(
					'Use the same artwork across a backwall, table cover and banner stand so your booth reads as one brand from the aisle. Add a flag outside the venue or at your tent, and keep spare graphics so you can reuse the hardware at the next event.',
				)),
				array('h' => 'Hardware and replacements', 'p' => array(
					'Most displays are sold as a complete kit or as the graphic only. Replacement poles, bases, frames and stands are in ' . $cat('flag-hardware', 'flag hardware') . ', ' . $cat('hardware-only', 'display hardware') . ' and ' . $cat('event-tent-hardware-only', 'event tent hardware') . '.',
				)),
			),
		),
	),
);
