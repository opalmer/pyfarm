# No shebang line, this module is meant to be imported
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

import re
import os
import types
import inspect
import includes

def __kwargs(args, defaults):
    '''Return a dictionary of keyword arguments'''
    kwargs = {}

    # extract the keyword arguments
    if defaults:
        index = -1
        for default in defaults:
            kwargs[args[index]] = defaults[index]
            index -= 1

        # remove any keyword argument keys from args
        args = args[:index+1]

    return args, kwargs

# TODO: Extract function code
# TODO: Remove comments from ^
# TODO: Remove arguments from ^
# TODO: Inject keyword arguments into ^
def execute(method, methods, frame):
    '''Attempt to execute the requested method'''
    args, varargs, varkw, defaults = inspect.getargspec(method)
    preMethods, kwargs = __kwargs(args, defaults)

    for call in preMethods:
        childMethod = methods.get(call)

        if not childMethod:
            print "ERROR: no such method %s" % call
            sys.exit(1)

        execute(childMethod, methods, frame)

    name = method.func_name
    print ":: Running %s" % name
    source = inspect.getsource(method)
    lines = source.split("\n")

    keywords = []
    for key, value in kwargs.items():
        if type(value) in types.StringTypes:
            keywords.append("%s='%s'" % (key, value))

        else:
            keywords.append("%s=%s" % (key, str(value)))

    # replace the function call and then add the call to the execution
    # code
    lines[0] = "def %s(%s):" % (name, ",".join(keywords))
    lines.append("%s()" % name)
    source = "\n".join(lines)
    exec source


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

