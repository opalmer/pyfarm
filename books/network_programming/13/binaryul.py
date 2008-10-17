#!/usr/bin/env python
# Binary download - Chapter 13 - binaryul.py
# Arguments: host, username, localfile, remotepath

from ftplib import FTP
import sys, getpass, os.path

host, username, localfile, remotepath = sys.argv[1:]
password = getpass.getpass("Enter password for %s on %s: " % \
        (username, host))
f = FTP(host)
f.login(username, password)

f.cwd(remotepath)
fd = open(localfile, 'rb')
f.storbinary('STOR %s' % os.path.basename(localfile), fd)
fd.close()

f.quit()

