/**
 * Channel letter product page: the builder's options (template-parts/cl-product-options.php).
 * Keeps the text preview in the chosen font and colors, shows the approximate width, and
 * fills the raceway length from it. The price comes from the live server quote in
 * js/main.js (wholesale_price_quote), which reads these fields.
 */
(() => {
  const init = () => {
    const box = document.querySelector('[data-cl-options]');
    if (!box) return;
    const text = box.querySelector('#letterInput');
    const output = box.querySelector('#letter-output');
    const font = box.querySelector('#select-font');
    const height = box.querySelector('#clHeight');
    const face = box.querySelector('#clFace');
    const ret = box.querySelector('#clReturn');
    const trimcap = box.querySelector('#clTrimcap');
    const raceway = box.querySelector('#clRaceway');
    const racewayLength = box.querySelector('#clRacewayLength');
    const racewayLabel = box.querySelector('.cl-opt-raceway-length');
    const widthText = box.querySelector('#clApproxWidth');

    // js/main.js marks the first option of every .form-select selected once jQuery is
    // ready (with option groups, the first of each group), which would start this form
    // on 8" letters and the last font group. Put back the defaults the page was built with.
    const restoreDefaults = () => {
      box.querySelectorAll('select').forEach((select) => {
        select.value = select.dataset.default || (select.options[0] ? select.options[0].value : '');
      });
      toggleRaceway();
      update();
    };

    const colorOf = (field) => {
      if (!field) return '';
      const option = field.tagName === 'SELECT' ? field.options[field.selectedIndex] : field;
      const code = option ? String(option.dataset.color || '').trim() : '';
      return code && CSS.supports('color', code) ? code : '';
    };

    // The text's width as B2Sign measures it, in the chosen font (js/cl-measure.js, as the builder
    // and the server quote); without it, the same estimate as wholesale_cl_approx_width() in inc/pricing.php.
    const approxWidth = () => {
      const inches = parseFloat(height ? height.value : 0) || 0;
      const measure = window.SfsLetterMeasure;
      if (measure && inches > 0 && text.value.trim()) {
        const sized = measure.at(measure.measure(text.value, font ? font.value : 'Arial'), inches);
        if (sized && sized.width > 0) return Math.round(sized.width * 10) / 10;
      }
      let width = 0;
      [...(text.value || '').replace(/\s+/g, '')].forEach((char) => {
        const isCap = char === char.toUpperCase() && char !== char.toLowerCase();
        width += isCap ? 6.33 + ((inches - 8) * (35.65 - 6.33)) / 37 : 4.37 + ((inches - 8) * (24.73 - 4.37)) / 37;
      });
      return Math.round(width * 10) / 10;
    };

    const paint = () => {
      const faceColor = colorOf(face);
      const returnColor = colorOf(ret) || faceColor;
      const trimColor = colorOf(trimcap);
      output.style.color = text.value.trim() ? faceColor || '#000' : '';
      output.style.textShadow = text.value.trim() && returnColor ? `3px 3px 2px ${returnColor}` : '';
      output.style.webkitTextStroke = text.value.trim() && trimColor ? `2px ${trimColor}` : '';
      if (font) output.style.fontFamily = `"${font.value}", Arial, sans-serif`;
      box.querySelectorAll('.cl-opt-color').forEach((wrap) => {
        const swatch = wrap.querySelector('.cl-opt-swatch');
        const select = wrap.querySelector('select');
        if (swatch && select) swatch.style.background = colorOf(select) || 'transparent';
      });
    };

    const update = () => {
      const width = approxWidth();
      if (widthText) widthText.innerHTML = `Approximate width: <b>${width.toFixed(1)} Inches</b>`;
      if (racewayLength && !racewayLength.dataset.touched) {
        racewayLength.value = width > 0 ? Math.max(12, Math.ceil(width)) : '';
      }
      // js/main.js resets the preview color 10ms after each keystroke; paint after it.
      setTimeout(paint, 30);
    };

    const toggleRaceway = () => {
      const on = raceway && raceway.value === 'yes';
      if (racewayLabel) racewayLabel.hidden = !on;
      if (racewayLength) {
        racewayLength.disabled = !on;
        racewayLength.required = on;
      }
    };

    text.addEventListener('input', update);
    box.addEventListener('change', (event) => {
      if (event.target === racewayLength) racewayLength.dataset.touched = '1';
      if (event.target === raceway) toggleRaceway();
      update();
    });
    if (racewayLength) racewayLength.addEventListener('input', () => { racewayLength.dataset.touched = '1'; });
    // Back/forward cache and autofill can restore values without events.
    window.addEventListener('pageshow', () => { toggleRaceway(); update(); });
    // A letter font that finishes downloading changes the measured width.
    if (document.fonts && document.fonts.addEventListener) {
      document.fonts.addEventListener('loadingdone', () => setTimeout(update, 0));
    }

    toggleRaceway();
    update();
    // Ready callbacks run in order, so this one runs after js/main.js's.
    if (window.jQuery) window.jQuery(restoreDefaults);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
