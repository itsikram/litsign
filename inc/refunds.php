<?php
/**
 * Card refunds through Converge, from the order edit screen.
 *
 *  1. Staff opens the Refund dialog; wholesale_refund_check asks Converge whether the
 *     payment has settled yet.
 *  2. Not settled: the whole charge is voided (ccvoid), so it never posts to the customer's
 *     statement. Settled: the full amount or part of it is returned to the card (ccreturn).
 *  3. wholesale_refund_issue re-checks everything server-side, sends the request, records
 *     the result on the order (_refund_log) and optionally emails the customer.
 *
 * Only administrators can refund (filter: wholesale_refund_capability).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

function wholesale_refund_capability()
{
	return apply_filters('wholesale_refund_capability', 'manage_options');
}

/**
 * Every refund attempt on the order, oldest first. Each entry: time, user, amount,
 * method (void|return), result (approved|declined|unknown), reference, message, reason, emailed.
 */
function wholesale_order_refund_log($post_id)
{
	$log = array_values(array_filter(get_post_meta($post_id, '_refund_log'), static function ($entry) {
		return is_array($entry) && !empty($entry['time']);
	}));
	usort($log, static function ($a, $b) {
		return (int) $a['time'] <=> (int) $b['time'];
	});
	return $log;
}

/**
 * @return array{charged:float,refunded:float,remaining:float}
 */
function wholesale_order_refund_totals($post_id)
{
	$charged = (float) get_post_meta($post_id, '_payment_amount', true);
	if ($charged <= 0) {
		$charged = wholesale_order_total($post_id);
	}
	$refunded = 0.0;
	foreach (wholesale_order_refund_log($post_id) as $entry) {
		if ('approved' === ($entry['result'] ?? '')) {
			$refunded += (float) $entry['amount'];
		}
	}
	return array(
		'charged' => round($charged, 2),
		'refunded' => round($refunded, 2),
		'remaining' => max(0.0, round($charged - $refunded, 2)),
	);
}

/**
 * Why this order can't be refunded from here, or '' when it can.
 */
function wholesale_refund_blocked_reason($post_id)
{
	if (!get_post_meta($post_id, '_payment_txn_id', true)) {
		return 'This order has no Converge transaction on file.';
	}
	if (!current_user_can(wholesale_refund_capability())) {
		return 'Only administrators can issue refunds.';
	}
	if (!wholesale_converge_credentials()) {
		return 'Add your Converge credentials in Settings to issue refunds from here.';
	}
	if ('refunded' === wholesale_get_payment_status($post_id) || wholesale_order_refund_totals($post_id)['remaining'] < 0.01) {
		return 'This payment has been fully refunded.';
	}
	return '';
}

function wholesale_refund_money($amount)
{
	return '$' . number_format((float) $amount, 2);
}

function wholesale_refund_activity_text($entry)
{
	$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
	$by = $user ? ' by ' . $user->display_name : '';
	$amount = wholesale_refund_money($entry['amount'] ?? 0);
	switch ($entry['result'] ?? '') {
		case 'approved':
			return ('void' === ($entry['method'] ?? '') ? 'Payment voided: ' : 'Refunded: ') . $amount . $by . (!empty($entry['emailed']) ? ' (customer emailed)' : '');
		case 'unknown':
			return 'Refund of ' . $amount . ' got no answer from Converge' . $by;
		default:
			return 'Refund of ' . $amount . ' declined' . $by . (!empty($entry['message']) ? ': ' . $entry['message'] : '');
	}
}

/**
 * Converge's settlement state for the transaction: 'void' while it's still in the open
 * batch, 'return' once settled. Returns array(method, txn fields) or WP_Error.
 */
function wholesale_refund_method($txn_id)
{
	$txn = wholesale_converge_query_transaction($txn_id);
	if (is_wp_error($txn)) {
		$message = 'unreachable' === $txn->get_error_code()
			? 'Converge could not be reached. Please try again in a minute.'
			: 'Converge could not look up this payment (' . $txn->get_error_message() . ').';
		return new WP_Error('lookup', $message);
	}

	$status = strtoupper($txn['ssl_trans_status'] ?? '');
	if ('STL' === $status) {
		return array('return', $txn);
	}
	if (in_array($status, array('PEN', 'OPN'), true)) {
		return array('void', $txn);
	}
	return new WP_Error('status', sprintf('Converge reports this payment as "%s", so it can\'t be refunded from here. Please check it in Converge.', $status ?: 'unknown'));
}

// ------------------------------------------------------------------
// AJAX
// ------------------------------------------------------------------

/**
 * Shared request checks. Returns the order ID or sends a JSON error.
 */
function wholesale_refund_request_order()
{
	$post_id = absint($_POST['post_id'] ?? 0);
	if (!$post_id || !check_ajax_referer('wholesale_refund_' . $post_id, 'nonce', false)) {
		wp_send_json(array('ok' => false, 'error' => 'Your session expired. Please reload the page and try again.'), 403);
	}
	if ('order' !== get_post_type($post_id) || !current_user_can('edit_post', $post_id)) {
		wp_send_json(array('ok' => false, 'error' => 'You are not allowed to refund this order.'), 403);
	}
	if ($reason = wholesale_refund_blocked_reason($post_id)) {
		wp_send_json(array('ok' => false, 'error' => $reason), 400);
	}
	return $post_id;
}

add_action('wp_ajax_wholesale_refund_check', function () {
	$post_id = wholesale_refund_request_order();
	$method = wholesale_refund_method(get_post_meta($post_id, '_payment_txn_id', true));
	if (is_wp_error($method)) {
		wp_send_json(array('ok' => false, 'error' => $method->get_error_message()), 502);
	}
	$totals = wholesale_order_refund_totals($post_id);
	if ('void' === $method[0] && $totals['refunded'] > 0) {
		wp_send_json(array('ok' => false, 'error' => 'Converge shows this payment as unsettled even though part of it was already refunded. Please finish this refund in Converge.'), 409);
	}
	wp_send_json(array_merge(array('ok' => true, 'method' => $method[0]), $totals));
});

add_action('wp_ajax_wholesale_refund_issue', function () {
	$post_id = wholesale_refund_request_order();
	$txn_id = get_post_meta($post_id, '_payment_txn_id', true);
	$totals = wholesale_order_refund_totals($post_id);

	$raw = trim((string) wp_unslash($_POST['amount'] ?? ''));
	$amount = 'full' === ($_POST['kind'] ?? '') ? $totals['remaining'] : (preg_match('/^\d+(\.\d{1,2})?$/', $raw) ? round((float) $raw, 2) : 0.0);
	if ($amount < 0.01 || $amount > $totals['remaining']) {
		wp_send_json(array('ok' => false, 'error' => 'Enter an amount between $0.01 and ' . wholesale_refund_money($totals['remaining']) . '.'), 400);
	}
	$reason = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));
	$new_status = sanitize_key(wp_unslash($_POST['status'] ?? ''));

	// One refund at a time per order: a double click can never refund twice.
	$lock = 'wholesale_refund_lock_' . $post_id;
	if ((int) get_option($lock) < time() - 120) {
		delete_option($lock);
	}
	if (!add_option($lock, time(), '', 'no')) {
		wp_send_json(array('ok' => false, 'error' => 'A refund for this order is already being processed.'), 409);
	}

	$method = wholesale_refund_method($txn_id);
	if (is_wp_error($method)) {
		delete_option($lock);
		wp_send_json(array('ok' => false, 'error' => $method->get_error_message()), 502);
	}
	list($method, $txn) = $method;
	// What staff confirmed must still be what will happen (the batch may have settled meanwhile).
	if ($method !== sanitize_key(wp_unslash($_POST['method'] ?? ''))) {
		delete_option($lock);
		wp_send_json(array('ok' => false, 'error' => 'This payment just settled in Converge, so it will now be returned instead of voided. Please review the refund again.', 'recheck' => true), 409);
	}
	if ('void' === $method && abs($amount - $totals['remaining']) > 0.005) {
		delete_option($lock);
		wp_send_json(array('ok' => false, 'error' => 'This payment hasn\'t settled yet, so only the full amount can be refunded (voided). For a partial refund, wait until it settles, usually by the next business day.'), 400);
	}
	if (isset($txn['ssl_amount']) && $amount > (float) $txn['ssl_amount'] + 0.005) {
		delete_option($lock);
		wp_send_json(array('ok' => false, 'error' => 'The refund is larger than the amount Converge charged (' . wholesale_refund_money($txn['ssl_amount']) . ').'), 400);
	}

	$fields = array('ssl_transaction_type' => 'void' === $method ? 'ccvoid' : 'ccreturn', 'ssl_txn_id' => $txn_id);
	if ('return' === $method) {
		$fields['ssl_amount'] = number_format($amount, 2, '.', '');
	}
	$doc = wholesale_converge_xml_request($fields, 45);

	$entry = array(
		'time' => time(),
		'user' => get_current_user_id(),
		'amount' => $amount,
		'method' => $method,
		'reason' => $reason,
		'reference' => '',
		'message' => '',
		'emailed' => false,
	);

	if (is_wp_error($doc) && in_array($doc->get_error_code(), array('unreachable', 'bad_response'), true)) {
		// The request may have reached Converge, so the refund may have gone through.
		$entry['result'] = 'unknown';
		$entry['message'] = $doc->get_error_message();
		add_post_meta($post_id, '_refund_log', $entry);
		delete_option($lock);
		error_log(sprintf('Wholesale: refund of %s for order %d (Converge %s) got no answer: %s', wholesale_refund_money($amount), $post_id, $txn_id, $doc->get_error_message()));
		wp_mail(wholesale_contact_admin_recipients(), 'Check refund for order #' . wholesale_order_number($post_id), sprintf("A refund of %s on Converge transaction %s got no answer from Converge, so it may or may not have gone through.\n\nPlease check the transaction in Converge before trying again.\n\n%s", wholesale_refund_money($amount), $txn_id, admin_url('post.php?post=' . $post_id . '&action=edit')));
		wp_send_json(array('ok' => false, 'unknown' => true, 'error' => 'Converge didn\'t respond, so we can\'t tell whether the refund went through. Please check this transaction in Converge before trying again. Your team has been emailed.'), 502);
	}

	$result = is_wp_error($doc) ? array() : wholesale_converge_xml_fields($doc);
	if (is_wp_error($doc) || '0' !== ($result['ssl_result'] ?? '')) {
		$entry['result'] = 'declined';
		$entry['message'] = is_wp_error($doc) ? $doc->get_error_message() : sanitize_text_field($result['ssl_result_message'] ?? 'Declined');
		add_post_meta($post_id, '_refund_log', $entry);
		delete_option($lock);
		wp_send_json(array('ok' => false, 'error' => 'Converge declined the refund: ' . $entry['message'] . '. No money was moved.'), 402);
	}

	$entry['result'] = 'approved';
	$entry['reference'] = sanitize_text_field($result['ssl_txn_id'] ?? '');
	$remaining = round($totals['remaining'] - $amount, 2);
	update_post_meta($post_id, '_payment_status', $remaining < 0.01 ? 'refunded' : 'partially_refunded');
	update_post_meta($post_id, '_payment_verified_by', get_current_user_id());

	// The customer gets the refund email below instead of the generic status email.
	$GLOBALS['wholesale_skip_status_email'] = true;
	$status_changed = false;
	if ($remaining < 0.01 && in_array($new_status, array('refunded', 'cancelled'), true) && get_post_status($post_id) !== $new_status) {
		$status_changed = (bool) wp_update_post(array('ID' => $post_id, 'post_status' => $new_status));
	}

	if (!empty($_POST['notify'])) {
		$entry['emailed'] = wholesale_send_refund_email($post_id, $amount, $method, $remaining < 0.01);
	}
	add_post_meta($post_id, '_refund_log', $entry);
	delete_option($lock);

	wp_send_json(array(
		'ok' => true,
		'amount' => $amount,
		'method' => $method,
		'reference' => $entry['reference'],
		'emailed' => $entry['emailed'],
		'status_changed' => $status_changed,
	));
});

function wholesale_send_refund_email($post_id, $amount, $method, $full)
{
	$last4 = get_post_meta($post_id, '_payment_card_last4', true);
	$card = $last4 ? 'your card ending ' . $last4 : 'your card';
	$timing = 'void' === $method
		? 'Because your payment hadn\'t finished processing, the charge was cancelled instead of refunded. It will drop off your statement, usually within a few business days depending on your bank.'
		: 'It can take 5–10 business days to appear on your statement, depending on your bank.';

	$body = '<p style="margin:0;">We\'ve issued a ' . ($full ? '' : 'partial ') . 'refund to ' . esc_html($card) . '.</p>'
		. '<p style="margin:16px 0 0;padding:14px 16px;background:#eef7fc;border-radius:8px;font-size:15px;color:#0d2e4d;"><strong>Refund amount:</strong> ' . esc_html(wholesale_refund_money($amount)) . '</p>'
		. '<p style="margin:16px 0 0;">' . esc_html($timing) . '</p>';

	return wholesale_send_order_email($post_id, sprintf('Your refund for order #%s', wholesale_order_number($post_id)), $body);
}

// ------------------------------------------------------------------
// Order screen: refund panel (inside the Payment box) and dialog
// ------------------------------------------------------------------

function wholesale_render_refund_panel($post)
{
	if (!get_post_meta($post->ID, '_payment_txn_id', true)) {
		return;
	}
	$log = wholesale_order_refund_log($post->ID);
	$approved = array_filter($log, static function ($entry) {
		return 'approved' === ($entry['result'] ?? '');
	});
	$last = end($log);
	$totals = wholesale_order_refund_totals($post->ID);
	$blocked = wholesale_refund_blocked_reason($post->ID);
	?>
	<div class="wr-panel">
		<?php if ($approved) : ?>
			<h4 class="wr-panel__title">Refunds</h4>
			<ul class="wr-history">
				<?php foreach (array_reverse($approved) as $entry) :
					$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null; ?>
					<li>
						<div class="wr-history__top">
							<strong>&minus;<?php echo esc_html(wholesale_refund_money($entry['amount'])); ?></strong>
							<span class="order-badge order-badge--muted"><?php echo 'void' === $entry['method'] ? 'Voided' : 'Returned'; ?></span>
						</div>
						<small><?php echo esc_html(wp_date('M j, Y g:ia', (int) $entry['time']) . ($user ? ' · ' . $user->display_name : '')); ?></small>
						<?php if (!empty($entry['reference'])) : ?><small>Converge ref <code><?php echo esc_html($entry['reference']); ?></code></small><?php endif; ?>
						<?php if (!empty($entry['reason'])) : ?><small class="wr-history__reason"><?php echo esc_html($entry['reason']); ?></small><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="wr-net"><span>Net payment</span><strong><?php echo esc_html(wholesale_refund_money($totals['remaining'])); ?></strong></p>
		<?php endif; ?>

		<?php if ($last && 'unknown' === $last['result']) : ?>
			<p class="wholesale-fulfillment-warning">The refund of <?php echo esc_html(wholesale_refund_money($last['amount'])); ?> on <?php echo esc_html(wp_date('M j, g:ia', (int) $last['time'])); ?> got no answer from Converge. Check the transaction in Converge before refunding again.</p>
		<?php endif; ?>

		<?php if (!$blocked) : ?>
			<button type="button" class="button wr-open" aria-haspopup="dialog"><span class="dashicons dashicons-undo" aria-hidden="true"></span> Refund payment</button>
		<?php elseif (!$approved || 'This payment has been fully refunded.' !== $blocked) : ?>
			<p class="description"><?php echo esc_html($blocked); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

add_action('admin_footer-post.php', function () {
	global $post;
	if (!$post || 'order' !== $post->post_type || wholesale_refund_blocked_reason($post->ID)) {
		return;
	}
	$totals = wholesale_order_refund_totals($post->ID);
	$billing = wholesale_decode_order_meta_array(get_post_meta($post->ID, 'billing_address', true));
	$last4 = get_post_meta($post->ID, '_payment_card_last4', true);
	$status_info = wholesale_order_status_info();
	$current = $status_info[$post->post_status]['label'] ?? ucwords(str_replace(array('-', '_'), ' ', $post->post_status));
	$config = array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'postId' => $post->ID,
		'nonce' => wp_create_nonce('wholesale_refund_' . $post->ID),
		'remaining' => $totals['remaining'],
		'card' => $last4 ? '•••• ' . $last4 : 'the card',
		'email' => is_email($billing['billing_email'] ?? '') ? $billing['billing_email'] : '',
		'currentStatus' => $current,
	);
	?>
	<dialog class="wr-dialog" id="wr-dialog" aria-labelledby="wr-title">
		<div class="wr-head">
			<div>
				<h2 id="wr-title">Refund payment</h2>
				<p>Order #<?php echo esc_html(wholesale_order_number($post->ID)); ?> &middot; Card <?php echo esc_html($config['card']); ?>
					<?php if (wholesale_setting_enabled('payment_test_mode')) : ?><span class="order-badge order-badge--warn">Test mode</span><?php endif; ?>
				</p>
			</div>
			<button type="button" class="wr-x" data-close aria-label="Close"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
		</div>

		<div class="wr-body">
			<div class="wr-alert" role="alert" hidden></div>

			<div data-step="form">
				<div class="wr-stats">
					<div><span>Charged</span><strong><?php echo esc_html(wholesale_refund_money($totals['charged'])); ?></strong></div>
					<div><span>Refunded</span><strong><?php echo esc_html(wholesale_refund_money($totals['refunded'])); ?></strong></div>
					<div class="is-key"><span>Available</span><strong><?php echo esc_html(wholesale_refund_money($totals['remaining'])); ?></strong></div>
				</div>

				<div class="wr-method" data-tone="loading"><span class="spinner is-active"></span><span class="wr-method__text">Checking the payment with Converge&hellip;</span></div>

				<fieldset class="wr-amount" disabled>
					<legend>Refund amount</legend>
					<label class="wr-option">
						<input type="radio" name="wr_kind" value="full" checked>
						<span class="wr-option__label">Full refund</span>
						<span class="wr-option__value"><?php echo esc_html(wholesale_refund_money($totals['remaining'])); ?></span>
					</label>
					<label class="wr-option wr-option--partial">
						<input type="radio" name="wr_kind" value="partial">
						<span class="wr-option__label">Partial refund <small class="wr-partial-note"></small></span>
						<span class="wr-money"><span aria-hidden="true">$</span><input type="text" id="wr-amount" inputmode="decimal" autocomplete="off" placeholder="0.00" aria-label="Partial refund amount"></span>
					</label>
					<p class="wr-field-error" hidden></p>
				</fieldset>

				<div class="wr-field wr-field--status">
					<label for="wr-status">Order status after refund</label>
					<select id="wr-status">
						<option value="refunded">Refunded</option>
						<option value="cancelled">Cancelled</option>
						<option value="">Keep as <?php echo esc_html($current); ?></option>
					</select>
				</div>

				<div class="wr-field">
					<label for="wr-reason">Reason <span>Internal note, not shown to the customer</span></label>
					<textarea id="wr-reason" rows="2" maxlength="500" placeholder="e.g. Customer cancelled before production"></textarea>
				</div>

				<label class="wr-check">
					<input type="checkbox" id="wr-notify" <?php checked((bool) $config['email']); ?> <?php disabled(!$config['email']); ?>>
					<span>Email the customer a refund confirmation<?php if ($config['email']) : ?> <small><?php echo esc_html($config['email']); ?></small><?php endif; ?></span>
				</label>
			</div>

			<div data-step="confirm" hidden>
				<div class="wr-confirm">
					<p class="wr-confirm__eyebrow">You're about to refund</p>
					<p class="wr-confirm__amount" data-field="amount"></p>
					<p class="wr-confirm__lead" data-field="lead"></p>
				</div>
				<dl class="wr-summary">
					<div><dt>Method</dt><dd data-field="method"></dd></div>
					<div><dt>Order status</dt><dd data-field="status"></dd></div>
					<div><dt>Customer email</dt><dd data-field="notify"></dd></div>
					<div data-row="reason"><dt>Reason</dt><dd data-field="reason"></dd></div>
				</dl>
				<p class="wr-irreversible"><span class="dashicons dashicons-warning" aria-hidden="true"></span> Refunds can't be undone once Converge approves them.</p>
			</div>

			<div data-step="working" hidden>
				<div class="wr-state">
					<span class="wr-loader" aria-hidden="true"></span>
					<p class="wr-state__title">Processing refund with Converge&hellip;</p>
					<p class="wr-state__text">This usually takes a few seconds. Please keep this window open.</p>
				</div>
			</div>

			<div data-step="done" hidden>
				<div class="wr-state">
					<span class="wr-state__icon"><span class="dashicons dashicons-yes" aria-hidden="true"></span></span>
					<p class="wr-state__title" data-field="done-title"></p>
					<p class="wr-state__text" data-field="done-text"></p>
				</div>
			</div>
		</div>

		<div class="wr-foot">
			<button type="button" class="button wr-back">Cancel</button>
			<button type="button" class="button button-primary wr-next" disabled>Review refund</button>
		</div>
	</dialog>

	<script>
		(function () {
			var config = <?php echo wp_json_encode($config); ?>;
			var dialog = document.getElementById('wr-dialog');
			var opener = document.querySelector('.wr-open');
			if (!dialog || !opener || typeof dialog.showModal !== 'function') {
				return;
			}
			var $ = function (selector) { return dialog.querySelector(selector); };
			var steps = dialog.querySelectorAll('[data-step]');
			var alertBox = $('.wr-alert');
			var methodBox = $('.wr-method');
			var fieldset = $('.wr-amount');
			var amountInput = $('#wr-amount');
			var fieldError = $('.wr-field-error');
			var backButton = $('.wr-back');
			var nextButton = $('.wr-next');
			var state = { step: 'form', method: '', busy: false, finished: false };

			var money = function (value) {
				return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
			};
			var kind = function () { return $('[name="wr_kind"]:checked').value; };
			var amount = function () {
				if (kind() === 'full') {
					return config.remaining;
				}
				var raw = amountInput.value.trim().replace(/^\$/, '').replace(/,/g, '');
				return /^\d+(\.\d{1,2})?$/.test(raw) ? Math.round(parseFloat(raw) * 100) / 100 : NaN;
			};
			var isFull = function () { return Math.abs(amount() - config.remaining) < 0.005; };

			var showAlert = function (message, tone) {
				alertBox.textContent = message || '';
				alertBox.dataset.tone = tone || 'error';
				alertBox.hidden = !message;
			};

			var validate = function (showErrors) {
				var value = amount();
				var error = '';
				if (kind() === 'partial') {
					if (amountInput.value.trim() === '') {
						error = showErrors ? 'Enter the amount to refund.' : '';
					} else if (isNaN(value) || value < 0.01) {
						error = 'Enter a valid amount, like 25.00.';
					} else if (value > config.remaining + 0.001) {
						error = 'You can refund up to ' + money(config.remaining) + '.';
					}
				}
				fieldError.textContent = error;
				fieldError.hidden = !error;
				amountInput.setAttribute('aria-invalid', error ? 'true' : 'false');
				$('.wr-field--status').hidden = !(isFull() || kind() === 'full');
				var ok = state.method && !isNaN(value) && value >= 0.01 && value <= config.remaining + 0.001;
				if (state.step === 'form') {
					nextButton.disabled = !ok;
					nextButton.textContent = ok ? 'Review refund of ' + money(value) : 'Review refund';
				}
				return ok;
			};

			var go = function (step) {
				state.step = step;
				steps.forEach(function (el) { el.hidden = el.dataset.step !== step; });
				dialog.classList.toggle('is-busy', step === 'working');
				backButton.hidden = step === 'working' || step === 'done';
				nextButton.hidden = step === 'working';
				nextButton.classList.toggle('wr-danger', step === 'confirm');
				if (step === 'form') {
					backButton.textContent = 'Cancel';
					validate(false);
				} else if (step === 'confirm') {
					backButton.textContent = 'Back';
					nextButton.disabled = false;
					nextButton.textContent = 'Refund ' + money(amount());
					nextButton.focus();
				} else if (step === 'done') {
					nextButton.disabled = false;
					nextButton.textContent = 'Done';
					nextButton.focus();
				}
			};

			var post = function (action, data) {
				var body = new FormData();
				body.append('action', action);
				body.append('post_id', config.postId);
				body.append('nonce', config.nonce);
				Object.keys(data || {}).forEach(function (key) { body.append(key, data[key]); });
				return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
					.then(function (response) {
						return response.json().catch(function () {
							return { ok: false, error: 'The server returned an unexpected response (HTTP ' + response.status + ').' };
						});
					});
			};

			var setMethod = function (method) {
				state.method = method;
				var partial = $('.wr-option--partial input[type="radio"]');
				if (method === 'void') {
					methodBox.dataset.tone = 'info';
					methodBox.querySelector('.wr-method__text').innerHTML = '<strong>Not settled yet: the charge will be voided.</strong> It is cancelled before it posts, so it drops off the customer\'s statement. Partial refunds are possible once it settles, usually by the next business day.';
					partial.disabled = true;
					$('[name="wr_kind"][value="full"]').checked = true;
					$('.wr-partial-note').textContent = '(after the payment settles)';
				} else {
					methodBox.dataset.tone = 'success';
					methodBox.querySelector('.wr-method__text').innerHTML = '<strong>Settled: the money will be returned to the card.</strong> Customers usually see it within 5–10 business days.';
					partial.disabled = false;
					$('.wr-partial-note').textContent = '';
				}
				amountInput.disabled = method === 'void';
				fieldset.disabled = false;
				validate(false);
			};

			var check = function () {
				state.method = '';
				fieldset.disabled = true;
				methodBox.dataset.tone = 'loading';
				methodBox.querySelector('.wr-method__text').textContent = 'Checking the payment with Converge…';
				validate(false);
				post('wholesale_refund_check').then(function (result) {
					if (!result.ok) {
						methodBox.dataset.tone = 'error';
						methodBox.querySelector('.wr-method__text').textContent = result.error || 'Converge could not be checked.';
						return;
					}
					config.remaining = result.remaining;
					setMethod(result.method);
				}).catch(function () {
					methodBox.dataset.tone = 'error';
					methodBox.querySelector('.wr-method__text').textContent = 'Could not reach the server. Check your connection and reopen this dialog.';
				});
			};

			var fillConfirm = function () {
				var value = amount();
				var statusSelect = $('#wr-status');
				var notify = $('#wr-notify');
				var reason = $('#wr-reason').value.trim();
				$('[data-field="amount"]').textContent = money(value);
				$('[data-field="lead"]').textContent = (state.method === 'void' ? 'The charge on card ' : 'Returned to card ') + config.card + (state.method === 'void' ? ' will be voided.' : '.') + (isFull() ? '' : ' ' + money(config.remaining - value) + ' stays charged.');
				$('[data-field="method"]').textContent = state.method === 'void' ? 'Void (payment not settled yet)' : 'Return to card (5–10 business days)';
				$('[data-field="status"]').textContent = isFull() && statusSelect.value ? 'Change to ' + statusSelect.options[statusSelect.selectedIndex].text : 'No change (' + config.currentStatus + ')';
				$('[data-field="notify"]').textContent = notify.checked ? 'Send confirmation to ' + config.email : 'Don\'t email';
				$('[data-field="reason"]').textContent = reason;
				$('[data-row="reason"]').hidden = !reason;
			};

			var submit = function () {
				var value = amount();
				go('working');
				state.busy = true;
				showAlert('');
				post('wholesale_refund_issue', {
					kind: isFull() ? 'full' : 'partial',
					amount: value.toFixed(2),
					method: state.method,
					reason: $('#wr-reason').value.trim(),
					status: isFull() ? $('#wr-status').value : '',
					notify: $('#wr-notify').checked ? '1' : ''
				}).then(function (result) {
					state.busy = false;
					if (result.ok) {
						state.finished = true;
						$('[data-field="done-title"]').textContent = (result.method === 'void' ? 'Payment voided' : 'Refund approved') + ': ' + money(result.amount);
						$('[data-field="done-text"]').textContent = [
							result.reference ? 'Converge reference ' + result.reference + '.' : '',
							result.emailed ? 'Confirmation emailed to ' + config.email + '.' : '',
							result.status_changed ? 'Order status updated.' : ''
						].filter(Boolean).join(' ');
						go('done');
						return;
					}
					if (result.unknown) {
						state.finished = true;
						showAlert(result.error, 'warning');
						go('form');
						fieldset.disabled = true;
						nextButton.hidden = true;
						backButton.textContent = 'Close';
						return;
					}
					showAlert(result.error || 'The refund could not be processed.');
					go('form');
					if (result.recheck) {
						check();
					}
				}).catch(function () {
					state.busy = false;
					state.finished = true;
					showAlert('The connection dropped before the server answered, so we can\'t tell whether the refund went through. Reload this order and check its refund history (or Converge) before trying again.', 'warning');
					go('form');
					fieldset.disabled = true;
					nextButton.hidden = true;
					backButton.textContent = 'Close';
				});
			};

			var close = function () {
				if (state.busy) {
					return;
				}
				dialog.close();
				if (state.finished) {
					window.location.reload();
				}
			};

			opener.addEventListener('click', function () {
				showAlert('');
				amountInput.value = '';
				$('[name="wr_kind"][value="full"]').checked = true;
				go('form');
				dialog.showModal();
				check();
			});
			dialog.addEventListener('cancel', function (event) {
				event.preventDefault();
				close();
			});
			dialog.addEventListener('click', function (event) {
				if (event.target === dialog && state.step === 'form') {
					close();
				}
			});
			dialog.querySelectorAll('[data-close]').forEach(function (button) { button.addEventListener('click', close); });
			backButton.addEventListener('click', function () {
				if (state.step === 'confirm') {
					go('form');
				} else {
					close();
				}
			});
			nextButton.addEventListener('click', function () {
				if (state.step === 'form' && validate(true)) {
					showAlert('');
					fillConfirm();
					go('confirm');
				} else if (state.step === 'confirm') {
					submit();
				} else if (state.step === 'done') {
					close();
				}
			});
			dialog.querySelectorAll('[name="wr_kind"]').forEach(function (radio) {
				radio.addEventListener('change', function () {
					if (kind() === 'partial') {
						amountInput.focus();
					}
					validate(false);
				});
			});
			// Clicking or typing in the amount box picks "Partial refund".
			var choosePartial = function () {
				var partial = $('[name="wr_kind"][value="partial"]');
				if (!partial.disabled && !partial.checked) {
					partial.checked = true;
				}
				validate(false);
			};
			amountInput.addEventListener('focus', choosePartial);
			amountInput.addEventListener('input', choosePartial);
			amountInput.addEventListener('keydown', function (event) {
				if (event.key === 'Enter') {
					event.preventDefault();
					nextButton.click();
				}
			});
		})();
	</script>
	<?php
});

add_action('admin_head-post.php', function () {
	if ('order' !== get_current_screen()->post_type) {
		return;
	}
	?>
	<style>
		.wr-panel { margin: 12px -12px 12px; padding: 12px 12px 0; border-top: 1px solid #f0f0f1; }
		.wr-panel__title { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #646970; }
		.wr-history { margin: 0 0 8px; }
		.wr-history li { display: grid; gap: 1px; margin: 0; padding: 8px 0; border-bottom: 1px solid #f0f0f1; }
		.wr-history li:first-child { padding-top: 0; }
		.wr-history__top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
		.wr-history__top strong { font-size: 14px; color: #b32d2e; }
		.wr-history small { color: #646970; }
		.wr-history__reason { font-style: italic; }
		.wr-net { display: flex; justify-content: space-between; margin: 0 0 12px; font-size: 13px; }
		.wr-open { display: flex !important; align-items: center; justify-content: center; gap: 4px; width: 100%; margin-bottom: 12px !important; color: #b32d2e !important; border-color: #e0a3a3 !important; }
		.wr-open:hover, .wr-open:focus { background: #fcf0f1 !important; border-color: #b32d2e !important; }
		.wr-open .dashicons { font-size: 16px; width: 16px; height: 16px; }

		.wr-dialog { width: min(480px, calc(100vw - 32px)); max-height: calc(100vh - 48px); padding: 0; border: 0; border-radius: 12px; box-shadow: 0 24px 64px rgba(0, 0, 0, .28); color: #1d2327; overflow: hidden; }
		.wr-dialog[open] { display: flex; flex-direction: column; animation: wr-in .16s ease-out; }
		.wr-dialog::backdrop { background: rgba(16, 24, 32, .55); }
		.wr-dialog [hidden] { display: none !important; }
		@keyframes wr-in { from { opacity: 0; transform: translateY(8px) scale(.98); } }
		.wr-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 18px 20px 14px; border-bottom: 1px solid #f0f0f1; }
		.wr-head h2 { margin: 0; font-size: 18px; line-height: 1.3; }
		.wr-head p { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 2px 0 0; color: #646970; }
		.wr-x { display: grid; place-items: center; width: 32px; height: 32px; margin: -4px -6px 0 0; padding: 0; border: 0; border-radius: 6px; background: none; color: #646970; cursor: pointer; }
		.wr-x:hover { background: #f0f0f1; color: #1d2327; }
		.wr-dialog.is-busy .wr-x { visibility: hidden; }
		.wr-dialog.is-busy .wr-foot { display: none; }
		.wr-body { padding: 18px 20px; overflow-y: auto; }
		.wr-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 20px; background: #f6f7f7; border-top: 1px solid #dcdcde; }
		.wr-foot .button { min-height: 36px; padding: 0 16px; }
		.wr-foot .wr-danger { background: #d63638; border-color: #d63638; }
		.wr-foot .wr-danger:hover, .wr-foot .wr-danger:focus { background: #b32d2e; border-color: #b32d2e; }

		.wr-alert { display: flex; gap: 8px; margin: 0 0 14px; padding: 10px 12px; border-left: 4px solid #d63638; border-radius: 4px; background: #fcf0f1; }
		.wr-alert[data-tone="warning"] { border-color: #dba617; background: #fcf9e8; }
		.wr-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
		.wr-stats div { padding: 10px 12px; border: 1px solid #e3e9ef; border-radius: 8px; background: #f8fafc; }
		.wr-stats span { display: block; font-size: 12px; color: #646970; }
		.wr-stats strong { display: block; font-size: 16px; font-variant-numeric: tabular-nums; }
		.wr-stats .is-key { border-color: #c5e3f3; background: #eef7fc; }

		.wr-method { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 16px; padding: 10px 12px; border-radius: 8px; background: #f6f7f7; line-height: 1.5; }
		.wr-method .spinner { float: none; margin: 1px 0 0; }
		.wr-method:not([data-tone="loading"]) .spinner { display: none; }
		.wr-method[data-tone="info"] { background: #eef7fc; color: #0d3b56; }
		.wr-method[data-tone="success"] { background: #edfaef; color: #0e4a2c; }
		.wr-method[data-tone="error"] { background: #fcf0f1; color: #8a1f1f; }

		.wr-amount { margin: 0 0 14px; padding: 0; border: 0; min-width: 0; }
		.wr-amount legend, .wr-field label { margin-bottom: 6px; padding: 0; font-weight: 600; }
		.wr-amount[disabled] { opacity: .55; }
		.wr-option { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; padding: 10px 12px; border: 1px solid #dcdcde; border-radius: 8px; cursor: pointer; transition: border-color .12s, background .12s; }
		.wr-option:hover { border-color: #8c8f94; }
		.wr-option:has(input[type="radio"]:checked) { border-color: #2271b1; background: #f0f6fc; box-shadow: 0 0 0 1px #2271b1; }
		.wr-option:has(input[type="radio"]:disabled) { cursor: not-allowed; background: #f6f7f7; }
		.wr-option input[type="radio"] { margin: 0; }
		.wr-option__label { flex: 1; font-weight: 500; }
		.wr-option__label small { display: block; color: #787c82; font-weight: 400; }
		.wr-option__value { font-weight: 600; font-variant-numeric: tabular-nums; }
		.wr-money { display: flex; align-items: center; width: 120px; border: 1px solid #8c8f94; border-radius: 4px; background: #fff; }
		.wr-money > span { padding: 0 2px 0 8px; color: #646970; }
		.wr-money input { width: 100%; min-height: 30px; border: 0 !important; box-shadow: none !important; text-align: right; font-variant-numeric: tabular-nums; }
		.wr-money:focus-within { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
		.wr-money:has(input[aria-invalid="true"]) { border-color: #d63638; box-shadow: 0 0 0 1px #d63638; }
		.wr-option:has(input[type="radio"]:disabled) .wr-money { opacity: .5; }
		.wr-field-error { margin: -2px 0 0; color: #b32d2e; }
		.wr-field { margin-bottom: 14px; }
		.wr-field label { display: block; }
		.wr-field label span { margin-left: 4px; color: #787c82; font-weight: 400; font-size: 12px; }
		.wr-field select, .wr-field textarea { width: 100%; max-width: none; }
		.wr-check { display: flex; align-items: flex-start; gap: 8px; }
		.wr-check input { margin-top: 2px !important; }
		.wr-check small { display: block; color: #646970; }

		.wr-confirm { margin-bottom: 16px; padding: 18px; border-radius: 10px; background: #f6f7f7; text-align: center; }
		.wr-confirm__eyebrow { margin: 0; color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
		.wr-confirm__amount { margin: 2px 0; font-size: 32px; font-weight: 600; line-height: 1.2; font-variant-numeric: tabular-nums; }
		.wr-confirm__lead { margin: 0; color: #50575e; }
		.wr-summary { margin: 0 0 14px; }
		.wr-summary div { display: grid; grid-template-columns: 120px 1fr; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f0f0f1; }
		.wr-summary dt { color: #646970; }
		.wr-summary dd { margin: 0; overflow-wrap: anywhere; }
		.wr-irreversible { display: flex; align-items: center; gap: 6px; margin: 0; color: #8a4b00; }

		.wr-state { padding: 20px 0 8px; text-align: center; }
		.wr-state__title { margin: 12px 0 4px; font-size: 16px; font-weight: 600; }
		.wr-state__text { margin: 0; color: #646970; }
		.wr-loader { display: inline-block; width: 40px; height: 40px; border: 3px solid #dcdcde; border-top-color: #2271b1; border-radius: 50%; animation: wr-spin .8s linear infinite; }
		@keyframes wr-spin { to { transform: rotate(360deg); } }
		.wr-state__icon { display: inline-grid; place-items: center; width: 52px; height: 52px; border-radius: 50%; background: #edfaef; color: #008a20; }
		.wr-state__icon .dashicons { font-size: 34px; width: 34px; height: 34px; }
		@media (prefers-reduced-motion: reduce) { .wr-dialog[open], .wr-loader { animation: none; } }
		@media (max-width: 480px) {
			.wr-stats strong { font-size: 14px; }
			.wr-summary div { grid-template-columns: 1fr; gap: 2px; }
		}
	</style>
	<?php
});
