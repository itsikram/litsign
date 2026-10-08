<?php
/**
 * Background file uploads for the contact form, the on-page quote forms (banners and
 * both channel letters pages) and the product page artwork field (js/async-upload.js).
 *
 * A picked file is sent right away to wholesale_async_upload, kept in a private folder
 * under a random token, and the form then submits only the token. The form handler calls
 * wholesale_async_upload_take() to get a $_FILES-style array for it and moves it into
 * place with wholesale_async_upload_store(). Plain $_FILES posts still work, so the
 * forms keep working without JavaScript or if the background upload fails.
 *
 * No nonce: product pages are served from the page cache. Tokens are unguessable,
 * claimed once, tied to one form type, and uploads are rate limited per IP.
 *
 * @package litsign
 */

/**
 * Upload rules per form: allowed types and the largest single file.
 */
function wholesale_async_upload_contexts()
{
	return array(
		'contact' => array(
			'mimes' => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png' => 'image/png',
				'webp' => 'image/webp',
				'heic' => 'image/heic',
				'pdf' => 'application/pdf',
			),
			'max' => min(10 * MB_IN_BYTES, wp_max_upload_size()),
		),
		'quote' => array(
			'mimes' => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png' => 'image/png',
				'pdf' => 'application/pdf',
			),
			'max' => WHOLESALE_QUOTE_FORM_MAX_MB * MB_IN_BYTES,
		),
		'artwork' => array(
			'mimes' => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png' => 'image/png',
				'gif' => 'image/gif',
				'webp' => 'image/webp',
				'pdf' => 'application/pdf',
			),
			'max' => 25 * MB_IN_BYTES,
		),
	);
}

/**
 * The private holding folder, created with deny rules on first use.
 */
function wholesale_async_upload_dir()
{
	$uploads = wp_upload_dir(null, false);
	$dir = trailingslashit($uploads['basedir']) . 'wholesale-pending';
	if (!is_dir($dir)) {
		wp_mkdir_p($dir);
		@file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
		@file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n");
	}
	return $dir;
}

function wholesale_async_upload_fail($message, $status = 400)
{
	wp_send_json_error(array('message' => $message), $status);
}

function wholesale_ajax_async_upload()
{
	nocache_headers();
	$contexts = wholesale_async_upload_contexts();
	$context = isset($_POST['context']) ? sanitize_key(wp_unslash($_POST['context'])) : '';
	if (!isset($contexts[$context])) {
		wholesale_async_upload_fail('Unknown upload.');
	}
	$rules = $contexts[$context];

	// 60 files an hour per IP is plenty for a person and stops a script filling the disk.
	$ip_key = 'wholesale_async_up_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
	$count = (int) get_transient($ip_key);
	if ($count >= 60) {
		wholesale_async_upload_fail('Too many uploads. Please try again later.', 429);
	}
	set_transient($ip_key, $count + 1, HOUR_IN_SECONDS);

	$file = $_FILES['file'] ?? null;
	if (!$file || UPLOAD_ERR_OK !== (int) $file['error'] || !is_uploaded_file($file['tmp_name'])) {
		wholesale_async_upload_fail('The file could not be uploaded. Please try again.');
	}
	if ((int) $file['size'] > $rules['max']) {
		wholesale_async_upload_fail(sprintf('Files must be under %s.', size_format($rules['max'])));
	}
	$checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $rules['mimes']);
	if (empty($checked['ext']) || empty($checked['type'])) {
		wholesale_async_upload_fail('That file type isn’t supported.');
	}

	$dir = wholesale_async_upload_dir();
	wholesale_async_upload_cleanup($dir);
	$token = wp_generate_password(32, false);
	if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $token . '.bin')) {
		wholesale_async_upload_fail('The file could not be saved. Please try again.', 500);
	}
	file_put_contents($dir . '/' . $token . '.json', wp_json_encode(array(
		'context' => $context,
		'name' => sanitize_file_name(wp_basename(wp_unslash($file['name']))),
		'type' => $checked['type'],
		'size' => (int) $file['size'],
	)));

	wp_send_json_success(array('token' => $token));
}
add_action('wp_ajax_wholesale_async_upload', 'wholesale_ajax_async_upload');
add_action('wp_ajax_nopriv_wholesale_async_upload', 'wholesale_ajax_async_upload');

/**
 * Delete held files nobody submitted within a day. Runs on roughly 1 in 20 uploads.
 */
function wholesale_async_upload_cleanup($dir)
{
	if (wp_rand(1, 20) !== 1) {
		return;
	}
	foreach ((array) glob($dir . '/*.{bin,json}', GLOB_BRACE) as $path) {
		if ($path && filemtime($path) < time() - DAY_IN_SECONDS) {
			@unlink($path);
		}
	}
}

/**
 * Claim an uploaded token for a form. Returns a $_FILES-style array (with 'async' => true)
 * or null when the token is missing, used, expired or belongs to another form.
 */
function wholesale_async_upload_take($token, $context)
{
	$token = is_string($token) ? $token : '';
	if (!preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
		return null;
	}
	$dir = wholesale_async_upload_dir();
	$meta_path = $dir . '/' . $token . '.json';
	$path = $dir . '/' . $token . '.bin';
	if (!is_file($meta_path) || !is_file($path)) {
		return null;
	}
	$meta = json_decode((string) file_get_contents($meta_path), true);
	@unlink($meta_path);
	if (!is_array($meta) || ($meta['context'] ?? '') !== $context) {
		@unlink($path);
		return null;
	}

	return array(
		'name' => $meta['name'],
		'type' => $meta['type'],
		'tmp_name' => $path,
		'error' => UPLOAD_ERR_OK,
		'size' => (int) $meta['size'],
		'async' => true,
	);
}

/**
 * Claim every token posted under $field (string or array) for $context.
 */
function wholesale_async_upload_take_posted($field, $context)
{
	$tokens = isset($_POST[$field]) ? (array) wp_unslash($_POST[$field]) : array();
	$files = array();
	foreach (array_slice($tokens, 0, 10) as $token) {
		$file = wholesale_async_upload_take($token, $context);
		if ($file) {
			$files[] = $file;
		}
	}
	return $files;
}

/**
 * wp_handle_upload() for a normal post, wp_handle_sideload() for a held file
 * (which isn't an is_uploaded_file() any more).
 */
function wholesale_async_upload_store(array $file, array $overrides)
{
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$overrides['test_form'] = false;
	if (!empty($file['async'])) {
		unset($file['async']);
		$result = wp_handle_sideload($file, $overrides);
		@unlink($file['tmp_name']);
		return $result;
	}
	return wp_handle_upload($file, $overrides);
}

function wholesale_async_upload_script()
{
	if (is_admin()) {
		return;
	}
	$theme_dir = get_template_directory();
	wp_register_script('wholesale-async-upload', get_template_directory_uri() . '/js/async-upload.js', array(), (string) filemtime($theme_dir . '/js/async-upload.js'), true);
	wp_script_add_data('wholesale-async-upload', 'strategy', 'defer');
	wp_localize_script('wholesale-async-upload', 'wholesaleAsyncUpload', array('ajaxUrl' => wp_make_link_relative(admin_url('admin-ajax.php'))));
	if (is_singular('product') || is_page(array('contact', 'banners-displays')) || is_page_template(array('page-channel-letters.php', 'page-channel-letters-ads.php'))) {
		wp_enqueue_script('wholesale-async-upload');
	}
}
add_action('wp_enqueue_scripts', 'wholesale_async_upload_script');
