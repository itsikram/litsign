/*
 * Visitor Insights admin page: traffic and hour-of-day charts (plain SVG),
 * clickable visit rows, copy buttons and the live visitors refresh.
 */
(() => {
  const NS = 'http://www.w3.org/2000/svg';
  const svgEl = (name, attrs, parent) => {
    const el = document.createElementNS(NS, name);
    Object.keys(attrs || {}).forEach((key) => el.setAttribute(key, attrs[key]));
    if (parent) parent.appendChild(el);
    return el;
  };
  const niceMax = (value) => {
    if (value <= 4) return 4;
    const step = Math.pow(10, Math.floor(Math.log10(value)));
    return Math.ceil(value / step / (value / step > 5 ? 2 : 1)) * step * (value / step > 5 ? 2 : 1);
  };

  const tooltip = (host) => {
    const tip = document.createElement('div');
    tip.className = 'wvi-tip';
    tip.hidden = true;
    host.appendChild(tip);
    return tip;
  };

  // ------------------------------------------------------------------
  // Traffic chart: visits (area), Google Ads (line), conversions (bars)
  // ------------------------------------------------------------------

  document.querySelectorAll('.wvi-chart[data-series]').forEach((host) => {
    const data = JSON.parse(host.dataset.series || '[]');
    const showAds = host.dataset.ads === '1';
    if (!data.length) return;

    const W = 760, H = 250, L = 34, R = 10, T = 12, B = 28;
    const max = niceMax(Math.max(1, ...data.map((d) => d.sessions)));
    const step = (W - L - R) / data.length;
    const x = (i) => L + step * i + step / 2;
    const y = (v) => T + (H - T - B) * (1 - v / max);

    const svg = svgEl('svg', { viewBox: `0 0 ${W} ${H}`, class: 'wvi-svg', role: 'img', 'aria-label': 'Visits over time' }, host);

    for (let i = 0; i <= 4; i++) {
      const value = (max / 4) * i;
      svgEl('line', { x1: L, x2: W - R, y1: y(value), y2: y(value), class: 'wvi-grid-line' }, svg);
      svgEl('text', { x: L - 6, y: y(value) + 4, class: 'wvi-axis', 'text-anchor': 'end' }, svg).textContent = Math.round(value);
    }

    const labelEvery = Math.ceil(data.length / 10);
    data.forEach((d, i) => {
      if (i % labelEvery === 0) svgEl('text', { x: x(i), y: H - 8, class: 'wvi-axis', 'text-anchor': 'middle' }, svg).textContent = d.label;
      if (d.conversions > 0) {
        const w = Math.max(3, Math.min(14, step * 0.35));
        svgEl('rect', { x: x(i) - w / 2, y: y(d.conversions), width: w, height: y(0) - y(d.conversions), rx: 2, class: 'wvi-bar-conv' }, svg);
      }
    });

    const line = (key) => data.map((d, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(d[key]).toFixed(1)}`).join(' ');
    svgEl('path', { d: `${line('sessions')} L${x(data.length - 1)},${y(0)} L${x(0)},${y(0)} Z`, class: 'wvi-area-all' }, svg);
    svgEl('path', { d: line('sessions'), class: 'wvi-line-all' }, svg);
    if (showAds) svgEl('path', { d: line('ads'), class: 'wvi-line-ads' }, svg);

    const cursor = svgEl('line', { y1: T, y2: H - B, class: 'wvi-cursor', visibility: 'hidden' }, svg);
    const tip = tooltip(host);
    svg.addEventListener('mousemove', (event) => {
      const box = svg.getBoundingClientRect();
      const px = (event.clientX - box.left) / box.width * W;
      const i = Math.max(0, Math.min(data.length - 1, Math.floor((px - L) / step)));
      const d = data[i];
      cursor.setAttribute('x1', x(i));
      cursor.setAttribute('x2', x(i));
      cursor.setAttribute('visibility', 'visible');
      tip.innerHTML = `<strong>${d.label}</strong><span><i class="is-all"></i>Visits ${d.sessions}</span>` +
        (showAds ? `<span><i class="is-ads"></i>Google Ads ${d.ads}</span>` : '') +
        `<span><i class="is-conv"></i>Leads + orders ${d.conversions}</span>`;
      tip.hidden = false;
      const left = (x(i) / W) * box.width;
      tip.style.left = Math.min(box.width - tip.offsetWidth - 4, Math.max(4, left + 12)) + 'px';
    });
    svg.addEventListener('mouseleave', () => {
      tip.hidden = true;
      cursor.setAttribute('visibility', 'hidden');
    });
  });

  // ------------------------------------------------------------------
  // Hour of day bars
  // ------------------------------------------------------------------

  document.querySelectorAll('.wvi-hours[data-hours]').forEach((host) => {
    const data = JSON.parse(host.dataset.hours || '[]');
    const W = 1100, H = 190, L = 30, R = 6, T = 10, B = 24;
    const max = niceMax(Math.max(1, ...data.map((d) => d.sessions)));
    const step = (W - L - R) / 24;
    const y = (v) => T + (H - T - B) * (1 - v / max);
    const svg = svgEl('svg', { viewBox: `0 0 ${W} ${H}`, class: 'wvi-svg', role: 'img', 'aria-label': 'Ad clicks by hour' }, host);
    const tip = tooltip(host);
    const hourLabel = (h) => (h % 12 || 12) + (h < 12 ? 'a' : 'p');

    [0, max / 2, max].forEach((value) => {
      svgEl('line', { x1: L, x2: W - R, y1: y(value), y2: y(value), class: 'wvi-grid-line' }, svg);
      svgEl('text', { x: L - 6, y: y(value) + 4, class: 'wvi-axis', 'text-anchor': 'end' }, svg).textContent = Math.round(value);
    });

    data.forEach((d, h) => {
      const bx = L + step * h + step * 0.15;
      const bw = step * 0.7;
      const bar = svgEl('rect', { x: bx, y: y(d.sessions), width: bw, height: Math.max(0, y(0) - y(d.sessions)), rx: 2, class: d.sessions >= 3 && !d.conversions ? 'wvi-bar-waste' : 'wvi-bar-all' }, svg);
      if (d.conversions) svgEl('rect', { x: bx, y: y(d.conversions), width: bw, height: y(0) - y(d.conversions), rx: 2, class: 'wvi-bar-conv' }, svg);
      if (h % 2 === 0) svgEl('text', { x: bx + bw / 2, y: H - 8, class: 'wvi-axis', 'text-anchor': 'middle' }, svg).textContent = hourLabel(h);

      const hit = svgEl('rect', { x: L + step * h, y: T, width: step, height: H - T - B, fill: 'transparent' }, svg);
      hit.addEventListener('mouseenter', () => {
        const box = svg.getBoundingClientRect();
        tip.innerHTML = `<strong>${hourLabel(h)}m – ${hourLabel((h + 1) % 24)}m</strong><span>Ad clicks ${d.sessions}</span><span>Leads + orders ${d.conversions}</span>`;
        tip.hidden = false;
        tip.style.left = Math.min(box.width - tip.offsetWidth - 4, ((bx + bw) / W) * box.width + 6) + 'px';
        bar.classList.add('is-hover');
      });
      hit.addEventListener('mouseleave', () => {
        tip.hidden = true;
        bar.classList.remove('is-hover');
      });
    });
  });

  // ------------------------------------------------------------------
  // Rows, copy buttons, confirmations
  // ------------------------------------------------------------------

  document.addEventListener('click', (event) => {
    const copy = event.target.closest('.wvi-copy');
    if (copy) {
      event.preventDefault();
      const done = () => {
        const original = copy.textContent;
        copy.textContent = 'Copied';
        setTimeout(() => { copy.textContent = original; }, 1500);
      };
      if (navigator.clipboard) {
        navigator.clipboard.writeText(copy.dataset.copy).then(done);
      } else {
        const area = document.createElement('textarea');
        area.value = copy.dataset.copy;
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        area.remove();
        done();
      }
      return;
    }

    const row = event.target.closest('.wvi-row-link');
    if (row && !event.target.closest('a, button, input')) {
      if (event.ctrlKey || event.metaKey) {
        window.open(row.dataset.href, '_blank');
      } else {
        window.location.href = row.dataset.href;
      }
    }
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  // ------------------------------------------------------------------
  // Live visitors
  // ------------------------------------------------------------------

  const liveRows = document.querySelector('[data-live-rows]');
  const settings = window.wholesaleVIAdmin;
  if (liveRows && settings) {
    const refresh = async () => {
      if (document.hidden) return;
      const data = new FormData();
      data.append('action', 'wholesale_vi_live');
      data.append('nonce', settings.nonce);
      try {
        const response = await fetch(settings.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
        const json = await response.json();
        if (!json.success) return;
        liveRows.innerHTML = json.data.html;
        document.querySelectorAll('[data-live-count]').forEach((el) => { el.textContent = json.data.count; });
      } catch (error) {
        // Try again on the next tick.
      }
    };
    setInterval(refresh, 15000);
  }
})();
