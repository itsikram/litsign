/**
 * Channel letter measuring, done the way B2Sign's channel letter builder measures
 * (b2sign.com /channel_letter/static/js/main.js, read 2026-11-15):
 * - A text's height is the height of all its letters together: from the top of the tallest
 *   letter to the bottom of the lowest (B2Sign's Dg()).
 * - Every separate piece of a letter is its own channel letter, sized by its own outline: the
 *   dot of an i, the two dots of a colon, the three parts of a % (Ug.splitTextByPathes()).
 * - A piece's size is its width and height rounded to 0.1" (OA(pw / 72, 1)); prices use its
 *   longer side, rounded up to the inch.
 * - A text's width runs from the left edge of its first letter to the right edge of its last,
 *   adding up each letter's advance without kerning (Ug.updateWidth()).
 *
 * B2Sign reads outlines from its own font files; this reads them from the browser's canvas in
 * the font the customer chose: each letter is drawn three times as large and its pieces and
 * edges are read from the ink, and its advance comes from the font. The builder (js/cl.js) and the
 * product page (js/main.js) both use this file, so the same text, font and height measure the
 * same on both, and the cart prices those numbers (retails_cl_text_letters() in inc/pricing.php).
 */
(function () {
  'use strict';

  // Font size of the outline metrics, in px (the numbers sent to the server are in these units).
  var SIZE = 200;
  // Pieces are found on the letter drawn this many times larger.
  var RASTER = 3;

  var metricCanvas = document.createElement('canvas');
  var metricContext = metricCanvas.getContext('2d');
  var rasterCanvas = document.createElement('canvas');
  var rasterContext = rasterCanvas.getContext('2d', { willReadFrequently: true });
  var cache = new Map();
  // Bumped whenever measurements may have changed (a web font finished loading), so drawings refit.
  var version = 0;
  var forget = function () {
    cache.clear();
    if (typeof middles !== 'undefined') middles.clear();
    version++;
  };

  if (document.fonts && document.fonts.addEventListener) {
    // A web font that finishes loading changes every measurement taken in its fallback.
    document.fonts.addEventListener('loadingdone', forget);
  }

  // Font codes that are not font names, as the fonts B2Sign cuts them in: [weight, family]. The
  // builder codes Arial Black "arial-black", and the product page offers "Arial bold".
  var FONTS = {
    'arial-black': ['', 'Arial Black'],
    'arial black': ['', 'Arial Black'],
    'arial bold': ['bold', 'Arial'],
  };
  var fontString = function (px, family) {
    var code = String(family || 'Arial').replace(/"/g, '');
    var font = FONTS[code.trim().toLowerCase()] || ['', code];
    return (font[0] ? font[0] + ' ' : '') + px + 'px "' + font[1] + '", Arial, sans-serif';
  };

  // One letter's outline and separate pieces, read from the letter drawn RASTER times larger: the
  // 8-connected areas of ink are its pieces, and each edge is placed to a fraction of a pixel from
  // the anti-aliased coverage next to it (the canvas's own text metrics are rounded to about 1/64
  // of the font size, too coarse to round to 0.1" the way B2Sign does). Returns, in outline units
  // at SIZE px, the ink box from the letter's origin (left and right of it, a above and d below the
  // baseline) and pieces [[width, height], ...], or null when it can't be drawn.
  var readLetter = function (ch, family, coarse) {
    // The canvas's own box, widened by a tenth of the font size so no ink is cut off.
    var margin = SIZE * 0.1 * RASTER + 4;
    var originX = Math.ceil(margin + Math.max(0, coarse.left) * RASTER);
    var baseline = Math.ceil(margin + Math.max(0, coarse.ascent) * RASTER);
    var width = originX + Math.ceil(Math.max(0, coarse.right) * RASTER + margin);
    var height = baseline + Math.ceil(Math.max(0, coarse.descent) * RASTER + margin);
    if (!rasterContext || width * height > 6000000) {
      return null;
    }
    rasterCanvas.width = width;
    rasterCanvas.height = height;
    rasterContext.clearRect(0, 0, width, height);
    rasterContext.font = fontString(SIZE * RASTER, family);
    rasterContext.textBaseline = 'alphabetic';
    rasterContext.textAlign = 'left';
    rasterContext.fillStyle = '#000';
    rasterContext.fillText(ch, originX, baseline);
    var ink;
    try {
      ink = rasterContext.getImageData(0, 0, width, height).data;
    } catch (e) {
      return null;
    }
    var labels = new Int32Array(width * height);
    var boxes = [];
    var stack = [];
    for (var start = 0; start < width * height; start++) {
      if (labels[start] || ink[start * 4 + 3] < 128) {
        continue;
      }
      var id = boxes.length + 1;
      var box = [width, height, -1, -1, 0];
      labels[start] = id;
      stack.push(start);
      while (stack.length) {
        var at = stack.pop();
        var x = at % width;
        var y = (at - x) / width;
        if (x < box[0]) box[0] = x;
        if (y < box[1]) box[1] = y;
        if (x > box[2]) box[2] = x;
        if (y > box[3]) box[3] = y;
        box[4]++;
        for (var dy = -1; dy <= 1; dy++) {
          var ny = y + dy;
          if (ny < 0 || ny >= height) continue;
          for (var dx = -1; dx <= 1; dx++) {
            var nx = x + dx;
            if (nx < 0 || nx >= width) continue;
            var next = ny * width + nx;
            if (!labels[next] && ink[next * 4 + 3] >= 128) {
              labels[next] = id;
              stack.push(next);
            }
          }
        }
      }
      boxes.push(box);
    }
    // Specks of anti-aliasing are not pieces: a piece covers at least 1% of the font size squared.
    var minArea = Math.pow(SIZE * RASTER * 0.01, 2);
    boxes = boxes.filter(function (b) { return b[4] >= minArea; });
    if (!boxes.length) {
      return null;
    }
    var alpha = function (x, y) {
      return x < 0 || y < 0 || x >= width || y >= height ? 0 : ink[(y * width + x) * 4 + 3];
    };
    var rowMax = function (y, from, to) {
      var most = 0;
      for (var x = from; x <= to; x++) most = Math.max(most, alpha(x, y));
      return most / 255;
    };
    var colMax = function (x, from, to) {
      var most = 0;
      for (var y = from; y <= to; y++) most = Math.max(most, alpha(x, y));
      return most / 255;
    };
    // Each edge to a fraction of a pixel: a faint pixel just outside the piece adds the share of it
    // that is covered; else the edge pixel itself is only partly covered.
    var edges = boxes.map(function (b) {
      var above = rowMax(b[1] - 1, b[0] - 1, b[2] + 1);
      var below = rowMax(b[3] + 1, b[0] - 1, b[2] + 1);
      var before = colMax(b[0] - 1, b[1] - 1, b[3] + 1);
      var after = colMax(b[2] + 1, b[1] - 1, b[3] + 1);
      return {
        top: above > 0 ? b[1] - above : b[1] + 1 - rowMax(b[1], b[0], b[2]),
        bottom: below > 0 ? b[3] + 1 + below : b[3] + rowMax(b[3], b[0], b[2]),
        left: before > 0 ? b[0] - before : b[0] + 1 - colMax(b[0], b[1], b[3]),
        right: after > 0 ? b[2] + 1 + after : b[2] + colMax(b[2], b[1], b[3]),
      };
    });
    var inkLeft = Math.min.apply(null, edges.map(function (e) { return e.left; }));
    var inkRight = Math.max.apply(null, edges.map(function (e) { return e.right; }));
    var inkTop = Math.min.apply(null, edges.map(function (e) { return e.top; }));
    var inkBottom = Math.max.apply(null, edges.map(function (e) { return e.bottom; }));
    edges.sort(function (a, b) { return a.left - b.left || a.top - b.top; });
    return {
      left: (originX - inkLeft) / RASTER,
      right: (inkRight - originX) / RASTER,
      a: (baseline - inkTop) / RASTER,
      d: (inkBottom - baseline) / RASTER,
      pieces: edges.map(function (e) { return [(e.right - e.left) / RASTER, (e.bottom - e.top) / RASTER]; }),
    };
  };

  // One character in a font: its outline box (w; a and d above and below the baseline), advance,
  // left and right ink edges from its origin, and its pieces, in outline units at SIZE px.
  var charInfo = function (ch, family) {
    var key = family + '|' + ch;
    if (cache.has(key)) {
      return cache.get(key);
    }
    metricContext.font = fontString(SIZE, family);
    var m = metricContext.measureText(ch);
    var coarse = {
      left: Number(m.actualBoundingBoxLeft) || 0,
      right: Number.isFinite(m.actualBoundingBoxRight) ? m.actualBoundingBoxRight : m.width,
      ascent: Number(m.actualBoundingBoxAscent) || 0,
      descent: Number(m.actualBoundingBoxDescent) || 0,
    };
    var hasInk = !/\s/.test(ch) && coarse.left + coarse.right > 0 && coarse.ascent + coarse.descent > 0;
    var read = hasInk ? readLetter(ch, family, coarse) : null;
    var info = read
      ? { left: read.left, right: read.right, a: read.a, d: read.d, pieces: read.pieces }
      : { left: coarse.left, right: coarse.right, a: coarse.ascent, d: coarse.descent, pieces: hasInk ? [[coarse.left + coarse.right, coarse.ascent + coarse.descent]] : [] };
    info.w = Math.max(0, info.left + info.right);
    info.adv = Number(m.width) || 0;
    cache.set(key, info);
    return info;
  };

  /**
   * A text measured in a font, in outline units at SIZE px (height-free, so one measurement prices
   * every height): letters (non-space characters) with their box [w, a, d] and pieces [[w, h], ...],
   * the text's width as B2Sign measures it, and ink, the height of all the letters together; inkLeft
   * (where the first letter's ink starts from the text's origin) and maxA (the tallest letter above
   * the baseline) for drawing it. Spaces are trimmed and collapsed, as the product page sends the
   * text, unless raw (the builder draws the text as typed). Null for a text with no letters or no canvas.
   */
  var measure = function (text, family, raw) {
    if (!metricContext || typeof metricContext.measureText !== 'function') {
      return null;
    }
    var chars = Array.from(raw ? String(text || '') : String(text || '').trim().replace(/\s+/g, ' '));
    if (!chars.length) {
      return null;
    }
    var fam = String(family || 'Arial');
    var infos = chars.map(function (ch) { return charInfo(ch, fam); });
    var letters = [];
    var width = 0;
    // From the text's origin: where the first letter's ink starts and the last one's ends.
    var inkLeft = null;
    var inkRight = 0;
    infos.forEach(function (info, i) {
      if (!/\s/.test(chars[i])) {
        letters.push({ ch: chars[i], w: info.w, a: info.a, d: info.d, pieces: info.pieces });
        if (null === inkLeft) inkLeft = width - info.left;
        inkRight = width + info.right;
      }
      width += info.adv;
    });
    if (!letters.length) {
      return null;
    }
    var first = infos[0];
    var last = infos[infos.length - 1];
    width += first.left - last.adv + last.right;
    var maxA = Math.max.apply(null, letters.map(function (l) { return l.a; }));
    var ink = maxA + Math.max.apply(null, [0].concat(letters.map(function (l) { return l.d; })));
    if (!(ink > 0)) {
      return null;
    }
    return {
      letters: letters,
      // B2Sign's width; as typed (raw), from the first letter's ink to the last one's.
      width: raw ? Math.max(0, inkRight - inkLeft) : Math.max(0, width),
      ink: ink,
      inkLeft: inkLeft,
      maxA: maxA,
    };
  };

  // Where Konva's "middle" text baseline sits above the alphabetic baseline, in outline units at SIZE px:
  // the builder draws letters on it (js/cl.js LetterText).
  var middles = new Map();
  var middle = function (family) {
    var fam = String(family || 'Arial');
    if (!middles.has(fam)) {
      metricContext.font = fontString(SIZE, fam);
      metricContext.textBaseline = 'alphabetic';
      var alpha = metricContext.measureText('H');
      metricContext.textBaseline = 'middle';
      var mid = metricContext.measureText('H');
      metricContext.textBaseline = 'alphabetic';
      var value = Number.isFinite(alpha.fontBoundingBoxAscent) && Number.isFinite(mid.fontBoundingBoxAscent)
        ? alpha.fontBoundingBoxAscent - mid.fontBoundingBoxAscent
        : (Number(alpha.actualBoundingBoxAscent) || 0) - (Number(mid.actualBoundingBoxAscent) || 0);
      middles.set(fam, value);
    }
    return middles.get(fam);
  };

  // To 0.1", as B2Sign rounds every size (and as inc/pricing.php rounds the page's measurement).
  var tenth = function (inches) { return Math.round(inches * 10) / 10; };

  /**
   * A measurement at a text height in inches: per letter its box [w, h] (glyphs) and its pieces
   * [[w, h], ...], and the text's width, all in inches rounded to 0.1".
   */
  var at = function (measured, height) {
    height = Number(height);
    if (!measured || !(height > 0)) {
      return null;
    }
    var scale = height / measured.ink;
    return {
      glyphs: measured.letters.map(function (l) { return [tenth(l.w * scale), tenth((l.a + l.d) * scale)]; }),
      pieces: measured.letters.map(function (l) { return l.pieces.map(function (p) { return [tenth(p[0] * scale), tenth(p[1] * scale)]; }); }),
      width: tenth(measured.width * scale),
    };
  };

  /**
   * The measurement the product page sends (field cl_measure, read by retails_cl_request_measure()):
   * {"v": 2, "c": [[w, a, d, [[w, h], ...]], ...] per letter, "t": width}, outline units at SIZE px.
   */
  var payload = function (measured) {
    if (!measured) {
      return '';
    }
    return JSON.stringify({
      v: 2,
      c: measured.letters.map(function (l) { return [l.w, l.a, l.d, l.pieces]; }),
      t: measured.width,
    });
  };

  window.SfsLetterMeasure = {
    SIZE: SIZE,
    measure: measure,
    at: at,
    payload: payload,
    middle: middle,
    cssFont: fontString,
    version: function () { return version; },
    clear: forget,
  };
})();
