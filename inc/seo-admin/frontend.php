<?php
/**
 * Front-end side of the SEO menu. Applies saved settings through filters in
 * the theme's existing SEO functions, so there is still exactly one title,
 * description, canonical, robots and schema tag per page.
 *
 * Every function returns its input unchanged when the matching setting is
 * empty. Reads only autoloaded options (no extra database queries), plus the
 * current page's own meta when the overrides index says it has some.
 *
 * Priority: per-page value > homepage setting / template > theme code.
 *
 * @package litsign
 */

defined('ABSPATH') || exit;

/**
 * What the current request is, for per-page values and templates.
 *
 * kind: home, page, post, product, product_category, location, industry, or
 * '' for pages whose keyword-targeted copy only a per-page value may replace
 * (channel letter landing pages, the builder, channel letter products).
 *
 * @return array object_type (post|term|''), object_id, kind, and the guide
 *               registry entry for location and industry pages.
 */
function retails_seo_context()
{
	if (isset($GLOBALS['retails_seo_context'])) {
		return $GLOBALS['retails_seo_context'];
	}

	$context = array('object_type' => '', 'object_id' => 0, 'kind' => '', 'guide' => array());
	$category_slug = get_query_var('category_slug');

	if ($category_slug) {
		$term = get_term_by('slug', sanitize_title($category_slug), 'product_category');
		if ($term && !is_wp_error($term)) {
			$context = array('object_type' => 'term', 'object_id' => (int) $term->term_id, 'kind' => 'product_category', 'guide' => array());
		}
	} elseif (is_singular()) {
		$post_id = (int) get_queried_object_id();
		$post_type = get_post_type($post_id);
		$context['object_type'] = 'post';
		$context['object_id'] = $post_id;

		if (is_front_page()) {
			$context['kind'] = 'home';
		} elseif ('product' === $post_type) {
			$context['kind'] = retails_seo_channel_letter_product($post_id) ? '' : 'product';
		} elseif ('post' === $post_type) {
			$context['kind'] = 'post';
		} elseif ('page' === $post_type) {
			$guide = function_exists('retails_guide_current') ? retails_guide_current() : array();
			$guide_type = isset($guide['type']) ? $guide['type'] : '';
			if (in_array($guide_type, array('state', 'city'), true)) {
				$context['kind'] = 'location';
				$context['guide'] = $guide;
			} elseif ('industry' === $guide_type) {
				$context['kind'] = 'industry';
				$context['guide'] = $guide;
			} elseif (!retails_is_channel_letters_landing() && !retails_is_channel_letters_page() && !is_page('channel-letter-builder')) {
				$context['kind'] = 'page';
			}
		}
	} elseif (is_front_page()) {
		$context['kind'] = 'home';
	}

	$GLOBALS['retails_seo_context'] = $context;
	return $context;
}

/**
 * Forget the cached context (used when the admin previews another page).
 */
function retails_seo_reset_context()
{
	unset($GLOBALS['retails_seo_context']);
}

/**
 * Per-page value for the current request, or ''. Meta is read only for
 * objects the overrides index lists.
 */
function retails_seo_object_value($key)
{
	$context = retails_seo_context();
	return $context['object_id'] ? retails_seo_stored_value($context['object_type'], $context['object_id'], $key) : '';
}

/**
 * A stored per-page value for any post or term, or ''.
 */
function retails_seo_stored_value($object_type, $object_id, $key)
{
	$index = retails_seo_settings('overrides');
	if (empty($index[$object_type][$object_id]) || !in_array($key, (array) $index[$object_type][$object_id], true)) {
		return '';
	}

	return (string) ('post' === $object_type
		? get_post_meta($object_id, '_retails_seo_' . $key, true)
		: get_term_meta($object_id, '_retails_seo_' . $key, true));
}

function retails_seo_brand()
{
	$brand = retails_seo_opt('general', 'brand');
	return '' !== $brand ? $brand : 'Storefront Sign Online';
}

function retails_seo_separator()
{
	$separator = retails_seo_opt('general', 'separator');
	return '' !== $separator ? $separator : '|';
}

/**
 * Template variables for a context.
 *
 * @return array
 */
function retails_seo_template_vars($context = null)
{
	$context = $context ? $context : retails_seo_context();
	$vars = array(
		'title' => '',
		'brand' => retails_seo_brand(),
		'category' => '',
		'price_from' => '',
		'state' => '',
		'city' => '',
		'sep' => retails_seo_separator(),
	);

	if ('term' === $context['object_type']) {
		$term = get_term($context['object_id'], 'product_category');
		if ($term && !is_wp_error($term)) {
			$vars['title'] = retails_schema_text($term->name);
			$vars['category'] = $vars['title'];
		}
		return $vars;
	}

	if ('post' !== $context['object_type'] || !$context['object_id']) {
		return $vars;
	}

	$post_id = $context['object_id'];
	$vars['title'] = retails_schema_text(get_the_title($post_id));

	if ('product' === get_post_type($post_id)) {
		$vars['title'] = retails_seo_product_name($post_id);
		$terms = get_the_terms($post_id, 'product_category');
		foreach ($terms && !is_wp_error($terms) ? $terms : array() as $term) {
			// A child category is more specific than its parent.
			if ('' === $vars['category'] || $term->parent) {
				$vars['category'] = retails_schema_text($term->name);
			}
		}
		$price = retails_seo_product_lowest_price($post_id);
		if ($price > 0) {
			$vars['price_from'] = '$' . preg_replace('/\.00$/', '', number_format($price, 2, '.', ','));
		}
	}

	$guide = $context['guide'];
	if (!empty($guide['state']) && function_exists('retails_guide_states')) {
		$states = retails_guide_states();
		$state = isset($states[$guide['state']]) ? $states[$guide['state']] : array();
		$vars['state'] = isset($state['name']) ? $state['name'] : '';
		if (!empty($guide['city']) && isset($state['cities'][$guide['city']]['name'])) {
			$vars['city'] = $state['cities'][$guide['city']]['name'];
		}
	}

	return $vars;
}

/**
 * Fill a title or description template. Separators and brackets left empty
 * by a missing variable are tidied away.
 */
function retails_seo_render_template($template, array $vars)
{
	$replace = array();
	foreach (array('title', 'brand', 'category', 'price_from', 'state', 'city', 'sep') as $key) {
		$replace['{' . $key . '}'] = isset($vars[$key]) ? (string) $vars[$key] : '';
	}

	$text = preg_replace('/\s+/', ' ', strtr((string) $template, $replace));
	$text = preg_replace('/\(\s*\)|\[\s*\]/', '', $text);

	$separator = $replace['{sep}'];
	if ('' !== trim($separator)) {
		$quoted = preg_quote(trim($separator), '/');
		$text = preg_replace('/(?:\s*' . $quoted . '\s*){2,}/', ' ' . trim($separator) . ' ', $text);
		$text = preg_replace('/^\s*' . $quoted . '\s*|\s*' . $quoted . '\s*$/', '', $text);
	}

	return trim(preg_replace('/\s+/', ' ', $text), " ,:;");
}

/**
 * Title or description from the SEO settings for the current request, or ''
 * to keep the theme's own.
 */
function retails_seo_resolved_text($field)
{
	$value = retails_seo_object_value($field);
	if ('' !== $value) {
		return $value;
	}

	$context = retails_seo_context();
	if ('home' === $context['kind']) {
		return (string) retails_seo_opt('general', 'home_' . $field);
	}

	$template = $context['kind'] ? (string) retails_seo_opt('general', 'tpl_' . $context['kind'] . '_' . $field) : '';

	return '' !== $template ? retails_seo_render_template($template, retails_seo_template_vars($context)) : '';
}

add_filter('document_title_parts', function ($parts) {
	if (retails_has_seo_plugin()) {
		return $parts;
	}

	$title = retails_seo_resolved_text('title');
	return '' !== $title ? array('title' => $title) : $parts;
}, 99);

add_filter('document_title_separator', function ($separator) {
	$saved = retails_seo_opt('general', 'separator');
	return '' !== $saved ? $saved : $separator;
});

add_filter('retails_seo_description_override', function ($description) {
	$saved = retails_seo_resolved_text('description');
	return '' !== $saved ? $saved : $description;
});

add_filter('retails_seo_canonical_override', function ($url) {
	$saved = retails_seo_object_value('canonical');
	return '' !== $saved ? $saved : $url;
});

add_filter('retails_seo_og_title', function ($title) {
	$saved = retails_seo_object_value('og_title');
	return '' !== $saved ? $saved : $title;
});

add_filter('retails_seo_og_description', function ($description) {
	$saved = retails_seo_object_value('og_description');
	return '' !== $saved ? $saved : $description;
});

/**
 * Share image: the page's own choice, else the default social image in place
 * of the logo the theme falls back to.
 */
add_filter('retails_seo_og_image', function ($image) {
	$saved = retails_seo_object_value('og_image');
	if ('' !== $saved) {
		return $saved;
	}

	$default = retails_seo_opt('general', 'og_image');
	if ('' === $default) {
		return $image;
	}

	$logo = get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';
	return '' === (string) $image || $image === $logo ? $default : $image;
});

/**
 * Per-page noindex and nofollow. They only ever add restrictions; pages the
 * theme keeps out of search stay out.
 */
add_filter('wp_robots', function ($robots) {
	if (retails_seo_object_value('noindex')) {
		unset($robots['index'], $robots['max-image-preview']);
		$robots['noindex'] = true;
		if (!isset($robots['nofollow'])) {
			$robots['follow'] = true;
		}
	}

	if (retails_seo_object_value('nofollow')) {
		unset($robots['follow']);
		$robots['nofollow'] = true;
	}

	return $robots;
}, 30);

/**
 * IDs with a per-page noindex or "exclude from sitemap", by object type.
 *
 * @return int[]
 */
function retails_seo_sitemap_excluded_ids($object_type)
{
	$ids = array();
	$index = retails_seo_settings('overrides');

	foreach (isset($index[$object_type]) ? (array) $index[$object_type] : array() as $id => $keys) {
		if (array_intersect(array('noindex', 'exclude_sitemap'), (array) $keys)) {
			$ids[] = (int) $id;
		}
	}

	return $ids;
}

add_filter('retails_seo_sitemap_excluded_post_ids', function ($ids) {
	return array_values(array_unique(array_merge((array) $ids, retails_seo_sitemap_excluded_ids('post'))));
});

add_filter('retails_seo_sitemap_excluded_product_ids', function ($ids) {
	$excluded = retails_seo_sitemap_excluded_ids('post');
	return $excluded ? array_values(array_unique(array_merge((array) $ids, $excluded))) : $ids;
});

add_filter('wp_sitemaps_posts_query_args', function ($args) {
	$excluded = retails_seo_sitemap_excluded_ids('post');
	if ($excluded) {
		$args['post__not_in'] = array_values(array_unique(array_merge(isset($args['post__not_in']) ? (array) $args['post__not_in'] : array(), $excluded)));
	}
	return $args;
}, 30);

add_filter('retails_seo_sitemap_excluded_categories', function ($slugs) {
	foreach (retails_seo_sitemap_excluded_ids('term') as $term_id) {
		$term = get_term($term_id, 'product_category');
		if ($term && !is_wp_error($term) && !in_array($term->slug, $slugs, true)) {
			$slugs[] = $term->slug;
		}
	}
	return $slugs;
});

/**
 * Social profile URLs (General & Titles), one per line.
 *
 * @return string[]
 */
function retails_seo_social_profiles()
{
	$profiles = retails_seo_opt('general', 'social_profiles');
	return is_array($profiles) ? array_values(array_filter($profiles)) : array();
}

/**
 * Whether the current page may describe the business as a place customers
 * visit: home, contact, about and the Washington location pages.
 */
function retails_seo_is_wa_page()
{
	if (isset($GLOBALS['retails_seo_preview_wa'])) {
		return (bool) $GLOBALS['retails_seo_preview_wa'];
	}

	if (is_front_page() || is_page(array('contact', 'about'))) {
		return true;
	}

	$guide = function_exists('retails_guide_current') ? retails_guide_current() : array();
	return isset($guide['state']) && 'washington' === $guide['state'];
}

/**
 * Apply Business & Schema settings to the Organization / LocalBusiness graph.
 */
function retails_seo_business_graph($graph)
{
	$business = retails_seo_settings('business');
	$social = retails_seo_social_profiles();
	if (!$business && !$social) {
		return $graph;
	}

	$org_key = null;
	$local_key = null;
	foreach ($graph as $key => $entity) {
		$id = isset($entity['@id']) ? (string) $entity['@id'] : '';
		if ('#organization' === substr($id, -13)) {
			$org_key = $key;
		} elseif ('#localbusiness' === substr($id, -14)) {
			$local_key = $key;
		}
	}
	if (null === $org_key) {
		return $graph;
	}

	$get = static function ($key) use ($business) {
		return isset($business[$key]) ? $business[$key] : '';
	};
	$org = $graph[$org_key];
	$local = null !== $local_key ? $graph[$local_key] : array();

	if ($get('org_type')) {
		$org['@type'] = $get('org_type');
	}
	// The Organization's name is the brand; the registered company is legalName.
	if ($get('legal_name')) {
		$org['legalName'] = $get('legal_name');
	}
	foreach (array('phone' => 'telephone', 'email' => 'email') as $setting => $property) {
		if ($get($setting)) {
			$org[$property] = $get($setting);
			if ($local) {
				$local[$property] = $get($setting);
			}
		}
	}
	if ($get('logo')) {
		$org['logo'] = $get('logo');
		if ($local) {
			$local['image'] = $get('logo');
		}
	}
	if ($social) {
		$org['sameAs'] = $social;
	}

	if ($local) {
		foreach (array('street' => 'streetAddress', 'locality' => 'addressLocality', 'region' => 'addressRegion', 'postal_code' => 'postalCode', 'country' => 'addressCountry') as $setting => $property) {
			if ($get($setting) && isset($local['address']) && is_array($local['address'])) {
				$local['address'][$property] = $get($setting);
			}
		}

		if ($get('hours_days') || $get('hours_opens') || $get('hours_closes')) {
			$current = isset($local['openingHoursSpecification'][0]) ? $local['openingHoursSpecification'][0] : array('@type' => 'OpeningHoursSpecification');
			$local['openingHoursSpecification'] = array(array(
				'@type' => 'OpeningHoursSpecification',
				'dayOfWeek' => $get('hours_days') ? array_values((array) $get('hours_days')) : (isset($current['dayOfWeek']) ? $current['dayOfWeek'] : array()),
				'opens' => $get('hours_opens') ? $get('hours_opens') : (isset($current['opens']) ? $current['opens'] : ''),
				'closes' => $get('hours_closes') ? $get('hours_closes') : (isset($current['closes']) ? $current['closes'] : ''),
			));
		}
	}

	// 'on': customers can visit, so LocalBusiness only where that matters;
	// 'off': no storefront, so Organization only. Both keep the address as
	// the company's postal address.
	$mode = $get('visit_mode');
	if (in_array($mode, array('on', 'off'), true) && $local) {
		if ('off' === $mode || !retails_seo_is_wa_page()) {
			if (!empty($local['address'])) {
				$org['address'] = $local['address'];
			}
			$local = array();
		}
	}

	$graph[$org_key] = $org;
	if (null !== $local_key) {
		if ($local) {
			$graph[$local_key] = $local;
		} else {
			unset($graph[$local_key]);
		}
	}

	return array_values($graph);
}
add_filter('retails_seo_organization_graph', 'retails_seo_business_graph');

/**
 * Where the business sells, one place per line: "United States" or a state.
 */
add_filter('retails_seo_area_served', function ($areas) {
	$lines = (string) retails_seo_opt('business', 'area_served');
	if ('' === trim($lines)) {
		return $areas;
	}

	$saved = array();
	foreach (preg_split('/\r\n|\r|\n/', $lines) as $line) {
		$line = trim($line);
		if ('' === $line) {
			continue;
		}
		$saved[] = in_array(strtolower($line), array('united states', 'usa', 'us', 'united states of america'), true)
			? array('@type' => 'Country', 'name' => 'United States')
			: array('@type' => 'State', 'name' => $line, 'containedInPlace' => array('@type' => 'Country', 'name' => 'United States'));
	}

	return $saved ? $saved : $areas;
});
