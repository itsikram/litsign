<?php
//template name: payment

if ('POST' !== strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '')) {
    wp_safe_redirect(home_url('/checkout/'));
    exit;
}

$order_nonce = isset($_POST['wholesale_order_nonce']) ? sanitize_text_field(wp_unslash($_POST['wholesale_order_nonce'])) : '';
if (!$order_nonce || !wp_verify_nonce($order_nonce, 'wholesale_place_order')) {
    wp_die(esc_html__('Your checkout session has expired. Please return to checkout and try again.', 'litsign'), esc_html__('Invalid checkout request', 'litsign'), array('response' => 403));
}

if (file_exists(get_template_directory() . '/utils/Cart.php')) {
    require_once(get_template_directory() . '/utils/Cart.php');
}

global $cart;




// billing informations

$billing_email = isset($_REQUEST['billing_email']) ? sanitize_email(wp_unslash($_REQUEST['billing_email'])) : '';
$billing_fname = isset($_REQUEST['billing_fname']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_fname'])) : '';
$billing_lname = isset($_REQUEST['billing_lname']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_lname'])) : '';
$billing_company = isset($_REQUEST['billing_company']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_company'])) : '';
$billing_address = isset($_REQUEST['billing_address']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_address'])) : '';
$billing_address_2 = isset($_REQUEST['billing_address_2']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_address_2'])) : '';
$billing_city = isset($_REQUEST['billing_city']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_city'])) : '';
$billing_state = isset($_REQUEST['billing_state']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_state'])) : '';
$billing_zip = isset($_REQUEST['billing_zip']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_zip'])) : '';
$billing_country = isset($_REQUEST['billing_country']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_country'])) : '';
$billing_tel = isset($_REQUEST['billing_tel']) ? sanitize_text_field(wp_unslash($_REQUEST['billing_tel'])) : '';
$address = trim($billing_address . ', ' . $billing_city . ', ' . $billing_country);

if (
    !is_email($billing_email)
    || '' === $billing_fname
    || '' === $billing_lname
    || '' === $billing_address
    || '' === $billing_city
    || '' === $billing_state
    || '' === $billing_zip
    || '' === $billing_country
) {
    wp_die(esc_html__('Please provide a complete and valid billing address.', 'litsign'), esc_html__('Invalid order', 'litsign'), array('response' => 400));
}

$billing_data = wp_json_encode(array(
   'billing_email' => $billing_email,
   'billing_fname' => $billing_fname,
   'billing_lname' => $billing_lname,
   'billing_company' => $billing_company,
   'billing_address' => $billing_address,
   'billing_address_2' => $billing_address_2,
   'billing_city' => $billing_city,
   'billing_state' => $billing_state,
   'billing_zip' => $billing_zip,
   'billing_country' => $billing_country,
   'billing_tel' => $billing_tel,
));

$same_shipping_address = isset($_REQUEST['same_shipping_address']) ? sanitize_text_field(wp_unslash($_REQUEST['same_shipping_address'])) : '';
$shipping_cost = isset($_REQUEST['shipping_cost']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_cost'])) : '';

if ('on' === $same_shipping_address) {
   $shipping_data = $billing_data;
} else {
   $shipping_fname = isset($_REQUEST['shipping_fname']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_fname'])) : '';
   $shipping_lname = isset($_REQUEST['shipping_lname']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_lname'])) : '';
   $shipping_company = isset($_REQUEST['shipping_company']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_company'])) : '';
   $shipping_address = isset($_REQUEST['shipping_address']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_address'])) : '';
   $shipping_address_2 = isset($_REQUEST['shipping_address_2']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_address_2'])) : '';
   $shipping_city = isset($_REQUEST['shipping_city']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_city'])) : '';
   $shipping_state = isset($_REQUEST['shipping_state']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_state'])) : '';
   $shipping_zip = isset($_REQUEST['shipping_zip']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_zip'])) : '';
   $shipping_country = isset($_REQUEST['shipping_country']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_country'])) : '';
   $shipping_tel = isset($_REQUEST['shipping_tel']) ? sanitize_text_field(wp_unslash($_REQUEST['shipping_tel'])) : '';

   $shipping_data = wp_json_encode(array(
       'shipping_fname' => $shipping_fname,
       'shipping_lname' => $shipping_lname,
       'shipping_company' => $shipping_company,
       'shipping_address' => $shipping_address,
       'shipping_address_2' => $shipping_address_2,
       'shipping_city' => $shipping_city,
       'shipping_state' => $shipping_state,
       'shipping_zip' => $shipping_zip,
       'shipping_country' => $shipping_country,
       'shipping_tel' => $shipping_tel,
       'shipping_cost' => $shipping_cost,
   ));
}

$sub_total = isset($_REQUEST['sub_total']) ? floatval(wp_unslash($_REQUEST['sub_total'])) : (isset($cart->sub_total) ? floatval($cart->sub_total) : 0);
$cart_items = is_object($cart) && is_callable(array($cart, 'get_items')) ? $cart->get_items() : array();

if (empty($cart_items)) {
    wp_safe_redirect(home_url('/cart/?type=warning&message=' . rawurlencode('Your cart is currently empty.')));
    exit;
}

foreach ($cart_items as $cart_item) {
    $product_id = isset($cart_item->product_id) ? absint($cart_item->product_id) : 0;
    $quantity = isset($cart_item->product_quantity) ? absint($cart_item->product_quantity) : 0;
    $item_subtotal = isset($cart_item->product_subtotal) ? floatval($cart_item->product_subtotal) : 0;

    if (!$product_id || 'product' !== get_post_type($product_id) || 'publish' !== get_post_status($product_id) || $quantity < 1 || $item_subtotal <= 0) {
        wp_die(esc_html__('The cart contains an invalid item. Please rebuild your cart and try again.', 'litsign'), esc_html__('Invalid order', 'litsign'), array('response' => 400));
    }
}

// Never trust totals from hidden form fields; derive them from the server-side cart.
$sub_total = round(floatval($cart->sub_total), 2);
$shipping_options = array_map('floatval', explode(',', wholesale_get_setting('standard_shipping_options')));
foreach ($cart_items as $cart_item) {
    $categories = get_the_terms(absint($cart_item->product_id), 'product_category');
    if (is_array($categories)) {
        foreach ($categories as $category) {
            if ('channel-letters' === $category->slug) {
                $shipping_options = array_map('floatval', explode(',', wholesale_get_setting('channel_shipping_options')));
                break 2;
            }
        }
    }
}

$requested_shipping_cost = isset($_POST['shipping_method']) ? floatval(wp_unslash($_POST['shipping_method'])) : 0;
if (!in_array($requested_shipping_cost, $shipping_options, true)) {
    wp_die(esc_html__('Please select a valid shipping method.', 'litsign'), esc_html__('Invalid order', 'litsign'), array('response' => 400));
}

$shipping_cost = $requested_shipping_cost;
$tax = round(($sub_total / 100) * floatval(wholesale_get_setting('tax_rate')), 2);
$grand_total = round($sub_total + $shipping_cost + $tax, 2);
$product_data = wp_json_encode($cart_items);

$order_cost = wp_json_encode(array(
   'grand_total' => $grand_total,
   'sub_total' => $sub_total,
   'shipping_cost' => $shipping_cost,
   'tax' => $tax,
));

$card_type = isset($_REQUEST['card_type']) ? sanitize_text_field(wp_unslash($_REQUEST['card_type'])) : '';
$card_number = isset($_REQUEST['card_number']) ? sanitize_text_field(wp_unslash($_REQUEST['card_number'])) : '';
$card_cvv = isset($_REQUEST['card_cvv']) ? sanitize_text_field(wp_unslash($_REQUEST['card_cvv'])) : '';
$card_exp_month = isset($_REQUEST['card_exp_month']) ? sanitize_text_field(wp_unslash($_REQUEST['card_exp_month'])) : '';
$card_exp_year = isset($_REQUEST['card_exp_year']) ? sanitize_text_field(wp_unslash($_REQUEST['card_exp_year'])) : '';

$order_comment = isset($_REQUEST['comment']) ? sanitize_textarea_field(wp_unslash($_REQUEST['comment'])) : '';
$estimate_delivery_time = isset($_REQUEST['estimate_delivery_time']) ? sanitize_text_field(wp_unslash($_REQUEST['estimate_delivery_time'])) : '';



function place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart)
{
   $order_id = wp_unique_id('order_');
   $user_id = is_user_logged_in() ? get_current_user_id() : null;

   $date = new DateTime();
   $formatted_date = $date->format('D M. j');

   $billing_data_array = json_decode((string) $billing_data, true);
   if (!is_array($billing_data_array)) {
       $billing_data_array = array();
   }

   $b_first_name = isset($billing_data_array['billing_fname']) ? sanitize_text_field((string) $billing_data_array['billing_fname']) : '';
   $b_last_name = isset($billing_data_array['billing_lname']) ? sanitize_text_field((string) $billing_data_array['billing_lname']) : '';
   $b_email = isset($billing_data_array['billing_email']) ? sanitize_email((string) $billing_data_array['billing_email']) : '';
   $order_slug = 'Order -' . trim($b_first_name . ' ' . $b_last_name) . ' #' . $order_id;

   $new_order = wp_insert_post(array(
       'post_type' => 'order',
       'post_title' => sanitize_text_field($order_slug),
       'post_status' => 'on_hold',
       'meta_input' => array(
           'product_json' => $product_data,
           'shipping_address' => $shipping_data,
           'billing_address' => $billing_data,
           'product_cost' => $order_cost,
           'order_comment' => wp_strip_all_tags($order_comment),
           'order_time' => sanitize_text_field($formatted_date),
           'estimate_delivery_time' => sanitize_text_field($estimate_delivery_time),
           'order_id' => sanitize_text_field($order_id),
           'user_id' => $user_id,
       ),
   ), true);

   if (is_wp_error($new_order) || !$new_order) {
       return false;
   }

   $cart->empty();

   if (is_email($b_email)) {
       wp_mail(
           $b_email,
           'Successfully Placed Order at ' . site_url(),
           'Thanks For Your Order we will check and delivery as fast we can'
       );
   }

   return true;
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



if (wholesale_setting_enabled('payment_disabled')) {
    if (!place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart)) {
        wp_die(esc_html__('We could not create your order. Please try again.', 'litsign'), esc_html__('Order failed', 'litsign'), array('response' => 500));
    }

    wp_safe_redirect(home_url('/thank-you/'));
    exit;
}

$result = processPayment($grand_total, $card_number, $card_exp_month . $card_exp_year, $card_cvv, $address, $billing_zip);

if ($result['status'] == 'success') {
    if (!place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart)) {
        wp_die(esc_html__('We could not create your order. Please try again.', 'litsign'), esc_html__('Order failed', 'litsign'), array('response' => 500));
    }

    wp_safe_redirect(home_url('/thank-you/'));
    exit;
} else {
    wp_redirect(home_url() . '/?type=danger&message=' . rawurlencode($result['message']));
    exit;
}
