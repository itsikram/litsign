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
                <section class="checkout-card" aria-labelledby="checkout-contact">
                    <h2 id="checkout-contact"><span class="checkout-step-number">1</span> Contact</h2>
                    <div class="checkout-grid">
                        <p class="checkout-field checkout-field--full">
                            <label for="billingEmail">Email</label>
                            <input type="email" id="billingEmail" name="billing_email" required autocomplete="email" value="<?php echo esc_attr($prefill['billing_email']); ?>">
                            <small>We'll send your order confirmation here.</small>
                        </p>
                        <p class="checkout-field">
                            <label for="billingTel">Phone <span class="checkout-optional">(recommended)</span></label>
                            <input type="tel" id="billingTel" name="billing_tel" autocomplete="tel" value="<?php echo esc_attr($prefill['billing_tel']); ?>">
                        </p>
                        <p class="checkout-field">
                            <label for="billingCompany">Business name <span class="checkout-optional">(optional)</span></label>
                            <input type="text" id="billingCompany" name="billing_company" autocomplete="organization">
                        </p>
                    </div>
                    <?php if (!is_user_logged_in()) : ?>
                        <p class="checkout-signin">Already have an account? <a href="<?php echo esc_url(add_query_arg('redirect_ulr', rawurlencode(home_url('/checkout/')), home_url('/login/'))); ?>">Log in</a> for faster checkout.</p>
                    <?php endif; ?>
                </section>

                <section class="checkout-card" aria-labelledby="checkout-billing">
                    <h2 id="checkout-billing"><span class="checkout-step-number">2</span> Billing address</h2>
                    <div class="checkout-grid">
                        <p class="checkout-field">
                            <label for="billingFirstName">First name</label>
                            <input type="text" id="billingFirstName" name="billing_fname" required autocomplete="given-name" value="<?php echo esc_attr($prefill['billing_fname']); ?>">
                        </p>
                        <p class="checkout-field">
                            <label for="billingLastName">Last name</label>
                            <input type="text" id="billingLastName" name="billing_lname" required autocomplete="family-name" value="<?php echo esc_attr($prefill['billing_lname']); ?>">
                        </p>
                        <p class="checkout-field checkout-field--full">
                            <label for="billingAddress">Street address</label>
                            <input type="text" id="billingAddress" name="billing_address" value="<?php echo esc_attr($prefill['billing_address']); ?>" required autocomplete="address-line1">
                            <input type="text" id="billingAddress2" name="billing_address_2" value="<?php echo esc_attr($prefill['billing_address_2']); ?>" autocomplete="address-line2" placeholder="Suite, unit, floor (optional)" aria-label="Address line 2">
                        </p>
                        <p class="checkout-field">
                            <label for="billingCity">City</label>
                            <input type="text" id="billingCity" name="billing_city" value="<?php echo esc_attr($prefill['billing_city']); ?>" required autocomplete="address-level2">
                        </p>
                        <div class="checkout-grid checkout-grid--pair">
                            <p class="checkout-field">
                                <label for="billingState">State</label>
                                <input type="text" id="billingState" name="billing_state" value="<?php echo esc_attr($prefill['billing_state']); ?>" required autocomplete="address-level1" maxlength="30">
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
                        Ship to this address
                    </label>

                    <div class="checkout-shipping-address" id="shippingAddressFields" hidden>
                        <h3>Shipping address</h3>
                        <div class="checkout-grid">
                            <p class="checkout-field">
                                <label for="shippingFirstName">First name</label>
                                <input type="text" id="shippingFirstName" name="shipping_fname" autocomplete="shipping given-name" data-ship-required>
                            </p>
                            <p class="checkout-field">
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
                                <input type="text" id="shippingAddress2" name="shipping_address_2" autocomplete="shipping address-line2" placeholder="Suite, unit, floor (optional)" aria-label="Shipping address line 2">
                            </p>
                            <p class="checkout-field">
                                <label for="shippingCity">City</label>
                                <input type="text" id="shippingCity" name="shipping_city" autocomplete="shipping address-level2" data-ship-required>
                            </p>
                            <div class="checkout-grid checkout-grid--pair">
                                <p class="checkout-field">
                                    <label for="shippingState">State</label>
                                    <input type="text" id="shippingState" name="shipping_state" autocomplete="shipping address-level1" maxlength="30" data-ship-required>
                                </p>
                                <p class="checkout-field">
                                    <label for="shippingZip">ZIP code</label>
                                    <input type="text" id="shippingZip" name="shipping_zip" autocomplete="shipping postal-code" inputmode="numeric" maxlength="10" data-ship-required>
                                </p>
                            </div>
                            <p class="checkout-field">
                                <label for="shippingTel">Phone at delivery address <span class="checkout-optional">(optional)</span></label>
                                <input type="tel" id="shippingTel" name="shipping_tel" autocomplete="shipping tel">
                            </p>
                            <input type="hidden" name="shipping_country" value="United States">
                        </div>
                    </div>
                </section>

                <section class="checkout-card" aria-labelledby="checkout-shipping">
                    <h2 id="checkout-shipping"><span class="checkout-step-number">3</span> Shipping speed</h2>
                    <p class="checkout-muted">Every sign is made to order. Dates include production time.</p>
                    <div class="checkout-options" role="radiogroup" aria-labelledby="checkout-shipping">
                        <?php foreach ($shipping_options as $index => $amount) : ?>
                            <label class="checkout-option">
                                <input type="radio" name="shipping_method" value="<?php echo esc_attr($amount); ?>" <?php checked(0, $index); ?> required data-shipping-amount="<?php echo esc_attr($amount); ?>">
                                <span class="checkout-option-body">
                                    <strong><?php echo esc_html(wholesale_shipping_label($index)); ?></strong>
                                    <small>Estimated to ship by <?php echo esc_html(wholesale_estimated_ship_date($cart, $index)); ?></small>
                                </span>
                                <span class="checkout-option-price"><?php echo esc_html($money($amount)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="checkout-card" aria-labelledby="checkout-payment">
                    <h2 id="checkout-payment"><span class="checkout-step-number">4</span> Payment</h2>
                    <?php if ($card_enabled) : ?>
                        <div class="checkout-secure">
                            <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M12 1a5 5 0 0 0-5 5v3H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-2V6a5 5 0 0 0-5-5Zm-3 5a3 3 0 0 1 6 0v3H9V6Z"/></svg>
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

                    <p class="checkout-field checkout-field--full">
                        <label for="orderComment">Order notes <span class="checkout-optional">(optional)</span></label>
                        <textarea name="comment" id="orderComment" rows="3" placeholder="Anything we should know about your sign, installation or delivery?"></textarea>
                    </p>

                    <?php if (!is_user_logged_in()) : ?>
                        <label class="checkout-check">
                            <input type="checkbox" name="create_account" value="1" id="createAccount">
                            Create an account to track this order
                        </label>
                        <p class="checkout-field" id="accountPasswordGroup" hidden>
                            <label for="accountPassword">Choose a password</label>
                            <input type="password" name="account_password" id="accountPassword" minlength="8" autocomplete="new-password">
                            <small>At least 8 characters.</small>
                        </p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                <div class="checkout-card checkout-summary-card">
                    <h2 id="checkout-summary-title">Order summary</h2>
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
                        <div class="checkout-grand"><dt>Total</dt><dd data-grand-total><?php echo esc_html($money($sub_total + $first_shipping + $tax)); ?></dd></div>
                    </dl>

                    <p class="checkout-status" data-pay-status role="alert" hidden></p>

                    <button type="submit" class="checkout-pay" data-pay-button>
                        <?php if ($card_enabled) : ?>
                            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 1a5 5 0 0 0-5 5v3H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-2V6a5 5 0 0 0-5-5Zm-3 5a3 3 0 0 1 6 0v3H9V6Z"/></svg>
                            Pay <span data-grand-total><?php echo esc_html($money($sub_total + $first_shipping + $tax)); ?></span>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('checkoutForm');
        if (!form) return;

        var money = function (value) {
            return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        // Separate shipping address.
        var same = document.getElementById('sameShippingAddress');
        var shipFields = document.getElementById('shippingAddressFields');
        var syncShipping = function () {
            shipFields.hidden = same.checked;
            shipFields.querySelectorAll('[data-ship-required]').forEach(function (input) {
                input.required = !same.checked;
            });
        };
        same.addEventListener('change', syncShipping);
        syncShipping();

        // Totals follow the chosen shipping speed.
        var totals = form.querySelector('.checkout-totals');
        var updateTotals = function () {
            var chosen = form.querySelector('input[name="shipping_method"]:checked');
            var shipping = chosen ? parseFloat(chosen.dataset.shippingAmount) : 0;
            var grand = parseFloat(totals.dataset.subtotal) + shipping + parseFloat(totals.dataset.tax);
            form.querySelector('[data-shipping-total]').textContent = money(shipping);
            form.querySelectorAll('[data-grand-total]').forEach(function (el) { el.textContent = money(grand); });
        };
        form.querySelectorAll('input[name="shipping_method"]').forEach(function (radio) {
            radio.addEventListener('change', updateTotals);
        });

        // Optional account.
        var createAccount = document.getElementById('createAccount');
        if (createAccount) {
            var group = document.getElementById('accountPasswordGroup');
            var password = document.getElementById('accountPassword');
            createAccount.addEventListener('change', function () {
                group.hidden = !createAccount.checked;
                password.required = createAccount.checked;
            });
        }

        // Browser validation messages for the no-JS-payment path.
        form.noValidate = false;
    });
</script>

<?php
get_footer();
