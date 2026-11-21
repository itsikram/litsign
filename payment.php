<?php
//template name: payment

/*
 * No-JavaScript fallback for the checkout form. Card payments run through the secure
 * Converge window (inc/payments.php + js/checkout.js); this page only places orders
 * when online card payment is turned off in Settings → Storefront Sign.
 */

if ('POST' !== strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '')) {
    wp_safe_redirect(home_url('/checkout/'));
    exit;
}

$order_nonce = isset($_POST['retails_order_nonce']) ? sanitize_text_field(wp_unslash($_POST['retails_order_nonce'])) : '';
if (!$order_nonce || !wp_verify_nonce($order_nonce, 'retails_place_order')) {
    wp_die(esc_html__('Your checkout session has expired. Please return to checkout and try again.', 'litsign'), esc_html__('Invalid checkout request', 'litsign'), array('response' => 403));
}

$checkout_error = static function ($message) {
    wp_safe_redirect(home_url('/checkout/?type=danger&message=' . rawurlencode($message)));
    exit;
};

if (!retails_setting_enabled('payment_disabled')) {
    $checkout_error('Please enable JavaScript to open our secure card payment window, or call us at 866-436-2101 to order by phone.');
}

$cart = retails_get_cart();
if (empty($cart->get_items())) {
    wp_safe_redirect(home_url('/cart/?type=warning&message=' . rawurlencode('Your cart is currently empty.')));
    exit;
}

$checkout = retails_checkout_build($_POST, $cart);
if (is_wp_error($checkout)) {
    $checkout_error($checkout->get_error_message());
}

$account_password = isset($_POST['account_password']) ? (string) wp_unslash($_POST['account_password']) : '';
if ($account_error = retails_checkout_account_error($checkout, $account_password)) {
    $checkout_error($account_error);
}

$order = retails_checkout_create_order($checkout, $cart, array('_payment_status' => 'manual'));
if (!$order) {
    wp_die(esc_html__('We could not create your order. Please try again or call us at 866-436-2101.', 'litsign'), esc_html__('Order failed', 'litsign'), array('response' => 500));
}
retails_checkout_attach_account($order, $checkout, $account_password);

wp_safe_redirect(retails_thank_you_url($order));
exit;
