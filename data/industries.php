<?php
/**
 * Industry pages (/industries/{slug}/). National pages, no location.
 *
 * Rules: recommend only products we sell (product slugs below); no invented
 * projects or reviews. 'examples' stays empty until there are real ones:
 * array('image' => URL, 'alt' => '', 'caption' => 'Business, City, ST').
 * Set 'status' => 'live' after approval.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

return array(
	array(
		'slug' => 'restaurant-signs',
		'name' => 'Restaurants & Cafes',
		'status' => 'live',
		'seo_title' => 'Restaurant Signs: Lit Storefront Letters, Menus & Banners',
		'seo_description' => 'Restaurant and cafe signs that work day and night: LED channel letters, window graphics, sidewalk menu boards and opening banners, priced online.',
		'h1' => 'Restaurant and Cafe Signs That Bring Diners In',
		'intro' => array(
			'A restaurant sign works two shifts. By day it tells passing drivers and walkers what you serve; after dark it&rsquo;s often the only thing that says you&rsquo;re open. Most restaurants pair a lit building sign with signs closer to the door for the menu, hours and today&rsquo;s specials.',
		),
		'needs' => array(
			array('Visible at night', 'Dinner service happens after dark for much of the year. A lit sign, ideally channel letters, keeps your name readable from the road all evening.'),
			array('Say what you serve', 'Your name alone may not say &ldquo;tacos&rdquo; or &ldquo;sushi&rdquo;. Window graphics and a sidewalk board can show the cuisine, hours and delivery apps at eye level.'),
			array('Change the message daily', 'Specials, happy hour and events change often. A dry-erase sidewalk sign or swappable A-frame graphic lets you update without reprinting.'),
			array('Open before the permanent sign', 'Permits for a lit sign can take weeks. A banner announces &ldquo;coming soon&rdquo; and your opening while you wait (check local temporary sign rules).'),
		),
		'products' => array(
			'standard-channel-letter-front-lit' => 'Bright, readable letters for the building.',
			'standard-channel-letter-front-back-lit' => 'Face and halo glow for bars and night spots.',
			'dry-erase-signicade-a-frame' => 'Write today&rsquo;s specials by hand.',
			'adhesive-window-perf' => 'Show food photos while keeping the view out.',
			'13oz-vinyl-banner' => 'Coming soon and grand opening banners.',
			'feather-angled-flag-pole' => 'Catch drivers on busy roads.',
		),
		'tips' => array(
			'Size letters for the road, not the sidewalk: a common sign industry rule of thumb is about 1 inch of letter height for every 10 feet of viewing distance, so a sign read from across a 4-lane road or a parking lot usually needs 12-inch letters or taller.',
			'Front lit letters read best from a distance; front and back lit letters add a halo on the wall that suits bars and evening dining. Keep the color of the lit face close to your brand, but make sure it contrasts with the wall behind it.',
			'Food photos in the window sell better than words. See-through window perf lets you cover the glass with images while diners inside can still see out.',
		),
		'faq' => array(
			array('q' => 'What is the best outdoor sign for a restaurant?', 'a' => 'For most restaurants, LED channel letters: they read clearly from the road by day and light up at night. Add window graphics and a sidewalk sign for the menu and specials.'),
			array('q' => 'How big should my restaurant sign letters be?', 'a' => 'Start with how far away people read it. About 1 inch of letter height per 10 feet of viewing distance is a common rule of thumb, then check your lease and city sign limits.'),
			array('q' => 'Can I put up a banner while my restaurant sign is being permitted?', 'a' => 'Many cities allow temporary or grand opening banners with their own permit or time limit. Check your city&rsquo;s temporary sign rules, then order a banner sized to fit.'),
			array('q' => 'Who installs a restaurant sign bought online?', 'a' => 'We make and ship the sign; a licensed local sign installer or electrician mounts and connects it, using the included wiring diagram and install pattern.'),
		),
		'categories' => array('banners' => 'Banners', 'signicade-a-frames' => 'A-frame sidewalk signs', 'adhesive-products' => 'Window graphics'),
		'examples' => array(),
	),
	array(
		'slug' => 'salon-signs',
		'name' => 'Salons & Barbers',
		'status' => 'live',
		'seo_title' => 'Salon & Barbershop Signs: Halo Letters, Window Graphics',
		'seo_description' => 'Signs for hair salons, nail salons, spas and barbershops: halo lit and trimless letters, frosted window graphics and sidewalk signs, priced online.',
		'h1' => 'Salon, Spa and Barbershop Signs',
		'intro' => array(
			'Salons, spas and barbershops sell an experience, and the sign is the first part of it. A polished lit sign, privacy for clients in the chair, and a sidewalk sign for walk-ins cover most of what a beauty business needs.',
		),
		'needs' => array(
			array('Look as good as your work', 'Halo lit, trimless and borderless letters give a clean, upscale finish that suits beauty brands better than a basic box sign.'),
			array('Privacy for clients', 'Frosted or etched window film lets light in while hiding clients in the chair from the street.'),
			array('Win walk-ins', 'A sidewalk sign with services, prices or &ldquo;walk-ins welcome&rdquo; turns foot traffic into appointments.'),
			array('Fit tight frontages', 'Salons are often in narrow strip-center units; letter height and logo size need to fit the landlord&rsquo;s sign band.'),
		),
		'products' => array(
			'hidden-back-halo-lit' => 'A soft halo glow on the wall behind each letter.',
			'inset-acrylic-face-lit-with-border-no-trimcap' => 'Trimless face lit letters with a clean edge.',
			'exposed-acrylic-face-lit-borderless-no-trimcap' => 'Borderless letters for modern brands.',
			'frosted-vinyl-etched' => 'Etched-glass privacy for the front window.',
			'premium-window-cling' => 'Removable clings for promotions.',
			'deluxe-signicade-graphic-frame' => 'A sidewalk sign for walk-ins.',
		),
		'tips' => array(
			'Halo lit letters glow onto the wall, so the wall color matters: a darker wall makes the halo stand out. Ask your landlord whether halo lighting is allowed in the center&rsquo;s sign criteria.',
			'Frost the lower part of the window (where clients sit) and keep the top clear for daylight and your logo. Window film is easy to replace when you rebrand.',
			'Script and decorative fonts are popular with salons but need enough stroke width to be built as channel letters; send your logo for a quote and we&rsquo;ll check it.',
		),
		'faq' => array(
			array('q' => 'What kind of sign is best for a salon?', 'a' => 'Many salons choose halo lit or trimless channel letters for an upscale look, plus frosted window film for client privacy and a sidewalk sign for walk-ins.'),
			array('q' => 'Can you make channel letters from my salon logo?', 'a' => 'Usually yes. Send your logo file for a free quote; very thin script strokes may need small adjustments to be built as lit letters.'),
			array('q' => 'How do I give clients privacy without blocking light?', 'a' => 'Frosted or etched window film diffuses light while hiding what&rsquo;s behind it. Many salons frost only the lower half of the window.'),
		),
		'categories' => array('adhesive-products' => 'Window graphics', 'signicade-a-frames' => 'A-frame sidewalk signs'),
		'examples' => array(),
	),
	array(
		'slug' => 'medical-office-signs',
		'name' => 'Medical & Dental',
		'status' => 'live',
		'seo_title' => 'Medical & Dental Office Signs: Building Letters, Privacy Film',
		'seo_description' => 'Signs for medical, dental and therapy offices: clean lit building letters, frosted privacy film, and aluminum signs for parking and wayfinding.',
		'h1' => 'Medical, Dental and Clinic Signs',
		'intro' => array(
			'Patients look for a clinic when they&rsquo;re in a hurry, unwell or nervous. The best medical signs are calm, clear and easy to find: a readable building sign, privacy on the windows, and simple signs that point to parking and the entrance.',
		),
		'needs' => array(
			array('Easy to find', 'Medical buildings often share a parking lot with other tenants. A lit building sign with the practice name, readable from the lot, saves patients a phone call.'),
			array('Patient privacy', 'Frosted or etched film on exam and waiting room windows keeps patients out of view while letting daylight in.'),
			array('Clear wayfinding', 'Aluminum signs for reserved parking, entrances and after-hours instructions hold up outdoors for years.'),
			array('A professional look', 'Halo lit and borderless letters suit professional offices; stick to clean, legible fonts.'),
		),
		'products' => array(
			'hidden-back-halo-lit' => 'Understated halo letters for professional offices.',
			'exposed-acrylic-face-lit-borderless-no-trimcap' => 'Clean, readable face lit letters.',
			'frosted-vinyl-etched' => 'Privacy film for exam and waiting rooms.',
			'aluminum-sign' => 'Parking, entrance and hours signs.',
			'reflective-aluminum-sign' => 'Reflective signs that show up in headlights.',
		),
		'tips' => array(
			'Put the most important word first. Patients scan for the type of care (&ldquo;Dental&rdquo;, &ldquo;Urgent Care&rdquo;) as much as the practice name, so consider making it the largest line.',
			'For evening clinics and urgent care, a lit sign is essential; for daytime-only offices, halo lighting gives a softer look that still reads at dusk.',
			'Check your building&rsquo;s sign criteria: medical office parks often specify letter color, mounting and lighting for every tenant.',
		),
		'faq' => array(
			array('q' => 'What sign works best for a dental office?', 'a' => 'A clean lit building sign that names the type of care, plus frosted window film for privacy and aluminum signs for parking and the entrance.'),
			array('q' => 'How can I make clinic windows private?', 'a' => 'Frosted or etched window film hides the inside of exam and waiting rooms while still letting daylight through.'),
			array('q' => 'Do you make reserved parking signs?', 'a' => 'Yes. Our aluminum and reflective aluminum signs are printed to your design for parking, entrances and directions.'),
		),
		'categories' => array('rigid-signs-and-magnets' => 'Rigid and aluminum signs', 'adhesive-products' => 'Window graphics', 'reflective-products' => 'Reflective signs'),
		'examples' => array(),
	),
	array(
		'slug' => 'retail-store-signs',
		'name' => 'Retail Stores',
		'status' => 'live',
		'seo_title' => 'Retail Store Signs: Storefront Letters, Window & Floor Graphics',
		'seo_description' => 'Retail store signs inside and out: lit storefront letters, window graphics, floor graphics, posters and sale banners, with prices online.',
		'h1' => 'Retail Store Signs, Inside and Out',
		'intro' => array(
			'Retail signs have to do three jobs: get shoppers to notice the store, give them a reason to walk in, and guide them once they&rsquo;re inside. That usually means a lit building sign, window graphics that change with the season, and in-store graphics and posters.',
		),
		'needs' => array(
			array('Stand out in the center', 'In a strip center or mall, your building sign competes with every neighbor. Lit channel letters keep your name visible day and night.'),
			array('Windows that sell', 'Seasonal window graphics, sale messages and product photos turn the glass into advertising.'),
			array('Guide shoppers inside', 'Floor graphics, posters and banner stands point shoppers to departments, promotions and the checkout.'),
			array('Sales and events', 'Banners and flags announce sales, openings and new arrivals for a few weeks at a time.'),
		),
		'products' => array(
			'standard-channel-letter-front-lit' => 'The standard lit storefront sign.',
			'adhesive-window-perf' => 'Full-window graphics shoppers can see through from inside.',
			'floor-graphics' => 'Directional and promotional floor decals.',
			'snap-poster-graphic-hanger' => 'Swappable posters for in-store promotions.',
			'standard-retractable-insert-stand' => 'A portable banner stand for the entrance.',
			'13oz-vinyl-banner' => 'Sale and grand opening banners.',
		),
		'tips' => array(
			'Keep the building sign simple: name and, if needed, one line on what you sell. Put details such as sales, hours and brands in the windows, where people read them up close.',
			'Plan window graphics for change. Removable clings and window perf are easy to replace each season; leave clear sightlines to your best displays.',
			'Floor graphics must suit the floor and traffic; choose a floor-rated material for high-traffic aisles.',
		),
		'faq' => array(
			array('q' => 'What signs does a new retail store need?', 'a' => 'Usually a lit building sign, window graphics with your hours and offer, a grand opening banner, and a few in-store signs or posters. Add floor graphics and banner stands as the store grows.'),
			array('q' => 'Can I change my window graphics every season?', 'a' => 'Yes. Window clings and perforated window film are made to be replaced; order new graphics for each season or sale.'),
			array('q' => 'Are floor graphics safe for customers to walk on?', 'a' => 'Our floor graphics are made for floors. Choose the right size and follow the application instructions, and replace them when they wear.'),
		),
		'categories' => array('adhesive-products' => 'Window and floor graphics', 'banner-stands' => 'Banner stands', 'banners' => 'Banners'),
		'examples' => array(),
	),
	array(
		'slug' => 'franchise-signs',
		'name' => 'Franchises & Multi-Location',
		'status' => 'live',
		'seo_title' => 'Franchise & Multi-Location Signs: Consistent Channel Letters',
		'seo_description' => 'Signs for franchise and multi-location businesses: consistent channel letters and graphics for each new site, shipped to every state, priced per location.',
		'h1' => 'Signs for Franchises and Multi-Location Businesses',
		'intro' => array(
			'When you open more than one location, the sign has to match every time: same letter style, same colors, same lighting, adjusted only for each site&rsquo;s wall, landlord criteria and local permit limits. Ordering online, with every sign made in one shop and shipped to each location, makes that consistency easier.',
		),
		'needs' => array(
			array('Brand consistency', 'The same letter style, colors and lighting at every location, built from one approved design.'),
			array('Fit each site', 'Each landlord and city sets size and lighting limits. The same design often needs a different letter height per location.'),
			array('Ship to every opening', 'We ship to all 50 states, so each sign goes straight to its location for the local installer.'),
			array('Opening kits', 'Grand opening banners, window graphics and flags for each new site.'),
		),
		'products' => array(
			'standard-channel-letter-front-lit' => 'Consistent lit letters for every location.',
			'standard-channel-letter-front-back-lit' => 'Dual lit letters for a signature look.',
			'adhesive-window-perf' => 'Branded window graphics for each store.',
			'13oz-vinyl-banner' => 'Grand opening banners for each launch.',
			'feather-angled-flag-pole' => 'Opening-week flags for visibility.',
		),
		'tips' => array(
			'Keep a sign spec on file: letter style, face and return colors, lighting, and the minimum and maximum letter heights your brand allows. It makes each new location faster to quote.',
			'Collect each site&rsquo;s landlord sign criteria and city limits before ordering; the sign band and allowed sign area often differ even within the same shopping center chain.',
			'Each location still needs its own sign permit and licensed installer. Our state guides list who licenses sign installers in each state.',
		),
		'faq' => array(
			array('q' => 'Can you make the same sign for all of our locations?', 'a' => 'Yes. We build each location&rsquo;s sign to the same design, adjusting letter height or layout where a site&rsquo;s criteria require it, and ship it to that location.'),
			array('q' => 'Do you ship to locations in different states?', 'a' => 'Yes. We ship to all 50 states. Each location&rsquo;s sign goes directly to that site, ready for a local licensed installer.'),
			array('q' => 'How do we get pricing for several locations?', 'a' => 'Send your logo, sign spec and the list of locations with their sign band sizes, and we&rsquo;ll quote each sign.'),
		),
		'categories' => array('banners' => 'Banners', 'advertising-flags' => 'Advertising flags', 'adhesive-products' => 'Window graphics'),
		'examples' => array(),
	),
	array(
		'slug' => 'event-trade-show-signs',
		'name' => 'Events & Trade Shows',
		'status' => 'live',
		'seo_title' => 'Event & Trade Show Signs: Banners, Booth Displays, Flags',
		'seo_description' => 'Signs for events, trade shows and pop-ups: banner stands, SEG booth displays, table covers, tents, flags and banners. No installer needed; prices online.',
		'h1' => 'Signs for Events, Trade Shows and Pop-Ups',
		'intro' => array(
			'Event signs have different rules from storefront signs: they have to travel, set up fast, and look sharp under bright lights. Unlike lit building signs, banners, stands and booth displays need no electrician; you set them up yourself. (Large tents and outdoor banners can still fall under venue or local rules, so check first.)',
		),
		'needs' => array(
			array('Set up in minutes', 'Retractable banner stands set up in minutes and pack in a bag; fabric displays slip a printed graphic over a lightweight frame.'),
			array('A complete booth', 'A backwall, a table cover and one or two banner stands make a 10-foot booth look finished.'),
			array('Outdoor events', 'Canopy tents, feather flags and mesh banners stand up to outdoor markets, races and festivals.'),
			array('Reuse it', 'Most stands and frames take replacement graphics, so you can update the message for the next show.'),
		),
		'products' => array(
			'standard-retractable-insert-stand' => 'The classic pull-up banner stand.',
			'10ft-seg-fabric-display-graphic-frame' => 'A 10ft fabric backwall for your booth.',
			'6ft-table-cover' => 'A printed cover for the booth table.',
			'event-tent-full-canopy-graphic-frame' => 'A printed canopy tent for outdoor events.',
			'feather-angled-flag-pole' => 'Feather flags to mark your spot.',
			'step-and-repeat-backdrop-graphic-frame' => 'Photo backdrops for events and launches.',
		),
		'tips' => array(
			'Check the venue&rsquo;s booth rules first: backwall height limits and fire-rating requirements vary by venue.',
			'Design for distance: put your name and one key message in the top third of a banner stand, where people see it over the crowd.',
			'Order with time to spare and choose a faster shipping speed at checkout if the show date is close; production time is included in the dates checkout shows.',
		),
		'faq' => array(
			array('q' => 'What do I need for a 10x10 trade show booth?', 'a' => 'Typically a 10ft backwall display, a printed table cover, and one or two retractable banner stands. Add a tent and flags for outdoor events.'),
			array('q' => 'Do banner stands or booth displays need an installer?', 'a' => 'No. Banner stands, fabric displays, table covers and flags are set up by hand, with no electrician needed. Check venue rules for booth heights and any tent or outdoor sign requirements.'),
			array('q' => 'Can I reuse my display for the next event?', 'a' => 'Yes. Most stands and frames take replacement graphics, so you only reorder the print when your message changes.'),
		),
		'categories' => array('trade-show-products' => 'Trade show displays', 'banner-stands' => 'Banner stands', 'custom-event-tents' => 'Event tents', 'table-throws' => 'Table covers', 'advertising-flags' => 'Advertising flags'),
		'examples' => array(),
	),
);
