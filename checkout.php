<?php
// Template Name: Checkout

$cart = wholesale_get_cart();

if (empty($cart->get_items())) {
    wp_safe_redirect(home_url('/cart/?type=warning&message=' . rawurlencode('Your cart is currently empty.')));
    exit;
}

$sub_total = round((float) $cart->sub_total, 2);
$tax_rate = (float) wholesale_get_setting('tax_rate');
$tax = round($sub_total * $tax_rate / 100, 2);
$free_shipping_min = wholesale_free_shipping_threshold($cart);
$free_shipping = wholesale_cart_qualifies_free_shipping($cart);

// Delivery (inc/shipping.php): UPS/FedEx speeds, Blind Drop Ship, Store Pickup.
$shipping_methods = wholesale_cart_shipping_methods($cart);
$default_method = wholesale_default_shipping_method($shipping_methods);
$first_shipping = $default_method ? (float) $shipping_methods[$default_method]['amount'] : 0;
$method_speeds = array();
foreach ($shipping_methods as $method) {
    $method_speeds[$method['speed']][] = $method;
}
$carriers = wholesale_enabled_shipping_carriers();
$default_carrier = $default_method ? $shipping_methods[$default_method]['carrier'] : (string) array_key_first($carriers);
$pickup = wholesale_pickup_location();
$ship_date = wholesale_estimated_ship_date($cart);
$blind_from = wholesale_saved_blind_ship_from();
$card_enabled = !wholesale_setting_enabled('payment_disabled');
$direct_card = $card_enabled && 'direct' === wholesale_payment_method();
$noun = wholesale_items_noun($cart->get_items());
$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';

// Prefill for signed-in customers.
$user = wp_get_current_user();
$prefill = array(
    'billing_email' => $user->exists() ? $user->user_email : '',
    'billing_fname' => $user->exists() ? $user->first_name : '',
    'billing_lname' => $user->exists() ? $user->last_name : '',
    'billing_tel' => $user->exists() ? get_user_meta($user->ID, 'telephone', true) : '',
    'billing_address' => $user->exists() ? get_user_meta($user->ID, 'street_one', true) : '',
    'billing_address_2' => $user->exists() ? get_user_meta($user->ID, 'street_two', true) : '',
    'billing_city' => $user->exists() ? get_user_meta($user->ID, 'city', true) : '',
    'billing_state' => $user->exists() ? get_user_meta($user->ID, 'state', true) : '',
    'billing_zip' => $user->exists() ? get_user_meta($user->ID, 'zip', true) : '',
);

$money = static function ($value) {
    return '$' . number_format((float) $value, 2);
};

$states = array(
    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado',
    'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia',
    'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
    'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts',
    'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana',
    'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
    'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
    'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'PR' => 'Puerto Rico', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
    'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia',
    'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
);

// State dropdown; a saved value that isn't a US state or code is kept as its own option.
$state_select = static function ($id, $name, $autocomplete, $value = '', $extra = '') use ($states) {
    $value = trim((string) $value);
    $code = isset($states[strtoupper($value)]) ? strtoupper($value) : (string) array_search(strtolower($value), array_map('strtolower', $states), true);
    ?>
    <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" autocomplete="<?php echo esc_attr($autocomplete); ?>" <?php echo $extra; // Static attributes. ?>>
        <option value="">Select</option>
        <?php foreach ($states as $abbr => $label) : ?>
            <option value="<?php echo esc_attr($abbr); ?>" <?php selected($code, $abbr); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
        <?php if ($value && !$code) : ?>
            <option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_html($value); ?></option>
        <?php endif; ?>
    </select>
    <?php
};

$lock_icon = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 1a5 5 0 0 0-5 5v3H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-2V6a5 5 0 0 0-5-5Zm-3 5a3 3 0 0 1 6 0v3H9V6Z"/></svg>';
$grand_total = $sub_total + $first_shipping + $tax;
$item_count = count($cart->get_items());

get_header();
?>

<main id="primary" class="checkout-page">
    <div class="container">
        <nav class="checkout-steps" aria-label="Checkout progress">
            <a href="<?php echo esc_url(home_url('/cart/')); ?>">Cart</a>
            <span class="is-current" aria-current="step">Checkout</span>
            <span>Confirmation</span>
        </nav>

        <h1 class="checkout-title">Secure Checkout</h1>

        <?php if ($message) : ?>
            <div class="checkout-alert" role="alert"><?php echo esc_html($message); ?></div>
        <?php endif; ?>

        <form action="<?php echo esc_url(home_url('/payment/')); ?>" method="post" id="checkoutForm" class="checkout-layout" novalidate>
            <?php wp_nonce_field('wholesale_place_order', 'wholesale_order_nonce'); ?>

            <div class="checkout-main">
                <details class="checkout-mobile-summary">
                    <summary>
                        <span class="checkout-mobile-summary__label"><?php echo esc_html(sprintf(_n('%d item', '%d items', $item_count, 'litsign'), $item_count)); ?> &middot; <span data-show-label>Show summary</span>
                            <?php // The total alone ($34.55 for a $15 banner) read as a hidden fee; show what it is made of. ?>
                            <small class="checkout-mobile-summary__breakdown"><?php echo esc_html($money($sub_total)); ?> + <span data-shipping-total><?php echo esc_html($first_shipping > 0 ? $money($first_shipping) : 'FREE'); ?></span> shipping<?php echo $tax > 0 ? esc_html(' + ' . $money($tax) . ' tax') : ''; ?></small>
                        </span>
                        <strong data-grand-total><?php echo esc_html($money($grand_total)); ?></strong>
                    </summary>
                    <ul class="checkout-items">
                        <?php foreach ($cart->get_items() as $item) : ?>
                            <li>
                                <span class="checkout-item-name">
                                    <?php echo esc_html($item->product_title); ?>
                                    <small>Qty <?php echo esc_html($item->product_quantity); ?></small>
                                </span>
                                <span class="checkout-item-price"><?php echo esc_html($money($item->product_subtotal)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo esc_url(home_url('/cart/')); ?>">Edit cart</a>
                </details>

                <section class="checkout-card" aria-labelledby="checkout-details">
                    <div class="checkout-card-head">
                        <h2 id="checkout-details"><span class="checkout-step-number">1</span> Your details</h2>
                        <?php if (!is_user_logged_in()) : ?>
                            <p class="checkout-signin">Have an account? <a href="<?php echo esc_url(add_query_arg('redirect_ulr', rawurlencode(home_url('/checkout/')), home_url('/login/'))); ?>">Log in</a></p>
                        <?php endif; ?>
                    </div>

                    <p class="checkout-remembered" data-remembered hidden>
                        Welcome back! We filled in your details from last time.
                        <button type="button" class="checkout-link" data-forget>Not you? Clear</button>
                    </p>

                    <div class="checkout-grid">
                        <p class="checkout-field">
                            <label for="billingEmail">Email</label>
                            <input type="email" id="billingEmail" name="billing_email" required autocomplete="email" inputmode="email" spellcheck="false" autocapitalize="off" value="<?php echo esc_attr($prefill['billing_email']); ?>" aria-describedby="billingEmailHint">
                            <small id="billingEmailHint">We'll send your order confirmation here.</small>
                            <button type="button" class="checkout-suggest" data-email-suggest hidden></button>
                        </p>
                        <p class="checkout-field">
                            <label for="billingTel">Phone <span class="checkout-optional">(recommended)</span></label>
                            <input type="tel" id="billingTel" name="billing_tel" autocomplete="tel" inputmode="tel" value="<?php echo esc_attr($prefill['billing_tel']); ?>">
                        </p>
                        <p class="checkout-field checkout-field--half">
                            <label for="billingFirstName">First name</label>
                            <input type="text" id="billingFirstName" name="billing_fname" required autocomplete="given-name" value="<?php echo esc_attr($prefill['billing_fname']); ?>">
                        </p>
                        <p class="checkout-field checkout-field--half">
                            <label for="billingLastName">Last name</label>
                            <input type="text" id="billingLastName" name="billing_lname" required autocomplete="family-name" value="<?php echo esc_attr($prefill['billing_lname']); ?>">
                        </p>
                        <p class="checkout-field checkout-field--full">
                            <label for="billingCompany">Business name <span class="checkout-optional">(optional)</span></label>
                            <input type="text" id="billingCompany" name="billing_company" autocomplete="organization">
                        </p>
                        <p class="checkout-field checkout-field--full">
                            <label for="billingAddress">Street address</label>
                            <input type="text" id="billingAddress" name="billing_address" value="<?php echo esc_attr($prefill['billing_address']); ?>" required autocomplete="address-line1">
                            <button type="button" class="checkout-link" data-reveal="billingAddress2">+ Add apartment, suite or unit</button>
                            <input type="text" id="billingAddress2" name="billing_address_2" value="<?php echo esc_attr($prefill['billing_address_2']); ?>" autocomplete="address-line2" placeholder="Apartment, suite, unit (optional)" aria-label="Apartment, suite or unit" hidden>
                        </p>
                        <div class="checkout-grid checkout-grid--address checkout-field--full">
                            <p class="checkout-field checkout-field--city">
                                <label for="billingCity">City</label>
                                <input type="text" id="billingCity" name="billing_city" value="<?php echo esc_attr($prefill['billing_city']); ?>" required autocomplete="address-level2">
                            </p>
                            <p class="checkout-field">
                                <label for="billingState">State</label>
                                <?php $state_select('billingState', 'billing_state', 'address-level1', $prefill['billing_state'], 'required'); ?>
                            </p>
                            <p class="checkout-field">
                                <label for="billingZip">ZIP code</label>
                                <input type="text" id="billingZip" name="billing_zip" value="<?php echo esc_attr($prefill['billing_zip']); ?>" required autocomplete="postal-code" inputmode="numeric" maxlength="10">
                            </p>
                        </div>
                        <input type="hidden" name="billing_country" value="United States">
                    </div>

                    <div data-ship-only>
                    <label class="checkout-check">
                        <input type="checkbox" name="same_shipping_address" id="sameShippingAddress" checked>
                        Ship my <?php echo esc_html($noun); ?> to this address
                    </label>

                    <div class="checkout-shipping-address" id="shippingAddressFields" hidden>
                        <h3>Shipping address</h3>
                        <div class="checkout-grid">
                            <p class="checkout-field checkout-field--half">
                                <label for="shippingFirstName">First name</label>
                                <input type="text" id="shippingFirstName" name="shipping_fname" autocomplete="shipping given-name" data-ship-required>
                            </p>
                            <p class="checkout-field checkout-field--half">
                                <label for="shippingLastName">Last name</label>
                                <input type="text" id="shippingLastName" name="shipping_lname" autocomplete="shipping family-name" data-ship-required>
                            </p>
                            <p class="checkout-field checkout-field--full">
                                <label for="shippingCompany">Business name <span class="checkout-optional">(optional)</span></label>
                                <input type="text" id="shippingCompany" name="shipping_company" autocomplete="shipping organization">
                            </p>
                            <p class="checkout-field checkout-field--full">
                                <label for="shippingAddress">Street address</label>
                                <input type="text" id="shippingAddress" name="shipping_address" autocomplete="shipping address-line1" data-ship-required>
                                <button type="button" class="checkout-link" data-reveal="shippingAddress2">+ Add apartment, suite or unit</button>
                                <input type="text" id="shippingAddress2" name="shipping_address_2" autocomplete="shipping address-line2" placeholder="Apartment, suite, unit (optional)" aria-label="Shipping apartment, suite or unit" hidden>
                            </p>
                            <div class="checkout-grid checkout-grid--address checkout-field--full">
                                <p class="checkout-field checkout-field--city">
                                    <label for="shippingCity">City</label>
                                    <input type="text" id="shippingCity" name="shipping_city" autocomplete="shipping address-level2" data-ship-required>
                                </p>
                                <p class="checkout-field">
                                    <label for="shippingState">State</label>
                                    <?php $state_select('shippingState', 'shipping_state', 'shipping address-level1', '', 'data-ship-required'); ?>
                                </p>
                                <p class="checkout-field">
                                    <label for="shippingZip">ZIP code</label>
                                    <input type="text" id="shippingZip" name="shipping_zip" autocomplete="shipping postal-code" inputmode="numeric" maxlength="10" data-ship-required>
                                </p>
                            </div>
                            <p class="checkout-field checkout-field--full">
                                <label for="shippingTel">Phone at delivery address <span class="checkout-optional">(optional)</span></label>
                                <input type="tel" id="shippingTel" name="shipping_tel" autocomplete="shipping tel" inputmode="tel">
                            </p>
                            <input type="hidden" name="shipping_country" value="United States">
                        </div>
                    </div>
                    </div>

                    <label class="checkout-check checkout-check--quiet">
                        <input type="checkbox" id="rememberDetails" checked>
                        Remember my details on this device for next time
                    </label>
                </section>

                <section class="checkout-card" aria-labelledby="checkout-shipping">
                    <h2 id="checkout-shipping"><span class="checkout-step-number">2</span> Delivery</h2>

                    <?php if ($pickup && $shipping_methods) : ?>
                        <div class="checkout-delivery-toggle" role="radiogroup" aria-label="How you get your order">
                            <label class="checkout-delivery-choice">
                                <input type="radio" name="delivery_method" value="ship" checked>
                                <span class="checkout-delivery-choice__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M3 5a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v2h3.4a1 1 0 0 1 .8.4l2.6 3.5a1 1 0 0 1 .2.6V16a1 1 0 0 1-1 1h-1.2a3 3 0 0 1-5.6 0H9.8a3 3 0 0 1-5.6 0H4a1 1 0 0 1-1-1V5Zm12 4v6h.2a3 3 0 0 1 4.6-.9V11.8L17.9 9H15ZM7 15.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm10 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/></svg></span>
                                <span class="checkout-delivery-choice__text"><strong>Ship it</strong><small><?php echo esc_html(implode(' or ', $carriers)); ?> to any US address</small></span>
                            </label>
                            <label class="checkout-delivery-choice">
                                <input type="radio" name="delivery_method" value="pickup">
                                <span class="checkout-delivery-choice__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M4 4h16l1 5a3 3 0 0 1-2 2.8V20a1 1 0 0 1-1 1h-4v-5h-4v5H6a1 1 0 0 1-1-1v-8.2A3 3 0 0 1 3 9l1-5Zm1.6 2-.6 3a1 1 0 0 0 2 0V6H5.6Zm3.4 0v3a1 1 0 0 0 2 0V6H9Zm4 0v3a1 1 0 0 0 2 0V6h-2Zm4 0v3a1 1 0 0 0 2 0l-.6-3H17Z"/></svg></span>
                                <span class="checkout-delivery-choice__text"><strong>Store pickup</strong><small>Free &middot; <?php echo esc_html($pickup['city']); ?></small></span>
                            </label>
                        </div>
                    <?php else : ?>
                        <input type="hidden" name="delivery_method" value="<?php echo $shipping_methods || !$pickup ? 'ship' : 'pickup'; ?>">
                    <?php endif; ?>

                    <?php if ($shipping_methods) : ?>
                        <div class="checkout-delivery-panel" data-delivery-panel="ship">
                            <p class="checkout-muted">Every <?php echo esc_html('order' === $noun ? 'item' : $noun); ?> is made to order and ships by <strong><?php echo esc_html($ship_date); ?></strong>. Delivery dates include production time.</p>
                            <?php if ($free_shipping_min > 0 && !$free_shipping) : ?>
                                <p class="checkout-free-hint"><?php echo esc_html(sprintf('Add %s more to get free Ground shipping.', $money($free_shipping_min - $sub_total))); ?> <a href="<?php echo esc_url(home_url('/cart/')); ?>">Back to cart</a></p>
                            <?php endif; ?>

                            <input type="hidden" name="shipping_quote" value="<?php echo esc_attr(number_format($first_shipping, 2, '.', '')); ?>" data-shipping-quote>

                            <?php if (count($carriers) > 1) : ?>
                                <div class="checkout-carriers" role="radiogroup" aria-label="Carrier">
                                    <span class="checkout-carriers__label">Carrier</span>
                                    <?php foreach ($carriers as $carrier => $carrier_label) : ?>
                                        <label class="checkout-carrier-choice checkout-carrier-choice--<?php echo esc_attr($carrier); ?>">
                                            <input type="radio" name="carrier_view" value="<?php echo esc_attr($carrier); ?>" <?php checked($default_carrier, $carrier); ?>>
                                            <span><?php echo esc_html($carrier_label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="checkout-options checkout-rates" role="radiogroup" aria-labelledby="checkout-shipping">
                                <?php foreach ($method_speeds as $speed => $speed_methods) : ?>
                                    <?php foreach ($speed_methods as $method) : ?>
                                        <label class="checkout-option checkout-rate" data-carrier="<?php echo esc_attr($method['carrier']); ?>" data-speed="<?php echo esc_attr($speed); ?>">
                                            <input type="radio" name="shipping_method" value="<?php echo esc_attr($method['id']); ?>" <?php checked($default_method, $method['id']); ?> required data-shipping-amount="<?php echo esc_attr(number_format($method['amount'], 2, '.', '')); ?>" data-shipping-name="<?php echo esc_attr($method['label']); ?>">
                                            <span class="checkout-option-body">
                                                <strong><?php echo esc_html($method['group']); ?> <span class="checkout-rate__carrier checkout-rate__carrier--<?php echo esc_attr($method['carrier']); ?>"><?php echo esc_html($method['carrier_label']); ?></span></strong>
                                                <small><?php echo esc_html($method['label']); ?> &middot; <?php echo esc_html($method['note']); ?></small>
                                            </span>
                                            <span class="checkout-rate__eta">
                                                <small>Est. delivery</small>
                                                <strong><?php echo esc_html($method['delivery']); ?></strong>
                                            </span>
                                            <span class="checkout-option-price">
                                                <?php if ($method['free']) : ?>
                                                    <s><?php echo esc_html($money($method['regular'])); ?></s> FREE
                                                <?php else : ?>
                                                    <?php echo esc_html($money($method['amount'])); ?>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="checkout-blind">
                                <label class="checkout-blind__toggle">
                                    <input type="checkbox" name="blind_ship" value="1" id="blindShip">
                                    <span class="checkout-blind__text">
                                        <strong>Blind drop ship <span class="checkout-badge">Free</span></strong>
                                        <small>Ship straight to your client under your business name: your return address on the label, and no Storefront Sign branding, invoice or prices in the box.</small>
                                    </span>
                                </label>
                                <div class="checkout-blind__fields" id="blindShipFields" hidden>
                                    <div class="checkout-blind__head">
                                        <h3>Return address on the label</h3>
                                        <button type="button" class="checkout-link" data-blind-copy>Use my billing details</button>
                                    </div>
                                    <div class="checkout-grid">
                                        <p class="checkout-field checkout-field--full">
                                            <label for="blindFromCompany">Business name</label>
                                            <input type="text" id="blindFromCompany" name="blind_from_company" required autocomplete="off" value="<?php echo esc_attr($blind_from['company'] ?? ''); ?>">
                                        </p>
                                        <p class="checkout-field checkout-field--half">
                                            <label for="blindFromName">Contact name <span class="checkout-optional">(optional)</span></label>
                                            <input type="text" id="blindFromName" name="blind_from_name" autocomplete="off" value="<?php echo esc_attr($blind_from['name'] ?? ''); ?>">
                                        </p>
                                        <p class="checkout-field checkout-field--half">
                                            <label for="blindFromTel">Phone <span class="checkout-optional">(optional)</span></label>
                                            <input type="tel" id="blindFromTel" name="blind_from_tel" autocomplete="off" inputmode="tel" value="<?php echo esc_attr($blind_from['tel'] ?? ''); ?>">
                                        </p>
                                        <p class="checkout-field checkout-field--full">
                                            <label for="blindFromAddress">Street address</label>
                                            <input type="text" id="blindFromAddress" name="blind_from_address" required autocomplete="off" value="<?php echo esc_attr($blind_from['address'] ?? ''); ?>">
                                            <button type="button" class="checkout-link" data-reveal="blindFromAddress2">+ Add suite or unit</button>
                                            <input type="text" id="blindFromAddress2" name="blind_from_address_2" autocomplete="off" placeholder="Suite, unit (optional)" aria-label="Return address suite or unit" value="<?php echo esc_attr($blind_from['address_2'] ?? ''); ?>" hidden>
                                        </p>
                                        <div class="checkout-grid checkout-grid--address checkout-field--full">
                                            <p class="checkout-field checkout-field--city">
                                                <label for="blindFromCity">City</label>
                                                <input type="text" id="blindFromCity" name="blind_from_city" required autocomplete="off" value="<?php echo esc_attr($blind_from['city'] ?? ''); ?>">
                                            </p>
                                            <p class="checkout-field">
                                                <label for="blindFromState">State</label>
                                                <?php $state_select('blindFromState', 'blind_from_state', 'off', $blind_from['state'] ?? '', 'required'); ?>
                                            </p>
                                            <p class="checkout-field">
                                                <label for="blindFromZip">ZIP code</label>
                                                <input type="text" id="blindFromZip" name="blind_from_zip" required autocomplete="off" inputmode="numeric" maxlength="10" value="<?php echo esc_attr($blind_from['zip'] ?? ''); ?>">
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($pickup) : ?>
                        <div class="checkout-delivery-panel" data-delivery-panel="pickup"<?php echo $shipping_methods ? ' hidden' : ''; ?>>
                            <div class="checkout-pickup">
                                <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg>
                                <div>
                                    <strong>Pick up at our shop</strong>
                                    <p><?php echo esc_html($pickup['address']); ?> &middot; <a href="<?php echo esc_url($pickup['map']); ?>" target="_blank" rel="noopener">Directions</a></p>
                                    <?php if ($pickup['hours']) : ?><p>Pickup hours: <?php echo esc_html($pickup['hours']); ?></p><?php endif; ?>
                                    <p class="checkout-pickup__ready">Ready for pickup by <strong><?php echo esc_html($ship_date); ?></strong>. We&rsquo;ll email you when it&rsquo;s ready &mdash; bring your order number.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$shipping_methods && !$pickup) : ?>
                        <p class="checkout-alert" role="alert">Online shipping rates aren&rsquo;t available for this order. Please call <a href="tel:+18664362101">866-436-2101</a> and we&rsquo;ll quote delivery.</p>
                    <?php endif; ?>
                </section>

                <section class="checkout-card" aria-labelledby="checkout-payment">
                    <div class="checkout-card-head">
                        <h2 id="checkout-payment"><span class="checkout-step-number">3</span> Payment</h2>
                        <?php if ($card_enabled) : ?>
                            <p class="checkout-secure-tag"><?php echo $lock_icon; // Static SVG. ?> Secure &amp; encrypted</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($direct_card) : ?>
                        <div class="checkout-grid checkout-card-fields">
                            <p class="checkout-field checkout-field--full">
                                <label for="cardNumber">Card number</label>
                                <span class="checkout-card-input">
                                    <input type="text" id="cardNumber" name="card_number" required inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="1234 1234 1234 1234" spellcheck="false">
                                    <span class="checkout-card-brand" data-card-brand aria-live="polite"></span>
                                </span>
                            </p>
                            <p class="checkout-field checkout-field--half">
                                <label for="cardExp">Expiration date</label>
                                <input type="text" id="cardExp" name="card_exp" required inputmode="numeric" autocomplete="cc-exp" maxlength="7" placeholder="MM / YY">
                            </p>
                            <p class="checkout-field checkout-field--half">
                                <label for="cardCvv">Security code</label>
                                <input type="text" id="cardCvv" name="card_cvv" required inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="CVV" aria-describedby="cardCvvHint">
                                <small id="cardCvvHint">3 digits on the back (Amex: 4 on the front)</small>
                            </p>
                        </div>
                        <p class="checkout-cards">We accept Visa, Mastercard, American Express and Discover. Processed by Elavon Converge; we never store your card number.</p>
                        <?php $wallet_names = function_exists('wholesale_public_wallet_names') ? wholesale_public_wallet_names() : array(); ?>
                        <?php if ($wallet_names) : ?><p class="checkout-cards checkout-cards--wallets">Also accepted: <?php echo esc_html(implode(', ', $wallet_names)); ?>.</p><?php endif; ?>
                    <?php elseif ($card_enabled) : ?>
                        <div class="checkout-secure">
                            <?php echo $lock_icon; // Static SVG. ?>
                            <div>
                                <strong>Pay securely by card</strong>
                                <p>When you click <em>Pay</em>, a secure window from our card processor, Elavon Converge, opens for your card details. Your card number never passes through our website.</p>
                                <p class="checkout-cards">Visa &middot; Mastercard &middot; American Express &middot; Discover</p>
                                <?php $wallet_names = function_exists('wholesale_public_wallet_names') ? wholesale_public_wallet_names() : array(); ?>
                                <?php if ($wallet_names) : ?><p class="checkout-cards checkout-cards--wallets">Also accepted: <?php echo esc_html(implode(', ', $wallet_names)); ?>.</p><?php endif; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="checkout-secure">
                            <div>
                                <strong>No payment is taken online right now</strong>
                                <p>Place your order and our team will contact you to arrange payment before production starts.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <details class="checkout-more">
                        <summary>Add a note to your order <span class="checkout-optional">(optional)</span></summary>
                        <p class="checkout-field">
                            <label for="orderComment" class="screen-reader-text">Order notes</label>
                            <textarea name="comment" id="orderComment" rows="3" placeholder="<?php echo esc_attr('sign' === $noun ? 'Anything we should know about your sign, installation or delivery?' : "Anything we should know about your {$noun} or delivery?"); ?>"></textarea>
                        </p>
                    </details>

                    <?php if (!is_user_logged_in()) : ?>
                        <label class="checkout-check">
                            <input type="checkbox" name="create_account" value="1" id="createAccount">
                            Create an account to track this order
                        </label>
                        <p class="checkout-field" id="accountPasswordGroup" hidden>
                            <label for="accountPassword">Choose a password</label>
                            <input type="password" name="account_password" id="accountPassword" minlength="8" autocomplete="new-password" aria-describedby="accountPasswordHint">
                            <small id="accountPasswordHint">At least 8 characters.</small>
                        </p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                <div class="checkout-card checkout-summary-card">
                    <div class="checkout-card-head">
                        <h2 id="checkout-summary-title">Order summary</h2>
                        <a class="checkout-edit-cart" href="<?php echo esc_url(home_url('/cart/')); ?>">Edit cart</a>
                    </div>
                    <ul class="checkout-items">
                        <?php foreach ($cart->get_items() as $item) : ?>
                            <li>
                                <?php if (!empty($item->product_thumbnail)) : ?>
                                    <img src="<?php echo esc_url($item->product_thumbnail); ?>" alt="" width="56" height="56" loading="lazy">
                                <?php endif; ?>
                                <span class="checkout-item-name">
                                    <?php echo esc_html($item->product_title); ?>
                                    <small>Qty <?php echo esc_html($item->product_quantity); ?><?php echo !empty($item->job_name) ? ' &middot; ' . esc_html($item->job_name) : ''; ?></small>
                                </span>
                                <span class="checkout-item-price"><?php echo esc_html($money($item->product_subtotal)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($promo_live || $promo_result) : ?>
                        <div class="checkout-promo">
                            <?php if ($promo_applied) : ?>
                                <p class="checkout-promo__applied" role="status">
                                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                    <span>Code <strong><?php echo esc_html($promo_applied); ?></strong> applied: <?php echo $promo_by_code ? 'free standard shipping' : 'this order already ships free'; ?>.</span>
                                    <button type="submit" form="promoForm" name="promo_code" value="" class="checkout-promo__remove">Remove</button>
                                </p>
                            <?php elseif ($promo_live) : ?>
                                <label class="checkout-promo__label" for="promoCode">Promo code</label>
                                <span class="checkout-promo__row">
                                    <input type="text" id="promoCode" name="promo_code" form="promoForm" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="30" placeholder="Enter code"<?php echo 'invalid' === $promo_result ? ' aria-invalid="true" aria-describedby="promoMessage"' : ''; ?>>
                                    <button type="submit" form="promoForm" class="checkout-promo__apply">Apply</button>
                                </span>
                            <?php endif; ?>
                            <?php if ('invalid' === $promo_result && !$promo_applied) : ?>
                                <p class="checkout-promo__message is-error" id="promoMessage" role="alert">That code isn't valid. Check the spelling and try again.</p>
                            <?php elseif ('removed' === $promo_result && !$promo_applied) : ?>
                                <p class="checkout-promo__message" role="status">Promo code removed.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <dl class="checkout-totals" data-subtotal="<?php echo esc_attr($sub_total); ?>" data-tax="<?php echo esc_attr($tax); ?>">
                        <div><dt>Subtotal</dt><dd><?php echo esc_html($money($sub_total)); ?></dd></div>
                        <div><dt>Shipping <small class="checkout-totals__method" data-summary-shipping><?php echo $default_method ? esc_html($shipping_methods[$default_method]['label']) : ($pickup ? 'Store pickup' : ''); ?></small></dt><dd data-shipping-total><?php echo esc_html($first_shipping > 0 ? $money($first_shipping) : 'FREE'); ?></dd></div>
                        <div><dt>Tax (<?php echo esc_html(rtrim(rtrim(number_format($tax_rate, 2), '0'), '.')); ?>%)</dt><dd><?php echo esc_html($money($tax)); ?></dd></div>
                        <div class="checkout-grand"><dt>Total</dt><dd data-grand-total><?php echo esc_html($money($grand_total)); ?></dd></div>
                    </dl>

                    <p class="checkout-status" data-pay-status role="alert" hidden></p>

                    <button type="submit" class="checkout-pay" data-pay-button>
                        <?php if ($card_enabled) : ?>
                            <?php echo $lock_icon; // Static SVG. ?>
                            Pay <span data-grand-total><?php echo esc_html($money($grand_total)); ?></span>
                        <?php else : ?>
                            Place order
                        <?php endif; ?>
                    </button>

                    <ul class="checkout-assurance">
                        <?php if ($card_enabled) : ?><li>Secure card processing by Elavon Converge</li><?php endif; ?>
                        <li><?php echo 'sign' === $noun ? 'UL listed signs, tested before shipping' : 'Made to order and checked before shipping'; ?></li>
                        <li>Questions? <a href="tel:+18664362101">866-436-2101</a> &middot; Mon&ndash;Fri 8am&ndash;5pm PST</li>
                    </ul>
                    <p class="checkout-terms">By placing your order you agree to our <a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>">Terms &amp; Conditions</a>.</p>
                </div>
            </aside>
        </form>
        <?php // The promo code box's own form (its fields sit in the order summary with form="promoForm"). ?>
        <form id="promoForm" method="get" action="<?php echo esc_url(home_url('/checkout/')); ?>" hidden></form>
    </div>
</main>

<?php
get_footer();
