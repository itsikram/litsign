<?php
/**
 * Payment tickets: the admin builds a priced request from catalog products
 * (with their options) and/or custom items, emails the customer a private
 * link, and the customer pays it on /pay/ (page-pay.php). A paid ticket
 * becomes a normal `order` post.
 *
 * @package litsign
 */

/* ---------------------------------------------------------------------------
 * Data
 * ------------------------------------------------------------------------ */

function wholesale_ticket_business()
{
    $logo_id = get_theme_mod('custom_logo');

    return array(
        'name' => 'Lit Sign Manufacturing',
        'site' => 'StorefrontSignOnline.com',
        'phone' => '866-436-2101',
        'email' => 'TR@StorefrontSignOnline.com',
        'address' => '707 S. Grady Way Suite 600, Renton, WA 98057',
        'logo' => $logo_id ? (string) wp_get_attachment_image_url($logo_id, 'full') : '',
    );
}

function wholesale_ticket_statuses()
{
    return array(
        'draft' => 'Draft',
        'sent' => 'Awaiting payment',
        'accepted' => 'Accepted - collect payment',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    );
}

function wholesale_ticket_status_label($ticket)
{
    if ('sent' === $ticket['status'] && wholesale_ticket_is_expired($ticket)) {
        return 'Expired';
    }
    $statuses = wholesale_ticket_statuses();
    return $statuses[$ticket['status']] ?? $ticket['status'];
}

function wholesale_ticket_status_key($ticket)
{
    return ('sent' === $ticket['status'] && wholesale_ticket_is_expired($ticket)) ? 'expired' : $ticket['status'];
}

function wholesale_ticket_number($ticket_id)
{
    return 'PT-' . str_pad((string) absint($ticket_id), 5, '0', STR_PAD_LEFT);
}

function wholesale_ticket_money($value)
{
    return max(0, round((float) preg_replace('/[^0-9.]/', '', (string) $value), 2));
}

function wholesale_format_ticket_amount($amount)
{
    return '$' . number_format((float) $amount, 2, '.', ',');
}

/**
 * Selectable options of a catalog product, read from its `product_attr` JSON
 * ([{"name":"face","options":[{"Red":"12.5/red"}, ...]}, ...]).
 *
 * @return array[] Each: name, label, options (option titles).
 */
function wholesale_ticket_product_attributes($product_id)
{
    $decoded = json_decode((string) get_post_meta($product_id, 'product_attr', true), true);
    if (!is_array($decoded)) {
        return array();
    }

    $attributes = array();
    foreach ($decoded as $attr) {
        if (!is_array($attr) || empty($attr['name']) || empty($attr['options']) || !is_array($attr['options'])) {
            continue;
        }

        $name = (string) $attr['name'];
        if (isset($attributes[$name])) {
            continue;
        }

        $options = array();
        foreach ($attr['options'] as $option) {
            $titles = is_array($option) ? array_keys($option) : array($option);
            foreach ($titles as $title) {
                $title = trim((string) $title);
                if ('' !== $title && !in_array($title, $options, true)) {
                    $options[] = $title;
                }
            }
        }
        if (empty($options)) {
            continue;
        }

        $label = ucwords(strtolower(str_replace(array('-', '_'), ' ', $name)));
        $label = preg_replace('/\bLed\b/', 'LED', $label);
        $attributes[$name] = array('name' => $name, 'label' => $label, 'options' => $options);
    }

    return array_values($attributes);
}

function wholesale_ticket_product_thumb($product_id, $size = 'thumbnail')
{
    $url = $product_id ? get_the_post_thumbnail_url($product_id, $size) : '';
    return $url ? (string) $url : '';
}

/**
 * Catalog for the ticket editor: every published product with its options.
 */
function wholesale_ticket_product_catalog()
{
    $products = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));

    $thumbnail_ids = array_filter(array_map(static function ($product) {
        return (int) get_post_thumbnail_id($product->ID);
    }, $products));
    if ($thumbnail_ids) {
        _prime_post_caches($thumbnail_ids, false, true);
    }

    $catalog = array();
    foreach ($products as $product) {
        $terms = get_the_terms($product->ID, 'product_category');
        $catalog[] = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'cat' => is_array($terms) && $terms ? $terms[0]->name : '',
            'thumb' => wholesale_ticket_product_thumb($product->ID),
            'price' => round((float) get_post_meta($product->ID, '_price_per_sqft', true), 2),
            'attrs' => wholesale_ticket_product_attributes($product->ID),
            'cl' => wholesale_ticket_is_cl_product($product->ID),
        );
    }

    return $catalog;
}

/**
 * Channel letter products are designed in the channel letter builder.
 */
function wholesale_ticket_is_cl_product($product_id)
{
    return $product_id && 'product' === get_post_type($product_id) && has_term('channel-letters', 'product_category', $product_id);
}

function wholesale_ticket_line_key($raw_key = '')
{
    $key = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw_key));
    return ('' !== $key && strlen($key) <= 24) ? $key : 'k' . strtolower(wp_generate_password(10, false, false));
}

/**
 * Fill in fields that older saved lines may not have.
 */
function wholesale_ticket_normalize_item($item, $index)
{
    $item = wp_parse_args(is_array($item) ? $item : array(), array(
        'key' => '',
        'product_id' => 0,
        'title' => '',
        'options' => array(),
        'width' => '',
        'height' => '',
        'qty' => 1,
        'unit_price' => 0,
        'notes' => '',
        'design' => null,
    ));
    if ('' === $item['key']) {
        $item['key'] = 'k' . $index;
    }
    if (!is_array($item['design']) || empty($item['design']['id'])) {
        $item['design'] = null;
    }

    return $item;
}

/**
 * Validate line items posted from the editor. Product options must be ones the
 * product actually offers; the product title always comes from the catalog.
 * Builder designs are never posted by the form: a line keeps the design it
 * already had (matched by line key) unless its product changed or the admin
 * removed it.
 */
function wholesale_ticket_sanitize_items($raw, $existing_items = array())
{
    $items = array();
    if (!is_array($raw)) {
        return $items;
    }

    $existing = array();
    foreach ($existing_items as $existing_item) {
        $existing[$existing_item['key']] = $existing_item;
    }

    foreach ($raw as $raw_key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $key = wholesale_ticket_line_key($raw_key);

        $product_id = isset($row['product_id']) ? absint($row['product_id']) : 0;
        if ($product_id && 'product' !== get_post_type($product_id)) {
            $product_id = 0;
        }

        $title = isset($row['title']) ? sanitize_text_field($row['title']) : '';
        $options = array();
        if ($product_id) {
            $title = (string) get_post_field('post_title', $product_id, 'raw');
            $chosen = isset($row['opt']) && is_array($row['opt']) ? $row['opt'] : array();
            foreach (wholesale_ticket_product_attributes($product_id) as $attr) {
                $value = isset($chosen[$attr['name']]) ? trim((string) $chosen[$attr['name']]) : '';
                if ('' !== $value && in_array($value, $attr['options'], true)) {
                    $options[] = array('name' => $attr['name'], 'label' => $attr['label'], 'value' => $value);
                }
            }
        }

        if ('' === trim($title)) {
            continue;
        }

        $dimension = static function ($value) {
            $value = round((float) preg_replace('/[^0-9.]/', '', (string) $value), 2);
            return $value > 0 ? $value : '';
        };

        $design = null;
        if (
            isset($existing[$key]['design']) && $existing[$key]['design']
            && (int) $existing[$key]['product_id'] === $product_id
            && empty($row['remove_design'])
        ) {
            $design = $existing[$key]['design'];
        }

        $items[] = array(
            'key' => $key,
            'product_id' => $product_id,
            'title' => $title,
            'options' => $options,
            'width' => isset($row['width']) ? $dimension($row['width']) : '',
            'height' => isset($row['height']) ? $dimension($row['height']) : '',
            'qty' => isset($row['qty']) ? max(1, min(100000, absint($row['qty']))) : 1,
            'unit_price' => isset($row['unit_price']) ? wholesale_ticket_money($row['unit_price']) : 0,
            'notes' => isset($row['notes']) ? sanitize_textarea_field($row['notes']) : '',
            'design' => $design,
        );

        if (count($items) >= 50) {
            break;
        }
    }

    return $items;
}

function wholesale_ticket_line_total($item)
{
    return round($item['qty'] * $item['unit_price'], 2);
}

function wholesale_ticket_calculate_totals($items, $discount, $shipping, $tax_rate)
{
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += wholesale_ticket_line_total($item);
    }
    $subtotal = round($subtotal, 2);
    $discount = min(round((float) $discount, 2), $subtotal);
    $taxable = round($subtotal - $discount, 2);
    $tax = round($taxable * (float) $tax_rate / 100, 2);
    $shipping = round((float) $shipping, 2);

    return array(
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'tax_rate' => (float) $tax_rate,
        'tax' => $tax,
        'grand_total' => round($taxable + $shipping + $tax, 2),
    );
}

/**
 * @return array Ticket fields read from post meta.
 */
function wholesale_get_ticket($ticket_id)
{
    $meta = static function ($key) use ($ticket_id) {
        return get_post_meta($ticket_id, $key, true);
    };

    $status = (string) $meta('_ticket_status');
    $items = $meta('_ticket_items');
    $items = is_array($items) ? $items : array();

    // Tickets created before line items existed carry a single amount.
    if (!$items && (float) $meta('_ticket_amount') > 0) {
        $items[] = array(
            'title' => (string) get_post_field('post_title', $ticket_id, 'raw'),
            'unit_price' => round((float) $meta('_ticket_amount'), 2),
        );
    }
    $items = array_map('wholesale_ticket_normalize_item', array_values($items), array_keys(array_values($items)));

    $totals = wholesale_ticket_calculate_totals($items, (float) $meta('_ticket_discount'), (float) $meta('_ticket_shipping'), (float) $meta('_ticket_tax_rate'));
    $log = $meta('_ticket_log');

    return array(
        'id' => (int) $ticket_id,
        'number' => wholesale_ticket_number($ticket_id),
        'title' => (string) get_post_field('post_title', $ticket_id, 'raw'),
        'created' => (string) get_post_field('post_date', $ticket_id, 'raw'),
        'customer_name' => (string) $meta('_ticket_customer_name'),
        'customer_company' => (string) $meta('_ticket_customer_company'),
        'customer_email' => (string) $meta('_ticket_customer_email'),
        'customer_phone' => (string) $meta('_ticket_customer_phone'),
        'message' => (string) $meta('_ticket_description'),
        'internal_note' => (string) $meta('_ticket_internal_note'),
        'proof_id' => (int) $meta('_ticket_proof_id'),
        'items' => $items,
        'totals' => $totals,
        'amount' => $totals['grand_total'],
        'due' => (string) $meta('_ticket_due'),
        'status' => $status ? $status : 'draft',
        'token' => (string) $meta('_ticket_token'),
        'sent_at' => (string) $meta('_ticket_sent_at'),
        'viewed_at' => (string) $meta('_ticket_viewed_at'),
        'paid_at' => (string) $meta('_ticket_paid_at'),
        'order_id' => (int) $meta('_ticket_order_id'),
        'log' => is_array($log) ? $log : array(),
    );
}

function wholesale_ticket_is_locked($ticket)
{
    return in_array($ticket['status'], array('paid', 'accepted', 'cancelled'), true);
}

function wholesale_ticket_is_expired($ticket)
{
    return '' !== $ticket['due'] && current_time('Y-m-d') > $ticket['due'];
}

function wholesale_ticket_log($ticket_id, $event, $note = '')
{
    $log = get_post_meta($ticket_id, '_ticket_log', true);
    $log = is_array($log) ? $log : array();
    $user = wp_get_current_user();

    $log[] = array(
        'time' => current_time('mysql'),
        'event' => $event,
        'by' => $user && $user->exists() ? $user->display_name : 'Customer',
        'note' => $note,
    );
    update_post_meta($ticket_id, '_ticket_log', array_slice($log, -100));
}

function wholesale_ticket_log_labels()
{
    return array(
        'created' => 'Ticket created',
        'sent' => 'Sent to customer',
        'resent' => 'Resent to customer',
        'email_failed' => 'Email could not be sent',
        'viewed' => 'Opened by customer',
        'payment_failed' => 'Payment attempt failed',
        'paid' => 'Paid by card',
        'accepted' => 'Accepted for manual payment',
        'cancelled' => 'Ticket cancelled',
        'duplicated' => 'Created as a copy',
        'design_saved' => 'Channel letter design attached',
    );
}

/**
 * The /pay/ page renders page-pay.php. Create it the first time it is needed
 * so the admin never has to set it up by hand.
 */
function wholesale_ticket_pay_page_url()
{
    if (!get_page_by_path('pay')) {
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

/**
 * One-line summary of a line item's options and size, for emails and orders.
 */
function wholesale_ticket_item_specs($item)
{
    $specs = array();
    if ($item['width'] || $item['height']) {
        $specs[] = 'Size: ' . ($item['width'] ?: '?') . '" W x ' . ($item['height'] ?: '?') . '" H';
    }
    foreach ($item['options'] as $option) {
        $specs[] = $option['label'] . ': ' . $option['value'];
    }
    if (!empty($item['design']['summary'])) {
        $specs = array_merge($specs, $item['design']['summary']);
    }

    return $specs;
}

/* ---------------------------------------------------------------------------
 * Channel letter builder
 * ------------------------------------------------------------------------ */

function wholesale_ticket_builder_url($ticket_id, $product_id)
{
    return add_query_arg(array('product_id' => absint($product_id), 'wpt_ticket' => absint($ticket_id)), home_url('/channel-letter-builder/'));
}

function wholesale_ticket_find_line($ticket, $key)
{
    foreach ($ticket['items'] as $index => $item) {
        if ($item['key'] === $key) {
            return $index;
        }
    }
    return null;
}

/**
 * Called by the builder page (cl_builderr.php). When an admin opened the
 * builder from a ticket line, returns what the builder needs to design for
 * that line; otherwise null and the builder works as the normal storefront tool.
 */
function wholesale_ticket_builder_context($product_id)
{
    if (empty($_GET['wpt_ticket']) || !is_user_logged_in()) {
        return null;
    }

    $ticket_id = absint($_GET['wpt_ticket']);
    $pending_key = 'wholesale_ticket_builder_' . get_current_user_id();
    $pending = get_transient($pending_key);
    if (!is_array($pending) || (int) $pending['ticket'] !== $ticket_id || !current_user_can('edit_post', $ticket_id) || !wholesale_ticket_is_cl_product($product_id)) {
        return null;
    }

    $ticket = wholesale_get_ticket($ticket_id);
    $index = wholesale_ticket_find_line($ticket, $pending['line']);
    if (wholesale_ticket_is_locked($ticket) || null === $index) {
        return null;
    }

    // The builder lets the admin switch to another channel letter product; the save follows it.
    $pending['product'] = (int) $product_id;
    set_transient($pending_key, $pending, 2 * HOUR_IN_SECONDS);

    $line = $ticket['items'][$index];
    $design_data = '';
    if ($line['design'] && (int) $line['product_id'] === (int) $product_id) {
        $design_data = (string) get_post_meta($line['design']['id'], '_cl_data', true);
    }

    return array(
        'ticket' => $ticket,
        'line_number' => $index + 1,
        'design_data' => $design_data,
        'save_url' => admin_url('admin-post.php'),
        'back_url' => (string) get_edit_post_link($ticket_id, 'raw'),
        'switch_query' => array('wpt_ticket' => $ticket_id),
    );
}

/**
 * The builder's Save button uploads the design image and then redirects to its
 * "product permalink" with ?save_design=1&design_data=…&design_id=…. In ticket
 * mode that permalink is admin-post.php with no action, which WordPress
 * routes to the `admin_post` hook for logged-in users.
 */
function wholesale_ticket_save_builder_design()
{
    if (!isset($_GET['save_design'], $_GET['design_id'], $_GET['design_data'])) {
        return;
    }

    $pending_key = 'wholesale_ticket_builder_' . get_current_user_id();
    $pending = get_transient($pending_key);
    if (!is_array($pending) || empty($pending['ticket'])) {
        wp_die(esc_html__('This design session has expired. Open the channel letter builder again from the payment ticket.', 'litsign'), esc_html__('Design not saved', 'litsign'), array('response' => 400, 'back_link' => true));
    }

    $ticket_id = (int) $pending['ticket'];
    $product_id = (int) $pending['product'];
    $design_id = absint($_GET['design_id']);
    $uploads = isset($_SESSION['wholesale_design_uploads']) && is_array($_SESSION['wholesale_design_uploads']) ? array_map('absint', $_SESSION['wholesale_design_uploads']) : array();
    $design = json_decode(wp_unslash((string) $_GET['design_data']), true);
    $ticket = current_user_can('edit_post', $ticket_id) ? wholesale_get_ticket($ticket_id) : null;
    $index = $ticket ? wholesale_ticket_find_line($ticket, $pending['line']) : null;

    if (!$ticket || wholesale_ticket_is_locked($ticket) || null === $index || !in_array($design_id, $uploads, true) || !is_array($design) || !wholesale_ticket_is_cl_product($product_id)) {
        wp_die(esc_html__('This design could not be attached to the ticket. Please try again from the payment ticket.', 'litsign'), esc_html__('Design not saved', 'litsign'), array('response' => 400, 'back_link' => true));
    }

    // Same storage the storefront uses, so the order screen can show the letter details table.
    update_post_meta($design_id, '_cl_data', wp_slash(wp_json_encode($design, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    update_post_meta($design_id, '_wholesale_ticket_id', $ticket_id);

    $total = round(wholesale_cl_design_total($design), 2);
    $item = $ticket['items'][$index];
    if ((int) $item['product_id'] !== $product_id) {
        $item['product_id'] = $product_id;
        $item['title'] = (string) get_post_field('post_title', $product_id, 'raw');
        $item['options'] = array();
    }
    if (!empty($design['contentDimenstion']['width'])) {
        $item['width'] = round((float) $design['contentDimenstion']['width'], 1);
    }
    if (!empty($design['contentDimenstion']['height'])) {
        $item['height'] = round((float) $design['contentDimenstion']['height'], 1);
    }
    if ($item['unit_price'] <= 0) {
        $item['unit_price'] = $total;
    }
    $item['design'] = array(
        'id' => $design_id,
        'url' => (string) wp_get_attachment_url($design_id),
        'cost' => $total,
        'summary' => wholesale_cl_design_summary($design),
    );
    $ticket['items'][$index] = $item;

    wholesale_ticket_store_items($ticket_id, $ticket['items']);
    wholesale_ticket_log($ticket_id, 'design_saved', 'Line ' . ($index + 1) . ' · builder price ' . wholesale_format_ticket_amount($total));
    delete_transient($pending_key);
    wholesale_ticket_set_notice('design_saved');

    wp_safe_redirect(admin_url('post.php?post=' . $ticket_id . '&action=edit') . '#wholesale_ticket_items');
    exit;
}
add_action('admin_post', 'wholesale_ticket_save_builder_design');

/**
 * Save line items and keep the stored total in step with them.
 */
function wholesale_ticket_store_items($ticket_id, $items)
{
    update_post_meta($ticket_id, '_ticket_items', array_values($items));
    $ticket = wholesale_get_ticket($ticket_id);
    update_post_meta($ticket_id, '_ticket_amount', $ticket['amount']);
}

/* ---------------------------------------------------------------------------
 * Admin: assets
 * ------------------------------------------------------------------------ */

function wholesale_ticket_admin_assets($hook)
{
    $screen = get_current_screen();
    if (!$screen || 'payment_ticket' !== $screen->post_type) {
        return;
    }

    $theme_uri = get_template_directory_uri();
    $version = static function ($path) {
        $file = get_template_directory() . $path;
        return file_exists($file) ? (string) filemtime($file) : _S_VERSION;
    };

    wp_enqueue_style('wholesale-ticket-admin', $theme_uri . '/css/admin-payment-ticket.css', array(), $version('/css/admin-payment-ticket.css'));

    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    global $post;
    $ticket = wholesale_get_ticket($post->ID);

    wp_enqueue_media();
    wp_enqueue_script('wholesale-ticket-admin', $theme_uri . '/js/admin-payment-ticket.js', array(), $version('/js/admin-payment-ticket.js'), true);
    wp_add_inline_script('wholesale-ticket-admin', 'window.wptData = ' . wp_json_encode(array(
        'catalog' => wholesale_ticket_product_catalog(),
        'items' => array_map(static function ($item) {
            $item['thumb'] = wholesale_ticket_product_thumb($item['product_id']);
            return $item;
        }, $ticket['items']),
        'locked' => wholesale_ticket_is_locked($ticket),
        'siteTaxRate' => (float) wholesale_get_setting('tax_rate'),
    )) . ';', 'before');
}
add_action('admin_enqueue_scripts', 'wholesale_ticket_admin_assets');

/* ---------------------------------------------------------------------------
 * Admin: edit screen
 * ------------------------------------------------------------------------ */

function wholesale_ticket_title_placeholder($placeholder, $post)
{
    return 'payment_ticket' === $post->post_type ? 'Project name, e.g. Pho Saigon storefront channel letters' : $placeholder;
}
add_filter('enter_title_here', 'wholesale_ticket_title_placeholder', 10, 2);

function wholesale_ticket_add_meta_boxes()
{
    remove_meta_box('submitdiv', 'payment_ticket', 'side');
    add_meta_box('wholesale_ticket_customer', 'Customer', 'wholesale_render_ticket_customer_box', 'payment_ticket', 'normal', 'high');
    add_meta_box('wholesale_ticket_items', 'Products & pricing', 'wholesale_render_ticket_items_box', 'payment_ticket', 'normal', 'high');
    add_meta_box('wholesale_ticket_message', 'Message, proof & terms', 'wholesale_render_ticket_message_box', 'payment_ticket', 'normal', 'default');
    add_meta_box('wholesale_ticket_summary', 'Payment ticket', 'wholesale_render_ticket_summary_box', 'payment_ticket', 'side', 'high');
    add_meta_box('wholesale_ticket_activity', 'Activity', 'wholesale_render_ticket_activity_box', 'payment_ticket', 'side', 'default');
}
add_action('add_meta_boxes_payment_ticket', 'wholesale_ticket_add_meta_boxes');

function wholesale_ticket_locked_notice($ticket)
{
    if (!wholesale_ticket_is_locked($ticket)) {
        return;
    }
    $reason = 'cancelled' === $ticket['status'] ? 'was cancelled' : 'has been ' . ('paid' === $ticket['status'] ? 'paid' : 'accepted');
    echo '<p class="wpt-locked"><span class="dashicons dashicons-lock" aria-hidden="true"></span> This ticket ' . esc_html($reason) . ', so these details can no longer be changed. Use <b>Duplicate</b> to start a new ticket from it.</p>';
}

function wholesale_render_ticket_customer_box($post)
{
    $ticket = wholesale_get_ticket($post->ID);
    $locked = wholesale_ticket_is_locked($ticket);
    wp_nonce_field('wholesale_save_ticket', 'wholesale_ticket_nonce');
    wholesale_ticket_locked_notice($ticket);
    ?>
    <div class="wpt-grid wpt-grid--2">
        <p class="wpt-field">
            <label for="ticket_customer_name">Full name <span class="wpt-req" aria-hidden="true">*</span></label>
            <input type="text" id="ticket_customer_name" name="ticket_customer_name" value="<?php echo esc_attr($ticket['customer_name']); ?>" autocomplete="off" <?php disabled($locked); ?>>
        </p>
        <p class="wpt-field">
            <label for="ticket_customer_company">Company</label>
            <input type="text" id="ticket_customer_company" name="ticket_customer_company" value="<?php echo esc_attr($ticket['customer_company']); ?>" autocomplete="off" <?php disabled($locked); ?>>
        </p>
        <p class="wpt-field">
            <label for="ticket_customer_email">Email <span class="wpt-req" aria-hidden="true">*</span></label>
            <input type="email" id="ticket_customer_email" name="ticket_customer_email" value="<?php echo esc_attr($ticket['customer_email']); ?>" autocomplete="off" <?php disabled($locked); ?>>
            <span class="wpt-help">The payment link is emailed here.</span>
        </p>
        <p class="wpt-field">
            <label for="ticket_customer_phone">Phone</label>
            <input type="tel" id="ticket_customer_phone" name="ticket_customer_phone" value="<?php echo esc_attr($ticket['customer_phone']); ?>" autocomplete="off" <?php disabled($locked); ?>>
        </p>
    </div>
    <?php
}

function wholesale_render_ticket_items_box($post)
{
    $ticket = wholesale_get_ticket($post->ID);
    $totals = $ticket['totals'];
    $locked = wholesale_ticket_is_locked($ticket);
    $site_rate = (float) wholesale_get_setting('tax_rate');
    ?>
    <div id="wpt-items" class="wpt-items" aria-live="polite"></div>
    <input type="hidden" name="wpt_builder_line" id="wpt-builder-line" value="">
    <noscript><p class="wpt-locked">JavaScript is required to edit line items.</p></noscript>

    <?php if (!$locked) : ?>
        <div class="wpt-add">
            <button type="button" class="button button-secondary wpt-add__btn" data-wpt-add="product"><span class="dashicons dashicons-cart" aria-hidden="true"></span> Add catalog product</button>
            <button type="button" class="button button-secondary wpt-add__btn" data-wpt-add="custom"><span class="dashicons dashicons-edit" aria-hidden="true"></span> Add custom item</button>
            <span class="wpt-help">Custom items are for things like installation, permits, design fees or deposits.</span>
        </div>
    <?php endif; ?>

    <div class="wpt-totals">
        <div class="wpt-totals__row">
            <span>Subtotal</span>
            <span class="wpt-num" data-wpt-subtotal><?php echo esc_html(wholesale_format_ticket_amount($totals['subtotal'])); ?></span>
        </div>
        <div class="wpt-totals__row">
            <label for="ticket_discount">Discount</label>
            <span class="wpt-money"><span aria-hidden="true">−$</span><input type="text" inputmode="decimal" id="ticket_discount" name="ticket_discount" value="<?php echo esc_attr($totals['discount'] > 0 ? number_format($totals['discount'], 2, '.', '') : ''); ?>" placeholder="0.00" <?php disabled($locked); ?>></span>
        </div>
        <div class="wpt-totals__row">
            <label for="ticket_shipping">Shipping / delivery</label>
            <span class="wpt-money"><span aria-hidden="true">$</span><input type="text" inputmode="decimal" id="ticket_shipping" name="ticket_shipping" value="<?php echo esc_attr($totals['shipping'] > 0 ? number_format($totals['shipping'], 2, '.', '') : ''); ?>" placeholder="0.00" <?php disabled($locked); ?>></span>
        </div>
        <div class="wpt-totals__row">
            <span class="wpt-totals__tax">
                <label for="ticket_tax_rate">Sales tax</label>
                <?php if (!$locked && $site_rate > 0) : ?>
                    <button type="button" class="button-link" data-wpt-site-tax>Use <?php echo esc_html(rtrim(rtrim(number_format($site_rate, 2), '0'), '.')); ?>%</button>
                <?php endif; ?>
            </span>
            <span class="wpt-tax-inputs">
                <span class="wpt-money wpt-money--pct"><input type="text" inputmode="decimal" id="ticket_tax_rate" name="ticket_tax_rate" value="<?php echo esc_attr($totals['tax_rate'] > 0 ? rtrim(rtrim(number_format($totals['tax_rate'], 3, '.', ''), '0'), '.') : ''); ?>" placeholder="0" <?php disabled($locked); ?>><span aria-hidden="true">%</span></span>
                <span class="wpt-num" data-wpt-tax><?php echo esc_html(wholesale_format_ticket_amount($totals['tax'])); ?></span>
            </span>
        </div>
        <div class="wpt-totals__row wpt-totals__grand">
            <span>Total due</span>
            <span class="wpt-num" data-wpt-grand><?php echo esc_html(wholesale_format_ticket_amount($totals['grand_total'])); ?></span>
        </div>
    </div>
    <?php
}

function wholesale_render_ticket_message_box($post)
{
    $ticket = wholesale_get_ticket($post->ID);
    $locked = wholesale_ticket_is_locked($ticket);
    $proof_url = $ticket['proof_id'] ? wp_get_attachment_url($ticket['proof_id']) : '';
    $proof_name = $proof_url ? wp_basename(get_attached_file($ticket['proof_id'])) : '';
    $proof_thumb = $ticket['proof_id'] && wp_attachment_is_image($ticket['proof_id']) ? wp_get_attachment_image_url($ticket['proof_id'], 'thumbnail') : '';
    ?>
    <div class="wpt-grid wpt-grid--msg">
        <p class="wpt-field wpt-field--wide">
            <label for="ticket_description">Message to the customer</label>
            <textarea id="ticket_description" name="ticket_description" rows="4" placeholder="e.g. Thanks for choosing us! This covers fabrication and installation. Production starts once payment is received." <?php disabled($locked); ?>><?php echo esc_textarea($ticket['message']); ?></textarea>
            <span class="wpt-help">Shown on the payment page and in the email.</span>
        </p>

        <div class="wpt-field">
            <span class="wpt-label">Design proof</span>
            <div class="wpt-proof" data-wpt-proof>
                <input type="hidden" name="ticket_proof_id" value="<?php echo esc_attr($ticket['proof_id'] ?: ''); ?>" data-wpt-proof-id>
                <span class="wpt-proof__thumb" data-wpt-proof-thumb><?php if ($proof_thumb) : ?><img src="<?php echo esc_url($proof_thumb); ?>" alt=""><?php else : ?><span class="dashicons dashicons-media-document" aria-hidden="true"></span><?php endif; ?></span>
                <span class="wpt-proof__body">
                    <span class="wpt-proof__name" data-wpt-proof-name><?php echo $proof_name ? esc_html($proof_name) : 'No file attached'; ?></span>
                    <?php if (!$locked) : ?>
                        <span class="wpt-proof__actions">
                            <button type="button" class="button button-small" data-wpt-proof-choose><?php echo $proof_name ? 'Replace' : 'Attach proof'; ?></button>
                            <button type="button" class="button-link button-link-delete" data-wpt-proof-remove <?php echo $proof_name ? '' : 'hidden'; ?>>Remove</button>
                        </span>
                    <?php endif; ?>
                </span>
            </div>
            <span class="wpt-help">Image or PDF. The customer can open it before paying.</span>
        </div>

        <p class="wpt-field">
            <label for="ticket_due">Pay by</label>
            <input type="date" id="ticket_due" name="ticket_due" value="<?php echo esc_attr($ticket['due']); ?>" <?php disabled($locked); ?>>
            <span class="wpt-help">Optional. After this date the link stops accepting payment.</span>
        </p>

        <p class="wpt-field wpt-field--wide">
            <label for="ticket_internal_note"><span class="dashicons dashicons-hidden" aria-hidden="true"></span> Internal note</label>
            <textarea id="ticket_internal_note" name="ticket_internal_note" rows="2" placeholder="Only visible to your team, e.g. quoted by phone on 9/24, rush job."><?php echo esc_textarea($ticket['internal_note']); ?></textarea>
        </p>
    </div>
    <?php
}

function wholesale_render_ticket_summary_box($post)
{
    $ticket = wholesale_get_ticket($post->ID);
    $status_key = wholesale_ticket_status_key($ticket);
    $can_send = in_array($ticket['status'], array('draft', 'sent'), true);
    $date = static function ($mysql) {
        return $mysql ? date_i18n('M j, Y g:i a', strtotime($mysql)) : '';
    };
    ?>
    <div class="wpt-summary">
        <div class="wpt-summary__head">
            <span class="wpt-summary__number"><?php echo esc_html($ticket['number']); ?></span>
            <span class="wpt-pill wpt-pill--<?php echo esc_attr($status_key); ?>"><?php echo esc_html(wholesale_ticket_status_label($ticket)); ?></span>
        </div>

        <div class="wpt-summary__total">
            <span>Total due</span>
            <strong class="wpt-num" data-wpt-grand><?php echo esc_html(wholesale_format_ticket_amount($ticket['amount'])); ?></strong>
        </div>

        <dl class="wpt-summary__meta">
            <?php if ($ticket['sent_at']) : ?><div><dt>Sent</dt><dd><?php echo esc_html($date($ticket['sent_at'])); ?></dd></div><?php endif; ?>
            <?php if ($ticket['viewed_at']) : ?><div><dt>Viewed</dt><dd><?php echo esc_html($date($ticket['viewed_at'])); ?></dd></div><?php endif; ?>
            <?php if ($ticket['paid_at']) : ?><div><dt><?php echo 'paid' === $ticket['status'] ? 'Paid' : 'Accepted'; ?></dt><dd><?php echo esc_html($date($ticket['paid_at'])); ?></dd></div><?php endif; ?>
            <?php if ($ticket['due']) : ?><div><dt>Pay by</dt><dd><?php echo esc_html(date_i18n('M j, Y', strtotime($ticket['due']))); ?></dd></div><?php endif; ?>
            <?php if ($ticket['order_id']) : ?><div><dt>Order</dt><dd><a href="<?php echo esc_url(get_edit_post_link($ticket['order_id'])); ?>"><?php echo esc_html(get_post_meta($ticket['order_id'], 'order_id', true) ?: '#' . $ticket['order_id']); ?></a></dd></div><?php endif; ?>
        </dl>

        <?php if ($can_send) : ?>
            <div class="wpt-summary__actions">
                <button type="submit" name="wpt_action" value="send" class="button button-primary button-large wpt-summary__primary">
                    <span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
                    <?php echo 'sent' === $ticket['status'] ? 'Save &amp; resend' : 'Save &amp; send to customer'; ?>
                </button>
                <button type="submit" name="wpt_action" value="save" class="button button-large">Save <?php echo 'draft' === $ticket['status'] ? 'draft' : 'changes'; ?></button>
            </div>
        <?php else : ?>
            <div class="wpt-summary__actions">
                <button type="submit" name="wpt_action" value="save" class="button button-large">Save internal note</button>
            </div>
        <?php endif; ?>

        <?php if ($ticket['token'] && 'cancelled' !== $ticket['status']) : ?>
            <div class="wpt-summary__link">
                <label for="wpt-link">Customer payment link</label>
                <div class="wpt-copy">
                    <input type="text" readonly id="wpt-link" value="<?php echo esc_attr(wholesale_ticket_payment_url($ticket)); ?>">
                    <button type="button" class="button" data-wpt-copy="#wpt-link">Copy</button>
                </div>
                <?php if ('draft' === $ticket['status']) : ?><span class="wpt-help">The link works for customers after you send the ticket.</span><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="wpt-summary__links">
            <?php if ($ticket['token']) : ?>
                <a href="<?php echo esc_url(wholesale_ticket_payment_url($ticket)); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-visibility" aria-hidden="true"></span> Preview</a>
            <?php endif; ?>
            <?php if ('auto-draft' !== $post->post_status) : ?>
                <a href="<?php echo esc_url(wholesale_ticket_duplicate_url($post->ID)); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span> Duplicate</a>
            <?php endif; ?>
            <?php if ('sent' === $ticket['status']) : ?>
                <button type="submit" name="wpt_action" value="cancel" class="button-link button-link-delete" data-wpt-confirm="Cancel this ticket? The payment link will stop working."><span class="dashicons dashicons-dismiss" aria-hidden="true"></span> Cancel ticket</button>
            <?php elseif (in_array($ticket['status'], array('draft', 'cancelled'), true) && 'auto-draft' !== $post->post_status && current_user_can('delete_post', $post->ID)) : ?>
                <a class="wpt-danger" href="<?php echo esc_url(get_delete_post_link($post->ID)); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span> Move to trash</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function wholesale_render_ticket_activity_box($post)
{
    $ticket = wholesale_get_ticket($post->ID);
    $labels = wholesale_ticket_log_labels();

    if (empty($ticket['log'])) {
        echo '<p class="wpt-help">Activity such as sending, customer views and payments will appear here.</p>';
        return;
    }

    echo '<ol class="wpt-activity">';
    foreach (array_reverse($ticket['log']) as $entry) {
        printf(
            '<li class="wpt-activity__item wpt-activity__item--%1$s"><span class="wpt-activity__event">%2$s</span>%3$s<span class="wpt-activity__time">%4$s · %5$s</span></li>',
            esc_attr($entry['event']),
            esc_html($labels[$entry['event']] ?? $entry['event']),
            $entry['note'] ? '<span class="wpt-activity__note">' . esc_html($entry['note']) . '</span>' : '',
            esc_html(date_i18n('M j, g:i a', strtotime($entry['time']))),
            esc_html($entry['by'])
        );
    }
    echo '</ol>';
}

/* ---------------------------------------------------------------------------
 * Admin: saving and sending
 * ------------------------------------------------------------------------ */

/**
 * The default Publish box is replaced by our own buttons: publish on send,
 * and turn a brand-new auto-draft into a draft on its first save.
 */
function wholesale_ticket_filter_post_data($data, $postarr)
{
    if ('payment_ticket' !== $data['post_type'] || empty($_POST['wholesale_ticket_nonce'])) {
        return $data;
    }

    $action = isset($_POST['wpt_action']) ? sanitize_key($_POST['wpt_action']) : 'save';
    if ('send' === $action && current_user_can('publish_posts')) {
        $data['post_status'] = 'publish';
    } elseif (in_array($data['post_status'], array('auto-draft', ''), true)) {
        $data['post_status'] = 'draft';
    }
    if ('' === trim($data['post_title'])) {
        $data['post_title'] = 'Payment request';
    }

    return $data;
}
add_filter('wp_insert_post_data', 'wholesale_ticket_filter_post_data', 10, 2);

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
    $action = isset($_POST['wpt_action']) ? sanitize_key($_POST['wpt_action']) : 'save';

    update_post_meta($post_id, '_ticket_internal_note', isset($_POST['ticket_internal_note']) ? sanitize_textarea_field(wp_unslash($_POST['ticket_internal_note'])) : '');

    if (wholesale_ticket_is_locked($ticket)) {
        wholesale_ticket_set_notice('note_saved');
        return;
    }

    $text = static function ($key) {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
    };

    $due = $text('ticket_due');
    if ($due && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
        $due = '';
    }
    $proof_id = isset($_POST['ticket_proof_id']) ? absint($_POST['ticket_proof_id']) : 0;
    if ($proof_id && 'attachment' !== get_post_type($proof_id)) {
        $proof_id = 0;
    }

    $items = wholesale_ticket_sanitize_items(isset($_POST['wpt_items']) ? wp_unslash($_POST['wpt_items']) : array(), $ticket['items']);
    $discount = wholesale_ticket_money($text('ticket_discount'));
    $shipping = wholesale_ticket_money($text('ticket_shipping'));
    $tax_rate = min(100, wholesale_ticket_money($text('ticket_tax_rate')));
    $totals = wholesale_ticket_calculate_totals($items, $discount, $shipping, $tax_rate);

    update_post_meta($post_id, '_ticket_customer_name', $text('ticket_customer_name'));
    update_post_meta($post_id, '_ticket_customer_company', $text('ticket_customer_company'));
    update_post_meta($post_id, '_ticket_customer_email', isset($_POST['ticket_customer_email']) ? sanitize_email(wp_unslash($_POST['ticket_customer_email'])) : '');
    update_post_meta($post_id, '_ticket_customer_phone', $text('ticket_customer_phone'));
    update_post_meta($post_id, '_ticket_description', isset($_POST['ticket_description']) ? sanitize_textarea_field(wp_unslash($_POST['ticket_description'])) : '');
    update_post_meta($post_id, '_ticket_items', $items);
    update_post_meta($post_id, '_ticket_discount', $totals['discount']);
    update_post_meta($post_id, '_ticket_shipping', $totals['shipping']);
    update_post_meta($post_id, '_ticket_tax_rate', $totals['tax_rate']);
    update_post_meta($post_id, '_ticket_amount', $totals['grand_total']);
    update_post_meta($post_id, '_ticket_due', $due);
    update_post_meta($post_id, '_ticket_proof_id', $proof_id);

    if (!get_post_meta($post_id, '_ticket_token', true)) {
        update_post_meta($post_id, '_ticket_token', wp_generate_password(32, false, false));
    }
    if (!get_post_meta($post_id, '_ticket_status', true)) {
        update_post_meta($post_id, '_ticket_status', 'draft');
        if (!get_post_meta($post_id, '_ticket_log', true)) {
            wholesale_ticket_log($post_id, 'created');
        }
    }

    if ('cancel' === $action && 'sent' === $ticket['status']) {
        update_post_meta($post_id, '_ticket_status', 'cancelled');
        wholesale_ticket_log($post_id, 'cancelled');
        wholesale_ticket_set_notice('cancelled');
        return;
    }

    if ('builder' === $action) {
        wholesale_ticket_open_builder($post_id, $items);
        return;
    }

    wholesale_ticket_set_notice('send' === $action ? wholesale_send_ticket($post_id) : 'saved');
}
add_action('save_post_payment_ticket', 'wholesale_save_ticket');

/**
 * "Open channel letter builder" on a line: the ticket has just been saved, so
 * remember which line is being designed and send the admin to the builder.
 */
function wholesale_ticket_open_builder($ticket_id, $items)
{
    $key = isset($_POST['wpt_builder_line']) ? wholesale_ticket_line_key(wp_unslash($_POST['wpt_builder_line'])) : '';
    $line = null;
    foreach ($items as $item) {
        if ($item['key'] === $key) {
            $line = $item;
        }
    }

    if (!$line || !wholesale_ticket_is_cl_product($line['product_id'])) {
        wholesale_ticket_set_notice('builder_invalid');
        return;
    }

    set_transient('wholesale_ticket_builder_' . get_current_user_id(), array(
        'ticket' => (int) $ticket_id,
        'line' => $key,
        'product' => (int) $line['product_id'],
    ), 2 * HOUR_IN_SECONDS);

    $builder_url = wholesale_ticket_builder_url($ticket_id, $line['product_id']);
    add_filter('redirect_post_location', static function () use ($builder_url) {
        return $builder_url;
    });
}

/**
 * Email the customer their payment link.
 *
 * @return string Notice code for the admin screen.
 */
function wholesale_send_ticket($ticket_id)
{
    $ticket = wholesale_get_ticket($ticket_id);

    if ('' === $ticket['customer_name'] || !is_email($ticket['customer_email'])) {
        return 'missing_customer';
    }
    if (empty($ticket['items'])) {
        return 'no_items';
    }
    if ($ticket['amount'] <= 0) {
        return 'zero_total';
    }
    if (!in_array($ticket['status'], array('draft', 'sent'), true)) {
        return 'locked';
    }

    if ('' === $ticket['token']) {
        $ticket['token'] = wp_generate_password(32, false, false);
        update_post_meta($ticket_id, '_ticket_token', $ticket['token']);
    }
    $was_sent = 'sent' === $ticket['status'];
    // The link goes live now, even if the email below fails, so the admin can share it by hand.
    update_post_meta($ticket_id, '_ticket_status', 'sent');
    $ticket['status'] = 'sent';

    $business = wholesale_ticket_business();
    $subject = sprintf('Payment request %s from %s: %s', $ticket['number'], $business['name'], wholesale_format_ticket_amount($ticket['amount']));
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . get_option('admin_email'),
    );
    foreach (wholesale_contact_admin_recipients() as $admin_email) {
        $headers[] = 'Bcc: ' . $admin_email;
    }

    if (!wp_mail($ticket['customer_email'], $subject, wholesale_ticket_email_html($ticket), $headers)) {
        error_log('Wholesale: payment ticket email failed for ticket #' . $ticket_id);
        wholesale_ticket_log($ticket_id, 'email_failed', $ticket['customer_email']);
        return 'send_failed';
    }

    update_post_meta($ticket_id, '_ticket_sent_at', current_time('mysql'));
    wholesale_ticket_log($ticket_id, $was_sent ? 'resent' : 'sent', $ticket['customer_email']);

    return 'sent';
}

function wholesale_ticket_email_html($ticket)
{
    $business = wholesale_ticket_business();
    $pay_url = wholesale_ticket_payment_url($ticket);
    $totals = $ticket['totals'];
    $cell = 'padding:12px 10px;border-bottom:1px solid #e3e8ee;vertical-align:top;';
    $money_cell = $cell . 'text-align:right;white-space:nowrap;';

    $rows = '';
    foreach ($ticket['items'] as $item) {
        $specs = wholesale_ticket_item_specs($item);
        if ($item['notes']) {
            $specs[] = $item['notes'];
        }
        $rows .= '<tr>'
            . '<td style="' . $cell . '"><strong>' . esc_html($item['title']) . '</strong>'
            . ($item['design'] ? '<div style="margin-top:8px;"><a href="' . esc_url($item['design']['url']) . '"><img src="' . esc_url($item['design']['url']) . '" alt="Channel letter design" style="max-width:260px;width:100%;border:1px solid #e3e8ee;border-radius:4px;"></a></div>' : '')
            . ($specs ? '<div style="color:#5b6573;font-size:13px;line-height:1.5;margin-top:4px;">' . implode('<br>', array_map('esc_html', $specs)) . '</div>' : '')
            . '</td>'
            . '<td style="' . $cell . 'text-align:center;">' . esc_html($item['qty']) . '</td>'
            . '<td style="' . $money_cell . '">' . esc_html(wholesale_format_ticket_amount($item['unit_price'])) . '</td>'
            . '<td style="' . $money_cell . '">' . esc_html(wholesale_format_ticket_amount(wholesale_ticket_line_total($item))) . '</td>'
            . '</tr>';
    }

    $total_row = static function ($label, $value, $strong = false) {
        $style = $strong ? 'font-size:18px;font-weight:bold;color:#0b1f33;padding:10px 10px 0;' : 'color:#5b6573;padding:4px 10px;';
        return '<tr><td style="' . $style . 'text-align:right;">' . esc_html($label) . '</td><td style="' . $style . 'text-align:right;white-space:nowrap;width:120px;">' . esc_html($value) . '</td></tr>';
    };
    $summary = $total_row('Subtotal', wholesale_format_ticket_amount($totals['subtotal']));
    if ($totals['discount'] > 0) {
        $summary .= $total_row('Discount', '−' . wholesale_format_ticket_amount($totals['discount']));
    }
    if ($totals['shipping'] > 0) {
        $summary .= $total_row('Shipping', wholesale_format_ticket_amount($totals['shipping']));
    }
    if ($totals['tax'] > 0) {
        $summary .= $total_row('Tax (' . rtrim(rtrim(number_format($totals['tax_rate'], 3), '0'), '.') . '%)', wholesale_format_ticket_amount($totals['tax']));
    }
    $summary .= $total_row('Total due', wholesale_format_ticket_amount($totals['grand_total']), true);

    $proof = '';
    if ($ticket['proof_id'] && ($proof_url = wp_get_attachment_url($ticket['proof_id']))) {
        $proof = '<p style="margin:0 0 20px;"><a href="' . esc_url($proof_url) . '" style="color:#0c7fae;">View your design proof</a></p>';
    }

    return '<!doctype html><html><body style="margin:0;background:#f3f5f8;font-family:Arial,Helvetica,sans-serif;color:#1d2733;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f8;padding:24px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e3e8ee;">'
        . '<tr><td style="padding:24px 28px;border-bottom:4px solid #1fa8de;">'
        . ($business['logo'] ? '<img src="' . esc_url($business['logo']) . '" alt="' . esc_attr($business['name']) . '" style="max-height:44px;max-width:220px;">' : '<strong style="font-size:18px;">' . esc_html($business['name']) . '</strong>')
        . '</td></tr>'
        . '<tr><td style="padding:28px 28px 8px;">'
        . '<p style="margin:0 0 4px;color:#5b6573;font-size:13px;letter-spacing:1px;text-transform:uppercase;">Payment request ' . esc_html($ticket['number']) . '</p>'
        . '<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#0b1f33;">' . esc_html($ticket['title']) . '</h1>'
        . '<p style="margin:0 0 16px;line-height:1.6;">Hi ' . esc_html($ticket['customer_name']) . ',</p>'
        . ($ticket['message'] ? '<p style="margin:0 0 16px;line-height:1.6;">' . nl2br(esc_html($ticket['message'])) . '</p>' : '<p style="margin:0 0 16px;line-height:1.6;">Here is the payment request for your custom order. You can review the details and pay securely online.</p>')
        . '</td></tr>'
        . '<tr><td style="padding:0 18px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">'
        . '<tr><th align="left" style="padding:8px 10px;color:#5b6573;font-size:12px;text-transform:uppercase;border-bottom:2px solid #e3e8ee;">Item</th><th style="padding:8px 10px;color:#5b6573;font-size:12px;text-transform:uppercase;border-bottom:2px solid #e3e8ee;">Qty</th><th align="right" style="padding:8px 10px;color:#5b6573;font-size:12px;text-transform:uppercase;border-bottom:2px solid #e3e8ee;">Price</th><th align="right" style="padding:8px 10px;color:#5b6573;font-size:12px;text-transform:uppercase;border-bottom:2px solid #e3e8ee;">Amount</th></tr>'
        . $rows
        . '</table>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin-top:8px;">' . $summary . '</table>'
        . '</td></tr>'
        . '<tr><td style="padding:24px 28px 8px;">'
        . '<p style="margin:0 0 20px;"><a href="' . esc_url($pay_url) . '" style="display:inline-block;background:#1fa8de;color:#ffffff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:16px;">Review &amp; pay ' . esc_html(wholesale_format_ticket_amount($ticket['amount'])) . '</a></p>'
        . $proof
        . ($ticket['due'] ? '<p style="margin:0 0 12px;color:#5b6573;font-size:13px;">Please pay by ' . esc_html(date_i18n('F j, Y', strtotime($ticket['due']))) . '.</p>' : '')
        . '<p style="margin:0 0 12px;color:#5b6573;font-size:13px;">Button not working? Copy this link: <a href="' . esc_url($pay_url) . '" style="color:#0c7fae;word-break:break-all;">' . esc_html($pay_url) . '</a></p>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 28px;background:#f8fafc;color:#5b6573;font-size:12px;line-height:1.6;border-top:1px solid #e3e8ee;">'
        . esc_html($business['name']) . ' · ' . esc_html($business['address']) . '<br>'
        . 'Questions? Reply to this email or call ' . esc_html($business['phone']) . '.'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
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
        'saved' => array('success', 'Ticket saved.'),
        'note_saved' => array('success', 'Internal note saved.'),
        'sent' => array('success', 'Payment request emailed to the customer.'),
        'cancelled' => array('warning', 'Ticket cancelled. The payment link no longer works.'),
        'duplicated' => array('success', 'Copy created. Review the details, then send it.'),
        'design_saved' => array('success', 'Channel letter design attached. Check the unit price, then save or send the ticket.'),
        'builder_invalid' => array('error', 'Saved, but the builder could not be opened: choose a channel letter product on that line first.'),
        'missing_customer' => array('error', 'Saved, but not sent: add the customer\'s name and a valid email address.'),
        'no_items' => array('error', 'Saved, but not sent: add at least one product or custom item.'),
        'zero_total' => array('error', 'Saved, but not sent: the total due must be more than $0.00.'),
        'locked' => array('error', 'Not sent: this ticket is already paid, accepted or cancelled.'),
        'send_failed' => array('error', 'The payment link is active, but the email could not be sent. Copy the link from the Payment ticket box and send it to the customer yourself.'),
    );
    if (!isset($notices[$code])) {
        return;
    }

    printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($notices[$code][0]), esc_html($notices[$code][1]));
}
add_action('admin_notices', 'wholesale_ticket_admin_notice');

/**
 * Hide WordPress's own "Post updated" message; our notice says what happened.
 */
function wholesale_ticket_updated_messages($messages)
{
    $messages['payment_ticket'] = array_fill(0, 11, '');
    return $messages;
}
add_filter('post_updated_messages', 'wholesale_ticket_updated_messages');

/* ---------------------------------------------------------------------------
 * Admin: duplicate
 * ------------------------------------------------------------------------ */

function wholesale_ticket_duplicate_url($ticket_id)
{
    return wp_nonce_url(admin_url('admin-post.php?action=wholesale_duplicate_ticket&ticket=' . absint($ticket_id)), 'wholesale_duplicate_ticket_' . absint($ticket_id));
}

function wholesale_handle_duplicate_ticket()
{
    $source_id = isset($_GET['ticket']) ? absint($_GET['ticket']) : 0;
    check_admin_referer('wholesale_duplicate_ticket_' . $source_id);
    if (!$source_id || 'payment_ticket' !== get_post_type($source_id) || !current_user_can('edit_post', $source_id) || !current_user_can('edit_posts')) {
        wp_die(esc_html__('You are not allowed to copy this ticket.', 'litsign'), '', array('response' => 403));
    }

    $source = wholesale_get_ticket($source_id);
    $copy_id = wp_insert_post(array(
        'post_type' => 'payment_ticket',
        'post_status' => 'draft',
        'post_title' => $source['title'],
    ), true);
    if (is_wp_error($copy_id)) {
        wp_die(esc_html($copy_id->get_error_message()));
    }

    $meta = array(
        '_ticket_customer_name' => $source['customer_name'],
        '_ticket_customer_company' => $source['customer_company'],
        '_ticket_customer_email' => $source['customer_email'],
        '_ticket_customer_phone' => $source['customer_phone'],
        '_ticket_description' => $source['message'],
        '_ticket_internal_note' => $source['internal_note'],
        '_ticket_items' => $source['items'],
        '_ticket_discount' => $source['totals']['discount'],
        '_ticket_shipping' => $source['totals']['shipping'],
        '_ticket_tax_rate' => $source['totals']['tax_rate'],
        '_ticket_amount' => $source['amount'],
        '_ticket_proof_id' => $source['proof_id'],
        '_ticket_status' => 'draft',
        '_ticket_token' => wp_generate_password(32, false, false),
    );
    foreach ($meta as $key => $value) {
        update_post_meta($copy_id, $key, $value);
    }
    wholesale_ticket_log($copy_id, 'duplicated', 'From ' . $source['number']);
    wholesale_ticket_set_notice('duplicated');

    wp_safe_redirect(admin_url('post.php?post=' . $copy_id . '&action=edit'));
    exit;
}
add_action('admin_post_wholesale_duplicate_ticket', 'wholesale_handle_duplicate_ticket');

/* ---------------------------------------------------------------------------
 * Admin: list screen
 * ------------------------------------------------------------------------ */

function wholesale_ticket_list_columns($columns)
{
    return array(
        'cb' => $columns['cb'],
        'ticket_number' => 'Ticket',
        'title' => 'Project',
        'ticket_customer' => 'Customer',
        'ticket_items' => 'Items',
        'ticket_amount' => 'Total',
        'ticket_status' => 'Status',
        'ticket_activity' => 'Last activity',
    );
}
add_filter('manage_payment_ticket_posts_columns', 'wholesale_ticket_list_columns');

function wholesale_ticket_list_column_value($column, $post_id)
{
    $ticket = wholesale_get_ticket($post_id);

    switch ($column) {
        case 'ticket_number':
            echo '<span class="wpt-num wpt-list-number">' . esc_html($ticket['number']) . '</span>';
            break;
        case 'ticket_customer':
            echo esc_html($ticket['customer_name']);
            if ($ticket['customer_company']) {
                echo '<br><small>' . esc_html($ticket['customer_company']) . '</small>';
            }
            if ($ticket['customer_email']) {
                echo '<br><small class="wpt-muted">' . esc_html($ticket['customer_email']) . '</small>';
            }
            break;
        case 'ticket_items':
            $titles = wp_list_pluck($ticket['items'], 'title');
            echo esc_html($titles ? $titles[0] : '—');
            if (count($titles) > 1) {
                echo ' <small class="wpt-muted">+' . esc_html((string) (count($titles) - 1)) . ' more</small>';
            }
            break;
        case 'ticket_amount':
            echo '<strong class="wpt-num">' . esc_html(wholesale_format_ticket_amount($ticket['amount'])) . '</strong>';
            break;
        case 'ticket_status':
            echo '<span class="wpt-pill wpt-pill--' . esc_attr(wholesale_ticket_status_key($ticket)) . '">' . esc_html(wholesale_ticket_status_label($ticket)) . '</span>';
            break;
        case 'ticket_activity':
            $last = end($ticket['log']);
            if ($last) {
                $labels = wholesale_ticket_log_labels();
                echo esc_html($labels[$last['event']] ?? $last['event']) . '<br><small class="wpt-muted">' . esc_html(human_time_diff(strtotime($last['time']), current_time('timestamp')) . ' ago') . '</small>';
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_payment_ticket_posts_custom_column', 'wholesale_ticket_list_column_value', 10, 2);

function wholesale_ticket_row_actions($actions, $post)
{
    if ('payment_ticket' !== $post->post_type) {
        return $actions;
    }
    unset($actions['inline hide-if-no-js']);
    if (current_user_can('edit_post', $post->ID)) {
        $actions['wpt_duplicate'] = '<a href="' . esc_url(wholesale_ticket_duplicate_url($post->ID)) . '">Duplicate</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'wholesale_ticket_row_actions', 10, 2);

/* ---------------------------------------------------------------------------
 * Customer payment (page lives in page-pay.php)
 * ------------------------------------------------------------------------ */

function wholesale_ticket_front_assets()
{
    if (!is_page('pay')) {
        return;
    }
    $path = '/css/payment-ticket.css';
    $file = get_template_directory() . $path;
    wp_enqueue_style('wholesale-payment-ticket', get_template_directory_uri() . $path, array(), file_exists($file) ? (string) filemtime($file) : _S_VERSION);
}
add_action('wp_enqueue_scripts', 'wholesale_ticket_front_assets', 20);

/**
 * Record the first time the customer opens their link (not admin previews).
 */
function wholesale_ticket_mark_viewed($ticket)
{
    if ('sent' !== $ticket['status'] || $ticket['viewed_at'] || current_user_can('edit_post', $ticket['id'])) {
        return;
    }
    update_post_meta($ticket['id'], '_ticket_viewed_at', current_time('mysql'));
    wholesale_ticket_log($ticket['id'], 'viewed');
}

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
    if ('sent' !== $ticket['status'] || wholesale_ticket_is_expired($ticket) || $ticket['amount'] <= 0 || empty($ticket['items'])) {
        delete_option($lock_key);
        wholesale_ticket_pay_redirect($token);
    }

    $amount = $ticket['amount'];
    $totals = $ticket['totals'];
    $billing_data = wp_json_encode($billing);

    if (!$payment_disabled) {
        $result = processPayment(number_format($amount, 2, '.', ''), $card_number, $card_exp_month . $card_exp_year, $card_cvv, trim($billing['billing_address'] . ', ' . $billing['billing_city'] . ', ' . $billing['billing_country']), $billing['billing_zip']);
        if ('success' !== $result['status']) {
            delete_option($lock_key);
            wholesale_ticket_log($ticket_id, 'payment_failed', $result['message']);
            wholesale_ticket_pay_redirect($token, $result['message']);
        }
        // The card is charged from here on. Mark the ticket paid first so it can never be charged twice.
        update_post_meta($ticket_id, '_ticket_status', 'paid');
        wholesale_ticket_log($ticket_id, 'paid', wholesale_format_ticket_amount($amount));
    } else {
        update_post_meta($ticket_id, '_ticket_status', 'accepted');
        wholesale_ticket_log($ticket_id, 'accepted', wholesale_format_ticket_amount($amount));
    }
    update_post_meta($ticket_id, '_ticket_paid_at', current_time('mysql'));

    // One order line per ticket line, in the shape the admin order boxes and emails read.
    $logo = wholesale_ticket_business()['logo'];
    $order_items = array();
    foreach (array_values($ticket['items']) as $index => $item) {
        $details = array();
        if ($item['product_id']) {
            $details['Product Id'] = $item['product_id'];
        }
        if ($item['width']) {
            $details['Width'] = $item['width'] . ' Inches';
        }
        if ($item['height']) {
            $details['Height'] = $item['height'] . ' Inches';
        }
        foreach ($item['options'] as $option) {
            $details[$option['label']] = $option['value'];
        }
        if ($item['design']) {
            $details['Design Url'] = $item['design']['url'];
        }
        if ($item['notes']) {
            $details['Notes'] = $item['notes'];
        }
        $details['Payment Ticket'] = $ticket['number'];

        $order_items[] = array(
            'cart_id' => 'ticket-' . $ticket_id . '-' . ($index + 1),
            'product_id' => $item['product_id'],
            'product_title' => $item['title'],
            'product_thumbnail' => $item['design'] ? $item['design']['url'] : (wholesale_ticket_product_thumb($item['product_id'], 'medium') ?: $logo),
            'product_quantity' => $item['qty'],
            'product_subtotal' => wholesale_ticket_line_total($item),
            'product_details' => $details,
            'design_id' => $item['design'] ? (int) $item['design']['id'] : 0,
            'job_name' => $ticket['title'],
        );
    }
    $order_cost = wp_json_encode(array(
        'grand_total' => $amount,
        'sub_total' => round($totals['subtotal'] - $totals['discount'], 2),
        'shipping_cost' => $totals['shipping'],
        'tax' => $totals['tax'],
    ));

    $comment = array(($payment_disabled ? 'Payment ticket accepted - collect payment manually.' : 'Paid by card via payment ticket') . ' ' . $ticket['number'] . '.');
    if ($totals['discount'] > 0) {
        $comment[] = 'Discount applied: ' . wholesale_format_ticket_amount($totals['discount']) . ' (subtotal ' . wholesale_format_ticket_amount($totals['subtotal']) . ').';
    }
    if ($ticket['message']) {
        $comment[] = 'Message: ' . $ticket['message'];
    }

    // wp_slash() keeps quotes inside the JSON intact when WordPress unslashes meta on save.
    $order = wholesale_insert_order(wp_slash(wp_json_encode($order_items)), wp_slash($order_cost), wp_slash($billing_data), wp_slash($billing_data), implode("\n", $comment), '', array('_ticket_id' => $ticket_id));
    delete_option($lock_key);

    if (!$order) {
        error_log(sprintf('Wholesale: payment ticket %s (%s) paid by %s but the order could not be saved.', $ticket['number'], number_format($amount, 2, '.', ''), $billing['billing_email']));
        wp_mail(
            wholesale_contact_admin_recipients(),
            'URGENT: payment ticket paid but order was not saved',
            sprintf("Payment ticket %s (%s) for %s was %s, but the order could not be saved.\n\nCustomer: %s %s (%s)\nBilling: %s\n\nOpen the ticket: %s",
                $ticket['number'], $ticket['title'], wholesale_format_ticket_amount($amount), $payment_disabled ? 'accepted' : 'PAID BY CARD',
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
