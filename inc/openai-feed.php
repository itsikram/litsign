<?php
/**
 * OpenAI (ChatGPT) product feed at /openai-feed.jsonl.
 *
 * A separate export next to the Google Merchant Center feed
 * (inc/merchant-feed.php), built from the same rows
 * (wholesale_merchant_feed_rows()) so IDs, prices, links and images always
 * agree with the Google feed, the product pages and the cart. Fields follow
 * OpenAI's product feed spec (developers.openai.com/commerce/specs/feed):
 * JSONL, one product per line.
 *
 * What is left out on purpose: GTIN/MPN (made to order, none exist), star
 * ratings and review counts (no per-product reviews are published), and
 * checkout (is_eligible_checkout is false: orders are placed on this site).
 *
 * The finished file is cached for 6 hours and cleared when a product, or any
 * "wholesale_" setting, is saved, so ordinary page requests never build it.
 *
 * @package litsign
 */

// v2 (2026-11-12): product group options as variants. A new name drops the old cached file.
const WHOLESALE_OPENAI_FEED_CACHE = 'wholesale_openai_feed_v2';

function wholesale_openai_feed_rewrite()
{
	add_rewrite_rule('^openai-feed\.jsonl$', 'index.php?wholesale_openai_feed=1', 'top');
}
add_action('init', 'wholesale_openai_feed_rewrite', 20);

add_action('init', function () {
	if ('1' !== get_option('wholesale_openai_feed_rewrite_version')) {
		flush_rewrite_rules(false);
		update_option('wholesale_openai_feed_rewrite_version', '1');
	}
}, 99);

add_filter('query_vars', function ($vars) {
	$vars[] = 'wholesale_openai_feed';
	return $vars;
});

/**
 * One feed line (an array) for a shared feed row.
 */
function wholesale_openai_feed_record($row)
{
	$offer = $row['offer'];
	$price = (float) $row['price'];
	$regular = (float) $offer['regular'];
	$stock = $row['stock'];
	$money = static function ($amount) {
		return number_format((float) $amount, 2, '.', '') . ' USD';
	};

	$record = array(
		'item_id' => (string) $row['id'],
		'title' => mb_substr(wp_strip_all_tags((string) $row['title']), 0, 150),
		'description' => mb_substr((string) $row['description'], 0, 5000),
		'url' => $row['link'],
		'brand' => get_bloginfo('name'),
		'seller_name' => get_bloginfo('name'),
		'seller_url' => home_url('/'),
		'image_url' => $row['image'],
		'availability' => 'instock' === $stock ? 'in_stock' : ('backorder' === $stock ? 'backorder' : 'out_of_stock'),
		'condition' => 'new',
		'price' => $money($regular),
		'is_eligible_search' => true,
		'is_eligible_checkout' => false,
		'target_countries' => array('US'),
		'store_country' => 'US',
		'return_policy' => home_url('/shipping-returns/'),
	);
	if ($row['gallery']) {
		$record['additional_image_urls'] = array_values($row['gallery']);
	}
	if ($row['product_type']) {
		$record['product_category'] = html_entity_decode($row['product_type'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	// A sale price only while the dated sale is running; OpenAI does not schedule it.
	if ($regular > $price && !empty($offer['sale_ends']) && !empty($offer['sale_starts'])) {
		$zone = new DateTimeZone('America/Los_Angeles');
		$starts = DateTimeImmutable::createFromFormat('!Y-m-d', $offer['sale_starts'], $zone);
		$now = time();
		if ($starts && $starts->getTimestamp() <= $now && (int) $offer['sale_ends'] > $now) {
			$record['sale_price'] = $money($price);
		}
	}

	// Same Standard rate the Google feed sends: free only when this item alone reaches
	// the free shipping minimum, the same test checkout applies to the subtotal.
	$record['shipping_price'] = $money($row['shipping']['shippingRate']['value']);

	if ($row['size']) {
		$record['group_id'] = $row['group_id'] . '-sizes';
		$record['listing_has_variations'] = true;
		$record['variant_dict'] = array('Size' => (string) $row['size']);
	} elseif (!empty($row['option_group'])) {
		// One product page with option buttons ("Graphic + Frame" / "Graphic Only",
		// inc/product-group-swap.php): the options are variants of one listing. Each keeps
		// its own item, price, URL and title.
		$record['group_id'] = $row['option_group'];
		$record['listing_has_variations'] = true;
		$record['variant_dict'] = array('Option' => (string) $row['option']);
	}

	return $record;
}

/**
 * Product group options for the feed rows: product ID => array(group, option). A group
 * counts when at least two of its products are feed items, none has size items (a row
 * is in one variant group only), and the option names are distinct. The group ID is the
 * first product's item ID plus "-options", so it never equals an item ID.
 *
 * @param array[] $rows wholesale_merchant_feed_rows().
 * @return array
 */
function wholesale_openai_feed_option_groups(array $rows)
{
	if (!function_exists('wholesale_product_group')) {
		return array();
	}
	$items = array();
	$sized = array();
	foreach ($rows as $row) {
		if ($row['size']) {
			$sized[(int) $row['product_id']] = true;
		} else {
			$items[(int) $row['product_id']] = true;
		}
	}

	$map = array();
	$done = array();
	foreach (array_keys($items) as $product_id) {
		$members = wholesale_product_group($product_id);
		if (!$members) {
			continue;
		}
		$ids = array_map('intval', wp_list_pluck($members, 'id'));
		$signature = implode(',', $ids);
		if (isset($done[$signature])) {
			continue;
		}
		$done[$signature] = true;

		$options = array();
		foreach ($members as $member) {
			if (isset($sized[$member['id']])) {
				$options = array();
				break;
			}
			if (isset($items[$member['id']])) {
				$options[(int) $member['id']] = trim(wp_strip_all_tags((string) $member['title']));
			}
		}
		if (count($options) < 2 || in_array('', $options, true) || count(array_unique($options)) !== count($options)) {
			continue;
		}
		$group = wholesale_merchant_feed_id($ids[0]) . '-options';
		foreach ($options as $id => $option) {
			// A product listed in two groups keeps the first.
			if (!isset($map[$id])) {
				$map[$id] = array('group' => $group, 'option' => $option);
			}
		}
	}

	return $map;
}

/**
 * The whole JSONL file (built once, then cached).
 */
function wholesale_openai_feed_body()
{
	$cached = get_transient(WHOLESALE_OPENAI_FEED_CACHE);
	if (is_string($cached) && '' !== $cached) {
		return $cached;
	}

	$lines = array();
	$rows = wholesale_merchant_feed_rows();
	$option_groups = wholesale_openai_feed_option_groups($rows);
	foreach ($rows as $row) {
		if (!$row['size'] && isset($option_groups[(int) $row['product_id']])) {
			$row['option_group'] = $option_groups[(int) $row['product_id']]['group'];
			$row['option'] = $option_groups[(int) $row['product_id']]['option'];
		}
		$lines[] = wp_json_encode(wholesale_openai_feed_record($row), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
	$body = $lines ? implode("\n", $lines) . "\n" : '';
	if ('' !== $body) {
		set_transient(WHOLESALE_OPENAI_FEED_CACHE, $body, 6 * HOUR_IN_SECONDS);
	}

	return $body;
}

function wholesale_render_openai_feed()
{
	if ('1' !== get_query_var('wholesale_openai_feed')) {
		return;
	}

	$body = wholesale_openai_feed_body();

	// Same headers as the Google feed. An explicit Expires also stops the host adding its
	// one-year default for file types it does not know (seen live 11-11), which would let
	// a cache keep an old feed for a year.
	nocache_headers();
	status_header(200);
	header('Content-Type: application/x-ndjson; charset=UTF-8');
	header('X-Robots-Tag: noindex, follow');
	echo $body;
	exit;
}
add_action('template_redirect', 'wholesale_render_openai_feed', 0);

function wholesale_openai_feed_clear()
{
	delete_transient(WHOLESALE_OPENAI_FEED_CACHE);
}
add_action('save_post_product', 'wholesale_openai_feed_clear');
add_action('deleted_post', 'wholesale_openai_feed_clear');
add_action('trashed_post', 'wholesale_openai_feed_clear');
// Group buttons changed outside a product save (the options are feed variants).
foreach (array('added_post_meta', 'updated_post_meta', 'deleted_post_meta') as $wholesale_openai_hook) {
	add_action($wholesale_openai_hook, function ($meta_id, $object_id, $meta_key) {
		if ('_product_group_data' === $meta_key) {
			wholesale_openai_feed_clear();
		}
	}, 10, 3);
}
foreach (array('updated_option', 'added_option', 'deleted_option') as $wholesale_openai_hook) {
	add_action($wholesale_openai_hook, function ($option) {
		if (0 === strpos((string) $option, 'wholesale_') && WHOLESALE_OPENAI_FEED_CACHE !== $option) {
			wholesale_openai_feed_clear();
		}
	});
}
unset($wholesale_openai_hook);
