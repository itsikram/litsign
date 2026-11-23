<?php
/**
 * Site time zone lock: America/Los_Angeles (Pacific, with daylight saving).
 *
 * The Google Ads account runs on Pacific time, the business hours are
 * Pacific, and the offline conversion uploads (inc/ads-uploads.php), sale
 * promotion dates and order/lead timestamps all read the WordPress time
 * zone. A database push or pull between local and live, or a save on
 * Settings > General, must never move the site off Los Angeles, so:
 * - every read of timezone_string returns Los Angeles, whatever the
 *   database holds (core then derives gmt_offset from it, DST included);
 * - every write of timezone_string / gmt_offset is turned back into
 *   Los Angeles / empty, the same values a normal Settings save stores;
 * - if the stored rows ever differ (for example after a database copy),
 *   they are rewritten on the next request;
 * - the Settings > General time zone dropdown is shown as locked.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

define('WHOLESALE_SITE_TIMEZONE', 'America/Los_Angeles');

add_filter('pre_option_timezone_string', 'wholesale_site_timezone_string');
add_filter('pre_update_option_timezone_string', 'wholesale_site_timezone_string');
add_filter('pre_update_option_gmt_offset', '__return_empty_string');

function wholesale_site_timezone_string()
{
	return WHOLESALE_SITE_TIMEZONE;
}

/**
 * Rewrite the stored rows when they differ from the lock. Uses the raw
 * autoloaded values (the read filter above hides the real ones) and a direct
 * query, because update_option() sees no change through the filter.
 */
function wholesale_site_timezone_repair()
{
	global $wpdb;

	$all = wp_load_alloptions();
	$want = array('timezone_string' => WHOLESALE_SITE_TIMEZONE, 'gmt_offset' => '');
	$fix = array();
	foreach ($want as $name => $value) {
		if (isset($all[$name]) && $value === (string) $all[$name]) {
			continue;
		}
		// Not autoloaded or different: check the real row once.
		$row = $wpdb->get_row($wpdb->prepare("SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $name));
		if (!$row || $value !== (string) $row->option_value || !in_array($row->autoload, array('yes', 'on', 'auto-on'), true)) {
			$fix[$name] = $row ? 'update' : 'insert';
		}
	}
	if (!$fix) {
		return;
	}

	foreach ($fix as $name => $action) {
		if ('update' === $action) {
			$wpdb->update($wpdb->options, array('option_value' => $want[$name], 'autoload' => 'on'), array('option_name' => $name));
		} else {
			$wpdb->insert($wpdb->options, array('option_name' => $name, 'option_value' => $want[$name], 'autoload' => 'on'));
		}
	}
	wp_cache_delete('alloptions', 'options');
	wp_cache_delete('timezone_string', 'options');
	wp_cache_delete('gmt_offset', 'options');
}
add_action('init', 'wholesale_site_timezone_repair', 1);

/**
 * Settings > General: show the time zone as locked instead of letting a
 * save look like it worked.
 */
function wholesale_site_timezone_settings_note()
{
	?>
	<script>
	(function () {
		var select = document.getElementById('timezone_string');
		if (!select) {
			return;
		}
		select.value = <?php echo wp_json_encode(WHOLESALE_SITE_TIMEZONE); ?>;
		select.disabled = true;
		var note = document.createElement('p');
		note.className = 'description';
		note.textContent = 'Locked to Los Angeles (Pacific time) by the theme, to match Google Ads and business hours. See inc/site-timezone.php.';
		select.parentNode.appendChild(note);
	})();
	</script>
	<?php
}
add_action('admin_footer-options-general.php', 'wholesale_site_timezone_settings_note');
