'''
HOMEPAGE: www.pyfarm.net
INITIAL: Oct 14 2010
PURPOSE: Contains small dialogs for various events
and activities.

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

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

from PyQt4.Qt import Qt
from PyQt4 import QtCore, QtGui

class CloseEvent(QtGui.QDialog):
    def __init__(self, parent=None):
        super(CloseEvent, self).__init__(parent)
        self.layout = QtGui.QGridLayout()
        self.setWindowTitle("Closing PyFarm")

        # create widgets
        self.ok = QtGui.QPushButton("Ok")
        self.helpLabel  = QtGui.QLabel("What would you like to do?")
        self.saveDB     = QtGui.QCheckBox("Save Datebase")
        self.uiSettings = QtGui.QCheckBox("Interface Settings")
        self.rLabel     = QtGui.QLabel("Remote Clients:")
        self.rHosts     = QtGui.QComboBox()
        self.rHosts.addItems((
                                 "Finish Frames and Exit",
                                 "Terminate Client",
                                 "Restart Client"
                             ))

        # final signal
        self.connect(
                     self.ok,
                     QtCore.SIGNAL("pressed()"),
                     self.signalState
                     )

        # add widgets to layout
        self.layout.addWidget(self.helpLabel, 0, 0)
        self.layout.addWidget(self.saveDB, 1, 0)
        self.layout.addWidget(self.uiSettings,  2, 0)
        self.layout.addWidget(self.rLabel, 3, 0)
        self.layout.addWidget(self.rHosts, 3, 1)
        self.layout.addWidget(self.ok, 4, 1)
        self.setLayout(self.layout)

    def signalState(self):
        '''
        Emit the state of the dialog back up to its
        calling parent
        '''
        state = {
                    "saveDB"     : self.saveDB.isChecked(),
                    "saveUI"     : self.uiSettings.isChecked(),
                    "hostAction" : self.rHosts.currentIndex()
                }
        self.emit(QtCore.SIGNAL("state"), state)