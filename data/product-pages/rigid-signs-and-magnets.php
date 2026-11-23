<?php
/**
 * Product page copy: rigid boards and magnets. Product names keep "sign"
 * where it is the product's name (owner decision pending); other copy uses
 * board, panel or print. See inc/product-page-content.php for the keys.
 *
 * @package litsign
 */

$big = 'Panels larger than 46" x 38" (or 48" x 30") are too big to ship by parcel. Call <a href="tel:+18664362101">866-436-2101</a> before ordering one.';

return array(
	'aluminum-sandwich-board' => array(
		'title' => array('Aluminum Sandwich Board', 'ACM Panels'),
		'description' => 'Aluminum sandwich board: a 1/8" composite panel with a UV printed face, cut to any size up to 48" x 96" for outdoor use. Ships nationwide. Order online.',
		'notes' => array($big),
		'faq' => array(
			array('q' => 'Will it fit my sign frame?', 'a' => 'At 1/8" (3mm) thick, it fits most display stands and frames made for standard sign panels.'),
		),
		'care' => 'rigid',
	),

	'aluminum-sign' => array(
		'title' => array('Aluminum Signs', 'Rust Proof .040'),
		'description' => 'Custom aluminum signs on .040 rust-proof aluminum with a UV printed face, in 3 sizes. Single or double sided. Ships nationwide. Order online.',
		'faq' => array(
			array('q' => 'Can both sides be printed?', 'a' => 'Yes. Choose single sided (4/0) or double sided (4/4) printing.'),
		),
		'care' => 'rigid',
	),

	'coroplast' => array(
		'title' => array('Coroplast Signs', 'Corrugated Plastic'),
		'description' => 'Coroplast signs printed on 4mm corrugated plastic for yards, events and job sites, with free grommets and optional H-stakes. Ships nationwide. Order now.',
		'notes' => array($big),
		'faq' => array(
			array('q' => 'Do coroplast prints come with stakes?', 'a' => 'H-stakes are optional. Choose the H-stake when you order to push the board straight into a lawn.'),
		),
		'care' => 'rigid',
	),

	'foam-board' => array(
		'title' => array('Foam Board Printing', 'Presentation Boards'),
		'description' => 'Foam board prints UV printed on 3/16" white foamcore, the lightest rigid board for presentations, easels and indoor displays. Ships nationwide. Order now.',
		'notes' => array($big),
		'care' => 'Foam board is for indoor use only. Keep it dry, dust it with a dry cloth, and carry it by the edges so the faces don&rsquo;t dent.',
	),

	'gatorfoam' => array(
		'title' => array('GatorFoam Boards', 'Rigid Display Panels'),
		'description' => 'GatorFoam with wood fiber faces that resist dents and warping, 3/16" or 1/2" thick in white or black, for indoor displays. Ships nationwide. Order now.',
		'notes' => array($big),
		'care' => 'GatorFoam is for indoor use only. Keep it dry and dust it with a soft, dry cloth.',
	),

	'magnets' => array(
		'title' => array('Custom Car Magnets', 'Printed Magnetic'),
		'description' => 'Custom car magnets printed on 30 mil magnet sheet, waterproof and UV safe, up to 24" x 60" with square or round corners. Ships nationwide. Order now.',
		'faq' => array(
			array('q' => 'Do rounded corners cost extra?', 'a' => 'No. Square and rounded corners are the same price.'),
			array('q' => 'What if my vehicle door isn&rsquo;t magnetic?', 'a' => 'Some newer doors are plastic or aluminum, so magnets won&rsquo;t hold. For those, a printed <a href="/product/adhesive-vinyl-high-performance/">removable adhesive vinyl</a> decal is a good alternative.'),
		),
		'care' => 'magnet',
	),

	'pvc-board' => array(
		'title' => array('PVC Board Printing', 'Sintra Style'),
		'description' => 'PVC board prints UV printed on 1/8" expanded PVC like Sintra: light, waterproof and frame-ready for walls and holders. Ships nationwide. Order online.',
		'notes' => array($big),
		'care' => 'rigid',
	),

	'styrene' => array(
		'title' => array('Styrene Printing', 'Thin Flexible Inserts'),
		'description' => 'Styrene prints on .020" high impact polystyrene with a blockout core that lays flat in frames and holders. Indoor or outdoor. Ships nationwide. Order now.',
		'care' => 'rigid',
	),
);
