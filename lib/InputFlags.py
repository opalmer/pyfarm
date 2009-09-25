'''
HOMEPAGE: www.pyfarm.net
INITIAL: Sept 25 2009
PURPOSE: Small library for discovering system info and installed software

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
# From Python
import sys

# From PyFarm
from Info import bold

class CommandLineHelp(object):
    '''Return command line usage help'''
    def __init__(self, program):
        self.program = program

    def invalidFlag(self, error):
        '''Tell the user that the given flag is invalid'''
        print "ERROR: %s" % error
        sys.exit(2)

    def echo(self):
        '''Echo the help to the command line'''
        helpFlags = ("-h", "-?", "--help")
        i = 0
        out = bold(1)+"PROGRAM"+bold(0)+"\n\t%s\n" % sys.argv[0]
        out += bold(1)+"USAGE"+bold(0)+"\n\t%s [optional flag]\n" % sys.argv[0]
        out += bold(1)+"FLAGS"+bold(0)+"\n\t"
        for flag in helpFlags:
            if i < len(helpFlags)-1:
                out += bold(1)+flag+bold(0)+", "
            else:
                out += bold(1)+flag+bold(0)+" >> Command line usage help (this text)\n\n\t"
            i += 1

        out += bold(1)+"--sysinfo"+bold(0)+" >> Show info about PyFarm and the system it is running on (os, architecture, software, etc)\n\n\t"
        out += bold(1)+"--compile"+bold(0)+" >> Byte compile all of PyFarm's modules for speed\n\n\t"
        out += bold(1)+"--clean"+bold(0)+" >> Cleanup the local PyFarm installation (byte-compiled files, tmp databases, etc)\n\n\t"

        print out
        sys.exit(2)


class SystemInfo(object):
    '''Gather and prepare to return info about the system'''
    def __init__(self):
        pass

    def echo(self):
        '''Echo the system info to the command line'''
        print "generating system info"
        sys.exit(0)


class SystemUtilities(object):
    '''General system utilities to run from the command line'''
    def __init__(self, cwd):
        self.cwd = cwd

    def compile(self):
        '''Byte compile all modules'''
        print "compiling"
        sys.exit(0)

    def clean(self):
        '''Cleanup any extra or byte-compiled files'''
        print "cleaning"
        sys.exit(0)
