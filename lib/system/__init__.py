import os
import sys
import fnmatch

from includes import *

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

#import lib
import lib
logger = lib.importFile(os.path.join(PYFARM, "lib", "Logger.py"))

for filename in os.listdir(CWD):
    matchPy      = fnmatch.fnmatch(filename, "*.py")
    matchInit    = fnmatch.fnmatch(filename, "*__init__*")
    matchInclude = fnmatch.fnmatch(filename, "*includes*")
    if matchPy and not matchInit and not matchInclude:
        varName                        = filename.split('.')[0]
        scriptPath                     = os.path.join(CWD, filename)
        vars()[varName]                = lib.importFile(scriptPath)
        logger.debug("Importing: system.%s" % varName)
