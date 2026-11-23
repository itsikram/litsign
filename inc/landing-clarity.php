<?php
/**
 * Landing-page clarity layer (2026-11-06).
 *
 * Every landing page (guides, state and city pages, industry pages, category
 * pages, the storefront/banner/window hubs, the cost guide and the packages
 * page) gets the same three things:
 *
 *  1. "The short version": a plain-language box near the top that answers
 *     what it costs, how long it takes, how it ships, who installs it and what
 *     to do next. Every number comes from the functions the cart, product
 *     cards and guides already use, so nothing here invents a price or rule.
 *  2. An early, compact quote strip on support pages whose only call to
 *     action used to sit at the very bottom.
 *  3. The phone quick-action bar (.cl-mobile-bar, styled in css/style.css)
 *     on landing pages that didn't have one.
 *
 * Also: related-guide links for category pages, so each page links to the
 * guide that explains it.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/* ---------------------------------------------------------------------------
 * Shared facts
 * ------------------------------------------------------------------------- */

/**
 * Facts every box can use. Cached per request.
 *
 * @return array
 */
function wholesale_lc_facts()
{
	static $facts = null;
	if (null !== $facts) {
		return $facts;
	}

	$facts = array(
		'phone' => '866-436-2101',
		'phone_tel' => 'tel:+18664362101',
		'sms' => '206-618-6543',
		'sms_tel' => 'sms:+12066186543',
		'hours' => 'Mon–Fri, 8am–5pm Pacific',
		'response' => function_exists('wholesale_quote_response_text') ? wholesale_quote_response_text() : 'usually within one business day',
		'free_min' => function_exists('wholesale_free_shipping_amount') ? wholesale_free_shipping_amount() : '',
		'free_channel_min' => function_exists('wholesale_free_shipping_amount') ? wholesale_free_shipping_amount(true) : '',
		'ship_from' => function_exists('wholesale_shipping_from_amount') ? wholesale_shipping_from_amount() : '',
		'ship_from_channel' => function_exists('wholesale_shipping_from_amount') ? wholesale_shipping_from_amount(true) : '',
		'cl_from' => '',
		'cl_height' => 0,
		'cl_example' => function_exists('wholesale_cl_worked_example_text') ? wholesale_cl_worked_example_text() : '',
		'contact_url' => home_url('/contact/'),
		'builder_url' => home_url('/channel-letter-builder/'),
		'cl_url' => home_url('/custom-channel-letters/'),
		'cost_url' => home_url('/channel-letter-cost/'),
	);

	$contact = get_page_by_path('contact');
	if ($contact) {
		$facts['contact_url'] = get_permalink($contact);
	}

	$front_lit = function_exists('wholesale_seo_product') ? wholesale_seo_product('standard-channel-letter-front-lit') : null;
	$from = $front_lit && function_exists('wholesale_cl_from_inch_rate') ? wholesale_cl_from_inch_rate($front_lit->ID) : null;
	if ($from && !empty($from['price'])) {
		$facts['cl_from'] = '$' . number_format($from['price'], 2);
		$facts['cl_height'] = (int) $from['height'];
	}

	return $facts;
}

/**
 * "from $2 per sq ft" / "from $131" for a product, using the same starting
 * price its card shows (whole dollars, sale price when a sale is on). '' when
 * the product has no starting price.
 *
 * @param string $slug Product slug.
 * @return string Plain text.
 */
function wholesale_lc_from_text($slug)
{
	if (!function_exists('wholesale_seo_product_starting_text')) {
		return '';
	}
	$html = wholesale_seo_product_starting_text($slug);
	if ('' === $html) {
		return '';
	}
	// Drop the struck-through everyday price; keep the price the customer pays now.
	$html = preg_replace('/<del\b.*?<\/del>/is', '', $html);
	$text = trim(wp_strip_all_tags(html_entity_decode($html, ENT_QUOTES, 'UTF-8')));
	if (!preg_match('/\$\s?([\d,]+(?:\.\d+)?)/', $text, $m)) {
		return '';
	}
	$amount = (float) str_replace(',', '', $m[1]);
	$lower = strtolower($text);
	if (false !== strpos($lower, 'letter') || false !== strpos($lower, 'inch')) {
		$product = wholesale_seo_product($slug);
		$from = $product && function_exists('wholesale_cl_from_inch_rate') ? wholesale_cl_from_inch_rate($product->ID) : null;
		return $from && !empty($from['price']) ? 'from $' . number_format($from['price'], 2) . ' per inch' : '';
	}
	if (false !== strpos($lower, 'ft')) {
		// Square-foot prices keep their cents: "$2" would misstate a $2.18 rate.
		return 'from $' . number_format($amount, 2) . ' per sq ft';
	}
	return 'from ' . wholesale_whole_dollars($amount);
}

/**
 * The quote-form URL for an interest, falling back to /contact/.
 */
function wholesale_lc_quote_url($interest = '', $item = '')
{
	if (function_exists('wholesale_quote_url')) {
		return wholesale_quote_url($interest, $item, false);
	}
	return wholesale_lc_facts()['contact_url'];
}

/* ---------------------------------------------------------------------------
 * Renderers
 * ------------------------------------------------------------------------- */

/**
 * "The short version" box.
 *
 * @param string $title   Box heading (plain text).
 * @param array  $rows    array(label => sentence) or list of array(label, sentence). Sentences may hold links.
 * @param array  $actions Up to three array('label' => …, 'url' => …, 'style' => 'primary'|'outline'|'call').
 * @param string $note    Optional closing line (may hold links).
 * @param string $kicker  Small label above the heading.
 */
function wholesale_render_short_version($title, $rows, $actions = array(), $note = '', $kicker = 'The short version')
{
	$rows = array_filter((array) $rows, static function ($row) {
		return is_array($row) ? !empty($row[1]) : '' !== trim((string) $row);
	});
	if (!$rows) {
		return;
	}
	static $count = 0;
	$count++;
	$id = 'lc-title-' . $count;
	?>
	<section class="lc-wrap" aria-labelledby="<?php echo esc_attr($id); ?>">
		<div class="container">
			<div class="lc-box">
				<p class="cl-kicker lc-kicker"><?php echo esc_html($kicker); ?></p>
				<h2 id="<?php echo esc_attr($id); ?>" class="lc-title"><?php echo esc_html($title); ?></h2>
				<dl class="lc-rows">
					<?php foreach ($rows as $label => $row) : ?>
						<?php
						if (is_array($row)) {
							list($label, $row) = $row;
						}
						?>
						<div class="lc-row">
							<dt><?php echo esc_html($label); ?></dt>
							<dd><?php echo wp_kses_post($row); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
				<?php if ($actions) : ?>
					<div class="lc-actions">
						<?php foreach ((array) $actions as $action) : ?>
							<?php
							if (empty($action['url']) || empty($action['label'])) {
								continue;
							}
							$style = isset($action['style']) ? $action['style'] : 'outline';
							?>
							<a class="lc-btn lc-btn--<?php echo esc_attr($style); ?>" href="<?php echo esc_url($action['url']); ?>"><?php if ('call' === $style) : ?><?php echo wholesale_home_icon('phone'); ?><?php endif; ?><?php echo esc_html($action['label']); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ($note) : ?>
					<p class="lc-note"><?php echo wp_kses_post($note); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * A compact "get a price" strip for the middle of a support page.
 *
 * @param string $title     Heading.
 * @param string $text      One or two sentences (may hold links).
 * @param array  $primary   array('label', 'url').
 * @param array  $secondary array('label', 'url') or empty.
 */
function wholesale_render_quote_strip($title = 'Ready to price your sign?', $text = '', $primary = array(), $secondary = array())
{
	$facts = wholesale_lc_facts();
	if (!$primary) {
		$primary = array('label' => 'Get a free quote', 'url' => $facts['contact_url']);
	}
	if (!$text) {
		$text = 'Send your logo and a photo of your storefront. A sign specialist replies ' . $facts['response'] . ' with a price, or call us.';
	}
	static $count = 0;
	$count++;
	$id = 'lc-strip-' . $count;
	?>
	<section class="lc-strip" aria-labelledby="<?php echo esc_attr($id); ?>">
		<div class="container lc-strip-inner">
			<div class="lc-strip-copy">
				<h2 id="<?php echo esc_attr($id); ?>" class="lc-strip-title"><?php echo esc_html($title); ?></h2>
				<p><?php echo wp_kses_post($text); ?></p>
			</div>
			<div class="lc-strip-actions">
				<a class="lc-btn lc-btn--primary" href="<?php echo esc_url($primary['url']); ?>"><?php echo esc_html($primary['label']); ?></a>
				<?php if ($secondary && !empty($secondary['url'])) : ?>
					<a class="lc-btn lc-btn--outline" href="<?php echo esc_url($secondary['url']); ?>"><?php echo esc_html($secondary['label']); ?></a>
				<?php endif; ?>
				<a class="lc-btn lc-btn--call" href="<?php echo esc_url($facts['phone_tel']); ?>"><?php echo wholesale_home_icon('phone'); ?><?php echo esc_html($facts['phone']); ?></a>
			</div>
		</div>
	</section>
	<?php
}

/**
 * "Related guides" link grid.
 *
 * @param string $title Heading.
 * @param array  $links List of array(url, label, small-print).
 */
function wholesale_render_related_links($title, $links)
{
	$links = array_values(array_filter((array) $links, static function ($link) {
		return !empty($link[0]) && !empty($link[1]);
	}));
	if (!$links) {
		return;
	}
	static $count = 0;
	$count++;
	$id = 'lc-related-' . $count;
	?>
	<section class="lc-related" aria-labelledby="<?php echo esc_attr($id); ?>">
		<div class="container">
			<p class="cl-kicker">Keep reading</p>
			<h2 id="<?php echo esc_attr($id); ?>" class="cl-section-title"><?php echo esc_html($title); ?></h2>
			<ul class="lc-links">
				<?php foreach ($links as $link) : ?>
					<li><a href="<?php echo esc_url($link[0]); ?>"><?php echo esc_html($link[1]); ?><?php if (!empty($link[2])) : ?><small><?php echo esc_html($link[2]); ?></small><?php endif; ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * The phone quick-action bar used on the home and channel letter pages
 * (.cl-mobile-bar, css/style.css). Print it just before </main>.
 *
 * @param string $quote_url  Where "Free Quote" goes.
 * @param string $shop_url   Where the filled button goes.
 * @param string $shop_label Its label.
 */
function wholesale_landing_mobile_bar($quote_url = '', $shop_url = '', $shop_label = 'See Prices')
{
	$facts = wholesale_lc_facts();
	$quote_url = $quote_url ? $quote_url : $facts['contact_url'];
	$shop_url = $shop_url ? $shop_url : $facts['cl_url'];
	?>
	<nav class="cl-mobile-bar" aria-label="Quick actions">
		<button type="button" class="cl-mobile-bar-products" aria-controls="megaMenu"><?php echo wholesale_home_icon('grid'); ?> All Products</button>
		<a class="cl-mobile-bar-quote" href="<?php echo esc_url($quote_url); ?>">Free Quote</a>
		<a class="cl-mobile-bar-shop" href="<?php echo esc_url($shop_url); ?>"><?php echo esc_html($shop_label); ?></a>
	</nav>
	<?php
}

/* ---------------------------------------------------------------------------
 * Ready-made boxes for page types
 * ------------------------------------------------------------------------- */

/**
 * Sentences shared by the channel-letter boxes.
 */
function wholesale_lc_cl_sentences()
{
	$f = wholesale_lc_facts();
	$price = '';
	if ($f['cl_from']) {
		$price = sprintf('Front lit channel letters start at <strong>%s per inch</strong> of letter height at %d inches tall. Rates vary by height and style; the builder shows the full sign price.', $f['cl_from'], $f['cl_height']);
	}
	$example = $f['cl_example'] ? ' ' . $f['cl_example'] . '.' : '';
	$shipping = '';
	if ($f['free_channel_min'] && $f['ship_from_channel']) {
		$shipping = sprintf('Channel letter orders of %s or more ship free. Below that, shipping starts at %s.', $f['free_channel_min'], $f['ship_from_channel']);
	} elseif ($f['ship_from_channel']) {
		$shipping = sprintf('Shipping starts at %s and is chosen at checkout.', $f['ship_from_channel']);
	}
	return array(
		'price' => $price,
		'example' => $example,
		'shipping' => $shipping,
		'install' => 'We build, test and ship the sign. A licensed sign installer or electrician near you mounts it and connects the power. Every sign comes with a wiring diagram and an install pattern.',
		'permit' => 'Usually yes. Most cities need a sign permit for a lit sign, plus an electrical permit for the hookup. Check with your landlord and city before you order.',
		'warranty' => 'Five years on all seven channel letter styles. Every sign is UL listed and tested before it ships.',
		'time' => 'Every sign is made to order. Checkout shows the ship date for each shipping speed. We answer quote requests ' . $f['response'] . '.',
	);
}

/**
 * The box for a state or city page.
 *
 * @param array  $state      State data (data/locations/{state}.php).
 * @param array  $place      State or city data.
 * @param string $place_name "Houston, TX" or "Texas".
 * @param string $transit    The page's shipping sentence.
 */
function wholesale_lc_location_box($state, $place, $place_name, $transit)
{
	$f = wholesale_lc_facts();
	$s = wholesale_lc_cl_sentences();
	$facts = isset($place['facts']) ? (array) $place['facts'] : array();

	$permit = $s['permit'];
	foreach (array('Sign permit', 'Permit office', 'Permits') as $key) {
		if (!empty($facts[$key])) {
			$permit = sprintf('Usually yes. Sign permits in %s come from: %s. Lit signs also need an electrical permit.', esc_html($place_name), wp_strip_all_tags($facts[$key]));
			break;
		}
	}

	$install = sprintf('A licensed sign installer or electrician in %s. We build, test and ship; we never install.', esc_html($place_name));
	foreach (array('Licensing agency', 'Sign license', 'Electrician licensing', 'Business license', 'License') as $key) {
		if (!empty($facts[$key])) {
			$install .= ' Licensing: ' . wp_strip_all_tags($facts[$key]) . '.';
			break;
		}
	}

	$shipping = trim(wp_strip_all_tags($transit));
	if ($s['shipping']) {
		$shipping .= ' ' . $s['shipping'];
	}

	wholesale_render_short_version(
		sprintf('Ordering a sign in %s: the short version', $place_name),
		array(
			array('Do I need a permit?', $permit),
			array('Who installs it?', $install),
			array('What does it cost?', $s['price'] . $s['example']),
			array('How does it get to me?', $shipping),
			array('Warranty', $s['warranty']),
		),
		array(
			array('label' => 'Price your channel letters', 'url' => $f['builder_url'], 'style' => 'primary'),
			array('label' => 'Send your logo for a quote', 'url' => wholesale_lc_quote_url('channel-letters'), 'style' => 'outline'),
			array('label' => $f['phone'], 'url' => $f['phone_tel'], 'style' => 'call'),
		),
		sprintf('The rest of this page covers the local rules in detail. <a href="%s">See channel letter prices</a> or <a href="%s">compare the seven lit styles</a>.', esc_url($f['cost_url']), esc_url($f['cl_url']))
	);
}

/**
 * The box for an industry page.
 *
 * @param array $industry Industry data (data/industries.php).
 */
function wholesale_lc_industry_box($industry)
{
	$f = wholesale_lc_facts();
	$s = wholesale_lc_cl_sentences();
	$is_events = 'event-trade-show-signs' === $industry['slug'];

	$first = '';
	$products = isset($industry['products']) ? (array) $industry['products'] : array();
	foreach ($products as $slug => $note) {
		$product = wholesale_seo_product($slug);
		if ($product) {
			$from = wholesale_lc_from_text($slug);
			$first = sprintf('<a href="%s">%s</a>%s. %s', esc_url(get_permalink($product)), esc_html(html_entity_decode(get_the_title($product), ENT_QUOTES, 'UTF-8')), $from ? ', ' . esc_html($from) : '', wp_kses_post($note));
			break;
		}
	}

	if ($is_events) {
		$rows = array(
			array('Start with', $first),
			array('How long?', 'Most banners, stands and table covers print in 1–2 business days after you approve the artwork. Each product shows its own cut-off time. Shipping speed is chosen at checkout.'),
			array('Shipping', $f['free_min'] ? sprintf('Orders of %s or more ship free (standard). Faster 3-day, 2-day and overnight options are shown at checkout.', $f['free_min']) : 'Standard, 3-day, 2-day and overnight shipping are shown at checkout.'),
			array('Artwork', sprintf('Upload a JPG or PDF at the size you order, or <a href="%s">send your logo</a> and we lay it out. Free <a href="%s">design templates</a> are available for flags, stands and tents.', esc_url(wholesale_lc_quote_url('displays')), esc_url(home_url('/design-templates/')))),
			array('Setup', 'Portable displays need no installer. They set up in minutes and pack into a carry bag.'),
		);
		$actions = array(
			array('label' => 'Shop banners & displays', 'url' => home_url('/banners-displays/'), 'style' => 'primary'),
			array('label' => 'Get a free quote', 'url' => wholesale_lc_quote_url('displays'), 'style' => 'outline'),
			array('label' => $f['phone'], 'url' => $f['phone_tel'], 'style' => 'call'),
		);
	} else {
		$rows = array(
			array('Start with', $first),
			array('Letter size', 'A common rule of thumb is 1 inch of letter height for every 10 feet of viewing distance. Letters read from across a parking lot usually need to be 12 inches or taller.'),
			array('What does it cost?', $s['price'] . $s['example']),
			array('Who installs it?', $s['install']),
			array('How long?', $s['time']),
		);
		$actions = array(
			array('label' => 'See channel letter prices', 'url' => $f['cl_url'], 'style' => 'primary'),
			array('label' => 'Send your logo for a quote', 'url' => wholesale_lc_quote_url('channel-letters'), 'style' => 'outline'),
			array('label' => $f['phone'], 'url' => $f['phone_tel'], 'style' => 'call'),
		);
	}

	wholesale_render_short_version(
		sprintf('Signs for %s: the short version', wholesale_schema_text($industry['name'])),
		$rows,
		$actions
	);
}

/**
 * Per-category facts for the category short-version box: a representative
 * product (its price is the "from" price), the production line, the quote
 * interest and the guides that explain the category.
 */
function wholesale_lc_category_map()
{
	$print = 'Most orders print in 1–2 business days after you approve the artwork. Many banners and vinyl orders placed by 4pm Pacific ship the next business day. Each product page shows its own cut-off.';
	$hardware = 'In-stock hardware ordered by 12pm Pacific ships the same business day.';
	$made = 'Made to order in 1–3 business days after artwork approval. Each product page shows its own production time.';
	$bd = home_url('/banners-displays/');
	$wg = home_url('/window-graphics/');
	$sf = home_url('/storefront-signs/');
	$pk = home_url('/sign-packages/');
	$dt = home_url('/design-templates/');
	$ev = home_url('/industries/event-trade-show-signs/');
	$re = home_url('/industries/retail-store-signs/');
	$rs = home_url('/industries/restaurant-signs/');

	return array(
		'banners' => array('product' => '13oz-vinyl-banner', 'noun' => 'banner', 'time' => $print, 'interest' => 'banners', 'links' => array(
			array($bd . '#banners', 'Banners & displays guide', 'Materials, finishing and how to hang a banner'),
			array($pk . '#grand-opening', 'Grand opening package', 'Banner, flag and store-hours decal in one quote'),
			array($dt, 'Artwork guide', 'File setup and free templates'),
		)),
		'advertising-flags' => array('product' => 'feather-angled-flag-pole', 'noun' => 'flag', 'time' => $made, 'interest' => 'flags', 'links' => array(
			array($bd . '#flags', 'Banners & displays guide', 'Where flags work best'),
			array($dt, 'Flag design templates', 'Free print-ready files'),
			array($pk . '#grand-opening', 'Grand opening package', 'Banner, flag and decal together'),
		)),
		'flag-hardware' => array('product' => '', 'noun' => 'hardware', 'time' => $hardware, 'interest' => 'flags', 'links' => array(
			array(wholesale_category_url('advertising-flags'), 'Printed flags', 'Order the flag graphic'),
			array($bd . '#flags', 'Banners & displays guide', 'Which flag and base to choose'),
		)),
		'banner-stands' => array('product' => 'standard-retractable-insert-stand', 'noun' => 'display', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($bd . '#stands', 'Banner stands side by side', 'Retractable, X-stand and fabric compared'),
			array($pk . '#trade-show-starter', 'Trade show starter package', 'Backdrop, table cover and stand in one quote'),
			array($ev, 'Signs for events & trade shows', 'What a booth needs'),
		)),
		'hardware-only' => array('product' => '', 'noun' => 'hardware', 'time' => $hardware, 'interest' => 'displays', 'links' => array(
			array(wholesale_category_url('banner-stands'), 'Printed banner stands', 'Graphic with the stand'),
			array($bd . '#stands', 'Banner stands side by side', 'Which stand fits'),
		)),
		'banner-stand-hardware' => array('product' => '', 'noun' => 'hardware', 'time' => $hardware, 'interest' => 'displays', 'links' => array(
			array(wholesale_category_url('banner-stands'), 'Printed banner stands', 'Graphic with the stand'),
			array($bd . '#stands', 'Banner stands side by side', 'Which stand fits'),
		)),
		'a-frame-and-sign-holders' => array('product' => 'banner-a-banner-frame', 'noun' => 'display', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($sf, 'Storefront signs guide', 'Sidewalk signs with your building sign'),
			array($rs, 'Restaurant signs', 'Menus and specials at the door'),
			array(wholesale_category_url('signicade-a-frames'), 'Signicade A-frames', 'Plastic sidewalk signs'),
		)),
		'signicade-a-frames' => array('product' => 'deluxe-signicade-graphic-frame', 'noun' => 'display', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($sf, 'Storefront signs guide', 'Sidewalk signs with your building sign'),
			array($rs, 'Restaurant signs', 'Daily specials at the door'),
			array(wholesale_category_url('dry-erase-products'), 'Dry erase signs', 'Write today\'s message by hand'),
		)),
		'real-estate-products' => array('product' => 'yard-sign-and-h-stake', 'noun' => 'display', 'time' => $made, 'interest' => 'not-sure', 'links' => array(
			array(wholesale_category_url('reflective-products'), 'Reflective signs', 'Readable at night without power'),
			array(wholesale_category_url('rigid-signs-and-magnets'), 'Rigid signs & magnets', 'Aluminum, PVC and Coroplast'),
		)),
		'rigid-signs-and-magnets' => array('product' => 'coroplast', 'noun' => 'print', 'time' => $made, 'interest' => 'not-sure', 'links' => array(
			array($sf, 'Storefront signs guide', 'Door, hours and parking signs'),
			array(wholesale_category_url('reflective-products'), 'Reflective signs', 'For night-time visibility'),
			array($re, 'Retail store signs', 'Inside and out'),
		)),
		'reflective-products' => array('product' => 'reflective-aluminum-sign', 'noun' => 'print', 'time' => $made, 'interest' => 'not-sure', 'links' => array(
			array(wholesale_category_url('rigid-signs-and-magnets'), 'Rigid signs & magnets', 'Non-reflective boards'),
			array($sf, 'Storefront signs guide', 'Lit signs for the building'),
		)),
		'dry-erase-products' => array('product' => 'dry-erase-signicade-a-frame', 'noun' => 'print', 'time' => $made, 'interest' => 'not-sure', 'links' => array(
			array($rs, 'Restaurant signs', 'Specials and menus'),
			array(wholesale_category_url('signicade-a-frames'), 'Signicade A-frames', 'Printed sidewalk signs'),
		)),
		'seg-products' => array('product' => '10ft-seg-fabric-display-graphic-frame', 'noun' => 'display', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($bd . '#seg', 'Banners & displays guide', 'SEG and backlit displays explained'),
			array($ev, 'Signs for events & trade shows', 'Planning a booth'),
			array($dt, 'SEG design templates', 'Free print-ready files'),
		)),
		'trade-show-products' => array('product' => 'straight-tension-fabric-displays-graphic-frame', 'noun' => 'display', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($pk . '#trade-show-starter', 'Trade show starter package', 'Backdrop, table cover and stand in one quote'),
			array($ev, 'Signs for events & trade shows', 'Booth sizing tips'),
			array($bd . '#trade-show', 'Banners & displays guide', 'Tension fabric vs pop up'),
		)),
		'custom-event-tents' => array('product' => 'event-tent-full-canopy-graphic-frame', 'noun' => 'tent', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($bd . '#tents', 'Banners & displays guide', 'Tents, walls and flags'),
			array($dt, 'Tent design templates', 'Free print-ready files'),
			array($ev, 'Signs for events & trade shows', 'Outdoor event setups'),
		)),
		'event-tent-hardware-only' => array('product' => '', 'noun' => 'hardware', 'time' => $hardware, 'interest' => 'displays', 'links' => array(
			array(wholesale_category_url('custom-event-tents'), 'Printed event tents', 'Canopies and walls'),
		)),
		'table-throws' => array('product' => '6ft-table-cover', 'noun' => 'table cover', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($pk . '#trade-show-starter', 'Trade show starter package', 'Table cover, backdrop and stand'),
			array($bd . '#table-covers', 'Banners & displays guide', 'Fitted, stretch or throw'),
			array($dt, 'Table cover templates', 'Free print-ready files'),
		)),
		'adhesive-products' => array('product' => 'adhesive-window-perf', 'noun' => 'graphic', 'time' => $print, 'interest' => 'window-graphics', 'links' => array(
			array($wg, 'Window graphics guide', 'Which film for which window'),
			array($sf, 'Storefront signs guide', 'Window graphics with a lit sign'),
			array($pk . '#new-storefront', 'New storefront package', 'Letters, window film and opening banner'),
		)),
		'large-format' => array('product' => 'adhesive-window-perf', 'noun' => 'print', 'time' => $print, 'interest' => 'window-graphics', 'links' => array(
			array($wg, 'Window graphics guide', 'Perf, frosted, clings and decals'),
			array(wholesale_category_url('wall-art'), 'Wall art prints', 'Canvas and acrylic'),
		)),
		'wall-art' => array('product' => 'canvas-wrap', 'noun' => 'print', 'time' => $made, 'interest' => 'not-sure', 'links' => array(
			array(wholesale_category_url('large-format'), 'Large format printing', 'Posters, styrene and film'),
			array($re, 'Retail store signs', 'Decor and displays inside the store'),
		)),
		'indoor-outdoor-displays' => array('product' => 'standard-retractable-insert-stand', 'noun' => 'display', 'time' => $made, 'interest' => 'displays', 'links' => array(
			array($bd, 'Banners & displays guide', 'Every display type compared'),
			array($ev, 'Signs for events & trade shows', 'Planning a booth'),
			array($pk, 'Sign packages', 'Opening, trade show and new storefront bundles'),
		)),
	);
}

/**
 * The box for a product category page (/{category}/).
 *
 * @param string $slug    product_category slug.
 * @param string $heading The page H1.
 */
function wholesale_lc_category_box($slug, $heading)
{
	$map = wholesale_lc_category_map();
	if (!isset($map[$slug])) {
		return;
	}
	$f = wholesale_lc_facts();
	$entry = $map[$slug];
	$noun = $entry['noun'];
	$plural = 'hardware' === $noun ? 'hardware' : $noun . 's';

	$price = '';
	if ($entry['product']) {
		$product = wholesale_seo_product($entry['product']);
		$from = $product ? wholesale_lc_from_text($entry['product']) : '';
		if ($product && $from) {
			$price = sprintf('<a href="%s">%s</a> starts %s. Enter your size and options on the product page and the price updates before you add it to the cart.', esc_url(get_permalink($product)), esc_html(html_entity_decode(get_the_title($product), ENT_QUOTES, 'UTF-8')), esc_html($from));
		}
	}
	if (!$price) {
		$price = 'Every product shows its price online. Choose your options and the price updates before you add it to the cart.';
	}

	$shipping = $f['free_min']
		? sprintf('Orders of %s or more ship free (standard, 3–6 business days). Faster 3-day, 2-day and overnight options are shown at checkout. We ship to all 50 states; pickup is not available.', $f['free_min'])
		: 'Standard, 3-day, 2-day and overnight shipping are shown at checkout. We ship to all 50 states.';

	$artwork = 'hardware' === $noun
		? 'No artwork needed. Hardware ships on its own; printed graphics are ordered separately.'
		: sprintf('Upload a JPG or PDF at the size you order, or <a href="%s">send your logo</a> and we lay it out for you. Free <a href="%s">design templates</a> show the safe area.', esc_url(wholesale_lc_quote_url($entry['interest'])), esc_url(home_url('/design-templates/')));

	wholesale_render_short_version(
		sprintf('%s: the short version', wholesale_schema_text($heading)),
		array(
			array('What it costs', $price),
			array('How long it takes', $entry['time']),
			array('Shipping', $shipping),
			array('Artwork', $artwork),
			array('Need help?', sprintf('Call <a href="%s">%s</a> or <a href="%s">ask a sign specialist online</a>. We answer %s.', esc_url($f['phone_tel']), esc_html($f['phone']), esc_url(wholesale_lc_quote_url($entry['interest'])), esc_html($f['response']))),
		),
		array(
			array('label' => 'hardware' === $noun ? 'Shop hardware' : 'Shop ' . $plural, 'url' => '#product-box-container', 'style' => 'primary'),
			array('label' => 'Get a free quote', 'url' => wholesale_lc_quote_url($entry['interest']), 'style' => 'outline'),
			array('label' => $f['phone'], 'url' => $f['phone_tel'], 'style' => 'call'),
		)
	);
}

/**
 * "Related guides" for a category page, from the map above.
 */
function wholesale_lc_category_related($slug)
{
	$map = wholesale_lc_category_map();
	if (empty($map[$slug]['links'])) {
		return;
	}
	wholesale_render_related_links('Guides That Explain This Category', $map[$slug]['links']);
}

/* ---------------------------------------------------------------------------
 * Where the layer applies
 * ------------------------------------------------------------------------- */

/**
 * Landing pages that get the stylesheet and the phone quick-action bar.
 * The home page, /custom-channel-letters/, /banners-displays/ and
 * /window-graphics/ already print their own bar.
 */
function wholesale_lc_is_landing()
{
	if (function_exists('wholesale_guide_current') && wholesale_guide_current()) {
		return true;
	}
	if (get_query_var('category_slug') && empty($GLOBALS['wholesale_channel_letter_landing'])) {
		return true;
	}
	return is_page(array('storefront-signs', 'channel-letter-cost', 'sign-packages', 'design-templates'));
}

/**
 * Pages whose bar is printed by this layer (not by the template itself).
 */
function wholesale_lc_prints_bar()
{
	if (!wholesale_lc_is_landing()) {
		return false;
	}
	// home.php prints its own bar only on the channel letter routes; plain
	// category pages get theirs here.
	return true;
}

/**
 * Print the bar for the pages this layer covers. Printed in the footer so no
 * template needs editing; the bar is position: fixed, so its place in the
 * HTML doesn't matter, and the live chat loader looks for it after the DOM
 * has loaded.
 */
function wholesale_lc_footer_bar()
{
	if (!wholesale_lc_prints_bar()) {
		return;
	}
	$f = wholesale_lc_facts();
	$quote = $f['contact_url'];
	$shop = $f['cl_url'];
	$label = 'See Prices';

	$entry = function_exists('wholesale_guide_current') ? wholesale_guide_current() : array();
	$type = isset($entry['type']) ? $entry['type'] : '';
	if ('state' === $type || 'city' === $type) {
		$quote = wholesale_lc_quote_url('channel-letters');
		$shop = $f['builder_url'];
		$label = 'Start Building';
	} elseif ('industry' === $type && 'event-trade-show-signs' === $entry['industry']) {
		$quote = wholesale_lc_quote_url('displays');
		$shop = home_url('/banners-displays/');
	} elseif ('industry' === $type) {
		$quote = wholesale_lc_quote_url('channel-letters');
	} elseif (is_page('sign-packages')) {
		$quote = wholesale_lc_quote_url('mixed');
		$shop = home_url('/banners-displays/');
	} elseif (is_page(array('design-templates', 'shipping-returns'))) {
		$shop = home_url('/banners-displays/');
	} elseif (is_page('channel-letter-cost')) {
		$shop = $f['builder_url'];
		$label = 'Price Your Sign';
	}

	wholesale_landing_mobile_bar($quote, $shop, $label);
}
add_action('wp_footer', 'wholesale_lc_footer_bar', 5);

function wholesale_lc_body_class($classes)
{
	if (wholesale_lc_prints_bar() && !in_array('has-cl-mobile-bar', $classes, true)) {
		$classes[] = 'has-cl-mobile-bar';
	}
	return $classes;
}
add_filter('body_class', 'wholesale_lc_body_class', 20);

function wholesale_lc_assets()
{
	$needs = wholesale_lc_is_landing() || is_page(array('banners-displays', 'window-graphics'));
	if (!$needs) {
		return;
	}
	$css = '/css/landing-clarity.css';
	wp_enqueue_style('wholesale-landing-clarity', get_template_directory_uri() . $css, array('custom-style'), (string) filemtime(get_template_directory() . $css));
}
add_action('wp_enqueue_scripts', 'wholesale_lc_assets', 30);
