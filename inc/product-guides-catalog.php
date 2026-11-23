<?php
/**
 * Buying guides for the rest of the catalog: banners, fabric displays, table
 * covers, rigid signs, adhesive vinyl, flags, tents, wall art and sign holders.
 *
 * Same shape and rules as wholesale_product_guides() in product-guides.php,
 * which merges these in (its own entries win on a shared slug). Every fact
 * repeats the product's own spec sheet; no turnaround, warranty, "made in" or
 * install claims, because those differ by product and some are unverified.
 */

/**
 * @return array
 */
function wholesale_product_guides_catalog()
{
	$flag_print = 'Flags are dye sublimated on 4 oz polyester mesh. A single sided flag is printed through, so the back shows a lighter, mirrored but readable image; choose the reverse version to put the pole sleeve on the right. Double sided flags are two prints sewn back to back with a silver block out layer, so both sides read correctly.';
	$flag_bases = 'The aluminum ground stake (3.6 lbs, with a ball bearing so the flag turns with the wind) goes into grass, sand or soil. For concrete, asphalt or indoor floors, choose the 6.73 lb aluminum cross base, add the water bag for extra weight, or use the 21.83 lb steel square base.';
	$oversize = 'Sizes over 46" x 38" (or 48" x 30") are too big to ship by parcel, so call 866-436-2101 before ordering one.';
	$banner_finishing = 'Hems are free, and you can add grommets, pole pockets, Velcro, webbing with D-rings, sewn-in rope or reinforced corners.';

	return array(

		/* Banners */

		'13oz-vinyl-banner' => array(
			'intro' => array(
				'A 13oz vinyl banner is the everyday workhorse of business advertising: strong, affordable and ready for indoor or outdoor use. It is printed with UV inks on matte scrim vinyl, which has woven fibers inside that resist tearing, and the matte surface keeps sun glare off your message so it reads clearly from the street.',
				'Every banner is cut to the size you enter. ' . $banner_finishing . ' Single piece banners go up to 10\' x 145\' (9.5\' x 145\' with pole pockets); larger banners are welded together. Banners 89" or larger on their shortest side ship folded, smaller ones ship rolled.',
			),
			'uses' => array('Grand opening, sale and now hiring banners', 'Storefront, fence and building banners', 'Event, festival and sponsor banners', 'Indoor banners for stores, gyms and schools'),
			'faq' => array(
				array('q' => 'Should I choose 13oz or 18oz vinyl?', 'a' => 'Choose 13oz for most single sided banners. Choose the 18oz blockout banner when both sides will be seen or the banner hangs in front of a window or light, because its blockout layer stops one side showing through the other.'),
				array('q' => 'Can a 13oz banner go outside?', 'a' => 'Yes. It is waterproof and UV safe. On open fences or building sides where wind is strong, a mesh banner is the better choice because wind passes through it.'),
				array('q' => 'Do I need grommets?', 'a' => 'Hems are free and grommets are optional. Add grommets to tie or hook the banner up, or pole pockets to slide it onto a pole.'),
			),
			'related' => array('18oz-blockout-banner', 'mesh-banner', 'indoor-banner-super-smooth', 'standard-retractable-insert-stand'),
		),

		'18oz-blockout-banner' => array(
			'intro' => array(
				'The 18oz blockout banner is our heavy weight vinyl. PVC vinyl faces on the front and back sandwich a blockout layer, and scrim fibers reinforce the whole sheet, so it stands up to tough outdoor conditions and stops light, and the opposite side, from showing through.',
				'That makes it the right banner for double sided jobs: both sides are printed on one single-ply banner and each reads cleanly. It is the usual material for street and light pole banners. Single sided banners go up to 10\' x 145\' (9.5\' x 145\' with pole pockets) and can be welded larger; double sided banners go up to 9.5\' x 145\'. ' . $banner_finishing,
			),
			'uses' => array('Double sided street and light pole banners', 'Banners hung in windows or in front of lights', 'Long term building and fence banners', 'Ceiling banners seen from both sides'),
			'faq' => array(
				array('q' => 'Why choose a blockout banner?', 'a' => 'A regular banner lets some light through, so a banner in front of a window, or one with print on both sides, can show a shadow of the other side. The blockout layer prevents that.'),
				array('q' => 'Can a double sided banner be oversized?', 'a' => 'No. Double sided banners go up to 9.5\' x 145\'. Welded oversized banners are single sided only.'),
				array('q' => 'How is it shipped?', 'a' => 'Banners 89" or larger on their shortest side ship folded; banners 88" and under ship rolled.'),
			),
			'related' => array('13oz-vinyl-banner', 'mesh-banner', 'pole-banner-set-banner-hardware', 'blockout-fabric-banner'),
		),

		'mesh-banner' => array(
			'intro' => array(
				'A mesh banner is printed on 10oz vinyl mesh with a 70/30 perforation, so wind blows through the open holes instead of pushing against the banner. It stays flatter, puts less strain on grommets and ties, and is the safer choice for fences, scaffolding and building sides where a solid banner would catch the wind like a sail.',
				'Graphics are UV printed in full color and read clearly from a distance. Single sided banners go up to 10\' x 145\' (9.5\' x 145\' with pole pockets) and larger ones are welded together; double sided mesh banners are two banners sewn back to back. ' . $banner_finishing,
			),
			'uses' => array('Construction fence and scaffolding banners', 'Sports field and stadium fence banners', 'Large outdoor banners in windy locations', 'Event and festival perimeter banners'),
			'faq' => array(
				array('q' => 'When should I choose mesh instead of 13oz vinyl?', 'a' => 'Choose mesh for banners in open, windy places such as fences, scaffolding and building sides. Choose 13oz vinyl for storefronts, indoor spaces and sheltered spots.'),
				array('q' => 'What finishing do you recommend?', 'a' => 'For street banners, large banners and long term installs, add webbing and reinforced corners, and use every grommet when you hang the banner so it is not damaged.'),
				array('q' => 'How is a double sided mesh banner made?', 'a' => 'Two banners are printed and sewn back to back.'),
			),
			'related' => array('13oz-vinyl-banner', '18oz-blockout-banner', 'coroplast'),
		),

		'backlit-banner' => array(
			'intro' => array(
				'A backlit banner is made for light boxes. It is UV printed on 15oz backlit vinyl, a translucent banner material that lets light pass through the print, so your graphic glows when the box is lit instead of looking flat.',
				'Choose a day time or night time color profile for when the banner is seen most, and enter any size up to 120" x 150\'. It is single sided, with free hemming and grommets and optional pole pockets or webbing with D-rings for mounting in large frames.',
			),
			'uses' => array('Large light boxes and illuminated display cabinets', 'Backlit storefront and mall displays', 'Lit menu and promotional displays', 'Replacement graphics for existing light boxes'),
			'faq' => array(
				array('q' => 'Which color profile should I pick?', 'a' => 'Pick the profile for when the banner is seen most: the night time profile is set up to look right with the light box on, the day time profile for viewing in daylight.'),
				array('q' => 'What is the difference between a backlit banner and backlit film?', 'a' => 'The backlit banner is flexible 15oz vinyl for large light boxes, up to 120" x 150\'. Backlit film is a 10 mil translucent film for smaller lit displays such as menu boards and poster light boxes, up to 59" high.'),
				array('q' => 'Can it be double sided?', 'a' => 'No, backlit banners are single sided.'),
			),
			'related' => array('backlit-film', 'adhesive-translucent-vinyl', '13oz-vinyl-banner'),
		),

		'blockout-fabric-banner' => array(
			'intro' => array(
				'Blockout fabric banners are dye sublimated on 9.5oz, 3-ply polyester: white on the front and back with a blockout layer in between. The low sheen surface stops camera flash and glare, which makes it a favorite for photo backdrops, and the blockout layer keeps lights behind the banner from showing through your graphic.',
				'Fabric gives a softer, more upscale look than vinyl. Order any size up to 8\' x 150\', single sided. Hems are free in white or black thread to match your design, and grommets, pole pockets, sewn-on Velcro or flush heat-cut edges are available.',
			),
			'uses' => array('Photo backdrops and step and repeat walls', 'Stage, event and wedding backdrops', 'Hanging banners in front of windows or lights', 'Indoor retail and trade show banners'),
			'faq' => array(
				array('q' => 'Why choose blockout fabric?', 'a' => 'The blockout layer stops light behind the banner from shining through, so colors stay even in front of windows, stage lights or a bright wall.'),
				array('q' => 'Can it be double sided or oversized?', 'a' => 'No. Blockout fabric banners are single sided, up to 8\' x 150\', and are not welded into oversized pieces.'),
				array('q' => 'Fabric or vinyl?', 'a' => 'Fabric photographs without glare and looks more polished up close. Vinyl banners are tougher outdoors and can be welded into larger sizes.'),
			),
			'related' => array('fabric-banner-9oz-wrinkle-free-copy', 'step-and-repeat-backdrop-graphic-frame', 'tension-fabric', '18oz-blockout-banner'),
		),

		'fabric-banner-9oz-wrinkle-free-copy' => array(
			'intro' => array(
				'This 9oz wrinkle free fabric banner is dye sublimated on an extra bright "Triple White" polyester, so prints come out vivid. The fabric resists wrinkles and has a low sheen that keeps glare off your graphic under lights and cameras.',
				'It is the same wrinkle free fabric we use on our step and repeat backdrop, and it suits photo backdrops, tapestries, table top displays and elegant indoor banners. It is single sided, in any size up to 8\' x 100\'. Hems are free in white or black thread, with grommets, pole pockets, Velcro or flush heat-cut edges as options.',
			),
			'uses' => array('Photo backdrops and selfie walls', 'Indoor event and stage banners', 'Tapestries and wall hangings', 'Table top and retail displays'),
			'faq' => array(
				array('q' => 'Will it wrinkle?', 'a' => 'It is made to resist wrinkles and hang smooth, which is why we also use it on our step and repeat backdrops.'),
				array('q' => 'How is it different from the blockout fabric banner?', 'a' => 'This fabric has no blockout layer, so strong light behind it can show through. Choose the 9.5oz blockout fabric banner when windows or lights are behind the banner.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 8\' x 100\', single sided. Oversized (joined) prints are not available.'),
			),
			'related' => array('blockout-fabric-banner', 'step-and-repeat-backdrop-graphic-frame', 'tension-fabric', 'indoor-banner-super-smooth'),
		),

		'indoor-banner-super-smooth' => array(
			'intro' => array(
				'The super smooth indoor banner is UV printed on 14 mil Double White Popup, a smooth PVC sheet with white faces, so photos and fine text look sharp even when people stand right next to it. The material has low memory, so it hangs flat without creases after being rolled.',
				'Edges are flush cut with no hems for a clean finish, and grommets are free if you want them. It is single sided, up to 52" x 100\'. It is made for close-up indoor viewing and also holds up outdoors.',
			),
			'uses' => array('Retail and point of purchase banners', 'Lobby, office and school banners', 'Trade show and conference banners', 'Hanging banners seen up close'),
			'faq' => array(
				array('q' => 'How is it different from a 13oz vinyl banner?', 'a' => 'It has a smoother surface for close viewing and flush cut edges. Choose 13oz vinyl for everyday outdoor banners that need hems, pole pockets or heavier finishing.'),
				array('q' => 'Does it have hems?', 'a' => 'No. It is flush cut for a clean edge; grommets are optional and free.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 52" high by 100\' long.'),
			),
			'related' => array('13oz-vinyl-banner', 'posters', 'popup', 'table-top-banner-stand'),
		),

		'tension-fabric' => array(
			'intro' => array(
				'Tension fabric is a 2-way stretch, 8.8oz polyester printed by dye sublimation. It is the same material used for our trade show tension displays and table throws, and because it stretches it can be pulled tight over hardware and around rounded corners for a smooth, wrinkle free face.',
				'Order it by the size you need, up to 90" x 100\', for custom frames, fabric wraps and installs on hardware you already own. It is single sided, with free double stitched hems in white or black thread, and optional grommets, pole pockets, sewn-on Velcro or flush heat-cut edges.',
			),
			'uses' => array('Graphics for frames you already own', 'Fabric wraps for columns, walls and fixtures', 'Custom trade show and retail installs', 'Stretched wall and ceiling features'),
			'faq' => array(
				array('q' => 'Can I use it on my own hardware?', 'a' => 'Yes, it is sold by custom size for your own frames. If your frame has a silicone edge channel, order Custom SEG Fabric instead.'),
				array('q' => 'Can it be double sided?', 'a' => 'No. Tension fabric is single sided, up to 90" x 100\', with no oversized pieces.'),
				array('q' => 'Is it the same fabric as your table covers?', 'a' => 'Yes, our table covers and tension displays use the same 8.8oz polyester.'),
			),
			'related' => array('custom-seg-fabric', 'straight-tension-fabric-displays-graphic-frame', 'tension-fabric-stand-graphic-frame', 'blockout-fabric-banner'),
		),

		/* SEG displays */

		'10ft-seg-fabric-display-graphic-frame' => array(
			'intro' => array(
				'The 10ft SEG fabric display is a 118.1" wide by 88.6" tall backwall made for a standard 10 ft trade show booth. The graphic is UV printed on 6oz fabric and sewn with a silicone edge (SEG) strip that presses into a channel in the aluminum frame, pulling the print tight with no visible seams or wrinkles.',
				'The frame goes together quickly and graphics swap in minutes, so one set of hardware can carry a new message for every show. Choose single or double sided. The display is 18" deep, weighs 38 lbs and ships in one 48" x 8.5" x 8" package. Order the graphic only to refresh a frame you already own.',
			),
			'uses' => array('10 ft trade show booth backwalls', 'Retail and lobby feature walls', 'Photo and media walls at events', 'Showroom and conference displays'),
			'faq' => array(
				array('q' => 'What does SEG mean?', 'a' => 'Silicone edge graphic: a thin silicone strip sewn around the print that presses into a groove in the frame, so the fabric is held tight on every edge.'),
				array('q' => 'Can I change the graphic later?', 'a' => 'Yes. Pull the silicone edge out of the channel and press the new graphic in. Order the graphic only version for replacements.'),
				array('q' => 'Is there a lit version?', 'a' => 'Yes. The 10ft SEG Backlit Fabric Display is the same width with interior LED lighting behind a backlit fabric print.'),
			),
			'related' => array('10ft-seg-backlit-fabric-display-graphic-frame', '20ft-seg-fabric-display-graphic-frame', '3ft-seg-fabric-stand-graphic-frame', 'straight-tension-fabric-displays-graphic-frame'),
		),

		'10ft-seg-backlit-fabric-display-graphic-frame' => array(
			'intro' => array(
				'The 10ft SEG backlit fabric display is a 118.1" x 88.6" illuminated backwall. LED lighting inside the frame shines evenly through a 6oz backlit fabric print, so your graphics glow and stand out across a busy show floor.',
				'A silicone edge sewn to the print presses into the slim aluminum frame for a tight, seamless face, and graphics change out easily between shows. Single sided displays include blackout fabric for the back. The display is 17" deep and ships in two boxes of about 31 lbs each.',
			),
			'uses' => array('Trade show booths that need to stand out', 'Illuminated retail and lobby walls', 'Product launch and event backdrops', 'Showroom feature walls'),
			'faq' => array(
				array('q' => 'Are the lights included?', 'a' => 'Yes. LED lighting is built into the display.'),
				array('q' => 'What covers the back of a single sided display?', 'a' => 'Single sided orders include blackout fabric to close the back.'),
				array('q' => 'Lit or unlit?', 'a' => 'The unlit 10ft SEG Fabric Display is the same width at a lower price. Choose backlit when you want graphics to glow and draw attention from across the room.'),
			),
			'related' => array('10ft-seg-fabric-display-graphic-frame', '10ft-seg-backlit-popup-display-graphic-frame', '8ft-seg-backlit-popup-display-graphic-frame', '20ft-seg-fabric-display-graphic-frame'),
		),

		'20ft-seg-fabric-display-graphic-frame' => array(
			'intro' => array(
				'Our largest SEG display, the 20ft SEG fabric display is a 236.2" wide by 88.6" tall fabric wall for 20 ft booths, island exhibits and large venues. The 6oz fabric is UV printed edge to edge and sewn with a silicone edge that locks into the aluminum frame, so the whole wall reads as one tight, seamless image.',
				'Choose single or double sided. The frame is 18" deep and ships in two 48" x 9" x 8.5" boxes of 38.5 lbs each. When the message changes, order the graphic only and reuse the hardware.',
			),
			'uses' => array('20 ft inline and island trade show booths', 'Event and stage backdrops', 'Retail, lobby and venue feature walls', 'Sponsor and media walls'),
			'faq' => array(
				array('q' => 'How big is the display?', 'a' => '236.2" wide, 88.6" tall and 18" deep.'),
				array('q' => 'How does it ship?', 'a' => 'In two boxes, each 48" x 9" x 8.5" and about 38.5 lbs.'),
				array('q' => 'Can I reuse the frame with a new graphic?', 'a' => 'Yes. Order the graphic only; the silicone edge presses into the same frame.'),
			),
			'related' => array('10ft-seg-fabric-display-graphic-frame', 'straight-tension-fabric-displays-graphic-frame', 'custom-seg-fabric', 'step-and-repeat-backdrop-graphic-frame'),
		),

		'3ft-seg-fabric-stand-graphic-frame' => array(
			'intro' => array(
				'The 3ft SEG fabric stand is a 33.45" wide by 78.75" tall freestanding display with a seamless, UV printed fabric graphic. A silicone edge sewn to the print presses into the aluminum frame, so the graphic stays tight and smooth all day.',
				'At 13 lbs, with a travel bag included, it is easy to carry to events. Use one as a premium banner stand, or line several up to divide space and build your own booth layout. Choose single or double sided; it packs down to 43" x 8.25" x 2.75".',
			),
			'uses' => array('Lobby and entrance displays', 'Trade show booth accents', 'Space dividers at events', 'Retail promotions'),
			'faq' => array(
				array('q' => 'How is it different from a retractable banner stand?', 'a' => 'A retractable banner rolls into its base. The SEG stand holds a tensioned fabric graphic in a frame for a smoother, more premium face, and the graphic is easy to replace.'),
				array('q' => 'Can both sides be printed?', 'a' => 'Yes, choose single or double sided.'),
				array('q' => 'How heavy is it?', 'a' => 'About 13 lbs, and a travel bag is included.'),
			),
			'related' => array('deluxe-retractable-insert-stand', 'tension-fabric-stand-graphic-frame', '10ft-seg-fabric-display-graphic-frame', 'x-stand-graphic-stand'),
		),

		'8ft-seg-backlit-popup-display-graphic-frame' => array(
			'intro' => array(
				'The 8ft SEG backlit popup display is an 87.8" x 87.8" illuminated backwall built on accordion style hardware that expands into shape in moments. LED lights inside shine through a 6oz backlit fabric print with a silicone edge, so your graphic glows evenly with no seams.',
				'It is a compact choice for 8 ft booths and in-store displays. Single sided displays include blackout fabric for the back. The display is 15" deep and ships in two boxes, 37 lb and 10 lb.',
			),
			'uses' => array('8 ft trade show booths', 'Illuminated in-store displays', 'Event and conference backdrops', 'Pop-up shops'),
			'faq' => array(
				array('q' => 'How does it set up?', 'a' => 'The accordion frame expands to full size, then the silicone edge graphic presses into the channel.'),
				array('q' => 'Are the lights included?', 'a' => 'Yes, LED lighting is built in.'),
				array('q' => 'Is there a bigger version?', 'a' => 'For a 10 ft space, see the 10ft SEG Backlit Popup Display or the 10ft SEG Backlit Fabric Display.'),
			),
			'related' => array('10ft-seg-backlit-popup-display-graphic-frame', '10ft-seg-backlit-fabric-display-graphic-frame', '10ft-seg-fabric-display-graphic-frame', 'curved-tension-fabric-display-graphic-frame'),
		),

		'custom-seg-fabric' => array(
			'intro' => array(
				'Custom SEG fabric is a silicone edge graphic made to fit hardware you already own. We print the same 6oz polyester fabric used on our SEG displays edge to edge in full CMYK, then sew a 3mm x 11mm silicone keder around it so it presses into your frame\'s channel.',
				'Enter any height and width up to 40\' x 10\'. With the fabric, the silicone edge measures 0.44" x 0.135"; compare that with your frame before ordering.',
			),
			'uses' => array('Replacement graphics for SEG frames and light boxes', 'Graphics for retail fixtures and store walls', 'Large SEG walls and ceiling features', 'Seasonal and promotional graphic swaps'),
			'faq' => array(
				array('q' => 'Will it fit my frame?', 'a' => 'Order the finished graphic size your frame calls for. The silicone edge with fabric measures 0.44" x 0.135"; check it against your frame\'s channel or its maker\'s spec sheet.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 40\' x 10\'.'),
				array('q' => 'Is it the same fabric as your SEG displays?', 'a' => 'Yes, it is the same 6oz polyester fabric, UV printed edge to edge.'),
			),
			'related' => array('10ft-seg-fabric-display-graphic-frame', '20ft-seg-fabric-display-graphic-frame', 'tension-fabric', '3ft-seg-fabric-stand-graphic-frame'),
		),

		/* Table covers */

		'4ft-table-cover' => array(
			'intro' => array(
				'This 4ft table cover is a full color, dye sublimated fabric cover sized for a 48" x 30" x 30" display table. It is sewn from 8.8oz polyester with rounded corners and hemmed edges, and hangs about 2" above the floor for a clean, finished look.',
				'Choose a 3 sided cover with an open back, so you can sit behind the table, or a 4 sided closed back cover when the table is seen from every side. The finished size is 102" x 62.25" (open back) or 102" x 83.5" (closed back), and the cover is washable if it gets dirty.',
			),
			'uses' => array('Small trade show and vendor tables', 'Registration and check-in tables', 'Farmers market and pop-up booths', 'In-store sampling and promotion tables'),
			'faq' => array(
				array('q' => 'Will it fit my table?', 'a' => 'It is sized for a 48" x 30" x 30" table. For 6 ft and 8 ft tables, choose the 6ft or 8ft table cover.'),
				array('q' => 'Open back or closed back?', 'a' => 'Open back (3 sided) leaves room to sit behind the table. Closed back (4 sided) covers the table on all sides for displays seen from every angle.'),
				array('q' => 'Can it be washed?', 'a' => 'Yes. Dye sublimated table covers are washable.'),
			),
			'related' => array('6ft-table-cover', 'table-runner', 'stretch-table-cover', 'table-top-banner-stand'),
		),

		'6ft-table-cover' => array(
			'intro' => array(
				'The 6ft table cover fits the most common folding table at shows and events, a 72" x 30" x 30" table. Your design is dye sublimated across 8.8oz polyester, then sewn with rounded corners and hemmed edges so it drapes evenly and hangs about 2" above the floor.',
				'Order it 3 sided with an open back for staffed booths, or 4 sided with a closed back for full coverage. Finished sizes are 126" x 62.25" and 126" x 83.5", and the cover is washable, so it can be folded into a bag and reused show after show.',
			),
			'uses' => array('Trade show and conference tables', 'Vendor, craft fair and market booths', 'School, church and nonprofit events', 'Recruiting and career fair tables'),
			'faq' => array(
				array('q' => 'What table does it fit?', 'a' => 'A standard 72" x 30" x 30" (6 ft) table.'),
				array('q' => 'Which back style should I pick?', 'a' => 'Open back if someone will sit behind the table; closed back if the table is seen from all sides.'),
				array('q' => 'Is there a fitted version?', 'a' => 'Yes. The stretch table cover fits snugly to 6 ft and 8 ft collapsible tables for a sleeker look.'),
			),
			'related' => array('8ft-table-cover', 'stretch-table-cover', 'table-runner', 'solid-color-table-throws'),
		),

		'8ft-table-cover' => array(
			'intro' => array(
				'The 8ft table cover is sized for a 96" x 30" x 30" display table, the longest standard trade show table. It is full color dye sublimated on 8.8oz polyester, with rounded corners and hemmed edges, and hangs about 2" above the floor.',
				'Choose an open back (3 sided, 150" x 62.25" finished) to sit behind the table, or a closed back (4 sided, 150" x 83.5") for full coverage. The fabric is washable and folds for storage between events.',
			),
			'uses' => array('Trade show booths with an 8 ft table', 'Sponsor and registration tables', 'Product demonstration tables', 'Banquet and event tables'),
			'faq' => array(
				array('q' => 'What table does it fit?', 'a' => 'A standard 96" x 30" x 30" (8 ft) table.'),
				array('q' => 'How much does it weigh?', 'a' => 'About 5.4 lbs open back or 7.3 lbs closed back.'),
				array('q' => 'Can I add branding to a plain throw instead?', 'a' => 'Yes. Pair a solid color table throw with a printed table runner for a lower cost option.'),
			),
			'related' => array('6ft-table-cover', 'stretch-table-cover', 'table-runner', 'solid-color-table-throws'),
		),

		'round-table-cover' => array(
			'intro' => array(
				'Round table covers bring full color dye sublimated branding to round event tables. We make standard height covers for 31.5", 36", 48" and 60" diameter tables and a bar height cover for 31.5" round cocktail tables, all on 8.8oz polyester with a hemmed bottom edge.',
				'The fabric can be folded, ironed and washed, so covers look fresh event after event. For a standard height (28") table, finished sizes run from 86" x 86" to 98" x 98"; the bar height (42") cover is 98" x 98".',
			),
			'uses' => array('Weddings, galas and fundraisers', 'Cocktail and bar height tables at events', 'Graduations and parties', 'Brand activations and lounge areas'),
			'faq' => array(
				array('q' => 'Which size do I need?', 'a' => 'Match the cover to your table\'s diameter: 31.5", 36", 48" or 60" for standard height tables, or 31.5" for bar height. Larger tables have a shorter overhang.'),
				array('q' => 'Do you have bar height covers?', 'a' => 'Yes, for 31.5" round tables that are 42" tall.'),
				array('q' => 'How do I care for it?', 'a' => 'It can be folded, ironed and washed.'),
			),
			'related' => array('6ft-table-cover', 'stretch-table-cover', 'solid-color-table-throws', 'table-runner'),
		),

		'solid-color-table-throws' => array(
			'intro' => array(
				'Solid color table throws are unprinted, factory dyed 7oz polyester covers for 6 ft and 8 ft tables. They give any booth or event a neat, uniform look at a low price, and they are washable and scratch resistant.',
				'The 6 ft throw is 90" x 132" and the 8 ft throw is 90" x 156". Colors include black, blue, red and yellow (see the options for each size). Printing is not available on these throws; add a custom printed table runner on top to show your logo.',
			),
			'uses' => array('Budget table coverage for events', 'Matching throws for multiple booth tables', 'Base layer under a printed table runner', 'Back office, check-in and food tables'),
			'faq' => array(
				array('q' => 'Can you print my logo on it?', 'a' => 'No, these throws are unprinted. Add a printed table runner, or choose a full color printed table cover.'),
				array('q' => 'What sizes are available?', 'a' => '6 ft (90" x 132") and 8 ft (90" x 156").'),
				array('q' => 'Are they washable?', 'a' => 'Yes.'),
			),
			'related' => array('table-runner', '6ft-table-cover', '8ft-table-cover', 'stretch-table-cover'),
		),

		'table-runner' => array(
			'intro' => array(
				'A printed table runner is the most economical way to brand a table. It is a full color dye sublimated strip of 8.8oz polyester, hemmed on the edges, that lays across the table on top of a plain throw.',
				'Pick a width of 24", 26", 28" or 30" and a length of 72", 80" or 90". The fabric is washable and scratch resistant, and the runner pairs with our solid color table throws for a complete look.',
			),
			'uses' => array('Branding a plain table throw', 'Vendor and market booths on a budget', 'Seasonal or event-specific messaging', 'Matching runners across several tables'),
			'faq' => array(
				array('q' => 'What sizes can I order?', 'a' => 'Widths of 24", 26", 28" or 30", and lengths of 72", 80" or 90".'),
				array('q' => 'What goes under it?', 'a' => 'A solid color table throw or any plain cover. Our solid color throws come in 6 ft and 8 ft sizes.'),
				array('q' => 'Can it be washed?', 'a' => 'Yes, the dye sublimated polyester is washable.'),
			),
			'related' => array('solid-color-table-throws', '6ft-table-cover', 'stretch-table-cover', 'table-top-banner-stand'),
		),

		'stretch-table-cover' => array(
			'intro' => array(
				'A stretch table cover is a fitted, modern take on the table throw. The 8.8oz polyester stretches snugly over a 6 ft or 8 ft collapsible table, and your design is dye sublimated across the whole cover for an all-over print with no loose fabric.',
				'Canvas loops hold the cover to each table leg, so it stays tight through a busy show. Edges are hemmed, and the fabric is washable.',
			),
			'uses' => array('Modern trade show booths', 'Product launch and demo tables', 'Sponsor tables at events', 'Retail pop-up displays'),
			'faq' => array(
				array('q' => 'What tables does it fit?', 'a' => 'Most 6 ft and 8 ft collapsible tables; choose your size when ordering.'),
				array('q' => 'How does it stay in place?', 'a' => 'Canvas loops secure the cover to each leg.'),
				array('q' => 'Stretch or regular table cover?', 'a' => 'A stretch cover is fitted for a sleek look. A regular cover drapes to about 2" above the floor and can have an open back so you can sit behind the table.'),
			),
			'related' => array('6ft-table-cover', '8ft-table-cover', 'table-runner', 'round-table-cover'),
		),

		/* Rigid signs and magnets */

		'aluminum-sandwich-board' => array(
			'intro' => array(
				'An aluminum sandwich board (aluminum composite, or ACM) is two sheets of corrosion resistant aluminum permanently bonded to a polyethylene core. The 1/8" (3mm) panel is rigid and long lasting like solid aluminum but lighter, and it fits most display stands. Your graphic is UV printed on vinyl and mounted to the face.',
				'It is cut to any size from 3" x 4" up to 48" x 96", with 1/4" rounded corners, single or double sided. It is waterproof and UV safe for long term indoor and outdoor use. ' . $oversize,
			),
			'uses' => array('Real estate and property signs', 'Fence and wall mounted business signs', 'Building, suite and parking signs', 'Long term outdoor and construction signs'),
			'faq' => array(
				array('q' => 'How is it different from an aluminum sign?', 'a' => 'The sandwich board is a thicker 1/8" composite panel cut to any size. The aluminum sign is a thinner .040 sheet in three standard sizes.'),
				array('q' => 'Can it be printed on both sides?', 'a' => 'Yes, choose single sided (4/0) or double sided (4/4).'),
				array('q' => 'Do you make reflective or dry erase versions?', 'a' => 'Yes, see the reflective aluminum sandwich board and the dry erase aluminum sandwich board.'),
			),
			'related' => array('aluminum-sign', 'reflective-aluminum-sandwich-board', 'dry-erase-aluminum-sandwich-board', 'pvc-board'),
		),

		'aluminum-sign' => array(
			'intro' => array(
				'An aluminum sign is a .040 aluminum sheet with a full color UV printed vinyl graphic mounted to the face. It will not rust, is waterproof and UV safe, and is one of the longest lasting signs you can put outside.',
				'Choose 12" x 18", 18" x 24" or 24" x 36", single or double sided, with square corners or 3/4" rounded corners. Add punched holes and the sign arrives ready to bolt, screw or zip tie to a post, fence or wall.',
			),
			'uses' => array('Parking and no parking signs', 'Private property and notice signs', 'Wall, door and gate signs', 'Novelty and decorative signs'),
			'faq' => array(
				array('q' => 'What sizes are available?', 'a' => '12" x 18", 18" x 24" and 24" x 36". For other sizes, the aluminum sandwich board can be cut from 3" x 4" up to 48" x 96".'),
				array('q' => 'Can I get holes for mounting?', 'a' => 'Yes, punched holes are an option.'),
				array('q' => 'Is there a version that shows up at night?', 'a' => 'Yes, the reflective aluminum sign is printed on reflective vinyl so it lights up in headlights.'),
			),
			'related' => array('reflective-aluminum-sign', 'aluminum-sandwich-board', 'coroplast', 'pvc-board'),
		),

		'coroplast' => array(
			'intro' => array(
				'Coroplast signs are UV printed on 4mm white corrugated plastic: lightweight, waterproof and rigid enough to stand up outdoors. They are the go-to sign for yard, real estate, political, event, construction and directional signs, and for short term storefront promotions.',
				'Pick a standard size from 18" x 12" to 24" x 36" or enter a custom size up to 4 ft x 8 ft, and print one or both sides. Grommets in all four corners or the top two are free, and an H-stake lets you push the sign straight into a lawn. ' . $oversize,
			),
			'uses' => array('Yard, lawn and open house signs', 'Political and campaign signs', 'Event, parking and directional signs', 'Construction site and temporary business signs'),
			'faq' => array(
				array('q' => 'Are grommets extra?', 'a' => 'No. Grommets in all four corners or the top two corners are free.'),
				array('q' => 'Can it be double sided?', 'a' => 'Yes, print one side or both.'),
				array('q' => 'What is the quickest way to order a yard sign?', 'a' => 'For a standard 24" x 18" double sided yard sign with a stake, use the Yard Sign and H-Stake listing.'),
			),
			'related' => array('yard-sign-and-h-stake', 'reflective-coroplast-sign-hstake', 'dry-erase-coroplast-sign-hstake', 'aluminum-sign'),
		),

		'gatorfoam' => array(
			'intro' => array(
				'GatorFoam is an extruded polystyrene foam board, like foamcore, but faced with a melamine and wood fiber veneer. That makes it far more rigid: the surface won\'t easily dent and the board doesn\'t warp or bow, so large prints stay flat and look professional.',
				'Choose 3/16" or 1/2" thickness in white or black. Your design is UV printed on adhesive vinyl and mounted, single or double sided, up to 48" x 96". GatorFoam is for indoor use only. ' . $oversize,
			),
			'uses' => array('Museum and gallery displays', 'Presentation and easel boards', 'Large indoor wall signs', 'Retail and trade show signs'),
			'faq' => array(
				array('q' => 'GatorFoam or foam board?', 'a' => 'Foam board is lighter and cheaper for short term use. GatorFoam\'s wood fiber faces resist dents and warping, so it is the better choice for large or long term displays.'),
				array('q' => 'Can it go outside?', 'a' => 'No, GatorFoam is not waterproof. For outdoor signs, choose PVC board, coroplast or aluminum.'),
				array('q' => 'What thickness should I choose?', 'a' => '3/16" works for most signs and frames; 1/2" is stiffer for very large boards and free standing displays.'),
			),
			'related' => array('foam-board', 'pvc-board', 'aluminum-sandwich-board', 'styrene'),
		),

		'magnets' => array(
			'intro' => array(
				'Custom magnets are printed direct with UV inks on a 30 mil matte magnet sheet, which gives a long lasting graphic that is waterproof and UV safe. They are best known as car door signs, and also work as temporary signs on steel doors, trailers and job site equipment.',
				'Order any size up to 24" high by 60" wide, with square or rounded corners. Magnets only hold to steel: some newer car doors are plastic or aluminum, so check the spot first. Let a new magnet sit on the vehicle overnight before driving so it bonds fully.',
			),
			'uses' => array('Car, truck and van door signs', 'Contractor and service vehicle branding', 'Signs on steel doors and job site trailers', 'Temporary signs that move between vehicles'),
			'faq' => array(
				array('q' => 'Will it stick to my car?', 'a' => 'Only to steel. Test the spot with a fridge magnet before you order. For non-magnetic surfaces, an adhesive vinyl decal is the better choice.'),
				array('q' => 'Can I drive right after putting it on?', 'a' => 'Let it sit overnight first. A magnet that hasn\'t bonded to the vehicle may come off at speed.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 24" high by 60" wide.'),
			),
			'related' => array('reflective-car-magnet', 'dry-erase-magnet', 'adhesive-products', 'aluminum-sign'),
		),

		'pvc-board' => array(
			'intro' => array(
				'PVC board is a 1/8" expanded PVC sheet, similar to Sintra, printed direct with UV inks. It is lightweight and slightly flexible, waterproof, and its prints resist scratches, making it a reliable sign for long term indoor use and short term outdoor jobs.',
				'Because it is 1/8" thick it fits most sign frames and display stands, and it mounts easily to walls. Print single or double sided, in any size up to 48" x 96". ' . $oversize,
			),
			'uses' => array('Wall mounted office and retail signs', 'Inserts for sign frames and holders', 'Indoor poster and menu boards', 'Short term outdoor and event signs'),
			'faq' => array(
				array('q' => 'Is PVC board good for outdoor signs?', 'a' => 'Yes for short term outdoor use; it is waterproof and UV safe. For signs that stay outside for years, choose aluminum.'),
				array('q' => 'Will it fit my sign frame?', 'a' => 'At 1/8" thick, it fits most display stands and frames.'),
				array('q' => 'Is there a dry erase or reflective version?', 'a' => 'Yes, see the dry erase PVC board and the reflective PVC board.'),
			),
			'related' => array('dry-erase-pvc-board', 'reflective-pvc-board', 'aluminum-sandwich-board', 'poster-graphic-stand'),
		),

		'styrene' => array(
			'intro' => array(
				'Styrene is a thin, flexible .020" high impact polystyrene sheet printed direct with UV inks in a matte finish. It has a blockout core, so nothing shows through from behind, and it lays flat after being rolled, which makes it easy to slide into frames and holders.',
				'It is waterproof and UV safe for indoor and outdoor use, single sided, up to 48" x 96", with optional brass grommets. It is a popular choice for long term point of purchase signs in stores, restaurants and malls.',
			),
			'uses' => array('Point of purchase and in-store signs', 'Drop-in inserts for frames and sign holders', 'Restaurant and menu signs', 'Posters for malls and events'),
			'faq' => array(
				array('q' => 'Is styrene rigid?', 'a' => 'No, it is thin and flexible. For a rigid sign, choose PVC board or foam board.'),
				array('q' => 'Can it be printed on both sides?', 'a' => 'No, styrene is single sided.'),
				array('q' => 'Can I hang it?', 'a' => 'Yes, add the optional brass grommets.'),
			),
			'related' => array('pvc-board', 'posters', 'foam-board', 'poster-graphic-stand'),
		),

		/* Adhesive vinyl (AV series) */

		'adhesive-products' => array(
			'intro' => array(
				'AV 1 adhesive vinyl is our standard printed decal material: a 4 mil white PVC calendered film with a clear permanent adhesive. It is UV printed in a matte finish with no lamination, holds up to water and scratches, and works indoors or out on flat surfaces.',
				'Use it for custom stickers, decals, wall and door graphics, and for prints you want to mount on your own boards. It is single sided and comes in sizes up to 52" high by 159\' wide.',
			),
			'uses' => array('Custom decals and stickers', 'Door, wall and equipment graphics', 'Prints mounted to boards or panels', 'Indoor and outdoor labels'),
			'faq' => array(
				array('q' => 'Can I remove it later?', 'a' => 'AV 1 has a permanent adhesive. For a decal that can be removed, choose AV 2 high performance adhesive vinyl or a window cling.'),
				array('q' => 'Does it work on windows?', 'a' => 'It is opaque white. For windows, see clear vinyl, window perf, frosted vinyl or window cling.'),
				array('q' => 'What is the largest size?', 'a' => '52" high by up to 159\' wide.'),
			),
			'related' => array('adhesive-vinyl-high-performance', 'adhesive-clear-vinyl', 'av9-reflective-adhesive-vinyl', 'av-12-dry-erase-adhesive-vinyl'),
		),

		'adhesive-vinyl-high-performance' => array(
			'intro' => array(
				'AV 2 high performance adhesive vinyl is a 3 mil calendered PVC film with a pressure sensitive, removable adhesive. Its gray backing has air egress channels, so air escapes as you press it down for a bubble free install, and the decal can be lifted and repositioned while you line it up.',
				'It is made for long term flat applications indoors and out. Prints are UV printed in a matte finish, single sided, up to 52" high by 159\' wide.',
			),
			'uses' => array('Large wall graphics and decals', 'Labels and graphics on flat panels, doors and equipment', 'Promotional graphics that will be removed later', 'Installs where bubbles must be avoided'),
			'faq' => array(
				array('q' => 'How is it different from AV 1?', 'a' => 'AV 2 has a removable adhesive and an air egress backing for easier, bubble free installs. AV 1 has a permanent adhesive.'),
				array('q' => 'Can it go outside?', 'a' => 'Yes, it is waterproof and UV safe for indoor or outdoor use.'),
				array('q' => 'Is it laminated?', 'a' => 'No, it is UV printed with a matte finish and no lamination.'),
			),
			'related' => array('adhesive-products', 'adhesive-wall-fabric', '3m-ij-180cv3-controltac-vehicle-wrap', 'floor-graphics'),
		),

		'adhesive-translucent-vinyl' => array(
			'intro' => array(
				'AV 3 adhesive translucent vinyl is a 3 mil printed vinyl made for lit displays. Its permanent adhesive sticks best to flat plexi, acrylic, lexan, glass and other clear or translucent plastics, so it is used for light boxes, channel letter faces, window displays and point of purchase displays.',
				'Choose single layer printing for graphics seen mostly in daylight (colors lighten when lit at night), or double layer printing for graphics seen at night in a light box (colors look darker unlit). It is indoor and outdoor safe, up to 52" high by 159\' wide.',
			),
			'uses' => array('Light box and display cabinet faces', 'Channel letter and lit display faces', 'Backlit window displays', 'Illuminated point of purchase displays'),
			'faq' => array(
				array('q' => 'Single or double layer?', 'a' => 'Single layer for daytime viewing; double layer for night viewing with a light box behind it.'),
				array('q' => 'Why does the print look dark?', 'a' => 'Translucent vinyl is printed with heavy ink so it glows correctly when lit. Without backlighting, it looks darker than your artwork.'),
				array('q' => 'Is there a non-adhesive version?', 'a' => 'Yes, backlit film is a 10 mil translucent film for light boxes that hold the graphic in place.'),
			),
			'related' => array('backlit-film', 'backlit-banner', 'adhesive-clear-vinyl', 'frosted-vinyl-etched'),
		),

		'frosted-vinyl-etched' => array(
			'intro' => array(
				'AV 4 frosted vinyl gives glass the look of etched glass for far less cost. It is a printable privacy film with an adhesive backing and a thick 7 mil matte frost lamination, so it lets diffuse light through while blocking a clear view, inside or out.',
				'It is printed in a double layer, so it looks darker on its backing paper than your artwork; once installed on glass, the colors look closer to your design. The film is not fully clear and will be slightly visible on glass. Sizes go up to 52" high by 159\' wide.',
			),
			'uses' => array('Office and conference room privacy glass', 'Storefront and door window graphics', 'Bathroom and clinic windows', 'Decorative bands and logos on glass walls'),
			'faq' => array(
				array('q' => 'Is it cheaper than etched glass?', 'a' => 'Yes, frosted vinyl gives a similar look at a fraction of the cost and can be replaced when your design changes.'),
				array('q' => 'Why does the print look darker before install?', 'a' => 'It is printed in a double layer for the clear material. Once the backing is removed and it is on the glass, colors look closer to your artwork.'),
				array('q' => 'Can it go on the outside of a window?', 'a' => 'Yes, it is waterproof and UV safe for indoor or outdoor use.'),
			),
			'related' => array('adhesive-clear-vinyl', 'adhesive-window-perf', 'premium-window-cling', 'adhesive-translucent-vinyl'),
		),

		'adhesive-clear-vinyl' => array(
			'intro' => array(
				'AV 5 adhesive clear vinyl is an ultra clear 4 mil PVC film with a clear permanent adhesive, so only your printed design shows on glass and other transparent surfaces and light still comes through around it.',
				'It is printed in CMYK without white ink, so colors are see-through and look best on clean, smooth glass. The adhesive grips strongly and the decal may not reposition; for graphics you plan to move, choose a window cling. Sizes go up to 52" high by 159\' wide.',
			),
			'uses' => array('Window logos and store hours', 'Glass door and partition decals', 'Clear labels on acrylic and plastic', 'Promotional graphics on glass displays'),
			'faq' => array(
				array('q' => 'Will white parts of my design print white?', 'a' => 'No. Clear vinyl is printed without white ink, so white areas stay clear.'),
				array('q' => 'Can I move it after it is applied?', 'a' => 'It has a strong permanent adhesive and may not reposition. For movable window graphics, choose the premium window cling.'),
				array('q' => 'Indoor or outdoor?', 'a' => 'Both. It is waterproof and UV safe.'),
			),
			'related' => array('premium-window-cling', 'adhesive-window-perf', 'frosted-vinyl-etched', 'adhesive-products'),
		),

		'adhesive-window-perf' => array(
			'intro' => array(
				'AV 6 window perf is a white perforated film with a black adhesive backing: 70% printed area and 30% open holes. From outside you see a full color graphic; from inside you can still see out through the glass. It is the standard material for storefront window and vehicle rear window advertising.',
				'Prints are UV printed for a longer lasting, scratch resistant image, single sided, up to 52" high by 159\' wide, for indoor or outdoor use.',
			),
			'uses' => array('Full coverage storefront window graphics', 'Vehicle rear and side window ads', 'Office windows that need branding and a view out', 'Bus shelter and kiosk glass'),
			'faq' => array(
				array('q' => 'Can I see out through it?', 'a' => 'Yes. The black adhesive side faces in, and the open holes let you see out while the printed side faces the street.'),
				array('q' => 'What does 70/30 mean?', 'a' => '70% of the film is printable and 30% is open perforation.'),
				array('q' => 'Is it printed on both sides?', 'a' => 'No, it is printed on one side only.'),
			),
			'related' => array('frosted-vinyl-etched', 'adhesive-clear-vinyl', 'premium-window-cling', '3m-ij-180cv3-controltac-vehicle-wrap'),
		),

		'premium-window-cling' => array(
			'intro' => array(
				'AV 7 premium window cling is an 8 mil film with a low tack mini-dot adhesive. It holds firmly to glass and other smooth, clean surfaces, then peels off without residue, so you can reposition and reuse it time and again. The air egress adhesive makes bubble free installs easy.',
				'The cling holds on the back of the print only, so the printed side faces you after install. Wetting the glass first makes positioning easier. It is UV printed for outdoor durability, single sided, up to 52" high by 159\' wide.',
			),
			'uses' => array('Seasonal and sale window promotions', 'Point of purchase graphics on glass and coolers', 'Temporary hours and event notices', 'Graphics for rented or leased storefronts'),
			'faq' => array(
				array('q' => 'Can I reuse it?', 'a' => 'Yes. It removes without residue and can be repositioned and reapplied.'),
				array('q' => 'Which side sticks?', 'a' => 'The cling holds on the back of the print, so apply it to the side of the glass you are viewing from.'),
				array('q' => 'Can I get a face-mounted version for inside the glass?', 'a' => 'No, face-mount material is not available for this cling.'),
			),
			'related' => array('adhesive-clear-vinyl', 'adhesive-window-perf', 'frosted-vinyl-etched', 'posters'),
		),

		'adhesive-wall-fabric' => array(
			'intro' => array(
				'AV 8 adhesive wall fabric is a 6.5 mil printed fabric with a repositionable adhesive that is safe for painted walls. Unlike vinyl, the fabric face doesn\'t crease or stretch, so it is very forgiving to install, and you can peel it up and reposition it until it is right.',
				'It is a Type I wall material, similar to Phototex and WallFrog, UV printed for a durable wall covering, up to 52" high by 100\' wide. Use it for full wall murals, accent walls and temporary wall decals.',
			),
			'uses' => array('Wall murals for offices, stores and restaurants', 'Accent walls and branded backdrops', 'Temporary wall decals and posters', 'Graphics for rented spaces'),
			'faq' => array(
				array('q' => 'Will it damage my paint?', 'a' => 'The adhesive is made to be repositionable and safe for painted walls.'),
				array('q' => 'Can I cover a whole wall?', 'a' => 'Yes. Panels are up to 52" high and up to 100\' wide, so large walls are covered in panels.'),
				array('q' => 'Is it like Phototex?', 'a' => 'Yes, it is similar to Phototex and WallFrog wall fabric.'),
			),
			'related' => array('adhesive-vinyl-high-performance', 'adhesive-products', 'canvas-roll', 'floor-graphics'),
		),

		'av9-reflective-adhesive-vinyl' => array(
			'intro' => array(
				'AV 9 reflective adhesive vinyl is an engineer grade, 4 mil prismatic reflective film with a permanent adhesive backing. It is UV printed in full color, so your graphic shines back when headlights or flashlights hit it.',
				'As part of our AV roll media line, it is cut to the size you need, up to 48" high by 100\' wide, for decals and graphics you apply yourself to panels, boards and vehicles. It is waterproof and UV safe for years of outdoor use.',
			),
			'uses' => array('High visibility vehicle decals', 'Safety, parking and directional graphics', 'Graphics for your own blank panels', 'Equipment and trailer markings'),
			'faq' => array(
				array('q' => 'What is engineer grade?', 'a' => 'It is a standard grade of reflective sheeting for graphics that need to be seen at night.'),
				array('q' => 'Can I apply it to my own blank panels?', 'a' => 'Yes. It is a permanent adhesive vinyl for flat surfaces, sold by size.'),
				array('q' => 'Do you sell ready-made reflective boards?', 'a' => 'Yes, see our reflective aluminum, reflective coroplast and reflective PVC board products.'),
			),
			'related' => array('reflective-aluminum-sign', 'reflective-pvc-board', 'adhesive-products', 'reflective-car-magnet'),
		),

		'floor-graphics' => array(
			'intro' => array(
				'AV 10 floor graphics are printed on 3 mil floor vinyl with an air egress backing and covered with a 7 mil textured floor lamination. The laminate is tough enough for heavy foot traffic and has a non-slip surface, so people can walk right over your message.',
				'They are made for indoor finished floors and are UV safe. Floor graphics are single sided, up to 52" high by 100\' wide.',
			),
			'uses' => array('Social distancing and queue markers', 'Wayfinding arrows and directions', 'Store promotions and product callouts', 'Trade show booth floor branding'),
			'faq' => array(
				array('q' => 'Is it slippery?', 'a' => 'No. The 7 mil floor lamination is textured to help prevent slips.'),
				array('q' => 'Can I use it outside?', 'a' => 'It is made for indoor finished surfaces.'),
				array('q' => 'How big can a floor graphic be?', 'a' => 'Up to 52" high by 100\' wide.'),
			),
			'related' => array('adhesive-vinyl-high-performance', 'adhesive-products', 'adhesive-wall-fabric'),
		),

		'3m-ij-180cv3-controltac-vehicle-wrap' => array(
			'intro' => array(
				'AV 11 is 3M Controltac IJ180Cv3, a 2 mil cast vehicle wrap film with Comply v3 adhesive. Hidden air release channels let air escape for a fast, bubble free install, and the film can be slid and repositioned while you work. Cast vinyl has low memory and doesn\'t shrink, so it conforms to deep channels, concave areas and complex curves.',
				'Every wrap is eco-solvent printed and finished with 3M Scotchcal 8518 gloss overlaminate, a 2 mil film that adds gloss and extra UV and scratch protection. It is long term durable, removable and comes up to 52" high by 150\' wide.',
			),
			'uses' => array('Full and partial vehicle wraps', 'Fleet and van graphics', 'Boat and trailer graphics', 'Wraps for curved surfaces and equipment'),
			'faq' => array(
				array('q' => 'Why cast vinyl for wraps?', 'a' => 'Cast film has low memory and won\'t shrink, so it stays put over curves and recesses where calendered vinyl would lift.'),
				array('q' => 'Is it laminated?', 'a' => 'Yes, with 3M Scotchcal 8518 gloss overlaminate.'),
				array('q' => 'Can it be removed later?', 'a' => 'Yes, IJ180Cv3 is a removable film.'),
			),
			'related' => array('adhesive-window-perf', 'magnets', 'adhesive-vinyl-high-performance', 'av9-reflective-adhesive-vinyl'),
		),

		'av-12-dry-erase-adhesive-vinyl' => array(
			'intro' => array(
				'AV 12 dry erase adhesive vinyl turns a flat wall, door or panel into a printed whiteboard. It is a 4 mil white PVC film with a clear permanent adhesive, UV printed in full color and sealed with a UV cured gloss dry erase coating, so marker wipes clean while your printed design stays.',
				'As part of our AV roll media line, it is cut to any size up to 52" high by 159\' wide. It is waterproof and UV safe for indoor and outdoor use.',
			),
			'uses' => array('Whiteboard walls and planning boards', 'Printed schedules, charts and calendars', 'Erasable labels and inventory boards', 'Menu and specials boards'),
			'faq' => array(
				array('q' => 'Does dry erase marker wipe off cleanly?', 'a' => 'Yes, the gloss UV cured coating is made to erase clean.'),
				array('q' => 'Can I apply it to a wall?', 'a' => 'Yes, it is a permanent adhesive film for flat surfaces.'),
				array('q' => 'Do you make rigid dry erase boards?', 'a' => 'Yes, see dry erase foamcore, PVC board, coroplast and aluminum sandwich board.'),
			),
			'related' => array('dry-erase-adhesive-vinyl', 'dry-erase-aluminum-sandwich-board', 'dry-erase-pvc-board', 'adhesive-products'),
		),

		/* Large format prints */

		'backlit-film' => array(
			'intro' => array(
				'Backlit film is a heavy duty 10 mil translucent film for light boxes. The printable side has a bright white point for vivid color, and the matte finish cuts glare, so images stay sharp and even when lit. It is double layer UV printed for long lasting color.',
				'It is non-adhesive, made for light boxes and lit frames that hold the print behind a clear or translucent face. Sizes go up to 59" high by 100\' wide. For a print that sticks to the light box face, choose AV 3 adhesive translucent vinyl.',
			),
			'uses' => array('Menu boards and drive-thru displays', 'Movie theater and poster light boxes', 'Airport, mall and bus stop displays', 'Backlit trade show and window displays'),
			'faq' => array(
				array('q' => 'Does it have adhesive?', 'a' => 'No. It sits in a light box frame. For an adhesive version, choose adhesive translucent vinyl.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 59" high by 100\' wide.'),
				array('q' => 'Backlit film or backlit banner?', 'a' => 'Backlit film is for frames and menu boards. The 15oz backlit banner suits large light boxes up to 120" x 150\'.'),
			),
			'related' => array('adhesive-translucent-vinyl', 'backlit-banner', 'posters'),
		),

		'popup' => array(
			'intro' => array(
				'Popup is our 14 mil Double White Popup PVC material: white on the front and back with a blockout layer inside, and a smooth surface for sharp UV printed color. It is scratch resistant, won\'t tear like paper stock and lays flat after being rolled.',
				'It is the material behind many of our inserts and posters. Order any size up to 52" high by 159\' wide, single sided, with grommets in all four corners, in the top two corners or none.',
			),
			'uses' => array('Inserts for display frames and poster holders', 'Hanging graphics with grommets', 'Mounted prints and displays', 'Retail and window posters'),
			'faq' => array(
				array('q' => 'Does it need lamination?', 'a' => 'No, the UV prints are scratch resistant on their own.'),
				array('q' => 'Can I hang it?', 'a' => 'Yes, choose grommets in all four corners or the top two.'),
				array('q' => 'Will it stay flat?', 'a' => 'Yes, the material lays flat after being rolled.'),
			),
			'related' => array('posters', 'indoor-banner-super-smooth', 'styrene', 'snap-poster-graphic-hanger'),
		),

		'posters' => array(
			'intro' => array(
				'Our large format posters are UV printed on 14 mil Double White Popup, so colors jump off the page and prints hold up without lamination. The PVC material has low memory: even after shipping rolled, posters lay flat for easy hanging or framing.',
				'Order any size up to 52" high by 159\' wide, single sided, for temporary and semi-permanent displays.',
			),
			'uses' => array('Retail, window and in-store posters', 'Event and movie posters', 'Inserts for poster frames and snap frames', 'Classroom, office and lobby posters'),
			'faq' => array(
				array('q' => 'Are posters printed on paper?', 'a' => 'No. They are printed on 14 mil PVC popup material, which is tougher than paper and doesn\'t need lamination.'),
				array('q' => 'Will they curl after shipping?', 'a' => 'The material has low memory and lays flat after being rolled.'),
				array('q' => 'What sizes can I order?', 'a' => 'Any size up to 52" high by 159\' wide.'),
			),
			'related' => array('popup', 'snap-poster-graphic-hanger', 'framed-art', 'styrene'),
		),

		/* Trade show displays */

		'curved-tension-fabric-display-graphic-frame' => array(
			'intro' => array(
				'Curved tension fabric displays slip a dye sublimated 8.8oz fabric print over a lightweight frame of interlocking aluminum tubes, like a pillow into a pillowcase, then zip it closed. The fabric pulls tight across the curve for a smooth, wrinkle free backwall, and the curve lets the frame stand on its own.',
				'Choose a 6 ft tabletop display (72" x 60") or 8 ft (96" x 89") and 10 ft (120" x 89") floor displays, single or double sided. Setup is tool free, a soft carry bag is included, and a hard case that converts to a podium is available for the 8 ft and 10 ft sizes.',
			),
			'uses' => array('8 ft and 10 ft trade show backwalls', 'Tabletop displays for small booths', 'Event and photo backdrops', 'Lobby and retail feature walls'),
			'faq' => array(
				array('q' => 'Do I need tools to set it up?', 'a' => 'No. The tubes click together by hand, then the fabric slides over and zips closed.'),
				array('q' => 'What sizes are available?', 'a' => '6 ft (72" x 60", tabletop), 8 ft (96" x 89") and 10 ft (120" x 89").'),
				array('q' => 'Is there a case?', 'a' => 'A soft carry bag is included. An optional hard case with podium is available for the 8 ft and 10 ft displays.'),
			),
			'related' => array('straight-tension-fabric-displays-graphic-frame', 'tension-fabric-stand-graphic-frame', '10ft-seg-fabric-display-graphic-frame', '6ft-table-cover'),
		),

		'straight-tension-fabric-displays-graphic-frame' => array(
			'intro' => array(
				'Straight tension fabric displays give you a flat, frameless looking backwall. A dye sublimated 8.8oz fabric print slips over interlocking aluminum tubes and zips closed, and stabilizing feet keep the straight frame upright.',
				'Choose 8 ft (89.5" x 89"), 10 ft (112" x 89") or 20 ft (236.5" x 89.5") to match your booth, single or double sided. Setup is tool free, a soft carry bag is included, and a hard case that converts to a podium is optional.',
			),
			'uses' => array('8, 10 and 20 ft trade show booths', 'Step and repeat and photo walls', 'Conference stage backdrops', 'Showroom and retail walls'),
			'faq' => array(
				array('q' => 'Straight or curved?', 'a' => 'A straight wall gives a flat backdrop and needs its stabilizing feet. A curved wall stands on its own and wraps the booth slightly.'),
				array('q' => 'How heavy is it?', 'a' => 'About 30 lbs (8 ft), 34 lbs (10 ft) or 41 lbs (20 ft) with the soft bag.'),
				array('q' => 'Can I order just a new graphic?', 'a' => 'Yes, order the graphic only to reuse your frame.'),
			),
			'related' => array('curved-tension-fabric-display-graphic-frame', '10ft-seg-fabric-display-graphic-frame', 'step-and-repeat-backdrop-graphic-frame', '8ft-table-cover'),
		),

		'tension-fabric-stand-graphic-frame' => array(
			'intro' => array(
				'A tension fabric stand is a premium, banner stand sized display. The interlocking aluminum tube frame sets up in seconds with no tools, then a stretch fabric "pillowcase" graphic slips over it and zips closed at the bottom, leaving a smooth, wrinkle free print.',
				'Graphics are full color dye sublimated on 8.8oz tension fabric, single or double sided, and can be replaced to reuse the frame year after year. A heavy duty steel base plate keeps it steady, a carry case is included, and LED lights are optional.',
			),
			'uses' => array('Lobby and entrance displays', 'Trade show booth accents', 'Retail and event displays', 'Double sided displays in walkways'),
			'faq' => array(
				array('q' => 'How does it set up?', 'a' => 'Click the tubes together, slip the fabric over the frame and zip it closed.'),
				array('q' => 'Can it be double sided?', 'a' => 'Yes, choose single or double sided pillowcase graphics.'),
				array('q' => 'How is it different from a retractable stand?', 'a' => 'It has a stretched pillowcase fabric face instead of a roll-up banner, and double sided printing is available.'),
			),
			'related' => array('3ft-seg-fabric-stand-graphic-frame', 'deluxe-retractable-insert-stand', 'curved-tension-fabric-display-graphic-frame', 'x-stand-graphic-stand'),
		),

		/* Banner stands */

		'deluxe-retractable-insert-stand' => array(
			'intro' => array(
				'The Deluxe retractable banner stand is a step up from our Standard model, with a sleek wide base and chrome end caps. An adjustable telescopic pole lets you set the display height, and when you are done the banner rolls back into the aluminum base and goes in the included carry bag.',
				'Choose a UV printed fabric insert with blockout backing and curl free edges, or 13oz matte vinyl. Keep important text 1" from the top and 3" from the bottom so it stays visible. LED lights are optional.',
			),
			'uses' => array('Trade shows and conferences', 'Lobby, reception and storefront displays', 'Retail promotions and product launches', 'Events, seminars and presentations'),
			'faq' => array(
				array('q' => 'Fabric or vinyl insert?', 'a' => 'Fabric has blockout backing and curl free edges for a premium look. 13oz matte vinyl is the classic option.'),
				array('q' => 'How much margin should my design have?', 'a' => 'Keep important elements at least 1" from the top and 3" from the bottom.'),
				array('q' => 'Deluxe or Standard?', 'a' => 'The Deluxe has a wider, heavier looking base with chrome end caps and an adjustable height pole. The Standard is lighter and more economical.'),
			),
			'related' => array('standard-retractable-insert-stand', 'sd-retractable-sd-retractable-insert-stand', 'table-top-banner-stand', 'x-stand-graphic-stand'),
		),

		'standard-retractable-insert-stand' => array(
			'intro' => array(
				'The Standard retractable banner stand is our lightweight, economical banner stand. Two feet rotate out from the compact aluminum base for stability and fold back in for storage, and the banner rolls into the base in seconds when the show is over.',
				'Choose a UV printed fabric insert with curl free edges and gray blockout backing, or 13oz matte vinyl. Keep important elements 1" from the top and 3" from the bottom. A carry bag is included and LED lights are optional.',
			),
			'uses' => array('Trade shows on a budget', 'Traveling sales and event teams', 'Lobby and point of sale displays', 'Schools, churches and nonprofits'),
			'faq' => array(
				array('q' => 'How long does setup take?', 'a' => 'Seconds: rotate out the feet, pull up the banner and attach it to the pole.'),
				array('q' => 'Is a bag included?', 'a' => 'Yes, a carrying bag is included.'),
				array('q' => 'Can I replace the banner later?', 'a' => 'Yes, order the insert only to put a new graphic in your stand.'),
			),
			'related' => array('deluxe-retractable-insert-stand', 'table-top-banner-stand', 'x-stand-graphic-stand', '13oz-vinyl-banner'),
		),

		'step-and-repeat-backdrop-graphic-frame' => array(
			'intro' => array(
				'Our step and repeat backdrop uses large 1.625" diameter telescoping aluminum poles, much sturdier than the 1" tubing common on other backdrops, with plastic locking collars and heavy feet for stability. The frame adjusts in height and width to fit your graphic.',
				'The graphic is dye sublimated on 9oz wrinkle free fabric with a low sheen that photographs without glare, hemmed with 3" pole pockets top and bottom. Order any size from 6\' x 4\' up to 10\' x 8\', single sided. It assembles in a few minutes and packs into the included carry bag.',
			),
			'uses' => array('Step and repeat logo walls for events', 'Photo booth and red carpet backdrops', 'Press and media walls', 'Trade show and stage backdrops'),
			'faq' => array(
				array('q' => 'What sizes can I order?', 'a' => 'Any size from 6\' x 4\' up to 10\' x 8\'.'),
				array('q' => 'Will the fabric glare in photos?', 'a' => 'The 9oz wrinkle free fabric has a low sheen, made for photo backdrops.'),
				array('q' => 'Is it double sided?', 'a' => 'No, step and repeat graphics are single sided.'),
			),
			'related' => array('fabric-banner-9oz-wrinkle-free-copy', 'straight-tension-fabric-displays-graphic-frame', 'blockout-fabric-banner', '20ft-seg-fabric-display-graphic-frame'),
		),

		'table-top-banner-stand' => array(
			'intro' => array(
				'The table top banner stand is a mini retractable banner, a small version of our Deluxe stand, for counters, tables and point of sale. It shows an 11.5" x 17.5" graphic on 14 mil Double White Popup that rolls into the silver aluminum base with chrome end caps for storage or travel.',
				'It sets up in two steps and is compact enough for carry on luggage. The bottom 1.5" of the graphic may stay inside the base, so keep important elements above it.',
			),
			'uses' => array('Counter and register displays', 'Trade show table displays', 'Reception desk and lobby displays', 'Promotions on restaurant and retail tables'),
			'faq' => array(
				array('q' => 'What size is the graphic?', 'a' => '11.5" x 17.5". The bottom 1.5" counts as bleed and may stay inside the base.'),
				array('q' => 'Is it portable?', 'a' => 'Yes, it is small enough to fit in carry on luggage.'),
				array('q' => 'Is there a floor version?', 'a' => 'Yes, see the Standard and Deluxe retractable banner stands.'),
			),
			'related' => array('deluxe-retractable-insert-stand', 'standard-retractable-insert-stand', '4ft-table-cover', 'table-runner'),
		),

		/* Dry erase */

		'dry-erase-adhesive-vinyl' => array(
			'intro' => array(
				'Dry erase adhesive vinyl puts your printed layout and a writable surface on any flat wall, door, cabinet or board. The 4 mil white PVC film is UV printed in full color, then sealed with a UV cured gloss dry erase coating, so marker wipes away cleanly around your printed graphics.',
				'Its clear permanent adhesive holds on flat surfaces indoors and out, and it is waterproof and UV safe. Order it by size, up to 52" high by 159\' wide.',
			),
			'uses' => array('Printed whiteboards for offices and classrooms', 'Production, job and scheduling boards', 'Erasable labels and bin tags', 'Specials and menu boards'),
			'faq' => array(
				array('q' => 'Can I write on it with any marker?', 'a' => 'Use dry erase markers; the gloss coating is made to erase clean.'),
				array('q' => 'What surfaces does it stick to?', 'a' => 'Flat, smooth surfaces such as painted walls, doors, metal and boards.'),
				array('q' => 'Is there a rigid version?', 'a' => 'Yes, see dry erase foamcore, PVC board, coroplast and aluminum sandwich board.'),
			),
			'related' => array('dry-erase-pvc-board', 'dry-erase-aluminum-sandwich-board', 'dry-erase-magnet', 'adhesive-products'),
		),

		'dry-erase-coroplast-sign-hstake' => array(
			'intro' => array(
				'A dry erase coroplast sign is a lightweight 4mm corrugated plastic sign with a UV printed graphic and a UV cured gloss dry erase coating. Print the parts that never change, then write in prices, times, names or directions and wipe them off when they change.',
				'It is waterproof and UV safe for indoor or outdoor use, single or double sided, in custom sizes up to 48" x 96". Add an H-stake for lawns or grommets for hanging. ' . $oversize,
			),
			'uses' => array('Open house signs with changing times', 'Event and parking signs with updatable details', 'Job site and lot status signs', 'Sale and price signs'),
			'faq' => array(
				array('q' => 'Will marker survive rain?', 'a' => 'The sign is waterproof, but dry erase marker can wash off in rain. It works best for signs that are updated often or are under cover.'),
				array('q' => 'Can I get a stake?', 'a' => 'Yes, order it with an H-stake, or choose the sign only with grommets.'),
				array('q' => 'Can both sides be printed?', 'a' => 'Yes, single or double sided.'),
			),
			'related' => array('coroplast', 'reflective-coroplast-sign-hstake', 'yard-sign-and-h-stake', 'dry-erase-pvc-board'),
		),

		'dry-erase-foamcore' => array(
			'intro' => array(
				'Dry erase foamcore is a 3/16" white foam board with a polystyrene core, UV printed and finished with a gloss dry erase coating. It is light enough to carry from room to room or hang on a wall, so you can draw, write and erase around your printed graphics.',
				'Print single or double sided, up to 48" x 96". Foamcore is for indoor use only. ' . $oversize,
			),
			'uses' => array('Presentation and planning boards', 'Classroom and training boards', 'Sign-up and seating charts', 'Easel signs for events'),
			'faq' => array(
				array('q' => 'Can it go outside?', 'a' => 'No, foamcore is not waterproof. For outdoor dry erase signs, choose dry erase coroplast or aluminum.'),
				array('q' => 'Is it heavy?', 'a' => 'No, it is lightweight and easy to move or attach to a wall.'),
				array('q' => 'Can both sides be printed?', 'a' => 'Yes, single or double sided.'),
			),
			'related' => array('foam-board', 'dry-erase-pvc-board', 'dry-erase-adhesive-vinyl', 'gatorfoam'),
		),

		'dry-erase-magnet' => array(
			'intro' => array(
				'A dry erase magnet is a 30 mil magnet with a UV printed graphic and a gloss dry erase coating. Stick it to any steel surface, write on it, wipe it clean, and move it whenever you need to.',
				'Order custom sizes up to 24" high by 60" wide, with straight or rounded corners. It is waterproof and UV safe for indoor and outdoor use. Some car doors and refrigerators, including some stainless steel ones, are not magnetic, so test the surface first.',
			),
			'uses' => array('Whiteboards and planners on fridges and filing cabinets', 'Vehicle magnets with changeable details', 'Job boards on steel doors and equipment', 'Room, machine and status labels'),
			'faq' => array(
				array('q' => 'Will it stick to my refrigerator?', 'a' => 'Only if the surface is magnetic. Some stainless steel refrigerators are not; test with a fridge magnet first.'),
				array('q' => 'Can I write on it outdoors?', 'a' => 'Yes, it is waterproof and UV safe, though marker may wash off in rain.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 24" high by 60" wide.'),
			),
			'related' => array('magnets', 'reflective-car-magnet', 'dry-erase-adhesive-vinyl', 'dry-erase-pvc-board'),
		),

		'dry-erase-pvc-board' => array(
			'intro' => array(
				'Dry erase PVC board is a 1/8" expanded PVC sheet, like Sintra, UV printed and sealed with a gloss dry erase coating, so your printed board never goes out of date. It is lightweight, slightly pliable and fits most sign frames, or mounts straight to a wall.',
				'It is waterproof and UV safe for indoor and short term outdoor use, single or double sided, up to 48" x 96". ' . $oversize,
			),
			'uses' => array('Wall mounted schedule and status boards', 'Menu and specials boards', 'Inserts for sign frames and A-frames', 'Classroom and office whiteboards'),
			'faq' => array(
				array('q' => 'Will it fit my sign frame?', 'a' => 'At 1/8" thick it fits most sign frames and display stands.'),
				array('q' => 'Can it go outside?', 'a' => 'Yes for short term outdoor use. For long term outdoor dry erase signs, choose the dry erase aluminum sandwich board.'),
				array('q' => 'Can both sides be printed?', 'a' => 'Yes, single or double sided.'),
			),
			'related' => array('pvc-board', 'dry-erase-aluminum-sandwich-board', 'dry-erase-foamcore', 'reflective-pvc-board'),
		),

		'dry-erase-signicade-a-frame' => array(
			'intro' => array(
				'The dry erase Signicade is a genuine Plasticade Signicade sidewalk A-frame with writable sign faces. Your 24" x 36" graphics are UV printed on adhesive vinyl and UV coated for dry erase, so you can change specials, prices and hours every day while your branding stays put.',
				'The white hollow body frame is 25" W x 45" H x 3" D and weighs 19 lbs. Prints are waterproof and UV safe for years of writing and erasing outdoors. Choose single or double sided.',
			),
			'uses' => array('Restaurant and cafe daily specials', 'Salon, spa and retail sidewalk signs', 'Event and directional signs with changing details', 'Open and sale signs'),
			'faq' => array(
				array('q' => 'What size are the sign faces?', 'a' => '24" wide by 36" tall.'),
				array('q' => 'Can I write on both sides?', 'a' => 'Yes, if you order it double sided.'),
				array('q' => 'Is there a version for nighttime?', 'a' => 'Yes, the reflective Signicade A-frame uses reflective vinyl for night visibility.'),
			),
			'related' => array('standard-signicade-white-frame-graphic', 'reflective-signicade-a-frame', 'deluxe-signicade-graphic-frame', 'simposign-ii-white-graphic-frame'),
		),

		/* Flags */

		'custom-pole-flag-pole' => array(
			// A traditional flag made to size for a house or wall pole (product options:
			// grommet strip left/top or hem only; no pole, pole, or pole with bracket).
			'intro' => array(
				'A custom pole flag is a traditional rectangular flag made to the size you enter, for flying from a porch, wall or storefront flag pole. It is priced per square foot, so a small porch flag and a large storefront flag are both easy to order.',
				'Your design is dye sublimated: a single sided flag is printed through, so the back shows a lighter, mirrored but readable image, and a double sided flag is two prints sewn back to back so both sides read correctly. Choose how it hangs: a hem with a grommet strip on the left side for a vertical pole, a grommet strip along the top, or a plain hem with no grommets. Add our 74.5" aluminum pole, or the pole with a white wall bracket; the pole is recommended for 2\' x 3\' and 3\' x 5\' flags.',
			),
			'uses' => array('Storefront and porch flags', 'Business, school and team flags', 'Open, sale and seasonal flags', 'Flags for events and celebrations'),
			'faq' => array(
				array('q' => 'Which edge finishing should I choose?', 'a' => 'A grommet strip on the left side clips to a standard flag pole. Put it along the top to hang the flag from a rod or wall, or choose a plain hem if you will attach it your own way.'),
				array('q' => 'Does it come with a pole?', 'a' => 'Only if you add one: choose Pole Only for the 74.5" two-piece aluminum pole with a tangle-free spinning top, or Pole w/ Bracket to add the white wall bracket. The <a href="/product/flag-pole-and-bracket/">pole and bracket</a> are also sold on their own.'),
				array('q' => 'Can both sides show my design?', 'a' => 'Yes. Choose double sided for two prints sewn back to back, so the design reads correctly from both sides.'),
			),
			'related' => array('flag-pole-and-bracket', 'rectangle-flag-pole', 'feather-angled-flag-pole', 'teardrop-flag-pole'),
		),

		'econo-feather-flag-pole' => array(
			'intro' => array(
				'The Econo feather flag is our budget friendly 16 ft outdoor flag. A 30" x 137.75" dye sublimated graphic on durable 4 oz polyester flies on a 4 piece brushed aluminum and graphite pole set (three straight sections and one curve), and the free top edge flutters in the wind for eye-catching movement.',
				'It comes with an aluminum ground stake for lawns, planters and soft ground. Flags are single sided print-thru, so the back shows a lighter mirror image; standard flags have the pole sleeve on the left, reversed flags on the right. The flag weighs 0.79 lbs, or 3.39 lbs with the pole.',
			),
			'uses' => array('Storefront and roadside advertising', 'Open house and real estate flags', 'Outdoor events and grand openings', 'Low cost flags in bulk'),
			'faq' => array(
				array('q' => 'How tall is it?', 'a' => 'About 16 ft assembled.'),
				array('q' => 'Can it be double sided?', 'a' => 'No, the Econo feather flag is single sided only. For double sided, choose the Feather Angled flag.'),
				array('q' => 'Can I use it on concrete?', 'a' => 'It ships with a ground stake for soft ground only.'),
			),
			'related' => array('econo-stock-flag-pole', 'feather-angled-flag-pole', 'feather-convex-flag-pole', 'custom-pole-flag-pole'),
		),

		'econo-stock-flag-pole' => array(
			'intro' => array(
				'The Econo stock flag is the fastest way to put a 16 ft flag outside your business: choose from ready-to-print designs instead of creating your own artwork. Each flag is a 30" x 137.75" dye sublimated 4 oz polyester graphic whose free edge flutters in the wind.',
				'It comes with a 4 piece brushed aluminum and graphite pole set and an aluminum ground stake for lawns, planters and soft ground. Flags are single sided print-thru, so the design shows on the back as a lighter mirror image.',
			),
			'uses' => array('Open, sale and grand opening flags', 'Car lots and seasonal promotions', 'Roadside attention for any storefront', 'Outdoor markets and events'),
			'faq' => array(
				array('q' => 'Do I need artwork?', 'a' => 'No. Pick one of the ready-to-print designs. For your own design, choose the Econo feather flag.'),
				array('q' => 'What is included?', 'a' => 'The printed flag, the 4 piece pole set and a ground stake.'),
				array('q' => 'Is it double sided?', 'a' => 'No, stock flags are single sided print-thru.'),
			),
			'related' => array('econo-feather-flag-pole', 'feather-angled-flag-pole', 'teardrop-flag-pole', 'custom-pole-flag-pole'),
		),

		'feather-angled-flag-pole' => array(
			'intro' => array(
				'Feather angled flags are tall, full color "skyscraper" flags with an angled top that keeps the whole graphic visible. Choose Small (9 ft, 23.5" x 78.5" graphic), Medium (10.5 ft, 24" x 104"), Large (14 ft, 28" x 138") or X-Large (18 ft, 24" x 183.5").',
				$flag_print . ' Poles are fiberglass and aluminum, tool free, and an optional zippered carry bag makes travel and storage easy. ' . $flag_bases,
			),
			'uses' => array('Storefronts and roadside visibility', 'Trade shows and indoor lobbies', 'Outdoor events, races and festivals', 'Car dealerships and grand openings'),
			'faq' => array(
				array('q' => 'What is the difference between angled, convex and teardrop flags?', 'a' => 'They differ in shape: angled has a slanted top edge, convex a rounded top edge, and teardrop a rounded teardrop shape.'),
				array('q' => 'Can I use it indoors?', 'a' => 'Yes, with the cross base or square base.'),
				array('q' => 'Is there a carry bag?', 'a' => 'Yes, an optional nylon zippered carry bag.'),
			),
			'related' => array('feather-convex-flag-pole', 'teardrop-flag-pole', 'econo-feather-flag-pole', 'cross-base'),
		),

		/* Event tents */

		'event-tent-full-canopy-graphic-frame' => array(
			'intro' => array(
				'Our 10x10 event tent gives you 360 degrees of branding. The canopy is dye sublimated seam to seam on 6 oz (600 x 600 denier) water proof coated tent polyester, so each of the four peaks and valances carries full color artwork instead of small vinyl patches.',
				'The heavy duty frame uses 40 mm hex aluminum, stronger than steel frame tents that bend and buckle. Telescopic legs adjust the height from 124.5" to 137", and each foot plate can be staked down; ropes, stakes and a carry bag are included. The full package weighs 51 lbs. Add printed walls, flags, a wheeled bag or sandbags.',
			),
			'uses' => array('Outdoor events, fairs and festivals', 'Sports, races and tailgates', 'Farmers markets and pop-up shops', 'Community and promotional events'),
			'faq' => array(
				array('q' => 'What is included?', 'a' => 'The printed canopy, the aluminum hex frame, ropes, stakes and a standard carry bag.'),
				array('q' => 'Is the canopy waterproof?', 'a' => 'The tent fabric is water proof coated and weather resistant.'),
				array('q' => 'How do I keep it steady in wind?', 'a' => 'Stake the foot plates and use the ropes, and add sandbags for hard surfaces.'),
			),
			'related' => array('tent-walls-full-color-full-wall', 'tent-flag', 'sandbag-4pc-set', 'carrying-bag-w-wheels'),
		),

		'tent-flag' => array(
			'intro' => array(
				'Tent flags take your event tent to new heights. Hex clamp connectors fit snugly onto our heavy duty tent legs and hold a full color flag above the canopy, so your brand is seen from across the event, especially with a flag on every corner.',
				'Flags are dye sublimated on durable 4 oz polyester, single or double sided. The connector works with our Feather and Teardrop flags in Small and Medium.',
			),
			'uses' => array('Beach and outdoor events', 'Sports tournaments and races', 'Fairs, festivals and markets', 'Sponsor tents'),
			'faq' => array(
				array('q' => 'Will it fit my tent?', 'a' => 'The hex clamp is made for our 10x10 event tent hardware.'),
				array('q' => 'Which flags work with it?', 'a' => 'Feather and Teardrop flags in Small and Medium.'),
				array('q' => 'Can it be double sided?', 'a' => 'Yes, single or double sided.'),
			),
			'related' => array('event-tent-full-canopy-graphic-frame', 'tent-walls-full-color-full-wall', 'teardrop-flag-pole', 'feather-angled-flag-pole'),
		),

		'tent-walls-full-color-full-wall' => array(
			'intro' => array(
				'Tent walls complete your 10x10 event tent. A full wall (113" wide x 78" tall) attaches with velcro loops under the canopy and drops to within about 4 inches of the floor, making a printed backdrop and a wind break. A half wall (113" x 39") adds branding along the sides while leaving the tent open to visitors.',
				'Walls are dye sublimated on 6 oz water proof coated tent polyester. Full walls are single sided and need no extra hardware; half walls can be single or double sided and hang from our half wall hardware: one telescopic support pole and two hex clamps that attach to the tent frame.',
			),
			'uses' => array('Tent backdrops and wind breaks', 'Side branding that guides visitors to the entrance', 'Sponsor and partner logos', 'Private or shaded areas at events'),
			'faq' => array(
				array('q' => 'Full wall or half wall?', 'a' => 'Full walls close a side and act as a backdrop. Half walls brand the sides while keeping the tent open.'),
				array('q' => 'Do I need hardware?', 'a' => 'Full walls attach with velcro loops and need no hardware. Half walls use the half wall hardware: a support pole and two hex clamps.'),
				array('q' => 'Can walls be double sided?', 'a' => 'Half walls can; full walls are single sided.'),
			),
			'related' => array('event-tent-full-canopy-graphic-frame', 'tent-flag', 'half-wall-hardware', 'sandbag-4pc-set'),
		),

		/* Wall art */

		'canvas-roll' => array(
			'intro' => array(
				'Canvas roll prints are unstretched artist canvas in any height and width, so you can print photos and art at exactly the size you need and frame or stretch them yourself. The 15 mil semi-gloss polyester and cotton blend canvas is made for fade resistant fine art reproduction with rich color.',
				'It arrives rolled, single sided, in custom sizes up to 58" high by 100\' wide, for indoor use.',
			),
			'uses' => array('Fine art and photo reproductions', 'Canvas to stretch on your own bars', 'Large art for framing', 'Wall hangings and posters'),
			'faq' => array(
				array('q' => 'Is it stretched?', 'a' => 'No, it ships rolled. For a ready to hang piece, choose canvas wraps or framed canvas.'),
				array('q' => 'What finish is it?', 'a' => 'A semi-gloss finish.'),
				array('q' => 'What is the largest size?', 'a' => 'Up to 58" high by 100\' wide.'),
			),
			'related' => array('canvas-wrap', 'framed-canvas', 'acrylic-prints', 'adhesive-wall-fabric'),
		),

		'framed-canvas' => array(
			'intro' => array(
				'A framed canvas adds a contemporary, gallery look to your photos and art. Your print is UV printed on canvas and mounted to a hard backing board so it won\'t dent, then set into a 1.25" deep floating frame with a .25" face and a .25" gap around the canvas.',
				'Choose a black, white or pecan frame in 16" x 16", 24" x 24", 16" x 24" or 24" x 36". A hanging kit is included, so it arrives ready to hang.',
			),
			'uses' => array('Home and office wall art', 'Restaurant, hotel and lobby decor', 'Gifts and photo keepsakes', 'Artist and photographer prints'),
			'faq' => array(
				array('q' => 'What sizes are available?', 'a' => '16" x 16", 24" x 24", 16" x 24" and 24" x 36".'),
				array('q' => 'What frame colors can I choose?', 'a' => 'Black, white or pecan.'),
				array('q' => 'Is it ready to hang?', 'a' => 'Yes, a hanging kit is included.'),
			),
			'related' => array('canvas-wrap', 'framed-art', 'framed-prints', 'acrylic-prints'),
		),

		'framed-art' => array(
			'intro' => array(
				'Our framed poster is a complete, classic frame kit for a 16" x 24" print. Your design is UV printed on Double White Popup, then framed in a matte black frame with a mat board, clear Plexiglas glazing and a Masonite backing.',
				'The finished piece is 23" x 31" and arrives with the hanging kit already installed, ready to hang out of the box.',
			),
			'uses' => array('Office and lobby wall art', 'Retail and restaurant decor', 'Movie, event and promotional posters', 'Framed certificates and photos'),
			'faq' => array(
				array('q' => 'What size is it?', 'a' => 'The print is 16" x 24"; with the frame and mat it is 23" x 31".'),
				array('q' => 'Is it glass?', 'a' => 'No, the glazing is clear Plexiglas (acrylic) rather than glass.'),
				array('q' => 'Is it ready to hang?', 'a' => 'Yes, the hanging kit is preinstalled.'),
			),
			'related' => array('framed-canvas', 'framed-prints', 'posters', 'magnetic-wood-frame-hanger'),
		),

		'magnetic-wood-frame-hanger' => array(
			'intro' => array(
				'A magnetic wood frame hanger is a simple, natural way to display a poster. Your artwork is UV printed on 14 mil Double White Popup, then held top and bottom by wooden rails that snap together with strong rare earth magnets.',
				'Choose 17.5" x 24" or 17.5" x 36". The set includes hanging twine, so it hangs from a single nail; some assembly is required.',
			),
			'uses' => array('Cafe, shop and salon decor', 'Menus and price lists', 'Home and office art', 'Swappable seasonal posters'),
			'faq' => array(
				array('q' => 'How do I change the poster?', 'a' => 'Pull the magnetic rails apart, swap the print and snap them back together.'),
				array('q' => 'What sizes are available?', 'a' => '17.5" x 24" and 17.5" x 36".'),
				array('q' => 'What is included?', 'a' => 'The printed poster, one set of wooden magnetic rails and hanging twine.'),
			),
			'related' => array('framed-art', 'snap-poster-graphic-hanger', 'posters', 'canvas-roll'),
		),

		/* Sign holders and A-frames */

		'poster-graphic-stand' => array(
			'intro' => array(
				'Our floor standing poster stand is a 60" tall, powder coated matte black steel frame that holds a 22" x 28" rigid graphic up to 1/4" thick. It weighs 16 lbs, so it stays put in busy aisles and entrances.',
				'The graphic is a 1/8" white PVC sign, direct UV printed on both sides, that drops into the frame for a quick change. It is made for indoor retail and lobby use.',
			),
			'uses' => array('Store entrances and aisle promotions', 'Lobby and reception signs', 'Wayfinding at events and venues', 'Menu and specials signs'),
			'faq' => array(
				array('q' => 'What size graphic does it hold?', 'a' => '22" x 28", up to 1/4" thick.'),
				array('q' => 'Is the graphic double sided?', 'a' => 'Yes, the PVC graphic is printed on both sides.'),
				array('q' => 'Can I change the graphic?', 'a' => 'Yes, graphics drop in and out. Order the graphic only for replacements.'),
			),
			'related' => array('pvc-board', 'x-stand-graphic-stand', 'standard-retractable-insert-stand', 'snap-poster-graphic-hanger'),
		),

		'simposign-ii-white-graphic-frame' => array(
			'intro' => array(
				'The SimpoSign II is the lightweight member of the Plasticade Signicade family. The molded plastic sandwich board weighs just 10 lbs, with a stabilizing bar and a hollow core you can fill with sand or water for extra weight outdoors.',
				'It has recessed 22" x 28" graphic areas on the front and back for quick change graphics. Inserts are full color UV printed 4mm white coroplast for a durable matte print; order one or two. The frame is 37" H x 24" W x 2" D.',
			),
			'uses' => array('Sidewalk and storefront signs', 'Open house and real estate signs', 'Event and parking directions', 'Restaurant and retail promotions'),
			'faq' => array(
				array('q' => 'How heavy is it?', 'a' => 'About 10 lbs empty. Fill the hollow core with sand or water for more weight.'),
				array('q' => 'What size are the graphics?', 'a' => '22" wide by 28" tall.'),
				array('q' => 'SimpoSign II or Signicade?', 'a' => 'The SimpoSign II is lighter and more portable with 22" x 28" graphics. The Signicade is heavier duty with larger 24" x 36" graphics.'),
			),
			'related' => array('standard-signicade-white-frame-graphic', 'deluxe-signicade-graphic-frame', 'dry-erase-signicade-a-frame', 'coroplast'),
		),

		'standard-signicade-white-frame-graphic' => array(
			'intro' => array(
				'The Standard Signicade is a heavy duty plastic sidewalk A-frame, the classic sandwich board seen outside shops and restaurants. Fill the white hollow body with sand or water for stability, and recessed sign areas help protect the sign faces from scuffs.',
				'Your 24" x 36" graphics are UV printed on permanent adhesive vinyl and mounted to the Signicade before it ships, single sided (front) or double sided (front and back). The frame is 46" H x 25" W x 3" D and weighs 19 lbs.',
			),
			'uses' => array('Restaurant and cafe sidewalk signs', 'Retail sale and open signs', 'Event, parking and directional signs', 'Salon, clinic and office entrance signs'),
			'faq' => array(
				array('q' => 'What size are the graphics?', 'a' => '24" wide by 36" tall.'),
				array('q' => 'Does it arrive ready to use?', 'a' => 'Yes, graphics are mounted to the frame before it ships.'),
				array('q' => 'Can I write on it?', 'a' => 'Choose the dry erase Signicade for writable faces, or the reflective Signicade for night visibility.'),
			),
			'related' => array('dry-erase-signicade-a-frame', 'reflective-signicade-a-frame', 'deluxe-signicade-graphic-frame', 'simposign-ii-white-graphic-frame'),
		),

		'reflective-signicade-a-frame' => array(
			'intro' => array(
				'The reflective Signicade is a sidewalk A-frame that works day and night. Its 24" x 36" graphics are UV printed on 4 mil engineer grade reflective vinyl, so headlights make your message shine back at drivers after dark.',
				'The genuine Plasticade Signicade frame has a white hollow body you can fill for stability, measures 45" H x 25" W x 3" D and weighs 19 lbs. Graphics are waterproof and UV safe for years of outdoor use, single or double sided.',
			),
			'uses' => array('Parking lot and driveway signs', 'Evening restaurant and bar signs', 'Event and traffic direction signs', 'Signs near roads that must be seen at night'),
			'faq' => array(
				array('q' => 'How does reflective vinyl work?', 'a' => 'It bounces light from headlights and flashlights back toward the viewer, so the sign is readable at night without its own lighting.'),
				array('q' => 'What size are the graphics?', 'a' => '24" wide by 36" tall.'),
				array('q' => 'Can it be double sided?', 'a' => 'Yes.'),
			),
			'related' => array('standard-signicade-white-frame-graphic', 'dry-erase-signicade-a-frame', 'reflective-aluminum-sign', 'reflective-coroplast-sign-hstake'),
		),

		/* Real estate */

		'real-estate-frame-sign' => array(
			'intro' => array(
				'The real estate frame is a sturdy black powder coated steel frame for lawns, medians and fields. Its pointed legs act as stakes and drive into turf easily, and the rust resistant finish holds up outdoors.',
				'The 25.25" W x 43" H frame holds one double sided 24" x 18" sign and a 6" x 24" rider, which drop in and are held by clips, so changing signs takes seconds. We suggest coroplast for economical outdoor signs. Keep important text at least .75" from the edges, which the frame may cover.',
			),
			'uses' => array('For sale and open house signs', 'Rider signs for agents and status updates', 'New development and leasing signs', 'Event and business lawn signs'),
			'faq' => array(
				array('q' => 'What sign size does it hold?', 'a' => 'One double sided 24" x 18" sign plus a 6" x 24" rider.'),
				array('q' => 'How is it installed?', 'a' => 'Push the pointed legs into soft ground; no separate stake is needed.'),
				array('q' => 'How much margin should my design have?', 'a' => 'Keep important elements at least .75" from the edges of the sign and rider.'),
			),
			'related' => array('real-estate-post-sign', 'real-estate-a-frame-sign', 'yard-sign-and-h-stake', 'coroplast'),
		),

		'real-estate-post-sign' => array(
			'intro' => array(
				'Our real estate post is a lightweight PVC post and arm that one person can put up. The quick-install ground stake hammers into the ground with no digging, then the post fits over it. The arm, stake, caps and hanging hardware all store inside the post for compact transport.',
				'Signs are double sided aluminum sandwich board, UV printed for long lasting outdoor use, with 1/4" rounded corners, pre-drilled to hang from the arm. The small post holds 24" x 18" or 24" x 36" signs and the large post 36" x 24" or 36" x 48", with 24" x 6" and 36" x 6" riders available.',
			),
			'uses' => array('Listing and for sale signs', 'Commercial property and leasing signs', 'New home community signs', 'Agent and brokerage branding'),
			'faq' => array(
				array('q' => 'Do I need a shovel?', 'a' => 'No. Hammer in the ground stake and slide the post over it.'),
				array('q' => 'What sign sizes fit?', 'a' => 'Small post: 24" x 18" or 24" x 36". Large post: 36" x 24" or 36" x 48".'),
				array('q' => 'Can I add a rider?', 'a' => 'Yes, 24" x 6" and 36" x 6" riders are available.'),
			),
			'related' => array('real-estate-frame-sign', 'real-estate-a-frame-sign', 'aluminum-sandwich-board', 'yard-sign-and-h-stake'),
		),

		'yard-sign-and-h-stake' => array(
			'intro' => array(
				'Our yard sign is a 24" x 18" double sided coroplast sign, the economical, durable staple of outdoor advertising. It is full color UV printed on 4mm corrugated plastic with vertical flutes, so a metal H-stake slides straight in.',
				'Add the 10" x 30" steel H-stake to push the sign into soft ground in seconds. For other sizes, order from our coroplast product page.',
			),
			'uses' => array('Open house and real estate signs', 'Political and campaign signs', 'Contractor and service job site signs', 'Event, school and celebration signs'),
			'faq' => array(
				array('q' => 'Is it double sided?', 'a' => 'Choose single or double sided printing.'),
				array('q' => 'Does the stake fit the sign?', 'a' => 'Yes, the flutes run vertically so the H-stake slides in.'),
				array('q' => 'Can I get a different size?', 'a' => 'Yes, the coroplast page offers sizes from 18" x 12" to 24" x 36" and custom sizes up to 4 ft x 8 ft.'),
			),
			'related' => array('coroplast', 'reflective-coroplast-sign-hstake', 'dry-erase-coroplast-sign-hstake', 'real-estate-frame-sign'),
		),

		/* Reflective */

		'reflective-adhesive-vinyl' => array(
			'intro' => array(
				'Reflective adhesive vinyl makes any flat surface visible at night. It is a 4 mil engineer grade prismatic reflective film with a permanent adhesive, UV printed in full color, so your graphic shines back when headlights or flashlights hit it.',
				'Use it for car decals, safety and warning signs, and graphics on doors, gates and equipment. It is waterproof and UV safe for years outdoors, single sided, up to 48" high by 100\' wide.',
			),
			'uses' => array('Car, truck and trailer decals', 'Safety and warning markings', 'Gate, driveway and address graphics', 'Night visible event and parking signs'),
			'faq' => array(
				array('q' => 'Where does it stick?', 'a' => 'Flat surfaces such as sign blanks, metal, doors and vehicles.'),
				array('q' => 'How long does it last outside?', 'a' => 'It is waterproof and UV safe for years of outdoor use.'),
				array('q' => 'Do you make reflective signs too?', 'a' => 'Yes, reflective vinyl is mounted on aluminum, coroplast, PVC and magnets in our reflective line.'),
			),
			'related' => array('reflective-aluminum-sign', 'reflective-car-magnet', 'reflective-pvc-board', 'reflective-coroplast-sign-hstake'),
		),

		'reflective-pvc-board' => array(
			'intro' => array(
				'Reflective PVC board combines our affordable 1/8" PVC board with 4 mil engineer grade reflective vinyl, for signs that stand out at night. The UV printed reflective graphic bounces headlights back to the viewer.',
				'At 1/8" thick it fits a variety of display stands and frames and mounts easily to walls. It is waterproof and UV safe for indoor and short term outdoor use, single or double sided, up to 48" x 96". ' . $oversize,
			),
			'uses' => array('Parking garage and lot signs', 'Night visible wall signs', 'Inserts for outdoor frames', 'Safety and directional signs'),
			'faq' => array(
				array('q' => 'Is it good for long term outdoor use?', 'a' => 'PVC is for indoor and short term outdoor use. For signs that stay outside for years, choose the reflective aluminum sign or sandwich board.'),
				array('q' => 'Will it fit my frame?', 'a' => 'At 1/8" thick it fits most frames and display stands.'),
				array('q' => 'Can both sides be printed?', 'a' => 'Yes, single or double sided.'),
			),
			'related' => array('pvc-board', 'reflective-aluminum-sandwich-board', 'reflective-aluminum-sign', 'dry-erase-pvc-board'),
		),

		/* Transfers */

		'dtf' => array(
			'intro' => array(
				'DTF (direct to film) transfers let you put full color graphics on almost any fabric. We print your design on cold peel transfer film in CMYK plus white ink, backed with a flood white and a white melt adhesive, then you press it onto the garment with a heat press. It works on cotton and polyester blends, hats, tote bags and other fabric goods, on light and dark colors.',
				'Transfers are stable, so you can print now and press on demand. Send a PNG with a transparent background (300 dpi recommended), with 1/8" safety margin on all edges, up to 22" x 60"; gang several designs on one sheet to make the most of the price. As a starting point, press at 290-320°F for 10-20 seconds at 35-50 PSI after a 5-7 second pre-press, then a 5-7 second post-press, and adjust for your machine and garment.',
			),
			'uses' => array('T-shirts, hoodies and team uniforms', 'Hats, tote bags and accessories', 'Print shops and apparel decorators', 'Small batch and on-demand merchandise'),
			'faq' => array(
				array('q' => 'What file do I send?', 'a' => 'A PNG with a transparent background, ideally 300 dpi, with 1/8" safety margin on all edges.'),
				array('q' => 'How do I apply it?', 'a' => 'With a heat press. As a starting point: pre-press 5-7 seconds, press at 290-320°F for 10-20 seconds at 35-50 PSI, then post-press 5-7 seconds with a teflon sheet. Peel cold.'),
				array('q' => 'Does it work on dark shirts?', 'a' => 'Yes, the white ink backing makes colors pop on light and dark garments.'),
			),
			'related' => array(),
		),
	);
}
