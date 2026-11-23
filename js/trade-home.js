/**
 * Trade home page: the "see it lit" preview (template-parts/trade/home.php).
 * Without this script the stage shows a static front lit sample and the
 * controls stay hidden.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-th-preview]');
	if (!root) {
		return;
	}

	var controls = root.querySelector('.th-controls');
	var stage = root.querySelector('.th-stage');
	var sign = root.querySelector('.th-stage__sign');
	var textEl = root.querySelector('[data-preview-text]');
	var input = root.querySelector('#th-preview-name');
	var labelEl = root.querySelector('[data-preview-label]');
	var typeLink = root.querySelector('[data-preview-type]');
	var cta = root.querySelector('[data-preview-cta]');
	var styleBtns = Array.prototype.slice.call(root.querySelectorAll('.th-seg__btn'));
	var swatches = Array.prototype.slice.call(root.querySelectorAll('.th-swatch'));
	var dayBtn = root.querySelector('[data-daynight]');
	var dayLabel = root.querySelector('[data-daynight-label]');
	var fallbackText = textEl.textContent;
	// Styles where the acrylic face carries the color; back and halo lit faces are opaque metal.
	var faceLit = { front: true, dual: true };
	var frame = 0;

	if (!controls || !stage || !sign || !input) {
		return;
	}

	controls.hidden = false;
	sign.classList.add('is-fit');

	/** Largest font size that keeps the name on one line inside the stage. */
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

	/** Radio-style button groups: one checked, arrow keys move the choice. */
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

		buttons.forEach(function (btn, i) {
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
		var label = btn.getAttribute('data-type-label');
		stage.setAttribute('data-style', style);
		if (labelEl && label) {
			labelEl.textContent = label;
		}
		if (typeLink) {
			typeLink.href = btn.getAttribute('data-type-url');
			if (typeLink.firstChild && typeLink.firstChild.nodeType === 3) {
				typeLink.firstChild.nodeValue = label + ' specs ';
			}
		}
		if (cta) {
			cta.href = btn.getAttribute('data-builder-url');
		}
		swatches.forEach(function (sw) {
			sw.disabled = !faceLit[style];
		});
	});

	radioGroup(swatches, function (btn) {
		var color = btn.getAttribute('data-color');
		stage.style.setProperty('--face', color);
		// A black face gives no glow of its own: the CSS shows a lit edge instead.
		stage.setAttribute('data-face', color === '#111111' ? 'dark' : 'light');
	});

	if (dayBtn) {
		dayBtn.addEventListener('click', function () {
			var day = dayBtn.getAttribute('aria-pressed') !== 'true';
			dayBtn.setAttribute('aria-pressed', day ? 'true' : 'false');
			stage.setAttribute('data-time', day ? 'day' : 'night');
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
}());
