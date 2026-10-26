<?php
/**
 * Product page copy: banner stand and backdrop hardware without graphics.
 * Stock hardware: no artwork section. See inc/product-page-content.php.
 *
 * @package litsign
 */

$included = 'Comes with the base, the support pole and the top banner rail, plus a carry bag. The printed banner is sold separately.';

return array(
	'deluxe-retractable-hardware-only' => array(
		'title' => array('Deluxe Retractable Hardware', 'Stand Only'),
		'description' => 'Deluxe retractable banner stand hardware: wide chrome-capped base, adjustable pole, top rail and carry bag for 33" x 81" banners. Ships nationwide.',
		'notes' => array($included),
		'care' => 'stand',
	),

	'led-light-for-banner-stand' => array(
		'title' => array('Banner Stand LED Light', '450 Lumens'),
		'description' => '450 lumen, 3 watt LED light that clips to the top of Deluxe, Standard and SD retractable banner stands to light your graphic. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'How is it powered?', 'a' => 'It plugs into a standard wall outlet or a power strip.'),
		),
	),

	'sd-retractable-hardware-only' => array(
		'title' => array('SD Retractable Stand Hardware', ''),
		'description' => 'SD retractable banner stand hardware in 36", 48" and 60" widths: low-profile base, adjustable pole, top rail and case. No graphic. Ships nationwide.',
		'notes' => array('Comes with the base, the support pole and the top banner rail, plus a carry case. The printed banner is sold separately.'),
		'care' => 'stand',
	),

	'step-and-repeat-backdrop-hardware-only' => array(
		'title' => array('Step and Repeat Frame', 'Hardware Only'),
		'description' => 'Step and repeat backdrop frame with telescoping tubes and heavy feet for graphics from 6\' x 4\' to 10\' x 8\'. Carry bag included. Ships nationwide.',
		'faq' => array(
			array('q' => 'How does the graphic attach?', 'a' => 'The top and bottom poles slide through pole pockets sewn into the graphic, then thumb screws lock them to the uprights.'),
		),
		'care' => 'display',
	),

	'x-stand-hardware-only' => array(
		'title' => array('X-Stand Frame', 'Hardware Only'),
		'description' => 'X-stand frame only: light aluminum and graphite hardware that hooks into a banner\'s corner grommets, in 2 sizes. Carry bag included. Ships nationwide.',
		'care' => 'stand',
	),

	'standard-retractable-hardware-only' => array(
		'title' => array('Standard Retractable Hardware', ''),
		'description' => 'Standard retractable banner stand hardware: aluminum base with fold-out feet, support pole, top rail and carry bag. Graphic not included. Ships nationwide.',
		'notes' => array($included),
		'care' => 'stand',
	),
);
