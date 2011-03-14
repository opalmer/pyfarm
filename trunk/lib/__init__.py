'''
HOMEPAGE: www.pyfarm.net
PURPOSE: To import the standard includes and setup the package

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
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
