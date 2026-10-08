<?php
/**
 * SEO > General & Titles: brand, separator, homepage, social image and
 * profiles, and title / description templates per page type.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

/**
 * Page types that can have templates.
 *
 * @return array kind => label.
 */
function wholesale_seo_template_kinds()
{
	return array(
		'page' => 'Pages',
		'post' => 'Blog posts',
		'product' => 'Products',
		'product_category' => 'Product categories',
		'location' => 'Location pages',
		'industry' => 'Industry pages',
	);
}

function wholesale_seo_general_fields()
{
	$fields = array(
		'brand' => array(
			'type' => 'text',
			'label' => 'Brand name',
			'placeholder' => 'Storefront Sign Online',
			'description' => 'Used for <code>{brand}</code> in templates. Empty uses "Storefront Sign Online". It does not rewrite titles the theme already sets.',
		),
		'separator' => array(
			'type' => 'select',
			'label' => 'Title separator',
			'options' => array('' => 'Theme default ( | )', '|' => '|', '–' => '–', '-' => '-', '·' => '·', '•' => '•', '»' => '»'),
			'description' => 'Used for <code>{sep}</code> in templates and by WordPress on pages without a theme title.',
		),
		'home_title' => array(
			'type' => 'text',
			'label' => 'Homepage title',
			'data' => 'data-wseo-count="60"',
		),
		'home_description' => array(
			'type' => 'textarea',
			'label' => 'Homepage meta description',
			'rows' => 2,
			'data' => 'data-wseo-count="160"',
		),
		'og_image' => array(
			'type' => 'image',
			'label' => 'Default social image',
			'description' => 'Shown when a page shares without its own image (instead of the logo). 1200 × 630 px works best.',
		),
		'social_profiles' => array(
			'type' => 'url_lines',
			'label' => 'Social profile URLs',
			'placeholder' => "https://www.facebook.com/...\nhttps://www.instagram.com/...",
			'description' => 'One per line. Added to the Organization schema as <code>sameAs</code>. Only add profiles you own.',
		),
	);

	foreach (wholesale_seo_template_kinds() as $kind => $label) {
		$fields['tpl_' . $kind . '_title'] = array(
			'type' => 'text',
			'label' => $label . ': title',
			'placeholder' => 'Empty: keep the titles the theme sets now',
			'data' => 'data-wseo-tpl="title" data-wseo-kind="' . esc_attr($kind) . '"',
		);
		$fields['tpl_' . $kind . '_description'] = array(
			'type' => 'textarea',
			'label' => $label . ': description',
			'rows' => 2,
			'placeholder' => 'Empty: keep the descriptions the theme sets now',
			'data' => 'data-wseo-tpl="description" data-wseo-kind="' . esc_attr($kind) . '"',
		);
	}

	return $fields;
}

/**
 * A real page of each type to preview templates on.
 *
 * @return array kind => array(object_type, object_id, context).
 */
function wholesale_seo_template_samples()
{
	$samples = array();

	$about = get_page_by_path('about');
	if ($about) {
		$samples['page'] = array('post', (int) $about->ID, array());
	}

	$posts = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids'));
	if ($posts) {
		$samples['post'] = array('post', (int) $posts[0], array());
	}

	$product = wholesale_seo_product('13oz-vinyl-banner');
	if ($product) {
		$samples['product'] = array('post', (int) $product->ID, array());
	}

	$term = get_term_by('slug', 'banners', 'product_category');
	if ($term && !is_wp_error($term)) {
		$samples['product_category'] = array('term', (int) $term->term_id, array());
	}

	if (function_exists('wholesale_guide_registry')) {
		foreach (wholesale_guide_registry() as $path => $entry) {
			$kind = isset($entry['type']) ? ('industry' === $entry['type'] ? 'industry' : 'location') : '';
			if (!$kind || isset($samples[$kind])) {
				continue;
			}
			$page = get_page_by_path($path);
			if ($page) {
				$samples[$kind] = array('post', (int) $page->ID, $entry);
			}
		}
	}

	return $samples;
}

function wholesale_seo_general_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	wholesale_seo_admin_header('wholesale-seo-general', 'Every field is optional. An empty field keeps what the theme prints today. Order of use: a page&rsquo;s own SEO box &rarr; the template or homepage field here &rarr; the theme&rsquo;s built-in text.');
	wholesale_seo_form_open('general', 'wholesale-seo-general');

	$front_id = (int) get_option('page_on_front');
	$home_now = $front_id ? wholesale_seo_admin_effective('post', $front_id) : array();
	$fields = wholesale_seo_general_fields();
	$saved = wholesale_seo_settings('general');
	?>
	<h2>Site</h2>
	<table class="form-table" role="presentation">
		<?php wholesale_seo_field_rows('general', array('brand', 'separator')); ?>
	</table>

	<h2>Homepage</h2>
	<?php if ($home_now) : ?>
		<p class="description">The homepage shows now: <strong><?php echo esc_html($home_now['title']); ?></strong><br><?php echo esc_html($home_now['description']); ?></p>
	<?php endif; ?>
	<table class="form-table" role="presentation">
		<?php
		$fields['home_title']['placeholder'] = $home_now ? $home_now['title'] : '';
		$fields['home_description']['placeholder'] = $home_now ? $home_now['description'] : '';
		foreach (array('home_title', 'home_description') as $key) {
			wholesale_seo_field_row($key, $fields[$key], isset($saved[$key]) ? $saved[$key] : '');
		}
		?>
	</table>

	<h2>Social</h2>
	<table class="form-table" role="presentation">
		<?php wholesale_seo_field_rows('general', array('og_image', 'social_profiles')); ?>
	</table>

	<h2>Title &amp; description templates</h2>
	<p>Variables: <code>{title}</code> page or product name, <code>{brand}</code>, <code>{category}</code> product category, <code>{price_from}</code> lowest product price, <code>{state}</code>, <code>{city}</code>, <code>{sep}</code> separator. A variable with no value on a page is left out, with its separator.</p>
	<p class="description">Templates never replace the hand-written keyword titles of the channel letter pages, the builder or the channel letter products; use those pages&rsquo; own SEO box instead.</p>
	<?php
	$samples = wholesale_seo_template_samples();
	foreach (wholesale_seo_template_kinds() as $kind => $label) :
		$sample = isset($samples[$kind]) ? $samples[$kind] : null;
		$vars = array();
		$now = array();
		$sample_label = '';
		if ($sample) {
			$context = array('object_type' => $sample[0], 'object_id' => $sample[1], 'kind' => $kind, 'guide' => $sample[2]);
			$vars = wholesale_seo_template_vars($context);
			$now = wholesale_seo_admin_effective($sample[0], $sample[1]);
			$sample_label = 'term' === $sample[0] ? $vars['title'] : wholesale_schema_text(get_the_title($sample[1]));
		}
		?>
		<div class="wseo-card wseo-template" data-wseo-vars="<?php echo esc_attr(wp_json_encode($vars)); ?>" data-wseo-kind="<?php echo esc_attr($kind); ?>">
			<h3><?php echo esc_html($label); ?></h3>
			<table class="form-table" role="presentation">
				<?php wholesale_seo_field_rows('general', array('tpl_' . $kind . '_title', 'tpl_' . $kind . '_description')); ?>
			</table>
			<?php if ($sample) : ?>
				<div class="wseo-preview">
					<p class="wseo-preview-label">Preview on &ldquo;<?php echo esc_html($sample_label); ?>&rdquo;</p>
					<div class="wseo-serp">
						<div class="wseo-serp-title" data-wseo-out="title"><?php echo esc_html(isset($now['title']) ? $now['title'] : ''); ?></div>
						<div class="wseo-serp-url"><?php echo esc_html(isset($now['canonical']) ? $now['canonical'] : ''); ?></div>
						<div class="wseo-serp-desc" data-wseo-out="description"><?php echo esc_html(isset($now['description']) ? $now['description'] : ''); ?></div>
					</div>
					<p class="description">Shows the template result while you type; with the template empty it shows what the page prints now.</p>
					<script type="application/json" class="wseo-now"><?php echo wp_json_encode(array('title' => isset($now['title']) ? $now['title'] : '', 'description' => isset($now['description']) ? $now['description'] : '')); ?></script>
				</div>
			<?php else : ?>
				<p class="description">No published page of this type to preview on yet.</p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<?php submit_button('Save settings'); ?>
	</form>
	<?php
	wholesale_seo_admin_footer();
}
