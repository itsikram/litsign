<?php
/**
 * Order confirmation page (/thank-you/?order=…&key=…).
 *
 * @package litsign
 */

$order = wholesale_thank_you_order();
$order_number = $order ? wholesale_order_number($order) : '';
$items = $order ? wholesale_decode_order_meta_array(get_post_meta($order, 'product_json', true)) : array();
$cost = $order ? wholesale_decode_order_meta_array(get_post_meta($order, 'product_cost', true)) : array();
$billing = $order ? wholesale_decode_order_meta_array(get_post_meta($order, 'billing_address', true)) : array();
$shipping = $order ? wholesale_decode_order_meta_array(get_post_meta($order, 'shipping_address', true)) : array();
$payment_status = $order ? get_post_meta($order, '_payment_status', true) : '';
$card_last4 = $order ? get_post_meta($order, '_payment_card_last4', true) : '';
$ship_by = $order ? get_post_meta($order, 'estimate_delivery_time', true) : '';
$money = static function ($value) {
    return '$' . number_format((float) $value, 2);
};
$ship_prefix = isset($shipping['shipping_fname']) ? 'shipping_' : 'billing_';

get_header();
?>

<main id="primary" class="order-confirmation">
    <div class="container">
        <section class="confirmation-hero">
            <span class="confirmation-check" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="30" height="30"><path fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7"/></svg>
            </span>
            <h1>Thank you<?php echo !empty($billing['billing_fname']) ? ', ' . esc_html($billing['billing_fname']) : ''; ?>! Your order is confirmed.</h1>
            <?php if ($order) : ?>
                <p>Order <strong>#<?php echo esc_html($order_number); ?></strong>. A confirmation has been emailed to <strong><?php echo esc_html($billing['billing_email'] ?? ''); ?></strong>.</p>
            <?php else : ?>
                <p>We've received your order and emailed you a confirmation.</p>
            <?php endif; ?>
        </section>

        <div class="confirmation-grid">
            <section class="checkout-card" aria-labelledby="next-steps">
                <h2 id="next-steps">What happens next</h2>
                <ol class="confirmation-steps">
                    <li><strong>We review your order.</strong> Our team checks your sign details and contacts you if anything needs a closer look.</li>
                    <li><strong>We build and test your sign.</strong> Every sign is made to order and tested before it ships.</li>
                    <li><strong>Your sign ships.</strong><?php echo $ship_by ? ' Estimated to ship by <strong>' . esc_html($ship_by) . '</strong>.' : ''; ?> You'll get tracking details by email.</li>
                </ol>
                <p class="confirmation-help">Questions about your order? Call <a href="tel:+18664362101">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PST) or email <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a><?php echo $order ? ' with order #' . esc_html($order_number) : ''; ?>.</p>
                <div class="confirmation-actions">
                    <?php if (is_user_logged_in()) : ?>
                        <a class="checkout-pay" href="<?php echo esc_url(home_url('/my-orders/')); ?>">View my orders</a>
                    <?php endif; ?>
                    <a class="cart-secondary" href="<?php echo esc_url(home_url('/')); ?>">Back to home</a>
                </div>
            </section>

            <?php if ($order) : ?>
                <aside class="checkout-card checkout-summary-card" aria-labelledby="order-summary">
                    <h2 id="order-summary">Order summary</h2>
                    <ul class="checkout-items">
                        <?php foreach ($items as $item) : ?>
                            <li>
                                <?php $image = !empty($item['product_details']['Design Url']) ? $item['product_details']['Design Url'] : ($item['product_thumbnail'] ?? ''); ?>
                                <?php if ($image) : ?>
                                    <img src="<?php echo esc_url($image); ?>" alt="" width="56" height="56" loading="lazy">
                                <?php endif; ?>
                                <span class="checkout-item-name">
                                    <?php echo esc_html($item['product_title'] ?? ''); ?>
                                    <small>Qty <?php echo esc_html($item['product_quantity'] ?? 1); ?></small>
                                </span>
                                <span class="checkout-item-price"><?php echo esc_html($money($item['product_subtotal'] ?? 0)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="checkout-totals">
                        <div><dt>Subtotal</dt><dd><?php echo esc_html($money($cost['sub_total'] ?? 0)); ?></dd></div>
                        <div><dt>Shipping</dt><dd><?php echo esc_html($money($cost['shipping_cost'] ?? 0)); ?></dd></div>
                        <div><dt>Tax</dt><dd><?php echo esc_html($money($cost['tax'] ?? 0)); ?></dd></div>
                        <div class="checkout-grand"><dt>Total</dt><dd><?php echo esc_html($money($cost['grand_total'] ?? 0)); ?></dd></div>
                    </dl>
                    <dl class="confirmation-meta">
                        <div>
                            <dt>Payment</dt>
                            <dd><?php
                                if ('paid' === $payment_status || 'needs_review' === $payment_status) {
                                    echo esc_html('Paid by card' . ($card_last4 ? ' ending ' . $card_last4 : ''));
                                } else {
                                    echo esc_html('paid_offline' === $payment_status ? 'Paid' : 'We will contact you to arrange payment');
                                }
                            ?></dd>
                        </div>
                        <?php if (!empty($cost['shipping_method'])) : ?>
                            <div><dt>Shipping method</dt><dd><?php echo esc_html($cost['shipping_method']); ?></dd></div>
                        <?php endif; ?>
                        <div>
                            <dt>Ship to</dt>
                            <dd><?php echo esc_html(trim(($shipping[$ship_prefix . 'fname'] ?? '') . ' ' . ($shipping[$ship_prefix . 'lname'] ?? ''))); ?><br>
                                <?php echo esc_html($shipping[$ship_prefix . 'address'] ?? ''); ?><?php echo !empty($shipping[$ship_prefix . 'address_2']) ? ', ' . esc_html($shipping[$ship_prefix . 'address_2']) : ''; ?><br>
                                <?php echo esc_html(trim(($shipping[$ship_prefix . 'city'] ?? '') . ', ' . ($shipping[$ship_prefix . 'state'] ?? '') . ' ' . ($shipping[$ship_prefix . 'zip'] ?? ''))); ?></dd>
                        </div>
                    </dl>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
get_footer();
