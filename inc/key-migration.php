<?php
/**
 * Moves stored names from the old "wholesale" prefix to "retails".
 *
 * The theme, its plugins and their stored data were renamed together. A site that still
 * has the old names (live right after an upload, or local after a pull from live) gets
 * them moved here, before anything else in the theme reads them:
 * tables, options, meta keys, cron hooks, rewrite rules and role capabilities. Old
 * transients are dropped (they are caches). Each step checks its own condition with
 * cheap indexed queries, so nothing happens once a site is moved, and a push or pull
 * that brings old names back is healed on the next request.
 *
 * When an old and a new copy both exist, the old one wins: it holds the site's real
 * data, while the new one can only be a default written in between.
 *
 * Also: old cookies are read under their new names, and old admin links
 * (admin.php?page=<old>-...) redirect to the renamed pages.
 */

if (!defined('ABSPATH')) {
	exit;
}

function retails_key_migration_run()
{
	static $ran = false;
	if ($ran) {
		return;
	}
	$ran = true;

	global $wpdb;
	$old = 'whole' . 'sale';
	$new = 'retails';
	$flush_cache = false;

	// 1. Tables: wp_<old>_x -> wp_retails_x.
	$old_prefix = $wpdb->prefix . $old . '_';
	$tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($old_prefix) . '%'));
	foreach ((array) $tables as $table) {
		$target = $wpdb->prefix . $new . '_' . substr($table, strlen($old_prefix));
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($target)))) {
			if (0 === (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$target}`")) {
				$wpdb->query("DROP TABLE `{$target}`");
			} else {
				$wpdb->query("RENAME TABLE `{$target}` TO `{$target}_dup" . gmdate('ymdHis') . '`');
			}
		}
		$wpdb->query("RENAME TABLE `{$table}` TO `{$target}`");
	}

	// 2. Options. Transients are caches: drop them.
	$names = $wpdb->get_col($wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like($old . '_') . '%',
		$wpdb->esc_like('_transient_' . $old . '_') . '%',
		$wpdb->esc_like('_transient_timeout_' . $old . '_') . '%',
		$wpdb->esc_like('_site_transient_' . $old . '_') . '%',
		$wpdb->esc_like('_site_transient_timeout_' . $old . '_') . '%'
	));
	foreach ((array) $names as $name) {
		if (0 === strpos($name, '_')) {
			$wpdb->delete($wpdb->options, array('option_name' => $name));
		} else {
			$target = $new . substr($name, strlen($old));
			$wpdb->delete($wpdb->options, array('option_name' => $target));
			$wpdb->update($wpdb->options, array('option_name' => $target), array('option_name' => $name));
		}
		$flush_cache = true;
	}

	// 3. Meta keys (_<old>_x and <old>_x; any position in user meta, e.g. screen options).
	$meta_tables = array(
		$wpdb->postmeta => 'post_id',
		$wpdb->termmeta => 'term_id',
		$wpdb->commentmeta => 'comment_id',
		$wpdb->usermeta => 'user_id',
	);
	foreach ($meta_tables as $table => $col) {
		if ($wpdb->usermeta === $table) {
			$where = $wpdb->prepare('meta_key LIKE %s', '%' . $wpdb->esc_like($old) . '%');
		} else {
			$where = $wpdb->prepare('(meta_key LIKE %s OR meta_key LIKE %s)', $wpdb->esc_like($old) . '%', $wpdb->esc_like('_' . $old) . '%');
		}
		if (!$wpdb->get_var("SELECT 1 FROM {$table} WHERE {$where} LIMIT 1")) {
			continue;
		}
		$o_where = str_replace('meta_key', 'o.meta_key', $where);
		$wpdb->query($wpdb->prepare(
			"DELETE n FROM {$table} n JOIN {$table} o ON o.{$col} = n.{$col} AND n.meta_key = REPLACE(o.meta_key, %s, %s) WHERE {$o_where}",
			$old,
			$new
		));
		$wpdb->query($wpdb->prepare("UPDATE {$table} SET meta_key = REPLACE(meta_key, %s, %s) WHERE {$where}", $old, $new));
		$flush_cache = true;
	}

	if ($flush_cache) {
		wp_cache_flush();
	}

	// 4. Cron hooks.
	$cron = _get_cron_array();
	$cron_changed = false;
	if (is_array($cron)) {
		foreach ($cron as $timestamp => $hooks) {
			if (!is_array($hooks)) {
				continue;
			}
			foreach ($hooks as $hook => $events) {
				if (false === strpos($hook, $old)) {
					continue;
				}
				$renamed = str_replace($old, $new, $hook);
				$cron[$timestamp][$renamed] = isset($cron[$timestamp][$renamed]) ? $cron[$timestamp][$renamed] + $events : $events;
				unset($cron[$timestamp][$hook]);
				$cron_changed = true;
			}
		}
		if ($cron_changed) {
			_set_cron_array($cron);
		}
	}

	// 5. Rewrite rules still pointing at old query vars: rebuild once the theme has added its rules.
	$rules = get_option('rewrite_rules');
	if (is_array($rules) && false !== strpos(implode("\n", $rules), $old . '_')) {
		add_action('init', function () {
			flush_rewrite_rules(false);
		}, 9999);
	}

	// 6. Role capabilities.
	$roles_option = $wpdb->prefix . 'user_roles';
	$roles = get_option($roles_option);
	if (is_array($roles) && false !== strpos(serialize($roles), $old)) {
		foreach ($roles as $role => $data) {
			if (empty($data['capabilities']) || !is_array($data['capabilities'])) {
				continue;
			}
			$caps = array();
			foreach ($data['capabilities'] as $cap => $grant) {
				$caps[str_replace($old, $new, $cap)] = $grant;
			}
			$roles[$role]['capabilities'] = $caps;
		}
		update_option($roles_option, $roles);
	}

	// 7. Old cookies answer to their new names for as long as they live.
	foreach ($_COOKIE as $name => $value) {
		if (is_string($name) && false !== strpos($name, $old)) {
			$renamed = str_replace($old, $new, $name);
			if (!isset($_COOKIE[$renamed])) {
				$_COOKIE[$renamed] = $value;
			}
		}
	}

	// 8. Old admin links (bookmarks, staff emails, push notifications) open the renamed pages.
	if (is_admin() && isset($_GET['page']) && is_string($_GET['page']) && false !== strpos($_GET['page'], $old)) {
		add_action('admin_menu', function () use ($old, $new) {
			$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
			if ('' !== $uri) {
				wp_safe_redirect(str_replace($old, $new, $uri), 301);
				exit;
			}
		}, 0);
	}
}

/**
 * Keeps the wholesale site's name and logo off this site.
 *
 * This site began as a copy of the wholesale site, and its settings travel with every
 * settings push or pull. On 2026-11-20 the Site Title came back as "Storefront Sign
 * Wholesale" and the Site Logo pointed at an attachment that doesn't exist, so every
 * logo fell back to the stored default: uploads/2026/10/logo.png, the
 * "StorefrontSign Wholesale.com" artwork. So:
 * - a Site Title with "wholesale" in it becomes "Storefront Sign Online";
 * - a Site Logo that is missing, not an image, or a wholesale file becomes the
 *   Storefront Sign Online logo, found by its file because attachment ids differ
 *   between local and live;
 * - a default logo that is a wholesale file is replaced the same way.
 * The check runs again only when the title, the logo or the site address changes.
 */
function retails_brand_guard()
{
	$stamp = static function () {
		return md5(home_url('/') . '|' . get_option('blogname') . '|' . (int) get_theme_mod('custom_logo'));
	};
	if (get_option('retails_brand_guard') === $stamp()) {
		return;
	}

	$changed = false;
	if (false !== stripos((string) get_option('blogname'), 'whole' . 'sale')) {
		update_option('blogname', 'Storefront Sign Online');
		$changed = true;
	}

	$retail = null;
	$retail_logo = static function () use (&$retail) {
		if (null === $retail) {
			$retail = retails_brand_retail_logo_id();
		}
		return $retail;
	};

	if (!retails_brand_logo_ok((int) get_theme_mod('custom_logo')) && $retail_logo()) {
		set_theme_mod('custom_logo', $retail);
		update_option('site_logo', $retail);
		update_option('retails_site_logo', $retail);
		$changed = true;
	}

	$defaults = get_option('retails_image_defaults', array());
	if (is_array($defaults) && !empty($defaults['site_logo']) && !retails_brand_logo_ok((int) $defaults['site_logo']) && $retail_logo()) {
		$defaults['site_logo'] = $retail;
		update_option('retails_image_defaults', $defaults, false);
		$changed = true;
	}

	if ($changed && function_exists('sfs_page_cache_queue_purge')) {
		sfs_page_cache_queue_purge();
	}
	update_option('retails_brand_guard', $stamp(), true);
}
add_action('init', 'retails_brand_guard', 1);

/**
 * Customer role: new registrations become "Customer" (read-only access, like Subscriber).
 * The role is created if missing. The default is switched once, so a different choice
 * made later in Settings → General is kept.
 */
function retails_customer_role_default()
{
	if (!get_role('customer')) {
		add_role('customer', __('Customer', 'tr-retails'), array('read' => true));
	}
	if (!get_option('retails_default_role_customer') && get_role('customer')) {
		update_option('default_role', 'customer');
		update_option('retails_default_role_customer', 1, true);
	}
}
add_action('init', 'retails_customer_role_default', 2);

/**
 * True for an image attachment that is not one of the wholesale site's logo files.
 */
function retails_brand_logo_ok($id)
{
	if ($id <= 0 || !wp_attachment_is_image($id)) {
		return false;
	}
	$file = (string) get_post_meta($id, '_wp_attached_file', true);
	return '2026/10/logo.png' !== $file && false === stripos($file, 'whole' . 'sale');
}

/**
 * Site icon (browser tab, phone home screen): the red "S" tile from the logo, served
 * from the theme's img/brand/site-icon-{32,180,192,270,512}.png. The 32 px file is the
 * simpler, brighter tab version. Theme files, so a settings push or pull can't point the
 * icon at an attachment the other site doesn't have.
 * Used when no Site Icon is chosen, or when the chosen one is the old icon carried over
 * from the wholesale copy ("StorefrontSignWholesale-Icon") or a copy of this same tile.
 * Any other Site Icon picked in the Customizer is left alone.
 */
function retails_brand_icon_url($url, $size)
{
	static $use = null;
	if (null === $use) {
		$id = (int) get_option('site_icon');
		$file = $id ? (string) get_post_meta($id, '_wp_attached_file', true) : '';
		$use = (!$id || '' === $file || false !== stripos($file, 'whole' . 'sale') || false !== strpos($file, 'storefront-sign-site-icon-s-tile'))
			&& is_readable(get_template_directory() . '/img/brand/site-icon-512.png');
	}
	if (!$use) {
		return $url;
	}
	foreach (array(32, 180, 192, 270, 512) as $px) {
		if ((int) $size <= $px) {
			break;
		}
	}
	return get_template_directory_uri() . '/img/brand/site-icon-' . $px . '.png';
}
add_filter('get_site_icon_url', 'retails_brand_icon_url', 10, 2);

/**
 * The Storefront Sign Online logo in this site's Media Library: the newest design
 * first, then the older ones. 0 when none is there.
 */
function retails_brand_retail_logo_id()
{
	global $wpdb;
	$files = array(
		'2026/11/Dimensional-StorefrontSign-Channel-Letter-Logo-v2.png',
		'2026/11/Dimensional-StorefrontSign-Channel-Letter-Logo.png',
		'2024/12/StorefrontSignOnline.com-logo1-1.png',
	);
	foreach ($files as $file) {
		$ids = $wpdb->get_col($wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_attached_file' WHERE p.post_type = 'attachment' AND m.meta_value = %s ORDER BY p.ID",
			$file
		));
		foreach ($ids as $id) {
			if (retails_brand_logo_ok((int) $id)) {
				return (int) $id;
			}
		}
	}
	return 0;
}
