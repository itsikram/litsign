<?php
/**
 * SEO menu: lets the owner control titles, descriptions, schema, sitemaps and
 * redirects from the dashboard instead of the theme code.
 *
 * Settings override, never replace: every field is empty by default, and an
 * empty field leaves the theme's own output (functions.php, inc/seo.php,
 * inc/seo-content.php, inc/guide-pages.php) exactly as it is.
 *
 * Storage:
 * - one option per tab, wholesale_seo_{tab}; only the ones the front end reads
 *   are autoloaded, so a page view costs no extra database query;
 * - per-page values in post meta / term meta named _wholesale_seo_{field};
 * - wholesale_seo_overrides: which posts and terms have per-page values, so
 *   the front end never reads meta for pages that have none.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

// define('WHOLESALE_SEO_ADMIN_OFF', true) in wp-config.php switches the whole
// module off; the theme then prints exactly what it did before it existed.
if (defined('WHOLESALE_SEO_ADMIN_OFF') && WHOLESALE_SEO_ADMIN_OFF) {
	return;
}

/**
 * Options for each tab and whether the front end reads them (autoload).
 *
 * @return array Option name => autoload.
 */
function wholesale_seo_options()
{
	return array(
		'wholesale_seo_general' => true,
		'wholesale_seo_business' => true,
		'wholesale_seo_overrides' => true,
	);
}

/**
 * A tab's saved settings. Empty values are never stored.
 *
 * @return array
 */
function wholesale_seo_settings($tab)
{
	$value = get_option('wholesale_seo_' . $tab, array());
	return is_array($value) ? $value : array();
}

/**
 * One saved setting, or '' when it is empty (keep the theme's behaviour).
 *
 * @return mixed
 */
function wholesale_seo_opt($tab, $key)
{
	$settings = wholesale_seo_settings($tab);
	return isset($settings[$key]) ? $settings[$key] : '';
}

/**
 * Create the autoloaded options once, from wp-admin, so front-end reads always
 * hit the autoload cache instead of querying for a missing option.
 */
function wholesale_seo_ensure_options()
{
	foreach (wholesale_seo_options() as $name => $autoload) {
		if (false === get_option($name, false)) {
			add_option($name, array(), '', $autoload ? 'yes' : 'no');
		}
	}
}
add_action('admin_init', 'wholesale_seo_ensure_options', 1);

/**
 * Per-page fields, stored as _wholesale_seo_{key}.
 *
 * @return array Key => type.
 */
function wholesale_seo_object_fields()
{
	return array(
		'title' => 'text',
		'description' => 'textarea',
		'focus_keyword' => 'text',
		'canonical' => 'url',
		'noindex' => 'flag',
		'nofollow' => 'flag',
		'exclude_sitemap' => 'flag',
		'og_title' => 'text',
		'og_description' => 'textarea',
		'og_image' => 'url',
	);
}

/**
 * Sanitize one per-page value.
 */
function wholesale_seo_sanitize_object_value($key, $value)
{
	$fields = wholesale_seo_object_fields();
	$type = isset($fields[$key]) ? $fields[$key] : 'text';
	$value = is_scalar($value) ? (string) $value : '';

	switch ($type) {
		case 'flag':
			return $value ? '1' : '';
		case 'url':
			return esc_url_raw(trim($value), array('http', 'https'));
		case 'textarea':
			return trim(preg_replace('/\s+/', ' ', sanitize_textarea_field($value)));
		default:
			return trim(sanitize_text_field($value));
	}
}

/**
 * Save per-page values for a post ('post') or a term ('term') and keep the
 * overrides index in step. Keys missing from $values are left alone.
 */
function wholesale_seo_save_object_values($object_type, $object_id, array $values)
{
	$object_id = (int) $object_id;
	if (!$object_id || !in_array($object_type, array('post', 'term'), true)) {
		return;
	}

	foreach ($values as $key => $value) {
		if (!array_key_exists($key, wholesale_seo_object_fields())) {
			continue;
		}
		$value = wholesale_seo_sanitize_object_value($key, $value);
		$meta_key = '_wholesale_seo_' . $key;
		if ('' === $value) {
			'post' === $object_type ? delete_post_meta($object_id, $meta_key) : delete_term_meta($object_id, $meta_key);
		} else {
			'post' === $object_type ? update_post_meta($object_id, $meta_key, wp_slash($value)) : update_term_meta($object_id, $meta_key, wp_slash($value));
		}
	}

	wholesale_seo_reindex_object($object_type, $object_id);
}

/**
 * Record which per-page fields an object has, or drop it from the index.
 */
function wholesale_seo_reindex_object($object_type, $object_id)
{
	$index = wholesale_seo_settings('overrides');
	$present = array();

	foreach (array_keys(wholesale_seo_object_fields()) as $key) {
		$value = 'post' === $object_type
			? get_post_meta($object_id, '_wholesale_seo_' . $key, true)
			: get_term_meta($object_id, '_wholesale_seo_' . $key, true);
		if ('' !== (string) $value) {
			$present[] = $key;
		}
	}

	if ($present) {
		$index[$object_type][$object_id] = $present;
	} else {
		unset($index[$object_type][$object_id]);
		if (empty($index[$object_type])) {
			unset($index[$object_type]);
		}
	}

	update_option('wholesale_seo_overrides', $index, true);
}

add_action('deleted_post', function ($post_id) {
	$index = wholesale_seo_settings('overrides');
	if (isset($index['post'][$post_id])) {
		unset($index['post'][$post_id]);
		update_option('wholesale_seo_overrides', $index, true);
	}
});

add_action('delete_term', function ($term_id) {
	$index = wholesale_seo_settings('overrides');
	if (isset($index['term'][$term_id])) {
		unset($index['term'][$term_id]);
		update_option('wholesale_seo_overrides', $index, true);
	}
});

/**
 * Header that marks the SEO scanner's own requests, so the 404 log, redirect
 * hit counters and visitor stats can skip them. Signed with a site salt so a
 * visitor can't fake it.
 */
function wholesale_seo_scan_signature()
{
	return hash_hmac('sha256', 'wholesale-seo-scan', wp_salt('auth'));
}

function wholesale_seo_is_scan_request()
{
	return isset($_SERVER['HTTP_X_WHOLESALE_SEO_SCAN'])
		&& hash_equals(wholesale_seo_scan_signature(), (string) $_SERVER['HTTP_X_WHOLESALE_SEO_SCAN']);
}

require_once __DIR__ . '/frontend.php';

if (is_admin()) {
	require_once __DIR__ . '/menu.php';
	require_once __DIR__ . '/tab-dashboard.php';
	require_once __DIR__ . '/tab-general.php';
	require_once __DIR__ . '/tab-business.php';
	require_once __DIR__ . '/metabox.php';
	require_once __DIR__ . '/tab-bulk.php';
}
