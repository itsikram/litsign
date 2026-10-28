<?php
/**
 * Product page copy: the 7 channel letter styles, printed by
 * wholesale_render_channel_letter_details() in place of the supplier's
 * Description / Components / Warranty / FAQ tabs, which repeated the same
 * parts lists and warranty text on every style.
 *
 * Extra keys for channel letters:
 * - 'material' 'aluminum' (acrylic face, aluminum returns) or 'stainless'.
 * - 'parts'    Part => specification rows for this style only.
 * - 'included' What ships with each letter.
 * - 'images'   File names in uploads/storefront-files/ to show, in order;
 *              the tags (and their alt text) come from the stored tabs.
 *
 * Facts come from each style's own component and FAQ tabs. Turnaround
 * figures are the ones those tabs state (owner to confirm, see
 * seo-plan/seo-changes-2026-10-25.md).
 *
 * @package litsign
 */

$aluminum_faq = array(
	array('q' => 'How long does production take?', 'a' => 'Standard letters from 16" to 24" tall are usually finished in 2–4 business days. Adding vinyl to the faces or ordering oversize letters can take longer. Your estimated ship date shows at checkout.'),
	array('q' => 'What are the size limits?', 'a' => 'A shaped letter or logo can be up to 58.5" x 136". The thinnest stroke must be at least 2"; very thin parts of a logo may need to be adjusted before production, which can add cost or time.'),
	array('q' => 'What artwork should I send?', 'a' => 'A vector file at your final size, with shapes simplified and colors in CMYK. Exact color matching is not guaranteed. For a logo or complex graphic, <a href="/contact/">send it to a sign specialist</a> for a quote.'),
	array('q' => 'Is installation hardware included?', 'a' => 'The letters ship with a wiring diagram and a mounting pattern, and the installation guide lists the fasteners to use; the fasteners themselves are not included. An 8 ft, 3-outlet 120V extension cord can be added to your order.'),
);

$stainless_faq = array(
	array('q' => 'How long does production take?', 'a' => 'Stainless steel letters take about 3 weeks to make, plus shipping. A custom Pantone C color outside the standard chart adds 3 or more days.'),
	array('q' => 'What are the size limits?', 'a' => 'Strokes must be at least 1/2" thick with 2" returns, or 1" thick with 3" returns. The online price covers letters up to 36" x 36"; letters up to 48" x 48" can be custom ordered for an added fee, so <a href="/contact/">ask for a quote</a>.'),
	array('q' => 'What artwork should I send?', 'a' => 'A vector PDF, EPS or AI file at your final size, with every letter outlined and simplified. If a file won&rsquo;t upload, use the Help button and a sign specialist will contact you.'),
	array('q' => 'How long is the power cord?', 'a' => 'Each sign comes with a standard 3 ft cord. A longer cord, an 8 ft 3-outlet 120V extension cord, and mounting accessories (stand-off screws, mounting screws and wiring) can be added for an extra charge.'),
);

$aluminum_parts = array(
	'Returns (sides)' => '3" or 5" deep .040" high reflective aluminum (Alumet or Metals USA)',
	'Trimcap' => '3/4" or 1" Jewelite trimcap around the face',
	'Face' => '3/16" acrylic (Acrylite, Chemcast or Palsun Lexan)',
	'Face vinyl (optional)' => '3M 3630 translucent, 3635 day/night dual color or blockout vinyl, or 3M IJ8150 clear printed vinyl with 8520 matte laminate, for colors outside the acrylic range',
);
$aluminum_led = '0.7W and 1.2W high efficiency LED modules (Mynice), 160&deg; beam, IP67, 9,000–10,500K';
$stainless_led = '0.7W and 1.2W high efficiency LED modules (Mynice), 160&deg; beam, IP67, 10,000–12,000K';
$aluminum_images = array('channel-letter-trimcap-diagram.png', 'channel-letter-trimcap-color-chart.png', 'channel-letter-aluminum-return-diagram.png', 'channel-letter-return-color-chart.png', 'channel-letter-acrylic-face-diagram.png', 'channel-letter-vinyl-face-color-chart.png');
$aluminum_included = array('Trimcap, aluminum returns and the acrylic face', 'LED modules wired inside every letter', 'A 3 ft power cord for ground and power', 'Installation guide and a mounting pattern');

return array(
	'standard-channel-letter-front-lit' => array(
		'title' => array('Front Lit LED Channel Letters', ''),
		'description' => 'Front lit LED channel letters with colored acrylic faces, trimcaps and .040 aluminum returns. UL listed, built to order. Ships nationwide. Price online.',
		'material' => 'aluminum',
		'parts' => $aluminum_parts + array('Back' => 'Aluminum backing', 'LEDs' => $aluminum_led),
		'included' => $aluminum_included,
		'images' => array_merge(array('front-lit-led-channel-letter-on-brick-wall.png'), $aluminum_images),
		'faq' => $aluminum_faq,
	),

	'standard-channel-letter-back-lit' => array(
		'title' => array('Back Lit LED Channel Letters', ''),
		'description' => 'Back lit channel letters that cast a soft LED glow on the wall behind each letter, on aluminum returns. UL listed. Ships nationwide. Price yours online.',
		'material' => 'aluminum',
		'parts' => $aluminum_parts + array('Lighting' => 'LED modules aimed out the back of each letter to light the wall', 'LEDs' => $aluminum_led),
		'included' => $aluminum_included,
		'images' => $aluminum_images,
		'faq' => $aluminum_faq,
	),

	'standard-channel-letter-front-back-lit' => array(
		'title' => array('Front & Back Lit Channel Letters', ''),
		'description' => 'Front and back lit channel letters glow through the face and onto the wall behind, with a clear Lexan back. UL listed. Ships nationwide. Price online.',
		'material' => 'aluminum',
		'parts' => $aluminum_parts + array('Back' => '1/8" clear Palsun Lexan, so light also shines onto the wall', 'LEDs' => $aluminum_led),
		'included' => array('Trimcap, aluminum returns, the acrylic face and a clear Lexan back', 'LED modules wired inside every letter', 'A 3 ft power cord for ground and power', 'Installation guide and a mounting pattern'),
		'images' => $aluminum_images,
		'faq' => $aluminum_faq,
	),

	'hidden-back-halo-lit' => array(
		'title' => array('Halo Lit Channel Letters', 'Steel'),
		'description' => 'Halo lit channel letters with welded stainless steel faces and returns that throw a halo on the wall. UL listed, made in USA. Ships nationwide. Order now.',
		'material' => 'stainless',
		'parts' => array(
			'Face' => '0.8mm or 1mm solid stainless steel, in 35+ colors',
			'Returns (sides)' => '2" or 3" deep 0.8mm or 1mm stainless steel',
			'Back' => '1/8" clear Lexan, hidden from view, that lets the light out onto the wall',
			'LEDs' => $stainless_led,
		),
		'included' => array('Welded stainless steel face and returns, sanded and painted', 'Clear Lexan back and LED modules in every letter', 'A 3 ft power cord', 'Wiring diagram and a mounting pattern'),
		'images' => array('halo-lit-channel-letter-front-view.png', 'halo-lit-channel-letter-back-view.png', 'halo-lit-channel-letter-clear-lexan-back-diagram.png', 'halo-lit-channel-letter-stainless-steel-face-diagram.png', 'halo-lit-channel-letter-stainless-steel-return-diagram.png', 'stainless-steel-channel-letter-color-chart.png'),
		'faq' => $stainless_faq,
	),

	'halo-reverse-acrylic-lit-channel-letters' => array(
		'title' => array('Reverse Lit Channel Letters', ''),
		'description' => 'Reverse lit channel letters with stainless steel faces and a translucent acrylic back that casts a halo of light. UL listed. Ships nationwide. Order now.',
		'material' => 'stainless',
		'parts' => array(
			'Face' => '0.8mm or 1mm stainless steel',
			'Returns (sides)' => '2" or 3" deep 0.8mm or 1mm stainless steel',
			'Back' => '0.4" translucent polycarbonate acrylic, exposed, that glows and casts the halo',
			'LEDs' => $stainless_led,
		),
		'included' => array('Welded stainless steel face and returns, sanded and painted', 'Translucent acrylic back and LED modules in every letter', 'A 3 ft power cord', 'Wiring diagram and a mounting pattern'),
		'images' => array('reverse-lit-channel-letter-acrylic-back-diagram.png', 'reverse-lit-channel-letter-stainless-steel-face-diagram.png', 'halo-lit-channel-letter-stainless-steel-return-diagram.png', 'stainless-steel-channel-letter-color-chart.png'),
		'faq' => $stainless_faq,
	),

	'exposed-acrylic-face-lit-borderless-no-trimcap' => array(
		'title' => array('Borderless Channel Letters', ''),
		'description' => 'Borderless channel letters with a thick exposed acrylic face and no trimcap, on stainless steel returns, for a sleek lit look. Ships nationwide. Order now.',
		'material' => 'stainless',
		'parts' => array(
			'Face' => '15mm white polycarbonate acrylic, exposed, with no trimcap or border',
			'Returns (sides)' => '2" or 3" deep 0.8mm or 1mm stainless steel',
			'Back' => '0.8mm or 1mm stainless steel',
			'LEDs' => $stainless_led,
		),
		'included' => array('Stainless steel returns and back, sanded and painted', 'White acrylic face and LED modules in every letter', 'A 3 ft power cord', 'Wiring diagram and a mounting pattern'),
		'images' => array('borderless-face-lit-channel-letter-front-view.jpg', 'borderless-face-lit-channel-letter-back-view.jpg', 'borderless-channel-letter-acrylic-face-diagram.png', 'borderless-channel-letter-stainless-steel-return-diagram.png', 'borderless-channel-letter-stainless-steel-back-diagram.png', 'stainless-steel-channel-letter-color-chart.png'),
		'faq' => $stainless_faq,
	),

	'inset-acrylic-face-lit-with-border-no-trimcap' => array(
		'title' => array('Trimless Channel Letters', 'Border'),
		'description' => 'Trimless channel letters with an inset acrylic face inside a thin stainless steel border instead of a trimcap. UL listed. Ships nationwide. Order now.',
		'material' => 'stainless',
		'parts' => array(
			'Face' => '9mm white polycarbonate acrylic, inset in a stainless steel border',
			'Returns (sides)' => '2" or 3" deep 0.8mm or 1mm stainless steel',
			'Back' => '0.8mm or 1mm stainless steel',
			'LEDs' => $stainless_led,
		),
		'included' => array('Stainless steel border, returns and back, sanded and painted', 'White acrylic face and LED modules in every letter', 'A 3 ft power cord', 'Wiring diagram and a mounting pattern'),
		'images' => array('inset-acrylic-face-lit-channel-letter-front-view.jpg', 'inset-acrylic-face-lit-channel-letter-back-view.jpg', 'inset-acrylic-channel-letter-face-diagram.png', 'inset-acrylic-channel-letter-stainless-steel-return-diagram.png', 'inset-acrylic-channel-letter-stainless-steel-back-diagram.png', 'stainless-steel-channel-letter-color-chart.png'),
		'faq' => $stainless_faq,
	),
);
