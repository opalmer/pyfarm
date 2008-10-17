#!/usr/bin/env python
#  dir() example - Chapter 13 - dir.py

from ftplib import FTP

f = FTP('ftp.kernel.org')
f.login()

f.cwd('/pub/linux/kernel')
entries = []
f.dir(entries.append)

print "%d entries:" % len(entries)
for entry in entries:
    print entry
f.quit()

