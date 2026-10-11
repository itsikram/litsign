"""Build a comparison sheet: original vs SVG render, plus a difference map."""
import sys
import numpy as np
from PIL import Image, ImageDraw

orig = Image.open(sys.argv[1]).convert('RGBA')
rend = Image.open(sys.argv[2]).convert('RGBA').resize(orig.size, Image.LANCZOS)
out = sys.argv[3]


def on(bg, im):
    base = Image.new('RGBA', im.size, bg)
    base.alpha_composite(im)
    return base.convert('RGB')


W, H = orig.size
a = np.array(on((255, 255, 255, 255), orig)).astype(int)
b = np.array(on((255, 255, 255, 255), rend)).astype(int)
diff = np.abs(a - b).max(axis=2)
print('mean abs diff %.2f, pixels >64: %.3f%%' % (diff.mean(), 100 * (diff > 64).mean()))
heat = Image.fromarray((255 - np.clip(diff * 2, 0, 255)).astype(np.uint8)).convert('RGB')

pad = 40
sheet = Image.new('RGB', (W + 2 * pad, 4 * (H + pad) + pad + 4 * 30), (236, 238, 242))
d = ImageDraw.Draw(sheet)
y = pad
for label, im in (
    ('ORIGINAL PNG (2172x408)', on((255, 255, 255, 255), orig)),
    ('SVG RENDER (same size)', on((255, 255, 255, 255), rend)),
    ('SVG RENDER on loader background', on((3, 24, 53, 255), rend)),
    ('DIFFERENCE (white = identical)', heat),
):
    d.text((pad, y), label, fill=(20, 20, 20))
    y += 30
    sheet.paste(im, (pad, y))
    y += H + pad
sheet.save(out)

# Second sheet: actual on-screen sizes (footer 213px, loader 340px), 2x DPR.
import subprocess
rows = []
for w in (213, 340):
    o = orig.resize((w * 2, round(w * 2 * H / W)), Image.LANCZOS)
    subprocess.run(['node', 'render.js', sys.argv[4], 'small.png', str(w * 2)], check=True)
    s = Image.open('small.png').convert('RGBA').resize(o.size, Image.LANCZOS)
    rows.append((w, on((255, 255, 255, 255), o), on((255, 255, 255, 255), s), on((3, 24, 53, 255), o), on((3, 24, 53, 255), s)))
sw = 2 * 680 + 3 * pad
sh = pad + sum(r[1].height * 2 + 30 + pad + 20 for r in rows) + pad
small = Image.new('RGB', (sw, sh), (236, 238, 242))
d = ImageDraw.Draw(small)
y = pad
for w, ow, sw_, od, sd in rows:
    d.text((pad, y), 'ORIGINAL @ %dpx (2x)' % w, fill=(20, 20, 20))
    d.text((pad * 2 + 680, y), 'SVG @ %dpx (2x)' % w, fill=(20, 20, 20))
    y += 20
    small.paste(ow, (pad, y)); small.paste(sw_, (pad * 2 + 680, y)); y += ow.height + 10
    small.paste(od, (pad, y)); small.paste(sd, (pad * 2 + 680, y)); y += ow.height + pad + 30
small.save(out.replace('.png', '-sizes.png'))
