<?php
/**
 * Product page buy box (2026-11-05): Buy Now next to Add To Cart, a quantity stepper,
 * the "order within … to ship …" delivery estimate, and the payment/guest checkout row.
 *
 * - Buy Now is a second `.add-to-cart-btn`, so every check Add To Cart runs (sign text,
 *   size limits, waiting for a price quote, repeat-tap guard) runs for it too. js/mini-cart.js
 *   adds the item over AJAX and opens checkout instead of the cart drawer; without
 *   JavaScript the form posts to /cart/ with buy_now=1 and Cart::add_item() redirects to
 *   /checkout/.
 * - Pages are served from the static page cache for up to 8 hours, so the delivery
 *   estimate is worked out in the browser (js/ui-polish.js section 6) from the
 *   production days printed here, with the same rules as checkout
 *   (retails_estimated_ship_datetime(), retails_estimated_delivery_date()).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Production business days for one product, as retails_cart_production_days() counts
 * them for a cart: the product's turnaround (at least 1), and at least 4 for channel letters.
 */
function retails_product_production_days($product_id)
{
	$days = max(1, (int) get_post_meta($product_id, '_product_turnaround', true));
	return retails_product_is_channel_letter($product_id) ? max($days, 4) : $days;
}

/**
 * "Order within 2 hr 14 min to ship Thu, Nov 6" box. Printed hidden; js/ui-polish.js
 * fills it in Pacific time and shows it, so a cached page never shows a stale date.
 * Not for out of stock or backordered products (single-product.php shows the backorder date).
 */
function retails_render_product_eta($product_id)
{
	if (in_array(retails_product_stock_status($product_id), array('outofstock', 'backorder'), true)) {
		return;
	}
	$is_cl = retails_product_is_channel_letter($product_id);
	$rates = array_filter(array_map('floatval', explode(',', (string) retails_get_setting($is_cl ? 'channel_shipping_options' : 'standard_shipping_options'))));
	// Transit business days per checkout speed (retails_estimated_delivery_date()).
	$transit = array_slice(array(6, 3, 2, 1), 0, max(1, count($rates)));
	$speed_names = array('standard', '3-day', '2-day', 'overnight');
	$cutoffs = retails_product_cutoff_hours($product_id);
	?>
	<div class="pd-eta" data-production-days="<?php echo esc_attr(retails_product_production_days($product_id)); ?>" data-cutoff="<?php echo esc_attr($cutoffs['next']); ?>" data-same-day-cutoff="<?php echo esc_attr($cutoffs['same']); ?>" data-fastest-days="<?php echo esc_attr(end($transit)); ?>" data-fastest-name="<?php echo esc_attr($speed_names[count($transit) - 1]); ?>" hidden>
		<svg class="pd-eta__icon" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M1 4h13v11H1zM14 8h4l3 3.5V15h-7M5.5 18.5a1.5 1.5 0 1 0 0-.01M17.5 18.5a1.5 1.5 0 1 0 0-.01"/></svg>
		<div class="pd-eta__text">
			<p class="pd-eta__ship" aria-live="polite"></p>
			<p class="pd-eta__arrive"></p>
		</div>
	</div>
	<?php
}

/**
 * Quantity stepper, Add To Cart and Buy Now. Keeps the ids js/main.js and
 * js/ui-polish.js use (#productQuantity, #addToCartBtn).
 *
 * @param string $stock_status retails_product_stock_status().
 * @param float  $initial_cost The price the page opens on; 0 disables the buttons until a price shows.
 */
function retails_render_product_actions($stock_status, $initial_cost)
{
	$disabled = $initial_cost <= 0 ? ' disabled' : '';
	// B2Sign quantity pricing (SFS B2Sign Sync): the price above already follows the quantity.
	// The note gets its own row under the buttons (css/ui-polish.css section 14): inside the
	// quantity column it widened the stepper and squeezed Add To Cart onto two lines on phones.
	$qty_pricing = 'outofstock' !== $stock_status && function_exists('retails_product_qty_pricing') ? retails_product_qty_pricing(get_the_ID()) : null;
	?>
	<div class="pd-actions<?php echo $qty_pricing ? ' pd-actions--note' : ''; ?>">
		<div class="pd-qty">
			<label for="productQuantity" class="form-label">Quantity</label>
			<div class="pd-qty__box">
				<button type="button" class="pd-qty__btn" data-qty-step="-1" aria-label="Decrease quantity" disabled>
					<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path stroke="currentColor" stroke-width="2.4" stroke-linecap="round" d="M6 12h12"/></svg>
				</button>
				<input type="number" name="product_quantity" data-current-qty="1" value="1" min="1" max="1000" inputmode="numeric" id="productQuantity" class="form-control">
				<button type="button" class="pd-qty__btn" data-qty-step="1" aria-label="Increase quantity">
					<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path stroke="currentColor" stroke-width="2.4" stroke-linecap="round" d="M6 12h12M12 6v12"/></svg>
				</button>
			</div>
		</div>
		<?php if ('outofstock' === $stock_status) : ?>
			<button id="addToCartBtn" type="button" class="btn btn-secondary d-block w-100 fw-bold pd-actions__out" disabled>Out of stock</button>
		<?php else : ?>
			<?php // Add To Cart stays first: pressing Enter in a field submits with the first submit button. ?>
			<button id="addToCartBtn" type="submit" class="add-to-cart-btn btn btn-danger d-block w-100 fw-bold"<?php echo $disabled; ?>>Add To Cart</button>
			<?php if ($qty_pricing) : ?>
				<small class="pd-qty__note"><?php echo esc_html(!empty($qty_pricing['sheet']) ? sprintf('Order more, pay less per piece. Pieces that share a %s x %s ft sheet cost no more than the full sheet.', $qty_pricing['sheet']['w'] / 12, $qty_pricing['sheet']['h'] / 12) : 'Order more, pay less per piece. The price updates with the quantity.'); ?></small>
			<?php endif; ?>
			<button id="buyNowBtn" type="submit" name="buy_now" value="1" class="add-to-cart-btn buy-now-btn btn w-100 fw-bold"<?php echo $disabled; ?>>
				<span class="buy-now-btn__label">Buy Now</span>
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
			</button>
		<?php endif; ?>
	</div>
	<?php if ('outofstock' !== $stock_status) : ?>
		<p class="pd-actions__hint">Buy Now takes you straight to secure checkout. No account needed.</p>
	<?php endif; ?>
	<?php
}

/**
 * "Secure checkout" and the cards accepted at checkout as small card marks (checkout.php
 * lists the same four). Under the buy buttons and, when shown, the wallet plugin's
 * Express checkout panel. Each mark has an accessible name.
 */
function retails_render_product_pay_row()
{
	if (retails_setting_enabled('payment_disabled')) {
		return;
	}
	$frame = '<rect x=".5" y=".5" width="37" height="23" rx="3.5" fill="#fff" stroke="#d3dbe4"/>';
	$font = 'font-family="Arial,Helvetica,sans-serif" font-weight="700"';
	$cards = array(
		'Visa' => $frame . '<text x="19" y="16" text-anchor="middle" ' . $font . ' font-size="10.5" font-style="italic" fill="#1a1f71" textLength="25" lengthAdjust="spacingAndGlyphs">VISA</text>',
		'Mastercard' => $frame . '<circle cx="15.5" cy="12" r="6.5" fill="#eb001b"/><circle cx="22.5" cy="12" r="6.5" fill="#f79e1b"/><path fill="#ff5f00" d="M19 6.52a6.5 6.5 0 0 1 0 10.96 6.5 6.5 0 0 1 0-10.96Z"/>',
		'American Express' => '<rect width="38" height="24" rx="4" fill="#016fd0"/><text x="19" y="15.6" text-anchor="middle" ' . $font . ' font-size="9.5" fill="#fff" textLength="28" lengthAdjust="spacingAndGlyphs">AMEX</text>',
		'Discover' => $frame . '<text x="3.6" y="14.9" ' . $font . ' font-size="7.4" fill="#231f20" textLength="15" lengthAdjust="spacingAndGlyphs">DISC</text><circle cx="22.4" cy="12.3" r="3.5" fill="#f48120"/><text x="26.4" y="14.9" ' . $font . ' font-size="7.4" fill="#231f20" textLength="9" lengthAdjust="spacingAndGlyphs">VER</text>',
	);
	$cards += retails_public_wallet_marks($frame, $font);
	?>
	<div class="pd-pay">
		<span class="pd-pay__secure">
			<svg class="pd-pay__lock" viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 1a5 5 0 0 0-5 5v3H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-2V6a5 5 0 0 0-5-5Zm-3 5a3 3 0 0 1 6 0v3H9V6Z"/></svg>
			Secure checkout
		</span>
		<div class="pd-pay__accept">
			<span class="pd-pay__label" id="pdPayLabel">We accept</span>
			<ul class="pd-pay__cards" aria-labelledby="pdPayLabel">
				<?php foreach ($cards as $name => $svg) : ?>
					<li title="<?php echo esc_attr($name); ?>"><svg viewBox="0 0 38 24" width="38" height="24" role="img" aria-label="<?php echo esc_attr($name); ?>" focusable="false"><?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup above. ?></svg></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * Wallets every visitor can really pay with, from the SFS Wallet Payments settings: Apple Pay and
 * Google Pay when Stripe is on in Live mode, PayPal (and Venmo when its switch is on) when PayPal
 * is on in Live mode. Empty while the plugin is missing, set to "Only administrators", or in
 * test/sandbox mode, so the page never lists a wallet a shopper can't use. Static marks: Google's
 * Merchant Center checks pages for them, and the real Stripe buttons only draw on devices that
 * have that wallet.
 *
 * @return string[] Wallet names, e.g. array('Apple Pay', 'Google Pay', 'PayPal', 'Venmo').
 */
function retails_public_wallet_names()
{
	$settings = '\SFS\WalletPayments\Settings';
	if (!class_exists($settings) || $settings::get('admins_only')) {
		return array();
	}
	if (!$settings::get('show_product_page') && !$settings::get('show_checkout') && !$settings::get('show_cart_page')) {
		return array();
	}
	$names = array();
	if ($settings::stripe_ready() && 'live' === $settings::stripe_mode()) {
		$names[] = 'Apple Pay';
		$names[] = 'Google Pay';
	}
	if ($settings::paypal_ready() && 'live' === $settings::paypal_mode()) {
		$names[] = 'PayPal';
		if ($settings::get('paypal_venmo')) {
			$names[] = 'Venmo';
		}
	}
	return $names;
}

/**
 * SVG marks for retails_public_wallet_names(), in the same frame as the card marks.
 */
function retails_public_wallet_marks($frame, $font)
{
	$svg = array(
		'Apple Pay' => $frame . '<path fill="#000" d="M11.6 8.6c.4-.5.7-1.2.6-1.9-.6 0-1.3.4-1.7.9-.4.4-.7 1.1-.6 1.8.7 0 1.3-.3 1.7-.8Zm.6 1c-.9 0-1.7.5-2.1.5-.5 0-1.1-.5-1.9-.5-1 0-1.9.6-2.4 1.5-1 1.8-.3 4.4.7 5.8.5.7 1 1.5 1.8 1.4.7 0 1-.5 1.9-.5s1.1.5 1.9.4c.8 0 1.3-.7 1.7-1.4.5-.8.8-1.6.8-1.6s-1.6-.6-1.6-2.4c0-1.5 1.2-2.2 1.3-2.3-.7-1-1.8-1.1-2.1-1.1Z"/><text x="16" y="16.2" ' . $font . ' font-size="9.5" fill="#000" textLength="17" lengthAdjust="spacingAndGlyphs">Pay</text>',
		'Google Pay' => $frame . '<text x="4.5" y="16.2" ' . $font . ' font-size="10" fill="#4285f4">G</text><text x="13.5" y="16.2" font-family="Arial,Helvetica,sans-serif" font-size="9.5" fill="#5f6368" textLength="17" lengthAdjust="spacingAndGlyphs">Pay</text>',
		'PayPal' => $frame . '<text x="19" y="15.6" text-anchor="middle" ' . $font . ' font-size="9" font-style="italic" textLength="30" lengthAdjust="spacingAndGlyphs"><tspan fill="#003087">Pay</tspan><tspan fill="#009cde">Pal</tspan></text>',
		'Venmo' => '<rect width="38" height="24" rx="4" fill="#008cff"/><text x="19" y="15.6" text-anchor="middle" ' . $font . ' font-size="9.5" font-style="italic" fill="#fff" textLength="28" lengthAdjust="spacingAndGlyphs">venmo</text>',
	);
	$out = array();
	foreach (retails_public_wallet_names() as $name) {
		$out[$name] = $svg[$name];
	}
	return $out;
}

/**
 * The line in $items that is the same order as $item (same product, options, quantity,
 * price, job name and design), or null. Buy Now uses it so going back from checkout and
 * pressing Buy Now again doesn't put the same item in the cart twice.
 *
 * @param object[] $items Cart lines.
 * @param object   $item  The line just added.
 */
function retails_cart_same_item($items, $item)
{
	$signature = static function ($line) {
		$copy = json_decode(wp_json_encode($line), true);
		if (!is_array($copy)) {
			return '';
		}
		unset($copy['cart_id']);
		return wp_json_encode($copy);
	};
	$wanted = $signature($item);
	foreach ($items as $line) {
		if ((string) $line->cart_id !== (string) $item->cart_id && $signature($line) === $wanted) {
			return $line;
		}
	}
	return null;
}

/**
 * Cut-off hours (Pacific) for the "Order within ... to ship ..." box: the product's own Next Day and
 * Same Day choices when SFS B2Sign Sync mirrored them, else the site's 4pm / 12pm.
 */
function retails_product_cutoff_hours($product_id)
{
	$out = array('next' => 16, 'same' => 12);
	foreach (function_exists('retails_product_turnaround_spec') ? retails_product_turnaround_spec($product_id) : array() as $o) {
		if ('' === $o['cutoff'] || $o['automatic']) {
			continue;
		}
		if ('next_day' === $o['value']) {
			$out['next'] = (int) substr($o['cutoff'], 0, 2);
		} elseif ('same_day' === $o['value']) {
			$out['same'] = (int) substr($o['cutoff'], 0, 2);
		}
	}
	return $out;
}

/**
 * "12:00" → "12pm", "23:59" → "11:59pm", "06:00" → "6am" (as B2Sign prints its cut-off times).
 */
function retails_turnaround_clock($hhmm)
{
	$t = strtotime('2000-01-01 ' . $hhmm);
	return $t ? date('i', $t) === '00' ? date('ga', $t) : date('g:ia', $t) : '';
}

/**
 * The product's own production times (single-product.php), the way B2Sign shows them: grouped under "Same Day",
 * "Next Day" and the slower ones, each with "Cut-off time … PST" and its price ("Free" or "+$8.00"; js/main.js
 * fills in the dollars from each price quote). B2Sign's default (Next Day) is checked. js/ui-polish.js greys out
 * choices closed by the Pacific clock or the size, and hides those the quantity rules out.
 */
function retails_render_turnaround_choices($spec)
{
	$choices = array_values(array_filter($spec, static function ($o) {
		return $o['choice'];
	}));
	if (!$choices) {
		return;
	}
	$checked = '';
	foreach ($choices as $o) {
		if ($o['default']) {
			$checked = $o['value'];
			break;
		}
	}
	if ('' === $checked) {
		// Older data without B2Sign's default: Next Day when offered, as B2Sign checks it.
		$checked = in_array('next_day', array_column($choices, 'value'), true) ? 'next_day' : $choices[0]['value'];
	}
	$sections = array();
	foreach ($choices as $o) {
		$sections[$o['section']][] = $o;
	}
	?>
	<div class="row d-fex align-items-start my-2 mt-3" data-turnaround-choices>
		<div class="col-md-3 col-4">
			<label class="text-capitalize">Production time</label><small class="pb-attr-help">Time to make your order before it ships. Shipping speed (standard, 3-day, 2-day or overnight) is chosen at checkout.</small>
		</div>
		<div class="col-md-9 col-8">
			<?php foreach ($sections as $title => $rows) : ?>
				<div class="pb-turn-section" data-turnaround-section>
					<div class="pb-turn-title"><?php echo esc_html($title); ?></div>
					<?php foreach ($rows as $o) :
						$id = 'next_day' === $o['value'] ? 'turnaroundNextDay' : 'turnaround_' . $o['value'];
						$cut = '' !== $o['cutoff_label'] ? retails_turnaround_clock($o['cutoff_label']) : '';
						$percent = rtrim(rtrim(number_format($o['percent'], 2), '0'), '.');
						?>
						<div class="form-check pb-turn-choice" data-turnaround-option data-value="<?php echo esc_attr($o['value']); ?>" data-min-qty="<?php echo esc_attr($o['min_qty']); ?>" data-max-qty="<?php echo esc_attr(null === $o['max_qty'] ? '' : $o['max_qty']); ?>" data-time-gate="<?php echo esc_attr($o['time_gate']); ?>" data-reopen-at="<?php echo esc_attr($o['reopen_at']); ?>" data-open-off-days="<?php echo $o['open_off_days'] ? '1' : ''; ?>" data-max-both="<?php echo esc_attr(null === $o['max_both_sides'] ? '' : $o['max_both_sides']); ?>" data-percent="<?php echo esc_attr($percent); ?>"<?php echo $o['default'] ? ' data-default' : ''; ?><?php echo 0 === $o['days'] ? ' data-same-day-option' : ''; ?>>
							<input class="form-check-input turnaround-option" type="radio" name="turnaround_option" id="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($o['value']); ?>"<?php echo $checked === $o['value'] ? ' checked' : ''; ?>>
							<label class="form-check-label" for="<?php echo esc_attr($id); ?>">
								<span class="pb-turn-cut"><?php echo esc_html($cut ? sprintf('Cut-off time %s PST', $cut) : $o['label']); ?></span>
								<span class="pb-turn-price" data-turnaround-price><?php echo esc_html($o['percent'] > 0 ? '+' . $percent . '%' : 'Free'); ?></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
