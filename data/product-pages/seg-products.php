<?php
/**
 * Product page copy: SEG (silicone edge graphic) displays. See
 * inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

$setup = array('q' => 'Do you set the display up for me?', 'a' => 'No, we ship it ready for you to assemble. The aluminum frame goes together by hand, and the download link on this page has the step-by-step setup guide.');
$no_bleed = 'Use the template for your display size; the silicone edge is sewn around the visible graphic, so don&rsquo;t add extra bleed for it.';

return array(
	'10ft-seg-backlit-fabric-display-graphic-frame' => array(
		'title' => array('10ft SEG Backlit Display', 'LED Lit Fabric'),
		'description' => '10ft SEG backlit display: a 118" x 88" LED lit fabric wall with a seamless silicone edge print that glows across the show floor. Ships nationwide.',
		'file_notes' => array($no_bleed),
		'faq' => array($setup),
		'care' => 'display',
	),
	'10ft-seg-backlit-fabric-display-graphic-only' => array(
		'description' => 'Replacement backlit SEG graphic for the 10ft SEG backlit fabric display, UV printed on 6oz fabric with a silicone edge. Ships nationwide. Order now.',
	),

	'10ft-seg-backlit-popup-display-graphic-frame' => array(
		'title' => array('10ft SEG Backlit Popup', 'LED Lit'),
		'description' => '10ft SEG backlit popup display: an accordion frame that expands in moments with LED lit seamless fabric, 117" x 88". Ships nationwide. Order online.',
		'file_notes' => array($no_bleed),
		'faq' => array($setup),
		'care' => 'display',
	),
	'10ft-seg-backlit-popup-display-graphic-only' => array(
		'description' => 'Replacement backlit SEG graphic for the 10ft backlit popup display, UV printed on 6oz fabric with a silicone edge. Frame not included. Ships nationwide.',
	),

	'10ft-seg-fabric-display-graphic-frame' => array(
		'title' => array('10ft SEG Fabric Display', 'Booth Backwall'),
		'description' => '10ft SEG fabric display: a 118" x 89" trade show backwall with a seamless silicone edge print on a quick assembly aluminum frame. Ships nationwide.',
		'file_notes' => array($no_bleed),
		'faq' => array($setup),
		'care' => 'display',
	),
	'10ft-seg-fabric-display-graphic-only' => array(
		'description' => 'Replacement SEG graphic for the 10ft SEG fabric display, UV printed on 6oz fabric with a silicone edge. Reuse your frame. Ships nationwide. Order online.',
	),

	'20ft-seg-fabric-display-graphic-frame' => array(
		'title' => array('20ft SEG Fabric Display', 'Island Booth'),
		'description' => '20ft SEG fabric display: our largest seamless fabric wall, 236" x 89", for island booths and big venues. Single or double sided. Ships nationwide.',
		'file_notes' => array($no_bleed),
		'faq' => array($setup),
		'care' => 'display',
	),
	'20ft-seg-fabric-display-graphic-only' => array(
		'description' => 'Replacement SEG graphic for the 20ft SEG fabric display, printed edge to edge on 6oz fabric with a silicone edge. Reuse your frame. Ships nationwide.',
	),

	'3ft-seg-fabric-stand-graphic-frame' => array(
		'title' => array('3ft SEG Fabric Stand', 'Seamless Display'),
		'description' => '3ft SEG fabric stand: a 33" x 79" seamless fabric display at 13 lb with a travel bag, a premium step up from banner stands. Ships nationwide. Order online.',
		'file_notes' => array($no_bleed),
		'care' => 'display',
	),
	'3ft-seg-fabric-stand-graphic-only' => array(
		'description' => 'Replacement SEG graphic for the 3ft SEG fabric stand, UV printed on 6oz fabric with a silicone edge, single or double sided. Ships nationwide. Order now.',
	),

	'8ft-seg-backlit-popup-display-graphic-frame' => array(
		'title' => array('8ft SEG Backlit Popup', 'LED Lit'),
		'description' => '8ft SEG backlit popup display: an 88" x 88" LED lit seamless fabric wall on accordion hardware for 8 ft booths. Ships nationwide. Order online.',
		'file_notes' => array($no_bleed),
		'faq' => array($setup),
		'care' => 'display',
	),
	'8ft-seg-backlit-popup-display-graphic-only' => array(
		'description' => 'Replacement backlit SEG graphic for the 8ft backlit popup display, UV printed on 6oz fabric with a silicone edge. Frame not included. Ships nationwide.',
	),

	'custom-seg-fabric' => array(
		'title' => array('Custom SEG Fabric', 'Fits Your Frame'),
		'description' => 'Custom SEG fabric printed to fit your own frame: 6oz polyester, edge to edge print, sewn 3mm x 11mm silicone edge, up to 40\' x 10\'. Ships nationwide.',
		'file_notes' => array('Order the size of the visible graphic you need. Don&rsquo;t add extra bleed for the silicone edge.'),
		'faq' => array(
			array('q' => 'How do I check that it fits my frame?', 'a' => 'With the fabric, the silicone edge measures 0.44" x 0.135". Compare that with the channel in your extrusion before you order.'),
		),
		'care' => 'display',
	),
);
