#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Jan 31 2009
PURPOSE: To handle and run all client connections on a remote machine
'''

import os
import sys
from lib.Network import *

class NetworkClient(object):
    '''
    Client class used to setup and run ops

    SUBCLASSES:
        StdOut - Setup and send standard output messages
        StdErr - Setup and send standard error messages
        Que - Setup a network connection to the que and get commands
    '''
    class StdOut(object):
        '''Standard Output Network Connection'''
        def __init__(self):
            pass

        def send(self, message):
            '''Send a standard message to the master'''
            pass

    class StdErr(object):
        '''Standard Error Network Connection'''
        def __init__(self):
            pass

        def send(self, message):
            '''Send a standard error to the master'''
            pass

    class Que(object):
        '''Que Network Connection'''
        def __init__(self):
            pass

        def get(self):
            '''Get an item from the remote que'''
            global JOB
            global FRAME
            global HOSTNAME
            global COMMAND

    def __init__(self):
        pass

    def broadcast(self):
        '''
        Broadcast process used for client/server
        discovery
        '''
        # using the broadcast packet, get the master IP
        # make it a global var so we can use it elsewhere
        global MASTER
        client = BroadcastClient()
        MASTER = client.run()
        print "Found the master @ %s" % MASTER


class Processing(object):
    '''
    Used for command logging and processing.
    '''
    def __init__(self):
        pass

def main():
    '''
    This is the main client program, the main event
    loop starts here.
    '''
    # broadcast your IP, get the master IP
    NetworkClient().broadcast()

    # after we get the master IP, setup the other
    # network connections



# if run from the command line
if __name__ == "__main__":
    main()
else:
    sys.exit("This is a program, not a module.  Command line use only!")
