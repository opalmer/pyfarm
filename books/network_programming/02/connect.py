#!/usr/bin/env python
# Basic Connection Example - Chapter 2 - connect.py

import socket

print "Creating socket...",
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
print "done."

print "Connecting to remote host...",
s.connect(("www.google.com", 80))
print "done."
