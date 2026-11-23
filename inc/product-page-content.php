<?php
/**
 * Product page copy that replaces the supplier's stored description on every
 * product that isn't a channel letter: search title, meta description, the
 * specifications, production and shipping, artwork setup, care and an
 * expanded FAQ.
 *
 * The supplier text in post_content is shared word for word with other
 * resellers, so Google indexed their pages instead of ours (GSC, Oct 2026).
 * Its prose (description, turnaround notes, file setup bullets, FAQ) is no
 * longer printed. Its structured facts stay: the Spec section (size tables
 * and charts) and the template and setup guide downloads. The stored
 * content is left as it is.
 *
 * Per-product copy lives in data/product-pages/*.php, keyed by product slug:
 *
 * - 'title'       array(keyword, differentiator) for the title tag.
 * - 'description' Meta description, 140-155 characters.
 * - 'notes'       "Good to know" facts the guide does not already state.
 * - 'faq'         Extra questions and answers (the guide has three).
 * - 'care'        Care advice (HTML), or a key of wholesale_product_care_texts().
 * - 'file_notes'  Artwork notes specific to the product.
 * - 'files'       File rules that replace the standard JPEG/PDF ones.
 * - 'spec'        Label => value rows that replace a wrong supplier Spec section.
 * - 'printed'     false for stock hardware that isn't printed to order.
 *
 * A variant ("Graphic Only", "Flag Only") inherits the entry of the product
 * it canonicalizes to; its own entry only needs a title and description.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * When the copy in data/product-pages/ last changed. Bump it by hand with
 * the copy, so product sitemap <lastmod> tells Google the pages changed.
 */
define('WHOLESALE_PRODUCT_PAGES_REVISED', '2026-10-25T00:00:00+00:00');

/**
 * Every product page entry, keyed by slug.
 *
 * @return array
 */
function wholesale_product_pages()
{
	static $pages = null;
	if (null === $pages) {
		$pages = array();
		foreach (glob(get_template_directory() . '/data/product-pages/*.php') ?: array() as $file) {
			$entries = include $file;
			if (is_array($entries)) {
				$pages = array_merge($pages, $entries);
			}
		}
	}

	return $pages;
}

/**
 * Descriptive alt text for a product photo from data/image-alts.php, matched
 * by its path under uploads/ whether the URL is the full file, a resized copy
 * ("-300x300.jpg") or a converted one (".jpg.webp"). $fallback when unknown.
 *
 * @param string $src URL or uploads-relative path.
 */
function wholesale_product_photo_alt($src, $fallback = '')
{
	static $map = null;
	if (null === $map) {
		$file = get_template_directory() . '/data/image-alts.php';
		$map = is_file($file) ? (array) include $file : array();
	}

	$path = (string) wp_parse_url((string) $src, PHP_URL_PATH);
	$rel = preg_replace('#^.*/wp-content/uploads/#', '', $path);
	$rel = preg_replace('#\.(?:webp|avif)$#i', '', $rel);
	foreach (array($rel, preg_replace('#-\d+x\d+(\.[a-z0-9]+)$#i', '$1', $rel)) as $key) {
		if (isset($map[$key])) {
			return $map[$key];
		}
	}

	return $fallback;
}

/**
 * The entry for a product: its own, laid over the entry of the product it
 * canonicalizes to. Empty array when there is none.
 */
function wholesale_product_page($product_id)
{
	$pages = wholesale_product_pages();
	$slug = (string) get_post_field('post_name', $product_id);
	$own = isset($pages[$slug]) ? $pages[$slug] : array();
	$primary = wholesale_seo_product_canonical_of($product_id);
	$base = array();
	if ($primary) {
		$primary_slug = (string) get_post_field('post_name', $primary);
		$base = isset($pages[$primary_slug]) ? $pages[$primary_slug] : array();
		// The kit's title and description describe the kit, not this variant;
		// an exact duplicate listing ("-3" slug) is the same product and keeps them.
		if (!wholesale_seo_product_duplicate_of($product_id)) {
			unset($base['title'], $base['description']);
		}
	}

	return array_merge($base, $own);
}

/**
 * Title tag for a product with an entry: "Keyword – Differentiator | Brand",
 * dropping the differentiator, then the brand, to stay within 60 characters.
 * '' when the product has no entry.
 */
function wholesale_product_page_title($product_id)
{
	$entry = wholesale_product_page($product_id);
	if (empty($entry['title'])) {
		$variant = wholesale_seo_product_variant_label($product_id);
		if (!$variant || !wholesale_seo_product_canonical_of($product_id)) {
			return '';
		}
		// Variants without their own title: "Name (Flag Only)".
		$entry['title'] = array(sprintf('%s (%s)', wholesale_seo_product_search_name($product_id), $variant), '');
	}

	$keyword = trim((string) $entry['title'][0]);
	$detail = isset($entry['title'][1]) ? trim((string) $entry['title'][1]) : '';
	$brand = get_bloginfo('name');
	$candidates = array();
	if ('' !== $detail) {
		$candidates[] = sprintf('%s – %s | %s', $keyword, $detail, $brand);
	}
	$candidates[] = sprintf('%s | %s', $keyword, $brand);
	if ('' !== $detail) {
		$candidates[] = sprintf('%s – %s', $keyword, $detail);
	}
	foreach ($candidates as $candidate) {
		if (mb_strlen($candidate) <= 60) {
			return $candidate;
		}
	}

	return $keyword;
}

/**
 * Meta description from the product's entry, or ''.
 */
function wholesale_product_page_description($product_id)
{
	$entry = wholesale_product_page($product_id);

	return isset($entry['description']) ? trim((string) $entry['description']) : '';
}

/**
 * The stored supplier description split into its sections, keyed
 * 'same_day', 'next_day', 'description', 'spec', 'files', 'setup' and 'faq'.
 * Most products store HTML with <h4> headings; a few store plain text with
 * the headings on their own lines.
 *
 * @return array
 */
function wholesale_product_supplier_sections($product_id)
{
	static $cache = array();
	$product_id = (int) $product_id;
	if (isset($cache[$product_id])) {
		return $cache[$product_id];
	}

	$html = (string) get_post_field('post_content', $product_id);
	$names = array(
		'next day turnaround' => 'next_day',
		'same day turnaround' => 'same_day',
		'available production facility' => 'facility',
		'available distribution facility' => 'facility',
		'description' => 'description',
		'spec' => 'spec',
		'file setup' => 'files',
		'installation guide' => 'setup',
		'frequently asked questions' => 'faq',
	);

	if (false === stripos($html, '<h4')) {
		// Plain text: turn each heading line into an <h4> and the rest into HTML.
		$html = wpautop($html);
		$html = preg_replace_callback('#<p>\s*(Next Day Turnaround and Cut-off Time:|Same Day Turnaround(?: and Cut-off Time)?:|Available (?:Production|Distribution) Facility|Description|Spec|File Setup|Installation Guide|Frequently asked questions)\s*(<br\s*/?>|</p>)#i', static function ($match) {
			// A heading that ended its paragraph needs no new one opened after it.
			return '<h4>' . $match[1] . '</h4>' . ('</p>' === strtolower($match[2]) ? '' : '<p>');
		}, $html);
	}

	$sections = array();
	$parts = preg_split('#<h4[^>]*>(.*?)</h4>#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
	for ($i = 1; $i < count($parts); $i += 2) {
		$name = strtolower(trim(wp_strip_all_tags($parts[$i]), " :\t\n\r"));
		foreach ($names as $prefix => $key) {
			if (0 === strpos($name, $prefix)) {
				$sections[$key] = (isset($sections[$key]) ? $sections[$key] : '') . (isset($parts[$i + 1]) ? $parts[$i + 1] : '');
				break;
			}
		}
	}

	return $cache[$product_id] = $sections;
}

/**
 * Turnaround from the supplier notes: whether same-day shipping is offered,
 * whether the cut-off is the 12 pm same-day one (stock items), and the
 * business days after a 4 pm order.
 *
 * @return array
 */
function wholesale_product_turnaround($product_id)
{
	$sections = wholesale_product_supplier_sections($product_id);
	$same = wp_strip_all_tags(isset($sections['same_day']) ? $sections['same_day'] : '');
	$next = wp_strip_all_tags(isset($sections['next_day']) ? $sections['next_day'] : '');

	return array(
		'same_day' => (bool) preg_match('/ships (?:same day|today)/i', $same),
		// Stock items list only "Order before 12pm PST ships today".
		'stock' => '' === trim($next) && (bool) preg_match('/ships today/i', $same),
		'next_days' => preg_match('/ships in (\d+) business days/i', $next, $m) ? (int) $m[1] : 1,
		'bulk_extra' => (bool) preg_match('/over 100 qty/i', $next),
	);
}

/**
 * Whether the product is printed to order (most are) or stock hardware.
 */
function wholesale_product_is_printed($product_id)
{
	$entry = wholesale_product_page($product_id);
	if (isset($entry['printed'])) {
		return (bool) $entry['printed'];
	}
	$turnaround = wholesale_product_turnaround($product_id);

	return !$turnaround['stock'];
}

/**
 * One sentence on when an order ships, for the shipping section and FAQ.
 */
function wholesale_product_ship_sentence($product_id)
{
	$t = wholesale_product_turnaround($product_id);
	if ($t['stock']) {
		return 'Order by 12 pm Pacific time and it ships the same business day; later orders ship the next business day.';
	}

	$next = 1 === $t['next_days'] ? 'the next business day' : sprintf('in %d business days', $t['next_days']);
	if ($t['same_day']) {
		return sprintf('Order and approve your artwork by 12 pm Pacific time and it ships the same business day. By 4 pm, it ships %s; after 4 pm, allow one more business day.', $next);
	}

	return sprintf('Order and approve your artwork by 4 pm Pacific time and it ships %s; after 4 pm, allow one more business day.', $next);
}

/**
 * Care advice by material family; entries pick one with 'care' => key.
 *
 * @return array
 */
function wholesale_product_care_texts()
{
	return array(
		'vinyl-banner' => 'Clean the print with mild soap, water and a soft cloth, and skip solvents and pressure washers. Let the banner dry fully before you store it, then roll it with the printed side facing out and keep it somewhere dry. Using every grommet when you hang it spreads the load and helps prevent tears.',
		'fabric' => 'The polyester fabric can be machine washed in cold water on a gentle cycle. Lay it flat or hang it to dry, and remove wrinkles with a hand steamer. Do not bleach, tumble dry or iron it.',
		'flag' => 'Take the flag down in storms and very high wind. The polyester flag can be machine washed in cold water on a gentle cycle and hung to dry; do not bleach, tumble dry or iron it. Store poles and flag dry, in the carry bag if you have one.',
		'rigid' => 'Wipe the panel with a soft cloth and mild soapy water. Avoid abrasive pads, solvents and pressure washers, which can scratch the print. Store panels flat or on edge, not stacked under weight, so they stay straight.',
		'magnet' => 'Put the magnet on a clean, dry, flat steel surface, never over fresh paint. Take it off about once a week to clean and dry both the magnet and the vehicle underneath, and store it flat. Remove it before automatic car washes.',
		'adhesive' => 'Apply the graphic to a clean, dry, smooth surface; dust, wax and moisture weaken the adhesive. Clean it afterward with a soft cloth and mild soapy water, without abrasives or solvents.',
		'dry-erase' => 'Write with standard dry erase markers and wipe them off with a dry eraser or soft cloth. Don&rsquo;t use permanent markers, abrasive pads or solvents, which can damage the dry erase coating.',
		'cling' => 'Apply the cling to clean, dry glass. To reuse it, peel it off slowly, put it back on its liner and store it flat. Clean the glass and the cling with mild soapy water.',
		'wall-art' => 'Hang wall art away from direct sun and heat to keep colors bright. Dust it with a dry microfiber cloth; do not use water or household cleaners on the print.',
		'display' => 'Wipe frames and hardware with a damp cloth. Pack every part back into its carry case after each event so nothing gets bent or lost, and keep graphics dry.',
		'stand' => 'Retract or fold the stand and pack it in its carry bag after each use. Keep the graphic dry and wipe it with a soft, damp cloth if it gets marked.',
		'tent' => 'Never fold or pack the canopy wet; let it dry fully first to prevent mildew. Take the tent down in high wind, and use weights or stakes on every leg whenever it is set up.',
		'a-frame' => 'Rinse the frame with water and wipe it down with mild soap. Weigh the base down or bring the A-frame inside in high wind, and store it out of direct sun when you are not using it.',
	);
}

/**
 * The product's FAQ: the guide's questions, the entry's extra ones, then
 * when it ships. Used for the page and for FAQPage structured data.
 *
 * @return array
 */
function wholesale_product_page_faq($product_id)
{
	$guide = function_exists('wholesale_product_guide') ? wholesale_product_guide($product_id) : array();
	$entry = wholesale_product_page($product_id);
	$faq = array_merge(
		!empty($guide['faq']) ? $guide['faq'] : array(),
		!empty($entry['faq']) ? $entry['faq'] : array()
	);
	if (!$entry && !$faq) {
		return array();
	}

	// Channel letters state their own production times in their entry.
	if (!wholesale_seo_channel_letter_product($product_id)) {
		$faq[] = array(
			'q' => 'When will my order ship?',
			'a' => wholesale_product_ship_sentence($product_id) . ' Then it travels by the shipping speed you choose at checkout.',
		);
	}

	return $faq;
}

/**
 * The <img> tags for the given file names, taken from a channel letter's
 * stored Description and Components tabs (they carry the alt text and
 * attachment IDs), in the order asked for.
 *
 * @return string[]
 */
function wholesale_channel_letter_tab_images($product_id, $files)
{
	$html = (string) get_post_meta($product_id, '_product_description', true) . (string) get_post_meta($product_id, '_product_component', true);
	preg_match_all('#<img\b[^>]*>#i', $html, $tags);
	$by_file = array();
	foreach ($tags[0] as $tag) {
		if (preg_match('#\bsrc="([^"]+)"#i', $tag, $src)) {
			$file = basename((string) wp_parse_url($src[1], PHP_URL_PATH));
			if (!isset($by_file[$file])) {
				// The tabs' inline sizing fights the grid; the grid sizes them.
				$by_file[$file] = preg_replace('#\s(?:style|width|height)="[^"]*"#i', '', $tag);
			}
		}
	}

	$images = array();
	foreach ((array) $files as $file) {
		if (isset($by_file[$file])) {
			$images[] = $by_file[$file];
		}
	}

	return $images;
}

/**
 * Print a channel letter style's specifications, shipping, installation,
 * warranty and FAQ in place of the supplier's tabs. False (nothing printed)
 * without an entry in data/product-pages/channel-letters.php.
 */
function wholesale_render_channel_letter_details($product_id)
{
	$entry = wholesale_product_page($product_id);
	$seo = wholesale_seo_channel_letter_product($product_id);
	if (empty($entry['parts']) || empty($seo['heading'])) {
		return false;
	}

	$heading = $seo['heading'];
	$stainless = 'stainless' === ($entry['material'] ?? '');
	$images = wholesale_channel_letter_tab_images($product_id, $entry['images'] ?? array());
	$rates = array_values(array_filter(explode(',', (string) wholesale_get_setting('channel_shipping_options')), 'is_numeric'));
	$free_min = function_exists('wholesale_free_shipping_amount') ? wholesale_free_shipping_amount(true) : '';
	$faq = wholesale_product_page_faq($product_id);
	$link = static function ($path, $text) {
		$page = get_page_by_path(trim($path, '/'));
		return $page && 'publish' === $page->post_status ? '<a href="' . esc_url(get_permalink($page)) . '">' . esc_html($text) . '</a>' : esc_html($text);
	};
	$parts = $entry['parts'] + array(
		'Power supply' => '12V 5A 60W Class 2, IP67 power supply (Fang Hua HMA-60NU-RX) with a 5-year warranty, for dry locations, in a 3.5" x 5.8" x 13.5" transformer box (3.5" x 10" x 13.5" for two)',
		'Raceway (optional)' => '5.5" high x 3.5" deep, 10"–46" per section; longer signs use several sections. Each section comes with a base, cover, 2 connect plates, 4 mounting brackets and 20 self-tapping screws, plus 2 end caps per raceway',
		'Certification' => 'UL listed, with UL sign section labels for outdoor installation',
	);
	?>
	<section class="product-info cl-details" aria-label="<?php echo esc_attr(sprintf('%s details', $heading)); ?>">
		<div class="product-info-block product-specs">
			<h2><?php echo esc_html(sprintf('%s specifications', $heading)); ?></h2>
			<table>
				<tbody>
					<?php foreach ($parts as $part => $spec) : ?>
						<tr><th scope="row"><?php echo esc_html($part); ?></th><td><?php echo wp_kses_post($spec); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ($images) : ?>
				<div class="grid grid-lg-3">
					<?php foreach ($images as $image) : ?>
						<div><?php echo wp_kses_post(wholesale_seo_fill_missing_alt($image, $heading)); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<p>
				<?php if ($stainless) : ?>
					Faces and returns come in a range of stainless steel colors: <a href="<?php echo esc_url(content_url('/uploads/storefront-files/stainless-steel-channel-letter-color-chart.pdf')); ?>">download the stainless steel color chart (PDF)</a>.
				<?php else : ?>
					Choose trimcap, return, face and vinyl colors from the charts above, or <a href="<?php echo esc_url(home_url('/contact/')); ?>">request a printed color chart</a> from a sign specialist.
				<?php endif; ?>
			</p>
		</div>

		<?php if (!empty($entry['included'])) : ?>
			<div class="product-info-block">
				<h2>What&rsquo;s included</h2>
				<ul>
					<?php foreach ($entry['included'] as $item) : ?>
						<li><?php echo esc_html($item); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="product-info-block">
			<h2>Production &amp; shipping</h2>
			<ul>
				<li>Built to order in Washington and California, tested before it ships, and shipped to all 50 states.</li>
				<li>
					<?php
					echo esc_html(sprintf(
						'At checkout, choose standard shipping (3–6 business days)%s, 3-day, 2-day or overnight delivery; your estimated ship date is shown there too.',
						$rates ? sprintf(' from $%s', number_format((float) $rates[0], 0)) : ''
					));
					if ($free_min) {
						echo ' ' . esc_html(sprintf('Channel letter orders of %s or more ship free on the standard schedule.', $free_min));
					}
					?>
				</li>
				<li>Inspect the boxes before you sign for them. If the packaging is damaged, check the letters, file a claim with the carrier and email <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a> or call <a href="tel:+18664362101">866-436-2101</a>.</li>
				<li>Pickup isn&rsquo;t available; every order ships to you.</li>
			</ul>
		</div>

		<div class="product-info-block">
			<h2>Installation</h2>
			<p>We build, test and ship your sign; we don&rsquo;t install. A licensed electrician or sign installer near you mounts and connects the letters using the wiring diagram and mounting pattern that come with them. See <?php echo $link('/how-to-install-channel-letters/', 'how to install channel letters'); // Escaped in the closure. ?> and <?php echo $link('/sign-permits/', 'sign permits'); // Escaped in the closure. ?> before you schedule the work.</p>
		</div>

		<div class="product-info-block">
			<h2>5-year warranty</h2>
			<p>All 7 channel letter styles are covered against defects in materials and workmanship for five years from the date they ship. If something goes wrong, contact us before any repair work: repairs made without telling us first void the warranty. A covered sign is repaired, or replaced with an equivalent product. Damage from storms and other acts of nature, accidents, vandalism, misuse, or improper installation or care is not covered. Read the full <?php echo $link('/warranty/', 'channel letter warranty'); // Escaped in the closure. ?>.</p>
		</div>

		<?php if ($faq) : ?>
			<div class="product-info-block">
				<h2><?php echo esc_html(sprintf('%s questions & answers', $heading)); ?></h2>
				<div class="cl-faq-list">
					<?php
					foreach ($faq as $item) {
						printf('<details><summary>%s</summary><p>%s</p></details>', esc_html($item['q']), wp_kses_post($item['a']));
					}
					?>
				</div>
			</div>
		<?php endif; ?>

		<p class="product-info-prop65"><img src="<?php echo esc_url(content_url('/uploads/storefront-files/california-prop-65-warning-icon.png')); ?>" alt="California Proposition 65 warning symbol" width="27" height="25" loading="lazy" decoding="async"> <strong>WARNING:</strong> This product can expose you to chemicals including Diisononyl phthalate (DINP), which is known to the State of California to cause cancer. For more information go to <a href="https://www.P65Warnings.ca.gov" target="_blank" rel="noopener">www.P65Warnings.ca.gov</a>.</p>
	</section>
	<?php
	return true;
}

/**
 * The supplier's Spec section, ready to print: pickup text removed, images
 * given alt text and responsive sizes. Products without a Spec section use
 * the lists from their description.
 */
function wholesale_product_spec_html($product_id)
{
	// An entry's own spec rows win where the supplier's are wrong for the product.
	$entry = wholesale_product_page($product_id);
	if (!empty($entry['spec'])) {
		$rows = '';
		foreach ((array) $entry['spec'] as $label => $value) {
			$rows .= '<tr><th scope="row">' . esc_html($label) . '</th><td>' . wp_kses_post($value) . '</td></tr>';
		}
		return '<table><tbody>' . $rows . '</tbody></table>';
	}

	$sections = wholesale_product_supplier_sections($product_id);
	$html = isset($sections['spec']) ? $sections['spec'] : '';
	if ('' === trim(wp_strip_all_tags($html)) && !empty($sections['description']) && preg_match_all('#<ul\b.*?</ul>#is', $sections['description'], $lists)) {
		$html = implode('', $lists[0]);
	}
	if ('' === trim(wp_strip_all_tags($html))) {
		return '';
	}

	// Plain-text specs: "Label:" lines become bold labels and the lines under them a list.
	if (false === stripos($html, '<li')) {
		$html = preg_replace_callback('#<p>(.*?)</p>#is', static function ($match) {
			$lines = array_values(array_filter(array_map('trim', preg_split('#<br\s*/?>#i', $match[1])), 'strlen'));
			if (1 === count($lines) && preg_match('/:\s*$/', $lines[0])) {
				return '<strong>' . $lines[0] . '</strong>';
			}
			return '<ul><li>' . implode('</li><li>', $lines) . '</li></ul>';
		}, $html);
	}

	$html = wholesale_seo_clean_product_content($html);
	// The supplier's section dividers double up with this page's own borders.
	$html = preg_replace('#<hr\s*/?>#i', '', $html);
	// Unclosed wrapper <div>s from the supplier layout are dropped by balancing.
	$html = force_balance_tags($html);

	return wholesale_seo_fill_missing_alt($html, wholesale_seo_product_search_name($product_id));
}

/**
 * Template downloads, product diagrams and setup steps from the supplier's
 * File Setup and Installation Guide sections: everything except the generic
 * file rules, which wholesale_render_product_details() states once.
 */
function wholesale_product_templates_html($product_id)
{
	$sections = wholesale_product_supplier_sections($product_id);
	$html = '';

	if (!empty($sections['files'])) {
		$files = $sections['files'];
		// The generic rules: the first list, and "Additional Tips" with its list.
		$files = preg_replace('#^\s*(?:&nbsp;|\s|<p>\s*</p>)*<ul\b.*?</ul>#is', '', $files, 1);
		$files = preg_replace('#<strong>\s*Additional Tips\s*</strong>\s*<ul\b.*?</ul>#is', '', $files);
		$files = preg_replace('#<p>\s*Additional Tips\s*</p>\s*(?:<p>.*?</p>|<ul\b.*?</ul>)#is', '', $files);
		// The template rules list, restated in the shared setup text.
		$files = preg_replace('#<ul>\s*<li>\s*Do not adjust artboard size.*?</ul>#is', '', $files);
		// Plain-text products repeat the generic rules as paragraphs.
		$files = preg_replace('#<p>\s*Accepted File Formats:.*?</p>#is', '', $files);
		// Notes are restated in the entry's file_notes.
		$files = preg_replace('#<strong>\s*Note:\s*</strong>.*?(?=<strong>|</div>|$)#is', '', $files);
		$files = preg_replace('#<strong>\s*Note:.*?</strong>.*?(?=<strong>|</div>|$)#is', '', $files);
		$files = preg_replace('#<p>\s*(?:<strong>)?\s*Note:.*?</p>#is', '', $files);
		// Supplier FAQ lines that ended up inside the file setup section.
		$files = preg_replace('#<p>\s*Q:.*?</p>|<p>\s*A:.*?</p>#is', '', $files);
		if (preg_match('#<(?:table|img|a)\b#i', $files)) {
			$html .= $files;
		}
	}

	if (!empty($sections['setup']) && preg_match('#<(?:img|a)\b#i', $sections['setup'])) {
		$html .= '<p class="product-info-label"><strong>Setup guide</strong></p>' . $sections['setup'];
	}

	if ('' === trim(wp_strip_all_tags($html, true)) && false === stripos($html, '<img')) {
		return '';
	}

	$html = force_balance_tags(wholesale_seo_clean_product_content($html));

	return wholesale_seo_fill_missing_alt($html, wholesale_seo_product_search_name($product_id));
}

/**
 * Print the specifications, production and shipping, artwork, care and FAQ
 * sections that replace the supplier description. Returns false (printing
 * nothing) for a product without an entry, so the template can fall back.
 */
function wholesale_render_product_details($product_id)
{
	$entry = wholesale_product_page($product_id);
	if (!$entry) {
		return false;
	}

	$name = wholesale_seo_product_search_name($product_id);
	$printed = wholesale_product_is_printed($product_id);
	$turnaround = wholesale_product_turnaround($product_id);
	$spec = wholesale_product_spec_html($product_id);
	$templates = $printed ? wholesale_product_templates_html($product_id) : '';
	$care = isset($entry['care']) ? (string) $entry['care'] : '';
	$care_texts = wholesale_product_care_texts();
	if (isset($care_texts[$care])) {
		$care = $care_texts[$care];
	}
	$free_min = function_exists('wholesale_free_shipping_amount') ? wholesale_free_shipping_amount(false) : '';
	$rates = array_values(array_filter(explode(',', (string) wholesale_get_setting('standard_shipping_options')), 'is_numeric'));
	$faq = wholesale_product_page_faq($product_id);
	$templates_page = get_page_by_path('design-templates');
	?>
	<section class="product-info" aria-label="<?php echo esc_attr(sprintf('%s details', $name)); ?>">
		<?php if (!empty($entry['notes'])) : ?>
			<div class="product-info-block">
				<h2>Good to know</h2>
				<ul>
					<?php foreach ($entry['notes'] as $note) : ?>
						<li><?php echo wp_kses_post($note); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ($spec) : ?>
			<div class="product-info-block product-specs">
				<h2><?php echo esc_html(sprintf('%s specifications', $name)); ?></h2>
				<?php echo wp_kses_post($spec); ?>
			</div>
		<?php endif; ?>

		<div class="product-info-block">
			<h2>Production &amp; shipping</h2>
			<ul>
				<li><?php echo esc_html(wholesale_product_ship_sentence($product_id)); ?><?php echo $turnaround['bulk_extra'] ? esc_html(' Orders of more than 100 pieces need two extra business days.') : ''; ?></li>
				<li><?php echo esc_html($printed ? 'Printed to order in Renton, Washington, and shipped to all 50 states.' : 'Ships to all 50 states.'); ?></li>
				<li>
					<?php
					echo esc_html(sprintf(
						'At checkout, choose standard shipping (3–6 business days)%s, 3-day, 2-day or overnight delivery.',
						$rates ? sprintf(' from $%s', number_format((float) $rates[0], 0)) : ''
					));
					if ($free_min) {
						echo ' ' . esc_html(sprintf('Orders of %s or more ship free on the standard schedule.', $free_min));
					}
					?>
				</li>
				<li>Pickup isn&rsquo;t available; every order ships to you. See <a href="<?php echo esc_url(home_url('/shipping-returns/')); ?>">shipping &amp; returns</a> for details.</li>
			</ul>
		</div>

		<?php if ($printed) : ?>
			<div class="product-info-block">
				<h2>Artwork &amp; file setup</h2>
				<ul>
					<?php if (!empty($entry['files'])) : // Products with their own file rules (DTF takes PNG only). ?>
						<?php foreach ((array) $entry['files'] as $rule) : ?>
							<li><?php echo wp_kses_post($rule); ?></li>
						<?php endforeach; ?>
					<?php else : ?>
					<li>Upload a JPEG or a single-page PDF, up to 300 MB, built at the exact size you order. Artwork at a different size is scaled to fit.</li>
					<li>Use CMYK color, convert Pantone and spot colors to CMYK, and outline your fonts. Raster images need 150 dpi at full size.</li>
					<li>Leave off crop marks and bleed. Double sided items take one file per side unless the template says otherwise.</li>
					<?php endif; ?>
					<?php foreach (isset($entry['file_notes']) ? (array) $entry['file_notes'] : array() as $note) : ?>
						<li><?php echo wp_kses_post($note); ?></li>
					<?php endforeach; ?>
					<?php if ($templates) : ?>
						<li>When you use a template below, keep its artboard size and guidelines, design on the &ldquo;Artwork&rdquo; layer, and delete guide layers when it tells you to.</li>
					<?php endif; ?>
				</ul>
				<?php if ($templates) : ?>
					<div class="product-templates"><?php echo wp_kses_post($templates); ?></div>
				<?php endif; ?>
				<?php if ($templates_page && 'publish' === $templates_page->post_status) : ?>
					<p>Need help? Our <a href="<?php echo esc_url(get_permalink($templates_page)); ?>">design templates and artwork guide</a> covers every product, or a sign specialist can check your file before it prints.</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ($care) : ?>
			<div class="product-info-block">
				<h2>Care &amp; handling</h2>
				<p><?php echo wp_kses_post($care); ?></p>
			</div>
		<?php endif; ?>

		<?php if ($faq) : ?>
			<div class="product-info-block">
				<h2><?php echo esc_html(sprintf('%s questions & answers', $name)); ?></h2>
				<div class="cl-faq-list">
					<?php
					foreach ($faq as $item) {
						printf('<details><summary>%s</summary><p>%s</p></details>', esc_html($item['q']), wp_kses_post($item['a']));
					}
					?>
				</div>
			</div>
		<?php endif; ?>
	</section>
	<?php
	return true;
}
