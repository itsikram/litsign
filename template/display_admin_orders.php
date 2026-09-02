<?php
function add_custom_order_meta_box()
{
    add_meta_box(
        'order_attr_details',
        'Product Attributes Details',
        'render_order_attr_meta_box',
        'order',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'add_custom_order_meta_box');


function process_price($price)
{
    if ($price) {
        return number_format($price, 2, '.', ',');
    }
}


function render_order_attr_meta_box()
{

    $product_json_data = get_post_meta(get_the_ID(), 'product_json', true);

    // Decode the JSON into an associative array
    $data = json_decode($product_json_data, true);


?>
    <h1 class="text-center text-capitalize mb-5">
        <small>Status: </small>

        <?php
        $order_status = get_post_status(get_the_ID());

        switch ($order_status) {
            case 'pending':
                echo '<span class="text-primary">Pending Review<span>';
                break;

            case 'on_hold':
                echo '<span class="text-warning">On Hold<span>';
                break;
            case 'completed':
                echo '<span class="text-success">Completed<span>';
                break;
            case 'failed':
                echo '<span class="text-danger">Failed<span>';
                break;
            default:
                echo '<span class="text-warning">' . $order_status . '<span>';
        }
        ?>
    </h1>
    <?php

    // Function to print array values line by line
    function printProduct($product)
    {
        $product_design_id = $product['design_id'] > 0 ? $product['design_id'] : 0;
        $product_cl_data = get_post_meta($product_design_id, '_cl_data', true);
        $product_cl_data_array = array();
        if ($product_cl_data) {
            $product_cl_data_array = json_decode(stripslashes($product_cl_data), true);
            print_r($product_cl_data_array);
        }

    ?>

        <div class="cart-item border p-2">


            <div class="row">
                <div class="col-md-3 cart-image">
                    <img class="cart-item-image w-100" src="<?php echo $product['product_thumbnail']; ?>" alt="">
                </div>
                <div class="col-md-9 ">
                    <div class="cart-title-container d-flex justify-content-between align-self-start border-bottom">
                        <h4 class="fs-4 align-self-center"><?php echo $product['product_title']; ?></h4>
                        <div class="align-self-center">
                            <!-- <a href="#" class="btn btn-link">Edit</a>| -->
                            <!-- <a href="<?php echo get_permalink() . '?remove_cart=' . $product['cart_id']; ?>" class="btn btn-link">Remove</a> -->

                        </div>
                    </div>
                    <div class="cart-info-container d-flex justify-content-between border-bottom py-2">
                        <span class="text-primary cart-details-toggler cursor-pinter">+ Details</span>
                        <div class="cart-item-price">
                            <span class="d-inline-block fw-bold" style="margin-right: 90px">Item Price</span>
                            <span>$<?php echo  number_format($product['product_subtotal'] / $product['product_quantity'], 2, '.', ','); ?></span>
                        </div>
                    </div>
                    <div class="cart-details-container border-bottom py-2">
                        <div>

                            <?php foreach ($product['product_details'] as $name => $value) {
                                if ($value == null) {
                                    continue;
                                }
                                if (preg_match('/u201d/i', $value)) {
                            ?>
                                    <strong><?php echo $name; ?>: </strong><?php echo str_replace('u201d', '”', $value); ?> <br>

                                <?php
                                    continue;
                                }
                                ?>


                                <strong><?php echo $name; ?>: </strong><?php echo $value; ?> <br>
                            <?php }
                            ?>
                        </div>
                    </div>
                    <div class="cart-item-quantity-container d-flex justify-content-end py-2 border-bottom">
                        <div class="d-flex">
                            <label class="d-inline-block fw-bold" style="margin-right: 20px">Quantity</label>
                            <div class="quantiy-input-container">
                                <form action="">
                                    <input type="hidden" name="cart_id" value="<?php echo $product['cart_id']; ?>">
                                    <input type="text" readonly value="<?php echo $product['product_quantity']; ?>" name="update_quantity" style="width: 50px" id="">
                                </form>

                            </div>
                        </div>
                    </div>

                    <div class="cart-total-price-container d-flex justify-content-end  border-bottom border-top py-2">
                        <div class="cart-item-total-price">
                            <span class="d-inline-block fw-bold" style="margin-right: 90px">Total Price</span>
                            <span>$<?php echo  number_format($product['product_subtotal'], 2, '.', ','); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($product_cl_data) {
            ?>
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
                                        <td><?php echo process_price($item_cost); ?></td>
                                        <td><?php echo process_price($item_face_cost); ?></td>
                                        <td><?php echo process_price($item_total_cost); ?></td>

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
                                        <td>$<?php echo process_price($power_supply['cost']); ?></td>
                                    </tr>
                                <?php

                                }
                                if (isset($cable['value'])) {
                                    $total_extras_cost += $cable['cost'];

                                ?>
                                    <tr>
                                        <td colspan="13">Power Supply: <span class="fw-bold"><?php echo $cable['value']; ?></span></td>
                                        <td>$<?php echo process_price($cable['cost']); ?></td>
                                    </tr>
                                <?php

                                }
                                if (isset($lit['value'])) {
                                    $lit_cost = round(($total_element_cost / 100) * $lit['cost'], 2);
                                    $total_extras_cost +=  $lit_cost;

                                ?>
                                    <tr>
                                        <td colspan="13">Lit: <span class="fw-bold"><?php echo $lit['value']; ?></span></td>
                                        <td>$<?php echo process_price($lit_cost); ?> (<?php echo $lit['cost']; ?>%)</td>
                                    </tr>
                                <?php

                                }

                                $total_order_cost = round($total_element_cost + $total_extras_cost, 2);
                                ?>


                                <tr>
                                    <td class="fw-bold" colspan="7" id="dtTotalObjDisplay">Total : <span class="text-primary"> <?php echo $total_objects; ?> </span> Objects</td>
                                    <td colspan="7" id="dtTotalPriceDisplay">Total Price: <span class="text-success fw-bold">$<?php echo process_price($total_order_cost); ?></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div> 

            <?php

            }; ?>




        </div>

    <?php
        echo '<hr/>';
    }

    echo '<div class="cart-item-container">';

    foreach ($data as $product) {
        printProduct($product);
    }

    echo '</div>';
}


function add_order_contact_meta_box()
{
    add_meta_box(
        'order_details',
        'Order Details',
        'render_order_meta_box',
        'order',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'add_order_contact_meta_box');


function render_order_meta_box()
{
    $post_id = get_the_ID();
    $shipping_address = json_decode(get_post_meta($post_id, 'shipping_address', true), true);
    $billing_address = json_decode(get_post_meta($post_id, 'billing_address', true), true);
    $product_cost = json_decode(get_post_meta($post_id, 'product_cost', true), true);
    $order_comment = get_post_meta($post_id, 'order_comment', true);
    $order_id = get_post_meta($post_id, 'order_id', true);
    $estimate_delivery_time = get_post_meta($post_id, 'estimate_delivery_time', true);
    $order_time = get_post_meta($post_id, 'order_time', true);
    ?>

    <div class="order-cost">
        <h1>Order Details</h1>
        <h2 style="padding: 0; line-height: 1.5"> <b>Grand Total: </b> $<?php echo number_format($product_cost['grand_total'] ? $product_cost['grand_total'] : 0, 2, '.', ','); ?></h2>
        <h2 style="padding: 0; line-height: 1.5"> <b>Sub Total: </b>
            $<?php echo number_format($product_cost['sub_total'], 2, '.', ','); ?>

        </h2>
        <h2 style="padding: 0; line-height: 1.5"> <b>Shipping Cost: </b>
            $<?php echo number_format($product_cost['shipping_cost'], 2, '.', ','); ?>
        </h2>
        <h2 style="padding: 0; line-height: 1.5"> <b>tax:</b>
            $<?php echo $product_cost['tax']; ?>
            $<?php echo number_format($product_cost['tax'] ? $product_cost['tax'] : 0, 2, '.', ','); ?>

        </h2>
        <h2 style="padding: 0; line-height: 1.5"> <b>Order Time:</b><?php echo $order_time; ?>
        </h2>
        <h2 style="padding: 0; line-height: 1.5"> <b>Estimate Delivery Time:</b> <?php echo $estimate_delivery_time; ?></h2>
        <h2 style="padding: 0; line-height: 1.5"> <b>Order Id:</b> <a href="<?php echo get_permalink(); ?>"><?php echo $order_id; ?></a></h2>
        <hr>
    </div>

    <div class="billing-details">
        <h1>Billing Details</h1>

        <?php
        foreach ($billing_address as $key => $value) {

        ?>
            <b> <?php echo ucwords(str_replace('billing_', ' ', $key)); ?>:</b> <?php echo $value; ?> <br />

        <?php

        } ?>
        <hr />
    </div>
    <div class="shipping-details">
        <h1>Shipping Details</h1>

        <?php
        foreach ($shipping_address as $key => $value) {

        ?>
            <b> <?php echo ucwords(str_replace('billing_', ' ', str_replace('shipping_', ' ', $key))); ?>:</b> <?php echo $value; ?> <br />

        <?php

        } ?>
        <hr />
    </div>

<?php

}
