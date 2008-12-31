'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com || (703)725-6544
INITIAL: Dec 8 2008
PURPOSE: Module used to return miscellaneous info either about the system
or PyFarm itself.
'''

import os
import sys
import uuid
import time
from FarmLog import FarmLog
from subprocess import Popen,PIPE

log = FarmLog('lib.Info')

# TODO: Add software discovery to Info module
class System(object):
    '''
    Return important information about the system

    REQUIRES:
        Python:
            os
            sys
            uuid
            time
            subprocess

        PyFarm:
            FarmLog
            Info (this module)

    INPUT:
        None
    '''
    def __init__(self):
        super(System,  self).__init__()

    def time(self, format ):
        '''Return the current system time to the user'''
        return time.strftime("%d %b %Y %H:%M:%S")

    # TODO: Find cross-platform ways to get CPU load
    def cpuLoad(self):
        '''
        Return the current CPU load to the user

        OUTPUT:
            3 value array
            array[0] -- 1 min cpu load
            array[1] -- 5 min cpu load
            array[2] -- 15 min cpu load
        '''
        if self.os() == 'linux':
            a = Popen('uptime', bufsize=1024, stdout=PIPE)
            load = a.stdout.read().split('\n')[0].split(', ')[2:5]
            one = load[0][15:]
            five = load[1]
            fifteen = load[2]

            return [one, five, fifteen]

        elif self.os() == 'windows':
            sys.exit(log.error('Only linux systems can call System.cpuLoad()'))

    def isRoot(self):
        '''Return false if not running as root, true if you are.'''
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
            p = Popen(['ipconfig'])

        return mac

# TODO: Begin work on job info class
class Job(object):
    '''Return info about a specific job'''
    def __init__(self,  jobid):
        self.jobid = jobid
