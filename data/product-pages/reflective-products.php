<?php
/**
 * Product page copy: reflective products. See inc/product-page-content.php.
 *
 * @package litsign
 */

$big = 'Panels larger than 46" x 38" (or 48" x 30") are too big to ship by parcel. Call <a href="tel:+18664362101">866-436-2101</a> before ordering one.';
$night = array('q' => 'How does reflective vinyl work?', 'a' => 'Engineer grade prismatic film bounces light from headlights or flashlights straight back toward its source, so the graphic looks lit to drivers and walkers without any power.');

return array(
	'reflective-adhesive-vinyl' => array(
		'title' => array('Reflective Adhesive Vinyl', ''),
		'description' => 'Reflective adhesive vinyl: engineer grade prismatic film printed in color that shines in headlights, for gates and trucks. Ships nationwide. Order now.',
		'faq' => array($night),
		'care' => 'adhesive',
	),

	'reflective-aluminum-sandwich-board' => array(
		'title' => array('Reflective Aluminum Board', 'Custom Size'),
		'description' => 'Reflective aluminum sandwich board: a 1/8" composite panel with reflective vinyl, cut to any size up to 48" x 96". Ships nationwide. Order now.',
		'notes' => array($big),
		'faq' => array($night),
		'care' => 'rigid',
	),

	'reflective-aluminum-sign' => array(
		'title' => array('Reflective Aluminum Signs', 'Night Visible'),
		'description' => 'Reflective aluminum signs: .040 aluminum faced with engineer grade reflective vinyl, in 3 sizes, readable at night. Ships nationwide. Order now.',
		'faq' => array($night),
		'care' => 'rigid',
	),

	'reflective-car-magnet' => array(
		'title' => array('Reflective Car Magnets', 'Night Visible'),
		'description' => 'Reflective car magnets: 30 mil magnets faced with printed reflective vinyl that lights up in headlights, up to 24" x 60". Ships nationwide. Order online.',
		'faq' => array(
			array('q' => 'Do rounded corners cost extra?', 'a' => 'No. Square and rounded corners are the same price.'),
			array('q' => 'What if I need one larger than 24" x 60"?', 'a' => 'That is the largest single magnet. For a bigger graphic, split the artwork across two magnets that line up side by side.'),
		),
		'care' => 'magnet',
	),

	'reflective-coroplast-sign-hstake' => array(
		'title' => array('Reflective Coroplast Signs', 'With H-Stake'),
		'description' => 'Reflective coroplast signs: corrugated plastic faced with reflective vinyl that catches headlights, with an optional H-stake. Ships nationwide. Order now.',
		'notes' => array($big),
		'faq' => array($night),
		'care' => 'rigid',
	),
	'reflective-coroplast-sign-only' => array(
		'description' => 'Reflective coroplast board without a stake, faced with printed reflective vinyl, for walls, fences and frames you have. Ships nationwide. Order online.',
	),

	'reflective-pvc-board' => array(
		'title' => array('Reflective PVC Board', 'Night Visible'),
		'description' => 'Reflective PVC board: 1/8" expanded PVC faced with printed reflective vinyl, visible at night. Single or double sided. Ships nationwide. Order now.',
		'notes' => array($big),
		'faq' => array($night),
		'care' => 'rigid',
	),

	'reflective-signicade-a-frame' => array(
		'title' => array('Reflective Signicade A-Frame', ''),
		'description' => 'Reflective Signicade A-frame: a Plasticade sidewalk frame with 24" x 36" reflective vinyl graphics that shine in headlights. Ships nationwide. Order now.',
		'faq' => array($night),
		'care' => 'a-frame',
	),
);
