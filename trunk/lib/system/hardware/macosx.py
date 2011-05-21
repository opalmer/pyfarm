'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 29 2011
PURPOSE: To query and return information about the local system (mac)

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
import types

from common import *

def cpuCount(): return 0
def cpuSpeed(): return 0
def ramTotal(): return 0
def ramFree(): return 0
def swapTotal(): return 0
def swapFree(): return 0
def load(): return 0, 0, 0
def uptime(): return 0
def idletime(): return 0
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
    print
    print "                 %s SYSTEM INFORMATION" % osName().upper()
    for key, value in report().items():
        print "%25s | %s" % (key, value)
