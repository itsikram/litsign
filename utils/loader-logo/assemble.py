"""Assemble traced paths into the structured loader-logo.svg."""
import json
import sys

P = json.load(open(sys.argv[1]))
OUT = sys.argv[2]

ORANGE = '#f64e09'
TILE = '#e81318'
TAG = '#d6020f'
BLUE = '#0292cb'
WHITE = '#f9fbfc'

# Tagline glyphs grouped into words: CHANNEL LETTERS & LARGE FORMAT PRINTS SINCE 2002
TAG_WORDS = [7, 7, 1, 5, 6, 6, 5, 4]
WORD_LETTERS = 'StorefrontSign'


def hexgray(t):
    h = '%02x' % max(0, min(255, t))
    return '#' + (h[0] * 3 if h[0] == h[1] else h * 3)


def num(v):
    s = ('%.1f' % v).rstrip('0').rstrip('.')
    return s


out = []
out.append('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2172 408" role="img" aria-labelledby="spl-logo-t" class="spl-logo" stroke-width="0">')
out.append('<title id="spl-logo-t">Storefront Sign Online</title>')

# Silver S: faces + gradients fitted to the original pixels (s_vector.py).
V = json.load(open(sys.argv[3]))
defs = ['<path id="spl-sp" pathLength="1" d="%s"/>' % P['s_base']['d'], '<clipPath id="spl-sc"><use href="#spl-sp"/></clipPath>']
for k, g in V['grads'].items():
    stops = ''.join('<stop offset="%s" stop-color="%s"/>' % (num(o), hexgray(t)) for o, t in g['stops'])
    defs.append('<linearGradient id="spl-%s" gradientUnits="userSpaceOnUse" x1="%s" y1="%s" x2="%s" y2="%s">%s</linearGradient>'
                % (k, num(g['p0'][0]), num(g['p0'][1]), num(g['p1'][0]), num(g['p1'][1]), stops))
out.append('<defs>' + ''.join(defs) + '</defs>')

# Mark: red tile, silver S (front face = outline, other faces clipped to it).
out.append('<g class="spl-mark">')
out.append('<path pathLength="1" class="spl-draw" fill="%s" stroke="%s" d="%s"/>' % (TILE, TILE, P['tile']))
out.append('<g class="spl-s">')
out.append('<use class="spl-draw" href="#spl-sp" fill="url(#spl-front)" stroke="#d0d0d0"/>')
out.append('<g clip-path="url(#spl-sc)">')
for f in V['faces']:
    out.append('<path fill="url(#spl-%s)" d="%s"/>' % (f['name'], f['d']))
out.append('<path fill="none" stroke="%s" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="%s"/>'
           % (hexgray(V['bevel_tone']), V['bevels']))
out.append('</g></g></g>')

# Wordmark: one path per letter.
out.append('<g class="spl-word" fill="%s" stroke="%s">' % (ORANGE, ORANGE))
for ch, d in zip(WORD_LETTERS, P['word']):
    out.append('<path pathLength="1" class="spl-draw spl-l" d="%s"/>' % d)
out.append('</g>')

# Tagline: one path per word.
out.append('<g class="spl-tag" fill="%s" stroke="%s">' % (TAG, TAG))
k = 0
for n in TAG_WORDS:
    out.append('<path pathLength="1" class="spl-draw" d="%s"/>' % ''.join(P['tag'][k:k + n]))
    k += n
out.append('</g>')

# "online .com" badge.
out.append('<g class="spl-badge">')
out.append('<path pathLength="1" class="spl-draw" fill="%s" stroke="%s" d="%s"/>' % (BLUE, BLUE, P['bubble']))
out.append('<path pathLength="1" class="spl-draw" fill="%s" stroke="%s" d="%s"/>' % (WHITE, WHITE, ''.join(P['online'])))
out.append('</g>')
out.append('</svg>')

svg = '\n'.join(out) + '\n'
open(OUT, 'w', newline='\n').write(svg)
print(len(svg.encode()), 'bytes')
