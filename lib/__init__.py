import os
import sys
import fnmatch

from includes import *

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

import lib
import Logger

logger = Logger.Logger("lib.__init__")

for filename in os.listdir(CWD):
    matchPy      = fnmatch.fnmatch(filename, "*.py")
    matchInit    = fnmatch.fnmatch(filename, "*__init__*")
    matchInclude = fnmatch.fnmatch(filename, "*includes*")
    isLogger     = fnmatch.fnmatch(filename, "*Logger*")
    if matchPy and not matchInit and not matchInclude and not isLogger:
        varName                        = filename.split('.')[0]
        scriptPath                     = os.path.join(CWD, filename)
        vars()[varName]                = lib.importFile(scriptPath)
        logger.debug("Importing: lib.%s" % varName)
