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
from os.path import isfile, islink, isdir
from PyQt4.QtCore import QRegExp

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

def AddExtraPath(inputPath):
    '''
    If we find extra paths to add, insert them into the
    search list.
    '''
    if len(inputPath)>=1:
        for addPath in inputPath:
            yield addPath


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

    def maya(self, extraPath=[]):
        '''
        Return all of the installed versions of maya as a dictionary

        INPUT:
            extraPath -- Add extra path(s) to search for

        OUTPUT:
            { /
                'maya2008', '/path/to/2008/render/program' /
                'maya2009', '/path/to/2009/render/program' /
            }
        '''
        OUTPUT = {}
        prefixList = []
        mayaRegEx = QRegExp(r"""[m|M]aya(200[89]|8.[05]|8[05]|7(.0|0))""")

        # if we find extra paths to use, add them
        for newPath in AddExtraPath(extraPath):
            print newPath
            prefixList.append(newPath)
        
        if self.os == 'linux':
            # paths to search for maya
            prefixList = ['/usr/autodesk']

            for prefix in prefixList:
                if isdir(prefix):
                    for result in os.listdir(prefix): # for each dir in the search dir
                        if not mayaRegEx.indexIn(result): # if we find a match
                            if isfile("%s/%s/bin/Render" % (prefix, result)): #check to see if if this is the render path
                                OUTPUT[str(mayaRegEx.cap(0))] = "%s/%s/bin/Render" % (prefix, result)

                            elif isfile("%s/%s/bin/render" % (prefix, result)): # or if this is the render path
                                OUTPUT[str(mayaRegEx.cap(0))] = "%s/%s/bin/Render" % (prefix, result)

                            else: #If the render path is not a default, inform the user
                                print "ERROR: Non default path for %s's renderer" % mayaRegEx.cap(0)
            return OUTPUT

        elif self.os == 'mac':
            self.prefix = '/Applications/Autodesk/maya'

        elif self.os == 'windows':
            self.prefix = 'C:\Program Files\Autodesk\Maya'

    def houdini(self, extraPath=[]):
        '''
        Return all of the houdini versions installed

        INPUT:
            extraPath -- Add extra path(s) to search for

        OUTPUT:
            { /
                '9.5.244', '/opt/hfs9.5.244/bin/hrender'
            }
        '''
        OUTPUT = {}
        prefixList = []
        houRegEx = QRegExp(r"""hfs9.[15].[0-9]+""")

        # if we find extra paths to use, add them
        for newPath in AddExtraPath(extraPath):
            prefixList.append(newPath)

        if self.os == 'linux':
            prefixList = ['/opt', '/usr']
            for prefix in prefixList:
                if isdir(prefix):
                    for result in os.listdir(prefix):
                        if not houRegEx.indexIn(result):
                            if isfile('%s/%s/bin/hrender' % (prefix, result)):
                                OUTPUT[result] = '%s/%s/bin/hrender' % (prefix, result)

        return OUTPUT

    def shake(self, extraPath=[]):
        '''
        Return the installation of shake, if it is installed
        
        INPUT:
            extraPath -- Add extra path(s) to search for

        OUTPUT:
        { /
            'shake', '/path/to/shake/installation' /
        }
        '''
        OUTPUT = {}
        prefixList = ['/usr/apple/shake-v4.00.0607', '/opt/shake', \
                           '/usr/local/shake']
        
        # if we find extra paths to use, add them
        for newPath in AddExtraPath(extraPath):
            prefixList.append(newPath)
            
        if self.os == 'linux':
            for prefix in prefixList:
                if isdir(prefix):
                    for result in os.listdir(prefix):
                        if isfile('%s/%s/shake' % (prefix, result)):
                            OUTPUT["shake"] = '%s/%s/shake' % (prefix, result)
        return OUTPUT

# get ready to find the currently installed software
LOCAL_SOFTWARE = {}
software = SoftwareInstalled()
LOCAL_SOFTWARE.update(software.maya())
LOCAL_SOFTWARE.update(software.houdini())
LOCAL_SOFTWARE.update(software.shake())

for (software,path) in LOCAL_SOFTWARE.items():
    print 'Found %s at %s' % (software,path)