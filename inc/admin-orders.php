<?php
/**
 * Admin tools for running the store: order list columns and search, payment and
 * fulfillment box, customer status emails, sales dashboard and review moderation.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

function wholesale_payment_badge($post_id)
{
	$labels = array(
		'paid' => array('Paid', 'success'),
		'paid_offline' => array('Paid (offline)', 'success'),
		'needs_review' => array('Verify payment', 'danger'),
		'manual' => array('Collect payment', 'warn'),
	);
	$status = get_post_meta($post_id, '_payment_status', true);
	if (!$status && get_post_meta($post_id, '_ticket_id', true)) {
		$status = 'paid';
	}
	list($label, $tone) = isset($labels[$status]) ? $labels[$status] : array('Not recorded', 'muted');
	return '<span class="order-badge order-badge--' . esc_attr($tone) . '">' . esc_html($label) . '</span>';
}

function wholesale_order_total($post_id)
{
	$cost = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_cost', true));
	return isset($cost['grand_total']) ? (float) $cost['grand_total'] : 0.0;
}

// ------------------------------------------------------------------
// Order list
// ------------------------------------------------------------------

add_filter('manage_order_posts_columns', function ($columns) {
	$updated = array();
	foreach ($columns as $key => $label) {
		$updated[$key] = $label;
		if ('order_status' === $key) {
			$updated['payment'] = __('Payment', 'litsign');
		}
	}
	return $updated;
}, 20);

add_action('manage_order_posts_custom_column', function ($column, $post_id) {
	if ('payment' === $column) {
		echo wholesale_payment_badge($post_id); // Escaped in the helper.
	}
}, 10, 2);

// Friendlier order number and colored status badges in the existing columns.
add_action('admin_footer-edit.php', function () {
	if ('order' !== get_current_screen()->post_type) {
		return;
	}
	?>
	<script>
		document.querySelectorAll('.column-order_status .wholesale-order-status').forEach(function (el) {
			var tones = { pending: 'info', processing: 'info', 'on-hold': 'warn', on_hold: 'warn', completed: 'success', cancelled: 'muted', refunded: 'muted', failed: 'danger' };
			el.classList.add('order-badge', 'order-badge--' + (tones[el.dataset.status] || 'muted'));
		});
		document.querySelectorAll('.column-order_number strong').forEach(function (el) {
			el.textContent = el.textContent.replace(/^#order_/i, '#').toUpperCase();
		});
	</script>
	<?php
});

/**
 * Order search matches order number, customer name, email and phone.
 */
add_filter('posts_search', function ($search, $query) {
	global $wpdb;
	if (!is_admin() || !$query->is_main_query() || 'order' !== $query->get('post_type') || '' === $query->get('s')) {
		return $search;
	}
	$term = '%' . $wpdb->esc_like(trim($query->get('s'))) . '%';
	$term_order = '%' . $wpdb->esc_like(preg_replace('/^#?(order_)?/i', '', trim($query->get('s')))) . '%';
	return $wpdb->prepare(
		" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = 'order_id' AND meta_value LIKE %s) OR (meta_key = 'billing_address' AND meta_value LIKE %s)))",
		$term,
		$term_order,
		$term
	);
}, 10, 2);

// ------------------------------------------------------------------
// Order edit screen: header, status, payment and activity
// ------------------------------------------------------------------

// Orders are records, not content: use the classic screen so the status box and details form work.
add_filter('use_block_editor_for_post_type', function ($use, $post_type) {
	return 'order' === $post_type ? false : $use;
}, 10, 2);

add_action('add_meta_boxes_order', function () {
	remove_meta_box('submitdiv', 'order', 'side');
	remove_meta_box('slugdiv', 'order', 'normal');
	add_meta_box('wholesale-order-status', 'Order status', 'wholesale_render_status_box', 'order', 'side', 'high');
	add_meta_box('wholesale-fulfillment', 'Payment', 'wholesale_render_fulfillment_box', 'order', 'side', 'high');
	add_meta_box('wholesale-order-activity', 'Activity', 'wholesale_render_activity_box', 'order', 'side', 'default');
});

/**
 * What the customer is told when an order moves to each status (see wholesale_send_status_email()).
 */
function wholesale_status_email_hints()
{
	return array(
		'pending' => 'No email is sent.',
		'processing' => 'Customer is emailed that the order is in production.',
		'on-hold' => 'Customer is emailed to call or reply with the missing detail.',
		'on_hold' => 'Customer is emailed to call or reply with the missing detail.',
		'completed' => 'Customer is emailed a shipped notice with the tracking link.',
		'cancelled' => 'Customer is emailed that the order was cancelled.',
		'refunded' => 'Customer is emailed that the refund was issued.',
		'failed' => 'No email is sent.',
	);
}

// Summary header above the boxes: order number, badges, customer and quick actions.
add_action('edit_form_top', function ($post) {
	if ('order' !== $post->post_type) {
		return;
	}
	$billing = wholesale_decode_order_meta_array(get_post_meta($post->ID, 'billing_address', true));
	$name = trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? ''));
	$email = $billing['billing_email'] ?? '';
	$phone = $billing['billing_tel'] ?? '';
	?>
	<div class="wo-header">
		<div class="wo-header__main">
			<h2 class="wo-header__title">Order #<?php echo esc_html(wholesale_order_number($post->ID)); ?></h2>
			<?php echo wholesale_order_status_badge($post->post_status); // Escaped in the helper. ?>
			<?php echo wholesale_payment_badge($post->ID); // Escaped in the helper. ?>
		</div>
		<p class="wo-header__meta">
			Placed <?php echo esc_html(get_the_date('M j, Y \a\t g:ia', $post)); ?>
			<?php if ($name) : ?>&middot; <?php echo esc_html($name); ?><?php endif; ?>
			<?php if (!empty($billing['billing_company'])) : ?>(<?php echo esc_html($billing['billing_company']); ?>)<?php endif; ?>
			&middot; <strong>$<?php echo esc_html(number_format(wholesale_order_total($post->ID), 2)); ?></strong>
		</p>
		<div class="wo-header__actions">
			<?php if (is_email($email)) : ?>
				<a class="button" href="mailto:<?php echo esc_attr($email); ?>?subject=<?php echo rawurlencode('Your order #' . wholesale_order_number($post->ID)); ?>"><span class="dashicons dashicons-email" aria-hidden="true"></span> Email customer</a>
			<?php endif; ?>
			<?php if ($phone) : ?>
				<a class="button" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><span class="dashicons dashicons-phone" aria-hidden="true"></span> <?php echo esc_html($phone); ?></a>
			<?php endif; ?>
			<a class="button" href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-external" aria-hidden="true"></span> Customer view</a>
		</div>
	</div>
	<?php
});

function wholesale_render_status_box($post)
{
	$status = $post->post_status;
	$info = wholesale_order_status_info();
	$hints = wholesale_status_email_hints();
	$emails_on = wholesale_setting_enabled('email_status_updates');
	$carrier = get_post_meta($post->ID, '_tracking_carrier', true);
	$tracking = get_post_meta($post->ID, '_tracking_number', true);
	$tracking_url = wholesale_tracking_url($carrier, $tracking);
	// "on_hold" is a legacy duplicate of "on-hold"; only offer it on orders that already use it.
	$options = array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed');
	if ('on_hold' === $status) {
		$options[array_search('on-hold', $options, true)] = 'on_hold';
	}
	wp_nonce_field('wholesale_fulfillment', 'wholesale_fulfillment_nonce');
	?>
	<div class="wo-status" data-current="<?php echo esc_attr($status); ?>">
		<input type="hidden" name="original_post_status" value="<?php echo esc_attr($status); ?>">
		<p class="wo-status__row">
			<label for="wo_post_status"><strong>Status</strong></label>
			<select name="post_status" id="wo_post_status">
				<?php foreach ($options as $value) : ?>
					<option value="<?php echo esc_attr($value); ?>" data-hint="<?php echo esc_attr($hints[$value] ?? ''); ?>" <?php selected($status, $value); ?>><?php echo esc_html($info[$value]['label'] ?? $value); ?></option>
				<?php endforeach; ?>
				<?php if (!in_array($status, $options, true)) : ?>
					<option value="<?php echo esc_attr($status); ?>" selected><?php echo esc_html(ucwords(str_replace(array('-', '_'), ' ', $status))); ?></option>
				<?php endif; ?>
			</select>
		</p>

		<div class="wo-status__tracking">
			<p class="wo-status__row">
				<label for="wholesale_tracking_carrier"><strong>Carrier</strong></label>
				<select name="wholesale_tracking_carrier" id="wholesale_tracking_carrier">
					<?php foreach (array('' => 'Select…', 'ups' => 'UPS', 'fedex' => 'FedEx', 'usps' => 'USPS', 'freight' => 'Freight / other') as $value => $label) : ?>
						<option value="<?php echo esc_attr($value); ?>" <?php selected($carrier, $value); ?>><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="wo-status__row">
				<label for="wholesale_tracking_number"><strong>Tracking #</strong></label>
				<input type="text" name="wholesale_tracking_number" id="wholesale_tracking_number" value="<?php echo esc_attr($tracking); ?>" autocomplete="off">
			</p>
			<?php if ($tracking_url) : ?>
				<p class="wo-status__track"><a href="<?php echo esc_url($tracking_url); ?>" target="_blank" rel="noopener">Track this package &rarr;</a></p>
			<?php endif; ?>
			<p class="wo-status__warning" hidden>Add a tracking number so the shipped email includes a tracking link.</p>
		</div>

		<?php if ($emails_on) : ?>
			<div class="wo-status__notify" hidden>
				<label><input type="checkbox" name="wholesale_notify_customer" value="1" checked> Email the customer about this change</label>
				<p class="description wo-status__hint"></p>
			</div>
		<?php else : ?>
			<p class="description">Status emails are off. <a href="<?php echo esc_url(admin_url('options-general.php?page=wholesale-settings')); ?>">Settings</a></p>
		<?php endif; ?>
	</div>
	<div class="wo-status__footer">
		<?php if (current_user_can('delete_post', $post->ID)) : ?>
			<a class="submitdelete" href="<?php echo esc_url(get_delete_post_link($post->ID)); ?>">Move to Trash</a>
		<?php endif; ?>
		<span class="spinner"></span>
		<button type="submit" name="save" id="publish" class="button button-primary button-large">Update order</button>
	</div>
	<?php
}

function wholesale_render_fulfillment_box($post)
{
	$payment_status = get_post_meta($post->ID, '_payment_status', true);
	$txn = get_post_meta($post->ID, '_payment_txn_id', true);
	$last4 = get_post_meta($post->ID, '_payment_card_last4', true);
	$approval = get_post_meta($post->ID, '_payment_approval_code', true);
	$ticket_id = (int) get_post_meta($post->ID, '_ticket_id', true);
	$verified_by = (int) get_post_meta($post->ID, '_payment_verified_by', true);
	?>
	<div class="wholesale-fulfillment">
		<p class="wo-pay__total"><span class="wo-pay__amount">$<?php echo esc_html(number_format(wholesale_order_total($post->ID), 2)); ?></span> <?php echo wholesale_payment_badge($post->ID); // Escaped in the helper. ?></p>
		<?php if ($txn || $last4 || $approval) : ?>
			<dl class="wo-pay__facts">
				<?php if ($txn) : ?><dt>Converge txn</dt><dd><code><?php echo esc_html($txn); ?></code></dd><?php endif; ?>
				<?php if ($last4) : ?><dt>Card</dt><dd>&bull;&bull;&bull;&bull; <?php echo esc_html($last4); ?></dd><?php endif; ?>
				<?php if ($approval) : ?><dt>Approval</dt><dd><?php echo esc_html($approval); ?></dd><?php endif; ?>
			</dl>
		<?php endif; ?>
		<?php if ($ticket_id) : ?>
			<p>Paid via <a href="<?php echo esc_url(get_edit_post_link($ticket_id)); ?>">payment ticket #<?php echo esc_html((string) $ticket_id); ?></a></p>
		<?php endif; ?>
		<?php if ($verified_by && ($user = get_userdata($verified_by))) : ?>
			<p class="description">Payment confirmed by <?php echo esc_html($user->display_name); ?>.</p>
		<?php endif; ?>
		<?php if ('needs_review' === $payment_status) : ?>
			<p class="wholesale-fulfillment-warning">Converge could not be reached to double-check this payment. Confirm the transaction in Converge before starting production.</p>
			<p><label><input type="checkbox" name="wholesale_payment_verified" value="1"> I confirmed this payment in Converge</label></p>
		<?php elseif ('manual' === $payment_status) : ?>
			<p class="wholesale-fulfillment-warning">No card payment was taken. Collect payment before production.</p>
			<p><label><input type="checkbox" name="wholesale_payment_verified" value="1"> Payment collected</label></p>
		<?php endif; ?>
	</div>
	<?php
}

function wholesale_render_activity_box($post)
{
	$info = wholesale_order_status_info();
	$label = static function ($status) use ($info) {
		return $info[$status]['label'] ?? ucwords(str_replace(array('-', '_'), ' ', (string) $status));
	};
	$events = array(array('time' => get_post_time('U', true, $post), 'text' => 'Order placed'));
	foreach (get_post_meta($post->ID, '_status_log') as $entry) {
		if (!is_array($entry) || empty($entry['time'])) {
			continue;
		}
		$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
		$events[] = array(
			'time' => (int) $entry['time'],
			'text' => 'Status: ' . $label($entry['from'] ?? '') . ' → ' . $label($entry['to'] ?? '') . ($user ? ' by ' . $user->display_name : ''),
		);
	}
	foreach (get_post_meta($post->ID, '_status_email_log') as $entry) {
		// Stored as "Y-m-d H:i:s status" in site time.
		$parts = explode(' ', (string) $entry);
		if (count($parts) < 3) {
			continue;
		}
		$events[] = array(
			'time' => (int) get_gmt_from_date($parts[0] . ' ' . $parts[1], 'U'),
			'text' => 'Customer emailed: ' . $label($parts[2]),
		);
	}
	usort($events, static function ($a, $b) {
		return $b['time'] <=> $a['time'];
	});
	?>
	<ol class="wo-activity">
		<?php foreach (array_slice($events, 0, 25) as $event) : ?>
			<li><span><?php echo esc_html($event['text']); ?></span><time><?php echo esc_html(wp_date('M j, g:ia', $event['time'])); ?></time></li>
		<?php endforeach; ?>
	</ol>
	<?php
}

// Log every status change, whether from this screen, quick edit or bulk actions.
add_action('transition_post_status', function ($new_status, $old_status, $post) {
	if ('order' !== $post->post_type || $new_status === $old_status || in_array($old_status, array('new', 'auto-draft', 'draft'), true)) {
		return;
	}
	add_post_meta($post->ID, '_status_log', array('time' => time(), 'user' => get_current_user_id(), 'from' => $old_status, 'to' => $new_status));
}, 10, 3);

/**
 * Merge edited address fields into an order's stored address array.
 */
function wholesale_merge_order_address($stored, $posted, $prefix, $with_email)
{
	foreach (wholesale_order_address_fields($with_email) as $key => $label) {
		if (!isset($posted[$key]) || !is_scalar($posted[$key])) {
			continue;
		}
		$value = wp_unslash((string) $posted[$key]);
		$stored[$prefix . $key] = 'email' === $key ? sanitize_email($value) : sanitize_text_field($value);
	}
	return $stored;
}

// Runs before the theme's status handler (priority 10) so status emails see the new tracking number.
add_action('save_post_order', function ($post_id) {
	if (!isset($_POST['wholesale_fulfillment_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wholesale_fulfillment_nonce'])), 'wholesale_fulfillment') || !current_user_can('edit_post', $post_id)) {
		return;
	}
	update_post_meta($post_id, '_tracking_carrier', sanitize_key(wp_unslash($_POST['wholesale_tracking_carrier'] ?? '')));
	update_post_meta($post_id, '_tracking_number', sanitize_text_field(wp_unslash($_POST['wholesale_tracking_number'] ?? '')));
	if (!empty($_POST['wholesale_payment_verified'])) {
		update_post_meta($post_id, '_payment_status', 'manual' === get_post_meta($post_id, '_payment_status', true) ? 'paid_offline' : 'paid');
		update_post_meta($post_id, '_payment_verified_by', get_current_user_id());
	}
	if (empty($_POST['wholesale_notify_customer'])) {
		$GLOBALS['wholesale_skip_status_email'] = true;
	}
	if (isset($_POST['wo_staff_note'])) {
		update_post_meta($post_id, '_staff_note', sanitize_textarea_field(wp_unslash($_POST['wo_staff_note'])));
	}

	// Addresses and ship date are only rewritten when staff opened the edit form.
	if (empty($_POST['wo_details_edited'])) {
		return;
	}
	$billing = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
	$shipping = wholesale_decode_order_meta_array(get_post_meta($post_id, 'shipping_address', true));
	if (isset($_POST['wo_billing']) && is_array($_POST['wo_billing'])) {
		$billing = wholesale_merge_order_address($billing, $_POST['wo_billing'], 'billing_', true);
		update_post_meta($post_id, 'billing_address', wp_slash(wp_json_encode($billing)));
	}
	if (isset($_POST['wo_shipping']) && is_array($_POST['wo_shipping'])) {
		$shipping = wholesale_merge_order_address($shipping, $_POST['wo_shipping'], wholesale_order_shipping_prefix($shipping), false);
		update_post_meta($post_id, 'shipping_address', wp_slash(wp_json_encode($shipping)));
	}
	if (isset($_POST['wo_ship_date'])) {
		update_post_meta($post_id, 'estimate_delivery_time', sanitize_text_field(wp_unslash($_POST['wo_ship_date'])));
	}
}, 5);

// Status box behaviour: email hint and tracking prompt follow the selected status; details box edit toggle.
add_action('admin_footer-post.php', function () {
	if ('order' !== get_current_screen()->post_type) {
		return;
	}
	?>
	<script>
		(function () {
			var box = document.querySelector('.wo-status');
			var select = document.getElementById('wo_post_status');
			if (box && select) {
				var notify = box.querySelector('.wo-status__notify');
				var hint = box.querySelector('.wo-status__hint');
				var tracking = document.getElementById('wholesale_tracking_number');
				var warning = box.querySelector('.wo-status__warning');
				var sync = function () {
					var changed = select.value !== box.dataset.current;
					var shipped = select.value === 'completed';
					if (notify) {
						notify.hidden = !changed;
						hint.textContent = select.options[select.selectedIndex].dataset.hint || '';
					}
					box.classList.toggle('is-shipping', shipped);
					warning.hidden = !(changed && shipped && !tracking.value.trim());
				};
				select.addEventListener('change', sync);
				tracking.addEventListener('input', sync);
				sync();
			}

			var details = document.querySelector('.wo-details');
			if (details) {
				var flag = details.querySelector('[name="wo_details_edited"]');
				var setEditing = function (on) {
					details.dataset.editing = on ? '1' : '0';
					flag.value = on ? '1' : '0';
					if (on) {
						details.querySelector('.wo-edit input').focus();
					}
				};
				details.querySelector('.wo-details__edit').addEventListener('click', function () { setEditing(true); });
				details.querySelector('.wo-details__cancel').addEventListener('click', function () {
					details.querySelectorAll('.wo-edit input').forEach(function (input) { input.value = input.defaultValue; });
					setEditing(false);
				});
				details.querySelector('.wo-copy-billing').addEventListener('click', function () {
					details.querySelectorAll('[name^="wo_shipping["]').forEach(function (input) {
						var source = details.querySelector('[name="' + input.name.replace('wo_shipping', 'wo_billing') + '"]');
						if (source) {
							input.value = source.value;
						}
					});
				});
			}

			// Show the spinner and block double submits while saving.
			var form = document.getElementById('post');
			if (form) {
				form.addEventListener('submit', function () {
					var button = document.getElementById('publish');
					if (button) {
						button.disabled = true;
						button.previousElementSibling.classList.add('is-active');
					}
				});
			}
		})();
	</script>
	<?php
});

// Clear notice after saving an order.
add_filter('post_updated_messages', function ($messages) {
	$messages['order'] = array_fill(0, 11, '');
	$messages['order'][1] = 'Order updated.';
	$messages['order'][4] = 'Order updated.';
	return $messages;
});

// ------------------------------------------------------------------
// Customer emails when an order's status changes
// ------------------------------------------------------------------

add_action('transition_post_status', function ($new_status, $old_status, $post) {
	if ('order' !== $post->post_type || $new_status === $old_status || in_array($old_status, array('new', 'auto-draft', 'draft'), true)) {
		return;
	}
	if (!wholesale_setting_enabled('email_status_updates')) {
		return;
	}
	// Send at the end of the request so tracking numbers saved in the same request are included.
	$GLOBALS['wholesale_status_emails'][$post->ID] = $new_status;
}, 10, 3);

add_action('shutdown', function () {
	if (empty($GLOBALS['wholesale_status_emails']) || !empty($GLOBALS['wholesale_skip_status_email'])) {
		return;
	}
	foreach ($GLOBALS['wholesale_status_emails'] as $post_id => $status) {
		wholesale_send_status_email($post_id, $status);
	}
});

function wholesale_send_status_email($post_id, $status)
{
	$billing = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
	$email = $billing['billing_email'] ?? '';
	if (!is_email($email)) {
		return;
	}

	$number = wholesale_order_number($post_id);
	$tracking = get_post_meta($post_id, '_tracking_number', true);
	$tracking_url = wholesale_tracking_url(get_post_meta($post_id, '_tracking_carrier', true), $tracking);
	$messages = array(
		'processing' => array('Your order #%s is in production', 'Good news: your order is now in production. We will email you again when it ships.'),
		'completed' => array('Your order #%s has shipped', 'Your order is on its way.'),
		'on-hold' => array('Action needed on order #%s', 'Your order is on hold while we confirm a detail with you. Please call us at 866-436-2101 or reply to this email.'),
		'on_hold' => array('Action needed on order #%s', 'Your order is on hold while we confirm a detail with you. Please call us at 866-436-2101 or reply to this email.'),
		'cancelled' => array('Order #%s has been cancelled', 'Your order has been cancelled. If you have questions, please call us at 866-436-2101.'),
		'refunded' => array('Order #%s has been refunded', 'Your refund has been issued. It can take 5–10 business days to appear on your statement.'),
	);
	if (!isset($messages[$status])) {
		return;
	}

	list($subject, $body) = $messages[$status];
	$font = "font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;";
	$tracking_html = '';
	if ('completed' === $status && $tracking) {
		$tracking_html = '<p style="margin:16px 0 0;padding:14px 16px;background:#eef7fc;border-radius:8px;font-size:15px;color:#0d2e4d;"><strong>Tracking number:</strong> '
			. ($tracking_url ? '<a href="' . esc_url($tracking_url) . '" style="color:#1287b5;">' . esc_html($tracking) . '</a>' : esc_html($tracking)) . '</p>';
	}

	$html = '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#f5f8fb;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;"><tr><td align="center">'
		. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border:1px solid #e3e9ef;border-radius:12px;">'
		. '<tr><td style="padding:24px 28px;border-bottom:4px solid #1fa8de;' . $font . 'font-size:20px;font-weight:700;color:#0d2e4d;">Storefront Sign Online</td></tr>'
		. '<tr><td style="padding:28px;' . $font . 'color:#172027;font-size:15px;line-height:1.6;">'
		. '<p style="margin:0 0 12px;">Hi' . (!empty($billing['billing_fname']) ? ' ' . esc_html($billing['billing_fname']) : '') . ',</p>'
		. '<p style="margin:0;">' . esc_html($body) . '</p>'
		. $tracking_html
		. '<p style="margin:20px 0 0;"><a href="' . esc_url(get_permalink($post_id)) . '" style="display:inline-block;padding:12px 20px;background:#1fa8de;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">View order #' . esc_html($number) . '</a></p>'
		. '<p style="margin:20px 0 0;color:#5b6b7b;font-size:14px;">Questions? Call <a href="tel:+18664362101" style="color:#1287b5;">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PST).</p>'
		. '</td></tr></table></td></tr></table></body></html>';

	wp_mail($email, sprintf($subject, $number), $html, array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: Storefront Sign Online <TR@StorefrontSignOnline.com>',
	));
	add_post_meta($post_id, '_status_email_log', current_time('mysql') . ' ' . $status);
}

// ------------------------------------------------------------------
// Dashboard: sales at a glance
// ------------------------------------------------------------------

function wholesale_attention_order_ids()
{
	$needs_payment = get_posts(array(
		'post_type' => 'order',
		'post_status' => array('pending', 'processing', 'on-hold', 'on_hold'),
		'meta_query' => array(array('key' => '_payment_status', 'value' => array('needs_review', 'manual'), 'compare' => 'IN')),
		'posts_per_page' => 50,
		'fields' => 'ids',
	));
	$on_hold = get_posts(array('post_type' => 'order', 'post_status' => array('on-hold', 'on_hold', 'pending'), 'posts_per_page' => 50, 'fields' => 'ids'));
	return array_values(array_unique(array_merge($needs_payment, $on_hold)));
}

add_action('wp_dashboard_setup', function () {
	if (!current_user_can('edit_posts')) {
		return;
	}
	wp_add_dashboard_widget('wholesale_sales', 'Storefront Sign sales', 'wholesale_render_sales_widget');

	// Put the sales widget first.
	global $wp_meta_boxes;
	$normal = $wp_meta_boxes['dashboard']['normal']['core'];
	$widget = array('wholesale_sales' => $normal['wholesale_sales']);
	unset($normal['wholesale_sales']);
	$wp_meta_boxes['dashboard']['normal']['core'] = array_merge($widget, $normal);
});

function wholesale_render_sales_widget()
{
	$counted = array('pending', 'processing', 'on-hold', 'on_hold', 'completed');
	$recent = get_posts(array(
		'post_type' => 'order',
		'post_status' => $counted,
		'date_query' => array(array('after' => '30 days ago')),
		'posts_per_page' => -1,
		'fields' => 'ids',
	));
	$revenue = 0.0;
	$today = 0.0;
	$today_count = 0;
	$today_date = current_time('Y-m-d');
	foreach ($recent as $id) {
		$total = wholesale_order_total($id);
		$revenue += $total;
		if (get_the_date('Y-m-d', $id) === $today_date) {
			$today += $total;
			$today_count++;
		}
	}
	$in_production = count(get_posts(array('post_type' => 'order', 'post_status' => 'processing', 'posts_per_page' => -1, 'fields' => 'ids')));
	$attention = wholesale_attention_order_ids();
	$latest = get_posts(array('post_type' => 'order', 'post_status' => array_keys(wholesale_order_status_info()), 'posts_per_page' => 6));
	$money = static function ($value) {
		return '$' . number_format((float) $value, 2);
	};
	?>
	<div class="wholesale-kpis">
		<div><span>Today</span><strong><?php echo esc_html($money($today)); ?></strong><small><?php echo esc_html(sprintf(_n('%d order', '%d orders', $today_count, 'litsign'), $today_count)); ?></small></div>
		<div><span>Last 30 days</span><strong><?php echo esc_html($money($revenue)); ?></strong><small><?php echo esc_html(sprintf(_n('%d order', '%d orders', count($recent), 'litsign'), count($recent))); ?></small></div>
		<div><span>Average order</span><strong><?php echo esc_html($money($recent ? $revenue / count($recent) : 0)); ?></strong><small>last 30 days</small></div>
		<div><span>In production</span><strong><?php echo esc_html($in_production); ?></strong><small><a href="<?php echo esc_url(admin_url('edit.php?post_status=processing&post_type=order')); ?>">view</a></small></div>
	</div>

	<?php if ($attention) : ?>
		<h3 class="wholesale-widget-heading">Needs attention (<?php echo esc_html(count($attention)); ?>)</h3>
		<ul class="wholesale-widget-list">
			<?php foreach (array_slice($attention, 0, 6) as $id) : ?>
				<li>
					<a href="<?php echo esc_url(get_edit_post_link($id)); ?>">#<?php echo esc_html(wholesale_order_number($id)); ?></a>
					<?php echo wholesale_order_status_badge(get_post_status($id)); // Escaped in the helper. ?>
					<?php echo wholesale_payment_badge($id); // Escaped in the helper. ?>
					<span><?php echo esc_html($money(wholesale_order_total($id))); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h3 class="wholesale-widget-heading">Latest orders</h3>
	<ul class="wholesale-widget-list">
		<?php foreach ($latest as $order) :
			$billing = wholesale_decode_order_meta_array(get_post_meta($order->ID, 'billing_address', true)); ?>
			<li>
				<a href="<?php echo esc_url(get_edit_post_link($order->ID)); ?>">#<?php echo esc_html(wholesale_order_number($order->ID)); ?></a>
				<span><?php echo esc_html(trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? ''))); ?></span>
				<?php echo wholesale_order_status_badge($order->post_status); // Escaped in the helper. ?>
				<span><?php echo esc_html($money(wholesale_order_total($order->ID))); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=order')); ?>">All orders</a> <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=review_submission&post_status=pending')); ?>">Reviews to approve</a></p>
	<?php
}

// Count bubbles on the Orders and Reviews menus.
add_action('admin_menu', function () {
	global $menu;
	$attention = count(wholesale_attention_order_ids());
	$reviews = (int) wp_count_posts('review_submission')->pending;
	foreach ($menu as $index => $item) {
		if ('edit.php?post_type=order' === $item[2] && $attention) {
			$menu[$index][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $attention . '</span></span>';
		}
		if ('edit.php?post_type=review_submission' === $item[2] && $reviews) {
			$menu[$index][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $reviews . '</span></span>';
		}
	}
}, 999);

// ------------------------------------------------------------------
// Review moderation
// ------------------------------------------------------------------

add_filter('post_row_actions', function ($actions, $post) {
	if ('review_submission' !== $post->post_type || !current_user_can('publish_post', $post->ID)) {
		return $actions;
	}
	$target = 'publish' === $post->post_status ? 'pending' : 'publish';
	$url = wp_nonce_url(admin_url('admin-post.php?action=wholesale_review_status&post=' . $post->ID . '&status=' . $target), 'wholesale_review_status_' . $post->ID);
	$actions = array('wholesale_review' => '<a href="' . esc_url($url) . '"><strong>' . ('publish' === $target ? 'Approve &amp; show on site' : 'Unpublish') . '</strong></a>') + $actions;
	return $actions;
}, 10, 2);

add_action('admin_post_wholesale_review_status', function () {
	$post_id = absint($_GET['post'] ?? 0);
	$status = sanitize_key(wp_unslash($_GET['status'] ?? ''));
	check_admin_referer('wholesale_review_status_' . $post_id);
	if (!current_user_can('publish_post', $post_id) || 'review_submission' !== get_post_type($post_id) || !in_array($status, array('publish', 'pending'), true)) {
		wp_die('Not allowed.');
	}
	wp_update_post(array('ID' => $post_id, 'post_status' => $status));
	wp_safe_redirect(add_query_arg('review_updated', $status, wp_get_referer() ?: admin_url('edit.php?post_type=review_submission')));
	exit;
});

// Stars and a status badge instead of "5/5" in the review list.
add_action('manage_review_submission_posts_custom_column', function ($column, $post_id) {
	if ('review_rating' === $column) {
		echo '<span class="wholesale-review-stars" aria-hidden="true">' . str_repeat('&#9733;', absint(get_post_meta($post_id, '_review_rating', true))) . '</span> ';
	}
}, 5, 2);

add_filter('manage_review_submission_posts_columns', function ($columns) {
	$columns['review_status'] = 'On site';
	return $columns;
}, 20);

add_action('manage_review_submission_posts_custom_column', function ($column, $post_id) {
	if ('review_status' === $column) {
		echo 'publish' === get_post_status($post_id)
			? '<span class="order-badge order-badge--success">Shown</span>'
			: '<span class="order-badge order-badge--warn">Awaiting approval</span>';
	}
}, 10, 2);

// ------------------------------------------------------------------
// Admin styles
// ------------------------------------------------------------------

add_action('admin_head', function () {
	?>
	<style>
		.order-badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 1.6; white-space: nowrap; }
		.order-badge--info { background: #e5f4fb; color: #0f6f96; }
		.order-badge--success { background: #e7f7ef; color: #117a4c; }
		.order-badge--warn { background: #fff4e5; color: #8a4b00; }
		.order-badge--danger { background: #fdecec; color: #9b1c1c; }
		.order-badge--muted { background: #eef1f4; color: #5b6b7b; }
		.post-type-order .column-order_number { width: 130px; }
		.post-type-order .column-payment, .post-type-order .column-order_status { width: 130px; }
		.wholesale-fulfillment-warning { padding: 8px 10px; border-left: 4px solid #d63638; background: #fcf0f1; }
		.wholesale-kpis { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 12px; }
		.wholesale-kpis > div { padding: 10px 12px; border: 1px solid #e3e9ef; border-radius: 8px; background: #f8fafc; }
		.wholesale-kpis span { display: block; color: #5b6b7b; font-size: 12px; }
		.wholesale-kpis strong { display: block; font-size: 20px; line-height: 1.4; }
		.wholesale-kpis small { color: #5b6b7b; }
		.wholesale-widget-heading { margin: 14px 0 6px !important; font-size: 13px !important; }
		.wholesale-widget-list { margin: 0; }
		.wholesale-widget-list li { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid #f0f0f1; }
		.wholesale-widget-list li > span:last-child { margin-left: auto; font-weight: 600; }
		.wholesale-review-stars { color: #f5ad27; letter-spacing: 1px; }

		/* Order edit screen */
		.post-type-order.post-php .wrap > h1.wp-heading-inline, .post-type-order.post-php .wrap > .page-title-action { display: none; }
		.wo-header { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; margin: 12px 0 16px; padding: 16px 20px; background: #fff; border: 1px solid #dcdcde; border-radius: 8px; }
		.wo-header__main { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
		.wo-header__title { margin: 0 4px 0 0 !important; padding: 0 !important; font-size: 22px !important; line-height: 1.3 !important; }
		.wo-header__meta { flex-basis: 100%; order: 3; margin: 0; color: #50575e; }
		.wo-header__actions { display: flex; flex-wrap: wrap; gap: 6px; margin-left: auto; }
		.wo-header__actions .dashicons, .wo-details__edit .dashicons { font-size: 16px; width: 16px; height: 16px; vertical-align: text-top; }
		.post-type-order .postbox .inside { margin-top: 0; }
		.wo-muted { color: #787c82; }

		.wo-status__row { display: grid; grid-template-columns: 76px 1fr; align-items: center; gap: 8px; margin: 0 0 10px; }
		.wo-status__row select, .wo-status__row input { width: 100%; max-width: none; }
		.wo-status__tracking { margin: 0 -12px 10px; padding: 10px 12px 2px; background: #f6f7f7; border-block: 1px solid #f0f0f1; }
		.wo-status.is-shipping .wo-status__tracking { background: #eef7fc; border-color: #c5e3f3; }
		.wo-status__track { margin: -4px 0 10px 84px; }
		.wo-status__warning { margin: 0 0 10px; padding: 6px 8px; border-left: 3px solid #dba617; background: #fcf9e8; }
		.wo-status__notify { margin: 0 0 8px; }
		.wo-status__notify .description { margin: 4px 0 0 24px; }
		.wo-status__footer { display: flex; align-items: center; gap: 8px; margin: 12px -12px -12px; padding: 10px 12px; background: #f6f7f7; border-top: 1px solid #dcdcde; }
		.wo-status__footer .submitdelete { color: #b32d2e; margin-right: auto; }
		.wo-status__footer .spinner { float: none; margin: 0; }

		.wo-pay__total { display: flex; align-items: center; justify-content: space-between; margin-top: 0; }
		.wo-pay__amount { font-size: 20px; font-weight: 600; }
		.wo-pay__facts { display: grid; grid-template-columns: auto 1fr; gap: 4px 10px; margin: 0 0 10px; }
		.wo-pay__facts dt { color: #646970; }
		.wo-pay__facts dd { margin: 0; overflow-wrap: anywhere; }

		.wo-activity { margin: 0; list-style: none; }
		.wo-activity li { display: grid; gap: 2px; margin: 0; padding: 7px 0 7px 14px; border-left: 2px solid #dcdcde; position: relative; }
		.wo-activity li::before { content: ""; position: absolute; left: -5px; top: 12px; width: 8px; height: 8px; border-radius: 50%; background: #8c8f94; }
		.wo-activity li:first-child::before { background: #2271b1; }
		.wo-activity time { color: #787c82; font-size: 12px; }

		.wo-items { margin: 0; }
		.wo-item { display: flex; gap: 16px; margin: 0; padding: 16px 0; border-bottom: 1px solid #f0f0f1; }
		.wo-item:first-child { padding-top: 4px; }
		.wo-item__image { flex: 0 0 88px; width: 88px; height: 88px; object-fit: contain; border: 1px solid #f0f0f1; border-radius: 6px; background: #f6f7f7; }
		.wo-item__body { flex: 1; min-width: 0; }
		.wo-item__head { display: flex; justify-content: space-between; gap: 16px; }
		.wo-item__title { font-size: 14px; }
		.wo-item__link { display: inline-block; margin-left: 8px; font-size: 12px; }
		.wo-item__price { text-align: right; white-space: nowrap; }
		.wo-item__price span { display: block; color: #646970; }
		.wo-item__price strong { font-size: 14px; }
		.wo-specs { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 6px 16px; margin: 10px 0 0; }
		.wo-specs div { min-width: 0; }
		.wo-specs dt { color: #646970; font-size: 12px; }
		.wo-specs dd { margin: 0; overflow-wrap: anywhere; }
		.wo-cl { margin-top: 12px; }
		.wo-cl summary { cursor: pointer; color: #2271b1; font-weight: 600; }
		.wo-table-scroll { margin-top: 8px; overflow-x: auto; }
		.wo-cl-table th, .wo-cl-table td { white-space: nowrap; }
		.wo-cl-table .num { text-align: right; }
		.wo-totals { max-width: 320px; margin: 12px 0 0 auto; }
		.wo-totals div { display: flex; justify-content: space-between; gap: 16px; padding: 3px 0; }
		.wo-totals dt small { color: #787c82; }
		.wo-totals dd { margin: 0; }
		.wo-totals__grand { margin-top: 4px; padding-top: 8px !important; border-top: 1px solid #dcdcde; font-size: 15px; font-weight: 600; }

		.wo-details__bar { display: flex; justify-content: flex-end; align-items: center; gap: 12px; margin-bottom: 4px; }
		.wo-details__cancel, .wo-details[data-editing="1"] .wo-details__edit, .wo-details[data-editing="1"] .wo-view, .wo-details[data-editing="0"] .wo-edit { display: none; }
		.wo-details[data-editing="1"] .wo-details__cancel { display: inline; }
		.wo-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px 24px; }
		.wo-grid h3 { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #646970; }
		.wo-grid section > h3:not(:first-child) { margin-top: 16px; }
		.wo-grid p { margin-top: 0; }
		.wo-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
		.wo-field { margin: 0; }
		.wo-field--wide { grid-column: 1 / -1; }
		.wo-field label { display: block; margin-bottom: 2px; font-size: 12px; color: #50575e; }
		.wo-field input { width: 100%; }
		.wo-staff-note { margin-top: 16px; padding-top: 12px; border-top: 1px solid #f0f0f1; }
		.wo-staff-note textarea { display: block; width: 100%; margin-top: 6px; }
		@media (max-width: 1100px) { .wo-grid { grid-template-columns: 1fr; } }
		@media (max-width: 600px) {
			.wo-header__actions { margin-left: 0; }
			.wo-item { flex-direction: column; }
			.wo-item__head { flex-direction: column; gap: 4px; }
			.wo-item__price { text-align: left; }
		}
	</style>
	<?php
});
