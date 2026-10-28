<?php
/**
 * Product page copy: banner stands and backdrops. See
 * inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

$margins = array('q' => 'How much margin should my artwork have?', 'a' => 'Keep text and logos at least 1" from the top and 3" from the bottom. The bottom of the graphic sits inside the base, so anything closer to the edges may be hidden.');
$retract_care = 'stand';

return array(
	'deluxe-retractable-insert-stand' => array(
		'title' => array('Deluxe Retractable Banner Stand', ''),
		'description' => 'Deluxe retractable banner stand with a wide chrome-capped base, adjustable pole and curl-free fabric or vinyl print. Ships nationwide. Order online.',
		'file_notes' => array('Keep important elements 1" from the top and 3" from the bottom of the graphic.'),
		'faq' => array(
			array('q' => 'Can I add a light?', 'a' => 'Yes, LED lights are an option for the Deluxe stand. They clip to the top of the pole and light the graphic from above.'),
		),
		'care' => $retract_care,
	),
	'deluxe-retractable-insert-only' => array(
		'description' => 'Replacement graphic for the Deluxe retractable banner stand, UV printed on curl-free blockout fabric or 13oz vinyl. No stand. Ships nationwide. Order now.',
	),

	'sd-retractable-sd-retractable-insert-stand' => array(
		'title' => array('SD Retractable Banner Stand', 'Premium'),
		'description' => 'SD retractable banner stand: our most stable roll-up, with a brushed aluminum base, adjustable pole and hard travel case. Ships nationwide. Order online.',
		'file_notes' => array('Keep important elements 1" from the top and 3" from the bottom of the graphic.'),
		'faq' => array(
			array('q' => 'Can I use a shorter graphic?', 'a' => 'Yes. The support pole adjusts, so the same stand can show a shorter or taller graphic.'),
		),
		'care' => $retract_care,
	),
	'sd-retractable-sd-retractable-insert-only' => array(
		'description' => 'Replacement graphic for the SD retractable banner stand, on blockout fabric or 13oz matte vinyl. Stand not included. Ships nationwide. Order online.',
	),

	'standard-retractable-insert-stand' => array(
		'title' => array('Retractable Banner Stands', 'Standard'),
		'description' => 'Standard retractable banner stands: a light roll-up stand with fold-out feet, a curl-free printed graphic and carry bag. Ships nationwide. Order online.',
		'faq' => array($margins),
		'care' => $retract_care,
	),
	'standard-retractable-insert-only' => array(
		'description' => 'Replacement graphic for the Standard retractable banner stand, UV printed on curl-free fabric or 13oz vinyl. No stand. Ships nationwide. Order now.',
	),

	'step-and-repeat-backdrop-graphic-frame' => array(
		'title' => array('Step and Repeat Backdrop', 'With Frame'),
		'description' => 'Step and repeat backdrops on a 1.625" tube frame with a glare-free 9oz fabric print, 6\' x 4\' to 10\' x 8\'. Carry bag included. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'How does the frame go together?', 'a' => 'Join the top poles and the bottom poles and extend their telescoping ends, bolt each upright into its base, run the horizontal poles through the graphic&rsquo;s top and bottom pole pockets, then tighten the thumb screws to lock them to the uprights.'),
		),
		'care' => 'The 9oz polyester graphic can be machine washed in cold water on a gentle cycle; lay it flat or hang it to dry and steam out wrinkles. Do not bleach, tumble dry or iron it. Pack the poles and feet in the carry bag after each event.',
	),
	'step-and-repeat-backdrop-graphic-only' => array(
		'description' => 'Replacement step and repeat graphic on 9oz wrinkle free fabric with 3" pole pockets, from 6\' x 4\' to 10\' x 8\'. No frame. Ships nationwide. Order now.',
	),

	'table-top-banner-stand' => array(
		'title' => array('Table Top Banner Stand', 'Mini Retractable'),
		'description' => 'Table top banner stand: a mini retractable with an 11.5" x 17.5" graphic for counters and trade show tables. Fits a carry-on. Ships nationwide. Order now.',
		'file_notes' => array('Build the graphic at 11.5" x 17.5". The bottom 1.5" may stay inside the base, so keep text and logos above it.'),
		'faq' => array(
			array('q' => 'Can I take it on a plane?', 'a' => 'Yes. It is small enough for carry-on luggage or checked bags.'),
			array('q' => 'Can my design leave blank space?', 'a' => 'Yes. Your design can be smaller than the banner area; fill the rest with white or a solid color.'),
		),
		'care' => $retract_care,
	),

	'tension-fabric-stand-graphic-frame' => array(
		'title' => array('Tension Fabric Banner Stand', 'Zip-On'),
		'description' => 'Tension fabric banner stand: a tool-free tube frame with a zip-on fabric graphic, single or double sided, on a steel base. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'Is double sided more expensive?', 'a' => 'No. Single and double sided pillowcase graphics are the same price.'),
		),
		'care' => 'The polyester fabric graphic can be machine washed in cold water on a gentle cycle and hung to dry; do not bleach, tumble dry or iron it. Pack the numbered tubes and base plate in the carry case so the frame goes back together quickly.',
	),
	'tension-fabric-stand-graphic-only' => array(
		'description' => 'Replacement zip-on graphic for the tension fabric stand, dye sublimated single or double sided, 36" or 48" wide. No frame. Ships nationwide. Order now.',
	),

	'tension-fabric-stand-hardware-only' => array(
		'title' => array('Tension Fabric Stand Frame', 'Hardware Only'),
		'description' => 'Tension fabric stand frame only: tool-free aluminum tubes, steel base plate and carry bag, 36" or 48" wide by 90" tall. Ships nationwide. Order now.',
		'printed' => false,
	),

	'x-stand-graphic-stand' => array(
		'title' => array('X-Stand Banners', 'Budget Banner Stand'),
		'description' => 'X-stand banner stands: our most affordable display, with a grommeted 24" x 63" or 32" x 71" graphic you swap in seconds. Ships nationwide. Order online.',
		'faq' => array(
			array('q' => 'How does the banner attach?', 'a' => 'The graphic has a grommet in each corner that hooks onto the ends of the X frame, which keeps it pulled flat.'),
		),
		'care' => $retract_care,
	),
	'x-stand-graphic-only' => array(
		'description' => 'Replacement X-stand banner, 24" x 63" or 32" x 71", on 13oz vinyl or curl-free double white popup with grommets. No stand. Ships nationwide. Order now.',
	),
);
