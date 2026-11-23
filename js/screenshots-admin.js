/*
 * Screenshots in wp-admin (inc/screenshots.php).
 *
 * A screenshot arrives as a copy of the visitor's page. The first time it is listed or
 * opened, this script rebuilds the copy in a sandboxed frame (no scripts run) at the
 * visitor's window size and scroll spot, draws it into a JPEG with Live Chat's
 * snap-render.js and saves the picture, so later views just show it.
 *
 * "Scroll the whole page" shows the copy itself, at the visitor's window size, to scroll
 * through everything below and above what was on their screen.
 */
(() => {
  const cfg = window.sfsShots;
  if (!cfg) return;

  const el = (tag, attrs, ...children) => {
    const node = document.createElement(tag);
    Object.entries(attrs || {}).forEach(([key, value]) => {
      if (value === null || value === undefined || value === false) return;
      if (key === 'class') node.className = value;
      else if (key === 'style') node.style.cssText = value;
      else node.setAttribute(key, value === true ? '' : value);
    });
    children.flat().forEach((child) => {
      if (child !== null && child !== undefined && child !== false) node.append(child.nodeType ? child : String(child));
    });
    return node;
  };

  const ajax = async (action, fields) => {
    const form = new FormData();
    form.append('action', action);
    form.append('nonce', cfg.nonce);
    Object.entries(fields || {}).forEach(([key, value]) => form.append(key, value));
    const response = await fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: form });
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
    if (!cfg.render) return reject(new Error('the SFS Live Chat plugin (which draws the pictures) is not active'));
    const script = document.createElement('script');
    script.src = cfg.render;
    script.async = true;
    script.onload = () => (window.SFSChatSnapRender ? resolve(window.SFSChatSnapRender) : reject(new Error('the drawing script did not load')));
    script.onerror = () => {
      renderer = null;
      reject(new Error('the drawing script did not load'));
    };
    document.head.appendChild(script);
  }));

  const copies = new Map();
  const getCopy = (shot) => {
    if (!copies.has(shot.id)) {
      copies.set(shot.id, ajax('sfs_shot_copy', { id: shot.id }).then((data) => data.html, (error) => {
        copies.delete(shot.id);
        throw error;
      }));
    }
    return copies.get(shot.id);
  };

  /** Draw a copy (in `mount`, laid out at the visitor's size) and save the picture. */
  const draw = async (shot, mount) => {
    const [R, html] = await Promise.all([loadRenderer(), getCopy(shot)]);
    const shown = await R.show({ ...shot, html, home: cfg.home }, mount);
    const image = await R.draw(shown, shot);
    return ajax('sfs_shot_save', { id: shot.id, image });
  };

  // ------------------------------------------------------------------
  // The copy itself, scrollable (no scripts, links do nothing)
  // ------------------------------------------------------------------

  const policy = (page) => {
    const bases = new Set();
    const add = (url) => {
      try {
        const u = new URL(url);
        bases.add(u.origin + u.pathname.replace(/[^/]*$/, ''));
      } catch (error) { /* ignore */ }
    };
    add(cfg.home || `${location.origin}/`);
    try {
      if (new URL(page).origin !== location.origin) add(`${new URL(page).origin}/`);
    } catch (error) { /* ignore */ }
    const files = [...bases].flatMap((base) => [`${base}wp-content/`, `${base}wp-includes/`]).join(' ');
    return [
      "default-src 'none'",
      `style-src 'unsafe-inline' ${files} https://fonts.googleapis.com`,
      `font-src data: ${files} https://fonts.gstatic.com`,
      `img-src data: blob: ${files} https://*.wsimg.com https://*.gravatar.com https://*.googleusercontent.com`,
      `media-src data: ${files}`,
    ].join('; ');
  };
  const attr = (value) => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
  const FREEZE = 'html{scroll-behavior:auto!important}*,*::before,*::after{animation-duration:0s!important;animation-delay:0s!important;transition:none!important}a,button,input,select,textarea,label,summary{pointer-events:none!important}';

  const restoreScroll = (doc, shot) => {
    if (doc.defaultView) doc.defaultView.scrollTo(shot.sx || 0, shot.sy || 0);
    doc.querySelectorAll('[data-sfs-scroll]').forEach((node) => {
      const [left, top] = String(node.getAttribute('data-sfs-scroll')).split(',').map(Number);
      node.scrollLeft = left || 0;
      node.scrollTop = top || 0;
    });
  };

  const showWhole = async (stage, shot) => {
    const html = await getCopy(shot);
    const inject = `<meta http-equiv="Content-Security-Policy" content="${attr(policy(shot.page))}"><style>${FREEZE}</style>`;
    const match = /<head(\s[^>]*)?>/i.exec(html);
    const doc = match ? html.slice(0, match.index + match[0].length) + inject + html.slice(match.index + match[0].length) : inject + html;
    const room = Math.max(280, stage.clientWidth || 800);
    const height = Math.min(shot.h, Math.max(320, window.innerHeight - 160));
    const k = Math.min(1, room / shot.w);
    const frame = el('iframe', {
      sandbox: 'allow-same-origin',
      referrerpolicy: 'no-referrer',
      title: 'Copy of the visitor\'s page',
      style: `width:${shot.w}px;height:${Math.round(height / k)}px;border:0;display:block;background:#fff;transform:scale(${k});transform-origin:0 0`,
    });
    frame.srcdoc = doc;
    frame.addEventListener('load', () => {
      try { restoreScroll(frame.contentDocument, shot); } catch (error) { /* ignore */ }
    }, { once: true });
    stage.replaceChildren(el('div', { class: 'sfs-shot-whole', style: `width:${Math.round(shot.w * k)}px;height:${Math.round(height)}px` }, frame));
  };

  // ------------------------------------------------------------------
  // List: draw the screenshots nobody has drawn yet, one at a time
  // ------------------------------------------------------------------

  const drawQueue = [];
  let drawing = false;
  const nextInQueue = async () => {
    if (drawing || !drawQueue.length) return;
    drawing = true;
    const { ph, shot } = drawQueue.shift();
    const mount = el('div', { class: 'sfs-shot-offscreen', style: `width:${shot.w}px;height:${shot.h}px`, 'aria-hidden': 'true' });
    document.body.appendChild(mount);
    try {
      const saved = await draw(shot, mount);
      ph.replaceWith(el('img', { src: saved.url, alt: '' }));
    } catch (error) {
      ph.lastChild.textContent = 'Open to view';
      ph.title = `Could not draw it here: ${error.message}`;
      // Without the drawing script nothing else in the queue can be drawn either.
      if (/not active|did not load/.test(error.message)) drawQueue.splice(0).forEach((item) => { item.ph.lastChild.textContent = 'Open to view'; });
    } finally {
      mount.remove();
      copies.clear();
      drawing = false;
      nextInQueue();
    }
  };
  document.querySelectorAll('[data-shot-draw]').forEach((ph, index) => {
    if (index >= 40) return;
    try {
      drawQueue.push({ ph, shot: JSON.parse(ph.dataset.shotDraw) });
    } catch (error) { /* ignore */ }
  });
  nextInQueue();

  // ------------------------------------------------------------------
  // One screenshot
  // ------------------------------------------------------------------

  const stage = document.querySelector('[data-shot-view]');
  if (!stage) return;
  let shot = null;
  try {
    shot = JSON.parse(stage.dataset.shotView);
  } catch (error) {
    return;
  }
  const status = (text, tone) => el('p', { class: `sfs-shot-status${tone ? ` is-${tone}` : ''}` }, text);
  const showPicture = (url) => {
    stage.replaceChildren(el('a', { href: url, target: '_blank', rel: 'noopener', class: 'sfs-shot-picture', title: 'Open full size' }, el('img', { src: url, alt: 'Screenshot' })));
  };

  const drawHere = async () => {
    stage.replaceChildren(status('Drawing the visitor\'s screen…'));
    const room = Math.max(280, stage.clientWidth || 800);
    const k = Math.min(1, room / shot.w, Math.max(240, window.innerHeight - 220) / shot.h);
    const inner = el('div', { class: 'sfs-shot-frame-inner', style: `width:${shot.w}px;height:${shot.h}px;transform:scale(${k})` });
    const box = el('div', { class: 'sfs-shot-frame', style: `width:${Math.round(shot.w * k)}px;height:${Math.round(shot.h * k)}px` }, inner);
    try {
      stage.append(box);
      const saved = await draw(shot, inner);
      shot.url = saved.url;
      showPicture(saved.url);
    } catch (error) {
      box.remove();
      stage.replaceChildren(status(`Could not draw the picture (${error.message}). Showing the page copy instead.`, 'bad'));
      try {
        const holder = el('div');
        stage.append(holder);
        await showWhole(holder, shot);
      } catch (inner2) {
        stage.append(status(`The page copy could not be opened either (${inner2.message}).`, 'bad'));
      }
    }
  };

  if (!shot.url) drawHere();

  const wholeButton = document.querySelector('[data-shot-whole]');
  let wholeShown = false;
  if (wholeButton) {
    wholeButton.addEventListener('click', async () => {
      wholeButton.disabled = true;
      try {
        if (wholeShown && shot.url) {
          showPicture(shot.url);
          wholeShown = false;
          wholeButton.textContent = 'Scroll the whole page';
        } else {
          stage.replaceChildren(status('Opening the page copy…'));
          await showWhole(stage, shot);
          wholeShown = true;
          wholeButton.textContent = shot.url ? 'Back to the picture' : 'Scroll the whole page';
        }
      } catch (error) {
        stage.replaceChildren(status(`The page copy could not be opened (${error.message}).`, 'bad'));
      } finally {
        wholeButton.disabled = false;
      }
    });
  }
  const redraw = document.querySelector('[data-shot-redraw]');
  if (redraw) {
    redraw.addEventListener('click', () => {
      wholeShown = false;
      if (wholeButton) wholeButton.textContent = 'Scroll the whole page';
      drawHere();
    });
  }
})();
