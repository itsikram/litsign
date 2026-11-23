<?php
/**
 * The order amount after refunds, wherever an order's amount is shown or counted.
 *
 * wholesale_order_total() stays what the order was charged for: payment links, the gateways and
 * refund limits use it, so it never changes. Screens, emails and reports use
 * wholesale_order_net_amounts() instead, so a refund lowers the order's amount there:
 *  - wp-admin: order list Total column, order header, Payment box, order totals, dashboard widget;
 *  - customer: My orders, the order page, order and receipt emails, refund emails ("New order total");
 *  - Visitor Insights: order revenue and each visit's purchase amount;
 *  - Live chat and Site Hub read it when the plugins are updated too.
 * A refund counts when it is approved in the order's refund log (_refund_log). Payment status
 * "Refunded" (or order status Refunded) means the whole order was refunded.
 * Loaded from inc/order-partial-refund.php. Built 2026-11-17.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return array{total:float,refunded:float,net:float,has_refund:bool}
 */
function wholesale_order_net_amounts($post_id)
{
	$post_id = (int) $post_id;
	$total = function_exists('wholesale_order_total') ? round((float) wholesale_order_total($post_id), 2) : 0.0;
	$refunded = 0.0;
	if (function_exists('wholesale_order_refund_log')) {
		foreach (wholesale_order_refund_log($post_id) as $entry) {
			if ('approved' === ($entry['result'] ?? '')) {
				$refunded += (float) ($entry['amount'] ?? 0);
			}
		}
	}
	$payment = function_exists('wholesale_get_payment_status') ? wholesale_get_payment_status($post_id) : (string) get_post_meta($post_id, '_payment_status', true);
	if ('refunded' === $payment || 'refunded' === get_post_status($post_id)) {
		$refunded = max($refunded, $total);
	}
	$refunded = min(round($refunded, 2), $total);

	return array(
		'total' => $total,
		'refunded' => $refunded,
		'net' => round(max(0.0, $total - $refunded), 2),
		'has_refund' => $refunded >= 0.01,
	);
}

function wholesale_order_net_total($post_id)
{
	$amounts = wholesale_order_net_amounts($post_id);
	return $amounts['net'];
}

function wholesale_ont_money($value)
{
	return '$' . number_format((float) $value, 2);
}

/**
 * Email table rows after "Total" (Refunded, New order total), in the look of the order emails.
 * Empty when nothing was refunded.
 */
function wholesale_order_net_email_rows($post_id, $font = '')
{
	$amounts = wholesale_order_net_amounts($post_id);
	if (!$amounts['has_refund']) {
		return '';
	}
	$font = $font ? $font : "font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;";
	return '<tr><td style="' . $font . 'font-size:14px;color:#1a7f37;">Refunded</td><td align="right" style="' . $font . 'font-size:14px;color:#1a7f37;">&minus;' . esc_html(wholesale_ont_money($amounts['refunded'])) . '</td></tr>'
		. '<tr><td style="' . $font . 'font-size:16px;font-weight:700;color:#172027;padding-top:10px;">New order total</td><td align="right" style="' . $font . 'font-size:16px;font-weight:700;color:#172027;padding-top:10px;">' . esc_html(wholesale_ont_money($amounts['net'])) . '</td></tr>';
}

/**
 * Small text for one amount cell: "$77.94" plus "was $100.00 · $22.06 refunded" when refunded.
 */
function wholesale_order_net_cell_html($post_id)
{
	$amounts = wholesale_order_net_amounts($post_id);
	if (!$amounts['has_refund']) {
		return esc_html(wholesale_ont_money($amounts['total']));
	}
	return '<span class="ont-cell"><strong>' . esc_html(wholesale_ont_money($amounts['net'])) . '</strong>'
		. '<del>' . esc_html(wholesale_ont_money($amounts['total'])) . '</del>'
		. '<small>&minus;' . esc_html(wholesale_ont_money($amounts['refunded'])) . ' refunded</small></span>';
}

// ------------------------------------------------------------------
// Order list: the Total column shows the amount after refunds
// ------------------------------------------------------------------

add_action('admin_init', function () {
	$priority = has_action('manage_order_posts_custom_column', 'wholesale_order_admin_column_content');
	if (false === $priority) {
		return;
	}
	remove_action('manage_order_posts_custom_column', 'wholesale_order_admin_column_content', $priority);
	add_action('manage_order_posts_custom_column', function ($column, $post_id) {
		if ('order_total' === $column) {
			echo wholesale_order_net_cell_html($post_id); // Escaped in the helper.
			return;
		}
		wholesale_order_admin_column_content($column, $post_id);
	}, $priority, 2);
});

add_action('admin_head', function () {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || !in_array($screen->base, array('edit', 'post', 'dashboard'), true) || ('dashboard' !== $screen->base && 'order' !== $screen->post_type)) {
		return;
	}
	?>
	<style id="wholesale-order-net-total-css">
		.ont-cell { display: inline-flex; flex-direction: column; line-height: 1.35; }
		.ont-cell del { color: #8c8f94; font-size: 12px; }
		.ont-cell small { color: #1a7f37; font-size: 12px; white-space: nowrap; }
		.post-type-order .fixed .column-order_total { width: 104px; }
		.wo-header__meta .ont-was { color: #646970; font-weight: 400; }
		.wo-header__meta .ont-was del { color: #8c8f94; }
		.wo-pay__net { margin: -4px 0 10px; color: #50575e; font-size: 12px; }
		.wo-pay__net .ont-refunded { color: #1a7f37; }
		.wo-totals .ont-refund dt, .wo-totals .ont-refund dd { color: #1a7f37; }
		.wholesale-widget-list .ont-cell { display: inline; }
		.wholesale-widget-list .ont-cell del, .wholesale-widget-list .ont-cell small { margin-left: 4px; }
	</style>
	<?php
});

// ------------------------------------------------------------------
// Refund emails: "New order total" under the refund amount
// ------------------------------------------------------------------

// Refund log entries saved during this request, so a refund already in the log isn't counted twice.
add_action('added_post_meta', function ($meta_id, $post_id, $meta_key, $value) {
	if ('_refund_log' === $meta_key && is_array($value) && 'approved' === ($value['result'] ?? '')) {
		$GLOBALS['wholesale_ont_logged'][(int) $post_id][] = round((float) ($value['amount'] ?? 0), 2);
	}
}, 10, 4);

add_filter('wholesale_order_email_body', function ($body, $post_id, $subject) {
	if (0 !== strpos((string) $subject, 'Your refund for order #')) {
		return $body;
	}
	$amounts = wholesale_order_net_amounts($post_id);
	if ($amounts['total'] <= 0) {
		return $body;
	}
	// The refund flows email the customer before they add the refund to the log (the scheduled
	// refund in inc/order-partial-refund.php logs first), so take this refund off when it isn't there yet.
	$net = $amounts['net'];
	if (preg_match('/Refund amount:<\/strong>\s*\$([0-9,]+(?:\.[0-9]{1,2})?)/', (string) $body, $match)) {
		$amount = round((float) str_replace(',', '', $match[1]), 2);
		$logged = $GLOBALS['wholesale_ont_logged'][(int) $post_id] ?? array();
		if (!in_array($amount, $logged, true) && $net > 0) {
			$net = round(max(0.0, $net - $amount), 2);
		}
	}
	$refunded = round($amounts['total'] - $net, 2);
	$row = static function ($label, $value, $strong = false) {
		$style = $strong ? 'padding:4px 0;font-size:15px;font-weight:700;color:#0d2e4d;' : 'padding:4px 0;font-size:14px;color:#34506a;';
		return '<tr><td style="' . $style . '">' . esc_html($label) . '</td><td align="right" style="' . $style . '">' . esc_html($value) . '</td></tr>';
	};
	$table = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:12px 0 0;padding:10px 16px;background:#f5f8fb;border:1px solid #e3e9ef;border-radius:8px;">'
		. $row('Original order total', wholesale_ont_money($amounts['total']))
		. $row('Refunded in total', "\u{2212}" . wholesale_ont_money($refunded))
		. $row('New order total', wholesale_ont_money($net), true)
		. '</table>';

	// Right after the "Refund amount" box when there is one, else at the end.
	$position = strpos($body, 'Refund amount:');
	$close = false === $position ? false : strpos($body, '</p>', $position);
	if (false === $close) {
		return $body . $table;
	}
	$close += 4;
	return substr($body, 0, $close) . $table . substr($body, $close);
}, 10, 3);

// ------------------------------------------------------------------
// Visitor Insights: a visit's purchase amount follows the order's refunds
// ------------------------------------------------------------------

function wholesale_ont_queue_sync($post_id)
{
	$post_id = (int) $post_id;
	if (!$post_id || 'order' !== get_post_type($post_id)) {
		return;
	}
	$GLOBALS['wholesale_ont_queue'][$post_id] = true;
	if (!has_action('shutdown', 'wholesale_ont_run_queue')) {
		add_action('shutdown', 'wholesale_ont_run_queue', 5);
	}
}

foreach (array('added_post_meta', 'updated_post_meta', 'deleted_post_meta') as $wholesale_ont_hook) {
	add_action($wholesale_ont_hook, function ($meta_id, $post_id, $meta_key) {
		if (in_array($meta_key, array('_refund_log', '_payment_status'), true)) {
			wholesale_ont_queue_sync(is_array($post_id) ? 0 : $post_id);
		}
	}, 10, 3);
}
unset($wholesale_ont_hook);

add_action('transition_post_status', function ($new_status, $old_status, $post) {
	if ('order' === $post->post_type && $new_status !== $old_status && ('refunded' === $new_status || 'refunded' === $old_status)) {
		wholesale_ont_queue_sync($post->ID);
	}
}, 10, 3);

function wholesale_ont_run_queue()
{
	foreach (array_keys($GLOBALS['wholesale_ont_queue'] ?? array()) as $post_id) {
		wholesale_ont_sync_visit_revenue($post_id);
	}
	$GLOBALS['wholesale_ont_queue'] = array();
}

/**
 * Visitor Insights added the order total to the visit's revenue when the order was placed
 * (wholesale_vi_record_order). Moves it to the amount after refunds, by the difference only,
 * so other orders on the same visit are untouched. _vi_revenue_counted remembers what is counted.
 */
function wholesale_ont_sync_visit_revenue($post_id)
{
	global $wpdb;
	$session = (int) get_post_meta($post_id, '_vi_session_id', true);
	if (!$session || !function_exists('wholesale_vi_table')) {
		return;
	}
	$amounts = wholesale_order_net_amounts($post_id);
	$counted = get_post_meta($post_id, '_vi_revenue_counted', true);
	$counted = '' === $counted ? $amounts['total'] : (float) $counted;
	$change = round($amounts['net'] - $counted, 2);
	if (abs($change) < 0.005) {
		return;
	}
	$table = wholesale_vi_table('sessions');
	$updated = $wpdb->query($wpdb->prepare("UPDATE {$table} SET revenue = GREATEST(0, revenue + %f) WHERE id = %d", $change, $session)); // phpcs:ignore WordPress.DB.PreparedSQL -- table name from the helper.
	if (false !== $updated) {
		update_post_meta($post_id, '_vi_revenue_counted', $amounts['net']);
	}
}
