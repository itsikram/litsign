<?php
/**
 * Product page copy: tension fabric and Velcro pop up trade show displays.
 * See inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

$tension_setup = array('q' => 'How do I set it up?', 'a' => 'Connect the numbered frame tubes, wrap the fabric graphic around the frame from left to right and zip it closed. If you added lights, clip them to the top of the frame. No tools are needed.');
$popup_setup = array('q' => 'How do I set it up?', 'a' => 'Pull the accordion frame open until it locks into shape, then press the graphic&rsquo;s sewn-on Velcro onto the Velcro on the frame, wrapping the printed ends around the sides.');
$case = array('q' => 'Can I get a hard case?', 'a' => 'Yes. A durable plastic hard case is optional, and it doubles as a podium (37" W x 37" H x 20" D) at the show.');
$fabric_care = 'The tension fabric graphic can be machine washed in cold water on a gentle cycle and hung to dry; don&rsquo;t bleach, tumble dry or iron it. Fold the frame and pack it in its bag or case after each show.';

return array(
	'curved-tension-fabric-display-graphic-frame' => array(
		'title' => array('Curved Tension Fabric Display', ''),
		'description' => 'Curved tension fabric displays: a tool-free tube frame with a zip-on fabric print in 6 ft tabletop, 8 ft or 10 ft sizes, with carry bag. Ships nationwide.',
		'faq' => array($tension_setup),
		'care' => $fabric_care,
	),
	'curved-tension-fabric-display-graphic-only' => array(
		'description' => 'Replacement zip-on graphic for the curved tension fabric display, dye sublimated on 8.8oz fabric in 6, 8 or 10 ft sizes. Ships nationwide. Order now.',
	),

	'curved-velcro-fabric-pop-up-display-graphic-frame' => array(
		'title' => array('Curved Pop Up Display', 'Velcro Fabric'),
		'description' => 'Curved Velcro pop up displays: an accordion frame that opens in one motion with a wraparound fabric print, 8 ft or 10 ft wide. Ships nationwide. Order now.',
		'faq' => array($popup_setup, $case),
		'care' => $fabric_care,
	),
	'curved-velcro-fabric-pop-up-display-graphic-only' => array(
		'description' => 'Replacement Velcro fabric graphic for the curved pop up display, dye sublimated with printed end caps, 8 ft or 10 ft. Frame not included. Ships nationwide.',
	),

	'straight-tension-fabric-displays-graphic-frame' => array(
		'title' => array('Straight Tension Fabric Display', ''),
		'description' => 'Straight tension fabric displays: a flat zip-on fabric backwall on tool-free tubes with stabilizing feet, in 8, 10 or 20 ft sizes. Ships nationwide.',
		'faq' => array($tension_setup),
		'care' => $fabric_care,
	),
	'straight-tension-fabric-displays-graphic-only' => array(
		'description' => 'Replacement zip-on graphic for the straight tension fabric display in 8, 10 or 20 ft sizes, single or double sided. Frame not included. Ships nationwide.',
	),

	'straight-velcro-fabric-pop-up-display-graphic-frame' => array(
		'title' => array('Straight Pop Up Display', 'Velcro Fabric'),
		'description' => 'Straight Velcro pop up displays: an accordion frame that opens in minutes with a fabric print that wraps the ends, 8 ft or 10 ft. Ships nationwide.',
		'faq' => array($popup_setup, $case),
		'care' => $fabric_care,
	),
	'straight-velcro-fabric-pop-up-display-graphic-only' => array(
		'description' => 'Replacement Velcro fabric graphic for the straight pop up display, dye sublimated with printed end caps, 8 or 10 ft. Frame not included. Ships nationwide.',
	),
);
