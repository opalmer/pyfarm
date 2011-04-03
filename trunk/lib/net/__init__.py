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
from includes import *
from errors import *

try:
    import tcp # we seem to have trouble import lib...

except ImportError, error:
    pass

for filename in os.listdir(os.path.dirname(os.path.abspath(__file__))):
    isInit    = filename.startswith("__init__")
    isInclude = filename.startswith("includes")

    if filename.endswith(".py") and not isInit and not isInclude:
        __import__(filename.split(".")[0], locals(), globals())

# cleanup extra objects
del os, filename, isInit, isInclude
