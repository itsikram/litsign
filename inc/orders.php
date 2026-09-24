<?php
/**
 * Order creation and card payment helpers shared by cart checkout (payment.php)
 * and payment tickets (page-pay.php).
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

function processPayment($amount, $cardNumber, $expDate, $cvv, $address, $zip)
{
   $merchant_id = wholesale_get_setting('merchant_id');
   $user_id = wholesale_get_setting('gateway_user_id');
   $pin = wholesale_get_setting('gateway_pin');

   if (empty($merchant_id) || empty($user_id) || empty($pin)) {
       return array(
           'status' => 'failed',
           'message' => 'Payment gateway is not configured.'
       );
   }

//    $url = 'https://api.convergepay.com/VirtualMerchant/process.do';
       // Switch endpoint based on a test-mode flag
    $url = wholesale_setting_enabled('payment_test_mode')
        ? 'https://api.demo.convergepay.com/VirtualMerchantDemo/process.do'
        : 'https://api.convergepay.com/VirtualMerchant/process.do';
   $data = array(
       'ssl_merchant_id' => $merchant_id,
       'ssl_user_id' => $user_id,
       'ssl_pin' => $pin,
       'ssl_show_form' => 'false',
       'ssl_result_format' => 'ASCII',
       'ssl_transaction_type' => 'ccsale',
       'ssl_amount' => $amount,
       'ssl_card_number' => $cardNumber,
       'ssl_exp_date' => $expDate,
       'ssl_cvv2cvc2' => $cvv,
       'ssl_avs_address' => $address,
       'ssl_avs_zip' => $zip,
   );

   $response = wp_remote_post($url, array(
       'timeout' => 45,
       'sslverify' => true,
       'headers' => array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8'),
       'body' => $data,
   ));

   if (is_wp_error($response)) {
       return array(
           'status' => 'failed',
           'message' => 'Payment gateway request failed.'
       );
   }

   $response_body = wp_remote_retrieve_body($response);
   if (empty($response_body)) {
       return array(
           'status' => 'failed',
           'message' => 'Payment gateway returned an empty response.'
       );
   }

   $parts = preg_split('/\s+(?=\w+=)/', (string) $response_body);
   $decoded_response = array();

   foreach ($parts as $part) {
       $pair = explode('=', $part, 2);
       if (count($pair) === 2) {
           $decoded_response[$pair[0]] = $pair[1];
       }
   }

   $object = (object) $decoded_response;

   if (str_contains($response_body, 'APPROVAL')) {
       return array(
           'status' => 'success',
           'message' => 'Order Placed Successfully',
       );
   }

   if (strpos($response_body, 'DECLINED') !== false) {
       return array(
           'status' => 'failed',
           'message' => 'Payment declined.',
       );
   }

   if (preg_match('/errorCode=(\d+)/', $response_body, $matches)) {
       return array(
           'status' => 'failed',
           'status_code' => $matches[1],
           'message' => isset($object->errorName) ? sanitize_text_field((string) $object->errorName) : 'Payment failed.',
       );
   }

   return array(
       'status' => 'failed',
       'message' => 'Payment failed.',
   );
}

/**
 * Price of a channel letter builder design, computed on the server from the
 * saved design state: element sizes and face colors, plus power supply, lit
 * (a percentage of the letters) and cable extras.
 */
function wholesale_cl_design_total($design)
{
    $safe_cost = static function ($value) {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : 0.0;
    };
    $elements = isset($design['elements']) && is_array($design['elements']) ? $design['elements'] : array();
    $extras = isset($design['extras']) && is_array($design['extras']) ? $design['extras'] : array();

    $product_cost = 0;
    foreach ($elements as $element) {
        $product_cost += $safe_cost($element['cost'] ?? 0) + $safe_cost($element['colorCost'] ?? 0);
    }

    $extras_cost = 0;
    if (!empty($extras['powerSupply']['qty'])) {
        $extras_cost += $safe_cost($extras['powerSupply']['cost'] ?? 0);
    }
    $extra_lit_percent = !empty($extras['lit']['qty']) ? $safe_cost($extras['lit']['cost'] ?? 0) : 0;
    $extras_cost += ($product_cost * $extra_lit_percent) / 100;
    if (!empty($extras['cable']['qty'])) {
        $extras_cost += $safe_cost($extras['cable']['cost'] ?? 0);
    }

    return $product_cost + $extras_cost;
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
