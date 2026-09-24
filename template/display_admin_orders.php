<?php
/**
 * Order edit screen: the items box and the editable customer & shipping box.
 * Status, payment and activity boxes live in inc/admin-orders.php.
 *
 * @package litsign
 */

add_action('add_meta_boxes_order', function () {
    add_meta_box('order_attr_details', 'Items', 'render_order_attr_meta_box', 'order', 'normal', 'high');
    add_meta_box('order_details', 'Customer & shipping', 'render_order_meta_box', 'order', 'normal', 'high');
});

function process_price($price)
{
    return '' === $price || null === $price ? '' : number_format((float) $price, 2, '.', ',');
}

/**
 * Channel letter builder breakdown for one order item: one row per letter/shape plus extras.
 */
function wholesale_render_cl_breakdown($design)
{
    $elements = isset($design['elements']) && is_array($design['elements']) ? $design['elements'] : array();
    $extras = isset($design['extras']) && is_array($design['extras']) ? $design['extras'] : array();
    if (!$elements) {
        return;
    }
    $types = array('Text' => 'Channel letter', 'Circle' => 'Oval', 'Star' => 'Starburst', 'RegularPolygon' => 'Triangle', 'Line' => 'Arrow', 'Rect' => 'Rectangle');
    $title = static function ($element, $key) {
        return isset($element[$key]['title']) ? (string) $element[$key]['title'] : '—';
    };
    $element_total = 0;
    ?>
    <details class="wo-cl">
        <summary>Channel letter breakdown (<?php echo esc_html(count($elements)); ?> <?php echo 1 === count($elements) ? 'element' : 'elements'; ?>)</summary>
        <div class="wo-table-scroll">
            <table class="widefat striped wo-cl-table">
                <thead>
                    <tr>
                        <th>#</th><th>Type</th><th>Text</th><th>H × W (in)</th><th>Font</th><th>Face</th><th>Return</th><th>Trimcap</th><th>Return size</th><th>Trimcap size</th><th>Radius</th><th class="num">Cost</th><th class="num">Face cost</th><th class="num">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($elements as $i => $element) :
                        $type = isset($element['type']) ? (string) $element['type'] : '';
                        $cost = isset($element['cost']) ? (float) $element['cost'] : 0;
                        $face_cost = isset($element['colorCost']) ? (float) $element['colorCost'] : 0;
                        $element_total += $cost + $face_cost; ?>
                        <tr>
                            <td><?php echo esc_html((string) ($i + 1)); ?></td>
                            <td><?php echo esc_html($types[$type] ?? $type); ?></td>
                            <td><?php echo esc_html(isset($element['text']) ? (string) $element['text'] : '—'); ?></td>
                            <td><?php echo esc_html(round((float) ($element['height'] ?? 0), 2) . ' × ' . round((float) ($element['width'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html($title($element, 'font')); ?></td>
                            <td><?php echo esc_html($title($element, 'faceColor')); ?></td>
                            <td><?php echo esc_html($title($element, 'returnColor')); ?></td>
                            <td><?php echo esc_html($title($element, 'trimcapColor')); ?></td>
                            <td><?php echo esc_html($title($element, 'returnSize')); ?></td>
                            <td><?php echo esc_html($title($element, 'trimcapSize')); ?></td>
                            <td><?php echo esc_html(isset($element['radius']) ? (string) $element['radius'] : '—'); ?></td>
                            <td class="num">$<?php echo esc_html(process_price($cost)); ?></td>
                            <td class="num">$<?php echo esc_html(process_price($face_cost)); ?></td>
                            <td class="num">$<?php echo esc_html(process_price($cost + $face_cost)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php
                    $extras_total = 0;
                    foreach (array('powerSupply' => 'Power supply', 'cable' => 'Cable', 'lit' => 'Lighting') as $key => $label) :
                        $extra = isset($extras[$key]) && is_array($extras[$key]) ? $extras[$key] : array();
                        if (empty($extra['qty'])) {
                            continue;
                        }
                        // Lighting is priced as a percentage of the letters.
                        $extra_cost = 'lit' === $key ? round($element_total / 100 * (float) ($extra['cost'] ?? 0), 2) : (float) ($extra['cost'] ?? 0);
                        $extras_total += $extra_cost; ?>
                        <tr>
                            <td colspan="13"><?php echo esc_html($label); ?>: <strong><?php echo esc_html((string) ($extra['value'] ?? '')); ?></strong><?php echo 'lit' === $key ? ' (' . esc_html((string) ($extra['cost'] ?? 0)) . '%)' : ''; ?></td>
                            <td class="num">$<?php echo esc_html(process_price($extra_cost)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th colspan="13">Builder total</th>
                        <th class="num">$<?php echo esc_html(process_price($element_total + $extras_total)); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </details>
    <?php
}

function render_order_attr_meta_box($post)
{
    $items = wholesale_decode_order_meta_array(get_post_meta($post->ID, 'product_json', true));
    $cost = wholesale_decode_order_meta_array(get_post_meta($post->ID, 'product_cost', true));
    $money = static function ($value) {
        return '$' . number_format((float) $value, 2);
    };

    if (!$items) {
        echo '<p>This order has no items.</p>';
        return;
    }
    ?>
    <ul class="wo-items">
        <?php foreach ($items as $item) :
            $qty = max(1, (int) ($item['product_quantity'] ?? 1));
            $subtotal = (float) ($item['product_subtotal'] ?? 0);
            $specs = wholesale_item_specs($item);
            $design_id = absint($item['design_id'] ?? 0);
            $design = $design_id ? json_decode(stripslashes((string) get_post_meta($design_id, '_cl_data', true)), true) : null;
            $product_id = absint($item['product_id'] ?? 0); ?>
            <li class="wo-item">
                <?php if (!empty($item['product_thumbnail'])) : ?>
                    <img class="wo-item__image" src="<?php echo esc_url($item['product_thumbnail']); ?>" alt="" loading="lazy">
                <?php endif; ?>
                <div class="wo-item__body">
                    <div class="wo-item__head">
                        <div>
                            <strong class="wo-item__title"><?php echo esc_html(wp_specialchars_decode((string) ($item['product_title'] ?? ''), ENT_QUOTES)); ?></strong>
                            <?php if ($product_id && get_post($product_id)) : ?>
                                <a class="wo-item__link" href="<?php echo esc_url(get_edit_post_link($product_id)); ?>">Edit product</a>
                            <?php endif; ?>
                        </div>
                        <div class="wo-item__price">
                            <span><?php echo esc_html($qty . ' × ' . $money($subtotal / $qty)); ?></span>
                            <strong><?php echo esc_html($money($subtotal)); ?></strong>
                        </div>
                    </div>
                    <?php if ($specs) : ?>
                        <dl class="wo-specs">
                            <?php foreach ($specs as $label => $value) : ?>
                                <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo $value; // Escaped by wholesale_item_specs(). ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                    <?php if (is_array($design)) {
                        wholesale_render_cl_breakdown($design);
                    } ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <dl class="wo-totals">
        <div><dt>Subtotal</dt><dd><?php echo esc_html($money($cost['sub_total'] ?? 0)); ?></dd></div>
        <div><dt>Shipping<?php echo !empty($cost['shipping_method']) ? ' <small>' . esc_html($cost['shipping_method']) . '</small>' : ''; ?></dt><dd><?php echo esc_html($money($cost['shipping_cost'] ?? 0)); ?></dd></div>
        <div><dt>Tax</dt><dd><?php echo esc_html($money($cost['tax'] ?? 0)); ?></dd></div>
        <div class="wo-totals__grand"><dt>Total</dt><dd><?php echo esc_html($money($cost['grand_total'] ?? 0)); ?></dd></div>
    </dl>
    <?php
}

/**
 * Editable address fields, keyed by the suffix after the "billing_"/"shipping_" prefix.
 */
function wholesale_order_address_fields($with_email)
{
    $fields = array(
        'fname' => 'First name',
        'lname' => 'Last name',
        'company' => 'Company',
        'email' => 'Email',
        'tel' => 'Phone',
        'address' => 'Address',
        'address_2' => 'Apt, suite, etc.',
        'city' => 'City',
        'state' => 'State',
        'zip' => 'ZIP',
        'country' => 'Country',
    );
    if (!$with_email) {
        unset($fields['email']);
    }
    return $fields;
}

/**
 * Which prefix an order's shipping address uses. Older orders copied the billing keys.
 */
function wholesale_order_shipping_prefix($shipping)
{
    return isset($shipping['shipping_fname']) || !isset($shipping['billing_fname']) ? 'shipping_' : 'billing_';
}

function render_order_meta_box($post)
{
    $post_id = $post->ID;
    $shipping = wholesale_decode_order_meta_array(get_post_meta($post_id, 'shipping_address', true));
    $billing = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
    $comment = get_post_meta($post_id, 'order_comment', true);
    $staff_note = get_post_meta($post_id, '_staff_note', true);
    $ship_date = get_post_meta($post_id, 'estimate_delivery_time', true);
    $ship_prefix = wholesale_order_shipping_prefix($shipping);

    $address = static function ($data, $prefix) {
        $lines = array(
            trim(($data[$prefix . 'fname'] ?? '') . ' ' . ($data[$prefix . 'lname'] ?? '')),
            $data[$prefix . 'company'] ?? '',
            trim(($data[$prefix . 'address'] ?? '') . (!empty($data[$prefix . 'address_2']) ? ', ' . $data[$prefix . 'address_2'] : '')),
            trim(($data[$prefix . 'city'] ?? '') . ', ' . ($data[$prefix . 'state'] ?? '') . ' ' . ($data[$prefix . 'zip'] ?? ''), ', '),
            $data[$prefix . 'country'] ?? '',
        );
        $lines = array_filter($lines);
        return $lines ? implode('<br>', array_map('esc_html', $lines)) : '<span class="wo-muted">Not provided</span>';
    };
    $inputs = static function ($data, $prefix, $name, $with_email) {
        foreach (wholesale_order_address_fields($with_email) as $key => $label) {
            $id = 'wo_' . $name . '_' . $key;
            $wide = in_array($key, array('company', 'address', 'email'), true) ? ' wo-field--wide' : '';
            printf(
                '<p class="wo-field%1$s"><label for="%2$s">%3$s</label><input type="%4$s" id="%2$s" name="wo_%5$s[%6$s]" value="%7$s"></p>',
                esc_attr($wide),
                esc_attr($id),
                esc_html($label),
                'email' === $key ? 'email' : ('tel' === $key ? 'tel' : 'text'),
                esc_attr($name),
                esc_attr($key),
                esc_attr((string) ($data[$prefix . $key] ?? ''))
            );
        }
    };
    ?>
    <div class="wo-details" data-editing="0">
        <input type="hidden" name="wo_details_edited" value="0">
        <div class="wo-details__bar">
            <button type="button" class="button wo-details__edit"><span class="dashicons dashicons-edit" aria-hidden="true"></span> Edit details</button>
            <button type="button" class="button-link wo-details__cancel">Cancel editing</button>
        </div>

        <div class="wo-grid">
            <section>
                <h3>Customer</h3>
                <div class="wo-view">
                    <p><?php echo $address($billing, 'billing_'); // Escaped per line. ?></p>
                    <p>
                        <?php if (!empty($billing['billing_email'])) : ?><a href="mailto:<?php echo esc_attr($billing['billing_email']); ?>"><?php echo esc_html($billing['billing_email']); ?></a><br><?php endif; ?>
                        <?php if (!empty($billing['billing_tel'])) : ?><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $billing['billing_tel'])); ?>"><?php echo esc_html($billing['billing_tel']); ?></a><?php endif; ?>
                    </p>
                </div>
                <div class="wo-edit wo-fields"><?php $inputs($billing, 'billing_', 'billing', true); ?></div>
            </section>

            <section>
                <h3>Ship to</h3>
                <div class="wo-view">
                    <p><?php echo $address($shipping, $ship_prefix); // Escaped per line. ?></p>
                    <?php if (!empty($shipping[$ship_prefix . 'tel'])) : ?><p><?php echo esc_html($shipping[$ship_prefix . 'tel']); ?></p><?php endif; ?>
                </div>
                <div class="wo-edit">
                    <p><button type="button" class="button-link wo-copy-billing">Copy from customer</button></p>
                    <div class="wo-fields"><?php $inputs($shipping, $ship_prefix, 'shipping', false); ?></div>
                </div>
            </section>

            <section>
                <h3>Delivery</h3>
                <div class="wo-view">
                    <p>Estimated ship date<br><strong><?php echo esc_html($ship_date ?: '—'); ?></strong></p>
                </div>
                <div class="wo-edit wo-fields">
                    <p class="wo-field wo-field--wide"><label for="wo_ship_date">Estimated ship date</label><input type="text" id="wo_ship_date" name="wo_ship_date" value="<?php echo esc_attr($ship_date); ?>" placeholder="e.g. Fri Oct. 9"></p>
                </div>
                <h3>Customer notes</h3>
                <p><?php echo $comment ? nl2br(esc_html($comment)) : '<span class="wo-muted">None</span>'; ?></p>
            </section>
        </div>

        <div class="wo-staff-note">
            <label for="wo_staff_note"><strong>Internal note</strong> <span class="wo-muted">— only staff can see this</span></label>
            <textarea id="wo_staff_note" name="wo_staff_note" rows="3" placeholder="Proof approved by phone, rush requested, etc."><?php echo esc_textarea($staff_note); ?></textarea>
        </div>
    </div>
    <?php
}
