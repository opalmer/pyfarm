#!/usr/bin/env python
#
# INITIAL: May 21 2011
# PURPOSE: To enforce some PEP 8 standards on PyFarm
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
import re
import shutil
import tempfile

cwd = os.path.abspath(__file__)
root = os.path.abspath(os.path.join(cwd, "..", "..", ".."))
tmp = os.path.join(tempfile.gettempdir(), "pyfarmPEP8")
assignmentOps = re.compile(r"""(.+)\s+=\s+(.+)""")
assignmentDict = re.compile(r"""(.+)\s+:\s+(.+)""")

if not os.path.isdir(tmp):
    os.makedirs(tmp)

for root, path, files in os.walk(root):
    for name in files:
        script = os.path.join(root, name)

        if not script.split(".")[-1] in ("pyw", "py") or "PEP8" in name:
            continue
        print "checking %s" % script

        # make a backup of the original
        shutil.copy(script, tmp)

        print "...backed up %s" % os.path.join(tmp, name)

        count = 0
        output = open(os.path.join(tmp, name), 'w')
        for line in open(script, 'r'):
            matchAssign = assignmentOps.match(line)
            matchDictAssign = assignmentDict.match(line)

            # strip extra whitespace around assignment operators
            if matchAssign:
                variable = matchAssign.group(1).rstrip(" ")
                value = matchAssign.group(2).lstrip(" ")
                line = "%s = %s\n" % (variable, value)
                count += 1

            # strip any extra spaces around : in a dictionary key/value
            # pair
            if matchDictAssign:
                key = matchDictAssign.group(1).rstrip(" ")
                value = matchDictAssign.group(2).lstrip(" ")
                line = "%s : %s\n" % (key, value)
                count += 1

            output.write(line)
        output.close()
        shutil.copy(output.name, script)

        if count:
            print "...%i replacement(s) made" % count
