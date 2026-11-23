<?php
/**
 * Per-page SEO box on pages, posts and products, and the same fields on the
 * product category edit screen. Values are stored as _wholesale_seo_* meta.
 *
 * Every field is empty by default; empty keeps what the theme prints now,
 * which the box shows as grey hints and in the search preview. Older
 * _seo_title / _seo_description values (and the guide pages that feed them)
 * keep working because they are part of that theme output.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

/**
 * @return string[]
 */
function wholesale_seo_metabox_post_types()
{
	return array('page', 'post', 'product');
}

add_action('add_meta_boxes', function ($post_type) {
	if (!current_user_can('manage_options') || !in_array($post_type, wholesale_seo_metabox_post_types(), true)) {
		return;
	}
	add_meta_box('wholesale-seo', 'SEO', 'wholesale_seo_metabox_render', $post_type, 'normal', 'high');
});

/**
 * Stored per-page values for a post or term.
 *
 * @return array
 */
function wholesale_seo_object_values($object_type, $object_id)
{
	$values = array();
	foreach (array_keys(wholesale_seo_object_fields()) as $key) {
		$values[$key] = (string) ('term' === $object_type
			? get_term_meta($object_id, '_wholesale_seo_' . $key, true)
			: get_post_meta($object_id, '_wholesale_seo_' . $key, true));
	}
	return $values;
}

/**
 * The fields, shared by the post box and the category screen.
 */
function wholesale_seo_object_form($object_type, $object_id, $permalink)
{
	$values = wholesale_seo_object_values($object_type, $object_id);
	$now = $object_id ? wholesale_seo_admin_effective($object_type, $object_id) : array();
	$now = wp_parse_args($now, array('title' => '', 'description' => '', 'canonical' => $permalink, 'robots' => ''));
	$theme_noindex = $object_id ? wholesale_seo_theme_noindex_reason($object_type, $object_id) : '';
	$protected = function_exists('wholesale_seo_protected_url_match') && $permalink ? wholesale_seo_protected_url_match($permalink) : '';

	wp_nonce_field('wholesale_seo_object', 'wholesale_seo_object_nonce');
	?>
	<div class="wseo-box" data-wseo-box>
		<?php if ($protected) : ?>
			<p class="wseo-flag wseo-flag--warn">Protected Google Ads URL. Changing its slug, noindex or status can break live ads.</p>
		<?php endif; ?>

		<div class="wseo-serp" aria-live="polite">
			<div class="wseo-serp-url"><?php echo esc_html($values['canonical'] ? $values['canonical'] : $now['canonical']); ?></div>
			<div class="wseo-serp-title" data-wseo-serp="title"><?php echo esc_html($values['title'] ? $values['title'] : $now['title']); ?></div>
			<div class="wseo-serp-desc" data-wseo-serp="description"><?php echo esc_html($values['description'] ? $values['description'] : $now['description']); ?></div>
		</div>
		<p class="description">Search preview. Grey hints show what the page prints now; type to replace it on this page only.</p>

		<div class="wseo-grid">
			<p class="wseo-field">
				<label for="wseo-title"><strong>SEO title</strong> <span class="wseo-counter" data-wseo-counter="wseo-title" data-limit="60"></span></label>
				<input type="text" id="wseo-title" name="wholesale_seo[title]" class="widefat" value="<?php echo esc_attr($values['title']); ?>" placeholder="<?php echo esc_attr($now['title']); ?>" data-wseo-input="title">
			</p>
			<p class="wseo-field">
				<label for="wseo-description"><strong>Meta description</strong> <span class="wseo-counter" data-wseo-counter="wseo-description" data-limit="160"></span></label>
				<textarea id="wseo-description" name="wholesale_seo[description]" class="widefat" rows="3" placeholder="<?php echo esc_attr($now['description']); ?>" data-wseo-input="description"><?php echo esc_textarea($values['description']); ?></textarea>
			</p>
			<p class="wseo-field">
				<label for="wseo-focus"><strong>Focus keyword</strong></label>
				<input type="text" id="wseo-focus" name="wholesale_seo[focus_keyword]" class="widefat" value="<?php echo esc_attr($values['focus_keyword']); ?>" data-wseo-input="focus">
				<span class="wseo-checks-out" data-wseo-focus-checks data-slug="<?php echo esc_attr($permalink); ?>"></span>
				<span class="description">Not printed on the page; used for the checks above and the Dashboard.</span>
			</p>
			<p class="wseo-field">
				<label for="wseo-canonical"><strong>Canonical URL</strong></label>
				<input type="url" id="wseo-canonical" name="wholesale_seo[canonical]" class="widefat code" value="<?php echo esc_attr($values['canonical']); ?>" placeholder="<?php echo esc_attr($now['canonical']); ?>">
				<span class="description">Leave empty unless this page duplicates another one.</span>
			</p>
		</div>

		<fieldset class="wseo-flags">
			<legend><strong>Search engines</strong></legend>
			<?php if ($theme_noindex) : ?>
				<p class="wseo-flag">Noindex by theme: <?php echo esc_html($theme_noindex); ?>. That cannot be undone here.</p>
			<?php endif; ?>
			<label><input type="checkbox" name="wholesale_seo[noindex]" value="1" <?php checked('1', $values['noindex']); ?> data-wseo-protect="noindex"> Noindex (keep out of search results)</label><br>
			<label><input type="checkbox" name="wholesale_seo[nofollow]" value="1" <?php checked('1', $values['nofollow']); ?>> Nofollow (don&rsquo;t follow this page&rsquo;s links)</label><br>
			<label><input type="checkbox" name="wholesale_seo[exclude_sitemap]" value="1" <?php checked('1', $values['exclude_sitemap']); ?>> Leave out of the sitemaps</label>
			<?php if ($now['robots']) : ?>
				<p class="description">Robots now: <code><?php echo esc_html($now['robots']); ?></code></p>
			<?php endif; ?>
		</fieldset>

		<details class="wseo-social"<?php echo $values['og_title'] || $values['og_description'] || $values['og_image'] ? ' open' : ''; ?>>
			<summary><strong>Social share (Facebook, LinkedIn, X)</strong></summary>
			<p class="wseo-field">
				<label for="wseo-og-title">Share title</label>
				<input type="text" id="wseo-og-title" name="wholesale_seo[og_title]" class="widefat" value="<?php echo esc_attr($values['og_title']); ?>" placeholder="Same as the SEO title">
			</p>
			<p class="wseo-field">
				<label for="wseo-og-description">Share description</label>
				<textarea id="wseo-og-description" name="wholesale_seo[og_description]" class="widefat" rows="2" placeholder="Same as the meta description"><?php echo esc_textarea($values['og_description']); ?></textarea>
			</p>
			<p class="wseo-field">
				<label for="wseo-og-image">Share image</label>
				<span class="wseo-image-field">
					<input type="url" id="wseo-og-image" name="wholesale_seo[og_image]" class="widefat code" value="<?php echo esc_attr($values['og_image']); ?>" placeholder="Featured image, else the default social image">
					<button type="button" class="button wseo-pick-image" data-target="wseo-og-image">Choose image</button>
				</span>
			</p>
		</details>
	</div>
	<?php
}

function wholesale_seo_metabox_render($post)
{
	wholesale_seo_object_form('post', 'auto-draft' === $post->post_status ? 0 : (int) $post->ID, get_permalink($post));
}

/**
 * Values from the submitted form, or null when the form isn't ours.
 *
 * @return array|null
 */
function wholesale_seo_submitted_object_values()
{
	if (!isset($_POST['wholesale_seo_object_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['wholesale_seo_object_nonce'])), 'wholesale_seo_object')) {
		return null;
	}

	$submitted = isset($_POST['wholesale_seo']) && is_array($_POST['wholesale_seo']) ? wp_unslash($_POST['wholesale_seo']) : array();
	$values = array();
	// Checkboxes are absent when unticked, so every field is written.
	foreach (array_keys(wholesale_seo_object_fields()) as $key) {
		$values[$key] = isset($submitted[$key]) ? $submitted[$key] : '';
	}
	return $values;
}

add_action('save_post', function ($post_id, $post) {
	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
		return;
	}
	if (!in_array($post->post_type, wholesale_seo_metabox_post_types(), true) || !current_user_can('manage_options') || !current_user_can('edit_post', $post_id)) {
		return;
	}

	$values = wholesale_seo_submitted_object_values();
	if (null !== $values) {
		wholesale_seo_save_object_values('post', $post_id, $values);
	}
}, 10, 2);

add_action('product_category_edit_form_fields', function ($term) {
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<tr class="form-field">
		<th scope="row">SEO</th>
		<td><?php wholesale_seo_object_form('term', (int) $term->term_id, wholesale_category_url($term->slug)); ?></td>
	</tr>
	<?php
});

add_action('edited_product_category', function ($term_id) {
	if (!current_user_can('manage_options')) {
		return;
	}

	$values = wholesale_seo_submitted_object_values();
	if (null !== $values) {
		wholesale_seo_save_object_values('term', $term_id, $values);
	}
});
