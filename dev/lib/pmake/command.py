# No shebang line, this module is meant to be imported
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

import os
import types
import inspect
import includes

def __isMethod(name, methods):
    if not methods.get(name):
        includes.HelpString.noSuchCall(name)

def execute(method, methods):
    # find and run all parent calls
    for parentCall in inspect.getargspec(method).args:
        __isMethod(parentCall, methods)
        execute(methods[parentCall], methods)

    #print dir(method.func_defaults)
    #print method.func_defaults
    print " :: Running %s" % method.func_name
    if os.getenv('PMAKE_DRYRUN'):
        if os.getenv('PMAKE_VERBOSE'):
            print "printing every line of code"
        print "NO"


class System(object):
    def __init__(self):
        self.file = tempfile.NamedTemporoaryFile(delete=False)
        # TODO: Write shebang/echo off depending on os
        # TODO: (windows) script SETLOCAL/ENDLOCAL


    # TODO: script should be set to change directory as soon as it starts
    #       to this directory
    def setcwd(self, path): pass

    # TODO: Set environment variable in a cross platform fashion
    def setenv(self, key, value): pass

    # TODO: Set command
    def setcmd(self, command): pass

    # TODO: Run process, close, and remote self.file
    def execute(self): pass

