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
from os import listdir, system
from os.path import isfile, islink, isdir, normpath

# From PyQt
from PyQt4.QtCore import QThread, QObject, QRegExp, SIGNAL, SLOT

# From PyFarm
import Info

class ConfigureCommand(object):
    '''
    Configure the input options to a viable output
    '''
    def __init__(self):
        pass

    def maya(self, ver, sFrame, eFrame, bFrame, renderer, scene, layer='', camera='', outDir='', project=''):
        '''
        Yield the sequence of frames for maya

        VARS:
        ver -- Version of maya to pull from self.software
        sFrame -- Start frame of sequence
        eFrame -- End Frame of sequence
        bFrame -- By frame or sequence step
        rayRender -- If using mental ray, set to true

        NOTE: The client will have to break down the command, as it is not cross-platoform.
        '''
        # software flag setup
        if renderer == 'Software':
            renderer = ' -r sw -verb'
        elif renderer == 'Mental Ray':
            renderer = ' -r mr -v 5 -rt 6'
        elif renderer == 'Scene Preset':
            renderer = ''

        # render layer setup
        if not layer == '':
            layer = '-rl %s ' % layer

        # camera setup
        if not camera == '':
            camera = '-cam %s ' % camera

        # render directory config
        if not outDir == '':
            outDir = '-rd %s ' % outDir

        if not project == '':
            project = '-proj %s ' % project

        for frame in range(sFrame, eFrame+1, bFrame):
            yield [str(frame), str(ver), str('%s -s %s -e %s %s%s%s%s%s') % (renderer, frame, frame, layer, camera, outDir, project, scene)]

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

class MayaCamAndLayers(QThread):
    '''
    Search the given maya file and return layers and cameras
    '''
    def __init__(self, file, parent=None):
        super(MayaCamAndLayers, self).__init__(parent)
        self.scene = open(file, 'r')

    def run(self):
        layerRegEx = QRegExp(r"""createNode renderLayer -n .+""")
        cameraRegEx = QRegExp(r"""createNode camera -n .+""")

        for line in self.scene.readlines():
            if not layerRegEx.indexIn(line):
                cap = str(layerRegEx.cap()).split('"')
                layer = cap[len(cap)-2]
                if layer != 'defaultRenderLayer':
                    self.emit(SIGNAL("gotMayaLayer"), layer)

            if not cameraRegEx.indexIn(line):
                cap = str(cameraRegEx.cap()).split('"')
                camera = cap[len(cap)-2]
                self.emit(SIGNAL("gotMayaCamera"), camera)

        self.scene.close()

def Program(inString):
    '''Return the program of a dictionary entry'''
    return inString.split('::')[0]