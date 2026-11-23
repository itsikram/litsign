jQuery(function ($) {
  $('.sfs-hero-fields').each(function () {
    const root = $(this);
    let presets = {};
    try { presets = JSON.parse(root.attr('data-presets') || '{}'); } catch (e) { /* Keep preview usable. */ }
    const value = (panel, key) => panel.find('input, select').filter(function () {
      return this.name && this.name.endsWith('[' + key + ']');
    }).val();
    const rgba = (hex, opacity) => {
      let color = /^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i.test(hex || '') ? hex.slice(1) : '031835';
      if (color.length === 3) color = color.split('').map(c => c + c).join('');
      return 'rgba(' + [0, 2, 4].map(i => parseInt(color.slice(i, i + 2), 16)).join(',') + ',' + (Number(opacity) / 100) + ')';
    };
    const preview = panel => {
      const viewport = panel.attr('data-viewport');
      const type = value(panel, 'overlay_type');
      panel.attr('data-overlay-type', type);
      const image = panel.find('.sfs-hero-preview').attr('data-image') || '';
      const preset = presets[root.find('[name="sfs_hero_overlay"]').val()] || presets.left || {};
      let overlay = preset[viewport === 'mobile' ? 'm' : 'd'] || 'linear-gradient(#03183599,#03183599)';
      if (type === 'solid') {
        const first = rgba(value(panel, 'overlay_color'), value(panel, 'overlay_opacity'));
        overlay = 'linear-gradient(' + first + ',' + first + ')';
      } else if (type === 'gradient') {
        overlay = 'linear-gradient(' + value(panel, 'overlay_angle') + 'deg,'
          + rgba(value(panel, 'overlay_color'), value(panel, 'overlay_opacity')) + ' ' + value(panel, 'overlay_stop') + '%,'
          + rgba(value(panel, 'overlay_color2'), value(panel, 'overlay_opacity2')) + ' ' + value(panel, 'overlay_stop2') + '%)';
      }
      const layers = type === 'preset' && viewport === 'desktop' && root.find('[name="sfs_hero_overlay"]').val() === 'left' ? 3 : 2;
      const repeat = Array(layers - 1).fill('no-repeat').concat(value(panel, 'repeat'));
      const size = Array(layers - 1).fill('cover').concat(value(panel, 'size') || 'cover');
      const position = Array(layers - 1).fill('center center').concat(value(panel, 'x') + '% ' + value(panel, 'y') + '%');
      const attachment = Array(layers - 1).fill('scroll').concat(value(panel, 'attachment'));
      const blend = [type === 'preset' ? 'normal' : (value(panel, 'overlay_blend') || 'normal')].concat(Array(layers - 1).fill('normal'));
      const display = panel.find('.sfs-hero-preview')[0];
      display.style.backgroundColor = value(panel, 'color') || '#031835';
      display.style.backgroundImage = overlay + (image ? ',url(' + JSON.stringify(image) + ')' : '');
      display.style.backgroundSize = size.join(',');
      display.style.backgroundRepeat = repeat.join(',');
      display.style.backgroundPosition = position.join(',');
      display.style.backgroundAttachment = attachment.join(',');
      display.style.backgroundBlendMode = blend.join(',');
      panel.find('input[type="range"]').each(function () { $(this).siblings('output').text(this.value + '%'); });
    };
    const refresh = () => root.find('.sfs-hero-panel').each(function () { preview($(this)); });
    root.on('input change', 'input, select', refresh);
    root.on('click', '.sfs-hero-tab', function () {
      const target = $(this).attr('data-target');
      root.find('.sfs-hero-tab').removeClass('is-active').attr('aria-selected', 'false');
      $(this).addClass('is-active').attr('aria-selected', 'true');
      root.find('.sfs-hero-panel').each(function () {
        const active = $(this).attr('data-viewport') === target;
        $(this).toggleClass('is-active', active).prop('hidden', !active);
      });
    });
    root.on('change', '.sfs-hero-img input[type="hidden"]', function () {
      const box = $(this).closest('.sfs-hero-img');
      const viewport = box.attr('data-viewport');
      const image = box.find('img').attr('src') || '';
      const desktop = root.find('.sfs-hero-panel[data-viewport="desktop"] .sfs-hero-preview');
      if (viewport === 'desktop') desktop.attr('data-image', image || root.attr('data-default-image'));
      if (viewport === 'mobile') root.find('.sfs-hero-panel[data-viewport="mobile"] .sfs-hero-preview').attr('data-image', image || desktop.attr('data-image'));
      if (viewport === 'desktop' && !root.find('.sfs-hero-img[data-viewport="mobile"] input').val()) {
        root.find('.sfs-hero-panel[data-viewport="mobile"] .sfs-hero-preview').attr('data-image', image);
      }
      refresh();
    });
    refresh();
  });
});
