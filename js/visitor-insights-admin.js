/*
 * Visitor Insights admin page: traffic and hour-of-day charts (plain SVG),
 * clickable visit rows, copy buttons, and live updates that swap in fresh
 * numbers without reloading the page.
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

  const drawTraffic = (host) => {
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
  };

  // ------------------------------------------------------------------
  // Hour of day bars
  // ------------------------------------------------------------------

  const drawHours = (host) => {
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
  };

  const drawCharts = (root) => {
    root.querySelectorAll('.wvi-chart[data-series]').forEach(drawTraffic);
    root.querySelectorAll('.wvi-hours[data-hours]').forEach(drawHours);
  };
  drawCharts(document);

  // ------------------------------------------------------------------
  // Rows, copy buttons, confirmations
  // ------------------------------------------------------------------

  // Copy the exported report. The fetch is handed to the clipboard as a
  // promise so Safari still treats it as part of the click.
  const copyReport = async (button) => {
    const label = button.querySelector('span:last-child');
    const original = label.textContent;
    const show = (text) => {
      label.textContent = text;
      setTimeout(() => { label.textContent = original; button.disabled = false; }, 2500);
    };
    button.disabled = true;
    label.textContent = 'Preparing…';
    const report = fetch(button.dataset.url, { credentials: 'same-origin' }).then((response) => {
      if (!response.ok) throw new Error('export failed');
      return response.text();
    });
    try {
      if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
        await navigator.clipboard.write([new ClipboardItem({ 'text/plain': report.then((text) => new Blob([text], { type: 'text/plain' })) })]);
      } else if (navigator.clipboard) {
        await navigator.clipboard.writeText(await report);
      } else {
        const area = document.createElement('textarea');
        area.value = await report;
        document.body.appendChild(area);
        area.select();
        const copied = document.execCommand('copy');
        area.remove();
        if (!copied) throw new Error('copy blocked');
      }
      show('Copied! Paste it to Claude');
    } catch (error) {
      show('Copy failed, use Download');
    }
  };

  document.addEventListener('click', (event) => {
    const reportButton = event.target.closest('.wvi-copy-report');
    if (reportButton) {
      event.preventDefault();
      if (!reportButton.disabled) copyReport(reportButton);
      return;
    }

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
  // Live updates
  // ------------------------------------------------------------------

  const body = document.querySelector('[data-live-body]');
  const settings = window.wholesaleVIAdmin;
  const status = document.querySelector('[data-live-status]');
  if (!body || !settings || !status) return;

  const updated = status.querySelector('[data-updated]');
  const toggle = status.querySelector('[data-live-toggle]');
  const online = status.querySelector('[data-online]');
  let version = body.dataset.version || '';
  let lastUpdate = Date.now();
  let busy = false;
  let paused = false;
  try { paused = localStorage.getItem('wviLivePaused') === '1'; } catch (error) { /* private mode */ }

  const ago = () => {
    const seconds = Math.round((Date.now() - lastUpdate) / 1000);
    return seconds < 5 ? 'just now' : seconds < 60 ? seconds + 's ago' : Math.floor(seconds / 60) + 'm ago';
  };
  const showState = (text) => {
    status.classList.toggle('is-paused', paused);
    toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
    toggle.title = paused ? 'Resume live updates' : 'Pause live updates';
    toggle.firstElementChild.className = 'dashicons dashicons-controls-' + (paused ? 'play' : 'pause');
    updated.textContent = paused ? 'Paused' : (text || 'Live · updated ' + ago());
  };

  // Don't swap the page out from under someone using a filter, selecting
  // text or reading a chart tooltip; try again on the next tick.
  const userIsBusy = () => {
    const active = document.activeElement;
    if (active && body.contains(active) && active.matches('input, select, textarea')) return true;
    const selection = window.getSelection && window.getSelection();
    if (selection && !selection.isCollapsed && body.contains(selection.anchorNode)) return true;
    return !!body.querySelector('.wvi-tip:not([hidden])');
  };

  const swap = (html) => {
    const oldRows = new Set(Array.from(body.querySelectorAll('.wvi-row-link'), (row) => row.dataset.id));
    const oldStats = Array.from(body.querySelectorAll('.wvi-stat-value'), (el) => el.textContent);
    const back = body.querySelector('.wvi-back');
    const backHref = back && back.getAttribute('href');

    body.innerHTML = html;
    drawCharts(body);

    const newBack = body.querySelector('.wvi-back');
    if (newBack && backHref) newBack.setAttribute('href', backHref);
    if (oldRows.size || body.querySelector('[data-live-rows]')) {
      body.querySelectorAll('.wvi-row-link').forEach((row) => {
        if (!oldRows.has(row.dataset.id)) row.classList.add('is-new');
      });
    }
    body.querySelectorAll('.wvi-stat-value').forEach((el, i) => {
      if (oldStats[i] !== undefined && oldStats[i] !== el.textContent) el.closest('.wvi-stat').classList.add('is-changed');
    });
  };

  const refresh = async (force) => {
    if (busy || paused || document.hidden) return;
    busy = true;
    const data = new FormData();
    data.append('action', 'wholesale_vi_refresh');
    data.append('nonce', settings.nonce);
    data.append('query', window.location.search.replace(/^\?/, ''));
    data.append('version', force ? '' : version);
    try {
      const response = await fetch(settings.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
      const json = await response.json();
      if (!json.success) throw new Error('refresh failed');
      if (online) online.textContent = json.data.online;
      if (json.data.changed) {
        if (userIsBusy()) {
          busy = false;
          return;
        }
        swap(json.data.html);
        version = json.data.version;
      }
      lastUpdate = Date.now();
      status.classList.remove('is-error');
      showState();
    } catch (error) {
      status.classList.add('is-error');
      showState('Reconnecting…');
    }
    busy = false;
  };

  toggle.addEventListener('click', () => {
    paused = !paused;
    try { localStorage.setItem('wviLivePaused', paused ? '1' : '0'); } catch (error) { /* private mode */ }
    showState();
    if (!paused) refresh();
  });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });

  showState();
  setInterval(refresh, settings.interval || 5000);
  setInterval(() => { if (!paused && !status.classList.contains('is-error')) showState(); }, 1000);
})();
