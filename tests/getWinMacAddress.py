'''
Used to get a windows mac address
'''
import os
import string
from subprocess import Popen,PIPE

mac = []
search = False
state = 'Media disconnected'
#state = 'Intel'

# first connect to and read output from ipconfig
p = Popen(['ipconfig /all'],shell=True, stdout=PIPE)

while True:
    line = p.stdout.readline().split('\r\n')[0]
    if line.find( state ) > -1:
        # enable search se we can find the assc. mac address for this connection
        search = True

    if search:
        # if we find the 'Physical Address' line, break it down
        if line.count('Physical') and line.count('00') == 1:
            addr = line.split('\r\n')[0].split(':')[1].split(' ')[1]
            if addr[0:2] == '00':
                print addr

    # now set search to false so we no longer look for ALL mac addrs
    search = False

    if line == '' and p.poll() != None:
        break
