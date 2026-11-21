<?php
/**
 * Per-page hero photos (desktop + phone) with gradient overlay presets.
 *
 * Fields:
 *  - Pages: "Hero images" box on the edit screen.
 *  - Product categories: fields on the add/edit form (child categories use
 *    their parent's photo until they get their own).
 *  - Settings > Storefront Sign > Images: a fallback pair for the text-only
 *    guide pages (warranty, permits, industries ...), see retails_image_slots().
 *
 * Nothing changes on a page until one of those fields is filled in: the CSS
 * below is printed only for pages that have a photo, so every other page keeps
 * its current hero byte for byte. The photos replace the background of the hero
 * band the template already prints (.home-hero-content, .bd-hero, .sf-hero,
 * .sc-hero, .contact-hero, .cla-hero, .pkg-hero, .track-hero), so no template
 * needs its own wiring.
 */

if (!defined('ABSPATH')) {
	exit;
}

const RETAILS_HERO_DESKTOP = '_sfs_hero_desktop';
const RETAILS_HERO_MOBILE = '_sfs_hero_mobile';
const RETAILS_HERO_OVERLAY = '_sfs_hero_overlay';
const RETAILS_HERO_FOCUS = '_sfs_hero_focus';

/**
 * Overlay presets: label, plus the gradient layers for desktop, tablet and
 * phone. Same navy as the rest of the site's heroes (rgb 3, 24, 53). Phones get
 * a top-down overlay because the copy spans the full width there.
 */
function retails_hero_overlays()
{
	$n = 'rgba(3,24,53,';
	return array(
		'left' => array(
			'label' => 'Dark on the left (headline left, subject right)',
			'd' => "linear-gradient(90deg,{$n}.96) 0%,{$n}.86) 32%,{$n}.34) 58%,{$n}.06) 100%),linear-gradient(0deg,{$n}.38) 0%,{$n}0) 38%)",
			't' => "linear-gradient(90deg,{$n}.96) 0%,{$n}.9) 50%,{$n}.7) 80%,{$n}.45) 100%)",
			'm' => "linear-gradient(180deg,{$n}.9) 0%,{$n}.74) 38%,{$n}.52) 72%,{$n}.42) 100%)",
		),
		'bottom' => array(
			'label' => 'Dark at the bottom (centered or short heroes)',
			'd' => "linear-gradient(180deg,{$n}.32) 0%,{$n}.5) 38%,{$n}.92) 100%)",
			't' => "linear-gradient(180deg,{$n}.4) 0%,{$n}.62) 40%,{$n}.94) 100%)",
			'm' => "linear-gradient(180deg,{$n}.55) 0%,{$n}.78) 50%,{$n}.94) 100%)",
		),
		'full' => array(
			'label' => 'Even dim (busy photos)',
			'd' => "linear-gradient({$n}.72),{$n}.72))",
			't' => "linear-gradient({$n}.76),{$n}.76))",
			'm' => "linear-gradient({$n}.72),{$n}.72))",
		),
		'brand' => array(
			'label' => 'Navy to brand blue tint',
			'd' => "linear-gradient(115deg,{$n}.95) 0%,rgba(8,62,112,.82) 45%,rgba(31,168,222,.36) 100%)",
			't' => "linear-gradient(115deg,{$n}.95) 0%,rgba(8,62,112,.86) 55%,rgba(31,168,222,.4) 100%)",
			'm' => "linear-gradient(170deg,{$n}.95) 0%,rgba(8,62,112,.84) 55%,rgba(31,168,222,.45) 100%)",
		),
	);
}

/** Focal point choices: key => CSS background-position. */
function retails_hero_focus_points()
{
	return array(
		'left top' => array('Top left', '15% 15%'),
		'center top' => array('Top', '50% 15%'),
		'right top' => array('Top right', '85% 15%'),
		'left center' => array('Left', '15% 50%'),
		'center center' => array('Center (default)', '50% 50%'),
		'right center' => array('Right', '85% 50%'),
		'left bottom' => array('Bottom left', '15% 85%'),
		'center bottom' => array('Bottom', '50% 85%'),
		'right bottom' => array('Bottom right', '85% 85%'),
	);
}

/** Templates whose hero is a text-only .sf-hero band (the fallback photo goes here). */
function retails_hero_fallback_templates()
{
	return array(
		'page-industries.php', 'page-industry.php', 'page-location.php', 'page-locations.php',
		'page-shipping-returns.php', 'page-sign-permits.php', 'page-warranty.php',
		'page-how-to-install-channel-letters.php', 'page-storefront-signs.php',
		'page-channel-letter-cost.php', 'page-design-templates.php', 'page-developer.php',
	);
}

function retails_hero_remember_template($template)
{
	$GLOBALS['retails_hero_template'] = basename((string) $template);
	return $template;
}
add_filter('template_include', 'retails_hero_remember_template', 99);

/* ------------------------------------------------------------------------
 * Reading
 * --------------------------------------------------------------------- */

function retails_hero_valid_image($id)
{
	$id = absint($id);
	return $id && wp_attachment_is_image($id) ? $id : 0;
}

/** The four values for a page (post) or category (term), '' / 0 when unset. */
function retails_hero_read($kind, $id)
{
	$get = 'term' === $kind ? 'get_term_meta' : 'get_post_meta';
	$overlay = (string) $get($id, RETAILS_HERO_OVERLAY, true);
	$focus = (string) $get($id, RETAILS_HERO_FOCUS, true);
	return array(
		'desktop' => retails_hero_valid_image($get($id, RETAILS_HERO_DESKTOP, true)),
		'mobile' => retails_hero_valid_image($get($id, RETAILS_HERO_MOBILE, true)),
		'overlay' => isset(retails_hero_overlays()[$overlay]) ? $overlay : 'left',
		'focus' => isset(retails_hero_focus_points()[$focus]) ? $focus : 'center center',
	);
}

/**
 * The hero configuration for the page being shown, or null when the page has
 * no photo of its own and is not a fallback page (it then looks as before).
 */
function retails_hero_config()
{
	static $config = false;
	if (false !== $config) {
		return $config;
	}
	$config = null;
	if (is_admin() || is_feed() || is_404() || is_search()) {
		return $config;
	}

	$found = null;
	$object = get_queried_object();

	// Category pages are served by home.php / page-channel-letters.php through the
	// category_slug query var (see retails_category_template()), not as a term archive.
	$slug = get_query_var('category_slug');
	$slug = $slug ? sanitize_title($slug) : '';
	$term = $slug ? get_term_by('slug', $slug, 'product_category') : null;
	if (!$term && $object instanceof WP_Term && 'product_category' === $object->taxonomy) {
		$term = $object;
	}
	for ($i = 0; $term && !is_wp_error($term) && $i < 6; $i++) {
		$values = retails_hero_read('term', $term->term_id);
		if ($values['desktop'] || $values['mobile']) {
			$found = $values;
			break;
		}
		$term = $term->parent ? get_term($term->parent, 'product_category') : null;
	}

	if (!$found) {
		$page_id = 0;
		if ($object instanceof WP_Post && 'page' === $object->post_type) {
			$page_id = $object->ID;
		} elseif (is_front_page() && (int) get_option('page_on_front')) {
			$page_id = (int) get_option('page_on_front');
		}
		if ($page_id && !$slug) {
			$values = retails_hero_read('post', $page_id);
			$found = ($values['desktop'] || $values['mobile']) ? $values : null;
		}
	}

	if (!$found) {
		// Fallback photo for the text-only guide pages.
		$template = $GLOBALS['retails_hero_template'] ?? '';
		if (in_array($template, retails_hero_fallback_templates(), true)) {
			$desktop = retails_image_slot_effective_id('hero_fallback');
			$mobile = retails_image_slot_effective_id('hero_fallback_mobile');
			if ($desktop || $mobile) {
				$found = array('desktop' => $desktop, 'mobile' => $mobile, 'overlay' => 'left', 'focus' => 'center center');
			}
		}
	}
	if (!$found) {
		return $config;
	}

	$url = static function ($id, $size) {
		if (!$id) {
			return '';
		}
		$src = wp_get_attachment_image_url($id, $size);
		if ($src && function_exists('retails_webp_url')) {
			$src = retails_webp_url($src);
		}
		return $src ? esc_url_raw($src) : '';
	};

	$config = array(
		'desktop' => $url($found['desktop'], '2048x2048'),
		// Phones: their own photo (up to 1536 px, sharp on high-density screens),
		// else the 1024 px copy of the desktop one.
		'mobile' => $found['mobile'] ? $url($found['mobile'], '1536x1536') : $url($found['desktop'], 'large'),
		'overlay' => $found['overlay'],
		'focus' => $found['focus'],
		// Share previews (og:image): the uploaded file at 1024 px, in the format it was uploaded in.
		'share' => (string) wp_get_attachment_image_url($found['desktop'] ? $found['desktop'] : $found['mobile'], 'large'),
	);
	if ('' === $config['desktop'] && '' === $config['mobile']) {
		$config = null;
	}
	return $config;
}

function retails_hero_body_class($classes)
{
	if (retails_hero_config()) {
		$classes[] = 'sfs-hero-on';
	}
	return $classes;
}
add_filter('body_class', 'retails_hero_body_class');

function retails_hero_css_url($url)
{
	return "url('" . str_replace(array("'", '"', '(', ')', ' ', "\n"), array('%27', '%22', '%28', '%29', '%20', ''), $url) . "')";
}

/* ------------------------------------------------------------------------
 * Front end: preload + CSS (only on pages that have a photo)
 * --------------------------------------------------------------------- */

function retails_hero_head()
{
	$config = retails_hero_config();
	if (!$config) {
		return;
	}

	// The template's own hero photo is no longer shown; don't preload it.
	if ($config['desktop']) {
		$GLOBALS['retails_hero_custom'] = true;
	}

	$desktop = $config['desktop'];
	$mobile = $config['mobile'];
	if ($desktop && $mobile && $desktop === $mobile) {
		printf('<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url($desktop));
	} else {
		if ($mobile) {
			printf('<link rel="preload" as="image" href="%s" media="(max-width: 767px)" fetchpriority="high">' . "\n", esc_url($mobile));
		}
		if ($desktop) {
			printf('<link rel="preload" as="image" href="%s" media="(min-width: 768px)" fetchpriority="high">' . "\n", esc_url($desktop));
		}
	}

	$overlay = retails_hero_overlays()[$config['overlay']];
	$pos = retails_hero_focus_points()[$config['focus']][1];

	// The hero bands the templates print. body.sfs-hero-on keeps these rules
	// above the per-page rules in style.css, whatever the load order.
	$bands = 'body.sfs-hero-on .home-hero .home-hero-content,body.sfs-hero-on .bd-hero,body.sfs-hero-on .sf-hero,body.sfs-hero-on .sc-hero,body.sfs-hero-on .contact-hero,body.sfs-hero-on .cla-hero,body.sfs-hero-on .pkg-hero,body.sfs-hero-on .track-hero';
	$base = 'background-color:#031835;background-size:cover;background-repeat:no-repeat;background-position:' . $pos . ';';

	$css = $bands . '{' . $base . '}';
	if ($desktop) {
		$d = retails_hero_css_url($desktop);
		$css .= '@media(min-width:768px){' . $bands . '{background-image:' . $overlay['t'] . ',' . $d . '}}';
		$css .= '@media(min-width:992px){' . $bands . '{background-image:' . $overlay['d'] . ',' . $d . '}';
		// Channel letters page: the photo sits on a ::before layer that starts 22% in.
		$cl_overlay = 'left' === $config['overlay']
			? 'linear-gradient(90deg,rgba(3,24,53,1) 0%,rgba(3,24,53,.88) 18%,rgba(3,24,53,.4) 42%,rgba(3,24,53,.05) 100%)'
			: $overlay['d'];
		$css .= 'body.sfs-hero-on .home-hero--cl-page .home-hero-content{background-image:none}';
		$css .= 'body.sfs-hero-on .home-hero--cl-page .home-hero-content::before{background-image:' . $cl_overlay . ',' . $d . ';background-position:' . $pos . '}}';
	}
	if ($mobile) {
		$m = retails_hero_css_url($mobile);
		$css .= '@media(max-width:767px){' . $bands . '{background-image:' . $overlay['m'] . ',' . $m . '}}';
	}

	// Pages whose hero is normally a light band: white text on the photo, the
	// same treatment as .sf-hero--photo in css/seo-pages.css.
	$css .= 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo),body.sfs-hero-on .sc-hero,body.sfs-hero-on .contact-hero{color:#fff;border-color:transparent}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo){padding-bottom:72px}'
		. 'body.sfs-hero-on .sc-hero{padding-bottom:48px}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-hero-title,body.sfs-hero-on .sc-hero-title,body.sfs-hero-on .contact-hero h1{color:#fff}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-hero-lead,body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-byline,body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-hero-points,body.sfs-hero-on .sc-hero-lead,body.sfs-hero-on .contact-hero-copy>p:not(.contact-eyebrow),body.sfs-hero-on .contact-trust-row,body.sfs-hero-on .contact-trust-row strong{color:rgba(255,255,255,.88)}'
		// Contact trust pills: style.css gives them a see-through white fill for the light
		// band, which washed out the white text on the photo (owner, Nov 20 2026). Same
		// navy glass as the home hero product cards, with the site's light blue.
		. 'body.sfs-hero-on .contact-trust-row span{border-color:rgba(31,168,222,.55);background:rgba(3,24,53,.78);color:#fff;-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}'
		. 'body.sfs-hero-on .contact-trust-row strong{color:#7fd3f2}'
		// css/ui-polish.css forces .cl-kicker to dark teal with !important (for white bands).
		. 'body.sfs-hero-on .sf-hero .cl-kicker{color:#7fd3f2!important}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .site-breadcrumbs ol,body.sfs-hero-on .sc-hero .site-breadcrumbs ol{border-color:rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:rgba(255,255,255,.7)}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .site-breadcrumbs a,body.sfs-hero-on .sc-hero .site-breadcrumbs a{color:#7fd3f2}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .site-breadcrumbs [aria-current="page"],body.sfs-hero-on .sc-hero .site-breadcrumbs [aria-current="page"]{color:#fff}'
		. 'body.sfs-hero-on .sf-hero:not(.sf-hero--photo) .sf-button-outline{border-color:#fff;color:#fff}'
		// Plain text links in the hero copy (phone numbers, jump links) turn light on the photo.
		. 'body.sfs-hero-on .contact-hero-call a,body.sfs-hero-on .sf-hero .gd-hero-call,body.sfs-hero-on .sf-hero .gd-jump-links a,body.sfs-hero-on .sf-hero-lead a,body.sfs-hero-on .sf-byline a,body.sfs-hero-on .sf-hero-points a,body.sfs-hero-on .sc-hero-lead a{color:#7fd3f2}'
		// Sign packages and track order print a plain text header inside the page
		// container: with a photo it becomes a rounded photo card with white text.
		. 'body.sfs-hero-on .pkg-hero,body.sfs-hero-on .track-hero{max-width:none;border-radius:16px;padding:64px 48px;min-height:300px;display:flex;flex-direction:column;justify-content:center;color:#fff}'
		. 'body.sfs-hero-on .pkg-hero{margin-bottom:28px}body.sfs-hero-on .pkg-hero>*{max-width:640px}'
		. 'body.sfs-hero-on .track-hero{align-items:center;margin-bottom:28px}body.sfs-hero-on .track-hero>*{max-width:560px}'
		. 'body.sfs-hero-on .pkg-hero h1,body.sfs-hero-on .track-hero h1{color:#fff}'
		. 'body.sfs-hero-on .pkg-hero p,body.sfs-hero-on .track-hero p{color:rgba(255,255,255,.9)}'
		. 'body.sfs-hero-on .track-hero .track-eyebrow{color:#7fd3f2}'
		. '@media(max-width:767px){body.sfs-hero-on .pkg-hero,body.sfs-hero-on .track-hero{padding:44px 22px;min-height:340px;border-radius:12px}'
		// Phones show more of the photo (lighter overlay): a soft shadow keeps the copy crisp.
		. 'body.sfs-hero-on :is(.home-hero-copy,.bd-hero-copy,.sf-hero>.container,.sc-hero-copy,.contact-hero-copy,.cla-hero-copy,.pkg-hero,.track-hero)>:is(h1,p){text-shadow:0 1px 3px rgba(3,24,53,.65)}}';

	echo '<style id="sfs-hero-images">' . $css . "</style>\n"; // Built from fixed presets and escaped URLs.
}
add_action('wp_head', 'retails_hero_head', 0);

/* ------------------------------------------------------------------------
 * Admin fields
 * --------------------------------------------------------------------- */

function retails_hero_field_html($values, $table = false)
{
	$overlays = retails_hero_overlays();
	$focus_points = retails_hero_focus_points();
	$images = array(
		'desktop' => array('Desktop photo', 'Landscape, about 1920 &times; 800 px (WebP or JPEG). Keep the left third quiet and dark where the headline sits.'),
		'mobile' => array('Phone photo (optional)', 'Portrait or square, about 900 &times; 1200 px, for screens under 768 px. Empty: phones get a smaller copy of the desktop photo.'),
	);

	wp_nonce_field('sfs_hero_save', 'sfs_hero_nonce');
	echo '<div class="sfs-hero-fields">';
	foreach ($images as $key => $info) {
		$id = (int) $values[$key];
		$name = 'sfs_hero_' . $key;
		$preview = $id ? wp_get_attachment_image_url($id, 'medium') : '';
		printf(
			'<div class="sfs-hero-img" style="margin:0 0 14px"><strong>%s</strong><div class="sfs-hero-prev" style="margin:6px 0;max-width:260px;background:#f0f0f1">%s</div><input type="hidden" name="%s" value="%d"><button type="button" class="button sfs-hero-pick">Choose image</button> <button type="button" class="button-link sfs-hero-clear"%s>Remove</button><p class="description" style="margin:4px 0 0">%s</p></div>',
			esc_html($info[0]),
			$preview ? '<img src="' . esc_url($preview) . '" alt="" style="max-width:100%;height:auto;display:block">' : '',
			esc_attr($name),
			$id,
			$id ? '' : ' style="display:none"',
			wp_kses($info[1], array())
		);
	}

	echo '<p style="margin:0 0 10px"><label><strong>Gradient overlay</strong><br><select name="sfs_hero_overlay">';
	foreach ($overlays as $key => $overlay) {
		printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($values['overlay'], $key, false), esc_html($overlay['label']));
	}
	echo '</select></label></p>';

	echo '<p style="margin:0"><label><strong>Focal point</strong><br><select name="sfs_hero_focus">';
	foreach ($focus_points as $key => $point) {
		printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($values['focus'], $key, false), esc_html($point[0]));
	}
	echo '</select></label><span class="description" style="display:block">Which part of the photo stays in view when the hero crops.</span></p>';
	echo '</div>';
}

function retails_hero_save_values($kind, $id)
{
	if (!isset($_POST['sfs_hero_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sfs_hero_nonce'])), 'sfs_hero_save')) {
		return;
	}
	$set = 'term' === $kind ? 'update_term_meta' : 'update_post_meta';
	$delete = 'term' === $kind ? 'delete_term_meta' : 'delete_post_meta';

	foreach (array('desktop' => RETAILS_HERO_DESKTOP, 'mobile' => RETAILS_HERO_MOBILE) as $field => $meta) {
		$value = isset($_POST['sfs_hero_' . $field]) ? retails_hero_valid_image(wp_unslash($_POST['sfs_hero_' . $field])) : 0;
		$value ? $set($id, $meta, $value) : $delete($id, $meta);
	}

	$overlay = isset($_POST['sfs_hero_overlay']) ? sanitize_key(wp_unslash($_POST['sfs_hero_overlay'])) : 'left';
	('left' !== $overlay && isset(retails_hero_overlays()[$overlay])) ? $set($id, RETAILS_HERO_OVERLAY, $overlay) : $delete($id, RETAILS_HERO_OVERLAY);

	$focus = isset($_POST['sfs_hero_focus']) ? sanitize_text_field(wp_unslash($_POST['sfs_hero_focus'])) : 'center center';
	('center center' !== $focus && isset(retails_hero_focus_points()[$focus])) ? $set($id, RETAILS_HERO_FOCUS, $focus) : $delete($id, RETAILS_HERO_FOCUS);
}

/* Pages */
function retails_hero_add_metabox()
{
	add_meta_box('sfs-hero-images', 'Hero images', function ($post) {
		retails_hero_field_html(retails_hero_read('post', $post->ID));
	}, 'page', 'side', 'default');
}
add_action('add_meta_boxes_page', 'retails_hero_add_metabox');

function retails_hero_save_page($post_id)
{
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_page', $post_id)) {
		return;
	}
	retails_hero_save_values('post', $post_id);
}
add_action('save_post_page', 'retails_hero_save_page');

/* Product categories */
function retails_hero_term_add_form()
{
	echo '<div class="form-field"><label>Hero images</label>';
	retails_hero_field_html(retails_hero_read('term', 0));
	echo '</div>';
}
add_action('product_category_add_form_fields', 'retails_hero_term_add_form');

function retails_hero_term_edit_form($term)
{
	echo '<tr class="form-field"><th scope="row">Hero images</th><td>';
	retails_hero_field_html(retails_hero_read('term', $term->term_id));
	echo '<p class="description">Child categories use the parent category\'s hero photo until they get their own.</p></td></tr>';
}
add_action('product_category_edit_form_fields', 'retails_hero_term_edit_form');

function retails_hero_save_term($term_id)
{
	if (!current_user_can('manage_categories')) {
		return;
	}
	retails_hero_save_values('term', $term_id);
}
add_action('created_product_category', 'retails_hero_save_term');
add_action('edited_product_category', 'retails_hero_save_term');

/* Media picker script, only on the screens that have the fields. */
function retails_hero_admin_assets($hook)
{
	$screen = get_current_screen();
	if (!$screen || !in_array($screen->id, array('page', 'edit-product_category'), true)) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script('jquery-core', <<<'JS'
jQuery(function ($) {
	$(document).on('click', '.sfs-hero-pick', function (e) {
		e.preventDefault();
		var box = $(this).closest('.sfs-hero-img');
		var frame = wp.media({ title: 'Choose hero image', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			var u = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
			box.find('input[type=hidden]').val(a.id);
			box.find('.sfs-hero-prev').empty().append($('<img alt="">').attr('src', u).css({ maxWidth: '100%', height: 'auto', display: 'block' }));
			box.find('.sfs-hero-clear').show();
		});
		frame.open();
	});
	$(document).on('click', '.sfs-hero-clear', function (e) {
		e.preventDefault();
		var box = $(this).closest('.sfs-hero-img');
		box.find('input[type=hidden]').val('');
		box.find('.sfs-hero-prev').empty();
		$(this).hide();
	});
	// Add-category form is submitted by AJAX: clear the fields afterwards.
	$(document).ajaxSuccess(function (ev, xhr, opts) {
		if (opts && opts.data && String(opts.data).indexOf('action=add-tag') > -1) {
			$('.sfs-hero-img input[type=hidden]').val('');
			$('.sfs-hero-prev').empty();
			$('.sfs-hero-clear').hide();
		}
	});
});
JS
	);
}
add_action('admin_enqueue_scripts', 'retails_hero_admin_assets');
