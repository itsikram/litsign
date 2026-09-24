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
// Order edit screen: payment & fulfillment
// ------------------------------------------------------------------

add_action('add_meta_boxes_order', function () {
	add_meta_box('wholesale-fulfillment', 'Payment & fulfillment', 'wholesale_render_fulfillment_box', 'order', 'side', 'high');
});

function wholesale_render_fulfillment_box($post)
{
	$payment_status = get_post_meta($post->ID, '_payment_status', true);
	$txn = get_post_meta($post->ID, '_payment_txn_id', true);
	$carrier = get_post_meta($post->ID, '_tracking_carrier', true);
	wp_nonce_field('wholesale_fulfillment', 'wholesale_fulfillment_nonce');
	?>
	<div class="wholesale-fulfillment">
		<p><strong>Total:</strong> $<?php echo esc_html(number_format(wholesale_order_total($post->ID), 2)); ?> <?php echo wholesale_payment_badge($post->ID); // Escaped in the helper. ?></p>
		<?php if ($txn) : ?>
			<p>Converge transaction <code><?php echo esc_html($txn); ?></code><br>
				<?php echo get_post_meta($post->ID, '_payment_card_last4', true) ? 'Card ending ' . esc_html(get_post_meta($post->ID, '_payment_card_last4', true)) . '<br>' : ''; ?>
				<?php echo get_post_meta($post->ID, '_payment_approval_code', true) ? 'Approval code ' . esc_html(get_post_meta($post->ID, '_payment_approval_code', true)) : ''; ?></p>
		<?php endif; ?>
		<?php if ('needs_review' === $payment_status) : ?>
			<p class="wholesale-fulfillment-warning">Converge could not be reached to double-check this payment. Confirm the transaction in Converge before starting production.</p>
			<p><label><input type="checkbox" name="wholesale_payment_verified" value="1"> I confirmed this payment in Converge</label></p>
		<?php elseif ('manual' === $payment_status) : ?>
			<p class="wholesale-fulfillment-warning">No card payment was taken. Collect payment before production.</p>
			<p><label><input type="checkbox" name="wholesale_payment_verified" value="1"> Payment collected</label></p>
		<?php endif; ?>

		<hr>
		<p><label for="wholesale_tracking_carrier"><strong>Shipping carrier</strong></label><br>
			<select name="wholesale_tracking_carrier" id="wholesale_tracking_carrier">
				<?php foreach (array('' => 'Select…', 'ups' => 'UPS', 'fedex' => 'FedEx', 'usps' => 'USPS', 'freight' => 'Freight / other') as $value => $label) : ?>
					<option value="<?php echo esc_attr($value); ?>" <?php selected($carrier, $value); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select></p>
		<p><label for="wholesale_tracking_number"><strong>Tracking number</strong></label><br>
			<input type="text" class="widefat" name="wholesale_tracking_number" id="wholesale_tracking_number" value="<?php echo esc_attr(get_post_meta($post->ID, '_tracking_number', true)); ?>"></p>
		<p class="description">Mark the order <strong>Completed</strong> to email the customer a "shipped" notice with this tracking link.</p>
		<p><label><input type="checkbox" name="wholesale_skip_status_email" value="1"> Don't email the customer about this update</label></p>
		<p><a href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank">View order page &rarr;</a></p>
	</div>
	<?php
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
	if (!empty($_POST['wholesale_skip_status_email'])) {
		$GLOBALS['wholesale_skip_status_email'] = true;
	}
}, 5);

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
	</style>
	<?php
});
