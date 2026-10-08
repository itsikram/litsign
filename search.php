<?php

/**
 * The template for displaying search results
 *
 * Responsive results grid with: breadcrumb, on-page search form, type filter,
 * sort options, highlighted matches, product prices (when WooCommerce is active),
 * pagination, a helpful "no results" state and a contact band.
 *
 * Tip: the grid looks best with a multiple of 12 results per page. Add this to
 * functions.php (it can't live in a template, the query has already run by then):
 *
 *   add_action('pre_get_posts', function ($q) {
 *       if (!is_admin() && $q->is_main_query() && $q->is_search()) {
 *           $q->set('posts_per_page', 12);
 *       }
 *   });
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package litsign
 */

get_header();

global $wp_query;

/* ---------------------------------------------------------------------
 * Settings
 * ------------------------------------------------------------------- */
$phone_display = '866-436-2101';
$phone_href    = 'tel:+18664362101';
$sms_href      = 'sms:+18664362101';
$popular       = array('Channel letters', '13oz vinyl banner', 'Flags', 'Adhesive vinyl', 'Trade show');

/* ---------------------------------------------------------------------
 * Query state
 * ------------------------------------------------------------------- */
$q      = trim(get_search_query(false));
$found  = (int) $wp_query->found_posts;
$paged  = max(1, (int) get_query_var('paged'));
$per    = max(1, (int) $wp_query->get('posts_per_page'));
$from   = $found ? (($paged - 1) * $per) + 1 : 0;
$to     = $found ? $from + (int) $wp_query->post_count - 1 : 0;

$req_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
if ($req_type && !post_type_exists($req_type)) {
	$req_type = '';
}
$req_order = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';
if (!in_array($req_order, array('date', 'title'), true)) {
	$req_order = '';
}

// Builds a search URL from the current state plus overrides (always resets to page 1).
$build = function ($overrides = array()) use ($q, $req_type, $req_order) {
	$args = array_merge(array('s' => $q, 'post_type' => $req_type, 'orderby' => $req_order), $overrides);
	if ('title' === $args['orderby']) {
		$args['order'] = 'asc';
	} elseif ('date' === $args['orderby']) {
		$args['order'] = 'desc';
	}
	return esc_url(add_query_arg(array_filter($args), home_url('/')));
};

$type_tabs = array('' => 'All results');
foreach (array('product' => 'Products', 'page' => 'Pages', 'post' => 'Articles') as $slug => $label) {
	if (post_type_exists($slug)) {
		$type_tabs[$slug] = $label;
	}
}
$sort_tabs = array('' => 'Best match', 'date' => 'Newest', 'title' => 'A to Z');

// Highlights the searched words inside a piece of text. Output is escaped.
$words = array();
foreach (preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) as $w) {
	if (mb_strlen($w) >= 2) {
		$words[] = preg_quote($w, '/');
	}
}
$pattern = $words ? '/(' . implode('|', array_slice(array_unique($words), 0, 6)) . ')/iu' : '';
$highlight = function ($text) use ($pattern) {
	if (!$pattern) {
		return esc_html($text);
	}
	$parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	if (false === $parts) {
		return esc_html($text);
	}
	$out = '';
	foreach ($parts as $i => $part) {
		$out .= ($i % 2) ? '<mark>' . esc_html($part) . '</mark>' : esc_html($part);
	}
	return $out;
};

$icon = static function ($name, $size = 20) {
	$s = 'fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"';
	$paths = array(
		'search'  => '<circle cx="11" cy="11" r="7" ' . $s . ' stroke-width="2.2"/><path ' . $s . ' stroke-width="2.2" d="m20 20-3.5-3.5"/>',
		'close'   => '<path ' . $s . ' stroke-width="2.2" d="M6 6l12 12M18 6 6 18"/>',
		'chevron' => '<path ' . $s . ' stroke-width="2.4" d="m9 6 6 6-6 6"/>',
		'image'   => '<rect x="3" y="4" width="18" height="16" rx="2" ' . $s . ' stroke-width="1.8"/><circle cx="9" cy="10" r="1.6" ' . $s . ' stroke-width="1.8"/><path ' . $s . ' stroke-width="1.8" d="m21 16-5-5-9 9"/>',
		'phone'   => '<path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
		'sms'     => '<path ' . $s . ' stroke-width="2" d="M4 5h16v11H9l-5 4z"/>',
	);
	return '<svg class="srp-svg" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
};

?>

<style>
	/* ===================================================================
	 * Search results page (srp-*). Self-contained: does not rely on theme CSS.
	 * =================================================================== */
	.srp {
		--srp-blue: #1fa8de;
		--srp-blue-hover: #1790c0;
		--srp-blue-text: #0e7aa6;
		--srp-blue-tint: #e6f6fc;
		--srp-navy: #0d2236;
		--srp-ink: #17222e;
		--srp-muted: #5c6b7a;
		--srp-line: #e2e8ee;
		--srp-soft: #f3f6f9;

		color: var(--srp-ink);
		font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
		font-size: 15px;
		line-height: 1.5;
		background: #fff;
	}

	.srp *,
	.srp *::before,
	.srp *::after {
		box-sizing: border-box;
	}

	.srp a {
		color: inherit;
		text-decoration: none;
	}

	.srp ul,
	.srp ol {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	.srp h1,
	.srp h2,
	.srp h3,
	.srp p {
		margin: 0;
	}

	.srp button {
		font-family: inherit;
		font-size: inherit;
		margin: 0;
	}

	.srp :focus-visible {
		outline: 3px solid #7ccdee;
		outline-offset: 2px;
	}

	.srp [hidden] {
		display: none !important;
	}

	.srp-container {
		width: 100%;
		max-width: 1320px;
		margin: 0 auto;
		padding-left: 12px;
		padding-right: 12px;
	}

	.srp-svg {
		display: block;
		flex: none;
	}

	.srp-vh {
		position: absolute !important;
		width: 1px;
		height: 1px;
		margin: -1px;
		overflow: hidden;
		clip: rect(0 0 0 0);
		white-space: nowrap;
		border: 0;
	}

	.srp mark {
		padding: 0 2px;
		border-radius: 3px;
		background: #cdeefa;
		color: inherit;
	}

	/* ---------- Hero ---------- */
	.srp-hero {
		padding: 28px 0 34px;
		background: linear-gradient(180deg, var(--srp-blue-tint) 0%, #f7fcfe 100%);
		border-bottom: 1px solid var(--srp-line);
	}

	.srp-crumbs {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 6px;
		margin-bottom: 14px;
		color: var(--srp-muted);
		font-size: 14px;
	}

	.srp-crumbs a:hover {
		color: var(--srp-blue-text);
		text-decoration: underline;
	}

	.srp-crumbs [aria-current] {
		color: var(--srp-ink);
		font-weight: 600;
	}

	.srp-title {
		font-size: clamp(26px, 3.4vw, 38px);
		line-height: 1.15;
		font-weight: 800;
		letter-spacing: -.01em;
		overflow-wrap: anywhere;
	}

	.srp-sub {
		margin-top: 8px;
		color: var(--srp-muted);
		font-size: 16px;
	}

	.srp-form {
		position: relative;
		max-width: 720px;
		margin-top: 22px;
	}

	.srp-field {
		display: flex;
		align-items: center;
		gap: 8px;
		height: 56px;
		padding: 0 7px 0 20px;
		border: 1.5px solid #c3d5e0;
		border-radius: 999px;
		background: #fff;
		color: var(--srp-muted);
		box-shadow: 0 4px 14px rgba(13, 34, 54, .06);
		transition: border-color .15s, box-shadow .15s;
	}

	.srp-field:focus-within {
		border-color: var(--srp-blue);
		box-shadow: 0 0 0 4px rgba(31, 168, 222, .18);
	}

	.srp-input {
		flex: 1;
		min-width: 0;
		height: 100%;
		padding: 0;
		border: 0;
		outline: 0;
		box-shadow: none;
		background: transparent;
		color: var(--srp-ink);
		font: inherit;
		font-size: 16px;
		-webkit-appearance: none;
		appearance: none;
	}

	.srp-input::placeholder {
		color: #7d8b99;
		opacity: 1;
	}

	.srp-input::-webkit-search-cancel-button,
	.srp-input::-webkit-search-decoration {
		-webkit-appearance: none;
		display: none;
	}

	.srp-submit {
		flex: none;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		height: 42px;
		padding: 0 24px;
		border: 0;
		border-radius: 999px;
		background: var(--srp-blue);
		color: #fff;
		font-weight: 700;
		cursor: pointer;
		transition: background-color .15s;
	}

	.srp-submit:hover {
		background: var(--srp-blue-hover);
	}

	.srp-popular {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 8px;
		margin-top: 16px;
	}

	.srp-popular__label {
		margin-right: 4px;
		color: var(--srp-muted);
		font-size: 14px;
		font-weight: 600;
	}

	.srp-chip {
		display: inline-flex;
		align-items: center;
		min-height: 36px;
		padding: 0 14px;
		border: 1px solid #cfe3ee;
		border-radius: 999px;
		background: #fff;
		color: var(--srp-ink);
		font-size: 14px;
		font-weight: 600;
		transition: background-color .15s, border-color .15s, color .15s;
	}

	.srp-chip:hover {
		border-color: var(--srp-blue);
		background: var(--srp-blue-tint);
		color: var(--srp-blue-text);
	}

	/* ---------- Toolbar ---------- */
	.srp-results {
		padding: 26px 0 56px;
	}

	.srp-toolbar {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 14px 24px;
		margin-bottom: 22px;
		padding-bottom: 18px;
		border-bottom: 1px solid var(--srp-line);
	}

	.srp-tabs {
		display: flex;
		align-items: center;
		gap: 8px;
		min-width: 0;
		overflow-x: auto;
		scrollbar-width: none;
	}

	.srp-tabs::-webkit-scrollbar {
		display: none;
	}

	.srp-tab {
		flex: none;
		display: inline-flex;
		align-items: center;
		min-height: 40px;
		padding: 0 16px;
		border: 1px solid var(--srp-line);
		border-radius: 999px;
		background: #fff;
		color: var(--srp-ink);
		font-weight: 600;
		white-space: nowrap;
		transition: background-color .15s, border-color .15s, color .15s;
	}

	.srp-tab:hover {
		border-color: var(--srp-blue);
		color: var(--srp-blue-text);
	}

	.srp-tab[aria-current="true"] {
		border-color: var(--srp-blue);
		background: var(--srp-blue);
		color: #fff;
	}

	.srp-sort {
		display: flex;
		align-items: center;
		gap: 6px;
	}

	.srp-sort__label {
		margin-right: 4px;
		color: var(--srp-muted);
		font-weight: 600;
	}

	.srp-sort a {
		padding: 8px 12px;
		border-radius: 8px;
		color: var(--srp-muted);
		font-weight: 600;
		transition: background-color .15s, color .15s;
	}

	.srp-sort a:hover {
		background: var(--srp-soft);
		color: var(--srp-ink);
	}

	.srp-sort a[aria-current="true"] {
		background: var(--srp-blue-tint);
		color: var(--srp-blue-text);
	}

	.srp-count {
		margin-bottom: 18px;
		color: var(--srp-muted);
	}

	.srp-count strong {
		color: var(--srp-ink);
	}

	/* ---------- Results grid ---------- */
	.srp-grid {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 22px;
	}

	.srp-card {
		position: relative;
		display: flex;
		flex-direction: column;
		height: 100%;
		overflow: hidden;
		border: 1px solid var(--srp-line);
		border-radius: 14px;
		background: #fff;
		transition: border-color .2s, box-shadow .2s, transform .2s;
	}

	.srp-card:hover {
		border-color: #b9e3f4;
		box-shadow: 0 14px 30px rgba(13, 34, 54, .1);
		transform: translateY(-2px);
	}

	.srp-card__media {
		position: relative;
		display: grid;
		place-items: center;
		aspect-ratio: 4 / 3;
		border-bottom: 1px solid var(--srp-line);
		background: #f6f9fb;
		color: #a9b8c5;
	}

	.srp-card__img {
		width: 100%;
		height: 100%;
		padding: 10px;
		object-fit: contain;
	}

	.srp-badge {
		position: absolute;
		top: 10px;
		left: 10px;
		padding: 3px 10px;
		border: 1px solid var(--srp-line);
		border-radius: 999px;
		background: #fff;
		color: var(--srp-muted);
		font-size: 12px;
		font-weight: 700;
	}

	.srp-card__body {
		display: flex;
		flex: 1;
		flex-direction: column;
		gap: 8px;
		padding: 16px;
	}

	.srp-card__title {
		font-size: 17px;
		font-weight: 700;
		line-height: 1.3;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		overflow-wrap: anywhere;
	}

	/* The whole card is clickable through the title link. */
	.srp-card__title a::after {
		content: "";
		position: absolute;
		inset: 0;
		z-index: 1;
		border-radius: 14px;
	}

	.srp-card__title a:focus-visible {
		outline: 0;
	}

	.srp-card__title a:focus-visible::after {
		outline: 3px solid #7ccdee;
		outline-offset: -3px;
	}

	.srp-card:hover .srp-card__title {
		color: var(--srp-blue-text);
	}

	.srp-card__excerpt {
		color: var(--srp-muted);
		font-size: 14px;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 3;
		overflow: hidden;
	}

	.srp-card__foot {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		margin-top: auto;
		padding-top: 10px;
	}

	.srp-price {
		font-size: 16px;
		font-weight: 800;
	}

	.srp-price del {
		margin-right: 4px;
		color: var(--srp-muted);
		font-weight: 500;
	}

	.srp-price ins {
		text-decoration: none;
	}

	.srp-more {
		display: inline-flex;
		align-items: center;
		gap: 2px;
		margin-left: auto;
		color: var(--srp-blue-text);
		font-weight: 700;
	}

	/* ---------- Pagination ---------- */
	.srp-pagination {
		margin-top: 40px;
	}

	.srp-pagination .nav-links {
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		gap: 8px;
	}

	.srp-pagination .page-numbers {
		display: inline-grid;
		place-items: center;
		min-width: 44px;
		height: 44px;
		padding: 0 14px;
		border: 1px solid var(--srp-line);
		border-radius: 10px;
		background: #fff;
		color: var(--srp-ink);
		font-weight: 600;
		transition: border-color .15s, color .15s;
	}

	.srp-pagination a.page-numbers:hover {
		border-color: var(--srp-blue);
		color: var(--srp-blue-text);
	}

	.srp-pagination .page-numbers.current {
		border-color: var(--srp-blue);
		background: var(--srp-blue);
		color: #fff;
	}

	.srp-pagination .page-numbers.dots {
		border-color: transparent;
		background: transparent;
	}

	/* ---------- Empty / no results ---------- */
	.srp-empty {
		max-width: 720px;
		margin: 14px auto 0;
		padding: 44px 28px;
		border: 1px solid var(--srp-line);
		border-radius: 18px;
		background: var(--srp-soft);
		text-align: center;
	}

	.srp-empty__icon {
		display: inline-grid;
		place-items: center;
		width: 64px;
		height: 64px;
		margin-bottom: 16px;
		border-radius: 50%;
		background: var(--srp-blue-tint);
		color: var(--srp-blue-text);
	}

	.srp-empty h2 {
		font-size: 24px;
		font-weight: 800;
		overflow-wrap: anywhere;
	}

	.srp-empty p {
		margin-top: 8px;
		color: var(--srp-muted);
	}

	.srp-tips {
		display: inline-block;
		margin: 18px 0 4px;
		text-align: left;
		color: var(--srp-muted);
	}

	.srp-tips li {
		position: relative;
		padding-left: 18px;
		margin-top: 6px;
	}

	.srp-tips li::before {
		content: "";
		position: absolute;
		left: 2px;
		top: .6em;
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: var(--srp-blue);
	}

	.srp-empty .srp-popular {
		justify-content: center;
	}

	/* ---------- Help band ---------- */
	.srp-help {
		display: grid;
		grid-template-columns: minmax(0, 1fr) auto;
		align-items: center;
		gap: 20px 32px;
		margin-top: 56px;
		padding: 30px 34px;
		border-radius: 18px;
		background: var(--srp-navy);
		color: #d3e0ec;
	}

	.srp-help h2 {
		color: #fff;
		font-size: 22px;
		font-weight: 800;
	}

	.srp-help p {
		margin-top: 6px;
	}

	.srp-help__actions {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
	}

	.srp-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		min-height: 46px;
		padding: 0 22px;
		border: 1.5px solid var(--srp-blue);
		border-radius: 999px;
		color: #fff;
		font-weight: 700;
		white-space: nowrap;
		transition: background-color .15s, border-color .15s;
	}

	.srp-btn--solid {
		background: var(--srp-blue);
	}

	.srp-btn--solid:hover {
		background: var(--srp-blue-hover);
		border-color: var(--srp-blue-hover);
	}

	.srp-btn--ghost:hover {
		background: rgba(255, 255, 255, .1);
	}

	/* ---------- Responsive ---------- */
	@media (max-width: 1199px) {
		.srp-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}
	}

	@media (max-width: 899px) {
		.srp-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 16px;
		}

		.srp-help {
			grid-template-columns: 1fr;
		}
	}

	@media (max-width: 575px) {
		.srp-container {
			padding-left: 14px;
			padding-right: 14px;
		}

		.srp-hero {
			padding: 20px 0 26px;
		}

		.srp-field {
			height: 52px;
			padding-left: 16px;
		}

		.srp-submit {
			width: 40px;
			height: 40px;
			padding: 0;
		}

		.srp-submit span {
			position: absolute;
			width: 1px;
			height: 1px;
			margin: -1px;
			overflow: hidden;
			clip: rect(0 0 0 0);
			white-space: nowrap;
		}

		.srp-toolbar {
			flex-direction: column;
			align-items: stretch;
		}

		.srp-sort {
			overflow-x: auto;
			scrollbar-width: none;
		}

		.srp-sort a {
			white-space: nowrap;
		}

		.srp-grid {
			gap: 12px;
		}

		.srp-card__body {
			padding: 12px;
		}

		.srp-card__title {
			font-size: 15px;
		}

		.srp-card__excerpt {
			-webkit-line-clamp: 2;
			font-size: 13px;
		}

		.srp-more span {
			display: none;
		}

		.srp-pagination .page-numbers:not(.current):not(.prev):not(.next) {
			display: none;
		}

		.srp-empty {
			padding: 32px 18px;
		}

		.srp-help {
			padding: 24px 20px;
		}

		.srp-help__actions .srp-btn {
			flex: 1 1 100%;
		}
	}

	@media (max-width: 380px) {
		.srp-grid {
			grid-template-columns: 1fr;
		}
	}

	@media (prefers-reduced-motion: reduce) {

		.srp *,
		.srp *::before,
		.srp *::after {
			transition-duration: 0s !important;
		}

		.srp-card:hover {
			transform: none;
		}
	}
</style>

<main id="primary" class="site-main srp">

	<!-- Hero: breadcrumb, title, search -->
	<section class="srp-hero" aria-labelledby="srpTitle">
		<div class="srp-container">
			<nav class="srp-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'litsign'); ?>">
				<a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
				<span aria-hidden="true">/</span>
				<span aria-current="page">Search</span>
			</nav>

			<?php if ('' !== $q) : ?>
				<h1 class="srp-title" id="srpTitle">Search results for &ldquo;<?php echo esc_html($q); ?>&rdquo;</h1>
				<p class="srp-sub" role="status">
					<?php if ($found) : ?>
						<?php echo esc_html(number_format_i18n($found)); ?> <?php echo esc_html(_n('result', 'results', $found, 'litsign')); ?> found
					<?php else : ?>
						Nothing matched your search
					<?php endif; ?>
				</p>
			<?php else : ?>
				<h1 class="srp-title" id="srpTitle">Search our products</h1>
				<p class="srp-sub">Type what you&rsquo;re looking for, like channel letters, banners or flags.</p>
			<?php endif; ?>

			<form class="srp-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
				<label class="srp-vh" for="srpInput"><?php esc_html_e('Search', 'litsign'); ?></label>
				<div class="srp-field">
					<?php echo $icon('search', 22); // Static SVG. ?>
					<input class="srp-input" id="srpInput" type="search" name="s" value="<?php echo esc_attr($q); ?>" placeholder="<?php esc_attr_e('Search channel letters, banners, flags…', 'litsign'); ?>" autocomplete="off" enterkeyhint="search">
					<?php if ($req_type) : ?>
						<input type="hidden" name="post_type" value="<?php echo esc_attr($req_type); ?>">
					<?php endif; ?>
					<button type="submit" class="srp-submit">
						<?php echo $icon('search', 18); // Static SVG. ?>
						<span>Search</span>
					</button>
				</div>
			</form>

			<?php if (!$found) : ?>
				<div class="srp-popular">
					<span class="srp-popular__label">Popular searches</span>
					<?php foreach ($popular as $term) : ?>
						<a class="srp-chip" href="<?php echo $build(array('s' => $term, 'post_type' => '', 'orderby' => '')); ?>"><?php echo esc_html($term); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="srp-results" aria-label="<?php esc_attr_e('Search results', 'litsign'); ?>">
		<div class="srp-container">

			<?php if ('' !== $q && have_posts()) : ?>

				<div class="srp-toolbar">
					<nav class="srp-tabs" aria-label="<?php esc_attr_e('Filter by type', 'litsign'); ?>">
						<?php foreach ($type_tabs as $slug => $label) : ?>
							<a class="srp-tab" href="<?php echo $build(array('post_type' => $slug)); ?>" <?php echo ($slug === $req_type) ? 'aria-current="true"' : ''; ?>><?php echo esc_html($label); ?></a>
						<?php endforeach; ?>
					</nav>

					<nav class="srp-sort" aria-label="<?php esc_attr_e('Sort results', 'litsign'); ?>">
						<span class="srp-sort__label">Sort:</span>
						<?php foreach ($sort_tabs as $slug => $label) : ?>
							<a href="<?php echo $build(array('orderby' => $slug)); ?>" <?php echo ($slug === $req_order) ? 'aria-current="true"' : ''; ?>><?php echo esc_html($label); ?></a>
						<?php endforeach; ?>
					</nav>
				</div>

				<p class="srp-count">
					Showing <strong><?php echo esc_html(number_format_i18n($from)); ?>&ndash;<?php echo esc_html(number_format_i18n($to)); ?></strong>
					of <strong><?php echo esc_html(number_format_i18n($found)); ?></strong>
				</p>

				<ul class="srp-grid">
					<?php
					while (have_posts()) :
						the_post();
						$post_id   = get_the_ID();
						$type_obj  = get_post_type_object(get_post_type());
						$type_name = $type_obj ? $type_obj->labels->singular_name : '';
						if ('post' === get_post_type()) {
							$type_name = 'Article';
						}
						$excerpt = wp_trim_words(wp_strip_all_tags(strip_shortcodes(get_the_excerpt())), 22, '…');

						$price_html = '';
						if ('product' === get_post_type() && function_exists('wc_get_product')) {
							$product = wc_get_product($post_id);
							if ($product && '' !== $product->get_price()) {
								$price_html = $product->get_price_html();
							}
						}
					?>
						<li>
							<article <?php post_class('srp-card'); ?>>
								<div class="srp-card__media">
									<?php
									if (has_post_thumbnail()) {
										echo get_the_post_thumbnail($post_id, 'medium_large', array(
											'class'    => 'srp-card__img',
											'loading'  => 'lazy',
											'decoding' => 'async',
											'alt'      => '',
										));
									} else {
										echo $icon('image', 44); // Static SVG.
									}
									?>
									<?php if ($type_name) : ?>
										<span class="srp-badge"><?php echo esc_html($type_name); ?></span>
									<?php endif; ?>
								</div>

								<div class="srp-card__body">
									<h2 class="srp-card__title">
										<a href="<?php the_permalink(); ?>"><?php echo $highlight(get_the_title()); // Escaped inside the closure. ?></a>
									</h2>

									<?php if ($excerpt) : ?>
										<p class="srp-card__excerpt"><?php echo $highlight($excerpt); // Escaped inside the closure. ?></p>
									<?php endif; ?>

									<div class="srp-card__foot">
										<?php if ($price_html) : ?>
											<span class="srp-price"><?php echo wp_kses_post($price_html); ?></span>
										<?php endif; ?>
										<span class="srp-more" aria-hidden="true">
											<span><?php echo ('product' === get_post_type()) ? 'View product' : 'Read more'; ?></span>
											<?php echo $icon('chevron', 18); // Static SVG. ?>
										</span>
									</div>
								</div>
							</article>
						</li>
					<?php endwhile; ?>
				</ul>

				<?php
				the_posts_pagination(array(
					'mid_size'            => 1,
					'prev_text'           => 'Previous',
					'next_text'           => 'Next',
					'class'               => 'srp-pagination',
					'screen_reader_text'  => __('Search results pages', 'litsign'),
				));
				?>

			<?php else : ?>

				<div class="srp-empty">
					<span class="srp-empty__icon"><?php echo $icon('search', 30); // Static SVG. ?></span>
					<?php if ('' !== $q) : ?>
						<h2>No results for &ldquo;<?php echo esc_html($q); ?>&rdquo;</h2>
						<p>We couldn&rsquo;t find a match. Try one of these:</p>
						<ul class="srp-tips">
							<li>Check the spelling</li>
							<li>Use fewer or more general words, like &ldquo;banner&rdquo; instead of &ldquo;vinyl event banner&rdquo;</li>
							<li>Search by product type, like &ldquo;channel letters&rdquo; or &ldquo;flags&rdquo;</li>
						</ul>
						<?php if ($req_type) : ?>
							<p><a class="srp-chip" href="<?php echo $build(array('post_type' => '')); ?>">Search all content instead</a></p>
						<?php endif; ?>
					<?php else : ?>
						<h2>What are you looking for?</h2>
						<p>Enter a product name above, or start with a popular search.</p>
						<div class="srp-popular">
							<?php foreach ($popular as $term) : ?>
								<a class="srp-chip" href="<?php echo $build(array('s' => $term, 'post_type' => '', 'orderby' => '')); ?>"><?php echo esc_html($term); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

			<?php endif; ?>

			<!-- Help band -->
			<aside class="srp-help" aria-label="<?php esc_attr_e('Need help', 'litsign'); ?>">
				<div>
					<h2>Can&rsquo;t find the right sign?</h2>
					<p>Our team is happy to help with sizes, materials and quotes. Mon&ndash;Fri 8am&ndash;5pm PST.</p>
				</div>
				<div class="srp-help__actions">
					<a class="srp-btn srp-btn--solid" href="<?php echo esc_url($phone_href); ?>"><?php echo $icon('phone', 18); // Static SVG. ?> Call <?php echo esc_html($phone_display); ?></a>
					<a class="srp-btn srp-btn--ghost" href="<?php echo esc_url($sms_href); ?>"><?php echo $icon('sms', 18); // Static SVG. ?> Text us</a>
					<a class="srp-btn srp-btn--ghost" href="<?php echo esc_url(home_url('/contact/')); ?>">Free quote</a>
				</div>
			</aside>

		</div>
	</section>
</main>

<?php
get_footer();
