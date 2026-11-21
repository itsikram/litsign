"""
Dark-background copy of the logo (footer, page loader): the navy tagline
becomes off-white (#ECF1F7); everything else stays identical, so the
measurements in js/logo-motion.js and inc/page-loader.php still fit.

    python dark.py <logo.png> <out.png>

The tagline is everything below the first fully empty row under the letters.
Needs Pillow and numpy.
"""
import sys

import numpy as np
from PIL import Image

a = np.asarray(Image.open(sys.argv[1]).convert('RGBA')).copy()
alpha = a[..., 3]
rows = (alpha >= 12).any(axis=1)
first = int(np.argmax(rows))
# Skip the letters' band (at least 200 rows tall), then find the empty row.
y = first + 200
while rows[y]:
    y += 1
# ...then the first row of the tagline (faint edge pixels above it stay as they are).
while not rows[y]:
    y += 1
band = a[y:]
ink = band[..., 3] > 0
band[..., 0][ink] = 236
band[..., 1][ink] = 241
band[..., 2][ink] = 247
Image.fromarray(a, 'RGBA').save(sys.argv[2], optimize=True)
print('tagline recoloured from row', y)
