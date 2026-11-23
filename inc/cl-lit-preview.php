<?php
/**
 * "Try your name/brand on this wall" lit preview for /custom-channel-letters/.
 *
 * Cloned from the wholesale home page (tr-wholesale template-parts/trade/home.php,
 * section .th-preview, script js/trade-home.js). The visitor types a name, flips
 * between four lighting styles, five face colors and day/night, and the button
 * opens the product page of the style on screen. Styles live in
 * css/cl-lit-preview.css and are printed inline here, so the page needs no
 * enqueue. Without JavaScript the stage shows a static front lit sample and the
 * controls stay hidden.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Print the lit preview section.
 */
function wholesale_render_cl_lit_preview()
{
	// Lighting key => array(button label, product slug, name on the stage caption).
	$styles = array(
		'front' => array('Front lit', 'standard-channel-letter-front-lit', 'Front Lit'),
		'back' => array('Back lit', 'standard-channel-letter-back-lit', 'Back Lit'),
		'dual' => array('Front & back lit', 'standard-channel-letter-front-back-lit', 'Front & Back Lit'),
		'halo' => array('Halo stainless', 'hidden-back-halo-lit', 'Halo Lit'),
	);

	$buttons = array();
	foreach ($styles as $key => $style) {
		$product = function_exists('wholesale_seo_product') ? wholesale_seo_product($style[1]) : get_page_by_path($style[1], OBJECT, 'product');
		if (!$product) {
			continue;
		}
		$type = preg_match('/^Type\s*(\d+)/i', get_the_title($product), $type_match) ? (int) $type_match[1] : 0;
		$buttons[$key] = array(
			'label' => $style[0],
			'name' => $style[2],
			'caption' => ($type ? 'Type ' . $type . ' · ' : '') . $style[2],
			'url' => get_permalink($product),
		);
	}
	if (!$buttons) {
		return;
	}
	$first = reset($buttons);
	$first_key = key($buttons);
	$arrow = function_exists('wholesale_home_icon') ? wholesale_home_icon('arrow') : '';
	$colors = array(
		'#ffffff' => 'White',
		'#111111' => 'Black',
		'#ffe2b0' => 'Warm white',
		'#ff3b30' => 'Red',
		'#ff7a1a' => 'Orange',
		'#ffd400' => 'Yellow',
		'#22c55e' => 'Green',
		'#3fd8ff' => 'Light blue',
		'#2f8cff' => 'Blue',
		'#0b2f8a' => 'Dark blue',
	);
	// Lit color at night when it differs from the daytime face. Black faces are
	// day/night vinyl (black by day, white when lit); dark blue acrylic reads as a
	// deep royal blue once the LEDs are on.
	$night_colors = array('#111111' => '#ffffff', '#0b2f8a' => '#1f4fe6');

	$css = @file_get_contents(get_template_directory() . '/css/cl-lit-preview.css');
	if (false !== $css) {
		$css = trim(preg_replace('#/\*.*?\*/#s', '', $css));
		echo '<style id="cl-lit-preview-inline-css">' . $css . "</style>\n"; // Theme file, not user input.
	}
	?>
	<section class="clp" id="cl-lit-preview" aria-labelledby="clp-title" data-clp>
		<div class="container clp-in">
			<div class="clp-head">
				<p class="clp-eyebrow">Try your name/brand on this wall</p>
				<h2 class="clp-title" id="clp-title">See your business name lit up</h2>
				<p class="clp-text">Type your name and flip between lighting styles to see the difference. When it looks right, open that style to pick your letter height, colors and wording and see your exact price.</p>
			</div>

			<div class="clp-stage" data-style="<?php echo esc_attr($first_key); ?>" data-time="night" style="--face:#ffffff">
				<div class="clp-wall" aria-hidden="true"></div>
				<p class="clp-sign"><span data-clp-text>OPEN LATE</span></p>
				<p class="clp-cap"><span data-clp-label><?php echo esc_html($first['caption']); ?></span> · Preview only, glow and colors are approximate</p>
			</div>

			<div class="clp-panel">
				<div class="clp-controls" hidden>
					<label class="clp-label" for="clp-name">Enter your text here</label>
					<input class="clp-input" id="clp-name" type="text" maxlength="16" value="OPEN LATE" autocomplete="off" spellcheck="false">

					<p class="clp-label" id="clp-style-label">Lighting types</p>
					<div class="clp-seg" role="radiogroup" aria-labelledby="clp-style-label">
						<?php foreach ($buttons as $key => $button) : ?>
							<button type="button" role="radio" class="clp-seg-btn" data-style="<?php echo esc_attr($key); ?>" data-url="<?php echo esc_url($button['url']); ?>" data-name="<?php echo esc_attr($button['name']); ?>" data-caption="<?php echo esc_attr($button['caption']); ?>" aria-checked="<?php echo $key === $first_key ? 'true' : 'false'; ?>"><?php echo esc_html($button['label']); ?></button>
						<?php endforeach; ?>
					</div>

					<p class="clp-label" id="clp-color-label">Face color options</p>
					<div class="clp-swatches" role="radiogroup" aria-labelledby="clp-color-label">
						<?php
						$is_first = true;
						foreach ($colors as $hex => $color_name) :
							?>
							<?php
							$night = isset($night_colors[$hex]) ? $night_colors[$hex] : '';
							$color_label = '#ffffff' === $night ? $color_name . ' (glows white at night)' : $color_name;
							?>
							<button type="button" role="radio" class="clp-swatch" data-color="<?php echo esc_attr($hex); ?>"<?php echo $night ? ' data-night-color="' . esc_attr($night) . '"' : ''; ?> style="--sw:<?php echo esc_attr($hex); ?>" aria-label="<?php echo esc_attr($color_label); ?>" title="<?php echo esc_attr($color_label); ?>" aria-checked="<?php echo $is_first ? 'true' : 'false'; ?>"></button>
							<?php
							$is_first = false;
						endforeach;
						?>
					</div>

					<button type="button" class="clp-daynight" aria-pressed="false" data-clp-daynight><span class="clp-daynight-knob" aria-hidden="true"></span><span data-clp-daynight-label>Show daytime</span></button>
				</div>

				<div class="clp-actions">
					<a class="clp-btn" href="<?php echo esc_url($first['url']); ?>" data-clp-cta><span>Customize &amp; Order <span data-clp-cta-name><?php echo esc_html($first['name']); ?></span></span></a>
					<a class="clp-link" href="#cl-compare-title">Compare all lighting styles <?php echo $arrow; // Theme SVG. ?></a>
				</div>
			</div>
		</div>
	</section>

	<script>
	/* Lit preview: live text, lighting style, face color and day/night (cloned from
	   the wholesale home page's js/trade-home.js). */
	(function () {
		var root = document.querySelector('[data-clp]');
		if (!root) {
			return;
		}
		var controls = root.querySelector('.clp-controls');
		var stage = root.querySelector('.clp-stage');
		var sign = root.querySelector('.clp-sign');
		var textEl = root.querySelector('[data-clp-text]');
		var input = root.querySelector('#clp-name');
		var labelEl = root.querySelector('[data-clp-label]');
		var cta = root.querySelector('[data-clp-cta]');
		var ctaName = root.querySelector('[data-clp-cta-name]');
		var styleBtns = Array.prototype.slice.call(root.querySelectorAll('.clp-seg-btn'));
		var swatches = Array.prototype.slice.call(root.querySelectorAll('.clp-swatch'));
		var dayBtn = root.querySelector('[data-clp-daynight]');
		var dayLabel = root.querySelector('[data-clp-daynight-label]');
		// Styles where the acrylic face carries the color; back and halo lit faces are opaque metal.
		var faceLit = { front: true, dual: true };
		var frame = 0;

		if (!controls || !stage || !sign || !input || !textEl) {
			return;
		}
		var fallbackText = textEl.textContent;

		controls.hidden = false;
		sign.classList.add('is-fit');

		/* Largest font size that keeps the name on one line inside the stage. */
		function fit() {
			var max = window.innerWidth >= 992 ? 84 : 58;
			var avail = stage.clientWidth - 40;
			sign.style.fontSize = max + 'px';
			var width = sign.scrollWidth;
			if (width > avail && width > 0) {
				sign.style.fontSize = Math.max(20, Math.floor(max * avail / width)) + 'px';
			}
		}

		function scheduleFit() {
			window.cancelAnimationFrame(frame);
			frame = window.requestAnimationFrame(fit);
		}

		function setText() {
			var value = input.value.replace(/\s+/g, ' ').trim();
			textEl.textContent = value || fallbackText;
			scheduleFit();
		}

		/* Radio-style button groups: one checked, arrow keys move the choice. */
		function radioGroup(buttons, onSelect) {
			function select(btn, focus) {
				buttons.forEach(function (b) {
					var on = b === btn;
					b.setAttribute('aria-checked', on ? 'true' : 'false');
					b.tabIndex = on ? 0 : -1;
				});
				if (focus) {
					btn.focus();
				}
				onSelect(btn);
			}

			buttons.forEach(function (btn) {
				btn.tabIndex = btn.getAttribute('aria-checked') === 'true' ? 0 : -1;
				btn.addEventListener('click', function () {
					select(btn, false);
				});
				btn.addEventListener('keydown', function (e) {
					var step = 0;
					if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
						step = 1;
					} else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
						step = -1;
					}
					if (!step) {
						return;
					}
					e.preventDefault();
					var enabled = buttons.filter(function (b) {
						return !b.disabled;
					});
					var at = enabled.indexOf(btn);
					var next = enabled[(at + step + enabled.length) % enabled.length];
					if (next) {
						select(next, true);
					}
				});
			});
		}

		radioGroup(styleBtns, function (btn) {
			var style = btn.getAttribute('data-style');
			stage.setAttribute('data-style', style);
			if (labelEl) {
				labelEl.textContent = btn.getAttribute('data-caption');
			}
			if (cta) {
				cta.href = btn.getAttribute('data-url');
			}
			if (ctaName) {
				ctaName.textContent = btn.getAttribute('data-name');
			}
			swatches.forEach(function (sw) {
				sw.disabled = !faceLit[style];
			});
		});

		var face = swatches[0] || null;

		/* Face color for the time of day: day/night vinyl (black) shows white when lit. */
		function paintFace() {
			if (!face) {
				return;
			}
			var night = stage.getAttribute('data-time') !== 'day';
			var nightColor = face.getAttribute('data-night-color');
			stage.style.setProperty('--face', night && nightColor ? nightColor : face.getAttribute('data-color'));
		}

		radioGroup(swatches, function (btn) {
			face = btn;
			paintFace();
		});

		if (dayBtn) {
			dayBtn.addEventListener('click', function () {
				var day = dayBtn.getAttribute('aria-pressed') !== 'true';
				dayBtn.setAttribute('aria-pressed', day ? 'true' : 'false');
				stage.setAttribute('data-time', day ? 'day' : 'night');
				paintFace();
				if (dayLabel) {
					dayLabel.textContent = day ? 'Show nighttime' : 'Show daytime';
				}
			});
		}

		input.addEventListener('input', setText);
		window.addEventListener('resize', scheduleFit);
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(scheduleFit);
		}
		setText();
	})();
	</script>
	<?php
}
