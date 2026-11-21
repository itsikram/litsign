"""Redraw the "online.com" sign on the header logo (2172 x 724).

Built 2026-11-20 (owner: make the "online.com" sign look professional).

Usage: python sign.py <logo.png> <out.png> [variant]   (needs Pillow, numpy, fontTools, brotli)
Variants: a = navy cabinet, white "online" + orange ".com", silver post to the ground
          b = same plus a thin light-blue inner trim
          c = like a, ".com" smaller and tracked
Only pixels in the sign's slot change: x >= 1871 above row 338 and x >= 2024
below it (the slot js/logo-motion.js turns).
"""
import io
import os
import sys
import numpy as np
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
from PIL import Image, ImageDraw, ImageFilter, ImageFont, ImageChops

SRC, OUT = sys.argv[1], sys.argv[2]
VARIANT = sys.argv[3] if len(sys.argv) > 3 else 'a'

# The site's heading font (theme fonts/plus-jakarta-sans-latin-var.woff2) at weight 800.
_var = TTFont(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'fonts', 'plus-jakarta-sans-latin-var.woff2'))
_ttf = instancer.instantiateVariableFont(_var, {'wght': 800})
_ttf.flavor = None
_buf = io.BytesIO()
_ttf.save(_buf)
FONT_BYTES = _buf.getvalue()

S = 4                       # supersampling
X0, Y0, X1, Y1 = 1860, 150, 2172, 590
W, H = (X1 - X0) * S, (Y1 - Y0) * S

# Geometry in logo pixels.
PL, PT, PR, PB, RAD = 1878, 170, 2164, 318, 26   # plate face
DEPTH = 7                                         # plate thickness shown below the face
POST_X, POST_W, POST_TOP, POST_BOT = 2051, 21, 314, 548
BASE_W, BASE_T, BASE_B = 46, 544, 556
COLLAR_W, COLLAR_T, COLLAR_B = 33, PB + DEPTH - 2, PB + DEPTH + 10


def P(x, y):
    return ((x - X0) * S, (y - Y0) * S)


def box(l, t, r, b):
    return [P(l, t), P(r, b)]


def layer():
    return Image.new('RGBA', (W, H), (0, 0, 0, 0))


def mask_rr(l, t, r, b, rad):
    m = Image.new('L', (W, H), 0)
    ImageDraw.Draw(m).rounded_rectangle(box(l, t, r, b), radius=rad * S, fill=255)
    return m


def vgrad(stops, t, b):
    """Full-size RGBA image with a vertical gradient between logo rows t and b."""
    ys = np.arange(H) / S + Y0
    f = np.clip((ys - t) / max(1, b - t), 0, 1)
    pos = np.array([s[0] for s in stops])
    cols = np.array([s[1] for s in stops], dtype=float)
    out = np.zeros((H, 4))
    for c in range(cols.shape[1]):
        out[:, c] = np.interp(f, pos, cols[:, c])
    if cols.shape[1] == 3:
        out[:, 3] = 255
    img = np.repeat(out[:, None, :], W, axis=1)
    return Image.fromarray(img.astype(np.uint8), 'RGBA')


def hgrad(stops, l, r):
    xs = np.arange(W) / S + X0
    f = np.clip((xs - l) / max(1, r - l), 0, 1)
    pos = np.array([s[0] for s in stops])
    cols = np.array([s[1] for s in stops], dtype=float)
    out = np.zeros((W, 4))
    for c in range(3):
        out[:, c] = np.interp(f, pos, cols[:, c])
    out[:, 3] = 255
    img = np.repeat(out[None, :, :], H, axis=0)
    return Image.fromarray(img.astype(np.uint8), 'RGBA')


def paint(canvas, fill, m):
    """Composite `fill` (RGBA image or colour) through mask m onto canvas."""
    if not isinstance(fill, Image.Image):
        fill = Image.new('RGBA', (W, H), fill)
    f = fill.copy()
    a = ImageChops.multiply(f.getchannel('A'), m)
    f.putalpha(a)
    canvas.alpha_composite(f)


def shift(m, dx, dy):
    return ImageChops.offset(m, int(dx * S), int(dy * S))


SILVER = [(0, (118, 120, 126)), (0.18, (196, 198, 202)), (0.38, (246, 246, 247)),
          (0.55, (214, 215, 218)), (0.82, (150, 152, 158)), (1, (104, 106, 112))]

c = layer()

# ---- Post (behind the plate), base and collar: brushed silver like the "S" mark.
post = mask_rr(POST_X - POST_W / 2, POST_TOP, POST_X + POST_W / 2, POST_BOT, POST_W / 2 - 1)
paint(c, hgrad(SILVER, POST_X - POST_W / 2, POST_X + POST_W / 2), post)
base = mask_rr(POST_X - BASE_W / 2, BASE_T, POST_X + BASE_W / 2, BASE_B, 4)
paint(c, hgrad(SILVER, POST_X - BASE_W / 2, POST_X + BASE_W / 2), base)
base_top = mask_rr(POST_X - BASE_W / 2 + 1, BASE_T, POST_X + BASE_W / 2 - 1, BASE_T + 2.2, 1)
paint(c, (255, 255, 255, 120), base_top)
paint(c, (40, 42, 48, 90), ImageChops.subtract(base, shift(base, 0, -2.2)))
collar = mask_rr(POST_X - COLLAR_W / 2, COLLAR_T, POST_X + COLLAR_W / 2, COLLAR_B, 4)
paint(c, hgrad(SILVER, POST_X - COLLAR_W / 2, POST_X + COLLAR_W / 2), collar)
paint(c, (40, 42, 48, 80), ImageChops.subtract(collar, shift(collar, 0, -2)))

# ---- Plate thickness (the cabinet's return), darker navy, shown under the face.
side = mask_rr(PL, PT + DEPTH, PR, PB + DEPTH, RAD)
paint(c, vgrad([(0, (8, 24, 44)), (1, (3, 12, 24))], PB - 10, PB + DEPTH), side)

# ---- Face: deep navy with a soft vertical light.
face = mask_rr(PL, PT, PR, PB, RAD)
paint(c, vgrad([(0, (42, 86, 138)), (0.45, (20, 52, 92)), (1, (10, 31, 58))], PT, PB), face)

# Bevel: light rim top-left, dark rim bottom-right (rounded "puffy" edge like the letters).
rim_light = ImageChops.subtract(face, shift(face, 4, 5)).filter(ImageFilter.GaussianBlur(2.4 * S))
paint(c, (200, 228, 255, 175), ImageChops.multiply(rim_light, face))
rim_dark = ImageChops.subtract(face, shift(face, -4, -5)).filter(ImageFilter.GaussianBlur(2.4 * S))
paint(c, (0, 6, 16, 185), ImageChops.multiply(rim_dark, face))

# Gloss across the upper half.
inner = mask_rr(PL + 5, PT + 5, PR - 5, PB - 5, RAD - 5)
gloss = vgrad([(0, (255, 255, 255, 70)), (0.48, (255, 255, 255, 14)), (0.5, (255, 255, 255, 0)), (1, (255, 255, 255, 0))], PT + 4, PB)
paint(c, gloss, inner)

if VARIANT == 'b':
    trim = ImageChops.subtract(mask_rr(PL + 7, PT + 7, PR - 7, PB - 7, RAD - 7), mask_rr(PL + 9.5, PT + 9.5, PR - 9.5, PB - 9.5, RAD - 9.5))
    paint(c, (31, 168, 222, 170), trim)

# ---- Lettering: Plus Jakarta Sans ExtraBold (the site's heading font), centred.
def text_mask(txt, size, tracking, cx, baseline):
    font = ImageFont.truetype(io.BytesIO(FONT_BYTES), int(size * S))
    widths = [font.getlength(ch) for ch in txt]
    total = sum(widths) + tracking * S * (len(txt) - 1)
    m = Image.new('L', (W, H), 0)
    md = ImageDraw.Draw(m)
    x = (cx - X0) * S - total / 2
    for ch, w in zip(txt, widths):
        md.text((x, (baseline - Y0) * S), ch, font=font, fill=255, anchor='ls')
        x += w + tracking * S
    return m


cx = (PL + PR) / 2
if VARIANT == 'c':
    lines = [('online', 76, 0, 0), ('.com', 56, 3, 52)]
else:
    lines = [('online', 68, 0.6, 0), ('.com', 68, 0.6, 53)]   # (text, size, tracking, baseline offset)
masks = [text_mask(t, s, tr, cx, 300 + off) for t, s, tr, off in lines]
# Centre the two lines as one block in the face (equal padding top and bottom).
union = masks[0]
for m in masks[1:]:
    union = ImageChops.lighter(union, m)
l, t, r, b = union.getbbox()
dy = ((PT + PB) / 2 - Y0) * S - (t + b) / 2
masks = [shift(m, 0, dy / S) for m in masks]
top = (t + dy) / S + Y0
mid = top + (masks[0].getbbox()[3] - masks[0].getbbox()[1]) / S
WHITE = vgrad([(0, (255, 255, 255)), (1, (212, 225, 240))], top, mid)
ORANGE = vgrad([(0, (255, 170, 76)), (0.55, (255, 114, 16)), (1, (234, 84, 0))], mid + 10, (b + dy) / S + Y0)
for m, fill in zip(masks, (WHITE, ORANGE)):
    sh = shift(m, 0, 2.6).filter(ImageFilter.GaussianBlur(1.8 * S))   # depth under the letters
    paint(c, (2, 10, 22, 175), ImageChops.multiply(sh, face))
    paint(c, fill, m)

# ---- Into the logo: clear the old sign's slot, drop the new sign in.
sign = c.resize((X1 - X0, Y1 - Y0), Image.LANCZOS)
logo = Image.open(SRC).convert('RGBA')
arr = np.asarray(logo).copy()
arr[:338, 1871:] = 0          # old plate (the letters start at row 338)
arr[338:470, 2024:] = 0       # old pole (the last letter ends at x 2019)
logo = Image.fromarray(arr, 'RGBA')
logo.alpha_composite(sign, (X0, Y0))
logo.save(OUT, optimize=True)
print('saved', OUT, logo.size)
