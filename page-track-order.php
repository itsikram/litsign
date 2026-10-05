<?php
/**
 * Template Name: Track Order
 *
 * Public order tracking: customers look up an order with its number and the email used at
 * checkout, no account needed. Shows status, progress, tracking and items, but no prices,
 * payment details or full addresses (those stay behind login on the order page).
 *
 * @package litsign
 */

$track_number = isset($_GET['order']) ? wholesale_normalize_order_number(wp_unslash($_GET['order'])) : '';
$track_email = '';
$track_error = '';
$order_post_id = 0;

// Lookups are read-only, so no nonce (it would go stale behind page caching); failed attempts are rate limited per IP.
if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['track_order_number'])) {
    $track_number = wholesale_normalize_order_number(wp_unslash($_POST['track_order_number']));
    $track_email = sanitize_email(wp_unslash($_POST['track_order_email'] ?? ''));
    $limit_key = 'wholesale_track_fail_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $failures = (int) get_transient($limit_key);

    if ($failures >= 10) {
        $track_error = 'Too many attempts. Please wait 15 minutes and try again, or call us at 866-436-2101.';
    } elseif ('' === $track_number || !is_email($track_email)) {
        $track_error = 'Enter your order number and the email address you used at checkout.';
    } else {
        $order_post_id = wholesale_find_order_for_tracking($track_number, $track_email);
        if (!$order_post_id) {
            set_transient($limit_key, $failures + 1, 15 * MINUTE_IN_SECONDS);
            $track_error = 'We couldn\'t find an order with that number and email. Check your order confirmation email and try again.';
        }
    }
}

if ($order_post_id) {
    $status = get_post_status($order_post_id);
    $status_info = wholesale_order_status_info();
    $current = $status_info[$status] ?? array('label' => ucwords(str_replace(array('-', '_'), ' ', (string) $status)), 'tone' => 'muted', 'step' => 1, 'note' => '');
    $step = (int) $current['step'];
    $timeline = wholesale_order_timeline($order_post_id);
    $items = wholesale_decode_order_meta_array(get_post_meta($order_post_id, 'product_json', true));
    $cost = wholesale_decode_order_meta_array(get_post_meta($order_post_id, 'product_cost', true));
    $shipping = wholesale_decode_order_meta_array(get_post_meta($order_post_id, 'shipping_address', true));
    $ship_prefix = isset($shipping['shipping_fname']) ? 'shipping_' : 'billing_';
    if ('billing_' === $ship_prefix) {
        $shipping = wholesale_decode_order_meta_array(get_post_meta($order_post_id, 'billing_address', true));
    }
    $ship_place = trim(($shipping[$ship_prefix . 'city'] ?? '') . ', ' . ($shipping[$ship_prefix . 'state'] ?? ''), ', ');
    $ship_by = get_post_meta($order_post_id, 'estimate_delivery_time', true);
    $tracking = get_post_meta($order_post_id, '_tracking_number', true);
    $carrier = get_post_meta($order_post_id, '_tracking_carrier', true);
    $tracking_url = $tracking ? wholesale_tracking_url($carrier, $tracking) : '';
    $carrier_names = array('ups' => 'UPS', 'fedex' => 'FedEx', 'usps' => 'USPS');
    $carrier_label = $carrier_names[strtolower((string) $carrier)] ?? ($carrier ? strtoupper($carrier) : '');
    $order_number = wholesale_order_number($order_post_id);
    $owner = absint(get_post_meta($order_post_id, 'user_id', true));
    $can_view_full = current_user_can('edit_post', $order_post_id) || ($owner && get_current_user_id() === $owner);

    // Date each progress step was reached, from the status log.
    $step_dates = array(1 => $timeline[0]['time']);
    foreach ($timeline as $event) {
        $event_step = $status_info[$event['status']]['step'] ?? 0;
        if ($event_step > 1 && !isset($step_dates[$event_step])) {
            $step_dates[$event_step] = $event['time'];
        }
    }
    if ($step >= 2 && !isset($step_dates[2])) {
        $step_dates[2] = $timeline[0]['time']; // Paid orders start in production.
    }

    $headline = array(
        'pending' => 'We\'re reviewing your order',
        'processing' => 'Your order is in production',
        'completed' => 'Your order has shipped',
        'on-hold' => 'Your order is on hold',
        'on_hold' => 'Your order is on hold',
        'cancelled' => 'This order was cancelled',
        'refunded' => 'This order was refunded',
        'failed' => 'Payment didn\'t go through',
    );
    $headline = $headline[$status] ?? $current['label'];
}

get_header();
?>

<main id="primary" class="account-page-v2 track-page">
    <div class="container account-container">
        <header class="track-hero">
            <p class="track-eyebrow">Order tracking</p>
            <h1>Track your order</h1>
            <p>Enter the order number from your confirmation email and the email address you used at checkout.</p>
        </header>

        <form class="checkout-card track-form" method="post" action="<?php echo esc_url(get_permalink()); ?>"<?php echo $order_post_id ? ' aria-label="Look up another order"' : ''; ?>>
            <p class="checkout-field">
                <label for="trackOrderNumber">Order number</label>
                <input type="text" id="trackOrderNumber" name="track_order_number" required placeholder="e.g. SN6LTHF5IA" autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?php echo esc_attr($track_number); ?>">
            </p>
            <p class="checkout-field">
                <label for="trackOrderEmail">Email</label>
                <input type="email" id="trackOrderEmail" name="track_order_email" required placeholder="you@company.com" autocomplete="email" value="<?php echo esc_attr($track_email); ?>">
            </p>
            <button type="submit" class="checkout-pay track-submit">Track order</button>
            <?php if ($track_error) : ?>
                <p class="track-error" role="alert"><?php echo esc_html($track_error); ?></p>
            <?php endif; ?>
        </form>

        <?php if ($order_post_id) : ?>
            <section class="track-result" aria-labelledby="track-headline" tabindex="-1" id="track-result">
                <div class="checkout-card track-status track-status--<?php echo esc_attr($current['tone']); ?>">
                    <div class="track-status-head">
                        <div>
                            <p class="track-order-number">Order #<?php echo esc_html($order_number); ?> &middot; Placed <?php echo esc_html(get_the_date('F j, Y', $order_post_id)); ?></p>
                            <h2 id="track-headline"><?php echo esc_html($headline); ?></h2>
                            <?php if ($current['note']) : ?>
                                <p class="track-note"><?php echo esc_html($current['note']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php echo wholesale_order_status_badge($status); // Escaped in the helper. ?>
                    </div>

                    <?php if ($step > 0) : ?>
                        <ol class="order-progress track-progress" aria-label="Order progress">
                            <?php foreach (array(1 => 'Order received', 2 => 'In production', 3 => 'Shipped') as $index => $label) : ?>
                                <li class="<?php echo $index < $step || 3 === $step ? 'is-done' : ($index === $step ? 'is-current' : ''); ?>"<?php echo $index === $step ? ' aria-current="step"' : ''; ?>>
                                    <span><?php echo esc_html($label); ?></span>
                                    <?php if ($index <= $step && isset($step_dates[$index])) : ?>
                                        <small><?php echo esc_html(wp_date('M j', $step_dates[$index])); ?></small>
                                    <?php elseif (3 === $index && $ship_by) : ?>
                                        <small>Est. <?php echo esc_html($ship_by); ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>

                    <?php if ($tracking) : ?>
                        <div class="track-shipment">
                            <div>
                                <span class="track-label"><?php echo esc_html($carrier_label ? $carrier_label . ' tracking number' : 'Tracking number'); ?></span>
                                <span class="track-number">
                                    <code><?php echo esc_html($tracking); ?></code>
                                    <button type="button" class="track-copy" data-copy="<?php echo esc_attr($tracking); ?>">Copy</button>
                                </span>
                            </div>
                            <?php if ($tracking_url) : ?>
                                <a class="checkout-pay track-carrier-link" href="<?php echo esc_url($tracking_url); ?>" target="_blank" rel="noopener">Track on <?php echo esc_html($carrier_label); ?> &rarr;</a>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($step > 0 && $step < 3 && $ship_by) : ?>
                        <p class="track-eta">Estimated to ship by <strong><?php echo esc_html($ship_by); ?></strong>. We'll email you a tracking number when it ships.</p>
                    <?php endif; ?>
                </div>

                <div class="track-grid">
                    <section class="checkout-card" aria-labelledby="track-items">
                        <h2 id="track-items">Items in this order</h2>
                        <ul class="track-items">
                            <?php foreach ($items as $item) :
                                $details = (array) ($item['product_details'] ?? array());
                                $image = !empty($details['Design Url']) ? $details['Design Url'] : ($item['product_thumbnail'] ?? '');
                                ?>
                                <li>
                                    <?php if ($image) : ?>
                                        <img src="<?php echo esc_url($image); ?>" alt="" width="64" height="64" loading="lazy">
                                    <?php else : ?>
                                        <span class="track-item-placeholder" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <span>
                                        <strong><?php echo esc_html($item['product_title'] ?? 'Custom sign'); ?></strong>
                                        <small>Qty <?php echo esc_html(max(1, (int) ($item['product_quantity'] ?? 1))); ?><?php echo !empty($item['job_name']) ? ' &middot; ' . esc_html($item['job_name']) : ''; ?></small>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <dl class="confirmation-meta track-meta">
                            <?php if ($ship_place) : ?>
                                <div><dt>Shipping to</dt><dd><?php echo esc_html($ship_place); ?></dd></div>
                            <?php endif; ?>
                            <?php if (!empty($cost['shipping_method'])) : ?>
                                <div><dt>Shipping method</dt><dd><?php echo esc_html($cost['shipping_method']); ?></dd></div>
                            <?php endif; ?>
                        </dl>
                        <?php if ($can_view_full) : ?>
                            <a class="cart-secondary track-full-link" href="<?php echo esc_url(get_permalink($order_post_id)); ?>">View full order details</a>
                        <?php elseif ($owner) : ?>
                            <p class="account-help"><a href="<?php echo esc_url(add_query_arg('redirect_ulr', rawurlencode(get_permalink($order_post_id)), home_url('/login/'))); ?>">Sign in</a> to see prices, payment and full addresses.</p>
                        <?php endif; ?>
                    </section>

                    <section class="checkout-card" aria-labelledby="track-activity">
                        <h2 id="track-activity">Order activity</h2>
                        <ol class="track-timeline">
                            <?php foreach (array_reverse($timeline) as $i => $event) :
                                $tone = 'placed' === $event['status'] ? 'info' : ($status_info[$event['status']]['tone'] ?? 'muted');
                                ?>
                                <li class="track-event track-event--<?php echo esc_attr($tone); ?><?php echo 0 === $i ? ' is-latest' : ''; ?>">
                                    <strong><?php echo esc_html($event['label']); ?></strong>
                                    <time datetime="<?php echo esc_attr(gmdate('c', $event['time'])); ?>"><?php echo esc_html(wp_date('M j, Y · g:i a', $event['time'])); ?></time>
                                    <?php if ('completed' === $event['status'] && $tracking) : ?>
                                        <span><?php echo esc_html(($carrier_label ? $carrier_label . ' ' : '') . $tracking); ?></span>
                                    <?php elseif ($event['note']) : ?>
                                        <span><?php echo esc_html($event['note']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                </div>
            </section>
        <?php endif; ?>

        <aside class="track-help">
            <div>
                <h2>Need help with your order?</h2>
                <p>Our team is available Monday&ndash;Friday, 8am&ndash;5pm PST. Have your order number ready.</p>
            </div>
            <div class="track-help-actions">
                <a class="checkout-pay" href="tel:+18664362101">Call 866-436-2101</a>
                <a class="cart-secondary" href="mailto:TR@StorefrontSignOnline.com<?php echo $order_post_id ? '?subject=' . rawurlencode('Order #' . $order_number) : ''; ?>">Email us</a>
            </div>
            <?php if (is_user_logged_in()) : ?>
                <p class="account-help">Signed in? See every order in <a href="<?php echo esc_url(home_url('/my-orders/')); ?>">My orders</a>.</p>
            <?php endif; ?>
        </aside>
    </div>
</main>

<script>
(function () {
    var result = document.getElementById('track-result');
    if (result) {
        result.focus({ preventScroll: true });
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    document.querySelectorAll('.track-copy').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!navigator.clipboard) {
                return;
            }
            navigator.clipboard.writeText(button.dataset.copy).then(function () {
                button.textContent = 'Copied';
                setTimeout(function () { button.textContent = 'Copy'; }, 2000);
            });
        });
    });
    var form = document.querySelector('.track-form');
    if (form) {
        form.addEventListener('submit', function () {
            var submit = form.querySelector('.track-submit');
            submit.disabled = true;
            submit.textContent = 'Looking up your order…';
        });
    }
})();
</script>

<?php
get_footer();
