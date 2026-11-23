<?php
/**
 * Developer credit page (/developer/).
 *
 * Names the site's developer and the work done on the site, rendered by
 * page-developer.php. The page is meant to be shared by direct link only:
 * it is noindexed, left out of the sitemaps and site search, and no menu or
 * footer links to it. Delete the WordPress page to take it down; it is
 * created once and not re-created after that.
 *
 * @package litsign
 */

define('WHOLESALE_DEVELOPER_PAGE_PATH', 'developer');

/**
 * Who the page credits. Change the details here.
 *
 * @return array
 */
function wholesale_developer_profile()
{
	return array(
		'name' => 'Md Ikram',
		'role' => 'Website design, development & maintenance',
	);
}

/**
 * The published developer page, or null.
 *
 * @return WP_Post|null
 */
function wholesale_developer_page()
{
	$page = get_page_by_path(WHOLESALE_DEVELOPER_PAGE_PATH, OBJECT, 'page');
	return $page && 'publish' === $page->post_status ? $page : null;
}

function wholesale_is_developer_page()
{
	$page = is_page() ? wholesale_developer_page() : null;
	return $page && (int) $page->ID === get_queried_object_id();
}

/**
 * Create the WordPress page on the next admin page load. Runs once; a page
 * deleted later stays deleted.
 */
function wholesale_developer_create_page()
{
	if (!current_user_can('manage_options') || get_option('wholesale_developer_page_created')) {
		return;
	}

	if (!get_page_by_path(WHOLESALE_DEVELOPER_PAGE_PATH, OBJECT, 'page')) {
		wp_insert_post(array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Website Developer',
			'post_name' => WHOLESALE_DEVELOPER_PAGE_PATH,
			'post_content' => '',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
		));
	}

	update_option('wholesale_developer_page_created', 1, false);
}
add_action('admin_init', 'wholesale_developer_create_page', 20);

function wholesale_developer_template($template)
{
	if (wholesale_is_developer_page()) {
		$located = locate_template('page-developer.php');
		if ($located) {
			return $located;
		}
	}

	return $template;
}
add_filter('template_include', 'wholesale_developer_template', 20);

function wholesale_developer_robots($robots)
{
	if (wholesale_is_developer_page()) {
		unset($robots['index'], $robots['max-image-preview']);
		$robots['noindex'] = true;
		$robots['follow'] = true;
	}

	return $robots;
}
add_filter('wp_robots', 'wholesale_developer_robots', 20);

// Sitemaps skip every page in this list.
add_filter('wholesale_seo_noindex_page_ids', function ($ids) {
	$page = wholesale_developer_page();
	return $page ? array_values(array_unique(array_merge($ids, array((int) $page->ID)))) : $ids;
});

/**
 * Keep the page out of the search page and the header's live search.
 */
function wholesale_developer_exclude_from_search($query)
{
	if (is_admin() || '' === (string) $query->get('s')) {
		return;
	}

	$page = wholesale_developer_page();
	if ($page) {
		$query->set('post__not_in', array_merge((array) $query->get('post__not_in'), array((int) $page->ID)));
	}
}
add_action('pre_get_posts', 'wholesale_developer_exclude_from_search');

/**
 * Title and description for the theme's SEO head, which reads _seo_title and
 * _seo_description for pages.
 */
function wholesale_developer_seo_meta($value, $object_id, $meta_key, $single)
{
	if ('_seo_title' !== $meta_key && '_seo_description' !== $meta_key) {
		return $value;
	}

	$post = get_post($object_id);
	if (!$post || 'page' !== $post->post_type || WHOLESALE_DEVELOPER_PAGE_PATH !== get_page_uri($post)) {
		return $value;
	}

	$profile = wholesale_developer_profile();
	$text = '_seo_title' === $meta_key
		? 'Website Developer: ' . $profile['name'] . ' | Storefront Sign Online'
		: $profile['name'] . ' designed, built and maintains the Storefront Sign Online website: its custom WordPress theme, sign builder, price calculators, SEO and speed work.';

	return $single ? $text : array($text);
}
add_filter('get_post_metadata', 'wholesale_developer_seo_meta', 10, 4);

function wholesale_developer_styles()
{
	if (!wholesale_is_developer_page()) {
		return;
	}

	$uri = get_template_directory_uri();
	$dir = get_template_directory();
	wp_enqueue_style('wholesale-seo-pages', $uri . '/css/seo-pages.css', array('custom-style'), (string) filemtime($dir . '/css/seo-pages.css'));
	wp_enqueue_style('wholesale-guides', $uri . '/css/guides.css', array('wholesale-seo-pages'), (string) filemtime($dir . '/css/guides.css'));
	wp_enqueue_style('wholesale-developer', $uri . '/css/developer.css', array('wholesale-guides'), (string) filemtime($dir . '/css/developer.css'));
}
add_action('wp_enqueue_scripts', 'wholesale_developer_styles', 20);
