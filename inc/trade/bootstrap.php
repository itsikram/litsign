<?php
/**
 * Trade (wholesale) site mode.
 *
 * One theme serves both sites. The wholesale site sets
 * define('SFS_SITE_MODE', 'trade'); in its own wp-config.php. A constant, not
 * an option, so a DB push or pull can never flip a site's mode. Without it the
 * theme behaves exactly as the retail site, except on the wholesale domain:
 * there a missing constant fails closed (trade mode, prices gated) instead of
 * silently making every price public.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Home hosts (without www.) that are always the trade site when SFS_SITE_MODE is missing. */
function wholesale_trade_hosts()
{
	return array('storefrontsignwholesale.com', 'storefrontsignwholesales.com');
}

/** Host of the stored home URL, lower case, without www. */
function wholesale_trade_home_host()
{
	static $host = null;
	if (null === $host) {
		$host = preg_replace('/^www\./', '', strtolower((string) wp_parse_url((string) get_option('home'), PHP_URL_HOST)));
	}
	return $host;
}

/** True on the wholesale (trade-only) site. The constant wins when defined. */
function wholesale_is_trade_site()
{
	if (defined('SFS_SITE_MODE')) {
		return 'trade' === SFS_SITE_MODE;
	}
	return in_array(wholesale_trade_home_host(), wholesale_trade_hosts(), true);
}

/** Tell admins when the wholesale domain runs on the host fallback (constant missing from wp-config.php). */
add_action('admin_notices', static function () {
	if (defined('SFS_SITE_MODE') || !current_user_can('manage_options') || !wholesale_is_trade_site()) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html("Trade mode is on because of this site's domain, but SFS_SITE_MODE is missing from wp-config.php. Add define('SFS_SITE_MODE', 'trade'); above the wp-settings.php require.") . '</p></div>';
});

/**
 * Whether this visitor may see prices. Always true on the retail site; on the
 * trade site only for approved trade accounts and staff. Every price surface
 * calls this on the server (never hide prices with CSS alone).
 */
function wholesale_trade_can_see_prices($user_id = null)
{
	if (!wholesale_is_trade_site()) {
		return true;
	}
	return function_exists('wholesale_trade_user_is_approved') && wholesale_trade_user_is_approved($user_id);
}

/** Whether this visitor may add to cart, check out and pay. Same rule as prices. */
function wholesale_trade_can_order($user_id = null)
{
	return wholesale_trade_can_see_prices($user_id);
}

/** Load the trade modules. Called from functions.php after the shared includes. */
function wholesale_trade_boot()
{
	if (!wholesale_is_trade_site()) {
		return;
	}
	$dir = __DIR__;
	foreach (array('settings', 'accounts', 'registration', 'admin', 'gate', 'seo', 'content') as $module) {
		require_once $dir . '/' . $module . '.php';
	}
}
