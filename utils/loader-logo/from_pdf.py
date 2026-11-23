"""Build img/loader-logo.svg straight from the vector logo PDF.

    pip install pymupdf
    python from_pdf.py "Storefront Sign Wholesale Logo 1.pdf" ../../img/loader-logo.svg

Reads the PDF's page content (paths, CMYK fills and the four axial gradients
of the silver "S"), keeps every shape as vector, and writes the structured SVG
the page loader expects: #spl-tile, #spl-s, #spl-l1..#spl-l14, #spl-tag and
#spl-badge. CMYK is converted with MuPDF, the same engine that rendered the
PNG logo, so the colours match it.

Prints the piece boxes (viewBox units) for wholesale_page_loader_3d_logo() in
inc/page-loader.php. Paste them there whenever the logo changes.
"""
import json
import math
import re
import sys

import pymupdf

SRC = sys.argv[1]
OUT = sys.argv[2]
SCALE = 4  # viewBox units per PDF point
LETTERS = 'StorefrontSign'
TITLE = 'Storefront Sign Wholesale'

doc = pymupdf.open(SRC)
page = doc[0]
MB = page.mediabox  # PDF user space; y grows upwards


def cmyk_to_hex(c, m, y, k):
    pix = pymupdf.Pixmap(pymupdf.csCMYK, pymupdf.IRect(0, 0, 1, 1), False)
    pix.set_pixel(0, 0, tuple(int(round(v * 255)) for v in (c, m, y, k)))
    rgb = pymupdf.Pixmap(pymupdf.csRGB, pix)
    return '#%02x%02x%02x' % tuple(rgb.pixel(0, 0)[:3])


def mul(a, b):
    """PDF matrix product a x b (apply a first, then b)."""
    return [
        a[0] * b[0] + a[1] * b[2], a[0] * b[1] + a[1] * b[3],
        a[2] * b[0] + a[3] * b[2], a[2] * b[1] + a[3] * b[3],
        a[4] * b[0] + a[5] * b[2] + b[4], a[4] * b[1] + a[5] * b[3] + b[5],
    ]


def apply(m, x, y):
    return (m[0] * x + m[2] * y + m[4], m[1] * x + m[3] * y + m[5])


# PDF user space -> page space (top-left origin) -> viewBox units.
TO_UNITS = [SCALE, 0, 0, -SCALE, -MB.x0 * SCALE, MB.y1 * SCALE]

# --- Interpret the content stream -------------------------------------------
raw = b''.join(doc.xref_stream(x) for x in page.get_contents()).decode('latin1')
TOKEN = re.compile(r'/[^\s/\[\]()<>{}]+|-?\d*\.\d+|-?\d+|[A-Za-z*\'"]+|\[|\]')

items = []  # ('fill', subpaths, rule, colour) | ('shade', subpaths, rule, name, matrix)
ctm = [1, 0, 0, 1, 0, 0]
colour = '#000000'
clip = None
stack = []
path = []
pending_clip = None
args = []


def pt(x, y):
    return apply(mul(ctm, TO_UNITS), x, y)


for tok in TOKEN.findall(raw):
    if tok[0] in '-.0123456789':
        args.append(float(tok))
        continue
    if tok[0] == '/' or tok in '[]':
        args.append(tok)
        continue
    op = tok
    if op == 'q':
        stack.append((ctm[:], colour, clip))
    elif op == 'Q':
        ctm, colour, clip = stack.pop()
    elif op == 'cm':
        ctm = mul(args[-6:], ctm)
    elif op == 'k':
        colour = cmyk_to_hex(*args[-4:])
    elif op == 'rg':
        colour = '#%02x%02x%02x' % tuple(int(round(v * 255)) for v in args[-3:])
    elif op == 'g':
        colour = '#%02x%02x%02x' % ((int(round(args[-1] * 255)),) * 3)
    elif op == 'm':
        path.append([('M',) + pt(*args[-2:])])
    elif op == 'l':
        path[-1].append(('L',) + pt(*args[-2:]))
    elif op == 'c':
        a = args[-6:]
        path[-1].append(('C',) + pt(a[0], a[1]) + pt(a[2], a[3]) + pt(a[4], a[5]))
    elif op == 'v':
        a = args[-4:]
        cur = path[-1][-1][-2:]
        path[-1].append(('C',) + cur + pt(a[0], a[1]) + pt(a[2], a[3]))
    elif op == 'y':
        a = args[-4:]
        end = pt(a[2], a[3])
        path[-1].append(('C',) + pt(a[0], a[1]) + end + end)
    elif op == 're':
        x, y, w, h = args[-4:]
        path.append([('M',) + pt(x, y), ('L',) + pt(x + w, y), ('L',) + pt(x + w, y + h), ('L',) + pt(x, y + h), ('Z',)])
    elif op == 'h':
        if path and path[-1][-1][0] != 'Z':
            path[-1].append(('Z',))
    elif op in ('W', 'W*'):
        pending_clip = 'evenodd' if op == 'W*' else 'nonzero'
    elif op in ('f', 'F', 'f*', 'n'):
        if op != 'n' and path:
            items.append(('fill', path, 'evenodd' if op == 'f*' else 'nonzero', colour))
        if pending_clip and path:
            clip = (path, pending_clip)
        pending_clip = None
        path = []
    elif op == 'sh':
        items.append(('shade', clip[0], clip[1], args[-1][1:], mul(ctm, TO_UNITS)))
    elif op in ('S', 's', 'B', 'B*', 'b', 'b*'):
        raise SystemExit('Stroked paths are not supported: ' + op)
    args = []

# --- Gradients ---------------------------------------------------------------
shading_refs = {}
res = doc.xref_get_key(page.xref, 'Resources/Shading')[1]
for name, xref in re.findall(r'/(\w+)\s+(\d+) 0 R', res):
    shading_refs[name] = int(xref)


def nums(xref, key):
    kind, val = doc.xref_get_key(xref, key)
    return [float(v) for v in re.findall(r'-?\d*\.?\d+', val)] if kind != 'null' else []


def ref(xref, key):
    return int(doc.xref_get_key(xref, key)[1].split()[0])


def gradient_stops(fn):
    """Stops of a type 2 (or type 3 of type 2) function over [0, 1]."""
    ftype = int(nums(fn, 'FunctionType')[0])
    if ftype == 2:
        subs, bounds = [fn], []
    elif ftype == 3:
        subs = [int(x) for x in re.findall(r'(\d+) 0 R', doc.xref_get_key(fn, 'Functions')[1])]
        bounds = nums(fn, 'Bounds')
    else:
        raise SystemExit('Unsupported gradient function type %d' % ftype)
    edges = [0.0] + bounds + [1.0]
    stops = []
    for i, sub in enumerate(subs):
        c0, c1 = nums(sub, 'C0') or [0, 0, 0, 0], nums(sub, 'C1') or [0, 0, 0, 1]
        n = (nums(sub, 'N') or [1])[0]
        steps = 1 if n == 1 else 6
        for j in range(steps + 1):
            t = j / steps
            col = [a + (b - a) * (t ** n) for a, b in zip(c0, c1)]
            stops.append((edges[i] + (edges[i + 1] - edges[i]) * t, cmyk_to_hex(*col)))
    out = []
    for off, col in stops:  # drop repeats at shared bounds
        if not out or out[-1] != (off, col):
            out.append((off, col))
    return out


# --- Geometry helpers --------------------------------------------------------


def bounds(subpaths):
    xs, ys = [], []
    for sp in subpaths:
        cur = None
        for seg in sp:
            if seg[0] == 'M' or seg[0] == 'L':
                cur = seg[1:3]
                xs.append(cur[0]), ys.append(cur[1])
            elif seg[0] == 'C':
                p0, p1, p2, p3 = cur, seg[1:3], seg[3:5], seg[5:7]
                for k in range(1, 25):
                    t = k / 24
                    mt = 1 - t
                    xs.append(mt ** 3 * p0[0] + 3 * mt * mt * t * p1[0] + 3 * mt * t * t * p2[0] + t ** 3 * p3[0])
                    ys.append(mt ** 3 * p0[1] + 3 * mt * mt * t * p1[1] + 3 * mt * t * t * p2[1] + t ** 3 * p3[1])
                cur = p3
    return [min(xs), min(ys), max(xs), max(ys)]


def union(boxes):
    return [min(b[0] for b in boxes), min(b[1] for b in boxes), max(b[2] for b in boxes), max(b[3] for b in boxes)]


fills = [it for it in items if it[0] == 'fill']
shades = [it for it in items if it[0] == 'shade']
everything = union([bounds(it[1]) for it in items])
OX, OY = math.floor(everything[0] * 10) / 10, math.floor(everything[1] * 10) / 10
W, H = math.ceil(everything[2] - OX), math.ceil(everything[3] - OY)


def num(v):
    s = '%.1f' % v
    s = s.rstrip('0').rstrip('.') if '.' in s else s
    if s in ('-0', ''):
        s = '0'
    return s.replace('0.', '.', 1) if s.startswith('0.') else s.replace('-0.', '-.', 1)


def d_attr(subpaths):
    """Compact relative path data, coordinates rounded to 0.1 unit."""
    out = []
    cx = cy = 0.0
    for sp in subpaths:
        start = None
        for seg in sp:
            if seg[0] == 'Z':
                out.append('z')
                cx, cy = start
                continue
            pts = [(round((seg[i] - OX) * 10) / 10, round((seg[i + 1] - OY) * 10) / 10) for i in range(1, len(seg), 2)]
            if seg[0] == 'M':
                out.append('M' + num(pts[0][0]) + ' ' + num(pts[0][1]))
                cx, cy = pts[0]
                start = pts[0]
                continue
            rel = []
            for x, y in pts:
                rel += [x - cx, y - cy]
            cx, cy = pts[-1]
            body = ''
            for v in rel:
                s = num(v)
                body += s if not body or s.startswith('-') else ' ' + s
            out.append(('l' if seg[0] == 'L' else 'c') + body)
    return ''.join(out)


def box_of(boxes):
    b = union(boxes)
    return [round(b[0] - OX, 1), round(b[1] - OY, 1), round(b[2] - b[0], 1), round(b[3] - b[1], 1)]


# --- Classify the shapes -----------------------------------------------------
by_colour = {}
for it in fills:
    by_colour.setdefault(it[3], []).append(it)

# The tile is the shape at the far left; the badge reaches the far right and
# carries the white text; the most common remaining colour is the wordmark,
# and its widest, flattest shape is the tagline.
tile = next(it for it in fills if abs(bounds(it[1])[0] - everything[0]) < 1 and it[3] != '#ffffff')
badge = [it for it in fills if bounds(it[1])[2] > everything[2] - 1 and it[3] != '#ffffff']
badge_text = [it for it in fills if it[3] == '#ffffff']
letter_colour = max(set(it[3] for it in fills if it not in badge and it is not tile and it[3] != '#ffffff'),
                    key=lambda c: len(by_colour[c]))
word_items = by_colour[letter_colour]
tag = max(word_items, key=lambda it: (bounds(it[1])[2] - bounds(it[1])[0]) / max(1, bounds(it[1])[3] - bounds(it[1])[1]))
letter_items = [it for it in word_items if it is not tag]

# Group letter shapes (the "i" is a dot plus a stem) left to right.
letter_items.sort(key=lambda it: bounds(it[1])[0])
groups = []
for it in letter_items:
    b = bounds(it[1])
    if groups:
        g = groups[-1]
        gb = union([bounds(x[1]) for x in g])
        overlap = min(b[2], gb[2]) - max(b[0], gb[0])
        if overlap > 0.6 * min(b[2] - b[0], gb[2] - gb[0]):
            g.append(it)
            continue
    groups.append([it])
if len(groups) != len(LETTERS):
    raise SystemExit('Expected %d letters, found %d' % (len(LETTERS), len(groups)))

# --- Write the SVG -----------------------------------------------------------
out = []
out.append('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" role="img" aria-labelledby="spl-logo-t" class="spl-logo">' % (W, H))
out.append('<title id="spl-logo-t">%s</title>' % TITLE)
out.append('<defs>')
for i, (kind, sub, rule, name, matrix) in enumerate(shades, 1):
    xref = shading_refs[name]
    if int(nums(xref, 'ShadingType')[0]) != 2:
        raise SystemExit('Only axial gradients are supported')
    x0, y0, x1, y1 = nums(xref, 'Coords')
    m = list(matrix)
    m[4] -= OX
    m[5] -= OY
    stops = ''.join('<stop offset="%s" stop-color="%s"/>' % (num(o * 100 / 100) if o not in (0, 1) else int(o), c)
                    for o, c in gradient_stops(ref(xref, 'Function')))
    out.append('<linearGradient id="spl-g%d" gradientUnits="userSpaceOnUse" x1="%s" y1="%s" x2="%s" y2="%s" gradientTransform="matrix(%s)">%s</linearGradient>'
               % (i, x0, y0, x1, y1, ' '.join('%.4f' % v for v in m), stops))
out.append('</defs>')
out.append('<path id="spl-tile" fill="%s" fill-rule="%s" d="%s"/>' % (tile[3], tile[2], d_attr(tile[1])))
out.append('<g id="spl-s">')
for i, (kind, sub, rule, name, matrix) in enumerate(shades, 1):
    out.append('<path fill="url(#spl-g%d)" fill-rule="%s" d="%s"/>' % (i, rule, d_attr(sub)))
out.append('</g>')
out.append('<g class="spl-word" fill="%s">' % letter_colour)
for i, g in enumerate(groups, 1):
    sub = [sp for it in g for sp in it[1]]
    out.append('<path id="spl-l%d" fill-rule="%s" d="%s"/>' % (i, g[0][2], d_attr(sub)))
out.append('</g>')
out.append('<g id="spl-tag" fill="%s"><path fill-rule="%s" d="%s"/></g>' % (tag[3], tag[2], d_attr(tag[1])))
out.append('<g id="spl-badge">')
for it in badge + badge_text:
    out.append('<path fill="%s" fill-rule="%s" d="%s"/>' % ('#fff' if it[3] == '#ffffff' else it[3], it[2], d_attr(it[1])))
out.append('</g>')
out.append('</svg>')
open(OUT, 'w', encoding='utf-8', newline='\n').write('\n'.join(out) + '\n')

# --- Piece boxes for inc/page-loader.php -------------------------------------
badge_box = box_of([bounds(it[1]) for it in badge + badge_text])
# Pole foot: the badge's lowest points; the badge swings round it.
low = []
for it in badge:
    for sp in it[1]:
        for seg in sp:
            if seg[0] in 'ML':
                low.append(seg[1:3])
bottom = max(p[1] for p in low)
foot_x = sum(p[0] for p in low if p[1] > bottom - 1) / max(1, len([p for p in low if p[1] > bottom - 1]))
print(json.dumps({
    'viewBox': [W, H],
    'colours': {'tile': tile[3], 'letters': letter_colour, 'tag': tag[3], 'badge': badge[0][3]},
    'mark': box_of([bounds(tile[1])] + [bounds(s[1]) for s in shades]),
    'letters': [box_of([bounds(it[1]) for it in g]) for g in groups],
    'tag': box_of([bounds(tag[1])]),
    'badge': badge_box,
    'badge_origin_x_percent': round((foot_x - OX - badge_box[0]) / badge_box[2] * 100, 1),
}, indent=1))
