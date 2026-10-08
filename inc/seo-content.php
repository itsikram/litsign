<?php
/**
 * Keyword-targeted titles, descriptions, headings and intro copy for the
 * product category routes, product variants and informational pages.
 *
 * Each page owns one primary keyword so pages don't compete with each other.
 * Titles stay under 60 characters and descriptions under 155.
 *
 * @package litsign
 */

/**
 * SEO copy for the /{category}/ routes. Keys are product_category slugs.
 * 'intro' may contain links; it is printed with wp_kses_post().
 *
 * @return array
 */
function wholesale_seo_category_meta()
{
	$link = static function ($slug, $text) {
		return '<a href="' . esc_url(wholesale_category_url($slug)) . '">' . esc_html($text) . '</a>';
	};
	$storefront = '<a href="' . esc_url(home_url('/storefront-signs/')) . '">storefront signs</a>';

	return array(
		'banners' => array(
			'title' => 'Custom Vinyl Banners | 13oz, 18oz, Mesh & Fabric Banners',
			'description' => 'Custom printed banners: 13oz and 18oz blockout vinyl, mesh, backlit, indoor and wrinkle-free fabric banners, plus pole banner sets. Priced per square foot.',
			'h1' => 'Custom Printed Banners',
			'intro' => 'Full color banners for storefronts, grand openings, events and trade shows. Choose 13oz vinyl for everyday outdoor use, 18oz blockout when both sides are seen, mesh for fences and windy locations, backlit banner for lightboxes, or wrinkle-free fabric for indoor displays. Pick a material, enter your size and see your price before you order. Need a stand? See our ' . $link('banner-stands', 'banner stands') . '.',
		),
		'advertising-flags' => array(
			'title' => 'Custom Feather Flags & Advertising Flags | Printed Flags',
			'description' => 'Custom printed feather, teardrop, rectangle and pole flags for storefronts and events. Order the flag only or with a pole set and see your price online.',
			'h1' => 'Custom Advertising Flags',
			'intro' => 'Advertising flags catch drivers and foot traffic from a distance. Choose angled or convex feather flags, teardrop flags, rectangle flags, economy stock flags or custom pole flags, in the shape and size that fits your space. Order the printed flag only, or add a pole set; replacement poles and bases are in ' . $link('flag-hardware', 'flag hardware') . '.',
		),
		'banner-stands' => array(
			'title' => 'Retractable Banner Stands | Custom Printed Banner Stands',
			'description' => 'Retractable banner stands, X-stands, tension fabric and table top stands, and step and repeat backdrops in full color. Graphic only or with hardware.',
			'h1' => 'Custom Banner Stands',
			'intro' => 'Indoor vertical displays for in-store advertising, lobbies and traveling exhibitors. Retractable stands roll the graphic into the base for fast setup, X-stands are the lightweight budget option, and tension fabric stands and step and repeat backdrops give a wrinkle-free fabric look. Order a printed graphic on its own or with the hardware, or shop ' . $link('hardware-only', 'hardware only') . ' to reuse a stand you already have.',
		),
		'a-frame-and-sign-holders' => array(
			'title' => 'A-Frame Signs & Sign Holders | Poster Stands & Hangers',
			'description' => 'Banner A-frames, poster stands, snap poster hangers and magnetic wood frame hangers with custom prints. Display systems for banners, signs and posters.',
			'h1' => 'A-Frame Signs & Sign Holders',
			'intro' => 'Display systems for banners, signs and posters, inside the store or on the sidewalk. Banner A-frames hold a printed banner on both sides, poster stands and snap hangers make it easy to swap promotions, and magnetic wood frame hangers suit posters and prints. For rugged plastic sidewalk signs, see ' . $link('signicade-a-frames', 'Signicade A-frames') . '.',
		),
		'signicade-a-frames' => array(
			'title' => 'Signicade A-Frame Signs | Custom Sidewalk Signs',
			'description' => 'Plasticade Signicade, Deluxe Signicade and Simposign II sidewalk A-frames with full color UV printed graphics. Order the graphic only or with the frame.',
			'h1' => 'Signicade A-Frame Sidewalk Signs',
			'intro' => 'Genuine Plasticade A-frames are a sidewalk sign staple for restaurants, salons and retail stores. Choose the Standard Signicade, the heavier Deluxe Signicade or the Simposign II, printed in full color with UV prints. Order the complete sign or replacement graphics for a frame you already own. Want a sign you can write on? See ' . $link('dry-erase-products', 'dry erase signs') . '.',
		),
		'real-estate-products' => array(
			'title' => 'Real Estate Signs | Yard Signs, Frames & Post Signs',
			'description' => 'Custom real estate signs: yard signs with H-stakes, real estate frames, post signs and A-frames. Reusable outdoor signage printed in full color.',
			'h1' => 'Real Estate Signs',
			'intro' => 'Outdoor signage for listings and open houses, designed to be long lasting and reusable. Choose yard signs with H-stakes, real estate frames, post signs or A-frames, and order the complete sign or the printed sign only to refresh a frame you already have. For reflective options that stand out at night, see ' . $link('reflective-products', 'reflective signs') . '.',
		),
		'seg-products' => array(
			'title' => 'SEG Fabric Displays | Silicone Edge Graphic Frames',
			'description' => 'SEG fabric displays and backlit SEG popups from 3ft to 20ft. Silicone edged fabric graphics slot into aluminum frames. Order graphic only or with frame.',
			'h1' => 'SEG Fabric Displays',
			'intro' => 'Silicone edge graphics (SEG) are printed fabric panels with a thin silicone strip sewn around the edge that presses into a slot in an aluminum frame, giving a tight, frameless look. Choose non-lit or backlit SEG displays from 3ft stands to 20ft backwalls, and order the graphic only or with the frame. For complete booth kits, see ' . $link('trade-show-products', 'trade show displays') . '.',
		),
		'trade-show-products' => array(
			'title' => 'Trade Show Displays | Fabric Pop Up & Tension Displays',
			'description' => 'Straight and curved tension fabric displays and Velcro fabric pop up displays for trade show booths and backdrops. Printed graphics, with or without frame.',
			'h1' => 'Trade Show Displays',
			'intro' => 'Fabric backwalls for trade show booths, events and photo backdrops. Tension fabric displays use a pillowcase-style graphic that zips over a tube frame, and Velcro pop up displays attach fabric panels to a collapsible frame. Both come straight or curved and can be ordered with or without the frame. Finish the booth with ' . $link('table-throws', 'custom table throws') . ' and ' . $link('banner-stands', 'banner stands') . '.',
		),
		'custom-event-tents' => array(
			'title' => 'Custom Event Tents | Printed 10x10 Canopy Tents & Walls',
			'description' => 'Full color 10x10 custom event tents with heavy duty aluminum hex frames, plus printed full and half tent walls and tent flags for outdoor events.',
			'h1' => 'Custom Event Tents',
			'intro' => 'Branded 10x10 canopy tents for farmers markets, festivals, sports and outdoor promotions. Order the full color canopy with a heavy duty aluminum hex frame or the graphic only, then add printed full or half walls and a tent flag. Frames, sandbags and carrying bags are in ' . $link('event-tent-hardware-only', 'event tent hardware') . '.',
		),
		'table-throws' => array(
			'title' => 'Custom Table Throws | Printed Table Covers & Runners',
			'description' => 'Full color dye sublimated table covers for 4ft, 6ft and 8ft and round tables, plus stretch covers, solid color throws and table runners for events.',
			'h1' => 'Custom Table Throws & Covers',
			'intro' => 'Full color dye sublimated polyester table covers that turn a plain folding table into part of your booth. Choose fitted covers for 4ft, 6ft or 8ft tables, round covers, stretch covers, solid color throws or a printed table runner. Pair them with ' . $link('trade-show-products', 'trade show displays') . ' for a complete booth.',
		),
		'hardware-only' => array(
			'title' => 'Banner Stand & Display Hardware | Replacement Stands',
			'description' => 'Hardware only for retractable banner stands, X-stands, tension fabric stands and step and repeat backdrops. Graphics are not included.',
			'h1' => 'Display Hardware Only',
			'intro' => 'Stands and frames without a printed graphic, for replacing worn hardware or adding stands to use with graphics you already have. Printed graphics are sold separately under ' . $link('banner-stands', 'banner stands') . '.',
		),
		'banner-stand-hardware' => array(
			'title' => 'Banner Stand Hardware | Retractable & Fabric Stands',
			'description' => 'Replacement hardware for standard retractable banner stands and tension fabric stands. Graphic not included.',
			'h1' => 'Banner Stand Hardware',
			'intro' => 'Replacement retractable and tension fabric stand hardware. Graphics are not included; order printed graphics under ' . $link('banner-stands', 'banner stands') . '.',
		),
		'flag-hardware' => array(
			'title' => 'Flag Poles & Bases | Feather Flag Hardware',
			'description' => 'Feather and rectangle flag pole sets, cross and square bases, ground stakes, spikes, water bags and carrying bags. Graphic not included.',
			'h1' => 'Flag Poles, Bases & Hardware',
			'intro' => 'Poles and bases for feather, rectangle and economy flags: cross bases and water bags for hard surfaces, ground stakes and spikes for grass. Graphics are not included; order printed flags under ' . $link('advertising-flags', 'advertising flags') . '.',
		),
		'event-tent-hardware-only' => array(
			'title' => 'Event Tent Frames & Hardware | Tent Accessories',
			'description' => 'Event tent frames, half wall hardware, flag holders, sandbag sets and wheeled carrying bags for custom event tents. Graphics not included.',
			'h1' => 'Event Tent Hardware',
			'intro' => 'Frames and accessories for 10x10 event tents: frame only, half wall hardware, flag holders, sandbag sets and wheeled carrying bags. Printed canopies and walls are under ' . $link('custom-event-tents', 'custom event tents') . '.',
		),
		'large-format' => array(
			'title' => 'Large Format Printing | Adhesive Vinyl, Posters & Film',
			'description' => 'Large format prints: adhesive vinyl, window perf and clings, floor graphics, vehicle wrap vinyl, backlit film, posters, canvas and acrylic prints.',
			'h1' => 'Large Format Printing',
			'intro' => 'Wide format prints for windows, walls, floors, vehicles and displays. Shop ' . $link('adhesive-products', 'printed adhesive vinyl') . ' and window graphics, backlit film for lightboxes, posters and styrene, and ' . $link('wall-art', 'canvas and acrylic wall art') . '. Prices are shown per square foot, so you can size your print and see the cost before you order.',
		),
		'adhesive-products' => array(
			'title' => 'Printed Adhesive Vinyl | Window, Wall & Floor Graphics',
			'description' => 'Custom printed adhesive vinyl: standard and high performance vinyl, window perf, clear and frosted film, clings, floor graphics and vehicle wrap.',
			'h1' => 'Printed Adhesive Vinyl & Window Graphics',
			'intro' => 'Printed adhesive vinyl for storefront windows, walls, floors and vehicles, priced per square foot. Use standard or high performance vinyl for decals and wall graphics, window perf for see-through window displays, clear, translucent or frosted vinyl for glass, removable window clings for short promotions, and floor graphics or 3M vehicle wrap film for specialty jobs. Window graphics pair well with lit ' . $storefront . '.',
		),
		'wall-art' => array(
			'title' => 'Custom Wall Art | Canvas, Acrylic & Framed Prints',
			'description' => 'Turn photos and artwork into wall art: canvas wraps and rolls, framed canvas, acrylic prints, framed posters and prints, and magnetic wood hangers.',
			'h1' => 'Custom Wall Art Prints',
			'intro' => 'Decor for offices, lobbies, restaurants and homes, printed from your photos or artwork. Choose gallery canvas wraps, canvas rolls, framed canvas, acrylic prints, framed posters and prints, or magnetic wood frame hangers.',
		),
		'rigid-signs-and-magnets' => array(
			'title' => 'Rigid Signs & Magnets | Aluminum, Coroplast & PVC Signs',
			'description' => 'Custom printed rigid signs: aluminum, Coroplast, foam board, GatorFoam, PVC, styrene and aluminum sandwich boards, plus custom magnets.',
			'h1' => 'Rigid Signs & Magnets',
			'intro' => 'Flat printed signs for indoors and out. Aluminum and PVC suit long-term outdoor signs, Coroplast is the lightweight choice for yard and event signs, foam board and GatorFoam work well for indoor displays and presentations, and aluminum sandwich boards make durable sidewalk signs. Custom magnets turn a vehicle into advertising.',
		),
		'reflective-products' => array(
			'title' => 'Reflective Signs | Reflective Vinyl, Aluminum & Magnets',
			'description' => 'Reflective signs that stand out at night: reflective aluminum signs, Coroplast, PVC, sandwich boards, A-frames, adhesive vinyl and car magnets.',
			'h1' => 'Reflective Signs',
			'intro' => 'Reflective material bounces headlight glare back toward drivers, so these signs stay visible after dark without power. Choose reflective aluminum, Coroplast, PVC, sandwich boards, Signicade A-frames, adhesive vinyl or car magnets. For lit signage on your building, see our ' . $storefront . '.',
		),
		'dry-erase-products' => array(
			'title' => 'Dry Erase Signs | Printed Whiteboard Signs & Vinyl',
			'description' => 'Printed dry erase signs: dry erase adhesive vinyl, Coroplast, foamcore, PVC, magnets, aluminum sandwich boards and Signicade A-frames.',
			'h1' => 'Dry Erase Signs',
			'intro' => 'Printed signs with a writable dry erase surface for daily specials, schedules and wait times. Choose adhesive vinyl, Coroplast, foamcore, PVC, magnets, aluminum sandwich boards or Signicade A-frames with your logo and layout printed in full color.',
		),
		'indoor-outdoor-displays' => array(
			'title' => 'Indoor & Outdoor Displays | Flags, Stands & Booths',
			'description' => 'Event and retail displays: advertising flags, banner stands, A-frames, SEG and trade show displays, event tents, table throws and real estate signs.',
			'h1' => 'Indoor & Outdoor Displays',
			'intro' => 'Displays for events, trade shows and retail spaces. Browse ' . $link('advertising-flags', 'advertising flags') . ', ' . $link('banner-stands', 'banner stands') . ', ' . $link('signicade-a-frames', 'A-frames') . ', ' . $link('trade-show-products', 'trade show displays') . ', ' . $link('custom-event-tents', 'event tents') . ' and ' . $link('table-throws', 'table throws') . '.',
		),
	);
}

/**
 * Buyer questions for a /{category}/ page, shown under the products and
 * described as FAQPage data in the head. Answers use only facts stated
 * elsewhere on the site (category intros, product pages, shipping terms).
 *
 * @return array List of array('q' => question, 'a' => answer HTML), or empty.
 */
function wholesale_seo_category_faq($slug)
{
	$link = static function ($slug, $text) {
		return '<a href="' . esc_url(wholesale_category_url($slug)) . '">' . esc_html($text) . '</a>';
	};
	$shipping = array(
		'q' => 'How fast will my order ship?',
		'a' => 'Every order is made to order. Your estimated ship date is shown at checkout, and after production you can choose standard (3&ndash;6 business days), 3-day, 2-day or overnight shipping. Pickup isn&rsquo;t available; every order ships to you.',
	);
	$help = array(
		'q' => 'Can someone help me choose?',
		'a' => 'Yes. Call a sign specialist at <a href="tel:+18664362101">866-436-2101</a>, Monday to Friday, 8am&ndash;5pm PST, or <a href="' . esc_url(home_url('/contact/')) . '">send us your question</a>.',
	);
	$per_sqft = array(
		'q' => 'How is the price calculated?',
		'a' => 'These products are priced per square foot. Enter your width and height on the product page and your price updates before you add it to the cart, so you know the cost before you order.',
	);

	$faqs = array(
		'banners' => array(
			array(
				'q' => 'Which banner material should I choose?',
				'a' => 'Use 13oz vinyl for everyday outdoor banners, 18oz blockout when both sides will be seen, mesh for fences and windy locations, backlit banner for lightboxes, and wrinkle-free fabric for indoor displays.',
			),
			$per_sqft,
			array(
				'q' => 'Do you make banners for light poles?',
				'a' => 'Yes. Pole banner sets come as the banner only, the banner with hardware, or the hardware only, so you can replace worn banners and keep your brackets.',
			),
			array(
				'q' => 'How do I display a banner indoors?',
				'a' => 'Pair a printed banner with a ' . $link('banner-stands', 'banner stand') . ', or use a banner A-frame from ' . $link('a-frame-and-sign-holders', 'A-frames and sign holders') . ' to show it on both sides of the sidewalk.',
			),
			$shipping,
		),
		'advertising-flags' => array(
			array(
				'q' => 'Which flag shape should I choose?',
				'a' => 'Choose angled or convex feather flags, teardrop flags, rectangle flags, economy stock flags or custom pole flags, depending on the shape and size that fits your space.',
			),
			array(
				'q' => 'Can I order just the printed flag?',
				'a' => 'Yes. Every flag comes as the flag only or as a flag and pole set. Order the flag only to replace a faded print on poles you already own.',
			),
			array(
				'q' => 'What base do I need?',
				'a' => 'Use a cross base with a water bag on hard surfaces such as parking lots and sidewalks, and a ground stake or spike on grass. Bases and poles are in ' . $link('flag-hardware', 'flag hardware') . '.',
			),
			$shipping,
		),
		'banner-stands' => array(
			array(
				'q' => 'Retractable banner stand or X-stand?',
				'a' => 'A retractable stand rolls the graphic into its base for fast setup and travel. An X-stand is the lightweight budget option. Tension fabric stands and step and repeat backdrops give a wrinkle-free fabric look.',
			),
			array(
				'q' => 'Can I buy a replacement graphic for my stand?',
				'a' => 'Yes. Order the printed graphic on its own, or shop ' . $link('hardware-only', 'hardware only') . ' to add stands for graphics you already have.',
			),
			array(
				'q' => 'Where are banner stands used?',
				'a' => 'Inside stores, in lobbies, at events and by traveling exhibitors, anywhere you need a vertical display that sets up quickly.',
			),
			$shipping,
		),
		'a-frame-and-sign-holders' => array(
			array(
				'q' => 'Which display works on a sidewalk?',
				'a' => 'A banner A-frame holds a printed banner on both sides. For a rugged plastic sidewalk sign, see ' . $link('signicade-a-frames', 'Signicade A-frames') . '.',
			),
			array(
				'q' => 'What is easiest for changing promotions?',
				'a' => 'Poster stands and snap poster hangers let you swap prints quickly. Magnetic wood frame hangers suit posters and prints you keep up longer.',
			),
			$shipping,
		),
		'signicade-a-frames' => array(
			array(
				'q' => 'What is the difference between the Signicade models?',
				'a' => 'Choose the Standard Signicade, the heavier Deluxe Signicade or the Simposign II. All are genuine Plasticade A-frames printed in full color with UV prints.',
			),
			array(
				'q' => 'Can I replace the graphics on my existing A-frame?',
				'a' => 'Yes. Order the graphics only for a frame you already own, or the complete sign with the frame.',
			),
			array(
				'q' => 'Are there reflective or writable versions?',
				'a' => 'Yes. See ' . $link('reflective-products', 'reflective signs') . ' for night visibility and ' . $link('dry-erase-products', 'dry erase signs') . ' for a surface you can write on.',
			),
			$shipping,
		),
		'real-estate-products' => array(
			array(
				'q' => 'Which real estate sign should I use?',
				'a' => 'For listings and open houses, choose yard signs with H-stakes, real estate frames, post signs or A-frames. Each is printed in full color and built to be reused.',
			),
			array(
				'q' => 'Can I reuse my frames and posts?',
				'a' => 'Yes. These signs are made to be reusable; order the printed sign only to refresh a frame or post you already have.',
			),
			array(
				'q' => 'How do I make a sign visible at night?',
				'a' => 'Choose a reflective version from ' . $link('reflective-products', 'reflective signs') . '. Reflective material bounces headlight glare back toward drivers.',
			),
			$shipping,
		),
		'seg-products' => array(
			array(
				'q' => 'What is an SEG fabric display?',
				'a' => 'Silicone edge graphics (SEG) are printed fabric panels with a thin silicone strip sewn around the edge. The strip presses into a slot in an aluminum frame for a tight, frameless look.',
			),
			array(
				'q' => 'What sizes are available?',
				'a' => 'Non-lit and backlit SEG displays run from 3ft stands to 20ft backwalls.',
			),
			array(
				'q' => 'Can I order just the fabric graphic?',
				'a' => 'Yes. Every SEG display comes as the graphic only or with the frame.',
			),
			$shipping,
		),
		'trade-show-products' => array(
			array(
				'q' => 'Tension fabric or Velcro pop up display?',
				'a' => 'A tension fabric display uses a pillowcase-style graphic that zips over a tube frame. A Velcro pop up display attaches fabric panels to a collapsible frame. Both come straight or curved.',
			),
			array(
				'q' => 'Can I order a new graphic for my frame?',
				'a' => 'Yes. Order the graphic only, or the graphic with the frame.',
			),
			array(
				'q' => 'What else do I need for a booth?',
				'a' => 'Finish the booth with ' . $link('table-throws', 'custom table throws') . ' and ' . $link('banner-stands', 'banner stands') . '. For a frameless look, see ' . $link('seg-products', 'SEG fabric displays') . '.',
			),
			$shipping,
		),
		'custom-event-tents' => array(
			array(
				'q' => 'What comes with a custom event tent?',
				'a' => 'Order the full color 10x10 canopy with a heavy duty aluminum hex frame, or the printed graphic only for a frame you already own.',
			),
			array(
				'q' => 'Can I add walls and a flag?',
				'a' => 'Yes. Add printed full or half tent walls and a tent flag to match your canopy.',
			),
			array(
				'q' => 'Do you sell tent accessories?',
				'a' => 'Frames, sandbags and carrying bags are in ' . $link('event-tent-hardware-only', 'event tent hardware') . '.',
			),
			$shipping,
		),
		'table-throws' => array(
			array(
				'q' => 'Which table cover size do I need?',
				'a' => 'Fitted covers come for 4ft, 6ft and 8ft tables, plus round covers and stretch covers. A table runner adds your logo to a cover you already have.',
			),
			array(
				'q' => 'How are table covers printed?',
				'a' => 'They are full color dye sublimated polyester. Solid color throws are also available.',
			),
			$shipping,
		),
		'hardware-only' => array(
			array(
				'q' => 'Does display hardware include a printed graphic?',
				'a' => 'No. These are stands and frames only. Order printed graphics under ' . $link('banner-stands', 'banner stands') . '.',
			),
			$help,
		),
		'banner-stand-hardware' => array(
			array(
				'q' => 'Is a graphic included with banner stand hardware?',
				'a' => 'No. This is replacement retractable and tension fabric stand hardware. Order printed graphics under ' . $link('banner-stands', 'banner stands') . '.',
			),
			$help,
		),
		'flag-hardware' => array(
			array(
				'q' => 'Which flag base should I use?',
				'a' => 'Use a cross base with a water bag on hard surfaces, and a ground stake or spike on grass.',
			),
			array(
				'q' => 'Is a printed flag included?',
				'a' => 'No. Flag hardware does not include a graphic. Order printed flags under ' . $link('advertising-flags', 'advertising flags') . '.',
			),
			$help,
		),
		'event-tent-hardware-only' => array(
			array(
				'q' => 'What tent hardware is available?',
				'a' => 'The 10x10 event tent frame on its own, half wall hardware, flag holders, sandbag sets and wheeled carrying bags.',
			),
			array(
				'q' => 'Are tent graphics included?',
				'a' => 'No. Printed canopies and walls are under ' . $link('custom-event-tents', 'custom event tents') . '.',
			),
			$help,
		),
		'large-format' => array(
			array(
				'q' => 'What can you print in large format?',
				'a' => 'Printed adhesive vinyl and window graphics, floor graphics, vehicle wrap vinyl, backlit film for lightboxes, posters, styrene, canvas and acrylic prints.',
			),
			$per_sqft,
			array(
				'q' => 'What should I use for a lightbox?',
				'a' => 'Use backlit film. For lit signs on your building, see our <a href="' . esc_url(home_url('/storefront-signs/')) . '">storefront signs guide</a>.',
			),
			$shipping,
		),
		'adhesive-products' => array(
			array(
				'q' => 'Which vinyl is best for store windows?',
				'a' => 'Use window perf for see-through window displays, clear, translucent or frosted vinyl for glass, and removable window clings for short promotions.',
			),
			array(
				'q' => 'Which vinyl is best for walls, floors and vehicles?',
				'a' => 'Standard or high performance vinyl suits decals and wall graphics. Floor graphics and 3M vehicle wrap film are made for those specialty jobs.',
			),
			$per_sqft,
			$shipping,
		),
		'wall-art' => array(
			array(
				'q' => 'Can you print my own photos?',
				'a' => 'Yes. Wall art is printed from your photos or artwork, for offices, lobbies, restaurants and homes.',
			),
			array(
				'q' => 'Which wall art format should I choose?',
				'a' => 'Choose gallery canvas wraps, canvas rolls, framed canvas, acrylic prints, framed posters and prints, or magnetic wood frame hangers.',
			),
			$shipping,
		),
		'rigid-signs-and-magnets' => array(
			array(
				'q' => 'Which rigid sign material lasts outdoors?',
				'a' => 'Aluminum and PVC suit long-term outdoor signs. Coroplast is the lightweight choice for yard and event signs. Foam board and GatorFoam work well indoors.',
			),
			array(
				'q' => 'What makes a durable sidewalk sign?',
				'a' => 'Aluminum sandwich boards. For plastic A-frames, see ' . $link('signicade-a-frames', 'Signicade A-frames') . '.',
			),
			array(
				'q' => 'Do you make vehicle magnets?',
				'a' => 'Yes. Custom magnets turn a vehicle into advertising. Reflective car magnets are in ' . $link('reflective-products', 'reflective signs') . '.',
			),
			$shipping,
		),
		'reflective-products' => array(
			array(
				'q' => 'How do reflective signs work?',
				'a' => 'Reflective material bounces headlight glare back toward drivers, so the sign stays visible after dark without power.',
			),
			array(
				'q' => 'Which reflective products are available?',
				'a' => 'Reflective aluminum signs, Coroplast, PVC, aluminum sandwich boards, Signicade A-frames, adhesive vinyl and car magnets.',
			),
			$shipping,
		),
		'dry-erase-products' => array(
			array(
				'q' => 'What are dry erase signs used for?',
				'a' => 'Daily specials, schedules and wait times. Your logo and layout are printed in full color, and you write the changing details on the dry erase surface.',
			),
			array(
				'q' => 'Which dry erase material should I choose?',
				'a' => 'Choose adhesive vinyl for walls, Coroplast, foamcore or PVC for boards, magnets, aluminum sandwich boards or Signicade A-frames for the sidewalk.',
			),
			$shipping,
		),
	);

	return isset($faqs[$slug]) ? $faqs[$slug] : array();
}

/**
 * Variant label for products sold in several configurations under one name,
 * taken from the slug ending (for example "Graphic Only" vs "Graphic & Frame").
 *
 * @return string Empty when the product has no variant ending.
 */
function wholesale_seo_product_variant_label($product_id)
{
	$slug = (string) get_post_field('post_name', $product_id);
	$slug = preg_replace('/-\d+$/', '', $slug);

	// Endings whose meaning is confirmed by price (the "only" version is the
	// cheaper one). "-frame-only" is left out: it is used inconsistently.
	$endings = array(
		'graphic-frame' => 'Graphic & Frame',
		'frame-graphic' => 'Graphic & Frame',
		'graphic-stand' => 'Graphic & Stand',
		'graphic-hanger' => 'Graphic & Hanger',
		'graphic-only' => 'Graphic Only',
		'insert-stand' => 'Graphic & Stand',
		'insert-only' => 'Graphic Only',
		'flag-pole' => 'Flag & Pole Set',
		'flag-only' => 'Flag Only',
		'sign-hstake' => 'Sign & H-Stake',
		'and-h-stake' => 'Sign & H-Stake',
		'banner-frame' => 'Banner & Frame',
		'banner-hardware' => 'Banner & Hardware',
		'banner-only' => 'Banner Only',
		'hardware-only' => 'Hardware Only',
		'full-wall' => 'Full Wall',
		'half-wall' => 'Half Wall',
		'a-frame-sign' => 'Sign & A-Frame',
		'frame-sign' => 'Sign & Frame',
		'post-sign' => 'Sign & Post',
		'sign-only' => 'Sign Only',
	);

	foreach ($endings as $ending => $label) {
		if (substr($slug, -strlen($ending) - 1) === '-' . $ending) {
			// Don't repeat what the product name already says.
			return false !== stripos(get_the_title($product_id), $label) ? '' : $label;
		}
	}

	return '';
}

/**
 * The product that an exact duplicate (same name, "-2"/"-3" slug) should
 * point search engines at, or 0.
 */
function wholesale_seo_product_duplicate_of($product_id)
{
	$slug = (string) get_post_field('post_name', $product_id);

	// Unlisted copies with the same name and price as a listed product.
	$copies = array(
		'straight-velcro-fabric-pop-up-display-graphic-frame-only' => 'straight-velcro-fabric-pop-up-display-graphic-frame',
	);

	if (isset($copies[$slug])) {
		$original_slug = $copies[$slug];
	} elseif (preg_match('/^(.+)-\d+$/', $slug, $match)) {
		$original_slug = $match[1];
	} else {
		return 0;
	}

	$original = get_page_by_path($original_slug, OBJECT, 'product');
	if (!$original || 'publish' !== $original->post_status || (int) $original->ID === (int) $product_id) {
		return 0;
	}

	return get_the_title($original) === get_the_title($product_id) ? (int) $original->ID : 0;
}

/**
 * IDs of published products that canonicalize to another product.
 *
 * @return int[]
 */
function wholesale_seo_duplicate_product_ids()
{
	static $ids = null;
	if (null !== $ids) {
		return $ids;
	}

	$ids = array();
	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
	));

	foreach ($products as $product_id) {
		if (wholesale_seo_product_duplicate_of($product_id)) {
			$ids[] = (int) $product_id;
		}
	}

	return $ids;
}

/**
 * The complete-kit product that a partial variant ("Graphic Only", "Flag Only",
 * "Sign Only"...) with the same name should point search engines at, or 0.
 *
 * Both pages share one description, so Google indexes only one of them and
 * reports the other as "Crawled - currently not indexed". The variant stays
 * buyable, in the merchant feed and in related products; only its canonical
 * and sitemap entry change.
 */
function wholesale_seo_product_variant_primary_of($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}

	$partial = array('Graphic Only', 'Flag Only', 'Sign Only', 'Banner Only', 'Hardware Only', 'Half Wall');
	$cache[$product_id] = 0;
	if (!in_array(wholesale_seo_product_variant_label($product_id), $partial, true)) {
		return 0;
	}

	$siblings = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'title' => get_the_title($product_id),
		'posts_per_page' => 10,
		'fields' => 'ids',
		'no_found_rows' => true,
		'orderby' => 'ID',
		'order' => 'ASC',
		'post__not_in' => array($product_id),
	));

	foreach ($siblings as $sibling_id) {
		$label = wholesale_seo_product_variant_label($sibling_id);
		if ($label && !in_array($label, $partial, true) && !wholesale_seo_product_duplicate_of($sibling_id)) {
			$cache[$product_id] = (int) $sibling_id;
			break;
		}
	}

	return $cache[$product_id];
}

/**
 * The product whose URL a product's canonical tag should use, or 0 for itself.
 */
function wholesale_seo_product_canonical_of($product_id)
{
	$original = wholesale_seo_product_duplicate_of($product_id);

	return $original ? $original : wholesale_seo_product_variant_primary_of($product_id);
}

/**
 * IDs of published products whose canonical points at another product; these
 * stay out of every sitemap.
 *
 * @return int[]
 */
function wholesale_seo_sitemap_excluded_product_ids()
{
	static $ids = null;
	if (null !== $ids) {
		return $ids;
	}

	$ids = array();
	$products = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
	));

	foreach ($products as $product_id) {
		if (wholesale_seo_product_canonical_of($product_id)) {
			$ids[] = (int) $product_id;
		}
	}

	return $ids;
}

function wholesale_seo_sitemap_exclude_duplicate_products($args, $post_type)
{
	if ('product' === $post_type && ($duplicates = wholesale_seo_sitemap_excluded_product_ids())) {
		$args['post__not_in'] = array_merge(isset($args['post__not_in']) ? (array) $args['post__not_in'] : array(), $duplicates);
	}

	return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'wholesale_seo_sitemap_exclude_duplicate_products', 20, 2);

/**
 * Plain-text description from product meta: list items become sentences and
 * the result is cut at a word boundary to fit a search snippet.
 */
function wholesale_seo_trim_description($text, $limit = 155)
{
	$text = preg_replace('#</(li|p|h[1-6])>#i', '. ', (string) $text);
	$text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES, 'UTF-8');
	$text = preg_replace('/\s+/', ' ', $text);
	$text = preg_replace('/\s*\.(\s*\.)+/', '.', $text);
	$text = trim($text, " .\t\n\r");

	if ('' === $text) {
		return '';
	}

	$text .= '.';
	if (mb_strlen($text) <= $limit) {
		return $text;
	}

	$cut = mb_substr($text, 0, $limit);
	$sentence_end = mb_strrpos($cut, '. ');
	if (false !== $sentence_end && $sentence_end > $limit * 0.6) {
		return mb_substr($cut, 0, $sentence_end + 1);
	}

	$space = mb_strrpos($cut, ' ');
	return rtrim(mb_substr($cut, 0, false !== $space ? $space : $limit), ' ,;:-') . '.';
}

/**
 * Search-friendly product names for catalog names that are codes or single
 * material words ("AV 6 – Adhesive Window Perf", "Coroplast", "DTF"). Keys are
 * the product name with any "AV n –" code removed; names not listed are used
 * as they are.
 *
 * @return array
 */
function wholesale_seo_product_search_names()
{
	return array(
		// Adhesive vinyl (AV 1–12).
		// "Matte" keeps it apart from the Printed Adhesive Vinyl category title.
		'Adhesive Vinyl' => 'Matte Printed Adhesive Vinyl',
		'Adhesive Vinyl (High Performance)' => 'High Performance Adhesive Vinyl',
		'Adhesive Translucent Vinyl' => 'Translucent Adhesive Vinyl',
		'Frosted Vinyl (Etched)' => 'Frosted Etched Glass Vinyl',
		'Adhesive Clear Vinyl' => 'Clear Adhesive Vinyl',
		'Adhesive Window Perf' => 'Window Perf Vinyl',
		'Floor Graphics' => 'Custom Floor Graphics',
		'3M IJ-180Cv3 Controltac (Vehicle Wrap)' => '3M IJ180Cv3 Vehicle Wrap Vinyl',
		// Large format and wall art.
		'DTF' => 'DTF Transfers (Direct to Film)',
		'Popup' => 'PVC Popup Prints',
		'Posters' => 'Custom Poster Printing',
		'Backlit Film' => 'Backlit Film Prints',
		'Acrylic Print' => 'Custom Acrylic Prints',
		'Canvas Wrap' => 'Canvas Wrap Prints',
		'Canvas Roll' => 'Canvas Roll Prints',
		// Banners.
		'13oz. Vinyl Banner' => '13oz Vinyl Banner',
		'Indoor Banner / Super Smooth' => 'Super Smooth Indoor Banner',
		'Tension Fabric' => 'Tension Fabric Banner',
		// Rigid signs.
		'Coroplast' => 'Coroplast Signs',
		'Styrene' => 'Styrene Signs',
		'PVC Board' => 'PVC Board Signs',
		'Foam Board' => 'Foam Board Signs',
		'GatorFoam' => 'GatorFoam Signs',
		'Aluminum Sign' => 'Aluminum Signs',
		'Magnets' => 'Custom Magnets',
		// Stands, A-frames, tents and table covers.
		'Standard Retractable' => 'Standard Retractable Banner Stand',
		'Deluxe Retractable' => 'Deluxe Retractable Banner Stand',
		'SD Retractable' => 'SD Retractable Banner Stand',
		'X-Stand' => 'X-Stand Banner',
		'Standard Retractable (Hardware Only)' => 'Standard Retractable Banner Stand (Hardware Only)',
		'Deluxe Retractable (Hardware Only)' => 'Deluxe Retractable Banner Stand (Hardware Only)',
		'SD Retractable (Hardware Only)' => 'SD Retractable Banner Stand (Hardware Only)',
		'X-Stand (Hardware Only)' => 'X-Stand Banner Stand (Hardware Only)',
		'Standard Signicade (White)' => 'Standard Signicade A-Frame (White)',
		'Deluxe Signicade' => 'Deluxe Signicade A-Frame',
		'Simposign II (White)' => 'Simposign II A-Frame (White)',
		'Event Tent (Full Color)' => 'Custom Full Color Event Tent',
		'Tent Walls (Full Color)' => 'Custom Full Color Tent Walls',
		'Table Runner' => 'Custom Table Runner',
		// Hardware.
		'Cross Base' => 'Flag Cross Base',
		'Square Base' => 'Square Flag Base',
		'Ground Stake' => 'Flag Ground Stake',
		'Water Bag' => 'Flag Base Water Bag',
		'Carrying Bag' => 'Flag Carrying Bag',
		'Pole Set (Econo Flag)' => 'Econo Flag Pole Set',
		'Spike (Econo Flag)' => 'Econo Flag Ground Spike',
		'Event Tent Frame Only' => 'Event Tent Frame',
		'Sandbag (4pc Set)' => 'Event Tent Sandbags (4pc Set)',
		'Carrying Bag w/ Wheels' => 'Event Tent Carrying Bag with Wheels',
		'Flag Holder Hardware' => 'Event Tent Flag Holder',
		'Half Wall Hardware' => 'Event Tent Half Wall Hardware',
	);
}

/**
 * The words that end a product title, by product_category slug: what a
 * shopper calls the whole category.
 *
 * @return array
 */
function wholesale_seo_category_title_suffixes()
{
	return array(
		'banners' => 'Custom Printed Banners',
		'advertising-flags' => 'Custom Advertising Flags',
		'banner-stands' => 'Custom Banner Stands',
		'a-frame-and-sign-holders' => 'A-Frames & Sign Holders',
		'signicade-a-frames' => 'Custom Sidewalk Signs',
		'real-estate-products' => 'Real Estate Signs',
		'seg-products' => 'SEG Fabric Displays',
		'trade-show-products' => 'Trade Show Displays',
		'custom-event-tents' => 'Custom Event Tents',
		'table-throws' => 'Custom Table Covers',
		'hardware-only' => 'Display Hardware',
		'banner-stand-hardware' => 'Banner Stand Hardware',
		'flag-hardware' => 'Flag Hardware',
		'event-tent-hardware-only' => 'Event Tent Hardware',
		'large-format' => 'Large Format Printing',
		'adhesive-products' => 'Printed Adhesive Vinyl',
		'wall-art' => 'Custom Wall Art',
		'rigid-signs-and-magnets' => 'Custom Rigid Signs',
		'reflective-products' => 'Reflective Signs',
		'dry-erase-products' => 'Dry Erase Signs',
	);
}

/**
 * The product's catalog name as plain text, without the "Copy" suffix that
 * duplicated products carry.
 */
function wholesale_seo_product_name($product_id)
{
	$name = html_entity_decode(wp_strip_all_tags(get_the_title($product_id)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

	return trim(preg_replace('/\s+Copy$/', '', $name));
}

/**
 * The name a shopper would search for: the catalog name without its "AV n –"
 * code, swapped for a clearer name when one is listed.
 */
function wholesale_seo_product_search_name($product_id)
{
	$name = wholesale_seo_product_name($product_id);
	$name = preg_replace('/^AV\s*\d+\s*[\x{2013}\x{2014}-]\s*/u', '', $name);
	$names = wholesale_seo_product_search_names();

	return isset($names[$name]) ? $names[$name] : $name;
}

/**
 * Title suffix for a product's category. A product in a parent and a child
 * category takes the child's, which is the more specific.
 */
function wholesale_seo_product_title_suffix($product_id)
{
	$terms = get_the_terms($product_id, 'product_category');
	$suffixes = wholesale_seo_category_title_suffixes();
	$suffix = '';

	foreach ($terms && !is_wp_error($terms) ? $terms : array() as $term) {
		if (isset($suffixes[$term->slug]) && ('' === $suffix || $term->parent)) {
			$suffix = $suffixes[$term->slug];
		}
	}

	return $suffix;
}

/**
 * Title and description for a product that is not a channel letter style.
 *
 * Title: search name, variant, then the category ("Coroplast Signs (Sign Only)
 * | Custom Rigid Signs"), dropping the category when it would pass 60
 * characters. The brand is left out: Google shows the site name beside it.
 *
 * @return array
 */
function wholesale_seo_product_meta($product_id)
{
	$name = wholesale_seo_product_search_name($product_id);
	$variant = wholesale_seo_product_variant_label($product_id);
	$label = $variant ? sprintf('%s (%s)', $name, $variant) : $name;
	$suffix = wholesale_seo_product_title_suffix($product_id);
	if ($suffix && false !== stripos($label, $suffix)) {
		$suffix = '';
	}

	$title = sprintf('%s | %s', $label, $suffix ? $suffix : get_bloginfo('name'));
	if (mb_strlen($title) > 60) {
		$title = $label;
	}

	$source = get_post_meta($product_id, '_seo_description', true);
	foreach (array('_product_short_desc', '_product_description') as $key) {
		if (!$source) {
			$source = get_post_meta($product_id, $key, true);
		}
	}
	if (!$source) {
		$source = get_post_field('post_content', $product_id);
	}

	// The catalog name ("AV 12 – Dry Erase Adhesive Vinyl") keeps the snippet
	// distinct from the same material listed in another category.
	$catalog_name = wholesale_seo_product_name($product_id);
	$prefix = $variant ? $catalog_name . ', ' . strtolower($variant) . ': ' : $catalog_name . ': ';
	$description = wholesale_seo_trim_description($source, 155 - mb_strlen($prefix));

	return array(
		'title' => $title,
		'description' => $description ? $prefix . $description : '',
	);
}

/**
 * A published product by slug, or null.
 */
function wholesale_seo_product($slug)
{
	$primed = $GLOBALS['wholesale_seo_primed_products'] ?? array();
	if (isset($primed[$slug])) {
		return $primed[$slug];
	}

	$product = get_page_by_path($slug, OBJECT, 'product');

	return $product && 'publish' === $product->post_status ? $product : null;
}

/**
 * Load the published products a page shows in one query (posts, meta and featured
 * images), so wholesale_seo_product() and get_post_meta() don't query per slug.
 * Slugs that aren't published fall back to wholesale_seo_product()'s own lookup.
 */
function wholesale_seo_prime_products(array $slugs)
{
	$slugs = array_values(array_unique(array_filter(array_map('sanitize_title', $slugs))));
	if (!$slugs) {
		return;
	}
	$query = new WP_Query(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'post_name__in' => $slugs,
		'posts_per_page' => count($slugs),
		'no_found_rows' => true,
		'ignore_sticky_posts' => true,
	));
	update_post_thumbnail_cache($query);
	foreach ($query->posts as $post) {
		$GLOBALS['wholesale_seo_primed_products'][$post->post_name] = $post;
	}
}

/**
 * The "starting at" price text a product card shows (may contain <del>), or ''.
 */
function wholesale_seo_product_starting_text($slug)
{
	$product = wholesale_seo_product($slug);

	return $product ? trim((string) get_post_meta($product->ID, '_starting_at_text', true)) : '';
}

/**
 * Smallest and largest letter height offered for a channel letter style.
 *
 * @return int[] array(min, max) in inches, or an empty array.
 */
function wholesale_seo_letter_height_range($slug)
{
	$product = wholesale_seo_product($slug);
	$attrs = $product ? json_decode((string) get_post_meta($product->ID, 'product_attr', true), true) : null;
	$heights = array();

	foreach (is_array($attrs) ? $attrs : array() as $attr) {
		if ('height' !== ($attr['name'] ?? '') || empty($attr['options'])) {
			continue;
		}
		foreach ($attr['options'] as $option) {
			$label = is_array($option) ? (string) key($option) : '';
			if (preg_match('/(\d+)/', $label, $match)) {
				$heights[] = (int) $match[1];
			}
		}
	}

	return $heights ? array(min($heights), max($heights)) : array();
}

/**
 * Lowest price a customer can pay for one unit of a product: the checkout's
 * own quote for the smallest size with the first option of every attribute
 * and any product discount applied. Channel letters are priced for one letter
 * at the smallest height without a power supply. Product structured data uses
 * it so Google sees the same price the page and cart charge.
 *
 * @return float 0 when the product has no online price.
 */
function wholesale_seo_product_lowest_price($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}

	$cache[$product_id] = 0.0;
	if (!function_exists('wholesale_price_quote')) {
		return 0.0;
	}

	$request = array('letters' => 'A', 'product_quantity' => 1);
	$choices = array();
	foreach (wholesale_product_price_attrs($product_id) as $attr) {
		$options = wholesale_attr_options($attr);
		if (!empty($attr['name']) && $options) {
			$request[$attr['name']] = $options[0]['value'];
			$choices[$attr['name']] = wp_list_pluck($options, 'value');
		}
	}

	// The starting size the product page fills in: a square of the minimum
	// area, rounded up to a tenth of an inch (see single-product.php).
	$min_sqft = max(0, (float) get_post_meta($product_id, '_min_sqft', true));
	$side_ft = ($min_sqft > 0 ? ceil(round(sqrt($min_sqft) * 12, 6) * 10) / 10 : 12) / 12;
	$request['height-ft'] = $side_ft;
	$request['width-ft'] = $side_ft;

	$quote = wholesale_price_quote($product_id, $request);
	if (!empty($quote['ok']) && $quote['total'] > 0) {
		return $cache[$product_id] = (float) $quote['total'];
	}

	// A product whose minimum height or width is larger than that square.
	$min_height = (float) get_post_meta($product_id, '_min_height', true);
	if ($min_height > $side_ft) {
		$request['height-ft'] = $min_height;
		$request['width-ft'] = max((float) get_post_meta($product_id, '_min_width', true), $min_sqft / $min_height);
		$quote = wholesale_price_quote($product_id, $request);
		if (!empty($quote['ok']) && $quote['total'] > 0) {
			return $cache[$product_id] = (float) $quote['total'];
		}
	}

	// The first option can't be bought on its own (it costs $0): use the
	// cheapest option that can.
	foreach ($choices as $name => $values) {
		foreach (array_slice($values, 1) as $value) {
			$quote = wholesale_price_quote($product_id, array_merge($request, array($name => $value)));
			if (!empty($quote['ok']) && $quote['total'] > 0 && (!$cache[$product_id] || $quote['total'] < $cache[$product_id])) {
				$cache[$product_id] = (float) $quote['total'];
			}
		}
	}

	return $cache[$product_id];
}

/**
 * Register the FAQ a template shows, so the head can describe it in FAQPage
 * structured data. Call before get_header(). Answers may contain links.
 *
 * @param array $items List of array('q' => question, 'a' => answer HTML).
 */
function wholesale_seo_set_page_faq($items)
{
	$GLOBALS['wholesale_page_faq'] = $items;
}

/**
 * FAQPage entity for the FAQ registered on this request, or null.
 */
function wholesale_seo_faq_schema()
{
	$items = isset($GLOBALS['wholesale_page_faq']) ? (array) $GLOBALS['wholesale_page_faq'] : array();
	if (!$items) {
		return null;
	}

	$questions = array();
	foreach ($items as $item) {
		$questions[] = array(
			'@type' => 'Question',
			'name' => html_entity_decode(wp_strip_all_tags($item['q']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text' => trim(preg_replace('/\s+/', ' ', html_entity_decode(wp_strip_all_tags($item['a']), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
			),
		);
	}

	return array(
		'@type' => 'FAQPage',
		'@id' => trailingslashit(wholesale_seo_url()) . '#faq',
		'mainEntity' => $questions,
	);
}

/**
 * Print the registered FAQ as the site's expandable question list.
 */
function wholesale_seo_render_faq($open_first = false)
{
	$items = isset($GLOBALS['wholesale_page_faq']) ? (array) $GLOBALS['wholesale_page_faq'] : array();

	foreach ($items as $index => $item) {
		printf(
			'<details%s><summary>%s</summary><p>%s</p></details>',
			$open_first && 0 === $index ? ' open' : '',
			esc_html($item['q']),
			wp_kses_post($item['a'])
		);
	}
}

/**
 * Add the page FAQ to Rank Math's graph when Rank Math owns the head.
 */
function wholesale_seo_rank_math_faq($data)
{
	$faq = is_array($data) ? wholesale_seo_faq_schema() : null;
	if ($faq) {
		foreach ($data as $entity) {
			if (is_array($entity) && in_array('FAQPage', (array) ($entity['@type'] ?? array()), true)) {
				return $data;
			}
		}
		$data['wholesale-faq'] = $faq;
	}

	return $data;
}
add_filter('rank_math/json_ld', 'wholesale_seo_rank_math_faq', 25);

/**
 * Style guide copy for each channel letter product page. Keys are product
 * slugs; facts match the product construction described elsewhere on the site.
 *
 * @return array
 */
function wholesale_seo_channel_letter_guides()
{
	return array(
		'standard-channel-letter-front-lit' => array(
			'look' => 'Front lit letters are lit from inside and shine through a colored acrylic face, so the whole face of each letter glows. It is the brightest, most readable style at night and the classic look for storefront signs.',
			'build' => array('Colored acrylic face held by a trimcap', '.040 aluminum returns', 'LED modules and a power supply inside each letter', 'Optional raceway for mounting and wiring'),
			'best_for' => 'Retail stores, restaurants, convenience stores and any business that needs to be read from the road or across a parking lot after dark.',
		),
		'standard-channel-letter-back-lit' => array(
			'look' => 'Back lit letters send light onto the wall behind them, surrounding each letter with a soft glow while the letter itself reads as a solid shape. The effect is calmer than a fully lit face.',
			'build' => array('Acrylic face held by a trimcap', '.040 aluminum returns', 'LED lighting aimed at the wall behind the letter', 'Optional raceway for mounting and wiring'),
			'best_for' => 'Cafes, restaurants and offices that want a lit sign with a softer, more atmospheric look.',
		),
		'standard-channel-letter-front-back-lit' => array(
			'look' => 'Front and back lit letters, also called dual lit, glow through the face and onto the wall behind at the same time. You get the readability of a front lit sign with a halo around every letter.',
			'build' => array('Acrylic face held by a trimcap', '.040 aluminum returns', 'LED lighting for both the face and the wall behind', 'Optional raceway for mounting and wiring'),
			'best_for' => 'Businesses that want maximum presence after dark, such as restaurants, entertainment venues and shops on busy streets.',
		),
		'hidden-back-halo-lit' => array(
			'look' => 'Halo lit letters have solid metal faces, so the letter stays dark and the light shines out of the back to draw a glowing outline on the wall. By day they look like painted metal dimensional letters.',
			'build' => array('Welded stainless steel faces and returns', 'Sanded and painted in your color', 'LED lighting that shines out the back of each letter'),
			'best_for' => 'Salons, boutiques, clinics, law and professional offices, and brands that want an upscale, understated sign.',
		),
		'halo-reverse-acrylic-lit-channel-letters' => array(
			'look' => 'Reverse lit letters glow from behind through an exposed acrylic back, casting a halo of light onto the wall around a painted metal face.',
			'build' => array('Welded stainless steel faces and returns', 'Exposed acrylic back that the light shines through', 'Sanded and painted in multiple colors'),
			'best_for' => 'Modern storefronts and offices that want a halo effect with a clean metal face.',
		),
		'inset-acrylic-face-lit-with-border-no-trimcap' => array(
			'look' => 'Trimless letters with a border are face lit like a standard letter, but the acrylic face sits inside a thin metal border instead of a plastic trimcap, for a crisp edge up close.',
			'build' => array('Inset acrylic face with a metal border, no trimcap', 'Welded stainless steel returns, sanded and painted', 'LED lighting behind the face'),
			'best_for' => 'Brands that want a bright face lit sign with a more refined, modern edge.',
		),
		'exposed-acrylic-face-lit-borderless-no-trimcap' => array(
			'look' => 'Borderless letters have an exposed acrylic face with no trimcap and no border, so only the lit face shows. It is the sleekest face lit style.',
			'build' => array('Exposed acrylic face, no trimcap or border', 'Welded stainless steel returns, sanded and painted', 'LED lighting behind the face'),
			'best_for' => 'Contemporary brands, tech and design businesses, and any storefront where a minimal look matters.',
		),
	);
}

/**
 * Print the style guide section on a channel letter product page.
 */
function wholesale_seo_render_channel_letter_guide($product_id)
{
	$slug = (string) get_post_field('post_name', $product_id);
	$guides = wholesale_seo_channel_letter_guides();
	$seo = wholesale_seo_channel_letter_product($product_id);
	if (!isset($guides[$slug]) || empty($seo['heading'])) {
		return;
	}

	$guide = $guides[$slug];
	$heading = $seo['heading'];
	$heights = wholesale_seo_letter_height_range($slug);
	$storefront_url = function_exists('wholesale_seo_storefront_signs_url') ? wholesale_seo_storefront_signs_url() : '';
	?>
	<section class="cl-style-guide" aria-labelledby="cl-style-guide-title">
		<h2 id="cl-style-guide-title"><?php echo esc_html(sprintf('%s: Style Guide', $heading)); ?></h2>
		<p><?php echo esc_html($guide['look']); ?></p>

		<h3>How they&rsquo;re built</h3>
		<ul>
			<?php foreach ($guide['build'] as $item) : ?>
				<li><?php echo esc_html($item); ?></li>
			<?php endforeach; ?>
		</ul>

		<h3>Best for</h3>
		<p><?php echo esc_html($guide['best_for']); ?></p>

		<h3>Sizes and ordering</h3>
		<p>
			<?php if ($heights) : ?>
				<?php echo esc_html(sprintf('Available with letters from %d to %d inches tall.', $heights[0], $heights[1])); ?>
			<?php endif; ?>
			<?php
			// The same starting price the product's structured data gives Google.
			$from_price = wholesale_seo_product_lowest_price($product_id);
			$discount = (float) get_post_meta($product_id, '_discount_percent', true);
			if ($from_price > 0 && $heights) :
				?>
				<?php
				echo '<strong>' . esc_html(sprintf('Prices start at $%s for one %d inch letter', number_format($from_price, 2), $heights[0])) . '</strong>'
					. esc_html($discount > 0 ? sprintf(', including the %s%% online discount,', rtrim(rtrim(number_format($discount, 2), '0'), '.')) : '')
					. ' before options such as a power supply or raceway.';
				$cost_page = get_page_by_path('channel-letter-cost');
				if ($cost_page && 'publish' === $cost_page->post_status) {
					echo ' Compare every style and height in our <a href="' . esc_url(get_permalink($cost_page)) . '">channel letter cost guide</a>.';
				}
				?>
			<?php endif; ?>
			Price is per letter by height: choose your height, enter your wording and colors above, and your total updates before you add it to the cart. Every sign is tested before it ships, with an installation pattern and wiring diagram for your installer.
		</p>

		<h3>Compare other channel letter styles</h3>
		<ul>
			<?php foreach (wholesale_seo_channel_letter_products() as $other_slug => $other) : ?>
				<?php
				if ($other_slug === $slug) {
					continue;
				}
				$other_product = wholesale_seo_product($other_slug);
				if (!$other_product) {
					continue;
				}
				?>
				<li><a href="<?php echo esc_url(get_permalink($other_product)); ?>"><?php echo esc_html($other['heading']); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<p>
			See all styles side by side on the <a href="<?php echo esc_url(home_url('/#cl-compare-title')); ?>">channel letters page</a><?php if ($storefront_url) : ?>, or compare every sign type in our <a href="<?php echo esc_url($storefront_url); ?>">storefront signs guide</a><?php endif; ?>.
		</p>
	</section>
	<?php
}

/**
 * Add alt text to <img> tags that have none (or an empty one) in stored HTML,
 * such as the supplier product descriptions on channel letter pages.
 * Images are numbered so each alt is distinct.
 */
function wholesale_seo_fill_missing_alt($html, $label)
{
	$html = (string) $html;
	if ('' === $html || false === stripos($html, '<img')) {
		return $html;
	}

	$count = 0;

	return preg_replace_callback('/<img\b[^>]*>/i', static function ($match) use ($label, &$count) {
		$tag = $match[0];
		// Keep a non-empty alt; alt="" counts as missing here.
		if (preg_match('/\balt\s*=\s*(?:"[^"]*[^"\s][^"]*"|\'[^\']*[^\'\s][^\']*\')/i', $tag)) {
			return $tag;
		}

		$count++;
		$alt = esc_attr(sprintf('%s, image %d', $label, $count));
		$tag = preg_replace('/\s+alt\s*=\s*(["\'])\s*\1/i', '', $tag);

		return preg_replace('/^<img\b/i', '<img alt="' . $alt . '"', $tag);
	}, $html);
}

/**
 * Readable alt text for a media library image, or '' when nothing reliable is
 * known (for example customer design uploads with generated file names).
 */
function wholesale_seo_attachment_alt($attachment_id)
{
	$product_title = static function ($product_id) {
		$seo = wholesale_seo_channel_letter_product($product_id);
		if (!empty($seo['heading'])) {
			return $seo['heading'];
		}
		return preg_replace('/\s+Copy$/', '', wp_strip_all_tags(get_the_title($product_id)));
	};

	// Customer uploads (builder designs, artwork files) are never labeled.
	$title = (string) get_the_title($attachment_id);
	if (preg_match('/^(?:clDesign|custom-artwork|guest-design)/i', $title) || preg_match('/[0-9a-f]{10,}/i', $title)) {
		return '';
	}

	// Readable file titles such as "trimcap-info" or "hero-bg"; generated
	// names like "0ov9xJYW-s1000" are not.
	$readable = '';
	if (!preg_match('/^(?:[A-Za-z0-9]{8}[-_]s1000|clDesign|IMG_|DSC|image\d*$|\d+$)/', $title) && preg_match('/[a-z]{3,}/i', $title)) {
		$readable = ucfirst(trim(preg_replace('/\s+/', ' ', str_replace(array('-', '_'), ' ', preg_replace('/\.(jpe?g|png|webp|gif)$/i', '', $title)))));
	}

	$featured_on = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'fields' => 'ids',
		'meta_key' => '_thumbnail_id',
		'meta_value' => (int) $attachment_id,
	));
	$parent = (int) wp_get_post_parent_id($attachment_id);
	$product_id = $featured_on ? (int) $featured_on[0] : ($parent && 'product' === get_post_type($parent) ? $parent : 0);

	if ($product_id) {
		$name = $product_title($product_id);
		return $readable && !$featured_on ? sprintf('%s: %s', $name, strtolower($readable)) : $name;
	}

	return $readable;
}

/**
 * Pages print their title as the H1, so an H1 typed into the page content
 * (the Terms page has one) becomes a second H1. Demote those to H2.
 */
function wholesale_seo_demote_content_h1($content)
{
	if (!is_page() || false === stripos($content, '<h1')) {
		return $content;
	}

	return preg_replace(array('/<h1(\s|>)/i', '/<\/h1>/i'), array('<h2$1', '</h2>'), $content);
}
add_filter('the_content', 'wholesale_seo_demote_content_h1', 20);

/**
 * "More {category}" links under a product: up to eight other listed products
 * from the product's most specific category, so shoppers and search engines
 * can move between related products (Coroplast to aluminum and PVC signs,
 * Graphic Only to Graphic & Frame). Channel letters have their own style list.
 */
function wholesale_seo_render_related_products($product_id)
{
	if (wholesale_seo_channel_letter_product($product_id)) {
		return;
	}

	$terms = get_the_terms($product_id, 'product_category');
	if (!$terms || is_wp_error($terms)) {
		return;
	}

	// A child category is more specific than its parent.
	$term = $terms[0];
	foreach ($terms as $candidate) {
		if ($candidate->parent) {
			$term = $candidate;
		}
	}

	$related = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => 8,
		'post__not_in' => array_merge(array((int) $product_id), wholesale_seo_duplicate_product_ids()),
		'no_found_rows' => true,
		'orderby' => array('menu_order' => 'ASC', 'title' => 'ASC'),
		'tax_query' => array(
			array('taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => (int) $term->term_id),
		),
		'meta_query' => array(
			array('key' => '_show_in_list', 'value' => 'on'),
		),
	));
	if (!$related) {
		return;
	}
	?>
	<section class="related-products" aria-labelledby="related-products-title">
		<div class="related-products-head">
			<h2 id="related-products-title"><?php echo esc_html(sprintf('More %s', $term->name)); ?></h2>
			<a href="<?php echo esc_url(wholesale_category_url($term->slug)); ?>"><?php echo esc_html(sprintf('See all %s', $term->name)); ?> &rarr;</a>
		</div>
		<ul class="related-products-grid">
			<?php foreach ($related as $item) : ?>
				<?php
				$name = wholesale_seo_product_name($item->ID);
				$price = wholesale_seo_product_lowest_price($item->ID);
				?>
				<li class="related-product">
					<a href="<?php echo esc_url(get_permalink($item)); ?>">
						<span class="related-product-image">
							<?php
							if (has_post_thumbnail($item)) {
								echo get_the_post_thumbnail($item, 'medium', array(
									'loading' => 'lazy',
									'decoding' => 'async',
									'alt' => $name,
									'sizes' => '(max-width: 575px) 50vw, 200px',
								));
							}
							?>
						</span>
						<span class="related-product-name"><?php echo esc_html($name); ?></span>
						<?php if ($price > 0) : ?>
							<span class="related-product-price"><?php echo esc_html(sprintf('From $%s', number_format($price, 2))); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}
