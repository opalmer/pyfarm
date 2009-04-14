#!/usr/bin/python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 14 2009
PURPOSE: Wiki documentation generator for PyFarm

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
# From Python
import os, fnmatch
from sys import argv, exit
from os.path import isdir

# From PyQt
from PyQt4.QtGui import QApplication, QDialog, QFileDialog, QMessageBox
from PyQt4.QtCore import SIGNAL, QString

# From PyFarm
from lib.ui.WikiDocGen import Ui_WikiDocGen


class WikiDocGenUI(QDialog):
    '''
    Documentation generator for PyFarm (outputs to wiki pages)
    '''
    def __init__(self, parent=None):
        super(WikiDocGenUI, self).__init__(parent)

        # setup ui
        self.ui = Ui_WikiDocGen()
        self.ui.setupUi(self)
        self.items = []

        # setup button connections
        self.connect(self.ui.browseStartPath, SIGNAL("pressed()"), self.browseForStartPath)
        self.connect(self.ui.browseOutPath, SIGNAL("pressed()"), self.browseForOutPath)
        self.connect(self.ui.findFiles, SIGNAL("pressed()"), self.findFiles)
        self.connect(self.ui.makeDocs, SIGNAL("pressed()"), self.makeDocs)
        self.connect(self.ui.findAndMake, SIGNAL("pressed()"), self.findAndMake)

    def browseForStartPath(self):
        '''Browse for starting path'''
        self.ui.startPath.setText(QFileDialog.getExistingDirectory(\
            None,
            self.trUtf8("Select Search Start Path"),
            QString(),
            QFileDialog.Options(QFileDialog.ShowDirsOnly)))

    def browseForOutPath(self):
        '''Browse for the output path'''
        self.ui.outPath.setText(QFileDialog.getExistingDirectory(\
            None,
            self.trUtf8("Select Output Directory Path"),
            QString(),
            QFileDialog.Options(QFileDialog.ShowDirsOnly)))

    def isValid(self):
        '''Return true of required paths are valid'''
        if isdir(str(self.ui.startPath.text())) and isdir(str(self.ui.outPath.text())):
            return True
        else:
            return False

    def findFiles(self):
        '''Find files in given paths'''
        if self.isValid():
            for item in self.locate('*.py', str(self.ui.startPath.text())):
                if item not in self.items:
                    self.items.append(item)
                    self.ui.selectedFiles.addItem(QString(item))
        else:
            self.warningMessage('You need to specify input/output dirs', \
                                'Please make sure you have entered an input and ouput' \
                                'directory before generating documentation!')

    def lineCount(self):
        '''Return the line count of the selected files'''
        lineCount = 0
        for item in self.ui.selectedFiles.selectedItems():
            text = open(str(item.text()), 'r')
            for line in text:
                lineCount += 1
            text.close()
        return lineCount

    def makeDocs(self):
        '''Make the documentation'''
        #print line[:len(line)-1]
        if self.isValid():
            classSearch = QRegExp(r"""class (\w*)[(](\w*)[)][:]""")
            defSearch = QRegExp(r"""def (\w*)[(](\w*)[,]""")

            self.ui.progress.setRange(0, self.lineCount())
        else:
            self.warningMessage('You need to specify input/output dirs', \
                                'Please make sure you have entered an input and ouput' \
                                'directory before generating documentation!')

    def findAndMake(self):
        '''Find and make the documentation'''
        self.findFiles()
        self.makeDocs()

    def locate(self, pattern, root=os.curdir):
        '''Locate all files matching supplied filename pattern in and below
        supplied root directory.'''
        for path, dirs, files in os.walk(os.path.abspath(root)):
            for filename in fnmatch.filter(files, pattern):
                yield os.path.join(path, filename)

    def warningMessage(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        msg = QMessageBox()
        msg.warning(self, title, unicode(message))

app = QApplication(argv)
DocGen = WikiDocGenUI()
DocGen.show()
exit(app.exec_())
