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

function wholesale_seo_sitemap_exclude_duplicate_products($args, $post_type)
{
	if ('product' === $post_type && ($duplicates = wholesale_seo_duplicate_product_ids())) {
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
 * Title and description for a product that is not a channel letter style.
 *
 * @return array
 */
function wholesale_seo_product_meta($product_id)
{
	$name = wp_strip_all_tags(get_the_title($product_id));
	$name = preg_replace('/\s+Copy$/', '', $name);
	$variant = wholesale_seo_product_variant_label($product_id);
	$label = $variant ? sprintf('%s (%s)', $name, $variant) : $name;

	$title = sprintf('%s | %s', $label, get_bloginfo('name'));
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

	$prefix = $variant ? $name . ', ' . strtolower($variant) . ': ' : $name . ': ';
	$description = wholesale_seo_trim_description($source, 155 - mb_strlen($prefix));

	return array(
		'title' => $title,
		'description' => $description ? $prefix . $description : '',
	);
}
