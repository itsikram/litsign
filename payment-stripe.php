<?php
//template name: payment stripe


 if(file_exists(dirname(__FILE__).'/stripe-php/init.php')){
	require_once(dirname(__FILE__).'/stripe-php/init.php');

}


define('STRIPE_API_KEY', 'sk_test_51NWbhtBkPgGRflkhwqvsaWM2JxFDJuBRXYFaS1L4pCd4L7baZUpofO5IhUYlD0WZv0wPOGQB6eBdvGp8GuohuWAK006rCVNTHg'); 
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51NWbhtBkPgGRflkhjqfJ4sVRfodWxqCeaTsgGhizrruZR5eNkZG4ebdGHesHTVdTFXxGSo6Mcp88HALuANAvZD2Z00GbRxzgbD'); 
define('STRIPE_SUCCESS_URL', site_url().'/thank-you/'); //Payment success URL
define('STRIPE_CANCEL_URL', site_url().'/?type=danger&message=Payment Failed'); //Payment cancel URL 


$stripe = new \Stripe\StripeClient(STRIPE_API_KEY); 
 
$response = array( 
    'status' => 0, 
    'error' => array( 
        'message' => 'Invalid Request!'    
    ) 
); 


if ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    $input = file_get_contents('php://input'); 
    $request = json_decode($input);  
    $productPrice = $request -> cost;
    $currency = $request -> currency;   
    $billing_email = $request -> email;
    $productTitle = $request -> title;

} 

 
if (json_last_error() !== JSON_ERROR_NONE) { 
    http_response_code(400); 
    echo json_encode($response); 
    exit; 
} 

if(!empty($request->createCheckoutSession)){ 
    // Convert product price to cent 
    $stripeAmount = round($productPrice*100,2);
 
    // Create new Checkout Session for the order 
    try { 
        $checkout_session = $stripe->checkout->sessions->create([ 
            'line_items' => [[ 
                'price_data' => [ 
                    'product_data' => [ 
                        'name' => 'lorem', 
                    ], 
                    'unit_amount' => $stripeAmount, 
                    'currency' => $currency, 
                ], 
                'quantity' => 1 
            ]],

            'mode' => 'payment', 
            'success_url' => STRIPE_SUCCESS_URL.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => STRIPE_CANCEL_URL, 
            //'customer_email' => $billing_email,
            
        ]); 
    } catch(Exception $e) {  
        $api_error = $e->getMessage();
		} 
     
    if(empty($api_error) && $checkout_session){ 
        $response = array( 
            'status' => 1, 
            'message' => 'Checkout Session created successfully!', 
            'sessionId' => $checkout_session->id ,
        ); 
    }else{ 
        $response = array( 
            'status' => 0, 
            'error' => array( 
                'message' => 'Checkout Session creation failed! '.$api_error    
            ) 
        ); 
    } 
} 
 
// Return response 
echo json_encode($response); 
 
?>