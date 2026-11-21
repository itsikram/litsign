"""
Realistic finish for the owner's flat header logo artwork (2026-11-20).

    python realistic.py <flat artwork 2000x667 png> <out.png> [depth]

Keeps every shape, colour family and font of the owner's picture and adds
light and depth, like a photographed channel-letter sign:
- orange letters: deeper returns (sides) that darken away from the face, a
  thin trim-cap line, a lit face that is brighter at the top, and a soft
  bevel (light from the top left);
- red tile: soft bevel and a gentle top-to-bottom gradient (silver S kept);
- blue sign: glossy plate with a bevelled rim, a soft shadow under the white
  "online .com" lettering, and a rounded (cylinder-shaded) pole;
- tagline: untouched (crisp navy).
Everything stays fully opaque where the picture was opaque (no see-through
shadows), because js/logo-motion.js lays clipped copies over the picture.

After running: python slice.py <out.png> <theme img/brand> ; php avif.php
<theme img/brand> ; paste the printed GEO into js/logo-motion.js.
Needs Pillow and numpy.
"""
import sys

import numpy as np
from PIL import Image, ImageFilter

SRC, OUT = sys.argv[1], sys.argv[2]
DEPTH = int(sys.argv[3]) if len(sys.argv) > 3 else 16
# 'classic' (19:05 version) or 'pillow': rounded, glossy letter edges with a
# darker outline stroke (owner's reference picture, 2026-11-20 ~20:45).
STYLE = sys.argv[4] if len(sys.argv) > 4 else 'classic'

im = Image.open(SRC).convert('RGBA')
a = np.asarray(im).astype(np.float64)
H, W = a.shape[:2]
R, G, B, A = a[..., 0], a[..., 1], a[..., 2], a[..., 3] / 255.0

# Regions of the 2000 x 667 artwork (measured; see README).
LET_X0, LET_X1, LET_Y0, LET_Y1 = 298, 1703, 180, 462
PLATE_Y1 = 329            # last row of the blue plate (pole below)
TILE = (40, 196, 300, 456)


def blur(mask, radius):
    img = Image.fromarray(np.clip(mask * 255, 0, 255).astype(np.uint8), 'L')
    return np.asarray(img.filter(ImageFilter.GaussianBlur(radius))).astype(np.float64) / 255.0


def bevel(mask, radius, light=(-0.55, -0.83)):
    """-1..1 shading from a soft edge: + on edges facing the light."""
    soft = blur(mask, radius)
    gy, gx = np.gradient(soft)
    shade = -(gx * light[0] + gy * light[1])
    peak = np.percentile(np.abs(shade[mask > 0.5]), 99.5) or 1.0
    return np.clip(shade / peak, -1, 1)


def over(dst_rgb, dst_a, src_rgb, src_a):
    out_a = src_a + dst_a * (1 - src_a)
    safe = np.where(out_a > 0, out_a, 1)
    out_rgb = (src_rgb * src_a[..., None] + dst_rgb * (dst_a * (1 - src_a))[..., None]) / safe[..., None]
    return out_rgb, out_a


rgb = a[..., :3].copy()
alpha = A.copy()

# ---- Orange letters ----------------------------------------------------------
region = np.zeros((H, W), bool)
region[LET_Y0:LET_Y1, LET_X0:LET_X1] = True
orange = region & (R > 90) & (R > G * 1.4) & (B < 110)
# Face weight from colour: faces are R ~225, returns R ~172.
face_w = np.clip((R - 188) / 24.0, 0, 1) * orange
face_a = A * face_w
old_a = A * orange

# Returns: the face pushed straight down 1..DEPTH px; depth map for shading.
ret_a = np.zeros((H, W))
depth = np.full((H, W), np.inf)
for k in range(1, DEPTH + 1):
    shifted = np.zeros((H, W))
    shifted[k:, :] = face_a[:-k, :]
    newly = (shifted > 0.5) & (depth == np.inf)
    depth[newly] = k
    ret_a = np.maximum(ret_a, shifted)
depth[np.isinf(depth)] = DEPTH * 0.5
t = np.clip(depth / DEPTH, 0, 1)
near = np.array([196.0, 62.0, 6.0])      # just under the face (trim cap)
mid = np.array([170.0, 47.0, 2.0])       # the artwork's own return colour
far = np.array([112.0, 28.0, 0.0])       # back edge, in shade
ret_rgb = np.where((t < 0.5)[..., None], near + (mid - near) * (t / 0.5)[..., None],
                   mid + (far - mid) * ((t - 0.5) / 0.5)[..., None])
ret_rgb[(depth <= 2)] = np.array([150.0, 40.0, 2.0])  # dark trim-cap seam
# Soft vertical light on the sides: a touch lighter where the light hits (left).
side = bevel((ret_a > 0.5).astype(float), 2.0)
ret_rgb = ret_rgb + (side * 10)[..., None]

# Face: lit acrylic, brighter at the top of each glyph's height band.
ys = np.arange(H)[:, None].repeat(W, 1).astype(float)
grad = np.clip((ys - 225) / (455 - 225), 0, 1)
face_top = np.array([244.0, 104.0, 22.0])
face_bot = np.array([214.0, 70.0, 0.0])
face_rgb = face_top + (face_bot - face_top) * grad[..., None]
bv = bevel((face_w > 0.5).astype(float), 3.0)
inner = blur((face_w > 0.5).astype(float), 2.5)          # 1 deep inside, 0.5 at the edge
edge = np.clip(1 - np.abs(inner - 0.75) / 0.25, 0, 1)    # a band just inside the edge
face_rgb = face_rgb + (bv * edge * 26)[..., None] * np.array([1.0, 0.9, 0.6])
# Faint wide sheen across the upper part of the faces.
sheen = np.exp(-((ys - 280) / 55.0) ** 2) * 10
face_rgb = face_rgb + sheen[..., None] * np.array([1.0, 0.8, 0.5])

if 'pillow' == STYLE:
    # Vivid face, brighter at the top (reference: #fa6f00 -> #f65200).
    face_rgb = np.array([251.0, 114.0, 0.0]) + (np.array([245.0, 80.0, 0.0]) - np.array([251.0, 114.0, 0.0])) * grad[..., None]
    # Rounded rim: the face rolls over at its edges like a moulded letter.
    # Height comes from a blurred copy of the face (smooth, no stair steps):
    # 0 at the outline, full height about RIM px inside, flat beyond.
    RIM = 13
    solid_face = face_w > 0.5
    soft = blur(solid_face.astype(float), RIM / 2.2)
    s_ = np.clip((soft - 0.5) / 0.5, 0, 1)
    z = np.sqrt(np.clip(1 - (1 - s_) ** 2, 0, 1)) * RIM * 0.85
    z = blur(z / (RIM * 0.85), 0.8) * RIM * 0.85
    gy, gx = np.gradient(z)
    norm = np.sqrt(gx ** 2 + gy ** 2 + 1)
    nx, ny, nz = -gx / norm, -gy / norm, 1 / norm
    L = np.array([-0.35, -0.75, 0.56])
    L = L / np.linalg.norm(L)
    lit = nx * L[0] + ny * L[1] + nz * L[2]
    f = (lit - L[2]) / (1 - L[2])                  # 0 on the flat face, + toward the light
    f = np.clip(f, -1, 1) * np.clip(face_w, 0, 1)
    hi = np.array([255.0, 170.0, 84.0])
    lo = np.array([214.0, 64.0, 0.0])
    up = np.clip(f, 0, 1)[..., None]
    down = np.clip(-f, 0, 1)[..., None]
    face_rgb = face_rgb + (hi - face_rgb) * up * 0.8 + (lo - face_rgb) * down * 0.75
    # Glossy glint where the rim faces the light most.
    face_rgb = face_rgb + (np.clip(f, 0, 1) ** 4 * 55)[..., None] * np.array([1.0, 1.0, 0.85])
    # Thin darker outline stroke round the face (the outer ~1.5 px).
    ring = np.clip(1 - (soft - 0.5) / 0.09, 0, 1) * solid_face
    face_rgb = face_rgb * (1 - 0.35 * ring)[..., None] + np.array([196.0, 62.0, 0.0]) * (0.35 * ring)[..., None]
    # Darker, redder sides (reference: #872000 under the face -> #701b00).
    near = np.array([168.0, 46.0, 0.0])
    mid = np.array([134.0, 33.0, 0.0])
    far = np.array([100.0, 23.0, 0.0])
    ret_rgb = np.where((t < 0.5)[..., None], near + (mid - near) * (t / 0.5)[..., None],
                       mid + (far - mid) * ((t - 0.5) / 0.5)[..., None])
    ret_rgb[(depth <= 2)] = np.array([118.0, 28.0, 0.0])
    ret_rgb = ret_rgb + (side * 8)[..., None]

# Build the letters: clear the old ones, then returns, then faces.
keep_a = alpha * (1 - orange)
let_rgb, let_a = over(rgb, keep_a, ret_rgb, ret_a * region)
let_rgb, let_a = over(let_rgb, let_a, face_rgb, face_a)
rgb, alpha = let_rgb, let_a

# ---- Red tile ----------------------------------------------------------------
x0, y0, x1, y1 = TILE
tile = np.zeros((H, W), bool)
tile[y0:y1, x0:x1] = True
red = tile & (R > 140) & (G < 90) & (B < 90) & (A > 0.5)
tile_mask = np.zeros((H, W))
tile_mask[y0:y1, x0:x1] = (A[y0:y1, x0:x1] > 0.5)
tbv = bevel(tile_mask, 4.0)
tin = blur(tile_mask, 3.0)
tedge = np.clip(1 - np.abs(tin - 0.72) / 0.28, 0, 1)
tg = np.clip((ys - 208) / (446 - 208), 0, 1)
tile_shade = (tbv * tedge * 34) + (1 - tg) * 10 - tg * 8
rgb[red] = rgb[red] + tile_shade[red][:, None] * np.array([1.0, 0.55, 0.55])

# ---- Blue sign -----------------------------------------------------------------
sign_box = np.zeros((H, W), bool)
sign_box[180:420, 1690:1965] = True
blue = sign_box & (B > R + 30) & (A > 0.05)
white = sign_box & (R > 200) & (G > 200) & (B > 200) & (A > 0.5)
plate = blue.copy()
plate[PLATE_Y1 + 1:, :] = False
pole = blue & ~plate
plate_full = plate | (white & (ys <= PLATE_Y1))
pm = plate_full.astype(float)
pbv = bevel(pm, 4.0)
pin = blur(pm, 3.0)
pedge = np.clip(1 - np.abs(pin - 0.72) / 0.28, 0, 1)
pg = np.clip((ys - 190) / (PLATE_Y1 - 190), 0, 1)
top_c = np.array([18.0, 104.0, 160.0])
bot_c = np.array([0.0, 66.0, 112.0])
plate_rgb = top_c + (bot_c - top_c) * pg[..., None]
plate_rgb = plate_rgb + (pbv * pedge * 38)[..., None] * np.array([0.8, 1.0, 1.0])
# Gloss: a soft light band over the top third of the plate.
gloss = np.exp(-((ys - 214) / 22.0) ** 2) * 16
plate_rgb = plate_rgb + gloss[..., None]
# Shadow of the white letters on the plate (down-right).
txt = white.astype(float)
sh = np.zeros((H, W))
sh[3:, 2:] = txt[:-3, :-2]
sh = blur(sh, 1.6) * (1 - blur(txt, 0.6))
plate_rgb = plate_rgb * (1 - 0.45 * sh)[..., None]
rgb[plate] = plate_rgb[plate]
# White lettering: very slight top-to-bottom tint so it reads as printed vinyl.
wt = white & (ys <= PLATE_Y1)
rgb[wt] = rgb[wt] - (pg[wt] * 14)[:, None]

# Pole: cylinder shading across its width, shadow under the plate.
pxs = np.where(pole.any(axis=0))[0]
if len(pxs):
    px0, px1 = pxs.min(), pxs.max()
    u = (np.arange(W) - px0) / max(1, px1 - px0)
    cyl = np.cos((u - 0.32) * np.pi * 0.95)
    cyl = np.clip(cyl, -1, 1)
    pole_base = np.array([4.0, 78.0, 124.0])
    pole_rgb = pole_base[None, None, :] * (0.62 + 0.38 * cyl)[None, :, None] + (np.clip(cyl, 0, 1) ** 8 * 40)[None, :, None]
    pole_rgb = np.repeat(pole_rgb, H, axis=0)
    under = np.clip(1 - (ys - PLATE_Y1) / 14.0, 0, 1)
    pole_rgb = pole_rgb * (1 - 0.35 * under)[..., None]
    rgb[pole] = pole_rgb[pole]

out = np.dstack([np.clip(rgb, 0, 255), np.clip(alpha, 0, 1) * 255]).round().astype(np.uint8)
Image.fromarray(out, 'RGBA').save(OUT, optimize=True)
print('saved', OUT)
