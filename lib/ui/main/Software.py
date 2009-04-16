'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 15 2009
PURPOSE: Classes for querying the current software and setting up
the related variables

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
from os import getcwd


# From PyQt
from PyQt4.QtGui import QFileDialog
from PyQt4.QtCore import QString, QFileInfo

# From PyFarm
from lib.ReadSettings import ParseXmlSettings
from lib.ui.main.CustomWidgets import MessageBox
settings = ParseXmlSettings('%s/settings.xml' % getcwd())


class SoftwareContextManager(object):
    '''
    Manages the input of files and parameters basd on the current
    software selection.
    '''
    def __init__(self, parentClass):
        self.ui = parentClass.ui
        self.contextMenu = parentClass.ui.softwareSelection
        self.msg = MessageBox(parentClass)

    def commonName(self):
        '''
        Return the common name of the current context menu
        selection.
        '''
        return settings.commonName(str(self.contextMenu.currentText()))

    def setSoftware(self, software):
        '''
        If the software selction is changed, setup the
        relevant info.  Upon calling self._setDefaults() the sofware generic name (maya),
        command, file grep list, scene ui ref, etc. are also setup

        OUTPUTS:
            self.scene -- path to ui component used to hold the file to render
            '''
        # if we are using maya
        if settings.commonName(str(software)) == 'maya':
            self.scene = self.ui.mayaScene
            self._setDefaults(software)

        # if we are using houdini
        elif settings.commonName(str(software)) == 'houdini':
            self.scene = self.ui.houdiniFile
            self._setDefaults(software)

        # if we are using shake
        elif settings.commonName(str(software)) == 'shake':
            self.scene = self.ui.shakeScript
            self._setDefaults(software)

    def _setDefaults(self, software):
        '''
        For the given software, set some defaults

        INPUT:
            self.command (str) - command to render with
            self.software (str) -- short name of currently select software
            self.fileGrep (str) - grep to use when searching for files
            self.widgetIndex (int) -- integer of widget for render settings
        '''
        self.command = settings.command(str(software))
        self.software = settings.commonName(str(software))
        self.fileGrep = settings.fileGrep(str(software))
        self.widgetIndex = settings.widgetIndex(str(software))
        self.ui.optionStack.setCurrentIndex(self.widgetIndex)

    def browseForScene(self):
        '''Allow the user to browse for a scene based on the current software'''
        render_file = QFileInfo(QFileDialog.getOpenFileName(\
            None,
            QString("Select File To Render"),
            QString(),
            QString(self.fileGrep),
            None,
            QFileDialog.Options(QFileDialog.DontResolveSymlinks))).symLinkTarget()

        if not render_file == '':
            self.scene.setText(render_file)

    def browseForMayaOutDir(self):
        '''Browse for the maya output directory'''
        outdir = QFileDialog.getExistingDirectory(\
            None,
            QString("Select Image Output Directory"),
            QString(),
            QFileDialog.Options(QFileDialog.ShowDirsOnly))

        if not outdir == '':
            self.ui.mayaOutputDir.setText(outdir)

    def browseForMayaProjectFile(self):
        '''Set the maya project file'''
        projectFile =  QFileDialog.getOpenFileName(\
            None,
            QString("Select Your Maya Project File"),
            QString(),
            QString("workspace.mel"),
            None)

        if not projectFile == '':
            self.ui.mayaProjectFile.setText(projectFile)
