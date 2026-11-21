<?php
/**
 * Order payment page: /pay/?o=<token>. Staff send this link from the order's "Payment link"
 * box (inc/order-pay-link.php). The amount is the order total, read on the server.
 *
 * Loaded by page-pay.php. Uses the payment-ticket page styles (css/payment-ticket.css).
 *
 * @package litsign
 */

$token = retails_order_pay_clean_token($_GET['o'] ?? '');
$order_id = retails_find_order_by_pay_token($token);

if (!$order_id) {
	status_header(404);
	nocache_headers();
	include get_query_template('404');
	exit;
}

nocache_headers();
retails_order_pay_mark_viewed($order_id);

$business = retails_ticket_business();
$number = retails_order_number($order_id);
$items = retails_decode_order_meta_array(get_post_meta($order_id, 'product_json', true));
$cost = retails_decode_order_meta_array(get_post_meta($order_id, 'product_cost', true));
$billing = retails_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
$amount = retails_order_total($order_id);
$amount_label = '$' . number_format($amount, 2);
$money = static function ($value) {
	return '$' . number_format((float) $value, 2);
};
$payment_status = retails_get_payment_status($order_id);
$is_preview = current_user_can('edit_post', $order_id);
$is_cancelled = in_array(get_post_status($order_id), array('cancelled', 'refunded', 'failed', 'trash'), true);
$is_paid = !$is_cancelled && in_array($payment_status, array('paid', 'paid_offline', 'needs_review', 'partially_refunded'), true);
$can_pay = retails_order_pay_is_open($order_id);
$paid_at = get_post_meta($order_id, '_pay_link_paid_at', true);
$card_ready = (bool) retails_converge_credentials();
$direct = 'direct' === retails_payment_method();
$placed = get_post_time('U', true, $order_id);
$field = static function ($key) use ($billing) {
	return isset($billing[$key]) && is_scalar($billing[$key]) ? (string) $billing[$key] : '';
};

get_header();
?>

<main id="primary" class="site-main wpt-pay">
	<div class="wpt-pay__wrap">

		<?php if ($is_preview) : ?>
			<div class="wpt-pay__preview" role="status">
				<strong>Preview.</strong> This is what your customer sees. Payment is turned off while you're logged in as staff.
				<a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>">Back to order</a>
			</div>
		<?php endif; ?>

		<header class="wpt-pay__top">
			<div class="wpt-pay__heading">
				<p class="wpt-pay__eyebrow">Order payment <span>#<?php echo esc_html($number); ?></span></p>
				<h1>Pay for your order</h1>
			</div>
			<?php if ($is_paid) : ?>
				<span class="wpt-pay__badge wpt-pay__badge--paid">Paid</span>
			<?php elseif ($is_cancelled) : ?>
				<span class="wpt-pay__badge wpt-pay__badge--expired">Cancelled</span>
			<?php else : ?>
				<span class="wpt-pay__badge">Awaiting payment</span>
			<?php endif; ?>
		</header>

		<div class="wpt-pay__grid">
			<article class="wpt-invoice" aria-label="Order details">
				<div class="wpt-invoice__parties">
					<div>
						<h2>From</h2>
						<p><strong><?php echo esc_html($business['name']); ?></strong><br><?php echo esc_html($business['address']); ?><br><?php echo esc_html($business['phone']); ?></p>
					</div>
					<div>
						<h2>Billed to</h2>
						<p><strong><?php echo esc_html(trim($field('billing_fname') . ' ' . $field('billing_lname'))); ?></strong>
							<?php if ($field('billing_company')) : ?><br><?php echo esc_html($field('billing_company')); ?><?php endif; ?>
							<br><?php echo esc_html($field('billing_email')); ?></p>
					</div>
					<dl class="wpt-invoice__dates">
						<div><dt>Ordered</dt><dd><?php echo esc_html(wp_date('M j, Y', $placed)); ?></dd></div>
						<?php if ($paid_at) : ?><div><dt>Paid</dt><dd><?php echo esc_html(date_i18n('M j, Y', strtotime($paid_at))); ?></dd></div><?php endif; ?>
					</dl>
				</div>

				<div class="wpt-invoice__items" role="table" aria-label="Items">
					<div class="wpt-invoice__row wpt-invoice__row--head" role="row">
						<span role="columnheader">Item</span>
						<span role="columnheader" class="wpt-c">Qty</span>
						<span role="columnheader" class="wpt-r">Price</span>
						<span role="columnheader" class="wpt-r">Amount</span>
					</div>
					<?php foreach ($items as $item) :
						if (!is_array($item)) {
							continue;
						}
						$qty = max(1, (int) ($item['product_quantity'] ?? 1));
						$line = (float) ($item['product_subtotal'] ?? 0);
						$thumb = isset($item['product_thumbnail']) && is_string($item['product_thumbnail']) ? $item['product_thumbnail'] : '';
						$specs = retails_item_specs($item);
						unset($specs['Design Url'], $specs['My Artwork'], $specs['Payment Ticket']);
						?>
						<div class="wpt-invoice__row" role="row">
							<div class="wpt-invoice__item" role="cell">
								<span class="wpt-invoice__thumb" aria-hidden="true"><?php if ($thumb) : ?><img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy"><?php endif; ?></span>
								<div>
									<strong><?php echo esc_html($item['product_title'] ?? ''); ?></strong>
									<?php if ($specs) : ?>
										<ul class="wpt-invoice__specs">
											<?php foreach (array_slice($specs, 0, 12, true) as $label => $value) : ?><li><?php echo esc_html($label . ': ' . wp_strip_all_tags($value)); ?></li><?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</div>
							<span role="cell" class="wpt-c" data-label="Qty"><?php echo esc_html((string) $qty); ?></span>
							<span role="cell" class="wpt-r" data-label="Price"><?php echo esc_html($money($line / $qty)); ?></span>
							<span role="cell" class="wpt-r wpt-invoice__amount" data-label="Amount"><?php echo esc_html($money($line)); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<dl class="wpt-invoice__totals">
					<div><dt>Subtotal</dt><dd><?php echo esc_html($money($cost['sub_total'] ?? 0)); ?></dd></div>
					<div><dt>Shipping<?php echo !empty($cost['shipping_method']) ? ' (' . esc_html($cost['shipping_method']) . ')' : ''; ?></dt><dd><?php echo esc_html($money($cost['shipping_cost'] ?? 0)); ?></dd></div>
					<?php if ((float) ($cost['tax'] ?? 0) > 0) : ?><div><dt>Tax</dt><dd><?php echo esc_html($money($cost['tax'])); ?></dd></div><?php endif; ?>
					<?php // After a refund: the amount left on the order (inc/order-net-total.php). $amount stays the charge. ?>
					<?php $net = $is_paid && function_exists('retails_order_net_amounts') ? retails_order_net_amounts($order_id) : null; ?>
					<?php if ($net && $net['has_refund']) : ?>
						<div><dt>Order total</dt><dd><?php echo esc_html($amount_label); ?></dd></div>
						<div><dt>Refunded</dt><dd>&minus;<?php echo esc_html($money($net['refunded'])); ?></dd></div>
						<div class="wpt-invoice__grand"><dt>Total after refund</dt><dd><?php echo esc_html($money($net['net'])); ?></dd></div>
					<?php else : ?>
						<div class="wpt-invoice__grand"><dt><?php echo $is_paid ? 'Total' : 'Total due'; ?></dt><dd><?php echo esc_html($amount_label); ?></dd></div>
					<?php endif; ?>
				</dl>
			</article>

			<aside class="wpt-paybox" id="ticket-pay" aria-labelledby="wpt-paybox-title">
				<div class="wpt-paybox__amount">
					<span id="wpt-paybox-title"><?php echo $net && $net['has_refund'] ? 'Paid after refund' : ($is_paid ? 'Amount paid' : 'Amount due'); ?></span>
					<strong><?php echo esc_html($net && $net['has_refund'] ? $money($net['net']) : $amount_label); ?></strong>
				</div>

				<?php if ($is_paid) : ?>
					<div class="wpt-paybox__state wpt-paybox__state--done">
						<h2>Thank you!</h2>
						<p>This order is paid<?php echo $paid_at ? ' (' . esc_html(date_i18n('F j, Y', strtotime($paid_at))) . ')' : ''; ?>. We've emailed you a confirmation and will email you again when your order ships.</p>
					</div>
				<?php elseif ($is_cancelled) : ?>
					<div class="wpt-paybox__state">
						<h2>Order cancelled</h2>
						<p>This order was cancelled and can't be paid. Contact us if you'd like to place it again.</p>
					</div>
				<?php elseif (!$can_pay || !$card_ready) : ?>
					<div class="wpt-paybox__state">
						<h2>Online payment unavailable</h2>
						<p>Please call us and we'll take your payment by phone.</p>
					</div>
				<?php else : ?>
					<?php // Staff preview: no id, so checkout.js leaves the form (and its disabled button) alone. ?>
					<form method="post" action="#" class="wpt-payform"<?php echo $is_preview ? ' onsubmit="return false"' : ' id="orderPayForm"'; ?>>
						<input type="hidden" name="order_token" value="<?php echo esc_attr($token); ?>">

						<fieldset>
							<legend>Billing details</legend>
							<div class="wpt-payform__row">
								<p><label for="billingFirstName">First name</label><input type="text" required name="billing_fname" id="billingFirstName" autocomplete="given-name" value="<?php echo esc_attr($field('billing_fname')); ?>"></p>
								<p><label for="billingLastName">Last name</label><input type="text" required name="billing_lname" id="billingLastName" autocomplete="family-name" value="<?php echo esc_attr($field('billing_lname')); ?>"></p>
							</div>
							<p><label for="billingEmail">Email</label><input type="email" required name="billing_email" id="billingEmail" autocomplete="email" value="<?php echo esc_attr($field('billing_email')); ?>"></p>
							<p><label for="billingCompany">Company <span>(optional)</span></label><input type="text" name="billing_company" id="billingCompany" autocomplete="organization" value="<?php echo esc_attr($field('billing_company')); ?>"></p>
							<p><label for="billingAddress">Billing address</label><input type="text" required name="billing_address" id="billingAddress" autocomplete="address-line1" placeholder="Street address" value="<?php echo esc_attr($field('billing_address')); ?>">
								<input type="text" name="billing_address_2" id="billingAddress2" autocomplete="address-line2" placeholder="Suite, unit (optional)" aria-label="Address line 2" value="<?php echo esc_attr($field('billing_address_2')); ?>"></p>
							<div class="wpt-payform__row wpt-payform__row--3">
								<p><label for="billingCity">City</label><input type="text" required name="billing_city" id="billingCity" autocomplete="address-level2" value="<?php echo esc_attr($field('billing_city')); ?>"></p>
								<p><label for="billingState">State</label><input type="text" required name="billing_state" id="billingState" autocomplete="address-level1" maxlength="30" value="<?php echo esc_attr($field('billing_state')); ?>"></p>
								<p><label for="billingZip">ZIP</label><input type="text" required name="billing_zip" id="billingZip" autocomplete="postal-code" inputmode="numeric" maxlength="10" value="<?php echo esc_attr($field('billing_zip')); ?>"></p>
							</div>
							<p><label for="billingTel">Phone <span>(optional)</span></label><input type="tel" name="billing_tel" id="billingTel" autocomplete="tel" value="<?php echo esc_attr($field('billing_tel')); ?>"></p>
						</fieldset>

						<fieldset>
							<legend>Payment</legend>
							<?php if ($direct) : ?>
								<p><label for="cardNumber">Card number</label><input required type="text" name="card_number" id="cardNumber" inputmode="numeric" autocomplete="cc-number" pattern="[0-9 ]{12,23}" maxlength="23" placeholder="1234 5678 9012 3456"></p>
								<div class="wpt-payform__row wpt-payform__row--3">
									<p><label for="expMonth">Month</label>
										<select required name="card_exp_month" id="expMonth" autocomplete="cc-exp-month">
											<option value="">MM</option>
											<?php for ($month = 1; $month <= 12; $month++) : ?>
												<option value="<?php echo esc_attr(sprintf('%02d', $month)); ?>"><?php echo esc_html(sprintf('%02d', $month)); ?></option>
											<?php endfor; ?>
										</select></p>
									<p><label for="expYear">Year</label>
										<select required name="card_exp_year" id="expYear" autocomplete="cc-exp-year">
											<option value="">YYYY</option>
											<?php for ($exp_year = (int) gmdate('Y'); $exp_year <= (int) gmdate('Y') + 12; $exp_year++) : ?>
												<option value="<?php echo esc_attr(substr((string) $exp_year, -2)); ?>"><?php echo esc_html((string) $exp_year); ?></option>
											<?php endfor; ?>
										</select></p>
									<p><label for="cardCvv">CVV</label><input required type="text" name="card_cvv" id="cardCvv" inputmode="numeric" autocomplete="cc-csc" pattern="[0-9]{3,4}" maxlength="4" placeholder="123"></p>
								</div>
							<?php else : ?>
								<p class="wpt-payform__info">When you click <strong>Pay</strong>, a secure window from our card processor (Elavon Converge) opens for your card details. Your card number never passes through this website.</p>
							<?php endif; ?>
						</fieldset>

						<p class="wpt-payform__status" data-pay-status role="alert" hidden></p>
						<button type="submit" class="wpt-payform__submit" data-pay-button <?php disabled($is_preview); ?>><?php echo esc_html('Pay ' . $amount_label); ?></button>
						<p class="wpt-payform__secure">
							<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a5 5 0 0 0-5 5v3H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-2V6a5 5 0 0 0-5-5Zm-3 5a3 3 0 0 1 6 0v3H9V6Z"/></svg>
							<?php echo $direct ? 'Secure payment processed by Elavon Converge. We never store your card number.' : 'Secure payment through Elavon Converge. Your card details are entered in their secure window and never reach this website.'; ?>
						</p>
					</form>
				<?php endif; ?>

				<p class="wpt-paybox__help">Questions about this order? Call <strong><?php echo esc_html($business['phone']); ?></strong> or email <strong><?php echo esc_html($business['email']); ?></strong>.</p>
			</aside>
		</div>
	</div>
</main>

<?php
get_footer();
