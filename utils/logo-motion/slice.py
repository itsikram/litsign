"""
Build the header logo files and measure them for js/logo-motion.js.

    python slice.py <logo.png|webp> <theme img/brand folder> [debug.png]
    python slice.py <dark logo.png> <img/brand> --prefix=sfs-logo-dark --widths=320,480,640,960

Full pipeline from the owner's flat artwork (2026-11-20):
    tagline.py <flat> t.png 1.2 -> realistic.py t.png r.png 10 -> dark.py r.png d.png
    -> slice.py r.png img/brand -> slice.py d.png img/brand --prefix=... -> php avif.php img/brand

1. Trims the empty transparent border (4px kept), makes the nearly solid
   pixels fully solid (alpha >= 240 -> 255; the animation lays copies over the
   picture, and see-through pixels would darken where they overlap), and saves
   the master PNG plus WebP copies at WIDTHS. AVIF copies are made by
   avif.php next to this file (PHP GD), run it afterwards.
2. Measures, in the trimmed picture's pixels: the mark (red tile), the sign
   ("online .com" plate and pole), the row between the letters and the
   tagline, and the cut line between each pair of neighbouring letters (the
   cheapest path through empty or dark pixels near each gap).
3. Prints the GEO object to paste into js/logo-motion.js, and with a third
   argument saves a picture with the cuts drawn on it, to check by eye.

Needs Pillow and numpy. Run again whenever the logo picture changes.
"""
import json
import os
import sys

import numpy as np
from PIL import Image, ImageDraw

#   --prefix=sfs-logo-dark --widths=320,480,640,960   makes the dark-background
#   copy (footer, page loader): only those widths, no master PNG.
OPTS = dict(a[2:].split('=', 1) for a in sys.argv[1:] if a.startswith('--') and '=' in a)
ARGS = [a for a in sys.argv[1:] if not a.startswith('--')]
SRC, OUT_DIR = ARGS[0], ARGS[1]
DEBUG = ARGS[2] if len(ARGS) > 2 else None
PREFIX = OPTS.get('prefix', 'sfs-logo')
WIDTHS = [int(w) for w in OPTS['widths'].split(',')] if 'widths' in OPTS else [320, 480, 640, 960, 1280]
LETTERS = 14         # S t o r e f r o n t S i g n
WINDOW = 34          # a cut line is searched this far either side of a gap
MAX_STEP = 3         # how far a cut line may move sideways per pixel row
PAD = 4


def sharpen(image):
    """Light unsharp mask for the scaled-down copies (downscaling softens the
    letter edges). Done on premultiplied colour, so transparent pixels never
    bleed a dark fringe into the edges."""
    from PIL import ImageFilter
    a = np.asarray(image).astype(np.float64)
    weight = a[..., 3:4] / 255.0
    pre = np.dstack([a[..., :3] * weight, a[..., 3:4]]).round().clip(0, 255).astype(np.uint8)
    mask = ImageFilter.UnsharpMask(radius=0.6, percent=70, threshold=1)
    rgb = np.asarray(Image.fromarray(pre[..., :3], 'RGB').filter(mask)).astype(np.float64)
    alp = np.asarray(Image.fromarray(pre[..., 3], 'L').filter(mask)).astype(np.float64)
    out = np.where(alp[..., None] > 0, rgb / np.maximum(alp[..., None], 1) * 255, 0)
    return Image.fromarray(np.dstack([out.clip(0, 255), alp]).round().astype(np.uint8), 'RGBA')


# ---- 1. Files ---------------------------------------------------------------
im = Image.open(SRC).convert('RGBA')
alpha = np.asarray(im)[..., 3]
ys, xs = np.where(alpha >= 12)
box = (max(0, xs.min() - PAD), max(0, ys.min() - PAD), min(im.width, xs.max() + 1 + PAD), min(im.height, ys.max() + 1 + PAD))
im = im.crop(box)
px = np.asarray(im).copy()
px[..., 3] = np.where(px[..., 3] >= 240, 255, px[..., 3])
im = Image.fromarray(px, 'RGBA')
W, H = im.size
os.makedirs(OUT_DIR, exist_ok=True)
if 'prefix' not in OPTS:
    im.save(os.path.join(OUT_DIR, '%s-%d.png' % (PREFIX, W)), optimize=True)
for width in (WIDTHS if 'widths' in OPTS else WIDTHS + [W]):
    copy = im if width == W else sharpen(im.resize((width, round(H * width / W)), Image.LANCZOS))
    copy.save(os.path.join(OUT_DIR, '%s-%d.webp' % (PREFIX, width)), quality=90, method=6)

# ---- 2. Measurements --------------------------------------------------------
px = px.astype(np.int32)
R, G, B, A = px[..., 0], px[..., 1], px[..., 2], px[..., 3]
luma = (R * 299 + G * 587 + B * 114) // 1000
solid = A > 40


def runs(flags):
    out, start = [], None
    for i, flag in enumerate(flags):
        if flag and start is None:
            start = i
        if not flag and start is not None:
            out.append((start, i - 1))
            start = None
    if start is not None:
        out.append((start, len(flags) - 1))
    return out


# Rows: the letters' band ends at the first empty row (under the letters and
# the tile) above the tagline.
row_ink_all = solid[:, W // 6:W * 5 // 6].sum(axis=1)
first_row = int(np.where(row_ink_all > 0)[0].min())
gap_rows = [r for r in runs(row_ink_all == 0) if r[0] > first_row + 40]
base = int((gap_rows[0][0] + gap_rows[0][1]) // 2)

# Mark: the red tile, the first solid block from the left above that row.
col_ink = solid[:base, :].sum(axis=0)
first_col = int(np.where(col_ink > 0)[0].min())
tile_gap = [r for r in runs(col_ink == 0) if r[0] > first_col + 40][0]
mark_right = int(tile_gap[0] - 1)
word_left = int((tile_gap[0] + tile_gap[1]) // 2)
tile_rows = np.where(solid[:base, first_col:mark_right + 1].sum(axis=1) > 0)[0]
mark = [first_col, int(tile_rows.min()), mark_right, int(tile_rows.max())]

# Sign: the blue plate and pole, at the right end.
blue = solid & (B > 110) & (R < 80)
sign_cols = np.where(blue.sum(axis=0) > 0)[0]
sign_rows = np.where(blue.sum(axis=1) > 0)[0]
sign = [int(sign_cols.min()), int(sign_rows.min()), int(sign_cols.max()), int(sign_rows.max())]
pole_cols = np.where(blue[int(sign_rows.max()) - 30:int(sign_rows.max()), :].sum(axis=0) > 0)[0]
pole_x = float(pole_cols.min() + pole_cols.max()) / 2

# Letters: between the tile and the sign.
letter_cols = slice(word_left, sign[0])
row_ink = solid[:base, letter_cols].sum(axis=1)
letters_top = int(np.where(row_ink > 0)[0].min())
letters_bottom = int(np.where(row_ink > 0)[0].max())
sign_left = int(sign[0]) - 1
for x in range(sign[0] - 1, sign[0] - 30, -1):
    if not solid[letters_top:base, x].any():
        sign_left = x
        break

# Letter faces: the bright fronts (the darker undersides are the extrusion).
# Each face is its own connected shape; neighbours can overlap in x (the arm
# of an r over the next e), so they are found by flood fill, not by columns.
face = solid & (R > 190) & (G > 60) & (G < 150) & (B < 70)
face[:, :word_left] = False
face[:, sign_left:] = False
face[base:, :] = False


def erode(mask, times):
    for _ in range(times):
        inner = mask.copy()
        inner[1:, :] &= mask[:-1, :]
        inner[:-1, :] &= mask[1:, :]
        inner[:, 1:] &= mask[:, :-1]
        inner[:, :-1] &= mask[:, 1:]
        mask = inner
    return mask


def find_letters(mask):
    labels = np.zeros(mask.shape, dtype=np.int32)
    shapes = []
    for y0, x0 in zip(*np.where(mask)):
        if labels[y0, x0]:
            continue
        label = len(shapes) + 1
        stack = [(y0, x0)]
        labels[y0, x0] = label
        xs_ = []
        while stack:
            y, x = stack.pop()
            xs_.append(x)
            for ny, nx in ((y - 1, x), (y + 1, x), (y, x - 1), (y, x + 1)):
                if 0 <= ny < H and 0 <= nx < W and mask[ny, nx] and not labels[ny, nx]:
                    labels[ny, nx] = label
                    stack.append((ny, nx))
        shapes.append({'labels': [label], 'x0': min(xs_), 'x1': max(xs_), 'area': len(xs_), 'cx': sum(xs_) / len(xs_)})
    shapes = sorted((s for s in shapes if s['area'] > 120), key=lambda s: s['cx'])
    found = []
    for shape in shapes:
        # A dot over a stem (the i) joins the letter it sits over.
        if found and found[-1]['x0'] <= shape['cx'] <= found[-1]['x1'] and shape['area'] < found[-1]['area'] / 3:
            found[-1]['labels'] += shape['labels']
            continue
        found.append(shape)
    return labels, found


# Neighbouring faces may touch along a thin line: shrink them until they part.
for shrink in range(0, 12):
    labels, letters = find_letters(erode(face, shrink))
    if len(letters) == LETTERS:
        break
assert len(letters) == LETTERS, 'found %d letters, expected %d' % (len(letters), LETTERS)


def seam(left, right, y0, y1):
    """Cheapest top-to-bottom path between two letters: free through empty
    pixels, dear through the extrusion (follows its darkest groove), and
    never through either letter's face."""
    x0 = int(max(left['x0'], left['x1'] - 3 * WINDOW))
    x1 = int(min(right['x1'], right['x0'] + 3 * WINDOW))
    middle = (left['x1'] + right['x0']) / 2.0 - x0
    region = labels[y0:y1, x0:x1]
    cost = np.where(A[y0:y1, x0:x1] < 40, 0, 40 + luma[y0:y1, x0:x1]).astype(np.float64)
    cost[np.isin(region, left['labels'] + right['labels'])] = 5000
    cost += np.abs(np.arange(x1 - x0) - middle)[None, :] * 0.35
    # The path starts left of the right letter and right of the left one.
    cost[:, :max(0, int(left['x0'] - x0) + 1)] = 1e7
    cost[:, min(x1 - x0, int(right['x1'] - x0)):] = 1e7
    n, w = cost.shape
    total = cost.copy()
    back = np.zeros((n, w), dtype=np.int32)
    for y in range(1, n):
        best = np.full(w, np.inf)
        arg = np.zeros(w, dtype=np.int32)
        for step in range(-MAX_STEP, MAX_STEP + 1):
            shifted = np.full(w, np.inf)
            if step < 0:
                shifted[:step] = total[y - 1, -step:]
            elif step > 0:
                shifted[step:] = total[y - 1, :-step]
            else:
                shifted = total[y - 1].copy()
            shifted = shifted + abs(step) * 6
            better = shifted < best
            best[better] = shifted[better]
            arg[better] = step
        total[y] += best
        back[y] = arg
    x = int(np.argmin(total[-1]))
    path = [x]
    for y in range(n - 1, 0, -1):
        x -= back[y, x]
        path.append(x)
    path.reverse()
    return [(int(x0 + x), int(y0 + i)) for i, x in enumerate(path)]


def simplify(points, tolerance):
    """Douglas-Peucker: keep only the corners that matter."""
    if len(points) < 3:
        return points
    (x0, y0), (x1, y1) = points[0], points[-1]
    dx, dy = x1 - x0, y1 - y0
    length = (dx * dx + dy * dy) ** 0.5 or 1.0
    worst, index = 0.0, 0
    for i in range(1, len(points) - 1):
        x, y = points[i]
        d = abs(dy * (x - x0) - dx * (y - y0)) / length
        if d > worst:
            worst, index = d, i
    if worst <= tolerance:
        return [points[0], points[-1]]
    return simplify(points[:index + 1], tolerance)[:-1] + simplify(points[index:], tolerance)


top = max(0, letters_top - 2)
seams = [[list(p) for p in simplify(seam(letters[k], letters[k + 1], top, base), 1.4)] for k in range(LETTERS - 1)]
guesses = [[int(s['x0']), int(s['x1'])] for s in letters]

geo = {
    'W': W, 'H': H, 'TOP': top, 'BASE': base, 'WORD_LEFT': word_left, 'SIGN_LEFT': sign_left,
    'MARK': mark, 'SIGN': sign, 'POLE_X': pole_x, 'SEAMS': seams,
    'check': {'lettersBottom': letters_bottom, 'guesses': guesses, 'crop': box},
}
print(json.dumps(geo, default=int))

if DEBUG:
    view = Image.new('RGBA', im.size, (40, 70, 110, 255))
    view.alpha_composite(im)
    draw = ImageDraw.Draw(view)
    for line in seams:
        draw.line([tuple(p) for p in line], fill=(0, 255, 120, 255), width=2)
    draw.line([(0, base), (W, base)], fill=(255, 255, 0, 255), width=1)
    draw.rectangle(mark, outline=(255, 255, 255, 255), width=2)
    draw.rectangle(sign, outline=(255, 0, 255, 255), width=2)
    draw.line([(word_left, 0), (word_left, base)], fill=(255, 255, 255, 255), width=1)
    draw.line([(sign_left, 0), (sign_left, base)], fill=(255, 0, 255, 255), width=1)
    draw.line([(pole_x, sign[1]), (pole_x, sign[3])], fill=(255, 128, 0, 255), width=1)
    view.convert('RGB').save(DEBUG)
