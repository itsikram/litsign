<?php
/**
 * Buying guide copy for product pages: what the product is, what it is good
 * for, and the questions buyers ask before ordering.
 *
 * Most product descriptions are a sentence or two plus the shared turnaround
 * and file setup notes, so Google saw near-identical pages and left many of
 * them out of its index. Each guide gives a page its own text. Facts here only
 * repeat the product's own spec sheet (sizes, materials, weights, what's
 * included); keep it that way when adding guides.
 *
 * Keys are product slugs. A partial variant ("Flag Only", "Graphic Only")
 * shows the guide of the complete kit it canonicalizes to.
 *
 * - 'intro'   Paragraphs; may contain links.
 * - 'uses'    "Popular uses" list.
 * - 'faq'     Questions and answers; answers may contain links. Also sent to
 *             Google as FAQPage structured data.
 * - 'related' Product slugs to link to; missing or unpublished ones are skipped.
 */

/**
 * @return array
 */
function wholesale_product_guides()
{
	$flag_bases = 'Use the ground stake for grass, sand or soil. For concrete, asphalt, tile or carpet, choose the cross base (add the water bag for extra weight outdoors) or the 21.83 lb steel square base.';
	$print_thru = 'Single sided flags are dye sublimated so the print shows through: full color on the front, mirrored and lighter but readable from the back. Double sided flags are two prints sewn back to back with a silver block out layer, so both sides read correctly.';

	return array(
		'reflective-aluminum-sign' => array(
			'intro' => array(
				'A reflective aluminum sign does the job of a regular aluminum sign during the day and keeps working after dark. The graphic is UV printed onto engineer grade reflective vinyl and mounted on a .040 aluminum sheet, so headlights and flashlights bounce your message back to the viewer.',
				'Aluminum does not rust or warp. The sign is waterproof and UV safe, so it suits permanent outdoor jobs such as parking, private property, gate and directional signs. Choose 12" x 18", 18" x 24" or 24" x 36", portrait or landscape, single or double sided, rounded or straight corners, and pre-punched holes for posts, fences or walls.',
			),
			'uses' => array('Parking, tow-away and private property signs', 'Driveway, gate and address signs that need to be seen at night', 'Directional and wayfinding signs for lots, warehouses and job sites', 'Safety and notice signs for HOAs, schools and businesses'),
			'faq' => array(
				array('q' => 'What is the difference between this and a regular aluminum sign?', 'a' => 'Both use the same .040 aluminum sheet. The reflective version is printed on 4 mil engineer grade reflective vinyl, so it lights up when headlights hit it. Choose reflective when the sign has to be read at night.'),
				array('q' => 'Can I mount it on a post or fence?', 'a' => 'Yes. Choose a hole punch option (all four corners, top corners, or center top and bottom) and the sign arrives ready to bolt, screw or zip tie in place.'),
				array('q' => 'What sizes are available?', 'a' => '12" x 18", 18" x 24" and 24" x 36", in portrait or landscape. For other sizes, the reflective aluminum sandwich board can be cut from 3" x 4" up to 48" x 96".'),
			),
			'related' => array('aluminum-sign', 'reflective-aluminum-sandwich-board', 'reflective-coroplast-sign-hstake', 'reflective-car-magnet'),
		),

		'reflective-aluminum-sandwich-board' => array(
			'intro' => array(
				'A reflective aluminum sandwich board is a 1/8" thick aluminum composite panel faced with 4 mil reflective vinyl, so your sign stays visible in low light and at night. It is the most rigid and longest lasting reflective sign we make.',
				'Unlike the fixed sizes of a reflective aluminum sign, it is cut to any size from 3" x 4" up to 48" x 96". Corners are rounded to 1/4" and you can add .25" holes for mounting. It is waterproof and UV safe for years of outdoor use. Sizes over 46" x 38" (or 48" x 30") are store pickup only.',
			),
			'uses' => array('Large parking lot and property signs', 'Building, dock and warehouse identification', 'Construction and job site signs that stay up for months', 'Custom-size reflective panels for gates and entrances'),
			'faq' => array(
				array('q' => 'How is this different from a reflective aluminum sign?', 'a' => 'The sandwich board is a thicker 1/8" aluminum composite panel and can be any size from 3" x 4" to 48" x 96". The reflective aluminum sign is a thinner .040 aluminum sheet in three standard sizes.'),
				array('q' => 'Can large sizes be shipped?', 'a' => 'Signs up to 46" x 38" or 48" x 30" ship. Larger panels, up to 48" x 96", are available for store pickup at our California locations.'),
				array('q' => 'Can it be printed on both sides?', 'a' => 'Yes, choose single sided (4/0) or double sided (4/4) printing.'),
			),
			'related' => array('aluminum-sandwich-board', 'dry-erase-aluminum-sandwich-board', 'reflective-aluminum-sign', 'reflective-pvc-board'),
		),

		'reflective-coroplast-sign-hstake' => array(
			'intro' => array(
				'Reflective coroplast signs are lightweight 4mm corrugated plastic signs faced with 4 mil reflective vinyl, so they catch headlights and stay readable around the clock. They cost far less than aluminum, which makes them a smart choice for signs that need to stay up for weeks or months rather than years.',
				'Coroplast is waterproof and UV safe. Order the sign with an H-stake to push it straight into a lawn or roadside, or order the sign only. Custom sizes run up to 48" x 96"; anything over 46" x 38" (or 48" x 30") is store pickup only.',
			),
			'uses' => array('Event parking and direction signs', 'Roadside and lawn signs that must be seen at dusk', 'Construction, detour and temporary notice signs', 'Open house and real estate directional signs'),
			'faq' => array(
				array('q' => 'How long does reflective coroplast last outdoors?', 'a' => 'It is made for medium term outdoor use: waterproof and UV safe, but lighter duty than aluminum. For signs that will stay up for years, choose a <a href="/product/reflective-aluminum-sandwich-board/">reflective aluminum sandwich board</a>.'),
				array('q' => 'Do I need the H-stake?', 'a' => 'Choose the sign and H-stake set if the sign goes into soft ground. Choose the sign only if you will mount it on a wall, fence or existing frame.'),
				array('q' => 'Can it be double sided?', 'a' => 'Yes, both single sided (4/0) and double sided (4/4) printing are available.'),
			),
			'related' => array('coroplast', 'dry-erase-coroplast-sign-hstake', 'yard-sign-and-h-stake', 'reflective-aluminum-sign'),
		),

		'reflective-car-magnet' => array(
			'intro' => array(
				'A reflective car magnet turns any steel door or tailgate into an advertisement that works day and night. The graphic is UV printed onto 4 mil reflective vinyl and laminated to a 30 mil magnet, so your name and phone number light up in other drivers&rsquo; headlights.',
				'Magnets go on and come off in seconds, so a personal vehicle can double as a work vehicle, and you can move signs between trucks or onto a metal job site trailer. They are waterproof and UV safe, up to 24" high by 60" wide.',
			),
			'uses' => array('Contractor, plumber and electrician trucks', 'Delivery, rideshare and food service vehicles', 'Temporary signs for job site trailers and steel doors', 'Fleet branding that can move between vehicles'),
			'faq' => array(
				array('q' => 'Will it stick to my car?', 'a' => 'Only on steel surfaces. Some newer doors and tailgates are plastic, aluminum or fiberglass. Test the spot with a fridge magnet before you order.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 24" high by 60" wide. Most vehicle doors fit a 12" x 18" to 18" x 24" magnet; measure the flat area between door handles and trim.'),
				array('q' => 'Is it different from a regular car magnet?', 'a' => 'It uses the same 30 mil magnet, with reflective vinyl so it can be read at night. Regular <a href="/product/magnets/">car magnets</a> cost less if night visibility doesn&rsquo;t matter.'),
			),
			'related' => array('magnets', 'dry-erase-magnet', 'reflective-aluminum-sign', 'reflective-coroplast-sign-hstake'),
		),

		'dry-erase-aluminum-sandwich-board' => array(
			'intro' => array(
				'A dry erase aluminum sandwich board gives you a printed, branded sign that you can still write on. Your design is UV printed onto adhesive vinyl, finished with a gloss dry erase coating and mounted on a 1/8" aluminum composite panel, so daily specials, schedules and notes wipe clean while the printed graphics stay put.',
				'Aluminum is waterproof and UV safe, so the board works inside or out for years. It is cut to any size from 3" x 4" up to 48" x 96", single or double sided. Sizes over 46" x 38" (or 48" x 30") are store pickup only.',
			),
			'uses' => array('Restaurant and cafe specials boards', 'Job site, warehouse and production schedules', 'Classroom, gym and office planning boards', 'Event check-in and wayfinding boards'),
			'faq' => array(
				array('q' => 'What markers should I use?', 'a' => 'Standard dry erase markers. The gloss coating wipes clean with a dry eraser or soft cloth.'),
				array('q' => 'Can I leave it outdoors?', 'a' => 'Yes. The aluminum panel and UV coated print are made for long term indoor and outdoor use.'),
				array('q' => 'Can you print lines, grids or a calendar?', 'a' => 'Yes. Anything in your artwork is printed under the dry erase coating, so lines, logos and headings stay while the writing wipes off.'),
			),
			'related' => array('aluminum-sandwich-board', 'reflective-aluminum-sandwich-board', 'dry-erase-coroplast-sign-hstake', 'dry-erase-pvc-board'),
		),

		'foam-board' => array(
			'intro' => array(
				'Foam board is the lightest rigid sign we print: a 3/16" white foamcore panel with your design UV printed directly on the surface. It stays flat, looks sharp up close and is easy to carry, which is why it is the go-to choice for presentations and indoor displays.',
				'Foam board is for indoor use only and is not waterproof. Sizes run up to 48" x 96", single or double sided; anything over 46" x 38" (or 48" x 30") is store pickup only. For signs that go outside, choose <a href="/product/coroplast/">coroplast</a> or <a href="/product/pvc-board/">PVC board</a>.',
			),
			'uses' => array('Presentation boards and easel signs', 'Welcome signs and seating charts for weddings and events', 'Trade show and conference posters', 'In-store promotions and wall signs'),
			'faq' => array(
				array('q' => 'Can foam board be used outdoors?', 'a' => 'No. Foamcore is not waterproof and will warp in rain or humidity. Use coroplast or PVC board outdoors.'),
				array('q' => 'Will it stand on an easel?', 'a' => 'Yes. At 3/16" thick it is rigid enough to stand on a standard easel at poster sizes such as 18" x 24" or 24" x 36".'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 48" x 96". Boards over 46" x 38" or 48" x 30" are store pickup only.'),
			),
			'related' => array('pvc-board', 'coroplast', 'posters', 'canvas-wrap'),
		),

		'canvas-wrap' => array(
			'intro' => array(
				'A canvas wrap turns a photo, logo or artwork into a finished piece of wall art. We print at high resolution on white semi-gloss artist canvas, then hand wrap it around an MDF stretcher frame so the image continues around the edges. No extra frame is needed.',
				'Every canvas ships with a free hanging kit, so it is ready for the wall when it arrives. Use it for family photos and gifts, or for decor in offices, waiting rooms, restaurants and retail stores.',
			),
			'uses' => array('Family, wedding and pet photos', 'Office, lobby and waiting room decor', 'Restaurant and retail wall art', 'Artist and photographer reproductions'),
			'faq' => array(
				array('q' => 'What resolution should my photo be?', 'a' => 'About 150 dpi at the final print size is enough for large format canvas. Phone photos usually print well at smaller sizes.'),
				array('q' => 'Does it come ready to hang?', 'a' => 'Yes. A hanging accessory kit is included with every canvas wrap.'),
				array('q' => 'What is the difference from a framed print?', 'a' => 'A canvas wrap has a textured canvas surface stretched over the frame with no border. A <a href="/product/framed-prints/">framed print</a> puts a flat print inside a wood-style frame.'),
			),
			'related' => array('framed-canvas', 'acrylic-prints', 'framed-prints', 'canvas-roll'),
		),

		'acrylic-prints' => array(
			'intro' => array(
				'An acrylic print gives your image a deep, glossy, modern look. A clear gloss vinyl print is face mounted to the back of a 1/4" plexiglass panel and backed with white vinyl, so colors look bright and saturated through the acrylic.',
				'Chrome standoff hardware and mounting screws are included. The standoffs hold the print 1 inch off the wall so it seems to float. Choose 16" x 24", 20" x 30" or 24" x 36".',
			),
			'uses' => array('Photography and fine art displays', 'Office and reception logo walls', 'Restaurant, salon and hotel decor', 'Premium gifts and portraits'),
			'faq' => array(
				array('q' => 'How is it mounted?', 'a' => 'With the included 1 inch chrome standoffs and screws, through the four corners of the acrylic into the wall.'),
				array('q' => 'Can I order a custom size?', 'a' => 'Not at the moment. Acrylic prints come in 16" x 24", 20" x 30" and 24" x 36".'),
				array('q' => 'Is the print on the front of the acrylic?', 'a' => 'No. It is mounted behind the acrylic, so the plexiglass protects the image and gives it a glossy finish.'),
			),
			'related' => array('canvas-wrap', 'framed-prints', 'framed-canvas', 'pvc-board'),
		),

		'framed-prints' => array(
			'intro' => array(
				'Framed prints come ready for the wall: your image is UV printed on adhesive vinyl, mounted to 3/16" foamcore and set in a natural wood-style frame. The frame is made from recycled polystyrene, so it looks like real wood but weighs much less and is easier to hang.',
				'Choose an image size of 16" x 20", 18" x 24" or 24" x 32". Finished frames measure 21.25" x 25.25", 23.25" x 29.25" and 29.25" x 37.28". Hanging hardware is included.',
			),
			'uses' => array('Office, lobby and hallway art', 'Certificates, posters and menus', 'Photo gifts and home decor', 'Retail and hospitality wall displays'),
			'faq' => array(
				array('q' => 'What size will the finished frame be?', 'a' => 'A 16" x 20" print becomes a 21.25" x 25.25" frame, 18" x 24" becomes 23.25" x 29.25", and 24" x 32" becomes 29.25" x 37.28".'),
				array('q' => 'Is the frame real wood?', 'a' => 'No. It is a wood-style frame made from eco-friendly recycled polystyrene, which keeps it lightweight.'),
				array('q' => 'Is hanging hardware included?', 'a' => 'Yes, every framed print ships with hanging hardware.'),
			),
			'related' => array('canvas-wrap', 'acrylic-prints', 'framed-canvas', 'magnetic-wood-frame-hanger'),
		),

		'x-stand-graphic-stand' => array(
			'intro' => array(
				'The X-stand is our most affordable banner stand. Its aluminum and fiberglass frame forms an X behind the graphic, and the banner hooks on by its grommets. That makes it the easiest stand for swapping graphics: unhook one banner and hook on the next.',
				'Choose a 24" x 63" or 32" x 71" graphic, printed on 13 oz matte vinyl or 14 mil double white popup material. Double white popup has a brighter white and stays flat at the edges. A nylon carry bag is included.',
			),
			'uses' => array('Trade show booths on a budget', 'Lobby, reception and in-store promotions', 'Seasonal signs you change often', 'Church, school and community events'),
			'faq' => array(
				array('q' => 'Which material should I choose?', 'a' => 'We recommend 14 mil double white popup: brighter colors and edges that won&rsquo;t curl. 13 oz matte vinyl is the lower-cost, more flexible option.'),
				array('q' => 'Can I buy replacement graphics?', 'a' => 'Yes. Graphics attach with grommets, so a new print hooks onto the same stand in seconds. Order the graphic only option.'),
				array('q' => 'How does it compare to a retractable stand?', 'a' => 'An X-stand costs less and makes graphic swaps easy. A <a href="/product/standard-retractable-insert-stand/">retractable stand</a> rolls the banner into its base for faster setup and better protection on the road.'),
			),
			'related' => array('standard-retractable-insert-stand', 'sd-retractable-sd-retractable-insert-stand', 'deluxe-retractable-insert-stand', 'table-top-banner-stand'),
		),

		'sd-retractable-sd-retractable-insert-stand' => array(
			'intro' => array(
				'The SD retractable is our top of the line roll-up banner stand. Its wide, low-profile base is very stable, finished in brushed aluminum with chrome end caps. The support pole adjusts, so you can run a shorter or taller graphic in the same stand.',
				'Graphics are UV printed on 8.85 oz coated polyester fabric with a blockout backing (no curl, no show-through), or on 13 oz matte vinyl. The banner rolls into the base when you pack up, and the whole stand fits in its hard-sided travel case.',
			),
			'uses' => array('Trade shows and conferences', 'Corporate lobbies and reception areas', 'Retail and bank promotions', 'Sponsor and step-in signs at events'),
			'faq' => array(
				array('q' => 'What safety margins should my artwork have?', 'a' => 'Keep important text and logos 1" from the top and 3" from the bottom, where the graphic meets the rail and the base.'),
				array('q' => 'Fabric or vinyl?', 'a' => 'Fabric has a premium look, no glare and edges that won&rsquo;t curl. Vinyl costs less. Both are UV printed.'),
				array('q' => 'Can I add a light?', 'a' => 'Yes. The <a href="/product/led-light-for-banner-stand/">LED light for banner stands</a> fits the SD, Standard and Deluxe retractables.'),
			),
			'related' => array('deluxe-retractable-insert-stand', 'standard-retractable-insert-stand', 'led-light-for-banner-stand', 'sd-retractable-hardware-only'),
		),

		'standard-retractable-hardware-only' => array(
			'intro' => array(
				'This is the Standard retractable banner stand without a graphic. Use it to replace a worn stand, add stands to your fleet, or reuse banners you already have. The banner rolls into the aluminum base when you are done and comes out in seconds at the next event.',
				'The stand is lightweight and comes with a carrying case. It is hardware only; to order a printed banner with it, choose the <a href="/product/standard-retractable-insert-stand/">Standard Retractable with graphic</a>, or order a replacement graphic on its own.',
			),
			'uses' => array('Replacing a damaged or lost stand', 'Adding stands for multi-location events', 'Reusing existing retractable banners', 'Building a stand kit for sales teams'),
			'faq' => array(
				array('q' => 'Is a banner included?', 'a' => 'No. This listing is the stand and carrying case only.'),
				array('q' => 'Can I order a graphic for it later?', 'a' => 'Yes. Order the Standard Retractable graphic only option and it will fit this stand.'),
				array('q' => 'Can it take a light?', 'a' => 'Yes, the <a href="/product/led-light-for-banner-stand/">LED light for banner stands</a> works with the Standard retractable.'),
			),
			'related' => array('standard-retractable-insert-stand', 'deluxe-retractable-hardware-only', 'sd-retractable-hardware-only', 'led-light-for-banner-stand'),
		),

		'led-light-for-banner-stand' => array(
			'intro' => array(
				'A banner stand light makes your graphic stand out in a dim convention hall or a busy lobby. This aluminum LED light gives 450 lumens from just 3 watts, clips to the top of the stand and aims light down across the print.',
				'It measures 2" wide by 14" high, weighs 1.25 lb and installs in seconds. It fits our Deluxe, Standard and SD retractable banner stands.',
			),
			'uses' => array('Trade show booths with poor overhead lighting', 'Evening events and galas', 'Retail displays that need to catch the eye', 'Lobby and reception banner stands'),
			'faq' => array(
				array('q' => 'Which stands does it fit?', 'a' => 'The Deluxe Retractable, Standard Retractable and SD Retractable banner stands.'),
				array('q' => 'How bright is it?', 'a' => '450 lumens from a 3 watt LED, enough to light the top of a full-size banner.'),
				array('q' => 'Does it add much weight?', 'a' => 'No, the light weighs only 1.25 lb.'),
			),
			'related' => array('sd-retractable-sd-retractable-insert-stand', 'standard-retractable-insert-stand', 'deluxe-retractable-insert-stand', 'x-stand-graphic-stand'),
		),

		'tension-fabric-stand-hardware-only' => array(
			'intro' => array(
				'This is the frame for our tension fabric stand without the printed fabric. The interlocking aluminum tubes snap together without tools, and a heavy-duty base plate keeps the display steady. A soft canvas carry bag is included.',
				'Choose 36" W x 90" H (22 lb) or 48" W x 90" H (28 lb) to match your fabric insert. Order this to replace a frame or add a stand, then pair it with the matching 36" x 90" or 48" x 90" fabric graphic.',
			),
			'uses' => array('Replacing or adding frames for existing fabric graphics', 'Trade show backdrops and booth walls', 'Lobby and store entrance displays', 'Photo backdrops at events'),
			'faq' => array(
				array('q' => 'Is the fabric graphic included?', 'a' => 'No. This is the frame, base plate and carry bag only. Order the <a href="/product/tension-fabric-stand-graphic-frame/">tension fabric stand with graphic</a> for a complete display.'),
				array('q' => 'Which size do I need?', 'a' => 'Match your graphic: the 36" x 90" frame takes the 36" x 90" insert, and the 48" x 90" frame takes the 48" x 90" insert.'),
				array('q' => 'Do I need tools to set it up?', 'a' => 'No, the tubes interlock by hand.'),
			),
			'related' => array('tension-fabric-stand-graphic-frame', 'straight-tension-fabric-displays-graphic-frame', 'curved-tension-fabric-display-graphic-frame', 'sd-retractable-sd-retractable-insert-stand'),
		),

		'straight-velcro-fabric-pop-up-display-graphic-frame' => array(
			'intro' => array(
				'A straight Velcro pop-up display gives you a full trade show back wall that sets up in minutes. The accordion-style aluminum frame expands in one motion, and the dye sublimated 8.8 oz tension fabric graphic attaches with sewn-on Velcro. The fabric wraps around the sides too, so the ends of the wall are covered.',
				'Choose 8 ft (89" W x 89" H x 12" D) or 10 ft (118" W x 89" H x 12" D). Each ships with a soft canvas bag at 27 or 31 lb, or you can upgrade to a durable plastic hard case.',
			),
			'uses' => array('10 x 10 and 10 x 20 trade show booths', 'Media walls and step-and-repeat backdrops', 'Product launches and in-store events', 'Job fairs and conferences'),
			'faq' => array(
				array('q' => 'Straight or curved?', 'a' => 'The straight wall is only 12" deep and gives you the widest flat graphic. The <a href="/product/curved-velcro-fabric-pop-up-display-graphic-frame/">curved version</a> is 24" deep and wraps slightly around your booth.'),
				array('q' => 'What graphic size do I design for?', 'a' => 'The graphic wraps around the sides: 116.5" W x 89.5" H for the 8 ft and 145" W x 89.5" H for the 10 ft. Use our templates to place the front panel and side returns.'),
				array('q' => 'How heavy is it to travel with?', 'a' => '27 lb (8 ft) or 31 lb (10 ft) in the soft bag. With the podium and LED lights it weighs about 60 to 66 lb.'),
			),
			'related' => array('curved-velcro-fabric-pop-up-display-graphic-frame', 'straight-tension-fabric-displays-graphic-frame', '10ft-seg-backlit-popup-display-graphic-frame', 'step-and-repeat-backdrop-graphic-frame'),
		),

		'curved-velcro-fabric-pop-up-display-graphic-frame' => array(
			'intro' => array(
				'A curved Velcro pop-up display gives your booth a gentle wraparound back wall that frames whoever stands in front of it. The accordion aluminum frame pops open in one motion, and the dye sublimated 8.8 oz tension fabric attaches with sewn-on Velcro and wraps the sides for clean ends.',
				'Choose 8 ft (90" W x 89" H x 24" D) or 10 ft (112" W x 89" H x 24" D). It ships in a soft canvas bag (27 or 31 lb) with an optional plastic hard case.',
			),
			'uses' => array('Trade show and expo booths', 'Photo and interview backdrops', 'Retail pop-ups and brand activations', 'Conference stages and registration areas'),
			'faq' => array(
				array('q' => 'Curved or straight?', 'a' => 'The curve gives a more dimensional, enclosing look and takes 24" of depth. The <a href="/product/straight-velcro-fabric-pop-up-display-graphic-frame/">straight version</a> is just 12" deep and slightly wider.'),
				array('q' => 'What graphic size do I design for?', 'a' => '108" W x 89.5" H for the 8 ft and 134" W x 89.5" H for the 10 ft, including the side wraps. Use our templates for exact panel positions.'),
				array('q' => 'Is a case included?', 'a' => 'A soft canvas bag is included. A durable plastic hard case is optional.'),
			),
			'related' => array('straight-velcro-fabric-pop-up-display-graphic-frame', 'curved-tension-fabric-display-graphic-frame', '10ft-seg-backlit-popup-display-graphic-frame', 'step-and-repeat-backdrop-graphic-frame'),
		),

		'10ft-seg-backlit-popup-display-graphic-frame' => array(
			'intro' => array(
				'A backlit SEG pop-up display lights your graphic from inside. LED lights in the aluminum pop-up frame shine through 6 oz premium backlit fabric, so colors glow even in a dark exhibit hall. SEG (silicone edge graphic) fabric presses into a channel in the frame for a tight, seamless, frameless look.',
				'Assembled, it measures 117.3" W x 87.8" H x 15" D, a full back wall for a 10 ft booth. Single sided orders include blackout fabric for the back. It ships in two boxes (44 lb and 13 lb).',
			),
			'uses' => array('Trade shows where you need to stand out from neighbors', 'Retail and mall promotions', 'Corporate events and product launches', 'Lobby feature walls'),
			'faq' => array(
				array('q' => 'What does SEG mean?', 'a' => 'Silicone edge graphic: a thin silicone strip is sewn around the fabric and pushed into a groove in the frame, which stretches the print tight with no visible hardware.'),
				array('q' => 'Is the lighting included?', 'a' => 'Yes, the interior LED lights are part of the display. That is what makes it backlit.'),
				array('q' => 'What size should my artwork be?', 'a' => 'Download the template for this display; the graphic is larger than the visible wall because it wraps into the frame.'),
			),
			'related' => array('8ft-seg-backlit-popup-display-graphic-frame', '10ft-seg-backlit-fabric-display-graphic-frame', '10ft-seg-fabric-display-graphic-frame', 'straight-velcro-fabric-pop-up-display-graphic-frame'),
		),

		'feather-convex-flag-pole' => array(
			'intro' => array(
				'Feather flags are tall, narrow advertising flags that move in the wind and catch the eye of passing drivers. The convex feather flag has a curved bottom edge for a sweeping shape and stays readable from the road. It is dye sublimated on 4 oz polyester mesh and is machine washable.',
				'Four sizes: Small 9 ft, Medium 10.5 ft, Large 14 ft and X-Large 18 ft assembled. The aluminum and graphite pole set interlocks without tools. ' . $flag_bases,
				$print_thru,
			),
			'uses' => array('Storefronts, car dealers and grand openings', 'Roadside sales, open houses and pop-up shops', 'Trade show entrances and festival booths', 'Sports events and farmers markets'),
			'faq' => array(
				array('q' => 'Which size should I choose?', 'a' => 'Small (9 ft) and Medium (10.5 ft) suit sidewalks and indoor use. Large (14 ft) and X-Large (18 ft) are for roadsides and parking lots where people see them from a distance.'),
				array('q' => 'Which base do I need?', 'a' => $flag_bases),
				array('q' => 'Single or double sided?', 'a' => $print_thru),
			),
			'related' => array('teardrop-flag-pole', 'feather-angled-flag-pole', 'econo-feather-flag-pole', 'ground-stake'),
		),

		'teardrop-flag-pole' => array(
			'intro' => array(
				'Teardrop flags have a rounded, iconic shape that looks clean from any angle and gives you a wide area for a logo. The full color graphic is dye sublimated on 4 oz polyester mesh, so it is light, washable and lets the wind through.',
				'Four sizes: Small 7 ft, Medium 9 ft, Large 11.2 ft and X-Large 13.5 ft. The X-Large graphic is 44.95" x 140.75". The black aluminum and fiberglass pole set goes together without tools. ' . $flag_bases,
				$print_thru,
			),
			'uses' => array('Storefronts and sidewalk advertising', 'Trade show booths and indoor lobbies', 'Golf tournaments, races and sports events', 'Grand openings and sales events'),
			'faq' => array(
				array('q' => 'Teardrop or feather flag?', 'a' => 'A teardrop is shorter and wider, good for logos and short text. A <a href="/product/feather-convex-flag-pole/">feather flag</a> is taller and narrower, good for words read from the road.'),
				array('q' => 'Which base do I need?', 'a' => $flag_bases),
				array('q' => 'Can I buy just the flag?', 'a' => 'Yes. If you already have a pole set, order the flag only version in the same size.'),
			),
			'related' => array('feather-convex-flag-pole', 'feather-angled-flag-pole', 'rectangle-flag-pole', 'carrying-bag'),
		),

		'rectangle-flag-pole' => array(
			'intro' => array(
				'Rectangle flags give you the biggest, simplest design area of any advertising flag: a tall rectangle that is easy to lay out for text, logos and photos. Every graphic is 31" wide, dye sublimated on 4 oz polyester mesh, and machine washable.',
				'Three sizes: Small 8.5 ft (31" x 90.5" graphic), Medium 11.8 ft (31" x 114") and Large 15 ft (31" x 151.5"). The brushed aluminum pole set goes together without tools. ' . $flag_bases,
				$print_thru,
			),
			'uses' => array('Car lots, gas stations and roadside businesses', 'Real estate developments and model homes', 'Festivals, fairs and outdoor markets', 'Trade show and showroom entrances'),
			'faq' => array(
				array('q' => 'Why pick a rectangle over a feather or teardrop?', 'a' => 'A rectangle has straight edges and keeps the full width all the way up, so long words and photos fit without being cropped by a curve.'),
				array('q' => 'Which base do I need?', 'a' => $flag_bases),
				array('q' => 'Does the carry bag fit rectangle flags?', 'a' => 'No, the flag carry bag is sized for teardrop and feather flags.'),
			),
			'related' => array('feather-convex-flag-pole', 'teardrop-flag-pole', 'custom-pole-flag-pole', 'ground-stake'),
		),

		'ground-stake' => array(
			'intro' => array(
				'The ground stake is the most popular base for feather, teardrop and rectangle flags. It is zinc coated steel, so it resists rust. Drive it into soft ground and slide the flag&rsquo;s bottom pole onto the connector.',
				'A bearing in the top lets the flag turn with the wind, which keeps the graphic facing out and reduces strain on the pole. The stake is 25.6" tall and weighs 3.4 lb.',
			),
			'uses' => array('Lawns, roadsides and grass medians', 'Beaches, sand and soil at outdoor events', 'Farm stands, golf courses and parks', 'Replacing a lost or bent stake'),
			'faq' => array(
				array('q' => 'Which flags does it fit?', 'a' => 'Teardrop, feather convex, feather angled and rectangle flags.'),
				array('q' => 'Can I use it on concrete?', 'a' => 'No. For hard surfaces use the <a href="/product/cross-base/">cross base</a> (with a water bag outdoors) or the <a href="/product/square-base/">square base</a>.'),
				array('q' => 'Does the flag spin?', 'a' => 'Yes. The bearing lets the flag rotate freely in the wind.'),
			),
			'related' => array('cross-base', 'square-base', 'carrying-bag', 'feather-convex-flag-pole'),
		),

		'carrying-bag' => array(
			'intro' => array(
				'A flag carrying bag keeps your flag, poles and base together between events. It is premium black polyester with interior pockets to organize the flag and accessories, an adjustable shoulder strap and two side handles.',
				'Two sizes: S/M (36" L x 10" W, 1 lb) for small and medium flags, and L/XL (48" L x 10" W, 1.5 lb) for large and X-large flags. It fits teardrop, feather convex and feather angled flags. It does not fit econo or rectangle flags or the square base.',
			),
			'uses' => array('Traveling to trade shows and events', 'Storing flags between seasons', 'Keeping poles and stakes from getting lost', 'Sales teams that set up at different sites'),
			'faq' => array(
				array('q' => 'Which size do I need?', 'a' => 'Match your flag: S/M for small and medium flags, L/XL for large and X-large flags.'),
				array('q' => 'Will my base fit?', 'a' => 'The ground stake and cross base fit. The steel square base does not.'),
				array('q' => 'Does it fit rectangle flags?', 'a' => 'No, it is made for teardrop, feather convex and feather angled flags.'),
			),
			'related' => array('ground-stake', 'cross-base', 'teardrop-flag-pole', 'feather-angled-flag-pole'),
		),

		'pole-banner-set-banner-hardware' => array(
			'intro' => array(
				'Pole banners, also called light pole or street pole banners, hang from street lights and posts to brand a whole street, campus or shopping center. Each banner is UV printed front and back on one piece of 18 oz blockout vinyl, so both sides read correctly and light doesn&rsquo;t show through.',
				'Choose 18", 24" or 30" wide and any height from 24" to 96". Banners have double-stitched 2" pole pockets with no hem, a brass grommet at each corner seam, and optional wind slits. The hardware kit adds 2 fiberglass pole arms, 2 brackets and steel bands for installation.',
			),
			'uses' => array('Downtown and main street districts', 'Shopping centers and business parks', 'College campuses, schools and hospitals', 'Seasonal and holiday street decorations'),
			'faq' => array(
				array('q' => 'Are they double sided?', 'a' => 'Yes. Each banner is one piece of blockout vinyl printed on both sides.'),
				array('q' => 'Do I need the hardware kit?', 'a' => 'Choose banner and hardware if your poles don&rsquo;t have arms yet. Choose banner only if you are replacing banners on existing brackets.'),
				array('q' => 'Should I add wind slits?', 'a' => 'Consider them for exposed, windy locations; they let some air pass through to reduce strain on the brackets.'),
			),
			'related' => array('18oz-blockout-banner', '13oz-vinyl-banner', 'mesh-banner', 'banner-a-banner-frame'),
		),

		'banner-a-banner-frame' => array(
			'intro' => array(
				'A banner A-frame puts a vinyl banner on a free-standing aluminum frame, so you can advertise anywhere without walls, fences or poles. The bars connect without tools, and the grommeted banner attaches with hooked bungee cords that keep it tight.',
				'Show one graphic on the front or two for double the impact. Choose the 4 ft frame (45" x 33" graphic) or the 8 ft frame (94" x 33" graphic). Banners are UV printed on 13 oz matte vinyl, hemmed on all sides, with 4 grommets at the top and bottom. It works indoors and out.',
			),
			'uses' => array('Outdoor events, races and sports fields', 'Sidewalk and parking lot promotions', 'Trade show and festival booths', 'Church, school and community signs'),
			'faq' => array(
				array('q' => 'Can I show two different designs?', 'a' => 'Yes. Order two graphics and hang one on each side of the frame.'),
				array('q' => 'Which size should I choose?', 'a' => 'The 4 ft frame fits sidewalks and booths. The 8 ft frame gives you a long 94" x 33" banner for sports fields, fence lines and event entrances.'),
				array('q' => 'Can I order replacement banners?', 'a' => 'Yes. Choose the banner only option to reuse your frame with a new design.'),
			),
			'related' => array('13oz-vinyl-banner', 'deluxe-signicade-graphic-frame', 'snap-poster-graphic-hanger', 'pole-banner-set-banner-hardware'),
		),

		'deluxe-signicade-graphic-frame' => array(
			'intro' => array(
				'The Signicade Deluxe is a heavy-duty plastic sidewalk A-frame built for quick graphic changes. Each side has a recessed 24" x 36" area, and rigid signs slide in and out, so you can switch promotions in seconds without tools.',
				'Graphics are 4mm white coroplast, UV printed for a durable matte finish. Order one insert for the front only or two for both sides. The board stands 46" H x 27" W x 3" D and weighs 19 lb, heavy enough to stay put on a busy sidewalk.',
			),
			'uses' => array('Restaurant, cafe and retail sidewalk signs', 'Real estate open houses', 'Salon, gym and service business promotions', 'Event and parking direction signs'),
			'faq' => array(
				array('q' => 'What size are the inserts?', 'a' => '24" wide by 36" tall, printed on 4mm white coroplast.'),
				array('q' => 'Can I change the signs myself?', 'a' => 'Yes. The inserts slide into the recessed panels, so you can swap them as often as you like.'),
				array('q' => 'Will it blow over?', 'a' => 'At 19 lb with a wide base, it is much steadier than lightweight A-frames, but in strong wind bring it inside.'),
			),
			'related' => array('standard-signicade-white-frame-graphic', 'reflective-signicade-a-frame', 'dry-erase-signicade-a-frame', 'banner-a-banner-frame'),
		),

		'snap-poster-graphic-hanger' => array(
			'intro' => array(
				'The snap poster hanger is the quickest way to hang a poster in a window or from a ceiling. Its aluminum snap bars open, take the graphic and snap shut, gripping it firmly without pockets, holes or special finishing. Changing a poster takes seconds.',
				'Each set includes 2 snap bars, 4 end caps and 2 universal hanging clips, plus a graphic printed on 14 mil double white popup material if you order one. Double sided orders arrive as two graphics to mount back to back. It is for indoor use.',
			),
			'uses' => array('Storefront window displays', 'Point of purchase signs above shelves and counters', 'Menu boards and promotions in cafes', 'Office, school and gallery displays'),
			'faq' => array(
				array('q' => 'How do I change the poster?', 'a' => 'Open the snap bars, slide out the old graphic, insert the new one and snap the bars closed.'),
				array('q' => 'Can it hang in a window facing both ways?', 'a' => 'Yes. Order double sided and you will receive two graphics to mount back to back in the hanger.'),
				array('q' => 'Can I use it outdoors?', 'a' => 'It is designed for indoor locations, including inside windows.'),
			),
			'related' => array('poster-graphic-stand', 'posters', 'magnetic-wood-frame-hanger', 'x-stand-graphic-stand'),
		),

		'real-estate-a-frame-sign' => array(
			'intro' => array(
				'A real estate A-frame is a black steel sign frame that stands on its own on a lawn, sidewalk or driveway. It holds a 24" x 18" double sided coroplast sign, and a rider clamp lets you add a second sign such as "Open House", "Sold" or an agent name.',
				'Hardware includes the steel A-frame and zip ties. Options include a 24" x 6" double sided coroplast rider and a red open house pennant flag with pole. The front and back of each sign can have different artwork.',
			),
			'uses' => array('Open house and for sale signs', 'Directional signs from main roads to a listing', 'New construction and leasing signs', 'Agent and brokerage branding'),
			'faq' => array(
				array('q' => 'What size sign does it hold?', 'a' => 'A 24" x 18" double sided coroplast sign, plus an optional 24" x 6" rider.'),
				array('q' => 'Can each side be different?', 'a' => 'Yes. Hanging signs and riders are double sided, and each side can have its own artwork.'),
				array('q' => 'Do I need stakes?', 'a' => 'No. The A-frame stands on its own, so it works on pavement as well as grass.'),
			),
			'related' => array('real-estate-frame-sign', 'real-estate-post-sign', 'yard-sign-and-h-stake', 'coroplast'),
		),
	);
}

/**
 * Guide for a product page, falling back to the guide of the complete kit a
 * partial variant canonicalizes to. Empty array when there is none.
 */
function wholesale_product_guide($product_id)
{
	$guides = wholesale_product_guides();
	$slug = (string) get_post_field('post_name', $product_id);

	if (!isset($guides[$slug]) && ($primary = wholesale_seo_product_canonical_of($product_id))) {
		$slug = (string) get_post_field('post_name', $primary);
	}

	return isset($guides[$slug]) ? $guides[$slug] : array();
}

/**
 * Register the guide FAQ before the head prints, for FAQPage structured data.
 */
function wholesale_product_guide_register_faq()
{
	if (!is_singular('product') || !empty($GLOBALS['wholesale_page_faq'])) {
		return;
	}

	$guide = wholesale_product_guide(get_queried_object_id());
	if (!empty($guide['faq'])) {
		wholesale_seo_set_page_faq($guide['faq']);
	}
}
add_action('wp', 'wholesale_product_guide_register_faq');

/**
 * Print the buying guide section on a product page.
 */
function wholesale_render_product_guide($product_id)
{
	$guide = wholesale_product_guide($product_id);
	if (!$guide) {
		return;
	}

	$name = wholesale_seo_product_name($product_id);
	$related = array();
	foreach (isset($guide['related']) ? $guide['related'] : array() as $slug) {
		$related_product = get_page_by_path($slug, OBJECT, 'product');
		if ($related_product && 'publish' === $related_product->post_status && (int) $related_product->ID !== (int) $product_id) {
			$related[] = $related_product;
		}
	}
	?>
	<section class="product-guide" aria-labelledby="product-guide-title">
		<h2 id="product-guide-title"><?php echo esc_html(sprintf('%s: Buying Guide', $name)); ?></h2>
		<?php foreach ($guide['intro'] as $paragraph) : ?>
			<p><?php echo wp_kses_post($paragraph); ?></p>
		<?php endforeach; ?>

		<?php if (!empty($guide['uses'])) : ?>
			<h3>Popular uses</h3>
			<ul>
				<?php foreach ($guide['uses'] as $use) : ?>
					<li><?php echo esc_html($use); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if (!empty($GLOBALS['wholesale_page_faq'])) : ?>
			<h3>Questions &amp; answers</h3>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		<?php endif; ?>

		<?php if ($related) : ?>
			<h3>Often compared with</h3>
			<ul>
				<?php foreach ($related as $related_product) : ?>
					<li><a href="<?php echo esc_url(get_permalink($related_product)); ?>"><?php echo esc_html(wholesale_seo_product_name($related_product->ID)); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
}
