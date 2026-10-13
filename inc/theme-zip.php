<?php
/**
 * "Download theme zip" (Settings > Storefront Sign header): builds an
 * installable zip of this theme in one click, leaving out development files
 * (version control, editor and AI tool settings, caches, logs, old zips).
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Whether a path inside the theme (relative, with / separators) stays out of the zip.
 */
function wholesale_theme_zip_excluded($rel)
{
	foreach (explode('/', $rel) as $part) {
		if (in_array($part, array('.git', '.github', '.claude', '.vscode', '.idea', '__pycache__', 'node_modules', '.sass-cache'), true)) {
			return true;
		}
	}
	$base = basename($rel);
	return in_array($base, array('.DS_Store', 'Thumbs.db', 'desktop.ini', 'error_log', '.gitignore', '.gitattributes'), true)
		|| (bool) preg_match('/\.(zip|log|part|tmp|swp|bak)$/i', $base);
}

function wholesale_handle_theme_zip()
{
	if (!current_user_can('manage_options')) {
		wp_die('You are not allowed to do this.');
	}
	check_admin_referer('wholesale_theme_zip');
	if (!class_exists('ZipArchive')) {
		wp_die('The PHP Zip extension (ZipArchive) is not available on this server.');
	}

	$root = wp_normalize_path(get_template_directory());
	$slug = get_template();
	$version = (string) wp_get_theme($slug)->get('Version');
	$name = $slug . ($version ? '-' . $version : '') . '-' . wp_date('Y-m-d-His') . '.zip';

	require_once ABSPATH . 'wp-admin/includes/file.php';
	$tmp = wp_tempnam($name);
	$zip = new ZipArchive();
	if (true !== $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
		wp_die('Could not create the zip file.');
	}

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::LEAVES_ONLY
	);
	$count = 0;
	foreach ($files as $file) {
		if (!$file->isFile()) {
			continue;
		}
		$rel = ltrim(substr(wp_normalize_path($file->getPathname()), strlen($root)), '/');
		if (wholesale_theme_zip_excluded($rel)) {
			continue;
		}
		// Inside a "<slug>/" folder, so WordPress installs it as this theme.
		$zip->addFile($file->getPathname(), $slug . '/' . $rel);
		$count++;
	}
	$zip->close();

	if (!$count || !is_file($tmp)) {
		@unlink($tmp);
		wp_die('The theme folder is empty.');
	}

	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	nocache_headers();
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="' . $name . '"');
	header('Content-Length: ' . filesize($tmp));
	readfile($tmp);
	@unlink($tmp);
	exit;
}
add_action('admin_post_wholesale_theme_zip', 'wholesale_handle_theme_zip');

function wholesale_theme_zip_url()
{
	return wp_nonce_url(admin_url('admin-post.php?action=wholesale_theme_zip'), 'wholesale_theme_zip');
}
