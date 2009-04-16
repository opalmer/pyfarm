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
# From Python
from os.path import isfile

# From PyFarm
from lib.RenderConfig import ConfigureCommand
from lib.ui.main.CustomWidgets import MessageBox

class SubmitManager(object):
    '''
    Handels the initial request from the gui

    INPUT:
        ui -- user interface instance
        dataJob -- job data dictionary
        dataGeneral -- general data manager class instance
    '''
    def __init__(self, parentClass):
        self.ui = parentClass.ui
        self.dataJob = parentClass.dataJob
        self.dataGeneral = parentClass.dataGeneral
        self.softwareManager = parentClass.softwareManager
        self.msg = MessageBox(parentClass)

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
            self.software_generic = self.ui.softwareSelection.currentText()
            commands = []

            if self.software_generic == 'maya':
                print "hello"
                renderer = str(self.ui.mayaRenderer.currentText())
                layers = self.ui.mayaRenderLayers.selectedItems()
                camera = str(self.ui.mayaCamera.currentText())

                if len(layers) >= 1:
                    for layer in layers:
                        for command in config.maya(str(self.software), sFrame, eFrame, bFrame,\
                            renderer, scene, str(layer.text()), camera, outDir, project):
                                print command

                else:
                    for command in config.maya(str(self.software), sFrame, eFrame, bFrame,\
                        renderer, scene, '', camera, outDir, project):
                            frame = QString(command[0])
                            cmd = QString(str(command[2]))
                            print command

                newFrames = self.que.size()-startSize

                self.updateStatus('QUE', 'Added %s frames to job %s(ID: %s) with priority %s' % \
                                  (newFrames, jobName, jobID, priority), 'black')

            elif self.software_generic == 'houdini':
                pass
            elif self.software_generic == 'shake':
                pass
        else:
            pass

    def runChecks(self):
        '''Check to be sure the user has entered the minium values'''
        scene = self.softwareManager.scene.text()
        if scene == '':
            self.msg.warning('Missing File', 'You must provide a file to render')
            return 0
        elif not isfile(scene):
            self.msg.warning('Please Select a File', 'You must provide a file to render, links or directories will not suffice.')
            return 0
        else:
            try:
                if self.jobName == '':
                    self.msg.warning('Missing Job Name', 'You name your job before you rendering')
                    return 0
            except AttributeError:
                self.msg.warning('Missing Job Name', 'You name your job before you rendering')
                return 0
            finally:
                # get a random number and return the hexadecimal value
                return Info.Numbers().randhex()
