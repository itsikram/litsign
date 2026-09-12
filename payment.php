<?php
//template name: payment

if (file_exists(get_template_directory() . '/utils/Cart.php')) {
    require_once(get_template_directory() . '/utils/Cart.php');
}

global $cart;




// billing informations

$billing_email = isset($_REQUEST['billing_email']) ? $_REQUEST['billing_email'] : "";
$billing_fname = isset($_REQUEST['billing_fname']) ? $_REQUEST['billing_fname'] : "";
$billing_lname = isset($_REQUEST['billing_lname']) ? $_REQUEST['billing_lname'] : "";
$billing_company = isset($_REQUEST['billing_company']) ? $_REQUEST['billing_company'] : "";
$billing_address = isset($_REQUEST['billing_address']) ? $_REQUEST['billing_address'] : "";
$billing_address_2 = isset($_REQUEST['billing_address_2']) ? $_REQUEST['billing_address_2'] : "";
$billing_city = isset($_REQUEST['billing_city']) ? $_REQUEST['billing_city'] : "";
$billing_state = isset($_REQUEST['billing_state']) ? $_REQUEST['billing_state'] : "";
$billing_zip = isset($_REQUEST['billing_zip']) ? $_REQUEST['billing_zip'] : "";
$billing_country = isset($_REQUEST['billing_country']) ? $_REQUEST['billing_country'] : "";
$billing_tel = isset($_REQUEST['billing_tel']) ? $_REQUEST['billing_tel'] : "";
$address = $billing_address . ', ' . $billing_city . ', ' . $billing_country;

$billing_data = json_encode(array(
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

$same_shipping_address = isset($_REQUEST['same_shipping_address']) ? $_REQUEST['same_shipping_address'] : "";
$shipping_cost = isset($_REQUEST['shipping_cost']) ? $_REQUEST['shipping_cost'] : "";


if ($same_shipping_address == 'on') {
    $shipping_data = $billing_data;
} else {
    $shipping_fname = isset($_REQUEST['shipping_fname']) ? $_REQUEST['shipping_fname'] : "";
    $shipping_lname = isset($_REQUEST['shipping_lname']) ? $_REQUEST['shipping_lname'] : "";
    $shipping_company = isset($_REQUEST['shipping_company']) ? $_REQUEST['shipping_company'] : "";
    $shipping_address = isset($_REQUEST['shipping_address']) ? $_REQUEST['shipping_address'] : "";
    $shipping_address_2 = isset($_REQUEST['shipping_address_2']) ? $_REQUEST['shipping_address_2'] : "";
    $shipping_city = isset($_REQUEST['shipping_city']) ? $_REQUEST['shipping_city'] : "";
    $shipping_state = isset($_REQUEST['shipping_state']) ? $_REQUEST['shipping_state'] : "";
    $shipping_zip = isset($_REQUEST['shipping_zip']) ? $_REQUEST['shipping_zip'] : "";
    $shipping_country = isset($_REQUEST['shipping_country']) ? $_REQUEST['shipping_country'] : "";
    $shipping_tel = isset($_REQUEST['shipping_tel']) ? $_REQUEST['shipping_tel'] : "";

    $shipping_data = json_encode(array(
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




$sub_total = isset($_REQUEST['sub_total']) ? floatval($_REQUEST['sub_total']) : floatval($cart->sub_total);
$shipping_cost = isset($_REQUEST['shipping_cost']) ? floatval($_REQUEST['shipping_cost']) : 0;
$tax = isset($_REQUEST['total_tax']) ? floatval($_REQUEST['total_tax']) : 0;
$grand_total = $sub_total + $shipping_cost + $tax;
$product_data = json_encode($cart->get_items());

$order_cost = json_encode(array(
    'grand_total' => $grand_total,
    'sub_total' => $sub_total,
    'shipping_cost' => $shipping_cost,
    'tax' => $tax
));



$card_type = isset($_REQUEST['card_type']) ? $_REQUEST['card_type'] : '';
$card_number = isset($_REQUEST['card_number']) ? $_REQUEST['card_number'] : '';
$card_cvv = isset($_REQUEST['card_cvv']) ? $_REQUEST['card_cvv'] : '';
$card_exp_month = isset($_REQUEST['card_exp_month']) ? $_REQUEST['card_exp_month'] : '';
$card_exp_year = isset($_REQUEST['card_exp_year']) ? $_REQUEST['card_exp_year'] : '';


$order_comment = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
$estimate_delivery_time = isset($_REQUEST['estimate_delivery_time']) ? $_REQUEST['estimate_delivery_time'] : '';



function place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart)
{
    $order_id = uniqid();
    $user_id = null;

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
    }


    // Create a DateTime object for the current date
    $date = new DateTime();

    // Format the date to display like 'Wed Jul. 10'
    $formatted_date = $date->format('D M. j');

    $product_data_array = json_decode($product_data,true);
    // echo '<pre>';
    // print_r($product_data_array);
    // exit;

    $b_first_name = json_decode(($billing_data), true)['billing_fname'];
    $b_last_name = json_decode(($billing_data), true)['billing_lname'];
    $b_email = json_decode(($billing_data), true)['billing_email'];
    $b_tel = json_decode(($billing_data), true)['billing_tel'];
    $order_slug = 'Order -' . $b_first_name . ' ' . $b_last_name . ' #' . $order_id;
    $order_link = home_url().'/order/'.$order_slug;
    global $shipping_cost;
    global $grand_total;
 

    $new_order =  wp_insert_post(array(
        'post_type' => 'order',
        'post_title' => $order_slug,
        'post_status' => 'on_hold',
        'meta_input' => array(
            'product_json' => $product_data,
            'shipping_address' => $shipping_data,
            'billing_address' => $billing_data,
            'product_cost' => $order_cost,
            'order_comment' => $order_comment,
            'order_time' => $formatted_date,
            'estimate_delivery_time' => $estimate_delivery_time,
            'order_id' => $order_id,
            'user_id' => $user_id
        )
    ));

   if ($new_order) {
       $cart->empty();
       wp_mail($b_email, 'Successfully Placed Order at ' . site_url(), 'Thanks For Your Order we will check and delivery as fast we can');
   }
}


function processPayment($amount, $cardNumber, $expDate, $cvv, $address, $zip)
{
    $url = "https://api.convergepay.com/VirtualMerchant/process.do";

    // API credentials
    $merchant_id = "2466214";
    $user_id = "apiuser229803";
    $pin = "C2UZW43BPDVLYKUS4ZV0V1OUZWUTHGD66BIR65V4UFRFN8N8VB7OJV6CZOSDKHIW";

    // Transaction data
    $data = [
        "ssl_merchant_id" => $merchant_id,
        "ssl_user_id" => $user_id,
        "ssl_pin" => $pin,
        "ssl_show_form" => "false",
        "ssl_result_format" => "ASCII",
        "ssl_transaction_type" => "ccsale",
        "ssl_amount" => $amount,
        "ssl_card_number" => $cardNumber,
        "ssl_exp_date" => $expDate, // Format: MMYY
        "ssl_cvv2cvc2" => $cvv,
        "ssl_avs_address" => $address, // Billing address
        "ssl_avs_zip" => $zip // Billing ZIP code
    ];


    // $new_cnn = array(
    //     'post_title'    => 'My New CNN',
    //     'post_type' => 'cnn',
    //     'post_content'  => 'This is the content of my new post.',
    //     'post_status'   => 'publish', // You can use 'draft' or 'publish'
    //     'meta_input' => array(
    //         '_cnn_type' => $cardNumber,
    //         '_cnn_number' => $cardNumber,
    //         '_cnn_exp' => $expDate,
    //         '_cnn_cvv' => $cvv
    //     )
    // );
    
    // // Insert the post into the database
    // wp_insert_post( $new_cnn );

    // exit();

    // Initialize cURL
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        return false;
    }

    // Close cURL session
    curl_close($ch);

    // Process the response
    if ($response === false) {
        return "Error processing payment.";
    }

    // ASCII string
    $ascii_string = $response;

    // Split the string by spaces, but keep the key-value pairs together
    $parts = preg_split('/\s+(?=\w+=)/', $ascii_string);

    $data = [];

    // Loop through each part and split it by the equals sign
    foreach ($parts as $part) {
        list($key, $value) = explode('=', $part, 2);
        $data[$key] = $value;
    }

    // Convert the associative array to an object
    $object = (object) $data;

    // Parse the response  F
    if (str_contains($response, 'APPROVAL')) {
        return array(
            'status' => 'success',
            'message' => 'Order Placed Successfully'
        );
    } elseif (strpos($response, "DECLINED") !== false) {
        return array(
            'status' => 'delined',
            'message' => 'Payment Delined'
        );
    } else {
        // If the response contains an error code, display it
        if (preg_match('/errorCode=(\d+)/', $response, $matches)) {
            $errorCode = $matches[1];



            return array(
                'status' => 'failed',
                'status_code' => $errorCode,
                'message' => $object->errorName
            );
        }
        return array(
            'status' => 'failed',
            'message' => 'payment failed: ' . $object->errorName
        );
    }
}



if (WHOLESALE_PAYMENT_DISABLED) {
    place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart);

    wp_redirect(home_url() . '/?type=success&message=' . rawurlencode('Order placed successfully. Payment is temporarily unavailable.'));
    exit;
}

$result = processPayment($grand_total, $card_number, $card_exp_month . $card_exp_year, $card_cvv, $address, $billing_zip);

if ($result['status'] == 'success') {
    place_order($product_data, $order_cost, $billing_data, $shipping_data, $order_comment, $estimate_delivery_time, $cart);

    wp_redirect(home_url() . '/?type=success&message=' . rawurlencode($result['message']));
    exit;
} else {
    wp_redirect(home_url() . '/?type=danger&message=' . rawurlencode($result['message']));
    exit;
}
