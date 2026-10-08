<?php
/**
 * SEO > Business & Schema: the facts behind the Organization / LocalBusiness
 * structured data, with a read-only preview of the JSON-LD.
 *
 * Empty fields keep the values written in wholesale_organization_schema().
 * There is deliberately no rating field: ratings only ever come from approved
 * reviews in the review manager.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

function wholesale_seo_business_fields()
{
	return array(
		'visit_mode' => array(
			'type' => 'select',
			'label' => 'Customers can visit this address',
			'options' => array(
				'' => 'Not set: keep today\'s output (LocalBusiness on every page)',
				'on' => 'Yes: LocalBusiness on home, contact, about and Washington pages only',
				'off' => 'No: Organization only, address as mailing address, no opening hours',
			),
			'description' => 'Only choose "Yes" if customers really can walk in at this address during the hours below.',
		),
		'org_type' => array(
			'type' => 'select',
			'label' => 'Organization type',
			'options' => array('' => 'Not set: Organization', 'Organization' => 'Organization', 'OnlineStore' => 'OnlineStore (sells online; a kind of Organization)'),
		),
		'legal_name' => array('type' => 'text', 'label' => 'Legal name', 'placeholder' => 'Storefront Sign Online LLC', 'description' => 'The registered company, printed as the Organization&rsquo;s <code>legalName</code>. The schema <code>name</code> stays the brand.'),
		'logo' => array('type' => 'image', 'label' => 'Logo', 'description' => 'Empty uses the logo from Appearance &gt; Customize.'),
		'phone' => array('type' => 'text', 'label' => 'Phone', 'placeholder' => '+1-866-436-2101', 'description' => 'As it should appear in schema, with country code.'),
		'text_number' => array(
			'type' => 'text',
			'label' => 'Text (SMS) number',
			'placeholder' => '206-618-6543',
			'description' => 'Kept here for reference. Schema.org has no property for a text-only number, so it is not printed in the JSON-LD.',
		),
		'email' => array('type' => 'email', 'label' => 'Email', 'placeholder' => 'TR@StorefrontSignOnline.com'),
		'street' => array('type' => 'text', 'label' => 'Street address', 'placeholder' => '707 S. Grady Way Suite 600'),
		'locality' => array('type' => 'text', 'label' => 'City', 'placeholder' => 'Renton'),
		'region' => array('type' => 'text', 'label' => 'State', 'placeholder' => 'WA'),
		'postal_code' => array('type' => 'text', 'label' => 'ZIP code', 'placeholder' => '98057'),
		'country' => array('type' => 'text', 'label' => 'Country code', 'placeholder' => 'US'),
		'hours_days' => array(
			'type' => 'checkboxes',
			'label' => 'Open days',
			'options' => array('Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'),
			'description' => 'None ticked keeps Monday to Friday.',
		),
		'hours_opens' => array('type' => 'time', 'label' => 'Opens', 'description' => 'Empty keeps 08:00 (Pacific).'),
		'hours_closes' => array('type' => 'time', 'label' => 'Closes', 'description' => 'Empty keeps 17:00 (Pacific).'),
		'area_served' => array(
			'type' => 'lines',
			'label' => 'Area served',
			'rows' => 3,
			'placeholder' => "Washington\nUnited States",
			'description' => 'Where you sell, one per line: "United States" or a state name. Empty keeps Washington and United States. Never list places as if you had an office there.',
		),
		'ul_file_number' => array(
			'type' => 'text',
			'label' => 'UL file number',
			'placeholder' => 'E123456',
			'option' => 'wholesale_ul_file_number',
			'description' => 'Same setting as Settings &gt; Storefront Sign (stored once). Shown on the permit, installation and warranty pages only when filled in; empty hides every UL file number on the site.',
		),
		'warranty_years' => array(
			'type' => 'number',
			'label' => 'Warranty (years)',
			'placeholder' => '5',
			'description' => 'For reference only for now: "5-year" is still written into the page copy (home, footer, warranty page) and is not changed by this field.',
		),
	);
}

/**
 * The organization JSON-LD as a WA page and as any other page, pretty-printed.
 *
 * @return array label => JSON.
 */
function wholesale_seo_business_preview()
{
	$previews = array();
	foreach (array('Home, contact, about and Washington pages' => true, 'All other pages' => false) as $label => $is_wa) {
		$GLOBALS['wholesale_seo_preview_wa'] = $is_wa;
		ob_start();
		wholesale_organization_schema();
		$html = ob_get_clean();
		unset($GLOBALS['wholesale_seo_preview_wa']);

		$json = preg_match('#<script[^>]*>(.*)</script>#s', (string) $html, $match) ? json_decode($match[1], true) : null;
		$previews[$label] = $json ? wp_json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 'No organization schema is printed (an SEO plugin is active).';
	}

	return $previews;
}

function wholesale_seo_business_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	wholesale_seo_admin_header('wholesale-seo-business', 'These facts feed the Organization and LocalBusiness structured data. Empty fields keep what the theme prints today (shown as grey hints).');
	wholesale_seo_form_open('business', 'wholesale-seo-business');
	?>
	<h2>Storefront</h2>
	<table class="form-table" role="presentation">
		<?php wholesale_seo_field_rows('business', array('visit_mode', 'org_type')); ?>
	</table>

	<h2>Company</h2>
	<table class="form-table" role="presentation">
		<?php wholesale_seo_field_rows('business', array('legal_name', 'logo', 'phone', 'text_number', 'email')); ?>
	</table>

	<h2>Address &amp; hours</h2>
	<table class="form-table" role="presentation">
		<?php wholesale_seo_field_rows('business', array('street', 'locality', 'region', 'postal_code', 'country', 'hours_days', 'hours_opens', 'hours_closes', 'area_served')); ?>
	</table>

	<h2>Product facts</h2>
	<table class="form-table" role="presentation">
		<?php wholesale_seo_field_rows('business', array('ul_file_number', 'warranty_years')); ?>
	</table>

	<h2>Ratings</h2>
	<?php
	$stats = function_exists('wholesale_review_stats_by_product') ? wholesale_review_stats_by_product() : array();
	$published = 0;
	$products = 0;
	foreach ((array) $stats as $product_stats) {
		if (!empty($product_stats['published'])) {
			$published += (int) $product_stats['published'];
			$products++;
		}
	}
	?>
	<div class="wseo-card">
		<p>Ratings come only from approved, real reviews in the <a href="<?php echo esc_url(function_exists('wholesale_review_manager_url') ? wholesale_review_manager_url() : admin_url()); ?>">review manager</a>. There is no manual rating field.</p>
		<p><strong><?php echo (int) $published; ?></strong> approved reviews on <strong><?php echo (int) $products; ?></strong> products. Each product page prints its own AggregateRating when it has approved reviews and "Show product reviews" is on.</p>
		<p class="description">The Organization gets no rating: Google ignores ratings a business publishes about itself.</p>
	</div>

	<?php submit_button('Save settings'); ?>
	</form>

	<h2>JSON-LD preview (saved settings)</h2>
	<p class="description">Read-only. This is the Organization block printed in the &lt;head&gt; of every page; product, page and FAQ blocks are separate. Save to update the preview.</p>
	<div class="wseo-json-grid">
		<?php foreach (wholesale_seo_business_preview() as $label => $json) : ?>
			<div class="wseo-card">
				<h3><?php echo esc_html($label); ?></h3>
				<pre class="wseo-json"><?php echo esc_html($json); ?></pre>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	wholesale_seo_admin_footer();
}
