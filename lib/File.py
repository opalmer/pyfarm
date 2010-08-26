'''
HOMEPAGE: www.pyfarm.net
INITIAL: Aug 25 2010
PURPOSE: To provide a simple means for interacting with file objects while
providing as much functionality as possible.

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os

from PyQt4 import QtCore

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
        self.filePath  = filePath
        self.lastSize  = os.stat(filePath).st_size
        self.parent    = parent
        self.interval  = int(float(interval) * 1000.00)

        # create the inital line cache
        f = open(filePath, 'r')
        self.lineCache = []
        for line in f.readlines():
            formatted = self.formatLine(line)
            self.lineCache.append(formatted)
        f.close()

        # setup and start the timer
        self.timer = QtCore.QTimer()
        self.timer.setInterval(self.interval)
        self.timer.start(self.interval)
        self.connect(
                        self.timer,
                        QtCore.SIGNAL("timeout()"),
                        self.timeout
                     )


    def cache(self):
        '''Return the current line cache'''
        return self.lineCache

    def formatLine(self, line):
        '''Return a properly formatted line as a string'''
        if line.split() == "":
            return ""
        else:
            return line.rstrip('\r\n')

    def updateInterval(self, newInterval):
        '''
        Change the iterval to newInterval and
        update internal variables
        '''
        self.interval = newInterval * 1000
        self.timer.setInterval(self.interval)

    def timeout(self):
        '''
        Check for newlies, emit a signal if
        newline is found
        '''
        stat = os.stat(self.filePath)
        newSize = stat.st_size

        if newSize != self.lastSize:
            self.lastSize = newSize

            # open the file and get all lines
            f = open(self.filePath, 'r')
            lines = [ self.formatLine(line) for line in f.readlines() ]
            f.close()

            # FIXME: Slice Range
            lineDiff = len(lines)-len(self.lineCache)
            position = len(self.lineCache)-lineDiff
            print self.lineCache[position:]
            self.lineCache = lines



#            # get the difference between old and new lists
#            newLines = []
#            for line in lines:
#                if self.lineCache:
#                    print "here"
#                    print len(self.lineCache)
#                    if self.lineCache[lineNo] != line:
#                        print line
#                    lineNo += 1
#                else:
#                    lineNo = 0
#                    self.lineCache.insert(
#                                          lineNo,
#                                          line
#                                          )
#                    print self.lineCache
#                    newLines.append(line)
#
#            if newLines:
#                self.emit(
#                          QtCore.SIGNAL("newLines"),
#                          newLines
#                          )

if __name__ == "__main__":
    import sys
    FILE_PATH = sys.argv[1]
    class Monitor(QtCore.QObject):
        def __init__(self, parent=None):
            super(Monitor, self).__init__(parent)

        def runMonitor(self):
            tail = Tail(FILE_PATH,  parent=self)
            print tail.cache() # get initial cache
            self.connect(
                         tail,
                         QtCore.SIGNAL("newLines"),
                         self.newLines
                         )

        def newLines(self, lines):
            print lines

    app = QtCore.QCoreApplication(sys.argv)
    main = Monitor()
    main.runMonitor()
    app.exec_()
