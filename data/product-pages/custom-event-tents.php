<?php
/**
 * Product page copy: event tents, tent walls, tent flags and tent hardware.
 * See inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

$tent_care = 'Hand wash tent graphics with a soft brush, mild soap and warm water; they are not machine washable. Let the canopy dry completely before you pack it, and take the canopy off before you fold the frame. Weigh or stake every leg whenever the tent is up, and take it down in high wind.';

return array(
	'event-tent-full-canopy-graphic-frame' => array(
		'title' => array('Custom Event Tents', '10x10 Canopy'),
		'description' => 'Custom 10x10 event tents: a full color dye sublimated canopy on a 40 mm hex aluminum frame, with ropes, stakes and carry bag. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'Can each side have different artwork?', 'a' => 'Yes. Upload a separate design for each of the four sides (A, B, C and D).'),
			array('q' => 'Can I leave the canopy on when I fold the frame?', 'a' => 'No. Take the canopy graphic off before you retract the frame, so it doesn&rsquo;t get pinched or torn.'),
		),
		'care' => $tent_care,
	),
	'event-tent-full-canopy-graphic-only' => array(
		'description' => 'Replacement full color 10x10 event tent canopy, dye sublimated seam to seam on 6 oz waterproof coated polyester. Frame not included. Ships nationwide.',
	),

	'tent-flag' => array(
		'title' => array('Event Tent Flags', 'Clamp-On'),
		'description' => 'Tent flags: a feather or teardrop flag on a hex clamp that mounts to your event tent leg, printed single or double sided on polyester. Ships nationwide.',
		'faq' => array(
			array('q' => 'How do I put the flag up?', 'a' => 'Clamp the connector to a tent leg, connect the pole sections, slide the flag sleeve onto the pole and secure the flag down the pole.'),
		),
		'care' => 'flag',
	),

	'tent-walls-full-color-full-wall' => array(
		'title' => array('Custom Tent Walls', '10x10 Event Tent'),
		'description' => 'Custom tent walls for 10x10 event tents: dye sublimated full walls that hang from the canopy as a backdrop and wind break. Ships nationwide. Order online.',
		'faq' => array(
			array('q' => 'Can I put full walls on all four sides?', 'a' => 'Yes, as long as the canopy has Velcro on those sides. Full walls attach to Velcro along the canopy&rsquo;s edge, and you choose which sides get Velcro when you order the canopy.'),
		),
		'care' => $tent_care,
	),
	'tent-walls-full-color-half-wall' => array(
		'description' => 'Custom half walls for 10x10 event tents, 113" x 39", printed single or double sided to brand the sides of your booth. Ships nationwide. Order online.',
	),

	'carrying-bag-w-wheels' => array(
		'title' => array('Event Tent Bag with Wheels', ''),
		'description' => 'Wheeled event tent bag with rubber casters, several handles and a second compartment for the canopy and walls. Roll it to your spot. Ships nationwide.',
		'faq' => array(
			array('q' => 'How many graphics fit in the second compartment?', 'a' => 'Either one canopy top, or one full wall and two half walls.'),
		),
	),

	'event-tent-frame-only' => array(
		'title' => array('Event Tent Frame', '10x10 Hex Aluminum'),
		'description' => '10x10 event tent frame only: heavy duty 40 mm hex aluminum legs, adjustable height, foot plates, ropes, stakes and bag. No canopy. Ships nationwide.',
		'care' => $tent_care,
	),

	'flag-holder-hardware' => array(
		'title' => array('Event Tent Flag Holder', 'Hex Clamps'),
		'description' => 'Event tent flag holder: a flag adapter and two hex clamps that mount a small or medium feather or teardrop flag to a tent leg. Ships nationwide. Order now.',
	),

	'half-wall-hardware' => array(
		'title' => array('Tent Half Wall Hardware', 'Pole & Clamps'),
		'description' => 'Half wall hardware for event tents: a telescopic support pole and two hex clamps that hold a printed half wall straight. Ships nationwide. Order online.',
	),

	'sandbag-4pc-set' => array(
		'title' => array('Event Tent Sandbags', '4 Piece Set'),
		'description' => 'Event tent sandbags: 4 nylon bags that strap to each leg with velcro and hold up to 19 lb of sand each, for wind and pavement. Ships nationwide.',
	),
);
