<?php
/**
 * Design settings (Storefront Sign settings > Design, 2026-11-23): colors,
 * typography and logo, each defaulting to what the site showed before.
 *
 * Colors
 * The theme's CSS has about 530 different colors written out as hex/rgb
 * values (css/*.css, style.css and <style> blocks in the templates), so a
 * CSS variable per color would mean rewriting every stylesheet. Instead every
 * color is sorted into a family by its hue, saturation and lightness
 * (wholesale_design_color_role()): brand blue, dark navy, star yellow, sale
 * orange, success green, error red, and five grays plus white. Changing a
 * family's color moves every shade in it by the same hue, saturation and
 * lightness change (wholesale_design_shift_color()), so the darker hover blue,
 * the light blue tints and the blue shadows follow the new brand color and
 * keep their relation to it. The All colors table can also set any single
 * color directly; that wins over its family.
 *
 * Where the new colors are applied (front end only, wholesale_design_recolor_html()):
 * - Theme stylesheets: every css/*.css and style.css link on the page points
 *   to a recolored copy in uploads/wholesale-design/<hash>/ (made on first use,
 *   remade when the theme file changes, old sets removed after a save).
 * - <style> blocks (header.php, inline critical CSS, plugin widgets), style=""
 *   attributes, SVG fill/stroke attributes and <meta name="theme-color">.
 * Colors written by JavaScript at run time and colors inside image files keep
 * their own values.
 *
 * With every color at its default nothing is rewritten at all: no output
 * buffer, no copies, the pages are byte-for-byte what they were.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Color families. 'option' is the setting key (option wholesale_<key>);
 * primary_color and accent_color are the settings the theme already had.
 */
function wholesale_design_color_roles()
{
	return array(
		'brand' => array('option' => 'primary_color', 'default' => '#1fa8de', 'label' => 'Brand color', 'help' => 'Buttons, links, icons and price highlights, plus every lighter tint and darker hover shade of the brand blue.'),
		'navy' => array('option' => 'color_navy', 'default' => '#031835', 'label' => 'Dark brand color', 'help' => 'Dark navy areas: top bar, footer, dark hero overlays and dark cards.'),
		'star' => array('option' => 'accent_color', 'default' => '#f5ad27', 'label' => 'Star / accent color', 'help' => 'Star ratings and the yellow and amber highlights.'),
		'orange' => array('option' => 'color_orange', 'default' => '#e95402', 'label' => 'Sale & badge color', 'help' => 'Sale badges, promo strips and the orange accents.'),
		'green' => array('option' => 'color_green', 'default' => '#17b26a', 'label' => 'Success color', 'help' => 'In stock, success messages, check marks and free shipping notes.'),
		'red' => array('option' => 'color_red', 'default' => '#d64545', 'label' => 'Error color', 'help' => 'Errors, required marks and remove buttons.'),
		'text' => array('option' => 'color_text', 'default' => '#172027', 'label' => 'Heading & dark text', 'help' => 'Headings, the darkest text and the near-black shadows.'),
		'body' => array('option' => 'color_body', 'default' => '#334155', 'label' => 'Body text', 'help' => 'Paragraph text and the dark grays.'),
		'muted' => array('option' => 'color_muted', 'default' => '#64748b', 'label' => 'Muted text', 'help' => 'Small print, captions, placeholders and gray icons.'),
		'border' => array('option' => 'color_border', 'default' => '#e2e8f0', 'label' => 'Borders & lines', 'help' => 'Card borders, dividers and input outlines.'),
		'soft' => array('option' => 'color_soft', 'default' => '#f8fafc', 'label' => 'Light backgrounds', 'help' => 'Light gray section backgrounds and panels.'),
		'white' => array('option' => 'color_white', 'default' => '#ffffff', 'label' => 'White', 'help' => 'Page and card backgrounds, and white text on buttons and dark areas.'),
	);
}

/**
 * Fonts offered for text and headings. 'google' fonts load from Google Fonts
 * only when chosen; the two theme fonts are self-hosted (css/typography.css).
 */
function wholesale_design_fonts()
{
	$sans = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
	return array(
		'inter' => array('label' => 'Inter (theme font)', 'stack' => '"Inter", ' . $sans),
		'plus-jakarta-sans' => array('label' => 'Plus Jakarta Sans (theme font)', 'stack' => '"Plus Jakarta Sans", "Inter", ' . $sans),
		'system' => array('label' => 'Device font (no download)', 'stack' => $sans),
		'georgia' => array('label' => 'Georgia, serif (no download)', 'stack' => 'Georgia, "Times New Roman", Times, serif'),
		'roboto' => array('label' => 'Roboto', 'stack' => '"Roboto", ' . $sans, 'google' => 'Roboto:wght@400;500;700;900'),
		'open-sans' => array('label' => 'Open Sans', 'stack' => '"Open Sans", ' . $sans, 'google' => 'Open+Sans:wght@400;500;600;700;800'),
		'lato' => array('label' => 'Lato', 'stack' => '"Lato", ' . $sans, 'google' => 'Lato:wght@400;700;900'),
		'montserrat' => array('label' => 'Montserrat', 'stack' => '"Montserrat", ' . $sans, 'google' => 'Montserrat:wght@400;500;600;700;800'),
		'poppins' => array('label' => 'Poppins', 'stack' => '"Poppins", ' . $sans, 'google' => 'Poppins:wght@400;500;600;700;800'),
		'nunito-sans' => array('label' => 'Nunito Sans', 'stack' => '"Nunito Sans", ' . $sans, 'google' => 'Nunito+Sans:wght@400;600;700;800'),
		'source-sans-3' => array('label' => 'Source Sans 3', 'stack' => '"Source Sans 3", ' . $sans, 'google' => 'Source+Sans+3:wght@400;600;700;800'),
		'work-sans' => array('label' => 'Work Sans', 'stack' => '"Work Sans", ' . $sans, 'google' => 'Work+Sans:wght@400;500;600;700;800'),
		'dm-sans' => array('label' => 'DM Sans', 'stack' => '"DM Sans", ' . $sans, 'google' => 'DM+Sans:wght@400;500;600;700;800'),
		'manrope' => array('label' => 'Manrope', 'stack' => '"Manrope", ' . $sans, 'google' => 'Manrope:wght@400;500;600;700;800'),
		'outfit' => array('label' => 'Outfit', 'stack' => '"Outfit", ' . $sans, 'google' => 'Outfit:wght@400;500;600;700;800'),
		'raleway' => array('label' => 'Raleway', 'stack' => '"Raleway", ' . $sans, 'google' => 'Raleway:wght@400;500;600;700;800'),
		'oswald' => array('label' => 'Oswald (condensed)', 'stack' => '"Oswald", "Arial Narrow", ' . $sans, 'google' => 'Oswald:wght@400;500;600;700'),
		'playfair-display' => array('label' => 'Playfair Display (serif)', 'stack' => '"Playfair Display", Georgia, serif', 'google' => 'Playfair+Display:wght@400;500;600;700;800'),
		'merriweather' => array('label' => 'Merriweather (serif)', 'stack' => '"Merriweather", Georgia, serif', 'google' => 'Merriweather:wght@400;700;900'),
	);
}

/** The header and footer tagline drawn as text under the logo (inc/logo-motion.php). */
define('WHOLESALE_DESIGN_TAGLINE', 'CHANNEL LETTERS & LARGE FORMAT PRINTS SINCE 2002');

/**
 * Defaults for the design settings, added to wholesale_setting_defaults().
 * Every value is what the theme used before these settings existed.
 */
function wholesale_design_setting_defaults($defaults)
{
	foreach (wholesale_design_color_roles() as $role) {
		if (!isset($defaults[$role['option']])) {
			$defaults[$role['option']] = $role['default'];
		}
	}
	return array_merge($defaults, array(
		// JSON object {"#old": "#new"} from the All colors table.
		'color_overrides' => '',
		// Professional palette (2026-11-23): one 11-step scale per family, and
		// text/buttons below WCAG AA contrast moved to a darker step of their color.
		'palette_tidy' => 1,
		'contrast_fix' => 1,
		'font_body' => 'inter',
		'font_heading' => 'plus-jakarta-sans',
		'font_size_base' => '16',
		'line_height' => '1.6',
		'heading_weight' => '700',
		'h1_weight' => '800',
		// 'brand' = the built-in animated brand artwork (img/brand/), 'site' = the Site logo image.
		// Wholesale: the img/brand artwork is the retail "StorefrontSign online.com" logo, so the
		// Site logo (the wholesale logo) is the default here.
		'logo_style' => 'site',
		'logo_animation' => 1,
		'logo_width_desktop' => '396',
		'logo_width_mobile' => '376',
		'footer_logo' => 0,
		'footer_logo_width' => '340',
		'logo_tagline' => WHOLESALE_DESIGN_TAGLINE,
	));
}
add_filter('wholesale_setting_defaults', 'wholesale_design_setting_defaults');

function wholesale_design_setting_sanitizer($callback, $key)
{
	foreach (wholesale_design_color_roles() as $role) {
		if ($role['option'] === $key) {
			$default = $role['default'];
			return static function ($value) use ($default) {
				$value = sanitize_hex_color(trim((string) $value));
				return $value ? strtolower($value) : $default;
			};
		}
	}
	$numbers = array(
		'font_size_base' => array(12, 22, 16, 1),
		'line_height' => array(1, 2.4, 1.6, 0.05),
		'heading_weight' => array(400, 900, 700, 50),
		'h1_weight' => array(400, 900, 800, 50),
		'logo_width_desktop' => array(120, 700, 396, 1),
		'logo_width_mobile' => array(100, 500, 376, 1),
		'footer_logo_width' => array(100, 600, 340, 1),
	);
	if (isset($numbers[$key])) {
		$range = $numbers[$key];
		return static function ($value) use ($range) {
			if (!is_numeric($value)) {
				return (string) $range[2];
			}
			$value = max($range[0], min($range[1], (float) $value));
			$value = round($value / $range[3]) * $range[3];
			return (string) (0.05 === $range[3] ? round($value, 2) : (int) $value);
		};
	}
	switch ($key) {
		case 'color_overrides':
			return 'wholesale_design_sanitize_overrides';
		case 'font_body':
		case 'font_heading':
			$fallback = 'font_body' === $key ? 'inter' : 'plus-jakarta-sans';
			return static function ($value) use ($fallback) {
				$value = sanitize_key($value);
				return isset(wholesale_design_fonts()[$value]) ? $value : $fallback;
			};
		case 'logo_style':
			return static function ($value) {
				return 'site' === $value ? 'site' : 'brand';
			};
		case 'logo_animation':
		case 'palette_tidy':
		case 'contrast_fix':
			return 'wholesale_sanitize_page_loader_enabled';
		case 'footer_logo':
			return 'wholesale_sanitize_image_id';
		case 'logo_tagline':
			return static function ($value) {
				return trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $value)));
			};
	}
	return $callback;
}
add_filter('wholesale_setting_sanitizer', 'wholesale_design_setting_sanitizer', 10, 2);

/**
 * The All colors overrides as a clean JSON object of 6-digit lowercase hex
 * pairs; pairs that change nothing are dropped.
 */
function wholesale_design_sanitize_overrides($value)
{
	$data = is_array($value) ? $value : json_decode(wp_unslash((string) $value), true);
	$clean = array();
	if (is_array($data)) {
		foreach ($data as $from => $to) {
			$from = wholesale_design_hex6($from);
			$to = wholesale_design_hex6($to);
			if ($from && $to && $from !== $to) {
				$clean[$from] = $to;
			}
		}
	}
	ksort($clean);
	return $clean ? wp_json_encode($clean) : '';
}

/**
 * Site icon (favicon and home screen icon): the settings page saves it
 * through wholesale_site_icon, which is copied into WordPress's own site_icon.
 */
function wholesale_design_register_settings()
{
	register_setting('wholesale_settings', 'wholesale_site_icon', array(
		'type' => 'integer',
		'sanitize_callback' => 'wholesale_design_sanitize_site_icon',
	));
}
add_action('admin_init', 'wholesale_design_register_settings');

function wholesale_design_sanitize_site_icon($value)
{
	if (null === $value || '' === $value) {
		return (int) get_option('site_icon');
	}
	$id = absint($value);
	$id = $id && wp_attachment_is_image($id) ? $id : 0;
	if ((int) get_option('site_icon') !== $id) {
		update_option('site_icon', $id);
	}
	return $id;
}

/* ------------------------------------------------------------------------
 * Color math
 * --------------------------------------------------------------------- */

/** '#ABC', 'abc', '#aabbcc' -> 'aabbcc', or '' when it is not a hex color. */
function wholesale_design_hex6($value)
{
	$value = strtolower(ltrim(trim((string) $value), '#'));
	if (preg_match('/^[0-9a-f]{3}$/', $value)) {
		return $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
	}
	return preg_match('/^[0-9a-f]{6}$/', $value) ? $value : '';
}

/** 'aabbcc' -> array(h 0-360, s 0-1, l 0-1). */
function wholesale_design_hsl($hex)
{
	$r = hexdec(substr($hex, 0, 2)) / 255;
	$g = hexdec(substr($hex, 2, 2)) / 255;
	$b = hexdec(substr($hex, 4, 2)) / 255;
	$max = max($r, $g, $b);
	$min = min($r, $g, $b);
	$l = ($max + $min) / 2;
	if ($max === $min) {
		return array(0.0, 0.0, $l);
	}
	$d = $max - $min;
	$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
	if ($max === $r) {
		$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
	} elseif ($max === $g) {
		$h = ($b - $r) / $d + 2;
	} else {
		$h = ($r - $g) / $d + 4;
	}
	return array($h * 60, $s, $l);
}

/** HSL -> 'aabbcc'. */
function wholesale_design_hsl_hex($h, $s, $l)
{
	$h = fmod(fmod($h, 360) + 360, 360) / 360;
	$s = max(0, min(1, $s));
	$l = max(0, min(1, $l));
	if (0.0 === (float) $s) {
		$r = $g = $b = $l;
	} else {
		$q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
		$p = 2 * $l - $q;
		$channel = static function ($t) use ($p, $q) {
			$t = $t < 0 ? $t + 1 : ($t > 1 ? $t - 1 : $t);
			if ($t < 1 / 6) {
				return $p + ($q - $p) * 6 * $t;
			}
			if ($t < 1 / 2) {
				return $q;
			}
			if ($t < 2 / 3) {
				return $p + ($q - $p) * (2 / 3 - $t) * 6;
			}
			return $p;
		};
		$r = $channel($h + 1 / 3);
		$g = $channel($h);
		$b = $channel($h - 1 / 3);
	}
	return sprintf('%02x%02x%02x', (int) round($r * 255), (int) round($g * 255), (int) round($b * 255));
}

/**
 * Which family a color belongs to, or '' (pure black and the colors outside
 * every family, such as Google blue or purple, change only in All colors).
 * Mirrored in js/admin-design.js; keep the two the same.
 */
function wholesale_design_color_role($hex)
{
	list($h, $s, $l) = wholesale_design_hsl($hex);
	if ($l >= 0.995) {
		return 'white';
	}
	if ($l <= 0.005) {
		return '';
	}
	$neutral = $s < 0.30 || ($l >= 0.85 && $s < 0.48) || ($l < 0.2 && $s < 0.55) || ($l < 0.35 && $s < 0.40);
	if ($neutral) {
		if ($l < 0.22) {
			return 'text';
		}
		if ($l < 0.40) {
			return 'body';
		}
		if ($l < 0.70) {
			return 'muted';
		}
		return $l < 0.94 ? 'border' : 'soft';
	}
	if ($h >= 185 && $h < 206) {
		return 'brand';
	}
	if ($h >= 206 && $h < 235 && $l < 0.35) {
		return 'navy';
	}
	if ($h >= 10 && $h < 35) {
		return 'orange';
	}
	if ($h >= 35 && $h < 52) {
		return 'star';
	}
	if ($h >= 90 && $h < 175) {
		return 'green';
	}
	if ($h >= 340 || $h < 10) {
		return 'red';
	}
	return '';
}

/**
 * Map x from the 0..base..1 scale onto 0..target..1: values below the base
 * scale with it, values above keep their distance to 1 in proportion. A tint
 * near white stays near white, a shade near black stays near black.
 */
function wholesale_design_rescale($x, $base, $target)
{
	if ($x <= $base) {
		return $base > 0 ? $x * $target / $base : $target;
	}
	return $base < 1 ? 1 - (1 - $x) * (1 - $target) / (1 - $base) : $target;
}

/** A family member moved by the same change as its family color (base -> target). */
function wholesale_design_shift_color($hex, $base, $target)
{
	if ($base === $target) {
		return $hex;
	}
	list($h, $s, $l) = wholesale_design_hsl($hex);
	list($bh, $bs, $bl) = wholesale_design_hsl($base);
	list($th, $ts, $tl) = wholesale_design_hsl($target);
	if ($hex === $base) {
		return $target;
	}
	// Gray bases have no hue: give the family the target's hue.
	$hue = $bs < 0.02 ? $th : $h + ($th - $bh);
	return wholesale_design_hsl_hex($hue, wholesale_design_rescale($s, $bs, $ts), wholesale_design_rescale($l, $bl, $tl));
}

/** Current color of each family: role => 'aabbcc'. */
function wholesale_design_role_values()
{
	static $values = null;
	if (null === $values) {
		$values = array();
		foreach (wholesale_design_color_roles() as $key => $role) {
			$value = wholesale_design_hex6(wholesale_get_setting($role['option']));
			$values[$key] = $value ? $value : wholesale_design_hex6($role['default']);
		}
	}
	return $values;
}

/** All colors overrides: 'aabbcc' => 'aabbcc'. */
function wholesale_design_overrides()
{
	static $overrides = null;
	if (null === $overrides) {
		$overrides = array();
		$data = json_decode((string) wholesale_get_setting('color_overrides'), true);
		if (is_array($data)) {
			foreach ($data as $from => $to) {
				$from = wholesale_design_hex6($from);
				$to = wholesale_design_hex6($to);
				if ($from && $to && $from !== $to) {
					$overrides[$from] = $to;
				}
			}
		}
	}
	return $overrides;
}

/** Families whose color differs from the default: role => array(base, target). */
function wholesale_design_changed_roles()
{
	static $changed = null;
	if (null === $changed) {
		$changed = array();
		$values = wholesale_design_role_values();
		foreach (wholesale_design_color_roles() as $key => $role) {
			$base = wholesale_design_hex6($role['default']);
			if ($values[$key] !== $base) {
				$changed[$key] = array($base, $values[$key]);
			}
		}
	}
	return $changed;
}

/** Professional palette switches (Design > Colors), both on by default. */
function wholesale_design_palette_flags()
{
	return array(
		'tidy' => wholesale_setting_enabled('palette_tidy'),
		'contrast' => wholesale_setting_enabled('contrast_fix'),
	);
}

/** True when the pages need rewriting: a palette switch is on or a color was changed. */
function wholesale_design_colors_active()
{
	$flags = wholesale_design_palette_flags();
	return (bool) ($flags['tidy'] || $flags['contrast'] || wholesale_design_changed_roles() || wholesale_design_overrides());
}

/** Short fingerprint of the color settings, for the stylesheet copies' folder. */
function wholesale_design_colors_hash()
{
	return substr(md5(wp_json_encode(array(wholesale_design_changed_roles(), wholesale_design_overrides(), wholesale_design_palette_flags(), WHOLESALE_DESIGN_PALETTE_VERSION))), 0, 10);
}

/** Bump when the scales or the contrast rules change, so stylesheet copies are remade. */
define('WHOLESALE_DESIGN_PALETTE_VERSION', 9);

/** Step names of every scale, lightest first. */
function wholesale_design_scale_steps()
{
	return array(50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950);
}

/**
 * The palette scale of a family, lightest (50) to darkest (950), from the
 * family's DEFAULT color. The five gray families share one slate scale (the
 * grays the theme already used most); the colored families get a scale built
 * around their own color, which stays exactly at its step.
 * Mirrored in js/admin-design.js (it receives these from the page).
 */
function wholesale_design_scale($role)
{
	static $scales = array();
	if (isset($scales[$role])) {
		return $scales[$role];
	}
	if (in_array($role, array('text', 'body', 'muted', 'border', 'soft'), true)) {
		return $scales[$role] = array('f8fafc', 'f1f5f9', 'e2e8f0', 'cbd5e1', '94a3b8', '64748b', '475569', '334155', '1e293b', '0f172a', '020617');
	}
	if ('white' === $role) {
		return $scales[$role] = array('ffffff');
	}
	$roles = wholesale_design_color_roles();
	if (!isset($roles[$role])) {
		return $scales[$role] = array();
	}
	$base = wholesale_design_hex6($roles[$role]['default']);
	list($h, $s, $l) = wholesale_design_hsl($base);
	// Lightness of each step in a typical UI scale.
	$ladder = array(0.97, 0.935, 0.87, 0.77, 0.64, 0.52, 0.42, 0.34, 0.27, 0.2, 0.13);
	$k = 0;
	foreach ($ladder as $i => $step) {
		if (abs($step - $l) < abs($ladder[$k] - $l)) {
			$k = $i;
		}
	}
	$delta = $l - $ladder[$k];
	$out = array();
	foreach ($ladder as $i => $step) {
		if ($i === $k) {
			$out[] = $base;
			continue;
		}
		$weight = max(0, 1 - abs($i - $k) / 4);
		// Very light tints and very dark shades carry a little less color.
		$sat = $s * ($step > 0.9 ? 0.85 : ($step < 0.2 ? 0.9 : 1));
		$out[] = wholesale_design_hsl_hex($h, $sat, max(0.02, min(0.985, $step + $delta * $weight)));
	}
	return $scales[$role] = $out;
}

/** The scale as it is now: the default scale moved by the family's color change. */
function wholesale_design_current_scale($role)
{
	$scale = wholesale_design_scale($role);
	$changed = wholesale_design_changed_roles();
	if (!isset($changed[$role])) {
		return $scale;
	}
	return array_map(static function ($hex) use ($changed, $role) {
		return wholesale_design_shift_color($hex, $changed[$role][0], $changed[$role][1]);
	}, $scale);
}

/** The scale step nearest to a color (by lightness). */
function wholesale_design_snap($hex, $role)
{
	$scale = wholesale_design_scale($role);
	if (!$scale) {
		return $hex;
	}
	$l = wholesale_design_hsl($hex)[2];
	$best = $scale[0];
	$gap = 2;
	foreach ($scale as $step) {
		$d = abs(wholesale_design_hsl($step)[2] - $l);
		if ($d < $gap) {
			$gap = $d;
			$best = $step;
		}
	}
	return $best;
}

/** The color a theme color becomes ('aabbcc'), or '' when it stays. */
function wholesale_design_map_color($hex)
{
	static $memo = array();
	if (isset($memo[$hex])) {
		return $memo[$hex];
	}
	$overrides = wholesale_design_overrides();
	if (isset($overrides[$hex])) {
		return $memo[$hex] = $overrides[$hex];
	}
	$role = wholesale_design_color_role($hex);
	if (!$role) {
		return $memo[$hex] = '';
	}
	$new = wholesale_design_palette_flags()['tidy'] ? wholesale_design_snap($hex, $role) : $hex;
	$changed = wholesale_design_changed_roles();
	if (isset($changed[$role])) {
		$new = wholesale_design_shift_color($new, $changed[$role][0], $changed[$role][1]);
	}
	return $memo[$hex] = ($new !== $hex ? $new : '');
}

/* ------------------------------------------------------------------------
 * Contrast (WCAG AA): text 4.5:1, icons and large text 3:1
 * --------------------------------------------------------------------- */

function wholesale_design_luminance($hex)
{
	$c = array();
	foreach (array(0, 2, 4) as $i) {
		$v = hexdec(substr($hex, $i, 2)) / 255;
		$c[] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
	}
	return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function wholesale_design_contrast($a, $b)
{
	$x = wholesale_design_luminance($a);
	$y = wholesale_design_luminance($b);
	return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

/**
 * The nearest step of the color's own family that reaches $need against
 * $against, going darker ($darker) or lighter; '' when none does.
 */
function wholesale_design_contrast_step($hex, $against, $need, $darker)
{
	$role = wholesale_design_color_role($hex);
	$scale = $role ? wholesale_design_current_scale($role) : array();
	if ('white' === $role || !$scale) {
		return '';
	}
	$l = wholesale_design_luminance($hex);
	$steps = $darker ? $scale : array_reverse($scale);
	foreach ($steps as $step) {
		$sl = wholesale_design_luminance($step);
		if (($darker ? $sl < $l : $sl > $l) && wholesale_design_contrast($step, $against) >= $need) {
			return $step;
		}
	}
	return '';
}

/**
 * First solid color in a CSS value: array(hex, token) or null. Values with
 * var(), gradients or images count as unknown (false).
 */
function wholesale_design_first_color($value)
{
	// var(--x) defined in the same stylesheet (wholesale_design_recolor_css() collects them).
	if (preg_match('/^\s*(var\(\s*(--[\w-]+)\s*(?:,[^)]*)?\))\s*(?:!important)?\s*$/i', $value, $m)) {
		$vars = $GLOBALS['wholesale_design_css_vars'] ?? array();
		return isset($vars[strtolower($m[2])]) ? array($vars[strtolower($m[2])], $m[1]) : false;
	}
	if (preg_match('/var\(|gradient|url\(|currentcolor|inherit|transparent/i', $value)) {
		return false;
	}
	if (preg_match('/#([0-9a-f]{6}|[0-9a-f]{3})(?![0-9a-z_-])/i', $value, $m)) {
		return array(wholesale_design_hex6($m[1]), $m[0]);
	}
	if (preg_match('/\b(?:white|#fff)\b/i', $value, $m)) {
		return array('ffffff', $m[0]);
	}
	if (preg_match('/rgba?\(\s*(\d{1,3})(?:\s*,\s*|\s+)(\d{1,3})(?:\s*,\s*|\s+)(\d{1,3})\s*(?:[,\/]\s*([0-9.]+)(%?)\s*)?\)/i', $value, $m)) {
		$alpha = isset($m[4]) && '' !== $m[4] ? (float) $m[4] / (!empty($m[5]) ? 100 : 1) : 1;
		return $alpha >= 0.99 ? array(sprintf('%02x%02x%02x', min(255, $m[1]), min(255, $m[2]), min(255, $m[3])), $m[0]) : false;
	}
	return null;
}

/**
 * Raise text contrast in one declaration block ($sel is its selector, '' for
 * a style attribute). Only colors of the theme's families move, and only to
 * another step of the same family, so the palette stays the palette:
 * - text and background in the same block: the text moves (dark text on a
 *   light background goes darker; light text on a colored button makes the
 *   button darker);
 * - text without a background in the block: checked against the light card
 *   tint (#f3f7fa) unless the selector is a dark area (footer, hero, top bar);
 * - a button-like block with a colored background and no text color: its
 *   text is white, so the background goes dark enough for white text.
 */
function wholesale_design_fix_contrast($body, $sel)
{
	$sel = trim((string) preg_replace('#/\*.*?\*/#s', '', $sel));
	if (false !== stripos($sel, ':disabled') || false !== stripos($sel, '[disabled]')) {
		return $body;
	}
	// Text color variables (--bs-link-color, --bs-link-color-rgb, --shop-muted...):
	// links and text read them, so they must be readable on light cards.
	if (false !== strpos($body, '--') && !preg_match('/footer|\.sf-|dark|navy|hero/i', $sel)) {
		$out = preg_replace_callback('/(--[\w-]*(?:link|text|muted|ink)[\w-]*\s*:\s*)(#[0-9a-f]{3,6}\b|(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3}))/i', static function ($m) {
			$rgb = !empty($m[3]) || (isset($m[3]) && '0' === $m[3]);
			$hex = $rgb ? sprintf('%02x%02x%02x', min(255, $m[3]), min(255, $m[4]), min(255, $m[5])) : wholesale_design_hex6($m[2]);
			if (!$hex || wholesale_design_luminance($hex) > 0.5 || wholesale_design_contrast($hex, 'f3f7fa') >= 4.5) {
				return $m[0];
			}
			$new = wholesale_design_contrast_step($hex, 'f3f7fa', 4.5, true);
			if (!$new) {
				return $m[0];
			}
			return $m[1] . ($rgb ? hexdec(substr($new, 0, 2)) . ', ' . hexdec(substr($new, 2, 2)) . ', ' . hexdec(substr($new, 4, 2)) : '#' . $new);
		}, $body);
		$body = null === $out ? $body : $out;
	}
	$has_color = preg_match('/(?<![\w-])color\s*:\s*([^;}]+)/i', $body, $cm, PREG_OFFSET_CAPTURE);
	$has_bg = preg_match('/(?<![\w-])background(?:-color)?\s*:\s*([^;}]+)/i', $body, $bm, PREG_OFFSET_CAPTURE);
	if (!$has_color && !$has_bg) {
		return $body;
	}
	$fg = $has_color ? wholesale_design_first_color($cm[1][0]) : null;
	$bg = $has_bg ? wholesale_design_first_color($bm[1][0]) : null;
	$graphic = (bool) preg_match('/icon|::?before|::?after|svg|\bstars?\b|rating|arrow|chevron|\bi\b|\.fa-|\bdots?\b|bullet|checkmark|\bcheck\b/i', $sel);
	$need = $graphic ? 3.0 : 4.5;
	// Hover/focus states aim higher so they stay one step darker than the normal state.
	$hover = (bool) preg_match('/:hover|:focus|:active|\.active\b/i', $sel);
	$dark_area = (bool) preg_match('/footer|\.sf-|dark|navy|hero|top-?bar|ssx-top|ssxm|loader|overlay|inverse|on-dark|-band\b|cta-band/i', $sel);
	$swap = static function ($body, $match, $old_token, $new_hex) {
		$value = $match[0];
		$new_value = preg_replace('/' . preg_quote($old_token, '/') . '/', '#' . $new_hex, $value, 1);
		return substr_replace($body, $new_value, $match[1], strlen($value));
	};

	if ($fg && $bg) {
		$ratio = wholesale_design_contrast($fg[0], $bg[0]);
		if ($ratio >= $need || $ratio < 1.15) {
			return $body;
		}
		if (wholesale_design_luminance($fg[0]) > wholesale_design_luminance($bg[0])) {
			// Light text on a darker fill (a button): darken the fill; on a fill
			// that is already as dark as it goes (navy), lighten the text instead.
			$new = wholesale_design_contrast_step($bg[0], $fg[0], $hover ? max($need, 6) : $need, true);
			$new = $new ? $new : ($hover ? wholesale_design_contrast_step($bg[0], $fg[0], $need, true) : '');
			if ($new) {
				return $swap($body, $bm[1], $bg[1], $new);
			}
			$new = wholesale_design_contrast_step($fg[0], $bg[0], $need, false);
			return $new ? $swap($body, $cm[1], $fg[1], $new) : $body;
		}
		$new = wholesale_design_contrast_step($fg[0], $bg[0], $need, true);
		return $new ? $swap($body, $cm[1], $fg[1], $new) : $body;
	}

	if ($fg && !$has_bg && !$dark_area) {
		$role = wholesale_design_color_role($fg[0]);
		if (!$role || in_array($role, array('white', 'star', 'soft', 'border'), true) || wholesale_design_luminance($fg[0]) > 0.5) {
			return $body;
		}
		if (wholesale_design_contrast($fg[0], 'f3f7fa') >= $need) {
			return $body;
		}
		$new = wholesale_design_contrast_step($fg[0], 'f3f7fa', $need, true);
		return $new ? $swap($body, $cm[1], $fg[1], $new) : $body;
	}

	if ($bg && !$has_color && preg_match('/btn|button|cta|submit|add-to-cart|badge|chip|flag|pill|primary|action|pay\b|__cart|cart-btn|buy/i', $sel)) {
		$role = wholesale_design_color_role($bg[0]);
		if (!in_array($role, array('brand', 'orange', 'green', 'red'), true) || wholesale_design_luminance($bg[0]) > 0.45) {
			return $body;
		}
		if (wholesale_design_contrast($bg[0], 'ffffff') >= 4.5) {
			return $body;
		}
		$new = wholesale_design_contrast_step($bg[0], 'ffffff', $hover ? 6 : 4.5, true);
		$new = $new ? $new : wholesale_design_contrast_step($bg[0], 'ffffff', 4.5, true);
		return $new ? $swap($body, $bm[1], $bg[1], $new) : $body;
	}
	return $body;
}

/* ------------------------------------------------------------------------
 * Rewriting CSS and HTML
 * --------------------------------------------------------------------- */

/**
 * Recolor the colors in CSS declarations or attribute values: #rgb, #rgba,
 * #rrggbb, #rrggbbaa, %23rrggbb (inside data: SVG URLs) and rgb()/rgba(),
 * keeping each one's format and alpha.
 */
function wholesale_design_recolor_value($text)
{
	if ('' === $text || (false === strpos($text, '#') && false === stripos($text, 'rgb') && false === stripos($text, '%23'))) {
		return $text;
	}
	$out = preg_replace_callback(
		// (?<!&): "&#039;" and "&#8217;" in style="" are HTML entities, not colors
		// (recoloring &#039; to &#0949a2; broke the home hero's url('...')).
		'/(?<!&)(#|%23)([0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{4}|[0-9a-f]{3})(?![0-9a-z_-])|\b(rgba?)\(\s*(\d{1,3})(\s*,\s*|\s+)(\d{1,3})(\s*,\s*|\s+)(\d{1,3})(\s*(?:[,\/]\s*[0-9.]+%?\s*)?)\)/i',
		'wholesale_design_recolor_match',
		$text
	);
	$text = null === $out ? $text : $out;
	// "--bs-primary-rgb: 31, 168, 222" (used as rgba(var(--bs-primary-rgb), .5)).
	if (false !== stripos($text, '-rgb')) {
		$out = preg_replace_callback('/(--[a-z0-9_-]*-rgb\s*:\s*)(\d{1,3})(\s*,\s*)(\d{1,3})(\s*,\s*)(\d{1,3})(?![\d.])/i', static function ($m) {
			if ($m[2] > 255 || $m[4] > 255 || $m[6] > 255) {
				return $m[0];
			}
			$new = wholesale_design_map_color(sprintf('%02x%02x%02x', $m[2], $m[4], $m[6]));
			return $new ? $m[1] . hexdec(substr($new, 0, 2)) . $m[3] . hexdec(substr($new, 2, 2)) . $m[5] . hexdec(substr($new, 4, 2)) : $m[0];
		}, $text);
		$text = null === $out ? $text : $out;
	}
	return $text;
}

function wholesale_design_recolor_match($m)
{
	if ('' !== $m[1]) {
		$digits = strtolower($m[2]);
		$len = strlen($digits);
		$alpha = '';
		if (8 === $len || 4 === $len) {
			$alpha = 8 === $len ? substr($digits, 6) : str_repeat($digits[3], 2);
			$digits = 8 === $len ? substr($digits, 0, 6) : substr($digits, 0, 3);
		}
		if ('%23' === $m[1] && $alpha) {
			return $m[0];
		}
		$new = wholesale_design_map_color(wholesale_design_hex6($digits));
		return $new ? $m[1] . $new . $alpha : $m[0];
	}
	if ($m[4] > 255 || $m[6] > 255 || $m[8] > 255) {
		return $m[0];
	}
	$new = wholesale_design_map_color(sprintf('%02x%02x%02x', $m[4], $m[6], $m[8]));
	if (!$new) {
		return $m[0];
	}
	return $m[3] . '(' . hexdec(substr($new, 0, 2)) . $m[5] . hexdec(substr($new, 2, 2)) . $m[7] . hexdec(substr($new, 4, 2)) . $m[9] . ')';
}

/**
 * Recolor a stylesheet: only the text inside declaration blocks { ... }, so
 * an ID selector that happens to look like a color (#add, #decade) is safe.
 */
function wholesale_design_recolor_css($css, $recolor = true)
{
	$contrast = wholesale_design_palette_flags()['contrast'];
	if ($contrast) {
		// Solid color variables of this stylesheet, after recoloring, for var() lookups.
		$vars = array();
		$source = $recolor ? wholesale_design_recolor_value($css) : $css;
		if (preg_match_all('/(--[\w-]+)\s*:\s*(#[0-9a-f]{6}|#[0-9a-f]{3})\s*[;}]/i', $source, $vm, PREG_SET_ORDER)) {
			foreach ($vm as $v) {
				$name = strtolower($v[1]);
				// A name set to two different colors in one file is ambiguous: skip it.
				$hex = wholesale_design_hex6($v[2]);
				$vars[$name] = isset($vars[$name]) && $vars[$name] !== $hex ? '' : $hex;
			}
		}
		$GLOBALS['wholesale_design_css_vars'] = array_filter($vars);
	}
	$out = preg_replace_callback('/([^{}]*)\{([^{}]*)\}/', static function ($m) use ($recolor, $contrast) {
		$body = $recolor ? wholesale_design_recolor_value($m[2]) : $m[2];
		if ($contrast && '' === trim($m[1]) ? false : $contrast) {
			$body = wholesale_design_fix_contrast($body, $m[1]);
		}
		return $m[1] . '{' . $body . '}';
	}, $css);
	return null === $out ? $css : $out;
}

/**
 * A stylesheet moved to uploads keeps working: its relative url(...) values
 * become absolute URLs of the theme folder it came from.
 */
function wholesale_design_absolutize_css_urls($css, $base_url)
{
	$out = preg_replace_callback('/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', static function ($m) use ($base_url) {
		$url = trim($m[2]);
		if ('' === $url || preg_match('#^(data:|https?:|//|/|\#|about:)#i', $url)) {
			return $m[0];
		}
		return 'url(' . $m[1] . $base_url . $url . $m[1] . ')';
	}, $css);
	return null === $out ? $css : $out;
}

/**
 * URL of the recolored copy of a theme stylesheet ('css/shop.css'), made
 * now if needed, or '' to keep the original.
 */
function wholesale_design_css_copy_url($rel)
{
	static $done = array();
	if (isset($done[$rel])) {
		return $done[$rel];
	}
	$done[$rel] = '';
	$rel = ltrim(str_replace('\\', '/', $rel), '/');
	if (false !== strpos($rel, '..') || !preg_match('#^[A-Za-z0-9_./-]+\.css$#', $rel)) {
		return '';
	}
	$src = get_template_directory() . '/' . $rel;
	if (!is_file($src) || !is_readable($src)) {
		return '';
	}
	$uploads = wp_upload_dir(null, false);
	if (!empty($uploads['error'])) {
		return '';
	}
	$copy = 'wholesale-design/' . wholesale_design_colors_hash() . '/' . substr($rel, 0, -4) . '-' . filemtime($src) . '.css';
	$path = $uploads['basedir'] . '/' . $copy;
	if (!is_file($path)) {
		$css = (string) file_get_contents($src);
		$dir = dirname($rel);
		$base = get_template_directory_uri() . '/' . ('.' === $dir ? '' : $dir . '/');
		$css = wholesale_design_recolor_css(wholesale_design_absolutize_css_urls($css, $base));
		if (!wp_mkdir_p(dirname($path))) {
			return '';
		}
		$tmp = $path . '.' . getmypid() . '.tmp';
		if (false === file_put_contents($tmp, $css) || !@rename($tmp, $path)) {
			@unlink($tmp);
			if (!is_file($path)) {
				return '';
			}
		}
	}
	return $done[$rel] = $uploads['baseurl'] . '/' . $copy;
}

/** Point every theme stylesheet URL in the page at its recolored copy. */
function wholesale_design_swap_css_urls($html)
{
	$uri = get_template_directory_uri();
	foreach (array($uri, str_replace('/', '\/', $uri)) as $i => $base) {
		$slash = $i ? '\\\\\/' : '\/';
		$pattern = '#' . preg_quote($base, '#') . $slash . '((?:[A-Za-z0-9_.-]+' . $slash . ')*[A-Za-z0-9_.-]+\.css)(?![A-Za-z0-9_])#';
		$out = preg_replace_callback($pattern, static function ($m) use ($base, $i) {
			$rel = $i ? str_replace('\/', '/', $m[1]) : $m[1];
			$copy = wholesale_design_css_copy_url($rel);
			if (!$copy) {
				return $m[0];
			}
			return $i ? str_replace('/', '\/', $copy) : $copy;
		}, $html);
		$html = null === $out ? $html : $out;
	}
	return $html;
}

/** Output buffer callback: the page with the new colors. */
function wholesale_design_recolor_html($html)
{
	if (!is_string($html) || '' === $html || false === stripos($html, '<html')) {
		return $html;
	}
	foreach (headers_list() as $header) {
		if (0 === stripos($header, 'content-type:') && false === stripos($header, 'html')) {
			return $html;
		}
	}

	$steps = array(
		// <style> blocks; data-wholesale-colors marks ones already printed with the new colors.
		array('#(<style\b[^>]*>)(.*?)(</style>)#is', static function ($m) {
			// data-wholesale-colors="final": already the chosen colors, only the contrast check runs.
			return $m[1] . wholesale_design_recolor_css($m[2], false === stripos($m[1], 'data-wholesale-colors')) . $m[3];
		}),
		// style="", SVG paint attributes and the old color attributes.
		array('#(\s(?:style|fill|stroke|stop-color|flood-color|color|bgcolor)\s*=\s*)("[^"]*"|\'[^\']*\')#i', static function ($m) {
			$value = wholesale_design_recolor_value($m[2]);
			if (wholesale_design_palette_flags()['contrast'] && 0 === stripos(ltrim($m[1]), 'style')) {
				$value = wholesale_design_fix_contrast($value, '');
			}
			return $m[1] . $value;
		}),
		array('#<meta\b[^>]*\bname=["\'](?:theme-color|msapplication-TileColor)["\'][^>]*>#i', static function ($m) {
			return wholesale_design_recolor_value($m[0]);
		}),
	);
	foreach ($steps as $step) {
		$out = preg_replace_callback($step[0], $step[1], $html);
		$html = null === $out ? $html : $out;
	}

	return wholesale_design_swap_css_urls($html);
}

/**
 * Start the recolor buffer on front-end pages. Priority -999: inside the page
 * cache's buffer (-1000), so cached pages are stored with the new colors.
 */
function wholesale_design_start_buffer()
{
	if (is_admin() || wp_doing_ajax() || is_feed() || is_robots() || is_trackback() || (defined('REST_REQUEST') && REST_REQUEST) || !wholesale_design_colors_active()) {
		return;
	}
	ob_start('wholesale_design_recolor_html');
}
add_action('template_redirect', 'wholesale_design_start_buffer', -999);

/**
 * After a save, remove the stylesheet copies of older color settings (the
 * page cache is cleared by the wholesale_ option update itself).
 */
function wholesale_design_queue_cleanup($option)
{
	static $queued = false;
	if ($queued || 0 !== strpos($option, 'wholesale_')) {
		return;
	}
	$keys = array('wholesale_color_overrides', 'wholesale_palette_tidy', 'wholesale_contrast_fix');
	foreach (wholesale_design_color_roles() as $role) {
		$keys[] = 'wholesale_' . $role['option'];
	}
	if (!in_array($option, $keys, true)) {
		return;
	}
	$queued = true;
	add_action('shutdown', 'wholesale_design_cleanup_copies');
}
add_action('updated_option', 'wholesale_design_queue_cleanup');
add_action('added_option', 'wholesale_design_queue_cleanup');

function wholesale_design_cleanup_copies()
{
	$uploads = wp_upload_dir(null, false);
	$root = $uploads['basedir'] . '/wholesale-design';
	if (!empty($uploads['error']) || !is_dir($root)) {
		return;
	}
	$keep = wholesale_design_colors_active() ? wholesale_design_colors_hash() : '';
	foreach ((array) glob($root . '/*', GLOB_ONLYDIR) as $dir) {
		if (basename($dir) !== $keep && preg_match('/^[0-9a-f]{10}$/', basename($dir))) {
			wholesale_design_rmdir($dir);
		}
	}
}

function wholesale_design_rmdir($dir)
{
	foreach ((array) @scandir($dir) as $item) {
		if ('.' === $item || '..' === $item || '' === (string) $item) {
			continue;
		}
		$path = $dir . '/' . $item;
		if (is_dir($path) && !is_link($path)) {
			wholesale_design_rmdir($path);
		} else {
			@unlink($path);
		}
	}
	@rmdir($dir);
}

/* ------------------------------------------------------------------------
 * Typography
 * --------------------------------------------------------------------- */

/** Google Fonts stylesheet for the chosen fonts, when either is a Google font. */
function wholesale_design_font_assets()
{
	if (is_admin()) {
		return;
	}
	$fonts = wholesale_design_fonts();
	$families = array();
	foreach (array_unique(array(wholesale_get_setting('font_body'), wholesale_get_setting('font_heading'))) as $key) {
		if (!empty($fonts[$key]['google'])) {
			$families[] = 'family=' . $fonts[$key]['google'];
		}
	}
	if ($families) {
		// Built by hand: add_query_arg() would merge the repeated family= keys.
		wp_enqueue_style('wholesale-google-fonts', 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap', array(), null);
	}
}
add_action('wp_enqueue_scripts', 'wholesale_design_font_assets', 98);

function wholesale_design_font_preconnect($urls, $relation)
{
	if ('preconnect' === $relation && wp_style_is('wholesale-google-fonts', 'enqueued')) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array('href' => 'https://fonts.gstatic.com', 'crossorigin');
	}
	return $urls;
}
add_filter('wp_resource_hints', 'wholesale_design_font_preconnect', 10, 2);

/**
 * Typography changes, printed after css/typography.css. Only what differs
 * from the defaults is printed, so the default site gets nothing extra.
 * "html h1" (0,0,2) beats typography.css's element rules and loses to every
 * component class, as before.
 */
function wholesale_design_typography_css()
{
	$defaults = wholesale_setting_defaults();
	$fonts = wholesale_design_fonts();
	$css = '';
	$vars = '';
	foreach (array('font_body' => '--font-sans', 'font_heading' => '--font-display') as $key => $var) {
		$value = wholesale_get_setting($key);
		if ($value !== $defaults[$key] && isset($fonts[$value])) {
			$vars .= $var . ':' . $fonts[$value]['stack'] . ';';
		}
	}
	$size = (string) wholesale_get_setting('font_size_base');
	if ($size !== $defaults['font_size_base'] && is_numeric($size)) {
		$vars .= '--bs-body-font-size:' . (float) $size . 'px;';
		$css .= 'html body{font-size:' . (float) $size . 'px}';
	}
	$line = (string) wholesale_get_setting('line_height');
	if ($line !== $defaults['line_height'] && is_numeric($line)) {
		$vars .= '--bs-body-line-height:' . (float) $line . ';';
		$css .= 'html body{line-height:' . (float) $line . '}';
	}
	if ($vars) {
		$css = 'html:root{' . $vars . '}' . $css;
	}
	$weight = (string) wholesale_get_setting('heading_weight');
	if ($weight !== $defaults['heading_weight'] && is_numeric($weight)) {
		$css .= 'html :is(h2,h3,h4,h5,h6,.h2,.h3,.h4,.h5,.h6){font-weight:' . (int) $weight . '}';
	}
	$h1 = (string) wholesale_get_setting('h1_weight');
	if ($h1 !== $defaults['h1_weight'] && is_numeric($h1)) {
		$css .= 'html :is(h1,.h1,.display-1,.display-2,.display-3,.display-4,.display-5,.display-6){font-weight:' . (int) $h1 . '}';
	}
	return $css;
}

/* ------------------------------------------------------------------------
 * Logo
 * --------------------------------------------------------------------- */

/** "Use my Site logo image" turns the built-in brand artwork off everywhere. */
function wholesale_design_brand_logo_ready($ready)
{
	return $ready && 'site' !== wholesale_get_setting('logo_style');
}
add_filter('wholesale_brand_logo_ready', 'wholesale_design_brand_logo_ready');

/** Footer logo: its own image when one is chosen, else the site logo as before. */
function wholesale_design_footer_logo_id()
{
	$id = (int) wholesale_get_setting('footer_logo');
	return $id && wp_attachment_is_image($id) ? $id : 0;
}

/** Logo widths as numbers, with the ratio to each default (1 = unchanged). */
function wholesale_design_logo_widths()
{
	$defaults = wholesale_setting_defaults();
	$out = array();
	foreach (array('desktop' => 'logo_width_desktop', 'mobile' => 'logo_width_mobile', 'footer' => 'footer_logo_width') as $name => $key) {
		$value = (float) wholesale_get_setting($key);
		$default = (float) $defaults[$key];
		$value = $value > 0 ? $value : $default;
		$out[$name] = array($value, $value / $default, $value !== $default);
	}
	return $out;
}

/** The tagline as a CSS content value ('none' when empty). */
function wholesale_design_tagline_content()
{
	$text = (string) wholesale_get_setting('logo_tagline');
	if ('' === trim($text)) {
		return 'none';
	}
	return '"' . addcslashes(str_replace(array("\r", "\n", '<', '>'), ' ', $text), '"\\') . '"';
}

/**
 * The brand logo's size and tagline CSS (inc/logo-motion.php) with the Logo
 * settings applied: every width scales with its setting, and the tagline
 * text is the one set. Unchanged settings return the CSS untouched.
 * $which: 'header' or 'footer'.
 */
function wholesale_design_logo_css($css, $which)
{
	$w = wholesale_design_logo_widths();
	$map = array();
	$scale = static function ($n, $f) {
		return wholesale_design_num($n * $f);
	};
	if ('header' === $which) {
		if ($w['desktop'][2]) {
			$f = $w['desktop'][1];
			$map['396px'] = $scale(396, $f) . 'px';
			$map['21.6vw + 100.8px'] = $scale(21.6, $f) . 'vw + ' . $scale(100.8, $f) . 'px';
			$map['26.1vw - 7.2px'] = $scale(26.1, $f) . 'vw - ' . $scale(7.2, $f) . 'px';
			$map['378px'] = $scale(378, $f) . 'px';
			$map['288px'] = $scale(288, $f) . 'px';
			$map['width:249px'] = 'width:' . $scale(249, $f) . 'px';
		}
		if ($w['mobile'][2]) {
			$map['max-width:376px'] = 'max-width:' . wholesale_design_num($w['mobile'][0]) . 'px';
		}
	} else {
		if (wholesale_design_footer_logo_id()) {
			// Own footer image: keep the size, drop the brand picture's tagline mask and text.
			$cut = strpos($css, '@supports');
			$css = false === $cut ? $css : substr($css, 0, $cut);
		}
		if ($w['footer'][2]) {
			$f = $w['footer'][1];
			$map['width:340px'] = 'width:' . $scale(340, $f) . 'px';
			$map['width:290px'] = 'width:' . $scale(290, $f) . 'px';
			$map['width:300px'] = 'width:' . $scale(300, $f) . 'px';
		}
	}
	$content = wholesale_design_tagline_content();
	$default_content = '"' . WHOLESALE_DESIGN_TAGLINE . '"';
	if ($content !== $default_content) {
		$map['content:' . $default_content] = 'content:' . $content;
	}
	return $map ? strtr($css, $map) : $css;
}

/** Number for CSS: at most 2 decimals, no trailing zeros. */
function wholesale_design_num($value)
{
	return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
}

/**
 * Logo widths for the Site logo image (logo style "site"): only printed when
 * a width was changed, so the theme's own sizes stay as they were.
 */
function wholesale_design_site_logo_size_style()
{
	if (is_admin() || (function_exists('wholesale_brand_logo_ready') && wholesale_brand_logo_ready())) {
		return;
	}
	$w = wholesale_design_logo_widths();
	$css = '';
	if ($w['desktop'][2]) {
		$css .= 'html body .ssx-logo img.header-logo{width:' . wholesale_design_num($w['desktop'][0]) . 'px!important;max-width:100%!important;height:auto!important}';
	}
	if ($w['mobile'][2]) {
		$css .= '@media (max-width:991.98px){html body .site-header .sh-logo img.header-logo{width:100%!important;max-width:' . wholesale_design_num($w['mobile'][0]) . 'px!important;height:auto!important}}';
	}
	if ($w['footer'][2]) {
		$css .= 'html body .sf-brand > .sf-brand__logo{width:' . wholesale_design_num($w['footer'][0]) . 'px;max-width:100%}';
	}
	if ($css) {
		echo '<style id="wholesale-design-logo-size" data-wholesale-colors="final">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- numbers only.
	}
}
add_action('wp_head', 'wholesale_design_site_logo_size_style', 22);

/** Typography + nothing else: printed late in the head. */
function wholesale_design_head_style()
{
	if (is_admin()) {
		return;
	}
	$css = wholesale_design_typography_css();
	if ('' !== $css) {
		echo '<style id="wholesale-design-typography">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from fixed font stacks and numbers.
	}
	if (wholesale_design_palette_flags()['contrast']) {
		// Text whose stylesheet rule is shared with light sections, inside a dark
		// navy band: a light step (300) of the brand scale reads there.
		// .cl-help (style.css) is the navy "Get your quote" band on the channel
		// letter and banner pages; its .cl-kicker was #0b74a3 (3.4:1, set with
		// !important by ui-polish.css for light sections). (cl-quote.css,
		// which colors .cl-help--quote's kicker, is not loaded on those pages.)
		$light = wholesale_design_current_scale('brand')[3];
		echo '<style id="wholesale-design-contrast" data-wholesale-colors="final">html body .cl-help .cl-kicker{color:#' . esc_html($light) . '!important}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hex only.
	}
}
add_action('wp_head', 'wholesale_design_head_style', 100);

/* ------------------------------------------------------------------------
 * Settings page: Design panel
 * --------------------------------------------------------------------- */

/**
 * Every color the theme's front end uses, for the All colors table:
 * 'aabbcc' => array('count' => n, 'files' => array(name => n)). Cached until a
 * scanned file changes.
 */
function wholesale_design_color_inventory()
{
	$dir = get_template_directory();
	$files = array_merge(
		(array) glob($dir . '/css/*.css'),
		array($dir . '/style.css'),
		(array) glob($dir . '/*.php'),
		(array) glob($dir . '/template-parts/*.php'),
		(array) glob($dir . '/template-parts/*/*.php')
	);
	$skip = '/^(admin|bootstrap|all\.min|zebra|visitor|screenshots|hero-admin|payments-admin|icons|style-rtl|functions)/';
	$stamp = '';
	$list = array();
	foreach ($files as $file) {
		if ($file && is_file($file) && !preg_match($skip, basename($file))) {
			$list[] = $file;
			$stamp .= basename($file) . filemtime($file) . '|';
		}
	}
	$key = 'wholesale_design_inventory_' . md5($stamp);
	$cached = get_transient($key);
	if (is_array($cached)) {
		return $cached;
	}

	$colors = array();
	foreach ($list as $file) {
		$text = (string) file_get_contents($file);
		$rel = substr($file, strlen($dir) + 1);
		preg_match_all('/#([0-9a-f]{6}|[0-9a-f]{3})(?![0-9a-z_-])|\brgba?\(\s*(\d{1,3})(?:\s*,\s*|\s+)(\d{1,3})(?:\s*,\s*|\s+)(\d{1,3})/i', $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			if (!empty($m[1])) {
				$hex = wholesale_design_hex6($m[1]);
			} else {
				if ($m[2] > 255 || $m[3] > 255 || $m[4] > 255) {
					continue;
				}
				$hex = sprintf('%02x%02x%02x', $m[2], $m[3], $m[4]);
			}
			if (!isset($colors[$hex])) {
				$colors[$hex] = array('count' => 0, 'files' => array());
			}
			$colors[$hex]['count']++;
			$colors[$hex]['files'][$rel] = ($colors[$hex]['files'][$rel] ?? 0) + 1;
		}
	}
	uasort($colors, static function ($a, $b) {
		return $b['count'] <=> $a['count'];
	});
	foreach ($colors as &$color) {
		arsort($color['files']);
	}
	unset($color);

	set_transient($key, $colors, WEEK_IN_SECONDS);
	return $colors;
}

/** The Design panel (inc/settings-page.php). */
function wholesale_design_panel()
{
	$roles = wholesale_design_color_roles();
	$values = wholesale_design_role_values();
	$defaults = wholesale_setting_defaults();
	$inventory = wholesale_design_color_inventory();
	$overrides = wholesale_design_overrides();
	$fonts = wholesale_design_fonts();

	$by_role = array();
	foreach ($inventory as $hex => $info) {
		$by_role[wholesale_design_color_role($hex)][$hex] = $info;
	}
	$sale = wholesale_design_hex6(wholesale_get_setting('sale_price_color'));
	?>
	<div class="wss-card wsd" id="design-colors" data-wsd-roles="<?php echo esc_attr(wp_json_encode(array_map(static function ($role) {
		return wholesale_design_hex6($role['default']);
	}, $roles))); ?>" data-wsd-scales="<?php echo esc_attr(wp_json_encode(array_combine(array_keys($roles), array_map('wholesale_design_scale', array_keys($roles))))); ?>">
		<div class="wss-card__head">
			<h2>Colors</h2>
			<p>Each color below stands for a family of shades. Change it and every lighter, darker and see-through shade of it on the site moves with it, so hover colors, tints and shadows still match. The strip under each color shows its shades before and after. To change one exact shade only, use <a href="#design-all-colors">All colors</a> below. After saving, the page cache is cleared and the new colors show on the next visit.</p>
		</div>
		<div class="wsd-roles">
			<?php foreach ($roles as $key => $role) : ?>
				<?php
				$name = 'wholesale_' . $role['option'];
				$default = wholesale_design_hex6($role['default']);
				$family = isset($by_role[$key]) ? array_slice(array_keys($by_role[$key]), 0, 14) : array();
				?>
				<div class="wsd-role" data-wsd-role="<?php echo esc_attr($key); ?>">
					<div class="wsd-role__pick">
						<input type="color" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" value="#<?php echo esc_attr($values[$key]); ?>" data-wsd-color data-default="#<?php echo esc_attr($default); ?>">
						<div>
							<label for="<?php echo esc_attr($name); ?>"><strong><?php echo esc_html($role['label']); ?></strong></label>
							<input type="text" class="wsd-hex code" value="#<?php echo esc_attr($values[$key]); ?>" maxlength="7" spellcheck="false" aria-label="<?php echo esc_attr($role['label'] . ' hex code'); ?>" data-wsd-hex data-wss-nosave>
							<button type="button" class="button-link wsd-reset" data-wsd-reset <?php echo $values[$key] === $default ? 'hidden' : ''; ?>>Reset to #<?php echo esc_html($default); ?></button>
						</div>
					</div>
					<p class="description"><?php echo esc_html($role['help']); ?> <?php echo esc_html(sprintf('%d shade%s.', count($by_role[$key] ?? array()), 1 === count($by_role[$key] ?? array()) ? '' : 's')); ?></p>
					<?php $scale = wholesale_design_current_scale($key); ?>
					<?php if (count($scale) > 1) : ?>
						<div class="wsd-scale" data-wsd-scale title="Palette: 50 (lightest) to 950 (darkest)">
							<?php foreach ($scale as $i => $step) : ?>
								<span style="background:#<?php echo esc_attr($step); ?>" title="<?php echo esc_attr(wholesale_design_scale_steps()[$i] . ': #' . $step); ?>"></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="wsd-strip" aria-hidden="true">
						<?php foreach ($family as $hex) : ?>
							<span class="wsd-chip" data-wsd-chip="<?php echo esc_attr($hex); ?>" title="#<?php echo esc_attr($hex); ?>"><i style="background:#<?php echo esc_attr($hex); ?>"></i><i class="wsd-chip__new"></i></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<table class="form-table" role="presentation">
			<tr><th scope="row">Professional palette</th><td>
				<input type="hidden" name="wholesale_palette_tidy" value="0">
				<label class="wss-toggle"><input type="checkbox" name="wholesale_palette_tidy" value="1" data-wsd-tidy <?php checked(wholesale_setting_enabled('palette_tidy')); ?>> Use one 11-step scale per color</label>
				<p class="description">The theme had grown <?php echo esc_html(number_format_i18n(count($inventory))); ?> slightly different colors (138 blues alone). With this on, every shade uses the nearest step of its family's scale above (50 lightest to 950 darkest), and all grays share one slate scale, so the same kind of element always gets the same color. Off: every original shade as written.</p>
			</td></tr>
			<tr><th scope="row">Readable contrast</th><td>
				<input type="hidden" name="wholesale_contrast_fix" value="0">
				<label class="wss-toggle"><input type="checkbox" name="wholesale_contrast_fix" value="1" <?php checked(wholesale_setting_enabled('contrast_fix')); ?>> Make text and buttons meet WCAG AA contrast</label>
				<p class="description">Text needs 4.5:1 against its background (icons and large text 3:1). The brand blue #1fa8de reaches only 2.7:1 as link text on white and with white text on buttons, so with this on, text, links and buttons that fall short use the nearest darker step of their own color (the brand blue becomes #146f93 there), and gray small print becomes a darker gray. Fills, icons and decorative uses keep the bright color. Off: colors exactly as the palette above.</p>
			</td></tr>
			<tr><th><label for="wholesale_sale_price_color">Sale price color</label></th><td>
				<input type="color" id="wholesale_sale_price_color" name="wholesale_sale_price_color" value="#<?php echo esc_attr($sale ? $sale : '1fa8de'); ?>" data-default="#1fa8de">
				<p class="description">Color of the sale price next to the crossed-out price. Left at #1fa8de it follows the brand color.</p>
			</td></tr>
			<tr><th scope="row">Start over</th><td>
				<button type="button" class="button" data-wsd-reset-all>Reset all colors to the theme's own</button>
				<p class="description">Sets every color above and every All colors change back to the default. Nothing changes until you save.</p>
			</td></tr>
		</table>
	</div>

	<div class="wss-card wsd" id="design-all-colors">
		<div class="wss-card__head">
			<h2>All colors <span class="wss-badge <?php echo $overrides ? 'is-ok' : 'is-info'; ?>" data-wsd-override-count><?php echo esc_html($overrides ? sprintf('%d changed', count($overrides)) : 'None changed'); ?></span></h2>
			<p>Every color the theme's pages use (<?php echo esc_html(number_format_i18n(count($inventory))); ?>), grouped by family, with where each is used. A color set here replaces that one shade only and wins over its family. Colors outside every family (such as Google blue or purple) can only be changed here.</p>
		</div>
		<input type="hidden" name="wholesale_color_overrides" value="<?php echo esc_attr((string) wholesale_get_setting('color_overrides')); ?>" data-wsd-overrides>
		<div class="wsd-all-tools" data-wss-nosave>
			<label class="screen-reader-text" for="wsd-all-search">Find a color</label>
			<input type="search" id="wsd-all-search" placeholder="Find a color, e.g. #1fa8de or footer.css" autocomplete="off">
			<label><input type="checkbox" data-wsd-only-changed> Only changed</label>
		</div>
		<?php
		$groups = $roles + array('' => array('label' => 'Other colors'));
		foreach ($groups as $key => $group) :
			if (empty($by_role[$key])) {
				continue;
			}
			?>
			<details class="wsd-group" data-wsd-group="<?php echo esc_attr($key); ?>">
				<summary><strong><?php echo esc_html($group['label']); ?></strong> <span class="description"><?php echo esc_html(sprintf('%d color%s', count($by_role[$key]), 1 === count($by_role[$key]) ? '' : 's')); ?></span></summary>
				<table class="widefat striped wsd-table">
					<thead><tr><th>Theme color</th><th>Now</th><th>Uses</th><th>Used in</th></tr></thead>
					<tbody>
						<?php foreach ($by_role[$key] as $hex => $info) : ?>
							<?php $now = $overrides[$hex] ?? $hex; ?>
							<tr data-wsd-row="<?php echo esc_attr($hex); ?>" data-wsd-row-role="<?php echo esc_attr($key); ?>">
								<td><span class="wsd-swatch" style="background:#<?php echo esc_attr($hex); ?>"></span> <code>#<?php echo esc_html($hex); ?></code></td>
								<td><input type="color" value="#<?php echo esc_attr($now); ?>" aria-label="<?php echo esc_attr('New color for #' . $hex); ?>" data-wsd-row-color data-wss-nosave> <button type="button" class="button-link" data-wsd-row-reset <?php echo isset($overrides[$hex]) ? '' : 'hidden'; ?>>Reset</button></td>
								<td><?php echo esc_html(number_format_i18n($info['count'])); ?></td>
								<td class="wsd-files"><?php echo esc_html(implode(', ', array_slice(array_keys($info['files']), 0, 4)) . (count($info['files']) > 4 ? sprintf(' +%d more', count($info['files']) - 4) : '')); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</details>
		<?php endforeach; ?>
	</div>

	<div class="wss-card wsd" id="design-typography">
		<div class="wss-card__head"><h2>Typography</h2><p>Fonts and text sizes for the whole site. Product sign previews (channel letter builder fonts) are not affected. Google fonts load from Google Fonts on each visit; the two theme fonts and the no-download options load nothing extra.</p></div>
		<table class="form-table" role="presentation">
			<?php foreach (array('font_body' => 'Text font', 'font_heading' => 'Heading font') as $key => $label) : ?>
				<tr><th><label for="wholesale_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td>
					<select id="wholesale_<?php echo esc_attr($key); ?>" name="wholesale_<?php echo esc_attr($key); ?>" data-wsd-font="<?php echo 'font_body' === $key ? 'body' : 'heading'; ?>">
						<?php foreach ($fonts as $font_key => $font) : ?>
							<option value="<?php echo esc_attr($font_key); ?>" data-stack="<?php echo esc_attr($font['stack']); ?>" data-google="<?php echo esc_attr($font['google'] ?? ''); ?>" <?php selected(wholesale_get_setting($key), $font_key); ?>><?php echo esc_html($font['label'] . ($font_key === $defaults[$key] ? ' (default)' : '')); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php echo 'font_body' === $key ? 'Paragraphs, menus, buttons, forms and prices.' : 'Page titles and section headings (h1 to h6).'; ?></p>
				</td></tr>
			<?php endforeach; ?>
			<tr><th><label for="wholesale_font_size_base">Base text size</label></th><td><input type="number" class="small-text" min="12" max="22" step="1" id="wholesale_font_size_base" name="wholesale_font_size_base" value="<?php echo esc_attr(wholesale_get_setting('font_size_base')); ?>" data-wsd-type> px <p class="description">Default 16. Text that sets its own size (headings, buttons, product cards) keeps it.</p></td></tr>
			<tr><th><label for="wholesale_line_height">Line height</label></th><td><input type="number" class="small-text" min="1" max="2.4" step="0.05" id="wholesale_line_height" name="wholesale_line_height" value="<?php echo esc_attr(wholesale_get_setting('line_height')); ?>" data-wsd-type> <p class="description">Space between lines of paragraph text. Default 1.6.</p></td></tr>
			<tr><th><label for="wholesale_heading_weight">Heading weight</label></th><td><select id="wholesale_heading_weight" name="wholesale_heading_weight" data-wsd-type><?php foreach (array(400 => 'Regular (400)', 500 => 'Medium (500)', 600 => 'Semibold (600)', 700 => 'Bold (700)', 800 => 'Extra bold (800)', 900 => 'Black (900)') as $w => $label) : ?><option value="<?php echo esc_attr($w); ?>" <?php selected((int) wholesale_get_setting('heading_weight'), $w); ?>><?php echo esc_html($label . (700 === $w ? ' (default)' : '')); ?></option><?php endforeach; ?></select><p class="description">h2 to h6.</p></td></tr>
			<tr><th><label for="wholesale_h1_weight">Page title weight</label></th><td><select id="wholesale_h1_weight" name="wholesale_h1_weight" data-wsd-type><?php foreach (array(400 => 'Regular (400)', 500 => 'Medium (500)', 600 => 'Semibold (600)', 700 => 'Bold (700)', 800 => 'Extra bold (800)', 900 => 'Black (900)') as $w => $label) : ?><option value="<?php echo esc_attr($w); ?>" <?php selected((int) wholesale_get_setting('h1_weight'), $w); ?>><?php echo esc_html($label . (800 === $w ? ' (default)' : '')); ?></option><?php endforeach; ?></select><p class="description">h1 page titles and display headings.</p></td></tr>
			<tr><th scope="row">Preview</th><td>
				<div class="wsd-type-preview" data-wsd-preview>
					<p class="wsd-type-preview__h1">Custom channel letters</p>
					<p class="wsd-type-preview__h2">Built and tested before shipping</p>
					<p class="wsd-type-preview__p">Front-lit, back-lit and open face channel letters with free artwork proofs, made in the USA and shipped ready to mount. Order online or ask for a quote.</p>
				</div>
			</td></tr>
		</table>
	</div>

	<div class="wss-card wsd" id="design-logo">
		<div class="wss-card__head"><h2>Logo &amp; site icon</h2><p>The header, phone menu and footer logos, their size, and the icon browsers show in tabs and on phone home screens.</p></div>
		<div class="wss-image-grid">
			<?php
			$logo_id = (int) get_theme_mod('custom_logo');
			$logo_id = $logo_id && wp_attachment_is_image($logo_id) ? $logo_id : 0;
			$logo_default = function_exists('wholesale_image_default_id') ? wholesale_image_default_id('site_logo') : 0;
			$logo_default_url = $logo_default ? wp_get_attachment_image_url($logo_default, 'medium_large') : '';
			wholesale_settings_image_field(array(
				'name' => 'wholesale_site_logo',
				'value' => $logo_id,
				'title' => 'Site logo',
				'help' => 'Used by the SEO markup, feeds and emails, by the footer unless a footer logo is chosen, and by the header when the header shows "my Site logo". A wide PNG, SVG or WebP with a transparent background, at least 800 px wide. Same setting as Appearance &rsaquo; Customize &rsaquo; Site Identity.',
				'preview' => $logo_id ? wp_get_attachment_image_url($logo_id, 'medium_large') : $logo_default_url,
				'fallback' => $logo_default_url,
				'fallback_label' => $logo_default_url ? 'Default image' : 'Not set',
				'source' => $logo_id ? 'library' : ($logo_default_url ? 'default' : 'none'),
				'ratio' => '4 / 1',
				'contain' => true,
			));
			$footer_id = wholesale_design_footer_logo_id();
			wholesale_settings_image_field(array(
				'name' => 'wholesale_footer_logo',
				'value' => $footer_id,
				'title' => 'Footer logo',
				'help' => 'Optional. The footer has a dark background, so a logo with light text works best. Without one, the footer shows the brand logo&rsquo;s light copy (or the Site logo).',
				'preview' => $footer_id ? wp_get_attachment_image_url($footer_id, 'medium_large') : '',
				'fallback' => '',
				'fallback_label' => 'Same as header',
				'source' => $footer_id ? 'library' : 'none',
				'ratio' => '4 / 1',
				'contain' => true,
			));
			$icon_id = (int) get_option('site_icon');
			$icon_id = $icon_id && wp_attachment_is_image($icon_id) ? $icon_id : 0;
			wholesale_settings_image_field(array(
				'name' => 'wholesale_site_icon',
				'value' => $icon_id,
				'title' => 'Site icon',
				'help' => 'Browser tab, bookmarks and phone home screens. A square PNG, at least 512 &times; 512 px. Same setting as Settings &rsaquo; General &rsaquo; Site Icon.',
				'preview' => $icon_id ? wp_get_attachment_image_url($icon_id, 'medium') : '',
				'fallback' => '',
				'fallback_label' => 'Not set',
				'source' => $icon_id ? 'library' : 'none',
				'ratio' => '1 / 1',
				'contain' => true,
			));
			?>
		</div>
		<table class="form-table" role="presentation">
			<tr><th scope="row">Header logo</th><td>
				<fieldset class="wss-choices">
					<label class="wss-choice"><input type="radio" name="wholesale_logo_style" value="brand" <?php checked('site' !== wholesale_get_setting('logo_style')); ?>> <span><strong>Built-in brand logo</strong> (default)<span class="description">The theme's StorefrontSign artwork (img/brand/) with the text tagline under it and the light copy in the footer.</span></span></label>
					<label class="wss-choice"><input type="radio" name="wholesale_logo_style" value="site" <?php checked('site', wholesale_get_setting('logo_style')); ?>> <span><strong>My Site logo image</strong><span class="description">The Site logo above in the header, phone menu and footer, as it is.</span></span></label>
				</fieldset>
			</td></tr>
			<tr><th scope="row">Logo animation</th><td>
				<input type="hidden" name="wholesale_logo_animation" value="0">
				<label class="wss-toggle"><input type="checkbox" name="wholesale_logo_animation" value="1" <?php checked(wholesale_setting_enabled('logo_animation')); ?>> Animate the built-in brand logo</label>
				<p class="description">The letters light up and the sign turns once after the page loads and on hover. Also needs Motion effects (Store tab) on.</p>
			</td></tr>
			<tr><th><label for="wholesale_logo_width_desktop">Header logo width, computers</label></th><td><input type="number" class="small-text" min="120" max="700" step="1" id="wholesale_logo_width_desktop" name="wholesale_logo_width_desktop" value="<?php echo esc_attr(wholesale_get_setting('logo_width_desktop')); ?>"> px <p class="description">Default 396. Smaller screens and the shrunk header on scroll scale with it.</p></td></tr>
			<tr><th><label for="wholesale_logo_width_mobile">Header logo width, phones</label></th><td><input type="number" class="small-text" min="100" max="500" step="1" id="wholesale_logo_width_mobile" name="wholesale_logo_width_mobile" value="<?php echo esc_attr(wholesale_get_setting('logo_width_mobile')); ?>"> px at most <p class="description">Default 376. On small phones the logo takes whatever room the call, cart and menu buttons leave.</p></td></tr>
			<tr><th><label for="wholesale_footer_logo_width">Footer logo width</label></th><td><input type="number" class="small-text" min="100" max="600" step="1" id="wholesale_footer_logo_width" name="wholesale_footer_logo_width" value="<?php echo esc_attr(wholesale_get_setting('footer_logo_width')); ?>"> px <p class="description">Default 340 on computers; tablets and phones scale with it.</p></td></tr>
			<tr><th><label for="wholesale_logo_tagline">Logo tagline</label></th><td><input type="text" class="large-text" maxlength="80" id="wholesale_logo_tagline" name="wholesale_logo_tagline" value="<?php echo esc_attr(wholesale_get_setting('logo_tagline')); ?>"> <p class="description">Drawn as sharp text under the built-in brand logo, spread across its width. Default &ldquo;<?php echo esc_html(WHOLESALE_DESIGN_TAGLINE); ?>&rdquo;. A line of about the same length fits best; leave empty for no tagline.</p></td></tr>
		</table>
	</div>
	<?php
}

function wholesale_design_admin_assets($hook)
{
	if ('toplevel_page_wholesale-settings' !== $hook) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('wholesale-admin-design', $uri . '/css/admin-design.css', array('wholesale-admin-settings'), (string) filemtime($dir . '/css/admin-design.css'));
	wp_add_inline_style('wholesale-admin-design', '@font-face{font-family:"Inter";font-weight:400 700;font-display:swap;src:url("' . esc_url($uri . '/fonts/inter-latin-var.woff2') . '") format("woff2")}@font-face{font-family:"Plus Jakarta Sans";font-weight:500 800;font-display:swap;src:url("' . esc_url($uri . '/fonts/plus-jakarta-sans-latin-var.woff2') . '") format("woff2")}');
	wp_enqueue_script('wholesale-admin-design', $uri . '/js/admin-design.js', array('jquery', 'wholesale-admin-settings'), (string) filemtime($dir . '/js/admin-design.js'), true);
}
add_action('admin_enqueue_scripts', 'wholesale_design_admin_assets', 11);
