<?php
// Template Name: Checkout

$cart = wholesale_get_cart();

if (empty($cart->get_items())) {
    wp_safe_redirect(home_url('/cart/?type=warning&message=' . rawurlencode('Your cart is currently empty.')));
    exit;
}

$shipping_options = wholesale_cart_shipping_options($cart);
$sub_total = round((float) $cart->sub_total, 2);
$tax_rate = (float) wholesale_get_setting('tax_rate');
$tax = round($sub_total * $tax_rate / 100, 2);
$first_shipping = $shipping_options ? $shipping_options[0] : 0;
$card_enabled = !wholesale_setting_enabled('payment_disabled');
$direct_card = $card_enabled && 'direct' === wholesale_payment_method();
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
                        <span class="checkout-mobile-summary__label"><?php echo esc_html(sprintf(_n('%d item', '%d items', $item_count, 'litsign'), $item_count)); ?> &middot; <span data-show-label>Show summary</span></span>
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

                    <label class="checkout-check">
                        <input type="checkbox" name="same_shipping_address" id="sameShippingAddress" checked>
                        Ship my sign to this address
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

                    <label class="checkout-check checkout-check--quiet">
                        <input type="checkbox" id="rememberDetails" checked>
                        Remember my details on this device for next time
                    </label>
                </section>

                <section class="checkout-card" aria-labelledby="checkout-shipping">
                    <h2 id="checkout-shipping"><span class="checkout-step-number">2</span> Shipping speed</h2>
                    <p class="checkout-muted">Every sign is made to order. Dates include production time.</p>
                    <div class="checkout-options" role="radiogroup" aria-labelledby="checkout-shipping">
                        <?php foreach ($shipping_options as $index => $amount) : ?>
                            <label class="checkout-option">
                                <input type="radio" name="shipping_method" value="<?php echo esc_attr($amount); ?>" <?php checked(0, $index); ?> required data-shipping-amount="<?php echo esc_attr($amount); ?>">
                                <span class="checkout-option-body">
                                    <strong><?php echo esc_html(wholesale_shipping_label($index)); ?></strong>
                                    <small>Ships by <?php echo esc_html(wholesale_estimated_ship_date($cart, $index)); ?></small>
                                </span>
                                <span class="checkout-option-price"><?php echo esc_html($money($amount)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
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
                    <?php elseif ($card_enabled) : ?>
                        <div class="checkout-secure">
                            <?php echo $lock_icon; // Static SVG. ?>
                            <div>
                                <strong>Pay securely by card</strong>
                                <p>When you click <em>Pay</em>, a secure window from our card processor, Elavon Converge, opens for your card details. Your card number never passes through our website.</p>
                                <p class="checkout-cards">Visa &middot; Mastercard &middot; American Express &middot; Discover</p>
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
                            <textarea name="comment" id="orderComment" rows="3" placeholder="Anything we should know about your sign, installation or delivery?"></textarea>
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

                    <dl class="checkout-totals" data-subtotal="<?php echo esc_attr($sub_total); ?>" data-tax="<?php echo esc_attr($tax); ?>">
                        <div><dt>Subtotal</dt><dd><?php echo esc_html($money($sub_total)); ?></dd></div>
                        <div><dt>Shipping</dt><dd data-shipping-total><?php echo esc_html($money($first_shipping)); ?></dd></div>
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
                        <li>UL listed signs, tested before shipping</li>
                        <li>Questions? <a href="tel:+18664362101">866-436-2101</a> &middot; Mon&ndash;Fri 8am&ndash;5pm PST</li>
                    </ul>
                    <p class="checkout-terms">By placing your order you agree to our <a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>">Terms &amp; Conditions</a>.</p>
                </div>
            </aside>
        </form>
    </div>
</main>

<?php
get_footer();
