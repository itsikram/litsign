"""
Redraw the red "S" tile of the logo as a raised, glossy button (owner's
reference picture, 2026-11-20): face colour #de0912, a darker side showing
along the bottom and right, a bright rim along the top edge, soft bevelled
edges, and the logo's own silver S a little smaller, centred on the face.

    python tile.py <logo 2000x667 png> <out.png> [s_scale]

The tile keeps its outer box (so js/logo-motion.js and inc/page-loader.php
measurements still fit); only the pixels inside it change. Everything stays
opaque (the animation lays clipped copies over the picture).
Needs Pillow and numpy.
"""
import sys

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

SRC, OUT = sys.argv[1], sys.argv[2]
S_SCALE = float(sys.argv[3]) if len(sys.argv) > 3 else 0.86
FACE = np.array([0xDE, 0x09, 0x12], dtype=np.float64)   # owner: #de0912
SS = 4                                                   # supersampling for smooth corners

im = Image.open(SRC).convert('RGBA')
a = np.asarray(im).astype(np.float64)
H, W = a.shape[:2]
R, G, B, A = a[..., 0], a[..., 1], a[..., 2], a[..., 3]

# ---- Find the tile: the solid block left of the letters, above the tagline.
region = np.zeros((H, W), bool)
region[150:460, 0:300] = True
solid = region & (A > 128)
ys, xs = np.where(solid)
X0, Y0, X1, Y1 = xs.min(), ys.min(), xs.max() + 1, ys.max() + 1
TW, TH = X1 - X0, Y1 - Y0
print('tile box', X0, Y0, X1, Y1)

# ---- Cut out the silver S (grey pixels; red ones belong to the tile).
tile = a[Y0:Y1, X0:X1].copy()
tr, tg, tb, ta = tile[..., 0], tile[..., 1], tile[..., 2], tile[..., 3]
redness = tr - (tg + tb) / 2.0
s_alpha = np.clip(1.0 - (redness - 25) / 90.0, 0, 1) * (ta / 255.0)
# Keep only the S's own connected area (drop stray grey pixels on the rim).
core = s_alpha > 0.6
cy, cx = np.where(core)
sx0, sy0, sx1, sy1 = cx.min(), cy.min(), cx.max() + 1, cy.max() + 1
pad = 3
sx0, sy0 = max(0, sx0 - pad), max(0, sy0 - pad)
sx1, sy1 = min(TW, sx1 + pad), min(TH, sy1 + pad)
s_rgba = np.dstack([tile[..., :3], s_alpha * 255])[sy0:sy1, sx0:sx1]
s_img = Image.fromarray(s_rgba.round().clip(0, 255).astype(np.uint8), 'RGBA')

# ---- Geometry of the button.
DEPTH = max(6, round(TW * 0.045))       # side thickness shown down-right
RADIUS = round(TW * 0.12)
fw, fh = TW - DEPTH, TH - DEPTH          # face size


def rounded_mask(w, h, r):
    big = Image.new('L', (w * SS, h * SS), 0)
    ImageDraw.Draw(big).rounded_rectangle((0, 0, w * SS - 1, h * SS - 1), r * SS, fill=255)
    return np.asarray(big.resize((w, h), Image.LANCZOS)).astype(np.float64) / 255.0


def blur(mask, radius):
    img = Image.fromarray((mask * 255).clip(0, 255).astype(np.uint8), 'L')
    return np.asarray(img.filter(ImageFilter.GaussianBlur(radius))).astype(np.float64) / 255.0


face_m = np.zeros((TH, TW))
face_m[:fh, :fw] = rounded_mask(fw, fh, RADIUS)

# Side: the face pushed down-right 1..DEPTH px; darker the further back.
side_m = np.zeros((TH, TW))
side_t = np.full((TH, TW), np.inf)
for k in range(1, DEPTH + 1):
    sh = np.zeros((TH, TW))
    sh[k:, k:] = face_m[:-k, :-k]
    newly = (sh > 0.5) & np.isinf(side_t)
    side_t[newly] = k
    side_m = np.maximum(side_m, sh)
side_t[np.isinf(side_t)] = DEPTH
t = side_t / DEPTH
near = np.array([150.0, 4.0, 10.0])
far = np.array([92.0, 0.0, 5.0])
side_rgb = near + (far - near) * t[..., None]
# The side's lower-right corner a touch darker (away from the light).
yy, xx = np.mgrid[0:TH, 0:TW].astype(np.float64)
side_rgb *= (1 - 0.18 * np.clip((xx / TW + yy / TH) - 1.2, 0, 1))[..., None]

# Face: #de0912 in the middle, a little lighter at the top, darker at the bottom.
v = yy / max(1, fh)
face_rgb = FACE + np.array([16.0, 8.0, 8.0]) * (0.5 - v)[..., None] * 2
# Soft "pillow" bevel: light from the top left.
soft = blur((face_m > 0.5).astype(float), 5)
gy, gx = np.gradient(soft)
shade = -(gx * -0.55 + gy * -0.83)
peak = np.percentile(np.abs(shade[face_m > 0.5]), 99.5) or 1.0
shade = np.clip(shade / peak, -1, 1)
inner = blur((face_m > 0.5).astype(float), 4)
band = np.clip(1 - np.abs(inner - 0.7) / 0.3, 0, 1)
face_rgb += (shade * band * 48)[..., None] * np.array([1.0, 0.7, 0.7])
# Glossy rim: a thin bright line just inside the top edge.
top_edge = np.zeros((TH, TW))
for x in range(fw):
    col = np.where(face_m[:, x] > 0.5)[0]
    if len(col):
        top_edge[col.min() + 2, x] = 1
rim = blur(top_edge, 1.2) * 2.2
rim *= np.clip(1 - np.abs(xx - fw / 2) / (fw / 2), 0, 1) ** 0.35     # fade at the corners
face_rgb += (np.clip(rim, 0, 1) * 70)[..., None] * np.array([1.0, 0.85, 0.85])
# Broad soft sheen on the upper half of the face.
sheen = np.exp(-((yy - fh * 0.18) / (fh * 0.22)) ** 2) * 7
face_rgb += sheen[..., None] * np.array([1.0, 0.6, 0.6])

# ---- Compose: side, face, then the S (with a soft contact shadow on the face).
out_rgb = side_rgb * side_m[..., None]
out_a = side_m.copy()
out_rgb = face_rgb * face_m[..., None] + out_rgb * (1 - face_m)[..., None]
out_a = face_m + out_a * (1 - face_m)

nw, nh = round(s_img.width * S_SCALE), round(s_img.height * S_SCALE)
s_small = s_img.resize((nw, nh), Image.LANCZOS)
ox = round((fw - nw) / 2)
oy = round((fh - nh) / 2)
s_np = np.asarray(s_small).astype(np.float64)
sa = s_np[..., 3] / 255.0
# Shadow of the S on the face (down-right, soft).
shadow = np.zeros((TH, TW))
shadow[oy + 5:oy + 5 + nh, ox + 4:ox + 4 + nw] = sa[:TH - oy - 5, :TW - ox - 4]
shadow = blur(shadow, 4) * face_m
out_rgb = out_rgb * (1 - 0.35 * shadow)[..., None]
canvas_rgb = out_rgb.copy()
canvas_a = out_a.copy()
region_rgb = canvas_rgb[oy:oy + nh, ox:ox + nw]
region_a = canvas_a[oy:oy + nh, ox:ox + nw]
canvas_rgb[oy:oy + nh, ox:ox + nw] = s_np[..., :3] * sa[..., None] + region_rgb * (1 - sa)[..., None]
canvas_a[oy:oy + nh, ox:ox + nw] = sa + region_a * (1 - sa)

res = a.copy()
# canvas_rgb is premultiplied by canvas_a; store straight colour.
res[Y0:Y1, X0:X1, :3] = np.where(canvas_a[..., None] > 0, canvas_rgb / np.maximum(canvas_a[..., None], 1e-6), 0)
res[Y0:Y1, X0:X1, 3] = np.clip(canvas_a, 0, 1) * 255
Image.fromarray(res.round().clip(0, 255).astype(np.uint8), 'RGBA').save(OUT, optimize=True)
print('saved', OUT, 'depth', DEPTH, 'radius', RADIUS, 'S', nw, 'x', nh)
