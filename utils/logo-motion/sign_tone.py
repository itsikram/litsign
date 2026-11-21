"""
Brighter blue "online .com" sign (owner, 2026-11-20: picked "Brighter blue
sign" so the header sign looks like the footer's on dark navy).

    python sign_tone.py <logo 2000x667 png> <out.png> [gain]

Every blue pixel of the sign plate and pole (right of the letters, above the
tagline) is lifted: colour x gain + (4, 10, 14). The white "online .com"
lettering and everything else stay as they are. Run after tile.py and before
dark.py / slice.py. Needs Pillow and numpy.
"""
import sys

import numpy as np
from PIL import Image

SRC, OUT = sys.argv[1], sys.argv[2]
GAIN = float(sys.argv[3]) if len(sys.argv) > 3 else 1.18

a = np.asarray(Image.open(SRC).convert('RGBA')).astype(np.float64)
H, W = a.shape[:2]
region = np.zeros((H, W), bool)
region[150:460, 1690:] = True           # the sign and its pole in the 2000 x 667 artwork
blue = region & (a[..., 2] > a[..., 0] + 30) & (a[..., 3] > 0)
a[blue, :3] = np.clip(a[blue, :3] * GAIN + np.array([4.0, 10.0, 14.0]), 0, 255)
Image.fromarray(a.round().astype(np.uint8), 'RGBA').save(OUT, optimize=True)
print('brightened', int(blue.sum()), 'sign pixels x', GAIN)
