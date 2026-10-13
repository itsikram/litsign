// Bounding boxes of each logo piece, in the logo's 2172x408 viewBox units.
const fs = require('fs');
const { Resvg } = require('@resvg/resvg-js');
const src = fs.readFileSync(process.argv[2], 'utf8');
const inner = src.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '');
const ids = ['spl-tile', 'spl-badge', 'spl-tag'].concat(Array.from({ length: 14 }, (_, i) => 'spl-l' + (i + 1)));
const out = {};
for (const id of ids) {
  const svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 2172 408" width="2172" height="408"><defs>' + inner + '</defs><use href="#' + id + '" fill="#000"/></svg>';
  const b = new Resvg(svg).getBBox();
  out[id] = b ? [b.x, b.y, b.width, b.height].map(v => Math.round(v * 10) / 10) : null;
}
console.log(JSON.stringify(out));
