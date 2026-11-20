<?php
/**
 * Artwork after ordering (owner request 2026-11-17).
 *
 * Artwork is optional on the product page, so some orders arrive without a file. Each order
 * item now has an Artwork box:
 * - On the order page (single-order.php) a signed-in customer who owns the order can upload
 *   the missing file for an item while the order is in review, on hold or in production.
 * - Staff can upload or replace any item's file at any time, on the order page and on the
 *   wp-admin order screen (template/display_admin_orders.php). A file whose shape doesn't
 *   match the order is refused for customers; staff get the same message and can save it anyway.
 *
 * Files get the product page's checks (PDF, JPG or PNG, the size limit and the shape check in
 * inc/artwork-size-check.php) and are stored like utils/Cart.php stores them: an attachment
 * titled custom-artwork-{product}-{id}, its id in the line's artwork_id and its URL in the
 * line's "My Artwork" detail, which the order emails, the media "Order artwork" tab and
 * SFS B2Sign Sync read. Each upload is logged (_artwork_log) for the order's Activity box,
 * and a customer upload emails the team.
 *
 * Product setting "Artwork after ordering" (meta _artwork_after_order, Product details >
 * Visibility and settings): empty = customers may add artwork after ordering whenever
 * "Allow artwork upload" is ticked; "off" = not for this product.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/* ---------------------------------------------------------------------------------------
 * Who may upload, and for which items
 * ------------------------------------------------------------------------------------- */

/**
 * Order statuses in which a customer may still add missing artwork (before Finishing).
 */
function wholesale_order_artwork_customer_statuses()
{
	return array('in-review', 'pending', 'on-hold', 'on_hold', 'processing');
}

function wholesale_order_item_product_id(array $item)
{
	return absint($item['product_id'] ?? ($item['product_details']['Product Id'] ?? 0));
}

/**
 * Whether customers may add artwork to this product after ordering.
 */
function wholesale_order_artwork_product_allows($product_id)
{
	$product_id = absint($product_id);
	return $product_id
		&& 'product' === get_post_type($product_id)
		&& 'on' === get_post_meta($product_id, '_has_upload_artwork', true)
		&& 'off' !== get_post_meta($product_id, '_artwork_after_order', true);
}

/**
 * The URL of an order line's artwork file, or ''. Older lines may hold a link instead of a URL.
 */
function wholesale_order_item_artwork_url(array $item)
{
	$value = $item['product_details']['My Artwork'] ?? '';
	if (!is_scalar($value) || '' === trim((string) $value)) {
		return '';
	}
	$value = trim((string) $value);
	if (preg_match('/href\s*=\s*[\'"]([^\'"]+)[\'"]/i', $value, $matches)) {
		return $matches[1];
	}
	return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
}

/**
 * True when the signed-in visitor placed this order (the same rule as My orders).
 */
function wholesale_order_artwork_is_owner($order_id)
{
	$owner = absint(get_post_meta($order_id, 'user_id', true));
	return $owner && is_user_logged_in() && $owner === get_current_user_id();
}

/**
 * Whether a customer can still add artwork to this line (no file yet, product allows it,
 * order not printed yet), leaving aside who is looking.
 */
function wholesale_order_artwork_line_open($order_id, array $item)
{
	return '' === wholesale_order_item_artwork_url($item)
		&& in_array((string) get_post_status($order_id), wholesale_order_artwork_customer_statuses(), true)
		&& wholesale_order_artwork_product_allows(wholesale_order_item_product_id($item));
}

/**
 * What the current user may do with one line's artwork: 'staff' (upload or replace at any
 * time), 'customer' (add the missing file) or '' (nothing).
 */
function wholesale_order_artwork_access($order_id, array $item)
{
	if (current_user_can('edit_post', $order_id)) {
		return 'staff';
	}
	return wholesale_order_artwork_is_owner($order_id) && wholesale_order_artwork_line_open($order_id, $item) ? 'customer' : '';
}

/**
 * Line numbers (0-based) of an order that a customer could still add artwork to.
 *
 * @return int[]
 */
function wholesale_order_artwork_missing_lines($order_id)
{
	$lines = array();
	foreach (wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_json', true)) as $index => $item) {
		if (is_array($item) && wholesale_order_artwork_line_open($order_id, $item)) {
			$lines[] = (int) $index;
		}
	}
	return $lines;
}

/* ---------------------------------------------------------------------------------------
 * The size the file must have
 * ------------------------------------------------------------------------------------- */

/**
 * "2 Feet 6 Inches" => 30, "18 Inches" => 18, "1 Foot" => 12 (the sizes utils/Cart.php writes).
 */
function wholesale_order_artwork_inches($text)
{
	$text = strtolower(trim((string) $text));
	$total = 0.0;
	$found = false;
	if (preg_match('/(\d+(?:\.\d+)?)\s*(?:feet|foot|ft\b|\')/', $text, $matches)) {
		$total += 12 * (float) $matches[1];
		$found = true;
	}
	if (preg_match('/(\d+(?:\.\d+)?)\s*(?:inches|inch|in\b|")/', $text, $matches)) {
		$total += (float) $matches[1];
		$found = true;
	}
	if (!$found && is_numeric($text)) {
		$total = (float) $text;
	}
	return $total;
}

/**
 * The print size an order line's artwork must match, as wholesale_artwork_ordered_size()
 * returns it, or null when it can't be told (then the file isn't checked). The product
 * form is rebuilt from the line's details: option labels back to option values, and the
 * stored width and height in inches.
 */
function wholesale_order_item_artwork_size(array $item)
{
	$product_id = wholesale_order_item_product_id($item);
	if (!$product_id || 'product' !== get_post_type($product_id) || !function_exists('wholesale_artwork_ordered_size')) {
		return null;
	}
	$details = isset($item['product_details']) && is_array($item['product_details']) ? $item['product_details'] : array();
	$key = static function ($text) {
		return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $text));
	};
	// Older orders hold curly inch marks (33” x 81”, or the broken escape u201d) where the option says 33" x 81".
	$plain = static function ($text) {
		$text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = str_replace(array('u201d', 'u201c', '”', '“', '″'), '"', $text);
		$text = str_replace(array('u2019', 'u2018', '’', '‘', '′'), "'", $text);
		return strtolower(trim(preg_replace('/\s+/', ' ', $text)));
	};
	$by_key = array();
	foreach ($details as $label => $value) {
		if (is_scalar($value) && '' !== (string) $value) {
			$by_key[$key($label)] = (string) $value;
		}
	}

	$request = array();
	foreach (wholesale_product_price_attrs($product_id) as $attr) {
		$name = (string) ($attr['name'] ?? '');
		$stored = '' !== $name ? ($by_key[$key($name)] ?? null) : null;
		if (null === $stored) {
			continue;
		}
		foreach (wholesale_attr_options($attr) as $option) {
			if ($plain($option['label']) === $plain($stored) || trim($option['value']) === trim($stored)) {
				$request[$name] = $option['value'];
				break;
			}
		}
	}
	$width = wholesale_order_artwork_inches($details['Width'] ?? '');
	$height = wholesale_order_artwork_inches($details['Height'] ?? '');
	if ($width > 0 && $height > 0) {
		$request += array('width-ft' => 0, 'width-in' => $width, 'height-ft' => 0, 'height-in' => $height);
	}

	return wholesale_artwork_ordered_size($product_id, $request);
}

/* ---------------------------------------------------------------------------------------
 * Saving a file
 * ------------------------------------------------------------------------------------- */

/**
 * Check, store and attach an uploaded file to one order line.
 *
 * @param int   $order_id Order post ID.
 * @param int   $line     Line number in product_json (0-based).
 * @param array $file     A $_FILES entry.
 * @param array $args     cart_id: the line's cart_id, to be sure it is the same line;
 *                        force: staff only, save a file whose shape doesn't match.
 * @return array|WP_Error array(url, line, name, warning, replaced)
 */
function wholesale_order_artwork_save($order_id, $line, array $file, array $args = array())
{
	$args += array('cart_id' => '', 'force' => false);
	$order_id = absint($order_id);
	$line = (int) $line;
	$help = 'Please call 866-436-2101 or email TR@StorefrontSignOnline.com with your order number.';

	if ('order' !== get_post_type($order_id)) {
		return new WP_Error('artwork_order', 'This order could not be found. ' . $help);
	}
	$items = wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_json', true));
	$item = isset($items[$line]) && is_array($items[$line]) ? $items[$line] : null;
	if (!$item || ('' !== (string) $args['cart_id'] && (string) ($item['cart_id'] ?? '') !== (string) $args['cart_id'])) {
		return new WP_Error('artwork_line', 'This item could not be found on the order. Please reload the page and try again.');
	}
	$access = wholesale_order_artwork_access($order_id, $item);
	if ('' === $access) {
		return new WP_Error('artwork_denied', 'Artwork can’t be added to this item any more. ' . $help);
	}

	$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
	if (UPLOAD_ERR_NO_FILE === $error) {
		return new WP_Error('artwork_file', 'Please choose your artwork file.');
	}
	if (in_array($error, array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true)) {
		return new WP_Error('artwork_file', 'This file is too large to upload. ' . wholesale_large_file_help());
	}
	if (UPLOAD_ERR_OK !== $error || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
		return new WP_Error('artwork_file', 'Your artwork could not be uploaded. Please try again.');
	}
	$checked = wp_check_filetype_and_ext($file['tmp_name'], (string) $file['name'], wholesale_artwork_mimes());
	if (empty($checked['ext']) || empty($checked['type'])) {
		return new WP_Error('artwork_file', 'Artwork must be a ' . wholesale_artwork_types_text() . ' file.');
	}
	if (filesize($file['tmp_name']) > wholesale_artwork_max_bytes()) {
		return new WP_Error('artwork_file', 'Artwork files must be under ' . wholesale_artwork_max_text() . '. ' . wholesale_large_file_help());
	}

	// The shape check from the product page, against the size on the order.
	$product_id = wholesale_order_item_product_id($item);
	$warning = '';
	$ordered = wholesale_order_item_artwork_size($item);
	if ($ordered) {
		// Worded as a fixed size ("The print size for this item is ..."): after ordering, the size can't be changed.
		$ordered[2] = 'field';
		$problem = wholesale_artwork_size_problem(wholesale_artwork_measure($file['tmp_name'], $checked['type']), $ordered, wholesale_artwork_bleed_for($product_id));
		if ('' !== $problem) {
			if ('staff' !== $access || empty($args['force'])) {
				return new WP_Error('artwork_size', $problem, array('can_force' => 'staff' === $access));
			}
			$warning = $problem;
		}
	}

	// Stored the way utils/Cart.php stores Add To Cart artwork.
	$original = sanitize_file_name(wp_basename((string) $file['name']));
	$file_name = 'custom-artwork-' . ($product_id ?: 'order') . '-' . uniqid();
	$file['name'] = $file_name . '.' . $checked['ext'];
	$stored = wholesale_async_upload_store($file, array('mimes' => wholesale_artwork_mimes()));
	if (!empty($stored['error']) || empty($stored['file'])) {
		return new WP_Error('artwork_store', 'Your artwork could not be saved. Please try again.');
	}
	$attachment_id = wp_insert_attachment(array(
		'guid' => $stored['url'],
		'post_mime_type' => $checked['type'],
		'post_title' => $file_name,
		'post_content' => '',
		'post_status' => 'inherit',
	), $stored['file'], 0);
	if (!$attachment_id || is_wp_error($attachment_id)) {
		@unlink($stored['file']);
		return new WP_Error('artwork_store', 'Your artwork could not be saved. Please try again.');
	}
	$attach_data = array('file' => _wp_relative_upload_path($stored['file']), 'filesize' => (int) filesize($stored['file']));
	$image_size = 0 === strpos($checked['type'], 'image/') ? @getimagesize($stored['file']) : false;
	if ($image_size) {
		$attach_data['width'] = $image_size[0];
		$attach_data['height'] = $image_size[1];
	}
	wp_update_attachment_metadata($attachment_id, $attach_data);
	$url = esc_url_raw(wp_get_attachment_url($attachment_id));

	// Read the order again right before writing, so changes saved meanwhile are kept.
	$items = wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_json', true));
	if (!isset($items[$line]) || !is_array($items[$line])) {
		return new WP_Error('artwork_line', 'This item could not be found on the order. Please reload the page and try again.');
	}
	$previous = wholesale_order_item_artwork_url($items[$line]);
	if ('customer' === $access && '' !== $previous) {
		// Another upload got there first (two tabs): keep that one.
		wp_delete_attachment($attachment_id, true);
		return new WP_Error('artwork_denied', 'Artwork was already added to this item. ' . $help);
	}
	if (!isset($items[$line]['product_details']) || !is_array($items[$line]['product_details'])) {
		$items[$line]['product_details'] = array();
	}
	$items[$line]['artwork_id'] = $attachment_id;
	$items[$line]['product_details']['My Artwork'] = $url;
	update_post_meta($order_id, 'product_json', wp_slash(wp_json_encode($items)));

	add_post_meta($order_id, '_artwork_log', wp_slash(array(
		'time' => time(),
		'user' => get_current_user_id(),
		'by' => $access,
		'line' => $line,
		'product' => (string) ($items[$line]['product_title'] ?? ''),
		'attachment' => $attachment_id,
		'name' => $original,
		'previous' => $previous,
		'forced' => '' !== $warning,
	)));

	if ('customer' === $access) {
		wholesale_order_artwork_notify_team($order_id, $line, $url, $original);
	}
	do_action('wholesale_order_artwork_added', $order_id, $line, $attachment_id, $access);

	return array('url' => $url, 'line' => $line, 'name' => $original, 'warning' => $warning, 'replaced' => '' !== $previous);
}

/**
 * Email the team that a customer added artwork after ordering.
 */
function wholesale_order_artwork_notify_team($order_id, $line, $url, $name)
{
	$items = wholesale_decode_order_meta_array(get_post_meta($order_id, 'product_json', true));
	$billing = wholesale_decode_order_meta_array(get_post_meta($order_id, 'billing_address', true));
	$item = isset($items[$line]) && is_array($items[$line]) ? $items[$line] : array();
	$number = wholesale_order_number($order_id);
	$customer = trim(($billing['billing_fname'] ?? '') . ' ' . ($billing['billing_lname'] ?? '')) ?: 'The customer';
	$status = wholesale_order_status_info($order_id)[get_post_status($order_id)]['label'] ?? get_post_status($order_id);

	$message = '<html><body>'
		. '<h2>Artwork added to order #' . esc_html($number) . '</h2>'
		. '<p>' . esc_html($customer) . ' uploaded artwork after ordering for <strong>' . esc_html(wp_specialchars_decode((string) ($item['product_title'] ?? 'an item'), ENT_QUOTES)) . '</strong>' . (count($items) > 1 ? ' (item ' . ($line + 1) . ' of ' . count($items) . ')' : '') . '.</p>'
		. '<p><strong>File:</strong> ' . esc_html($name) . '<br>' . wholesale_format_order_detail_value('My Artwork', $url) . '</p>'
		. '<p><strong>Order status:</strong> ' . esc_html($status) . '</p>'
		. '<p><a href="' . esc_url(admin_url('post.php?post=' . absint($order_id) . '&action=edit')) . '">Open the order in admin</a></p>'
		. '</body></html>';

	wp_mail(
		wholesale_contact_admin_recipients(),
		sprintf('Artwork added to order #%s', $number),
		$message,
		array('Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . get_option('admin_email'))
	);
}

/* ---------------------------------------------------------------------------------------
 * Upload requests
 * ------------------------------------------------------------------------------------- */

function wholesale_order_artwork_request()
{
	return array(
		'order_id' => absint($_POST['order_id'] ?? 0),
		'line' => isset($_POST['line']) ? (int) $_POST['line'] : -1,
		'cart_id' => sanitize_text_field(wp_unslash($_POST['cart_id'] ?? '')),
		'force' => !empty($_POST['force']),
		'context' => 'admin' === ($_POST['context'] ?? '') ? 'admin' : 'site',
		'nonce' => sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')),
	);
}

/**
 * Upload with progress from js/order-artwork.js. Responds with the line's new Artwork box.
 */
function wholesale_ajax_order_artwork()
{
	nocache_headers();
	$request = wholesale_order_artwork_request();
	if (!wp_verify_nonce($request['nonce'], 'wholesale_order_artwork_' . $request['order_id'])) {
		wp_send_json_error(array('message' => 'This page has expired. Please reload it and try again.'), 403);
	}
	$result = wholesale_order_artwork_save($request['order_id'], $request['line'], (array) ($_FILES['artwork'] ?? array()), $request);
	if (is_wp_error($result)) {
		$data = (array) $result->get_error_data();
		wp_send_json_error(array(
			'message' => $result->get_error_message(),
			'code' => $result->get_error_code(),
			'can_force' => !empty($data['can_force']),
		), 400);
	}
	$items = wholesale_decode_order_meta_array(get_post_meta($request['order_id'], 'product_json', true));
	$result['html'] = wholesale_order_artwork_box_html($request['order_id'], $request['line'], (array) ($items[$request['line']] ?? array()), $request['context'], array(
		'ok' => true,
		'message' => wholesale_order_artwork_success_text($result),
	));
	wp_send_json_success($result);
}
add_action('wp_ajax_wholesale_order_artwork', 'wholesale_ajax_order_artwork');

/**
 * The same upload as a plain form post (no JavaScript): back to the order with a message.
 */
function wholesale_post_order_artwork()
{
	$request = wholesale_order_artwork_request();
	$back = 'admin' === $request['context']
		? admin_url('post.php?post=' . $request['order_id'] . '&action=edit')
		: get_permalink($request['order_id']);
	if (!$back || !wp_verify_nonce($request['nonce'], 'wholesale_order_artwork_' . $request['order_id'])) {
		wp_die('This page has expired. Please go back, reload it and try again.', 'Artwork upload', array('response' => 403, 'back_link' => true));
	}
	$result = wholesale_order_artwork_save($request['order_id'], $request['line'], (array) ($_FILES['artwork'] ?? array()), $request);
	set_transient('wholesale_order_artwork_flash_' . get_current_user_id(), array(
		'order' => $request['order_id'],
		'line' => $request['line'],
		'ok' => !is_wp_error($result),
		'message' => is_wp_error($result) ? $result->get_error_message() : wholesale_order_artwork_success_text($result),
	), 5 * MINUTE_IN_SECONDS);
	wp_safe_redirect($back . '#artwork-line-' . $request['line']);
	exit;
}
add_action('admin_post_wholesale_order_artwork', 'wholesale_post_order_artwork');

function wholesale_order_artwork_success_text(array $result)
{
	$text = $result['replaced'] ? 'Artwork replaced.' : 'Artwork uploaded. Thank you!';
	if ('' !== $result['warning']) {
		$text .= ' Saved even though its shape doesn’t match the order.';
	}
	return $text;
}

/* ---------------------------------------------------------------------------------------
 * The Artwork box under each order item
 * ------------------------------------------------------------------------------------- */

/**
 * The Artwork box for one order line: the current file (Open / Download) and, when the
 * current user may upload, the upload form. '' when there is neither.
 *
 * @param string $context 'site' (single-order.php) or 'admin' (wp-admin order screen, where
 *                        the box sits inside the post form, so it has no form of its own).
 * @param array  $notice  Optional array(ok, message) shown in the box.
 */
function wholesale_order_artwork_box_html($order_id, $line, array $item, $context = 'site', array $notice = array())
{
	$order_id = absint($order_id);
	$line = (int) $line;
	$access = $item ? wholesale_order_artwork_access($order_id, $item) : '';
	$url = $item ? wholesale_order_item_artwork_url($item) : '';
	if ('' === $access && '' === $url) {
		return '';
	}

	// A message left by the no-JavaScript form post.
	$flash_key = 'wholesale_order_artwork_flash_' . get_current_user_id();
	$flash = is_user_logged_in() ? get_transient($flash_key) : false;
	if (!$notice && is_array($flash) && (int) $flash['order'] === $order_id && (int) $flash['line'] === $line) {
		$notice = $flash;
		delete_transient($flash_key);
	}

	$admin = 'admin' === $context;
	$id = 'order-artwork-' . $order_id . '-' . $line;
	$ordered = '' !== $access ? wholesale_order_item_artwork_size($item) : null;
	$added = wholesale_order_artwork_last_log($order_id, $line);
	$note = '';
	if ($added && '' !== $url) {
		$who = 'customer' === ($added['by'] ?? '') ? 'by the customer' : 'by staff';
		$note = 'Added ' . wp_date('M j', (int) $added['time']) . ' ' . $who . ' after ordering' . (!empty($added['forced']) ? ' (shape didn’t match the order)' : '');
	}

	ob_start();
	?>
	<div class="order-artwork<?php echo '' === $url ? ' is-missing' : ' is-set'; ?><?php echo $admin ? ' order-artwork--admin' : ''; ?>" id="artwork-line-<?php echo esc_attr($line); ?>" data-order-artwork data-order="<?php echo esc_attr($order_id); ?>" data-line="<?php echo esc_attr($line); ?>" data-cart="<?php echo esc_attr((string) ($item['cart_id'] ?? '')); ?>" data-context="<?php echo esc_attr($admin ? 'admin' : 'site'); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wholesale_order_artwork_' . $order_id)); ?>" data-max="<?php echo esc_attr(wholesale_artwork_max_bytes()); ?>">
		<div class="order-artwork__head">
			<strong class="order-artwork__label">Artwork</strong>
			<?php if ('' !== $url) : ?>
				<?php echo wholesale_format_order_detail_value('My Artwork', $url); // Escaped in the helper. ?>
				<?php if ($note) : ?><small class="order-artwork__note"><?php echo esc_html($note); ?></small><?php endif; ?>
			<?php else : ?>
				<span class="order-artwork__none"><?php echo 'customer' === $access ? 'Not uploaded yet' : 'No file on this order'; ?></span>
			<?php endif; ?>
		</div>

		<?php if (!empty($notice['message'])) : ?>
			<p class="order-artwork__status <?php echo !empty($notice['ok']) ? 'is-done' : 'is-error'; ?>" role="status"><?php echo esc_html($notice['message']); ?></p>
		<?php endif; ?>

		<?php if ('' !== $access) : ?>
			<?php if (!$admin) : ?>
				<form class="order-artwork__form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="wholesale_order_artwork">
					<input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
					<input type="hidden" name="line" value="<?php echo esc_attr($line); ?>">
					<input type="hidden" name="cart_id" value="<?php echo esc_attr((string) ($item['cart_id'] ?? '')); ?>">
					<input type="hidden" name="context" value="site">
					<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wholesale_order_artwork_' . $order_id)); ?>">
			<?php else : ?>
				<div class="order-artwork__form">
			<?php endif; ?>
					<?php if ('customer' === $access) : ?>
						<p class="order-artwork__intro">Didn’t send your artwork with the order? Upload it here and we’ll add it to your order.</p>
					<?php endif; ?>
					<div class="order-artwork__row">
						<label class="screen-reader-text" for="<?php echo esc_attr($id); ?>"><?php echo esc_html(('' === $url ? 'Upload artwork for ' : 'Replace artwork for ') . wp_specialchars_decode((string) ($item['product_title'] ?? 'this item'), ENT_QUOTES)); ?></label>
						<input type="file" id="<?php echo esc_attr($id); ?>" class="order-artwork__file"<?php echo $admin ? '' : ' name="artwork" required'; ?> accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
						<button type="<?php echo $admin ? 'button' : 'submit'; ?>" class="<?php echo $admin ? 'button' : 'order-artwork__button'; ?> order-artwork__submit"><?php echo '' === $url ? 'Upload artwork' : 'Replace artwork'; ?></button>
					</div>
					<small class="order-artwork__hint">
						<?php echo esc_html(wholesale_artwork_types_text()); ?>, up to <?php echo esc_html(wholesale_artwork_max_text()); ?><?php
						if ($ordered) {
							echo esc_html(', made at ' . wholesale_artwork_inches_text($ordered[0]) . ' W × ' . wholesale_artwork_inches_text($ordered[1]) . ' H or the same shape');
						} ?>. <?php echo $admin ? 'Replacing keeps the old file in the media library.' : esc_html(wholesale_large_file_help()); ?>
					</small>
					<p class="order-artwork__progress" role="status" aria-live="polite" hidden></p>
			<?php if (!$admin) : ?>
				</form>
			<?php else : ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * The latest _artwork_log entry for one line, or null.
 */
function wholesale_order_artwork_last_log($order_id, $line)
{
	$last = null;
	foreach (get_post_meta($order_id, '_artwork_log') as $entry) {
		if (is_array($entry) && (int) ($entry['line'] ?? -1) === (int) $line && (!$last || (int) $entry['time'] >= (int) $last['time'])) {
			$last = $entry;
		}
	}
	return $last;
}

// The order's Activity box (inc/admin-orders.php).
add_filter('wholesale_order_activity_events', function ($events, $post) {
	foreach (get_post_meta($post->ID, '_artwork_log') as $entry) {
		if (!is_array($entry) || empty($entry['time'])) {
			continue;
		}
		$user = !empty($entry['user']) ? get_userdata((int) $entry['user']) : null;
		$who = 'customer' === ($entry['by'] ?? '') ? 'customer' : ($user ? $user->display_name : 'staff');
		$events[] = array(
			'time' => (int) $entry['time'],
			'text' => (!empty($entry['previous']) ? 'Artwork replaced' : 'Artwork uploaded') . ' by ' . $who . ': ' . wp_specialchars_decode((string) ($entry['product'] ?? ''), ENT_QUOTES) . (!empty($entry['forced']) ? ' (shape didn’t match)' : ''),
		);
	}
	return $events;
}, 10, 2);

/* ---------------------------------------------------------------------------------------
 * Scripts and styles
 * ------------------------------------------------------------------------------------- */

function wholesale_order_artwork_assets($ajax_url)
{
	$dir = get_template_directory();
	wp_enqueue_style('wholesale-order-artwork', get_template_directory_uri() . '/css/order-artwork.css', array(), (string) filemtime($dir . '/css/order-artwork.css'));
	wp_enqueue_script('wholesale-order-artwork', get_template_directory_uri() . '/js/order-artwork.js', array(), (string) filemtime($dir . '/js/order-artwork.js'), true);
	wp_localize_script('wholesale-order-artwork', 'wholesaleOrderArtwork', array(
		'ajaxUrl' => $ajax_url,
		'maxText' => wholesale_artwork_max_text(),
		'largeHelp' => wholesale_large_file_help(),
	));
}

add_action('wp_enqueue_scripts', function () {
	if (!is_user_logged_in()) {
		return;
	}
	if (is_singular('order')) {
		wholesale_order_artwork_assets(wp_make_link_relative(admin_url('admin-ajax.php')));
	} elseif (is_page(array('my-orders', 'my_orders')) || is_page_template('my_orders.php') || get_query_var('wholesale_thank_you')) {
		// Only the "Upload artwork" link style.
		wp_enqueue_style('wholesale-order-artwork', get_template_directory_uri() . '/css/order-artwork.css', array(), (string) filemtime(get_template_directory() . '/css/order-artwork.css'));
	}
}, 20);

add_action('admin_enqueue_scripts', function ($hook) {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (in_array($hook, array('post.php', 'post-new.php'), true) && $screen && 'order' === $screen->post_type) {
		wholesale_order_artwork_assets(admin_url('admin-ajax.php'));
	}
});

/* ---------------------------------------------------------------------------------------
 * Product setting
 * ------------------------------------------------------------------------------------- */

// "Artwork after ordering", right after "Allow artwork upload" (before the product editor's
// copy pass at priority 20 in inc/admin-product-editor.php).
add_action('cmb2_admin_init', function () {
	$box = function_exists('cmb2_get_metabox') ? cmb2_get_metabox('product_details') : null;
	if (!$box || $box->get_field('_artwork_after_order')) {
		return;
	}
	$position = array_search('_has_upload_artwork', array_keys((array) $box->prop('fields')), true);
	$box->add_field(array(
		'name' => 'Artwork after ordering',
		'desc' => 'Used when "Allow artwork upload" is ticked. Yes: a signed-in customer who ordered without a file can upload it from their order page (My orders) until the order is Finishing or shipped. It gets the same file type, size and shape check as the product page, and the team is emailed. Staff can always upload or replace artwork on the order screen.',
		'id' => '_artwork_after_order',
		'type' => 'select',
		'options' => array(
			'' => 'Yes, customers can add it after ordering',
			'off' => 'No, only on the product page',
		),
	), false === $position ? 0 : $position + 2);
}, 15);
