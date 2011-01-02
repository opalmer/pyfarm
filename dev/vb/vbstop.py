#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Nov 17 2010
PURPOSE [FOR DEVELOPMENT PURPOSES ONLY]:
    Script to stop virtual hosts

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

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
import time

if os.name == "nt":
    SSH_KEY = "C:\Users\opalmer\.ssh\id_rsa_insecure"
	
elif os.name == "posix":
	SSH_KEY = "/home/opalmer/.ssh/id_rsa_insecure"

for i in range(6):
    host = '10.56.2.%i' % (i+2)
    print "Turning off %s..." % host
    os.system('ssh -i %s render@%s "sudo shutdown -h now"' % (SSH_KEY, host))

print "Sleeping 15 for hosts to shutdown..."
time.sleep(15)
print "Turning off pyroot..."
os.system('ssh -i %s render@pyroot "sudo shutdown -h now"' % SSH_KEY)
