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
from os.path import isdir, basename

# From PyQt
from PyQt4.QtGui import QApplication, QDialog, QFileDialog, QMessageBox
from PyQt4.QtCore import SIGNAL, QString, QRegExp

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
        self.connect(self.ui.makeDocs, SIGNAL("pressed()"), self.makeDocs)

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

        self.findFiles()

    def isValid(self):
        '''Return true of required paths are valid'''
        if isdir(str(self.ui.startPath.text())) and isdir(str(self.ui.outPath.text())):
            return True
        else:
            return False

    def notInit(self, path):
        '''Make sure that we are not trying to document __init__ files'''
        if basename(path) != '__init__.py':
            return True
        else:
            return False

    def findFiles(self):
        '''Find files in given paths'''
        if self.isValid():
            for itemA in self.locate('*.py', str(self.ui.startPath.text())):
                if self.notInit(itemA):
                    if itemA not in self.items:
                        self.items.append(itemA)
                        self.ui.selectedFiles.addItem(QString(itemA))
                else:
                    pass
            for itemB in self.locate('*.pyw', str(self.ui.startPath.text())):
                if self.notInit(itemB):
                    if itemB not in self.items:
                        self.items.append(itemB)
                        self.ui.selectedFiles.addItem(QString(itemB))
                else:
                    pass
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
        if self.isValid():
            count = 0
            classCount = 0
            functionCount = 0
            self.ui.progress.setRange(0, self.lineCount())

            # declare search values and vars
            classSearch = QRegExp(r"""class (\w*)[(](\w*)[)][:]""")
            defSearch = QRegExp(r"""def (\w*)[(](.*)[)]""")
            singleLineDocStr = QRegExp(r"""(?:''')(.*)(?:''')|(?:\"\"\")(.*)(?:\"\"\")""")
            multiLine = QRegExp(r"""(?:''')""")
            singleLine = QRegExp(r"""(?:'''.*''')""")
            tripleQuote = QRegExp(r"""^.*'''""")

            #  loop over each file
            for item in self.ui.selectedFiles.selectedItems():
                inMultiLineComment = 0
                hitClass = 0
                text = open(str(item.text()), 'r')
                outfile = open(str(self.ui.outPath.text())+basename(str(item.text()).split('.')[0]+'.txt'), 'w')
                outfile.write('\n====== General Description ======')
                outfile.write('\nGeneral info goes here!')
                outfile.write('\n====== Classes ======')

                # loop over each line in file
                for line in text:
                    qLine = QString(line)
                    quoteOnce = 0

                    # classes
                    if classSearch.indexIn(qLine)+1:
                        classCount += 1
                        caps = classSearch.capturedTexts()
                        outfile.write('\n===== %s =====' % str(caps[1]))
                        outfile.write('\n**Parent**\\\\')
                        outfile.write('\n  * [[http://www.riverbankcomputing.co.uk/static/Docs/PyQt4/html/%s.html|%s]]' % (str(caps[2]).lower(), str(caps[2])))

                    # function documentation
                    if defSearch.indexIn(qLine)+1:
                        cap = defSearch.capturedTexts()

                        # record the function name
                        outfile.write('\n==== %s ====' % cap[1])

                        # gather the input vars
                        outfile.write('\n**Input**\\\\')
                        vars = cap[2]
                        if vars != 'self' and len(vars.split(', ')):
                            for var in vars.split(', '):
                                if var != 'self':
                                    outfile.write('\n  * %s -- ' % var)
                        else:
                            pass

                    # doc string comments
                    if tripleQuote.indexIn(qLine)+1 and not singleLine.indexIn(qLine)+1:
                        if inMultiLineComment:
                            inMultiLineComment = 0
                        else:
                            outfile.write('\n**Description**\\\\')
                            inMultiLineComment = 1
                    elif singleLine.indexIn(qLine)+1:
                        outfile.write('\n\\\\\n**Description**\\\\')
                        outfile.write('%s' % singleLine.cap()[3:len(singleLine.cap())-3])
                    elif inMultiLineComment:
                        outfile.write('\n%s' % QString(line).trimmed())

                    count += 1
                    self.ui.progress.setValue(count)
                outfile.close()
                text.close()
        else:
            self.warningMessage('You need to specify input/output dirs', \
                                'Please make sure you have entered an input and ouput' \
                                'directory before generating documentation!')

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
