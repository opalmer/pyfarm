'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 12 2009
PURPOSE: Contains classes related to initial job submission

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
from lib.RenderConfig import ConfigureCommand

class SubmitManager(object):
    '''
    Handels the initial request from the gui

    INPUT:
        ui -- user interface instance
        dataJob -- job data dictionary
        dataGeneral -- general data manager class instance
    '''
    def __init__(self, ui, dataJob, dataGeneral):
        self.ui = ui
        self.dataJob = dataJob
        self.dataGeneral = dataGeneral

    def beginProcessing(self):
        '''
        Begin processing information from the
        user interface
        '''
        config = ConfigureCommand()
        jobID = self.runChecks()

        if jobID:
            startSize = self.que.size()
            sFrame = self.ui.inputStartFrame.value()
            eFrame = self.ui.inputEndFrame.value()
            bFrame = self.ui.inputByFrame.value()
            priority = self.ui.inputJobPriority.value()
            jobName = str(self.ui.inputJobName.text())
            outDir = str(self.ui.mayaOutputDir.text())
            project = str(self.ui.mayaProjectFile.text())
            scene = str(self.scene.text())
            commands = []

            if self.software_generic == 'maya':
                renderer = str(self.ui.mayaRenderer.currentText())
                layers = self.ui.mayaRenderLayers.selectedItems()
                camera = str(self.ui.mayaCamera.currentText())

                if len(layers) >= 1:
                    for layer in layers:
                        for command in config.maya(str(self.software), sFrame, eFrame, bFrame,\
                            renderer, scene, str(layer.text()), camera, outDir, project):
                                self.que.put([jobName, command[0], command[1], str(command[2])], priority)
                else:
                    for command in config.maya(str(self.software), sFrame, eFrame, bFrame,\
                        renderer, scene, '', camera, outDir, project):
                            frame = QString(command[0])
                            cmd = QString(str(command[2]))
                            self.que.put([jobName, frame, cmd], priority)

                newFrames = self.que.size()-startSize

                self.updateStatus('QUE', 'Added %s frames to job %s(ID: %s) with priority %s' % \
                                  (newFrames, jobName, jobID, priority), 'black')

            elif self.software_generic == 'houdini':
                pass
            elif self.software_generic == 'shake':
                pass
        else:
            pass
