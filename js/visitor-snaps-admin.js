/*
 * Visitor Insights screenshots (inc/visitor-snaps.php).
 *
 * "Take screenshot" asks the visitor's browser for a copy of its page; this script waits
 * for it, rebuilds the copy in a sandboxed frame at the visitor's window size and draws it
 * into a JPEG here, in the admin's browser (Live Chat's snap-render.js), then saves it with
 * the visit. The viewer shows the picture beside the visitor's details, and "Download with
 * details" stamps those details under the picture.
 */
(() => {
  const cfg = window.retailsVISnaps;
  const admin = window.retailsVIAdmin;
  if (!cfg || !admin) return;

  const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

  const el = (tag, attrs, ...children) => {
    const node = document.createElement(tag);
    Object.entries(attrs || {}).forEach(([key, value]) => {
      if (value === null || value === undefined || value === false) return;
      if (key === 'class') node.className = value;
      else if (key.startsWith('on') && typeof value === 'function') node.addEventListener(key.slice(2), value);
      else node.setAttribute(key, value === true ? '' : value);
    });
    children.flat().forEach((child) => {
      if (child !== null && child !== undefined && child !== false) node.append(child.nodeType ? child : String(child));
    });
    return node;
  };

  // admin.nonce is refreshed in place by the live updates (visitor-insights-admin.js).
  const ajax = async (action, fields) => {
    const form = new FormData();
    form.append('action', action);
    form.append('nonce', admin.nonce);
    Object.entries(fields || {}).forEach(([key, value]) => form.append(key, value));
    const response = await fetch(admin.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: form });
    const text = await response.text();
    let json = null;
    try { json = JSON.parse(text); } catch (error) { /* not JSON */ }
    if (!json) throw new Error(text === '-1' || text === '0' ? 'Your login expired. Reload the page.' : `The server answered HTTP ${response.status}.`);
    if (!json.success) throw new Error(typeof json.data === 'string' && json.data ? json.data : 'The request failed.');
    return json.data;
  };

  let renderer = null;
  const loadRenderer = () => renderer || (renderer = new Promise((resolve, reject) => {
    if (window.SFSChatSnapRender) return resolve(window.SFSChatSnapRender);
    const script = document.createElement('script');
    script.src = cfg.render;
    script.async = true;
    script.onload = () => (window.SFSChatSnapRender ? resolve(window.SFSChatSnapRender) : reject(new Error('The drawing script did not load.')));
    script.onerror = () => {
      renderer = null;
      reject(new Error('The drawing script did not load.'));
    };
    document.head.appendChild(script);
  }));

  // ------------------------------------------------------------------
  // Visitor details saved with each screenshot
  // ------------------------------------------------------------------

  const FIELDS = [
    ['name', 'Name'], ['email', 'Email'], ['customer', 'Customer'], ['ip', 'IP address'], ['location', 'Location'],
    ['timezone', 'Time zone'], ['language', 'Language'], ['device', 'Device'], ['screen', 'Screen'], ['source', 'Source'],
    ['campaign', 'Campaign'], ['keyword', 'Keyword'], ['ad_network', 'Ad shown on'], ['referrer', 'Referrer'],
    ['landing', 'Landing page'], ['visit_started', 'Visit started'], ['pages', 'Pages viewed'], ['returning', 'Visitor'],
  ];

  const detailRows = (snap) => {
    const info = snap.info || {};
    const scrolled = snap.ph > snap.h ? `, scrolled ${Math.min(100, Math.round((100 * snap.sy) / Math.max(1, snap.ph - snap.h)))}% down` : '';
    const rows = [
      ['Taken', snap.at_text],
      ['Page', snap.page],
      ['Page title', snap.title],
      ['Window', snap.w && snap.h ? `${snap.w} × ${snap.h}${scrolled}` : ''],
    ];
    FIELDS.forEach(([key, label]) => {
      if (!(key in info)) return;
      let value = info[key];
      if (key === 'returning') value = value ? 'Returning visitor' : 'First visit';
      rows.push([label, value]);
    });
    if (snap.by) rows.push(['Taken by', snap.by]);
    return rows.filter((row) => row[1] !== '' && row[1] !== null && row[1] !== undefined);
  };

  // ------------------------------------------------------------------
  // Viewer
  // ------------------------------------------------------------------

  let view = null;

  const closeView = () => {
    if (!view) return;
    view.closed = true;
    view.backdrop.remove();
    document.documentElement.classList.remove('wvi-snap-open');
    view = null;
  };
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeView(); });

  const openView = (title) => {
    closeView();
    const stage = el('div', { class: 'wvi-snap-stage' });
    const status = el('p', { class: 'wvi-snap-status', hidden: true });
    const details = el('dl', { class: 'wvi-snap-details' });
    const actions = el('div', { class: 'wvi-snap-actions' });
    const heading = el('h2', {}, title);
    const box = el(
      'div',
      { class: 'wvi-snap-box', role: 'dialog', 'aria-modal': 'true', 'aria-label': title },
      el('div', { class: 'wvi-snap-head' }, heading, el('button', { type: 'button', class: 'wvi-snap-x', 'aria-label': 'Close', onclick: closeView }, '×')),
      el('div', { class: 'wvi-snap-body' }, el('div', { class: 'wvi-snap-main' }, stage, status), el('aside', { class: 'wvi-snap-side' }, el('h3', {}, 'Visitor details'), details)),
      actions,
    );
    const backdrop = el('div', { class: 'wvi-snap-backdrop', onclick: (event) => { if (event.target === backdrop) closeView(); } }, box);
    document.body.appendChild(backdrop);
    document.documentElement.classList.add('wvi-snap-open');
    view = { backdrop, stage, status, details, actions, heading, closed: false };
    return view;
  };

  const setStatus = (v, text, tone) => {
    v.status.hidden = !text;
    v.status.textContent = text || '';
    v.status.className = 'wvi-snap-status' + (tone ? ' is-' + tone : '');
  };

  const showDetails = (v, snap) => {
    v.details.replaceChildren(...detailRows(snap).flatMap(([label, value]) => {
      const text = String(value);
      const isLink = /^https?:\/\//.test(text);
      return [el('dt', {}, label), el('dd', {}, isLink ? el('a', { href: text, target: '_blank', rel: 'noopener' }, text) : text)];
    }));
  };

  const showActions = (v, snap) => {
    const buttons = [];
    if (snap.url) {
      buttons.push(el('button', { type: 'button', class: 'button button-primary', onclick: (event) => downloadWithDetails(snap, event.currentTarget) }, el('span', { class: 'dashicons dashicons-download' }), ' Download with details'));
      buttons.push(el('a', { class: 'button', href: snap.download }, 'Picture only'));
      buttons.push(el('a', { class: 'button', href: snap.url, target: '_blank', rel: 'noopener' }, 'Open full size'));
    }
    buttons.push(el('button', {
      type: 'button',
      class: 'button-link wvi-snap-delete',
      onclick: async () => {
        if (!window.confirm('Delete this screenshot? This cannot be undone.')) return;
        try {
          await ajax('retails_vi_snap_delete', { session: snap.session, name: snap.id });
          document.querySelectorAll(`[data-snap-id="${snap.id}"]`).forEach((node) => node.remove());
          closeView();
        } catch (error) {
          window.alert(error.message);
        }
      },
    }, 'Delete'));
    v.actions.replaceChildren(...buttons);
  };

  const showPicture = (v, snap) => {
    v.stage.replaceChildren(el('a', { class: 'wvi-snap-picture', href: snap.url, target: '_blank', rel: 'noopener', title: 'Open full size' }, el('img', { src: snap.url, alt: `Screenshot of ${snap.title || snap.page}` })));
    setStatus(v, '');
  };

  /** Rebuild the copy inside the viewer (scaled to fit), draw it, save it. */
  const drawInView = async (v, snap) => {
    setStatus(v, "Drawing the visitor's screen…");
    const [R, copy] = await Promise.all([loadRenderer(), ajax('retails_vi_snap_copy', { session: snap.session, name: snap.id })]);
    if (v.closed) return null;
    const room = Math.max(280, v.stage.clientWidth || 800);
    const k = Math.min(1, room / snap.w, Math.max(240, window.innerHeight - 240) / snap.h);
    const inner = el('div', { class: 'wvi-snap-frame-inner', style: `width:${snap.w}px;height:${snap.h}px;transform:scale(${k})` });
    v.stage.replaceChildren(el('div', { class: 'wvi-snap-frame', style: `width:${Math.round(snap.w * k)}px;height:${Math.round(snap.h * k)}px` }, inner));
    const shown = await R.show({ ...snap, html: copy.html, home: cfg.home }, inner);
    const image = await R.draw(shown, snap);
    const saved = await ajax('retails_vi_snap_save', { session: snap.session, name: snap.id, image });
    markDrawn(saved);
    if (!v.closed) {
      showPicture(v, saved);
      showDetails(v, saved);
      showActions(v, saved);
    }
    return saved;
  };

  const openSnap = async (snap) => {
    const v = openView('Visitor screenshot');
    showDetails(v, snap);
    showActions(v, snap);
    if (snap.url) {
      showPicture(v, snap);
      return;
    }
    try {
      await drawInView(v, snap);
    } catch (error) {
      setStatus(v, `Could not draw the screenshot (${error.message}).`, 'bad');
    }
  };

  // ------------------------------------------------------------------
  // Taking one
  // ------------------------------------------------------------------

  const waitText = (state, online, seconds) => {
    if (state === 'sent') return "The visitor's browser is copying its page…";
    const base = online
      ? 'Waiting for the visitor\'s browser. It answers within about 15 seconds while they move, scroll or click.'
      : 'This visitor has been idle for a few minutes. Their browser answers as soon as they come back to the page.';
    return `${base} (${seconds}s) You can close this; the screenshot is saved on the visit when it arrives.`;
  };

  const take = async (sessionId, button) => {
    const v = openView('Taking a screenshot');
    v.stage.replaceChildren(el('div', { class: 'wvi-snap-wait' }, el('span', { class: 'spinner is-active' })));
    v.details.replaceChildren(el('dd', { class: 'wvi-muted' }, 'Saved with the screenshot when it arrives.'));
    if (button) button.disabled = true;
    try {
      const { req, online } = await ajax('retails_vi_snap_request', { session: sessionId });
      const started = Date.now();
      let state = { state: 'waiting' };
      setStatus(v, waitText('waiting', online, 0));
      while (!v.closed) {
        await sleep(2000);
        if (v.closed) break;
        state = await ajax('retails_vi_snap_status', { req });
        if (state.state === 'captured') break;
        if (state.state === 'failed') throw new Error(state.error || "The visitor's browser could not copy the page.");
        if (state.state === 'expired') throw new Error('The visitor did not come back to the page in time. Try again while they are active.');
        setStatus(v, waitText(state.state, online, Math.round((Date.now() - started) / 1000)));
      }
      if (v.closed || state.state !== 'captured') return;
      v.heading.textContent = 'Visitor screenshot';
      showDetails(v, state.snap);
      showActions(v, state.snap);
      await drawInView(v, state.snap);
    } catch (error) {
      if (!v.closed) {
        v.stage.replaceChildren();
        setStatus(v, error.message, 'bad');
      }
    } finally {
      if (button) button.disabled = false;
    }
  };

  // ------------------------------------------------------------------
  // Copies nobody drew yet (the admin closed the viewer early): draw them quietly
  // ------------------------------------------------------------------

  const markDrawn = (snap) => {
    document.querySelectorAll(`[data-snap-id="${snap.id}"] .wvi-snap-thumb`).forEach((thumb) => {
      thumb.dataset.snap = JSON.stringify(snap);
      thumb.replaceChildren(el('img', { src: snap.url, alt: `Screenshot of ${snap.title || snap.page}` }));
    });
    document.querySelectorAll('button[data-snap]').forEach((button) => {
      try {
        if (JSON.parse(button.dataset.snap).id === snap.id) button.dataset.snap = JSON.stringify(snap);
      } catch (error) { /* ignore */ }
    });
  };

  const drawing = new Set();
  let drawQueue = Promise.resolve();
  const drawHidden = (snap) => {
    if (drawing.has(snap.id)) return;
    drawing.add(snap.id);
    drawQueue = drawQueue.then(async () => {
      // Off screen but laid out at the visitor's size: the drawer needs a real layout.
      const mount = el('div', { class: 'wvi-snap-offscreen', style: `width:${snap.w}px;height:${snap.h}px`, 'aria-hidden': 'true' });
      document.body.appendChild(mount);
      try {
        const [R, copy] = await Promise.all([loadRenderer(), ajax('retails_vi_snap_copy', { session: snap.session, name: snap.id })]);
        const shown = await R.show({ ...snap, html: copy.html, home: cfg.home }, mount);
        const image = await R.draw(shown, snap);
        markDrawn(await ajax('retails_vi_snap_save', { session: snap.session, name: snap.id, image }));
      } catch (error) {
        document.querySelectorAll(`[data-snap-id="${snap.id}"] .wvi-snap-ph`).forEach((ph) => {
          ph.lastChild.textContent = 'Click to draw';
        });
      } finally {
        mount.remove();
      }
    });
  };

  const drawPending = (root) => {
    root.querySelectorAll('[data-snap-undrawn]').forEach((ph) => {
      const thumb = ph.closest('[data-snap]');
      try {
        if (thumb) drawHidden(JSON.parse(thumb.dataset.snap));
      } catch (error) { /* ignore */ }
    });
  };

  // The live updates swap the page body: look again after each swap.
  const body = document.querySelector('[data-live-body]');
  if (body) new MutationObserver(() => drawPending(body)).observe(body, { childList: true });
  drawPending(document);

  // ------------------------------------------------------------------
  // Download with the visitor's details stamped under the picture
  // ------------------------------------------------------------------

  const fitText = (ctx, text, width) => {
    if (ctx.measureText(text).width <= width) return text;
    let low = 0;
    let high = text.length;
    while (low < high) {
      const mid = Math.ceil((low + high) / 2);
      if (ctx.measureText(text.slice(0, mid) + '…').width <= width) low = mid;
      else high = mid - 1;
    }
    return text.slice(0, low) + '…';
  };

  const downloadWithDetails = async (snap, button) => {
    if (button) button.disabled = true;
    try {
      const img = new Image();
      img.src = snap.url;
      await img.decode();
      const W = img.naturalWidth;
      const s = Math.max(1, W / 1100);
      const rows = detailRows(snap);
      const columns = W >= 900 ? 2 : 1;
      const perColumn = Math.ceil(rows.length / columns);
      const pad = 18 * s;
      const line = 21 * s;
      const band = pad * 2 + line * (perColumn + 1.4);
      const canvas = document.createElement('canvas');
      canvas.width = W;
      canvas.height = Math.round(img.naturalHeight + band);
      const ctx = canvas.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0);
      const top = img.naturalHeight;
      ctx.fillStyle = '#f6f7f7';
      ctx.fillRect(0, top, W, band);
      ctx.fillStyle = '#dcdcde';
      ctx.fillRect(0, top, W, Math.max(1, s));
      ctx.textBaseline = 'top';
      ctx.fillStyle = '#1d2327';
      ctx.font = `600 ${15 * s}px -apple-system, "Segoe UI", Roboto, Arial, sans-serif`;
      ctx.fillText(fitText(ctx, `Visitor screenshot · ${snap.site || ''} · ${snap.at_text}`, W - pad * 2), pad, top + pad);
      const columnWidth = (W - pad * (columns + 1)) / columns;
      const labelWidth = 104 * s;
      rows.forEach(([label, value], i) => {
        const column = Math.floor(i / perColumn);
        const x = pad + column * (columnWidth + pad);
        const y = top + pad + line * (1.4 + (i % perColumn));
        ctx.font = `600 ${12.5 * s}px -apple-system, "Segoe UI", Roboto, Arial, sans-serif`;
        ctx.fillStyle = '#646970';
        ctx.fillText(fitText(ctx, label, labelWidth - 6 * s), x, y);
        ctx.font = `${12.5 * s}px -apple-system, "Segoe UI", Roboto, Arial, sans-serif`;
        ctx.fillStyle = '#1d2327';
        ctx.fillText(fitText(ctx, String(value), columnWidth - labelWidth), x + labelWidth, y);
      });
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
      if (!blob) throw new Error('The picture could not be made.');
      const where = String((snap.info && (snap.info.location || snap.info.ip)) || '').replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').slice(0, 40);
      // Site time (Los Angeles), like the time shown on the picture.
      const stamp = String(snap.stamp || snap.at).replace(/[^0-9-]/g, '');
      const link = el('a', { href: URL.createObjectURL(blob), download: `visitor-screenshot-${stamp}${where ? '-' + where : ''}.jpg` });
      document.body.appendChild(link);
      link.click();
      link.remove();
      setTimeout(() => URL.revokeObjectURL(link.href), 10000);
    } catch (error) {
      window.alert(`Could not make the download: ${error.message}`);
    } finally {
      if (button) button.disabled = false;
    }
  };

  // ------------------------------------------------------------------
  // Clicks (delegated: the live updates replace the page body)
  // ------------------------------------------------------------------

  document.addEventListener('click', (event) => {
    const takeButton = event.target.closest('[data-snap-take]');
    if (takeButton) {
      event.preventDefault();
      event.stopPropagation();
      if (!takeButton.disabled) take(takeButton.dataset.snapTake, takeButton);
      return;
    }
    const open = event.target.closest('[data-snap]');
    if (open) {
      event.preventDefault();
      event.stopPropagation();
      try {
        openSnap(JSON.parse(open.dataset.snap));
      } catch (error) {
        window.alert('That screenshot could not be opened.');
      }
    }
  }, true);
})();
