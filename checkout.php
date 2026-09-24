<?php
// Template Name: Checkout


// if(!is_user_logged_in()) {
//     wp_redirect(site_url().'/login');
// }

if (file_exists(get_template_directory() . '/utils/Cart.php')) {
    require_once(get_template_directory() . '/utils/Cart.php');
}

global $cart;

$product_category = 'adhesive-products';
$turnaround = 1;
$shipping_cost = array_map('floatval', explode(',', wholesale_get_setting('standard_shipping_options')));


if ($cart->have_items) {
    foreach ($cart->get_items() as $item) {
        $product_turnaround = intval(get_post_meta($item->product_id, '_product_turnaround', true));

        if ($product_turnaround > $turnaround) {
            $turnaround = $product_turnaround;
        }

        // Must match the category check in payment.php, which validates the shipping rate.
        if (has_term('channel-letters', 'product_category', $item->product_id)) {
            $product_category = 'channel-letters';
        }
    }
}

if ($product_category == 'channel-letters') {
    $shipping_cost = array_map('floatval', explode(',', wholesale_get_setting('channel_shipping_options')));
}


// Create a DateTime object for the current date
$date = new DateTime();

// Add the custom number of days
$date->modify('+' . ($turnaround + 6) . ' days');

// Format the date to display like 'Wed Jul. 10'
$formatted_date = $date->format('D M. j');

$cart_subtotal = $cart->sub_total;
$total_tax = floatval(($cart_subtotal / 100) * floatval(wholesale_get_setting('tax_rate')));
if(empty($cart -> get_items())) {
    wp_safe_redirect(home_url('/cart/?type=warning&message=' . rawurlencode('Your cart is currently empty.')));
    exit;
}

get_header();


?>

<div class="container my-3 checkout-container">
    <h2 class="text-center fs-2 mb-4">Secure Checkout</h2>
    <form action="<?php echo esc_url(home_url('/payment')); ?>" method="post" class="needs-validation" id="checkoutForm">
        <?php wp_nonce_field('wholesale_place_order', 'wholesale_order_nonce'); ?>
        <input type="hidden" name="sub_total" value="<?php echo $cart->sub_total; ?>" id="subTotal">

        <input type="hidden" name="grand_total" value="<?php echo $cart->sub_total + floatval($shipping_cost[0]) + $total_tax; ?>" data-sc="<?php echo floatval($shipping_cost[0]); ?>" id="grandTotal">
        <input type="hidden" name="shipping_cost" value="<?php echo $shipping_cost[0]; ?>" id="shippingCost">
        <input type="hidden" name="total_tax" value="<?php echo $total_tax; ?>" id="totalTax">
        <input type="hidden" name="product_turnaround" value="<?php echo $turnaround; ?>" id="productTurnaround">
        <input type="hidden" name="product_category" value="<?php echo $product_category; ?>" id="productCategory">
        <input type="hidden" name="estimate_delivery_time" value="<?php echo $formatted_date; ?>" id="estimateDeliveryTime">

        <div class="row py-3">
            <div class="col-md-4 border-right">

                <!-- billing address  -->
                <div class="billing-address-container">
                    <h3 class="fs-3">1. Billing Address</h3>
                    <!-- email -->

                    <div class="form-group">
                        <label for="billingEmail" class="form-label input-required">Biling Email</label>
                        <input type="email" id="billingEmail" required name="billing_email" class="form-control">
                    </div>
                    <!-- first and last name -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="billingFirstName" class="form-label input-required">First Name</label>
                                <input type="text" class="form-control" required name="billing_fname" id="billingFirstName">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="billingLastName" class="form-label input-required">Last Name</label>
                                <input type="text" class="form-control" required name="billing_lname" id="billingLastName">
                            </div>
                        </div>
                    </div>

                    <!-- company -->
                    <div class="form-group">
                        <label for="billingCompany" class="form-label input-required">Company</label>
                        <input type="text" id="billingCompany" name="billing_company" required class="form-control">
                    </div>

                    <!-- address -->
                    <div class="form-group">
                        <label for="billingAddress" class="form-label input-required">Address</label>
                        <input type="text" id="billingAddress" required name="billing_address" class="form-control">
                        <input type="text" id="billingAddress2" name="billing_address_2" class="form-control mt-1">
                    </div>

                    <!-- city -->
                    <div class="form-group">
                        <label for="billingCity" class="form-label input-required">City</label>
                        <input type="text" id="billingCity" required name="billing_city" class="form-control">
                    </div>
                    <!-- state and zip -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="billingState" class="form-label input-required">State</label>
                                <input type="text" required class="form-control" name="billing_state" id="billingState">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="billingZip" class="form-label input-required">Zip</label>
                                <input type="text" required class="form-control" name="billing_zip" id="billingZip">
                            </div>
                        </div>
                    </div>

                    <!-- country -->
                    <div class="form-group">
                        <label for="billingCountry" class="form-label input-required">Country</label>
                        <input type="text" required readonly value="United States" id="billingCountry" name="billing_country" class="form-control">
                    </div>

                    <!-- telephone -->
                    <div class="form-group">
                        <label for="billingTel" class="form-label input-required">Telephone</label>
                        <input type="tel" id="billingTel" name="billing_tel" class="form-control">
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" name="same_shipping_address" type="checkbox" checked id="sameShippingAddress">
                        <label class="form-check-label" for="sameShippingAddress">
                            Ship to the same address
                        </label>
                    </div>
                    <?php if (!is_user_logged_in()) : ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" name="create_account" type="checkbox" value="1" id="createAccount">
                            <label class="form-check-label" for="createAccount">
                                Create an account for faster checkout next time
                            </label>
                        </div>
                        <div id="accountPasswordGroup" class="form-group mt-2" hidden>
                            <label for="accountPassword" class="form-label">Account Password</label>
                            <input type="password" name="account_password" id="accountPassword" class="form-control" minlength="8" autocomplete="new-password">
                            <small class="form-text text-muted">Use at least 8 characters.</small>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Shipping Address  -->
                <div class="shipping-address-container mt-3">
                    <h3 class="fs-3">Shipping Address</h3>

                    <!-- first and last name -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="shippingFirstName" class="form-label input-required">First Name</label>
                                <input type="text" class="form-control" name="shipping_fname" id="shippingFirstName">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="shippingLastName" class="form-label input-required">Last Name</label>
                                <input type="text" class="form-control" name="shipping_lname" id="shippingLastName">
                            </div>
                        </div>
                    </div>

                    <!-- company -->
                    <div class="form-group">
                        <label for="shippingCompany" class="form-label input-required">Company</label>
                        <input type="text" id="shippingCompany" name="shipping_company" class="form-control">
                    </div>

                    <!-- address -->
                    <div class="form-group">
                        <label for="shippingAddress" class="form-label input-required">Address</label>
                        <input type="text" id="shippingAddress" name="shipping_address" class="form-control">
                        <input type="text" id="shippingAddress2" name="shipping_address_2" class="form-control mt-1">
                    </div>

                    <!-- city -->
                    <div class="form-group">
                        <label for="shippingCity" class="form-label input-required">City</label>
                        <input type="text" id="shippingCity" name="shipping_city" class="form-control">
                    </div>
                    <!-- state and zip -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="shippingState" class="form-label input-required">State</label>
                                <input type="text" class="form-control" name="shipping_state" id="shippingState">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="shippingZip" class="form-label input-required">Zip</label>
                                <input type="text" class="form-control" name="shipping_zip" id="shippingZip">
                            </div>
                        </div>
                    </div>

                    <!-- country -->
                    <div class="form-group">
                        <label for="shippingCountry" class="form-label input-required">Country</label>
                        <input type="text" id="shippingCountry" name="shipping_country" value="United States" class="form-control">
                    </div>

                    <!-- telephone -->
                    <div class="form-group">
                        <label for="shippingTel" class="form-label input-required">Telephone</label>
                        <input type="tel" id="shippingTel" name="shipping_tel" class="form-control">
                    </div>
                </div>
            </div>

            <div class="col-md-4 border-right">
                <h3 class="fs-3">2. Shipping Option</h3>
                <p id="estimateDeliveryText" class="text-muted">
                    Order in the next 12 hrs and your order will ship by 
                    <?php echo esc_html($formatted_date); ?>
                </p>
                <?php
                $shipping_labels = array(
                    'Standard (3-6 Business Days + Manufacturing)',
                    '3Day + Manufacturing',
                    '2Day + Manufacturing',
                    'Overnight + Manufacturing',
                );
                foreach (array_values($shipping_cost) as $shipping_index => $shipping_amount) :
                    $shipping_label = isset($shipping_labels[$shipping_index]) ? $shipping_labels[$shipping_index] : 'Shipping option ' . ($shipping_index + 1);
                    ?>
                    <div class="form-check">
                        <input class="form-check-input shipping-radio" type="radio" value="<?php echo esc_attr($shipping_amount); ?>" name="shipping_method" id="shippingMethod<?php echo esc_attr($shipping_index + 1); ?>" <?php checked(0, $shipping_index); ?>>
                        <label class="form-check-label" for="shippingMethod<?php echo esc_attr($shipping_index + 1); ?>">
                            <strong>$<?php echo esc_html(number_format($shipping_amount, 2, '.', ',')); ?></strong> - <?php echo esc_html($shipping_label); ?>
                        </label>
                    </div>
                <?php endforeach; ?>

                <h3 class="fs-3 my-3">3. Payment Method</h3>

                <?php if (wholesale_setting_enabled('payment_disabled')) : ?>
                    <div class="alert alert-info" role="status">
                        Payment is temporarily unavailable. You can place your order now and we will contact you about payment.
                    </div>
                <?php else : ?>
                <div class="card-details-container p-3">

                    <!-- card types -->
                    <div class="form-group">
                        <label for="cardType" class="form-label input-required">Select Card Type</label>
                        <select required name="card_type" id="cardType" class="form-control">
                            <option value="">--Please Select--</option>
                            <option value="amarican-express">Amarican Express</option>
                            <option value="visa">Visa</option>
                            <option value="mastercard">MasterCard</option>
                            <option value="discover">Discover</option>

                        </select>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <!-- card number  -->
                            <div class="form-group">
                                <label for="cardNumber" class="form-label input-required">Card Number</label>
                                <input required type="text" name="card_number" id="cardNumber" class="form-control" inputmode="numeric" autocomplete="cc-number" pattern="[0-9 ]{12,23}" maxlength="23">
                            </div>
                        </div>
                        <div class="col-4">
                            <!-- card cvv  -->
                            <div class="form-group">
                                <label for="cardCvv" class="form-label input-required">CVV</label>
                                <input required type="text" name="card_cvv" id="cardCvv" class="form-control" inputmode="numeric" autocomplete="cc-csc" pattern="[0-9]{3,4}" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <!-- card exp year and month -->
                    <div class="row">
                        <label for="cardExYear" class="form-lable input-required">Expiration Date</label>

                        <div class="col-6">
                            <select required name="card_exp_month" id="expMonth" class="form-control">
                                <option value="">Month</option>
                                <option value="01">01 - January</option>
                                <option value="02">02 - February</option>
                                <option value="03">03 - March</option>
                                <option value="04">04 - April</option>
                                <option value="05">05 - May</option>
                                <option value="06">06 - June</option>
                                <option value="07">07 - July</option>
                                <option value="08">08 - Augest</option>
                                <option value="09">09 - September</option>
                                <option value="10">10 - Octeber</option>
                                <option value="11">11 - November</option>
                                <option value="12">12 - December</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <select required name="card_exp_year" id="expYear" class="form-control">
                                <option value="">Year</option>
                                <?php for ($exp_year = (int) gmdate('Y'); $exp_year <= (int) gmdate('Y') + 12; $exp_year++) : ?>
                                    <option value="<?php echo esc_attr(substr((string) $exp_year, -2)); ?>"><?php echo esc_html($exp_year); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <h3 class="fs-3"> 4. Review Your Order</h3>

                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Qty</th>
                            <th scope="col">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cart->have_items) {
                            foreach ($cart->get_items() as $item) {
                        ?>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-truncate"><?php echo esc_html($item->product_title); ?></th>
                                    <td class="text-center"><?php echo esc_html($item->product_quantity); ?></td>
                                    <td class="text-end">$<?php echo   number_format($item->product_subtotal, 2, '.', ','); ?></td>
                                </tr>

                        <?php
                            }
                        } ?>




                    </tbody>

                </table>

                <div class="sub-total-container d-flex justify-content-between p-2">
                    <span class="fw-bold"> Subtotal</span>
                    <span> $<?php echo number_format($cart->sub_total, 2, '.', ','); ?> </span>
                </div>
                <div class="shipping-container border-top d-flex justify-content-between p-2">
                    <span class="fw-bold"> Shipping</span>
                    <span class="shipping-cost-holder"> $<?php echo number_format($shipping_cost[0], 2, '.', ','); ?></span>
                </div>
                <div class="tax-container border-top d-flex justify-content-between p-2">
                    <span class="fw-bold"> Tax</span>
                    <span class="tax-holder"> $<?php echo number_format($total_tax, 2, '.', ','); ?></span>
                </div>

                <div class="grand-total-container border-top d-flex justify-content-between p-2">
                    <span class="fw-bold"> Grand Total</span>
                    <span class="grand-total-holder"> $<?php echo number_format($cart->sub_total + $shipping_cost[0] + $total_tax, 2, '.', ','); ?> </span>
                </div>

                <div class="form-group p-2">
                    <label for="orderComment" class="form-label fw-bold">Comment</label>
                    <textarea name="comment" id="orderComment" cols="30" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-group text-center">
                    <input type="submit" id="placeOrder" value="Place Order Now" class="btn btn-danger mt-2">

                </div>
            </div>
        </div>
    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('checkoutForm');
        var button = document.getElementById('placeOrder');

        if (!form || !button) {
            return;
        }

        // Prevent a second click from submitting (and charging) the order twice.
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = '1';
            button.disabled = true;
            button.value = 'Placing Order...';
        });

        // Restore the button if the visitor comes back via the browser's back button.
        window.addEventListener('pageshow', function () {
            form.dataset.submitting = '';
            button.disabled = false;
            button.value = 'Place Order Now';
        });
    });
</script>

<?php if (!is_user_logged_in()) : ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkbox = document.getElementById('createAccount');
            var passwordGroup = document.getElementById('accountPasswordGroup');
            var password = document.getElementById('accountPassword');

            if (!checkbox || !passwordGroup || !password) {
                return;
            }

            checkbox.addEventListener('change', function () {
                passwordGroup.hidden = !checkbox.checked;
                password.required = checkbox.checked;
            });
        });
    </script>
<?php endif; ?>


<?php
get_footer();
