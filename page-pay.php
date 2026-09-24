<?php
/**
 * Payment ticket page: /pay/?t=<token>. The admin sends this link from
 * wp-admin → Payment Tickets; the amount is read from the ticket, never the form.
 *
 * @package litsign
 */

$token = isset($_GET['t']) ? preg_replace('/[^A-Za-z0-9]/', '', (string) wp_unslash($_GET['t'])) : '';
$ticket_id = wholesale_find_ticket_by_token($token);
$ticket = $ticket_id ? wholesale_get_ticket($ticket_id) : null;

if (!$ticket || 'cancelled' === $ticket['status'] || 'draft' === $ticket['status']) {
	status_header(404);
	nocache_headers();
	include get_query_template('404');
	exit;
}

nocache_headers();
$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
$payment_disabled = wholesale_setting_enabled('payment_disabled');
$name_parts = explode(' ', trim($ticket['customer_name']), 2);

get_header();
?>

<main id="primary" class="site-main ticket-pay-page">
	<div class="container py-5">
		<div class="row justify-content-center">
			<div class="col-lg-10">
				<h1 class="fs-2 mb-4">Payment for your custom order</h1>

				<div class="row g-4">
					<div class="col-md-5 order-md-2">
						<div class="border rounded p-4 bg-light">
							<p class="text-uppercase small text-muted mb-1">Payment request</p>
							<h2 class="fs-4"><?php echo esc_html($ticket['title']); ?></h2>
							<?php if ($ticket['description']) : ?>
								<p class="mb-3"><?php echo nl2br(esc_html($ticket['description'])); ?></p>
							<?php endif; ?>
							<p class="fs-3 fw-bold text-primary mb-1"><?php echo esc_html(wholesale_format_ticket_amount($ticket['amount'])); ?></p>
							<p class="small text-muted mb-0">Total due. No additional tax or shipping is added.</p>
							<?php if ($ticket['due']) : ?>
								<p class="small mt-2 mb-0"><strong>Pay by:</strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ticket['due']))); ?></p>
							<?php endif; ?>
							<hr>
							<p class="small mb-0">Questions? Call <a href="tel:+18664362101">866-436-2101</a> or email <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a>.</p>
						</div>
					</div>

					<div class="col-md-7 order-md-1" id="ticket-pay">
						<?php if (in_array($ticket['status'], array('paid', 'accepted'), true)) : ?>
							<div class="alert alert-success" role="status">
								<strong>This payment request has already been completed.</strong> Thank you! If you have questions about your order, please contact us.
							</div>
						<?php elseif (wholesale_ticket_is_expired($ticket)) : ?>
							<div class="alert alert-warning" role="status">
								<strong>This payment link has expired.</strong> Please contact us and we will send you a new one.
							</div>
						<?php else : ?>
							<?php if ($message) : ?>
								<div class="alert alert-danger" role="alert"><?php echo esc_html($message); ?></div>
							<?php endif; ?>

							<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="ticketPayForm">
								<input type="hidden" name="action" value="wholesale_pay_ticket">
								<input type="hidden" name="ticket_token" value="<?php echo esc_attr($token); ?>">
								<?php wp_nonce_field('wholesale_pay_ticket_' . $ticket['id'], 'wholesale_ticket_pay_nonce'); ?>

								<h3 class="fs-4 mb-3">1. Billing details</h3>
								<div class="row">
									<div class="col-sm-6 mb-3">
										<label for="billingFirstName" class="form-label input-required">First name</label>
										<input type="text" class="form-control" required name="billing_fname" id="billingFirstName" autocomplete="given-name" value="<?php echo esc_attr($name_parts[0]); ?>">
									</div>
									<div class="col-sm-6 mb-3">
										<label for="billingLastName" class="form-label input-required">Last name</label>
										<input type="text" class="form-control" required name="billing_lname" id="billingLastName" autocomplete="family-name" value="<?php echo esc_attr($name_parts[1] ?? ''); ?>">
									</div>
								</div>
								<div class="mb-3">
									<label for="billingEmail" class="form-label input-required">Email</label>
									<input type="email" class="form-control" required name="billing_email" id="billingEmail" autocomplete="email" value="<?php echo esc_attr($ticket['customer_email']); ?>">
								</div>
								<div class="mb-3">
									<label for="billingCompany" class="form-label">Company</label>
									<input type="text" class="form-control" name="billing_company" id="billingCompany" autocomplete="organization">
								</div>
								<div class="mb-3">
									<label for="billingAddress" class="form-label input-required">Billing address</label>
									<input type="text" class="form-control" required name="billing_address" id="billingAddress" autocomplete="address-line1">
									<input type="text" class="form-control mt-1" name="billing_address_2" id="billingAddress2" autocomplete="address-line2" aria-label="Address line 2">
								</div>
								<div class="row">
									<div class="col-sm-5 mb-3">
										<label for="billingCity" class="form-label input-required">City</label>
										<input type="text" class="form-control" required name="billing_city" id="billingCity" autocomplete="address-level2">
									</div>
									<div class="col-sm-3 mb-3">
										<label for="billingState" class="form-label input-required">State</label>
										<input type="text" class="form-control" required name="billing_state" id="billingState" autocomplete="address-level1">
									</div>
									<div class="col-sm-4 mb-3">
										<label for="billingZip" class="form-label input-required">ZIP</label>
										<input type="text" class="form-control" required name="billing_zip" id="billingZip" autocomplete="postal-code">
									</div>
								</div>
								<div class="mb-4">
									<label for="billingTel" class="form-label">Phone</label>
									<input type="tel" class="form-control" name="billing_tel" id="billingTel" autocomplete="tel" value="<?php echo esc_attr($ticket['customer_phone']); ?>">
								</div>

								<h3 class="fs-4 mb-3">2. Payment</h3>
								<?php if ($payment_disabled) : ?>
									<div class="alert alert-info" role="status">
										Online card payment is temporarily unavailable. Confirm below and we will contact you about payment.
									</div>
								<?php else : ?>
									<div class="row">
										<div class="col-8 mb-3">
											<label for="cardNumber" class="form-label input-required">Card number</label>
											<input required type="text" name="card_number" id="cardNumber" class="form-control" inputmode="numeric" autocomplete="cc-number" pattern="[0-9 ]{12,23}" maxlength="23">
										</div>
										<div class="col-4 mb-3">
											<label for="cardCvv" class="form-label input-required">CVV</label>
											<input required type="text" name="card_cvv" id="cardCvv" class="form-control" inputmode="numeric" autocomplete="cc-csc" pattern="[0-9]{3,4}" maxlength="4">
										</div>
									</div>
									<div class="row mb-4">
										<div class="col-6">
											<label for="expMonth" class="form-label input-required">Expiry month</label>
											<select required name="card_exp_month" id="expMonth" class="form-control" autocomplete="cc-exp-month">
												<option value="">Month</option>
												<?php for ($month = 1; $month <= 12; $month++) : ?>
													<option value="<?php echo esc_attr(sprintf('%02d', $month)); ?>"><?php echo esc_html(sprintf('%02d', $month)); ?></option>
												<?php endfor; ?>
											</select>
										</div>
										<div class="col-6">
											<label for="expYear" class="form-label input-required">Expiry year</label>
											<select required name="card_exp_year" id="expYear" class="form-control" autocomplete="cc-exp-year">
												<option value="">Year</option>
												<?php for ($exp_year = (int) gmdate('Y'); $exp_year <= (int) gmdate('Y') + 12; $exp_year++) : ?>
													<option value="<?php echo esc_attr(substr((string) $exp_year, -2)); ?>"><?php echo esc_html($exp_year); ?></option>
												<?php endfor; ?>
											</select>
										</div>
									</div>
								<?php endif; ?>

								<button type="submit" class="btn btn-primary btn-lg w-100" id="ticketPaySubmit">
									<?php echo $payment_disabled ? 'Confirm order' : esc_html('Pay ' . wholesale_format_ticket_amount($ticket['amount'])); ?>
								</button>
								<p class="small text-muted mt-2 mb-0">Your card details are sent securely to our payment processor and are not stored on this website.</p>
							</form>
							<script>
								document.getElementById('ticketPayForm').addEventListener('submit', function () {
									var button = document.getElementById('ticketPaySubmit');
									button.disabled = true;
									button.textContent = 'Processing...';
								});
							</script>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
