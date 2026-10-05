<?php
/**
 * Order detail for the customer who placed it (and staff; see wholesale_protect_order_pages()).
 *
 * @package litsign
 */

$post_id = get_the_ID();
$items = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_json', true));
$cost = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_cost', true));
$billing = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
$shipping = wholesale_decode_order_meta_array(get_post_meta($post_id, 'shipping_address', true));
$status = get_post_status($post_id);
$status_info = wholesale_order_status_info();
$step = isset($status_info[$status]) ? $status_info[$status]['step'] : 1;
$note = isset($status_info[$status]) ? $status_info[$status]['note'] : '';
$tracking = get_post_meta($post_id, '_tracking_number', true);
$carrier = get_post_meta($post_id, '_tracking_carrier', true);
$tracking_url = $tracking ? wholesale_tracking_url($carrier, $tracking) : '';
$is_staff = current_user_can('edit_post', $post_id);
$money = static function ($value) {
    return '$' . number_format((float) $value, 2);
};
$address = static function ($data, $prefix) {
    $lines = array(
        trim(($data[$prefix . 'fname'] ?? '') . ' ' . ($data[$prefix . 'lname'] ?? '')),
        $data[$prefix . 'company'] ?? '',
        trim(($data[$prefix . 'address'] ?? '') . (!empty($data[$prefix . 'address_2']) ? ', ' . $data[$prefix . 'address_2'] : '')),
        trim(($data[$prefix . 'city'] ?? '') . ', ' . ($data[$prefix . 'state'] ?? '') . ' ' . ($data[$prefix . 'zip'] ?? ''), ', '),
        $data[$prefix . 'tel'] ?? '',
        $data[$prefix . 'email'] ?? '',
    );
    return implode('<br>', array_map('esc_html', array_filter($lines)));
};
$ship_prefix = isset($shipping['shipping_fname']) ? 'shipping_' : 'billing_';
$element_names = array('Text' => 'Letters', 'Circle' => 'Oval', 'Star' => 'Starburst', 'RegularPolygon' => 'Triangle', 'Line' => 'Arrow', 'Rect' => 'Rectangle');

get_header();
?>

<main id="primary" class="account-page-v2">
    <div class="container account-container">
        <p class="order-back"><a href="<?php echo esc_url($is_staff ? admin_url('edit.php?post_type=order') : home_url('/my-orders/')); ?>">&larr; <?php echo $is_staff ? 'All orders' : 'My orders'; ?></a></p>

        <header class="acct-head">
            <div>
                <h1>Order #<?php echo esc_html(wholesale_order_number($post_id)); ?> <?php echo wholesale_order_status_badge($status); // Escaped in the helper. ?></h1>
                <p>Placed <?php echo esc_html(get_the_date('F j, Y', $post_id)); ?><?php echo $note ? ' &middot; ' . esc_html($note) : ''; ?></p>
            </div>
            <?php if ($is_staff) : ?>
                <a class="cart-secondary" href="<?php echo esc_url(get_edit_post_link($post_id)); ?>">Manage in admin</a>
            <?php endif; ?>
        </header>

        <?php if ($step > 0) : ?>
            <ol class="order-progress" aria-label="Order progress">
                <?php foreach (array(1 => 'Order received', 2 => 'In production', 3 => 'Shipped') as $index => $label) : ?>
                    <li class="<?php echo $index < $step ? 'is-done' : ($index === $step ? 'is-current' : ''); ?>"<?php echo $index === $step ? ' aria-current="step"' : ''; ?>>
                        <span><?php echo esc_html($label); ?></span>
                        <?php if (3 === $index && get_post_meta($post_id, 'estimate_delivery_time', true) && $step < 3) : ?>
                            <small>Est. <?php echo esc_html(get_post_meta($post_id, 'estimate_delivery_time', true)); ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if ($tracking) : ?>
            <div class="order-tracking checkout-card">
                <strong>Tracking number<?php echo $carrier ? ' (' . esc_html(strtoupper($carrier)) . ')' : ''; ?>:</strong>
                <?php if ($tracking_url) : ?>
                    <a href="<?php echo esc_url($tracking_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($tracking); ?></a>
                <?php else : ?>
                    <?php echo esc_html($tracking); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="confirmation-grid">
            <section class="cart-items" aria-label="Items">
                <?php foreach ($items as $item) :
                    $details = (array) ($item['product_details'] ?? array());
                    $image = !empty($details['Design Url']) ? $details['Design Url'] : ($item['product_thumbnail'] ?? '');
                    $specs = wholesale_item_specs($item);
                    $design = !empty($item['design_id']) ? json_decode((string) get_post_meta((int) $item['design_id'], '_cl_data', true), true) : null;
                    $quantity = max(1, (int) ($item['product_quantity'] ?? 1));
                    $product_id = absint($item['product_id'] ?? ($details['Product Id'] ?? 0));
                    $product_url = $product_id && 'product' === get_post_type($product_id) && 'publish' === get_post_status($product_id) ? get_permalink($product_id) : '';
                    ?>
                    <article class="checkout-card cart-line">
                        <div class="cart-line-image">
                            <?php if ($image) : ?>
                                <a href="<?php echo esc_url($image); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item['product_title'] ?? ''); ?>" loading="lazy"></a>
                            <?php endif; ?>
                        </div>
                        <div class="cart-line-body">
                            <div class="cart-line-head">
                                <h2><?php echo esc_html($item['product_title'] ?? ''); ?></h2>
                                <strong class="cart-line-total"><?php echo esc_html($money($item['product_subtotal'] ?? 0)); ?></strong>
                            </div>
                            <p class="cart-line-unit">Qty <?php echo esc_html($quantity); ?> &middot; <?php echo esc_html($money(($item['product_subtotal'] ?? 0) / $quantity)); ?> each</p>

                            <?php if (is_array($design) && !empty($design['elements'])) : ?>
                                <?php if ($is_staff) : ?>
                                    <div class="order-elements">
                                        <table>
                                            <thead><tr><th>#</th><th>Type</th><th>Text</th><th>H &times; W (in)</th><th>Font</th><th>Face</th><th>Return</th><th>Trimcap</th><th>Return size</th><th>Trimcap size</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($design['elements'] as $index => $element) : ?>
                                                    <tr>
                                                        <td><?php echo esc_html($index + 1); ?></td>
                                                        <td><?php echo esc_html($element_names[$element['type'] ?? ''] ?? ($element['type'] ?? '')); ?></td>
                                                        <td><?php echo esc_html($element['text'] ?? ''); ?></td>
                                                        <td><?php echo esc_html(round((float) ($element['height'] ?? 0), 1) . ' × ' . round((float) ($element['width'] ?? 0), 1)); ?></td>
                                                        <td><?php echo esc_html($element['font']['title'] ?? ''); ?></td>
                                                        <td><?php echo esc_html($element['faceColor']['title'] ?? ''); ?></td>
                                                        <td><?php echo esc_html($element['returnColor']['title'] ?? ''); ?></td>
                                                        <td><?php echo esc_html($element['trimcapColor']['title'] ?? ''); ?></td>
                                                        <td><?php echo esc_html($element['returnSize']['title'] ?? ''); ?></td>
                                                        <td><?php echo esc_html($element['trimcapSize']['title'] ?? ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <ul class="cart-line-design">
                                    <?php foreach (wholesale_cl_design_summary($design) as $line) : ?>
                                        <li><?php echo esc_html($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($specs) : ?>
                                <details class="cart-line-specs"<?php echo $is_staff ? ' open' : ''; ?>>
                                    <summary>Sign details</summary>
                                    <dl>
                                        <?php foreach ($specs as $label => $value) : ?>
                                            <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo $value; // Escaped in wholesale_item_specs(). ?></dd></div>
                                        <?php endforeach; ?>
                                    </dl>
                                </details>
                            <?php endif; ?>

                            <?php if ($product_url) : ?>
                                <div class="cart-line-actions">
                                    <a class="order-view-product" href="<?php echo esc_url($product_url); ?>">View product</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="checkout-card checkout-summary-card">
                <h2>Summary</h2>
                <dl class="checkout-totals">
                    <div><dt>Subtotal</dt><dd><?php echo esc_html($money($cost['sub_total'] ?? 0)); ?></dd></div>
                    <div><dt>Shipping</dt><dd><?php echo esc_html($money($cost['shipping_cost'] ?? 0)); ?></dd></div>
                    <div><dt>Tax</dt><dd><?php echo esc_html($money($cost['tax'] ?? 0)); ?></dd></div>
                    <div class="checkout-grand"><dt>Total</dt><dd><?php echo esc_html($money($cost['grand_total'] ?? 0)); ?></dd></div>
                </dl>
                <dl class="confirmation-meta">
                    <div><dt>Payment</dt><dd><?php echo esc_html(wholesale_payment_customer_text($post_id)); ?></dd></div>
                    <?php if (!empty($cost['shipping_method'])) : ?>
                        <div><dt>Shipping method</dt><dd><?php echo esc_html($cost['shipping_method']); ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Ship to</dt><dd><?php echo $address($shipping, $ship_prefix); // Escaped per line. ?></dd></div>
                    <div><dt>Billing</dt><dd><?php echo $address($billing, 'billing_'); // Escaped per line. ?></dd></div>
                    <?php if (get_post_meta($post_id, 'order_comment', true)) : ?>
                        <div><dt>Order notes</dt><dd><?php echo nl2br(esc_html(get_post_meta($post_id, 'order_comment', true))); ?></dd></div>
                    <?php endif; ?>
                </dl>
                <p class="account-help">Questions? Call <a href="tel:+18664362101">866-436-2101</a> with order #<?php echo esc_html(wholesale_order_number($post_id)); ?>.</p>
            </aside>
        </div>
    </div>
</main>

<?php
get_footer();
