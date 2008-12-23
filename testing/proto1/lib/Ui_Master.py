'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 19 2008
PURPOSE: Classes used to call up the render gui and
submit renders
'''
import sys
import time
from PyQt4 import QtCore, QtGui
from ui_Proto1 import Ui_Proto1

__VERSION__ = '0.0.1'
__AUTHOR__ = 'Oliver Palmer'
__DESCRIPTION__ = 'PyFarm'

class Proto1(QtGui.QDialog):
    def __init__(self):
        '''Tmp class to create proto1 gui from QtDesigner UI output'''
        QtGui.QDialog.__init__(self)

        # first setup the user interface from QtDesigner
        self.ui = Ui_Proto1()
        self.ui.setupUi(self)

        # initilize some required variables
        self.scene = None
        self.sFrame = None
        self.eFrame = None

        # Connect Qt signals to actionss
        self.connect(self.ui.renderButton, QtCore.SIGNAL("pressed()"), self._startRender)
        self.connect(self.ui.aboutButton,  QtCore.SIGNAL("pressed()"),  self._about)

    def _sFrame(self):
        '''(int) get the start frame from the interface'''
        return self.ui.sFrameBox.value()

    def _eFrame(self):
        '''(int) Get the end frame from the interface'''
        return self.ui.eFrameBox.value()

    def _scene(self):
        '''Return the currently entered scene from the interface'''
        return self.ui.sceneEntry.text()

    def _about(self):
        '''Informs the user about this program'''
        message = 'Author: %s\nVersion: %s\n\n%s' % (__AUTHOR__, __VERSION__, __DESCRIPTION__)
        about = QtGui.QMessageBox()
        about.information(None, "PyFarm -- Prototype 1 - About", message)

    def _testProgress(self):
        '''Run a test on the progress bar'''
        min = 0
        max = 100
        i = 0

        self.ui.progressBar.setMinimum(min)
        self.ui.progressBar.setMaximum(max)

        while i <= max:
            self.ui.progressBar.setValue(i)
            time.sleep(.1)
            i += 1

    def _startRender(self):
            '''Once the render button is pressed this function is
            executed starting the entire render process'''

            # lock in the variables for rendering
            self.scene = self._scene()
            self.sFrame = self._sFrame()
            self.eFrame = self._eFrame()

            # inform the user of their choices
            print "Starting Render"
            print "Scene: %s" % self.scene
            print "Start Frame: %s" % self.sFrame
            print "End Frame: %s" % self.eFrame
            self._testProgress()

if __name__ == "__main__":
    app = QtGui.QApplication(sys.argv)
    #window = QtGui.QDialog()
    ui = Proto1()
    ui.show()
    sys.exit(app.exec_())
