#!/usr/bin/env python
# Basic connection - Chapter 13 - connect.py

from ftplib import FTP

f = FTP('ftp.ibiblio.org')
print "Welcome:", f.getwelcome()
f.login()

print "CWD:", f.pwd()
f.quit()

