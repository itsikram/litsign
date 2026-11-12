<?php
/**
 * Payment links for orders that were placed without a card payment.
 *
 * Staff open an unpaid order (Payment status "Collect payment") and click "Email payment link".
 * The customer gets /pay/?o=<token>, sees the order and pays its total by card through the same
 * Converge flow as checkout (inc/payments.php, kind 'order'). The payment is recorded on that
 * same order (its _payment_* meta, so the Payment box, order list, refunds and customer pages
 * all show it); no new order is created. The Converge invoice number carries the order number.
 *
 * Once paid, the customer gets a payment receipt (order email, plus the payer's email if they
 * typed a different one) and every store notification recipient gets their own staff copy.
 *
 * The link takes cards even while Settings → Storefront Sign → "Disable card payment" is on:
 * that setting only controls checkout. The amount always comes from the order, never the form.
 *
 * Page: template-parts/pay-order.php (loaded by page-pay.php when ?o= is present).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Whether the order can still be paid through a link: unpaid, not cancelled, and has a total.
 */
function wholesale_order_pay_is_open($order_id)
{
	if ('order' !== get_post_type($order_id)) {
		return false;
	}
	if (in_array(get_post_status($order_id), array('cancelled', 'refunded', 'failed', 'trash'), true)) {
		return false;
	}
	return in_array(wholesale_get_payment_status($order_id), array('', 'manual'), true) && wholesale_order_total($order_id) > 0;
}

/**
 * The order's link token, created on first use. Kept after payment so the link then shows "Paid".
 */
function wholesale_order_pay_token($order_id, $create = false)
{
	$token = (string) get_post_meta($order_id, '_pay_link_token', true);
	if ('' === $token && $create) {
		$token = wp_generate_password(32, false, false);
		update_post_meta($order_id, '_pay_link_token', $token);
	}
	return $token;
}

function wholesale_order_pay_url($order_id, $create = true)
{
	$token = wholesale_order_pay_token($order_id, $create);
	return $token ? add_query_arg('o', $token, home_url('/pay/')) : '';
}

function wholesale_order_pay_clean_token($raw)
{
	return preg_replace('/[^A-Za-z0-9]/', '', (string) wp_unslash($raw));
}

function wholesale_find_order_by_pay_token($token)
{
	if (strlen($token) < 20) {
		return 0;
	}
	$ids = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'any',
		'meta_key' => '_pay_link_token',
		'meta_value' => $token,
		'posts_per_page' => 1,
		'fields' => 'ids',
		'no_found_rows' => true,
	));
	$order_id = $ids ? (int) $ids[0] : 0;
	return $order_id && hash_equals((string) get_post_meta($order_id, '_pay_link_token', true), $token) ? $order_id : 0;
}

// ------------------------------------------------------------------
// Activity log (shown in the order's Activity box)
// ------------------------------------------------------------------

function wholesale_order_pay_log($order_id, $event, $note = '')
{
	add_post_meta($order_id, '_pay_link_log', array('time' => time(), 'event' => $event, 'note' => $note, 'user' => get_current_user_id()));
}

/**
 * Newest log entry of the given event(s), or null.
 */
function wholesale_order_pay_last_event($order_id, $events)
{
	$latest = null;
	foreach (get_post_meta($order_id, '_pay_link_log') as $entry) {
		if (is_array($entry) && in_array($entry['event'] ?? '', (array) $events, true) && (!$latest || (int) $entry['time'] >= (int) $latest['time'])) {
			$latest = $entry;
		}
	}
	return $latest;
}

add_filter('wholesale_order_activity_events', function ($events, $post) {
	// Events whose note is an email address read "… to <email>".
	$labels = array(
		'sent' => 'Payment link emailed to',
		'send_failed' => 'Payment link email failed to',
		'copied' => 'Payment link copied',
		'viewed' => 'Customer opened the payment link',
		'failed' => 'Payment link: card not accepted',
		'paid' => 'Paid by card via payment link',
		'duplicate' => 'SECOND payment via link - refund it',
		'receipt_sent' => 'Payment receipt emailed to',
		'receipt_failed' => 'Payment receipt email failed to',
		'team_notified' => 'Staff emailed about the payment',
		'team_failed' => 'Staff payment email failed',
	);
	$to_events = array('sent', 'send_failed', 'receipt_sent', 'receipt_failed');
	foreach (get_post_meta($post->ID, '_pay_link_log') as $entry) {
		if (!is_array($entry) || empty($entry['time'])) {
			continue;
		}
		$event = (string) ($entry['event'] ?? '');
		$user = !empty($entry['user']) && in_array($event, array('sent', 'copied', 'receipt_sent'), true) ? get_userdata((int) $entry['user']) : null;
		$events[] = array(
			'time' => (int) $entry['time'],
			'text' => ($labels[$event] ?? $event) . (!empty($entry['note']) ? (in_array($event, $to_events, true) ? ' ' : ': ') . $entry['note'] : '') . ($user ? ' by ' . $user->display_name : ''),
		);
	}
	return $events;
}, 10, 2);

/**
 * Logs the customer's first visit after each send (staff previews don't count).
 */
function wholesale_order_pay_mark_viewed($order_id)
{
	if (current_user_can('edit_post', $order_id) || !wholesale_order_pay_is_open($order_id)) {
		return;
	}
	$viewed = wholesale_order_pay_last_event($order_id, 'viewed');
	$issued = wholesale_order_pay_last_event($order_id, array('sent', 'copied'));
	if (!$viewed || ($issued && (int) $issued['time'] > (int) $viewed['time'])) {
		wholesale_order_pay_log($order_id, 'viewed');
	}
}

// ------------------------------------------------------------------
// Payment facts (read back from the order, so a resend shows the same)
// ------------------------------------------------------------------

/**
 * Billing details the payer typed on the pay page (empty array before payment).
 */
function wholesale_order_pay_payer($order_id)
{
	return wholesale_decode_order_meta_array(get_post_meta($order_id, '_pay_link_billing', true));
}

/**
 * label => value of the link payment saved on the order.
 *
 * @param bool $for_staff Adds approval code, Converge invoice and payment status.
 * @return array<string,string>
 */
function wholesale_order_pay_facts($order_id, $for_staff = false)
{
	$paid_at = (string) get_post_meta($order_id, '_pay_link_paid_at', true);
	$amount = (float) get_post_meta($order_id, '_payment_amount', true);
	$last4 = (string) get_post_meta($order_id, '_payment_card_last4', true);
	$facts = array(
		'Order number' => '#' . wholesale_order_number($order_id),
		'Amount paid' => '$' . number_format($amount > 0 ? $amount : wholesale_order_total($order_id), 2),
		'Paid on' => $paid_at ? date_i18n('F j, Y g:i a', strtotime($paid_at)) : '',
		'Payment method' => 'Card' . ($last4 ? ' ending ' . $last4 : ''),
		'Transaction ID' => (string) get_post_meta($order_id, '_payment_txn_id', true),
	);
	if ($for_staff) {
		$statuses = wholesale_payment_statuses();
		$status = wholesale_get_payment_status($order_id);
		$facts['Approval code'] = (string) get_post_meta($order_id, '_payment_approval_code', true);
		$facts['Converge invoice'] = (string) get_post_meta($order_id, '_payment_ref', true);
		$facts['Payment status'] = isset($statuses[$status]) ? $statuses[$status][0] : $status;
	}
	return array_filter($facts, 'strlen');
}

// ------------------------------------------------------------------
// Admin: "Payment link" box on the order screen
// ------------------------------------------------------------------

add_action('add_meta_boxes_order', function () {
	add_meta_box('wholesale-order-pay-link', 'Payment link', 'wholesale_render_order_pay_link_box', 'order', 'side', 'high');
}, 20);

function wholesale_render_order_pay_link_box($post)
{
	$nonce = wp_create_nonce('wholesale_order_pay_link_' . $post->ID);

	if (get_post_meta($post->ID, '_pay_link_paid_at', true)) {
		$payer = wholesale_order_pay_payer($post->ID);
		$payer_name = trim(($payer['billing_fname'] ?? '') . ' ' . ($payer['billing_lname'] ?? ''));
		$receipts = array_filter(get_post_meta($post->ID, '_pay_link_log'), static function ($entry) {
			return is_array($entry) && in_array($entry['event'] ?? '', array('receipt_sent', 'receipt_failed'), true);
		});
		$team = wholesale_order_pay_last_event($post->ID, array('team_notified', 'team_failed'));
		$billing = wholesale_decode_order_meta_array(get_post_meta($post->ID, 'billing_address', true));
		?>
		<div class="wo-paylink" data-order="<?php echo (int) $post->ID; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
			<p class="wo-paylink__done">Paid through the payment link. The payment is saved on this order.</p>
			<dl class="wo-paylink__facts">
				<?php foreach (wholesale_order_pay_facts($post->ID, true) as $label => $value) : ?>
					<dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd>
				<?php endforeach; ?>
				<?php if ($payer_name || !empty($payer['billing_email'])) : ?>
					<dt>Paid by</dt><dd><?php echo esc_html(trim($payer_name . (!empty($payer['billing_email']) ? ' (' . $payer['billing_email'] . ')' : ''))); ?></dd>
				<?php endif; ?>
				<?php if (!empty($payer['billing_address'])) : ?>
					<dt>Card billing</dt><dd><?php echo esc_html(implode(', ', array_filter(array($payer['billing_address'] ?? '', $payer['billing_address_2'] ?? '', $payer['billing_city'] ?? '', trim(($payer['billing_state'] ?? '') . ' ' . ($payer['billing_zip'] ?? '')))))); ?></dd>
				<?php endif; ?>
			</dl>
			<?php foreach ($receipts as $receipt) : ?>
				<p class="description"><?php echo esc_html(('receipt_sent' === $receipt['event'] ? 'Receipt emailed to ' : 'Receipt email FAILED to ') . $receipt['note'] . ' · ' . wp_date('M j, g:ia', (int) $receipt['time'])); ?></p>
			<?php endforeach; ?>
			<?php if ($team) : ?>
				<p class="description"><?php echo esc_html(('team_notified' === $team['event'] ? 'Staff emailed' : 'Staff email FAILED') . ' · ' . wp_date('M j, g:ia', (int) $team['time'])); ?></p>
			<?php endif; ?>
			<p><label for="wo-paylink-email"><strong>Resend receipt to</strong></label><br>
				<input type="email" id="wo-paylink-email" class="widefat" data-paylink-email value="<?php echo esc_attr($billing['billing_email'] ?? ($payer['billing_email'] ?? '')); ?>"></p>
			<p class="wo-paylink__actions"><button type="button" class="button" data-paylink="receipt">Resend receipt</button></p>
			<p class="wo-paylink__msg" role="status" aria-live="polite"></p>
		</div>
		<?php
		return;
	}
	if (!wholesale_order_pay_is_open($post->ID)) {
		$status = wholesale_get_payment_status($post->ID);
		$paid = in_array($status, array('paid', 'paid_offline', 'needs_review', 'partially_refunded'), true);
		echo '<p class="description">' . esc_html($paid ? 'This order is already paid, so no payment link is needed.' : 'Payment links are for unpaid orders (Payment status "Collect payment").') . '</p>';
		return;
	}

	$billing = wholesale_decode_order_meta_array(get_post_meta($post->ID, 'billing_address', true));
	$url = wholesale_order_pay_token($post->ID) ? wholesale_order_pay_url($post->ID, false) : '';
	$sent = wholesale_order_pay_last_event($post->ID, 'sent');
	$viewed = wholesale_order_pay_last_event($post->ID, 'viewed');
	$failed = wholesale_order_pay_last_event($post->ID, 'failed');
	?>
	<div class="wo-paylink" data-order="<?php echo (int) $post->ID; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
		<p>Send the customer a secure link to pay <strong>$<?php echo esc_html(number_format(wholesale_order_total($post->ID), 2)); ?></strong> by card. The payment is saved on this order (no new order), and you and the customer get a payment confirmation email.</p>
		<p><label for="wo-paylink-email"><strong>Send to</strong></label><br>
			<input type="email" id="wo-paylink-email" class="widefat" data-paylink-email value="<?php echo esc_attr($billing['billing_email'] ?? ''); ?>"></p>
		<p><label for="wo-paylink-note"><strong>Message</strong> <span class="description">(optional)</span></label><br>
			<textarea id="wo-paylink-note" class="widefat" rows="3" data-paylink-note placeholder="Example: Our card system was down when you ordered. Please use this link to pay."></textarea></p>
		<p class="wo-paylink__actions">
			<button type="button" class="button button-primary" data-paylink="send">Email payment link</button>
			<button type="button" class="button" data-paylink="copy">Copy link</button>
		</p>
		<p class="wo-paylink__url"<?php echo $url ? '' : ' hidden'; ?>><input type="text" readonly class="widefat" value="<?php echo esc_attr($url); ?>" aria-label="Payment link" onfocus="this.select()"></p>
		<p class="wo-paylink__msg" role="status" aria-live="polite"></p>
		<?php if ($sent) : ?>
			<p class="description">Last emailed <?php echo esc_html(wp_date('M j, g:ia', (int) $sent['time'])); ?> to <?php echo esc_html($sent['note']); ?>.</p>
		<?php endif; ?>
		<?php if ($viewed) : ?>
			<p class="description">Customer opened it <?php echo esc_html(wp_date('M j, g:ia', (int) $viewed['time'])); ?>.</p>
		<?php endif; ?>
		<?php if ($failed) : ?>
			<p class="description">Last card attempt failed <?php echo esc_html(wp_date('M j, g:ia', (int) $failed['time'])); ?>: <?php echo esc_html($failed['note']); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

add_action('admin_footer-post.php', function () {
	if ('order' !== get_post_type()) {
		return;
	}
	?>
	<style>
		.wo-paylink__actions { display: flex; flex-wrap: wrap; gap: 6px; }
		.wo-paylink__msg:empty { display: none; }
		.wo-paylink__msg { padding: 8px 10px; border-radius: 4px; background: #edf7ed; color: #1e4620; }
		.wo-paylink__msg.is-error { background: #fcf0f1; color: #8a1f1f; }
		.wo-paylink__done { margin: 0 0 8px; padding: 8px 10px; border-radius: 4px; background: #edf7ed; color: #1e4620; }
		.wo-paylink__facts { display: grid; grid-template-columns: auto 1fr; gap: 4px 10px; margin: 0 0 8px; }
		.wo-paylink__facts dt { color: #646970; }
		.wo-paylink__facts dd { margin: 0; overflow-wrap: anywhere; }
	</style>
	<script>
		(function () {
			var box = document.querySelector('.wo-paylink');
			if (!box) return;
			var msg = box.querySelector('.wo-paylink__msg');
			var urlRow = box.querySelector('.wo-paylink__url');
			var urlInput = urlRow ? urlRow.querySelector('input') : null;
			var emailInput = box.querySelector('[data-paylink-email]');
			var noteInput = box.querySelector('[data-paylink-note]');
			var say = function (text, isError) {
				msg.textContent = text;
				msg.classList.toggle('is-error', !!isError);
			};
			// Enter in these fields must not submit (and save) the whole order form.
			box.querySelectorAll('input').forEach(function (input) {
				input.addEventListener('keydown', function (event) {
					if (event.key === 'Enter') event.preventDefault();
				});
			});
			box.querySelectorAll('[data-paylink]').forEach(function (button) {
				button.addEventListener('click', function () {
					var mode = button.getAttribute('data-paylink');
					var email = emailInput ? emailInput.value.trim() : '';
					if (mode === 'send' && !window.confirm('Email the payment link to ' + email + '?')) return;
					if (mode === 'receipt' && !window.confirm('Email the payment receipt to ' + email + '?')) return;
					var data = new FormData();
					data.append('action', 'wholesale_order_pay_link');
					data.append('mode', mode);
					data.append('order', box.getAttribute('data-order'));
					data.append('nonce', box.getAttribute('data-nonce'));
					data.append('email', email);
					data.append('note', noteInput ? noteInput.value : '');
					button.disabled = true;
					say(mode === 'copy' ? 'Creating link…' : 'Sending…');
					fetch(window.ajaxurl, { method: 'POST', body: data, credentials: 'same-origin' })
						.then(function (response) { return response.json(); })
						.then(function (json) {
							var result = json && json.data ? json.data : {};
							if (result.url && urlInput) {
								urlInput.value = result.url;
								urlRow.hidden = false;
							}
							if (!json || !json.success) throw new Error(result.message || 'Something went wrong. Please try again.');
							if (mode !== 'copy') {
								say(result.message);
								return;
							}
							var copied = navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText(result.url) : Promise.reject();
							return copied.then(function () {
								say('Link copied. Paste it into a text or an email to the customer.');
							}, function () {
								urlInput.focus();
								urlInput.select();
								say('Link ready. Press Ctrl+C to copy it.');
							});
						})
						.catch(function (error) { say(error.message || 'Something went wrong. Please try again.', true); })
						.then(function () { button.disabled = false; });
				});
			});
		})();
	</script>
	<?php
});

/**
 * AJAX (staff): create the link and email it (mode 'send'), just return it ('copy'),
 * or resend the payment receipt of a paid order ('receipt').
 */
function wholesale_ajax_order_pay_link()
{
	$order_id = isset($_POST['order']) ? absint($_POST['order']) : 0;
	if (!$order_id || !check_ajax_referer('wholesale_order_pay_link_' . $order_id, 'nonce', false) || !current_user_can('edit_post', $order_id)) {
		wp_send_json_error(array('message' => 'Your session expired. Reload the page and try again.'), 403);
	}
	$mode = sanitize_key(wp_unslash($_POST['mode'] ?? ''));
	$email = sanitize_email(wp_unslash($_POST['email'] ?? ''));

	if ('receipt' === $mode) {
		if (!get_post_meta($order_id, '_pay_link_paid_at', true)) {
			wp_send_json_error(array('message' => 'This order was not paid through a payment link.'));
		}
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Enter a valid email address.'));
		}
		if (!wholesale_send_order_pay_receipt($order_id, array($email))) {
			wp_send_json_error(array('message' => 'The receipt could not be sent right now. It is retried automatically from the Emails log.'));
		}
		wp_send_json_success(array('message' => 'Receipt emailed to ' . $email . '.'));
	}

	if (!wholesale_order_pay_is_open($order_id)) {
		wp_send_json_error(array('message' => 'This order is not waiting for payment, so no link was sent.'));
	}

	$url = wholesale_order_pay_url($order_id, true);
	if ('copy' === $mode) {
		wholesale_order_pay_log($order_id, 'copied');
		wp_send_json_success(array('url' => $url));
	}

	if (!is_email($email)) {
		wp_send_json_error(array('message' => 'Enter a valid email address.', 'url' => $url));
	}
	$note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));

	if (!wholesale_send_order_pay_link_email($order_id, $email, $note)) {
		wholesale_order_pay_log($order_id, 'send_failed', $email);
		wp_send_json_error(array('message' => 'The email could not be sent right now. Use Copy link and send it to the customer yourself.', 'url' => $url));
	}
	wholesale_order_pay_log($order_id, 'sent', $email);
	wp_send_json_success(array('url' => $url, 'message' => 'Payment link emailed to ' . $email . '.'));
}
add_action('wp_ajax_wholesale_order_pay_link', 'wholesale_ajax_order_pay_link');

// ------------------------------------------------------------------
// Emails
// ------------------------------------------------------------------

/**
 * The customer email layout used by order emails (same look as the order confirmation).
 */
function wholesale_order_pay_email_shell($inner_html, $for_customer = true)
{
	$font = "font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;";
	return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;padding:0;background:#f5f8fb;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f8fb;padding:24px 12px;"><tr><td align="center">'
		. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e3e9ef;border-radius:12px;">'
		. '<tr><td style="padding:24px 28px;border-bottom:4px solid #1fa8de;' . $font . 'font-size:20px;font-weight:700;color:#0d2e4d;">Storefront Sign Online</td></tr>'
		. '<tr><td style="padding:28px;' . $font . 'color:#172027;font-size:15px;line-height:1.6;">'
		. $inner_html
		. ($for_customer ? '<p style="margin:24px 0 0;font-size:14px;color:#5b6b7b;">Questions? Call <a href="tel:+18664362101" style="color:#1287b5;">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PT) or reply to this email with your order number.</p>' : '')
		. '</td></tr>'
		. '<tr><td style="padding:18px 28px;border-top:1px solid #e3e9ef;' . $font . 'font-size:12px;color:#8a97a5;">Storefront Sign Online &middot; 2601 NE 12th St, Renton, WA 98056</td></tr>'
		. '</table></td></tr></table></body></html>';
}

/**
 * The order's items and totals as email tables.
 */
function wholesale_order_pay_items_email_html($order_id, $total_label)
{
	$items = wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_json', true));
	$cost = wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_cost', true));
	$font = "font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;";
	$money = static function ($value) {
		return '$' . number_format((float) $value, 2);
	};

	$rows = '';
	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}
		$specs = wholesale_item_specs($item);
		unset($specs['Design Url'], $specs['My Artwork'], $specs['Payment Ticket']);
		$spec_text = array();
		foreach (array_slice($specs, 0, 8, true) as $label => $value) {
			$spec_text[] = esc_html($label) . ': ' . esc_html(wp_strip_all_tags($value));
		}
		$rows .= '<tr>'
			. '<td style="padding:12px 0;border-bottom:1px solid #e3e9ef;' . $font . 'font-size:14px;color:#172027;"><strong>' . esc_html($item['product_title'] ?? '') . '</strong>'
			. '<br><span style="color:#5b6b7b;font-size:13px;">Qty ' . esc_html((string) ($item['product_quantity'] ?? 1)) . ($spec_text ? ' &middot; ' . implode(' &middot; ', $spec_text) : '') . '</span></td>'
			. '<td align="right" style="padding:12px 0;border-bottom:1px solid #e3e9ef;' . $font . 'font-size:14px;color:#172027;white-space:nowrap;">' . esc_html($money($item['product_subtotal'] ?? 0)) . '</td>'
			. '</tr>';
	}
	$total_row = static function ($label, $value, $strong = false) use ($font) {
		$style = $font . ($strong ? 'font-size:16px;font-weight:700;color:#172027;padding-top:10px;' : 'font-size:14px;color:#5b6b7b;');
		return '<tr><td style="' . $style . '">' . esc_html($label) . '</td><td align="right" style="' . $style . '">' . esc_html($value) . '</td></tr>';
	};

	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rows . '</table>'
		. '<table role="presentation" width="100%" cellpadding="4" cellspacing="0" style="margin-top:12px;">'
		. $total_row('Subtotal', $money($cost['sub_total'] ?? 0))
		. $total_row('Shipping' . (!empty($cost['shipping_method']) ? ' - ' . $cost['shipping_method'] : ''), $money($cost['shipping_cost'] ?? 0))
		. $total_row('Tax', $money($cost['tax'] ?? 0))
		. $total_row($total_label, $money(wholesale_order_total($order_id)), true)
		. '</table>';
}

/**
 * label => value pairs as a shaded email table.
 */
function wholesale_order_pay_facts_email_html($facts)
{
	$rows = '';
	foreach ($facts as $label => $value) {
		$rows .= '<tr><td style="padding:5px 16px;color:#5b6b7b;width:150px;font-size:14px;">' . esc_html($label) . '</td><td style="padding:5px 16px 5px 0;font-weight:700;font-size:14px;color:#172027;">' . esc_html($value) . '</td></tr>';
	}
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;background:#f5f8fb;border:1px solid #e3e9ef;border-radius:8px;"><tr><td colspan="2" style="height:8px;"></td></tr>' . $rows . '<tr><td colspan="2" style="height:8px;"></td></tr></table>';
}

/**
 * The payment link email: order summary and a Pay button.
 */
function wholesale_send_order_pay_link_email($order_id, $email, $note = '')
{
	$billing = wholesale_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
	$number = wholesale_order_number($order_id);
	$amount = '$' . number_format(wholesale_order_total($order_id), 2);
	$url = wholesale_order_pay_url($order_id, true);

	$html = wholesale_order_pay_email_shell(
		'<p style="margin:0 0 12px;">Hi' . (!empty($billing['billing_fname']) ? ' ' . esc_html($billing['billing_fname']) : '') . ',</p>'
		. '<p style="margin:0 0 16px;">Please use the secure link below to pay for your order <strong>#' . esc_html($number) . '</strong>. We start production as soon as your payment is received.</p>'
		. ('' !== $note ? '<p style="margin:0 0 16px;padding:14px 16px;background:#eef7fc;border-radius:8px;color:#0d2e4d;">' . nl2br(esc_html($note)) . '</p>' : '')
		. wholesale_order_pay_items_email_html($order_id, 'Total due')
		. '<p style="margin:24px 0 0;"><a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 26px;background:#1fa8de;border-radius:8px;color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;">Pay ' . esc_html($amount) . ' securely</a></p>'
		. '<p style="margin:12px 0 0;font-size:13px;color:#5b6b7b;">Card payments are processed by Elavon Converge. We never store your card number.</p>'
		. '<p style="margin:12px 0 0;font-size:13px;color:#5b6b7b;overflow-wrap:anywhere;">If the button does not work, copy this link into your browser:<br><a href="' . esc_url($url) . '" style="color:#1287b5;">' . esc_html($url) . '</a></p>'
	);

	return true === wholesale_send_html_mail($email, sprintf('Payment for your order #%s (%s)', $number, $amount), $html, wholesale_customer_email_headers(), 'Storefront Sign Online');
}

/**
 * Customer payment receipt. Goes to the order's email and, when the payer typed a different
 * one on the pay page, to that too (or only to $recipients when given, for a resend).
 *
 * @return bool True when at least one copy was accepted by the mail server.
 */
function wholesale_send_order_pay_receipt($order_id, $recipients = array())
{
	$billing = wholesale_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
	$payer = wholesale_order_pay_payer($order_id);
	if (!$recipients) {
		$recipients = array($billing['billing_email'] ?? '', $payer['billing_email'] ?? '');
	}
	$to = array();
	foreach ($recipients as $email) {
		$email = sanitize_email((string) $email);
		if (is_email($email)) {
			$to[strtolower($email)] = $email;
		}
	}
	if (!$to) {
		wholesale_order_pay_log($order_id, 'receipt_failed', '(no valid customer email on the order)');
		return false;
	}

	$number = wholesale_order_number($order_id);
	$facts = wholesale_order_pay_facts($order_id);
	$first = $billing['billing_fname'] ?? ($payer['billing_fname'] ?? '');
	$html = wholesale_order_pay_email_shell(
		'<h1 style="margin:0 0 8px;font-size:22px;">Payment received. Thank you!</h1>'
		. '<p style="margin:0 0 16px;">Hi' . ($first ? ' ' . esc_html($first) : '') . ', we received your payment of <strong>' . esc_html($facts['Amount paid']) . '</strong> for order <strong>#' . esc_html($number) . '</strong>. Your order is now in our production queue, and we will email you tracking details when it ships.</p>'
		. wholesale_order_pay_facts_email_html($facts)
		. wholesale_order_pay_items_email_html($order_id, 'Total paid')
		. '<p style="margin:24px 0 0;"><a href="' . esc_url(wholesale_track_order_url($order_id)) . '" style="display:inline-block;padding:12px 22px;background:#1fa8de;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Track your order</a></p>'
		. '<p style="margin:16px 0 0;font-size:13px;color:#5b6b7b;">This charge appears on your card statement from our card processor, Elavon Converge. Keep this email as your receipt.</p>'
	);
	$subject = sprintf('Payment received for order #%s - %s', $number, $facts['Amount paid']);

	$any = false;
	foreach ($to as $email) {
		$result = wholesale_send_html_mail($email, $subject, $html, wholesale_customer_email_headers(), 'Storefront Sign Online');
		wholesale_order_pay_log($order_id, true === $result ? 'receipt_sent' : 'receipt_failed', $email . (true === $result ? '' : ' (' . $result . ' Retried automatically from the Emails log.)'));
		$any = $any || true === $result;
	}
	return $any;
}

/**
 * Staff email: one copy per store notification recipient (Emails → SMTP Settings).
 *
 * @return bool True when at least one copy was accepted.
 */
function wholesale_send_order_pay_staff_email($order_id)
{
	$number = wholesale_order_number($order_id);
	$facts = wholesale_order_pay_facts($order_id, true);
	$payer = wholesale_order_pay_payer($order_id);
	$billing = wholesale_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
	$verify = 'needs_review' === wholesale_get_payment_status($order_id);

	$payer_facts = array_filter(array(
		'Name' => trim(($payer['billing_fname'] ?? '') . ' ' . ($payer['billing_lname'] ?? '')),
		'Company' => $payer['billing_company'] ?? '',
		'Email' => $payer['billing_email'] ?? '',
		'Phone' => $payer['billing_tel'] ?? '',
		'Card billing' => implode(', ', array_filter(array($payer['billing_address'] ?? '', $payer['billing_address_2'] ?? '', $payer['billing_city'] ?? '', trim(($payer['billing_state'] ?? '') . ' ' . ($payer['billing_zip'] ?? ''))))),
		'Order email' => ($billing['billing_email'] ?? '') !== ($payer['billing_email'] ?? '') ? ($billing['billing_email'] ?? '') : '',
	), 'strlen');

	$html = wholesale_order_pay_email_shell(
		'<h1 style="margin:0 0 8px;font-size:22px;">' . ($verify ? 'Payment needs checking: order #' : 'Payment received: order #') . esc_html($number) . '</h1>'
		. '<p style="margin:0 0 16px;">' . ($verify
			? 'The customer paid through the payment link, but Converge could not be reached to double-check the transaction. <strong>Confirm it in Converge before production.</strong>'
			: 'The customer paid this order by card through the payment link. The order is now marked <strong>Paid</strong>.') . '</p>'
		. wholesale_order_pay_facts_email_html($facts)
		. ($payer_facts ? '<h2 style="margin:0 0 8px;font-size:16px;">Paid by</h2>' . wholesale_order_pay_facts_email_html($payer_facts) : '')
		. wholesale_order_pay_items_email_html($order_id, 'Total paid')
		. '<p style="margin:24px 0 0;"><a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '" style="display:inline-block;padding:12px 22px;background:#1fa8de;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Open order #' . esc_html($number) . '</a></p>',
		false
	);
	$subject = sprintf('%s: Order #%s - %s paid by card (payment link)', $verify ? 'VERIFY PAYMENT' : 'Payment received', $number, $facts['Amount paid']);
	$headers = array('Content-Type: text/html; charset=UTF-8');
	if (!empty($payer['billing_email']) && is_email($payer['billing_email'])) {
		$headers[] = 'Reply-To: ' . $payer['billing_email'];
	}

	$sent = function_exists('wholesale_mail_team')
		? wholesale_mail_team($subject, $html, $headers)
		: wp_mail(wholesale_contact_admin_recipients(), $subject, $html, $headers);
	wholesale_order_pay_log($order_id, $sent ? 'team_notified' : 'team_failed', implode(', ', wholesale_contact_admin_recipients()));
	return $sent;
}

// ------------------------------------------------------------------
// Payment (called from inc/payments.php once the card is approved)
// ------------------------------------------------------------------

/**
 * Records an approved card payment on the order, then emails staff and the customer.
 *
 * @param int   $order_id
 * @param array $billing      Billing details typed on the pay page.
 * @param array $payment_meta _payment_* meta, as checkout saves it.
 * @return int The order ID.
 */
function wholesale_order_pay_complete($order_id, $billing, $payment_meta)
{
	$amount = (float) ($payment_meta['_payment_amount'] ?? wholesale_order_total($order_id));
	$txn = (string) ($payment_meta['_payment_txn_id'] ?? '');
	$last4 = (string) ($payment_meta['_payment_card_last4'] ?? '');
	$number = wholesale_order_number($order_id);
	$summary = '$' . number_format($amount, 2) . ($last4 ? ', card ending ' . $last4 : '') . ($txn ? ' (Converge ' . $txn . ')' : '');

	// Paid meanwhile (another tab, or staff marked it paid): keep the first payment, flag this one for a refund.
	if (!wholesale_order_pay_is_open($order_id)) {
		add_post_meta($order_id, '_pay_link_duplicate', $payment_meta);
		wholesale_order_pay_log($order_id, 'duplicate', $summary);
		$subject = 'URGENT: order #' . $number . ' was paid twice - refund one payment';
		$text = sprintf("Order #%s was already paid, but the customer paid again through the payment link: %s.\n\nPlease refund the second payment in Converge.\n\n%s", $number, $summary, admin_url('post.php?post=' . $order_id . '&action=edit'));
		function_exists('wholesale_mail_team') ? wholesale_mail_team($subject, $text) : wp_mail(wholesale_contact_admin_recipients(), $subject, $text);
		return $order_id;
	}

	// The payment lives on the order itself: Payment box, order list, refunds and customer pages read these.
	foreach ($payment_meta as $key => $value) {
		update_post_meta($order_id, $key, $value);
	}
	delete_post_meta($order_id, '_payment_verified_by');
	update_post_meta($order_id, '_pay_link_paid_at', current_time('mysql'));
	update_post_meta($order_id, '_pay_link_billing', wp_slash(wp_json_encode($billing)));
	wholesale_order_pay_log($order_id, 'paid', $summary);

	wholesale_send_order_pay_staff_email($order_id);
	wholesale_send_order_pay_receipt($order_id);

	return $order_id;
}
