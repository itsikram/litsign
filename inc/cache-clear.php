<?php
/**
 * "Clear Cache" button in the admin toolbar (front end and wp-admin).
 *
 * One click clears every cache the site can reach from PHP:
 * - cached transients in the database (Google reviews, live search results,
 *   WordPress update checks, feeds, plugin caches), keeping the few
 *   transients that hold live state such as pending payments;
 * - the object cache (and any persistent object cache drop-in);
 * - page caches of common cache plugins, when one is active on the host;
 * - cached files under wp-content/cache;
 * - PHP OPcache, so edited PHP files are picked up at once;
 * - visitors' browser caches, by adding a new cache-busting stamp to every
 *   local CSS and JS URL.
 *
 * Runs through admin-ajax (the toolbar button) with an admin-post fallback
 * for browsers without JavaScript. Only administrators see or can use it.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

define('WHOLESALE_CACHE_BUSTER_OPTION', 'wholesale_cache_buster');

/**
 * Transient name prefixes that hold live state, not cached copies: a pending
 * payment, an admin's open sign-builder ticket line, an in-progress or saved
 * SEO scan, and the upload and order-tracking rate limits. Clearing these
 * would break a checkout or a ticket that is in progress.
 */
function wholesale_cache_protected_transients()
{
	return array(
		'wholesale_payment_',
		'wholesale_ticket_builder_',
		'wholesale_seo_scan',
		'wholesale_async_up_',
		'wholesale_track_fail_',
	);
}

function wholesale_clear_cache_url()
{
	return wp_nonce_url(admin_url('admin-post.php?action=wholesale_clear_cache'), 'wholesale_clear_cache');
}

function wholesale_admin_bar_clear_cache($admin_bar)
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$admin_bar->add_node(array(
		'id' => 'wholesale-clear-cache',
		'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">' . esc_html__('Clear Cache', 'litsign') . '</span>',
		'href' => wholesale_clear_cache_url(),
		'meta' => array(
			'title' => __('Clear the entire website cache', 'litsign'),
		),
	));
}
add_action('admin_bar_menu', 'wholesale_admin_bar_clear_cache', 105);

/**
 * Delete cached transients from the options table, keeping the protected ones.
 *
 * @return int Number of transients removed.
 */
function wholesale_clear_cache_transients()
{
	global $wpdb;

	$deleted = 0;
	foreach (array('_transient_', '_site_transient_') as $prefix) {
		$where = $wpdb->prepare('option_name LIKE %s', $wpdb->esc_like($prefix) . '%');
		foreach (wholesale_cache_protected_transients() as $protected) {
			$where .= $wpdb->prepare(' AND option_name NOT LIKE %s AND option_name NOT LIKE %s',
				$wpdb->esc_like($prefix . $protected) . '%',
				$wpdb->esc_like($prefix . 'timeout_' . $protected) . '%'
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where is built from prepare() above.
		$deleted += (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE {$where} AND option_name NOT LIKE %s",
			$wpdb->esc_like($prefix . 'timeout_') . '%'
		));
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE {$where}");
		// phpcs:enable
	}

	return $deleted;
}

/**
 * Empty wp-content/cache, keeping the folder itself and its .htaccess files.
 *
 * @return int Number of files removed.
 */
function wholesale_clear_cache_files()
{
	$cache_dir = realpath(WP_CONTENT_DIR . '/cache');
	$content_dir = realpath(WP_CONTENT_DIR);
	if (!$cache_dir || !$content_dir || !is_dir($cache_dir) || 0 !== strpos($cache_dir, $content_dir . DIRECTORY_SEPARATOR)) {
		return 0;
	}

	$deleted = 0;
	try {
		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($cache_dir, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($items as $item) {
			if ($item->isLink()) {
				@unlink($item->getPathname());
			} elseif ($item->isDir()) {
				@rmdir($item->getPathname()); // Fails harmlessly while a kept .htaccess is inside.
			} elseif ('.htaccess' !== $item->getFilename() && @unlink($item->getPathname())) {
				$deleted++;
			}
		}
	} catch (Exception $e) {
		return $deleted;
	}

	return $deleted;
}

/**
 * Ask any active cache plugin to purge its page cache.
 *
 * @return string[] Names of the plugins that were purged.
 */
function wholesale_clear_cache_plugins()
{
	$purged = array();

	if (function_exists('rocket_clean_domain')) {
		rocket_clean_domain();
		if (function_exists('rocket_clean_minify')) {
			rocket_clean_minify();
		}
		$purged[] = 'WP Rocket';
	}
	if (function_exists('w3tc_flush_all')) {
		w3tc_flush_all();
		$purged[] = 'W3 Total Cache';
	}
	if (function_exists('wp_cache_clear_cache')) {
		wp_cache_clear_cache();
		$purged[] = 'WP Super Cache';
	}
	if (defined('LSCWP_V') || has_action('litespeed_purge_all')) {
		do_action('litespeed_purge_all');
		$purged[] = 'LiteSpeed Cache';
	}
	if (function_exists('sg_cachepress_purge_cache')) {
		sg_cachepress_purge_cache();
		$purged[] = 'SiteGround Optimizer';
	}
	if (class_exists('WpFastestCache') && isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache')) {
		$GLOBALS['wp_fastest_cache']->deleteCache(true);
		$purged[] = 'WP Fastest Cache';
	}
	if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
		autoptimizeCache::clearall();
		$purged[] = 'Autoptimize';
	}
	if (has_action('breeze_clear_all_cache')) {
		do_action('breeze_clear_all_cache');
		$purged[] = 'Breeze';
	}
	if (function_exists('nitropack_sdk_purge')) {
		nitropack_sdk_purge(null, null, 'Cleared from the admin toolbar');
		$purged[] = 'NitroPack';
	}
	if (class_exists('WPaaS\\Plugin') && has_action('wpaas_flush_cache')) {
		do_action('wpaas_flush_cache');
		$purged[] = 'GoDaddy';
	}

	return $purged;
}

/**
 * Clear every cache. Returns a summary for the toolbar message.
 *
 * @return array{transients:int,files:int,opcache:bool,plugins:string[]}
 */
function wholesale_clear_all_cache()
{
	$transients = wholesale_clear_cache_transients();

	// A new stamp changes every local CSS/JS URL so browsers fetch fresh copies.
	update_option(WHOLESALE_CACHE_BUSTER_OPTION, (string) time(), true);

	$plugins = wholesale_clear_cache_plugins();
	$files = wholesale_clear_cache_files();

	wp_clean_themes_cache();
	if (function_exists('wp_clean_plugins_cache')) {
		wp_clean_plugins_cache(false);
	}
	wp_cache_flush();

	$opcache = false;
	if (function_exists('opcache_reset')) {
		$opcache = (bool) @opcache_reset();
	}
	clearstatcache(true);

	do_action('wholesale_cache_cleared');

	return array(
		'transients' => $transients,
		'files' => $files,
		'opcache' => $opcache,
		'plugins' => $plugins,
	);
}

function wholesale_clear_cache_summary($result)
{
	$parts = array(
		sprintf(_n('%s cached item', '%s cached items', $result['transients'], 'litsign'), number_format_i18n($result['transients'])),
		sprintf(_n('%s cache file', '%s cache files', $result['files'], 'litsign'), number_format_i18n($result['files'])),
		__('object cache', 'litsign'),
		__('browser CSS/JS', 'litsign'),
	);
	if ($result['opcache']) {
		$parts[] = __('PHP OPcache', 'litsign');
	}
	foreach ($result['plugins'] as $plugin) {
		$parts[] = $plugin;
	}

	return sprintf(__('Cache cleared: %s.', 'litsign'), implode(', ', $parts));
}

function wholesale_ajax_clear_cache()
{
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => __('You are not allowed to clear the cache.', 'litsign')), 403);
	}
	if (!check_ajax_referer('wholesale_clear_cache', 'nonce', false)) {
		wp_send_json_error(array('message' => __('Your session expired. Reload the page and try again.', 'litsign')), 403);
	}

	$result = wholesale_clear_all_cache();
	wp_send_json_success(array('message' => wholesale_clear_cache_summary($result)) + $result);
}
add_action('wp_ajax_wholesale_clear_cache', 'wholesale_ajax_clear_cache');

/**
 * No-JavaScript fallback: clear, then return to the page the click came from.
 */
function wholesale_admin_post_clear_cache()
{
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You are not allowed to clear the cache.', 'litsign'), 403);
	}
	check_admin_referer('wholesale_clear_cache');

	wholesale_clear_all_cache();

	$back = wp_get_referer();
	if (!$back) {
		$back = admin_url();
	}
	wp_safe_redirect(add_query_arg('wholesale_cache_cleared', '1', $back));
	exit;
}
add_action('admin_post_wholesale_clear_cache', 'wholesale_admin_post_clear_cache');

function wholesale_clear_cache_admin_notice()
{
	if (empty($_GET['wholesale_cache_cleared']) || !current_user_can('manage_options')) {
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('The website cache was cleared.', 'litsign') . '</p></div>';
}
add_action('admin_notices', 'wholesale_clear_cache_admin_notice');

/**
 * Add the cache-busting stamp to local stylesheet and script URLs.
 */
function wholesale_cache_bust_asset_src($src)
{
	$stamp = get_option(WHOLESALE_CACHE_BUSTER_OPTION);
	if (!$stamp || !is_string($src) || '' === $src) {
		return $src;
	}

	$host = wp_parse_url($src, PHP_URL_HOST);
	if ($host && $host !== wp_parse_url(home_url(), PHP_URL_HOST) && $host !== wp_parse_url(site_url(), PHP_URL_HOST)) {
		return $src;
	}

	return add_query_arg('cb', rawurlencode($stamp), $src);
}
add_filter('style_loader_src', 'wholesale_cache_bust_asset_src', 20);
add_filter('script_loader_src', 'wholesale_cache_bust_asset_src', 20);

/**
 * Toolbar script: clear through admin-ajax and show the result in the button.
 */
function wholesale_clear_cache_toolbar_script()
{
	if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
		return;
	}

	$config = array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('wholesale_clear_cache'),
		'clearing' => __('Clearing…', 'litsign'),
		'done' => __('Cache Cleared', 'litsign'),
		'failed' => __('Clear Failed', 'litsign'),
	);
	?>
	<style>
		#wpadminbar #wp-admin-bar-wholesale-clear-cache .ab-icon:before { content: "\f463"; top: 2px; }
		#wpadminbar #wp-admin-bar-wholesale-clear-cache.is-busy .ab-icon:before { display: inline-block; animation: wholesale-cache-spin 1s linear infinite; }
		#wpadminbar #wp-admin-bar-wholesale-clear-cache.is-done .ab-icon:before { content: "\f147"; color: #68de7c; }
		#wpadminbar #wp-admin-bar-wholesale-clear-cache.is-failed .ab-icon:before { content: "\f534"; color: #f86368; }
		@keyframes wholesale-cache-spin { to { transform: rotate(360deg); } }
		@media screen and (max-width: 782px) {
			#wpadminbar li#wp-admin-bar-wholesale-clear-cache { display: block; }
			#wpadminbar #wp-admin-bar-wholesale-clear-cache .ab-icon:before { top: 0; }
		}
	</style>
	<script>
		(function () {
			var config = <?php echo wp_json_encode($config); ?>;
			document.addEventListener('click', function (event) {
				var link = event.target.closest && event.target.closest('#wp-admin-bar-wholesale-clear-cache > a');
				if (!link || !window.fetch) {
					return;
				}
				event.preventDefault();

				var item = link.parentNode;
				var label = link.querySelector('.ab-label');
				if (item.classList.contains('is-busy')) {
					return;
				}
				var original = label.textContent;
				item.classList.remove('is-done', 'is-failed');
				item.classList.add('is-busy');
				label.textContent = config.clearing;

				var body = new URLSearchParams({ action: 'wholesale_clear_cache', nonce: config.nonce });
				fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
					.then(function (response) {
						return response.json().catch(function () { return { success: false }; });
					})
					.then(function (json) {
						var ok = json && json.success;
						var message = json && json.data && json.data.message;
						item.classList.add(ok ? 'is-done' : 'is-failed');
						label.textContent = ok ? config.done : config.failed;
						link.setAttribute('title', message || '');
						if (message && window.console) {
							console.info(message);
						}
					})
					.catch(function () {
						item.classList.add('is-failed');
						label.textContent = config.failed;
					})
					.then(function () {
						item.classList.remove('is-busy');
						setTimeout(function () {
							item.classList.remove('is-done', 'is-failed');
							label.textContent = original;
						}, 4000);
					});
			});
		})();
	</script>
	<?php
}
add_action('admin_footer', 'wholesale_clear_cache_toolbar_script');
add_action('wp_footer', 'wholesale_clear_cache_toolbar_script');
