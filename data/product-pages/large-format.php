<?php
/**
 * Product page copy: large format prints (backlit film, popup, posters, DTF).
 * See inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

return array(
	'backlit-film' => array(
		'title' => array('Backlit Film Prints', 'For Light Boxes'),
		'description' => 'Backlit film prints on 10 mil translucent film with a bright white point and matte finish, for menu boards and light boxes. Ships nationwide. Order online.',
		'faq' => array(
			array('q' => 'Is it printed in one layer?', 'a' => 'No. It is double layer UV printed, which keeps colors rich and even when the light box is on.'),
		),
		'care' => 'Handle the film by its edges and keep it rolled with the printed side out until it goes in the light box. Wipe dust off with a soft, dry cloth.',
	),

	'popup' => array(
		'title' => array('PVC Popup Prints', 'Double White'),
		'description' => 'PVC popup prints on 14 mil double white material with a blockout core: smooth, scratch resistant inserts that lay flat. Ships nationwide. Order online.',
		'care' => 'Wipe the print with a soft, damp cloth. Store it flat or loosely rolled with the printed side out.',
	),

	'posters' => array(
		'title' => array('Custom Poster Printing', 'Tear Resistant'),
		'description' => 'Custom poster printing on 14 mil double white PVC that won\'t tear like paper and lays flat after shipping, up to 52" high. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'How wide can a poster be?', 'a' => 'Up to 52" on the short side, and up to 159\' long.'),
		),
		'care' => 'Wipe the poster with a soft, damp cloth. Store it flat or loosely rolled with the printed side out.',
	),

	'dtf' => array(
		'title' => array('DTF Transfers', 'Direct to Film'),
		'description' => 'Custom DTF transfers printed in CMYK plus white on cold peel film, ready to heat press on cotton, poly, hats and bags. Ships nationwide. Order online.',
		'files' => array(
			'Send a PNG file only, with a transparent background; 300 dpi is recommended.',
			'Leave a 1/8" safety margin on every edge so nothing is cut off when the transfer is trimmed.',
			'Gang several designs, or repeats of one design, on the sheet to make the most of the price per square foot. Sheets go up to 22" x 60" and are trimmed to the size you order.',
		),
		'faq' => array(
			array('q' => 'Can I press the transfers later?', 'a' => 'Yes. DTF transfers are stable in storage, so you can order now and press them on demand.'),
		),
		'care' => 'Store unused transfers flat and dry, away from heat. After pressing, wash garments inside out in cold water.',
	),
);
