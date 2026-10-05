<?php
// Template Name: My Orders
if (!is_user_logged_in()) {
    wp_safe_redirect(add_query_arg('redirect_ulr', rawurlencode(get_permalink()), home_url('/login/')));
    exit;
}

$orders = get_posts(array(
    'post_type' => 'order',
    'post_status' => array_keys(wholesale_order_status_info()),
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
                <p>Track your signs and review past orders.</p>
            </div>
            <a class="cart-secondary" href="<?php echo esc_url(home_url('/account/')); ?>">Account details</a>
        </header>

        <?php if (!$orders) : ?>
            <section class="checkout-card cart-empty">
                <h2>No orders yet</h2>
                <p>When you place an order it will appear here with its status and tracking.</p>
                <div class="cart-empty-actions">
                    <a class="checkout-pay" href="<?php echo esc_url(home_url('/#product-box-container')); ?>">Shop Channel Letters</a>
                </div>
            </section>
        <?php else : ?>
            <ul class="order-list">
                <?php foreach ($orders as $order) :
                    $items = wholesale_decode_order_meta_array(get_post_meta($order->ID, 'product_json', true));
                    $cost = wholesale_decode_order_meta_array(get_post_meta($order->ID, 'product_cost', true));
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
                                <a class="order-row-number" href="<?php echo esc_url(get_permalink($order)); ?>">Order #<?php echo esc_html(wholesale_order_number($order->ID)); ?></a>
                                <?php echo wholesale_order_status_badge($order->post_status); // Escaped in the helper. ?>
                            </div>
                            <p class="order-row-items">
                                <?php echo esc_html(implode(', ', array_map(static function ($item) {
                                    return ($item['product_title'] ?? '') . (($item['product_quantity'] ?? 1) > 1 ? ' × ' . $item['product_quantity'] : '');
                                }, $items))); ?>
                            </p>
                            <p class="order-row-meta">
                                Placed <?php echo esc_html(get_the_date('M j, Y', $order)); ?>
                                <?php if ('completed' !== $order->post_status && get_post_meta($order->ID, 'estimate_delivery_time', true)) : ?>
                                    &middot; Estimated to ship by <?php echo esc_html(get_post_meta($order->ID, 'estimate_delivery_time', true)); ?>
                                <?php endif; ?>
                                <?php if ($tracking) : ?>
                                    &middot; Tracking <?php echo esc_html($tracking); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="order-row-side">
                            <strong><?php echo esc_html('$' . number_format((float) ($cost['grand_total'] ?? 0), 2)); ?></strong>
                            <a href="<?php echo esc_url(get_permalink($order)); ?>">View details</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p class="account-help">Questions about an order? Call <a href="tel:+18664362101">866-436-2101</a> (Mon&ndash;Fri, 8am&ndash;5pm PST).</p>
    </div>
</main>

<?php
get_footer();
