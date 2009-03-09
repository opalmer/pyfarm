'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Dec 8 2008
PURPOSE: Module used to return miscellaneous info either about the system
or PyFarm itself.

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
try:
    from os import uname
    from os import loadavg

# if we get an import error do nothing,
#  error usually caused by trying to import a lib
#  that is designed for another operating system
except ImportError:
    pass

finally:
    import Info
    from os import sep, name, getenv
    import sys
    import uuid
    import time
    import socket
    from subprocess import Popen,PIPE
    from os.path import dirname, join
    from PyQt4.QtCore import QObject

def Int2Time(s):
        '''Given an input integer, return time elapsed'''
        #s=ms/1000
        m,s=divmod(s,60)
        h,m=divmod(m,60)
        d,h=divmod(h,24)
        return d,h,m,s

def ModulePath(module, level=0):
    '''Given a module return it's path to the n'th level'''
    if level == 0:
        return dirname(module)+'/'

    else:
        OUT = ''
        path = dirname(module).split(sep)

        for i in path[:len(path)-level]:
            OUT += '%s/' % i

        return OUT

class System(object):
    '''
    Return important information about the system
    '''
    def __init__(self):
        super(System,  self).__init__()

    def time(self, format ):
        '''Return the current system time to the user'''
        return time.strftime("%d %b %Y %H:%M:%S")

    def os(self):
        '''
        Get the type of os and architecture

        OUTPUT:
            [ operating_system, arhitecture ]
        '''
        output = []

        if name == 'posix' and uname()[0] != 'Darwin':
            output.append('linux')
            if uname()[4] == 'x86_64':
                output.append('x64')
            elif uname()[4] == 'i386' or uname()[4] == 'i686' or uname()[4] == 'x86':
                output.append('x86')

        # if mac, do this
        elif name == 'posix' and uname()[0] == 'Darwin':
            output.append('mac')
            if uname()[4] == 'i386' or uname[4] == 'i686':
                output.append('x86')

        elif name == 'nt':
            output.append('windows')
            output.append(getenv('PROCESSOR_ARCHITECTURE'))

        return output

    def load(self):
        '''
        Return the average CPU load to the user
        '''
        if self.os() == 'linux':
            return loadavg()

        elif self.os() == 'windows':
            # there is a not a way to do this on windows...yet
            pass

    def coreCount(self):
        '''Return the number of cores installed on the system'''
        # OS X: sysctl -n hw.logicalcpu
        # Win: getEnv(NUMBER_OF_PROCESSORS)
        # Linux: cat /proc/cpuinfo | grep siblings | awk {'print $3'}

    def hostname(self):
        '''Return the name of the computer'''
        return socket.gethostname()

    def macAddress(self):
        '''Return a list of mac address to the user'''
        mac = []

        if self.os() == 'linux':
            p = Popen(['ifconfig | grep HWaddr | awk {"print $5"}'],shell=True, stdout=PIPE)

            while True:
                line = p.stdout.readline()
                mac.append(line.split('\n')[0][len(line)-20:])
                if line == '' and p.poll() != None: break

        elif self.os() == 'windows':
            p = Popen(['ipconfig'])
        return mac


class Numbers(object):
    '''
    Provides several functions for creating, converting,
    and evaling numbers
    '''
    def __init__(self):
        pass

    def int2hex(self, intIn):
        '''Convert an integer to hexadecimal'''
        return "%x" % intIn

    def hex2int(self, inHex):
        '''Convert an hexadecimal to integer'''
        return int(inHex, 16)

    def randhex(self):
        '''Produces hex value based on time'''
        return "%x" % int(time.time())

    def randint(self):
        '''Produces rand int based on time'''
        return int(time.time())

class File(object):
    '''
    Large file class meant to handle multiple tasks
    including readline, file size, get extension, etc.
    '''
    def __init__(self, file):
        self.file = file

    def ext(self):
        '''Return the extension of the file'''
        return self.file.split('.')[len(self.file.split('.'))-1]


class Job(QObject):
    '''
    Contains information related to the state of a job

    NOTES:
    -Any function starting with count returns a quantity (int)
    -Any function starting with state return a boolean (True/False)
    '''
    def __init__(self, parnet=None):
        super(Job, self).__init__(parent)
        self.startTime = time.time()

        # setup frame related vars
        self.avgFrameTime = 0
        self.maxFrameTime = 0
        self.minFrameTime = 0

####
# counting functions
###

    def countSubJobs(self):
        '''Return the number of sub jobs'''
        pass

    def countTotalFrames(self):
        '''Number of frame total in job (reguardless of state)'''
        pass

    def countFramesRendering(self):
        '''Number of frames currently rendering'''
        pass

    def countFramesComplete(self):
        '''Number of frames compelete'''
        pass

    def countFramesFailed(self):
        '''Number of errors returned by clients'''
        pass

    def jobState(self):
        '''Return the current state of the job'''
        pass

    def frameState(self, job, id, frame):
        '''Return the current state of a given frame'''
        pass

    def timeElapsed(self):
        '''Time elapsed since start of job'''
        return self.startTime + time.time()

####
# Frame time related functions
###

    def _setMinFrameTime(self, frameTime):
        '''Set the new min. frame time and emit signal'''
        self.minFrameTime = frameTime
        self.emit(SIGNAL("minFrameTime"), frameTime)

    def _setMaxFrameTime(self, frameTime):
        '''Set the new max. frame time and emit signal'''
        self.maxFrameTime = frameTime
        self.emit(SIGNAL("maxFrameTime"), frameTime)

    def _setAvgFrameTime(self, frameTime):
        '''Set the avg min. frame time and emit signal'''
        self.avgFrameTime = (self.avgFrameTime+frameTime) / 2
        self.emit(SIGNAL("avgFrameTime"), self.avgFrameTime)

    def setFrameTime(self, frameTime):
        '''Check and see if frameTime is longer than the previous
        longest frame time.  If it is, set a new longestFrameTime
        '''
        if self.minFrameTime and self.maxFrameTime and self.avgFrameTime:
            if frameTime > self.maxFrameTime:
                self._setMaxFrameTime(frameTime)
            elif frameTime < self.minFrameTime:
                self._setMinFrameTime(frameTime)

            self._setAvgFrameTime(frameTime)

        else:
            self._setMinFrameTime(frameTime)
            self._setMaxFrameTime(frameTime)
            self._avgMaxFrameTime(frameTime)

    def minFrameTime(self):
        '''Return the minium frame time'''
        self.emit(SIGNAL("minFrameTime"), self.minFrameTime)
        return self.minFrameTime

    def maxFrameTime(self):
        '''Return the maxium frame time'''
        self.emit(SIGNAL("maxFrameTime"), self.maxFrameTime)
        return self.maxFrameTime

    def avgFrameTime(self):
        '''Return the average frame time'''
        self.emit(SIGNAL("avgFrameTime"), self.avgFrameTime)
        return self.avgFrameTime
