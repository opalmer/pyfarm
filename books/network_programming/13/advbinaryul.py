#!/usr/bin/env python
# Advanced binary upload - Chapter 13 - advbinaryul.py
# Arguments: host, username, localfile, remotepath

from ftplib import FTP
import sys, getpass, os.path

host, username, localfile, remotepath = sys.argv[1:]
password = getpass.getpass("Enter password for %s on %s: " % \
        (username, host))
f = FTP(host)
f.login(username, password)

f.cwd(remotepath)
f.voidcmd("TYPE I")

fd = open(localfile, 'rb')
datasock, esize = f.ntransfercmd('STOR %s' % os.path.basename(localfile))
esize = os.stat(localfile)[6]
transbytes = 0

while 1:
    buf = fd.read(2048)
    if not len(buf):
        break
    datasock.sendall(buf)
    transbytes += len(buf)
    sys.stdout.write("Sent %d of %d bytes (%.1f%%)\r" % (transbytes, esize,
        100.0 * float(transbytes) / float(esize)))
    sys.stdout.flush()
datasock.close()
sys.stdout.write("\n")
fd.close()
f.voidresp()

f.quit()
