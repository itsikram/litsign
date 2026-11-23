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
			/* Comes in fast on a click (owner 2026-11-23: show instantly);
			   leaving still fades over 420 ms (rule above). */
			transition: opacity 140ms ease-out, visibility 0s linear 0s;
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
		/* Once the landing page has loaded, or the visitor moves, scrolls or
		   touches, page-loader.js adds -warm: the loader is laid out but stays
		   invisible (.is-hidden), so the browser downloads the logo picture
		   before any link click. With display:none the picture was only
		   requested on the click, so the loader showed with no logo for a
		   second or more (owner 2026-11-23). */
		html.wholesale-page-loader-landing.wholesale-page-loader-warm .wholesale-page-loader {
			display: grid;
		}
		/* Nothing runs while the loader is hidden. */
		.wholesale-page-loader.is-hidden .wholesale-page-loader__progress span {
			animation-play-state: paused;
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

		/* Stage: the logo box keeps the logo's 2313:365 shape; perspective gives
		   the pieces real depth. --dz is the gap between the stacked layers. */
		.wholesale-page-loader__logo-wrap {
			--dz: clamp(1.4px, .7vw, 2.4px);
			position: relative;
			width: clamp(180px, 60vw, 340px);
			max-width: 100%;
			aspect-ratio: 2313 / 365;
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
			background: radial-gradient(closest-side, rgba(246, 145, 30, .30), rgba(246, 145, 30, .11) 52%, rgba(246, 145, 30, 0) 100%);
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
		/* Brand picture version (wholesale_page_loader_brand_logo()): every layer
		   shows its piece of one picture; side layers are darker copies. */
		/* Same size as the header logo on computers (396px after the owner's
		   10% reduction; owner 2026-11-20: "make sure that preloader logo is
		   matching with main header logo"). */
		.wholesale-page-loader__logo-wrap.is-brand {
			--spl-w: min(396px, calc(100vw - 48px));
			width: var(--spl-w);
			aspect-ratio: 1910 / 323;
		}
		/* Tagline as sharp text, like the header's (wholesale_logo_tagline_style()
		   in inc/logo-motion.php), in the dark copy's off-white for the navy
		   overlay. The tag piece spans picture rows 279-323; the text starts at
		   row 281. Plus Jakarta Sans may not be loaded yet when the loader
		   paints; the fallbacks fit the same width. */
		.spl3--img .spl3-p.spl3-tag > i {
			background-image: none;
		}
		.spl3-tagtext {
			position: absolute;
			left: 0;
			/* S tile to the end of "StorefrontSign", like the header (owner
			   2026-11-21): letters end at x=1648 of 1910, so 13.6% stays clear
			   of the sign and the font is x0.864 (was / 32.8). */
			right: 13.6%;
			top: calc(100% * 2 / 44);
			display: block;
			font: 800 13px/1 "Plus Jakarta Sans", Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
			font-size: calc(var(--spl-w) / 38);
			font-style: normal;
			letter-spacing: .08em;
			text-transform: uppercase;
			color: #ecf1f7;
			white-space: nowrap;
			text-align: justify;
			text-align-last: justify;
		}
		.spl3--img .spl3-p > i {
			position: absolute;
			inset: 0;
			background-repeat: no-repeat;
			background-size: var(--bs);
			background-position: var(--bp);
			-webkit-clip-path: var(--clip, none);
			clip-path: var(--clip, none);
			-webkit-backface-visibility: hidden;
			backface-visibility: hidden;
		}
		.spl3--img .spl3-d {
			filter: brightness(.42);
		}
		.spl3-letter { transform-origin: 50% 100%; }
		.spl3-mark { transform-origin: 50% 50%; }
		.spl3-badge { transform-origin: 50% 100%; transform: translateZ(2px); }
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
			background: linear-gradient(90deg, rgba(243, 108, 33, 0), #f36c21 45%, #f6911e);
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
					// Start reading the logo picture (cached by the page the
					// visitor clicked on) now, so it is ready when the loader
					// paints instead of arriving a moment later.
					var logo = window.sessionStorage.getItem(key + '-logo');
					if (logo && 0 === logo.indexOf(location.origin + '/')) {
						window.wholesalePageLoaderLogo = new Image();
						window.wholesalePageLoaderLogo.src = logo;
					}
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

	// The brand artwork (img/brand, same as the header and footer) when it is in
	// the theme; the older traced SVG logo otherwise.
	$brand = function_exists('wholesale_brand_logo_dark_ready') && wholesale_brand_logo_dark_ready();
	$logo_svg = '';
	if (!$brand) {
		$logo_path = get_template_directory() . '/img/loader-logo.svg';
		if (!is_readable($logo_path)) {
			return;
		}
		$logo_svg = file_get_contents($logo_path);
		if (false === $logo_svg) {
			return;
		}
	}
	?>
	<div class="wholesale-page-loader is-intro" role="status" aria-live="polite" aria-atomic="true" aria-hidden="false">
		<span class="screen-reader-text">Loading...</span>
		<div class="wholesale-page-loader__content">
			<div class="wholesale-page-loader__logo-wrap<?php echo $brand ? ' is-brand' : ''; ?>">
				<span class="wholesale-page-loader__halo" aria-hidden="true"></span>
				<?php if ($brand) : ?>
					<?php wholesale_page_loader_brand_logo(); ?>
				<?php else : ?>
				<span class="wholesale-page-loader__src" aria-hidden="true">
					<?php
					// Source shapes for the 3D pieces below; never shown directly.
					// This SVG is a fixed, reviewed theme asset, not user-provided markup.
					echo $logo_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</span>
				<?php wholesale_page_loader_3d_logo(); ?>
				<?php endif; ?>
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
 * and the "WHOLESALE .COM" sign. Each piece is a front face plus a few darker
 * layers stacked behind it (translateZ), like the returns of a channel
 * letter, so it shows real thickness when it turns. All layers draw the
 * shapes from the hidden source SVG with <use>, so no path data is repeated.
 *
 * Boxes are each piece's bounds in the logo's 2313 x 365 viewBox. They and
 * img/loader-logo.svg come from the vector logo PDF via
 * utils/loader-logo/from_pdf.py, which prints them. Re-run it if the logo changes.
 */
function wholesale_page_loader_3d_logo()
{
	$width = 2313;
	$height = 365;
	$letters = array(
		array(299.2, 93.6, 134.3, 206.8),
		array(447.4, 122.1, 99.8, 177.4),
		array(554.1, 150.7, 133.5, 149.7),
		array(701.1, 150.1, 106.4, 148.9),
		array(815.6, 150.6, 127, 149.8),
		array(953.9, 93.5, 109.4, 204.3),
		array(1061.7, 150.1, 106.4, 148.9),
		array(1176, 150.7, 133.5, 149.7),
		array(1323, 150.4, 141.8, 148.9),
		array(1480.5, 122.1, 99.5, 177.4),
		array(1589.6, 93.7, 134.3, 206.8),
		array(1744.4, 93.3, 53.7, 205.1),
		array(1818.5, 150.7, 128.6, 203.2),
		array(1965.1, 150.4, 141.5, 148.8),
	);

	// kind, source ids, box, depth layers, front fill, side fill.
	$pieces = array(array('mark', array('spl-tile', 'spl-s'), array(0, 90.9, 273.6, 273.6), 4, '', ''));
	foreach ($letters as $index => $box) {
		$pieces[] = array('letter', array('spl-l' . ($index + 1)), $box, 3, '#f6911e', '#9a5b13');
	}
	$pieces[] = array('tag', array('spl-tag'), array(309.2, 325.4, 1500.1, 38.7), 0, '', ''); // Thin text: no side layers.
	$pieces[] = array('badge', array('spl-badge'), array(1945.8, 0, 367, 242.6), 3, '', '');

	$percent = static function ($value, $total) {
		return round($value / $total * 100, 3) . '%';
	};

	echo '<div class="spl3" role="img" aria-label="' . esc_attr__('Storefront Sign Wholesale', 'litsign') . '">';
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
 * The same 3D pieces cut from the brand picture (img/brand/sfs-logo-dark-*,
 * the header artwork with a light tagline for the dark overlay). Each layer
 * is an <i> that shows its piece of the picture as a background; letters are
 * cut along the lines between them with clip-path, so a neighbour never
 * shows. Side layers are darker copies stacked behind (see .spl3--img CSS).
 *
 * Numbers are picture pixels (1910 x 323), the same as js/logo-motion.js
 * (made by utils/logo-motion/slice.py). Re-measure if the artwork changes.
 */
function wholesale_page_loader_brand_logo()
{
	$width = 1910;
	$height = 323;
	$top = 32;
	$base = 279;
	$word_left = 252;
	$sign_left = 1656;
	$seams = array(
		array(array(380, 32), array(380, 278)),
		array(array(460, 32), array(461, 125), array(458, 144), array(458, 180), array(461, 209), array(460, 278)),
		array(array(583, 32), array(583, 278)),
		array(array(663, 32), array(665, 125), array(659, 128), array(657, 134), array(657, 136), array(660, 137), array(660, 180), array(662, 186), array(662, 278)),
		array(array(774, 32), array(773, 128), array(778, 130), array(775, 183), array(775, 278)),
		array(array(860, 32), array(858, 89), array(859, 278)),
		array(array(933, 32), array(933, 86), array(937, 89), array(937, 118), array(930, 123), array(929, 127), array(929, 181), array(933, 192), array(933, 278)),
		array(array(1053, 32), array(1053, 278)),
		array(array(1163, 32), array(1162, 120), array(1166, 123), array(1166, 141), array(1164, 142), array(1163, 278)),
		array(array(1243, 32), array(1244, 278)),
		array(array(1365, 32), array(1365, 278)),
		array(array(1412, 32), array(1412, 278)),
		array(array(1535, 32), array(1535, 278)),
	);
	// Outer edges of the first and last letter, as straight lines.
	array_unshift($seams, array(array($word_left, $top), array($word_left, $base)));
	$seams[] = array(array($sign_left, $top), array($sign_left, $base));

	// kind, box (x, y, w, h), depth layers, clip polygon in picture pixels.
	$pieces = array(array('mark', array(0, 0, $word_left, $base), 4, null));
	for ($i = 0; $i < 14; $i++) {
		$left = $seams[$i];
		$right = $seams[$i + 1];
		$xs = array_merge(array_column($left, 0), array_column($right, 0));
		$x0 = min($xs);
		$x1 = max($xs);
		// Down the left cut line, back up the right one, stretched to the full band.
		$poly = array(array($left[0][0], $top));
		foreach ($left as $point) {
			$poly[] = $point;
		}
		$poly[] = array(end($left)[0], $base);
		$poly[] = array(end($right)[0], $base);
		foreach (array_reverse($right) as $point) {
			$poly[] = $point;
		}
		$poly[] = array($right[0][0], $top);
		$pieces[] = array('letter', array($x0, $top, $x1 - $x0, $base - $top), 3, $poly);
	}
	$pieces[] = array('tag', array(0, $base, $width, $height - $base), 0, null);
	$pieces[] = array('badge', array($sign_left, 0, $width - $sign_left, $base), 3, null);

	$pct = static function ($value, $total) {
		return round($value / $total * 100, 3) . '%';
	};

	$dir = get_template_directory_uri() . '/img/brand/sfs-logo-dark-640.';
	$version = (string) filemtime(get_template_directory() . '/img/brand/sfs-logo-dark-960.webp');
	$webp = esc_url($dir . 'webp?v=' . $version);
	$avif = esc_url($dir . 'avif?v=' . $version);
	// One picture for every layer; image-set() is skipped by browsers that
	// do not know it, so they keep the WebP line.
	echo '<style>.spl3--img .spl3-p>i{background-image:url("' . $webp . '");background-image:image-set(url("' . $avif . '") type("image/avif"),url("' . $webp . '") type("image/webp"))}</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URLs escaped above.

	echo '<div class="spl3 spl3--img" role="img" aria-label="' . esc_attr__('Storefront Sign Online', 'litsign') . '">';
	$letter_index = 0;
	foreach ($pieces as $piece) {
		list($kind, $box, $depth, $poly) = $piece;
		list($x, $y, $w, $h) = $box;
		$style = sprintf(
			'--x:%s;--y:%s;--w:%s;--h:%s;--i:%d;--bs:%s %s;--bp:%s %s',
			$pct($x, $width),
			$pct($y, $height),
			$pct($w, $width),
			$pct($h, $height),
			'letter' === $kind ? $letter_index++ : 0,
			$pct($width, $w),
			$pct($height, $h),
			$width > $w ? $pct($x, $width - $w) : '0%',
			$height > $h ? $pct($y, $height - $h) : '0%'
		);
		if ($poly) {
			$points = array();
			foreach ($poly as $point) {
				$points[] = $pct($point[0] - $x, $w) . ' ' . $pct($point[1] - $y, $h);
			}
			$style .= ';--clip:polygon(' . implode(',', $points) . ')';
		}
		echo '<span class="spl3-p spl3-' . esc_attr($kind) . '" style="' . esc_attr($style) . '">';
		for ($layer = $depth; $layer >= 0; $layer--) {
			// The tagline is text, not a piece of the picture (see .spl3-tagtext).
			$inner = ('tag' === $kind && 0 === $layer) ? '<span class="spl3-tagtext">' . esc_html__('Channel letters & large format prints since 2002', 'litsign') . '</span>' : '';
			echo '<i class="' . (0 === $layer ? 'spl3-f' : 'spl3-d') . '" style="--k:' . (int) $layer . '">' . $inner . '</i>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
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
