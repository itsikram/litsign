<?php
/**
 * SEO menu, shared admin UI helpers and the settings save handler.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

/**
 * SEO screens: slug => array(menu label, render callback).
 *
 * @return array
 */
function wholesale_seo_admin_pages()
{
	return apply_filters('wholesale_seo_admin_pages', array(
		'wholesale-seo' => array('Dashboard', 'wholesale_seo_dashboard_page'),
		'wholesale-seo-general' => array('General & Titles', 'wholesale_seo_general_page'),
		'wholesale-seo-business' => array('Business & Schema', 'wholesale_seo_business_page'),
		'wholesale-seo-bulk' => array('Per-page SEO', 'wholesale_seo_bulk_page'),
	));
}

add_action('admin_menu', function () {
	$pages = wholesale_seo_admin_pages();
	add_menu_page('SEO', 'SEO', 'manage_options', 'wholesale-seo', $pages['wholesale-seo'][1], 'dashicons-search', 58);

	foreach ($pages as $slug => $page) {
		add_submenu_page('wholesale-seo', $page[0] . ' ‹ SEO', $page[0], 'manage_options', $slug, $page[1]);
	}
});

/**
 * Whether the current admin screen shows SEO fields (menu pages, edit
 * screens with the SEO box, product category edit screens).
 */
function wholesale_seo_is_seo_screen($hook)
{
	if (false !== strpos((string) $hook, 'wholesale-seo')) {
		return true;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen) {
		return false;
	}

	if (in_array($hook, array('post.php', 'post-new.php'), true)) {
		return in_array($screen->post_type, wholesale_seo_metabox_post_types(), true);
	}

	return in_array($hook, array('term.php', 'edit-tags.php'), true) && 'product_category' === $screen->taxonomy;
}

add_action('admin_enqueue_scripts', function ($hook) {
	if (!current_user_can('manage_options') || !wholesale_seo_is_seo_screen($hook)) {
		return;
	}

	$dir = get_template_directory() . '/inc/seo-admin/assets/';
	$uri = get_template_directory_uri() . '/inc/seo-admin/assets/';
	wp_enqueue_media();
	wp_enqueue_style('wholesale-seo-admin', $uri . 'admin.css', array(), (string) filemtime($dir . 'admin.css'));
	wp_enqueue_script('wholesale-seo-admin', $uri . 'admin.js', array('jquery'), (string) filemtime($dir . 'admin.js'), true);
	wp_localize_script('wholesale-seo-admin', 'wholesaleSeo', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'scanNonce' => wp_create_nonce('wholesale_seo_scan'),
		'bulkNonce' => wp_create_nonce('wholesale_seo_bulk'),
		'brand' => wholesale_seo_brand(),
		'separator' => wholesale_seo_separator(),
	));
});

/**
 * Open an SEO screen: title, tabs and the saved / error notice.
 */
function wholesale_seo_admin_header($current, $intro = '')
{
	$pages = wholesale_seo_admin_pages();
	?>
	<div class="wrap wseo">
		<h1>SEO <span class="wseo-sub"><?php echo esc_html(isset($pages[$current]) ? $pages[$current][0] : ''); ?></span></h1>
		<nav class="nav-tab-wrapper wseo-tabs" aria-label="SEO sections">
			<?php foreach ($pages as $slug => $page) : ?>
				<a class="nav-tab<?php echo $slug === $current ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html($page[0]); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php if (isset($_GET['wseo_saved'])) : ?>
			<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
		<?php endif; ?>
		<?php
		$dropped = isset($_GET['wseo_dropped']) ? array_filter(array_map('sanitize_key', explode(',', wp_unslash($_GET['wseo_dropped'])))) : array();
		if ($dropped) :
			?>
			<div class="notice notice-warning is-dismissible"><p>These fields were not saved because their value was not valid: <code><?php echo esc_html(implode(', ', $dropped)); ?></code></p></div>
		<?php endif; ?>
		<?php if ($intro) : ?>
			<p class="wseo-intro"><?php echo wp_kses_post($intro); ?></p>
		<?php endif; ?>
	<?php
}

function wholesale_seo_admin_footer()
{
	echo '</div>';
}

/**
 * Fields of a settings tab. Each field: type, label, and optionally
 * description, placeholder, options, rows.
 *
 * @return array
 */
function wholesale_seo_tab_fields($tab)
{
	switch ($tab) {
		case 'general':
			$fields = wholesale_seo_general_fields();
			break;
		case 'business':
			$fields = wholesale_seo_business_fields();
			break;
		default:
			$fields = array();
	}

	return apply_filters('wholesale_seo_tab_fields', $fields, $tab);
}

/**
 * Sanitize one settings value. Returns null when a non-empty value is invalid.
 *
 * @return mixed
 */
function wholesale_seo_sanitize_field(array $field, $raw)
{
	$type = $field['type'];

	if ('checkboxes' === $type) {
		$options = array_keys($field['options']);
		return array_values(array_intersect($options, array_map('strval', (array) $raw)));
	}

	$raw = is_scalar($raw) ? trim((string) $raw) : '';
	if ('' === $raw) {
		return '';
	}

	switch ($type) {
		case 'url':
		case 'image':
			$url = esc_url_raw($raw, array('http', 'https'));
			return $url ? $url : null;
		case 'email':
			return is_email($raw) ? sanitize_email($raw) : null;
		case 'select':
			return array_key_exists($raw, $field['options']) ? $raw : null;
		case 'time':
			return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $raw) ? $raw : null;
		case 'number':
			return is_numeric($raw) ? (string) max(0, (int) $raw) : null;
		case 'url_lines':
			$urls = array();
			foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
				$url = esc_url_raw(trim($line), array('http', 'https'));
				if ($url) {
					$urls[] = $url;
				}
			}
			return array_values(array_unique($urls));
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field($raw);
		default:
			return sanitize_text_field($raw);
	}
}

add_action('admin_post_wholesale_seo_save', 'wholesale_seo_handle_save');

function wholesale_seo_handle_save()
{
	$tab = isset($_POST['tab']) ? sanitize_key(wp_unslash($_POST['tab'])) : '';
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You are not allowed to change SEO settings.', 'litsign'), 403);
	}
	check_admin_referer('wholesale_seo_save_' . $tab);

	$fields = wholesale_seo_tab_fields($tab);
	if (!$fields) {
		wp_die('Unknown SEO tab.', 400);
	}

	$saved = array();
	$dropped = array();
	foreach ($fields as $key => $field) {
		if (!empty($field['readonly'])) {
			continue;
		}
		$raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
		$value = wholesale_seo_sanitize_field($field, $raw);

		if (null === $value) {
			$dropped[] = $key;
			continue;
		}

		// Settings that already live elsewhere (e.g. Settings > Storefront
		// Sign) are written back to that option, never stored twice.
		if (!empty($field['option'])) {
			update_option($field['option'], $value);
			continue;
		}

		if ('' !== $value && array() !== $value) {
			$saved[$key] = $value;
		}
	}

	// Keep an invalid field's previous value rather than wiping it.
	$previous = wholesale_seo_settings($tab);
	foreach ($dropped as $key) {
		if (isset($previous[$key])) {
			$saved[$key] = $previous[$key];
		}
	}

	$options = wholesale_seo_options();
	$name = 'wholesale_seo_' . $tab;
	update_option($name, $saved, !empty($options[$name]));
	update_option('wholesale_seo_changed_at', time(), false);

	$page = isset($_POST['page_slug']) ? sanitize_key(wp_unslash($_POST['page_slug'])) : 'wholesale-seo';
	$args = array('page' => $page, 'wseo_saved' => 1);
	if ($dropped) {
		$args['wseo_dropped'] = implode(',', $dropped);
	}
	wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
	exit;
}

/**
 * Opening tag and hidden fields of a settings form.
 */
function wholesale_seo_form_open($tab, $page_slug)
{
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="wseo-form">';
	echo '<input type="hidden" name="action" value="wholesale_seo_save">';
	echo '<input type="hidden" name="tab" value="' . esc_attr($tab) . '">';
	echo '<input type="hidden" name="page_slug" value="' . esc_attr($page_slug) . '">';
	wp_nonce_field('wholesale_seo_save_' . $tab);
}

/**
 * A settings row: label, input and help text. $value is the saved value.
 */
function wholesale_seo_field_row($key, array $field, $value)
{
	$id = 'wseo-' . $key;
	$type = $field['type'];
	$placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
	$attrs = ' id="' . esc_attr($id) . '" name="' . esc_attr($key) . '"' . ($placeholder ? ' placeholder="' . esc_attr($placeholder) . '"' : '');
	$attrs .= !empty($field['readonly']) ? ' readonly' : '';
	$attrs .= !empty($field['data']) ? ' ' . $field['data'] : '';
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($field['label']); ?></label></th>
		<td>
			<?php
			switch ($type) {
				case 'textarea':
				case 'lines':
					echo '<textarea class="large-text" rows="' . esc_attr(isset($field['rows']) ? $field['rows'] : 3) . '"' . $attrs . '>' . esc_textarea((string) $value) . '</textarea>';
					break;
				case 'url_lines':
					echo '<textarea class="large-text code" rows="' . esc_attr(isset($field['rows']) ? $field['rows'] : 4) . '"' . $attrs . '>' . esc_textarea(implode("\n", (array) $value)) . '</textarea>';
					break;
				case 'select':
					echo '<select' . $attrs . '>';
					foreach ($field['options'] as $option => $label) {
						echo '<option value="' . esc_attr($option) . '"' . selected((string) $value, (string) $option, false) . '>' . esc_html($label) . '</option>';
					}
					echo '</select>';
					break;
				case 'checkboxes':
					echo '<fieldset class="wseo-checks"><legend class="screen-reader-text">' . esc_html($field['label']) . '</legend>';
					foreach ($field['options'] as $option => $label) {
						echo '<label><input type="checkbox" name="' . esc_attr($key) . '[]" value="' . esc_attr($option) . '"' . checked(in_array($option, (array) $value, true), true, false) . '> ' . esc_html($label) . '</label> ';
					}
					echo '</fieldset>';
					break;
				case 'image':
					echo '<div class="wseo-image-field"><input type="url" class="regular-text code"' . $attrs . ' value="' . esc_attr((string) $value) . '">';
					echo ' <button type="button" class="button wseo-pick-image" data-target="' . esc_attr($id) . '">Choose image</button></div>';
					break;
				case 'time':
					echo '<input type="time"' . $attrs . ' value="' . esc_attr((string) $value) . '">';
					break;
				case 'number':
					echo '<input type="number" min="0" class="small-text"' . $attrs . ' value="' . esc_attr((string) $value) . '">';
					break;
				default:
					$input_type = in_array($type, array('url', 'email'), true) ? $type : 'text';
					echo '<input type="' . esc_attr($input_type) . '" class="regular-text' . ('url' === $type ? ' code' : '') . '"' . $attrs . ' value="' . esc_attr((string) $value) . '">';
			}
			if (!empty($field['description'])) {
				echo '<p class="description">' . wp_kses_post($field['description']) . '</p>';
			}
			?>
		</td>
	</tr>
	<?php
}

/**
 * Rows for a group of fields of a tab.
 */
function wholesale_seo_field_rows($tab, array $keys)
{
	$fields = wholesale_seo_tab_fields($tab);
	$saved = wholesale_seo_settings($tab);

	foreach ($keys as $key) {
		if (!isset($fields[$key])) {
			continue;
		}
		$field = $fields[$key];
		$value = !empty($field['option']) ? get_option($field['option'], '') : (isset($saved[$key]) ? $saved[$key] : '');
		wholesale_seo_field_row($key, $field, $value);
	}
}

/**
 * Edit link for a post or term (admin only).
 */
function wholesale_seo_edit_link($object_type, $object_id)
{
	if ('term' === $object_type) {
		$link = get_edit_term_link((int) $object_id, 'product_category');
	} else {
		$link = get_edit_post_link((int) $object_id, 'raw');
	}

	return $link ? $link : '';
}

/**
 * What the theme currently outputs for a post or term, worked out by running
 * the front-end SEO functions against a stand-in query (no HTTP request).
 *
 * @return array title, description, canonical, robots; empty on failure.
 */
function wholesale_seo_admin_effective($object_type, $object_id)
{
	global $wp_query, $wp_the_query, $post;

	$saved_query = $wp_query;
	$saved_main = $wp_the_query;
	$saved_post = $post;
	$result = array();

	try {
		$query = new WP_Query();
		if ('term' === $object_type) {
			$term = get_term((int) $object_id, 'product_category');
			if (!$term || is_wp_error($term)) {
				return array();
			}
			$query->parse_query(array('category_slug' => $term->slug));
		} else {
			$object = get_post((int) $object_id);
			if (!$object) {
				return array();
			}
			$query->parse_query('page' === $object->post_type ? array('page_id' => $object->ID) : array('p' => $object->ID, 'post_type' => $object->post_type));
			$query->queried_object = $object;
			$query->queried_object_id = (int) $object->ID;
			$query->posts = array($object);
			$query->post = $object;
			$query->post_count = 1;
			$post = $object;
			setup_postdata($object);
		}

		$wp_query = $query;
		$wp_the_query = $query;
		wholesale_seo_reset_context();

		$robots = array();
		foreach ((array) apply_filters('wp_robots', array()) as $directive => $value) {
			if (true === $value) {
				$robots[] = $directive;
			} elseif (is_string($value) && '' !== $value) {
				$robots[] = $directive . ':' . $value;
			}
		}

		$result = array(
			'title' => html_entity_decode(wp_strip_all_tags(wp_get_document_title()), ENT_QUOTES, 'UTF-8'),
			'description' => (string) wholesale_seo_description(),
			'canonical' => (string) wholesale_seo_url(),
			'robots' => implode(', ', $robots),
		);
	} catch (Throwable $error) {
		$result = array();
	} finally {
		$wp_query = $saved_query;
		$wp_the_query = $saved_main;
		$post = $saved_post;
		if ($saved_post instanceof WP_Post) {
			setup_postdata($saved_post);
		}
		wholesale_seo_reset_context();
	}

	return $result;
}

/**
 * Why the theme keeps a post or term out of search, or ''.
 */
function wholesale_seo_theme_noindex_reason($object_type, $object_id)
{
	if ('term' === $object_type) {
		$term = get_term((int) $object_id, 'product_category');
		if ($term && !is_wp_error($term) && in_array($term->slug, array('channel-letters', 'signs-letters'), true)) {
			return 'Redirected / canonicalized route (inc/seo.php)';
		}
		return '';
	}

	$object = get_post((int) $object_id);
	if (!$object) {
		return '';
	}

	if ('product' === $object->post_type) {
		$canonical = wholesale_seo_product_canonical_of($object->ID);
		return $canonical ? 'Canonical points to "' . wholesale_schema_text(get_the_title($canonical)) . '" (duplicate or partial variant)' : '';
	}

	if ('page' !== $object->post_type) {
		return '';
	}

	if (in_array($object->post_name, wholesale_seo_noindex_page_slugs(), true)) {
		return 'Private, ads-only or placeholder page (theme list)';
	}

	if (function_exists('wholesale_guide_registry')) {
		$registry = wholesale_guide_registry();
		$path = get_page_uri($object);
		if (isset($registry[$path]) && 'live' !== $registry[$path]['status']) {
			return 'Guide page still in review (data file status)';
		}
	}

	if (in_array((int) $object->ID, wholesale_seo_noindex_page_ids(), true)) {
		return 'Ads-only template or theme list';
	}

	return '';
}

/**
 * Character count badge.
 */
function wholesale_seo_length_badge($text, $limit)
{
	$length = mb_strlen((string) $text);
	$class = 0 === $length ? 'is-empty' : ($length > $limit ? 'is-long' : 'is-ok');
	return '<span class="wseo-len ' . esc_attr($class) . '" title="' . esc_attr($length . ' of ' . $limit . ' characters') . '">' . (int) $length . '</span>';
}
