<?php
/**
 * Storefront Sign (own top-level admin menu, admin.php?page=retails-settings;
 * it was under Settings until 2026-11-20). One form (options.php, group retails_settings)
 * split into panels with a side menu; css/admin-settings.css and
 * js/admin-settings.js add the tabs, search, image pickers and the save bar.
 * Without JavaScript every panel shows, one after the other.
 *
 * Field names are the option names, so they must not change.
 */

if (!defined('ABSPATH')) {
	exit;
}

function retails_settings_panels()
{
	return array(
		'general' => array('Store', 'dashicons-store'),
		'images' => array('Images', 'dashicons-format-image'),
		'sales' => array('Sale promotion', 'dashicons-tag'),
		'reviews' => array('Reviews', 'dashicons-star-filled'),
		'emails' => array('Customer emails', 'dashicons-email-alt'),
		'tickets' => array('Payment tickets', 'dashicons-media-text'),
		'payments' => array('Payments & checkout', 'dashicons-cart'),
		'marketing' => array('Google Ads & Analytics', 'dashicons-chart-line'),
		'domains' => array('Domain redirects', 'dashicons-randomize'),
		'media-sync' => array('Media sync', 'dashicons-update'),
	);
}

/**
 * One image picker card. $args: name, value (attachment ID), title, help,
 * preview (URL of what is shown now), fallback (URL shown when cleared, or ''),
 * fallback_label, source ('library', 'theme' or 'none'), ratio (CSS).
 */
function retails_settings_image_field($args)
{
	$args = wp_parse_args($args, array(
		'name' => '',
		'value' => 0,
		'title' => '',
		'help' => '',
		'preview' => '',
		'fallback' => '',
		'fallback_label' => 'Not set',
		'source' => 'none',
		'ratio' => '12 / 5',
		'contain' => false,
	));
	$badges = array(
		'library' => array('Media Library', 'is-ok'),
		'default' => array('Default image', 'is-info'),
		'theme' => array('Built-in theme file', 'is-info'),
		'none' => array('Not set', 'is-warn'),
	);
	$badge = $badges[$args['source']] ?? $badges['none'];
	?>
	<div class="wss-image" data-wss-image data-fallback="<?php echo esc_url($args['fallback']); ?>" data-fallback-label="<?php echo esc_attr($args['fallback_label']); ?>" data-title="<?php echo esc_attr(wp_strip_all_tags($args['title'])); ?>">
		<input type="hidden" name="<?php echo esc_attr($args['name']); ?>" id="<?php echo esc_attr($args['name']); ?>" value="<?php echo esc_attr((string) (int) $args['value']); ?>">
		<div class="wss-image__frame<?php echo $args['contain'] ? ' is-contain' : ''; ?>" style="aspect-ratio: <?php echo esc_attr($args['ratio']); ?>;">
			<img src="<?php echo esc_url($args['preview']); ?>" alt="" <?php echo $args['preview'] ? '' : 'hidden'; ?>>
			<span class="wss-image__empty" <?php echo $args['preview'] ? 'hidden' : ''; ?>><span class="dashicons dashicons-format-image" aria-hidden="true"></span>No image</span>
		</div>
		<div class="wss-image__body">
			<div class="wss-image__title">
				<strong><?php echo esc_html($args['title']); ?></strong>
				<span class="wss-badge <?php echo esc_attr($badge[1]); ?>" data-wss-badge><?php echo esc_html($badge[0]); ?></span>
			</div>
			<p class="description"><?php echo wp_kses_post($args['help']); ?></p>
			<div class="wss-image__actions">
				<button type="button" class="button button-secondary" data-wss-choose><?php echo $args['value'] ? 'Replace image' : 'Upload or choose'; ?></button>
				<button type="button" class="button-link button-link-delete" data-wss-remove <?php echo $args['value'] ? '' : 'hidden'; ?>><?php echo esc_html($args['fallback'] ? 'Use ' . strtolower($args['fallback_label']) : 'Remove'); ?></button>
			</div>
		</div>
	</div>
	<?php
}

function retails_settings_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$panels = retails_settings_panels();
	$promo_now = function_exists('retails_promotion_window') ? retails_promotion_window() : null;
	$mail_status = retails_mail_status();
	$google_status = retails_google_reviews();
	$missing_images = retails_missing_image_slots();

	// Overview chips: what is on, what needs attention.
	if (retails_setting_enabled('payment_disabled')) {
		$pay_chip = array('Card payments off', 'is-warn', 'payments');
	} elseif (retails_setting_enabled('payment_test_mode')) {
		$pay_chip = array('Payments in test mode', 'is-warn', 'payments');
	} else {
		$pay_chip = array('Card payments live', 'is-ok', 'payments');
	}
	$mail_chip = 'smtp' === $mail_status['mode'] ? array('Email: SMTP', 'is-ok', 'emails')
		: ('default' === $mail_status['mode'] ? array('Email: default mailer', 'is-info', 'emails') : array('Email: SMTP failing', 'is-bad', 'emails'));
	$chips = array(
		$pay_chip,
		$promo_now ? array(sprintf('Sale: %s%% off', $promo_now['percent']), 'is-ok', 'sales') : array('No sale running', 'is-info', 'sales'),
		$mail_chip,
		$google_status ? (!empty($google_status['error']) ? array('Google reviews: refresh failed', 'is-bad', 'reviews') : array(sprintf('Google: %s ★', number_format((float) $google_status['rating'], 1)), 'is-ok', 'reviews')) : array('Google reviews not connected', 'is-info', 'reviews'),
		$missing_images ? array(sprintf('%d image%s missing', count($missing_images), count($missing_images) > 1 ? 's' : ''), 'is-bad', 'images') : array('All images set', 'is-ok', 'images'),
	);
	?>
	<div class="wrap wss">
		<header class="wss-header">
			<div>
				<h1>Storefront Sign Settings</h1>
				<p>Website defaults that take effect as soon as you save, without editing theme files.</p>
			</div>
			<div class="wss-header__links">
				<a class="button" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-external" aria-hidden="true"></span> View site</a>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=retails-emails-smtp')); ?>"><span class="dashicons dashicons-email" aria-hidden="true"></span> Email settings</a>
				<a class="button button-primary" href="<?php echo esc_url(retails_theme_zip_url()); ?>" title="Installable zip of the theme, without development files"><span class="dashicons dashicons-download" aria-hidden="true"></span> Download theme zip</a>
			</div>
		</header>
		<hr class="wp-header-end">
		<?php settings_errors(); // Under Settings, options-head.php printed "Settings saved."; a top-level page must do it itself. ?>

		<?php if (isset($_GET['images_imported'])) : ?>
			<?php $imported = absint($_GET['images_imported']); $failed = absint($_GET['images_failed'] ?? 0); ?>
			<div class="notice <?php echo $failed ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html(sprintf('%d image%s moved to the Media Library.', $imported, 1 === $imported ? '' : 's') . ($failed ? sprintf(' %d could not be copied.', $failed) : '')); ?></p></div>
		<?php endif; ?>

		<ul class="wss-chips" aria-label="Status">
			<?php foreach ($chips as $chip) : ?>
				<li><a class="wss-chip <?php echo esc_attr($chip[1]); ?>" href="#<?php echo esc_attr($chip[2]); ?>" data-wss-goto="<?php echo esc_attr($chip[2]); ?>"><?php echo esc_html($chip[0]); ?></a></li>
			<?php endforeach; ?>
		</ul>

		<form action="options.php" method="post" class="wss-layout" id="wss-form">
			<?php settings_fields('retails_settings'); ?>

			<nav class="wss-nav" aria-label="Settings sections">
				<label class="screen-reader-text" for="wss-search">Search settings</label>
				<input type="search" id="wss-search" class="wss-search" placeholder="Search settings…" autocomplete="off">
				<ul role="tablist">
					<?php foreach ($panels as $slug => $panel) : ?>
						<li><a href="#<?php echo esc_attr($slug); ?>" role="tab" data-wss-tab="<?php echo esc_attr($slug); ?>" aria-controls="wss-panel-<?php echo esc_attr($slug); ?>"><span class="dashicons <?php echo esc_attr($panel[1]); ?>" aria-hidden="true"></span><?php echo esc_html($panel[0]); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<div class="wss-panels">
				<p class="wss-no-results" hidden>No settings match your search.</p>

				<section class="wss-panel" id="wss-panel-general" data-wss-panel="general" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head"><h2>Brand colors</h2><p>Used for buttons, links, star ratings and sale prices across the site.</p></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_primary_color">Primary color</label></th><td><input type="color" id="retails_primary_color" name="retails_primary_color" value="<?php echo esc_attr(retails_get_setting('primary_color')); ?>"></td></tr>
							<tr><th><label for="retails_accent_color">Rating/accent color</label></th><td><input type="color" id="retails_accent_color" name="retails_accent_color" value="<?php echo esc_attr(retails_get_setting('accent_color')); ?>"></td></tr>
							<tr><th><label for="retails_sale_price_color">Sale price color</label></th><td><input type="color" id="retails_sale_price_color" name="retails_sale_price_color" value="<?php echo esc_attr(retails_get_setting('sale_price_color')); ?>"></td></tr>
						</table>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Page loader</h2></div>
						<table class="form-table" role="presentation">
							<tr><th scope="row">Enable page loader</th><td>
								<input type="hidden" name="retails_page_loader_enabled" value="0">
								<label class="wss-toggle"><input type="checkbox" name="retails_page_loader_enabled" value="1" <?php checked(retails_setting_enabled('page_loader_enabled')); ?>> Show the animated sign loader on internal page navigation</label>
								<p class="description">Disabled by default. Add <code>no-page-loader</code> or <code>data-no-loader</code> to a link or one of its ancestors to exclude it.</p>
							</td></tr>
						</table>
					</div>
					<?php if (function_exists('retails_motion_enabled')) : ?>
					<div class="wss-card">
						<div class="wss-card__head" id="motion-effects"><h2>Motion effects</h2></div>
						<table class="form-table" role="presentation">
							<tr><th scope="row">Enable motion effects</th><td>
								<input type="hidden" name="retails_motion_enabled" value="0">
								<label class="wss-toggle"><input type="checkbox" name="retails_motion_enabled" value="1" <?php checked(retails_motion_enabled()); ?>> Scroll reveals, hover and tap effects</label>
								<p class="description">Visitors whose device asks for less motion never see them. Cart, checkout, account and order pages only get button and focus effects, and the channel letter builder gets none. Turn this off to go back to the site exactly as it was.</p>
							</td></tr>
						</table>
					</div>
					<?php endif; ?>
					<?php if (function_exists('retails_es_setting')) : ?>
					<div class="wss-card">
						<div class="wss-card__head" id="exit-survey"><h2>Exit survey</h2></div>
						<table class="form-table" role="presentation">
							<tr><th scope="row">Enable exit survey</th><td>
								<input type="hidden" name="retails_exit_survey_enabled" value="0">
								<label class="wss-toggle"><input type="checkbox" name="retails_exit_survey_enabled" value="1" <?php checked((bool) retails_es_setting('enabled')); ?>> Show the &ldquo;Before you go, what stopped you from ordering today?&rdquo; popup to visitors about to leave</label>
								<p class="description">Answers, phone/tablet and timing options are under <a href="<?php echo esc_url(admin_url('admin.php?page=retails-es')); ?>">Visitor Insights &rsaquo; Exit Survey</a>. This is the same switch as the one there.</p>
							</td></tr>
						</table>
					</div>
					<?php endif; ?>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Certification</h2></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_ul_file_number">UL file number</label></th><td><input class="regular-text" id="retails_ul_file_number" name="retails_ul_file_number" value="<?php echo esc_attr(retails_get_setting('ul_file_number')); ?>" placeholder="E123456"><p class="description">Shown on the sign permit, installation and warranty pages so inspectors and customers can look it up. Leave empty to hide it.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-images" data-wss-panel="images" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="images"><h2>Site images</h2><p>These photos are stored in the <a href="<?php echo esc_url(admin_url('upload.php')); ?>">Media Library</a> rather than inside the theme, which keeps the theme small. Upload a new image or pick one already in the library, then save.</p></div>
						<?php if ($missing_images) : ?>
							<div class="notice notice-warning inline"><p><strong>Missing:</strong> <?php echo esc_html(implode(', ', $missing_images)); ?>. Those spots show no image until one is chosen.</p></div>
						<?php endif; ?>
						<div class="wss-image-grid">
							<?php
							$logo_id = (int) get_theme_mod('custom_logo');
							$logo_id = $logo_id && wp_attachment_is_image($logo_id) ? $logo_id : 0;
							$logo_default = retails_image_default_id('site_logo');
							$logo_default_url = $logo_default ? wp_get_attachment_image_url($logo_default, 'medium_large') : '';
							retails_settings_image_field(array(
								'name' => 'retails_site_logo',
								'value' => $logo_id,
								'title' => 'Site logo',
								'help' => 'Header, footer and the ads landing pages. A wide PNG, SVG or WebP with a transparent background, at least 800 px wide. Without one, the default logo is shown (or the site name as text). Same setting as Appearance &rsaquo; Customize &rsaquo; Site Identity.',
								'preview' => $logo_id ? wp_get_attachment_image_url($logo_id, 'medium_large') : $logo_default_url,
								'fallback' => $logo_default_url,
								'fallback_label' => $logo_default_url ? 'Default image' : 'Not set',
								'source' => $logo_id ? 'library' : ($logo_default_url ? 'default' : 'none'),
								'ratio' => '4 / 1',
								'contain' => true,
							));

							$hero_image_id = (int) retails_get_setting('default_hero_image');
							$hero_built_in = get_template_directory_uri() . '/img/hero-1920.webp';
							retails_settings_image_field(array(
								'name' => 'retails_default_hero_image',
								'value' => $hero_image_id,
								'title' => 'Default hero image',
								'help' => 'Hero background for product categories and pages that have no image of their own. The home page and the Channel Letters page use the Channel Letters page\'s featured image instead. Best size: at least 2400 &times; 1000 px, landscape, with the main subject on the right and a darker, quiet left side where the headline sits. Phones show the right-hand part of the photo. A smaller AVIF copy is made automatically a few seconds after the first page view.',
								'preview' => $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'medium_large') : $hero_built_in,
								'fallback' => $hero_built_in,
								'fallback_label' => 'Built-in photo',
								'source' => $hero_image_id ? 'library' : 'theme',
							));

							$can_import = false;
							foreach (retails_image_slots() as $key => $slot) {
								$id = retails_image_slot_id($key);
								$default = retails_image_default_id($key);
								$legacy = retails_image_slot_legacy($key);
								$can_import = $can_import || (!$id && !$default && $legacy);
								$fallback = $default ? wp_get_attachment_image_url($default, 'medium_large') : ($legacy ? $legacy[1] : '');
								retails_settings_image_field(array(
									'name' => retails_image_slot_option($key),
									'value' => $id,
									'title' => $slot['label'],
									'help' => $slot['help'],
									'preview' => $id ? wp_get_attachment_image_url($id, 'medium_large') : $fallback,
									'fallback' => $fallback,
									'fallback_label' => $default ? 'Default image' : ($legacy ? 'Built-in file' : 'Not set'),
									'source' => $id ? 'library' : ($default ? 'default' : ($legacy ? 'theme' : 'none')),
								));
							}
							?>
						</div>
						<?php if ($can_import) : ?>
							<p class="wss-import"><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=retails_import_theme_images'), 'retails_import_theme_images')); ?>">Move built-in images to the Media Library</a> <span class="description">Copies the theme's own files for the empty spots above into the Media Library, so they can be removed from the theme.</span></p>
						<?php endif; ?>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-sales" data-wss-panel="sales" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="sale-promotion">
							<h2>Sale promotion <span class="wss-badge <?php echo $promo_now ? 'is-ok' : 'is-info'; ?>"><?php echo $promo_now ? 'Running' : 'Off'; ?></span></h2>
							<p>Products always sell at their everyday price, with no sale badge or crossed-out price. A sale set here takes its percentage off the everyday price, in the cart and on every page, only from the start date through the end date (Pacific time). During the sale, pages show the everyday price crossed out, the sale price and a &ldquo;Sale x% off&rdquo; badge. Run a sale only when the everyday price is what customers actually paid before it. After you change these settings, clear the page cache so cached pages update.</p>
						</div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_promo_name">Sale name</label></th><td><input type="text" class="regular-text" maxlength="40" id="retails_promo_name" name="retails_promo_name" value="<?php echo esc_attr(retails_get_setting('promo_name')); ?>" placeholder="Black Friday Sale"> <p class="description">Shown on product pages and the channel letter builder, e.g. &ldquo;Black Friday Sale: 15% off, ends Nov 30&rdquo;. Leave empty to say &ldquo;Sale&rdquo;.</p></td></tr>
							<tr><th><label for="retails_promo_percent">Sale percentage</label></th><td><input type="number" class="small-text" min="0" max="90" step="0.01" id="retails_promo_percent" name="retails_promo_percent" value="<?php echo esc_attr(retails_get_setting('promo_percent')); ?>"> % off <p class="description">0 turns the sale off.</p></td></tr>
							<tr><th><label for="retails_promo_start">Starts</label></th><td><input type="date" id="retails_promo_start" name="retails_promo_start" value="<?php echo esc_attr(retails_get_setting('promo_start')); ?>"></td></tr>
							<tr><th><label for="retails_promo_end">Ends (last day of the sale)</label></th><td><input type="date" id="retails_promo_end" name="retails_promo_end" value="<?php echo esc_attr(retails_get_setting('promo_end')); ?>"></td></tr>
							<tr><th><label for="retails_promo_scope">Products</label></th><td><select id="retails_promo_scope" name="retails_promo_scope">
								<?php foreach (array('all' => 'All products', 'channel-letters' => 'Channel letters only', 'other' => 'Everything except channel letters') as $scope => $label) : ?>
									<option value="<?php echo esc_attr($scope); ?>" <?php selected(retails_get_setting('promo_scope'), $scope); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php echo $promo_now ? esc_html(sprintf('%s: %s%% off is running now, through %s.', retails_promotion_label($promo_now), $promo_now['percent'], retails_promotion_end_text($promo_now))) : 'No sale is running now.'; ?></p></td></tr>
						</table>
					</div>
					<?php $ship_code_now = function_exists('retails_ship_code_window') ? retails_ship_code_window() : ''; ?>
					<div class="wss-card">
						<div class="wss-card__head" id="free-shipping-code">
							<h2>Free shipping promo code <span class="wss-badge <?php echo $ship_code_now ? 'is-ok' : 'is-info'; ?>"><?php echo $ship_code_now ? 'Running' : 'Off'; ?></span></h2>
							<p>A code customers type in the checkout&rsquo;s order summary. It makes standard shipping free on orders below the free shipping minimum, from the start date through the end date (Pacific time). The order&rsquo;s shipping line names the code. While a code is set, the Merchant Center feed tags the items that don&rsquo;t already ship free with promotion id <code>freeship_&lt;code&gt;</code>, for a Merchant Center promotion with the same id.</p>
						</div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_ship_code">Code</label></th><td><input type="text" class="regular-text" maxlength="30" id="retails_ship_code" name="retails_ship_code" value="<?php echo esc_attr(retails_get_setting('ship_code')); ?>" placeholder="BLACKFRIDAY" style="text-transform: uppercase"> <p class="description">Letters, numbers, - and _. Empty turns the code off.</p></td></tr>
							<tr><th><label for="retails_ship_code_start">Starts</label></th><td><input type="date" id="retails_ship_code_start" name="retails_ship_code_start" value="<?php echo esc_attr(retails_get_setting('ship_code_start')); ?>"> <p class="description">Empty: works as soon as you save.</p></td></tr>
							<tr><th><label for="retails_ship_code_end">Ends (last day)</label></th><td><input type="date" id="retails_ship_code_end" name="retails_ship_code_end" value="<?php echo esc_attr(retails_get_setting('ship_code_end')); ?>">
							<p class="description"><?php echo $ship_code_now ? esc_html(sprintf('Code %s works at checkout now%s.', $ship_code_now, retails_get_setting('ship_code_end') ? ', through ' . wp_date('M j', strtotime(retails_get_setting('ship_code_end') . ' 12:00'), new DateTimeZone('America/Los_Angeles')) : '')) : 'No code works at checkout now.'; ?></p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-reviews" data-wss-panel="reviews" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head"><h2>Reviews</h2><p>Approve and reply to reviews under <a href="<?php echo esc_url(admin_url('edit.php?post_type=review_submission')); ?>">Customer Reviews</a>.</p></div>
						<table class="form-table" role="presentation">
							<?php foreach (array(
								'show_product_ratings' => 'Show ratings on product cards',
								'show_product_reviews' => 'Show reviews on product pages',
								'allow_verified_reviews' => 'Allow reviews from customers with completed orders',
								'auto_publish_reviews' => 'Publish customer reviews automatically',
								'schema_imported_reviews' => 'Send imported reviews to Google as star ratings (only if they are genuine customer reviews)',
							) as $key => $label) : ?>
								<tr><th><?php echo esc_html($label); ?></th><td><input type="hidden" name="retails_<?php echo esc_attr($key); ?>" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_<?php echo esc_attr($key); ?>" value="1" <?php checked(retails_setting_enabled($key)); ?>> Enabled</label></td></tr>
							<?php endforeach; ?>
						</table>
					</div>
					<?php if (function_exists('retails_review_request_summary')) : ?>
						<?php $rr = retails_review_request_summary(); ?>
						<div class="wss-card">
							<div class="wss-card__head" id="review-requests"><h2>Review request email</h2><p>One email per order, a few days after it arrives, asking the customer to review each item (no account needed) and your business on Google. Product reviews you approve give product pages their star rating in Google search results.</p></div>
							<table class="form-table" role="presentation">
								<tr><th>Review request email</th><td><input type="hidden" name="retails_review_request_email" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_review_request_email" value="1" <?php checked(retails_setting_enabled('review_request_email')); ?>> Enabled</label><p class="description">Needs &ldquo;Allow reviews from customers with completed orders&rdquo; above. Orders with a delivery issue, returned, cancelled or refunded, and orders whose items were all reviewed, get no email.</p></td></tr>
								<tr><th><label for="retails_review_request_days">Send</label></th><td><input type="number" min="1" max="60" step="1" class="small-text" id="retails_review_request_days" name="retails_review_request_days" value="<?php echo esc_attr((string) retails_get_setting('review_request_days')); ?>"> days after delivery<p class="description">Orders without delivery tracking count as delivered 7 days after they ship. Channel letter orders wait 7 extra days for installation. Emails go out between 9am and 6pm Pacific.</p></td></tr>
								<tr><th>Status</th><td><p><?php
									echo esc_html(sprintf('%d sent in the last 30 days. %d due now, %d coming up%s.', $rr['sent_30'], $rr['due_now'], $rr['upcoming'], $rr['next'] ? ' (next ' . wp_date('M j', $rr['next']) . ')' : ''));
									if (!retails_review_requests_can_send()) {
										echo ' ' . esc_html('This copy of the site is not storefrontsignonline.com, so it never sends them.');
									}
								?></p></td></tr>
							</table>
						</div>
					<?php endif; ?>
					<div class="wss-card">
						<div class="wss-card__head" id="google-reviews"><h2>Google reviews</h2><p>Shows your Google Business rating and latest reviews in the site-wide review slider, next to reviews you approve under <a href="<?php echo esc_url(admin_url('edit.php?post_type=review_submission')); ?>">Customer Reviews</a>.</p></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_google_places_api_key">Google Places API key</label></th><td><input type="password" class="regular-text" id="retails_google_places_api_key" name="retails_google_places_api_key" value="<?php echo esc_attr(retails_get_setting('google_places_api_key')); ?>" autocomplete="new-password" data-1p-ignore data-lpignore="true" spellcheck="false"><p class="description">Create it in Google Cloud Console with the <strong>Places API (New)</strong> enabled, and restrict it to that API. It starts with <code>AIza</code>; anything else is not saved.</p></td></tr>
							<tr><th><label for="retails_google_place_id">Google Place ID</label></th><td><input class="regular-text" id="retails_google_place_id" name="retails_google_place_id" value="<?php echo esc_attr(retails_get_setting('google_place_id')); ?>" placeholder="ChIJ..."><p class="description">Find it with Google's <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener">Place ID finder</a>.</p></td></tr>
							<tr><th><label for="retails_google_reviews_min_rating">Show reviews rated</label></th><td><select id="retails_google_reviews_min_rating" name="retails_google_reviews_min_rating"><?php foreach (array(5 => '5 stars only', 4 => '4 stars and up', 3 => '3 stars and up', 2 => '2 stars and up', 1 => 'All ratings') as $stars => $label) : ?><option value="<?php echo esc_attr($stars); ?>" <?php selected((int) retails_get_setting('google_reviews_min_rating'), $stars); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><p class="description">Lower-rated Google reviews are left out of the review slider and reviews sections. Your overall Google rating and review count are always shown as Google reports them.</p></td></tr>
							<?php if ($google_status) : ?>
								<tr><th>Status</th><td>
									<?php if (!empty($google_status['error'])) : ?>
										<p style="color:#b32d2e;"><strong>Last refresh failed:</strong> <?php echo esc_html($google_status['error']); ?></p>
									<?php endif; ?>
									<p><?php echo esc_html(sprintf('%s stars from %s reviews; %d shown in the slider. Checked %s ago.', number_format((float) $google_status['rating'], 1), number_format_i18n((int) $google_status['count']), count($google_status['reviews']), human_time_diff((int) $google_status['checked'], time()))); ?></p>
									<p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=retails_refresh_google_reviews'), 'retails_refresh_google_reviews')); ?>">Refresh now</a></p>
								</td></tr>
							<?php endif; ?>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-emails" data-wss-panel="emails" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="email-delivery"><h2>Email delivery</h2></div>
						<p class="wss-status">
							<?php if ('smtp' === $mail_status['mode']) : ?>
								<span class="wss-badge is-ok">SMTP</span> through <code><?php echo esc_html($mail_status['host']); ?></code>.
							<?php elseif ('default' === $mail_status['mode']) : ?>
								<span class="wss-badge is-info">Default WordPress mailer</span> (PHP mail()).
							<?php else : ?>
								<span class="wss-badge is-bad">SMTP is not working</span> <?php echo esc_html($mail_status['problem']); ?>
							<?php endif; ?>
							SMTP settings, test emails and the log of every email sent are under <a href="<?php echo esc_url(admin_url('admin.php?page=retails-emails-smtp')); ?>">Emails</a>.
						</p>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Customer emails</h2></div>
						<table class="form-table" role="presentation">
							<tr><th>Order status emails</th><td><input type="hidden" name="retails_email_status_updates" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_email_status_updates" value="1" <?php checked(retails_setting_enabled('email_status_updates')); ?>> Email customers when their order goes into production, ships (with tracking), is put on hold, cancelled or refunded</label></td></tr>
							<tr><th>Quote request confirmation</th><td><input type="hidden" name="retails_quote_autoreply" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_quote_autoreply" value="1" <?php checked(retails_setting_enabled('quote_autoreply')); ?>> Email customers a confirmation with a copy of their request as soon as they submit a quote form</label><p class="description">Reply to a request from its screen under <a href="<?php echo esc_url(admin_url('edit.php?post_type=contact_submission')); ?>">Contact Submissions</a>.</p></td></tr>
							<tr><th><label for="retails_quote_autoreply_subject">Confirmation subject</label></th><td><input class="large-text" id="retails_quote_autoreply_subject" name="retails_quote_autoreply_subject" value="<?php echo esc_attr(retails_get_setting('quote_autoreply_subject')); ?>"></td></tr>
							<tr><th><label for="retails_quote_autoreply_message">Confirmation message</label></th><td><textarea class="large-text" rows="4" id="retails_quote_autoreply_message" name="retails_quote_autoreply_message"><?php echo esc_textarea(retails_get_setting('quote_autoreply_message')); ?></textarea><p class="description">Placeholders: <code>{name}</code> customer's first name, <code>{store}</code> business name (from Payment tickets), <code>{project}</code> project type. A copy of what they sent is added below the message.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-tickets" data-wss-panel="tickets" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="payment-tickets"><h2>Payment tickets</h2><p>Used by <a href="<?php echo esc_url(admin_url('edit.php?post_type=payment_ticket')); ?>">Payment Tickets</a>: the customer's payment page, the payment request email and new tickets.</p></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_ticket_business_name">Business name</label></th><td><input class="regular-text" id="retails_ticket_business_name" name="retails_ticket_business_name" value="<?php echo esc_attr(retails_get_setting('ticket_business_name')); ?>"></td></tr>
							<tr><th><label for="retails_ticket_business_phone">Phone</label></th><td><input class="regular-text" id="retails_ticket_business_phone" name="retails_ticket_business_phone" value="<?php echo esc_attr(retails_get_setting('ticket_business_phone')); ?>"></td></tr>
							<tr><th><label for="retails_ticket_business_email">Email</label></th><td><input type="email" class="regular-text" id="retails_ticket_business_email" name="retails_ticket_business_email" value="<?php echo esc_attr(retails_get_setting('ticket_business_email')); ?>"><p class="description">Shown to customers and used as the Reply-To address, so their replies reach you.</p></td></tr>
							<tr><th><label for="retails_ticket_business_address">Address</label></th><td><input class="large-text" id="retails_ticket_business_address" name="retails_ticket_business_address" value="<?php echo esc_attr(retails_get_setting('ticket_business_address')); ?>"><p class="description">Shown in the "From" section of the payment page and in the email footer.</p></td></tr>
							<tr><th><label for="retails_ticket_email_subject">Email subject</label></th><td><input class="large-text" id="retails_ticket_email_subject" name="retails_ticket_email_subject" value="<?php echo esc_attr(retails_get_setting('ticket_email_subject')); ?>"><p class="description">Placeholders: <code>{number}</code> ticket number, <code>{title}</code> project name, <code>{amount}</code> total due, <code>{business}</code> business name.</p></td></tr>
							<tr><th>Email new tickets automatically</th><td><input type="hidden" name="retails_ticket_auto_send" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_ticket_auto_send" value="1" <?php checked(retails_setting_enabled('ticket_auto_send')); ?>> Email the customer as soon as a new ticket is saved with a customer, items and a total</label><p class="description">Use "Save draft without sending" on a ticket to keep working on it first. Customers also get a receipt when they pay, and the <a href="<?php echo esc_url(admin_url('admin.php?page=retails-emails-smtp')); ?>">store notification recipients</a> get a blind copy of every payment request and receipt.</p></td></tr>
							<tr><th><label for="retails_ticket_default_message">Default message</label></th><td><textarea class="large-text" rows="3" id="retails_ticket_default_message" name="retails_ticket_default_message" placeholder="e.g. Thanks for choosing us! Production starts once payment is received."><?php echo esc_textarea(retails_get_setting('ticket_default_message')); ?></textarea><p class="description">Pre-filled as the message to the customer on new tickets. You can still change it on each ticket.</p></td></tr>
							<tr><th>Sales tax on new tickets</th><td><input type="hidden" name="retails_ticket_default_tax" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_ticket_default_tax" value="1" <?php checked(retails_setting_enabled('ticket_default_tax')); ?>> Start new tickets with the checkout tax rate (<?php echo esc_html(retails_get_setting('tax_rate')); ?>%)</label></td></tr>
							<tr><th><label for="retails_ticket_due_days">Pay-by period</label></th><td><input type="number" min="0" step="1" class="small-text" id="retails_ticket_due_days" name="retails_ticket_due_days" value="<?php echo esc_attr((string) retails_get_setting('ticket_due_days')); ?>"> days<p class="description">New tickets get a pay-by date this many days ahead, and the link stops accepting payment after it. Use 0 for no pay-by date.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-payments" data-wss-panel="payments" role="tabpanel">
					<?php // Since 2026-11-12 the SFS Wallet Payments plugin edits these options (same names) on its Card tab; its allowed_options filter keeps a save here from emptying them. ?>
					<?php if (function_exists('sfs_wallet_card_settings_url')) : ?>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Card payments <span class="wss-badge <?php echo retails_setting_enabled('payment_disabled') ? 'is-info' : 'is-ok'; ?>"><?php echo esc_html(retails_setting_enabled('payment_disabled') ? 'Off at checkout' : (retails_setting_enabled('payment_test_mode') ? 'Demo' : 'Live')); ?></span></h2><p>Card payment through Elavon Converge (on or off at checkout, card entry, Demo or Live, and the merchant ID, user ID and PIN) is set in the SFS Wallet Payments plugin now, next to Apple Pay, Google Pay and PayPal.</p></div>
						<p><a class="button button-primary" href="<?php echo esc_url(sfs_wallet_card_settings_url()); ?>"><span class="dashicons dashicons-id-alt" aria-hidden="true"></span> Open card payment settings</a></p>
					</div>
					<?php else : ?>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Payment gateway</h2></div>
						<table class="form-table" role="presentation">
							<tr><th>Disable card payment</th><td><input type="hidden" name="retails_payment_disabled" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_payment_disabled" value="1" <?php checked(retails_setting_enabled('payment_disabled')); ?>> Create orders for manual payment instead</label></td></tr>
							<tr><th>Card payment method</th><td>
								<fieldset class="wss-choices">
									<label class="wss-choice"><input type="radio" name="retails_payment_method" value="lightbox" <?php checked('lightbox', retails_payment_method()); ?>> <span><strong>Converge secure window (Lightbox)</strong> &mdash; recommended<span class="description">Customers type their card into Elavon's own secure window, so card numbers never pass through this website. Your Converge user must have <em>Hosted Payments</em> enabled.</span></span></label>
									<label class="wss-choice"><input type="radio" name="retails_payment_method" value="direct" <?php checked('direct', retails_payment_method()); ?>> <span><strong>Card fields on this website (direct)</strong><span class="description">The previous method: card fields on the checkout page, charged by this server through the Converge API. Card numbers pass through (but are never stored on) this server, which puts the site in a stricter PCI compliance scope.</span></span></label>
								</fieldset>
							</td></tr>
							<tr><th>Test mode</th><td><input type="hidden" name="retails_payment_test_mode" value="0"><label class="wss-toggle"><input type="checkbox" name="retails_payment_test_mode" value="1" <?php checked(retails_setting_enabled('payment_test_mode')); ?>> Use the Converge demo endpoint</label></td></tr>
							<tr><th><label for="retails_merchant_id">Merchant ID</label></th><td><input class="regular-text" id="retails_merchant_id" name="retails_merchant_id" value="<?php echo esc_attr(retails_get_setting('merchant_id')); ?>"></td></tr>
							<tr><th><label for="retails_gateway_user_id">Gateway user ID</label></th><td><input class="regular-text" id="retails_gateway_user_id" name="retails_gateway_user_id" value="<?php echo esc_attr(retails_get_setting('gateway_user_id')); ?>"></td></tr>
							<tr><th><label for="retails_gateway_pin">Gateway PIN</label></th><td><input type="password" class="regular-text" id="retails_gateway_pin" name="retails_gateway_pin" value="<?php echo esc_attr(retails_get_setting('gateway_pin')); ?>" autocomplete="new-password"></td></tr>
						</table>
					</div>
					<?php endif; ?>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Checkout defaults</h2></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_tax_rate">Tax rate (%)</label></th><td><input type="number" min="0" step="0.01" id="retails_tax_rate" name="retails_tax_rate" value="<?php echo esc_attr(retails_get_setting('tax_rate')); ?>"></td></tr>
							<tr><th><label for="retails_standard_shipping_options">Standard shipping options</label></th><td><input class="regular-text" id="retails_standard_shipping_options" name="retails_standard_shipping_options" value="<?php echo esc_attr(retails_get_setting('standard_shipping_options')); ?>"><p class="description">Comma-separated amounts, from fastest to slowest.</p></td></tr>
							<tr><th><label for="retails_channel_shipping_options">Channel-letter shipping options</label></th><td><input class="regular-text" id="retails_channel_shipping_options" name="retails_channel_shipping_options" value="<?php echo esc_attr(retails_get_setting('channel_shipping_options')); ?>"><p class="description">Comma-separated amounts, from fastest to slowest.</p></td></tr>
							<tr><th><label for="retails_free_shipping_min">Free shipping minimum ($)</label></th><td><input type="number" min="0" step="0.01" id="retails_free_shipping_min" name="retails_free_shipping_min" value="<?php echo esc_attr(retails_get_setting('free_shipping_min')); ?>"><p class="description">Standard shipping is free when the cart subtotal reaches this amount. 0 turns it off.</p></td></tr>
							<tr><th><label for="retails_free_shipping_channel_min">Channel-letter free shipping minimum ($)</label></th><td><input type="number" min="0" step="0.01" id="retails_free_shipping_channel_min" name="retails_free_shipping_channel_min" value="<?php echo esc_attr(retails_get_setting('free_shipping_channel_min')); ?>"><p class="description">Used instead of the minimum above when the cart has channel letters. 0 turns it off.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-marketing" data-wss-panel="marketing" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="google-ads"><h2>Google Ads</h2><p>Conversion labels for the Google Ads tag <code>AW-18454059893</code>. Each conversion action is sent from one place only, so nothing counts twice.</p></div>
						<?php
						$page_conversions = get_posts(array('post_type' => 'any', 'post_status' => 'any', 'posts_per_page' => 50, 'meta_key' => RETAILS_PAGE_CONVERSION_META, 'orderby' => 'title', 'order' => 'ASC'));
						$used_labels = array_count_values(array_merge(retails_ads_sitewide_labels(), array_map(function ($p) {
							return retails_page_conversion_clean_label(get_post_meta($p->ID, RETAILS_PAGE_CONVERSION_META, true));
						}, $page_conversions)));
						$duplicates = array_keys(array_filter($used_labels, function ($n) {
							return $n > 1;
						}));
						?>
						<?php if ($duplicates) : ?>
							<div class="notice notice-warning inline"><p><strong>Same label in two places:</strong> <code><?php echo esc_html(implode('</code>, <code>', $duplicates)); ?></code>. Purchase, quote, call and click conversions are always sent by their own trigger, so a page box with the same label is skipped. Give each conversion action its own label.</p></div>
						<?php endif; ?>
						<h3>Conversions the theme always sends</h3>
						<table class="widefat striped" role="presentation">
							<thead><tr><th>Conversion</th><th>Label</th><th>When it fires</th></tr></thead>
							<tbody>
								<?php foreach (retails_ads_builtin_conversions() as $conversion) : ?>
									<tr><td><strong><?php echo esc_html($conversion['name']); ?></strong></td><td><code>AW-18454059893/<?php echo esc_html($conversion['label']); ?></code></td><td><?php echo esc_html($conversion['when']); ?></td></tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<h3>Page-view conversions</h3>
						<?php if ($page_conversions) : ?>
							<table class="widefat striped" role="presentation">
								<thead><tr><th>Page</th><th>Label</th><th>When it fires</th></tr></thead>
								<tbody>
									<?php foreach ($page_conversions as $p) : ?>
										<tr><td><a href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>"><?php echo esc_html(get_the_title($p) ?: '(no title)'); ?></a></td><td><code>AW-18454059893/<?php echo esc_html(retails_page_conversion_clean_label(get_post_meta($p->ID, RETAILS_PAGE_CONVERSION_META, true))); ?></code></td><td>Every view of the page, except the view a quote form returns to.</td></tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p class="description">None. Set one in the <strong>Google Ads conversion</strong> box on a page's edit screen, for example a thank-you page. Don't use it on a page ads link to: every ad visit would count.</p>
						<?php endif; ?>
						<h3>Labels you can change</h3>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_google_ads_lead_label">Quote request conversion label</label></th><td><input class="regular-text" id="retails_google_ads_lead_label" name="retails_google_ads_lead_label" value="<?php echo esc_attr(retails_get_setting('google_ads_lead_label')); ?>" placeholder="<?php echo esc_attr(retails_setting_defaults()['google_ads_lead_label']); ?>"><p class="description">Empty uses the default <code><?php echo esc_html(retails_setting_defaults()['google_ads_lead_label']); ?></code>. Sent when someone submits a quote form: the contact page, the sign landing page and the <strong>Channel Letters</strong> page (/custom-channel-letters/). In Google Ads, create a "Submit lead form" conversion action and paste the part after <code>AW-18454059893/</code> from its event snippet.</p></td></tr>
							<?php foreach (retails_ads_click_conversions() as $key => $conversion) : ?>
								<tr><th><label for="retails_<?php echo esc_attr($key); ?>"><?php echo esc_html($conversion['label']); ?></label></th><td><input class="regular-text" id="retails_<?php echo esc_attr($key); ?>" name="retails_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(retails_get_setting($key)); ?>" placeholder="Leave empty to turn off"><p class="description"><?php echo esc_html($conversion['help']); ?> Create it in Google Ads as a <strong>Secondary</strong> conversion action and paste the part after <code>AW-18454059893/</code>.</p></td></tr>
							<?php endforeach; ?>
						</table>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Google Analytics</h2></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="retails_ga4_measurement_id">Google Analytics 4 measurement ID</label></th><td><input class="regular-text" id="retails_ga4_measurement_id" name="retails_ga4_measurement_id" value="<?php echo esc_attr(retails_get_setting('ga4_measurement_id')); ?>" placeholder="G-XXXXXXXXXX"><p class="description">From Google Analytics: Admin &gt; Data streams &gt; your web stream. Purchases, checkouts and quote requests are then also sent to Analytics. Leave empty to turn off.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-domains" data-wss-panel="domains" role="tabpanel">
					<?php
					$redirect_on = retails_setting_enabled('domain_redirect_enabled');
					$redirect_rules = retails_domain_redirect_rules();
					$redirect_target = retails_domain_redirect_target();
					$redirect_status = (string) retails_get_setting('domain_redirect_status');
					?>
					<div class="wss-card">
						<div class="wss-card__head" id="domain-redirects">
							<h2>Other domains <span class="wss-badge <?php echo $redirect_on && $redirect_rules ? 'is-ok' : 'is-info'; ?>"><?php echo $redirect_on && $redirect_rules ? esc_html(sprintf('On, %d domain%s', count($redirect_rules), 1 === count($redirect_rules) ? '' : 's')) : 'Off'; ?></span></h2>
							<p>Visitors who open this site through one of your other domains are sent to <code><?php echo esc_html($redirect_target); ?></code>, so Google sees one site instead of duplicate copies. The page they asked for and ad tags such as <code>gclid</code> and UTM are kept.</p>
						</div>
						<table class="form-table" role="presentation">
							<tr><th scope="row">Redirect other domains</th><td>
								<input type="hidden" name="retails_domain_redirect_enabled" value="0">
								<label class="wss-toggle"><input type="checkbox" name="retails_domain_redirect_enabled" value="1" <?php checked($redirect_on); ?>> Send the domains below to <?php echo esc_html(wp_parse_url($redirect_target, PHP_URL_HOST)); ?></label>
								<p class="description">Turned off, those domains show this site as it is, with no redirect.</p>
							</td></tr>
							<tr><th><label for="retails_domain_redirect_domains">Domains</label></th><td>
								<textarea class="large-text code" rows="7" id="retails_domain_redirect_domains" name="retails_domain_redirect_domains" spellcheck="false" placeholder="mystorefrontsigns.com"><?php echo esc_textarea((string) retails_get_setting('domain_redirect_domains')); ?></textarea>
								<p class="description">One domain per line. <code>www.</code> and <code>https://</code> don't matter: both versions are covered. To send every page of a domain to one page instead of the same page, add that page after it, for example <code>mystorefrontsigns.com /custom-channel-letters/</code> (<code>/</code> for the home page). <?php echo esc_html(wp_parse_url($redirect_target, PHP_URL_HOST)); ?> itself can't be listed. wp-admin, the REST API, cron and <code>/.well-known/</code> (SSL certificate checks) are never redirected.</p>
							</td></tr>
							<tr><th><label for="retails_domain_redirect_status">Redirect type</label></th><td>
								<select id="retails_domain_redirect_status" name="retails_domain_redirect_status">
									<option value="301" <?php selected($redirect_status, '301'); ?>>Permanent (301), recommended</option>
									<option value="302" <?php selected($redirect_status, '302'); ?>>Temporary (302)</option>
								</select>
								<p class="description">301 tells Google to move the domain's rankings to the main site. Browsers remember a 301, so someone who already visited may still be redirected for a while after a domain is removed here. Use 302 to try a change first.</p>
							</td></tr>
							<tr><th scope="row">What happens</th><td>
								<?php if (!$redirect_rules) : ?>
									<p class="description">No domains listed.</p>
								<?php else : ?>
									<?php if (!$redirect_on) : ?>
										<p class="description"><strong>Redirects are off.</strong> When on, after saving:</p>
									<?php endif; ?>
									<table class="widefat striped wss-redirects">
										<thead><tr><th>Visitor opens</th><th>Goes to (<?php echo esc_html($redirect_status); ?>)</th></tr></thead>
										<tbody>
											<?php foreach ($redirect_rules as $redirect_host => $redirect_path) : ?>
												<?php $redirect_sample = '/product/coroplast/?gclid=test'; ?>
												<tr><td><code><?php echo esc_html($redirect_host . $redirect_sample); ?></code><br><span class="description">and www.<?php echo esc_html($redirect_host); ?>, http or https</span></td><td><code><?php echo esc_html(retails_domain_redirect_url($redirect_path, $redirect_sample)); ?></code><br><span class="description"><?php echo '' === $redirect_path ? 'Same page' : ('/' === $redirect_path ? 'Home page, every page' : 'This page, every page'); ?></span></td></tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php endif; ?>
								<p class="description">Only domains that open this WordPress site are covered, such as cPanel aliases of it. A domain with its own website folder in cPanel, or one forwarded at GoDaddy, never reaches this site, so its redirect has to be set where it is hosted.</p>
							</td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-media-sync" data-wss-panel="media-sync" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="media-sync"><h2>Media sync</h2><p>Copy Media Library files between this site and the live site when one of them is missing some, using the Diagnostics Toolkit Site Migration connection.</p></div>
						<?php retails_msync_panel(); ?>
					</div>
				</section>
			</div>

			<div class="wss-savebar">
				<span class="wss-savebar__state" data-wss-state aria-live="polite">All changes saved</span>
				<?php submit_button('Save settings', 'primary', 'submit', false); ?>
			</div>
		</form>
	</div>
	<?php
}

function retails_settings_page_scripts($hook)
{
	if ('toplevel_page_retails-settings' !== $hook) {
		return;
	}

	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_media();
	wp_enqueue_style('retails-admin-settings', $uri . '/css/admin-settings.css', array(), (string) filemtime($dir . '/css/admin-settings.css'));
	wp_enqueue_script('retails-admin-settings', $uri . '/js/admin-settings.js', array('jquery', 'media-editor'), (string) filemtime($dir . '/js/admin-settings.js'), true);
}
add_action('admin_enqueue_scripts', 'retails_settings_page_scripts');

/**
 * Link to the settings page, e.g. retails_settings_url('images') for a panel.
 */
function retails_settings_url($panel = '')
{
	return admin_url('admin.php?page=retails-settings') . ($panel ? '#' . $panel : '');
}

function retails_add_settings_page()
{
	add_menu_page('Storefront Sign Settings', 'Storefront Sign', 'manage_options', 'retails-settings', 'retails_settings_page', 'dashicons-store', '3.1');
}
add_action('admin_menu', 'retails_add_settings_page');

/**
 * The page used to live under Settings. Send old links and bookmarks
 * (options-general.php?page=retails-settings...) to the new address,
 * keeping the rest of the query string.
 */
function retails_settings_old_url_redirect()
{
	global $pagenow;
	if ('options-general.php' !== $pagenow || !isset($_GET['page']) || 'retails-settings' !== $_GET['page']) {
		return;
	}
	wp_safe_redirect(admin_url('admin.php?' . $_SERVER['QUERY_STRING']));
	exit;
}
add_action('admin_menu', 'retails_settings_old_url_redirect', 1);
