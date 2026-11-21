<?php
/**
 * Header logo: the owner's flat brand artwork (2026-11-20: orange letters with
 * a dark orange underside, navy tagline, red tile with the silver S, blue
 * "online .com" sign), animated by js/logo-motion.js in the style of the
 * page loader's logo: the mark swings on its axis, the letters pop forward one
 * after another like channel letters lighting up, the sign turns on its pole
 * and one light sweep crosses the word.
 *
 * Files: img/brand/sfs-logo-<width>.webp/.avif (320-1910px wide) and the
 * master sfs-logo-1910.png, made from the owner's picture by
 * utils/logo-motion/slice.py (trims the empty border, makes the nearly solid
 * pixels solid, measures the animation's cut lines) and avif.php. To change
 * the logo: run both on the new picture and paste slice.py's GEO into
 * js/logo-motion.js.
 *
 * Where: the two header logos (computer header .ssx-logo, phone/tablet header
 * .sh-logo) and the phone menu. The Customizer's site logo (whichever is set)
 * is untouched and still used by the footer (dark background, where the navy
 * tagline would not show), the SEO markup and feeds. Without the files every
 * place falls back to the site logo as before.
 *
 * How the animation works
 * - The logo stays the normal <img> at all times. The script only adds a layer
 *   of copies on top of it for the 1.9 s a run lasts, then removes the class
 *   again, so the logo at rest is exactly the picture, with JS off, on old
 *   browsers and under "reduce motion" too.
 * - Every copy is the same file the <img> already downloaded (its currentSrc
 *   as a CSS background, so no new request), drawn at the same size and cut
 *   to one piece with clip-path along the measured cut lines.
 * - While a run plays, the mark and the sign are cut out of the <img>
 *   (clip-path) so their turning copies do not show double. Letter copies are
 *   hidden except during their own pop.
 * - Runs: once after the page has loaded, at the first idle moment (after the
 *   page loader has faded, on a link click), two softer runs about 10 s apart
 *   while the visitor is still at the top of the page, then only on hover.
 *   Motion that starts by itself and never stops needs a pause control
 *   (WCAG 2.2.2), and it would pull the eye while someone fills in an order.
 * - Nothing here touches the first paint: the script is deferred, waits for
 *   the load event and adds its own <style>. Only transform, opacity and
 *   filter are animated.
 * - Only on 'full' motion pages (see retails_motion_level()), so the cart,
 *   checkout and account pages keep a still logo.
 *
 * @package retails
 */

defined('ABSPATH') || exit;

/**
 * Widths of the made files, and the master's size.
 */
function retails_brand_logo_files()
{
	return array(
		'widths' => array(320, 480, 640, 960, 1280, 1910),
		'width' => 1910,
		'height' => 323,
	);
}

/**
 * True when the brand logo files are in the theme.
 */
function retails_brand_logo_ready()
{
	static $ready = null;
	if (null === $ready) {
		$dir = get_template_directory() . '/img/brand/';
		$ready = is_readable($dir . 'sfs-logo-1910.webp') && is_readable($dir . 'sfs-logo-480.avif');
	}
	return (bool) apply_filters('retails_brand_logo_ready', $ready);
}

/**
 * True when the dark-background copy (img/brand/sfs-logo-dark-*) is there too.
 */
function retails_brand_logo_dark_ready()
{
	$dir = get_template_directory() . '/img/brand/';
	return retails_brand_logo_ready() && is_readable($dir . 'sfs-logo-dark-960.webp') && is_readable($dir . 'sfs-logo-dark-480.avif');
}

/**
 * <picture> for the brand logo: AVIF first, WebP for the rest. The version
 * query changes when the files do (the theme folder is cached for a year).
 */
function retails_brand_logo_picture($attr, $sizes, $variant = '')
{
	$files = retails_brand_logo_files();
	$base = get_template_directory_uri() . '/img/brand/sfs-logo-';
	$version = (string) filemtime(get_template_directory() . '/img/brand/sfs-logo-1910.webp');
	if ('dark' === $variant) {
		// Same artwork with a light tagline, for dark backgrounds (footer, page loader).
		$files['widths'] = array(320, 480, 640, 960);
		$base = get_template_directory_uri() . '/img/brand/sfs-logo-dark-';
		$version = (string) filemtime(get_template_directory() . '/img/brand/sfs-logo-dark-960.webp');
	}
	$set = static function ($type) use ($files, $base, $version) {
		$out = array();
		foreach ($files['widths'] as $width) {
			$out[] = esc_url($base . $width . '.' . $type . '?v=' . $version) . ' ' . $width . 'w';
		}
		return implode(', ', $out);
	};

	$img = array(
		'src' => $base . '480.webp?v=' . $version,
		'srcset' => null,
		'sizes' => $sizes,
		'width' => $files['width'],
		'height' => $files['height'],
		'alt' => isset($attr['alt']) ? $attr['alt'] : get_bloginfo('name'),
		'class' => isset($attr['class']) ? $attr['class'] : '',
		'loading' => isset($attr['loading']) ? $attr['loading'] : 'eager',
		'decoding' => isset($attr['decoding']) ? $attr['decoding'] : 'async',
		'fetchpriority' => isset($attr['fetchpriority']) ? $attr['fetchpriority'] : '',
	);
	$html = '<img';
	foreach ($img as $name => $value) {
		if ('srcset' === $name) {
			$html .= ' srcset="' . $set('webp') . '"'; // Escaped per URL above.
			continue;
		}
		if ('' === $value || null === $value) {
			continue;
		}
		$html .= ' ' . $name . '="' . ('src' === $name ? esc_url($value) : esc_attr($value)) . '"';
	}
	$html .= '>';

	return '<picture style="display:contents"><source type="image/avif" srcset="' . $set('avif') . '" sizes="' . esc_attr($sizes) . '">' . $html . '</picture>';
}

/**
 * Swap the site logo for the brand logo in the headers and the phone menu.
 * Runs after the theme's AVIF <picture> filter (10) and replaces its output.
 */
function retails_brand_logo_html($html, $attachment_id, $size = '', $icon = false, $attr = array())
{
	if (is_admin() || (int) $attachment_id !== (int) get_theme_mod('custom_logo') || !retails_brand_logo_ready() || !is_array($attr)) {
		return $html;
	}
	$class = isset($attr['class']) ? (string) $attr['class'] : '';
	if (false !== strpos(' ' . $class . ' ', ' header-logo ') && isset($attr['sizes']) && false !== strpos($attr['sizes'], '(max-width: 767px)')) {
		// Widths set in retails_logo_size_style().
		return retails_brand_logo_picture($attr, '(max-width: 572px) calc(100vw - 185px), (max-width: 767px) 376px, (max-width: 991px) 378px, (max-width: 1199px) calc(26.1vw - 7.2px), 396px');
	}
	// Phone menu (header.php .ssxm__logo): WordPress adds its own size class
	// and "auto, " to the sizes of lazy images.
	if (isset($attr['sizes']) && preg_match('/^(auto,\s*)?170px$/', (string) $attr['sizes'])) {
		$attr['class'] = '';
		return retails_brand_logo_picture($attr, '170px');
	}
	// Footer (footer.php .sf-brand__logo, dark background): the light-tagline copy.
	if (isset($attr['sizes']) && preg_match('/^(auto,\s*)?260px$/', (string) $attr['sizes']) && retails_brand_logo_dark_ready()) {
		$attr['class'] = '';
		return retails_brand_logo_picture($attr, '(max-width: 575px) 300px, (max-width: 991px) 290px, 340px', 'dark');
	}
	return $html;
}
add_filter('wp_get_attachment_image', 'retails_brand_logo_html', 20, 5);

/**
 * Header logo size (2026-11-20; the owner asked for these widths, then ~22:00
 * "make the header logo size 10% decreased": every width below is now x0.9,
 * i.e. 396px on computers, 378px on tablets (288px scrolled), at most 342px
 * below 768px, and the original header's link is 90% of its slot). The
 * pre-10% widths, with the older ones in brackets:
 * - 1200px and wider: 24vw + 112px, at most 440px (320px).
 * - 992-1199px: 29vw - 8px, 280-340px (250px).
 * - 768-991px (tablets, original header): 420px; 320px once the header has
 *   shrunk on scroll (300px / 290px).
 * - Below 768px: as wide as the row allows next to the call, cart and menu
 *   buttons, at most 380px (210px between 573 and 767px; phones already
 *   filled the row).
 *
 * !important because js/main.js writes an inline width on computers (the
 * width of an old home page product card, cached in localStorage), which
 * made the logo 304px in some browsers.
 */
function retails_logo_size_style()
{
	if (is_admin() || !retails_brand_logo_ready()) {
		return;
	}
	$desktop = 'html body .ssx-logo img.header-logo';
	$original = 'html body .site-header .sh-logo img.header-logo';
	$css = $desktop . ',' . $original . '{height:auto!important;object-fit:contain!important;object-position:50% 50%!important}'
		. $desktop . '{width:min(396px,calc(21.6vw + 100.8px))!important;max-width:100%!important}'
		. '@media (max-width:1199.98px){' . $desktop . '{width:calc(26.1vw - 7.2px)!important}}'
		// Original header: the picture fills the link, so the text tagline (on
		// the link) keeps the logo's width. Below 768px the link takes the whole
		// logo slot, up to 376px (owner 2026-11-20 ~22:40: "increase the header
		// logo size more 10% on mobile view" - it was 90% of the slot, max 342px);
		// from 768px up it stays at 90% of the slot (the owner's 10% reduction).
		. 'html body .site-header .sh-logo>a{width:100%}'
		. $original . '{width:100%!important;max-width:376px!important}'
		// Phones (owner 2026-11-21: "increase header logo size in mobile view"):
		// the logo already filled the row, so the row gives it about 22px more -
		// call/cart/menu buttons 42 -> 38px, button gap 6 -> 4px, logo gap
		// 8 -> 6px, side gutter 12 -> 10px (logo 190 -> 212px on a 360px phone).
		. '@media (max-width:572.98px){html body .site-header>.container{padding-left:10px!important;padding-right:10px!important}html body .site-header .sh-top{gap:6px}html body .site-header .sh-actions{gap:4px}html body .site-header .sh-actions .sh-icon{width:38px;min-width:38px;height:38px}}'
		. '@media (min-width:768px){html body .site-header .sh-logo{flex:0 1 420px!important}html body .site-header .sh-logo>a{width:90%}' . $original . '{max-width:378px!important}html body .site-header.is-scrolled .sh-logo img.header-logo{max-width:288px!important}}'
		. retails_logo_tagline_style($desktop, $original);
	echo '<style id="retails-logo-size">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
}

/**
 * Header tagline as sharp text (2026-11-20; the owner: "make the tagline
 * easier to read", second try). In the picture the tagline is 36 of its 323
 * rows, so it showed 8px-tall letters on computers and tablets and 4px on
 * phones, blurred by the downscaling. The picture keeps its exact size; only
 * its tagline rows are masked off (the cut falls in the empty rows 276-282,
 * between the letters and the tagline), and the same words are drawn as text
 * in the artwork's navy with Plus Jakarta Sans 800 (already loaded on every
 * page), spread to the full logo width as the owner asked:
 * - 573px and wider: under the logo, logo width: 13.4px on computers, 12.8px
 *   on tablets (9.8px once the tablet header shrinks), 9-11px at 992-1199px,
 *   11.2px at 573-767px. Each size is the logo width / the line's width in
 *   em, with 3% to spare so the fallback font (Inter) fits too.
 * - Phones below 573px: first the tagline ran the full row width under the
 *   logo and the buttons; since ~22:40 the owner wants it under the logo
 *   only, so it is the logo's width there too (see the max-width:767.98px
 *   rules below).
 * The text is a pseudo-element: no markup change, and it sits inside the link
 * whose aria-label already names the site. Browsers without mask-image keep
 * the picture's own tagline. js/logo-motion.js sets the same
 * position:relative on the link while it plays.
 */
function retails_logo_tagline_style($desktop, $original)
{
	$text = 'content:"CHANNEL LETTERS & LARGE FORMAT PRINTS SINCE 2002";position:absolute;left:0;right:0;display:block;'
		. 'font:800 13px/1 "Plus Jakarta Sans",Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;'
		. 'letter-spacing:.08em;text-transform:uppercase;color:#021024;white-space:nowrap;'
		. 'text-align:justify;text-align-last:justify;pointer-events:none';
	$mask = 'linear-gradient(#000 86.5%,transparent 86.5%)';
	$link = 'html body .ssx-logo';
	$link_original = 'html body .site-header .sh-logo>a';
	return '@supports (mask-image:none) or (-webkit-mask-image:none){'
		. $desktop . ',' . $original . '{-webkit-mask-image:' . $mask . ';mask-image:' . $mask . '}'
		// The animation's letter and sign copies (js/logo-motion.js) end at the
		// same empty rows; while they scale and turn they would draw a faint
		// line of the old tagline's top edge.
		. $link . ' .lm-l,' . $link . ' .lm-sign,' . $link_original . ' .lm-l,' . $link_original . ' .lm-sign{-webkit-mask-image:linear-gradient(#000 86.2%,transparent 86.2%);mask-image:linear-gradient(#000 86.2%,transparent 86.2%)}'
		. $link . ',' . $link_original . '{position:relative}'
		// Tagline spans the S tile's left edge to the end of "StorefrontSign"
		// (owner 2026-11-21: "fit within the S icon and storefrontsign logo
		// text"): the letters end at x=1648 of 1910 and the blue sign starts at
		// 1658, so the line stops at 86.4% of the logo width (right:13.6%) and
		// every font size is the old one x0.864 (divisors 32.8 -> 38,
		// 30.8 -> 35.6, 29.7 -> 34.4; scrolled tablet 288px -> 249px wide).
		. $link . '::after,' . $link_original . '::after{' . $text . ';right:13.6%;top:calc(100% * 281 / 323)}'
		. $link . '::after{font-size:calc(min(396px,21.6vw + 100.8px) / 38)}'
		. '@media (max-width:1199.98px){' . $link . '::after{font-size:calc((26.1vw - 7.2px) / 35.6);letter-spacing:.04em}}'
		. '@media (min-width:768px){' . $link_original . '::after{font-size:calc(378px / 38)}html body .site-header.is-scrolled .sh-logo>a::after{font-size:calc(288px / 38);right:auto;width:249px}}'
		// Below 768px the tagline sits under the logo at the logo's width
		// (owner 2026-11-20 ~22:40: "tagline should show under the logo, not
		// under the entire header on mobile"). The link is a size container, so
		// the text follows the logo's own width: 573-767px 11-11.5px; phones
		// (logo 175-229px) 5.9-7.7px with tighter letter-spacing (.02em: the
		// line is 28.8em, +3% for the fallback font). Fixed sizes for browsers
		// without container units.
		. '@media (max-width:767.98px){' . $link_original . '{container-type:inline-size}' . $link_original . '::after{font-size:8.7px;font-size:calc(100cqw / 38)}}'
		. '@media (max-width:572.98px){' . $link_original . '::after{font-size:5.5px;font-size:calc(100cqw / 34.4);letter-spacing:.02em}}'
		// Phone menu logo (header.php .ssxm__logo, 170px): it kept the picture's
		// own tagline, which ran under the blue sign. Same treatment as the
		// header (owner 2026-11-21: tagline from the S tile to the end of
		// "StorefrontSign"): mask the picture's tagline rows, draw the words as
		// text up to 86.4% of the logo width.
		. 'html body .ssxm__logo{position:relative;width:170px;max-width:100%;container-type:inline-size}'
		. 'html body .ssxm__logo img{width:100%;-webkit-mask-image:' . $mask . ';mask-image:' . $mask . '}'
		. 'html body .ssxm__logo::after{' . $text . ';right:13.6%;top:calc(100% * 281 / 323);font-size:4.9px;font-size:calc(100cqw / 34.4);letter-spacing:.02em}'
		. '}';
}
add_action('wp_head', 'retails_logo_size_style', 20);

/**
 * Footer logo tagline as sharp text too (2026-11-20; the owner: "update footer
 * logo as well", after the header got its text tagline). Same method as
 * retails_logo_tagline_style(): the dark picture (sfs-logo-dark-*, same rows
 * as the header picture) has its tagline rows masked off and the words are
 * drawn as text in the dark copy's off-white (#ECF1F7), spread to the logo
 * width. The font size follows the logo's own width (container query units;
 * 340px wide = 10.4px, 290-300px = 8.8-9.1px), with a fixed size for browsers
 * without them. The link gets an explicit width so it can be a size container.
 */
function retails_footer_logo_style()
{
	if (is_admin() || !retails_brand_logo_dark_ready()) {
		return;
	}
	$link = 'html body .sf-brand__logo';
	// Bigger footer logo (owner, 2026-11-20 "increase the footer logo size a
	// bit more"): 340px on computers (was 280), 290px on tablets and 300px on
	// phones (were 250). Beats footer.css and tablet.css (.sf-brand > .sf-brand__logo).
	$sized = 'html body .sf-brand > .sf-brand__logo';
	$css = $sized . '{width:340px;max-width:100%}'
		. '@media (max-width:991.98px){' . $sized . '{width:290px}}'
		. '@media (max-width:575.98px){' . $sized . '{width:300px}}'
		. '@supports (mask-image:none) or (-webkit-mask-image:none){'
		. $link . '{position:relative;container-type:inline-size}'
		. $link . ' img{-webkit-mask-image:linear-gradient(#000 86.5%,transparent 86.5%);mask-image:linear-gradient(#000 86.5%,transparent 86.5%)}'
		// The footer logo animates too (js/logo-motion.js): its letter and sign
		// copies get the header's cut, so no edge of the picture's tagline shows.
		. $link . ' .lm-l,' . $link . ' .lm-sign{-webkit-mask-image:linear-gradient(#000 86.2%,transparent 86.2%);mask-image:linear-gradient(#000 86.2%,transparent 86.2%)}'
		. $link . '::after{content:"CHANNEL LETTERS & LARGE FORMAT PRINTS SINCE 2002";position:absolute;left:0;right:13.6%;top:calc(100% * 281 / 323);display:block;'
		// right:13.6% + / 38 (was 0 + / 32.8): S tile to the end of
		// "StorefrontSign", like the header tagline (owner 2026-11-21).
		. 'font:800 7.3px/1 "Plus Jakarta Sans",Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;font-size:calc(100cqw / 38);'
		. 'letter-spacing:.08em;text-transform:uppercase;color:#ecf1f7;white-space:nowrap;text-align:justify;text-align-last:justify;pointer-events:none}'
		. '}';
	echo '<style id="retails-footer-logo">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
}
add_action('wp_head', 'retails_footer_logo_style', 21);

function retails_logo_motion_assets()
{
	// The theme is being renamed from retails_ to retails_; work with either
	// motion.php, so this file can go live before or after that rename.
	$level = function_exists('retails_motion_level') ? 'retails_motion_level' : (function_exists('retails_motion_level') ? 'retails_motion_level' : '');
	if (!$level || 'full' !== call_user_func($level) || !retails_brand_logo_ready()) {
		return;
	}
	$path = get_template_directory() . '/js/logo-motion.js';
	if (!is_readable($path)) {
		return;
	}
	wp_enqueue_script(
		'retails-logo-motion',
		get_template_directory_uri() . '/js/logo-motion.js',
		array(),
		(string) filemtime($path),
		array('in_footer' => true, 'strategy' => 'defer')
	);
}
add_action('wp_enqueue_scripts', 'retails_logo_motion_assets', 102);
