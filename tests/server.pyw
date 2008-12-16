'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 16 2008
PURPOSE: Used to create a small gui and receieve information
'''
import math, random, sys
from PyQt4.QtCore import *
from PyQt4.QtGui import *

class Server(QTcpServer):
    '''Threaded TCP server to handle incoming connections'''
    def __init__(self, parent=None):
        super(TcpServer, self).__init__(parent)

    def incomingConnection(self, socketID):
        '''Receieve incoming connection and give it a thread to work with'''
        thread = Thread(socketID,self)
        self.connect(thread, SIGNAL("finished()"), # if the signal 'finished' is given
                     thread, SLOT("deleteLater()")) # then delete the connection

        thread.start()


class Thread(QThread):
    '''
    lock = QReadWriteLock()
    def __