"""
Site icon (favicon) from the logo's red "S" tile (owner, 2026-11-20):
the same raised glossy button as utils/logo-motion/tile.py (face #de0912,
darker side bottom-right, bright top rim, soft bevel) with the logo's silver
S, drawn at full icon size instead of being cut out of the small logo.

    python icon.py <owner's flat artwork 2000x667> <out folder>

Writes:
  site-icon-512.png        WordPress Site Icon (Appearance > Customize > Site Identity), rounded tile, transparent corners
  favicon.ico              16, 32 and 48 px (tab version: flatter tile, bigger brighter S)
  favicon-32.png / -16.png  same tab version
  apple-touch-icon.png     180 px, full square (iOS rounds the corners itself and shows black behind transparency)
  android-chrome-192.png / -512.png   rounded tile, transparent corners
  maskable-512.png         full square, S inside the 80% safe zone (Android adaptive icons)
Needs Pillow and numpy.
"""
import os
import sys

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

SRC, OUT = sys.argv[1], sys.argv[2]
FACE = np.array([0xDE, 0x09, 0x12], dtype=np.float64)   # owner: #de0912
N = 1024                                                 # working size

# ---- The silver S, at the artwork's own resolution --------------------------
art = np.asarray(Image.open(SRC).convert('RGBA')).astype(np.float64)
tile = art[200:456, 40:300]
redness = tile[..., 0] - (tile[..., 1] + tile[..., 2]) / 2.0
s_alpha = np.clip(1.0 - (redness - 25) / 90.0, 0, 1) * (tile[..., 3] / 255.0)
ys, xs = np.where(s_alpha > 0.6)
pad = 3
y0, y1, x0, x1 = ys.min() - pad, ys.max() + 1 + pad, xs.min() - pad, xs.max() + 1 + pad
S_IMG = Image.fromarray(np.dstack([tile[y0:y1, x0:x1, :3], s_alpha[y0:y1, x0:x1] * 255]).round().clip(0, 255).astype(np.uint8), 'RGBA')


def blur(mask, radius):
    img = Image.fromarray((np.clip(mask, 0, 1) * 255).astype(np.uint8), 'L')
    return np.asarray(img.filter(ImageFilter.GaussianBlur(radius))).astype(np.float64) / 255.0


def rounded(w, h, r, ss=4):
    big = Image.new('L', (w * ss, h * ss), 0)
    ImageDraw.Draw(big).rounded_rectangle((0, 0, w * ss - 1, h * ss - 1), r * ss, fill=255)
    return np.asarray(big.resize((w, h), Image.LANCZOS)).astype(np.float64) / 255.0


def render(full_bleed=False, s_height=0.80, tab=False):
    """1024 px RGBA icon. full_bleed: the face fills the square (no side, no
    transparent corners), for Apple/maskable icons."""
    yy, xx = np.mgrid[0:N, 0:N].astype(np.float64)
    if full_bleed:
        margin, depth = 0, 0
        fw = fh = N
        face_m = np.ones((N, N))
    else:
        # Browser-tab size (16-48 px): almost no side, so the face and the S
        # get every pixel.
        margin = round(N * (0.0 if tab else 0.02))
        depth = round(N * (0.02 if tab else 0.045))
        fw = fh = N - 2 * margin - depth
        face_m = np.zeros((N, N))
        face_m[margin:margin + fh, margin:margin + fw] = rounded(fw, fh, round(fw * 0.14))
    k = N / 240.0     # tile.py's sizes were for a 240 px tile

    # Side (bottom-right thickness).
    side_m = np.zeros((N, N))
    side_t = np.full((N, N), np.inf)
    for d in range(1, depth + 1):
        sh = np.zeros((N, N))
        sh[d:, d:] = face_m[:-d, :-d]
        newly = (sh > 0.5) & np.isinf(side_t)
        side_t[newly] = d
        side_m = np.maximum(side_m, sh)
    side_t[np.isinf(side_t)] = max(depth, 1)
    t = side_t / max(depth, 1)
    side_rgb = np.array([150.0, 4.0, 10.0]) + (np.array([92.0, 0.0, 5.0]) - np.array([150.0, 4.0, 10.0])) * t[..., None]

    # Face: #de0912 in the middle, a little lighter at the top.
    v = (yy - margin) / max(1, fh)
    face_rgb = FACE + np.array([16.0, 8.0, 8.0]) * (0.5 - v)[..., None] * 2
    solid = (face_m > 0.5).astype(float)
    if not full_bleed:
        soft = blur(solid, 5 * k)
        gy, gx = np.gradient(soft)
        shade = -(gx * -0.55 + gy * -0.83)
        peak = np.percentile(np.abs(shade[solid > 0.5]), 99.5) or 1.0
        shade = np.clip(shade / peak, -1, 1)
        inner = blur(solid, 4 * k)
        band = np.clip(1 - np.abs(inner - 0.7) / 0.3, 0, 1)
        face_rgb += (shade * band * 48)[..., None] * np.array([1.0, 0.7, 0.7])
        # Bright rim just inside the top edge.
        top_edge = np.zeros((N, N))
        for x in range(margin, margin + fw):
            col = np.where(face_m[:, x] > 0.5)[0]
            if len(col):
                top_edge[min(N - 1, col.min() + round(2 * k)), x] = 1
        rim = np.clip(blur(top_edge, 1.2 * k) * 2.2 * k, 0, 1)
        rim *= np.clip(1 - np.abs(xx - (margin + fw / 2)) / (fw / 2), 0, 1) ** 0.35
        face_rgb += (rim * 70)[..., None] * np.array([1.0, 0.85, 0.85])
    sheen = np.exp(-((yy - margin - fh * 0.18) / (fh * 0.22)) ** 2) * 7
    face_rgb += sheen[..., None] * np.array([1.0, 0.6, 0.6])

    rgb = side_rgb * side_m[..., None]
    alpha = side_m.copy()
    rgb = face_rgb * face_m[..., None] + rgb * (1 - face_m)[..., None]
    alpha = face_m + alpha * (1 - face_m)

    # The S, centred on the face, with a soft shadow down-right.
    nh = round(fh * s_height)
    nw = round(S_IMG.width * nh / S_IMG.height)
    s_src = S_IMG
    if tab:
        # Brighter, higher-contrast S so it still reads as an S at 16 px.
        from PIL import ImageEnhance
        rgb_s = ImageEnhance.Contrast(S_IMG.convert('RGB')).enhance(1.45)
        rgb_s = ImageEnhance.Brightness(rgb_s).enhance(1.08)
        s_src = Image.merge('RGBA', (*rgb_s.split(), S_IMG.split()[3]))
    s_big = s_src.resize((nw, nh), Image.LANCZOS).filter(ImageFilter.UnsharpMask(radius=1.2, percent=60, threshold=1))
    s_np = np.asarray(s_big).astype(np.float64)
    sa = s_np[..., 3] / 255.0
    ox = margin + round((fw - nw) / 2) + round(nw * 0.02)
    oy = margin + round((fh - nh) / 2)
    shadow = np.zeros((N, N))
    dx, dy = round(4 * k), round(5 * k)
    shadow[oy + dy:oy + dy + nh, ox + dx:ox + dx + nw] = sa
    shadow = blur(shadow, 4 * k) * face_m
    rgb = rgb * (1 - 0.35 * shadow)[..., None]
    region = rgb[oy:oy + nh, ox:ox + nw]
    rgb[oy:oy + nh, ox:ox + nw] = s_np[..., :3] * sa[..., None] + region * (1 - sa)[..., None]
    alpha[oy:oy + nh, ox:ox + nw] = sa + alpha[oy:oy + nh, ox:ox + nw] * (1 - sa)

    straight = np.where(alpha[..., None] > 0, rgb / np.maximum(alpha[..., None], 1e-6), 0)
    return Image.fromarray(np.dstack([straight, alpha * 255]).round().clip(0, 255).astype(np.uint8), 'RGBA')


def small(img, size):
    out = img.resize((size, size), Image.LANCZOS)
    if size <= 64:
        out = out.filter(ImageFilter.UnsharpMask(radius=0.6, percent=80, threshold=1))
    return out


os.makedirs(OUT, exist_ok=True)
tile_icon = render()
bleed_icon = render(full_bleed=True, s_height=0.74)
mask_icon = render(full_bleed=True, s_height=0.62)
tab_icon = render(s_height=0.88, tab=True)

small(tile_icon, 512).save(os.path.join(OUT, 'site-icon-512.png'), optimize=True)
small(tile_icon, 512).save(os.path.join(OUT, 'android-chrome-512.png'), optimize=True)
small(tile_icon, 192).save(os.path.join(OUT, 'android-chrome-192.png'), optimize=True)
small(tab_icon, 32).save(os.path.join(OUT, 'favicon-32.png'), optimize=True)
small(tab_icon, 16).save(os.path.join(OUT, 'favicon-16.png'), optimize=True)
small(bleed_icon, 180).convert('RGB').save(os.path.join(OUT, 'apple-touch-icon.png'), optimize=True)
small(mask_icon, 512).convert('RGB').save(os.path.join(OUT, 'maskable-512.png'), optimize=True)
ico48 = small(tab_icon, 48)
ico48.save(os.path.join(OUT, 'favicon.ico'), sizes=[(16, 16), (32, 32), (48, 48)],
           append_images=[small(tab_icon, 32), small(tab_icon, 16)])
tile_icon.save(os.path.join(OUT, 'site-icon-1024-master.png'), optimize=True)
print('icons written to', OUT, sorted(os.listdir(OUT)))
