<?php
// Template Name: Cart

$cart = wholesale_get_cart();
$cart_url = get_permalink();

if (isset($_GET['remove_cart'])) {
    if (wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'wholesale_cart_remove')) {
        $cart->remove_item(sanitize_text_field(wp_unslash($_GET['remove_cart'])));
    }
    wp_safe_redirect($cart_url);
    exit;
}

if (isset($_POST['update_quantity'], $_POST['cart_id'])) {
    if (wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '', 'wholesale_cart_update')) {
        $cart->update_quantity(sanitize_text_field(wp_unslash($_POST['cart_id'])), min(1000, absint($_POST['update_quantity'])));
    }
    wp_safe_redirect($cart_url);
    exit;
}

if (isset($_REQUEST['product_id'])) {
    $cart->add_item();
}

$notice = '';
if (!empty($_SESSION['wholesale_cart_notice'])) {
    $notice = (string) $_SESSION['wholesale_cart_notice'];
    unset($_SESSION['wholesale_cart_notice']);
}

$items = $cart->get_items();
$shipping_options = wholesale_cart_shipping_options($cart);
$lowest_shipping = $shipping_options ? min($shipping_options) : 0;
$tax_rate = (float) wholesale_get_setting('tax_rate');
$sub_total = round((float) $cart->sub_total, 2);
$tax = round($sub_total * $tax_rate / 100, 2);
$money = static function ($value) {
    return '$' . number_format((float) $value, 2);
};

get_header();
?>

<main id="primary" class="cart-page-v2">
    <div class="container">
        <?php if ($items) : ?>
            <nav class="checkout-steps" aria-label="Checkout progress">
                <span class="is-current" aria-current="step">Cart</span>
                <span>Checkout</span>
                <span>Confirmation</span>
            </nav>
        <?php endif; ?>
        <h1 class="checkout-title">Your Cart</h1>

        <?php if ($notice) : ?>
            <div class="cart-notice" role="status"><?php echo esc_html($notice); ?></div>
        <?php endif; ?>

        <?php if (!$items) : ?>
            <section class="cart-empty checkout-card">
                <h2>Your cart is empty</h2>
                <p>Choose a channel letter style to see your price, or design your sign online.</p>
                <div class="cart-empty-actions">
                    <a class="checkout-pay" href="<?php echo esc_url(home_url('/#product-box-container')); ?>">Shop Channel Letters</a>
                    <a class="cart-secondary" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Design Your Sign Online</a>
                </div>
                <p class="cart-help">Need help? Call <a href="tel:+18664362101">866-436-2101</a>, Mon&ndash;Fri 8am&ndash;5pm PST.</p>
            </section>
        <?php else : ?>
            <div class="checkout-layout">
                <section class="cart-items" aria-label="Items in your cart">
                    <?php foreach ($items as $item) :
                        $details = (array) ($item->product_details ?? array());
                        $design_url = !empty($details['Design Url']) ? $details['Design Url'] : '';
                        $image = $design_url ?: ($item->product_thumbnail ?? '');
                        $specs = wholesale_item_specs($item);
                        unset($specs['Design Url']);
                        $quantity = max(1, (int) $item->product_quantity);
                        $is_cl = has_term('channel-letters', 'product_category', $item->product_id);
                        $edit_url = $is_cl && !empty($item->design_id)
                            ? add_query_arg(array('product_id' => absint($item->product_id), 'edit_design' => 'true'), home_url('/channel-letter-builder/'))
                            : '';
                        $design = $is_cl && !empty($item->design_id) ? json_decode((string) get_post_meta((int) $item->design_id, '_cl_data', true), true) : null;
                        ?>
                        <article class="checkout-card cart-line">
                            <a class="cart-line-image" href="<?php echo esc_url(get_permalink($item->product_id)); ?>">
                                <?php if ($image) : ?>
                                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($design_url ? 'Your sign design' : $item->product_title); ?>" loading="lazy">
                                <?php endif; ?>
                            </a>
                            <div class="cart-line-body">
                                <div class="cart-line-head">
                                    <h2><a href="<?php echo esc_url(get_permalink($item->product_id)); ?>"><?php echo esc_html($item->product_title); ?></a></h2>
                                    <strong class="cart-line-total"><?php echo esc_html($money($item->product_subtotal)); ?></strong>
                                </div>
                                <p class="cart-line-unit"><?php echo esc_html($money($item->product_subtotal / $quantity)); ?> each</p>

                                <?php if (is_array($design) && !empty($design['elements'])) : ?>
                                    <ul class="cart-line-design">
                                        <?php foreach (wholesale_cl_design_summary($design) as $line) : ?>
                                            <li><?php echo esc_html($line); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if ($specs) : ?>
                                    <details class="cart-line-specs">
                                        <summary>Sign details</summary>
                                        <dl>
                                            <?php foreach ($specs as $label => $value) : ?>
                                                <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo $value; // Escaped in wholesale_item_specs(). ?></dd></div>
                                            <?php endforeach; ?>
                                        </dl>
                                    </details>
                                <?php endif; ?>

                                <div class="cart-line-actions">
                                    <form method="post" class="cart-qty">
                                        <?php wp_nonce_field('wholesale_cart_update'); ?>
                                        <input type="hidden" name="cart_id" value="<?php echo esc_attr($item->cart_id); ?>">
                                        <label for="qty-<?php echo esc_attr($item->cart_id); ?>">Qty</label>
                                        <input id="qty-<?php echo esc_attr($item->cart_id); ?>" type="number" name="update_quantity" min="1" max="1000" value="<?php echo esc_attr($quantity); ?>" onchange="this.form.requestSubmit()">
                                        <noscript><button type="submit">Update</button></noscript>
                                    </form>
                                    <?php if ($edit_url) : ?>
                                        <a href="<?php echo esc_url($edit_url); ?>">Edit design</a>
                                    <?php endif; ?>
                                    <a class="cart-remove" href="<?php echo esc_url(wp_nonce_url(add_query_arg('remove_cart', $item->cart_id, $cart_url), 'wholesale_cart_remove')); ?>">Remove</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <a class="cart-continue" href="<?php echo esc_url(home_url('/#product-box-container')); ?>">&larr; Continue shopping</a>
                </section>

                <aside class="checkout-summary" aria-labelledby="cart-summary-title">
                    <div class="checkout-card checkout-summary-card">
                        <h2 id="cart-summary-title">Order summary</h2>
                        <dl class="checkout-totals">
                            <div><dt>Subtotal (<?php echo esc_html(count($items)); ?> <?php echo esc_html(_n('item', 'items', count($items), 'litsign')); ?>)</dt><dd><?php echo esc_html($money($sub_total)); ?></dd></div>
                            <div><dt>Shipping</dt><dd>from <?php echo esc_html($money($lowest_shipping)); ?></dd></div>
                            <div><dt>Estimated tax</dt><dd><?php echo esc_html($money($tax)); ?></dd></div>
                            <div class="checkout-grand"><dt>Estimated total</dt><dd><?php echo esc_html($money($sub_total + $lowest_shipping + $tax)); ?></dd></div>
                        </dl>
                        <a class="checkout-pay" href="<?php echo esc_url(home_url('/checkout/')); ?>">Proceed to Checkout</a>
                        <ul class="checkout-assurance">
                            <li>Choose your shipping speed at checkout</li>
                            <li>UL listed signs, tested before shipping</li>
                            <li>Questions? <a href="tel:+18664362101">866-436-2101</a></li>
                        </ul>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
