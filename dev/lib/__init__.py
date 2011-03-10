import os
import sys
import imp
import fnmatch

try:
    from includes import *

except ImportError:
    pass

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

def importFile(filename,  verbose=False):
    (path, name) = os.path.split(filename)
    (name, ext) = os.path.splitext(name)
    try:
        (file, filename, data) = imp.find_module(name, [path])

    except ImportError, e:
        raise ImportError(e)

    return imp.load_module(name, file, filename, data)

for filename in os.listdir(CWD):
    matchPy      = fnmatch.fnmatch(filename, "*.py")
    matchPyc     = fnmatch.fnmatch(filename, "*.pyc")
    matchInit    = fnmatch.fnmatch(filename, "*__init__*")
    matchInclude = fnmatch.fnmatch(filename, "*includes*")
    if matchPy and not matchPyc and not matchInit and not matchInclude:
        varName                        = filename.split('.')[0]
        scriptPath                     = os.path.join(CWD, filename)
        vars()[varName]                = importFile(scriptPath)