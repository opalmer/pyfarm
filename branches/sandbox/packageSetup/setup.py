#!/usr/bin/env python

# CLEANUP NOTES:
# 1.) REMOVE any top level modules/variables at the end of the file
#     to prevent namespace clashes
# 2.) should be using FROM lib IMPORT logger

import os
import shutil

CWD = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.abspath(os.path.join(CWD, "..", "..", "..", "trunk"))

def wipe(path):
    print "Removing old files..."
    for listing in os.listdir(CWD):
        if os.path.isdir(listing):
            shutil.rmtree(listing)

        elif os.path.isfile(listing) and "setup.py" not in listing and "initTemplate.py" not in listing:
            os.remove(listing)

def insertFirst(line, path, exts=(".py", "pyw")):
    '''Insert the given line to the beginning of the file at path'''
    for ext in exts:
        if path.endswith(ext):
            lines  = open(path, 'r').readlines()
            output = open(path, 'w')
            output.write(line+os.linesep)
            output.write(''.join(lines))
            output.close()

def insertLast(line, path, exts=(".py", "pyw")):
    '''Insert the given line at the end of the file'''
    for ext in exts:
        if path.endswith(ext):
            output = open(path, 'a')
            output.write(os.linesep+line)
            output.close()

def mirror(blank=False, importPrint=True, templateInit=True):
    '''
    Mirror the trunk to this testing directory

    @param blank: Create an empty destination file
    @param importPrint: print the file name on import
    @param templateInit: Copy initTemplate.py instead of the incoming init
    '''
    print "Copying old files..."
    for parent, dirs, files in os.walk(SRC):
        for filename in files:
            for ext in ("py", "pyw", "xml"):
                if filename.endswith(".%s" % ext):
                    root = os.path.join(parent, filename)
                    rel  = root.split(SRC)[1][1:]
                    dst  = os.path.join(CWD, rel)
                    src  = os.path.join(SRC, rel)

                    if not os.path.isdir(os.path.dirname(dst)):
                        os.makedirs(os.path.dirname(dst))

                    if dst.endswith("__init__.py") and templateInit:
                        src = os.path.join(CWD, "initTemplate.py")

                    shutil.copy(src, dst)

                    if blank:
                        fileObj = open(dst, 'w')
                        fileObj.write("")
                        fileObj.close()

                    if importPrint:
                        insertFirst("print 'Importing: %s'" % dst, dst)
                        insertLast("print '...imported: %s'" % dst, dst)

def main():
    wipe(CWD)
    mirror(blank=False, importPrint=True, templateInit=True)

main()