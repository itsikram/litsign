<?php
/**
 * Merchant Center feed validator. Command line only:
 *
 *   php tools/validate-merchant-feed.php [feed-url] [--no-http]
 *
 * Parses every <item>, checks the attributes Merchant Center requires, then
 * fetches each landing page and image and compares the feed against the
 * page's Product structured data (price, availability) and checkout's
 * shipping rules. Exits 1 when any item fails. Don't point it at the live
 * site in a tight loop: rapid requests there get the IP firewalled.
 *
 * @package litsign
 */

if ('cli' !== PHP_SAPI) {
	exit;
}

$args = array_slice($argv, 1);
$http = !in_array('--no-http', $args, true);
$args = array_values(array_diff($args, array('--no-http')));
$feed_url = $args ? $args[0] : 'http://localhost/storefrontsignonline.com/merchant-feed.xml';

$get = static function ($url, $head = false) {
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_NOBODY => $head,
		CURLOPT_TIMEOUT => 60,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
	));
	$body = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	return array((int) $info['http_code'], (string) $body, (string) $info['content_type']);
};

list($code, $xml, $type) = $get($feed_url);
if (200 !== $code) {
	fwrite(STDERR, "Feed returned HTTP $code\n");
	exit(1);
}
libxml_use_internal_errors(true);
$doc = simplexml_load_string($xml);
if (!$doc) {
	foreach (libxml_get_errors() as $error) {
		fwrite(STDERR, 'XML: ' . trim($error->message) . " (line {$error->line})\n");
	}
	exit(1);
}
$ns = $doc->getNamespaces(true);
if (($ns['g'] ?? '') !== 'http://base.google.com/ns/1.0' || 'rss' !== $doc->getName() || '2.0' !== (string) $doc['version']) {
	fwrite(STDERR, "Not an RSS 2.0 feed with the Google product namespace\n");
	exit(1);
}

$money = '/^\d+\.\d{2} USD$/';
$ids = array();
$failed = array();
$warnings = array();
$items = $doc->channel->item;
$checked_images = array();

foreach ($items as $item) {
	$g = $item->children($ns['g']);
	$id = (string) $g->id;
	$errors = array();
	$warn = array();

	foreach (array('id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'condition', 'brand') as $field) {
		if ('' === trim((string) $g->$field)) {
			$errors[] = "missing $field";
		}
	}
	if (isset($ids[$id])) {
		$errors[] = 'duplicate id';
	}
	$ids[$id] = true;
	if (strlen($id) > 50) {
		$errors[] = 'id over 50 characters';
	}
	if (mb_strlen((string) $g->title) > 150) {
		$errors[] = 'title over 150 characters';
	}
	if (mb_strlen((string) $g->description) > 5000) {
		$errors[] = 'description over 5000 characters';
	}
	if (!in_array((string) $g->availability, array('in_stock', 'out_of_stock', 'preorder', 'backorder'), true)) {
		$errors[] = 'bad availability';
	}
	if ('backorder' === (string) $g->availability && '' === (string) $g->availability_date) {
		$errors[] = 'backorder without availability_date';
	}
	if ('new' !== (string) $g->condition) {
		$errors[] = 'condition is not new';
	}
	if ('' === (string) $g->gtin && '' === (string) $g->mpn && 'no' !== (string) $g->identifier_exists) {
		$errors[] = 'no identifiers and identifier_exists is not no';
	}

	$price = (string) $g->price;
	$value = (float) $price;
	if (!preg_match($money, $price) || $value <= 0) {
		$errors[] = "bad price '$price'";
	}
	$current = $value;
	if ('' !== (string) $g->sale_price) {
		$sale = (float) $g->sale_price;
		if (!preg_match($money, (string) $g->sale_price) || $sale <= 0 || $sale >= $value) {
			$errors[] = 'sale_price not below price';
		}
		$dates = explode('/', (string) $g->sale_price_effective_date);
		$end = 2 === count($dates) ? strtotime($dates[1]) : false;
		if (2 !== count($dates) || !strtotime($dates[0]) || !$end) {
			$errors[] = 'sale_price without a valid sale_price_effective_date';
		} elseif ($end < time()) {
			$errors[] = 'sale has already ended';
		} elseif (strtotime($dates[0]) <= time()) {
			$current = $sale;
		}
	}

	// Shipping: the flat Standard rate, or free at or above the free shipping minimum.
	$ship = $g->shipping ? (float) $g->shipping->children($ns['g'])->price : null;
	if (null === $ship) {
		$errors[] = 'missing shipping';
	} else {
		$channel = false !== stripos((string) $g->product_type, 'Channel Letters');
		$free_min = $channel ? 1000 : 100;
		if ($current >= $free_min && $ship > 0) {
			$errors[] = "charges $ship shipping at a price over the free shipping minimum";
		}
		if ($current < $free_min && 0.0 === $ship) {
			$errors[] = 'free shipping below the free shipping minimum';
		}
		$s = $g->shipping->children($ns['g']);
		if ((int) $s->min_handling_time > (int) $s->max_handling_time || (int) $s->min_transit_time > (int) $s->max_transit_time) {
			$errors[] = 'shipping min time above max time';
		}
	}

	if ($http) {
		list($page_code, $html) = $get((string) $g->link);
		if (200 !== $page_code) {
			$errors[] = "landing page HTTP $page_code";
		} else {
			if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+noindex/i', $html)) {
				$warn[] = 'landing page is noindex';
			}
			$products = 0;
			$schema_price = null;
			$schema_availability = null;
			if (preg_match_all('#<script[^>]+application/ld\+json[^>]*>(.*?)</script>#s', $html, $blocks)) {
				foreach ($blocks[1] as $block) {
					$data = json_decode($block, true);
					$nodes = isset($data['@graph']) ? $data['@graph'] : array($data);
					foreach ($nodes as $node) {
						if (is_array($node) && 'Product' === ($node['@type'] ?? '')) {
							$products++;
							if (isset($node['offers']['price'])) {
								$schema_price = (float) $node['offers']['price'];
								$schema_availability = basename((string) ($node['offers']['availability'] ?? ''));
							}
						}
					}
				}
			}
			if (1 !== $products) {
				$errors[] = "$products Product entities on the landing page";
			}
			if (null === $schema_price) {
				$errors[] = 'landing page has no Offer price';
			} elseif (abs($schema_price - $current) > 0.005) {
				$errors[] = "feed price $current differs from page price $schema_price";
			}
			$map = array('InStock' => 'in_stock', 'OutOfStock' => 'out_of_stock', 'BackOrder' => 'backorder', 'PreOrder' => 'preorder');
			if ($schema_availability && ($map[$schema_availability] ?? '') !== (string) $g->availability) {
				$errors[] = "availability differs from page ($schema_availability)";
			}
			// The visible "Starting at" price, when the page prints one.
			if (preg_match('#class="product-price-per-sqft[^"]*">(.*?)</div>#s', $html, $visible)
				&& preg_match_all('/\$\s*([\d,]+(?:\.\d{2})?)/', preg_replace('#<!--.*?-->#s', '', $visible[1]), $amounts)) {
				$shown = array_map(static function ($a) {
					return (float) str_replace(',', '', $a);
				}, $amounts[1]);
				if (!in_array(round($current, 2), array_map(static function ($a) {
					return round($a, 2);
				}, $shown), true) && !in_array((float) ceil($current), $shown, true)) {
					$warn[] = 'visible Starting at price (' . implode(', ', $shown) . ") does not show $current";
				}
			}
		}

		$images = array((string) $g->image_link);
		foreach ($g->additional_image_link as $extra) {
			$images[] = (string) $extra;
		}
		if (count($images) > 11) {
			$errors[] = 'more than 10 additional images';
		}
		foreach ($images as $image) {
			if (!isset($checked_images[$image])) {
				list($image_code, $bytes, $image_type) = $get($image);
				$size = 200 === $image_code ? @getimagesizefromstring($bytes) : false;
				$checked_images[$image] = array($image_code, $image_type, $size);
			}
			list($image_code, $image_type, $size) = $checked_images[$image];
			if (200 !== $image_code) {
				$errors[] = "image HTTP $image_code: $image";
			} elseif (!preg_match('#^image/(jpeg|png|gif|webp|bmp|tiff)#', $image_type)) {
				$errors[] = "image type $image_type: $image";
			} elseif (!$size || $size[0] < 100 || $size[1] < 100) {
				$errors[] = "image under 100x100: $image";
			} elseif ($size[0] < 500 || $size[1] < 500) {
				$warn[] = "image under 500x500 ({$size[0]}x{$size[1]}): $image";
			}
		}
	}

	if ($errors) {
		$failed[$id] = $errors;
	}
	if ($warn) {
		$warnings[$id] = $warn;
	}
}

$total = count($items);
foreach ($failed as $id => $errors) {
	echo "FAIL $id: " . implode('; ', $errors) . "\n";
}
foreach ($warnings as $id => $warn) {
	echo "WARN $id: " . implode('; ', $warn) . "\n";
}
printf("\n%d items, %d passed, %d failed, %d with warnings, %d images checked\n", $total, $total - count($failed), count($failed), count($warnings), count($checked_images));
exit($failed ? 1 : 0);
