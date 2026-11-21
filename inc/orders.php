<?php
/**
 * Order creation helpers shared by cart checkout and payment tickets.
 * Card payments live in inc/payments.php.
 *
 * @package litsign
 */

function retails_generate_order_id()
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
function retails_insert_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $extra_meta = array())
{
   $order_id = retails_generate_order_id();
   $user_id = is_user_logged_in() ? get_current_user_id() : null;

   $date = new DateTime();
   $formatted_date = $date->format('D M. j');

   $billing_data_array = retails_decode_order_meta_array($billing_data);

   $b_first_name = isset($billing_data_array['billing_fname']) ? sanitize_text_field((string) $billing_data_array['billing_fname']) : '';
   $b_last_name = isset($billing_data_array['billing_lname']) ? sanitize_text_field((string) $billing_data_array['billing_lname']) : '';
   $order_slug = 'Order -' . trim($b_first_name . ' ' . $b_last_name) . ' #' . $order_id;

   $new_order = wp_insert_post(array(
       'post_type' => 'order',
       'post_title' => sanitize_text_field($order_slug),
       // Review in progress until the B2Sign order is linked; the tracking sync plugin then moves it to In production.
       'post_status' => 'in-review',
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

// New orders start in "Review in progress" until the B2Sign order is linked (2026-11-12). Registered like the other
// order statuses (functions.php). Not core "pending": that one hides the order page from customers, gives a new order
// no slug and resets the placed date when the status changes.
add_action('init', function () {
   register_post_status('in-review', array(
       'label' => _x('Review in progress', 'post'),
       'public' => true,
       'exclude_from_search' => false,
       'show_in_admin_all_list' => true,
       'show_in_admin_status_list' => true,
       'label_count' => _n_noop('Review in progress <span class="count">(%s)</span>', 'Review in progress <span class="count">(%s)</span>'),
   ));
});

function place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart)
{
   $new_order = retails_insert_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time);
   if (!$new_order) {
       return false;
   }

   $cart->empty();
   retails_send_new_order_admin_email($new_order);

   return $new_order;
}

/**
 * Price of a channel letter builder design from the product's rate table
 * (see retails_cl_design_quote() in inc/pricing.php). Returns 0 when the design
 * can't be priced.
 */
function retails_cl_design_total($design, $product_id)
{
    $total = retails_cl_design_quote($product_id, is_array($design) ? $design : array());
    return is_wp_error($total) ? 0.0 : (float) $total;
}

/**
 * Human-readable lines describing a builder design, e.g.
 * 'Letters "PHO" — 30" W × 12" H, Font: Arial, Face: Red'.
 *
 * @return string[]
 */
function retails_cl_design_summary($design)
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
            $parts[] = round((float) ($element['width'] ?? 0), 1) . '" W × ' . round((float) ($element['height'] ?? 0), 1) . '" H';
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
 * Order details with Width listed right before Height, so every size reads
 * width × height like the product page size box (and B2Sign). Orders placed
 * before 2026-10-29 stored Height first.
 *
 * @param array $details product_details of a cart/order line.
 * @return array
 */
function retails_width_first($details)
{
    $details = (array) $details;
    if (!array_key_exists('Width', $details) || !array_key_exists('Height', $details)) {
        return $details;
    }
    $ordered = array();
    foreach ($details as $label => $value) {
        if ('Width' === $label) {
            continue;
        }
        if ('Height' === $label) {
            $ordered['Width'] = $details['Width'];
        }
        $ordered[$label] = $value;
    }
    return $ordered;
}

/**
 * Customer-facing specs for a cart/order line: label => escaped HTML value.
 * Internal fields are skipped; design and artwork files become links.
 *
 * @param object|array $item Cart item (object) or decoded order item (array).
 * @return array<string,string>
 */
function retails_item_specs($item)
{
    $item = (array) $item;
    $details = isset($item['product_details']) ? retails_width_first($item['product_details']) : array();
    $skip = array('Product Id', 'Shipping Type', 'Turnaround Option');
    $specs = array();

    if (!empty($item['job_name'])) {
        $specs['Job name'] = esc_html($item['job_name']);
    }
    foreach ($details as $label => $value) {
        if (in_array($label, $skip, true) || null === $value || '' === $value || !is_scalar($value)) {
            continue;
        }
        $specs[$label] = retails_format_order_detail_value($label, $value);
    }
    if (!empty($details['Turnaround Option']) && 'Same Day' === $details['Turnaround Option']) {
        $specs['Turnaround'] = 'Same-day production';
    }

    // Size first, so the spec grid always puts Width in the left cell and
    // Height in the right one, even when a job name is set (owner rule 2026-10-29).
    return array_intersect_key($specs, array('Width' => 1, 'Height' => 1)) + $specs;
}

/**
 * Customer-facing order number: "order_SN6LTHF5IA" -> "SN6LTHF5IA".
 */
function retails_order_number($order_post_id)
{
    $order_id = (string) get_post_meta($order_post_id, 'order_id', true);
    return strtoupper(preg_replace('/^order_/i', '', $order_id ?: (string) $order_post_id));
}

/**
 * Branded HTML confirmation email for the customer.
 */
function retails_customer_order_email_html($post_id)
{
    $items = retails_decode_order_meta_array(get_post_meta($post_id, 'product_json', true));
    $cost = retails_decode_order_meta_array(get_post_meta($post_id, 'product_cost', true));
    $billing = retails_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
    $ship_by = get_post_meta($post_id, 'estimate_delivery_time', true);
    $number = retails_order_number($post_id);
    $money = static function ($value) {
        return '$' . number_format((float) $value, 2);
    };
    $font = "font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;";
    $noun = retails_items_noun($items);
    $build_line = 'sign' === $noun ? 'We build your sign and test it before it ships.' : "We make your {$noun} and check it before it ships.";

    $rows = '';
    foreach ($items as $item) {
        $specs = retails_item_specs($item);
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

    $payment_line = retails_payment_customer_text($post_id, 'Our team will contact you to arrange payment before production starts.');
    // Ordered without artwork while signed in: it can be uploaded from the order page (inc/order-artwork.php).
    $artwork_line = get_post_meta($post_id, 'user_id', true) && function_exists('retails_order_artwork_missing_lines') && retails_order_artwork_missing_lines($post_id)
        ? '<p style="margin:12px 0 0;padding:14px 16px;background:#fffaf0;border:1px solid #f0c36d;border-radius:8px;font-size:14px;color:#5c3d00;"><strong>No artwork yet?</strong> Sign in and upload it from <a href="' . esc_url(get_permalink($post_id)) . '" style="color:#8a5a00;font-weight:700;">your order page</a>, so we can start on your order.</p>'
        : '';

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
        . (function_exists('retails_order_net_email_rows') ? retails_order_net_email_rows($post_id, $font) : '') // After refunds (inc/order-net-total.php).
        . '</table>'
        . '<p style="margin:20px 0 0;padding:14px 16px;background:#eef7fc;border-radius:8px;font-size:14px;color:#0d2e4d;"><strong>Payment:</strong> ' . esc_html($payment_line) . '</p>'
        . $artwork_line
        . '<h2 style="margin:28px 0 10px;font-size:16px;">What happens next</h2>'
        . '<ol style="margin:0;padding-left:20px;font-size:14px;line-height:1.6;color:#34506a;">'
        . '<li>We review your order and contact you if anything needs a closer look.</li>'
        . '<li>' . esc_html($build_line) . '</li>'
        . '<li>You receive tracking details by email when it ships.</li>'
        . '</ol>'
        . '<p style="margin:20px 0 0;"><a href="' . esc_url(retails_track_order_url($post_id)) . '" style="display:inline-block;padding:12px 22px;background:#1fa8de;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Track your order</a></p>'
        . '<p style="margin:24px 0 0;font-size:14px;color:#5b6b7b;">Questions? Call <a href="tel:+18664362101" style="color:#1287b5;">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PT) or reply to this email with your order number.</p>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 28px;border-top:1px solid #e3e9ef;' . $font . 'font-size:12px;color:#8a97a5;">Storefront Sign Online &middot; 2601 NE 12th St, Renton, WA 98056</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/**
 * Order statuses as customers and staff see them.
 *
 * @param int $order_post_id Optional order, so notes can name what was ordered.
 * @return array<string,array{label:string,tone:string,step:int,note:string}>
 */
function retails_order_status_info($order_post_id = 0)
{
    // Notes name what was ordered ("Your banner is being made."); without an order it is just "order".
    $noun = $order_post_id ? retails_order_noun($order_post_id) : 'order';
    return array(
        'in-review' => array('label' => 'Review in progress', 'tone' => 'info', 'step' => 1, 'note' => 'We received your order and are checking the details before production.'),
        // Legacy duplicate of "in-review" (core WordPress status: hides the order page from customers).
        'pending' => array('label' => 'Review in progress', 'tone' => 'info', 'step' => 1, 'note' => 'We received your order and are checking the details before production.'),
        'processing' => array('label' => 'In production', 'tone' => 'info', 'step' => 2, 'note' => "Your {$noun} is being made."),
        'finishing' => array('label' => 'Finishing', 'tone' => 'info', 'step' => 2, 'note' => "Your {$noun} is printed and is being trimmed and packed for shipping."),
        'ready-for-pickup' => array('label' => 'Ready for pickup', 'tone' => 'success', 'step' => 2, 'note' => "Your {$noun} is ready. Our team will contact you about pickup, or call 866-436-2101."),
        'on-hold' => array('label' => 'On hold', 'tone' => 'warn', 'step' => 1, 'note' => 'We need something from you. Our team will contact you, or call 866-436-2101.'),
        'on_hold' => array('label' => 'On hold', 'tone' => 'warn', 'step' => 1, 'note' => 'We need something from you. Our team will contact you, or call 866-436-2101.'),
        'completed' => array('label' => 'Shipped', 'tone' => 'success', 'step' => 3, 'note' => "Your {$noun} has shipped. The carrier has the package."),
        'in-transit' => array('label' => 'In transit', 'tone' => 'info', 'step' => 3, 'note' => "Your {$noun} is on its way."),
        'out-for-delivery' => array('label' => 'Out for delivery', 'tone' => 'info', 'step' => 3, 'note' => "Your {$noun} is out for delivery today."),
        'delivered' => array('label' => 'Delivered', 'tone' => 'success', 'step' => 4, 'note' => "Your {$noun} was delivered."),
        'delivery-issue' => array('label' => 'Delivery issue', 'tone' => 'warn', 'step' => 3, 'note' => 'The carrier reported a problem or delay. We are following up; call 866-436-2101 with questions.'),
        'returned' => array('label' => 'Returned to sender', 'tone' => 'warn', 'step' => 3, 'note' => 'The carrier is returning the package. Our team will contact you, or call 866-436-2101.'),
        'cancelled' => array('label' => 'Cancelled', 'tone' => 'muted', 'step' => 0, 'note' => 'This order was cancelled.'),
        'refunded' => array('label' => 'Refunded', 'tone' => 'muted', 'step' => 0, 'note' => 'This order was refunded.'),
        'failed' => array('label' => 'Payment failed', 'tone' => 'danger', 'step' => 0, 'note' => 'Payment did not go through. Please contact us.'),
    );
}

/**
 * How an order's payment is described to the customer. $unpaid is shown while payment is still owed.
 */
function retails_payment_customer_text($post_id, $unpaid = 'To be arranged with our team')
{
    $status = retails_get_payment_status($post_id);
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
    if ('partially_refunded' === $status) {
        return 'Paid by card' . ($last4 ? ' ending ' . $last4 : '') . ' (partly refunded)';
    }
    return $unpaid;
}

/**
 * Statuses of an order that has left the printer: Shipped and the carrier stages after it. Reviews, the shipped
 * email and tracking display treat all of them like "completed" (Shipped).
 *
 * @return string[]
 */
function retails_order_shipped_statuses()
{
    return array('completed', 'in-transit', 'out-for-delivery', 'delivered', 'delivery-issue', 'returned');
}

function retails_order_is_shipped($status)
{
    return in_array((string) $status, retails_order_shipped_statuses(), true);
}

/**
 * Shipped but not delivered yet: customer pages lead with tracking and hold review prompts until it arrives.
 */
function retails_order_on_the_way($status)
{
    return in_array((string) $status, array('in-transit', 'out-for-delivery', 'delivery-issue', 'returned'), true);
}

/**
 * Status pill. With an order id, the tracking sync plugin may add detail such as "1/2 delivered".
 */
function retails_order_status_badge($status, $post_id = 0)
{
    $info = retails_order_status_info();
    $item = isset($info[$status]) ? $info[$status] : array('label' => ucwords(str_replace(array('-', '_'), ' ', (string) $status)), 'tone' => 'muted');
    if ($post_id) {
        $item = apply_filters('retails_order_status_badge_item', $item, $status, (int) $post_id);
    }
    return '<span class="order-badge order-badge--' . esc_attr($item['tone']) . '">' . esc_html($item['label']) . '</span>';
}

/**
 * Tracking link for common carriers.
 */
function retails_tracking_url($carrier, $number)
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
function retails_track_order_url($order_post_id = 0)
{
    $url = home_url('/track-order/');
    return $order_post_id ? add_query_arg('order', retails_order_number($order_post_id), $url) : $url;
}

/**
 * Normalize what a customer types as an order number: "#sn6lthf5ia", "order_SN6LTHF5IA" -> "SN6LTHF5IA".
 */
function retails_normalize_order_number($input)
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
function retails_find_order_for_tracking($number, $email)
{
    $number = retails_normalize_order_number($number);
    $email = strtolower(trim((string) $email));
    if ('' === $number || !is_email($email)) {
        return 0;
    }

    $ids = get_posts(array(
        'post_type' => 'order',
        'post_status' => array_keys(retails_order_status_info()),
        'meta_key' => 'order_id',
        'meta_value' => 'order_' . $number,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ));
    if (!$ids) {
        return 0;
    }

    $billing = retails_decode_order_meta_array(get_post_meta($ids[0], 'billing_address', true));
    $shipping = retails_decode_order_meta_array(get_post_meta($ids[0], 'shipping_address', true));
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
function retails_order_timeline($order_post_id)
{
    $info = retails_order_status_info($order_post_id);
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

/**
 * Every stage of an order as steps, for the step-by-step tracker on the order and tracking pages: Order received,
 * In production, Finishing, Shipped, In transit, Out for delivery, Delivered (pickup orders end at Ready for pickup).
 * On hold, Delivery issue and Returned are shown on the step where they happened. Dates come from the status log;
 * the tracking sync plugin adds the delivered date and delivery estimate (filter retails_order_steps).
 *
 * @return array<string,array{label:string,state:string,here:bool,date:int,note:string,warn:bool}> Empty for closed orders.
 */
function retails_order_steps($order_post_id)
{
    $status = (string) get_post_status($order_post_id);
    if (in_array($status, array('cancelled', 'refunded', 'failed'), true)) {
        return array();
    }
    $labels = array('received' => 'Order received', 'production' => 'In production', 'finishing' => 'Finishing');
    $labels += 'ready-for-pickup' === $status
        ? array('pickup' => 'Ready for pickup')
        : array('shipped' => 'Shipped', 'in-transit' => 'In transit', 'out-for-delivery' => 'Out for delivery', 'delivered' => 'Delivered');
    // The step each stored status stands on.
    $at = array(
        'in-review' => 'received',
        'pending' => 'received',
        'processing' => 'production',
        'finishing' => 'finishing',
        'ready-for-pickup' => 'pickup',
        'completed' => 'shipped',
        'in-transit' => 'in-transit',
        'out-for-delivery' => 'out-for-delivery',
        'delivered' => 'delivered',
        'delivery-issue' => 'in-transit',
        'returned' => 'in-transit',
    );
    $keys = array_keys($labels);

    // First time each step was reached, and the furthest step reached (where an on-hold order stopped).
    $dates = array();
    $furthest = 0;
    foreach (retails_order_timeline($order_post_id) as $event) {
        $key = 'placed' === $event['status'] ? 'received' : ($at[$event['status']] ?? '');
        $index = array_search($key, $keys, true);
        if (false === $index) {
            continue;
        }
        $dates[$key] = $dates[$key] ?? $event['time'];
        $furthest = max($furthest, $index);
    }
    // A status an order left was reached too (orders are created without a log entry: Review in progress since
    // 2026-11-12, In production before).
    foreach ((array) get_post_meta($order_post_id, '_status_log') as $entry) {
        $index = array_search($at[$entry['from'] ?? ''] ?? '', $keys, true);
        if (false !== $index) {
            $furthest = max($furthest, $index);
        }
    }
    $current = isset($at[$status]) ? (int) array_search($at[$status], $keys, true) : $furthest;
    // Orders placed before 2026-11-12 started in production the moment they were placed.
    if ($current >= 1 && !isset($dates['production'])) {
        $dates['production'] = $dates['received'] ?? 0;
    }
    $final = in_array($status, array('delivered', 'ready-for-pickup'), true);
    $problems = array(
        'on-hold' => array('On hold', 'We need something from you. Our team will contact you, or call 866-436-2101.'),
        'on_hold' => array('On hold', 'We need something from you. Our team will contact you, or call 866-436-2101.'),
        'delivery-issue' => array('Delivery issue', 'The carrier reported a problem or delay. We are following up.'),
        'returned' => array('Returned to sender', 'The carrier is returning the package. Our team will contact you.'),
    );

    $steps = array();
    foreach ($keys as $index => $key) {
        $done = $index < $current || ($index === $current && $final);
        $steps[$key] = array(
            'label' => $labels[$key],
            'state' => $done ? 'done' : ($index === $current ? 'current' : 'todo'),
            'here' => $index === $current,
            'date' => $index <= $current ? (int) ($dates[$key] ?? 0) : 0,
            'note' => '',
            'warn' => false,
        );
    }
    $current_key = $keys[$current];
    if (in_array($status, array('in-review', 'pending'), true)) {
        $steps[$current_key]['note'] = 'Checking your details';
    }
    if (isset($problems[$status])) {
        $steps[$current_key]['label'] = $problems[$status][0];
        $steps[$current_key]['note'] = $problems[$status][1];
        $steps[$current_key]['warn'] = true;
    }
    $ship_by = get_post_meta($order_post_id, 'estimate_delivery_time', true);
    if ($ship_by && isset($steps['shipped']) && 'todo' === $steps['shipped']['state']) {
        $steps['shipped']['note'] = 'Est. ' . $ship_by;
    }
    return (array) apply_filters('retails_order_steps', $steps, (int) $order_post_id);
}

/**
 * The step-by-step tracker. Already escaped.
 */
function retails_order_steps_html($order_post_id)
{
    $steps = retails_order_steps($order_post_id);
    if (!$steps) {
        return '';
    }
    $words = array('done' => 'Completed', 'current' => 'Current step', 'todo' => 'Not yet');
    $html = '<ol class="order-steps" style="--order-steps:' . count($steps) . '" aria-label="Order progress">';
    foreach ($steps as $step) {
        $classes = 'order-steps__step is-' . $step['state'] . ($step['warn'] ? ' is-warn' : '');
        $html .= '<li class="' . esc_attr($classes) . '"' . (!empty($step['here']) ? ' aria-current="step"' : '') . '>'
            . '<span class="order-steps__dot" aria-hidden="true"></span>'
            . '<span class="order-steps__text"><strong>' . esc_html($step['label']) . '</strong>'
            . '<span class="screen-reader-text">' . esc_html($words[$step['state']] ?? '') . '</span>'
            . ($step['date'] ? '<small>' . esc_html(wp_date('M j', $step['date'])) . '</small>' : '')
            . ('' !== $step['note'] ? '<small class="order-steps__note">' . esc_html($step['note']) . '</small>' : '')
            . '</span></li>';
    }
    return $html . '</ol>';
}

// Browser tab title for an order page: "Order #SN6LTHF5IA" instead of the internal post title.
add_filter('pre_get_document_title', function ($title) {
    return is_singular('order') ? 'Order #' . retails_order_number(get_queried_object_id()) . ' | ' . get_bloginfo('name') : $title;
}, 99);
