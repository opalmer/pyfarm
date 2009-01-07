'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 16 2008
PURPOSE: Network modules used to help facilitate network communication
'''
import sys
import socket
import threading
from PyQt4.QtCore import *
from Info import System
from FarmLog import FarmLog

class MulticastServer(QThread):
    '''Threaded server to send multicast packets and listen for clients'''
    def __init__(self,  parent=QThread(),  port=51423, host='', timeout=3):
        super(MulticastServer,  self).__init__(parent)
        self.log = FarmLog("Network.MulticastServer()")
        self.log.setLevel('debug')
        self.port = port
        self.host = host
        #self.nodes = []
        self.mutex = QMutex()
        self.dest = ('<broadcast>', self.port)
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.sock.settimeout(timeout)
        self.thread = QThread()
        self.done = False
            
    def run(self):
        '''Send the broadcast packet accross the network'''
        try:
            nodes = []
            # get ready to broadcast
            self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
            self.sock.sendto("I'm a server!  My name is %s" % System().name(), self.dest) 
            self.log.debug("Looking for nodes; press Ctrl-C to stop.")
            
            while True:
                (message, address) = self.sock.recvfrom(2048)
                self.log.debug("Found Node: %s:%s" % (address[0], address[1]))
                nodes.append('%s:%s' % (address[0], str(address[1])))

        except (socket.timeout,  socket.error):
            #self.start()
            pass
            
        finally:
            #return nodes
            print 'Finished? ', self.isFinished()
            print 'Running? ', self.isRunning()
            self.emit("finished()")
            print 'Finished? ', self.isFinished()
            print 'Running? ', self.isRunning()

class MulticastClient(QThread):
    '''Threaded client to recieve a multicast packet and log the server ip/port'''
    def __init__(self, port=51423, host='', parent=None):
        super(MulticastClient,  self).__init__(parent)
        self.log = FarmLog("Network.MulticastClient()")
        self.log.setLevel('debug')
        self.port = port
        self.host = host
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    def run(self):
        '''Receieve the broadcast packet and reply to the host'''
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        self.sock.bind((self.host, self.port)) #setup a socket to receieve a connection
        self.log.debug("Looking for server; press Ctrl-C to stop.")

        while True:
            try:
                # this loop is run ever time a connection comes in
                message, address = self.sock.recvfrom(8192) # get the connection info and message
                self.log.debug("Found Server: %s:%s" % (address[0], address[1]))

                # Now, reply back to the server with our address and message
                self.sock.sendto("Hello, my name is %s" % System().name(), address)
                #sys.exit()
            except (KeyboardInterrupt, SystemExit):
                sys.exit(self.log.critical('PROGRAM TERMINATED'))


class WakeOnLan(object):
    '''Designed to utilize wake on lan to startup a remote machine'''
    def wake( macaddress ):
        '''
        Wake computer with given mac address

        NOTE: address can either include or omit the colons.
        '''
        # if the len of macaddress = 12 do nothing
        if len(macaddress) == 12:
            pass

        # if it does not, add : every third character
        elif len(macaddress) == 12 + 5:
            sep = macaddress[2]
            macaddress = macaddress.replace(sep, '')

        # unless they did something wrong.....
        else:
            raise ValueError('Incorrect MAC address format')

        # Pad the synchronization stream.
        data = ''.join(['FFFFFFFFFFFF', macaddress * 20])
        send_data = ''

        # Split up the hex values and pack.
        for i in range(0, len(data), 2):
            send_data = ''.join([send_data,
                                 struct.pack('B', int(data[i: i + 2], 16))])

        # Broadcast it to the LAN.
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        sock.sendto(send_data, ('<broadcast>', 7))


UDP_PORT = 43278; CHECK_PERIOD = 20; CHECK_TIMEOUT = 15

import socket, threading, time

class Heartbeats(dict):
    """Manage shared heartbeats dictionary with thread locking"""

    def __init__(self):
        super(Heartbeats, self).__init__()
        self._lock = threading.Lock()

    def __setitem__(self, key, value):
        """Create or update the dictionary entry for a client"""
        self._lock.acquire()
        super(Heartbeats, self).__setitem__(key, value)
        self._lock.release()

    def getSilent(self):
        """Return a list of clients with heartbeat older than CHECK_TIMEOUT"""
        limit = time.time() - CHECK_TIMEOUT
        self._lock.acquire()
        silent = [ip for (ip, ipTime) in self.items() if ipTime < limit]
        self._lock.release()
        return silent

class HeartbeatReceiver(threading.Thread):
    """Receive UDP packets and log them in the heartbeats dictionary"""

    def __init__(self, goOnEvent, heartbeats):
        super(Receiver, self).__init__()
        self.goOnEvent = goOnEvent
        self.heartbeats = heartbeats
        self.recSocket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.recSocket.settimeout(CHECK_TIMEOUT)
        self.recSocket.bind((socket.gethostbyname('localhost'), UDP_PORT))

    def run(self):
        while self.goOnEvent.isSet():
            try:
                data, addr = self.recSocket.recvfrom(5)
                if data == 'PyHB':
                    self.heartbeats[addr[0]] = time.time()
            except socket.timeout:
                pass
