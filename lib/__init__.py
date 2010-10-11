import os
import sys

for root, dirs, files in os.walk(os.path.dirname(__file__)):
    if root not in sys.path:
        sys.path.append(root)