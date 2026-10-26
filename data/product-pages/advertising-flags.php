<?php
/**
 * Product page copy: advertising flags. See inc/product-page-content.php
 * for the keys. "Flag Only" variants inherit their kit's entry.
 *
 * @package litsign
 */

$setup = array('q' => 'How do I put the flag up?', 'a' => 'Position the base or ground stake, connect the pole sections (they interlock with no tools), slide the flag sleeve onto the pole, then secure the flag down the pole so it stays tight.');
$bag = array('q' => 'Is there a carry bag?', 'a' => 'Yes, an optional zippered nylon carry bag holds the flag and poles: about 1.41 lbs for Small and Medium, 1.63 lbs for Large and X-Large.');

return array(
	'custom-pole-flag-pole' => array(
		// A house-style flag made to size, not a feather/rectangle pole set (see the guide).
		'title' => array('Custom Pole Flags', 'House & Wall Flags'),
		// The stored spec repeats the rectangle flag's pole set; these are this product's options.
		'spec' => array(
			'Size' => 'Made to the width and height you enter, priced per square foot',
			'Print' => 'Dye sublimated: single sided print-through, or double sided (two prints sewn back to back)',
			'Finishing' => 'Hem with a grommet strip on the left side, a grommet strip along the top, or a hem with no grommets',
			'Pole (optional)' => '74.5" two-piece aluminum pole with a chrome ball and a tangle-free spinning top, recommended for 2\' x 3\' and 3\' x 5\' flags',
			'Bracket (optional)' => 'White powder coated wall bracket with two pole angles',
		),
		'description' => 'Custom pole flags made to your size for porch, wall and storefront poles, with grommet strip or hem finishing and an optional pole. Ships nationwide.',
		'faq' => array(
			array('q' => 'How do I hang it?', 'a' => 'With the optional pole and bracket, screw the bracket to the wall or post, set the pole in it at either of its two angles, and clip the flag&rsquo;s grommets to the pole. A plastic ring is included for flags with grommets.'),
		),
		'care' => 'Take the flag in during storms and very high wind. Wash it by hand or on a gentle cold cycle and hang it to dry; do not bleach, tumble dry or iron it.',
	),
	'custom-pole-flag-only' => array(
		'description' => 'Custom pole flag only, made to size with a grommet strip or hem to fly on a pole you already own. Single or double sided. Ships nationwide. Order online.',
	),

	'econo-feather-flag-pole' => array(
		'title' => array('Econo Feather Flag', '16 ft Outdoor'),
		'description' => 'Econo feather flags: a 16 ft dye sublimated outdoor flag with a 4 piece aluminum pole set and ground stake at a low price. Ships nationwide. Order online.',
		'faq' => array($setup),
		'care' => 'flag',
	),
	'econo-feather-flag-only' => array(
		'description' => 'Replacement 16 ft Econo feather flag, dye sublimated on 4 oz polyester to fit your Econo pole set. Flag only, no pole. Ships nationwide. Order online.',
	),

	'econo-stock-flag-pole' => array(
		'title' => array('Stock Feather Flags', 'No Artwork'),
		'description' => 'Ready-made 16 ft stock feather flags: pick a printed design, no artwork needed. Pole set and ground stake included. Ships nationwide. Order online.',
		'faq' => array($setup),
		'care' => 'flag',
	),
	'econo-stock-flag-only' => array(
		'description' => 'Replacement 16 ft stock design feather flag for your Econo pole set: choose a ready-made design, no artwork needed. Flag only. Ships nationwide.',
	),

	'feather-angled-flag-pole' => array(
		'title' => array('Angled Feather Flags', '9–18 ft Tall'),
		'description' => 'Custom angled feather flags in 4 sizes from 9 to 18 ft, dye sublimated on polyester mesh, with tool-free poles and bases. Ships nationwide. Order online.',
		'faq' => array($setup),
		'care' => 'flag',
	),
	'feather-angled-flag-only' => array(
		'description' => 'Replacement angled feather flag, dye sublimated on 4 oz polyester mesh in sizes from 9 to 18 ft. Flag only, pole not included. Ships nationwide.',
	),

	'feather-convex-flag-pole' => array(
		'title' => array('Convex Feather Flags', '9–18 ft Tall'),
		'description' => 'Custom convex feather flags with a curved edge, 9 to 18 ft tall, printed single or double sided, with indoor or outdoor bases. Ships nationwide.',
		'faq' => array($setup, $bag),
		'care' => 'flag',
	),
	'feather-convex-flag-only' => array(
		'description' => 'Replacement convex feather flag, dye sublimated on 4 oz polyester mesh in sizes from 9 to 18 ft. Flag only, pole not included. Ships nationwide.',
	),

	'rectangle-flag-pole' => array(
		'title' => array('Rectangle Flags', 'Custom Printed'),
		'description' => 'Custom rectangle flags with a 31" wide graphic that\'s easy to design for, 8.5 to 15 ft tall, with stake or base options. Ships nationwide. Order online.',
		'faq' => array($setup),
		'care' => 'flag',
	),
	'rectangle-flag-only' => array(
		'description' => 'Replacement rectangle flag with a 31" wide dye sublimated graphic, 8.5 to 15 ft sizes. Flag only, pole not included. Ships nationwide. Order online.',
	),

	'teardrop-flag-pole' => array(
		'title' => array('Teardrop Flags', 'Custom Printed'),
		'description' => 'Custom teardrop flags in 4 sizes from 7 to 13.5 ft, dye sublimated on light polyester mesh with tool-free poles and bases. Ships nationwide. Order online.',
		'faq' => array($setup, $bag),
		'care' => 'flag',
	),
	'teardrop-flag-only' => array(
		'description' => 'Replacement teardrop flag, dye sublimated on 4 oz polyester mesh in sizes from 7 to 13.5 ft. Flag only, pole not included. Ships nationwide. Order online.',
	),
);
