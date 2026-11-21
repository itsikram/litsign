<?php
// Template Name: My Orders
if (!is_user_logged_in()) {
    wp_safe_redirect(add_query_arg('redirect_ulr', rawurlencode(get_permalink()), home_url('/login/')));
    exit;
}

$orders = get_posts(array(
    'post_type' => 'order',
    'post_status' => array_keys(retails_order_status_info()),
    'meta_key' => 'user_id',
    'meta_value' => get_current_user_id(),
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
));

get_header();
?>

<main id="primary" class="account-page-v2">
    <div class="container account-container">
        <header class="acct-head">
            <div>
                <h1>My orders</h1>
                <p>Track your orders and review past purchases.</p>
            </div>
            <a class="cart-secondary" href="<?php echo esc_url(home_url('/account/')); ?>">Account details</a>
        </header>

        <?php if (!$orders) : ?>
            <section class="checkout-card cart-empty">
                <h2>No orders yet</h2>
                <p>When you place an order it will appear here with its status and tracking.</p>
                <div class="cart-empty-actions">
                    <a class="checkout-pay" href="<?php echo esc_url(home_url('/')); ?>">Shop Products</a>
                </div>
            </section>
        <?php else : ?>
            <ul class="order-list">
                <?php foreach ($orders as $order) :
                    $items = retails_decode_order_meta_array(get_post_meta($order->ID, 'product_json', true));
                    $cost = retails_decode_order_meta_array(get_post_meta($order->ID, 'product_cost', true));
                    $first = $items ? reset($items) : array();
                    $image = !empty($first['product_details']['Design Url']) ? $first['product_details']['Design Url'] : ($first['product_thumbnail'] ?? '');
                    $tracking = get_post_meta($order->ID, '_tracking_number', true);
                    ?>
                    <li class="checkout-card order-row">
                        <?php if ($image) : ?>
                            <img src="<?php echo esc_url($image); ?>" alt="" width="72" height="72" loading="lazy">
                        <?php endif; ?>
                        <div class="order-row-main">
                            <div class="order-row-top">
                                <a class="order-row-number" href="<?php echo esc_url(get_permalink($order)); ?>">Order #<?php echo esc_html(retails_order_number($order->ID)); ?></a>
                                <?php echo retails_order_status_badge($order->post_status, $order->ID); // Escaped in the helper. ?>
                            </div>
                            <p class="order-row-items">
                                <?php echo esc_html(implode(', ', array_map(static function ($item) {
                                    return ($item['product_title'] ?? '') . (($item['product_quantity'] ?? 1) > 1 ? ' × ' . $item['product_quantity'] : '');
                                }, $items))); ?>
                            </p>
                            <p class="order-row-meta">
                                Placed <?php echo esc_html(get_the_date('M j, Y', $order)); ?>
                                <?php if (!retails_order_is_shipped($order->post_status) && get_post_meta($order->ID, 'estimate_delivery_time', true)) : ?>
                                    &middot; Estimated to ship by <?php echo esc_html(get_post_meta($order->ID, 'estimate_delivery_time', true)); ?>
                                <?php endif; ?>
                                <?php $shipment_summary = (string) apply_filters('retails_my_orders_shipment_summary', '', $order->ID); ?>
                                <?php if ('' !== $shipment_summary) : ?>
                                    &middot; <?php echo $shipment_summary; // Status badge and estimate, escaped by the tracking sync plugin. ?>
                                <?php elseif ($tracking) : ?>
                                    &middot; Tracking <?php echo esc_html($tracking); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="order-row-side">
                            <?php $net = function_exists('retails_order_net_amounts') ? retails_order_net_amounts($order->ID) : null; // inc/order-net-total.php ?>
                            <?php if ($net && $net['has_refund']) : ?>
                                <strong><?php echo esc_html('$' . number_format($net['net'], 2)); ?></strong>
                                <small class="order-row-refund" style="color:#1a7f37;"><?php echo esc_html('$' . number_format($net['refunded'], 2) . ' refunded'); ?> &middot; <del style="color:#8a97a5;"><?php echo esc_html('$' . number_format($net['total'], 2)); ?></del></small>
                            <?php else : ?>
                                <strong><?php echo esc_html('$' . number_format((float) ($cost['grand_total'] ?? 0), 2)); ?></strong>
                            <?php endif; ?>
                            <?php if (retails_order_on_the_way($order->post_status)) : ?>
                                <a class="order-write-review" href="<?php echo esc_url(get_permalink($order) . '#shipments'); ?>">Track package</a>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(get_permalink($order)); ?>">View details</a>
                            <?php // Items ordered without artwork that can still get it (inc/order-artwork.php). ?>
                            <?php $artwork_missing = function_exists('retails_order_artwork_missing_lines') ? retails_order_artwork_missing_lines($order->ID) : array(); ?>
                            <?php if ($artwork_missing) : ?>
                                <a class="order-artwork-needed" href="<?php echo esc_url(get_permalink($order) . '#artwork-line-' . $artwork_missing[0]); ?>">Upload artwork</a>
                            <?php endif; ?>
                            <?php // Review prompts wait until the package arrives. ?>
                            <?php $review_links = retails_order_on_the_way($order->post_status) ? array() : retails_order_unreviewed_products($order->ID); ?>
                            <?php if ($review_links) : ?>
                                <a class="order-write-review" href="<?php echo esc_url(1 === count($review_links) ? reset($review_links) : get_permalink($order)); ?>">&#9733; Write a review</a>
                            <?php endif; ?>
                            <?php $google_review_url = retails_order_on_the_way($order->post_status) ? '' : retails_order_google_review_url($order->ID); ?>
                            <?php if ($google_review_url) : ?>
                                <a class="order-google-review-link" href="<?php echo esc_url($google_review_url); ?>" target="_blank" rel="noopener"><?php echo retails_google_logo_svg(14); ?> Review us on Google</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p class="account-help">Questions about an order? Call <a href="tel:+18664362101">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PT).</p>
    </div>
</main>

<?php
get_footer();
