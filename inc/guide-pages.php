<?php
/**
 * Guide, location and industry pages.
 *
 * Every page here is described in code and data files, not in the editor:
 * - the help hubs (/sign-permits/, /how-to-install-channel-letters/,
 *   /warranty/, /shipping-returns/),
 * - /locations/ with one page per state and city pages, fed by
 *   data/locations/{state}.php plus one file per city in
 *   data/locations/{state}/{city}.php,
 * - /industries/ with one page per industry, fed by data/industries.php.
 *
 * The WordPress page for each entry is created (or re-parented) on the next
 * admin page load after the registry changes. Each entry has a status:
 * 'review' pages render normally but are noindexed and left out of the
 * sitemaps and footer; 'live' pages are indexed. Switch an entry (or a whole
 * state file) to 'live' only after the content has been checked.
 *
 * @package litsign
 */

/**
 * Load a data file from the theme's data/ folder.
 *
 * @return array
 */
function wholesale_guide_data_file($relative)
{
	$path = get_template_directory() . '/data/' . $relative;
	if (!file_exists($path)) {
		return array();
	}

	$data = include $path;
	return is_array($data) ? $data : array();
}

/**
 * Every state file in data/locations/, keyed by state slug, in the order the
 * hub lists them (alphabetical by state name).
 *
 * City guides live either inline in the state file ('cities') or in their own
 * file, data/locations/{state}/{city}.php, which returns one city array (same
 * keys as an inline city). A city file wins over an inline city with the same
 * slug; its slug is the file name unless the array sets 'slug'.
 *
 * @return array
 */
function wholesale_guide_states()
{
	static $states = null;

	if (null === $states) {
		$states = array();
		$dir = get_template_directory() . '/data/locations/';
		foreach ((array) glob($dir . '*.php') as $file) {
			$state = wholesale_guide_data_file('locations/' . basename($file));
			if (empty($state['slug'])) {
				continue;
			}

			$state['cities'] = isset($state['cities']) ? (array) $state['cities'] : array();
			foreach ((array) glob($dir . $state['slug'] . '/*.php') as $city_file) {
				$city = wholesale_guide_data_file('locations/' . $state['slug'] . '/' . basename($city_file));
				if (empty($city['name'])) {
					continue;
				}
				$city_slug = !empty($city['slug']) ? $city['slug'] : basename($city_file, '.php');
				$city['file'] = $city_file;
				$state['cities'][$city_slug] = $city;
			}
			$state['file'] = $file;

			$states[$state['slug']] = $state;
		}
		uasort($states, static function ($a, $b) {
			return strcmp($a['name'], $b['name']);
		});
	}

	return $states;
}

/**
 * @return array Industry entries keyed by slug.
 */
function wholesale_guide_industries()
{
	static $industries = null;

	if (null === $industries) {
		$industries = array();
		foreach (wholesale_guide_data_file('industries.php') as $industry) {
			if (!empty($industry['slug'])) {
				$industries[$industry['slug']] = $industry;
			}
		}
	}

	return $industries;
}

/**
 * All pages this module owns, keyed by page path ("locations/washington/seattle").
 *
 * Each entry: title (page and H1 base), template, status, seo_title,
 * seo_description, and for data-driven pages the type and data keys.
 *
 * @return array
 */
function wholesale_guide_registry()
{
	static $registry = null;

	if (null !== $registry) {
		return $registry;
	}

	$registry = array(
		'sign-permits' => array(
			'title' => 'Sign Permits',
			'template' => 'page-sign-permits.php',
			'status' => 'live',
			'seo_title' => 'Sign Permits for Lit Storefront Signs: What You Need',
			'seo_description' => 'How sign permits work for channel letters: what the city asks for, sign drawings, UL labels, and the checklist your licensed electrician needs.',
		),
		'how-to-install-channel-letters' => array(
			'title' => 'How to Install Channel Letters',
			'template' => 'page-how-to-install-channel-letters.php',
			'status' => 'live',
			'seo_title' => 'How to Install Channel Letters: Raceway vs. Direct Mount',
			'seo_description' => 'A step-by-step channel letter installation overview for your licensed installer: raceway or direct mount, the install pattern, power and inspection.',
		),
		'warranty' => array(
			'title' => 'Warranty',
			'template' => 'page-warranty.php',
			// Owner approved going live 2026-10-11; letter types and labor coverage still unconfirmed.
			'status' => 'live',
			'seo_title' => '5-Year Channel Letter Warranty | Storefront Sign Online',
			'seo_description' => 'What our five-year channel letter warranty covers, what it excludes, and how to make a claim. Every sign is tested before it ships.',
		),
		'shipping-returns' => array(
			'title' => 'Shipping & Returns',
			'template' => 'page-shipping-returns.php',
			'status' => 'live',
			'seo_title' => 'Shipping & Returns | Storefront Sign Online',
			'seo_description' => 'Shipping options for made-to-order signs (standard, 3-day, 2-day, overnight) to all 50 states, plus how cancellations, reprints and damage claims work.',
		),
		'locations' => array(
			'title' => 'Locations',
			'template' => 'page-locations.php',
			'status' => 'live',
			'seo_title' => 'Storefront Signs Shipped to Every State | Permit Guides',
			'seo_description' => 'Channel letters and storefront signs shipped from Washington and California to all 50 states, with state guides to sign permits and licensed installers.',
		),
		'industries' => array(
			'title' => 'Industries',
			'template' => 'page-industries.php',
			'status' => 'live',
			'seo_title' => 'Business Signs by Industry | Storefront Sign Online',
			'seo_description' => 'Sign ideas and the right products for restaurants, salons, medical offices, retail stores, franchises and events, with prices shown online.',
		),
	);

	foreach (wholesale_guide_states() as $state_slug => $state) {
		$registry['locations/' . $state_slug] = array(
			'title' => $state['name'],
			'template' => 'page-location.php',
			'status' => isset($state['status']) ? $state['status'] : 'review',
			'seo_title' => $state['seo_title'],
			'seo_description' => $state['seo_description'],
			'type' => 'state',
			'state' => $state_slug,
		);

		foreach (isset($state['cities']) ? (array) $state['cities'] : array() as $city_slug => $city) {
			$registry['locations/' . $state_slug . '/' . $city_slug] = array(
				'title' => $city['name'],
				'template' => 'page-location.php',
				'status' => isset($city['status']) ? $city['status'] : (isset($state['status']) ? $state['status'] : 'review'),
				'seo_title' => $city['seo_title'],
				'seo_description' => $city['seo_description'],
				'type' => 'city',
				'state' => $state_slug,
				'city' => $city_slug,
			);
		}
	}

	foreach (wholesale_guide_industries() as $industry_slug => $industry) {
		$registry['industries/' . $industry_slug] = array(
			'title' => $industry['name'],
			'template' => 'page-industry.php',
			'status' => isset($industry['status']) ? $industry['status'] : 'review',
			'seo_title' => $industry['seo_title'],
			'seo_description' => $industry['seo_description'],
			'type' => 'industry',
			'industry' => $industry_slug,
		);
	}

	return $registry;
}

/**
 * Registry path of the page being viewed, or ''.
 */
function wholesale_guide_current_path()
{
	if (!is_page()) {
		return '';
	}

	$path = get_page_uri(get_queried_object_id());
	return isset(wholesale_guide_registry()[$path]) ? $path : '';
}

/**
 * Registry entry for the page being viewed, or an empty array.
 */
function wholesale_guide_current()
{
	$path = wholesale_guide_current_path();
	return $path ? wholesale_guide_registry()[$path] : array();
}

function wholesale_guide_is_live($path)
{
	$registry = wholesale_guide_registry();
	return isset($registry[$path]) && 'live' === $registry[$path]['status'];
}

/**
 * URL of a registry page, or '' when its WordPress page doesn't exist yet or
 * isn't approved. Pages in review are linked only from other pages in review,
 * so reviewers can click through while live pages never link to them; pass
 * $live_only to require an approved page everywhere (footer).
 */
function wholesale_guide_url($path, $live_only = false)
{
	if (!wholesale_guide_is_live($path)) {
		$current = wholesale_guide_current();
		if ($live_only || !$current || 'live' === $current['status']) {
			return '';
		}
	}

	$page = get_page_by_path($path);
	return $page && 'publish' === $page->post_status ? get_permalink($page) : '';
}

/**
 * Create, publish and re-parent the WordPress pages for the registry. Runs for
 * an administrator whenever the registry's paths change, so a new state file
 * gets its pages on the next admin page load. Page content stays empty: the
 * templates render everything. Existing pages keep their content and title.
 */
function wholesale_guide_sync_pages()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$registry = wholesale_guide_registry();
	$version = md5(implode('|', array_keys($registry)) . '|v1');
	if ($version === get_option('wholesale_guide_pages_version')) {
		return;
	}

	// Parents before children: "locations" sorts before "locations/washington".
	$paths = array_keys($registry);
	sort($paths);

	foreach ($paths as $path) {
		$parent_path = false !== strpos($path, '/') ? substr($path, 0, strrpos($path, '/')) : '';
		$parent = $parent_path ? get_page_by_path($parent_path) : null;
		if ($parent_path && !$parent) {
			continue;
		}

		$slug = basename($path);
		$page = get_page_by_path($path, OBJECT, 'page');

		if (!$page) {
			wp_insert_post(array(
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_title' => $registry[$path]['title'],
				'post_name' => $slug,
				'post_parent' => $parent ? (int) $parent->ID : 0,
				'post_content' => '',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
			));
		} elseif ('publish' !== $page->post_status) {
			// The Shipping & Returns draft saved by an earlier update.
			wp_update_post(array('ID' => $page->ID, 'post_status' => 'publish'));
		}
	}

	update_option('wholesale_guide_pages_version', $version, false);
}
add_action('admin_init', 'wholesale_guide_sync_pages', 20);

/**
 * Render registry pages with their template whatever the page's own
 * template setting says.
 */
function wholesale_guide_template($template)
{
	$entry = wholesale_guide_current();
	if ($entry) {
		$located = locate_template($entry['template']);
		if ($located) {
			return $located;
		}
	}

	return $template;
}
add_filter('template_include', 'wholesale_guide_template', 20);

/**
 * Feed the registry's title and description to the theme's SEO head, which
 * reads _seo_title and _seo_description for pages.
 */
function wholesale_guide_seo_meta($value, $object_id, $meta_key, $single)
{
	if ('_seo_title' !== $meta_key && '_seo_description' !== $meta_key) {
		return $value;
	}

	$post = get_post($object_id);
	if (!$post || 'page' !== $post->post_type) {
		return $value;
	}

	$registry = wholesale_guide_registry();
	$path = get_page_uri($post);
	if (!isset($registry[$path])) {
		return $value;
	}

	$text = '_seo_title' === $meta_key ? $registry[$path]['seo_title'] : $registry[$path]['seo_description'];
	return $single ? $text : array($text);
}
add_filter('get_post_metadata', 'wholesale_guide_seo_meta', 10, 4);

/**
 * Pages still in review stay out of search results.
 */
function wholesale_guide_robots($robots)
{
	$entry = wholesale_guide_current();
	if ($entry && 'live' !== $entry['status']) {
		unset($robots['index'], $robots['max-image-preview']);
		$robots['noindex'] = true;
		$robots['follow'] = true;
	}

	return $robots;
}
add_filter('wp_robots', 'wholesale_guide_robots', 20);

/**
 * IDs of registry pages still in review, for the sitemaps.
 *
 * @return int[]
 */
function wholesale_guide_review_page_ids()
{
	$ids = array();
	foreach (wholesale_guide_registry() as $path => $entry) {
		if ('live' === $entry['status']) {
			continue;
		}
		$page = get_page_by_path($path);
		if ($page) {
			$ids[] = (int) $page->ID;
		}
	}

	return $ids;
}

add_filter('wholesale_seo_noindex_page_ids', function ($ids) {
	return array_values(array_unique(array_merge($ids, wholesale_guide_review_page_ids())));
});

/**
 * Styles for the guide pages: the shared guide page styles plus their own.
 */
function wholesale_guide_styles()
{
	if (!wholesale_guide_current()) {
		return;
	}

	$uri = get_template_directory_uri();
	$dir = get_template_directory();
	wp_enqueue_style('wholesale-seo-pages', $uri . '/css/seo-pages.css', array('custom-style'), (string) filemtime($dir . '/css/seo-pages.css'));
	wp_enqueue_style('wholesale-guides', $uri . '/css/guides.css', array('wholesale-seo-pages'), (string) filemtime($dir . '/css/guides.css'));
}
add_action('wp_enqueue_scripts', 'wholesale_guide_styles', 20);

/**
 * The organization these pages describe: ships from Washington and California, does not
 * install. Used as the provider of Service entities on guide pages.
 */
function wholesale_guide_organization_ref()
{
	return array('@id' => trailingslashit(home_url('/')) . '#organization');
}

/**
 * Add a Service entity to the page's structured data (state, city and
 * industry pages). areaServed is where we ship, never where we are.
 */
function wholesale_guide_add_service_schema($name, $description, $area_served = null, $extra = array())
{
	$service = array(
		'@type' => 'Service',
		'@id' => trailingslashit(get_permalink()) . '#service',
		'name' => $name,
		'serviceType' => 'Custom sign manufacturing and shipping',
		'description' => $description,
		'url' => get_permalink(),
		'provider' => wholesale_guide_organization_ref(),
		'areaServed' => $area_served ? $area_served : array('@type' => 'Country', 'name' => 'United States'),
	);

	$GLOBALS['wholesale_page_entities'][] = array_merge($service, (array) $extra);
}

/**
 * Schema.org State or City for a location page, with its Wikipedia page as
 * sameAs so Portland, OR and Portland, ME can't be confused. A city sits in
 * its county (when the data names one), and the county in the state.
 *
 * @param array      $state State data.
 * @param array|null $city  City data, or null for the state itself.
 * @return array
 */
function wholesale_guide_place_entity($state, $city = null)
{
	$state_entity = array(
		'@type' => 'State',
		'name' => wholesale_schema_text($state['name']),
		'containedInPlace' => array('@type' => 'Country', 'name' => 'United States'),
	);
	if (!empty($state['wikipedia'])) {
		$state_entity['sameAs'] = $state['wikipedia'];
	}
	if (!$city) {
		return $state_entity;
	}

	$parent = $state_entity;
	if (!empty($city['county'])) {
		$parent = array(
			'@type' => 'AdministrativeArea',
			'name' => wholesale_schema_text($city['county'] . ', ' . $state['name']),
			'containedInPlace' => $state_entity,
		);
	}

	$city_entity = array(
		'@type' => 'City',
		'name' => wholesale_schema_text($city['name']),
		'containedInPlace' => $parent,
	);
	if (!empty($city['wikipedia'])) {
		$city_entity['sameAs'] = $city['wikipedia'];
	}

	return $city_entity;
}

/**
 * When a location entry's facts were last checked: its 'updated' date
 * (YYYY-MM-DD), else the data file's modification time.
 *
 * @param array $place      State or city data.
 * @param bool  $date_only  True to return '' when there is no 'updated' date.
 * @return string Y-m-d, or ''.
 */
function wholesale_guide_updated($place, $date_only = false)
{
	if (!empty($place['updated']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $place['updated'])) {
		return $place['updated'];
	}
	if ($date_only || empty($place['file']) || !file_exists($place['file'])) {
		return '';
	}

	return gmdate('Y-m-d', (int) filemtime($place['file']));
}

/**
 * Product families on location pages, in display order: the page each links
 * to, where it is made and the product whose card price is quoted. Only
 * channel letters are called signs.
 *
 * @return array
 */
function wholesale_guide_product_families()
{
	return array(
		'channel-letters' => array(
			'label' => 'Lit channel letters',
			'url' => home_url('/custom-channel-letters/'),
			'made' => 'Built in Washington and California',
			'price' => 'standard-channel-letter-front-lit',
		),
		'banners' => array(
			'label' => 'Banners and banner stands',
			'url' => wholesale_category_url('banners'),
			'made' => 'Made in Renton, WA',
			'price' => '13oz-vinyl-banner',
			'more' => array(wholesale_category_url('banner-stands') => 'Banner stands'),
		),
		'window-graphics' => array(
			'label' => 'Window graphics and vinyl',
			'url' => home_url('/window-graphics/'),
			'made' => 'Made in Renton, WA',
			'price' => 'adhesive-window-perf',
		),
		'flags-aframes' => array(
			'label' => 'Flags and A-frames',
			'url' => wholesale_category_url('advertising-flags'),
			'made' => 'Made in Renton, WA',
			'price' => 'feather-angled-flag-pole',
			'more' => array(wholesale_category_url('signicade-a-frames') => 'A-frames'),
		),
		'displays' => array(
			'label' => 'Trade show displays',
			'url' => wholesale_category_url('trade-show-products'),
			'made' => 'Made in Renton, WA',
			'price' => 'curved-tension-fabric-display-graphic-frame',
		),
	);
}

/**
 * The product family cards for a place. A card carries the place's own
 * 'categories' note when it has one; there is no generic fallback text, so
 * the same sentences don't repeat across hundreds of location pages.
 */
function wholesale_guide_render_families($place)
{
	$notes = isset($place['categories']) ? (array) $place['categories'] : array();
	$families = wholesale_guide_product_families();
	wholesale_seo_prime_products(wp_list_pluck($families, 'price'));
	?>
	<div class="gd-family-grid">
		<?php foreach ($families as $key => $family) : ?>
			<?php $price = wholesale_price_no_cents(wholesale_seo_product_starting_text($family['price'])); ?>
			<article class="gd-family">
				<h3><a href="<?php echo esc_url($family['url']); ?>"><?php echo esc_html($family['label']); ?></a></h3>
				<p class="gd-family-made"><?php echo esc_html($family['made']); ?></p>
				<?php if (!empty($notes[$key])) : ?>
					<p><?php echo wp_kses_post($notes[$key]); ?></p>
				<?php endif; ?>
				<?php if ($price) : ?>
					<p class="sf-type-price"><small>Starting at</small> <?php echo wp_kses_post($price); ?></p>
				<?php endif; ?>
				<?php if (!empty($family['more'])) : ?>
					<p class="gd-family-more">
						<?php foreach ($family['more'] as $url => $label) : ?>
							<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * OfferCatalog of the product families a location page shows, for its
 * Service entity. No prices: they change and live on the product pages.
 */
function wholesale_guide_offer_catalog($place_name)
{
	$items = array();
	foreach (wholesale_guide_product_families() as $family) {
		$items[] = array(
			'@type' => 'Offer',
			'itemOffered' => array(
				'@type' => 'Service',
				'name' => $family['label'],
				'url' => $family['url'],
			),
		);
	}

	return array(
		'@type' => 'OfferCatalog',
		'name' => wholesale_schema_text(sprintf('Signs and graphics shipped to %s', $place_name)),
		'itemListElement' => $items,
	);
}

/**
 * Sources list: array of array(label, url). Only http(s) links are printed.
 */
function wholesale_guide_render_sources($sources, $heading = 'Official sources', $note = true)
{
	$sources = array_filter((array) $sources, static function ($source) {
		return !empty($source[1]) && preg_match('#^https?://#', $source[1]);
	});
	if (!$sources) {
		return;
	}
	?>
	<div class="gd-sources">
		<h3><?php echo esc_html($heading); ?></h3>
		<ul>
			<?php foreach ($sources as $source) : ?>
				<li><a href="<?php echo esc_url($source[1]); ?>" target="_blank" rel="noopener"><?php echo esc_html($source[0]); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php if ($note) : ?>
			<p class="gd-sources-note">Rules change. Always confirm with the office that issues your permit before you order.</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The UL file number from Settings > Storefront Sign, or ''. Pages show it
 * only once it has been entered.
 */
function wholesale_guide_ul_file_number()
{
	return trim((string) wholesale_get_setting('ul_file_number'));
}

/**
 * Shared "talk to us" block used at the bottom of guide pages.
 */
function wholesale_guide_render_help($title = 'Talk to a Real Sign Specialist', $text = '')
{
	$contact_page = get_page_by_path('contact');
	$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
	$text = $text ? $text : 'Send a photo of your storefront, your logo and your landlord&rsquo;s sign criteria, and we&rsquo;ll help you choose the right sign and size before you order.';
	?>
	<section class="cl-help" aria-labelledby="gd-help-title">
		<div class="container cl-help-inner">
			<div class="cl-help-copy">
				<h2 id="gd-help-title"><?php echo esc_html($title); ?></h2>
				<p><?php echo wp_kses_post($text); ?></p>
				<ul class="cl-help-details">
					<li><?php echo wholesale_home_icon('clock'); ?> Mon&ndash;Fri, 8:00am&ndash;5:00pm PST</li>
					<li><?php echo wholesale_home_icon('pin'); ?> Channel letters made in Washington &amp; California &middot; shipped to all 50 states</li>
				</ul>
			</div>
			<div class="cl-help-actions">
				<a class="cl-help-action cl-help-action--primary" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?><span><small>Call toll free</small>866-436-2101</span></a>
				<a class="cl-help-action" href="sms:+12066186543"><?php echo wholesale_home_icon('message'); ?><span><small>Text your logo</small>206-618-6543</span></a>
				<a class="cl-help-action" href="mailto:TR@StorefrontSignOnline.com"><?php echo wholesale_home_icon('mail'); ?><span><small>Email</small>TR@StorefrontSignOnline.com</span></a>
				<a class="cl-help-quote" href="<?php echo esc_url($contact_url); ?>">Request a free quote <?php echo wholesale_home_icon('arrow'); ?></a>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Product cards for a list of product slugs: photo, name, starting price.
 * Slugs that don't exist are skipped.
 */
function wholesale_guide_render_products($slugs)
{
	$cards = array();
	foreach ((array) $slugs as $slug => $note) {
		$product = wholesale_seo_product($slug);
		if ($product) {
			$cards[] = array($product, $slug, $note);
		}
	}
	if (!$cards) {
		return;
	}
	?>
	<div class="sf-type-grid">
		<?php foreach ($cards as $card) : ?>
			<?php list($product, $slug, $note) = $card; ?>
			<?php $price = wholesale_price_no_cents(wholesale_seo_product_starting_text($slug)); ?>
			<article class="sf-type-card">
				<a class="sf-type-image" href="<?php echo esc_url(get_permalink($product)); ?>" tabindex="-1" aria-hidden="true">
					<?php
					if (has_post_thumbnail($product)) {
						echo get_the_post_thumbnail($product, 'medium_large', array(
							'loading' => 'lazy',
							'decoding' => 'async',
							'alt' => wholesale_schema_text(get_the_title($product)),
							'sizes' => '(max-width: 575px) 112px, (max-width: 991px) 50vw, 25vw',
						));
					}
					?>
				</a>
				<div class="sf-type-body">
					<h3><a href="<?php echo esc_url(get_permalink($product)); ?>"><?php echo esc_html(wholesale_schema_text(get_the_title($product))); ?></a></h3>
					<?php if ($note) : ?>
						<p><?php echo wp_kses_post($note); ?></p>
					<?php endif; ?>
					<?php if ($price) : ?>
						<p class="sf-type-price"><small>Starting at</small> <?php echo wp_kses_post($price); ?></p>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Footer links to approved guide pages only.
 *
 * @return array URL => label.
 */
function wholesale_guide_footer_links()
{
	$links = array();
	foreach (array(
		'locations' => 'Shipping by State',
		'industries' => 'Signs by Industry',
		'sign-permits' => 'Sign Permit Help',
		'how-to-install-channel-letters' => 'Installation Guide',
		'warranty' => 'Warranty',
		'shipping-returns' => 'Shipping & Returns',
	) as $path => $label) {
		$url = wholesale_guide_url($path, true);
		if ($url) {
			$links[$url] = $label;
		}
	}

	return $links;
}
