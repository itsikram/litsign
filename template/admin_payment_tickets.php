<?php
/**
 * Payment tickets: the admin sets a fixed amount for a custom job, emails the
 * customer a private link, and the customer pays it on /pay/ (page-pay.php).
 * A paid ticket becomes a normal `order` post.
 *
 * @package litsign
 */

function wholesale_ticket_statuses()
{
    return array(
        'draft' => 'Draft',
        'sent' => 'Sent - awaiting payment',
        'accepted' => 'Accepted - collect payment manually',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    );
}

/**
 * @return array Ticket fields read from post meta.
 */
function wholesale_get_ticket($ticket_id)
{
    $status = get_post_meta($ticket_id, '_ticket_status', true);

    return array(
        'id' => (int) $ticket_id,
        'title' => (string) get_post_field('post_title', $ticket_id, 'raw'),
        'customer_name' => (string) get_post_meta($ticket_id, '_ticket_customer_name', true),
        'customer_email' => (string) get_post_meta($ticket_id, '_ticket_customer_email', true),
        'customer_phone' => (string) get_post_meta($ticket_id, '_ticket_customer_phone', true),
        'description' => (string) get_post_meta($ticket_id, '_ticket_description', true),
        'amount' => round((float) get_post_meta($ticket_id, '_ticket_amount', true), 2),
        'due' => (string) get_post_meta($ticket_id, '_ticket_due', true),
        'status' => $status ? $status : 'draft',
        'token' => (string) get_post_meta($ticket_id, '_ticket_token', true),
        'sent_at' => (string) get_post_meta($ticket_id, '_ticket_sent_at', true),
        'order_id' => (int) get_post_meta($ticket_id, '_ticket_order_id', true),
    );
}

function wholesale_ticket_is_expired($ticket)
{
    return '' !== $ticket['due'] && current_time('Y-m-d') > $ticket['due'];
}

function wholesale_format_ticket_amount($amount)
{
    return '$' . number_format((float) $amount, 2, '.', ',');
}

/**
 * The /pay/ page renders page-pay.php. Create it the first time a ticket is sent
 * so the admin never has to set it up by hand.
 */
function wholesale_ticket_pay_page_url()
{
    $page = get_page_by_path('pay');
    if (!$page) {
        wp_insert_post(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Pay',
            'post_name' => 'pay',
        ));
    }

    return home_url('/pay/');
}

function wholesale_ticket_payment_url($ticket)
{
    return add_query_arg('t', rawurlencode($ticket['token']), wholesale_ticket_pay_page_url());
}

/**
 * @return int Ticket ID, or 0 when the token does not match a ticket.
 */
function wholesale_find_ticket_by_token($token)
{
    $token = preg_replace('/[^A-Za-z0-9]/', '', (string) $token);
    if (strlen($token) < 20) {
        return 0;
    }

    $ids = get_posts(array(
        'post_type' => 'payment_ticket',
        'post_status' => 'any',
        'meta_key' => '_ticket_token',
        'meta_value' => $token,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ));

    if (empty($ids) || !hash_equals((string) get_post_meta($ids[0], '_ticket_token', true), $token)) {
        return 0;
    }

    return (int) $ids[0];
}

/* ---------------------------------------------------------------------------
 * Admin screen
 * ------------------------------------------------------------------------ */

function wholesale_ticket_title_placeholder($placeholder, $post)
{
    return 'payment_ticket' === $post->post_type ? 'Job name, e.g. Channel letters - Pho Saigon 12ft' : $placeholder;
}
add_filter('enter_title_here', 'wholesale_ticket_title_placeholder', 10, 2);

function wholesale_ticket_add_meta_boxes()
{
    add_meta_box('wholesale_ticket_details', 'Customer & Amount', 'wholesale_render_ticket_details_box', 'payment_ticket', 'normal', 'high');
    add_meta_box('wholesale_ticket_send', 'Send to Customer', 'wholesale_render_ticket_send_box', 'payment_ticket', 'side', 'high');
}
add_action('add_meta_boxes', 'wholesale_ticket_add_meta_boxes');

function wholesale_render_ticket_details_box($post)
{
    $ticket = wholesale_get_ticket($post->ID);
    $locked = in_array($ticket['status'], array('paid', 'accepted'), true);
    wp_nonce_field('wholesale_save_ticket', 'wholesale_ticket_nonce');
    ?>
    <?php if ($locked) : ?>
        <p><strong>This ticket has been <?php echo esc_html(strtolower(wholesale_ticket_statuses()[$ticket['status']])); ?>, so its details can no longer be changed.</strong></p>
    <?php endif; ?>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="ticket_customer_name">Customer name <span style="color:#d63638">*</span></label></th>
            <td><input type="text" class="regular-text" id="ticket_customer_name" name="ticket_customer_name" value="<?php echo esc_attr($ticket['customer_name']); ?>" <?php disabled($locked); ?>></td>
        </tr>
        <tr>
            <th><label for="ticket_customer_email">Customer email <span style="color:#d63638">*</span></label></th>
            <td><input type="email" class="regular-text" id="ticket_customer_email" name="ticket_customer_email" value="<?php echo esc_attr($ticket['customer_email']); ?>" <?php disabled($locked); ?>>
                <p class="description">The payment link is emailed here.</p></td>
        </tr>
        <tr>
            <th><label for="ticket_customer_phone">Customer phone</label></th>
            <td><input type="tel" class="regular-text" id="ticket_customer_phone" name="ticket_customer_phone" value="<?php echo esc_attr($ticket['customer_phone']); ?>" <?php disabled($locked); ?>></td>
        </tr>
        <tr>
            <th><label for="ticket_description">What the customer is paying for</label></th>
            <td><textarea class="large-text" rows="5" id="ticket_description" name="ticket_description" <?php disabled($locked); ?>><?php echo esc_textarea($ticket['description']); ?></textarea>
                <p class="description">Shown on the payment page and in the email, e.g. size, colours, installation, deposit or balance.</p></td>
        </tr>
        <tr>
            <th><label for="ticket_amount">Amount to pay (USD) <span style="color:#d63638">*</span></label></th>
            <td>$ <input type="number" min="0.01" step="0.01" id="ticket_amount" name="ticket_amount" value="<?php echo esc_attr($ticket['amount'] > 0 ? number_format($ticket['amount'], 2, '.', '') : ''); ?>" style="width:140px" <?php disabled($locked); ?>>
                <p class="description">This is the exact total the customer is charged. No tax or shipping is added, so include them here if needed.</p></td>
        </tr>
        <tr>
            <th><label for="ticket_due">Pay by (optional)</label></th>
            <td><input type="date" id="ticket_due" name="ticket_due" value="<?php echo esc_attr($ticket['due']); ?>" <?php disabled($locked); ?>>
                <p class="description">After this date the link stops accepting payment.</p></td>
        </tr>
    </table>
    <?php
}

function wholesale_render_ticket_send_box($post)
{
    $ticket = wholesale_get_ticket($post->ID);
    $statuses = wholesale_ticket_statuses();
    ?>
    <p><strong>Status:</strong> <?php echo esc_html($statuses[$ticket['status']] ?? $ticket['status']); ?>
        <?php if ('sent' === $ticket['status'] && wholesale_ticket_is_expired($ticket)) : ?><em>(expired)</em><?php endif; ?></p>

    <?php if ($ticket['sent_at']) : ?>
        <p><strong>Last sent:</strong> <?php echo esc_html($ticket['sent_at']); ?></p>
    <?php endif; ?>

    <?php if ($ticket['order_id']) : ?>
        <p><strong>Order:</strong> <a href="<?php echo esc_url(get_edit_post_link($ticket['order_id'])); ?>"><?php echo esc_html(get_post_meta($ticket['order_id'], 'order_id', true) ?: '#' . $ticket['order_id']); ?></a></p>
    <?php endif; ?>

    <?php if ($ticket['token'] && !in_array($ticket['status'], array('paid', 'accepted', 'cancelled'), true)) : ?>
        <p><label for="wholesale-ticket-link"><strong>Payment link</strong></label>
            <input type="text" readonly id="wholesale-ticket-link" class="widefat" value="<?php echo esc_attr(wholesale_ticket_payment_url($ticket)); ?>" onclick="this.select()">
            <button type="button" class="button button-small" style="margin-top:4px" onclick="var i=document.getElementById('wholesale-ticket-link');i.select();navigator.clipboard&&navigator.clipboard.writeText(i.value);this.textContent='Copied';">Copy link</button>
        </p>
        <p class="description">You can also paste this link into a text message or chat.</p>
    <?php endif; ?>

    <?php if (in_array($ticket['status'], array('draft', 'sent'), true)) : ?>
        <p>
            <button type="submit" name="wholesale_ticket_send" value="1" class="button button-primary button-large" style="width:100%">
                <?php echo 'sent' === $ticket['status'] ? 'Save &amp; resend email' : 'Save &amp; send to customer'; ?>
            </button>
        </p>
        <p class="description">Saves the details above and emails the customer a "Pay now" link.</p>
        <?php if ('sent' === $ticket['status']) : ?>
            <p><button type="submit" name="wholesale_ticket_cancel" value="1" class="button-link button-link-delete" onclick="return confirm('Cancel this ticket? The payment link will stop working.');">Cancel ticket</button></p>
        <?php endif; ?>
    <?php endif; ?>
    <?php
}

/**
 * Publish the ticket when it is sent so it shows as a normal entry in the list.
 */
function wholesale_ticket_publish_on_send($data, $postarr)
{
    if ('payment_ticket' === $data['post_type'] && !empty($_POST['wholesale_ticket_send']) && current_user_can('publish_posts')) {
        $data['post_status'] = 'publish';
    }

    return $data;
}
add_filter('wp_insert_post_data', 'wholesale_ticket_publish_on_send', 10, 2);

function wholesale_save_ticket($post_id)
{
    $nonce = isset($_POST['wholesale_ticket_nonce']) ? sanitize_text_field(wp_unslash($_POST['wholesale_ticket_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'wholesale_save_ticket')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $ticket = wholesale_get_ticket($post_id);
    if (in_array($ticket['status'], array('paid', 'accepted'), true)) {
        return;
    }

    $due = isset($_POST['ticket_due']) ? sanitize_text_field(wp_unslash($_POST['ticket_due'])) : '';
    if ($due && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
        $due = '';
    }

    update_post_meta($post_id, '_ticket_customer_name', isset($_POST['ticket_customer_name']) ? sanitize_text_field(wp_unslash($_POST['ticket_customer_name'])) : '');
    update_post_meta($post_id, '_ticket_customer_email', isset($_POST['ticket_customer_email']) ? sanitize_email(wp_unslash($_POST['ticket_customer_email'])) : '');
    update_post_meta($post_id, '_ticket_customer_phone', isset($_POST['ticket_customer_phone']) ? sanitize_text_field(wp_unslash($_POST['ticket_customer_phone'])) : '');
    update_post_meta($post_id, '_ticket_description', isset($_POST['ticket_description']) ? sanitize_textarea_field(wp_unslash($_POST['ticket_description'])) : '');
    update_post_meta($post_id, '_ticket_amount', isset($_POST['ticket_amount']) ? max(0, round((float) preg_replace('/[^0-9.]/', '', (string) wp_unslash($_POST['ticket_amount'])), 2)) : 0);
    update_post_meta($post_id, '_ticket_due', $due);
    if (!get_post_meta($post_id, '_ticket_status', true)) {
        update_post_meta($post_id, '_ticket_status', 'draft');
    }

    if (!empty($_POST['wholesale_ticket_cancel']) && 'sent' === $ticket['status']) {
        update_post_meta($post_id, '_ticket_status', 'cancelled');
        wholesale_ticket_set_notice('cancelled');
        return;
    }

    if (!empty($_POST['wholesale_ticket_send'])) {
        wholesale_ticket_set_notice(wholesale_send_ticket($post_id));
    }
}
add_action('save_post_payment_ticket', 'wholesale_save_ticket');

/**
 * Email the customer their payment link.
 *
 * @return string Notice code for the admin screen.
 */
function wholesale_send_ticket($ticket_id)
{
    $ticket = wholesale_get_ticket($ticket_id);

    if ('' === trim($ticket['title']) || '' === $ticket['customer_name'] || !is_email($ticket['customer_email']) || $ticket['amount'] <= 0) {
        return 'missing';
    }
    if (!in_array($ticket['status'], array('draft', 'sent'), true)) {
        return 'locked';
    }

    if ('' === $ticket['token']) {
        $ticket['token'] = wp_generate_password(32, false, false);
        update_post_meta($ticket_id, '_ticket_token', $ticket['token']);
    }
    // The link goes live now, even if the email below fails, so the admin can share it by hand.
    update_post_meta($ticket_id, '_ticket_status', 'sent');

    $pay_url = wholesale_ticket_payment_url($ticket);
    $amount = wholesale_format_ticket_amount($ticket['amount']);
    $business = 'Lit Sign Manufacturing';

    $subject = sprintf('%s payment request: %s for %s', $business, $amount, $ticket['title']);
    $message = '<html><body style="font-family:Arial,sans-serif;color:#1d2327;">'
        . '<p>Hi ' . esc_html($ticket['customer_name']) . ',</p>'
        . '<p>' . esc_html($business) . ' has sent you a payment request for your custom order.</p>'
        . '<table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;min-width:320px;">'
        . '<tr><th align="left">Job</th><td>' . esc_html($ticket['title']) . '</td></tr>'
        . ($ticket['description'] ? '<tr><th align="left">Details</th><td>' . nl2br(esc_html($ticket['description'])) . '</td></tr>' : '')
        . '<tr><th align="left">Amount due</th><td><strong>' . esc_html($amount) . '</strong></td></tr>'
        . ($ticket['due'] ? '<tr><th align="left">Pay by</th><td>' . esc_html(date_i18n(get_option('date_format'), strtotime($ticket['due']))) . '</td></tr>' : '')
        . '</table>'
        . '<p style="margin:24px 0;"><a href="' . esc_url($pay_url) . '" style="background:#1fa8de;color:#fff;padding:12px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Pay now</a></p>'
        . '<p>Or open this link: <a href="' . esc_url($pay_url) . '">' . esc_html($pay_url) . '</a></p>'
        . '<p>Questions? Reply to this email or call us at 866-436-2101.</p>'
        . '<p>Thank you,<br>' . esc_html($business) . '</p>'
        . '</body></html>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . get_option('admin_email'),
    );
    foreach (wholesale_contact_admin_recipients() as $admin_email) {
        $headers[] = 'Bcc: ' . $admin_email;
    }

    if (!wp_mail($ticket['customer_email'], $subject, $message, $headers)) {
        error_log('Wholesale: payment ticket email failed for ticket #' . $ticket_id);
        return 'send_failed';
    }

    update_post_meta($ticket_id, '_ticket_sent_at', current_time('mysql'));

    return 'sent';
}

function wholesale_ticket_set_notice($code)
{
    set_transient('wholesale_ticket_notice_' . get_current_user_id(), $code, 60);
}

function wholesale_ticket_admin_notice()
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || 'payment_ticket' !== $screen->post_type) {
        return;
    }

    $key = 'wholesale_ticket_notice_' . get_current_user_id();
    $code = get_transient($key);
    if (!$code) {
        return;
    }
    delete_transient($key);

    $notices = array(
        'sent' => array('success', 'Payment link emailed to the customer.'),
        'cancelled' => array('warning', 'Ticket cancelled. The payment link no longer works.'),
        'missing' => array('error', 'Not sent. Please fill in the job name, customer name, a valid customer email and an amount greater than $0.'),
        'locked' => array('error', 'Not sent. This ticket is already paid or cancelled.'),
        'send_failed' => array('error', 'The payment link is active, but the email could not be sent. Copy the link from the "Send to Customer" box and send it to the customer yourself.'),
    );
    if (!isset($notices[$code])) {
        return;
    }

    printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($notices[$code][0]), esc_html($notices[$code][1]));
}
add_action('admin_notices', 'wholesale_ticket_admin_notice');

function wholesale_ticket_list_columns($columns)
{
    return array(
        'cb' => $columns['cb'],
        'title' => 'Job',
        'ticket_customer' => 'Customer',
        'ticket_amount' => 'Amount',
        'ticket_status' => 'Status',
        'date' => $columns['date'],
    );
}
add_filter('manage_payment_ticket_posts_columns', 'wholesale_ticket_list_columns');

function wholesale_ticket_list_column_value($column, $post_id)
{
    $ticket = wholesale_get_ticket($post_id);

    switch ($column) {
        case 'ticket_customer':
            echo esc_html($ticket['customer_name']);
            if ($ticket['customer_email']) {
                echo '<br><small>' . esc_html($ticket['customer_email']) . '</small>';
            }
            break;
        case 'ticket_amount':
            echo esc_html(wholesale_format_ticket_amount($ticket['amount']));
            break;
        case 'ticket_status':
            $statuses = wholesale_ticket_statuses();
            echo esc_html($statuses[$ticket['status']] ?? $ticket['status']);
            if ('sent' === $ticket['status'] && wholesale_ticket_is_expired($ticket)) {
                echo ' <em>(expired)</em>';
            }
            break;
    }
}
add_action('manage_payment_ticket_posts_custom_column', 'wholesale_ticket_list_column_value', 10, 2);

/* ---------------------------------------------------------------------------
 * Customer payment (form lives in page-pay.php)
 * ------------------------------------------------------------------------ */

function wholesale_ticket_pay_redirect($token, $message = '')
{
    $url = add_query_arg('t', rawurlencode($token), home_url('/pay/'));
    if ($message) {
        $url = add_query_arg('message', rawurlencode($message), $url);
    }
    wp_safe_redirect($url . '#ticket-pay');
    exit;
}

function wholesale_handle_ticket_payment()
{
    $token = isset($_POST['ticket_token']) ? preg_replace('/[^A-Za-z0-9]/', '', (string) wp_unslash($_POST['ticket_token'])) : '';
    $ticket_id = wholesale_find_ticket_by_token($token);
    if (!$ticket_id) {
        wp_die(esc_html__('This payment link is not valid.', 'litsign'), esc_html__('Invalid payment link', 'litsign'), array('response' => 404));
    }

    $nonce = isset($_POST['wholesale_ticket_pay_nonce']) ? sanitize_text_field(wp_unslash($_POST['wholesale_ticket_pay_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'wholesale_pay_ticket_' . $ticket_id)) {
        wholesale_ticket_pay_redirect($token, 'Your session expired. Please try again.');
    }

    $field = static function ($key) {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
    };
    $billing = array(
        'billing_email' => isset($_POST['billing_email']) ? sanitize_email(wp_unslash($_POST['billing_email'])) : '',
        'billing_fname' => $field('billing_fname'),
        'billing_lname' => $field('billing_lname'),
        'billing_company' => $field('billing_company'),
        'billing_address' => $field('billing_address'),
        'billing_address_2' => $field('billing_address_2'),
        'billing_city' => $field('billing_city'),
        'billing_state' => $field('billing_state'),
        'billing_zip' => $field('billing_zip'),
        'billing_country' => 'United States',
        'billing_tel' => $field('billing_tel'),
    );

    if (!is_email($billing['billing_email']) || '' === $billing['billing_fname'] || '' === $billing['billing_lname'] || '' === $billing['billing_address'] || '' === $billing['billing_city'] || '' === $billing['billing_state'] || '' === $billing['billing_zip']) {
        wholesale_ticket_pay_redirect($token, 'Please provide a complete and valid billing address.');
    }

    $payment_disabled = wholesale_setting_enabled('payment_disabled');
    $card_number = isset($_POST['card_number']) ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['card_number'])) : '';
    $card_cvv = isset($_POST['card_cvv']) ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['card_cvv'])) : '';
    $card_exp_month = isset($_POST['card_exp_month']) ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['card_exp_month'])) : '';
    $card_exp_year = isset($_POST['card_exp_year']) ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['card_exp_year'])) : '';

    if (!$payment_disabled) {
        $exp_month_number = (int) $card_exp_month;
        $exp_year_number = 2000 + (int) $card_exp_year;
        $card_is_expired = $exp_year_number < (int) gmdate('Y')
            || ($exp_year_number === (int) gmdate('Y') && $exp_month_number < (int) gmdate('n'));

        if (
            strlen($card_number) < 12 || strlen($card_number) > 19
            || strlen($card_cvv) < 3 || strlen($card_cvv) > 4
            || 2 !== strlen($card_exp_month) || $exp_month_number < 1 || $exp_month_number > 12
            || 2 !== strlen($card_exp_year) || $card_is_expired
        ) {
            wholesale_ticket_pay_redirect($token, 'Please check your card number, CVV and expiration date.');
        }
    }

    // add_option() is atomic, so two submits of the same ticket can never both reach the gateway.
    $lock_key = 'wholesale_ticket_lock_' . $ticket_id;
    $lock_time = (int) get_option($lock_key);
    if ($lock_time && $lock_time < time() - 120) {
        delete_option($lock_key);
    }
    if (!add_option($lock_key, time(), '', 'no')) {
        wholesale_ticket_pay_redirect($token, 'Your payment is already being processed. Please wait a moment before trying again.');
    }

    // Re-read after taking the lock: the amount and status always come from the server.
    $ticket = wholesale_get_ticket($ticket_id);
    if ('sent' !== $ticket['status'] || wholesale_ticket_is_expired($ticket) || $ticket['amount'] <= 0) {
        delete_option($lock_key);
        wholesale_ticket_pay_redirect($token);
    }

    $amount = $ticket['amount'];
    $billing_data = wp_json_encode($billing);

    if (!$payment_disabled) {
        $result = processPayment(number_format($amount, 2, '.', ''), $card_number, $card_exp_month . $card_exp_year, $card_cvv, trim($billing['billing_address'] . ', ' . $billing['billing_city'] . ', ' . $billing['billing_country']), $billing['billing_zip']);
        if ('success' !== $result['status']) {
            delete_option($lock_key);
            wholesale_ticket_pay_redirect($token, $result['message']);
        }
        // The card is charged from here on. Mark the ticket paid first so it can never be charged twice.
        update_post_meta($ticket_id, '_ticket_status', 'paid');
    } else {
        update_post_meta($ticket_id, '_ticket_status', 'accepted');
    }
    update_post_meta($ticket_id, '_ticket_paid_at', current_time('mysql'));

    $logo_id = get_theme_mod('custom_logo');
    $product_details = array('Payment Ticket' => '#' . $ticket_id);
    if ($ticket['description']) {
        $product_details = array('Description' => $ticket['description']) + $product_details;
    }
    $product_data = wp_json_encode(array(array(
        'cart_id' => 'ticket-' . $ticket_id,
        'product_id' => 0,
        'product_title' => $ticket['title'],
        'product_thumbnail' => $logo_id ? (string) wp_get_attachment_image_url($logo_id, 'full') : '',
        'product_quantity' => 1,
        'product_subtotal' => $amount,
        'product_details' => $product_details,
        'design_id' => 0,
        'job_name' => $ticket['title'],
    )));
    $order_cost = wp_json_encode(array(
        'grand_total' => $amount,
        'sub_total' => $amount,
        'shipping_cost' => 0,
        'tax' => 0,
    ));
    $comment = $payment_disabled ? 'Payment ticket accepted - collect payment manually.' : 'Paid by card via payment ticket.';

    // wp_slash() keeps quotes inside the JSON intact when WordPress unslashes meta on save.
    $order = wholesale_insert_order(wp_slash($product_data), wp_slash($order_cost), wp_slash($billing_data), wp_slash($billing_data), $comment, '', array('_ticket_id' => $ticket_id));
    delete_option($lock_key);

    if (!$order) {
        error_log(sprintf('Wholesale: payment ticket #%d (%s) paid by %s but the order could not be saved.', $ticket_id, number_format($amount, 2, '.', ''), $billing['billing_email']));
        wp_mail(
            wholesale_contact_admin_recipients(),
            'URGENT: payment ticket paid but order was not saved',
            sprintf("Payment ticket #%d (%s) for %s was %s, but the order could not be saved.\n\nCustomer: %s %s (%s)\nBilling: %s\n\nOpen the ticket: %s",
                $ticket_id, $ticket['title'], wholesale_format_ticket_amount($amount), $payment_disabled ? 'accepted' : 'PAID BY CARD',
                $billing['billing_fname'], $billing['billing_lname'], $billing['billing_email'], $billing_data, admin_url('post.php?post=' . $ticket_id . '&action=edit'))
        );
        wp_die(esc_html__('Your payment was received, but we could not finish saving your order. Please do not pay again - our team has been notified and will contact you shortly.', 'litsign'), esc_html__('Order needs attention', 'litsign'), array('response' => 500));
    }

    update_post_meta($ticket_id, '_ticket_order_id', $order);
    wholesale_send_new_order_admin_email($order);

    wp_safe_redirect(home_url('/thank-you/'));
    exit;
}
add_action('admin_post_nopriv_wholesale_pay_ticket', 'wholesale_handle_ticket_payment');
add_action('admin_post_wholesale_pay_ticket', 'wholesale_handle_ticket_payment');

/**
 * Payment links are private: keep /pay/ out of search engines.
 */
function wholesale_ticket_noindex($robots)
{
    if (is_page('pay')) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'wholesale_ticket_noindex');

function wholesale_ticket_rank_math_noindex($robots)
{
    if (is_page('pay')) {
        $robots['index'] = 'noindex';
        $robots['follow'] = 'nofollow';
    }
    return $robots;
}
add_filter('rank_math/frontend/robots', 'wholesale_ticket_rank_math_noindex');
