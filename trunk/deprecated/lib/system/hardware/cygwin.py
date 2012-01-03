# No shebang line, this module is meant to be imported
#
# INITIAL: March 29 2011
# PURPOSE: To query and return information about the local system (cygwin)
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import sys
import types
import site

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, "..", "..", ".."))
site.addsitedir(root)

from common import *

def load():
    '''Return the average system load'''
    loads = open('/proc/loadavg', 'r').read().split()
    return float(loads[0]), float(loads[1]), float(loads[2])

def osName():
    '''Operating system name based on current file name'''
    return os.path.basename(__file__).split('.')[0]

def report():
    '''Report all hardware information in the form of a dictionary'''
    output = {}

    for key, value in globals().items():
        isFunction = type(value) == types.FunctionType
        isPrivate = key.startswith("_")
        isReport = key == "report"

        if isFunction and not isPrivate and not isReport:
            output[key] = value()

    return output

if __name__ == '__main__':
    print "                 %s SYSTEM INFORMATION" % osName().upper()
    for key, value in report().items():
        print "%25s | %s" % (key, value)
