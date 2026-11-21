"""
Copy the measurements printed by slice.py into the theme code that uses them.

    python slice.py <logo.png> <img/brand> > geo.json
    python apply-geo.py geo.json

Updates js/logo-motion.js (header animation), inc/page-loader.php (page
loader 3D pieces and its aspect-ratio) and inc/logo-motion.php (picture
height), so all three always match the img/brand pictures.
"""
import json
import os
import re
import sys

geo = json.load(open(sys.argv[1]))
theme = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))


def edit(rel, pairs):
    path = os.path.join(theme, rel)
    text = open(path, encoding='utf-8').read()
    for pattern, repl in pairs:
        text, n = re.subn(pattern, repl, text, count=1, flags=re.S)
        assert n == 1, '%s: no match for %s' % (rel, pattern)
    open(path, 'w', encoding='utf-8', newline='').write(text)
    print('updated', rel)


num = lambda v: ('%.1f' % v) if isinstance(v, float) else str(v)
js_seams = 'var SEAMS = [\n' + ',\n'.join(
    '\t\t[' + ', '.join('[%d,%d]' % tuple(p) for p in line) + ']' for line in geo['SEAMS']) + '\n\t];'
edit('js/logo-motion.js', [
    (r'var W = \d+;', 'var W = %d;' % geo['W']),
    (r'var H = \d+;', 'var H = %d;' % geo['H']),
    (r'var TOP = \d+;', 'var TOP = %d;' % geo['TOP']),
    (r'var BASE = \d+;', 'var BASE = %d;' % geo['BASE']),
    (r'var WORD_LEFT = \d+;', 'var WORD_LEFT = %d;' % geo['WORD_LEFT']),
    (r'var SIGN_LEFT = \d+;', 'var SIGN_LEFT = %d;' % geo['SIGN_LEFT']),
    (r'var MARK = \[[^\]]*\];', 'var MARK = [%s];' % ','.join(map(str, geo['MARK']))),
    (r'var SIGN = \[[^\]]*\];', 'var SIGN = [%s];' % ','.join(map(str, geo['SIGN']))),
    (r'var POLE_X = [\d.]+;', 'var POLE_X = %s;' % num(float(geo['POLE_X']))),
    (r'var SEAMS = \[.*?\n\t\];', lambda m: js_seams),
])

php_seams = '$seams = array(\n' + ''.join(
    '\t\tarray(' + ', '.join('array(%d, %d)' % tuple(p) for p in line) + '),\n' for line in geo['SEAMS']) + '\t);'
edit('inc/page-loader.php', [
    (r'aspect-ratio: \d+ / \d+;(\s*\}\s*\.spl3--img)', 'aspect-ratio: %d / %d;\\1' % (geo['W'], geo['H'])),
    (r'Numbers are picture pixels \(\d+ x \d+\)', 'Numbers are picture pixels (%d x %d)' % (geo['W'], geo['H'])),
    (r'\$width = \d+;(\s*\$height = \d+;\s*\$top)', '$width = %d;\\1' % geo['W']),
    (r'\$height = \d+;(\s*\$top)', '$height = %d;\\1' % geo['H']),
    (r'\$top = \d+;', '$top = %d;' % geo['TOP']),
    (r'\$base = \d+;', '$base = %d;' % geo['BASE']),
    (r'\$word_left = \d+;', '$word_left = %d;' % geo['WORD_LEFT']),
    (r'\$sign_left = \d+;', '$sign_left = %d;' % geo['SIGN_LEFT']),
    (r'\$seams = array\(\n.*?\n\t\);', lambda m: php_seams),
])

edit('inc/logo-motion.php', [
    (r"'width' => \d+,\s*'height' => \d+,", "'width' => %d,\n\t\t'height' => %d," % (geo['W'], geo['H'])),
])
