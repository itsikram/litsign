<?php
/**
 * Order creation helpers shared by cart checkout and payment tickets.
 * Card payments live in inc/payments.php.
 *
 * @package litsign
 */

function wholesale_generate_order_id()
{
    do {
        $order_id = 'order_' . strtoupper(wp_generate_password(10, false, false));
        $existing_order = get_posts(array(
            'post_type' => 'order',
            'post_status' => 'any',
            'meta_key' => 'order_id',
            'meta_value' => $order_id,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));
    } while (!empty($existing_order));

    return $order_id;
}

/**
 * Insert an order post. All data arguments are JSON strings in the same shape
 * the admin order meta boxes and order emails read.
 *
 * @return int|false The new order post ID.
 */
function wholesale_insert_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $extra_meta = array())
{
   $order_id = wholesale_generate_order_id();
   $user_id = is_user_logged_in() ? get_current_user_id() : null;

   $date = new DateTime();
   $formatted_date = $date->format('D M. j');

   $billing_data_array = wholesale_decode_order_meta_array($billing_data);

   $b_first_name = isset($billing_data_array['billing_fname']) ? sanitize_text_field((string) $billing_data_array['billing_fname']) : '';
   $b_last_name = isset($billing_data_array['billing_lname']) ? sanitize_text_field((string) $billing_data_array['billing_lname']) : '';
   $order_slug = 'Order -' . trim($b_first_name . ' ' . $b_last_name) . ' #' . $order_id;

   $new_order = wp_insert_post(array(
       'post_type' => 'order',
       'post_title' => sanitize_text_field($order_slug),
       'post_status' => 'processing',
       'meta_input' => array_merge(array(
           'product_json' => $product_data,
           'shipping_address' => $shipping_data,
           'billing_address' => $billing_data,
           'product_cost' => $order_cost,
           'order_comment' => wp_strip_all_tags($order_comment),
           'order_time' => sanitize_text_field($formatted_date),
           'estimate_delivery_time' => sanitize_text_field($estimate_delivery_time),
           'order_id' => sanitize_text_field($order_id),
           'user_id' => $user_id,
       ), $extra_meta),
   ), true);

   if (is_wp_error($new_order) || !$new_order) {
       return false;
   }

   return $new_order;
}

function place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart)
{
   $new_order = wholesale_insert_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time);
   if (!$new_order) {
       return false;
   }

   $cart->empty();
   wholesale_send_new_order_admin_email($new_order);

   return $new_order;
}

/**
 * Price of a channel letter builder design from the product's rate table
 * (see wholesale_cl_design_quote() in inc/pricing.php). Returns 0 when the design
 * can't be priced.
 */
function wholesale_cl_design_total($design, $product_id)
{
    $total = wholesale_cl_design_quote($product_id, is_array($design) ? $design : array());
    return is_wp_error($total) ? 0.0 : (float) $total;
}

/**
 * Human-readable lines describing a builder design, e.g.
 * 'Letters "PHO" — 12" H × 30" W, Font: Arial, Face: Red'.
 *
 * @return string[]
 */
function wholesale_cl_design_summary($design)
{
    $types = array('Text' => 'Letters', 'Circle' => 'Oval', 'Star' => 'Starburst', 'RegularPolygon' => 'Triangle', 'Line' => 'Arrow', 'Rect' => 'Rectangle');
    $elements = isset($design['elements']) && is_array($design['elements']) ? $design['elements'] : array();
    $extras = isset($design['extras']) && is_array($design['extras']) ? $design['extras'] : array();
    $lines = array();

    foreach ($elements as $element) {
        if (!is_array($element)) {
            continue;
        }
        $type = isset($element['type']) ? (string) $element['type'] : '';
        $label = $types[$type] ?? ($type ?: 'Element');
        if (isset($element['text']) && '' !== trim((string) $element['text'])) {
            $label .= ' "' . trim((string) $element['text']) . '"';
        }

        $parts = array();
        if (!empty($element['height']) || !empty($element['width'])) {
            $parts[] = round((float) ($element['height'] ?? 0), 1) . '" H × ' . round((float) ($element['width'] ?? 0), 1) . '" W';
        }
        foreach (array('font' => 'Font', 'faceColor' => 'Face', 'returnColor' => 'Return', 'trimcapColor' => 'Trimcap') as $key => $name) {
            if (!empty($element[$key]['title'])) {
                $parts[] = $name . ': ' . $element[$key]['title'];
            }
        }
        $lines[] = sanitize_text_field($label . ($parts ? ' — ' . implode(', ', $parts) : ''));
    }

    foreach (array('powerSupply' => 'Power supply', 'lit' => 'Lighting', 'cable' => 'Cable') as $key => $name) {
        if (!empty($extras[$key]['qty']) && !empty($extras[$key]['value'])) {
            $lines[] = sanitize_text_field($name . ': ' . $extras[$key]['value']);
        }
    }

    return array_slice($lines, 0, 40);
}

/**
 * Customer-facing specs for a cart/order line: label => escaped HTML value.
 * Internal fields are skipped; design and artwork files become links.
 *
 * @param object|array $item Cart item (object) or decoded order item (array).
 * @return array<string,string>
 */
function wholesale_item_specs($item)
{
    $item = (array) $item;
    $details = isset($item['product_details']) ? (array) $item['product_details'] : array();
    $skip = array('Product Id', 'Shipping Type', 'Turnaround Option');
    $specs = array();

    if (!empty($item['job_name'])) {
        $specs['Job name'] = esc_html($item['job_name']);
    }
    foreach ($details as $label => $value) {
        if (in_array($label, $skip, true) || null === $value || '' === $value || !is_scalar($value)) {
            continue;
        }
        $specs[$label] = wholesale_format_order_detail_value($label, $value);
    }
    if (!empty($details['Turnaround Option']) && 'Same Day' === $details['Turnaround Option']) {
        $specs['Turnaround'] = 'Same-day production';
    }

    return $specs;
}

/**
 * Customer-facing order number: "order_SN6LTHF5IA" -> "SN6LTHF5IA".
 */
function wholesale_order_number($order_post_id)
{
    $order_id = (string) get_post_meta($order_post_id, 'order_id', true);
    return strtoupper(preg_replace('/^order_/i', '', $order_id ?: (string) $order_post_id));
}

/**
 * Branded HTML confirmation email for the customer.
 */
function wholesale_customer_order_email_html($post_id)
{
    $items = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_json', true));
    $cost = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_cost', true));
    $billing = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
    $ship_by = get_post_meta($post_id, 'estimate_delivery_time', true);
    $number = wholesale_order_number($post_id);
    $money = static function ($value) {
        return '$' . number_format((float) $value, 2);
    };
    $font = "font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;";

    $rows = '';
    foreach ($items as $item) {
        $specs = wholesale_item_specs($item);
        unset($specs['Design Url'], $specs['My Artwork']);
        $spec_text = array();
        foreach (array_slice($specs, 0, 12, true) as $label => $value) {
            $spec_text[] = esc_html($label) . ': ' . wp_strip_all_tags($value);
        }
        $rows .= '<tr>'
            . '<td style="padding:12px 0;border-bottom:1px solid #e3e9ef;' . $font . 'font-size:14px;color:#172027;"><strong>' . esc_html($item['product_title'] ?? '') . '</strong>'
            . '<br><span style="color:#5b6b7b;font-size:13px;">Qty ' . esc_html($item['product_quantity'] ?? 1) . ($spec_text ? ' &middot; ' . implode(' &middot; ', $spec_text) : '') . '</span></td>'
            . '<td align="right" style="padding:12px 0;border-bottom:1px solid #e3e9ef;' . $font . 'font-size:14px;color:#172027;white-space:nowrap;">' . esc_html($money($item['product_subtotal'] ?? 0)) . '</td>'
            . '</tr>';
    }

    $total_row = static function ($label, $value, $strong = false) use ($font) {
        $style = $font . ($strong ? 'font-size:16px;font-weight:700;color:#172027;padding-top:10px;' : 'font-size:14px;color:#5b6b7b;');
        return '<tr><td style="' . $style . '">' . esc_html($label) . '</td><td align="right" style="' . $style . '">' . esc_html($value) . '</td></tr>';
    };

    $payment_line = wholesale_payment_customer_text($post_id, 'Our team will contact you to arrange payment before production starts.');

    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;padding:0;background:#f5f8fb;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f8fb;padding:24px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e3e9ef;border-radius:12px;">'
        . '<tr><td style="padding:24px 28px;border-bottom:4px solid #1fa8de;' . $font . 'font-size:20px;font-weight:700;color:#0d2e4d;">Storefront Sign Online</td></tr>'
        . '<tr><td style="padding:28px;' . $font . 'color:#172027;">'
        . '<h1 style="margin:0 0 8px;font-size:22px;">Thank you' . (!empty($billing['billing_fname']) ? ', ' . esc_html($billing['billing_fname']) : '') . '! Your order is confirmed.</h1>'
        . '<p style="margin:0 0 20px;font-size:15px;color:#5b6b7b;">Order <strong style="color:#172027;">#' . esc_html($number) . '</strong>' . ($ship_by ? ' &middot; Estimated to ship by <strong style="color:#172027;">' . esc_html($ship_by) . '</strong>' : '') . '</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rows . '</table>'
        . '<table role="presentation" width="100%" cellpadding="4" cellspacing="0" style="margin-top:12px;">'
        . $total_row('Subtotal', $money($cost['sub_total'] ?? 0))
        . $total_row('Shipping' . (!empty($cost['shipping_method']) ? ' - ' . $cost['shipping_method'] : ''), $money($cost['shipping_cost'] ?? 0))
        . $total_row('Tax', $money($cost['tax'] ?? 0))
        . $total_row('Total', $money($cost['grand_total'] ?? 0), true)
        . '</table>'
        . '<p style="margin:20px 0 0;padding:14px 16px;background:#eef7fc;border-radius:8px;font-size:14px;color:#0d2e4d;"><strong>Payment:</strong> ' . esc_html($payment_line) . '</p>'
        . '<h2 style="margin:28px 0 10px;font-size:16px;">What happens next</h2>'
        . '<ol style="margin:0;padding-left:20px;font-size:14px;line-height:1.6;color:#34506a;">'
        . '<li>We review your order and contact you if anything needs a closer look.</li>'
        . '<li>We build your sign and test it before it ships.</li>'
        . '<li>You receive tracking details by email when it ships.</li>'
        . '</ol>'
        . '<p style="margin:20px 0 0;"><a href="' . esc_url(wholesale_track_order_url($post_id)) . '" style="display:inline-block;padding:12px 22px;background:#1fa8de;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Track your order</a></p>'
        . '<p style="margin:24px 0 0;font-size:14px;color:#5b6b7b;">Questions? Call <a href="tel:+18664362101" style="color:#1287b5;">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PST) or reply to this email with your order number.</p>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 28px;border-top:1px solid #e3e9ef;' . $font . 'font-size:12px;color:#8a97a5;">Storefront Sign Online &middot; 707 S. Grady Way, Suite 600, Renton, WA 98057</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/**
 * Order statuses as customers and staff see them.
 *
 * @return array<string,array{label:string,tone:string,step:int,note:string}>
 */
function wholesale_order_status_info()
{
    return array(
        'pending' => array('label' => 'Pending review', 'tone' => 'info', 'step' => 1, 'note' => 'We received your order and are reviewing the details.'),
        'processing' => array('label' => 'In production', 'tone' => 'info', 'step' => 2, 'note' => 'Your sign is being made.'),
        'on-hold' => array('label' => 'On hold', 'tone' => 'warn', 'step' => 1, 'note' => 'We need something from you. Our team will contact you, or call 866-436-2101.'),
        'on_hold' => array('label' => 'On hold', 'tone' => 'warn', 'step' => 1, 'note' => 'We need something from you. Our team will contact you, or call 866-436-2101.'),
        'completed' => array('label' => 'Shipped', 'tone' => 'success', 'step' => 3, 'note' => 'Your sign is on its way.'),
        'cancelled' => array('label' => 'Cancelled', 'tone' => 'muted', 'step' => 0, 'note' => 'This order was cancelled.'),
        'refunded' => array('label' => 'Refunded', 'tone' => 'muted', 'step' => 0, 'note' => 'This order was refunded.'),
        'failed' => array('label' => 'Payment failed', 'tone' => 'danger', 'step' => 0, 'note' => 'Payment did not go through. Please contact us.'),
    );
}

/**
 * How an order's payment is described to the customer. $unpaid is shown while payment is still owed.
 */
function wholesale_payment_customer_text($post_id, $unpaid = 'To be arranged with our team')
{
    $status = wholesale_get_payment_status($post_id);
    $last4 = get_post_meta($post_id, '_payment_card_last4', true);
    if (in_array($status, array('paid', 'needs_review'), true)) {
        return 'Paid by card' . ($last4 ? ' ending ' . $last4 : '');
    }
    if ('paid_offline' === $status) {
        return 'Paid';
    }
    if ('refunded' === $status) {
        return 'Refunded';
    }
    return $unpaid;
}

function wholesale_order_status_badge($status)
{
    $info = wholesale_order_status_info();
    $item = isset($info[$status]) ? $info[$status] : array('label' => ucwords(str_replace(array('-', '_'), ' ', (string) $status)), 'tone' => 'muted');
    return '<span class="order-badge order-badge--' . esc_attr($item['tone']) . '">' . esc_html($item['label']) . '</span>';
}

/**
 * Tracking link for common carriers.
 */
function wholesale_tracking_url($carrier, $number)
{
    $number = rawurlencode(trim((string) $number));
    switch (strtolower((string) $carrier)) {
        case 'ups':
            return 'https://www.ups.com/track?tracknum=' . $number;
        case 'fedex':
            return 'https://www.fedex.com/fedextrack/?trknbr=' . $number;
        case 'usps':
            return 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $number;
        default:
            return '';
    }
}

/**
 * Public order tracking page URL, optionally prefilled with an order number.
 */
function wholesale_track_order_url($order_post_id = 0)
{
    $url = home_url('/track-order/');
    return $order_post_id ? add_query_arg('order', wholesale_order_number($order_post_id), $url) : $url;
}

/**
 * Normalize what a customer types as an order number: "#sn6lthf5ia", "order_SN6LTHF5IA" -> "SN6LTHF5IA".
 */
function wholesale_normalize_order_number($input)
{
    $number = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));
    if (strlen($number) > 10 && 0 === strpos($number, 'ORDER')) {
        $number = substr($number, 5);
    }
    return substr($number, 0, 32);
}

/**
 * Find an order for the public tracking page. The email must match the order's billing
 * (or shipping) email, so an order number alone reveals nothing.
 *
 * @return int Order post ID, or 0 when there is no match.
 */
function wholesale_find_order_for_tracking($number, $email)
{
    $number = wholesale_normalize_order_number($number);
    $email = strtolower(trim((string) $email));
    if ('' === $number || !is_email($email)) {
        return 0;
    }

    $ids = get_posts(array(
        'post_type' => 'order',
        'post_status' => array_keys(wholesale_order_status_info()),
        'meta_key' => 'order_id',
        'meta_value' => 'order_' . $number,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ));
    if (!$ids) {
        return 0;
    }

    $billing = wholesale_decode_order_meta_array(get_post_meta($ids[0], 'billing_address', true));
    $shipping = wholesale_decode_order_meta_array(get_post_meta($ids[0], 'shipping_address', true));
    foreach (array($billing['billing_email'] ?? '', $shipping['shipping_email'] ?? '') as $order_email) {
        if ('' !== $order_email && hash_equals(strtolower(trim((string) $order_email)), $email)) {
            return (int) $ids[0];
        }
    }
    return 0;
}

/**
 * Customer-facing history of an order, oldest first: placed, then each status change.
 *
 * @return array<int,array{time:int,status:string,label:string,note:string}>
 */
function wholesale_order_timeline($order_post_id)
{
    $info = wholesale_order_status_info();
    $events = array(array(
        'time' => (int) get_post_time('U', true, $order_post_id),
        'status' => 'placed',
        'label' => 'Order placed',
        'note' => 'We received your order.',
    ));

    $log = get_post_meta($order_post_id, '_status_log');
    usort($log, static function ($a, $b) {
        return ((int) ($a['time'] ?? 0)) <=> ((int) ($b['time'] ?? 0));
    });
    foreach ($log as $entry) {
        $to = $entry['to'] ?? '';
        if (!isset($info[$to])) {
            continue;
        }
        $events[] = array(
            'time' => (int) ($entry['time'] ?? 0),
            'status' => $to,
            'label' => $info[$to]['label'],
            'note' => $info[$to]['note'],
        );
    }
    return $events;
}

// Browser tab title for an order page: "Order #SN6LTHF5IA" instead of the internal post title.
add_filter('pre_get_document_title', function ($title) {
    return is_singular('order') ? 'Order #' . wholesale_order_number(get_queried_object_id()) . ' | ' . get_bloginfo('name') : $title;
}, 99);
