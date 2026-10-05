<?php
/**
 * Design templates and artwork guide (/design-templates/).
 *
 * How to set up print-ready artwork, then every template and installation
 * guide in the media library, grouped by product. The download list is read
 * from the media library (files registered by inc/storefront-media.php), so
 * it stays current as templates are added.
 *
 * @package litsign
 */

$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');

// Template and guide downloads, grouped by product name.
$template_groups = array();
$template_files = get_posts(array(
	'post_type' => 'attachment',
	'post_status' => 'inherit',
	'posts_per_page' => -1,
	'meta_key' => '_wholesale_storefront_product',
	'orderby' => 'title',
	'order' => 'ASC',
	'no_found_rows' => true,
));
foreach ($template_files as $template_file) {
	$mime = (string) $template_file->post_mime_type;
	if (0 === strpos($mime, 'image/') && 'image/vnd.adobe.photoshop' !== $mime) {
		continue;
	}
	$template_product = wholesale_seo_product(get_post_meta($template_file->ID, '_wholesale_storefront_product', true));
	if (!$template_product) {
		continue;
	}
	$group = html_entity_decode(get_the_title($template_product), ENT_QUOTES, 'UTF-8');
	if (!isset($template_groups[$group])) {
		$template_groups[$group] = array('url' => get_permalink($template_product), 'files' => array());
	}
	$template_groups[$group]['files'][] = $template_file;
}
ksort($template_groups, SORT_NATURAL | SORT_FLAG_CASE);

$template_format = static function ($attachment) {
	$ext = strtoupper(pathinfo((string) get_attached_file($attachment->ID), PATHINFO_EXTENSION));
	$names = array('PDF' => 'PDF', 'PSD' => 'Photoshop', 'CDR' => 'CorelDRAW');

	return isset($names[$ext]) ? $names[$ext] : $ext;
};

$template_size = static function ($attachment) {
	$path = get_attached_file($attachment->ID);

	return $path && file_exists($path) ? size_format(filesize($path), 1) : '';
};

wholesale_seo_set_page_faq(array(
	array(
		'q' => 'Do I have to use a design template?',
		'a' => 'No, but it is the easiest way to get a print-ready file. Each template is built to the exact print size of the product, with the safe area, pole pockets and seams marked, so your logo and text land where they will be seen. If you upload artwork without a template, size it to the exact product dimensions.',
	),
	array(
		'q' => 'Which template format should I download?',
		'a' => 'Download the PDF template if you design in Adobe Illustrator or Acrobat, the PSD template for Adobe Photoshop, and the CDR template for CorelDRAW. If you use another app, build your design at the template size and export a flattened JPG.',
	),
	array(
		'q' => 'What file should I upload with my order?',
		'a' => 'A single-page PDF or a JPG at 150 dpi at full print size, in CMYK color, without crop marks or bleed. Delete the template guide layers before you save. For DTF transfers, upload a PNG with a transparent background. Files can be up to 300MB.',
	),
	array(
		'q' => 'Can you set up my artwork for me?',
		'a' => 'Yes. <a href="' . esc_url($contact_url) . '">Send us your logo and text</a> or call <a href="tel:+18664362101">866-436-2101</a>, and we will help you prepare a print-ready file for your product.',
	),
));

get_header();
?>

<main id="primary" class="sf-page">
	<section class="sf-hero" aria-labelledby="dt-title">
		<div class="container">
			<?php wholesale_breadcrumbs(); ?>
			<p class="cl-kicker">Free downloads</p>
			<h1 id="dt-title" class="sf-hero-title">Sign Design Templates &amp; Artwork Guide</h1>
			<p class="sf-hero-lead">Download a free print-ready template for your flag, banner stand, tent, table cover or fabric display, drop in your artwork and upload it with your order. Templates come in PDF, Photoshop (PSD) and CorelDRAW (CDR) formats.</p>
			<div class="sf-hero-actions">
				<a class="cl-button" href="#templates">Browse Templates <?php echo wholesale_home_icon('arrow'); ?></a>
				<a class="sf-button-outline" href="#template-guide">How to Use a Template</a>
			</div>
			<ul class="sf-hero-points">
				<li>Built to exact print size</li>
				<li>Safe areas and seams marked</li>
				<li>PDF, PSD and CDR formats</li>
				<li>Free to download</li>
			</ul>
		</div>
	</section>

	<section class="sf-section" id="template-guide" aria-labelledby="dt-guide-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Template user guide</p>
			<h2 id="dt-guide-title" class="cl-section-title">How to Use a Design Template</h2>

			<h3>1. Download the template for your exact product and size</h3>
			<p>Each template matches one product, size and print option, such as a large double-sided feather flag or a 6ft closed-back table cover. Pick the same size and sides you will order, in the format your design app opens: PDF for Adobe Illustrator, PSD for Adobe Photoshop or CDR for CorelDRAW. Designing in another app? Build your artwork at the template&rsquo;s full size and export a JPG.</p>

			<h3>2. Design on the artwork layer</h3>
			<p>Place your design on the layer named &ldquo;Artwork&rdquo;, or on a new layer above the template. Use the template&rsquo;s guides to position logos, text and photos. Don&rsquo;t resize the artboard or move the guidelines: the template is already the exact size we print.</p>

			<h3>3. Keep important content inside the safe area</h3>
			<p>Keep logos, phone numbers and text inside the marked safe area so nothing is lost in hems, pole pockets or seams. On advertising flags, keep text at least 2 inches from every edge. Our printing materials are white, so any area you leave empty prints white.</p>

			<h3>4. Remove the template layers and prepare the file</h3>
			<p>When your design is final, delete every template, guide and mask layer so only your artwork remains. Convert fonts to outlines, flatten gradients and transparencies, and convert Pantone or RGB colors to CMYK.</p>

			<h3>5. Save and upload your print file</h3>
			<p>Save a single-page PDF or a JPG at 150 dpi at full size and upload it when you order. For double-sided products, upload each side as a separate file unless the template shows both sides on one page.</p>

			<div class="cl-compare-table-wrap">
				<table class="cl-compare-table" aria-label="Print-ready file checklist">
					<thead>
						<tr>
							<th scope="col">File setup</th>
							<th scope="col">Requirement</th>
						</tr>
					</thead>
					<tbody>
						<tr><th scope="row">File type</th><td>Single-page PDF or JPG (PNG for DTF transfers)</td></tr>
						<tr><th scope="row">Color mode</th><td>CMYK (no Pantone or spot colors)</td></tr>
						<tr><th scope="row">Resolution</th><td>150 dpi at full print size</td></tr>
						<tr><th scope="row">Size</th><td>Exactly the template or ordered size; very large graphics (over 200&Prime;) may be scaled proportionally</td></tr>
						<tr><th scope="row">Fonts &amp; effects</th><td>Text outlined; gradients and transparencies flattened</td></tr>
						<tr><th scope="row">Marks</th><td>No crop marks, bleed or template guide layers</td></tr>
						<tr><th scope="row">Max upload</th><td>300MB per file</td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="sf-section sf-section--tint" id="templates" aria-labelledby="dt-list-title">
		<div class="container sf-prose">
			<p class="cl-kicker">Downloads</p>
			<h2 id="dt-list-title" class="cl-section-title">Design Templates &amp; Installation Guides by Product</h2>
			<p>Choose your product to download its templates and setup instructions. Each product page also lists its templates next to the size and specification details.</p>

			<?php if ($template_groups) : ?>
				<?php foreach ($template_groups as $group => $data) : ?>
					<?php $group_id = 'templates-' . sanitize_title($group); ?>
					<h3 id="<?php echo esc_attr($group_id); ?>"><a href="<?php echo esc_url($data['url']); ?>"><?php echo esc_html($group); ?></a> templates</h3>
					<div class="cl-compare-table-wrap">
						<table class="cl-compare-table" aria-labelledby="<?php echo esc_attr($group_id); ?>">
							<thead>
								<tr>
									<th scope="col">File</th>
									<th scope="col">Format</th>
									<th scope="col">Download</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($data['files'] as $template_file) : ?>
									<?php
									$file_title = get_the_title($template_file);
									$file_format = $template_format($template_file);
									$file_size = $template_size($template_file);
									?>
									<tr>
										<th scope="row"><?php echo esc_html($file_title); ?></th>
										<td><?php echo esc_html($file_format); ?></td>
										<td><a href="<?php echo esc_url(wp_get_attachment_url($template_file->ID)); ?>" download aria-label="<?php echo esc_attr('Download ' . $file_title); ?>">Download<?php echo $file_size ? ' (' . esc_html($file_size) . ')' : ''; ?></a></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p>Templates are being added. <a href="<?php echo esc_url($contact_url); ?>">Contact us</a> and we will send the template for your product.</p>
			<?php endif; ?>

			<p>Need a template that isn&rsquo;t listed? <a href="<?php echo esc_url($contact_url); ?>">Ask us</a> and we&rsquo;ll send it.</p>
		</div>
	</section>

	<section class="cl-faq" aria-labelledby="dt-faq-title">
		<div class="container">
			<p class="cl-kicker">Artwork questions</p>
			<h2 id="dt-faq-title" class="cl-section-title">Design Template FAQ</h2>
			<div class="cl-faq-list">
				<?php wholesale_seo_render_faq(); ?>
			</div>
		</div>
	</section>

	<section class="cl-help" aria-labelledby="dt-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="dt-help-title">Need Help With Your Artwork?</h2>
				<p>Send us your logo and text, and we&rsquo;ll help you get a print-ready file for your product before you order.</p>
				<ul class="cl-help-details">
					<li><?php echo wholesale_home_icon('clock'); ?> Mon&ndash;Fri, 8:00am&ndash;5:00pm PST</li>
					<li><?php echo wholesale_home_icon('pin'); ?> 707 S. Grady Way, Suite 600, Renton, WA 98057</li>
				</ul>
			</div>
			<div class="cl-help-actions">
				<a class="cl-help-action cl-help-action--primary" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?><span><small>Call toll free</small>866-436-2101</span></a>
				<a class="cl-help-action" href="sms:+12066186543"><?php echo wholesale_home_icon('message'); ?><span><small>Text us</small>206-618-6543</span></a>
				<a class="cl-help-action" href="mailto:TR@StorefrontSignOnline.com"><?php echo wholesale_home_icon('mail'); ?><span><small>Email</small>TR@StorefrontSignOnline.com</span></a>
				<a class="cl-help-quote" href="<?php echo esc_url($contact_url); ?>">Request a free quote <?php echo wholesale_home_icon('arrow'); ?></a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
