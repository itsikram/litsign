<?php
/**
 * Product page copy: dry erase products. See inc/product-page-content.php.
 *
 * @package litsign
 */

$big = 'Panels larger than 46" x 38" (or 48" x 30") are too big to ship by parcel. Call <a href="tel:+18664362101">866-436-2101</a> before ordering one.';
$layout = array('q' => 'What should I print and what should I write?', 'a' => 'Print the parts that never change, such as your logo, headings, lines or a grid, and leave open space for what you will write in and wipe off.');

return array(
	'dry-erase-adhesive-vinyl' => array(
		'title' => array('Dry Erase Adhesive Vinyl', 'Printed'),
		'description' => 'Dry erase adhesive vinyl: printed film with a gloss dry erase coat for whiteboards, job boards and labels on flat surfaces. Ships nationwide. Order online.',
		'faq' => array($layout),
		'care' => 'dry-erase',
	),

	'dry-erase-aluminum-sandwich-board' => array(
		'title' => array('Dry Erase Aluminum Board', 'Writable'),
		'description' => 'Dry erase aluminum board: a printed 1/8" composite panel with a gloss writable coat for specials and schedules, in or out. Ships nationwide. Order now.',
		'notes' => array($big),
		'care' => 'dry-erase',
	),

	'dry-erase-coroplast-sign-hstake' => array(
		'title' => array('Dry Erase Coroplast Signs', 'With H-Stake'),
		'description' => 'Dry erase coroplast signs: printed 4mm corrugated plastic with a writable gloss coat for open house times and prices. Ships nationwide. Order online.',
		'notes' => array($big),
		'faq' => array($layout),
		'care' => 'dry-erase',
	),
	'dry-erase-coroplast-sign-only' => array(
		'description' => 'Dry erase coroplast board without a stake: printed corrugated plastic with a writable gloss coat for walls, fences and frames. Ships nationwide. Order now.',
	),

	'dry-erase-foamcore' => array(
		'title' => array('Dry Erase Foam Board', 'Printed'),
		'description' => 'Dry erase foamcore: a light 3/16" foam board printed in full color with a writable gloss coat for planning and presentations. Ships nationwide. Order now.',
		'notes' => array($big),
		'faq' => array($layout),
		'care' => 'Foamcore is for indoor use only; keep it dry. Wipe marker off with a dry eraser or soft cloth, and don&rsquo;t use permanent markers or solvents on the coating.',
	),

	'dry-erase-magnet' => array(
		'title' => array('Dry Erase Magnets', 'Printed'),
		'description' => 'Dry erase magnets: 30 mil printed magnets with a writable gloss coat for fridges, cabinets, vehicles and job boards. Ships nationwide. Order online.',
		'faq' => array(
			array('q' => 'Do rounded corners cost extra?', 'a' => 'No. Straight and rounded corners are the same price.'),
		),
		'care' => 'dry-erase',
	),

	'dry-erase-pvc-board' => array(
		'title' => array('Dry Erase PVC Board', 'Frame Ready'),
		'description' => 'Dry erase PVC board: 1/8" expanded PVC printed in full color with a gloss writable coat that fits most frames and walls. Ships nationwide. Order online.',
		'notes' => array($big),
		'faq' => array($layout),
		'care' => 'dry-erase',
	),

	'dry-erase-signicade-a-frame' => array(
		'title' => array('Dry Erase Signicade A-Frame', ''),
		'description' => 'Dry erase Signicade A-frame: a Plasticade sidewalk frame with 24" x 36" printed faces you can write on and wipe clean daily. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'Do the printed faces come on the frame?', 'a' => 'Yes. The graphics that come with the frame are installed and ready to use when it arrives.'),
		),
		'care' => 'dry-erase',
	),
);
