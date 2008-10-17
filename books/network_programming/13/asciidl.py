#!/usr/bin/env python
# ASCII download - Chapter 13 - asciidl.py
# Downloads README from remote and writes it to disk.

from ftplib import FTP

def writeline(data):
    fd.write(data + "\n")

f = FTP('ftp.kernel.org')
f.login()

f.cwd('/pub/linux/kernel')
fd = open('README', 'wt')
f.retrlines('RETR README', writeline)
fd.close()

f.quit()

