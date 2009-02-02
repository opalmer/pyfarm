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
from PyQt4.QtCore import QCoreApplication

CFG = os.getcwd()+'/settings.cfg'
SIZEOF_UINT16 = Settings.Network(CFG).Unit16Size()
BROADCAST_PORT = Settings.Network(CFG).BroadcastPort()
QUE_PORT = Settings.Network(CFG).QuePort()
STDOUT_PORT = Settings.Network(CFG).StdOutPort()
STDERR_PORT = Settings.Network(CFG).StdErrPort()

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
        '''Que class to manage connectiont to remote que'''
        def __init__(self):
            pass

        def recv(self):
            '''Receieve a command from the remote que'''
            # make the vars global
            global JOB
            global FRAME
            global COMMAND

            # setup the server
            self.queRecv = TCPServerQue()
            self.connect(self.queRecv, SIGNAL("JOB"), self.setJob)
            self.connect(self.queRecv, SIGNAL("FRAME"), self.setFrame)
            self.connect(self.queRecv, SIGNAL("COMMAND"), self.setCommand)
            self.queRecv.listen(QHostAddress(GetLocalIP(MASTER)), QUE_PORT)

        def setJob(self, job):
            '''Set the job var from the server'''
            JOB = job

        def setFrame(self, frame):
            '''Set the frame var from the server'''
            FRAME = frame

        def setCommand(self, command):
            '''Set the command var from the server'''
            COMMAND = command

        def get(self):
            '''Get an item from the remote que'''
            pass

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
    # broadcast your IP, get the master IP
    NetworkClient().broadcast()

    # next, get ready to get the first command
    que = NetworkClient.Que()
    que.recv()

# if run from the command line
if __name__ == "__main__":
    try:
        # QCoreApplication creates the event loop.
        # This is used instead of QApplication because the program
        # is run via the console.
        app = QCoreApplication(sys.argv)
        main()
        app.exec_()

    except KeyboardInterrupt:
        sys.exit("PROGRAM TERMINATED")

else:
    sys.exit("This is a program, not a module.  Command line use only!")
