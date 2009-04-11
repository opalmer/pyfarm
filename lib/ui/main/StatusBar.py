'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008
PURPOSE: Group of classes used to manage the status bar

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

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
# From PyQt4
from PyQt4.QtGui import QProgressBar, QStatusBar, QWidget
from PyQt4.QtCore import QObject, SIGNAL, QString

class BroadcastProgress(QObject):
    '''
    Given a max. value setup a QProgressBar inside
    of the status bar

    INPUT:
        statusbar -- main statusbar
        max (int) -- max value of the status bar

    '''
    def __init__(self, statusbar, max, parent=None):
        super(BroadcastProgress, self).__init__(parent)
        self.statusbar = statusbar
        self.max = max
        self.count = 0

    def next(self):
        '''Incriment the progress bar'''
        percent = str(int((float(self.count)/float(self.max))*100))
        self.statusbar.showMessage(QString("Broadcast Progress: %s%%" % percent))
        self.count += 1

    def done(self):
        '''Remove the progress bar and reset the status line'''
        print "done"
