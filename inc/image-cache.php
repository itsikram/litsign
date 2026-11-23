<?php
/**
 * Browser caching for images.
 *
 * The server tells browsers to keep every image file for a year without asking
 * again ("public, max-age=31536000, immutable": the theme's .htaccess for theme
 * files, the root .htaccess for uploads, plugins and wp-includes). A repeat
 * visit then shows images straight from the browser's cache.
 *
 * Media Library uploads always get a new file name, so a new photo is a new URL.
 * Some theme images are replaced under the same name (the home hero, the site
 * icons), and a year-long cache would keep showing the old picture. So pages
 * link theme images with ?v=<file time>: a replaced file gets a new URL, every
 * browser fetches it once, then keeps it.
 *
 * @package tr-wholesale
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Theme images that the CSS or JS files also link, without a version. Adding
 * ?v= only in the page would make browsers download them twice (for example a
 * preload with ?v= and a stylesheet without it).
 *
 * @return string[] Paths relative to the theme folder.
 */
function wholesale_unversioned_theme_images()
{
	return (array) apply_filters('wholesale_unversioned_theme_images', array(
		'img/hero-1920.webp',      // css/style.css, css/cl-ads.css
		'img/hero-1920.avif',      // css/style.css
		'img/dual-color-white.jpeg', // js/cl.js
		'img/dual-color-black.jpeg', // js/cl.js
		'img/ajax_loader.gif',     // js/cl.js
	));
}

/**
 * Version for one theme image: its modification time, or '' when the file is
 * missing or listed in wholesale_unversioned_theme_images().
 */
function wholesale_theme_image_version($relative)
{
	static $versions = array();
	static $skip = null;

	$relative = ltrim(rawurldecode((string) $relative), '/');
	if (null === $skip) {
		$skip = array_flip(wholesale_unversioned_theme_images());
	}
	if (!isset($versions[$relative])) {
		$file = get_template_directory() . '/' . $relative;
		$versions[$relative] = (isset($skip[$relative]) || false !== strpos($relative, '..') || !is_file($file)) ? '' : (string) filemtime($file);
	}

	return $versions[$relative];
}

/**
 * Add ?v=<file time> to theme image URLs that have no query string yet. Works
 * on a whole page or on a single URL.
 */
function wholesale_version_theme_image_urls($html)
{
	if (!is_string($html) || '' === $html) {
		return $html;
	}
	$path = rtrim((string) wp_parse_url(get_template_directory_uri(), PHP_URL_PATH), '/');
	if ('' === $path || false === strpos($html, $path . '/img')) {
		return $html;
	}

	// The lookahead skips URLs that already have a query (?ver=, ?v=) and stops
	// "photo.png" from matching inside "photo.png.webp".
	$pattern = '#' . preg_quote($path, '#') . '/((?:img|images)/[A-Za-z0-9_./%-]+?\.(?:png|jpe?g|gif|webp|avif|svg|ico))(?![A-Za-z0-9_./%?-])#i';
	$out = preg_replace_callback($pattern, static function ($match) {
		$version = wholesale_theme_image_version($match[1]);
		return '' === $version ? $match[0] : $match[0] . '?v=' . $version;
	}, $html);

	return null === $out ? $html : $out;
}

/**
 * Start the buffer on front-end pages. Priority -998: inside the page cache's
 * buffer (-1000) and the design recolor buffer (-999), so cached pages are
 * stored with the versions.
 */
function wholesale_image_cache_start_buffer()
{
	if (is_admin() || wp_doing_ajax() || is_feed() || is_robots() || is_trackback() || (defined('REST_REQUEST') && REST_REQUEST)) {
		return;
	}
	ob_start('wholesale_version_theme_image_urls');
}
add_action('template_redirect', 'wholesale_image_cache_start_buffer', -998);

/**
 * /favicon.ico: WordPress answers with a redirect to the site icon that no
 * browser may keep, so every request started PHP. Let browsers keep the
 * redirect for a week; the icon it points to is versioned and kept for a year.
 */
function wholesale_cacheable_favicon_redirect()
{
	$url = wholesale_version_theme_image_urls((string) get_site_icon_url(32, includes_url('images/w-logo-gray-white-bg.png')));
	if ('' === $url || headers_sent()) {
		return;
	}
	header_remove('Expires');
	header_remove('Pragma');
	header('Cache-Control: public, max-age=604800');
	wp_redirect($url, 302, 'WordPress');
	exit;
}
add_action('do_faviconico', 'wholesale_cacheable_favicon_redirect');
