#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Jan 16 2009
PURPOSE: Drop down menu testing

    This file is part of PyFarm.

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
import sys
from PyQt4.QtCore import *
from PyQt4.QtGui import *
from PyQt4.QtNetwork import *
from lib.ui.DropDownMenuTest import Ui_DropDownMenuTest
from lib.ui.RenderOptionsMaya import Ui_RenderOptionsMaya
from lib.ui.RenderOptionsHoudini import Ui_RenderOptionsHoudini
from lib.ui.RenderOptionsShake import Ui_RenderOptionsShake
from lib.RenderConfig import SoftwareInstalled

# get ready to find the currently installed software
LOCAL_SOFTWARE = {}
software = SoftwareInstalled()

# find the software and add it to the dictionary
LOCAL_SOFTWARE.update(software.maya())
LOCAL_SOFTWARE.update(software.houdini())
LOCAL_SOFTWARE.update(software.shake())

class DropDownMenuTest(QWidget):
    def __init__(self):
        super(DropDownMenuTest, self).__init__()

        # setup UI
        self.ui = Ui_DropDownMenuTest()
        self.ui.setupUi(self)

        # make connections
        self.connect(self.ui.softwareSelection, SIGNAL("activated (const QString&)"), self.setSoftware)

        for (software,path) in LOCAL_SOFTWARE.items():
            self.ui.softwareSelection.addItem(software)

    def configRenderOptions(self, software):
        if software[:4] == 'Maya':
            self.ui.optionStack.setCurrentIndex(0)

        elif software[:4] == 'Houd':
            self.ui.optionStack.setCurrentIndex(1)

        elif software[:4] == 'Shak':
            self.ui.optionStack.setCurrentIndex(2)

        else:
            print software

    def setSoftware(self, software):
        '''Setup the software command and call the correct gui'''
        programName = str(software)
        self.software = [programName, LOCAL_SOFTWARE[programName]]
        self.configRenderOptions(self.software[0])


app = QApplication(sys.argv)
ui =DropDownMenuTest()
ui.show()
app.exec_()
