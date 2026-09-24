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

    $data = wholesale_decode_order_meta_array(get_post_meta(get_the_ID(), 'product_json', true));


?>
    <h1 class="text-center text-capitalize mb-5">
        <small>Status: </small>

        <?php
        $order_status = get_post_status(get_the_ID());

        switch ($order_status) {
            case 'pending':
                echo '<span class="text-primary">Pending Review</span>';
                break;

            case 'on-hold':
            case 'on_hold':
                echo '<span class="text-warning">On hold</span>';
                break;
            case 'processing':
                echo '<span class="text-primary">Processing</span>';
                break;
            case 'completed':
                echo '<span class="text-success">Completed</span>';
                break;
            case 'failed':
                echo '<span class="text-danger">Failed</span>';
                break;
            case 'cancelled':
                echo '<span class="text-secondary">Cancelled</span>';
                break;
            case 'refunded':
                echo '<span class="text-info">Refunded</span>';
                break;
            default:
                echo '<span class="text-warning">' . esc_html($order_status) . '</span>';
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
        }

    ?>

        <div class="cart-item border p-2">


            <div class="row">
                <div class="col-md-3 cart-image">
                    <img class="cart-item-image w-100" src="<?php echo esc_url($product['product_thumbnail']); ?>" alt="<?php echo esc_attr($product['product_title']); ?>">
                </div>
                <div class="col-md-9 ">
                    <div class="cart-title-container d-flex justify-content-between align-self-start border-bottom">
                        <h4 class="fs-4 align-self-center"><?php echo esc_html($product['product_title']); ?></h4>
                        <div class="align-self-center">
                            <!-- <a href="#" class="btn btn-link">Edit</a>| -->
                            <!-- <a href="<?php echo get_permalink() . '?remove_cart=' . $product['cart_id']; ?>" class="btn btn-link">Remove</a> -->

                        </div>
                    </div>
                    <div class="cart-info-container d-flex justify-content-between border-bottom py-2">
                        <span class="text-primary cart-details-toggler cursor-pinter">+ Details</span>
                        <div class="cart-item-price">
                            <span class="d-inline-block fw-bold" style="margin-right: 90px">Item Price</span>
                            <span>$<?php echo esc_html(number_format((float) ($product['product_subtotal'] / max(1, (int) $product['product_quantity'])), 2, '.', ',')); ?></span>
                        </div>
                    </div>
                    <div class="cart-details-container border-bottom py-2">
                        <div>

                            <?php foreach ($product['product_details'] as $name => $value) {
                                if ($value == null) {
                                    continue;
                                }
                                $detail_label = esc_html((string) $name);
                                $detail_value = wholesale_format_order_detail_value($name, $value);
                            ?>
                                <strong><?php echo $detail_label; ?>: </strong><?php echo $detail_value; ?> <br>
                            <?php }
                            ?>
                            <?php if (!empty($product['job_name'])) : ?>
                                <strong>Job Name: </strong><?php echo esc_html($product['job_name']); ?> <br>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="cart-item-quantity-container d-flex justify-content-end py-2 border-bottom">
                        <div class="d-flex">
                            <label class="d-inline-block fw-bold" style="margin-right: 20px">Quantity</label>
                            <div class="quantiy-input-container">
                                <form action="">
                                    <input type="hidden" name="cart_id" value="<?php echo esc_attr($product['cart_id']); ?>">
                                    <input type="text" readonly value="<?php echo esc_attr($product['product_quantity']); ?>" name="update_quantity" style="width: 50px" id="">
                                </form>

                            </div>
                        </div>
                    </div>

                    <div class="cart-total-price-container d-flex justify-content-end  border-bottom border-top py-2">
                        <div class="cart-item-total-price">
                            <span class="d-inline-block fw-bold" style="margin-right: 90px">Total Price</span>
                            <span>$<?php echo esc_html(number_format((float) $product['product_subtotal'], 2, '.', ',')); ?></span>
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

                                        <td><?php echo esc_html((string) ($key + 1)); ?></td>
                                        <td><?php echo esc_html((string) $element_type); ?></td>
                                        <td><?php echo esc_html((string) $item_dimenstion); ?></td>
                                        <td><?php echo esc_html((string) $cl_text); ?></td>
                                        <td><?php echo esc_html((string) $item_font); ?></td>
                                        <td><?php echo esc_html((string) $item_face_color); ?></td>
                                        <td><?php echo esc_html((string) $item_return_color); ?></td>
                                        <td><?php echo esc_html((string) $item_trimcap_color); ?></td>
                                        <td><?php echo esc_html((string) $item_return_size); ?></td>
                                        <td><?php echo esc_html((string) $item_trimcap_size); ?></td>
                                        <td><?php echo esc_html((string) $item_radius); ?></td>
                                        <td><?php echo esc_html(process_price($item_cost)); ?></td>
                                        <td><?php echo esc_html(process_price($item_face_cost)); ?></td>
                                        <td><?php echo esc_html(process_price($item_total_cost)); ?></td>

                                    </tr>

                                <?php }
                                $power_supply = $product_cl_data_array['extras']['powerSupply'];
                                $cable = $product_cl_data_array['extras']['cable'];
                                $lit = $product_cl_data_array['extras']['lit'];
                                $total_extras_cost = 0;

                                if (!empty($power_supply['qty'])) {
                                    $total_extras_cost += floatval($power_supply['cost'] ?? 0);
                                ?>
                                    <tr>
                                        <td colspan="13">Power Supply: <span class="fw-bold"><?php echo esc_html((string) $power_supply['value']); ?></span></td>
                                        <td>$<?php echo esc_html(process_price(floatval($power_supply['cost'] ?? 0))); ?></td>
                                    </tr>
                                <?php

                                }
                                if (!empty($cable['qty'])) {
                                    $total_extras_cost += floatval($cable['cost'] ?? 0);

                                ?>
                                    <tr>
                                        <td colspan="13">Power Supply: <span class="fw-bold"><?php echo esc_html((string) $cable['value']); ?></span></td>
                                        <td>$<?php echo esc_html(process_price($cable['cost'])); ?></td>
                                    </tr>
                                <?php

                                }
                                if (!empty($lit['qty'])) {
                                    $lit_cost = round(($total_element_cost / 100) * floatval($lit['cost'] ?? 0), 2);
                                    $total_extras_cost +=  $lit_cost;

                                ?>
                                    <tr>
                                        <td colspan="13">Lit: <span class="fw-bold"><?php echo esc_html((string) $lit['value']); ?></span></td>
                                        <td>$<?php echo esc_html(process_price($lit_cost)); ?> (<?php echo esc_html((string) $lit['cost']); ?>%)</td>
                                    </tr>
                                <?php

                                }

                                $total_order_cost = round($total_element_cost + $total_extras_cost, 2);
                                ?>


                                <tr>
                                    <td class="fw-bold" colspan="7" id="dtTotalObjDisplay">Total : <span class="text-primary"> <?php echo esc_html((string) $total_objects); ?> </span> Objects</td>
                                    <td colspan="7" id="dtTotalPriceDisplay">Total Price: <span class="text-success fw-bold">$<?php echo esc_html(process_price($total_order_cost)); ?></span></td>
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
    $shipping = wholesale_decode_order_meta_array(get_post_meta($post_id, 'shipping_address', true));
    $billing = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
    $cost = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_cost', true));
    $comment = get_post_meta($post_id, 'order_comment', true);
    $ticket_id = (int) get_post_meta($post_id, '_ticket_id', true);
    $money = static function ($value) {
        return '$' . number_format((float) $value, 2);
    };
    $address = static function ($data, $prefix) {
        $lines = array(
            trim(($data[$prefix . 'fname'] ?? '') . ' ' . ($data[$prefix . 'lname'] ?? '')),
            $data[$prefix . 'company'] ?? '',
            trim(($data[$prefix . 'address'] ?? '') . (!empty($data[$prefix . 'address_2']) ? ', ' . $data[$prefix . 'address_2'] : '')),
            trim(($data[$prefix . 'city'] ?? '') . ', ' . ($data[$prefix . 'state'] ?? '') . ' ' . ($data[$prefix . 'zip'] ?? ''), ', '),
            $data[$prefix . 'country'] ?? '',
        );
        return implode('<br>', array_map('esc_html', array_filter($lines)));
    };
    $ship_prefix = isset($shipping['shipping_fname']) ? 'shipping_' : 'billing_';
    ?>
    <style>
        .wo-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .wo-grid h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #646970; }
        .wo-grid dl { display: grid; grid-template-columns: auto auto; gap: 4px 12px; margin: 0; }
        .wo-grid dd { margin: 0; text-align: right; }
        .wo-grid .wo-total { font-weight: 700; font-size: 14px; }
        @media (max-width: 1100px) { .wo-grid { grid-template-columns: 1fr; } }
    </style>
    <div class="wo-grid">
        <div>
            <h3>Totals</h3>
            <dl>
                <dt>Subtotal</dt><dd><?php echo esc_html($money($cost['sub_total'] ?? 0)); ?></dd>
                <dt>Shipping<?php echo !empty($cost['shipping_method']) ? ' (' . esc_html($cost['shipping_method']) . ')' : ''; ?></dt><dd><?php echo esc_html($money($cost['shipping_cost'] ?? 0)); ?></dd>
                <dt>Tax</dt><dd><?php echo esc_html($money($cost['tax'] ?? 0)); ?></dd>
                <dt class="wo-total">Total</dt><dd class="wo-total"><?php echo esc_html($money($cost['grand_total'] ?? 0)); ?></dd>
            </dl>
            <p>Order #<?php echo esc_html(wholesale_order_number($post_id)); ?> &middot; placed <?php echo esc_html(get_the_date('M j, Y g:ia', $post_id)); ?><br>
                Estimated ship date: <strong><?php echo esc_html(get_post_meta($post_id, 'estimate_delivery_time', true) ?: '—'); ?></strong>
                <?php if ($ticket_id) : ?><br>Paid via <a href="<?php echo esc_url(get_edit_post_link($ticket_id)); ?>">payment ticket #<?php echo esc_html((string) $ticket_id); ?></a><?php endif; ?></p>
        </div>
        <div>
            <h3>Customer</h3>
            <p><?php echo $address($billing, 'billing_'); // Escaped per line. ?></p>
            <p>
                <?php if (!empty($billing['billing_email'])) : ?><a href="mailto:<?php echo esc_attr($billing['billing_email']); ?>"><?php echo esc_html($billing['billing_email']); ?></a><br><?php endif; ?>
                <?php if (!empty($billing['billing_tel'])) : ?><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $billing['billing_tel'])); ?>"><?php echo esc_html($billing['billing_tel']); ?></a><?php endif; ?>
            </p>
        </div>
        <div>
            <h3>Ship to</h3>
            <p><?php echo $address($shipping, $ship_prefix); // Escaped per line. ?></p>
            <?php if (!empty($shipping[$ship_prefix . 'tel'])) : ?><p><?php echo esc_html($shipping[$ship_prefix . 'tel']); ?></p><?php endif; ?>
        </div>
    </div>
    <?php if ($comment) : ?>
        <h3 style="margin-top:16px;">Customer notes</h3>
        <p><?php echo nl2br(esc_html($comment)); ?></p>
    <?php endif; ?>
    <?php
}
