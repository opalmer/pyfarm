'''
HOMEPAGE: www.pyfarm.net
INITIAL: Aug 25 2010
PURPOSE: To provide a simple means for interacting with file objects while
providing as much functionality as possible.

    This file is part of PyFarm.
    Copyright (C) 2008-2011 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys
import fnmatch
import difflib
from PyQt4 import QtCore

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Logger, system

log = Logger.Logger(MODULE)

def mkdir(path):
    '''Create a directory'''
    if not os.path.isdir(path):
        try:
            os.makedirs(path)
        except:
            log.error("Failed to create directory: %s" % path)
        else:
            log.info("Created Directory: %s" % path)

class Tail(QtCore.QObject):
    '''
    Async file tail object, searches for and returns
    newlines at the given interval.

    NOTE: Given the asyc nature of this class
    the parent argument is usually |REQUIRED|

    NOTE2: Tail is meant to respond just as Unix's
    tail -f command does.
    '''
    def __init__(self, filePath, interval=1, parent=None):
        super(Tail, self).__init__(parent)
        self.parent    = parent
        self.filePath  = filePath
        self.interval  = int(float(interval) * 1000.00)
        self.changes   = 0
        self.linecount = 0
        self.lines     = []

        # monitor setup
        self.monitor   = QtCore.QFileSystemWatcher(self.parent)
        self.monitor.addPath(self.filePath)
        self.connect(
                         self.monitor,
                         QtCore.SIGNAL("fileChanged(QString)"),
                         self.fileChanged
                     )

    def _getString(self, line):
        '''Given a line of text return the proper string'''
        if fnmatch.fnmatch(line, "+ *"):
            return line.split("+ ")[1]
        else:
            return False

    def fileChanged(self, fileString):
        '''When a file is changed, show the difference'''
        f = open(fileString, 'r')

        # determine if we have a new line count
        lines = f.readlines()
        if len(lines) > self.linecount:
            for line in difflib.Differ().compare(self.lines, lines):
                string = self._getString(line)
                if string:
                    self.emit(
                                  QtCore.SIGNAL("newline"),
                                  string
                              )
            # reset variables
            self.lines     = lines
            self.linecount = len(lines)

        f.close()

class Monitor(QtCore.QObject):
    '''Given a filepath, monitor it for changes.'''
    def __init__(self, filepath, parent=None):
        super(Monitor, self).__init__(parent)
        self.linecount = 1
        self.filepath  = filepath

    def runMonitor(self):
        tail = Tail(self.filepath,  parent=self)
        self.connect(
                         tail,
                         QtCore.SIGNAL("newline"),
                         self.newLine
                     )

    def newLine(self, line):
        print "%03i: %s" % (self.linecount, line.strip("\r\n"))
        self.linecount += 1
