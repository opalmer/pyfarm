'''
HOMEPAGE: www.pyfarm.net
INITIAL: Feb 6 2009
PURPOSE: Module used to configure a command line render based on operating system
and architecture.   This module first looks at the operating system, then the arhitecture.
After discovering this information it will then try and discover the currently installed software.

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
# From PyQt
from os import listdir, system, getcwd
from os.path import isfile, islink, isdir, normpath

# From PyQt
from PyQt4.QtCore import QThread, QObject, QRegExp, SIGNAL, SLOT

# From PyFarm
from lib import Info
from lib.ReadSettings import ParseXmlSettings

settings = ParseXmlSettings('%s/settings.xml' % getcwd(), skipSoftware=True)

class ConfigureCommand(object):
    '''Configure the input options to a viable output'''
    def __init__(self, ui):
        self.ui = ui

    def maya(self, frame, layer=None):
        '''
        Yield the sequence of frames for maya

        VARS:
            frame (int) -- frame to add to command
            layer (str) -- layer to render

        NOTE: The client will have to break down the command, as it is not cross-platoform.
        this function ONLY returns the formatted arguments
        '''
        # setup the renderer
        if self.ui.mayaRenderer.currentText() == 'Mental Ray':
            output = ' -r mr -v %i -rt %i' % (self.ui.myaMRLogLevel.currentIndex(), \
                                                                self.ui.myaMRThreadCount.value())
        elif self.ui.mayaRenderer.currentText() == 'Scene Preset':
            output = ''
        elif self.ui.mayaRenderer.currentText() == 'Software':
            output = ' -r sw -verb'

        # add the frame flag
        output += ' -s %i -e %i' % (frame, frame)

        # check for layer addition
        if not layer == None:
            output += ' -rl %s' % layer

        # check for camera selectionf
        if not self.ui.mayaCamera.currentText() == '':
            output += ' -cam %s' % self.ui.mayaCamera.currentText()

        # set the render output directory (if one has been set)
        if not self.ui.mayaOutputDir.text() == '':
            output += ' -rd %s' % self.ui.mayaOutputDir.text()

        # set the project file if one has been set
        if not self.ui.mayaProjectFile.text() == '':
            output += ' -proj %s' % self.ui.mayaProjectFile.text()


        # finally, add the scene
        output += ' %s' % self.ui.mayaScene.text()

        return output

    def houdini(self, ver, sFrame, eFrame, bFrame):
        '''
        Yield the sequence of frames for houdini

        VARS:
            ver -- Version of houdini to use
            sFrame -- Start frame of sequence
            eFrame -- End Frame of sequence
            bFrame -- By frame or sequence step
        '''
        pass

    def shake(self, ver, sFrame, eFrame, bFrame):
      '''
      Yield the sequence of frames for shake

      VARS:
        ver -- Version of shake to use
        sFrame -- Start frame of sequence
        eFrame -- End Frame of sequence
        bFrame -- By frame or sequence step
      '''
      pass


class RenderLayerBreakdown(QObject):
    '''
    Breakdown an input file into individual layers.
    Yield each layer back to the ui.
    '''
    def __init__(self, inputFile, parent=None):
        super(RenderLayerBreakdown, self).__init__(parent)
        self.file = inputFile

    def houdini(self):
        '''Output the houdini mantra nodes'''
        hip = open(self.file)
        exp = QRegExp(r"""[0-9]+out/[0-9a-zA-Z]+[.]parm""")

        for line in hip.readline():
            if not exp.indexIn(line):
                yield line

        hip.close()


def Program(inString):
    '''Return the program of a dictionary entry'''
    return inString.split('::')[0]
