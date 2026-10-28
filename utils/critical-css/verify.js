// Paste into the console on a loaded page, then run e.g.
//   await verifySubset("custom-style-css", document.getElementById("custom-style-critical-css").href)
// It compares every element's computed style (and ::before/::after, and box)
// with the full stylesheet against the subset alone. Takes about a minute.
window.verifySubset = async function (linkId, subsetUrl) {
  const link = document.getElementById(linkId);
  if (!link) return 'no link ' + linkId;
  const css = await (await fetch(subsetUrl, {cache: 'no-store'})).text();
  const freeze = document.createElement('style');
  freeze.textContent = '*,*::before,*::after{transition:none!important;animation:none!important}';
  document.head.appendChild(freeze);
  const sub = document.createElement('style');
  sub.id = 'verify-subset';
  sub.textContent = css.replace(/url\("\.\.\//g, 'url("' + link.href.replace(/css\/[^/]*$/, ''));
  link.parentNode.insertBefore(sub, link.nextSibling);
  document.body.offsetHeight; await new Promise(r => setTimeout(r, 50));
  const els = [...document.querySelectorAll('body, body *')].filter(e => !['SCRIPT','STYLE','NOSCRIPT','TEMPLATE'].includes(e.tagName));
  const snap = () => els.map(e => {
    const out = [];
    for (const p of [null, '::before', '::after']) {
      const cs = getComputedStyle(e, p);
      let s = '';
      for (let i = 0; i < cs.length; i++) { const k = cs[i]; s += k + ':' + cs.getPropertyValue(k) + ';'; }
      out.push(s);
    }
    const r = e.getBoundingClientRect();
    out.push([Math.round(r.left), Math.round(r.top + scrollY), Math.round(r.width), Math.round(r.height)].join(','));
    return out;
  });
  const a = snap();
  link.disabled = true;
  document.body.offsetHeight; await new Promise(r => setTimeout(r, 50));
  const b = snap();
  link.disabled = false;
  sub.remove(); freeze.remove();
  const diffs = [];
  const parse = s => Object.fromEntries(s.split(';').filter(Boolean).map(x => { const i = x.indexOf(':'); return [x.slice(0, i), x.slice(i + 1)]; }));
  els.forEach((e, i) => {
    for (let j = 0; j < 4; j++) {
      if (a[i][j] !== b[i][j]) {
        let detail = '';
        if (j < 3) { const pa = parse(a[i][j]), pb = parse(b[i][j]); detail = Object.keys({...pa, ...pb}).filter(k => pa[k] !== pb[k]).slice(0, 6).map(k => k + ': ' + pa[k] + ' -> ' + pb[k]).join(' | '); }
        else detail = a[i][j] + ' -> ' + b[i][j];
        diffs.push((e.tagName + '#' + e.id + '.' + String(e.className).trim().replace(/\s+/g, '.')).slice(0, 90) + ['', '::before', '::after', ' box'][j] + '  ' + detail.slice(0, 300));
      }
    }
  });
  return {elements: els.length, diffs: diffs.length, sample: diffs.slice(0, 40)};
};
