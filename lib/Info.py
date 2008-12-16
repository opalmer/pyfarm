'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 8 2008
PURPOSE: Module used to return miscellaneous info either about the system
or PyFarm itself.
'''

class System:
    def time( format ):
        '''Return the current system time to the user'''
        import time
        return time.strftime("%d %b %Y %H:%M:%S")

    def cpuLoad():
        '''Return the current CPU load to the user'''
        from subprocess import Popen,PIPE

        a = Popen('uptime', bufsize=1024, stdout=PIPE)
        load = a.stdout.read().split('\n')[0].split(', ')[2:5]
        one = load[0][15:]
        five = load[1]
        fifteen = load[2]

        return [one, five, fifteen]

    def isRoot():
        '''Return false if not running as root, true if you are.'''
        import os
        if os.getuid() == 0:
            return True
        else:
            return False

    def os():
        '''Return the os type to PyFarm (win,irux,etc.)'''
        import os
        import sys
        #'posix', 'nt', 'dos', 'os2', 'mac', or 'ce'
        if os.name == 'posix':
            return 'linux'
        elif os.name == 'nt':
            return 'windows':
        elif os.name == 'dos':
            return 'dos'
        elif os.name == 'os2':
            pass # will figure out what to do here later


class Job(object):
    '''Return info about a specific job'''
    def __init__(self):
        pass
