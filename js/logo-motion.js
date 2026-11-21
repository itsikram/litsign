/*
 * Animated header logo. Notes and the measuring tool: inc/logo-motion.php,
 * utils/logo-motion/slice.py.
 */
(function () {
	'use strict';

	var d = document;
	var root = d.documentElement;
	if ('full' !== root.getAttribute('data-motion') || !window.CSS || !CSS.supports('clip-path', 'polygon(0 0, 1px 0, 0 1px)')) {
		return;
	}

	// Measured by utils/logo-motion/slice.py from img/brand/sfs-logo-1910.png
	// (the owner's brand artwork with the realistic finish from
	// utils/logo-motion/realistic.py, 2026-11-20). Picture pixels.
	var W = 1910;
	var H = 323;
	var TOP = 32;        // above the tallest letter
	var BASE = 279;      // empty row between the letters (and tile, sign) and the tagline
	var WORD_LEFT = 252; // empty column between the red tile and the first letter
	var SIGN_LEFT = 1656; // empty column between the last letter and the sign
	var MARK = [5,23,244,264];   // red tile with the silver S: left, top, right, bottom
	var SIGN = [1657,5,1905,226]; // "online .com" plate and pole
	var POLE_X = 1782.0;
	// Cut lines between neighbouring letters: S|t, t|o, o|r ... g|n.
	var SEAMS = [
		[[380,32], [380,278]],
		[[460,32], [461,125], [458,144], [458,180], [461,209], [460,278]],
		[[583,32], [583,278]],
		[[663,32], [665,125], [659,128], [657,134], [657,136], [660,137], [660,180], [662,186], [662,278]],
		[[774,32], [773,128], [778,130], [775,183], [775,278]],
		[[860,32], [858,89], [859,278]],
		[[933,32], [933,86], [937,89], [937,118], [930,123], [929,127], [929,181], [933,192], [933,278]],
		[[1053,32], [1053,278]],
		[[1163,32], [1162,120], [1166,123], [1166,141], [1164,142], [1163,278]],
		[[1243,32], [1244,278]],
		[[1365,32], [1365,278]],
		[[1412,32], [1412,278]],
		[[1535,32], [1535,278]]
	];

	var pct = function (value, total) {
		return Math.round(value / total * 1e4) / 100 + '%';
	};
	var polygon = function (points) {
		return 'polygon(' + points.map(function (p) {
			return pct(p[0], W) + ' ' + pct(p[1], H);
		}).join(',') + ')';
	};
	// The part of a cut line between two rows, top to bottom.
	var between = function (line, from, to) {
		var out = [];
		var at = function (a, b, y) {
			return [a[0] + (b[0] - a[0]) * (y - a[1]) / (b[1] - a[1]), y];
		};
		for (var i = 0; i < line.length; i++) {
			var p = line[i];
			var q = line[i - 1];
			if (q && q[1] < from && p[1] > from) {
				out.push(at(q, p, from));
			}
			if (q && q[1] < to && p[1] > to) {
				out.push(at(q, p, to));
			}
			if (p[1] >= from && p[1] <= to) {
				out.push(p);
			}
		}
		var first = out[0];
		var last = out[out.length - 1];
		if (first[1] > from) {
			out.unshift([first[0], from]);
		}
		if (last[1] < to) {
			out.push([last[0], to]);
		}
		return out;
	};

	// Shapes, as CSS clip-paths. WORD is everything except the mark and the
	// sign (the letters, and the tagline under all three): the area the light
	// sweep may cross. While the mark or the sign turns, that piece is cut out
	// of the real <img> so it never shows double.
	var WORD_AREA = [[WORD_LEFT, 0], [SIGN_LEFT, 0], [SIGN_LEFT, BASE], [W, BASE], [W, H], [0, H], [0, BASE], [WORD_LEFT, BASE]];
	// The sweep crosses the letters only: the tagline is drawn as text now
	// (inc/logo-motion.php), and on the dark footer the sweep would light up
	// the picture's own (hidden) tagline.
	var WORD = polygon([[WORD_LEFT, 0], [SIGN_LEFT, 0], [SIGN_LEFT, BASE], [WORD_LEFT, BASE]]);
	var SIGN_CUT = polygon([[SIGN_LEFT, 0], [W, 0], [W, BASE], [SIGN_LEFT, BASE]]);
	var NO_MARK = [[WORD_LEFT, 0], [W, 0], [W, H], [0, H], [0, BASE], [WORD_LEFT, BASE]];
	var NO_SIGN = [[0, 0], [SIGN_LEFT, 0], [SIGN_LEFT, BASE], [W, BASE], [W, H], [0, H]];
	// The tile, 2px larger all round, as an inset() from the picture's edges.
	var MARK_CUT = 'inset(' + pct(MARK[1] - 2, H) + ' ' + pct(W - MARK[2] - 2, W) + ' ' + pct(H - MARK[3] - 2, H) + ' ' + pct(MARK[0] - 2, W) + ')';
	var SEEN = 'visibility:visible;';
	var TURN = 'transform:perspective(var(--p)) rotateY(calc(';

	// Timeline of one run (1.9 s): mark 0-0.95 s, letters 0.16-1.28 s, sign
	// 0.57-1.615 s, light sweep 1.02-1.82 s. Every copy is hidden except while
	// it moves, so the logo at rest is always the untouched <img>.
	var css = '.lm-host{position:relative}'
		// No z-index here: a stacking context would cut the sweep's blend off
		// from the <img> underneath.
		+ '.lm{position:absolute;display:none;pointer-events:none;--a:1}'
		+ '.lm-play>.lm{display:block}'
		+ '.lm-play img{animation:lm-img 1.9s step-end}'
		+ '.lm span,.lm i{position:absolute;inset:0}'
		+ '.lm-l,.lm-sign,.lm-mark i{background:var(--lm-src) 0 0/100% 100% no-repeat}'
		+ '.lm-l,.lm-sign,.lm-mark{visibility:hidden}'
		// Mark: swings on its vertical axis like a sign on a hinge; the darker
		// copies behind the face give it thickness while it is turned.
		+ '.lm-mark{transform-style:preserve-3d;transform-origin:' + pct((MARK[0] + MARK[2]) / 2, W) + ' ' + pct((MARK[1] + MARK[3]) / 2, H) + ';animation:lm-swing .95s cubic-bezier(.33,0,.3,1)}'
		+ '.lm-mark i{clip-path:' + MARK_CUT + ';-webkit-backface-visibility:hidden;backface-visibility:hidden}'
		+ '.lm-mark i[style]{filter:brightness(.45);transform:translateZ(calc(var(--p)*var(--k)*-.0025));animation:lm-depth .95s linear}'
		// Letters: copies that pop forward one after another over the still
		// logo, like channel letters lighting up in turn.
		+ '.lm-l{animation:lm-pop .5s cubic-bezier(.3,.5,.3,1) calc(.16s + var(--i)*48ms)}'
		// Sign: turns a little on its pole.
		+ '.lm-sign{clip-path:' + SIGN_CUT + ';transform-origin:' + pct(POLE_X, W) + ' ' + pct((SIGN[1] + SIGN[3]) / 2, H) + ';animation:lm-turn 1.045s cubic-bezier(.33,0,.3,1) .57s}'
		// One light sweep across the word, cut to the logo's own shape.
		+ '.lm-sweep{clip-path:' + WORD + ';-webkit-mask:var(--lm-src) 0 0/100% 100% no-repeat;mask:var(--lm-src) 0 0/100% 100% no-repeat;mix-blend-mode:overlay}'
		+ '.lm .lm-sweep i{right:auto;width:15%;background:linear-gradient(114deg,rgba(255,255,255,0) 20%,#fff 50%,rgba(255,255,255,0) 80%);opacity:var(--a);transform:translateX(-60%);animation:lm-sweep .8s cubic-bezier(.45,0,.4,1) 1.02s both}'
		+ '@keyframes lm-swing{0%,100%{' + SEEN + 'transform:none}26%{' + SEEN + TURN + '-42deg*var(--a))) scale(1.03)}56%{' + SEEN + TURN + '20deg*var(--a)))}80%{' + SEEN + TURN + '-7deg*var(--a)))}}'
		+ '@keyframes lm-depth{0%,100%{opacity:0}6%,94%{opacity:1}}'
		+ '@keyframes lm-pop{0%,100%{' + SEEN + 'transform:none;filter:none}36%{' + SEEN + 'transform:scale(calc(1 + .12*var(--a)));filter:brightness(calc(1 + .14*var(--a)))}}'
		+ '@keyframes lm-turn{0%,100%{' + SEEN + 'transform:none}26%{' + SEEN + TURN + '40deg*var(--a)))}56%{' + SEEN + TURN + '-18deg*var(--a)))}80%{' + SEEN + TURN + '6deg*var(--a)))}}'
		+ '@keyframes lm-sweep{to{transform:translateX(690%)}}';

	// The picture may not fill the <img> box exactly (object-fit), so the
	// <img>'s own cut-outs are worked out for its box at play time: picture
	// pixels -> drawn size (dw x dh at offset ox/oy) -> percentages of the box.
	var imgStyle = null;
	var clipImg = function (dw, dh, ox, oy, width, height) {
		var at = function (points) {
			return 'polygon(' + points.map(function (p) {
				return Math.round((p[0] / W * dw + ox) / width * 1e4) / 100 + '% ' + Math.round((p[1] / H * dh + oy) / height * 1e4) / 100 + '%';
			}).join(',') + ')';
		};
		var text = '@keyframes lm-img{0%{clip-path:' + at(NO_MARK) + '}30%{clip-path:' + at(WORD_AREA) + '}50%{clip-path:' + at(NO_SIGN) + '}85%,100%{clip-path:none}}';
		if (!imgStyle) {
			imgStyle = d.createElement('style');
			imgStyle.id = 'retails-logo-motion-img';
			d.head.appendChild(imgStyle);
		}
		if (imgStyle.textContent !== text) {
			imgStyle.textContent = text;
		}
	};
	// Offset along one axis for object-position (a percentage of the spare
	// room, or a length).
	var shift = function (value, room) {
		return /%$/.test(value) ? room * parseFloat(value) / 100 : parseFloat(value) || 0;
	};

	var LENGTH = 1900; // ms, the whole sequence
	var styled = false;
	var playing = null;
	var endTimer = 0;
	var lastEnd = 0;
	var watcher = null;

	var build = function (host) {
		if (!styled) {
			var style = d.createElement('style');
			style.id = 'retails-logo-motion';
			style.textContent = css;
			d.head.appendChild(style);
			styled = true;
		}
		var html = '<span class="lm-mark"><i style="--k:3"></i><i style="--k:2"></i><i style="--k:1"></i><i></i></span>';
		var last = SEAMS.length;
		for (var k = 0; k <= last; k++) {
			var top = TOP;
			var bottom = BASE;
			var left = k ? between(SEAMS[k - 1], top, bottom) : [[WORD_LEFT, top], [WORD_LEFT, bottom]];
			var right = k < last ? between(SEAMS[k], top, bottom) : [[SIGN_LEFT, top], [SIGN_LEFT, bottom]];
			var xs = left.concat(right).map(function (p) { return p[0]; });
			var middle = (Math.min.apply(null, xs) + Math.max.apply(null, xs)) / 2;
			html += '<span class="lm-l" style="--i:' + k + ';clip-path:' + polygon(left.concat(right.reverse()))
				+ ';transform-origin:' + pct(middle, W) + ' ' + pct(BASE, H) + '"></span>';
		}
		html += '<span class="lm-sign"></span><span class="lm-sweep"><i></i></span>';

		var layer = d.createElement('span');
		layer.className = 'lm';
		layer.setAttribute('aria-hidden', 'true');
		layer.innerHTML = html;
		host.classList.add('lm-host');
		host.appendChild(layer);
		return layer;
	};

	// A logo picture that can play: on screen, loaded, and the same shape as the
	// measured picture. naturalWidth/Height are in CSS pixels for a srcset file
	// and rounded, so allow 1.5px.
	var ready = function (img) {
		return !!img && 'IMG' === img.tagName && img.offsetWidth && img.complete && img.naturalWidth
			&& Math.abs(img.naturalHeight - img.naturalWidth * H / W) < 1.5;
	};
	// The header logo that is on screen: the desktop header above 991px, the
	// original header below.
	var current = function () {
		var logos = d.querySelectorAll('.ssx-logo .header-logo, .sh-logo .header-logo');
		for (var i = 0; i < logos.length; i++) {
			if (ready(logos[i])) {
				return logos[i];
			}
		}
		return null;
	};
	// The footer logo (same artwork with a light tagline, same measurements;
	// owner 2026-11-20: "make sure that footer logo is animated as well").
	var FOOTER = '.sf-brand__logo';
	var footerLogo = function () {
		var img = d.querySelector(FOOTER + ' img');
		return ready(img) ? img : null;
	};

	var stop = function () {
		if (!playing) {
			return;
		}
		clearTimeout(endTimer);
		playing.classList.remove('lm-play');
		playing = null;
		lastEnd = Date.now();
		if (watcher) {
			watcher.disconnect();
		}
	};

	var play = function (strength, which) {
		if (playing || d.hidden) {
			return false;
		}
		// A given logo (the footer's, possibly not ready: then nothing plays),
		// otherwise the header logo on screen.
		var img = undefined === which ? current() : which;
		var host = img && img.closest('a');
		if (!host) {
			return false;
		}
		var layer = host.querySelector('.lm') || build(host);
		var box = img.getBoundingClientRect();
		var hostBox = host.getBoundingClientRect();
		// Where the whole picture is drawn: the layer covers all of it, also
		// the trimmed rows outside the <img> box (they are transparent).
		// A box of (nearly) the picture's shape is filled by it: the smaller
		// files' heights are rounded, so their shape differs a little from W x H.
		var view = getComputedStyle(img);
		var fit = view.objectFit;
		var ratio = W / H;
		var wide = box.width / box.height > ratio;
		var dw = box.width;
		var dh = box.height;
		if (Math.abs(box.width / box.height / ratio - 1) > .02 && /^(contain|cover|scale-down)$/.test(fit)) {
			if (('cover' === fit) === wide) {
				dh = box.width / ratio;
			} else {
				dw = box.height * ratio;
			}
		}
		var position = (view.objectPosition || '50% 50%').split(' ');
		var ox = shift(position[0], box.width - dw);
		var oy = shift(position[1] || '50%', box.height - dh);
		clipImg(dw, dh, ox, oy, box.width, box.height);
		layer.style.cssText = 'left:' + (box.left - hostBox.left + ox) + 'px;top:' + (box.top - hostBox.top + oy) + 'px;width:' + dw + 'px;height:' + dh
			+ 'px;--a:' + strength + ';--p:' + Math.round(dw * 1.75) + 'px;--lm-src:url("' + (img.currentSrc || img.src).replace(/["\\\n]/g, '') + '")';
		host.classList.add('lm-play');
		playing = host;
		endTimer = setTimeout(stop, LENGTH);
		// The header shrinks its logo on scroll: end early instead of drifting.
		if (window.ResizeObserver) {
			var first = true;
			watcher = new ResizeObserver(function () {
				if (first) {
					first = false;
				} else {
					stop();
				}
			});
			watcher.observe(img);
		}
		return true;
	};

	// Once after the page has loaded (after the page loader has gone, if it
	// is covering the page), then two softer runs while the visitor is still
	// at the top of the page. After that only on hover.
	var runs = 0;
	var tries = 0;
	var again = function () {
		if (window.scrollY < 140) {
			play(.6);
		}
	};
	var next = function () {
		if (play(1)) {
			runs = 1;
			setTimeout(again, 10000);
			setTimeout(again, 23000);
		} else if (d.hidden) {
			// Opened in a background tab: play when the visitor first looks.
			d.addEventListener('visibilitychange', function shown() {
				if (!d.hidden) {
					d.removeEventListener('visibilitychange', shown);
					setTimeout(next, 400);
				}
			});
		} else if (++tries < 4) {
			// The logo picture is still on its way.
			setTimeout(next, 1500);
		}
	};
	// The first run waits until the browser is idle (at most 2.5 s more): right
	// after load the theme swaps in its full stylesheets and releases photos,
	// and on slow phones a run started then shares those busy frames.
	var calm = function () {
		if (window.requestIdleCallback) {
			requestIdleCallback(next, { timeout: 2500 });
		} else {
			next();
		}
	};
	var start = function () {
		// The loader's classes may carry either prefix (the theme is moving
		// from retails- to retails- names).
		var loader = d.querySelector('.retails-page-loader, .retails-page-loader');
		var prefix = loader && loader.classList.contains('retails-page-loader') ? 'retails' : 'retails';
		var covered = loader && !loader.classList.contains('is-hidden')
			&& !root.classList.contains(prefix + '-page-loader-landing')
			&& !root.classList.contains(prefix + '-page-loader-failsafe');
		if (covered && window.MutationObserver) {
			var seen = new MutationObserver(function () {
				if (loader.classList.contains('is-hidden')) {
					seen.disconnect();
					setTimeout(calm, 250);
				}
			});
			seen.observe(loader, { attributes: true, attributeFilter: ['class'] });
			return;
		}
		setTimeout(calm, 450);
	};

	if ('complete' === d.readyState) {
		start();
	} else {
		window.addEventListener('load', start);
	}
	window.addEventListener('resize', stop);
	d.addEventListener('visibilitychange', stop);

	// Footer logo: plays once each time it scrolls into view (mostly visible),
	// at full strength; waits its turn if the header logo is still playing.
	var footerShown = false;
	var footerTries = 0;
	var footerRun = function () {
		// Retries cover a lazy picture still loading or a header run in progress.
		if (footerShown && !play(1, footerLogo()) && !d.hidden && ++footerTries < 6) {
			setTimeout(footerRun, 1200);
		}
	};
	if (window.IntersectionObserver) {
		var footerHost = d.querySelector(FOOTER);
		if (footerHost) {
			new IntersectionObserver(function (entries) {
				var seen = entries[0].intersectionRatio >= .75;
				if (seen && !footerShown) {
					footerShown = true;
					footerTries = 0;
					setTimeout(footerRun, 250);
				} else if (!seen) {
					footerShown = false;
				}
			}, { threshold: [0, .75] }).observe(footerHost);
		}
	}

	if (matchMedia('(hover: hover) and (pointer: fine)').matches) {
		d.addEventListener('mouseover', function (event) {
			var host = event.target.closest && event.target.closest('.ssx-logo, .sh-logo a, ' + FOOTER);
			if (host && !playing && Date.now() - lastEnd > 700 && !host.contains(event.relatedTarget)) {
				if (host.matches(FOOTER)) {
					play(.8, footerLogo());
				} else if (runs) {
					play(.8);
				}
			}
		});
	}
}());
