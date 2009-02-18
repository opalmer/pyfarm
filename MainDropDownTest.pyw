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
from lib.ui.DropDownMenuTest import Ui_DropDownMenuTest
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
        self.connect(self.ui.softwareSelection, SIGNAL("activated (const QString&)"), self.configure_renderer)

        for (software,path) in LOCAL_SOFTWARE.items():
            self.ui.softwareSelection.addItem(software)

        self.configure_renderer(self.ui.softwareSelection.currentText())

    def configure_renderer(self, software):
        '''
        Given an incoming program, change the rendering options
        and self.software
        '''
        program = str(software)
        self.software = [program, LOCAL_SOFTWARE[program]]
        #self.configRenderOptions(self.software[0])

        if program[:4] == 'Maya':
            print self.software
            self.ui.optionStack.setCurrentIndex(0)

        elif program[:4] == 'Houd':
            print self.software
            self.ui.optionStack.setCurrentIndex(1)

        elif program[:4] == 'Shak':
            print self.software
            self.ui.optionStack.setCurrentIndex(2)


app = QApplication(sys.argv)
ui =DropDownMenuTest()
ui.show()
app.exec_()
