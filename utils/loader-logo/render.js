// Render an SVG to PNG at a given width: node render.js in.svg out.png width
const fs = require('fs');
const { Resvg } = require('@resvg/resvg-js');
const [, , src, out, width] = process.argv;
const resvg = new Resvg(fs.readFileSync(src), { fitTo: { mode: 'width', value: Number(width) } });
fs.writeFileSync(out, resvg.render().asPng());
