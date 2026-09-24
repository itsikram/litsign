<?php
/**
 * Payment ticket page: /pay/?t=<token>. The admin sends this link from
 * wp-admin → Payment Tickets; every amount is read from the ticket, never the form.
 *
 * @package litsign
 */

$token = isset($_GET['t']) ? preg_replace('/[^A-Za-z0-9]/', '', (string) wp_unslash($_GET['t'])) : '';
$ticket_id = wholesale_find_ticket_by_token($token);
$ticket = $ticket_id ? wholesale_get_ticket($ticket_id) : null;
$is_preview = $ticket && current_user_can('edit_post', $ticket['id']);

if (!$ticket || (!$is_preview && in_array($ticket['status'], array('draft', 'cancelled'), true))) {
	status_header(404);
	nocache_headers();
	include get_query_template('404');
	exit;
}

nocache_headers();
wholesale_ticket_mark_viewed($ticket);

$business = wholesale_ticket_business();
$totals = $ticket['totals'];
$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
$payment_disabled = wholesale_setting_enabled('payment_disabled');
$name_parts = explode(' ', trim($ticket['customer_name']), 2);
$is_complete = in_array($ticket['status'], array('paid', 'accepted'), true);
$is_expired = !$is_complete && wholesale_ticket_is_expired($ticket);
$can_pay = 'sent' === $ticket['status'] && !$is_expired;
$issued = $ticket['sent_at'] ? $ticket['sent_at'] : $ticket['created'];
$proof_url = $ticket['proof_id'] ? wp_get_attachment_url($ticket['proof_id']) : '';
$proof_image = $ticket['proof_id'] && wp_attachment_is_image($ticket['proof_id']) ? wp_get_attachment_image_url($ticket['proof_id'], 'large') : '';
$amount_label = wholesale_format_ticket_amount($ticket['amount']);

get_header();
?>

<main id="primary" class="site-main wpt-pay">
	<div class="wpt-pay__wrap">

		<?php if ($is_preview) : ?>
			<div class="wpt-pay__preview" role="status">
				<strong>Preview.</strong> This is exactly what your customer sees<?php echo 'draft' === $ticket['status'] ? ', once the ticket is sent' : ''; ?>. Payment is turned off while you're logged in as an admin.
				<a href="<?php echo esc_url(get_edit_post_link($ticket['id'])); ?>">Back to ticket</a>
			</div>
		<?php endif; ?>

		<header class="wpt-pay__top">
			<div class="wpt-pay__heading">
				<p class="wpt-pay__eyebrow">Payment request <span><?php echo esc_html($ticket['number']); ?></span></p>
				<h1><?php echo esc_html($ticket['title']); ?></h1>
			</div>
			<?php if ($is_complete) : ?>
				<span class="wpt-pay__badge wpt-pay__badge--paid"><?php echo 'paid' === $ticket['status'] ? 'Paid' : 'Confirmed'; ?></span>
			<?php elseif ($is_expired) : ?>
				<span class="wpt-pay__badge wpt-pay__badge--expired">Expired</span>
			<?php elseif ('cancelled' === $ticket['status']) : ?>
				<span class="wpt-pay__badge wpt-pay__badge--expired">Cancelled</span>
			<?php else : ?>
				<span class="wpt-pay__badge">Awaiting payment</span>
			<?php endif; ?>
		</header>

		<div class="wpt-pay__grid">
			<article class="wpt-invoice" aria-label="Payment request details">
				<div class="wpt-invoice__parties">
					<div>
						<h2>From</h2>
						<p><strong><?php echo esc_html($business['name']); ?></strong><br><?php echo esc_html($business['address']); ?><br><?php echo esc_html($business['phone']); ?></p>
					</div>
					<div>
						<h2>Billed to</h2>
						<p><strong><?php echo esc_html($ticket['customer_name']); ?></strong>
							<?php if ($ticket['customer_company']) : ?><br><?php echo esc_html($ticket['customer_company']); ?><?php endif; ?>
							<br><?php echo esc_html($ticket['customer_email']); ?></p>
					</div>
					<dl class="wpt-invoice__dates">
						<div><dt>Issued</dt><dd><?php echo esc_html(date_i18n('M j, Y', strtotime($issued))); ?></dd></div>
						<?php if ($ticket['due']) : ?><div><dt>Pay by</dt><dd><?php echo esc_html(date_i18n('M j, Y', strtotime($ticket['due']))); ?></dd></div><?php endif; ?>
						<?php if ($ticket['paid_at']) : ?><div><dt><?php echo 'paid' === $ticket['status'] ? 'Paid' : 'Confirmed'; ?></dt><dd><?php echo esc_html(date_i18n('M j, Y', strtotime($ticket['paid_at']))); ?></dd></div><?php endif; ?>
					</dl>
				</div>

				<?php if ($ticket['message']) : ?>
					<div class="wpt-invoice__message">
						<p><?php echo nl2br(esc_html($ticket['message'])); ?></p>
					</div>
				<?php endif; ?>

				<div class="wpt-invoice__items" role="table" aria-label="Items">
					<div class="wpt-invoice__row wpt-invoice__row--head" role="row">
						<span role="columnheader">Item</span>
						<span role="columnheader" class="wpt-c">Qty</span>
						<span role="columnheader" class="wpt-r">Price</span>
						<span role="columnheader" class="wpt-r">Amount</span>
					</div>
					<?php foreach ($ticket['items'] as $item) :
						$thumb = $item['design'] ? $item['design']['url'] : wholesale_ticket_product_thumb($item['product_id']);
						$specs = wholesale_ticket_item_specs($item);
						?>
						<div class="wpt-invoice__row" role="row">
							<div class="wpt-invoice__item" role="cell">
								<span class="wpt-invoice__thumb" aria-hidden="true"><?php if ($thumb) : ?><img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy"><?php else : ?><svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M22.7 19.3 13.6 10.2a6 6 0 0 0-7.9-7.9l3.9 3.9-2.8 2.8-4-3.8a6 6 0 0 0 7.9 7.9l9.1 9.1a1 1 0 0 0 1.4 0l1.5-1.5a1 1 0 0 0 0-1.4Z"/></svg><?php endif; ?></span>
								<div>
									<strong><?php echo esc_html($item['title']); ?></strong>
									<?php if ($specs) : ?>
										<ul class="wpt-invoice__specs">
											<?php foreach ($specs as $spec) : ?><li><?php echo esc_html($spec); ?></li><?php endforeach; ?>
										</ul>
									<?php endif; ?>
									<?php if ($item['notes']) : ?><p class="wpt-invoice__notes"><?php echo nl2br(esc_html($item['notes'])); ?></p><?php endif; ?>
									<?php if ($item['design']) : ?><a class="wpt-invoice__design" href="<?php echo esc_url($item['design']['url']); ?>" target="_blank" rel="noopener">View your channel letter design</a><?php endif; ?>
								</div>
							</div>
							<span role="cell" class="wpt-c" data-label="Qty"><?php echo esc_html((string) $item['qty']); ?></span>
							<span role="cell" class="wpt-r" data-label="Price"><?php echo esc_html(wholesale_format_ticket_amount($item['unit_price'])); ?></span>
							<span role="cell" class="wpt-r wpt-invoice__amount" data-label="Amount"><?php echo esc_html(wholesale_format_ticket_amount(wholesale_ticket_line_total($item))); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<dl class="wpt-invoice__totals">
					<div><dt>Subtotal</dt><dd><?php echo esc_html(wholesale_format_ticket_amount($totals['subtotal'])); ?></dd></div>
					<?php if ($totals['discount'] > 0) : ?><div class="wpt-invoice__discount"><dt>Discount</dt><dd>−<?php echo esc_html(wholesale_format_ticket_amount($totals['discount'])); ?></dd></div><?php endif; ?>
					<?php if ($totals['shipping'] > 0) : ?><div><dt>Shipping</dt><dd><?php echo esc_html(wholesale_format_ticket_amount($totals['shipping'])); ?></dd></div><?php endif; ?>
					<?php if ($totals['tax'] > 0) : ?><div><dt>Tax (<?php echo esc_html(rtrim(rtrim(number_format($totals['tax_rate'], 3), '0'), '.')); ?>%)</dt><dd><?php echo esc_html(wholesale_format_ticket_amount($totals['tax'])); ?></dd></div><?php endif; ?>
					<div class="wpt-invoice__grand"><dt><?php echo $is_complete ? 'Total' : 'Total due'; ?></dt><dd><?php echo esc_html($amount_label); ?></dd></div>
				</dl>

				<?php if ($proof_url) : ?>
					<div class="wpt-invoice__proof">
						<h2>Design proof</h2>
						<?php if ($proof_image) : ?>
							<a href="<?php echo esc_url($proof_url); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url($proof_image); ?>" alt="Design proof for <?php echo esc_attr($ticket['title']); ?>" loading="lazy"></a>
						<?php endif; ?>
						<a class="wpt-invoice__proof-link" href="<?php echo esc_url($proof_url); ?>" target="_blank" rel="noopener">Open proof in a new tab</a>
					</div>
				<?php endif; ?>
			</article>

			<aside class="wpt-paybox" id="ticket-pay" aria-labelledby="wpt-paybox-title">
				<div class="wpt-paybox__amount">
					<span id="wpt-paybox-title"><?php echo $is_complete ? 'Amount paid' : 'Amount due'; ?></span>
					<strong><?php echo esc_html($amount_label); ?></strong>
				</div>

				<?php if ($is_complete) : ?>
					<div class="wpt-paybox__state wpt-paybox__state--done">
						<h2>Thank you!</h2>
						<p><?php echo 'paid' === $ticket['status'] ? 'Your payment was received' : 'Your order was confirmed'; ?><?php echo $ticket['paid_at'] ? ' on ' . esc_html(date_i18n('F j, Y', strtotime($ticket['paid_at']))) : ''; ?>. We've emailed you a confirmation and our team will be in touch about production.</p>
					</div>
				<?php elseif ($is_expired) : ?>
					<div class="wpt-paybox__state">
						<h2>This link has expired</h2>
						<p>The pay-by date has passed. Contact us and we'll send you an updated payment request.</p>
					</div>
				<?php elseif (!$can_pay) : ?>
					<div class="wpt-paybox__state">
						<?php if ('cancelled' === $ticket['status']) : ?>
							<h2>Request cancelled</h2>
							<p>This payment request was cancelled and can't be paid.</p>
						<?php else : ?>
							<h2>Not available yet</h2>
							<p>This payment request hasn't been sent to the customer.</p>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<?php if ($message) : ?>
						<div class="wpt-paybox__error" role="alert"><?php echo esc_html($message); ?></div>
					<?php endif; ?>

					<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="wpt-payform" id="ticketPayForm">
						<input type="hidden" name="action" value="wholesale_pay_ticket">
						<input type="hidden" name="ticket_token" value="<?php echo esc_attr($token); ?>">
						<?php wp_nonce_field('wholesale_pay_ticket_' . $ticket['id'], 'wholesale_ticket_pay_nonce'); ?>

						<fieldset>
							<legend>Billing details</legend>
							<div class="wpt-payform__row">
								<p><label for="billingFirstName">First name</label><input type="text" required name="billing_fname" id="billingFirstName" autocomplete="given-name" value="<?php echo esc_attr($name_parts[0]); ?>"></p>
								<p><label for="billingLastName">Last name</label><input type="text" required name="billing_lname" id="billingLastName" autocomplete="family-name" value="<?php echo esc_attr($name_parts[1] ?? ''); ?>"></p>
							</div>
							<p><label for="billingEmail">Email</label><input type="email" required name="billing_email" id="billingEmail" autocomplete="email" value="<?php echo esc_attr($ticket['customer_email']); ?>"></p>
							<p><label for="billingCompany">Company <span>(optional)</span></label><input type="text" name="billing_company" id="billingCompany" autocomplete="organization" value="<?php echo esc_attr($ticket['customer_company']); ?>"></p>
							<p><label for="billingAddress">Billing address</label><input type="text" required name="billing_address" id="billingAddress" autocomplete="address-line1" placeholder="Street address">
								<input type="text" name="billing_address_2" id="billingAddress2" autocomplete="address-line2" placeholder="Suite, unit (optional)" aria-label="Address line 2"></p>
							<div class="wpt-payform__row wpt-payform__row--3">
								<p><label for="billingCity">City</label><input type="text" required name="billing_city" id="billingCity" autocomplete="address-level2"></p>
								<p><label for="billingState">State</label><input type="text" required name="billing_state" id="billingState" autocomplete="address-level1" maxlength="30"></p>
								<p><label for="billingZip">ZIP</label><input type="text" required name="billing_zip" id="billingZip" autocomplete="postal-code" inputmode="numeric" maxlength="10"></p>
							</div>
							<p><label for="billingTel">Phone <span>(optional)</span></label><input type="tel" name="billing_tel" id="billingTel" autocomplete="tel" value="<?php echo esc_attr($ticket['customer_phone']); ?>"></p>
						</fieldset>

						<fieldset>
							<legend>Payment</legend>
							<?php if ($payment_disabled) : ?>
								<p class="wpt-payform__info">Online card payment is temporarily unavailable. Confirm your order and we'll contact you to arrange payment.</p>
							<?php else : ?>
								<?php if ('direct' === wholesale_payment_method()) : ?>
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
							<?php endif; ?>
						</fieldset>

						<p class="wpt-payform__status" data-pay-status role="alert" hidden></p>
						<button type="submit" class="wpt-payform__submit" id="ticketPaySubmit" data-pay-button <?php disabled($is_preview); ?>>
							<?php echo $payment_disabled ? 'Confirm order' : esc_html('Pay ' . $amount_label); ?>
						</button>
						<p class="wpt-payform__secure">
							<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a5 5 0 0 0-5 5v3H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-2V6a5 5 0 0 0-5-5Zm-3 5a3 3 0 0 1 6 0v3H9V6Z"/></svg>
							<?php echo $payment_disabled ? 'No payment is taken online.' : ('direct' === wholesale_payment_method() ? 'Secure payment processed by Elavon Converge. We never store your card number.' : 'Secure payment through Elavon Converge. Your card details are entered in their secure window and never reach this website.'); ?>
						</p>
					</form>
				<?php endif; ?>

				<p class="wpt-paybox__help">Questions about this request? Call <strong><?php echo esc_html($business['phone']); ?></strong> or email <strong><?php echo esc_html($business['email']); ?></strong>.</p>
			</aside>
		</div>
	</div>
</main>

<?php
get_footer();
