"""Build the silver S as vector faces.

Face geometry was read off the clean 512px site icon (same mark) and is
mapped onto the logo with the affine fitted in s_faces.py (silhouette IoU
0.99). Every face is clipped by the logo's own traced S outline, so the
outline stays exact. Each face gets a linear gradient fitted to the logo's
pixels. Writes s_vector.json for assemble.py.
"""
import json

import cv2
import numpy as np
from PIL import Image

LOGO = r'C:\xampp\htdocs\storefrontsignonline.com\wp-content\uploads\2026\09\Store-Front-Sign-Online.png'
ICON = r'C:\xampp\htdocs\storefrontsignonline.com\wp-content\uploads\2026\10\cropped-cropped-cropped-StorefrontSignWholesale-Icon-1.png'
U4, CX, CY = 4, 40, 120
UI = 2

# ---- affine icon -> logo (same fit as s_faces.py) ----------------------------
icon = Image.open(ICON).convert('RGBA')
ii = np.array(icon.resize((512 * UI, 512 * UI), Image.LANCZOS)).astype(np.float32)
iS = (ii[..., 3] > 127) & ((ii[..., :3].max(2) - ii[..., :3].min(2)) < 40)
iS = cv2.morphologyEx(iS.astype(np.uint8), cv2.MORPH_OPEN, np.ones((3, 3), np.uint8)) > 0
crop = Image.open(LOGO).convert('RGBA').crop((CX, CY, 230, 380))
cw, ch = crop.size
li = np.array(crop.resize((cw * U4, ch * U4), Image.LANCZOS)).astype(np.float32)
lS = (li[..., 3] > 127) & ((li[..., :3].max(2) - li[..., :3].min(2)) < 45)
lS = cv2.morphologyEx(lS.astype(np.uint8), cv2.MORPH_OPEN, np.ones((5, 5), np.uint8)) > 0
lum = (li[..., :3] @ np.array([.299, .587, .114], np.float32)).astype(np.float32)


def bbox(m):
    ys, xs = np.nonzero(m)
    return xs.min(), xs.max(), ys.min(), ys.max()


ix0, ix1, iy0, iy1 = bbox(iS)
lx0, lx1, ly0, ly1 = bbox(lS)
sx, sy = (lx1 - lx0) / (ix1 - ix0), (ly1 - ly0) / (iy1 - iy0)
warp = np.array([[sx, 0, lx0 - ix0 * sx], [0, sy, ly0 - iy0 * sy]], np.float32)
inv = cv2.invertAffineTransform(warp)
_, inv = cv2.findTransformECC(cv2.GaussianBlur(lS.astype(np.float32), (0, 0), 3),
                              cv2.GaussianBlur(iS.astype(np.float32), (0, 0), 3),
                              inv.astype(np.float32), cv2.MOTION_AFFINE,
                              (cv2.TERM_CRITERIA_EPS | cv2.TERM_CRITERIA_COUNT, 200, 1e-6), None, 5)
fwd = cv2.invertAffineTransform(inv)  # icon2x -> logo4x


def to_logo(p):
    """Icon pixel coords -> logo user units."""
    x, y = p[0] * UI, p[1] * UI
    X = fwd[0, 0] * x + fwd[0, 1] * y + fwd[0, 2]
    Y = fwd[1, 0] * x + fwd[1, 1] * y + fwd[1, 2]
    return (X / U4 + CX, Y / U4 + CY)


def to_px(p):
    """Logo user units -> 4x crop pixels."""
    return ((p[0] - CX) * U4, (p[1] - CY) * U4)


# ---- face geometry in icon coordinates --------------------------------------
# Segments: ('L', [pts]) straight polyline, ('C', [pts]) smooth curve through pts.
ARC = [(300, 236), (299, 225), (296, 210), (290, 192), (280, 172), (268, 155), (255, 140), (240, 128), (220, 118)]
NOTCH_LOW = [(218, 232), (195, 213), (180, 203), (172, 190), (170, 176)]
FACES = {
    # upper bowl: top + curved extruded side
    'bowl': [('L', [(130, 73), (203, 0), (430, 0), (430, 181.5)]), ('C', ARC), ('L', [(130, 73)])],
    # top of the middle stroke + upper notch + box top (dark -> light)
    'mid': [('L', [(170, 176), (300, 236), (430, 181.5), (430, 215.5), (300, 270)]), ('C', NOTCH_LOW)],
    # right side of the box
    'side': [('L', [(300, 270), (430, 215.5), (430, 480), (300, 480)])],
    # lower notch surface
    'low': [('L', [(150, 280), (165, 289), (244, 327)]), ('C', [(244, 327), (250, 340), (249, 351), (236, 357)]), ('L', [(130, 305), (110, 300)])],
}
BEVELS = [
    ('C', ARC),
    ('L', [(218, 232), (300, 270), (376, 238)]),
    ('L', [(300, 270), (300, 463)]),
]


def fmt(v):
    s = ('%.1f' % v).rstrip('0').rstrip('.')
    if s.startswith('0.'):
        s = s[1:]
    elif s.startswith('-0.'):
        s = '-' + s[2:]
    return s if s not in ('', '-0') else '0'


def pair(p):
    return fmt(p[0]) + ('' if fmt(p[1]).startswith('-') else ',') + fmt(p[1])


def catmull(pts):
    """Catmull-Rom through pts -> list of cubic segments (c1, c2, end)."""
    segs = []
    for i in range(len(pts) - 1):
        p0 = pts[i - 1] if i else pts[i]
        p1, p2 = pts[i], pts[i + 1]
        p3 = pts[i + 2] if i + 2 < len(pts) else p2
        c1 = (p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6)
        c2 = (p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6)
        segs.append((c1, c2, p2))
    return segs


def build(segs, close=True):
    """Return (svg path d in logo units, polyline in logo px for rasterising)."""
    d, poly, started = '', [], False
    for kind, pts in segs:
        L = [to_logo(p) for p in pts]
        if not started:
            d += 'M' + pair(L[0])
            poly.append(L[0])
            started = True
            rest = L[1:] if kind == 'L' else L
        else:
            rest = L
        if kind == 'L':
            for p in rest:
                d += 'L' + pair(p)
                poly.append(p)
        else:
            if poly and np.hypot(poly[-1][0] - L[0][0], poly[-1][1] - L[0][1]) > .05:
                d += 'L' + pair(L[0])
                poly.append(L[0])
            for c1, c2, e in catmull(L):
                d += 'C' + pair(c1) + ' ' + pair(c2) + ' ' + pair(e)
                for t in np.linspace(0.1, 1, 10):
                    a = poly[-1] if False else None
                prev = poly[-1]
                for t in np.linspace(0.1, 1, 10):
                    mt = 1 - t
                    poly.append((mt ** 3 * prev[0] + 3 * mt * mt * t * c1[0] + 3 * mt * t * t * c2[0] + t ** 3 * e[0],
                                 mt ** 3 * prev[1] + 3 * mt * mt * t * c1[1] + 3 * mt * t * t * c2[1] + t ** 3 * e[1]))
    if close:
        d += 'Z'
    return d, poly


masks, out = {}, {'faces': [], 'bevels': ''}
for name, segs in FACES.items():
    d, poly = build(segs)
    m = np.zeros(lS.shape, np.uint8)
    cv2.fillPoly(m, [np.round(np.array([to_px(p) for p in poly]) * 4).astype(np.int32)], 1, cv2.LINE_8, 2)
    masks[name] = (m > 0) & lS
    out['faces'].append({'name': name, 'd': d})
front = lS.copy()
for m in masks.values():
    front &= ~m
masks = {'front': front, **masks}

bev = ''
bev_mask = np.zeros(lS.shape, np.uint8)
for kind, pts in BEVELS:
    d, poly = build([(kind, pts)], close=False)
    bev += d
    cv2.polylines(bev_mask, [np.round(np.array([to_px(p) for p in poly]) * 4).astype(np.int32)], False, 1, 14, cv2.LINE_8, 2)
out['bevels'] = bev
bev_mask = bev_mask > 0
bev_px = lS & bev_mask
out['bevel_tone'] = int(np.percentile(lum[bev_px], 80))

Ys, Xs = np.mgrid[0:lS.shape[0], 0:lS.shape[1]]


def gradient(mask, stops=7):
    mask = mask & ~bev_mask
    mask = cv2.erode(mask.astype(np.uint8), np.ones((3, 3), np.uint8)) > 0
    xs, ys, ls = Xs[mask].astype(float), Ys[mask].astype(float), lum[mask]
    coef = np.linalg.lstsq(np.c_[np.ones_like(xs), xs, ys], ls, rcond=None)[0]
    dvec = np.array(coef[1:])
    dvec = dvec / np.linalg.norm(dvec)
    t = xs * dvec[0] + ys * dvec[1]
    t0, t1 = np.percentile(t, 0.5), np.percentile(t, 99.5)
    vals = []
    for k in range(stops):
        lo, hi = t0 + (t1 - t0) * k / stops, t0 + (t1 - t0) * (k + 1) / stops
        sel = (t >= lo) & (t < hi)
        if sel.sum() > 20:
            vals.append([round((k + .5) / stops, 2), int(round(np.median(ls[sel])))])
    cxm, cym = xs.mean(), ys.mean()
    tm = cxm * dvec[0] + cym * dvec[1]
    conv = lambda p: (round(p[0] / U4 + CX, 1), round(p[1] / U4 + CY, 1))
    return {'p0': conv((cxm + (t0 - tm) * dvec[0], cym + (t0 - tm) * dvec[1])),
            'p1': conv((cxm + (t1 - tm) * dvec[0], cym + (t1 - tm) * dvec[1])), 'stops': vals}


out['grads'] = {k: gradient(m) for k, m in masks.items()}
json.dump(out, open('s_vector.json', 'w'))
for k, g in out['grads'].items():
    print(k, g)
print('bevel', out['bevel_tone'])
