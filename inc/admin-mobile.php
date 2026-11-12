<?php
/**
 * Phone-friendly wp-admin (2026-11-12).
 *
 * Makes every admin screen usable on a small iPhone (iPhone 12 mini, 375px wide):
 * - viewport stays at phone width (no zoomed-out "desktop" view after a reload, no
 *   iOS auto-zoom when a small field is tapped),
 * - every toolbar item, list filter, bulk action, Export button and sort option that
 *   WordPress hides on phones is shown again,
 * - wide tables scroll sideways inside their own box instead of widening the page.
 *
 * Styles: css/admin-mobile.css (all rules sit inside max-width: 782px, so desktop is
 * untouched). Script: js/admin-mobile.js. Both load on every admin screen, including
 * plugin screens, and are versioned by file date so a reload always gets the newest copy.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Viewport for admin screens. On iPhone/iPad, maximum-scale=1 stops Safari from
 * zooming in when a field under 16px is tapped (and staying zoomed); iOS still
 * allows pinch zoom, so nothing is lost.
 */
function wholesale_admin_mobile_viewport($content)
{
	$content = 'width=device-width,initial-scale=1.0,viewport-fit=cover';
	$agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	if (preg_match('/iPhone|iPad|iPod/i', $agent)) {
		$content .= ',maximum-scale=1.0';
	}
	return $content;
}
add_filter('admin_viewport_meta', 'wholesale_admin_mobile_viewport', 20);

function wholesale_admin_mobile_assets()
{
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	$css = $dir . '/css/admin-mobile.css';
	$js = $dir . '/js/admin-mobile.js';

	if (is_file($css)) {
		wp_enqueue_style('wholesale-admin-mobile', $uri . '/css/admin-mobile.css', array(), (string) filemtime($css));
	}
	if (is_file($js)) {
		wp_enqueue_script('wholesale-admin-mobile', $uri . '/js/admin-mobile.js', array(), (string) filemtime($js), true);
	}
}
// Late so these rules load after plugin and theme admin styles.
add_action('admin_enqueue_scripts', 'wholesale_admin_mobile_assets', 999);
