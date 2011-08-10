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
import sys
import types
import optparse

class HelpString(object):
    def __init__(self):
        self.string = "%sPMake Usage: %s <call> <call> ..." % (os.linesep, sys.argv[0])

    @staticmethod
    def noSuchCall(name):
        '''Inform the user that the requested call does not exist'''
        print "No such call: %s" % name
        sys.exit(1)

    def addMethod(self, name, method):
        '''Add a new doc string for the given method'''
        doc = " -- ".join([name, method.__doc__ or "Not Documented"])
        self.string += "%s\t%s" % (os.linesep, doc)


def methods(frame):
    '''Return all methods in the given frame'''
    methods = {}

    for name, data in frame.f_locals.items():
        if type(data) == types.FunctionType and not name.startswith("_"):
            methods[name] = data

    return methods

def parseInput(methods):
    '''Comand line input parsing'''
    parser = optparse.OptionParser()
    parser.add_option(
                        "--dry-run", "-n", action="store_true",
                        default=False, dest="dryrun"
                     )
    parser.add_option(
                        "--verbose", "-v", action="store_true",
                        default=False, dest="verbose"
                     )
    options, args = parser.parse_args()

    if options.dryrun: os.environ['PMAKE_DRYRUN'] = "True"
    if options.verbose: os.environ['PMAKE_VERBOSE'] = "True"

    if not args:
        docs = HelpString()

        for name, method in methods.items():
            docs.addMethod(name, method)

        print docs.string
        sys.exit()

    return args
