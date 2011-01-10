'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 10 2011
PURPOSE: Sync the source code of PyFarm with the local copy

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

SCRIPT_DIR = os.path.abspath(os.path.dirname(__file__))
PYFARM     = os.path.abspath(os.path.join(SCRIPT_DIR, "..",  ".."))
GIT_IGNORE = os.path.join(PYFARM,  ".gitignore")
SRC        = "/farm/src/"
DST        = "/home/render/pyfarm"

os.system('rm -R %s' % DST)

if not os.path.isdir(DST):
    os.makedirs(DST)
    print "Created Destination: %s" % DST

exclude  = []
commands = ['rsync -rv --progress %s %s' % (SRC, DST)]
for exclusion in open(GIT_IGNORE, 'r'):
    exclude.append("--exclude '%s'" % exclusion.rstrip("\r\n"))

commands.append(' '.join(exclude))
cmd = ' '.join(commands)
os.system(cmd)
