<?php
/**
 * Search visibility helpers: removed-content status codes and one-time
 * database updates that ship with the theme.
 *
 * @package litsign
 */

/**
 * Whether a request path looks like one of the spam posts injected during the
 * September 2026 compromise (casino, betting and dating articles). Only used
 * for requests that already 404, so a false match changes nothing but the code.
 */
function wholesale_is_removed_spam_path($path)
{
	$slug = trim((string) $path, '/');
	if ('' === $slug) {
		return false;
	}

	$known = array('article-jule-24000', 'hello-world', 'hello-world-2');
	if (in_array($slug, $known, true)) {
		return true;
	}

	return (bool) preg_match(
		'/(?:^|[-\/])(?:1win|1vin|casino\w*|kazino\w*|kasino|bahis|bukmeker\w*|pin-?up|slots?|aviator|olympus|poker\w*|jackpot|bett(?:ing|ors?)|\w*bet|bonus\w*|spins?|apuestas|kumar|oyun\w*|stavki|zerkalo|igrov\w*|onlajn|onlayn|kripto\w*|billionairespin|dating|singles|marry|sigara\w*|jeux|jogos|gioco|giocatore|igaming|gambling|roulette|blackjack|bookmaker|vulkan|mostbet|melbet|fortune|reload|dealer|auszahlung\w*)(?:[-\/]|$)/i',
		$slug
	);
}

/**
 * Answer removed spam URLs with 410 Gone so search engines drop them faster
 * than a plain 404. The normal 404 template still renders.
 */
function wholesale_removed_spam_status()
{
	if (!is_404()) {
		return;
	}

	$path = wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH);
	$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
	if ($home_path && 0 === strpos((string) $path, $home_path)) {
		$path = substr((string) $path, strlen($home_path));
	}

	if (wholesale_is_removed_spam_path($path)) {
		status_header(410);
	}
}
add_action('template_redirect', 'wholesale_removed_spam_status', 2);

/**
 * Permanently redirect leftover placeholder pages to the page that replaces them.
 *
 * @return array Page slug => target URL.
 */
function wholesale_seo_page_redirects()
{
	return array(
		'sample-page' => home_url('/'),
		'sample-page-2' => home_url('/'),
	);
}

function wholesale_seo_redirect_pages()
{
	if (!is_page()) {
		return;
	}

	$slug = get_post_field('post_name', get_queried_object_id());
	$redirects = wholesale_seo_page_redirects();

	if ($slug && isset($redirects[$slug])) {
		wp_safe_redirect($redirects[$slug], 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_pages', 1);

/**
 * The site has no blog authors: /author/{name}/ pages are thin duplicates
 * that also reveal admin user names. Send them home and keep them out of
 * the sitemap.
 */
function wholesale_seo_redirect_author_archives()
{
	if (is_author()) {
		wp_safe_redirect(home_url('/'), 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_author_archives', 1);

add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
	return 'users' === $name ? false : $provider;
}, 10, 2);

/**
 * Product category links point at the clean /{category}/ routes the theme
 * serves, instead of /category/{slug}/ URLs that only redirect there.
 */
function wholesale_seo_product_category_link($url, $term, $taxonomy)
{
	return 'product_category' === $taxonomy ? wholesale_category_url($term->slug) : $url;
}
add_filter('term_link', 'wholesale_seo_product_category_link', 10, 3);

/**
 * Categories whose route is canonicalized or redirected elsewhere stay out of
 * the core sitemap.
 */
function wholesale_seo_sitemap_excluded_categories()
{
	$excluded = array('channel-letters');

	// /signs-letters/ redirects to the Storefront Signs guide once it is published.
	if (wholesale_seo_storefront_signs_url()) {
		$excluded[] = 'signs-letters';
	}

	return $excluded;
}

/**
 * URL of the published Storefront Signs guide, or ''.
 */
function wholesale_seo_storefront_signs_url()
{
	static $url = null;

	if (null === $url) {
		$page = get_page_by_path('storefront-signs');
		$url = $page && 'publish' === $page->post_status ? get_permalink($page) : '';
	}

	return $url;
}

/**
 * /signs-letters/ listed the same seven channel letter products as the home
 * page; send it to the Storefront Signs guide instead.
 */
function wholesale_seo_redirect_signs_letters()
{
	if ('signs-letters' === get_query_var('category_slug') && ($url = wholesale_seo_storefront_signs_url())) {
		wp_safe_redirect($url, 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_signs_letters', 1);

/**
 * /channel-letters/ rendered the same letters as the channel letters landing
 * page; send it (and its link equity) to /custom-channel-letters/.
 */
function wholesale_seo_redirect_channel_letters()
{
	if ('channel-letters' === get_query_var('category_slug') && wholesale_channel_letters_landing_id()) {
		$url = get_permalink(wholesale_channel_letters_landing_id());
		if (!empty($_SERVER['QUERY_STRING'])) {
			$url .= '?' . wp_unslash($_SERVER['QUERY_STRING']);
		}
		wp_safe_redirect($url, 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_channel_letters', 1);

/**
 * The site has no blog, so RSS feeds (/feed/, /comments/feed/, ...) are empty
 * pages Google keeps crawling; send them to the home page.
 */
function wholesale_seo_redirect_feeds()
{
	if (is_feed()) {
		wp_safe_redirect(home_url('/'), 301);
		exit;
	}
}
add_action('template_redirect', 'wholesale_seo_redirect_feeds', 1);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

function wholesale_seo_sitemap_taxonomy_args($args, $taxonomy)
{
	if ('product_category' !== $taxonomy) {
		return $args;
	}

	$excluded = array();
	foreach (wholesale_seo_sitemap_excluded_categories() as $slug) {
		$term = get_term_by('slug', $slug, 'product_category');
		if ($term && !is_wp_error($term)) {
			$excluded[] = (int) $term->term_id;
		}
	}

	if ($excluded) {
		$args['exclude'] = array_merge(isset($args['exclude']) ? (array) $args['exclude'] : array(), $excluded);
	}

	return $args;
}
add_filter('wp_sitemaps_taxonomies_query_args', 'wholesale_seo_sitemap_taxonomy_args', 10, 2);

/**
 * Menu links that point at the wrong or a retired URL, keyed by path.
 *
 * @return array Path without trailing slash => site path to use instead.
 */
function wholesale_seo_link_fixes()
{
	return array(
		// The old slug now redirects to Tension Fabric, a different product.
		'/product/fabric-banner-9oz-wrinkle-free' => '/product/fabric-banner-9oz-wrinkle-free-copy/',
		// The channel letter route redirects to the channel letters landing page.
		'/channel-letters' => wholesale_channel_letters_landing_id()
			? '/' . get_page_uri(wholesale_channel_letters_landing_id()) . '/'
			: '/',
	);
}

/**
 * Normalize an internal link to its final URL: absolute, on this site's base
 * path, clean category routes, and a trailing slash, so a click never costs a
 * redirect. External, anchor, tel:, mailto: and sms: links are returned as-is.
 */
function wholesale_seo_normalize_internal_url($url)
{
	$url = trim((string) $url);
	if ('' === $url || '#' === $url[0] || preg_match('/^(?:tel|mailto|sms|javascript):/i', $url)) {
		return $url;
	}

	$parts = wp_parse_url($url);
	if (false === $parts) {
		return $url;
	}

	$home = wp_parse_url(home_url('/'));
	if (!empty($parts['host']) && strcasecmp($parts['host'], $home['host']) !== 0) {
		return $url;
	}

	$path = isset($parts['path']) ? $parts['path'] : '/';
	$home_path = isset($home['path']) ? rtrim($home['path'], '/') : '';
	if ($home_path && 0 === strpos($path, $home_path . '/')) {
		$path = substr($path, strlen($home_path));
	}
	$path = '/' . ltrim($path, '/');

	if (!empty($parts['query'])) {
		parse_str($parts['query'], $query);
		if (!empty($query['category_slug']) && 1 === count($query) && '/' === $path) {
			$term = get_term_by('slug', sanitize_title($query['category_slug']), 'product_category');
			if ($term && !is_wp_error($term)) {
				$path = '/' . $term->slug;
				unset($parts['query']);
			}
		}
	}

	$fixes = wholesale_seo_link_fixes();
	$key = untrailingslashit($path);
	if (isset($fixes[$key])) {
		$path = $fixes[$key];
	} elseif (!pathinfo($path, PATHINFO_EXTENSION)) {
		$path = trailingslashit($path);
	}

	$normalized = home_url($path);
	if (!empty($parts['query'])) {
		$normalized .= '?' . $parts['query'];
	}
	if (!empty($parts['fragment'])) {
		$normalized .= '#' . $parts['fragment'];
	}

	return $normalized;
}

function wholesale_seo_normalize_menu_urls($items)
{
	foreach ($items as $item) {
		if (!empty($item->url)) {
			$item->url = wholesale_seo_normalize_internal_url($item->url);
		}
	}

	return $items;
}
add_filter('wp_nav_menu_objects', 'wholesale_seo_normalize_menu_urls', 20);

/**
 * Rewrite this site's http:// URLs to https:// in a page served over HTTPS.
 *
 * Product gallery meta and some stored content still hold http:// image URLs
 * (the site address was http when they were saved), which browsers flag as
 * mixed content. Setting both site addresses to https in Settings > General
 * and running a search-replace fixes the data; this keeps pages clean either way.
 */
function wholesale_seo_force_https_urls($html)
{
	$host = wp_parse_url(home_url('/'), PHP_URL_HOST);
	if (!$host) {
		return $html;
	}

	return str_replace(
		array('http://' . $host, 'http:\/\/' . $host, 'http://www.' . $host, 'http:\/\/www.' . $host),
		array('https://' . $host, 'https:\/\/' . $host, 'https://www.' . $host, 'https:\/\/www.' . $host),
		$html
	);
}

function wholesale_seo_start_https_buffer()
{
	if (!is_ssl() || is_admin() || wp_doing_ajax() || is_feed()) {
		return;
	}

	ob_start('wholesale_seo_force_https_urls');
}
add_action('template_redirect', 'wholesale_seo_start_https_buffer', 0);

/**
 * One-time database updates that travel with the theme. Each step runs once,
 * for an administrator, and is recorded so it never repeats.
 */
function wholesale_seo_migrations()
{
	return array(
		'2026-10-remove-spam-categories' => 'wholesale_seo_migrate_remove_spam_categories',
		'2026-10-storefront-signs-page' => 'wholesale_seo_migrate_storefront_signs_page',
		'2026-10-image-alt-text' => 'wholesale_seo_migrate_image_alt_text',
		'2026-10-product-copy-titles' => 'wholesale_seo_migrate_product_copy_titles',
		'2026-10-policy-drafts' => 'wholesale_seo_migrate_policy_drafts',
		'2026-10-remove-supplier-references' => 'wholesale_seo_migrate_remove_supplier_references',
		'2026-10-banners-displays-page' => 'wholesale_seo_migrate_banners_displays_page',
		'2026-10-coroplast-description' => 'wholesale_seo_migrate_coroplast_description',
		'2026-10-channel-letter-cost-page' => 'wholesale_seo_migrate_channel_letter_cost_page',
		'2026-10-design-templates-page' => 'wholesale_seo_migrate_design_templates_page',
		'2026-10-storefront-media' => 'wholesale_seo_migrate_storefront_media',
		'2026-10-supplier-text' => 'wholesale_seo_migrate_supplier_text',
	);
}

function wholesale_seo_run_migrations()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$done = get_option('wholesale_seo_migrations_done', array());
	$done = is_array($done) ? $done : array();

	foreach (wholesale_seo_migrations() as $key => $callback) {
		if (in_array($key, $done, true) || !is_callable($callback)) {
			continue;
		}

		if (false !== call_user_func($callback)) {
			$done[] = $key;
			update_option('wholesale_seo_migrations_done', $done, false);
		}
	}
}
add_action('admin_init', 'wholesale_seo_run_migrations');

/**
 * Delete the empty post categories the spam injection created.
 */
function wholesale_seo_migrate_remove_spam_categories()
{
	$slugs = array('bez-rubriki', 'pinup', 'pu', 'bh-top', 'bt', 'btprod', 'casinom-hub', 'gatesofolympus-link', 'marsbet', 'pb-top', 'sahabet');

	foreach ($slugs as $slug) {
		$term = get_term_by('slug', $slug, 'category');
		if ($term && !is_wp_error($term) && 0 === (int) $term->count) {
			wp_delete_term($term->term_id, 'category');
		}
	}

	return true;
}

/**
 * Create the Storefront Signs guide page. Its content comes from
 * page-storefront-signs.php, so the page itself stays empty.
 */
function wholesale_seo_migrate_storefront_signs_page()
{
	if (get_page_by_path('storefront-signs', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Storefront Signs',
		'post_name' => 'storefront-signs',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Create the Banners & Displays landing page. Its content comes from
 * page-banners-displays.php, so the page itself stays empty.
 */
function wholesale_seo_migrate_banners_displays_page()
{
	if (get_page_by_path('banners-displays', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Banners & Displays',
		'post_name' => 'banners-displays',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Fill empty alt text on media library images from the product each image
 * belongs to. Existing alt text is never changed.
 */
function wholesale_seo_migrate_image_alt_text()
{
	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
	));

	foreach ($attachments as $attachment_id) {
		if ('' !== trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true))) {
			continue;
		}

		$alt = wholesale_seo_attachment_alt($attachment_id);
		if ('' !== $alt) {
			update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt));
		}
	}

	return true;
}

/**
 * Two products were published with " Copy" left in their names from being
 * duplicated. Only the displayed name changes; the URLs stay the same.
 */
function wholesale_seo_migrate_product_copy_titles()
{
	$titles = array(
		'fabric-banner-9oz-wrinkle-free-copy' => 'Fabric Banner (9oz. Wrinkle Free)',
		'sd-retractable-sd-retractable-insert-only' => 'SD Retractable',
	);

	foreach ($titles as $slug => $title) {
		$product = get_page_by_path($slug, OBJECT, 'product');
		if ($product && preg_match('/\sCopy$/', $product->post_title)) {
			wp_update_post(array('ID' => $product->ID, 'post_title' => $title));
		}
	}

	return true;
}

/**
 * Save draft Privacy Policy and Shipping & Returns text for the owner to
 * review and publish. Never publishes anything, and only replaces the
 * privacy page's content while it still holds WordPress's sample text.
 */
function wholesale_seo_migrate_policy_drafts()
{
	require_once get_template_directory() . '/inc/policy-drafts.php';

	$privacy_id = (int) get_option('wp_page_for_privacy_policy');
	$privacy = $privacy_id ? get_post($privacy_id) : null;
	if ($privacy && 'publish' !== $privacy->post_status && false !== strpos($privacy->post_content, 'privacy-policy-tutorial')) {
		wp_update_post(array(
			'ID' => $privacy_id,
			'post_content' => wholesale_policy_privacy_draft(),
		));
	}

	if (!get_page_by_path('shipping-returns', OBJECT, 'page')) {
		wp_insert_post(array(
			'post_type' => 'page',
			'post_status' => 'draft',
			'post_title' => 'Shipping & Returns',
			'post_name' => 'shipping-returns',
			'post_content' => str_replace('href="/terms-conditions/"', 'href="' . esc_url(home_url('/terms-conditions/')) . '"', wholesale_policy_shipping_draft()),
			'comment_status' => 'closed',
			'ping_status' => 'closed',
		));
	}

	return true;
}

/**
 * The Coroplast product was saved with the Magnets product's copy (description,
 * specs, FAQ, bullets and card text all describe a 30 mil magnet). Replace it
 * with copy built from the product's own options: 4mm white Coroplast, UV
 * printed matte, one or two sides, free grommets, optional H-stake. Only runs
 * while the magnet text is still there, so owner edits are never overwritten.
 */
function wholesale_seo_migrate_coroplast_description()
{
	$product = get_page_by_path('coroplast', OBJECT, 'product');
	if (!$product || false === strpos($product->post_content, 'Our magnet sheet')) {
		return true;
	}

	$intro = strstr($product->post_content, '<h4 id="description">', true);
	$body = '<h4 id="description">Description</h4>
Custom Coroplast signs are printed on 4mm white corrugated plastic: lightweight, waterproof and rigid enough to stand up outdoors. They are the go-to material for yard signs, real estate and open house signs, political and event signs, construction site and directional signs, and temporary storefront promotions. Graphics are UV printed for a long lasting matte finish.

Choose a standard size or enter your own, print one or both sides, and add an H-stake to put your sign straight into the ground.

<hr />

<h4 id="spec">Spec</h4>
<strong>Material:</strong>
<ul>
 	<li>4mm White Coroplast (corrugated plastic)</li>
</ul>
<strong>Print</strong>
<ul>
 	<li>UV Ink - Matte Finish</li>
 	<li>1 side or 2 sides</li>
</ul>
<strong>Product Attributes:</strong>
<ul>
 	<li>Standard sizes from 18" x 12" to 24" x 36", or custom sizes up to 4 ft x 8 ft</li>
 	<li>Grommets in all four corners or the top two corners, free of charge</li>
 	<li>Optional H-stake for yard and lawn installs</li>
 	<li>Indoor or outdoor; waterproof</li>
</ul>
<strong>See Also:</strong>
<ul>
 	<li><a href="/product/reflective-coroplast-sign-hstake/">Reflective Coroplast</a></li>
 	<li><a href="/product/dry-erase-coroplast-sign-hstake/">Dry Erase Coroplast</a></li>
 	<li><a href="/product/yard-sign-and-h-stake/">Yard Sign and H-Stake</a></li>
</ul>

<hr />

<h4 id="file-setup">File Setup</h4>
<ul>
 	<li>Accepted File Formats: JPEG or PDF (single page only)</li>
 	<li>Color Space: CMYK</li>
 	<li>Resolution: 150dpi for raster images (More than enough for large format)</li>
 	<li>Max File Upload Size: 300MB</li>
 	<li>Submit artwork built to ordered size - Scaled artwork is automatically detected and fit to order</li>
 	<li>Do not include crop marks or bleeds</li>
</ul>
<strong>Additional Tips</strong>
<ul>
 	<li>Do not submit with Pantones/Spot Colors - Convert to CMYK</li>
 	<li>Convert live fonts to outlines</li>
 	<li>Use provided design templates when available</li>
</ul>

<hr />

<h4 id="frequently-asked-questions">Frequently asked questions</h4>
<ul>
 	<li>Q: Can I print both sides of my Coroplast sign?</li>
 	<li>A: Yes. Choose 2 Sides when you order and both faces are printed.</li>
 	<li>Q: Do Coroplast signs come with stakes?</li>
 	<li>A: H-stakes are an optional add-on. Choose Yes for H-Stake when you order.</li>
 	<li>Q: Is there a charge for grommets?</li>
 	<li>A: No. Grommets in all four corners or the top two corners are free.</li>
</ul>';

	$body = str_replace('href="/product/', 'href="' . esc_url(home_url('/product/')), $body);

	wp_update_post(array(
		'ID' => $product->ID,
		'post_content' => (false !== $intro ? $intro : '') . $body,
	));

	update_post_meta($product->ID, '_product_short_desc', '<ul>
 	<li>4mm white Coroplast (corrugated plastic)</li>
 	<li>UV printed matte finish - indoor and outdoor ready</li>
 	<li>Print 1 or 2 sides, free grommets</li>
 	<li>Optional H-stake for yard signs</li>
</ul>');
	update_post_meta($product->ID, '_product_list_desc', '<ul>
 	<li>4mm corrugated plastic yard signs</li>
 	<li>Waterproof, optional H-stake</li>
</ul>');

	return true;
}

/**
 * Create the Channel Letter Cost guide page. Its content comes from
 * page-channel-letter-cost.php, so the page itself stays empty.
 */
function wholesale_seo_migrate_channel_letter_cost_page()
{
	if (get_page_by_path('channel-letter-cost', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Channel Letter Cost',
		'post_name' => 'channel-letter-cost',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}

/**
 * Create the Design Templates page (artwork guide and every template
 * download). Its content comes from page-design-templates.php, so the page
 * itself stays empty.
 */
function wholesale_seo_migrate_design_templates_page()
{
	if (get_page_by_path('design-templates', OBJECT, 'page')) {
		return true;
	}

	$page_id = wp_insert_post(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_title' => 'Design Templates',
		'post_name' => 'design-templates',
		'post_content' => '',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
	), true);

	return !is_wp_error($page_id);
}
