<?php
/**
 * Site images that live in the Media Library instead of the theme folder, so
 * the theme stays small. Each slot stores an attachment ID in an option and is
 * set under Settings > Storefront Sign > Images. A slot that is still empty
 * falls back to its old file in img/ while that file exists, and the
 * "Move to Media Library" button there imports such files.
 *
 * The site logo is WordPress's own custom logo (Appearance > Customize); the
 * settings page edits that same value. The default hero keeps its own setting
 * (wholesale_default_hero_image) and its built-in photo in img/.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * key => label, help text, the old theme file it replaces, and the attachment
 * size served (sized for where the image is shown).
 */
function wholesale_image_slots()
{
	return array(
		'landing_hero' => array(
			'label' => 'Landing page hero',
			'help' => 'Background photo of the Sign Company landing page. Landscape, at least 1440 &times; 600 px. The left side sits under the headline, so keep it darker and quiet.',
			'legacy' => 'img/landing-hero-1440.webp',
			'size' => '2048x2048',
		),
		'landing_hero_mobile' => array(
			'label' => 'Landing page hero on phones',
			'help' => 'Optional. A smaller or differently cropped photo for screens under 768 px wide. When empty, phones get a resized copy of the photo above.',
			'legacy' => 'img/landing-hero-960.webp',
			'size' => 'large',
		),
	);
}

function wholesale_image_slot_option($key)
{
	return 'wholesale_image_' . $key;
}

function wholesale_image_slot_id($key)
{
	$id = (int) get_option(wholesale_image_slot_option($key), 0);
	return $id && wp_attachment_is_image($id) ? $id : 0;
}

/**
 * Default image of a slot (or 'site_logo'): a Media Library copy of the file
 * the theme used to ship, shown while nothing else is chosen. Stored as
 * key => attachment ID in the wholesale_image_defaults option.
 */
function wholesale_image_default_id($key)
{
	$defaults = get_option('wholesale_image_defaults', array());
	$id = is_array($defaults) ? (int) ($defaults[$key] ?? 0) : 0;
	return $id && wp_attachment_is_image($id) ? $id : 0;
}

/**
 * The image a slot shows: the chosen one, else its default.
 */
function wholesale_image_slot_effective_id($key)
{
	$id = wholesale_image_slot_id($key);
	return $id ? $id : wholesale_image_default_id($key);
}

/**
 * The slot's old file in img/, as array(path, url), or null when it is gone.
 */
function wholesale_image_slot_legacy($key)
{
	$slots = wholesale_image_slots();
	$file = $slots[$key]['legacy'] ?? '';
	if ('' === $file || !file_exists(get_template_directory() . '/' . $file)) {
		return null;
	}
	return array(get_template_directory() . '/' . $file, get_template_directory_uri() . '/' . $file);
}

/**
 * URL of a slot's image: the chosen Media Library image, else its default,
 * else the old theme file, else ''.
 */
function wholesale_image_slot_url($key, $size = '')
{
	$slots = wholesale_image_slots();
	if (!isset($slots[$key])) {
		return '';
	}

	$id = wholesale_image_slot_effective_id($key);
	if ($id) {
		$url = wp_get_attachment_image_url($id, $size ? $size : $slots[$key]['size']);
		if ($url) {
			return $url;
		}
	}

	$legacy = wholesale_image_slot_legacy($key);
	return $legacy ? $legacy[1] : '';
}

/**
 * The landing page hero for wide screens and for phones. Either may be ''.
 */
function wholesale_landing_hero_urls()
{
	$wide = wholesale_image_slot_url('landing_hero');
	$phone = wholesale_image_slot_url('landing_hero_mobile');
	if ('' === $phone) {
		$phone = wholesale_image_slot_effective_id('landing_hero') ? wholesale_image_slot_url('landing_hero', 'large') : $wide;
	}
	return array('wide' => $wide, 'phone' => $phone);
}

/**
 * Inline style for the landing hero section. css/landing-page.css reads the
 * two variables and keeps its dark overlay when they are missing.
 */
function wholesale_landing_hero_style()
{
	$urls = wholesale_landing_hero_urls();
	$style = '';
	foreach (array('wide' => '--landing-hero-wide', 'phone' => '--landing-hero-phone') as $which => $var) {
		if ($urls[$which]) {
			$style .= $var . ": url('" . esc_url($urls[$which]) . "');";
		}
	}
	return $style;
}

/**
 * Shown where the logo goes when no custom logo is set: the default logo
 * from the Media Library, else the site name as text.
 */
function wholesale_logo_fallback_html($class = '')
{
	$default = wholesale_image_default_id('site_logo');
	if ($default) {
		return wp_get_attachment_image($default, 'full', false, array(
			'class' => $class,
			'alt' => get_bloginfo('name'),
			'loading' => 'eager',
			'decoding' => 'async',
		));
	}
	return '<span class="wholesale-logo-text' . ($class ? ' ' . esc_attr($class) : '') . '" style="display:inline-block;font-weight:800;font-size:1.25rem;line-height:1.2;color:inherit;">' . esc_html(get_bloginfo('name')) . '</span>';
}

/**
 * The settings page saves the logo through this option; the value is copied
 * into the theme's custom logo, which is what the templates read.
 */
function wholesale_sanitize_site_logo($value)
{
	// Not in the submitted form (options.php then passes null): leave it alone.
	if (null === $value || '' === $value) {
		return (int) get_theme_mod('custom_logo');
	}
	$id = absint($value);
	$id = $id && wp_attachment_is_image($id) ? $id : 0;
	if ($id) {
		set_theme_mod('custom_logo', $id);
	} elseif (get_theme_mod('custom_logo')) {
		remove_theme_mod('custom_logo');
	}
	return $id;
}

function wholesale_register_image_settings()
{
	foreach (array_keys(wholesale_image_slots()) as $key) {
		register_setting('wholesale_settings', wholesale_image_slot_option($key), array(
			'type' => 'integer',
			'sanitize_callback' => 'wholesale_sanitize_image_id',
		));
	}
	register_setting('wholesale_settings', 'wholesale_site_logo', array(
		'type' => 'integer',
		'sanitize_callback' => 'wholesale_sanitize_site_logo',
	));
}
add_action('admin_init', 'wholesale_register_image_settings');

/**
 * Copy a file into the Media Library. Returns the attachment ID or WP_Error.
 */
function wholesale_import_image_file($path, $title)
{
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// media_handle_sideload() moves the file it is given, so hand it a copy.
	$tmp = wp_tempnam(basename($path));
	if (!$tmp || !copy($path, $tmp)) {
		return new WP_Error('wholesale_copy', 'Could not copy ' . basename($path));
	}

	$id = media_handle_sideload(array('name' => basename($path), 'tmp_name' => $tmp), 0, $title);
	if (is_wp_error($id)) {
		@unlink($tmp);
	}
	return $id;
}

/**
 * "Move to Media Library": imports the old theme file of every empty slot.
 */
function wholesale_handle_import_theme_images()
{
	if (!current_user_can('manage_options') || !current_user_can('upload_files')) {
		wp_die('You are not allowed to do this.');
	}
	check_admin_referer('wholesale_import_theme_images');

	$imported = 0;
	$failed = array();
	foreach (wholesale_image_slots() as $key => $slot) {
		$legacy = wholesale_image_slot_legacy($key);
		if (wholesale_image_slot_id($key) || !$legacy) {
			continue;
		}
		$id = wholesale_import_image_file($legacy[0], $slot['label']);
		if (is_wp_error($id)) {
			$failed[] = $slot['label'];
			continue;
		}
		update_post_meta($id, '_wp_attachment_image_alt', '');
		update_option(wholesale_image_slot_option($key), (int) $id);
		$imported++;
	}

	wp_safe_redirect(add_query_arg(array(
		'page' => 'wholesale-settings',
		'images_imported' => $imported,
		'images_failed' => count($failed),
	), admin_url('options-general.php')) . '#images');
	exit;
}
add_action('admin_post_wholesale_import_theme_images', 'wholesale_handle_import_theme_images');

/**
 * Slots with no image at all (neither uploaded nor an old theme file).
 */
function wholesale_missing_image_slots()
{
	$missing = array();
	foreach (wholesale_image_slots() as $key => $slot) {
		if ('' === wholesale_image_slot_url($key) && 'landing_hero_mobile' !== $key) {
			$missing[$key] = $slot['label'];
		}
	}
	if (!get_theme_mod('custom_logo') && !wholesale_image_default_id('site_logo')) {
		$missing['site_logo'] = 'Site logo';
	}
	return $missing;
}

/**
 * Warn administrators on the dashboard when a site image is missing, so a
 * deploy without the Media Library images doesn't go unnoticed.
 */
function wholesale_missing_images_notice()
{
	if (!current_user_can('manage_options')) {
		return;
	}
	$screen = get_current_screen();
	if (!$screen || !in_array($screen->id, array('dashboard', 'settings_page_wholesale-settings'), true)) {
		return;
	}
	$missing = wholesale_missing_image_slots();
	if (!$missing) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p><strong>Site images missing:</strong> %s. <a href="%s">Upload them under Storefront Sign &rsaquo; Images</a>.</p></div>',
		esc_html(implode(', ', $missing)),
		esc_url(admin_url('options-general.php?page=wholesale-settings#images'))
	);
}
add_action('admin_notices', 'wholesale_missing_images_notice');
