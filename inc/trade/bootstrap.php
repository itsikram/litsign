<?php
/**
 * Trade (wholesale) site mode.
 *
 * One theme serves both sites. The wholesale site sets
 * define('SFS_SITE_MODE', 'trade'); in its own wp-config.php. A constant, not
 * an option, so a DB push or pull can never flip a site's mode. Without it the
 * theme behaves exactly as the retail site.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/** True on the wholesale (trade-only) site. */
function wholesale_is_trade_site()
{
	return defined('SFS_SITE_MODE') && 'trade' === SFS_SITE_MODE;
}

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
