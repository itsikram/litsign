<?php
/**
 * Opt-in, branded page loader for public front-end navigation.
 *
 * @package litsign
 */

/**
 * Whether the loader can be rendered for this request.
 */
function wholesale_page_loader_is_enabled()
{
	return wholesale_setting_enabled('page_loader_enabled')
		&& !is_admin()
		&& !is_customize_preview()
		&& !wp_doing_ajax()
		&& !(defined('REST_REQUEST') && REST_REQUEST)
		&& !is_feed();
}

/**
 * Add critical styles and bootstrap the visibility gate before first paint.
 */
function wholesale_page_loader_head()
{
	if (!wholesale_page_loader_is_enabled()) {
		return;
	}
	?>
	<style id="wholesale-page-loader-critical">
		/* Overlay and visibility gate (shown only once JS has tagged <html>). */
		.wholesale-page-loader {
			position: fixed;
			z-index: 2147483647;
			inset: 0;
			display: grid;
			place-items: center;
			box-sizing: border-box;
			width: 100%;
			height: 100vh;
			height: 100dvh;
			min-height: 100vh;
			padding: 24px 20px;
			padding: max(24px, env(safe-area-inset-top)) max(20px, env(safe-area-inset-right)) max(24px, env(safe-area-inset-bottom)) max(20px, env(safe-area-inset-left));
			overflow: hidden;
			background: #031835;
			background: radial-gradient(120% 85% at 50% 46%, #0b2b52 0%, #031835 52%, #020e20 100%);
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
			transition: opacity 420ms ease, visibility 0s linear 420ms;
		}
		html.wholesale-page-loader-enabled:not(.wholesale-page-loader-failsafe) .wholesale-page-loader:not(.is-hidden) {
			opacity: 1;
			visibility: visible;
			pointer-events: auto;
			transition-delay: 0s;
		}
		.wholesale-page-loader.is-hidden {
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
		}
		/* The first page a visitor lands on shows straight away; the loader only
		   covers the wait after a link click (page-loader.js removes the class).
		   display:none also keeps its animations from running. */
		html.wholesale-page-loader-landing .wholesale-page-loader {
			display: none;
		}
		.wholesale-page-loader .screen-reader-text {
			position: absolute;
			width: 1px;
			height: 1px;
			padding: 0;
			margin: -1px;
			overflow: hidden;
			clip: rect(0, 0, 0, 0);
			white-space: nowrap;
			border: 0;
		}
		.wholesale-page-loader__content {
			display: grid;
			justify-items: center;
			width: 100%;
		}

		/* Stage: the logo box keeps the logo's 2172:408 shape; perspective gives
		   the pieces real depth. --dz is the gap between the stacked layers. */
		.wholesale-page-loader__logo-wrap {
			--dz: clamp(1.4px, .7vw, 2.4px);
			position: relative;
			width: clamp(180px, 60vw, 340px);
			max-width: 100%;
			aspect-ratio: 2172 / 408;
			perspective: 760px;
			perspective-origin: 50% 40%;
			transition: opacity 340ms ease, transform 440ms cubic-bezier(.4, 0, 1, 1);
		}
		.wholesale-page-loader__src {
			position: absolute;
			width: 0;
			height: 0;
			overflow: hidden;
		}
		.wholesale-page-loader__halo {
			position: absolute;
			inset: -75% -14%;
			pointer-events: none;
			background: radial-gradient(closest-side, rgba(246, 78, 9, .30), rgba(246, 78, 9, .11) 52%, rgba(246, 78, 9, 0) 100%);
			opacity: .85;
			transition: opacity 320ms ease;
		}
		.spl3 {
			position: absolute;
			inset: 0;
			transform-style: preserve-3d;
		}
		.spl3-p {
			position: absolute;
			left: var(--x);
			top: var(--y);
			width: var(--w);
			height: var(--h);
			transform-style: preserve-3d;
		}
		.spl3-p svg {
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			overflow: visible;
			stroke-width: 0;
			/* Hidden while a piece faces away, so letters only appear once
			   they turn past upright and the badge never shows mirrored. */
			-webkit-backface-visibility: hidden;
			backface-visibility: hidden;
		}
		/* Side layers: stacked behind the face, darker, like channel letter returns. */
		.spl3-d {
			transform: translateZ(calc(var(--k) * var(--dz) * -1));
		}
		.spl3-mark .spl3-d,
		.spl3-tag .spl3-d,
		.spl3-badge .spl3-d {
			filter: brightness(.42);
		}
		.spl3-letter { transform-origin: 50% 100%; }
		.spl3-mark { transform-origin: 50% 50%; }
		.spl3-badge { transform-origin: 35% 100%; transform: translateZ(2px); }
		.spl3-tag { transform-origin: 50% 0; }

		/* Indeterminate progress line. */
		.wholesale-page-loader__progress {
			position: relative;
			width: min(44vw, 160px);
			height: 2px;
			margin-top: clamp(22px, 4.5vh, 34px);
			overflow: hidden;
			border-radius: 2px;
			background: rgba(255, 255, 255, .12);
			transition: opacity 240ms ease;
		}
		.wholesale-page-loader__progress span {
			position: absolute;
			top: 0;
			left: 0;
			width: 40%;
			height: 100%;
			border-radius: inherit;
			background: linear-gradient(90deg, rgba(246, 78, 9, 0), #f64e09 45%, #ff9a5c);
			transform: translateX(-100%);
			animation: wholesale-loader-progress 1.4s cubic-bezier(.65, 0, .35, 1) infinite;
		}

		/* 1. Intro, about 1.6 s. The mark swings round on its vertical axis;
		   the letters stand up on their baseline one after another, like
		   channel letters being raised; each face then switches on (two give
		   a single soft flicker, well under 3 flashes a second); the tagline
		   rises, the badge spins in like a pole sign, and the halo lights. */
		.wholesale-page-loader.is-intro .spl3-mark {
			animation: wholesale-loader-mark 900ms cubic-bezier(.22, .9, .3, 1.08) .04s both;
		}
		.wholesale-page-loader.is-intro .spl3-letter {
			animation: wholesale-loader-raise 700ms cubic-bezier(.3, 1.35, .5, 1) both;
			animation-delay: calc(.2s + var(--i) * 42ms);
		}
		.wholesale-page-loader.is-intro .spl3-letter .spl3-f {
			animation: wholesale-loader-light 260ms ease-out both;
			animation-delay: calc(.62s + var(--i) * 42ms);
		}
		.wholesale-page-loader.is-intro .spl3-letter:nth-child(7) .spl3-f,
		.wholesale-page-loader.is-intro .spl3-letter:nth-child(13) .spl3-f {
			animation: wholesale-loader-light 260ms ease-out both, wholesale-loader-flicker 280ms linear both;
			animation-delay: calc(.62s + var(--i) * 42ms), 1.28s;
		}
		.wholesale-page-loader.is-intro .spl3-tag {
			animation: wholesale-loader-rise 560ms cubic-bezier(.2, .8, .3, 1) .95s both;
		}
		.wholesale-page-loader.is-intro .spl3-badge {
			animation: wholesale-loader-badge 760ms cubic-bezier(.25, 1.3, .5, 1) .82s both;
		}
		.wholesale-page-loader.is-intro .wholesale-page-loader__halo {
			animation: wholesale-loader-halo-in 520ms ease-out 1.1s both;
		}
		.wholesale-page-loader.is-intro .wholesale-page-loader__progress {
			animation: wholesale-loader-fade 400ms ease-out .5s both;
		}

		/* 2. Idle loop: the sign sways slowly in 3D so its depth shows, the
		   letters pop forward one after another (a light chase), and the halo
		   breathes. Every loop starts and ends at rest, so it is seamless. */
		.wholesale-page-loader.is-idle .spl3 {
			animation: wholesale-loader-sway 7s ease-in-out infinite;
		}
		.wholesale-page-loader.is-idle .spl3-letter {
			animation: wholesale-loader-chase 3.4s ease-in-out infinite;
			animation-delay: calc(.4s + var(--i) * 70ms);
		}
		.wholesale-page-loader.is-idle .wholesale-page-loader__halo {
			animation: wholesale-loader-breathe 3.2s ease-in-out infinite;
		}

		/* 3. Exit: the sign comes forward and fades, then the overlay fades. */
		.wholesale-page-loader.is-exiting .wholesale-page-loader__logo-wrap {
			opacity: 0;
			transform: translateY(-6px) scale(1.06);
		}
		.wholesale-page-loader.is-exiting .wholesale-page-loader__halo,
		.wholesale-page-loader.is-exiting .wholesale-page-loader__progress {
			opacity: 0;
		}

		@keyframes wholesale-loader-mark {
			0% { transform: translateZ(-220px) rotateY(-130deg) scale(.5); }
			68% { transform: translateZ(14px) rotateY(14deg) scale(1.03); }
			100% { transform: none; }
		}
		@keyframes wholesale-loader-raise {
			0% { transform: translateZ(-30px) rotateX(-96deg); }
			100% { transform: none; }
		}
		@keyframes wholesale-loader-light {
			from { opacity: .35; }
			to { opacity: 1; }
		}
		@keyframes wholesale-loader-flicker {
			0%, 100% { opacity: 1; }
			35% { opacity: .4; }
			60% { opacity: .92; }
		}
		@keyframes wholesale-loader-rise {
			0% { opacity: 0; transform: translateY(70%) translateZ(-40px) rotateX(70deg); }
			100% { opacity: 1; transform: none; }
		}
		@keyframes wholesale-loader-badge {
			0% { transform: translateZ(2px) rotateY(-180deg) scale(.6); }
			100% { transform: translateZ(2px); }
		}
		@keyframes wholesale-loader-sway {
			0%, 100% { transform: none; }
			25% { transform: rotateY(-11deg) rotateX(4deg); }
			75% { transform: rotateY(11deg) rotateX(-2deg); }
		}
		@keyframes wholesale-loader-chase {
			0%, 26%, 100% { transform: none; }
			13% { transform: translateZ(calc(var(--dz) * 7)) translateY(-3%); }
		}
		@keyframes wholesale-loader-fade {
			from { opacity: 0; }
			to { opacity: 1; }
		}
		@keyframes wholesale-loader-halo-in {
			from { opacity: 0; transform: scale(.9); }
			to { opacity: .85; transform: scale(1); }
		}
		@keyframes wholesale-loader-breathe {
			0%, 100% { opacity: .85; transform: scale(1); }
			50% { opacity: .5; transform: scale(.96); }
		}
		@keyframes wholesale-loader-progress {
			from { transform: translateX(-100%); }
			to { transform: translateX(250%); }
		}

		/* Reduced motion: no turning, raising, flicker or sway; the lit logo
		   and the overlay simply fade. */
		@media (prefers-reduced-motion: reduce) {
			.wholesale-page-loader * {
				animation: none !important;
			}
			.wholesale-page-loader {
				transition: opacity 200ms linear, visibility 0s linear 200ms;
			}
			html.wholesale-page-loader-enabled:not(.wholesale-page-loader-failsafe) .wholesale-page-loader:not(.is-hidden) {
				animation: wholesale-loader-fade 200ms linear both;
			}
			.wholesale-page-loader__logo-wrap {
				transition: opacity 200ms linear;
			}
			.wholesale-page-loader.is-exiting .wholesale-page-loader__logo-wrap {
				transform: none;
			}
			.wholesale-page-loader__progress span {
				transform: translateX(75%);
			}
		}
	</style>
	<script>
		(function () {
			var root = document.documentElement;
			var key = 'wholesale-page-loader-handoff';
			var now = Date.now();
			root.classList.add('wholesale-page-loader-enabled');
			// page-loader.js reads resource timing to know when the hero photo
			// has arrived; the default 250-entry buffer can fill up on busy pages.
			if (window.performance && performance.setResourceTimingBufferSize) {
				performance.setResourceTimingBufferSize(1000);
			}
			window.wholesalePageLoaderNavigating = false;
			window.wholesalePageLoaderStartedAt = now;
			try {
				var stamp = Number(window.sessionStorage.getItem(key));
				if (stamp && now >= stamp && now - stamp < 20000) {
					root.classList.add('wholesale-page-loader-handoff');
				}
				window.sessionStorage.removeItem(key);
			} catch (error) {
				console.warn('Page loader hand-off storage is unavailable.', error);
			}
			// No hand-off means the visitor arrived from outside (search, an ad,
			// a typed URL) or reloaded: show the page itself, not the loader.
			if (!root.classList.contains('wholesale-page-loader-handoff')) {
				root.classList.add('wholesale-page-loader-landing');
			}
			// Fallback only for when js/page-loader.js never runs (blocked or
			// failed to download). The timer starts once the whole HTML has
			// arrived, not at this point in the head: on a slow connection the
			// <body> holding the loader can arrive many seconds after the head.
			document.addEventListener('DOMContentLoaded', function () {
				window.setTimeout(function () {
					if (window.wholesalePageLoaderReady || window.wholesalePageLoaderNavigating) {
						return;
					}
					root.classList.add('wholesale-page-loader-failsafe');
					var loader = document.querySelector('.wholesale-page-loader');
					if (loader) {
						loader.classList.add('is-hidden');
						loader.setAttribute('aria-hidden', 'true');
					}
				}, 12000);
			});
		}());
	</script>
	<noscript><style id="wholesale-page-loader-noscript">.wholesale-page-loader{display:none!important}</style></noscript>
	<?php
}
add_action('wp_head', 'wholesale_page_loader_head', 0);

/**
 * Inline the trusted theme SVG after the body opens; no separate logo request.
 */
function wholesale_page_loader_markup()
{
	if (!wholesale_page_loader_is_enabled()) {
		return;
	}

	$logo_path = get_template_directory() . '/img/loader-logo.svg';
	if (!is_readable($logo_path)) {
		return;
	}
	$logo_svg = file_get_contents($logo_path);
	if (false === $logo_svg) {
		return;
	}
	?>
	<div class="wholesale-page-loader is-intro" role="status" aria-live="polite" aria-atomic="true" aria-hidden="false">
		<span class="screen-reader-text">Loading...</span>
		<div class="wholesale-page-loader__content">
			<div class="wholesale-page-loader__logo-wrap">
				<span class="wholesale-page-loader__halo" aria-hidden="true"></span>
				<span class="wholesale-page-loader__src" aria-hidden="true">
					<?php
					// Source shapes for the 3D pieces below; never shown directly.
					// This SVG is a fixed, reviewed theme asset, not user-provided markup.
					echo $logo_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</span>
				<?php wholesale_page_loader_3d_logo(); ?>
			</div>
			<div class="wholesale-page-loader__progress" aria-hidden="true"><span></span></div>
		</div>
	</div>
	<script>
		// Start the animation from the first paint. js/page-loader.js is deferred,
		// so it only runs once the whole page has downloaded. After a link click
		// (hand-off) skip the intro and go straight to the idle loop.
		(function () {
			var loader = document.currentScript.previousElementSibling;
			window.wholesalePageLoaderShownAt = Date.now();
			var toIdle = function () {
				if (loader.classList.contains('is-intro')) {
					loader.classList.remove('is-intro');
					loader.classList.add('is-idle');
				}
			};
			if (document.documentElement.classList.contains('wholesale-page-loader-handoff')) {
				toIdle();
			} else {
				window.setTimeout(toIdle, 1700);
			}
		}());
	</script>
	<?php
}
add_action('wp_body_open', 'wholesale_page_loader_markup', 1);

/**
 * Print the logo as 3D pieces: the mark, each wordmark letter, the tagline
 * and the "online .com" badge. Each piece is a front face plus a few darker
 * layers stacked behind it (translateZ), like the returns of a channel
 * letter, so it shows real thickness when it turns. All layers draw the
 * shapes from the hidden source SVG with <use>, so no path data is repeated.
 *
 * Boxes are each piece's bounds in the logo's 2172 x 408 viewBox, measured
 * from img/loader-logo.svg (utils/loader-logo). Re-measure if the logo changes.
 */
function wholesale_page_loader_3d_logo()
{
	$width = 2172;
	$height = 408;
	$letters = array(
		array(288, 125.6, 135.7, 199.7),
		array(425, 153, 97.5, 171),
		array(524.5, 178.8, 139.7, 147.2),
		array(667, 179.2, 105.6, 144.5),
		array(769.6, 179.2, 137.9, 146.9),
		array(916, 125, 117.2, 199),
		array(1022.9, 178.5, 106, 145.5),
		array(1126, 178.5, 139, 147.9),
		array(1267.5, 179, 143.5, 145.8),
		array(1415.7, 152.8, 98.4, 171.3),
		array(1513.9, 125.8, 136.3, 200),
		array(1661.8, 125, 57.6, 198.8),
		array(1728, 179.1, 138, 199.9),
		array(1872.8, 177.9, 150.2, 148.1),
	);

	// kind, source ids, box, depth layers, front fill, side fill.
	$pieces = array(array('mark', array('spl-tile', 'spl-s'), array(-0.2, 123.1, 265.5, 264.6), 4, '', ''));
	foreach ($letters as $index => $box) {
		$pieces[] = array('letter', array('spl-l' . ($index + 1)), $box, 3, '#f64e09', '#9a3006');
	}
	$pieces[] = array('tag', array('spl-tag'), array(287.5, 343.5, 1424, 40.4), 0, '', ''); // Thin text: no side layers.
	$pieces[] = array('badge', array('spl-badge'), array(1890.3, 12.5, 282.2, 270), 3, '', '');

	$percent = static function ($value, $total) {
		return round($value / $total * 100, 3) . '%';
	};

	echo '<div class="spl3" role="img" aria-label="' . esc_attr__('Storefront Sign Online', 'litsign') . '">';
	$letter_index = 0;
	foreach ($pieces as $piece) {
		list($kind, $ids, $box, $depth, $front_fill, $side_fill) = $piece;
		$style = sprintf(
			'--x:%s;--y:%s;--w:%s;--h:%s;--i:%d',
			$percent($box[0], $width),
			$percent($box[1], $height),
			$percent($box[2], $width),
			$percent($box[3], $height),
			'letter' === $kind ? $letter_index++ : 0
		);
		$view_box = implode(' ', $box);

		echo '<span class="spl3-p spl3-' . esc_attr($kind) . '" style="' . esc_attr($style) . '">';
		for ($layer = $depth; $layer >= 0; $layer--) {
			$is_front = 0 === $layer;
			$fill = $is_front ? $front_fill : $side_fill;
			echo '<svg class="' . ($is_front ? 'spl3-f' : 'spl3-d') . '" style="--k:' . (int) $layer . '" viewBox="' . esc_attr($view_box) . '" aria-hidden="true" focusable="false">';
			foreach ($ids as $id) {
				echo '<use href="#' . esc_attr($id) . '"' . ($fill ? ' fill="' . esc_attr($fill) . '"' : '') . '/>';
			}
			echo '</svg>';
		}
		echo '</span>';
	}
	echo '</div>';
}

/**
 * Templates that open <body> without calling wp_body_open(), so the loader
 * cannot be printed there and their styles must stay in the head.
 */
function wholesale_page_loader_skips_body()
{
	return is_page_template('b2calc.php');
}

/**
 * Print the theme's stylesheets after the loader instead of in the head.
 *
 * Not used since 2026-10-21: the loader no longer shows on the page a visitor
 * lands on, and after a link click the stylesheets are already in the
 * browser cache, so they stay in the head. In the body they held back the
 * first paint of the page content (Speed Index 5.7s on PageSpeed).
 *
 * Original reason:
 * A browser paints nothing until the <body> arrives and every stylesheet in
 * the head has downloaded. The head carries about 60 KB of inline CSS, so on a
 * slow connection visitors saw a blank white page for many seconds and the
 * loader only appeared once the page was nearly ready. Printed just after the
 * loader markup, the same styles still load before any page content renders,
 * but the loader can paint first. Search engines are unaffected: only CSS
 * moves, never meta, canonical or structured data tags.
 */
function wholesale_page_loader_defer_styles()
{
	if (!wholesale_page_loader_is_enabled() || wholesale_page_loader_skips_body() || !is_readable(get_template_directory() . '/img/loader-logo.svg')) {
		return;
	}

	if (false !== has_action('wp_head', 'wp_print_styles')) {
		remove_action('wp_head', 'wp_print_styles', 8);
		$GLOBALS['wholesale_page_loader_styles_moved'] = true;
	}
}

/**
 * Print the moved styles: right after the loader, or at the latest in the
 * footer if a template never called wp_body_open().
 */
function wholesale_page_loader_print_moved_styles()
{
	if (empty($GLOBALS['wholesale_page_loader_styles_moved'])) {
		return;
	}

	$GLOBALS['wholesale_page_loader_styles_moved'] = false;
	wp_print_styles();
}
add_action('wp_body_open', 'wholesale_page_loader_print_moved_styles', 2);
add_action('wp_footer', 'wholesale_page_loader_print_moved_styles', 0);

/**
 * Load the navigation listener early and only when the option is enabled.
 */
function wholesale_page_loader_enqueue_script()
{
	if (!wholesale_page_loader_is_enabled()) {
		return;
	}

	$relative_path = '/js/page-loader.js';
	$file_path = get_template_directory() . $relative_path;
	$version = file_exists($file_path) ? (string) filemtime($file_path) : _S_VERSION;

	wp_enqueue_script(
		'wholesale-page-loader',
		get_template_directory_uri() . $relative_path,
		array(),
		$version,
		false
	);
	wp_script_add_data('wholesale-page-loader', 'strategy', 'defer');
}
add_action('wp_enqueue_scripts', 'wholesale_page_loader_enqueue_script', 1);
