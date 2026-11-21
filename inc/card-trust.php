<?php
/**
 * Trust badges on product cards (2026-11-21). The owner asked for cards that
 * "get buyer confident to place order". Each card gets at most two short
 * pills, built only from facts the site already holds, so they follow the
 * settings and the product data:
 *
 * - Channel letters: "UL listed" and "5-year warranty". The owner confirmed
 *   both on 2026-10-12, and the cart and checkout say the same.
 * - Everything else gets two pills:
 *   1. The product's fastest real production time: "Same-day available",
 *      "Next-day turnaround" or "Ships in N business days". It is read from
 *      the production-time choices the page and cart offer, and from the
 *      supplier notes (with "Ships same day" for stock items) for products
 *      without choices. Products with no production-time choice (hardware)
 *      and products with no turnaround data get none. Same Day usually costs
 *      extra, so it is only ever "available", never "free".
 *   2. (Removed 2026-11-21 at the owner's request: the "Free shipping $100+" pill.
 *      No card shows a free shipping badge any more.)
 * - Cards that stand for a whole category pass 'ship'. They used to get only the
 *   shipping pill, so they now print nothing.
 *
 * The Google business rating is left off on purpose: next to a product it
 * would read like that product's own rating.
 *
 * Styles print inline once, before the first badge row of the page. That
 * avoids a new request and leaves style.css / ui-polish.css (and their home
 * critical subsets) untouched. Icons are CSS masks, so each card adds about
 * 150 bytes of HTML. The badges are HTML only and never go into structured
 * data.
 *
 * @package retails
 */

defined('ABSPATH') || exit;

/**
 * The badge list for one card: array of array(icon, label).
 *
 * @param int    $product_id Product the card shows (0 when unknown).
 * @param string $only       'ship' for category cards (shipping pill only).
 */
function retails_card_trust_items($product_id, $only = '')
{
	$product_id = (int) $product_id;
	$cl = $product_id && function_exists('retails_product_is_channel_letter') && retails_product_is_channel_letter($product_id);
	if ($cl && 'ship' !== $only) {
		return array(array('shield', 'UL listed'), array('award', '5-year warranty'));
	}

	$items = array();
	if ($product_id && 'ship' !== $only && function_exists('retails_product_turnaround')) {
		$label = retails_card_trust_turnaround($product_id);
		if ('' !== $label) {
			$items[] = array('clock', $label);
		}
	}
	// 2026-11-21: the "Free shipping $X+" pill was removed from every card at the
	// owner's request. Cards that pass 'ship' (category cards) now print nothing.
	return $items;
}

/**
 * The production-time pill text, or '' when the product has no production
 * time to promise (hardware without choices, or no turnaround data at all).
 */
function retails_card_trust_turnaround($product_id)
{
	// Products with production-time choices (B2Sign Sync): the fastest choice the
	// page and cart really offer. retails_product_turnaround() alone is not enough:
	// it says "next day" for products whose only choice is 7 days (custom booths).
	$spec = function_exists('retails_product_turnaround_spec') ? retails_product_turnaround_spec($product_id) : array();
	if ($spec) {
		$days = array();
		foreach ($spec as $o) {
			// Same Day counts only as the 12 pm Same Day choice. The free Same Day for
			// orders approved by an early cut-off (6 am) is too narrow to promise.
			if (($o['choice'] || $o['automatic']) && ('same_day' === $o['value'] || (int) $o['days'] > 0)) {
				$days[] = 'same_day' === $o['value'] ? 0 : (int) $o['days'];
			}
		}
		if (!$days) {
			return '';
		}
		$fastest = min($days);
		if (0 === $fastest) {
			return 'Same-day available';
		}
		return 1 === $fastest ? 'Next-day turnaround' : sprintf('Ships in %d business days', $fastest);
	}

	// Hardware and older products: the supplier notes ("... ships same day",
	// "ships in 3 business days").
	$sections = function_exists('retails_product_supplier_sections') ? retails_product_supplier_sections($product_id) : array();
	if ('' === trim(wp_strip_all_tags((string) ($sections['same_day'] ?? '') . (string) ($sections['next_day'] ?? '')))) {
		return '';
	}
	$t = retails_product_turnaround($product_id);
	if ($t['stock'] && $t['same_day']) {
		return 'Ships same day';
	}
	// Hardware with no production-time choice (meta "[]"): only the in-stock promise above.
	if (function_exists('retails_product_turnaround_none') && retails_product_turnaround_none($product_id)) {
		return '';
	}
	if ($t['same_day']) {
		return 'Same-day available';
	}
	$days = max(1, (int) $t['next_days']);
	return 1 === $days ? 'Next-day turnaround' : sprintf('Ships in %d business days', $days);
}

/**
 * The badge row HTML for one card ('' when there is nothing true to say).
 * A <span> wrapper, so it is valid inside the links and spans cards use.
 */
function retails_card_trust($product_id, $only = '')
{
	$items = retails_card_trust_items($product_id, $only);
	// The style block also carries the phone card rules (no short description, line
	// break after the struck price), so it prints even for cards with no pill.
	$style = retails_card_trust_style();
	if (!$items) {
		return $style;
	}
	$html = '<span class="pc-trust">';
	foreach ($items as $item) {
		// Phones get a shorter wording so the pill stays on one line (see the CSS below).
		$short = str_replace(array('Ships in ', ' business days', ' business day'), array('Ships in ', ' days', ' day'), $item[1]);
		if ($short !== $item[1]) {
			$html .= '<span class="pc-t pc-t--' . esc_attr($item[0]) . '"><span class="pc-l">' . esc_html($item[1]) . '</span><span class="pc-s" aria-hidden="true">' . esc_html($short) . '</span></span>';
		} else {
			$html .= '<span class="pc-t pc-t--' . esc_attr($item[0]) . '">' . esc_html($item[1]) . '</span>';
		}
	}
	return $style . $html . '</span>';
}

/**
 * The badge styles, once per page (printed before the first badge row).
 */
function retails_card_trust_style()
{
	static $done = false;
	if ($done) {
		return '';
	}
	$done = true;
	$icon = static function ($path) {
		return 'url("data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>') . '")';
	};
	$icons = array(
		'clock' => $icon('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'),
		'truck' => $icon('<path d="M1 4h13v12H1zM14 8h4l4 4v4h-8z"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>'),
		'shield' => $icon('<path d="M12 2 4 5v6c0 5 3.4 9.3 8 11 4.6-1.7 8-6 8-11V5z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>'),
		'award' => $icon('<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 22l5-3 5 3-1.5-8"/>'),
	);
	$css = '.pc-trust{display:flex;flex-wrap:wrap;gap:5px;margin:8px 0 4px;font-size:12px;line-height:1.25;font-weight:600;letter-spacing:0;text-align:left}'
		. '.pc-t{display:inline-flex;align-items:center;gap:5px;max-width:100%;padding:4px 9px 4px 7px;border-radius:999px;background:#e9f7ef;color:#11663a}'
		. '.pc-t::before{content:"";flex:none;width:14px;height:14px;background:currentColor;-webkit-mask:var(--pc-i) center/contain no-repeat;mask:var(--pc-i) center/contain no-repeat}'
		. '.pc-t--truck{background:#e7f4fb;color:#0a5a80}';
	foreach ($icons as $name => $url) {
		$css .= '.pc-t--' . $name . '{--pc-i:' . $url . '}';
	}
	$css .= '.pc-s{display:none}';
	$css .= '@media (max-width:575.98px){.pc-trust{gap:4px;font-size:11.5px}.pc-t{padding:3px 8px 3px 6px}}';
	// Phone grid cards (2026-11-21): one-line pill, no short description, and a line
	// break after the struck regular price.
	$css .= '@media (max-width:767.98px){'
		. '.pc-t{white-space:nowrap}.pc-t .pc-l{display:none}.pc-t .pc-s{display:inline;min-width:0;overflow:hidden;text-overflow:ellipsis}'
		. '.pb-description-list,.sf-type-body>p:not(.sf-type-price){display:none}'
		. '.pb-price del::after,.related-product-price del::after,.bd-product-price del::after,.sf-type-price del::after{content:"";display:block}'
		. '}';
	return '<style id="retails-card-trust-css">' . $css . '</style>';
}
