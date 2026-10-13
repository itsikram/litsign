<?php
/**
 * Settings > Storefront Sign. One form (options.php, group wholesale_settings)
 * split into panels with a side menu; css/admin-settings.css and
 * js/admin-settings.js add the tabs, search, image pickers and the save bar.
 * Without JavaScript every panel shows, one after the other.
 *
 * Field names are the option names, so they must not change.
 */

if (!defined('ABSPATH')) {
	exit;
}

function wholesale_settings_panels()
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
		'media-sync' => array('Media sync', 'dashicons-update'),
	);
}

/**
 * One image picker card. $args: name, value (attachment ID), title, help,
 * preview (URL of what is shown now), fallback (URL shown when cleared, or ''),
 * fallback_label, source ('library', 'theme' or 'none'), ratio (CSS).
 */
function wholesale_settings_image_field($args)
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

function wholesale_settings_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$panels = wholesale_settings_panels();
	$promo_now = function_exists('wholesale_promotion_window') ? wholesale_promotion_window() : null;
	$mail_status = wholesale_mail_status();
	$google_status = wholesale_google_reviews();
	$missing_images = wholesale_missing_image_slots();

	// Overview chips: what is on, what needs attention.
	if (wholesale_setting_enabled('payment_disabled')) {
		$pay_chip = array('Card payments off', 'is-warn', 'payments');
	} elseif (wholesale_setting_enabled('payment_test_mode')) {
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
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wholesale-emails-smtp')); ?>"><span class="dashicons dashicons-email" aria-hidden="true"></span> Email settings</a>
				<a class="button button-primary" href="<?php echo esc_url(wholesale_theme_zip_url()); ?>" title="Installable zip of the theme, without development files"><span class="dashicons dashicons-download" aria-hidden="true"></span> Download theme zip</a>
			</div>
		</header>
		<hr class="wp-header-end">

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
			<?php settings_fields('wholesale_settings'); ?>

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
							<tr><th><label for="wholesale_primary_color">Primary color</label></th><td><input type="color" id="wholesale_primary_color" name="wholesale_primary_color" value="<?php echo esc_attr(wholesale_get_setting('primary_color')); ?>"></td></tr>
							<tr><th><label for="wholesale_accent_color">Rating/accent color</label></th><td><input type="color" id="wholesale_accent_color" name="wholesale_accent_color" value="<?php echo esc_attr(wholesale_get_setting('accent_color')); ?>"></td></tr>
							<tr><th><label for="wholesale_sale_price_color">Sale price color</label></th><td><input type="color" id="wholesale_sale_price_color" name="wholesale_sale_price_color" value="<?php echo esc_attr(wholesale_get_setting('sale_price_color')); ?>"></td></tr>
						</table>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Page loader</h2></div>
						<table class="form-table" role="presentation">
							<tr><th scope="row">Enable page loader</th><td>
								<input type="hidden" name="wholesale_page_loader_enabled" value="0">
								<label class="wss-toggle"><input type="checkbox" name="wholesale_page_loader_enabled" value="1" <?php checked(wholesale_setting_enabled('page_loader_enabled')); ?>> Show the animated sign loader on internal page navigation</label>
								<p class="description">Disabled by default. Add <code>no-page-loader</code> or <code>data-no-loader</code> to a link or one of its ancestors to exclude it.</p>
							</td></tr>
						</table>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Certification</h2></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="wholesale_ul_file_number">UL file number</label></th><td><input class="regular-text" id="wholesale_ul_file_number" name="wholesale_ul_file_number" value="<?php echo esc_attr(wholesale_get_setting('ul_file_number')); ?>" placeholder="E123456"><p class="description">Shown on the sign permit, installation and warranty pages so inspectors and customers can look it up. Leave empty to hide it.</p></td></tr>
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
							$logo_default = wholesale_image_default_id('site_logo');
							$logo_default_url = $logo_default ? wp_get_attachment_image_url($logo_default, 'medium_large') : '';
							wholesale_settings_image_field(array(
								'name' => 'wholesale_site_logo',
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

							$hero_image_id = (int) wholesale_get_setting('default_hero_image');
							$hero_built_in = get_template_directory_uri() . '/img/hero-1920.webp';
							wholesale_settings_image_field(array(
								'name' => 'wholesale_default_hero_image',
								'value' => $hero_image_id,
								'title' => 'Default hero image',
								'help' => 'Hero background for product categories and pages that have no image of their own. The home page and the Channel Letters page use the Channel Letters page\'s featured image instead. Best size: at least 2400 &times; 1000 px, landscape, with the main subject on the right and a darker, quiet left side where the headline sits. Phones show the right-hand part of the photo. A smaller AVIF copy is made automatically a few seconds after the first page view.',
								'preview' => $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'medium_large') : $hero_built_in,
								'fallback' => $hero_built_in,
								'fallback_label' => 'Built-in photo',
								'source' => $hero_image_id ? 'library' : 'theme',
							));

							$can_import = false;
							foreach (wholesale_image_slots() as $key => $slot) {
								$id = wholesale_image_slot_id($key);
								$default = wholesale_image_default_id($key);
								$legacy = wholesale_image_slot_legacy($key);
								$can_import = $can_import || (!$id && !$default && $legacy);
								$fallback = $default ? wp_get_attachment_image_url($default, 'medium_large') : ($legacy ? $legacy[1] : '');
								wholesale_settings_image_field(array(
									'name' => wholesale_image_slot_option($key),
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
							<p class="wss-import"><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wholesale_import_theme_images'), 'wholesale_import_theme_images')); ?>">Move built-in images to the Media Library</a> <span class="description">Copies the theme's own files for the empty spots above into the Media Library, so they can be removed from the theme.</span></p>
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
							<tr><th><label for="wholesale_promo_percent">Sale percentage</label></th><td><input type="number" class="small-text" min="0" max="90" step="0.01" id="wholesale_promo_percent" name="wholesale_promo_percent" value="<?php echo esc_attr(wholesale_get_setting('promo_percent')); ?>"> % off <p class="description">0 turns the sale off.</p></td></tr>
							<tr><th><label for="wholesale_promo_start">Starts</label></th><td><input type="date" id="wholesale_promo_start" name="wholesale_promo_start" value="<?php echo esc_attr(wholesale_get_setting('promo_start')); ?>"></td></tr>
							<tr><th><label for="wholesale_promo_end">Ends (last day of the sale)</label></th><td><input type="date" id="wholesale_promo_end" name="wholesale_promo_end" value="<?php echo esc_attr(wholesale_get_setting('promo_end')); ?>"></td></tr>
							<tr><th><label for="wholesale_promo_scope">Products</label></th><td><select id="wholesale_promo_scope" name="wholesale_promo_scope">
								<?php foreach (array('all' => 'All products', 'channel-letters' => 'Channel letters only', 'other' => 'Everything except channel letters') as $scope => $label) : ?>
									<option value="<?php echo esc_attr($scope); ?>" <?php selected(wholesale_get_setting('promo_scope'), $scope); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php echo $promo_now ? esc_html(sprintf('A %s%% sale is running now, through %s.', $promo_now['percent'], wholesale_promotion_end_text($promo_now))) : 'No sale is running now.'; ?></p></td></tr>
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
							) as $key => $label) : ?>
								<tr><th><?php echo esc_html($label); ?></th><td><input type="hidden" name="wholesale_<?php echo esc_attr($key); ?>" value="0"><label class="wss-toggle"><input type="checkbox" name="wholesale_<?php echo esc_attr($key); ?>" value="1" <?php checked(wholesale_setting_enabled($key)); ?>> Enabled</label></td></tr>
							<?php endforeach; ?>
						</table>
					</div>
					<div class="wss-card">
						<div class="wss-card__head" id="google-reviews"><h2>Google reviews</h2><p>Shows your Google Business rating and latest reviews in the site-wide review slider, next to reviews you approve under <a href="<?php echo esc_url(admin_url('edit.php?post_type=review_submission')); ?>">Customer Reviews</a>.</p></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="wholesale_google_places_api_key">Google Places API key</label></th><td><input type="password" class="regular-text" id="wholesale_google_places_api_key" name="wholesale_google_places_api_key" value="<?php echo esc_attr(wholesale_get_setting('google_places_api_key')); ?>" autocomplete="new-password" data-1p-ignore data-lpignore="true" spellcheck="false"><p class="description">Create it in Google Cloud Console with the <strong>Places API (New)</strong> enabled, and restrict it to that API. It starts with <code>AIza</code>; anything else is not saved.</p></td></tr>
							<tr><th><label for="wholesale_google_place_id">Google Place ID</label></th><td><input class="regular-text" id="wholesale_google_place_id" name="wholesale_google_place_id" value="<?php echo esc_attr(wholesale_get_setting('google_place_id')); ?>" placeholder="ChIJ..."><p class="description">Find it with Google's <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener">Place ID finder</a>.</p></td></tr>
							<tr><th><label for="wholesale_google_reviews_min_rating">Show reviews rated</label></th><td><select id="wholesale_google_reviews_min_rating" name="wholesale_google_reviews_min_rating"><?php foreach (array(5 => '5 stars only', 4 => '4 stars and up', 3 => '3 stars and up', 2 => '2 stars and up', 1 => 'All ratings') as $stars => $label) : ?><option value="<?php echo esc_attr($stars); ?>" <?php selected((int) wholesale_get_setting('google_reviews_min_rating'), $stars); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><p class="description">Lower-rated Google reviews are left out of the review slider and reviews sections. Your overall Google rating and review count are always shown as Google reports them.</p></td></tr>
							<?php if ($google_status) : ?>
								<tr><th>Status</th><td>
									<?php if (!empty($google_status['error'])) : ?>
										<p style="color:#b32d2e;"><strong>Last refresh failed:</strong> <?php echo esc_html($google_status['error']); ?></p>
									<?php endif; ?>
									<p><?php echo esc_html(sprintf('%s stars from %s reviews; %d shown in the slider. Checked %s ago.', number_format((float) $google_status['rating'], 1), number_format_i18n((int) $google_status['count']), count($google_status['reviews']), human_time_diff((int) $google_status['checked'], time()))); ?></p>
									<p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wholesale_refresh_google_reviews'), 'wholesale_refresh_google_reviews')); ?>">Refresh now</a></p>
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
							SMTP settings, test emails and the log of every email sent are under <a href="<?php echo esc_url(admin_url('admin.php?page=wholesale-emails-smtp')); ?>">Emails</a>.
						</p>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Customer emails</h2></div>
						<table class="form-table" role="presentation">
							<tr><th>Order status emails</th><td><input type="hidden" name="wholesale_email_status_updates" value="0"><label class="wss-toggle"><input type="checkbox" name="wholesale_email_status_updates" value="1" <?php checked(wholesale_setting_enabled('email_status_updates')); ?>> Email customers when their order goes into production, ships (with tracking), is put on hold, cancelled or refunded</label></td></tr>
							<tr><th>Quote request confirmation</th><td><input type="hidden" name="wholesale_quote_autoreply" value="0"><label class="wss-toggle"><input type="checkbox" name="wholesale_quote_autoreply" value="1" <?php checked(wholesale_setting_enabled('quote_autoreply')); ?>> Email customers a confirmation with a copy of their request as soon as they submit a quote form</label><p class="description">Reply to a request from its screen under <a href="<?php echo esc_url(admin_url('edit.php?post_type=contact_submission')); ?>">Contact Submissions</a>.</p></td></tr>
							<tr><th><label for="wholesale_quote_autoreply_subject">Confirmation subject</label></th><td><input class="large-text" id="wholesale_quote_autoreply_subject" name="wholesale_quote_autoreply_subject" value="<?php echo esc_attr(wholesale_get_setting('quote_autoreply_subject')); ?>"></td></tr>
							<tr><th><label for="wholesale_quote_autoreply_message">Confirmation message</label></th><td><textarea class="large-text" rows="4" id="wholesale_quote_autoreply_message" name="wholesale_quote_autoreply_message"><?php echo esc_textarea(wholesale_get_setting('quote_autoreply_message')); ?></textarea><p class="description">Placeholders: <code>{name}</code> customer's first name, <code>{store}</code> business name (from Payment tickets), <code>{project}</code> project type. A copy of what they sent is added below the message.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-tickets" data-wss-panel="tickets" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="payment-tickets"><h2>Payment tickets</h2><p>Used by <a href="<?php echo esc_url(admin_url('edit.php?post_type=payment_ticket')); ?>">Payment Tickets</a>: the customer's payment page, the payment request email and new tickets.</p></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="wholesale_ticket_business_name">Business name</label></th><td><input class="regular-text" id="wholesale_ticket_business_name" name="wholesale_ticket_business_name" value="<?php echo esc_attr(wholesale_get_setting('ticket_business_name')); ?>"></td></tr>
							<tr><th><label for="wholesale_ticket_business_phone">Phone</label></th><td><input class="regular-text" id="wholesale_ticket_business_phone" name="wholesale_ticket_business_phone" value="<?php echo esc_attr(wholesale_get_setting('ticket_business_phone')); ?>"></td></tr>
							<tr><th><label for="wholesale_ticket_business_email">Email</label></th><td><input type="email" class="regular-text" id="wholesale_ticket_business_email" name="wholesale_ticket_business_email" value="<?php echo esc_attr(wholesale_get_setting('ticket_business_email')); ?>"><p class="description">Shown to customers and used as the Reply-To address, so their replies reach you.</p></td></tr>
							<tr><th><label for="wholesale_ticket_business_address">Address</label></th><td><input class="large-text" id="wholesale_ticket_business_address" name="wholesale_ticket_business_address" value="<?php echo esc_attr(wholesale_get_setting('ticket_business_address')); ?>"><p class="description">Shown in the "From" section of the payment page and in the email footer.</p></td></tr>
							<tr><th><label for="wholesale_ticket_email_subject">Email subject</label></th><td><input class="large-text" id="wholesale_ticket_email_subject" name="wholesale_ticket_email_subject" value="<?php echo esc_attr(wholesale_get_setting('ticket_email_subject')); ?>"><p class="description">Placeholders: <code>{number}</code> ticket number, <code>{title}</code> project name, <code>{amount}</code> total due, <code>{business}</code> business name.</p></td></tr>
							<tr><th>Email new tickets automatically</th><td><input type="hidden" name="wholesale_ticket_auto_send" value="0"><label class="wss-toggle"><input type="checkbox" name="wholesale_ticket_auto_send" value="1" <?php checked(wholesale_setting_enabled('ticket_auto_send')); ?>> Email the customer as soon as a new ticket is saved with a customer, items and a total</label><p class="description">Use "Save draft without sending" on a ticket to keep working on it first. Customers also get a receipt when they pay, and the <a href="<?php echo esc_url(admin_url('admin.php?page=wholesale-emails-smtp')); ?>">store notification recipients</a> get a blind copy of every payment request and receipt.</p></td></tr>
							<tr><th><label for="wholesale_ticket_default_message">Default message</label></th><td><textarea class="large-text" rows="3" id="wholesale_ticket_default_message" name="wholesale_ticket_default_message" placeholder="e.g. Thanks for choosing us! Production starts once payment is received."><?php echo esc_textarea(wholesale_get_setting('ticket_default_message')); ?></textarea><p class="description">Pre-filled as the message to the customer on new tickets. You can still change it on each ticket.</p></td></tr>
							<tr><th>Sales tax on new tickets</th><td><input type="hidden" name="wholesale_ticket_default_tax" value="0"><label class="wss-toggle"><input type="checkbox" name="wholesale_ticket_default_tax" value="1" <?php checked(wholesale_setting_enabled('ticket_default_tax')); ?>> Start new tickets with the checkout tax rate (<?php echo esc_html(wholesale_get_setting('tax_rate')); ?>%)</label></td></tr>
							<tr><th><label for="wholesale_ticket_due_days">Pay-by period</label></th><td><input type="number" min="0" step="1" class="small-text" id="wholesale_ticket_due_days" name="wholesale_ticket_due_days" value="<?php echo esc_attr((string) wholesale_get_setting('ticket_due_days')); ?>"> days<p class="description">New tickets get a pay-by date this many days ahead, and the link stops accepting payment after it. Use 0 for no pay-by date.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-payments" data-wss-panel="payments" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head"><h2>Payment gateway</h2></div>
						<table class="form-table" role="presentation">
							<tr><th>Disable card payment</th><td><input type="hidden" name="wholesale_payment_disabled" value="0"><label class="wss-toggle"><input type="checkbox" name="wholesale_payment_disabled" value="1" <?php checked(wholesale_setting_enabled('payment_disabled')); ?>> Create orders for manual payment instead</label></td></tr>
							<tr><th>Card payment method</th><td>
								<fieldset class="wss-choices">
									<label class="wss-choice"><input type="radio" name="wholesale_payment_method" value="lightbox" <?php checked('lightbox', wholesale_payment_method()); ?>> <span><strong>Converge secure window (Lightbox)</strong> &mdash; recommended<span class="description">Customers type their card into Elavon's own secure window, so card numbers never pass through this website. Your Converge user must have <em>Hosted Payments</em> enabled.</span></span></label>
									<label class="wss-choice"><input type="radio" name="wholesale_payment_method" value="direct" <?php checked('direct', wholesale_payment_method()); ?>> <span><strong>Card fields on this website (direct)</strong><span class="description">The previous method: card fields on the checkout page, charged by this server through the Converge API. Card numbers pass through (but are never stored on) this server, which puts the site in a stricter PCI compliance scope.</span></span></label>
								</fieldset>
							</td></tr>
							<tr><th>Test mode</th><td><input type="hidden" name="wholesale_payment_test_mode" value="0"><label class="wss-toggle"><input type="checkbox" name="wholesale_payment_test_mode" value="1" <?php checked(wholesale_setting_enabled('payment_test_mode')); ?>> Use the Converge demo endpoint</label></td></tr>
							<tr><th><label for="wholesale_merchant_id">Merchant ID</label></th><td><input class="regular-text" id="wholesale_merchant_id" name="wholesale_merchant_id" value="<?php echo esc_attr(wholesale_get_setting('merchant_id')); ?>"></td></tr>
							<tr><th><label for="wholesale_gateway_user_id">Gateway user ID</label></th><td><input class="regular-text" id="wholesale_gateway_user_id" name="wholesale_gateway_user_id" value="<?php echo esc_attr(wholesale_get_setting('gateway_user_id')); ?>"></td></tr>
							<tr><th><label for="wholesale_gateway_pin">Gateway PIN</label></th><td><input type="password" class="regular-text" id="wholesale_gateway_pin" name="wholesale_gateway_pin" value="<?php echo esc_attr(wholesale_get_setting('gateway_pin')); ?>" autocomplete="new-password"></td></tr>
						</table>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Checkout defaults</h2></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="wholesale_tax_rate">Tax rate (%)</label></th><td><input type="number" min="0" step="0.01" id="wholesale_tax_rate" name="wholesale_tax_rate" value="<?php echo esc_attr(wholesale_get_setting('tax_rate')); ?>"></td></tr>
							<tr><th><label for="wholesale_standard_shipping_options">Standard shipping options</label></th><td><input class="regular-text" id="wholesale_standard_shipping_options" name="wholesale_standard_shipping_options" value="<?php echo esc_attr(wholesale_get_setting('standard_shipping_options')); ?>"><p class="description">Comma-separated amounts, from fastest to slowest.</p></td></tr>
							<tr><th><label for="wholesale_channel_shipping_options">Channel-letter shipping options</label></th><td><input class="regular-text" id="wholesale_channel_shipping_options" name="wholesale_channel_shipping_options" value="<?php echo esc_attr(wholesale_get_setting('channel_shipping_options')); ?>"><p class="description">Comma-separated amounts, from fastest to slowest.</p></td></tr>
						</table>
					</div>
				</section>

				<section class="wss-panel" id="wss-panel-marketing" data-wss-panel="marketing" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="google-ads"><h2>Google Ads</h2><p>Conversion labels for the Google Ads tag <code>AW-18454059893</code>. Each conversion action is sent from one place only, so nothing counts twice.</p></div>
						<?php
						$page_conversions = get_posts(array('post_type' => 'any', 'post_status' => 'any', 'posts_per_page' => 50, 'meta_key' => WHOLESALE_PAGE_CONVERSION_META, 'orderby' => 'title', 'order' => 'ASC'));
						$used_labels = array_count_values(array_merge(wholesale_ads_sitewide_labels(), array_map(function ($p) {
							return wholesale_page_conversion_clean_label(get_post_meta($p->ID, WHOLESALE_PAGE_CONVERSION_META, true));
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
								<?php foreach (wholesale_ads_builtin_conversions() as $conversion) : ?>
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
										<tr><td><a href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>"><?php echo esc_html(get_the_title($p) ?: '(no title)'); ?></a></td><td><code>AW-18454059893/<?php echo esc_html(wholesale_page_conversion_clean_label(get_post_meta($p->ID, WHOLESALE_PAGE_CONVERSION_META, true))); ?></code></td><td>Every view of the page, except the view a quote form returns to.</td></tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p class="description">None. Set one in the <strong>Google Ads conversion</strong> box on a page's edit screen, for example a thank-you page. Don't use it on a page ads link to: every ad visit would count.</p>
						<?php endif; ?>
						<h3>Labels you can change</h3>
						<table class="form-table" role="presentation">
							<tr><th><label for="wholesale_google_ads_lead_label">Quote request conversion label</label></th><td><input class="regular-text" id="wholesale_google_ads_lead_label" name="wholesale_google_ads_lead_label" value="<?php echo esc_attr(wholesale_get_setting('google_ads_lead_label')); ?>" placeholder="<?php echo esc_attr(wholesale_setting_defaults()['google_ads_lead_label']); ?>"><p class="description">Empty uses the default <code><?php echo esc_html(wholesale_setting_defaults()['google_ads_lead_label']); ?></code>. Sent when someone submits a quote form: the contact page, the sign landing page and the <strong>Channel Letters</strong> page (/custom-channel-letters/). In Google Ads, create a "Submit lead form" conversion action and paste the part after <code>AW-18454059893/</code> from its event snippet.</p></td></tr>
							<?php foreach (wholesale_ads_click_conversions() as $key => $conversion) : ?>
								<tr><th><label for="wholesale_<?php echo esc_attr($key); ?>"><?php echo esc_html($conversion['label']); ?></label></th><td><input class="regular-text" id="wholesale_<?php echo esc_attr($key); ?>" name="wholesale_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(wholesale_get_setting($key)); ?>" placeholder="Leave empty to turn off"><p class="description"><?php echo esc_html($conversion['help']); ?> Create it in Google Ads as a <strong>Secondary</strong> conversion action and paste the part after <code>AW-18454059893/</code>.</p></td></tr>
							<?php endforeach; ?>
						</table>
					</div>
					<div class="wss-card">
						<div class="wss-card__head"><h2>Google Analytics</h2></div>
						<table class="form-table" role="presentation">
							<tr><th><label for="wholesale_ga4_measurement_id">Google Analytics 4 measurement ID</label></th><td><input class="regular-text" id="wholesale_ga4_measurement_id" name="wholesale_ga4_measurement_id" value="<?php echo esc_attr(wholesale_get_setting('ga4_measurement_id')); ?>" placeholder="G-XXXXXXXXXX"><p class="description">From Google Analytics: Admin &gt; Data streams &gt; your web stream. Purchases, checkouts and quote requests are then also sent to Analytics. Leave empty to turn off.</p></td></tr>
						</table>
					</div>
				</section>
				<section class="wss-panel" id="wss-panel-media-sync" data-wss-panel="media-sync" role="tabpanel">
					<div class="wss-card">
						<div class="wss-card__head" id="media-sync"><h2>Media sync</h2><p>Copy Media Library files between this site and the live site when one of them is missing some, using the Diagnostics Toolkit Site Migration connection.</p></div>
						<?php wholesale_msync_panel(); ?>
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

function wholesale_settings_page_scripts($hook)
{
	if ('settings_page_wholesale-settings' !== $hook) {
		return;
	}

	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_media();
	wp_enqueue_style('wholesale-admin-settings', $uri . '/css/admin-settings.css', array(), (string) filemtime($dir . '/css/admin-settings.css'));
	wp_enqueue_script('wholesale-admin-settings', $uri . '/js/admin-settings.js', array('jquery', 'media-editor'), (string) filemtime($dir . '/js/admin-settings.js'), true);
}
add_action('admin_enqueue_scripts', 'wholesale_settings_page_scripts');

function wholesale_add_settings_page()
{
	add_options_page('Storefront Sign Settings', 'Storefront Sign', 'manage_options', 'wholesale-settings', 'wholesale_settings_page');
}
add_action('admin_menu', 'wholesale_add_settings_page');
