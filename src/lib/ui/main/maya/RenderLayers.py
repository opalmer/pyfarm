'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 17 2009
PURPOSE: Main program to run and manage PyFarm

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
from PyQt4.QtCore import QThread, QRegExp, QString

import lib.Logger as logger

__MODULE__ = "lib.ui.main.maya.RenderLayers"

class MayaCamAndLayers(QThread):
    '''
    Search the given maya file and return layers and cameras

    INPUT:
        layers (widget) - the input maya render layers widget
        camera (widget) - the input camera widget
    '''
    def __init__(self, layers, camera, parent=None):
        super(MayaCamAndLayers, self).__init__(parent)
        self.layers = layers
        self.camera = camera

    def run(self, scene):
        '''Start the thread and add the layers to the gui'''
        scene = open(scene, 'r')
        layerRegEx = QRegExp(r"""createNode renderLayer -n .+""")
        cameraRegEx = QRegExp(r"""createNode camera -n .+""")

        for line in scene.readlines():
            if not layerRegEx.indexIn(line):
                cap = str(layerRegEx.cap()).split('"')
                layer = cap[len(cap)-2]
                if layer != 'defaultRenderLayer':
                    self.layers.addItem(QString(layer))

            if not cameraRegEx.indexIn(line):
                cap = str(cameraRegEx.cap()).split('"')
                self.camera.addItem(QString(cap[len(cap)-2]))

        scene.close()
