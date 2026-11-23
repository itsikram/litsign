<?php
/**
 * Product group buttons ("Graphic + Frame" / "Graphic Only", "Flag + Pole" / "Flag Only"...),
 * B2Sign style. The products stay separate (own price, options, cart line, feed item, URL,
 * orders), but a button swaps the other product into the page without reloading
 * (js/product-group-swap.js) and the address bar shows the group's first product with a key:
 *
 *   /product/straight-tension-fabric-displays-graphic-frame/?graphic_only=true
 *
 * Opening such a link renders that member on the first product's URL. Each product's own URL
 * keeps working unchanged (Merchant Center feed, Google Ads, old links). The canonical tag of
 * a member stays its own (a partial product points at its kit, inc/seo-content.php).
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * When the group buttons last changed on the product pages: the product sitemap's <lastmod>
 * for grouped products (inc/product-guides.php retails_product_content_modified()). Theme
 * code, so bump it by hand when the buttons change, never into the future.
 */
define('RETAILS_PRODUCT_GROUPS_REVISED', '2026-11-11T21:00:00+00:00');

/**
 * Every product's group list (_product_group_data), read once per request.
 *
 * @return array product ID => list of array(slug, title)
 */
function retails_product_group_lists()
{
	static $lists = null;
	if (null !== $lists) {
		return $lists;
	}

	global $wpdb;
	$lists = array();
	$rows = $wpdb->get_results(
		"SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = '_product_group_data' AND p.post_type = 'product' AND p.post_status = 'publish'
		ORDER BY pm.post_id ASC"
	);
	foreach ((array) $rows as $row) {
		$data = json_decode((string) $row->meta_value, true);
		if (!is_array($data)) {
			continue;
		}
		$list = array();
		foreach ($data as $entry) {
			$slug = is_array($entry) && isset($entry['slug']) ? trim((string) $entry['slug']) : '';
			if ('' === $slug || 'null' === $slug) {
				continue;
			}
			$list[] = array('slug' => $slug, 'title' => isset($entry['title']) ? (string) $entry['title'] : '');
		}
		if ($list) {
			$lists[(int) $row->post_id] = $list;
		}
	}

	return $lists;
}

/**
 * The URL key for a group member: its button title ("Graphic Only" => graphic_only, "Hardware
 * Only" => hardware_only), or its variant label when only that says "Only" (button "Banner",
 * label "Banner Only" => banner_only), else its slug; never one another member has (the x-stand
 * group titles both buttons "Stand + Graphic").
 */
function retails_product_group_key($title, $product_id, array $used)
{
	$keyify = function ($text) {
		return str_replace('-', '_', sanitize_title(wp_strip_all_tags((string) $text)));
	};
	$title_key = $keyify($title);
	$label_key = $keyify(function_exists('retails_seo_product_variant_label') ? retails_seo_product_variant_label($product_id) : '');
	$only = function ($key) {
		return '_only' === substr($key, -5);
	};
	$candidates = $only($label_key) && !$only($title_key) ? array($label_key, $title_key) : array($title_key, $label_key);
	$candidates[] = $keyify(get_post_field('post_name', $product_id));
	foreach ($candidates as $key) {
		if ('' !== $key && !in_array($key, $used, true)) {
			return $key;
		}
	}

	return 'product_' . (int) $product_id;
}

/**
 * The group shown on a product page: the product's own list, or, when it has none, the first
 * list that names it (a kit whose partial lists it, e.g. Custom Pole Flag + Pole).
 *
 * @return array[] Members in list order: id, slug, title, key ('' for the first), url.
 *                 Empty unless at least two members are published products.
 */
function retails_product_group($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}

	$lists = retails_product_group_lists();
	$list = isset($lists[$product_id]) ? $lists[$product_id] : array();
	if (!$list) {
		$slug = (string) get_post_field('post_name', $product_id);
		foreach ($lists as $other) {
			if (in_array($slug, wp_list_pluck($other, 'slug'), true)) {
				$list = $other;
				break;
			}
		}
	}

	$members = array();
	$used = array();
	$seen = array();
	foreach ($list as $entry) {
		$post = get_page_by_path($entry['slug'], OBJECT, 'product');
		if (!$post || 'publish' !== $post->post_status || isset($seen[$post->ID])) {
			continue;
		}
		$seen[$post->ID] = true;
		// The first member's link has no key, but its title still claims one.
		$key = retails_product_group_key($entry['title'], $post->ID, $used);
		$used[] = $key;
		if (!$members) {
			$key = '';
		}
		$members[] = array(
			'id' => (int) $post->ID,
			'slug' => $post->post_name,
			'title' => $entry['title'],
			'key' => $key,
		);
	}
	if (count($members) < 2) {
		return $cache[$product_id] = array();
	}

	$home = get_permalink($members[0]['id']);
	foreach ($members as $i => $member) {
		$members[$i]['url'] = '' === $member['key'] ? $home : add_query_arg($member['key'], 'true', $home);
	}

	return $cache[$product_id] = $members;
}

/**
 * The group on the current product page: the group of the product the URL names (with
 * ?graphic_only=true another member is shown on that URL).
 */
function retails_product_group_for_page()
{
	$page_id = !empty($GLOBALS['retails_product_group_page']) ? (int) $GLOBALS['retails_product_group_page'] : (int) get_queried_object_id();

	return $page_id ? retails_product_group($page_id) : array();
}

/**
 * Where the swap script fetches a member's product block: the member's own URL (a static page
 * from the page cache for visitors on the live site, so the swap is quick) when that page shows
 * the same group, else the group link (?graphic_only=true pages always run WordPress).
 */
function retails_product_group_fetch_url(array $member, array $members)
{
	$own = retails_product_group($member['id']);

	return wp_list_pluck($own, 'id') === wp_list_pluck($members, 'id') ? get_permalink($member['id']) : $member['url'];
}

/**
 * Group members whose page heading repeated another member's ("Yard Sign and H-Stake" on
 * both the kit and the sign-only page), by slug. Others get "Name (Button)" when they repeat.
 */
function retails_product_group_fixed_titles()
{
	return array(
		'yard-sign-only' => 'Yard Sign Only (No H-Stake)',
		'standard-signicade-white-frame-only' => 'Replacement Graphic for the Standard Signicade A-Frame (White)',
		'sd-retractable-sd-retractable-insert-only' => 'Replacement Graphic for the SD Retractable Banner Stand',
	);
}

/**
 * A product's heading before group titles: its channel letter heading or search name, or
 * the partial product's own ("Replacement Graphic for the X-Stand Banner", inc/product-kit.php).
 * Same order as single-product.php.
 */
function retails_product_base_heading($product_id)
{
	$kit = function_exists('retails_product_kit_info') ? retails_product_kit_info($product_id) : null;
	if ($kit && !empty($kit['heading'])) {
		return $kit['heading'];
	}
	$channel_letter = function_exists('retails_seo_channel_letter_product') ? retails_seo_channel_letter_product($product_id) : null;
	if (!empty($channel_letter['heading'])) {
		return $channel_letter['heading'];
	}

	return function_exists('retails_seo_product_search_name') ? retails_seo_product_search_name($product_id) : get_the_title($product_id);
}

/**
 * Each member's own title in the product's group: the "Title in its product group" field
 * when filled in, else the fixed title above, else its heading, with the button title added
 * when an earlier member already has that heading ("Real Estate A-Frame (Sign Only)").
 *
 * @return array product ID => plain-text title. Empty when the product has no group.
 */
function retails_product_group_titles($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}

	$fixed = retails_product_group_fixed_titles();
	$titles = array();
	$used = array();
	foreach (retails_product_group($product_id) as $member) {
		$title = trim(wp_strip_all_tags((string) get_post_meta($member['id'], '_product_group_title', true)));
		if ('' === $title && isset($fixed[$member['slug']])) {
			$title = $fixed[$member['slug']];
		}
		if ('' === $title) {
			$title = trim(html_entity_decode(wp_strip_all_tags(retails_product_base_heading($member['id'])), ENT_QUOTES, 'UTF-8'));
			$button = trim(wp_strip_all_tags((string) $member['title']));
			if (isset($used[mb_strtolower($title)]) && '' !== $button && 'null' !== $button) {
				// "Standard Signicade A-Frame (White) – Graphic Only", not "(White) (Graphic Only)".
				$title = ')' === substr($title, -1) ? $title . ' – ' . $button : sprintf('%s (%s)', $title, $button);
			}
		}
		$titles[$member['id']] = $title;
		$used[mb_strtolower($title)] = true;
	}

	return $cache[$product_id] = $titles;
}

/**
 * The title a product's page heading, cart line and order show: its own group title, else
 * $fallback (the heading the page already worked out, or the product title).
 */
function retails_product_group_title($product_id, $fallback = '')
{
	$titles = retails_product_group_titles($product_id);

	return isset($titles[(int) $product_id]) && '' !== $titles[(int) $product_id] ? $titles[(int) $product_id] : $fallback;
}

/**
 * The cart line name: the group title (so "Sign Only" doesn't reach the order as the kit's
 * name), else the product title. HTML-safe like get_the_title().
 */
function retails_product_cart_title($product_id)
{
	$title = retails_product_group_title($product_id);

	return '' !== $title ? esc_html($title) : get_the_title($product_id);
}

/**
 * Admin: the "Title in its product group" box under the group links, showing the title used
 * now as its placeholder.
 */
function retails_product_group_title_field()
{
	$box = function_exists('cmb2_get_metabox') ? cmb2_get_metabox('product_details') : null;
	if (!$box) {
		return;
	}
	$post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
	$current = $post_id && 'product' === get_post_type($post_id) ? retails_product_group_title($post_id) : '';
	$fields = array_keys((array) $box->prop('fields'));
	$after = array_search('_product_group_data', $fields, true);
	$box->add_field(
		array(
			'name' => 'Title in its product group',
			'desc' => 'Shown as the page heading, in the cart and on the order when this product has group buttons, so each product of the group has its own title. Leave empty for the automatic title (shown greyed). The product name above, the URL and the Google feed stay as they are.',
			'id' => '_product_group_title',
			'type' => 'text',
			'attributes' => array(
				'placeholder' => $current ? $current : 'Only used for products with group buttons',
				'autocomplete' => 'off',
			),
		),
		false === $after ? 0 : $after + 2
	);
}
add_action('cmb2_admin_init', 'retails_product_group_title_field', 15); // After functions.php adds the box, before inc/admin-product-editor.php (20).

/**
 * Admin product list: "X-Stand — Graphic Only", so products sharing a name can be told apart.
 */
function retails_product_group_post_state($states, $post)
{
	if (!$post || 'product' !== $post->post_type || !is_admin()) {
		return $states;
	}
	foreach (retails_product_group($post->ID) as $member) {
		$button = trim(wp_strip_all_tags((string) $member['title']));
		if ((int) $member['id'] === (int) $post->ID && '' !== $button && 'null' !== $button) {
			$states['sfs_product_group'] = esc_html($button);
			break;
		}
	}

	return $states;
}
add_filter('display_post_states', 'retails_product_group_post_state', 10, 2);

/**
 * ?graphic_only=true (any member's key) on a product URL shows that member there.
 */
function retails_product_group_request($vars)
{
	if (is_admin() || empty($_GET) || empty($vars['product']) || !is_string($vars['product'])) {
		return $vars;
	}
	$page = get_page_by_path($vars['product'], OBJECT, 'product');
	if (!$page || 'publish' !== $page->post_status) {
		return $vars;
	}
	foreach (retails_product_group($page->ID) as $member) {
		if ('' === $member['key'] || !isset($_GET[$member['key']])) {
			continue;
		}
		$value = strtolower(sanitize_text_field(wp_unslash((string) $_GET[$member['key']])));
		if (!in_array($value, array('true', '1'), true)) {
			continue;
		}
		$GLOBALS['retails_product_group_page'] = (int) $page->ID;
		if ((int) $member['id'] !== (int) $page->ID) {
			$vars['product'] = $member['slug'];
			$vars['name'] = $member['slug'];
		}
		break;
	}

	return $vars;
}
add_filter('request', 'retails_product_group_request');

/**
 * Never redirect a member shown on its group's URL to the member's own URL.
 */
function retails_product_group_no_redirect($redirect_url)
{
	return !empty($GLOBALS['retails_product_group_page']) ? false : $redirect_url;
}
add_filter('redirect_canonical', 'retails_product_group_no_redirect', 20);

/**
 * The swap script and its loading state, on product pages with group buttons.
 */
function retails_product_group_assets()
{
	if (!is_singular('product') || count(retails_product_group_for_page()) < 2) {
		return;
	}
	$path = '/js/product-group-swap.js';
	$file = get_template_directory() . $path;
	wp_enqueue_script('retails-product-group-swap', get_template_directory_uri() . $path, array(), file_exists($file) ? (string) filemtime($file) : '1', array('in_footer' => true, 'strategy' => 'defer'));
	wp_add_inline_style(
		'retails-ui-polish',
		'.product-details.is-swapping #product-options,.product-details.is-swapping .pd-hero{opacity:.55;transition:opacity .15s ease;pointer-events:none}'
		. '.product-group-item.is-loading .product-group-item-link{cursor:progress;position:relative}'
		// Small ring inside the tapped button.
		. '.product-group-item.is-loading .product-group-item-link::after{content:"";position:absolute;right:10px;top:50%;width:16px;height:16px;margin-top:-8px;border:2px solid rgba(31,168,222,.25);border-top-color:#1fa8de;border-radius:50%;animation:pgs-spin .7s linear infinite}'
		// Screen-centre spinner (js/product-group-swap.js adds it to the body).
		. '.pgs-spinner{position:fixed;left:50%;top:50%;z-index:99990;display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 20px;border-radius:12px;background:rgba(255,255,255,.96);box-shadow:0 8px 30px rgba(15,23,42,.18);color:#0f172a;font-size:14px;font-weight:600;transform:translate(-50%,-50%) scale(.92);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .12s ease,transform .12s ease,visibility 0s linear .12s}'
		. '.pgs-spinner.is-on{opacity:1;visibility:visible;transform:translate(-50%,-50%) scale(1);transition:opacity .12s ease,transform .12s ease}'
		. '.pgs-spinner__ring{width:38px;height:38px;border:4px solid rgba(31,168,222,.2);border-top-color:#1fa8de;border-radius:50%;animation:pgs-spin .7s linear infinite}'
		// Animated header logo in place of the ring (owner 2026-11-21). No scale on the box
		// while it is the logo: js/logo-motion.js measures the picture when it starts playing.
		// The picture's own tagline rows are masked off (like the header, cut at 86.5%) and
		// its animation copies at 86.2%; the spare transparent rows are pulled up.
		. '.pgs-spinner:has(.pgs-spinner__logo),.pgs-spinner:has(.pgs-spinner__logo).is-on{transform:translate(-50%,-50%)}'
		. '.pgs-spinner:has(.pgs-spinner__logo){gap:2px;padding:18px 24px 14px}'
		. '.pgs-spinner__logo{position:relative;display:block;width:min(280px,calc(100vw - 96px));overflow:visible}'
		. '.pgs-spinner__logo img{display:block;width:100%;height:auto;margin-bottom:-2.2%;-webkit-mask-image:linear-gradient(#000 86.5%,transparent 86.5%);mask-image:linear-gradient(#000 86.5%,transparent 86.5%)}'
		. '.pgs-spinner__logo .lm-l,.pgs-spinner__logo .lm-sign{-webkit-mask-image:linear-gradient(#000 86.2%,transparent 86.2%);mask-image:linear-gradient(#000 86.2%,transparent 86.2%)}'
		. '.pgs-spinner__text{color:#475467;font-size:13px;letter-spacing:.02em}'
		. '.pgs-spinner.is-still.is-on .pgs-spinner__logo img{animation:pgs-pulse 1.1s ease-in-out infinite alternate}'
		. '@keyframes pgs-pulse{from{opacity:1}to{opacity:.55}}'
		. '@keyframes pgs-spin{to{transform:rotate(360deg)}}'
		. '@media (prefers-reduced-motion:reduce){.product-details.is-swapping #product-options,.product-details.is-swapping .pd-hero,.pgs-spinner{transition:none}.pgs-spinner__ring,.product-group-item.is-loading .product-group-item-link::after{animation-duration:1.6s}.pgs-spinner.is-still.is-on .pgs-spinner__logo img{animation:none}}'
	);
}
add_action('wp_enqueue_scripts', 'retails_product_group_assets', 110); // After css/ui-polish.css (100).
