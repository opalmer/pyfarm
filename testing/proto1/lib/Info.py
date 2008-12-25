'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 8 2008
PURPOSE: Module used to return miscellaneous info either about the system
or PyFarm itself.
'''

import os
import sys
import uuid
from FarmLog import FarmLog
from subprocess import Popen,PIPE

log = FarmLog('lib.Info')

# TODO: Add software discovery to Info module
# TODO: Find a cross platform way to return the mac address and
class System(object):
    '''Return important information abou the system'''
    def __init__(self):
        super(System,  self).__init__()

    def time(self, format ):
        '''Return the current system time to the user'''
        import time
        return time.strftime("%d %b %Y %H:%M:%S")

    # TODO: Find cross-platform ways to get CPU load
    def cpuLoad(self):
        '''Return the current CPU load to the user'''
        if self.os() == 'linux':
            a = Popen('uptime', bufsize=1024, stdout=PIPE)
            load = a.stdout.read().split('\n')[0].split(', ')[2:5]
            one = load[0][15:]
            five = load[1]
            fifteen = load[2]

            return [one, five, fifteen]

    # TODO: ONLY allow execution of Info.isRoot() if running on Linux
    def isRoot(self):
        '''Return false if not running as root, true if you are.'''
        import os
        if self.os() == 'linux':
            if os.getuid() == 0:
                return True
            else:
                return False
        else:
            sys.exit(log.error('os.getuid() can only be called on linux!'))

    def os(self):
        '''Return the os type to PyFarm (win,irux,etc.)'''
        if os.name == 'posix':
            return 'linux'
        elif os.name == 'nt':
            return 'windows'
        elif os.name == 'mac':
            return'mac'
        elif os.name == 'dos':
            return 'dos'
        elif os.name == 'os2':
            pass # will figure out what to do here later

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
            mac = hex(uuid.getnode())[2:len(mac)-1]

        return mac

# TODO: Begin work on job info class
class Job(object):
    '''Return info about a specific job'''
    def __init__(self):
        pass
