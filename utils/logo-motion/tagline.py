"""
Re-lay the tagline of the owner's flat logo artwork (2000 x 667).

    python tagline.py <flat artwork> <out.png> [scale]

The tagline glyphs ("CHANNEL LETTERS & LARGE FORMAT PRINTS SINCE 2002") are
cut out of the picture one by one (same letters, same font), optionally
enlarged by <scale>, and spread so the line runs exactly from the left edge
of the red tile to the right edge of the blue sign (owner, 2026-11-20).
Word gaps get twice the extra spacing of letter gaps, like CSS
letter-spacing. Needs Pillow and numpy.
"""
import sys

import numpy as np
from PIL import Image

SRC, OUT = sys.argv[1], sys.argv[2]
SCALE = float(sys.argv[3]) if len(sys.argv) > 3 else 1.0
T0, T1 = 455, 515          # rows holding the tagline (letters end at 451)

a = np.asarray(Image.open(SRC).convert('RGBA')).copy()
A = a[..., 3]
band = a[T0:T1].copy()
cols = (band[..., 3] >= 8).sum(axis=0)
runs, start = [], None
for i, c in enumerate(cols):
    if c and start is None:
        start = i
    if not c and start is not None:
        runs.append([start, i - 1])
        start = None
glyphs = [runs[0]]
for r in runs[1:]:
    if r[0] - glyphs[-1][1] - 1 <= 1:   # a faint 1px column inside a letter
        glyphs[-1][1] = r[1]
    else:
        glyphs.append(r)
gaps = [glyphs[i + 1][0] - glyphs[i][1] - 1 for i in range(len(glyphs) - 1)]
word = [g >= 18 for g in gaps]
assert len(glyphs) == 41, 'expected 41 tagline glyphs, found %d' % len(glyphs)

rows = np.where((band[..., 3] >= 8).any(axis=1))[0]
g_top, g_bot = rows.min(), rows.max() + 1
ys, xs = np.where(A[:T0] >= 12)
left, right = xs.min(), xs.max()

pieces = []
for x0, x1 in glyphs:
    piece = Image.fromarray(band[g_top:g_bot, x0:x1 + 1], 'RGBA')
    if SCALE != 1.0:
        piece = piece.resize((max(1, round(piece.width * SCALE)), round(piece.height * SCALE)), Image.LANCZOS)
    pieces.append(piece)
ink = sum(p.width for p in pieces)
units = len(gaps) + sum(word)
extra = ((right - left + 1) - ink - sum(g * SCALE for g in gaps)) / units
if extra >= 0:
    # Room to spare: add letter-spacing (word gaps count twice).
    new_gaps = [g * SCALE + extra * (2 if w else 1) for g, w in zip(gaps, word)]
else:
    # Bigger letters than fit at their own spacing: tighten every gap by the
    # same factor, so kerned pairs (A-T) keep their proportions.
    fit = ((right - left + 1) - ink) / (sum(gaps) * SCALE)
    assert fit >= 0.7, 'tagline too large to fit (gaps x%.2f)' % fit
    new_gaps = [g * SCALE * fit for g in gaps]

out = Image.fromarray(a, 'RGBA')
clear = np.asarray(out).copy()
clear[T0:T1 + 40] = 0
out = Image.fromarray(clear, 'RGBA')
top = T0 + g_top
pos = float(left)
for i, piece in enumerate(pieces):
    if i:
        pos += new_gaps[i - 1]
    out.alpha_composite(piece, (int(round(pos)), int(top)))
    pos += piece.width
out.save(OUT, optimize=True)
print('glyphs', len(pieces), 'scale', SCALE, 'gaps %.1f-%.1f' % (min(new_gaps), max(new_gaps)), 'ends at', int(round(pos)) - 1, 'right edge', right)
