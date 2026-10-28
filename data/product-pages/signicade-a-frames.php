<?php
/**
 * Product page copy: Signicade sidewalk A-frames. See
 * inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

$fill = array('q' => 'How do I keep it from blowing over?', 'a' => 'Fill the hollow body with sand or water before you set it out, and bring it inside in very high wind.');

return array(
	'deluxe-signicade-graphic-frame' => array(
		'title' => array('Deluxe Signicade A-Frame', 'Slide-In Inserts'),
		'description' => 'Deluxe Signicade A-frame: a Plasticade sidewalk board with slide-in 24" x 36" coroplast inserts you can swap in seconds. Ships nationwide. Order online.',
		'faq' => array(
			array('q' => 'Do I need one insert or two?', 'a' => 'Order one insert to show your message on the front only, or two so both sides of the A-frame carry a graphic.'),
		),
		'care' => 'a-frame',
	),
	'deluxe-signicade-graphic-only' => array(
		'description' => 'Replacement 24" x 36" coroplast inserts for the Deluxe Signicade A-frame, UV printed to slide into its recesses. Ships nationwide. Order now.',
	),

	'simposign-ii-white-graphic-frame' => array(
		'title' => array('SimpoSign II A-Frame', 'Lightweight'),
		'description' => 'SimpoSign II A-frame: the 10 lb lightweight Signicade with 22" x 28" printed coroplast inserts and a fillable base for weight. Ships nationwide. Order now.',
		'faq' => array($fill),
		'care' => 'a-frame',
	),
	'simposign-ii-white-graphic-only' => array(
		'description' => 'Replacement 22" x 28" coroplast inserts for the SimpoSign II A-frame, UV printed in full color for quick promo changes. Ships nationwide. Order online.',
	),

	'standard-signicade-white-frame-graphic' => array(
		'title' => array('Signicade A-Frame', 'Standard White'),
		'description' => 'Standard Signicade A-frame: the classic white Plasticade sidewalk board with 24" x 36" printed faces mounted before it ships. Ships nationwide. Order now.',
		'faq' => array($fill),
		'care' => 'a-frame',
	),
	// Listed as "Frame Only" but sells the 24" x 36" replacement graphic.
	'standard-signicade-white-frame-only' => array(
		'title' => array('Signicade Replacement Graphic', '24" x 36"'),
		'description' => 'Replacement 24" x 36" printed vinyl graphic for the Standard Signicade A-frame, to refresh a frame you already own. Ships nationwide. Order online.',
	),
);
