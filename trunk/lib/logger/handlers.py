# No shebang line, this module is meant to be imported
#
# INITIAL: Aug 11 2011
# PURPOSE: To handle output of logging information
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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

import sys
import inspector

# classes should be setup the same and meet some requirements
# so the config can still use handlers.FileHandler or handlers.RotatingHandler
# TODO: Determine requirements
class FileHandler(object): pass
class RotatingHandler(object): pass # inherit from FileHandler???

def stdout(output):
    sys.stderr.write(output)
# END stdout

def stderr(output):
    sys.stderr.write(output)
# END stderr

def inspector(output):
    print "module.methodCalled[line]"
# END inspector