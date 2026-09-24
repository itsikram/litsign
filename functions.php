<?php

if (!defined('WHOLESALE_CONVERGE_MERCHANT_ID')) {
	define('WHOLESALE_CONVERGE_MERCHANT_ID', '2466214');
}

if (!defined('WHOLESALE_CONVERGE_USER_ID')) {
	define('WHOLESALE_CONVERGE_USER_ID', 'apiuser229803');
}

if (!defined('WHOLESALE_CONVERGE_PIN')) {
	define('WHOLESALE_CONVERGE_PIN', 'C2UZW43BPDVLYKUS4ZV0V1OUZWUTHGD66BIR65V4UFRFN8N8VB7OJV6CZOSDKHIW');
}

if (!defined('WHOLESALE_CONVERGE_TEST_MODE')) {
	define('WHOLESALE_CONVERGE_TEST_MODE', false);
}


if (!defined('WHOLESALE_PAYMENT_DISABLED')) {
	define('WHOLESALE_PAYMENT_DISABLED', false);
}



/**
 * litsign functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package litsign
 */

if (!defined('_S_VERSION')) {
	// Replace the version number of the theme on each release.
	define('_S_VERSION', '2.5.1');
}


if (file_exists(dirname(__FILE__) . '/cmb2/init.php')) {
	require_once(dirname(__FILE__) . '/cmb2/init.php');
}
if (file_exists(dirname(__FILE__) . '/template/display_admin_orders.php')) {
	require_once(dirname(__FILE__) . '/template/display_admin_orders.php');
}
require_once(dirname(__FILE__) . '/inc/orders.php');
require_once(dirname(__FILE__) . '/template/admin_payment_tickets.php');

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function wholesale_setup()
{
	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on litsign, use a find and replace
	 * to change 'litsign' to the name of your theme in all the template files.
	 */
	load_theme_textdomain('litsign', get_template_directory() . '/languages');

	// Add default posts and comments RSS feed links to head.
	add_theme_support('automatic-feed-links');

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support('title-tag');

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
	 */
	add_theme_support('post-thumbnails');

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'header-menu' => esc_html__('Primary', 'litsign'),
		),

	);
	register_nav_menus(
		array(
			'header-bottom-menu' => esc_html__('Header Bottom', 'litsign'),
		)

	);
	register_nav_menus(
		array(
			'category-filter-menu' => esc_html__('Category Filter Menu', 'litsign'),
		)

	);

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'litsign_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support('customize-selective-refresh-widgets');

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height' => 250,
			'width' => 250,
			'flex-width' => true,
			'flex-height' => true,
		)
	);

	add_image_size('header-logo', 240, 40, false);
	add_image_size('product-card', 290, 290, true);

	register_post_type(
		'product',
		array(
			'label' => 'Product',
			'supports' => array('title', 'editor', 'thumbnail'),
			'labels' => array(
				'name' => 'Products',
				'singular_name' => 'Product'

			),
			'public' => true,
			'publicly_queryable' => true,
			'show_in_rest' => true,
			'has_archive' => false,
			'rewrite' => array('slug' => 'product'),
		)
	);
	register_post_type('cnn', array(
		'label' => 'CNN',
		'supports' => array('title', 'editor', 'thumbnail'),
		'labels' => array(
			'name' => 'CNNS',
			'singular_name' => 'CNN'
		),
		'public' => true,

	));

	register_post_type('order', array(
		'label' => 'Order',
		'public' => true,
		'exclude_from_search' => true, // Orders contain customer details; never list them in site search.
		'show_in_nav_menus' => false,
		'supports' => array('title', 'editor', 'thumbnail'),
		'has_archive' => false,
		'rewrite' => array('slug' => 'order'), // Custom slug for your post type
		'query_var' => 'store_order',
		'show_in_rest' => true, // Enable block editor support
	));

	register_post_type('contact_submission', array(
		'label' => 'Contact Submissions',
		'labels' => array(
			'name' => 'Contact Submissions',
			'singular_name' => 'Contact Submission',
			'add_new_item' => 'Add Contact Submission',
			'edit_item' => 'View Contact Submission',
		),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => false,
		'supports' => array('title', 'editor'),
		'menu_icon' => 'dashicons-email-alt',
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));
	register_post_type('review_submission', array(
		'label' => 'Review Submissions',
		'labels' => array(
			'name' => 'Review Submissions',
			'singular_name' => 'Review Submission',
			'edit_item' => 'View Review Submission',
		),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => false,
		'supports' => array('title', 'editor'),
		'menu_icon' => 'dashicons-star-filled',
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));
	register_post_type('payment_ticket', array(
		'label' => 'Payment Tickets',
		'labels' => array(
			'name' => 'Payment Tickets',
			'singular_name' => 'Payment Ticket',
			'add_new' => 'New Ticket',
			'add_new_item' => 'New Payment Ticket',
			'edit_item' => 'Edit Payment Ticket',
		),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => false,
		'supports' => array('title'),
		'menu_icon' => 'dashicons-tickets-alt',
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));



	$category_labels = array(
		'name' => _x('Product Categories', 'taxonomy general name'),
		'singular_name' => _x('Product Category', 'taxonomy singular name'),
		'search_items' => __('Category Subjects'),
		'all_items' => __('All Categories'),
		'parent_item' => __('Parent Category'),
		'parent_item_colon' => __('Parent Category:'),
		'edit_item' => __('Edit Category'),
		'update_item' => __('Update Category'),
		'add_new_item' => __('Add New Category'),
		'new_item_name' => __('New Category Name'),
		'menu_name' => __('Category'),
	);

	// Now register the taxonomy
	register_taxonomy(
		'product_category',
		array('product'),
		array(
			'hierarchical' => true,
			'labels' => $category_labels,
			'show_ui' => true,
			'public' => true,
			'publicly_queryable' => true,
			'show_in_rest' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => array('slug' => 'category')
		)
	);

	$subscriber_role = get_role('subscriber');

	if ($subscriber_role) {
		$subscriber_role->add_cap('upload_files'); // Allow file uploads
	}

}
add_action('after_setup_theme', 'wholesale_setup');

function wholesale_hide_admin_bar_for_subscribers()
{
	if (!is_user_logged_in()) {
		return;
	}

	$user = wp_get_current_user();
	if (empty($user->roles) || !in_array('subscriber', (array) $user->roles, true)) {
		return;
	}

	show_admin_bar(false);
}
add_action('after_setup_theme', 'wholesale_hide_admin_bar_for_subscribers');

function wholesale_setting_defaults()
{
	return array(
		'primary_color' => '#1fa8de',
		'accent_color' => '#f5ad27',
		'sale_price_color' => '#1fa8de',
		'show_product_ratings' => 1,
		'show_product_reviews' => 1,
		'allow_verified_reviews' => 1,
		'auto_publish_reviews' => 0,
		'payment_disabled' => defined('WHOLESALE_PAYMENT_DISABLED') && WHOLESALE_PAYMENT_DISABLED ? 1 : 0,
		'payment_test_mode' => defined('WHOLESALE_CONVERGE_TEST_MODE') && WHOLESALE_CONVERGE_TEST_MODE ? 1 : 0,
		'merchant_id' => defined('WHOLESALE_CONVERGE_MERCHANT_ID') ? WHOLESALE_CONVERGE_MERCHANT_ID : '',
		'gateway_user_id' => defined('WHOLESALE_CONVERGE_USER_ID') ? WHOLESALE_CONVERGE_USER_ID : '',
		'gateway_pin' => defined('WHOLESALE_CONVERGE_PIN') ? WHOLESALE_CONVERGE_PIN : '',
		'tax_rate' => '10.3',
		'standard_shipping_options' => '12.5,50,62.5,75',
		'channel_shipping_options' => '50,200,250,300',
	);
}

function wholesale_get_setting($key)
{
	$defaults = wholesale_setting_defaults();
	$value = get_option('wholesale_' . $key, null);
	return null === $value && array_key_exists($key, $defaults) ? $defaults[$key] : $value;
}

function wholesale_setting_enabled($key)
{
	return (bool) wholesale_get_setting($key);
}

function wholesale_sanitize_hex_color($value)
{
	$sanitized = sanitize_hex_color($value);
	return $sanitized ? $sanitized : wholesale_setting_defaults()['primary_color'];
}

function wholesale_sanitize_decimal_list($value)
{
	$values = array_filter(array_map('trim', explode(',', (string) $value)), 'is_numeric');
	return implode(',', array_map(static function ($item) {
		return number_format((float) $item, 2, '.', '');
	}, $values));
}

function wholesale_register_settings()
{
	$defaults = wholesale_setting_defaults();
	foreach ($defaults as $key => $default) {
		register_setting('wholesale_settings', 'wholesale_' . $key, array(
			'type' => is_int($default) ? 'integer' : 'string',
			'sanitize_callback' => in_array($key, array('primary_color', 'accent_color', 'sale_price_color'), true)
				? 'wholesale_sanitize_hex_color'
				: ('tax_rate' === $key ? 'wholesale_sanitize_decimal' : ('standard_shipping_options' === $key || 'channel_shipping_options' === $key ? 'wholesale_sanitize_decimal_list' : 'sanitize_text_field')),
		));
	}
}
add_action('admin_init', 'wholesale_register_settings');

function wholesale_sanitize_decimal($value)
{
	return is_numeric($value) && (float) $value >= 0 ? number_format((float) $value, 2, '.', '') : '0.00';
}

function wholesale_settings_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Storefront Sign Settings</h1>
		<p>Manage the website defaults that can be updated immediately without editing theme files.</p>
		<form action="options.php" method="post">
			<?php settings_fields('wholesale_settings'); ?>
			<h2 class="title">Branding</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="wholesale_primary_color">Primary color</label></th><td><input type="color" id="wholesale_primary_color" name="wholesale_primary_color" value="<?php echo esc_attr(wholesale_get_setting('primary_color')); ?>"></td></tr>
				<tr><th><label for="wholesale_accent_color">Rating/accent color</label></th><td><input type="color" id="wholesale_accent_color" name="wholesale_accent_color" value="<?php echo esc_attr(wholesale_get_setting('accent_color')); ?>"></td></tr>
				<tr><th><label for="wholesale_sale_price_color">Sale price color</label></th><td><input type="color" id="wholesale_sale_price_color" name="wholesale_sale_price_color" value="<?php echo esc_attr(wholesale_get_setting('sale_price_color')); ?>"></td></tr>
			</table>
			<h2 class="title">Reviews</h2>
			<table class="form-table" role="presentation">
				<?php foreach (array(
					'show_product_ratings' => 'Show ratings on product cards',
					'show_product_reviews' => 'Show reviews on product pages',
					'allow_verified_reviews' => 'Allow reviews from customers with completed orders',
					'auto_publish_reviews' => 'Publish customer reviews automatically',
				) as $key => $label) : ?>
					<tr><th><?php echo esc_html($label); ?></th><td><input type="hidden" name="wholesale_<?php echo esc_attr($key); ?>" value="0"><label><input type="checkbox" name="wholesale_<?php echo esc_attr($key); ?>" value="1" <?php checked(wholesale_setting_enabled($key)); ?>> Enabled</label></td></tr>
				<?php endforeach; ?>
			</table>
			<h2 class="title">Payment gateway</h2>
			<table class="form-table" role="presentation">
				<tr><th>Disable card payment</th><td><input type="hidden" name="wholesale_payment_disabled" value="0"><label><input type="checkbox" name="wholesale_payment_disabled" value="1" <?php checked(wholesale_setting_enabled('payment_disabled')); ?>> Create orders for manual payment instead</label></td></tr>
				<tr><th>Test mode</th><td><input type="hidden" name="wholesale_payment_test_mode" value="0"><label><input type="checkbox" name="wholesale_payment_test_mode" value="1" <?php checked(wholesale_setting_enabled('payment_test_mode')); ?>> Use the Converge demo endpoint</label></td></tr>
				<tr><th><label for="wholesale_merchant_id">Merchant ID</label></th><td><input class="regular-text" id="wholesale_merchant_id" name="wholesale_merchant_id" value="<?php echo esc_attr(wholesale_get_setting('merchant_id')); ?>"></td></tr>
				<tr><th><label for="wholesale_gateway_user_id">Gateway user ID</label></th><td><input class="regular-text" id="wholesale_gateway_user_id" name="wholesale_gateway_user_id" value="<?php echo esc_attr(wholesale_get_setting('gateway_user_id')); ?>"></td></tr>
				<tr><th><label for="wholesale_gateway_pin">Gateway PIN</label></th><td><input type="password" class="regular-text" id="wholesale_gateway_pin" name="wholesale_gateway_pin" value="<?php echo esc_attr(wholesale_get_setting('gateway_pin')); ?>" autocomplete="new-password"></td></tr>
			</table>
			<h2 class="title">Checkout defaults</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="wholesale_tax_rate">Tax rate (%)</label></th><td><input type="number" min="0" step="0.01" id="wholesale_tax_rate" name="wholesale_tax_rate" value="<?php echo esc_attr(wholesale_get_setting('tax_rate')); ?>"></td></tr>
				<tr><th><label for="wholesale_standard_shipping_options">Standard shipping options</label></th><td><input class="regular-text" id="wholesale_standard_shipping_options" name="wholesale_standard_shipping_options" value="<?php echo esc_attr(wholesale_get_setting('standard_shipping_options')); ?>"><p class="description">Comma-separated amounts, from fastest to slowest.</p></td></tr>
				<tr><th><label for="wholesale_channel_shipping_options">Channel-letter shipping options</label></th><td><input class="regular-text" id="wholesale_channel_shipping_options" name="wholesale_channel_shipping_options" value="<?php echo esc_attr(wholesale_get_setting('channel_shipping_options')); ?>"><p class="description">Comma-separated amounts, from fastest to slowest.</p></td></tr>
			</table>
			<?php submit_button('Save settings'); ?>
		</form>
	</div>
	<?php
}

function wholesale_add_settings_page()
{
	add_options_page('Storefront Sign Settings', 'Storefront Sign', 'manage_options', 'wholesale-settings', 'wholesale_settings_page');
}
add_action('admin_menu', 'wholesale_add_settings_page');

function wholesale_newsletter_table_name()
{
	global $wpdb;

	return $wpdb->prefix . 'newsletter_emails';
}

function wholesale_newsletter_create_table()
{
	global $wpdb;

	$table_name = wholesale_newsletter_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		email VARCHAR(190) NOT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY email_unique (email)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}
add_action('init', 'wholesale_newsletter_create_table');
register_activation_hook(__FILE__, 'wholesale_newsletter_create_table');

function wholesale_newsletter_admin_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	global $wpdb;
	$table_name = wholesale_newsletter_table_name();
	$subscribers = $wpdb->get_results(
		"SELECT id, email, created_at FROM {$table_name} ORDER BY id DESC"
	);
	?>
	<div class="wrap">
		<h1>Newsletter Emails</h1>
		<p class="description"><?php echo esc_html(sprintf(_n('%d email saved', '%d emails saved', count($subscribers), 'litsign'), count($subscribers))); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Email</th>
					<th>Submitted</th>
				</tr>
			</thead>
			<tbody>
				<?php if (!empty($subscribers)) : ?>
					<?php foreach ($subscribers as $subscriber) : ?>
						<tr>
							<td><?php echo esc_html((string) $subscriber->id); ?></td>
							<td><?php echo esc_html($subscriber->email); ?></td>
							<td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($subscriber->created_at))); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="3">No newsletter emails have been saved yet.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function wholesale_newsletter_add_admin_page()
{
	add_menu_page(
		'Newsletter Emails',
		'Newsletter Emails',
		'manage_options',
		'wholesale-newsletter',
		'wholesale_newsletter_admin_page',
		'dashicons-email-alt',
		26
	);
}
add_action('admin_menu', 'wholesale_newsletter_add_admin_page');

function wholesale_newsletter_get_redirect_url($status)
{
	return add_query_arg('newsletter', $status, home_url('/'));
}

function wholesale_handle_newsletter_subscribe()
{
	if (!isset($_POST['newsletter_email']) || empty($_POST['newsletter_email'])) {
		wp_safe_redirect(wholesale_newsletter_get_redirect_url('invalid'));
		exit;
	}

	check_admin_referer('wholesale_newsletter_subscribe', 'newsletter_nonce');

	$email = sanitize_email(wp_unslash($_POST['newsletter_email']));
	$redirect_url = wholesale_newsletter_get_redirect_url('invalid');

	if (!is_email($email)) {
		wp_safe_redirect($redirect_url);
		exit;
	}

	global $wpdb;
	$table_name = wholesale_newsletter_table_name();
	$existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE email = %s LIMIT 1", $email));

	if (empty($existing)) {
		$inserted = $wpdb->insert(
			$table_name,
			array('email' => $email),
			array('%s')
		);

		if ($inserted === false) {
			wp_safe_redirect(wholesale_newsletter_get_redirect_url('error'));
			exit;
		}
		$redirect_url = wholesale_newsletter_get_redirect_url('success');
	} else {
		$redirect_url = wholesale_newsletter_get_redirect_url('exists');
	}

	wp_safe_redirect($redirect_url);
	exit;
}
add_action('admin_post_nopriv_wholesale_newsletter_subscribe', 'wholesale_handle_newsletter_subscribe');
add_action('admin_post_wholesale_newsletter_subscribe', 'wholesale_handle_newsletter_subscribe');

function wholesale_output_dynamic_settings()
{
	if (is_admin()) {
		return;
	}
	$primary = wholesale_get_setting('primary_color');
	$accent = wholesale_get_setting('accent_color');
	$sale = wholesale_get_setting('sale_price_color');
	?>
	<style id="wholesale-dynamic-settings">:root{--bs-primary:<?php echo esc_html($primary); ?>;--bs-link-color:<?php echo esc_html($primary); ?>;--bs-link-hover-color:<?php echo esc_html($primary); ?>}.text-primary,.product-rating-star.is-full,.product-rating-star.is-half{color:<?php echo esc_html($primary); ?>!important}.product-rating-star.is-full,.product-rating-star.is-half{color:<?php echo esc_html($accent); ?>!important}.pb-price span{color:<?php echo esc_html($sale); ?>!important}</style>
	<?php
}
add_action('wp_head', 'wholesale_output_dynamic_settings', 99);

/**
 * Send contact notifications to the configured site admin, administrators,
 * and the quote-response inbox.
 *
 * @return string[]
 */
function wholesale_contact_admin_recipients()
{
	$recipients = array(
		'mdikram295@gmail.com',
		'litsigntonight@gmail.com',
	);


	return array_values(array_unique(array_filter(array_map('sanitize_email', $recipients))));
}

/**
 * Process a public review submission.
 */
function wholesale_handle_review_submission()
{
	$redirect_url = wp_get_referer() ? wp_get_referer() : home_url('/');
	$review_anchor = !empty($_POST['review_product_id']) ? '#product-reviews' : '#feedbackModal';
	$nonce = isset($_POST['review_nonce']) ? sanitize_text_field(wp_unslash($_POST['review_nonce'])) : '';

	if (!wp_verify_nonce($nonce, 'submit_review')) {
		wp_safe_redirect(add_query_arg('review_status', 'error', $redirect_url) . $review_anchor);
		exit;
	}

	$name = isset($_POST['review_name']) ? sanitize_text_field(wp_unslash($_POST['review_name'])) : '';
	$email = isset($_POST['review_email']) ? sanitize_email(wp_unslash($_POST['review_email'])) : '';
	$rating = isset($_POST['review_rating']) ? absint($_POST['review_rating']) : 0;
	$review = isset($_POST['review_message']) ? sanitize_textarea_field(wp_unslash($_POST['review_message'])) : '';
	$product_id = isset($_POST['review_product_id']) ? absint($_POST['review_product_id']) : 0;
	$review_anchor = $product_id ? '#product-reviews' : '#feedbackModal';

	if (!$name || !is_email($email) || $rating < 1 || $rating > 5 || !$review) {
		wp_safe_redirect(add_query_arg('review_status', 'error', $redirect_url) . $review_anchor);
		exit;
	}

	if ($product_id) {
		if (!wholesale_setting_enabled('allow_verified_reviews')) {
			wp_safe_redirect(add_query_arg('review_status', 'disabled', $redirect_url) . $review_anchor);
			exit;
		}
		if (!is_user_logged_in() || !wholesale_user_can_review_product(get_current_user_id(), $product_id)) {
			wp_safe_redirect(add_query_arg('review_status', 'not_eligible', $redirect_url) . $review_anchor);
			exit;
		}
	}

	$submission_id = wp_insert_post(array(
		'post_type' => 'review_submission',
		'post_status' => wholesale_setting_enabled('auto_publish_reviews') ? 'publish' : 'pending',
		'post_title' => sprintf('%d-star review from %s', $rating, $name),
		'post_content' => $review,
		'meta_input' => array(
			'_review_name' => $name,
			'_review_email' => $email,
			'_review_rating' => $rating,
			'_review_product_id' => $product_id,
		),
	), true);

	if (is_wp_error($submission_id)) {
		wp_safe_redirect(add_query_arg('review_status', 'error', $redirect_url) . $review_anchor);
		exit;
	}

	$subject = sprintf('New %d-star customer review from %s', $rating, $name);
	$body = "Name: {$name}\nEmail: {$email}\nRating: {$rating}/5\n\nReview:\n{$review}\n";
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);
	wp_mail(wholesale_contact_admin_recipients(), $subject, $body, $headers);

	wp_safe_redirect(add_query_arg('review_status', 'sent', $redirect_url) . $review_anchor);
	exit;
}
add_action('admin_post_nopriv_submit_review', 'wholesale_handle_review_submission');
add_action('admin_post_submit_review', 'wholesale_handle_review_submission');

function wholesale_user_can_review_product($user_id, $product_id)
{
	$orders = get_posts(array(
		'post_type' => 'order',
		'post_status' => 'completed',
		'posts_per_page' => -1,
		'meta_key' => 'user_id',
		'meta_value' => absint($user_id),
	));

	foreach ($orders as $order) {
		$items = wholesale_decode_order_meta_array(get_post_meta($order->ID, 'product_json', true));
		foreach ($items as $item) {
			$details = isset($item['product_details']) && is_array($item['product_details']) ? $item['product_details'] : array();
			if (isset($details['Product Id']) && absint($details['Product Id']) === absint($product_id)) {
				return true;
			}
		}
	}

	return false;
}

function wholesale_product_review_data($product_id)
{
	$rating = min(5, max(0, (float) get_post_meta($product_id, '_product_review_rating', true)));
	$count = max(0, absint(get_post_meta($product_id, '_product_review_count', true)));
	$text = trim((string) get_post_meta($product_id, '_product_review_text', true));

	return array(
		'rating' => $rating,
		'count' => $count,
		'text' => $text,
	);
}

function wholesale_render_product_rating($product_id, $class = '')
{
	if (!wholesale_setting_enabled('show_product_ratings')) {
		return;
	}
	$review_data = wholesale_product_review_data($product_id);
	if (!$review_data['rating'] && !$review_data['count']) {
		return;
	}

	$label = sprintf('%s out of 5 stars from %s reviews', number_format($review_data['rating'], 1), number_format_i18n($review_data['count']));
	echo '<div class="product-rating ' . esc_attr($class) . '" aria-label="' . esc_attr($label) . '">';
	echo '<span class="product-rating-stars" aria-hidden="true">';
	for ($star = 1; $star <= 5; $star++) {
		$state = $star <= floor($review_data['rating']) ? 'is-full' : (($star - 0.5) <= $review_data['rating'] ? 'is-half' : '');
		echo '<span class="product-rating-star ' . esc_attr($state) . '">&#9733;</span>';
	}
	echo '</span><span class="product-rating-value">' . esc_html(number_format($review_data['rating'], 1)) . '</span>';
	echo '<span class="product-rating-count">(' . esc_html(number_format_i18n($review_data['count'])) . ' reviews)</span>';
	echo '</div>';
}

function wholesale_review_submission_columns($columns)
{
	return array(
		'cb' => isset($columns['cb']) ? $columns['cb'] : '<input type="checkbox">',
		'title' => 'Review',
		'review_name' => 'Name',
		'review_email' => 'Email',
		'review_rating' => 'Rating',
		'review_product' => 'Product',
		'date' => 'Received',
	);
}
add_filter('manage_review_submission_posts_columns', 'wholesale_review_submission_columns');

function wholesale_review_submission_column_content($column, $post_id)
{
	$meta_keys = array(
		'review_name' => '_review_name',
		'review_email' => '_review_email',
		'review_rating' => '_review_rating',
		'review_product' => '_review_product_id',
	);

	if (!isset($meta_keys[$column])) {
		return;
	}

	$value = get_post_meta($post_id, $meta_keys[$column], true);

	if ('review_email' === $column && is_email($value)) {
		printf('<a href="mailto:%s">%s</a>', esc_attr($value), esc_html($value));
		return;
	}

	if ('review_rating' === $column) {
		echo esc_html($value . '/5');
		return;
	}

	if ('review_product' === $column) {
		$product_title = $value ? get_the_title(absint($value)) : '';
		echo esc_html($product_title ?: 'General feedback');
		return;
	}

	echo esc_html($value);
}
add_action('manage_review_submission_posts_custom_column', 'wholesale_review_submission_column_content', 10, 2);

function wholesale_review_submission_details_meta_box($post)
{
	$fields = array(
		'Name' => '_review_name',
		'Email' => '_review_email',
		'Rating' => '_review_rating',
		'Product ID' => '_review_product_id',
	);

	echo '<table class="widefat striped"><tbody>';
	foreach ($fields as $label => $meta_key) {
		$value = get_post_meta($post->ID, $meta_key, true);
		echo '<tr><td><strong>' . esc_html($label) . '</strong></td><td>';
		if ('_review_email' === $meta_key && is_email($value)) {
			printf('<a href="mailto:%s">%s</a>', esc_attr($value), esc_html($value));
		} elseif ('_review_rating' === $meta_key) {
			echo esc_html($value . '/5');
		} elseif ('_review_product_id' === $meta_key) {
			echo esc_html($value ? (get_the_title(absint($value)) ?: 'Product #' . absint($value)) : 'General feedback');
		} else {
			echo esc_html($value);
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

function wholesale_review_submission_register_meta_box()
{
	add_meta_box(
		'review-submission-details',
		'Review Details',
		'wholesale_review_submission_details_meta_box',
		'review_submission',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes_review_submission', 'wholesale_review_submission_register_meta_box');

function wholesale_contact_submission_columns($columns)
{
	return array(
		'cb' => isset($columns['cb']) ? $columns['cb'] : '<input type="checkbox">',
		'title' => 'Submission',
		'contact_business' => 'Business',
		'contact_email' => 'Email',
		'contact_project_type' => 'Project type',
		'date' => 'Received',
	);
}
add_filter('manage_contact_submission_posts_columns', 'wholesale_contact_submission_columns');

function wholesale_contact_submission_column_content($column, $post_id)
{
	$meta_keys = array(
		'contact_business' => '_contact_business',
		'contact_email' => '_contact_email',
		'contact_project_type' => '_contact_project_type',
	);

	if (isset($meta_keys[$column])) {
		echo esc_html(get_post_meta($post_id, $meta_keys[$column], true));
	}
}
add_action('manage_contact_submission_posts_custom_column', 'wholesale_contact_submission_column_content', 10, 2);

function wholesale_contact_submission_details_meta_box($post)
{
	$fields = array(
		'Name' => '_contact_name',
		'Business' => '_contact_business',
		'Phone' => '_contact_phone',
		'Email' => '_contact_email',
		'Project type' => '_contact_project_type',
	);

	echo '<table class="widefat striped"><tbody>';
	foreach ($fields as $label => $meta_key) {
		$value = get_post_meta($post->ID, $meta_key, true);
		echo '<tr><td><strong>' . esc_html($label) . '</strong></td><td>';
		if ('_contact_email' === $meta_key && is_email($value)) {
			echo '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
		} elseif ('_contact_phone' === $meta_key) {
			echo '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $value)) . '">' . esc_html($value) . '</a>';
		} else {
			echo esc_html($value);
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

function wholesale_contact_submission_register_meta_box()
{
	add_meta_box(
		'contact-submission-details',
		'Contact Details',
		'wholesale_contact_submission_details_meta_box',
		'contact_submission',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes_contact_submission', 'wholesale_contact_submission_register_meta_box');

/**
 * Keep conversion tracking, but wait until the page has loaded before fetching it.
 */
function wholesale_deferred_conversion_tracking()
{
	?>
	<script>
		window.addEventListener('load', function () {
			var script = document.createElement('script');
			script.async = true;
			script.src = 'https://www.googletagmanager.com/gtag/js?id=AW-18454059893';
			document.head.appendChild(script);
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function () {
				window.dataLayer.push(arguments);
			};
			window.gtag('js', new Date());
			window.gtag('config', 'AW-18454059893');
			window.gtag('config', 'AW-18454059893/OK42COLkgPocEPW2yt9E', {
				'phone_conversion_number': '866-436-2101'
			});
		});
	</script>
	<?php
}
add_action('wp_footer', 'wholesale_deferred_conversion_tracking', 20);

/**
 * Do not let the GoDaddy widget become a parser-blocking request.
 */
function wholesale_defer_wsimg_script($tag, $handle, $src)
{
	if (false === strpos($src, 'img1.wsimg.com')) {
		return $tag;
	}

	return str_replace(' src=', ' defer src=', $tag);
}
add_filter('script_loader_tag', 'wholesale_defer_wsimg_script', 10, 3);

/**
 * Remove front-end WordPress features that are not used by this theme.
 *
 * These are otherwise extra requests and inline payload on every public page.
 */
function wholesale_trim_frontend_overhead()
{
	if (is_admin()) {
		return;
	}

	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
	remove_action('wp_head', 'wp_oembed_add_host_js');
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'wp_shortlink_wp_head');

	if (!is_user_logged_in()) {
		wp_deregister_style('dashicons');
	}
}
add_action('init', 'wholesale_trim_frontend_overhead');

/**
 * Make the CSS hero image discoverable while the stylesheet is loading.
 */
function wholesale_preload_front_page_hero()
{
	if (is_front_page()) {
		// Browsers without AVIF support skip this preload and use the WebP from style.css.
		printf(
			'<link rel="preload" as="image" href="%s" type="image/avif" fetchpriority="high">' . "\n",
			esc_url(get_template_directory_uri() . '/img/hero-1920.avif')
		);
	}
}
add_action('wp_head', 'wholesale_preload_front_page_hero', 1);

/**
 * Return a WebP copy of an uploaded JPEG/PNG, creating it next to the original
 * on first use. Falls back to the original URL when conversion is unavailable.
 */
function wholesale_webp_url($url)
{
	static $cache = array();

	if (!$url || !preg_match('/\.(png|jpe?g)$/i', wp_parse_url($url, PHP_URL_PATH) ?? '')) {
		return $url;
	}
	if (isset($cache[$url])) {
		return $cache[$url];
	}

	$uploads = wp_get_upload_dir();
	$base_path = wp_parse_url($uploads['baseurl'], PHP_URL_PATH);
	$url_path = wp_parse_url($url, PHP_URL_PATH);
	if (!$base_path || 0 !== strpos($url_path, $base_path . '/')) {
		return $cache[$url] = $url;
	}

	$file = $uploads['basedir'] . substr($url_path, strlen($base_path));
	$webp_file = $file . '.webp';
	$webp_url = preg_replace('/(\.(png|jpe?g))(\?.*)?$/i', '$1.webp', $url);

	if (file_exists($webp_file) && filemtime($webp_file) >= filemtime($file)) {
		return $cache[$url] = $webp_url;
	}
	if (!file_exists($file) || !function_exists('imagewebp') || filesize($file) > 8 * MB_IN_BYTES) {
		return $cache[$url] = $url;
	}

	$image = preg_match('/\.png$/i', $file) ? @imagecreatefrompng($file) : @imagecreatefromjpeg($file);
	if (!$image) {
		return $cache[$url] = $url;
	}
	imagepalettetotruecolor($image);
	imagealphablending($image, false);
	imagesavealpha($image, true);
	$saved = imagewebp($image, $webp_file, 80);
	imagedestroy($image);

	// Keep the original when WebP is not actually smaller.
	if (!$saved || filesize($webp_file) >= filesize($file)) {
		@unlink($webp_file);
		return $cache[$url] = $url;
	}

	return $cache[$url] = $webp_url;
}

function wholesale_webp_srcset($srcset)
{
	return preg_replace_callback('/(\S+)(\s+\d+[wx])/', function ($matches) {
		return wholesale_webp_url($matches[1]) . $matches[2];
	}, $srcset);
}

function wholesale_webp_attachment_attributes($attr)
{
	if (is_admin()) {
		return $attr;
	}
	if (!empty($attr['src'])) {
		$attr['src'] = wholesale_webp_url($attr['src']);
	}
	if (!empty($attr['srcset'])) {
		$attr['srcset'] = wholesale_webp_srcset($attr['srcset']);
	}

	return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'wholesale_webp_attachment_attributes', 20);

/**
 * Print the theme's tiny stylesheets inline to save render-blocking requests.
 */
function wholesale_inline_small_styles($tag, $handle)
{
	$files = array(
		'litsign-style' => '/style.css',
		'font-awesome' => '/css/icons.css',
	);

	if (is_admin() || is_rtl() || !isset($files[$handle])) {
		return $tag;
	}

	$css = @file_get_contents(get_template_directory() . $files[$handle]);
	if (false === $css) {
		return $tag;
	}

	$css = preg_replace('#/\*.*?\*/#s', '', $css);
	$css = str_replace('url("../', 'url("' . get_template_directory_uri() . '/', $css);

	return '<style id="' . esc_attr($handle) . '-inline-css">' . trim($css) . "</style>\n";
}
add_filter('style_loader_tag', 'wholesale_inline_small_styles', 10, 2);

/**
 * Keep render-blocking JavaScript out of the <head> on the public site.
 *
 * The social icons plugin loads jQuery UI, Modernizr and Shuffle on every page
 * although the theme never outputs its icons, and jQuery itself only needs to
 * be ready before the footer scripts that depend on it.
 */
function wholesale_optimize_frontend_scripts()
{
	if (is_admin() || is_customize_preview()) {
		return;
	}

	foreach (array('SFSIjqueryModernizr', 'SFSIjqueryShuffle', 'SFSIjqueryrandom-shuffle', 'SFSICustomJs', 'SFSIPLUSjqueryModernizr') as $handle) {
		wp_dequeue_script($handle);
	}
	wp_dequeue_style('SFSImainCss');

	// single.php has inline scripts that expect jQuery to be loaded already.
	if (is_singular('post')) {
		return;
	}

	$scripts = wp_scripts();
	$scripts->remove('jquery');
	$scripts->add('jquery', false, array('jquery-core'), $scripts->registered['jquery-core']->ver);
	foreach (array('jquery', 'jquery-core', 'jquery-ui-core') as $handle) {
		if (isset($scripts->registered[$handle])) {
			$scripts->add_data($handle, 'group', 1);
		}
	}
}
add_action('wp_enqueue_scripts', 'wholesale_optimize_frontend_scripts', 100);

/**
 * Expose product categories as clean URLs while keeping the
 * existing shop page and its category query compatible.
 */
function wholesale_category_rewrites()
{
	add_rewrite_rule('^thank-you/?$', 'index.php?wholesale_thank_you=1', 'top');

	$terms = get_terms(array(
		'taxonomy' => 'product_category',
		'hide_empty' => false,
		'fields' => 'slugs',
	));

	if (is_wp_error($terms)) {
		return;
	}

	foreach ($terms as $term_slug) {
		add_rewrite_rule(
			'^' . preg_quote($term_slug, '/') . '/?$',
			'index.php?category_slug=' . $term_slug,
			'top'
		);
	}
}
add_action('init', 'wholesale_category_rewrites', 20);
function wholesale_refresh_category_rewrites()
{
	$rewrite_version = 'category-root-v2';

	if ($rewrite_version === get_option('wholesale_category_rewrite_version')) {
		return;
	}

	flush_rewrite_rules(false);
	update_option('wholesale_category_rewrite_version', $rewrite_version);
}
add_action('init', 'wholesale_refresh_category_rewrites', 21);
add_action('after_switch_theme', function () {
	wholesale_category_rewrites();
	wholesale_sitemap_rewrite();
	flush_rewrite_rules();
});

function wholesale_category_query_var($vars)
{
	$vars[] = 'category_slug';
	$vars[] = 'wholesale_thank_you';
	return $vars;
}
add_filter('query_vars', 'wholesale_category_query_var');

function wholesale_category_template($template)
{
	if (get_query_var('wholesale_thank_you')) {
		return locate_template('thank-you.php');
	}

	$term_slug = get_query_var('category_slug');

	if ($term_slug && get_term_by('slug', $term_slug, 'product_category')) {
		return locate_template('home.php');
	}

	return $template;
}
add_filter('template_include', 'wholesale_category_template');

function wholesale_category_url($term_slug)
{
	return trailingslashit(home_url(sanitize_title($term_slug)));
}

function wholesale_normalize_category_menu_urls($items, $args)
{
	if (empty($args->theme_location) || 'category-filter-menu' !== $args->theme_location) {
		return $items;
	}

	foreach ($items as $item) {
		$query = wp_parse_url($item->url, PHP_URL_QUERY);
		parse_str((string) $query, $query_vars);

		if (empty($query_vars['category_slug'])) {
			continue;
		}

		$term = get_term_by('slug', sanitize_title($query_vars['category_slug']), 'product_category');
		if ($term && !is_wp_error($term)) {
			$item->url = wholesale_category_url($term->slug);
		}
	}

	return $items;
}
add_filter('wp_nav_menu_objects', 'wholesale_normalize_category_menu_urls', 10, 2);

function wholesale_category_redirect()
{
	if (!empty($_GET['category_slug'])) {
		$term_slug = sanitize_title(wp_unslash($_GET['category_slug']));
		$term = get_term_by('slug', $term_slug, 'product_category');

		if ($term && !is_wp_error($term)) {
			wp_safe_redirect(wholesale_category_url($term->slug), 301);
			exit;
		}
	}

	// The core taxonomy archive duplicates the clean /{category}/ route.
	if (is_tax('product_category')) {
		$term = get_queried_object();

		if ($term instanceof WP_Term) {
			wp_safe_redirect(wholesale_category_url($term->slug), 301);
			exit;
		}
	}
}
add_action('template_redirect', 'wholesale_category_redirect', 1);

add_filter('the_generator', '__return_empty_string');

/**
 * Register the public XML sitemap endpoint.
 */
function wholesale_sitemap_rewrite()
{
	add_rewrite_rule('^sitemap\.xml$', 'index.php?wholesale_sitemap=1', 'top');
}
add_action('init', 'wholesale_sitemap_rewrite', 20);
add_action('init', function () {
	if ('2' !== get_option('wholesale_sitemap_rewrite_version')) {
		flush_rewrite_rules(false);
		update_option('wholesale_sitemap_rewrite_version', '2');
	}
}, 99);
add_filter('query_vars', function ($vars) {
	$vars[] = 'wholesale_sitemap';
	return $vars;
});

/**
 * Render a dynamic sitemap containing only public, indexable URLs.
 */
function wholesale_render_sitemap()
{
	if ('1' !== get_query_var('wholesale_sitemap')) {
		return;
	}

	$urls = array();
	$add_url = function ($url, $lastmod = '', $changefreq = '', $priority = '') use (&$urls) {
		$url = esc_url_raw($url);
		if (!$url || isset($urls[$url])) {
			return;
		}

		$urls[$url] = array(
			'loc' => $url,
			'lastmod' => $lastmod,
			'changefreq' => $changefreq,
			'priority' => $priority,
		);
	};

	$last_modified = get_lastpostmodified('gmt');
	$last_modified = $last_modified ? mysql2date('c', $last_modified, true) : '';
	$add_url(home_url('/'), $last_modified, 'daily', '1.0');

	$private_pages = wholesale_seo_noindex_page_slugs();
	$page_query = new WP_Query(array(
		'post_type' => array('page', 'post', 'product'),
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'modified',
		'order' => 'DESC',
		'no_found_rows' => true,
		'ignore_sticky_posts' => true,
	));

	foreach ($page_query->posts as $post) {
		if ('page' === $post->post_type && in_array($post->post_name, $private_pages, true)) {
			continue;
		}

		$url = get_permalink($post);
		if ($url) {
			$add_url($url, get_post_modified_time('c', true, $post), 'weekly', '0.8');
		}
	}

	$terms = get_terms(array(
		'taxonomy' => 'product_category',
		'hide_empty' => true,
	));

	if (!is_wp_error($terms)) {
		foreach ($terms as $term) {
			// The channel-letter route canonicalizes to the home page.
			if ('channel-letters' === $term->slug) {
				continue;
			}

			$term_url = wholesale_category_url($term->slug);
			$add_url($term_url, '', 'weekly', '0.7');
		}
	}

	nocache_headers();
	header('Content-Type: application/xml; charset=UTF-8');
	status_header(200);

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	foreach ($urls as $entry) {
		echo "\t<url>\n";
		echo "\t\t<loc>" . esc_xml($entry['loc']) . "</loc>\n";
		if ($entry['lastmod']) {
			echo "\t\t<lastmod>" . esc_xml($entry['lastmod']) . "</lastmod>\n";
		}
		if ($entry['changefreq']) {
			echo "\t\t<changefreq>" . esc_xml($entry['changefreq']) . "</changefreq>\n";
		}
		if ($entry['priority']) {
			echo "\t\t<priority>" . esc_xml($entry['priority']) . "</priority>\n";
		}
		echo "\t</url>\n";
	}

	echo '</urlset>';
	exit;
}
add_action('template_redirect', 'wholesale_render_sitemap', 0);

/**
 * Keep core's dynamic sitemap focused on indexable products and categories.
 */
function wholesale_sitemap_post_types($post_types)
{
	unset($post_types['cnn'], $post_types['order']);
	return $post_types;
}
add_filter('wp_sitemaps_post_types', 'wholesale_sitemap_post_types');

function wholesale_sitemap_taxonomies($taxonomies)
{
	return $taxonomies;
}
add_filter('wp_sitemaps_taxonomies', 'wholesale_sitemap_taxonomies');

function wholesale_sitemap_excluded_page_ids($args, $post_type)
{
	if ('page' !== $post_type) {
		return $args;
	}

	$private_pages = wholesale_seo_noindex_page_slugs();
	$excluded_ids = array();

	foreach ($private_pages as $slug) {
		$page = get_page_by_path($slug);
		if ($page) {
			$excluded_ids[] = $page->ID;
		}
	}

	if ($excluded_ids) {
		$args['post__not_in'] = $excluded_ids;
	}

	return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'wholesale_sitemap_excluded_page_ids', 10, 2);

function register_order_post_statuses()
{
	$statuses = array(
		'on-hold' => 'On hold',
		'processing' => 'Processing',
		'completed' => 'Completed',
		'cancelled' => 'Cancelled',
		'refunded' => 'Refunded',
		'failed' => 'Failed',
		'on_hold' => 'On hold',
	);

	foreach ($statuses as $status => $label) {
		register_post_status($status, array(
			'label' => _x($label, 'post'),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop("$label <span class='count'>(%s)</span>", "$label <span class='count'>(%s)</span>"),
		));
	}
}
add_action('init', 'register_order_post_statuses');

/**
 * Orders hold customer names, emails, phones and addresses. Only the customer who placed an
 * order (or staff who can edit it) may view it on the front end.
 */
function wholesale_protect_order_pages()
{
	if (is_post_type_archive('order')) {
		wholesale_render_404();
	}

	if (!is_singular('order')) {
		return;
	}

	$order_post_id = get_queried_object_id();
	if (current_user_can('edit_post', $order_post_id)) {
		return;
	}

	if (!is_user_logged_in()) {
		wp_safe_redirect(add_query_arg('redirect_ulr', rawurlencode(get_permalink($order_post_id)), home_url('/login/')));
		exit;
	}

	$order_owner = absint(get_post_meta($order_post_id, 'user_id', true));
	if ($order_owner && $order_owner === get_current_user_id()) {
		return;
	}

	wholesale_render_404();
}
add_action('template_redirect', 'wholesale_protect_order_pages', 5);

function wholesale_render_404()
{
	global $wp_query;

	$wp_query->set_404();
	status_header(404);
	nocache_headers();
	include get_query_template('404');
	exit;
}

/**
 * Keep orders out of the public REST API; staff still use it through the block editor.
 */
function wholesale_protect_order_rest_routes($response, $handler, $request)
{
	if (0 === strpos($request->get_route(), '/wp/v2/order') && !current_user_can('edit_posts')) {
		return new WP_Error('rest_forbidden', __('Sorry, you are not allowed to view orders.', 'litsign'), array('status' => rest_authorization_required_code()));
	}

	return $response;
}
add_filter('rest_request_before_callbacks', 'wholesale_protect_order_rest_routes', 10, 3);

function wholesale_decode_order_meta_array($value)
{
	if (is_array($value)) {
		return $value;
	}

	if (!is_string($value) || $value === '') {
		return array();
	}

	foreach (array($value, wp_unslash($value)) as $candidate) {
		$decoded = json_decode($candidate, true);
		if (is_array($decoded)) {
			return $decoded;
		}

		if (is_string($decoded)) {
			$decoded = json_decode($decoded, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
	}

	$unserialized = maybe_unserialize($value);
	return is_array($unserialized) ? $unserialized : array();
}

function wholesale_format_order_detail_value($name, $value)
{
	if (!is_scalar($value)) {
		return '';
	}

	$value = str_replace('u201d', '”', (string) $value);
	$link_fields = array('Design Url', 'My Artwork');

	if (in_array((string) $name, $link_fields, true)) {
		$url = '';
		if (preg_match('/href\s*=\s*[\'"]([^\'"]+)[\'"]/i', $value, $matches)) {
			$url = $matches[1];
		} elseif (filter_var(trim($value), FILTER_VALIDATE_URL)) {
			$url = trim($value);
		}

		if ($url) {
			return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">Open</a>';
		}
	}

	return esc_html($value);
}

function wholesale_send_new_order_admin_email($post_id)
{
	if ('order' !== get_post_type($post_id)) {
		return;
	}

	$billing_data = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));

	$order_id = get_post_meta($post_id, 'order_id', true);
	$order_cost = json_decode(get_post_meta($post_id, 'product_cost', true), true);
	$order_cost = is_array($order_cost) ? $order_cost : array();
	$product_data = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_json', true));
	$order_comment = get_post_meta($post_id, 'order_comment', true);
	$order_time = get_post_meta($post_id, 'order_time', true);
	$estimate_delivery_time = get_post_meta($post_id, 'estimate_delivery_time', true);
	$order_total = isset($order_cost['grand_total']) ? floatval($order_cost['grand_total']) : 0;
	$customer_email = isset($billing_data['billing_email']) ? $billing_data['billing_email'] : '';
	$customer_name = trim((isset($billing_data['billing_fname']) ? $billing_data['billing_fname'] : '') . ' ' . (isset($billing_data['billing_lname']) ? $billing_data['billing_lname'] : ''));
	$shipping_data = wholesale_decode_order_meta_array(get_post_meta($post_id, 'shipping_address', true));

	$subject = sprintf('New Order Received - #%s', $order_id ?: $post_id);
	$message = '<html><body>'
		. '<h2>New Order Notification</h2>'
		. '<p>A new order has been received on your website.</p>'
		. '<p><strong>Order ID:</strong> #' . esc_html($order_id ?: $post_id) . '</p>'
		. '<p><strong>Customer:</strong> ' . esc_html($customer_name ?: 'N/A') . '<br>'
		. '<strong>Email:</strong> ' . esc_html($customer_email ?: 'N/A') . '<br>'
		. '<strong>Order Time:</strong> ' . esc_html($order_time ?: 'N/A') . '<br>'
		. '<strong>Estimated Delivery:</strong> ' . esc_html($estimate_delivery_time ?: 'N/A') . '</p>'
		. '<h3>Products</h3>'
		. '<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;">'
		. '<thead><tr><th align="left">Product</th><th>Quantity</th><th align="right">Unit Price</th><th align="right">Total</th><th align="left">Details</th></tr></thead><tbody>';

	foreach ($product_data as $product) {
		$quantity = isset($product['product_quantity']) ? intval($product['product_quantity']) : 0;
		$subtotal = isset($product['product_subtotal']) ? floatval($product['product_subtotal']) : 0;
		$unit_price = $quantity > 0 ? $subtotal / $quantity : $subtotal;
		$details = array();

		if (!empty($product['job_name'])) {
			$details[] = '<strong>Job Name:</strong> ' . esc_html($product['job_name']);
		}

		if (!empty($product['product_details']) && is_array($product['product_details'])) {
			foreach ($product['product_details'] as $name => $value) {
				if ($value !== null && $value !== '') {
					$details[] = '<strong>' . esc_html($name) . ':</strong> ' . wholesale_format_order_detail_value($name, $value);
				}
			}
		}

		$message .= '<tr><td>' . esc_html(isset($product['product_title']) ? $product['product_title'] : 'N/A') . '</td>'
			. '<td align="center">' . esc_html($quantity) . '</td>'
			. '<td align="right">$' . number_format($unit_price, 2, '.', ',') . '</td>'
			. '<td align="right">$' . number_format($subtotal, 2, '.', ',') . '</td>'
			. '<td>' . implode('<br>', $details ?: array('N/A')) . '</td></tr>';
	}

	$message .= '</tbody></table>'
		. '<h3>Order Details</h3>'
		. '<p><strong>Subtotal:</strong> $' . number_format(isset($order_cost['sub_total']) ? floatval($order_cost['sub_total']) : 0, 2, '.', ',') . '<br>'
		. '<strong>Shipping:</strong> $' . number_format(isset($order_cost['shipping_cost']) ? floatval($order_cost['shipping_cost']) : 0, 2, '.', ',') . '<br>'
		. '<strong>Tax:</strong> $' . number_format(isset($order_cost['tax']) ? floatval($order_cost['tax']) : 0, 2, '.', ',') . '<br>'
		. '<strong>Grand Total:</strong> $' . number_format($order_total, 2, '.', ',') . '</p>'
		. '<h3>Billing Details</h3><p>' . wholesale_format_order_email_data($billing_data, 'billing_') . '</p>'
		. '<h3>Shipping Details</h3><p>' . wholesale_format_order_email_data($shipping_data, 'shipping_') . '</p>'
		. '<h3>Comment</h3><p>' . esc_html($order_comment ?: 'N/A') . '</p>'
		. '<p><a href="' . esc_url(get_permalink($post_id)) . '" target="_blank">View Order Details</a></p>'
		. '</body></html>';

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . get_option('admin_email'),
	);

	if (!get_post_meta($post_id, '_wholesale_admin_order_email_sent', true)) {
		$admin_sent = wp_mail(wholesale_contact_admin_recipients(), $subject, $message, $headers);
		if ($admin_sent) {
			update_post_meta($post_id, '_wholesale_admin_order_email_sent', current_time('mysql'));
		} else {
			error_log('Wholesale order admin email failed for order #' . ($order_id ?: $post_id));
		}
	}

	if (is_email($customer_email) && !get_post_meta($post_id, '_wholesale_customer_order_email_sent', true)) {
		$customer_subject = sprintf(__('Your order #%s has been received', 'litsign'), $order_id ?: $post_id);
		$customer_message = '<html><body>'
			. '<p>' . esc_html(sprintf(__('Thank you%s for your order.', 'litsign'), $customer_name ? ' ' . $customer_name : '')) . '</p>'
			. '<p>' . esc_html__('We have received your order and will begin processing it shortly.', 'litsign') . '</p>'
			. '<p><strong>' . esc_html__('Order number:', 'litsign') . '</strong> #' . esc_html($order_id ?: $post_id) . '<br>'
			. '<strong>' . esc_html__('Order total:', 'litsign') . '</strong> $' . number_format($order_total, 2, '.', ',') . '</p>'
			. '</body></html>';
		$customer_sent = wp_mail($customer_email, $customer_subject, $customer_message, $headers);
		if ($customer_sent) {
			update_post_meta($post_id, '_wholesale_customer_order_email_sent', current_time('mysql'));
		} else {
			error_log('Wholesale order customer email failed for order #' . ($order_id ?: $post_id));
		}
	}
}

function wholesale_format_order_email_data($data, $prefix)
{
	$formatted_data = array();

	foreach ($data as $key => $value) {
		if ($value === null || $value === '') {
			continue;
		}

		$label = ucwords(str_replace('_', ' ', preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $key)));
		$formatted_data[] = '<strong>' . esc_html($label) . ':</strong> ' . esc_html($value);
	}

	return $formatted_data ? implode('<br>', $formatted_data) : 'N/A';
}

/**
 * Add the most useful order information to the admin order list.
 */
function wholesale_order_admin_columns($columns)
{
	$custom_columns = array(
		'order_number' => __('Order #', 'litsign'),
		'order_status' => __('Status', 'litsign'),
		'customer' => __('Customer', 'litsign'),
		'contact' => __('Contact', 'litsign'),
		'items' => __('Items', 'litsign'),
		'order_total' => __('Total', 'litsign'),
		'delivery_date' => __('Delivery', 'litsign'),
	);

	unset($columns['title']);

	$updated_columns = array();
	foreach ($columns as $key => $label) {
		$updated_columns[$key] = $label;

		if ('cb' === $key) {
			$updated_columns = array_merge($updated_columns, $custom_columns);
		}
	}

	return $updated_columns;
}
add_filter('manage_order_posts_columns', 'wholesale_order_admin_columns');

function wholesale_order_admin_column_content($column, $post_id)
{
	$billing_data = wholesale_decode_order_meta_array(get_post_meta($post_id, 'billing_address', true));
	$order_cost = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_cost', true));
	$product_data = wholesale_decode_order_meta_array(get_post_meta($post_id, 'product_json', true));

	switch ($column) {
		case 'order_number':
			$order_number = get_post_meta($post_id, 'order_id', true);
			printf(
				'<a href="%s"><strong>#%s</strong></a>',
				esc_url(get_edit_post_link($post_id)),
				esc_html($order_number ?: $post_id)
			);
			break;

		case 'order_status':
			$status_labels = array(
				'pending' => __('Pending Review', 'litsign'),
				'on-hold' => __('On hold', 'litsign'),
				'processing' => __('Processing', 'litsign'),
				'completed' => __('Completed', 'litsign'),
				'cancelled' => __('Cancelled', 'litsign'),
				'refunded' => __('Refunded', 'litsign'),
				'failed' => __('Failed', 'litsign'),
				'on_hold' => __('On hold', 'litsign'),
			);
			$status = get_post_status($post_id);
			$status_label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst(str_replace('_', ' ', $status));
			printf(
				'<span class="wholesale-order-status" data-status="%s">%s</span>',
				esc_attr($status),
				esc_html($status_label)
			);
			break;

		case 'customer':
			$name = trim(
				(isset($billing_data['billing_fname']) ? $billing_data['billing_fname'] : '')
				. ' '
				. (isset($billing_data['billing_lname']) ? $billing_data['billing_lname'] : '')
			);
			echo esc_html($name ?: __('Guest', 'litsign'));
			break;

		case 'contact':
			$email = isset($billing_data['billing_email']) ? sanitize_email($billing_data['billing_email']) : '';
			$phone = isset($billing_data['billing_tel']) ? $billing_data['billing_tel'] : '';

			if ($email) {
				printf('<a href="mailto:%s">%s</a>', esc_attr($email), esc_html($email));
			}
			if ($phone) {
				printf('<br><span>%s</span>', esc_html($phone));
			}
			if (!$email && !$phone) {
				echo '&mdash;';
			}
			break;

		case 'items':
			$item_count = 0;
			$product_names = array();

			foreach ($product_data as $product) {
				$quantity = isset($product['product_quantity']) ? absint($product['product_quantity']) : 0;
				$item_count += $quantity;

				if (!empty($product['product_title'])) {
					$product_names[] = $product['product_title'] . ' x ' . $quantity;
				}
			}

			printf(
				'<span title="%s">%s item%s</span>',
				esc_attr(implode(', ', $product_names)),
				esc_html($item_count),
				1 === $item_count ? '' : 's'
			);
			break;

		case 'order_total':
			$total = isset($order_cost['grand_total']) ? floatval($order_cost['grand_total']) : 0;
			echo esc_html('$' . number_format($total, 2, '.', ','));
			break;

		case 'delivery_date':
			$delivery_date = get_post_meta($post_id, 'estimate_delivery_time', true);
			echo $delivery_date ? esc_html($delivery_date) : '&mdash;';
			break;
	}
}
add_action('manage_order_posts_custom_column', 'wholesale_order_admin_column_content', 10, 2);

/**
 * Add all supported order statuses to the order list quick edit form.
 */
function wholesale_order_quick_edit_status($column_name, $post_type)
{
	if ('order' !== $post_type || 'order_status' !== $column_name) {
		return;
	}

	$statuses = array(
		'pending' => __('Pending Review', 'litsign'),
		'on-hold' => __('On hold', 'litsign'),
		'processing' => __('Processing', 'litsign'),
		'completed' => __('Completed', 'litsign'),
		'cancelled' => __('Cancelled', 'litsign'),
		'refunded' => __('Refunded', 'litsign'),
		'failed' => __('Failed', 'litsign'),
		'on_hold' => __('On hold (legacy)', 'litsign'),
	);
	?>
	<fieldset class="inline-edit-col-right">
		<div class="inline-edit-col">
			<label>
				<span class="title"><?php esc_html_e('Order status', 'litsign'); ?></span>
				<select name="order_status">
					<?php foreach ($statuses as $status => $label) : ?>
						<option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
	</fieldset>
	<?php
}
add_action('quick_edit_custom_box', 'wholesale_order_quick_edit_status', 10, 2);


function add_custom_status_to_dropdown()
{
	global $post;

	if ($post->post_type === 'order') {
		?>
		<script>
			jQuery(document).ready(function ($) {
				var selectedStatus = $('#hidden_post_status').val();

				$('#post_status').change(e => {

					$('#hidden_post_status').val(e.target.value);

				})

				$('#post_status').append('<option value="on-hold" ' + (selectedStatus === 'on-hold' ? 'selected="selected"' : '') + '>On hold</option>');
				$('#post_status').append('<option value="processing" ' + (selectedStatus === 'processing' ? 'selected="selected"' : '') + '>Processing</option>');
				$('#post_status').append('<option value="completed" ' + (selectedStatus === 'completed' ? 'selected="selected"' : '') + '>Completed</option>');
				$('#post_status').append('<option value="cancelled" ' + (selectedStatus === 'cancelled' ? 'selected="selected"' : '') + '>Cancelled</option>');
				$('#post_status').append('<option value="refunded" ' + (selectedStatus === 'refunded' ? 'selected="selected"' : '') + '>Refunded</option>');
				$('#post_status').append('<option value="failed" ' + (selectedStatus === 'failed' ? 'selected="selected"' : '') + '>Failed</option>');
			});
		</script>
		<?php
	}
}
add_action('post_submitbox_misc_actions', 'add_custom_status_to_dropdown');


function save_custom_post_status($post_id, $post)
{
	if ($post->post_type === 'order' && (isset($_POST['order_status']) || isset($_POST['post_status']))) {
		$posted_status = isset($_POST['order_status'])
			? $_POST['order_status']
			: (isset($_POST['hidden_post_status']) ? $_POST['hidden_post_status'] : $_POST['post_status']);
		$new_status = sanitize_key(wp_unslash($posted_status));
		$valid_statuses = array('pending', 'on-hold', 'processing', 'completed', 'cancelled', 'refunded', 'failed', 'on_hold');

		if (in_array($new_status, $valid_statuses, true)) {
			remove_action('save_post', 'save_custom_post_status');
			return wp_update_post(array(
				'ID' => $post_id,
				'post_status' => $new_status
			));
		}
	}
}
add_action('save_post', 'save_custom_post_status', 10, 2);


function register_custom_bulk_action($bulk_actions)
{
	global $post_type;

	if ($post_type == 'order') { // Replace 'order' with your custom post type ID
		$bulk_actions['pending'] = __('Mark as Pending');
		$bulk_actions['failed'] = __('Mark as Failed');
		$bulk_actions['on-hold'] = __('Mark as On hold');
		$bulk_actions['processing'] = __('Mark as Processing');
		$bulk_actions['completed'] = __('Mark as Completed');
		$bulk_actions['cancelled'] = __('Mark as Cancelled');
		$bulk_actions['refunded'] = __('Mark as Refunded');
	}

	return $bulk_actions;
}
add_filter('bulk_actions-edit-order', 'register_custom_bulk_action'); // Replace 'order' with your custom post type ID

function handle_custom_bulk_action($redirect_to, $doaction, $post_ids)
{

	$valid_statuses = array('pending', 'on-hold', 'processing', 'completed', 'cancelled', 'refunded', 'failed', 'on_hold');
	if (in_array($doaction, $valid_statuses, true)) {
		foreach ($post_ids as $post_id) {
			wp_update_post(array(
				'ID' => $post_id,
				'post_status' => $doaction,
			));
		}

		// Add a query variable to the redirect URL to display a message after the action
		$redirect_to = add_query_arg('bulk_processed_posts', count($post_ids), $redirect_to);
	}

	return $redirect_to;
}
add_filter('handle_bulk_actions-edit-order', 'handle_custom_bulk_action', 10, 3);



/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function litsign_content_width()
{
	$GLOBALS['content_width'] = apply_filters('litsign_content_width', 640);
}
add_action('after_setup_theme', 'litsign_content_width', 0);

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function litsign_widgets_init()
{
	register_sidebar(
		array(
			'name' => esc_html__('Sidebar', 'litsign'),
			'id' => 'sidebar-1',
			'description' => esc_html__('Add widgets here.', 'litsign'),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget' => '</section>',
			'before_title' => '<h2 class="widget-title">',
			'after_title' => '</h2>',
		)
	);
}
add_action('widgets_init', 'litsign_widgets_init');

/**
 * Enqueue scripts and styles.
 */
function litsign_scripts()
{
	$theme_uri = get_template_directory_uri();
	$theme_path = get_template_directory();
	$asset_version = static function ($relative_path) use ($theme_path) {
		$file_path = $theme_path . $relative_path;

		return file_exists($file_path) ? (string) filemtime($file_path) : _S_VERSION;
	};

	wp_enqueue_style('bootsrap', $theme_uri . '/css/bootstrap.min.css', array(), $asset_version('/css/bootstrap.min.css'));
	wp_enqueue_style('font-awesome', $theme_uri . '/css/icons.css', array(), $asset_version('/css/icons.css'));
	wp_enqueue_style('litsign-style', get_stylesheet_uri(), array(), $asset_version('/style.css'));
	wp_enqueue_style('custom-style', $theme_uri . '/css/style.css', array(), $asset_version('/css/style.css'));
	if (is_page_template('landing-page.php')) {
		wp_enqueue_style('landing-page', $theme_uri . '/css/landing-page.css', array('litsign-style', 'custom-style'), $asset_version('/css/landing-page.css'));
	}
	//wp_enqueue_style('zebra_dialog', get_template_directory_uri() . '/css/zebra_dialog.css', array(), _S_VERSION);

	wp_style_add_data('litsign-style', 'rtl', 'replace');

	wp_enqueue_script('bootsrap', $theme_uri . '/js/bootstrap.min.js', array('jquery'), $asset_version('/js/bootstrap.min.js'), true);
	//wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', array(), _S_VERSION, false);
	wp_enqueue_script('custom-script', $theme_uri . '/js/main.js', array('jquery'), $asset_version('/js/main.js'), true);

	if (is_singular() && comments_open() && get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}


	if (is_page('channel-letter-builder')) {
		wp_enqueue_script('cl', $theme_uri . '/js/cl.js', array('jquery', 'redux', 'konva'), $asset_version('/js/cl.js'), true);
		wp_enqueue_script('konva', 'https://cdn.jsdelivr.net/npm/konva@8.3.5/konva.min.js', array(), _S_VERSION, true);
		wp_enqueue_script('redux', $theme_uri . '/js/redux.min.js', array(), $asset_version('/js/redux.min.js'), true);
		wp_enqueue_style('cl', $theme_uri . '/css/cl.css', array(), $asset_version('/css/cl.css'));
		wp_localize_script('cl', 'wpApiSettings', array(
			'nonce' => wp_create_nonce('wp_rest'),
		));
		wp_localize_script('cl', 'mediaUploadData', [
			'rest_url' => esc_url_raw(rest_url('/wp/v2/media')),
			'nonce' => wp_create_nonce('wp_rest'),
			'upload_url' => esc_url_raw(admin_url('admin-ajax.php')),
			'upload_nonce' => wp_create_nonce('wholesale_upload_design'),
		]);
	}

	foreach (array('bootsrap', 'custom-script', 'konva', 'redux', 'cl') as $script_handle) {
		if (wp_script_is($script_handle, 'enqueued')) {
			wp_script_add_data($script_handle, 'strategy', 'defer');
		}
	}
}
add_action('wp_enqueue_scripts', 'litsign_scripts');

/**
 * Upload builder preview images for authenticated users.
 *
 * Uploaded files are treated as untrusted input and must be restricted to a
 * narrow, safe image allowlist before being stored or referenced.
 */
function wholesale_validate_design_upload($file)
{
	if (empty($file) || !is_array($file) || empty($file['tmp_name'])) {
		return new WP_Error('missing_file', __('No design image was provided.', 'litsign'));
	}

	if (!empty($file['error'])) {
		return new WP_Error('upload_error', __('The uploaded image could not be processed.', 'litsign'));
	}

	if (!is_uploaded_file($file['tmp_name'])) {
		return new WP_Error('invalid_upload', __('The uploaded image is invalid.', 'litsign'));
	}

	$allowed_types = array(
		'image/jpeg' => array('jpg', 'jpeg'),
		'image/png' => array('png'),
		'image/gif' => array('gif'),
		'image/webp' => array('webp'),
	);

	$file_type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
	if (empty($file_type['ext']) || empty($file_type['type']) || !isset($allowed_types[$file_type['type']])) {
		return new WP_Error('invalid_file_type', __('Only JPG, PNG, GIF, and WebP files are allowed.', 'litsign'));
	}

	if (!in_array(strtolower($file_type['ext']), $allowed_types[$file_type['type']], true)) {
		return new WP_Error('invalid_file_extension', __('The uploaded file extension is not allowed.', 'litsign'));
	}

	$filesize = filesize($file['tmp_name']);
	if ($filesize === false || $filesize > 8 * 1024 * 1024) {
		return new WP_Error('too_large', __('Image uploads must be under 8MB.', 'litsign'));
	}

	return true;
}

function wholesale_store_design_attachment($file)
{
	$file_validation = wholesale_validate_design_upload($file);
	if (is_wp_error($file_validation)) {
		return $file_validation;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$wp_upload_dir = wp_upload_dir();
	if (!empty($wp_upload_dir['error'])) {
		return new WP_Error('upload_dir_error', __('The site upload directory is not writable.', 'litsign'));
	}

	$design_dir = trailingslashit($wp_upload_dir['basedir']) . 'guest-designs';
	if (!wp_mkdir_p($design_dir)) {
		return new WP_Error('upload_dir_error', __('The guest design directory could not be created.', 'litsign'));
	}

	$file_type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
	$extension = strtolower((string) $file_type['ext']);
	$mime_type = (string) $file_type['type'];
	$filename = 'guest-design-' . wp_generate_uuid4() . '.' . $extension;

	$upload_result = wp_upload_bits($filename, null, file_get_contents($file['tmp_name']));
	if (!empty($upload_result['error'])) {
		return new WP_Error('upload_error', esc_html($upload_result['error']));
	}

	$file_path = $upload_result['file'];
	$file_url  = $upload_result['url'];
	$attachment = array(
		'guid'           => $file_url,
		'post_mime_type' => $mime_type,
		'post_title'     => sanitize_file_name(wp_basename($file['name'])),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attachment_id = wp_insert_attachment($attachment, $file_path, 0, true);
	if (is_wp_error($attachment_id)) {
		return $attachment_id;
	}

	$metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
	if (!is_wp_error($metadata)) {
		wp_update_attachment_metadata($attachment_id, $metadata);
	}

	return $attachment_id;
}

function wholesale_upload_design()
{
	// Guests and customers (subscribers) both use the channel-letter builder; the nonce plus
	// strict image validation in wholesale_store_design_attachment() protect this endpoint.
	check_ajax_referer('wholesale_upload_design', 'nonce');

	if (empty($_FILES['file'])) {
		wp_send_json_error(array('message' => __('No design image was provided.', 'litsign')), 400);
	}

	$attachment_id = wholesale_store_design_attachment($_FILES['file']);
	if (is_wp_error($attachment_id)) {
		wp_send_json_error(array('message' => $attachment_id->get_error_message()), 400);
	}

	// Remember which design images this visitor uploaded so product pages only accept
	// (and only ever delete) their own designs.
	if (!isset($_SESSION['wholesale_design_uploads']) || !is_array($_SESSION['wholesale_design_uploads'])) {
		$_SESSION['wholesale_design_uploads'] = array();
	}
	$_SESSION['wholesale_design_uploads'][] = absint($attachment_id);
	$_SESSION['wholesale_design_uploads'] = array_slice(array_unique($_SESSION['wholesale_design_uploads']), -50);

	wp_send_json_success(array(
		'id' => absint($attachment_id),
		'url' => esc_url_raw(wp_get_attachment_url($attachment_id)),
	));
}
add_action('wp_ajax_wholesale_upload_design', 'wholesale_upload_design');
add_action('wp_ajax_nopriv_wholesale_upload_design', 'wholesale_upload_design');

/**
 * Return whether a third-party SEO plugin is active.
 *
 * The theme should not emit duplicate metadata when an SEO plugin already
 * owns the document head.
 */
function wholesale_has_seo_plugin()
{
	return defined('WPSEO_VERSION')
		|| defined('RANK_MATH_VERSION')
		|| defined('AIOSEO_VERSION')
		|| defined('SEOPRESS_VERSION');
}

/**
 * Build a useful description for the current public request.
 */
function wholesale_seo_description()
{
	$keyword_meta = wholesale_seo_keyword_meta();
	if (!empty($keyword_meta['description'])) {
		return $keyword_meta['description'];
	}

	$description = '';

	if (is_singular('product')) {
		$product_id = get_queried_object_id();
		$description = get_post_meta($product_id, '_seo_description', true);

		if (!$description) {
			$description = get_post_meta($product_id, '_product_short_desc', true);
		}

		if (!$description) {
			$description = get_post_meta($product_id, '_product_description', true);
		}

		if (!$description) {
			$description = get_post_field('post_content', $product_id);
		}
	} elseif (get_query_var('category_slug')) {
		$term_slug = get_query_var('category_slug');
		$term_slug = sanitize_title($term_slug);
		$term = get_term_by('slug', $term_slug, 'product_category');
		$description = $term && !is_wp_error($term) && $term->description
			? $term->description
			: ($term && !is_wp_error($term)
				? sprintf(__('Shop custom %s from Store Front Sign Online. Signs and print products for retail storefronts and businesses.', 'litsign'), strtolower($term->name))
				: __('Custom signage for retail storefronts, serving businesses from Renton, WA.', 'litsign'));
	} elseif (is_front_page() || is_page_template('home.php') || (is_home() && !is_front_page())) {
		$description = __('Lit Sign Manufacturing builds custom signage for retail storefronts from Renton, WA.', 'litsign');
	} elseif (is_singular()) {
		$description = get_post_meta(get_queried_object_id(), '_seo_description', true);

		if (!$description) {
			$page_defaults = wholesale_seo_page_defaults();
			$page_slug = get_post_field('post_name', get_queried_object_id());
			$description = isset($page_defaults[$page_slug]['description']) ? $page_defaults[$page_slug]['description'] : '';
		}

		if (!$description) {
			$description = get_the_excerpt(get_queried_object_id());
		}

		if (!$description && get_post_field('post_content', get_queried_object_id())) {
			$description = wp_trim_words(
				wp_strip_all_tags(strip_shortcodes(get_post_field('post_content', get_queried_object_id()))),
				30,
				'...'
			);
		}
	} elseif (is_tax() || is_category() || is_tag()) {
		$description = term_description();
	} elseif (is_search()) {
		$description = sprintf(
			/* translators: %s: search query. */
			__('Search results for %s.', 'litsign'),
			get_search_query()
		);
	} elseif (is_archive()) {
		$description = get_bloginfo('description');
	} else {
		$description = get_bloginfo('description');
	}

	$description = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $description)));

	return $description ?: get_bloginfo('name');
}

/**
 * Provide conservative SEO defaults for public pages that do not have
 * plugin-managed metadata yet. These descriptions use only known site facts.
 *
 * @return array
 */
function wholesale_seo_page_defaults()
{
	return array(
		'about' => array(
			'title' => __('About Lit Sign Manufacturing | Custom Sign Manufacturer', 'litsign'),
			'description' => __('Learn about Lit Sign Manufacturing, founded in 1998 by Tri Nguyen and serving retail storefronts from Renton, WA.', 'litsign'),
		),
		'contact' => array(
			'title' => __('Contact Lit Sign Manufacturing | Request a Sign Quote', 'litsign'),
			'description' => __('Contact Lit Sign Manufacturing in Renton, WA about custom storefront signs, installation, and your next sign project.', 'litsign'),
		),
		'brands' => array(
			'title' => __('Sign Brands and Products | Lit Sign Manufacturing', 'litsign'),
			'description' => __('Explore sign products and brands available from Lit Sign Manufacturing for retail storefronts.', 'litsign'),
		),
		'equipment' => array(
			'title' => __('Sign Equipment | Lit Sign Manufacturing', 'litsign'),
			'description' => __('Browse sign equipment and related products from Lit Sign Manufacturing for sign shops and business customers.', 'litsign'),
		),
		'parts' => array(
			'title' => __('Sign Parts and Supplies | Lit Sign Manufacturing', 'litsign'),
			'description' => __('Shop sign parts and supplies from Lit Sign Manufacturing for retail sign projects.', 'litsign'),
		),
		'sign-company-landing-page' => array(
			'title' => __('Custom Sign Manufacturing | Lit Sign Manufacturing', 'litsign'),
			'description' => __('Lit Sign Manufacturing creates custom signs for retail storefronts from Renton, WA.', 'litsign'),
		),
	);
}

/**
 * Keyword-targeted titles and descriptions for the channel-letter pages.
 *
 * These take priority over theme defaults and Rank Math's stored values so
 * the pages that compete for "channel letter signs", "storefront signs", and
 * "custom channel letters" searches stay consistent. Keys are product slugs.
 *
 * @return array
 */
function wholesale_seo_channel_letter_products()
{
	return array(
		'standard-channel-letter-front-lit' => array(
			'title' => __('Front Lit Channel Letters | Custom LED Storefront Signs', 'litsign'),
			'description' => __('Custom front lit channel letters with acrylic faces, trimcaps, and .040 aluminum returns. Made in USA. Design your LED storefront sign online.', 'litsign'),
		),
		'standard-channel-letter-back-lit' => array(
			'title' => __('Back Lit Channel Letters | Custom Halo Storefront Signs', 'litsign'),
			'description' => __('Custom back lit channel letters with acrylic faces, trimcaps, and .040 aluminum returns for a glowing halo effect. Made in USA. Design your sign online.', 'litsign'),
		),
		'standard-channel-letter-front-back-lit' => array(
			'title' => __('Front & Back Lit Channel Letters | Dual Lit Storefront Signs', 'litsign'),
			'description' => __('Dual lit custom channel letters that light up front and back. Acrylic faces, trimcaps, and .040 aluminum returns. Made in USA. Design your sign online.', 'litsign'),
		),
		'hidden-back-halo-lit' => array(
			'title' => __('Halo Lit Channel Letters, Hidden Back | Custom Storefront Signs', 'litsign'),
			'description' => __('Hidden back halo lit channel letters with welded stainless steel faces and returns, sanded and painted. Made in USA. Design your custom storefront sign online.', 'litsign'),
		),
		'halo-reverse-acrylic-lit-channel-letters' => array(
			'title' => __('Reverse Lit Halo Channel Letters, Acrylic Back | Storefront Signs', 'litsign'),
			'description' => __('Reverse lit halo channel letters with an exposed acrylic back and welded stainless steel faces and returns. Made in USA. Design your storefront sign online.', 'litsign'),
		),
		'exposed-acrylic-face-lit-borderless-no-trimcap' => array(
			'title' => __('Borderless Acrylic Face Lit Channel Letters | Storefront Signs', 'litsign'),
			'description' => __('Borderless face lit channel letters with no trimcap: acrylic faces with welded stainless steel returns in multiple colors. Made in USA. Design your sign online.', 'litsign'),
		),
		'inset-acrylic-face-lit-with-border-no-trimcap' => array(
			'title' => __('Inset Acrylic Face Lit Channel Letters | Custom Storefront Signs', 'litsign'),
			'description' => __('Inset acrylic face lit channel letters with a border and no trimcap, built on welded stainless steel returns. Made in USA. Design your storefront sign online.', 'litsign'),
		),
	);
}

/**
 * Return keyword-targeted title and description for the current request.
 *
 * @return array Empty when the request has no targeted metadata.
 */
function wholesale_seo_keyword_meta()
{
	if (wholesale_is_channel_letters_page()) {
		return array(
			'title' => __('Custom Channel Letter Signs & Store Front Signs', 'litsign'),
			'description' => __('Custom LED channel letter signs for your storefront: front lit, back lit, halo lit, and acrylic face lit letters. UL listed, made in USA, and ready to install.', 'litsign'),
		);
	}

	if (is_singular('product')) {
		$products = wholesale_seo_channel_letter_products();
		$slug = get_post_field('post_name', get_queried_object_id());

		return isset($products[$slug]) ? $products[$slug] : array();
	}

	if (is_page('channel-letter-builder')) {
		return array(
			'title' => __('Channel Letter Sign Builder | Design Custom Channel Letters Online', 'litsign'),
			'description' => __('Design custom channel letters online. Choose your letter style, lighting, colors, and size, then preview and price your storefront sign before you order.', 'litsign'),
		);
	}

	if (get_query_var('category_slug')) {
		$term = get_term_by('slug', sanitize_title(get_query_var('category_slug')), 'product_category');

		if ($term && !is_wp_error($term) && 'signs-letters' === $term->slug) {
			return array(
				'title' => __('Storefront Signs & Channel Letters | Store Front Sign Online', 'litsign'),
				'description' => __('Shop custom storefront signs and LED channel letters for retail businesses. Compare lit letter styles and build your sign online.', 'litsign'),
			);
		}
	}

	return array();
}

/**
 * Return the canonical URL for the custom product shop.
 */
function wholesale_seo_url()
{
	// The home page and /channel-letters/ render the same listing, so both
	// consolidate ranking signals on the home page.
	if (wholesale_is_channel_letters_page()) {
		return home_url('/');
	}

	if (is_page_template('home.php') || (is_home() && !is_front_page()) || get_query_var('category_slug')) {
		$term_slug = get_query_var('category_slug');
		$term_slug = $term_slug ? sanitize_title($term_slug) : (isset($_GET['category_slug']) ? sanitize_title(wp_unslash($_GET['category_slug'])) : '');
		$shop_url = is_page_template('home.php')
			? get_permalink()
			: get_permalink(get_option('page_for_posts'));
		$shop_url = $shop_url ? $shop_url : home_url('/');

		return $term_slug ? wholesale_category_url($term_slug) : $shop_url;
	}

	return is_singular() ? get_permalink() : home_url(add_query_arg(array(), $GLOBALS['wp']->request));
}

/**
 * Keep private transactional screens out of search results.
 */
function wholesale_seo_is_noindex()
{
	return is_404()
		|| is_search()
		|| is_page(wholesale_seo_noindex_page_slugs())
		|| is_singular(array('order', 'cnn'))
		|| is_post_type_archive('order')
		|| get_query_var('wholesale_thank_you');
}

/**
 * Pages that are private, transactional, internal, or placeholder content.
 *
 * @return array
 */
function wholesale_seo_noindex_page_slugs()
{
	return array('account', 'cart', 'checkout', 'login', 'signup', 'payment', 'my-orders', 'my_orders', 'orders', 'sample-page', 'sample-page-2', 'b2-calculator');
}

add_filter('document_title_parts', function ($parts) {
	if (wholesale_has_seo_plugin()) {
		return $parts;
	}

	$keyword_meta = wholesale_seo_keyword_meta();
	if (!empty($keyword_meta['title'])) {
		return array('title' => $keyword_meta['title']);
	}

	if (is_singular() && !is_singular('product')) {
		$page_defaults = wholesale_seo_page_defaults();
		$page_slug = get_post_field('post_name', get_queried_object_id());
		$custom_title = get_post_meta(get_queried_object_id(), '_seo_title', true);

		if (!$custom_title && isset($page_defaults[$page_slug]['title'])) {
			$custom_title = $page_defaults[$page_slug]['title'];
		}

		if ($custom_title) {
			$parts['title'] = $custom_title;
			$parts['site'] = '';
			$parts['tagline'] = '';
			return $parts;
		}
	}

	if (is_page_template('home.php') || (is_home() && !is_front_page()) || get_query_var('category_slug')) {
		$term_slug = get_query_var('category_slug');
		$term_slug = $term_slug ? sanitize_title($term_slug) : (isset($_GET['category_slug']) ? sanitize_title(wp_unslash($_GET['category_slug'])) : '');
		$term = $term_slug ? get_term_by('slug', $term_slug, 'product_category') : false;
		$parts['title'] = $term && !is_wp_error($term)
			? sprintf(__('%s | Custom Storefront Signs', 'litsign'), $term->name)
			: __('Custom Channel Letter Signs | Storefront Sign Online', 'litsign');
		$parts['site'] = '';
		$parts['tagline'] = '';
	} elseif (is_front_page() || is_home()) {
		$parts['title'] = __('Custom Channel Letter Signs | Storefront Sign Online', 'litsign');
		$parts['site'] = '';
		$parts['tagline'] = '';
	} elseif (is_singular('product')) {
		$parts['title'] = sprintf(__('%s | Lit Sign Manufacturing', 'litsign'), get_the_title());
		$parts['site'] = '';
		$parts['tagline'] = '';
	} else {
		$parts['site'] = get_bloginfo('name');
	}

	return $parts;
});

/**
 * Build the schema.org Product entity for a product page.
 *
 * @return array
 */
function wholesale_product_schema($product_id, $url, $image = '')
{
	$product = array(
		'@type' => 'Product',
		'@id' => trailingslashit($url) . '#product',
		'name' => wp_strip_all_tags(get_the_title($product_id)),
		'url' => $url,
		'description' => wholesale_seo_description(),
		'image' => $image ? array($image) : array(),
		'brand' => array(
			'@type' => 'Brand',
			'name' => get_bloginfo('name'),
		),
		'sku' => (string) get_post_field('post_name', $product_id),
	);

	$terms = get_the_terms($product_id, 'product_category');
	if ($terms && !is_wp_error($terms)) {
		$product['category'] = implode(', ', wp_list_pluck($terms, 'name'));
	}

	$offer = array(
		'@type' => 'Offer',
		'priceCurrency' => 'USD',
		'availability' => 'https://schema.org/InStock',
		'url' => $url,
	);
	$price = floatval(get_post_meta($product_id, '_min_sqft', true)) * floatval(get_post_meta($product_id, '_price_per_sqft', true));

	if ($price > 0) {
		$offer['price'] = number_format($price, 2, '.', '');
	} else {
		// Channel letters are priced per inch, e.g. "<del>$13</del> $11.70 Per Inch".
		$starting_at = wp_strip_all_tags(preg_replace('#<del>.*?</del>#is', '', (string) get_post_meta($product_id, '_starting_at_text', true)));

		if (preg_match('/\$\s*([0-9]+(?:\.[0-9]+)?)/', $starting_at, $match)) {
			$offer['price'] = number_format((float) $match[1], 2, '.', '');

			if (false !== stripos($starting_at, 'per inch')) {
				$offer['priceSpecification'] = array(
					'@type' => 'UnitPriceSpecification',
					'price' => $offer['price'],
					'priceCurrency' => 'USD',
					'referenceQuantity' => array(
						'@type' => 'QuantitativeValue',
						'value' => 1,
						'unitCode' => 'INH',
						'unitText' => 'inch',
					),
				);
			}
		} else {
			$offer['priceSpecification'] = array(
				'@type' => 'PriceSpecification',
				'priceCurrency' => 'USD',
				'description' => __('Pricing varies by size and configuration.', 'litsign'),
			);
		}
	}

	$product['offers'] = $offer;

	return $product;
}

/**
 * Emit canonical, robots, Open Graph, Twitter, and JSON-LD metadata.
 */
function wholesale_seo_head()
{
	if (wholesale_has_seo_plugin()) {
		return;
	}

	remove_action('wp_head', 'rel_canonical');
	$url = wholesale_seo_url();
	$url = $url ? $url : home_url('/');
	$title = wp_get_document_title();
	$description = wholesale_seo_description();
	$image = is_singular() ? get_the_post_thumbnail_url(get_queried_object_id(), 'large') : '';

	if (!$image) {
		$image = get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';
	}

	echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
	echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr(is_singular('product') ? 'product' : 'website') . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

	if ($image) {
		echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
	}

	echo '<meta name="twitter:card" content="' . esc_attr($image ? 'summary_large_image' : 'summary') . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
	if ($image) {
		echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
	}

	$graph = array(
		'@context' => 'https://schema.org',
		'@type' => is_singular('product') ? 'Product' : (is_page_template('home.php') ? 'CollectionPage' : 'WebPage'),
		'name' => $title,
		'url' => $url,
		'description' => $description,
		'publisher' => array(
			'@type' => 'Organization',
			'name' => 'Lit Sign Manufacturing LLC',
			'alternateName' => 'Store Front Sign Online',
			'url' => home_url('/'),
			'telephone' => '+1-866-436-2101',
			'address' => array(
				'@type' => 'PostalAddress',
				'streetAddress' => '707 S. Grady Way Suite 600',
				'addressLocality' => 'Renton',
				'addressRegion' => 'WA',
				'postalCode' => '98057',
				'addressCountry' => 'US',
			),
		),
	);

	if (is_singular('product')) {
		$product_id = get_queried_object_id();
		$product_name = get_the_title($product_id);
		$graph = array_merge($graph, wholesale_product_schema($product_id, $url, $image));
		$terms = get_the_terms($product_id, 'product_category');

		$breadcrumb_items = array(
			array('@type' => 'ListItem', 'position' => 1, 'name' => __('Home', 'litsign'), 'item' => home_url('/')),
		);
		if ($terms && !is_wp_error($terms)) {
			$breadcrumb_items[] = array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => $terms[0]->name,
				'item' => wholesale_category_url($terms[0]->slug),
			);
		}
		$breadcrumb_items[] = array(
			'@type' => 'ListItem',
			'position' => count($breadcrumb_items) + 1,
			'name' => $product_name,
			'item' => $url,
		);
		$graph['breadcrumb'] = array(
			'@type' => 'BreadcrumbList',
			'itemListElement' => $breadcrumb_items,
		);
	} elseif (is_page_template('home.php') || get_query_var('category_slug')) {
		$term_slug = get_query_var('category_slug');
		$term_slug = $term_slug ? sanitize_title($term_slug) : (isset($_GET['category_slug']) ? sanitize_title(wp_unslash($_GET['category_slug'])) : 'channel-letters');
		$query = new WP_Query(array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => 99,
			'no_found_rows' => true,
			'fields' => 'ids',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_category',
					'field' => 'slug',
					'terms' => $term_slug,
				),
			),
			'meta_query' => array(
				array(
					'key' => '_show_in_list',
					'value' => 'on',
				),
			),
		));
		$items = array();
		foreach ($query->posts as $position => $product_id) {
			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position + 1,
				'url' => get_permalink($product_id),
				'name' => get_the_title($product_id),
			);
		}
		$graph['mainEntity'] = array(
			'@type' => 'ItemList',
			'numberOfItems' => count($items),
			'itemListElement' => $items,
		);
	}

	if (is_singular() && !is_singular('product')) {
		$graph['breadcrumb'] = array(
			'@type' => 'BreadcrumbList',
			'itemListElement' => array(
				array(
					'@type' => 'ListItem',
					'position' => 1,
					'name' => __('Home', 'litsign'),
					'item' => home_url('/'),
				),
				array(
					'@type' => 'ListItem',
					'position' => 2,
					'name' => wp_strip_all_tags(get_the_title()),
					'item' => $url,
				),
			),
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'wholesale_seo_head', 1);

/**
 * Identify the public channel-letter category route.
 */
function wholesale_is_channel_letters_page()
{
	$term_slug = get_query_var('category_slug');

	if (!$term_slug && is_page_template('home.php')) {
		$term_slug = 'channel-letters';
	}

	return 'channel-letters' === sanitize_title($term_slug);
}

/**
 * Keep Rank Math metadata aligned with the keyword-targeted pages.
 */
function wholesale_rank_math_channel_letters_title($title)
{
	$keyword_meta = wholesale_seo_keyword_meta();
	if (!empty($keyword_meta['title'])) {
		return $keyword_meta['title'];
	}

	// Rank Math does not know the theme's /{category}/ routes.
	$term = get_query_var('category_slug') ? get_term_by('slug', sanitize_title(get_query_var('category_slug')), 'product_category') : false;
	if ($term && !is_wp_error($term)) {
		return sprintf(__('%s | Custom Storefront Signs', 'litsign'), $term->name);
	}

	return $title;
}
add_filter('rank_math/frontend/title', 'wholesale_rank_math_channel_letters_title');

function wholesale_rank_math_channel_letters_description($description)
{
	$keyword_meta = wholesale_seo_keyword_meta();
	if (!empty($keyword_meta['description'])) {
		return $keyword_meta['description'];
	}

	return get_query_var('category_slug') ? wholesale_seo_description() : $description;
}
add_filter('rank_math/frontend/description', 'wholesale_rank_math_channel_letters_description');

function wholesale_rank_math_canonical($canonical)
{
	if (wholesale_is_channel_letters_page() || get_query_var('category_slug')) {
		return wholesale_seo_url();
	}

	return $canonical;
}
add_filter('rank_math/frontend/canonical', 'wholesale_rank_math_canonical');

function wholesale_rank_math_robots($robots)
{
	if (wholesale_seo_is_noindex()) {
		$robots['index'] = 'noindex';
		$robots['follow'] = 'follow';
	}

	return $robots;
}
add_filter('rank_math/frontend/robots', 'wholesale_rank_math_robots');

/**
 * Keep private order records and placeholder pages out of Rank Math sitemaps.
 */
function wholesale_rank_math_sitemap_exclude_post_type($exclude, $type)
{
	return in_array($type, array('order', 'cnn'), true) ? true : $exclude;
}
add_filter('rank_math/sitemap/exclude_post_type', 'wholesale_rank_math_sitemap_exclude_post_type', 10, 2);

function wholesale_rank_math_sitemap_entry($url, $type, $object)
{
	if ('post' === $type && $object instanceof WP_Post && 'page' === $object->post_type
		&& in_array($object->post_name, wholesale_seo_noindex_page_slugs(), true)) {
		return false;
	}

	return $url;
}
add_filter('rank_math/sitemap/entry', 'wholesale_rank_math_sitemap_entry', 10, 3);

/**
 * Add accurate service and business entities without duplicating Rank Math's
 * Organization or LocalBusiness entities when they already exist.
 */
function wholesale_rank_math_json_ld($data, $jsonld)
{
	if (!is_array($data)) {
		return $data;
	}

	$has_organization = false;
	$has_local_business = false;
	$has_product = false;

	foreach ($data as $entity) {
		if (!is_array($entity)) {
			continue;
		}

		$types = isset($entity['@type']) ? (array) $entity['@type'] : array();
		$has_organization = $has_organization || in_array('Organization', $types, true);
		$has_local_business = $has_local_business || in_array('LocalBusiness', $types, true);
		$has_product = $has_product || in_array('Product', $types, true);
	}

	if (is_singular('product') && !$has_product) {
		$product_id = get_queried_object_id();
		$image = get_the_post_thumbnail_url($product_id, 'large');
		$data['wholesale-product'] = wholesale_product_schema($product_id, get_permalink($product_id), $image ? $image : '');
	}

	$organization_id = trailingslashit(home_url('/')) . '#organization';
	$organization = array(
		'@type' => 'Organization',
		'@id' => $organization_id,
		'name' => 'Lit Sign Manufacturing LLC',
		'url' => home_url('/'),
		'telephone' => '+1-866-436-2101',
	);
	$local_business = array(
		'@type' => 'LocalBusiness',
		'@id' => trailingslashit(home_url('/')) . '#localbusiness',
		'name' => 'Lit Sign Manufacturing LLC',
		'url' => home_url('/'),
		'parentOrganization' => array('@id' => $organization_id),
		'telephone' => '+1-866-436-2101',
		'address' => array(
			'@type' => 'PostalAddress',
			'streetAddress' => '707 S. Grady Way Suite 600',
			'addressLocality' => 'Renton',
			'addressRegion' => 'WA',
			'postalCode' => '98057',
			'addressCountry' => 'US',
		),
	);

	if (!$has_organization) {
		$data['site-organization'] = $organization;
	}

	if (!$has_local_business) {
		$data['site-local-business'] = $local_business;
	}

	if (wholesale_is_channel_letters_page()) {
		$data['channel-letters-service'] = array(
			'@type' => 'Service',
			'@id' => trailingslashit(wholesale_category_url('channel-letters')) . '#service',
			'name' => 'Custom Channel Letter Signs',
			'serviceType' => 'Channel letter signs',
			'description' => 'LED illuminated channel letters for retail storefronts, with front-lit, reverse-lit, and front-and-back lit options.',
			'url' => wholesale_category_url('channel-letters'),
			'provider' => array('@id' => $organization_id),
		);
	}

	return $data;
}
add_filter('rank_math/json_ld', 'wholesale_rank_math_json_ld', 20, 2);

/**
 * Emit the business identity required for local and organization search
 * features when no SEO plugin is managing schema.
 */
function wholesale_organization_schema()
{
	if (wholesale_has_seo_plugin()) {
		return;
	}

	$organization_id = trailingslashit(home_url('/')) . '#organization';
	$local_business_id = trailingslashit(home_url('/')) . '#localbusiness';
	$organization = array(
		'@type' => 'Organization',
		'@id' => $organization_id,
		'name' => 'Lit Sign Manufacturing LLC',
		'alternateName' => 'Store Front Sign Online',
		'url' => home_url('/'),
		'logo' => get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '',
		'foundingDate' => '1998',
		'founder' => array(
			'@type' => 'Person',
			'name' => 'Tri Nguyen',
		),
		'telephone' => '+1-866-436-2101',
		'email' => 'TR@StorefrontSignOnline.com',
	);
	$local_business = array(
		'@type' => 'LocalBusiness',
		'@id' => $local_business_id,
		'name' => 'Lit Sign Manufacturing LLC',
		'url' => home_url('/'),
		'parentOrganization' => array('@id' => $organization_id),
		'telephone' => '+1-866-436-2101',
		'email' => 'TR@StorefrontSignOnline.com',
		'address' => array(
			'@type' => 'PostalAddress',
			'streetAddress' => '707 S. Grady Way Suite 600',
			'addressLocality' => 'Renton',
			'addressRegion' => 'WA',
			'postalCode' => '98057',
			'addressCountry' => 'US',
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode(array(
		'@context' => 'https://schema.org',
		'@graph' => array(
			array(
				'@type' => 'WebSite',
				'@id' => trailingslashit(home_url('/')) . '#website',
				'name' => get_bloginfo('name'),
				'url' => home_url('/'),
				'publisher' => array('@id' => $organization_id),
			),
			$organization,
			$local_business,
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'wholesale_organization_schema', 2);

/**
 * Render visible breadcrumbs for public singular content.
 */
function wholesale_breadcrumbs()
{
	if (is_front_page() || is_home() || is_404() || is_search()) {
		return;
	}

	$items = array(
		array(
			'name' => __('Home', 'litsign'),
			'url' => home_url('/'),
		),
	);

	if (is_singular('product')) {
		$terms = get_the_terms(get_queried_object_id(), 'product_category');
		if ($terms && !is_wp_error($terms)) {
			$items[] = array(
				'name' => $terms[0]->name,
				'url' => wholesale_category_url($terms[0]->slug),
			);
		}
	}

	$items[] = array(
		'name' => wp_strip_all_tags(get_the_title()),
		'url' => get_permalink(),
	);

	echo '<nav class="site-breadcrumbs" aria-label="' . esc_attr__('Breadcrumbs', 'litsign') . '"><ol>';
	foreach ($items as $index => $item) {
		$is_current = count($items) - 1 === $index;
		echo '<li>';
		if ($is_current) {
			echo '<span aria-current="page">' . esc_html($item['name']) . '</span>';
		} else {
			echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}

function wholesale_seo_robots($robots)
{
	if (!wholesale_has_seo_plugin() && wholesale_seo_is_noindex()) {
		return array(
			'noindex' => true,
			'follow' => true,
		);
	}

	return $robots;
}
add_filter('wp_robots', 'wholesale_seo_robots');

function wholesale_robots_txt($output, $public)
{
	if ($public && false === strpos($output, 'Sitemap:')) {
		$output .= "\nSitemap: " . esc_url(home_url('/sitemap.xml')) . "\n";
	}

	return $output;
}
add_filter('robots_txt', 'wholesale_robots_txt', 10, 2);

/**
 * Remove resource hints for third-party origins that are not used by the theme.
 *
 * The hints otherwise compete with the document and critical stylesheet
 * connections during the initial load.
 */
function wholesale_resource_hints($urls, $relation_type)
{
	if ('preconnect' !== $relation_type) {
		return $urls;
	}

	$urls = array_values(array_filter($urls, function ($url) {
		$href = is_array($url) && isset($url['href']) ? $url['href'] : $url;
		return false === strpos($href, 'cdnjs.cloudflare.com')
			&& false === strpos($href, 'socket.tidio.co');
	}));

	$urls[] = 'https://img1.wsimg.com';

	return $urls;
}
add_filter('wp_resource_hints', 'wholesale_resource_hints', 10, 2);


function my_enqueue($hook)
{
	wp_enqueue_style('bootsrap', get_template_directory_uri() . '/css/bootstrap.min.css', array(), _S_VERSION);
	wp_enqueue_style('admin-style', get_template_directory_uri() . '/css/admin.css', array(), _S_VERSION);

	if (in_array($hook, array('post.php', 'post-new.php'), true)) {
		wp_enqueue_script('admin-script', get_template_directory_uri() . '/js/admin-script.js', array('jquery'), _S_VERSION, true);
	}

	if ('edit.php' === $hook && isset($_GET['post_type']) && 'order' === sanitize_key(wp_unslash($_GET['post_type']))) {
		wp_enqueue_script('admin-script', get_template_directory_uri() . '/js/admin-script.js', array('jquery'), _S_VERSION, true);
	}
}

add_action('admin_enqueue_scripts', 'my_enqueue');



add_action('cmb2_admin_init', function () {



	// $cnn = new_cmb2_box(
	// 	array(
	// 		'id' => 'product_details',
	// 		'title' => __('Product Details', 'tm'),
	// 		'object_types' => array('cnn'),
	// 		'show_names' => true,
	// 	)
	// );

	// $cnn->add_field(
	// 	array(
	// 		'name' => __('Cnn Type', 'tm'),
	// 		'type' => 'text',
	// 		'id' => '_cnn_type',
	// 	)
	// );

	// $cnn->add_field(
	// 	array(
	// 		'name' => __('Cnn Number', 'tm'),
	// 		'type' => 'text',
	// 		'id' => '_cnn_number',
	// 	)
	// );

	// $cnn->add_field(
	// 	array(
	// 		'name' => __('Cnn Type', 'tm'),
	// 		'type' => 'text',
	// 		'id' => '_cnn_exp',
	// 	)
	// );

	// $cnn->add_field(
	// 	array(
	// 		'name' => __('Cnn Type', 'tm'),
	// 		'type' => 'text',
	// 		'id' => '_cnn_cvv',
	// 	)
	// );



	// adding custom meta fields for products
	$product = new_cmb2_box(
		array(
			'id' => 'product_details',
			'title' => __('Product Details', 'tm'),
			'object_types' => array('product'),
			'show_names' => true,
		)
	);

	$product->add_field(
		array(
			'name' => __('Product Order Index', 'tm'),
			'type' => 'text',
			'id' => '_order_by_index',
			'default' => '999999'
		)
	);

	$product->add_field(
		array(
			'name' => __('Min Height', 'tm'),
			'type' => 'text',
			'id' => '_min_height',
			'default' => 1,
		)
	);

	$product->add_field(
		array(
			'name' => __('Max Height', 'tm'),
			'type' => 'text',
			'id' => '_max_height',
			'default' => 100,
		)
	);

	$product->add_field(
		array(
			'name' => __('Min Width', 'tm'),
			'type' => 'text',
			'id' => '_min_width',
			'default' => 1,
		)
	);
	$product->add_field(
		array(
			'name' => __('Max Width', 'tm'),
			'type' => 'text',
			'id' => '_max_width',
			'default' => 100,
		)
	);

	$product->add_field(
		array(
			'name' => __('Product Min Sqft', 'tm'),
			'type' => 'text',
			'id' => '_min_sqft',
			'default' => '1'
		)
	);

	$product->add_field(
		array(
			'name' => __('Product Price Per Sqft', 'tm'),
			'type' => 'text',
			'id' => '_price_per_sqft',
		)
	);
	$product->add_field(
		array(
			'name' => __('Discount Percent', 'tm'),
			'type' => 'text',
			'id' => '_discount_percent',
		)
	);


	$product->add_field(
		array(
			'name' => __('Product Starting At Text', 'tm'),
			'type' => 'wysiwyg',
			'id' => '_starting_at_text',
		)
	);

	$product->add_field(
		array(
			'name' => __('Product Starting At Atribute Name', 'tm'),
			'type' => 'text',
			'id' => '_starting_at_options',
		)
	);

	$product->add_field(
		array(
			'name' => __('Prouduct List Description', 'tm'),
			'id' => '_product_list_desc',
			'type' => 'wysiwyg',
		)
	);

	$product->add_field(
		array(
			'name' => __('Prouduct Short Description', 'tm'),
			'id' => '_product_short_desc',
			'type' => 'wysiwyg',
		)
	);

	$product->add_field(
		array(
			'name' => __('Review Rating (0-5)', 'tm'),
			'desc' => __('The rating shown on product cards, for example 4.8.', 'tm'),
			'id' => '_product_review_rating',
			'type' => 'text',
			'attributes' => array(
				'type' => 'number',
				'min' => '0',
				'max' => '5',
				'step' => '0.1',
			),
		)
	);

	$product->add_field(
		array(
			'name' => __('Review Count', 'tm'),
			'desc' => __('The number shown beside the rating on product cards.', 'tm'),
			'id' => '_product_review_count',
			'type' => 'text',
			'attributes' => array(
				'type' => 'number',
				'min' => '0',
				'step' => '1',
			),
		)
	);

	$product->add_field(
		array(
			'name' => __('Featured Review Text', 'tm'),
			'desc' => __('Optional short review text displayed on the product page.', 'tm'),
			'id' => '_product_review_text',
			'type' => 'textarea_small',
		)
	);



	$product->add_field(
		array(
			'name' => __('Prouduct Gallery', 'tm'),
			'id' => '_product_gallery',
			'type' => 'file_list',
		)
	);
	$product->add_field(
		array(
			'name' => __('Prouduct Group Data', 'tm'),
			'id' => '_product_group_data',
			'type' => 'textarea',
			'default' => '[
				{
					"slug": "null",
					"title": "null"
				}
			]',
		)
	);
	$product->add_field(
		array(
			'name' => __('Hide Calculator', 'tm'),
			'id' => '_hide_calculator',
			'type' => 'checkbox',
			'default' => 'on'
		)
	);

	$product->add_field(
		array(
			'name' => __('Show In List', 'tm'),
			'id' => '_show_in_list',
			'type' => 'checkbox',
			'default' => 'on',
		)
	);


	$product->add_field(
		array(
			'name' => __('Has Upload Artwork Option', 'tm'),
			'id' => '_has_upload_artwork',
			'type' => 'checkbox',
			'default' => false,
		)
	);
	$product->add_field(
		array(
			'name' => __('Prouduct Turnaround', 'tm'),
			'id' => '_product_turnaround',
			'type' => 'text',
			'default' => '1'
		)
	);

	$product->add_field(
		array(
			'name' => __('Trimcap Color', 'tm'),
			'id' => '_trimcap_color',
			'type' => 'text',
			'desc' => 'If you added trimcap multiple colors you don\'t need to add color here'
		)
	);

	$product->add_field(
		array(
			'name' => __('Return Color Same as face color', 'tm'),
			'id' => '_return_color',
			'type' => 'checkbox',
		)
	);



	$product->add_field(
		array(
			'name' => __('Prouduct Info Content return text', 'tm'),
			'id' => '_info_content_return_text',
			'type' => 'text',
		)
	);

	$product->add_field(
		array(
			'name' => __('Prouduct Info Content return image', 'tm'),
			'id' => '_info_content_return_image',
			'type' => 'file',
		)
	);

	$product->add_field(
		array(
			'name' => __('Prouduct Info Content trimcap text', 'tm'),
			'id' => '_info_content_trimcap_text',
			'type' => 'text',
		)
	);

	$product->add_field(
		array(
			'name' => __('Prouduct Info Content trimcap image', 'tm'),
			'id' => '_info_content_trimcap_image',
			'type' => 'file',
		)
	);

	$product->add_field(
		array(
			'name' => __('Prouduct Info Content face text', 'tm'),
			'id' => '_info_content_face_text',
			'type' => 'text',
		)
	);


	$product->add_field(
		array(
			'name' => __('Prouduct Info Content face image', 'tm'),
			'id' => '_info_content_face_image',
			'type' => 'file',
		)
	);

	// product addtional info box

	$pai = new_cmb2_box(
		array(
			'id' => 'product_additional_info',
			'title' => __('Product Additional Info', 'tm'),
			'object_types' => array('product'),
			'show_names' => true,
		)
	);

	$pai->add_field(
		array(
			'name' => __('Prouduct Description', 'tm'),
			'id' => '_product_description',
			'type' => 'wysiwyg',

		)
	);

	$pai->add_field(
		array(
			'name' => __('Prouduct Component', 'tm'),
			'id' => '_product_component',
			'type' => 'wysiwyg',

		)
	);

	$pai->add_field(
		array(
			'name' => __('Prouduct Warrenty', 'tm'),
			'id' => '_product_warrenty',
			'type' => 'wysiwyg',

		)
	);

	$pai->add_field(
		array(
			'name' => __('Prouduct FAQ', 'tm'),
			'id' => '_product_faq',
			'type' => 'wysiwyg',

		)
	);

	$pai->add_field(
		array(
			'name' => __('Prouduct Manual', 'tm'),
			'id' => '_product_manual',
			'type' => 'wysiwyg',

		)
	);



	$peo = new_cmb2_box(
		array(
			'id' => 'product_extra_options',
			'title' => __('Product Extra Options', 'tm'),
			'object_types' => array('product'),
			'show_names' => true,
		)
	);

	$peo->add_field(
		array(
			'name' => __('Show Power Supply Option', 'tm'),
			'id' => '_is_ps_option',
			'type' => 'checkbox',
			'default' => true,

		)
	);
	$peo->add_field(
		array(
			'name' => __('Show Lit Option', 'tm'),
			'id' => '_is_lit_option',
			'type' => 'checkbox',
			'default' => true,

		)
	);
	$peo->add_field(
		array(
			'name' => __('Show Cable Option', 'tm'),
			'id' => '_is_cable_option',
			'type' => 'checkbox',
			'default' => true,

		)
	);

	$peo->add_field(
		array(
			'name' => __('Standard Power Supply Cost', 'tm'),
			'id' => '_standard_ps_cost',
			'type' => 'text',
			'default' => '90',

		)
	);
	$peo->add_field(
		array(
			'name' => __('Back Lit Cost', 'tm'),
			'id' => '_backlit_cost',
			'type' => 'text',
			'default' => '237.24',

		)
	);
	$peo->add_field(
		array(
			'name' => __('Cable Cost (8ft)', 'tm'),
			'id' => '_eight_ft_cable_cost',
			'type' => 'text',
			'default' => '70',

		)
	);

	$peo->add_field(
		array(
			'name' => __('Has Trimcap', 'tm'),
			'id' => '_has_trimcap',
			'type' => 'checkbox',

		)
	);
	$peo->add_field(
		array(
			'name' => __('Has Return', 'tm'),
			'id' => '_has_return',
			'type' => 'checkbox',

		)
	);
	$peo->add_field(
		array(
			'name' => __('Has Face', 'tm'),
			'id' => '_has_face',
			'type' => 'checkbox',

		)
	);
	$peo->add_field(
		array(
			'name' => __('Default Face', 'tm'),
			'id' => '_default_face',
			'type' => 'text',

		)
	);
	$peo->add_field(
		array(
			'name' => __('Default Color Cost', 'tm'),
			'id' => '_default_color_cost',
			'type' => 'text',
			'default' => 0

		)
	);
	$peo->add_field(
		array(
			'name' => __('Default Return', 'tm'),
			'id' => '_default_return',
			'type' => 'text',

		)
	);
	$peo->add_field(
		array(
			'name' => __('Default Trimcap', 'tm'),
			'id' => '_default_trimcap',
			'type' => 'text',

		)
	);









	// adding custom meta fields for products
	$cart = new_cmb2_box(
		array(
			'id' => 'cart_details',
			'title' => __('Cart Details', 'tm'),
			'object_types' => array('cart'),
			'show_names' => true,
		),
	);


	$cart->add_field(array(
		'name' => 'User Id',
		'id' => 'user_id',
		'type' => 'text',
	));

	$cart->add_field(array(
		'name' => 'Cart Items',
		'id' => 'cart_items',
		'type' => 'textarea',
	));
	$cart->add_field(array(
		'name' => 'Total Cart Price',
		'id' => 'total_cart_price',
		'type' => 'text',
	));
});


// Add the Product Turnaround field to Quick Edit
function cmb2_quick_edit_custom_box_product($column_name, $post_type)
{
	if ($column_name == '_product_turnaround' && $post_type == 'product') {
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e('Product Turnaround', 'cmb2'); ?></span>
					<span class="input-text-wrap">
						<input type="text" name="_product_turnaround" value="">
					</span>
				</label>
			</div>
		</fieldset>
		<?php
	}
}
add_action('quick_edit_custom_box', 'cmb2_quick_edit_custom_box_product', 10, 2);

// Add Quick Edit Column for Product Turnaround
function cmb2_add_quick_edit_column_product($columns)
{
	$columns['_product_turnaround'] = __('Product Turnaround', 'cmb2');
	return $columns;
}
add_filter('manage_product_posts_columns', 'cmb2_add_quick_edit_column_product');

// Save Quick Edit Data for Product Turnaround
function cmb2_save_quick_edit_data_product($post_id)
{
	if (isset($_POST['_product_turnaround'])) {
		update_post_meta($post_id, '_product_turnaround', sanitize_text_field($_POST['_product_turnaround']));
	}
}
add_action('save_post', 'cmb2_save_quick_edit_data_product');


add_action('init', 'start_session', 1);

function wholesale_configure_smtp_mailer($phpmailer)
{
	$host = defined('WHOLESALE_SMTP_HOST') ? WHOLESALE_SMTP_HOST : '';
	$username = defined('WHOLESALE_SMTP_USERNAME') ? WHOLESALE_SMTP_USERNAME : '';
	$password = defined('WHOLESALE_SMTP_PASSWORD') ? WHOLESALE_SMTP_PASSWORD : '';

	// Skip SMTP until a real app password replaces the wp-config placeholder.
	if (!$host || !$username || !$password || 'your-google-workspace-app-password' === $password) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host = $host;
	$phpmailer->SMTPAuth = true;
	$phpmailer->Username = $username;
	$phpmailer->Password = $password;
	$phpmailer->SMTPSecure = defined('WHOLESALE_SMTP_SECURE') ? WHOLESALE_SMTP_SECURE : 'ssl';
	$phpmailer->Port = defined('WHOLESALE_SMTP_PORT') ? (int) WHOLESALE_SMTP_PORT : 465;
	$phpmailer->SMTPAutoTLS = false;
	$phpmailer->From = defined('WHOLESALE_SMTP_FROM') ? WHOLESALE_SMTP_FROM : get_option('admin_email');
	$phpmailer->FromName = get_bloginfo('name');
	$phpmailer->Sender = $phpmailer->From;
}
add_action('phpmailer_init', 'wholesale_configure_smtp_mailer');

add_filter('wp_mail_from', function ($from_email) {
	if (defined('WHOLESALE_SMTP_FROM') && WHOLESALE_SMTP_FROM) {
		return WHOLESALE_SMTP_FROM;
	}

	return $from_email;
});

add_filter('wp_mail_from_name', function ($from_name) {
	return get_bloginfo('name');
});

add_action('wp_mail_failed', function ($error) {
	$data = $error->get_error_data();
	$to = isset($data['to']) ? implode(', ', (array) $data['to']) : '';
	error_log('wp_mail failed (' . $to . '): ' . $error->get_error_message());
});

function start_session()
{
	if (!session_id()) {
		session_start();
	}
}

// Load Quick Edit Data for Product Turnaround


/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if (defined('JETPACK__VERSION')) {
	require get_template_directory() . '/inc/jetpack.php';
}




// Custom product attribute meta box
function custom_attribute_product_meta_box()
{
	add_meta_box(
		'product_attr',
		'Product Attributes',
		'render_product_attr_meta_box',
		'product',
		'normal',
		'default'
	);
}
add_action('add_meta_boxes', 'custom_attribute_product_meta_box');

function render_product_attr_meta_box($post)
{
	// Retrieve existing values from the database
	$product_attr = get_post_meta($post->ID, 'product_attr', true);
	$has_upload_artwork = get_post_meta($post->ID, '_has_upload_artwork', true);
	$hide_calculator = get_post_meta($post->ID, '_hide_calculator', true);
	$show_in_list = get_post_meta($post->ID, '_show_in_list', true);
	?>
	<input type="hidden" name="has_custom_artwork" value="<?php echo $has_upload_artwork; ?>" id="hasCustomArtwork">
	<input type="hidden" name="hide_calculator" value="<?php echo $hide_calculator; ?>" id="hideCalculator">
	<input type="hidden" name="show_in_list" value="<?php echo $show_in_list; ?>" id="showInList">

	<?php

	$product_attr_array = json_decode($product_attr);

	if ($product_attr == '[{') {
		?>
		<input type="hidden" name="product_attr" id="productAttrJson" value="[]">


		<?php
	} else {
		?>
		<input type="hidden" name="product_attr" id="productAttrJson" value='<?php echo $product_attr; ?>'>

		<?php
	}
	?>





	<div class="product-attr-container default">
		<?php
		if ($product_attr_array == true) {
			for ($i = 0; $i < count($product_attr_array); $i++) {

				$allOptions = $product_attr_array[$i]->options;
				?>
				<div class="product-attr mt-2 attr-<?php echo $product_attr_array[$i]->name; ?>"
					data-opname="<?php echo $product_attr_array[$i]->name; ?>">
					<div class="attr-name">
						<div class="row">
							<div class="col">
								<label for="">Attribute Name</label>
								<input type="text" name="attr-title" disabled=""
									value="<?php echo $product_attr_array[$i]->name; ?>" placeholder="Display Option"
									class="form-control">
							</div>
							<div class="col"><label> Attribute Type </label><select type="text"
									value="<?php echo $product_attr_array[$i]->type; ?>" name="attr-type" class="form-select">
									<option value="normal"> Normal </option>
									<option value="flat"> Flat </option>
									<option value="percent"> Percent </option>
									<option value="lft"> Linear Ft. </option>
									<option value="sqft"> Squre Ft. </option>
								</select></div>
							<div class="col"><label for="">Css Class</label> <input type="text" name="css-class"
									value="<?php echo $product_attr_array[$i]->cssClass; ?>" class="form-control"></div>
						</div>



					</div>
					<div class="attibute-options">
						<?php
						foreach ($allOptions as $option) {

							foreach ($option as $name => $price) ?>
							<div class="row opt-row">
								<div class="col">
									<div class="form-group">
										<label for="" class="form-label">Variant Title</label>
										<input type="text" value="<?php echo $name; ?>"
											data-opname="<?php echo $product_attr_array[$i]->name ? trim($product_attr_array[$i]->name) : ''; ?>"
											placeholder="Single Sided" name="attr-name" class="variable-title form-control">
									</div>
								</div>
								<div class="col">
									<div class="form-group">
										<label class="form-label">Variant Price</label>
										<input data-opname="<?php echo $product_attr_array[$i]->name; ?>" type="text" placeholder="10"
											value="<?php echo $price ? trim($price) : 0; ?>" name="attr-price"
											class="variable-price form-control">
									</div>
								</div>
							</div>

						<?php }
						; ?>
					</div>
					<a class="btn btn-primary button-large mt-2 addOptBtn"
						data-opname="<?php echo $product_attr_array[$i]->name; ?>">Add Option</a>
					<a class="btn btn-danger button-large mt-2 removeAttr"
						data-opname="<?php echo $product_attr_array[$i]->name; ?>">Remove Attribute</a>
				</div>

				<?php
			}
		}
		;

		?>

	</div>
	<input type="text" class="attr-title mt-2 mr-2 " placeholder="New Attribute title"><a
		class="button button-success button-large mt-2" id="addAttrBtn">Add attribute</a> <a
		class="button button-primary button-large mt-2 saveOptBtn">Save Options</a>

	<?php
}

// Save meta box data
function save_product_attr_meta($post_id)
{

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;
	if (!current_user_can('edit_post', $post_id))
		return;
	if (isset($_POST['product_attr'])) {

		if ($_POST['product_attr'] == '[{') {
			return;
		} else {
			update_post_meta($post_id, 'product_attr', sanitize_text_field($_POST['product_attr']));
		}
	}
}
add_action('save_post', 'save_product_attr_meta');




// Custom product cl meta box
function custom_cl_product_meta_box()
{
	add_meta_box(
		'product_cl_data',
		'Channel Letter Product Data',
		'render_product_cl_meta_box',
		'product',
		'normal',
		'default'
	);
}
add_action('add_meta_boxes', 'custom_cl_product_meta_box');

function render_product_cl_meta_box($post)
{
	// Retrieve existing values from the database
	$product_cl_data = get_post_meta($post->ID, 'product_cl_data', true);
	//update_post_meta($post->ID, 'product_attr', '[]');

	$product_cl_array = json_decode($product_cl_data);

	if ($product_cl_data == '[{') {
		?>
		<input type="hidden" name="product_cl_data" id="productClJson" value="[]">


		<?php
	} else {
		?>
		<input type="hidden" name="product_cl_data" id="productClJson" value="<?php echo $product_cl_data; ?>">

		<?php
	}
	?>





	<div class="product-cl-container default">
		<?php
		if ($product_cl_array == true) {
			for ($i = 0; $i < count($product_cl_array); $i++) {

				$allOptions = $product_cl_array[$i]->options;
				?>
				<div class="product-attr mt-2 attr-<?php echo $product_cl_array[$i]->id; ?>"
					data-opname="<?php echo $product_cl_array[$i]->id; ?>">
					<div class="attr-name">
						<div class="row">
							<div class="col">
								<label for="">Id</label>
								<input type="text" name="attr-id" disabled="" value="<?php echo $product_cl_array[$i]->id; ?>"
									class="form-control">
							</div>
							<div class="col"><label> Heading </label><input type="text"
									value="<?php echo $product_cl_array[$i]->heading; ?>" name="attr-heading"
									class="form-control" />
							</div>
							<div class="col"><label for="">Cost</label> <input type="text" name="css-class"
									value="<?php echo $product_cl_array[$i]->cost; ?>" class="form-control"></div>

						</div>



					</div>
					<div class="attibute-options">
						<?php
						foreach ($allOptions as $option) {

							foreach ($option as $name => $price) ?>
							<div class="row opt-row">
								<div class="col">
									<div class="form-group">
										<label for="" class="form-label">Variant Title</label>
										<input type="text" value="<?php echo $name; ?>"
											data-opname="<?php echo $product_cl_array[$i]->id; ?>" placeholder="Single Sided"
											name="attr-name" class="variable-title form-control">
									</div>
								</div>
								<div class="col">
									<div class="form-group">
										<label class="form-label">Variant Value</label>
										<input data-opname="<?php echo $product_cl_array[$i]->id; ?>" type="text" placeholder="10"
											value="<?php echo $price ? $price : 0; ?>" name="attr-price"
											class="variable-price form-control">
									</div>
								</div>
							</div>

						<?php }
						; ?>
					</div>
					<a class="btn btn-primary button-large mt-2 addOptBtn"
						data-opname="<?php echo $product_cl_array[$i]->id; ?>">Add Option</a>
					<a class="btn btn-danger button-large mt-2 removeAttr"
						data-opname="<?php echo $product_cl_array[$i]->id; ?>">Remove Attribute</a>
				</div>

				<?php
			}
		}
		;

		?>

	</div>
	<input type="text" class="attr-title mt-2 mr-2 " placeholder="New Attribute title">
	<a class="button button-success button-large mt-2" id="addClAttrBtn">Add attribute</a> <a
		class="button button-primary button-large mt-2 saveClBtn">Save Options</a>

	<?php
}

// Save meta box data
function save_product_cl_meta($post_id)
{

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;
	if (!current_user_can('edit_post', $post_id))
		return;
	if (isset($_POST['product_cl_data'])) {

		if ($_POST['product_cl_data'] == '[{') {
			return;
		} else {
			update_post_meta($post_id, 'product_cl_data', sanitize_text_field($_POST['product_cl_data']));
		}
	}
}
add_action('save_post', 'save_product_cl_meta');

function custom_cron_intervals($schedules)
{
	$schedules['every_five_minutes'] = array(
		'interval' => 300, // 300 seconds = 5 minutes
		'display' => __('Every 5 Minutes'),
	);
	return $schedules;
}
add_filter('cron_schedules', 'custom_cron_intervals');



function schedule_image_deletion()
{
	if (!wp_next_scheduled('delete_scheduled_images')) {
		wp_schedule_event(time(), 'yearly', 'delete_scheduled_images'); // Use 'every_five_minutes' for custom intervals
	}
}
add_action('wp', 'schedule_image_deletion');

function delete_scheduled_images()
{
	// Get the current time
	function delete_old_uploaded_images($title)
	{
		// Query to get attachment by its title

		$current_time = current_time('timestamp');

		// Set the time threshold (e.g., 30 days ago)
		$time_threshold = strtotime('-30 Days', $current_time);

		$args = array(
			'post_type' => 'attachment',  // Specify that we are looking for attachments
			'post_status' => 'inherit',     // Attachments have an 'inherit' status
			's' => $title,        // Title of the attachment to search for
			'date_query' => array(
				array(
					'column' => 'post_date',
					'before' => date('Y-m-d H:i:s', $time_threshold),
				),
			),
		);

		// Get the attachment(s)
		$attachments = get_posts($args);

		// Check if attachment is found
		if (!empty($attachments)) {
			// Return the attachment (or you can loop if you allow multiple results)
			if ($attachments) {
				foreach ($attachments as $attachment) {
					$attachment_id = $attachment->ID;
					wp_delete_attachment($attachment_id, true);
				}
			}
		}
	}

	delete_old_uploaded_images('custom-artwork');
	delete_old_uploaded_images('clDesign');
}

add_action('delete_scheduled_images', 'delete_scheduled_images');


// Add an image field to add new taxonomy term
function add_taxonomy_image_field()
{ ?>

	<div class="form-field">
		<label for="taxonomy-ref"><?php _e('Reference Category Slug', 'wholesale'); ?></label>
		<input type="text" id="taxonomy-ref" name="taxonomy-ref" value="" />
		<p class="description"><?php _e('', 'wholesale'); ?></p>
	</div>

	<div class="form-field">
		<label for="taxonomy-image"><?php _e('Category Image', 'wholesale'); ?></label>
		<input type="text" id="taxonomy-image" name="taxonomy-image" value="" />
		<p class="description"><?php _e('Upload an image for this term.', 'wholesale'); ?></p>
		<button class="button button-secondary upload_image_button">Upload Image</button>
	</div>
	<script>
		jQuery(document).ready(function ($) {
			var mediaUploader;
			$('.upload_image_button').click(function (e) {
				e.preventDefault();
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}
				mediaUploader = wp.media.frames.file_frame = wp.media({
					title: 'Choose Image',
					button: {
						text: 'Choose Image'
					},
					multiple: false
				});
				mediaUploader.on('select', function () {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					$('#taxonomy-image').val(attachment.url);
				});
				mediaUploader.open();
			});
		});
	</script>
<?php }
add_action('category_add_form_fields', 'add_taxonomy_image_field', 10, 2);
add_action('product_category_add_form_fields', 'add_taxonomy_image_field', 10, 2);

// Add an image field to edit taxonomy term
function edit_taxonomy_image_field($term)
{
	$term_id = $term->term_id;
	$image_url = get_term_meta($term_id, 'taxonomy-image', true);
	$taxonomy_ref = get_term_meta($term_id, 'taxonomy-ref', true);

	?>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="taxonomy-ref"><?php _e('Reference Category', 'wholesale'); ?></label>
		</th>
		<td>
			<input type="text" id="taxonomy-ref" name="taxonomy-ref" value="<?php echo esc_html($taxonomy_ref); ?>" />
			<p class="description"><?php _e('', 'wholesale'); ?></p>

		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="taxonomy-image"><?php _e('Image', 'wholesale'); ?></label>
		</th>
		<td>
			<input type="text" id="taxonomy-image" name="taxonomy-image" value="<?php echo esc_attr($image_url); ?>" />
			<p class="description"><?php _e('Upload an image for this term.', 'wholesale'); ?></p>
			<button class="button button-secondary upload_image_button">Upload Image</button>
			<?php if ($image_url): ?>
				<br><img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width:150px;margin-top:10px;" />
			<?php endif; ?>
		</td>
	</tr>
	<script>
		jQuery(document).ready(function ($) {
			var mediaUploader;
			$('.upload_image_button').click(function (e) {
				e.preventDefault();
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}
				mediaUploader = wp.media.frames.file_frame = wp.media({
					title: 'Choose Image',
					button: {
						text: 'Choose Image'
					},
					multiple: false
				});
				mediaUploader.on('select', function () {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					$('#taxonomy-image').val(attachment.url);
				});
				mediaUploader.open();
			});
		});
	</script>
<?php }
add_action('category_edit_form_fields', 'edit_taxonomy_image_field', 10, 2);
add_action('product_category_edit_form_fields', 'edit_taxonomy_image_field', 10, 2);


// Save the image field
function save_taxonomy_image_field($term_id)
{
	if (isset($_POST['taxonomy-image'])) {
		update_term_meta($term_id, 'taxonomy-image', esc_url_raw($_POST['taxonomy-image']));
	}
	if (isset($_POST['taxonomy-ref'])) {
		update_term_meta($term_id, 'taxonomy-ref', esc_html($_POST['taxonomy-ref']));
	}
}
add_action('edited_category', 'save_taxonomy_image_field', 10, 2);
add_action('create_category', 'save_taxonomy_image_field', 10, 2);
add_action('edited_product_category', 'save_taxonomy_image_field', 10, 2);
add_action('create_product_category', 'save_taxonomy_image_field', 10, 2);


add_action('rest_api_init', function () {
	register_rest_route('connect/v1', '/send-mail', [
		'methods' => 'POST',
		'callback' => 'handle_connect_mail',
		'permission_callback' => '__return_true',
	]);
});

function handle_connect_mail($request)
{
	$params = $request->get_json_params();

	$name = isset($params['name']) ? sanitize_text_field(wp_unslash($params['name'])) : '';
	$email = isset($params['email']) ? sanitize_email(wp_unslash($params['email'])) : '';
	$message = isset($params['message']) ? sanitize_textarea_field(wp_unslash($params['message'])) : '';
	$subject = isset($params['subject']) ? sanitize_text_field(wp_unslash($params['subject'])) : __('New connect inquiry', 'litsign');

	if (!$name || !is_email($email) || !$message) {
		return new WP_REST_Response(['success' => false, 'message' => 'Missing or invalid fields.'], 400);
	}

	$to = wholesale_contact_admin_recipients();
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		"Reply-To: $name <$email>",
	);
	$body = "Name: $name<br>Email: $email<br><br>Message:<br>$message";

	$sent = wp_mail($to, $subject, $body, $headers);

	if ($sent) {
		return new WP_REST_Response(['success' => true, 'message' => 'Mail sent.'], 200);
	} else {
		return new WP_REST_Response(['success' => false, 'message' => 'Mail failed.', 'params' => $params], 500);
	}
}

add_action('rest_api_init', function () {
	register_rest_route('portfolio/v1', '/send-mail', [
		'methods' => 'POST',
		'callback' => 'handle_portfolio_mail',
		'permission_callback' => '__return_true',
	]);
});

function handle_portfolio_mail($request)
{
	$params = $request->get_json_params();

	$name = isset($params['name']) ? sanitize_text_field(wp_unslash($params['name'])) : '';
	$email = isset($params['email']) ? sanitize_email(wp_unslash($params['email'])) : '';
	$message = isset($params['message']) ? sanitize_textarea_field(wp_unslash($params['message'])) : '';
	$subject = isset($params['subject']) ? sanitize_text_field(wp_unslash($params['subject'])) : __('New portfolio inquiry', 'litsign');

	if (!$name || !is_email($email) || !$message) {
		return new WP_REST_Response(['success' => false, 'message' => 'Missing or invalid fields.'], 400);
	}

	$to = wholesale_contact_admin_recipients();
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		"Reply-To: $name <$email>",
	);
	$body = "Name: $name<br>Email: $email<br><br>Message:<br>$message";

	$sent = wp_mail($to, $subject, $body, $headers);

	if ($sent) {
		return new WP_REST_Response(['success' => true, 'message' => 'Mail sent.'], 200);
	} else {
		return new WP_REST_Response(['success' => false, 'message' => 'Mail failed.', 'params' => $params], 500);
	}
}


function allow_cross_origin_requests()
{
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
	header("Access-Control-Allow-Headers: Content-Type");
}
add_action('init', 'allow_cross_origin_requests');






add_action('wp_head', function () {
    if (is_page('thank-you')) {
        ?>
        <!-- Event snippet for Purchase conversion page -->
<script>
  gtag('event', 'conversion', {
      'send_to': 'AW-18454059893/UkFKCJGGp_kcEPW2yt9E',
      'value': 1.0,
      'currency': 'USD',
      'transaction_id': ''
      // 'new_customer': true /* calculate dynamically, populate with true/false */,
  });
</script>

        <?php
    }
});

/**
 * Basic hardening: this site was compromised through admin access, so remove
 * the common brute-force and account-discovery entry points.
 */
if (!defined('DISALLOW_FILE_EDIT')) {
	// Stops a stolen admin login from editing theme/plugin PHP in wp-admin.
	define('DISALLOW_FILE_EDIT', true);
}

add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_empty_array');

// Hide usernames from anonymous REST requests (/wp-json/wp/v2/users).
add_filter('rest_endpoints', function ($endpoints) {
	if (!is_user_logged_in()) {
		unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);
	}

	return $endpoints;
});

// Block ?author=N scans, which redirect to /author/<username>/.
add_action('template_redirect', function () {
	if (!is_admin() && isset($_GET['author']) && !is_user_logged_in()) {
		wp_safe_redirect(home_url('/'), 301);
		exit;
	}
}, 1);
