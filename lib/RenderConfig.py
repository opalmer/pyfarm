'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Feb 6 2009
PURPOSE: Module used to configure a command line render based on operating system
and architecture.   This module first looks at the operating system, then the arhitecture.
After discovering this information it will then try and discover the currently installed software.

    This file is part of PyFarm.

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

import os
import glob
import fnmatch
from os.path import isfile, islink

def GetOs():
    '''
    Get the type of os and the architecture

    OUTPUT:
        [ operating_system, arhitecture ]
    '''
    output = []

    # if linux, do this
    if os.name == 'posix' and os.uname()[0] != 'Darwin':
        output.append('linux')
        if os.uname()[4] == 'x86_64':
            output.append('x64')
        elif os.uname()[4] == 'i386' or os.uname()[4] == 'i686' or os.uname()[4] == 'x86':
            output.append('x86')

    # if mac, do this
    elif os.name == 'posix' and os.uname()[0] == 'Darwin':
        output.append('mac')
        if os.uname()[4] == 'i386' or os.uname[4] == 'i686':
            output.append('x86')

    elif os.name == 'nt':
        output.append('windows')
        output.append(os.getenv(PROCESSOR_ARCHITECTURE))

    return output

def FindProgram(pattern, rootPath):
    '''
    Locate all files matching supplied filename pattern in and below
    supplied root directory

    INPUT:
        pattern (string) -- file to search for
        rootPath (string) -- Path to start search at
    '''
    for path, dirs, files in os.walk(os.path.abspath(rootPath)):
        for file in files:
          if glob.fnmatch.fnmatch(file, pattern):
            yield os.path.join(path, file)


class SoftwareInstalled(object):
    '''
    Preconfigure the software

    INITIAL VARS:
        self.os -- the os of the system (linux, mac, windows)
        self.arch -- the architecture of the processor (x86, x64)
    '''
    def __init__(self):
        self.os = GetOs()[0]
        self.arch = GetOs()[1]

    def maya(self):
        '''
        Return all of the installed versions of maya as a dictionary

        OUTPUT:
            { /
                'maya2008', '/path/to/2008/render/program' /
                'maya2009', '/path/to/2009/render/program' /
            }
        '''
        OUTPUT = {}
        if self.os == 'linux':
            self.prefix = '/usr/autodesk'
            for result in FindProgram('Render', self.prefix):
                # TRY AND USE A REGULAR EXPRESSION
                # TRY AND USE A REGULAR EXPRESSION
                # TRY AND USE A REGULAR EXPRESSION
                # TRY AND USE A REGULAR EXPRESSION
                # TRY AND USE A REGULAR EXPRESSION
                # TRY AND USE A REGULAR EXPRESSION
                for i in result.split('/'):
                    if len(i.split('-')) > 1:
                        OUTPUT[i.split('-')[0]] = result
                    else:
                        pass
            return OUTPUT

        elif self.os == 'mac':
            self.prefix = '/Applications/Autodesk/maya'

        elif self.os == 'windows':
            self.prefix = 'C:\Program Files\Autodesk\Maya'

    def shake(self):
        '''
        Return the installation of shake, if it is installed

        OUTPUT:
        { /
            'shake', '/path/to/shake/installation' /
        }
        '''
        OUTPUT = {}

        if self.os == 'linux':
            shake0 = '/usr/apple/shake-v4.00.0607/bin/shake'
            shake1 = '/opt/shake/bin/shake'
            if isfile(shake0):
                pass

mya = SoftwareInstalled()
maya = mya.maya()
print maya['maya2008']
