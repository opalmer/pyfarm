'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Feb 6 2009
PURPOSE: Module used to configure a command line render based on operating system
and architecture.

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
from os.path import isfile

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
        if self.os == 'linux':
            self.prefix = '/usr/autodesk/maya'
            maya2008x64 = self.prefix+'2008-x64/bin/Render'
            maya2008x86 = self.prefix+'2008/bin/Render'
            maya2009x64 = self.prefix+'2009-x64/bin/Render'
            maya2009x86 = self.prefix+'2009/bin/Render'
            
            # if we are running on a 64-bit system, run these checks
            if self.arch == 'x64':
                if isfile(maya2008x64):
                #if not isfile(maya2008x64):
                    print "Maya 2008 (64-bit) is installed @ %s" % maya2008x64
                if isfile(maya2009x64):
                #if not isfile(maya2009x64):
                    print "Maya 2009 (64-bit) is installed @ %s" % maya2009x64
            
            elif self.arch == 'x86':
                #if isfile(maya2008x86):
                if not isfile(maya2008x86):
                    print "Maya 2008 (32-bit) is installed @ %s" % maya2008x86
                #if isfile(maya2009x86):
                if not isfile(maya2009x86):
                    print "Maya 2009 (32-bit) is installed @ %s" % maya2009x86
                    
        elif self.os == 'mac':
            self.prefix = '/Applications/Autodesk/maya'
            
        elif self.os == 'windows':
            self.prefix = 'C:\Program Files\Autodesk\Maya'
            
a = SoftwareInstalled()
a.maya()
