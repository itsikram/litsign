<?php
/**
 * Per-page hero photos and background settings with gradient overlay presets.
 *
 * Fields:
 *  - Pages: "Hero images" box on the edit screen.
 *  - Product categories: fields on the add/edit form (child categories use
 *    their parent's photo until they get their own).
 *  - Settings > Storefront Sign > Images: a fallback pair for the text-only
 *    guide pages (warranty, permits, industries ...), see wholesale_image_slots().
 *
 * Pages use their featured image on desktop, falling back to the replaceable
 * default hero in Storefront Sign > Images. The photos replace the background of the hero
 * band the template already prints (.home-hero-content, .bd-hero, .sf-hero,
 * .sc-hero, .contact-hero, .cla-hero, .pkg-hero, .track-hero), so no template
 * needs its own wiring.
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_HERO_DESKTOP = '_sfs_hero_desktop';
const WHOLESALE_HERO_MOBILE = '_sfs_hero_mobile';
const WHOLESALE_HERO_OVERLAY = '_sfs_hero_overlay';
const WHOLESALE_HERO_FOCUS = '_sfs_hero_focus';
const WHOLESALE_HERO_BACKGROUND = '_sfs_hero_background';

/**
 * Overlay presets: label, plus the gradient layers for desktop, tablet and
 * phone. Same navy as the rest of the site's heroes (rgb 3, 24, 53). Phones get
 * a top-down overlay because the copy spans the full width there.
 */
function wholesale_hero_overlays()
{
	$n = 'rgba(3,24,53,';
	return array(
		'left' => array(
			'label' => 'Dark on the left (headline left, subject right)',
			'd' => "linear-gradient(90deg,{$n}.96) 0%,{$n}.86) 32%,{$n}.34) 58%,{$n}.06) 100%),linear-gradient(0deg,{$n}.38) 0%,{$n}0) 38%)",
			't' => "linear-gradient(90deg,{$n}.96) 0%,{$n}.9) 50%,{$n}.7) 80%,{$n}.45) 100%)",
			'm' => "linear-gradient(180deg,{$n}.9) 0%,{$n}.74) 38%,{$n}.52) 72%,{$n}.42) 100%)",
		),
		'bottom' => array(
			'label' => 'Dark at the bottom (centered or short heroes)',
			'd' => "linear-gradient(180deg,{$n}.32) 0%,{$n}.5) 38%,{$n}.92) 100%)",
			't' => "linear-gradient(180deg,{$n}.4) 0%,{$n}.62) 40%,{$n}.94) 100%)",
			'm' => "linear-gradient(180deg,{$n}.55) 0%,{$n}.78) 50%,{$n}.94) 100%)",
		),
		'full' => array(
			'label' => 'Even dim (busy photos)',
			'd' => "linear-gradient({$n}.72),{$n}.72))",
			't' => "linear-gradient({$n}.76),{$n}.76))",
			'm' => "linear-gradient({$n}.72),{$n}.72))",
		),
		'brand' => array(
			'label' => 'Navy to brand blue tint',
			'd' => "linear-gradient(115deg,{$n}.95) 0%,rgba(8,62,112,.82) 45%,rgba(31,168,222,.36) 100%)",
			't' => "linear-gradient(115deg,{$n}.95) 0%,rgba(8,62,112,.86) 55%,rgba(31,168,222,.4) 100%)",
			'm' => "linear-gradient(170deg,{$n}.95) 0%,rgba(8,62,112,.84) 55%,rgba(31,168,222,.45) 100%)",
		),
	);
}

/** Focal point choices: key => CSS background-position. */
function wholesale_hero_focus_points()
{
	return array(
		'left top' => array('Top left', '15% 15%'),
		'center top' => array('Top', '50% 15%'),
		'right top' => array('Top right', '85% 15%'),
		'left center' => array('Left', '15% 50%'),
		'center center' => array('Center (default)', '50% 50%'),
		'right center' => array('Right', '85% 50%'),
		'left bottom' => array('Bottom left', '15% 85%'),
		'center bottom' => array('Bottom', '50% 85%'),
		'right bottom' => array('Bottom right', '85% 85%'),
	);
}

/** Templates whose hero is a text-only .sf-hero band (the fallback photo goes here). */
function wholesale_hero_fallback_templates()
{
	return array(
		'page-industries.php', 'page-industry.php', 'page-location.php', 'page-locations.php',
		'page-shipping-returns.php', 'page-sign-permits.php', 'page-warranty.php',
		'page-how-to-install-channel-letters.php', 'page-storefront-signs.php',
		'page-channel-letter-cost.php', 'page-design-templates.php', 'page-developer.php',
	);
}

function wholesale_hero_is_fallback_page($page_id)
{
	$templates = wholesale_hero_fallback_templates();
	$candidates = array($GLOBALS['wholesale_hero_template'] ?? '', get_page_template_slug($page_id));
	$page = get_post($page_id);
	if ($page) {
		$candidates[] = 'page-' . $page->post_name . '.php';
	}
	if (function_exists('wholesale_guide_current')) {
		$guide = wholesale_guide_current();
		$candidates[] = is_array($guide) ? ($guide['template'] ?? '') : '';
	}
	foreach ($candidates as $candidate) {
		if (in_array(basename((string) $candidate), $templates, true)) {
			return true;
		}
	}
	return false;
}

function wholesale_hero_remember_template($template)
{
	$GLOBALS['wholesale_hero_template'] = basename((string) $template);
	return $template;
}
add_filter('template_include', 'wholesale_hero_remember_template', 99);

/* ------------------------------------------------------------------------
 * Reading
 * --------------------------------------------------------------------- */

function wholesale_hero_valid_image($id)
{
	$id = absint($id);
	return $id && wp_attachment_is_image($id) ? $id : 0;
}

/** Safe CSS values for each viewport's photo layer. */
function wholesale_hero_background_defaults()
{
	return array('color' => '#031835', 'size' => 'cover', 'repeat' => 'no-repeat', 'x' => 50, 'y' => 50, 'attachment' => 'scroll',
		'overlay_type' => 'preset', 'overlay_color' => '#031835', 'overlay_color2' => '#031835',
		'overlay_opacity' => 85, 'overlay_opacity2' => 10, 'overlay_angle' => 90,
		'overlay_stop' => 0, 'overlay_stop2' => 100, 'overlay_blend' => 'normal');
}

function wholesale_hero_background_clean($input)
{
	$defaults = wholesale_hero_background_defaults();
	$input = is_array($input) ? $input : array();
	$size = isset($input['size']) && is_scalar($input['size']) ? strtolower(trim((string) $input['size'])) : '';
	// CSS background-size accepts one or two lengths, as well as these keywords.
	if (!in_array($size, array('cover', 'contain', 'auto'), true)
		&& !preg_match('/^(?:auto|\d{1,4}(?:\.\d{1,2})?(?:px|%|vw|vh))(?:\s+(?:auto|\d{1,4}(?:\.\d{1,2})?(?:px|%|vw|vh)))?$/', $size)) {
		$size = $defaults['size'];
	}
	$repeat = isset($input['repeat']) && is_scalar($input['repeat']) ? (string) $input['repeat'] : '';
	$attachment = isset($input['attachment']) && is_scalar($input['attachment']) ? (string) $input['attachment'] : '';
	$color = isset($input['color']) && is_scalar($input['color']) ? sanitize_hex_color((string) $input['color']) : '';
	$overlay_color = isset($input['overlay_color']) && is_scalar($input['overlay_color']) ? sanitize_hex_color((string) $input['overlay_color']) : '';
	$overlay_color2 = isset($input['overlay_color2']) && is_scalar($input['overlay_color2']) ? sanitize_hex_color((string) $input['overlay_color2']) : '';
	$overlay_type = isset($input['overlay_type']) && is_scalar($input['overlay_type']) ? (string) $input['overlay_type'] : '';
	$overlay_blend = isset($input['overlay_blend']) && is_scalar($input['overlay_blend']) ? (string) $input['overlay_blend'] : '';
	return array(
		'color' => $color ? $color : $defaults['color'],
		'size' => $size,
		'repeat' => in_array($repeat, array('no-repeat', 'repeat', 'repeat-x', 'repeat-y', 'space', 'round'), true) ? $repeat : $defaults['repeat'],
		'x' => isset($input['x']) && is_numeric($input['x']) ? max(0, min(100, (float) $input['x'])) : $defaults['x'],
		'y' => isset($input['y']) && is_numeric($input['y']) ? max(0, min(100, (float) $input['y'])) : $defaults['y'],
		'attachment' => in_array($attachment, array('scroll', 'fixed', 'local'), true) ? $attachment : $defaults['attachment'],
		'overlay_type' => in_array($overlay_type, array('preset', 'solid', 'gradient'), true) ? $overlay_type : 'preset',
		'overlay_color' => $overlay_color ? $overlay_color : $defaults['overlay_color'],
		'overlay_color2' => $overlay_color2 ? $overlay_color2 : $defaults['overlay_color2'],
		'overlay_opacity' => isset($input['overlay_opacity']) && is_numeric($input['overlay_opacity']) ? max(0, min(100, (int) $input['overlay_opacity'])) : $defaults['overlay_opacity'],
		'overlay_opacity2' => isset($input['overlay_opacity2']) && is_numeric($input['overlay_opacity2']) ? max(0, min(100, (int) $input['overlay_opacity2'])) : $defaults['overlay_opacity2'],
		'overlay_angle' => isset($input['overlay_angle']) && is_numeric($input['overlay_angle']) ? max(0, min(360, (int) $input['overlay_angle'])) : $defaults['overlay_angle'],
		'overlay_stop' => isset($input['overlay_stop']) && is_numeric($input['overlay_stop']) ? max(0, min(100, (int) $input['overlay_stop'])) : $defaults['overlay_stop'],
		'overlay_stop2' => isset($input['overlay_stop2']) && is_numeric($input['overlay_stop2']) ? max(0, min(100, (int) $input['overlay_stop2'])) : $defaults['overlay_stop2'],
		'overlay_blend' => in_array($overlay_blend, array('normal', 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'soft-light'), true) ? $overlay_blend : 'normal',
	);
}

function wholesale_hero_overlay_rgba($hex, $opacity)
{
	$hex = ltrim($hex, '#');
	if (3 === strlen($hex)) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$rgb = sscanf($hex, '%02x%02x%02x');
	return 'rgba(' . implode(',', $rgb) . ',' . ($opacity / 100) . ')';
}

function wholesale_hero_overlay_layer($settings, $preset, $viewport)
{
	if ('preset' === $settings['overlay_type']) {
		return wholesale_hero_overlays()[$preset][$viewport];
	}
	$first = wholesale_hero_overlay_rgba($settings['overlay_color'], $settings['overlay_opacity']);
	if ('solid' === $settings['overlay_type']) {
		return 'linear-gradient(' . $first . ',' . $first . ')';
	}
	$second = wholesale_hero_overlay_rgba($settings['overlay_color2'], $settings['overlay_opacity2']);
	return 'linear-gradient(' . $settings['overlay_angle'] . 'deg,' . $first . ' ' . $settings['overlay_stop']
		. '%,' . $second . ' ' . $settings['overlay_stop2'] . '%)';
}

function wholesale_hero_overlay_layers($settings, $preset, $viewport)
{
	return 'preset' === $settings['overlay_type'] && 'left' === $preset && 'd' === $viewport ? 3 : 2;
}

function wholesale_hero_background_read($kind, $id)
{
	$get = 'term' === $kind ? 'get_term_meta' : 'get_post_meta';
	$stored = $get($id, WHOLESALE_HERO_BACKGROUND, true);
	$stored = is_array($stored) ? $stored : array();
	$result = array();
	foreach (array('desktop', 'mobile') as $viewport) {
		$result[$viewport] = wholesale_hero_background_clean($stored[$viewport] ?? array());
	}
	// Keep existing focal-point choices working until an editor saves new positions.
	if (!isset($stored['desktop'])) {
		$focus = (string) $get($id, WHOLESALE_HERO_FOCUS, true);
		if (isset(wholesale_hero_focus_points()[$focus])) {
			$parts = explode(' ', wholesale_hero_focus_points()[$focus][1]);
			$result['desktop']['x'] = (int) $parts[0];
			$result['desktop']['y'] = (int) $parts[1];
			$result['mobile']['x'] = $result['desktop']['x'];
			$result['mobile']['y'] = $result['desktop']['y'];
		}
	}
	return $result;
}

/** Image, overlay, and background values for a page or product category. */
function wholesale_hero_read($kind, $id)
{
	$get = 'term' === $kind ? 'get_term_meta' : 'get_post_meta';
	$overlay = (string) $get($id, WHOLESALE_HERO_OVERLAY, true);
	$focus = (string) $get($id, WHOLESALE_HERO_FOCUS, true);
	return array(
		'desktop' => 'term' === $kind ? wholesale_hero_valid_image($get($id, WHOLESALE_HERO_DESKTOP, true)) : wholesale_hero_valid_image(get_post_thumbnail_id($id)),
		'mobile' => wholesale_hero_valid_image($get($id, WHOLESALE_HERO_MOBILE, true)),
		'overlay' => isset(wholesale_hero_overlays()[$overlay]) ? $overlay : 'left',
		'focus' => isset(wholesale_hero_focus_points()[$focus]) ? $focus : 'center center',
		'background' => wholesale_hero_background_read($kind, $id),
	);
}

/**
 * The hero configuration for the page or product category being shown.
 */
function wholesale_hero_config()
{
	static $config = false;
	if (false !== $config) {
		return $config;
	}
	$config = null;
	if (is_admin() || is_feed() || is_404() || is_search()) {
		return $config;
	}

	$found = null;
	$object = get_queried_object();

	// Category pages are served by home.php / page-channel-letters.php through the
	// category_slug query var (see wholesale_category_template()), not as a term archive.
	$slug = get_query_var('category_slug');
	$slug = $slug ? sanitize_title($slug) : '';
	$term = $slug ? get_term_by('slug', $slug, 'product_category') : null;
	if (!$term && $object instanceof WP_Term && 'product_category' === $object->taxonomy) {
		$term = $object;
	}
	if (!$term && !($object instanceof WP_Post && 'page' === $object->post_type) && !is_front_page()) {
		return $config;
	}
	for ($i = 0; $term && !is_wp_error($term) && $i < 6; $i++) {
		$values = wholesale_hero_read('term', $term->term_id);
		if ($values['desktop'] || $values['mobile'] || get_term_meta($term->term_id, WHOLESALE_HERO_BACKGROUND, true)) {
			$found = $values;
			break;
		}
		$term = $term->parent ? get_term($term->parent, 'product_category') : null;
	}

	if (!$found) {
		$page_id = 0;
		if ($object instanceof WP_Post && 'page' === $object->post_type) {
			$page_id = $object->ID;
		} elseif (is_front_page() && (int) get_option('page_on_front')) {
			$page_id = (int) get_option('page_on_front');
		}
		if ($page_id && !$slug) {
			$values = wholesale_hero_read('post', $page_id);
			// Preserve explicitly configured guide-page photos when the page has
			// no featured image. A page-specific phone photo still takes priority.
			if (!$values['desktop'] && wholesale_hero_is_fallback_page($page_id)) {
				$values['desktop'] = wholesale_image_slot_effective_id('hero_fallback');
				if (!$values['mobile']) {
					$values['mobile'] = wholesale_image_slot_effective_id('hero_fallback_mobile');
				}
			}
			$found = $values;
		}
	}

	if (!$found) {
		// Product categories without their own image use the theme default.
		$found = array('desktop' => 0, 'mobile' => 0, 'overlay' => 'left',
			'background' => array('desktop' => wholesale_hero_background_defaults(), 'mobile' => wholesale_hero_background_defaults()));
	}

	$url = static function ($id, $size) {
		if (!$id) {
			return '';
		}
		$src = wp_get_attachment_image_url($id, $size);
		if ($src && function_exists('wholesale_webp_url')) {
			$src = wholesale_webp_url($src);
		}
		return $src ? esc_url_raw($src) : '';
	};

	// Home page: the full upload (up to 2560 px wide). Its hero is ~2300 px wide on
	// large screens, so the 2048 copy got stretched and looked soft (owner, Nov 23 2026).
	$desktop_url = $url($found['desktop'], is_front_page() ? 'full' : '2048x2048');
	if (!$desktop_url) {
		$desktop_url = wholesale_default_hero_url();
	}
	$config = array(
		'desktop' => $desktop_url,
		// Phones: their own photo (up to 1536 px, sharp on high-density screens),
		// else the 1024 px copy of the desktop one.
		// Tall phone photos (about 1024 × 2000) are used whole: their 1536 copy would be
		// only ~700 px wide and look soft on phones.
		'mobile' => $found['mobile'] ? $url($found['mobile'], wholesale_hero_mobile_size($found['mobile'])) : ($found['desktop'] ? $url($found['desktop'], 'large') : $desktop_url),
		'overlay' => $found['overlay'],
		'background' => $found['background'],
		// Share previews (og:image): the uploaded file at 1024 px, in the format it was uploaded in.
		'share' => $found['desktop'] || $found['mobile'] ? (string) wp_get_attachment_image_url($found['desktop'] ? $found['desktop'] : $found['mobile'], 'large') : '',
	);
	if ('' === $config['desktop'] && '' === $config['mobile']) {
		$config = null;
		return $config;
	}
	// AVIF copies (sharper at fewer bytes than WebP); '' until the cron has made one.
	$avif = static function ($src) {
		return $src && function_exists('wholesale_hero_avif_url') ? esc_url_raw((string) wholesale_hero_avif_url($src)) : '';
	};
	$config['desktop_avif'] = $avif($config['desktop']);
	$config['mobile_avif'] = $config['mobile'] === $config['desktop'] ? $config['desktop_avif'] : $avif($config['mobile']);
	return $config;
}

/** Phone hero size: the full upload when it is at most 1600 px wide, else the 1536 copy. */
function wholesale_hero_mobile_size($id)
{
	$meta = wp_get_attachment_metadata($id);
	return is_array($meta) && !empty($meta['width']) && (int) $meta['width'] <= 1600 ? 'full' : '1536x1536';
}

function wholesale_hero_body_class($classes)
{
	if (wholesale_hero_config()) {
		$classes[] = 'sfs-hero-on';
	}
	return $classes;
}
add_filter('body_class', 'wholesale_hero_body_class');

function wholesale_hero_css_url($url)
{
	return "url('" . str_replace(array("'", '"', '(', ')', ' ', "\n"), array('%27', '%22', '%28', '%29', '%20', ''), $url) . "')";
}

function wholesale_hero_background_css($settings, $layers = 2)
{
	$settings = wholesale_hero_background_clean($settings);
	$position = $settings['x'] . '% ' . $settings['y'] . '%';
	$overlays = max(1, $layers - 1);
	return 'background-color:' . $settings['color'] . ';background-size:' . implode(',', array_merge(array_fill(0, $overlays, 'cover'), array($settings['size'])))
		. ';background-repeat:' . implode(',', array_merge(array_fill(0, $overlays, 'no-repeat'), array($settings['repeat'])))
		. ';background-position:' . implode(',', array_merge(array_fill(0, $overlays, 'center center'), array($position)))
		. ';background-attachment:' . implode(',', array_merge(array_fill(0, $overlays, 'scroll'), array($settings['attachment']))) . ';';
}

/* ------------------------------------------------------------------------
 * Front end: preload + CSS
 * --------------------------------------------------------------------- */

function wholesale_hero_head()
{
	$config = wholesale_hero_config();
	if (!$config) {
		return;
	}

	// The template's own hero photo is no longer shown; don't preload it.
	if ($config['desktop']) {
		$GLOBALS['wholesale_hero_custom'] = true;
	}

	$desktop = $config['desktop'];
	$mobile = $config['mobile'];
	// Preload the file the CSS below will pick: AVIF when it exists.
	$desktop_preload = $config['desktop_avif'] ? $config['desktop_avif'] : $desktop;
	$mobile_preload = $config['mobile_avif'] ? $config['mobile_avif'] : $mobile;
	$type = static function ($src) {
		return preg_match('/\.avif$/i', (string) $src) ? ' type="image/avif"' : '';
	};
	if ($desktop && $mobile && $desktop === $mobile) {
		printf('<link rel="preload" as="image" href="%s"%s fetchpriority="high">' . "\n", esc_url($desktop_preload), $type($desktop_preload));
	} else {
		if ($mobile) {
			printf('<link rel="preload" as="image" href="%s"%s media="(max-width: 767px)" fetchpriority="high">' . "\n", esc_url($mobile_preload), $type($mobile_preload));
		}
		if ($desktop) {
			printf('<link rel="preload" as="image" href="%s"%s media="(min-width: 768px)" fetchpriority="high">' . "\n", esc_url($desktop_preload), $type($desktop_preload));
		}
	}

	$desktop_settings = $config['background']['desktop'];
	$mobile_settings = $config['background']['mobile'];
	$desktop_background = wholesale_hero_background_css($desktop_settings);
	$mobile_background = wholesale_hero_background_css($config['background']['mobile']);
	$tablet_overlay = wholesale_hero_overlay_layer($desktop_settings, $config['overlay'], 't');
	$desktop_overlay = wholesale_hero_overlay_layer($desktop_settings, $config['overlay'], 'd');
	$phone_overlay = wholesale_hero_overlay_layer($mobile_settings, $config['overlay'], 'm');
	$desktop_layers = wholesale_hero_overlay_layers($desktop_settings, $config['overlay'], 'd');
	$desktop_mode = 'preset' === $desktop_settings['overlay_type'] ? 'normal' : $desktop_settings['overlay_blend'];
	$mobile_mode = 'preset' === $mobile_settings['overlay_type'] ? 'normal' : $mobile_settings['overlay_blend'];
	$desktop_blend = 'background-blend-mode:' . implode(',', array_merge(array($desktop_mode), array_fill(1, $desktop_layers - 1, 'normal'))) . ';';
	$mobile_blend = 'background-blend-mode:' . $mobile_mode . ',normal;';

	// The hero bands the templates print. body.sfs-hero-on keeps these rules
	// above the per-page rules in style.css, whatever the load order.
	$bands = 'body.sfs-hero-on .home-hero .home-hero-content,body.sfs-hero-on .bd-hero,body.sfs-hero-on .sf-hero,body.sfs-hero-on .sc-hero,body.sfs-hero-on .contact-hero,body.sfs-hero-on .cla-hero,body.sfs-hero-on .pkg-hero,body.sfs-hero-on .track-hero';
	$base = $desktop_background;

	$css = $bands . '{' . $base . $desktop_blend . '}';
	if ($desktop) {
		$d = wholesale_hero_css_url($desktop);
		$css .= '@media(min-width:768px){' . $bands . '{background-image:' . $tablet_overlay . ',' . $d . '}}';
		$css .= '@media(min-width:992px){' . $bands . '{background-image:' . $desktop_overlay . ',' . $d . ';' . wholesale_hero_background_css($desktop_settings, $desktop_layers) . $desktop_blend . '}';
		// Channel letters page: the photo sits on a ::before layer that starts 22% in.
		$cl_overlay = 'preset' === $desktop_settings['overlay_type'] && 'left' === $config['overlay']
			? 'linear-gradient(90deg,rgba(3,24,53,1) 0%,rgba(3,24,53,.88) 18%,rgba(3,24,53,.4) 42%,rgba(3,24,53,.05) 100%)'
			: $desktop_overlay;
		$css .= 'body.sfs-hero-on .home-hero--cl-page .home-hero-content{background-image:none}';
		$css .= 'body.sfs-hero-on .home-hero--cl-page .home-hero-content::before{background-image:' . $cl_overlay . ',' . $d . ';' . $desktop_background . 'background-blend-mode:' . $desktop_mode . ',normal;}}';
	}
	if ($mobile) {
		$m = wholesale_hero_css_url($mobile);
		$css .= '@media(max-width:767px){' . $bands . '{background-image:' . $phone_overlay . ',' . $m . ';' . $mobile_background . $mobile_blend . '}}';
	}
	// Browsers that understand image-set() with type() get the AVIF copy.
	if ($config['desktop_avif'] || $config['mobile_avif']) {
		$set = static function ($avif, $fallback) {
			return 'image-set(' . wholesale_hero_css_url($avif) . ' type("image/avif"),' . wholesale_hero_css_url($fallback) . ')';
		};
		$css .= '@supports(background-image:image-set(url("a.avif") type("image/avif"))){';
		if ($desktop && $config['desktop_avif']) {
			$ds = $set($config['desktop_avif'], $desktop);
			$css .= '@media(min-width:768px){' . $bands . '{background-image:' . $tablet_overlay . ',' . $ds . '}}';
			// Keep the channel letters page's photo on its ::before layer only (see above).
			$css .= '@media(min-width:992px){' . $bands . '{background-image:' . $desktop_overlay . ',' . $ds . '}body.sfs-hero-on .home-hero--cl-page .home-hero-content{background-image:none}}';
		}
		if ($mobile && $config['mobile_avif']) {
			$css .= '@media(max-width:767px){' . $bands . '{background-image:' . $phone_overlay . ',' . $set($config['mobile_avif'], $mobile) . '}}';
		}
		$css .= '}';
	}

	// Pages whose hero is normally a light band: white text on the photo, the
	// same treatment as .sf-hero--photo in css/seo-pages.css.
	$css .= 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo),body.sfs-hero-on .sc-hero,body.sfs-hero-on .contact-hero{color:#fff;border-color:transparent}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo){padding-bottom:72px}'
		. 'body.sfs-hero-on .sc-hero{padding-bottom:48px}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-hero-title,body.sfs-hero-on .sc-hero-title,body.sfs-hero-on .contact-hero h1{color:#fff}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-hero-lead,body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-byline,body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-hero-points,body.sfs-hero-on .sc-hero-lead,body.sfs-hero-on .contact-hero-copy>p:not(.contact-eyebrow),body.sfs-hero-on .contact-trust-row,body.sfs-hero-on .contact-trust-row strong{color:rgba(255,255,255,.88)}'
		// Contact trust pills: style.css gives them a see-through white fill for the light
		// band, which washed out the white text on the photo (owner, Nov 20 2026). Same
		// navy glass as the home hero product cards, with the site's light blue.
		. 'body.sfs-hero-on .contact-trust-row span{border-color:rgba(31,168,222,.55);background:rgba(3,24,53,.78);color:#fff;-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}'
		. 'body.sfs-hero-on .contact-trust-row strong{color:#7fd3f2}'
		// css/ui-polish.css forces .cl-kicker to dark teal with !important (for white bands).
		. 'body.sfs-hero-on .sf-hero .cl-kicker{color:#7fd3f2!important}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .site-breadcrumbs ol,body.sfs-hero-on .sc-hero .site-breadcrumbs ol{border-color:rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:rgba(255,255,255,.7)}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .site-breadcrumbs a,body.sfs-hero-on .sc-hero .site-breadcrumbs a{color:#7fd3f2}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .site-breadcrumbs [aria-current="page"],body.sfs-hero-on .sc-hero .site-breadcrumbs [aria-current="page"]{color:#fff}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-button-outline{border-color:#fff;color:#fff}'
		// Plain text links in the hero copy (phone numbers, jump links) turn light on the photo.
		. 'body.sfs-hero-on .contact-hero-call a,body.sfs-hero-on .sf-hero .gd-hero-call,body.sfs-hero-on .sf-hero .gd-jump-links a,body.sfs-hero-on .sf-hero-lead a,body.sfs-hero-on .sf-byline a,body.sfs-hero-on .sf-hero-points a,body.sfs-hero-on .sc-hero-lead a{color:#7fd3f2}'
		// Sign packages and track order print a plain text header inside the page
		// container: with a photo it becomes a rounded photo card with white text.
		. 'body.sfs-hero-on .pkg-hero,body.sfs-hero-on .track-hero{max-width:none;border-radius:16px;padding:64px 48px;min-height:300px;display:flex;flex-direction:column;justify-content:center;color:#fff}'
		. 'body.sfs-hero-on .pkg-hero{margin-bottom:28px}body.sfs-hero-on .pkg-hero>*{max-width:640px}'
		. 'body.sfs-hero-on .track-hero{align-items:center;margin-bottom:28px}body.sfs-hero-on .track-hero>*{max-width:560px}'
		. 'body.sfs-hero-on .pkg-hero h1,body.sfs-hero-on .track-hero h1{color:#fff}'
		. 'body.sfs-hero-on .pkg-hero p,body.sfs-hero-on .track-hero p{color:rgba(255,255,255,.9)}'
		. 'body.sfs-hero-on .track-hero .track-eyebrow{color:#7fd3f2}'
		. '@media(max-width:767px){body.sfs-hero-on .pkg-hero,body.sfs-hero-on .track-hero{padding:44px 22px;min-height:340px;border-radius:12px}'
		// Phones show more of the photo (lighter overlay): a soft shadow keeps the copy crisp.
		. 'body.sfs-hero-on :is(.home-hero-copy,.bd-hero-copy,.sf-hero>.container,.sc-hero-copy,.contact-hero-copy,.cla-hero-copy,.pkg-hero,.track-hero)>:is(h1,p){text-shadow:0 1px 3px rgba(3,24,53,.65)}}';

	echo '<style id="sfs-hero-images">' . $css . "</style>\n"; // Built from fixed presets and escaped URLs.
}
add_action('wp_head', 'wholesale_hero_head', 0);

/* ------------------------------------------------------------------------
 * Admin fields
 * --------------------------------------------------------------------- */

function wholesale_hero_field_html($values, $kind = 'post', $post_id = 0)
{
	$overlays = wholesale_hero_overlays();
	$images = array();
	if ('term' === $kind) {
		$images['desktop'] = array('Desktop photo', 'Landscape, about 1920 &times; 800 px (WebP or JPEG). Empty: the default hero banner is used.');
	}
	$images['mobile'] = array('Phone photo (optional)', 'Portrait or square, about 900 &times; 1200 px, for screens under 768 px. Empty: phones use the desktop photo.');

	wp_nonce_field('sfs_hero_save', 'sfs_hero_nonce');
	echo '<div class="sfs-hero-fields" data-presets="' . esc_attr(wp_json_encode($overlays)) . '" data-default-image="' . esc_url(wholesale_default_hero_url()) . '"><p class="sfs-hero-intro">Choose the image above, then tune its crop and overlay for each screen size. Changes appear in the preview; save the page to publish them.</p>';
	if ('post' === $kind) {
		$featured = (int) $values['desktop'];
		$guide_id = !$featured && $post_id && wholesale_hero_is_fallback_page($post_id) ? wholesale_image_slot_effective_id('hero_fallback') : 0;
		$desktop_preview = $featured ? wp_get_attachment_image_url($featured, 'medium') : ($guide_id ? wp_get_attachment_image_url($guide_id, 'medium') : wholesale_default_hero_url());
		echo '<div class="sfs-hero-source"><strong>Desktop photo</strong>';
		if ($desktop_preview) {
			printf('<div class="sfs-hero-source-image"><img src="%s" alt=""></div>', esc_url($desktop_preview));
		}
		$source_text = $featured ? 'Uses this page\'s Featured image. Change it in the Featured image panel.'
			: ($guide_id ? 'Uses the configured guide-page hero photo. Add a Featured image to replace it on this page.'
				: 'Uses the theme default hero banner. Add a Featured image to replace it on this page.');
		echo '<p class="description" style="margin:4px 0 0">' . esc_html($source_text) . '</p>';
		if (current_user_can('manage_options')) {
			printf('<p style="margin:5px 0 0"><a href="%s">Replace the %s in Storefront Sign settings</a></p>', esc_url(admin_url('admin.php?page=wholesale-settings#images')), $guide_id ? 'guide photo' : 'default banner');
		}
		echo '</div>';
	}
	foreach ($images as $key => $info) {
		$id = (int) $values[$key];
		$name = 'sfs_hero_' . $key;
		$preview = $id ? wp_get_attachment_image_url($id, 'medium') : '';
		printf(
			'<div class="sfs-hero-img sfs-hero-source" data-viewport="%s"><strong>%s</strong><div class="sfs-hero-prev">%s</div><input type="hidden" name="%s" value="%d"><button type="button" class="button sfs-hero-pick">Choose image</button> <button type="button" class="button-link sfs-hero-clear"%s>Remove</button><p class="description">%s</p></div>',
			esc_attr($key),
			esc_html($info[0]),
			$preview ? '<img src="' . esc_url($preview) . '" alt="" style="max-width:100%;height:auto;display:block">' : '',
			esc_attr($name),
			$id,
			$id ? '' : ' style="display:none"',
			wp_kses($info[1], array())
		);
	}

	echo '<div class="sfs-hero-preset"><label><strong>Theme overlay preset</strong><select name="sfs_hero_overlay">';
	foreach ($overlays as $key => $overlay) {
		printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($values['overlay'], $key, false), esc_html($overlay['label']));
	}
	echo '</select></label><p class="description">Used when a screen size is set to “Theme preset” below.</p></div>';
	echo '<div class="sfs-hero-tabs" role="tablist" aria-label="Hero preview size"><button type="button" class="sfs-hero-tab is-active" data-target="desktop" role="tab" aria-selected="true">Desktop</button><button type="button" class="sfs-hero-tab" data-target="mobile" role="tab" aria-selected="false">Phone</button></div>';

	foreach (array('desktop' => 'Desktop background', 'mobile' => 'Phone background') as $viewport => $title) {
		$background = $values['background'][$viewport];
		$prefix = 'sfs_hero_background[' . $viewport . ']';
		$preview = 'mobile' === $viewport && $values['mobile'] ? wp_get_attachment_image_url($values['mobile'], 'medium') : '';
		if (!$preview && 'post' === $kind) {
			$preview = $desktop_preview;
		} elseif (!$preview) {
			$preview = $values['desktop'] ? wp_get_attachment_image_url($values['desktop'], 'medium') : wholesale_default_hero_url();
		}
		printf('<section class="sfs-hero-panel%s" data-viewport="%s"%s><h3>%s</h3><div class="sfs-hero-preview%s" data-image="%s" role="img" aria-label="Hero background preview"><span>YOUR HERO TEXT</span></div><div class="sfs-hero-controls">', 'desktop' === $viewport ? ' is-active' : '', esc_attr($viewport), 'desktop' === $viewport ? '' : ' hidden', esc_html($title), 'mobile' === $viewport ? ' is-phone' : '', esc_url($preview));
		echo '<fieldset class="sfs-hero-control-group"><legend>Image layout</legend><div class="sfs-hero-grid">';
		printf('<label>Background color<input type="color" name="%s[color]" value="%s"></label>', esc_attr($prefix), esc_attr($background['color']));
		printf('<label>Image size<input type="text" name="%s[size]" value="%s" list="sfs-hero-sizes" class="widefat" placeholder="cover"><small>cover, contain, auto, or 120%% auto</small></label>', esc_attr($prefix), esc_attr($background['size']));
		printf('<label>Repeat<select name="%s[repeat]" class="widefat">', esc_attr($prefix));
		foreach (array('no-repeat' => 'No repeat', 'repeat' => 'Repeat', 'repeat-x' => 'Repeat horizontally', 'repeat-y' => 'Repeat vertically', 'space' => 'Space', 'round' => 'Round') as $key => $label) {
			printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($background['repeat'], $key, false), esc_html($label));
		}
		echo '</select></label>';
		foreach (array('x' => 'Position X (%)', 'y' => 'Position Y (%)') as $axis => $label) {
			printf('<label>%s<input type="number" name="%s[%s]" value="%s" min="0" max="100" step="1"></label>', esc_html($label), esc_attr($prefix), esc_attr($axis), esc_attr($background[$axis]));
		}
		printf('<label>Attachment<select name="%s[attachment]" class="widefat">', esc_attr($prefix));
		foreach (array('scroll' => 'Scroll', 'fixed' => 'Fixed', 'local' => 'Local') as $key => $label) {
			printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($background['attachment'], $key, false), esc_html($label));
		}
		echo '</select></label></div></fieldset>';
		echo '<fieldset class="sfs-hero-control-group"><legend>Background overlay</legend><div class="sfs-hero-grid">';
		printf('<label>Style<select name="%s[overlay_type]" class="sfs-hero-overlay-type">', esc_attr($prefix));
		foreach (array('preset' => 'Theme preset', 'solid' => 'Solid color', 'gradient' => 'Gradient') as $key => $label) {
			printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($background['overlay_type'], $key, false), esc_html($label));
		}
		echo '</select></label>';
		printf('<label class="sfs-hero-custom">First color<input type="color" name="%s[overlay_color]" value="%s"></label>', esc_attr($prefix), esc_attr($background['overlay_color']));
		printf('<label class="sfs-hero-custom">First opacity <output>%d%%</output><input type="range" name="%s[overlay_opacity]" value="%d" min="0" max="100"></label>', $background['overlay_opacity'], esc_attr($prefix), $background['overlay_opacity']);
		printf('<label class="sfs-hero-gradient">Second color<input type="color" name="%s[overlay_color2]" value="%s"></label>', esc_attr($prefix), esc_attr($background['overlay_color2']));
		printf('<label class="sfs-hero-gradient">Second opacity <output>%d%%</output><input type="range" name="%s[overlay_opacity2]" value="%d" min="0" max="100"></label>', $background['overlay_opacity2'], esc_attr($prefix), $background['overlay_opacity2']);
		foreach (array('overlay_angle' => 'Angle (°)', 'overlay_stop' => 'First stop (%)', 'overlay_stop2' => 'Second stop (%)') as $field => $label) {
			printf('<label class="sfs-hero-gradient">%s<input type="number" name="%s[%s]" value="%d" min="0" max="%d"></label>', esc_html($label), esc_attr($prefix), esc_attr($field), $background[$field], 'overlay_angle' === $field ? 360 : 100);
		}
		printf('<label class="sfs-hero-custom">Blend mode<select name="%s[overlay_blend]">', esc_attr($prefix));
		foreach (array('normal', 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'soft-light') as $mode) {
			printf('<option value="%s"%s>%s</option>', esc_attr($mode), selected($background['overlay_blend'], $mode, false), esc_html(ucwords(str_replace('-', ' ', $mode))));
		}
		echo '</select></label></div></fieldset></div></section>';
	}
	echo '<datalist id="sfs-hero-sizes"><option value="cover"><option value="contain"><option value="auto"><option value="100% auto"></datalist>';
	echo '</div>';
}

function wholesale_hero_save_values($kind, $id)
{
	if (!isset($_POST['sfs_hero_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sfs_hero_nonce'])), 'sfs_hero_save')) {
		return;
	}
	$set = 'term' === $kind ? 'update_term_meta' : 'update_post_meta';
	$delete = 'term' === $kind ? 'delete_term_meta' : 'delete_post_meta';

	$fields = 'term' === $kind ? array('desktop' => WHOLESALE_HERO_DESKTOP, 'mobile' => WHOLESALE_HERO_MOBILE) : array('mobile' => WHOLESALE_HERO_MOBILE);
	foreach ($fields as $field => $meta) {
		$value = isset($_POST['sfs_hero_' . $field]) ? wholesale_hero_valid_image(wp_unslash($_POST['sfs_hero_' . $field])) : 0;
		$value ? $set($id, $meta, $value) : $delete($id, $meta);
	}

	$overlay = isset($_POST['sfs_hero_overlay']) ? sanitize_key(wp_unslash($_POST['sfs_hero_overlay'])) : 'left';
	('left' !== $overlay && isset(wholesale_hero_overlays()[$overlay])) ? $set($id, WHOLESALE_HERO_OVERLAY, $overlay) : $delete($id, WHOLESALE_HERO_OVERLAY);

	$background = isset($_POST['sfs_hero_background']) && is_array($_POST['sfs_hero_background']) ? wp_unslash($_POST['sfs_hero_background']) : array();
	$clean = array();
	foreach (array('desktop', 'mobile') as $viewport) {
		$clean[$viewport] = wholesale_hero_background_clean($background[$viewport] ?? array());
	}
	$set($id, WHOLESALE_HERO_BACKGROUND, $clean);
	$delete($id, WHOLESALE_HERO_FOCUS);
	if ('post' === $kind) {
		$delete($id, WHOLESALE_HERO_DESKTOP);
	}
}

/* Pages */
function wholesale_hero_add_metabox()
{
	add_meta_box('sfs-hero-images', 'Hero images & background', function ($post) {
		wholesale_hero_field_html(wholesale_hero_read('post', $post->ID), 'post', $post->ID);
	}, 'page', 'normal', 'default');
}
add_action('add_meta_boxes_page', 'wholesale_hero_add_metabox');

function wholesale_hero_save_page($post_id)
{
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_page', $post_id)) {
		return;
	}
	wholesale_hero_save_values('post', $post_id);
}
add_action('save_post_page', 'wholesale_hero_save_page');

/* Product categories */
function wholesale_hero_term_add_form()
{
	echo '<div class="form-field"><label>Hero images</label>';
	wholesale_hero_field_html(wholesale_hero_read('term', 0), 'term');
	echo '</div>';
}
add_action('product_category_add_form_fields', 'wholesale_hero_term_add_form');

function wholesale_hero_term_edit_form($term)
{
	echo '<tr class="form-field"><th scope="row">Hero images</th><td>';
	wholesale_hero_field_html(wholesale_hero_read('term', $term->term_id), 'term');
	echo '<p class="description">Child categories use the parent category\'s hero photo until they get their own.</p></td></tr>';
}
add_action('product_category_edit_form_fields', 'wholesale_hero_term_edit_form');

function wholesale_hero_save_term($term_id)
{
	if (!current_user_can('manage_categories')) {
		return;
	}
	wholesale_hero_save_values('term', $term_id);
}
add_action('created_product_category', 'wholesale_hero_save_term');
add_action('edited_product_category', 'wholesale_hero_save_term');

/* Media picker script, only on the screens that have the fields. */
function wholesale_hero_admin_assets($hook)
{
	$screen = get_current_screen();
	if (!$screen || !in_array($screen->id, array('page', 'edit-product_category'), true)) {
		return;
	}
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	wp_enqueue_style('sfs-hero-admin', $theme_uri . '/css/hero-admin.css', array(), (string) filemtime($theme_dir . '/css/hero-admin.css'));
	wp_enqueue_script('sfs-hero-admin', $theme_uri . '/js/hero-admin.js', array('jquery'), (string) filemtime($theme_dir . '/js/hero-admin.js'), true);
	wp_enqueue_media();
	wp_add_inline_script('jquery-core', <<<'JS'
jQuery(function ($) {
	$(document).on('click', '.sfs-hero-pick', function (e) {
		e.preventDefault();
		var box = $(this).closest('.sfs-hero-img');
		var frame = wp.media({ title: 'Choose hero image', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			var u = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
			box.find('input[type=hidden]').val(a.id);
			box.find('.sfs-hero-prev').empty().append($('<img alt="">').attr('src', u).css({ maxWidth: '100%', height: 'auto', display: 'block' }));
			box.find('.sfs-hero-clear').show();
			box.find('input[type=hidden]').trigger('change');
		});
		frame.open();
	});
	$(document).on('click', '.sfs-hero-clear', function (e) {
		e.preventDefault();
		var box = $(this).closest('.sfs-hero-img');
		box.find('input[type=hidden]').val('');
		box.find('.sfs-hero-prev').empty();
		$(this).hide();
		box.find('input[type=hidden]').trigger('change');
	});
	// Add-category form is submitted by AJAX: clear the fields afterwards.
	$(document).ajaxSuccess(function (ev, xhr, opts) {
		if (opts && opts.data && String(opts.data).indexOf('action=add-tag') > -1) {
			$('.sfs-hero-img input[type=hidden]').val('');
			$('.sfs-hero-prev').empty();
			$('.sfs-hero-clear').hide();
		}
	});
});
JS
	);
}
add_action('admin_enqueue_scripts', 'wholesale_hero_admin_assets');
