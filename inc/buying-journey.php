<?php
/**
 * Shared buying-journey facts (2026-11-05):
 *  - upload limits and the response-time line, so every page states the same rule;
 *  - plain-language labels and help text for calculator options ("of side" → "Printed sides");
 *  - the product-aware quote form registry used by /contact/ (?interest=…&item=…);
 *  - quote-based project packages (/sign-packages/);
 *  - the lead pipeline on Contact Submissions (stage, owner, follow-up dates, lost reason).
 *
 * Nothing here changes prices, shipping rules or the checkout.
 *
 * @package litsign
 */

/**
 * Whether quote replies for signs include a design mockup to approve. Off until the owner
 * confirms it; the copy then says "a price" instead of "a price and a mockup".
 */
const WHOLESALE_QUOTE_MOCKUP = false;

/** $with when mockups are confirmed (WHOLESALE_QUOTE_MOCKUP), else $without. */
function wholesale_quote_mockup($with, $without)
{
	return WHOLESALE_QUOTE_MOCKUP ? $with : $without;
}

/** Largest product-page artwork file (utils/Cart.php and inc/async-uploads.php enforce it). */
const WHOLESALE_ARTWORK_MAX_MB = 25;

function wholesale_artwork_max_bytes()
{
	return WHOLESALE_ARTWORK_MAX_MB * MB_IN_BYTES;
}

function wholesale_artwork_max_text()
{
	return WHOLESALE_ARTWORK_MAX_MB . ' MB';
}

/** What to do with a file over the limit (email attachments are capped too, so ask for a link). */
function wholesale_large_file_help()
{
	return 'Larger file? Email a download link (Google Drive, Dropbox or WeTransfer) to TR@StorefrontSignOnline.com with your order number or name.';
}

/**
 * The response time the site states after a quote request. This is the wording the
 * contact page already used; change it here only after the owner confirms a new target.
 */
function wholesale_quote_response_text()
{
	return 'usually within one business day (Mon–Fri, 8am–5pm Pacific)';
}

/**
 * Supplier copy says "Max File Upload Size: 300MB"; the site accepts WHOLESALE_ARTWORK_MAX_MB.
 * Rewrites the statement at render time so post_content stays untouched.
 */
function wholesale_fix_upload_limit_text($html)
{
	$limit = wholesale_artwork_max_text();
	$html = preg_replace('/(Max(?:imum)?\s+File\s+Upload\s+Size:?\s*)300\s?MB/i', '${1}' . $limit . ' (larger files: email us a download link)', (string) $html);
	return preg_replace('/\bup to 300\s?MB\b/i', 'up to ' . $limit, $html);
}

/* ---------------------------------------------------------------------------
 * Calculator option labels and help text
 * ------------------------------------------------------------------------- */

/**
 * Customer-facing label for a product_attr name. Unknown names keep the old
 * "dashes to spaces" label, so nothing else changes.
 */
function wholesale_attr_label($attr_name)
{
	$labels = array(
		'of-side' => 'Printed sides',
		'of-sides' => 'Printed sides',
		'windslit' => 'Wind slits',
		'wind-slit' => 'Wind slits',
		'pole-pocket' => 'Pole pockets',
		'holes-punch' => 'Hole punch',
		'reinforced-strip' => 'Reinforced edge strip',
		'corners' => 'Reinforced corners',
		'webbing' => 'Webbing & D-rings',
		'velcro' => 'Velcro strips',
		'rope' => 'Sewn-in rope',
		'hem' => 'Hem',
		'grommets' => 'Grommets',
		'grommet' => 'Grommets',
		'turnaround' => 'Production time',
	);
	$key = strtolower((string) $attr_name);
	return isset($labels[$key]) ? $labels[$key] : ucfirst(str_replace('-', ' ', (string) $attr_name));
}

/** One-line explanation shown under a finishing option, or ''. */
function wholesale_attr_help($attr_name)
{
	$help = array(
		'of-side' => '2 sides prints your design on the back too, for banners seen from both directions.',
		'of-sides' => '2 sides prints your design on the back too, for banners seen from both directions.',
		'grommets' => 'Metal rings along the edges for rope, zip ties or hooks.',
		'grommet' => 'Metal rings along the edges for rope, zip ties or hooks.',
		'hem' => 'The edges are folded and welded or sewn for strength.',
		'pole-pocket' => 'A sleeve sewn along the edge for a pole or rod to slide through.',
		'windslit' => 'Half-moon cuts that let wind pass through outdoor banners on fences or buildings.',
		'wind-slit' => 'Half-moon cuts that let wind pass through outdoor banners on fences or buildings.',
		'webbing' => 'A strong strap sewn along the edge, with D-rings to tie off; for large or long-term banners.',
		'velcro' => 'Hook-and-loop strips to attach the banner to a frame or surface.',
		'rope' => 'Rope sewn inside the hem so the edge holds tension when tied.',
		'corners' => 'Extra material at each corner where the most strain is.',
		'reinforced-strip' => 'An extra strip of material along the edge behind the grommets.',
		'turnaround' => 'Time to make your order before it ships. Shipping time is chosen at checkout.',
	);
	$key = strtolower((string) $attr_name);
	return isset($help[$key]) ? $help[$key] : '';
}

/** Finishing options grouped under "Advanced finishing options" on calculator products (js/ui-polish.js). */
function wholesale_advanced_finishing_attrs()
{
	return array('webbing', 'velcro', 'rope', 'corners', 'reinforced-strip', 'windslit', 'wind-slit');
}

/* ---------------------------------------------------------------------------
 * Shipping statements derived from the Settings values the checkout uses
 * ------------------------------------------------------------------------- */

/**
 * Shipping line for a channel letter example total: "ships free (orders $1,000+)"
 * when the total reaches the channel letter free-shipping minimum (the same test as
 * wholesale_cart_qualifies_free_shipping()), otherwise "+ $50 shipping".
 */
function wholesale_cl_example_shipping_text($total)
{
	$threshold = max(0.0, (float) wholesale_get_setting('free_shipping_channel_min'));
	if ($threshold > 0 && round((float) $total, 2) >= $threshold) {
		return sprintf(' &middot; ships free (channel letter orders %s+)', wholesale_free_shipping_amount(true));
	}
	$from = wholesale_shipping_from_amount(true);
	return $from ? ' + ' . $from . ' shipping' : '';
}

/** "Channel letter orders of $1,000 or more ship free; below that, shipping starts at $50." */
function wholesale_cl_shipping_rule_text()
{
	$free = wholesale_free_shipping_amount(true);
	$from = wholesale_shipping_from_amount(true);
	if ($free && $from) {
		return sprintf('Channel letter orders of %s or more ship free (3–6 business days); below that, shipping starts at %s.', $free, $from);
	}
	return $from ? sprintf('Shipping starts at %s.', $from) : '';
}

/* ---------------------------------------------------------------------------
 * Product-aware quote form registry (/contact/)
 * ------------------------------------------------------------------------- */

/**
 * What a quote request can be about. 'fields' names the conditional question
 * groups the form shows for it (see wholesale_quote_spec_fields()).
 */
function wholesale_quote_interests()
{
	return array(
		'channel-letters' => array(
			'label' => 'Channel letters / storefront sign',
			'heading' => 'Get a Free Channel Letter Quote',
			'intro' => wholesale_quote_mockup('Send your logo or a storefront photo and rough size. A sign specialist replies with a price and a mockup to approve before anything is made.', 'Send your logo or a storefront photo and rough size. A sign specialist replies with a price and answers your questions before anything is made.'),
			'fields' => array('cl'),
			'next' => array('We check your logo, wall size and lighting style.', wholesale_quote_mockup('You get a price and a mockup by email.', 'You get a price by email.'), wholesale_quote_mockup('You approve the mockup, then we build, test and ship.', 'You approve the quote, then we build, test and ship.')),
		),
		'banners' => array(
			'label' => 'Banners',
			'heading' => 'Get a Free Banner Quote',
			'intro' => 'Most banner sizes are priced instantly on the product page. Use this form for large runs, unusual sizes or design help.',
			'fields' => array('size', 'qty', 'date'),
			'next' => array('We check sizes, material and finishing.', 'You get a price by email (artwork help included if you asked).', 'You approve and pay; we print and ship.'),
		),
		'flags' => array(
			'label' => 'Flags',
			'heading' => 'Get a Free Flag Quote',
			'intro' => 'Feather, teardrop, rectangle and custom pole flags. Tell us the style, size and how many.',
			'fields' => array('size', 'qty', 'date'),
			'next' => array('We confirm style, size and base for your surface.', 'You get a price by email.', 'You approve and pay; we print and ship.'),
		),
		'displays' => array(
			'label' => 'Trade show displays',
			'heading' => 'Get a Free Trade Show Display Quote',
			'intro' => 'Backdrops, table covers, banner stands and SEG displays. Share your booth size and event date.',
			'fields' => array('booth', 'qty', 'date'),
			'next' => array('We match products to your booth size and event date.', 'You get a price by email.', 'You approve and pay; we print and ship.'),
		),
		'window-graphics' => array(
			'label' => 'Window graphics',
			'heading' => 'Get a Free Window Graphics Quote',
			'intro' => 'Perforated window film, clings, frosted vinyl and store-hours decals. Send a photo of the glass and the window sizes.',
			'fields' => array('size', 'qty', 'date'),
			'next' => array('We check window sizes and the right film.', 'You get a price by email.', 'You approve and pay; we print and ship.'),
		),
		'mixed' => array(
			'label' => 'Several products / a full project',
			'heading' => 'Get a Free Quote for Your Project',
			'intro' => 'Opening a store, planning an event or rebranding? List what you need and we quote it together.',
			'fields' => array('products', 'date'),
			'next' => array('We review the whole list together.', 'You get one combined quote by email.', 'You approve each item before it is made.'),
		),
		'not-sure' => array(
			'label' => 'Not sure yet – help me choose',
			'heading' => 'Get a Free Sign Quote',
			'intro' => 'Tell us where the sign goes and what it needs to do. A specialist will suggest options and prices.',
			'fields' => array('date'),
			'next' => array('A specialist reads your request.', 'You get suggestions and prices by email or phone.', 'You choose; nothing is made until you approve.'),
		),
	);
}

/** Maps product categories and old project-type values to an interest key. */
function wholesale_quote_interest_from_text($text)
{
	$text = strtolower((string) $text);
	$map = array(
		'channel' => 'channel-letters', 'letter' => 'channel-letters', 'storefront' => 'channel-letters',
		'banner' => 'banners', 'flag' => 'flags',
		'display' => 'displays', 'trade' => 'displays', 'backdrop' => 'displays', 'table' => 'displays', 'stand' => 'displays',
		'window' => 'window-graphics', 'vinyl' => 'window-graphics', 'decal' => 'window-graphics',
		'mixed' => 'mixed', 'package' => 'mixed', 'project' => 'mixed',
	);
	foreach ($map as $needle => $key) {
		if (false !== strpos($text, $needle)) {
			return $key;
		}
	}
	return '';
}

/** Channel letter lighting choices shown on the quote form. */
function wholesale_quote_cl_styles()
{
	return array(
		'not-sure' => 'Not sure – help me choose',
		'front-lit' => 'Front lit (face glows)',
		'back-lit' => 'Back lit / halo (glow on the wall behind)',
		'front-back-lit' => 'Front and back lit',
		'non-lit' => 'Non-lit dimensional letters',
	);
}

/**
 * Conditional question groups. Each field: name => [label, type, extra].
 * All are optional; the form only shows the groups for the chosen interest.
 */
function wholesale_quote_spec_fields()
{
	return array(
		'cl' => array(
			'cl_style' => array('Lighting style', 'select', wholesale_quote_cl_styles()),
			'cl_width' => array('Approximate sign width', 'text', 'e.g. 10 ft, or the width of your sign band'),
			'cl_height' => array('Letter height, if known', 'text', 'e.g. 18 in'),
			'cl_wording' => array('Wording on the sign', 'text', 'e.g. ACME DENTAL'),
		),
		'size' => array(
			'size' => array('Size (width × height)', 'text', 'e.g. 3 ft × 8 ft, or 36 × 96 in'),
		),
		'booth' => array(
			'booth' => array('Booth size', 'select', array('' => 'Select', '10x10' => '10 ft × 10 ft', '10x20' => '10 ft × 20 ft', 'table' => 'Table only', 'other' => 'Other / not sure')),
		),
		'qty' => array(
			'qty' => array('Quantity', 'number', ''),
		),
		'products' => array(
			'products' => array('What do you need?', 'checks', array('channel-letters' => 'Channel letters', 'banners' => 'Banners', 'flags' => 'Flags', 'displays' => 'Trade show displays', 'window-graphics' => 'Window graphics', 'other' => 'Something else')),
		),
		'date' => array(
			'needed_by' => array('Needed by (optional)', 'date', ''),
		),
	);
}

/**
 * Quote URL with context, e.g. wholesale_quote_url('banners', '13oz-vinyl-banner').
 * $item is a product slug or a package key; both are re-validated on /contact/.
 */
function wholesale_quote_url($interest = '', $item = '', $anchor = true)
{
	$page = get_page_by_path('contact');
	$url = $page ? get_permalink($page) : home_url('/contact/');
	$args = array_filter(array(
		'interest' => sanitize_key($interest),
		'item' => sanitize_title($item),
	));
	return add_query_arg($args, $url) . ($anchor ? '#contact-form' : '');
}

/**
 * The validated context for the contact form: array('interest' => key|'' , 'item' => array|null).
 * item = array('type' => 'product'|'package', 'key', 'title', 'url').
 */
function wholesale_quote_context($interest_raw, $item_raw)
{
	$interests = wholesale_quote_interests();
	$interest = sanitize_key((string) $interest_raw);
	$interest = isset($interests[$interest]) ? $interest : '';
	$item = null;
	$slug = sanitize_title((string) $item_raw);
	if ($slug) {
		$packages = wholesale_packages();
		if (isset($packages[$slug])) {
			$item = array('type' => 'package', 'key' => $slug, 'title' => $packages[$slug]['title'] . ' package', 'url' => home_url('/sign-packages/#' . $slug));
			$interest = $interest ? $interest : 'mixed';
		} else {
			$product = get_page_by_path($slug, OBJECT, 'product');
			if ($product && 'publish' === $product->post_status) {
				$item = array('type' => 'product', 'key' => $slug, 'title' => html_entity_decode(get_the_title($product), ENT_QUOTES), 'url' => get_permalink($product));
				if (!$interest) {
					$terms = wp_get_post_terms($product->ID, 'product_category', array('fields' => 'names'));
					$interest = wholesale_quote_interest_from_text(get_the_title($product) . ' ' . (is_array($terms) ? implode(' ', $terms) : ''));
				}
			}
		}
	}
	return array('interest' => $interest, 'item' => $item);
}

/* ---------------------------------------------------------------------------
 * Quote-based packages (/sign-packages/)
 * ------------------------------------------------------------------------- */

/**
 * Project packages built from products the site sells. No bundle discount and no
 * fixed package price: each item is priced at its normal price in the quote.
 * 'products' are product slugs (checked at render; missing ones are skipped).
 */
function wholesale_packages()
{
	return array(
		'grand-opening' => array(
			'title' => 'Grand Opening',
			'lead' => 'Get noticed from the street in your first weeks.',
			'contents' => array(
				array('13oz-vinyl-banner', '“Grand Opening” or “Now Open” vinyl banner', 'Any size; hems included, grommets optional.'),
				array('feather-angled-flag-pole', 'Feather flag', 'Choose the size, sides and base on the product page.'),
				array('adhesive-products', 'Window store-hours decal', 'Printed adhesive vinyl sized to your door or window.'),
			),
			'excludes' => array('Installation', 'Permits (many cities limit temporary banners and flags)', 'Design work beyond simple text layout, unless quoted'),
			'options' => 'Banner size and finishing, flag style and base, decal size and colors.',
		),
		'trade-show-starter' => array(
			'title' => 'Trade Show Starter',
			'lead' => 'A complete look for a 10 ft booth or a table-top event.',
			'contents' => array(
				array('step-and-repeat-backdrop-graphic-frame', 'Backdrop', 'Step and repeat backdrop with frame, sized to your booth.'),
				array('6ft-table-cover', 'Printed table cover', '4, 6 or 8 ft tables.'),
				array('standard-retractable-insert-stand', 'Retractable banner stand', 'Printed graphic with the stand.'),
			),
			'excludes' => array('Booth space, furniture and lighting', 'Event-site setup', 'Rush production unless quoted'),
			'options' => 'Backdrop size and stand type, table size, stand model.',
		),
		'new-storefront' => array(
			'title' => 'New Storefront',
			'lead' => 'Your permanent sign plus the graphics to open with.',
			'contents' => array(
				array('standard-channel-letter-front-lit', 'LED channel letters', 'Any of 7 lit styles; you approve the quote first.'),
				array('adhesive-window-perf', 'Window graphics', 'Perforated film, clings or store-hours vinyl.'),
				array('13oz-vinyl-banner', 'Opening banner', 'To use while you wait for permits or install.'),
			),
			'excludes' => array('Installation (your licensed electrician or sign installer installs)', 'Sign permits and landlord approval', 'Electrical work at the wall'),
			'options' => 'Letter style, height and colors, window film type, banner size.',
		),
	);
}

/** Package item product, or null when that slug isn't a published product. */
function wholesale_package_product($slug)
{
	$product = get_page_by_path($slug, OBJECT, 'product');
	return $product && 'publish' === $product->post_status ? $product : null;
}

/* ---------------------------------------------------------------------------
 * Lead pipeline on Contact Submissions
 * ------------------------------------------------------------------------- */

function wholesale_lead_stages()
{
	return array('new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'won' => 'Won', 'lost' => 'Lost');
}

function wholesale_lead_lost_reasons()
{
	return array('' => '—', 'price' => 'Price', 'timing' => 'Timing', 'installation' => 'Installation', 'no-response' => 'No response', 'other' => 'Other');
}

function wholesale_lead_stage($post_id)
{
	$stage = (string) get_post_meta($post_id, '_lead_stage', true);
	return isset(wholesale_lead_stages()[$stage]) ? $stage : 'new';
}

/** Open (not won/lost) lead whose next follow-up date has passed, or New with no reply after 1 day. */
function wholesale_lead_is_overdue($post_id)
{
	$stage = wholesale_lead_stage($post_id);
	if (in_array($stage, array('won', 'lost'), true)) {
		return false;
	}
	$today = current_time('Y-m-d');
	$next = (string) get_post_meta($post_id, '_lead_next_followup', true);
	if ($next) {
		return $next < $today;
	}
	return 'new' === $stage && !get_post_meta($post_id, '_contact_last_reply', true)
		&& (current_time('timestamp') - get_post_time('U', false, $post_id)) > DAY_IN_SECONDS;
}

/**
 * Follow-up email drafts for staff to copy into the existing "Reply to customer" box.
 * Nothing sends these automatically.
 */
function wholesale_lead_templates()
{
	return array(
		'ack' => array('Acknowledgment (already sent automatically)', 'The theme already emails the customer a confirmation when the form is sent (Settings → Quote auto-reply). No change.'),
		'quote' => array('Quote delivery', "Hi {name},\n\nThanks for the details on your {project} project. Your quote is below / attached:\n\n[items, sizes, options and price]\n[shipping: method and cost, or free if the order qualifies]\nSales tax is added at checkout.\n\nFor channel letters: the price includes the letters and the options listed. Installation is not included; your licensed electrician or sign installer mounts it using our wiring diagram and mounting pattern.\n\nTo go ahead, reply \"approved\" or call 866-436-2101 and we will send the mockup / payment link.\n\nThanks,\n{staff}\nStorefront Sign Online"),
		'day1' => array('Follow-up – 1 business day after the quote', "Hi {name},\n\nJust checking that the quote for your {project} came through. Happy to change sizes, colors or options if anything doesn't fit.\n\n{staff}"),
		'day3' => array('Follow-up – 3 business days', "Hi {name},\n\nAny questions on the {project} quote? If you have a date you need it by, tell us and we'll confirm production and shipping times.\n\n{staff}"),
		'day7' => array('Follow-up – 7 business days (last)', "Hi {name},\n\nI'll close this quote for now so we don't keep emailing you. If you want to pick it back up, just reply to this email; the quote can be updated to current prices.\n\n{staff}"),
	);
}

/* ---------------------------------------------------------------------------
 * Assets, attribution, packages page
 * ------------------------------------------------------------------------- */

function wholesale_buying_journey_assets()
{
	if (!is_page(array('contact', 'sign-packages')) && !is_singular('product') && !(!is_front_page() && function_exists('wholesale_is_channel_letters_page') && wholesale_is_channel_letters_page())) {
		return;
	}
	$css = '/css/buying-journey.css';
	$js = '/js/buying-journey.js';
	wp_enqueue_style('wholesale-buying-journey', get_template_directory_uri() . $css, array(), (string) filemtime(get_template_directory() . $css));
	wp_enqueue_script('wholesale-buying-journey', get_template_directory_uri() . $js, array(), (string) filemtime(get_template_directory() . $js), array('in_footer' => true, 'strategy' => 'defer'));
}
add_action('wp_enqueue_scripts', 'wholesale_buying_journey_assets', 30);

/**
 * First-touch attribution for quote forms: the first page of the visit and its URL tags
 * (utm_*, gclid), kept in sessionStorage (this tab only, cleared when it closes) and copied
 * into the quote form's hidden fields. First-party only; nothing is sent anywhere else.
 */
function wholesale_buying_journey_attribution_script()
{
	if (is_admin() || is_feed()) {
		return;
	}
	?>
	<script>(function(){try{var s=window.sessionStorage;if(s.getItem('sfsAttribution'))return;var p=new URLSearchParams(location.search),o={landing:location.origin+location.pathname};['utm_source','utm_medium','utm_campaign','utm_term','utm_content','gclid'].forEach(function(k){var v=p.get(k);if(v)o[k]=v.slice(0,150);});if(document.referrer&&document.referrer.indexOf(location.origin)!==0)o.referrer=document.referrer.split('?')[0].slice(0,300);s.setItem('sfsAttribution',JSON.stringify(o));}catch(e){}})();</script>
	<?php
}
add_action('wp_footer', 'wholesale_buying_journey_attribution_script', 5);

/** Creates the /sign-packages/ page once (template: page-sign-packages.php). */
function wholesale_packages_sync_page()
{
	if (!current_user_can('manage_options') || 'v1' === get_option('wholesale_packages_page_version')) {
		return;
	}
	if (!get_page_by_path('sign-packages', OBJECT, 'page')) {
		wp_insert_post(array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Sign Packages',
			'post_name' => 'sign-packages',
			'post_content' => '',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
		));
	}
	update_option('wholesale_packages_page_version', 'v1', false);
}
add_action('admin_init', 'wholesale_packages_sync_page', 21);

/* ---------------------------------------------------------------------------
 * Lead pipeline (Contact Submissions admin)
 * ------------------------------------------------------------------------- */

add_action('add_meta_boxes_contact_submission', function () {
	add_meta_box('wholesale-lead-pipeline', 'Lead pipeline', 'wholesale_lead_pipeline_meta_box', 'contact_submission', 'side', 'high');
	add_meta_box('wholesale-lead-templates', 'Follow-up templates (copy into the reply box)', 'wholesale_lead_templates_meta_box', 'contact_submission', 'normal', 'low');
});

function wholesale_lead_pipeline_meta_box($post)
{
	wp_nonce_field('wholesale_lead_pipeline', 'wholesale_lead_pipeline_nonce');
	$stage = wholesale_lead_stage($post->ID);
	$owner = (int) get_post_meta($post->ID, '_lead_owner', true);
	$last = (string) get_post_meta($post->ID, '_lead_last_contact', true);
	$last = $last ? $last : substr((string) get_post_meta($post->ID, '_contact_last_reply', true), 0, 10);
	$attribution = (array) get_post_meta($post->ID, '_contact_attribution', true);
	$interest = (string) get_post_meta($post->ID, '_contact_interest', true);
	$interests = wholesale_quote_interests();
	$specs = (array) get_post_meta($post->ID, '_contact_specs', true);
	$field = static function ($label, $html) {
		echo '<p style="margin:0 0 10px"><label style="display:block;font-weight:600;margin-bottom:3px">' . esc_html($label) . '</label>' . $html . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $html is built from escaped parts below.
	};
	if (wholesale_lead_is_overdue($post->ID)) {
		echo '<p style="background:#fcf0f1;border-left:4px solid #d63638;padding:6px 8px;margin:0 0 10px"><strong>Follow-up overdue</strong></p>';
	}
	$options = '';
	foreach (wholesale_lead_stages() as $key => $label) {
		$options .= '<option value="' . esc_attr($key) . '"' . selected($stage, $key, false) . '>' . esc_html($label) . '</option>';
	}
	$field('Stage', '<select name="lead_stage" style="width:100%">' . $options . '</select>');
	$field('Assigned to', wp_dropdown_users(array('name' => 'lead_owner', 'selected' => $owner, 'show_option_none' => '— Unassigned —', 'option_none_value' => 0, 'capability' => array('edit_posts'), 'echo' => false)));
	$field('Last contact', '<input type="date" name="lead_last_contact" value="' . esc_attr($last) . '" style="width:100%">');
	$field('Next follow-up', '<input type="date" name="lead_next_followup" value="' . esc_attr((string) get_post_meta($post->ID, '_lead_next_followup', true)) . '" style="width:100%">');
	$field('Quote amount ($)', '<input type="number" step="0.01" min="0" name="lead_quote_amount" value="' . esc_attr((string) get_post_meta($post->ID, '_lead_quote_amount', true)) . '" style="width:100%">');
	$field('Won order # (optional)', '<input type="text" name="lead_order" value="' . esc_attr((string) get_post_meta($post->ID, '_lead_order', true)) . '" style="width:100%">');
	$reasons = '';
	foreach (wholesale_lead_lost_reasons() as $key => $label) {
		$reasons .= '<option value="' . esc_attr($key) . '"' . selected((string) get_post_meta($post->ID, '_lead_lost_reason', true), $key, false) . '>' . esc_html($label) . '</option>';
	}
	$field('Lost reason', '<select name="lead_lost_reason" style="width:100%">' . $reasons . '</select>');
	$field('Stop follow-ups', '<label><input type="checkbox" name="lead_no_followup" value="1"' . checked((bool) get_post_meta($post->ID, '_lead_no_followup', true), true, false) . '> Customer replied, ordered, declined or opted out</label>');

	echo '<hr><p style="margin:0 0 4px"><strong>Product interest:</strong> ' . esc_html(isset($interests[$interest]) ? $interests[$interest]['label'] : (string) get_post_meta($post->ID, '_contact_project_type', true)) . '</p>';
	foreach ($specs as $label => $value) {
		echo '<p style="margin:0 0 4px">' . esc_html($label . ': ' . $value) . '</p>';
	}
	$source = (string) get_post_meta($post->ID, '_contact_source', true);
	echo '<p style="margin:6px 0 4px"><strong>Source:</strong> ' . esc_html($source ? $source : 'Direct / unknown') . '</p>';
	foreach (array('landing_page' => 'Landing page', 'quote_button_page' => 'Quote button on', 'referrer' => 'Referrer') as $key => $label) {
		if (!empty($attribution[$key])) {
			echo '<p style="margin:0 0 4px;word-break:break-all">' . esc_html($label . ': ' . $attribution[$key]) . '</p>';
		}
	}
}

function wholesale_lead_templates_meta_box($post)
{
	$name = (string) get_post_meta($post->ID, '_contact_name', true);
	$project = (string) get_post_meta($post->ID, '_contact_project_type', true);
	$user = wp_get_current_user();
	echo '<p>Drafts only. Nothing is sent until you paste one into “Reply to customer” and send it. Skip follow-ups when the customer has replied, ordered, declined or opted out.</p>';
	foreach (wholesale_lead_templates() as $template) {
		$text = strtr($template[1], array('{name}' => $name ? strtok($name, ' ') : 'there', '{project}' => $project ? $project : 'sign', '{staff}' => $user->display_name));
		echo '<details style="margin-bottom:8px"><summary style="cursor:pointer;font-weight:600">' . esc_html($template[0]) . '</summary><textarea readonly rows="8" style="width:100%;margin-top:6px" onfocus="this.select()">' . esc_textarea($text) . '</textarea></details>';
	}
}

add_action('save_post_contact_submission', function ($post_id) {
	if (!isset($_POST['wholesale_lead_pipeline_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wholesale_lead_pipeline_nonce'])), 'wholesale_lead_pipeline')
		|| (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
		return;
	}
	$date = static function ($key) {
		$value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
	};
	$stage = isset($_POST['lead_stage']) ? sanitize_key(wp_unslash($_POST['lead_stage'])) : 'new';
	$stage = isset(wholesale_lead_stages()[$stage]) ? $stage : 'new';
	$closed = in_array($stage, array('won', 'lost'), true);
	$reason = isset($_POST['lead_lost_reason']) ? sanitize_key(wp_unslash($_POST['lead_lost_reason'])) : '';
	$amount = isset($_POST['lead_quote_amount']) && '' !== $_POST['lead_quote_amount'] ? round(max(0, (float) $_POST['lead_quote_amount']), 2) : '';
	$stop = !empty($_POST['lead_no_followup']) || $closed;
	$values = array(
		'_lead_stage' => $stage,
		'_lead_owner' => isset($_POST['lead_owner']) ? absint($_POST['lead_owner']) : 0,
		'_lead_last_contact' => $date('lead_last_contact'),
		'_lead_next_followup' => $stop ? '' : $date('lead_next_followup'),
		'_lead_quote_amount' => $amount,
		'_lead_order' => isset($_POST['lead_order']) ? sanitize_text_field(wp_unslash($_POST['lead_order'])) : '',
		'_lead_lost_reason' => 'lost' === $stage && isset(wholesale_lead_lost_reasons()[$reason]) ? $reason : '',
		'_lead_no_followup' => $stop ? 1 : '',
	);
	foreach ($values as $key => $value) {
		if ('' === $value || 0 === $value) {
			delete_post_meta($post_id, $key);
		} else {
			update_post_meta($post_id, $key, $value);
		}
	}
}, 20);

/** A reply sent from the existing "Reply to customer" box marks a New lead as Contacted. */
function wholesale_lead_note_reply($meta_id, $post_id, $meta_key)
{
	if ('_contact_last_reply' !== $meta_key || 'contact_submission' !== get_post_type($post_id)) {
		return;
	}
	if ('new' === wholesale_lead_stage($post_id)) {
		update_post_meta($post_id, '_lead_stage', 'contacted');
	}
	update_post_meta($post_id, '_lead_last_contact', current_time('Y-m-d'));
}
add_action('added_post_meta', 'wholesale_lead_note_reply', 10, 3);
add_action('updated_post_meta', 'wholesale_lead_note_reply', 10, 3);

add_filter('manage_contact_submission_posts_columns', function ($columns) {
	$date = isset($columns['date']) ? $columns['date'] : 'Received';
	unset($columns['date']);
	$columns['lead_stage'] = 'Stage';
	$columns['lead_interest'] = 'Interest / source';
	$columns['lead_followup'] = 'Next follow-up';
	$columns['date'] = $date;
	return $columns;
}, 30);

add_action('manage_contact_submission_posts_custom_column', function ($column, $post_id) {
	if ('lead_stage' === $column) {
		$stages = wholesale_lead_stages();
		$stage = wholesale_lead_stage($post_id);
		$colors = array('new' => '#b26200', 'contacted' => '#2271b1', 'quoted' => '#6c3fb5', 'won' => '#008a20', 'lost' => '#646970');
		echo '<strong style="color:' . esc_attr($colors[$stage]) . '">' . esc_html($stages[$stage]) . '</strong>';
		$amount = get_post_meta($post_id, '_lead_quote_amount', true);
		if ('' !== (string) $amount) {
			echo '<br>$' . esc_html(number_format((float) $amount, 2));
		}
		$owner = (int) get_post_meta($post_id, '_lead_owner', true);
		$user = $owner ? get_userdata($owner) : false;
		if ($user) {
			echo '<br><span style="color:#646970">' . esc_html($user->display_name) . '</span>';
		}
	} elseif ('lead_interest' === $column) {
		$interests = wholesale_quote_interests();
		$interest = (string) get_post_meta($post_id, '_contact_interest', true);
		echo esc_html(isset($interests[$interest]) ? $interests[$interest]['label'] : (string) get_post_meta($post_id, '_contact_project_type', true));
		$source = (string) get_post_meta($post_id, '_contact_source', true);
		echo '<br><span style="color:#646970">' . esc_html($source ? $source : 'Direct / unknown') . '</span>';
	} elseif ('lead_followup' === $column) {
		$next = (string) get_post_meta($post_id, '_lead_next_followup', true);
		echo $next ? esc_html($next) : '—';
		if (wholesale_lead_is_overdue($post_id)) {
			echo '<br><strong style="color:#d63638">Overdue</strong>';
		}
	}
}, 10, 2);

/** Stage filter + "Overdue" view on the Contact Submissions list. */
add_filter('views_edit-contact_submission', function ($views) {
	$base = admin_url('edit.php?post_type=contact_submission');
	$current = isset($_GET['lead_stage']) ? sanitize_key(wp_unslash($_GET['lead_stage'])) : '';
	foreach (array_merge(wholesale_lead_stages(), array('overdue' => 'Overdue follow-up')) as $key => $label) {
		$views['lead_' . $key] = '<a href="' . esc_url(add_query_arg('lead_stage', $key, $base)) . '"' . ($current === $key ? ' class="current" aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
	}
	return $views;
});

add_action('pre_get_posts', function ($query) {
	if (!is_admin() || !$query->is_main_query() || 'contact_submission' !== $query->get('post_type') || empty($_GET['lead_stage'])) {
		return;
	}
	$stage = sanitize_key(wp_unslash($_GET['lead_stage']));
	if ('overdue' === $stage) {
		$query->set('meta_query', array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array('key' => '_lead_stage', 'compare' => 'NOT EXISTS'),
				array('key' => '_lead_stage', 'value' => array('won', 'lost'), 'compare' => 'NOT IN'),
			),
			array('key' => '_lead_next_followup', 'value' => current_time('Y-m-d'), 'compare' => '<', 'type' => 'DATE'),
		));
	} elseif ('new' === $stage) {
		$query->set('meta_query', array(
			'relation' => 'OR',
			array('key' => '_lead_stage', 'compare' => 'NOT EXISTS'),
			array('key' => '_lead_stage', 'value' => 'new'),
		));
	} elseif (isset(wholesale_lead_stages()[$stage])) {
		$query->set('meta_query', array(array('key' => '_lead_stage', 'value' => $stage)));
	}
});

/** Dashboard reminder: open leads with an overdue follow-up, and New leads with no reply after a day. */
add_action('wp_dashboard_setup', function () {
	if (!current_user_can('edit_posts')) {
		return;
	}
	wp_add_dashboard_widget('wholesale_lead_reminders', 'Quote requests needing follow-up', function () {
		$ids = get_posts(array(
			'post_type' => 'contact_submission',
			'post_status' => 'publish',
			'posts_per_page' => 200,
			'fields' => 'ids',
			'date_query' => array(array('after' => '90 days ago')),
		));
		$due = array_values(array_filter($ids, 'wholesale_lead_is_overdue'));
		if (!$due) {
			echo '<p>Nothing overdue.</p>';
			return;
		}
		echo '<ul>';
		foreach (array_slice($due, 0, 15) as $id) {
			echo '<li><a href="' . esc_url(get_edit_post_link($id)) . '">' . esc_html(get_the_title($id)) . '</a> &middot; ' . esc_html(wholesale_lead_stages()[wholesale_lead_stage($id)]) . '</li>';
		}
		echo '</ul><p>' . esc_html(sprintf('%d lead(s) in the last 90 days need a reply or follow-up.', count($due))) . ' <a href="' . esc_url(admin_url('edit.php?post_type=contact_submission&lead_stage=overdue')) . '">Overdue follow-ups</a></p>';
	});
});

/* ---------------------------------------------------------------------------
 * Case studies (data/case-studies.php)
 * ------------------------------------------------------------------------- */

/** Case studies cleared to show: published, customer permission, and a real photo. */
function wholesale_case_studies()
{
	$file = get_template_directory() . '/data/case-studies.php';
	$entries = file_exists($file) ? (array) include $file : array();
	return array_filter($entries, static function ($entry) {
		return !empty($entry['published']) && !empty($entry['permission'])
			&& (!empty($entry['photos_day']) || !empty($entry['photos_night']))
			&& !empty($entry['business_type']) && !empty($entry['style']);
	});
}

/**
 * Case study cards, or '' when none are cleared (the section is then left out entirely).
 * $filter: optional callback to pick entries (e.g. by business type on an industry page).
 */
function wholesale_render_case_studies($heading = 'Recent Customer Projects', $filter = null)
{
	$entries = wholesale_case_studies();
	if ($filter) {
		$entries = array_filter($entries, $filter);
	}
	if (!$entries) {
		return '';
	}
	ob_start();
	?>
	<section class="case-studies" aria-labelledby="case-studies-title">
		<div class="container">
			<h2 id="case-studies-title"><?php echo esc_html($heading); ?></h2>
			<div class="case-study-grid">
				<?php foreach ($entries as $key => $entry) : ?>
					<article class="case-study" id="<?php echo esc_attr($key); ?>">
						<?php foreach (array('photos_day' => 'Daytime', 'photos_night' => 'At night') as $photo_key => $photo_label) :
							foreach (array_slice((array) $entry[$photo_key], 0, 1) as $photo_id) :
								$image = wp_get_attachment_image((int) $photo_id, 'medium_large', false, array('loading' => 'lazy', 'alt' => $entry['business_type'] . ' sign, ' . strtolower($photo_label)));
								if ($image) : ?>
									<figure><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput ?><figcaption><?php echo esc_html($photo_label); ?></figcaption></figure>
								<?php endif;
							endforeach;
						endforeach; ?>
						<h3><?php echo esc_html($entry['business_type'] . ($entry['location'] ? ', ' . $entry['location'] : '')); ?></h3>
						<dl>
							<?php foreach (array('requirement' => 'Needed', 'style' => 'Sign', 'size' => 'Size', 'materials' => 'Materials', 'scope' => 'We made and shipped', 'install' => 'Installed by') as $field_key => $field_label) : ?>
								<?php if (!empty($entry[$field_key])) : ?>
									<dt><?php echo esc_html($field_label); ?></dt><dd><?php echo esc_html($entry[$field_key]); ?></dd>
								<?php endif; ?>
							<?php endforeach; ?>
						</dl>
						<?php if (!empty($entry['quote'])) : ?>
							<blockquote><?php echo esc_html($entry['quote']); ?><?php if (!empty($entry['quote_name'])) : ?><cite><?php echo esc_html($entry['quote_name']); ?></cite><?php endif; ?></blockquote>
						<?php endif; ?>
						<?php if (!empty($entry['concept_image']) && ($concept = wp_get_attachment_image((int) $entry['concept_image'], 'medium', false, array('loading' => 'lazy')))) : ?>
							<figure class="case-study-concept"><?php echo $concept; // phpcs:ignore WordPress.Security.EscapeOutput ?><figcaption>Concept rendering, not a photo of the installed sign</figcaption></figure>
						<?php endif; ?>
						<p class="case-study-links">
							<?php foreach ((array) $entry['products'] as $slug) :
								$product = wholesale_package_product($slug);
								if ($product) : ?>
									<a href="<?php echo esc_url(get_permalink($product)); ?>"><?php echo esc_html(html_entity_decode(get_the_title($product), ENT_QUOTES)); ?></a>
								<?php endif;
							endforeach; ?>
							<a class="case-study-quote" href="<?php echo esc_url(wholesale_quote_url('channel-letters')); ?>">Get a quote for a sign like this</a>
						</p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * GA4 gaps (2026-11-05): view_item on product pages and contact taps as a secondary
 * event. The existing tags already send add_to_cart (after the cart confirms),
 * begin_checkout, purchase (once per order) and generate_lead (once per saved
 * quote). No names, emails, phones or files are sent in these events.
 * ------------------------------------------------------------------------- */

function wholesale_ga4_gap_events()
{
	if ('' === wholesale_ga4_measurement_id()) {
		return;
	}
	$item = null;
	if (is_singular('product')) {
		$product_id = get_queried_object_id();
		$terms = wp_get_post_terms($product_id, 'product_category', array('fields' => 'names'));
		$item = array(
			'item_id' => wholesale_ads_item_id($product_id),
			'item_name' => html_entity_decode(get_the_title($product_id), ENT_QUOTES, 'UTF-8'),
			'item_category' => is_array($terms) && $terms ? html_entity_decode((string) $terms[0], ENT_QUOTES, 'UTF-8') : '',
		);
	}
	?>
	<script>
		(function () {
			window.dataLayer = window.dataLayer || [];
			function gtag() { window.dataLayer.push(arguments); }
			<?php if ($item) : ?>
			gtag('event', 'view_item', { currency: 'USD', items: [<?php echo wp_json_encode($item); ?>] });
			<?php endif; ?>
			// Secondary interaction only: a tap on a phone/text/email link, not a completed call or lead.
			var last = 0;
			document.addEventListener('click', function (e) {
				var target = e.composedPath ? e.composedPath()[0] : e.target;
				var link = target && target.closest ? target.closest('a[href]') : null;
				if (!link) return;
				var href = (link.getAttribute('href') || '').trim().toLowerCase();
				var method = 0 === href.indexOf('tel:') ? 'phone' : (0 === href.indexOf('sms:') ? 'sms' : (0 === href.indexOf('mailto:') ? 'email' : ''));
				if (!method || Date.now() - last < 3000) return;
				last = Date.now();
				gtag('event', 'contact_click', { method: method });
			}, true);
		})();
	</script>
	<?php
}
add_action('wp_footer', 'wholesale_ga4_gap_events', 22);
