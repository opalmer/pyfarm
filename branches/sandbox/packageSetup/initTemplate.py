import os

try:
    from includes import *

except ImportError:
    pass

for filename in os.listdir(os.path.dirname(os.path.abspath(__file__))):
    isInit    = filename.startswith("__init__")
    isInclude = filename.startswith("includes")

    if filename.endswith(".py") and not isInit and not isInclude:
        __import__(filename.split(".")[0], locals(), globals())

# cleanup extra objects
del os, filename, isInit, isInclude