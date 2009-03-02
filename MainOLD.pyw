#self.ui.cancelRender.setEnabled(True)
        #self.ui.render.setEnabled(False)
        if self.ui.inputOutputDir.text() == '':
            self.criticalMessage("No Output Directory Specified", "You must specify an output directory to send the rendered images to.")
        else:
            if self.ui.inputScene.text() == '':
                self.criticalMessage("No Input File Specified", "You must specify an input file to render.")
            elif not os.path.isfile(self.ui.inputScene.text()):
                self.criticalMessage("Input File Error","You must specify an input file to render, not a path.")
            else:
                self.job = self.ui.inputJobName.text()
                self.sFrame = self.ui.inputStartFrame.text()
                self.eFrame = self.ui.inputEndFrame.text()
                self.bFrame = self.ui.inputByFrame.text()
                self.scene = self.ui.inputScene.text()

                #setup mentalray if activated
                if self.ui.useMentalRay.isChecked():
                    self.rayFlag = '-r mr -v 5 -rt 10'
                else:
                    self.rayFlag = ''

                # get information from the drop down menu
                if self.software.currentText() == 'Maya 2008':
                    self.command = '/usr/local/bin/Render'

                elif self.software.currentText() == 'Maya 2009':
                    self.command = '/usr/autodesk/maya2009-x64/bin/Render'

                elif self.software.currentText() == 'Shake':
                    self.command = '/opt/shake/bin/shake'

                self.jobName = self.ui.inputJobName.text()
                self.outputDir = self.ui.inputOutputDir.text()
                self.projectFile = self.ui.inputProject.text()
                self.priority = int(self.ui.inputJobPriority.text())

                if self.jobName == '':
                    self.criticalMessage("Missing Job Name", "You're job needs a name")
                else:
                    if self.software.currentText() != 'Shake':
                        for frame in range(int(self.sFrame),int(self.eFrame)+1, int(self.bFrame)):
                            if self.rayFlag == '':
                                self.que.put([self.jobName, frame, '%s -proj %s -s %s -e %s -rd %s %s' % (self.command, self.projectFile, frame, frame, self.outputDir, self.scene)], self.priority)
                            else:
                                self.que.put([self.jobName, frame, '%s %s -proj %s -s %s -e %s  -rd %s %s' % (self.command, self.rayFlag, self.projectFile, frame, frame, self.outputDir, self.scene)], self.priority)
                    else:
                        for frame in range(int(self.sFrame),int(self.eFrame)+1, int(self.bFrame)):
                            self.que.put([self.jobName, frame, '%s -v -t %s-%sx1 -exec %s' % (self.command, frame, frame, self.scene)], self.priority)

                    self.updateStatus('QUEUE', '%s frames waiting to render' % self.que.size(), 'brown')
                    #self.initJob()
