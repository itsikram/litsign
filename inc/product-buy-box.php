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
 *   (wholesale_estimated_ship_datetime(), wholesale_estimated_delivery_date()).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Production business days for one product, as wholesale_cart_production_days() counts
 * them for a cart: the product's turnaround (at least 1), and at least 4 for channel letters.
 */
function wholesale_product_production_days($product_id)
{
	$days = max(1, (int) get_post_meta($product_id, '_product_turnaround', true));
	return wholesale_product_is_channel_letter($product_id) ? max($days, 4) : $days;
}

/**
 * "Order within 2 hr 14 min to ship Thu, Nov 6" box. Printed hidden; js/ui-polish.js
 * fills it in Pacific time and shows it, so a cached page never shows a stale date.
 * Not for out of stock or backordered products (single-product.php shows the backorder date).
 */
function wholesale_render_product_eta($product_id)
{
	if (in_array(wholesale_product_stock_status($product_id), array('outofstock', 'backorder'), true)) {
		return;
	}
	$is_cl = wholesale_product_is_channel_letter($product_id);
	$rates = array_filter(array_map('floatval', explode(',', (string) wholesale_get_setting($is_cl ? 'channel_shipping_options' : 'standard_shipping_options'))));
	// Transit business days per checkout speed (wholesale_estimated_delivery_date()).
	$transit = array_slice(array(6, 3, 2, 1), 0, max(1, count($rates)));
	$speed_names = array('standard', '3-day', '2-day', 'overnight');
	$cutoffs = wholesale_product_cutoff_hours($product_id);
	?>
	<div class="pd-eta" data-production-days="<?php echo esc_attr(wholesale_product_production_days($product_id)); ?>" data-cutoff="<?php echo esc_attr($cutoffs['next']); ?>" data-same-day-cutoff="<?php echo esc_attr($cutoffs['same']); ?>" data-fastest-days="<?php echo esc_attr(end($transit)); ?>" data-fastest-name="<?php echo esc_attr($speed_names[count($transit) - 1]); ?>" hidden>
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
 * @param string $stock_status wholesale_product_stock_status().
 * @param float  $initial_cost The price the page opens on; 0 disables the buttons until a price shows.
 */
function wholesale_render_product_actions($stock_status, $initial_cost)
{
	$disabled = $initial_cost <= 0 ? ' disabled' : '';
	?>
	<div class="pd-actions">
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
			<?php
			// B2Sign quantity pricing (SFS B2Sign Sync): the price above already follows the quantity.
			$qty_pricing = function_exists('wholesale_product_qty_pricing') ? wholesale_product_qty_pricing(get_the_ID()) : null;
			if ($qty_pricing) :
				?>
				<small class="pd-qty__note"><?php echo esc_html(!empty($qty_pricing['sheet']) ? sprintf('Order more, pay less per piece. Pieces that share a %s x %s ft sheet cost no more than the full sheet.', $qty_pricing['sheet']['w'] / 12, $qty_pricing['sheet']['h'] / 12) : 'Order more, pay less per piece. The price updates with the quantity.'); ?></small>
			<?php endif; ?>
		</div>
		<?php if ('outofstock' === $stock_status) : ?>
			<button id="addToCartBtn" type="button" class="btn btn-secondary d-block w-100 fw-bold pd-actions__out" disabled>Out of stock</button>
		<?php else : ?>
			<?php // Add To Cart stays first: pressing Enter in a field submits with the first submit button. ?>
			<button id="addToCartBtn" type="submit" class="add-to-cart-btn btn btn-danger d-block w-100 fw-bold"<?php echo $disabled; ?>>Add To Cart</button>
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
 * Cards accepted at checkout, as text badges (checkout.php lists the same four).
 */
function wholesale_render_product_pay_row()
{
	if (wholesale_setting_enabled('payment_disabled')) {
		return;
	}
	?>
	<div class="pd-pay" aria-label="Payment methods accepted">
		<svg class="pd-pay__lock" viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 1a5 5 0 0 0-5 5v3H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-2V6a5 5 0 0 0-5-5Zm-3 5a3 3 0 0 1 6 0v3H9V6Z"/></svg>
		<span class="pd-pay__label">We accept</span>
		<ul class="pd-pay__cards">
			<li>Visa</li>
			<li>Mastercard</li>
			<li>Amex</li>
			<li>Discover</li>
		</ul>
	</div>
	<?php
}

/**
 * The line in $items that is the same order as $item (same product, options, quantity,
 * price, job name and design), or null. Buy Now uses it so going back from checkout and
 * pressing Buy Now again doesn't put the same item in the cart twice.
 *
 * @param object[] $items Cart lines.
 * @param object   $item  The line just added.
 */
function wholesale_cart_same_item($items, $item)
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
function wholesale_product_cutoff_hours($product_id)
{
	$out = array('next' => 16, 'same' => 12);
	foreach (function_exists('wholesale_product_turnaround_spec') ? wholesale_product_turnaround_spec($product_id) : array() as $o) {
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
 * The product's own production times (single-product.php), mirrored from B2Sign: one radio per choice,
 * with the quantities it allows. js/ui-polish.js hides choices the quantity or the Pacific clock rules out.
 */
function wholesale_render_turnaround_choices($spec)
{
	$free = null;
	foreach ($spec as $o) {
		if ($o['automatic']) {
			$free = $o;
		}
	}
	$first = true;
	?>
	<div class="row d-fex align-items-center my-2 mt-3" data-turnaround-choices>
		<div class="col-md-3 col-4">
			<label class="text-capitalize">Production time</label><small class="pb-attr-help">Time to make your order before it ships. Shipping speed (standard, 3-day, 2-day or overnight) is chosen at checkout.</small>
		</div>
		<div class="col-md-9 col-8">
			<div class="row">
				<?php foreach ($spec as $o) :
					if ($o['automatic']) {
						continue;
					}
					$id = 'turnaround_' . $o['value'];
					$same = 0 === $o['days'];
					$note = '' !== $o['cutoff'] ? sprintf('Cut-off time %s PST', date('ga', strtotime($o['cutoff']))) : '';
					if ($o['min_qty'] > 1 || null !== $o['max_qty']) {
						$note .= ($note ? ' · ' : '') . (null === $o['max_qty'] ? sprintf('%d+ items', $o['min_qty']) : sprintf('%d–%d items', $o['min_qty'], $o['max_qty']));
					}
					if ($o['percent'] > 0) {
						$note .= ($note ? ' · ' : '') . sprintf('+%s%%', rtrim(rtrim(number_format($o['percent'], 2), '0'), '.'));
					}
					?>
					<div class="col-12 col-md-6" data-turnaround-option data-min-qty="<?php echo esc_attr($o['min_qty']); ?>" data-max-qty="<?php echo esc_attr(null === $o['max_qty'] ? '' : $o['max_qty']); ?>" data-cutoff="<?php echo esc_attr($same ? $o['cutoff'] : ''); ?>"<?php echo $same ? ' data-same-day-option' : ''; ?>>
						<div class="form-check">
							<input class="form-check-input turnaround-option" type="radio" name="turnaround_option" id="<?php echo esc_attr('next_day' === $o['value'] ? 'turnaroundNextDay' : $id); ?>" value="<?php echo esc_attr($o['value']); ?>"<?php echo $first ? ' checked' : ''; ?>>
							<label class="form-check-label" for="<?php echo esc_attr('next_day' === $o['value'] ? 'turnaroundNextDay' : $id); ?>">
								<?php echo esc_html($o['label']); ?>
								<?php if ($note) : ?><p class="form-text mb-0 mt-0 fw-normal"><?php echo esc_html($note); ?></p><?php endif; ?>
								<?php if ($free && 'next_day' === $o['value']) : ?><p class="form-text mb-0 mt-0 fw-normal"><?php echo esc_html(sprintf('Approve your artwork before %s PST on a weekday and it ships the same day, free.', date('ga', strtotime($free['cutoff'] ?: '06:00')))); ?></p><?php endif; ?>
							</label>
						</div>
					</div>
					<?php $first = false; endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}
