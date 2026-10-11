"""Trace the Storefront Sign Online raster logo into paths (JSON).

Flat-colour parts (tile, wordmark, tagline, badge) are colour-segmented and
traced with potrace. The silver "S" is split into its faces by luminance
edges; each face gets a linear gradient fitted to the original pixels.
"""
import json
import sys

import cv2
import numpy as np
import potrace
from PIL import Image

SRC = sys.argv[1]
OUT = sys.argv[2]
TOL = float(sys.argv[3]) if len(sys.argv) > 3 else 0.6
PREC = int(sys.argv[4]) if len(sys.argv) > 4 else 1

src = Image.open(SRC).convert('RGBA')
W, H = src.size


def _last_token(s):
    tok = ''
    for ch in reversed(s):
        if ch.isdigit() or ch == '.':
            tok = ch + tok
        else:
            break
    return tok


def fmt(v):
    """Integer in 1/10^PREC units -> shortest decimal string."""
    s = ('%.*f' % (PREC, v / 10 ** PREC)) if PREC else str(v)
    if PREC:
        s = s.rstrip('0').rstrip('.')
    if s.startswith('0.'):
        s = s[1:]
    elif s.startswith('-0.'):
        s = '-' + s[2:]
    return '0' if s in ('', '-', '-0') else s


def join(vals):
    """Join numbers with the fewest separators."""
    res = ''
    for v in vals:
        t = fmt(v)
        if not res or res[-1] in 'MmLlCcz' or t.startswith('-') or (t.startswith('.') and '.' in _last_token(res)):
            res += t
        else:
            res += ' ' + t
    return res


def trace(mask, scale, ox=0, oy=0, turd=8, alpha=1.0, tol=None):
    """potrace a boolean mask and return relative path data."""
    tol = TOL if tol is None else tol
    f = 10 ** PREC
    q = lambda p: (round((p.x / scale + ox) * f), round((p.y / scale + oy) * f))
    bm = potrace.Bitmap(~mask)  # potracer inverts its input
    plist = bm.trace(turdsize=int(turd * scale * scale), alphamax=alpha, opticurve=True, opttolerance=tol)
    d = ''
    for curve in plist:
        cur = q(curve.start_point)
        d += 'M' + join(cur)
        last = None
        for seg in curve.segments:
            if seg.is_corner:
                pts, cmd = [q(seg.c), q(seg.end_point)], 'l'
            else:
                pts, cmd = [q(seg.c1), q(seg.c2), q(seg.end_point)], 'c'
            rel = []
            base = cur
            for p in pts:
                rel += [p[0] - base[0], p[1] - base[1]]
                if cmd == 'l':
                    base = p
            cur = pts[-1]
            body = join(rel)
            if cmd == last:
                d += body if body.startswith('-') or (body.startswith('.') and '.' in _last_token(d)) else ' ' + body
            else:
                d += cmd + body
            last = cmd
        d += 'z'
    return d


def clean(mask, k=1):
    m = mask.astype(np.uint8)
    if k:
        m = cv2.morphologyEx(m, cv2.MORPH_OPEN, np.ones((k * 2 + 1, k * 2 + 1), np.uint8))
    return m.astype(bool)


def fill_holes(mask):
    m = mask.astype(np.uint8) * 255
    cnts, _ = cv2.findContours(m, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_NONE)
    out = np.zeros_like(m)
    cv2.drawContours(out, cnts, -1, 255, -1)
    return out > 0


def components(mask, min_area=30, up=1):
    """Per-glyph masks left to right; a small part sitting over a larger one
    (the dot of an i) is merged into it."""
    n, lab, stats, _ = cv2.connectedComponentsWithStats(mask.astype(np.uint8), 8)
    items = []
    for i in range(1, n):
        x, y, w, h, area = stats[i]
        if area >= min_area * up * up:
            items.append([x, x + w, [i], y, y + h])
    items.sort(key=lambda t: (t[0], t[3]))
    merged = []
    for it in items:
        m = merged[-1] if merged else None
        if m:
            small = min(it[4] - it[3], m[4] - m[3]) < 0.45 * max(it[4] - it[3], m[4] - m[3])
            vgap = max(it[3], m[3]) - min(it[4], m[4])
            overlap = (min(it[1], m[1]) - it[0]) > 0.5 * min(it[1] - it[0], m[1] - m[0])
            if small and overlap and vgap < 0.5 * max(it[4] - it[3], m[4] - m[3]):
                m[1], m[3], m[4] = max(m[1], it[1]), min(m[3], it[3]), max(m[4], it[4])
                m[2] += it[2]
                continue
        merged.append(it)
    return [np.isin(lab, m[2]) for m in merged]


# ---- Flat colour parts, traced at 2x -------------------------------------
UP = 2
im = np.array(src.resize((W * UP, H * UP), Image.LANCZOS)).astype(int)
r, g, b, a = im[..., 0], im[..., 1], im[..., 2], im[..., 3]
Yi, Xi = np.mgrid[0:H * UP, 0:W * UP]
X, Y = Xi / UP, Yi / UP
op = a > 127
sat = im[..., :3].max(2) - im[..., :3].min(2)
orange = op & (r > 150) & (g > 40) & (b < 110) & (g - b > 25) & (X > 275)
red = op & (r > 120) & (g < 60) & (r - g > 90) & ~orange
silver_lo = op & (sat < 45) & (X < 270) & (Y > 120)
blue = op & (b > 120) & (b - r > 60)
white = op & (sat < 40) & (r > 200) & (X > 1880) & (Y < 260)

res = {}
res['tile'] = trace(fill_holes(clean((red & (X < 270)) | silver_lo, 1)), UP, alpha=1.2)
res['word'] = [trace(m, UP) for m in components(clean(orange, 1), up=UP)]
res['tag'] = [trace(m, UP, turd=2) for m in components(clean(red & (X > 275) & (Y > 330), 0), min_area=6, up=UP)]
bub = fill_holes(clean(blue, 1))
res['bubble'] = trace(bub, UP, alpha=1.2)
res['online'] = [trace(m, UP, turd=2) for m in components(clean(white & bub, 0), min_area=6, up=UP)]

# ---- Silver S, traced at 4x from a crop --------------------------------------
U4 = 4
CX, CY = 40, 120
crop = src.crop((CX, CY, 230, 380))
cw, chh = crop.size
ci = np.array(crop.resize((cw * U4, chh * U4), Image.LANCZOS)).astype(np.float32)
cr, cg, cb, ca = [ci[..., i] for i in range(4)]
csat = ci[..., :3].max(2) - ci[..., :3].min(2)
S = (ca > 127) & (csat < 45)
S = cv2.morphologyEx(S.astype(np.uint8), cv2.MORPH_OPEN, np.ones((5, 5), np.uint8)) > 0
lum = 0.299 * cr + 0.587 * cg + 0.114 * cb
blur = cv2.GaussianBlur(lum, (0, 0), 2.0)
mag = np.hypot(cv2.Sobel(blur, cv2.CV_32F, 1, 0), cv2.Sobel(blur, cv2.CV_32F, 0, 1))
edge = cv2.dilate((mag > 25).astype(np.uint8), np.ones((3, 3), np.uint8)) > 0
n, lab, stats, _ = cv2.connectedComponentsWithStats((S & ~edge).astype(np.uint8), 4)
faces = [lab == i for i in range(1, n) if stats[i, 4] > 2000]

# Split the big front region: the dark notch interiors are their own faces.
faces.sort(key=lambda m: -m.sum())
front = faces[0]
DARK = float(sys.argv[5]) if len(sys.argv) > 5 else 150
dark = front & (cv2.GaussianBlur(lum, (0, 0), 3) < DARK)
dark = cv2.morphologyEx(dark.astype(np.uint8), cv2.MORPH_OPEN, np.ones((7, 7), np.uint8)) > 0
nd, dlab, dstats, _ = cv2.connectedComponentsWithStats(dark.astype(np.uint8), 4)
darks = [dlab == i for i in range(1, nd) if dstats[i, 4] > 1500]
dark_all = np.zeros_like(front)
for m in darks:
    dark_all |= m
faces = [front & ~(cv2.dilate(dark_all.astype(np.uint8), np.ones((5, 5), np.uint8)) > 0)] + faces[1:] + darks

faces = [f for f in faces if f.sum() > 4000]

# Grow the faces until they tile the whole S (each pixel goes to the nearest
# face), then pull them back 2px from each other so a thin bevel highlight
# (the base layer) shows only along interior edges, never around the outline.
dists = np.stack([cv2.distanceTransform((~f).astype(np.uint8), cv2.DIST_L2, 5) for f in faces])
owner = dists.argmin(0)
rim = S & ~(cv2.erode(S.astype(np.uint8), np.ones((9, 9), np.uint8)) > 0)
GAP = int(sys.argv[6]) if len(sys.argv) > 6 else 5
grown = []
for i in range(len(faces)):
    f = (owner == i) & S
    shrunk = cv2.erode(f.astype(np.uint8), np.ones((GAP, GAP), np.uint8)) > 0
    grown.append(shrunk | (f & rim & ~(cv2.dilate(((owner != i) & S).astype(np.uint8), np.ones((GAP, GAP), np.uint8)) > 0)) | (f & rim & (cv2.erode(S.astype(np.uint8), np.ones((3, 3), np.uint8)) == 0)))
faces_fit = faces
faces = grown

Ys, Xs = np.mgrid[0:chh * U4, 0:cw * U4]


def gradient(mask, stops=6):
    """Fit a linear gradient: direction from least squares, stops from the
    median luminance in bins along that direction."""
    xs, ys, ls = Xs[mask].astype(float), Ys[mask].astype(float), lum[mask]
    coef = np.linalg.lstsq(np.c_[np.ones_like(xs), xs, ys], ls, rcond=None)[0]
    d = np.array(coef[1:])
    d = d / np.linalg.norm(d) if np.linalg.norm(d) > 1e-6 else np.array([1.0, 0])
    t = xs * d[0] + ys * d[1]
    t0, t1 = np.percentile(t, 1), np.percentile(t, 99)
    vals = []
    for k in range(stops):
        lo, hi = t0 + (t1 - t0) * k / stops, t0 + (t1 - t0) * (k + 1) / stops
        sel = (t >= lo) & (t < hi)
        if sel.any():
            vals.append([round((k + .5) / stops, 2), int(np.median(ls[sel]))])
    cxm, cym = xs.mean(), ys.mean()
    tm = cxm * d[0] + cym * d[1]
    conv = lambda p: (round(p[0] / U4 + CX, 1), round(p[1] / U4 + CY, 1))
    return {'p0': conv((cxm + (t0 - tm) * d[0], cym + (t0 - tm) * d[1])),
            'p1': conv((cxm + (t1 - tm) * d[0], cym + (t1 - tm) * d[1])), 'stops': vals}


res['s_base'] = {'d': trace(S, U4, CX, CY, turd=4), 'tone': int(np.percentile(lum[S & edge & ~rim], 85))}
res['s_faces'] = []
for m, fit in zip(faces, faces_fit):
    m = cv2.morphologyEx(m.astype(np.uint8), cv2.MORPH_OPEN, np.ones((3, 3), np.uint8)) > 0
    res['s_faces'].append({'d': trace(m, U4, CX, CY, turd=6), 'grad': gradient(fit)})

json.dump(res, open(OUT, 'w'))
print({k: (len(v) if isinstance(v, list) else 1) for k, v in res.items()})
