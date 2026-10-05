<?php
/**
 * Google Ads offline conversions: show the ad click on orders and quotes,
 * let the team mark a quote as a sale, and export those sales in the file
 * format Google Ads accepts under Goals > Uploads.
 *
 * @package litsign
 */

/**
 * Name of the Google Ads conversion action that offline sales are uploaded to.
 * It must match the action's name in Google Ads exactly.
 */
const WHOLESALE_ADS_OFFLINE_CONVERSION = 'Closed sale (offline)';

/**
 * Order screen: show whether the order came from a Google ad.
 */
function wholesale_ads_order_click_box($post)
{
	$id = (string) get_post_meta($post->ID, '_ads_click_id', true);
	if ('' === $id) {
		echo '<p>No Google Ads click was recorded for this order.</p>';
		return;
	}

	printf(
		'<p><strong>Came from a Google ad.</strong></p><p>%s: <code style="word-break:break-all">%s</code></p><p class="description">Online orders are already counted as purchase conversions; there is no need to upload them.</p>',
		esc_html((string) get_post_meta($post->ID, '_ads_click_type', true)),
		esc_html($id)
	);
}

add_action('add_meta_boxes_order', static function () {
	add_meta_box('wholesale-ads-click', 'Google Ads', 'wholesale_ads_order_click_box', 'order', 'side', 'default');
});

/**
 * Quote screen: record the value of a quote that became a sale, so it can be
 * uploaded to Google Ads as an offline conversion.
 */
function wholesale_ads_quote_sale_box($post)
{
	$click_id = (string) get_post_meta($post->ID, '_contact_gclid', true);
	$value = (string) get_post_meta($post->ID, '_won_value', true);

	wp_nonce_field('wholesale_ads_quote_sale', 'wholesale_ads_quote_sale_nonce');

	echo '' === $click_id
		? '<p>This quote did not come from a Google ad, so it cannot be uploaded.</p>'
		: '<p><strong>Came from a Google ad.</strong></p>';

	printf(
		'<p><label for="wholesale-won-value"><strong>Sale value (USD)</strong></label><br><input type="number" min="0" step="0.01" id="wholesale-won-value" name="wholesale_won_value" value="%s" style="width:100%%"></p>',
		esc_attr($value)
	);
	echo '<p class="description">When this quote turns into a paid job, enter the sale amount; Tools &gt; Google Ads uploads adds it to the upload file. Jobs paid through the website checkout or a payment link are counted automatically, so enter a value only for sales paid another way.</p>';
}

add_action('add_meta_boxes_contact_submission', static function () {
	add_meta_box('wholesale-ads-quote-sale', 'Google Ads: closed sale', 'wholesale_ads_quote_sale_box', 'contact_submission', 'side', 'default');
});

function wholesale_ads_save_quote_sale($post_id)
{
	if (!isset($_POST['wholesale_ads_quote_sale_nonce'])
		|| !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wholesale_ads_quote_sale_nonce'])), 'wholesale_ads_quote_sale')
		|| (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		|| !current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw = isset($_POST['wholesale_won_value']) ? trim((string) wp_unslash($_POST['wholesale_won_value'])) : '';
	if ('' === $raw || (float) $raw <= 0) {
		delete_post_meta($post_id, '_won_value');
		delete_post_meta($post_id, '_won_at');
		return;
	}

	update_post_meta($post_id, '_won_value', round((float) $raw, 2));
	if (!get_post_meta($post_id, '_won_at', true)) {
		update_post_meta($post_id, '_won_at', current_time('mysql', true));
	}
}
add_action('save_post_contact_submission', 'wholesale_ads_save_quote_sale');

/**
 * Quote requests from Google Ads clicks in the last 90 days (Google only
 * accepts uploads within 90 days of the click).
 *
 * @return int[]
 */
function wholesale_ads_recent_ad_quotes()
{
	return get_posts(array(
		'post_type' => 'contact_submission',
		'post_status' => array('publish', 'private', 'draft', 'pending'),
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'date_query' => array(array('after' => '89 days ago', 'inclusive' => true)),
		'meta_query' => array(array('key' => '_contact_gclid', 'compare' => 'EXISTS')),
	));
}

/**
 * Rows for the offline conversions upload: recent quotes from ad clicks that
 * were marked as sales.
 *
 * @return array List of array(click ID, conversion time, value).
 */
function wholesale_ads_offline_conversion_rows()
{
	$timezone = wp_timezone();
	$rows = array();

	foreach (wholesale_ads_recent_ad_quotes() as $quote_id) {
		$click_id = (string) get_post_meta($quote_id, '_contact_gclid', true);
		$type = (string) get_post_meta($quote_id, '_contact_click_type', true);
		$value = (float) get_post_meta($quote_id, '_won_value', true);

		// The file upload matches gclid; iOS app clicks (gbraid, wbraid) need the API.
		if ('' === $click_id || ('' !== $type && 'gclid' !== $type) || $value <= 0) {
			continue;
		}

		$won_at = (string) get_post_meta($quote_id, '_won_at', true);
		$time = new DateTime($won_at ? $won_at : 'now', new DateTimeZone('UTC'));
		$time->setTimezone($timezone);

		$rows[] = array($click_id, $time->format('Y-m-d H:i:s'), number_format($value, 2, '.', ''));
	}

	return $rows;
}

/**
 * The upload file's time zone line: an IANA name, or an offset like -0700.
 */
function wholesale_ads_upload_timezone()
{
	$timezone = wp_timezone_string();

	return preg_match('#^[A-Za-z_]+/[A-Za-z_/]+$#', $timezone) ? $timezone : str_replace(':', '', $timezone);
}

/**
 * The offline conversions file, as CSV text.
 */
function wholesale_ads_offline_csv_text()
{
	$out = fopen('php://temp', 'r+');
	fputcsv($out, array('Parameters:TimeZone=' . wholesale_ads_upload_timezone()));
	fputcsv($out, array('Google Click ID', 'Conversion Name', 'Conversion Time', 'Conversion Value', 'Conversion Currency'));
	foreach (wholesale_ads_offline_conversion_rows() as $row) {
		fputcsv($out, array($row[0], WHOLESALE_ADS_OFFLINE_CONVERSION, $row[1], $row[2], 'USD'));
	}
	rewind($out);
	$csv = stream_get_contents($out);
	fclose($out);

	return $csv;
}

/**
 * Tools > Google Ads uploads.
 */
function wholesale_ads_uploads_page()
{
	$rows = wholesale_ads_offline_conversion_rows();
	$quotes = wholesale_ads_recent_ad_quotes();
	?>
	<div class="wrap">
		<h1>Google Ads uploads</h1>
		<p>Quote requests from Google Ads clicks in the last 90 days: <strong><?php echo esc_html(count($quotes)); ?></strong>. Marked as sales: <strong><?php echo esc_html(count($rows)); ?></strong>.</p>
		<ol>
			<li>When a quote from an ad becomes a paid job that was <em>not</em> paid through the website, open it under Contact Submissions and enter the <strong>Sale value</strong>.</li>
			<li>Download the file below once a week.</li>
			<li>In Google Ads, go to <strong>Goals &gt; Uploads</strong>, upload the file and apply it. The conversion action must be named exactly <code><?php echo esc_html(WHOLESALE_ADS_OFFLINE_CONVERSION); ?></code>.</li>
		</ol>
		<?php if ($rows) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wholesale_ads_offline_csv'), 'wholesale_ads_offline_csv')); ?>">Download offline conversions (CSV)</a></p>
		<?php else : ?>
			<p><button type="button" class="button" disabled>Download offline conversions (CSV)</button> Nothing to upload yet.</p>
		<?php endif; ?>
		<p class="description">Uploading the same sale twice is safe: Google skips a click ID and conversion time it has already recorded.</p>
	</div>
	<?php
}

add_action('admin_menu', static function () {
	add_management_page('Google Ads uploads', 'Google Ads uploads', 'manage_options', 'wholesale-ads-uploads', 'wholesale_ads_uploads_page');
});

function wholesale_ads_offline_csv()
{
	if (!current_user_can('manage_options') || !check_admin_referer('wholesale_ads_offline_csv')) {
		wp_die(esc_html__('Not allowed.', 'litsign'));
	}

	nocache_headers();
	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="google-ads-offline-conversions-' . gmdate('Y-m-d') . '.csv"');
	echo wholesale_ads_offline_csv_text(); // CSV built with fputcsv.
	exit;
}
add_action('admin_post_wholesale_ads_offline_csv', 'wholesale_ads_offline_csv');
