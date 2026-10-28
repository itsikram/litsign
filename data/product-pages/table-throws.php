<?php
/**
 * Product page copy: table covers, throws and runners. See
 * inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

$template_size = 'Design on the template for your size and back style. The upload size is larger than the finished cover to allow for the sewn hems.';
$fold = array('q' => 'Will it wrinkle in storage?', 'a' => 'Fold it loosely in its bag between events. Creases from folding can be steamed out, or the cover can be machine washed and hung to dry.');

return array(
	'4ft-table-cover' => array(
		'title' => array('4ft Table Cover', 'Custom Printed'),
		'description' => 'Custom 4ft table covers dye sublimated on washable 8.8oz polyester for 48" x 30" tables, in open or closed back styles. Ships nationwide. Order online.',
		'file_notes' => array($template_size),
		'faq' => array($fold),
		'care' => 'fabric',
	),

	'6ft-table-cover' => array(
		'title' => array('6ft Table Cover', 'Custom Printed'),
		'description' => 'Custom 6ft table covers for 72" x 30" tables, dye sublimated on washable 8.8oz polyester, open or closed back. Ships nationwide. Order online.',
		'file_notes' => array($template_size),
		'faq' => array($fold),
		'care' => 'fabric',
	),

	'8ft-table-cover' => array(
		'title' => array('8ft Table Cover', 'Custom Printed'),
		'description' => 'Custom 8ft table covers for 96" x 30" tables, dye sublimated edge to edge on washable 8.8oz polyester, open or closed back. Ships nationwide. Order online.',
		'file_notes' => array($template_size),
		'faq' => array($fold),
		'care' => 'fabric',
	),

	'round-table-cover' => array(
		'title' => array('Round Table Covers', 'Custom Printed'),
		'description' => 'Custom round table covers for 31.5" to 60" tables, plus bar height, dye sublimated on washable polyester with a hemmed edge. Ships nationwide. Order now.',
		'printed' => true,
		'file_notes' => array('Use the template for your table diameter and height; the finished cover is a square, from 86" x 86" to 98" x 98".'),
		'care' => 'fabric',
	),

	'solid-color-table-throws' => array(
		'title' => array('Solid Color Table Throws', '6 ft & 8 ft'),
		'description' => 'Solid color table throws in washable 7 oz polyester for 6 ft or 8 ft tables, in black, blue, red and more. A base for runners. Ships nationwide. Order now.',
		'printed' => false,
		'care' => 'fabric',
	),

	'stretch-table-cover' => array(
		'title' => array('Stretch Table Cover', 'Fitted'),
		'description' => 'Stretch table covers: a fitted 8.8oz polyester cover printed all over for 6 ft or 8 ft folding tables, with leg loops. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'How do I put it on?', 'a' => 'Pull the cover down over the table top like a fitted sheet, then fasten the canvas loop on each leg so the fabric stays tight.'),
		),
		'care' => 'fabric',
	),

	'table-runner' => array(
		'title' => array('Custom Table Runners', 'Printed'),
		'description' => 'Custom table runners dye sublimated on 8.8oz polyester, 24" to 30" wide and 72" to 90" long, to brand a plain table throw. Ships nationwide. Order online.',
		'care' => 'fabric',
	),
);
