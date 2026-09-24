<?php
// Template Name: Cart


// if(!is_user_logged_in()) {
//     wp_redirect(site_url().'/login');
// }

if (file_exists(get_template_directory() . '/utils/Cart.php')) {
    require_once(get_template_directory() . '/utils/Cart.php');
}
global $cart;

//print_r($_REQUEST);

//echo $_COOKIE['cart_items'];
//setcookie('cart_items', '[]' , time() + (7 * 24 * 60 * 60 * 60), '/');

function formate_price($price)
{
    if ($price) {
        return number_format($price, 2, '.', ',');
    }
}

if(isset($_REQUEST['remove_cart'])){
    $cart_id = sanitize_text_field(wp_unslash($_REQUEST['remove_cart']));
    $cart -> remove_item($cart_id);
    wp_safe_redirect(get_permalink());
    exit;
}

if(isset($_REQUEST['update_quantity'])){
    $quantity = absint($_REQUEST['update_quantity']);
    $cart_id = isset($_REQUEST['cart_id']) ? sanitize_text_field(wp_unslash($_REQUEST['cart_id'])) : '';
    $cart ->update_quantity($cart_id, $quantity);
    wp_safe_redirect(get_permalink());
    exit;
}

if(isset($_REQUEST['product_id'])){
    $cart -> add_item();
}


$cart_subtotal = 0;

$product_category = 'adhesive-products';
$turnaround = 1;
$shipping_cost = array_map('floatval', explode(',', wholesale_get_setting('standard_shipping_options')));


if ($cart->have_items) {
    foreach ($cart->get_items() as $item) {
        if (has_term('channel-letters', 'product_category', $item->product_id)) {
            $product_category = 'channel-letters';
        }
    }
}

if ($product_category == 'channel-letters') {
    $shipping_cost = array_map('floatval', explode(',', wholesale_get_setting('channel_shipping_options')));
}


//echo '<pre>';
//print_r($cart -> remove_item(209))
get_header();


?>

<div class="container my-5 cart-page">
    <div class="row  text-center">
        <div class="col">
            <h2 class="fs-2">
                Shopping Cart
            </h2>
        </div>
    </div>
    <div class="row my-3 text-center">
        <div class="col-md-6 offset-md-3 d-inline-block text-center padding-mobile-0">
            <a href="<?php echo home_url(); ?>" class="btn btn-secondary px-5 mb-2 fw-bold py-2">Countinue Shopping</a>
            <?php if ($cart->have_items) : ?>
                <a href="<?php echo home_url() . '/checkout'; ?>" class="btn mb-2 btn-danger checkout-button fw-bold px-5 py-2">Procced to Checkout</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($cart->have_items) { ?>
    <div class="row my-5">
        <div class="col padding-mobile-0">

            <?php foreach ($cart->get_items() as $cart_item) {
                $cart_subtotal = $cart_subtotal + $cart_item->product_subtotal;
                
                $product_design_id = $cart_item ->  design_id > 0 ? $cart_item ->  design_id : 0;
                $product_cl_data = get_post_meta($product_design_id, '_cl_data', true);
                $product_cl_data_array = array();
                if ($product_cl_data) {
                    $product_cl_data_array = json_decode(stripslashes($product_cl_data), true);
                }
                if (!is_array($product_cl_data_array) || empty($product_cl_data_array['elements']) || !is_array($product_cl_data_array['elements'])) {
                    $product_cl_data = '';
                }
                $is_channel_letter_product = has_term('channel-letters', 'product_category', $cart_item->product_id);
                $edit_design_url = $is_channel_letter_product && $product_design_id
                    ? trailingslashit(get_permalink($cart_item->product_id)) . 'channel-letter-builder/?' . http_build_query(array(
                        'product_id' => absint($cart_item->product_id),
                        'edit_design' => 'true',
                    ))
                    : '';
            ?>
                <div class="cart-item border p-2">
                    <div class="row">
                        <div class="col-md-3 cart-image">
                            <img class="cart-item-image w-100" src="<?php echo esc_url($cart_item->product_thumbnail); ?>" alt="<?php echo esc_attr($cart_item->product_title); ?>">
                        </div>
                        <div class="col-md-9 ">
                            <div class="cart-title-container d-flex justify-content-between align-self-start border-bottom">
                                <h4 class="fs-4 align-self-center"><?php echo esc_html($cart_item->product_title); ?></h4>
                                <div class="cart-item-actions align-self-center">
                                    <?php if ($edit_design_url) : ?>
                                        <a href="<?php echo esc_url($edit_design_url); ?>" class="btn btn-link text-primary">Edit Design</a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url(add_query_arg('remove_cart', $cart_item->cart_id, get_permalink())); ?>" class="btn btn-link text-danger">Remove</a>

                                </div>
                            </div>
                            <div class="cart-info-container d-flex justify-content-between border-bottom py-2">
                                <span class="text-primary cart-details-toggler cursor-pinter">+ Details</span>
                                <div class="cart-item-price">
                                    <span class="d-inline-block fw-bold" style="margin-right: 90px">Item Price</span>
                                    <span>$<?php echo  number_format($cart_item->product_subtotal / max(1, (int) $cart_item->product_quantity),2,'.',','); ?></span>
                                </div>
                            </div>
                            <div class="cart-details-container border-bottom py-2">
                                <div>

                                    <?php foreach ($cart_item->product_details as $name => $value) {

                                        if($value == null){
                                            continue;

                                        }

                                    ?>
                                        <strong><?php echo esc_html($name); ?>: </strong><?php echo wholesale_format_order_detail_value($name, $value); ?> <br>

                                      <?php } ?>
                                      <?php  if($cart_item -> job_name) {
                                            ?>
                                            <strong><?php echo 'Job Name' ?>: </strong><?php echo esc_html($cart_item->job_name); ?> <br>
                                            <?php
                                        }?>
                                </div>
                            </div>
                            <div class="cart-item-quantity-container d-flex justify-content-end py-2 border-bottom">
                                <div class="d-flex">
                                    <label class="d-inline-block fw-bold" style="margin-right: 20px">Quantity</label>
                                    <div class="quantiy-input-container">
                                        <form action="">
                                        <input type="hidden" name="cart_id" value="<?php echo esc_attr($cart_item->cart_id); ?>">
                                        <input type="number" min="1" step="1" required value="<?php echo esc_attr(max(1, (int) $cart_item->product_quantity)); ?>" name="update_quantity" style="width: 60px">
                                        <input type="submit" value="Update">
                                        </form>

                                    </div>
                                </div>
                            </div>

                            <div class="cart-total-price-container d-flex justify-content-end  border-bottom border-top py-2">
                                <div class="cart-item-total-price">
                                    <span class="d-inline-block fw-bold" style="margin-right: 90px">Total Price</span>
                                    <span>$<?php echo  number_format($cart_item->product_subtotal,2,'.',','); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <?php if ($product_cl_data) { ?>
                        <div class="row mt-3 px-2 border-top">
                            <h4 class="fs-4 text-center py-2">Channel Letter Elements Details</h4>
                            <div class="responsive-table">
                                <table class="table table-bordered text-center">
                                    <thead>
                                        <tr>
                                            <th scope="col">Index</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Dimension</th>
                                            <th scope="col">Text</th>
                                            <th scope="col">Font</th>
                                            <th scope="col">Face Color</th>
                                            <th scope="col">Return Color</th>
                                            <th scope="col">Trimcap Color</th>
                                            <th scope="col">Return Size</th>
                                            <th scope="col">Trimcap Size</th>
                                            <th scope="col">Radius</th>
                                            <th scope="col">Cost</th>
                                            <th scope="col">Face Cost</th>
                                            <th scope="col">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php
                                        $total_element_cost = 0;
                                        $total_objects = count($product_cl_data_array['elements']);
                                        foreach ($product_cl_data_array['elements'] as $key => $single_item) {
                                            $element_type = '';

                                            switch ($single_item['type']) {
                                                case 'Text':
                                                    $element_type = 'Channel Letter';
                                                    break;

                                                case 'Circle':
                                                    $element_type = 'Oval';

                                                    break;
                                                case 'Star':
                                                    $element_type = 'Starburst';

                                                    break;
                                                case 'RegularPolygon':
                                                    $element_type = 'Triangle';

                                                    break;
                                                case 'Line':
                                                    $element_type = 'Arrow';
                                                    break;
                                                case 'Rect':
                                                    $element_type = 'Rectangle';
                                                    break;
                                                default:

                                                    $element_type = $single_item['type'];
                                                    break;
                                            }

                                            $cl_text = isset($single_item['text']) ? $single_item['text'] : '-';
                                            $item_height = isset($single_item['height']) ? round($single_item['height'], 2) : '-';
                                            $item_width = isset($single_item['width']) ? round($single_item['width'], 2) : '-';
                                            $item_font = isset($single_item['font']) ? $single_item['font']['title'] : '-';
                                            $item_face_color = isset($single_item['faceColor']) ? $single_item['faceColor']['title'] : '-';
                                            $item_trimcap_color = isset($single_item['trimcapColor']) ? $single_item['trimcapColor']['title'] : '-';
                                            $item_return_color = isset($single_item['returnColor']) ? $single_item['returnColor']['title'] : '-';
                                            $item_trimcap_size = isset($single_item['trimcapSize']) ? $single_item['trimcapSize']['title'] : '-';
                                            $item_return_size = isset($single_item['returnSize']) ? $single_item['returnSize']['title'] : '-';
                                            $item_radius = isset($single_item['radius']) ? $single_item['radius'] : '-';
                                            $item_face_cost = isset($single_item['colorCost']) ? round($single_item['colorCost'], 2) : '-';
                                            $item_cost = isset($single_item['cost']) ? round($single_item['cost'], 2) : '-';
                                            $item_dimenstion = "$item_height x $item_width";
                                            $item_total_cost = round(floatval($item_cost) + floatval($item_face_cost), 2);
                                            $total_element_cost += $item_total_cost;
                                        ?>
                                            <tr>

                                                <td><?php echo $key + 1; ?></td>
                                                <td><?php echo esc_html($element_type); ?></td>
                                                <td><?php echo esc_html($item_dimenstion); ?></td>
                                                <td><?php echo esc_html((string) $cl_text); ?></td>
                                                <td><?php echo esc_html((string) $item_font); ?></td>
                                                <td><?php echo esc_html((string) $item_face_color); ?></td>
                                                <td><?php echo esc_html((string) $item_return_color); ?></td>
                                                <td><?php echo esc_html((string) $item_trimcap_color); ?></td>
                                                <td><?php echo esc_html((string) $item_return_size); ?></td>
                                                <td><?php echo esc_html((string) $item_trimcap_size); ?></td>
                                                <td><?php echo esc_html((string) $item_radius); ?></td>
                                                <td><?php echo formate_price($item_cost); ?></td>
                                                <td><?php echo formate_price($item_face_cost); ?></td>
                                                <td><?php echo formate_price($item_total_cost); ?></td>

                                            </tr>

                                        <?php }
                                        $power_supply = $product_cl_data_array['extras']['powerSupply'] ?? array();
                                        $cable = $product_cl_data_array['extras']['cable'] ?? array();
                                        $lit = $product_cl_data_array['extras']['lit'] ?? array();
                                        $total_extras_cost = 0;

                                        if (!empty($power_supply['qty'])) {
                                            $total_extras_cost += floatval($power_supply['cost'] ?? 0);
                                        ?>
                                            <tr>
                                                <td colspan="13">Power Supply: <span class="fw-bold"><?php echo esc_html($power_supply['value']); ?></span></td>
                                                <td>$<?php echo floatval($power_supply['cost'] ?? 0) > 0 ? formate_price($power_supply['cost']) : "0"; ?></td>
                                            </tr>
                                        <?php

                                        }
                                        if (!empty($cable['qty'])) {
                                            $total_extras_cost += floatval($cable['cost'] ?? 0);

                                        ?>
                                            <tr>
                                                <td colspan="13">Cable: <span class="fw-bold"><?php echo esc_html($cable['value']); ?></span></td>
                                                <td>$<?php echo floatval($cable['cost'] ?? 0) > 0 ?  formate_price( $cable['cost']) : "0"; ?></td>
                                            </tr>
                                        <?php

                                        }
                                        if (!empty($lit['qty'])) {
                                            $lit_cost = round(($total_element_cost / 100) * floatval($lit['cost'] ?? 0), 2);
                                            $total_extras_cost +=  $lit_cost;

                                        ?>
                                            <tr>
                                                <td colspan="13">Lit: <span class="fw-bold"><?php echo esc_html($lit['value']); ?></span></td>
                                                <td>$<?php echo $lit_cost > 0 ? formate_price( $lit_cost) : 0; ?> (<?php echo esc_html(floatval($lit['cost'] ?? 0)); ?>%)</td>
                                            </tr>
                                        <?php

                                        }

                                        $total_order_cost = round($total_element_cost + $total_extras_cost, 2);
                                        ?>


                                        <tr>
                                            <td class="fw-bold" colspan="7" id="dtTotalObjDisplay">Total : <span class="text-primary"> <?php echo $total_objects; ?> </span> Objects</td>
                                            <td colspan="7" id="dtTotalPriceDisplay">Total Price: <span class="text-success fw-bold">$<?php echo formate_price($total_order_cost); ?></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    <?php } ?>

                </div>

            <?php } ?>
        </div>
    </div>

    <div class="row cart-summary">
        <div class="col-md-4 offset-md-8">
            <div class="sub-total-container border-top d-flex justify-content-between p-2">
                <span class="fw-bold"> Subtotal</span>
                <span> $<?php echo  number_format($cart->sub_total,2,'.',','); ?> </span>
            </div>
            <div class="shipping-container border-top d-flex justify-content-between p-2">
                <span class="fw-bold"> Shipping (Standard)</span> <span class="fw-normal">$<?php echo number_format($shipping_cost[0], 2, '.', ','); ?> </span>
            </div>
            <div class="grand-total-container border-top d-flex justify-content-between p-2">
                <span class="fw-bold"> Grand Total</span>
                <span> $<?php echo  number_format($cart->sub_total + $shipping_cost[0],2,'.',','); ?> </span>
            </div>
            <div class="checkout-container mt-3">
                <a href="<?php echo home_url() . '/checkout'; ?>" class="btn btn-danger checkout-button fw-bold px-5 py-2 d-block w-100">Procced to
                    Checkout</a>
            </div>
        </div>
    </div>
    <?php }else {
        ?>
            <p class="text-muted text-danger text-center">Your Cart is empty</p>
        <?php
    } ?>
</div>




<?php
get_footer();
?>