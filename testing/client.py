#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
PURPOSE: To connect and test a remote client server
INITIAL: Oct 16 2008
'''
import os
import sys

if not len(sys.argv) == 2:
    print "\nUSAGE:\n\t%s <server>" % sys.argv[0]
else:
   print "\nType SEND <enter> to send your message!"
   os.system('telnet %s 54321' % sys.argv[1]) 