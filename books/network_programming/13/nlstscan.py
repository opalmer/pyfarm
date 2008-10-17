#!/usr/bin/env python
# nlst() with file/directory detection example - Chapter 13
# nlstscan.py

import ftplib

class DirEntry:
    def __init__(self, filename, ftpobj, startingdir = None):
        self.filename = filename
        if startingdir == None:
            startingdir = ftpobj.pwd()
        try:
            ftpobj.cwd(filename)
            self.filetype = 'd'
            ftpobj.cwd(startingdir)
        except ftplib.error_perm:
            self.filetype = '-'
        
    def gettype(self):
        """Returns - for regular file; d for directory."""
        return self.filetype

    def getfilename(self):
        return self.filename

f = ftplib.FTP('ftp.kernel.org')
f.login()

f.cwd('/pub/linux/kernel')
nitems = f.nlst()
items = [DirEntry(item, f, f.pwd()) for item in nitems]

print "%d entries:" % len(items)
for item in items:
    print "%s: type %s" % (item.getfilename(), item.gettype())
f.quit()

