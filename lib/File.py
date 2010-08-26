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
import sys
import string

from PyQt4 import QtCore

class Monitor(QtCore.QObject):
    '''
    Async file monitoring object, searches for and returns
    newlines at the given interval
    '''
    def __init__(self, filePath, interval=5, parent=None):
        super(Monitor, self).__init__(parent)
        self.lastCount = 0
        self.filePath  = filePath
        self.parent    = parent
        self.interval  = interval * 1000 # convert to seconds
        self.timer     = QtCore.QTimer()
        self.timer.setInterval(self.interval)

        self.connect(
                        self.timer,
                        QtCore.SIGNAL("timeout()"),
                        self.timeout
                     )

        self.timer.start(self.interval)

    def timeout(self):
        '''Check for newlies, emit a signal if newline is found'''
        f = open(self.filePath, 'r')
        lines = f.readlines()
        f.close()

        if len(lines) != self.lastCount:
            currentLine = 1
            for line in lines:
                if currentLine > self.lastCount:
                    self.emit(QtCore.SIGNAL("newLine"), line.split(os.sep))
                currentLine += 1