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

    NOTES:
        -Each program config function contains a prefixList, this is the path
        to search for the installed software
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
        commonName = 'maya'
        fileGrep = 'Maya Scene File (*.mb *.ma)'
        widgetIndex = '0'
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
                                OUTPUT['Maya '+str(mayaRegEx.cap(0))[4:]] = "%s/%s/bin/Render::%s::%s::%s" % (prefix, result, commonName, fileGrep, widgetIndex)

                            elif isfile("%s/%s/bin/render" % (prefix, result)): # or if this is the render path
                                OUTPUT['Maya '+str(mayaRegEx.cap(0))[4:]] = "%s/%s/bin/Render::%s::%s::%s" % (prefix, result, commonName, fileGrep, widgetIndex)

                            else: #If the render path is not a default, inform the user
                                print "ERROR: Non default path for %s's renderer" % mayaRegEx.cap(0)

            return OUTPUT

        elif self.os == 'mac':
            prefixList = ['/Applications/Autodesk/']

        elif self.os == 'windows':
            prefixList = ['C:\Program Files\Autodesk']

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
        commonName = 'houdini'
        fileGrep = 'Houdini File (*.hip)'
        widgetIndex = '1'
        houRegEx = QRegExp(r"""hfs9.[15].[0-9]+""")

        # if we find extra paths to use, add them
        for newPath in AddExtraPath(extraPath):
            prefixList.append(newPath)

        if self.os == 'linux':
            prefixList = ['/opt', '/usr']
            for prefix in prefixList:
                if isdir(prefix):
                    for result in os.listdir(prefix):
                        if not houRegEx.indexIn(result): # if we the regular expression matches
                            if isfile('%s/%s/bin/hrender' % (prefix, result)):
                                OUTPUT['Houdini '+str(houRegEx.cap(0))[3:]] = '%s/%s/bin/hrender::%s::%s::%s' % (prefix, result, commonName, fileGrep, widgetIndex)

        elif self.os == 'mac':
            pass

        elif self.os == 'windows':
            #C:\Program Files\Side Effects Software\Houdini 9.5.303\hrender.exe
            prefixList = ['C:\Program Files\Side Effects Software']


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

        SHAKE FLAG NOTES:
            v/vv -- Verbose(-vv just gives you a percentage as the frames render) .
            cpus -- number of cpus to use
            sequential -- will process each file out node in turn.
            t -- fram range (ex. 1-10)
        '''
        OUTPUT = {}
        commonName = 'shake'
        fileGrep = 'Shake Script (*.shk)'
        widgetIndex = '2'
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
                            OUTPUT["Shake"] = '%s/%s/shake::%s::%s::%s' % (prefix, result, commonName, fileGrep, widgetIndex)

        if self.os == 'mac':
            prefixList = ['/Applications/Shake/shake.app/Contents/MacOS/shake']

        return OUTPUT

class ConfigureCommand(object):
    '''
    Given an input dictionary and a program (ex. self.maya)
    yield the output commands
    '''
    def __init__(self, softwareDict):
      self.software = softwareDict

    def maya(self, ver, sFrame, eFrame, bFrame, rayRender):
      '''
      Yield the sequence of frames for maya

      VARS:
         ver -- Version of maya to pull from self.software
         sFrame -- Start frame of sequence
         eFrame -- End Frame of sequence
         bFrame -- By frame or sequence step
         rayRender -- If using mental ray, set to true
      '''
      version = self.sofware[ver]

    def houdini(self, ver, sFrame, eFrame, bFrame):
      '''
      Yield the sequence of frames for houdini

      VARS:
        ver -- Version of houdini to pull from self.software
        sFrame -- Start frame of sequence
        eFrame -- End Frame of sequence
        bFrame -- By frame or sequence step
      '''
      version = self.sofware[ver]

    def shake(self, version, sFrame, eFrame, bFrame):
      '''
      Yield the sequence of frames for shake

      VARS:
        ver -- Version of shake to pull from self.software
        sFrame -- Start frame of sequence
        eFrame -- End Frame of sequence
        bFrame -- By frame or sequence step
      '''
      version = self.sofware[ver]


class RenderLayerBreakdown(object):
    '''
    Breakdown an input file into individual layers.
    Yield each layer back to the ui.
    '''
    def __init__(self, inputFile):
        self.file = inputFile

    def houdini(self):
        '''Output the houdini mantra nodes'''
        hip = open(self.file)
        exp = QRegExp(r"""[0-9]+out/[0-9a-zA-Z]+[.]parm""")

        for line in hip.readline():
            if not exp.indexIn(line):
                yield line



# small set of tests

# get ready to find the currently installed software
#LOCAL_SOFTWARE = {}
#software = SoftwareInstalled()

# find the software and add it to the dictionary
#LOCAL_SOFTWARE.update(software.maya())
#LOCAL_SOFTWARE.update(software.houdini())
#LOCAL_SOFTWARE.update(software.shake())

#for (software,path) in LOCAL_SOFTWARE.items():
#    print path.split('::')[1]
#    print 'Found %s at %s' % (software,path)
#
#print LOCAL_SOFTWARE
