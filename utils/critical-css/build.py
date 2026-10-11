import gzip
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import cssparse
tree = cssparse.load(sys.argv[1])
keep = {int(x) for x in open(os.path.join(HERE, 'keep.txt')).read().strip().split(',')}
safe = re.compile(r'\.(collapse|collapsing|fade|show|visually-hidden(-focusable)?|modal-open|modal-backdrop)\b')
extra = {r[1] for r in cssparse.walk_rules(tree) if safe.search(r[2])}
keep |= extra
css = cssparse.emit(tree, keep)
open(sys.argv[2], 'w', encoding='utf8', newline='').write('/* Bootstrap v5.3.3 rules used by first paint; the full file loads after. Generated from css/bootstrap.min.css, see inc/performance.php. */' + css)
print('rules', len(keep), 'bytes', len(css), 'gz', len(gzip.compress(css.encode())))
