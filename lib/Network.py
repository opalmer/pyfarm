'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 16 2008
PURPOSE: Network modules used to help facilitate network communication
'''
import sys
import socket
import traceback

###################################
## TEST AND TWEAK CLASSES TO KEEP
## FROM BREAKING THE NETWORK!
###################################
class Broadcast(object):
    '''Class used to send a broadcast signal to all computers on a network'''
    def __init__(self, port=51423, host=''):
        self.port = port
        self.host = host
        self.dest = ('<broadcast>', self.port)
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    def send(self):
        '''Send the broadcast packet accross the network'''
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        self.sock.sendto("Hello", self.dest)

        print "Looking for replies; press Ctrl-C to stop."
        while 1:
            (buf, address) = self.sock.recvfrom(2048)
            if not len(buf):
                break
            print "Receieved from %s: %s" % (address, buf)

    def receieve(self):
        '''Receieve the broadcast packet and reply to the host'''
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        self.sock.bind((self.host, self.port))

        while 1:
            try:
                message, address = self.sock.recvfrom(8192)
                print "Got broadcast from", address
                # acknowledge the broadcase
                self.sock.sendto("Active Client: ", address)
            except (KeyboardInterrupt, SystemExit):
                raise
            except:
                traceback.print_exc()


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