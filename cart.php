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
    $cart_id = $_REQUEST['remove_cart'];
    $cart -> remove_item($cart_id);
    wp_redirect(get_permalink());
}

if(isset($_REQUEST['update_quantity'])){
    $quantity = $_REQUEST['update_quantity'];
    $cart_id = $_REQUEST['cart_id'];
    $cart ->update_quantity($cart_id, $quantity);
    wp_redirect(get_permalink());
}




if(isset($_REQUEST['product_id'])){
    $cart -> add_item();
}


$cart_subtotal = 0;

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


//echo '<pre>';
//print_r($cart -> remove_item(209))
get_header();


?>

<div class="container my-5">
    <div class="row  text-center">
        <div class="col">
            <h2 class="fs-2">
                Shopping Cart
            </h2>
        </div>
    </div>
    <div class="row my-3 text-center">
        <div class="col-md-6 offset-md-3 d-inline-block text-center">
            <a href="<?php echo home_url(); ?>" class="btn btn-secondary px-5 mb-2 fw-bold py-2">Countinue Shopping</a>
            <?php if ($cart->have_items) : ?>
                <a href="<?php echo home_url() . '/checkout'; ?>" class="btn mb-2 btn-danger checkout-button fw-bold px-5 py-2">Procced to Checkout</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($cart->have_items) { ?>
    <div class="row my-5">
        <div class="col">

            <?php foreach ($cart->get_items() as $cart_item) {
                $cart_subtotal = $cart_subtotal + $cart_item->product_subtotal;
                
                $product_design_id = $cart_item ->  design_id > 0 ? $cart_item ->  design_id : 0;
                $product_cl_data = get_post_meta($product_design_id, '_cl_data', true);
                $product_cl_data_array = array();
                if ($product_cl_data) {
                    $product_cl_data_array = json_decode(stripslashes($product_cl_data), true);
                }
            ?>
                <div class="cart-item border p-2">
                    <div class="row">
                        <div class="col-md-3 cart-image">
                            <img class="cart-item-image w-100" src="<?php echo $cart_item->product_thumbnail; ?>" alt="">
                        </div>
                        <div class="col-md-9 ">
                            <div class="cart-title-container d-flex justify-content-between align-self-start border-bottom">
                                <h4 class="fs-4 align-self-center"><?php echo $cart_item->product_title; ?></h4>
                                <div class="align-self-center">
                                    <!-- <a href="#" class="btn btn-link">Edit</a>| -->
                                    <a href="<?php echo get_permalink().'?remove_cart='.$cart_item -> cart_id; ?>" class="btn btn-link text-danger">Remove</a>

                                </div>
                            </div>
                            <div class="cart-info-container d-flex justify-content-between border-bottom py-2">
                                <span class="text-primary cart-details-toggler cursor-pinter">+ Details</span>
                                <div class="cart-item-price">
                                    <span class="d-inline-block fw-bold" style="margin-right: 90px">Item Price</span>
                                    <span>$<?php echo  number_format($cart_item->product_subtotal / $cart_item->product_quantity,2,'.',','); ?></span>
                                </div>
                            </div>
                            <div class="cart-details-container border-bottom py-2">
                                <div>

                                    <?php foreach ($cart_item->product_details as $name => $value) {

                                        if($value == null){
                                            continue;

                                        }

                                        if(preg_match('/u201d/i',$value)) {
                                            ?>
                                        <strong><?php echo $name; ?>: </strong><?php echo str_replace('u201d', '”',$value); ?> <br>

                                            <?php
                                            continue;
                                        } 

                                    ?>
                                        <strong><?php echo $name; ?>: </strong><?php echo $value; ?> <br>

                                      <?php } ?>
                                      <?php  if($cart_item -> job_name) {
                                            ?>
                                            <strong><?php echo 'Job Name' ?>: </strong><?php echo $cart_item -> job_name? $cart_item -> job_name: ''; ?> <br>
                                            <?php
                                        }?>
                                </div>
                            </div>
                            <div class="cart-item-quantity-container d-flex justify-content-end py-2 border-bottom">
                                <div class="d-flex">
                                    <label class="d-inline-block fw-bold" style="margin-right: 20px">Quantity</label>
                                    <div class="quantiy-input-container">
                                        <form action="">
                                        <input type="hidden" name="cart_id" value="<?php echo $cart_item -> cart_id; ?>">
                                        <input type="number" value="<?php echo $cart_item->product_quantity; ?>" name="update_quantity" style="width: 50px" id="">
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
                                                <td><?php echo $element_type; ?></td>
                                                <td><?php echo $item_dimenstion; ?></td>
                                                <td><?php echo $cl_text; ?></td>
                                                <td><?php echo $item_font; ?></td>
                                                <td><?php echo $item_face_color; ?></td>
                                                <td><?php echo $item_return_color; ?></td>
                                                <td><?php echo $item_trimcap_color; ?></td>
                                                <td><?php echo $item_return_size; ?></td>
                                                <td><?php echo $item_trimcap_size; ?></td>
                                                <td><?php echo $item_radius; ?></td>
                                                <td><?php echo formate_price($item_cost); ?></td>
                                                <td><?php echo formate_price($item_face_cost); ?></td>
                                                <td><?php echo formate_price($item_total_cost); ?></td>

                                            </tr>

                                        <?php }
                                        $power_supply = $product_cl_data_array['extras']['powerSupply'];
                                        $cable = $product_cl_data_array['extras']['cable'];
                                        $lit = $product_cl_data_array['extras']['lit'];
                                        $total_extras_cost = 0;

                                        if (isset($power_supply['value'])) {
                                            $total_extras_cost += $power_supply['cost'];
                                        ?>
                                            <tr>
                                                <td colspan="13">Power Supply: <span class="fw-bold"><?php echo $power_supply['value']; ?></span></td>
                                                <td>$<?php echo $power_supply['cost'] > 0 ? formate_price( $power_supply['cost']) : "0"; ?></td>
                                            </tr>
                                        <?php

                                        }
                                        if (isset($cable['value'])) {
                                            $total_extras_cost += $cable['cost'];

                                        ?>
                                            <tr>
                                                <td colspan="13">Power Supply: <span class="fw-bold"><?php echo $cable['value']; ?></span></td>
                                                <td>$<?php echo $cable['cost'] > 0 ?  formate_price( $cable['cost']) : "0"; ?></td>
                                            </tr>
                                        <?php

                                        }
                                        if (isset($lit['value'])) {
                                            $lit_cost = round(($total_element_cost / 100) * $lit['cost'], 2);
                                            $total_extras_cost +=  $lit_cost;

                                        ?>
                                            <tr>
                                                <td colspan="13">Lit: <span class="fw-bold"><?php echo $lit['value']; ?></span></td>
                                                <td>$<?php echo $lit_cost > 0 ? formate_price( $lit_cost) : 0; ?> (<?php echo $lit['cost']; ?>%)</td>
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

    <div class="row">
        <div class="col-md-4 offset-md-8">
            <div class="sub-total-container border-top d-flex justify-content-between p-2">
                <span class="fw-bold"> Subtotal</span>
                <span> $<?php echo  number_format($cart->sub_total,2,'.',','); ?> </span>
            </div>
            <div class="shipping-container border-top d-flex justify-content-between p-2">
                <span class="fw-bold"> Shipping (Standard)</span> <span class="fw-normal">$<?php echo $shipping_cost[0]; ?> </span>
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