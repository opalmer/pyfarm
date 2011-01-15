#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Nov 17 2010
PURPOSE [FOR DEVELOPMENT PURPOSES ONLY]:
    Script to stop virtual hosts

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

for i in range(6):
    host = "pynode%s" % str(i+1).zfill(2)
    print "Brining up %s..." % host

    if os.name == "nt":
        os.system("start /B VBoxHeadless -s %s" % host)

print "CLOSING THIS WINDOW WILL FORCE TERMINATE ALL RUNNING VMs!"
