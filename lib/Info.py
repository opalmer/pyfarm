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

# if we get an import error pass it
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
