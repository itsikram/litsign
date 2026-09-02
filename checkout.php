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
$shipping_cost = array(12.5, 50, 62.5, 75);


if ($cart->have_items) {
    foreach ($cart->get_items() as $item) {
        $product_turnaround = get_post_meta($item->product_id, '_product_turnaround', true);

        if (intval($product_turnaround) > $turnaround) {
            $turnaround = $product_turnaround;
        }

        $get_category = get_the_terms($item->product_id, 'product_category');
        $category_slug = $get_category[0]->slug;

        if ($category_slug == 'channel-letters') {
            $product_category = $category_slug;
        }

?>
<?php
    }
}

if ($product_category == 'channel-letters') {
    $shipping_cost = array(50, 200, 250, 300);
}


// Create a DateTime object for the current date
$date = new DateTime();

// Add the custom number of days
$date->modify("+".$turnaround +6 ." days");

// Format the date to display like 'Wed Jul. 10'
$formatted_date = $date->format('D M. j');

$cart_subtotal = $cart->sub_total;
$total_tax = floatval(($cart_subtotal / 100) * 10.3);
if(empty($cart -> get_items())) {
    wp_redirect(site_url().'/cart?type=warning&message=Your Cart is currently empty.');

}

get_header();


?>

<div class="container my-3 checkout-container">
    <h2 class="text-center fs-2 mb-4">Secure Checkout</h2>
    <form action="<?php echo site_url(); ?>/payment" class="needs-validation">
        <input type="hidden" name="sub_total" value="<?php echo $cart->sub_total; ?>" id="subTotal">

        <input type="hidden" name="grand_total" value="<?php echo $cart->sub_total + intval($shipping_cost[0]) + $total_tax; ?>" data-sc="<?php echo intval($shipping_cost[0]); ?>" id="grandTotal">
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
                        <label for="billingCity" required class="form-label input-required">City</label>
                        <input type="text" id="billingCity" name="billing_city" class="form-control">
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
                        <input type="text" id="shippingCompany" class="form-control">
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
                        <input type="text" id="shippingCountry" name="shipping_country" class="form-control">
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
                    <?php echo $formatted_date; ?>
                </p>
                <div class="form-check">
                    <input class="form-check-input shipping-radio" type="radio" value="<?php echo $shipping_cost[0]; ?>" name="shipping_method" id="shippingMethod1" checked>
                    <label class="form-check-label" for="shippingMethod1">
                        <strong>$<?php echo $shipping_cost[0]; ?></strong> - Standard (3-6 Business Days + Manufacturing)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input shipping-radio" type="radio" name="shipping_method" value="<?php echo $shipping_cost[1]; ?>" id="shippingMethod2">
                    <label class="form-check-label" for="shippingMethod2">
                        <strong>$<?php echo $shipping_cost[1]; ?></strong> - 3Day + Manufacturing
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input shipping-radio" type="radio" name="shipping_method" value="<?php echo $shipping_cost[2]; ?>" id="shippingMethod3">
                    <label class="form-check-label" for="shippingMethod3">
                        <strong>$<?php echo $shipping_cost[2]; ?></strong> - 2Day + Manufacturing
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input shipping-radio" type="radio" value="<?php echo $shipping_cost[3]; ?>" name="shipping_method" id="shippingMethod4">
                    <label class="form-check-label" for="shippingMethod4">
                        <strong>$<?php echo $shipping_cost[3]; ?></strong> - Overnight + Manufacturing
                    </label>
                </div>

                <h3 class="fs-3 my-3">3. Payment Method</h3>

                <div class="card-details-container p-3">

                    <!-- card types -->
                    <div class="form-group">
                        <label for="cardType" class="form-label input-required">Select Card Type</label>
                        <select required name="card_type" id="cardType" class="form-control">
                            <option value="card-type">--Please Select--</option>
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
                                <input required type="text" name="card_number" id="cardNumber" class="form-control">
                            </div>
                        </div>
                        <div class="col-4">
                            <!-- card cvv  -->
                            <div class="form-group">
                                <label for="cardCvv" class="form-label input-required">CVV</label>
                                <input required type="text" name="card_cvv" id="cardCvv" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- card exp year and month -->
                    <div class="row">
                        <label for="cardExYear" class="form-lable input-required">Expiration Date</label>

                        <div class="col-6">
                            <select required name="card_exp_month" id="expMonth" class="form-control">
                                <option value="month">Month</option>
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
                                <option value="Year">Year</option>
                                <option value="24">2024</option>
                                <option value="25">2025</option>
                                <option value="26">2026</option>
                                <option value="27">2027</option>
                                <option value="28">2028</option>
                                <option value="29">2029</option>
                                <option value="30">2030</option>
                                <option value="31">2031</option>
                                <option value="32">2032</option>
                                <option value="33">2033</option>
                                <option value="34">2034</option>
                                <option value="35">2035</option>
                                <option value="36">2036</option>
                            </select>
                        </div>
                    </div>
                </div>
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
                                $product_turnaround = get_post_meta($item->product_id, '_product_turnaround', true);

                                if (intval($product_turnaround) > $turnaround) {
                                    $turnaround = $product_turnaround;
                                }

                                $get_category = get_the_terms($item->product_id, 'product_category');
                                $category_slug = $get_category[0]->slug;

                                if ($category_slug == 'channel-letters') {
                                    $product_category = $category_slug;
                                }

                        ?>
                                <tr class="border-bottom">
                                    <th scope="row text-truncate"><?php echo $item->product_title; ?></th>
                                    <td class="text-center"><?php echo $item->product_quantity; ?></td>
                                    <td class="text-end">$<?php echo   number_format($item->product_subtotal, 2, '.', ','); ?></td>
                                </tr>

                        <?php
                            }
                        } ?>




                    </tbody>

                </table>

                <div class="sub-total-container d-flex justify-content-between p-2">
                    <span class="fw-bold"> Subtotal</span>
                    <span> $<?php echo $cart->sub_total;// number_format($cart->sub_total, 2, '.', ','); ?> </span>
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



<?php
get_footer();
